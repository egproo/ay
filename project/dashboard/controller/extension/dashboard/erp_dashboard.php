<?php
class ControllerExtensionDashboardErpDashboard extends Controller {
    private $error = array();

    // -----------------------------------------------------------
    // index(): صفحة الإعداد الخاصة باللوحة ضمن Extensions > Dashboard
    // -----------------------------------------------------------
    public function index() {
        $this->load->language('extension/dashboard/erp_dashboard');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('dashboard_erp_dashboard', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('marketplace/extension','user_token='.$this->session->data['user_token'].'&type=dashboard', true));
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard','user_token='.$this->session->data['user_token'],true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension','user_token='.$this->session->data['user_token'].'&type=dashboard',true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/dashboard/erp_dashboard','user_token='.$this->session->data['user_token'],true)
        );

        $data['action'] = $this->url->link('extension/dashboard/erp_dashboard','user_token='.$this->session->data['user_token'],true);
        $data['cancel'] = $this->url->link('marketplace/extension','user_token='.$this->session->data['user_token'].'&type=dashboard',true);

        if (isset($this->request->post['dashboard_erp_dashboard_width'])) {
            $data['dashboard_erp_dashboard_width'] = $this->request->post['dashboard_erp_dashboard_width'];
        } else {
            $data['dashboard_erp_dashboard_width'] = $this->config->get('dashboard_erp_dashboard_width');
        }

        $data['columns'] = array();
        for ($i=3; $i<=12; $i++){
            $data['columns'][] = $i;
        }

        if (isset($this->request->post['dashboard_erp_dashboard_status'])) {
            $data['dashboard_erp_dashboard_status'] = $this->request->post['dashboard_erp_dashboard_status'];
        } else {
            $data['dashboard_erp_dashboard_status'] = $this->config->get('dashboard_erp_dashboard_status');
        }

        if (isset($this->request->post['dashboard_erp_dashboard_sort_order'])) {
            $data['dashboard_erp_dashboard_sort_order'] = $this->request->post['dashboard_erp_dashboard_sort_order'];
        } else {
            $data['dashboard_erp_dashboard_sort_order'] = $this->config->get('dashboard_erp_dashboard_sort_order');
        }

        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/dashboard/erp_dashboard_form',$data));
    }

    // -----------------------------------------------------------
    // dashboard(): تعرض محتوى اللوحة في الصفحة الرئيسية للادارة
    // -----------------------------------------------------------
    public function dashboard() {
        $this->load->language('extension/dashboard/erp_dashboard');

        if (!$this->config->get('dashboard_erp_dashboard_status')) {
            return '';
        }

        $data['user_token'] = $this->session->data['user_token'];
        $data['branches']   = $this->getAllBranches();

        // افتراض: نجلب بيانات لشهر الحالي
        list($start, $end) = $this->resolveDates('month','','');

        $data['stats']         = $this->buildErpStats($start, $end, 'all');
        $data['latest_orders'] = $this->getLatestOrders(5, $start, $end, 'all');
        $data['latest_pos']    = $this->getLatestPurchaseOrders(5);
        $data['overdue_invoices'] = $this->getOverdueSupplierInvoices(5);
        $data['top_products']  = $this->getTopSellingProducts($start, $end, 'all', 5);
        $data['low_stock']     = $this->getLowStockItems(5);

        return $this->load->view('extension/dashboard/erp_dashboard_info', $data);
    }

    // -----------------------------------------------------------
    // ajaxFilter(): فلترة عبر AJAX عند اختيار تاريخ او فرع
    // -----------------------------------------------------------
    public function ajaxFilter() {
        $json = array();

        if (!$this->user->hasPermission('access','extension/dashboard/erp_dashboard')){
            $json['error'] = 'No Permission!';
            $this->response->addHeader('Content-Type: application/json');
            return $this->response->setOutput(json_encode($json));
        }

        $branch_id  = isset($this->request->post['branch_id']) ? $this->request->post['branch_id'] : 'all';
        $periodType = isset($this->request->post['periodType']) ? $this->request->post['periodType'] : 'month';
        $date_start = isset($this->request->post['date_start']) ? $this->request->post['date_start'] : '';
        $date_end   = isset($this->request->post['date_end'])   ? $this->request->post['date_end']   : '';
        list($start, $end) = $this->resolveDates($periodType, $date_start, $date_end);

        $stats          = $this->buildErpStats($start, $end, $branch_id);
        $latest_orders  = $this->getLatestOrders(5, $start, $end, $branch_id);
        $latest_pos     = $this->getLatestPurchaseOrders(5);
        $attendance     = $this->getAttendanceStats($start, $end);
        $finance        = array(
            'bank_balance' => $this->getBankBalance(),
            'ar_amount'    => $this->getARAmount(),
            'ap_amount'    => $this->getAPAmount()
        );
        $overdue_invs   = $this->getOverdueSupplierInvoices(5);
        $top_products   = $this->getTopSellingProducts($start, $end, $branch_id, 5);
        $low_stock      = $this->getLowStockItems(5);

        $json['stats']            = $stats;
        $json['latest_orders']    = $latest_orders;
        $json['latest_pos']       = $latest_pos;
        $json['attendance']       = $attendance;
        $json['finance']          = $finance;
        $json['overdue_invoices'] = $overdue_invs;
        $json['top_products']     = $top_products;
        $json['low_stock']        = $low_stock;

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // -----------------------------------------------------------
    // buildErpStats(): جلب بعض الارقام الرئيسية (المبيعات, المشتريات, المخزون..الخ)
    // -----------------------------------------------------------
    private function buildErpStats($date_start, $date_end, $branch_id){
        // المبيعات
        $sql_sales = "SELECT COUNT(*) AS total_orders, IFNULL(SUM(total),0) AS total_sales
                      FROM `".DB_PREFIX."order`
                      WHERE date_added >= '".$this->db->escape($date_start)." 00:00:00'
                        AND date_added <= '".$this->db->escape($date_end)." 23:59:59'
                        AND order_status_id>0";
        if($branch_id!='all'){
            $sql_sales .= " AND order_posuser_id='".(int)$branch_id."'";
        }
        $q_sales = $this->db->query($sql_sales);
        $sales_data = (isset($q_sales->row)) ? $q_sales->row : ['total_orders'=>0,'total_sales'=>0];

        // المشتريات
        $sql_po = "SELECT COUNT(*) AS total_po, IFNULL(SUM(total_amount),0) AS total_po_amount
                   FROM `cod_purchase_order`
                   WHERE created_at>='".$this->db->escape($date_start)." 00:00:00'
                     AND created_at<='".$this->db->escape($date_end)." 23:59:59'";
        $q_po = $this->db->query($sql_po);
        $po_data = (isset($q_po->row)) ? $q_po->row : ['total_po'=>0,'total_po_amount'=>0];

        // المخزون
        $sql_inv = "SELECT COUNT(*) AS total_skus, IFNULL(SUM(quantity),0) AS total_qty
                    FROM `cod_product_inventory`
                    WHERE 1";
        if($branch_id!='all'){
            $sql_inv .= " AND branch_id='".(int)$branch_id."'";
        }
        $q_inv = $this->db->query($sql_inv);
        $inv_data = (isset($q_inv->row)) ? $q_inv->row : ['total_skus'=>0,'total_qty'=>0];

        // الحسابات
        $sql_acc = "SELECT COUNT(*) AS total_accounts
                    FROM `cod_accounts`
                    WHERE status='1'";
        $q_acc = $this->db->query($sql_acc);
        $acc_data = (isset($q_acc->row)) ? $q_acc->row : ['total_accounts'=>0];
        $accounts_balance = $this->getBankBalance(); // من جداول البنك فقط

        // الموظفون 
        $sql_emp = "SELECT COUNT(*) AS total_active
                    FROM `cod_employee_profile`
                    WHERE status='active'";
        $q_emp = $this->db->query($sql_emp);
        $emp_data = (isset($q_emp->row)) ? $q_emp->row : ['total_active'=>0];

        // الطلبات المتأخرة
        $delayed_data = $this->getDelayedOrders($date_start, $date_end, $branch_id);

        // نسبة المرتجعات
        $return_rate  = $this->getReturnRate($date_start, $date_end, $branch_id);

        return [
            'sales' => $sales_data,
            'purchase' => $po_data,
            'inventory'=> $inv_data,
            'accounts' => [
                'total_accounts' => $acc_data['total_accounts'],
                'total_balance'  => $accounts_balance
            ],
            'hr'       => $emp_data,
            'cancel'   => 0, // (لن نستدعي getCancellationRate حاليا لتجنب الاخطاء)
            'attendance'=> ['rate'=>0,'present_count'=>0],
            'delayed'  => $delayed_data,
            'return_rate'=> $return_rate
        ];
    }

    // -----------------------------------------------------------
    // استعلام ياتي بقائمة الفروع
    // -----------------------------------------------------------
    private function getAllBranches(){
        $sql = "SELECT branch_id, name FROM `cod_branch` ORDER BY name ASC";
        $q = $this->db->query($sql);
        return $q->rows;
    }

    // -----------------------------------------------------------
    // جلب أحدث الطلبات
    // -----------------------------------------------------------
    private function getLatestOrders($limit=5, $date_start='', $date_end='', $branch_id='all'){
        $sql = "SELECT order_id, firstname, lastname, total, date_added, order_status_id
                FROM `".DB_PREFIX."order`
                WHERE order_status_id>0";
        if($date_start && $date_end){
            $sql .= " AND date_added>='".$this->db->escape($date_start)." 00:00:00'
                      AND date_added<='".$this->db->escape($date_end)." 23:59:59'";
        }
        if($branch_id!='all'){
            $sql .= " AND order_posuser_id='".(int)$branch_id."'";
        }
        $sql .= " ORDER BY order_id DESC LIMIT ".(int)$limit;
        $q = $this->db->query($sql);
        $rows = $q->rows;
        foreach($rows as &$r){
            $r['order_status'] = $this->getOrderStatusName($r['order_status_id']);
        }
        return $rows;
    }

    // -----------------------------------------------------------
    // جلب أحدث أوامر الشراء
    // -----------------------------------------------------------
    private function getLatestPurchaseOrders($limit=5){
        $sql = "SELECT po_id, po_number, supplier_id, total_amount, status, created_at
                FROM `cod_purchase_order`
                ORDER BY po_id DESC
                LIMIT ".(int)$limit;
        $q = $this->db->query($sql);
        $rows = $q->rows;
        foreach($rows as &$r){
            $r['vendor_name'] = $this->getVendorName($r['vendor_id']);
        }
        return $rows;
    }

    private function getVendorName($vendor_id){
        $sql = "SELECT firstname, lastname
                FROM `cod_supplier`
                WHERE supplier_id='".(int)$vendor_id."' LIMIT 1";
        $q = $this->db->query($sql);
        if($q->num_rows){
            return trim($q->row['firstname'].' '.$q->row['lastname']);
        }
        return '';
    }

    // -----------------------------------------------------------
    // جلب اسم حالة الطلب (order_status)
    // -----------------------------------------------------------
    private function getOrderStatusName($status_id){
        $sql = "SELECT name
                FROM `".DB_PREFIX."order_status`
                WHERE order_status_id='".(int)$status_id."'
                  AND language_id='".(int)$this->config->get('config_language_id')."'";
        $q = $this->db->query($sql);
        if($q->num_rows){
            return $q->row['name'];
        }
        return '';
    }

    // -----------------------------------------------------------
    // Delayed Orders
    // -----------------------------------------------------------
    private function getDelayedOrders($start, $end, $branch_id){
        $sql = "SELECT COUNT(*) as delayed_orders
                FROM `".DB_PREFIX."order`
                WHERE date_added>='".$this->db->escape($start)." 00:00:00'
                  AND date_added<='".$this->db->escape($end)." 23:59:59'
                  AND order_status_id IN (5,9)"; 
        if($branch_id!='all'){
            $sql .= " AND order_posuser_id='".(int)$branch_id."'";
        }
        $q = $this->db->query($sql);
        if($q->num_rows){
            return ['delayed_orders'=>$q->row['delayed_orders']];
        }
        return ['delayed_orders'=>0];
    }

    // -----------------------------------------------------------
    // نسبة المرتجعات (عدد المرتجعات / عدد الطلبات)
    // -----------------------------------------------------------
    private function getReturnRate($start, $end, $branch_id){
        $sql_tot = "SELECT COUNT(*) as cnt
                    FROM `".DB_PREFIX."order`
                    WHERE date_added>='".$this->db->escape($start)." 00:00:00'
                      AND date_added<='".$this->db->escape($end)." 23:59:59'
                      AND order_status_id>0";
        if($branch_id!='all'){
            $sql_tot .= " AND order_posuser_id='".(int)$branch_id."'";
        }
        $q_tot = $this->db->query($sql_tot);
        $total_orders = (int)($q_tot->row['cnt']??0);

        $sql_ret = "SELECT COUNT(*) as cnt
                    FROM `cod_return`
                    WHERE date_added>='".$this->db->escape($start)." 00:00:00'
                      AND date_added<='".$this->db->escape($end)." 23:59:59'";
        $q_ret = $this->db->query($sql_ret);
        $returns = (int)($q_ret->row['cnt']??0);

        if($total_orders==0) return 0;
        return round(($returns/$total_orders)*100,2);
    }

    // -----------------------------------------------------------
    // resolveDates(): تحويل periodType الى تواريخ start/end
    // -----------------------------------------------------------
    private function resolveDates($periodType, $date_start, $date_end){
        if($periodType=='today'){
            $t = date('Y-m-d');
            return [$t,$t];
        }
        if($periodType=='week'){
            $start = date('Y-m-d', strtotime('monday this week'));
            $end   = date('Y-m-d', strtotime('sunday this week'));
            return [$start,$end];
        }
        if($periodType=='month'){
            $start = date('Y-m-01');
            $end   = date('Y-m-t');
            return [$start,$end];
        }
        if($periodType=='quarter'){
            $m=(int)date('m');
            $q=intval(($m-1)/3)+1;
            $sm = ($q-1)*3+1;
            $start = date('Y').'-'.str_pad($sm,2,'0',STR_PAD_LEFT).'-01';
            $em = $sm+2;
            $d = cal_days_in_month(CAL_GREGORIAN,$em,date('Y'));
            $end = date('Y').'-'.str_pad($em,2,'0',STR_PAD_LEFT).'-'.$d;
            return [$start,$end];
        }
        if($periodType=='year'){
            $start = date('Y-01-01');
            $end   = date('Y-12-31');
            return [$start,$end];
        }
        if($periodType=='custom' && $date_start && $date_end){
            return [$date_start,$date_end];
        }
        $t = date('Y-m-d');
        return [$t,$t];
    }

    // -----------------------------------------------------------
    // attendance: كمثال
    // -----------------------------------------------------------
    private function getAttendanceStats($start, $end){
        $sql_total = "SELECT COUNT(*) as total
                      FROM `cod_employee_profile`
                      WHERE status='active'";
        $q_total = $this->db->query($sql_total);
        $total_emp = (int)($q_total->row['total']??0);

        $sql_att = "SELECT COUNT(distinct user_id) as present_count
                    FROM `cod_attendance`
                    WHERE date>='".$this->db->escape($start)."'
                      AND date<='".$this->db->escape($end)."'
                      AND status='present'";
        $q_att = $this->db->query($sql_att);
        $present_count = (int)($q_att->row['present_count']??0);

        $rate=0;
        if($total_emp>0){
            $rate = round(($present_count/$total_emp)*100,2);
        }
        return ['rate'=>$rate,'present_count'=>$present_count];
    }

    // -----------------------------------------------------------
    // getBankBalance(): من جدول cod_bank_account فقط
    // -----------------------------------------------------------
    private function getBankBalance(){
        $sql = "SELECT IFNULL(SUM(current_balance),0) as bal
                FROM `cod_bank_account`";
        $q = $this->db->query($sql);
        return (float)($q->row['bal']??0);
    }

    // -----------------------------------------------------------
    // getARAmount(): مجموع ذمم العملاء من cod_customer_transaction.amount
    // -----------------------------------------------------------
    private function getARAmount(){
        $sql = "SELECT IFNULL(SUM(amount),0) as amt
                FROM `cod_customer_transaction`";
        $q = $this->db->query($sql);
        return (float)($q->row['amt']??0);
    }

    // -----------------------------------------------------------
    // getAPAmount(): ذمم الموردين من cod_supplier_invoice
    // -----------------------------------------------------------
    private function getAPAmount(){
        $sql = "SELECT IFNULL(SUM(total_amount - discount_amount - tax_amount),0) as amt
                FROM `cod_supplier_invoice`
                WHERE status IN ('pending','approved')";
        $q = $this->db->query($sql);
        return (float)($q->row['amt']??0);
    }

    // -----------------------------------------------------------
    // getOverdueSupplierInvoices():
    // -----------------------------------------------------------
    private function getOverdueSupplierInvoices($limit=5){
        $today = date('Y-m-d');
        $sql = "SELECT invoice_id, invoice_number, vendor_id, total_amount, due_date, status
                FROM `cod_supplier_invoice`
                WHERE status IN ('pending','approved')
                  AND due_date < '".$this->db->escape($today)."'
                ORDER BY due_date ASC
                LIMIT ".(int)$limit;
        $q = $this->db->query($sql);
        $rows = $q->rows;
        foreach($rows as &$r){
            $r['vendor_name'] = $this->getVendorName($r['vendor_id']);
        }
        return $rows;
    }

    // -----------------------------------------------------------
    // getTopSellingProducts():
    // -----------------------------------------------------------
    private function getTopSellingProducts($start, $end, $branch_id, $limit=5){
        $sql = "SELECT op.product_id, SUM(op.quantity) as qty, SUM(op.total) as totalval, pd.name
                FROM `".DB_PREFIX."order_product` op
                JOIN `".DB_PREFIX."product_description` pd ON (op.product_id=pd.product_id)
                JOIN `".DB_PREFIX."order` o ON (op.order_id=o.order_id)
                WHERE o.order_status_id>0
                  AND o.date_added>='".$this->db->escape($start)." 00:00:00'
                  AND o.date_added<='".$this->db->escape($end)." 23:59:59'
                  AND pd.language_id='".(int)$this->config->get('config_language_id')."'";
        if($branch_id!='all'){
            $sql.=" AND o.order_posuser_id='".(int)$branch_id."'";
        }
        $sql.=" GROUP BY op.product_id
                ORDER BY qty DESC
                LIMIT ".(int)$limit;
        $q = $this->db->query($sql);
        return $q->rows;
    }

    // -----------------------------------------------------------
    // getLowStockItems():
    // -----------------------------------------------------------
    private function getLowStockItems($limit=5){
        $sql = "SELECT pi.product_id, pd.name, pi.quantity, p.model
                FROM `cod_product_inventory` pi
                JOIN `".DB_PREFIX."product` p ON (pi.product_id=p.product_id)
                JOIN `".DB_PREFIX."product_description` pd ON (p.product_id=pd.product_id)
                WHERE pd.language_id='".(int)$this->config->get('config_language_id')."'
                ORDER BY pi.quantity ASC
                LIMIT ".(int)$limit;
        $q = $this->db->query($sql);
        return $q->rows;
    }

    // -----------------------------------------------------------
    // validate()
    // -----------------------------------------------------------
    protected function validate(){
        if(!$this->user->hasPermission('modify','extension/dashboard/erp_dashboard')){
            $this->error['warning'] = $this->language->get('error_permission');
        }
        return !$this->error;
    }

    public function install(){}
    public function uninstall(){}
}
