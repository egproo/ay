<?php
/**
 * ملف اللغة العربية - مدفوعات الأقساط
 * Arabic Language File - Installment Payments
 *
 * @author ERP Team
 * @version 2.0
 * @since 2024
 */

// العناوين الرئيسية
$_['heading_title']         = 'مدفوعات الأقساط';
$_['heading_title_add']     = 'إضافة مدفوعة قسط';
$_['heading_title_edit']    = 'تعديل مدفوعة قسط';
$_['heading_title_view']    = 'عرض مدفوعة قسط';

// النصوص العامة
$_['text_success_add']      = 'تم تسجيل مدفوعة القسط بنجاح!';
$_['text_success_edit']     = 'تم تعديل مدفوعة القسط بنجاح!';
$_['text_success_delete']   = 'تم حذف مدفوعات الأقساط المحددة بنجاح!';
$_['text_list']             = 'قائمة مدفوعات الأقساط';
$_['text_add']              = 'إضافة مدفوعة جديدة';
$_['text_edit']             = 'تعديل المدفوعة';
$_['text_view']             = 'عرض المدفوعة';
$_['text_enabled']          = 'مفعل';
$_['text_disabled']         = 'معطل';
$_['text_all']              = 'الكل';
$_['text_confirm']          = 'هل أنت متأكد من الحذف؟';
$_['text_home']             = 'الرئيسية';

// طرق الدفع
$_['text_cash']             = 'نقدي';
$_['text_bank_transfer']    = 'تحويل بنكي';
$_['text_check']            = 'شيك';
$_['text_credit_card']      = 'بطاقة ائتمان';
$_['text_mobile_wallet']    = 'محفظة إلكترونية';
$_['text_other']            = 'أخرى';

// حالات المدفوعة
$_['text_status_confirmed'] = 'مؤكدة';
$_['text_status_pending']   = 'معلقة';
$_['text_status_cancelled'] = 'ملغية';

// أعمدة الجدول
$_['column_payment_id']     = 'رقم المدفوعة';
$_['column_customer']       = 'العميل';
$_['column_plan_id']        = 'رقم الخطة';
$_['column_amount']         = 'المبلغ';
$_['column_late_fee']       = 'غرامة التأخير';
$_['column_discount']       = 'الخصم';
$_['column_net_amount']     = 'المبلغ الصافي';
$_['column_payment_method'] = 'طريقة الدفع';
$_['column_payment_date']   = 'تاريخ الدفع';
$_['column_reference']      = 'الرقم المرجعي';
$_['column_status']         = 'الحالة';
$_['column_received_by']    = 'استلمها';
$_['column_date_created']   = 'تاريخ الإنشاء';
$_['column_action']         = 'الإجراءات';

// حقول النموذج
$_['entry_plan_id']         = 'خطة التقسيط';
$_['entry_customer']        = 'العميل';
$_['entry_amount']          = 'مبلغ القسط';
$_['entry_late_fee']        = 'غرامة التأخير';
$_['entry_discount']        = 'خصم السداد المبكر';
$_['entry_net_amount']      = 'المبلغ الصافي';
$_['entry_payment_method']  = 'طريقة الدفع';
$_['entry_payment_date']    = 'تاريخ الدفع';
$_['entry_bank_reference']  = 'المرجع البنكي';
$_['entry_notes']           = 'ملاحظات';
$_['entry_status']          = 'الحالة';

// مرشحات البحث
$_['entry_filter_customer'] = 'العميل';
$_['entry_filter_payment_method'] = 'طريقة الدفع';
$_['entry_filter_status']   = 'الحالة';
$_['entry_filter_amount_from'] = 'المبلغ من';
$_['entry_filter_amount_to'] = 'المبلغ إلى';
$_['entry_filter_date_from'] = 'التاريخ من';
$_['entry_filter_date_to']  = 'التاريخ إلى';

// الأزرار
$_['button_add']            = 'إضافة';
$_['button_edit']           = 'تعديل';
$_['button_delete']         = 'حذف';
$_['button_view']           = 'عرض';
$_['button_save']           = 'حفظ';
$_['button_cancel']         = 'إلغاء';
$_['button_filter']         = 'فلترة';
$_['button_clear']          = 'مسح';
$_['button_export']         = 'تصدير';
$_['button_print_receipt']  = 'طباعة إيصال';
$_['button_bulk_payment']   = 'دفع مجمع';
$_['button_daily_report']   = 'تقرير يومي';
$_['button_save_and_print'] = 'حفظ وطباعة';
$_['button_plan_details']   = 'تفاصيل الخطة';

// رسائل الخطأ
$_['error_permission']      = 'تحذير: ليس لديك صلاحية للوصول إلى مدفوعات الأقساط!';
$_['error_plan']            = 'يجب اختيار خطة التقسيط!';
$_['error_amount']          = 'مبلغ القسط يجب أن يكون أكبر من صفر!';
$_['error_payment_method']  = 'يجب اختيار طريقة الدفع!';
$_['error_payment_date']    = 'تاريخ الدفع مطلوب!';
$_['error_not_found']       = 'المدفوعة المطلوبة غير موجودة!';
$_['error_plan_not_found']  = 'خطة التقسيط غير موجودة!';
$_['error_invalid_amount']  = 'المبلغ المدخل غير صحيح!';

// الإحصائيات
$_['text_today_payments']   = 'مدفوعات اليوم';
$_['text_today_amount']     = 'مبلغ اليوم';
$_['text_month_payments']   = 'مدفوعات الشهر';
$_['text_month_amount']     = 'مبلغ الشهر';
$_['text_pending_payments'] = 'مدفوعات معلقة';
$_['text_overdue_amount']   = 'مبلغ متأخر';

// تفاصيل المدفوعة
$_['text_payment_details']  = 'تفاصيل المدفوعة';
$_['text_customer_info']    = 'معلومات العميل';
$_['text_payment_info']     = 'معلومات الدفع';
$_['text_plan_info']        = 'معلومات الخطة';

// الإيصال
$_['text_receipt']          = 'إيصال استلام';
$_['text_receipt_number']   = 'رقم الإيصال';
$_['text_received_from']    = 'استلم من';
$_['text_amount_in_words']  = 'المبلغ بالكلمات';
$_['text_payment_for']      = 'دفعة عن';
$_['text_installment_plan'] = 'خطة التقسيط';
$_['text_signature']        = 'التوقيع';
$_['text_date']             = 'التاريخ';

// نصائح المساعدة
$_['help_amount']           = 'مبلغ القسط المستحق أو جزء منه';
$_['help_late_fee']         = 'غرامة التأخير المطبقة حسب شروط الخطة';
$_['help_discount']         = 'خصم السداد المبكر إن وجد';
$_['help_payment_method']   = 'طريقة الدفع المستخدمة';
$_['help_bank_reference']   = 'رقم المرجع البنكي أو رقم الشيك';
$_['help_notes']            = 'أي ملاحظات إضافية حول المدفوعة';

// رسائل التأكيد
$_['text_confirm_delete']   = 'هل أنت متأكد من حذف المدفوعات المحددة؟ هذا الإجراء لا يمكن التراجع عنه.';
$_['text_confirm_cancel']   = 'هل تريد إلغاء هذه المدفوعة؟';

// حالات الخطة
$_['text_plan_active']      = 'نشطة';
$_['text_plan_completed']   = 'مكتملة';
$_['text_plan_overdue']     = 'متأخرة';
$_['text_plan_cancelled']   = 'ملغية';

// تصدير واستيراد
$_['text_export_excel']     = 'تصدير إلى Excel';
$_['text_export_pdf']       = 'تصدير إلى PDF';
$_['text_print_list']       = 'طباعة القائمة';

// إشعارات النجاح
$_['text_payment_confirmed'] = 'تم تأكيد المدفوعة بنجاح!';
$_['text_payment_cancelled'] = 'تم إلغاء المدفوعة بنجاح!';
$_['text_receipt_printed']  = 'تم طباعة الإيصال بنجاح!';

// تحذيرات
$_['warning_payment_exists'] = 'تحذير: يوجد مدفوعة أخرى بنفس المرجع!';
$_['warning_amount_exceeds'] = 'تحذير: المبلغ يتجاوز المبلغ المستحق!';
$_['warning_plan_completed'] = 'تحذير: خطة التقسيط مكتملة بالفعل!';

// معلومات إضافية
$_['text_total_installments'] = 'إجمالي الأقساط';
$_['text_paid_installments'] = 'الأقساط المدفوعة';
$_['text_remaining_installments'] = 'الأقساط المتبقية';
$_['text_total_amount']     = 'إجمالي المبلغ';
$_['text_paid_amount']      = 'المبلغ المدفوع';
$_['text_remaining_amount'] = 'المبلغ المتبقي';
$_['text_next_due_date']    = 'تاريخ الاستحقاق التالي';

// تقارير
$_['text_daily_report']     = 'التقرير اليومي';
$_['text_monthly_report']   = 'التقرير الشهري';
$_['text_payment_summary']  = 'ملخص المدفوعات';
$_['text_collection_report'] = 'تقرير التحصيل';

// رسائل النظام
$_['text_no_payments']      = 'لا توجد مدفوعات أقساط';
$_['text_loading']          = 'جاري التحميل...';
$_['text_processing']       = 'جاري المعالجة...';
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
$_['text_default']          = 'افتراضي';
