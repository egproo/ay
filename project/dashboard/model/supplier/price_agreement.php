<?php
/**
 * AYM ERP - Supplier Price Agreement Model
 * 
 * @author AYM ERP Development Team
 * @copyright 2024 AYM ERP
 * @license Commercial License
 * @version 1.0.0
 * @link https://aym-erp.com
 */

class ModelSupplierPriceAgreement extends Model {
    
    public function addPriceAgreement($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "supplier_price_agreement SET 
            agreement_name = '" . $this->db->escape($data['agreement_name']) . "', 
            supplier_id = '" . (int)$data['supplier_id'] . "', 
            description = '" . $this->db->escape($data['description']) . "', 
            start_date = '" . $this->db->escape($data['start_date']) . "', 
            end_date = '" . $this->db->escape($data['end_date']) . "', 
            terms = '" . $this->db->escape($data['terms']) . "', 
            status = '" . (int)$data['status'] . "', 
            date_added = NOW(), 
            date_modified = NOW()");
        
        $price_agreement_id = $this->db->getLastId();
        
        // Add price agreement items if provided
        if (isset($data['price_agreement_items'])) {
            foreach ($data['price_agreement_items'] as $item) {
                $this->addPriceAgreementItem($price_agreement_id, $item);
            }
        }
        
        return $price_agreement_id;
    }
    
    public function editPriceAgreement($price_agreement_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "supplier_price_agreement SET 
            agreement_name = '" . $this->db->escape($data['agreement_name']) . "', 
            supplier_id = '" . (int)$data['supplier_id'] . "', 
            description = '" . $this->db->escape($data['description']) . "', 
            start_date = '" . $this->db->escape($data['start_date']) . "', 
            end_date = '" . $this->db->escape($data['end_date']) . "', 
            terms = '" . $this->db->escape($data['terms']) . "', 
            status = '" . (int)$data['status'] . "', 
            date_modified = NOW() 
            WHERE price_agreement_id = '" . (int)$price_agreement_id . "'");
        
        // Delete existing items
        $this->db->query("DELETE FROM " . DB_PREFIX . "supplier_price_agreement_item WHERE price_agreement_id = '" . (int)$price_agreement_id . "'");
        
        // Add new items
        if (isset($data['price_agreement_items'])) {
            foreach ($data['price_agreement_items'] as $item) {
                $this->addPriceAgreementItem($price_agreement_id, $item);
            }
        }
    }
    
    public function deletePriceAgreement($price_agreement_id) {
        // Delete items first
        $this->db->query("DELETE FROM " . DB_PREFIX . "supplier_price_agreement_item WHERE price_agreement_id = '" . (int)$price_agreement_id . "'");
        
        // Delete agreement
        $this->db->query("DELETE FROM " . DB_PREFIX . "supplier_price_agreement WHERE price_agreement_id = '" . (int)$price_agreement_id . "'");
    }
    
    public function getPriceAgreement($price_agreement_id) {
        $query = $this->db->query("SELECT DISTINCT *, 
            (SELECT name FROM " . DB_PREFIX . "supplier s WHERE s.supplier_id = pa.supplier_id) AS supplier_name 
            FROM " . DB_PREFIX . "supplier_price_agreement pa 
            WHERE pa.price_agreement_id = '" . (int)$price_agreement_id . "'");
        
        return $query->row;
    }
    
    public function getPriceAgreements($data = array()) {
        $sql = "SELECT pa.*, s.name as supplier_name 
                FROM " . DB_PREFIX . "supplier_price_agreement pa 
                LEFT JOIN " . DB_PREFIX . "supplier s ON (pa.supplier_id = s.supplier_id)";
        
        $sql .= " WHERE 1=1";
        
        if (!empty($data['filter_agreement_name'])) {
            $sql .= " AND pa.agreement_name LIKE '" . $this->db->escape($data['filter_agreement_name']) . "%'";
        }
        
        if (!empty($data['filter_supplier_id'])) {
            $sql .= " AND pa.supplier_id = '" . (int)$data['filter_supplier_id'] . "'";
        }
        
        if (isset($data['filter_status']) && $data['filter_status'] !== '') {
            $sql .= " AND pa.status = '" . (int)$data['filter_status'] . "'";
        }
        
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(pa.start_date) >= DATE('" . $this->db->escape($data['filter_date_start']) . "')";
        }
        
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(pa.end_date) <= DATE('" . $this->db->escape($data['filter_date_end']) . "')";
        }
        
        $sort_data = array(
            'pa.agreement_name',
            's.name',
            'pa.start_date',
            'pa.end_date',
            'pa.status',
            'pa.date_added'
        );
        
        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY pa.agreement_name";
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
    
    public function getTotalPriceAgreements($data = array()) {
        $sql = "SELECT COUNT(*) AS total 
                FROM " . DB_PREFIX . "supplier_price_agreement pa 
                LEFT JOIN " . DB_PREFIX . "supplier s ON (pa.supplier_id = s.supplier_id)";
        
        $sql .= " WHERE 1=1";
        
        if (!empty($data['filter_agreement_name'])) {
            $sql .= " AND pa.agreement_name LIKE '" . $this->db->escape($data['filter_agreement_name']) . "%'";
        }
        
        if (!empty($data['filter_supplier_id'])) {
            $sql .= " AND pa.supplier_id = '" . (int)$data['filter_supplier_id'] . "'";
        }
        
        if (isset($data['filter_status']) && $data['filter_status'] !== '') {
            $sql .= " AND pa.status = '" . (int)$data['filter_status'] . "'";
        }
        
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(pa.start_date) >= DATE('" . $this->db->escape($data['filter_date_start']) . "')";
        }
        
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(pa.end_date) <= DATE('" . $this->db->escape($data['filter_date_end']) . "')";
        }
        
        $query = $this->db->query($sql);
        
        return $query->row['total'];
    }
    
    public function addPriceAgreementItem($price_agreement_id, $data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "supplier_price_agreement_item SET 
            price_agreement_id = '" . (int)$price_agreement_id . "', 
            product_id = '" . (int)$data['product_id'] . "', 
            quantity_min = '" . (float)$data['quantity_min'] . "', 
            quantity_max = '" . (float)$data['quantity_max'] . "', 
            price = '" . (float)$data['price'] . "', 
            discount_percentage = '" . (float)$data['discount_percentage'] . "', 
            currency_id = '" . (int)$data['currency_id'] . "', 
            status = '" . (int)$data['status'] . "'");
    }
    
    public function getPriceAgreementItems($price_agreement_id) {
        $query = $this->db->query("SELECT pai.*, 
            p.name as product_name, 
            p.model as product_model,
            c.title as currency_title,
            c.symbol_left,
            c.symbol_right
            FROM " . DB_PREFIX . "supplier_price_agreement_item pai 
            LEFT JOIN " . DB_PREFIX . "product p ON (pai.product_id = p.product_id) 
            LEFT JOIN " . DB_PREFIX . "currency c ON (pai.currency_id = c.currency_id)
            WHERE pai.price_agreement_id = '" . (int)$price_agreement_id . "' 
            ORDER BY p.name ASC");
        
        return $query->rows;
    }
    
    public function getActivePriceAgreements($supplier_id = 0) {
        $sql = "SELECT * FROM " . DB_PREFIX . "supplier_price_agreement 
                WHERE status = '1' 
                AND start_date <= NOW() 
                AND end_date >= NOW()";
        
        if ($supplier_id) {
            $sql .= " AND supplier_id = '" . (int)$supplier_id . "'";
        }
        
        $sql .= " ORDER BY agreement_name ASC";
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    public function getProductPrice($supplier_id, $product_id, $quantity = 1) {
        $query = $this->db->query("SELECT pai.price, pai.discount_percentage 
            FROM " . DB_PREFIX . "supplier_price_agreement_item pai 
            LEFT JOIN " . DB_PREFIX . "supplier_price_agreement pa ON (pai.price_agreement_id = pa.price_agreement_id) 
            WHERE pa.supplier_id = '" . (int)$supplier_id . "' 
            AND pai.product_id = '" . (int)$product_id . "' 
            AND pai.quantity_min <= '" . (float)$quantity . "' 
            AND (pai.quantity_max >= '" . (float)$quantity . "' OR pai.quantity_max = 0) 
            AND pa.status = '1' 
            AND pa.start_date <= NOW() 
            AND pa.end_date >= NOW() 
            AND pai.status = '1' 
            ORDER BY pai.quantity_min DESC 
            LIMIT 1");
        
        if ($query->num_rows) {
            $price = $query->row['price'];
            if ($query->row['discount_percentage'] > 0) {
                $price = $price - ($price * $query->row['discount_percentage'] / 100);
            }
            return $price;
        }
        
        return false;
    }
    
    public function getExpiringAgreements($days = 30) {
        $query = $this->db->query("SELECT pa.*, s.name as supplier_name 
            FROM " . DB_PREFIX . "supplier_price_agreement pa 
            LEFT JOIN " . DB_PREFIX . "supplier s ON (pa.supplier_id = s.supplier_id) 
            WHERE pa.status = '1' 
            AND pa.end_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL " . (int)$days . " DAY) 
            ORDER BY pa.end_date ASC");
        
        return $query->rows;
    }
}
