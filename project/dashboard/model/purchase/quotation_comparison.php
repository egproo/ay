<?php
/**
 * نظام أيم ERP: نظام إدارة موارد الشركات المتكامل للتجارة والتوزيع
 * مع دعم الجرد المستمر والمتوسط المرجح للتكلفة والفروع المتعددة
 * 
 * Model: Quotation Comparison
 * تم تحديثه لدعم الإشعارات وتصدير المقارنات وتتبع الموافقات
 */
class ModelPurchaseQuotationComparison extends Model {
    /**
     * الحصول على بيانات المقارنة بين عروض الأسعار
     * 
     * @param array $quotation_ids معرفات عروض الأسعار المراد مقارنتها
     * @return array بيانات المقارنة
     */
    public function getComparisonData($quotation_ids) {
        $this->load->model('purchase/quotation');
        $this->load->model('supplier/supplier');
        
        $data = array(
            'quotations' => array(),
            'products' => array(),
            'best_price' => array(),
            'best_value' => array(),
            'requisition_info' => array()
        );
        
        // التحقق من وجود عروض أسعار للمقارنة
        if (empty($quotation_ids)) {
            return $data;
        }
        
        // جلب بيانات عروض الأسعار
        foreach ($quotation_ids as $quotation_id) {
            $quotation_info = $this->model_purchase_quotation->getQuotation($quotation_id);
            
            if ($quotation_info) {
                // معلومات المورد
                $supplier_info = $this->model_supplier_supplier->getSupplier($quotation_info['supplier_id']);
                $supplier_name = $supplier_info ? $supplier_info['name'] : '';
                
                // إضافة معلومات عرض السعر
                $data['quotations'][$quotation_id] = array(
                    'quotation_id' => $quotation_id,
                    'quotation_number' => $quotation_info['quotation_number'],
                    'supplier_id' => $quotation_info['supplier_id'],
                    'supplier_name' => $supplier_name,
                    'date_added' => $quotation_info['date_added'],
                    'validity_date' => $quotation_info['validity_date'],
                    'total' => $quotation_info['total'],
                    'tax_total' => $quotation_info['tax_total'],
                    'grand_total' => $quotation_info['grand_total'],
                    'payment_terms' => $quotation_info['payment_terms'],
                    'delivery_terms' => $quotation_info['delivery_terms'],
                    'tax_included' => $quotation_info['tax_included'],
                    'tax_rate' => $quotation_info['tax_rate'],
                    'status' => $quotation_info['status'],
                    'products' => array(),
                    'supplier_rating' => $this->getSupplierRating($quotation_info['supplier_id']),
                    'on_time_delivery' => $this->getSupplierOnTimeDelivery($quotation_info['supplier_id'])
                );
                
                // جلب منتجات عرض السعر
                $products = $this->model_purchase_quotation->getQuotationProducts($quotation_id);
                
                foreach ($products as $product) {
                    $data['quotations'][$quotation_id]['products'][$product['product_id']] = array(
                        'product_id' => $product['product_id'],
                        'name' => $product['name'],
                        'model' => $product['model'],
                        'quantity' => $product['quantity'],
                        'price' => $product['price'],
                        'tax' => $product['tax'],
                        'total' => $product['total']
                    );
                    
                    // تجميع المنتجات لعرض مقارنة المنتجات
                    if (!isset($data['products'][$product['product_id']])) {
                        $data['products'][$product['product_id']] = array(
                            'product_id' => $product['product_id'],
                            'name' => $product['name'],
                            'model' => $product['model'],
                            'quantity' => $product['quantity'],
                            'suppliers' => array()
                        );
                    }
                    
                    $data['products'][$product['product_id']]['suppliers'][$quotation_info['supplier_id']] = array(
                        'quotation_id' => $quotation_id,
                        'supplier_id' => $quotation_info['supplier_id'],
                        'supplier_name' => $supplier_name,
                        'price' => $product['price'],
                        'tax' => $product['tax'],
                        'total' => $product['total']
                    );
                }
                
                // جلب معلومات طلب الشراء إذا كان مرتبطًا بعرض السعر
                if (!empty($quotation_info['requisition_id']) && empty($data['requisition_info'])) {
                    $this->load->model('purchase/requisition');
                    $requisition_info = $this->model_purchase_requisition->getRequisition($quotation_info['requisition_id']);
                    
                    if ($requisition_info) {
                        $data['requisition_info'] = array(
                            'requisition_id' => $requisition_info['requisition_id'],
                            'requisition_number' => $requisition_info['requisition_number'],
                            'date_required' => $requisition_info['date_required'],
                            'branch_id' => $requisition_info['branch_id'],
                            'branch_name' => $this->getBranchName($requisition_info['branch_id'])
                        );
                    }
                }
            }
        }
        
        // تحديد أفضل سعر وأفضل قيمة
        $this->calculateBestPrice($data);
        $this->calculateBestValue($data);
        
        return $data;
    }
    
    /**
     * حساب أفضل سعر بين عروض الأسعار
     * 
     * @param array &$data بيانات المقارنة
     */
    private function calculateBestPrice(&$data) {
        $best_price_quotation_id = 0;
        $best_price_total = 0;
        $second_best_price_total = 0;
        
        // البحث عن أفضل سعر
        foreach ($data['quotations'] as $quotation_id => $quotation) {
            if ($best_price_quotation_id == 0 || $quotation['grand_total'] < $best_price_total) {
                $second_best_price_total = $best_price_total;
                $best_price_total = $quotation['grand_total'];
                $best_price_quotation_id = $quotation_id;
            } elseif ($second_best_price_total == 0 || $quotation['grand_total'] < $second_best_price_total) {
                $second_best_price_total = $quotation['grand_total'];
            }
        }
        
        // حساب فرق السعر
        $price_difference = 0;
        $price_difference_percentage = 0;
        
        if ($second_best_price_total > 0 && $best_price_total > 0) {
            $price_difference = $second_best_price_total - $best_price_total;
            $price_difference_percentage = ($price_difference / $best_price_total) * 100;
        }
        
        // تعيين أفضل سعر
        if ($best_price_quotation_id > 0) {
            $data['best_price'] = array(
                'quotation_id' => $best_price_quotation_id,
                'supplier_id' => $data['quotations'][$best_price_quotation_id]['supplier_id'],
                'supplier_name' => $data['quotations'][$best_price_quotation_id]['supplier_name'],
                'total' => $best_price_total,
                'price_difference' => $price_difference,
                'price_difference_percentage' => $price_difference_percentage
            );
        }
    }
    
    /**
     * حساب أفضل قيمة بين عروض الأسعار (بناءً على معايير متعددة)
     * 
     * @param array &$data بيانات المقارنة
     */
    private function calculateBestValue(&$data) {
        $best_value_quotation_id = 0;
        $best_value_score = 0;
        
        // تعيين أوزان المعايير
        $criteria_weights = array(
            'price' => 0.4,           // 40% للسعر
            'supplier_rating' => 0.2,  // 20% لتقييم المورد
            'delivery' => 0.2,         // 20% للتسليم في الموعد
            'payment_terms' => 0.1,    // 10% لشروط الدفع
            'quality' => 0.1           // 10% لجودة المنتجات (معيار جديد)
        );
        
        // حساب درجة كل عرض سعر
        $scores = array();
        $best_price = isset($data['best_price']['total']) ? $data['best_price']['total'] : 0;
        
        foreach ($data['quotations'] as $quotation_id => $quotation) {
            // درجة السعر (أفضل سعر = 100، والباقي نسبي)
            $price_score = 0;
            if ($best_price > 0) {
                $price_score = ($best_price / $quotation['grand_total']) * 100;
            }
            
            // درجة تقييم المورد (مباشرة من التقييم)
            $supplier_rating_score = $quotation['supplier_rating'];
            
            // درجة التسليم في الموعد
            $delivery_score = $quotation['on_time_delivery'];
            
            // درجة شروط الدفع (تقدير بسيط - يمكن تحسينه)
            $payment_terms_score = $this->calculatePaymentTermsScore($quotation['payment_terms']);
            
            // درجة جودة المنتجات (معيار جديد)
            $quality_score = $this->getSupplierQualityScore($quotation['supplier_id']);
            
            // حساب الدرجة الإجمالية
            $total_score = ($price_score * $criteria_weights['price']) +
                          ($supplier_rating_score * $criteria_weights['supplier_rating']) +
                          ($delivery_score * $criteria_weights['delivery']) +
                          ($payment_terms_score * $criteria_weights['payment_terms']) +
                          ($quality_score * $criteria_weights['quality']);
            
            $scores[$quotation_id] = array(
                'total_score' => $total_score,
                'price_score' => $price_score,
                'supplier_rating_score' => $supplier_rating_score,
                'delivery_score' => $delivery_score,
                'payment_terms_score' => $payment_terms_score,
                'quality_score' => $quality_score
            );
            
            // تحديث أفضل قيمة
            if ($best_value_quotation_id == 0 || $total_score > $best_value_score) {
                $best_value_score = $total_score;
                $best_value_quotation_id = $quotation_id;
            }
        }
        
        // تعيين أفضل قيمة
        if ($best_value_quotation_id > 0) {
            $data['best_value'] = array(
                'quotation_id' => $best_value_quotation_id,
                'supplier_id' => $data['quotations'][$best_value_quotation_id]['supplier_id'],
                'supplier_name' => $data['quotations'][$best_value_quotation_id]['supplier_name'],
                'total_score' => $scores[$best_value_quotation_id]['total_score'],
                'scores' => $scores
            );
        }
    }
    
    /**
     * حساب درجة شروط الدفع
     * 
     * @param string $payment_terms شروط الدفع
     * @return float درجة شروط الدفع (0-100)
     */
    private function calculatePaymentTermsScore($payment_terms) {
        // تنفيذ بسيط - يمكن تحسينه بتحليل أكثر تفصيلاً لشروط الدفع
        $score = 50; // درجة افتراضية
        
        // البحث عن فترات الدفع في النص
        if (preg_match('/\b(\d+)\s*days?\b/i', $payment_terms, $matches)) {
            $days = (int)$matches[1];
            
            // كلما زادت فترة الدفع، كلما كان أفضل للمشتري
            if ($days <= 0) {
                $score = 0; // الدفع الفوري
            } elseif ($days <= 15) {
                $score = 25; // 1-15 يوم
            } elseif ($days <= 30) {
                $score = 50; // 16-30 يوم
            } elseif ($days <= 60) {
                $score = 75; // 31-60 يوم
            } else {
                $score = 100; // أكثر من 60 يوم
            }
        }
        
        return $score;
    }
    
    /**
     * الحصول على تقييم المورد
     * 
     * @param int $supplier_id معرف المورد
     * @return float تقييم المورد (0-100)
     */
    public function getSupplierRating($supplier_id) {
        // يمكن تنفيذ استعلام لجلب تقييم المورد من قاعدة البيانات
        // هذا تنفيذ مبسط للتوضيح
        $this->load->model('supplier/evaluation');
        
        // محاولة جلب تقييم المورد إذا كان النموذج موجودًا
        if (method_exists($this->model_supplier_evaluation, 'getSupplierRating')) {
            return $this->model_supplier_evaluation->getSupplierRating($supplier_id);
        }
        
        // قيمة افتراضية إذا لم يكن هناك تقييم
        return 70;
    }
    
    /**
     * الحصول على درجة جودة منتجات المورد
     * 
     * @param int $supplier_id معرف المورد
     * @return float درجة جودة المنتجات (0-100)
     */
    public function getSupplierQualityScore($supplier_id) {
        // يمكن تنفيذ استعلام لجلب درجة جودة المنتجات من قاعدة البيانات
        // هذا تنفيذ مبسط للتوضيح
        $this->load->model('supplier/evaluation');
        
        // محاولة جلب درجة جودة المنتجات إذا كان النموذج موجودًا
        if (method_exists($this->model_supplier_evaluation, 'getSupplierQualityScore')) {
            return $this->model_supplier_evaluation->getSupplierQualityScore($supplier_id);
        }
        
        // قيمة افتراضية إذا لم تكن هناك بيانات
        return 75;
    }
    
    /**
     * الحصول على نسبة التسليم في الموعد للمورد
     * 
     * @param int $supplier_id معرف المورد
     * @return float نسبة التسليم في الموعد (0-100)
     */
    public function getSupplierOnTimeDelivery($supplier_id) {
        // يمكن تنفيذ استعلام لجلب نسبة التسليم في الموعد من قاعدة البيانات
        // هذا تنفيذ مبسط للتوضيح
        $this->load->model('supplier/evaluation');
        
        // محاولة جلب نسبة التسليم في الموعد إذا كان النموذج موجودًا
        if (method_exists($this->model_supplier_evaluation, 'getSupplierOnTimeDelivery')) {
            return $this->model_supplier_evaluation->getSupplierOnTimeDelivery($supplier_id);
        }
        
        // قيمة افتراضية إذا لم تكن هناك بيانات
        return 80;
    }
    
    /**
     * الحصول على اسم الفرع
     * 
     * @param int $branch_id معرف الفرع
     * @return string اسم الفرع
     */
    private function getBranchName($branch_id) {
        $this->load->model('branch/branch');
        
        // محاولة جلب اسم الفرع إذا كان النموذج موجودًا
        if (method_exists($this->model_branch_branch, 'getBranch')) {
            $branch_info = $this->model_branch_branch->getBranch($branch_id);
            return $branch_info ? $branch_info['name'] : '';
        }
        
        return '';
    }
    
    /**
     * إضافة سجل لتاريخ الموافقات والرفض
     * 
     * @param int $quotation_id معرف عرض السعر
     * @param int $user_id معرف المستخدم
     * @param string $action نوع الإجراء (approve, reject, create_po)
     * @param string $comment تعليق
     * @return int معرف السجل الجديد
     */
    public function addApprovalHistory($quotation_id, $user_id, $action, $comment = '') {
        // التحقق من وجود جدول تاريخ الموافقات
        $this->db->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "quotation_approval_history (
            history_id INT(11) NOT NULL AUTO_INCREMENT,
            quotation_id INT(11) NOT NULL,
            user_id INT(11) NOT NULL,
            action VARCHAR(50) NOT NULL,
            comment TEXT,
            date_added DATETIME NOT NULL,
            PRIMARY KEY (history_id)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
        
        // إضافة سجل جديد
        $this->db->query("INSERT INTO " . DB_PREFIX . "quotation_approval_history SET 
            quotation_id = '" . (int)$quotation_id . "',
            user_id = '" . (int)$user_id . "',
            action = '" . $this->db->escape($action) . "',
            comment = '" . $this->db->escape($comment) . "',
            date_added = NOW()");
            
        return $this->db->getLastId();
    }
    
    /**
     * الحصول على تاريخ الموافقات والرفض لعرض سعر
     * 
     * @param int $quotation_id معرف عرض السعر
     * @return array سجلات تاريخ الموافقات
     */
    public function getApprovalHistory($quotation_id) {
        $query = $this->db->query("SELECT h.*, CONCAT(u.firstname, ' ', u.lastname) AS username 
            FROM " . DB_PREFIX . "quotation_approval_history h 
            LEFT JOIN " . DB_PREFIX . "user u ON (h.user_id = u.user_id) 
            WHERE h.quotation_id = '" . (int)$quotation_id . "' 
            ORDER BY h.date_added DESC");
            
        return $query->rows;
    }
    
    /**
     * إرسال إشعار بالموافقة أو الرفض
     * 
     * @param int $quotation_id معرف عرض السعر
     * @param string $action نوع الإجراء (approve, reject)
     * @param int $user_id معرف المستخدم
     * @param string $comment تعليق
     * @return bool نجاح العملية
     */
    public function sendApprovalNotification($quotation_id, $action, $user_id, $comment = '') {
        $this->load->model('purchase/quotation');
        
        // جلب بيانات عرض السعر
        $quotation_info = $this->model_purchase_quotation->getQuotation($quotation_id);
        
        if (!$quotation_info) {
            return false;
        }
        
        // جلب بيانات المورد
        $this->load->model('supplier/supplier');
        $supplier_info = $this->model_supplier_supplier->getSupplier($quotation_info['supplier_id']);
        $supplier_name = $supplier_info ? $supplier_info['name'] : '';
        
        // تحديد عنوان ونص الإشعار
        $title = '';
        $message = '';
        
        if ($action == 'approve') {
            $title = 'تمت الموافقة على عرض السعر #' . $quotation_info['quotation_number'];
            $message = 'تمت الموافقة على عرض السعر رقم ' . $quotation_info['quotation_number'] . ' من المورد ' . $supplier_name;
            if (!empty($comment)) {
                $message .= '. التعليق: ' . $comment;
            }
        } else if ($action == 'reject') {
            $title = 'تم رفض عرض السعر #' . $quotation_info['quotation_number'];
            $message = 'تم رفض عرض السعر رقم ' . $quotation_info['quotation_number'] . ' من المورد ' . $supplier_name;
            if (!empty($comment)) {
                $message .= '. سبب الرفض: ' . $comment;
            }
        }
        
        if (empty($title) || empty($message)) {
            return false;
        }
        
        // إضافة سجل في تاريخ الموافقات
        $this->addApprovalHistory($quotation_id, $user_id, $action, $comment);
        
        // إرسال الإشعار للمستخدمين المعنيين
        $this->sendSystemNotification($title, $message, 'purchase', $quotation_id, 'quotation_approval');
        
        // إرسال إشعار بالبريد الإلكتروني إذا كان التكوين يسمح بذلك
        if ($this->config->get('config_quotation_email_notification')) {
            // جلب المستخدمين المعنيين بالإشعارات
            $this->load->model('user/user');
            $users = $this->model_user_user->getUsersByPermission('purchase/quotation');
            
            $email_to = '';
            $email_cc = array();
            
            // تحديد المستلم الرئيسي والنسخ
            foreach ($users as $user) {
                if (!empty($user['email'])) {
                    if (empty($email_to)) {
                        $email_to = $user['email'];
                    } else {
                        $email_cc[] = $user['email'];
                    }
                }
            }
            
            // إضافة البريد الإلكتروني للمورد في نسخة إذا كان متوفرًا
            if (!empty($supplier_info['email'])) {
                $email_cc[] = $supplier_info['email'];
            }
            
            // إرسال البريد الإلكتروني إذا كان هناك مستلم على الأقل
            if (!empty($email_to)) {
                $email_data = array(
                    'to' => $email_to,
                    'cc' => $email_cc
                );
                
                if ($action == 'approve') {
                    $email_data['comment'] = $comment;
                } else if ($action == 'reject') {
                    $email_data['reason'] = $comment;
                }
                
                $this->sendApprovalEmail($quotation_id, $action, $email_data);
            }
        }
        
        return true;
    }
    
    /**
     * إرسال إشعار بإنشاء أمر شراء
     * 
     * @param int $quotation_id معرف عرض السعر
     * @param int $order_id معرف أمر الشراء
     * @param int $user_id معرف المستخدم
     * @return bool نجاح العملية
     */
    public function sendPurchaseOrderNotification($quotation_id, $order_id, $user_id) {
        $this->load->model('purchase/quotation');
        $this->load->model('purchase/order');
        
        // جلب بيانات عرض السعر
        $quotation_info = $this->model_purchase_quotation->getQuotation($quotation_id);
        
        if (!$quotation_info) {
            return false;
        }
        
        // جلب بيانات أمر الشراء
        $order_info = $this->model_purchase_order->getOrder($order_id);
        
        if (!$order_info) {
            return false;
        }
        
        // جلب بيانات المورد
        $this->load->model('supplier/supplier');
        $supplier_info = $this->model_supplier_supplier->getSupplier($quotation_info['supplier_id']);
        $supplier_name = $supplier_info ? $supplier_info['name'] : '';
        
        // إنشاء عنوان ونص الإشعار
        $title = 'تم إنشاء أمر شراء جديد #' . $order_info['order_number'];
        $message = 'تم إنشاء أمر شراء جديد رقم ' . $order_info['order_number'] . ' بناءً على عرض السعر رقم ' . 
                  $quotation_info['quotation_number'] . ' من المورد ' . $supplier_name;
        
        // إرسال الإشعار للمستخدمين المعنيين
        $this->sendSystemNotification($title, $message, 'purchase', $order_id, 'purchase_order_created');
        
        return true;
    }
    
    /**
     * إرسال إشعار نظام
     * 
     * @param string $title عنوان الإشعار
     * @param string $message نص الإشعار
     * @param string $type نوع الإشعار
     * @param int $reference_id معرف المرجع
     * @param string $reference_type نوع المرجع
     * @return bool نجاح العملية
     */
    private function sendSystemNotification($title, $message, $type, $reference_id, $reference_type) {
        // التحقق من وجود جدول الإشعارات
        $this->db->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "system_notifications (
            notification_id INT(11) NOT NULL AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            type VARCHAR(50) NOT NULL,
            reference_id INT(11) NOT NULL,
            reference_type VARCHAR(50) NOT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT '0',
            date_added DATETIME NOT NULL,
            PRIMARY KEY (notification_id)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
        
        // إضافة إشعار جديد
        $this->db->query("INSERT INTO " . DB_PREFIX . "system_notifications SET 
            title = '" . $this->db->escape($title) . "',
            message = '" . $this->db->escape($message) . "',
            type = '" . $this->db->escape($type) . "',
            reference_id = '" . (int)$reference_id . "',
            reference_type = '" . $this->db->escape($reference_type) . "',
            date_added = NOW()");
            
        return true;
    }
    
    /**
     * تصدير نتائج المقارنة بتنسيق PDF
     * 
     * @param array $data بيانات المقارنة
     * @return string مسار ملف PDF المنشأ
     */
    public function exportComparisonToPDF($data) {
        // التحقق من وجود مكتبة TCPDF
        if (!class_exists('TCPDF')) {
            // تضمين مكتبة TCPDF إذا لم تكن موجودة
            if (file_exists(DIR_SYSTEM . 'library/tcpdf/tcpdf.php')) {
                require_once(DIR_SYSTEM . 'library/tcpdf/tcpdf.php');
            } else {
                // إذا لم تكن المكتبة موجودة، قم بإنشاء ملف نصي بدلاً من PDF
                return $this->exportComparisonToText($data);
            }
        }
        
        // إنشاء اسم الملف
        $filename = 'quotation_comparison_' . date('Ymd_His') . '.pdf';
        $filepath = DIR_DOWNLOAD . $filename;
        
        // إنشاء مستند PDF جديد
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        
        // تعيين معلومات المستند
        $pdf->SetCreator('AYM ERP System');
        $pdf->SetAuthor('AYM ERP');
        $pdf->SetTitle('مقارنة عروض الأسعار');
        $pdf->SetSubject('مقارنة عروض الأسعار');
        
        // تعيين الهوامش
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(10);
        
        // تعيين الخط الافتراضي
        $pdf->SetFont('dejavusans', '', 10);
        
        // إضافة صفحة جديدة
        $pdf->AddPage();
        
        // إنشاء محتوى المستند
        $html = $this->generateComparisonHTML($data);
        
        // إضافة المحتوى إلى المستند
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // حفظ المستند
        $pdf->Output($filepath, 'F');
        
        return $filepath;
    }
    
    /**
     * إنشاء محتوى HTML لمقارنة عروض الأسعار
     * 
     * @param array $data بيانات المقارنة
     * @return string محتوى HTML
     */
    private function generateComparisonHTML($data) {
        $this->load->language('purchase/quotation_comparison');
        
        $html = '<h1 style="text-align: center;">' . $this->language->get('heading_title') . '</h1>';
        
        // معلومات طلب الشراء إذا كانت متوفرة
        if (!empty($data['requisition_info'])) {
            $html .= '<h2>' . $this->language->get('text_requisition_info') . '</h2>';
            $html .= '<table border="1" cellpadding="5" style="width: 100%;">';
            $html .= '<tr>';
            $html .= '<td width="25%"><strong>' . $this->language->get('text_requisition_number') . ':</strong></td>';
            $html .= '<td width="25%">' . $data['requisition_info']['requisition_number'] . '</td>';
            $html .= '<td width="25%"><strong>' . $this->language->get('text_date_required') . ':</strong></td>';
            $html .= '<td width="25%">' . $data['requisition_info']['date_required'] . '</td>';
            $html .= '</tr>';
            $html .= '<tr>';
            $html .= '<td><strong>' . $this->language->get('text_branch') . ':</strong></td>';
            $html .= '<td colspan="3">' . $data['requisition_info']['branch_name'] . '</td>';
            $html .= '</tr>';
            $html .= '</table>';
        }
        
        // ملخص المقارنة
        $html .= '<h2>' . $this->language->get('text_comparison_summary') . '</h2>';
        $html .= '<table border="1" cellpadding="5" style="width: 100%;">';
        $html .= '<tr>';
        $html .= '<td width="50%"><strong>' . $this->language->get('text_total_quotations') . ':</strong></td>';
        $html .= '<td width="50%">' . count($data['quotations']) . '</td>';
        $html .= '</tr>';
        
        // أفضل سعر
        if (!empty($data['best_price'])) {
            $html .= '<tr>';
            $html .= '<td><strong>' . $this->language->get('text_best_price_supplier') . ':</strong></td>';
            $html .= '<td>' . $data['best_price']['supplier_name'] . '</td>';
            $html .= '</tr>';
            $html .= '<tr>';
            $html .= '<td><strong>' . $this->language->get('text_price_difference') . ':</strong></td>';
            $html .= '<td>' . number_format($data['best_price']['price_difference'], 2) . ' (' . number_format($data['best_price']['price_difference_percentage'], 2) . '%)</td>';
            $html .= '</tr>';
        }
        
        // أفضل قيمة
        if (!empty($data['best_value'])) {
            $html .= '<tr>';
            $html .= '<td><strong>' . $this->language->get('text_best_value_supplier') . ':</strong></td>';
            $html .= '<td>' . $data['best_value']['supplier_name'] . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        
        // مقارنة عروض الأسعار
        $html .= '<h2>' . $this->language->get('text_quotation_comparison') . '</h2>';
        $html .= '<table border="1" cellpadding="5" style="width: 100%;">';
        
        // رؤوس الجدول
        $html .= '<tr style="background-color: #f2f2f2;">';
        $html .= '<th>' . $this->language->get('text_criteria') . '</th>';
        
        foreach ($data['quotations'] as $quotation) {
            $html .= '<th>' . $quotation['supplier_name'] . '</th>';
        }
        
        $html .= '</tr>';
        
        // بيانات الجدول
        $criteria = array(
            'quotation_number' => 'text_quotation_number',
            'date_added' => 'text_quotation_date',
            'validity_date' => 'text_validity_date',
            'payment_terms' => 'text_payment_terms',
            'delivery_terms' => 'text_delivery_terms',
            'total' => 'text_total',
            'tax_total' => 'text_tax_total',
            'grand_total' => 'text_grand_total',
            'supplier_rating' => 'text_supplier_rating',
            'on_time_delivery' => 'text_on_time_delivery'
        );
        
        foreach ($criteria as $key => $text) {
            $html .= '<tr>';
            $html .= '<td><strong>' . $this->language->get($text) . '</strong></td>';
            
            foreach ($data['quotations'] as $quotation) {
                $value = isset($quotation[$key]) ? $quotation[$key] : '';
                
                // تنسيق القيم المالية
                if (in_array($key, array('total', 'tax_total', 'grand_total'))) {
                    $value = number_format($value, 2);
                }
                
                // تنسيق النسب المئوية
                if (in_array($key, array('supplier_rating', 'on_time_delivery'))) {
                    $value = number_format($value, 2) . '%';
                }
                
                $html .= '<td>' . $value . '</td>';
            }
            
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        
        // مقارنة المنتجات
        $html .= '<h2>' . $this->language->get('text_product_comparison') . '</h2>';
        
        foreach ($data['products'] as $product) {
            $html .= '<h3>' . $product['name'] . ' (' . $product['model'] . ')</h3>';
            $html .= '<table border="1" cellpadding="5" style="width: 100%;">';
            $html .= '<tr style="background-color: #f2f2f2;">';
            $html .= '<th>' . $this->language->get('text_supplier') . '</th>';
            $html .= '<th>' . $this->language->get('text_price') . '</th>';
            $html .= '<th>' . $this->language->get('text_tax') . '</th>';
            $html .= '<th>' . $this->language->get('text_total') . '</th>';
            $html .= '</tr>';
            
            foreach ($product['suppliers'] as $supplier) {
                $html .= '<tr>';
                $html .= '<td>' . $supplier['supplier_name'] . '</td>';
                $html .= '<td>' . number_format($supplier['price'], 2) . '</td>';
                $html .= '<td>' . number_format($supplier['tax'], 2) . '</td>';
                $html .= '<td>' . number_format($supplier['total'], 2) . '</td>';
                $html .= '</tr>';
            }
            
            $html .= '</table>';
        }
        
        return $html;
    }
    
    /**
     * تصدير نتائج المقارنة بتنسيق نصي
     * 
     * @param array $data بيانات المقارنة
     * @return string مسار الملف النصي المنشأ
     */
    private function exportComparisonToText($data) {
        // إنشاء اسم الملف
        $filename = 'quotation_comparison_' . date('Ymd_His') . '.txt';
        $filepath = DIR_DOWNLOAD . $filename;
        
        $this->load->language('purchase/quotation_comparison');
        
        // إنشاء محتوى الملف
        $content = $this->language->get('heading_title') . "\n";
        $content .= str_repeat('=', 50) . "\n\n";
        
        // معلومات طلب الشراء إذا كانت متوفرة
        if (!empty($data['requisition_info'])) {
            $content .= $this->language->get('text_requisition_info') . "\n";
            $content .= str_repeat('-', 30) . "\n";
            $content .= $this->language->get('text_requisition_number') . ": " . $data['requisition_info']['requisition_number'] . "\n";
            $content .= $this->language->get('text_date_required') . ": " . $data['requisition_info']['date_required'] . "\n";
            $content .= $this->language->get('text_branch') . ": " . $data['requisition_info']['branch_name'] . "\n\n";
        }
        
        // ملخص المقارنة
        $content .= $this->language->get('text_comparison_summary') . "\n";
        $content .= str_repeat('-', 30) . "\n";
        $content .= $this->language->get('text_total_quotations') . ": " . count($data['quotations']) . "\n";
        
        // أفضل سعر
        if (!empty($data['best_price'])) {
            $content .= $this->language->get('text_best_price_supplier') . ": " . $data['best_price']['supplier_name'] . "\n";
            $content .= $this->language->get('text_price_difference') . ": " . 
                      number_format($data['best_price']['price_difference'], 2) . 
                      " (" . number_format($data['best_price']['price_difference_percentage'], 2) . "%)\n";
        }
        
        // أفضل قيمة
        if (!empty($data['best_value'])) {
            $content .= $this->language->get('text_best_value_supplier') . ": " . $data['best_value']['supplier_name'] . "\n\n";
        }
        
        // كتابة المحتوى إلى الملف
        file_put_contents($filepath, $content);
        
        return $filepath;
    }
    
    /**
     * تصدير نتائج المقارنة بتنسيق CSV
     * 
     * @param array $data بيانات المقارنة
     * @return string مسار ملف CSV المنشأ
     */
    public function exportComparisonToCSV($data) {
        // إنشاء اسم الملف
        $filename = 'quotation_comparison_' . date('Ymd_His') . '.csv';
        $filepath = DIR_DOWNLOAD . $filename;
        
        $this->load->language('purchase/quotation_comparison');
        
        // فتح الملف للكتابة
        $handle = fopen($filepath, 'w');
        
        // إضافة BOM للتوافق مع اللغة العربية في Excel
        fputs($handle, "\xEF\xBB\xBF");
        
        // كتابة عنوان التقرير
        fputcsv($handle, array($this->language->get('heading_title')));
        fputcsv($handle, array('')); // سطر فارغ
        
        // معلومات طلب الشراء إذا كانت متوفرة
        if (!empty($data['requisition_info'])) {
            fputcsv($handle, array($this->language->get('text_requisition_info')));
            fputcsv($handle, array($this->language->get('text_requisition_number'), $data['requisition_info']['requisition_number']));
            fputcsv($handle, array($this->language->get('text_date_required'), $data['requisition_info']['date_required']));
            fputcsv($handle, array($this->language->get('text_branch'), $data['requisition_info']['branch_name']));
            fputcsv($handle, array('')); // سطر فارغ
        }
        
        // ملخص المقارنة
        fputcsv($handle, array($this->language->get('text_comparison_summary')));
        fputcsv($handle, array($this->language->get('text_total_quotations'), count($data['quotations'])));
        
        // أفضل سعر
        if (!empty($data['best_price'])) {
            fputcsv($handle, array($this->language->get('text_best_price_supplier'), $data['best_price']['supplier_name']));
            fputcsv($handle, array(
                $this->language->get('text_price_difference'), 
                number_format($data['best_price']['price_difference'], 2) . ' (' . 
                number_format($data['best_price']['price_difference_percentage'], 2) . '%)'
            ));
        }
        
        // أفضل قيمة
        if (!empty($data['best_value'])) {
            fputcsv($handle, array($this->language->get('text_best_value_supplier'), $data['best_value']['supplier_name']));
        }
        
        fputcsv($handle, array('')); // سطر فارغ
        
        // مقارنة عروض الأسعار
        fputcsv($handle, array($this->language->get('text_quotation_comparison')));
        
        // رؤوس الجدول
        $header = array($this->language->get('text_criteria'));
        foreach ($data['quotations'] as $quotation) {
            $header[] = $quotation['supplier_name'];
        }
        fputcsv($handle, $header);
        
        // بيانات الجدول
        $criteria = array(
            'quotation_number' => 'text_quotation_number',
            'date_added' => 'text_quotation_date',
            'validity_date' => 'text_validity_date',
            'payment_terms' => 'text_payment_terms',
            'delivery_terms' => 'text_delivery_terms',
            'total' => 'text_total',
            'tax_total' => 'text_tax_total',
            'grand_total' => 'text_grand_total',
            'supplier_rating' => 'text_supplier_rating',
            'on_time_delivery' => 'text_on_time_delivery'
        );
        
        foreach ($criteria as $key => $text) {
            $row = array($this->language->get($text));
            
            foreach ($data['quotations'] as $quotation) {
                $value = isset($quotation[$key]) ? $quotation[$key] : '';
                
                // تنسيق القيم المالية
                if (in_array($key, array('total', 'tax_total', 'grand_total'))) {
                    $value = number_format($value, 2);
                }
                
                // تنسيق النسب المئوية
                if (in_array($key, array('supplier_rating', 'on_time_delivery'))) {
                    $value = number_format($value, 2) . '%';
                }
                
                $row[] = $value;
            }
            
            fputcsv($handle, $row);
        }
        
        fputcsv($handle, array('')); // سطر فارغ
        
        // مقارنة المنتجات
        fputcsv($handle, array($this->language->get('text_product_comparison')));
        
        foreach ($data['products'] as $product) {
            fputcsv($handle, array($product['name'] . ' (' . $product['model'] . ')'));
            
            // رؤوس جدول المنتج
            fputcsv($handle, array(
                $this->language->get('text_supplier'),
                $this->language->get('text_price'),
                $this->language->get('text_tax'),
                $this->language->get('text_total')
            ));
            
            // بيانات المنتج
            foreach ($product['suppliers'] as $supplier) {
                fputcsv($handle, array(
                    $supplier['supplier_name'],
                    number_format($supplier['price'], 2),
                    number_format($supplier['tax'], 2),
                    number_format($supplier['total'], 2)
                ));
            }
            
            fputcsv($handle, array('')); // سطر فارغ
        }
        
        // إغلاق الملف
        fclose($handle);
        
        return $filepath;
    }
    
    /**
     * تصدير نتائج المقارنة بتنسيق Excel
     * 
     * @param array $data بيانات المقارنة
     * @return string مسار ملف Excel المنشأ
     */
    public function exportComparisonToExcel($data) {
        // التحقق من وجود مكتبة PHPExcel
        if (!class_exists('PHPExcel')) {
            // تضمين مكتبة PHPExcel إذا لم تكن موجودة
            if (file_exists(DIR_SYSTEM . 'library/phpexcel/PHPExcel.php')) {
                require_once(DIR_SYSTEM . 'library/phpexcel/PHPExcel.php');
            } else {
                // إذا لم تكن المكتبة موجودة، قم بتصدير PDF بدلاً من Excel
                return $this->exportComparisonToPDF($data);
            }
        }
        
        // إنشاء اسم الملف
        $filename = 'quotation_comparison_' . date('Ymd_His') . '.xlsx';
        $filepath = DIR_DOWNLOAD . $filename;
        
        // إنشاء مستند Excel جديد
        $excel = new PHPExcel();
        
        // تعيين خصائص المستند
        $excel->getProperties()
            ->setCreator('AYM ERP System')
            ->setLastModifiedBy('AYM ERP System')
            ->setTitle('مقارنة عروض الأسعار')
            ->setSubject('مقارنة عروض الأسعار')
            ->setDescription('تقرير مقارنة عروض الأسعار من نظام AYM ERP');
        
        $this->load->language('purchase/quotation_comparison');
        
        // إنشاء ورقة العمل الأولى - ملخص المقارنة
        $excel->setActiveSheetIndex(0);
        $excel->getActiveSheet()->setTitle('ملخص المقارنة');
        $sheet = $excel->getActiveSheet();
        
        // تعيين عنوان التقرير
        $sheet->setCellValue('A1', $this->language->get('heading_title'));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells('A1:D1');
        
        $row = 3;
        
        // معلومات طلب الشراء إذا كانت متوفرة
        if (!empty($data['requisition_info'])) {
            $sheet->setCellValue('A' . $row, $this->language->get('text_requisition_info'));
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;
            
            $sheet->setCellValue('A' . $row, $this->language->get('text_requisition_number'));
            $sheet->setCellValue('B' . $row, $data['requisition_info']['requisition_number']);
            $row++;
            
            $sheet->setCellValue('A' . $row, $this->language->get('text_date_required'));
            $sheet->setCellValue('B' . $row, $data['requisition_info']['date_required']);
            $row++;
            
            $sheet->setCellValue('A' . $row, $this->language->get('text_branch'));
            $sheet->setCellValue('B' . $row, $data['requisition_info']['branch_name']);
            $row += 2;
        }
        
        // ملخص المقارنة
        $sheet->setCellValue('A' . $row, $this->language->get('text_comparison_summary'));
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        
        $sheet->setCellValue('A' . $row, $this->language->get('text_total_quotations'));
        $sheet->setCellValue('B' . $row, count($data['quotations']));
        $row++;
        
        // أفضل سعر
        if (!empty($data['best_price'])) {
            $sheet->setCellValue('A' . $row, $this->language->get('text_best_price_supplier'));
            $sheet->setCellValue('B' . $row, $data['best_price']['supplier_name']);
            $row++;
            
            $sheet->setCellValue('A' . $row, $this->language->get('text_price_difference'));
            $sheet->setCellValue('B' . $row, number_format($data['best_price']['price_difference'], 2) . 
                                ' (' . number_format($data['best_price']['price_difference_percentage'], 2) . '%)');
            $row++;
        }
        
        // أفضل قيمة
        if (!empty($data['best_value'])) {
            $sheet->setCellValue('A' . $row, $this->language->get('text_best_value_supplier'));
            $sheet->setCellValue('B' . $row, $data['best_value']['supplier_name']);
            $row += 2;
        }
        
        // إنشاء ورقة عمل جديدة - مقارنة عروض الأسعار
        $excel->createSheet();
        $excel->setActiveSheetIndex(1);
        $excel->getActiveSheet()->setTitle('مقارنة العروض');
        $sheet = $excel->getActiveSheet();
        
        // رؤوس الجدول
        $sheet->setCellValue('A1', $this->language->get('text_criteria'));
        $col = 'B';
        
        foreach ($data['quotations'] as $quotation) {
            $sheet->setCellValue($col . '1', $quotation['supplier_name']);
            $col++;
        }
        
        // تنسيق رؤوس الجدول
        $lastCol = chr(ord('A') + count($data['quotations']));
        $sheet->getStyle('A1:' . $lastCol . '1')->getFont()->setBold(true);
        
        // بيانات الجدول
        $criteria = array(
            'quotation_number' => 'text_quotation_number',
            'date_added' => 'text_quotation_date',
            'validity_date' => 'text_validity_date',
            'payment_terms' => 'text_payment_terms',
            'delivery_terms' => 'text_delivery_terms',
            'total' => 'text_total',
            'tax_total' => 'text_tax_total',
            'grand_total' => 'text_grand_total',
            'supplier_rating' => 'text_supplier_rating',
            'on_time_delivery' => 'text_on_time_delivery'
        );
        
        $row = 2;
        foreach ($criteria as $key => $text) {
            $sheet->setCellValue('A' . $row, $this->language->get($text));
            
            $col = 'B';
            foreach ($data['quotations'] as $quotation) {
                $value = isset($quotation[$key]) ? $quotation[$key] : '';
                $sheet->setCellValue($col . $row, $value);
                $col++;
            }
            
            $row++;
        }
        
        // إنشاء ورقة عمل جديدة لكل منتج
        $productIndex = 2;
        foreach ($data['products'] as $product) {
            $excel->createSheet();
            $excel->setActiveSheetIndex($productIndex);
            $excel->getActiveSheet()->setTitle(substr($product['name'], 0, 30)); // تقصير الاسم ليناسب عنوان الورقة
            $sheet = $excel->getActiveSheet();
            
            // عنوان المنتج
            $sheet->setCellValue('A1', $product['name'] . ' (' . $product['model'] . ')');
            $sheet->getStyle('A1')->getFont()->setBold(true);
            $sheet->mergeCells('A1:D1');
            
            // رؤوس الجدول
            $sheet->setCellValue('A3', $this->language->get('text_supplier'));
            $sheet->setCellValue('B3', $this->language->get('text_price'));
            $sheet->setCellValue('C3', $this->language->get('text_tax'));
            $sheet->setCellValue('D3', $this->language->get('text_total'));
            $sheet->getStyle('A3:D3')->getFont()->setBold(true);
            
            // بيانات المنتج
            $row = 4;
            foreach ($product['suppliers'] as $supplier) {
                $sheet->setCellValue('A' . $row, $supplier['supplier_name']);
                $sheet->setCellValue('B' . $row, $supplier['price']);
                $sheet->setCellValue('C' . $row, $supplier['tax']);
                $sheet->setCellValue('D' . $row, $supplier['total']);
                $row++;
            }
            
            $productIndex++;
        }
        
        // تعيين الورقة الأولى كنشطة
        $excel->setActiveSheetIndex(0);
        
        // حفظ الملف
        $writer = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
        $writer->save($filepath);
        
        return $filepath;
    }
    
    /**
     * الحصول على تقرير مفصل عن تاريخ الموافقات والرفض
     * 
     * @param int $quotation_id معرف عرض السعر
     * @param array $filter معايير التصفية
     * @return array بيانات التقرير
     */
    public function getApprovalHistoryReport($quotation_id, $filter = array()) {
        $this->load->model('purchase/quotation');
        
        // جلب بيانات عرض السعر
        $quotation_info = $this->model_purchase_quotation->getQuotation($quotation_id);
        
        if (!$quotation_info) {
            return array();
        }
        
        // جلب بيانات المورد
        $this->load->model('supplier/supplier');
        $supplier_info = $this->model_supplier_supplier->getSupplier($quotation_info['supplier_id']);
        
        // جلب سجلات تاريخ الموافقات
        $history_records = $this->getApprovalHistory($quotation_id);
        
        // تطبيق معايير التصفية إذا كانت موجودة
        if (!empty($filter['date_start']) && !empty($filter['date_end'])) {
            $date_start = strtotime($filter['date_start'] . ' 00:00:00');
            $date_end = strtotime($filter['date_end'] . ' 23:59:59');
            
            foreach ($history_records as $key => $record) {
                $record_date = strtotime($record['date_added']);
                
                if ($record_date < $date_start || $record_date > $date_end) {
                    unset($history_records[$key]);
                }
            }
            
            // إعادة ترتيب المفاتيح
            $history_records = array_values($history_records);
        }
        
        // إنشاء بيانات التقرير
        $report_data = array(
            'quotation_info' => $quotation_info,
            'supplier_info' => $supplier_info,
            'history_records' => $history_records,
            'summary' => array(
                'total_records' => count($history_records),
                'approvals' => 0,
                'rejections' => 0,
                'po_created' => 0,
                'last_action' => '',
                'last_action_date' => '',
                'last_action_user' => ''
            )
        );
        
        // حساب ملخص الإحصائيات
        foreach ($history_records as $record) {
            if ($record['action'] == 'approve') {
                $report_data['summary']['approvals']++;
            } else if ($record['action'] == 'reject') {
                $report_data['summary']['rejections']++;
            } else if ($record['action'] == 'create_po') {
                $report_data['summary']['po_created']++;
            }
            
            // تحديث آخر إجراء
            if (empty($report_data['summary']['last_action_date']) || 
                strtotime($record['date_added']) > strtotime($report_data['summary']['last_action_date'])) {
                $report_data['summary']['last_action'] = $record['action'];
                $report_data['summary']['last_action_date'] = $record['date_added'];
                $report_data['summary']['last_action_user'] = $record['username'];
            }
        }
        
        return $report_data;
    }
    
    /**
     * إرسال إشعار بالبريد الإلكتروني للموافقة أو الرفض
     * 
     * @param int $quotation_id معرف عرض السعر
     * @param string $action نوع الإجراء (approve, reject)
     * @param array $email_data بيانات البريد الإلكتروني
     * @return bool نجاح العملية
     */
    public function sendApprovalEmail($quotation_id, $action, $email_data) {
        $this->load->model('purchase/quotation');
        
        // جلب بيانات عرض السعر
        $quotation_info = $this->model_purchase_quotation->getQuotation($quotation_id);
        
        if (!$quotation_info) {
            return false;
        }
        
        // جلب بيانات المورد
        $this->load->model('supplier/supplier');
        $supplier_info = $this->model_supplier_supplier->getSupplier($quotation_info['supplier_id']);
        $supplier_name = $supplier_info ? $supplier_info['name'] : '';
        
        // تحديد عنوان ونص البريد الإلكتروني
        $subject = '';
        $message = '';
        
        if ($action == 'approve') {
            $subject = 'تمت الموافقة على عرض السعر #' . $quotation_info['quotation_number'];
            $message = '<p>تمت الموافقة على عرض السعر رقم ' . $quotation_info['quotation_number'] . ' من المورد ' . $supplier_name . '</p>';
            
            if (!empty($email_data['comment'])) {
                $message .= '<p><strong>التعليق:</strong> ' . $email_data['comment'] . '</p>';
            }
            
            $message .= '<p><strong>تفاصيل عرض السعر:</strong></p>';
            $message .= '<ul>';
            $message .= '<li>رقم عرض السعر: ' . $quotation_info['quotation_number'] . '</li>';
            $message .= '<li>المورد: ' . $supplier_name . '</li>';
            $message .= '<li>تاريخ العرض: ' . $quotation_info['date_added'] . '</li>';
            $message .= '<li>تاريخ الصلاحية: ' . $quotation_info['validity_date'] . '</li>';
            $message .= '<li>إجمالي المبلغ: ' . $quotation_info['grand_total'] . '</li>';
            $message .= '</ul>';
            
            $message .= '<p>سيتم إنشاء أمر شراء بناءً على هذا العرض قريبًا.</p>';
            
        } else if ($action == 'reject') {
            $subject = 'تم رفض عرض السعر #' . $quotation_info['quotation_number'];
            $message = '<p>تم رفض عرض السعر رقم ' . $quotation_info['quotation_number'] . ' من المورد ' . $supplier_name . '</p>';
            
            if (!empty($email_data['reason'])) {
                $message .= '<p><strong>سبب الرفض:</strong> ' . $email_data['reason'] . '</p>';
            }
            
            $message .= '<p><strong>تفاصيل عرض السعر:</strong></p>';
            $message .= '<ul>';
            $message .= '<li>رقم عرض السعر: ' . $quotation_info['quotation_number'] . '</li>';
            $message .= '<li>المورد: ' . $supplier_name . '</li>';
            $message .= '<li>تاريخ العرض: ' . $quotation_info['date_added'] . '</li>';
            $message .= '<li>تاريخ الصلاحية: ' . $quotation_info['validity_date'] . '</li>';
            $message .= '<li>إجمالي المبلغ: ' . $quotation_info['grand_total'] . '</li>';
            $message .= '</ul>';
            
            $message .= '<p>يرجى مراجعة سبب الرفض واتخاذ الإجراء المناسب.</p>';
        }
        
        if (empty($subject) || empty($message)) {
            return false;
        }
        
        // إرسال البريد الإلكتروني
        $mail = new Mail();
        $mail->protocol = $this->config->get('config_mail_protocol');
        $mail->parameter = $this->config->get('config_mail_parameter');
        $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
        $mail->smtp_username = $this->config->get('config_mail_smtp_username');
        $mail->smtp_password = $this->config->get('config_mail_smtp_password');
        $mail->smtp_port = $this->config->get('config_mail_smtp_port');
        $mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');
        
        $mail->setTo($email_data['to']);
        $mail->setFrom($this->config->get('config_email'));
        $mail->setSender($this->config->get('config_name'));
        $mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
        $mail->setHtml($message);
        
        // إضافة نسخة إلى المستخدمين المعنيين
        if (!empty($email_data['cc'])) {
            foreach ($email_data['cc'] as $cc_email) {
                $mail->addCc($cc_email);
            }
        }
        
        // إرسال البريد الإلكتروني
        $mail->send();
        
        return true;
    }
    
    /**
     * الحصول على تقرير مفصل عن تاريخ الموافقات والرفض
     * 
     * @param int $quotation_id معرف عرض السعر (اختياري)
     * @param array $filter معايير التصفية (اختياري)
     * @return array بيانات التقرير
     */
    public function getApprovalReport($quotation_id = 0, $filter = array()) {
        $sql = "SELECT h.*, q.quotation_number, q.supplier_id, CONCAT(u.firstname, ' ', u.lastname) AS username, 
                s.name AS supplier_name 
                FROM " . DB_PREFIX . "quotation_approval_history h 
                LEFT JOIN " . DB_PREFIX . "purchase_quotation q ON (h.quotation_id = q.quotation_id) 
                LEFT JOIN " . DB_PREFIX . "user u ON (h.user_id = u.user_id) 
                LEFT JOIN " . DB_PREFIX . "supplier s ON (q.supplier_id = s.supplier_id) 
                WHERE 1=1";
        
        // تطبيق معايير التصفية
        if ($quotation_id > 0) {
            $sql .= " AND h.quotation_id = '" . (int)$quotation_id . "'";
        }
        
        if (!empty($filter['start_date'])) {
            $sql .= " AND DATE(h.date_added) >= '" . $this->db->escape($filter['start_date']) . "'";
        }
        
        if (!empty($filter['end_date'])) {
            $sql .= " AND DATE(h.date_added) <= '" . $this->db->escape($filter['end_date']) . "'";
        }
        
        if (!empty($filter['user_id'])) {
            $sql .= " AND h.user_id = '" . (int)$filter['user_id'] . "'";
        }
        
        if (!empty($filter['action'])) {
            $sql .= " AND h.action = '" . $this->db->escape($filter['action']) . "'";
        }
        
        if (!empty($filter['supplier_id'])) {
            $sql .= " AND q.supplier_id = '" . (int)$filter['supplier_id'] . "'";
        }
        
        // ترتيب النتائج
        $sql .= " ORDER BY h.date_added DESC";
        
        // تحديد عدد النتائج
        if (isset($filter['start']) && isset($filter['limit'])) {
            $sql .= " LIMIT " . (int)$filter['start'] . "," . (int)$filter['limit'];
        }
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    /**
     * الحصول على إجمالي عدد سجلات تاريخ الموافقات والرفض
     * 
     * @param int $quotation_id معرف عرض السعر (اختياري)
     * @param array $filter معايير التصفية (اختياري)
     * @return int عدد السجلات
     */
    public function getTotalApprovalHistory($quotation_id = 0, $filter = array()) {
        $sql = "SELECT COUNT(*) AS total 
                FROM " . DB_PREFIX . "quotation_approval_history h 
                LEFT JOIN " . DB_PREFIX . "purchase_quotation q ON (h.quotation_id = q.quotation_id) 
                WHERE 1=1";
        
        // تطبيق معايير التصفية
        if ($quotation_id > 0) {
            $sql .= " AND h.quotation_id = '" . (int)$quotation_id . "'";
        }
        
        if (!empty($filter['start_date'])) {
            $sql .= " AND DATE(h.date_added) >= '" . $this->db->escape($filter['start_date']) . "'";
        }
        
        if (!empty($filter['end_date'])) {
            $sql .= " AND DATE(h.date_added) <= '" . $this->db->escape($filter['end_date']) . "'";
        }
        
        if (!empty($filter['user_id'])) {
            $sql .= " AND h.user_id = '" . (int)$filter['user_id'] . "'";
        }
        
        if (!empty($filter['action'])) {
            $sql .= " AND h.action = '" . $this->db->escape($filter['action']) . "'";
        }
        
        if (!empty($filter['supplier_id'])) {
            $sql .= " AND q.supplier_id = '" . (int)$filter['supplier_id'] . "'";
        }
        
        $query = $this->db->query($sql);
        
        return $query->row['total'];
    }
    
    /**
     * تصدير نتائج المقارنة بتنسيق CSV
     * 
     * @param array $data بيانات المقارنة
     * @return string مسار ملف CSV المنشأ
     */
    public function exportComparisonToCSV($data) {
        // إنشاء اسم الملف
        $filename = 'quotation_comparison_' . date('Ymd_His') . '.csv';
        $filepath = DIR_DOWNLOAD . $filename;
        
        $this->load->language('purchase/quotation_comparison');
        
        // فتح الملف للكتابة
        $handle = fopen($filepath, 'w');
        
        // إضافة BOM للتوافق مع اللغة العربية في Excel
        fputs($handle, "\xEF\xBB\xBF");
        
        // كتابة عنوان التقرير
        fputcsv($handle, array($this->language->get('heading_title')));
        fputcsv($handle, array('')); // سطر فارغ
        
        // معلومات طلب الشراء إذا كانت متوفرة
        if (!empty($data['requisition_info'])) {
            fputcsv($handle, array($this->language->get('text_requisition_info')));
            fputcsv($handle, array($this->language->get('text_requisition_number'), $data['requisition_info']['requisition_number']));
            fputcsv($handle, array($this->language->get('text_date_required'), $data['requisition_info']['date_required']));
            fputcsv($handle, array($this->language->get('text_branch'), $data['requisition_info']['branch_name']));
            fputcsv($handle, array('')); // سطر فارغ
        }
        
        // ملخص المقارنة
        fputcsv($handle, array($this->language->get('text_comparison_summary')));
        fputcsv($handle, array($this->language->get('text_total_quotations'), count($data['quotations'])));
        
        // أفضل سعر
        if (!empty($data['best_price'])) {
            fputcsv($handle, array($this->language->get('text_best_price_supplier'), $data['best_price']['supplier_name']));
            fputcsv($handle, array(
                $this->language->get('text_price_difference'), 
                number_format($data['best_price']['price_difference'], 2) . ' (' . 
                number_format($data['best_price']['price_difference_percentage'], 2) . '%)'
            ));
        }
        
        // أفضل قيمة
        if (!empty($data['best_value'])) {
            fputcsv($handle, array($this->language->get('text_best_value_supplier'), $data['best_value']['supplier_name']));
        }
        
        fputcsv($handle, array('')); // سطر فارغ
        
        // مقارنة عروض الأسعار
        fputcsv($handle, array($this->language->get('text_quotation_comparison')));
        
        // رؤوس الجدول
        $header = array($this->language->get('text_criteria'));
        foreach ($data['quotations'] as $quotation) {
            $header[] = $quotation['supplier_name'];
        }
        fputcsv($handle, $header);
        
        // بيانات الجدول
        $criteria = array(
            'quotation_number' => 'text_quotation_number',
            'date_added' => 'text_quotation_date',
            'validity_date' => 'text_validity_date',
            'payment_terms' => 'text_payment_terms',
            'delivery_terms' => 'text_delivery_terms',
            'total' => 'text_total',
            'tax_total' => 'text_tax_total',
            'grand_total' => 'text_grand_total',
            'supplier_rating' => 'text_supplier_rating',
            'on_time_delivery' => 'text_on_time_delivery'
        );
        
        foreach ($criteria as $key => $text) {
            $row = array($this->language->get($text));
            
            foreach ($data['quotations'] as $quotation) {
                $value = isset($quotation[$key]) ? $quotation[$key] : '';
                
                // تنسيق القيم المالية
                if (in_array($key, array('total', 'tax_total', 'grand_total'))) {
                    $value = number_format($value, 2);
                }
                
                // تنسيق النسب المئوية
                if (in_array($key, array('supplier_rating', 'on_time_delivery'))) {
                    $value = number_format($value, 2) . '%';
                }
                
                $row[] = $value;
            }
            
            fputcsv($handle, $row);
        }
        
        fputcsv($handle, array('')); // سطر فارغ
        
        // مقارنة المنتجات
        fputcsv($handle, array($this->language->get('text_product_comparison')));
        
        foreach ($data['products'] as $product) {
            fputcsv($handle, array($product['name'] . ' (' . $product['model'] . ')'));
            
            // رؤوس جدول المنتج
            fputcsv($handle, array(
                $this->language->get('text_supplier'),
                $this->language->get('text_price'),
                $this->language->get('text_tax'),
                $this->language->get('text_total')
            ));
            
            // بيانات المنتج
            foreach ($product['suppliers'] as $supplier) {
                fputcsv($handle, array(
                    $supplier['supplier_name'],
                    number_format($supplier['price'], 2),
                    number_format($supplier['tax'], 2),
                    number_format($supplier['total'], 2)
                ));
            }
            
            fputcsv($handle, array('')); // سطر فارغ
        }
        
        // إغلاق الملف
        fclose($handle);
        
        return $filepath;
    }
    
    /**
     * تصدير نتائج المقارنة بتنسيق Excel
     * 
     * @param array $data بيانات المقارنة
     * @return string مسار ملف Excel المنشأ
     */
    public function exportComparisonToExcel($data) {
        // هذه وظيفة تجريبية تحتاج إلى تنفيذ كامل باستخدام مكتبة Excel
        // يمكن استخدام مكتبات مثل PHPExcel أو PhpSpreadsheet
        
        // إنشاء اسم الملف
        $filename = 'quotation_comparison_' . date('Ymd_His') . '.xlsx';
        $filepath = DIR_DOWNLOAD . $filename;
        
        // هنا يتم إنشاء ملف Excel باستخدام المكتبة المناسبة
        // ...
        
        return $filepath;
    }
    
    /**
     * تصدير نتائج المقارنة بتنسيق CSV
     * 
     * @param array $data بيانات المقارنة
     * @return string مسار ملف CSV المنشأ
     */
    public function exportComparisonToCSV($data) {
        // إنشاء اسم الملف
        $filename = 'quotation_comparison_' . date('Ymd_His') . '.csv';
        $filepath = DIR_DOWNLOAD . $filename;
        
        $this->load->language('purchase/quotation_comparison');
        
        // فتح الملف للكتابة
        $handle = fopen($filepath, 'w');
        
        // إضافة BOM للتوافق مع اللغة العربية في Excel
        fputs($handle, "\xEF\xBB\xBF");
        
        // كتابة عنوان التقرير
        fputcsv($handle, array($this->language->get('heading_title')));
        fputcsv($handle, array('')); // سطر فارغ
        
        // معلومات طلب الشراء إذا كانت متوفرة
        if (!empty($data['requisition_info'])) {
            fputcsv($handle, array($this->language->get('text_requisition_info')));
            fputcsv($handle, array($this->language->get('text_requisition_number'), $data['requisition_info']['requisition_number']));
            fputcsv($handle, array($this->language->get('text_date_required'), $data['requisition_info']['date_required']));
            fputcsv($handle, array($this->language->get('text_branch'), $data['requisition_info']['branch_name']));
            fputcsv($handle, array('')); // سطر فارغ
        }
        
        // ملخص المقارنة
        fputcsv($handle, array($this->language->get('text_comparison_summary')));
        fputcsv($handle, array($this->language->get('text_total_quotations'), count($data['quotations'])));
        
        // أفضل سعر
        if (!empty($data['best_price'])) {
            fputcsv($handle, array($this->language->get('text_best_price_supplier'), $data['best_price']['supplier_name']));
            fputcsv($handle, array(
                $this->language->get('text_price_difference'), 
                number_format($data['best_price']['price_difference'], 2) . ' (' . 
                number_format($data['best_price']['price_difference_percentage'], 2) . '%)'
            ));
        }
        
        // أفضل قيمة
        if (!empty($data['best_value'])) {
            fputcsv($handle, array($this->language->get('text_best_value_supplier'), $data['best_value']['supplier_name']));
        }
        
        fputcsv($handle, array('')); // سطر فارغ
        
        // مقارنة عروض الأسعار
        fputcsv($handle, array($this->language->get('text_quotation_comparison')));
        
        // رؤوس الجدول
        $header = array($this->language->get('text_criteria'));
        foreach ($data['quotations'] as $quotation) {
            $header[] = $quotation['supplier_name'];
        }
        fputcsv($handle, $header);
        
        // بيانات الجدول
        $criteria = array(
            'quotation_number' => 'text_quotation_number',
            'date_added' => 'text_quotation_date',
            'validity_date' => 'text_validity_date',
            'payment_terms' => 'text_payment_terms',
            'delivery_terms' => 'text_delivery_terms',
            'total' => 'text_total',
            'tax_total' => 'text_tax_total',
            'grand_total' => 'text_grand_total',
            'supplier_rating' => 'text_supplier_rating',
            'on_time_delivery' => 'text_on_time_delivery'
        );
        
        foreach ($criteria as $key => $text) {
            $row = array($this->language->get($text));
            
            foreach ($data['quotations'] as $quotation) {
                $value = isset($quotation[$key]) ? $quotation[$key] : '';
                
                // تنسيق القيم المالية
                if (in_array($key, array('total', 'tax_total', 'grand_total'))) {
                    $value = number_format($value, 2);
                }
                
                // تنسيق النسب المئوية
                if (in_array($key, array('supplier_rating', 'on_time_delivery'))) {
                    $value = number_format($value, 2) . '%';
                }
                
                $row[] = $value;
            }
            
            fputcsv($handle, $row);
        }
        
        fputcsv($handle, array('')); // سطر فارغ
        
        // مقارنة المنتجات
        fputcsv($handle, array($this->language->get('text_product_comparison')));
        
        foreach ($data['products'] as $product) {
            fputcsv($handle, array($product['name'] . ' (' . $product['model'] . ')'));
            
            // رؤوس جدول المنتج
            fputcsv($handle, array(
                $this->language->get('text_supplier'),
                $this->language->get('text_price'),
                $this->language->get('text_tax'),
                $this->language->get('text_total')
            ));
            
            // بيانات المنتج
            foreach ($product['suppliers'] as $supplier) {
                fputcsv($handle, array(
                    $supplier['supplier_name'],
                    number_format($supplier['price'], 2),
                    number_format($supplier['tax'], 2),
                    number_format($supplier['total'], 2)
                ));
            }
            
            fputcsv($handle, array('')); // سطر فارغ
        }
        
        // إغلاق الملف
        fclose($handle);
        
        return $filepath;
    }
    
    /**
     * تصدير نتائج المقارنة بتنسيق Excel
     * 
     * @param array $data بيانات المقارنة
     * @return string مسار ملف Excel المنشأ
     */
    public function exportComparisonToExcel($data) {
        // التحقق من وجود مكتبة PHPExcel
        if (!class_exists('PHPExcel')) {
            // تضمين مكتبة PHPExcel إذا لم تكن موجودة
            if (file_exists(DIR_SYSTEM . 'library/phpexcel/PHPExcel.php')) {
                require_once(DIR_SYSTEM . 'library/phpexcel/PHPExcel.php');
            } else {
                // إذا لم تكن المكتبة موجودة، قم بتصدير PDF بدلاً من Excel
                return $this->exportComparisonToPDF($data);
            }
        }
        
        // إنشاء اسم الملف
        $filename = 'quotation_comparison_' . date('Ymd_His') . '.xlsx';
        $filepath = DIR_DOWNLOAD . $filename;
        
        // إنشاء مستند Excel جديد
        $excel = new PHPExcel();
        
        // تعيين خصائص المستند
        $excel->getProperties()
            ->setCreator('AYM ERP System')
            ->setLastModifiedBy('AYM ERP System')
            ->setTitle('مقارنة عروض الأسعار')
            ->setSubject('مقارنة عروض الأسعار')
            ->setDescription('تقرير مقارنة عروض الأسعار من نظام AYM ERP');
        
        $this->load->language('purchase/quotation_comparison');
        
        // إنشاء ورقة العمل الأولى - ملخص المقارنة
        $excel->setActiveSheetIndex(0);
        $excel->getActiveSheet()->setTitle('ملخص المقارنة');
        $sheet = $excel->getActiveSheet();
        
        // تعيين عنوان التقرير
        $sheet->setCellValue('A1', $this->language->get('heading_title'));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells('A1:D1');
        
        $row = 3;
        
        // معلومات طلب الشراء إذا كانت متوفرة
        if (!empty($data['requisition_info'])) {
            $sheet->setCellValue('A' . $row, $this->language->get('text_requisition_info'));
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;
            
            $sheet->setCellValue('A' . $row, $this->language->get('text_requisition_number'));
            $sheet->setCellValue('B' . $row, $data['requisition_info']['requisition_number']);
            $row++;
            
            $sheet->setCellValue('A' . $row, $this->language->get('text_date_required'));
            $sheet->setCellValue('B' . $row, $data['requisition_info']['date_required']);
            $row++;
            
            $sheet->setCellValue('A' . $row, $this->language->get('text_branch'));
            $sheet->setCellValue('B' . $row, $data['requisition_info']['branch_name']);
            $row += 2;
        }
        
        // ملخص المقارنة
        $sheet->setCellValue('A' . $row, $this->language->get('text_comparison_summary'));
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        
        $sheet->setCellValue('A' . $row, $this->language->get('text_total_quotations'));
        $sheet->setCellValue('B' . $row, count($data['quotations']));
        $row++;
        
        // أفضل سعر
        if (!empty($data['best_price'])) {
            $sheet->setCellValue('A' . $row, $this->language->get('text_best_price_supplier'));
            $sheet->setCellValue('B' . $row, $data['best_price']['supplier_name']);
            $row++;
            
            $sheet->setCellValue('A' . $row, $this->language->get('text_price_difference'));
            $sheet->setCellValue('B' . $row, number_format($data['best_price']['price_difference'], 2) . 
                                ' (' . number_format($data['best_price']['price_difference_percentage'], 2) . '%)');
            $row++;
        }
        
        // أفضل قيمة
        if (!empty($data['best_value'])) {
            $sheet->setCellValue('A' . $row, $this->language->get('text_best_value_supplier'));
            $sheet->setCellValue('B' . $row, $data['best_value']['supplier_name']);
            $row += 2;
        }
        
        // إنشاء ورقة عمل جديدة - مقارنة عروض الأسعار
        $excel->createSheet();
        $excel->setActiveSheetIndex(1);
        $excel->getActiveSheet()->setTitle('مقارنة العروض');
        $sheet = $excel->getActiveSheet();
        
        // رؤوس الجدول
        $sheet->setCellValue('A1', $this->language->get('text_criteria'));
        $col = 'B';
        
        foreach ($data['quotations'] as $quotation) {
            $sheet->setCellValue($col . '1', $quotation['supplier_name']);
            $col++;
        }
        
        // تنسيق رؤوس الجدول
        $lastCol = chr(ord('A') + count($data['quotations']));
        $sheet->getStyle('A1:' . $lastCol . '1')->getFont()->setBold(true);
        
        // بيانات الجدول
        $criteria = array(
            'quotation_number' => 'text_quotation_number',
            'date_added' => 'text_quotation_date',
            'validity_date' => 'text_validity_date',
            'payment_terms' => 'text_payment_terms',
            'delivery_terms' => 'text_delivery_terms',
            'total' => 'text_total',
            'tax_total' => 'text_tax_total',
            'grand_total' => 'text_grand_total',
            'supplier_rating' => 'text_supplier_rating',
            'on_time_delivery' => 'text_on_time_delivery'
        );
        
        $row = 2;
        foreach ($criteria as $key => $text) {
            $sheet->setCellValue('A' . $row, $this->language->get($text));
            
            $col = 'B';
            foreach ($data['quotations'] as $quotation) {
                $value = isset($quotation[$key]) ? $quotation[$key] : '';
                $sheet->setCellValue($col . $row, $value);
                $col++;
            }
            
            $row++;
        }
        
        // إنشاء ورقة عمل جديدة لكل منتج
        $productIndex = 2;
        foreach ($data['products'] as $product) {
            $excel->createSheet();
            $excel->setActiveSheetIndex($productIndex);
            $excel->getActiveSheet()->setTitle(substr($product['name'], 0, 30)); // تقصير الاسم ليناسب عنوان الورقة
            $sheet = $excel->getActiveSheet();
            
            // عنوان المنتج
            $sheet->setCellValue('A1', $product['name'] . ' (' . $product['model'] . ')');
            $sheet->getStyle('A1')->getFont()->setBold(true);
            $sheet->mergeCells('A1:D1');
            
            // رؤوس الجدول
            $sheet->setCellValue('A3', $this->language->get('text_supplier'));
            $sheet->setCellValue('B3', $this->language->get('text_price'));
            $sheet->setCellValue('C3', $this->language->get('text_tax'));
            $sheet->setCellValue('D3', $this->language->get('text_total'));
            $sheet->getStyle('A3:D3')->getFont()->setBold(true);
            
            // بيانات المنتج
            $row = 4;
            foreach ($product['suppliers'] as $supplier) {
                $sheet->setCellValue('A' . $row, $supplier['supplier_name']);
                $sheet->setCellValue('B' . $row, $supplier['price']);
                $sheet->setCellValue('C' . $row, $supplier['tax']);
                $sheet->setCellValue('D' . $row, $supplier['total']);
                $row++;
            }
            
            $productIndex++;
        }
        
        // تعيين الورقة الأولى كنشطة
        $excel->setActiveSheetIndex(0);
        
        // حفظ الملف
        $writer = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
        $writer->save($filepath);
        
        return $filepath;
    }
    
    /**
     * الحصول على تقرير مفصل عن تاريخ الموافقات والرفض
     * 
     * @param int $quotation_id معرف عرض السعر
     * @param array $filter معايير التصفية
     * @return array بيانات التقرير
     */
    public function getApprovalHistoryReport($quotation_id, $filter = array()) {
        $this->load->model('purchase/quotation');
        
        // جلب بيانات عرض السعر
        $quotation_info = $this->model_purchase_quotation->getQuotation($quotation_id);
        
        if (!$quotation_info) {
            return array();
        }
        
        // جلب بيانات المورد
        $this->load->model('supplier/supplier');
        $supplier_info = $this->model_supplier_supplier->getSupplier($quotation_info['supplier_id']);
        
        // جلب سجلات تاريخ الموافقات
        $history_records = $this->getApprovalHistory($quotation_id);
        
        // تطبيق معايير التصفية إذا كانت موجودة
        if (!empty($filter['date_start']) && !empty($filter['date_end'])) {
            $date_start = strtotime($filter['date_start'] . ' 00:00:00');
            $date_end = strtotime($filter['date_end'] . ' 23:59:59');
            
            foreach ($history_records as $key => $record) {
                $record_date = strtotime($record['date_added']);
                
                if ($record_date < $date_start || $record_date > $date_end) {
                    unset($history_records[$key]);
                }
            }
            
            // إعادة ترتيب المفاتيح
            $history_records = array_values($history_records);
        }
        
        // إنشاء بيانات التقرير
        $report_data = array(
            'quotation_info' => $quotation_info,
            'supplier_info' => $supplier_info,
            'history_records' => $history_records,
            'summary' => array(
                'total_records' => count($history_records),
                'approvals' => 0,
                'rejections' => 0,
                'po_created' => 0,
                'last_action' => '',
                'last_action_date' => '',
                'last_action_user' => ''
            )
        );
        
        // حساب ملخص الإحصائيات
        foreach ($history_records as $record) {
            if ($record['action'] == 'approve') {
                $report_data['summary']['approvals']++;
            } else if ($record['action'] == 'reject') {
                $report_data['summary']['rejections']++;
            } else if ($record['action'] == 'create_po') {
                $report_data['summary']['po_created']++;
            }
            
            // تحديث آخر إجراء
            if (empty($report_data['summary']['last_action_date']) || 
                strtotime($record['date_added']) > strtotime($report_data['summary']['last_action_date'])) {
                $report_data['summary']['last_action'] = $record['action'];
                $report_data['summary']['last_action_date'] = $record['date_added'];
                $report_data['summary']['last_action_user'] = $record['username'];
            }
        }
        
        return $report_data;
    }
    
    /**
     * إرسال إشعار بالبريد الإلكتروني للموافقة أو الرفض
     * 
     * @param int $quotation_id معرف عرض السعر
     * @param string $action نوع الإجراء (approve, reject)
     * @param array $email_data بيانات البريد الإلكتروني
     * @return bool نجاح العملية
     */
    public function sendApprovalEmail($quotation_id, $action, $email_data) {
        $this->load->model('purchase/quotation');
        
        // جلب بيانات عرض السعر
        $quotation_info = $this->model_purchase_quotation->getQuotation($quotation_id);
        
        if (!$quotation_info) {
            return false;
        }
        
        // جلب بيانات المورد
        $this->load->model('supplier/supplier');
        $supplier_info = $this->model_supplier_supplier->getSupplier($quotation_info['supplier_id']);
        $supplier_name = $supplier_info ? $supplier_info['name'] : '';
        
        // تحديد عنوان ونص البريد الإلكتروني
        $subject = '';
        $message = '';
        
        if ($action == 'approve') {
            $subject = 'تمت الموافقة على عرض السعر #' . $quotation_info['quotation_number'];
            $message = '<p>تمت الموافقة على عرض السعر رقم ' . $quotation_info['quotation_number'] . ' من المورد ' . $supplier_name . '</p>';
            
            if (!empty($email_data['comment'])) {
                $message .= '<p><strong>التعليق:</strong> ' . $email_data['comment'] . '</p>';
            }
            
            $message .= '<p><strong>تفاصيل عرض السعر:</strong></p>';
            $message .= '<ul>';
            $message .= '<li>رقم عرض السعر: ' . $quotation_info['quotation_number'] . '</li>';
            $message .= '<li>المورد: ' . $supplier_name . '</li>';
            $message .= '<li>تاريخ العرض: ' . $quotation_info['date_added'] . '</li>';
            $message .= '<li>تاريخ الصلاحية: ' . $quotation_info['validity_date'] . '</li>';
            $message .= '<li>إجمالي المبلغ: ' . $quotation_info['grand_total'] . '</li>';
            $message .= '</ul>';
            
            $message .= '<p>سيتم إنشاء أمر شراء بناءً على هذا العرض قريبًا.</p>';
            
        } else if ($action == 'reject') {
            $subject = 'تم رفض عرض السعر #' . $quotation_info['quotation_number'];
            $message = '<p>تم رفض عرض السعر رقم ' . $quotation_info['quotation_number'] . ' من المورد ' . $supplier_name . '</p>';
            
            if (!empty($email_data['reason'])) {
                $message .= '<p><strong>سبب الرفض:</strong> ' . $email_data['reason'] . '</p>';
            }
            
            $message .= '<p><strong>تفاصيل عرض السعر:</strong></p>';
            $message .= '<ul>';
            $message .= '<li>رقم عرض السعر: ' . $quotation_info['quotation_number'] . '</li>';
            $message .= '<li>المورد: ' . $supplier_name . '</li>';
            $message .= '<li>تاريخ العرض: ' . $quotation_info['date_added'] . '</li>';
            $message .= '<li>تاريخ الصلاحية: ' . $quotation_info['validity_date'] . '</li>';
            $message .= '<li>إجمالي المبلغ: ' . $quotation_info['grand_total'] . '</li>';
            $message .= '</ul>';
            
            $message .= '<p>يرجى مراجعة سبب الرفض واتخاذ الإجراء المناسب.</p>';
        }
        
        if (empty($subject) || empty($message)) {
            return false;
        }
        
        // إرسال البريد الإلكتروني
        $mail = new Mail();
        $mail->protocol = $this->config->get('config_mail_protocol');
        $mail->parameter = $this->config->get('config_mail_parameter');
        $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
        $mail->smtp_username = $this->config->get('config_mail_smtp_username');
        $mail->smtp_password = $this->config->get('config_mail_smtp_password');
        $mail->smtp_port = $this->config->get('config_mail_smtp_port');
        $mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');
        
        $mail->setTo($email_data['to']);
        $mail->setFrom($this->config->get('config_email'));
        $mail->setSender($this->config->get('config_name'));
        $mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
        $mail->setHtml($message);
        
        // إضافة نسخة إلى المستخدمين المعنيين
        if (!empty($email_data['cc'])) {
            foreach ($email_data['cc'] as $cc_email) {
                $mail->addCc($cc_email);
            }
        }
        
        // إرسال البريد الإلكتروني
        $mail->send();
        
        return true;
    }
    
    /**
     * الحصول على تقرير مفصل عن تاريخ الموافقات والرفض
     * 
     * @param int $quotation_id معرف عرض السعر (اختياري)
     * @param array $filter معايير التصفية (اختياري)
     * @return array بيانات التقرير
     */
    public function getApprovalReport($quotation_id = 0, $filter = array()) {
        $sql = "SELECT h.*, q.quotation_number, q.supplier_id, CONCAT(u.firstname, ' ', u.lastname) AS username, 
                s.name AS supplier_name 
                FROM " . DB_PREFIX . "quotation_approval_history h 
                LEFT JOIN " . DB_PREFIX . "purchase_quotation q ON (h.quotation_id = q.quotation_id) 
                LEFT JOIN " . DB_PREFIX . "user u ON (h.user_id = u.user_id) 
                LEFT JOIN " . DB_PREFIX . "supplier s ON (q.supplier_id = s.supplier_id) 
                WHERE 1=1";
        
        // تطبيق معايير التصفية
        if ($quotation_id > 0) {
            $sql .= " AND h.quotation_id = '" . (int)$quotation_id . "'";
        }
        
        if (!empty($filter['start_date'])) {
            $sql .= " AND DATE(h.date_added) >= '" . $this->db->escape($filter['start_date']) . "'";
        }
        
        if (!empty($filter['end_date'])) {
            $sql .= " AND DATE(h.date_added) <= '" . $this->db->escape($filter['end_date']) . "'";
        }
        
        if (!empty($filter['user_id'])) {
            $sql .= " AND h.user_id = '" . (int)$filter['user_id'] . "'";
        }
        
        if (!empty($filter['action'])) {
            $sql .= " AND h.action = '" . $this->db->escape($filter['action']) . "'";
        }
        
        if (!empty($filter['supplier_id'])) {
            $sql .= " AND q.supplier_id = '" . (int)$filter['supplier_id'] . "'";
        }
        
        // ترتيب النتائج
        $sql .= " ORDER BY h.date_added DESC";
        
        // تحديد عدد النتائج
        if (isset($filter['start']) && isset($filter['limit'])) {
            $sql .= " LIMIT " . (int)$filter['start'] . "," . (int)$filter['limit'];
        }
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    /**
     * الحصول على إجمالي عدد سجلات تاريخ الموافقات والرفض
     * 
     * @param int $quotation_id معرف عرض السعر (اختياري)
     * @param array $filter معايير التصفية (اختياري)
     * @return int عدد السجلات
     */
    public function getTotalApprovalHistory($quotation_id = 0, $filter = array()) {
        $sql = "SELECT COUNT(*) AS total 
                FROM " . DB_PREFIX . "quotation_approval_history h 
                LEFT JOIN " . DB_PREFIX . "purchase_quotation q ON (h.quotation_id = q.quotation_id) 
                WHERE 1=1";
        
        // تطبيق معايير التصفية
        if ($quotation_id > 0) {
            $sql .= " AND h.quotation_id = '" . (int)$quotation_id . "'";
        }
        
        if (!empty($filter['start_date'])) {
            $sql .= " AND DATE(h.date_added) >= '" . $this->db->escape($filter['start_date']) . "'";
        }
        
        if (!empty($filter['end_date'])) {
            $sql .= " AND DATE(h.date_added) <= '" . $this->db->escape($filter['end_date']) . "'";
        }
        
        if (!empty($filter['user_id'])) {
            $sql .= " AND h.user_id = '" . (int)$filter['user_id'] . "'";
        }
        
        if (!empty($filter['action'])) {
            $sql .= " AND h.action = '" . $this->db->escape($filter['action']) . "'";
        }
        
        if (!empty($filter['supplier_id'])) {
            $sql .= " AND q.supplier_id = '" . (int)$filter['supplier_id'] . "'";
        }
        
        $query = $this->db->query($sql);
        
        return $query->row['total'];
    }
    
    /**
     * تصدير نتائج المقارنة بتنسيق HTML
     * 
     * @param array $data بيانات المقارنة
     * @return string محتوى HTML
     */
    public function exportComparisonToHTML($data) {
        $html = '<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>مقارنة عروض الأسعار</title>
    <style>
        body { font-family: Arial, sans-serif; direction: rtl; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: right; }
        th { background-color: #f2f2f2; }
        .best-price { background-color: #d4edda; }
        .best-value { background-color: #cce5ff; }
        h2 { color: #333; }
    </style>
</head>
<body>
    <h1>مقارنة عروض الأسعار</h1>';
        
        // معلومات طلب الشراء إذا كانت متوفرة
        if (!empty($data['requisition_info'])) {
            $html .= '<h2>معلومات طلب الشراء</h2>
            <table>
                <tr>
                    <th>رقم طلب الشراء</th>
                    <th>تاريخ الطلب</th>
                    <th>الفرع</th>
                </tr>
                <tr>
                    <td>' . $data['requisition_info']['requisition_number'] . '</td>
                    <td>' . $data['requisition_info']['date_required'] . '</td>
                    <td>' . $data['requisition_info']['branch_name'] . '</td>
                </tr>
            </table>';
        }
        
        // ملخص المقارنة
        $html .= '<h2>ملخص المقارنة</h2>
        <table>
            <tr>
                <th>إجمالي عروض الأسعار</th>
                <th>أفضل سعر</th>
                <th>فرق السعر</th>
                <th>أفضل قيمة</th>
            </tr>
            <tr>
                <td>' . count($data['quotations']) . '</td>
                <td>' . (isset($data['best_price']['supplier_name']) ? $data['best_price']['supplier_name'] : '-') . '</td>
                <td>' . (isset($data['best_price']['price_difference_percentage']) ? number_format($data['best_price']['price_difference_percentage'], 2) . '%' : '-') . '</td>
                <td>' . (isset($data['best_value']['supplier_name']) ? $data['best_value']['supplier_name'] : '-') . '</td>
            </tr>
        </table>';
        
        // مقارنة عروض الأسعار
        $html .= '<h2>مقارنة عروض الأسعار</h2>
        <table>
            <tr>
                <th>رقم عرض السعر</th>
                <th>المورد</th>
                <th>التاريخ</th>
                <th>تاريخ الصلاحية</th>
                <th>الإجمالي</th>
                <th>الضريبة</th>
                <th>المجموع الكلي</th>
                <th>شروط الدفع</th>
                <th>شروط التسليم</th>
                <th>تقييم المورد</th>
                <th>نسبة التسليم في الموعد</th>
            </tr>';
        
        foreach ($data['quotations'] as $quotation_id => $quotation) {
            $rowClass = '';
            if (isset($data['best_price']['quotation_id']) && $data['best_price']['quotation_id'] == $quotation_id) {
                $rowClass = 'best-price';
            } else if (isset($data['best_value']['quotation_id']) && $data['best_value']['quotation_id'] == $quotation_id) {
                $rowClass = 'best-value';
            }
            
            $html .= '<tr class="' . $rowClass . '">
                <td>' . $quotation['quotation_number'] . '</td>
                <td>' . $quotation['supplier_name'] . '</td>
                <td>' . $quotation['date_added'] . '</td>
                <td>' . $quotation['validity_date'] . '</td>
                <td>' . number_format($quotation['total'], 2) . '</td>
                <td>' . number_format($quotation['tax_total'], 2) . '</td>
                <td>' . number_format($quotation['grand_total'], 2) . '</td>
                <td>' . $quotation['payment_terms'] . '</td>
                <td>' . $quotation['delivery_terms'] . '</td>
                <td>' . number_format($quotation['supplier_rating'], 2) . '</td>
                <td>' . number_format($quotation['on_time_delivery'], 2) . '%</td>
            </tr>';
        }
        
        $html .= '</table>';
        
        // مقارنة المنتجات
        $html .= '<h2>مقارنة المنتجات</h2>';
        
        foreach ($data['products'] as $product_id => $product) {
            $html .= '<h3>' . $product['name'] . ' (' . $product['model'] . ')</h3>
            <table>
                <tr>
                    <th>المورد</th>
                    <th>السعر</th>
                    <th>الضريبة</th>
                    <th>الإجمالي</th>
                </tr>';
            
            foreach ($product['suppliers'] as $supplier_id => $supplier) {
                $html .= '<tr>
                    <td>' . $supplier['supplier_name'] . '</td>
                    <td>' . number_format($supplier['price'], 2) . '</td>
                    <td>' . number_format($supplier['tax'], 2) . '</td>
                    <td>' . number_format($supplier['total'], 2) . '</td>
                </tr>';
            }
            
            $html .= '</table>';
        }
        
        $html .= '</body>
</html>';
        
        return $html;
    }
    
    /**
     * تعيين المستخدمين المعنيين بإشعارات عروض الأسعار
     * 
     * @param int $quotation_id معرف عرض السعر
     * @param array $user_ids معرفات المستخدمين
     * @return bool نجاح العملية
     */
    public function assignNotificationUsers($quotation_id, $user_ids) {
        // التحقق من وجود جدول تعيينات الإشعارات
        $this->db->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "quotation_notification_assignment (
            assignment_id INT(11) NOT NULL AUTO_INCREMENT,
            quotation_id INT(11) NOT NULL,
            user_id INT(11) NOT NULL,
            date_added DATETIME NOT NULL,
            PRIMARY KEY (assignment_id),
            UNIQUE KEY quotation_user (quotation_id, user_id)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
        
        // حذف التعيينات الحالية
        $this->db->query("DELETE FROM " . DB_PREFIX . "quotation_notification_assignment WHERE quotation_id = '" . (int)$quotation_id . "'");
        
        // إضافة التعيينات الجديدة
        foreach ($user_ids as $user_id) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "quotation_notification_assignment SET 
                quotation_id = '" . (int)$quotation_id . "',
                user_id = '" . (int)$user_id . "',
                date_added = NOW()");
        }
        
        return true;
    }
    
    /**
     * الحصول على المستخدمين المعنيين بإشعارات عرض سعر
     * 
     * @param int $quotation_id معرف عرض السعر
     * @return array معرفات المستخدمين
     */
    public function getNotificationUsers($quotation_id) {
        $query = $this->db->query("SELECT user_id FROM " . DB_PREFIX . "quotation_notification_assignment 
            WHERE quotation_id = '" . (int)$quotation_id . "'");
            
        $user_ids = array();
        
        foreach ($query->rows as $row) {
            $user_ids[] = $row['user_id'];
        }
        
        return $user_ids;
    }
    
    /**
     * تصدير نتائج المقارنة بتنسيق CSV
     * 
     * @param array $data بيانات المقارنة
     * @return string مسار ملف CSV المنشأ
     */
    public function exportComparisonToCSV($data) {
        // إنشاء اسم الملف
        $filename = 'quotation_comparison_' . date('Ymd_His') . '.csv';
        $filepath = DIR_DOWNLOAD . $filename;
        
        // فتح الملف للكتابة
        $handle = fopen($filepath, 'w');
        
        // كتابة ترويسة UTF-8 BOM
        fputs($handle, "\xEF\xBB\xBF");
        
        // كتابة عناوين الأعمدة
        $header = array(
            'رقم عرض السعر',
            'المورد',
            'التاريخ',
            'تاريخ الصلاحية',
            'الإجمالي',
            'الضريبة',
            'المجموع الكلي',
            'شروط الدفع',
            'شروط التسليم',
            'تقييم المورد',
            'نسبة التسليم في الموعد'
        );
        
        fputcsv($handle, $header);
        
        // كتابة بيانات عروض الأسعار
        foreach ($data['quotations'] as $quotation) {
            $row = array(
                $quotation['quotation_number'],
                $quotation['supplier_name'],
                $quotation['date_added'],
                $quotation['validity_date'],
                $quotation['total'],
                $quotation['tax_total'],
                $quotation['grand_total'],
                $quotation['payment_terms'],
                $quotation['delivery_terms'],
                $quotation['supplier_rating'],
                $quotation['on_time_delivery']
            );
            
            fputcsv($handle, $row);
        }
        
        // إغلاق الملف
        fclose($handle);
        
        return $filepath;
    }
    
    /**
     * تصدير نتائج المقارنة بتنسيق CSV
     * 
     * @param array $data بيانات المقارنة
     * @return string مسار ملف CSV المنشأ
     */
    public function exportComparisonToCSV($data) {
        // إنشاء اسم الملف
        $filename = 'quotation_comparison_' . date('Ymd_His') . '.csv';
        $filepath = DIR_DOWNLOAD . $filename;
        
        $this->load->language('purchase/quotation_comparison');
        
        // فتح الملف للكتابة
        $handle = fopen($filepath, 'w');
        
        // إضافة BOM للتوافق مع اللغة العربية في Excel
        fputs($handle, "\xEF\xBB\xBF");
        
        // كتابة عنوان التقرير
        fputcsv($handle, array($this->language->get('heading_title')));
        fputcsv($handle, array('')); // سطر فارغ
        
        // معلومات طلب الشراء إذا كانت متوفرة
        if (!empty($data['requisition_info'])) {
            fputcsv($handle, array($this->language->get('text_requisition_info')));
            fputcsv($handle, array($this->language->get('text_requisition_number'), $data['requisition_info']['requisition_number']));
            fputcsv($handle, array($this->language->get('text_date_required'), $data['requisition_info']['date_required']));
            fputcsv($handle, array($this->language->get('text_branch'), $data['requisition_info']['branch_name']));
            fputcsv($handle, array('')); // سطر فارغ
        }
        
        // ملخص المقارنة
        fputcsv($handle, array($this->language->get('text_comparison_summary')));
        fputcsv($handle, array($this->language->get('text_total_quotations'), count($data['quotations'])));
        
        // أفضل سعر
        if (!empty($data['best_price'])) {
            fputcsv($handle, array($this->language->get('text_best_price_supplier'), $data['best_price']['supplier_name']));
            fputcsv($handle, array(
                $this->language->get('text_price_difference'), 
                number_format($data['best_price']['price_difference'], 2) . ' (' . 
                number_format($data['best_price']['price_difference_percentage'], 2) . '%)'
            ));
        }
        
        // أفضل قيمة
        if (!empty($data['best_value'])) {
            fputcsv($handle, array($this->language->get('text_best_value_supplier'), $data['best_value']['supplier_name']));
        }
        
        fputcsv($handle, array('')); // سطر فارغ
        
        // مقارنة عروض الأسعار
        fputcsv($handle, array($this->language->get('text_quotation_comparison')));
        
        // رؤوس الجدول
        $header = array($this->language->get('text_criteria'));
        foreach ($data['quotations'] as $quotation) {
            $header[] = $quotation['supplier_name'];
        }
        fputcsv($handle, $header);
        
        // بيانات الجدول
        $criteria = array(
            'quotation_number' => 'text_quotation_number',
            'date_added' => 'text_quotation_date',
            'validity_date' => 'text_validity_date',
            'payment_terms' => 'text_payment_terms',
            'delivery_terms' => 'text_delivery_terms',
            'total' => 'text_total',
            'tax_total' => 'text_tax_total',
            'grand_total' => 'text_grand_total',
            'supplier_rating' => 'text_supplier_rating',
            'on_time_delivery' => 'text_on_time_delivery'
        );
        
        foreach ($criteria as $key => $text) {
            $row = array($this->language->get($text));
            
            foreach ($data['quotations'] as $quotation) {
                $value = isset($quotation[$key]) ? $quotation[$key] : '';
                
                // تنسيق القيم المالية
                if (in_array($key, array('total', 'tax_total', 'grand_total'))) {
                    $value = number_format($value, 2);
                }
                
                // تنسيق النسب المئوية
                if (in_array($key, array('supplier_rating', 'on_time_delivery'))) {
                    $value = number_format($value, 2) . '%';
                }
                
                $row[] = $value;
            }
            
            fputcsv($handle, $row);
        }
        
        fputcsv($handle, array('')); // سطر فارغ
        
        // مقارنة المنتجات
        fputcsv($handle, array($this->language->get('text_product_comparison')));
        
        foreach ($data['products'] as $product) {
            fputcsv($handle, array($product['name'] . ' (' . $product['model'] . ')'));
            
            // رؤوس جدول المنتج
            fputcsv($handle, array(
                $this->language->get('text_supplier'),
                $this->language->get('text_price'),
                $this->language->get('text_tax'),
                $this->language->get('text_total')
            ));
            
            // بيانات المنتج
            foreach ($product['suppliers'] as $supplier) {
                fputcsv($handle, array(
                    $supplier['supplier_name'],
                    number_format($supplier['price'], 2),
                    number_format($supplier['tax'], 2),
                    number_format($supplier['total'], 2)
                ));
            }
            
            fputcsv($handle, array('')); // سطر فارغ
        }
        
        // إغلاق الملف
        fclose($handle);
        
        return $filepath;
    }
    
    /**
     * تصدير نتائج المقارنة بتنسيق Excel
     * 
     * @param array $data بيانات المقارنة
     * @return string مسار ملف Excel المنشأ
     */
    public function exportComparisonToExcel($data) {
        // التحقق من وجود مكتبة PHPExcel
        if (!class_exists('PHPExcel')) {
            // تضمين مكتبة PHPExcel إذا لم تكن موجودة
            if (file_exists(DIR_SYSTEM . 'library/phpexcel/PHPExcel.php')) {
                require_once(DIR_SYSTEM . 'library/phpexcel/PHPExcel.php');
            } else {
                // إذا لم تكن المكتبة موجودة، قم بتصدير PDF بدلاً من Excel
                return $this->exportComparisonToPDF($data);
            }
        }
        
        // إنشاء اسم الملف
        $filename = 'quotation_comparison_' . date('Ymd_His') . '.xlsx';
        $filepath = DIR_DOWNLOAD . $filename;
        
        // إنشاء مستند Excel جديد
        $excel = new PHPExcel();
        
        // تعيين خصائص المستند
        $excel->getProperties()
            ->setCreator('AYM ERP System')
            ->setLastModifiedBy('AYM ERP System')
            ->setTitle('مقارنة عروض الأسعار')
            ->setSubject('مقارنة عروض الأسعار')
            ->setDescription('تقرير مقارنة عروض الأسعار من نظام AYM ERP');
        
        $this->load->language('purchase/quotation_comparison');
        
        // إنشاء ورقة العمل الأولى - ملخص المقارنة
        $excel->setActiveSheetIndex(0);
        $excel->getActiveSheet()->setTitle('ملخص المقارنة');
        $sheet = $excel->getActiveSheet();
        
        // تعيين عنوان التقرير
        $sheet->setCellValue('A1', $this->language->get('heading_title'));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells('A1:D1');
        
        $row = 3;
        
        // معلومات طلب الشراء إذا كانت متوفرة
        if (!empty($data['requisition_info'])) {
            $sheet->setCellValue('A' . $row, $this->language->get('text_requisition_info'));
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;
            
            $sheet->setCellValue('A' . $row, $this->language->get('text_requisition_number'));
            $sheet->setCellValue('B' . $row, $data['requisition_info']['requisition_number']);
            $row++;
            
            $sheet->setCellValue('A' . $row, $this->language->get('text_date_required'));
            $sheet->setCellValue('B' . $row, $data['requisition_info']['date_required']);
            $row++;
            
            $sheet->setCellValue('A' . $row, $this->language->get('text_branch'));
            $sheet->setCellValue('B' . $row, $data['requisition_info']['branch_name']);
            $row += 2;
        }
        
        // ملخص المقارنة
        $sheet->setCellValue('A' . $row, $this->language->get('text_comparison_summary'));
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        
        $sheet->setCellValue('A' . $row, $this->language->get('text_total_quotations'));
        $sheet->setCellValue('B' . $row, count($data['quotations']));
        $row++;
        
        // أفضل سعر
        if (!empty($data['best_price'])) {
            $sheet->setCellValue('A' . $row, $this->language->get('text_best_price_supplier'));
            $sheet->setCellValue('B' . $row, $data['best_price']['supplier_name']);
            $row++;
            
            $sheet->setCellValue('A' . $row, $this->language->get('text_price_difference'));
            $sheet->setCellValue('B' . $row, number_format($data['best_price']['price_difference'], 2) . 
                                ' (' . number_format($data['best_price']['price_difference_percentage'], 2) . '%)');
            $row++;
        }
        
        // أفضل قيمة
        if (!empty($data['best_value'])) {
            $sheet->setCellValue('A' . $row, $this->language->get('text_best_value_supplier'));
            $sheet->setCellValue('B' . $row, $data['best_value']['supplier_name']);
            $row += 2;
        }
        
        // إنشاء ورقة عمل جديدة - مقارنة عروض الأسعار
        $excel->createSheet();
        $excel->setActiveSheetIndex(1);
        $excel->getActiveSheet()->setTitle('مقارنة العروض');
        $sheet = $excel->getActiveSheet();
        
        // رؤوس الجدول
        $sheet->setCellValue('A1', $this->language->get('text_criteria'));
        $col = 'B';
        
        foreach ($data['quotations'] as $quotation) {
            $sheet->setCellValue($col . '1', $quotation['supplier_name']);
            $col++;
        }
        
        // تنسيق رؤوس الجدول
        $lastCol = chr(ord('A') + count($data['quotations']));
        $sheet->getStyle('A1:' . $lastCol . '1')->getFont()->setBold(true);
        
        // بيانات الجدول
        $criteria = array(
            'quotation_number' => 'text_quotation_number',
            'date_added' => 'text_quotation_date',
            'validity_date' => 'text_validity_date',
            'payment_terms' => 'text_payment_terms',
            'delivery_terms' => 'text_delivery_terms',
            'total' => 'text_total',
            'tax_total' => 'text_tax_total',
            'grand_total' => 'text_grand_total',
            'supplier_rating' => 'text_supplier_rating',
            'on_time_delivery' => 'text_on_time_delivery'
        );
        
        $row = 2;
        foreach ($criteria as $key => $text) {
            $sheet->setCellValue('A' . $row, $this->language->get($text));
            
            $col = 'B';
            foreach ($data['quotations'] as $quotation) {
                $value = isset($quotation[$key]) ? $quotation[$key] : '';
                $sheet->setCellValue($col . $row, $value);
                $col++;
            }
            
            $row++;
        }
        
        // إنشاء ورقة عمل جديدة لكل منتج
        $productIndex = 2;
        foreach ($data['products'] as $product) {
            $excel->createSheet();
            $excel->setActiveSheetIndex($productIndex);
            $excel->getActiveSheet()->setTitle(substr($product['name'], 0, 30)); // تقصير الاسم ليناسب عنوان الورقة
            $sheet = $excel->getActiveSheet();
            
            // عنوان المنتج
            $sheet->setCellValue('A1', $product['name'] . ' (' . $product['model'] . ')');
            $sheet->getStyle('A1')->getFont()->setBold(true);
            $sheet->mergeCells('A1:D1');
            
            // رؤوس الجدول
            $sheet->setCellValue('A3', $this->language->get('text_supplier'));
            $sheet->setCellValue('B3', $this->language->get('text_price'));
            $sheet->setCellValue('C3', $this->language->get('text_tax'));
            $sheet->setCellValue('D3', $this->language->get('text_total'));
            $sheet->getStyle('A3:D3')->getFont()->setBold(true);
            
            // بيانات المنتج
            $row = 4;
            foreach ($product['suppliers'] as $supplier) {
                $sheet->setCellValue('A' . $row, $supplier['supplier_name']);
                $sheet->setCellValue('B' . $row, $supplier['price']);
                $sheet->setCellValue('C' . $row, $supplier['tax']);
                $sheet->setCellValue('D' . $row, $supplier['total']);
                $row++;
            }
            
            $productIndex++;
        }
        
        // تعيين الورقة الأولى كنشطة
        $excel->setActiveSheetIndex(0);
        
        // حفظ الملف
        $writer = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
        $writer->save($filepath);
        
        return $filepath;
    }
    
    /**
     * الحصول على تقرير مفصل عن تاريخ الموافقات والرفض
     * 
     * @param int $quotation_id معرف عرض السعر
     * @param array $filter معايير التصفية
     * @return array بيانات التقرير
     */
    public function getApprovalHistoryReport($quotation_id, $filter = array()) {
        $this->load->model('purchase/quotation');
        
        // جلب بيانات عرض السعر
        $quotation_info = $this->model_purchase_quotation->getQuotation($quotation_id);
        
        if (!$quotation_info) {
            return array();
        }
        
        // جلب بيانات المورد
        $this->load->model('supplier/supplier');
        $supplier_info = $this->model_supplier_supplier->getSupplier($quotation_info['supplier_id']);
        
        // جلب سجلات تاريخ الموافقات
        $history_records = $this->getApprovalHistory($quotation_id);
        
        // تطبيق معايير التصفية إذا كانت موجودة
        if (!empty($filter['date_start']) && !empty($filter['date_end'])) {
            $date_start = strtotime($filter['date_start'] . ' 00:00:00');
            $date_end = strtotime($filter['date_end'] . ' 23:59:59');
            
            foreach ($history_records as $key => $record) {
                $record_date = strtotime($record['date_added']);
                
                if ($record_date < $date_start || $record_date > $date_end) {
                    unset($history_records[$key]);
                }
            }
            
            // إعادة ترتيب المفاتيح
            $history_records = array_values($history_records);
        }
        
        // إنشاء بيانات التقرير
        $report_data = array(
            'quotation_info' => $quotation_info,
            'supplier_info' => $supplier_info,
            'history_records' => $history_records,
            'summary' => array(
                'total_records' => count($history_records),
                'approvals' => 0,
                'rejections' => 0,
                'po_created' => 0,
                'last_action' => '',
                'last_action_date' => '',
                'last_action_user' => ''
            )
        );
        
        // حساب ملخص الإحصائيات
        foreach ($history_records as $record) {
            if ($record['action'] == 'approve') {
                $report_data['summary']['approvals']++;
            } else if ($record['action'] == 'reject') {
                $report_data['summary']['rejections']++;
            } else if ($record['action'] == 'create_po') {
                $report_data['summary']['po_created']++;
            }
            
            // تحديث آخر إجراء
            if (empty($report_data['summary']['last_action_date']) || 
                strtotime($record['date_added']) > strtotime($report_data['summary']['last_action_date'])) {
                $report_data['summary']['last_action'] = $record['action'];
                $report_data['summary']['last_action_date'] = $record['date_added'];
                $report_data['summary']['last_action_user'] = $record['username'];
            }
        }
        
        return $report_data;
    }
    
    /**
     * إرسال إشعار بالبريد الإلكتروني للموافقة أو الرفض
     * 
     * @param int $quotation_id معرف عرض السعر
     * @param string $action نوع الإجراء (approve, reject)
     * @param array $email_data بيانات البريد الإلكتروني
     * @return bool نجاح العملية
     */
    public function sendApprovalEmail($quotation_id, $action, $email_data) {
        $this->load->model('purchase/quotation');
        
        // جلب بيانات عرض السعر
        $quotation_info = $this->model_purchase_quotation->getQuotation($quotation_id);
        
        if (!$quotation_info) {
            return false;
        }
        
        // جلب بيانات المورد
        $this->load->model('supplier/supplier');
        $supplier_info = $this->model_supplier_supplier->getSupplier($quotation_info['supplier_id']);
        $supplier_name = $supplier_info ? $supplier_info['name'] : '';
        
        // تحديد عنوان ونص البريد الإلكتروني
        $subject = '';
        $message = '';
        
        if ($action == 'approve') {
            $subject = 'تمت الموافقة على عرض السعر #' . $quotation_info['quotation_number'];
            $message = '<p>تمت الموافقة على عرض السعر رقم ' . $quotation_info['quotation_number'] . ' من المورد ' . $supplier_name . '</p>';
            
            if (!empty($email_data['comment'])) {
                $message .= '<p><strong>التعليق:</strong> ' . $email_data['comment'] . '</p>';
            }
            
            $message .= '<p><strong>تفاصيل عرض السعر:</strong></p>';
            $message .= '<ul>';
            $message .= '<li>رقم عرض السعر: ' . $quotation_info['quotation_number'] . '</li>';
            $message .= '<li>المورد: ' . $supplier_name . '</li>';
            $message .= '<li>تاريخ العرض: ' . $quotation_info['date_added'] . '</li>';
            $message .= '<li>تاريخ الصلاحية: ' . $quotation_info['validity_date'] . '</li>';
            $message .= '<li>إجمالي المبلغ: ' . $quotation_info['grand_total'] . '</li>';
            $message .= '</ul>';
            
            $message .= '<p>سيتم إنشاء أمر شراء بناءً على هذا العرض قريبًا.</p>';
            
        } else if ($action == 'reject') {
            $subject = 'تم رفض عرض السعر #' . $quotation_info['quotation_number'];
            $message = '<p>تم رفض عرض السعر رقم ' . $quotation_info['quotation_number'] . ' من المورد ' . $supplier_name . '</p>';
            
            if (!empty($email_data['reason'])) {
                $message .= '<p><strong>سبب الرفض:</strong> ' . $email_data['reason'] . '</p>';
            }
            
            $message .= '<p><strong>تفاصيل عرض السعر:</strong></p>';
            $message .= '<ul>';
            $message .= '<li>رقم عرض السعر: ' . $quotation_info['quotation_number'] . '</li>';
            $message .= '<li>المورد: ' . $supplier_name . '</li>';
            $message .= '<li>تاريخ العرض: ' . $quotation_info['date_added'] . '</li>';
            $message .= '<li>تاريخ الصلاحية: ' . $quotation_info['validity_date'] . '</li>';
            $message .= '<li>إجمالي المبلغ: ' . $quotation_info['grand_total'] . '</li>';
            $message .= '</ul>';
            
            $message .= '<p>يرجى مراجعة سبب الرفض واتخاذ الإجراء المناسب.</p>';
        }
        
        if (empty($subject) || empty($message)) {
            return false;
        }
        
        // إرسال البريد الإلكتروني
        $mail = new Mail();
        $mail->protocol = $this->config->get('config_mail_protocol');
        $mail->parameter = $this->config->get('config_mail_parameter');
        $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
        $mail->smtp_username = $this->config->get('config_mail_smtp_username');
        $mail->smtp_password = $this->config->get('config_mail_smtp_password');
        $mail->smtp_port = $this->config->get('config_mail_smtp_port');
        $mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');
        
        $mail->setTo($email_data['to']);
        $mail->setFrom($this->config->get('config_email'));
        $mail->setSender($this->config->get('config_name'));
        $mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
        $mail->setHtml($message);
        
        // إضافة نسخة إلى المستخدمين المعنيين
        if (!empty($email_data['cc'])) {
            foreach ($email_data['cc'] as $cc_email) {
                $mail->addCc($cc_email);
            }
        }
        
        // إرسال البريد الإلكتروني
        $mail->send();
        
        return true;
    }
    
    /**
     * الحصول على تقرير مفصل عن تاريخ الموافقات والرفض
     * 
     * @param int $quotation_id معرف عرض السعر (اختياري)
     * @param array $filter معايير التصفية (اختياري)
     * @return array بيانات التقرير
     */
    public function getApprovalReport($quotation_id = 0, $filter = array()) {
        $sql = "SELECT h.*, q.quotation_number, q.supplier_id, CONCAT(u.firstname, ' ', u.lastname) AS username, 
                s.name AS supplier_name 
                FROM " . DB_PREFIX . "quotation_approval_history h 
                LEFT JOIN " . DB_PREFIX . "purchase_quotation q ON (h.quotation_id = q.quotation_id) 
                LEFT JOIN " . DB_PREFIX . "user u ON (h.user_id = u.user_id) 
                LEFT JOIN " . DB_PREFIX . "supplier s ON (q.supplier_id = s.supplier_id) 
                WHERE 1=1";
        
        // تطبيق معايير التصفية
        if ($quotation_id > 0) {
            $sql .= " AND h.quotation_id = '" . (int)$quotation_id . "'";
        }
        
        if (!empty($filter['start_date'])) {
            $sql .= " AND DATE(h.date_added) >= '" . $this->db->escape($filter['start_date']) . "'";
        }
        
        if (!empty($filter['end_date'])) {
            $sql .= " AND DATE(h.date_added) <= '" . $this->db->escape($filter['end_date']) . "'";
        }
        
        if (!empty($filter['user_id'])) {
            $sql .= " AND h.user_id = '" . (int)$filter['user_id'] . "'";
        }
        
        if (!empty($filter['action'])) {
            $sql .= " AND h.action = '" . $this->db->escape($filter['action']) . "'";
        }
        
        if (!empty($filter['supplier_id'])) {
            $sql .= " AND q.supplier_id = '" . (int)$filter['supplier_id'] . "'";
        }
        
        // ترتيب النتائج
        $sql .= " ORDER BY h.date_added DESC";
        
        // تحديد عدد النتائج
        if (isset($filter['start']) && isset($filter['limit'])) {
            $sql .= " LIMIT " . (int)$filter['start'] . "," . (int)$filter['limit'];
        }
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    /**
     * الحصول على إجمالي عدد سجلات تاريخ الموافقات والرفض
     * 
     * @param int $quotation_id معرف عرض السعر (اختياري)
     * @param array $filter معايير التصفية (اختياري)
     * @return int عدد السجلات
     */
    public function getTotalApprovalHistory($quotation_id = 0, $filter = array()) {
        $sql = "SELECT COUNT(*) AS total 
                FROM " . DB_PREFIX . "quotation_approval_history h 
                LEFT JOIN " . DB_PREFIX . "purchase_quotation q ON (h.quotation_id = q.quotation_id) 
                WHERE 1=1";
        
        // تطبيق معايير التصفية
        if ($quotation_id > 0) {
            $sql .= " AND h.quotation_id = '" . (int)$quotation_id . "'";
        }
        
        if (!empty($filter['start_date'])) {
            $sql .= " AND DATE(h.date_added) >= '" . $this->db->escape($filter['start_date']) . "'";
        }
        
        if (!empty($filter['end_date'])) {
            $sql .= " AND DATE(h.date_added) <= '" . $this->db->escape($filter['end_date']) . "'";
        }
        
        if (!empty($filter['user_id'])) {
            $sql .= " AND h.user_id = '" . (int)$filter['user_id'] . "'";
        }
        
        if (!empty($filter['action'])) {
            $sql .= " AND h.action = '" . $this->db->escape($filter['action']) . "'";
        }
        
        if (!empty($filter['supplier_id'])) {
            $sql .= " AND q.supplier_id = '" . (int)$filter['supplier_id'] . "'";
        }
        
        $query = $this->db->query($sql);
        
        return $query->row['total'];
    }
    
    /**
     * تصدير نتائج المقارنة بتنسيق HTML
     * 
     * @param array $data بيانات المقارنة
     * @return string محتوى HTML
     */
    public function exportComparisonToHTML($data) {
        $html = '<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>مقارنة عروض الأسعار</title>
    <style>
        body { font-family: Arial, sans-serif; direction: rtl; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: right; }
        th { background-color: #f2f2f2; }
        .best-price { background-color: #d4edda; }
        .best-value { background-color: #cce5ff; }
        h2 { color: #333; }
    </style>
</head>
<body>
    <h1>مقارنة عروض الأسعار</h1>';
        
        // معلومات طلب الشراء إذا كانت متوفرة
        if (!empty($data['requisition_info'])) {
            $html .= '<h2>معلومات طلب الشراء</h2>
            <table>
                <tr>
                    <th>رقم طلب الشراء</th>
                    <th>تاريخ الطلب</th>
                    <th>الفرع</th>
                </tr>
                <tr>
                    <td>' . $data['requisition_info']['requisition_number'] . '</td>
                    <td>' . $data['requisition_info']['date_required'] . '</td>
                    <td>' . $data['requisition_info']['branch_name'] . '</td>
                </tr>
            </table>';
        }
        
        // ملخص المقارنة
        $html .= '<h2>ملخص المقارنة</h2>
        <table>
            <tr>
                <th>إجمالي عروض الأسعار</th>
                <th>أفضل سعر</th>
                <th>فرق السعر</th>
                <th>أفضل قيمة</th>
            </tr>
            <tr>
                <td>' . count($data['quotations']) . '</td>
                <td>' . (isset($data['best_price']['supplier_name']) ? $data['best_price']['supplier_name'] : '-') . '</td>
                <td>' . (isset($data['best_price']['price_difference_percentage']) ? number_format($data['best_price']['price_difference_percentage'], 2) . '%' : '-') . '</td>
                <td>' . (isset($data['best_value']['supplier_name']) ? $data['best_value']['supplier_name'] : '-') . '</td>
            </tr>
        </table>';
        
        // مقارنة عروض الأسعار
        $html .= '<h2>مقارنة عروض الأسعار</h2>
        <table>
            <tr>
                <th>رقم عرض السعر</th>
                <th>المورد</th>
                <th>التاريخ</th>
                <th>تاريخ الصلاحية</th>
                <th>الإجمالي</th>
                <th>الضريبة</th>
                <th>المجموع الكلي</th>
                <th>شروط الدفع</th>
                <th>شروط التسليم</th>
                <th>تقييم المورد</th>
                <th>نسبة التسليم في الموعد</th>
            </tr>';
        
        foreach ($data['quotations'] as $quotation_id => $quotation) {
            $rowClass = '';
            if (isset($data['best_price']['quotation_id']) && $data['best_price']['quotation_id'] == $quotation_id) {
                $rowClass = 'best-price';
            } else if (isset($data['best_value']['quotation_id']) && $data['best_value']['quotation_id'] == $quotation_id) {
                $rowClass = 'best-value';
            }
            
            $html .= '<tr class="' . $rowClass . '">
                <td>' . $quotation['quotation_number'] . '</td>
                <td>' . $quotation['supplier_name'] . '</td>
                <td>' . $quotation['date_added'] . '</td>
                <td>' . $quotation['validity_date'] . '</td>
                <td>' . number_format($quotation['total'], 2) . '</td>
                <td>' . number_format($quotation['tax_total'], 2) . '</td>
                <td>' . number_format($quotation['grand_total'], 2) . '</td>
                <td>' . $quotation['payment_terms'] . '</td>
                <td>' . $quotation['delivery_terms'] . '</td>
                <td>' . number_format($quotation['supplier_rating'], 2) . '</td>
                <td>' . number_format($quotation['on_time_delivery'], 2) . '%</td>
            </tr>';
        }
        
        $html .= '</table>';
        
        // مقارنة المنتجات
        $html .= '<h2>مقارنة المنتجات</h2>';
        
        foreach ($data['products'] as $product_id => $product) {
            $html .= '<h3>' . $product['name'] . ' (' . $product['model'] . ')</h3>
            <table>
                <tr>
                    <th>المورد</th>
                    <th>السعر</th>
                    <th>الضريبة</th>
                    <th>الإجمالي</th>
                </tr>';
            
            foreach ($product['suppliers'] as $supplier_id => $supplier) {
                $html .= '<tr>
                    <td>' . $supplier['supplier_name'] . '</td>
                    <td>' . number_format($supplier['price'], 2) . '</td>
                    <td>' . number_format($supplier['tax'], 2) . '</td>
                    <td>' . number_format($supplier['total'], 2) . '</td>
                </tr>';
            }
            
            $html .= '</table>';
        }
        
        $html .= '</body>
</html>';
        
        return $html;
    }
    
    /**
     * تعيين المستخدمين المعنيين بإشعارات عروض الأسعار
     * 
     * @param int $quotation_id معرف عرض السعر
     * @param array $user_ids معرفات المستخدمين
     * @return bool نجاح العملية
     */
    public function assignNotificationUsers($quotation_id, $user_ids) {
        // التحقق من وجود جدول تعيينات الإشعارات
        $this->db->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "quotation_notification_assignment (
            assignment_id INT(11) NOT NULL AUTO_INCREMENT,
            quotation_id INT(11) NOT NULL,
            user_id INT(11) NOT NULL,
            date_added DATETIME NOT NULL,
            PRIMARY KEY (assignment_id),
            UNIQUE KEY quotation_user (quotation_id, user_id)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
        
        // حذف التعيينات الحالية
        $this->db->query("DELETE FROM " . DB_PREFIX . "quotation_notification_assignment WHERE quotation_id = '" . (int)$quotation_id . "'");
        
        // إضافة التعيينات الجديدة
        foreach ($user_ids as $user_id) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "quotation_notification_assignment SET 
                quotation_id = '" . (int)$quotation_id . "',
                user_id = '" . (int)$user_id . "',
                date_added = NOW()");
        }
        
        return true;
    }
    
    /**
     * الحصول على المستخدمين المعنيين بإشعارات عرض سعر
     * 
     * @param int $quotation_id معرف عرض السعر
     * @return array معرفات المستخدمين
     */
    public function getNotificationUsers($quotation_id) {
        $query = $this->db->query("SELECT user_id FROM " . DB_PREFIX . "quotation_notification_assignment 
            WHERE quotation_id = '" . (int)$quotation_id . "'");
            
        $user_ids = array();
        
        foreach ($query->rows as $row) {
            $user_ids[] = $row['user_id'];
        }
        
        return $user_ids;
    }
    
    /**
     * إنشاء أمر شراء من عرض سعر
     * 
     * @param int $quotation_id معرف عرض السعر
     * @param int $user_id معرف المستخدم الذي قام بإنشاء أمر الشراء
     * @return int معرف أمر الشراء الجديد أو 0 في حالة الفشل
     */
    public function createPurchaseOrderFromQuotation($quotation_id, $user_id = 0) {
        $this->load->model('purchase/quotation');
        $this->load->model('purchase/order');
        
        // جلب بيانات عرض السعر
        $quotation_info = $this->model_purchase_quotation->getQuotation($quotation_id);
        
        if (!$quotation_info) {
            return 0;
        }
        
        // تحضير بيانات أمر الشراء
        $order_data = array(
            'supplier_id' => $quotation_info['supplier_id'],
            'quotation_id' => $quotation_id,
            'requisition_id' => $quotation_info['requisition_id'],
            'branch_id' => $quotation_info['branch_id'],
            'currency_id' => $quotation_info['currency_id'],
            'currency_code' => $quotation_info['currency_code'],
            'currency_value' => $quotation_info['currency_value'],
            'payment_terms' => $quotation_info['payment_terms'],
            'delivery_terms' => $quotation_info['delivery_terms'],
            'shipping_method' => $quotation_info['shipping_method'],
            'shipping_address' => $quotation_info['shipping_address'],
            'comment' => $quotation_info['comment'],
            'tax_included' => $quotation_info['tax_included'],
            'tax_rate' => $quotation_info['tax_rate'],
            'status' => 1, // حالة أمر شراء جديد
            'date_required' => $quotation_info['date_required'],
            'total' => $quotation_info['total'],
            'tax_total' => $quotation_info['tax_total'],
            'grand_total' => $quotation_info['grand_total']
        );
        
        // جلب منتجات عرض السعر
        $products = $this->model_purchase_quotation->getQuotationProducts($quotation_id);
        $order_data['products'] = $products;
        
        // إنشاء أمر الشراء
        if (method_exists($this->model_purchase_order, 'addOrder')) {
            $order_id = $this->model_purchase_order->addOrder($order_data);
            
            // تحديث حالة عرض السعر إلى "تمت الموافقة"
            $this->model_purchase_quotation->updateQuotationStatus($quotation_id, 3); // 3 = تمت الموافقة
            
            if ($order_id) {
                // إضافة سجل لإنشاء أمر الشراء
                $this->addApprovalHistory($quotation_id, $user_id, 'create_po', 'تم إنشاء أمر شراء برقم: ' . $order_id);
                
                // إرسال إشعار بإنشاء أمر الشراء
                $this->sendPurchaseOrderNotification($quotation_id, $order_id, $user_id);
            }
            
            return $order_id;
        }
        
        return 0;
    }
    
    /**
     * الموافقة على عرض سعر
     * 
     * @param int $quotation_id معرف عرض السعر
     * @param int $user_id معرف المستخدم الذي قام بالموافقة
     * @param string $comment تعليق الموافقة
     * @return bool نجاح العملية
     */
    public function approveQuotation($quotation_id, $user_id = 0, $comment = '') {
        $this->load->model('purchase/quotation');
        
        // تحديث حالة عرض السعر إلى "تمت الموافقة"
        if (method_exists($this->model_purchase_quotation, 'updateQuotationStatus')) {
            $result = $this->model_purchase_quotation->updateQuotationStatus($quotation_id, 3); // 3 = تمت الموافقة
            
            if ($result) {
                // إضافة سجل للموافقة
                $this->addApprovalHistory($quotation_id, $user_id, 'approve', $comment);
                
                // إرسال إشعار بالموافقة
                $this->sendApprovalNotification($quotation_id, 'approve', $user_id, $comment);
            }
            
            return $result;
        }
        
        return false;
    }
    
    /**
     * رفض عرض سعر
     * 
     * @param int $quotation_id معرف عرض السعر
     * @param int $user_id معرف المستخدم الذي قام بالرفض
     * @param string $reason سبب الرفض
     * @return bool نجاح العملية
     */
    public function rejectQuotation($quotation_id, $user_id = 0, $reason = '') {
        $this->load->model('purchase/quotation');
        
        // تحديث حالة عرض السعر إلى "مرفوض"
        if (method_exists($this->model_purchase_quotation, 'updateQuotationStatus')) {
            $result = $this->model_purchase_quotation->updateQuotationStatus($quotation_id, 4); // 4 = مرفوض
            
            // إضافة سبب الرفض إذا كان متاحًا
            if ($result && !empty($reason) && method_exists($this->model_purchase_quotation, 'addQuotationHistory')) {
                $this->model_purchase_quotation->addQuotationHistory($quotation_id, 4, $reason);
                
                // إضافة سجل للرفض
                $this->addApprovalHistory($quotation_id, $user_id, 'reject', $reason);
                
                // إرسال إشعار بالرفض
                $this->sendApprovalNotification($quotation_id, 'reject', $user_id, $reason);
            }
            
            return $result;
        }
        
        return false;
    }
}