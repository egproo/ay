<?php
/**
 * ملف اللغة العربية لإدارة الوحدات المتطورة
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

// العنوان الرئيسي
$_['heading_title'] = 'إدارة الوحدات المتطورة';

// النصوص الأساسية
$_['text_success'] = 'تم: تم تعديل الوحدات بنجاح!';
$_['text_list'] = 'قائمة الوحدات';
$_['text_add'] = 'إضافة وحدة';
$_['text_edit'] = 'تعديل وحدة';
$_['text_default'] = 'افتراضي';
$_['text_enabled'] = 'مفعل';
$_['text_disabled'] = 'معطل';
$_['text_yes'] = 'نعم';
$_['text_no'] = 'لا';
$_['text_none'] = '--- لا يوجد ---';
$_['text_select'] = '--- اختر ---';

// أنواع الوحدات
$_['text_unit_type_base'] = 'وحدة أساسية';
$_['text_unit_type_sub'] = 'وحدة فرعية';
$_['text_unit_type_super'] = 'وحدة عليا';

// أعمدة الجدول
$_['column_name'] = 'اسم الوحدة';
$_['column_symbol'] = 'الرمز';
$_['column_type'] = 'النوع';
$_['column_base_unit'] = 'الوحدة الأساسية';
$_['column_conversion_factor'] = 'معامل التحويل';
$_['column_decimal_places'] = 'المنازل العشرية';
$_['column_status'] = 'الحالة';
$_['column_sort_order'] = 'ترتيب العرض';
$_['column_action'] = 'إجراء';

// حقول الإدخال
$_['entry_name'] = 'اسم الوحدة';
$_['entry_symbol'] = 'رمز الوحدة';
$_['entry_description'] = 'الوصف';
$_['entry_type'] = 'نوع الوحدة';
$_['entry_base_unit'] = 'الوحدة الأساسية';
$_['entry_conversion_factor'] = 'معامل التحويل';
$_['entry_decimal_places'] = 'المنازل العشرية';
$_['entry_status'] = 'الحالة';
$_['entry_sort_order'] = 'ترتيب العرض';

// الفلاتر
$_['entry_filter_name'] = 'اسم الوحدة';
$_['entry_filter_type'] = 'نوع الوحدة';
$_['entry_filter_status'] = 'الحالة';

// الأزرار
$_['button_add'] = 'إضافة';
$_['button_edit'] = 'تعديل';
$_['button_delete'] = 'حذف';
$_['button_save'] = 'حفظ';
$_['button_cancel'] = 'إلغاء';
$_['button_clear'] = 'مسح';
$_['button_filter'] = 'فلترة';
$_['button_refresh'] = 'تحديث';
$_['button_create_defaults'] = 'إنشاء وحدات افتراضية';
$_['button_convert'] = 'تحويل';

// رسائل المساعدة
$_['help_name'] = 'اسم الوحدة كما سيظهر في النظام';
$_['help_symbol'] = 'الرمز المختصر للوحدة (مثل: كجم، لتر، قطعة)';
$_['help_type'] = 'نوع الوحدة: أساسية (مستقلة)، فرعية (أصغر من الأساسية)، عليا (أكبر من الأساسية)';
$_['help_base_unit'] = 'الوحدة الأساسية التي تنتمي إليها هذه الوحدة (للوحدات الفرعية والعليا فقط)';
$_['help_conversion_factor'] = 'معامل التحويل إلى الوحدة الأساسية. مثال: 1 كيلو = 1000 جرام، فمعامل الجرام = 0.001';
$_['help_decimal_places'] = 'عدد المنازل العشرية المسموحة في هذه الوحدة';
$_['help_sort_order'] = 'ترتيب عرض الوحدة في القوائم';

// رسائل الخطأ
$_['error_warning'] = 'تحذير: يرجى التحقق من النموذج بعناية للأخطاء!';
$_['error_permission'] = 'تحذير: ليس لديك صلاحية لتعديل الوحدات!';
$_['error_name'] = 'اسم الوحدة يجب أن يكون بين 1 و 64 حرف!';
$_['error_symbol'] = 'رمز الوحدة يجب أن يكون بين 1 و 10 أحرف!';
$_['error_conversion_factor'] = 'معامل التحويل يجب أن يكون رقم أكبر من صفر!';
$_['error_unit_in_use'] = 'تحذير: لا يمكن حذف هذه الوحدة لأنها مستخدمة في المنتجات!';
$_['error_base_unit_required'] = 'يجب اختيار وحدة أساسية للوحدات الفرعية والعليا!';
$_['error_circular_reference'] = 'خطأ: لا يمكن أن تكون الوحدة أساسية لنفسها!';

// رسائل النجاح
$_['text_success_add'] = 'تم: تم إضافة الوحدة بنجاح!';
$_['text_success_edit'] = 'تم: تم تعديل الوحدة بنجاح!';
$_['text_success_delete'] = 'تم: تم حذف الوحدات المحددة بنجاح!';
$_['text_defaults_created'] = 'تم: تم إنشاء الوحدات الافتراضية بنجاح!';

// نصوص التحويل
$_['text_unit_converter'] = 'محول الوحدات';
$_['text_convert_from'] = 'تحويل من';
$_['text_convert_to'] = 'تحويل إلى';
$_['text_quantity'] = 'الكمية';
$_['text_converted_quantity'] = 'الكمية المحولة';
$_['text_conversion_result'] = 'نتيجة التحويل';

// نصوص شجرة الوحدات
$_['text_units_tree'] = 'شجرة الوحدات';
$_['text_base_units'] = 'الوحدات الأساسية';
$_['text_sub_units'] = 'الوحدات الفرعية';
$_['text_super_units'] = 'الوحدات العليا';
$_['text_no_sub_units'] = 'لا توجد وحدات فرعية';

// نصوص الوحدات الافتراضية
$_['text_default_units'] = 'الوحدات الافتراضية';
$_['text_weight_units'] = 'وحدات الوزن';
$_['text_volume_units'] = 'وحدات الحجم';
$_['text_count_units'] = 'وحدات العدد';
$_['text_length_units'] = 'وحدات الطول';
$_['text_area_units'] = 'وحدات المساحة';
$_['text_time_units'] = 'وحدات الوقت';

// أسماء الوحدات الافتراضية
$_['unit_kilogram'] = 'كيلوجرام';
$_['unit_gram'] = 'جرام';
$_['unit_liter'] = 'لتر';
$_['unit_milliliter'] = 'مليلتر';
$_['unit_piece'] = 'قطعة';
$_['unit_box'] = 'صندوق';
$_['unit_carton'] = 'كرتونة';
$_['unit_meter'] = 'متر';
$_['unit_centimeter'] = 'سنتيمتر';
$_['unit_square_meter'] = 'متر مربع';
$_['unit_hour'] = 'ساعة';
$_['unit_minute'] = 'دقيقة';

// رموز الوحدات الافتراضية
$_['symbol_kilogram'] = 'كجم';
$_['symbol_gram'] = 'جم';
$_['symbol_liter'] = 'لتر';
$_['symbol_milliliter'] = 'مل';
$_['symbol_piece'] = 'قطعة';
$_['symbol_box'] = 'صندوق';
$_['symbol_carton'] = 'كرتونة';
$_['symbol_meter'] = 'م';
$_['symbol_centimeter'] = 'سم';
$_['symbol_square_meter'] = 'م²';
$_['symbol_hour'] = 'ساعة';
$_['symbol_minute'] = 'دقيقة';

// نصوص التقارير
$_['text_units_report'] = 'تقرير الوحدات';
$_['text_conversion_report'] = 'تقرير التحويلات';
$_['text_usage_report'] = 'تقرير استخدام الوحدات';

// نصوص الإحصائيات
$_['text_total_units'] = 'إجمالي الوحدات';
$_['text_base_units_count'] = 'عدد الوحدات الأساسية';
$_['text_sub_units_count'] = 'عدد الوحدات الفرعية';
$_['text_super_units_count'] = 'عدد الوحدات العليا';
$_['text_active_units'] = 'الوحدات النشطة';
$_['text_inactive_units'] = 'الوحدات غير النشطة';

// نصوص التحقق
$_['text_validation'] = 'التحقق من صحة البيانات';
$_['text_validate_conversion'] = 'التحقق من صحة التحويل';
$_['text_test_conversion'] = 'اختبار التحويل';

// نصوص الاستيراد والتصدير
$_['text_import_units'] = 'استيراد الوحدات';
$_['text_export_units'] = 'تصدير الوحدات';
$_['text_import_from_excel'] = 'استيراد من Excel';
$_['text_export_to_excel'] = 'تصدير إلى Excel';

// نصوص التكامل
$_['text_integration'] = 'التكامل مع النظام';
$_['text_products_using_unit'] = 'المنتجات التي تستخدم هذه الوحدة';
$_['text_pricing_using_unit'] = 'التسعير الذي يستخدم هذه الوحدة';
$_['text_barcodes_using_unit'] = 'الباركود الذي يستخدم هذه الوحدة';

// نصوص المساعدة المتقدمة
$_['help_advanced_units'] = 'نظام الوحدات المتطور يسمح بإنشاء وحدات أساسية وفرعية وعليا مع تحويل تلقائي بينها';
$_['help_conversion_examples'] = 'أمثلة التحويل: 1 كيلو = 1000 جرام، 1 صندوق = 12 قطعة، 1 كرتونة = 24 قطعة';
$_['help_decimal_places_examples'] = 'أمثلة المنازل العشرية: الجرام = 0، الكيلو = 3، اللتر = 3';

// نصوص الأمان
$_['text_security'] = 'الأمان والصلاحيات';
$_['text_audit_log'] = 'سجل المراجعة';
$_['text_change_history'] = 'تاريخ التغييرات';

// نصوص الأداء
$_['text_performance'] = 'الأداء والتحسين';
$_['text_cache_units'] = 'تخزين الوحدات مؤقتاً';
$_['text_optimize_conversions'] = 'تحسين التحويلات';

// نصوص متنوعة
$_['text_no_results'] = 'لا توجد نتائج!';
$_['text_confirm'] = 'هل أنت متأكد؟';
$_['text_loading'] = 'جاري التحميل...';
$_['text_processing'] = 'جاري المعالجة...';
$_['text_please_wait'] = 'يرجى الانتظار...';

// نصوص التنبيهات
$_['alert_unit_in_use'] = 'تنبيه: هذه الوحدة مستخدمة في المنتجات ولا يمكن حذفها';
$_['alert_conversion_factor_changed'] = 'تنبيه: تغيير معامل التحويل سيؤثر على جميع المنتجات التي تستخدم هذه الوحدة';
$_['alert_base_unit_changed'] = 'تنبيه: تغيير الوحدة الأساسية سيؤثر على التحويلات';

// نصوص التوجيه
$_['guide_creating_units'] = 'دليل إنشاء الوحدات';
$_['guide_conversion_factors'] = 'دليل معاملات التحويل';
$_['guide_unit_types'] = 'دليل أنواع الوحدات';
$_['guide_best_practices'] = 'أفضل الممارسات';

// نصوص الأمثلة
$_['example_weight_system'] = 'مثال: نظام الوزن (كيلو، جرام، طن)';
$_['example_volume_system'] = 'مثال: نظام الحجم (لتر، مليلتر، جالون)';
$_['example_count_system'] = 'مثال: نظام العدد (قطعة، صندوق، كرتونة)';

// نصوص التحديثات
$_['text_updates'] = 'التحديثات والتطوير';
$_['text_version_history'] = 'تاريخ الإصدارات';
$_['text_new_features'] = 'الميزات الجديدة';
$_['text_improvements'] = 'التحسينات';

// نصوص الدعم
$_['text_support'] = 'الدعم والمساعدة';
$_['text_documentation'] = 'الوثائق';
$_['text_tutorials'] = 'الدروس التعليمية';
$_['text_faq'] = 'الأسئلة الشائعة';
$_['text_contact_support'] = 'اتصل بالدعم';
?>
