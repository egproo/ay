<?php
/**
 * AYM ERP - Supplier Price Agreement Language File (Arabic)
 * 
 * @author AYM ERP Development Team
 * @copyright 2024 AYM ERP
 * @license Commercial License
 * @version 1.0.0
 * @link https://aym-erp.com
 */

// Heading
$_['heading_title']          = 'اتفاقيات الأسعار مع الموردين';

// Text
$_['text_success']           = 'تم: تم تعديل اتفاقيات الأسعار بنجاح!';
$_['text_list']              = 'قائمة اتفاقيات الأسعار';
$_['text_add']               = 'إضافة اتفاقية أسعار';
$_['text_edit']              = 'تعديل اتفاقية أسعار';
$_['text_default']           = 'افتراضي';
$_['text_enabled']           = 'مفعل';
$_['text_disabled']          = 'معطل';
$_['text_yes']               = 'نعم';
$_['text_no']                = 'لا';
$_['text_none']              = 'لا يوجد';
$_['text_select']            = 'اختر';
$_['text_all_suppliers']     = 'جميع الموردين';
$_['text_active']            = 'نشط';
$_['text_inactive']          = 'غير نشط';
$_['text_expired']           = 'منتهي الصلاحية';
$_['text_expiring_soon']     = 'ينتهي قريباً';

// Column
$_['column_agreement_name']  = 'اسم الاتفاقية';
$_['column_supplier']        = 'المورد';
$_['column_start_date']      = 'تاريخ البداية';
$_['column_end_date']        = 'تاريخ النهاية';
$_['column_status']          = 'الحالة';
$_['column_action']          = 'إجراء';
$_['column_product']         = 'المنتج';
$_['column_model']           = 'الموديل';
$_['column_quantity_min']    = 'الحد الأدنى للكمية';
$_['column_quantity_max']    = 'الحد الأقصى للكمية';
$_['column_price']           = 'السعر';
$_['column_discount']        = 'نسبة الخصم %';
$_['column_currency']        = 'العملة';
$_['column_total_items']     = 'عدد المنتجات';
$_['column_date_added']      = 'تاريخ الإضافة';

// Entry
$_['entry_agreement_name']   = 'اسم الاتفاقية';
$_['entry_supplier']         = 'المورد';
$_['entry_description']      = 'الوصف';
$_['entry_start_date']       = 'تاريخ البداية';
$_['entry_end_date']         = 'تاريخ النهاية';
$_['entry_terms']            = 'الشروط والأحكام';
$_['entry_status']           = 'الحالة';
$_['entry_product']          = 'المنتج';
$_['entry_quantity_min']     = 'الحد الأدنى للكمية';
$_['entry_quantity_max']     = 'الحد الأقصى للكمية';
$_['entry_price']            = 'السعر';
$_['entry_discount']         = 'نسبة الخصم %';
$_['entry_currency']         = 'العملة';
$_['entry_item_status']      = 'حالة المنتج';

// Help
$_['help_agreement_name']    = 'اسم مميز لاتفاقية الأسعار مع المورد';
$_['help_description']       = 'وصف تفصيلي لاتفاقية الأسعار والشروط المتفق عليها';
$_['help_start_date']        = 'تاريخ بداية سريان اتفاقية الأسعار';
$_['help_end_date']          = 'تاريخ انتهاء سريان اتفاقية الأسعار';
$_['help_terms']             = 'الشروط والأحكام الخاصة بالاتفاقية مثل شروط الدفع والتسليم';
$_['help_quantity_min']      = 'الحد الأدنى للكمية المطلوبة للحصول على هذا السعر';
$_['help_quantity_max']      = 'الحد الأقصى للكمية (اتركه فارغاً للكمية غير المحدودة)';
$_['help_discount']          = 'نسبة الخصم الإضافية على السعر المحدد';

// Tab
$_['tab_general']            = 'عام';
$_['tab_items']              = 'منتجات الاتفاقية';
$_['tab_terms']              = 'الشروط والأحكام';

// Button
$_['button_add_item']        = 'إضافة منتج';
$_['button_remove_item']     = 'حذف';
$_['button_filter']          = 'فلترة';
$_['button_export']          = 'تصدير';
$_['button_import']          = 'استيراد';
$_['button_copy']            = 'نسخ';
$_['button_renew']           = 'تجديد الاتفاقية';

// Error
$_['error_permission']       = 'تحذير: ليس لديك صلاحية تعديل اتفاقيات الأسعار!';
$_['error_agreement_name']   = 'يجب أن يكون اسم الاتفاقية بين 3 و 64 حرف!';
$_['error_supplier']         = 'مطلوب اختيار المورد!';
$_['error_start_date']       = 'مطلوب تاريخ البداية!';
$_['error_end_date']         = 'مطلوب تاريخ النهاية!';
$_['error_date_range']       = 'تاريخ النهاية يجب أن يكون بعد تاريخ البداية!';
$_['error_product']          = 'مطلوب اختيار المنتج!';
$_['error_price']            = 'السعر مطلوب ويجب أن يكون أكبر من صفر!';
$_['error_quantity_min']     = 'الحد الأدنى للكمية مطلوب!';
$_['error_quantity_range']   = 'الحد الأقصى للكمية يجب أن يكون أكبر من الحد الأدنى!';
$_['error_currency']         = 'مطلوب اختيار العملة!';
$_['error_duplicate_product'] = 'هذا المنتج موجود بالفعل في الاتفاقية!';
$_['error_active_orders']    = 'لا يمكن حذف هذه الاتفاقية لأنها مرتبطة بطلبات نشطة!';

// Success
$_['success_add']            = 'تم إضافة اتفاقية الأسعار بنجاح!';
$_['success_edit']           = 'تم تعديل اتفاقية الأسعار بنجاح!';
$_['success_delete']         = 'تم حذف اتفاقية الأسعار بنجاح!';
$_['success_copy']           = 'تم نسخ اتفاقية الأسعار بنجاح!';
$_['success_renew']          = 'تم تجديد اتفاقية الأسعار بنجاح!';

// Info
$_['info_no_items']          = 'لا توجد منتجات في هذه الاتفاقية';
$_['info_expired']           = 'هذه الاتفاقية منتهية الصلاحية';
$_['info_expiring_soon']     = 'هذه الاتفاقية ستنتهي خلال %d يوم';
$_['info_total_agreements']  = 'إجمالي الاتفاقيات: %d';
$_['info_active_agreements'] = 'الاتفاقيات النشطة: %d';
$_['info_expired_agreements'] = 'الاتفاقيات المنتهية: %d';

// Notification
$_['notification_expiring']  = 'تنبيه: لديك %d اتفاقية أسعار ستنتهي خلال 30 يوم';
$_['notification_expired']   = 'تحذير: لديك %d اتفاقية أسعار منتهية الصلاحية';

// Filter
$_['filter_agreement_name']  = 'اسم الاتفاقية';
$_['filter_supplier']        = 'المورد';
$_['filter_status']          = 'الحالة';
$_['filter_date_start']      = 'من تاريخ';
$_['filter_date_end']        = 'إلى تاريخ';

// Report
$_['report_title']           = 'تقرير اتفاقيات الأسعار';
$_['report_period']          = 'الفترة: %s إلى %s';
$_['report_total_value']     = 'إجمالي قيمة الاتفاقيات';
$_['report_average_discount'] = 'متوسط نسبة الخصم';
$_['report_top_suppliers']   = 'أفضل الموردين';
$_['report_expiring_soon']   = 'الاتفاقيات المنتهية قريباً';
