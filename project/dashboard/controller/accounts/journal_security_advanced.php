<?php
/**
 * نظام حماية القيود المحاسبية المتقدم
 * مستوى عالمي مثل SAP وOracle وOdoo وMicrosoft Dynamics
 * منع التعديل والحذف بعد المراجعة والترحيل
 */
class ControllerAccountsJournalSecurityAdvanced extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('accounts/journal_security_advanced');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('accounts/journal_security_advanced');
        $this->load->model('accounts/audit_trail');

        // تسجيل الوصول في سجل المراجعة
        $this->model_accounts_audit_trail->logAction([
            'action_type' => 'view',
            'table_name' => 'journal_security',
            'record_id' => 0,
            'description' => 'عرض شاشة حماية القيود المحاسبية',
            'module' => 'journal_security'
        ]);

        $this->getList();
    }

    /**
     * منع تعديل القيد بعد المراجعة والترحيل
     */
    public function preventEdit() {
        $this->load->language('accounts/journal_security_advanced');
        $this->load->model('accounts/journal_security_advanced');

        $json = array();

        if (isset($this->request->post['journal_id'])) {
            $journal_id = $this->request->post['journal_id'];
            
            try {
                // التحقق من حالة القيد
                $journal_status = $this->model_accounts_journal_security_advanced->getJournalStatus($journal_id);
                
                if ($journal_status['is_reviewed'] || $journal_status['is_posted']) {
                    $json['error'] = 'لا يمكن تعديل القيد بعد المراجعة أو الترحيل - نظام الحماية المتقدم';
                    $json['status'] = 'blocked';
                    
                    // تسجيل محاولة التعديل المرفوضة
                    $this->model_accounts_audit_trail->logAction([
                        'action_type' => 'edit_attempt_blocked',
                        'table_name' => 'journal_entries',
                        'record_id' => $journal_id,
                        'description' => 'محاولة تعديل قيد محمي - تم الرفض',
                        'module' => 'journal_security',
                        'severity' => 'warning'
                    ]);
                } else {
                    $json['success'] = 'يمكن تعديل القيد';
                    $json['status'] = 'allowed';
                }
                
            } catch (Exception $e) {
                $json['error'] = 'خطأ في التحقق من حالة القيد: ' . $e->getMessage();
            }
        } else {
            $json['error'] = 'معرف القيد مطلوب';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * منع حذف القيد - صلاحيات خاصة فقط
     */
    public function preventDelete() {
        $this->load->language('accounts/journal_security_advanced');
        $this->load->model('accounts/journal_security_advanced');
        $this->load->model('user/user_permission');

        $json = array();

        if (isset($this->request->post['journal_id'])) {
            $journal_id = $this->request->post['journal_id'];
            
            try {
                // التحقق من صلاحية الحذف
                $can_delete = $this->model_user_user_permission->hasAdvancedPermission($this->user->getId(), 'journal_delete_advanced');
                
                if (!$can_delete) {
                    $json['error'] = 'ليس لديك صلاحية حذف القيود المحاسبية - نظام الحماية المتقدم';
                    $json['status'] = 'no_permission';
                    
                    // تسجيل محاولة الحذف المرفوضة
                    $this->model_accounts_audit_trail->logAction([
                        'action_type' => 'delete_attempt_blocked',
                        'table_name' => 'journal_entries',
                        'record_id' => $journal_id,
                        'description' => 'محاولة حذف قيد بدون صلاحية - تم الرفض',
                        'module' => 'journal_security',
                        'severity' => 'critical'
                    ]);
                    
                    $this->response->addHeader('Content-Type: application/json');
                    $this->response->setOutput(json_encode($json));
                    return;
                }

                // التحقق من حالة القيد
                $journal_status = $this->model_accounts_journal_security_advanced->getJournalStatus($journal_id);
                
                if ($journal_status['is_reviewed'] || $journal_status['is_posted']) {
                    $json['error'] = 'لا يمكن حذف القيد بعد المراجعة أو الترحيل - حتى مع الصلاحيات المتقدمة';
                    $json['status'] = 'blocked_reviewed';
                    
                    // تسجيل محاولة الحذف المرفوضة
                    $this->model_accounts_audit_trail->logAction([
                        'action_type' => 'delete_attempt_blocked_reviewed',
                        'table_name' => 'journal_entries',
                        'record_id' => $journal_id,
                        'description' => 'محاولة حذف قيد مراجع/مرحل - تم الرفض',
                        'module' => 'journal_security',
                        'severity' => 'critical'
                    ]);
                } else {
                    // التحقق من وجود قيود مرتبطة
                    $related_entries = $this->model_accounts_journal_security_advanced->getRelatedEntries($journal_id);
                    
                    if (!empty($related_entries)) {
                        $json['warning'] = 'يوجد قيود مرتبطة بهذا القيد - هل تريد المتابعة؟';
                        $json['related_entries'] = $related_entries;
                        $json['status'] = 'has_relations';
                    } else {
                        $json['success'] = 'يمكن حذف القيد';
                        $json['status'] = 'allowed';
                    }
                }
                
            } catch (Exception $e) {
                $json['error'] = 'خطأ في التحقق من إمكانية الحذف: ' . $e->getMessage();
            }
        } else {
            $json['error'] = 'معرف القيد مطلوب';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * تأمين القيد بعد المراجعة
     */
    public function secureAfterReview() {
        $this->load->language('accounts/journal_security_advanced');
        $this->load->model('accounts/journal_security_advanced');

        $json = array();

        if (isset($this->request->post['journal_id'])) {
            $journal_id = $this->request->post['journal_id'];
            
            try {
                // تأمين القيد
                $result = $this->model_accounts_journal_security_advanced->secureJournalEntry($journal_id);
                
                if ($result) {
                    $json['success'] = 'تم تأمين القيد بنجاح - لا يمكن تعديله أو حذفه';
                    
                    // تسجيل تأمين القيد
                    $this->model_accounts_audit_trail->logAction([
                        'action_type' => 'journal_secured',
                        'table_name' => 'journal_entries',
                        'record_id' => $journal_id,
                        'description' => 'تم تأمين القيد بعد المراجعة',
                        'module' => 'journal_security'
                    ]);
                } else {
                    $json['error'] = 'فشل في تأمين القيد';
                }
                
            } catch (Exception $e) {
                $json['error'] = 'خطأ في تأمين القيد: ' . $e->getMessage();
            }
        } else {
            $json['error'] = 'معرف القيد مطلوب';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * إلغاء تأمين القيد - صلاحيات خاصة جداً
     */
    public function unsecureEntry() {
        $this->load->language('accounts/journal_security_advanced');
        $this->load->model('accounts/journal_security_advanced');
        $this->load->model('user/user_permission');

        $json = array();

        // التحقق من الصلاحية الخاصة جداً
        $can_unsecure = $this->model_user_user_permission->hasAdvancedPermission($this->user->getId(), 'journal_unsecure_super_admin');
        
        if (!$can_unsecure) {
            $json['error'] = 'ليس لديك صلاحية إلغاء تأمين القيود - صلاحية المدير العام فقط';
            
            // تسجيل محاولة إلغاء التأمين المرفوضة
            $this->model_accounts_audit_trail->logAction([
                'action_type' => 'unsecure_attempt_blocked',
                'table_name' => 'journal_entries',
                'record_id' => $this->request->post['journal_id'] ?? 0,
                'description' => 'محاولة إلغاء تأمين قيد بدون صلاحية - تم الرفض',
                'module' => 'journal_security',
                'severity' => 'critical'
            ]);
            
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        if (isset($this->request->post['journal_id']) && isset($this->request->post['reason'])) {
            $journal_id = $this->request->post['journal_id'];
            $reason = $this->request->post['reason'];
            
            try {
                // إلغاء تأمين القيد مع تسجيل السبب
                $result = $this->model_accounts_journal_security_advanced->unsecureJournalEntry($journal_id, $reason);
                
                if ($result) {
                    $json['success'] = 'تم إلغاء تأمين القيد - يمكن تعديله الآن';
                    
                    // تسجيل إلغاء التأمين
                    $this->model_accounts_audit_trail->logAction([
                        'action_type' => 'journal_unsecured',
                        'table_name' => 'journal_entries',
                        'record_id' => $journal_id,
                        'description' => 'تم إلغاء تأمين القيد - السبب: ' . $reason,
                        'module' => 'journal_security',
                        'severity' => 'high'
                    ]);
                } else {
                    $json['error'] = 'فشل في إلغاء تأمين القيد';
                }
                
            } catch (Exception $e) {
                $json['error'] = 'خطأ في إلغاء تأمين القيد: ' . $e->getMessage();
            }
        } else {
            $json['error'] = 'معرف القيد والسبب مطلوبان';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * تقرير القيود المحمية
     */
    public function getSecurityReport() {
        $this->load->model('accounts/journal_security_advanced');

        $json = array();

        try {
            $filter_data = array(
                'start_date' => $this->request->get['start_date'] ?? date('Y-m-01'),
                'end_date' => $this->request->get['end_date'] ?? date('Y-m-t'),
                'security_level' => $this->request->get['security_level'] ?? 'all'
            );
            
            $security_report = $this->model_accounts_journal_security_advanced->getSecurityReport($filter_data);
            
            $json['success'] = true;
            $json['report'] = $security_report;
            
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * تقرير محاولات الوصول المرفوضة
     */
    public function getAccessAttempts() {
        $this->load->model('accounts/audit_trail');

        $json = array();

        try {
            $filter_data = array(
                'action_types' => ['edit_attempt_blocked', 'delete_attempt_blocked', 'unsecure_attempt_blocked'],
                'start_date' => $this->request->get['start_date'] ?? date('Y-m-01'),
                'end_date' => $this->request->get['end_date'] ?? date('Y-m-t'),
                'severity' => ['warning', 'critical']
            );
            
            $access_attempts = $this->model_accounts_audit_trail->getFilteredActions($filter_data);
            
            $json['success'] = true;
            $json['attempts'] = $access_attempts;
            
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    protected function getList() {
        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('accounts/journal_security_advanced', 'user_token=' . $this->session->data['user_token'], true)
        );

        // URLs للـ AJAX
        $data['prevent_edit_url'] = $this->url->link('accounts/journal_security_advanced/preventEdit', 'user_token=' . $this->session->data['user_token'], true);
        $data['prevent_delete_url'] = $this->url->link('accounts/journal_security_advanced/preventDelete', 'user_token=' . $this->session->data['user_token'], true);
        $data['secure_url'] = $this->url->link('accounts/journal_security_advanced/secureAfterReview', 'user_token=' . $this->session->data['user_token'], true);
        $data['unsecure_url'] = $this->url->link('accounts/journal_security_advanced/unsecureEntry', 'user_token=' . $this->session->data['user_token'], true);
        $data['security_report_url'] = $this->url->link('accounts/journal_security_advanced/getSecurityReport', 'user_token=' . $this->session->data['user_token'], true);
        $data['access_attempts_url'] = $this->url->link('accounts/journal_security_advanced/getAccessAttempts', 'user_token=' . $this->session->data['user_token'], true);

        $data['user_token'] = $this->session->data['user_token'];

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('accounts/journal_security_advanced_list', $data));
    }
}
