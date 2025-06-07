<?php
/**
 * نموذج إدارة طلبات الشراء
 * يستخدم لإدارة طلبات الشراء وتجديد المخزون
 */
class ModelInventoryPurchaseOrder extends Model {
    /**
     * الحصول على قائمة طلبات الشراء
     */
    public function getPurchaseOrders($data = array()) {
        $sql = "SELECT po.purchase_order_id, po.po_number, po.supplier_id, po.branch_id, po.order_date, 
                    po.expected_date, po.status, po.notes, po.created_by, po.date_added, po.approved_by, po.date_approved,
                    s.name AS supplier_name, b.name AS branch_name, 
                    CONCAT(u.firstname, ' ', u.lastname) AS created_by_name,
                    (SELECT SUM(quantity * unit_price) FROM " . DB_PREFIX . "purchase_order_product WHERE purchase_order_id = po.purchase_order_id) AS total_amount,
                    (SELECT COUNT(*) FROM " . DB_PREFIX . "purchase_order_product WHERE purchase_order_id = po.purchase_order_id) AS total_items
                FROM " . DB_PREFIX . "purchase_order po
                LEFT JOIN " . DB_PREFIX . "supplier s ON (po.supplier_id = s.supplier_id)
                LEFT JOIN " . DB_PREFIX . "branch b ON (po.branch_id = b.branch_id)
                LEFT JOIN " . DB_PREFIX . "user u ON (po.created_by = u.user_id)
                WHERE 1=1";

        if (!empty($data['filter_po_number'])) {
            $sql .= " AND po.po_number LIKE '%" . $this->db->escape($data['filter_po_number']) . "%'";
        }

        if (!empty($data['filter_supplier'])) {
            $sql .= " AND po.supplier_id = '" . (int)$data['filter_supplier'] . "'";
        }

        if (!empty($data['filter_branch'])) {
            $sql .= " AND po.branch_id = '" . (int)$data['filter_branch'] . "'";
        }

        if (!empty($data['filter_date_from'])) {
            $sql .= " AND po.order_date >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }

        if (!empty($data['filter_date_to'])) {
            $sql .= " AND po.order_date <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND po.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        $sort_data = array(
            'po.po_number',
            's.name',
            'b.name',
            'po.order_date',
            'po.expected_date',
            'po.status',
            'po.date_added'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY po.date_added";
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
     * الحصول على إجمالي عدد طلبات الشراء
     */
    public function getTotalPurchaseOrders($data = array()) {
        $sql = "SELECT COUNT(*) AS total
                FROM " . DB_PREFIX . "purchase_order po
                LEFT JOIN " . DB_PREFIX . "supplier s ON (po.supplier_id = s.supplier_id)
                LEFT JOIN " . DB_PREFIX . "branch b ON (po.branch_id = b.branch_id)
                WHERE 1=1";

        if (!empty($data['filter_po_number'])) {
            $sql .= " AND po.po_number LIKE '%" . $this->db->escape($data['filter_po_number']) . "%'";
        }

        if (!empty($data['filter_supplier'])) {
            $sql .= " AND po.supplier_id = '" . (int)$data['filter_supplier'] . "'";
        }

        if (!empty($data['filter_branch'])) {
            $sql .= " AND po.branch_id = '" . (int)$data['filter_branch'] . "'";
        }

        if (!empty($data['filter_date_from'])) {
            $sql .= " AND po.order_date >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }

        if (!empty($data['filter_date_to'])) {
            $sql .= " AND po.order_date <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND po.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    /**
     * الحصول على معلومات طلب شراء محدد
     */
    public function getPurchaseOrder($purchase_order_id) {
        $query = $this->db->query("SELECT po.*, s.name AS supplier_name, b.name AS branch_name, 
                    CONCAT(u1.firstname, ' ', u1.lastname) AS created_by_name,
                    CONCAT(u2.firstname, ' ', u2.lastname) AS approved_by_name
                FROM " . DB_PREFIX . "purchase_order po
                LEFT JOIN " . DB_PREFIX . "supplier s ON (po.supplier_id = s.supplier_id)
                LEFT JOIN " . DB_PREFIX . "branch b ON (po.branch_id = b.branch_id)
                LEFT JOIN " . DB_PREFIX . "user u1 ON (po.created_by = u1.user_id)
                LEFT JOIN " . DB_PREFIX . "user u2 ON (po.approved_by = u2.user_id)
                WHERE po.purchase_order_id = '" . (int)$purchase_order_id . "'");

        return $query->row;
    }

    /**
     * الحصول على منتجات طلب شراء محدد
     */
    public function getPurchaseOrderProducts($purchase_order_id) {
        $query = $this->db->query("SELECT pop.*, pd.name AS product_name, p.model, p.sku, u.desc_en AS unit_name,
                    (pop.quantity * pop.unit_price) AS total_price
                FROM " . DB_PREFIX . "purchase_order_product pop
                LEFT JOIN " . DB_PREFIX . "product p ON (pop.product_id = p.product_id)
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '1')
                LEFT JOIN " . DB_PREFIX . "unit u ON (pop.unit_id = u.unit_id)
                WHERE pop.purchase_order_id = '" . (int)$purchase_order_id . "'
                ORDER BY pd.name ASC");

        return $query->rows;
    }

    /**
     * إضافة طلب شراء جديد
     */
    public function addPurchaseOrder($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "purchase_order SET 
                po_number = '" . $this->db->escape($data['po_number']) . "', 
                supplier_id = '" . (int)$data['supplier_id'] . "', 
                branch_id = '" . (int)$data['branch_id'] . "', 
                order_date = '" . $this->db->escape($data['order_date']) . "', 
                expected_date = '" . $this->db->escape($data['expected_date']) . "', 
                status = '" . $this->db->escape($data['status']) . "', 
                notes = '" . $this->db->escape($data['notes']) . "', 
                created_by = '" . (int)$this->user->getId() . "', 
                date_added = NOW()");

        $purchase_order_id = $this->db->getLastId();

        // إضافة المنتجات إلى طلب الشراء
        if (isset($data['products'])) {
            foreach ($data['products'] as $product) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "purchase_order_product SET 
                        purchase_order_id = '" . (int)$purchase_order_id . "', 
                        product_id = '" . (int)$product['product_id'] . "', 
                        unit_id = '" . (int)$product['unit_id'] . "', 
                        quantity = '" . (float)$product['quantity'] . "', 
                        received_quantity = '0', 
                        unit_price = '" . (float)$product['unit_price'] . "', 
                        tax_rate = '" . (float)$product['tax_rate'] . "', 
                        discount = '" . (float)$product['discount'] . "', 
                        notes = '" . $this->db->escape($product['notes']) . "'");
            }
        }

        return $purchase_order_id;
    }

    /**
     * تعديل طلب شراء
     */
    public function editPurchaseOrder($purchase_order_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "purchase_order SET 
                po_number = '" . $this->db->escape($data['po_number']) . "', 
                supplier_id = '" . (int)$data['supplier_id'] . "', 
                branch_id = '" . (int)$data['branch_id'] . "', 
                order_date = '" . $this->db->escape($data['order_date']) . "', 
                expected_date = '" . $this->db->escape($data['expected_date']) . "', 
                status = '" . $this->db->escape($data['status']) . "', 
                notes = '" . $this->db->escape($data['notes']) . "' 
                WHERE purchase_order_id = '" . (int)$purchase_order_id . "'");

        // حذف المنتجات الحالية
        $this->db->query("DELETE FROM " . DB_PREFIX . "purchase_order_product WHERE purchase_order_id = '" . (int)$purchase_order_id . "'");

        // إضافة المنتجات الجديدة
        if (isset($data['products'])) {
            foreach ($data['products'] as $product) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "purchase_order_product SET 
                        purchase_order_id = '" . (int)$purchase_order_id . "', 
                        product_id = '" . (int)$product['product_id'] . "', 
                        unit_id = '" . (int)$product['unit_id'] . "', 
                        quantity = '" . (float)$product['quantity'] . "', 
                        received_quantity = '" . (float)$product['received_quantity'] . "', 
                        unit_price = '" . (float)$product['unit_price'] . "', 
                        tax_rate = '" . (float)$product['tax_rate'] . "', 
                        discount = '" . (float)$product['discount'] . "', 
                        notes = '" . $this->db->escape($product['notes']) . "'");
            }
        }
    }

    /**
     * الموافقة على طلب الشراء
     */
    public function approvePurchaseOrder($purchase_order_id) {
        $this->db->query("UPDATE " . DB_PREFIX . "purchase_order SET 
                status = 'approved', 
                approved_by = '" . (int)$this->user->getId() . "', 
                date_approved = NOW() 
                WHERE purchase_order_id = '" . (int)$purchase_order_id . "'");
    }

    /**
     * تحديث حالة طلب الشراء إلى "تم الطلب"
     */
    public function orderPurchaseOrder($purchase_order_id) {
        $this->db->query("UPDATE " . DB_PREFIX . "purchase_order SET 
                status = 'ordered' 
                WHERE purchase_order_id = '" . (int)$purchase_order_id . "'");
    }

    /**
     * استلام طلب الشراء
     */
    public function receivePurchaseOrder($purchase_order_id, $data) {
        // الحصول على معلومات طلب الشراء
        $purchase_order_info = $this->getPurchaseOrder($purchase_order_id);
        
        // تحديث كميات الاستلام للمنتجات
        foreach ($data['products'] as $product) {
            $this->db->query("UPDATE " . DB_PREFIX . "purchase_order_product SET 
                    received_quantity = received_quantity + '" . (float)$product['received_quantity'] . "' 
                    WHERE purchase_order_id = '" . (int)$purchase_order_id . "' 
                    AND product_id = '" . (int)$product['product_id'] . "' 
                    AND unit_id = '" . (int)$product['unit_id'] . "'");
            
            // إضافة المنتج إلى المخزون
            if ($product['received_quantity'] > 0) {
                // إضافة حركة مخزون
                $this->db->query("INSERT INTO " . DB_PREFIX . "inventory_movement SET 
                        product_id = '" . (int)$product['product_id'] . "', 
                        branch_id = '" . (int)$purchase_order_info['branch_id'] . "', 
                        unit_id = '" . (int)$product['unit_id'] . "', 
                        movement_type = 'in', 
                        reference_type = 'purchase_order', 
                        reference_id = '" . (int)$purchase_order_id . "', 
                        quantity = '" . (float)$product['received_quantity'] . "', 
                        cost = '" . (float)$product['unit_price'] . "', 
                        notes = 'Purchase order receipt', 
                        created_by = '" . (int)$this->user->getId() . "', 
                        created_at = NOW()");
                
                // تحديث المخزون
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_inventory SET 
                        product_id = '" . (int)$product['product_id'] . "', 
                        branch_id = '" . (int)$purchase_order_info['branch_id'] . "', 
                        unit_id = '" . (int)$product['unit_id'] . "', 
                        quantity = '" . (float)$product['received_quantity'] . "', 
                        average_cost = '" . (float)$product['unit_price'] . "' 
                        ON DUPLICATE KEY UPDATE 
                        quantity = quantity + '" . (float)$product['received_quantity'] . "', 
                        average_cost = (quantity * average_cost + '" . (float)$product['received_quantity'] . "' * '" . (float)$product['unit_price'] . "') / (quantity + '" . (float)$product['received_quantity'] . "')");
                
                // إذا كان المنتج يستخدم تتبع الدفعات
                if (isset($product['batch_number']) && !empty($product['batch_number'])) {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "product_batch SET 
                            product_id = '" . (int)$product['product_id'] . "', 
                            branch_id = '" . (int)$purchase_order_info['branch_id'] . "', 
                            unit_id = '" . (int)$product['unit_id'] . "', 
                            batch_number = '" . $this->db->escape($product['batch_number']) . "', 
                            quantity = '" . (float)$product['received_quantity'] . "', 
                            manufacturing_date = " . ($product['manufacturing_date'] ? "'" . $this->db->escape($product['manufacturing_date']) . "'" : "NULL") . ", 
                            expiry_date = " . ($product['expiry_date'] ? "'" . $this->db->escape($product['expiry_date']) . "'" : "NULL") . ", 
                            status = 'active', 
                            notes = 'Purchase order receipt', 
                            created_by = '" . (int)$this->user->getId() . "', 
                            created_at = NOW()");
                    
                    $batch_id = $this->db->getLastId();
                    
                    // إضافة سجل في تاريخ الدفعة
                    $this->db->query("INSERT INTO " . DB_PREFIX . "product_batch_history SET 
                            batch_id = '" . (int)$batch_id . "', 
                            action = 'created', 
                            quantity = '" . (float)$product['received_quantity'] . "', 
                            user_id = '" . (int)$this->user->getId() . "', 
                            notes = 'Purchase order receipt', 
                            created_at = NOW()");
                }
            }
        }
        
        // تحديث حالة طلب الشراء
        $query = $this->db->query("SELECT 
                SUM(quantity) AS total_quantity, 
                SUM(received_quantity) AS total_received 
                FROM " . DB_PREFIX . "purchase_order_product 
                WHERE purchase_order_id = '" . (int)$purchase_order_id . "'");
        
        $row = $query->row;
        
        if ($row['total_received'] >= $row['total_quantity']) {
            // تم استلام جميع المنتجات
            $this->db->query("UPDATE " . DB_PREFIX . "purchase_order SET 
                    status = 'received' 
                    WHERE purchase_order_id = '" . (int)$purchase_order_id . "'");
        } elseif ($row['total_received'] > 0) {
            // تم استلام بعض المنتجات
            $this->db->query("UPDATE " . DB_PREFIX . "purchase_order SET 
                    status = 'partial' 
                    WHERE purchase_order_id = '" . (int)$purchase_order_id . "'");
        }
    }

    /**
     * إلغاء طلب الشراء
     */
    public function cancelPurchaseOrder($purchase_order_id) {
        $this->db->query("UPDATE " . DB_PREFIX . "purchase_order SET 
                status = 'cancelled' 
                WHERE purchase_order_id = '" . (int)$purchase_order_id . "'");
    }

    /**
     * حذف طلب شراء
     */
    public function deletePurchaseOrder($purchase_order_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "purchase_order WHERE purchase_order_id = '" . (int)$purchase_order_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "purchase_order_product WHERE purchase_order_id = '" . (int)$purchase_order_id . "'");
    }

    /**
     * الحصول على المنتجات المتاحة للشراء
     */
    public function getAvailableProducts($data = array()) {
        $sql = "SELECT p.product_id, pd.name, p.model, p.sku, 
                    pu.unit_id, u.desc_en AS unit_name, 
                    p.price, p.cost
                FROM " . DB_PREFIX . "product p
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '1')
                LEFT JOIN " . DB_PREFIX . "product_unit pu ON (p.product_id = pu.product_id)
                LEFT JOIN " . DB_PREFIX . "unit u ON (pu.unit_id = u.unit_id)
                WHERE p.status = '1'";
        
        if (!empty($data['filter_name'])) {
            $sql .= " AND (pd.name LIKE '%" . $this->db->escape($data['filter_name']) . "%' OR p.model LIKE '%" . $this->db->escape($data['filter_name']) . "%' OR p.sku LIKE '%" . $this->db->escape($data['filter_name']) . "%')";
        }
        
        if (!empty($data['filter_category_id'])) {
            $sql .= " AND p.product_id IN (SELECT product_id FROM " . DB_PREFIX . "product_to_category WHERE category_id = '" . (int)$data['filter_category_id'] . "')";
        }
        
        $sql .= " GROUP BY p.product_id, pu.unit_id";
        $sql .= " ORDER BY pd.name ASC";
        
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
     * الحصول على منتجات إعادة الطلب
     */
    public function getReorderProducts($branch_id) {
        $sql = "SELECT sl.product_id, pd.name AS product_name, p.model, p.sku, 
                    sl.unit_id, u.desc_en AS unit_name, 
                    sl.minimum_stock, sl.reorder_point, sl.maximum_stock, 
                    (SELECT COALESCE(SUM(pi.quantity), 0) FROM " . DB_PREFIX . "product_inventory pi 
                     WHERE pi.product_id = sl.product_id AND pi.branch_id = sl.branch_id AND pi.unit_id = sl.unit_id) AS current_stock,
                    (sl.reorder_point - (SELECT COALESCE(SUM(pi.quantity), 0) FROM " . DB_PREFIX . "product_inventory pi 
                     WHERE pi.product_id = sl.product_id AND pi.branch_id = sl.branch_id AND pi.unit_id = sl.unit_id)) AS quantity_to_order,
                    p.cost
                FROM " . DB_PREFIX . "product_stock_level sl
                LEFT JOIN " . DB_PREFIX . "product p ON (sl.product_id = p.product_id)
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '1')
                LEFT JOIN " . DB_PREFIX . "unit u ON (sl.unit_id = u.unit_id)
                WHERE sl.branch_id = '" . (int)$branch_id . "'
                AND sl.status = '1'
                HAVING current_stock <= sl.reorder_point
                ORDER BY quantity_to_order DESC";
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }

    /**
     * إنشاء رقم طلب شراء جديد
     */
    public function generatePONumber() {
        $query = $this->db->query("SELECT MAX(CAST(SUBSTRING(po_number, 4) AS UNSIGNED)) AS max_number FROM " . DB_PREFIX . "purchase_order WHERE po_number LIKE 'PO-%'");
        
        if ($query->row['max_number']) {
            $number = $query->row['max_number'] + 1;
        } else {
            $number = 1;
        }
        
        return 'PO-' . str_pad($number, 6, '0', STR_PAD_LEFT);
    }
}
