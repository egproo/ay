<?php
/**
 * نموذج إدارة الجرد الدوري والسنوي
 * يستخدم لإدارة عمليات جرد المخزون والتحقق من دقة بيانات المخزون
 */
class ModelInventoryStocktake extends Model {
    /**
     * الحصول على قائمة عمليات الجرد
     */
    public function getStocktakes($data = array()) {
        $sql = "SELECT s.stocktake_id, s.reference, s.branch_id, s.stocktake_date, s.type, s.status,
                    s.notes, s.created_by, s.date_added, s.completed_by, s.date_completed,
                    b.name AS branch_name,
                    CONCAT(u.firstname, ' ', u.lastname) AS created_by_name,
                    (SELECT COUNT(*) FROM " . DB_PREFIX . "stocktake_product WHERE stocktake_id = s.stocktake_id) AS total_items
                FROM " . DB_PREFIX . "stocktake s
                LEFT JOIN " . DB_PREFIX . "branch b ON (s.branch_id = b.branch_id)
                LEFT JOIN " . DB_PREFIX . "user u ON (s.created_by = u.user_id)
                WHERE 1=1";

        if (!empty($data['filter_reference'])) {
            $sql .= " AND s.reference LIKE '%" . $this->db->escape($data['filter_reference']) . "%'";
        }

        if (!empty($data['filter_branch'])) {
            $sql .= " AND s.branch_id = '" . (int)$data['filter_branch'] . "'";
        }

        if (!empty($data['filter_date_from'])) {
            $sql .= " AND s.stocktake_date >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }

        if (!empty($data['filter_date_to'])) {
            $sql .= " AND s.stocktake_date <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND s.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        if (!empty($data['filter_type'])) {
            $sql .= " AND s.type = '" . $this->db->escape($data['filter_type']) . "'";
        }

        $sort_data = array(
            's.reference',
            'b.name',
            's.stocktake_date',
            's.type',
            's.status',
            's.date_added'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY s.date_added";
        }

        if (isset($data['order']) && ($data['order'] == 'ASC')) {
            $sql .= " ASC";
        } else {
            $sql .= " DESC";
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
     * الحصول على إجمالي عدد عمليات الجرد
     */
    public function getTotalStocktakes($data = array()) {
        $sql = "SELECT COUNT(*) AS total
                FROM " . DB_PREFIX . "stocktake s
                LEFT JOIN " . DB_PREFIX . "branch b ON (s.branch_id = b.branch_id)
                WHERE 1=1";

        if (!empty($data['filter_reference'])) {
            $sql .= " AND s.reference LIKE '%" . $this->db->escape($data['filter_reference']) . "%'";
        }

        if (!empty($data['filter_branch'])) {
            $sql .= " AND s.branch_id = '" . (int)$data['filter_branch'] . "'";
        }

        if (!empty($data['filter_date_from'])) {
            $sql .= " AND s.stocktake_date >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }

        if (!empty($data['filter_date_to'])) {
            $sql .= " AND s.stocktake_date <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND s.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        if (!empty($data['filter_type'])) {
            $sql .= " AND s.type = '" . $this->db->escape($data['filter_type']) . "'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    /**
     * الحصول على معلومات عملية جرد محددة
     */
    public function getStocktake($stocktake_id) {
        $query = $this->db->query("SELECT s.*, b.name AS branch_name,
                    CONCAT(u1.firstname, ' ', u1.lastname) AS created_by_name,
                    CONCAT(u2.firstname, ' ', u2.lastname) AS completed_by_name
                FROM " . DB_PREFIX . "stocktake s
                LEFT JOIN " . DB_PREFIX . "branch b ON (s.branch_id = b.branch_id)
                LEFT JOIN " . DB_PREFIX . "user u1 ON (s.created_by = u1.user_id)
                LEFT JOIN " . DB_PREFIX . "user u2 ON (s.completed_by = u2.user_id)
                WHERE s.stocktake_id = '" . (int)$stocktake_id . "'");

        return $query->row;
    }

    /**
     * الحصول على منتجات عملية جرد محددة
     */
    public function getStocktakeProducts($stocktake_id) {
        $query = $this->db->query("SELECT sp.*, pd.name AS product_name, p.model, p.sku, u.desc_en AS unit_name,
                    (SELECT quantity FROM " . DB_PREFIX . "product_inventory
                     WHERE product_id = sp.product_id
                     AND branch_id = (SELECT branch_id FROM " . DB_PREFIX . "stocktake WHERE stocktake_id = '" . (int)$stocktake_id . "')
                     AND unit_id = sp.unit_id) AS system_quantity
                FROM " . DB_PREFIX . "stocktake_product sp
                LEFT JOIN " . DB_PREFIX . "product p ON (sp.product_id = p.product_id)
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '1')
                LEFT JOIN " . DB_PREFIX . "unit u ON (sp.unit_id = u.unit_id)
                WHERE sp.stocktake_id = '" . (int)$stocktake_id . "'
                ORDER BY pd.name ASC");

        return $query->rows;
    }

    /**
     * إضافة عملية جرد جديدة
     */
    public function addStocktake($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "stocktake SET
                reference = '" . $this->db->escape($data['reference']) . "',
                branch_id = '" . (int)$data['branch_id'] . "',
                stocktake_date = '" . $this->db->escape($data['stocktake_date']) . "',
                type = '" . $this->db->escape($data['type']) . "',
                status = '" . $this->db->escape($data['status']) . "',
                notes = '" . $this->db->escape($data['notes']) . "',
                created_by = '" . (int)$this->user->getId() . "',
                date_added = NOW()");

        $stocktake_id = $this->db->getLastId();

        // إضافة المنتجات إلى عملية الجرد
        if (isset($data['products'])) {
            foreach ($data['products'] as $product) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "stocktake_product SET
                        stocktake_id = '" . (int)$stocktake_id . "',
                        product_id = '" . (int)$product['product_id'] . "',
                        unit_id = '" . (int)$product['unit_id'] . "',
                        expected_quantity = '" . (float)$product['expected_quantity'] . "',
                        counted_quantity = '" . (float)$product['counted_quantity'] . "',
                        variance_quantity = '" . (float)($product['counted_quantity'] - $product['expected_quantity']) . "',
                        notes = '" . $this->db->escape($product['notes']) . "'");
            }
        }

        return $stocktake_id;
    }

    /**
     * تعديل عملية جرد
     */
    public function editStocktake($stocktake_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "stocktake SET
                reference = '" . $this->db->escape($data['reference']) . "',
                branch_id = '" . (int)$data['branch_id'] . "',
                stocktake_date = '" . $this->db->escape($data['stocktake_date']) . "',
                type = '" . $this->db->escape($data['type']) . "',
                status = '" . $this->db->escape($data['status']) . "',
                notes = '" . $this->db->escape($data['notes']) . "'
                WHERE stocktake_id = '" . (int)$stocktake_id . "'");

        // حذف المنتجات الحالية
        $this->db->query("DELETE FROM " . DB_PREFIX . "stocktake_product WHERE stocktake_id = '" . (int)$stocktake_id . "'");

        // إضافة المنتجات الجديدة
        if (isset($data['products'])) {
            foreach ($data['products'] as $product) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "stocktake_product SET
                        stocktake_id = '" . (int)$stocktake_id . "',
                        product_id = '" . (int)$product['product_id'] . "',
                        unit_id = '" . (int)$product['unit_id'] . "',
                        expected_quantity = '" . (float)$product['expected_quantity'] . "',
                        counted_quantity = '" . (float)$product['counted_quantity'] . "',
                        variance_quantity = '" . (float)($product['counted_quantity'] - $product['expected_quantity']) . "',
                        notes = '" . $this->db->escape($product['notes']) . "'");
            }
        }
    }

    /**
     * إكمال عملية الجرد
     */
    public function completeStocktake($stocktake_id, $data) {
        // تحديث حالة عملية الجرد
        $this->db->query("UPDATE " . DB_PREFIX . "stocktake SET
                status = 'completed',
                completed_by = '" . (int)$this->user->getId() . "',
                date_completed = NOW()
                WHERE stocktake_id = '" . (int)$stocktake_id . "'");

        // الحصول على معلومات عملية الجرد
        $stocktake_info = $this->getStocktake($stocktake_id);

        // الحصول على منتجات عملية الجرد
        $stocktake_products = $this->getStocktakeProducts($stocktake_id);

        // تعديل المخزون بناءً على نتائج الجرد
        foreach ($stocktake_products as $product) {
            $variance = $product['counted_quantity'] - $product['expected_quantity'];

            if ($variance != 0) {
                // إضافة حركة مخزون للتعديل
                $movement_type = ($variance > 0) ? 'in' : 'out';
                $quantity = abs($variance);

                $this->db->query("INSERT INTO " . DB_PREFIX . "inventory_movement SET
                        product_id = '" . (int)$product['product_id'] . "',
                        branch_id = '" . (int)$stocktake_info['branch_id'] . "',
                        unit_id = '" . (int)$product['unit_id'] . "',
                        movement_type = '" . $movement_type . "',
                        reference_type = 'stocktake',
                        reference_id = '" . (int)$stocktake_id . "',
                        quantity = '" . (float)$quantity . "',
                        cost = '0.0000',
                        notes = 'Stocktake adjustment',
                        created_by = '" . (int)$this->user->getId() . "',
                        created_at = NOW()");

                // تحديث المخزون
                if ($movement_type == 'in') {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "product_inventory SET
                            product_id = '" . (int)$product['product_id'] . "',
                            branch_id = '" . (int)$stocktake_info['branch_id'] . "',
                            unit_id = '" . (int)$product['unit_id'] . "',
                            quantity = '" . (float)$quantity . "'
                            ON DUPLICATE KEY UPDATE quantity = quantity + '" . (float)$quantity . "'");
                } else {
                    $this->db->query("UPDATE " . DB_PREFIX . "product_inventory SET
                            quantity = quantity - '" . (float)$quantity . "'
                            WHERE product_id = '" . (int)$product['product_id'] . "'
                            AND branch_id = '" . (int)$stocktake_info['branch_id'] . "'
                            AND unit_id = '" . (int)$product['unit_id'] . "'");
                }
            }
        }
    }

    /**
     * إلغاء عملية الجرد
     */
    public function cancelStocktake($stocktake_id) {
        $this->db->query("UPDATE " . DB_PREFIX . "stocktake SET
                status = 'cancelled'
                WHERE stocktake_id = '" . (int)$stocktake_id . "'");
    }

    /**
     * حذف عملية جرد
     */
    public function deleteStocktake($stocktake_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "stocktake WHERE stocktake_id = '" . (int)$stocktake_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "stocktake_product WHERE stocktake_id = '" . (int)$stocktake_id . "'");
    }

    /**
     * الحصول على المنتجات المتاحة للجرد في فرع محدد
     */
    public function getAvailableProducts($branch_id, $category_id = 0) {
        $sql = "SELECT p.product_id, pd.name, p.model, p.sku,
                    pi.unit_id, u.desc_en AS unit_name, pi.quantity
                FROM " . DB_PREFIX . "product p
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '1')
                LEFT JOIN " . DB_PREFIX . "product_inventory pi ON (p.product_id = pi.product_id AND pi.branch_id = '" . (int)$branch_id . "')
                LEFT JOIN " . DB_PREFIX . "unit u ON (pi.unit_id = u.unit_id)";

        if ($category_id > 0) {
            $sql .= " LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id)";
        }

        $sql .= " WHERE pi.branch_id = '" . (int)$branch_id . "'";

        if ($category_id > 0) {
            $sql .= " AND p2c.category_id = '" . (int)$category_id . "'";
        }

        $sql .= " ORDER BY pd.name ASC";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    /**
     * الحصول على وحدات منتج محدد
     */
    public function getProductUnits($product_id) {
        $query = $this->db->query("SELECT u.unit_id, u.desc_en AS name
                FROM " . DB_PREFIX . "product_unit pu
                LEFT JOIN " . DB_PREFIX . "unit u ON (pu.unit_id = u.unit_id)
                WHERE pu.product_id = '" . (int)$product_id . "'
                ORDER BY u.desc_en ASC");

        return $query->rows;
    }

    /**
     * الحصول على كمية المخزون الحالية لمنتج في فرع ووحدة محددة
     */
    public function getProductQuantity($product_id, $branch_id, $unit_id) {
        $query = $this->db->query("SELECT quantity
                FROM " . DB_PREFIX . "product_inventory
                WHERE product_id = '" . (int)$product_id . "'
                AND branch_id = '" . (int)$branch_id . "'
                AND unit_id = '" . (int)$unit_id . "'");

        if ($query->num_rows) {
            return $query->row['quantity'];
        } else {
            return 0;
        }
    }

    /**
     * تحديث منتج في عملية الجرد
     */
    public function updateStocktakeProduct($stocktake_id, $data) {
        // الحصول على معلومات عملية الجرد
        $stocktake_info = $this->getStocktake($stocktake_id);

        if (!$stocktake_info) {
            return false;
        }

        // الحصول على الكمية المتوقعة من المخزون
        $expected_quantity = $this->getProductQuantity($data['product_id'], $stocktake_info['branch_id'], $data['unit_id']);

        // التحقق مما إذا كان المنتج موجودًا بالفعل في عملية الجرد
        $query = $this->db->query("SELECT stocktake_product_id
                FROM " . DB_PREFIX . "stocktake_product
                WHERE stocktake_id = '" . (int)$stocktake_id . "'
                AND product_id = '" . (int)$data['product_id'] . "'
                AND unit_id = '" . (int)$data['unit_id'] . "'");

        if ($query->num_rows) {
            // تحديث المنتج الموجود
            $this->db->query("UPDATE " . DB_PREFIX . "stocktake_product SET
                    expected_quantity = '" . (float)$expected_quantity . "',
                    counted_quantity = '" . (float)$data['counted_quantity'] . "',
                    variance_quantity = '" . (float)($data['counted_quantity'] - $expected_quantity) . "',
                    notes = '" . $this->db->escape($data['notes']) . "'
                    WHERE stocktake_id = '" . (int)$stocktake_id . "'
                    AND product_id = '" . (int)$data['product_id'] . "'
                    AND unit_id = '" . (int)$data['unit_id'] . "'");
        } else {
            // إضافة منتج جديد
            $this->db->query("INSERT INTO " . DB_PREFIX . "stocktake_product SET
                    stocktake_id = '" . (int)$stocktake_id . "',
                    product_id = '" . (int)$data['product_id'] . "',
                    unit_id = '" . (int)$data['unit_id'] . "',
                    expected_quantity = '" . (float)$expected_quantity . "',
                    counted_quantity = '" . (float)$data['counted_quantity'] . "',
                    variance_quantity = '" . (float)($data['counted_quantity'] - $expected_quantity) . "',
                    notes = '" . $this->db->escape($data['notes']) . "'");
        }

        return true;
    }

    /**
     * الحصول على معلومات منتج محدد
     */
    public function getProductInfo($product_id, $unit_id, $branch_id) {
        $query = $this->db->query("SELECT p.product_id, pd.name, p.model, p.sku,
                u.unit_id, u.desc_en AS unit_name,
                IFNULL(pi.quantity, 0) AS quantity
                FROM " . DB_PREFIX . "product p
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '1')
                LEFT JOIN " . DB_PREFIX . "unit u ON (u.unit_id = '" . (int)$unit_id . "')
                LEFT JOIN " . DB_PREFIX . "product_inventory pi ON (p.product_id = pi.product_id AND pi.branch_id = '" . (int)$branch_id . "' AND pi.unit_id = '" . (int)$unit_id . "')
                WHERE p.product_id = '" . (int)$product_id . "'");

        return $query->row;
    }
}
