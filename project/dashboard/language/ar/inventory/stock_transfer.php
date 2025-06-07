<?php
/**
 * ملف اللغة العربية لنقل المخزون بين الفروع المتطور
 *
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

// العنوان الرئيسي
$_['heading_title'] = 'نقل المخزون بين الفروع';

// النصوص الأساسية
$_['text_success'] = 'تم: تم تحديث البيانات بنجاح!';
$_['text_list'] = 'قائمة طلبات النقل';
$_['text_add'] = 'إضافة طلب نقل جديد';
$_['text_edit'] = 'تعديل طلب النقل';
$_['text_view'] = 'عرض طلب النقل';
$_['text_no_results'] = 'لا توجد طلبات نقل!';
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
$_['column_transfer_number'] = 'رقم النقل';
$_['column_transfer_name'] = 'اسم النقل';
$_['column_transfer_type'] = 'نوع النقل';
$_['column_status'] = 'الحالة';
$_['column_priority'] = 'الأولوية';
$_['column_from_branch'] = 'من الفرع';
$_['column_to_branch'] = 'إلى الفرع';
$_['column_reason'] = 'السبب';
$_['column_user'] = 'المستخدم';
$_['column_approved_by'] = 'معتمد بواسطة';
$_['column_shipped_by'] = 'مشحون بواسطة';
$_['column_received_by'] = 'مستلم بواسطة';
$_['column_request_date'] = 'تاريخ الطلب';
$_['column_approval_date'] = 'تاريخ الاعتماد';
$_['column_ship_date'] = 'تاريخ الشحن';
$_['column_expected_delivery_date'] = 'تاريخ التسليم المتوقع';
$_['column_actual_delivery_date'] = 'تاريخ التسليم الفعلي';
$_['column_total_items'] = 'إجمالي العناصر';
$_['column_total_quantity'] = 'إجمالي الكمية';
$_['column_total_received_quantity'] = 'إجمالي الكمية المستلمة';
$_['column_total_value'] = 'إجمالي القيمة';
$_['column_progress'] = 'التقدم';
$_['column_notes'] = 'ملاحظات';
$_['column_date_added'] = 'تاريخ الإضافة';
$_['column_action'] = 'إجراء';

// حقول النموذج
$_['entry_transfer_number'] = 'رقم النقل';
$_['entry_transfer_name'] = 'اسم النقل';
$_['entry_transfer_type'] = 'نوع النقل';
$_['entry_priority'] = 'الأولوية';
$_['entry_from_branch'] = 'من الفرع/المستودع';
$_['entry_to_branch'] = 'إلى الفرع/المستودع';
$_['entry_reason'] = 'السبب';
$_['entry_request_date'] = 'تاريخ الطلب';
$_['entry_expected_delivery_date'] = 'تاريخ التسليم المتوقع';
$_['entry_notes'] = 'ملاحظات';
$_['entry_product'] = 'المنتج';
$_['entry_quantity'] = 'الكمية';
$_['entry_received_quantity'] = 'الكمية المستلمة';
$_['entry_unit_cost'] = 'تكلفة الوحدة';
$_['entry_total_cost'] = 'إجمالي التكلفة';
$_['entry_lot_number'] = 'رقم الدفعة';
$_['entry_expiry_date'] = 'تاريخ انتهاء الصلاحية';
$_['entry_item_notes'] = 'ملاحظات العنصر';
$_['entry_available_quantity'] = 'الكمية المتاحة';

// حقول الفلاتر
$_['entry_filter_transfer_number'] = 'رقم النقل';
$_['entry_filter_transfer_name'] = 'اسم النقل';
$_['entry_filter_status'] = 'الحالة';
$_['entry_filter_transfer_type'] = 'نوع النقل';
$_['entry_filter_priority'] = 'الأولوية';
$_['entry_filter_from_branch'] = 'من الفرع';
$_['entry_filter_to_branch'] = 'إلى الفرع';
$_['entry_filter_reason'] = 'السبب';
$_['entry_filter_user'] = 'المستخدم';
$_['entry_filter_date_from'] = 'من تاريخ';
$_['entry_filter_date_to'] = 'إلى تاريخ';
$_['entry_filter_min_value'] = 'الحد الأدنى للقيمة';
$_['entry_filter_max_value'] = 'الحد الأقصى للقيمة';

// الأزرار
$_['button_add'] = 'إضافة طلب نقل جديد';
$_['button_edit'] = 'تعديل';
$_['button_delete'] = 'حذف';
$_['button_view'] = 'عرض';
$_['button_approve'] = 'موافقة';
$_['button_reject'] = 'رفض';
$_['button_ship'] = 'شحن';
$_['button_receive'] = 'استلام';
$_['button_complete'] = 'إكمال';
$_['button_cancel_transfer'] = 'إلغاء النقل';
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
$_['button_check_availability'] = 'فحص التوفر';

// حالات النقل
$_['text_status_draft'] = 'مسودة';
$_['text_status_pending_approval'] = 'في انتظار الموافقة';
$_['text_status_approved'] = 'معتمد';
$_['text_status_shipped'] = 'تم الشحن';
$_['text_status_in_transit'] = 'في الطريق';
$_['text_status_delivered'] = 'تم التسليم';
$_['text_status_received'] = 'تم الاستلام';
$_['text_status_completed'] = 'مكتمل';
$_['text_status_cancelled'] = 'ملغي';
$_['text_status_rejected'] = 'مرفوض';

// أنواع النقل
$_['text_transfer_type_regular'] = 'نقل عادي';
$_['text_transfer_type_emergency'] = 'نقل طارئ';
$_['text_transfer_type_restock'] = 'إعادة تخزين';
$_['text_transfer_type_redistribution'] = 'إعادة توزيع';
$_['text_transfer_type_return'] = 'إرجاع';

// مستويات الأولوية
$_['text_priority_low'] = 'منخفضة';
$_['text_priority_normal'] = 'عادية';
$_['text_priority_high'] = 'عالية';
$_['text_priority_urgent'] = 'عاجلة';

// أنواع الفروع
$_['text_branch_type_store'] = 'متجر';
$_['text_branch_type_warehouse'] = 'مستودع';

// ملخص النقل
$_['text_summary'] = 'ملخص النقل';
$_['text_total_transfers'] = 'إجمالي طلبات النقل';
$_['text_draft_count'] = 'المسودات';
$_['text_pending_approval_count'] = 'في انتظار الموافقة';
$_['text_approved_count'] = 'المعتمدة';
$_['text_shipped_count'] = 'المشحونة';
$_['text_in_transit_count'] = 'في الطريق';
$_['text_delivered_count'] = 'المسلمة';
$_['text_received_count'] = 'المستلمة';
$_['text_completed_count'] = 'المكتملة';
$_['text_cancelled_count'] = 'الملغية';
$_['text_total_completed_value'] = 'إجمالي قيمة النقل المكتمل';
$_['text_avg_items_per_transfer'] = 'متوسط العناصر لكل نقل';

// التحليلات
$_['text_transfers_by_branch'] = 'النقل حسب الفرع';
$_['text_outgoing_transfers'] = 'النقل الصادر';
$_['text_incoming_transfers'] = 'النقل الوارد';
$_['text_outgoing_count'] = 'عدد الصادر';
$_['text_incoming_count'] = 'عدد الوارد';
$_['text_outgoing_value'] = 'قيمة الصادر';
$_['text_incoming_value'] = 'قيمة الوارد';
$_['text_net_transfer'] = 'صافي النقل';

// رسائل المساعدة
$_['help_transfer_number'] = 'رقم فريد لتحديد طلب النقل';
$_['help_transfer_name'] = 'اسم وصفي لطلب النقل';
$_['help_transfer_type'] = 'نوع النقل: عادي، طارئ، إعادة تخزين، إعادة توزيع، إرجاع';
$_['help_priority'] = 'أولوية النقل: منخفضة، عادية، عالية، عاجلة';
$_['help_from_branch'] = 'الفرع أو المستودع المرسل';
$_['help_to_branch'] = 'الفرع أو المستودع المستقبل';
$_['help_reason'] = 'سبب النقل من القائمة المحددة مسبقاً';
$_['help_request_date'] = 'تاريخ طلب النقل';
$_['help_expected_delivery_date'] = 'التاريخ المتوقع لوصول الشحنة';
$_['help_quantity'] = 'الكمية المراد نقلها';
$_['help_received_quantity'] = 'الكمية الفعلية المستلمة';
$_['help_unit_cost'] = 'تكلفة الوحدة الواحدة';
$_['help_lot_number'] = 'رقم الدفعة (اختياري)';
$_['help_expiry_date'] = 'تاريخ انتهاء الصلاحية (اختياري)';
$_['help_available_quantity'] = 'الكمية المتاحة في المخزون';

// رسائل الخطأ
$_['error_warning'] = 'تحذير: يرجى التحقق من النموذج بعناية للأخطاء!';
$_['error_permission'] = 'تحذير: ليس لديك صلاحية للوصول إلى نقل المخزون!';
$_['error_transfer_name'] = 'يجب أن يكون اسم النقل بين 3 و 255 حرف!';
$_['error_from_branch_required'] = 'الفرع المرسل مطلوب!';
$_['error_to_branch_required'] = 'الفرع المستقبل مطلوب!';
$_['error_same_branch'] = 'لا يمكن أن يكون الفرع المرسل والمستقبل نفس الفرع!';
$_['error_request_date'] = 'تاريخ الطلب مطلوب!';
$_['error_transfer_items_required'] = 'يجب إضافة عنصر واحد على الأقل!';
$_['error_product_required'] = 'المنتج مطلوب!';
$_['error_quantity_required'] = 'الكمية مطلوبة ويجب أن تكون أكبر من صفر!';
$_['error_unit_cost_required'] = 'تكلفة الوحدة مطلوبة ويجب أن تكون أكبر من صفر!';
$_['error_transfer_not_found'] = 'طلب النقل غير موجود!';
$_['error_transfer_posted'] = 'لا يمكن تعديل نقل مكتمل!';
$_['error_insufficient_stock'] = 'المخزون غير كافي لإتمام النقل!';

// رسائل النجاح
$_['text_approved_success'] = 'تم اعتماد طلب النقل بنجاح!';
$_['text_rejected_success'] = 'تم رفض طلب النقل بنجاح!';
$_['text_shipped_success'] = 'تم شحن طلب النقل بنجاح!';
$_['text_received_success'] = 'تم استلام طلب النقل بنجاح!';
$_['text_completed_success'] = 'تم إكمال طلب النقل بنجاح!';
$_['text_cancelled_success'] = 'تم إلغاء طلب النقل بنجاح!';

// نصوص التقارير
$_['text_report_title'] = 'تقرير نقل المخزون';
$_['text_report_date'] = 'تاريخ التقرير';
$_['text_report_filters'] = 'الفلاتر المطبقة';
$_['text_report_summary'] = 'ملخص التقرير';
$_['text_report_details'] = 'تفاصيل التقرير';

// نصوص الطباعة
$_['text_print_title'] = 'نقل المخزون بين الفروع';
$_['text_print_company'] = 'اسم الشركة';
$_['text_print_date'] = 'تاريخ الطباعة';
$_['text_print_user'] = 'طبع بواسطة';
$_['text_print_page'] = 'صفحة';
$_['text_print_of'] = 'من';

// نصوص التصدير
$_['text_export_excel_success'] = 'تم تصدير البيانات إلى Excel بنجاح';
$_['text_export_pdf_success'] = 'تم تصدير البيانات إلى PDF بنجاح';

// تاريخ النقل
$_['text_transfer_history'] = 'تاريخ النقل';
$_['text_transfer_status'] = 'حالة النقل';
$_['text_transfer_user'] = 'المستخدم';
$_['text_transfer_date'] = 'تاريخ النقل';
$_['text_transfer_notes'] = 'ملاحظات النقل';

// نصوص الموافقات
$_['text_approval'] = 'الموافقة';
$_['text_approval_required'] = 'تتطلب موافقة';
$_['text_approval_workflow'] = 'سير عمل الموافقة';
$_['text_pending_approvals'] = 'الموافقات المعلقة';

// نصوص الشحن
$_['text_shipping'] = 'الشحن';
$_['text_shipping_details'] = 'تفاصيل الشحن';
$_['text_tracking_number'] = 'رقم التتبع';
$_['text_carrier'] = 'شركة الشحن';
$_['text_shipping_method'] = 'طريقة الشحن';
$_['text_shipping_cost'] = 'تكلفة الشحن';

// نصوص الاستلام
$_['text_receiving'] = 'الاستلام';
$_['text_receiving_details'] = 'تفاصيل الاستلام';
$_['text_received_by'] = 'مستلم بواسطة';
$_['text_received_date'] = 'تاريخ الاستلام';
$_['text_condition'] = 'الحالة';
$_['text_damage_report'] = 'تقرير الأضرار';

// نصوص التحليل
$_['text_analysis'] = 'تحليل النقل';
$_['text_performance_analysis'] = 'تحليل الأداء';
$_['text_efficiency_analysis'] = 'تحليل الكفاءة';
$_['text_cost_analysis'] = 'تحليل التكلفة';

// نصوص الإحصائيات
$_['text_statistics'] = 'إحصائيات النقل';
$_['text_transfer_frequency'] = 'تكرار النقل';
$_['text_average_delivery_time'] = 'متوسط وقت التسليم';
$_['text_success_rate'] = 'معدل النجاح';

// نصوص التنبيهات
$_['text_alerts'] = 'تنبيهات النقل';
$_['text_delayed_transfer_alert'] = 'تنبيه: نقل متأخر';
$_['text_urgent_transfer_alert'] = 'تنبيه: نقل عاجل';
$_['text_stock_shortage_alert'] = 'تنبيه: نقص في المخزون';

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
$_['text_variance_calculation'] = 'حساب الفرق';

// نصوص التكامل
$_['text_accounting_integration'] = 'التكامل المحاسبي';
$_['text_inventory_integration'] = 'تكامل المخزون';
$_['text_shipping_integration'] = 'تكامل الشحن';
$_['text_notification_integration'] = 'تكامل الإشعارات';

// نصوص الأمان
$_['text_security'] = 'الأمان';
$_['text_audit_trail'] = 'مسار المراجعة';
$_['text_user_permissions'] = 'صلاحيات المستخدم';
$_['text_data_integrity'] = 'سلامة البيانات';

// نصوص الأداء
$_['text_performance'] = 'الأداء';
$_['text_loading_time'] = 'وقت التحميل';
$_['text_response_time'] = 'وقت الاستجابة';
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
$_['text_trend_analysis'] = 'تحليل الاتجاهات';

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

// نصوص النقل المتقدم
$_['text_advanced_transfers'] = 'النقل المتقدم';
$_['text_bulk_transfers'] = 'النقل المجمع';
$_['text_automated_transfers'] = 'النقل التلقائي';
$_['text_scheduled_transfers'] = 'النقل المجدول';

// نصوص التتبع
$_['text_tracking'] = 'التتبع';
$_['text_real_time_tracking'] = 'التتبع في الوقت الفعلي';
$_['text_gps_tracking'] = 'تتبع GPS';
$_['text_barcode_tracking'] = 'تتبع الباركود';

// نصوص اللوجستيات
$_['text_logistics'] = 'اللوجستيات';
$_['text_supply_chain'] = 'سلسلة التوريد';
$_['text_distribution'] = 'التوزيع';
$_['text_warehouse_management'] = 'إدارة المستودعات';

// نصوص العمليات المجمعة الجديدة
$_['button_bulk_actions'] = 'عمليات مجمعة';
$_['button_bulk_approve'] = 'اعتماد مجمع';
$_['button_bulk_ship'] = 'شحن مجمع';
$_['button_bulk_cancel'] = 'إلغاء مجمع';
$_['button_bulk_print'] = 'طباعة مجمعة';
$_['button_bulk_export'] = 'تصدير مجمع';
$_['button_bulk_delete'] = 'حذف مجمع';
$_['button_select_all'] = 'تحديد الكل';
$_['button_deselect_all'] = 'إلغاء تحديد الكل';

// رسائل العمليات المجمعة
$_['text_bulk_approve_confirm'] = 'هل أنت متأكد من اعتماد العناصر المحددة؟';
$_['text_bulk_ship_confirm'] = 'هل أنت متأكد من شحن العناصر المحددة؟';
$_['text_bulk_cancel_confirm'] = 'هل أنت متأكد من إلغاء العناصر المحددة؟ لا يمكن التراجع عن هذا الإجراء!';
$_['text_bulk_delete_confirm'] = 'هل أنت متأكد من حذف العناصر المحددة؟ لا يمكن التراجع عن هذا الإجراء!';
$_['text_no_selection'] = 'يرجى تحديد عنصر واحد على الأقل';
$_['text_bulk_success'] = 'تم تنفيذ العملية بنجاح على %d عنصر';
$_['text_bulk_partial_success'] = 'تم تنفيذ العملية على %d من %d عنصر';

// نصوص التفاعل المتقدم
$_['text_loading'] = 'جاري التحميل...';
$_['text_processing'] = 'جاري المعالجة...';
$_['text_updating'] = 'جاري التحديث...';
$_['text_saving'] = 'جاري الحفظ...';
$_['text_deleting'] = 'جاري الحذف...';
$_['text_connection_error'] = 'حدث خطأ في الاتصال';
$_['text_operation_completed'] = 'تم إكمال العملية بنجاح';
$_['text_operation_failed'] = 'فشلت العملية';

// نصوص الفلاتر المتقدمة
$_['text_filter_saved'] = 'تم حفظ الفلتر بنجاح';
$_['text_filter_applied'] = 'تم تطبيق الفلتر';
$_['text_filter_cleared'] = 'تم مسح الفلاتر';
$_['text_no_filters'] = 'لا توجد فلاتر محفوظة';
$_['text_save_filter'] = 'حفظ الفلتر الحالي';
$_['text_load_filter'] = 'تحميل فلتر محفوظ';

// نصوص التحديث التلقائي
$_['text_auto_refresh'] = 'تحديث تلقائي';
$_['text_auto_refresh_enabled'] = 'التحديث التلقائي مفعل';
$_['text_auto_refresh_disabled'] = 'التحديث التلقائي معطل';
$_['text_last_updated'] = 'آخر تحديث';
$_['text_refresh_interval'] = 'فترة التحديث';

// نصوص الإحصائيات المتقدمة
$_['text_click_to_filter'] = 'انقر للفلترة';
$_['text_view_details'] = 'عرض التفاصيل';
$_['text_expand_panel'] = 'توسيع اللوحة';
$_['text_collapse_panel'] = 'طي اللوحة';
$_['text_show_chart'] = 'عرض الرسم البياني';
$_['text_hide_chart'] = 'إخفاء الرسم البياني';

// نصوص التنبيهات الذكية
$_['text_smart_alerts'] = 'التنبيهات الذكية';
$_['text_alert_overdue'] = 'تنبيه: طلبات متأخرة';
$_['text_alert_urgent'] = 'تنبيه: طلبات عاجلة';
$_['text_alert_pending'] = 'تنبيه: طلبات في انتظار الموافقة';
$_['text_alert_stock_low'] = 'تنبيه: مخزون منخفض';

// نصوص التصدير المتقدم
$_['text_export_options'] = 'خيارات التصدير';
$_['text_export_all'] = 'تصدير الكل';
$_['text_export_filtered'] = 'تصدير المفلتر';
$_['text_export_selected'] = 'تصدير المحدد';
$_['text_export_format'] = 'تنسيق التصدير';
$_['text_export_columns'] = 'الأعمدة المراد تصديرها';

// نصوص الطباعة المتقدمة
$_['text_print_options'] = 'خيارات الطباعة';
$_['text_print_preview'] = 'معاينة الطباعة';
$_['text_print_layout'] = 'تخطيط الطباعة';
$_['text_print_orientation'] = 'اتجاه الطباعة';
$_['text_print_portrait'] = 'عمودي';
$_['text_print_landscape'] = 'أفقي';

// نصوص الأداء والتحسين
$_['text_performance'] = 'الأداء';
$_['text_optimization'] = 'التحسين';
$_['text_cache_cleared'] = 'تم مسح التخزين المؤقت';
$_['text_data_refreshed'] = 'تم تحديث البيانات';
$_['text_system_optimized'] = 'تم تحسين النظام';

// نصوص المساعدة المتقدمة
$_['text_help_center'] = 'مركز المساعدة';
$_['text_user_guide'] = 'دليل المستخدم';
$_['text_video_tutorials'] = 'دروس فيديو';
$_['text_keyboard_shortcuts'] = 'اختصارات لوحة المفاتيح';
$_['text_tips_tricks'] = 'نصائح وحيل';

// نصوص التخصيص
$_['text_customize'] = 'تخصيص';
$_['text_layout_settings'] = 'إعدادات التخطيط';
$_['text_column_settings'] = 'إعدادات الأعمدة';
$_['text_theme_settings'] = 'إعدادات المظهر';
$_['text_user_preferences'] = 'تفضيلات المستخدم';

// نصوص التكامل
$_['text_integration'] = 'التكامل';
$_['text_api_access'] = 'الوصول للـ API';
$_['text_webhook_settings'] = 'إعدادات Webhook';
$_['text_external_systems'] = 'الأنظمة الخارجية';
$_['text_data_sync'] = 'مزامنة البيانات';

// نصوص الأمان المتقدم
$_['text_security'] = 'الأمان';
$_['text_access_log'] = 'سجل الوصول';
$_['text_permission_denied'] = 'تم رفض الإذن';
$_['text_session_expired'] = 'انتهت صلاحية الجلسة';
$_['text_login_required'] = 'يتطلب تسجيل الدخول';

// نصوص التقارير المتقدمة
$_['text_advanced_reports'] = 'التقارير المتقدمة';
$_['text_custom_reports'] = 'التقارير المخصصة';
$_['text_report_builder'] = 'منشئ التقارير';
$_['text_scheduled_reports'] = 'التقارير المجدولة';
$_['text_report_templates'] = 'قوالب التقارير';

// نصوص الذكاء الاصطناعي
$_['text_ai_insights'] = 'رؤى الذكاء الاصطناعي';
$_['text_predictive_analytics'] = 'التحليل التنبؤي';
$_['text_smart_recommendations'] = 'التوصيات الذكية';
$_['text_automated_decisions'] = 'القرارات الآلية';
$_['text_machine_learning'] = 'التعلم الآلي';
?>
