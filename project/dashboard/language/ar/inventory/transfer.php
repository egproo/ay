<?php
// العنوان
$_['heading_title']          = 'التحويلات المخزنية';

// النص
$_['text_success']           = 'تم تعديل التحويل المخزني بنجاح!';
$_['text_list']              = 'قائمة التحويلات المخزنية';
$_['text_add']               = 'إضافة تحويل مخزني';
$_['text_edit']              = 'تعديل تحويل مخزني';
$_['text_form']              = 'نموذج التحويل المخزني';
$_['text_select']            = '--- اختر ---';
$_['text_pending']           = 'معلق';
$_['text_confirmed']         = 'مؤكد';
$_['text_cancelled']         = 'ملغي';
$_['text_products']          = 'المنتجات';
$_['text_in_transit']        = 'قيد النقل';
$_['text_completed']         = 'مكتمل';
$_['text_rejected']          = 'مرفوض';
$_['text_approve']           = 'تأكيد التحويل';
$_['text_reject']            = 'رفض التحويل';
$_['text_confirm_approve']   = 'هل أنت متأكد من تأكيد هذا التحويل؟';
$_['text_confirm_reject']    = 'هل أنت متأكد من رفض هذا التحويل؟';
$_['text_confirm_delete']    = 'هل أنت متأكد من حذف هذا التحويل؟';
$_['text_confirm_in_transit'] = 'هل أنت متأكد من تحديث حالة التحويل إلى قيد النقل؟';
$_['text_confirm_complete']  = 'هل أنت متأكد من تحديث حالة التحويل إلى مكتمل؟';
$_['text_approve_success']   = 'تم تأكيد التحويل المخزني بنجاح!';
$_['text_reject_success']    = 'تم رفض التحويل المخزني بنجاح!';
$_['text_in_transit_success'] = 'تم تحديث حالة التحويل المخزني إلى قيد النقل بنجاح!';
$_['text_complete_success']  = 'تم تحديث حالة التحويل المخزني إلى مكتمل بنجاح!';
$_['text_available']         = 'متاح';
$_['text_no_stock']          = 'لا يوجد مخزون';
$_['text_available_quantity'] = 'الكمية المتاحة: %s';
$_['text_check_availability'] = 'التحقق من التوفر';
$_['text_loading_units']     = 'جاري تحميل الوحدات...';
$_['text_loading_availability'] = 'جاري التحقق من التوفر...';
$_['text_product_available'] = 'المنتج متوفر';
$_['text_product_unavailable'] = 'المنتج غير متوفر';

// الأعمدة
$_['column_reference']       = 'رقم المرجع';
$_['column_from_branch']     = 'من فرع';
$_['column_to_branch']       = 'إلى فرع';
$_['column_transfer_date']   = 'تاريخ التحويل';
$_['column_status']          = 'الحالة';
$_['column_created_by']      = 'تم بواسطة';
$_['column_product']         = 'المنتج';
$_['column_unit']            = 'الوحدة';
$_['column_quantity']        = 'الكمية';
$_['column_notes']           = 'ملاحظات';
$_['column_action']          = 'تحرير';

// المدخلات
$_['entry_reference']        = 'رقم المرجع';
$_['entry_from_branch']      = 'من فرع';
$_['entry_to_branch']        = 'إلى فرع';
$_['entry_transfer_date']    = 'تاريخ التحويل';
$_['entry_status']           = 'الحالة';
$_['entry_notes']            = 'ملاحظات';
$_['entry_product']          = 'المنتج';
$_['entry_unit']             = 'الوحدة';
$_['entry_quantity']         = 'الكمية';

// الأزرار
$_['button_add']             = 'إضافة';
$_['button_edit']            = 'تعديل';
$_['button_delete']          = 'حذف';
$_['button_save']            = 'حفظ';
$_['button_cancel']          = 'إلغاء';
$_['button_filter']          = 'تصفية';
$_['button_approve']         = 'تأكيد';
$_['button_reject']          = 'رفض';
$_['button_in_transit']      = 'قيد النقل';
$_['button_complete']        = 'إكمال';

// التصفية
$_['filter_reference']       = 'رقم المرجع';
$_['filter_from_branch']     = 'من فرع';
$_['filter_to_branch']       = 'إلى فرع';
$_['filter_status']          = 'الحالة';
$_['filter_date_start']      = 'تاريخ البداية';
$_['filter_date_end']        = 'تاريخ النهاية';

// رسائل الخطأ
$_['error_permission']       = 'تحذير: ليس لديك صلاحية لتعديل التحويلات المخزنية!';
$_['error_reference_number'] = 'رقم المرجع مطلوب!';
$_['error_from_branch']      = 'الفرع المصدر مطلوب!';
$_['error_to_branch']        = 'الفرع المستلم مطلوب!';
$_['error_same_branch']      = 'لا يمكن التحويل لنفس الفرع!';
$_['error_transfer_date']    = 'تاريخ التحويل مطلوب!';
$_['error_products']         = 'يجب إضافة منتج واحد على الأقل!';
$_['error_quantity']         = 'الكمية يجب أن تكون أكبر من صفر!';
$_['error_unit']             = 'الوحدة مطلوبة!';
$_['error_transfer_id']      = 'معرف التحويل مطلوب!';
$_['error_missing_parameters'] = 'المعلمات المطلوبة مفقودة!';
$_['error_insufficient_stock'] = 'الكمية المطلوبة غير متوفرة في المخزن!';
$_['error_product']           = 'يرجى اختيار المنتج!';
$_['error_insufficient_quantity'] = 'الكمية المطلوبة غير متوفرة! الكمية المتاحة: %s';