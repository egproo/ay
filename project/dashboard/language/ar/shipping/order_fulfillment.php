<?php
/**
 * ملف اللغة العربية لنظام تجهيز الطلبات المتقدم
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

// العناوين الرئيسية
$_['heading_title']                    = 'نظام تجهيز الطلبات المتقدم';
$_['text_fulfill_order']               = 'تجهيز الطلب';
$_['text_fulfillment_dashboard']       = 'لوحة تحكم تجهيز الطلبات';

// النصوص العامة
$_['text_list']                        = 'قائمة الطلبات الجاهزة للتجهيز';
$_['text_add']                         = 'إضافة تجهيز جديد';
$_['text_edit']                        = 'تعديل التجهيز';
$_['text_default']                     = 'افتراضي';
$_['text_enabled']                     = 'مفعل';
$_['text_disabled']                    = 'معطل';
$_['text_yes']                         = 'نعم';
$_['text_no']                          = 'لا';
$_['text_none']                        = 'لا يوجد';
$_['text_select']                      = 'اختر';
$_['text_all_zones']                   = 'جميع المناطق';

// نصوص التجهيز
$_['text_order_details']               = 'تفاصيل الطلب';
$_['text_customer_information']        = 'معلومات العميل';
$_['text_shipping_address']            = 'عنوان الشحن';
$_['text_order_products']              = 'منتجات الطلب';
$_['text_fulfillment_information']     = 'معلومات التجهيز';
$_['text_package_details']             = 'تفاصيل الطرد';
$_['text_shipping_options']            = 'خيارات الشحن';
$_['text_available_stock']             = 'المخزون المتاح';
$_['text_required_quantity']           = 'الكمية المطلوبة';
$_['text_can_fulfill']                 = 'يمكن التجهيز';
$_['text_cannot_fulfill']              = 'لا يمكن التجهيز';
$_['text_stock_shortage']              = 'نقص في المخزون';

// حالات التجهيز
$_['text_status_pending']              = 'في الانتظار';
$_['text_status_processing']           = 'قيد المعالجة';
$_['text_status_picking']              = 'قيد الانتقاء';
$_['text_status_packing']              = 'قيد التعبئة';
$_['text_status_ready_to_ship']        = 'جاهز للشحن';
$_['text_status_shipped']              = 'تم الشحن';
$_['text_status_completed']            = 'مكتمل';
$_['text_status_cancelled']            = 'ملغي';

// نصوص الشحن
$_['text_shipping_company']            = 'شركة الشحن';
$_['text_shipping_cost']               = 'تكلفة الشحن';
$_['text_cod_amount']                  = 'مبلغ الدفع عند الاستلام';
$_['text_tracking_number']             = 'رقم التتبع';
$_['text_estimated_delivery']          = 'التاريخ المتوقع للتسليم';
$_['text_special_instructions']        = 'تعليمات خاصة';

// نصوص التعبئة
$_['text_package_weight']              = 'وزن الطرد (كجم)';
$_['text_package_dimensions']          = 'أبعاد الطرد (سم)';
$_['text_package_length']              = 'الطول';
$_['text_package_width']               = 'العرض';
$_['text_package_height']              = 'الارتفاع';
$_['text_packing_notes']               = 'ملاحظات التعبئة';
$_['text_fragile_items']               = 'أصناف قابلة للكسر';
$_['text_handle_with_care']            = 'يُرجى التعامل بحذر';

// نصوص الإحصائيات
$_['text_ready_orders']                = 'الطلبات الجاهزة للتجهيز';
$_['text_today_fulfilled']             = 'الطلبات المجهزة اليوم';
$_['text_avg_fulfillment_time']        = 'متوسط وقت التجهيز (ساعة)';
$_['text_fulfillment_rate']            = 'معدل التجهيز';
$_['text_pending_fulfillment']         = 'في انتظار التجهيز';
$_['text_completed_fulfillment']       = 'تم التجهيز';

// نصوص الأزرار والإجراءات
$_['button_fulfill']                   = 'تجهيز الطلب';
$_['button_print_picking_list']        = 'طباعة قائمة الانتقاء';
$_['button_print_packing_slip']        = 'طباعة بوليصة التعبئة';
$_['button_create_shipment']           = 'إنشاء أمر شحن';
$_['button_update_status']             = 'تحديث الحالة';
$_['button_view_order']                = 'عرض الطلب';
$_['button_refresh']                   = 'تحديث';

// رسائل النجاح
$_['text_success']                     = 'تم الحفظ بنجاح!';
$_['text_order_fulfilled']             = 'تم تجهيز الطلب بنجاح!';
$_['text_status_updated']              = 'تم تحديث الحالة بنجاح!';
$_['text_shipment_created']            = 'تم إنشاء أمر الشحن بنجاح!';

// رسائل الخطأ
$_['error_permission']                 = 'تحذير: ليس لديك صلاحية للوصول إلى هذه الصفحة!';
$_['error_order_not_found']            = 'خطأ: الطلب غير موجود!';
$_['error_package_weight']             = 'خطأ: يجب إدخال وزن الطرد!';
$_['error_package_dimensions']         = 'خطأ: يجب إدخال أبعاد الطرد!';
$_['error_shipping_company']           = 'خطأ: يجب اختيار شركة الشحن!';
$_['error_insufficient_stock']         = 'خطأ: المخزون غير كافي لتجهيز الطلب!';
$_['error_already_fulfilled']          = 'خطأ: تم تجهيز هذا الطلب مسبقاً!';
$_['error_required_fields']            = 'خطأ: يجب ملء جميع الحقول المطلوبة!';

// عناوين الأعمدة
$_['column_order_id']                  = 'رقم الطلب';
$_['column_customer']                  = 'العميل';
$_['column_email']                     = 'البريد الإلكتروني';
$_['column_telephone']                 = 'الهاتف';
$_['column_total']                     = 'الإجمالي';
$_['column_status']                    = 'الحالة';
$_['column_date_added']                = 'تاريخ الطلب';
$_['column_product_count']             = 'عدد المنتجات';
$_['column_total_quantity']            = 'إجمالي الكمية';
$_['column_action']                    = 'الإجراء';

// عناوين أعمدة المنتجات
$_['column_product_name']              = 'اسم المنتج';
$_['column_model']                     = 'الموديل';
$_['column_quantity']                  = 'الكمية';
$_['column_unit']                      = 'الوحدة';
$_['column_price']                     = 'السعر';
$_['column_stock_quantity']            = 'كمية المخزون';
$_['column_location']                  = 'الموقع';
$_['column_can_fulfill']               = 'يمكن التجهيز';

// نصوص المرشحات
$_['text_filter']                      = 'مرشح';
$_['entry_filter_order_id']            = 'رقم الطلب';
$_['entry_filter_customer']            = 'العميل';
$_['entry_filter_date_start']          = 'تاريخ البداية';
$_['entry_filter_date_end']            = 'تاريخ النهاية';
$_['entry_filter_status']              = 'الحالة';

// نصوص النماذج
$_['entry_package_weight']             = 'وزن الطرد';
$_['entry_package_dimensions']         = 'أبعاد الطرد';
$_['entry_packing_notes']              = 'ملاحظات التعبئة';
$_['entry_shipping_company']           = 'شركة الشحن';
$_['entry_shipping_cost']              = 'تكلفة الشحن';
$_['entry_cod_amount']                 = 'مبلغ COD';
$_['entry_special_instructions']       = 'تعليمات خاصة';

// نصوص المساعدة
$_['help_package_weight']              = 'أدخل الوزن الإجمالي للطرد بالكيلوجرام';
$_['help_package_dimensions']          = 'أدخل الأبعاد بالصيغة: طول×عرض×ارتفاع (بالسنتيمتر)';
$_['help_packing_notes']               = 'أي ملاحظات خاصة بالتعبئة أو التعامل مع الطرد';
$_['help_special_instructions']        = 'تعليمات خاصة لشركة الشحن أو العميل';

// نصوص التنبيهات
$_['text_alert_stock_shortage']        = 'تنبيه: يوجد نقص في المخزون لبعض المنتجات';
$_['text_alert_fragile_items']         = 'تنبيه: يحتوي الطلب على أصناف قابلة للكسر';
$_['text_alert_heavy_package']         = 'تنبيه: الطرد ثقيل الوزن';
$_['text_alert_oversized_package']     = 'تنبيه: الطرد كبير الحجم';

// نصوص التقارير
$_['text_fulfillment_report']          = 'تقرير التجهيز';
$_['text_daily_fulfillment']           = 'التجهيز اليومي';
$_['text_weekly_fulfillment']          = 'التجهيز الأسبوعي';
$_['text_monthly_fulfillment']         = 'التجهيز الشهري';
$_['text_performance_metrics']         = 'مؤشرات الأداء';

// نصوص الطباعة
$_['text_picking_list']                = 'قائمة الانتقاء';
$_['text_packing_slip']                = 'بوليصة التعبئة';
$_['text_shipping_label']              = 'ملصق الشحن';
$_['text_print_date']                  = 'تاريخ الطباعة';
$_['text_prepared_by']                 = 'تم التحضير بواسطة';

// نصوص التاريخ والوقت
$_['text_date_format']                 = 'd/m/Y';
$_['text_datetime_format']             = 'd/m/Y H:i';
$_['text_time_format']                 = 'H:i';

// نصوص الوحدات
$_['text_kg']                          = 'كجم';
$_['text_cm']                          = 'سم';
$_['text_piece']                       = 'قطعة';
$_['text_box']                         = 'صندوق';
$_['text_package']                     = 'طرد';

// نصوص الحالات المتقدمة
$_['text_partially_fulfilled']        = 'مجهز جزئياً';
$_['text_backordered']                 = 'طلب مؤجل';
$_['text_on_hold']                     = 'معلق';
$_['text_priority_order']              = 'طلب عاجل';
$_['text_express_shipping']            = 'شحن سريع';
$_['text_standard_shipping']           = 'شحن عادي';

// نصوص التكامل
$_['text_inventory_integration']       = 'التكامل مع المخزون';
$_['text_accounting_integration']      = 'التكامل المحاسبي';
$_['text_shipping_integration']        = 'التكامل مع الشحن';
$_['text_notification_sent']           = 'تم إرسال الإشعار';
$_['text_email_notification']          = 'إشعار بريد إلكتروني';
$_['text_sms_notification']            = 'إشعار رسالة نصية';
?>
