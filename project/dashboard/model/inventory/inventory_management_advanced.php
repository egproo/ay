<?php
/**
 * نموذج إدارة المخزون المتقدمة والمتكاملة
 * مستوى احترافي عالمي مثل SAP وOracle وMicrosoft Dynamics
 */
class ModelInventoryInventoryManagementAdvanced extends Model {

    /**
     * معالجة حركة المخزون
     */
    public function processStockMovement($data) {
        $product_id = $data['product_id'];
        $warehouse_id = $data['warehouse_id'];
        $movement_type = $data['movement_type']; // in, out, transfer
        $quantity = $data['quantity'];
        $unit_cost = $data['unit_cost'] ?? 0;
        $reference = $data['reference'];
        $notes = $data['notes'];

        // التحقق من توفر المخزون للحركات الخارجة
        if ($movement_type == 'out') {
            $current_stock = $this->getCurrentStock($product_id, $warehouse_id);
            if ($current_stock < $quantity) {
                throw new Exception('المخزون المتاح غير كافي للحركة المطلوبة');
            }
        }

        // إنشاء سجل الحركة
        $movement_id = $this->createStockMovement($data);

        // تحديث المخزون
        $this->updateStockQuantity($product_id, $warehouse_id, $movement_type, $quantity);

        // تحديث التكلفة المتوسطة المرجحة
        if ($movement_type == 'in' && $unit_cost > 0) {
            $this->updateWeightedAverageCost($product_id, $warehouse_id, $quantity, $unit_cost);
        }

        // إنشاء القيد المحاسبي
        $journal_entry_id = $this->createInventoryJournalEntry($data, $movement_id);

        return array(
            'movement_id' => $movement_id,
            'journal_entry_id' => $journal_entry_id,
            'new_stock_level' => $this->getCurrentStock($product_id, $warehouse_id)
        );
    }

    /**
     * معالجة تسوية المخزون
     */
    public function processStockAdjustment($data) {
        $product_id = $data['product_id'];
        $warehouse_id = $data['warehouse_id'];
        $physical_count = $data['physical_count'];
        $reason = $data['reason'];
        $notes = $data['notes'];

        // الحصول على المخزون الحالي
        $current_stock = $this->getCurrentStock($product_id, $warehouse_id);
        $difference = $physical_count - $current_stock;

        if ($difference == 0) {
            throw new Exception('لا يوجد فرق بين المخزون الفعلي والمخزون في النظام');
        }

        // إنشاء سجل التسوية
        $adjustment_id = $this->createStockAdjustment($data, $current_stock, $difference);

        // تحديث المخزون
        $movement_type = $difference > 0 ? 'in' : 'out';
        $this->updateStockQuantity($product_id, $warehouse_id, $movement_type, abs($difference));

        // إنشاء القيد المحاسبي
        $journal_entry_id = $this->createAdjustmentJournalEntry($data, $adjustment_id, $difference);

        return array(
            'adjustment_id' => $adjustment_id,
            'journal_entry_id' => $journal_entry_id,
            'difference' => $difference,
            'new_stock_level' => $physical_count
        );
    }

    /**
     * معالجة تحويل المخزون
     */
    public function processStockTransfer($data) {
        $product_id = $data['product_id'];
        $from_warehouse_id = $data['from_warehouse_id'];
        $to_warehouse_id = $data['to_warehouse_id'];
        $quantity = $data['quantity'];
        $notes = $data['notes'];

        // التحقق من توفر المخزون في المخزن المصدر
        $current_stock = $this->getCurrentStock($product_id, $from_warehouse_id);
        if ($current_stock < $quantity) {
            throw new Exception('المخزون المتاح في المخزن المصدر غير كافي للتحويل');
        }

        // إنشاء سجل التحويل
        $transfer_id = $this->createStockTransfer($data);

        // تحديث المخزون في المخزن المصدر (خروج)
        $this->updateStockQuantity($product_id, $from_warehouse_id, 'out', $quantity);

        // تحديث المخزون في المخزن المستقبل (دخول)
        $this->updateStockQuantity($product_id, $to_warehouse_id, 'in', $quantity);

        // نقل التكلفة المتوسطة
        $this->transferWeightedAverageCost($product_id, $from_warehouse_id, $to_warehouse_id, $quantity);

        // إنشاء القيد المحاسبي
        $journal_entry_id = $this->createTransferJournalEntry($data, $transfer_id);

        return array(
            'transfer_id' => $transfer_id,
            'journal_entry_id' => $journal_entry_id,
            'from_warehouse_stock' => $this->getCurrentStock($product_id, $from_warehouse_id),
            'to_warehouse_stock' => $this->getCurrentStock($product_id, $to_warehouse_id)
        );
    }

    /**
     * معالجة جرد المخزون
     */
    public function processStockCount($data) {
        $warehouse_id = $data['warehouse_id'];
        $count_date = $data['count_date'];
        $count_items = $data['count_items']; // array of product_id => physical_count
        $notes = $data['notes'];

        // إنشاء سجل الجرد
        $count_id = $this->createStockCount($data);

        $adjustments = array();
        $total_adjustments = 0;

        foreach ($count_items as $product_id => $physical_count) {
            $current_stock = $this->getCurrentStock($product_id, $warehouse_id);
            $difference = $physical_count - $current_stock;

            if ($difference != 0) {
                // إنشاء تسوية للفرق
                $adjustment_data = array(
                    'product_id' => $product_id,
                    'warehouse_id' => $warehouse_id,
                    'physical_count' => $physical_count,
                    'reason' => 'stock_count',
                    'notes' => 'تسوية من جرد المخزون رقم: ' . $count_id,
                    'count_id' => $count_id
                );

                $adjustment_result = $this->processStockAdjustment($adjustment_data);

                $adjustments[] = array(
                    'product_id' => $product_id,
                    'current_stock' => $current_stock,
                    'physical_count' => $physical_count,
                    'difference' => $difference,
                    'adjustment_id' => $adjustment_result['adjustment_id']
                );

                $total_adjustments++;
            }
        }

        // تحديث سجل الجرد
        $this->updateStockCountResult($count_id, $total_adjustments);

        return array(
            'count_id' => $count_id,
            'adjustments' => $adjustments,
            'total_adjustments' => $total_adjustments
        );
    }

    /**
     * معالجة إعادة تقييم المخزون
     */
    public function processRevaluation($data) {
        $revaluation_date = $data['revaluation_date'];
        $revaluation_items = $data['revaluation_items']; // array of product_id => new_unit_cost
        $reason = $data['reason'];
        $notes = $data['notes'];

        // إنشاء سجل إعادة التقييم
        $revaluation_id = $this->createRevaluation($data);

        $revaluation_entries = array();
        $total_adjustment = 0;

        foreach ($revaluation_items as $product_id => $new_unit_cost) {
            $current_cost = $this->getCurrentUnitCost($product_id);
            $current_stock = $this->getTotalStock($product_id);

            $cost_difference = $new_unit_cost - $current_cost;
            $value_adjustment = $cost_difference * $current_stock;

            if ($value_adjustment != 0) {
                // تحديث التكلفة
                $this->updateProductUnitCost($product_id, $new_unit_cost);

                $revaluation_entries[] = array(
                    'product_id' => $product_id,
                    'old_unit_cost' => $current_cost,
                    'new_unit_cost' => $new_unit_cost,
                    'current_stock' => $current_stock,
                    'value_adjustment' => $value_adjustment
                );

                $total_adjustment += $value_adjustment;
            }
        }

        // إنشاء القيد المحاسبي
        $journal_entry_id = $this->createRevaluationJournalEntry($revaluation_id, $revaluation_entries, $total_adjustment);

        return array(
            'revaluation_id' => $revaluation_id,
            'journal_entry_id' => $journal_entry_id,
            'entries' => $revaluation_entries,
            'total_adjustment' => $total_adjustment
        );
    }

    /**
     * تحليل المخزون
     */
    public function analyzeInventory($filter_data) {
        $analysis = array();

        // تحليل مستويات المخزون
        $analysis['stock_levels'] = $this->analyzeStockLevels($filter_data);

        // تحليل حركة المخزون
        $analysis['movement_analysis'] = $this->analyzeStockMovement($filter_data);

        // تحليل التقييم
        $analysis['valuation_analysis'] = $this->analyzeInventoryValuation($filter_data);

        // تحليل الأداء
        $analysis['performance_analysis'] = $this->analyzeInventoryPerformance($filter_data);

        // التوصيات
        $analysis['recommendations'] = $this->generateInventoryRecommendations($analysis);

        return $analysis;
    }

    /**
     * الحصول على مستويات المخزون
     */
    public function getStockLevels($warehouse_id = 0, $category_id = 0) {
        $sql = "
            SELECT p.product_id, p.product_code, p.product_name,
                   pc.category_name, w.warehouse_name,
                   COALESCE(s.quantity, 0) as current_stock,
                   COALESCE(s.reserved_quantity, 0) as reserved_stock,
                   COALESCE(s.quantity - s.reserved_quantity, 0) as available_stock,
                   p.reorder_level, p.max_stock_level,
                   COALESCE(s.unit_cost, p.cost_price) as unit_cost,
                   COALESCE(s.quantity * s.unit_cost, 0) as total_value,
                   CASE
                       WHEN COALESCE(s.quantity, 0) <= p.reorder_level THEN 'low_stock'
                       WHEN COALESCE(s.quantity, 0) >= p.max_stock_level THEN 'overstock'
                       ELSE 'normal'
                   END as stock_status
            FROM " . DB_PREFIX . "product p
            LEFT JOIN " . DB_PREFIX . "product_category pc ON p.category_id = pc.category_id
            LEFT JOIN " . DB_PREFIX . "stock s ON p.product_id = s.product_id
            LEFT JOIN " . DB_PREFIX . "warehouse w ON s.warehouse_id = w.warehouse_id
            WHERE p.status = 1
        ";

        if ($warehouse_id > 0) {
            $sql .= " AND s.warehouse_id = '" . (int)$warehouse_id . "'";
        }

        if ($category_id > 0) {
            $sql .= " AND p.category_id = '" . (int)$category_id . "'";
        }

        $sql .= " ORDER BY p.product_name";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    /**
     * الحصول على تاريخ حركة المخزون
     */
    public function getMovementHistory($product_id, $limit = 50, $offset = 0) {
        $query = $this->db->query("
            SELECT sm.*, w.warehouse_name, p.product_name,
                   CASE
                       WHEN sm.movement_type = 'in' THEN 'دخول'
                       WHEN sm.movement_type = 'out' THEN 'خروج'
                       WHEN sm.movement_type = 'transfer' THEN 'تحويل'
                       ELSE 'أخرى'
                   END as movement_type_name,
                   CONCAT(u.firstname, ' ', u.lastname) as created_by_name
            FROM " . DB_PREFIX . "stock_movements sm
            LEFT JOIN " . DB_PREFIX . "warehouse w ON sm.warehouse_id = w.warehouse_id
            LEFT JOIN " . DB_PREFIX . "product p ON sm.product_id = p.product_id
            LEFT JOIN " . DB_PREFIX . "user u ON sm.created_by = u.user_id
            WHERE sm.product_id = '" . (int)$product_id . "'
            ORDER BY sm.movement_date DESC, sm.movement_id DESC
            LIMIT " . (int)$offset . ", " . (int)$limit . "
        ");

        return $query->rows;
    }

    /**
     * حساب تقييم المخزون
     */
    public function calculateInventoryValuation($valuation_date, $warehouse_id = 0) {
        $sql = "
            SELECT p.product_id, p.product_code, p.product_name,
                   pc.category_name, w.warehouse_name,
                   COALESCE(s.quantity, 0) as current_stock,
                   COALESCE(s.unit_cost, p.cost_price) as unit_cost,
                   COALESCE(s.quantity * s.unit_cost, 0) as total_value,
                   p.selling_price,
                   COALESCE(s.quantity * p.selling_price, 0) as retail_value
            FROM " . DB_PREFIX . "product p
            LEFT JOIN " . DB_PREFIX . "product_category pc ON p.category_id = pc.category_id
            LEFT JOIN " . DB_PREFIX . "stock s ON p.product_id = s.product_id
            LEFT JOIN " . DB_PREFIX . "warehouse w ON s.warehouse_id = w.warehouse_id
            WHERE p.status = 1
            AND COALESCE(s.quantity, 0) > 0
        ";

        if ($warehouse_id > 0) {
            $sql .= " AND s.warehouse_id = '" . (int)$warehouse_id . "'";
        }

        $sql .= " ORDER BY total_value DESC";

        $query = $this->db->query($sql);

        $valuation = array(
            'items' => $query->rows,
            'summary' => array(
                'total_items' => count($query->rows),
                'total_cost_value' => array_sum(array_column($query->rows, 'total_value')),
                'total_retail_value' => array_sum(array_column($query->rows, 'retail_value')),
                'valuation_date' => $valuation_date
            )
        );

        return $valuation;
    }

    /**
     * الحصول على عناصر المخزون
     */
    public function getInventoryItems($data = array()) {
        $sql = "
            SELECT p.product_id, p.product_code, p.product_name,
                   pc.category_name, w.warehouse_name,
                   COALESCE(s.quantity, 0) as current_stock,
                   COALESCE(s.reserved_quantity, 0) as reserved_stock,
                   COALESCE(s.quantity - s.reserved_quantity, 0) as available_stock,
                   COALESCE(s.unit_cost, p.cost_price) as unit_cost,
                   COALESCE(s.quantity * s.unit_cost, 0) as total_value,
                   p.reorder_level,
                   CASE
                       WHEN COALESCE(s.quantity, 0) <= p.reorder_level THEN 'منخفض'
                       WHEN COALESCE(s.quantity, 0) >= p.max_stock_level THEN 'مرتفع'
                       ELSE 'طبيعي'
                   END as status,
                   (SELECT MAX(movement_date) FROM " . DB_PREFIX . "stock_movements sm WHERE sm.product_id = p.product_id) as last_movement
            FROM " . DB_PREFIX . "product p
            LEFT JOIN " . DB_PREFIX . "product_category pc ON p.category_id = pc.category_id
            LEFT JOIN " . DB_PREFIX . "stock s ON p.product_id = s.product_id
            LEFT JOIN " . DB_PREFIX . "warehouse w ON s.warehouse_id = w.warehouse_id
            WHERE p.status = 1
        ";

        if (!empty($data['filter_name'])) {
            $sql .= " AND (p.product_name LIKE '%" . $this->db->escape($data['filter_name']) . "%' OR p.product_code LIKE '%" . $this->db->escape($data['filter_name']) . "%')";
        }

        if (!empty($data['filter_category'])) {
            $sql .= " AND p.category_id = '" . (int)$data['filter_category'] . "'";
        }

        if (!empty($data['filter_warehouse'])) {
            $sql .= " AND s.warehouse_id = '" . (int)$data['filter_warehouse'] . "'";
        }

        $sort_data = array(
            'product_name',
            'product_code',
            'category_name',
            'current_stock',
            'unit_cost',
            'total_value'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY product_name";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    /**
     * الحصول على إجمالي عناصر المخزون
     */
    public function getTotalInventoryItems($data = array()) {
        $sql = "
            SELECT COUNT(DISTINCT p.product_id) AS total
            FROM " . DB_PREFIX . "product p
            LEFT JOIN " . DB_PREFIX . "stock s ON p.product_id = s.product_id
            WHERE p.status = 1
        ";

        if (!empty($data['filter_name'])) {
            $sql .= " AND (p.product_name LIKE '%" . $this->db->escape($data['filter_name']) . "%' OR p.product_code LIKE '%" . $this->db->escape($data['filter_name']) . "%')";
        }

        if (!empty($data['filter_category'])) {
            $sql .= " AND p.category_id = '" . (int)$data['filter_category'] . "'";
        }

        if (!empty($data['filter_warehouse'])) {
            $sql .= " AND s.warehouse_id = '" . (int)$data['filter_warehouse'] . "'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    /**
     * الحصول على البيانات للتصدير
     */
    public function getInventoryForExport($report_type, $filter_data) {
        switch ($report_type) {
            case 'stock_levels':
                return $this->getStockLevels($filter_data['warehouse_id'] ?? 0, $filter_data['category_id'] ?? 0);
            case 'valuation':
                return $this->calculateInventoryValuation($filter_data['valuation_date'] ?? date('Y-m-d'), $filter_data['warehouse_id'] ?? 0);
            case 'movement_summary':
                return $this->getMovementSummary($filter_data);
            default:
                return $this->getInventoryItems($filter_data);
        }
    }

    /**
     * الحصول على المخزون الحالي
     */
    private function getCurrentStock($product_id, $warehouse_id) {
        $query = $this->db->query("
            SELECT COALESCE(quantity, 0) as current_stock
            FROM " . DB_PREFIX . "stock
            WHERE product_id = '" . (int)$product_id . "'
            AND warehouse_id = '" . (int)$warehouse_id . "'
        ");

        return $query->row ? (float)$query->row['current_stock'] : 0;
    }

    /**
     * إنشاء حركة مخزون
     */
    private function createStockMovement($data) {
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "stock_movements SET
            product_id = '" . (int)$data['product_id'] . "',
            warehouse_id = '" . (int)$data['warehouse_id'] . "',
            movement_type = '" . $this->db->escape($data['movement_type']) . "',
            quantity = '" . (float)$data['quantity'] . "',
            unit_cost = '" . (float)($data['unit_cost'] ?? 0) . "',
            total_cost = '" . (float)($data['quantity'] * ($data['unit_cost'] ?? 0)) . "',
            reference = '" . $this->db->escape($data['reference']) . "',
            notes = '" . $this->db->escape($data['notes']) . "',
            movement_date = '" . $this->db->escape($data['movement_date'] ?? date('Y-m-d H:i:s')) . "',
            created_by = '" . (int)$this->user->getId() . "',
            created_date = NOW()
        ");

        return $this->db->getLastId();
    }

    /**
     * تحديث كمية المخزون
     */
    private function updateStockQuantity($product_id, $warehouse_id, $movement_type, $quantity) {
        // التحقق من وجود سجل المخزون
        $stock_query = $this->db->query("
            SELECT stock_id, quantity
            FROM " . DB_PREFIX . "stock
            WHERE product_id = '" . (int)$product_id . "'
            AND warehouse_id = '" . (int)$warehouse_id . "'
        ");

        if ($stock_query->num_rows) {
            // تحديث السجل الموجود
            $quantity_change = $movement_type == 'in' ? $quantity : -$quantity;

            $this->db->query("
                UPDATE " . DB_PREFIX . "stock SET
                quantity = quantity + (" . (float)$quantity_change . "),
                modified_date = NOW()
                WHERE product_id = '" . (int)$product_id . "'
                AND warehouse_id = '" . (int)$warehouse_id . "'
            ");
        } else {
            // إنشاء سجل جديد
            $initial_quantity = $movement_type == 'in' ? $quantity : 0;

            $this->db->query("
                INSERT INTO " . DB_PREFIX . "stock SET
                product_id = '" . (int)$product_id . "',
                warehouse_id = '" . (int)$warehouse_id . "',
                quantity = '" . (float)$initial_quantity . "',
                reserved_quantity = 0,
                unit_cost = 0,
                created_date = NOW(),
                modified_date = NOW()
            ");
        }
    }

    /**
     * تحديث التكلفة المتوسطة المرجحة
     */
    private function updateWeightedAverageCost($product_id, $warehouse_id, $quantity, $unit_cost) {
        $stock_query = $this->db->query("
            SELECT quantity, unit_cost
            FROM " . DB_PREFIX . "stock
            WHERE product_id = '" . (int)$product_id . "'
            AND warehouse_id = '" . (int)$warehouse_id . "'
        ");

        if ($stock_query->num_rows) {
            $current_quantity = (float)$stock_query->row['quantity'];
            $current_cost = (float)$stock_query->row['unit_cost'];

            // حساب التكلفة المتوسطة المرجحة الجديدة
            $total_value = ($current_quantity * $current_cost) + ($quantity * $unit_cost);
            $total_quantity = $current_quantity + $quantity;

            $new_average_cost = $total_quantity > 0 ? $total_value / $total_quantity : 0;

            $this->db->query("
                UPDATE " . DB_PREFIX . "stock SET
                unit_cost = '" . (float)$new_average_cost . "'
                WHERE product_id = '" . (int)$product_id . "'
                AND warehouse_id = '" . (int)$warehouse_id . "'
            ");
        }
    }
}
