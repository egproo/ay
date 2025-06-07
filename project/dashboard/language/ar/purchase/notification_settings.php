<?php
/**
 * AYM ERP - Purchase Notification Settings Language File (Arabic)
 * 
 * @author AYM ERP Development Team
 * @copyright 2024 AYM ERP
 * @license Commercial License
 * @version 1.0.0
 * @link https://aym-erp.com
 */

// Heading
$_['heading_title']          = 'إعدادات إشعارات المشتريات';

// Text
$_['text_success']           = 'تم: تم تحديث إعدادات الإشعارات بنجاح!';
$_['text_success_templates'] = 'تم: تم حفظ قوالب الإشعارات بنجاح!';
$_['text_list']              = 'قائمة الإعدادات';
$_['text_edit']              = 'تعديل الإعدادات';
$_['text_templates']         = 'قوالب الإشعارات';
$_['text_logs']              = 'سجل الإشعارات';
$_['text_analytics']         = 'تحليلات الإشعارات';
$_['text_test']              = 'اختبار الإشعارات';
$_['text_preview']           = 'معاينة القالب';
$_['text_enabled']           = 'مفعل';
$_['text_disabled']          = 'معطل';
$_['text_email']             = 'بريد إلكتروني';
$_['text_sms']               = 'رسائل نصية';
$_['text_push']              = 'إشعارات فورية';
$_['text_internal']          = 'إشعارات داخلية';
$_['text_test_success']      = 'تم إرسال الإشعار التجريبي بنجاح!';
$_['text_hourly']            = 'كل ساعة';
$_['text_daily']             = 'يومياً';
$_['text_weekly']            = 'أسبوعياً';
$_['text_monthly']           = 'شهرياً';

// Tab
$_['tab_general']            = 'الإعدادات العامة';
$_['tab_email']              = 'إعدادات البريد الإلكتروني';
$_['tab_sms']                = 'إعدادات الرسائل النصية';
$_['tab_push']               = 'إعدادات الإشعارات الفورية';
$_['tab_events']             = 'أحداث الإشعارات';
$_['tab_templates']          = 'قوالب الإشعارات';
$_['tab_rules']              = 'قواعد الإشعارات';
$_['tab_escalation']         = 'التصعيد';
$_['tab_digest']             = 'الملخص الدوري';
$_['tab_advanced']           = 'إعدادات متقدمة';

// Entry
$_['entry_notification_enabled'] = 'تفعيل نظام الإشعارات';
$_['entry_email_enabled']    = 'تفعيل إشعارات البريد الإلكتروني';
$_['entry_sms_enabled']      = 'تفعيل الرسائل النصية';
$_['entry_push_enabled']     = 'تفعيل الإشعارات الفورية';
$_['entry_internal_enabled'] = 'تفعيل الإشعارات الداخلية';
$_['entry_email_from_name']  = 'اسم المرسل';
$_['entry_email_from_address'] = 'عنوان البريد الإلكتروني';
$_['entry_email_reply_to']   = 'الرد إلى';
$_['entry_sms_provider']     = 'مزود الرسائل النصية';
$_['entry_sms_api_key']      = 'مفتاح API';
$_['entry_sms_api_secret']   = 'كلمة سر API';
$_['entry_sms_from_number']  = 'رقم المرسل';
$_['entry_push_provider']    = 'مزود الإشعارات الفورية';
$_['entry_push_api_key']     = 'مفتاح API';
$_['entry_push_app_id']      = 'معرف التطبيق';
$_['entry_escalation_enabled'] = 'تفعيل التصعيد';
$_['entry_digest_enabled']   = 'تفعيل الملخص الدوري';
$_['entry_digest_frequency'] = 'تكرار الملخص';
$_['entry_digest_time']      = 'وقت الإرسال';

// Event Types
$_['entry_event_name']       = 'اسم الحدث';
$_['entry_event_type']       = 'نوع الحدث';
$_['entry_event_description'] = 'وصف الحدث';
$_['entry_trigger_conditions'] = 'شروط التفعيل';
$_['entry_delivery_methods'] = 'طرق التوصيل';
$_['entry_recipients']       = 'المستقبلون';
$_['entry_template']         = 'القالب';
$_['entry_priority']         = 'الأولوية';
$_['entry_delay_minutes']    = 'تأخير (دقائق)';
$_['entry_retry_attempts']   = 'محاولات الإعادة';

// Template Fields
$_['entry_template_name']    = 'اسم القالب';
$_['entry_template_subject'] = 'موضوع الرسالة';
$_['entry_template_content'] = 'محتوى الرسالة';
$_['entry_template_content_html'] = 'محتوى HTML';
$_['entry_template_variables'] = 'المتغيرات المتاحة';

// Rules
$_['entry_rule_name']        = 'اسم القاعدة';
$_['entry_rule_description'] = 'وصف القاعدة';
$_['entry_rule_conditions']  = 'شروط القاعدة';
$_['entry_rule_actions']     = 'إجراءات القاعدة';

// Escalation
$_['entry_escalation_level'] = 'مستوى التصعيد';
$_['entry_escalation_to']    = 'التصعيد إلى';
$_['entry_trigger_after_hours'] = 'التفعيل بعد (ساعات)';
$_['entry_max_escalations']  = 'الحد الأقصى للتصعيد';
$_['entry_escalation_message'] = 'رسالة التصعيد';

// Test
$_['entry_test_type']        = 'نوع الإشعار';
$_['entry_test_method']      = 'طريقة التوصيل';
$_['entry_test_recipient']   = 'المستقبل';
$_['entry_test_message']     = 'الرسالة التجريبية';

// Column
$_['column_event_name']      = 'اسم الحدث';
$_['column_event_type']      = 'نوع الحدث';
$_['column_delivery_methods'] = 'طرق التوصيل';
$_['column_recipients']      = 'المستقبلون';
$_['column_template']        = 'القالب';
$_['column_priority']        = 'الأولوية';
$_['column_status']          = 'الحالة';
$_['column_template_name']   = 'اسم القالب';
$_['column_template_type']   = 'نوع القالب';
$_['column_rule_name']       = 'اسم القاعدة';
$_['column_escalation_level'] = 'مستوى التصعيد';
$_['column_notification_type'] = 'نوع الإشعار';
$_['column_delivery_method'] = 'طريقة التوصيل';
$_['column_recipient']       = 'المستقبل';
$_['column_subject']         = 'الموضوع';
$_['column_date_sent']       = 'تاريخ الإرسال';
$_['column_action']          = 'إجراء';

// Button
$_['button_add_event']       = 'إضافة حدث';
$_['button_add_template']    = 'إضافة قالب';
$_['button_add_rule']        = 'إضافة قاعدة';
$_['button_add_escalation']  = 'إضافة تصعيد';
$_['button_test_notification'] = 'اختبار الإشعار';
$_['button_preview_template'] = 'معاينة القالب';
$_['button_send_test']       = 'إرسال تجريبي';
$_['button_view_logs']       = 'عرض السجل';
$_['button_view_analytics']  = 'عرض التحليلات';
$_['button_export_settings'] = 'تصدير الإعدادات';
$_['button_import_settings'] = 'استيراد الإعدادات';

// Help
$_['help_notification_enabled'] = 'تفعيل أو إلغاء تفعيل نظام الإشعارات بالكامل';
$_['help_email_enabled']     = 'تفعيل إرسال الإشعارات عبر البريد الإلكتروني';
$_['help_sms_enabled']       = 'تفعيل إرسال الإشعارات عبر الرسائل النصية';
$_['help_push_enabled']      = 'تفعيل الإشعارات الفورية للتطبيقات المحمولة';
$_['help_internal_enabled']  = 'تفعيل الإشعارات الداخلية في النظام';
$_['help_email_from_name']   = 'الاسم الذي سيظهر كمرسل في رسائل البريد الإلكتروني';
$_['help_email_from_address'] = 'عنوان البريد الإلكتروني المرسل';
$_['help_sms_provider']      = 'اختيار مزود خدمة الرسائل النصية';
$_['help_push_provider']     = 'اختيار مزود خدمة الإشعارات الفورية';
$_['help_escalation_enabled'] = 'تفعيل تصعيد الإشعارات في حالة عدم الاستجابة';
$_['help_digest_enabled']    = 'تفعيل إرسال ملخص دوري للإشعارات';
$_['help_digest_frequency']  = 'تكرار إرسال الملخص الدوري';

// Error
$_['error_permission']       = 'تحذير: ليس لديك صلاحية تعديل إعدادات الإشعارات!';
$_['error_email_from_address'] = 'عنوان البريد الإلكتروني المرسل مطلوب وصحيح!';
$_['error_email_from_name']  = 'اسم المرسل مطلوب!';
$_['error_sms_provider']     = 'مزود الرسائل النصية مطلوب!';
$_['error_sms_api_key']      = 'مفتاح API للرسائل النصية مطلوب!';
$_['error_push_provider']    = 'مزود الإشعارات الفورية مطلوب!';
$_['error_push_api_key']     = 'مفتاح API للإشعارات الفورية مطلوب!';
$_['error_template_name']    = 'اسم القالب مطلوب!';
$_['error_template_subject'] = 'موضوع القالب مطلوب!';
$_['error_template_content'] = 'محتوى القالب مطلوب!';
$_['error_test_data']        = 'بيانات الاختبار غير مكتملة!';
$_['error_preview_data']     = 'بيانات المعاينة غير مكتملة!';

// Success
$_['success_event_added']    = 'تم إضافة الحدث بنجاح!';
$_['success_template_added'] = 'تم إضافة القالب بنجاح!';
$_['success_rule_added']     = 'تم إضافة القاعدة بنجاح!';
$_['success_escalation_added'] = 'تم إضافة التصعيد بنجاح!';
$_['success_test_sent']      = 'تم إرسال الإشعار التجريبي بنجاح!';

// Info
$_['info_no_events']         = 'لا توجد أحداث محددة';
$_['info_no_templates']      = 'لا توجد قوالب محددة';
$_['info_no_rules']          = 'لا توجد قواعد محددة';
$_['info_no_escalations']    = 'لا توجد مستويات تصعيد محددة';
$_['info_no_logs']           = 'لا توجد سجلات إشعارات';

// Event Types
$_['event_purchase_order_created'] = 'إنشاء أمر شراء';
$_['event_purchase_order_approved'] = 'اعتماد أمر شراء';
$_['event_purchase_order_rejected'] = 'رفض أمر شراء';
$_['event_purchase_order_delivered'] = 'تسليم أمر شراء';
$_['event_purchase_order_cancelled'] = 'إلغاء أمر شراء';
$_['event_supplier_payment_due'] = 'استحقاق دفعة مورد';
$_['event_budget_exceeded'] = 'تجاوز الميزانية';
$_['event_approval_timeout'] = 'انتهاء مهلة الموافقة';
$_['event_emergency_purchase'] = 'شراء طارئ';
$_['event_contract_expiry'] = 'انتهاء صلاحية العقد';

// Delivery Methods
$_['delivery_email']         = 'بريد إلكتروني';
$_['delivery_sms']           = 'رسائل نصية';
$_['delivery_push']          = 'إشعارات فورية';
$_['delivery_internal']      = 'إشعارات داخلية';
$_['delivery_webhook']       = 'Webhook';

// Priority Levels
$_['priority_low']           = 'منخفض';
$_['priority_normal']        = 'عادي';
$_['priority_high']          = 'عالي';
$_['priority_urgent']        = 'عاجل';
$_['priority_critical']      = 'حرج';

// Status
$_['status_pending']         = 'في الانتظار';
$_['status_sent']            = 'تم الإرسال';
$_['status_delivered']       = 'تم التسليم';
$_['status_failed']          = 'فشل';
$_['status_cancelled']       = 'ملغي';

// Recipients
$_['recipient_requester']    = 'طالب الشراء';
$_['recipient_approver']     = 'المعتمد';
$_['recipient_manager']      = 'المدير';
$_['recipient_finance']      = 'المالية';
$_['recipient_supplier']     = 'المورد';
$_['recipient_custom']       = 'مخصص';

// Template Variables
$_['variable_order_number']  = 'رقم الأمر';
$_['variable_order_total']   = 'إجمالي الأمر';
$_['variable_order_date']    = 'تاريخ الأمر';
$_['variable_supplier_name'] = 'اسم المورد';
$_['variable_user_name']     = 'اسم المستخدم';
$_['variable_system_name']   = 'اسم النظام';

// Analytics
$_['analytics_total_sent']   = 'إجمالي المرسل';
$_['analytics_success_rate'] = 'معدل النجاح';
$_['analytics_failed_count'] = 'عدد الفاشل';
$_['analytics_by_method']    = 'حسب الطريقة';
$_['analytics_recent_24h']   = 'آخر 24 ساعة';
$_['analytics_peak_hour']    = 'ساعة الذروة';
$_['analytics_avg_delivery_time'] = 'متوسط وقت التسليم';

// Conditions
$_['condition_equals']       = 'يساوي';
$_['condition_not_equals']   = 'لا يساوي';
$_['condition_greater']      = 'أكبر من';
$_['condition_less']         = 'أقل من';
$_['condition_contains']     = 'يحتوي على';
$_['condition_not_contains'] = 'لا يحتوي على';
$_['condition_in_list']      = 'ضمن القائمة';
$_['condition_not_in_list']  = 'ليس ضمن القائمة';

// Actions
$_['action_send_email']      = 'إرسال بريد إلكتروني';
$_['action_send_sms']        = 'إرسال رسالة نصية';
$_['action_send_push']       = 'إرسال إشعار فوري';
$_['action_create_task']     = 'إنشاء مهمة';
$_['action_escalate']        = 'تصعيد';
$_['action_log_event']       = 'تسجيل الحدث';

// Providers
$_['provider_twilio']        = 'Twilio';
$_['provider_nexmo']         = 'Nexmo/Vonage';
$_['provider_aws_sns']       = 'AWS SNS';
$_['provider_clickatell']    = 'Clickatell';
$_['provider_firebase']      = 'Firebase FCM';
$_['provider_onesignal']     = 'OneSignal';
$_['provider_pusher']        = 'Pusher';
$_['provider_custom']        = 'مخصص';

// Digest
$_['digest_subject']         = 'ملخص إشعارات المشتريات';
$_['digest_intro']           = 'ملخص أنشطة المشتريات';
$_['digest_summary']         = 'ملخص الفترة';
$_['digest_details']         = 'تفاصيل الأنشطة';

// Escalation
$_['escalation_subject']     = 'تصعيد إشعار المشتريات';
$_['escalation_message']     = 'تم تصعيد هذا الإشعار بسبب عدم الاستجابة';

// Webhook
$_['webhook_url']            = 'رابط Webhook';
$_['webhook_method']         = 'طريقة HTTP';
$_['webhook_headers']        = 'رؤوس HTTP';
$_['webhook_payload']        = 'البيانات المرسلة';

// Queue
$_['queue_enabled']          = 'تفعيل طابور الإشعارات';
$_['queue_batch_size']       = 'حجم الدفعة';
$_['queue_retry_delay']      = 'تأخير الإعادة (ثواني)';
$_['queue_max_retries']      = 'الحد الأقصى للمحاولات';

// Rate Limiting
$_['rate_limit_enabled']     = 'تفعيل تحديد المعدل';
$_['rate_limit_per_minute']  = 'الحد الأقصى في الدقيقة';
$_['rate_limit_per_hour']    = 'الحد الأقصى في الساعة';
$_['rate_limit_per_day']     = 'الحد الأقصى في اليوم';

// Blacklist
$_['blacklist_enabled']      = 'تفعيل القائمة السوداء';
$_['blacklist_emails']       = 'بريد إلكتروني محظور';
$_['blacklist_phones']       = 'أرقام محظورة';

// Whitelist
$_['whitelist_enabled']      = 'تفعيل القائمة البيضاء';
$_['whitelist_emails']       = 'بريد إلكتروني مسموح';
$_['whitelist_phones']       = 'أرقام مسموحة';

// Maintenance
$_['maintenance_cleanup_logs'] = 'تنظيف السجلات';
$_['maintenance_cleanup_days'] = 'الاحتفاظ بالسجلات (أيام)';
$_['maintenance_optimize_db'] = 'تحسين قاعدة البيانات';

// Security
$_['security_encrypt_content'] = 'تشفير المحتوى';
$_['security_sign_messages'] = 'توقيع الرسائل';
$_['security_verify_ssl']    = 'التحقق من SSL';

// Monitoring
$_['monitoring_enabled']     = 'تفعيل المراقبة';
$_['monitoring_alerts']      = 'تنبيهات المراقبة';
$_['monitoring_thresholds']  = 'عتبات التنبيه';

// Integration
$_['integration_slack']      = 'تكامل Slack';
$_['integration_teams']      = 'تكامل Microsoft Teams';
$_['integration_discord']    = 'تكامل Discord';
$_['integration_telegram']   = 'تكامل Telegram';
