<?php
/**
 * AYM ERP System: Order Preparation Language File (Arabic)
 * 
 * ملف اللغة العربية لتجهيز الطلبات - مطور للشركات الحقيقية
 * 
 * @package    AYM ERP
 * @author     AYM Development Team
 * @copyright  2024 AYM ERP Systems
 * @license    Commercial License
 * @version    1.0.0
 * @since      2024-01-15
 */

// العناوين الرئيسية
$_['heading_title']                    = 'تجهيز الطلبات للشحن';
$_['heading_title_preparation']        = 'إدارة تجهيز الطلبات المتقدمة';

// النصوص الأساسية
$_['text_list']                        = 'قائمة الطلبات المطلوب تجهيزها';
$_['text_add']                         = 'إضافة';
$_['text_edit']                        = 'تعديل';
$_['text_default']                     = 'افتراضي';
$_['text_enabled']                     = 'مفعل';
$_['text_disabled']                    = 'معطل';
$_['text_yes']                         = 'نعم';
$_['text_no']                          = 'لا';
$_['text_none']                        = 'لا يوجد';
$_['text_select']                      = 'اختيار';
$_['text_all_zones']                   = 'جميع المناطق';

// عناوين الأعمدة
$_['column_order_id']                  = 'رقم الطلب';
$_['column_customer']                  = 'العميل';
$_['column_status']                    = 'حالة التجهيز';
$_['column_priority']                  = 'الأولوية';
$_['column_total']                     = 'إجمالي الطلب';
$_['column_date_added']                = 'تاريخ الطلب';
$_['column_date_modified']             = 'تاريخ التعديل';
$_['column_action']                    = 'إجراء';
$_['column_items_count']               = 'عدد العناصر';
$_['column_prepared_items']            = 'العناصر المُجهزة';
$_['column_preparation_percentage']    = 'نسبة التجهيز';
$_['column_assigned_user']             = 'المسؤول عن التجهيز';
$_['column_preparation_started']       = 'بدء التجهيز';
$_['column_preparation_completed']     = 'اكتمال التجهيز';

// حقول النموذج
$_['entry_order_id']                   = 'رقم الطلب';
$_['entry_customer']                   = 'العميل';
$_['entry_status']                     = 'حالة التجهيز';
$_['entry_priority']                   = 'الأولوية';
$_['entry_date_from']                  = 'من تاريخ';
$_['entry_date_to']                    = 'إلى تاريخ';
$_['entry_branch']                     = 'الفرع';
$_['entry_assigned_user']              = 'المسؤول عن التجهيز';
$_['entry_notes']                      = 'ملاحظات';
$_['entry_preparation_notes']          = 'ملاحظات التجهيز';

// حالات التجهيز
$_['text_status_pending']              = 'في الانتظار';
$_['text_status_in_progress']          = 'قيد التجهيز';
$_['text_status_ready_for_shipping']   = 'جاهز للشحن';
$_['text_status_shipped']              = 'تم الشحن';
$_['text_status_completed']            = 'مكتمل';
$_['text_status_cancelled']            = 'ملغي';
$_['text_status_on_hold']              = 'معلق';

// مستويات الأولوية
$_['text_priority_low']                = 'منخفضة';
$_['text_priority_normal']             = 'عادية';
$_['text_priority_high']               = 'عالية';
$_['text_priority_urgent']             = 'عاجلة';

// الأزرار
$_['button_filter']                    = 'فلترة';
$_['button_clear_filter']              = 'مسح الفلتر';
$_['button_print_picking_list']        = 'طباعة قائمة التجهيز';
$_['button_print_multiple']            = 'طباعة متعددة';
$_['button_update_status']             = 'تحديث الحالة';
$_['button_update_priority']           = 'تحديث الأولوية';
$_['button_assign_user']               = 'تعيين مسؤول';
$_['button_start_preparation']         = 'بدء التجهيز';
$_['button_complete_preparation']      = 'إنهاء التجهيز';
$_['button_mark_ready']                = 'تحديد كجاهز';
$_['button_mark_shipped']              = 'تحديد كمشحون';
$_['button_view_details']              = 'عرض التفاصيل';
$_['button_preparation_history']       = 'تاريخ التجهيز';

// النصوص العامة
$_['text_preparation_dashboard']       = 'لوحة تجهيز الطلبات';
$_['text_preparation_statistics']      = 'إحصائيات التجهيز';
$_['text_pending_orders']              = 'الطلبات في الانتظار';
$_['text_in_progress_orders']          = 'الطلبات قيد التجهيز';
$_['text_ready_orders']                = 'الطلبات الجاهزة';
$_['text_shipped_orders']              = 'الطلبات المشحونة';
$_['text_total_items']                 = 'إجمالي العناصر';
$_['text_prepared_items']              = 'العناصر المُجهزة';
$_['text_preparation_percentage']      = 'نسبة التجهيز';

// قائمة التجهيز (Picking List)
$_['text_picking_list']                = 'قائمة التجهيز';
$_['text_picking_list_title']          = 'قائمة تجهيز الطلب رقم: %s';
$_['text_order_information']           = 'معلومات الطلب';
$_['text_customer_information']        = 'معلومات العميل';
$_['text_shipping_information']        = 'معلومات الشحن';
$_['text_products_to_pick']            = 'المنتجات المطلوب تجهيزها';
$_['text_product_name']                = 'اسم المنتج';
$_['text_product_model']               = 'موديل المنتج';
$_['text_product_sku']                 = 'كود المنتج';
$_['text_quantity_ordered']            = 'الكمية المطلوبة';
$_['text_quantity_prepared']           = 'الكمية المُجهزة';
$_['text_unit']                        = 'الوحدة';
$_['text_location']                    = 'الموقع';
$_['text_zone']                        = 'المنطقة';
$_['text_barcode']                     = 'الباركود';
$_['text_notes']                       = 'ملاحظات';

// معلومات المنتج في التجهيز
$_['text_product_options']             = 'خيارات المنتج';
$_['text_product_location']            = 'موقع المنتج';
$_['text_stock_available']             = 'المخزون المتاح';
$_['text_reserved_quantity']           = 'الكمية المحجوزة';
$_['text_pick_quantity']               = 'الكمية المطلوب تجهيزها';

// الإجراءات والعمليات
$_['text_bulk_actions']                = 'الإجراءات المجمعة';
$_['text_select_action']               = 'اختر إجراء';
$_['text_update_status_bulk']          = 'تحديث الحالة للمحدد';
$_['text_update_priority_bulk']        = 'تحديث الأولوية للمحدد';
$_['text_print_picking_lists']         = 'طباعة قوائم التجهيز';
$_['text_assign_user_bulk']            = 'تعيين مسؤول للمحدد';

// رسائل النجاح
$_['text_success']                     = 'تم بنجاح!';
$_['text_status_updated']              = 'تم تحديث حالة التجهيز بنجاح!';
$_['text_priority_updated']            = 'تم تحديث الأولوية بنجاح!';
$_['text_user_assigned']               = 'تم تعيين المسؤول بنجاح!';
$_['text_preparation_started']         = 'تم بدء التجهيز بنجاح!';
$_['text_preparation_completed']       = 'تم إنهاء التجهيز بنجاح!';
$_['text_item_status_updated']         = 'تم تحديث حالة العنصر بنجاح!';
$_['text_picking_lists_generated']     = 'تم إنشاء قوائم التجهيز بنجاح!';
$_['text_customer_notified']           = 'تم إرسال إشعار للعميل بنجاح!';

// رسائل الخطأ
$_['error_permission']                 = 'تحذير: ليس لديك صلاحية للوصول إلى هذه الصفحة!';
$_['error_order_not_found']            = 'الطلب غير موجود!';
$_['error_invalid_status']             = 'حالة التجهيز غير صحيحة!';
$_['error_invalid_priority']           = 'الأولوية غير صحيحة!';
$_['error_update_status']              = 'فشل في تحديث حالة التجهيز!';
$_['error_update_priority']            = 'فشل في تحديث الأولوية!';
$_['error_update_item_status']         = 'فشل في تحديث حالة العنصر!';
$_['error_assign_user']                = 'فشل في تعيين المسؤول!';
$_['error_required_fields']            = 'يرجى ملء جميع الحقول المطلوبة!';
$_['error_no_orders_selected']         = 'لم يتم اختيار أي طلبات!';
$_['error_no_valid_orders']            = 'لا توجد طلبات صحيحة محددة!';
$_['error_insufficient_stock']         = 'المخزون غير كافي!';
$_['error_preparation_conflict']       = 'تعارض في عملية التجهيز!';

// الإشعارات والتنبيهات
$_['text_notification_status_change']  = 'تم تغيير حالة طلبكم رقم %s إلى: %s';
$_['text_notification_ready_shipping'] = 'طلبكم رقم %s جاهز للشحن';
$_['text_notification_shipped']        = 'تم شحن طلبكم رقم %s';
$_['text_order_status_update_subject'] = 'تحديث حالة الطلب رقم %s';
$_['text_order_status_update_message'] = 'عزيزي %s، تم تحديث حالة طلبكم رقم %s إلى: %s';

// التقارير والإحصائيات
$_['text_preparation_report']          = 'تقرير التجهيز';
$_['text_daily_preparation']           = 'التجهيز اليومي';
$_['text_weekly_preparation']          = 'التجهيز الأسبوعي';
$_['text_monthly_preparation']         = 'التجهيز الشهري';
$_['text_user_performance']            = 'أداء المستخدمين';
$_['text_preparation_efficiency']      = 'كفاءة التجهيز';
$_['text_average_preparation_time']    = 'متوسط وقت التجهيز';
$_['text_orders_per_hour']             = 'الطلبات في الساعة';
$_['text_items_per_hour']              = 'العناصر في الساعة';

// الفلاتر المتقدمة
$_['text_advanced_filters']            = 'الفلاتر المتقدمة';
$_['text_filter_by_status']            = 'فلترة حسب الحالة';
$_['text_filter_by_priority']          = 'فلترة حسب الأولوية';
$_['text_filter_by_user']              = 'فلترة حسب المسؤول';
$_['text_filter_by_date']              = 'فلترة حسب التاريخ';
$_['text_filter_by_customer']          = 'فلترة حسب العميل';
$_['text_filter_by_branch']            = 'فلترة حسب الفرع';

// التصدير والطباعة
$_['text_export_options']              = 'خيارات التصدير';
$_['text_export_excel']                = 'تصدير Excel';
$_['text_export_pdf']                  = 'تصدير PDF';
$_['text_export_csv']                  = 'تصدير CSV';
$_['text_print_options']               = 'خيارات الطباعة';
$_['text_print_summary']               = 'طباعة الملخص';
$_['text_print_detailed']              = 'طباعة مفصلة';

// الإعدادات
$_['text_preparation_settings']        = 'إعدادات التجهيز';
$_['text_auto_assign_users']           = 'تعيين المستخدمين تلقائياً';
$_['text_notify_customers']            = 'إشعار العملاء تلقائياً';
$_['text_preparation_time_limit']      = 'الحد الزمني للتجهيز';
$_['text_priority_rules']              = 'قواعد الأولوية';
$_['text_barcode_scanning']            = 'مسح الباركود';

// التكامل مع الأنظمة الأخرى
$_['text_inventory_integration']       = 'التكامل مع المخزون';
$_['text_shipping_integration']        = 'التكامل مع الشحن';
$_['text_accounting_integration']      = 'التكامل مع المحاسبة';
$_['text_notification_integration']    = 'التكامل مع الإشعارات';

// المساعدة والدعم
$_['text_help']                        = 'المساعدة';
$_['text_preparation_guide']           = 'دليل التجهيز';
$_['text_best_practices']              = 'أفضل الممارسات';
$_['text_troubleshooting']             = 'حل المشاكل';
$_['text_contact_support']             = 'اتصل بالدعم';

// التنسيق والعرض
$_['date_format_short']                = 'd/m/Y';
$_['date_format_long']                 = 'd/m/Y H:i:s';
$_['time_format']                      = 'H:i:s';
$_['currency_format']                  = '%s %s';
$_['percentage_format']                = '%s%%';

// الترقيم والنتائج
$_['text_pagination']                  = 'عرض %d إلى %d من %d (%d صفحات)';
$_['text_no_results']                  = 'لا توجد طلبات للتجهيز';
$_['text_loading']                     = 'جاري التحميل...';
$_['text_processing']                  = 'جاري المعالجة...';

// الحالات المختلفة
$_['text_all_statuses']                = 'جميع الحالات';
$_['text_all_priorities']              = 'جميع الأولويات';
$_['text_all_users']                   = 'جميع المستخدمين';
$_['text_all_branches']                = 'جميع الفروع';

// breadcrumbs
$_['text_home']                        = 'الرئيسية';
$_['text_shipping']                    = 'الشحن والتوزيع';

// متنوعة
$_['text_order_details']               = 'تفاصيل الطلب';
$_['text_customer_details']            = 'تفاصيل العميل';
$_['text_shipping_details']            = 'تفاصيل الشحن';
$_['text_preparation_history']         = 'تاريخ التجهيز';
$_['text_preparation_notes']           = 'ملاحظات التجهيز';
$_['text_internal_notes']              = 'ملاحظات داخلية';
$_['text_special_instructions']        = 'تعليمات خاصة';
$_['text_handling_instructions']       = 'تعليمات التعامل';
$_['text_packaging_instructions']      = 'تعليمات التعبئة';
?>
