<?php
/**
 * ملف اللغة العربية لسجل حركة المخزون المتطور
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

// العنوان الرئيسي
$_['heading_title'] = 'سجل حركة المخزون (كارت الصنف)';

// النصوص الأساسية
$_['text_success'] = 'تم: تم تحديث البيانات بنجاح!';
$_['text_list'] = 'سجل حركة المخزون';
$_['text_no_results'] = 'لا توجد حركات!';
$_['text_confirm'] = 'هل أنت متأكد؟';
$_['text_loading'] = 'جاري التحميل...';
$_['text_all'] = 'الكل';
$_['text_none'] = '--- لا يوجد ---';
$_['text_select'] = '--- اختر ---';
$_['text_enabled'] = 'مفعل';
$_['text_disabled'] = 'معطل';
$_['text_yes'] = 'نعم';
$_['text_no'] = 'لا';
$_['text_with_expiry'] = 'مع تاريخ صلاحية';
$_['text_without_expiry'] = 'بدون تاريخ صلاحية';

// أعمدة الجدول
$_['column_date'] = 'التاريخ';
$_['column_product_name'] = 'اسم المنتج';
$_['column_model'] = 'الموديل';
$_['column_sku'] = 'رمز المنتج';
$_['column_category'] = 'التصنيف';
$_['column_manufacturer'] = 'العلامة التجارية';
$_['column_branch'] = 'الفرع/المستودع';
$_['column_branch_type'] = 'نوع الفرع';
$_['column_movement_type'] = 'نوع الحركة';
$_['column_reference_type'] = 'نوع المرجع';
$_['column_reference'] = 'رقم المرجع';
$_['column_lot_number'] = 'رقم الدفعة';
$_['column_expiry_date'] = 'تاريخ الصلاحية';
$_['column_unit'] = 'الوحدة';
$_['column_quantity_in'] = 'الكمية الواردة';
$_['column_quantity_out'] = 'الكمية الصادرة';
$_['column_net_quantity'] = 'صافي الكمية';
$_['column_running_balance'] = 'الرصيد الجاري';
$_['column_unit_cost'] = 'تكلفة الوحدة';
$_['column_total_cost'] = 'إجمالي التكلفة';
$_['column_average_cost_before'] = 'متوسط التكلفة قبل';
$_['column_average_cost_after'] = 'متوسط التكلفة بعد';
$_['column_cost_change'] = 'تغيير التكلفة';
$_['column_notes'] = 'ملاحظات';
$_['column_user'] = 'المستخدم';
$_['column_action'] = 'إجراء';

// حقول الفلاتر
$_['entry_filter_product_id'] = 'معرف المنتج';
$_['entry_filter_product_name'] = 'اسم المنتج/الموديل/الرمز';
$_['entry_filter_category'] = 'التصنيف';
$_['entry_filter_manufacturer'] = 'العلامة التجارية';
$_['entry_filter_branch'] = 'الفرع/المستودع';
$_['entry_filter_branch_type'] = 'نوع الفرع';
$_['entry_filter_movement_type'] = 'نوع الحركة';
$_['entry_filter_reference_type'] = 'نوع المرجع';
$_['entry_filter_reference_number'] = 'رقم المرجع';
$_['entry_filter_lot_number'] = 'رقم الدفعة';
$_['entry_filter_user'] = 'المستخدم';
$_['entry_filter_date_from'] = 'من تاريخ';
$_['entry_filter_date_to'] = 'إلى تاريخ';
$_['entry_filter_has_expiry'] = 'تتبع الصلاحية';
$_['entry_filter_expiry_from'] = 'صلاحية من';
$_['entry_filter_expiry_to'] = 'صلاحية إلى';

// الأزرار
$_['button_filter'] = 'فلترة';
$_['button_clear'] = 'مسح الفلاتر';
$_['button_export_excel'] = 'تصدير Excel';
$_['button_export_pdf'] = 'تصدير PDF';
$_['button_print'] = 'طباعة';
$_['button_refresh'] = 'تحديث';
$_['button_view_reference'] = 'عرض المرجع';
$_['button_product_card'] = 'كارت الصنف';
$_['button_lot_report'] = 'تقرير الدفعات';
$_['button_expiring_lots'] = 'الدفعات منتهية الصلاحية';

// أنواع الحركات
$_['text_movement_type_purchase'] = 'وارد شراء';
$_['text_movement_type_sale'] = 'صادر بيع';
$_['text_movement_type_transfer_in'] = 'وارد تحويل';
$_['text_movement_type_transfer_out'] = 'صادر تحويل';
$_['text_movement_type_adjustment_in'] = 'تسوية زيادة';
$_['text_movement_type_adjustment_out'] = 'تسوية نقص';
$_['text_movement_type_production_in'] = 'وارد إنتاج';
$_['text_movement_type_production_out'] = 'صادر إنتاج';
$_['text_movement_type_return_in'] = 'وارد مرتجع';
$_['text_movement_type_return_out'] = 'صادر مرتجع';
$_['text_movement_type_opening_balance'] = 'رصيد افتتاحي';
$_['text_movement_type_physical_count'] = 'جرد فعلي';

// أنواع المراجع
$_['text_reference_type_purchase_order'] = 'أمر شراء';
$_['text_reference_type_purchase_invoice'] = 'فاتورة شراء';
$_['text_reference_type_sale_order'] = 'أمر بيع';
$_['text_reference_type_sale_invoice'] = 'فاتورة بيع';
$_['text_reference_type_stock_transfer'] = 'تحويل مخزني';
$_['text_reference_type_stock_adjustment'] = 'تسوية مخزنية';
$_['text_reference_type_production_order'] = 'أمر إنتاج';
$_['text_reference_type_physical_inventory'] = 'جرد فعلي';

// أنواع الفروع
$_['text_branch_type_store'] = 'متجر';
$_['text_branch_type_warehouse'] = 'مستودع';

// ملخص الحركات
$_['text_summary'] = 'ملخص الحركات';
$_['text_total_movements'] = 'إجمالي الحركات';
$_['text_total_products'] = 'إجمالي المنتجات';
$_['text_total_branches'] = 'إجمالي الفروع';
$_['text_total_quantity_in'] = 'إجمالي الوارد';
$_['text_total_quantity_out'] = 'إجمالي الصادر';
$_['text_net_quantity'] = 'صافي الكمية';
$_['text_total_value'] = 'إجمالي القيمة';
$_['text_avg_unit_cost'] = 'متوسط تكلفة الوحدة';
$_['text_total_lots'] = 'إجمالي الدفعات';
$_['text_movements_with_expiry'] = 'حركات مع صلاحية';

// تحليل الحركات حسب النوع
$_['text_movements_by_type'] = 'تحليل الحركات حسب النوع';
$_['text_movements_by_type_desc'] = 'توزيع الحركات والكميات والقيم حسب نوع الحركة';

// الدفعات منتهية الصلاحية
$_['text_expiring_lots'] = 'الدفعات منتهية الصلاحية قريباً';
$_['text_expiring_lots_desc'] = 'الدفعات التي ستنتهي صلاحيتها خلال 30 يوم';
$_['text_days_to_expiry'] = 'أيام للانتهاء';
$_['text_remaining_quantity'] = 'الكمية المتبقية';

// حالات انتهاء الصلاحية
$_['text_expiry_status_expired'] = 'منتهية الصلاحية';
$_['text_expiry_status_critical'] = 'حرجة (أقل من أسبوع)';
$_['text_expiry_status_warning'] = 'تحذير (أقل من شهر)';
$_['text_expiry_status_normal'] = 'طبيعية';

// رسائل المساعدة
$_['help_filter_product_name'] = 'البحث في اسم المنتج أو الموديل أو رمز المنتج';
$_['help_filter_category'] = 'فلترة حسب تصنيف المنتج';
$_['help_filter_manufacturer'] = 'فلترة حسب العلامة التجارية';
$_['help_filter_branch'] = 'فلترة حسب الفرع أو المستودع';
$_['help_filter_movement_type'] = 'فلترة حسب نوع الحركة (وارد، صادر، تسوية، إلخ)';
$_['help_filter_reference_type'] = 'فلترة حسب نوع المستند المرجعي';
$_['help_filter_reference_number'] = 'البحث في رقم المستند المرجعي';
$_['help_filter_lot_number'] = 'البحث في رقم الدفعة';
$_['help_filter_date_from'] = 'عرض الحركات من هذا التاريخ فما بعد';
$_['help_filter_date_to'] = 'عرض الحركات حتى هذا التاريخ';
$_['help_filter_has_expiry'] = 'فلترة الحركات حسب وجود تاريخ صلاحية';
$_['help_filter_expiry_from'] = 'عرض الحركات التي صلاحيتها من هذا التاريخ';
$_['help_filter_expiry_to'] = 'عرض الحركات التي صلاحيتها حتى هذا التاريخ';

// رسائل الخطأ
$_['error_warning'] = 'تحذير: يرجى التحقق من النموذج بعناية للأخطاء!';
$_['error_permission'] = 'تحذير: ليس لديك صلاحية لعرض حركات المخزون!';
$_['error_product_required'] = 'خطأ: يجب تحديد المنتج لعرض كارت الصنف!';

// نصوص التقارير
$_['text_report_title'] = 'تقرير سجل حركة المخزون';
$_['text_report_date'] = 'تاريخ التقرير';
$_['text_report_filters'] = 'الفلاتر المطبقة';
$_['text_report_summary'] = 'ملخص التقرير';
$_['text_report_details'] = 'تفاصيل التقرير';

// نصوص كارت الصنف
$_['text_product_card'] = 'كارت الصنف';
$_['text_product_card_title'] = 'كارت الصنف التفصيلي';
$_['text_product_info'] = 'معلومات المنتج';
$_['text_branch_info'] = 'معلومات الفرع';
$_['text_movement_history'] = 'تاريخ الحركات';
$_['text_opening_balance'] = 'الرصيد الافتتاحي';
$_['text_closing_balance'] = 'الرصيد الختامي';

// نصوص الطباعة
$_['text_print_title'] = 'تقرير سجل حركة المخزون';
$_['text_print_company'] = 'اسم الشركة';
$_['text_print_date'] = 'تاريخ الطباعة';
$_['text_print_user'] = 'طبع بواسطة';
$_['text_print_page'] = 'صفحة';
$_['text_print_of'] = 'من';

// نصوص التصدير
$_['text_export_excel_success'] = 'تم تصدير البيانات إلى Excel بنجاح';
$_['text_export_pdf_success'] = 'تم تصدير البيانات إلى PDF بنجاح';

// نصوص التحليل
$_['text_analysis'] = 'تحليل الحركات';
$_['text_movement_analysis'] = 'تحليل حركة المخزون';
$_['text_cost_analysis'] = 'تحليل التكلفة';
$_['text_lot_analysis'] = 'تحليل الدفعات';

// نصوص الإحصائيات
$_['text_statistics'] = 'إحصائيات الحركات';
$_['text_movement_distribution'] = 'توزيع الحركات';
$_['text_value_distribution'] = 'توزيع القيمة';
$_['text_time_analysis'] = 'تحليل زمني';

// نصوص التنبيهات
$_['text_alerts'] = 'تنبيهات الحركات';
$_['text_expiry_alert'] = 'تنبيه: يوجد دفعات منتهية الصلاحية قريباً';
$_['text_cost_change_alert'] = 'تنبيه: تغيير كبير في متوسط التكلفة';
$_['text_negative_balance_alert'] = 'تنبيه: رصيد سالب في المخزون';

// نصوص الإجراءات
$_['text_actions'] = 'الإجراءات المتاحة';
$_['text_view_details'] = 'عرض التفاصيل';
$_['text_edit_movement'] = 'تعديل الحركة';
$_['text_reverse_movement'] = 'عكس الحركة';

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
$_['datetime_format'] = 'd/m/Y H:i:s';
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
$_['text_running_balance'] = 'الرصيد الجاري';
$_['text_cumulative_cost'] = 'التكلفة التراكمية';

// نصوص التكامل
$_['text_integration'] = 'التكامل';
$_['text_accounting_integration'] = 'التكامل المحاسبي';
$_['text_purchase_integration'] = 'تكامل المشتريات';
$_['text_sales_integration'] = 'تكامل المبيعات';

// نصوص الأمان
$_['text_security'] = 'الأمان';
$_['text_audit_trail'] = 'مسار المراجعة';
$_['text_user_permissions'] = 'صلاحيات المستخدم';
$_['text_data_integrity'] = 'سلامة البيانات';

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

// نصوص الدفعات والصلاحية
$_['text_lot_management'] = 'إدارة الدفعات';
$_['text_expiry_management'] = 'إدارة الصلاحية';
$_['text_batch_tracking'] = 'تتبع الدفعات';
$_['text_expiry_tracking'] = 'تتبع الصلاحية';
$_['text_lot_traceability'] = 'تتبع الدفعات';
$_['text_expiry_alerts'] = 'تنبيهات الصلاحية';
?>
