<?php
class ModelExtensionDashboardCodaym extends Model {
    public function getLatestOrders() {
        $query = $this->db->query("SELECT o.order_id, CONCAT(o.firstname, ' ', o.lastname) AS customer_name, o.total, os.name AS status, o.date_added FROM `" . DB_PREFIX . "order` o JOIN `" . DB_PREFIX . "order_status` os ON (o.order_status_id = os.order_status_id AND os.language_id = '" . (int)$this->config->get('config_language_id') . "') WHERE o.order_status_id > 0 ORDER BY o.date_added DESC LIMIT 20");
        return $query->rows;
    }
    
    
    public function getMissingOrders() {
        $query = $this->db->query("SELECT o.order_id, CONCAT(o.firstname, ' ', o.lastname) AS customer_name, o.total, os.name AS status, o.date_added FROM `" . DB_PREFIX . "order` o JOIN `" . DB_PREFIX . "order_status` os ON (o.order_status_id = os.order_status_id AND os.language_id = '" . (int)$this->config->get('config_language_id') . "') WHERE o.order_status_id = 0 ORDER BY o.date_added DESC");
        return $query->rows;
    }


    public function getAbandonedCarts() {
        // Your query to fetch abandoned carts
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "cart` WHERE customer_id != 0 AND date_added < DATE_SUB(NOW(), INTERVAL 1 DAY)");
        return $query->rows;
    }
}
