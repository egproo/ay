<?php
/**
 * ملف اللغة العربية للتسويات المخزنية المتطور
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

// العنوان الرئيسي
$_['heading_title'] = 'التسويات المخزنية';

// النصوص الأساسية
$_['text_success'] = 'تم: تم تحديث البيانات بنجاح!';
$_['text_list'] = 'قائمة التسويات المخزنية';
$_['text_add'] = 'إضافة تسوية جديدة';
$_['text_edit'] = 'تعديل التسوية';
$_['text_view'] = 'عرض التسوية';
$_['text_no_results'] = 'لا توجد تسويات!';
$_['text_confirm'] = 'هل أنت متأكد؟';
$_['text_loading'] = 'جاري التحميل...';
$_['text_all'] = 'الكل';
$_['text_none'] = '--- لا يوجد ---';
$_['text_select'] = '--- اختر ---';
$_['text_enabled'] = 'مفعل';
$_['text_disabled'] = 'معطل';
$_['text_yes'] = 'نعم';
$_['text_no'] = 'لا';
$_['text_no_reason'] = 'بدون سبب';

// أعمدة الجدول
$_['column_adjustment_number'] = 'رقم التسوية';
$_['column_adjustment_name'] = 'اسم التسوية';
$_['column_adjustment_type'] = 'نوع التسوية';
$_['column_status'] = 'الحالة';
$_['column_branch'] = 'الفرع/المستودع';
$_['column_reason'] = 'السبب';
$_['column_reason_category'] = 'فئة السبب';
$_['column_user'] = 'المستخدم';
$_['column_approved_by'] = 'معتمد بواسطة';
$_['column_adjustment_date'] = 'تاريخ التسوية';
$_['column_approval_date'] = 'تاريخ الاعتماد';
$_['column_total_items'] = 'إجمالي العناصر';
$_['column_total_quantity'] = 'إجمالي الكمية';
$_['column_total_value'] = 'إجمالي القيمة';
$_['column_increase_value'] = 'قيمة الزيادة';
$_['column_decrease_value'] = 'قيمة النقص';
$_['column_notes'] = 'ملاحظات';
$_['column_date_added'] = 'تاريخ الإضافة';
$_['column_action'] = 'إجراء';

// حقول النموذج
$_['entry_adjustment_number'] = 'رقم التسوية';
$_['entry_adjustment_name'] = 'اسم التسوية';
$_['entry_adjustment_type'] = 'نوع التسوية';
$_['entry_branch'] = 'الفرع/المستودع';
$_['entry_reason'] = 'السبب';
$_['entry_reference_type'] = 'نوع المرجع';
$_['entry_reference_number'] = 'رقم المرجع';
$_['entry_adjustment_date'] = 'تاريخ التسوية';
$_['entry_notes'] = 'ملاحظات';
$_['entry_product'] = 'المنتج';
$_['entry_quantity'] = 'الكمية';
$_['entry_unit_cost'] = 'تكلفة الوحدة';
$_['entry_total_cost'] = 'إجمالي التكلفة';
$_['entry_lot_number'] = 'رقم الدفعة';
$_['entry_expiry_date'] = 'تاريخ انتهاء الصلاحية';
$_['entry_item_reason'] = 'سبب العنصر';
$_['entry_item_notes'] = 'ملاحظات العنصر';

// حقول الفلاتر
$_['entry_filter_adjustment_number'] = 'رقم التسوية';
$_['entry_filter_adjustment_name'] = 'اسم التسوية';
$_['entry_filter_status'] = 'الحالة';
$_['entry_filter_adjustment_type'] = 'نوع التسوية';
$_['entry_filter_branch'] = 'الفرع/المستودع';
$_['entry_filter_reason'] = 'السبب';
$_['entry_filter_reason_category'] = 'فئة السبب';
$_['entry_filter_user'] = 'المستخدم';
$_['entry_filter_date_from'] = 'من تاريخ';
$_['entry_filter_date_to'] = 'إلى تاريخ';
$_['entry_filter_min_value'] = 'الحد الأدنى للقيمة';
$_['entry_filter_max_value'] = 'الحد الأقصى للقيمة';

// الأزرار
$_['button_add'] = 'إضافة تسوية جديدة';
$_['button_edit'] = 'تعديل';
$_['button_delete'] = 'حذف';
$_['button_view'] = 'عرض';
$_['button_approve'] = 'موافقة';
$_['button_reject'] = 'رفض';
$_['button_post'] = 'ترحيل';
$_['button_submit'] = 'تقديم للموافقة';
$_['button_filter'] = 'فلترة';
$_['button_clear'] = 'مسح الفلاتر';
$_['button_export_excel'] = 'تصدير Excel';
$_['button_export_pdf'] = 'تصدير PDF';
$_['button_print'] = 'طباعة';
$_['button_refresh'] = 'تحديث';
$_['button_save'] = 'حفظ';
$_['button_cancel'] = 'إلغاء';
$_['button_add_item'] = 'إضافة عنصر';
$_['button_remove_item'] = 'حذف عنصر';

// حالات التسوية
$_['text_status_draft'] = 'مسودة';
$_['text_status_pending_approval'] = 'في انتظار الموافقة';
$_['text_status_approved'] = 'معتمد';
$_['text_status_posted'] = 'مرحل';
$_['text_status_rejected'] = 'مرفوض';
$_['text_status_cancelled'] = 'ملغي';

// أنواع التسوية
$_['text_adjustment_type_manual'] = 'تسوية يدوية';
$_['text_adjustment_type_counting'] = 'تسوية من الجرد';
$_['text_adjustment_type_damage'] = 'تسوية تلف';
$_['text_adjustment_type_loss'] = 'تسوية فقدان';
$_['text_adjustment_type_found'] = 'تسوية عثور';
$_['text_adjustment_type_expiry'] = 'تسوية انتهاء صلاحية';
$_['text_adjustment_type_system'] = 'تسوية نظام';

// فئات الأسباب
$_['text_reason_category_increase'] = 'زيادة';
$_['text_reason_category_decrease'] = 'نقص';
$_['text_reason_category_correction'] = 'تصحيح';
$_['text_reason_category_transfer'] = 'تحويل';

// أنواع الفروع
$_['text_branch_type_store'] = 'متجر';
$_['text_branch_type_warehouse'] = 'مستودع';

// ملخص التسويات
$_['text_summary'] = 'ملخص التسويات';
$_['text_total_adjustments'] = 'إجمالي التسويات';
$_['text_draft_count'] = 'المسودات';
$_['text_pending_approval_count'] = 'في انتظار الموافقة';
$_['text_approved_count'] = 'المعتمدة';
$_['text_posted_count'] = 'المرحلة';
$_['text_rejected_count'] = 'المرفوضة';
$_['text_total_increase_value'] = 'إجمالي قيمة الزيادة';
$_['text_total_decrease_value'] = 'إجمالي قيمة النقص';
$_['text_avg_items_per_adjustment'] = 'متوسط العناصر لكل تسوية';

// التحليلات
$_['text_adjustments_by_reason'] = 'التسويات حسب السبب';
$_['text_adjustments_by_branch'] = 'التسويات حسب الفرع';
$_['text_top_value_adjustments'] = 'أكبر التسويات قيمة';
$_['text_adjustment_count'] = 'عدد التسويات';
$_['text_avg_value'] = 'متوسط القيمة';

// رسائل المساعدة
$_['help_adjustment_number'] = 'رقم فريد لتحديد التسوية';
$_['help_adjustment_name'] = 'اسم وصفي للتسوية';
$_['help_adjustment_type'] = 'نوع التسوية: يدوية، من الجرد، تلف، فقدان، عثور، انتهاء صلاحية، نظام';
$_['help_branch'] = 'الفرع أو المستودع المراد تسويته';
$_['help_reason'] = 'سبب التسوية من القائمة المحددة مسبقاً';
$_['help_reference_type'] = 'نوع المستند المرجعي (جرد، فاتورة، إلخ)';
$_['help_reference_number'] = 'رقم المستند المرجعي';
$_['help_adjustment_date'] = 'التاريخ الفعلي لتنفيذ التسوية';
$_['help_quantity'] = 'الكمية المراد تسويتها (موجبة للزيادة، سالبة للنقص)';
$_['help_unit_cost'] = 'تكلفة الوحدة الواحدة';
$_['help_lot_number'] = 'رقم الدفعة (اختياري)';
$_['help_expiry_date'] = 'تاريخ انتهاء الصلاحية (اختياري)';

// رسائل الخطأ
$_['error_warning'] = 'تحذير: يرجى التحقق من النموذج بعناية للأخطاء!';
$_['error_permission'] = 'تحذير: ليس لديك صلاحية للوصول إلى التسويات المخزنية!';
$_['error_adjustment_name'] = 'يجب أن يكون اسم التسوية بين 3 و 255 حرف!';
$_['error_branch_required'] = 'الفرع/المستودع مطلوب!';
$_['error_adjustment_date'] = 'تاريخ التسوية مطلوب!';
$_['error_adjustment_items_required'] = 'يجب إضافة عنصر واحد على الأقل!';
$_['error_product_required'] = 'المنتج مطلوب!';
$_['error_quantity_required'] = 'الكمية مطلوبة ولا يمكن أن تكون صفر!';
$_['error_unit_cost_required'] = 'تكلفة الوحدة مطلوبة ويجب أن تكون أكبر من صفر!';
$_['error_adjustment_not_found'] = 'التسوية غير موجودة!';
$_['error_adjustment_posted'] = 'لا يمكن تعديل تسوية مرحلة!';
$_['error_cannot_approve'] = 'لا يمكنك الموافقة على هذه التسوية!';

// رسائل النجاح
$_['text_approved_success'] = 'تم اعتماد التسوية بنجاح!';
$_['text_rejected_success'] = 'تم رفض التسوية بنجاح!';
$_['text_posted_success'] = 'تم ترحيل التسوية بنجاح!';
$_['text_submitted_success'] = 'تم تقديم التسوية للموافقة بنجاح!';

// نصوص التقارير
$_['text_report_title'] = 'تقرير التسويات المخزنية';
$_['text_report_date'] = 'تاريخ التقرير';
$_['text_report_filters'] = 'الفلاتر المطبقة';
$_['text_report_summary'] = 'ملخص التقرير';
$_['text_report_details'] = 'تفاصيل التقرير';

// نصوص الطباعة
$_['text_print_title'] = 'التسويات المخزنية';
$_['text_print_company'] = 'اسم الشركة';
$_['text_print_date'] = 'تاريخ الطباعة';
$_['text_print_user'] = 'طبع بواسطة';
$_['text_print_page'] = 'صفحة';
$_['text_print_of'] = 'من';

// نصوص التصدير
$_['text_export_excel_success'] = 'تم تصدير البيانات إلى Excel بنجاح';
$_['text_export_pdf_success'] = 'تم تصدير البيانات إلى PDF بنجاح';

// تاريخ الموافقات
$_['text_approval_history'] = 'تاريخ الموافقات';
$_['text_approval_status'] = 'حالة الموافقة';
$_['text_approval_user'] = 'المستخدم';
$_['text_approval_date'] = 'تاريخ الموافقة';
$_['text_approval_notes'] = 'ملاحظات الموافقة';

// نصوص الموافقات
$_['text_approval'] = 'الموافقة';
$_['text_approval_required'] = 'تتطلب موافقة';
$_['text_approval_limit'] = 'حد الموافقة';
$_['text_approval_workflow'] = 'سير عمل الموافقة';
$_['text_pending_approvals'] = 'الموافقات المعلقة';

// نصوص التحليل
$_['text_analysis'] = 'تحليل التسويات';
$_['text_variance_analysis'] = 'تحليل الفروقات';
$_['text_trend_analysis'] = 'تحليل الاتجاهات';
$_['text_cost_analysis'] = 'تحليل التكلفة';

// نصوص الإحصائيات
$_['text_statistics'] = 'إحصائيات التسويات';
$_['text_adjustment_frequency'] = 'تكرار التسويات';
$_['text_value_distribution'] = 'توزيع القيمة';
$_['text_reason_distribution'] = 'توزيع الأسباب';

// نصوص التنبيهات
$_['text_alerts'] = 'تنبيهات التسويات';
$_['text_high_value_alert'] = 'تنبيه: تسوية عالية القيمة';
$_['text_frequent_adjustments_alert'] = 'تنبيه: تسويات متكررة';
$_['text_unusual_pattern_alert'] = 'تنبيه: نمط غير عادي';

// نصوص الإجراءات
$_['text_actions'] = 'الإجراءات المتاحة';
$_['text_workflow'] = 'سير العمل';
$_['text_automation'] = 'الأتمتة';
$_['text_integration'] = 'التكامل';

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
$_['text_cost_calculation'] = 'حساب التكلفة';
$_['text_value_calculation'] = 'حساب القيمة';
$_['text_impact_calculation'] = 'حساب التأثير';

// نصوص التكامل
$_['text_accounting_integration'] = 'التكامل المحاسبي';
$_['text_inventory_integration'] = 'تكامل المخزون';
$_['text_reporting_integration'] = 'تكامل التقارير';
$_['text_notification_integration'] = 'تكامل الإشعارات';

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
$_['text_comparative_analysis'] = 'التحليل المقارن';
$_['text_root_cause_analysis'] = 'تحليل السبب الجذري';

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

// نصوص الامتثال
$_['text_compliance'] = 'الامتثال';
$_['text_regulatory_compliance'] = 'الامتثال التنظيمي';
$_['text_audit_compliance'] = 'امتثال المراجعة';
$_['text_sox_compliance'] = 'امتثال SOX';

// نصوص الكفاءة
$_['text_efficiency'] = 'الكفاءة';
$_['text_process_efficiency'] = 'كفاءة العمليات';
$_['text_time_efficiency'] = 'كفاءة الوقت';
$_['text_resource_efficiency'] = 'كفاءة الموارد';

// نصوص التسويات المتقدمة
$_['text_advanced_adjustments'] = 'التسويات المتقدمة';
$_['text_bulk_adjustments'] = 'التسويات المجمعة';
$_['text_automated_adjustments'] = 'التسويات التلقائية';
$_['text_scheduled_adjustments'] = 'التسويات المجدولة';
?>
