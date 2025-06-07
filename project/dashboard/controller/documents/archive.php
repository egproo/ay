<?php
/**
 * نظام أرشيف المستندات المتقدم
 * Advanced Document Archive Controller
 * 
 * نظام أرشفة ذكي للمستندات مع تكامل مع catalog/inventory وworkflow
 * مطور بمستوى عالمي لتفوق على Odoo
 * 
 * @package    AYM ERP
 * @author     AYM ERP Development Team
 * @copyright  2024 AYM ERP
 * @license    Proprietary
 * @version    1.0.0
 * @link       https://aym-erp.com
 * @since      2024-12-19
 */

class ControllerDocumentsArchive extends Controller {
    
    /**
     * @var array خطأ في النظام
     */
    private $error = array();
    
    /**
     * عرض صفحة أرشيف المستندات الرئيسية
     */
    public function index() {
        $this->load->language('documents/archive');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('access', 'documents/archive')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('documents/archive', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        // تحميل المستندات المؤرشفة
        $this->load->model('documents/archive');
        
        $filter_data = array(
            'start' => 0,
            'limit' => 20
        );
        
        // تطبيق الفلاتر
        if (isset($this->request->get['filter_category'])) {
            $filter_data['filter_category'] = $this->request->get['filter_category'];
        }
        
        if (isset($this->request->get['filter_type'])) {
            $filter_data['filter_type'] = $this->request->get['filter_type'];
        }
        
        if (isset($this->request->get['filter_date_from'])) {
            $filter_data['filter_date_from'] = $this->request->get['filter_date_from'];
        }
        
        if (isset($this->request->get['filter_date_to'])) {
            $filter_data['filter_date_to'] = $this->request->get['filter_date_to'];
        }
        
        if (isset($this->request->get['filter_search'])) {
            $filter_data['filter_search'] = $this->request->get['filter_search'];
        }
        
        $data['documents'] = $this->model_documents_archive->getArchivedDocuments($filter_data);
        $data['total'] = $this->model_documents_archive->getTotalArchivedDocuments($filter_data);
        
        // إحصائيات الأرشيف
        $data['archive_stats'] = array(
            'total_documents' => $this->model_documents_archive->getTotalDocuments(),
            'total_size' => $this->model_documents_archive->getTotalArchiveSize(),
            'documents_this_month' => $this->model_documents_archive->getDocumentsThisMonth(),
            'most_used_category' => $this->model_documents_archive->getMostUsedCategory(),
            'storage_efficiency' => $this->model_documents_archive->getStorageEfficiency(),
            'retrieval_speed' => $this->model_documents_archive->getAverageRetrievalSpeed()
        );
        
        // فئات المستندات المتخصصة للـ catalog/inventory
        $data['document_categories'] = array(
            'catalog' => array(
                'name' => $this->language->get('text_category_catalog'),
                'description' => $this->language->get('text_category_catalog_desc'),
                'icon' => 'fa-tags',
                'color' => 'primary',
                'count' => $this->model_documents_archive->getCategoryCount('catalog'),
                'subcategories' => array(
                    'product_specs' => $this->language->get('text_product_specifications'),
                    'product_images' => $this->language->get('text_product_images'),
                    'price_lists' => $this->language->get('text_price_lists'),
                    'category_docs' => $this->language->get('text_category_documents')
                )
            ),
            'inventory' => array(
                'name' => $this->language->get('text_category_inventory'),
                'description' => $this->language->get('text_category_inventory_desc'),
                'icon' => 'fa-cubes',
                'color' => 'warning',
                'count' => $this->model_documents_archive->getCategoryCount('inventory'),
                'subcategories' => array(
                    'stock_reports' => $this->language->get('text_stock_reports'),
                    'movement_docs' => $this->language->get('text_movement_documents'),
                    'adjustment_docs' => $this->language->get('text_adjustment_documents'),
                    'audit_reports' => $this->language->get('text_audit_reports')
                )
            ),
            'purchase' => array(
                'name' => $this->language->get('text_category_purchase'),
                'description' => $this->language->get('text_category_purchase_desc'),
                'icon' => 'fa-shopping-cart',
                'color' => 'success',
                'count' => $this->model_documents_archive->getCategoryCount('purchase'),
                'subcategories' => array(
                    'purchase_orders' => $this->language->get('text_purchase_orders'),
                    'supplier_docs' => $this->language->get('text_supplier_documents'),
                    'invoices' => $this->language->get('text_invoices'),
                    'contracts' => $this->language->get('text_contracts')
                )
            ),
            'sales' => array(
                'name' => $this->language->get('text_category_sales'),
                'description' => $this->language->get('text_category_sales_desc'),
                'icon' => 'fa-line-chart',
                'color' => 'info',
                'count' => $this->model_documents_archive->getCategoryCount('sales'),
                'subcategories' => array(
                    'sales_orders' => $this->language->get('text_sales_orders'),
                    'customer_docs' => $this->language->get('text_customer_documents'),
                    'quotations' => $this->language->get('text_quotations'),
                    'delivery_notes' => $this->language->get('text_delivery_notes')
                )
            ),
            'workflow' => array(
                'name' => $this->language->get('text_category_workflow'),
                'description' => $this->language->get('text_category_workflow_desc'),
                'icon' => 'fa-sitemap',
                'color' => 'secondary',
                'count' => $this->model_documents_archive->getCategoryCount('workflow'),
                'subcategories' => array(
                    'approval_docs' => $this->language->get('text_approval_documents'),
                    'process_docs' => $this->language->get('text_process_documents'),
                    'workflow_logs' => $this->language->get('text_workflow_logs'),
                    'templates' => $this->language->get('text_workflow_templates')
                )
            ),
            'compliance' => array(
                'name' => $this->language->get('text_category_compliance'),
                'description' => $this->language->get('text_category_compliance_desc'),
                'icon' => 'fa-shield',
                'color' => 'danger',
                'count' => $this->model_documents_archive->getCategoryCount('compliance'),
                'subcategories' => array(
                    'audit_docs' => $this->language->get('text_audit_documents'),
                    'compliance_reports' => $this->language->get('text_compliance_reports'),
                    'certifications' => $this->language->get('text_certifications'),
                    'legal_docs' => $this->language->get('text_legal_documents')
                )
            )
        );
        
        // أنواع الملفات المدعومة
        $data['supported_file_types'] = array(
            'documents' => array('pdf', 'doc', 'docx', 'txt', 'rtf'),
            'spreadsheets' => array('xls', 'xlsx', 'csv'),
            'images' => array('jpg', 'jpeg', 'png', 'gif', 'bmp'),
            'archives' => array('zip', 'rar', '7z'),
            'others' => array('xml', 'json', 'html')
        );
        
        // البحث المتقدم والفهرسة الذكية
        $data['search_features'] = array(
            'full_text_search' => true,
            'metadata_search' => true,
            'content_indexing' => true,
            'ai_categorization' => true,
            'auto_tagging' => true,
            'duplicate_detection' => true
        );
        
        // المستندات الأخيرة
        $data['recent_documents'] = $this->model_documents_archive->getRecentDocuments(10);
        
        // المستندات الأكثر وصولاً
        $data['popular_documents'] = $this->model_documents_archive->getPopularDocuments(10);
        
        // تحليل استخدام الأرشيف
        $data['usage_analytics'] = array(
            'daily_uploads' => $this->model_documents_archive->getDailyUploads(30),
            'category_distribution' => $this->model_documents_archive->getCategoryDistribution(),
            'file_type_distribution' => $this->model_documents_archive->getFileTypeDistribution(),
            'user_activity' => $this->model_documents_archive->getUserActivity()
        );
        
        // الروابط
        $data['upload'] = $this->url->link('documents/archive/upload', 'user_token=' . $this->session->data['user_token'], true);
        $data['bulk_upload'] = $this->url->link('documents/archive/bulk_upload', 'user_token=' . $this->session->data['user_token'], true);
        $data['advanced_search'] = $this->url->link('documents/archive/advanced_search', 'user_token=' . $this->session->data['user_token'], true);
        $data['manage_categories'] = $this->url->link('documents/archive/manage_categories', 'user_token=' . $this->session->data['user_token'], true);
        $data['storage_management'] = $this->url->link('documents/archive/storage_management', 'user_token=' . $this->session->data['user_token'], true);
        $data['analytics'] = $this->url->link('documents/archive/analytics', 'user_token=' . $this->session->data['user_token'], true);
        
        // الرسائل
        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }
        
        // التوكن
        $data['user_token'] = $this->session->data['user_token'];
        
        // عرض الصفحة
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('documents/archive', $data));
    }
    
    /**
     * رفع مستند جديد
     */
    public function upload() {
        $this->load->language('documents/archive');
        
        $this->document->setTitle($this->language->get('text_upload_document'));
        
        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('modify', 'documents/archive')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        // معالجة رفع الملف
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateUpload()) {
            $this->load->model('documents/archive');
            
            $upload_result = $this->model_documents_archive->uploadDocument($this->request->post, $this->request->files);
            
            if ($upload_result['success']) {
                // تشغيل معالجة ذكية للمستند
                $this->processDocumentAI($upload_result['document_id']);
                
                // إرسال إشعارات للمعنيين
                $this->sendUploadNotifications($upload_result['document_id'], $this->request->post);
                
                // تسجيل في نظام اللوج
                $this->logDocumentAction('upload', $upload_result['document_id'], $this->request->post);
                
                $this->session->data['success'] = $this->language->get('text_upload_success');
                
                $this->response->redirect($this->url->link('documents/archive/view', 'document_id=' . $upload_result['document_id'] . '&user_token=' . $this->session->data['user_token'], true));
            } else {
                $this->error['warning'] = $upload_result['error'];
            }
        }
        
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('documents/archive', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_upload_document'),
            'href' => $this->url->link('documents/archive/upload', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        // خيارات الرفع المتقدمة
        $data['upload_options'] = array(
            'auto_categorization' => true,
            'ocr_processing' => true,
            'duplicate_detection' => true,
            'virus_scanning' => true,
            'metadata_extraction' => true,
            'thumbnail_generation' => true
        );
        
        // قوالب البيانات الوصفية حسب الفئة
        $data['metadata_templates'] = array(
            'catalog' => array(
                'product_id' => $this->language->get('text_product_id'),
                'category_id' => $this->language->get('text_category_id'),
                'supplier_id' => $this->language->get('text_supplier_id'),
                'specification_type' => $this->language->get('text_specification_type')
            ),
            'inventory' => array(
                'warehouse_id' => $this->language->get('text_warehouse_id'),
                'movement_type' => $this->language->get('text_movement_type'),
                'reference_number' => $this->language->get('text_reference_number'),
                'audit_period' => $this->language->get('text_audit_period')
            ),
            'purchase' => array(
                'po_number' => $this->language->get('text_po_number'),
                'supplier_id' => $this->language->get('text_supplier_id'),
                'invoice_number' => $this->language->get('text_invoice_number'),
                'contract_type' => $this->language->get('text_contract_type')
            )
        );
        
        // تحميل البيانات المساعدة
        $this->load->model('catalog/product');
        $this->load->model('catalog/category');
        $this->load->model('purchase/supplier');
        
        $data['products'] = $this->model_catalog_product->getProducts(array('limit' => 100));
        $data['categories'] = $this->model_catalog_category->getCategories();
        $data['suppliers'] = $this->model_purchase_supplier->getSuppliers();
        
        // الروابط
        $data['action'] = $this->url->link('documents/archive/upload', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('documents/archive', 'user_token=' . $this->session->data['user_token'], true);
        
        // الأخطاء
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }
        
        // التوكن
        $data['user_token'] = $this->session->data['user_token'];
        
        // عرض الصفحة
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('documents/archive_upload', $data));
    }
    
    /**
     * عرض مستند محدد
     */
    public function view() {
        $this->load->language('documents/archive');
        
        if (isset($this->request->get['document_id'])) {
            $document_id = (int)$this->request->get['document_id'];
        } else {
            $document_id = 0;
        }
        
        $this->load->model('documents/archive');
        
        $document_info = $this->model_documents_archive->getDocument($document_id);
        
        if ($document_info) {
            // تسجيل المشاهدة
            $this->model_documents_archive->recordView($document_id, $this->user->getId());
            
            $this->document->setTitle($document_info['title']);
            
            $data['breadcrumbs'] = array();
            
            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
            );
            
            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('documents/archive', 'user_token=' . $this->session->data['user_token'], true)
            );
            
            $data['breadcrumbs'][] = array(
                'text' => $document_info['title'],
                'href' => $this->url->link('documents/archive/view', 'document_id=' . $document_id . '&user_token=' . $this->session->data['user_token'], true)
            );
            
            $data['document'] = $document_info;
            
            // إصدارات المستند
            $data['versions'] = $this->model_documents_archive->getDocumentVersions($document_id);
            
            // المستندات ذات الصلة
            $data['related_documents'] = $this->model_documents_archive->getRelatedDocuments($document_id);
            
            // تاريخ المستند
            $data['document_history'] = $this->model_documents_archive->getDocumentHistory($document_id);
            
            // إحصائيات المشاهدة
            $data['view_stats'] = $this->model_documents_archive->getViewStats($document_id);
            
            // معاينة المحتوى (إذا كان مدعوماً)
            $data['content_preview'] = $this->generateContentPreview($document_info);
            
            // الروابط
            $data['download'] = $this->url->link('documents/archive/download', 'document_id=' . $document_id . '&user_token=' . $this->session->data['user_token'], true);
            $data['edit'] = $this->url->link('documents/archive/edit', 'document_id=' . $document_id . '&user_token=' . $this->session->data['user_token'], true);
            $data['share'] = $this->url->link('documents/archive/share', 'document_id=' . $document_id . '&user_token=' . $this->session->data['user_token'], true);
            $data['back'] = $this->url->link('documents/archive', 'user_token=' . $this->session->data['user_token'], true);
            
            // التوكن
            $data['user_token'] = $this->session->data['user_token'];
            
            // عرض الصفحة
            $data['header'] = $this->load->controller('common/header');
            $data['column_left'] = $this->load->controller('common/column_left');
            $data['footer'] = $this->load->controller('common/footer');
            
            $this->response->setOutput($this->load->view('documents/archive_view', $data));
        } else {
            $this->response->redirect($this->url->link('documents/archive', 'user_token=' . $this->session->data['user_token'], true));
        }
    }
    
    /**
     * معالجة ذكية للمستند باستخدام AI
     */
    private function processDocumentAI($document_id) {
        $this->load->model('ai/pattern_recognition');
        $this->load->model('documents/archive');
        
        $document_info = $this->model_documents_archive->getDocument($document_id);
        
        if ($document_info) {
            // استخراج النص من المستند
            $extracted_text = $this->extractTextFromDocument($document_info['file_path']);
            
            // تصنيف تلقائي باستخدام AI
            $ai_category = $this->model_ai_pattern_recognition->categorizeDocument($extracted_text);
            
            // استخراج الكلمات المفتاحية
            $keywords = $this->model_ai_pattern_recognition->extractKeywords($extracted_text);
            
            // كشف المحتوى المشابه
            $similar_documents = $this->model_ai_pattern_recognition->findSimilarDocuments($extracted_text);
            
            // تحديث المستند بالمعلومات المستخرجة
            $this->model_documents_archive->updateDocumentAI($document_id, array(
                'ai_category' => $ai_category,
                'keywords' => implode(',', $keywords),
                'extracted_text' => $extracted_text,
                'similar_documents' => json_encode($similar_documents)
            ));
        }
    }
    
    /**
     * إرسال إشعارات الرفع
     */
    private function sendUploadNotifications($document_id, $document_data) {
        $this->load->model('notification/center');
        
        // إشعار للمستخدمين المهتمين بالفئة
        $interested_users = $this->getInterestedUsers($document_data['category']);
        
        foreach ($interested_users as $user_id) {
            $notification_data = array(
                'type' => 'document_uploaded',
                'recipient_id' => $user_id,
                'title' => 'مستند جديد: ' . $document_data['title'],
                'message' => 'تم رفع مستند جديد في فئة ' . $document_data['category'],
                'priority' => 'medium',
                'link' => 'documents/archive/view&document_id=' . $document_id
            );
            
            $this->model_notification_center->addNotification($notification_data);
        }
    }
    
    /**
     * تسجيل إجراء المستند
     */
    private function logDocumentAction($action, $document_id, $data) {
        $this->load->model('logging/user_activity');
        
        $activity_data = array(
            'action_type' => 'document_' . $action,
            'module' => 'documents/archive',
            'description' => 'تم ' . $action . ' المستند رقم ' . $document_id,
            'reference_type' => 'document',
            'reference_id' => $document_id
        );
        
        $this->model_logging_user_activity->addActivity($activity_data);
    }
    
    /**
     * التحقق من صحة الرفع
     */
    protected function validateUpload() {
        if (!$this->user->hasPermission('modify', 'documents/archive')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        if (empty($this->request->post['title'])) {
            $this->error['title'] = $this->language->get('error_title_required');
        }
        
        if (empty($this->request->post['category'])) {
            $this->error['category'] = $this->language->get('error_category_required');
        }
        
        if (!isset($this->request->files['file']) || !is_uploaded_file($this->request->files['file']['tmp_name'])) {
            $this->error['file'] = $this->language->get('error_file_required');
        }
        
        return !$this->error;
    }
    
    /**
     * دوال مساعدة
     */
    private function extractTextFromDocument($file_path) {
        // منطق استخراج النص من المستند
        return '';
    }
    
    private function generateContentPreview($document_info) {
        // منطق إنشاء معاينة المحتوى
        return array();
    }
    
    private function getInterestedUsers($category) {
        // منطق الحصول على المستخدمين المهتمين بالفئة
        return array();
    }
}
