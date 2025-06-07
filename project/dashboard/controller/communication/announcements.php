<?php
/**
 * نظام الإعلانات المتقدم
 * Advanced Announcements System Controller
 * 
 * نظام إعلانات متقدم مع تكامل مع catalog/inventory
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

class ControllerCommunicationAnnouncements extends Controller {
    
    /**
     * @var array خطأ في النظام
     */
    private $error = array();
    
    /**
     * عرض صفحة الإعلانات الرئيسية
     */
    public function index() {
        $this->load->language('communication/announcements');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('access', 'communication/announcements')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('communication/announcements', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        // تحميل الإعلانات
        $this->load->model('communication/announcements');
        
        $filter_data = array(
            'start' => 0,
            'limit' => 20
        );
        
        // الإعلانات النشطة
        $data['active_announcements'] = $this->model_communication_announcements->getActiveAnnouncements($filter_data);
        
        // الإعلانات المجدولة
        $data['scheduled_announcements'] = $this->model_communication_announcements->getScheduledAnnouncements($filter_data);
        
        // الإعلانات المنتهية
        $data['expired_announcements'] = $this->model_communication_announcements->getExpiredAnnouncements($filter_data);
        
        // إعلانات خاصة بـ catalog/inventory
        $data['specialized_announcements'] = array(
            'catalog' => array(
                'new_products' => $this->model_communication_announcements->getCatalogAnnouncements('new_products'),
                'price_updates' => $this->model_communication_announcements->getCatalogAnnouncements('price_updates'),
                'category_changes' => $this->model_communication_announcements->getCatalogAnnouncements('category_changes')
            ),
            'inventory' => array(
                'stock_alerts' => $this->model_communication_announcements->getInventoryAnnouncements('stock_alerts'),
                'warehouse_updates' => $this->model_communication_announcements->getInventoryAnnouncements('warehouse_updates'),
                'system_maintenance' => $this->model_communication_announcements->getInventoryAnnouncements('maintenance')
            )
        );
        
        // أنواع الإعلانات
        $data['announcement_types'] = array(
            'general' => array(
                'name' => $this->language->get('text_type_general'),
                'icon' => 'fa-bullhorn',
                'color' => 'info'
            ),
            'urgent' => array(
                'name' => $this->language->get('text_type_urgent'),
                'icon' => 'fa-exclamation-triangle',
                'color' => 'danger'
            ),
            'catalog' => array(
                'name' => $this->language->get('text_type_catalog'),
                'icon' => 'fa-tags',
                'color' => 'primary'
            ),
            'inventory' => array(
                'name' => $this->language->get('text_type_inventory'),
                'icon' => 'fa-cubes',
                'color' => 'warning'
            ),
            'system' => array(
                'name' => $this->language->get('text_type_system'),
                'icon' => 'fa-cogs',
                'color' => 'secondary'
            ),
            'maintenance' => array(
                'name' => $this->language->get('text_type_maintenance'),
                'icon' => 'fa-wrench',
                'color' => 'dark'
            )
        );
        
        // إحصائيات الإعلانات
        $data['announcement_stats'] = array(
            'total_announcements' => $this->model_communication_announcements->getTotalAnnouncements(),
            'active_announcements' => $this->model_communication_announcements->getActiveAnnouncementsCount(),
            'scheduled_announcements' => $this->model_communication_announcements->getScheduledAnnouncementsCount(),
            'views_today' => $this->model_communication_announcements->getTodayViews(),
            'engagement_rate' => $this->model_communication_announcements->getEngagementRate()
        );
        
        // الإعلانات العاجلة (تظهر في أعلى الصفحة)
        $data['urgent_announcements'] = $this->model_communication_announcements->getUrgentAnnouncements();
        
        // إعلانات خاصة بالمستخدم الحالي
        $data['personal_announcements'] = $this->model_communication_announcements->getPersonalAnnouncements($this->user->getId());
        
        // الروابط
        $data['add'] = $this->url->link('communication/announcements/add', 'user_token=' . $this->session->data['user_token'], true);
        $data['bulk_send'] = $this->url->link('communication/announcements/bulk', 'user_token=' . $this->session->data['user_token'], true);
        $data['templates'] = $this->url->link('communication/announcements/templates', 'user_token=' . $this->session->data['user_token'], true);
        $data['analytics'] = $this->url->link('communication/announcements/analytics', 'user_token=' . $this->session->data['user_token'], true);
        
        // الرسائل
        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }
        
        // التوكن
        $data['user_token'] = $this->session->data['user_token'];
        $data['current_user_id'] = $this->user->getId();
        
        // عرض الصفحة
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('communication/announcements', $data));
    }
    
    /**
     * إضافة إعلان جديد
     */
    public function add() {
        $this->load->language('communication/announcements');
        
        $this->document->setTitle($this->language->get('text_add'));
        
        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('modify', 'communication/announcements')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        // معالجة حفظ الإعلان
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->load->model('communication/announcements');
            
            $announcement_id = $this->model_communication_announcements->addAnnouncement($this->request->post);
            
            // إرسال إشعارات للمستهدفين
            $this->sendAnnouncementNotifications($announcement_id, $this->request->post);
            
            // تشغيل workflow إذا كان مطلوباً
            if (isset($this->request->post['trigger_workflow']) && $this->request->post['trigger_workflow']) {
                $this->triggerAnnouncementWorkflow($announcement_id, $this->request->post);
            }
            
            $this->session->data['success'] = $this->language->get('text_success');
            
            $this->response->redirect($this->url->link('communication/announcements', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $this->getForm();
    }
    
    /**
     * عرض إعلان محدد
     */
    public function view() {
        $this->load->language('communication/announcements');
        
        if (isset($this->request->get['announcement_id'])) {
            $announcement_id = (int)$this->request->get['announcement_id'];
        } else {
            $announcement_id = 0;
        }
        
        $this->load->model('communication/announcements');
        
        $announcement_info = $this->model_communication_announcements->getAnnouncement($announcement_id);
        
        if ($announcement_info) {
            // تسجيل المشاهدة
            $this->model_communication_announcements->recordView($announcement_id, $this->user->getId());
            
            $this->document->setTitle($announcement_info['title']);
            
            $data['breadcrumbs'] = array();
            
            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
            );
            
            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('communication/announcements', 'user_token=' . $this->session->data['user_token'], true)
            );
            
            $data['breadcrumbs'][] = array(
                'text' => $announcement_info['title'],
                'href' => $this->url->link('communication/announcements/view', 'announcement_id=' . $announcement_id . '&user_token=' . $this->session->data['user_token'], true)
            );
            
            $data['announcement'] = $announcement_info;
            
            // المرفقات
            $data['attachments'] = $this->model_communication_announcements->getAnnouncementAttachments($announcement_id);
            
            // التعليقات والردود
            $data['comments'] = $this->model_communication_announcements->getAnnouncementComments($announcement_id);
            
            // إحصائيات المشاهدة
            $data['view_stats'] = $this->model_communication_announcements->getViewStats($announcement_id);
            
            // الإعلانات ذات الصلة
            $data['related_announcements'] = $this->model_communication_announcements->getRelatedAnnouncements($announcement_id);
            
            // إجراءات سريعة حسب نوع الإعلان
            if ($announcement_info['type'] == 'catalog') {
                $data['quick_actions'] = array(
                    'view_products' => $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'], true),
                    'add_product' => $this->url->link('catalog/product/add', 'user_token=' . $this->session->data['user_token'], true),
                    'price_update' => $this->url->link('catalog/dynamic_pricing', 'user_token=' . $this->session->data['user_token'], true)
                );
            } elseif ($announcement_info['type'] == 'inventory') {
                $data['quick_actions'] = array(
                    'stock_inquiry' => $this->url->link('inventory/stock_inquiry', 'user_token=' . $this->session->data['user_token'], true),
                    'stock_movement' => $this->url->link('inventory/movement_history', 'user_token=' . $this->session->data['user_token'], true),
                    'adjustment' => $this->url->link('inventory/adjustment/add', 'user_token=' . $this->session->data['user_token'], true)
                );
            }
            
            // الروابط
            $data['edit'] = $this->url->link('communication/announcements/edit', 'announcement_id=' . $announcement_id . '&user_token=' . $this->session->data['user_token'], true);
            $data['delete'] = $this->url->link('communication/announcements/delete', 'announcement_id=' . $announcement_id . '&user_token=' . $this->session->data['user_token'], true);
            $data['back'] = $this->url->link('communication/announcements', 'user_token=' . $this->session->data['user_token'], true);
            
            // التوكن
            $data['user_token'] = $this->session->data['user_token'];
            
            // عرض الصفحة
            $data['header'] = $this->load->controller('common/header');
            $data['column_left'] = $this->load->controller('common/column_left');
            $data['footer'] = $this->load->controller('common/footer');
            
            $this->response->setOutput($this->load->view('communication/announcement_view', $data));
        } else {
            $this->response->redirect($this->url->link('communication/announcements', 'user_token=' . $this->session->data['user_token'], true));
        }
    }
    
    /**
     * إرسال إعلان جماعي
     */
    public function bulk() {
        $this->load->language('communication/announcements');
        
        $this->document->setTitle($this->language->get('text_bulk_send'));
        
        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('modify', 'communication/announcements')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        // معالجة الإرسال الجماعي
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateBulkForm()) {
            $this->load->model('communication/announcements');
            
            $bulk_data = $this->request->post;
            $result = $this->model_communication_announcements->sendBulkAnnouncement($bulk_data);
            
            if ($result['success']) {
                $this->session->data['success'] = sprintf($this->language->get('text_bulk_success'), $result['sent_count']);
            } else {
                $this->session->data['error'] = $result['error'];
            }
            
            $this->response->redirect($this->url->link('communication/announcements', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        // تحميل البيانات للنموذج
        $this->load->model('user/user_group');
        $this->load->model('user/user');
        
        $data['user_groups'] = $this->model_user_user_group->getUserGroups();
        $data['users'] = $this->model_user_user->getUsers();
        
        // قوالب الإعلانات الجاهزة
        $data['announcement_templates'] = array(
            'catalog_new_product' => array(
                'title' => 'إعلان منتج جديد',
                'content' => 'تم إضافة منتج جديد: {product_name} في فئة {category_name}',
                'type' => 'catalog'
            ),
            'inventory_low_stock' => array(
                'title' => 'تحذير: مخزون منخفض',
                'content' => 'تحذير: المنتج {product_name} وصل إلى مستوى مخزون منخفض',
                'type' => 'inventory'
            ),
            'system_maintenance' => array(
                'title' => 'صيانة النظام',
                'content' => 'سيتم إجراء صيانة للنظام يوم {date} من {start_time} إلى {end_time}',
                'type' => 'system'
            )
        );
        
        // الروابط
        $data['action'] = $this->url->link('communication/announcements/bulk', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('communication/announcements', 'user_token=' . $this->session->data['user_token'], true);
        
        // التوكن
        $data['user_token'] = $this->session->data['user_token'];
        
        // عرض الصفحة
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('communication/announcement_bulk', $data));
    }
    
    /**
     * تحليلات الإعلانات
     */
    public function analytics() {
        $this->load->language('communication/announcements');
        
        $this->document->setTitle($this->language->get('text_analytics'));
        
        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('access', 'communication/announcements')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $this->load->model('communication/announcements');
        
        // إحصائيات شاملة
        $data['analytics'] = array(
            'overview' => array(
                'total_announcements' => $this->model_communication_announcements->getTotalAnnouncements(),
                'total_views' => $this->model_communication_announcements->getTotalViews(),
                'average_engagement' => $this->model_communication_announcements->getAverageEngagement(),
                'most_viewed_type' => $this->model_communication_announcements->getMostViewedType()
            ),
            'by_type' => $this->model_communication_announcements->getAnalyticsByType(),
            'by_month' => $this->model_communication_announcements->getAnalyticsByMonth(),
            'engagement_trends' => $this->model_communication_announcements->getEngagementTrends(),
            'top_announcements' => $this->model_communication_announcements->getTopAnnouncements(10)
        );
        
        // تحليلات خاصة بـ catalog/inventory
        $data['specialized_analytics'] = array(
            'catalog_performance' => $this->model_communication_announcements->getCatalogAnnouncementsAnalytics(),
            'inventory_alerts_effectiveness' => $this->model_communication_announcements->getInventoryAlertsAnalytics()
        );
        
        // التوكن
        $data['user_token'] = $this->session->data['user_token'];
        
        // عرض الصفحة
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('communication/announcement_analytics', $data));
    }
    
    /**
     * إرسال إشعارات للإعلان
     */
    private function sendAnnouncementNotifications($announcement_id, $announcement_data) {
        $this->load->model('notification/center');
        
        if (isset($announcement_data['send_notifications']) && $announcement_data['send_notifications']) {
            $notification_data = array(
                'type' => 'new_announcement',
                'title' => 'إعلان جديد: ' . $announcement_data['title'],
                'message' => substr($announcement_data['content'], 0, 100) . '...',
                'priority' => $announcement_data['priority'],
                'link' => 'communication/announcements/view&announcement_id=' . $announcement_id
            );
            
            if (isset($announcement_data['target_groups'])) {
                foreach ($announcement_data['target_groups'] as $group_id) {
                    $this->model_notification_center->sendGroupNotification($group_id, $notification_data);
                }
            }
            
            if (isset($announcement_data['target_users'])) {
                foreach ($announcement_data['target_users'] as $user_id) {
                    $this->model_notification_center->sendUserNotification($user_id, $notification_data);
                }
            }
        }
    }
    
    /**
     * تشغيل workflow للإعلان
     */
    private function triggerAnnouncementWorkflow($announcement_id, $announcement_data) {
        $this->load->model('workflow/automation');
        
        $workflow_data = array(
            'trigger_type' => 'announcement_published',
            'announcement_id' => $announcement_id,
            'announcement_type' => $announcement_data['type'],
            'priority' => $announcement_data['priority']
        );
        
        $this->model_workflow_automation->triggerWorkflow('announcement_workflow', $workflow_data);
    }
    
    /**
     * نموذج إضافة/تعديل الإعلان
     */
    protected function getForm() {
        // تحميل البيانات للنموذج
        // ... (كود النموذج)
    }
    
    /**
     * التحقق من صحة النموذج
     */
    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'communication/announcements')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        if ((utf8_strlen($this->request->post['title']) < 1) || (utf8_strlen($this->request->post['title']) > 255)) {
            $this->error['title'] = $this->language->get('error_title');
        }
        
        if (utf8_strlen($this->request->post['content']) < 1) {
            $this->error['content'] = $this->language->get('error_content');
        }
        
        return !$this->error;
    }
    
    /**
     * التحقق من صحة النموذج الجماعي
     */
    protected function validateBulkForm() {
        if (!$this->user->hasPermission('modify', 'communication/announcements')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        if (!isset($this->request->post['target_groups']) && !isset($this->request->post['target_users'])) {
            $this->error['targets'] = $this->language->get('error_no_targets');
        }
        
        return !$this->error;
    }
}
