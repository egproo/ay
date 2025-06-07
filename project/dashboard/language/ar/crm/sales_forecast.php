<?php
/**
 * ملف اللغة العربية - توقعات المبيعات
 * Arabic Language File - Sales Forecast
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

// العناوين الرئيسية
$_['heading_title']         = 'توقعات المبيعات';
$_['heading_title_create']  = 'إنشاء توقع مبيعات';
$_['heading_title_edit']    = 'تعديل توقع المبيعات';
$_['heading_title_view']    = 'عرض توقع المبيعات';
$_['heading_title_analytics'] = 'تحليلات التوقعات';
$_['heading_title_scenarios'] = 'سيناريوهات التوقعات';

// النصوص العامة
$_['text_list']             = 'قائمة توقعات المبيعات';
$_['text_create']           = 'إنشاء توقع جديد';
$_['text_view']             = 'عرض التفاصيل';
$_['text_success_create']   = 'تم إنشاء توقع المبيعات بنجاح!';
$_['text_success_edit']     = 'تم تعديل توقع المبيعات بنجاح!';
$_['text_success_delete']   = 'تم حذف توقعات المبيعات المحددة بنجاح!';
$_['text_success_auto_generate'] = 'تم توليد %d توقع تلقائياً بنجاح!';
$_['text_home']             = 'الرئيسية';

// الفترات الزمنية
$_['text_period_daily']     = 'يومي';
$_['text_period_weekly']    = 'أسبوعي';
$_['text_period_monthly']   = 'شهري';
$_['text_period_quarterly'] = 'ربع سنوي';
$_['text_period_yearly']    = 'سنوي';

// أنواع التوقعات
$_['text_type_revenue']     = 'الإيرادات';
$_['text_type_units']       = 'الوحدات المباعة';
$_['text_type_customers']   = 'العملاء الجدد';
$_['text_type_orders']      = 'عدد الطلبات';

// طرق التوقع
$_['text_method_linear']    = 'الانحدار الخطي';
$_['text_method_moving_avg'] = 'المتوسط المتحرك';
$_['text_method_exponential'] = 'التنعيم الأسي';
$_['text_method_seasonal']  = 'التحليل الموسمي';
$_['text_method_arima']     = 'ARIMA';
$_['text_method_neural']    = 'الشبكات العصبية';

// طرق التوليد التلقائي
$_['text_auto_best']        = 'أفضل طريقة تلقائياً';
$_['text_auto_ensemble']    = 'مجموعة الطرق';
$_['text_auto_all']         = 'جميع الطرق';

// السيناريوهات
$_['text_scenario_optimistic'] = 'متفائل';
$_['text_scenario_realistic'] = 'واقعي';
$_['text_scenario_pessimistic'] = 'متشائم';
$_['text_scenario_custom']  = 'مخصص';

// عوامل التعديل
$_['text_factor_market']    = 'نمو السوق';
$_['text_factor_competition'] = 'المنافسة';
$_['text_factor_seasonality'] = 'الموسمية';
$_['text_factor_economic']  = 'الظروف الاقتصادية';
$_['text_factor_marketing'] = 'الحملات التسويقية';

// أعمدة الجدول
$_['column_forecast_id']    = 'رقم التوقع';
$_['column_period']         = 'الفترة';
$_['column_forecast_type']  = 'نوع التوقع';
$_['column_method']         = 'الطريقة';
$_['column_predicted_amount'] = 'المبلغ المتوقع';
$_['column_actual_amount']  = 'المبلغ الفعلي';
$_['column_variance']       = 'التباين';
$_['column_variance_percentage'] = 'نسبة التباين';
$_['column_accuracy']       = 'الدقة';
$_['column_confidence_level'] = 'مستوى الثقة';
$_['column_created_by']     = 'أنشأه';
$_['column_date_created']   = 'تاريخ الإنشاء';
$_['column_action']         = 'الإجراءات';

// حقول النموذج
$_['entry_period']          = 'الفترة الزمنية';
$_['entry_period_start']    = 'بداية الفترة';
$_['entry_period_end']      = 'نهاية الفترة';
$_['entry_forecast_type']   = 'نوع التوقع';
$_['entry_method']          = 'طريقة التوقع';
$_['entry_predicted_amount'] = 'المبلغ المتوقع';
$_['entry_actual_amount']   = 'المبلغ الفعلي';
$_['entry_confidence_level'] = 'مستوى الثقة';
$_['entry_notes']           = 'ملاحظات';
$_['entry_parameters']      = 'المعاملات';

// مرشحات البحث
$_['entry_filter_period']   = 'الفترة';
$_['entry_filter_type']     = 'نوع التوقع';
$_['entry_filter_method']   = 'الطريقة';
$_['entry_filter_accuracy'] = 'مستوى الدقة';
$_['entry_filter_date_from'] = 'التاريخ من';
$_['entry_filter_date_to']  = 'التاريخ إلى';

// الأزرار
$_['button_create']         = 'إنشاء توقع';
$_['button_edit']           = 'تعديل';
$_['button_delete']         = 'حذف';
$_['button_view']           = 'عرض';
$_['button_save']           = 'حفظ';
$_['button_cancel']         = 'إلغاء';
$_['button_filter']         = 'فلترة';
$_['button_clear']          = 'مسح';
$_['button_export']         = 'تصدير';
$_['button_auto_generate']  = 'توليد تلقائي';
$_['button_analytics']      = 'التحليلات';
$_['button_scenarios']      = 'السيناريوهات';
$_['button_compare']        = 'مقارنة';
$_['button_recalculate']    = 'إعادة حساب';

// رسائل الخطأ
$_['error_permission']      = 'تحذير: ليس لديك صلاحية للوصول إلى توقعات المبيعات!';
$_['error_not_found']       = 'التوقع المطلوب غير موجود!';
$_['error_period']          = 'يجب تحديد الفترة الزمنية!';
$_['error_forecast_type']   = 'يجب اختيار نوع التوقع!';
$_['error_method']          = 'يجب اختيار طريقة التوقع!';
$_['error_period_start']    = 'تاريخ بداية الفترة مطلوب!';
$_['error_period_end']      = 'تاريخ نهاية الفترة مطلوب!';
$_['error_auto_generate']   = 'حدث خطأ أثناء التوليد التلقائي!';
$_['error_insufficient_data'] = 'البيانات التاريخية غير كافية للتوقع!';

// الإحصائيات
$_['text_total_forecasts']  = 'إجمالي التوقعات';
$_['text_active_forecasts'] = 'التوقعات النشطة';
$_['text_avg_accuracy']     = 'متوسط الدقة';
$_['text_best_method']      = 'أفضل طريقة';
$_['text_next_period_prediction'] = 'توقع الفترة القادمة';
$_['text_variance_trend']   = 'اتجاه التباين';

// التحليلات
$_['text_accuracy_trends']  = 'اتجاهات الدقة';
$_['text_method_performance'] = 'أداء الطرق';
$_['text_seasonal_patterns'] = 'الأنماط الموسمية';
$_['text_variance_analysis'] = 'تحليل التباين';
$_['text_confidence_distribution'] = 'توزيع الثقة';
$_['text_forecast_vs_actual'] = 'المتوقع مقابل الفعلي';

// مؤشرات الأداء
$_['text_overall_accuracy'] = 'الدقة الإجمالية';
$_['text_best_performing_method'] = 'أفضل طريقة أداءً';
$_['text_prediction_reliability'] = 'موثوقية التوقعات';
$_['text_forecast_coverage'] = 'تغطية التوقعات';

// تفاصيل التوقع
$_['text_forecast_details'] = 'تفاصيل التوقع';
$_['text_historical_data']  = 'البيانات التاريخية';
$_['text_forecast_parameters'] = 'معاملات التوقع';
$_['text_accuracy_metrics'] = 'مقاييس الدقة';
$_['text_confidence_interval'] = 'فترة الثقة';

// المقارنات
$_['text_forecast_comparisons'] = 'مقارنات التوقعات';
$_['text_method_comparison'] = 'مقارنة الطرق';
$_['text_period_comparison'] = 'مقارنة الفترات';
$_['text_accuracy_comparison'] = 'مقارنة الدقة';

// نصائح المساعدة
$_['help_method']           = 'اختر طريقة التوقع المناسبة حسب طبيعة البيانات';
$_['help_confidence']       = 'مستوى الثقة يشير إلى موثوقية التوقع';
$_['help_accuracy']         = 'الدقة محسوبة بناءً على مقارنة التوقع بالنتائج الفعلية';
$_['help_variance']         = 'التباين يقيس الفرق بين المتوقع والفعلي';

// رسائل التأكيد
$_['text_confirm_delete']   = 'هل أنت متأكد من حذف التوقعات المحددة؟';
$_['text_confirm_recalculate'] = 'هل تريد إعادة حساب هذا التوقع؟';
$_['text_confirm_auto_generate'] = 'هل تريد توليد توقعات تلقائية؟ قد يستغرق هذا بعض الوقت.';

// تصدير واستيراد
$_['text_export_excel']     = 'تصدير إلى Excel';
$_['text_export_pdf']       = 'تصدير إلى PDF';
$_['text_export_csv']       = 'تصدير إلى CSV';
$_['text_print_report']     = 'طباعة التقرير';

// إشعارات النجاح
$_['text_forecast_calculated'] = 'تم حساب التوقع بنجاح!';
$_['text_accuracy_updated'] = 'تم تحديث دقة التوقع بنجاح!';
$_['text_comparison_generated'] = 'تم إنشاء المقارنة بنجاح!';

// تحذيرات
$_['warning_low_accuracy']  = 'تحذير: دقة هذا التوقع منخفضة!';
$_['warning_old_data']      = 'تحذير: البيانات المستخدمة قديمة!';
$_['warning_high_variance'] = 'تحذير: التباين عالي في هذا التوقع!';
$_['warning_insufficient_confidence'] = 'تحذير: مستوى الثقة منخفض!';

// معلومات إضافية
$_['text_forecast_info']    = 'معلومات التوقع';
$_['text_calculation_info'] = 'معلومات الحساب';
$_['text_data_source']      = 'مصدر البيانات';
$_['text_calculation_date'] = 'تاريخ الحساب';
$_['text_last_updated']     = 'آخر تحديث';

// تقارير
$_['text_forecast_report']  = 'تقرير التوقعات';
$_['text_accuracy_report']  = 'تقرير الدقة';
$_['text_variance_report']  = 'تقرير التباين';
$_['text_performance_report'] = 'تقرير الأداء';

// رسائل النظام
$_['text_no_forecasts']     = 'لا توجد توقعات مبيعات';
$_['text_loading']          = 'جاري التحميل...';
$_['text_calculating']      = 'جاري الحساب...';
$_['text_processing']       = 'جاري المعالجة...';
$_['text_generating']       = 'جاري التوليد...';
$_['text_please_wait']      = 'يرجى الانتظار...';

// تنسيق التاريخ والوقت
$_['date_format_short']     = 'd/m/Y';
$_['date_format_long']      = 'd/m/Y H:i:s';
$_['time_format']           = 'H:i:s';

// العملة
$_['text_currency']         = 'ج.م';
$_['text_currency_position'] = 'right';

// التصفح
$_['text_pagination']       = 'عرض %d إلى %d من %d (%d صفحات)';
$_['text_first']            = 'الأولى';
$_['text_last']             = 'الأخيرة';
$_['text_next']             = 'التالي';
$_['text_prev']             = 'السابق';

// الترتيب
$_['text_sort_by']          = 'ترتيب حسب';
$_['text_sort_asc']         = 'تصاعدي';
$_['text_sort_desc']        = 'تنازلي';

// أخرى
$_['text_select']           = 'اختر...';
$_['text_none']             = 'لا يوجد';
$_['text_yes']              = 'نعم';
$_['text_no']               = 'لا';
$_['text_enabled']          = 'مفعل';
$_['text_disabled']         = 'معطل';
$_['text_default']          = 'افتراضي';
$_['text_all']              = 'الكل';

// مستويات الدقة
$_['text_accuracy_excellent'] = 'ممتازة (90%+)';
$_['text_accuracy_good']    = 'جيدة (80-89%)';
$_['text_accuracy_fair']    = 'مقبولة (70-79%)';
$_['text_accuracy_poor']    = 'ضعيفة (<70%)';

// مستويات الثقة
$_['text_confidence_high']  = 'عالية (85%+)';
$_['text_confidence_medium'] = 'متوسطة (70-84%)';
$_['text_confidence_low']   = 'منخفضة (<70%)';

// اتجاهات التباين
$_['text_trend_improving']  = 'يتحسن';
$_['text_trend_stable']     = 'مستقر';
$_['text_trend_declining']  = 'يتراجع';

// حالات التوقع
$_['text_status_draft']     = 'مسودة';
$_['text_status_active']    = 'نشط';
$_['text_status_completed'] = 'مكتمل';
$_['text_status_archived']  = 'مؤرشف';

// أنواع البيانات
$_['text_data_historical']  = 'بيانات تاريخية';
$_['text_data_real_time']   = 'بيانات فورية';
$_['text_data_projected']   = 'بيانات متوقعة';

// خيارات التصدير
$_['text_export_all']       = 'تصدير الكل';
$_['text_export_filtered']  = 'تصدير المفلتر';
$_['text_export_selected']  = 'تصدير المحدد';
$_['text_export_summary']   = 'تصدير الملخص';
$_['text_export_detailed']  = 'تصدير مفصل';

// إعدادات التوقع
$_['text_forecast_settings'] = 'إعدادات التوقع';
$_['text_auto_update']      = 'تحديث تلقائي';
$_['text_notification_threshold'] = 'حد الإشعارات';
$_['text_data_retention']   = 'الاحتفاظ بالبيانات';

// التكامل
$_['text_sales_integration'] = 'تكامل المبيعات';
$_['text_inventory_integration'] = 'تكامل المخزون';
$_['text_crm_integration']  = 'تكامل CRM';
$_['text_analytics_integration'] = 'تكامل التحليلات';

// الأمان
$_['text_access_level']     = 'مستوى الوصول';
$_['text_view_only']        = 'عرض فقط';
$_['text_edit_allowed']     = 'تعديل مسموح';
$_['text_full_access']      = 'وصول كامل';
$_['text_admin_only']       = 'المدراء فقط';
?>
