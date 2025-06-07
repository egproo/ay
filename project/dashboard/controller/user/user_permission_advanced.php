<?php
/**
 * نظام الصلاحيات المتقدم
 * مستوى عالمي مثل SAP وOracle وOdoo وMicrosoft Dynamics
 */
class ControllerUserUserPermissionAdvanced extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('user/user_permission_advanced');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('user/user_permission_advanced');
        $this->load->model('accounts/audit_trail');

        // إضافة CSS و JavaScript المتقدم
        $this->document->addStyle('view/stylesheet/user/user_permission_advanced.css');
        $this->document->addScript('view/javascript/user/user_permission_advanced.js');

        // تسجيل الوصول في سجل المراجعة
        $this->model_accounts_audit_trail->logAction([
            'action_type' => 'view',
            'table_name' => 'user_permissions',
            'record_id' => 0,
            'description' => 'عرض نظام الصلاحيات المتقدم',
            'module' => 'user_permissions'
        ]);

        $this->getList();
    }

    public function getUserPermissions() {
        $this->load->model('user/user_permission_advanced');

        $json = array();

        if (isset($this->request->get['user_id'])) {
            $user_id = $this->request->get['user_id'];

            try {
                $permissions = $this->model_user_user_permission_advanced->getUserPermissions($user_id);
                
                $json['success'] = true;
                $json['permissions'] = $permissions;
                
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = 'معرف المستخدم مطلوب';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function updateUserPermissions() {
        $this->load->model('user/user_permission_advanced');
        $this->load->model('accounts/audit_trail');

        $json = array();

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $user_id = $this->request->post['user_id'];
            $permissions = $this->request->post['permissions'] ?? array();

            try {
                // التحقق من الصلاحيات
                if (!$this->user->hasPermission('modify', 'user/user_permission_advanced')) {
                    throw new Exception('ليس لديك صلاحية لتعديل صلاحيات المستخدمين');
                }

                $result = $this->model_user_user_permission_advanced->updateUserPermissions($user_id, $permissions);
                
                if ($result) {
                    $json['success'] = true;
                    $json['message'] = 'تم تحديث صلاحيات المستخدم بنجاح';
                    
                    // تسجيل في سجل المراجعة
                    $this->model_accounts_audit_trail->logAction([
                        'action_type' => 'update_user_permissions',
                        'table_name' => 'user_permissions',
                        'record_id' => $user_id,
                        'description' => 'تحديث صلاحيات المستخدم رقم: ' . $user_id,
                        'module' => 'user_permissions'
                    ]);
                } else {
                    $json['error'] = 'فشل في تحديث صلاحيات المستخدم';
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

    public function createRole() {
        $this->load->model('user/user_permission_advanced');
        $this->load->model('accounts/audit_trail');

        $json = array();

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $role_data = array(
                'role_name' => $this->request->post['role_name'],
                'role_description' => $this->request->post['role_description'],
                'permissions' => $this->request->post['permissions'] ?? array()
            );

            try {
                // التحقق من الصلاحيات
                if (!$this->user->hasPermission('modify', 'user/roles')) {
                    throw new Exception('ليس لديك صلاحية لإنشاء الأدوار');
                }

                $role_id = $this->model_user_user_permission_advanced->createRole($role_data);
                
                if ($role_id) {
                    $json['success'] = true;
                    $json['message'] = 'تم إنشاء الدور بنجاح';
                    $json['role_id'] = $role_id;
                    
                    // تسجيل في سجل المراجعة
                    $this->model_accounts_audit_trail->logAction([
                        'action_type' => 'create_role',
                        'table_name' => 'user_roles',
                        'record_id' => $role_id,
                        'description' => 'إنشاء دور جديد: ' . $role_data['role_name'],
                        'module' => 'user_permissions'
                    ]);
                } else {
                    $json['error'] = 'فشل في إنشاء الدور';
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

    public function assignRoleToUser() {
        $this->load->model('user/user_permission_advanced');
        $this->load->model('accounts/audit_trail');

        $json = array();

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $user_id = $this->request->post['user_id'];
            $role_id = $this->request->post['role_id'];

            try {
                // التحقق من الصلاحيات
                if (!$this->user->hasPermission('modify', 'user/user_roles')) {
                    throw new Exception('ليس لديك صلاحية لتعيين الأدوار للمستخدمين');
                }

                $result = $this->model_user_user_permission_advanced->assignRoleToUser($user_id, $role_id);
                
                if ($result) {
                    $json['success'] = true;
                    $json['message'] = 'تم تعيين الدور للمستخدم بنجاح';
                    
                    // تسجيل في سجل المراجعة
                    $this->model_accounts_audit_trail->logAction([
                        'action_type' => 'assign_role_to_user',
                        'table_name' => 'user_role_assignments',
                        'record_id' => $user_id,
                        'description' => 'تعيين الدور رقم: ' . $role_id . ' للمستخدم رقم: ' . $user_id,
                        'module' => 'user_permissions'
                    ]);
                } else {
                    $json['error'] = 'فشل في تعيين الدور للمستخدم';
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

    public function getPermissionMatrix() {
        $this->load->model('user/user_permission_advanced');

        $json = array();

        try {
            $matrix = $this->model_user_user_permission_advanced->getPermissionMatrix();
            
            $json['success'] = true;
            $json['matrix'] = $matrix;
            
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getAccessLog() {
        $this->load->model('user/user_permission_advanced');

        $json = array();

        try {
            $filter_data = array(
                'start' => $this->request->get['start'] ?? 0,
                'limit' => $this->request->get['limit'] ?? 20,
                'user_id' => $this->request->get['user_id'] ?? null,
                'date_from' => $this->request->get['date_from'] ?? null,
                'date_to' => $this->request->get['date_to'] ?? null,
                'action_type' => $this->request->get['action_type'] ?? null
            );

            $access_log = $this->model_user_user_permission_advanced->getAccessLog($filter_data);
            $total_log = $this->model_user_user_permission_advanced->getTotalAccessLog($filter_data);
            
            $json['success'] = true;
            $json['log'] = $access_log;
            $json['total'] = $total_log;
            
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function validatePermissions() {
        $this->load->model('user/user_permission_advanced');

        $json = array();

        try {
            $validation = $this->model_user_user_permission_advanced->validatePermissions();
            
            $json['success'] = true;
            $json['validation'] = $validation;
            
            if (!$validation['is_valid']) {
                $json['warning'] = 'توجد مشاكل في صلاحيات النظام';
            }
            
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getSecurityReport() {
        $this->load->model('user/user_permission_advanced');

        $json = array();

        try {
            $report = $this->model_user_user_permission_advanced->generateSecurityReport();
            
            $json['success'] = true;
            $json['report'] = $report;
            
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function exportPermissions() {
        $this->load->model('user/user_permission_advanced');

        $format = $this->request->get['format'] ?? 'excel';
        
        try {
            $permissions_data = $this->model_user_user_permission_advanced->getAllPermissions();
            
            switch ($format) {
                case 'excel':
                    $this->exportToExcel($permissions_data);
                    break;
                case 'csv':
                    $this->exportToCsv($permissions_data);
                    break;
                default:
                    $this->exportToExcel($permissions_data);
            }
            
        } catch (Exception $e) {
            $this->session->data['error'] = 'خطأ في تصدير الصلاحيات: ' . $e->getMessage();
            $this->response->redirect($this->url->link('user/user_permission_advanced', 'user_token=' . $this->session->data['user_token'], true));
        }
    }

    protected function getList() {
        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('user/user_permission_advanced', 'user_token=' . $this->session->data['user_token'], true)
        );

        // URLs للـ AJAX
        $data['get_permissions_url'] = $this->url->link('user/user_permission_advanced/getUserPermissions', 'user_token=' . $this->session->data['user_token'], true);
        $data['update_permissions_url'] = $this->url->link('user/user_permission_advanced/updateUserPermissions', 'user_token=' . $this->session->data['user_token'], true);
        $data['create_role_url'] = $this->url->link('user/user_permission_advanced/createRole', 'user_token=' . $this->session->data['user_token'], true);
        $data['assign_role_url'] = $this->url->link('user/user_permission_advanced/assignRoleToUser', 'user_token=' . $this->session->data['user_token'], true);
        $data['permission_matrix_url'] = $this->url->link('user/user_permission_advanced/getPermissionMatrix', 'user_token=' . $this->session->data['user_token'], true);
        $data['access_log_url'] = $this->url->link('user/user_permission_advanced/getAccessLog', 'user_token=' . $this->session->data['user_token'], true);
        $data['validate_permissions_url'] = $this->url->link('user/user_permission_advanced/validatePermissions', 'user_token=' . $this->session->data['user_token'], true);
        $data['security_report_url'] = $this->url->link('user/user_permission_advanced/getSecurityReport', 'user_token=' . $this->session->data['user_token'], true);
        $data['export_url'] = $this->url->link('user/user_permission_advanced/exportPermissions', 'user_token=' . $this->session->data['user_token'], true);

        $data['user_token'] = $this->session->data['user_token'];

        // تحميل قائمة المستخدمين
        $this->load->model('user/user');
        $data['users'] = $this->model_user_user->getUsers();

        // تحميل قائمة الأدوار
        $this->load->model('user/user_permission_advanced');
        $data['roles'] = $this->model_user_user_permission_advanced->getRoles();

        // الصلاحيات
        $data['can_modify_permissions'] = $this->user->hasPermission('modify', 'user/user_permission_advanced');
        $data['can_create_roles'] = $this->user->hasPermission('modify', 'user/roles');
        $data['can_assign_roles'] = $this->user->hasPermission('modify', 'user/user_roles');

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

        $this->response->setOutput($this->load->view('user/user_permission_advanced', $data));
    }

    private function exportToExcel($data) {
        // تنفيذ تصدير Excel
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="user_permissions_' . date('Y-m-d') . '.xlsx"');
        // كود تصدير Excel هنا
    }

    private function exportToCsv($data) {
        // تنفيذ تصدير CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="user_permissions_' . date('Y-m-d') . '.csv"');
        // كود تصدير CSV هنا
    }
}
