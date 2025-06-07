<?php
// Heading
$_['heading_title']          = 'حسابات الموردين';

// Text
$_['text_success']           = 'تم: تم تحديث حسابات الموردين بنجاح!';
$_['text_list']              = 'قائمة حسابات الموردين';
$_['text_account_details']   = 'تفاصيل الحساب';
$_['text_transaction_success'] = 'تم إضافة المعاملة بنجاح!';
$_['text_payment_success']   = 'تم إضافة الدفعة بنجاح!';
$_['text_credit_limit_updated'] = 'تم تحديث حد الائتمان بنجاح!';
$_['text_status_updated']    = 'تم تحديث حالة الحساب بنجاح!';
$_['text_aging_report']      = 'تقرير أعمار الديون';
$_['text_statement']         = 'كشف الحساب';
$_['text_confirm']           = 'هل أنت متأكد؟';
$_['text_loading']           = 'جاري التحميل...';
$_['text_no_results']        = 'لا توجد نتائج!';

// Column
$_['column_supplier']        = 'المورد';
$_['column_account_number']  = 'رقم الحساب';
$_['column_current_balance'] = 'الرصيد الحالي';
$_['column_credit_limit']    = 'حد الائتمان';
$_['column_payment_terms']   = 'شروط الدفع';
$_['column_account_status']  = 'حالة الحساب';
$_['column_last_transaction'] = 'آخر معاملة';
$_['column_action']          = 'إجراء';
$_['column_transaction_date'] = 'تاريخ المعاملة';
$_['column_transaction_type'] = 'نوع المعاملة';
$_['column_amount']          = 'المبلغ';
$_['column_reference']       = 'المرجع';
$_['column_description']     = 'الوصف';
$_['column_user']            = 'المستخدم';
$_['column_current_30']      = 'الحالي (0-30 يوم)';
$_['column_days_31_60']      = '31-60 يوم';
$_['column_days_61_90']      = '61-90 يوم';
$_['column_over_90']         = 'أكثر من 90 يوم';

// Entry
$_['entry_supplier']         = 'المورد';
$_['entry_supplier_name']    = 'اسم المورد';
$_['entry_account_status']   = 'حالة الحساب';
$_['entry_balance_min']      = 'الحد الأدنى للرصيد';
$_['entry_balance_max']      = 'الحد الأقصى للرصيد';
$_['entry_transaction_type'] = 'نوع المعاملة';
$_['entry_amount']           = 'المبلغ';
$_['entry_transaction_date'] = 'تاريخ المعاملة';
$_['entry_reference']        = 'المرجع';
$_['entry_description']      = 'الوصف';
$_['entry_payment_amount']   = 'مبلغ الدفعة';
$_['entry_payment_method']   = 'طريقة الدفع';
$_['entry_payment_date']     = 'تاريخ الدفع';
$_['entry_reference_number'] = 'رقم المرجع';
$_['entry_notes']            = 'ملاحظات';
$_['entry_credit_limit']     = 'حد الائتمان';
$_['entry_date_start']       = 'تاريخ البداية';
$_['entry_date_end']         = 'تاريخ النهاية';

// Button
$_['button_filter']          = 'فلترة';
$_['button_view_account']    = 'عرض الحساب';
$_['button_add_transaction'] = 'إضافة معاملة';
$_['button_add_payment']     = 'إضافة دفعة';
$_['button_export']          = 'تصدير';
$_['button_aging_report']    = 'تقرير أعمار الديون';
$_['button_statement']       = 'كشف الحساب';
$_['button_update_credit']   = 'تحديث الائتمان';
$_['button_toggle_status']   = 'تبديل الحالة';
$_['button_print']           = 'طباعة';

// Tab
$_['tab_account_info']       = 'معلومات الحساب';
$_['tab_transactions']       = 'المعاملات';
$_['tab_payments']           = 'المدفوعات';
$_['tab_summary']            = 'الملخص';

// Transaction Types
$_['transaction_purchase']   = 'مشتريات';
$_['transaction_invoice']    = 'فاتورة';
$_['transaction_payment']    = 'دفعة';
$_['transaction_credit']     = 'رصيد دائن';
$_['transaction_debit']      = 'رصيد مدين';
$_['transaction_adjustment'] = 'تسوية';

// Account Status
$_['status_active']          = 'نشط';
$_['status_suspended']       = 'معلق';
$_['status_closed']          = 'مغلق';

// Payment Terms
$_['terms_net_30']           = 'صافي 30 يوم';
$_['terms_net_60']           = 'صافي 60 يوم';
$_['terms_net_90']           = 'صافي 90 يوم';
$_['terms_cod']              = 'الدفع عند التسليم';
$_['terms_prepaid']          = 'مدفوع مقدماً';

// Error
$_['error_permission']       = 'تحذير: ليس لديك صلاحية للوصول إلى حسابات الموردين!';
$_['error_supplier']         = 'المورد مطلوب!';
$_['error_transaction_type'] = 'نوع المعاملة مطلوب!';
$_['error_amount']           = 'المبلغ يجب أن يكون أكبر من صفر!';
$_['error_transaction_date'] = 'تاريخ المعاملة مطلوب!';
$_['error_payment_amount']   = 'مبلغ الدفعة يجب أن يكون أكبر من صفر!';
$_['error_payment_method']   = 'طريقة الدفع مطلوبة!';
$_['error_payment_date']     = 'تاريخ الدفع مطلوب!';
$_['error_credit_limit']     = 'حد الائتمان يجب أن يكون أكبر من أو يساوي صفر!';
$_['error_missing_data']     = 'بيانات مفقودة!';
$_['error_update_status']    = 'خطأ في تحديث حالة الحساب!';

// Help
$_['help_current_balance']   = 'الرصيد الحالي للمورد (موجب = مدين للمورد، سالب = دائن للمورد)';
$_['help_credit_limit']      = 'الحد الأقصى للائتمان المسموح للمورد';
$_['help_payment_terms']     = 'شروط الدفع المتفق عليها مع المورد';
$_['help_account_status']    = 'حالة الحساب (نشط، معلق، مغلق)';

// Success
$_['success_transaction_added'] = 'تم إضافة المعاملة بنجاح!';
$_['success_payment_added']  = 'تم إضافة الدفعة بنجاح!';
$_['success_credit_updated'] = 'تم تحديث حد الائتمان بنجاح!';
$_['success_status_updated'] = 'تم تحديث حالة الحساب بنجاح!';
$_['success_export']         = 'تم تصدير البيانات بنجاح!';

// Info
$_['info_account_help']      = 'استخدم هذه الشاشة لإدارة حسابات الموردين ومتابعة المعاملات المالية';
$_['info_balance_positive']  = 'الرصيد الموجب يعني أن الشركة مدينة للمورد';
$_['info_balance_negative']  = 'الرصيد السالب يعني أن المورد مدين للشركة';
$_['info_aging_help']        = 'تقرير أعمار الديون يوضح توزيع المبالغ المستحقة حسب الفترة الزمنية';

// Statistics
$_['text_total_accounts']    = 'إجمالي الحسابات';
$_['text_active_accounts']   = 'حسابات نشطة';
$_['text_total_balance']     = 'إجمالي الأرصدة';
$_['text_positive_balance']  = 'أرصدة موجبة';
$_['text_negative_balance']  = 'أرصدة سالبة';
$_['text_account_summary']   = 'ملخص الحسابات';

// Dashboard Widget
$_['widget_title']           = 'حسابات الموردين';
$_['widget_total_balance']   = 'إجمالي الرصيد';
$_['widget_overdue']         = 'متأخرة السداد';
$_['widget_current']         = 'حالية';
$_['widget_view_all']        = 'عرض الكل';

// Report
$_['report_aging_title']     = 'تقرير أعمار ديون الموردين';
$_['report_statement_title'] = 'كشف حساب مورد';
$_['report_summary_title']   = 'ملخص حسابات الموردين';
$_['report_transactions']    = 'تقرير معاملات الموردين';

// Email Templates
$_['email_statement_subject'] = 'كشف حساب - %s';
$_['email_statement_body']   = 'مرفق كشف حساب للفترة من %s إلى %s';
$_['email_overdue_subject']  = 'تذكير بالمبالغ المستحقة';
$_['email_overdue_body']     = 'لديك مبالغ مستحقة بقيمة %s متأخرة عن موعد الاستحقاق';

// Export
$_['export_filename']        = 'حسابات_الموردين_%s.csv';
$_['export_aging_filename']  = 'أعمار_الديون_%s.csv';
$_['export_statement_filename'] = 'كشف_حساب_%s_%s.csv';

// Notifications
$_['notification_payment_received'] = 'تم استلام دفعة من المورد %s بمبلغ %s';
$_['notification_credit_limit_exceeded'] = 'تحذير: المورد %s تجاوز حد الائتمان';
$_['notification_account_suspended'] = 'تم تعليق حساب المورد %s';

// Validation
$_['validation_supplier_required'] = 'يجب اختيار مورد!';
$_['validation_amount_positive'] = 'المبلغ يجب أن يكون أكبر من صفر!';
$_['validation_date_required'] = 'التاريخ مطلوب!';
$_['validation_credit_limit_valid'] = 'حد الائتمان يجب أن يكون رقماً صحيحاً!';

// Filters
$_['filter_all_suppliers']   = 'جميع الموردين';
$_['filter_all_statuses']    = 'جميع الحالات';
$_['filter_positive_balance'] = 'رصيد موجب';
$_['filter_negative_balance'] = 'رصيد سالب';
$_['filter_zero_balance']    = 'رصيد صفر';

// Actions
$_['action_view_details']    = 'عرض التفاصيل';
$_['action_add_transaction'] = 'إضافة معاملة';
$_['action_add_payment']     = 'إضافة دفعة';
$_['action_view_statement']  = 'عرض كشف الحساب';
$_['action_suspend_account'] = 'تعليق الحساب';
$_['action_activate_account'] = 'تفعيل الحساب';

// Bulk Actions
$_['bulk_export']            = 'تصدير المحدد';
$_['bulk_suspend']           = 'تعليق المحدد';
$_['bulk_activate']          = 'تفعيل المحدد';
$_['bulk_action_success']    = 'تم تنفيذ الإجراء على %d حساب بنجاح!';

// Search
$_['search_placeholder']     = 'البحث في حسابات الموردين...';
$_['search_results']         = 'نتائج البحث عن: %s';
$_['search_no_results']      = 'لا توجد نتائج للبحث عن: %s';

// Modal
$_['modal_add_transaction']  = 'إضافة معاملة جديدة';
$_['modal_add_payment']      = 'إضافة دفعة جديدة';
$_['modal_update_credit']    = 'تحديث حد الائتمان';
$_['modal_confirm_suspend']  = 'تأكيد تعليق الحساب';
$_['modal_confirm_activate'] = 'تأكيد تفعيل الحساب';

// API
$_['api_success']            = 'تم تنفيذ العملية بنجاح';
$_['api_error']              = 'خطأ في تنفيذ العملية';
$_['api_invalid_data']       = 'بيانات غير صحيحة';
$_['api_not_found']          = 'الحساب غير موجود';
$_['api_permission_denied']  = 'ليس لديك صلاحية لهذه العملية';
