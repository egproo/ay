<?php
class ModelPurchaseOrder extends Model {
    /**
     * إضافة أمر شراء جديد
     *
     * @param array $data بيانات أمر الشراء
     * @return int معرف أمر الشراء الجديد
     */
    public function addOrder($data) {
        // إنشاء رقم أمر الشراء الجديد
        $po_number = $this->generatePoNumber();

        $this->db->query("INSERT INTO " . DB_PREFIX . "purchase_order SET
            po_number = '" . $this->db->escape($po_number) . "',
            quotation_id = '" . (int)$data['quotation_id'] . "',
            requisition_id = '" . (int)$data['requisition_id'] . "',
            supplier_id = '" . (int)$data['supplier_id'] . "',
            currency_id = '" . (int)$data['currency_id'] . "',
            exchange_rate = '" . (float)$data['exchange_rate'] . "',
            order_date = '" . $this->db->escape($data['order_date']) . "',
            expected_delivery_date = " . ($data['expected_delivery_date'] ? "'" . $this->db->escape($data['expected_delivery_date']) . "'" : "NULL") . ",
            payment_terms = '" . $this->db->escape($data['payment_terms']) . "',
            delivery_terms = '" . $this->db->escape($data['delivery_terms']) . "',
            notes = '" . $this->db->escape($data['notes']) . "',
            tax_included = '" . (int)$data['tax_included'] . "',
            tax_rate = '" . (float)$data['tax_rate'] . "',
            subtotal = '" . (float)$data['subtotal'] . "',
            discount_type = '" . $this->db->escape($data['discount_type']) . "',
            has_discount = '" . (int)$data['has_discount'] . "',
            discount_value = '" . (float)$data['discount_value'] . "',
            discount_amount = '" . (float)$data['discount_amount'] . "',
            tax_amount = '" . (float)$data['tax_amount'] . "',
            total_amount = '" . (float)$data['total_amount'] . "',
            status = '" . $this->db->escape($data['status']) . "',
            reference_type = '" . $this->db->escape($data['reference_type']) . "',
            reference_id = '" . (int)$data['reference_id'] . "',
            source_type = '" . $this->db->escape($data['source_type']) . "',
            source_id = '" . (int)$data['source_id'] . "',
            created_at = NOW(),
            created_by = '" . (int)$data['user_id'] . "',
            updated_at = NOW(),
            updated_by = '" . (int)$data['user_id'] . "'");

        $po_id = $this->db->getLastId();

        // إضافة بنود أمر الشراء
        if (!empty($data['items'])) {
            foreach ($data['items'] as $item) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "purchase_order_item SET
                    po_id = '" . (int)$po_id . "',
                    quotation_item_id = '" . (int)$item['quotation_item_id'] . "',
                    product_id = '" . (int)$item['product_id'] . "',
                    quantity = '" . (float)$item['quantity'] . "',
                    unit_id = '" . (int)$item['unit_id'] . "',
                    unit_price = '" . (float)$item['unit_price'] . "',
                    tax_rate = '" . (float)$item['tax_rate'] . "',
                    discount_type = '" . $this->db->escape($item['discount_type']) . "',
                    discount_rate = '" . (float)$item['discount_rate'] . "',
                    total_price = '" . (float)$item['total_price'] . "',
                    description = '" . $this->db->escape($item['description']) . "',
                    original_unit_price = '" . (float)$item['original_unit_price'] . "',
                    exchange_rate = '" . (float)$data['exchange_rate'] . "',
                    is_received = '0',
                    received_quantity = '0'");
            }
        }

        // إضافة سجل تاريخ
        $this->addOrderHistory($po_id, $data['user_id'], 'create', $this->language->get('text_order_created'));

        return $po_id;
    }

    /**
     * تعديل أمر شراء موجود
     *
     * @param array $data بيانات أمر الشراء
     * @return bool نجاح العملية
     */
    public function editOrder($data) {
        $po_id = (int)$data['po_id'];

        // التحقق من وجود أمر الشراء
        $order_info = $this->getOrder($po_id);
        if (!$order_info) {
            return false;
        }

        // التحقق من الحالة
        if (!in_array($order_info['status'], ['draft', 'pending'])) {
            return false;
        }

        $this->db->query("UPDATE " . DB_PREFIX . "purchase_order SET
            quotation_id = '" . (int)$data['quotation_id'] . "',
            requisition_id = '" . (int)$data['requisition_id'] . "',
            supplier_id = '" . (int)$data['supplier_id'] . "',
            currency_id = '" . (int)$data['currency_id'] . "',
            exchange_rate = '" . (float)$data['exchange_rate'] . "',
            order_date = '" . $this->db->escape($data['order_date']) . "',
            expected_delivery_date = " . ($data['expected_delivery_date'] ? "'" . $this->db->escape($data['expected_delivery_date']) . "'" : "NULL") . ",
            payment_terms = '" . $this->db->escape($data['payment_terms']) . "',
            delivery_terms = '" . $this->db->escape($data['delivery_terms']) . "',
            notes = '" . $this->db->escape($data['notes']) . "',
            tax_included = '" . (int)$data['tax_included'] . "',
            tax_rate = '" . (float)$data['tax_rate'] . "',
            subtotal = '" . (float)$data['subtotal'] . "',
            discount_type = '" . $this->db->escape($data['discount_type']) . "',
            has_discount = '" . (int)$data['has_discount'] . "',
            discount_value = '" . (float)$data['discount_value'] . "',
            discount_amount = '" . (float)$data['discount_amount'] . "',
            tax_amount = '" . (float)$data['tax_amount'] . "',
            total_amount = '" . (float)$data['total_amount'] . "',
            status = '" . $this->db->escape($data['status']) . "',
            reference_type = '" . $this->db->escape($data['reference_type']) . "',
            reference_id = '" . (int)$data['reference_id'] . "',
            source_type = '" . $this->db->escape($data['source_type']) . "',
            source_id = '" . (int)$data['source_id'] . "',
            updated_at = NOW(),
            updated_by = '" . (int)$data['user_id'] . "'
            WHERE po_id = '" . (int)$po_id . "'");

        // حذف البنود الموجودة
        $this->db->query("DELETE FROM " . DB_PREFIX . "purchase_order_item WHERE po_id = '" . (int)$po_id . "'");

        // إعادة إضافة البنود
        if (!empty($data['items'])) {
            foreach ($data['items'] as $item) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "purchase_order_item SET
                    po_id = '" . (int)$po_id . "',
                    quotation_item_id = '" . (int)$item['quotation_item_id'] . "',
                    product_id = '" . (int)$item['product_id'] . "',
                    quantity = '" . (float)$item['quantity'] . "',
                    unit_id = '" . (int)$item['unit_id'] . "',
                    unit_price = '" . (float)$item['unit_price'] . "',
                    tax_rate = '" . (float)$item['tax_rate'] . "',
                    discount_type = '" . $this->db->escape($item['discount_type']) . "',
                    discount_rate = '" . (float)$item['discount_rate'] . "',
                    total_price = '" . (float)$item['total_price'] . "',
                    description = '" . $this->db->escape($item['description']) . "',
                    original_unit_price = '" . (float)$item['original_unit_price'] . "',
                    exchange_rate = '" . (float)$data['exchange_rate'] . "',
                    is_received = '0',
                    received_quantity = '0'");
            }
        }

        // إضافة سجل تاريخ
        $this->addOrderHistory($po_id, $data['user_id'], 'edit', $this->language->get('text_order_edited'));

        return true;
    }

    /**
     * اعتماد أمر شراء
     *
     * @param int $po_id معرف أمر الشراء
     * @param int $user_id معرف المستخدم
     * @return array نتيجة العملية
     */
    public function approveOrder($po_id, $user_id) {
        // التحقق من وجود أمر الشراء
        $order_info = $this->getOrder($po_id);
        if (!$order_info) {
            return array('error' => 'Order not found');
        }

        // التحقق من الحالة
        if ($order_info['status'] != 'pending') {
            return array('error' => 'Order cannot be approved in current status');
        }

        $this->db->query("UPDATE " . DB_PREFIX . "purchase_order SET
            status = 'approved',
            updated_at = NOW(),
            updated_by = '" . (int)$user_id . "'
            WHERE po_id = '" . (int)$po_id . "'");

        // إضافة سجل تاريخ
        $this->addOrderHistory($po_id, $user_id, 'approve', $this->language->get('text_order_approved'));

        return array('success' => true);
    }

    /**
     * رفض أمر شراء
     *
     * @param int $po_id معرف أمر الشراء
     * @param int $user_id معرف المستخدم
     * @param string $reason سبب الرفض
     * @return array نتيجة العملية
     */
    public function rejectOrder($po_id, $user_id, $reason = '') {
        // التحقق من وجود أمر الشراء
        $order_info = $this->getOrder($po_id);
        if (!$order_info) {
            return array('error' => 'Order not found');
        }

        // التحقق من الحالة
        if ($order_info['status'] != 'pending') {
            return array('error' => 'Order cannot be rejected in current status');
        }

        $this->db->query("UPDATE " . DB_PREFIX . "purchase_order SET
            status = 'rejected',
            updated_at = NOW(),
            updated_by = '" . (int)$user_id . "'
            WHERE po_id = '" . (int)$po_id . "'");

        // إضافة سجل تاريخ
        $description = $this->language->get('text_order_rejected');
        if ($reason) {
            $description .= ': ' . $reason;
        }
        $this->addOrderHistory($po_id, $user_id, 'reject', $description);

        return array('success' => true);
    }

    /**
     * حذف أمر شراء
     *
     * @param int $po_id معرف أمر الشراء
     * @return array نتيجة العملية
     */
    public function deleteOrder($po_id) {
        // التحقق من وجود أمر الشراء
        $order_info = $this->getOrder($po_id);
        if (!$order_info) {
            return array('error' => 'Order not found');
        }

        // التحقق من الحالة
        if (!in_array($order_info['status'], ['draft', 'pending', 'rejected'])) {
            return array('error' => 'Cannot delete order in current status');
        }

        // التحقق من وجود استلامات مرتبطة
        $receipt_count = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "goods_receipt WHERE po_id = '" . (int)$po_id . "'")->row['total'];
        if ($receipt_count > 0) {
            return array('error' => 'Cannot delete order with existing receipts');
        }

        // حذف البنود
        $this->db->query("DELETE FROM " . DB_PREFIX . "purchase_order_item WHERE po_id = '" . (int)$po_id . "'");

        // حذف التاريخ
        $this->db->query("DELETE FROM " . DB_PREFIX . "purchase_order_history WHERE po_id = '" . (int)$po_id . "'");

        // حذف المستندات
        $this->db->query("DELETE FROM " . DB_PREFIX . "purchase_document WHERE reference_type = 'order' AND reference_id = '" . (int)$po_id . "'");

        // حذف أمر الشراء
        $this->db->query("DELETE FROM " . DB_PREFIX . "purchase_order WHERE po_id = '" . (int)$po_id . "'");

        return array('success' => true);
    }

    /**
     * إضافة سجل تاريخ لأمر الشراء
     *
     * @param int $po_id معرف أمر الشراء
     * @param int $user_id معرف المستخدم
     * @param string $action الإجراء المتخذ
     * @param string $description وصف الإجراء
     * @return int معرف السجل
     */
    public function addOrderHistory($po_id, $user_id, $action, $description = '') {
        $this->db->query("INSERT INTO " . DB_PREFIX . "purchase_order_history SET
            po_id = '" . (int)$po_id . "',
            user_id = '" . (int)$user_id . "',
            action = '" . $this->db->escape($action) . "',
            description = '" . $this->db->escape($description) . "',
            created_at = NOW()");

        return $this->db->getLastId();
    }

    /**
     * إضافة إذن استلام
     *
     * @param array $data بيانات الاستلام
     * @return int معرف إذن الاستلام
     */
    public function addGoodsReceipt($data) {
        // التحقق من وجود أمر الشراء وحالته
        $order_info = $this->getOrder($data['po_id']);
        if (!$order_info || !in_array($order_info['status'], ['approved', 'partially_received'])) {
            return false;
        }

        // إنشاء رقم إذن استلام جديد
        $receipt_number = $this->generateReceiptNumber();

        $this->db->query("INSERT INTO " . DB_PREFIX . "goods_receipt SET
            po_id = '" . (int)$data['po_id'] . "',
            receipt_number = '" . $this->db->escape($receipt_number) . "',
            branch_id = '" . (int)$data['branch_id'] . "',
            receipt_date = '" . $this->db->escape($data['receipt_date']) . "',
            status = 'received',
            notes = '" . $this->db->escape($data['notes']) . "',
            created_by = '" . (int)$data['created_by'] . "',
            created_at = NOW(),
            updated_at = NOW(),
            invoice_number = " . (!empty($data['invoice_number']) ? "'" . $this->db->escape($data['invoice_number']) . "'" : "NULL") . ",
            invoice_date = " . (!empty($data['invoice_date']) ? "'" . $this->db->escape($data['invoice_date']) . "'" : "NULL") . ",
            invoice_amount = " . (!empty($data['invoice_amount']) ? "'" . (float)$data['invoice_amount'] . "'" : "NULL") . ",
            currency_id = " . (!empty($data['currency_id']) ? "'" . (int)$data['currency_id'] . "'" : "NULL") . ",
            exchange_rate = " . (!empty($data['exchange_rate']) ? "'" . (float)$data['exchange_rate'] . "'" : "NULL") . ",
            quality_check_required = '" . (int)$data['quality_check_required'] . "',
            matching_status = 'pending'");

        $receipt_id = $this->db->getLastId();

        // نسبة الاستلام الإجمالية
        $total_ordered_qty = 0;
        $total_received_qty = 0;

        // أضف بنود إذن الاستلام
        foreach ($data['items'] as $item) {
            if ((float)$item['quantity_received'] <= 0) {
                continue; // تخطي البنود بكمية صفر أو سالبة
            }

            // الحصول على تفاصيل البند في أمر الشراء
            $po_item_info = $this->getPurchaseOrderItem($item['po_item_id']);
            if (!$po_item_info) {
                continue;
            }

            // إضافة البند إلى إذن الاستلام
            $this->db->query("INSERT INTO " . DB_PREFIX . "goods_receipt_item SET
                goods_receipt_id = '" . (int)$receipt_id . "',
                po_item_id = '" . (int)$item['po_item_id'] . "',
                product_id = '" . (int)$item['product_id'] . "',
                quantity_received = '" . (float)$item['quantity_received'] . "',
                unit_id = '" . (int)$item['unit_id'] . "',
                quality_result = 'pending',
                remarks = '" . $this->db->escape($item['remarks']) . "',
                invoice_unit_price = " . (isset($item['invoice_unit_price']) ? "'" . (float)$item['invoice_unit_price'] . "'" : "NULL")
            );

            // تحديث الكمية المستلمة في بند أمر الشراء
            $new_received_qty = $po_item_info['received_quantity'] + (float)$item['quantity_received'];
            $is_fully_received = ($new_received_qty >= $po_item_info['quantity']) ? 1 : 0;

            $this->db->query("UPDATE " . DB_PREFIX . "purchase_order_item SET
                received_quantity = '" . (float)$new_received_qty . "',
                is_received = '" . $is_fully_received . "'
                WHERE po_item_id = '" . (int)$item['po_item_id'] . "'");

            // إضافة إلى المجموع الكلي
            $total_ordered_qty += $po_item_info['quantity'];
            $total_received_qty += $new_received_qty;

            // تحديث المخزون وحساب التكلفة المتوسطة
            $this->updateInventory($item['product_id'], $item['unit_id'], $data['branch_id'], $item['quantity_received'], $po_item_info['unit_price'], $receipt_id);
        }

        // تحديث حالة أمر الشراء
        $order_status = 'approved';
        if ($total_received_qty >= $total_ordered_qty) {
            $order_status = 'fully_received';
        } else if ($total_received_qty > 0) {
            $order_status = 'partially_received';
        }

        $this->db->query("UPDATE " . DB_PREFIX . "purchase_order SET
            status = '" . $order_status . "',
            updated_at = NOW(),
            updated_by = '" . (int)$data['created_by'] . "'
            WHERE po_id = '" . (int)$data['po_id'] . "'");

        // إضافة سجل تاريخ
        $this->addOrderHistory($data['po_id'], $data['created_by'], 'receipt', sprintf($this->language->get('text_receipt_added'), $receipt_number));

        // إنشاء قيد محاسبي
        if ($this->config->get('config_auto_journal') && $data['items']) {
            $journal_id = $this->createGoodsReceiptJournal($receipt_id, $data);

            if ($journal_id) {
                $this->db->query("UPDATE " . DB_PREFIX . "goods_receipt SET journal_id = '" . (int)$journal_id . "' WHERE goods_receipt_id = '" . (int)$receipt_id . "'");
            }
        }

        return $receipt_id;
    }

    /**
     * تحديث المخزون وحساب المتوسط المرجح للتكلفة
     *
     * @param int $product_id معرف المنتج
     * @param int $unit_id معرف الوحدة
     * @param int $branch_id معرف الفرع
     * @param float $quantity الكمية
     * @param float $unit_cost تكلفة الوحدة
     * @param int $reference_id معرف المرجع (استلام البضائع)
     * @return bool نجاح العملية
     */
    protected function updateInventory($product_id, $unit_id, $branch_id, $quantity, $unit_cost, $reference_id) {
        // الحصول على معلومات المخزون الحالية
        $inventory_info = $this->getInventoryInfo($product_id, $unit_id, $branch_id);

        // إضافة حركة المخزون
        $this->db->query("INSERT INTO " . DB_PREFIX . "product_movement SET
            product_id = '" . (int)$product_id . "',
            type = 'receipt',
            movement_reference_type = 'goods_receipt',
            movement_reference_id = '" . (int)$reference_id . "',
            date_added = NOW(),
            quantity = '" . (float)$quantity . "',
            unit_cost = '" . (float)$unit_cost . "',
            unit_id = '" . (int)$unit_id . "',
            old_average_cost = '" . ($inventory_info ? (float)$inventory_info['average_cost'] : 0) . "',
            effect_on_cost = 'increase'");

        $movement_id = $this->db->getLastId();

        // الكمية الجديدة
        $new_quantity = $inventory_info ? $inventory_info['quantity'] + $quantity : $quantity;

        // حساب التكلفة المتوسطة الجديدة
        if ($inventory_info && $inventory_info['quantity'] > 0) {
            $current_total = $inventory_info['quantity'] * $inventory_info['average_cost'];
            $new_total = $quantity * $unit_cost;
            $new_average_cost = ($current_total + $new_total) / $new_quantity;
        } else {
            $new_average_cost = $unit_cost;
        }

        // إذا كان المخزون موجوداً، قم بتحديثه
        if ($inventory_info) {
            $this->db->query("UPDATE " . DB_PREFIX . "product_inventory SET
                quantity = '" . (float)$new_quantity . "',
                quantity_available = '" . (float)$new_quantity . "',
                average_cost = '" . (float)$new_average_cost . "'
                WHERE product_inventory_id = '" . (int)$inventory_info['product_inventory_id'] . "'");
        } else {
            // إذا لم يكن موجوداً، قم بإنشائه
            $this->db->query("INSERT INTO " . DB_PREFIX . "product_inventory SET
                product_id = '" . (int)$product_id . "',
                branch_id = '" . (int)$branch_id . "',
                unit_id = '" . (int)$unit_id . "',
                quantity = '" . (float)$quantity . "',
                quantity_available = '" . (float)$quantity . "',
                is_consignment = '0',
                average_cost = '" . (float)$unit_cost . "'");
        }

        // تحديث تكلفة المتوسط المرجح
        $this->db->query("UPDATE " . DB_PREFIX . "product_movement SET
            new_average_cost = '" . (float)$new_average_cost . "'
            WHERE product_movement_id = '" . (int)$movement_id . "'");

        // تسجيل تحديث التكلفة
        $this->db->query("INSERT INTO " . DB_PREFIX . "inventory_cost_update SET
            product_id = '" . (int)$product_id . "',
            branch_id = '" . (int)$branch_id . "',
            old_cost = '" . ($inventory_info ? (float)$inventory_info['average_cost'] : 0) . "',
            new_cost = '" . (float)$new_average_cost . "',
            update_date = NOW(),
            source_type = 'receipt',
            source_id = '" . (int)$reference_id . "',
            notes = 'Automatic cost update from goods receipt',
            created_by = '1'");

        // تحديث تقييم المخزون
        $this->db->query("INSERT INTO " . DB_PREFIX . "inventory_valuation SET
            product_id = '" . (int)$product_id . "',
            unit_id = '" . (int)$unit_id . "',
            valuation_date = NOW(),
            average_cost = '" . (float)$new_average_cost . "',
            quantity = '" . (float)$new_quantity . "',
            total_value = '" . (float)($new_quantity * $new_average_cost) . "',
            transaction_reference_id = '" . (int)$reference_id . "',
            transaction_type = 'receipt',
            previous_quantity = '" . ($inventory_info ? (float)$inventory_info['quantity'] : 0) . "',
            previous_cost = '" . ($inventory_info ? (float)$inventory_info['average_cost'] : 0) . "',
            movement_quantity = '" . (float)$quantity . "',
            movement_cost = '" . (float)$unit_cost . "',
            date_added = NOW()");

        return true;
    }

    /**
     * الحصول على معلومات المخزون
     *
     * @param int $product_id معرف المنتج
     * @param int $unit_id معرف الوحدة
     * @param int $branch_id معرف الفرع
     * @return array|false معلومات المخزون أو false إذا لم يكن موجوداً
     */
    protected function getInventoryInfo($product_id, $unit_id, $branch_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_inventory
            WHERE product_id = '" . (int)$product_id . "'
            AND unit_id = '" . (int)$unit_id . "'
            AND branch_id = '" . (int)$branch_id . "'");

        return $query->num_rows ? $query->row : false;
    }

    /**
     * إنشاء قيد محاسبي لاستلام البضائع
     *
     * @param int $receipt_id معرف إذن الاستلام
     * @param array $data بيانات الاستلام
     * @return int|false معرف القيد المحاسبي أو false إذا فشلت العملية
     */
    protected function createGoodsReceiptJournal($receipt_id, $data) {
        // الحصول على معلومات إذن الاستلام
        $receipt_info = $this->getGoodsReceipt($receipt_id);
        if (!$receipt_info) {
            return false;
        }

        // الحصول على معلومات أمر الشراء
        $order_info = $this->getOrder($receipt_info['po_id']);
        if (!$order_info) {
            return false;
        }

        // الحصول على بنود إذن الاستلام
        $receipt_items = $this->getGoodsReceiptItems($receipt_id);
        if (empty($receipt_items)) {
            return false;
        }

        // حساب إجمالي قيمة البضائع المستلمة
        $total_value = 0;
        foreach ($receipt_items as $item) {
            $item_value = $item['quantity_received'] * $item['unit_price'];
            $total_value += $item_value;
        }

        // إنشاء القيد المحاسبي
        $journal_data = [
            'refnum' => $receipt_info['receipt_number'],
            'thedate' => $receipt_info['receipt_date'],
            'description' => sprintf($this->language->get('text_journal_goods_receipt'), $receipt_info['receipt_number'], $order_info['po_number']),
            'added_by' => $data['created_by'],
            'entries' => [
                // مدين: المخزون
                [
                    'account_code' => $this->config->get('config_inventory_account'),
                    'is_debit' => 1,
                    'amount' => $total_value
                ],
                // دائن: مستحق للموردين (حساب تعليق)
                [
                    'account_code' => $this->config->get('config_vendors_suspense_account'),
                    'is_debit' => 0,
                    'amount' => $total_value
                ]
            ]
        ];

        // إنشاء القيد
        $this->load->model('accounting/journal');
        $journal_id = $this->model_accounting_journal->addJournal($journal_data);

        return $journal_id;
    }

    /**
     * إضافة مطابقة ثلاثية
     *
     * @param array $data بيانات المطابقة
     * @return bool نجاح العملية
     */
    public function saveMatching($data) {
        $po_id = (int)$data['po_id'];

        // التحقق من وجود أمر الشراء
        $order_info = $this->getOrder($po_id);
        if (!$order_info) {
            return false;
        }

        // التحقق من إذا كانت هناك مطابقة سابقة
        $existing_match = $this->getMatchingByPO($po_id);
        if ($existing_match) {
            // حذف المطابقة السابقة
            $this->db->query("DELETE FROM " . DB_PREFIX . "purchase_matching_item WHERE matching_id = '" . (int)$existing_match['matching_id'] . "'");
            $this->db->query("DELETE FROM " . DB_PREFIX . "purchase_matching WHERE matching_id = '" . (int)$existing_match['matching_id'] . "'");
        }

        // الحصول على معلومات الاستلام والفاتورة المرتبطة
        $receipt_query = $this->db->query("SELECT goods_receipt_id FROM " . DB_PREFIX . "goods_receipt WHERE po_id = '" . (int)$po_id . "' ORDER BY created_at DESC LIMIT 1");
        $receipt_id = $receipt_query->num_rows ? $receipt_query->row['goods_receipt_id'] : null;

        $invoice_query = $this->db->query("SELECT invoice_id FROM " . DB_PREFIX . "supplier_invoice WHERE po_id = '" . (int)$po_id . "' ORDER BY created_at DESC LIMIT 1");
        $invoice_id = $invoice_query->num_rows ? $invoice_query->row['invoice_id'] : null;

        // تحديد حالة المطابقة
        $matching_status = 'pending';
        if ($receipt_id && $invoice_id) {
            $matching_status = 'matched';

            // نتحقق من وجود تباينات
            if (!empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    if (!empty($item['variance_amount']) && $item['variance_amount'] != 0) {
                        $matching_status = 'mismatch';
                        break;
                    }
                }
            }
        } else if ($receipt_id || $invoice_id) {
            $matching_status = 'partial';
        }

        // إنشاء سجل المطابقة
        $this->db->query("INSERT INTO " . DB_PREFIX . "purchase_matching SET
            po_id = '" . (int)$po_id . "',
            receipt_id = " . ($receipt_id ? "'" . (int)$receipt_id . "'" : "NULL") . ",
            invoice_id = " . ($invoice_id ? "'" . (int)$invoice_id . "'" : "NULL") . ",
            status = '" . $this->db->escape($matching_status) . "',
            matched_by = '" . (int)$data['matched_by'] . "',
            matched_at = NOW(),
            notes = '" . $this->db->escape($data['notes']) . "',
            created_at = NOW()");

        $matching_id = $this->db->getLastId();

        // إضافة بنود المطابقة
        if (!empty($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $po_item_id => $item) {
                // الحصول على معلومات بند أمر الشراء
                $po_item_info = $this->getPurchaseOrderItem($po_item_id);
                if (!$po_item_info) {
                    continue;
                }

                // الحصول على معلومات بند الاستلام
                $receipt_item_id = null;
                $quantity_received = 0;
                if ($receipt_id) {
                    $receipt_item_query = $this->db->query("SELECT receipt_item_id, quantity_received FROM " . DB_PREFIX . "goods_receipt_item WHERE goods_receipt_id = '" . (int)$receipt_id . "' AND po_item_id = '" . (int)$po_item_id . "' LIMIT 1");
                    if ($receipt_item_query->num_rows) {
                        $receipt_item_id = $receipt_item_query->row['receipt_item_id'];
                        $quantity_received = $receipt_item_query->row['quantity_received'];
                    }
                }

                // الحصول على معلومات بند الفاتورة
                $invoice_item_id = null;
                $quantity_invoiced = 0;
                $unit_price_invoiced = 0;
                if ($invoice_id) {
                    $invoice_item_query = $this->db->query("SELECT invoice_item_id, quantity, unit_price FROM " . DB_PREFIX . "supplier_invoice_item WHERE invoice_id = '" . (int)$invoice_id . "' AND po_item_id = '" . (int)$po_item_id . "' LIMIT 1");
                    if ($invoice_item_query->num_rows) {
                        $invoice_item_id = $invoice_item_query->row['invoice_item_id'];
                        $quantity_invoiced = $invoice_item_query->row['quantity'];
                        $unit_price_invoiced = $invoice_item_query->row['unit_price'];
                    }
                }

                // حساب التباين
                $variance_amount = 0;
                if ($invoice_item_id && $quantity_invoiced > 0 && $unit_price_invoiced > 0) {
                    $variance_amount = ($unit_price_invoiced - $po_item_info['unit_price']) * $quantity_invoiced;
                }

                // تحديد حالة المطابقة للبند
                $item_status = 'pending';
                if ($receipt_item_id && $invoice_item_id) {
                    $item_status = ($variance_amount == 0) ? 'matched' : 'mismatch';
                }

                // إضافة بند المطابقة
                $this->db->query("INSERT INTO " . DB_PREFIX . "purchase_matching_item SET
                    matching_id = '" . (int)$matching_id . "',
                    po_item_id = '" . (int)$po_item_id . "',
                    receipt_item_id = " . ($receipt_item_id ? "'" . (int)$receipt_item_id . "'" : "NULL") . ",
                    invoice_item_id = " . ($invoice_item_id ? "'" . (int)$invoice_item_id . "'" : "NULL") . ",
                    quantity_ordered = '" . (float)$po_item_info['quantity'] . "',
                    quantity_received = '" . (float)$quantity_received . "',
                    quantity_invoiced = '" . (float)$quantity_invoiced . "',
                    unit_price_ordered = '" . (float)$po_item_info['unit_price'] . "',
                    unit_price_invoiced = '" . (float)$unit_price_invoiced . "',
                    status = '" . $this->db->escape($item_status) . "',
                    variance_amount = '" . (float)$variance_amount . "',
                    variance_notes = '" . $this->db->escape($item['variance_notes'] ?? '') . "',
                    created_at = NOW()");
            }
        }

        // تحديث حالة المطابقة في إذن الاستلام إذا وجد
        if ($receipt_id) {
            $this->db->query("UPDATE " . DB_PREFIX . "goods_receipt SET matching_status = '" . $this->db->escape($matching_status) . "' WHERE goods_receipt_id = '" . (int)$receipt_id . "'");
        }

        // تحديث حالة المطابقة في فاتورة المورد إذا وجدت
        if ($invoice_id) {
            $this->db->query("UPDATE " . DB_PREFIX . "supplier_invoice SET matching_status = '" . $this->db->escape($matching_status) . "' WHERE invoice_id = '" . (int)$invoice_id . "'");
        }

        // إضافة سجل تاريخ
        $this->addOrderHistory($po_id, $data['matched_by'], 'match', sprintf($this->language->get('text_order_matched'), $matching_status));

        return true;
    }

    /**
     * الحصول على الإحصائيات
     *
     * @param array $filter فلاتر البحث
     * @return array الإحصائيات
     */
    public function getOrderStats($filter = array()) {
        $stats = array(
            'total' => 0,
            'draft' => 0,
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
            'received' => 0,
            'completed' => 0
        );

        // إجمالي عدد أوامر الشراء
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "purchase_order");
        $stats['total'] = $query->row['total'];

        // عدد أوامر الشراء حسب الحالة
        $query = $this->db->query("SELECT status, COUNT(*) AS count FROM " . DB_PREFIX . "purchase_order GROUP BY status");

        foreach ($query->rows as $row) {
            $stats[$row['status']] = $row['count'];
        }

        // مجموع مستلم (جزئي + كامل)
        $stats['received'] = ($stats['partially_received'] ?? 0) + ($stats['fully_received'] ?? 0);

        return $stats;
    }

    /**
     * الحصول على قائمة أوامر الشراء
     *
     * @param array $filter فلاتر البحث
     * @return array قائمة أوامر الشراء
     */
    public function getOrders($filter = array()) {
        $sql = "SELECT po.*, s.firstname, s.lastname, CONCAT(s.firstname, ' ', COALESCE(s.lastname, '')) AS supplier_name,
                c.code AS currency_code, q.quotation_number
                FROM " . DB_PREFIX . "purchase_order po
                LEFT JOIN " . DB_PREFIX . "supplier s ON (po.supplier_id = s.supplier_id)
                LEFT JOIN " . DB_PREFIX . "currency c ON (po.currency_id = c.currency_id)
                LEFT JOIN " . DB_PREFIX . "purchase_quotation q ON (po.quotation_id = q.quotation_id)
                WHERE 1 = 1";

        // تطبيق الفلاتر
        if (!empty($filter['filter_po_number'])) {
            $sql .= " AND po.po_id = '" . (int)$filter['filter_po_number'] . "'";
        }

        if (!empty($filter['filter_quotation_id'])) {
            $sql .= " AND po.quotation_id = '" . (int)$filter['filter_quotation_id'] . "'";
        }

        if (!empty($filter['filter_supplier_id'])) {
            $sql .= " AND po.supplier_id = '" . (int)$filter['filter_supplier_id'] . "'";
        }

if (!empty($filter['filter_status']) && $filter['filter_status'] != '*') {
            $sql .= " AND po.status = '" . $this->db->escape($filter['filter_status']) . "'";
        }

        if (!empty($filter['filter_date_start'])) {
            $sql .= " AND DATE(po.order_date) >= '" . $this->db->escape($filter['filter_date_start']) . "'";
        }

        if (!empty($filter['filter_date_end'])) {
            $sql .= " AND DATE(po.order_date) <= '" . $this->db->escape($filter['filter_date_end']) . "'";
        }

        $sort_data = array(
            'po.po_number',
            'supplier_name',
            'po.total_amount',
            'po.status',
            'po.order_date',
            'po.expected_delivery_date',
            'po.created_at'
        );

        if (isset($filter['sort']) && in_array($filter['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $filter['sort'];
        } else {
            $sql .= " ORDER BY po.created_at";
        }

        if (isset($filter['order']) && ($filter['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($filter['page']) || isset($filter['limit'])) {
            if (empty($filter['page'])) {
                $filter['page'] = 1;
            }

            if (empty($filter['limit'])) {
                $filter['limit'] = 20;
            }

            $start = ($filter['page'] - 1) * $filter['limit'];

            $sql .= " LIMIT " . (int)$start . "," . (int)$filter['limit'];
        }

        $query = $this->db->query($sql);

        // جلب عدد المستندات لكل أمر شراء
        $orders = array();
        foreach ($query->rows as $row) {
            $document_count = $this->getDocumentCount($row['po_id']);
            $row['document_count'] = $document_count;
            $orders[] = $row;
        }

        return $orders;
    }

    /**
     * الحصول على العدد الإجمالي لأوامر الشراء وفق الفلتر
     *
     * @param array $filter فلاتر البحث
     * @return int العدد الإجمالي
     */
    public function getTotalOrders($filter = array()) {
        $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "purchase_order po
                LEFT JOIN " . DB_PREFIX . "supplier s ON (po.supplier_id = s.supplier_id)
                LEFT JOIN " . DB_PREFIX . "currency c ON (po.currency_id = c.currency_id)
                LEFT JOIN " . DB_PREFIX . "purchase_quotation q ON (po.quotation_id = q.quotation_id)
                WHERE 1 = 1";

        // تطبيق الفلاتر
        if (!empty($filter['filter_po_number'])) {
            $sql .= " AND po.po_id = '" . (int)$filter['filter_po_number'] . "'";
        }

        if (!empty($filter['filter_quotation_id'])) {
            $sql .= " AND po.quotation_id = '" . (int)$filter['filter_quotation_id'] . "'";
        }

        if (!empty($filter['filter_supplier_id'])) {
            $sql .= " AND po.supplier_id = '" . (int)$filter['filter_supplier_id'] . "'";
        }

        if (!empty($filter['filter_status']) && $filter['filter_status'] != '*') {
            $sql .= " AND po.status = '" . $this->db->escape($filter['filter_status']) . "'";
        }

        if (!empty($filter['filter_date_start'])) {
            $sql .= " AND DATE(po.order_date) >= '" . $this->db->escape($filter['filter_date_start']) . "'";
        }

        if (!empty($filter['filter_date_end'])) {
            $sql .= " AND DATE(po.order_date) <= '" . $this->db->escape($filter['filter_date_end']) . "'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    /**
     * الحصول على بيانات أمر شراء
     *
     * @param int $po_id معرف أمر الشراء
     * @return array|false بيانات أمر الشراء أو false إذا لم يكن موجوداً
     */
    public function getOrder($po_id) {
        $query = $this->db->query("SELECT po.*, s.firstname, s.lastname, CONCAT(s.firstname, ' ', COALESCE(s.lastname, '')) AS supplier_name,
                c.code AS currency_code, q.quotation_number
                FROM " . DB_PREFIX . "purchase_order po
                LEFT JOIN " . DB_PREFIX . "supplier s ON (po.supplier_id = s.supplier_id)
                LEFT JOIN " . DB_PREFIX . "currency c ON (po.currency_id = c.currency_id)
                LEFT JOIN " . DB_PREFIX . "purchase_quotation q ON (po.quotation_id = q.quotation_id)
                WHERE po.po_id = '" . (int)$po_id . "'");

        return $query->num_rows ? $query->row : false;
    }

    /**
     * الحصول على بيانات بند أمر شراء
     *
     * @param int $po_item_id معرف بند أمر الشراء
     * @return array|false بيانات البند أو false إذا لم يكن موجوداً
     */
    public function getPurchaseOrderItem($po_item_id) {
        $query = $this->db->query("SELECT poi.*, p.*, pd.name AS product_name, u.code AS unit_code, u.desc_ar AS unit_name
                FROM " . DB_PREFIX . "purchase_order_item poi
                LEFT JOIN " . DB_PREFIX . "product p ON (poi.product_id = p.product_id)
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (poi.product_id = pd.product_id) AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
                LEFT JOIN " . DB_PREFIX . "unit u ON (poi.unit_id = u.unit_id)
                WHERE poi.po_item_id = '" . (int)$po_item_id . "'");

        return $query->num_rows ? $query->row : false;
    }

    /**
     * الحصول على بنود أمر شراء
     *
     * @param int $po_id معرف أمر الشراء
     * @return array بنود أمر الشراء
     */
    public function getOrderItems($po_id) {
        $query = $this->db->query("SELECT poi.*, p.*, pd.name AS product_name, u.code AS unit_code, u.desc_ar AS unit_name
                FROM " . DB_PREFIX . "purchase_order_item poi
                LEFT JOIN " . DB_PREFIX . "product p ON (poi.product_id = p.product_id)
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (poi.product_id = pd.product_id) AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
                LEFT JOIN " . DB_PREFIX . "unit u ON (poi.unit_id = u.unit_id)
                WHERE poi.po_id = '" . (int)$po_id . "'
                ORDER BY poi.po_item_id ASC");

        return $query->rows;
    }

    /**
     * الحصول على بنود أمر شراء مع معلومات الاستلام
     *
     * @param int $po_id معرف أمر الشراء
     * @return array بنود أمر الشراء مع معلومات الاستلام
     */
    public function getOrderItemsWithReceiptInfo($po_id) {
        $query = $this->db->query("SELECT poi.*, p.*, pd.name AS product_name, u.code AS unit_code, u.desc_ar AS unit_name,
                (poi.quantity - poi.received_quantity) AS remaining_quantity
                FROM " . DB_PREFIX . "purchase_order_item poi
                LEFT JOIN " . DB_PREFIX . "product p ON (poi.product_id = p.product_id)
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (poi.product_id = pd.product_id) AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
                LEFT JOIN " . DB_PREFIX . "unit u ON (poi.unit_id = u.unit_id)
                WHERE poi.po_id = '" . (int)$po_id . "'
                ORDER BY poi.po_item_id ASC");

        return $query->rows;
    }

    /**
     * الحصول على إشعارات استلام البضائع المرتبطة بأمر شراء
     *
     * @param int $po_id معرف أمر الشراء
     * @return array إشعارات الاستلام
     */
    public function getGoodsReceipts($po_id) {
        $query = $this->db->query("SELECT gr.*, b.name AS branch_name, CONCAT(u.firstname, ' ', u.lastname) AS created_by_name
                FROM " . DB_PREFIX . "goods_receipt gr
                LEFT JOIN " . DB_PREFIX . "branch b ON (gr.branch_id = b.branch_id)
                LEFT JOIN " . DB_PREFIX . "user u ON (gr.created_by = u.user_id)
                WHERE gr.po_id = '" . (int)$po_id . "'
                ORDER BY gr.created_at DESC");

        return $query->rows;
    }

    /**
     * الحصول على بيانات إشعار استلام
     *
     * @param int $receipt_id معرف إشعار الاستلام
     * @return array|false بيانات إشعار الاستلام أو false إذا لم يكن موجوداً
     */
    public function getGoodsReceipt($receipt_id) {
        $query = $this->db->query("SELECT gr.*, b.name AS branch_name, CONCAT(u.firstname, ' ', u.lastname) AS created_by_name
                FROM " . DB_PREFIX . "goods_receipt gr
                LEFT JOIN " . DB_PREFIX . "branch b ON (gr.branch_id = b.branch_id)
                LEFT JOIN " . DB_PREFIX . "user u ON (gr.created_by = u.user_id)
                WHERE gr.goods_receipt_id = '" . (int)$receipt_id . "'");

        return $query->num_rows ? $query->row : false;
    }

    /**
     * الحصول على بنود إشعار استلام
     *
     * @param int $receipt_id معرف إشعار الاستلام
     * @return array بنود إشعار الاستلام
     */
    public function getGoodsReceiptItems($receipt_id) {
        $query = $this->db->query("SELECT gri.*, p.*, pd.name AS product_name, u.code AS unit_code, u.desc_ar AS unit_name,
                poi.unit_price, poi.quantity AS ordered_quantity
                FROM " . DB_PREFIX . "goods_receipt_item gri
                LEFT JOIN " . DB_PREFIX . "purchase_order_item poi ON (gri.po_item_id = poi.po_item_id)
                LEFT JOIN " . DB_PREFIX . "product p ON (gri.product_id = p.product_id)
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (gri.product_id = pd.product_id) AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
                LEFT JOIN " . DB_PREFIX . "unit u ON (gri.unit_id = u.unit_id)
                WHERE gri.goods_receipt_id = '" . (int)$receipt_id . "'
                ORDER BY gri.receipt_item_id ASC");

        return $query->rows;
    }

    /**
     * الحصول على تاريخ أمر الشراء
     *
     * @param int $po_id معرف أمر الشراء
     * @return array سجلات تاريخ أمر الشراء
     */
    public function getOrderHistory($po_id) {
        $query = $this->db->query("SELECT poh.*, CONCAT(u.firstname, ' ', u.lastname) AS user_name
                FROM " . DB_PREFIX . "purchase_order_history poh
                LEFT JOIN " . DB_PREFIX . "user u ON (poh.user_id = u.user_id)
                WHERE poh.po_id = '" . (int)$po_id . "'
                ORDER BY poh.created_at DESC");

        return $query->rows;
    }

    /**
     * الحصول على بيانات المطابقة لأمر شراء
     *
     * @param int $po_id معرف أمر الشراء
     * @return array بيانات المطابقة
     */
    public function getMatchingData($po_id) {
        // الحصول على معلومات أمر الشراء
        $order_info = $this->getOrder($po_id);
        if (!$order_info) {
            return array();
        }

        // الحصول على قائمة الاستلامات
        $receipts = $this->getGoodsReceipts($po_id);

        // الحصول على قائمة فواتير الموردين
        $invoices = $this->getSupplierInvoices($po_id);

        // الحصول على بنود أمر الشراء
        $items = $this->getOrderItems($po_id);

        // إعداد بيانات المطابقة
        $data = array(
            'order' => $order_info,
            'receipts' => $receipts,
            'invoices' => $invoices,
            'items' => $items,
            'matching' => $this->getMatchingByPO($po_id)
        );

        // التفاصيل الدقيقة لكل بند
        if (!empty($items)) {
            foreach ($items as $key => $item) {
                // معلومات الاستلام
                $receipt_info = array();
                if (!empty($receipts)) {
                    foreach ($receipts as $receipt) {
                        $receipt_item = $this->getReceiptItemByPOItem($receipt['goods_receipt_id'], $item['po_item_id']);
                        if ($receipt_item) {
                            $receipt_info[] = $receipt_item;
                        }
                    }
                }

                // معلومات الفواتير
                $invoice_info = array();
                if (!empty($invoices)) {
                    foreach ($invoices as $invoice) {
                        $invoice_item = $this->getInvoiceItemByPOItem($invoice['invoice_id'], $item['po_item_id']);
                        if ($invoice_item) {
                            $invoice_info[] = $invoice_item;
                        }
                    }
                }

                // إضافة إلى بيانات البند
                $data['items'][$key]['receipt_info'] = $receipt_info;
                $data['items'][$key]['invoice_info'] = $invoice_info;
            }
        }

        return $data;
    }

    /**
     * الحصول على معلومات المطابقة لأمر شراء
     *
     * @param int $po_id معرف أمر الشراء
     * @return array|false بيانات المطابقة أو false إذا لم تكن موجودة
     */
    public function getMatchingByPO($po_id) {
        $query = $this->db->query("SELECT pm.*, CONCAT(u.firstname, ' ', u.lastname) AS matched_by_name
                FROM " . DB_PREFIX . "purchase_matching pm
                LEFT JOIN " . DB_PREFIX . "user u ON (pm.matched_by = u.user_id)
                WHERE pm.po_id = '" . (int)$po_id . "'
                ORDER BY pm.matched_at DESC
                LIMIT 1");

        return $query->num_rows ? $query->row : false;
    }

    /**
     * الحصول على قائمة فواتير الموردين المرتبطة بأمر شراء
     *
     * @param int $po_id معرف أمر الشراء
     * @return array قائمة فواتير الموردين
     */
    public function getSupplierInvoices($po_id) {
        $query = $this->db->query("SELECT si.*, CONCAT(s.firstname, ' ', COALESCE(s.lastname, '')) AS supplier_name,
                c.code AS currency_code
                FROM " . DB_PREFIX . "supplier_invoice si
                LEFT JOIN " . DB_PREFIX . "supplier s ON (si.vendor_id = s.supplier_id)
                LEFT JOIN " . DB_PREFIX . "currency c ON (si.currency_id = c.currency_id)
                WHERE si.po_id = '" . (int)$po_id . "'
                ORDER BY si.created_at DESC");

        return $query->rows;
    }

    /**
     * الحصول على بند إشعار استلام بناءً على بند أمر الشراء
     *
     * @param int $receipt_id معرف إشعار الاستلام
     * @param int $po_item_id معرف بند أمر الشراء
     * @return array|false بيانات بند الاستلام أو false إذا لم يكن موجوداً
     */
    public function getReceiptItemByPOItem($receipt_id, $po_item_id) {
        $query = $this->db->query("SELECT gri.*, gr.receipt_date
                FROM " . DB_PREFIX . "goods_receipt_item gri
                LEFT JOIN " . DB_PREFIX . "goods_receipt gr ON (gri.goods_receipt_id = gr.goods_receipt_id)
                WHERE gri.goods_receipt_id = '" . (int)$receipt_id . "'
                AND gri.po_item_id = '" . (int)$po_item_id . "'");

        return $query->num_rows ? $query->row : false;
    }

    /**
     * الحصول على بند فاتورة المورد بناءً على بند أمر الشراء
     *
     * @param int $invoice_id معرف فاتورة المورد
     * @param int $po_item_id معرف بند أمر الشراء
     * @return array|false بيانات بند الفاتورة أو false إذا لم يكن موجوداً
     */
    public function getInvoiceItemByPOItem($invoice_id, $po_item_id) {
        $query = $this->db->query("SELECT sii.*, si.invoice_date
                FROM " . DB_PREFIX . "supplier_invoice_item sii
                LEFT JOIN " . DB_PREFIX . "supplier_invoice si ON (sii.invoice_id = si.invoice_id)
                WHERE sii.invoice_id = '" . (int)$invoice_id . "'
                AND sii.po_item_id = '" . (int)$po_item_id . "'");

        return $query->num_rows ? $query->row : false;
    }

    /**
     * الحصول على المستندات المرتبطة بأمر شراء
     *
     * @param int $po_id معرف أمر الشراء
     * @return array قائمة المستندات
     */
    public function getDocuments($po_id) {
        $query = $this->db->query("SELECT pd.*, CONCAT(u.firstname, ' ', u.lastname) AS uploaded_by_name
                FROM " . DB_PREFIX . "purchase_document pd
                LEFT JOIN " . DB_PREFIX . "user u ON (pd.uploaded_by = u.user_id)
                WHERE pd.reference_type = 'purchase_order'
                AND pd.reference_id = '" . (int)$po_id . "'
                ORDER BY pd.upload_date DESC");

        return $query->rows;
    }

    /**
     * الحصول على عدد المستندات المرتبطة بأمر شراء
     *
     * @param int $po_id معرف أمر الشراء
     * @return int عدد المستندات
     */
    public function getDocumentCount($po_id) {
        $query = $this->db->query("SELECT COUNT(*) AS total
                FROM " . DB_PREFIX . "purchase_document
                WHERE reference_type = 'purchase_order'
                AND reference_id = '" . (int)$po_id . "'");

        return $query->row['total'];
    }

    /**
     * الحصول على معلومات مستند
     *
     * @param int $document_id معرف المستند
     * @return array|false بيانات المستند أو false إذا لم يكن موجوداً
     */
    public function getDocument($document_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "purchase_document
                WHERE document_id = '" . (int)$document_id . "'");

        return $query->num_rows ? $query->row : false;
    }

    /**
     * تحميل مستند جديد
     *
     * @param int $po_id معرف أمر الشراء
     * @param array $file معلومات الملف المرفوع
     * @param string $document_type نوع المستند
     * @param int $user_id معرف المستخدم
     * @return array معلومات المستند المحمل
     * @throws Exception في حالة فشل التحميل
     */
    public function uploadDocument($po_id, $file, $document_type, $user_id) {
        // التحقق من وجود أمر الشراء
        $order_info = $this->getOrder($po_id);
        if (!$order_info) {
            throw new Exception($this->language->get('error_order_not_found'));
        }

        // التحقق من الملف
        if (!$file || !is_uploaded_file($file['tmp_name'])) {
            throw new Exception($this->language->get('error_file_upload'));
        }

        // الحصول على امتداد الملف
        $filename = basename($file['name']);
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        // التحقق من الامتداد
        $allowed_extensions = array('pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif', 'zip', 'rar');
        if (!in_array(strtolower($extension), $allowed_extensions)) {
            throw new Exception($this->language->get('error_file_type'));
        }

        // التحقق من حجم الملف (10MB كحد أقصى)
        if ($file['size'] > 10485760) { // 10MB in bytes
            throw new Exception($this->language->get('error_file_size'));
        }

        // إنشاء مسار الملف
        $upload_dir = DIR_UPLOAD . 'purchase_orders/';

        // التأكد من وجود المجلد
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // إنشاء اسم ملف فريد
        $unique_filename = uniqid('po_' . $po_id . '_') . '.' . $extension;
        $file_path = 'purchase_orders/' . $unique_filename;

        // نقل الملف
        if (!move_uploaded_file($file['tmp_name'], DIR_UPLOAD . $file_path)) {
            throw new Exception($this->language->get('error_file_move'));
        }

        // حفظ معلومات المستند في قاعدة البيانات
        $this->db->query("INSERT INTO " . DB_PREFIX . "purchase_document SET
            reference_type = 'purchase_order',
            reference_id = '" . (int)$po_id . "',
            document_name = '" . $this->db->escape($filename) . "',
            file_path = '" . $this->db->escape($file_path) . "',
            document_type = '" . $this->db->escape($document_type) . "',
            uploaded_by = '" . (int)$user_id . "',
            upload_date = NOW()");

        $document_id = $this->db->getLastId();

        // إضافة سجل تاريخ
        $this->addOrderHistory($po_id, $user_id, 'document_upload', sprintf($this->language->get('text_document_uploaded'), $filename));

        return array(
            'document_id' => $document_id,
            'document_name' => $filename,
            'file_path' => $file_path,
            'document_type' => $document_type,
            'upload_date' => date('Y-m-d H:i:s'),
            'uploaded_by' => $user_id,
            'uploaded_by_name' => $this->getUserName($user_id)
        );
    }

    /**
     * حذف مستند
     *
     * @param int $document_id معرف المستند
     * @return bool نجاح العملية
     */
    public function deleteDocument($document_id) {
        // الحصول على معلومات المستند
        $document_info = $this->getDocument($document_id);
        if (!$document_info) {
            return false;
        }

        // حذف الملف الفعلي
        if (file_exists(DIR_UPLOAD . $document_info['file_path'])) {
            unlink(DIR_UPLOAD . $document_info['file_path']);
        }

        // حذف السجل من قاعدة البيانات
        $this->db->query("DELETE FROM " . DB_PREFIX . "purchase_document WHERE document_id = '" . (int)$document_id . "'");

        // إضافة سجل تاريخ
        $this->addOrderHistory($document_info['reference_id'], $this->user->getId(), 'document_delete', sprintf($this->language->get('text_document_deleted'), $document_info['document_name']));

        return true;
    }

    /**
     * الحصول على المستندات مع الصلاحيات
     *
     * @param int $po_id معرف أمر الشراء
     * @return array معلومات المستندات مع الصلاحيات
     */
    public function getDocumentsWithPermissions($po_id) {
        // الحصول على قائمة المستندات
        $documents = $this->getDocuments($po_id);

        // إضافة الصلاحيات
        $can_delete = $this->user->hasPermission('delete', 'purchase/document');

        return array(
            'documents' => $documents,
            'can_delete' => $can_delete
        );
    }

    /**
     * تحديث تكاليف المنتجات عند الاستلام
     *
     * @param int $receipt_id معرف إشعار الاستلام
     * @return bool نجاح العملية
     */
    public function updateProductCosts($receipt_id) {
        // الحصول على معلومات الاستلام
        $receipt_info = $this->getGoodsReceipt($receipt_id);
        if (!$receipt_info) {
            return false;
        }

        // الحصول على بنود الاستلام
        $receipt_items = $this->getGoodsReceiptItems($receipt_id);
        if (empty($receipt_items)) {
            return false;
        }

        foreach ($receipt_items as $item) {
            // الحصول على معلومات بند أمر الشراء
            $po_item_info = $this->getPurchaseOrderItem($item['po_item_id']);
            if (!$po_item_info) {
                continue;
            }

            // تحديث تكلفة المنتج في الجدول الرئيسي
            $this->db->query("UPDATE " . DB_PREFIX . "product SET
                average_cost = '" . (float)$po_item_info['unit_price'] . "',
                date_modified = NOW()
                WHERE product_id = '" . (int)$item['product_id'] . "'");

            // تحديث المخزون مرة أخرى بقيمة التكلفة الجديدة
            $this->updateInventoryCost($item['product_id'], $item['unit_id'], $receipt_info['branch_id'], $po_item_info['unit_price'], $receipt_id);

            // تحديث حالة التكلفة في بند أمر الشراء
            $this->db->query("UPDATE " . DB_PREFIX . "purchase_order_item SET
                is_cost_updated = '1',
                cost_updated_at = NOW(),
                cost_updated_by = '" . (int)$this->user->getId() . "',
                average_cost = '" . (float)$po_item_info['unit_price'] . "'
                WHERE po_item_id = '" . (int)$item['po_item_id'] . "'");
        }

        // تحديث حالة أمر الشراء
        $this->db->query("UPDATE " . DB_PREFIX . "purchase_order SET
            is_cost_updated = '1',
            updated_at = NOW(),
            updated_by = '" . (int)$this->user->getId() . "'
            WHERE po_id = '" . (int)$receipt_info['po_id'] . "'");

        // إضافة سجل تاريخ
        $this->addOrderHistory($receipt_info['po_id'], $this->user->getId(), 'cost_update', $this->language->get('text_costs_updated'));

        return true;
    }

    /**
     * تحديث تكلفة المخزون
     *
     * @param int $product_id معرف المنتج
     * @param int $unit_id معرف الوحدة
     * @param int $branch_id معرف الفرع
     * @param float $new_cost التكلفة الجديدة
     * @param int $reference_id معرف المرجع
     * @return bool نجاح العملية
     */
    protected function updateInventoryCost($product_id, $unit_id, $branch_id, $new_cost, $reference_id) {
        // الحصول على معلومات المخزون الحالية
        $inventory_info = $this->getInventoryInfo($product_id, $unit_id, $branch_id);
        if (!$inventory_info) {
            return false;
        }

        // تحديث تكلفة المخزون
        $this->db->query("UPDATE " . DB_PREFIX . "product_inventory SET
            average_cost = '" . (float)$new_cost . "'
            WHERE product_inventory_id = '" . (int)$inventory_info['product_inventory_id'] . "'");

        // تسجيل تحديث التكلفة
        $this->db->query("INSERT INTO " . DB_PREFIX . "inventory_cost_update SET
            product_id = '" . (int)$product_id . "',
            branch_id = '" . (int)$branch_id . "',
            old_cost = '" . (float)$inventory_info['average_cost'] . "',
            new_cost = '" . (float)$new_cost . "',
            update_date = NOW(),
            source_type = 'cost_update',
            source_id = '" . (int)$reference_id . "',
            notes = 'Manual cost update from goods receipt',
            created_by = '" . (int)$this->user->getId() . "'");

        return true;
    }

    /**
     * البحث عن المنتجات
     *
     * @param string $query نص البحث
     * @return array قائمة المنتجات
     */
    public function searchProducts($query) {
        $sql = "SELECT p.product_id, pd.name, p.model, p.sku
                FROM " . DB_PREFIX . "product p
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
                WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
                AND (pd.name LIKE '%" . $this->db->escape($query) . "%'
                OR p.model LIKE '%" . $this->db->escape($query) . "%'
                OR p.sku LIKE '%" . $this->db->escape($query) . "%')
                AND p.status = '1'
                ORDER BY pd.name ASC
                LIMIT 15";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    /**
     * الحصول على تفاصيل منتج
     *
     * @param int $product_id معرف المنتج
     * @return array بيانات المنتج
     */
    public function getProductDetails($product_id) {
        // معلومات المنتج الأساسية
        $product_query = $this->db->query("SELECT p.*, pd.name AS product_name
                FROM " . DB_PREFIX . "product p
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
                WHERE p.product_id = '" . (int)$product_id . "'
                AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

        if (!$product_query->num_rows) {
            return array();
        }

        $product_info = $product_query->row;

        // وحدات القياس للمنتج
        $units_query = $this->db->query("SELECT pu.*, u.code AS unit_code, u.desc_ar AS unit_name
                FROM " . DB_PREFIX . "product_unit pu
                LEFT JOIN " . DB_PREFIX . "unit u ON (pu.unit_id = u.unit_id)
                WHERE pu.product_id = '" . (int)$product_id . "'
                ORDER BY pu.unit_type ASC, u.unit_id ASC");

        $units = array();

        if ($units_query->num_rows) {
            foreach ($units_query->rows as $unit) {
                // الحصول على معلومات المخزون لهذه الوحدة
                $inventory_query = $this->db->query("SELECT SUM(quantity) AS total_quantity, SUM(quantity_available) AS available_quantity, AVG(average_cost) AS average_cost
                        FROM " . DB_PREFIX . "product_inventory
                        WHERE product_id = '" . (int)$product_id . "'
                        AND unit_id = '" . (int)$unit['unit_id'] . "'");

                $unit['quantity'] = $inventory_query->row['total_quantity'] ?? 0;
                $unit['quantity_available'] = $inventory_query->row['available_quantity'] ?? 0;
                $unit['average_cost'] = $inventory_query->row['average_cost'] ?? 0;

                $units[] = $unit;
            }
        } else {
            // إذا لم تكن هناك وحدات محددة، استخدم الوحدة الافتراضية
            $default_unit_query = $this->db->query("SELECT u.unit_id, u.code AS unit_code, u.desc_ar AS unit_name
                    FROM " . DB_PREFIX . "unit u
                    WHERE u.unit_id = '37'"); // الوحدة الافتراضية (قطعة)

            if ($default_unit_query->num_rows) {
                $unit = $default_unit_query->row;
                $unit['unit_type'] = 'base';
                $unit['conversion_factor'] = 1;
                $unit['quantity'] = 0;
                $unit['quantity_available'] = 0;
                $unit['average_cost'] = 0;

                $units[] = $unit;
            }
        }

        return array(
            'product_id' => $product_info['product_id'],
            'product_name' => $product_info['product_name'],
            'model' => $product_info['model'],
            'sku' => $product_info['sku'],
            'average_cost' => $product_info['average_cost'],
            'tax_class_id' => $product_info['tax_class_id'],
            'units' => $units
        );
    }

    /**
     * الحصول على قائمة الموردين
     *
     * @return array قائمة الموردين
     */
    public function getSuppliers() {
        $query = $this->db->query("SELECT s.supplier_id, CONCAT(s.firstname, ' ', COALESCE(s.lastname, '')) AS name, s.email, s.telephone
                FROM " . DB_PREFIX . "supplier s
                WHERE s.status = '1'
                ORDER BY s.firstname ASC");

        return $query->rows;
    }

    /**
     * الحصول على معلومات مورد
     *
     * @param int $supplier_id معرف المورد
     * @return array|false بيانات المورد أو false إذا لم يكن موجوداً
     */
    public function getSupplier($supplier_id) {
        $query = $this->db->query("SELECT s.*, a.address_1, a.address_2, a.city, a.postcode, a.country_id, a.zone_id
                FROM " . DB_PREFIX . "supplier s
                LEFT JOIN " . DB_PREFIX . "supplier_address a ON (s.address_id = a.address_id)
                WHERE s.supplier_id = '" . (int)$supplier_id . "'");

        return $query->num_rows ? $query->row : false;
    }

    /**
     * الحصول على قائمة الفروع/المخازن
     *
     * @return array قائمة الفروع/المخازن
     */
    public function getBranches() {
        $query = $this->db->query("SELECT branch_id, name, type FROM " . DB_PREFIX . "branch ORDER BY name ASC");

        return $query->rows;
    }

    /**
     * الحصول على اسم المستخدم
     *
     * @param int $user_id معرف المستخدم
     * @return string اسم المستخدم
     */
    public function getUserName($user_id) {
        $query = $this->db->query("SELECT CONCAT(firstname, ' ', lastname) AS name FROM " . DB_PREFIX . "user WHERE user_id = '" . (int)$user_id . "'");

        return $query->num_rows ? $query->row['name'] : '';
    }

    /**
     * إنشاء رقم أمر شراء جديد
     *
     * @return string رقم أمر الشراء
     */
    protected function generatePoNumber() {
        // الحصول على التاريخ الحالي وتنسيقه
        $date_prefix = date('Ym');

        // البحث عن أعلى رقم حالي بنفس التاريخ
        $query = $this->db->query("SELECT MAX(CAST(SUBSTRING(po_number, 8) AS UNSIGNED)) AS max_number
                FROM " . DB_PREFIX . "purchase_order
                WHERE po_number LIKE 'PO" . $date_prefix . "%'");

        $max_number = $query->row['max_number'] ?? 0;

        // إنشاء الرقم التالي
        $next_number = $max_number + 1;

        // تنسيق الرقم مع الأصفار في البداية
        $formatted_number = str_pad($next_number, 4, '0', STR_PAD_LEFT);

        // تجميع الرقم الكامل
        $po_number = 'PO' . $date_prefix . $formatted_number;

        return $po_number;
    }

    /**
     * إنشاء رقم إذن استلام جديد
     *
     * @return string رقم إذن الاستلام
     */
    protected function generateReceiptNumber() {
        // الحصول على التاريخ الحالي وتنسيقه
        $date_prefix = date('Ym');

        // البحث عن أعلى رقم حالي بنفس التاريخ
        $query = $this->db->query("SELECT MAX(CAST(SUBSTRING(receipt_number, 7) AS UNSIGNED)) AS max_number
                FROM " . DB_PREFIX . "goods_receipt
                WHERE receipt_number LIKE 'GR" . $date_prefix . "%'");

        $max_number = $query->row['max_number'] ?? 0;

        // إنشاء الرقم التالي
        $next_number = $max_number + 1;

        // تنسيق الرقم مع الأصفار في البداية
        $formatted_number = str_pad($next_number, 4, '0', STR_PAD_LEFT);

        // تجميع الرقم الكامل
        $receipt_number = 'GR' . $date_prefix . $formatted_number;

        return $receipt_number;
    }

    /**
     * الحصول على نص الحالة
     *
     * @param string $status رمز الحالة
     * @return string نص الحالة
     */
    public function getStatusText($status) {
        $status_texts = array(
            'draft' => $this->language->get('text_status_draft'),
            'pending' => $this->language->get('text_status_pending'),
            'approved' => $this->language->get('text_status_approved'),
            'rejected' => $this->language->get('text_status_rejected'),
            'cancelled' => $this->language->get('text_status_cancelled'),
            'sent_to_vendor' => $this->language->get('text_status_sent_to_vendor'),
            'confirmed_by_vendor' => $this->language->get('text_status_confirmed_by_vendor'),
            'partially_received' => $this->language->get('text_status_partially_received'),
            'fully_received' => $this->language->get('text_status_fully_received'),
            'completed' => $this->language->get('text_status_completed')
        );

        return isset($status_texts[$status]) ? $status_texts[$status] : $status;
    }

    /**
     * الحصول على صنف CSS للحالة
     *
     * @param string $status رمز الحالة
     * @return string صنف CSS
     */
    public function getStatusClass($status) {
        $status_classes = array(
            'draft' => 'default',
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'cancelled' => 'danger',
            'sent_to_vendor' => 'info',
            'confirmed_by_vendor' => 'primary',
            'partially_received' => 'info',
            'fully_received' => 'primary',
            'completed' => 'success'
        );

        return isset($status_classes[$status]) ? $status_classes[$status] : 'default';
    }
}