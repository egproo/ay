<?php
/**
 * نموذج نظام تجهيز الطلبات المتقدم
 *
 * يوفر إدارة شاملة لتجهيز الطلبات مع:
 * - تجهيز الطلبات (Picking & Packing)
 * - التكامل مع المخزون والمبيعات
 * - إنشاء أوامر الشحن
 * - التكامل مع شركات الشحن
 * - التكامل المحاسبي
 *
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ModelShippingOrderFulfillment extends Model {

    /**
     * الحصول على الطلبات الجاهزة للتجهيز
     */
    public function getOrdersReadyForFulfillment($filter_data = []) {
        $sql = "SELECT o.*,
                CONCAT(o.firstname, ' ', o.lastname) as customer_name,
                os.name as order_status_name,
                (SELECT COUNT(*) FROM cod_order_product WHERE order_id = o.order_id) as product_count,
                (SELECT SUM(quantity) FROM cod_order_product WHERE order_id = o.order_id) as total_quantity
                FROM cod_order o
                LEFT JOIN cod_order_status os ON (o.order_status_id = os.order_status_id)
                WHERE o.order_status_id IN (2, 3, 15) -- Confirmed, Processing, Awaiting Fulfillment
                AND o.order_id NOT IN (
                    SELECT DISTINCT order_id FROM cod_shipping_order
                    WHERE status NOT IN ('cancelled', 'failed')
                )";

        if (!empty($filter_data['filter_order_id'])) {
            $sql .= " AND o.order_id = '" . (int)$filter_data['filter_order_id'] . "'";
        }

        if (!empty($filter_data['filter_customer'])) {
            $sql .= " AND CONCAT(o.firstname, ' ', o.lastname) LIKE '%" . $this->db->escape($filter_data['filter_customer']) . "%'";
        }

        if (!empty($filter_data['filter_date_start'])) {
            $sql .= " AND DATE(o.date_added) >= '" . $this->db->escape($filter_data['filter_date_start']) . "'";
        }

        if (!empty($filter_data['filter_date_end'])) {
            $sql .= " AND DATE(o.date_added) <= '" . $this->db->escape($filter_data['filter_date_end']) . "'";
        }

        $sql .= " ORDER BY o.date_added ASC";

        if (isset($filter_data['start']) || isset($filter_data['limit'])) {
            if ($filter_data['start'] < 0) {
                $filter_data['start'] = 0;
            }

            if ($filter_data['limit'] < 1) {
                $filter_data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$filter_data['start'] . "," . (int)$filter_data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    /**
     * الحصول على تفاصيل الطلب للتجهيز مع الخيارات والوحدات
     */
    public function getOrderFulfillmentDetails($order_id) {
        // الحصول على بيانات الطلب الأساسية
        $order_query = $this->db->query("
            SELECT o.*,
                CONCAT(o.firstname, ' ', o.lastname) as customer_name,
                os.name as order_status_name,
                c.country as shipping_country,
                z.name as shipping_zone,
                b.name as branch_name
            FROM cod_order o
            LEFT JOIN cod_order_status os ON (o.order_status_id = os.order_status_id AND os.language_id = 1)
            LEFT JOIN cod_country c ON (o.shipping_country_id = c.country_id)
            LEFT JOIN cod_zone z ON (o.shipping_zone_id = z.zone_id)
            LEFT JOIN cod_branch b ON (o.store_id = b.branch_id)
            WHERE o.order_id = '" . (int)$order_id . "'
        ");

        if (!$order_query->num_rows) {
            return false;
        }

        $order = $order_query->row;

        // الحصول على منتجات الطلب مع الخيارات والوحدات والمخزون
        $products_query = $this->db->query("
            SELECT op.*, p.image, p.weight, p.length, p.width, p.height, p.location as product_location,
                pi.quantity as stock_quantity,
                pi.location as stock_location,
                u.desc_ar as unit_name,
                u.code as unit_code,
                pu.conversion_factor,
                pb.barcode,
                pb.type as barcode_type,
                (SELECT SUM(quantity) FROM cod_inventory_movement
                 WHERE product_id = op.product_id AND movement_type = 'reserved'
                 AND reference_type = 'order' AND reference_id = op.order_id) as reserved_quantity
            FROM cod_order_product op
            LEFT JOIN cod_product p ON (op.product_id = p.product_id)
            LEFT JOIN cod_product_inventory pi ON (op.product_id = pi.product_id AND pi.unit_id = op.unit_id)
            LEFT JOIN cod_unit u ON (op.unit_id = u.unit_id)
            LEFT JOIN cod_product_unit pu ON (op.product_id = pu.product_id AND op.unit_id = pu.unit_id)
            LEFT JOIN cod_product_barcode pb ON (op.product_id = pb.product_id AND op.unit_id = pb.unit_id)
            WHERE op.order_id = '" . (int)$order_id . "'
            ORDER BY op.order_product_id
        ");

        $products = [];
        foreach ($products_query->rows as $product) {
            // الحصول على خيارات المنتج في الطلب
            $options_query = $this->db->query("
                SELECT oo.*, od.name as option_name, ovd.name as option_value_name,
                    pov.quantity as option_stock_quantity,
                    pov.subtract as option_subtract_stock
                FROM cod_order_option oo
                LEFT JOIN cod_option_description od ON (oo.product_option_id = od.option_id AND od.language_id = 1)
                LEFT JOIN cod_option_value_description ovd ON (oo.product_option_value_id = ovd.option_value_id AND ovd.language_id = 1)
                LEFT JOIN cod_product_option_value pov ON (oo.product_option_value_id = pov.product_option_value_id)
                WHERE oo.order_id = '" . (int)$order_id . "'
                AND oo.order_product_id = '" . (int)$product['order_product_id'] . "'
                ORDER BY oo.order_option_id
            ");

            $product['options'] = $options_query->rows;

            // الحصول على الباركود المناسب للمنتج + الخيارات + الوحدة
            $barcode_query = $this->db->query("
                SELECT barcode, type FROM cod_product_barcode
                WHERE product_id = '" . (int)$product['product_id'] . "'
                AND unit_id = '" . (int)$product['unit_id'] . "'
                " . (!empty($product['options']) ? "AND product_option_id IS NOT NULL" : "AND product_option_id IS NULL") . "
                ORDER BY product_barcode_id ASC
                LIMIT 1
            ");

            if ($barcode_query->num_rows) {
                $product['specific_barcode'] = $barcode_query->row['barcode'];
                $product['specific_barcode_type'] = $barcode_query->row['type'];
            }

            $products[] = $product;
        }

        $order['products'] = $products;

        // حساب الأوزان والأبعاد الإجمالية
        $total_weight = 0;
        $total_volume = 0;
        $can_fulfill = true;

        foreach ($order['products'] as &$product) {
            $product_weight = $product['weight'] * $product['quantity'];
            $product_volume = ($product['length'] * $product['width'] * $product['height']) * $product['quantity'];

            $total_weight += $product_weight;
            $total_volume += $product_volume;

            // التحقق من توفر المخزون
            $available_quantity = $product['stock_quantity'] - $product['reserved_quantity'];
            $product['available_quantity'] = $available_quantity;
            $product['can_fulfill'] = ($available_quantity >= $product['quantity']);

            if (!$product['can_fulfill']) {
                $can_fulfill = false;
            }
        }

        $order['total_weight'] = $total_weight;
        $order['total_volume'] = $total_volume;
        $order['can_fulfill'] = $can_fulfill;

        // الحصول على خيارات الشحن المتاحة
        $order['shipping_options'] = $this->getAvailableShippingOptions($order);

        return $order;
    }

    /**
     * الحصول على خيارات الشحن المتاحة
     */
    private function getAvailableShippingOptions($order) {
        $shipping_options = [];

        // البحث عن شركات الشحن التي تغطي منطقة العميل
        $query = $this->db->query("
            SELECT DISTINCT sc.*, sr.price, sr.cod_fee
            FROM cod_shipping_company sc
            LEFT JOIN cod_shipping_coverage scov ON (sc.company_id = scov.company_id)
            LEFT JOIN cod_shipping_rate sr ON (scov.coverage_id = sr.coverage_id)
            WHERE sc.status = 'active'
            AND (
                scov.country_id = '" . (int)$order['shipping_country_id'] . "'
                OR scov.zone_id = '" . (int)$order['shipping_zone_id'] . "'
                OR scov.city = '" . $this->db->escape($order['shipping_city']) . "'
            )
            AND sr.weight_from <= '" . (float)$order['total_weight'] . "'
            AND (sr.weight_to IS NULL OR sr.weight_to >= '" . (float)$order['total_weight'] . "')
            AND sr.effective_from <= CURDATE()
            AND (sr.effective_to IS NULL OR sr.effective_to >= CURDATE())
            ORDER BY scov.priority, sr.price
        ");

        foreach ($query->rows as $option) {
            $shipping_cost = $this->calculateShippingCost($option, $order);
            $cod_fee = $this->calculateCODFee($option, $order);

            $shipping_options[] = [
                'company_id' => $option['company_id'],
                'company_name' => $option['name'],
                'company_code' => $option['code'],
                'shipping_cost' => $shipping_cost,
                'cod_fee' => $cod_fee,
                'total_cost' => $shipping_cost + $cod_fee,
                'estimated_delivery' => $this->getEstimatedDelivery($option['company_id'])
            ];
        }

        return $shipping_options;
    }

    /**
     * حساب تكلفة الشحن
     */
    private function calculateShippingCost($shipping_option, $order) {
        $base_cost = $shipping_option['price'];

        if ($shipping_option['price_type'] == 'per_kg') {
            return $base_cost * $order['total_weight'];
        } elseif ($shipping_option['price_type'] == 'percentage') {
            return $order['total'] * ($base_cost / 100);
        }

        return $base_cost;
    }

    /**
     * حساب رسوم الدفع عند الاستلام
     */
    private function calculateCODFee($shipping_option, $order) {
        if ($order['payment_method'] != 'cod' || !$shipping_option['cod_fee']) {
            return 0;
        }

        if ($shipping_option['cod_fee_type'] == 'percentage') {
            return $order['total'] * ($shipping_option['cod_fee'] / 100);
        }

        return $shipping_option['cod_fee'];
    }

    /**
     * تجهيز الطلب (Picking & Packing)
     */
    public function fulfillOrder($order_id, $fulfillment_data) {
        // التحقق من صحة البيانات
        if (!$this->validateFulfillmentData($order_id, $fulfillment_data)) {
            throw new Exception('بيانات التجهيز غير صحيحة');
        }

        // بدء المعاملة
        $this->db->query("START TRANSACTION");

        try {
            // حجز المخزون
            $this->reserveInventory($order_id, $fulfillment_data['products']);

            // إنشاء سجل التجهيز
            $fulfillment_id = $this->createFulfillmentRecord($order_id, $fulfillment_data);

            // تحديث حالة الطلب
            $this->updateOrderStatus($order_id, 'fulfilled');

            // إنشاء أمر الشحن إذا تم تحديد شركة الشحن
            if (!empty($fulfillment_data['shipping_company_id'])) {
                $shipping_order_id = $this->createShippingOrder($order_id, $fulfillment_data);

                // ربط أمر الشحن بسجل التجهيز
                $this->db->query("
                    UPDATE cod_order_fulfillment SET
                    shipping_order_id = '" . (int)$shipping_order_id . "'
                    WHERE fulfillment_id = '" . (int)$fulfillment_id . "'
                ");
            }

            // إنشاء القيد المحاسبي لتكلفة الشحن
            if (!empty($fulfillment_data['shipping_cost'])) {
                $this->createShippingCostEntry($order_id, $fulfillment_data);
            }

            // إرسال إشعار للعميل
            $this->sendCustomerFulfillmentNotification($order_id, $fulfillment_id);

            // إرسال إشعار للفريق المسؤول
            $this->sendTeamFulfillmentNotification($order_id, $fulfillment_id);

            // إنشاء مستند التجهيز
            $this->createFulfillmentDocument($order_id, $fulfillment_id);

            // تحديث سير العمل
            $this->updateWorkflowStatus($order_id, 'fulfilled');

            // تأكيد المعاملة
            $this->db->query("COMMIT");

            return $fulfillment_id;

        } catch (Exception $e) {
            // إلغاء المعاملة
            $this->db->query("ROLLBACK");
            throw $e;
        }
    }

    /**
     * حجز المخزون
     */
    private function reserveInventory($order_id, $products) {
        foreach ($products as $product) {
            // التحقق من توفر المخزون
            $stock_query = $this->db->query("
                SELECT quantity FROM cod_product_inventory
                WHERE product_id = '" . (int)$product['product_id'] . "'
            ");

            if (!$stock_query->num_rows || $stock_query->row['quantity'] < $product['quantity']) {
                throw new Exception('المخزون غير كافي للمنتج: ' . $product['name']);
            }

            // تسجيل حركة الحجز
            $this->db->query("
                INSERT INTO cod_inventory_movement SET
                product_id = '" . (int)$product['product_id'] . "',
                movement_type = 'reserved',
                quantity = '" . (int)$product['quantity'] . "',
                reference_type = 'order',
                reference_id = '" . (int)$order_id . "',
                notes = 'حجز مخزون للطلب رقم " . $order_id . "',
                created_by = '" . (int)$this->user->getId() . "',
                created_at = NOW()
            ");
        }
    }

    /**
     * إنشاء سجل التجهيز
     */
    private function createFulfillmentRecord($order_id, $fulfillment_data) {
        $this->db->query("
            INSERT INTO cod_order_fulfillment SET
            order_id = '" . (int)$order_id . "',
            fulfillment_date = NOW(),
            package_weight = '" . (float)$fulfillment_data['package_weight'] . "',
            package_dimensions = '" . $this->db->escape($fulfillment_data['package_dimensions']) . "',
            packing_notes = '" . $this->db->escape($fulfillment_data['packing_notes']) . "',
            fulfilled_by = '" . (int)$this->user->getId() . "',
            status = 'completed',
            created_at = NOW()
        ");

        return $this->db->getLastId();
    }

    /**
     * إنشاء أمر الشحن
     */
    private function createShippingOrder($order_id, $fulfillment_data) {
        $this->load->model('shipping/shipping_integration');

        $shipping_data = [
            'order_id' => $order_id,
            'company_id' => $fulfillment_data['shipping_company_id'],
            'package_weight' => $fulfillment_data['package_weight'],
            'package_dimensions' => $fulfillment_data['package_dimensions'],
            'shipping_cost' => $fulfillment_data['shipping_cost'],
            'cod_amount' => $fulfillment_data['cod_amount'],
            'special_instructions' => $fulfillment_data['special_instructions']
        ];

        return $this->model_shipping_shipping_integration->createShippingOrder($shipping_data);
    }

    /**
     * إنشاء القيد المحاسبي لتكلفة الشحن
     */
    private function createShippingCostEntry($order_id, $fulfillment_data) {
        $this->load->model('accounts/journal');

        $journal_data = [
            'reference' => 'SHIP-' . $order_id,
            'description' => 'تكلفة شحن الطلب رقم ' . $order_id,
            'date' => date('Y-m-d'),
            'entries' => []
        ];

        // مدين: مصروف الشحن
        $journal_data['entries'][] = [
            'account_id' => $this->getAccountId('shipping_expense'),
            'debit' => $fulfillment_data['shipping_cost'],
            'credit' => 0,
            'description' => 'تكلفة شحن الطلب'
        ];

        // دائن: شركة الشحن مستحقة أو النقدية
        $journal_data['entries'][] = [
            'account_id' => $this->getAccountId('shipping_payable'),
            'debit' => 0,
            'credit' => $fulfillment_data['shipping_cost'],
            'description' => 'مستحق لشركة الشحن'
        ];

        return $this->model_accounts_journal->addJournalEntry($journal_data);
    }

    /**
     * تحديث حالة الطلب
     */
    private function updateOrderStatus($order_id, $status) {
        $status_mapping = [
            'fulfilled' => 16, // Fulfilled
            'shipped' => 17,   // Shipped
            'delivered' => 5   // Complete
        ];

        $status_id = isset($status_mapping[$status]) ? $status_mapping[$status] : 16;

        $this->db->query("
            UPDATE cod_order SET
            order_status_id = '" . (int)$status_id . "'
            WHERE order_id = '" . (int)$order_id . "'
        ");

        // إضافة سجل في تاريخ الطلب
        $this->db->query("
            INSERT INTO cod_order_history SET
            order_id = '" . (int)$order_id . "',
            order_status_id = '" . (int)$status_id . "',
            comment = 'تم تجهيز الطلب بواسطة: " . $this->user->getUserName() . "',
            date_added = NOW()
        ");
    }

    /**
     * التحقق من صحة بيانات التجهيز
     */
    private function validateFulfillmentData($order_id, $data) {
        // التحقق من وجود الطلب
        $order_query = $this->db->query("
            SELECT order_id FROM cod_order
            WHERE order_id = '" . (int)$order_id . "'
        ");

        if (!$order_query->num_rows) {
            return false;
        }

        // التحقق من البيانات المطلوبة
        if (empty($data['package_weight']) || empty($data['products'])) {
            return false;
        }

        return true;
    }

    /**
     * الحصول على معرف الحساب المحاسبي
     */
    private function getAccountId($account_key) {
        $account_mapping = [
            'shipping_expense' => 5101, // مصروف الشحن والتوصيل
            'shipping_payable' => 2101, // شركات الشحن مستحقة
            'inventory' => 1201,        // المخزون
            'cogs' => 5001             // تكلفة البضاعة المباعة
        ];

        return isset($account_mapping[$account_key]) ? $account_mapping[$account_key] : 0;
    }

    /**
     * الحصول على التاريخ المتوقع للتسليم
     */
    private function getEstimatedDelivery($company_id) {
        // يمكن تطوير هذه الدالة لحساب التاريخ المتوقع بناءً على شركة الشحن والمنطقة
        return date('Y-m-d', strtotime('+3 days'));
    }

    /**
     * إرسال إشعار للعميل عند التجهيز
     */
    private function sendCustomerFulfillmentNotification($order_id, $fulfillment_id) {
        // الحصول على بيانات الطلب والعميل
        $order_query = $this->db->query("
            SELECT o.*, CONCAT(o.firstname, ' ', o.lastname) as customer_name
            FROM cod_order o
            WHERE o.order_id = '" . (int)$order_id . "'
        ");

        if ($order_query->num_rows) {
            $order = $order_query->row;

            // إنشاء إشعار في النظام المركزي
            $this->db->query("
                INSERT INTO cod_unified_notification SET
                user_id = '" . (int)$order['customer_id'] . "',
                title = 'تم تجهيز طلبكم رقم " . $order_id . "',
                message = 'تم تجهيز طلبكم بنجاح وهو جاهز للشحن',
                type = 'order_fulfillment',
                priority = 'medium',
                reference_type = 'order',
                reference_id = '" . (int)$order_id . "',
                created_by = '" . (int)$this->user->getId() . "',
                created_at = NOW()
            ");

            // إرسال بريد إلكتروني للعميل
            $this->load->model('setting/mail');

            $subject = 'تم تجهيز طلبكم رقم ' . $order_id;
            $message = "عزيزي/عزيزتي " . $order['customer_name'] . ",\n\n";
            $message .= "نود إعلامكم بأنه تم تجهيز طلبكم رقم " . $order_id . " بنجاح.\n";
            $message .= "سيتم شحن الطلب قريباً وستصلكم رسالة أخرى برقم التتبع.\n\n";
            $message .= "شكراً لتسوقكم معنا.";

            $this->model_setting_mail->send($order['email'], $subject, $message);
        }
    }

    /**
     * إرسال إشعار للفريق المسؤول
     */
    private function sendTeamFulfillmentNotification($order_id, $fulfillment_id) {
        // الحصول على المستخدمين المسؤولين عن الشحن
        $users_query = $this->db->query("
            SELECT user_id FROM cod_user_group_permission
            WHERE permission = 'access' AND route = 'shipping/order_fulfillment'
        ");

        foreach ($users_query->rows as $user) {
            $this->db->query("
                INSERT INTO cod_unified_notification SET
                user_id = '" . (int)$user['user_id'] . "',
                title = 'تم تجهيز الطلب رقم " . $order_id . "',
                message = 'تم تجهيز الطلب بواسطة " . $this->user->getUserName() . " وهو جاهز للشحن',
                type = 'fulfillment_completed',
                priority = 'low',
                reference_type = 'order',
                reference_id = '" . (int)$order_id . "',
                created_by = '" . (int)$this->user->getId() . "',
                created_at = NOW()
            ");
        }
    }

    /**
     * إنشاء مستند التجهيز
     */
    private function createFulfillmentDocument($order_id, $fulfillment_id) {
        // إنشاء مستند في النظام المركزي
        $this->db->query("
            INSERT INTO cod_unified_document SET
            title = 'مستند تجهيز الطلب رقم " . $order_id . "',
            content = 'تم تجهيز الطلب بنجاح',
            document_type = 'fulfillment',
            reference_module = 'shipping',
            reference_id = '" . (int)$fulfillment_id . "',
            creator_id = '" . (int)$this->user->getId() . "',
            department_id = 1,
            status = 'active',
            created_at = NOW()
        ");

        $document_id = $this->db->getLastId();

        // ربط المستند بالطلب
        $this->db->query("
            UPDATE cod_order_fulfillment SET
            document_id = '" . (int)$document_id . "'
            WHERE fulfillment_id = '" . (int)$fulfillment_id . "'
        ");
    }

    /**
     * تحديث سير العمل
     */
    private function updateWorkflowStatus($order_id, $status) {
        // البحث عن سير العمل المرتبط بالطلب
        $workflow_query = $this->db->query("
            SELECT workflow_id FROM cod_unified_workflow
            WHERE reference_type = 'order' AND reference_id = '" . (int)$order_id . "'
        ");

        if ($workflow_query->num_rows) {
            $workflow_id = $workflow_query->row['workflow_id'];

            // تحديث حالة سير العمل
            $this->db->query("
                UPDATE cod_unified_workflow SET
                current_status = '" . $this->db->escape($status) . "',
                updated_at = NOW()
                WHERE workflow_id = '" . (int)$workflow_id . "'
            ");
        } else {
            // إنشاء سير عمل جديد للطلب
            $this->db->query("
                INSERT INTO cod_unified_workflow SET
                name = 'سير عمل الطلب رقم " . $order_id . "',
                description = 'سير عمل تجهيز وشحن الطلب',
                workflow_type = 'order_fulfillment',
                reference_type = 'order',
                reference_id = '" . (int)$order_id . "',
                current_status = '" . $this->db->escape($status) . "',
                creator_id = '" . (int)$this->user->getId() . "',
                department_id = 1,
                status = 'active',
                created_at = NOW()
            ");
        }
    }

    /**
     * حجز المخزون مع مراعاة الخيارات والوحدات
     */
    private function reserveInventoryWithOptions($order_id, $products) {
        foreach ($products as $product) {
            // التحقق من توفر المخزون للمنتج + الوحدة
            $stock_query = $this->db->query("
                SELECT quantity FROM cod_product_inventory
                WHERE product_id = '" . (int)$product['product_id'] . "'
                AND unit_id = '" . (int)$product['unit_id'] . "'
            ");

            $available_quantity = $stock_query->num_rows ? $stock_query->row['quantity'] : 0;

            // إذا كان المنتج له خيارات، التحقق من مخزون الخيارات
            if (!empty($product['options'])) {
                foreach ($product['options'] as $option) {
                    if ($option['option_subtract_stock']) {
                        $option_stock_query = $this->db->query("
                            SELECT quantity FROM cod_product_option_value
                            WHERE product_option_value_id = '" . (int)$option['product_option_value_id'] . "'
                        ");

                        if ($option_stock_query->num_rows) {
                            $option_quantity = $option_stock_query->row['quantity'];
                            if ($option_quantity < $product['quantity']) {
                                throw new Exception('المخزون غير كافي للخيار: ' . $option['option_value_name']);
                            }
                        }
                    }
                }
            }

            if ($available_quantity < $product['quantity']) {
                throw new Exception('المخزون غير كافي للمنتج: ' . $product['name']);
            }

            // تسجيل حركة الحجز
            $this->db->query("
                INSERT INTO cod_inventory_movement SET
                product_id = '" . (int)$product['product_id'] . "',
                unit_id = '" . (int)$product['unit_id'] . "',
                movement_type = 'reserved',
                quantity = '" . (int)$product['quantity'] . "',
                reference_type = 'order',
                reference_id = '" . (int)$order_id . "',
                notes = 'حجز مخزون للطلب رقم " . $order_id . "',
                created_by = '" . (int)$this->user->getId() . "',
                created_at = NOW()
            ");

            // تحديث مخزون الخيارات إذا لزم الأمر
            if (!empty($product['options'])) {
                foreach ($product['options'] as $option) {
                    if ($option['option_subtract_stock']) {
                        $this->db->query("
                            UPDATE cod_product_option_value SET
                            quantity = quantity - '" . (int)$product['quantity'] . "'
                            WHERE product_option_value_id = '" . (int)$option['product_option_value_id'] . "'
                        ");
                    }
                }
            }
        }
    }

    /**
     * الحصول على طرق الشحن المتاحة من OpenCart
     */
    public function getAvailableOpenCartShippingMethods($order) {
        $this->load->model('setting/extension');

        $shipping_methods = [];

        // الحصول على طرق الشحن المفعلة
        $extensions = $this->model_setting_extension->getExtensions('shipping');

        foreach ($extensions as $extension) {
            if ($this->config->get('shipping_' . $extension['code'] . '_status')) {
                $this->load->model('extension/shipping/' . $extension['code']);

                $method_class = 'model_extension_shipping_' . $extension['code'];

                if (method_exists($this->$method_class, 'getQuote')) {
                    $quote = $this->$method_class->getQuote($order);

                    if ($quote) {
                        $shipping_methods[] = [
                            'code' => $extension['code'],
                            'title' => $quote['title'],
                            'quote' => $quote
                        ];
                    }
                }
            }
        }

        return $shipping_methods;
    }

    /**
     * التكامل مع المناديب الداخليين
     */
    public function getInternalCouriers() {
        $query = $this->db->query("
            SELECT ic.*, u.firstname, u.lastname,
                CONCAT(u.firstname, ' ', u.lastname) as courier_name
            FROM cod_internal_courier ic
            LEFT JOIN cod_user u ON (ic.user_id = u.user_id)
            WHERE ic.status = 'active'
            ORDER BY ic.name
        ");

        return $query->rows;
    }
}
