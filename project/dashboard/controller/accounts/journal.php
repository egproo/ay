<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
class ControllerAccountsJournal extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('accounts/journal');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('accounts/journal');

		$this->getList();
	}
	
    public function add() {

        $this->load->language('accounts/journal');
        $this->document->setTitle($this->language->get('heading_title'));
        
        $this->load->model('accounts/journal');
    

        $this->getForm();
    }

public function print_pdf() {
    $this->load->language('accounts/journal');
    $this->load->model('accounts/journal');

    if (!$this->user->hasPermission('modify', 'accounts/journal')) {
        $json['error'] = $this->language->get('error_permission');
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
        return;
    }

    $journal_ids = isset($this->request->post['journal_ids']) ? $this->request->post['journal_ids'] : [];
    
    if (empty($journal_ids)) {
        $json['error'] = 'No journals selected for printing.';
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
        return;
    }

    $pdf = new TCPDF('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Your Company Name');
    $pdf->SetTitle('Journal Entries');
    $pdf->SetSubject('Multiple Journal Entries');

    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    
    foreach ($journal_ids as $journal_id) {
        $journal_data = $this->model_accounts_journal->getJournal($journal_id);
        if ($journal_data) {
            $pdf->AddPage();
            $html = $this->formatJournalForPrinting($journal_data);
            $pdf->writeHTML($html, true, false, true, false, '');
        }
    }

    $pdf_name = 'journals_' . date('YmdHis') . '.pdf';
    $pdf->Output(DIR_DOWNLOAD . $pdf_name, 'F');  // Save the PDF to a file

    $json['success'] = 'PDF generated successfully.';
    $json['pdf_url'] = HTTP_CATALOG . 'system/download/' . $pdf_name;
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
}

public function print_multiple() {
    $this->load->model('accounts/journal');
    $journal_ids = isset($this->request->get['journal_ids']) ? explode(',', $this->request->get['journal_ids']) : [];

    $journals_data = [];
foreach ($journal_ids as $journal_id) {
    $journal_info = $this->model_accounts_journal->getJournal($journal_id);
    $journal_data['whoprint'] = $this->user->getUserName();
    $journal_data['printdate'] = date('Y-m-d h:i');
    
    $entries = $journal_info['entries'];
    $total_debit = 0;
    $total_credit = 0;
    $formatted_entries = [];
    
    foreach ($entries as $entry) {
        if ($entry['is_debit']) {
            $total_debit += $entry['amount'];
        } else {
            $total_credit += $entry['amount'];
        }
        
        // استدعاء الدالة للحصول على معلومات الحساب
        $account_info = $this->getAccount($entry['account_code']);
        
        // إضافة معلومات الحساب إلى كل إدخال
        $formatted_entry = [
            'account_code' => $account_info['account_code'],
            'name' => $account_info['name'],
            'debit' => $entry['is_debit']?$this->currency->format($entry['amount'], $this->config->get('config_currency')):"", // يمكن تغييره إلى $entry['debit'] إذا كان هناك خطأ في الاسم
            'credit' => $entry['is_debit']?"":$this->currency->format($entry['amount'], $this->config->get('config_currency')), // يمكن تغييره إلى $entry['credit'] إذا كان هناك خطأ في الاسم
        ];
        
        $formatted_entries[] = $formatted_entry;
    }
    
    // إضافة المعلومات المنسقة للحسابات إلى بيانات القيد
    $journal_data['entries'] = $formatted_entries;
    
    // تعيين الرصيد الإجمالي للقيد
    $journal_data['total_debit'] = $this->currency->format($total_debit, $this->config->get('config_currency'), 1);
    $journal_data['total_credit'] = $this->currency->format($total_credit, $this->config->get('config_currency'), 1);
    $journal_data['journal_id'] = $journal_info['journal_id'];
    $journal_data['description'] = $journal_info['description'];
    $journal_data['refnum'] = $journal_info['refnum'];
    $journal_data['is_cancelled'] = $journal_info['is_cancelled'];
    $journal_data['cancelled_by'] = $journal_info['cancelled_by'];
    $journal_data['cancelled_date'] = $journal_info['cancelled_date'];
    $journal_data['last_edit_by'] = $journal_info['last_edit_by'];
    $journal_data['audited'] = $journal_info['audited'];
    $journal_data['audit_by'] = $journal_info['audit_by'];
    $journal_data['audit_date'] = $journal_info['audit_date'];
    $journal_data['thedate'] = date($this->language->get('date_format_short'), strtotime($journal_info['thedate']));
    $journal_data['total_debit'] = $this->currency->format($journal_info['total_debit'], $this->config->get('config_currency'));
    $journal_data['total_credit'] = $this->currency->format($journal_info['total_credit'], $this->config->get('config_currency'));
    $journal_data['is_balanced'] = $journal_info['is_balanced'];
    $journal_data['entrytype'] = $journal_info['entrytype'];
    $journal_data['created_at'] = $journal_info['created_at'];
    $journal_data['updated_at'] = $journal_info['updated_at'];
    $journal_data['added_by'] = $journal_info['added_by'];




    // إضافة بيانات القيد إلى قائمة البيانات النهائية
   if ($journal_data) {
        $journals_data[] = $journal_data;
    }


    }

    if (!empty($journals_data)) {
        $data['journals'] = $journals_data;
        
		echo($this->response->setOutput($this->load->view('accounts/journal_print_partial', $data)));
    } else {
        //$this->response->redirect($this->url->link('error/not_found', 'user_token=' . $this->session->data['user_token'], true));
    }
}
    public function getAccount($account_code){
        $sql = "SELECT a.account_id, ad.name, a.account_code, a.status, a.parent_id FROM " . DB_PREFIX . "accounts a LEFT JOIN " . DB_PREFIX . "account_description ad ON (a.account_id = ad.account_id) WHERE a.account_code='".$account_code."' and ad.language_id = '" . (int)$this->config->get('config_language_id') . "'";
    
        $query = $this->db->query($sql);
        return $query->row;
    }
public function print_single() {
    $this->load->model('accounts/journal');
    $journal_id = isset($this->request->get['journal_id']) ? $this->request->get['journal_id'] : 0;
    $journals_data = [];
    $journal_data = $this->model_accounts_journal->getJournal($journal_id);
    if ($journal_data) {
        // Generate PDF or pass data to the view
        $data['journals'][] = $journal_data;
        $this->response->setOutput($this->load->view('accounts/journal_print_partial', $data));
    } else {
        $this->response->redirect($this->url->link('error/not_found', 'user_token=' . $this->session->data['user_token'], true));
    }
}

private function formatJournalForPrinting($journal_data) {
    $html = '<h2>Journal Entry: ' . $journal_data['journal_id'] . '</h2>';
    $html .= '<table cellspacing="0" cellpadding="5" border="1" style="width: 100%;">';
    $html .= '<tr>';
    $html .= '<th colspan="2" style="text-align:center;">مدين</th>';
    $html .= '<th colspan="2" style="text-align:center;">دائن</th>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<th>حساب</th>';
    $html .= '<th>المبلغ</th>';
    $html .= '<th>حساب</th>';
    $html .= '<th>المبلغ</th>';
    $html .= '</tr>';

    $total_debit = 0;
    $total_credit = 0;

    // تخيل أن لدينا بيانات الديون والائتمان في مصفوفات
    foreach ($journal_data['entries'] as $entry) {
        if ($entry['is_debit']) {
            $html .= '<tr>';
            $html .= '<td>' . $entry['account_name'] . '</td>';
            $html .= '<td>' . number_format($entry['amount'], 2) . '</td>';
            $html .= '<td></td>';  // خلية فارغة للجانب الدائن
            $html .= '<td></td>';  // خلية فارغة للجانب الدائن
            $html .= '</tr>';
            $total_debit += $entry['amount'];
        } else {
            $html .= '<tr>';
            $html .= '<td></td>';  // خلية فارغة للجانب المدين
            $html .= '<td></td>';  // خلية فارغة للجانب المدين
            $html .= '<td>' . $entry['account_name'] . '</td>';
            $html .= '<td>' . number_format($entry['amount'], 2) . '</td>';
            $html .= '</tr>';
            $total_credit += $entry['amount'];
        }
    }

    $html .= '<tr>';
    $html .= '<th>إجمالي المدين</th>';
    $html .= '<th>' . number_format($total_debit, 2) . '</th>';
    $html .= '<th>إجمالي الدائن</th>';
    $html .= '<th>' . number_format($total_credit, 2) . '</th>';
    $html .= '</tr>';
    $html .= '</table>';

    return $html;
}


public function reverse_multiple() {
    $json = array();
    if (!$this->user->hasPermission('modify', 'accounts/journal')) {
        $json['error'] = $this->language->get('error_permission');
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
        return;
    }
    $this->load->model('accounts/journal');

    $journal_ids = isset($this->request->post['journal_ids']) ? $this->request->post['journal_ids'] : [];

    foreach ($journal_ids as $journal_id) {
        // قم بإلغاء القيد
        $result = $this->model_accounts_journal->addReverseJournal($journal_id);
        if (!$result) {
            $json['error'] = 'Failed to cancel journal ID: ' . $journal_id;
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }
    }

    $json['success'] = 'Selected journals have been successfully cancelled.';
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
}

public function cancel_multiple() {
    $json = array();
    if (!$this->user->hasPermission('modify', 'accounts/journal')) {
        $json['error'] = $this->language->get('error_permission');
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
        return;
    }
    $this->load->model('accounts/journal');

    $journal_ids = isset($this->request->post['journal_ids']) ? $this->request->post['journal_ids'] : [];

    foreach ($journal_ids as $journal_id) {
        // قم بإلغاء القيد
        $result = $this->model_accounts_journal->cancelJournal($journal_id);
        if (!$result) {
            $json['error'] = 'Failed to cancel journal ID: ' . $journal_id;
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }
    }

    $json['success'] = 'Selected journals have been successfully cancelled.';
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
}

 public function saveAdd() {
    $this->load->language('accounts/journal');
    $this->document->setTitle($this->language->get('heading_title'));
    $this->load->model('accounts/journal');

    $json = array();

    if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validateForm()) {
        $data = $this->request->post;
        $data['added_by'] = $this->user->getUserName();
        
        // Handle attachments from the form
        $data['attachments'] = array();
        if(!empty($this->request->files['attachments'])){
        $data['attachments'] = $this->request->files['attachments'];
        }else{
        $data['attachments'] = array();
        }

        $journal_id = $this->model_accounts_journal->addJournal($data);

        if ($journal_id) {
            $json['success'] = $this->language->get('text_success');
            $json['redirect'] = html_entity_decode($this->url->link('accounts/journal/edit', 'user_token=' . $this->session->data['user_token'] . '&journal_id=' . $journal_id, true));
        } else {
            $json['error'] = $this->language->get('error_save');
        }
    } else {
        $json['error'] = $this->error;
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
}   
    
    
    
    
        
    public function edit() {
        $this->load->language('accounts/journal');
        $this->document->setTitle($this->language->get('heading_title'));
        
        $this->load->model('accounts/journal');
        

        $this->getForm();
    }
    public function saveEdit() {
        $this->load->language('accounts/journal');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('accounts/journal');
    
        $json = array();
    
        if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validateForm()) {
            $data = $this->request->post;
            $data['last_edit_by'] = $this->user->getUserName();
            $data['attachments'] = array();

            // Similar to add, handle file uploads
            if(!empty($this->request->files['attachments'])){
            $data['attachments'] = $this->request->files['attachments'];
            }else{
            $data['attachments'] = array();
            }
            
            $this->model_accounts_journal->editJournal($this->request->get['journal_id'], $data);
    
            $json['success'] = $this->language->get('text_updated');
            $json['redirect'] = html_entity_decode($this->url->link('accounts/journal', 'user_token=' . $this->session->data['user_token'], true));
        } else {
            if($this->error['description']){
               $json['error'] = $this->language->get('error_description_required');
            }else if($this->error['thedate']){
               $json['error'] = $this->language->get('error_date_required');
            }else if($this->error['entries']){
               $json['error'] = $this->language->get('error_entries_required');
            }else if($this->error['balance']){
               $json['error'] = $this->language->get('error_unbalanced');
            }
        } 
                


    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
    


    }
    


public function deleteAttachment() {
    $json = array();
    $this->load->model('accounts/journal');
    
    $this->load->language('accounts/journal');
    if (!$this->user->hasPermission('modify', 'accounts/journal')) {
        $json['error'] = $this->language->get('error_permission');
    } else {
        $attachmentId = $this->request->post['attachmentId'];
        if ($this->model_accounts_journal->deleteAttachmentById($attachmentId)) {
            $json['success'] = $this->language->get('text_delete');
        } else {
            $json['error'] = $this->language->get('error_delete');
        }
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
}

  
    
protected function getList() {
    $data = array();

    // Load models and language
    $this->load->model('accounts/journal');
    $this->load->language('accounts/journal');

    // Collect filters
    $filter_date_start = isset($this->request->get['filter_date_start']) ? $this->request->get['filter_date_start'] : null;
    $filter_date_end = isset($this->request->get['filter_date_end']) ? $this->request->get['filter_date_end'] : null;
    $filter_journal_id = isset($this->request->get['filter_journal_id']) ? $this->request->get['filter_journal_id'] : null;
    $filter_description = isset($this->request->get['filter_description']) ? $this->request->get['filter_description'] : null;
    $include_cancelled = $this->request->get['include_cancelled'] ?? 0;
    $limit = isset($this->request->get['limit']) ? (int)$this->request->get['limit'] : 10;
    $page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
    
    $filter_data = array(
        'filter_date_start' => $filter_date_start,
        'filter_date_end' => $filter_date_end,
        'filter_journal_id' => $filter_journal_id,
        'filter_description' => $filter_description,
        'include_cancelled' => $include_cancelled,
        'start' => ($page - 1) * $limit,
        'limit' => $limit        
    );

    if (isset($this->request->get['show_all']) && $this->request->get['show_all']) {
        unset($filter_data['start'], $filter_data['limit']);
    }


    $total_journals = $this->model_accounts_journal->getTotalJournals($filter_data); // Model needs a method to count journals
    $total_pages = ceil($total_journals / $limit);
    $data['current_page'] = $page;
    $data['pages'] = $total_pages;
        
    // Fetch the journals
    $results = $this->model_accounts_journal->getJournals($filter_data);

    foreach ($results as $result) {
        $data['journals'][] = array(
            'journal_id'   => $result['journal_id'],
            'thedate'      => date($this->language->get('date_format_short'), strtotime($result['thedate'])),
            'description'  => $result['description'],
            'refnum'  => $result['refnum'],
            'added_by'  => $result['added_by'],
            'last_edit_by'  => $result['last_edit_by'],
            'audited'  => $result['audited'],  
            'audit_date'  => $result['audit_date'],  
            'is_cancelled'  => $result['is_cancelled'],
            'cancelled_by'  => $result['cancelled_by'],
            'cancelled_date'  => $result['cancelled_date'],
            'audit_by'  => $result['audit_by'],
            'total_debit'  => $this->currency->format($result['total_debit'], $this->config->get('config_currency'), 1),
            'total_credit'  => $this->currency->format($result['total_credit'], $this->config->get('config_currency'), 1),
            'is_balanced'  => $result['is_balanced'] ? $this->language->get('text_yes') : $this->language->get('text_no'),
            'edit'  => html_entity_decode($this->url->link('accounts/journal/edit', 'user_token=' . $this->session->data['user_token']. '&journal_id=' . $result['journal_id'], true)),
            'print'  => html_entity_decode($this->url->link('accounts/journal/print', 'user_token=' . $this->session->data['user_token']. '&journal_id=' . $result['journal_id'], true))
        );
    }
	$data['add'] = html_entity_decode($this->url->link('accounts/journal/add', 'user_token=' . $this->session->data['user_token'], true));
	$data['delete'] = html_entity_decode($this->url->link('accounts/journal/delete', 'user_token=' . $this->session->data['user_token'], true));
    $data['get_cancel_multiple'] = html_entity_decode($this->url->link('accounts/journal/cancel_multiple', 'user_token=' . $this->session->data['user_token'], true));
    $data['get_print_multiple'] = html_entity_decode($this->url->link('accounts/journal/print_multiple', 'user_token=' . $this->session->data['user_token'], true));

    
    $data['cancelled'] = html_entity_decode($this->url->link('accounts/journal', 'user_token=' . $this->session->data['user_token'], true));

$pagination = new Pagination();
$pagination->total = $total_journals; // Total number of items (journals)
$pagination->page = $page; // Current page index
$pagination->limit = 1; // Items per page
$pagination->num_links = 5; // Number of links to show
$pagination->url = 'javascript:void(0);'; // Dummy URL since we're handling pages in JS
$pagination->onclick = "filterJournals({page}); return false;"; // JavaScript function

$data['pagination'] = $pagination->render(); // Generate and store the output


    // Additional template data
    $data['heading_title'] = $this->language->get('heading_title');
    $data['text_no_results'] = $this->language->get('text_no_results');
  	$data['header'] = $this->load->controller('common/header');
  	$data['user_token'] =  $this->session->data['user_token'];
	$data['column_left'] = $this->load->controller('common/column_left');
	$data['footer'] = $this->load->controller('common/footer');
    // View template path
    $this->response->setOutput($this->load->view('accounts/journal_list', $data));
}

public function getJournals() {
    $this->load->language('accounts/journal');
    $this->load->model('accounts/journal');

    $filter_date_start = isset($this->request->get['filter_date_start']) ? $this->request->get['filter_date_start'] : null;
    $filter_date_end = isset($this->request->get['filter_date_end']) ? $this->request->get['filter_date_end'] : null;
    $filter_journal_id = isset($this->request->get['filter_journal_id']) ? $this->request->get['filter_journal_id'] : null;
    $filter_description = isset($this->request->get['filter_description']) ? $this->request->get['filter_description'] : null;
    $include_cancelled = $this->request->get['include_cancelled'] ?? 0;
    $limit = isset($this->request->get['limit']) ? (int)$this->request->get['limit'] : 50;
    $page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
    


    $filter_data = array(
        'filter_date_start' => $filter_date_start,
        'filter_date_end' => $filter_date_end,
        'filter_journal_id' => $filter_journal_id,
        'filter_description' => $filter_description,
        'include_cancelled' => $include_cancelled,
        'start' => ($page - 1) * $limit,
        'limit' => $limit       
    );
    
    if (isset($this->request->get['show_all']) && $this->request->get['show_all']) {
        unset($filter_data['start'], $filter_data['limit']);
    }



    $results = $this->model_accounts_journal->getJournals($filter_data);
    $total_journals = $this->model_accounts_journal->getTotalJournals($filter_data); // Model needs a method to count journals
    $total_pages = ceil($total_journals / $limit);
    
    $data['journals'] = array();
    foreach ($results as $result) {
        $data['journals'][] = array(
            'journal_id'   => $result['journal_id'],
            'thedate'      => date($this->language->get('date_format_short'), strtotime($result['thedate'])),
            'description'  => $result['description'],
            'refnum'  => $result['refnum'],
            'added_by'  => $result['added_by'],
            'last_edit_by'  => $result['last_edit_by'],
            'audited'  => $result['audited'],
            'is_cancelled'  => $result['is_cancelled'],
            'cancelled_by'  => $result['cancelled_by'], 
            'cancelled_date'  => $result['cancelled_date'], 
            'audit_date'  => $result['audit_date'],  
            'audit_by'  => $result['audit_by'],            
            'total_debit'  => $this->currency->format($result['total_debit'],  $this->config->get('config_currency'), 1),
            'total_credit' => $this->currency->format($result['total_credit'], $this->config->get('config_currency'), 1),
            'is_balanced'  => $result['is_balanced'] ? $this->language->get('text_yes') : $this->language->get('text_no'),
            'edit'  => html_entity_decode($this->url->link('accounts/journal/edit', 'user_token=' . $this->session->data['user_token'] . '&journal_id=' . $result['journal_id'], true)),
            'print'  => html_entity_decode($this->url->link('accounts/journal/print', 'user_token=' . $this->session->data['user_token']. '&journal_id=' . $result['journal_id'], true))
        );
    }

    $html = $this->load->view('accounts/journal_list_partial', $data);


    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode(array(
        'html' => $html,
        'total_pages' => $total_pages,
        'current_page' => $page,
    )));
}
public function printJournal() {
    $this->load->language('accounts/journal');
    $journal_id = isset($this->request->get['journal_id']) ? $this->request->get['journal_id'] : 0;
		$data['lang'] = $this->language->get('code');
		$data['direction'] = $this->language->get('direction');
    $this->load->model('accounts/journal');
    $journal_info = $this->model_accounts_journal->getJournal($journal_id);

    if ($journal_info) {
        // استرجاع تفاصيل القيد
        $data['whoprint'] = $this->user->getUserName();
        $data['thedate'] = date($this->language->get('date_format_short'), strtotime($journal_info['thedate']));
        $data['printdate'] = date('Y-m-d h:i');


        $data['journal_id'] = $journal_info['journal_id'];
        $data['description'] = $journal_info['description'];
        $data['refnum'] = $journal_info['refnum'];
        $data['is_cancelled'] = $journal_info['is_cancelled'];
        $data['cancelled_by'] = $journal_info['cancelled_by'];
        $data['cancelled_date'] = $journal_info['cancelled_date'];
        $data['last_edit_by'] = $journal_info['last_edit_by'];
        $data['audited'] = $journal_info['audited'];
        $data['audit_by'] = $journal_info['audit_by'];
        $data['audit_date'] = $journal_info['audit_date'];
        $data['thedate'] = date($this->language->get('date_format_short'), strtotime($journal_info['thedate']));
        $data['total_debit'] = $this->currency->format($journal_info['total_debit'], $this->config->get('config_currency'));
        $data['total_credit'] = $this->currency->format($journal_info['total_credit'], $this->config->get('config_currency'));
        $data['is_balanced'] = $journal_info['is_balanced'];
        $this->response->setOutput($this->load->view('accounts/journal_print_partial', $data));
        
    } else {
        return new Action('error/not_found');
    }
}

private function generatePDF($data) {
    require_once(DIR_SYSTEM . 'library/tcpdf/tcpdf.php');

    $pdf = new TCPDF('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Your Name');
    $pdf->SetTitle('Journal Report');
    $pdf->SetSubject('Generated PDF for Journal Entry');

    // set default header data
    $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, 'Journal Entry ' . $data['journal_id'], "Date: " . $data['thedate']);

    // set header and footer fonts
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

    // set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

    // set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

    // set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    // add a page
    $pdf->AddPage();

    // print a line of text
    $text = <<<EOD
Journal ID: {$data['journal_id']}
Date: {$data['thedate']}
Description: {$data['description']}
Total Debit: {$data['total_debit']}
Total Credit: {$data['total_credit']}
EOD;

    $pdf->writeHTMLCell(0, 0, '', '', $text, 0, 1, 0, true, '', true);

    // Close and output PDF document
    $pdf->Output('journal_' . $data['journal_id'] . '.pdf', 'I');
}

public function getJournalDetails() {
    $this->load->language('accounts/journal');
    $journal_id = isset($this->request->get['journal_id']) ? $this->request->get['journal_id'] : 0;

    // Check user permissions
    if (!$this->user->hasPermission('modify', 'accounts/journal')) {
        $this->session->data['error'] = $this->language->get('error_permission');
        $this->response->redirect(html_entity_decode($this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], true)));
    }

    // Load the model and get the journal details
    $this->load->model('accounts/journal');

    $data['entries'] = $this->model_accounts_journal->getJournalEntries($journal_id);

    // Render the Twig template with the provided data
    $html = $this->load->view('accounts/journal_print_partial.twig', $data);

    // Set the response headers and output the JSON response
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode(array('html' => $html)));
}


protected function getForm() {
    $data = array();
    
    $this->load->language('accounts/journal');
    
    $data['heading_title'] = $this->language->get('heading_title');
    
    $data['text_form'] = !isset($this->request->get['journal_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');
    		$data['lang'] = $this->language->get('code');
		$data['direction'] = $this->language->get('direction');
    $data['entry_account_code'] = $this->language->get('entry_account_code');
    $data['entry_is_debit'] = $this->language->get('entry_is_debit');
    $data['entry_amount'] = $this->language->get('entry_amount');
    $data['entry_description'] = $this->language->get('entry_description');
    $data['entry_attachment'] = $this->language->get('entry_attachment');
    
    $data['button_save'] = $this->language->get('button_save');
    $data['button_cancel'] = $this->language->get('button_cancel');

    $data['user_token'] = $this->session->data['user_token'];

    $journal_id = isset($this->request->get['journal_id']) ? $this->request->get['journal_id'] : 0;
    $data['journal_id'] = $journal_id;


        $njournal_info = $this->model_accounts_journal->getJournal($journal_id+1);
if(!empty($njournal_info)){
    $data['nextj'] = html_entity_decode($this->url->link('accounts/journal/edit', 'user_token=' . $this->session->data['user_token'] . '&journal_id=' . $journal_id+1, true));
}
        $ljournal_info = $this->model_accounts_journal->getJournal($journal_id-1);
if(!empty($ljournal_info)){
    $data['lastj'] = html_entity_decode($this->url->link('accounts/journal/edit', 'user_token=' . $this->session->data['user_token'] . '&journal_id=' . $journal_id-1, true));

}
    if ($journal_id && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
        $journal_info = $this->model_accounts_journal->getJournal($journal_id);
    }

    if (!empty($this->request->post['thedate'])) {
        $data['thedate'] = $this->request->post['thedate'];
    } elseif (!empty($journal_info)) {
        $data['thedate'] = $journal_info['thedate'];
    } else {
        $data['thedate'] = date('Y-m-d');
    }

    if (!empty($this->request->post['refnum'])) {
        $data['refnum'] = $this->request->post['refnum'];
    } elseif (!empty($journal_info)) {
        $data['refnum'] = $journal_info['refnum'];
    } else {
        $data['refnum'] = '';
    }


    if (!empty($this->request->post['description'])) {
        $data['description'] = $this->request->post['description'];
    } elseif (!empty($journal_info)) {
        $data['description'] = $journal_info['description'];
    } else {
        $data['description'] = '';
    }


    // Ensuring JSON data for entries is prepared
    if (isset($this->request->post['entries'])) {
        $entries = $this->request->post['entries'];
    } elseif ($journal_id) {
        $journalEntries = $this->model_accounts_journal->getJournalEntries($journal_id);
        $data['entries_json'] = json_encode($journalEntries);

    } else {
        $entries = ['debit' => [], 'credit' => []];
        $data['entries_json'] = json_encode($entries);
    }
 

    if (!empty($this->request->files['attachments'])) {
        foreach ($this->request->files['attachments']['name'] as $key => $value) {
            if ($this->request->files['attachments']['error'][$key] == UPLOAD_ERR_OK) {
                $file_name = basename($this->request->files['attachments']['name'][$key]);
                $file_temp = $this->request->files['attachments']['tmp_name'][$key];
                $file_path = 'catalog/attachments/' . $file_name;
                move_uploaded_file($file_temp, DIR_IMAGE . $file_path); // Make sure DIR_IMAGE points to your image directory
                $attachments[] = array(
                    'file_name' => $file_name,
                    'file_path' => $file_path
                );
            }
        }
        
        //$data['attachments_json'] = json_encode($attachments);
        //$data['attachments'][] = $attachments;   
        
    } elseif ($journal_id) {
        $journal = $this->model_accounts_journal->getJournal($journal_id);
        $data['attachments_json'] = json_encode($journal['attachments']);
        $data['attachments'][] = $journal['attachments'];
    } else {
       $data['attachments'][] = [];
       $data['attachments_json'] = json_encode([]);
    }

    $data['upload_url'] = html_entity_decode($this->url->link('accounts/journal/uploadAttachment', 'user_token=' . $this->session->data['user_token'], true));
    $data['delete_attachment_url'] = html_entity_decode($this->url->link('accounts/journal/deleteAttachment', 'user_token=' . $this->session->data['user_token'], true));
    $data['get_attachments_url'] = html_entity_decode($this->url->link('accounts/journal/getAttachments', 'user_token=' . $this->session->data['user_token'] . '&journal_id=' . $journal_id, true));
    $data['get_cancel_multiple'] = html_entity_decode($this->url->link('accounts/journal/cancel_multiple', 'user_token=' . $this->session->data['user_token'], true));
    $data['get_print_multiple'] = html_entity_decode($this->url->link('accounts/journal/print_multiple', 'user_token=' . $this->session->data['user_token'], true));
    
  
    
    
    
    $data['action'] = !isset($this->request->get['journal_id']) ? html_entity_decode($this->url->link('accounts/journal/saveAdd', 'user_token=' . $this->session->data['user_token'], true)) : html_entity_decode($this->url->link('accounts/journal/saveEdit', 'user_token=' . $this->session->data['user_token'] . '&journal_id=' . $this->request->get['journal_id'], true));
    $data['cancel'] = html_entity_decode($this->url->link('accounts/journal', 'user_token=' . $this->session->data['user_token'], true));
   
    $this->load->model('accounts/chartaccount'); // تأكد من تحميل النموذج المناسب
    $data['accounts'] = $this->model_accounts_chartaccount->getAccountsToEntry();
	
    $data['header'] = $this->load->controller('common/header');
    $data['column_left'] = $this->load->controller('common/column_left');
    $data['footer'] = $this->load->controller('common/footer');

    $this->response->setOutput($this->load->view('accounts/journal_form', $data));
}
public function getAttachments() {
    $json = array();

    $this->load->language('accounts/journal');
    $journal_id = isset($this->request->get['journal_id']) ? $this->request->get['journal_id'] : 0;

    if (!$this->user->hasPermission('modify', 'accounts/journal')) {
        $json['error'] = $this->language->get('error_permission');
    } else {
        $this->load->model('accounts/journal');
        $attachments = $this->model_accounts_journal->getAttachments($journal_id);

        $json['success'] = true;
        if($attachments){
        $json['attachments'] = array_map(function ($attachment) {
            return [
                'id' => $attachment['attachment_id'],  // Ensure your model provides this
                'name' => $attachment['file_name'],
                'url' => $attachment['file_path']  // Adjust path as needed
            ];
        }, $attachments);
        }
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
}

public function checkBalance() {
    $this->load->language('accounts/journal');
    $json = array();

    if ($this->request->server['REQUEST_METHOD'] == 'POST') {
        $this->load->model('accounts/journal');

        if (isset($this->request->post['entries'])) {
            $is_balanced = $this->model_accounts_journal->isBalancedJournal($this->request->post['entries']);
            if ($is_balanced) {
                $json['success'] = $this->language->get('text_balanced');
            } else {
                $json['error'] = $this->language->get('error_unbalanced');
            }
        } else {
            $json['error'] = $this->language->get('error_no_data');
        }
    } else {
        $json['error'] = $this->language->get('error_method');
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
}




protected function validateForm() {
     $this->error = [];
    $this->load->language('accounts/journal');
    
    if (!$this->user->hasPermission('modify', 'accounts/journal')) {
        $this->error['warning'] = $this->language->get('error_permission');
        return false;
    }
    
    if (empty($this->request->post['thedate'])) {
        $this->error['thedate'] = $this->language->get('error_date_required');
        return false;

    }

    if (empty($this->request->post['description'])) {
        $this->error['description'] =  $this->language->get('error_description_required');
        return false;

    }

    // Validate debit and credit entries
    if (empty($this->request->post['entries']['debit']) || empty($this->request->post['entries']['credit'])) {
        $this->error['entries'] = $this->language->get('error_entries_required');
        return false;

    }

    // Check if totals match
    $total_debit = array_sum(array_column($this->request->post['entries']['debit'], 'amount'));
    $total_credit = array_sum(array_column($this->request->post['entries']['credit'], 'amount'));
    if ($total_debit !== $total_credit) {
        $this->error['balance'] = $this->language->get('error_unbalanced');
        return false;

    }
    

    return true;
}





    
}
