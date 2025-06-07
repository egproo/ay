<?php
class ModelEtaComplianceDashboard extends Model {

    public function getTotalInvoices() {
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "eta_invoice WHERE status != 'deleted'");
        return (int)$query->row['total'];
    }

    public function getSubmittedInvoices() {
        $query = $this->db->query("
            SELECT COUNT(*) as total 
            FROM " . DB_PREFIX . "eta_invoice 
            WHERE eta_status IN ('submitted', 'accepted') 
            AND status != 'deleted'
        ");
        return (int)$query->row['total'];
    }

    public function getPendingInvoices() {
        $query = $this->db->query("
            SELECT COUNT(*) as total 
            FROM " . DB_PREFIX . "eta_invoice 
            WHERE eta_status = 'pending' 
            AND status != 'deleted'
        ");
        return (int)$query->row['total'];
    }

    public function getRejectedInvoices() {
        $query = $this->db->query("
            SELECT COUNT(*) as total 
            FROM " . DB_PREFIX . "eta_invoice 
            WHERE eta_status = 'rejected' 
            AND status != 'deleted'
        ");
        return (int)$query->row['total'];
    }

    public function getComplianceRate() {
        $total = $this->getTotalInvoices();
        if ($total == 0) return 0;

        $submitted = $this->getSubmittedInvoices();
        return round(($submitted / $total) * 100, 2);
    }

    public function getTotalTaxAmount() {
        $query = $this->db->query("
            SELECT SUM(tax_amount) as total_tax 
            FROM " . DB_PREFIX . "eta_invoice 
            WHERE eta_status IN ('submitted', 'accepted') 
            AND status != 'deleted'
            AND date_added >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        return $query->row['total_tax'] ? (float)$query->row['total_tax'] : 0;
    }

    public function getAverageSubmissionTime() {
        $query = $this->db->query("
            SELECT AVG(TIMESTAMPDIFF(HOUR, date_added, eta_submitted_date)) as avg_time 
            FROM " . DB_PREFIX . "eta_invoice 
            WHERE eta_status IN ('submitted', 'accepted') 
            AND eta_submitted_date IS NOT NULL
            AND date_added >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        return $query->row['avg_time'] ? round((float)$query->row['avg_time'], 2) : 0;
    }

    public function getSuccessRate() {
        $total_submitted = $this->db->query("
            SELECT COUNT(*) as total 
            FROM " . DB_PREFIX . "eta_invoice 
            WHERE eta_status IN ('submitted', 'accepted', 'rejected') 
            AND status != 'deleted'
            AND date_added >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");

        $successful = $this->db->query("
            SELECT COUNT(*) as total 
            FROM " . DB_PREFIX . "eta_invoice 
            WHERE eta_status = 'accepted' 
            AND status != 'deleted'
            AND date_added >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");

        $total_count = (int)$total_submitted->row['total'];
        $success_count = (int)$successful->row['total'];

        if ($total_count == 0) return 0;
        
        return round(($success_count / $total_count) * 100, 2);
    }

    public function getErrorRate() {
        return 100 - $this->getSuccessRate();
    }

    public function getMonthlyGrowth() {
        $current_month = $this->db->query("
            SELECT COUNT(*) as total 
            FROM " . DB_PREFIX . "eta_invoice 
            WHERE eta_status IN ('submitted', 'accepted') 
            AND status != 'deleted'
            AND MONTH(date_added) = MONTH(NOW())
            AND YEAR(date_added) = YEAR(NOW())
        ");

        $previous_month = $this->db->query("
            SELECT COUNT(*) as total 
            FROM " . DB_PREFIX . "eta_invoice 
            WHERE eta_status IN ('submitted', 'accepted') 
            AND status != 'deleted'
            AND MONTH(date_added) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH))
            AND YEAR(date_added) = YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH))
        ");

        $current = (int)$current_month->row['total'];
        $previous = (int)$previous_month->row['total'];

        if ($previous == 0) return $current > 0 ? 100 : 0;
        
        return round((($current - $previous) / $previous) * 100, 2);
    }

    public function getSubmissionTrendData() {
        $query = $this->db->query("
            SELECT 
                DATE(date_added) as date,
                COUNT(*) as total_invoices,
                SUM(CASE WHEN eta_status IN ('submitted', 'accepted') THEN 1 ELSE 0 END) as submitted_invoices
            FROM " . DB_PREFIX . "eta_invoice 
            WHERE status != 'deleted'
            AND date_added >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(date_added)
            ORDER BY date ASC
        ");

        $data = array();
        foreach ($query->rows as $row) {
            $data[] = array(
                'date' => $row['date'],
                'total' => (int)$row['total_invoices'],
                'submitted' => (int)$row['submitted_invoices']
            );
        }

        return $data;
    }

    public function getStatusDistributionData() {
        $query = $this->db->query("
            SELECT 
                eta_status,
                COUNT(*) as count
            FROM " . DB_PREFIX . "eta_invoice 
            WHERE status != 'deleted'
            AND date_added >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY eta_status
        ");

        $data = array();
        foreach ($query->rows as $row) {
            $data[] = array(
                'status' => $row['eta_status'],
                'count' => (int)$row['count']
            );
        }

        return $data;
    }

    public function getTaxBreakdownData() {
        $query = $this->db->query("
            SELECT 
                tax_type,
                SUM(tax_amount) as total_amount,
                COUNT(*) as invoice_count
            FROM " . DB_PREFIX . "eta_invoice 
            WHERE eta_status IN ('submitted', 'accepted') 
            AND status != 'deleted'
            AND date_added >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY tax_type
            ORDER BY total_amount DESC
        ");

        $data = array();
        foreach ($query->rows as $row) {
            $data[] = array(
                'tax_type' => $row['tax_type'],
                'amount' => (float)$row['total_amount'],
                'count' => (int)$row['invoice_count']
            );
        }

        return $data;
    }

    public function getComplianceTimelineData() {
        $query = $this->db->query("
            SELECT 
                DATE(date_added) as date,
                AVG(CASE WHEN eta_status IN ('submitted', 'accepted') THEN 100 ELSE 0 END) as compliance_rate
            FROM " . DB_PREFIX . "eta_invoice 
            WHERE status != 'deleted'
            AND date_added >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(date_added)
            ORDER BY date ASC
        ");

        $data = array();
        foreach ($query->rows as $row) {
            $data[] = array(
                'date' => $row['date'],
                'compliance_rate' => round((float)$row['compliance_rate'], 2)
            );
        }

        return $data;
    }

    public function getComplianceAlerts() {
        $alerts = array();

        // تحقق من الفواتير المعلقة لأكثر من 24 ساعة
        $pending_query = $this->db->query("
            SELECT COUNT(*) as count 
            FROM " . DB_PREFIX . "eta_invoice 
            WHERE eta_status = 'pending' 
            AND status != 'deleted'
            AND date_added < DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");

        if ($pending_query->row['count'] > 0) {
            $alerts[] = array(
                'type' => 'warning',
                'title' => 'فواتير معلقة',
                'message' => $pending_query->row['count'] . ' فاتورة معلقة لأكثر من 24 ساعة',
                'count' => (int)$pending_query->row['count']
            );
        }

        // تحقق من الفواتير المرفوضة
        $rejected_query = $this->db->query("
            SELECT COUNT(*) as count 
            FROM " . DB_PREFIX . "eta_invoice 
            WHERE eta_status = 'rejected' 
            AND status != 'deleted'
            AND date_added >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");

        if ($rejected_query->row['count'] > 0) {
            $alerts[] = array(
                'type' => 'danger',
                'title' => 'فواتير مرفوضة',
                'message' => $rejected_query->row['count'] . ' فاتورة مرفوضة خلال الأسبوع الماضي',
                'count' => (int)$rejected_query->row['count']
            );
        }

        // تحقق من معدل الامتثال المنخفض
        $compliance_rate = $this->getComplianceRate();
        if ($compliance_rate < 90) {
            $alerts[] = array(
                'type' => 'warning',
                'title' => 'معدل امتثال منخفض',
                'message' => 'معدل الامتثال الحالي ' . $compliance_rate . '% أقل من المطلوب',
                'rate' => $compliance_rate
            );
        }

        return $alerts;
    }

    public function getInvoiceDetails($invoice_id) {
        $query = $this->db->query("
            SELECT * 
            FROM " . DB_PREFIX . "eta_invoice 
            WHERE invoice_id = '" . (int)$invoice_id . "' 
            AND status != 'deleted'
        ");

        return $query->row;
    }

    public function getSubmissionHistory($invoice_id) {
        $query = $this->db->query("
            SELECT * 
            FROM " . DB_PREFIX . "eta_submission_log 
            WHERE invoice_id = '" . (int)$invoice_id . "' 
            ORDER BY date_added DESC
        ");

        return $query->rows;
    }

    public function getValidationErrors($invoice_id) {
        $query = $this->db->query("
            SELECT * 
            FROM " . DB_PREFIX . "eta_validation_error 
            WHERE invoice_id = '" . (int)$invoice_id . "' 
            ORDER BY date_added DESC
        ");

        return $query->rows;
    }

    public function resubmitInvoice($invoice_id) {
        try {
            // تحديث حالة الفاتورة
            $this->db->query("
                UPDATE " . DB_PREFIX . "eta_invoice 
                SET eta_status = 'pending', 
                    eta_resubmitted_date = NOW() 
                WHERE invoice_id = '" . (int)$invoice_id . "'
            ");

            // تسجيل محاولة الإعادة
            $this->db->query("
                INSERT INTO " . DB_PREFIX . "eta_submission_log 
                SET invoice_id = '" . (int)$invoice_id . "',
                    action = 'resubmit',
                    status = 'pending',
                    date_added = NOW()
            ");

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function testETAConnection() {
        try {
            // محاولة الاتصال بـ ETA API
            $this->load->library('eta_api');
            
            $result = $this->eta_api->testConnection();
            
            if ($result['success']) {
                return array(
                    'success' => true,
                    'info' => array(
                        'server_status' => 'متصل',
                        'response_time' => $result['response_time'] . 'ms',
                        'api_version' => $result['api_version'],
                        'last_check' => date('Y-m-d H:i:s')
                    )
                );
            } else {
                return array(
                    'success' => false,
                    'error' => $result['error']
                );
            }
        } catch (Exception $e) {
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }

    public function generateComplianceReport($report_type, $date_from = '', $date_to = '') {
        $data = array();
        
        $where_date = '';
        if ($date_from && $date_to) {
            $where_date = " AND DATE(date_added) BETWEEN '" . $this->db->escape($date_from) . "' AND '" . $this->db->escape($date_to) . "'";
        }

        switch ($report_type) {
            case 'submission_summary':
                $data = $this->getSubmissionTrendData();
                break;
            case 'status_breakdown':
                $data = $this->getStatusDistributionData();
                break;
            case 'tax_summary':
                $data = $this->getTaxBreakdownData();
                break;
            case 'compliance_timeline':
                $data = $this->getComplianceTimelineData();
                break;
            case 'detailed_invoices':
                $query = $this->db->query("
                    SELECT 
                        invoice_id,
                        invoice_number,
                        customer_name,
                        total_amount,
                        tax_amount,
                        eta_status,
                        eta_submitted_date,
                        date_added
                    FROM " . DB_PREFIX . "eta_invoice 
                    WHERE status != 'deleted'" . $where_date . "
                    ORDER BY date_added DESC
                ");
                $data = $query->rows;
                break;
        }

        return $data;
    }
}
