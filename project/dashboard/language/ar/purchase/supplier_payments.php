<?php
// Heading
$_['heading_title']          = 'دفعات الموردين';

// Text
$_['text_success']           = 'تم: تم تحديث دفعات الموردين بنجاح!';
$_['text_list']              = 'قائمة دفعات الموردين';
$_['text_add']               = 'إضافة دفعة';
$_['text_edit']              = 'تعديل دفعة';
$_['text_confirm']           = 'هل أنت متأكد؟';
$_['text_loading']           = 'جاري التحميل...';
$_['text_no_results']        = 'لا توجد نتائج!';
$_['text_payment_approved']  = 'تم اعتماد الدفعة بنجاح!';
$_['text_payment_cancelled'] = 'تم إلغاء الدفعة بنجاح!';
$_['text_payment_report']    = 'تقرير دفعات الموردين';

// Column
$_['column_payment_number']  = 'رقم الدفعة';
$_['column_supplier']        = 'المورد';
$_['column_payment_amount']  = 'مبلغ الدفعة';
$_['column_payment_method']  = 'طريقة الدفع';
$_['column_payment_date']    = 'تاريخ الدفع';
$_['column_reference_number'] = 'رقم المرجع';
$_['column_status']          = 'الحالة';
$_['column_created_by']      = 'أنشأ بواسطة';
$_['column_action']          = 'إجراء';

// Entry
$_['entry_supplier']         = 'المورد';
$_['entry_payment_amount']   = 'مبلغ الدفعة';
$_['entry_payment_method']   = 'طريقة الدفع';
$_['entry_payment_date']     = 'تاريخ الدفع';
$_['entry_reference_number'] = 'رقم المرجع';
$_['entry_bank_account']     = 'الحساب البنكي';
$_['entry_check_number']     = 'رقم الشيك';
$_['entry_check_date']       = 'تاريخ الشيك';
$_['entry_notes']            = 'ملاحظات';
$_['entry_status']           = 'الحالة';
$_['entry_date_start']       = 'تاريخ البداية';
$_['entry_date_end']         = 'تاريخ النهاية';
$_['entry_cancellation_reason'] = 'سبب الإلغاء';

// Button
$_['button_filter']          = 'فلترة';
$_['button_approve']         = 'اعتماد';
$_['button_cancel_payment']  = 'إلغاء الدفعة';
$_['button_export']          = 'تصدير';
$_['button_print']           = 'طباعة';
$_['button_view_report']     = 'عرض التقرير';

// Tab
$_['tab_general']            = 'عام';
$_['tab_payment_details']    = 'تفاصيل الدفع';
$_['tab_bank_details']       = 'تفاصيل البنك';
$_['tab_notes']              = 'ملاحظات';

// Status
$_['text_status_pending']    = 'معلق';
$_['text_status_approved']   = 'معتمد';
$_['text_status_paid']       = 'مدفوع';
$_['text_status_cancelled']  = 'ملغي';
$_['text_status_returned']   = 'مرتجع';

// Payment Methods
$_['text_method_cash']       = 'نقداً';
$_['text_method_bank_transfer'] = 'تحويل بنكي';
$_['text_method_check']      = 'شيك';
$_['text_method_credit_card'] = 'بطاقة ائتمان';
$_['text_method_money_order'] = 'حوالة';

// Error
$_['error_permission']       = 'تحذير: ليس لديك صلاحية للوصول إلى دفعات الموردين!';
$_['error_supplier']         = 'المورد مطلوب!';
$_['error_payment_amount']   = 'مبلغ الدفعة يجب أن يكون أكبر من صفر!';
$_['error_payment_method']   = 'طريقة الدفع مطلوبة!';
$_['error_payment_date']     = 'تاريخ الدفع مطلوب!';
$_['error_payment_id']       = 'معرف الدفعة مطلوب!';
$_['error_approve_payment']  = 'خطأ في اعتماد الدفعة!';
$_['error_cancel_payment']   = 'خطأ في إلغاء الدفعة!';

// Help
$_['help_payment_amount']    = 'أدخل مبلغ الدفعة المراد دفعها للمورد';
$_['help_reference_number']  = 'رقم مرجعي للدفعة (رقم الشيك، رقم التحويل، إلخ)';
$_['help_bank_account']      = 'الحساب البنكي المستخدم للدفع (للتحويلات البنكية)';
$_['help_check_details']     = 'تفاصيل الشيك (رقم الشيك وتاريخه)';
$_['help_status']            = 'حالة الدفعة: معلق (يحتاج موافقة)، معتمد (جاهز للدفع)، مدفوع (تم الدفع)';

// Success
$_['success_payment_added']  = 'تم إضافة الدفعة بنجاح!';
$_['success_payment_updated'] = 'تم تحديث الدفعة بنجاح!';
$_['success_payment_deleted'] = 'تم حذف الدفعة بنجاح!';
$_['success_payment_approved'] = 'تم اعتماد الدفعة بنجاح!';
$_['success_payment_cancelled'] = 'تم إلغاء الدفعة بنجاح!';
$_['success_export']         = 'تم تصدير البيانات بنجاح!';

// Info
$_['info_payment_help']      = 'استخدم هذه الشاشة لإدارة دفعات الموردين وتتبع المدفوعات';
$_['info_approval_required'] = 'الدفعات تحتاج إلى موافقة قبل التنفيذ';
$_['info_payment_tracking']  = 'يمكن تتبع جميع الدفعات من خلال التقارير';

// Statistics
$_['text_total_payments']    = 'إجمالي الدفعات';
$_['text_pending_payments']  = 'دفعات معلقة';
$_['text_approved_payments'] = 'دفعات معتمدة';
$_['text_paid_payments']     = 'دفعات مدفوعة';
$_['text_total_amount']      = 'إجمالي المبلغ';
$_['text_monthly_payments']  = 'دفعات الشهر';
$_['text_monthly_amount']    = 'مبلغ الشهر';

// Dashboard Widget
$_['widget_title']           = 'دفعات الموردين';
$_['widget_pending']         = 'معلقة';
$_['widget_approved']        = 'معتمدة';
$_['widget_paid']            = 'مدفوعة';
$_['widget_view_all']        = 'عرض الكل';

// Report
$_['report_payment_summary'] = 'ملخص دفعات الموردين';
$_['report_by_supplier']     = 'تقرير حسب المورد';
$_['report_by_method']       = 'تقرير حسب طريقة الدفع';
$_['report_by_period']       = 'تقرير حسب الفترة';

// Email Templates
$_['email_payment_subject']  = 'إشعار دفعة - %s';
$_['email_payment_body']     = 'تم إنشاء دفعة جديدة رقم %s بمبلغ %s للمورد %s';
$_['email_approval_subject'] = 'طلب اعتماد دفعة - %s';
$_['email_approval_body']    = 'دفعة رقم %s بمبلغ %s تحتاج إلى اعتماد';

// Export
$_['export_filename']        = 'دفعات_الموردين_%s.csv';
$_['export_headers']         = array(
    'رقم الدفعة',
    'المورد',
    'مبلغ الدفعة',
    'طريقة الدفع',
    'تاريخ الدفع',
    'رقم المرجع',
    'الحالة',
    'ملاحظات'
);

// Notifications
$_['notification_payment_created'] = 'تم إنشاء دفعة جديدة رقم %s';
$_['notification_payment_approved'] = 'تم اعتماد الدفعة رقم %s';
$_['notification_payment_cancelled'] = 'تم إلغاء الدفعة رقم %s';
$_['notification_approval_required'] = 'الدفعة رقم %s تحتاج إلى اعتماد';

// Validation
$_['validation_supplier_required'] = 'يجب اختيار مورد!';
$_['validation_amount_positive'] = 'مبلغ الدفعة يجب أن يكون أكبر من صفر!';
$_['validation_date_required'] = 'تاريخ الدفع مطلوب!';
$_['validation_method_required'] = 'طريقة الدفع مطلوبة!';
$_['validation_reference_required'] = 'رقم المرجع مطلوب لهذه طريقة الدفع!';
$_['validation_check_details_required'] = 'تفاصيل الشيك مطلوبة!';

// Filters
$_['filter_all_suppliers']   = 'جميع الموردين';
$_['filter_all_methods']     = 'جميع طرق الدفع';
$_['filter_all_statuses']    = 'جميع الحالات';
$_['filter_pending_only']    = 'المعلقة فقط';
$_['filter_approved_only']   = 'المعتمدة فقط';
$_['filter_paid_only']       = 'المدفوعة فقط';

// Actions
$_['action_view_details']    = 'عرض التفاصيل';
$_['action_approve']         = 'اعتماد';
$_['action_cancel']          = 'إلغاء';
$_['action_print_voucher']   = 'طباعة سند';
$_['action_view_receipt']    = 'عرض الإيصال';

// Bulk Actions
$_['bulk_approve']           = 'اعتماد المحدد';
$_['bulk_cancel']            = 'إلغاء المحدد';
$_['bulk_export']            = 'تصدير المحدد';
$_['bulk_action_success']    = 'تم تنفيذ الإجراء على %d دفعة بنجاح!';

// Search
$_['search_placeholder']     = 'البحث في الدفعات...';
$_['search_results']         = 'نتائج البحث عن: %s';
$_['search_no_results']      = 'لا توجد نتائج للبحث عن: %s';

// Modal
$_['modal_approve_payment']  = 'اعتماد الدفعة';
$_['modal_cancel_payment']   = 'إلغاء الدفعة';
$_['modal_confirm_approve']  = 'هل أنت متأكد من اعتماد هذه الدفعة؟';
$_['modal_confirm_cancel']   = 'هل أنت متأكد من إلغاء هذه الدفعة؟';
$_['modal_cancellation_reason'] = 'سبب الإلغاء';

// Workflow
$_['workflow_created']       = 'تم إنشاء الدفعة';
$_['workflow_pending']       = 'في انتظار الموافقة';
$_['workflow_approved']      = 'تم الاعتماد';
$_['workflow_paid']          = 'تم الدفع';
$_['workflow_cancelled']     = 'تم الإلغاء';

// API
$_['api_success']            = 'تم تنفيذ العملية بنجاح';
$_['api_error']              = 'خطأ في تنفيذ العملية';
$_['api_invalid_data']       = 'بيانات غير صحيحة';
$_['api_not_found']          = 'الدفعة غير موجودة';
$_['api_permission_denied']  = 'ليس لديك صلاحية لهذه العملية';
$_['api_cannot_modify']      = 'لا يمكن تعديل هذه الدفعة';

// Audit Trail
$_['audit_payment_created']  = 'تم إنشاء دفعة جديدة';
$_['audit_payment_updated']  = 'تم تحديث الدفعة';
$_['audit_payment_approved'] = 'تم اعتماد الدفعة';
$_['audit_payment_cancelled'] = 'تم إلغاء الدفعة';
$_['audit_payment_deleted']  = 'تم حذف الدفعة';

// Integration
$_['integration_accounting'] = 'تم تسجيل القيد المحاسبي';
$_['integration_bank']       = 'تم إرسال التحويل للبنك';
$_['integration_notification'] = 'تم إرسال الإشعار';

// Approval Workflow
$_['approval_level_1']       = 'موافقة المستوى الأول';
$_['approval_level_2']       = 'موافقة المستوى الثاني';
$_['approval_final']         = 'الموافقة النهائية';
$_['approval_rejected']      = 'مرفوض';

// Bank Integration
$_['bank_transfer_pending']  = 'تحويل بنكي معلق';
$_['bank_transfer_sent']     = 'تم إرسال التحويل';
$_['bank_transfer_confirmed'] = 'تم تأكيد التحويل';
$_['bank_transfer_failed']   = 'فشل التحويل';

// Reconciliation
$_['reconciliation_matched'] = 'تم التطابق';
$_['reconciliation_unmatched'] = 'غير متطابق';
$_['reconciliation_pending'] = 'في انتظار التطابق';
