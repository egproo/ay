<?php
/**
 * نظام أيم ERP: نظام إدارة موارد الشركات المتكامل للتجارة والتوزيع
 * 
 * Language File: Notification Center (Arabic)
 */

// Heading
$_['heading_title']                    = 'مركز الإشعارات';

// Text
$_['text_home']                        = 'الرئيسية';
$_['text_loading']                     = 'جاري التحميل...';
$_['text_success']                     = 'تم بنجاح';
$_['text_error']                       = 'خطأ';
$_['text_confirm_delete']              = 'هل أنت متأكد من حذف هذا الإشعار؟';
$_['text_no_notifications']            = 'لا توجد إشعارات';
$_['text_all_types']                   = 'جميع الأنواع';
$_['text_all_status']                  = 'جميع الحالات';
$_['text_all_priorities']              = 'جميع الأولويات';
$_['text_to']                          = 'إلى';
$_['text_previous']                    = 'السابق';
$_['text_next']                        = 'التالي';

// Statistics
$_['text_total_notifications']         = 'إجمالي الإشعارات';
$_['text_unread_notifications']        = 'الإشعارات غير المقروءة';
$_['text_high_priority']               = 'أولوية عالية';
$_['text_today_notifications']         = 'إشعارات اليوم';

// Status
$_['text_unread']                      = 'غير مقروء';
$_['text_read']                        = 'مقروء';
$_['text_archived']                    = 'مؤرشف';

// Priority
$_['text_low_priority']                = 'أولوية منخفضة';
$_['text_normal_priority']             = 'أولوية عادية';
$_['text_high_priority']               = 'أولوية عالية';
$_['text_urgent_priority']             = 'أولوية عاجلة';

// Actions
$_['text_mark_read']                   = 'تحديد كمقروء';
$_['text_archive']                     = 'أرشفة';
$_['text_delete']                      = 'حذف';
$_['text_compose_notification']        = 'إنشاء إشعار جديد';
$_['text_notification_preferences']   = 'تفضيلات الإشعارات';
$_['text_filters']                     = 'المرشحات';
$_['text_notifications_list']          = 'قائمة الإشعارات';

// Delivery Methods
$_['text_delivery_methods']            = 'طرق التوصيل';
$_['text_email_notifications']         = 'إشعارات البريد الإلكتروني';
$_['text_sms_notifications']           = 'إشعارات الرسائل النصية';
$_['text_desktop_notifications']       = 'إشعارات سطح المكتب';
$_['text_sound_notifications']         = 'إشعارات صوتية';
$_['text_send_email']                  = 'إرسال بريد إلكتروني';
$_['text_send_sms']                    = 'إرسال رسالة نصية';

// Notification Types
$_['text_notification_types']          = 'أنواع الإشعارات';
$_['text_system_notification']         = 'إشعار النظام';
$_['text_order_notification']          = 'إشعار الطلبات';
$_['text_inventory_notification']      = 'إشعار المخزون';
$_['text_payment_notification']        = 'إشعار المدفوعات';
$_['text_user_notification']           = 'إشعار المستخدمين';
$_['text_security_notification']       = 'إشعار الأمان';
$_['text_maintenance_notification']    = 'إشعار الصيانة';
$_['text_reminder_notification']       = 'إشعار التذكير';

// Entry
$_['entry_type']                       = 'النوع';
$_['entry_status']                     = 'الحالة';
$_['entry_priority']                   = 'الأولوية';
$_['entry_date_range']                 = 'نطاق التاريخ';
$_['entry_title']                      = 'العنوان';
$_['entry_message']                    = 'الرسالة';
$_['entry_recipients']                 = 'المستقبلون';

// Buttons
$_['button_compose']                   = 'إنشاء إشعار';
$_['button_mark_all_read']             = 'تحديد الكل كمقروء';
$_['button_preferences']               = 'التفضيلات';
$_['button_filter']                    = 'تصفية';
$_['button_clear']                     = 'مسح';
$_['button_send']                      = 'إرسال';
$_['button_save']                      = 'حفظ';
$_['button_cancel']                    = 'إلغاء';

// Help
$_['help_recipients']                  = 'اختر المستخدمين الذين تريد إرسال الإشعار إليهم';

// Success Messages
$_['text_notification_marked_read']    = 'تم تحديد الإشعار كمقروء بنجاح';
$_['text_notifications_marked_read']   = 'تم تحديد %s إشعار كمقروء';
$_['text_notification_deleted']        = 'تم حذف الإشعار بنجاح';
$_['text_notification_archived']       = 'تم أرشفة الإشعار بنجاح';
$_['text_preferences_updated']         = 'تم تحديث التفضيلات بنجاح';
$_['text_notification_sent']           = 'تم إرسال الإشعار بنجاح';

// Error Messages
$_['error_permission']                 = 'تحذير: ليس لديك صلاحية للوصول إلى مركز الإشعارات!';
$_['error_notification_not_found']     = 'الإشعار غير موجود';
$_['error_notification_id_required']   = 'معرف الإشعار مطلوب';
$_['error_preferences_update']         = 'خطأ في تحديث التفضيلات';
$_['error_invalid_request']            = 'طلب غير صحيح';
$_['error_required_fields']            = 'يرجى ملء جميع الحقول المطلوبة';
$_['error_no_recipients']              = 'يرجى اختيار مستقبل واحد على الأقل';
$_['error_notification_send']          = 'خطأ في إرسال الإشعار';
$_['error_template_not_found']         = 'القالب غير موجود';
$_['error_template_id_required']       = 'معرف القالب مطلوب';
$_['error_loading_notifications']      = 'خطأ في تحميل الإشعارات';

// Notification Templates
$_['text_template_new_order']          = 'طلب جديد';
$_['text_template_low_stock']          = 'مخزون منخفض';
$_['text_template_payment_received']   = 'دفعة مستلمة';
$_['text_template_user_login']         = 'تسجيل دخول مستخدم';
$_['text_template_system_backup']      = 'نسخة احتياطية للنظام';
$_['text_template_security_alert']     = 'تنبيه أمني';
$_['text_template_maintenance']        = 'صيانة النظام';
$_['text_template_reminder']           = 'تذكير';

// Notification Content Templates
$_['template_new_order_title']         = 'طلب جديد رقم #{order_id}';
$_['template_new_order_message']       = 'تم استلام طلب جديد من العميل {customer_name} بقيمة {total_amount}';

$_['template_low_stock_title']         = 'تنبيه: مخزون منخفض';
$_['template_low_stock_message']       = 'المنتج {product_name} وصل إلى الحد الأدنى للمخزون. الكمية المتبقية: {quantity}';

$_['template_payment_received_title']  = 'دفعة مستلمة';
$_['template_payment_received_message'] = 'تم استلام دفعة بقيمة {amount} من العميل {customer_name}';

$_['template_user_login_title']        = 'تسجيل دخول جديد';
$_['template_user_login_message']      = 'المستخدم {username} قام بتسجيل الدخول من عنوان IP: {ip_address}';

$_['template_system_backup_title']     = 'نسخة احتياطية مكتملة';
$_['template_system_backup_message']   = 'تم إنشاء النسخة الاحتياطية بنجاح. حجم الملف: {file_size}';

$_['template_security_alert_title']    = 'تنبيه أمني';
$_['template_security_alert_message']  = 'تم اكتشاف نشاط مشبوه: {alert_details}';

$_['template_maintenance_title']       = 'صيانة النظام';
$_['template_maintenance_message']     = 'سيتم إجراء صيانة للنظام في {maintenance_date}. المدة المتوقعة: {duration}';

$_['template_reminder_title']          = 'تذكير: {reminder_subject}';
$_['template_reminder_message']        = 'تذكير بـ {reminder_details} في تاريخ {due_date}';

// Advanced Features
$_['text_notification_rules']          = 'قواعد الإشعارات';
$_['text_auto_notifications']          = 'الإشعارات التلقائية';
$_['text_notification_history']        = 'تاريخ الإشعارات';
$_['text_notification_analytics']      = 'تحليلات الإشعارات';
$_['text_bulk_actions']                = 'إجراءات مجمعة';
$_['text_export_notifications']        = 'تصدير الإشعارات';
$_['text_notification_templates']      = 'قوالب الإشعارات';

// Filters and Search
$_['text_search_notifications']        = 'البحث في الإشعارات';
$_['text_filter_by_sender']            = 'تصفية حسب المرسل';
$_['text_filter_by_date']              = 'تصفية حسب التاريخ';
$_['text_advanced_search']             = 'بحث متقدم';
$_['text_saved_searches']              = 'عمليات البحث المحفوظة';

// Notification Settings
$_['text_notification_settings']       = 'إعدادات الإشعارات';
$_['text_global_settings']             = 'الإعدادات العامة';
$_['text_user_settings']               = 'إعدادات المستخدم';
$_['text_notification_frequency']      = 'تكرار الإشعارات';
$_['text_quiet_hours']                 = 'ساعات الهدوء';
$_['text_notification_grouping']       = 'تجميع الإشعارات';

// Frequency Options
$_['text_immediate']                   = 'فوري';
$_['text_hourly']                      = 'كل ساعة';
$_['text_daily']                       = 'يومي';
$_['text_weekly']                      = 'أسبوعي';
$_['text_never']                       = 'أبداً';

// Time Settings
$_['text_quiet_hours_start']           = 'بداية ساعات الهدوء';
$_['text_quiet_hours_end']             = 'نهاية ساعات الهدوء';
$_['text_timezone']                    = 'المنطقة الزمنية';

// Notification Channels
$_['text_notification_channels']       = 'قنوات الإشعارات';
$_['text_in_app_notifications']        = 'إشعارات داخل التطبيق';
$_['text_push_notifications']          = 'الإشعارات المدفوعة';
$_['text_webhook_notifications']       = 'إشعارات Webhook';

// Statistics and Reports
$_['text_notification_stats']          = 'إحصائيات الإشعارات';
$_['text_delivery_rate']               = 'معدل التوصيل';
$_['text_read_rate']                   = 'معدل القراءة';
$_['text_response_rate']               = 'معدل الاستجابة';
$_['text_most_active_users']           = 'المستخدمون الأكثر نشاطاً';
$_['text_notification_trends']         = 'اتجاهات الإشعارات';

// Integration
$_['text_email_integration']           = 'تكامل البريد الإلكتروني';
$_['text_sms_integration']             = 'تكامل الرسائل النصية';
$_['text_slack_integration']           = 'تكامل Slack';
$_['text_teams_integration']           = 'تكامل Microsoft Teams';
$_['text_webhook_integration']         = 'تكامل Webhook';

// Mobile
$_['text_mobile_notifications']        = 'إشعارات الهاتف المحمول';
$_['text_mobile_app_settings']         = 'إعدادات تطبيق الهاتف';
$_['text_push_notification_settings']  = 'إعدادات الإشعارات المدفوعة';

// Security
$_['text_notification_security']       = 'أمان الإشعارات';
$_['text_encryption_settings']         = 'إعدادات التشفير';
$_['text_access_control']              = 'التحكم في الوصول';
$_['text_audit_log']                   = 'سجل المراجعة';

// Automation
$_['text_notification_automation']     = 'أتمتة الإشعارات';
$_['text_trigger_conditions']          = 'شروط التشغيل';
$_['text_automated_responses']         = 'الردود التلقائية';
$_['text_workflow_integration']        = 'تكامل سير العمل';

// Performance
$_['text_notification_performance']    = 'أداء الإشعارات';
$_['text_delivery_optimization']       = 'تحسين التوصيل';
$_['text_queue_management']            = 'إدارة الطوابير';
$_['text_rate_limiting']               = 'تحديد المعدل';

// Troubleshooting
$_['text_troubleshooting']             = 'استكشاف الأخطاء وإصلاحها';
$_['text_delivery_issues']             = 'مشاكل التوصيل';
$_['text_notification_logs']           = 'سجلات الإشعارات';
$_['text_system_health']               = 'صحة النظام';

// API
$_['text_notification_api']            = 'واجهة برمجة تطبيقات الإشعارات';
$_['text_api_documentation']           = 'وثائق واجهة برمجة التطبيقات';
$_['text_api_keys']                    = 'مفاتيح واجهة برمجة التطبيقات';
$_['text_webhook_endpoints']           = 'نقاط نهاية Webhook';

// Compliance
$_['text_notification_compliance']     = 'امتثال الإشعارات';
$_['text_gdpr_compliance']             = 'امتثال GDPR';
$_['text_data_retention']              = 'الاحتفاظ بالبيانات';
$_['text_privacy_settings']            = 'إعدادات الخصوصية';
