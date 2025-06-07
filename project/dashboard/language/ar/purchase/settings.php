<?php
// Heading
$_['heading_title']          = 'إعدادات المشتريات';

// Text
$_['text_success']           = 'تم: تم تحديث إعدادات المشتريات بنجاح!';
$_['text_reset_success']     = 'تم إعادة تعيين الإعدادات للقيم الافتراضية بنجاح!';
$_['text_import_success']    = 'تم استيراد الإعدادات بنجاح!';
$_['text_edit']              = 'تعديل إعدادات المشتريات';
$_['text_enabled']           = 'مفعل';
$_['text_disabled']          = 'معطل';

// Tab
$_['tab_general']            = 'إعدادات عامة';
$_['tab_numbering']          = 'إعدادات الترقيم';
$_['tab_notifications']      = 'إعدادات الإشعارات';
$_['tab_inventory']          = 'إعدادات المخزون';
$_['tab_integration']        = 'إعدادات التكامل';
$_['tab_approval']           = 'إعدادات الموافقة';
$_['tab_reports']            = 'إعدادات التقارير';

// Entry - General Settings
$_['entry_auto_approve_limit'] = 'حد الموافقة التلقائية';
$_['entry_require_approval'] = 'يتطلب موافقة';
$_['entry_default_payment_terms'] = 'شروط الدفع الافتراضية';
$_['entry_default_currency'] = 'العملة الافتراضية';

// Entry - Numbering Settings
$_['entry_order_prefix']     = 'بادئة أمر الشراء';
$_['entry_order_start_number'] = 'رقم البداية لأمر الشراء';
$_['entry_requisition_prefix'] = 'بادئة طلب الشراء';
$_['entry_quotation_prefix'] = 'بادئة عرض السعر';

// Entry - Notification Settings
$_['entry_email_notifications'] = 'إشعارات البريد الإلكتروني';
$_['entry_notification_emails'] = 'عناوين البريد للإشعارات';
$_['entry_low_stock_notification'] = 'إشعار نفاد المخزون';

// Entry - Inventory Settings
$_['entry_auto_update_inventory'] = 'تحديث المخزون تلقائياً';
$_['entry_inventory_method'] = 'طريقة تقييم المخزون';
$_['entry_reorder_level_days'] = 'أيام مستوى إعادة الطلب';

// Entry - Integration Settings
$_['entry_accounting_integration'] = 'تكامل المحاسبة';
$_['entry_expense_account'] = 'حساب المصروفات';
$_['entry_payable_account'] = 'حساب الدائنين';

// Entry - Approval Settings
$_['entry_approval_workflow'] = 'سير عمل الموافقة';
$_['entry_approval_levels'] = 'مستويات الموافقة';

// Entry - Report Settings
$_['entry_default_report_period'] = 'فترة التقرير الافتراضية';
$_['entry_report_auto_email'] = 'إرسال التقارير تلقائياً';

// Button
$_['button_save']            = 'حفظ';
$_['button_cancel']          = 'إلغاء';
$_['button_reset']           = 'إعادة تعيين';
$_['button_export']          = 'تصدير الإعدادات';
$_['button_import']          = 'استيراد الإعدادات';

// Payment Terms
$_['text_net_30']            = 'صافي 30 يوم';
$_['text_net_60']            = 'صافي 60 يوم';
$_['text_net_90']            = 'صافي 90 يوم';
$_['text_cod']               = 'الدفع عند التسليم';
$_['text_prepaid']           = 'مدفوع مقدماً';

// Inventory Methods
$_['text_fifo']              = 'الوارد أولاً صادر أولاً (FIFO)';
$_['text_lifo']              = 'الوارد أخيراً صادر أولاً (LIFO)';
$_['text_weighted_average']  = 'المتوسط المرجح';

// Approval Workflows
$_['text_no_approval']       = 'بدون موافقة';
$_['text_single_approval']   = 'موافقة واحدة';
$_['text_multi_approval']    = 'موافقة متعددة المستويات';

// Report Periods
$_['text_daily']             = 'يومي';
$_['text_weekly']            = 'أسبوعي';
$_['text_monthly']           = 'شهري';
$_['text_quarterly']         = 'ربع سنوي';
$_['text_yearly']            = 'سنوي';

// Help
$_['help_auto_approve_limit'] = 'المبلغ الأقصى الذي يمكن الموافقة عليه تلقائياً بدون تدخل بشري';
$_['help_require_approval']  = 'هل تتطلب جميع أوامر الشراء موافقة قبل التنفيذ؟';
$_['help_order_prefix']      = 'البادئة المستخدمة في ترقيم أوامر الشراء (مثل: PO)';
$_['help_order_start_number'] = 'الرقم الذي سيبدأ منه ترقيم أوامر الشراء';
$_['help_notification_emails'] = 'عناوين البريد الإلكتروني التي ستتلقى الإشعارات (مفصولة بفاصلة)';
$_['help_low_stock_notification'] = 'إرسال إشعار عند انخفاض مستوى المخزون';
$_['help_auto_update_inventory'] = 'تحديث كميات المخزون تلقائياً عند استلام البضائع';
$_['help_inventory_method']  = 'طريقة تقييم المخزون المستخدمة في حساب التكلفة';
$_['help_reorder_level_days'] = 'عدد الأيام المستخدمة في حساب مستوى إعادة الطلب';
$_['help_accounting_integration'] = 'تفعيل التكامل مع النظام المحاسبي';
$_['help_approval_workflow'] = 'نوع سير عمل الموافقة المطلوب';
$_['help_approval_levels']   = 'عدد مستويات الموافقة المطلوبة';
$_['help_default_report_period'] = 'الفترة الافتراضية لعرض التقارير';
$_['help_report_auto_email'] = 'إرسال التقارير تلقائياً بالبريد الإلكتروني';

// Error
$_['error_permission']       = 'تحذير: ليس لديك صلاحية لتعديل إعدادات المشتريات!';
$_['error_auto_approve_limit'] = 'حد الموافقة التلقائية يجب أن يكون رقماً صحيحاً!';
$_['error_start_number']     = 'رقم البداية يجب أن يكون رقماً صحيحاً!';
$_['error_reorder_level_days'] = 'أيام مستوى إعادة الطلب يجب أن تكون رقماً صحيحاً!';
$_['error_invalid_file']     = 'ملف غير صحيح!';
$_['error_upload']           = 'خطأ في رفع الملف!';

// Success Messages
$_['success_settings_saved'] = 'تم حفظ الإعدادات بنجاح!';
$_['success_settings_reset'] = 'تم إعادة تعيين الإعدادات بنجاح!';
$_['success_settings_exported'] = 'تم تصدير الإعدادات بنجاح!';
$_['success_settings_imported'] = 'تم استيراد الإعدادات بنجاح!';

// Info Messages
$_['info_general_settings']  = 'الإعدادات العامة لنظام المشتريات';
$_['info_numbering_settings'] = 'إعدادات ترقيم المستندات';
$_['info_notification_settings'] = 'إعدادات الإشعارات والتنبيهات';
$_['info_inventory_settings'] = 'إعدادات إدارة المخزون';
$_['info_integration_settings'] = 'إعدادات التكامل مع الأنظمة الأخرى';
$_['info_approval_settings'] = 'إعدادات سير عمل الموافقات';
$_['info_report_settings']   = 'إعدادات التقارير والتحليلات';

// Validation Messages
$_['validation_auto_approve_limit'] = 'حد الموافقة التلقائية يجب أن يكون رقماً موجباً!';
$_['validation_start_number'] = 'رقم البداية يجب أن يكون رقماً موجباً!';
$_['validation_reorder_days'] = 'أيام إعادة الطلب يجب أن تكون رقماً موجباً!';
$_['validation_email_format'] = 'تنسيق البريد الإلكتروني غير صحيح!';

// Warning Messages
$_['warning_reset_settings'] = 'هل أنت متأكد من إعادة تعيين جميع الإعدادات للقيم الافتراضية؟';
$_['warning_import_settings'] = 'سيتم استبدال الإعدادات الحالية. هل تريد المتابعة؟';

// Configuration Groups
$_['config_group_general']   = 'الإعدادات العامة';
$_['config_group_numbering'] = 'إعدادات الترقيم';
$_['config_group_notifications'] = 'إعدادات الإشعارات';
$_['config_group_inventory'] = 'إعدادات المخزون';
$_['config_group_integration'] = 'إعدادات التكامل';
$_['config_group_approval']  = 'إعدادات الموافقة';
$_['config_group_reports']   = 'إعدادات التقارير';

// Advanced Settings
$_['entry_advanced_settings'] = 'إعدادات متقدمة';
$_['entry_debug_mode']       = 'وضع التصحيح';
$_['entry_log_level']        = 'مستوى السجل';
$_['entry_cache_enabled']    = 'تفعيل التخزين المؤقت';
$_['entry_api_enabled']      = 'تفعيل API';
$_['entry_webhook_url']      = 'رابط Webhook';

// Security Settings
$_['entry_security_settings'] = 'إعدادات الأمان';
$_['entry_require_2fa']      = 'يتطلب المصادقة الثنائية';
$_['entry_session_timeout']  = 'انتهاء صلاحية الجلسة (دقيقة)';
$_['entry_max_login_attempts'] = 'الحد الأقصى لمحاولات تسجيل الدخول';
$_['entry_password_policy']  = 'سياسة كلمة المرور';

// Backup Settings
$_['entry_backup_settings']  = 'إعدادات النسخ الاحتياطي';
$_['entry_auto_backup']      = 'النسخ الاحتياطي التلقائي';
$_['entry_backup_frequency'] = 'تكرار النسخ الاحتياطي';
$_['entry_backup_retention'] = 'مدة الاحتفاظ بالنسخ (أيام)';

// Performance Settings
$_['entry_performance_settings'] = 'إعدادات الأداء';
$_['entry_page_size']        = 'عدد العناصر في الصفحة';
$_['entry_query_timeout']    = 'انتهاء صلاحية الاستعلام (ثانية)';
$_['entry_memory_limit']     = 'حد الذاكرة (MB)';

// Email Settings
$_['entry_email_settings']   = 'إعدادات البريد الإلكتروني';
$_['entry_smtp_host']        = 'خادم SMTP';
$_['entry_smtp_port']        = 'منفذ SMTP';
$_['entry_smtp_username']    = 'اسم مستخدم SMTP';
$_['entry_smtp_password']    = 'كلمة مرور SMTP';
$_['entry_smtp_encryption']  = 'تشفير SMTP';

// File Upload Settings
$_['entry_upload_settings']  = 'إعدادات رفع الملفات';
$_['entry_max_file_size']    = 'الحد الأقصى لحجم الملف (MB)';
$_['entry_allowed_extensions'] = 'الامتدادات المسموحة';
$_['entry_upload_path']      = 'مسار رفع الملفات';

// Localization Settings
$_['entry_localization']     = 'إعدادات التوطين';
$_['entry_default_language'] = 'اللغة الافتراضية';
$_['entry_default_timezone'] = 'المنطقة الزمنية الافتراضية';
$_['entry_date_format']      = 'تنسيق التاريخ';
$_['entry_time_format']      = 'تنسيق الوقت';
$_['entry_number_format']    = 'تنسيق الأرقام';

// System Information
$_['text_system_info']       = 'معلومات النظام';
$_['text_version']           = 'الإصدار';
$_['text_database_version']  = 'إصدار قاعدة البيانات';
$_['text_php_version']       = 'إصدار PHP';
$_['text_server_info']       = 'معلومات الخادم';
$_['text_last_backup']       = 'آخر نسخة احتياطية';

// Import/Export
$_['text_export_settings']   = 'تصدير الإعدادات';
$_['text_import_settings']   = 'استيراد الإعدادات';
$_['text_select_file']       = 'اختر ملف';
$_['text_export_format']     = 'تنسيق التصدير';
$_['text_json_format']       = 'JSON';
$_['text_xml_format']        = 'XML';
$_['text_csv_format']        = 'CSV';

// Maintenance
$_['text_maintenance']       = 'الصيانة';
$_['text_clear_cache']       = 'مسح التخزين المؤقت';
$_['text_rebuild_index']     = 'إعادة بناء الفهارس';
$_['text_optimize_database'] = 'تحسين قاعدة البيانات';
$_['text_check_updates']     = 'فحص التحديثات';

// Status
$_['text_status_active']     = 'نشط';
$_['text_status_inactive']   = 'غير نشط';
$_['text_status_pending']    = 'معلق';
$_['text_status_error']      = 'خطأ';

// Actions
$_['action_save']            = 'حفظ';
$_['action_reset']           = 'إعادة تعيين';
$_['action_export']          = 'تصدير';
$_['action_import']          = 'استيراد';
$_['action_test']            = 'اختبار';
$_['action_backup']          = 'نسخ احتياطي';
$_['action_restore']         = 'استعادة';

// Advanced Settings
$_['tab_advanced']           = 'إعدادات متقدمة';
$_['tab_maintenance']        = 'الصيانة';
$_['entry_debug_mode']       = 'وضع التصحيح';
$_['entry_log_level']        = 'مستوى السجل';
$_['entry_cache_enabled']    = 'تفعيل التخزين المؤقت';
$_['entry_api_enabled']      = 'تفعيل API';
$_['entry_webhook_url']      = 'رابط Webhook';
$_['entry_page_size']        = 'عدد العناصر في الصفحة';
$_['entry_query_timeout']    = 'انتهاء صلاحية الاستعلام (ثانية)';

// Help Text for Advanced Settings
$_['help_debug_mode']        = 'تفعيل وضع التصحيح لعرض معلومات إضافية للمطورين';
$_['help_log_level']         = 'مستوى تفصيل السجلات المحفوظة';
$_['help_cache_enabled']     = 'تفعيل التخزين المؤقت لتحسين الأداء';
$_['help_api_enabled']       = 'تفعيل واجهة برمجة التطبيقات للتكامل الخارجي';
$_['help_webhook_url']       = 'رابط استقبال الإشعارات التلقائية';
$_['help_page_size']         = 'عدد العناصر المعروضة في كل صفحة';
$_['help_query_timeout']     = 'الحد الأقصى لوقت تنفيذ الاستعلامات';

// Log Levels
$_['text_error']             = 'خطأ';
$_['text_warning']           = 'تحذير';
$_['text_info']              = 'معلومات';
$_['text_debug']             = 'تصحيح';

// Maintenance
$_['text_system_statistics'] = 'إحصائيات النظام';
$_['text_maintenance_actions'] = 'إجراءات الصيانة';
$_['text_backup_management'] = 'إدارة النسخ الاحتياطية';
$_['text_create_backup']     = 'إنشاء نسخة احتياطية';
$_['text_test_email']        = 'اختبار البريد الإلكتروني';
$_['text_loading']           = 'جاري التحميل...';
$_['text_processing']        = 'جاري المعالجة...';

// Statistics
$_['text_total_orders']      = 'إجمالي الطلبات';
$_['text_pending_orders']    = 'الطلبات المعلقة';
$_['text_approved_orders']   = 'الطلبات المعتمدة';
$_['text_active_suppliers']  = 'الموردين النشطين';
$_['text_active_products']   = 'المنتجات النشطة';
$_['text_total_value']       = 'القيمة الإجمالية';

// Columns
$_['column_filename']        = 'اسم الملف';
$_['column_size']            = 'الحجم';
$_['column_date']            = 'التاريخ';
$_['column_action']          = 'الإجراء';

// Buttons
$_['button_refresh']         = 'تحديث';

// Confirmation Messages
$_['text_confirm_clear_cache'] = 'هل أنت متأكد من مسح التخزين المؤقت؟';
$_['text_confirm_optimize_db'] = 'هل أنت متأكد من تحسين قاعدة البيانات؟';
$_['text_confirm_reset']     = 'هل أنت متأكد من إعادة تعيين جميع الإعدادات؟';

// Success Messages for Maintenance
$_['text_cache_cleared']     = 'تم مسح التخزين المؤقت بنجاح!';
$_['text_database_optimized'] = 'تم تحسين قاعدة البيانات بنجاح!';

// Error Messages for Maintenance
$_['error_cache_clear']      = 'فشل في مسح التخزين المؤقت!';
$_['error_database_optimization'] = 'فشل في تحسين قاعدة البيانات!';
$_['error_filename_required'] = 'اسم الملف مطلوب!';
$_['error_invalid_email']    = 'عنوان البريد الإلكتروني غير صحيح!';
$_['error_page_size']        = 'حجم الصفحة يجب أن يكون بين 1 و 1000!';
$_['error_query_timeout']    = 'انتهاء صلاحية الاستعلام يجب أن يكون بين 1 و 300 ثانية!';

// AJAX Messages
$_['text_ajax_error']        = 'حدث خطأ أثناء الاتصال بالخادم';
$_['text_enter_test_email']  = 'أدخل عنوان البريد الإلكتروني للاختبار:';
$_['text_invalid_email']     = 'عنوان البريد الإلكتروني غير صحيح';
$_['text_error_loading_stats'] = 'خطأ في تحميل الإحصائيات';

// Additional Settings
$_['entry_default_payment_term'] = 'شروط الدفع الافتراضية';
$_['entry_default_supplier'] = 'المورد الافتراضي';
$_['entry_auto_approval_limit'] = 'حد الموافقة التلقائية';
$_['entry_require_approval'] = 'يتطلب موافقة';
$_['entry_allow_partial_receipt'] = 'السماح بالاستلام الجزئي';
$_['entry_auto_create_journal'] = 'إنشاء قيود محاسبية تلقائياً';
$_['entry_default_warehouse'] = 'المستودع الافتراضي';

// Help Text for General Settings
$_['help_default_currency']  = 'العملة المستخدمة افتراضياً في أوامر الشراء';
$_['help_default_payment_term'] = 'عدد الأيام الافتراضية لشروط الدفع';
$_['help_default_supplier']  = 'المورد المحدد افتراضياً عند إنشاء أوامر شراء جديدة';
$_['help_auto_approval_limit'] = 'المبلغ الأقصى الذي يمكن الموافقة عليه تلقائياً';
$_['help_require_approval']  = 'هل تتطلب جميع أوامر الشراء موافقة قبل التنفيذ؟';
$_['help_allow_partial_receipt'] = 'السماح باستلام جزء من البضائع المطلوبة';
$_['help_auto_create_journal'] = 'إنشاء قيود محاسبية تلقائياً عند اعتماد أوامر الشراء';
$_['help_default_warehouse'] = 'المستودع المحدد افتراضياً لاستلام البضائع';

// Numbering Settings Help
$_['help_order_suffix']      = 'اللاحقة المستخدمة في ترقيم أوامر الشراء';
$_['help_order_next_number'] = 'الرقم التالي الذي سيستخدم في ترقيم أوامر الشراء';
$_['help_quotation_prefix']  = 'البادئة المستخدمة في ترقيم عروض الأسعار';
$_['help_quotation_suffix']  = 'اللاحقة المستخدمة في ترقيم عروض الأسعار';
$_['help_quotation_next_number'] = 'الرقم التالي الذي سيستخدم في ترقيم عروض الأسعار';
$_['help_receipt_prefix']    = 'البادئة المستخدمة في ترقيم إيصالات الاستلام';
$_['help_receipt_suffix']    = 'اللاحقة المستخدمة في ترقيم إيصالات الاستلام';
$_['help_receipt_next_number'] = 'الرقم التالي الذي سيستخدم في ترقيم إيصالات الاستلام';

// Notification Settings Help
$_['help_email_notifications'] = 'إرسال إشعارات بالبريد الإلكتروني للأحداث المهمة';
$_['help_sms_notifications'] = 'إرسال إشعارات نصية للأحداث المهمة';
$_['help_approval_notifications'] = 'إرسال إشعارات عند الحاجة للموافقة';
$_['help_receipt_notifications'] = 'إرسال إشعارات عند استلام البضائع';
$_['help_overdue_notifications'] = 'إرسال إشعارات للطلبات المتأخرة';

// Inventory Settings Help
$_['help_inventory_method']  = 'طريقة تقييم المخزون المستخدمة في حساب التكلفة';
$_['help_cost_calculation']  = 'طريقة حساب تكلفة المنتجات';
$_['help_auto_update_cost']  = 'تحديث تكلفة المنتجات تلقائياً عند الشراء';
$_['help_allow_negative_stock'] = 'السماح بالمخزون السالب';
$_['help_track_serial_numbers'] = 'تتبع الأرقام التسلسلية للمنتجات';
$_['help_track_batch_numbers'] = 'تتبع أرقام الدفعات للمنتجات';

// Integration Settings Help
$_['help_accounting_integration'] = 'تفعيل التكامل مع النظام المحاسبي';
$_['help_default_expense_account'] = 'حساب المصروفات الافتراضي للمشتريات';
$_['help_default_payable_account'] = 'حساب الدائنين الافتراضي للموردين';
$_['help_default_tax_account'] = 'حساب الضرائب الافتراضي';
$_['help_auto_post_journals'] = 'ترحيل القيود المحاسبية تلقائياً';

// Approval Settings Help
$_['help_approval_workflow'] = 'تفعيل سير عمل الموافقات';
$_['help_approval_levels']   = 'عدد مستويات الموافقة المطلوبة';

// Report Settings Help
$_['help_report_period']     = 'الفترة الافتراضية لعرض التقارير';
$_['help_report_currency']   = 'العملة المستخدمة في التقارير';
$_['help_report_grouping']   = 'طريقة تجميع البيانات في التقارير';
$_['help_report_format']     = 'تنسيق التقارير الافتراضي';

// Additional Text
$_['text_select']            = 'اختر...';
$_['text_one_level']         = 'مستوى واحد';
$_['text_two_levels']        = 'مستويان';
$_['text_three_levels']      = 'ثلاثة مستويات';
$_['text_by_supplier']       = 'حسب المورد';
$_['text_by_category']       = 'حسب الفئة';
$_['text_by_product']        = 'حسب المنتج';
$_['text_by_warehouse']      = 'حسب المستودع';
$_['text_standard_cost']     = 'التكلفة المعيارية';
$_['text_moving_average']    = 'المتوسط المتحرك';

// Import/Export
$_['text_import_settings']   = 'استيراد الإعدادات';
$_['entry_import_file']      = 'ملف الاستيراد';
$_['help_import_file']       = 'اختر ملف JSON يحتوي على الإعدادات المراد استيرادها';
?>
