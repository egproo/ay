<?php
// العنوان والنص
$_['heading_title']             = 'محرر سير العمل المرئي';
$_['text_form']                 = 'تحرير سير العمل';
$_['text_success']              = 'تم حفظ سير العمل بنجاح!';
$_['text_saving']               = 'جاري الحفظ';
$_['text_select']               = '-- اختر --';
$_['text_active']               = 'نشط';
$_['text_inactive']             = 'غير نشط';
$_['text_archived']             = 'مؤرشف';
$_['text_properties']           = 'الخصائص';
$_['text_no_node_selected']     = 'لم يتم تحديد أي عقدة';
$_['text_triggers']             = 'المحفزات';
$_['text_actions']              = 'الإجراءات';
$_['text_flow']                 = 'التدفق';
$_['text_connections']          = 'الاتصالات';
$_['text_approval_properties']  = 'خصائص الموافقة';
$_['text_any_one']              = 'موافقة أي شخص';
$_['text_all']                  = 'موافقة الجميع';
$_['text_percentage']           = 'نسبة الموافقة';
$_['text_sequential']           = 'موافقة متسلسلة';
$_['text_select_user']          = 'اختر مستخدم';
$_['text_select_group']         = 'اختر مجموعة';
$_['text_document_approval']    = 'موافقة المستندات';
$_['text_purchase_approval']    = 'موافقة المشتريات';
$_['text_leave_request']        = 'طلب إجازة';
$_['text_expense_claim']        = 'طلب مصروفات';
$_['text_payment_approval']     = 'موافقة الدفع';
$_['text_other']                = 'أخرى';
$_['text_confirm_delete']       = 'هل أنت متأكد أنك تريد حذف هذه العقدة؟';
$_['text_confirm_reset']        = 'هل أنت متأكد أنك تريد إعادة تعيين سير العمل؟ سيتم حذف جميع العقد والاتصالات.';
$_['text_success_save']         = 'تم حفظ سير العمل بنجاح!';
$_['text_error_save']           = 'حدث خطأ أثناء حفظ سير العمل!';

// أزرار
$_['button_save']                = 'حفظ';
$_['button_cancel']              = 'إلغاء';
$_['button_zoom_in']             = 'تكبير';
$_['button_zoom_out']            = 'تصغير';
$_['button_zoom_reset']          = 'إعادة تعيين الحجم';
$_['button_undo']                = 'تراجع';
$_['button_redo']                = 'إعادة';
$_['button_delete']              = 'حذف';
$_['button_copy']                = 'نسخ';
$_['button_paste']               = 'لصق';

// علامات التبويب
$_['tab_general']                = 'عام';
$_['tab_designer']               = 'المصمم';
$_['tab_settings']               = 'الإعدادات';

// المدخلات
$_['entry_name']                 = 'اسم سير العمل';
$_['entry_description']          = 'الوصف';
$_['entry_workflow_type']        = 'نوع سير العمل';
$_['entry_department']           = 'القسم';
$_['entry_status']               = 'الحالة';
$_['entry_escalation']           = 'تفعيل التصعيد';
$_['entry_escalation_days']      = 'التصعيد بعد عدد أيام';
$_['entry_notify_creator']       = 'إشعار المنشئ';
$_['entry_step_name']            = 'اسم الخطوة';
$_['entry_approval_type']        = 'نوع الموافقة';
$_['entry_approval_percentage']  = 'نسبة الموافقة';
$_['entry_approvers']            = 'الموافقون';
$_['entry_instructions']         = 'التعليمات';
$_['entry_deadline_days']        = 'الموعد النهائي (أيام)';
$_['entry_final_step']           = 'هذه هي الخطوة النهائية';

// أخطاء
$_['error_permission']           = 'تحذير: ليس لديك صلاحية لتعديل سير العمل!';
$_['error_name']                 = 'اسم سير العمل يجب أن يكون بين 3 و 255 حرفاً!';
$_['error_workflow_data']        = 'خطأ في بيانات سير العمل!';
$_['error_workflow_data_empty']  = 'لم يتم تحديد بيانات سير العمل!';
$_['error_no_start_node']        = 'يجب أن يحتوي سير العمل على عقدة بداية واحدة على الأقل!';

// أسماء العقد
$_['node_start']                 = 'بداية';
$_['node_start_desc']            = 'نقطة بداية سير العمل';
$_['node_document_created']      = 'إنشاء مستند';
$_['node_document_created_desc'] = 'يتم تنفيذه عند إنشاء مستند جديد';
$_['node_order_status_changed']  = 'تغيير حالة الطلب';
$_['node_order_status_changed_desc'] = 'يتم تنفيذه عند تغيير حالة الطلب';
$_['node_schedule']              = 'جدولة';
$_['node_schedule_desc']         = 'يتم تنفيذه في وقت محدد أو بشكل متكرر';
$_['node_webhook']               = 'Webhook';
$_['node_webhook_desc']          = 'يتم تنفيذه عند استلام طلب HTTP من مصدر خارجي';
$_['node_approval']              = 'موافقة';
$_['node_approval_desc']         = 'خطوة موافقة من قبل مستخدم أو مجموعة';
$_['node_notification']          = 'إشعار';
$_['node_notification_desc']     = 'إرسال إشعار للمستخدمين';
$_['node_email']                 = 'بريد إلكتروني';
$_['node_email_desc']            = 'إرسال بريد إلكتروني';
$_['node_status_update']         = 'تحديث الحالة';
$_['node_status_update_desc']    = 'تحديث حالة الكائن';
$_['node_database']              = 'قاعدة البيانات';
$_['node_database_desc']         = 'تنفيذ عملية على قاعدة البيانات';
$_['node_http_request']          = 'طلب HTTP';
$_['node_http_request_desc']     = 'إرسال طلب HTTP لنظام خارجي';
$_['node_ai_processing']         = 'معالجة ذكاء اصطناعي';
$_['node_ai_processing_desc']    = 'تحليل أو معالجة البيانات باستخدام الذكاء الاصطناعي';
$_['node_ocr']                   = 'التعرف الضوئي على النص';
$_['node_ocr_desc']              = 'استخراج النص من الصور أو المستندات';
$_['node_condition']             = 'شرط';
$_['node_condition_desc']        = 'تنفيذ مسار مختلف بناءً على شرط';
$_['node_delay']                 = 'تأخير';
$_['node_delay_desc']            = 'تأخير تنفيذ سير العمل لفترة زمنية محددة';
$_['node_merge']                 = 'دمج';
$_['node_merge_desc']            = 'دمج مسارات متعددة من سير العمل';
$_['node_split']                 = 'تقسيم';
$_['node_split_desc']            = 'تقسيم سير العمل إلى مسارات متوازية'; 