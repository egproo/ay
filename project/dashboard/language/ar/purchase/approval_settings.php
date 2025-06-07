<?php
/**
 * AYM ERP - Purchase Approval Settings Language File (Arabic)
 * 
 * @author AYM ERP Development Team
 * @copyright 2024 AYM ERP
 * @license Commercial License
 * @version 1.0.0
 * @link https://aym-erp.com
 */

// Heading
$_['heading_title']          = 'إعدادات الموافقة على المشتريات';

// Text
$_['text_success']           = 'تم: تم تحديث إعدادات الموافقة بنجاح!';
$_['text_success_workflow']  = 'تم: تم حفظ سير العمل بنجاح!';
$_['text_list']              = 'قائمة الإعدادات';
$_['text_edit']              = 'تعديل الإعدادات';
$_['text_workflow']          = 'سير العمل';
$_['text_general']           = 'عام';
$_['text_thresholds']        = 'حدود المبالغ';
$_['text_departments']       = 'قواعد الأقسام';
$_['text_categories']        = 'قواعد الفئات';
$_['text_notifications']     = 'الإشعارات';
$_['text_emergency']         = 'الموافقة الطارئة';
$_['text_test']              = 'اختبار النظام';
$_['text_import_export']     = 'استيراد/تصدير';
$_['text_enabled']           = 'مفعل';
$_['text_disabled']          = 'معطل';
$_['text_sequential']        = 'متتالي';
$_['text_parallel']          = 'متوازي';
$_['text_user']              = 'مستخدم';
$_['text_group']             = 'مجموعة';
$_['text_role']              = 'دور';
$_['text_department']        = 'قسم';
$_['text_auto']              = 'تلقائي';
$_['text_manual']            = 'يدوي';
$_['text_test_success']      = 'تم اختبار النظام بنجاح!';
$_['text_import_success']    = 'تم استيراد الإعدادات بنجاح!';

// Tab
$_['tab_general']            = 'الإعدادات العامة';
$_['tab_amount_thresholds']  = 'حدود المبالغ';
$_['tab_department_rules']   = 'قواعد الأقسام';
$_['tab_category_rules']     = 'قواعد الفئات';
$_['tab_workflow']           = 'سير العمل';
$_['tab_notifications']      = 'الإشعارات';
$_['tab_emergency']          = 'الموافقة الطارئة';
$_['tab_advanced']           = 'إعدادات متقدمة';

// Entry
$_['entry_approval_enabled'] = 'تفعيل نظام الموافقة';
$_['entry_auto_approval_enabled'] = 'الموافقة التلقائية';
$_['entry_approval_timeout_days'] = 'مهلة الموافقة (أيام)';
$_['entry_escalation_enabled'] = 'تفعيل التصعيد';
$_['entry_escalation_days']  = 'أيام التصعيد';
$_['entry_workflow_type']    = 'نوع سير العمل';
$_['entry_parallel_approval_percentage'] = 'نسبة الموافقة المتوازية (%)';
$_['entry_emergency_approval_enabled'] = 'تفعيل الموافقة الطارئة';
$_['entry_emergency_approval_roles'] = 'أدوار الموافقة الطارئة';
$_['entry_notification_enabled'] = 'تفعيل الإشعارات';
$_['entry_email_notifications'] = 'إشعارات البريد الإلكتروني';
$_['entry_sms_notifications'] = 'إشعارات الرسائل النصية';

// Amount Thresholds
$_['entry_amount']           = 'المبلغ';
$_['entry_currency']         = 'العملة';
$_['entry_approver_type']    = 'نوع المعتمد';
$_['entry_approver']         = 'المعتمد';
$_['entry_department']       = 'القسم';
$_['entry_category']         = 'الفئة';
$_['entry_sort_order']       = 'ترتيب الفرز';
$_['entry_status']           = 'الحالة';

// Department Rules
$_['entry_min_amount']       = 'الحد الأدنى للمبلغ';
$_['entry_max_amount']       = 'الحد الأقصى للمبلغ';

// Workflow
$_['entry_step_name']        = 'اسم الخطوة';
$_['entry_step_description'] = 'وصف الخطوة';
$_['entry_is_required']      = 'مطلوب';
$_['entry_timeout_hours']    = 'مهلة الانتظار (ساعات)';
$_['entry_escalation_approver'] = 'معتمد التصعيد';
$_['entry_conditions']       = 'الشروط';

// Test
$_['entry_test_amount']      = 'مبلغ الاختبار';
$_['entry_test_department']  = 'قسم الاختبار';
$_['entry_test_category']    = 'فئة الاختبار';

// Column
$_['column_amount']          = 'المبلغ';
$_['column_currency']        = 'العملة';
$_['column_approver']        = 'المعتمد';
$_['column_department']      = 'القسم';
$_['column_category']        = 'الفئة';
$_['column_sort_order']      = 'الترتيب';
$_['column_status']          = 'الحالة';
$_['column_step_name']       = 'اسم الخطوة';
$_['column_approver_type']   = 'نوع المعتمد';
$_['column_is_required']     = 'مطلوب';
$_['column_timeout']         = 'المهلة';
$_['column_action']          = 'إجراء';

// Button
$_['button_add_threshold']   = 'إضافة حد مبلغ';
$_['button_add_rule']        = 'إضافة قاعدة';
$_['button_add_step']        = 'إضافة خطوة';
$_['button_test_system']     = 'اختبار النظام';
$_['button_export_settings'] = 'تصدير الإعدادات';
$_['button_import_settings'] = 'استيراد الإعدادات';
$_['button_reset_defaults']  = 'إعادة تعيين افتراضي';
$_['button_workflow_designer'] = 'مصمم سير العمل';

// Help
$_['help_approval_enabled']  = 'تفعيل أو إلغاء تفعيل نظام الموافقة على المشتريات';
$_['help_auto_approval']     = 'الموافقة التلقائية للطلبات التي تحت حد معين';
$_['help_timeout_days']      = 'عدد الأيام قبل انتهاء صلاحية طلب الموافقة';
$_['help_escalation']        = 'تصعيد الطلب للمستوى الأعلى في حالة عدم الرد';
$_['help_workflow_type']     = 'متتالي: موافقة واحدة تلو الأخرى، متوازي: موافقات متعددة في نفس الوقت';
$_['help_parallel_percentage'] = 'النسبة المئوية المطلوبة للموافقة في النظام المتوازي';
$_['help_emergency_approval'] = 'السماح بالموافقة الطارئة لأدوار محددة';
$_['help_amount_thresholds'] = 'تحديد المعتمدين بناءً على مبلغ الطلب';
$_['help_department_rules']  = 'قواعد الموافقة الخاصة بكل قسم';
$_['help_category_rules']    = 'قواعد الموافقة الخاصة بكل فئة منتج';

// Error
$_['error_permission']       = 'تحذير: ليس لديك صلاحية تعديل إعدادات الموافقة!';
$_['error_timeout_days']     = 'مهلة الموافقة يجب أن تكون بين 1 و 365 يوم!';
$_['error_escalation_days']  = 'أيام التصعيد يجب أن تكون بين 1 و 30 يوم!';
$_['error_approval_percentage'] = 'نسبة الموافقة يجب أن تكون بين 1 و 100%!';
$_['error_threshold_amount'] = 'مبلغ الحد يجب أن يكون رقم موجب!';
$_['error_threshold_approver'] = 'يجب اختيار نوع ومعتمد للحد!';
$_['error_step_name']        = 'اسم الخطوة مطلوب!';
$_['error_step_approver']    = 'يجب اختيار معتمد للخطوة!';
$_['error_step_sort_order']  = 'ترتيب الفرز يجب أن يكون رقم موجب!';
$_['error_test_data']        = 'بيانات الاختبار غير مكتملة!';
$_['error_import_file']      = 'خطأ في رفع ملف الاستيراد!';
$_['error_import_invalid']   = 'ملف الاستيراد غير صحيح!';
$_['error_import_failed']    = 'فشل في استيراد الإعدادات';

// Success
$_['success_threshold_added'] = 'تم إضافة حد المبلغ بنجاح!';
$_['success_rule_added']     = 'تم إضافة القاعدة بنجاح!';
$_['success_step_added']     = 'تم إضافة خطوة سير العمل بنجاح!';
$_['success_settings_reset'] = 'تم إعادة تعيين الإعدادات للافتراضي!';

// Info
$_['info_no_thresholds']     = 'لا توجد حدود مبالغ محددة';
$_['info_no_rules']          = 'لا توجد قواعد محددة';
$_['info_no_steps']          = 'لا توجد خطوات سير عمل محددة';
$_['info_approval_flow']     = 'سير الموافقة المتوقع';
$_['info_test_result']       = 'نتيجة الاختبار';

// Workflow
$_['workflow_step_1']        = 'موافقة المشرف المباشر';
$_['workflow_step_2']        = 'موافقة مدير القسم';
$_['workflow_step_3']        = 'موافقة المدير المالي';
$_['workflow_step_4']        = 'موافقة المدير العام';

// Approval Types
$_['approval_type_amount']   = 'حسب المبلغ';
$_['approval_type_department'] = 'حسب القسم';
$_['approval_type_category'] = 'حسب الفئة';
$_['approval_type_workflow'] = 'سير العمل';
$_['approval_type_emergency'] = 'طارئ';

// Status
$_['status_pending']         = 'في الانتظار';
$_['status_approved']        = 'معتمد';
$_['status_rejected']        = 'مرفوض';
$_['status_escalated']       = 'مصعد';
$_['status_expired']         = 'منتهي الصلاحية';

// Priority
$_['priority_low']           = 'منخفض';
$_['priority_normal']        = 'عادي';
$_['priority_high']          = 'عالي';
$_['priority_urgent']        = 'عاجل';

// Notification
$_['notification_new_request'] = 'طلب موافقة جديد';
$_['notification_approved']  = 'تم اعتماد الطلب';
$_['notification_rejected']  = 'تم رفض الطلب';
$_['notification_escalated'] = 'تم تصعيد الطلب';
$_['notification_expired']   = 'انتهت صلاحية الطلب';

// Conditions
$_['condition_equals']       = 'يساوي';
$_['condition_not_equals']   = 'لا يساوي';
$_['condition_greater']      = 'أكبر من';
$_['condition_greater_equal'] = 'أكبر من أو يساوي';
$_['condition_less']         = 'أقل من';
$_['condition_less_equal']   = 'أقل من أو يساوي';
$_['condition_in']           = 'ضمن';
$_['condition_not_in']       = 'ليس ضمن';

// Reports
$_['report_approval_summary'] = 'ملخص الموافقات';
$_['report_pending_approvals'] = 'الموافقات المعلقة';
$_['report_approval_times']  = 'أوقات الموافقة';
$_['report_rejection_reasons'] = 'أسباب الرفض';

// Dashboard
$_['dashboard_total_rules']  = 'إجمالي القواعد';
$_['dashboard_active_approvals'] = 'الموافقات النشطة';
$_['dashboard_avg_approval_time'] = 'متوسط وقت الموافقة';
$_['dashboard_approval_rate'] = 'معدل الموافقة';

// Advanced
$_['advanced_custom_fields'] = 'حقول مخصصة';
$_['advanced_api_integration'] = 'تكامل API';
$_['advanced_audit_log']     = 'سجل المراجعة';
$_['advanced_backup_restore'] = 'نسخ احتياطي واستعادة';

// Validation
$_['validation_required']    = 'هذا الحقل مطلوب';
$_['validation_numeric']     = 'يجب أن يكون رقم';
$_['validation_positive']    = 'يجب أن يكون رقم موجب';
$_['validation_percentage']  = 'يجب أن يكون بين 0 و 100';
$_['validation_email']       = 'عنوان بريد إلكتروني غير صحيح';

// Import/Export
$_['import_file_format']     = 'تنسيق الملف: JSON';
$_['export_filename']        = 'اسم الملف';
$_['export_date_format']     = 'تنسيق التاريخ';
$_['import_overwrite']       = 'استبدال الإعدادات الحالية';
$_['import_merge']           = 'دمج مع الإعدادات الحالية';

// Security
$_['security_approval_limit'] = 'حد الموافقة الأمني';
$_['security_dual_approval'] = 'الموافقة المزدوجة';
$_['security_ip_restriction'] = 'قيود عنوان IP';
$_['security_time_restriction'] = 'قيود الوقت';

// Integration
$_['integration_erp']        = 'تكامل ERP';
$_['integration_accounting'] = 'تكامل المحاسبة';
$_['integration_hr']         = 'تكامل الموارد البشرية';
$_['integration_crm']        = 'تكامل CRM';

// Mobile
$_['mobile_approval']        = 'موافقة عبر الجوال';
$_['mobile_notification']    = 'إشعارات الجوال';
$_['mobile_signature']       = 'التوقيع الإلكتروني';

// Analytics
$_['analytics_approval_trends'] = 'اتجاهات الموافقة';
$_['analytics_bottlenecks']  = 'نقاط الاختناق';
$_['analytics_efficiency']   = 'كفاءة النظام';
$_['analytics_compliance']   = 'الامتثال';
