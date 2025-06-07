<?php
class ControllerPurchaseQuotationComparison extends Controller {
    private $error = array();
    
    public function __construct($registry) {
        parent::__construct($registry);
        $this->load->model('purchase/quotation');
        $this->load->model('purchase/requisition');
        $this->load->language('purchase/quotation');
    }
    
    /**
     * عرض صفحة مقارنة عروض الأسعار
     */
    public function index() {
        // التحقق من صلاحيات المستخدم
        if (!$this->user->hasKey('purchase_quotation_compare')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $this->document->setTitle($this->language->get('text_quotation_comparison'));
        
        // جلب معرفات عروض الأسعار المراد مقارنتها
        $quotation_ids = [];
        if (isset($this->request->get['quotation_ids']) && is_array($this->request->get['quotation_ids'])) {
            $quotation_ids = array_map('intval', $this->request->get['quotation_ids']);
        } elseif (isset($this->request->get['quotation_id'])) {
            $quotation_ids[] = (int)$this->request->get['quotation_id'];
        }
        
        // التحقق من وجود عروض أسعار للمقارنة
        if (empty($quotation_ids)) {
            $this->session->data['error_warning'] = $this->language->get('error_no_quotations_selected');
            $this->response->redirect($this->url->link('purchase/quotation', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        // جلب بيانات المقارنة
        $data = $this->getComparisonData($quotation_ids);
        
        if (empty($data['quotations'])) {
            $this->session->data['error_warning'] = $this->language->get('error_no_quotations');
            $this->response->redirect($this->url->link('purchase/quotation', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        // إضافة بيانات اللغة
        $data['text_quotation_comparison'] = $this->language->get('text_quotation_comparison');
        $data['text_requisition_info'] = $this->language->get('text_requisition_info');
        $data['text_requisition_number'] = $this->language->get('text_requisition_number');
        $data['text_date_required'] = $this->language->get('text_date_required');
        $data['text_branch'] = $this->language->get('text_branch');
        $data['text_comparison_summary'] = $this->language->get('text_comparison_summary');
        $data['text_total_quotations'] = $this->language->get('text_total_quotations');
        $data['text_best_price_supplier'] = $this->language->get('text_best_price_supplier');
        $data['text_price_difference'] = $this->language->get('text_price_difference');
        $data['text_comparison_criteria'] = $this->language->get('text_comparison_criteria');
        $data['text_quotation_number'] = $this->language->get('text_quotation_number');
        $data['text_quotation_date'] = $this->language->get('text_quotation_date');
        $data['text_validity_date'] = $this->language->get('text_validity_date');
        $data['text_total_amount'] = $this->language->get('text_total_amount');
        $data['text_payment_terms'] = $this->language->get('text_payment_terms');
        $data['text_delivery_terms'] = $this->language->get('text_delivery_terms');
        $data['text_tax_included'] = $this->language->get('text_tax_included');
        $data['text_tax_rate'] = $this->language->get('text_tax_rate');
        $data['text_supplier_rating'] = $this->language->get('text_supplier_rating');
        $data['text_on_time_delivery'] = $this->language->get('text_on_time_delivery');
        $data['text_no_data'] = $this->language->get('text_no_data');
        $data['text_product_comparison'] = $this->language->get('text_product_comparison');
        $data['text_product'] = $this->language->get('text_product');
        $data['text_quantity'] = $this->language->get('text_quantity');
        $data['text_not_quoted'] = $this->language->get('text_not_quoted');
        $data['text_analysis_recommendations'] = $this->language->get('text_analysis_recommendations');
        $data['text_best_overall_value'] = $this->language->get('text_best_overall_value');
        $data['text_payment_terms_comparison'] = $this->language->get('text_payment_terms_comparison');
        $data['text_delivery_terms_comparison'] = $this->language->get('text_delivery_terms_comparison');
        $data['text_best_value_recommendation'] = $this->language->get('text_best_value_recommendation');
        $data['text_best_value_explanation'] = $this->language->get('text_best_value_explanation');
        $data['text_approve_best_price'] = $this->language->get('text_approve_best_price');
        $data['text_approve_best_value'] = $this->language->get('text_approve_best_value');
        $data['text_export_excel'] = $this->language->get('text_export_excel');
        $data['text_export_pdf'] = $this->language->get('text_export_pdf');
        $data['text_no_quotations_to_compare'] = $this->language->get('text_no_quotations_to_compare');
        $data['text_yes'] = $this->language->get('text_yes');
        $data['text_no'] = $this->language->get('text_no');
        $data['text_expired'] = $this->language->get('text_expired');
        $data['text_confirm_approve_quotation'] = $this->language->get('text_confirm_approve_quotation');
        
        $data['button_close'] = $this->language->get('button_close');
        
        // إضافة معرفات عروض الأسعار للاستخدام في التصدير
        $data['quotation_ids'] = $quotation_ids;
        $data['user_token'] = $this->session->data['user_token'];
        
        // إضافة روابط التصدير
        $data['link_export_excel'] = $this->url->link('purchase/quotation_comparison/exportComparison', 'user_token=' . $this->session->data['user_token'] . '&format=excel&' . http_build_query(['quotation_ids' => $quotation_ids]), true);
        $data['link_export_pdf'] = $this->url->link('purchase/quotation_comparison/exportComparison', 'user_token=' . $this->session->data['user_token'] . '&format=pdf&' . http_build_query(['quotation_ids' => $quotation_ids]), true);
        
        // تحميل القالب
        $this->response->setOutput($this->load->view('purchase/quotation_comparison', $data));
    }
    
    /**
     * جلب بيانات المقارنة لعروض الأسعار
     * 
     * @param array $quotation_ids معرفات عروض الأسعار
     * @return array بيانات المقارنة
     */
    protected function getComparisonData($quotation_ids) {
        $data = [
            'quotations' => [],
            'products' => [],
            'requisition' => null,
            'best_price_quotation' => null,
            'best_value_quotation' => null,
            'price_difference_percentage' => 0
        ];
        
        // جلب عروض الأسعار
        $quotations = [];
        foreach ($quotation_ids as $quotation_id) {
            $quotation_info = $this->model_purchase_quotation->getQuotation($quotation_id);
            if ($quotation_info) {
                // جلب بيانات المورد
                $supplier_info = $this->model_purchase_quotation->getSupplier($quotation_info['supplier_id']);
                $supplier_stats = $this->model_purchase_quotation->getSupplierStats($quotation_info['supplier_id']);
                
                // جلب عناصر عرض السعر
                $items = $this->model_purchase_quotation->getQuotationItems($quotation_id);
                
                // تنسيق البيانات
                $quotation = [
                    'quotation_id' => $quotation_info['quotation_id'],
                    'quotation_number' => $quotation_info['quotation_number'],
                    'requisition_id' => $quotation_info['requisition_id'],
                    'supplier_id' => $quotation_info['supplier_id'],
                    'supplier_name' => $supplier_info ? $supplier_info['firstname'] . ' ' . $supplier_info['lastname'] : $quotation_info['supplier_name'],
                    'currency_id' => $quotation_info['currency_id'],
                    'currency_code' => $quotation_info['currency_code'],
                    'exchange_rate' => $quotation_info['exchange_rate'],
                    'subtotal' => $quotation_info['subtotal'],
                    'tax_amount' => $quotation_info['tax_amount'],
                    'discount_amount' => $quotation_info['discount_amount'],
                    'total_amount' => $quotation_info['total_amount'],
                    'tax_included' => $quotation_info['tax_included'],
                    'tax_rate' => $quotation_info['tax_rate'],
                    'status' => $quotation_info['status'],
                    'validity_date' => date($this->language->get('date_format_short'), strtotime($quotation_info['validity_date'])),
                    'is_expired' => strtotime($quotation_info['validity_date']) < time(),
                    'payment_terms' => $quotation_info['payment_terms'],
                    'delivery_terms' => $quotation_info['delivery_terms'],
                    'notes' => $quotation_info['notes'],
                    'created_at' => date($this->language->get('date_format_short'), strtotime($quotation_info['created_at'])),
                    'supplier_stats' => $supplier_stats,
                    'items' => []
                ];
                
                // إضافة عناصر عرض السعر
                foreach ($items as $item) {
                    $quotation['items'][$item['product_id']] = $item;
                    
                    // إضافة المنتج إلى قائمة المنتجات إذا لم يكن موجوداً
                    if (!isset($data['products'][$item['product_id']])) {
                        $data['products'][$item['product_id']] = [
                            'product_id' => $item['product_id'],
                            'name' => $item['product_name'],
                            'sku' => $item['product_code'],
                            'quantity' => $item['quantity'],
                            'unit_name' => $item['unit_name'],
                            'lowest_price' => $item['unit_price'],
                            'lowest_price_quotation_id' => $quotation_id
                        ];
                    } else {
                        // تحديث أقل سعر إذا كان السعر الحالي أقل
                        if ($item['unit_price'] < $data['products'][$item['product_id']]['lowest_price']) {
                            $data['products'][$item['product_id']]['lowest_price'] = $item['unit_price'];
                            $data['products'][$item['product_id']]['lowest_price_quotation_id'] = $quotation_id;
                        }
                    }
                }
                
                $quotations[] = $quotation;
            }
        }
        
        // ترتيب عروض الأسعار حسب السعر الإجمالي
        usort($quotations, function($a, $b) {
            return $a['total_amount'] - $b['total_amount'];
        });
        
        $data['quotations'] = $quotations;
        
        // تحديد عرض السعر الأفضل سعراً
        if (!empty($quotations)) {
            $data['best_price_quotation'] = $quotations[0];
            
            // حساب نسبة الفرق في السعر بين أفضل عرض وأسوأ عرض
            if (count($quotations) > 1) {
                $best_price = $quotations[0]['total_amount'];
                $worst_price = $quotations[count($quotations) - 1]['total_amount'];
                
                if ($best_price > 0) {
                    $data['price_difference_percentage'] = round((($worst_price - $best_price) / $best_price) * 100, 2);
                }
            }
            
            // تحديد عرض السعر الأفضل قيمة (يمكن تعديل المنطق حسب متطلبات العمل)
            // في هذا المثال، نستخدم تقييم المورد وشروط الدفع والتسليم لتحديد أفضل قيمة
            $best_value_score = 0;
            foreach ($quotations as $quotation) {
                $score = 0;
                
                // إعطاء نقاط للسعر (أقل سعر = أعلى نقاط)
                $price_score = 50 * ($quotations[0]['total_amount'] / max(0.01, $quotation['total_amount']));
                $score += $price_score;
                
                // إعطاء نقاط لتقييم المورد
                if (isset($quotation['supplier_stats'])) {
                    $score += $quotation['supplier_stats']['quality_rating'] * 5; // 0-25 نقطة
                    $score += $quotation['supplier_stats']['on_time_delivery'] / 4; // 0-25 نقطة
                }
                
                // تحديث أفضل قيمة إذا كانت النتيجة أعلى
                if ($score > $best_value_score) {
                    $best_value_score = $score;
                    $data['best_value_quotation'] = $quotation;
                }
            }
        }
        
        // جلب بيانات طلب الشراء
        if (!empty($quotations) && $quotations[0]['requisition_id']) {
            $data['requisition'] = $this->model_purchase_requisition->getRequisition($quotations[0]['requisition_id']);
        }
        
        return $data;
    }
    
    /**
     * الموافقة على عرض السعر وإنشاء أمر شراء
     */
    public function approveQuotation() {
        // التحقق من صلاحيات المستخدم
        if (!$this->user->hasKey('purchase_quotation_approve')) {
            $json['error'] = $this->language->get('error_permission');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }
        
        $json = [];
        
        if (isset($this->request->post['quotation_id'])) {
            $quotation_id = (int)$this->request->post['quotation_id'];
            $approval_type = isset($this->request->post['approval_type']) ? $this->request->post['approval_type'] : 'best_price';
            
            // جلب بيانات عرض السعر
            $quotation_info = $this->model_purchase_quotation->getQuotation($quotation_id);
            
            if ($quotation_info) {
                // التحقق من حالة عرض السعر
                if ($quotation_info['status'] == 'approved') {
                    $json['error'] = $this->language->get('error_already_approved');
                } elseif ($quotation_info['status'] == 'rejected') {
                    $json['error'] = $this->language->get('error_already_rejected');
                } elseif (strtotime($quotation_info['validity_date']) < time()) {
                    $json['error'] = $this->language->get('error_expired_quotation');
                } else {
                    // تحديث حالة عرض السعر إلى معتمد
                    $this->model_purchase_quotation->updateQuotationStatus($quotation_id, 'approved', $this->user->getId());
                    
                    // إنشاء أمر شراء من عرض السعر
                    $po_data = [
                        'quotation_id' => $quotation_id,
                        'requisition_id' => $quotation_info['requisition_id'],
                        'supplier_id' => $quotation_info['supplier_id'],
                        'currency_id' => $quotation_info['currency_id'],
                        'exchange_rate' => $quotation_info['exchange_rate'],
                        'order_date' => date('Y-m-d'),
                        'expected_delivery_date' => null,
                        'payment_terms' => $quotation_info['payment_terms'],
                        'delivery_terms' => $quotation_info['delivery_terms'],
                        'notes' => $this->language->get('text_auto_generated_from_quotation') . ' #' . $quotation_info['quotation_number'],
                        'tax_included' => $quotation_info['tax_included'],
                        'tax_rate' => $quotation_info['tax_rate'],
                        'subtotal' => $quotation_info['subtotal'],
                        'discount_type' => $quotation_info['discount_type'],
                        'has_discount' => $quotation_info['has_discount'],
                        'discount_value' => $quotation_info['discount_value'],
                        'discount_amount' => $quotation_info['discount_amount'],
                        'tax_amount' => $quotation_info['tax_amount'],
                        'total_amount' => $quotation_info['total_amount'],
                        'status' => 'pending_approval',
                        'reference_type' => 'quotation',
                        'reference_id' => $quotation_id,
                        'source_type' => 'quotation_comparison',
                        'source_id' => 0,
                        'user_id' => $this->user->getId(),
                        'items' => []
                    ];
                    
                    // جلب بنود عرض السعر
                    $quotation_items = $this->model_purchase_quotation->getQuotationItems($quotation_id);
                    
                    foreach ($quotation_items as $item) {
                        $po_data['items'][] = [
                            'product_id' => $item['product_id'],
                            'product_name' => $item['product_name'],
                            'product_code' => $item['product_code'],
                            'quantity' => $item['quantity'],
                            'unit_id' => $item['unit_id'],
                            'unit_name' => $item['unit_name'],
                            'unit_price' => $item['unit_price'],
                            'tax_rate' => $item['tax_rate'],
                            'tax_amount' => $item['tax_amount'],
                            'discount_type' => $item['discount_type'],
                            'discount_value' => $item['discount_value'],
                            'discount_amount' => $item['discount_amount'],
                            'line_total' => $item['line_total'],
                            'notes' => $item['notes']
                        ];
                    }
                    
                    // إضافة أمر الشراء
                    $this->load->model('purchase/order');
                    $po_id = $this->model_purchase_order->addOrder($po_data);
                    
                    if ($po_id) {
                        // إضافة سجل نشاط
                        $this->user->logActivity('approve', 'purchase', 'تمت الموافقة على عرض السعر #' . $quotation_info['quotation_number'] . ' وإنشاء أمر شراء', 'quotation', $quotation_id);
                        
                        // إرسال إشعارات
                        $this->sendApprovalNotifications($quotation_info, $po_id, $approval_type);
                        
                        $json['success'] = $this->language->get('text_quotation_approved');
                        $json['po_id'] = $po_id;
                        $json['redirect'] = $this->url->link('purchase/order/edit', 'user_token=' . $this->session->data['user_token'] . '&po_id=' . $po_id, true);
                    } else {
                        $json['error'] = $this->language->get('error_creating_po');
                    }
                }
            } else {
                $json['error'] = $this->language->get('error_quotation_not_found');
            }
        } else {
            $json['error'] = $this->language->get('error_quotation_required');
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    /**
     * إرسال إشعارات الموافقة على عرض السعر
     * 
     * @param array $quotation_info معلومات عرض السعر
     * @param int $po_id معرف أمر الشراء
     * @param string $approval_type نوع الموافقة (best_price أو best_value)
     */
    protected function sendApprovalNotifications($quotation_info, $po_id, $approval_type) {
        $this->load->model('tool/notification');
        $this->load->model('user/user');
        
        // جلب مستخدمي قسم المشتريات الذين لديهم صلاحية إضافة أوامر شراء
        $purchase_managers = $this->model_user_user->getUsersByPermission('purchase_order_add');
        
        // إنشاء رابط أمر الشراء
        $po_link = $this->url->link('purchase/order/edit', 'user_token=' . $this->session->data['user_token'] . '&po_id=' . $po_id, true);
        
        // تحديد نص الإشعار بناءً على نوع الموافقة
        $approval_text = ($approval_type == 'best_value') ? 
            $this->language->get('text_approved_best_value') : 
            $this->language->get('text_approved_best_price');
        
        // إنشاء بيانات الإشعار
        $notification_data = [
            'title' => $this->language->get('text_quotation_approved_notification_title'),
            'content' => sprintf($this->language->get('text_quotation_approved_notification_content'), 
                $quotation_info['quotation_number'], 
                $quotation_info['supplier_name'],
                $approval_text,
                $this->user->getUserName()),
            'type' => 'purchase_order',
            'priority' => 'normal',
            'link' => $po_link,
            'user_ids' => array_column($purchase_managers, 'user_id')
        ];
        
        // إضافة الإشعار
        $this->model_tool_notification->addNotification($notification_data);
        
        // إرسال إشعار للمورد إذا كان مسجلاً في النظام
        if ($quotation_info['supplier_id'] > 0) {
            // يمكن إضافة كود لإرسال إشعار للمورد هنا
            // على سبيل المثال، إرسال بريد إلكتروني أو إشعار داخل النظام
        }
    }
    
    /**
     * تصدير مقارنة عروض الأسعار إلى Excel
     */
    public function exportComparison() {
        if (!$this->user->hasKey('purchase_quotation_export')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $quotation_ids = [];
        if (isset($this->request->get['quotation_ids']) && is_array($this->request->get['quotation_ids'])) {
            $quotation_ids = array_map('intval', $this->request->get['quotation_ids']);
        }
        
        if (empty($quotation_ids)) {
            $this->session->data['error_warning'] = $this->language->get('error_no_quotations_selected');
            $this->response->redirect($this->url->link('purchase/quotation', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $format = isset($this->request->get['format']) ? $this->request->get['format'] : 'excel';
        
        // جلب بيانات المقارنة
        $data = $this->getComparisonData($quotation_ids);
        
        if (empty($data['quotations'])) {
            $this->session->data['error_warning'] = $this->language->get('error_no_quotations');
            $this->response->redirect($this->url->link('purchase/quotation', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        if ($format == 'excel') {
            $this->exportComparisonExcel($data);
        } else {
            $this->exportComparisonPdf($data);
        }
    }
    
    /**
     * تصدير مقارنة عروض الأسعار إلى Excel
     * 
     * @param array $data بيانات المقارنة
     */
    protected function exportComparisonExcel($data) {
        // استدعاء مكتبة PhpSpreadsheet
        require_once(DIR_SYSTEM . 'library/phpspreadsheet/vendor/autoload.php');
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setRTL(true); // تعيين اتجاه الصفحة من اليمين إلى اليسار
        
        // إعداد عنوان الملف
        $sheet->setTitle($this->language->get('text_quotation_comparison'));
        
        // إضافة ترويسة الشركة
        $sheet->setCellValue('A1', $this->config->get('config_name'));
        $sheet->setCellValue('A2', $this->language->get('text_quotation_comparison'));
        $sheet->setCellValue('A3', date($this->language->get('date_format_short')));
        
        // تنسيق الترويسة
        $sheet->getStyle('A1:A3')->getFont()->setBold(true);
        $sheet->getStyle('A1')->getFont()->setSize(14);
        
        // إضافة معلومات طلب الشراء إذا كانت متوفرة
        $row = 5;
        if (isset($data['requisition']) && $data['requisition']) {
            $sheet->setCellValue('A' . $row, $this->language->get('text_requisition_info'));
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;
            
            $sheet->setCellValue('A' . $row, $this->language->get('text_requisition_number') . ':');
            $sheet->setCellValue('B' . $row, $data['requisition']['requisition_number']);
            $row++;
            
            $sheet->setCellValue('A' . $row, $this->language->get('text_date_required') . ':');
            $sheet->setCellValue('B' . $row, $data['requisition']['date_required']);
            $row++;
            
            if (isset($data['requisition']['branch_name'])) {
                $sheet->setCellValue('A' . $row, $this->language->get('text_branch') . ':');
                $sheet->setCellValue('B' . $row, $data['requisition']['branch_name']);
                $row++;
            }
            
            $row++; // إضافة سطر فارغ
        }
        
        // إضافة ملخص المقارنة
        $sheet->setCellValue('A' . $row, $this->language->get('text_comparison_summary'));
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        
        $sheet->setCellValue('A' . $row, $this->language->get('text_total_quotations') . ':');
        $sheet->setCellValue('B' . $row, count($data['quotations']));
        $row++;
        
        if (isset($data['best_price_quotation'])) {
            $sheet->setCellValue('A' . $row, $this->language->get('text_best_price_supplier') . ':');
            $sheet->setCellValue('B' . $row, $data['best_price_quotation']['supplier_name']);
            $row++;
            
            $sheet->setCellValue('A' . $row, $this->language->get('text_price_difference') . ':');
            $sheet->setCellValue('B' . $row, $data['price_difference_percentage'] . '%');
            $row++;
        }
        
        $row += 2; // إضافة سطرين فارغين
        
        // إضافة معايير المقارنة
        $sheet->setCellValue('A' . $row, $this->language->get('text_comparison_criteria'));
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        
        // إضافة ترويسة الجدول
        $columns = [
            'A' => $this->language->get('text_criteria'),
        ];
        
        $col = 'B';
        foreach ($data['quotations'] as $quotation) {
            $columns[$col] = $quotation['supplier_name'];
            $col++;
        }
        
        foreach ($columns as $column => $value) {
            $sheet->setCellValue($column . $row, $value);
            $sheet->getStyle($column . $row)->getFont()->setBold(true);
            $sheet->getStyle($column . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            $sheet->getStyle($column . $row)->getFill()->getStartColor()->setRGB('DDDDDD');
        }
        $row++;
        
        // إضافة معايير المقارنة
        $criteria = [
            $this->language->get('text_quotation_number'),
            $this->language->get('text_quotation_date'),
            $this->language->get('text_validity_date'),
            $this->language->get('text_total_amount'),
            $this->language->get('text_payment_terms'),
            $this->language->get('text_delivery_terms'),
            $this->language->get('text_tax_included'),
            $this->language->get('text_tax_rate'),
            $this->language->get('text_supplier_rating'),
            $this->language->get('text_on_time_delivery')
        ];
        
        foreach ($criteria as $criterion) {
            $sheet->setCellValue('A' . $row, $criterion);
            
            $col = 'B';
            foreach ($data['quotations'] as $quotation) {
                switch ($criterion) {
                    case $this->language->get('text_quotation_number'):
                        $value = $quotation['quotation_number'];
                        break;
                    case $this->language->get('text_quotation_date'):
                        $value = $quotation['created_at'];
                        break;
                    case $this->language->get('text_validity_date'):
                        $value = $quotation['validity_date'];
                        if ($quotation['is_expired']) {
                            $value .= ' (' . $this->language->get('text_expired') . ')';
                        }
                        break;
                    case $this->language->get('text_total_amount'):
                        $value = $quotation['total_amount'] . ' ' . $quotation['currency_code'];
                        break;
                    case $this->language->get('text_payment_terms'):
                        $value = $quotation['payment_terms'] ?: $this->language->get('text_no_data');
                        break;
                    case $this->language->get('text_delivery_terms'):
                        $value = $quotation['delivery_terms'] ?: $this->language->get('text_no_data');
                        break;
                    case $this->language->get('text_tax_included'):
                        $value = $quotation['tax_included'] ? $this->language->get('text_yes') : $this->language->get('text_no');
                        break;
                    case $this->language->get('text_tax_rate'):
                        $value = $quotation['tax_rate'] . '%';
                        break;
                    case $this->language->get('text_supplier_rating'):
                        $value = isset($quotation['supplier_stats']['quality_rating']) ? $quotation['supplier_stats']['quality_rating'] . '/5' : $this->language->get('text_no_data');
                        break;
                    case $this->language->get('text_on_time_delivery'):
                        $value = isset($quotation['supplier_stats']['on_time_delivery']) ? $quotation['supplier_stats']['on_time_delivery'] . '%' : $this->language->get('text_no_data');
                        break;
                    default:
                        $value = $this->language->get('text_no_data');
                }
                
                $sheet->setCellValue($col . $row, $value);
                $col++;
            }
            
            $row++;
        }
        
        $row += 2; // إضافة سطرين فارغين
        
        // إضافة مقارنة المنتجات
        $sheet->setCellValue('A' . $row, $this->language->get('text_product_comparison'));
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        
        // إضافة ترويسة جدول المنتجات
        $columns = [
            'A' => $this->language->get('text_product'),
            'B' => $this->language->get('text_quantity'),
        ];
        
        $col = 'C';
        foreach ($data['quotations'] as $quotation) {
            $columns[$col] = $quotation['supplier_name'];
            $col++;
        }
        
        foreach ($columns as $column => $value) {
            $sheet->setCellValue($column . $row, $value);
            $sheet->getStyle($column . $row)->getFont()->setBold(true);
            $sheet->getStyle($column . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            $sheet->getStyle($column . $row)->getFill()->getStartColor()->setRGB('DDDDDD');
        }
        $row++;
        
        // إضافة بيانات المنتجات
        foreach ($data['products'] as $product) {
            $sheet->setCellValue('A' . $row, $product['name'] . ' (' . $product['sku'] . ')');
            $sheet->setCellValue('B' . $row, $product['quantity'] . ' ' . $product['unit_name']);
            
            $col = 'C';
            foreach ($data['quotations'] as $quotation) {
                if (isset($quotation['items'][$product['product_id']])) {
                    $item = $quotation['items'][$product['product_id']];
                    $value = $item['unit_price'] . ' ' . $quotation['currency_code'];
                    
                    // تمييز أقل سعر
                    if ($product['lowest_price_quotation_id'] == $quotation['quotation_id']) {
                        $sheet->getStyle($col . $row)->getFont()->setBold(true);
                        $sheet->getStyle($col . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                        $sheet->getStyle($col . $row)->getFill()->getStartColor()->setRGB('EEFFEE');
                    }
                } else {
                    $value = $this->language->get('text_not_quoted');
                }
                
                $sheet->setCellValue($col . $row, $value);
                $col++;
            }
            
            $row++;
        }
        
        // تعديل عرض الأعمدة ليناسب المحتوى
        foreach (range('A', $col) as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }
        
        // إنشاء ملف Excel وتنزيله
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        
        $filename = 'quotation_comparison_' . date('Y-m-d_His') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
    }
    
    /**
     * تصدير مقارنة عروض الأسعار إلى PDF
     * 
     * @param array $data بيانات المقارنة
     */
    protected function exportComparisonPdf($data) {
        // التحقق من وجود مكتبة mPDF
        if (!class_exists('\Mpdf\Mpdf')) {
            require_once(DIR_SYSTEM . 'library/mpdf/vendor/autoload.php');
        }
        
        // إنشاء كائن mPDF مع دعم اللغة العربية
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
            'margin_header' => 0,
            'margin_footer' => 0,
            'tempDir' => DIR_UPLOAD . 'temp',
            'default_font_size' => 10,
            'default_font' => 'xbriyaz'
        ]);
        
        // تعيين معلومات المستند
        $mpdf->SetCreator($this->config->get('config_name'));
        $mpdf->SetTitle($this->language->get('text_quotation_comparison'));
        $mpdf->SetAuthor($this->user->getUserName());
        
        // إنشاء محتوى HTML للتقرير
        $html = $this->generateComparisonPdfHtml($data);
        
        // إضافة المحتوى إلى المستند
        $mpdf->WriteHTML($html);
        
        // إخراج المستند
        $mpdf->Output('quotation_comparison_' . date('Y-m-d_His') . '.pdf', 'D');
    }
    
    /**
     * إنشاء محتوى HTML لتقرير مقارنة عروض الأسعار
     * 
     * @param array $data بيانات المقارنة
     * @return string محتوى HTML
     */
    protected function generateComparisonPdfHtml($data) {
        // تحميل قالب HTML للتقرير
        $this->load->model('setting/setting');
        
        // إنشاء محتوى HTML
        $html = '<!DOCTYPE html>
<html dir="rtl">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>' . $this->language->get('text_quotation_comparison') . '</title>
    <style>
        body { font-family: xbriyaz, sans-serif; font-size: 10pt; direction: rtl; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 18pt; }
        .header p { margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 5px; text-align: right; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .section-title { font-size: 14pt; margin: 20px 0 10px 0; }
        .best-price { background-color: #e6ffe6; font-weight: bold; }
        .best-value { background-color: #e6f2ff; font-weight: bold; }
        .expired { color: #ff0000; }
        .recommendation { background-color: #fffde6; padding: 10px; border: 1px solid #e6e6e6; margin: 20px 0; }
        .footer { text-align: center; margin-top: 20px; font-size: 9pt; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>' . $this->config->get('config_name') . '</h1>
        <p>' . $this->language->get('text_quotation_comparison') . '</p>
        <p>' . date($this->language->get('date_format_short')) . '</p>
    </div>'
        
        // إضافة معلومات طلب الشراء إذا كانت متوفرة
        if (isset($data['requisition']) && $data['requisition']) {
            $html .= '<div class="section-title">' . $this->language->get('text_requisition_info') . '</div>
            <table>
                <tr>
                    <th width="30%">' . $this->language->get('text_requisition_number') . '</th>
                    <td>' . $data['requisition']['requisition_number'] . '</td>
                </tr>
                <tr>
                    <th>' . $this->language->get('text_date_required') . '</th>
                    <td>' . $data['requisition']['date_required'] . '</td>
                </tr>';
            
            if (isset($data['requisition']['branch_name'])) {
                $html .= '<tr>
                    <th>' . $this->language->get('text_branch') . '</th>
                    <td>' . $data['requisition']['branch_name'] . '</td>
                </tr>';
            }
            
            $html .= '</table>';
        }
        
        // إضافة ملخص المقارنة
        $html .= '<div class="section-title">' . $this->language->get('text_comparison_summary') . '</div>
        <table>
            <tr>
                <th width="30%">' . $this->language->get('text_total_quotations') . '</th>
                <td>' . count($data['quotations']) . '</td>
            </tr>';
            
        if (isset($data['best_price_quotation'])) {
            $html .= '<tr>
                <th>' . $this->language->get('text_best_price_supplier') . '</th>
                <td>' . $data['best_price_quotation']['supplier_name'] . '</td>
            </tr>
            <tr>
                <th>' . $this->language->get('text_price_difference') . '</th>
                <td>' . $data['price_difference_percentage'] . '%</td>
            </tr>';
        }
        
        if (isset($data['best_value_quotation'])) {
            $html .= '<tr>
                <th>' . $this->language->get('text_best_overall_value') . '</th>
                <td>' . $data['best_value_quotation']['supplier_name'] . '</td>
            </tr>';
        }
        
        $html .= '</table>';
        
        // إضافة جدول مقارنة المعايير
        $html .= '<div class="section-title">' . $this->language->get('text_comparison_criteria') . '</div>
        <table>
            <tr>
                <th>' . $this->language->get('text_criteria') . '</th>';
        
        foreach ($data['quotations'] as $quotation) {
            $html .= '<th>' . $quotation['supplier_name'] . '</th>';
        }
        
        $html .= '</tr>';
        
        // إضافة معايير المقارنة
        $criteria = [
            'quotation_number' => $this->language->get('text_quotation_number'),
            'created_at' => $this->language->get('text_quotation_date'),
            'validity_date' => $this->language->get('text_validity_date'),
            'total_amount' => $this->language->get('text_total_amount'),
            'payment_terms' => $this->language->get('text_payment_terms'),
            'delivery_terms' => $this->language->get('text_delivery_terms'),
            'tax_included' => $this->language->get('text_tax_included'),
            'tax_rate' => $this->language->get('text_tax_rate'),
            'supplier_rating' => $this->language->get('text_supplier_rating'),
            'on_time_delivery' => $this->language->get('text_on_time_delivery')
        ];
        
        foreach ($criteria as $key => $criterion) {
            $html .= '<tr>
                <td>' . $criterion . '</td>';
            
            foreach ($data['quotations'] as $quotation) {
                $value = '';
                $class = '';
                
                switch ($key) {
                    case 'quotation_number':
                        $value = $quotation['quotation_number'];
                        break;
                    case 'created_at':
                        $value = $quotation['created_at'];
                        break;
                    case 'validity_date':
                        $value = $quotation['validity_date'];
                        if ($quotation['is_expired']) {
                            $value .= ' <span class="expired">(' . $this->language->get('text_expired') . ')</span>';
                        }
                        break;
                    case 'total_amount':
                        $value = $quotation['total_amount'] . ' ' . $quotation['currency_code'];
                        if (isset($data['best_price_quotation']) && $quotation['quotation_id'] == $data['best_price_quotation']['quotation_id']) {
                            $class = ' class="best-price"';
                        }
                        break;
                    case 'payment_terms':
                        $value = $quotation['payment_terms'] ?: $this->language->get('text_no_data');
                        break;
                    case 'delivery_terms':
                        $value = $quotation['delivery_terms'] ?: $this->language->get('text_no_data');
                        break;
                    case 'tax_included':
                        $value = $quotation['tax_included'] ? $this->language->get('text_yes') : $this->language->get('text_no');
                        break;
                    case 'tax_rate':
                        $value = $quotation['tax_rate'] . '%';
                        break;
                    case 'supplier_rating':
                        $value = isset($quotation['supplier_stats']['quality_rating']) ? $quotation['supplier_stats']['quality_rating'] . '/5' : $this->language->get('text_no_data');
                        if (isset($data['best_value_quotation']) && $quotation['quotation_id'] == $data['best_value_quotation']['quotation_id']) {
                            $class = ' class="best-value"';
                        }
                        break;
                    case 'on_time_delivery':
                        $value = isset($quotation['supplier_stats']['on_time_delivery']) ? $quotation['supplier_stats']['on_time_delivery'] . '%' : $this->language->get('text_no_data');
                        if (isset($data['best_value_quotation']) && $quotation['quotation_id'] == $data['best_value_quotation']['quotation_id']) {
                            $class = ' class="best-value"';
                        }
                        break;
                }
                
                $html .= '<td' . $class . '>' . $value . '</td>';
            }
            
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        
        // إضافة مقارنة المنتجات
        $html .= '<div class="section-title">' . $this->language->get('text_product_comparison') . '</div>
        <table>
            <tr>
                <th>' . $this->language->get('text_product') . '</th>
                <th>' . $this->language->get('text_quantity') . '</th>';
        
        foreach ($data['quotations'] as $quotation) {
            $html .= '<th>' . $quotation['supplier_name'] . '</th>';
        }
        
        $html .= '</tr>';
        
        // إضافة بيانات المنتجات
        foreach ($data['products'] as $product) {
            $html .= '<tr>
                <td>' . $product['name'] . ' (' . $product['sku'] . ')</td>
                <td>' . $product['quantity'] . ' ' . $product['unit_name'] . '</td>';
            
            foreach ($data['quotations'] as $quotation) {
                if (isset($quotation['items'][$product['product_id']])) {
                    $item = $quotation['items'][$product['product_id']];
                    $class = '';
                    
                    if ($product['lowest_price_quotation_id'] == $quotation['quotation_id']) {
                        $class = ' class="best-price"';
                    }
                    
                    $html .= '<td' . $class . '>' . $item['unit_price'] . ' ' . $quotation['currency_code'] . '</td>';
                } else {
                    $html .= '<td><span class="text-muted">' . $this->language->get('text_not_quoted') . '</span></td>';
                }
            }
            
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        
        // إضافة قسم التحليل والتوصيات
        $html .= '<div class="section-title">' . $this->language->get('text_analysis_recommendations') . '</div>
        <div class="recommendation">';
        
        if (isset($data['best_price_quotation']) && isset($data['best_value_quotation'])) {
            if ($data['best_price_quotation']['quotation_id'] == $data['best_value_quotation']['quotation_id']) {
                $html .= '<p><strong>' . $this->language->get('text_best_value_recommendation') . ':</strong> ' . 
                    sprintf($this->language->get('text_best_overall_recommendation'), 
                    $data['best_price_quotation']['supplier_name'], 
                    $data['best_price_quotation']['total_amount'] . ' ' . $data['best_price_quotation']['currency_code']) . '</p>';
            } else {
                $html .= '<p><strong>' . $this->language->get('text_best_price_recommendation') . ':</strong> ' . 
                    sprintf($this->language->get('text_best_price_recommendation_text'), 
                    $data['best_price_quotation']['supplier_name'], 
                    $data['best_price_quotation']['total_amount'] . ' ' . $data['best_price_quotation']['currency_code']) . '</p>';
                
                $html .= '<p><strong>' . $this->language->get('text_best_value_recommendation') . ':</strong> ' . 
                    sprintf($this->language->get('text_best_value_recommendation_text'), 
                    $data['best_value_quotation']['supplier_name'], 
                    isset($data['best_value_quotation']['supplier_stats']['quality_rating']) ? $data['best_value_quotation']['supplier_stats']['quality_rating'] . '/5' : $this->language->get('text_no_data'),
                    isset($data['best_value_quotation']['supplier_stats']['on_time_delivery']) ? $data['best_value_quotation']['supplier_stats']['on_time_delivery'] . '%' : $this->language->get('text_no_data')) . '</p>';
            }
        }
        
        $html .= '<p>' . $this->language->get('text_payment_terms_comparison') . '</p>';
        $html .= '<p>' . $this->language->get('text_delivery_terms_comparison') . '</p>';
        $html .= '</div>';
        
        $html .= '<div class="footer">' . sprintf($this->language->get('text_generated_by'), date('Y-m-d H:i:s'), $this->user->getUserName()) . '</div>';
        
        $html .= '</body>
</html>';
        
        return $html;
            <tr>
                <th width="30%">' . $this->language->get('text_total_quotations') . '</th>
                <td>' . count($data['quotations']) . '</td>
            </tr>';
        
        if (isset($data['best_price_quotation'])) {
            $html .= '<tr>
                <th>' . $this->language->get('text_best_price_supplier') . '</th>
                <td>' . $data['best_price_quotation']['supplier_name'] . '</td>
            </tr>
            <tr>
                <th>' . $this->language->get('text_price_difference') . '</th>
                <td>' . $data['price_difference_percentage'] . '%</td>
            </tr>';
        }
        
        $html .= '</table>';
        
        // إضافة معايير المقارنة
        $html .= '<div class="section-title">' . $this->language->get('text_comparison_criteria') . '</div>
        <table>
            <tr>
                <th>' . $this->language->get('text_criteria') . '</th>';
        
        foreach ($data['quotations'] as $quotation) {
            $html .= '<th>' . $quotation['supplier_name'] . '</th>';
        }
        
        $html .= '</tr>';
        
        // إضافة معايير المقارنة
        $criteria = [
            'quotation_number' => $this->language->get('text_quotation_number'),
            'created_at' => $this->language->get('text_quotation_date'),
            'validity_date' => $this->language->get('text_validity_date'),
            'total_amount' => $this->language->get('text_total_amount'),
            'payment_terms' => $this->language->get('text_payment_terms'),
            'delivery_terms' => $this->language->get('text_delivery_terms'),
            'tax_included' => $this->language->get('text_tax_included'),
            'tax_rate' => $this->language->get('text_tax_rate'),
            'supplier_rating' => $this->language->get('text_supplier_rating'),
            'on_time_delivery' => $this->language->get('text_on_time_delivery')
        ];
        
        foreach ($criteria as $key => $criterion) {
            $html .= '<tr>
                <th>' . $criterion . '</th>';
            
            foreach ($data['quotations'] as $quotation) {
                $html .= '<td>';
                
                switch ($key) {
                    case 'quotation_number':
                        $html .= $quotation['quotation_number'];
                        break;
                    case 'created_at':
                        $html .= $quotation['created_at'];
                        break;
                    case 'validity_date':
                        $html .= $quotation['validity_date'];
                        if ($quotation['is_expired']) {
                            $html .= ' <span class="expired">(' . $this->language->get('text_expired') . ')</span>';
                        }
                        break;
                    case 'total_amount':
                        $html .= $quotation['total_amount'] . ' ' . $quotation['currency_code'];
                        break;
                    case 'payment_terms':
                        $html .= $quotation['payment_terms'] ?: $this->language->get('text_no_data');
                        break;
                    case 'delivery_terms':
                        $html .= $quotation['delivery_terms'] ?: $this->language->get('text_no_data');
                        break;
                    case 'tax_included':
                        $html .= $quotation['tax_included'] ? $this->language->get('text_yes') : $this->language->get('text_no');
                        break;
                    case 'tax_rate':
                        $html .= $quotation['tax_rate'] . '%';
                        break;
                    case 'supplier_rating':
                        $html .= isset($quotation['supplier_stats']['quality_rating']) ? $quotation['supplier_stats']['quality_rating'] . '/5' : $this->language->get('text_no_data');
                        break;
                    case 'on_time_delivery':
                        $html .= isset($quotation['supplier_stats']['on_time_delivery']) ? $quotation['supplier_stats']['on_time_delivery'] . '%' : $this->language->get('text_no_data');
                        break;
                    default:
                        $html .= $this->language->get('text_no_data');
                }
                
                $html .= '</td>';
            }
            
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        
        // إضافة مقارنة المنتجات
        $html .= '<div class="section-title">' . $this->language->get('text_product_comparison') . '</div>
        <table>
            <tr>
                <th>' . $this->language->get('text_product') . '</th>
                <th>' . $this->language->get('text_quantity') . '</th>';
        
        foreach ($data['quotations'] as $quotation) {
            $html .= '<th>' . $quotation['supplier_name'] . '</th>';
        }
        
        $html .= '</tr>';
        
        // إضافة بيانات المنتجات
        foreach ($data['products'] as $product) {
            $html .= '<tr>
                <td>' . $product['name'] . ' (' . $product['sku'] . ')</td>
                <td>' . $product['quantity'] . ' ' . $product['unit_name'] . '</td>';
            
            foreach ($data['quotations'] as $quotation) {
                $html .= '<td';
                
                if (isset($quotation['items'][$product['product_id']])) {
                    $item = $quotation['items'][$product['product_id']];
                    $value = $item['unit_price'] . ' ' . $quotation['currency_code'];
                    
                    // تمييز أقل سعر
                    if ($product['lowest_price_quotation_id'] == $quotation['quotation_id']) {
                        $html .= ' class="best-price"';
                    }
                } else {
                    $value = $this->language->get('text_not_quoted');
                }
                
                $html .= '>' . $value . '</td>';
            }
            
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        
        // إضافة التوصيات والتحليل
        if (isset($data['best_value_quotation'])) {
            $html .= '<div class="section-title">' . $this->language->get('text_analysis_recommendations') . '</div>
            <table>
                <tr>
                    <th width="30%">' . $this->language->get('text_best_overall_value') . '</th>
                    <td>' . $data['best_value_quotation']['supplier_name'] . '</td>
                </tr>
                <tr>
                    <th>' . $this->language->get('text_best_value_explanation') . '</th>
                    <td>' . $this->language->get('text_best_value_recommendation') . '</td>
                </tr>
            </table>';
        }
        
        $html .= '</body>
</html>';
        
        return $html;
    }
    
    /**
     * الموافقة على عرض السعر
     */
    public function approveQuotation() {
        // التحقق من صلاحيات المستخدم
        if (!$this->user->hasKey('purchase_quotation_approve')) {
            $this->sendJSON(['success' => false, 'error' => $this->language->get('error_permission')]);
            return;
        }
        
        // التحقق من وجود معرف عرض السعر
        if (!isset($this->request->post['quotation_id'])) {
            $this->sendJSON(['success' => false, 'error' => $this->language->get('error_quotation_id')]);
            return;
        }
        
        $quotation_id = (int)$this->request->post['quotation_id'];
        $approval_type = isset($this->request->post['approval_type']) ? $this->request->post['approval_type'] : 'best_price';
        $notes = isset($this->request->post['notes']) ? $this->request->post['notes'] : '';
        
        // جلب معلومات عرض السعر
        $quotation_info = $this->model_purchase_quotation->getQuotation($quotation_id);
        
        if (!$quotation_info) {
            $this->sendJSON(['success' => false, 'error' => $this->language->get('error_quotation_not_found')]);
            return;
        }
        
        // التحقق من حالة عرض السعر
        if ($quotation_info['status'] == 'approved') {
            $this->sendJSON(['success' => false, 'error' => $this->language->get('error_quotation_already_approved')]);
            return;
        }
        
        if (in_array($quotation_info['status'], ['rejected', 'cancelled', 'converted'])) {
            $this->sendJSON(['success' => false, 'error' => $this->language->get('error_quotation_cannot_approve')]);
            return;
        }
        
        // تحديث حالة عرض السعر إلى معتمد
        $update_data = [
            'status' => 'approved',
            'approval_type' => $approval_type,
            'approved_by' => $this->user->getId(),
            'approved_at' => date('Y-m-d H:i:s'),
            'approval_notes' => $notes
        ];
        
        $this->model_purchase_quotation->updateQuotation($quotation_id, $update_data);
        
        // إضافة سجل النشاط
        $this->load->model('user/activity');
        $this->model_user_activity->addActivity('approve', 'purchase', sprintf($this->language->get('text_quotation_approved'), $quotation_info['quotation_number']), 'quotation', $quotation_id);
        
        // إرسال إشعار للمستخدمين المعنيين
        $this->sendApprovalNotification($quotation_info, $approval_type);
        
        // إرجاع استجابة نجاح
        $this->sendJSON([
            'success' => true,
            'message' => $this->language->get('text_quotation_approval_success')
        ]);
    }
    
    /**
     * إرسال إشعار بالموافقة على عرض السعر
     * 
     * @param array $quotation_info معلومات عرض السعر
     * @param string $approval_type نوع الموافقة (best_price أو best_value)
     */
    protected function sendApprovalNotification($quotation_info, $approval_type) {
        $this->load->model('user/user');
        $this->load->model('tool/notification');
        
        // تحديد المستخدمين الذين سيتلقون الإشعار
        $users = [];
        
        // المستخدم الذي أنشأ عرض السعر
        if ($quotation_info['user_id']) {
            $users[] = $quotation_info['user_id'];
        }
        
        // مدير المشتريات والمستخدمين الذين لديهم صلاحية تحويل عرض السعر إلى أمر شراء
        $purchase_managers = $this->model_user_user->getUsersByPermission('purchase_order_add');
        foreach ($purchase_managers as $manager) {
            if (!in_array($manager['user_id'], $users)) {
                $users[] = $manager['user_id'];
            }
        }
        
        // إنشاء نص الإشعار
        $approval_type_text = ($approval_type == 'best_value') ? 
            $this->language->get('text_best_value') : 
            $this->language->get('text_best_price');
        
        $notification_title = sprintf($this->language->get('text_quotation_approved_notification'), $quotation_info['quotation_number']);
        $notification_message = sprintf(
            $this->language->get('text_quotation_approved_notification_message'),
            $quotation_info['quotation_number'],
            $approval_type_text,
            $this->user->getUserName()
        );
        
        // إرسال الإشعار لكل مستخدم
        foreach ($users as $user_id) {
            $this->model_tool_notification->addNotification([
                'user_id' => $user_id,
                'title' => $notification_title,
                'message' => $notification_message,
                'type' => 'purchase_quotation_approved',
                'reference_id' => $quotation_info['quotation_id'],
                'reference_type' => 'quotation',
                'link' => 'purchase/quotation/edit&quotation_id=' . $quotation_info['quotation_id']
            ]);
        }
    }
    
    /**
     * إرسال استجابة JSON
     */
    protected function sendJSON($data) {
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($data));
    }
}