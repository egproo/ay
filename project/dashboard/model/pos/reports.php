<?php
class ModelPosReports extends Model {
    public function getSalesReport($data = array()) {
        $sql = "SELECT o.order_id, o.date_added, o.total, o.payment_method, o.order_posuser_id, 
                CONCAT(o.firstname, ' ', o.lastname) AS customer_name, 
                CONCAT(u.firstname, ' ', u.lastname) AS user_name, 
                b.name AS branch_name, 
                t.name AS terminal_name, 
                (SELECT COUNT(*) FROM " . DB_PREFIX . "order_product WHERE order_id = o.order_id) AS products 
                FROM `" . DB_PREFIX . "order` o 
                LEFT JOIN " . DB_PREFIX . "user u ON (o.order_posuser_id = u.user_id) 
                LEFT JOIN " . DB_PREFIX . "pos_shift s ON (s.user_id = u.user_id AND o.date_added BETWEEN s.start_time AND IFNULL(s.end_time, NOW())) 
                LEFT JOIN " . DB_PREFIX . "branch b ON (s.branch_id = b.branch_id) 
                LEFT JOIN " . DB_PREFIX . "pos_terminal t ON (s.terminal_id = t.terminal_id) 
                WHERE o.order_status_id > 0";
        
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(o.date_added) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }
        
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(o.date_added) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }
        
        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND s.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }
        
        if (!empty($data['filter_terminal_id'])) {
            $sql .= " AND s.terminal_id = '" . (int)$data['filter_terminal_id'] . "'";
        }
        
        $sql .= " ORDER BY o.date_added DESC";
        
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
    
    public function getTotalSales($data = array()) {
        $sql = "SELECT COUNT(DISTINCT o.order_id) AS total 
                FROM `" . DB_PREFIX . "order` o 
                LEFT JOIN " . DB_PREFIX . "user u ON (o.order_posuser_id = u.user_id) 
                LEFT JOIN " . DB_PREFIX . "pos_shift s ON (s.user_id = u.user_id AND o.date_added BETWEEN s.start_time AND IFNULL(s.end_time, NOW())) 
                WHERE o.order_status_id > 0";
        
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(o.date_added) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }
        
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(o.date_added) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }
        
        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND s.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }
        
        if (!empty($data['filter_terminal_id'])) {
            $sql .= " AND s.terminal_id = '" . (int)$data['filter_terminal_id'] . "'";
        }
        
        $query = $this->db->query($sql);
        
        return $query->row['total'];
    }
    
    public function getSalesSummary($data = array()) {
        $sql = "SELECT COUNT(DISTINCT o.order_id) AS total_orders, SUM(o.total) AS total_sales 
                FROM `" . DB_PREFIX . "order` o 
                LEFT JOIN " . DB_PREFIX . "user u ON (o.order_posuser_id = u.user_id) 
                LEFT JOIN " . DB_PREFIX . "pos_shift s ON (s.user_id = u.user_id AND o.date_added BETWEEN s.start_time AND IFNULL(s.end_time, NOW())) 
                WHERE o.order_status_id > 0";
        
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(o.date_added) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }
        
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(o.date_added) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }
        
        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND s.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }
        
        if (!empty($data['filter_terminal_id'])) {
            $sql .= " AND s.terminal_id = '" . (int)$data['filter_terminal_id'] . "'";
        }
        
        $query = $this->db->query($sql);
        
        return $query->row;
    }
    
    public function getPaymentMethodSummary($data = array()) {
        $sql = "SELECT o.payment_method, COUNT(DISTINCT o.order_id) AS count, SUM(o.total) AS total 
                FROM `" . DB_PREFIX . "order` o 
                LEFT JOIN " . DB_PREFIX . "user u ON (o.order_posuser_id = u.user_id) 
                LEFT JOIN " . DB_PREFIX . "pos_shift s ON (s.user_id = u.user_id AND o.date_added BETWEEN s.start_time AND IFNULL(s.end_time, NOW())) 
                WHERE o.order_status_id > 0";
        
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(o.date_added) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }
        
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(o.date_added) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }
        
        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND s.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }
        
        if (!empty($data['filter_terminal_id'])) {
            $sql .= " AND s.terminal_id = '" . (int)$data['filter_terminal_id'] . "'";
        }
        
        $sql .= " GROUP BY o.payment_method";
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    public function getCashierReport($data = array()) {
        $sql = "SELECT s.*, 
                CONCAT(u.firstname, ' ', u.lastname) AS user_name, 
                b.name AS branch_name, 
                t.name AS terminal_name, 
                (SELECT COUNT(*) FROM `" . DB_PREFIX . "order` o WHERE o.order_posuser_id = s.user_id AND o.date_added BETWEEN s.start_time AND IFNULL(s.end_time, NOW())) AS sales_count, 
                (SELECT SUM(o.total) FROM `" . DB_PREFIX . "order` o WHERE o.order_posuser_id = s.user_id AND o.date_added BETWEEN s.start_time AND IFNULL(s.end_time, NOW())) AS sales_total, 
                IFNULL(TIMESTAMPDIFF(MINUTE, s.start_time, s.end_time), TIMESTAMPDIFF(MINUTE, s.start_time, NOW())) AS duration 
                FROM " . DB_PREFIX . "pos_shift s 
                LEFT JOIN " . DB_PREFIX . "user u ON (s.user_id = u.user_id) 
                LEFT JOIN " . DB_PREFIX . "branch b ON (s.branch_id = b.branch_id) 
                LEFT JOIN " . DB_PREFIX . "pos_terminal t ON (s.terminal_id = t.terminal_id) 
                WHERE 1";
        
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(s.start_time) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }
        
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(s.start_time) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }
        
        if (!empty($data['filter_user_id'])) {
            $sql .= " AND s.user_id = '" . (int)$data['filter_user_id'] . "'";
        }
        
        $sql .= " ORDER BY s.start_time DESC";
        
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
    
    public function getTotalCashierShifts($data = array()) {
        $sql = "SELECT COUNT(*) AS total 
                FROM " . DB_PREFIX . "pos_shift s 
                WHERE 1";
        
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(s.start_time) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }
        
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(s.start_time) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }
        
        if (!empty($data['filter_user_id'])) {
            $sql .= " AND s.user_id = '" . (int)$data['filter_user_id'] . "'";
        }
        
        $query = $this->db->query($sql);
        
        return $query->row['total'];
    }
    
    public function getCashierSummary($data = array()) {
        $sql = "SELECT s.user_id, 
                CONCAT(u.firstname, ' ', u.lastname) AS user_name, 
                COUNT(s.shift_id) AS shifts_count, 
                SUM(IFNULL(TIMESTAMPDIFF(MINUTE, s.start_time, s.end_time), TIMESTAMPDIFF(MINUTE, s.start_time, NOW()))) AS total_minutes, 
                (SELECT COUNT(*) FROM `" . DB_PREFIX . "order` o WHERE o.order_posuser_id = s.user_id AND o.date_added BETWEEN s.start_time AND IFNULL(s.end_time, NOW())) AS sales_count, 
                (SELECT SUM(o.total) FROM `" . DB_PREFIX . "order` o WHERE o.order_posuser_id = s.user_id AND o.date_added BETWEEN s.start_time AND IFNULL(s.end_time, NOW())) AS sales_total 
                FROM " . DB_PREFIX . "pos_shift s 
                LEFT JOIN " . DB_PREFIX . "user u ON (s.user_id = u.user_id) 
                WHERE 1";
        
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(s.start_time) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }
        
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(s.start_time) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }
        
        if (!empty($data['filter_user_id'])) {
            $sql .= " AND s.user_id = '" . (int)$data['filter_user_id'] . "'";
        }
        
        $sql .= " GROUP BY s.user_id";
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    public function getProductReport($data = array()) {
        $sql = "SELECT op.product_id, p.model, pd.name, 
                (SELECT name FROM " . DB_PREFIX . "category_description cd WHERE cd.category_id = (SELECT category_id FROM " . DB_PREFIX . "product_to_category p2c WHERE p2c.product_id = op.product_id LIMIT 1) AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "' LIMIT 1) AS category, 
                SUM(op.quantity) AS quantity, 
                SUM(op.total) AS total, 
                SUM(op.total) / SUM(op.quantity) AS avg_price 
                FROM " . DB_PREFIX . "order_product op 
                LEFT JOIN `" . DB_PREFIX . "order` o ON (op.order_id = o.order_id) 
                LEFT JOIN " . DB_PREFIX . "product p ON (op.product_id = p.product_id) 
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (op.product_id = pd.product_id) 
                LEFT JOIN " . DB_PREFIX . "user u ON (o.order_posuser_id = u.user_id) 
                LEFT JOIN " . DB_PREFIX . "pos_shift s ON (s.user_id = u.user_id AND o.date_added BETWEEN s.start_time AND IFNULL(s.end_time, NOW())) 
                WHERE o.order_status_id > 0 
                AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";
        
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(o.date_added) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }
        
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(o.date_added) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }
        
        if (!empty($data['filter_category_id'])) {
            $sql .= " AND op.product_id IN (SELECT product_id FROM " . DB_PREFIX . "product_to_category WHERE category_id = '" . (int)$data['filter_category_id'] . "')";
        }
        
        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND s.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }
        
        $sql .= " GROUP BY op.product_id";
        
        if (isset($data['sort'])) {
            if ($data['sort'] == 'name') {
                $sql .= " ORDER BY pd.name";
            } elseif ($data['sort'] == 'model') {
                $sql .= " ORDER BY p.model";
            } elseif ($data['sort'] == 'category') {
                $sql .= " ORDER BY category";
            } elseif ($data['sort'] == 'quantity') {
                $sql .= " ORDER BY quantity";
            } elseif ($data['sort'] == 'total') {
                $sql .= " ORDER BY total";
            } elseif ($data['sort'] == 'avg_price') {
                $sql .= " ORDER BY avg_price";
            } else {
                $sql .= " ORDER BY quantity";
            }
        } else {
            $sql .= " ORDER BY quantity";
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
    
    public function getTotalProductReport($data = array()) {
        $sql = "SELECT COUNT(DISTINCT op.product_id) AS total 
                FROM " . DB_PREFIX . "order_product op 
                LEFT JOIN `" . DB_PREFIX . "order` o ON (op.order_id = o.order_id) 
                LEFT JOIN " . DB_PREFIX . "user u ON (o.order_posuser_id = u.user_id) 
                LEFT JOIN " . DB_PREFIX . "pos_shift s ON (s.user_id = u.user_id AND o.date_added BETWEEN s.start_time AND IFNULL(s.end_time, NOW())) 
                WHERE o.order_status_id > 0";
        
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(o.date_added) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }
        
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(o.date_added) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }
        
        if (!empty($data['filter_category_id'])) {
            $sql .= " AND op.product_id IN (SELECT product_id FROM " . DB_PREFIX . "product_to_category WHERE category_id = '" . (int)$data['filter_category_id'] . "')";
        }
        
        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND s.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }
        
        $query = $this->db->query($sql);
        
        return $query->row['total'];
    }
    
    public function getTopCategories($data = array()) {
        $sql = "SELECT cd.name, SUM(op.quantity) AS quantity 
                FROM " . DB_PREFIX . "order_product op 
                LEFT JOIN `" . DB_PREFIX . "order` o ON (op.order_id = o.order_id) 
                LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (op.product_id = p2c.product_id) 
                LEFT JOIN " . DB_PREFIX . "category_description cd ON (p2c.category_id = cd.category_id) 
                LEFT JOIN " . DB_PREFIX . "user u ON (o.order_posuser_id = u.user_id) 
                LEFT JOIN " . DB_PREFIX . "pos_shift s ON (s.user_id = u.user_id AND o.date_added BETWEEN s.start_time AND IFNULL(s.end_time, NOW())) 
                WHERE o.order_status_id > 0 
                AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "'";
        
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(o.date_added) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }
        
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(o.date_added) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }
        
        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND s.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }
        
        $sql .= " GROUP BY cd.category_id ORDER BY quantity DESC LIMIT 5";
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    public function getDailySales($data = array()) {
        $sql = "SELECT DATE(o.date_added) AS date, SUM(o.total) AS total 
                FROM `" . DB_PREFIX . "order` o 
                LEFT JOIN " . DB_PREFIX . "user u ON (o.order_posuser_id = u.user_id) 
                LEFT JOIN " . DB_PREFIX . "pos_shift s ON (s.user_id = u.user_id AND o.date_added BETWEEN s.start_time AND IFNULL(s.end_time, NOW())) 
                WHERE o.order_status_id > 0";
        
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(o.date_added) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }
        
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(o.date_added) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }
        
        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND s.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }
        
        $sql .= " GROUP BY DATE(o.date_added) ORDER BY date ASC";
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
}