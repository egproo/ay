<?php
/**
 * ملف اللغة العربية - قوالب خطط التقسيط
 * Arabic Language File - Installment Templates
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

// العناوين الرئيسية
$_['heading_title']         = 'قوالب خطط التقسيط';
$_['heading_title_add']     = 'إضافة قالب تقسيط';
$_['heading_title_edit']    = 'تعديل قالب تقسيط';
$_['heading_title_preview'] = 'معاينة قالب التقسيط';

// النصوص العامة
$_['text_success_add']      = 'تم إضافة قالب التقسيط بنجاح!';
$_['text_success_edit']     = 'تم تعديل قالب التقسيط بنجاح!';
$_['text_success_delete']   = 'تم حذف قوالب التقسيط المحددة بنجاح!';
$_['text_success_copy']     = 'تم نسخ قالب التقسيط بنجاح!';
$_['text_list']             = 'قائمة قوالب التقسيط';
$_['text_add']              = 'إضافة قالب جديد';
$_['text_edit']             = 'تعديل القالب';
$_['text_enabled']          = 'مفعل';
$_['text_disabled']         = 'معطل';
$_['text_all']              = 'الكل';
$_['text_confirm']          = 'هل أنت متأكد من الحذف؟';

// أنواع الفوائد
$_['text_no_interest']      = 'بدون فوائد';
$_['text_fixed_interest']   = 'فائدة ثابتة';
$_['text_reducing_interest'] = 'فائدة متناقصة';
$_['text_simple_interest']  = 'فائدة بسيطة';

// أعمدة الجدول
$_['column_name']           = 'اسم القالب';
$_['column_description']    = 'الوصف';
$_['column_installments']   = 'عدد الأقساط';
$_['column_interest_rate']  = 'معدل الفائدة';
$_['column_interest_type']  = 'نوع الفائدة';
$_['column_min_amount']     = 'الحد الأدنى';
$_['column_max_amount']     = 'الحد الأقصى';
$_['column_down_payment']   = 'الدفعة المقدمة';
$_['column_status']         = 'الحالة';
$_['column_date_added']     = 'تاريخ الإضافة';
$_['column_action']         = 'الإجراءات';

// حقول النموذج
$_['entry_name']            = 'اسم القالب';
$_['entry_description']     = 'الوصف';
$_['entry_installments_count'] = 'عدد الأقساط';
$_['entry_interest_rate']   = 'معدل الفائدة (%)';
$_['entry_interest_type']   = 'نوع الفائدة';
$_['entry_min_amount']      = 'الحد الأدنى للمبلغ';
$_['entry_max_amount']      = 'الحد الأقصى للمبلغ';
$_['entry_down_payment_percentage'] = 'نسبة الدفعة المقدمة (%)';
$_['entry_grace_period_days'] = 'فترة السماح (أيام)';
$_['entry_late_fee_percentage'] = 'غرامة التأخير (%)';
$_['entry_early_payment_discount'] = 'خصم السداد المبكر (%)';
$_['entry_customer_group_restriction'] = 'قيود مجموعة العملاء';
$_['entry_product_category_restriction'] = 'قيود فئة المنتجات';
$_['entry_terms_conditions'] = 'الشروط والأحكام';
$_['entry_status']          = 'الحالة';
$_['entry_sort_order']      = 'ترتيب العرض';

// مرشحات البحث
$_['entry_filter_name']     = 'اسم القالب';
$_['entry_filter_status']   = 'الحالة';
$_['entry_filter_interest_type'] = 'نوع الفائدة';

// الأزرار
$_['button_add']            = 'إضافة';
$_['button_edit']           = 'تعديل';
$_['button_delete']         = 'حذف';
$_['button_copy']           = 'نسخ';
$_['button_preview']        = 'معاينة';
$_['button_save']           = 'حفظ';
$_['button_cancel']         = 'إلغاء';
$_['button_filter']         = 'فلترة';
$_['button_clear']          = 'مسح';
$_['button_import']         = 'استيراد';
$_['button_export']         = 'تصدير';
$_['button_bulk_update']    = 'تحديث مجمع';
$_['button_refresh']        = 'تحديث';

// رسائل الخطأ
$_['error_permission']      = 'تحذير: ليس لديك صلاحية للوصول إلى قوالب التقسيط!';
$_['error_name']            = 'يجب أن يكون اسم القالب بين 1 و 255 حرف!';
$_['error_installments_count'] = 'عدد الأقساط يجب أن يكون بين 1 و 120 قسط!';
$_['error_interest_rate']   = 'معدل الفائدة يجب أن يكون بين 0 و 100%!';
$_['error_min_amount']      = 'الحد الأدنى للمبلغ مطلوب!';
$_['error_max_amount']      = 'الحد الأقصى للمبلغ يجب أن يكون أكبر من الحد الأدنى!';
$_['error_down_payment']    = 'نسبة الدفعة المقدمة يجب أن تكون بين 0 و 100%!';
$_['error_has_plans']       = 'تحذير: لا يمكن حذف هذا القالب لأنه مرتبط بخطط تقسيط موجودة!';
$_['error_not_found']       = 'القالب المطلوب غير موجود!';

// الإحصائيات
$_['text_total_templates']  = 'إجمالي القوالب';
$_['text_active_templates'] = 'القوالب المفعلة';
$_['text_most_used_template'] = 'القالب الأكثر استخداماً';
$_['text_avg_interest_rate'] = 'متوسط معدل الفائدة';

// معاينة القالب
$_['text_template_preview'] = 'معاينة القالب';
$_['text_example_calculation'] = 'مثال حسابي';
$_['text_example_amount']   = 'مبلغ المثال';
$_['text_down_payment_amount'] = 'مبلغ الدفعة المقدمة';
$_['text_financed_amount']  = 'المبلغ الممول';
$_['text_total_interest']   = 'إجمالي الفوائد';
$_['text_installment_schedule'] = 'جدول الأقساط';

// جدول الأقساط
$_['column_installment_number'] = 'رقم القسط';
$_['column_due_date']       = 'تاريخ الاستحقاق';
$_['column_installment_amount'] = 'مبلغ القسط';
$_['column_principal_amount'] = 'مبلغ الأصل';
$_['column_interest_amount'] = 'مبلغ الفائدة';
$_['column_remaining_balance'] = 'الرصيد المتبقي';

// الإعدادات المحاسبية
$_['tab_general']           = 'عام';
$_['tab_accounting']        = 'الإعدادات المحاسبية';
$_['tab_eligibility']       = 'شروط الأهلية';
$_['tab_terms']             = 'الشروط والأحكام';

$_['text_accounting_settings'] = 'إعدادات الحسابات المحاسبية';
$_['entry_sales_account']   = 'حساب المبيعات';
$_['entry_receivables_account'] = 'حساب الذمم المدينة';
$_['entry_interest_income_account'] = 'حساب إيرادات الفوائد';
$_['entry_deferred_interest_account'] = 'حساب الفوائد المؤجلة';
$_['entry_late_fee_account'] = 'حساب غرامات التأخير';
$_['entry_discount_account'] = 'حساب خصومات السداد المبكر';

// شروط الأهلية
$_['text_eligibility_criteria'] = 'شروط الأهلية';
$_['entry_criteria_type']   = 'نوع الشرط';
$_['entry_criteria_operator'] = 'المشغل';
$_['entry_criteria_value']  = 'القيمة';
$_['button_add_criteria']   = 'إضافة شرط';
$_['button_remove_criteria'] = 'حذف الشرط';

// أنواع شروط الأهلية
$_['text_criteria_credit_score'] = 'درجة الائتمان';
$_['text_criteria_income']      = 'الدخل الشهري';
$_['text_criteria_employment_duration'] = 'مدة العمل';
$_['text_criteria_age']         = 'العمر';
$_['text_criteria_previous_purchases'] = 'المشتريات السابقة';

// المشغلات
$_['text_operator_greater_than'] = 'أكبر من';
$_['text_operator_less_than']   = 'أقل من';
$_['text_operator_equal']       = 'يساوي';
$_['text_operator_between']     = 'بين';

// نصائح المساعدة
$_['help_name']             = 'اسم وصفي للقالب يساعد في التمييز بينه وبين القوالب الأخرى';
$_['help_installments_count'] = 'عدد الأقساط الشهرية (من 1 إلى 120 شهر)';
$_['help_interest_rate']    = 'معدل الفائدة السنوي كنسبة مئوية';
$_['help_interest_type']    = 'نوع حساب الفائدة: ثابتة أو متناقصة أو بسيطة';
$_['help_min_max_amount']   = 'الحد الأدنى والأقصى للمبالغ المؤهلة لهذا القالب';
$_['help_down_payment']     = 'نسبة الدفعة المقدمة المطلوبة من إجمالي المبلغ';
$_['help_grace_period']     = 'عدد الأيام المسموح بها بعد تاريخ الاستحقاق قبل تطبيق الغرامة';
$_['help_late_fee']         = 'نسبة الغرامة المطبقة على الأقساط المتأخرة';
$_['help_early_discount']   = 'نسبة الخصم الممنوح للسداد المبكر';

// رسائل التأكيد
$_['text_confirm_delete']   = 'هل أنت متأكد من حذف القوالب المحددة؟ هذا الإجراء لا يمكن التراجع عنه.';
$_['text_confirm_copy']     = 'هل تريد نسخ هذا القالب؟';

// حالات القالب
$_['text_status_active']    = 'نشط';
$_['text_status_inactive']  = 'غير نشط';
$_['text_status_draft']     = 'مسودة';

// تصدير واستيراد
$_['text_export_excel']     = 'تصدير إلى Excel';
$_['text_import_excel']     = 'استيراد من Excel';
$_['text_download_template'] = 'تحميل قالب Excel';

// إشعارات النجاح
$_['text_template_activated'] = 'تم تفعيل القالب بنجاح!';
$_['text_template_deactivated'] = 'تم إلغاء تفعيل القالب بنجاح!';
$_['text_bulk_update_success'] = 'تم تحديث %d قالب بنجاح!';

// تحذيرات
$_['warning_template_in_use'] = 'تحذير: هذا القالب مستخدم حالياً في خطط تقسيط نشطة!';
$_['warning_high_interest_rate'] = 'تحذير: معدل الفائدة مرتفع قد يؤثر على قبول العملاء!';
$_['warning_no_accounting_setup'] = 'تحذير: لم يتم إعداد الحسابات المحاسبية لهذا القالب!';
?>
