<?php
class ControllerPurchaseQuoteComparison extends Controller {
    private $error = array();
    
    public function __construct($registry) {
        parent::__construct($registry);
        $this->load->model('purchase/quote_comparison');
        $this->load->model('purchase/order');
        $this->load->language('purchase/quote_comparison');
    }
    
    /**
     * عرض صفحة مقارنة عروض الأسعار لطلب شراء معين
     */
    public function index() {
        // التحقق من صلاحيات المستخدم
        if (!$this->user->hasKey('purchase_quotation_compare')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        // التحقق من وجود معرف طلب الشراء
        if (isset($this->request->get['po_id'])) {
            $po_id = (int)$this->request->get['po_id'];
        } else {
            $this->response->redirect($this->url->link('purchase/order', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        // الحصول على معلومات طلب الشراء
        $po_info = $this->model_purchase_quote_comparison->getPurchaseOrderInfo($po_id);
        if (!$po_info) {
            $this->session->data['error'] = $this->language->get('error_purchase_order_not_found');
            $this->response->redirect($this->url->link('purchase/order', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        // الحصول على عروض الأسعار المرتبطة بطلب الشراء
        $comparison_data = $this->model_purchase_quote_comparison->getQuotationsByPurchaseOrder($po_id);
        
        // إعداد بيانات العرض
        $data = array_merge($comparison_data, [
            'purchase_order' => $po_info,
            'user_token' => $this->session->data['user_token'],
            'link_export_excel' => $this->url->link('purchase/quote_comparison/exportExcel', 'user_token=' . $this->session->data['user_token'] . '&po_id=' . $po_id, true),
            'link_export_pdf' => $this->url->link('purchase/quote_comparison/exportPDF', 'user_token=' . $this->session->data['user_token'] . '&po_id=' . $po_id, true),
        ]);
        
        // إضافة نصوص اللغة
        $this->addLanguageStrings($data);
        
        // عرض القالب
        if (isset($this->request->get['modal']) && $this->request->get['modal'] == 'true') {
            // عرض في نافذة منبثقة
            $this->response->setOutput($this->load->view('purchase/po_quotation_comparison', $data));
        } else {
            // عرض كصفحة كاملة
            $data['header'] = $this->load->controller('common/header');
            $data['column_left'] = $this->load->controller('common/column_left');
            $data['footer'] = $this->load->controller('common/footer');
            
            $this->response->setOutput($this->load->view('purchase/po_quotation_comparison_page', $data));
        }
    }
    
    /**
     * تصدير مقارنة عروض الأسعار إلى ملف Excel
     */
    public function exportExcel() {
        // التحقق من صلاحيات المستخدم
        if (!$this->user->hasKey('purchase_quotation_export')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        // التحقق من وجود معرف طلب الشراء
        if (isset($this->request->get['po_id'])) {
            $po_id = (int)$this->request->get['po_id'];
        } else {
            $this->response->redirect($this->url->link('purchase/order', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        // الحصول على معلومات طلب الشراء
        $po_info = $this->model_purchase_quote_comparison->getPurchaseOrderInfo($po_id);
        if (!$po_info) {
            $this->session->data['error'] = $this->language->get('error_purchase_order_not_found');
            $this->response->redirect($this->url->link('purchase/order', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        // الحصول على عروض الأسعار المرتبطة بطلب الشراء
        $comparison_data = $this->model_purchase_quote_comparison->getQuotationsByPurchaseOrder($po_id);
        $comparison_data['purchase_order'] = $po_info;
        
        // تصدير البيانات إلى ملف Excel
        $file_path = $this->model_purchase_quote_comparison->exportComparisonToExcel($comparison_data);
        
        // تنزيل الملف
        if ($file_path) {
            $this->response->addHeader('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $this->response->addHeader('Content-Disposition: attachment; filename="quotation_comparison_' . $po_info['po_number'] . '.xlsx"');
            $this->response->setOutput(file_get_contents($file_path));
        } else {
            $this->session->data['error'] = $this->language->get('error_export_failed');
            $this->response->redirect($this->url->link('purchase/quote_comparison', 'user_token=' . $this->session->data['user_token'] . '&po_id=' . $po_id, true));
        }
    }
    
    /**
     * تصدير مقارنة عروض الأسعار إلى ملف PDF
     */
    public function exportPDF() {
        // التحقق من صلاحيات المستخدم
        if (!$this->user->hasKey('purchase_quotation_export')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        // التحقق من وجود معرف طلب الشراء
        if (isset($this->request->get['po_id'])) {
            $po_id = (int)$this->request->get['po_id'];
        } else {
            $this->response->redirect($this->url->link('purchase/order', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        // الحصول على معلومات طلب الشراء
        $po_info = $this->model_purchase_quote_comparison->getPurchaseOrderInfo($po_id);
        if (!$po_info) {
            $this->session->data['error'] = $this->language->get('error_purchase_order_not_found');
            $this->response->redirect($this->url->link('purchase/order', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        // الحصول على عروض الأسعار المرتبطة بطلب الشراء
        $comparison_data = $this->model_purchase_quote_comparison->getQuotationsByPurchaseOrder($po_id);
        $comparison_data['purchase_order'] = $po_info;
        
        // إضافة نصوص اللغة
        $this->addLanguageStrings($comparison_data);
        
        // تصدير البيانات إلى ملف PDF
        $file_path = $this->model_purchase_quote_comparison->exportComparisonToPDF($comparison_data);
        
        // تنزيل الملف
        if ($file_path) {
            $this->response->addHeader('Content-Type: application/pdf');
            $this->response->addHeader('Content-Disposition: attachment; filename="quotation_comparison_' . $po_info['po_number'] . '.pdf"');
            $this->response->setOutput(file_get_contents($file_path));
        } else {
            $this->session->data['error'] = $this->language->get('error_export_failed');
            $this->response->redirect($this->url->link('purchase/quote_comparison', 'user_token=' . $this->session->data['user_token'] . '&po_id=' . $po_id, true));
        }
    }
    
    /**
     * إضافة نصوص اللغة إلى مصفوفة البيانات
     * 
     * @param array &$data مصفوفة البيانات
     */
    private function addLanguageStrings(&$data) {
        // العناوين الرئيسية
        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_quotation_comparison'] = $this->language->get('text_quotation_comparison');
        $data['text_purchase_order_info'] = $this->language->get('text_purchase_order_info');
        $data['text_comparison_summary'] = $this->language->get('text_comparison_summary');
        $data['text_analysis_recommendations'] = $this->language->get('text_analysis_recommendations');
        $data['text_general_info'] = $this->language->get('text_general_info');
        $data['text_items_comparison'] = $this->language->get('text_items_comparison');
        $data['text_totals'] = $this->language->get('text_totals');
        $data['text_additional_criteria'] = $this->language->get('text_additional_criteria');
        $data['text_notes'] = $this->language->get('text_notes');
        
        // نصوص المعلومات
        $data['text_po_number'] = $this->language->get('text_po_number');
        $data['text_date_required'] = $this->language->get('text_date_required');
        $data['text_branch'] = $this->language->get('text_branch');
        $data['text_total_quotations'] = $this->language->get('text_total_quotations');
        $data['text_best_price_supplier'] = $this->language->get('text_best_price_supplier');
        $data['text_price_difference'] = $this->language->get('text_price_difference');
        $data['text_best_value_recommendation'] = $this->language->get('text_best_value_recommendation');
        $data['text_best_price_recommendation'] = $this->language->get('text_best_price_recommendation');
        $data['text_best_overall_value_explanation'] = $this->language->get('text_best_overall_value_explanation');
        $data['text_best_value_explanation'] = $this->language->get('text_best_value_explanation');
        
        // نصوص الجدول
        $data['column_attribute'] = $this->language->get('column_attribute');
        $data['text_supplier'] = $this->language->get('text_supplier');
        $data['text_date'] = $this->language->get('text_date');
        $data['text_validity'] = $this->language->get('text_validity');
        $data['text_currency'] = $this->language->get('text_currency');
        $data['text_payment_terms'] = $this->language->get('text_payment_terms');
        $data['text_delivery_terms'] = $this->language->get('text_delivery_terms');
        $data['text_subtotal'] = $this->language->get('text_subtotal');
        $data['text_discount'] = $this->language->get('text_discount');
        $data['text_tax'] = $this->language->get('text_tax');
        $data['text_total'] = $this->language->get('text_total');
        $data['text_quality_rating'] = $this->language->get('text_quality_rating');
        $data['text_delivery_rating'] = $this->language->get('text_delivery_rating');
        $data['text_warranty'] = $this->language->get('text_warranty');
        $data['text_expired'] = $this->language->get('text_expired');
        $data['text_valid'] = $this->language->get('text_valid');
        $data['text_not_quoted'] = $this->language->get('text_not_quoted');
        $data['text_not_specified'] = $this->language->get('text_not_specified');
        $data['text_best_price'] = $this->language->get('text_best_price');
        
        // نصوص الأزرار
        $data['text_approve_best_value'] = $this->language->get('text_approve_best_value');
        $data['text_approve_best_price'] = $this->language->get('text_approve_best_price');
        $data['text_print_comparison'] = $this->language->get('text_print_comparison');
        $data['text_export_comparison'] = $this->language->get('text_export_comparison');
        $data['text_export_excel'] = $this->language->get('text_export_excel');
        $data['text_export_pdf'] = $this->language->get('text_export_pdf');
        $data['text_comparison_title'] = $this->language->get('text_comparison_title');
        $data['text_create_purchase_order'] = $this->language->get('text_create_purchase_order');
        $data['text_no_quotations'] = $this->language->get('text_no_quotations');
        
        $data['button_close'] = $this->language->get('button_close');
        $data['button_export'] = $this->language->get('button_export');
    }
}