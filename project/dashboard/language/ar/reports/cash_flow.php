<?php
/**
 * AYM ERP System: Cash Flow Report Language File (Arabic)
 *
 * ملف اللغة العربية لتقرير التدفقات النقدية - مطور بجودة عالمية
 *
 * الميزات المتقدمة:
 * - تقرير التدفقات النقدية الشامل
 * - الطريقة المباشرة وغير المباشرة
 * - تصدير PDF و Excel
 * - تحليل مالي متقدم
 * - واجهة مستخدم احترافية
 * - تكامل مع النظام المحاسبي
 *
 * @package    AYM ERP
 * @author     AYM Development Team
 * @copyright  2024 AYM ERP Systems
 * @license    Commercial License
 * @version    1.0.0
 * @since      2024-01-15
 */

// العناوين الرئيسية
$_['heading_title']                    = 'تقرير التدفقات النقدية';
$_['heading_title_form']               = 'إعداد تقرير التدفقات النقدية';
$_['heading_title_report']             = 'قائمة التدفقات النقدية';

// النصوص الأساسية
$_['text_success']                     = 'تم بنجاح: تم إنشاء تقرير التدفقات النقدية!';
$_['text_list']                        = 'قائمة التقارير';
$_['text_add']                         = 'إنشاء تقرير';
$_['text_edit']                        = 'تعديل التقرير';
$_['text_view']                        = 'عرض التقرير';
$_['text_confirm']                     = 'هل أنت متأكد؟';
$_['text_loading']                     = 'جاري التحميل...';
$_['text_no_results']                  = 'لا توجد بيانات للفترة المحددة!';
$_['text_select']                      = 'اختر';
$_['text_none']                        = 'لا يوجد';
$_['text_all']                         = 'الكل';

// أقسام التدفقات النقدية
$_['text_operating_activities']        = 'التدفقات النقدية من الأنشطة التشغيلية';
$_['text_investing_activities']        = 'التدفقات النقدية من الأنشطة الاستثمارية';
$_['text_financing_activities']        = 'التدفقات النقدية من الأنشطة التمويلية';

// طرق إعداد التقرير
$_['text_method_direct']               = 'الطريقة المباشرة';
$_['text_method_indirect']             = 'الطريقة غير المباشرة';

// بنود الأنشطة التشغيلية
$_['text_net_income']                  = 'صافي الدخل';
$_['text_depreciation']                = 'الإهلاك والاستنفاد';
$_['text_accounts_receivable']         = 'التغير في الذمم المدينة';
$_['text_inventory']                   = 'التغير في المخزون';
$_['text_accounts_payable']            = 'التغير في الذمم الدائنة';
$_['text_accrued_expenses']            = 'التغير في المصروفات المستحقة';
$_['text_prepaid_expenses']            = 'التغير في المصروفات المدفوعة مقدماً';
$_['text_other_operating']             = 'تغيرات أخرى في رأس المال العامل';

// بنود الأنشطة الاستثمارية
$_['text_purchase_equipment']          = 'شراء معدات وأصول ثابتة';
$_['text_sale_equipment']              = 'بيع معدات وأصول ثابتة';
$_['text_purchase_investments']        = 'شراء استثمارات';
$_['text_sale_investments']            = 'بيع استثمارات';
$_['text_loans_made']                  = 'قروض ممنوحة';
$_['text_loans_collected']             = 'تحصيل قروض';

// بنود الأنشطة التمويلية
$_['text_issue_stock']                 = 'إصدار أسهم';
$_['text_repurchase_stock']            = 'إعادة شراء أسهم';
$_['text_borrow_funds']                = 'اقتراض أموال';
$_['text_repay_debt']                  = 'سداد ديون';
$_['text_pay_dividends']               = 'دفع أرباح';
$_['text_capital_contributions']       = 'مساهمات رأس المال';

// الإجماليات
$_['text_net_operating_cash']          = 'صافي التدفق النقدي من الأنشطة التشغيلية';
$_['text_net_investing_cash']          = 'صافي التدفق النقدي من الأنشطة الاستثمارية';
$_['text_net_financing_cash']          = 'صافي التدفق النقدي من الأنشطة التمويلية';
$_['text_net_change_cash']             = 'صافي التغير في النقدية';
$_['text_opening_cash']                = 'النقدية في بداية الفترة';
$_['text_closing_cash']                = 'النقدية في نهاية الفترة';

// عناوين الأعمدة
$_['column_description']               = 'البيان';
$_['column_amount']                    = 'المبلغ';
$_['column_percentage']                = 'النسبة %';
$_['column_period']                    = 'الفترة';

// حقول الإدخال
$_['entry_date_start']                 = 'من تاريخ';
$_['entry_date_end']                   = 'إلى تاريخ';
$_['entry_method']                     = 'طريقة الإعداد';
$_['entry_currency']                   = 'العملة';
$_['entry_branch']                     = 'الفرع';
$_['entry_include_budget']             = 'تضمين الموازنة';
$_['entry_comparison_period']          = 'فترة المقارنة';

// الأزرار
$_['button_generate']                  = 'إنشاء التقرير';
$_['button_export_pdf']                = 'تصدير PDF';
$_['button_export_excel']              = 'تصدير Excel';
$_['button_print']                     = 'طباعة';
$_['button_email']                     = 'إرسال بالبريد';
$_['button_save_template']             = 'حفظ كقالب';
$_['button_reset']                     = 'إعادة تعيين';
$_['button_back']                      = 'رجوع';
$_['button_refresh']                   = 'تحديث';

// التبويبات
$_['tab_parameters']                   = 'معاملات التقرير';
$_['tab_operating']                    = 'الأنشطة التشغيلية';
$_['tab_investing']                    = 'الأنشطة الاستثمارية';
$_['tab_financing']                    = 'الأنشطة التمويلية';
$_['tab_summary']                      = 'الملخص';
$_['tab_analysis']                     = 'التحليل';

// رسائل الخطأ
$_['error_permission']                 = 'تحذير: ليس لديك صلاحية للوصول إلى تقارير التدفقات النقدية!';
$_['error_date_start']                 = 'خطأ: تاريخ البداية مطلوب!';
$_['error_date_end']                   = 'خطأ: تاريخ النهاية مطلوب!';
$_['error_date_range']                 = 'خطأ: تاريخ النهاية يجب أن يكون بعد تاريخ البداية!';
$_['error_no_data']                    = 'خطأ: لا توجد بيانات للفترة المحددة!';
$_['error_invalid_method']             = 'خطأ: طريقة إعداد التقرير غير صحيحة!';
$_['error_export_failed']              = 'خطأ: فشل في تصدير التقرير!';

// رسائل النجاح
$_['success_generated']                = 'تم إنشاء تقرير التدفقات النقدية بنجاح!';
$_['success_exported']                 = 'تم تصدير التقرير بنجاح!';
$_['success_emailed']                  = 'تم إرسال التقرير بالبريد الإلكتروني بنجاح!';
$_['success_saved']                    = 'تم حفظ قالب التقرير بنجاح!';

// رسائل التحذير
$_['warning_large_period']             = 'تحذير: الفترة المحددة طويلة، قد يستغرق التقرير وقتاً أطول!';
$_['warning_no_transactions']          = 'تحذير: لا توجد معاملات في الفترة المحددة!';
$_['warning_incomplete_data']          = 'تحذير: بعض البيانات قد تكون غير مكتملة!';

// المساعدة والنصائح
$_['help_method']                      = 'الطريقة المباشرة تظهر التدفقات النقدية الفعلية، بينما غير المباشرة تبدأ من صافي الدخل';
$_['help_date_range']                  = 'اختر الفترة المحاسبية المطلوب إنشاء التقرير لها';
$_['help_operating_activities']        = 'الأنشطة التشغيلية تشمل العمليات الأساسية للشركة';
$_['help_investing_activities']        = 'الأنشطة الاستثمارية تشمل شراء وبيع الأصول طويلة الأجل';
$_['help_financing_activities']        = 'الأنشطة التمويلية تشمل التمويل من المالكين والدائنين';

// التحليل المالي
$_['text_analysis']                    = 'التحليل المالي';
$_['text_cash_ratio']                  = 'نسبة السيولة النقدية';
$_['text_operating_cash_ratio']        = 'نسبة التدفق النقدي التشغيلي';
$_['text_free_cash_flow']              = 'التدفق النقدي الحر';
$_['text_cash_coverage_ratio']         = 'نسبة تغطية النقدية';
$_['text_quality_earnings']            = 'جودة الأرباح';

// المؤشرات المالية
$_['text_positive_flow']               = 'تدفق إيجابي';
$_['text_negative_flow']               = 'تدفق سلبي';
$_['text_stable_flow']                 = 'تدفق مستقر';
$_['text_volatile_flow']               = 'تدفق متقلب';

// التنسيق والعرض
$_['date_format_short']                = 'd/m/Y';
$_['date_format_long']                 = 'd/m/Y H:i:s';
$_['currency_format']                  = '%s %s';
$_['number_format']                    = '%s';
$_['percentage_format']                = '%s%%';

// التصدير والطباعة
$_['text_export_options']              = 'خيارات التصدير';
$_['text_pdf_settings']                = 'إعدادات PDF';
$_['text_excel_settings']              = 'إعدادات Excel';
$_['text_email_settings']              = 'إعدادات البريد الإلكتروني';

// القوالب
$_['text_templates']                   = 'القوالب';
$_['text_save_template']               = 'حفظ كقالب';
$_['text_load_template']               = 'تحميل قالب';
$_['text_template_name']               = 'اسم القالب';
$_['text_default_template']            = 'القالب الافتراضي';

// مسار التنقل
$_['text_home']                        = 'الرئيسية';
$_['text_reports']                     = 'التقارير';
$_['text_financial_reports']           = 'التقارير المالية';
$_['text_cash_flow']                   = 'التدفقات النقدية';

// الفترات المحاسبية
$_['text_current_month']               = 'الشهر الحالي';
$_['text_current_quarter']             = 'الربع الحالي';
$_['text_current_year']                = 'السنة الحالية';
$_['text_last_month']                  = 'الشهر الماضي';
$_['text_last_quarter']                = 'الربع الماضي';
$_['text_last_year']                   = 'السنة الماضية';
$_['text_custom_period']               = 'فترة مخصصة';

// الحالات والأوضاع
$_['text_draft']                       = 'مسودة';
$_['text_final']                       = 'نهائي';
$_['text_approved']                    = 'معتمد';
$_['text_published']                   = 'منشور';

// متنوعة
$_['text_version']                     = 'الإصدار';
$_['text_generated_by']                = 'أنشأ بواسطة';
$_['text_generated_date']              = 'تاريخ الإنشاء';
$_['text_page_number']                 = 'رقم الصفحة';
$_['text_total_pages']                 = 'إجمالي الصفحات';
$_['text_confidential']                = 'سري';

// الأمان والصلاحيات
$_['text_access_level']                = 'مستوى الوصول';
$_['text_view_only']                   = 'عرض فقط';
$_['text_full_access']                 = 'وصول كامل';
$_['text_restricted']                  = 'مقيد';

// التكامل
$_['text_integration']                 = 'التكامل';
$_['text_accounting_system']           = 'النظام المحاسبي';
$_['text_budget_system']               = 'نظام الموازنات';
$_['text_forecasting']                 = 'التنبؤ';

?>
