<?php
/**
 * AYM ERP System: Integrated Enterprise Resource Planning System for Commerce and Distribution
 * With support for perpetual inventory, weighted average cost, and multi-branch operations
 * built by edt opencart 3.0.3.7 to be high inteligent and user friendly erp for ecommerce company in egypt
 * Components (n8n worflow visual editor + Notificaiton center system + AI assistant + message + must use header.twig libarary + must review db tables in db.tx and all screen in column_left.php in controller/common
 * 1. Sales Management & CRM System
 * 2. Purchase & Supplier Management System
 * 3. Inventory & Warehouse Management System with Perpetual Inventory
 * 4. Shipping & Distribution Management System
 * 5. Financial & Accounting Management System
 * 6. Electronic Invoice System (ETA Egypt)
 * 7. E-commerce Store Management System
 * 8. Human Resources Management System
 * 9. Project & Operations Management System
 * 10. Internal Communication & Workflow System
 * 11. AI & Automation System
 * 12. Reports & Analytics System
 * 13. Settings & System Management
 */
class ControllerCommonColumnLeft extends Controller {
    public function index() {
        // -----------------------------------------------------
        // 1) التحقق من user_token وتسجيل الدخول
        // -----------------------------------------------------
        if (!isset($this->request->get['user_token']) || !isset($this->session->data['user_token'])
            || ($this->request->get['user_token'] != $this->session->data['user_token'])) {
            // إضافة سجل محاولة دخول غير مصرح بها إذا لزم الأمر
            $this->response->redirect($this->url->link('common/login'));
        }

        // -----------------------------------------------------
        // 2) تحميل ملف اللغة الأساسي للقائمة
        // -----------------------------------------------------
        $this->load->language('common/column_left');

        // -----------------------------------------------------
        // 3) تهيئة مصفوفة القوائم الرئيسية
        // -----------------------------------------------------
        $data['menus'] = array();

        // =======================================================================
        // (A) عرض المتجر الإلكتروني (رابط سريع)
        // المستخدمين: جميع المستخدمين المصرح لهم
        // الهدف: فتح واجهة المتجر الإلكتروني في تبويب جديد للمعاينة أو الاستخدام.
        // -----------------------------------------------------------------------
        $data['menus'][] = array(
            'id'       => 'menu-webstore-link',
            'icon'     => 'fa-globe',
            'name'     => $this->language->get('text_show_website'),
            'href'     => HTTPS_CATALOG, // يفترض أنه معرف في config.php
            'target'   => '_blank', // لفتحه في تبويب جديد
            'children' => array()
        );

        // =======================================================================
        // (B) لوحات المعلومات (Dashboards)
        // المستخدمين: الإدارة العليا، مدراء الأقسام، والمستخدمين حسب الحاجة.
        // الهدف: عرض ملخصات مرئية وتحليلات سريعة لأداء العمليات الرئيسية.
        // workflow: متابعة مؤشرات الأداء (KPIs)، مراقبة الأهداف، الاطلاع على التنبيهات الهامة.
        // -----------------------------------------------------------------------
        if ($this->user->hasPermission('access', 'common/dashboard')) {
            $dashboards = array();

            // لوحة المعلومات الرئيسية (الافتراضية)
            $dashboards[] = array(
                'name' => $this->language->get('text_main_dashboard'),
                'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'common/dashboard'
            );

            // لوحة مؤشرات الأداء الرئيسية (KPI Dashboard)
            if ($this->user->hasPermission('access', 'dashboard/kpi')) {
                $dashboards[] = array(
                    'name' => $this->language->get('text_kpi_dashboard'),
                    'href' => $this->url->link('dashboard/kpi', 'user_token=' . $this->session->data['user_token'], true),
                    'permission' => 'access',
                    'route' => 'dashboard/kpi'
                );
            }

            // لوحة متابعة الأهداف (Goals Dashboard)
            if ($this->user->hasPermission('access', 'dashboard/goals')) {
                $dashboards[] = array(
                    'name' => $this->language->get('text_goals_dashboard'),
                    'href' => $this->url->link('dashboard/goals', 'user_token=' . $this->session->data['user_token'], true),
                    'permission' => 'access',
                    'route' => 'dashboard/goals'
                );
            }

            // لوحة التنبيهات والإنذارات (Alerts Dashboard)
            if ($this->user->hasPermission('access', 'dashboard/alerts')) {
                $dashboards[] = array(
                    'name' => $this->language->get('text_alerts_dashboard'),
                    'href' => $this->url->link('dashboard/alerts', 'user_token=' . $this->session->data['user_token'], true),
                    'permission' => 'access',
                    'route' => 'dashboard/alerts'
                );
            }

            // لوحة تحليل المخزون الذكي (Inventory Analytics Dashboard)
            if ($this->user->hasPermission('access', 'dashboard/inventory_analytics')) {
                $dashboards[] = array(
                    'name' => $this->language->get('text_inventory_analytics_dashboard'),
                    'href' => $this->url->link('dashboard/inventory_analytics', 'user_token=' . $this->session->data['user_token'], true),
                    'permission' => 'access',
                    'route' => 'dashboard/inventory_analytics'
                );
            }

            // لوحة تحليل الربحية والتكاليف (Profitability Dashboard)
            if ($this->user->hasPermission('access', 'dashboard/profitability')) {
                $dashboards[] = array(
                    'name' => $this->language->get('text_profitability_dashboard'),
                    'href' => $this->url->link('dashboard/profitability', 'user_token=' . $this->session->data['user_token'], true),
                    'permission' => 'access',
                    'route' => 'dashboard/profitability'
                );
            }

            // إضافة قسم لوحات المعلومات إذا كان هناك أي لوحة متاحة
            if (count($dashboards) > 1) { // إذا كان هناك أكثر من لوحة واحدة (بالإضافة للرئيسية)
                $data['menus'][] = array(
                    'id'       => 'menu-dashboards',
                    'icon'     => 'fa-dashboard', // أو fa-tachometer
                    'name'     => $this->language->get('text_dashboards'),
                    'href'     => '',
                    'children' => $dashboards
                );
            } elseif (count($dashboards) == 1) { // إذا كانت اللوحة الرئيسية فقط متاحة
                $data['menus'][] = array(
                    'id'       => 'menu-dashboard-main',
                    'icon'     => 'fa-dashboard',
                    'name'     => $this->language->get('text_main_dashboard'),
                    'href'     => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
                    'children' => array()
                );
            }
        }

        // =======================================================================
        // (C) العمليات اليومية السريعة (Quick Operations / Daily Tasks)
        // المستخدمين: جميع الموظفين التنفيذيين للوصول السريع للمهام المتكررة.
        // الهدف: تسريع إنجاز المهام الروتينية دون الحاجة للتنقل العميق في القوائم.
        // ملاحظة: هذه الروابط تؤدي مباشرة لصفحة "إضافة" أو شاشة البحث/الاستعلام السريع.
        // -----------------------------------------------------------------------
        $daily_operations = array();

        // -- مهام المبيعات السريعة --
        $quick_sales = array();

        // إنشاء عرض سعر سريع
        if ($this->user->hasPermission('modify', 'sale/quote')) { // صلاحية إضافة
            $quick_sales[] = array(
                'name' => $this->language->get('text_quick_add_quote'),
                'href' => $this->url->link('sale/quote/add', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'sale/quote'
            );
        }

        // إنشاء طلب بيع سريع
        if ($this->user->hasPermission('modify', 'sale/order')) {
            $quick_sales[] = array(
                'name' => $this->language->get('text_quick_add_order'),
                'href' => $this->url->link('sale/order/add', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'sale/order'
            );
        }

        // شاشة تجهيز الطلبات للشحن (Picking/Packing)
        if ($this->user->hasPermission('access', 'shipping/prepare_orders')) { // صلاحية وصول للشاشة
            $quick_sales[] = array(
                'name' => $this->language->get('text_prepare_orders_screen'),
                'href' => $this->url->link('shipping/prepare_orders', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'shipping/prepare_orders'
            );
        }

        if ($quick_sales) {
            $daily_operations[] = array(
                'name' => $this->language->get('text_quick_sales_tasks'),
                'children' => $quick_sales
            );
        }

        // -- مهام المخزون السريعة --
        $quick_inventory = array();

        // إنشاء استلام بضائع سريع (من مورد)
        if ($this->user->hasPermission('modify', 'purchase/goods_receipt')) {
            $quick_inventory[] = array(
                'name' => $this->language->get('text_quick_add_receipt'),
                'href' => $this->url->link('purchase/goods_receipt/add', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'purchase/goods_receipt'
            );
        }

        // إنشاء تسوية مخزون سريعة
        if ($this->user->hasPermission('modify', 'inventory/adjustment')) {
            $quick_inventory[] = array(
                'name' => $this->language->get('text_quick_add_adjustment'),
                'href' => $this->url->link('inventory/adjustment/add', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'inventory/adjustment'
            );
        }

        // استعلام سريع عن حركة صنف
        if ($this->user->hasPermission('access', 'inventory/movement_history')) {
            $quick_inventory[] = array(
                'name' => $this->language->get('text_quick_stock_movement_query'),
                'href' => $this->url->link('inventory/movement_history', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'inventory/movement_history'
            );
        }

        // طباعة باركود سريع
        if ($this->user->hasPermission('access', 'inventory/barcode_print')) {
            $quick_inventory[] = array(
                'name' => $this->language->get('text_quick_barcode_print'),
                'href' => $this->url->link('inventory/barcode_print', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'inventory/barcode_print'
            );
        }

        if ($quick_inventory) {
            $daily_operations[] = array(
                'name' => $this->language->get('text_quick_inventory_tasks'),
                'children' => $quick_inventory
            );
        }

        // -- مهام مالية سريعة --
        $quick_finance = array();

        // إنشاء سند قبض سريع
        if ($this->user->hasPermission('modify', 'finance/receipt_voucher')) {
            $quick_finance[] = array(
                'name' => $this->language->get('text_quick_add_receipt_voucher'),
                'href' => $this->url->link('finance/receipt_voucher/add', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'finance/receipt_voucher'
            );
        }

        // إنشاء سند صرف سريع
        if ($this->user->hasPermission('modify', 'finance/payment_voucher')) {
            $quick_finance[] = array(
                'name' => $this->language->get('text_quick_add_payment_voucher'),
                'href' => $this->url->link('finance/payment_voucher/add', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'finance/payment_voucher'
            );
        }

        // إنشاء قيد يومية سريع
        if ($this->user->hasPermission('modify', 'accounts/journal')) {
            $quick_finance[] = array(
                'name' => $this->language->get('text_quick_add_journal'),
                'href' => $this->url->link('accounts/journal/add', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'accounts/journal'
            );
        }

        // استعلام سريع عن رصيد حساب
        if ($this->user->hasPermission('access', 'accounts/account_query')) {
            $quick_finance[] = array(
                'name' => $this->language->get('text_quick_account_balance_query'),
                'href' => $this->url->link('accounts/account_query', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'accounts/account_query'
            );
        }

        if ($quick_finance) {
            $daily_operations[] = array(
                'name' => $this->language->get('text_quick_finance_tasks'),
                'children' => $quick_finance
            );
        }

        if ($daily_operations) {
            $data['menus'][] = array(
                'id'       => 'menu-daily-operations',
                'icon'     => 'fa-flash', // أيقونة مناسبة للسرعة
                'name'     => $this->language->get('text_daily_operations'),
                'href'     => '',
                'children' => $daily_operations
            );
        }

        // =======================================================================
        // (D) المشتريات والموردين (Purchase & Suppliers)
        // المستخدمين: مسؤولي المشتريات، المحاسبين، مدراء المخازن.
        // الهدف: إدارة عمليات الشراء وعلاقات الموردين وسلسلة التوريد.
        // workflow: طلب شراء -> عروض أسعار -> أمر شراء -> استلام بضائع -> فاتورة مورد -> سداد -> مرتجعات.
        // -----------------------------------------------------------------------
        $purchase_cycle = array();

        // طلبات الشراء الداخلية
        if ($this->user->hasPermission('access', 'purchase/requisition')) {
            $purchase_cycle[] = array(
                'name' => $this->language->get('text_purchase_requisitions'),
                'href' => $this->url->link('purchase/requisition', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'purchase/requisition'
            );
        }

        // عروض أسعار الموردين
        if ($this->user->hasPermission('access', 'purchase/quotation')) {
            $purchase_cycle[] = array(
                'name' => $this->language->get('text_supplier_quotations'),
                'href' => $this->url->link('purchase/quotation', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'purchase/quotation'
            );
        }

        // أوامر الشراء
        if ($this->user->hasPermission('access', 'purchase/purchase_order')) {
            $purchase_cycle[] = array(
                'name' => $this->language->get('text_purchase_orders'),
                'href' => $this->url->link('purchase/purchase_order', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'purchase/purchase_order'
            );
        }

        // استلام البضائع
        if ($this->user->hasPermission('access', 'purchase/goods_receipt')) {
            $purchase_cycle[] = array(
                'name' => $this->language->get('text_goods_receipt_notes'),
                'href' => $this->url->link('purchase/goods_receipt', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'purchase/goods_receipt'
            );
        }

        // فواتير الموردين
        if ($this->user->hasPermission('access', 'purchase/supplier_invoice')) {
            $purchase_cycle[] = array(
                'name' => $this->language->get('text_supplier_invoices'),
                'href' => $this->url->link('purchase/supplier_invoice', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'purchase/supplier_invoice'
            );
        }

        // مرتجعات المشتريات
        if ($this->user->hasPermission('access', 'purchase/purchase_return')) {
            $purchase_cycle[] = array(
                'name' => $this->language->get('text_purchase_returns'),
                'href' => $this->url->link('purchase/purchase_return', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'purchase/purchase_return'
            );
        }

        // مقارنة عروض الأسعار
        if ($this->user->hasPermission('access', 'purchase/quotation_comparison')) {
            $purchase_cycle[] = array(
                'name' => $this->language->get('text_quotation_comparison'),
                'href' => $this->url->link('purchase/quotation_comparison', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'purchase/quotation_comparison'
            );
        }

        // تتبع طلبات الشراء
        if ($this->user->hasPermission('access', 'purchase/order_tracking')) {
            $purchase_cycle[] = array(
                'name' => $this->language->get('text_purchase_order_tracking'),
                'href' => $this->url->link('purchase/order_tracking', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'purchase/order_tracking'
            );
        }

        // إدارة عقود الموردين
        if ($this->user->hasPermission('access', 'purchase/supplier_contracts')) {
            $purchase_cycle[] = array(
                'name' => $this->language->get('text_supplier_contracts'),
                'href' => $this->url->link('purchase/supplier_contracts', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'purchase/supplier_contracts'
            );
        }

        // تخطيط المشتريات
        if ($this->user->hasPermission('access', 'purchase/planning')) {
            $purchase_cycle[] = array(
                'name' => $this->language->get('text_purchase_planning'),
                'href' => $this->url->link('purchase/planning', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'purchase/planning'
            );
        }

        // سجل دفعات الموردين
        if ($this->user->hasPermission('access', 'purchase/supplier_payments')) {
            $purchase_cycle[] = array(
                'name' => $this->language->get('text_supplier_payments'),
                'href' => $this->url->link('purchase/supplier_payments', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'purchase/supplier_payments'
            );
        }

        // إدارة الموردين
        $supplier_management = array();

        // الموردين
        if ($this->user->hasPermission('access', 'supplier/supplier')) {
            $supplier_management[] = array(
                'name' => $this->language->get('text_suppliers'),
                'href' => $this->url->link('supplier/supplier', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'supplier/supplier'
            );
        }

        // مجموعات الموردين
        if ($this->user->hasPermission('access', 'supplier/supplier_group')) {
            $supplier_management[] = array(
                'name' => $this->language->get('text_supplier_groups'),
                'href' => $this->url->link('supplier/supplier_group', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'supplier/supplier_group'
            );
        }

        // تقييم الموردين
        if ($this->user->hasPermission('access', 'supplier/evaluation')) {
            $supplier_management[] = array(
                'name' => $this->language->get('text_supplier_evaluation'),
                'href' => $this->url->link('supplier/evaluation', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'supplier/evaluation'
            );
        }

        // حسابات الموردين
        if ($this->user->hasPermission('access', 'supplier/accounts')) {
            $supplier_management[] = array(
                'name' => $this->language->get('text_supplier_accounts_ledger'),
                'href' => $this->url->link('supplier/accounts', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'supplier/accounts'
            );
        }

        // اتفاقيات أسعار الموردين
        if ($this->user->hasPermission('access', 'supplier/price_agreement')) {
            $supplier_management[] = array(
                'name' => $this->language->get('text_supplier_price_agreements'),
                'href' => $this->url->link('supplier/price_agreement', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'supplier/price_agreement'
            );
        }

        // تحليل أداء الموردين
        if ($this->user->hasPermission('access', 'supplier/performance')) {
            $supplier_management[] = array(
                'name' => $this->language->get('text_supplier_performance'),
                'href' => $this->url->link('supplier/performance', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'supplier/performance'
            );
        }

        // إدارة المستندات والوثائق
        if ($this->user->hasPermission('access', 'supplier/documents')) {
            $supplier_management[] = array(
                'name' => $this->language->get('text_supplier_documents'),
                'href' => $this->url->link('supplier/documents', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'supplier/documents'
            );
        }

        // سجل التواصل مع الموردين
        if ($this->user->hasPermission('access', 'supplier/communication')) {
            $supplier_management[] = array(
                'name' => $this->language->get('text_supplier_communication'),
                'href' => $this->url->link('supplier/communication', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'supplier/communication'
            );
        }

        // إعدادات المشتريات
        $purchase_settings = array();

        // إعدادات عامة للمشتريات
        if ($this->user->hasPermission('access', 'purchase/settings')) {
            $purchase_settings[] = array(
                'name' => $this->language->get('text_purchase_general_settings'),
                'href' => $this->url->link('purchase/settings', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'purchase/settings'
            );
        }

        // إعدادات الموافقات والصلاحيات
        if ($this->user->hasPermission('access', 'purchase/approval_settings')) {
            $purchase_settings[] = array(
                'name' => $this->language->get('text_purchase_approval_settings'),
                'href' => $this->url->link('purchase/approval_settings', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'purchase/approval_settings'
            );
        }

        // إعدادات الإشعارات والتنبيهات
        if ($this->user->hasPermission('access', 'purchase/notification_settings')) {
            $purchase_settings[] = array(
                'name' => $this->language->get('text_purchase_notification_settings'),
                'href' => $this->url->link('purchase/notification_settings', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'purchase/notification_settings'
            );
        }

        // إعدادات التقارير والتحليلات
        if ($this->user->hasPermission('access', 'purchase/report_settings')) {
            $purchase_settings[] = array(
                'name' => $this->language->get('text_purchase_report_settings'),
                'href' => $this->url->link('purchase/report_settings', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'purchase/report_settings'
            );
        }

        // إضافة قسم دورة الشراء إذا كان هناك أي عناصر متاحة
        if ($purchase_cycle) {
            $data['menus'][] = array(
                'id'       => 'menu-purchase-cycle',
                'icon'     => 'fa-refresh', // أيقونة مناسبة لدورة الشراء
                'name'     => $this->language->get('text_purchase_cycle_section'),
                'href'     => '',
                'children' => $purchase_cycle
            );
        }

        // إضافة قسم إدارة الموردين إذا كان هناك أي عناصر متاحة
        if ($supplier_management) {
            $data['menus'][] = array(
                'id'       => 'menu-supplier-management',
                'icon'     => 'fa-truck', // أيقونة مناسبة للموردين
                'name'     => $this->language->get('text_supplier_management_section'),
                'href'     => '',
                'children' => $supplier_management
            );
        }

        // إضافة قسم إعدادات المشتريات إذا كان هناك أي عناصر متاحة
        if ($purchase_settings) {
            $data['menus'][] = array(
                'id'       => 'menu-purchase-settings',
                'icon'     => 'fa-cogs', // أيقونة مناسبة للإعدادات
                'name'     => $this->language->get('text_purchase_settings_section'),
                'href'     => '',
                'children' => $purchase_settings
            );
        }

        // =======================================================================
        // (D) الانتقال من الأنظمة الأخرى (System Migration)
        // المستخدمين: مدراء النظام، مسؤولي البيانات، فريق الدعم الفني
        // الهدف: تسهيل عملية نقل البيانات من الأنظمة الأخرى إلى نظام AYM
        // workflow: استيراد -> مراجعة -> تنقيح -> اعتماد -> إدخال
        // -----------------------------------------------------------------------
        $migration = array();

        // الانتقال من نظام أودو
        if ($this->user->hasPermission('access', 'migration/odoo')) {
            $migration[] = array(
                'name' => $this->language->get('text_odoo_migration'),
                'href' => $this->url->link('migration/odoo', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'migration/odoo'
            );
        }

        // الانتقال من ووكومرس
        if ($this->user->hasPermission('access', 'migration/woocommerce')) {
            $migration[] = array(
                'name' => $this->language->get('text_woocommerce_migration'),
                'href' => $this->url->link('migration/woocommerce', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'migration/woocommerce'
            );
        }

        // الانتقال من شوبيفاي
        if ($this->user->hasPermission('access', 'migration/shopify')) {
            $migration[] = array(
                'name' => $this->language->get('text_shopify_migration'),
                'href' => $this->url->link('migration/shopify', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'migration/shopify'
            );
        }

        // استيراد من ملفات إكسل
        if ($this->user->hasPermission('access', 'migration/excel')) {
            $migration[] = array(
                'name' => $this->language->get('text_excel_migration'),
                'href' => $this->url->link('migration/excel', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'migration/excel'
            );
        }

        // مراجعة واعتماد البيانات المستوردة
        if ($this->user->hasPermission('access', 'migration/review')) {
            $migration[] = array(
                'name' => $this->language->get('text_migration_review'),
                'href' => $this->url->link('migration/review', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'migration/review'
            );
        }

        if ($migration) {
            $data['menus'][] = array(
                'id'       => 'menu-migration',
                'icon'     => 'fa-exchange', // أيقونة مناسبة للانتقال
                'name'     => $this->language->get('text_migration'),
                'href'     => '',
                'children' => $migration
            );
        }

        // =======================================================================
        // (E) الحوكمة والامتثال (Governance & Compliance)
        // المستخدمين: الإدارة العليا، مسؤولي الحوكمة، المدققين الداخليين.
        // الهدف: إدارة وتوثيق عمليات الحوكمة المؤسسية والامتثال والمخاطر.
        // workflow: متابعة الامتثال، التدقيق الداخلي، إدارة المخاطر، الاجتماعات.
        // -----------------------------------------------------------------------
        $governance = array();

        // سجل الامتثال
        if ($this->user->hasPermission('access', 'governance/compliance')) {
            $governance[] = array(
                'name' => $this->language->get('text_compliance'),
                'href' => $this->url->link('governance/compliance', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'governance/compliance'
            );
        }

        // التدقيق الداخلي
        if ($this->user->hasPermission('access', 'governance/internal_audit')) {
            $governance[] = array(
                'name' => $this->language->get('text_internal_audit'),
                'href' => $this->url->link('governance/internal_audit', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'governance/internal_audit'
            );
        }

        // سجل المخاطر
        if ($this->user->hasPermission('access', 'governance/risk_register')) {
            $governance[] = array(
                'name' => $this->language->get('text_risk_register'),
                'href' => $this->url->link('governance/risk_register', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'governance/risk_register'
            );
        }

        // الاجتماعات
        if ($this->user->hasPermission('access', 'governance/meetings')) {
            $governance[] = array(
                'name' => $this->language->get('text_meetings'),
                'href' => $this->url->link('governance/meetings', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'governance/meetings'
            );
        }

        if ($governance) {
            $data['menus'][] = array(
                'id'       => 'menu-governance',
                'icon'     => 'fa-balance-scale', // أيقونة مناسبة للحوكمة
                'name'     => $this->language->get('text_governance'),
                'href'     => '',
                'children' => $governance
            );
        }

        // =======================================================================
        // (1) المبيعات وإدارة علاقات العملاء (Sales & CRM)
        // المستخدمين: فرق المبيعات، خدمة العملاء، التسويق، الكاشير، مدير المبيعات، مدير الفرع.
        // الهدف: إدارة دورة المبيعات كاملة من العميل المحتمل حتى التحصيل وخدمة ما بعد البيع، وإدارة نقاط البيع.
        // workflow: دورة المبيعات (Lead -> Opportunity -> Quote -> Order -> Fulfillment -> Invoice -> Payment), دورة CRM, دورة POS, دورة التقسيط, دورة ما بعد البيع.
        // القيود المحاسبية الرئيسية:
        // - فاتورة مبيعات: من ح/العملاء أو النقدية أو البنك XXX إلى ح/المبيعات XXX وإلى ح/ضريبة القيمة المضافة XXX.
        // - تكلفة المبيعات (WAC): من ح/تكلفة البضاعة المباعة XXX إلى ح/المخزون XXX (تلقائي عند اكتمال الطلب/الشحن).
        // - المرتجعات: من ح/مرتجعات المبيعات ومردوداتها XXX ومن ح/ضريبة القيمة المضافة XXX إلى ح/العملاء أو النقدية XXX.
        // - عكس قيد التكلفة للمرتجع: من ح/المخزون XXX إلى ح/تكلفة البضاعة المباعة XXX.
        // - العمولات: من ح/مصروف عمولات مبيعات XXX إلى ح/عمولات مستحقة الدفع XXX.
        // - التقسيط (الفائدة): من ح/العملاء-تقسيط XXX إلى ح/إيرادات فوائد تقسيط XXX (عند الاستحقاق).
        // - تحصيل قسط: من ح/النقدية أو البنك XXX إلى ح/العملاء-تقسيط XXX.
        // -----------------------------------------------------------------------
        $sales_crm = array();

        // -- قسم نقطة البيع (POS) --
        // المكان الرئيسي الآن هنا لإدارة وتشغيل POS
        $pos_section = array();

        // الدخول لواجهة الكاشير
        if ($this->user->hasPermission('access', 'pos/pos')) {
            $pos_section[] = array(
                'name' => $this->language->get('text_pos_interface'),
                'href' => $this->url->link('pos/pos', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'pos/pos'
            );
        }

        // إدارة مناوبات الكاشير
        if ($this->user->hasPermission('access', 'pos/shift')) {
            $pos_section[] = array(
                'name' => $this->language->get('text_pos_shifts'),
                'href' => $this->url->link('pos/shift', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'pos/shift'
            );
        }

        // تسليم واستلام النقدية بين المناوبات
        if ($this->user->hasPermission('access', 'pos/cashier_handover')) {
            $pos_section[] = array(
                'name' => $this->language->get('text_cashier_handover'),
                'href' => $this->url->link('pos/cashier_handover', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'pos/cashier_handover'
            );
        }

        // تقارير نقاط البيع
        if ($this->user->hasPermission('access', 'pos/reports')) {
            $pos_section[] = array(
                'name' => $this->language->get('text_pos_reports'),
                'href' => $this->url->link('pos/reports', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'pos/reports'
            );
        }

        // إعدادات نقاط البيع (أجهزة، طابعات)
        if ($this->user->hasPermission('modify', 'pos/settings')) { // صلاحية تعديل للإعدادات
            $pos_section[] = array(
                'name' => $this->language->get('text_pos_settings'),
                'href' => $this->url->link('pos/settings', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'pos/settings'
            );
        }

        if ($pos_section) {
            $sales_crm[] = array(
                'name' => $this->language->get('text_pos_management_section'),
                'children' => $pos_section
            );
        }

        // -- قسم عمليات المبيعات --
        $sales_ops = array();

        // عروض الأسعار (Quotations)
        if ($this->user->hasPermission('access', 'sale/quote')) {
            $sales_ops[] = array(
                'name' => $this->language->get('text_quotations'),
                'href' => $this->url->link('sale/quote', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'sale/quote'
            );
        }

        // طلبات البيع (Sales Orders)
        if ($this->user->hasPermission('access', 'sale/order')) {
            $sales_ops[] = array(
                'name' => $this->language->get('text_sales_orders'),
                'href' => $this->url->link('sale/order', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'sale/order'
            );
        }

        // تنفيذ الطلبات - شاشة الكاشير المتقدمة
        if ($this->user->hasPermission('access', 'sale/order_processing')) {
            $sales_ops[] = array(
                'name' => 'تنفيذ الطلبات - الكاشير',
                'href' => $this->url->link('sale/order_processing', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'sale/order_processing'
            );
        }

        // تجهيز الطلبات للشحن
        if ($this->user->hasPermission('access', 'shipping/prepare_orders')) {
            $sales_ops[] = array(
                'name' => 'تجهيز الطلبات للشحن',
                'href' => $this->url->link('shipping/prepare_orders', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'shipping/prepare_orders'
            );
        }

        // الفوترة الإلكترونية - ETA
        if ($this->user->hasPermission('access', 'extension/eta/invoice')) {
            $sales_ops[] = array(
                'name' => 'الفوترة الإلكترونية - ETA',
                'href' => $this->url->link('extension/eta/invoice', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'extension/eta/invoice'
            );
        }

        // نظام التقسيط المتقدم
        if ($this->user->hasPermission('access', 'extension/payment/installment')) {
            $sales_ops[] = array(
                'name' => 'نظام التقسيط المتقدم',
                'href' => $this->url->link('extension/payment/installment', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'extension/payment/installment'
            );
        }

        // مرتجعات المبيعات (Sales Returns)
        if ($this->user->hasPermission('access', 'sale/return')) {
            $sales_ops[] = array(
                'name' => $this->language->get('text_sales_returns'),
                'href' => $this->url->link('sale/return', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'sale/return'
            );
        }

        // السلات المتروكة (Abandoned Carts) - متابعة وتحويل
        if ($this->user->hasPermission('access', 'sale/abandoned_cart')) {
            $sales_ops[] = array(
                'name' => $this->language->get('text_abandoned_carts'),
                'href' => $this->url->link('sale/abandoned_cart', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'sale/abandoned_cart'
            );
        }

        // تتبع الطلبات والشحنات (Order Tracking) - عرض حالة الشحن للعميل أو داخلياً
        if ($this->user->hasPermission('access', 'sale/order_tracking')) {
            $sales_ops[] = array(
                'name' => $this->language->get('text_order_shipment_tracking'),
                'href' => $this->url->link('sale/order_tracking', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'sale/order_tracking'
            );
        }

        if ($sales_ops) {
            $sales_crm[] = array(
                'name' => $this->language->get('text_sales_operations_section'),
                'children' => $sales_ops
            );
        }

        // -- قسم إدارة العملاء (Customer Management) --
        $customer_mgmt = array();

        // العملاء (Customers)
        if ($this->user->hasPermission('access', 'customer/customer')) {
            $customer_mgmt[] = array(
                'name' => $this->language->get('text_customers'),
                'href' => $this->url->link('customer/customer', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'customer/customer'
            );
        }

        // مجموعات العملاء (Customer Groups)
        if ($this->user->hasPermission('access', 'customer/customer_group')) {
            $customer_mgmt[] = array(
                'name' => $this->language->get('text_customer_groups'),
                'href' => $this->url->link('customer/customer_group', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'customer/customer_group'
            );
        }

        // برنامج الولاء (Loyalty Program) - إدارة النقاط والمكافآت
        if ($this->user->hasPermission('access', 'sale/loyalty')) {
            $customer_mgmt[] = array(
                'name' => $this->language->get('text_loyalty_program'),
                'href' => $this->url->link('sale/loyalty', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'sale/loyalty'
            );
        }

        // حدود الائتمان (Credit Limits) - تعريف ومتابعة حدود الائتمان للعملاء
        if ($this->user->hasPermission('access', 'customer/credit_limit')) {
            $customer_mgmt[] = array(
                'name' => $this->language->get('text_credit_limits'),
                'href' => $this->url->link('customer/credit_limit', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'customer/credit_limit'
            );
        }

        // ملاحظات العملاء (Customer Notes) - سجل داخلي لمتابعة تفاعلات العملاء
        if ($this->user->hasPermission('access', 'customer/note')) {
            $customer_mgmt[] = array(
                'name' => $this->language->get('text_customer_notes'),
                'href' => $this->url->link('customer/note', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'customer/note'
            );
        }

        // تذاكر الدعم الفني / خدمة العملاء (Support Tickets / Customer Service)
        // workflow: استلام > تصنيف > توجيه > معالجة > متابعة > إغلاق
        // users: فريق خدمة العملاء، الدعم الفني، مدير المبيعات
        // accounting: قد يؤدي لعمليات مرتجعات أو صيانة لها قيود.
        // ai_integration: تصنيف تلقائي، اقتراح حلول، تحليل أسباب الشكاوى، تنبؤ برضا العملاء.
        if ($this->user->hasPermission('access', 'customer/support_ticket')) {
            $customer_mgmt[] = array(
                'name' => $this->language->get('text_support_tickets'),
                'href' => $this->url->link('customer/support_ticket', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'customer/support_ticket'
            );
        }

        // التغذية الراجعة (Feedback) - يمكن دمجها مع التذاكر أو فصلها لجمع الاقتراحات
        if ($this->user->hasPermission('access', 'customer/feedback')) {
            $customer_mgmt[] = array(
                'name' => $this->language->get('text_customer_feedback_management'),
                'href' => $this->url->link('customer/feedback', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'customer/feedback'
            );
        }

        if ($customer_mgmt) {
            $sales_crm[] = array(
                'name' => $this->language->get('text_customer_management_section'),
                'children' => $customer_mgmt
            );
        }

        // -- قسم البيع بالتقسيط (Installment Sales) --
        $installment_sales = array();

        // قوالب خطط التقسيط (Installment Templates) - تعريف الشروط العامة للتقسيط
        if ($this->user->hasPermission('modify', 'sale/installment_template')) {
            $installment_sales[] = array(
                'name' => $this->language->get('text_installment_templates'),
                'href' => $this->url->link('sale/installment_template', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'sale/installment_template'
            );
        }

        // خطط التقسيط للعملاء (Customer Installment Plans) - إدارة خطط العملاء الفردية
        if ($this->user->hasPermission('access', 'sale/installment_plan')) {
            $installment_sales[] = array(
                'name' => $this->language->get('text_customer_installment_plans'),
                'href' => $this->url->link('sale/installment_plan', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'sale/installment_plan'
            );
        }

        // تسجيل مدفوعات الأقساط (Record Installment Payments)
        // القيد المحاسبي (السداد): من ح/النقدية أو البنوك XXX إلى ح/العملاء-التقسيط XXX
        if ($this->user->hasPermission('modify', 'sale/installment_payment')) {
            $installment_sales[] = array(
                'name' => $this->language->get('text_record_installment_payments'),
                'href' => $this->url->link('sale/installment_payment', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'sale/installment_payment'
            );
        }

        // تذكيرات الأقساط (Installment Reminders) - جدولة وإرسال التذكيرات
        if ($this->user->hasPermission('access', 'sale/installment_reminder')) {
            $installment_sales[] = array(
                'name' => $this->language->get('text_installment_reminders'),
                'href' => $this->url->link('sale/installment_reminder', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'sale/installment_reminder'
            );
        }

        // تقارير التقسيط (Installment Reports) - يمكن وضعها هنا أو تحت التقارير العامة
        if ($this->user->hasPermission('access', 'report/installment')) {
            $installment_sales[] = array(
                'name' => $this->language->get('text_installment_reports'),
                'href' => $this->url->link('report/installment', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'report/installment'
            );
        }

        if ($installment_sales) {
            $sales_crm[] = array(
                'name' => $this->language->get('text_installment_sales_section'),
                'children' => $installment_sales
            );
        }

        // -- CRM & Sales Management Section --
        $crm_module = array();

        // Pipeline Overview & Analytics
        if ($this->user->hasPermission('access', 'crm/pipeline')) {
            $crm_module[] = array(
                'name' => $this->language->get('text_sales_pipeline'),
                'href' => $this->url->link('crm/pipeline', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'crm/pipeline'
            );
        }

        // Sales Performance Dashboard
        if ($this->user->hasPermission('access', 'crm/dashboard')) {
            $crm_module[] = array(
                'name' => $this->language->get('text_sales_dashboard'),
                'href' => $this->url->link('crm/dashboard', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'crm/dashboard'
            );
        }

        // Leads Management
        if ($this->user->hasPermission('access', 'crm/lead')) {
            $crm_module[] = array(
                'name' => $this->language->get('text_leads'),
                'href' => $this->url->link('crm/lead', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'crm/lead'
            );
        }

        // Sales Opportunities & Pipeline Management
        if ($this->user->hasPermission('access', 'crm/opportunity')) {
            $crm_module[] = array(
                'name' => $this->language->get('text_sales_opportunities'),
                'href' => $this->url->link('crm/opportunity', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'crm/opportunity'
            );
        }

        // Deal Management & Tracking
        if ($this->user->hasPermission('access', 'crm/deal')) {
            $crm_module[] = array(
                'name' => $this->language->get('text_deal_management'),
                'href' => $this->url->link('crm/deal', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'crm/deal'
            );
        }

        // الحملات التسويقية (Campaigns)
        if ($this->user->hasPermission('access', 'crm/campaign')) {
            $crm_module[] = array(
                'name' => $this->language->get('text_marketing_campaigns'),
                'href' => $this->url->link('crm/campaign', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'crm/campaign'
            );
        }

        // جهات الاتصال (Contacts) - قد تكون مرتبطة بالعملاء أو الموردين أو مستقلة
        if ($this->user->hasPermission('access', 'crm/contact')) {
            $crm_module[] = array(
                'name' => $this->language->get('text_contacts_crm'),
                'href' => $this->url->link('crm/contact', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'crm/contact'
            );
        }

        // الصفقات (Deals) - قد تكون مرتبطة بالفرص أو الطلبات
        if ($this->user->hasPermission('access', 'crm/deal')) {
            $crm_module[] = array(
                'name' => $this->language->get('text_deals'),
                'href' => $this->url->link('crm/deal', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'crm/deal'
            );
        }

        // أنشطة CRM (Activities) - تسجيل المكالمات، الاجتماعات، المهام المتعلقة بالعملاء
        if ($this->user->hasPermission('access', 'crm/activity')) {
            $crm_module[] = array(
                'name' => $this->language->get('text_crm_activities'),
                'href' => $this->url->link('crm/activity', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'crm/activity'
            );
        }

        // تحليلات CRM (Analytics)
        if ($this->user->hasPermission('access', 'crm/analytics')) {
            $crm_module[] = array(
                'name' => $this->language->get('text_crm_analytics'),
                'href' => $this->url->link('crm/analytics', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'crm/analytics'
            );
        }

        if ($crm_module) {
            $sales_crm[] = array(
                'name' => $this->language->get('text_crm_section'),
                'children' => $crm_module
            );
        }

        // -- قسم خدمات ما بعد البيع (After-Sales Services) --
        $after_sales = array();

        // إدارة الضمان (Warranty Management)
        if ($this->user->hasPermission('access', 'service/warranty')) {
            $after_sales[] = array(
                'name' => $this->language->get('text_warranty_management'),
                'href' => $this->url->link('service/warranty', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'service/warranty'
            );
        }

        // طلبات الصيانة (Maintenance Requests)
        // القيد المحاسبي (إيراد صيانة): من ح/النقدية أو العملاء XXX إلى ح/إيرادات الصيانة XXX.
        // القيد المحاسبي (تكلفة قطع الغيار - WAC): من ح/تكلفة الصيانة أو مصروف قطع الغيار XXX إلى ح/المخزون-قطع غيار XXX.
        if ($this->user->hasPermission('access', 'service/maintenance')) {
            $after_sales[] = array(
                'name' => $this->language->get('text_maintenance_requests'),
                'href' => $this->url->link('service/maintenance', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'service/maintenance'
            );
        }

        // عقود الخدمة/الصيانة (Service Contracts) - إذا كانت تقدم بشكل منفصل
        if ($this->user->hasPermission('access', 'service/contract')) {
            $after_sales[] = array(
                'name' => $this->language->get('text_service_contracts'),
                'href' => $this->url->link('service/contract', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'service/contract'
            );
        }

        if ($after_sales) {
            $sales_crm[] = array(
                'name' => $this->language->get('text_after_sales_section'),
                'children' => $after_sales
            );
        }

        // -- قسم إعدادات المبيعات والتسعير --
        $sales_settings = array();

        // التسعير الديناميكي (Dynamic Pricing) - إدارة قواعد التسعير المتغير
        // AI Integration: تحليل تأثير السعر على الطلب، ضبط تلقائي للأسعار بناءً على المنافسين والمخزون والطلب.
        if ($this->user->hasPermission('modify', 'sale/dynamic_pricing')) {
            $sales_settings[] = array(
                'name' => $this->language->get('text_dynamic_pricing_rules'),
                'href' => $this->url->link('sale/dynamic_pricing', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'sale/dynamic_pricing'
            );
        }

        // إدارة القنوات المتعددة (Omnichannel Management) - ربط وتنسيق المبيعات عبر المتجر، الفروع، POS، إلخ.
        // AI Integration: تحسين توزيع المخزون بين القنوات، تحليل أداء كل قناة، توحيد تجربة العميل.
        if ($this->user->hasPermission('access', 'sale/omnichannel')) {
            $sales_settings[] = array(
                'name' => $this->language->get('text_omnichannel_management'),
                'href' => $this->url->link('sale/omnichannel', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'sale/omnichannel'
            );
        }

        // إعدادات عمولات المبيعات (Sales Commission Settings)
        if ($this->user->hasPermission('modify', 'sale/commission_settings')) {
            $sales_settings[] = array(
                'name' => $this->language->get('text_sales_commission_settings'),
                'href' => $this->url->link('sale/commission_settings', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'sale/commission_settings'
            );
        }

        if ($sales_settings) {
            $sales_crm[] = array(
                'name' => $this->language->get('text_sales_pricing_settings_section'),
                'children' => $sales_settings
            );
        }

        // إضافة القسم الرئيسي للمبيعات و CRM
        if ($sales_crm) {
            $data['menus'][] = array(
                'id'       => 'menu-sales-crm',
                'icon'     => 'fa-shopping-cart',
                'name'     => $this->language->get('text_sales_and_crm'),
                'href'     => '',
                'children' => $sales_crm
            );
        }

        // =======================================================================
        // (2) المشتريات والموردين (Purchasing & Suppliers)
        // المستخدمين: فرق المشتريات، مدير المشتريات، المحاسبين، أمناء المخازن (للاستلام)، الإدارة المالية.
        // الهدف: إدارة دورة الشراء من الطلب الداخلي حتى الدفع للمورد وتقييم الموردين وإدارة تكاليف الاستيراد.
        // workflow: دورة الشراء (Requisition -> RFQ -> PO -> Goods Receipt -> Supplier Invoice -> Payment), دورة إدارة الموردين, دورة الاستيراد والتكاليف الإضافية, المطابقة الثلاثية (3-Way Matching).
        // القيود المحاسبية وتأثير WAC:
        // - استلام البضائع (Goods Receipt - GRN): من ح/المخزون XXX إلى ح/المشتريات (حساب وسيط/مؤقت) XXX. [يتم تحديث كمية وتكلفة WAC للصنف المستلم].
        // - فاتورة المورد (Supplier Invoice): من ح/المشتريات (حساب وسيط) XXX ومن ح/ضريبة القيمة المضافة XXX إلى ح/الموردين XXX. [أي فروق سعرية بين GRN والفاتورة تعالج حسب الإعدادات، إما بتعديل تكلفة المخزون WAC أو تحميلها على حساب فروق أسعار المشتريات].
        // - تكاليف الاستيراد (Landed Costs): من ح/المخزون XXX (توزيع التكلفة على الأصناف) إلى ح/مصاريف الاستيراد المختلفة (شحن، جمارك...) XXX. [يؤدي إلى زيادة تكلفة WAC للأصناف المستوردة].
        // - سداد للمورد: من ح/الموردين XXX إلى ح/النقدية أو البنوك XXX.
        // - مرتجع مشتريات: من ح/الموردين XXX إلى ح/المخزون XXX [يخفض المخزون بتكلفة WAC الحالية للصنف].
        // -----------------------------------------------------------------------
        $purchasing = array();

        // -- قسم دورة الشراء الأساسية --
        $purchase_cycle = array();

        // طلبات الشراء الداخلية (Purchase Requisitions) - من الأقسام المختلفة
        // مطور بمستوى عالمي: موافقات متعددة المستويات، ربط بالموازنات، تتبع حالة، إشعارات ذكية
        if ($this->user->hasPermission('access', 'purchase/requisition')) {
            $purchase_cycle[] = array(
                'name' => $this->language->get('text_purchase_requisitions'),
                'href' => $this->url->link('purchase/requisition', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'purchase/requisition'
            );
        }

        // طلبات عروض الأسعار للموردين (Requests for Quotation - RFQ)
        // مطور بمستوى عالمي: إرسال تلقائي، مقارنة ذكية، تحليل أسعار، تقييم موردين
        if ($this->user->hasPermission('access', 'purchase/rfq')) {
            $purchase_cycle[] = array(
                'name' => $this->language->get('text_request_for_quotations'),
                'href' => $this->url->link('purchase/rfq', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'purchase/rfq'
            );
        }

        // عروض أسعار الموردين (Supplier Quotations) - تسجيل ومقارنة العروض
        // مطور بمستوى عالمي: مقارنة متقدمة، تحليل تكلفة إجمالية، تقييم شروط، اختيار ذكي
        if ($this->user->hasPermission('access', 'purchase/quotation')) {
            $purchase_cycle[] = array(
                'name' => $this->language->get('text_supplier_quotations'),
                'href' => $this->url->link('purchase/quotation', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'purchase/quotation'
            );
        }

        // أوامر الشراء (Purchase Orders - PO)
        // مطور بمستوى عالمي: إنشاء تلقائي، تتبع متقدم، ربط محاسبي، موافقات ذكية، طباعة احترافية
        // workflow: إنشاء من طلب شراء أو عرض سعر > إرسال للمورد > تتبع حالة > ربط بالاستلام والفواتير
        if ($this->user->hasPermission('access', 'purchase/order')) {
            $purchase_cycle[] = array(
                'name' => $this->language->get('text_purchase_orders'),
                'href' => $this->url->link('purchase/order', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'purchase/order'
            );
        }

        // إيصالات استلام البضائع (Goods Receipt Notes - GRN)
        // مطور بمستوى عالمي: فحص جودة متقدم، تحديث WAC تلقائي، قيود محاسبية فورية، تتبع دفعات
        // workflow: استلام فعلي > تسجيل الكميات > فحص جودة (إن لزم) > تحديث المخزون (كمية وتكلفة WAC) > إنشاء قيد استلام وسيط.
        if ($this->user->hasPermission('access', 'purchase/goods_receipt')) {
            $purchase_cycle[] = array(
                'name' => $this->language->get('text_goods_receipt_notes'),
                'href' => $this->url->link('purchase/goods_receipt', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'purchase/goods_receipt'
            );
        }

        // فواتير الموردين (Supplier Invoices)
        // مطور بمستوى عالمي: مطابقة ثلاثية ذكية، معالجة ضرائب متقدمة، قيود تلقائية، موافقات متعددة
        // workflow: استلام الفاتورة > تسجيلها > مطابقتها مع PO و GRN > معالجة الفروق > إنشاء قيد استحقاق المورد.
        if ($this->user->hasPermission('access', 'purchase/supplier_invoice')) {
            $purchase_cycle[] = array(
                'name' => $this->language->get('text_supplier_invoices'),
                'href' => $this->url->link('purchase/supplier_invoice', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'purchase/supplier_invoice'
            );
        }

        // المطابقة الثلاثية (3-Way Matching) - شاشة مخصصة لمطابقة PO, GRN, Invoice
        // مطور بمستوى عالمي: مطابقة ذكية بالذكاء الاصطناعي، كشف الفروق، تحليل انحرافات، موافقات تلقائية
        // الهدف: التأكد من تطابق الكميات والأسعار قبل اعتماد فاتورة المورد للدفع.
        if ($this->user->hasPermission('access', 'purchase/three_way_matching')) {
            $purchase_cycle[] = array(
                'name' => $this->language->get('text_three_way_matching'),
                'href' => $this->url->link('purchase/three_way_matching', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'purchase/three_way_matching'
            );
        }

        // مرتجعات المشتريات (Purchase Returns)
        // مطور بمستوى عالمي: أسباب إرجاع متقدمة، تحديث WAC عكسي، قيود تلقائية، تتبع جودة
        if ($this->user->hasPermission('access', 'purchase/return')) {
            $purchase_cycle[] = array(
                'name' => $this->language->get('text_purchase_returns'),
                'href' => $this->url->link('purchase/return', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'purchase/return'
            );
        }

        // مدفوعات الموردين (Vendor Payments) - تسجيل ودفع مستحقات الموردين
        // مطور بمستوى عالمي: دفعات متعددة الطرق، خصومات تلقائية، ربط بنكي، موافقات متدرجة
        if ($this->user->hasPermission('access', 'purchase/vendor_payment')) {
            $purchase_cycle[] = array(
                'name' => $this->language->get('text_vendor_payments'),
                'href' => $this->url->link('purchase/vendor_payment', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'purchase/vendor_payment'
            );
        }

        if ($purchase_cycle) {
            $purchasing[] = array(
                'name' => $this->language->get('text_purchase_cycle_section'),
                'children' => $purchase_cycle
            );
        }

        // -- قسم إدارة الموردين --
        $supplier_mgmt = array();

        // الموردين (Suppliers / Vendors)
        if ($this->user->hasPermission('access', 'supplier/supplier')) {
            $supplier_mgmt[] = array(
                'name' => $this->language->get('text_suppliers'),
                'href' => $this->url->link('supplier/supplier', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'supplier/supplier'
            );
        }

        // مجموعات الموردين (Supplier Groups)
        if ($this->user->hasPermission('access', 'supplier/supplier_group')) {
            $supplier_mgmt[] = array(
                'name' => $this->language->get('text_supplier_groups'),
                'href' => $this->url->link('supplier/supplier_group', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'supplier/supplier_group'
            );
        }

        // تقييم الموردين (Supplier Evaluation)
        if ($this->user->hasPermission('access', 'supplier/evaluation')) {
            $supplier_mgmt[] = array(
                'name' => $this->language->get('text_supplier_evaluation'),
                'href' => $this->url->link('supplier/evaluation', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'supplier/evaluation'
            );
        }

        // حسابات الموردين (Supplier Accounts / Ledger) - كشوف حساب وتقارير أعمار الديون
        if ($this->user->hasPermission('access', 'supplier/account')) {
            $supplier_mgmt[] = array(
                'name' => $this->language->get('text_supplier_accounts_ledger'),
                'href' => $this->url->link('supplier/account', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'supplier/account'
            );
        }

        // اتفاقيات الأسعار مع الموردين (Supplier Price Agreements / Lists)
        if ($this->user->hasPermission('modify', 'supplier/price_agreement')) {
            $supplier_mgmt[] = array(
                'name' => $this->language->get('text_supplier_price_agreements'),
                'href' => $this->url->link('supplier/price_agreement', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'supplier/price_agreement'
            );
        }

        if ($supplier_mgmt) {
            $purchasing[] = array(
                'name' => $this->language->get('text_supplier_management_section'),
                'children' => $supplier_mgmt
            );
        }

        // -- قسم الاستيراد والتكاليف الإضافية (Import & Landed Costs) --
        $import_landed_costs = array();

        // إدارة شحنات الاستيراد (Import Shipments) - لتتبع الشحنات وتجميع التكاليف
        if ($this->user->hasPermission('access', 'import/shipment')) {
            $import_landed_costs[] = array(
                'name' => $this->language->get('text_import_shipment_management'),
                'href' => $this->url->link('import/shipment', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'import/shipment'
            );
        }

        // تسجيل تكاليف الاستيراد (Landed Cost Vouchers) - تسجيل فواتير التخليص، الشحن، الجمارك وربطها بالشحنة
        if ($this->user->hasPermission('modify', 'import/cost_voucher')) {
            $import_landed_costs[] = array(
                'name' => $this->language->get('text_record_landed_costs'),
                'href' => $this->url->link('import/cost_voucher', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'import/cost_voucher'
            );
        }

        // توزيع تكاليف الاستيراد (Landed Cost Allocation) - توزيع التكاليف المسجلة على أصناف الشحنة
        // تأثير محاسبي وتكلفة WAC: زيادة تكلفة الأصناف المستلمة (WAC) بقيمة التكاليف الموزعة.
        if ($this->user->hasPermission('modify', 'import/allocation')) {
            $import_landed_costs[] = array(
                'name' => $this->language->get('text_allocate_landed_costs'),
                'href' => $this->url->link('import/allocation', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'import/allocation'
            );
        }

        // تتبع شحنات الاستيراد (Import Tracking) - ربط مع شركات الشحن أو تحديث يدوي لحالة الشحنة
        if ($this->user->hasPermission('access', 'import/tracking')) {
            $import_landed_costs[] = array(
                'name' => $this->language->get('text_import_shipment_tracking'),
                'href' => $this->url->link('import/tracking', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'import/tracking'
            );
        }

        if ($import_landed_costs) {
            $purchasing[] = array(
                'name' => $this->language->get('text_import_landed_costs_section'),
                'children' => $import_landed_costs
            );
        }

        // -- قسم الأنظمة المتقدمة للمشتريات (Advanced Purchase Systems) --
        // مطور بمستوى عالمي مثل SAP وOracle وOdoo وMicrosoft Dynamics
        $advanced_purchase_systems = array();

        // نظام التكامل المحاسبي المتقدم للمشتريات
        if ($this->user->hasPermission('access', 'purchase/accounting_integration_advanced')) {
            $advanced_purchase_systems[] = array(
                'name' => 'التكامل المحاسبي المتقدم',
                'href' => $this->url->link('purchase/accounting_integration_advanced', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'purchase/accounting_integration_advanced'
            );
        }

        // نظام إدارة التكلفة المتقدم (WAC + Landed Costs)
        if ($this->user->hasPermission('access', 'purchase/cost_management_advanced')) {
            $advanced_purchase_systems[] = array(
                'name' => 'إدارة التكلفة المتقدمة',
                'href' => $this->url->link('purchase/cost_management_advanced', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'purchase/cost_management_advanced'
            );
        }

        // نظام الموافقات الذكي
        if ($this->user->hasPermission('access', 'purchase/smart_approval_system')) {
            $advanced_purchase_systems[] = array(
                'name' => 'نظام الموافقات الذكي',
                'href' => $this->url->link('purchase/smart_approval_system', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'purchase/smart_approval_system'
            );
        }

        // نظام تحليل الموردين المتقدم
        if ($this->user->hasPermission('access', 'purchase/supplier_analytics_advanced')) {
            $advanced_purchase_systems[] = array(
                'name' => 'تحليل الموردين المتقدم',
                'href' => $this->url->link('purchase/supplier_analytics_advanced', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'purchase/supplier_analytics_advanced'
            );
        }

        if ($advanced_purchase_systems) {
            $purchasing[] = array(
                'name' => 'الأنظمة المتقدمة (Enterprise Level)',
                'children' => $advanced_purchase_systems
            );
        }

        // إضافة القسم الرئيسي للمشتريات والموردين
        if ($purchasing) {
            $data['menus'][] = array(
                'id'       => 'menu-purchasing',
                'icon'     => 'fa-truck',
                'name'     => $this->language->get('text_purchasing_and_suppliers'),
                'href'     => '',
                'children' => $purchasing
            );
        }

        // =======================================================================
        // (3) المخزون والمستودعات (Inventory & Warehouse)
        // المستخدمين: أمناء المخازن، مدير المخازن، مدير اللوجستيات، فرق الجرد، المحاسبين (للتكاليف والتقارير).
        // الهدف: الإدارة الشاملة للأصناف، الكميات، التكاليف (WAC)، الجرد، التحويلات، الفروع والمستودعات المتعددة، وتتبع الوحدات والتشغيلات وتواريخ الصلاحية.
        // workflow: دورة حياة الصنف، دورة الاستلام والصرف، دورة الجرد والتسوية، دورة التحويلات، دورة حساب التكلفة (WAC).
        // القيود المحاسبية وتأثير WAC: (يتم إنشاؤها بواسطة العمليات المرتبطة مثل الشراء والبيع والتسويات)
        // - التكلفة (WAC): يتم تحديثها مع *كل* عملية استلام بضاعة (شراء، مرتجع مبيعات، تسوية بالزيادة) أو توزيع تكاليف إضافية (استيراد). المعادلة الأساسية: ((الكمية القديمة * التكلفة القديمة WAC) + (الكمية الجديدة * تكلفة الشراء الجديدة)) / (الكمية القديمة + الكمية الجديدة).
        // - الصرف (بيع، تحويل، تسوية بالنقص): يتم الصرف دائماً بـ *آخر* تكلفة متوسط مرجح (WAC) محسوبة للصنف في ذلك الفرع/المستودع.
        // - تسوية بالزيادة: من ح/المخزون XXX إلى ح/تسويات المخزون أو إيرادات أخرى XXX. [تزيد كمية وتؤثر على WAC إذا تم إدخال تكلفة].
        // - تسوية بالنقص: من ح/تسويات المخزون أو مصروفات أخرى XXX إلى ح/المخزون XXX. [تخفض الكمية بالقيمة المحسوبة من WAC].
        // - التحويل: من ح/المخزون-فرع مستلم XXX إلى ح/المخزون-فرع مرسل XXX. [يتم التحويل بنفس قيمة WAC في الفرع المرسل].
        // - الجرد: فروقات الجرد (عجز أو زيادة) تعالج كقيود تسوية.
        // - ربط الحسابات: يتم تحديد الحسابات المتأثرة (مخزون، تكلفة مبيعات، تسويات) من خلال جدول `cod_inventory_account_mapping`.
        // -----------------------------------------------------------------------
        $inventory_warehouse = array();

        // -- قسم إدارة المنتجات والأصناف (Product & Item Management) --
        // الهدف: فصل إدارة المنتجات عن المتجر وجعل المخزون هو الأساس (مثل Odoo)
        // المخزون يدير: المنتجات، التصنيفات، العلامات التجارية، التكويد، التسعير، الوحدات، الخيارات، الباركود
        // المتجر يستخدم: بيانات المخزون للعرض والبيع فقط
        $product_management = array();

        // إدارة المنتجات المتقدمة (Advanced Product Management) - أفضل من Odoo
        if ($this->user->hasPermission('access', 'inventory/product')) {
            $product_management[] = array(
                'name' => $this->language->get('text_advanced_product_management'),
                'href' => $this->url->link('inventory/product', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'inventory/product'
            );
        }

        // إدارة التصنيفات والأقسام (Categories Management) - هيكل شجري متطور
        if ($this->user->hasPermission('access', 'inventory/category')) {
            $product_management[] = array(
                'name' => $this->language->get('text_categories_management'),
                'href' => $this->url->link('inventory/category', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'inventory/category'
            );
        }

        // إدارة العلامات التجارية (Brands Management) - مع ربط محاسبي
        if ($this->user->hasPermission('access', 'inventory/manufacturer')) {
            $product_management[] = array(
                'name' => $this->language->get('text_brands_management'),
                'href' => $this->url->link('inventory/manufacturer', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'inventory/manufacturer'
            );
        }

        // نظام التكويد الذكي (Smart Coding System) - تكويد تلقائي متطور
        if ($this->user->hasPermission('access', 'inventory/product_coding')) {
            $product_management[] = array(
                'name' => $this->language->get('text_smart_coding_system'),
                'href' => $this->url->link('inventory/product_coding', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'inventory/product_coding'
            );
        }

        // إدارة التسعير المتطور (Advanced Pricing Management) - 5 مستويات
        if ($this->user->hasPermission('access', 'inventory/pricing')) {
            $product_management[] = array(
                'name' => $this->language->get('text_advanced_pricing_management'),
                'href' => $this->url->link('inventory/pricing', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'inventory/pricing'
            );
        }

        // إدارة الوحدات المتطورة (Advanced Units Management) - تحويل تلقائي
        if ($this->user->hasPermission('access', 'inventory/units')) {
            $product_management[] = array(
                'name' => $this->language->get('text_advanced_units_management'),
                'href' => $this->url->link('inventory/units', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'inventory/units'
            );
        }

        // إدارة خيارات المنتج (Product Options Management) - مرتبطة بالوحدات (ميزة فريدة)
        if ($this->user->hasPermission('access', 'inventory/product_options')) {
            $product_management[] = array(
                'name' => $this->language->get('text_product_options_management'),
                'href' => $this->url->link('inventory/product_options', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'inventory/product_options'
            );
        }

        // إدارة الباركود المتقدم (Advanced Barcode Management) - متعدد الأنواع
        if ($this->user->hasPermission('access', 'inventory/barcode')) {
            $product_management[] = array(
                'name' => $this->language->get('text_advanced_barcode_management'),
                'href' => $this->url->link('inventory/barcode', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'inventory/barcode'
            );
        }

        // إدارة الباقات والخصومات (Bundles & Discounts Management) - نظام معقد
        if ($this->user->hasPermission('access', 'inventory/bundles')) {
            $product_management[] = array(
                'name' => $this->language->get('text_bundles_discounts_management'),
                'href' => $this->url->link('inventory/bundles', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'inventory/bundles'
            );
        }

        if ($product_management) {
            $inventory_warehouse[] = array(
                'name' => $this->language->get('text_product_item_management_section'),
                'children' => $product_management
            );
        }

        // -- قسم نظرة عامة وتقارير المخزون --
        $inv_overview = array();

        // لوحة معلومات المخزون (Inventory Dashboard) - مخصصة للمخزون
        if ($this->user->hasPermission('access', 'inventory/dashboard')) {
            $inv_overview[] = array(
                'name' => $this->language->get('text_inventory_dashboard'),
                'href' => $this->url->link('inventory/dashboard', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'inventory/dashboard'
            );
        }

        // استعلام الأرصدة الحالية (Current Stock Levels) - عرض الأرصدة بالفروع والوحدات
        if ($this->user->hasPermission('access', 'inventory/current_stock')) {
            $inv_overview[] = array(
                'name' => $this->language->get('text_current_stock_levels'),
                'href' => $this->url->link('inventory/current_stock', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'inventory/current_stock'
            );
        }

        // سجل حركة المخزون (Stock Movement History / Ledger) - تتبع تفصيلي لكل الحركات
        if ($this->user->hasPermission('access', 'inventory/movement_history')) {
            $inv_overview[] = array(
                'name' => $this->language->get('text_stock_movement_ledger'),
                'href' => $this->url->link('inventory/movement_history', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'inventory/movement_history'
            );
        }

        // تقرير تقييم المخزون (Inventory Valuation Report) - عرض قيمة المخزون الحالية بالتكلفة (WAC)
        if ($this->user->hasPermission('access', 'report/inventory_valuation')) {
            $inv_overview[] = array(
                'name' => $this->language->get('text_inventory_valuation_report'),
                'href' => $this->url->link('report/inventory_valuation', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'report/inventory_valuation'
            );
        }

        // تقرير المخزون حسب الفرع (Inventory by Branch Report)
        if ($this->user->hasPermission('access', 'report/inventory_by_branch')) {
            $inv_overview[] = array(
                'name' => $this->language->get('text_inventory_by_branch_report'),
                'href' => $this->url->link('report/inventory_by_branch', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'report/inventory_by_branch'
            );
        }

        if ($inv_overview) {
            $inventory_warehouse[] = array(
                'name' => $this->language->get('text_inventory_overview_reports_section'),
                'children' => $inv_overview
            );
        }

        // -- قسم عمليات المخزون --
        $inv_operations = array();

        // الجرد المخزني (Stock Counting / Physical Inventory) - إنشاء وإدارة جلسات الجرد
        // workflow: إنشاء جرد > طباعة قوائم الجرد > تسجيل الكميات الفعلية > مراجعة الفروقات > تطبيق التسويات (إنشاء قيود تسوية آلية).
        if ($this->user->hasPermission('access', 'inventory/stocktake')) {
            $inv_operations[] = array(
                'name' => $this->language->get('text_stock_counting'),
                'href' => $this->url->link('inventory/stocktake', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'inventory/stocktake'
            );
        }

        // تسويات المخزون (Stock Adjustments) - للمعالجة اليدوية للزيادة أو النقص (تالف، عينات، إلخ)
        if ($this->user->hasPermission('access', 'inventory/adjustment')) {
            $inv_operations[] = array(
                'name' => $this->language->get('text_stock_adjustments'),
                'href' => $this->url->link('inventory/adjustment', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'inventory/adjustment'
            );
        }

        // التحويلات المخزنية (Stock Transfers) - نقل البضائع بين الفروع والمستودعات
        if ($this->user->hasPermission('access', 'inventory/transfer')) {
            $inv_operations[] = array(
                'name' => $this->language->get('text_stock_transfers'),
                'href' => $this->url->link('inventory/transfer', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'inventory/transfer'
            );
        }

        // طباعة الباركود (Barcode Printing) - المكان الرئيسي لطباعة الباركود للأصناف
        if ($this->user->hasPermission('access', 'inventory/barcode_print')) {
            $inv_operations[] = array(
                'name' => $this->language->get('text_barcode_printing'),
                'href' => $this->url->link('inventory/barcode_print', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'inventory/barcode_print'
            );
        }

        // إدارة الحجوزات (Inventory Reservations) - حجز كميات لطلبات أو عروض أسعار معينة
        if ($this->user->hasPermission('access', 'inventory/reservation')) {
            $inv_operations[] = array(
                'name' => $this->language->get('text_inventory_reservations'),
                'href' => $this->url->link('inventory/reservation', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'inventory/reservation'
            );
        }

        // صرف المستلزمات الداخلية (Internal Consumption/Issue)
        // workflow: طلب > موافقة > صرف من المخزن > تسجيل القيد المحاسبي (من ح/مصروف القسم XXX إلى ح/المخزون XXX).
        if ($this->user->hasPermission('access', 'inventory/internal_issue')) {
            $inv_operations[] = array(
                'name' => $this->language->get('text_internal_consumption'),
                'href' => $this->url->link('inventory/internal_issue', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'inventory/internal_issue'
            );
        }

        if ($inv_operations) {
            $inventory_warehouse[] = array(
                'name' => $this->language->get('text_inventory_operations_section'),
                'children' => $inv_operations
            );
        }

        // -- قسم إدارة التكاليف والتتبع --
        $cost_tracking = array();

        // سجل تكلفة الصنف (Item Cost History) - تتبع التغيرات في المتوسط المرجح (WAC) للصنف
        if ($this->user->hasPermission('access', 'inventory/cost_history')) {
            $cost_tracking[] = array(
                'name' => $this->language->get('text_item_cost_history_wac'),
                'href' => $this->url->link('inventory/cost_history', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'inventory/cost_history'
            );
        }

        // تحديث التكلفة اليدوي (Manual Cost Update) - لتعديل تكلفة صنف بشكل يدوي مع إنشاء قيد تسوية
        // القيد (زيادة): من ح/المخزون XXX إلى ح/تسويات تكلفة المخزون XXX.
        // القيد (نقص): من ح/تسويات تكلفة المخزون XXX إلى ح/المخزون XXX.
        // **تحذير:** يجب استخدامها بحذر شديد لأنها تتجاوز حساب WAC الآلي.
        if ($this->user->hasPermission('modify', 'inventory/manual_cost_update')) {
            $cost_tracking[] = array(
                'name' => $this->language->get('text_manual_cost_update'),
                'href' => $this->url->link('inventory/manual_cost_update', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'inventory/manual_cost_update'
            );
        }

        // إعادة تقييم المخزون (Inventory Revaluation) - لتطبيق سياسة التكلفة أو السوق أيهما أقل أو لتعديل جماعي
        // القيد: من/إلى ح/المخزون XXX من/إلى ح/أرباح أو خسائر إعادة تقييم المخزون XXX.
        if ($this->user->hasPermission('modify', 'inventory/revaluation')) {
            $cost_tracking[] = array(
                'name' => $this->language->get('text_inventory_revaluation'),
                'href' => $this->url->link('inventory/revaluation', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'inventory/revaluation'
            );
        }

        // تتبع تاريخ الصلاحية (Expiry Date Tracking) - إدارة ومتابعة الأصناف حسب تاريخ الانتهاء
        if ($this->user->hasPermission('access', 'inventory/expiry_tracking')) {
            $cost_tracking[] = array(
                'name' => $this->language->get('text_expiry_date_tracking'),
                'href' => $this->url->link('inventory/expiry_tracking', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'inventory/expiry_tracking'
            );
        }

        // تتبع التشغيلات/الدفعات (Batch/Lot Tracking) - إدارة ومتابعة الأصناف حسب رقم التشغيلة
        if ($this->user->hasPermission('access', 'inventory/batch_tracking')) {
            $cost_tracking[] = array(
                'name' => $this->language->get('text_batch_lot_tracking'),
                'href' => $this->url->link('inventory/batch_tracking', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'inventory/batch_tracking'
            );
        }

        // تتبع الأرقام التسلسلية (Serial Number Tracking) - إدارة ومتابعة الأصناف ذات الأرقام التسلسلية
        if ($this->user->hasPermission('access', 'inventory/serial_tracking')) {
            $cost_tracking[] = array(
                'name' => $this->language->get('text_serial_number_tracking'),
                'href' => $this->url->link('inventory/serial_tracking', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'inventory/serial_tracking'
            );
        }

        // مطابقة المخزون مع الحسابات (Inventory-Accounting Reconciliation) - تقرير وتحليل الفروقات
        if ($this->user->hasPermission('access', 'inventory/accounting_reconciliation')) {
            $cost_tracking[] = array(
                'name' => $this->language->get('text_inventory_accounting_reconciliation'),
                'href' => $this->url->link('inventory/accounting_reconciliation', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'inventory/accounting_reconciliation'
            );
        }

        if ($cost_tracking) {
            $inventory_warehouse[] = array(
                'name' => $this->language->get('text_costing_tracking_section'),
                'children' => $cost_tracking
            );
        }

        // -- قسم تخطيط وتحليل المخزون --
        $inv_planning = array();

        // تحليل ABC (ABC Analysis) - تصنيف الأصناف حسب الأهمية (القيمة أو الحركة)
        if ($this->user->hasPermission('access', 'inventory/abc_analysis')) {
            $inv_planning[] = array(
                'name' => $this->language->get('text_abc_analysis'),
                'href' => $this->url->link('inventory/abc_analysis', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'inventory/abc_analysis'
            );
        }

        // معدل دوران المخزون (Inventory Turnover) - تحليل سرعة بيع المخزون
        if ($this->user->hasPermission('access', 'inventory/turnover')) {
            $inv_planning[] = array(
                'name' => $this->language->get('text_inventory_turnover_analysis'),
                'href' => $this->url->link('inventory/turnover', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'inventory/turnover'
            );
        }

        // تحليل المخزون الراكد وبطيء الحركة (Slow-Moving & Obsolete Stock Analysis)
        if ($this->user->hasPermission('access', 'inventory/slow_moving')) {
            $inv_planning[] = array(
                'name' => $this->language->get('text_slow_moving_obsolete_analysis'),
                'href' => $this->url->link('inventory/slow_moving', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'inventory/slow_moving'
            );
        }

        // التنبؤ بالطلب والمخزون (Demand & Inventory Forecasting)
        // AI Integration: استخدام نماذج تنبؤ متقدمة تأخذ في الاعتبار الموسمية والاتجاهات والعوامل الخارجية.
        if ($this->user->hasPermission('access', 'inventory/forecasting')) {
            $inv_planning[] = array(
                'name' => $this->language->get('text_demand_inventory_forecasting'),
                'href' => $this->url->link('inventory/forecasting', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'inventory/forecasting'
            );
        }

        // إدارة نقاط إعادة الطلب (Reorder Point Management) - تحديد الحد الأدنى والأقصى ونقطة إعادة الطلب
        if ($this->user->hasPermission('modify', 'inventory/reorder_points')) {
            $inv_planning[] = array(
                'name' => $this->language->get('text_reorder_point_management'),
                'href' => $this->url->link('inventory/reorder_points', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'inventory/reorder_points'
            );
        }

        if ($inv_planning) {
            $inventory_warehouse[] = array(
                'name' => $this->language->get('text_inventory_planning_analysis_section'),
                'children' => $inv_planning
            );
        }

        // -- قسم ضبط الجودة (Quality Control) --
        $quality_control = array();

        // فحص جودة الاستلام (Incoming Quality Inspection) - مرتبط بإذن الاستلام (GRN)
        if ($this->user->hasPermission('access', 'inventory/quality_inspection')) {
            $quality_control[] = array(
                'name' => $this->language->get('text_incoming_quality_inspection'),
                'href' => $this->url->link('inventory/quality_inspection', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'inventory/quality_inspection'
            );
        }

        // إدارة معايير الجودة (Quality Management Settings) - تعريف معايير الفحص وإجراءاته
        if ($this->user->hasPermission('modify', 'inventory/quality_management')) {
            $quality_control[] = array(
                'name' => $this->language->get('text_quality_management_settings'),
                'href' => $this->url->link('inventory/quality_management', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'inventory/quality_management'
            );
        }

        // إدارة حالات عدم المطابقة (Non-Conformance Management)
        if ($this->user->hasPermission('access', 'inventory/non_conformance')) {
            $quality_control[] = array(
                'name' => $this->language->get('text_non_conformance_management'),
                'href' => $this->url->link('inventory/non_conformance', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'inventory/non_conformance'
            );
        }

        if ($quality_control) {
            $inventory_warehouse[] = array(
                'name' => $this->language->get('text_quality_control_section'),
                'children' => $quality_control
            );
        }

        // -- قسم الأنظمة المتقدمة للمخزون (Advanced Inventory Systems) --
        // مطور بمستوى عالمي مثل SAP وOracle وOdoo وMicrosoft Dynamics
        $advanced_inventory_systems = array();

        // لوحة معلومات المخزون المتقدمة (Advanced Inventory Dashboard)
        if ($this->user->hasPermission('access', 'inventory/dashboard')) {
            $advanced_inventory_systems[] = array(
                'name' => 'لوحة معلومات المخزون المتقدمة',
                'href' => $this->url->link('inventory/dashboard', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'inventory/dashboard'
            );
        }

        // لوحة تحكم المخزون التفاعلية (Interactive Inventory Dashboard)
        if ($this->user->hasPermission('access', 'inventory/interactive_dashboard')) {
            $advanced_inventory_systems[] = array(
                'name' => 'لوحة تحكم المخزون التفاعلية',
                'href' => $this->url->link('inventory/interactive_dashboard', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'inventory/interactive_dashboard'
            );
        }

        // إدارة الباركود المتعدد (Multiple Barcode Management)
        if ($this->user->hasPermission('access', 'inventory/barcode_management')) {
            $advanced_inventory_systems[] = array(
                'name' => 'إدارة الباركود المتعدد',
                'href' => $this->url->link('inventory/barcode_management', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'inventory/barcode_management'
            );
        }

        // إدارة المنتجات المتطورة (Advanced Product Management)
        if ($this->user->hasPermission('access', 'inventory/product_management')) {
            $advanced_inventory_systems[] = array(
                'name' => 'إدارة المنتجات المتطورة',
                'href' => $this->url->link('inventory/product_management', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'inventory/product_management'
            );
        }

        // إدارة الوحدات والتحويلات المتطورة (Advanced Units Management)
        if ($this->user->hasPermission('access', 'inventory/unit_management')) {
            $advanced_inventory_systems[] = array(
                'name' => 'إدارة الوحدات والتحويلات المتطورة',
                'href' => $this->url->link('inventory/unit_management', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'inventory/unit_management'
            );
        }

        // إدارة المواقع والمناطق المتطورة (Advanced Locations Management)
        if ($this->user->hasPermission('access', 'inventory/location_management')) {
            $advanced_inventory_systems[] = array(
                'name' => 'إدارة المواقع والمناطق المتطورة',
                'href' => $this->url->link('inventory/location_management', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'inventory/location_management'
            );
        }

        // إدارة المخزون المتقدمة (Advanced Inventory Management)
        if ($this->user->hasPermission('access', 'inventory/inventory_management_advanced')) {
            $advanced_inventory_systems[] = array(
                'name' => 'إدارة المخزون المتقدمة',
                'href' => $this->url->link('inventory/inventory_management_advanced', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'inventory/inventory_management_advanced'
            );
        }

        if ($advanced_inventory_systems) {
            $inventory_warehouse[] = array(
                'name' => 'الأنظمة المتقدمة للمخزون',
                'children' => $advanced_inventory_systems
            );
        }

        // -- قسم إعدادات المخزون والمستودعات --
        $inv_settings = array();

        // تعريف المستودعات والفروع (Warehouses & Branches Definition)
        if ($this->user->hasPermission('modify', 'localisation/location')) {
            $inv_settings[] = array(
                'name' => $this->language->get('text_define_warehouses_branches'),
                'href' => $this->url->link('localisation/location', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'localisation/location'
            );
        }

        // تعريف الوحدات (Units of Measure - UoM)
        if ($this->user->hasPermission('modify', 'localisation/unit_class')) {
            $inv_settings[] = array(
                'name' => $this->language->get('text_units_of_measure'),
                'href' => $this->url->link('localisation/unit_class', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'localisation/unit_class'
            );
        }

        // ربط حسابات المخزون (Inventory Account Mapping) - تحديد حسابات الأستاذ المرتبطة بعمليات المخزون
        if ($this->user->hasPermission('modify', 'inventory/account_mapping')) {
            $inv_settings[] = array(
                'name' => $this->language->get('text_inventory_account_mapping'),
                'href' => $this->url->link('inventory/account_mapping', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'inventory/account_mapping'
            );
        }

        // إعدادات حساب التكلفة (Costing Method Settings) - تحديد طريقة WAC ومعالجة الفروق
        if ($this->user->hasPermission('modify', 'inventory/costing_settings')) {
            $inv_settings[] = array(
                'name' => $this->language->get('text_costing_method_settings'),
                'href' => $this->url->link('inventory/costing_settings', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'inventory/costing_settings'
            );
        }

        if ($inv_settings) {
            $inventory_warehouse[] = array(
                'name' => $this->language->get('text_inventory_warehouse_settings_section'),
                'children' => $inv_settings
            );
        }

        // إضافة القسم الرئيسي للمخزون والمستودعات
        if ($inventory_warehouse) {
            $data['menus'][] = array(
                'id'       => 'menu-inventory-warehouse',
                'icon'     => 'fa-archive',
                'name'     => $this->language->get('text_inventory_and_warehouse'),
                'href'     => '',
                'children' => $inventory_warehouse
            );
        }

// =======================================================================
        // (4) الشحن والتجهيز والتوزيع (Shipping, Fulfillment & Distribution)
        // المستخدمين: مسؤولو الشحن، فرق التجهيز في المستودعات، مناديب التوصيل، مديرو اللوجستيات، المحاسبون (للتسويات).
        // الهدف: إدارة عمليات تجهيز الطلبات للشحن، التعامل مع شركات الشحن، تتبع الشحنات، تخطيط التوزيع، وإدارة تسويات شركات الشحن.
        // workflow: دورة تجهيز الطلب (Picking -> Packing -> Dispatch), دورة الشحن والتتبع, دورة تسويات شركات الشحن (خاصة COD).
        // القيود المحاسبية:
        // - مصاريف الشحن (إذا تحملناها): من ح/مصروفات شحن وتوصيل XXX إلى ح/شركات الشحن (كمستحق) أو النقدية XXX.
        // - تسوية الدفع عند الاستلام (COD): من ح/البنك أو النقدية (المحصل من شركة الشحن) XXX ومن ح/مصروفات شحن وتوصيل (عمولة COD ورسوم الشحن) XXX إلى ح/شركات الشحن (إغلاق حساب التحصيل) XXX.
        // -----------------------------------------------------------------------
        $shipping_fulfillment = array();

        // -- لوحة تحكم الشحن والتوزيع التفاعلية (Interactive Shipping Dashboard) --
        if ($this->user->hasPermission('access', 'shipping/shipping_dashboard')) {
            $shipping_fulfillment[] = array(
                'name' => 'لوحة تحكم الشحن والتوزيع التفاعلية',
                'href' => $this->url->link('shipping/shipping_dashboard', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'shipping/shipping_dashboard'
            );
        }

        // -- قسم عمليات التجهيز والشحن المتطورة --
        $fulfillment_ops = array();

        // نظام تجهيز الطلبات المتقدم (Advanced Order Fulfillment) - مطور بمستوى عالمي
        if ($this->user->hasPermission('access', 'shipping/order_fulfillment')) {
            $fulfillment_ops[] = array(
                'name' => 'نظام تجهيز الطلبات المتقدم',
                'href' => $this->url->link('shipping/order_fulfillment', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'shipping/order_fulfillment'
            );
        }

        // لوحة تحكم تجهيز الطلبات (Fulfillment Dashboard)
        if ($this->user->hasPermission('access', 'shipping/order_fulfillment')) {
            $fulfillment_ops[] = array(
                'name' => 'لوحة تحكم تجهيز الطلبات',
                'href' => $this->url->link('shipping/order_fulfillment/dashboard', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'shipping/order_fulfillment'
            );
        }

        // تجهيز الطلبات (Order Picking & Packing) - شاشة لفرق المستودع لتجهيز الطلبات
        if ($this->user->hasPermission('access', 'shipping/prepare_orders')) {
            $fulfillment_ops[] = array(
                'name' => $this->language->get('text_order_picking_packing'),
                'href' => $this->url->link('shipping/prepare_orders', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'shipping/prepare_orders'
            );
        }

        // إدارة أوامر الشحن (Shipping Orders / Dispatch) - إنشاء وإدارة بوالص الشحن والتسليم لشركة الشحن
        if ($this->user->hasPermission('access', 'shipping/shipment_orders')) {
            $fulfillment_ops[] = array(
                'name' => $this->language->get('text_manage_shipping_orders'),
                'href' => $this->url->link('shipping/shipment_orders', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'shipping/shipment_orders'
            );
        }

        // نظام التكامل مع شركات الشحن (Shipping Integration) - أرامكس وبوسطة
        if ($this->user->hasPermission('access', 'shipping/shipping_integration')) {
            $fulfillment_ops[] = array(
                'name' => 'التكامل مع شركات الشحن (أرامكس وبوسطة)',
                'href' => $this->url->link('shipping/shipping_integration', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'shipping/shipping_integration'
            );
        }

        // نظام تتبع الشحنات المتقدم (Advanced Shipment Tracking)
        if ($this->user->hasPermission('access', 'shipping/shipment_tracking')) {
            $fulfillment_ops[] = array(
                'name' => 'نظام تتبع الشحنات المتقدم',
                'href' => $this->url->link('shipping/shipment_tracking', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'shipping/shipment_tracking'
            );
        }

        // إدارة المناديب الداخليين (Internal Couriers Management)
        if ($this->user->hasPermission('access', 'shipping/internal_courier')) {
            $fulfillment_ops[] = array(
                'name' => 'إدارة المناديب الداخليين',
                'href' => $this->url->link('shipping/internal_courier', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'shipping/internal_courier'
            );
        }

        // تتبع الشحنات (Shipment Tracking) - متابعة حالة الشحنات المرسلة
        if ($this->user->hasPermission('access', 'shipping/tracking')) {
            $fulfillment_ops[] = array(
                'name' => $this->language->get('text_shipment_tracking_external'),
                'href' => $this->url->link('shipping/tracking', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'shipping/tracking'
            );
        }

        // تخطيط التحميل والتوزيع (Load Planning & Routing) - للمناديب الداخليين أو تخطيط التسليم للمناطق
        if ($this->user->hasPermission('access', 'shipping/load_planning')) {
            $fulfillment_ops[] = array(
                'name' => $this->language->get('text_load_planning_routing'),
                'href' => $this->url->link('shipping/load_planning', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'shipping/load_planning'
            );
        }

        if ($fulfillment_ops) {
            $shipping_fulfillment[] = array(
                'name' => $this->language->get('text_fulfillment_shipping_ops_section'),
                'children' => $fulfillment_ops
            );
        }

        // -- قسم إدارة شركات الشحن والمناديب --
        $carriers_mgmt = array();

        // إدارة شركات الشحن (Shipping Carriers) - تعريف الشركات وبياناتها
        if ($this->user->hasPermission('modify', 'shipping/carrier')) {
            $carriers_mgmt[] = array(
                'name' => $this->language->get('text_shipping_carriers_management'),
                'href' => $this->url->link('shipping/carrier', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'shipping/carrier'
            );
        }

        // مناطق تغطية الشحن (Shipping Coverage Zones) - تحديد المناطق التي تغطيها كل شركة
        if ($this->user->hasPermission('modify', 'shipping/coverage')) {
            $carriers_mgmt[] = array(
                'name' => $this->language->get('text_shipping_coverage_zones'),
                'href' => $this->url->link('shipping/coverage', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'shipping/coverage'
            );
        }

        // أسعار الشحن (Shipping Rates) - تعريف أسعار الشحن لكل شركة ومنطقة ووزن
        if ($this->user->hasPermission('modify', 'shipping/rate')) {
            $carriers_mgmt[] = array(
                'name' => $this->language->get('text_shipping_rates_setup'),
                'href' => $this->url->link('shipping/rate', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'shipping/rate'
            );
        }

        // إدارة المناديب الداخليين (Internal Couriers)
        if ($this->user->hasPermission('modify', 'shipping/internal_courier')) {
            $carriers_mgmt[] = array(
                'name' => $this->language->get('text_internal_couriers_management'),
                'href' => $this->url->link('shipping/internal_courier', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'shipping/internal_courier'
            );
        }

        if ($carriers_mgmt) {
            $shipping_fulfillment[] = array(
                'name' => $this->language->get('text_carriers_couriers_mgmt_section'),
                'children' => $carriers_mgmt
            );
        }

        // -- قسم تسويات شركات الشحن المتقدم --
        // مهم جداً لمتابعة مستحقات الدفع عند الاستلام (COD) والرسوم مع التكامل المحاسبي.
        $shipping_settlements = array();

        // نظام تسويات شركات الشحن المتقدم (Advanced Shipping Settlements)
        if ($this->user->hasPermission('access', 'shipping/shipping_settlement')) {
            $shipping_settlements[] = array(
                'name' => 'نظام تسويات شركات الشحن المتقدم',
                'href' => $this->url->link('shipping/shipping_settlement', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'shipping/shipping_settlement'
            );
        }

        // لوحة تحكم التسويات (Settlements Dashboard)
        if ($this->user->hasPermission('access', 'shipping/shipping_settlement')) {
            $shipping_settlements[] = array(
                'name' => 'لوحة تحكم التسويات',
                'href' => $this->url->link('shipping/shipping_settlement/dashboard', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'shipping/shipping_settlement'
            );
        }

        // تسويات الدفع عند الاستلام (COD Settlements)
        if ($this->user->hasPermission('access', 'shipping/settlement')) {
            $shipping_settlements[] = array(
                'name' => $this->language->get('text_carrier_settlements_cod'),
                'href' => $this->url->link('shipping/settlement', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'shipping/settlement'
            );
        }

        if ($shipping_settlements) {
            $shipping_fulfillment[] = array(
                'name' => 'تسويات شركات الشحن المتقدمة',
                'children' => $shipping_settlements
            );
        }

        // إضافة القسم الرئيسي للشحن والتجهيز
        if ($shipping_fulfillment) {
            $data['menus'][] = array(
                'id'       => 'menu-shipping-fulfillment',
                'icon'     => 'fa-shipping-fast',
                'name'     => $this->language->get('text_shipping_fulfillment'),
                'href'     => '',
                'children' => $shipping_fulfillment
            );
        }

        // =======================================================================
        // (5) المحاسبة والمالية (Accounting & Finance)
        // المستخدمين: المحاسبون، المدير المالي، أمناء الصناديق، مراجعو الحسابات، الإدارة العليا.
        // الهدف: إدارة جميع العمليات المحاسبية والمالية، بما في ذلك دفتر الأستاذ، الذمم، النقدية والبنوك، الأصول الثابتة، الموازنات، والتقارير المالية.
        // workflow: دورة القيد المحاسبي، دورة التحصيل والدفع، دورة التسويات البنكية، دورة إغلاق الفترة، دورة الموازنة، دورة الأصول الثابتة.
        // ملاحظات WAC: هذا القسم يعكس النتائج المالية لعمليات المخزون التي تتبع WAC ويحتوي على أدوات الربط والتحليل.
        // -----------------------------------------------------------------------
        $finance_accounting = array();

        // -- قسم المحاسبة الأساسية (Core Accounting) --
        $core_accounting = array();

        // دليل الحسابات (Chart of Accounts - CoA)
        if ($this->user->hasPermission('access','accounts/chart_account')) {
            $core_accounting[] = array(
                'name' => $this->language->get('text_chart_of_accounts'),
                'href' => $this->url->link('accounts/chart_account','user_token='.$this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'accounts/chart_account'
            );
        }

        // قيود اليومية (Journal Entries / Vouchers) - تسجيل ومراجعة القيود اليدوية والآلية
        if ($this->user->hasPermission('access','accounts/journal')) {
            $core_accounting[] = array(
                'name' => $this->language->get('text_journal_entries'),
                'href' => $this->url->link('accounts/journal','user_token='.$this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'accounts/journal'
            );
        }

        // مراجعة واعتماد القيود (Journal Review & Approval) - خطوة اختيارية حسب الإعدادات
        if ($this->user->hasPermission('access','accounts/journal_review')) {
            $core_accounting[] = array(
                'name' => $this->language->get('text_journal_review_approval'),
                'href' => $this->url->link('accounts/journal_review','user_token='.$this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'accounts/journal_review'
            );
        }

        // كشوف الحسابات (Account Statements / Ledger)
        if ($this->user->hasPermission('access','accounts/statement_account')) {
            $core_accounting[] = array(
                'name' => $this->language->get('text_account_statements'),
                'href' => $this->url->link('accounts/statement_account','user_token='.$this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'accounts/statement_account'
            );
        }

        // ربط حسابات المخزون (Inventory Account Mapping) - مكرر من قسم المخزون للتأكيد على أهميته المحاسبية
        if ($this->user->hasPermission('modify', 'inventory/account_mapping')) {
            $core_accounting[] = array(
                'name' => $this->language->get('text_inventory_account_mapping_link'),
                'href' => $this->url->link('inventory/account_mapping', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'inventory/account_mapping'
            );
        }

        // إغلاق الفترة المحاسبية (Period Closing)
        // workflow: مراجعة التسويات > إقفال الحسابات المؤقتة > ترحيل الأرباح/الخسائر > إصدار تقارير الفترة الختامية.
        if ($this->user->hasPermission('modify', 'accounts/period_closing')) {
            $core_accounting[] = array(
                'name' => $this->language->get('text_accounting_period_closing'),
                'href' => $this->url->link('accounts/period_closing', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'accounts/period_closing'
            );
        }

        if ($core_accounting) {
            $finance_accounting[] = array(
                'name' => $this->language->get('text_core_accounting_section'),
                'children' => $core_accounting
            );
        }

        // -- قسم الذمم (Receivables & Payables) --
        $receivables_payables = array();

        // حسابات العملاء (Customer Accounts / AR Ledger) - أعمار الديون، كشوف الحسابات
        if ($this->user->hasPermission('access', 'customer/account_ledger')) {
            $receivables_payables[] = array(
                'name' => $this->language->get('text_customer_accounts_ar_ledger'),
                'href' => $this->url->link('customer/account_ledger', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'customer/account_ledger'
            );
        }

        // حسابات الموردين (Supplier Accounts / AP Ledger) - أعمار الديون، كشوف الحسابات - مكرر من قسم الموردين
        if ($this->user->hasPermission('access', 'supplier/account')) {
            $receivables_payables[] = array(
                'name' => $this->language->get('text_supplier_accounts_ap_ledger_link'),
                'href' => $this->url->link('supplier/account', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'supplier/account'
            );
        }

        // متابعة تحصيل الديون (Debt Collection Management)
        // workflow: تحديد الديون المستحقة > إرسال تذكيرات > تسجيل محاولات الاتصال > التفاوض > اتخاذ إجراءات.
        if ($this->user->hasPermission('access','finance/debt_collection')) {
            $receivables_payables[] = array(
                'name' => $this->language->get('text_debt_collection_management'),
                'href' => $this->url->link('finance/debt_collection','user_token='.$this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'finance/debt_collection'
            );
        }

        // سندات القبض (Receipt Vouchers) - تسجيل المقبوضات النقدية أو الشيكات من العملاء أو مصادر أخرى
        // مطور بمستوى عالمي: تخصيص على الفواتير، قيود تلقائية، تحديث أرصدة، طباعة احترافية
        if ($this->user->hasPermission('access', 'finance/receipt_voucher')) {
            $receivables_payables[] = array(
                'name' => $this->language->get('text_receipt_vouchers'),
                'href' => $this->url->link('finance/receipt_voucher', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'finance/receipt_voucher'
            );
        }

        // سندات الصرف (Payment Vouchers) - تسجيل المدفوعات النقدية أو الشيكات للموردين أو المصروفات
        // مطور بمستوى عالمي: أنواع مستفيدين متعددة، قيود تلقائية، تحديث أرصدة، طباعة احترافية
        if ($this->user->hasPermission('access', 'finance/payment_voucher')) {
            $receivables_payables[] = array(
                'name' => $this->language->get('text_payment_vouchers'),
                'href' => $this->url->link('finance/payment_voucher', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'finance/payment_voucher'
            );
        }

        // موافقات الدفع (Payment Approvals) - دورة عمل لاعتماد المدفوعات قبل تنفيذها
        if ($this->user->hasPermission('access','payment/approval')) {
            $receivables_payables[] = array(
                'name' => $this->language->get('text_payment_approvals'),
                'href' => $this->url->link('payment/approval','user_token='.$this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'payment/approval'
            );
        }

        // موافقات فواتير الموردين (Invoice Approvals) - دورة عمل لاعتماد فواتير الموردين قبل تسجيلها كاستحقاق
        if ($this->user->hasPermission('access','invoice/approval')) {
            $receivables_payables[] = array(
                'name' => $this->language->get('text_supplier_invoice_approvals'),
                'href' => $this->url->link('invoice/approval','user_token='.$this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'invoice/approval'
            );
        }

        if ($receivables_payables) {
            $finance_accounting[] = array(
                'name' => $this->language->get('text_receivables_payables_section'),
                'children' => $receivables_payables
            );
        }

        // -- قسم النقدية والبنوك (Cash & Bank Management) --
        $cash_bank = array();

        // إدارة الصناديق (Cash / Treasury Management) - متابعة أرصدة وحركات الصناديق النقدية
        if ($this->user->hasPermission('access','finance/cash')) {
            $cash_bank[] = array(
                'name' => $this->language->get('text_cash_treasury_management'),
                'href' => $this->url->link('finance/cash','user_token='.$this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'finance/cash'
            );
        }

        // إدارة الحسابات البنكية (Bank Accounts Management) - تعريف ومتابعة الحسابات البنكية
        if ($this->user->hasPermission('access','finance/bank')) {
            $cash_bank[] = array(
                'name' => $this->language->get('text_bank_accounts_management'),
                'href' => $this->url->link('finance/bank','user_token='.$this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'finance/bank'
            );
        }

        // التسوية البنكية (Bank Reconciliation) - مطابقة كشف حساب البنك مع سجلات النظام
        // workflow: استيراد كشف البنك > مطابقة آلية ويدوية > تسجيل الفروقات (مصاريف بنكية، فوائد...) > إنشاء قيد تسوية.
        // AI Integration: مطابقة ذكية للمعاملات، تعلم أنماط المعاملات المتكررة، اقتراح تسويات للفروقات.
        $bank_rec = array();

        // استيراد كشف حساب بنكي
        if ($this->user->hasPermission('modify', 'bank/statement_import')) {
            $bank_rec[] = array(
                'name' => $this->language->get('text_import_bank_statement'),
                'href' => $this->url->link('bank/statement_import', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'bank/statement_import'
            );
        }

        // شاشة التسوية البنكية (يدوي/آلي)
        // مطور بمستوى عالمي: تسوية ذكية بالذكاء الاصطناعي، تطابق تلقائي، استيراد متعدد الصيغ
        if ($this->user->hasPermission('access', 'finance/bank_reconciliation')) {
            $bank_rec[] = array(
                'name' => $this->language->get('text_bank_reconciliation_screen'),
                'href' => $this->url->link('finance/bank_reconciliation', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'finance/bank_reconciliation'
            );
        }

        // التسوية البنكية الذكية (Smart Reconcile) - إذا كانت ميزة منفصلة
        if ($this->user->hasPermission('access', 'bank/smart_reconcile')) {
            $bank_rec[] = array(
                'name' => $this->language->get('text_smart_bank_reconciliation'),
                'href' => $this->url->link('bank/smart_reconcile', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'bank/smart_reconcile'
            );
        }

        if ($bank_rec) {
            $cash_bank[] = array(
                'name' => $this->language->get('text_bank_reconciliation_section'),
                'children' => $bank_rec
            );
        }

        // إدارة الشيكات (Checks Management) - تتبع الشيكات الواردة والصادرة وحالاتها
        // قيود الشيكات: (وارد) من ح/شيكات تحت التحصيل إلى ح/العملاء. (تحصيل) من ح/البنك إلى ح/شيكات تحت التحصيل. (صادر) من ح/الموردين إلى ح/شيكات مؤجلة الدفع. (سداد) من ح/شيكات مؤجلة الدفع إلى ح/البنك.
        if ($this->user->hasPermission('access','finance/checks')) {
            $cash_bank[] = array(
                'name' => $this->language->get('text_checks_management'),
                'href' => $this->url->link('finance/checks','user_token='.$this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'finance/checks'
            );
        }

        // إدارة المحافظ الإلكترونية (E-Wallets Management)
        if ($this->user->hasPermission('access','finance/ewallet')) {
            $cash_bank[] = array(
                'name' => $this->language->get('text_ewallet_management'),
                'href' => $this->url->link('finance/ewallet','user_token='.$this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'finance/ewallet'
            );
        }

        // إدارة بوابات الدفع الإلكتروني (Payment Gateways)
        // workflow: تعريف البوابة > ربطها بالمتجر/الفواتير > متابعة المعاملات > تسوية المدفوعات المستلمة من البوابة.
        $payment_gateways = array();

        // تعريف بوابات الدفع
        if ($this->user->hasPermission('modify', 'payment/gateway')) {
            $payment_gateways[] = array(
                'name' => $this->language->get('text_payment_gateways_setup'),
                'href' => $this->url->link('payment/gateway', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'payment/gateway'
            );
        }

        // معاملات بوابات الدفع
        if ($this->user->hasPermission('access', 'payment/transaction')) {
            $payment_gateways[] = array(
                'name' => $this->language->get('text_gateway_transactions'),
                'href' => $this->url->link('payment/transaction', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'payment/transaction'
            );
        }

        // تسويات بوابات الدفع
        // القيد: من ح/البنك XXX ومن ح/مصروف عمولات بوابة الدفع XXX إلى ح/حساب وسيط بوابة الدفع XXX.
        if ($this->user->hasPermission('access', 'payment/settlement')) {
            $payment_gateways[] = array(
                'name' => $this->language->get('text_gateway_settlements'),
                'href' => $this->url->link('payment/settlement', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'payment/settlement'
            );
        }

        if ($payment_gateways) {
            $cash_bank[] = array(
                'name' => $this->language->get('text_payment_gateways_section'),
                'children' => $payment_gateways
            );
        }

        if ($cash_bank) {
            $finance_accounting[] = array(
                'name' => $this->language->get('text_cash_bank_gateways_section'),
                'children' => $cash_bank
            );
        }

        // -- قسم الأصول الثابتة (Fixed Assets) --
        $fixed_assets = array();

        // سجل الأصول الثابتة (Fixed Assets Registry)
        // القيد (شراء): من ح/الأصول الثابتة (حسب النوع) XXX إلى ح/الموردين أو البنك XXX.
        if ($this->user->hasPermission('access','accounts/fixed_assets')) {
            $fixed_assets[] = array(
                'name' => $this->language->get('text_fixed_assets_registry'),
                'href' => $this->url->link('accounts/fixed_assets','user_token='.$this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'accounts/fixed_assets'
            );
        }

        // حساب وتسجيل الإهلاك (Depreciation Calculation & Posting)
        // القيد (دوري): من ح/مصروف إهلاك الأصول XXX إلى ح/مجمع إهلاك الأصول XXX.
        if ($this->user->hasPermission('modify','accounts/depreciation')) {
            $fixed_assets[] = array(
                'name' => $this->language->get('text_depreciation_calculation_posting'),
                'href' => $this->url->link('accounts/depreciation','user_token='.$this->session->data['user_token'],true),
                'permission' => 'modify',
                'route' => 'accounts/depreciation'
            );
        }

        // التخلص من الأصول (Asset Disposal) - بيع أو استبعاد
        // القيد (بيع بربح): من ح/البنك/النقدية + ح/مجمع الإهلاك + إلى ح/الأصول الثابتة + ح/أرباح رأسمالية.
        // القيد (بيع بخسارة): من ح/البنك/النقدية + ح/مجمع الإهلاك + ح/خسائر رأسمالية إلى ح/الأصول الثابتة.
        // القيد (استبعاد): من ح/مجمع الإهلاك + ح/خسائر رأسمالية (إن وجدت) إلى ح/الأصول الثابتة.
        if ($this->user->hasPermission('modify','accounts/asset_disposal')) {
            $fixed_assets[] = array(
                'name' => $this->language->get('text_asset_disposal'),
                'href' => $this->url->link('accounts/asset_disposal','user_token='.$this->session->data['user_token'],true),
                'permission' => 'modify',
                'route' => 'accounts/asset_disposal'
            );
        }

        // تقارير الأصول الثابتة (Fixed Assets Reports)
        if ($this->user->hasPermission('access','report/fixed_assets')) {
            $fixed_assets[] = array(
                'name' => $this->language->get('text_fixed_assets_reports'),
                'href' => $this->url->link('report/fixed_assets','user_token='.$this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'report/fixed_assets'
            );
        }

        if ($fixed_assets) {
            $finance_accounting[] = array(
                'name' => $this->language->get('text_fixed_assets_section'),
                'children' => $fixed_assets
            );
        }

        // -- قسم الموازنات والتخطيط المالي (Budgeting & Financial Planning) --
        $budgeting = array();

        // إعداد الموازنات (Budget Setup / Management)
        if ($this->user->hasPermission('modify','accounts/budget')) {
            $budgeting[] = array(
                'name' => $this->language->get('text_budget_setup_management'),
                'href' => $this->url->link('accounts/budget','user_token='.$this->session->data['user_token'],true),
                'permission' => 'modify',
                'route' => 'accounts/budget'
            );
        }

        // بنود الموازنة (Budget Lines / Items) - تفاصيل الموازنة حسب الحسابات أو المراكز
        if ($this->user->hasPermission('access','accounts/budget_line')) {
            $budgeting[] = array(
                'name' => $this->language->get('text_budget_lines'),
                'href' => $this->url->link('accounts/budget_line','user_token='.$this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'accounts/budget_line'
            );
        }

        // متابعة الموازنة (Budget Monitoring / Variance Report) - مقارنة الفعلي بالموازنة
        if ($this->user->hasPermission('access','accounts/budget_monitoring')) {
            $budgeting[] = array(
                'name' => $this->language->get('text_budget_monitoring_variance'),
                'href' => $this->url->link('accounts/budget_monitoring','user_token='.$this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'accounts/budget_monitoring'
            );
        }

        // التنبؤ بالتدفقات النقدية (Cash Flow Forecasting)
        // AI Integration: نماذج تنبؤ متقدمة، محاكاة سيناريوهات مختلفة (What-if analysis).
        if ($this->user->hasPermission('access', 'finance/cash_flow_forecast')) {
            $budgeting[] = array(
                'name' => $this->language->get('text_cash_flow_forecasting'),
                'href' => $this->url->link('finance/cash_flow_forecast', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'finance/cash_flow_forecast'
            );
        }

        // إدارة السيولة (Liquidity Management) - مراقبة وتخطيط الأرصدة النقدية
        if ($this->user->hasPermission('access','finance/liquidity_management')) {
            $budgeting[] = array(
                'name' => $this->language->get('text_liquidity_management'),
                'href' => $this->url->link('finance/liquidity_management','user_token='.$this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'finance/liquidity_management'
            );
        }

        // التنبؤات المالية الأخرى (Other Financial Forecasts) - مثل توقعات الإيرادات والمصروفات
        if ($this->user->hasPermission('access','accounts/financial_forecast')) {
            $budgeting[] = array(
                'name' => $this->language->get('text_other_financial_forecasts'),
                'href' => $this->url->link('accounts/financial_forecast','user_token='.$this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'accounts/financial_forecast'
            );
        }

        if ($budgeting) {
            $finance_accounting[] = array(
                'name' => $this->language->get('text_budgeting_planning_section'),
                'children' => $budgeting
            );
        }

        // -- قسم التقارير المالية والضريبية --
        $fin_tax_reports = array();

        // ميزان المراجعة (Trial Balance)
        if ($this->user->hasPermission('access','accounts/trial_balance')) {
            $fin_tax_reports[] = array(
                'name' => $this->language->get('text_trial_balance'),
                'href' => $this->url->link('accounts/trial_balance','user_token='.$this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'accounts/trial_balance'
            );
        }

        // قائمة الدخل (Income Statement / P&L)
        // مطور بمستوى عالمي: مقارنات، تحليلات، نسب مالية، تصدير متعدد، drill-down، تحليل ربحية
        if ($this->user->hasPermission('access','accounts/income_statement')) {
            $fin_tax_reports[] = array(
                'name' => $this->language->get('text_income_statement'),
                'href' => $this->url->link('accounts/income_statement','user_token='.$this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'accounts/income_statement'
            );
        }

        // قائمة المركز المالي (Balance Sheet)
        // مطور بمستوى عالمي: مقارنات، نسب مالية، تحليل سيولة، تحليل ربحية، تصدير متعدد
        if ($this->user->hasPermission('access','accounts/balance_sheet')) {
            $fin_tax_reports[] = array(
                'name' => $this->language->get('text_balance_sheet'),
                'href' => $this->url->link('accounts/balance_sheet','user_token='.$this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'accounts/balance_sheet'
            );
        }

        // قائمة التدفقات النقدية (Cash Flow Statement)
        // مطور بمستوى عالمي: طريقة مباشرة وغير مباشرة، توقعات، تحليل سيولة، مقارنات
        if ($this->user->hasPermission('access','accounts/cash_flow')) {
            $fin_tax_reports[] = array(
                'name' => $this->language->get('text_cash_flow_statement'),
                'href' => $this->url->link('accounts/cash_flow','user_token='.$this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'accounts/cash_flow'
            );
        }

        // قائمة التغير في حقوق الملكية (Statement of Changes in Equity)
        if ($this->user->hasPermission('access','accounts/changes_in_equity')) {
            $fin_tax_reports[] = array(
                'name' => $this->language->get('text_changes_in_equity_statement'),
                'href' => $this->url->link('accounts/changes_in_equity','user_token='.$this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'accounts/changes_in_equity'
            );
        }

        // تحليل الربحية (Profitability Analysis) - حسب المنتج، العميل، الفرع، إلخ.
        if ($this->user->hasPermission('access','accounts/profitability_analysis')) {
            $fin_tax_reports[] = array(
                'name' => $this->language->get('text_profitability_analysis'),
                'href' => $this->url->link('accounts/profitability_analysis','user_token='.$this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'accounts/profitability_analysis'
            );
        }

        // تقرير ضريبة القيمة المضافة (VAT Report)
        if ($this->user->hasPermission('access','accounts/vat_report')) {
            $fin_tax_reports[] = array(
                'name' => $this->language->get('text_vat_report'),
                'href' => $this->url->link('accounts/vat_report','user_token='.$this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'accounts/vat_report'
            );
        }

        // الإقرارات الضريبية الأخرى (Other Tax Returns) - مثل إقرار ضريبة الدخل، الخصم والإضافة، إلخ.
        if ($this->user->hasPermission('access','accounts/tax_return')) {
            $fin_tax_reports[] = array(
                'name' => $this->language->get('text_other_tax_returns'),
                'href' => $this->url->link('accounts/tax_return','user_token='.$this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'accounts/tax_return'
            );
        }

        if ($fin_tax_reports) {
            $finance_accounting[] = array(
                'name' => $this->language->get('text_financial_tax_reports_section'),
                'children' => $fin_tax_reports
            );
        }

        // -- قسم الأنظمة المتقدمة (Advanced Systems) --
        // مطور بمستوى عالمي مثل SAP وOracle وOdoo وMicrosoft Dynamics
        $advanced_systems = array();

        // نظام الحماية المتقدم للقيود المحاسبية
        if ($this->user->hasPermission('access', 'accounts/journal_security_advanced')) {
            $advanced_systems[] = array(
                'name' => 'نظام الحماية المتقدم للقيود',
                'href' => $this->url->link('accounts/journal_security_advanced', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'accounts/journal_security_advanced'
            );
        }

        // نظام الصلاحيات المتقدم
        if ($this->user->hasPermission('access', 'user/user_permission_advanced')) {
            $advanced_systems[] = array(
                'name' => 'نظام الصلاحيات المتقدم',
                'href' => $this->url->link('user/user_permission_advanced', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'user/user_permission_advanced'
            );
        }

        // نظام الترابط المتقدم بين الوحدات
        if ($this->user->hasPermission('access', 'system/integration_advanced')) {
            $advanced_systems[] = array(
                'name' => 'نظام الترابط المتقدم',
                'href' => $this->url->link('system/integration_advanced', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'system/integration_advanced'
            );
        }

        if ($advanced_systems) {
            $finance_accounting[] = array(
                'name' => 'الأنظمة المتقدمة (Enterprise Level)',
                'children' => $advanced_systems
            );
        }

        // إضافة القسم الرئيسي للمحاسبة والمالية
        if ($finance_accounting) {
            $data['menus'][] = array(
                'id'       => 'menu-finance-accounting',
                'icon'     => 'fa-money',
                'name'     => $this->language->get('text_accounting_and_finance'),
                'href'     => '',
                'children' => $finance_accounting
            );
        }

        // =======================================================================
        // (6) الفوترة الإلكترونية (مصر) - (E-Invoicing ETA)
        // المستخدمين: المحاسبون، المدير المالي، مسؤولو الامتثال الضريبي.
        // الهدف: إنشاء وإرسال الفواتير والإشعارات الإلكترونية لهيئة الضرائب المصرية (ETA) ومتابعة حالتها والامتثال للمتطلبات.
        // workflow: إنشاء الفاتورة/الإشعار في النظام > إرسالها إلى ETA > متابعة الحالة (مقبول، مرفوض) > التعامل مع الإشعارات من ETA.
        // القيود المحاسبية: نفس قيود الفواتير العادية، لكن هذه الوحدة تضمن التنسيق الصحيح والإرسال للجهة الضريبية.
        // -----------------------------------------------------------------------
        $eta_module = array();

        // لوحة مراقبة الامتثال (ETA Compliance Dashboard)
        if ($this->user->hasPermission('access', 'eta/compliance_dashboard')) {
            $eta_module[] = array(
                'name' => $this->language->get('text_eta_compliance_dashboard'),
                'href' => $this->url->link('eta/compliance_dashboard', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'eta/compliance_dashboard',
                'children' => array()
            );
        }

        // -- قسم المستندات الإلكترونية (E-Documents) --
        $eta_documents = array();

        // الفواتير الإلكترونية (E-Invoices)
        if ($this->user->hasPermission('access', 'eta/invoices')) {
            $eta_documents[] = array(
                'name' => $this->language->get('text_eta_einvoices'),
                'href' => $this->url->link('eta/invoices', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'eta/invoices'
            );
        }

        // الإشعارات الإلكترونية (Credit/Debit Notes)
        if ($this->user->hasPermission('access', 'eta/notices')) {
            $eta_documents[] = array(
                'name' => $this->language->get('text_eta_credit_debit_notes'),
                'href' => $this->url->link('eta/notices', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'eta/notices'
            );
        }

        // الإيصالات الإلكترونية (E-Receipts) - إذا كان النظام يدعمها
        if ($this->user->hasPermission('access', 'eta/receipts')) {
            $eta_documents[] = array(
                'name' => $this->language->get('text_eta_ereceipts'),
                'href' => $this->url->link('eta/receipts', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'eta/receipts'
            );
        }

        if ($eta_documents) {
            $eta_module[] = array(
                'name' => $this->language->get('text_eta_edocuments_section'),
                'children' => $eta_documents
            );
        }

        // -- قسم إعدادات الفوترة الإلكترونية --
        $eta_settings = array();

        // أكواد الأصناف (GS1 / EGS Codes) - إدارة وربط أكواد المنتجات المستخدمة في الفوترة
        if ($this->user->hasPermission('modify', 'eta/codes')) {
            $eta_settings[] = array(
                'name' => $this->language->get('text_eta_item_codes_gs1_egs'),
                'href' => $this->url->link('eta/codes', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'eta/codes'
            );
        }

        // ربط الأصناف بالأكواد (Product Code Mapping)
        if ($this->user->hasPermission('modify', 'eta/product_mapping')) {
            $eta_settings[] = array(
                'name' => $this->language->get('text_eta_product_code_mapping'),
                'href' => $this->url->link('eta/product_mapping', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'eta/product_mapping'
            );
        }

        // إعدادات الربط مع ETA (Connection Settings) - مثل Client ID, Secret
        if ($this->user->hasPermission('modify', 'eta/connection_settings')) {
            $eta_settings[] = array(
                'name' => $this->language->get('text_eta_connection_settings'),
                'href' => $this->url->link('eta/connection_settings', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'eta/connection_settings'
            );
        }

        // سجل الإشعارات من ETA (Notifications Log) - لعرض الرسائل الواردة من المنظومة
        if ($this->user->hasPermission('access', 'eta/notifications')) {
            $eta_settings[] = array(
                'name' => $this->language->get('text_eta_notifications_log'),
                'href' => $this->url->link('eta/notifications', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'eta/notifications'
            );
        }

        if ($eta_settings) {
            $eta_module[] = array(
                'name' => $this->language->get('text_eta_settings_section'),
                'children' => $eta_settings
            );
        }

        // -- قسم التقارير والامتثال الضريبي --
        $eta_compliance_reports = array();

        // تقارير الامتثال (ETA Compliance Reports) - ملخصات للحالة الضريبية
        if ($this->user->hasPermission('access', 'eta/tax_compliance_reports')) {
            $eta_compliance_reports[] = array(
                'name' => $this->language->get('text_eta_compliance_reports'),
                'href' => $this->url->link('eta/tax_compliance_reports', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'eta/tax_compliance_reports'
            );
        }

        // أرشيف المستندات المرسلة (Sent Documents Archive)
        if ($this->user->hasPermission('access', 'eta/document_archive')) {
            $eta_compliance_reports[] = array(
                'name' => $this->language->get('text_eta_document_archive'),
                'href' => $this->url->link('eta/document_archive', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'eta/document_archive'
            );
        }

        // نماذج الإقرارات (إذا كان النظام يساعد في تجهيزها)
        if ($this->user->hasPermission('access', 'eta/tax_forms')) {
            $eta_compliance_reports[] = array(
                'name' => $this->language->get('text_eta_tax_forms_preparation'),
                'href' => $this->url->link('eta/tax_forms', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'eta/tax_forms'
            );
        }

        if ($eta_compliance_reports) {
            $eta_module[] = array(
                'name' => $this->language->get('text_eta_reports_compliance_section'),
                'children' => $eta_compliance_reports
            );
        }

        // إضافة القسم الرئيسي للفوترة الإلكترونية
        if ($eta_module) {
            $data['menus'][] = array(
                'id'       => 'menu-eta-einvoicing',
                'icon'     => 'fa-file-text-o',
                'name'     => $this->language->get('text_eta_einvoicing'),
                'href'     => '',
                'children' => $eta_module
            );
        }

        // =======================================================================
        // (7) الموقع والمتجر الإلكتروني (Website & E-commerce)
        // المستخدمين: مدير المتجر، مدير التسويق، مدير المنتجات، مدير المحتوى، مصممو الواجهات.
        // الهدف: إدارة كل ما يتعلق بواجهة المتجر الإلكتروني: المنتجات (البيانات الوصفية والتسويقية)، التصنيفات، المحتوى، التصميم، العروض، التسويق الرقمي.
        // ملاحظة: إدارة الكميات والتكاليف تتم في قسم "المخزون والمستودعات". إدارة الطلبات تتم في قسم "المبيعات و CRM".
        // workflow: دورة إدارة المنتج للعرض (وصف، صور، سعر)، دورة إدارة المحتوى (صفحات، مدونة)، دورة إدارة العروض والتسويق، دورة تحسين محركات البحث (SEO).
        // القيود المحاسبية: لا يوجد قيود مباشرة من هذا القسم، التأثير المحاسبي يأتي من الطلبات والمبيعات الفعلية التي تتم عبر المتجر وتدار في قسم المبيعات.
        // -----------------------------------------------------------------------
        $website_ecommerce = array();

        // -- قسم كتالوج المتجر (Store Catalog) --
        // لإدارة كيفية ظهور المنتجات والتصنيفات على الواجهة.
        $store_catalog = array();

        // تصنيفات المتجر (Store Categories)
        if ($this->user->hasPermission('access', 'catalog/category')) {
            $store_catalog[] = array(
                'name' => $this->language->get('text_store_categories'),
                'href' => $this->url->link('catalog/category', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'catalog/category'
            );
        }

        // منتجات المتجر (Store Products) - إدارة البيانات الوصفية، الصور، الأسعار، العروض للمتجر.
        // **هام:** هذا القسم لا يدير الكميات أو التكاليف.
        if ($this->user->hasPermission('access', 'catalog/product')) {
            $store_catalog[] = array(
                'name' => $this->language->get('text_store_products_management'),
                'href' => $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'catalog/product'
            );
        }

        // خيارات المنتج (Product Options) - مثل الألوان والأحجام
        if ($this->user->hasPermission('access', 'catalog/option')) {
            $store_catalog[] = array(
                'name' => $this->language->get('text_product_options'),
                'href' => $this->url->link('catalog/option', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'catalog/option'
            );
        }

        // السمات / الخصائص (Attributes) - للمقارنة والتفاصيل الفنية
        if ($this->user->hasPermission('access', 'catalog/attribute')) {
            $attributes_submenu = array();

            if ($this->user->hasPermission('access', 'catalog/attribute')) {
                $attributes_submenu[] = array(
                    'name' => $this->language->get('text_attributes'),
                    'href' => $this->url->link('catalog/attribute', 'user_token=' . $this->session->data['user_token'], true),
                    'permission' => 'access',
                    'route' => 'catalog/attribute'
                );
            }

            if ($this->user->hasPermission('access', 'catalog/attribute_group')) {
                $attributes_submenu[] = array(
                    'name' => $this->language->get('text_attribute_groups'),
                    'href' => $this->url->link('catalog/attribute_group', 'user_token=' . $this->session->data['user_token'], true),
                    'permission' => 'access',
                    'route' => 'catalog/attribute_group'
                );
            }

            if ($attributes_submenu) {
                $store_catalog[] = array(
                    'name' => $this->language->get('text_attributes_section'),
                    'children' => $attributes_submenu
                );
            }
        }

        // الفلاتر (Filters) - لتصفية المنتجات في المتجر
        if ($this->user->hasPermission('access', 'catalog/filter')) {
            $store_catalog[] = array(
                'name' => $this->language->get('text_product_filters'),
                'href' => $this->url->link('catalog/filter', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'catalog/filter'
            );
        }

        // العلامات التجارية (Brands / Manufacturers)
        if ($this->user->hasPermission('access', 'catalog/manufacturer')) {
            $store_catalog[] = array(
                'name' => $this->language->get('text_brands_manufacturers'),
                'href' => $this->url->link('catalog/manufacturer', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'catalog/manufacturer'
            );
        }

        // تقييمات المنتجات (Product Reviews)
        if ($this->user->hasPermission('access', 'catalog/review')) {
            $store_catalog[] = array(
                'name' => $this->language->get('text_product_reviews'),
                'href' => $this->url->link('catalog/review', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'catalog/review'
            );
        }

        if ($store_catalog) {
            $website_ecommerce[] = array(
                'name' => $this->language->get('text_store_catalog_section'),
                'children' => $store_catalog
            );
        }

        // -- قسم التسعير والعروض الترويجية للمتجر --
        $store_pricing_promo = array();

        // العروض الخاصة (Specials / Discounts)
        if ($this->user->hasPermission('access', 'marketing/coupon')) {
            $promotions_submenu = array();

            // الكوبونات
            if ($this->user->hasPermission('access', 'marketing/coupon')) {
                $promotions_submenu[] = array(
                    'name' => $this->language->get('text_coupons'),
                    'href' => $this->url->link('marketing/coupon', 'user_token=' . $this->session->data['user_token'], true),
                    'permission' => 'access',
                    'route' => 'marketing/coupon'
                );
            }

            // العروض الخاصة على المنتجات
            if ($this->user->hasPermission('access', 'catalog/special')) {
                $promotions_submenu[] = array(
                    'name' => $this->language->get('text_product_specials'),
                    'href' => $this->url->link('catalog/special', 'user_token=' . $this->session->data['user_token'], true),
                    'permission' => 'access',
                    'route' => 'catalog/special'
                );
            }

            // خصومات الكميات
            if ($this->user->hasPermission('access', 'catalog/quantity_discount')) {
                $promotions_submenu[] = array(
                    'name' => $this->language->get('text_quantity_discounts'),
                    'href' => $this->url->link('catalog/quantity_discount', 'user_token=' . $this->session->data['user_token'], true),
                    'permission' => 'access',
                    'route' => 'catalog/quantity_discount'
                );
            }

            // بطاقات الهدايا (Gift Cards / Vouchers)
            if ($this->user->hasPermission('access','marketing/voucher')) {
                $vouchers_submenu = array();

                if ($this->user->hasPermission('access', 'marketing/voucher')) {
                    $vouchers_submenu[] = array(
                        'name' => $this->language->get('text_gift_vouchers'),
                        'href' => $this->url->link('marketing/voucher', 'user_token=' . $this->session->data['user_token'], true),
                        'permission' => 'access',
                        'route' => 'marketing/voucher'
                    );
                }

                if ($this->user->hasPermission('access', 'catalog/voucher_theme')) {
                    $vouchers_submenu[] = array(
                        'name' => $this->language->get('text_voucher_themes'),
                        'href' => $this->url->link('catalog/voucher_theme', 'user_token=' . $this->session->data['user_token'], true),
                        'permission' => 'access',
                        'route' => 'catalog/voucher_theme'
                    );
                }

                if ($vouchers_submenu){
                    $promotions_submenu[] = array(
                        'name' => $this->language->get('text_gift_vouchers_section'),
                        'children' => $vouchers_submenu
                    );
                }
            }

            if ($promotions_submenu) {
                $store_pricing_promo[] = array(
                    'name' => $this->language->get('text_promotions_discounts_section'),
                    'children' => $promotions_submenu
                );
            }
        }

        // إدارة قوائم الأسعار (Price Lists Management) - تحديد أسعار مختلفة لمجموعات عملاء أو قنوات بيع
        if ($this->user->hasPermission('modify', 'catalog/price_list')) {
            $store_pricing_promo[] = array(
                'name' => $this->language->get('text_price_lists_management'),
                'href' => $this->url->link('catalog/price_list', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'catalog/price_list'
            );
        }

        if ($store_pricing_promo) {
            $website_ecommerce[] = array(
                'name' => $this->language->get('text_store_pricing_promotions_section'),
                'children' => $store_pricing_promo
            );
        }

        // -- قسم إدارة المحتوى (Content Management - CMS) --
        $cms = array();

        // الصفحات (Information Pages) - مثل "عنا"، "سياسة الخصوصية"
        if ($this->user->hasPermission('access', 'catalog/information')) {
            $cms[] = array(
                'name' => $this->language->get('text_information_pages'),
                'href' => $this->url->link('catalog/information', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'catalog/information'
            );
        }

        // المدونة (Blog)
        $blog_submenu = array();

        // المقالات (Posts)
        if ($this->user->hasPermission('access', 'catalog/blog_post')) {
            $blog_submenu[] = array(
                'name' => $this->language->get('text_blog_posts'),
                'href' => $this->url->link('catalog/blog_post', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'catalog/blog_post'
            );
        }

        // تصنيفات المدونة (Categories)
        if ($this->user->hasPermission('access', 'catalog/blog_category')) {
            $blog_submenu[] = array(
                'name' => $this->language->get('text_blog_categories'),
                'href' => $this->url->link('catalog/blog_category', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'catalog/blog_category'
            );
        }

        // وسوم المدونة (Tags)
        if ($this->user->hasPermission('access', 'catalog/blog_tag')) {
            $blog_submenu[] = array(
                'name' => $this->language->get('text_blog_tags'),
                'href' => $this->url->link('catalog/blog_tag', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'catalog/blog_tag'
            );
        }

        // تعليقات المدونة (Comments)
        if ($this->user->hasPermission('access', 'catalog/blog_comment')) {
            $blog_submenu[] = array(
                'name' => $this->language->get('text_blog_comments'),
                'href' => $this->url->link('catalog/blog_comment', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'catalog/blog_comment'
            );
        }

        if ($blog_submenu) {
            $cms[] = array(
                'name' => $this->language->get('text_blog_management_section'),
                'children' => $blog_submenu
            );
        }

        // إدارة الوسائط (Media Manager) - لرفع وإدارة الصور والملفات المستخدمة في المحتوى
        if ($this->user->hasPermission('access', 'common/filemanager')) {
            $cms[] = array(
                'name' => $this->language->get('text_media_manager'),
                'href' => $this->url->link('common/filemanager', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'common/filemanager',
                'children' => array()
            );
        }

        if ($cms) {
            $website_ecommerce[] = array(
                'name' => $this->language->get('text_content_management_cms_section'),
                'children' => $cms
            );
        }

        // -- قسم التسويق الرقمي و SEO --
        $digital_marketing_seo = array();

        // حملات التسويق (Marketing Campaigns) - تتبع مصادر الزيارات والمبيعات
        if ($this->user->hasPermission('access', 'marketing/marketing')) {
            $digital_marketing_seo[] = array(
                'name' => $this->language->get('text_marketing_tracking'),
                'href' => $this->url->link('marketing/marketing', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'marketing/marketing'
            );
        }

        // التسويق بالعمولة (Affiliates) - إذا كان مفعلاً
        if ($this->user->hasPermission('access', 'marketing/affiliate')) {
            $digital_marketing_seo[] = array(
                'name' => $this->language->get('text_affiliates'),
                'href' => $this->url->link('marketing/affiliate', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'marketing/affiliate'
            );
        }

        // البريد الإلكتروني والنشرات البريدية (Mail / Newsletters)
        if ($this->user->hasPermission('access', 'marketing/contact')) {
            $digital_marketing_seo[] = array(
                'name' => $this->language->get('text_mail_newsletters'),
                'href' => $this->url->link('marketing/contact', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'marketing/contact'
            );
        }

        // إدارة SEO (SEO Management)
        $seo_submenu = array();

        // روابط SEO (SEO URLs)
        if ($this->user->hasPermission('access', 'design/seo_url')) {
            $seo_submenu[] = array(
                'name' => $this->language->get('text_seo_urls'),
                'href' => $this->url->link('design/seo_url', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'design/seo_url'
            );
        }

        // إعدادات SEO العامة (General SEO Settings) - مثل Robots.txt, Sitemap
        if ($this->user->hasPermission('modify', 'setting/seo')) {
            $seo_submenu[] = array(
                'name' => $this->language->get('text_general_seo_settings'),
                'href' => $this->url->link('setting/seo', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'setting/seo'
            );
        }

        // تحليل صفحات SEO (SEO Page Analysis)
        if ($this->user->hasPermission('access', 'report/seo_analysis')) {
            $seo_submenu[] = array(
                'name' => $this->language->get('text_seo_page_analysis'),
                'href' => $this->url->link('report/seo_analysis', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'report/seo_analysis'
            );
        }

        if ($seo_submenu) {
            $digital_marketing_seo[] = array(
                'name' => $this->language->get('text_seo_management_section'),
                'children' => $seo_submenu
            );
        }

        // تحليلات التسويق (Marketing Analytics)
        if ($this->user->hasPermission('access','marketing/analytics')) {
            $digital_marketing_seo[] = array(
                'name' => $this->language->get('text_marketing_analytics'),
                'href' => $this->url->link('marketing/analytics','user_token=' . $this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'marketing/analytics',
                'children' => array()
            );
        }

        if ($digital_marketing_seo) {
            $website_ecommerce[] = array(
                'name' => $this->language->get('text_digital_marketing_seo_section'),
                'children' => $digital_marketing_seo
            );
        }

        // -- قسم تصميم المتجر وتخصيصه --
        $design_customization = array();

        // إدارة التخطيطات (Layouts)
        if ($this->user->hasPermission('access', 'design/layout')) {
            $design_customization[] = array(
                'name' => $this->language->get('text_layout_management'),
                'href' => $this->url->link('design/layout', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'design/layout'
            );
        }

        // محرر القوالب (Theme Editor)
        if ($this->user->hasPermission('modify', 'design/theme')) {
            $design_customization[] = array(
                'name' => $this->language->get('text_theme_editor'),
                'href' => $this->url->link('design/theme', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'design/theme'
            );
        }

        // محرر اللغة (Language Editor) - لتعديل نصوص الواجهة
        if ($this->user->hasPermission('modify', 'design/translation')) {
            $design_customization[] = array(
                'name' => $this->language->get('text_language_editor'),
                'href' => $this->url->link('design/translation', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'design/translation'
            );
        }

        // إدارة البانرات (Banners)
        if ($this->user->hasPermission('access', 'design/banner')) {
            $design_customization[] = array(
                'name' => $this->language->get('text_banner_management'),
                'href' => $this->url->link('design/banner', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'design/banner'
            );
        }

        if ($design_customization) {
            $website_ecommerce[] = array(
                'name' => $this->language->get('text_design_customization_section'),
                'children' => $design_customization
            );
        }

        // إعدادات المتجر العامة (General Store Settings)
        if ($this->user->hasPermission('modify', 'setting/store')) {
            $website_ecommerce[] = array(
                'name' => $this->language->get('text_general_store_settings'),
                'href' => $this->url->link('setting/store', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'setting/store',
                'children' => array()
            );
        }

        // إضافة القسم الرئيسي للموقع والمتجر الإلكتروني
        if ($website_ecommerce) {
            $data['menus'][] = array(
                'id'       => 'menu-website-ecommerce',
                'icon'     => 'fa-shopping-bag',
                'name'     => $this->language->get('text_website_ecommerce'),
                'href'     => '',
                'children' => $website_ecommerce
            );
        }

        // =======================================================================
        // (8) الموارد البشرية (Human Resources - HR)
        // المستخدمين: مدير الموارد البشرية، موظفو شؤون الموظفين، مسؤولو الرواتب، المدراء (لتقييم الأداء والإجازات)، المحاسبون (لقيود الرواتب).
        // الهدف: إدارة بيانات الموظفين، الحضور والانصراف، الإجازات، الرواتب، تقييم الأداء، التدريب، والسلف.
        // workflow: دورة حياة الموظف (توظيف > ... > إنهاء خدمة), دورة الرواتب (حساب > مراجعة > صرف), دورة تقييم الأداء, دورة الإجازات, دورة السلف.
        // القيود المحاسبية:
        // - الرواتب: (إثبات) من ح/مصروف الرواتب والأجور وملحقاتها XXX إلى ح/الرواتب المستحقة XXX وإلى ح/استقطاعات (تأمينات، ضرائب، سلف) XXX. (صرف) من ح/الرواتب المستحقة XXX إلى ح/البنك XXX.
        // - السلف: (صرف) من ح/سلف الموظفين XXX إلى ح/النقدية أو البنك XXX. (تسوية/خصم من الراتب) من ح/الرواتب المستحقة XXX إلى ح/سلف الموظفين XXX.
        // -----------------------------------------------------------------------
        $hr_management = array();

        // -- لوحة تحكم الموارد البشرية التفاعلية (Interactive HR Dashboard) --
        if ($this->user->hasPermission('access', 'hr/hr_dashboard')) {
            $hr_management[] = array(
                'name' => 'لوحة تحكم الموارد البشرية التفاعلية',
                'href' => $this->url->link('hr/hr_dashboard', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'hr/hr_dashboard'
            );
        }

        // -- قسم إدارة شؤون الموظفين --
        $employee_affairs = array();

        // ملفات الموظفين (Employee Profiles)
        if ($this->user->hasPermission('access', 'hr/employee')) {
            $employee_affairs[] = array(
                'name' => $this->language->get('text_employee_profiles'),
                'href' => $this->url->link('hr/employee', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'hr/employee'
            );
        }

        // الحضور والانصراف (Attendance Management)
        if ($this->user->hasPermission('access', 'hr/attendance')) {
            $employee_affairs[] = array(
                'name' => $this->language->get('text_attendance_management'),
                'href' => $this->url->link('hr/attendance', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'hr/attendance'
            );
        }

        // إدارة الإجازات (Leave Management)
        if ($this->user->hasPermission('access', 'hr/leave')) {
            $employee_affairs[] = array(
                'name' => $this->language->get('text_leave_management'),
                'href' => $this->url->link('hr/leave', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'hr/leave'
            );
        }

        // السلف والعهد للموظفين (Employee Advances & Loans) - المكان الرئيسي لإدارتها
        if ($this->user->hasPermission('access','hr/employee_advance')) {
            $employee_affairs[] = array(
                'name' => $this->language->get('text_employee_advances_loans'),
                'href' => $this->url->link('hr/employee_advance','user_token=' . $this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'hr/employee_advance'
            );
        }

        // وثائق الموظفين (Employee Documents)
        if ($this->user->hasPermission('access','hr/employee_documents')) {
            $employee_affairs[] = array(
                'name' => $this->language->get('text_employee_documents'),
                'href' => $this->url->link('hr/employee_documents','user_token=' . $this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'hr/employee_documents'
            );
        }

        // الهيكل التنظيمي (Organizational Chart) - إذا كان مدعومًا
        if ($this->user->hasPermission('access','hr/org_chart')) {
            $employee_affairs[] = array(
                'name' => $this->language->get('text_organizational_chart'),
                'href' => $this->url->link('hr/org_chart','user_token=' . $this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'hr/org_chart'
            );
        }

        if ($employee_affairs) {
            $hr_management[] = array(
                'name' => $this->language->get('text_employee_affairs_section'),
                'children' => $employee_affairs
            );
        }

        // -- قسم الرواتب والأجور (Payroll) --
        $payroll_section = array();

        // إعداد الرواتب (Payroll Preparation / Run)
        // workflow: جمع بيانات الحضور والإجازات والسلف > حساب المستحقات والاستقطاعات > مراجعة المسير > اعتماد.
        // AI Integration: كشف الأنماط غير العادية في الإضافي أو الخصومات، التحقق من صحة البيانات.
        if ($this->user->hasPermission('modify', 'hr/payroll_run')) {
            $payroll_section[] = array(
                'name' => $this->language->get('text_payroll_preparation_run'),
                'href' => $this->url->link('hr/payroll_run', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'hr/payroll_run'
            );
        }

        // صرف الرواتب (Payroll Disbursement) - إنشاء ملف الدفع للبنك أو تسجيل الدفع النقدي
        // workflow: اعتماد المسير > إنشاء ملف الدفع/أمر الصرف > تنفيذ الدفع > تحديث حالة الدفع للموظفين > إنشاء قيد الصرف.
        if ($this->user->hasPermission('modify', 'hr/payroll_disbursement')) {
            $payroll_section[] = array(
                'name' => $this->language->get('text_payroll_disbursement'),
                'href' => $this->url->link('hr/payroll_disbursement', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'hr/payroll_disbursement'
            );
        }

        // قسائم الراتب (Payslips) - عرض وطباعة قسائم الرواتب للموظفين
        if ($this->user->hasPermission('access', 'hr/payslip')) {
            $payroll_section[] = array(
                'name' => $this->language->get('text_payslips'),
                'href' => $this->url->link('hr/payslip', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'hr/payslip'
            );
        }

        // نظام الرواتب المتطور (Advanced Payroll System) - مع التكامل المحاسبي
        if ($this->user->hasPermission('access', 'hr/payroll_advanced')) {
            $payroll_section[] = array(
                'name' => 'نظام الرواتب المتطور',
                'href' => $this->url->link('hr/payroll_advanced', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'hr/payroll_advanced'
            );
        }

        // إعدادات الرواتب (Payroll Settings) - تعريف عناصر الراتب، الاستقطاعات، التأمينات، الضرائب
        if ($this->user->hasPermission('modify', 'hr/payroll_settings')) {
            $payroll_section[] = array(
                'name' => $this->language->get('text_payroll_settings'),
                'href' => $this->url->link('hr/payroll_settings', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'hr/payroll_settings'
            );
        }

        if ($payroll_section) {
            $hr_management[] = array(
                'name' => $this->language->get('text_payroll_section'),
                'children' => $payroll_section
            );
        }

        // -- قسم السلف والقروض المتطور (Advanced Employee Advances) --
        $employee_advances = array();

        // إدارة السلف والقروض المتطورة (Advanced Employee Advances Management)
        if ($this->user->hasPermission('access', 'hr/employee_advance')) {
            $employee_advances[] = array(
                'name' => 'إدارة السلف والقروض المتطورة',
                'href' => $this->url->link('hr/employee_advance', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'hr/employee_advance'
            );
        }

        // لوحة تحكم السلف (Advances Dashboard)
        if ($this->user->hasPermission('access', 'hr/employee_advance')) {
            $employee_advances[] = array(
                'name' => 'لوحة تحكم السلف',
                'href' => $this->url->link('hr/employee_advance/dashboard', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'hr/employee_advance'
            );
        }

        // الأقساط المستحقة (Pending Installments)
        if ($this->user->hasPermission('access', 'hr/employee_advance')) {
            $employee_advances[] = array(
                'name' => 'الأقساط المستحقة',
                'href' => $this->url->link('hr/employee_advance/pendingInstallments', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'hr/employee_advance'
            );
        }

        if ($employee_advances) {
            $hr_management[] = array(
                'name' => 'السلف والقروض المتطورة',
                'children' => $employee_advances
            );
        }

        // -- قسم تقييم الأداء والتطوير المتقدم --
        $performance_dev = array();

        // نظام تقييم الأداء المتقدم (Advanced Performance Evaluation)
        if ($this->user->hasPermission('access', 'hr/performance_evaluation')) {
            $performance_dev[] = array(
                'name' => 'نظام تقييم الأداء المتقدم',
                'href' => $this->url->link('hr/performance_evaluation', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'hr/performance_evaluation'
            );
        }

        // إدارة التدريب والتطوير (Training & Development)
        if ($this->user->hasPermission('access', 'hr/training_development')) {
            $performance_dev[] = array(
                'name' => 'إدارة التدريب والتطوير',
                'href' => $this->url->link('hr/training_development', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'hr/training_development'
            );
        }

        // خطط التطوير الوظيفي (Career Development Plans)
        if ($this->user->hasPermission('access', 'hr/career_development')) {
            $performance_dev[] = array(
                'name' => 'خطط التطوير الوظيفي',
                'href' => $this->url->link('hr/career_development', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'hr/career_development'
            );
        }

        // نماذج التقييم (Evaluation Forms / Templates)
        // AI Integration: اقتراح معايير تقييم مناسبة للوظيفة، تحليل النصوص في التقييمات.
        if ($this->user->hasPermission('modify', 'hr/evaluation_form')) {
            $performance_dev[] = array(
                'name' => $this->language->get('text_evaluation_forms_templates'),
                'href' => $this->url->link('hr/evaluation_form', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'hr/evaluation_form'
            );
        }

        // دورات التقييم (Evaluation Cycles / Process)
        // workflow: إطلاق دورة التقييم > تعبئة التقييمات (موظف/مدير) > مراجعة واعتماد > ربط النتائج بالترقيات/المكافآت.
        // AI Integration: تحليل نتائج التقييمات، اقتراح خطط تطوير فردية، تحديد الموظفين ذوي الأداء العالي/المنخفض.
        if ($this->user->hasPermission('access', 'hr/evaluation_process')) {
            $performance_dev[] = array(
                'name' => $this->language->get('text_evaluation_cycles_process'),
                'href' => $this->url->link('hr/evaluation_process', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'hr/evaluation_process'
            );
        }

        // إدارة التدريب (Training Management) - تخطيط وتنفيذ وتتبع برامج التدريب
        if ($this->user->hasPermission('access', 'hr/training')) {
            $performance_dev[] = array(
                'name' => $this->language->get('text_training_management'),
                'href' => $this->url->link('hr/training', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'hr/training'
            );
        }

        // المسار الوظيفي والتطوير (Career Development)
        if ($this->user->hasPermission('access', 'hr/career_development')) {
            $performance_dev[] = array(
                'name' => $this->language->get('text_career_development'),
                'href' => $this->url->link('hr/career_development', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'hr/career_development'
            );
        }

        if ($performance_dev) {
            $hr_management[] = array(
                'name' => $this->language->get('text_performance_development_section'),
                'children' => $performance_dev
            );
        }

        // إضافة القسم الرئيسي للموارد البشرية
        if ($hr_management) {
            $data['menus'][] = array(
                'id'       => 'menu-human-resources',
                'icon'     => 'fa-users',
                'name'     => $this->language->get('text_human_resources'),
                'href'     => '',
                'children' => $hr_management
            );
        }

        // =======================================================================
        // (9) إدارة المشاريع (Project Management)
        // المستخدمين: مديرو المشاريع، أعضاء فرق المشاريع، الإدارة العليا (للمتابعة).
        // الهدف: تخطيط وتنفيذ ومتابعة المشاريع والمهام المرتبطة بها.
        // workflow: دورة حياة المشروع (إنشاء > تخطيط > تنفيذ > مراقبة > إغلاق), إدارة المهام, تتبع الوقت.
        // القيود المحاسبية: قد ترتبط بتكاليف المشاريع (مواد، أجور) وإيراداتها، يمكن ربطها بمراكز تكلفة.
        // -----------------------------------------------------------------------
        $project_management = array();

        // المشاريع (Projects)
        if ($this->user->hasPermission('access', 'project/project')) {
            $project_management[] = array(
                'name' => $this->language->get('text_projects_list'),
                'href' => $this->url->link('project/project', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'project/project'
            );
        }

        // المهام (Tasks)
        if ($this->user->hasPermission('access', 'project/task')) {
            $project_management[] = array(
                'name' => $this->language->get('text_tasks_list'),
                'href' => $this->url->link('project/task', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'project/task'
            );
        }

        // لوحة كانبان للمهام (Task Kanban Board) - عرض مرئي لحالة المهام
        if ($this->user->hasPermission('access', 'project/task_board')) {
            $project_management[] = array(
                'name' => $this->language->get('text_task_kanban_board'),
                'href' => $this->url->link('project/task_board', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'project/task_board'
            );
        }

        // مخطط جانت للمشاريع (Project Gantt Chart) - عرض زمني للمشاريع ومهامها
        if ($this->user->hasPermission('access', 'project/gantt')) {
            $project_management[] = array(
                'name' => $this->language->get('text_project_gantt_chart'),
                'href' => $this->url->link('project/gantt', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'project/gantt'
            );
        }

// تتبع الوقت (Time Tracking / Timesheets) - تسجيل الوقت المصروف على المهام والمشاريع
        if ($this->user->hasPermission('access', 'project/timesheet')) {
            $project_management[] = array(
                'name' => $this->language->get('text_time_tracking_timesheets'),
                'href' => $this->url->link('project/timesheet', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'project/timesheet'
            );
        }

        // تقارير المشاريع (Project Reports) - تقارير التكلفة، التقدم، الموارد
        if ($this->user->hasPermission('access', 'report/project')) {
            $project_management[] = array(
                'name' => $this->language->get('text_project_reports'),
                'href' => $this->url->link('report/project', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'report/project'
            );
        }

        // إضافة القسم الرئيسي لإدارة المشاريع
        if ($project_management) {
            $data['menus'][] = array(
                'id'       => 'menu-project-management',
                'icon'     => 'fa-tasks',
                'name'     => $this->language->get('text_project_management'),
                'href'     => '',
                'children' => $project_management
            );
        }

        // =======================================================================
        // (10) التعاون وسير العمل (Collaboration & Workflow)
        // المستخدمين: جميع الموظفين للتواصل، المدراء للموافقات، مسؤولو النظام لتعريف سير العمل.
        // الهدف: تسهيل التواصل الداخلي، إدارة الموافقات على العمليات المختلفة، وتنظيم الاجتماعات.
        // workflow: دورة طلب الموافقة (إنشاء > إرسال للمعتمد > اعتماد/رفض/تفويض > إشعار)، دورة المراسلات، دورة الاجتماعات.
        // القيود المحاسبية: لا يوجد قيود مباشرة، لكن الموافقة على طلب (مثل طلب شراء أو صرف) قد تؤدي لإنشاء قيد لاحقاً.
        // -----------------------------------------------------------------------
        $collaboration_workflow = array();

        // -- قسم التواصل الداخلي --
        $communication = array();

        // المراسلات الداخلية (Internal Messenger / Chat)
        if ($this->user->hasPermission('access', 'communication/messenger')) {
            $communication[] = array(
                'name' => $this->language->get('text_internal_messenger_chat'),
                'href' => $this->url->link('communication/messenger', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'communication/messenger'
            );
        }

        // مجموعات المحادثة (Chat Groups)
        if ($this->user->hasPermission('access', 'communication/groups')) {
            $communication[] = array(
                'name' => $this->language->get('text_chat_groups'),
                'href' => $this->url->link('communication/groups', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'communication/groups'
            );
        }

        // مركز الإشعارات (Notifications Center) - لعرض جميع إشعارات النظام
        if ($this->user->hasPermission('access', 'notification/center')) {
            $communication[] = array(
                'name' => $this->language->get('text_notifications_center'),
                'href' => $this->url->link('notification/center', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'notification/center'
            );
        }

        // لوحة الإعلانات الداخلية (Announcements / Bulletin Board)
        if ($this->user->hasPermission('access', 'communication/announcement')) {
            $communication[] = array(
                'name' => $this->language->get('text_internal_announcements'),
                'href' => $this->url->link('communication/announcement', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'communication/announcement'
            );
        }

        if ($communication) {
            $collaboration_workflow[] = array(
                'name' => $this->language->get('text_internal_communication_section'),
                'children' => $communication
            );
        }

        // -- قسم سير العمل والموافقات (Workflow & Approvals) --
        $workflow_approvals = array();

        // تعريفات سير العمل (Workflow Definitions) - لتصميم مسارات الموافقات
        // AI Integration: اقتراح مسارات موافقة بناءً على نوع الطلب وقيمته، تحليل عنق الزجاجة في الموافقات.
        if ($this->user->hasPermission('modify', 'workflow/definition')) {
            $workflow_approvals[] = array(
                'name' => $this->language->get('text_workflow_definitions'),
                'href' => $this->url->link('workflow/definition', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'workflow/definition'
            );
        }

        // المحرر المرئي المتقدم لسير العمل (Advanced Visual Workflow Editor - شبيه n8n)
        if ($this->user->hasPermission('modify', 'workflow/advanced_visual_editor')) {
            $workflow_approvals[] = array(
                'name' => 'المحرر المرئي المتقدم (شبيه n8n)',
                'href' => $this->url->link('workflow/advanced_visual_editor', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'workflow/advanced_visual_editor'
            );
        }

        // === قسم الذكاء الاصطناعي والأتمتة (AI & Automation) ===
        $ai_automation = array();

        // المساعد الذكي (AI Assistant / Copilot)
        if ($this->user->hasPermission('access', 'ai/ai_assistant')) {
            $ai_automation[] = array(
                'name' => 'المساعد الذكي (AI Copilot)',
                'href' => $this->url->link('ai/ai_assistant', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'ai/ai_assistant'
            );
        }

        // مركز إدارة الذكاء الاصطناعي (AI Center Management)
        if ($this->user->hasPermission('modify', 'ai/ai_center_management')) {
            $ai_automation[] = array(
                'name' => 'مركز إدارة الذكاء الاصطناعي',
                'href' => $this->url->link('ai/ai_center_management', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'ai/ai_center_management'
            );
        }

        // تحليل البيانات الذكي (Smart Data Analytics)
        if ($this->user->hasPermission('access', 'ai/smart_analytics')) {
            $ai_automation[] = array(
                'name' => 'تحليل البيانات الذكي',
                'href' => $this->url->link('ai/smart_analytics', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'ai/smart_analytics'
            );
        }

        // الأتمتة الذكية (Smart Automation)
        if ($this->user->hasPermission('modify', 'ai/smart_automation')) {
            $ai_automation[] = array(
                'name' => 'الأتمتة الذكية',
                'href' => $this->url->link('ai/smart_automation', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'ai/smart_automation'
            );
        }

        // التنبؤات والتوقعات (Predictions & Forecasting)
        if ($this->user->hasPermission('access', 'ai/predictions')) {
            $ai_automation[] = array(
                'name' => 'التنبؤات والتوقعات',
                'href' => $this->url->link('ai/predictions', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'ai/predictions'
            );
        }

        // اكتشاف الشذوذ والاحتيال (Anomaly & Fraud Detection)
        if ($this->user->hasPermission('access', 'ai/anomaly_detection')) {
            $ai_automation[] = array(
                'name' => 'اكتشاف الشذوذ والاحتيال',
                'href' => $this->url->link('ai/anomaly_detection', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'ai/anomaly_detection'
            );
        }

        if ($ai_automation) {
            $data['menus'][] = array(
                'id'       => 'menu-ai-automation',
                'icon'     => 'fa-robot',
                'name'     => 'الذكاء الاصطناعي والأتمتة',
                'href'     => '',
                'children' => $ai_automation
            );
        }

        // طلبات الموافقات (My Approval Requests) - الطلبات التي قدمها المستخدم
        if ($this->user->hasPermission('access', 'workflow/my_requests')) {
            $workflow_approvals[] = array(
                'name' => $this->language->get('text_my_approval_requests'),
                'href' => $this->url->link('workflow/my_requests', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'workflow/my_requests'
            );
        }

        // الموافقات المعلقة (Pending Approvals) - الطلبات التي بانتظار موافقة المستخدم
        if ($this->user->hasPermission('access', 'workflow/pending_approvals')) {
            $workflow_approvals[] = array(
                'name' => $this->language->get('text_pending_my_approval'),
                'href' => $this->url->link('workflow/pending_approvals', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'workflow/pending_approvals'
            );
        }

        // تفويض الموافقات (Approval Delegation) - لتفويض صلاحية الموافقة مؤقتًا
        if ($this->user->hasPermission('modify', 'workflow/delegate')) {
            $workflow_approvals[] = array(
                'name' => $this->language->get('text_approval_delegation'),
                'href' => $this->url->link('workflow/delegate', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'modify',
                'route' => 'workflow/delegate'
            );
        }

        // لوحة تحكم الموافقات (Approval Dashboard) - رؤية شاملة للمدراء
        // AI Integration: تصنيف ذكي للطلبات حسب الأولوية، اقتراح القرارات بناءً على بيانات سابقة.
        if ($this->user->hasPermission('access', 'workflow/approval_dashboard')) {
            $workflow_approvals[] = array(
                'name' => $this->language->get('text_approval_dashboard_overview'),
                'href' => $this->url->link('workflow/approval_dashboard', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'workflow/approval_dashboard'
            );
        }

        if ($workflow_approvals) {
            $collaboration_workflow[] = array(
                'name' => $this->language->get('text_workflow_approvals_section'),
                'children' => $workflow_approvals
            );
        }

        // -- قسم الاجتماعات والتقويم --
        $meetings_calendar = array();

        // إدارة الاجتماعات (Meetings Management) - جدولة، دعوات، محاضر
        if ($this->user->hasPermission('access', 'meeting/meeting')) {
            $meetings_calendar[] = array(
                'name' => $this->language->get('text_meetings_management'),
                'href' => $this->url->link('meeting/meeting', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'meeting/meeting'
            );
        }

        // التقويم (Calendar) - عرض الأحداث والاجتماعات والمهام
        if ($this->user->hasPermission('access', 'meeting/calendar')) {
            $meetings_calendar[] = array(
                'name' => $this->language->get('text_calendar_view'),
                'href' => $this->url->link('meeting/calendar', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'meeting/calendar'
            );
        }

        // إدارة المهام الشخصية (My Tasks) - تختلف عن مهام المشاريع
        if ($this->user->hasPermission('access', 'user/tasks')) {
            $meetings_calendar[] = array(
                'name' => $this->language->get('text_my_personal_tasks'),
                'href' => $this->url->link('user/tasks', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'user/tasks'
            );
        }

        if ($meetings_calendar) {
            $collaboration_workflow[] = array(
                'name' => $this->language->get('text_meetings_calendar_tasks_section'),
                'children' => $meetings_calendar
            );
        }

        // إضافة القسم الرئيسي للتعاون وسير العمل
        if ($collaboration_workflow) {
            $data['menus'][] = array(
                'id'       => 'menu-collaboration-workflow',
                'icon'     => 'fa-comments-o',
                'name'     => $this->language->get('text_collaboration_workflow'),
                'href'     => '',
                'children' => $collaboration_workflow
            );
        }

        // =======================================================================
        // (10.5) الأنظمة المركزية المتقدمة (Advanced Central Systems)
        // المستخدمين: جميع المستخدمين، مدراء النظام، مدراء الأقسام.
        // الهدف: الأنظمة المركزية الجديدة للإشعارات والتواصل والمستندات واللوج.
        // workflow: تكامل شامل مع جميع أجزاء النظام.
        // -----------------------------------------------------------------------
        $central_systems = array();

        // -- نظام الإشعارات المتقدم --
        $notification_system = array();

        // مركز الإشعارات
        if ($this->user->hasPermission('access', 'notification/center')) {
            $notification_system[] = array(
                'name' => $this->language->get('text_notification_center'),
                'href' => $this->url->link('notification/center', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'notification/center'
            );
        }

        // إعدادات الإشعارات
        if ($this->user->hasPermission('access', 'notification/settings')) {
            $notification_system[] = array(
                'name' => $this->language->get('text_notification_settings'),
                'href' => $this->url->link('notification/settings', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'notification/settings'
            );
        }

        // قوالب الإشعارات
        if ($this->user->hasPermission('access', 'notification/templates')) {
            $notification_system[] = array(
                'name' => $this->language->get('text_notification_templates'),
                'href' => $this->url->link('notification/templates', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'notification/templates'
            );
        }

        if ($notification_system) {
            $central_systems[] = array(
                'name' => $this->language->get('text_notification_system'),
                'children' => $notification_system
            );
        }

        // -- نظام التواصل الداخلي --
        $communication_system = array();

        // الرسائل الداخلية
        if ($this->user->hasPermission('access', 'communication/messages')) {
            $communication_system[] = array(
                'name' => $this->language->get('text_internal_messages'),
                'href' => $this->url->link('communication/messages', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'communication/messages'
            );
        }

        // المحادثات المباشرة
        if ($this->user->hasPermission('access', 'communication/chat')) {
            $communication_system[] = array(
                'name' => $this->language->get('text_live_chat'),
                'href' => $this->url->link('communication/chat', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'communication/chat'
            );
        }

        // الإعلانات
        if ($this->user->hasPermission('access', 'communication/announcements')) {
            $communication_system[] = array(
                'name' => $this->language->get('text_announcements'),
                'href' => $this->url->link('communication/announcements', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'communication/announcements'
            );
        }

        // فرق العمل
        if ($this->user->hasPermission('access', 'communication/teams')) {
            $communication_system[] = array(
                'name' => $this->language->get('text_work_teams'),
                'href' => $this->url->link('communication/teams', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'communication/teams'
            );
        }

        if ($communication_system) {
            $central_systems[] = array(
                'name' => $this->language->get('text_communication_system'),
                'children' => $communication_system
            );
        }

        // -- نظام المستندات المتقدم --
        $document_system = array();

        // أرشيف المستندات
        if ($this->user->hasPermission('access', 'documents/archive')) {
            $document_system[] = array(
                'name' => $this->language->get('text_document_archive'),
                'href' => $this->url->link('documents/archive', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'documents/archive'
            );
        }

        // قوالب المستندات
        if ($this->user->hasPermission('access', 'documents/templates')) {
            $document_system[] = array(
                'name' => $this->language->get('text_document_templates'),
                'href' => $this->url->link('documents/templates', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'documents/templates'
            );
        }

        // موافقة المستندات
        if ($this->user->hasPermission('access', 'documents/approval')) {
            $document_system[] = array(
                'name' => $this->language->get('text_document_approval'),
                'href' => $this->url->link('documents/approval', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'documents/approval'
            );
        }

        // إدارة الإصدارات
        if ($this->user->hasPermission('access', 'documents/versioning')) {
            $document_system[] = array(
                'name' => $this->language->get('text_document_versioning'),
                'href' => $this->url->link('documents/versioning', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'documents/versioning'
            );
        }

        if ($document_system) {
            $central_systems[] = array(
                'name' => $this->language->get('text_document_system'),
                'children' => $document_system
            );
        }

        // -- نظام اللوج المتقدم --
        $logging_system = array();

        // سجلات النظام
        if ($this->user->hasPermission('access', 'logging/system_logs')) {
            $logging_system[] = array(
                'name' => $this->language->get('text_system_logs'),
                'href' => $this->url->link('logging/system_logs', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'logging/system_logs'
            );
        }

        // نشاط المستخدمين
        if ($this->user->hasPermission('access', 'logging/user_activity')) {
            $logging_system[] = array(
                'name' => $this->language->get('text_user_activity'),
                'href' => $this->url->link('logging/user_activity', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'logging/user_activity'
            );
        }

        // مسار المراجعة
        if ($this->user->hasPermission('access', 'logging/audit_trail')) {
            $logging_system[] = array(
                'name' => $this->language->get('text_audit_trail'),
                'href' => $this->url->link('logging/audit_trail', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'logging/audit_trail'
            );
        }

        // مراقبة الأداء
        if ($this->user->hasPermission('access', 'logging/performance')) {
            $logging_system[] = array(
                'name' => $this->language->get('text_performance_monitoring'),
                'href' => $this->url->link('logging/performance', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'logging/performance'
            );
        }

        if ($logging_system) {
            $central_systems[] = array(
                'name' => $this->language->get('text_logging_system'),
                'children' => $logging_system
            );
        }

        // إضافة القسم الرئيسي للأنظمة المركزية
        if ($central_systems) {
            $data['menus'][] = array(
                'id'       => 'menu-central-systems',
                'icon'     => 'fa-cogs',
                'name'     => $this->language->get('text_central_systems'),
                'href'     => '',
                'children' => $central_systems
            );
        }

        // =======================================================================
        // (11) الذكاء الاصطناعي والأتمتة (AI & Automation)
        // المستخدمين: محللو البيانات، مديرو الأقسام المتقدمون، مديرو تقنية المعلومات.
        // الهدف: الاستفادة من قدرات الذكاء الاصطناعي لتحليل البيانات، التنبؤ، اكتشاف الاحتيال، تحسين العمليات، وأتمتة المهام الروتينية.
        // workflow: يختلف حسب الوظيفة (تنبؤ، تحليل، أتمتة).
        // القيود المحاسبية: قد يؤثر على دقة التنبؤات المالية أو اكتشاف أخطاء قد تؤدي لتصحيحات محاسبية.
        // -----------------------------------------------------------------------
        $ai_automation = array();

        // مركز الذكاء الاصطناعي (AI Center) - لإدارة النماذج والتكاملات
        if ($this->user->hasPermission('access', 'ai/center')) {
            $ai_automation[] = array(
                'name' => $this->language->get('text_ai_center_management'),
                'href' => $this->url->link('ai/center', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'ai/center',
                'children' => array()
            );
        }

        // التنبؤ بالطلب والمبيعات (Sales & Demand Forecasting) - قد يكون مكررًا من التقارير أو المخزون، لكن هنا يركز على نماذج AI
        if ($this->user->hasPermission('access', 'ai/sales_demand_forecast')) {
            $ai_automation[] = array(
                'name' => $this->language->get('text_ai_sales_demand_forecast'),
                'href' => $this->url->link('ai/sales_demand_forecast', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'ai/sales_demand_forecast',
                'children' => array()
            );
        }

        // تحسين المخزون (Inventory Optimization) - اقتراحات لكميات الطلب المثلى، توزيع المخزون
        if ($this->user->hasPermission('access', 'ai/inventory_optimization')) {
            $ai_automation[] = array(
                'name' => $this->language->get('text_ai_inventory_optimization'),
                'href' => $this->url->link('ai/inventory_optimization', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'ai/inventory_optimization',
                'children' => array()
            );
        }

        // اكتشاف الاحتيال والشذوذ (Fraud & Anomaly Detection) - في المعاملات المالية، المبيعات، المخزون
        if ($this->user->hasPermission('access', 'ai/fraud_detection')) {
            $ai_automation[] = array(
                'name' => $this->language->get('text_ai_fraud_anomaly_detection'),
                'href' => $this->url->link('ai/fraud_detection', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'ai/fraud_detection',
                'children' => array()
            );
        }

        // تحليل سلوك العملاء (Customer Behavior Analysis & Churn Prediction) - لفهم العملاء وتوقع فقدهم
        if ($this->user->hasPermission('access', 'ai/customer_behavior')) {
            $ai_automation[] = array(
                'name' => $this->language->get('text_ai_customer_behavior_analysis'),
                'href' => $this->url->link('ai/customer_behavior', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'ai/customer_behavior',
                'children' => array()
            );
        }

        // أتمتة العمليات (Process Automation - RPA) - لأتمتة المهام المتكررة
        if ($this->user->hasPermission('access', 'ai/process_automation')) {
            $ai_automation[] = array(
                'name' => $this->language->get('text_ai_process_automation_rpa'),
                'href' => $this->url->link('ai/process_automation', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'ai/process_automation',
                'children' => array()
            );
        }

        // مساعد الذكاء الاصطناعي (AI Assistant / Copilot) - للمساعدة في استخدام النظام أو تحليل البيانات
        if ($this->user->hasPermission('access', 'ai/assistant')) {
            $ai_automation[] = array(
                'name' => $this->language->get('text_ai_assistant_copilot'),
                'href' => $this->url->link('ai/assistant', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'ai/assistant',
                'children' => array()
            );
        }

        // إضافة القسم الرئيسي للذكاء الاصطناعي والأتمتة
        if ($ai_automation) {
            $data['menus'][] = array(
                'id'       => 'menu-ai-automation',
                'icon'     => 'fa-robot', // أو fa-brain
                'name'     => $this->language->get('text_ai_automation'),
                'href'     => '',
                'children' => $ai_automation
            );
        }

        // =======================================================================
        // (12) التقارير والتحليلات (Reports & Analytics)
        // المستخدمين: الإدارة العليا، مدراء الأقسام، المحللون، المحاسبون، أي مستخدم يحتاج لبيانات مجمعة.
        // الهدف: توفير رؤى شاملة وقابلة للتخصيص حول أداء الشركة في جميع المجالات.
        // workflow: اختيار التقرير > تحديد المعايير (فترة، فرع...) > عرض/تصدير التقرير > تحليل النتائج.
        // ملاحظات WAC: العديد من التقارير هنا (خاصة المخزون والمالية) ستعتمد على بيانات التكلفة المحسوبة بـ WAC.
        // -----------------------------------------------------------------------
        $reports_analytics = array();

        // التقارير القياسية (Standard Reports) - مجموعة التقارير المدمجة
        if ($this->user->hasPermission('access','report/report')) {
            // يمكن تقسيم التقارير القياسية هنا حسب الوحدة (مبيعات، مشتريات، مخزون...) إذا كانت كثيرة جداً
            $standard_reports = array();

            // مثال: تقارير مبيعات قياسية
            if ($this->user->hasPermission('access','report/sale')) {
                $standard_reports[] = array(
                    'name' => $this->language->get('text_standard_sales_reports'),
                    'href' => $this->url->link('report/sale','user_token=' . $this->session->data['user_token'],true),
                    'permission' => 'access',
                    'route' => 'report/sale'
                );
            }

            // مثال: تقارير مشتريات قياسية
            if ($this->user->hasPermission('access','report/purchase')) {
                $standard_reports[] = array(
                    'name' => $this->language->get('text_standard_purchase_reports'),
                    'href' => $this->url->link('report/purchase','user_token=' . $this->session->data['user_token'],true),
                    'permission' => 'access',
                    'route' => 'report/purchase'
                );
            }

            // مثال: تقارير مخزون قياسية
            if ($this->user->hasPermission('access','report/inventory')) {
                $standard_reports[] = array(
                    'name' => $this->language->get('text_standard_inventory_reports'),
                    'href' => $this->url->link('report/inventory','user_token=' . $this->session->data['user_token'],true),
                    'permission' => 'access',
                    'route' => 'report/inventory'
                );
            }

            // مثال: تقارير مالية قياسية
            if ($this->user->hasPermission('access','accounts/financial_reports')) {
                $standard_reports[] = array(
                    'name' => $this->language->get('text_standard_financial_reports'),
                    'href' => $this->url->link('accounts/financial_reports','user_token=' . $this->session->data['user_token'],true),
                    'permission' => 'access',
                    'route' => 'accounts/financial_reports'
                );
            }

            if ($standard_reports) {
                $reports_analytics[] = array(
                    'name' => $this->language->get('text_standard_reports_section'),
                    'children' => $standard_reports
                );
            } elseif ($this->user->hasPermission('access','report/report')) {
                // إذا لم يكن هناك تفصيل، نضع الرابط العام
                $reports_analytics[] = array(
                    'name' => $this->language->get('text_view_standard_reports'),
                    'href' => $this->url->link('report/report','user_token=' . $this->session->data['user_token'],true),
                    'permission' => 'access',
                    'route' => 'report/report',
                    'children' => array()
                );
            }
        }

        // منشئ التقارير المخصصة (Custom Report Builder)
        // AI Integration: اقتراح حقول ومقاييس ذات صلة، توليد رؤى تلقائية من البيانات المختارة.
        if ($this->user->hasPermission('access', 'report/custom_report_builder')) {
            $reports_analytics[] = array(
                'name' => $this->language->get('text_custom_report_builder'),
                'href' => $this->url->link('report/custom_report_builder', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'report/custom_report_builder',
                'children' => array()
            );
        }

        // التقارير المجدولة (Scheduled Reports) - لإرسال التقارير تلقائياً
        if ($this->user->hasPermission('access', 'report/scheduled')) {
            $reports_analytics[] = array(
                'name' => $this->language->get('text_scheduled_reports'),
                'href' => $this->url->link('report/scheduled', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'report/scheduled',
                'children' => array()
            );
        }

        // أدوات الإحصاء (Statistics Tools) - مثل المستخدمين المتصلين، إحصائيات النظام
        $stats_tools = array();

        // قائمة المتصلين حالياً
        if ($this->user->hasPermission('access','report/online')) {
            $stats_tools[] = array(
                'name' => $this->language->get('text_online_users'),
                'href' => $this->url->link('report/online','user_token=' . $this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'report/online'
            );
        }

        // إحصائيات عامة
        if ($this->user->hasPermission('access','report/statistics')) {
            $stats_tools[] = array(
                'name' => $this->language->get('text_general_statistics'),
                'href' => $this->url->link('report/statistics','user_token=' . $this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'report/statistics'
            );
        }

        if ($stats_tools) {
            $reports_analytics[] = array(
                'name' => $this->language->get('text_statistics_tools_section'),
                'children' => $stats_tools
            );
        }

        // إضافة القسم الرئيسي للتقارير والتحليلات
        if ($reports_analytics) {
            $data['menus'][] = array(
                'id'       => 'menu-reports-analytics',
                'icon'     => 'fa-bar-chart',
                'name'     => $this->language->get('text_reports_and_analytics'),
                'href'     => '',
                'children' => $reports_analytics
            );
        }

        // =======================================================================
        // (13) النظام والإعدادات (System & Settings)
        // المستخدمين: مديرو النظام، مديرو تقنية المعلومات، الدعم الفني.
        // الهدف: إدارة الإعدادات العامة للنظام، المستخدمين والصلاحيات، التوطين، الأدوات المساعدة، والصيانة.
        // -----------------------------------------------------------------------
        $system_settings = array();

        // الإعدادات العامة للنظام (General System Settings)
        if ($this->user->hasPermission('modify','setting/setting')) {
            $system_settings[] = array(
                'name' => $this->language->get('text_general_system_settings'),
                'href' => $this->url->link('setting/setting','user_token=' . $this->session->data['user_token'],true),
                'permission' => 'modify',
                'route' => 'setting/setting',
                'children' => array()
            );
        }

        // -- قسم المستخدمين والصلاحيات --
        $users_permissions = array();

        // المستخدمين (Users)
        if ($this->user->hasPermission('access','user/user')) {
            $users_permissions[] = array(
                'name' => $this->language->get('text_users_management'),
                'href' => $this->url->link('user/user','user_token=' . $this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'user/user'
            );
        }

        // مجموعات المستخدمين (User Groups / Roles)
        if ($this->user->hasPermission('access','user/user_group')) {
            $users_permissions[] = array(
                'name' => $this->language->get('text_user_groups_roles'),
                'href' => $this->url->link('user/user_group','user_token=' . $this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'user/user_group'
            );
        }

        // صلاحيات الوصول (Permissions) - قد تكون مدمجة في المجموعات أو منفصلة للعرض
        if ($this->user->hasPermission('access','user/permission')) {
            $users_permissions[] = array(
                'name' => $this->language->get('text_permissions_list'),
                'href' => $this->url->link('user/permission','user_token=' . $this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'user/permission'
            );
        }

        // واجهة برمجة التطبيقات (API Keys)
        if ($this->user->hasPermission('access','user/api')) {
            $users_permissions[] = array(
                'name' => $this->language->get('text_api_keys_management'),
                'href' => $this->url->link('user/api','user_token=' . $this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'user/api'
            );
        }

        if ($users_permissions) {
            $system_settings[] = array(
                'name' => $this->language->get('text_users_permissions_section'),
                'children' => $users_permissions
            );
        }

        // -- قسم التوطين (Localisation) --
        $localisation = array();

        // المواقع (المخازن/الفروع) - مكرر من المخزون
        if ($this->user->hasPermission('access','localisation/location')) {
            $localisation[] = array(
                'name' => $this->language->get('text_locations_branches_warehouses_link'),
                'href' => $this->url->link('localisation/location','user_token=' . $this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'localisation/location'
            );
        }

        // اللغات (Languages)
        if ($this->user->hasPermission('access','localisation/language')) {
            $localisation[] = array(
                'name' => $this->language->get('text_languages'),
                'href' => $this->url->link('localisation/language','user_token=' . $this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'localisation/language'
            );
        }

        // العملات (Currencies)
        if ($this->user->hasPermission('access','localisation/currency')) {
            $localisation[] = array(
                'name' => $this->language->get('text_currencies'),
                'href' => $this->url->link('localisation/currency','user_token=' . $this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'localisation/currency'
            );
        }

        // حالات المخزون (Stock Statuses)
        if ($this->user->hasPermission('access','localisation/stock_status')) {
            $localisation[] = array(
                'name' => $this->language->get('text_stock_statuses'),
                'href' => $this->url->link('localisation/stock_status','user_token=' . $this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'localisation/stock_status'
            );
        }

        // حالات الطلبات (Order Statuses)
        if ($this->user->hasPermission('access','localisation/order_status')) {
            $localisation[] = array(
                'name' => $this->language->get('text_order_statuses'),
                'href' => $this->url->link('localisation/order_status','user_token=' . $this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'localisation/order_status'
            );
        }

        // إعدادات المرتجعات (Return Settings)
        $returns_localisation = array();

        if ($this->user->hasPermission('access','localisation/return_status')) {
            $returns_localisation[] = array(
                'name' => $this->language->get('text_return_statuses'),
                'href' => $this->url->link('localisation/return_status','user_token='.$this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'localisation/return_status'
            );
        }

        if ($this->user->hasPermission('access','localisation/return_action')) {
            $returns_localisation[] = array(
                'name' => $this->language->get('text_return_actions'),
                'href' => $this->url->link('localisation/return_action','user_token='.$this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'localisation/return_action'
            );
        }

        if ($this->user->hasPermission('access','localisation/return_reason')) {
            $returns_localisation[] = array(
                'name' => $this->language->get('text_return_reasons'),
                'href' => $this->url->link('localisation/return_reason','user_token='.$this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'localisation/return_reason'
            );
        }

        if ($returns_localisation) {
            $localisation[] = array(
                'name' => $this->language->get('text_returns_settings_section'),
                'children' => $returns_localisation
            );
        }

        // الدول والمناطق (Countries & Zones)
        $countries_zones = array();

        if ($this->user->hasPermission('access','localisation/country')) {
            $countries_zones[] = array(
                'name' => $this->language->get('text_countries'),
                'href' => $this->url->link('localisation/country','user_token='.$this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'localisation/country'
            );
        }

        if ($this->user->hasPermission('access','localisation/zone')) {
            $countries_zones[] = array(
                'name' => $this->language->get('text_zones_regions'),
                'href' => $this->url->link('localisation/zone','user_token='.$this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'localisation/zone'
            );
        }

        if ($countries_zones) {
            $localisation[] = array(
                'name' => $this->language->get('text_countries_zones_section'),
                'children' => $countries_zones
            );
        }

        // المناطق الجغرافية (Geo Zones) - للشحن والضرائب
        if ($this->user->hasPermission('access','localisation/geo_zone')) {
            $localisation[] = array(
                'name' => $this->language->get('text_geo_zones'),
                'href' => $this->url->link('localisation/geo_zone','user_token=' . $this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'localisation/geo_zone'
            );
        }

        // الضرائب (Taxes)
        $taxes_localisation = array();

        if ($this->user->hasPermission('access','localisation/tax_class')) {
            $taxes_localisation[] = array(
                'name' => $this->language->get('text_tax_classes'),
                'href' => $this->url->link('localisation/tax_class','user_token='.$this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'localisation/tax_class'
            );
        }

        if ($this->user->hasPermission('access','localisation/tax_rate')) {
            $taxes_localisation[] = array(
                'name' => $this->language->get('text_tax_rates'),
                'href' => $this->url->link('localisation/tax_rate','user_token='.$this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'localisation/tax_rate'
            );
        }

        if ($taxes_localisation) {
            $localisation[] = array(
                'name' => $this->language->get('text_taxes_section'),
                'children' => $taxes_localisation
            );
        }

        // وحدات القياس (Units of Measure)
        $uom_localisation = array();

        if ($this->user->hasPermission('access','localisation/length_class')) {
            $uom_localisation[] = array(
                'name' => $this->language->get('text_length_classes'),
                'href' => $this->url->link('localisation/length_class','user_token='.$this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'localisation/length_class'
            );
        }

        if ($this->user->hasPermission('access','localisation/weight_class')) {
            $uom_localisation[] = array(
                'name' => $this->language->get('text_weight_classes'),
                'href' => $this->url->link('localisation/weight_class','user_token='.$this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'localisation/weight_class'
            );
        }

        if ($this->user->hasPermission('access','localisation/unit_class')) {
            $uom_localisation[] = array(
                'name' => $this->language->get('text_general_unit_classes'),
                'href' => $this->url->link('localisation/unit_class','user_token='.$this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'localisation/unit_class'
            );
        }

        if ($uom_localisation) {
            $localisation[] = array(
                'name' => $this->language->get('text_units_of_measure_section'),
                'children' => $uom_localisation
            );
        }

        if ($localisation) {
            $system_settings[] = array(
                'name' => $this->language->get('text_localisation_section'),
                'children' => $localisation
            );
        }

        // -- قسم المحاسبة --
        $accounting_section = array();

        // إعدادات المحاسبة
        if ($this->user->hasPermission('access', 'accounting/settings')) {
            $accounting_section[] = array(
                'name' => $this->language->get('text_accounting_settings'),
                'href' => $this->url->link('accounting/settings', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'accounting/settings'
            );
        }

        // الحسابات المحاسبية
        if ($this->user->hasPermission('access', 'accounting/account')) {
            $accounting_section[] = array(
                'name' => $this->language->get('text_accounting_accounts'),
                'href' => $this->url->link('accounting/account', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'accounting/account'
            );
        }

        // دفتر اليومية
        if ($this->user->hasPermission('access', 'accounting/journal')) {
            $accounting_section[] = array(
                'name' => $this->language->get('text_accounting_journal'),
                'href' => $this->url->link('accounting/journal', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'accounting/journal'
            );
        }

        // التقارير المالية
        if ($this->user->hasPermission('access', 'accounting/report')) {
            $accounting_section[] = array(
                'name' => $this->language->get('text_financial_reports'),
                'href' => $this->url->link('accounting/report', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'accounting/report'
            );
        }

        // الفترات المحاسبية
        if ($this->user->hasPermission('access', 'accounting/period')) {
            $accounting_section[] = array(
                'name' => $this->language->get('text_accounting_periods'),
                'href' => $this->url->link('accounting/period', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'accounting/period'
            );
        }

        if ($accounting_section) {
            $system_settings[] = array(
                'name' => $this->language->get('text_accounting_section'),
                'children' => $accounting_section
            );
        }

        // -- قسم الأدوات والصيانة --
        $tools_maintenance = array();

        // النسخ الاحتياطي والاستعادة (Backup & Restore)
        if ($this->user->hasPermission('access', 'tool/backup')) {
            $tools_maintenance[] = array(
                'name' => $this->language->get('text_backup_restore'),
                'href' => $this->url->link('tool/backup','user_token=' . $this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'tool/backup'
            );
        }

        // سجل أخطاء النظام (Error Logs)
        if ($this->user->hasPermission('access', 'tool/log')) {
            $tools_maintenance[] = array(
                'name' => $this->language->get('text_system_error_logs'),
                'href' => $this->url->link('tool/log','user_token=' . $this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'tool/log'
            );
        }

        // سجل تدقيق النظام (Audit Trail / Activity Log) - لتتبع من فعل ماذا ومتى
        if ($this->user->hasPermission('access', 'report/activity')) {
            $tools_maintenance[] = array(
                'name' => $this->language->get('text_audit_trail_activity_log'),
                'href' => $this->url->link('report/activity','user_token=' . $this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'report/activity'
            );
        }

        // مراقبة أداء النظام (System Performance Monitor)
        if ($this->user->hasPermission('access', 'tool/performance')) {
            $tools_maintenance[] = array(
                'name' => $this->language->get('text_system_performance_monitor'),
                'href' => $this->url->link('tool/performance','user_token=' . $this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'tool/performance'
            );
        }

        // معلومات النظام (System Information / phpinfo)
        if ($this->user->hasPermission('access', 'tool/phpinfo')) {
            $tools_maintenance[] = array(
                'name' => $this->language->get('text_system_information'),
                'href' => $this->url->link('tool/phpinfo','user_token=' . $this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'tool/phpinfo'
            );
        }

        if ($tools_maintenance) {
            $system_settings[] = array(
                'name' => $this->language->get('text_tools_maintenance_section'),
                'children' => $tools_maintenance
            );
        }

        // -- قسم الإضافات والتعديلات (Extensions & Modifications) --
        $extensions = array();

        // سوق الإضافات (Marketplace) - إذا كان مدمجًا
        if ($this->user->hasPermission('access', 'marketplace/marketplace')) {
            $extensions[] = array(
                'name' => $this->language->get('text_extension_marketplace'),
                'href' => $this->url->link('marketplace/marketplace','user_token=' . $this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'marketplace/marketplace'
            );
        }

        // مثبت الإضافات (Installer)
        if ($this->user->hasPermission('access', 'marketplace/installer')) {
            $extensions[] = array(
                'name' => $this->language->get('text_extension_installer'),
                'href' => $this->url->link('marketplace/installer','user_token=' . $this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'marketplace/installer'
            );
        }

        // إدارة الإضافات (Extensions Management)
        if ($this->user->hasPermission('access', 'marketplace/extension')) {
            $extensions[] = array(
                'name' => $this->language->get('text_extensions_management'),
                'href' => $this->url->link('marketplace/extension','user_token=' . $this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'marketplace/extension'
            );
        }

        // التعديلات (Modifications - OCMOD/VQMOD)
        if ($this->user->hasPermission('access', 'marketplace/modification')) {
            $extensions[] = array(
                'name' => $this->language->get('text_modifications_management'),
                'href' => $this->url->link('marketplace/modification','user_token=' . $this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'marketplace/modification'
            );
        }

        // الأحداث (Events / Hooks)
        if ($this->user->hasPermission('access', 'marketplace/event')) {
            $extensions[] = array(
                'name' => $this->language->get('text_events_hooks_management'),
                'href' => $this->url->link('marketplace/event','user_token=' . $this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'marketplace/event'
            );
        }

        if ($extensions) {
            $system_settings[] = array(
                'name' => $this->language->get('text_extensions_modifications_section'),
                'children' => $extensions
            );
        }

        // -- قسم الحوكمة والامتثال (Governance & Compliance) --
        // تم نقله هنا لمركزية الإدارة
        $governance_compliance = array();

        // سجل المخاطر (Risk Register)
        if ($this->user->hasPermission('access', 'governance/risk_register')) {
            $governance_compliance[] = array(
                'name' => $this->language->get('text_risk_register'),
                'href' => $this->url->link('governance/risk_register', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'governance/risk_register'
            );
        }

        // إدارة الرقابة الداخلية (Internal Controls Management)
        if ($this->user->hasPermission('access', 'governance/internal_control')) {
            $governance_compliance[] = array(
                'name' => $this->language->get('text_internal_controls_management'),
                'href' => $this->url->link('governance/internal_control', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'governance/internal_control'
            );
        }

        // سجل الامتثال (Compliance Record) - متابعة الالتزام باللوائح
        if ($this->user->hasPermission('access', 'governance/compliance')) {
            $governance_compliance[] = array(
                'name' => $this->language->get('text_compliance_record_tracking'),
                'href' => $this->url->link('governance/compliance', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'governance/compliance'
            );
        }

        // إدارة التدقيق الداخلي (Internal Audit Management)
        if ($this->user->hasPermission('access', 'governance/internal_audit')) {
            $governance_compliance[] = array(
                'name' => $this->language->get('text_internal_audit_management'),
                'href' => $this->url->link('governance/internal_audit', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'governance/internal_audit'
            );
        }

        // إدارة العقود (Contracts Management) - المكان الرئيسي للعقود القانونية والتجارية
        if ($this->user->hasPermission('access', 'legal/contract_management')) {
            $governance_compliance[] = array(
                'name' => $this->language->get('text_contracts_management'),
                'href' => $this->url->link('legal/contract_management', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'legal/contract_management'
            );
        }

        // إدارة الوثائق الهامة (Important Documents Management)
        if ($this->user->hasPermission('access', 'legal/document_management')) {
            $governance_compliance[] = array(
                'name' => $this->language->get('text_important_documents_management'),
                'href' => $this->url->link('legal/document_management', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'legal/document_management'
            );
        }

        if ($governance_compliance) {
            $system_settings[] = array(
                'name' => $this->language->get('text_governance_compliance_section'),
                'children' => $governance_compliance
            );
        }

        // إضافة القسم الرئيسي للنظام والإعدادات
        if ($system_settings) {
            $data['menus'][] = array(
                'id'       => 'menu-system-settings',
                'icon'     => 'fa-cogs',
                'name'     => $this->language->get('text_system_and_settings'),
                'href'     => '',
                'children' => $system_settings
            );
        }

        // =======================================================================
        // (13.5) النسخ الاحتياطي وإدارة البيانات (Backup & Data Management)
        // المستخدمين: مدراء النظام، مدراء قواعد البيانات.
        // الهدف: إنشاء نسخ احتياطية وإدارة البيانات وتصديرها.
        // workflow: إنشاء نسخة احتياطية -> تصدير البيانات -> رفع للسحابة -> جدولة تلقائية.
        // -----------------------------------------------------------------------
        $backup_management = array();

        // النسخ الاحتياطي اليدوي
        if ($this->user->hasPermission('access', 'tool/backup')) {
            $backup_management[] = array(
                'name' => $this->language->get('text_manual_backup'),
                'href' => $this->url->link('tool/backup', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'tool/backup'
            );
        }

        // تصدير البيانات إلى Excel
        if ($this->user->hasPermission('access', 'tool/export_excel')) {
            $backup_management[] = array(
                'name' => $this->language->get('text_export_to_excel'),
                'href' => $this->url->link('tool/export_excel', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'tool/export_excel'
            );
        }

        // تصدير إلى Google Drive
        if ($this->user->hasPermission('access', 'tool/google_drive_export')) {
            $backup_management[] = array(
                'name' => $this->language->get('text_export_to_google_drive'),
                'href' => $this->url->link('tool/google_drive_export', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'tool/google_drive_export'
            );
        }

        // جدولة النسخ الاحتياطية
        if ($this->user->hasPermission('access', 'tool/backup_scheduler')) {
            $backup_management[] = array(
                'name' => $this->language->get('text_backup_scheduler'),
                'href' => $this->url->link('tool/backup_scheduler', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'tool/backup_scheduler'
            );
        }

        // استعادة البيانات
        if ($this->user->hasPermission('access', 'tool/restore')) {
            $backup_management[] = array(
                'name' => $this->language->get('text_restore_data'),
                'href' => $this->url->link('tool/restore', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'tool/restore'
            );
        }

        // سجل النسخ الاحتياطية
        if ($this->user->hasPermission('access', 'tool/backup_history')) {
            $backup_management[] = array(
                'name' => $this->language->get('text_backup_history'),
                'href' => $this->url->link('tool/backup_history', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'tool/backup_history'
            );
        }

        // إعدادات النسخ الاحتياطي
        if ($this->user->hasPermission('access', 'tool/backup_settings')) {
            $backup_management[] = array(
                'name' => $this->language->get('text_backup_settings'),
                'href' => $this->url->link('tool/backup_settings', 'user_token=' . $this->session->data['user_token'], true),
                'permission' => 'access',
                'route' => 'tool/backup_settings'
            );
        }

        // إضافة قسم النسخ الاحتياطي
        if ($backup_management) {
            $data['menus'][] = array(
                'id'       => 'menu-backup-management',
                'icon'     => 'fa-database',
                'name'     => $this->language->get('text_backup_management'),
                'href'     => '',
                'children' => $backup_management
            );
        }

        // =======================================================================
        // (14) الاشتراك والدعم (Subscription & Support)
        // المستخدمين: مدير النظام، المسؤول المالي.
        // الهدف: إدارة تفاصيل الاشتراك في النظام السحابي وطلب الدعم الفني.
        // -----------------------------------------------------------------------
        $subscription_support = array();

        // معلومات الاشتراك (Subscription Information)
        if ($this->user->hasPermission('access','subscription/info')) {
            $subscription_support[] = array(
                'name' => $this->language->get('text_subscription_information'),
                'href' => $this->url->link('subscription/info','user_token=' . $this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'subscription/info'
            );
        }

        // الفواتير والمدفوعات (Billing & Payments) - للاشتراك
        if ($this->user->hasPermission('access','subscription/billing')) {
            $subscription_support[] = array(
                'name' => $this->language->get('text_subscription_billing_payments'),
                'href' => $this->url->link('subscription/billing','user_token=' . $this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'subscription/billing'
            );
        }

        // طلب الدعم الفني (Request Support)
        if ($this->user->hasPermission('access','support/request')) {
            $subscription_support[] = array(
                'name' => $this->language->get('text_request_technical_support'),
                'href' => $this->url->link('support/request','user_token=' . $this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'support/request'
            );
        }

        // قاعدة المعرفة (Knowledge Base) - إذا كانت متاحة
        if ($this->user->hasPermission('access','support/knowledge_base')) {
            $subscription_support[] = array(
                'name' => $this->language->get('text_knowledge_base'),
                'href' => $this->url->link('support/knowledge_base','user_token=' . $this->session->data['user_token'],true),
                'permission' => 'access',
                'route' => 'support/knowledge_base'
            );
        }

        // إضافة قسم الاشتراك والدعم
        if ($subscription_support) {
            $data['menus'][] = array(
                'id'       => 'menu-subscription-support',
                'icon'     => 'fa-cloud',
                'name'     => $this->language->get('text_subscription_support'),
                'href'     => '',
                'children' => $subscription_support
            );
        }

        // -----------------------------------------------------
        // 4) فلترة القائمة النهائية بناءً على صلاحيات المستخدم الفعلية
        // (هذه الخطوة مهمة لضمان عدم ظهور قسم فارغ إذا لم يكن للمستخدم صلاحية على أي من عناصره الفرعية)
        // -----------------------------------------------------
        $filtered_menus = array();

        foreach ($data['menus'] as $menu) {
            if (isset($menu['href']) && $menu['href']) { // إذا كان عنصراً رئيسياً له رابط مباشر
                // التحقق من الصلاحية إذا تم تحديدها (يمكن إضافتها للعناصر الرئيسية أيضاً)
                $routeParts = explode('/', isset($menu['route']) ? $menu['route'] : '');
                $permissionRoute = count($routeParts) >= 2 ? $routeParts[0] . '/' . $routeParts[1] : '';

                if (isset($menu['permission']) && $permissionRoute && !$this->user->hasPermission($menu['permission'], $permissionRoute)) {
                    continue; // تخطي العنصر إذا لم تكن هناك صلاحية
                }

                $filtered_menus[] = $menu;
            } else { // إذا كان عنصراً رئيسياً يحتوي على قائمة فرعية
                $children = array();

                foreach ($menu['children'] as $child_level_1) {
                    if (isset($child_level_1['children']) && $child_level_1['children']) { // إذا كان عنصراً فرعياً من المستوى الأول وله أبناء (المستوى الثاني)
                        $sub_children = array();

                        foreach ($child_level_1['children'] as $child_level_2) {
                            // التحقق من الصلاحية للعنصر الفرعي من المستوى الثاني
                            $routeParts = explode('/', isset($child_level_2['route']) ? $child_level_2['route'] : '');
                            $permissionRoute = count($routeParts) >= 2 ? $routeParts[0] . '/' . $routeParts[1] : '';

                            if ($permissionRoute && $this->user->hasPermission($child_level_2['permission'] ?? 'access', $permissionRoute)) {
                                $sub_children[] = $child_level_2;
                            }
                        }

                        if ($sub_children) { // فقط أضف العنصر الأب إذا كان لديه أبناء لديهم صلاحية
                            $child_level_1['children'] = $sub_children;
                            $children[] = $child_level_1;
                        }
                    } else { // إذا كان عنصراً فرعياً من المستوى الأول وليس له أبناء
                        // التحقق من الصلاحية للعنصر الفرعي من المستوى الأول
                        $routeParts = explode('/', isset($child_level_1['route']) ? $child_level_1['route'] : '');
                        $permissionRoute = count($routeParts) >= 2 ? $routeParts[0] . '/' . $routeParts[1] : '';

                        if ($permissionRoute && $this->user->hasPermission($child_level_1['permission'] ?? 'access', $permissionRoute)) {
                            $children[] = $child_level_1;
                        }
                    }
                }

                if ($children) { // فقط أضف القائمة الرئيسية إذا كانت تحتوي على أي عناصر فرعية مسموح بها
                    $menu['children'] = $children;
                    $filtered_menus[] = $menu;
                }
            }
        }

        $data['menus'] = $filtered_menus; // استبدال القائمة الأصلية بالقائمة المفلترة

        // -----------------------------------------------------
        // 5) إرسال البيانات إلى ملف العرض (View)
        // -----------------------------------------------------
        return $this->load->view('common/column_left', $data);
    }
}
