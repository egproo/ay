<?php
/**
 * ملف اللغة العربية لتقرير تقييم المخزون المتطور
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

// العنوان الرئيسي
$_['heading_title'] = 'تقرير تقييم المخزون WAC';

// النصوص الأساسية
$_['text_success'] = 'تم: تم تحديث البيانات بنجاح!';
$_['text_list'] = 'تقرير تقييم المخزون';
$_['text_no_results'] = 'لا توجد نتائج!';
$_['text_confirm'] = 'هل أنت متأكد؟';
$_['text_loading'] = 'جاري التحميل...';
$_['text_all'] = 'الكل';
$_['text_none'] = '--- لا يوجد ---';
$_['text_select'] = '--- اختر ---';
$_['text_enabled'] = 'مفعل';
$_['text_disabled'] = 'معطل';
$_['text_yes'] = 'نعم';
$_['text_no'] = 'لا';
$_['text_never'] = 'لم يحدث مطلقاً';

// أعمدة الجدول
$_['column_product_name'] = 'اسم المنتج';
$_['column_model'] = 'الموديل';
$_['column_sku'] = 'رمز المنتج';
$_['column_category'] = 'التصنيف';
$_['column_manufacturer'] = 'العلامة التجارية';
$_['column_branch'] = 'الفرع/المستودع';
$_['column_branch_type'] = 'نوع الفرع';
$_['column_unit'] = 'الوحدة';
$_['column_quantity'] = 'الكمية';
$_['column_average_cost'] = 'متوسط التكلفة';
$_['column_total_value'] = 'إجمالي القيمة';
$_['column_selling_price'] = 'سعر البيع';
$_['column_total_selling_value'] = 'إجمالي قيمة البيع';
$_['column_unit_profit'] = 'ربح الوحدة';
$_['column_total_profit'] = 'إجمالي الربح';
$_['column_profit_percentage'] = 'نسبة الربح';
$_['column_stock_status'] = 'حالة المخزون';
$_['column_historical_avg_cost'] = 'متوسط التكلفة التاريخي';
$_['column_max_cost'] = 'أعلى تكلفة';
$_['column_min_cost'] = 'أقل تكلفة';
$_['column_cost_variance'] = 'تباين التكلفة';
$_['column_last_movement'] = 'آخر حركة';
$_['column_days_since_last_movement'] = 'أيام منذ آخر حركة';
$_['column_total_movements'] = 'إجمالي الحركات';
$_['column_calculated_quantity'] = 'الكمية المحسوبة';
$_['column_quantity_difference'] = 'فرق الكمية';
$_['column_action'] = 'إجراء';

// حقول الفلاتر
$_['entry_filter_product_name'] = 'اسم المنتج/الموديل/الرمز';
$_['entry_filter_category'] = 'التصنيف';
$_['entry_filter_manufacturer'] = 'العلامة التجارية';
$_['entry_filter_branch'] = 'الفرع/المستودع';
$_['entry_filter_branch_type'] = 'نوع الفرع';
$_['entry_filter_stock_status'] = 'حالة المخزون';
$_['entry_filter_min_value'] = 'الحد الأدنى للقيمة';
$_['entry_filter_max_value'] = 'الحد الأقصى للقيمة';
$_['entry_filter_min_profit_percentage'] = 'الحد الأدنى لنسبة الربح';
$_['entry_filter_max_profit_percentage'] = 'الحد الأقصى لنسبة الربح';
$_['entry_valuation_date'] = 'تاريخ التقييم';

// الأزرار
$_['button_filter'] = 'فلترة';
$_['button_clear'] = 'مسح الفلاتر';
$_['button_export_excel'] = 'تصدير Excel';
$_['button_export_pdf'] = 'تصدير PDF';
$_['button_print'] = 'طباعة';
$_['button_refresh'] = 'تحديث';
$_['button_view_movements'] = 'عرض الحركات';
$_['button_edit_product'] = 'تعديل المنتج';
$_['button_compare_dates'] = 'مقارنة التواريخ';
$_['button_valuation_history'] = 'تاريخ التقييم';

// حالات المخزون
$_['text_stock_status_normal'] = 'طبيعي';
$_['text_stock_status_low_stock'] = 'مخزون منخفض';
$_['text_stock_status_out_of_stock'] = 'نفد المخزون';
$_['text_stock_status_overstock'] = 'مخزون زائد';

// أنواع الفروع
$_['text_branch_type_store'] = 'متجر';
$_['text_branch_type_warehouse'] = 'مستودع';

// ملخص التقييم
$_['text_summary'] = 'ملخص التقييم';
$_['text_total_products'] = 'إجمالي المنتجات';
$_['text_total_branches'] = 'إجمالي الفروع';
$_['text_total_quantity'] = 'إجمالي الكمية';
$_['text_total_cost_value'] = 'إجمالي قيمة التكلفة';
$_['text_total_selling_value'] = 'إجمالي قيمة البيع';
$_['text_total_profit'] = 'إجمالي الربح';
$_['text_avg_cost'] = 'متوسط التكلفة';
$_['text_avg_selling_price'] = 'متوسط سعر البيع';
$_['text_avg_profit_percentage'] = 'متوسط نسبة الربح';
$_['text_out_of_stock_count'] = 'المنتجات النافدة';
$_['text_low_stock_count'] = 'المنتجات منخفضة المخزون';
$_['text_overstock_count'] = 'المنتجات زائدة المخزون';
$_['text_highest_value_item'] = 'أعلى قيمة منتج';
$_['text_lowest_value_item'] = 'أقل قيمة منتج';

// التقييم حسب التصنيف
$_['text_valuation_by_category'] = 'التقييم حسب التصنيف';
$_['text_valuation_by_category_desc'] = 'توزيع قيمة المخزون حسب تصنيفات المنتجات';
$_['text_percentage_of_total'] = 'نسبة من الإجمالي';

// التقييم حسب الفرع
$_['text_valuation_by_branch'] = 'التقييم حسب الفرع';
$_['text_valuation_by_branch_desc'] = 'توزيع قيمة المخزون حسب الفروع والمستودعات';

// أعلى المنتجات قيمة
$_['text_top_value_products'] = 'أعلى المنتجات قيمة';
$_['text_top_value_products_desc'] = 'المنتجات الخمسة الأعلى قيمة في المخزون';

// أكثر المنتجات ربحية
$_['text_most_profitable_products'] = 'أكثر المنتجات ربحية';
$_['text_most_profitable_products_desc'] = 'المنتجات الخمسة الأكثر ربحية حسب نسبة الربح';

// مقارنة التقييم
$_['text_comparison'] = 'مقارنة التقييم';
$_['text_date_comparison'] = 'مقارنة بين التواريخ';
$_['text_date_from'] = 'من تاريخ';
$_['text_date_to'] = 'إلى تاريخ';
$_['text_value_change'] = 'تغيير القيمة';
$_['text_value_change_percentage'] = 'نسبة تغيير القيمة';
$_['text_products_change'] = 'تغيير عدد المنتجات';

// رسائل المساعدة
$_['help_filter_product_name'] = 'البحث في اسم المنتج أو الموديل أو رمز المنتج';
$_['help_filter_category'] = 'فلترة حسب تصنيف المنتج';
$_['help_filter_manufacturer'] = 'فلترة حسب العلامة التجارية';
$_['help_filter_branch'] = 'فلترة حسب الفرع أو المستودع';
$_['help_filter_stock_status'] = 'فلترة حسب حالة المخزون';
$_['help_filter_min_value'] = 'عرض المنتجات التي قيمتها أكبر من أو تساوي هذا المبلغ';
$_['help_filter_max_value'] = 'عرض المنتجات التي قيمتها أقل من أو تساوي هذا المبلغ';
$_['help_filter_min_profit_percentage'] = 'عرض المنتجات التي نسبة ربحها أكبر من أو تساوي هذه النسبة';
$_['help_filter_max_profit_percentage'] = 'عرض المنتجات التي نسبة ربحها أقل من أو تساوي هذه النسبة';
$_['help_valuation_date'] = 'تاريخ التقييم المطلوب حساب قيمة المخزون فيه';

// رسائل الخطأ
$_['error_warning'] = 'تحذير: يرجى التحقق من النموذج بعناية للأخطاء!';
$_['error_permission'] = 'تحذير: ليس لديك صلاحية لعرض تقرير تقييم المخزون!';
$_['error_date_required'] = 'خطأ: تاريخ التقييم مطلوب!';
$_['error_invalid_date'] = 'خطأ: تاريخ التقييم غير صحيح!';

// نصوص التقارير
$_['text_report_title'] = 'تقرير تقييم المخزون';
$_['text_report_date'] = 'تاريخ التقرير';
$_['text_report_valuation_date'] = 'تاريخ التقييم';
$_['text_report_filters'] = 'الفلاتر المطبقة';
$_['text_report_summary'] = 'ملخص التقرير';
$_['text_report_details'] = 'تفاصيل التقرير';

// نصوص الطباعة
$_['text_print_title'] = 'تقرير تقييم المخزون';
$_['text_print_company'] = 'اسم الشركة';
$_['text_print_date'] = 'تاريخ الطباعة';
$_['text_print_user'] = 'طبع بواسطة';
$_['text_print_page'] = 'صفحة';
$_['text_print_of'] = 'من';

// نصوص التصدير
$_['text_export_excel_success'] = 'تم تصدير البيانات إلى Excel بنجاح';
$_['text_export_pdf_success'] = 'تم تصدير البيانات إلى PDF بنجاح';

// نصوص التحليل
$_['text_analysis'] = 'تحليل التقييم';
$_['text_valuation_analysis'] = 'تحليل تقييم المخزون';
$_['text_profitability_analysis'] = 'تحليل الربحية';
$_['text_cost_analysis'] = 'تحليل التكلفة';

// نصوص الإحصائيات
$_['text_statistics'] = 'إحصائيات التقييم';
$_['text_value_distribution'] = 'توزيع القيمة';
$_['text_profit_distribution'] = 'توزيع الربح';
$_['text_category_analysis'] = 'تحليل حسب التصنيف';
$_['text_branch_analysis'] = 'تحليل حسب الفرع';

// نصوص التنبيهات
$_['text_alerts'] = 'تنبيهات التقييم';
$_['text_high_value_alert'] = 'تنبيه: يوجد منتجات عالية القيمة';
$_['text_low_profit_alert'] = 'تنبيه: يوجد منتجات منخفضة الربحية';
$_['text_negative_profit_alert'] = 'تنبيه: يوجد منتجات بربح سالب';
$_['text_cost_variance_alert'] = 'تنبيه: تباين كبير في التكلفة';

// نصوص الإجراءات
$_['text_actions'] = 'الإجراءات المتاحة';
$_['text_price_adjustment'] = 'تعديل الأسعار';
$_['text_cost_review'] = 'مراجعة التكلفة';
$_['text_inventory_optimization'] = 'تحسين المخزون';

// نصوص متقدمة
$_['text_advanced_filters'] = 'فلاتر متقدمة';
$_['text_quick_filters'] = 'فلاتر سريعة';
$_['text_saved_filters'] = 'فلاتر محفوظة';
$_['text_custom_view'] = 'عرض مخصص';

// نصوص التفاعل
$_['text_expand_all'] = 'توسيع الكل';
$_['text_collapse_all'] = 'طي الكل';
$_['text_select_all'] = 'تحديد الكل';
$_['text_deselect_all'] = 'إلغاء تحديد الكل';

// نصوص التنسيق
$_['date_format_short'] = 'd/m/Y';
$_['date_format_long'] = 'd/m/Y H:i:s';
$_['number_format_decimal'] = '2';
$_['currency_symbol'] = 'ج.م';

// نصوص الوحدات
$_['text_unit'] = 'الوحدة';
$_['text_units'] = 'الوحدات';
$_['text_base_unit'] = 'الوحدة الأساسية';
$_['text_conversion_factor'] = 'معامل التحويل';

// نصوص الحسابات
$_['text_calculations'] = 'الحسابات';
$_['text_wac_calculation'] = 'حساب المتوسط المرجح';
$_['text_profit_calculation'] = 'حساب الربح';
$_['text_margin_calculation'] = 'حساب الهامش';

// نصوص التكامل
$_['text_integration'] = 'التكامل';
$_['text_accounting_integration'] = 'التكامل المحاسبي';
$_['text_financial_integration'] = 'التكامل المالي';
$_['text_reporting_integration'] = 'تكامل التقارير';

// نصوص الأمان
$_['text_security'] = 'الأمان';
$_['text_audit_trail'] = 'مسار المراجعة';
$_['text_user_permissions'] = 'صلاحيات المستخدم';
$_['text_data_protection'] = 'حماية البيانات';

// نصوص الأداء
$_['text_performance'] = 'الأداء';
$_['text_loading_time'] = 'وقت التحميل';
$_['text_cache_status'] = 'حالة التخزين المؤقت';
$_['text_optimization'] = 'التحسين';

// نصوص الدعم
$_['text_support'] = 'الدعم';
$_['text_help'] = 'المساعدة';
$_['text_documentation'] = 'الوثائق';
$_['text_contact_support'] = 'اتصل بالدعم';

// نصوص التحديث
$_['text_last_updated'] = 'آخر تحديث';
$_['text_auto_refresh'] = 'تحديث تلقائي';
$_['text_manual_refresh'] = 'تحديث يدوي';
$_['text_refresh_interval'] = 'فترة التحديث';

// نصوص التخصيص
$_['text_customization'] = 'التخصيص';
$_['text_column_settings'] = 'إعدادات الأعمدة';
$_['text_display_options'] = 'خيارات العرض';
$_['text_user_preferences'] = 'تفضيلات المستخدم';

// نصوص التصدير المتقدم
$_['text_export_options'] = 'خيارات التصدير';
$_['text_export_format'] = 'تنسيق التصدير';
$_['text_export_range'] = 'نطاق التصدير';
$_['text_export_columns'] = 'أعمدة التصدير';

// نصوص الإشعارات
$_['text_notifications'] = 'الإشعارات';
$_['text_email_notifications'] = 'إشعارات البريد الإلكتروني';
$_['text_sms_notifications'] = 'إشعارات الرسائل النصية';
$_['text_push_notifications'] = 'الإشعارات الفورية';

// نصوص التقارير المتقدمة
$_['text_advanced_reports'] = 'التقارير المتقدمة';
$_['text_custom_reports'] = 'التقارير المخصصة';
$_['text_scheduled_reports'] = 'التقارير المجدولة';
$_['text_report_templates'] = 'قوالب التقارير';

// نصوص التحليل المتقدم
$_['text_advanced_analytics'] = 'التحليل المتقدم';
$_['text_predictive_analytics'] = 'التحليل التنبؤي';
$_['text_trend_analysis'] = 'تحليل الاتجاهات';
$_['text_comparative_analysis'] = 'التحليل المقارن';

// نصوص الذكاء الاصطناعي
$_['text_ai_insights'] = 'رؤى الذكاء الاصطناعي';
$_['text_ai_recommendations'] = 'توصيات الذكاء الاصطناعي';
$_['text_machine_learning'] = 'التعلم الآلي';
$_['text_automated_decisions'] = 'القرارات الآلية';

// نصوص التكامل السحابي
$_['text_cloud_integration'] = 'التكامل السحابي';
$_['text_cloud_sync'] = 'المزامنة السحابية';
$_['text_cloud_backup'] = 'النسخ الاحتياطي السحابي';
$_['text_cloud_storage'] = 'التخزين السحابي';

// نصوص الجودة
$_['text_quality_control'] = 'ضبط الجودة';
$_['text_quality_assurance'] = 'ضمان الجودة';
$_['text_quality_metrics'] = 'مقاييس الجودة';
$_['text_quality_standards'] = 'معايير الجودة';

// نصوص التقييم المتقدم
$_['text_advanced_valuation'] = 'التقييم المتقدم';
$_['text_wac_method'] = 'طريقة المتوسط المرجح';
$_['text_fifo_method'] = 'طريقة الوارد أولاً صادر أولاً';
$_['text_lifo_method'] = 'طريقة الوارد أخيراً صادر أولاً';
$_['text_standard_cost'] = 'التكلفة المعيارية';
$_['text_market_value'] = 'القيمة السوقية';
$_['text_replacement_cost'] = 'تكلفة الاستبدال';

// نصوص المقارنات
$_['text_period_comparison'] = 'مقارنة الفترات';
$_['text_year_over_year'] = 'مقارنة سنوية';
$_['text_month_over_month'] = 'مقارنة شهرية';
$_['text_quarter_over_quarter'] = 'مقارنة ربع سنوية';

// نصوص التنبؤات
$_['text_forecasting'] = 'التنبؤات';
$_['text_demand_forecast'] = 'توقع الطلب';
$_['text_cost_forecast'] = 'توقع التكلفة';
$_['text_value_forecast'] = 'توقع القيمة';

// نصوص الامتثال
$_['text_compliance'] = 'الامتثال';
$_['text_regulatory_compliance'] = 'الامتثال التنظيمي';
$_['text_tax_compliance'] = 'الامتثال الضريبي';
$_['text_audit_compliance'] = 'امتثال المراجعة';
?>
