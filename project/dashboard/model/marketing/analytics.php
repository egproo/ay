<?php
class ModelMarketingAnalytics extends Model {

    public function getTotalCampaigns() {
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "marketing_campaign WHERE status = '1'");
        return (int)$query->row['total'];
    }

    public function getActiveCampaigns() {
        $query = $this->db->query("
            SELECT COUNT(*) as total 
            FROM " . DB_PREFIX . "marketing_campaign 
            WHERE status = '1' 
            AND start_date <= NOW() 
            AND (end_date IS NULL OR end_date >= NOW())
        ");
        return (int)$query->row['total'];
    }

    public function getTotalLeads() {
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "marketing_lead WHERE status = '1'");
        return (int)$query->row['total'];
    }

    public function getConversionRate() {
        $total_leads = $this->getTotalLeads();
        if ($total_leads == 0) return 0;

        $query = $this->db->query("
            SELECT COUNT(*) as converted 
            FROM " . DB_PREFIX . "marketing_lead 
            WHERE status = '1' AND converted = '1'
        ");
        
        $converted = (int)$query->row['converted'];
        return round(($converted / $total_leads) * 100, 2);
    }

    public function getTotalRevenue() {
        $query = $this->db->query("
            SELECT SUM(o.total) as revenue 
            FROM " . DB_PREFIX . "order o
            INNER JOIN " . DB_PREFIX . "marketing_lead l ON o.customer_id = l.customer_id
            WHERE l.converted = '1' 
            AND o.order_status_id > 0
            AND o.date_added >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        return $query->row['revenue'] ? (float)$query->row['revenue'] : 0;
    }

    public function getROI() {
        $revenue = $this->getTotalRevenue();
        
        $query = $this->db->query("
            SELECT SUM(budget) as total_budget 
            FROM " . DB_PREFIX . "marketing_campaign 
            WHERE status = '1'
            AND start_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        $budget = $query->row['total_budget'] ? (float)$query->row['total_budget'] : 0;
        
        if ($budget == 0) return 0;
        
        return round((($revenue - $budget) / $budget) * 100, 2);
    }

    public function getCampaignPerformanceData() {
        $query = $this->db->query("
            SELECT 
                c.name,
                COUNT(l.lead_id) as leads,
                SUM(CASE WHEN l.converted = '1' THEN 1 ELSE 0 END) as conversions,
                c.budget,
                SUM(CASE WHEN l.converted = '1' THEN o.total ELSE 0 END) as revenue
            FROM " . DB_PREFIX . "marketing_campaign c
            LEFT JOIN " . DB_PREFIX . "marketing_lead l ON c.campaign_id = l.campaign_id
            LEFT JOIN " . DB_PREFIX . "order o ON l.customer_id = o.customer_id AND l.converted = '1'
            WHERE c.status = '1'
            GROUP BY c.campaign_id
            ORDER BY revenue DESC
            LIMIT 10
        ");

        $data = array();
        foreach ($query->rows as $row) {
            $conversion_rate = $row['leads'] > 0 ? round(($row['conversions'] / $row['leads']) * 100, 2) : 0;
            $roi = $row['budget'] > 0 ? round((($row['revenue'] - $row['budget']) / $row['budget']) * 100, 2) : 0;
            
            $data[] = array(
                'name' => $row['name'],
                'leads' => (int)$row['leads'],
                'conversions' => (int)$row['conversions'],
                'conversion_rate' => $conversion_rate,
                'budget' => (float)$row['budget'],
                'revenue' => (float)$row['revenue'],
                'roi' => $roi
            );
        }

        return $data;
    }

    public function getLeadSourcesData() {
        $query = $this->db->query("
            SELECT 
                source,
                COUNT(*) as count,
                SUM(CASE WHEN converted = '1' THEN 1 ELSE 0 END) as conversions
            FROM " . DB_PREFIX . "marketing_lead 
            WHERE status = '1'
            GROUP BY source
            ORDER BY count DESC
        ");

        $data = array();
        foreach ($query->rows as $row) {
            $conversion_rate = $row['count'] > 0 ? round(($row['conversions'] / $row['count']) * 100, 2) : 0;
            
            $data[] = array(
                'source' => $row['source'],
                'leads' => (int)$row['count'],
                'conversions' => (int)$row['conversions'],
                'conversion_rate' => $conversion_rate
            );
        }

        return $data;
    }

    public function getConversionFunnelData() {
        $data = array();
        
        // مراحل القمع
        $stages = array(
            'visitors' => 'الزوار',
            'leads' => 'العملاء المحتملين',
            'qualified' => 'مؤهلين',
            'opportunities' => 'فرص',
            'customers' => 'عملاء'
        );

        // حساب كل مرحلة
        foreach ($stages as $stage => $label) {
            switch ($stage) {
                case 'visitors':
                    $query = $this->db->query("SELECT COUNT(*) as count FROM " . DB_PREFIX . "customer WHERE date_added >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
                    break;
                case 'leads':
                    $query = $this->db->query("SELECT COUNT(*) as count FROM " . DB_PREFIX . "marketing_lead WHERE status = '1' AND date_added >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
                    break;
                case 'qualified':
                    $query = $this->db->query("SELECT COUNT(*) as count FROM " . DB_PREFIX . "marketing_lead WHERE status = '1' AND qualified = '1' AND date_added >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
                    break;
                case 'opportunities':
                    $query = $this->db->query("SELECT COUNT(*) as count FROM " . DB_PREFIX . "marketing_lead WHERE status = '1' AND opportunity = '1' AND date_added >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
                    break;
                case 'customers':
                    $query = $this->db->query("SELECT COUNT(*) as count FROM " . DB_PREFIX . "marketing_lead WHERE status = '1' AND converted = '1' AND date_added >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
                    break;
            }
            
            $data[] = array(
                'stage' => $stage,
                'label' => $label,
                'count' => (int)$query->row['count']
            );
        }

        return $data;
    }

    public function getRevenueTrendData() {
        $query = $this->db->query("
            SELECT 
                DATE(o.date_added) as date,
                SUM(o.total) as revenue,
                COUNT(o.order_id) as orders
            FROM " . DB_PREFIX . "order o
            INNER JOIN " . DB_PREFIX . "marketing_lead l ON o.customer_id = l.customer_id
            WHERE l.converted = '1' 
            AND o.order_status_id > 0
            AND o.date_added >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(o.date_added)
            ORDER BY date ASC
        ");

        $data = array();
        foreach ($query->rows as $row) {
            $data[] = array(
                'date' => $row['date'],
                'revenue' => (float)$row['revenue'],
                'orders' => (int)$row['orders']
            );
        }

        return $data;
    }

    public function getCampaigns() {
        $query = $this->db->query("
            SELECT campaign_id, name 
            FROM " . DB_PREFIX . "marketing_campaign 
            WHERE status = '1' 
            ORDER BY name ASC
        ");

        return $query->rows;
    }

    public function getLeadSources() {
        $query = $this->db->query("
            SELECT DISTINCT source 
            FROM " . DB_PREFIX . "marketing_lead 
            WHERE status = '1' AND source != ''
            ORDER BY source ASC
        ");

        $sources = array();
        foreach ($query->rows as $row) {
            $sources[] = $row['source'];
        }

        return $sources;
    }

    public function getCampaignDetails($campaign_id) {
        $query = $this->db->query("
            SELECT * 
            FROM " . DB_PREFIX . "marketing_campaign 
            WHERE campaign_id = '" . (int)$campaign_id . "' 
            AND status = '1'
        ");

        return $query->row;
    }

    public function getCampaignMetrics($campaign_id) {
        $query = $this->db->query("
            SELECT 
                COUNT(l.lead_id) as total_leads,
                SUM(CASE WHEN l.converted = '1' THEN 1 ELSE 0 END) as conversions,
                SUM(CASE WHEN l.converted = '1' THEN o.total ELSE 0 END) as revenue,
                c.budget
            FROM " . DB_PREFIX . "marketing_campaign c
            LEFT JOIN " . DB_PREFIX . "marketing_lead l ON c.campaign_id = l.campaign_id
            LEFT JOIN " . DB_PREFIX . "order o ON l.customer_id = o.customer_id AND l.converted = '1'
            WHERE c.campaign_id = '" . (int)$campaign_id . "'
            GROUP BY c.campaign_id
        ");

        if ($query->row) {
            $row = $query->row;
            $conversion_rate = $row['total_leads'] > 0 ? round(($row['conversions'] / $row['total_leads']) * 100, 2) : 0;
            $roi = $row['budget'] > 0 ? round((($row['revenue'] - $row['budget']) / $row['budget']) * 100, 2) : 0;
            
            return array(
                'total_leads' => (int)$row['total_leads'],
                'conversions' => (int)$row['conversions'],
                'conversion_rate' => $conversion_rate,
                'revenue' => (float)$row['revenue'],
                'budget' => (float)$row['budget'],
                'roi' => $roi
            );
        }

        return array();
    }

    public function getCampaignTimeline($campaign_id) {
        $query = $this->db->query("
            SELECT 
                DATE(date_added) as date,
                COUNT(*) as leads,
                SUM(CASE WHEN converted = '1' THEN 1 ELSE 0 END) as conversions
            FROM " . DB_PREFIX . "marketing_lead 
            WHERE campaign_id = '" . (int)$campaign_id . "' 
            AND status = '1'
            GROUP BY DATE(date_added)
            ORDER BY date ASC
        ");

        return $query->rows;
    }

    public function generateReport($report_type, $date_from = '', $date_to = '') {
        $data = array();
        
        $where_date = '';
        if ($date_from && $date_to) {
            $where_date = " AND DATE(date_added) BETWEEN '" . $this->db->escape($date_from) . "' AND '" . $this->db->escape($date_to) . "'";
        }

        switch ($report_type) {
            case 'campaign_performance':
                $data = $this->getCampaignPerformanceData();
                break;
            case 'lead_sources':
                $data = $this->getLeadSourcesData();
                break;
            case 'conversion_funnel':
                $data = $this->getConversionFunnelData();
                break;
            case 'revenue_trend':
                $data = $this->getRevenueTrendData();
                break;
        }

        return $data;
    }
}
