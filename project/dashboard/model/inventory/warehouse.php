<?php
/**
 * AYM ERP - Advanced Multi-Warehouse Management Model
 *
 * Professional warehouse management system with comprehensive features
 * Features:
 * - Multi-warehouse inventory tracking
 * - Real-time stock levels and movements
 * - Advanced location management (zones, aisles, shelves, bins)
 * - Automated reorder points and procurement
 * - Barcode/QR code integration
 * - Batch and serial number tracking
 * - Expiry date management
 * - Transfer management between warehouses
 * - Advanced reporting and analytics
 *
 * @author AYM ERP Development Team
 * @copyright 2024 AYM ERP
 * @license Commercial License
 * @version 1.0.0
 * @link https://aym-erp.com
 */

class ModelInventoryWarehouse extends Model {

    public function addWarehouse($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "warehouse SET
            name = '" . $this->db->escape($data['name']) . "',
            code = '" . $this->db->escape($data['code']) . "',
            address = '" . $this->db->escape($data['address']) . "',
            telephone = '" . $this->db->escape($data['telephone']) . "',
            manager = '" . $this->db->escape($data['manager']) . "',
            status = '" . (int)$data['status'] . "',
            date_added = NOW()");

        $warehouse_id = $this->db->getLastId();

        // Create default locations for the warehouse
        $this->createDefaultLocations($warehouse_id);

        return $warehouse_id;
    }

    public function editWarehouse($warehouse_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "warehouse SET
            name = '" . $this->db->escape($data['name']) . "',
            code = '" . $this->db->escape($data['code']) . "',
            address = '" . $this->db->escape($data['address']) . "',
            telephone = '" . $this->db->escape($data['telephone']) . "',
            manager = '" . $this->db->escape($data['manager']) . "',
            status = '" . (int)$data['status'] . "',
            date_modified = NOW()
            WHERE warehouse_id = '" . (int)$warehouse_id . "'");
    }

    public function deleteWarehouse($warehouse_id) {
        // Check if warehouse has stock
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "product_stock
                                  WHERE warehouse_id = '" . (int)$warehouse_id . "' AND quantity > 0");

        if ($query->row['total'] > 0) {
            throw new Exception('Cannot delete warehouse with existing stock');
        }

        // Delete warehouse and related data
        $this->db->query("DELETE FROM " . DB_PREFIX . "warehouse WHERE warehouse_id = '" . (int)$warehouse_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "warehouse_location WHERE warehouse_id = '" . (int)$warehouse_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_stock WHERE warehouse_id = '" . (int)$warehouse_id . "'");
    }

    public function getWarehouse($warehouse_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "warehouse
                                  WHERE warehouse_id = '" . (int)$warehouse_id . "'");

        return $query->row;
    }

    public function getWarehouses($data = array()) {
        $sql = "SELECT * FROM " . DB_PREFIX . "warehouse";

        $sort_data = array(
            'name',
            'code',
            'status',
            'date_added'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY name";
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

    public function getTotalWarehouses() {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "warehouse");

        return $query->row['total'];
    }

    public function getWarehouseStatistics() {
        $sql = "SELECT
                    COUNT(DISTINCT w.warehouse_id) as total_warehouses,
                    COUNT(DISTINCT ps.product_id) as total_products,
                    SUM(ps.quantity) as total_stock_quantity,
                    SUM(ps.quantity * p.price) as total_stock_value,
                    COUNT(CASE WHEN ps.quantity <= ps.reorder_level THEN 1 END) as low_stock_products,
                    COUNT(CASE WHEN ps.quantity = 0 THEN 1 END) as out_of_stock_products
                FROM " . DB_PREFIX . "warehouse w
                LEFT JOIN " . DB_PREFIX . "product_stock ps ON (w.warehouse_id = ps.warehouse_id)
                LEFT JOIN " . DB_PREFIX . "product p ON (ps.product_id = p.product_id)
                WHERE w.status = 1";

        $query = $this->db->query($sql);

        return $query->row;
    }

    public function getLowStockAlerts($limit = 20) {
        $sql = "SELECT
                    ps.product_id,
                    pd.name as product_name,
                    p.model,
                    p.sku,
                    w.name as warehouse_name,
                    ps.quantity,
                    ps.reorder_level,
                    ps.reorder_quantity,
                    (ps.reorder_level - ps.quantity) as shortage
                FROM " . DB_PREFIX . "product_stock ps
                LEFT JOIN " . DB_PREFIX . "product p ON (ps.product_id = p.product_id)
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "')
                LEFT JOIN " . DB_PREFIX . "warehouse w ON (ps.warehouse_id = w.warehouse_id)
                WHERE ps.quantity <= ps.reorder_level
                AND ps.reorder_level > 0
                AND w.status = 1
                ORDER BY (ps.reorder_level - ps.quantity) DESC
                LIMIT " . (int)$limit;

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getRecentMovements($limit = 10) {
        $sql = "SELECT
                    sm.movement_id,
                    sm.movement_type,
                    sm.quantity,
                    sm.reference,
                    sm.notes,
                    sm.date_added,
                    pd.name as product_name,
                    p.model,
                    w.name as warehouse_name,
                    u.username
                FROM " . DB_PREFIX . "stock_movement sm
                LEFT JOIN " . DB_PREFIX . "product p ON (sm.product_id = p.product_id)
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "')
                LEFT JOIN " . DB_PREFIX . "warehouse w ON (sm.warehouse_id = w.warehouse_id)
                LEFT JOIN " . DB_PREFIX . "user u ON (sm.user_id = u.user_id)
                ORDER BY sm.date_added DESC
                LIMIT " . (int)$limit;

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getExpiryAlerts($days = 30) {
        $sql = "SELECT
                    pb.batch_id,
                    pb.batch_number,
                    pb.expiry_date,
                    pb.quantity,
                    pd.name as product_name,
                    p.model,
                    w.name as warehouse_name,
                    DATEDIFF(pb.expiry_date, NOW()) as days_to_expiry
                FROM " . DB_PREFIX . "product_batch pb
                LEFT JOIN " . DB_PREFIX . "product p ON (pb.product_id = p.product_id)
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "')
                LEFT JOIN " . DB_PREFIX . "warehouse w ON (pb.warehouse_id = w.warehouse_id)
                WHERE pb.expiry_date IS NOT NULL
                AND pb.expiry_date <= DATE_ADD(NOW(), INTERVAL " . (int)$days . " DAY)
                AND pb.quantity > 0
                AND w.status = 1
                ORDER BY pb.expiry_date ASC";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getPendingTransfers($limit = 10) {
        $sql = "SELECT
                    st.transfer_id,
                    st.transfer_number,
                    st.status,
                    st.date_added,
                    st.expected_date,
                    wf.name as from_warehouse,
                    wt.name as to_warehouse,
                    COUNT(sti.product_id) as product_count,
                    SUM(sti.quantity) as total_quantity
                FROM " . DB_PREFIX . "stock_transfer st
                LEFT JOIN " . DB_PREFIX . "warehouse wf ON (st.from_warehouse_id = wf.warehouse_id)
                LEFT JOIN " . DB_PREFIX . "warehouse wt ON (st.to_warehouse_id = wt.warehouse_id)
                LEFT JOIN " . DB_PREFIX . "stock_transfer_item sti ON (st.transfer_id = sti.transfer_id)
                WHERE st.status IN ('pending', 'in_transit')
                GROUP BY st.transfer_id
                ORDER BY st.date_added DESC
                LIMIT " . (int)$limit;

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getWarehouseUtilization() {
        $sql = "SELECT
                    w.warehouse_id,
                    w.name,
                    w.capacity,
                    COUNT(DISTINCT ps.product_id) as products_stored,
                    SUM(ps.quantity) as total_quantity,
                    (SUM(ps.quantity) / w.capacity * 100) as utilization_percentage
                FROM " . DB_PREFIX . "warehouse w
                LEFT JOIN " . DB_PREFIX . "product_stock ps ON (w.warehouse_id = ps.warehouse_id)
                WHERE w.status = 1
                GROUP BY w.warehouse_id
                ORDER BY utilization_percentage DESC";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function createStockMovement($data) {
        // Start transaction
        $this->db->query("START TRANSACTION");

        try {
            // Insert movement record
            $this->db->query("INSERT INTO " . DB_PREFIX . "stock_movement SET
                product_id = '" . (int)$data['product_id'] . "',
                warehouse_id = '" . (int)$data['warehouse_id'] . "',
                location_id = '" . (int)($data['location_id'] ?? 0) . "',
                movement_type = '" . $this->db->escape($data['movement_type']) . "',
                quantity = '" . (float)$data['quantity'] . "',
                unit_cost = '" . (float)($data['unit_cost'] ?? 0) . "',
                reference = '" . $this->db->escape($data['reference'] ?? '') . "',
                notes = '" . $this->db->escape($data['notes'] ?? '') . "',
                batch_number = '" . $this->db->escape($data['batch_number'] ?? '') . "',
                serial_number = '" . $this->db->escape($data['serial_number'] ?? '') . "',
                user_id = '" . (int)$this->user->getId() . "',
                date_added = NOW()");

            $movement_id = $this->db->getLastId();

            // Update stock levels
            $this->updateProductStock($data['product_id'], $data['warehouse_id'], $data['movement_type'], $data['quantity']);

            $this->db->query("COMMIT");

            return $movement_id;

        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            throw $e;
        }
    }

    public function createTransfer($data) {
        // Start transaction
        $this->db->query("START TRANSACTION");

        try {
            // Generate transfer number
            $transfer_number = $this->generateTransferNumber();

            // Insert transfer record
            $this->db->query("INSERT INTO " . DB_PREFIX . "stock_transfer SET
                transfer_number = '" . $this->db->escape($transfer_number) . "',
                from_warehouse_id = '" . (int)$data['from_warehouse_id'] . "',
                to_warehouse_id = '" . (int)$data['to_warehouse_id'] . "',
                status = 'pending',
                notes = '" . $this->db->escape($data['notes'] ?? '') . "',
                expected_date = '" . $this->db->escape($data['expected_date'] ?? date('Y-m-d')) . "',
                user_id = '" . (int)$this->user->getId() . "',
                date_added = NOW()");

            $transfer_id = $this->db->getLastId();

            // Insert transfer items
            foreach ($data['products'] as $product) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "stock_transfer_item SET
                    transfer_id = '" . (int)$transfer_id . "',
                    product_id = '" . (int)$product['product_id'] . "',
                    quantity = '" . (float)$product['quantity'] . "',
                    batch_number = '" . $this->db->escape($product['batch_number'] ?? '') . "',
                    serial_number = '" . $this->db->escape($product['serial_number'] ?? '') . "'");

                // Reserve stock in source warehouse
                $this->reserveStock($product['product_id'], $data['from_warehouse_id'], $product['quantity']);
            }

            $this->db->query("COMMIT");

            return $transfer_id;

        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            throw $e;
        }
    }

    public function getProductByBarcode($barcode) {
        $query = $this->db->query("SELECT p.*, pd.name, pd.description
                                  FROM " . DB_PREFIX . "product p
                                  LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "')
                                  WHERE p.sku = '" . $this->db->escape($barcode) . "'
                                  OR p.upc = '" . $this->db->escape($barcode) . "'
                                  OR p.ean = '" . $this->db->escape($barcode) . "'
                                  AND p.status = 1");

        return $query->row;
    }

    public function getProductStockLevels($product_id) {
        $query = $this->db->query("SELECT
                                      ps.warehouse_id,
                                      w.name as warehouse_name,
                                      ps.quantity,
                                      ps.reserved_quantity,
                                      ps.available_quantity,
                                      ps.reorder_level,
                                      ps.reorder_quantity
                                  FROM " . DB_PREFIX . "product_stock ps
                                  LEFT JOIN " . DB_PREFIX . "warehouse w ON (ps.warehouse_id = w.warehouse_id)
                                  WHERE ps.product_id = '" . (int)$product_id . "'
                                  AND w.status = 1
                                  ORDER BY w.name");

        return $query->rows;
    }

    public function getProductMovements($product_id, $limit = 10) {
        $query = $this->db->query("SELECT
                                      sm.*,
                                      w.name as warehouse_name,
                                      u.username
                                  FROM " . DB_PREFIX . "stock_movement sm
                                  LEFT JOIN " . DB_PREFIX . "warehouse w ON (sm.warehouse_id = w.warehouse_id)
                                  LEFT JOIN " . DB_PREFIX . "user u ON (sm.user_id = u.user_id)
                                  WHERE sm.product_id = '" . (int)$product_id . "'
                                  ORDER BY sm.date_added DESC
                                  LIMIT " . (int)$limit);

        return $query->rows;
    }

    public function createStockAdjustment($data) {
        // Start transaction
        $this->db->query("START TRANSACTION");

        try {
            // Generate adjustment number
            $adjustment_number = $this->generateAdjustmentNumber();

            // Insert adjustment record
            $this->db->query("INSERT INTO " . DB_PREFIX . "stock_adjustment SET
                adjustment_number = '" . $this->db->escape($adjustment_number) . "',
                warehouse_id = '" . (int)$data['warehouse_id'] . "',
                reason_id = '" . (int)$data['reason_id'] . "',
                notes = '" . $this->db->escape($data['notes'] ?? '') . "',
                user_id = '" . (int)$this->user->getId() . "',
                date_added = NOW()");

            $adjustment_id = $this->db->getLastId();

            // Process each adjustment item
            foreach ($data['adjustments'] as $adjustment) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "stock_adjustment_item SET
                    adjustment_id = '" . (int)$adjustment_id . "',
                    product_id = '" . (int)$adjustment['product_id'] . "',
                    current_quantity = '" . (float)$adjustment['current_quantity'] . "',
                    adjusted_quantity = '" . (float)$adjustment['adjusted_quantity'] . "',
                    difference = '" . (float)($adjustment['adjusted_quantity'] - $adjustment['current_quantity']) . "',
                    unit_cost = '" . (float)($adjustment['unit_cost'] ?? 0) . "',
                    batch_number = '" . $this->db->escape($adjustment['batch_number'] ?? '') . "',
                    serial_number = '" . $this->db->escape($adjustment['serial_number'] ?? '') . "'");

                // Create stock movement
                $movement_type = ($adjustment['adjusted_quantity'] > $adjustment['current_quantity']) ? 'adjustment_in' : 'adjustment_out';
                $quantity = abs($adjustment['adjusted_quantity'] - $adjustment['current_quantity']);

                $this->createStockMovement(array(
                    'product_id' => $adjustment['product_id'],
                    'warehouse_id' => $data['warehouse_id'],
                    'movement_type' => $movement_type,
                    'quantity' => $quantity,
                    'reference' => $adjustment_number,
                    'notes' => 'Stock adjustment: ' . ($data['notes'] ?? ''),
                    'batch_number' => $adjustment['batch_number'] ?? '',
                    'serial_number' => $adjustment['serial_number'] ?? ''
                ));
            }

            $this->db->query("COMMIT");

            return $adjustment_id;

        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            throw $e;
        }
    }

    public function getAdjustmentReasons() {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "stock_adjustment_reason
                                  WHERE status = 1
                                  ORDER BY sort_order, name");

        return $query->rows;
    }

    public function getRecentAdjustments($limit = 20) {
        $query = $this->db->query("SELECT
                                      sa.adjustment_id,
                                      sa.adjustment_number,
                                      sa.date_added,
                                      sa.notes,
                                      w.name as warehouse_name,
                                      sar.name as reason_name,
                                      u.username,
                                      COUNT(sai.product_id) as item_count,
                                      SUM(ABS(sai.difference)) as total_adjustment
                                  FROM " . DB_PREFIX . "stock_adjustment sa
                                  LEFT JOIN " . DB_PREFIX . "warehouse w ON (sa.warehouse_id = w.warehouse_id)
                                  LEFT JOIN " . DB_PREFIX . "stock_adjustment_reason sar ON (sa.reason_id = sar.reason_id)
                                  LEFT JOIN " . DB_PREFIX . "user u ON (sa.user_id = u.user_id)
                                  LEFT JOIN " . DB_PREFIX . "stock_adjustment_item sai ON (sa.adjustment_id = sai.adjustment_id)
                                  GROUP BY sa.adjustment_id
                                  ORDER BY sa.date_added DESC
                                  LIMIT " . (int)$limit);

        return $query->rows;
    }

    public function getTotalProductsInWarehouse($warehouse_id) {
        $query = $this->db->query("SELECT COUNT(DISTINCT product_id) as total
                                  FROM " . DB_PREFIX . "product_stock
                                  WHERE warehouse_id = '" . (int)$warehouse_id . "'
                                  AND quantity > 0");

        return $query->row['total'];
    }

    public function getTotalValueInWarehouse($warehouse_id) {
        $query = $this->db->query("SELECT SUM(ps.quantity * p.price) as total_value
                                  FROM " . DB_PREFIX . "product_stock ps
                                  LEFT JOIN " . DB_PREFIX . "product p ON (ps.product_id = p.product_id)
                                  WHERE ps.warehouse_id = '" . (int)$warehouse_id . "'
                                  AND ps.quantity > 0");

        return $query->row['total_value'] ?? 0;
    }

    public function getProductStock($product_id, $warehouse_id) {
        $query = $this->db->query("SELECT quantity FROM " . DB_PREFIX . "product_stock
                                  WHERE product_id = '" . (int)$product_id . "'
                                  AND warehouse_id = '" . (int)$warehouse_id . "'");

        return $query->row['quantity'] ?? 0;
    }

    private function createDefaultLocations($warehouse_id) {
        // Create default zones
        $zones = array(
            array('name' => 'Receiving', 'code' => 'REC', 'type' => 'receiving'),
            array('name' => 'Storage', 'code' => 'STO', 'type' => 'storage'),
            array('name' => 'Picking', 'code' => 'PIC', 'type' => 'picking'),
            array('name' => 'Shipping', 'code' => 'SHI', 'type' => 'shipping'),
            array('name' => 'Quality Control', 'code' => 'QC', 'type' => 'quality')
        );

        foreach ($zones as $zone) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "warehouse_location SET
                warehouse_id = '" . (int)$warehouse_id . "',
                name = '" . $this->db->escape($zone['name']) . "',
                code = '" . $this->db->escape($zone['code']) . "',
                type = '" . $this->db->escape($zone['type']) . "',
                parent_id = 0,
                level = 1,
                status = 1,
                date_added = NOW()");
        }
    }

    private function updateProductStock($product_id, $warehouse_id, $movement_type, $quantity) {
        // Check if stock record exists
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_stock
                                  WHERE product_id = '" . (int)$product_id . "'
                                  AND warehouse_id = '" . (int)$warehouse_id . "'");

        if ($query->num_rows) {
            // Update existing stock
            $current_quantity = $query->row['quantity'];

            if (in_array($movement_type, array('in', 'purchase', 'transfer_in', 'adjustment_in', 'return'))) {
                $new_quantity = $current_quantity + $quantity;
            } else {
                $new_quantity = $current_quantity - $quantity;

                if ($new_quantity < 0) {
                    throw new Exception('Insufficient stock. Available: ' . $current_quantity . ', Required: ' . $quantity);
                }
            }

            $this->db->query("UPDATE " . DB_PREFIX . "product_stock SET
                quantity = '" . (float)$new_quantity . "',
                available_quantity = '" . (float)($new_quantity - $query->row['reserved_quantity']) . "',
                last_movement_date = NOW()
                WHERE product_id = '" . (int)$product_id . "'
                AND warehouse_id = '" . (int)$warehouse_id . "'");
        } else {
            // Create new stock record
            if (in_array($movement_type, array('in', 'purchase', 'transfer_in', 'adjustment_in', 'return'))) {
                $new_quantity = $quantity;
            } else {
                throw new Exception('Cannot perform outbound movement on non-existent stock');
            }

            $this->db->query("INSERT INTO " . DB_PREFIX . "product_stock SET
                product_id = '" . (int)$product_id . "',
                warehouse_id = '" . (int)$warehouse_id . "',
                quantity = '" . (float)$new_quantity . "',
                reserved_quantity = 0,
                available_quantity = '" . (float)$new_quantity . "',
                reorder_level = 0,
                reorder_quantity = 0,
                last_movement_date = NOW(),
                date_added = NOW()");
        }
    }

    private function reserveStock($product_id, $warehouse_id, $quantity) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_stock
                                  WHERE product_id = '" . (int)$product_id . "'
                                  AND warehouse_id = '" . (int)$warehouse_id . "'");

        if (!$query->num_rows) {
            throw new Exception('Product not found in warehouse');
        }

        $available_quantity = $query->row['available_quantity'];

        if ($available_quantity < $quantity) {
            throw new Exception('Insufficient available stock for reservation');
        }

        $new_reserved = $query->row['reserved_quantity'] + $quantity;
        $new_available = $query->row['quantity'] - $new_reserved;

        $this->db->query("UPDATE " . DB_PREFIX . "product_stock SET
            reserved_quantity = '" . (float)$new_reserved . "',
            available_quantity = '" . (float)$new_available . "'
            WHERE product_id = '" . (int)$product_id . "'
            AND warehouse_id = '" . (int)$warehouse_id . "'");
    }

    private function generateTransferNumber() {
        $query = $this->db->query("SELECT transfer_number FROM " . DB_PREFIX . "stock_transfer
                                  ORDER BY transfer_id DESC LIMIT 1");

        if ($query->num_rows) {
            $last_number = $query->row['transfer_number'];
            $number = (int)substr($last_number, 2) + 1;
        } else {
            $number = 1;
        }

        return 'TR' . str_pad($number, 6, '0', STR_PAD_LEFT);
    }

    private function generateAdjustmentNumber() {
        $query = $this->db->query("SELECT adjustment_number FROM " . DB_PREFIX . "stock_adjustment
                                  ORDER BY adjustment_id DESC LIMIT 1");

        if ($query->num_rows) {
            $last_number = $query->row['adjustment_number'];
            $number = (int)substr($last_number, 2) + 1;
        } else {
            $number = 1;
        }

        return 'ADJ' . str_pad($number, 6, '0', STR_PAD_LEFT);
    }

    public function getWarehouseLocations($warehouse_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "warehouse_location
                                  WHERE warehouse_id = '" . (int)$warehouse_id . "'
                                  AND status = 1
                                  ORDER BY level, sort_order, name");

        return $query->rows;
    }

    public function getProductBatches($product_id, $warehouse_id = null) {
        $sql = "SELECT pb.*, w.name as warehouse_name
                FROM " . DB_PREFIX . "product_batch pb
                LEFT JOIN " . DB_PREFIX . "warehouse w ON (pb.warehouse_id = w.warehouse_id)
                WHERE pb.product_id = '" . (int)$product_id . "'
                AND pb.quantity > 0";

        if ($warehouse_id) {
            $sql .= " AND pb.warehouse_id = '" . (int)$warehouse_id . "'";
        }

        $sql .= " ORDER BY pb.expiry_date ASC, pb.date_added ASC";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getProductSerials($product_id, $warehouse_id = null) {
        $sql = "SELECT ps.*, w.name as warehouse_name
                FROM " . DB_PREFIX . "product_serial ps
                LEFT JOIN " . DB_PREFIX . "warehouse w ON (ps.warehouse_id = w.warehouse_id)
                WHERE ps.product_id = '" . (int)$product_id . "'
                AND ps.status = 'available'";

        if ($warehouse_id) {
            $sql .= " AND ps.warehouse_id = '" . (int)$warehouse_id . "'";
        }

        $sql .= " ORDER BY ps.date_added ASC";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getStockValuation($warehouse_id = null) {
        $sql = "SELECT
                    p.product_id,
                    pd.name as product_name,
                    p.model,
                    p.sku,
                    ps.quantity,
                    p.price as unit_cost,
                    (ps.quantity * p.price) as total_value,
                    w.name as warehouse_name
                FROM " . DB_PREFIX . "product_stock ps
                LEFT JOIN " . DB_PREFIX . "product p ON (ps.product_id = p.product_id)
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "')
                LEFT JOIN " . DB_PREFIX . "warehouse w ON (ps.warehouse_id = w.warehouse_id)
                WHERE ps.quantity > 0
                AND w.status = 1";

        if ($warehouse_id) {
            $sql .= " AND ps.warehouse_id = '" . (int)$warehouse_id . "'";
        }

        $sql .= " ORDER BY total_value DESC";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getInventoryTurnover($warehouse_id = null, $period_days = 365) {
        $sql = "SELECT
                    p.product_id,
                    pd.name as product_name,
                    p.model,
                    AVG(ps.quantity) as avg_inventory,
                    SUM(CASE WHEN sm.movement_type IN ('out', 'sale', 'transfer_out') THEN sm.quantity ELSE 0 END) as total_sold,
                    (SUM(CASE WHEN sm.movement_type IN ('out', 'sale', 'transfer_out') THEN sm.quantity ELSE 0 END) / AVG(ps.quantity)) as turnover_ratio,
                    w.name as warehouse_name
                FROM " . DB_PREFIX . "product_stock ps
                LEFT JOIN " . DB_PREFIX . "product p ON (ps.product_id = p.product_id)
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "')
                LEFT JOIN " . DB_PREFIX . "warehouse w ON (ps.warehouse_id = w.warehouse_id)
                LEFT JOIN " . DB_PREFIX . "stock_movement sm ON (ps.product_id = sm.product_id AND ps.warehouse_id = sm.warehouse_id AND sm.date_added >= DATE_SUB(NOW(), INTERVAL " . (int)$period_days . " DAY))
                WHERE ps.quantity > 0
                AND w.status = 1";

        if ($warehouse_id) {
            $sql .= " AND ps.warehouse_id = '" . (int)$warehouse_id . "'";
        }

        $sql .= " GROUP BY p.product_id, ps.warehouse_id
                  HAVING avg_inventory > 0
                  ORDER BY turnover_ratio DESC";

        $query = $this->db->query($sql);

        return $query->rows;
    }
}
