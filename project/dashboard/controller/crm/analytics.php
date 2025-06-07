<?php
class ControllerCrmAnalytics extends Controller {
    public function index() {
        $this->load->language('crm/analytics');
        $this->document->setTitle($this->language->get('heading_title'));

        $data['user_token'] = $this->session->data['user_token'];

        // روابط Ajax
        $data['ajax_stats_url'] = $this->url->link('crm/analytics/stats', 'user_token=' . $this->session->data['user_token'], true);

        // النصوص
        $data['heading_title']             = $this->language->get('heading_title');
        $data['text_filter']               = $this->language->get('text_filter');
        $data['text_date_start']           = $this->language->get('text_date_start');
        $data['text_date_end']             = $this->language->get('text_date_end');
        $data['button_filter']             = $this->language->get('button_filter');
        $data['button_reset']              = $this->language->get('button_reset');
        $data['text_overview']             = $this->language->get('text_overview');
        $data['text_total_leads']          = $this->language->get('text_total_leads');
        $data['text_total_opportunities']  = $this->language->get('text_total_opportunities');
        $data['text_total_deals_closed_won']= $this->language->get('text_total_deals_closed_won');
        $data['text_total_visits']         = $this->language->get('text_total_visits');
        $data['text_ajax_error']           = $this->language->get('text_ajax_error');

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard','user_token=' . $this->session->data['user_token'],true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_crm'),
            'href' => ''
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('crm/analytics','user_token=' . $this->session->data['user_token'],true)
        );

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('crm/analytics', $data));
    }

    public function stats() {
        $this->load->language('crm/analytics');

        $filter_date_start = isset($this->request->post['filter_date_start']) ? $this->request->post['filter_date_start'] : '';
        $filter_date_end = isset($this->request->post['filter_date_end']) ? $this->request->post['filter_date_end'] : '';

        $json = array();

        try {
            // سنحسب الأرقام من الجداول:
            // leads: من جدول cod_crm_lead
            $leads = $this->countLeads($filter_date_start,$filter_date_end);
            // opportunities: من جدول cod_crm_opportunity
            $opps = $this->countOpportunities($filter_date_start,$filter_date_end);
            // deals won: من جدول cod_crm_deal حيث stage=closed_won
            $deals_won = $this->countDealsWon($filter_date_start,$filter_date_end);
            // visits from cod_visitors_stats
            $visits = $this->sumVisits($filter_date_start,$filter_date_end);

            $json['leads'] = $leads;
            $json['opportunities'] = $opps;
            $json['deals_won'] = $deals_won;
            $json['visits'] = $visits;
        } catch (Exception $e) {
            $json['error'] = $this->language->get('text_ajax_error').': '.$e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    private function countLeads($date_start,$date_end) {
        $sql = "SELECT COUNT(*) as total FROM `cod_crm_lead` WHERE 1";
        $sql .= $this->dateFilter($date_start,$date_end,'date_added');
        $query=$this->db->query($sql);
        return (int)$query->row['total'];
    }

    private function countOpportunities($date_start,$date_end) {
        $sql="SELECT COUNT(*) as total FROM `cod_crm_opportunity` WHERE 1";
        $sql .= $this->dateFilter($date_start,$date_end,'date_added');
        $query=$this->db->query($sql);
        return (int)$query->row['total'];
    }

    private function countDealsWon($date_start,$date_end) {
        $sql="SELECT COUNT(*) as total FROM `cod_crm_deal` WHERE stage='closed_won'";
        $sql .= $this->dateFilter($date_start,$date_end,'date_added');
        $query=$this->db->query($sql);
        return (int)$query->row['total'];
    }

    private function sumVisits($date_start,$date_end) {
        if (!$date_start || !$date_end) return 0;
        $sql="SELECT SUM(visits) as total FROM `cod_visitors_stats` WHERE visit_date BETWEEN '".$this->db->escape($date_start)."' AND '".$this->db->escape($date_end)."'";
        $query=$this->db->query($sql);
        return $query->row['total'] ? (int)$query->row['total'] : 0;
    }

    private function dateFilter($date_start,$date_end,$field) {
        $condition='';
        if($date_start && $date_end) {
            $condition = " AND $field BETWEEN '".$this->db->escape($date_start)." 00:00:00' AND '".$this->db->escape($date_end)." 23:59:59'";
        }
        return $condition;
    }
}
