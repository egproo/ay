<?php
/**
 * AYM ERP System: Accounting Journal Language File (Arabic)
 *
 * ملف اللغة العربية لدفتر اليومية المحاسبي - مطور بجودة عالمية
 *
 * الميزات المتقدمة:
 * - دفتر يومية شامل ومتقدم
 * - قيود محاسبية تلقائية
 * - تكامل مع جميع العمليات
 * - تقارير محاسبية متقدمة
 * - نظام فترات محاسبية
 * - مراجعة وتدقيق شاملة
 *
 * @package    AYM ERP
 * @author     AYM Development Team
 * @copyright  2024 AYM ERP Systems
 * @license    Commercial License
 * @version    1.0.0
 * @since      2024-01-15
 */

// العناوين الرئيسية
$_['heading_title']                    = 'دفتر اليومية المحاسبي';
$_['heading_title_list']               = 'قائمة القيود المحاسبية';
$_['heading_title_view']               = 'عرض القيد المحاسبي';
$_['heading_title_add']                = 'إضافة قيد محاسبي';
$_['heading_title_edit']               = 'تعديل القيد المحاسبي';

// النصوص الأساسية
$_['text_success']                     = 'تم بنجاح: تم حفظ القيد المحاسبي!';
$_['text_list']                        = 'قائمة القيود';
$_['text_add']                         = 'إضافة قيد';
$_['text_edit']                        = 'تعديل قيد';
$_['text_view']                        = 'عرض القيد';
$_['text_confirm']                     = 'هل أنت متأكد؟';
$_['text_loading']                     = 'جاري التحميل...';
$_['text_no_results']                  = 'لا توجد قيود محاسبية!';
$_['text_select']                      = 'اختر';
$_['text_none']                        = 'لا يوجد';
$_['text_yes']                         = 'نعم';
$_['text_no']                          = 'لا';
$_['text_enabled']                     = 'مفعل';
$_['text_disabled']                    = 'معطل';

// تفاصيل القيد
$_['text_journal_details']             = 'تفاصيل القيد المحاسبي';
$_['text_journal_entries']             = 'بنود القيد';
$_['text_journal_summary']             = 'ملخص القيد';
$_['text_reference_details']           = 'تفاصيل المرجع';
$_['text_period_info']                 = 'معلومات الفترة';
$_['text_audit_trail']                 = 'مسار المراجعة';

// أنواع المراجع
$_['text_inventory_movement']          = 'حركة مخزون';
$_['text_sales_order']                 = 'طلب بيع';
$_['text_purchase_order']              = 'طلب شراء';
$_['text_payment']                     = 'دفعة';
$_['text_receipt']                     = 'إيصال';
$_['text_manual_entry']                = 'قيد يدوي';
$_['text_adjustment']                  = 'تسوية';
$_['text_depreciation']                = 'إهلاك';
$_['text_accrual']                     = 'استحقاق';
$_['text_provision']                   = 'مخصص';

// حالات القيد
$_['text_status_draft']                = 'مسودة';
$_['text_status_posted']               = 'مرحل';
$_['text_status_approved']             = 'معتمد';
$_['text_status_cancelled']            = 'ملغي';
$_['text_status_reversed']             = 'معكوس';

// عناوين الأعمدة
$_['column_journal_id']                = 'رقم القيد';
$_['column_reference_type']            = 'نوع المرجع';
$_['column_reference_id']              = 'رقم المرجع';
$_['column_description']               = 'الوصف';
$_['column_date_added']                = 'تاريخ الإضافة';
$_['column_user_name']                 = 'المستخدم';
$_['column_status']                    = 'الحالة';
$_['column_action']                    = 'إجراء';
$_['column_account_code']              = 'رمز الحساب';
$_['column_account_name']              = 'اسم الحساب';
$_['column_debit']                     = 'مدين';
$_['column_credit']                    = 'دائن';
$_['column_balance']                   = 'الرصيد';
$_['column_period']                    = 'الفترة';
$_['column_total_debit']               = 'إجمالي المدين';
$_['column_total_credit']              = 'إجمالي الدائن';

// حقول الإدخال
$_['entry_reference_type']             = 'نوع المرجع';
$_['entry_reference_id']               = 'رقم المرجع';
$_['entry_description']                = 'وصف القيد';
$_['entry_date_from']                  = 'من تاريخ';
$_['entry_date_to']                    = 'إلى تاريخ';
$_['entry_period']                     = 'الفترة المحاسبية';
$_['entry_status']                     = 'الحالة';
$_['entry_account']                    = 'الحساب';
$_['entry_debit_amount']               = 'المبلغ المدين';
$_['entry_credit_amount']              = 'المبلغ الدائن';
$_['entry_notes']                      = 'ملاحظات';

// الأزرار
$_['button_filter']                    = 'فلترة';
$_['button_clear']                     = 'مسح';
$_['button_view']                      = 'عرض';
$_['button_edit']                      = 'تعديل';
$_['button_delete']                    = 'حذف';
$_['button_add']                       = 'إضافة';
$_['button_save']                      = 'حفظ';
$_['button_cancel']                    = 'إلغاء';
$_['button_back']                      = 'رجوع';
$_['button_print']                     = 'طباعة';
$_['button_export']                    = 'تصدير';
$_['button_post']                      = 'ترحيل';
$_['button_approve']                   = 'اعتماد';
$_['button_reverse']                   = 'عكس القيد';
$_['button_duplicate']                 = 'نسخ';

// التبويبات
$_['tab_general']                      = 'عام';
$_['tab_entries']                      = 'البنود';
$_['tab_reference']                    = 'المرجع';
$_['tab_audit']                        = 'المراجعة';
$_['tab_attachments']                  = 'المرفقات';

// رسائل الخطأ
$_['error_permission']                 = 'تحذير: ليس لديك صلاحية للوصول إلى دفتر اليومية!';
$_['error_not_found']                  = 'خطأ: القيد المحاسبي غير موجود!';
$_['error_journal_id']                 = 'خطأ: رقم القيد مطلوب!';
$_['error_description']                = 'خطأ: وصف القيد مطلوب!';
$_['error_reference_type']             = 'خطأ: نوع المرجع مطلوب!';
$_['error_reference_id']               = 'خطأ: رقم المرجع مطلوب!';
$_['error_period_closed']              = 'خطأ: الفترة المحاسبية مغلقة!';
$_['error_unbalanced_entry']           = 'خطأ: القيد غير متوازن! المدين لا يساوي الدائن';
$_['error_no_entries']                 = 'خطأ: يجب إضافة بند واحد على الأقل!';
$_['error_invalid_amount']             = 'خطأ: المبلغ غير صحيح!';
$_['error_account_required']           = 'خطأ: الحساب مطلوب!';
$_['error_already_posted']             = 'خطأ: القيد مرحل بالفعل!';
$_['error_cannot_edit_posted']         = 'خطأ: لا يمكن تعديل قيد مرحل!';
$_['error_cannot_delete_posted']       = 'خطأ: لا يمكن حذف قيد مرحل!';

// رسائل النجاح
$_['success_journal_added']            = 'تم إضافة القيد المحاسبي بنجاح!';
$_['success_journal_updated']          = 'تم تحديث القيد المحاسبي بنجاح!';
$_['success_journal_deleted']          = 'تم حذف القيد المحاسبي بنجاح!';
$_['success_journal_posted']           = 'تم ترحيل القيد المحاسبي بنجاح!';
$_['success_journal_approved']         = 'تم اعتماد القيد المحاسبي بنجاح!';
$_['success_journal_reversed']         = 'تم عكس القيد المحاسبي بنجاح!';
$_['success_export']                   = 'تم تصدير البيانات بنجاح!';

// رسائل التحذير
$_['warning_unbalanced']               = 'تحذير: القيد غير متوازن!';
$_['warning_period_closing']           = 'تحذير: الفترة المحاسبية قريبة من الإغلاق!';
$_['warning_large_amount']             = 'تحذير: المبلغ كبير، يرجى التأكد!';
$_['warning_duplicate_reference']      = 'تحذير: يوجد قيد آخر بنفس المرجع!';

// المساعدة والنصائح
$_['help_reference_type']              = 'نوع العملية التي أدت لإنشاء هذا القيد';
$_['help_reference_id']                = 'رقم العملية في النظام';
$_['help_description']                 = 'وصف مختصر للقيد المحاسبي';
$_['help_period']                      = 'الفترة المحاسبية التي ينتمي إليها القيد';
$_['help_debit_credit']                = 'يجب أن يكون إجمالي المدين مساوياً لإجمالي الدائن';
$_['help_posting']                     = 'الترحيل يجعل القيد نهائياً ولا يمكن تعديله';
$_['help_reversal']                    = 'عكس القيد ينشئ قيد معاكس لإلغاء التأثير';

// الإحصائيات
$_['text_statistics']                  = 'إحصائيات دفتر اليومية';
$_['text_total_journals']              = 'إجمالي القيود';
$_['text_posted_journals']             = 'القيود المرحلة';
$_['text_draft_journals']              = 'القيود المسودة';
$_['text_total_debit']                 = 'إجمالي المدين';
$_['text_total_credit']                = 'إجمالي الدائن';
$_['text_balance_check']               = 'فحص التوازن';

// التقارير
$_['text_reports']                     = 'تقارير دفتر اليومية';
$_['text_journal_report']              = 'تقرير دفتر اليومية';
$_['text_trial_balance']               = 'ميزان المراجعة';
$_['text_general_ledger']              = 'دفتر الأستاذ العام';
$_['text_account_statement']           = 'كشف حساب';

// التصدير والطباعة
$_['text_export_options']              = 'خيارات التصدير';
$_['text_export_excel']                = 'تصدير إلى Excel';
$_['text_export_pdf']                  = 'تصدير إلى PDF';
$_['text_export_csv']                  = 'تصدير إلى CSV';
$_['text_print_journal']               = 'طباعة القيد';
$_['text_print_voucher']               = 'طباعة سند';

// التنسيق والعرض
$_['date_format_short']                = 'd/m/Y';
$_['date_format_long']                 = 'd/m/Y H:i:s';
$_['currency_format']                  = '%s %s';
$_['number_format']                    = '%s';

// التصفح والترقيم
$_['text_pagination']                  = 'عرض %d إلى %d من %d (%d صفحات)';
$_['text_first']                       = 'الأولى';
$_['text_last']                        = 'الأخيرة';
$_['text_next']                        = 'التالي';
$_['text_prev']                        = 'السابق';

// مسار التنقل
$_['text_home']                        = 'الرئيسية';
$_['text_accounting']                  = 'المحاسبة';
$_['text_journal']                     = 'دفتر اليومية';

// متنوعة
$_['text_version']                     = 'الإصدار';
$_['text_created_by']                  = 'أنشأ بواسطة';
$_['text_created_date']                = 'تاريخ الإنشاء';
$_['text_modified_by']                 = 'عدل بواسطة';
$_['text_modified_date']               = 'تاريخ التعديل';
$_['text_approved_by']                 = 'اعتمد بواسطة';
$_['text_approved_date']               = 'تاريخ الاعتماد';
$_['text_posted_by']                   = 'رحل بواسطة';
$_['text_posted_date']                 = 'تاريخ الترحيل';

// الأمان والصلاحيات
$_['text_permissions']                 = 'الصلاحيات';
$_['text_view_permission']             = 'صلاحية العرض';
$_['text_add_permission']              = 'صلاحية الإضافة';
$_['text_edit_permission']             = 'صلاحية التعديل';
$_['text_delete_permission']           = 'صلاحية الحذف';
$_['text_post_permission']             = 'صلاحية الترحيل';
$_['text_approve_permission']          = 'صلاحية الاعتماد';

// التكامل مع الأنظمة الأخرى
$_['text_integration']                 = 'التكامل';
$_['text_auto_posting']                = 'الترحيل التلقائي';
$_['text_manual_posting']              = 'الترحيل اليدوي';
$_['text_batch_processing']            = 'المعالجة المجمعة';
$_['text_real_time_sync']              = 'المزامنة الفورية';

?>
