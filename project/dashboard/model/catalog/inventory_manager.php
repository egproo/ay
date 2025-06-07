<?php
class ModelCatalogInventoryManager extends Model {
    /**
     * طبقة وسيطة لإدارة عمليات المخزون مع الحفاظ على الأثر المحاسبي
     * تضمن هذه الطبقة ترابط جميع عمليات المخزون وتسجيل الأثر المحاسبي بشكل صحيح
     */

    /**
     * تحديث كمية المخزون مع تسجيل الحركة والأثر المحاسبي
     *
     * @param int $product_id معرف المنتج
     * @param float $quantity الكمية (موجبة للإضافة، سالبة للخصم)
     * @param string $unit_id معرف الوحدة
     * @param int $warehouse_id معرف المستودع
     * @param string $reference_type نوع المرجع (purchase, sale, adjustment, etc.)
     * @param int $reference_id معرف المرجع
     * @param string $notes ملاحظات
     * @param float $cost التكلفة (اختياري - للمشتريات)
     * @return bool
     */
    public function updateStock($product_id, $quantity, $unit_id, $warehouse_id, $reference_type, $reference_id, $notes = '', $cost = null) {
        // التحقق من صحة البيانات
        if (!$this->validateStockUpdate($product_id, $quantity, $unit_id, $warehouse_id)) {
            return false;
        }

        // تحويل الكمية إلى الوحدة الأساسية إذا لزم الأمر
        $base_quantity = $this->convertToBaseUnit($product_id, $quantity, $unit_id);

        // الحصول على معلومات المخزون الحالية
        $current_stock = $this->getCurrentStock($product_id, $warehouse_id);
        $current_quantity = $current_stock['quantity'];
        $current_cost = $current_stock['cost'];

        // حساب الكمية والتكلفة الجديدة
        $new_quantity = $current_quantity + $base_quantity;

        // حساب متوسط التكلفة المرجح إذا كانت عملية إضافة مع تكلفة
        $new_cost = $current_cost;
        if ($base_quantity > 0 && $cost !== null) {
            // حساب متوسط التكلفة المرجح
            $new_cost = $this->calculateWeightedAverageCost($current_quantity, $current_cost, $base_quantity, $cost);
        }

        // تحديث المخزون
        $this->updateProductStock($product_id, $warehouse_id, $new_quantity, $new_cost);

        // تسجيل حركة المخزون
        $movement_id = $this->recordStockMovement($product_id, $base_quantity, $unit_id, $warehouse_id, $reference_type, $reference_id, $notes, $cost);

        // تسجيل الأثر المحاسبي
        $this->recordAccountingEffect($product_id, $base_quantity, $cost, $reference_type, $movement_id);

        return true;
    }

    /**
     * التحقق من صحة تحديث المخزون
     */
    private function validateStockUpdate($product_id, $quantity, $unit_id, $warehouse_id) {
        // التحقق من وجود المنتج
        $product_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product WHERE product_id = '" . (int)$product_id . "'");
        if ($product_query->num_rows == 0) {
            return false;
        }

        // التحقق من وجود الوحدة
        $unit_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "unit WHERE unit_id = '" . $this->db->escape($unit_id) . "'");
        if ($unit_query->num_rows == 0) {
            return false;
        }

        // التحقق من وجود المستودع
        $warehouse_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "warehouse WHERE warehouse_id = '" . (int)$warehouse_id . "'");
        if ($warehouse_query->num_rows == 0) {
            return false;
        }

        return true;
    }

    /**
     * تحويل الكمية إلى الوحدة الأساسية
     */
    private function convertToBaseUnit($product_id, $quantity, $unit_id) {
        // الحصول على الوحدة الأساسية للمنتج
        $product_query = $this->db->query("SELECT base_unit_id FROM " . DB_PREFIX . "product WHERE product_id = '" . (int)$product_id . "'");
        $base_unit_id = $product_query->row['base_unit_id'];

        // إذا كانت الوحدة هي نفسها الوحدة الأساسية، أعد الكمية كما هي
        if ($unit_id == $base_unit_id) {
            return $quantity;
        }

        // الحصول على معامل التحويل
        $conversion_query = $this->db->query("SELECT conversion_factor FROM " . DB_PREFIX . "unit_conversion
            WHERE from_unit_id = '" . $this->db->escape($unit_id) . "'
            AND to_unit_id = '" . $this->db->escape($base_unit_id) . "'");

        if ($conversion_query->num_rows > 0) {
            return $quantity * $conversion_query->row['conversion_factor'];
        }

        // محاولة العكس إذا لم يتم العثور على التحويل المباشر
        $reverse_conversion_query = $this->db->query("SELECT conversion_factor FROM " . DB_PREFIX . "unit_conversion
            WHERE from_unit_id = '" . $this->db->escape($base_unit_id) . "'
            AND to_unit_id = '" . $this->db->escape($unit_id) . "'");

        if ($reverse_conversion_query->num_rows > 0) {
            return $quantity / $reverse_conversion_query->row['conversion_factor'];
        }

        // إذا لم يتم العثور على تحويل، أعد الكمية كما هي (يجب تجنب هذه الحالة)
        return $quantity;
    }

    /**
     * الحصول على معلومات المخزون الحالية
     */
    private function getCurrentStock($product_id, $warehouse_id) {
        $query = $this->db->query("SELECT quantity, cost FROM " . DB_PREFIX . "product_warehouse
            WHERE product_id = '" . (int)$product_id . "'
            AND warehouse_id = '" . (int)$warehouse_id . "'");

        if ($query->num_rows > 0) {
            return [
                'quantity' => (float)$query->row['quantity'],
                'cost' => (float)$query->row['cost']
            ];
        } else {
            return [
                'quantity' => 0,
                'cost' => 0
            ];
        }
    }

    /**
     * حساب متوسط التكلفة المرجح
     */
    private function calculateWeightedAverageCost($current_quantity, $current_cost, $new_quantity, $new_cost) {
        // تجنب القسمة على صفر
        if ($current_quantity + $new_quantity == 0) {
            return $new_cost;
        }

        // حساب متوسط التكلفة المرجح
        $total_cost = ($current_quantity * $current_cost) + ($new_quantity * $new_cost);
        $total_quantity = $current_quantity + $new_quantity;

        return $total_cost / $total_quantity;
    }

    /**
     * تحديث مخزون المنتج
     */
    private function updateProductStock($product_id, $warehouse_id, $quantity, $cost) {
        // التحقق مما إذا كان المنتج موجودًا بالفعل في المستودع
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_warehouse
            WHERE product_id = '" . (int)$product_id . "'
            AND warehouse_id = '" . (int)$warehouse_id . "'");

        if ($query->num_rows > 0) {
            // تحديث السجل الموجود
            $this->db->query("UPDATE " . DB_PREFIX . "product_warehouse
                SET quantity = '" . (float)$quantity . "',
                    cost = '" . (float)$cost . "',
                    date_modified = NOW()
                WHERE product_id = '" . (int)$product_id . "'
                AND warehouse_id = '" . (int)$warehouse_id . "'");
        } else {
            // إنشاء سجل جديد
            $this->db->query("INSERT INTO " . DB_PREFIX . "product_warehouse
                SET product_id = '" . (int)$product_id . "',
                    warehouse_id = '" . (int)$warehouse_id . "',
                    quantity = '" . (float)$quantity . "',
                    cost = '" . (float)$cost . "',
                    date_added = NOW(),
                    date_modified = NOW()");
        }

        // تحديث إجمالي المخزون في جدول المنتج
        $this->updateTotalStock($product_id);
    }

    /**
     * تحديث إجمالي المخزون في جدول المنتج
     */
    private function updateTotalStock($product_id) {
        $query = $this->db->query("SELECT SUM(quantity) as total FROM " . DB_PREFIX . "product_warehouse
            WHERE product_id = '" . (int)$product_id . "'");

        $total_quantity = $query->row['total'];

        $this->db->query("UPDATE " . DB_PREFIX . "product
            SET quantity = '" . (float)$total_quantity . "',
                date_modified = NOW()
            WHERE product_id = '" . (int)$product_id . "'");
    }

    /**
     * تسجيل حركة المخزون
     */
    private function recordStockMovement($product_id, $quantity, $unit_id, $warehouse_id, $reference_type, $reference_id, $notes, $cost) {
        // الحصول على معلومات المنتج
        $product_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product WHERE product_id = '" . (int)$product_id . "'");
        $product_info = $product_query->row;

        // تحديد نوع الحركة (إضافة أو خصم)
        $movement_type = ($quantity > 0) ? 'in' : 'out';

        // إدخال حركة المخزون
        $this->db->query("INSERT INTO " . DB_PREFIX . "stock_movement
            SET product_id = '" . (int)$product_id . "',
                warehouse_id = '" . (int)$warehouse_id . "',
                quantity = '" . (float)$quantity . "',
                unit_id = '" . $this->db->escape($unit_id) . "',
                movement_type = '" . $this->db->escape($movement_type) . "',
                reference_type = '" . $this->db->escape($reference_type) . "',
                reference_id = '" . (int)$reference_id . "',
                cost = " . ($cost !== null ? "'" . (float)$cost . "'" : "NULL") . ",
                notes = '" . $this->db->escape($notes) . "',
                date_added = NOW(),
                user_id = '" . (int)$this->user->getId() . "'");

        return $this->db->getLastId();
    }

    /**
     * تسجيل الأثر المحاسبي
     */
    private function recordAccountingEffect($product_id, $quantity, $cost, $reference_type, $movement_id) {
        // تنفيذ هذه الدالة فقط إذا كان نظام المحاسبة مفعل
        if (!$this->config->get('config_accounting_enabled')) {
            return;
        }

        // الحصول على معلومات المنتج
        $product_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product WHERE product_id = '" . (int)$product_id . "'");
        $product_info = $product_query->row;

        // الحصول على الحسابات المحاسبية المرتبطة
        $inventory_account = $this->config->get('config_inventory_account');
        $cogs_account = $this->config->get('config_cogs_account');
        $purchase_account = $this->config->get('config_purchase_account');
        $sales_account = $this->config->get('config_sales_account');
        $inventory_adjustment_account = $this->config->get('config_inventory_adjustment_account');

        // تحديد الحسابات بناءً على نوع المرجع
        $debit_account = 0;
        $credit_account = 0;
        $amount = abs($quantity) * ($cost !== null ? $cost : $this->getProductCost($product_id));

        switch ($reference_type) {
            case 'purchase':
                // شراء: زيادة المخزون (مدين) ونقص النقدية/الذمم الدائنة (دائن)
                $debit_account = $inventory_account;
                $credit_account = $purchase_account;
                break;

            case 'sale':
                // بيع: نقص المخزون (دائن) وزيادة تكلفة البضاعة المباعة (مدين)
                $debit_account = $cogs_account;
                $credit_account = $inventory_account;
                break;

            case 'adjustment':
                // تعديل: إما زيادة أو نقص في المخزون
                if ($quantity > 0) {
                    // زيادة المخزون
                    $debit_account = $inventory_account;
                    $credit_account = $inventory_adjustment_account;
                } else {
                    // نقص المخزون
                    $debit_account = $inventory_adjustment_account;
                    $credit_account = $inventory_account;
                }
                break;

            default:
                // أنواع أخرى من الحركات
                return;
        }

        // إنشاء القيد المحاسبي
        $this->createAccountingEntry($debit_account, $credit_account, $amount, $reference_type, $movement_id, $product_info['name']);
    }

    /**
     * إنشاء قيد محاسبي
     */
    private function createAccountingEntry($debit_account, $credit_account, $amount, $reference_type, $reference_id, $description) {
        // إنشاء رأس القيد
        $this->db->query("INSERT INTO " . DB_PREFIX . "accounting_entry
            SET reference_type = '" . $this->db->escape($reference_type) . "',
                reference_id = '" . (int)$reference_id . "',
                description = '" . $this->db->escape($description) . "',
                amount = '" . (float)$amount . "',
                date_added = NOW(),
                user_id = '" . (int)$this->user->getId() . "'");

        $entry_id = $this->db->getLastId();

        // إنشاء تفاصيل القيد (الطرف المدين)
        $this->db->query("INSERT INTO " . DB_PREFIX . "accounting_entry_line
            SET entry_id = '" . (int)$entry_id . "',
                account_id = '" . (int)$debit_account . "',
                debit = '" . (float)$amount . "',
                credit = '0'");

        // إنشاء تفاصيل القيد (الطرف الدائن)
        $this->db->query("INSERT INTO " . DB_PREFIX . "accounting_entry_line
            SET entry_id = '" . (int)$entry_id . "',
                account_id = '" . (int)$credit_account . "',
                debit = '0',
                credit = '" . (float)$amount . "'");
    }

    /**
     * الحصول على تكلفة المنتج
     */
    private function getProductCost($product_id) {
        $query = $this->db->query("SELECT AVG(cost) as average_cost FROM " . DB_PREFIX . "product_warehouse
            WHERE product_id = '" . (int)$product_id . "'
            AND quantity > 0");

        if ($query->num_rows > 0 && $query->row['average_cost'] > 0) {
            return $query->row['average_cost'];
        }

        // إذا لم يتم العثور على تكلفة، استخدم سعر التكلفة من جدول المنتج
        $product_query = $this->db->query("SELECT cost FROM " . DB_PREFIX . "product
            WHERE product_id = '" . (int)$product_id . "'");

        if ($product_query->num_rows > 0) {
            return $product_query->row['cost'];
        }

        return 0;
    }

    /**
     * تحويل الكمية من وحدة إلى أخرى
     *
     * @param int $product_id معرف المنتج
     * @param float $quantity الكمية
     * @param string $from_unit_id وحدة المصدر
     * @param string $to_unit_id وحدة الهدف
     * @return float
     */
    public function convertQuantity($product_id, $quantity, $from_unit_id, $to_unit_id) {
        // إذا كانت الوحدتان متطابقتين، أعد الكمية كما هي
        if ($from_unit_id == $to_unit_id) {
            return $quantity;
        }

        // البحث عن معامل التحويل المباشر
        $direct_query = $this->db->query("SELECT conversion_factor FROM " . DB_PREFIX . "unit_conversion
            WHERE from_unit_id = '" . $this->db->escape($from_unit_id) . "'
            AND to_unit_id = '" . $this->db->escape($to_unit_id) . "'");

        if ($direct_query->num_rows > 0) {
            return $quantity * $direct_query->row['conversion_factor'];
        }

        // البحث عن معامل التحويل العكسي
        $reverse_query = $this->db->query("SELECT conversion_factor FROM " . DB_PREFIX . "unit_conversion
            WHERE from_unit_id = '" . $this->db->escape($to_unit_id) . "'
            AND to_unit_id = '" . $this->db->escape($from_unit_id) . "'");

        if ($reverse_query->num_rows > 0) {
            return $quantity / $reverse_query->row['conversion_factor'];
        }

        // البحث عن التحويل من خلال الوحدة الأساسية
        $product_query = $this->db->query("SELECT base_unit_id FROM " . DB_PREFIX . "product WHERE product_id = '" . (int)$product_id . "'");

        if ($product_query->num_rows > 0) {
            $base_unit_id = $product_query->row['base_unit_id'];

            // التحويل من وحدة المصدر إلى الوحدة الأساسية
            $to_base_query = $this->db->query("SELECT conversion_factor FROM " . DB_PREFIX . "unit_conversion
                WHERE from_unit_id = '" . $this->db->escape($from_unit_id) . "'
                AND to_unit_id = '" . $this->db->escape($base_unit_id) . "'");

            $from_base_query = $this->db->query("SELECT conversion_factor FROM " . DB_PREFIX . "unit_conversion
                WHERE from_unit_id = '" . $this->db->escape($base_unit_id) . "'
                AND to_unit_id = '" . $this->db->escape($from_unit_id) . "'");

            // التحويل من الوحدة الأساسية إلى وحدة الهدف
            $base_to_query = $this->db->query("SELECT conversion_factor FROM " . DB_PREFIX . "unit_conversion
                WHERE from_unit_id = '" . $this->db->escape($base_unit_id) . "'
                AND to_unit_id = '" . $this->db->escape($to_unit_id) . "'");

            $base_from_query = $this->db->query("SELECT conversion_factor FROM " . DB_PREFIX . "unit_conversion
                WHERE from_unit_id = '" . $this->db->escape($to_unit_id) . "'
                AND to_unit_id = '" . $this->db->escape($base_unit_id) . "'");

            // حساب معامل التحويل من وحدة المصدر إلى الوحدة الأساسية
            $to_base_factor = 0;
            if ($to_base_query->num_rows > 0) {
                $to_base_factor = $to_base_query->row['conversion_factor'];
            } elseif ($from_base_query->num_rows > 0) {
                $to_base_factor = 1 / $from_base_query->row['conversion_factor'];
            }

            // حساب معامل التحويل من الوحدة الأساسية إلى وحدة الهدف
            $from_base_factor = 0;
            if ($base_to_query->num_rows > 0) {
                $from_base_factor = $base_to_query->row['conversion_factor'];
            } elseif ($base_from_query->num_rows > 0) {
                $from_base_factor = 1 / $base_from_query->row['conversion_factor'];
            }

            // إذا تم العثور على كلا المعاملين، قم بالتحويل
            if ($to_base_factor > 0 && $from_base_factor > 0) {
                return $quantity * $to_base_factor * $from_base_factor;
            }
        }

        // إذا لم يتم العثور على أي معامل تحويل، أعد الكمية كما هي
        return $quantity;
    }

    /**
     * تحديث معامل التحويل بين وحدتين مع مراعاة المخزون السابق
     *
     * @param string $from_unit_id وحدة المصدر
     * @param string $to_unit_id وحدة الهدف
     * @param float $conversion_factor معامل التحويل الجديد
     * @return bool
     */
    public function updateConversionFactor($from_unit_id, $to_unit_id, $conversion_factor) {
        // التحقق من وجود معامل تحويل سابق
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "unit_conversion
            WHERE from_unit_id = '" . $this->db->escape($from_unit_id) . "'
            AND to_unit_id = '" . $this->db->escape($to_unit_id) . "'");

        $old_factor = 0;
        if ($query->num_rows > 0) {
            $old_factor = $query->row['conversion_factor'];
        }

        // تحديث معامل التحويل
        if ($query->num_rows > 0) {
            $this->db->query("UPDATE " . DB_PREFIX . "unit_conversion
                SET conversion_factor = '" . (float)$conversion_factor . "',
                    date_modified = NOW()
                WHERE from_unit_id = '" . $this->db->escape($from_unit_id) . "'
                AND to_unit_id = '" . $this->db->escape($to_unit_id) . "'");
        } else {
            $this->db->query("INSERT INTO " . DB_PREFIX . "unit_conversion
                SET from_unit_id = '" . $this->db->escape($from_unit_id) . "',
                    to_unit_id = '" . $this->db->escape($to_unit_id) . "',
                    conversion_factor = '" . (float)$conversion_factor . "',
                    date_added = NOW(),
                    date_modified = NOW()");
        }

        // إذا كان هناك تغيير في معامل التحويل، قم بتحديث المخزون
        if ($old_factor > 0 && $old_factor != $conversion_factor) {
            $this->updateStockAfterConversionChange($from_unit_id, $to_unit_id, $old_factor, $conversion_factor);
        }

        return true;
    }

    /**
     * تحديث المخزون بعد تغيير معامل التحويل
     *
     * @param string $from_unit_id وحدة المصدر
     * @param string $to_unit_id وحدة الهدف
     * @param float $old_factor معامل التحويل القديم
     * @param float $new_factor معامل التحويل الجديد
     * @return bool
     */
    private function updateStockAfterConversionChange($from_unit_id, $to_unit_id, $old_factor, $new_factor) {
        // الحصول على المنتجات التي تستخدم هذه الوحدات
        $products_query = $this->db->query("SELECT product_id, base_unit_id FROM " . DB_PREFIX . "product
            WHERE base_unit_id = '" . $this->db->escape($from_unit_id) . "'
            OR base_unit_id = '" . $this->db->escape($to_unit_id) . "'");

        if ($products_query->num_rows == 0) {
            return true;
        }

        // تسجيل تغيير معامل التحويل
        $this->db->query("INSERT INTO " . DB_PREFIX . "unit_conversion_history
            SET from_unit_id = '" . $this->db->escape($from_unit_id) . "',
                to_unit_id = '" . $this->db->escape($to_unit_id) . "',
                old_factor = '" . (float)$old_factor . "',
                new_factor = '" . (float)$new_factor . "',
                date_added = NOW(),
                user_id = '" . (int)$this->user->getId() . "'");

        $history_id = $this->db->getLastId();

        // تحديث المخزون لكل منتج
        foreach ($products_query->rows as $product) {
            $product_id = $product['product_id'];
            $base_unit_id = $product['base_unit_id'];

            // تحديث المخزون فقط إذا كانت الوحدة الأساسية للمنتج هي إحدى الوحدتين المتأثرتين
            if ($base_unit_id == $from_unit_id || $base_unit_id == $to_unit_id) {
                // الحصول على مخزون المنتج في جميع المستودعات
                $stock_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_warehouse
                    WHERE product_id = '" . (int)$product_id . "'");

                foreach ($stock_query->rows as $stock) {
                    $warehouse_id = $stock['warehouse_id'];
                    $quantity = $stock['quantity'];
                    $cost = $stock['cost'];

                    // تسجيل حركة تعديل للمخزون
                    $this->db->query("INSERT INTO " . DB_PREFIX . "stock_movement
                        SET product_id = '" . (int)$product_id . "',
                            warehouse_id = '" . (int)$warehouse_id . "',
                            quantity = '0',
                            unit_id = '" . $this->db->escape($base_unit_id) . "',
                            movement_type = 'adjustment',
                            reference_type = 'unit_conversion',
                            reference_id = '" . (int)$history_id . "',
                            cost = '" . (float)$cost . "',
                            notes = 'تعديل بسبب تغيير معامل التحويل من " . $old_factor . " إلى " . $new_factor . "',
                            date_added = NOW(),
                            user_id = '" . (int)$this->user->getId() . "'");
                }
            }
        }

        return true;
    }

    /**
     * إضافة حركة مخزون مع دعم المحاسبة
     *
     * @param array $data بيانات الحركة
     * @return int|bool معرف الحركة أو false في حالة الفشل
     */
    public function addInventoryMovement($data) {
        try {
            $this->db->query("START TRANSACTION");

            // الحصول على المخزون الحالي
            $current_inventory = $this->getCurrentStock($data['product_id'], $data['warehouse_id']);
            $current_quantity = $current_inventory['quantity'];
            $current_cost = $current_inventory['cost'];

            // حساب الكمية الجديدة
            $new_quantity = $current_quantity;

            if (in_array($data['movement_type'], array('purchase', 'adjustment_increase', 'transfer_in', 'initial', 'return_in', 'production'))) {
                $new_quantity += $data['quantity'];
            } else {
                $new_quantity -= $data['quantity'];
            }

            // حساب متوسط التكلفة الجديد
            $new_cost = $current_cost;

            if (in_array($data['movement_type'], array('purchase', 'adjustment_increase', 'initial', 'production')) && $data['quantity'] > 0) {
                // حساب متوسط التكلفة المرجح
                $new_cost = $this->calculateWeightedAverageCost($current_quantity, $current_cost, $data['quantity'], $data['cost']);
            }

            // حساب التغير في القيمة للمحاسبة
            $value_change = 0;

            if (in_array($data['movement_type'], array('purchase', 'adjustment_increase', 'transfer_in', 'initial', 'return_in', 'production'))) {
                // حركة واردة
                $value_change = $data['quantity'] * $data['cost'];
            } else {
                // حركة صادرة
                $value_change = -($data['quantity'] * $current_cost);
            }

            // الحصول على اسم المنتج للمحاسبة
            $product_query = $this->db->query("SELECT name FROM " . DB_PREFIX . "product_description
                WHERE product_id = '" . (int)$data['product_id'] . "'
                AND language_id = '" . (int)$this->config->get('config_language_id') . "'");

            $product_name = $product_query->row['name'] ?? 'Product #' . $data['product_id'];

            // إدخال سجل الحركة
            $this->db->query("INSERT INTO " . DB_PREFIX . "stock_movement SET
                product_id = '" . (int)$data['product_id'] . "',
                warehouse_id = '" . (int)$data['warehouse_id'] . "',
                unit_id = '" . $this->db->escape($data['unit_id']) . "',
                movement_type = '" . $this->db->escape($data['movement_type']) . "',
                quantity = '" . (float)$data['quantity'] . "',
                cost = '" . (float)$data['cost'] . "',
                old_quantity = '" . (float)$current_quantity . "',
                new_quantity = '" . (float)$new_quantity . "',
                old_cost = '" . (float)$current_cost . "',
                new_cost = '" . (float)$new_cost . "',
                value_change = '" . (float)$value_change . "',
                reference_type = '" . $this->db->escape($data['reference_type'] ?? '') . "',
                reference_id = '" . (int)($data['reference_id'] ?? 0) . "',
                notes = '" . $this->db->escape($data['notes'] ?? '') . "',
                date_added = '" . $this->db->escape($data['date_added'] ?? date('Y-m-d H:i:s')) . "',
                user_id = '" . (int)($data['user_id'] ?? $this->user->getId()) . "'");

            $movement_id = $this->db->getLastId();

            // تحديث المخزون
            $this->updateProductStock($data['product_id'], $data['warehouse_id'], $new_quantity, $new_cost);

            // إضافة تاريخ التكلفة إذا تغيرت التكلفة
            if ($current_cost != $new_cost) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "inventory_cost_history SET
                    product_id = '" . (int)$data['product_id'] . "',
                    unit_id = '" . $this->db->escape($data['unit_id']) . "',
                    branch_id = '" . (int)$data['warehouse_id'] . "',
                    old_cost = '" . (float)$current_cost . "',
                    new_cost = '" . (float)$new_cost . "',
                    change_type = '" . $this->db->escape($data['movement_type']) . "',
                    reason = '" . $this->db->escape($data['reason'] ?? '') . "',
                    notes = '" . $this->db->escape($data['notes'] ?? '') . "',
                    date_added = '" . $this->db->escape($data['date_added'] ?? date('Y-m-d H:i:s')) . "',
                    user_id = '" . (int)($data['user_id'] ?? $this->user->getId()) . "'");
            }

            // إنشاء قيد محاسبي إذا كان نظام المحاسبة مفعل
            if ($this->config->get('config_accounting_enabled') && abs($value_change) > 0) {
                $this->load->model('accounting/accounting_manager');

                $accounting_data = array(
                    'movement_id' => $movement_id,
                    'movement_type' => $data['movement_type'],
                    'product_id' => $data['product_id'],
                    'product_name' => $product_name,
                    'quantity' => $data['quantity'],
                    'cost' => $data['cost'],
                    'value_change' => $value_change,
                    'date_added' => $data['date_added'] ?? date('Y-m-d H:i:s'),
                    'user_id' => $data['user_id'] ?? $this->user->getId()
                );

                $journal_id = $this->model_accounting_accounting_manager->createInventoryJournalEntry($accounting_data);

                if ($journal_id) {
                    // تحديث الحركة بمرجع القيد المحاسبي
                    $this->db->query("UPDATE " . DB_PREFIX . "stock_movement SET
                        journal_id = '" . (int)$journal_id . "'
                        WHERE movement_id = '" . (int)$movement_id . "'");
                }
            }

            $this->db->query("COMMIT");

            return $movement_id;
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            $this->log->write("Error in addInventoryMovement: " . $e->getMessage());
            return false;
        }
    }

    /**
     * الحصول على تفاصيل حركة مخزون
     *
     * @param int $movement_id معرف الحركة
     * @return array|bool بيانات الحركة أو false في حالة عدم وجودها
     */
    public function getMovementDetails($movement_id) {
        $query = $this->db->query("SELECT sm.*, p.name as product_name, w.name as warehouse_name, u.name as unit_name, us.username
            FROM " . DB_PREFIX . "stock_movement sm
            LEFT JOIN " . DB_PREFIX . "product p ON (sm.product_id = p.product_id)
            LEFT JOIN " . DB_PREFIX . "warehouse w ON (sm.warehouse_id = w.warehouse_id)
            LEFT JOIN " . DB_PREFIX . "unit u ON (sm.unit_id = u.unit_id)
            LEFT JOIN " . DB_PREFIX . "user us ON (sm.user_id = us.user_id)
            WHERE sm.movement_id = '" . (int)$movement_id . "'");

        return $query->row;
    }

    /**
     * الحصول على جميع حركات المخزون
     *
     * @param array $filters مرشحات البحث
     * @return array
     */
    public function getAllMovements($filters = []) {
        $sql = "SELECT sm.*, p.name as product_name, w.name as warehouse_name, u.name as unit_name, us.username
            FROM " . DB_PREFIX . "stock_movement sm
            LEFT JOIN " . DB_PREFIX . "product p ON (sm.product_id = p.product_id)
            LEFT JOIN " . DB_PREFIX . "warehouse w ON (sm.warehouse_id = w.warehouse_id)
            LEFT JOIN " . DB_PREFIX . "unit u ON (sm.unit_id = u.unit_id)
            LEFT JOIN " . DB_PREFIX . "user us ON (sm.user_id = us.user_id)
            WHERE 1=1";

        // تطبيق المرشحات
        if (!empty($filters['filter_branch_id'])) {
            $sql .= " AND sm.warehouse_id = '" . (int)$filters['filter_branch_id'] . "'";
        }

        if (!empty($filters['filter_movement_type'])) {
            $sql .= " AND sm.movement_type = '" . $this->db->escape($filters['filter_movement_type']) . "'";
        }

        if (!empty($filters['filter_reference_type'])) {
            $sql .= " AND sm.reference_type = '" . $this->db->escape($filters['filter_reference_type']) . "'";
        }

        if (!empty($filters['filter_date_start'])) {
            $sql .= " AND DATE(sm.date_added) >= '" . $this->db->escape($filters['filter_date_start']) . "'";
        }

        if (!empty($filters['filter_date_end'])) {
            $sql .= " AND DATE(sm.date_added) <= '" . $this->db->escape($filters['filter_date_end']) . "'";
        }

        // ترتيب النتائج
        if (!empty($filters['sort'])) {
            $sql .= " ORDER BY " . $this->db->escape($filters['sort']);
        } else {
            $sql .= " ORDER BY sm.date_added";
        }

        if (!empty($filters['order'])) {
            $sql .= " " . $this->db->escape($filters['order']);
        } else {
            $sql .= " DESC";
        }

        // تحديد عدد النتائج
        if (isset($filters['start']) || isset($filters['limit'])) {
            if ($filters['start'] < 0) {
                $filters['start'] = 0;
            }

            if ($filters['limit'] < 1) {
                $filters['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$filters['start'] . "," . (int)$filters['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    /**
     * الحصول على إجمالي عدد حركات المخزون
     *
     * @param array $filters مرشحات البحث
     * @return int
     */
    public function getTotalAllMovements($filters = []) {
        $sql = "SELECT COUNT(*) AS total
            FROM " . DB_PREFIX . "stock_movement sm
            WHERE 1=1";

        // تطبيق المرشحات
        if (!empty($filters['filter_branch_id'])) {
            $sql .= " AND sm.warehouse_id = '" . (int)$filters['filter_branch_id'] . "'";
        }

        if (!empty($filters['filter_movement_type'])) {
            $sql .= " AND sm.movement_type = '" . $this->db->escape($filters['filter_movement_type']) . "'";
        }

        if (!empty($filters['filter_reference_type'])) {
            $sql .= " AND sm.reference_type = '" . $this->db->escape($filters['filter_reference_type']) . "'";
        }

        if (!empty($filters['filter_date_start'])) {
            $sql .= " AND DATE(sm.date_added) >= '" . $this->db->escape($filters['filter_date_start']) . "'";
        }

        if (!empty($filters['filter_date_end'])) {
            $sql .= " AND DATE(sm.date_added) <= '" . $this->db->escape($filters['filter_date_end']) . "'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    /**
     * الحصول على إجمالي عدد حركات المخزون للمنتج
     *
     * @param int $product_id معرف المنتج
     * @param array $filters مرشحات البحث
     * @return int
     */
    public function getTotalProductMovements($product_id, $filters = []) {
        $sql = "SELECT COUNT(*) AS total
            FROM " . DB_PREFIX . "stock_movement sm
            WHERE sm.product_id = '" . (int)$product_id . "'";

        // تطبيق المرشحات
        if (!empty($filters['filter_branch_id'])) {
            $sql .= " AND sm.warehouse_id = '" . (int)$filters['filter_branch_id'] . "'";
        }

        if (!empty($filters['filter_movement_type'])) {
            $sql .= " AND sm.movement_type = '" . $this->db->escape($filters['filter_movement_type']) . "'";
        }

        if (!empty($filters['filter_reference_type'])) {
            $sql .= " AND sm.reference_type = '" . $this->db->escape($filters['filter_reference_type']) . "'";
        }

        if (!empty($filters['filter_date_start'])) {
            $sql .= " AND DATE(sm.date_added) >= '" . $this->db->escape($filters['filter_date_start']) . "'";
        }

        if (!empty($filters['filter_date_end'])) {
            $sql .= " AND DATE(sm.date_added) <= '" . $this->db->escape($filters['filter_date_end']) . "'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    /**
     * الحصول على القيود المحاسبية المرتبطة بحركة مخزون
     *
     * @param int $movement_id معرف الحركة
     * @return array القيود المحاسبية
     */
    public function getMovementJournalEntries($movement_id) {
        $this->load->model('accounting/accounting_manager');

        $query = $this->db->query("SELECT journal_id FROM " . DB_PREFIX . "stock_movement
            WHERE movement_id = '" . (int)$movement_id . "'");

        if ($query->num_rows > 0 && $query->row['journal_id']) {
            return $this->model_accounting_accounting_manager->getJournalEntries($query->row['journal_id']);
        }

        return array();
    }

    /**
     * الحصول على إجمالي عدد حركات المخزون للمنتج
     *
     * @param int $product_id معرف المنتج
     * @param array $filters مرشحات البحث
     * @return int
     */
    public function getTotalProductMovements($product_id, $filters = []) {
        $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "stock_movement sm
            WHERE sm.product_id = '" . (int)$product_id . "'";

        // إضافة المرشحات
        if (!empty($filters['warehouse_id'])) {
            $sql .= " AND sm.warehouse_id = '" . (int)$filters['warehouse_id'] . "'";
        }

        if (!empty($filters['movement_type'])) {
            $sql .= " AND sm.movement_type = '" . $this->db->escape($filters['movement_type']) . "'";
        }

        if (!empty($filters['reference_type'])) {
            $sql .= " AND sm.reference_type = '" . $this->db->escape($filters['reference_type']) . "'";
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(sm.date_added) >= '" . $this->db->escape($filters['date_from']) . "'";
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(sm.date_added) <= '" . $this->db->escape($filters['date_to']) . "'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    /**
     * الحصول على تاريخ تكلفة المنتج
     *
     * @param int $product_id معرف المنتج
     * @return array
     */
    public function getProductCostHistory($product_id) {
        $query = $this->db->query("SELECT ch.*, u.name as unit_name, w.name as branch_name, us.username
            FROM " . DB_PREFIX . "inventory_cost_history ch
            LEFT JOIN " . DB_PREFIX . "unit u ON (ch.unit_id = u.unit_id)
            LEFT JOIN " . DB_PREFIX . "warehouse w ON (ch.branch_id = w.warehouse_id)
            LEFT JOIN " . DB_PREFIX . "user us ON (ch.user_id = us.user_id)
            WHERE ch.product_id = '" . (int)$product_id . "'
            ORDER BY ch.date_added DESC");

        return $query->rows;
    }

    /**
     * الحصول على إحصائيات حركات المخزون للمنتج
     *
     * @param int $product_id معرف المنتج
     * @param array $filters مرشحات البحث
     * @return array
     */
    public function getProductMovementStatistics($product_id, $filters = []) {
        $statistics = array(
            'total_incoming' => 0,
            'total_outgoing' => 0,
            'net_change' => 0,
            'current_stock' => 0,
            'by_type' => array(),
            'by_branch' => array(),
            'stock_trend' => array(),
            'frequency' => array(
                'daily' => 0,
                'weekly' => 0,
                'monthly' => 0,
                'quarterly' => 0,
                'yearly' => 0
            )
        );

        // الحصول على إجمالي المخزون الحالي
        $stock_query = $this->db->query("SELECT SUM(quantity) as total FROM " . DB_PREFIX . "product_warehouse
            WHERE product_id = '" . (int)$product_id . "'");

        $statistics['current_stock'] = $stock_query->row['total'] ?? 0;

        // بناء استعلام الحركات
        $sql = "SELECT sm.* FROM " . DB_PREFIX . "stock_movement sm
            WHERE sm.product_id = '" . (int)$product_id . "'";

        // إضافة المرشحات
        if (!empty($filters['warehouse_id'])) {
            $sql .= " AND sm.warehouse_id = '" . (int)$filters['warehouse_id'] . "'";
        }

        if (!empty($filters['movement_type'])) {
            $sql .= " AND sm.movement_type = '" . $this->db->escape($filters['movement_type']) . "'";
        }

        if (!empty($filters['unit_id'])) {
            $sql .= " AND sm.unit_id = '" . $this->db->escape($filters['unit_id']) . "'";
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(sm.date_added) >= '" . $this->db->escape($filters['date_from']) . "'";
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(sm.date_added) <= '" . $this->db->escape($filters['date_to']) . "'";
        }

        $sql .= " ORDER BY sm.date_added";

        $query = $this->db->query($sql);

        // تحليل البيانات
        $movements = $query->rows;
        $stock_trend = array();
        $current_date = '';
        $current_stock = 0;

        foreach ($movements as $movement) {
            // حساب الإجماليات
            if (in_array($movement['movement_type'], array('purchase', 'adjustment_increase', 'transfer_in', 'initial', 'return_in', 'production'))) {
                $statistics['total_incoming'] += $movement['quantity'];
            } else {
                $statistics['total_outgoing'] += $movement['quantity'];
            }

            // حساب صافي التغيير
            $statistics['net_change'] = $statistics['total_incoming'] - $statistics['total_outgoing'];

            // تجميع حسب النوع
            if (!isset($statistics['by_type'][$movement['movement_type']])) {
                $statistics['by_type'][$movement['movement_type']] = array(
                    'quantity' => 0,
                    'value' => 0
                );
            }

            $statistics['by_type'][$movement['movement_type']]['quantity'] += $movement['quantity'];
            $statistics['by_type'][$movement['movement_type']]['value'] += $movement['value_change'];

            // تجميع حسب الفرع
            $warehouse_query = $this->db->query("SELECT name FROM " . DB_PREFIX . "warehouse
                WHERE warehouse_id = '" . (int)$movement['warehouse_id'] . "'");

            $warehouse_name = $warehouse_query->row['name'] ?? 'Unknown';

            if (!isset($statistics['by_branch'][$warehouse_name])) {
                $statistics['by_branch'][$warehouse_name] = 0;
            }

            // الحصول على المخزون الحالي حسب الفرع
            $branch_stock_query = $this->db->query("SELECT quantity FROM " . DB_PREFIX . "product_warehouse
                WHERE product_id = '" . (int)$product_id . "'
                AND warehouse_id = '" . (int)$movement['warehouse_id'] . "'");

            $statistics['by_branch'][$warehouse_name] = $branch_stock_query->row['quantity'] ?? 0;

            // تحليل اتجاه المخزون
            $date = date('Y-m-d', strtotime($movement['date_added']));

            if ($date != $current_date) {
                if ($current_date != '') {
                    $stock_trend[] = array(
                        'date' => $current_date,
                        'quantity' => $current_stock
                    );
                }

                $current_date = $date;
            }

            $current_stock = $movement['new_quantity'];

            // تحليل تكرار الحركات
            $now = time();
            $movement_date = strtotime($movement['date_added']);
            $diff_days = floor(($now - $movement_date) / (60 * 60 * 24));

            if ($diff_days <= 1) {
                $statistics['frequency']['daily']++;
            } elseif ($diff_days <= 7) {
                $statistics['frequency']['weekly']++;
            } elseif ($diff_days <= 30) {
                $statistics['frequency']['monthly']++;
            } elseif ($diff_days <= 90) {
                $statistics['frequency']['quarterly']++;
            } else {
                $statistics['frequency']['yearly']++;
            }
        }

        // إضافة آخر نقطة في اتجاه المخزون
        if ($current_date != '') {
            $stock_trend[] = array(
                'date' => $current_date,
                'quantity' => $current_stock
            );
        }

        $statistics['stock_trend'] = $stock_trend;

        return $statistics;
    }

    /**
     * الحصول على حركات المخزون للمنتج
     *
     * @param int $product_id معرف المنتج
     * @param array $filters مرشحات البحث
     * @return array
     */
    public function getProductMovements($product_id, $filters = []) {
        $sql = "SELECT sm.*, p.name as product_name, w.name as warehouse_name, u.name as unit_name, us.username
            FROM " . DB_PREFIX . "stock_movement sm
            LEFT JOIN " . DB_PREFIX . "product p ON (sm.product_id = p.product_id)
            LEFT JOIN " . DB_PREFIX . "warehouse w ON (sm.warehouse_id = w.warehouse_id)
            LEFT JOIN " . DB_PREFIX . "unit u ON (sm.unit_id = u.unit_id)
            LEFT JOIN " . DB_PREFIX . "user us ON (sm.user_id = us.user_id)
            WHERE sm.product_id = '" . (int)$product_id . "'";

        // إضافة المرشحات
        if (!empty($filters['warehouse_id'])) {
            $sql .= " AND sm.warehouse_id = '" . (int)$filters['warehouse_id'] . "'";
        }

        if (!empty($filters['movement_type'])) {
            $sql .= " AND sm.movement_type = '" . $this->db->escape($filters['movement_type']) . "'";
        }

        if (!empty($filters['reference_type'])) {
            $sql .= " AND sm.reference_type = '" . $this->db->escape($filters['reference_type']) . "'";
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(sm.date_added) >= '" . $this->db->escape($filters['date_from']) . "'";
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(sm.date_added) <= '" . $this->db->escape($filters['date_to']) . "'";
        }

        // الترتيب
        $sql .= " ORDER BY sm.date_added DESC";

        // الصفحات
        if (isset($filters['start']) || isset($filters['limit'])) {
            if ($filters['start'] < 0) {
                $filters['start'] = 0;
            }

            if ($filters['limit'] < 1) {
                $filters['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$filters['start'] . "," . (int)$filters['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }
}
