<?php
class ModelServiceWarranty extends Model {
    public function getTotalWarranties($filter_data = array()) {
        $sql = "SELECT COUNT(*) as total 
                FROM `cod_warranty` w
                LEFT JOIN `cod_customer` c ON (w.customer_id = c.customer_id)
                LEFT JOIN `cod_product` p ON (w.product_id = p.product_id)
                WHERE 1";

        if (!empty($filter_data['filter_order_id'])) {
            $sql .= " AND w.order_id = '" . (int)$filter_data['filter_order_id'] . "'";
        }

        if (!empty($filter_data['filter_status'])) {
            $sql .= " AND w.warranty_status = '" . $this->db->escape($filter_data['filter_status']) . "'";
        }

        $query = $this->db->query($sql);
        return $query->row['total'];
    }

    public function getWarranties($filter_data = array()) {
        $sql = "SELECT w.*, 
                CONCAT(c.firstname,' ',c.lastname) as customer_name,
                p.name as product_name
                FROM `cod_warranty` w
                LEFT JOIN `cod_customer` c ON (w.customer_id = c.customer_id)
                LEFT JOIN `cod_product` p ON (w.product_id = p.product_id)
                WHERE 1";

        if (!empty($filter_data['filter_order_id'])) {
            $sql .= " AND w.order_id = '" . (int)$filter_data['filter_order_id'] . "'";
        }

        if (!empty($filter_data['filter_status'])) {
            $sql .= " AND w.warranty_status = '" . $this->db->escape($filter_data['filter_status']) . "'";
        }

        $sort_data = array('order_id','customer_name','product_name','start_date','end_date','warranty_status');
        $sort = (isset($filter_data['sort']) && in_array($filter_data['sort'], $sort_data)) ? $filter_data['sort'] : 'start_date';
        $order = (isset($filter_data['order']) && $filter_data['order'] == 'desc') ? 'DESC' : 'ASC';

        $sql .= " ORDER BY " . $sort . " " . $order;

        $start = isset($filter_data['start']) ? (int)$filter_data['start'] : 0;
        $limit = isset($filter_data['limit']) ? (int)$filter_data['limit'] : 10;

        if ($start < 0) { $start = 0; }
        if ($limit < 1) { $limit = 10; }

        $sql .= " LIMIT " . $start . "," . $limit;

        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function getWarranty($warranty_id) {
        $query = $this->db->query("SELECT * FROM `cod_warranty` WHERE warranty_id = '" . (int)$warranty_id . "'");
        return $query->row;
    }

    public function addWarranty($data) {
        $this->db->query("INSERT INTO `cod_warranty` SET 
            order_id = '" . (int)$data['order_id'] . "',
            product_id = '" . (int)$data['product_id'] . "',
            customer_id = '" . (int)$data['customer_id'] . "',
            start_date = '" . $this->db->escape($data['start_date']) . "',
            end_date = '" . $this->db->escape($data['end_date']) . "',
            warranty_status = '" . $this->db->escape($data['warranty_status']) . "',
            notes = '" . $this->db->escape($data['notes']) . "'");

        return $this->db->getLastId();
    }

    public function editWarranty($warranty_id, $data) {
        $this->db->query("UPDATE `cod_warranty` SET
            order_id = '" . (int)$data['order_id'] . "',
            product_id = '" . (int)$data['product_id'] . "',
            customer_id = '" . (int)$data['customer_id'] . "',
            start_date = '" . $this->db->escape($data['start_date']) . "',
            end_date = '" . $this->db->escape($data['end_date']) . "',
            warranty_status = '" . $this->db->escape($data['warranty_status']) . "',
            notes = '" . $this->db->escape($data['notes']) . "'
            WHERE warranty_id = '" . (int)$warranty_id . "'");
    }

    public function deleteWarranty($warranty_id) {
        $this->db->query("DELETE FROM `cod_warranty` WHERE warranty_id = '" . (int)$warranty_id . "'");
    }
}
