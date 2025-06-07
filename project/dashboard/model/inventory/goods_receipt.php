<?php
class ModelInventoryGoodsReceipt extends Model {
    public function addGoodsReceipt($data) {
        // إدخال البيانات في جدول goods_receipt
        $this->db->query("INSERT INTO " . DB_PREFIX . "goods_receipt 
            SET receipt_number = '" . $this->db->escape($data['receipt_number']) . "',
                po_id = '" . (int)$data['purchase_order_id'] . "', 
                user_id = '" . (int)$data['user_id'] . "', 
                receipt_date = '" . $this->db->escape($data['receipt_date']) . "', 
                status = '" . $this->db->escape($data['status']) . "', 
                notes = '" . $this->db->escape($data['notes']) . "', 
                created_at = NOW(), 
                updated_at = NOW()");

        $receipt_id = $this->db->getLastId();

        foreach ($data['receipt_items'] as $item) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "goods_receipt_item 
                SET receipt_id = '" . (int)$receipt_id . "',
                    po_item_id = '" . (int)$item['po_item_id'] . "',
                    product_id = '" . (int)$item['product_id'] . "', 
                    quantity_ordered = '" . (float)$item['quantity_ordered'] . "', 
                    quantity_received = '" . (float)$item['quantity_received'] . "', 
                    unit_id = '" . (int)$item['unit_id'] . "', 
                    batch_number = '" . $this->db->escape($item['batch_number']) . "', 
                    expiry_date = '" . $this->db->escape($item['expiry_date']) . "', 
                    notes = '" . $this->db->escape($item['notes']) . "'");

            if (!empty($data['purchase_order_id'])) {
                $this->updatePOItemQuantity($item['po_item_id'], $item['quantity_received']);
            }

            $this->updateProductInventory($item['product_id'], $item['quantity_received'], $item['unit_id'], $data['branch_id']);
            $this->addProductMovement($item['product_id'], $item['quantity_received'], $item['unit_id'], $data['branch_id']);
            $this->addInventoryHistory($item['product_id'], $item['quantity_received'], $item['unit_id'], $data['branch_id'], $data['user_id']);
            $this->addQualityInspection($receipt_id, $item);
        }

        return $receipt_id;
    }

    private function updatePOItemQuantity($po_item_id, $quantity_received) {
        $this->db->query("UPDATE " . DB_PREFIX . "purchase_order_item 
            SET quantity_remaining = quantity_remaining - '" . (float)$quantity_received . "' 
            WHERE po_item_id = '" . (int)$po_item_id . "'");
    }

    // دالة لتحديث مخزون المنتج
    private function updateProductInventory($product_id, $quantity_received, $unit_id, $branch_id) {
        // تحقق إذا كان المنتج موجودًا في مخزون الفرع المحدد
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_inventory WHERE product_id = '" . (int)$product_id . "' AND branch_id = '" . (int)$branch_id . "' AND unit_id = '" . (int)$unit_id . "'");

        if ($query->num_rows) {
            // إذا كان المنتج موجودًا، تحديث الكمية في المخزون
            $this->db->query("UPDATE " . DB_PREFIX . "product_inventory 
                SET quantity = quantity + '" . (float)$quantity_received . "', 
                    quantity_available = quantity_available + '" . (float)$quantity_received . "' 
                WHERE product_id = '" . (int)$product_id . "' AND branch_id = '" . (int)$branch_id . "' AND unit_id = '" . (int)$unit_id . "'");
        } else {
            // إذا لم يكن المنتج موجودًا، إضافة سجل جديد للمخزون
            $this->db->query("INSERT INTO " . DB_PREFIX . "product_inventory 
                SET product_id = '" . (int)$product_id . "', 
                    branch_id = '" . (int)$branch_id . "', 
                    unit_id = '" . (int)$unit_id . "', 
                    quantity = '" . (float)$quantity_received . "', 
                    quantity_available = '" . (float)$quantity_received . "'");
        }
    }

    // دالة لتسجيل حركة المنتج
    private function addProductMovement($product_id, $quantity_received, $unit_id, $branch_id) {
        // إضافة حركة للمخزون في جدول product_movement
        $this->db->query("INSERT INTO " . DB_PREFIX . "product_movement 
            SET product_id = '" . (int)$product_id . "', 
                type = 'purchase', 
                quantity = '" . (float)$quantity_received . "', 
                unit_id = '" . (int)$unit_id . "', 
                branch_id = '" . (int)$branch_id . "', 
                date_added = NOW()");
    }

    // دالة لتسجيل سجل التغييرات في المخزون
    private function addInventoryHistory($product_id, $quantity_received, $unit_id, $branch_id, $user_id) {
        // إضافة سجل التغييرات في جدول inventory_history
        $this->db->query("INSERT INTO " . DB_PREFIX . "inventory_history 
            SET product_id = '" . (int)$product_id . "', 
                unit_id = '" . (int)$unit_id . "', 
                branch_id = '" . (int)$branch_id . "', 
                quantity_change = '" . (float)$quantity_received . "', 
                action_type = 'add', 
                user_id = '" . (int)$user_id . "', 
                date_added = NOW()");
    }

    // دالة لإضافة فحص الجودة
    private function addQualityInspection($receipt_id, $item) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "goods_receipt_quality 
            SET receipt_id = '" . (int)$receipt_id . "',
                product_id = '" . (int)$item['product_id'] . "', 
                quality_status = '" . $this->db->escape($item['quality_status']) . "', 
                inspection_grade = '" . $this->db->escape($item['inspection_grade']) . "', 
                inspection_notes = '" . $this->db->escape($item['inspection_notes']) . "', 
                date_inspected = NOW()");
    }
}

