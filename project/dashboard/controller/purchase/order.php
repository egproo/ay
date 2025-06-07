<?php
/**
 * أوامر الشراء المتقدمة
 * مستوى عالمي مثل SAP وOracle وOdoo وMicrosoft Dynamics
 *
 * الميزات المتقدمة:
 * - التكامل المحاسبي التلقائي
 * - نظام الموافقات الذكي
 * - تتبع متقدم للحالة
 * - ربط بالموازنات
 * - إشعارات ذكية
 * - طباعة احترافية
 * - تحليلات متقدمة
 */
class ControllerPurchaseOrder extends Controller {
    private $error = array();

    public function __construct($registry) {
        parent::__construct($registry);
        $this->load->model('purchase/order');
        $this->load->model('purchase/quotation');
        $this->load->model('purchase/requisition');
        $this->load->model('purchase/accounting_integration_advanced');
        $this->load->model('purchase/smart_approval_system');
        $this->load->model('accounts/audit_trail');
        $this->load->language('purchase/order');
    }

    public function index() {
        // التحقق من صلاحية العرض
        if (!$this->user->hasPermission('access', 'purchase/order')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->document->setTitle($this->language->get('heading_title'));

        // معالجة بيانات الفلتر
        $filter_data = [
            'filter_po_number'    => $this->request->get['filter_po_number'] ?? '',
            'filter_supplier_id'  => isset($this->request->get['filter_supplier_id']) ? (int)$this->request->get['filter_supplier_id'] : 0,
            'filter_quotation_id' => isset($this->request->get['filter_quotation_id']) ? (int)$this->request->get['filter_quotation_id'] : 0,
            'filter_status'       => $this->request->get['filter_status'] ?? '',
            'filter_date_start'   => $this->request->get['filter_date_start'] ?? '',
            'filter_date_end'     => $this->request->get['filter_date_end'] ?? '',
            'sort'                => $this->request->get['sort'] ?? 'po.order_date',
            'order'               => $this->request->get['order'] ?? 'DESC',
            'page'                => isset($this->request->get['page']) ? max(1, (int)$this->request->get['page']) : 1,
            'limit'               => isset($this->request->get['limit']) ? (int)$this->request->get['limit'] : 20
        ];

        $data = [];

        // الحصول على إحصائيات لوحة المعلومات
        $data['stats'] = $this->model_purchase_order->getOrderStats($filter_data);

        // الحصول على قائمة أوامر الشراء والعدد الإجمالي
        $orders = $this->model_purchase_order->getOrders($filter_data);
        $total = $this->model_purchase_order->getTotalOrders($filter_data);

        // تحضير بيانات أوامر الشراء للعرض
        $data['orders'] = [];
        foreach ($orders as $order) {
            $currency_code = $order['currency_code'] ?? $this->config->get('config_currency');

            $data['orders'][] = [
                'po_id'              => $order['po_id'],
                'po_number'          => $order['po_number'],
                'quotation_id'       => $order['quotation_id'],
                'quotation_number'   => $order['quotation_number'] ?? '',
                'supplier_id'        => $order['supplier_id'],
                'supplier_name'      => $order['supplier_name'],
                'currency_code'      => $currency_code,
                'total_amount'       => $order['total_amount'],
                'total_formatted'    => $this->currency->format($order['total_amount'], $currency_code),
                'status'             => $order['status'],
                'status_text'        => $this->model_purchase_order->getStatusText($order['status']),
                'status_class'       => $this->model_purchase_order->getStatusClass($order['status']),
                'order_date'         => date($this->language->get('date_format_short'), strtotime($order['order_date'])),
                'expected_delivery_date' => $order['expected_delivery_date'] ? date($this->language->get('date_format_short'), strtotime($order['expected_delivery_date'])) : '',
                'created_at'         => date($this->language->get('datetime_format'), strtotime($order['created_at'])),

                // الصلاحيات
                'can_view'           => $this->user->hasKey('purchase_order_view'),
                'can_edit'           => $this->user->hasKey('purchase_order_edit') && in_array($order['status'], ['draft', 'pending']),
                'can_delete'         => $this->user->hasKey('purchase_order_delete') && in_array($order['status'], ['draft', 'pending', 'rejected']),
                'can_approve'        => $this->user->hasKey('purchase_order_approve') && $order['status'] == 'pending',
                'can_reject'         => $this->user->hasKey('purchase_order_reject') && $order['status'] == 'pending',
                'can_print'          => $this->user->hasKey('purchase_order_print'),
                'can_create_receipt' => $this->user->hasKey('purchase_order_receipt') && in_array($order['status'], ['approved', 'partially_received']),
                'can_match'          => $this->user->hasKey('purchase_order_match') && in_array($order['status'], ['approved', 'partially_received', 'fully_received']),
                'has_documents'      => (bool)($order['document_count'] ?? 0)
            ];
        }

        // إعداد الترقيم
        $pagination = new Pagination();
        $pagination->total = $total;
        $pagination->page = $filter_data['page'];
        $pagination->limit = $filter_data['limit'];
        $pagination->url = $this->url->link('purchase/order', 'user_token=' . $this->session->data['user_token'] . '&page={page}', true);
        $data['pagination'] = $pagination->render();

        // تنسيق نص الترقيم
        $data['results'] = sprintf(
            $this->language->get('text_pagination'),
            ($total) ? (($filter_data['page'] - 1) * $filter_data['limit']) + 1 : 0,
            ((($filter_data['page'] - 1) * $filter_data['limit']) > ($total - $filter_data['limit'])) ? $total : ((($filter_data['page'] - 1) * $filter_data['limit']) + $filter_data['limit']),
            $total,
            ceil($total / $filter_data['limit'])
        );

        // بيانات إضافية للقوائم المنسدلة للفلاتر
        $data['suppliers'] = $this->model_purchase_order->getSuppliers();
        $data['status_options'] = [
            ['value' => 'draft',               'text' => $this->language->get('text_status_draft')],
            ['value' => 'pending',             'text' => $this->language->get('text_status_pending')],
            ['value' => 'approved',            'text' => $this->language->get('text_status_approved')],
            ['value' => 'rejected',            'text' => $this->language->get('text_status_rejected')],
            ['value' => 'sent_to_vendor',      'text' => $this->language->get('text_status_sent_to_vendor')],
            ['value' => 'confirmed_by_vendor', 'text' => $this->language->get('text_status_confirmed_by_vendor')],
            ['value' => 'partially_received',  'text' => $this->language->get('text_status_partially_received')],
            ['value' => 'fully_received',      'text' => $this->language->get('text_status_fully_received')],
            ['value' => 'completed',           'text' => $this->language->get('text_status_completed')],
            ['value' => 'cancelled',           'text' => $this->language->get('text_status_cancelled')]
        ];

        // صلاحيات المستخدم للعرض
        $data['can_add'] = $this->user->hasPermission('modify', 'purchase/order');
        $data['can_edit'] = $this->user->hasPermission('modify', 'purchase/order');
        $data['can_approve'] = $this->user->hasPermission('modify', 'purchase/order');
        $data['can_reject'] = $this->user->hasPermission('modify', 'purchase/order');
        $data['can_delete'] = $this->user->hasPermission('modify', 'purchase/order');
        $data['can_export'] = $this->user->hasPermission('access', 'purchase/order');

        // بيانات اللغة
        $data['text_filter'] = $this->language->get('text_filter');
        $data['text_list'] = $this->language->get('text_list');
        $data['text_no_results'] = $this->language->get('text_no_results');
        $data['text_confirm_approve'] = $this->language->get('text_confirm_approve');
        $data['text_confirm_reject'] = $this->language->get('text_confirm_reject');
        $data['text_confirm_delete'] = $this->language->get('text_confirm_delete');
        $data['text_bulk_action'] = $this->language->get('text_bulk_action');
        $data['text_approve_selected'] = $this->language->get('text_approve_selected');
        $data['text_reject_selected'] = $this->language->get('text_reject_selected');
        $data['text_delete_selected'] = $this->language->get('text_delete_selected');
        $data['text_enter_rejection_reason'] = $this->language->get('text_enter_rejection_reason');
        $data['text_export_excel'] = $this->language->get('text_export_excel');
        $data['text_export_pdf'] = $this->language->get('text_export_pdf');
        $data['text_select'] = $this->language->get('text_select');
        $data['text_all_suppliers'] = $this->language->get('text_all_suppliers');
        $data['text_all_statuses'] = $this->language->get('text_all_statuses');
        $data['text_date_start'] = $this->language->get('text_date_start');
        $data['text_date_end'] = $this->language->get('text_date_end');
        $data['text_quotation'] = $this->language->get('text_quotation');
        $data['text_total_orders'] = $this->language->get('text_total_orders');
        $data['text_pending_orders'] = $this->language->get('text_pending_orders');
        $data['text_approved_orders'] = $this->language->get('text_approved_orders');
        $data['text_received_orders'] = $this->language->get('text_received_orders');
        $data['text_refresh'] = $this->language->get('text_refresh');

        $data['column_po_number'] = $this->language->get('column_po_number');
        $data['column_quotation_number'] = $this->language->get('column_quotation_number');
        $data['column_supplier'] = $this->language->get('column_supplier');
        $data['column_total'] = $this->language->get('column_total');
        $data['column_status'] = $this->language->get('column_status');
        $data['column_order_date'] = $this->language->get('column_order_date');
        $data['column_expected_delivery'] = $this->language->get('column_expected_delivery');
        $data['column_action'] = $this->language->get('column_action');

        $data['button_filter'] = $this->language->get('button_filter');
        $data['button_clear'] = $this->language->get('button_clear');
        $data['button_add_po'] = $this->language->get('button_add_po');
        $data['button_apply'] = $this->language->get('button_apply');
        $data['button_export'] = $this->language->get('button_export');

        // معالجة الإشعارات
        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        if (isset($this->session->data['error_warning'])) {
            $data['error_warning'] = $this->session->data['error_warning'];
            unset($this->session->data['error_warning']);
        } else {
            $data['error_warning'] = '';
        }

        // خبز الفتات
        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('purchase/order', 'user_token=' . $this->session->data['user_token'], true)
        ];

        // البيانات المشتركة
        $data['user_token'] = $this->session->data['user_token'];
        $data['heading_title'] = $this->language->get('heading_title');

        // تحميل القالب
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('purchase/purchase_order_list', $data));
    }

/**
 * استرجاع قائمة أوامر الشراء عبر AJAX
 */
public function ajaxList() {
    $this->load->language('purchase/order');

    $json = [];

    if (!$this->user->hasPermission('access', 'purchase/order')) {
        $json['error'] = $this->language->get('error_permission');
        $this->sendJSON($json);
        return;
    }

    // معالجة بيانات الفلتر
    $filter_data = [
        'filter_po_number'    => $this->request->get['filter_po_number'] ?? '',
        'filter_supplier_id'  => isset($this->request->get['filter_supplier_id']) ? (int)$this->request->get['filter_supplier_id'] : 0,
        'filter_quotation_id' => isset($this->request->get['filter_quotation_id']) ? (int)$this->request->get['filter_quotation_id'] : 0,
        'filter_status'       => $this->request->get['filter_status'] ?? '',
        'filter_date_start'   => $this->request->get['filter_date_start'] ?? '',
        'filter_date_end'     => $this->request->get['filter_date_end'] ?? '',
        'sort'                => $this->request->get['sort'] ?? 'po.order_date',
        'order'               => $this->request->get['order'] ?? 'DESC',
        'page'                => isset($this->request->get['page']) ? max(1, (int)$this->request->get['page']) : 1,
        'limit'               => isset($this->request->get['limit']) ? (int)$this->request->get['limit'] : 20
    ];

    // الحصول على إحصائيات لوحة المعلومات
    $json['stats'] = $this->model_purchase_order->getOrderStats($filter_data);

    // الحصول على قائمة أوامر الشراء والعدد الإجمالي
    $orders = $this->model_purchase_order->getOrders($filter_data);
    $total = $this->model_purchase_order->getTotalOrders($filter_data);

    $json['orders'] = [];
    foreach ($orders as $order) {
        $currency_code = $order['currency_code'] ?? $this->config->get('config_currency');

        $json['orders'][] = [
            'po_id'              => $order['po_id'],
            'po_number'          => $order['po_number'],
            'quotation_id'       => $order['quotation_id'],
            'quotation_number'   => $order['quotation_number'] ?? '',
            'supplier_id'        => $order['supplier_id'],
            'supplier_name'      => $order['supplier_name'],
            'currency_code'      => $currency_code,
            'total_amount'       => $order['total_amount'],
            'total_formatted'    => $this->currency->format($order['total_amount'], $currency_code),
            'status'             => $order['status'],
            'status_text'        => $this->model_purchase_order->getStatusText($order['status']),
            'status_class'       => $this->model_purchase_order->getStatusClass($order['status']),
            'order_date'         => date($this->language->get('date_format_short'), strtotime($order['order_date'])),
            'expected_delivery_date' => $order['expected_delivery_date'] ? date($this->language->get('date_format_short'), strtotime($order['expected_delivery_date'])) : '',
            'created_at'         => date($this->language->get('datetime_format'), strtotime($order['created_at'])),

            // الصلاحيات
            'can_view'           => $this->user->hasPermission('access', 'purchase/order'),
            'can_edit'           => $this->user->hasPermission('modify', 'purchase/order') && in_array($order['status'], ['draft', 'pending']),
            'can_delete'         => $this->user->hasPermission('modify', 'purchase/order') && in_array($order['status'], ['draft', 'pending', 'rejected']),
            'can_approve'        => $this->user->hasPermission('modify', 'purchase/order') && $order['status'] == 'pending',
            'can_reject'         => $this->user->hasPermission('modify', 'purchase/order') && $order['status'] == 'pending',
            'can_print'          => $this->user->hasPermission('access', 'purchase/order'),
            'can_create_receipt' => $this->user->hasPermission('modify', 'purchase/order') && in_array($order['status'], ['approved', 'partially_received']),
            'can_match'          => $this->user->hasPermission('modify', 'purchase/order') && in_array($order['status'], ['approved', 'partially_received', 'fully_received']),
            'has_documents'      => (bool)($order['document_count'] ?? 0)
        ];
    }

    // إعداد الترقيم
    $pagination = new Pagination();
    $pagination->total = $total;
    $pagination->page = $filter_data['page'];
    $pagination->limit = $filter_data['limit'];
    $pagination->url = $this->url->link('purchase/order', 'user_token=' . $this->session->data['user_token'] . '&page={page}', true);
    $json['pagination'] = $pagination->render();

    // تنسيق نص الترقيم
    $json['results'] = sprintf(
        $this->language->get('text_pagination'),
        ($total) ? (($filter_data['page'] - 1) * $filter_data['limit']) + 1 : 0,
        ((($filter_data['page'] - 1) * $filter_data['limit']) > ($total - $filter_data['limit'])) ? $total : ((($filter_data['page'] - 1) * $filter_data['limit']) + $filter_data['limit']),
        $total,
        ceil($total / $filter_data['limit'])
    );

    $this->sendJSON($json);
}


/**
 * البحث عن أوامر الشراء عبر AJAX
 */
public function ajaxSearchPO() {
    $this->load->language('purchase/order');

    $json = [];

    if (!$this->user->hasKey('purchase_order_view')) {
        $this->sendJSON(['error' => $this->language->get('error_permission')]);
        return;
    }

    $search = $this->request->get['q'] ?? '';

    $results = $this->model_purchase_order->searchPurchaseOrders($search);

    foreach ($results as $result) {
        $json[] = [
            'id' => $result['po_id'],
            'text' => $result['po_number']
        ];
    }

    $this->sendJSON($json);
}

/**
 * الحصول على قائمة المستندات مع الصلاحيات
 * @param int $po_id معرف أمر الشراء
 * @return array مصفوفة تحتوي على قائمة المستندات والصلاحيات
 */
public function getDocumentsWithPermissions($po_id) {
    $documents = $this->model_purchase_order->getDocuments($po_id);

    $result = [
        'documents' => [],
        'can_delete' => $this->user->hasKey('purchase_order_delete'),
        'can_download' => $this->user->hasKey('purchase_order_view')
    ];

    foreach ($documents as $document) {
        $fileExt = pathinfo($document['file_path'], PATHINFO_EXTENSION);
        $iconClass = $this->getFileIconClass($fileExt);
        $canPreview = in_array(strtolower($fileExt), ['pdf', 'jpg', 'jpeg', 'png', 'gif']);

        $result['documents'][] = [
            'document_id' => $document['document_id'],
            'document_name' => $document['document_name'],
            'document_type' => $document['document_type'],
            'file_path' => $document['file_path'],
            'file_extension' => $fileExt,
            'icon_class' => $iconClass,
            'uploaded_by' => $document['uploaded_by_name'],
            'upload_date' => date($this->language->get('date_format_short'), strtotime($document['upload_date'])),
            'can_preview' => $canPreview
        ];
    }

    return $result;
}

/**
 * الحصول على صنف أيقونة مناسبة لنوع الملف
 * @param string $fileExt امتداد الملف
 * @return string صنف الأيقونة
 */
private function getFileIconClass($fileExt) {
    $fileExt = strtolower($fileExt);

    switch ($fileExt) {
        case 'pdf':
            return 'fa fa-file-pdf-o text-danger';
        case 'doc':
        case 'docx':
            return 'fa fa-file-word-o text-primary';
        case 'xls':
        case 'xlsx':
            return 'fa fa-file-excel-o text-success';
        case 'ppt':
        case 'pptx':
            return 'fa fa-file-powerpoint-o text-warning';
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif':
        case 'bmp':
            return 'fa fa-file-image-o text-info';
        case 'zip':
        case 'rar':
            return 'fa fa-file-archive-o text-muted';
        case 'txt':
            return 'fa fa-file-text-o';
        default:
            return 'fa fa-file-o';
    }
}
    /**
     * عرض التفاصيل
     */
    public function view() {
        if (!$this->user->hasKey('purchase_order_view')) {
            return $this->load->view('error/permission', []);
        }

        $po_id = isset($this->request->get['po_id']) ? (int)$this->request->get['po_id'] : 0;

        if (!$po_id) {
            return $this->load->view('error/not_found', []);
        }

        // الحصول على بيانات أمر الشراء
        $order_info = $this->model_purchase_order->getOrder($po_id);

        if (!$order_info) {
            return $this->load->view('error/not_found', []);
        }

        $data = [];

        // الحصول على معلومات عرض السعر إذا كان مرتبطًا
        $quotation_info = [];
        if ($order_info['quotation_id']) {
            $this->load->model('purchase/quotation');
            $quotation_info = $this->model_purchase_quotation->getQuotation($order_info['quotation_id']);
        }

        // تنسيق بيانات أمر الشراء
        $currency_code = $order_info['currency_code'] ?? $this->config->get('config_currency');

        $data['order'] = [
            'po_id' => $order_info['po_id'],
            'po_number' => $order_info['po_number'],
            'quotation_id' => $order_info['quotation_id'],
            'quotation_number' => $quotation_info ? $quotation_info['quotation_number'] : '',
            'supplier_name' => $order_info['supplier_name'],
            'currency_code' => $currency_code,
            'subtotal' => $this->currency->format($order_info['subtotal'], $currency_code),
            'tax_amount' => $this->currency->format($order_info['tax_amount'], $currency_code),
            'discount_amount' => $this->currency->format($order_info['discount_amount'], $currency_code),
            'total_amount' => $this->currency->format($order_info['total_amount'], $currency_code),
            'tax_included' => $order_info['tax_included'] ? $this->language->get('text_yes') : $this->language->get('text_no'),
            'tax_rate' => $order_info['tax_rate'] . '%',
            'status' => $order_info['status'],
            'status_text' => $this->model_purchase_order->getStatusText($order_info['status']),
            'status_class' => $this->model_purchase_order->getStatusClass($order_info['status']),
            'order_date' => date($this->language->get('date_format_short'), strtotime($order_info['order_date'])),
            'expected_delivery_date' => $order_info['expected_delivery_date'] ? date($this->language->get('date_format_short'), strtotime($order_info['expected_delivery_date'])) : '',
            'payment_terms' => nl2br($order_info['payment_terms']),
            'delivery_terms' => nl2br($order_info['delivery_terms']),
            'notes' => nl2br($order_info['notes']),
            'created_at' => date($this->language->get('datetime_format'), strtotime($order_info['created_at'])),
            'created_by_name' => $this->model_purchase_order->getUserName($order_info['created_by'])
        ];

        // الحصول على بنود أمر الشراء
        $data['items'] = $this->model_purchase_order->getOrderItems($po_id);

        // تنسيق بيانات البنود
        foreach ($data['items'] as &$item) {
            $item['unit_price_formatted'] = $this->currency->format($item['unit_price'], $currency_code);
            $item['total_price_formatted'] = $this->currency->format($item['total_price'], $currency_code);
            $item['discount_amount_formatted'] = $this->currency->format($item['discount_amount'] ?? 0, $currency_code);
            $item['tax_amount_formatted'] = $this->currency->format($item['tax_amount'] ?? 0, $currency_code);
        }

        // الحصول على المستندات
        $data['documents'] = $this->model_purchase_order->getDocuments($po_id);

        // الحصول على التاريخ
        $data['history'] = $this->model_purchase_order->getOrderHistory($po_id);

        // الحصول على بيانات الاستلام
        $data['receipts'] = $this->model_purchase_order->getGoodsReceipts($po_id);

        // الحصول على بيانات المطابقة
        $data['matching'] = $this->model_purchase_order->getMatchingInfo($po_id);

        // الصلاحيات
        $data['can_edit'] = $this->user->hasKey('purchase_order_edit') && in_array($order_info['status'], ['draft', 'pending']);
        $data['can_delete'] = $this->user->hasKey('purchase_order_delete') && in_array($order_info['status'], ['draft', 'pending', 'rejected']);
        $data['can_approve'] = $this->user->hasKey('purchase_order_approve') && $order_info['status'] == 'pending';
        $data['can_reject'] = $this->user->hasKey('purchase_order_reject') && $order_info['status'] == 'pending';
        $data['can_print'] = $this->user->hasKey('purchase_order_print');
        $data['can_create_receipt'] = $this->user->hasKey('purchase_order_receipt') && in_array($order_info['status'], ['approved', 'partially_received']);
        $data['can_match'] = $this->user->hasKey('purchase_order_match') && in_array($order_info['status'], ['approved', 'partially_received', 'fully_received']);
        $data['can_upload'] = $this->user->hasKey('purchase_order_upload');
        $data['can_download'] = $this->user->hasKey('purchase_order_view');

        // نصوص اللغة
        $data['text_order_details'] = $this->language->get('text_order_details');
        $data['text_order_view'] = $this->language->get('text_order_view');
        $data['text_quotation'] = $this->language->get('text_quotation');
        $data['text_supplier'] = $this->language->get('text_supplier');
        $data['text_currency'] = $this->language->get('text_currency');
        $data['text_order_date'] = $this->language->get('text_order_date');
        $data['text_expected_delivery'] = $this->language->get('text_expected_delivery');
        $data['text_payment_terms'] = $this->language->get('text_payment_terms');
        $data['text_delivery_terms'] = $this->language->get('text_delivery_terms');
        $data['text_notes'] = $this->language->get('text_notes');
        $data['text_tax_included'] = $this->language->get('text_tax_included');
        $data['text_tax_rate'] = $this->language->get('text_tax_rate');
        $data['text_status'] = $this->language->get('text_status');
        $data['text_created_by'] = $this->language->get('text_created_by');
        $data['text_date_added'] = $this->language->get('text_date_added');
        $data['text_history'] = $this->language->get('text_history');
        $data['text_documents'] = $this->language->get('text_documents');
        $data['text_receipts'] = $this->language->get('text_receipts');
        $data['text_matching'] = $this->language->get('text_matching');
        $data['text_no_documents'] = $this->language->get('text_no_documents');
        $data['text_no_history'] = $this->language->get('text_no_history');
        $data['text_no_items'] = $this->language->get('text_no_items');
        $data['text_no_receipts'] = $this->language->get('text_no_receipts');
        $data['text_no_matching'] = $this->language->get('text_no_matching');

        $data['column_product'] = $this->language->get('column_product');
        $data['column_quantity'] = $this->language->get('column_quantity');
        $data['column_unit'] = $this->language->get('column_unit');
        $data['column_unit_price'] = $this->language->get('column_unit_price');
        $data['column_discount'] = $this->language->get('column_discount');
        $data['column_tax'] = $this->language->get('column_tax');
        $data['column_total'] = $this->language->get('column_total');
        $data['column_document_name'] = $this->language->get('column_document_name');
        $data['column_document_type'] = $this->language->get('column_document_type');
        $data['column_uploaded_by'] = $this->language->get('column_uploaded_by');
        $data['column_upload_date'] = $this->language->get('column_upload_date');
        $data['column_action'] = $this->language->get('column_action');
        $data['column_date'] = $this->language->get('column_date');
        $data['column_user'] = $this->language->get('column_user');
        $data['column_action_type'] = $this->language->get('column_action_type');
        $data['column_description'] = $this->language->get('column_description');
        $data['column_receipt_number'] = $this->language->get('column_receipt_number');
        $data['column_receipt_date'] = $this->language->get('column_receipt_date');
        $data['column_receipt_status'] = $this->language->get('column_receipt_status');

        $data['button_back'] = $this->language->get('button_back');
        $data['button_edit'] = $this->language->get('button_edit');
        $data['button_delete'] = $this->language->get('button_delete');
        $data['button_approve'] = $this->language->get('button_approve');
        $data['button_reject'] = $this->language->get('button_reject');
        $data['button_print'] = $this->language->get('button_print');
        $data['button_create_receipt'] = $this->language->get('button_create_receipt');
        $data['button_match'] = $this->language->get('button_match');
        $data['button_upload'] = $this->language->get('button_upload');
        $data['button_download'] = $this->language->get('button_download');

        $data['text_subtotal'] = $this->language->get('text_subtotal');
        $data['text_discount'] = $this->language->get('text_discount');
        $data['text_tax'] = $this->language->get('text_tax');
        $data['text_total'] = $this->language->get('text_total');

        $data['user_token'] = $this->session->data['user_token'];

        return $this->response->setOutput($this->load->view('purchase/purchase_order_view', $data));
    }

    /**
     * نموذج إضافة/تعديل أمر شراء
     */
    public function form() {
        if (!$this->user->hasKey('purchase_order_add') && !$this->user->hasKey('purchase_order_edit')) {
            return $this->load->view('error/permission', []);
        }
        $this->load->model('purchase/order');
        $this->load->model('purchase/quotation');
        $this->load->model('localisation/currency');

        $data = [];
        $data['error'] = '';

        $po_id = isset($this->request->get['po_id']) ? (int)$this->request->get['po_id'] : 0;
        $quotation_id = isset($this->request->get['quotation_id']) ? (int)$this->request->get['quotation_id'] : 0;

        // الوضع - إضافة أو تعديل
        $data['mode'] = $po_id ? 'edit' : 'add';

        if ($data['mode'] == 'edit') {
            $order_info = $this->model_purchase_order->getOrder($po_id);

            if (!$order_info) {
                $data['error'] = $this->language->get('error_order_not_found');
                return $this->load->view('error/not_found', $data);
            }

            // التحقق من صلاحية التعديل استنادًا إلى الحالة
            if (!in_array($order_info['status'], ['draft', 'pending'])) {
                $data['error'] = $this->language->get('error_edit_status');
                return $this->load->view('error/permission', $data);
            }
        }

        // الحصول على بيانات النموذج - إما من السجل الموجود أو القيم الافتراضية
        $form_data = [
            'po_id' => $po_id,
            'po_number' => $order_info['po_number'] ?? '',
            'quotation_id' => $order_info['quotation_id'] ?? $quotation_id,
            'requisition_id' => $order_info['requisition_id'] ?? 0,
            'supplier_id' => $order_info['supplier_id'] ?? 0,
            'currency_id' => $order_info['currency_id'] ?? $this->config->get('config_currency_id'),
            'exchange_rate' => $order_info['exchange_rate'] ?? 1,
            'order_date' => $order_info['order_date'] ?? date('Y-m-d'),
            'expected_delivery_date' => $order_info['expected_delivery_date'] ?? date('Y-m-d', strtotime('+30 days')),
            'payment_terms' => $order_info['payment_terms'] ?? '',
            'delivery_terms' => $order_info['delivery_terms'] ?? '',
            'notes' => $order_info['notes'] ?? '',
            'tax_included' => $order_info['tax_included'] ?? 1,
            'tax_rate' => $order_info['tax_rate'] ?? $this->config->get('config_tax') ?? 0,
            'subtotal' => $order_info['subtotal'] ?? 0,
            'discount_type' => $order_info['discount_type'] ?? 'fixed',
            'has_discount' => $order_info['has_discount'] ?? 0,
            'discount_value' => $order_info['discount_value'] ?? 0,
            'discount_amount' => $order_info['discount_amount'] ?? 0,
            'tax_amount' => $order_info['tax_amount'] ?? 0,
            'total_amount' => $order_info['total_amount'] ?? 0,
            'status' => $order_info['status'] ?? 'draft',
            'reference_type' => $order_info['reference_type'] ?? 'direct',
            'reference_id' => $order_info['reference_id'] ?? 0,
            'source_type' => $order_info['source_type'] ?? 'direct',
            'source_id' => $order_info['source_id'] ?? 0
        ];

        $data['form_data'] = $form_data;

        // الحصول على معلومات عرض السعر إذا كان متاحًا
        $data['quotation_info'] = [];
        if ($form_data['quotation_id']) {
            $quotation_info = $this->model_purchase_quotation->getQuotation($form_data['quotation_id']);
            if ($quotation_info) {
                $data['quotation_info'] = [
                    'quotation_id' => $quotation_info['quotation_id'],
                    'quotation_number' => $quotation_info['quotation_number'],
                    'supplier_id' => $quotation_info['supplier_id'],
                    'supplier_name' => $quotation_info['supplier_name'],
                    'currency_id' => $quotation_info['currency_id'],
                    'currency_code' => $quotation_info['currency_code'],
                    'exchange_rate' => $quotation_info['exchange_rate'],
                    'validity_date' => date($this->language->get('date_format_short'), strtotime($quotation_info['validity_date'])),
                    'payment_terms' => $quotation_info['payment_terms'],
                    'delivery_terms' => $quotation_info['delivery_terms'],
                    'subtotal' => $quotation_info['subtotal'],
                    'tax_amount' => $quotation_info['tax_amount'],
                    'discount_amount' => $quotation_info['discount_amount'],
                    'total_amount' => $quotation_info['total_amount'],
                    'tax_included' => $quotation_info['tax_included'],
                    'tax_rate' => $quotation_info['tax_rate'],
                    'status' => $quotation_info['status'],
                    'status_text' => $this->model_purchase_quotation->getStatusText($quotation_info['status']),
                    'requisition_id' => $quotation_info['requisition_id']
                ];

                // تعيين القيم الافتراضية من عرض السعر إذا كان إضافة جديدة
                if ($data['mode'] == 'add') {
                    $form_data['supplier_id'] = $quotation_info['supplier_id'];
                    $form_data['currency_id'] = $quotation_info['currency_id'];
                    $form_data['exchange_rate'] = $quotation_info['exchange_rate'];
                    $form_data['payment_terms'] = $quotation_info['payment_terms'];
                    $form_data['delivery_terms'] = $quotation_info['delivery_terms'];
                    $form_data['tax_included'] = $quotation_info['tax_included'];
                    $form_data['tax_rate'] = $quotation_info['tax_rate'];
                    $form_data['subtotal'] = $quotation_info['subtotal'];
                    $form_data['discount_amount'] = $quotation_info['discount_amount'];
                    $form_data['tax_amount'] = $quotation_info['tax_amount'];
                    $form_data['total_amount'] = $quotation_info['total_amount'];
                    $form_data['reference_type'] = 'quotation';
                    $form_data['reference_id'] = $quotation_info['quotation_id'];
                    $form_data['source_type'] = 'quotation';
                    $form_data['source_id'] = $quotation_info['quotation_id'];

                    // تعيين طلب الشراء إذا كان عرض السعر مرتبط بطلب شراء
                    if ($quotation_info['requisition_id']) {
                        $form_data['requisition_id'] = $quotation_info['requisition_id'];
                    }

                    $data['form_data'] = $form_data;
                }
            }
        }

        // الحصول على معلومات طلب الشراء إذا كان متاحًا
        $data['requisition_info'] = [];
        if ($form_data['requisition_id']) {
            $this->load->model('purchase/requisition');
            $requisition_info = $this->model_purchase_requisition->getRequisition($form_data['requisition_id']);
            if ($requisition_info) {
                $data['requisition_info'] = [
                    'requisition_id' => $requisition_info['requisition_id'],
                    'req_number' => $requisition_info['req_number'],
                    'branch_name' => $requisition_info['branch_name'],
                    'user_group_name' => $requisition_info['user_group_name'],
                    'required_date' => date($this->language->get('date_format_short'), strtotime($requisition_info['required_date'])),
                    'priority' => $requisition_info['priority'],
                    'priority_text' => $this->language->get('text_priority_' . $requisition_info['priority']),
                    'status' => $requisition_info['status'],
                    'status_text' => $this->model_purchase_quotation->getStatusText($requisition_info['status'])
                ];
            }
        }

        // الحصول على بنود أمر الشراء
        $data['items'] = [];
        if ($po_id) {
            $items = $this->model_purchase_order->getOrderItems($po_id);
            foreach ($items as $item) {
                $data['items'][] = $item;
            }
        } else if ($form_data['quotation_id']) {
            // إذا تمت الإضافة استنادًا إلى عرض سعر، جلب بنود عرض السعر
            $quote_items = $this->model_purchase_quotation->getQuotationItems($form_data['quotation_id']);
            foreach ($quote_items as $item) {
                $data['items'][] = [
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'quotation_item_id' => $item['quotation_item_id'],
                    'requisition_item_id' => $item['requisition_item_id'],
                    'unit_id' => $item['unit_id'],
                    'unit_name' => $item['unit_name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'tax_rate' => $item['tax_rate'],
                    'discount_rate' => $item['discount_rate'],
                    'discount_type' => $item['discount_type'],
                    'discount_amount' => $item['discount_amount'],
                    'tax_amount' => $item['tax_amount'],
                    'total_price' => $item['line_total'],
                    'description' => $item['description'] ?? ''
                ];
            }
        }

        // الحصول على قائمة الموردين
        $data['suppliers'] = $this->model_purchase_order->getSuppliers();

        // الحصول على قائمة العملات
        $data['currencies'] = $this->model_localisation_currency->getCurrencies();

        // خيارات القوائم المنسدلة
        $data['discount_types'] = [
            ['value' => 'fixed', 'text' => $this->language->get('text_fixed')],
            ['value' => 'percentage', 'text' => $this->language->get('text_percentage')]
        ];

        $data['tax_options'] = [
            ['value' => '1', 'text' => $this->language->get('text_tax_included')],
            ['value' => '0', 'text' => $this->language->get('text_tax_excluded')]
        ];

        // صلاحيات
        $data['can_edit_price'] = $this->user->hasKey('purchase_order_edit_price');
        $data['can_apply_discount'] = $this->user->hasKey('purchase_order_discount');
        $data['can_change_tax'] = $this->user->hasKey('purchase_order_tax');

        // نصوص اللغة
        $data['text_edit_order'] = $this->language->get('text_edit_order');
        $data['text_add_order'] = $this->language->get('text_add_order');
        $data['text_order_details'] = $this->language->get('text_order_details');
        $data['text_order_items'] = $this->language->get('text_order_items');
        $data['text_totals'] = $this->language->get('text_totals');
        $data['text_documents'] = $this->language->get('text_documents');
        $data['text_select_quotation'] = $this->language->get('text_select_quotation');
        $data['text_select_supplier'] = $this->language->get('text_select_supplier');
        $data['text_select_currency'] = $this->language->get('text_select_currency');
        $data['text_upload_documents'] = $this->language->get('text_upload_documents');
        $data['text_add_item'] = $this->language->get('text_add_item');
        $data['text_calculate_totals'] = $this->language->get('text_calculate_totals');
        $data['text_save_as_draft'] = $this->language->get('text_save_as_draft');
        $data['text_submit'] = $this->language->get('text_submit');
        $data['text_cancel'] = $this->language->get('text_cancel');
        $data['text_select_product'] = $this->language->get('text_select_product');
        $data['text_no_items'] = $this->language->get('text_no_items');
        $data['text_from_quotation'] = $this->language->get('text_from_quotation');
        $data['text_direct'] = $this->language->get('text_direct');

        $data['entry_quotation'] = $this->language->get('entry_quotation');
        $data['entry_supplier'] = $this->language->get('entry_supplier');
        $data['entry_currency'] = $this->language->get('entry_currency');
        $data['entry_exchange_rate'] = $this->language->get('entry_exchange_rate');
        $data['entry_order_date'] = $this->language->get('entry_order_date');
        $data['entry_expected_delivery'] = $this->language->get('entry_expected_delivery');
        $data['entry_payment_terms'] = $this->language->get('entry_payment_terms');
        $data['entry_delivery_terms'] = $this->language->get('entry_delivery_terms');
        $data['entry_notes'] = $this->language->get('entry_notes');
        $data['entry_tax_included'] = $this->language->get('entry_tax_included');
        $data['entry_tax_rate'] = $this->language->get('entry_tax_rate');
        $data['entry_has_discount'] = $this->language->get('entry_has_discount');
        $data['entry_discount_type'] = $this->language->get('entry_discount_type');
        $data['entry_discount_value'] = $this->language->get('entry_discount_value');

        $data['column_product'] = $this->language->get('column_product');
        $data['column_quantity'] = $this->language->get('column_quantity');
        $data['column_unit'] = $this->language->get('column_unit');
        $data['column_unit_price'] = $this->language->get('column_unit_price');
        $data['column_discount'] = $this->language->get('column_discount');
        $data['column_tax'] = $this->language->get('column_tax');
        $data['column_total'] = $this->language->get('column_total');
        $data['column_action'] = $this->language->get('column_action');

        $data['button_save'] = $this->language->get('button_save');
        $data['button_save_submit'] = $this->language->get('button_save_submit');
        $data['button_cancel'] = $this->language->get('button_cancel');

        $data['tab_general'] = $this->language->get('tab_general');
        $data['tab_items'] = $this->language->get('tab_items');
        $data['tab_documents'] = $this->language->get('tab_documents');
        $data['tab_totals'] = $this->language->get('tab_totals');

        $data['user_token'] = $this->session->data['user_token'];

        return $this->response->setOutput($this->load->view('purchase/purchase_order_form', $data));
    }

    /**
     * حفظ أمر الشراء
     */
    public function ajaxSave() {
        $this->load->language('purchase/order');

        $json = [];

        // التحقق من الصلاحيات
        if (!$this->user->hasKey('purchase_order_add') && !$this->user->hasKey('purchase_order_edit')) {
            $json['error'] = $this->language->get('error_permission');
            $this->sendJSON($json);
            return;
        }

        $po_id = isset($this->request->post['po_id']) ? (int)$this->request->post['po_id'] : 0;
        $mode = $po_id ? 'edit' : 'add';

        // التحقق من إذا كان تعديلاً لأمر شراء موجود
        if ($mode == 'edit') {
            $order_info = $this->model_purchase_order->getOrder($po_id);

            if (!$order_info) {
                $json['error'] = $this->language->get('error_order_not_found');
                $this->sendJSON($json);
                return;
            }

            // التحقق من الحالة التي تسمح بالتعديل
            if (!in_array($order_info['status'], ['draft', 'pending'])) {
                $json['error'] = $this->language->get('error_edit_status');
                $this->sendJSON($json);
                return;
            }
        }

        // التحقق الأساسي
        if (empty($this->request->post['supplier_id'])) {
            $json['error'] = $this->language->get('error_supplier_required');
            $this->sendJSON($json);
            return;
        }

        if (empty($this->request->post['order_date'])) {
            $json['error'] = $this->language->get('error_order_date_required');
            $this->sendJSON($json);
            return;
        }

        // التحقق من البنود - التحقق من وجود مصفوفة البنود وأنها منظمة بشكل صحيح
        if (!isset($this->request->post['item']) ||
            !isset($this->request->post['item']['product_id']) ||
            !is_array($this->request->post['item']['product_id'])) {
            $json['error'] = $this->language->get('error_items_required');
            $this->sendJSON($json);
            return;
        }

        // التحقق من وجود بند واحد صالح على الأقل
        $valid_items = false;
        $items_count = count($this->request->post['item']['product_id']);

        for ($i = 0; $i < $items_count; $i++) {
            if (!empty($this->request->post['item']['product_id'][$i]) &&
                isset($this->request->post['item']['quantity'][$i]) &&
                (float)$this->request->post['item']['quantity'][$i] > 0) {
                $valid_items = true;
                break;
            }
        }

        if (!$valid_items) {
            $json['error'] = $this->language->get('error_valid_items_required');
            $this->sendJSON($json);
            return;
        }

        // تحضير البيانات
        $data = [
            'po_id' => $po_id,
            'quotation_id' => isset($this->request->post['quotation_id']) ? (int)$this->request->post['quotation_id'] : 0,
            'requisition_id' => isset($this->request->post['requisition_id']) ? (int)$this->request->post['requisition_id'] : 0,
            'supplier_id' => (int)$this->request->post['supplier_id'],
            'currency_id' => (int)$this->request->post['currency_id'],
            'exchange_rate' => (float)$this->request->post['exchange_rate'],
            'order_date' => $this->request->post['order_date'],
            'expected_delivery_date' => $this->request->post['expected_delivery_date'],
            'payment_terms' => $this->request->post['payment_terms'] ?? '',
            'delivery_terms' => $this->request->post['delivery_terms'] ?? '',
            'notes' => $this->request->post['notes'] ?? '',
            'tax_included' => isset($this->request->post['tax_included']) ? (int)$this->request->post['tax_included'] : 0,
            'tax_rate' => (float)$this->request->post['tax_rate'],
            'subtotal' => (float)$this->request->post['subtotal'],
            'discount_type' => $this->request->post['discount_type'] ?? 'fixed',
            'has_discount' => isset($this->request->post['has_discount']) ? (int)$this->request->post['has_discount'] : 0,
            'discount_value' => (float)$this->request->post['discount_value'],
            'discount_amount' => (float)$this->request->post['discount_amount'],
            'tax_amount' => (float)$this->request->post['tax_amount'],
            'total_amount' => (float)$this->request->post['total_amount'],
            'status' => isset($this->request->post['submit_type']) && $this->request->post['submit_type'] == 'submit' ? 'pending' : 'draft',
            'reference_type' => $this->request->post['reference_type'] ?? 'direct',
            'reference_id' => (int)($this->request->post['reference_id'] ?? 0),
            'source_type' => $this->request->post['source_type'] ?? 'direct',
            'source_id' => (int)($this->request->post['source_id'] ?? 0),
            'items' => [],
            'user_id' => $this->user->getId()
        ];

        // معالجة البنود
        for ($i = 0; $i < $items_count; $i++) {
            // تخطي البنود الفارغة
            if (empty($this->request->post['item']['product_id'][$i])) {
                continue;
            }

            $data['items'][] = [
                'po_item_id' => isset($this->request->post['item']['po_item_id'][$i]) ? (int)$this->request->post['item']['po_item_id'][$i] : 0,
                'quotation_item_id' => isset($this->request->post['item']['quotation_item_id'][$i]) ? (int)$this->request->post['item']['quotation_item_id'][$i] : 0,
                'requisition_item_id' => isset($this->request->post['item']['requisition_item_id'][$i]) ? (int)$this->request->post['item']['requisition_item_id'][$i] : 0,
                'product_id' => (int)$this->request->post['item']['product_id'][$i],
                'unit_id' => (int)$this->request->post['item']['unit_id'][$i],
                'quantity' => (float)$this->request->post['item']['quantity'][$i],
                'unit_price' => (float)$this->request->post['item']['unit_price'][$i],
                'tax_rate' => (float)($this->request->post['item']['tax_rate'][$i] ?? 0),
                'discount_type' => $this->request->post['item']['discount_type'][$i] ?? 'fixed',
                'discount_rate' => (float)($this->request->post['item']['discount_rate'][$i] ?? 0),
                'discount_amount' => (float)($this->request->post['item']['discount_amount'][$i] ?? 0),
                'tax_amount' => (float)($this->request->post['item']['tax_amount'][$i] ?? 0),
                'total_price' => (float)($this->request->post['item']['total_price'][$i] ?? 0),
                'description' => $this->request->post['item']['description'][$i] ?? '',
                'original_unit_price' => (float)($this->request->post['item']['original_unit_price'][$i] ?? $this->request->post['item']['unit_price'][$i])
            ];
        }

        // حفظ أمر الشراء
        try {
            if ($mode == 'add') {
                $result = $this->model_purchase_order->addOrder($data);
                $json['success'] = $this->language->get('text_success_add');
            } else {
                $result = $this->model_purchase_order->editOrder($data);
                $json['success'] = $this->language->get('text_success_edit');
            }

            if ($result) {
                $json['po_id'] = $result;
                $json['redirect'] = $this->url->link('purchase/order', 'user_token=' . $this->session->data['user_token'], true);
            } else {
                $json['error'] = $this->language->get('error_saving');
            }
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->sendJSON($json);
    }

    /**
     * اعتماد أمر شراء
     */
    public function approve() {
        $this->load->language('purchase/order');

        $json = [];

        if (!$this->user->hasKey('purchase_order_approve')) {
            $json['error'] = $this->language->get('error_permission');
            $this->sendJSON($json);
            return;
        }

        $po_id = isset($this->request->post['po_id']) ? (int)$this->request->post['po_id'] : 0;

        if (!$po_id) {
            $json['error'] = $this->language->get('error_order_required');
            $this->sendJSON($json);
            return;
        }

        try {
            $result = $this->model_purchase_order->approveOrder($po_id, $this->user->getId());

            if (!$result) {
                $json['error'] = $this->language->get('error_approving');
            } else {
                $json['success'] = $this->language->get('text_approve_success');
            }
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->sendJSON($json);
    }

    /**
     * رفض أمر شراء
     */
    public function reject() {
        $this->load->language('purchase/order');

        $json = [];

        if (!$this->user->hasKey('purchase_order_reject')) {
            $json['error'] = $this->language->get('error_permission');
            $this->sendJSON($json);
            return;
        }

        $po_id = isset($this->request->post['po_id']) ? (int)$this->request->post['po_id'] : 0;
        $reason = isset($this->request->post['reason']) ? trim($this->request->post['reason']) : '';

        if (!$po_id) {
            $json['error'] = $this->language->get('error_order_required');
            $this->sendJSON($json);
            return;
        }

        if (empty($reason)) {
            $json['error'] = $this->language->get('error_rejection_reason_required');
            $this->sendJSON($json);
            return;
        }

        try {
            $result = $this->model_purchase_order->rejectOrder($po_id, $reason, $this->user->getId());

            if (!$result) {
                $json['error'] = $this->language->get('error_rejecting');
            } else {
                $json['success'] = $this->language->get('text_reject_success');
            }
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->sendJSON($json);
    }

    /**
     * حذف أمر شراء
     */
    public function delete() {
        $this->load->language('purchase/order');

        $json = [];

        if (!$this->user->hasKey('purchase_order_delete')) {
            $json['error'] = $this->language->get('error_permission');
            $this->sendJSON($json);
            return;
        }

        $po_id = isset($this->request->post['po_id']) ? (int)$this->request->post['po_id'] : 0;

        if (!$po_id) {
            $json['error'] = $this->language->get('error_order_required');
            $this->sendJSON($json);
            return;
        }

        try {
            $result = $this->model_purchase_order->deleteOrder($po_id);

            if (!$result) {
                $json['error'] = $this->language->get('error_deleting');
            } else {
                $json['success'] = $this->language->get('text_delete_success');
            }
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->sendJSON($json);
    }

    /**
     * طباعة أمر شراء
     */
    public function print() {
        if (!$this->user->hasKey('purchase_order_print')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }

        $po_id = isset($this->request->get['po_id']) ? (int)$this->request->get['po_id'] : 0;

        if (!$po_id) {
            $this->response->redirect($this->url->link('purchase/order', 'user_token=' . $this->session->data['user_token'], true));
        }

        // الحصول على بيانات أمر الشراء
        $order_info = $this->model_purchase_order->getOrder($po_id);

        if (!$order_info) {
            $this->response->redirect($this->url->link('purchase/order', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data = [];

        // الحصول على معلومات المورد
        $supplier_info = [];
        if ($order_info['supplier_id']) {
            $supplier_info = $this->model_purchase_order->getSupplier($order_info['supplier_id']);
        }

        // تنسيق بيانات أمر الشراء
        $currency_code = $order_info['currency_code'] ?? $this->config->get('config_currency');

        $data['order'] = [
            'po_id' => $order_info['po_id'],
            'po_number' => $order_info['po_number'],
            'quotation_id' => $order_info['quotation_id'],
            'quotation_number' => $order_info['quotation_number'] ?? '',
            'supplier_id' => $order_info['supplier_id'],
            'supplier_name' => $supplier_info ? $supplier_info['firstname'] . ' ' . $supplier_info['lastname'] : $order_info['supplier_name'],
            'supplier_address' => $supplier_info ? nl2br($supplier_info['address_1']) : '',
            'currency_code' => $currency_code,
            'currency_id' => $order_info['currency_id'],
            'exchange_rate' => $order_info['exchange_rate'],
            'subtotal' => $this->currency->format($order_info['subtotal'], $currency_code),
            'tax_amount' => $this->currency->format($order_info['tax_amount'], $currency_code),
            'discount_amount' => $this->currency->format($order_info['discount_amount'], $currency_code),
            'total_amount' => $this->currency->format($order_info['total_amount'], $currency_code),
            'tax_included' => $order_info['tax_included'],
            'tax_rate' => $order_info['tax_rate'],
            'status' => $order_info['status'],
            'status_text' => $this->model_purchase_order->getStatusText($order_info['status']),
            'order_date' => date($this->language->get('date_format_short'), strtotime($order_info['order_date'])),
            'expected_delivery_date' => $order_info['expected_delivery_date'] ? date($this->language->get('date_format_short'), strtotime($order_info['expected_delivery_date'])) : '',
            'payment_terms' => nl2br($order_info['payment_terms']),
            'delivery_terms' => nl2br($order_info['delivery_terms']),
            'notes' => nl2br($order_info['notes']),
            'created_at' => date($this->language->get('datetime_format'), strtotime($order_info['created_at'])),
            'created_by_name' => $this->model_purchase_order->getUserName($order_info['created_by'])
        ];

        // الحصول على بنود أمر الشراء
        $data['items'] = $this->model_purchase_order->getOrderItems($po_id);

        // تنسيق بيانات البنود
        foreach ($data['items'] as &$item) {
            $item['unit_price_formatted'] = $this->currency->format($item['unit_price'], $currency_code);
            $item['total_price_formatted'] = $this->currency->format($item['total_price'], $currency_code);
            $item['discount_amount_formatted'] = $this->currency->format($item['discount_amount'] ?? 0, $currency_code);
            $item['tax_amount_formatted'] = $this->currency->format($item['tax_amount'] ?? 0, $currency_code);
        }

        // معلومات الشركة
        $data['company'] = [
            'name' => $this->config->get('config_name'),
            'address' => nl2br($this->config->get('config_address')),
            'email' => $this->config->get('config_email'),
            'telephone' => $this->config->get('config_telephone'),
            'logo' => $this->config->get('config_logo') ? HTTP_CATALOG . 'image/' . $this->config->get('config_logo') : '',
        ];

        // نصوص اللغة
        $data['text_purchase_order'] = $this->language->get('text_purchase_order');
        $data['text_po_number'] = $this->language->get('text_po_number');
        $data['text_date'] = $this->language->get('text_date');
        $data['text_quotation_reference'] = $this->language->get('text_quotation_reference');
        $data['text_supplier'] = $this->language->get('text_supplier');
        $data['text_expected_delivery'] = $this->language->get('text_expected_delivery');
        $data['text_payment_terms'] = $this->language->get('text_payment_terms');
        $data['text_delivery_terms'] = $this->language->get('text_delivery_terms');
        $data['text_notes'] = $this->language->get('text_notes');
        $data['text_prepared_by'] = $this->language->get('text_prepared_by');
        $data['text_authorized_by'] = $this->language->get('text_authorized_by');
        $data['text_supplier_signature'] = $this->language->get('text_supplier_signature');
        $data['text_subtotal'] = $this->language->get('text_subtotal');
        $data['text_discount'] = $this->language->get('text_discount');
        $data['text_tax'] = $this->language->get('text_tax');
        $data['text_total'] = $this->language->get('text_total');
        $data['text_print_date'] = $this->language->get('text_print_date');

        $data['column_item'] = $this->language->get('column_item');
        $data['column_description'] = $this->language->get('column_description');
        $data['column_quantity'] = $this->language->get('column_quantity');
        $data['column_unit'] = $this->language->get('column_unit');
        $data['column_unit_price'] = $this->language->get('column_unit_price');
        $data['column_discount'] = $this->language->get('column_discount');
        $data['column_tax'] = $this->language->get('column_tax');
        $data['column_total'] = $this->language->get('column_total');

        $data['print_date'] = date($this->language->get('datetime_format'));
        $data['user_token'] = $this->session->data['user_token'];

        $this->response->setOutput($this->load->view('purchase/purchase_order_print', $data));
    }

    /**
     * تصدير أوامر الشراء
     */
    public function export() {
        if (!$this->user->hasKey('purchase_order_export')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }

        $export_type = isset($this->request->get['type']) ? $this->request->get['type'] : 'excel';

        // تهيئة بيانات الفلتر
        $filter_data = [
            'filter_po_number'    => $this->request->get['filter_po_number'] ?? '',
            'filter_supplier_id'  => isset($this->request->get['filter_supplier_id']) ? (int)$this->request->get['filter_supplier_id'] : 0,
            'filter_quotation_id' => isset($this->request->get['filter_quotation_id']) ? (int)$this->request->get['filter_quotation_id'] : 0,
            'filter_status'       => $this->request->get['filter_status'] ?? '',
            'filter_date_start'   => $this->request->get['filter_date_start'] ?? '',
            'filter_date_end'     => $this->request->get['filter_date_end'] ?? '',
            'sort'                => 'po.order_date',
            'order'               => 'DESC',
            'start'               => 0,
            'limit'               => 1000 // حد أعلى للتصدير
        ];

        $orders = $this->model_purchase_order->getOrders($filter_data);

        if ($export_type == 'excel') {
            $this->exportExcel($orders);
        } else if ($export_type == 'pdf') {
            $this->exportPDF($orders);
        } else {
            $this->response->redirect($this->url->link('purchase/order', 'user_token=' . $this->session->data['user_token'], true));
        }
    }

    /**
     * تصدير إلى Excel
     */
    protected function exportExcel($orders) {
        // استدعاء مكتبة PhpSpreadsheet
        require_once(DIR_SYSTEM . 'library/phpspreadsheet/vendor/autoload.php');

        // إنشاء ملف اكسل جديد
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        // ضبط خصائص المستند
        $spreadsheet->getProperties()
            ->setCreator($this->config->get('config_name'))
            ->setLastModifiedBy($this->config->get('config_name'))
            ->setTitle($this->language->get('heading_title'))
            ->setSubject($this->language->get('heading_title'))
            ->setDescription($this->language->get('heading_title'));

        $spreadsheet->setActiveSheetIndex(0);
        $sheet = $spreadsheet->getActiveSheet();

        // ضبط ترويسة الأعمدة
        $columns = [
            'A' => 'column_po_number',
            'B' => 'column_quotation_number',
            'C' => 'column_supplier',
            'D' => 'column_total',
            'E' => 'column_status',
            'F' => 'column_order_date',
            'G' => 'column_expected_delivery',
            'H' => 'text_payment_terms'
        ];

        foreach ($columns as $column => $langKey) {
            $sheet->setCellValue($column . '1', $this->language->get($langKey));
            $sheet->getStyle($column . '1')->getFont()->setBold(true);
            $sheet->getStyle($column . '1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            $sheet->getStyle($column . '1')->getFill()->getStartColor()->setRGB('EEEEEE');
        }

        // تعبئة صفوف البيانات
        $row = 2;
        foreach ($orders as $order) {
            $currency_code = $order['currency_code'] ?? $this->config->get('config_currency');

            $sheet->setCellValue('A' . $row, $order['po_number']);
            $sheet->setCellValue('B' . $row, $order['quotation_number'] ?? '');
            $sheet->setCellValue('C' . $row, $order['supplier_name']);
            $sheet->setCellValue('D' . $row, $this->currency->format($order['total_amount'], $currency_code, false));
            $sheet->setCellValue('E' . $row, $this->model_purchase_order->getStatusText($order['status']));
            $sheet->setCellValue('F' . $row, date($this->language->get('date_format_short'), strtotime($order['order_date'])));
            $sheet->setCellValue('G' . $row, $order['expected_delivery_date'] ? date($this->language->get('date_format_short'), strtotime($order['expected_delivery_date'])) : '');
            $sheet->setCellValue('H' . $row, $order['payment_terms'] ?? '');
            $row++;
        }

        // تنسيق الأعمدة
        foreach (array_keys($columns) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // إضافة تنسيق العملة لعمود المجموع
        $lastRow = count($orders) + 1;
        $sheet->getStyle('D2:D' . $lastRow)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

        // إضافة تنسيق التاريخ
        $sheet->getStyle('F2:F' . $lastRow)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_YYYYMMDD);
        $sheet->getStyle('G2:G' . $lastRow)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_YYYYMMDD);

        // تعيين اسم وفهرس ورقة العمل النشطة
        $spreadsheet->getActiveSheet()->setTitle($this->language->get('text_purchase_orders'));
        $spreadsheet->setActiveSheetIndex(0);

        // ضبط ترويسات الملف لتنزيله
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="purchase_orders_' . date('Y-m-d_His') . '.xlsx"');
        header('Cache-Control: max-age=0');

        // إنشاء الملف وتصديره
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * تصدير إلى PDF
     */
    protected function exportPDF($orders) {
        // التحقق من وجود مكتبة mPDF أو TCPDF
        if (class_exists('Mpdf\Mpdf')) {
            $this->exportPDFWithMpdf($orders);
        } else {
            // الرجوع إلى HTML الأساسي لتشجيع تثبيت مكتبة PDF
            $this->exportPDFAsHTML($orders);
        }
    }

    /**
     * تصدير باستخدام مكتبة mPDF إذا كانت متاحة
     */
    protected function exportPDFWithMpdf($orders) {
        require_once(DIR_SYSTEM . 'library/vendor/autoload.php');

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 15,
            'margin_bottom' => 15
        ]);

        // ضبط معلومات المستند
        $mpdf->SetCreator($this->config->get('config_name'));
        $mpdf->SetTitle($this->language->get('heading_title'));

        // إنشاء محتوى HTML
        $html = $this->getPDFContent($orders);

        // كتابة المحتوى
        $mpdf->WriteHTML($html);

        // إخراج ملف PDF
        $mpdf->Output('purchase_orders_' . date('Y-m-d_His') . '.pdf', 'D');
        exit;
    }

    /**
     * العودة إلى HTML عندما لا تتوفر مكتبة PDF
     */
    protected function exportPDFAsHTML($orders) {
        $html = $this->getPDFContent($orders);

        // ضبط الترويسات
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename="purchase_orders_' . date('Y-m-d_His') . '.html"');

        echo $html;
        exit;
    }

    /**
     * إنشاء محتوى HTML لتصدير PDF
     */
    protected function getPDFContent($orders) {
        $data = [
            'heading_title' => $this->language->get('heading_title'),
            'text_date' => $this->language->get('text_date') . ': ' . date($this->language->get('date_format_short')),
            'text_purchase_orders' => $this->language->get('text_purchase_orders'),
            'column_po_number' => $this->language->get('column_po_number'),
            'column_quotation_number' => $this->language->get('column_quotation_number'),
            'column_supplier' => $this->language->get('column_supplier'),
            'column_total' => $this->language->get('column_total'),
            'column_status' => $this->language->get('column_status'),
            'column_order_date' => $this->language->get('column_order_date'),
            'column_expected_delivery' => $this->language->get('column_expected_delivery'),
            'config_name' => $this->config->get('config_name'),
            'orders' => []
        ];

        foreach ($orders as $order) {
            $currency_code = $order['currency_code'] ?? $this->config->get('config_currency');

            $data['orders'][] = [
                'po_number' => $order['po_number'],
                'quotation_number' => $order['quotation_number'] ?? '',
                'supplier_name' => $order['supplier_name'],
                'total_formatted' => $this->currency->format($order['total_amount'], $currency_code),
                'status_text' => $this->model_purchase_order->getStatusText($order['status']),
                'order_date' => date($this->language->get('date_format_short'), strtotime($order['order_date'])),
                'expected_delivery_date' => $order['expected_delivery_date'] ? date($this->language->get('date_format_short'), strtotime($order['expected_delivery_date'])) : ''
            ];
        }

        return $this->load->view('purchase/purchase_order_export_pdf', $data);
    }

    /**
     * نموذج إنشاء إذن استلام
     */
    public function createReceipt() {
        if (!$this->user->hasKey('purchase_order_receipt')) {
            return $this->load->view('error/permission', []);
        }

        $po_id = isset($this->request->get['po_id']) ? (int)$this->request->get['po_id'] : 0;

        if (!$po_id) {
            return $this->load->view('error/not_found', []);
        }

        // الحصول على معلومات أمر الشراء
        $order_info = $this->model_purchase_order->getOrder($po_id);

        if (!$order_info) {
            return $this->load->view('error/not_found', []);
        }

        // التحقق من حالة أمر الشراء
        if (!in_array($order_info['status'], ['approved', 'partially_received'])) {
            $data['error'] = $this->language->get('error_order_status_receipt');
            return $this->load->view('error/permission', $data);
        }

        $data = [];

        // تنسيق بيانات أمر الشراء
        $currency_code = $order_info['currency_code'] ?? $this->config->get('config_currency');

        $data['order'] = [
            'po_id' => $order_info['po_id'],
            'po_number' => $order_info['po_number'],
            'supplier_id' => $order_info['supplier_id'],
            'supplier_name' => $order_info['supplier_name'],
            'currency_code' => $currency_code,
            'currency_id' => $order_info['currency_id'],
            'exchange_rate' => $order_info['exchange_rate'],
            'order_date' => date($this->language->get('date_format_short'), strtotime($order_info['order_date'])),
            'expected_delivery_date' => $order_info['expected_delivery_date'] ? date($this->language->get('date_format_short'), strtotime($order_info['expected_delivery_date'])) : '',
            'status' => $order_info['status'],
            'status_text' => $this->model_purchase_order->getStatusText($order_info['status'])
        ];

        // الحصول على بنود أمر الشراء مع معلومات الاستلام
        $data['items'] = $this->model_purchase_order->getOrderItemsWithReceiptInfo($po_id);

        // الحصول على قائمة الفروع/المخازن
        $data['branches'] = $this->model_purchase_order->getBranches();

        // نصوص اللغة
        $data['text_create_receipt'] = $this->language->get('text_create_receipt');
        $data['text_order_details'] = $this->language->get('text_order_details');
        $data['text_order_number'] = $this->language->get('text_order_number');
        $data['text_supplier'] = $this->language->get('text_supplier');
        $data['text_order_date'] = $this->language->get('text_order_date');
        $data['text_receipt_date'] = $this->language->get('text_receipt_date');
        $data['text_branch'] = $this->language->get('text_branch');
        $data['text_invoice_number'] = $this->language->get('text_invoice_number');
        $data['text_invoice_date'] = $this->language->get('text_invoice_date');
        $data['text_invoice_amount'] = $this->language->get('text_invoice_amount');
        $data['text_quality_check'] = $this->language->get('text_quality_check');
        $data['text_notes'] = $this->language->get('text_notes');

        $data['column_product'] = $this->language->get('column_product');
        $data['column_ordered_quantity'] = $this->language->get('column_ordered_quantity');
        $data['column_received_quantity'] = $this->language->get('column_received_quantity');
        $data['column_remaining_quantity'] = $this->language->get('column_remaining_quantity');
        $data['column_unit'] = $this->language->get('column_unit');
        $data['column_unit_price'] = $this->language->get('column_unit_price');
        $data['column_receive_quantity'] = $this->language->get('column_receive_quantity');
        $data['column_remarks'] = $this->language->get('column_remarks');

        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        $data['user_token'] = $this->session->data['user_token'];

        return $this->response->setOutput($this->load->view('purchase/order_receipt_form', $data));
    }

    /**
     * حفظ إذن استلام
     */
    public function ajaxSaveReceipt() {
        $this->load->language('purchase/order');

        $json = [];

        // التحقق من الصلاحيات
        if (!$this->user->hasKey('purchase_order_receipt')) {
            $json['error'] = $this->language->get('error_permission');
            $this->sendJSON($json);
            return;
        }

        $po_id = isset($this->request->post['po_id']) ? (int)$this->request->post['po_id'] : 0;

        if (!$po_id) {
            $json['error'] = $this->language->get('error_order_required');
            $this->sendJSON($json);
            return;
        }

        // التحقق من الحقول المطلوبة
        if (empty($this->request->post['branch_id'])) {
            $json['error'] = $this->language->get('error_branch_required');
            $this->sendJSON($json);
            return;
        }

        if (empty($this->request->post['receipt_date'])) {
            $json['error'] = $this->language->get('error_receipt_date_required');
            $this->sendJSON($json);
            return;
        }

        // التحقق من البنود
        if (!isset($this->request->post['item']) || !isset($this->request->post['item']['po_item_id']) || !is_array($this->request->post['item']['po_item_id'])) {
            $json['error'] = $this->language->get('error_items_required');
            $this->sendJSON($json);
            return;
        }

        // التحقق من وجود كميات استلام صالحة
        $valid_receipt = false;
        for ($i = 0; $i < count($this->request->post['item']['po_item_id']); $i++) {
            if ((float)$this->request->post['item']['quantity_received'][$i] > 0) {
                $valid_receipt = true;
                break;
            }
        }

        if (!$valid_receipt) {
            $json['error'] = $this->language->get('error_no_valid_receipt_quantity');
            $this->sendJSON($json);
            return;
        }

        // تحضير بيانات إذن الاستلام
        $receipt_data = [
            'po_id' => $po_id,
            'branch_id' => (int)$this->request->post['branch_id'],
            'receipt_date' => $this->request->post['receipt_date'],
            'invoice_number' => $this->request->post['invoice_number'] ?? null,
            'invoice_date' => !empty($this->request->post['invoice_date']) ? $this->request->post['invoice_date'] : null,
            'invoice_amount' => !empty($this->request->post['invoice_amount']) ? (float)$this->request->post['invoice_amount'] : null,
            'currency_id' => isset($this->request->post['currency_id']) ? (int)$this->request->post['currency_id'] : null,
            'exchange_rate' => isset($this->request->post['exchange_rate']) ? (float)$this->request->post['exchange_rate'] : null,
            'quality_check_required' => isset($this->request->post['quality_check_required']) ? 1 : 0,
            'notes' => $this->request->post['notes'] ?? '',
            'created_by' => $this->user->getId(),
            'items' => []
        ];

        // تحضير بنود الاستلام
        for ($i = 0; $i < count($this->request->post['item']['po_item_id']); $i++) {
            $quantity_received = (float)$this->request->post['item']['quantity_received'][$i];

            // تجاهل البنود بكمية صفر
            if ($quantity_received <= 0) {
                continue;
            }

            $receipt_data['items'][] = [
                'po_item_id' => (int)$this->request->post['item']['po_item_id'][$i],
                'product_id' => (int)$this->request->post['item']['product_id'][$i],
                'quantity_received' => $quantity_received,
                'unit_id' => (int)$this->request->post['item']['unit_id'][$i],
                'quality_result' => 'pending',
                'remarks' => $this->request->post['item']['remarks'][$i] ?? '',
                'invoice_unit_price' => isset($this->request->post['item']['invoice_unit_price'][$i]) ? (float)$this->request->post['item']['invoice_unit_price'][$i] : null
            ];
        }

        try {
            $receipt_id = $this->model_purchase_order->addGoodsReceipt($receipt_data);

            if (!$receipt_id) {
                $json['error'] = $this->language->get('error_saving_receipt');
            } else {
                $json['success'] = $this->language->get('text_receipt_success');
                $json['redirect'] = $this->url->link('purchase/order/view', 'user_token=' . $this->session->data['user_token'] . '&po_id=' . $po_id, true);
            }
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->sendJSON($json);
    }

    /**
     * المطابقة الثلاثية
     */
    public function match() {
        if (!$this->user->hasKey('purchase_order_match')) {
            return $this->load->view('error/permission', []);
        }

        $po_id = isset($this->request->get['po_id']) ? (int)$this->request->get['po_id'] : 0;

        if (!$po_id) {
            return $this->load->view('error/not_found', []);
        }

        // الحصول على معلومات أمر الشراء
        $order_info = $this->model_purchase_order->getOrder($po_id);

        if (!$order_info) {
            return $this->load->view('error/not_found', []);
        }

        // التحقق من حالة أمر الشراء
        if (!in_array($order_info['status'], ['approved', 'partially_received', 'fully_received'])) {
            $data['error'] = $this->language->get('error_order_status_match');
            return $this->load->view('error/permission', $data);
        }

        // الحصول على بيانات المطابقة
        $data = $this->model_purchase_order->getMatchingData($po_id);

        if (!$data || empty($data['order']) || empty($data['receipts'])) {
            $data['error'] = $this->language->get('error_no_matching_data');
            return $this->load->view('error/permission', $data);
        }

        // نصوص اللغة
        $data['text_matching_title'] = $this->language->get('text_matching_title');
        $data['text_order_details'] = $this->language->get('text_order_details');
        $data['text_po_number'] = $this->language->get('text_po_number');
        $data['text_supplier'] = $this->language->get('text_supplier');
        $data['text_order_date'] = $this->language->get('text_order_date');
        $data['text_matching_status'] = $this->language->get('text_matching_status');
        $data['text_receipts'] = $this->language->get('text_receipts');
        $data['text_invoices'] = $this->language->get('text_invoices');
        $data['text_matching_comparison'] = $this->language->get('text_matching_comparison');
        $data['text_matching_explanation'] = $this->language->get('text_matching_explanation');
        $data['text_export_matching'] = $this->language->get('text_export_matching');

        $data['column_product'] = $this->language->get('column_product');
        $data['column_po_quantity'] = $this->language->get('column_po_quantity');
        $data['column_po_price'] = $this->language->get('column_po_price');
        $data['column_received_quantity'] = $this->language->get('column_received_quantity');
        $data['column_received_date'] = $this->language->get('column_received_date');
        $data['column_invoice_quantity'] = $this->language->get('column_invoice_quantity');
        $data['column_invoice_price'] = $this->language->get('column_invoice_price');
$data['column_variance'] = $this->language->get('column_variance');
        $data['column_variance_amount'] = $this->language->get('column_variance_amount');
        $data['column_unit'] = $this->language->get('column_unit');

        $data['button_close'] = $this->language->get('button_close');
        $data['button_save_matching'] = $this->language->get('button_save_matching');
        $data['button_export_pdf'] = $this->language->get('button_export_pdf');
        $data['button_export_excel'] = $this->language->get('button_export_excel');

        $data['user_token'] = $this->session->data['user_token'];

        return $this->response->setOutput($this->load->view('purchase/purchase_order_matching', $data));
    }

    /**
     * إجراء المطابقة الثلاثية
     */
    public function ajaxMatch() {
        $this->load->language('purchase/order');

        $json = [];

        if (!$this->user->hasKey('purchase_order_match')) {
            $json['error'] = $this->language->get('error_permission');
            $this->sendJSON($json);
            return;
        }

        $po_id = isset($this->request->post['po_id']) ? (int)$this->request->post['po_id'] : 0;

        if (!$po_id) {
            $json['error'] = $this->language->get('error_order_required');
            $this->sendJSON($json);
            return;
        }

        // التحقق من البيانات المرسلة
        if (!isset($this->request->post['matching']) || !is_array($this->request->post['matching'])) {
            $json['error'] = $this->language->get('error_invalid_matching_data');
            $this->sendJSON($json);
            return;
        }

        // إعداد بيانات المطابقة
        $matching_data = [
            'po_id' => $po_id,
            'matched_by' => $this->user->getId(),
            'notes' => $this->request->post['notes'] ?? '',
            'items' => $this->request->post['matching']
        ];

        try {
            $result = $this->model_purchase_order->saveMatching($matching_data);

            if (!$result) {
                $json['error'] = $this->language->get('error_saving_matching');
            } else {
                $json['success'] = $this->language->get('text_matching_success');
                $json['redirect'] = $this->url->link('purchase/order/view', 'user_token=' . $this->session->data['user_token'] . '&po_id=' . $po_id, true);
            }
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->sendJSON($json);
    }

    /**
     * تنفيذ إجراءات على مجموعة من أوامر الشراء
     */
    public function bulkAction() {
        $this->load->language('purchase/order');

        $json = [];

        if (!isset($this->request->post['action']) || !isset($this->request->post['selected']) || !is_array($this->request->post['selected'])) {
            $json['error'] = $this->language->get('error_invalid_request');
            $this->sendJSON($json);
            return;
        }

        $action = $this->request->post['action'];
        $selected = array_map('intval', $this->request->post['selected']);

        // التحقق من الصلاحيات استنادًا إلى الإجراء
        switch ($action) {
            case 'approve':
                if (!$this->user->hasKey('purchase_order_approve')) {
                    $json['error'] = $this->language->get('error_permission');
                    $this->sendJSON($json);
                    return;
                }
                break;

            case 'reject':
                if (!$this->user->hasKey('purchase_order_reject')) {
                    $json['error'] = $this->language->get('error_permission');
                    $this->sendJSON($json);
                    return;
                }
                break;

            case 'delete':
                if (!$this->user->hasKey('purchase_order_delete')) {
                    $json['error'] = $this->language->get('error_permission');
                    $this->sendJSON($json);
                    return;
                }
                break;

            default:
                $json['error'] = $this->language->get('error_invalid_action');
                $this->sendJSON($json);
                return;
        }

        // معالجة الإجراء
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($selected as $po_id) {
            try {
                switch ($action) {
                    case 'approve':
                        $success = $this->model_purchase_order->approveOrder($po_id, $this->user->getId());
                        break;

                    case 'reject':
                        $reason = $this->request->post['reason'] ?? $this->language->get('text_bulk_action');
                        $success = $this->model_purchase_order->rejectOrder($po_id, $reason, $this->user->getId());
                        break;

                    case 'delete':
                        $success = $this->model_purchase_order->deleteOrder($po_id);
                        break;
                }

                if ($success) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                }
            } catch (Exception $e) {
                $results['failed']++;
                $results['errors'][] = $this->language->get('error_processing') . ' #' . $po_id . ': ' . $e->getMessage();
            }
        }

        // بناء الاستجابة
        if ($results['success'] > 0) {
            $json['success'] = sprintf($this->language->get('text_bulk_success'), $results['success'], count($selected));
        }

        if ($results['failed'] > 0) {
            $json['error'] = sprintf($this->language->get('text_bulk_failed'), $results['failed'], count($selected));
            if (!empty($results['errors'])) {
                $json['errors'] = $results['errors'];
            }
        }

        $this->sendJSON($json);
    }

    /**
     * الحصول على بيانات عرض سعر لتحويله إلى أمر شراء
     */
    public function ajaxGetQuotation() {
        $this->load->language('purchase/order');

        $json = [];

        if (!$this->user->hasKey('purchase_order_add')) {
            $json['error'] = $this->language->get('error_permission');
            $this->sendJSON($json);
            return;
        }

        $quotation_id = isset($this->request->get['quotation_id']) ? (int)$this->request->get['quotation_id'] : 0;

        if (!$quotation_id) {
            $json['error'] = $this->language->get('error_quotation_required');
            $this->sendJSON($json);
            return;
        }

        $this->load->model('purchase/quotation');
        $quotation_info = $this->model_purchase_quotation->getQuotation($quotation_id);

        if (!$quotation_info) {
            $json['error'] = $this->language->get('error_quotation_not_found');
            $this->sendJSON($json);
            return;
        }

        // التحقق من حالة عرض السعر
        if ($quotation_info['status'] != 'approved') {
            $json['error'] = $this->language->get('error_quotation_not_approved');
            $this->sendJSON($json);
            return;
        }

        // الحصول على بنود عرض السعر
        $quotation_items = $this->model_purchase_quotation->getQuotationItems($quotation_id);

        // تحضير بيانات عرض السعر
        $json['quotation'] = [
            'quotation_id' => $quotation_info['quotation_id'],
            'quotation_number' => $quotation_info['quotation_number'],
            'supplier_id' => $quotation_info['supplier_id'],
            'supplier_name' => $quotation_info['supplier_name'],
            'currency_id' => $quotation_info['currency_id'],
            'currency_code' => $quotation_info['currency_code'],
            'exchange_rate' => $quotation_info['exchange_rate'],
            'validity_date' => $quotation_info['validity_date'],
            'payment_terms' => $quotation_info['payment_terms'],
            'delivery_terms' => $quotation_info['delivery_terms'],
            'subtotal' => $quotation_info['subtotal'],
            'tax_amount' => $quotation_info['tax_amount'],
            'discount_amount' => $quotation_info['discount_amount'],
            'total_amount' => $quotation_info['total_amount'],
            'tax_included' => $quotation_info['tax_included'],
            'tax_rate' => $quotation_info['tax_rate'],
            'requisition_id' => $quotation_info['requisition_id'],
            'items' => []
        ];

        // إضافة بنود عرض السعر
        foreach ($quotation_items as $item) {
            $json['quotation']['items'][] = [
                'quotation_item_id' => $item['quotation_item_id'],
                'requisition_item_id' => $item['requisition_item_id'],
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'unit_id' => $item['unit_id'],
                'unit_name' => $item['unit_name'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'tax_rate' => $item['tax_rate'],
                'discount_type' => $item['discount_type'],
                'discount_rate' => $item['discount_rate'],
                'discount_amount' => $item['discount_amount'],
                'tax_amount' => $item['tax_amount'],
                'line_total' => $item['line_total'],
                'description' => $item['description'] ?? ''
            ];
        }

        $this->sendJSON($json);
    }

    /**
     * البحث عن المنتجات عبر AJAX
     */
    public function ajaxSearchProducts() {
        if (!$this->user->hasKey('purchase_order_view')) {
            $this->sendJSON(['error' => $this->language->get('error_permission')]);
            return;
        }

        $json = [];
        $search = $this->request->get['q'] ?? '';

        $results = $this->model_purchase_order->searchProducts($search);

        foreach ($results as $result) {
            $json[] = [
                'id' => $result['product_id'],
                'text' => $result['name'] . ' (' . $result['model'] . ')',
                'model' => $result['model']
            ];
        }

        $this->sendJSON($json);
    }

    /**
     * الحصول على تفاصيل منتج
     */
    public function ajaxGetProductDetails() {
        if (!$this->user->hasKey('purchase_order_view')) {
            $this->sendJSON(['error' => $this->language->get('error_permission')]);
            return;
        }

        $product_id = isset($this->request->get['product_id']) ? (int)$this->request->get['product_id'] : 0;

        if (!$product_id) {
            $this->sendJSON(['error' => $this->language->get('error_product_required')]);
            return;
        }

        $product_info = $this->model_purchase_order->getProductDetails($product_id);

        $this->sendJSON($product_info);
    }

    /**
     * تحميل مستند
     */
    public function ajaxUploadDocument() {
        if (!$this->user->hasKey('purchase_order_upload')) {
            $this->sendJSON(['error' => $this->language->get('error_permission')]);
            return;
        }

        $po_id = isset($this->request->post['po_id']) ? (int)$this->request->post['po_id'] : 0;

        if (!$po_id) {
            $this->sendJSON(['error' => $this->language->get('error_order_required')]);
            return;
        }

        if (!isset($this->request->files['file']) || !$this->request->files['file']['tmp_name']) {
            $this->sendJSON(['error' => $this->language->get('error_file_required')]);
            return;
        }

        try {
            $upload_info = $this->model_purchase_order->uploadDocument(
                $po_id,
                $this->request->files['file'],
                $this->request->post['document_type'] ?? 'purchase_order',
                $this->user->getId()
            );

            $this->sendJSON(['success' => $this->language->get('text_upload_success'), 'data' => $upload_info]);
        } catch (Exception $e) {
            $this->sendJSON(['error' => $e->getMessage()]);
        }
    }

    /**
     * حذف مستند
     */
    public function ajaxDeleteDocument() {
        if (!$this->user->hasKey('purchase_order_delete')) {
            $this->sendJSON(['error' => $this->language->get('error_permission')]);
            return;
        }

        $document_id = isset($this->request->post['document_id']) ? (int)$this->request->post['document_id'] : 0;

        if (!$document_id) {
            $this->sendJSON(['error' => $this->language->get('error_document_required')]);
            return;
        }

        try {
            $result = $this->model_purchase_order->deleteDocument($document_id);

            if (!$result) {
                $this->sendJSON(['error' => $this->language->get('error_deleting_document')]);
            } else {
                $this->sendJSON(['success' => $this->language->get('text_delete_success')]);
            }
        } catch (Exception $e) {
            $this->sendJSON(['error' => $e->getMessage()]);
        }
    }

    /**
     * الحصول على قائمة المستندات
     */
    public function getDocuments() {
        $this->load->language('purchase/order');

        $json = array();

        if (!$this->user->hasPermission('access', 'purchase/order')) {
            $json['error'] = $this->language->get('error_permission');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        $po_id = isset($this->request->get['po_id']) ? (int)$this->request->get['po_id'] : 0;

        if (!$po_id) {
            $json['error'] = $this->language->get('error_order_required');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        $result = $this->model_purchase_order->getDocumentsWithPermissions($po_id);

        $json = $result;

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * تنزيل مستند
     */
    public function downloadDocument() {
        if (!$this->user->hasKey('purchase_order_view')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
            return;
        }

        $document_id = isset($this->request->get['document_id']) ? (int)$this->request->get['document_id'] : 0;

        if (!$document_id) {
            $this->session->data['error_warning'] = $this->language->get('error_document_required');
            $this->response->redirect($this->url->link('purchase/order', 'user_token=' . $this->session->data['user_token'], true));
            return;
        }

        $document_info = $this->model_purchase_order->getDocument($document_id);

        if (!$document_info || !file_exists(DIR_UPLOAD . $document_info['file_path'])) {
            $this->session->data['error_warning'] = $this->language->get('error_file_not_found');
            $this->response->redirect($this->url->link('purchase/order', 'user_token=' . $this->session->data['user_token'], true));
            return;
        }

        $file = DIR_UPLOAD . $document_info['file_path'];

        $mime_type = mime_content_type($file);
        if (!$mime_type) {
            $mime_type = 'application/octet-stream';
        }

        // ضبط الترويسات للتنزيل
        header('Content-Type: ' . $mime_type);
        header('Content-Disposition: attachment; filename="' . $document_info['document_name'] . '"');
        header('Content-Length: ' . filesize($file));
        header('Cache-Control: max-age=0, must-revalidate');
        header('Pragma: public');

        readfile($file);
        exit();
    }

    /**
     * معاينة مستند
     */
    public function previewDocument() {
        if (!$this->user->hasPermission('access', 'purchase/order')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
            return;
        }

        $document_id = isset($this->request->get['document_id']) ? (int)$this->request->get['document_id'] : 0;
        $thumbnail = isset($this->request->get['thumb']) && $this->request->get['thumb'] == '1';

        if (!$document_id) {
            $this->response->redirect($this->url->link('error/not_found', 'user_token=' . $this->session->data['user_token'], true));
            return;
        }

        $result = $this->model_purchase_order->previewDocument($document_id, $thumbnail);

        if (!$result) {
            $this->response->redirect($this->url->link('error/not_found', 'user_token=' . $this->session->data['user_token'], true));
        }

        exit;
    }

    /**
     * تحديث تكلفة المنتجات
     */
    public function ajaxUpdateCosts() {
        $this->load->language('purchase/order');

        $json = [];

        if (!$this->user->hasKey('purchase_order_manage_costs')) {
            $json['error'] = $this->language->get('error_permission');
            $this->sendJSON($json);
            return;
        }

        $receipt_id = isset($this->request->post['receipt_id']) ? (int)$this->request->post['receipt_id'] : 0;

        if (!$receipt_id) {
            $json['error'] = $this->language->get('error_receipt_required');
            $this->sendJSON($json);
            return;
        }

        try {
            $result = $this->model_purchase_order->updateProductCosts($receipt_id);

            if (!$result) {
                $json['error'] = $this->language->get('error_updating_costs');
            } else {
                $json['success'] = $this->language->get('text_costs_updated');
            }
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->sendJSON($json);
    }

    /**
     * طريقة مساعدة لإرسال استجابة JSON
     */
    protected function sendJSON($data) {
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($data));
    }

    /**
     * AJAX method to approve purchase order
     */
    public function ajaxApprove() {
        $json = array();

        if (!$this->user->hasPermission('modify', 'purchase/order')) {
            $json['error'] = $this->language->get('error_permission');
            return $this->sendJSON($json);
        }

        $po_id = (int)($this->request->get['po_id'] ?? 0);

        if ($po_id) {
            $result = $this->model_purchase_order->approveOrder($po_id, $this->user->getId());
            if (!empty($result['error'])) {
                $json['error'] = $result['error'];
            } else {
                $json['success'] = $this->language->get('text_approve_success');
            }
        } else {
            $json['error'] = 'Missing po_id';
        }

        return $this->sendJSON($json);
    }

    /**
     * AJAX method to reject purchase order
     */
    public function ajaxReject() {
        $json = array();

        if (!$this->user->hasPermission('modify', 'purchase/order')) {
            $json['error'] = $this->language->get('error_permission');
            return $this->sendJSON($json);
        }

        $po_id = (int)($this->request->post['po_id'] ?? 0);
        $reason = ($this->request->post['reason'] ?? '');

        if ($po_id) {
            $result = $this->model_purchase_order->rejectOrder($po_id, $this->user->getId(), $reason);
            if (!empty($result['error'])) {
                $json['error'] = $result['error'];
            } else {
                $json['success'] = $this->language->get('text_reject_success');
            }
        } else {
            $json['error'] = 'Missing po_id';
        }

        return $this->sendJSON($json);
    }

    /**
     * AJAX method to delete purchase order
     */
    public function ajaxDelete() {
        $json = array();

        if (!$this->user->hasPermission('modify', 'purchase/order')) {
            $json['error'] = $this->language->get('error_permission');
            return $this->sendJSON($json);
        }

        $po_id = (int)($this->request->get['po_id'] ?? 0);

        if ($po_id) {
            $result = $this->model_purchase_order->deleteOrder($po_id);
            if (!empty($result['error'])) {
                $json['error'] = $result['error'];
            } else {
                $json['success'] = $this->language->get('text_delete_success');
            }
        } else {
            $json['error'] = 'Missing po_id';
        }

        return $this->sendJSON($json);
    }

    /**
     * إنشاء قيود محاسبية تلقائية لأمر الشراء
     */
    public function generateAccountingEntries() {
        $this->load->language('purchase/order');

        $json = array();

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            try {
                // التحقق من الصلاحيات
                if (!$this->user->hasPermission('modify', 'purchase/order')) {
                    throw new Exception($this->language->get('error_permission'));
                }

                $po_id = $this->request->post['po_id'];
                $entry_type = $this->request->post['entry_type'] ?? 'commitment'; // commitment, receipt, invoice

                $result = $this->model_purchase_accounting_integration_advanced->generatePurchaseOrderEntries($po_id, $entry_type);

                if ($result['success']) {
                    $json['success'] = true;
                    $json['message'] = 'تم إنشاء القيود المحاسبية بنجاح';
                    $json['journal_entries'] = $result['journal_entries'];

                    // تسجيل في سجل المراجعة
                    $this->model_accounts_audit_trail->logAction([
                        'action_type' => 'generate_accounting_entries',
                        'table_name' => 'purchase_orders',
                        'record_id' => $po_id,
                        'description' => 'إنشاء قيود محاسبية لأمر الشراء رقم: ' . $po_id . ' - نوع القيد: ' . $entry_type,
                        'module' => 'purchase_order'
                    ]);
                } else {
                    $json['error'] = $result['error'];
                }

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'طريقة طلب غير صحيحة';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * تقديم أمر الشراء للموافقة الذكية
     */
    public function submitForApproval() {
        $this->load->language('purchase/order');

        $json = array();

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            try {
                // التحقق من الصلاحيات
                if (!$this->user->hasPermission('modify', 'purchase/order')) {
                    throw new Exception($this->language->get('error_permission'));
                }

                $po_id = $this->request->post['po_id'];
                $comments = $this->request->post['comments'] ?? '';

                // التحقق من صحة أمر الشراء
                $order_info = $this->model_purchase_order->getOrder($po_id);
                if (!$order_info) {
                    throw new Exception('أمر الشراء غير موجود');
                }

                if ($order_info['status'] != 'draft') {
                    throw new Exception('لا يمكن تقديم أمر الشراء للموافقة في هذه الحالة');
                }

                // تقديم للموافقة الذكية
                $approval_data = array(
                    'document_type' => 'purchase_order',
                    'document_id' => $po_id,
                    'amount' => $order_info['total_amount'],
                    'supplier_id' => $order_info['supplier_id'],
                    'department_id' => $order_info['department_id'] ?? null,
                    'comments' => $comments
                );

                $result = $this->model_purchase_smart_approval_system->submitForApproval($approval_data);

                if ($result['success']) {
                    // تحديث حالة أمر الشراء
                    $this->model_purchase_order->updateStatus($po_id, 'pending_approval');

                    $json['success'] = true;
                    $json['message'] = 'تم تقديم أمر الشراء للموافقة بنجاح';
                    $json['approval_workflow'] = $result['workflow'];

                    // تسجيل في سجل المراجعة
                    $this->model_accounts_audit_trail->logAction([
                        'action_type' => 'submit_for_approval',
                        'table_name' => 'purchase_orders',
                        'record_id' => $po_id,
                        'description' => 'تقديم أمر الشراء رقم: ' . $po_id . ' للموافقة',
                        'module' => 'purchase_order'
                    ]);
                } else {
                    $json['error'] = $result['error'];
                }

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'طريقة طلب غير صحيحة';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * الحصول على تحليلات أمر الشراء
     */
    public function getOrderAnalytics() {
        $this->load->language('purchase/order');

        $json = array();

        try {
            $po_id = $this->request->get['po_id'];

            if (!$this->user->hasPermission('access', 'purchase/order')) {
                throw new Exception($this->language->get('error_permission'));
            }

            $analytics = $this->model_purchase_order->getOrderAnalytics($po_id);

            $json['success'] = true;
            $json['analytics'] = $analytics;

        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * تتبع متقدم لحالة أمر الشراء
     */
    public function getAdvancedTracking() {
        $this->load->language('purchase/order');

        $json = array();

        try {
            $po_id = $this->request->get['po_id'];

            if (!$this->user->hasPermission('access', 'purchase/order')) {
                throw new Exception($this->language->get('error_permission'));
            }

            $tracking = $this->model_purchase_order->getAdvancedTracking($po_id);

            $json['success'] = true;
            $json['tracking'] = $tracking;

        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * إرسال إشعارات ذكية
     */
    public function sendSmartNotifications() {
        $this->load->language('purchase/order');

        $json = array();

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            try {
                // التحقق من الصلاحيات
                if (!$this->user->hasPermission('modify', 'purchase/order')) {
                    throw new Exception($this->language->get('error_permission'));
                }

                $po_id = $this->request->post['po_id'];
                $notification_type = $this->request->post['notification_type']; // reminder, escalation, status_update
                $recipients = $this->request->post['recipients'] ?? array();
                $message = $this->request->post['message'] ?? '';

                $result = $this->model_purchase_order->sendSmartNotifications($po_id, $notification_type, $recipients, $message);

                if ($result['success']) {
                    $json['success'] = true;
                    $json['message'] = 'تم إرسال الإشعارات بنجاح';
                    $json['sent_count'] = $result['sent_count'];

                    // تسجيل في سجل المراجعة
                    $this->model_accounts_audit_trail->logAction([
                        'action_type' => 'send_notifications',
                        'table_name' => 'purchase_orders',
                        'record_id' => $po_id,
                        'description' => 'إرسال إشعارات لأمر الشراء رقم: ' . $po_id . ' - النوع: ' . $notification_type,
                        'module' => 'purchase_order'
                    ]);
                } else {
                    $json['error'] = $result['error'];
                }

            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'طريقة طلب غير صحيحة';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}