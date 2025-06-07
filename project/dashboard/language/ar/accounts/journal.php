<?php
// Heading
$_['heading_title'] = 'القيود المحاسبية';

// Text
$_['text_success'] = 'تم حفظ القيد بنجاح!';
$_['text_success_add'] = 'تم إضافة القيد بنجاح!';
$_['text_success_edit'] = 'تم تعديل القيد بنجاح!';
$_['text_success_delete'] = 'تم حذف القيد بنجاح!';
$_['text_success_post'] = 'تم ترحيل القيد بنجاح!';
$_['text_success_unpost'] = 'تم إلغاء ترحيل القيد بنجاح!';
$_['text_success_duplicate'] = 'تم نسخ القيد بنجاح!';
$_['text_success_template_save'] = 'تم حفظ القالب بنجاح!';

$_['text_list'] = 'قائمة القيود';
$_['text_add'] = 'إضافة قيد';
$_['text_edit'] = 'تعديل قيد';
$_['text_view'] = 'عرض القيد';
$_['text_print'] = 'طباعة';
$_['text_print_selected'] = 'طباعة المحدد';
$_['text_export'] = 'تصدير';
$_['text_import'] = 'استيراد';

$_['text_balanced'] = 'متوازن';
$_['text_unbalanced'] = 'غير متوازن';
$_['text_save_and_print'] = 'حفظ وطباعة';
$_['text_save_and_new'] = 'حفظ وجديد';
$_['text_save_as_draft'] = 'حفظ كمسودة';

$_['text_debit_entries'] = 'إدخالات مدينة';
$_['text_credit_entries'] = 'إدخالات دائنة';
$_['text_general'] = 'عام';
$_['text_manual'] = 'يدوي';
$_['text_auto'] = 'تلقائي';

$_['text_draft'] = 'مسودة';
$_['text_posted'] = 'مرحل';
$_['text_approved'] = 'معتمد';
$_['text_cancelled'] = 'ملغي';

$_['text_status_draft'] = 'مسودة';
$_['text_status_posted'] = 'مرحل';
$_['text_status_approved'] = 'معتمد';
$_['text_status_cancelled'] = 'ملغي';

$_['text_sales_order'] = 'أمر بيع';
$_['text_purchase_order'] = 'أمر شراء';
$_['text_customer_payment'] = 'دفعة عميل';
$_['text_supplier_payment'] = 'دفعة مورد';
$_['text_inventory_movement'] = 'حركة مخزون';

$_['text_confirm'] = 'هل أنت متأكد؟';
$_['text_no_results'] = 'لا توجد نتائج!';
$_['text_loading'] = 'جاري التحميل...';
$_['text_select'] = 'اختر';

// Entry
$_['entry_date'] = 'تاريخ القيد';
$_['entry_journal_number'] = 'رقم القيد';
$_['entry_description'] = 'وصف القيد';
$_['entry_account'] = 'الحساب';
$_['entry_account_code'] = 'كود الحساب';
$_['entry_debit'] = 'مدين';
$_['entry_credit'] = 'دائن';
$_['entry_amount'] = 'المبلغ';
$_['entry_attachments'] = 'المرفقات';
$_['entry_reference_type'] = 'نوع المرجع';
$_['entry_reference_number'] = 'رقم المرجع';
$_['entry_status'] = 'الحالة';
$_['entry_cost_center'] = 'مركز التكلفة';
$_['entry_project'] = 'المشروع';
$_['entry_department'] = 'القسم';
$_['entry_notes'] = 'ملاحظات';

// Button
$_['button_save'] = 'حفظ';
$_['button_cancel'] = 'إلغاء';
$_['button_add'] = 'إضافة';
$_['button_edit'] = 'تعديل';
$_['button_delete'] = 'حذف';
$_['button_print'] = 'طباعة';
$_['button_export'] = 'تصدير';
$_['button_import'] = 'استيراد';
$_['button_post'] = 'ترحيل';
$_['button_unpost'] = 'إلغاء ترحيل';
$_['button_duplicate'] = 'نسخ';
$_['button_save_print'] = 'حفظ وطباعة';
$_['button_add_debit'] = 'إضافة مدين';
$_['button_add_credit'] = 'إضافة دائن';
$_['button_remove'] = 'إزالة';
$_['button_clear'] = 'مسح';
$_['button_search'] = 'بحث';
$_['button_filter'] = 'فلترة';
$_['button_refresh'] = 'تحديث';

// Error
$_['error_permission'] = 'تحذير: ليس لديك صلاحية لتعديل القيود المحاسبية!';
$_['error_journal_date'] = 'تاريخ القيد مطلوب!';
$_['error_description'] = 'وصف القيد مطلوب!';
$_['error_account'] = 'تحذير: يجب إدخال كود حساب صحيح!';
$_['error_account_required'] = 'الحساب مطلوب!';
$_['error_account_not_found'] = 'الحساب غير موجود!';
$_['error_amount_required'] = 'المبلغ مطلوب!';
$_['error_both_amounts'] = 'لا يمكن إدخال مبلغ في المدين والدائن معاً!';
$_['error_lines'] = 'تحذير: يجب أن تكون قيم المدين والدائن متساوية!';
$_['error_lines_minimum'] = 'يجب أن يحتوي القيد على بندين على الأقل!';
$_['error_unbalanced'] = 'القيد غير متوازن - يجب أن يكون إجمالي المدين مساوياً لإجمالي الدائن!';
$_['error_journal_id'] = 'معرف القيد مطلوب!';
$_['error_template_data'] = 'بيانات القالب غير صحيحة!';
$_['error_already_posted'] = 'هذا القيد مرحل بالفعل!';
$_['error_cannot_edit_posted'] = 'لا يمكن تعديل قيد مرحل!';
$_['error_cannot_delete_posted'] = 'لا يمكن حذف قيد مرحل!';

// Column
$_['column_journal_number'] = 'رقم القيد';
$_['column_date'] = 'التاريخ';
$_['column_description'] = 'الوصف';
$_['column_debit'] = 'مدين';
$_['column_credit'] = 'دائن';
$_['column_balance'] = 'الرصيد';
$_['column_status'] = 'الحالة';
$_['column_reference'] = 'المرجع';
$_['column_created_by'] = 'أنشأ بواسطة';
$_['column_posted_by'] = 'رحل بواسطة';
$_['column_action'] = 'الإجراء';
$_['column_account'] = 'الحساب';
$_['column_account_code'] = 'كود الحساب';
$_['column_account_name'] = 'اسم الحساب';

// Help
$_['help_journal_number'] = 'اتركه فارغاً للترقيم التلقائي';
$_['help_reference'] = 'رقم المستند المرجعي إن وجد';
$_['help_balance'] = 'يجب أن يكون إجمالي المدين مساوياً لإجمالي الدائن';
$_['help_auto_generated'] = 'هذا القيد تم إنشاؤه تلقائياً من النظام';

// Tab
$_['tab_general'] = 'عام';
$_['tab_lines'] = 'بنود القيد';
$_['tab_attachments'] = 'المرفقات';
$_['tab_history'] = 'التاريخ';
$_['tab_approval'] = 'الاعتماد';

// Filter
$_['filter_journal_number'] = 'رقم القيد';
$_['filter_description'] = 'الوصف';
$_['filter_status'] = 'الحالة';
$_['filter_date_start'] = 'من تاريخ';
$_['filter_date_end'] = 'إلى تاريخ';
$_['filter_account'] = 'الحساب';
$_['filter_reference'] = 'المرجع';
$_['filter_created_by'] = 'أنشأ بواسطة';

// Placeholder
$_['placeholder_search'] = 'البحث في القيود...';
$_['placeholder_description'] = 'وصف البند';
$_['placeholder_amount'] = '0.00';
$_['placeholder_reference'] = 'رقم المرجع';

// Tooltip
$_['tooltip_add'] = 'إضافة قيد جديد';
$_['tooltip_edit'] = 'تعديل القيد';
$_['tooltip_delete'] = 'حذف القيد';
$_['tooltip_print'] = 'طباعة القيد';
$_['tooltip_duplicate'] = 'نسخ القيد';
$_['tooltip_post'] = 'ترحيل القيد';
$_['tooltip_unpost'] = 'إلغاء ترحيل القيد';
$_['tooltip_export'] = 'تصدير القيود';
$_['tooltip_refresh'] = 'تحديث البيانات';

// Stats
$_['stat_total_journals'] = 'إجمالي القيود';
$_['stat_draft_journals'] = 'قيود مسودة';
$_['stat_posted_journals'] = 'قيود مرحلة';
$_['stat_total_debit'] = 'إجمالي المدين';
$_['stat_total_credit'] = 'إجمالي الدائن';

// Template
$_['template_name'] = 'اسم القالب';
$_['template_description'] = 'وصف القالب';
$_['template_save'] = 'حفظ كقالب';
$_['template_apply'] = 'تطبيق القالب';
$_['template_delete'] = 'حذف القالب';

// Keyboard Shortcuts
$_['shortcut_save'] = 'Ctrl+S للحفظ';
$_['shortcut_new'] = 'Ctrl+N لقيد جديد';
$_['shortcut_print'] = 'Ctrl+P للطباعة';
$_['shortcut_search'] = 'Ctrl+F للبحث';
$_['shortcut_balance'] = 'F9 لتحديث التوازن';

// Validation
$_['validation_required'] = 'هذا الحقل مطلوب';
$_['validation_numeric'] = 'يجب أن يكون رقماً';
$_['validation_positive'] = 'يجب أن يكون رقماً موجباً';
$_['validation_date'] = 'تاريخ غير صحيح';
$_['validation_account'] = 'حساب غير صحيح';

// Pagination
$_['text_pagination'] = 'عرض %d إلى %d من %d (%d صفحات)';
$_['text_first'] = 'الأولى';
$_['text_last'] = 'الأخيرة';
$_['text_next'] = 'التالي';
$_['text_prev'] = 'السابق';

// Date Format
$_['date_format_short'] = 'd/m/Y';
$_['date_format_long'] = 'd F Y';
$_['date_format_time'] = 'd/m/Y H:i';
?>
