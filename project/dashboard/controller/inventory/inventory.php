<?php
class ControllerInventoryInventory extends Controller {
    private $error = array();

    // =========== index() ===========
    public function index() {
        // 1) ملف اللغة
        $this->load->language('inventory/inventory');

        // 2) عنوان الصفحة
        $this->document->setTitle($this->language->get('heading_title'));

        // 3) Breadcrumbs
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard','user_token='.$this->session->data['user_token'],true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('inventory/inventory','user_token='.$this->session->data['user_token'],true)
        );

        // 4) الصلاحيات
        $data['can_modify'] = $this->user->hasPermission('modify','inventory/inventory');

        // 5) تمرير user_token
        $data['user_token'] = $this->session->data['user_token'];

        // 6) أزرار التصدير والطباعة
        $data['export_csv_action'] = $this->url->link('inventory/inventory/exportCsv','user_token='.$this->session->data['user_token'],true);
        $data['export_pdf_action'] = $this->url->link('inventory/inventory/exportPdf','user_token='.$this->session->data['user_token'],true);
        $data['print_action']      = $this->url->link('inventory/inventory/printList','user_token='.$this->session->data['user_token'],true);

        // 7) دمج أجزاء القالب
        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        // 8) إخراج القالب
        $this->response->setOutput($this->load->view('inventory/inventory', $data));
    }

    // =========== getList() ===========
    // تُستدعى بأسلوب ServerSide من DataTables
    public function getList() {
        // تأكد أنه POST
        if ($this->request->server['REQUEST_METHOD'] != 'POST') {
            $this->response->redirect($this->url->link('error/not_found'));
            return;
        }

        $this->load->language('inventory/inventory');
        $this->load->model('inventory/inventory');

        // قراءة بارامترات الـDataTables
        $draw   = isset($this->request->post['draw']) ? (int)$this->request->post['draw'] : 1;
        $start  = isset($this->request->post['start']) ? (int)$this->request->post['start'] : 0;
        $length = isset($this->request->post['length'])? (int)$this->request->post['length']: 10;

        // ترتيب/فرز
        $orderColumnIndex = 0;
        $orderDir         = 'ASC';
        if (isset($this->request->post['order'][0])) {
            $orderColumnIndex = (int)$this->request->post['order'][0]['column'];
            $orderDir         = ($this->request->post['order'][0]['dir'] == 'desc') ? 'DESC' : 'ASC';
        }
        $columns = array('branch_name','product_name','unit_name','quantity','average_cost','total_value');
        $sortColumnName = isset($columns[$orderColumnIndex]) ? $columns[$orderColumnIndex] : 'branch_name';

        // البحث العام
        $searchValue = isset($this->request->post['search']['value']) ? $this->request->post['search']['value'] : '';

        // فلاتر أخرى
        $filter_branch_id   = isset($this->request->post['filter_branch_id']) ? (int)$this->request->post['filter_branch_id'] : 0;
        $filter_product_id  = isset($this->request->post['filter_product_id'])? (int)$this->request->post['filter_product_id']: 0;
        $filter_consignment = $this->request->post['filter_consignment'] !== '' 
                              ? $this->request->post['filter_consignment'] : null;

        $filter_data = array(
            'search'             => $searchValue,
            'filter_branch_id'   => $filter_branch_id,
            'filter_product_id'  => $filter_product_id,
            'filter_consignment' => $filter_consignment,
            'sort'               => $sortColumnName,
            'order'              => $orderDir,
            'start'              => $start,
            'limit'              => $length
        );

        // إجمالي السجلات بدون فلترة
        $recordsTotal = $this->model_inventory_inventory->getTotalInventory();

        // جلب البيانات
        $results = $this->model_inventory_inventory->getInventoryList($filter_data);

        // إجمالي السجلات بعد الفلترة
        $recordsFiltered = $this->model_inventory_inventory->getTotalInventory($filter_data);

        $json_data = array(
            "draw"            => $draw,
            "recordsTotal"    => $recordsTotal,
            "recordsFiltered" => $recordsFiltered,
            "data"            => array()
        );

        foreach ($results as $row) {
            // تشكيل صف الداتا
            $json_data['data'][] = array(
                $row['branch_name'],
                $row['product_name'],
                $row['unit_name'],
                $row['quantity'],
                number_format($row['average_cost'], 2),
                number_format($row['total_value'], 2)  // لو أضفت عمودها
            );
        }

        // إخراج JSON
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json_data));
    }

    // =========== exportCsv() ===========
    // تصدير ملف CSV بناء على فلاتر
    public function exportCsv() {
        $this->load->language('inventory/inventory');
        $this->load->model('inventory/inventory');

        // نقرأ الفلاتر من GET أو POST
        $filter_branch_id   = isset($this->request->post['filter_branch_id']) ? (int)$this->request->post['filter_branch_id'] : 0;
        $filter_product_id  = isset($this->request->post['filter_product_id'])? (int)$this->request->post['filter_product_id']: 0;
        $filter_consignment = ($this->request->post['filter_consignment'] !== '') ? $this->request->post['filter_consignment'] : null;

        $filter_data = array(
            'search'             => '', // لا نطبق بحث عام هنا (أو يمكن أخذه من POST)
            'filter_branch_id'   => $filter_branch_id,
            'filter_product_id'  => $filter_product_id,
            'filter_consignment' => $filter_consignment,
            'sort'  => 'branch_name',
            'order' => 'ASC',
            'start' => 0,
            'limit' => 100000 // رقم كبير لجلب كل السجلات
        );

        $results = $this->model_inventory_inventory->getInventoryList($filter_data);

        // تجهيز الرأس
        $output  = "Branch,Product,Unit,Quantity,AvgCost,TotalValue\n";
        foreach ($results as $row) {
            $line = sprintf('"%s","%s","%s","%s","%s","%s"'."\n",
                $row['branch_name'],
                $row['product_name'],
                $row['unit_name'],
                $row['quantity'],
                number_format($row['average_cost'], 2),
                number_format($row['total_value'], 2)
            );
            $output .= $line;
        }

        $filename = 'inventory_export_'.date('Y-m-d_His').'.csv';

        // تهيئة الهيدر
        $this->response->addHeader('Pragma: public');
        $this->response->addHeader('Expires: 0');
        $this->response->addHeader('Content-Description: File Transfer');
        $this->response->addHeader('Content-Type: text/csv');
        $this->response->addHeader('Content-Disposition: attachment; filename='.$filename);
        $this->response->addHeader('Content-Transfer-Encoding: binary');

        $this->response->setOutput($output);
    }

    // =========== exportPdf() ===========
    // يتطلب وجود مكتبة TCPDF أو ما يعادلها
    public function exportPdf() {
        $this->load->language('inventory/inventory');
        $this->load->model('inventory/inventory');

        // مثل exportCsv, نجلب الفلاتر
        $filter_branch_id   = isset($this->request->post['filter_branch_id']) ? (int)$this->request->post['filter_branch_id'] : 0;
        $filter_product_id  = isset($this->request->post['filter_product_id'])? (int)$this->request->post['filter_product_id']: 0;
        $filter_consignment = ($this->request->post['filter_consignment'] !== '') ? $this->request->post['filter_consignment'] : null;

        $filter_data = array(
            'filter_branch_id'   => $filter_branch_id,
            'filter_product_id'  => $filter_product_id,
            'filter_consignment' => $filter_consignment,
            'sort'  => 'branch_name',
            'order' => 'ASC',
            'start' => 0,
            'limit' => 100000
        );
        $results = $this->model_inventory_inventory->getInventoryList($filter_data);

        // استدعاء ملف TCPDF
        $this->load->library('tcpdf/tcpdf'); 
        // أو: require_once(DIR_SYSTEM.'library/tcpdf/tcpdf.php');

        $pdf = new TCPDF('P','mm','A4', true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('YourCompany');
        $pdf->SetTitle($this->language->get('heading_title'));
        $pdf->SetHeaderData('', 0, $this->language->get('heading_title'), date('Y-m-d H:i'));
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
        $pdf->SetMargins(10, 15, 10);
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(10);
        $pdf->SetAutoPageBreak(TRUE, 10);
        $pdf->AddPage();

        // تصميم الجدول داخل الـHTML
        $html  = '<h3>'.$this->language->get('heading_title').'</h3>';
        $html .= '<table border="1" cellpadding="4" cellspacing="0">';
        $html .= '<thead>
            <tr bgcolor="#cccccc">
              <th>'.$this->language->get('column_branch').'</th>
              <th>'.$this->language->get('column_product').'</th>
              <th>'.$this->language->get('column_unit').'</th>
              <th>'.$this->language->get('column_quantity').'</th>
              <th>'.$this->language->get('column_average_cost').'</th>
              <th>'.$this->language->get('column_total_value').'</th>
            </tr>
        </thead>
        <tbody>';
        foreach ($results as $row) {
            $html .= '<tr>
              <td>'. $row['branch_name'] .'</td>
              <td>'. $row['product_name'] .'</td>
              <td>'. $row['unit_name'] .'</td>
              <td>'. $row['quantity'] .'</td>
              <td>'. number_format($row['average_cost'],2) .'</td>
              <td>'. number_format($row['total_value'],2) .'</td>
            </tr>';
        }
        $html .= '</tbody></table>';

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('Inventory_'.date('Y-m-d_His').'.pdf','I');
    }

    // =========== printList() ===========
    // تعرض صفحة HTML جاهزة للطباعة
    public function printList() {
        $this->load->language('inventory/inventory');
        $this->load->model('inventory/inventory');

        // نفس الفلاتر
        $filter_branch_id   = isset($this->request->get['filter_branch_id']) ? (int)$this->request->get['filter_branch_id'] : 0;
        $filter_product_id  = isset($this->request->get['filter_product_id'])? (int)$this->request->get['filter_product_id']: 0;
        $filter_consignment = ($this->request->get['filter_consignment'] !== '') ? $this->request->get['filter_consignment'] : null;

        $filter_data = array(
            'filter_branch_id'   => $filter_branch_id,
            'filter_product_id'  => $filter_product_id,
            'filter_consignment' => $filter_consignment,
            'sort'  => 'branch_name',
            'order' => 'ASC',
            'start' => 0,
            'limit' => 999999
        );
        $results = $this->model_inventory_inventory->getInventoryList($filter_data);

        $data['heading_title'] = $this->language->get('heading_title');
        $data['results']       = $results;

        // عرض قالب مصغر للطباعة
        $this->response->setOutput($this->load->view('inventory/print_inventory', $data));
    }

    // ========================================================================
    // مثال: ربط بسيط مع الحسابات (تسجيل قيد عند تحديث مخزون)
    // ========================================================================
    public function addAccountingEntryExample($product_inventory_id, $difference, $cost) {
        // هذا مجرد مثال افتراضي
        // لو قمت بتحديث المخزون وزادت قيمته 5000، قد تسجل قيد
        // Debit Inventory Account    5000
        // Credit SomeAccount        5000
        // بما أن لديك جداول محاسبية. here's a stub:

        if ($this->user->hasPermission('modify','inventory/inventory')) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "journal_entries SET 
                journal_id    = 0,
                account_code  = '150101', -- مثلا حساب المخزون
                is_debit      = 1,
                amount        = '".(float)$cost."',
                date_added    = NOW()
            ");
            // ... بقية القيود
        }
    }

}
