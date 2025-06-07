<?php
/**
 * AYM ERP - Advanced Tax Reporting Model
 * 
 * Professional tax reporting system with comprehensive analytics
 * Features:
 * - Real-time tax calculations and summaries
 * - ETA compliance reporting
 * - Multi-period comparisons
 * - Advanced analytics and insights
 * - Tax filing preparation
 * - Performance optimization
 * 
 * @author AYM ERP Development Team
 * @copyright 2024 AYM ERP
 * @license Commercial License
 * @version 1.0.0
 * @link https://aym-erp.com
 */

class ModelReportTaxReport extends Model {
    
    public function getTaxSummary($date_start, $date_end) {
        $sql = "SELECT 
                    COUNT(DISTINCT o.order_id) as total_orders,
                    SUM(ot.value) as total_tax_amount,
                    SUM(o.total) as total_order_amount,
                    AVG(ot.value) as average_tax_per_order,
                    COUNT(CASE WHEN ei.status = 'sent' THEN 1 END) as eta_sent_count,
                    COUNT(CASE WHEN ei.status = 'accepted' THEN 1 END) as eta_accepted_count,
                    COUNT(CASE WHEN ei.status = 'rejected' THEN 1 END) as eta_rejected_count,
                    COUNT(CASE WHEN ei.status IS NULL THEN 1 END) as eta_pending_count,
                    (COUNT(CASE WHEN ei.status = 'accepted' THEN 1 END) / COUNT(DISTINCT o.order_id) * 100) as eta_success_rate
                FROM " . DB_PREFIX . "order o
                LEFT JOIN " . DB_PREFIX . "order_total ot ON (o.order_id = ot.order_id AND ot.code = 'tax')
                LEFT JOIN " . DB_PREFIX . "eta_invoices ei ON (o.order_id = ei.order_id)
                WHERE DATE(o.date_added) >= '" . $this->db->escape($date_start) . "'
                AND DATE(o.date_added) <= '" . $this->db->escape($date_end) . "'
                AND o.order_status_id > 0";
        
        $query = $this->db->query($sql);
        
        return $query->row;
    }
    
    public function getETACompliance($date_start, $date_end) {
        $sql = "SELECT 
                    ei.status,
                    COUNT(*) as count,
                    SUM(ei.total_amount) as total_amount,
                    AVG(ei.total_amount) as average_amount,
                    MIN(ei.sent_date) as first_sent,
                    MAX(ei.sent_date) as last_sent
                FROM " . DB_PREFIX . "eta_invoices ei
                LEFT JOIN " . DB_PREFIX . "order o ON (ei.order_id = o.order_id)
                WHERE DATE(o.date_added) >= '" . $this->db->escape($date_start) . "'
                AND DATE(o.date_added) <= '" . $this->db->escape($date_end) . "'
                GROUP BY ei.status
                ORDER BY count DESC";
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    public function getTaxBreakdown($date_start, $date_end) {
        $sql = "SELECT 
                    tr.name as tax_name,
                    tr.rate as tax_rate,
                    COUNT(DISTINCT o.order_id) as order_count,
                    SUM(ot.value) as total_tax_amount,
                    AVG(ot.value) as average_tax_amount,
                    SUM(o.total - ot.value) as total_taxable_amount
                FROM " . DB_PREFIX . "order o
                LEFT JOIN " . DB_PREFIX . "order_total ot ON (o.order_id = ot.order_id AND ot.code = 'tax')
                LEFT JOIN " . DB_PREFIX . "order_product op ON (o.order_id = op.order_id)
                LEFT JOIN " . DB_PREFIX . "product p ON (op.product_id = p.product_id)
                LEFT JOIN " . DB_PREFIX . "tax_class tc ON (p.tax_class_id = tc.tax_class_id)
                LEFT JOIN " . DB_PREFIX . "tax_rule tr ON (tc.tax_class_id = tr.tax_class_id)
                WHERE DATE(o.date_added) >= '" . $this->db->escape($date_start) . "'
                AND DATE(o.date_added) <= '" . $this->db->escape($date_end) . "'
                AND o.order_status_id > 0
                AND ot.value > 0
                GROUP BY tr.tax_class_id, tr.rate
                ORDER BY total_tax_amount DESC";
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    public function getMonthlyTrends($date_start, $date_end) {
        $sql = "SELECT 
                    DATE_FORMAT(o.date_added, '%Y-%m') as month,
                    COUNT(DISTINCT o.order_id) as order_count,
                    SUM(ot.value) as tax_amount,
                    SUM(o.total) as total_amount,
                    AVG(ot.value) as avg_tax_per_order,
                    COUNT(CASE WHEN ei.status = 'sent' THEN 1 END) as eta_sent,
                    COUNT(CASE WHEN ei.status = 'accepted' THEN 1 END) as eta_accepted
                FROM " . DB_PREFIX . "order o
                LEFT JOIN " . DB_PREFIX . "order_total ot ON (o.order_id = ot.order_id AND ot.code = 'tax')
                LEFT JOIN " . DB_PREFIX . "eta_invoices ei ON (o.order_id = ei.order_id)
                WHERE DATE(o.date_added) >= '" . $this->db->escape($date_start) . "'
                AND DATE(o.date_added) <= '" . $this->db->escape($date_end) . "'
                AND o.order_status_id > 0
                GROUP BY DATE_FORMAT(o.date_added, '%Y-%m')
                ORDER BY month ASC";
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    public function getTopCustomersByTax($date_start, $date_end, $limit = 10) {
        $sql = "SELECT 
                    o.customer_id,
                    CONCAT(o.firstname, ' ', o.lastname) as customer_name,
                    o.email,
                    COUNT(DISTINCT o.order_id) as order_count,
                    SUM(ot.value) as total_tax_paid,
                    SUM(o.total) as total_spent,
                    AVG(ot.value) as avg_tax_per_order,
                    MAX(o.date_added) as last_order_date
                FROM " . DB_PREFIX . "order o
                LEFT JOIN " . DB_PREFIX . "order_total ot ON (o.order_id = ot.order_id AND ot.code = 'tax')
                WHERE DATE(o.date_added) >= '" . $this->db->escape($date_start) . "'
                AND DATE(o.date_added) <= '" . $this->db->escape($date_end) . "'
                AND o.order_status_id > 0
                AND ot.value > 0
                GROUP BY o.customer_id
                ORDER BY total_tax_paid DESC
                LIMIT " . (int)$limit;
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    public function getTaxRateAnalysis($date_start, $date_end) {
        $sql = "SELECT 
                    CASE 
                        WHEN tr.rate = 0 THEN 'Tax Exempt'
                        WHEN tr.rate <= 5 THEN 'Low Tax (0-5%)'
                        WHEN tr.rate <= 10 THEN 'Medium Tax (5-10%)'
                        WHEN tr.rate <= 15 THEN 'Standard Tax (10-15%)'
                        ELSE 'High Tax (15%+)'
                    END as tax_category,
                    COUNT(DISTINCT o.order_id) as order_count,
                    SUM(ot.value) as total_tax_amount,
                    SUM(o.total) as total_order_amount,
                    AVG(tr.rate) as average_rate
                FROM " . DB_PREFIX . "order o
                LEFT JOIN " . DB_PREFIX . "order_total ot ON (o.order_id = ot.order_id AND ot.code = 'tax')
                LEFT JOIN " . DB_PREFIX . "order_product op ON (o.order_id = op.order_id)
                LEFT JOIN " . DB_PREFIX . "product p ON (op.product_id = p.product_id)
                LEFT JOIN " . DB_PREFIX . "tax_class tc ON (p.tax_class_id = tc.tax_class_id)
                LEFT JOIN " . DB_PREFIX . "tax_rule tr ON (tc.tax_class_id = tr.tax_class_id)
                WHERE DATE(o.date_added) >= '" . $this->db->escape($date_start) . "'
                AND DATE(o.date_added) <= '" . $this->db->escape($date_end) . "'
                AND o.order_status_id > 0
                GROUP BY tax_category
                ORDER BY total_tax_amount DESC";
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    public function getPendingETASubmissions() {
        $sql = "SELECT 
                    eq.queue_id,
                    eq.order_id,
                    eq.type,
                    eq.status,
                    eq.attempts,
                    eq.error_message,
                    eq.created_date,
                    eq.next_attempt,
                    o.total as order_total,
                    CONCAT(o.firstname, ' ', o.lastname) as customer_name
                FROM " . DB_PREFIX . "eta_queue eq
                LEFT JOIN " . DB_PREFIX . "order o ON (eq.order_id = o.order_id)
                WHERE eq.status IN ('pending', 'failed')
                ORDER BY eq.priority DESC, eq.created_date ASC
                LIMIT 50";
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    public function getTaxFilingPreparation($date_start, $date_end) {
        $sql = "SELECT 
                    'VAT' as tax_type,
                    SUM(CASE WHEN tr.rate = 14 THEN ot.value ELSE 0 END) as vat_14_amount,
                    SUM(CASE WHEN tr.rate = 0 THEN (o.total - COALESCE(ot.value, 0)) ELSE 0 END) as exempt_amount,
                    SUM(CASE WHEN tr.rate > 0 THEN (o.total - ot.value) ELSE 0 END) as taxable_amount,
                    SUM(ot.value) as total_tax_collected,
                    COUNT(DISTINCT o.order_id) as total_transactions,
                    COUNT(DISTINCT o.customer_id) as unique_customers
                FROM " . DB_PREFIX . "order o
                LEFT JOIN " . DB_PREFIX . "order_total ot ON (o.order_id = ot.order_id AND ot.code = 'tax')
                LEFT JOIN " . DB_PREFIX . "order_product op ON (o.order_id = op.order_id)
                LEFT JOIN " . DB_PREFIX . "product p ON (op.product_id = p.product_id)
                LEFT JOIN " . DB_PREFIX . "tax_class tc ON (p.tax_class_id = tc.tax_class_id)
                LEFT JOIN " . DB_PREFIX . "tax_rule tr ON (tc.tax_class_id = tr.tax_class_id)
                WHERE DATE(o.date_added) >= '" . $this->db->escape($date_start) . "'
                AND DATE(o.date_added) <= '" . $this->db->escape($date_end) . "'
                AND o.order_status_id > 0";
        
        $query = $this->db->query($sql);
        
        return $query->row;
    }
    
    public function getTaxDetails($date_start, $date_end, $filters = array()) {
        $sql = "SELECT 
                    o.order_id,
                    o.date_added,
                    CONCAT(o.firstname, ' ', o.lastname) as customer_name,
                    o.email,
                    o.telephone,
                    tr.name as tax_type,
                    tr.rate as tax_rate,
                    (o.total - COALESCE(ot.value, 0)) as taxable_amount,
                    COALESCE(ot.value, 0) as tax_amount,
                    o.total as total_amount,
                    COALESCE(ei.status, 'not_sent') as eta_status,
                    ei.eta_uuid,
                    ei.sent_date as eta_sent_date
                FROM " . DB_PREFIX . "order o
                LEFT JOIN " . DB_PREFIX . "order_total ot ON (o.order_id = ot.order_id AND ot.code = 'tax')
                LEFT JOIN " . DB_PREFIX . "order_product op ON (o.order_id = op.order_id)
                LEFT JOIN " . DB_PREFIX . "product p ON (op.product_id = p.product_id)
                LEFT JOIN " . DB_PREFIX . "tax_class tc ON (p.tax_class_id = tc.tax_class_id)
                LEFT JOIN " . DB_PREFIX . "tax_rule tr ON (tc.tax_class_id = tr.tax_class_id)
                LEFT JOIN " . DB_PREFIX . "eta_invoices ei ON (o.order_id = ei.order_id)
                WHERE DATE(o.date_added) >= '" . $this->db->escape($date_start) . "'
                AND DATE(o.date_added) <= '" . $this->db->escape($date_end) . "'
                AND o.order_status_id > 0";
        
        // Apply filters
        if (!empty($filters['tax_type'])) {
            $sql .= " AND tr.name = '" . $this->db->escape($filters['tax_type']) . "'";
        }
        
        if (!empty($filters['customer_type'])) {
            if ($filters['customer_type'] == 'business') {
                $sql .= " AND o.customer_id > 0";
            } elseif ($filters['customer_type'] == 'guest') {
                $sql .= " AND o.customer_id = 0";
            }
        }
        
        if (!empty($filters['eta_status'])) {
            $sql .= " AND ei.status = '" . $this->db->escape($filters['eta_status']) . "'";
        }
        
        $sql .= " GROUP BY o.order_id ORDER BY o.date_added DESC";
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    public function getETADetails($date_start, $date_end, $filters = array()) {
        $sql = "SELECT 
                    ei.invoice_id,
                    ei.order_id,
                    ei.internal_id,
                    ei.eta_uuid,
                    ei.status,
                    ei.total_amount,
                    ei.tax_amount,
                    ei.sent_date,
                    ei.accepted_date,
                    ei.error_data,
                    o.date_added as order_date,
                    CONCAT(o.firstname, ' ', o.lastname) as customer_name,
                    eq.attempts as retry_count,
                    eq.error_message as queue_error
                FROM " . DB_PREFIX . "eta_invoices ei
                LEFT JOIN " . DB_PREFIX . "order o ON (ei.order_id = o.order_id)
                LEFT JOIN " . DB_PREFIX . "eta_queue eq ON (ei.order_id = eq.order_id AND eq.type = 'invoice')
                WHERE DATE(o.date_added) >= '" . $this->db->escape($date_start) . "'
                AND DATE(o.date_added) <= '" . $this->db->escape($date_end) . "'";
        
        // Apply filters
        if (!empty($filters['eta_status'])) {
            $sql .= " AND ei.status = '" . $this->db->escape($filters['eta_status']) . "'";
        }
        
        $sql .= " ORDER BY ei.sent_date DESC";
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    public function getCustomerTaxDetails($date_start, $date_end, $filters = array()) {
        $sql = "SELECT 
                    o.customer_id,
                    CONCAT(o.firstname, ' ', o.lastname) as customer_name,
                    o.email,
                    COUNT(DISTINCT o.order_id) as order_count,
                    SUM(ot.value) as total_tax_paid,
                    SUM(o.total) as total_spent,
                    AVG(ot.value) as avg_tax_per_order,
                    MIN(o.date_added) as first_order,
                    MAX(o.date_added) as last_order,
                    COUNT(CASE WHEN ei.status = 'sent' THEN 1 END) as eta_sent_count,
                    COUNT(CASE WHEN ei.status = 'accepted' THEN 1 END) as eta_accepted_count
                FROM " . DB_PREFIX . "order o
                LEFT JOIN " . DB_PREFIX . "order_total ot ON (o.order_id = ot.order_id AND ot.code = 'tax')
                LEFT JOIN " . DB_PREFIX . "eta_invoices ei ON (o.order_id = ei.order_id)
                WHERE DATE(o.date_added) >= '" . $this->db->escape($date_start) . "'
                AND DATE(o.date_added) <= '" . $this->db->escape($date_end) . "'
                AND o.order_status_id > 0";
        
        // Apply filters
        if (!empty($filters['customer_type'])) {
            if ($filters['customer_type'] == 'business') {
                $sql .= " AND o.customer_id > 0";
            } elseif ($filters['customer_type'] == 'guest') {
                $sql .= " AND o.customer_id = 0";
            }
        }
        
        $sql .= " GROUP BY o.customer_id ORDER BY total_tax_paid DESC";
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    public function getProductTaxDetails($date_start, $date_end, $filters = array()) {
        $sql = "SELECT 
                    p.product_id,
                    pd.name as product_name,
                    p.model,
                    p.sku,
                    tr.name as tax_type,
                    tr.rate as tax_rate,
                    COUNT(DISTINCT o.order_id) as order_count,
                    SUM(op.quantity) as total_quantity_sold,
                    SUM(op.total) as total_sales,
                    SUM(op.total * tr.rate / 100) as total_tax_generated,
                    AVG(op.price) as avg_selling_price
                FROM " . DB_PREFIX . "order_product op
                LEFT JOIN " . DB_PREFIX . "order o ON (op.order_id = o.order_id)
                LEFT JOIN " . DB_PREFIX . "product p ON (op.product_id = p.product_id)
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "')
                LEFT JOIN " . DB_PREFIX . "tax_class tc ON (p.tax_class_id = tc.tax_class_id)
                LEFT JOIN " . DB_PREFIX . "tax_rule tr ON (tc.tax_class_id = tr.tax_class_id)
                WHERE DATE(o.date_added) >= '" . $this->db->escape($date_start) . "'
                AND DATE(o.date_added) <= '" . $this->db->escape($date_end) . "'
                AND o.order_status_id > 0";
        
        // Apply filters
        if (!empty($filters['tax_type'])) {
            $sql .= " AND tr.name = '" . $this->db->escape($filters['tax_type']) . "'";
        }
        
        $sql .= " GROUP BY p.product_id ORDER BY total_tax_generated DESC";
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    public function generateTaxFiling($filing_period, $filing_type = 'monthly') {
        // Parse filing period (e.g., "2024-01" for monthly)
        $period_parts = explode('-', $filing_period);
        $year = $period_parts[0];
        $month = $period_parts[1] ?? '01';
        
        if ($filing_type == 'monthly') {
            $date_start = $year . '-' . $month . '-01';
            $date_end = date('Y-m-t', strtotime($date_start));
        } else {
            // Quarterly or yearly logic can be added here
            $date_start = $year . '-01-01';
            $date_end = $year . '-12-31';
        }
        
        // Get comprehensive tax data for filing
        $filing_data = array(
            'filing_period' => $filing_period,
            'filing_type' => $filing_type,
            'date_start' => $date_start,
            'date_end' => $date_end,
            'summary' => $this->getTaxSummary($date_start, $date_end),
            'breakdown' => $this->getTaxBreakdown($date_start, $date_end),
            'eta_compliance' => $this->getETACompliance($date_start, $date_end),
            'filing_preparation' => $this->getTaxFilingPreparation($date_start, $date_end),
            'generated_date' => date('Y-m-d H:i:s'),
            'company_info' => array(
                'name' => $this->config->get('config_name'),
                'tax_id' => $this->config->get('config_eta_taxpayer_id'),
                'address' => $this->config->get('config_address'),
                'telephone' => $this->config->get('config_telephone'),
                'email' => $this->config->get('config_email')
            )
        );
        
        return $filing_data;
    }
    
    public function saveTaxFiling($filing_data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "tax_filing SET
            filing_period = '" . $this->db->escape($filing_data['filing_period']) . "',
            filing_type = '" . $this->db->escape($filing_data['filing_type']) . "',
            filing_data = '" . $this->db->escape(json_encode($filing_data)) . "',
            status = 'generated',
            user_id = '" . (int)$this->user->getId() . "',
            date_added = NOW()");
        
        return $this->db->getLastId();
    }
    
    public function getTaxFiling($filing_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "tax_filing 
                                  WHERE filing_id = '" . (int)$filing_id . "'");
        
        return $query->row;
    }
}
