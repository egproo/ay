<?php
/**
 * ملف اللغة العربية لإدارة المواقع والمناطق المتطورة
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

// العنوان الرئيسي
$_['heading_title'] = 'إدارة المواقع والمناطق المتطورة';

// النصوص الأساسية
$_['text_success'] = 'تم: تم تحديث البيانات بنجاح!';
$_['text_list'] = 'قائمة المواقع';
$_['text_add'] = 'إضافة موقع جديد';
$_['text_edit'] = 'تعديل الموقع';
$_['text_view'] = 'عرض الموقع';
$_['text_copy'] = 'نسخ الموقع';
$_['text_delete'] = 'حذف الموقع';
$_['text_no_results'] = 'لا توجد مواقع!';
$_['text_confirm'] = 'هل أنت متأكد؟';
$_['text_loading'] = 'جاري التحميل...';
$_['text_all'] = 'الكل';
$_['text_none'] = '--- لا يوجد ---';
$_['text_select'] = '--- اختر ---';
$_['text_enabled'] = 'مفعل';
$_['text_disabled'] = 'معطل';
$_['text_yes'] = 'نعم';
$_['text_no'] = 'لا';
$_['text_location_map'] = 'خريطة المواقع';
$_['text_barcode_scanner'] = 'ماسح الباركود';
$_['text_usage_report'] = 'تقرير الاستخدام';
$_['text_generate_qr'] = 'إنشاء QR Code';
$_['text_update_quantities'] = 'تحديث الكميات';

// أعمدة الجدول
$_['column_name'] = 'اسم الموقع';
$_['column_location_code'] = 'كود الموقع';
$_['column_location_type'] = 'نوع الموقع';
$_['column_parent_location'] = 'الموقع الرئيسي';
$_['column_branch'] = 'الفرع';
$_['column_warehouse'] = 'المستودع';
$_['column_zone'] = 'المنطقة';
$_['column_full_address'] = 'العنوان الكامل';
$_['column_capacity'] = 'السعة';
$_['column_current_quantity'] = 'الكمية الحالية';
$_['column_usage_percentage'] = 'نسبة الاستخدام';
$_['column_occupancy_status'] = 'حالة الإشغال';
$_['column_products_count'] = 'عدد المنتجات';
$_['column_movements_30_days'] = 'حركات 30 يوم';
$_['column_total_value'] = 'إجمالي القيمة';
$_['column_priority'] = 'الأولوية';
$_['column_gps_coordinates'] = 'إحداثيات GPS';
$_['column_status'] = 'الحالة';
$_['column_date_added'] = 'تاريخ الإضافة';
$_['column_date_modified'] = 'آخر تعديل';
$_['column_action'] = 'إجراء';
$_['column_last_used'] = 'آخر استخدام';
$_['column_sub_locations'] = 'المواقع الفرعية';

// حقول النموذج
$_['entry_name'] = 'اسم الموقع';
$_['entry_description'] = 'وصف الموقع';
$_['entry_location_code'] = 'كود الموقع';
$_['entry_location_type'] = 'نوع الموقع';
$_['entry_parent_location'] = 'الموقع الرئيسي';
$_['entry_branch'] = 'الفرع';
$_['entry_warehouse'] = 'المستودع';
$_['entry_zone'] = 'المنطقة';
$_['entry_aisle'] = 'الممر';
$_['entry_rack'] = 'الرف';
$_['entry_shelf'] = 'الرفة';
$_['entry_bin'] = 'الصندوق';
$_['entry_barcode'] = 'الباركود';
$_['entry_qr_code'] = 'QR Code';
$_['entry_capacity_weight'] = 'سعة الوزن (كجم)';
$_['entry_capacity_volume'] = 'سعة الحجم (لتر)';
$_['entry_capacity_units'] = 'سعة الوحدات';
$_['entry_current_weight'] = 'الوزن الحالي (كجم)';
$_['entry_current_volume'] = 'الحجم الحالي (لتر)';
$_['entry_current_units'] = 'الوحدات الحالية';
$_['entry_temperature_min'] = 'أدنى درجة حرارة (°م)';
$_['entry_temperature_max'] = 'أعلى درجة حرارة (°م)';
$_['entry_humidity_min'] = 'أدنى رطوبة (%)';
$_['entry_humidity_max'] = 'أعلى رطوبة (%)';
$_['entry_is_active'] = 'مفعل';
$_['entry_is_pickable'] = 'قابل للانتقاء';
$_['entry_is_receivable'] = 'قابل للاستقبال';
$_['entry_is_countable'] = 'قابل للعد';
$_['entry_priority_level'] = 'مستوى الأولوية';
$_['entry_gps_latitude'] = 'خط العرض';
$_['entry_gps_longitude'] = 'خط الطول';
$_['entry_sort_order'] = 'ترتيب العرض';

// حقول الفلاتر
$_['entry_filter_name'] = 'اسم الموقع';
$_['entry_filter_location_code'] = 'كود الموقع';
$_['entry_filter_location_type'] = 'نوع الموقع';
$_['entry_filter_branch'] = 'الفرع';
$_['entry_filter_warehouse'] = 'المستودع';
$_['entry_filter_zone'] = 'المنطقة';
$_['entry_filter_parent_location'] = 'الموقع الرئيسي';
$_['entry_filter_is_active'] = 'الحالة';
$_['entry_filter_is_pickable'] = 'قابل للانتقاء';
$_['entry_filter_occupancy_status'] = 'حالة الإشغال';

// أنواع المواقع
$_['text_location_type_warehouse'] = 'مستودع';
$_['text_location_type_zone'] = 'منطقة';
$_['text_location_type_aisle'] = 'ممر';
$_['text_location_type_rack'] = 'رف';
$_['text_location_type_shelf'] = 'رفة';
$_['text_location_type_bin'] = 'صندوق';
$_['text_location_type_room'] = 'غرفة';
$_['text_location_type_floor'] = 'طابق';
$_['text_location_type_building'] = 'مبنى';
$_['text_location_type_yard'] = 'ساحة';
$_['text_location_type_dock'] = 'رصيف';
$_['text_location_type_staging'] = 'منطقة تجميع';

// حالات الإشغال
$_['text_occupancy_empty'] = 'فارغ';
$_['text_occupancy_low'] = 'منخفض';
$_['text_occupancy_medium'] = 'متوسط';
$_['text_occupancy_high'] = 'عالي';
$_['text_occupancy_full'] = 'ممتلئ';

// مستويات الأولوية
$_['text_priority_low'] = 'منخفضة';
$_['text_priority_normal'] = 'عادية';
$_['text_priority_high'] = 'عالية';
$_['text_priority_critical'] = 'حرجة';
$_['text_priority_urgent'] = 'طارئة';

// الأزرار
$_['button_add'] = 'إضافة موقع جديد';
$_['button_edit'] = 'تعديل';
$_['button_delete'] = 'حذف';
$_['button_view'] = 'عرض';
$_['button_copy'] = 'نسخ';
$_['button_filter'] = 'فلترة';
$_['button_clear'] = 'مسح الفلاتر';
$_['button_refresh'] = 'تحديث';
$_['button_save'] = 'حفظ';
$_['button_cancel'] = 'إلغاء';
$_['button_location_map'] = 'خريطة المواقع';
$_['button_barcode_scanner'] = 'ماسح الباركود';
$_['button_usage_report'] = 'تقرير الاستخدام';
$_['button_generate_qr'] = 'إنشاء QR Code';
$_['button_update_quantities'] = 'تحديث الكميات';
$_['button_scan_barcode'] = 'مسح الباركود';
$_['button_get_gps'] = 'الحصول على الموقع';
$_['button_view_map'] = 'عرض على الخريطة';

// الإحصائيات
$_['text_statistics'] = 'إحصائيات المواقع';
$_['text_total_locations'] = 'إجمالي المواقع';
$_['text_active_locations'] = 'المواقع المفعلة';
$_['text_parent_locations'] = 'المواقع الرئيسية';
$_['text_location_types'] = 'أنواع المواقع';
$_['text_branches_with_locations'] = 'الفروع مع مواقع';
$_['text_warehouses_with_locations'] = 'المستودعات مع مواقع';
$_['text_products_with_locations'] = 'المنتجات مع مواقع';
$_['text_movements_with_locations'] = 'الحركات مع مواقع';
$_['text_total_capacity_units'] = 'إجمالي السعة';
$_['text_total_current_units'] = 'إجمالي الكمية الحالية';
$_['text_overall_usage_percentage'] = 'نسبة الاستخدام الإجمالية';
$_['text_most_used_location'] = 'أكثر المواقع استخداماً';

// خريطة المواقع
$_['text_location_map_title'] = 'خريطة المواقع مع GPS';
$_['text_map_instructions'] = 'انقر على العلامات لعرض تفاصيل الموقع';
$_['text_no_gps_locations'] = 'لا توجد مواقع مع إحداثيات GPS';
$_['text_location_details'] = 'تفاصيل الموقع';
$_['text_navigate_to'] = 'التنقل إلى الموقع';

// ماسح الباركود
$_['text_barcode_scanner_title'] = 'ماسح الباركود للمواقع';
$_['text_scan_instructions'] = 'امسح الباركود أو QR Code للموقع';
$_['text_manual_entry'] = 'إدخال يدوي';
$_['text_scan_result'] = 'نتيجة المسح';
$_['text_location_found'] = 'تم العثور على الموقع';
$_['text_location_not_found'] = 'الموقع غير موجود';
$_['text_start_camera'] = 'تشغيل الكاميرا';
$_['text_stop_camera'] = 'إيقاف الكاميرا';

// تقرير الاستخدام
$_['text_usage_report_title'] = 'تقرير استخدام المواقع';
$_['text_location_usage_summary'] = 'ملخص استخدام المواقع';
$_['text_most_used_locations'] = 'أكثر المواقع استخداماً';
$_['text_least_used_locations'] = 'أقل المواقع استخداماً';
$_['text_empty_locations'] = 'المواقع الفارغة';
$_['text_full_locations'] = 'المواقع الممتلئة';
$_['text_capacity_analysis'] = 'تحليل السعة';
$_['text_efficiency_analysis'] = 'تحليل الكفاءة';

// المواقع الفرعية
$_['text_sub_locations'] = 'المواقع الفرعية';
$_['text_parent_location'] = 'الموقع الرئيسي';
$_['text_add_sub_location'] = 'إضافة موقع فرعي';
$_['text_no_sub_locations'] = 'لا توجد مواقع فرعية';
$_['text_location_hierarchy'] = 'التسلسل الهرمي للمواقع';

// معلومات السعة والبيئة
$_['text_capacity_info'] = 'معلومات السعة';
$_['text_environmental_conditions'] = 'الظروف البيئية';
$_['text_temperature_range'] = 'نطاق درجة الحرارة';
$_['text_humidity_range'] = 'نطاق الرطوبة';
$_['text_current_occupancy'] = 'الإشغال الحالي';
$_['text_available_space'] = 'المساحة المتاحة';

// إعدادات الموقع
$_['text_location_settings'] = 'إعدادات الموقع';
$_['text_operational_settings'] = 'الإعدادات التشغيلية';
$_['text_access_permissions'] = 'صلاحيات الوصول';
$_['text_location_rules'] = 'قواعد الموقع';

// رسائل المساعدة
$_['help_name'] = 'اسم الموقع كما سيظهر في النظام';
$_['help_location_code'] = 'كود فريد للموقع (سيتم إنشاؤه تلقائياً إذا ترك فارغاً)';
$_['help_description'] = 'وصف تفصيلي للموقع واستخدامه';
$_['help_location_type'] = 'نوع الموقع يحدد التسلسل الهرمي والوظائف المتاحة';
$_['help_parent_location'] = 'الموقع الرئيسي الذي يحتوي على هذا الموقع';
$_['help_branch'] = 'الفرع الذي ينتمي إليه الموقع';
$_['help_warehouse'] = 'المستودع الذي يحتوي على الموقع';
$_['help_zone'] = 'المنطقة داخل المستودع';
$_['help_aisle'] = 'رقم أو اسم الممر';
$_['help_rack'] = 'رقم أو اسم الرف';
$_['help_shelf'] = 'رقم أو اسم الرفة';
$_['help_bin'] = 'رقم أو اسم الصندوق';
$_['help_barcode'] = 'الباركود المطبوع على الموقع';
$_['help_qr_code'] = 'QR Code للموقع (سيتم إنشاؤه تلقائياً)';
$_['help_capacity_weight'] = 'أقصى وزن يمكن تخزينه في الموقع بالكيلوجرام';
$_['help_capacity_volume'] = 'أقصى حجم يمكن تخزينه في الموقع باللتر';
$_['help_capacity_units'] = 'أقصى عدد وحدات يمكن تخزينها في الموقع';
$_['help_temperature_range'] = 'نطاق درجة الحرارة المسموح للموقع';
$_['help_humidity_range'] = 'نطاق الرطوبة المسموح للموقع';
$_['help_is_active'] = 'هل الموقع مفعل ومتاح للاستخدام؟';
$_['help_is_pickable'] = 'هل يمكن انتقاء المنتجات من هذا الموقع؟';
$_['help_is_receivable'] = 'هل يمكن استقبال المنتجات في هذا الموقع؟';
$_['help_is_countable'] = 'هل يتم تضمين هذا الموقع في عمليات الجرد؟';
$_['help_priority_level'] = 'أولوية الموقع في عمليات الانتقاء والتخزين';
$_['help_gps_coordinates'] = 'إحداثيات GPS للموقع الجغرافي';
$_['help_sort_order'] = 'ترتيب عرض الموقع في القوائم';

// رسائل الخطأ
$_['error_warning'] = 'تحذير: يرجى التحقق من النموذج بعناية للأخطاء!';
$_['error_permission'] = 'تحذير: ليس لديك صلاحية للوصول إلى إدارة المواقع!';
$_['error_name'] = 'اسم الموقع مطلوب ويجب أن يكون بين 1 و 64 حرف!';
$_['error_location_code'] = 'كود الموقع مطلوب ويجب أن يكون بين 1 و 32 حرف!';
$_['error_location_code_exists'] = 'كود الموقع موجود مسبقاً!';
$_['error_location_type'] = 'نوع الموقع مطلوب!';
$_['error_capacity_negative'] = 'السعة لا يمكن أن تكون سالبة!';
$_['error_temperature_range'] = 'نطاق درجة الحرارة غير صحيح!';
$_['error_humidity_range'] = 'نطاق الرطوبة غير صحيح!';
$_['error_gps_coordinates'] = 'إحداثيات GPS غير صحيحة!';
$_['error_circular_reference'] = 'لا يمكن أن يكون الموقع رئيسياً لنفسه!';
$_['error_location_in_use'] = 'لا يمكن حذف الموقع لأنه مستخدم في النظام!';
$_['error_location_not_found'] = 'الموقع غير موجود!';
$_['error_invalid_barcode'] = 'الباركود غير صحيح!';
$_['error_camera_not_supported'] = 'الكاميرا غير مدعومة في هذا المتصفح!';
$_['error_gps_not_supported'] = 'GPS غير مدعوم في هذا المتصفح!';

// رسائل النجاح
$_['text_location_added'] = 'تم إضافة الموقع بنجاح!';
$_['text_location_updated'] = 'تم تحديث الموقع بنجاح!';
$_['text_location_deleted'] = 'تم حذف الموقع بنجاح!';
$_['text_location_copied'] = 'تم نسخ الموقع بنجاح!';
$_['text_quantities_updated'] = 'تم تحديث الكميات بنجاح!';
$_['text_qr_generated'] = 'تم إنشاء QR Code بنجاح!';
$_['text_barcode_scanned'] = 'تم مسح الباركود بنجاح!';
$_['text_gps_obtained'] = 'تم الحصول على الموقع بنجاح!';

// نصوص التصدير والاستيراد
$_['text_export_locations'] = 'تصدير المواقع';
$_['text_import_locations'] = 'استيراد المواقع';
$_['text_export_success'] = 'تم تصدير المواقع بنجاح';
$_['text_import_success'] = 'تم استيراد %s موقع بنجاح';
$_['text_import_errors'] = 'تم العثور على %s أخطاء أثناء الاستيراد';

// نصوص التحليل
$_['text_location_analysis'] = 'تحليل المواقع';
$_['text_capacity_analysis'] = 'تحليل السعة';
$_['text_usage_analysis'] = 'تحليل الاستخدام';
$_['text_efficiency_analysis'] = 'تحليل الكفاءة';
$_['text_optimization_suggestions'] = 'اقتراحات التحسين';

// نصوص التكامل
$_['text_product_integration'] = 'تكامل المنتجات';
$_['text_inventory_integration'] = 'تكامل المخزون';
$_['text_movement_integration'] = 'تكامل الحركات';
$_['text_warehouse_integration'] = 'تكامل المستودعات';

// نصوص التقارير المتقدمة
$_['text_detailed_report'] = 'تقرير مفصل';
$_['text_summary_report'] = 'تقرير ملخص';
$_['text_comparison_report'] = 'تقرير مقارنة';
$_['text_trend_report'] = 'تقرير الاتجاهات';
$_['text_performance_report'] = 'تقرير الأداء';

// نصوص الإعدادات
$_['text_location_settings'] = 'إعدادات المواقع';
$_['text_display_settings'] = 'إعدادات العرض';
$_['text_map_settings'] = 'إعدادات الخريطة';
$_['text_scanner_settings'] = 'إعدادات الماسح';

// نصوص التنسيق
$_['date_format_short'] = 'd/m/Y';
$_['date_format_long'] = 'd/m/Y H:i:s';
$_['datetime_format'] = 'd/m/Y H:i:s';
$_['number_format_decimal'] = '2';
$_['gps_precision'] = '6';

// نصوص الحالة
$_['text_status_active'] = 'نشط';
$_['text_status_inactive'] = 'غير نشط';
$_['text_status_maintenance'] = 'صيانة';
$_['text_status_blocked'] = 'محجوب';

// نصوص الأولوية
$_['text_priority_1'] = 'منخفضة';
$_['text_priority_2'] = 'عادية';
$_['text_priority_3'] = 'عالية';
$_['text_priority_4'] = 'حرجة';
$_['text_priority_5'] = 'طارئة';

// نصوص التصنيف
$_['text_category_storage'] = 'تخزين';
$_['text_category_picking'] = 'انتقاء';
$_['text_category_receiving'] = 'استقبال';
$_['text_category_shipping'] = 'شحن';
$_['text_category_staging'] = 'تجميع';

// نصوص الجودة
$_['text_quality_excellent'] = 'ممتاز';
$_['text_quality_good'] = 'جيد';
$_['text_quality_average'] = 'متوسط';
$_['text_quality_poor'] = 'ضعيف';

// نصوص الأمان
$_['text_security_high'] = 'أمان عالي';
$_['text_security_medium'] = 'أمان متوسط';
$_['text_security_low'] = 'أمان منخفض';
$_['text_security_public'] = 'عام';

// نصوص البيئة
$_['text_environment_normal'] = 'عادي';
$_['text_environment_cold'] = 'بارد';
$_['text_environment_frozen'] = 'مجمد';
$_['text_environment_heated'] = 'ساخن';
$_['text_environment_controlled'] = 'محكوم';
?>
