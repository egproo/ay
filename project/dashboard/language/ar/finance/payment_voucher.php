<?php
/**
 * Language: Payment Voucher (Arabic)
 * ملف اللغة العربية لسندات الصرف
 */

// Heading
$_['heading_title']                    = 'سندات الصرف';
$_['heading_title_add']                = 'إضافة سند صرف';
$_['heading_title_edit']               = 'تعديل سند صرف';

// Text
$_['text_success']                     = 'تم حفظ سند الصرف بنجاح!';
$_['text_success_approve']             = 'تم اعتماد سند الصرف بنجاح!';
$_['text_success_post']                = 'تم ترحيل سند الصرف بنجاح!';
$_['text_success_delete']              = 'تم حذف سند الصرف بنجاح!';
$_['text_list']                        = 'قائمة سندات الصرف';
$_['text_add']                         = 'إضافة سند صرف';
$_['text_edit']                        = 'تعديل سند صرف';
$_['text_form']                        = 'بيانات سند الصرف';
$_['text_view']                        = 'عرض سند الصرف';
$_['text_select']                      = '--- اختر ---';
$_['text_none']                        = 'لا يوجد';
$_['text_loading']                     = 'جاري التحميل...';
$_['text_confirm']                     = 'هل أنت متأكد؟';
$_['text_confirm_approve']             = 'هل تريد اعتماد هذا السند؟';
$_['text_confirm_post']                = 'هل تريد ترحيل هذا السند؟ لن يمكن التراجع عن هذا الإجراء.';
$_['text_confirm_delete']              = 'هل تريد حذف هذا السند؟';

// Payment Types
$_['text_supplier_payment']            = 'دفع للمورد';
$_['text_expense_payment']             = 'دفع مصروفات';

// Payment Methods
$_['text_cash']                        = 'نقدي';
$_['text_bank']                        = 'بنكي';
$_['text_check']                       = 'شيك';

// Status
$_['text_status_draft']                = 'مسودة';
$_['text_status_approved']             = 'معتمد';
$_['text_status_posted']               = 'مرحل';
$_['text_status_cancelled']            = 'ملغي';

// Sections
$_['text_supplier_info']               = 'معلومات المورد';
$_['text_payment_method']              = 'طريقة الدفع';
$_['text_bill_allocation']             = 'تخصيص الفواتير';
$_['text_expense_items']               = 'بنود المصروفات';
$_['text_notes']                       = 'ملاحظات';
$_['text_additional_info']             = 'معلومات إضافية';

// Entry
$_['entry_voucher_number']             = 'رقم السند';
$_['entry_voucher_date']               = 'تاريخ السند';
$_['entry_payment_type']               = 'نوع الدفع';
$_['entry_supplier']                   = 'المورد';
$_['entry_amount']                     = 'المبلغ';
$_['entry_currency']                   = 'العملة';
$_['entry_exchange_rate']              = 'سعر الصرف';
$_['entry_reference']                  = 'المرجع';
$_['entry_payment_method']             = 'طريقة الدفع';
$_['entry_cash_account']               = 'حساب الصندوق';
$_['entry_bank_account']               = 'الحساب البنكي';
$_['entry_check_number']               = 'رقم الشيك';
$_['entry_check_date']                 = 'تاريخ الشيك';
$_['entry_bank_name']                  = 'اسم البنك';
$_['entry_notes']                      = 'ملاحظات';
$_['entry_status']                     = 'الحالة';

// Column
$_['column_voucher_number']            = 'رقم السند';
$_['column_voucher_date']              = 'التاريخ';
$_['column_supplier']                  = 'المورد';
$_['column_amount']                    = 'المبلغ';
$_['column_currency']                  = 'العملة';
$_['column_payment_method']            = 'طريقة الدفع';
$_['column_status']                    = 'الحالة';
$_['column_created_by']                = 'أنشأ بواسطة';
$_['column_created_date']              = 'تاريخ الإنشاء';
$_['column_action']                    = 'إجراء';

// Bill Allocation Columns
$_['column_bill_number']               = 'رقم الفاتورة';
$_['column_bill_date']                 = 'تاريخ الفاتورة';
$_['column_bill_amount']               = 'مبلغ الفاتورة';
$_['column_paid_amount']               = 'المبلغ المدفوع';
$_['column_remaining_amount']          = 'المبلغ المتبقي';
$_['column_allocation_amount']         = 'مبلغ التخصيص';

// Expense Items Columns
$_['column_account']                   = 'الحساب';
$_['column_description']               = 'الوصف';

// Button
$_['button_add']                       = 'إضافة';
$_['button_edit']                      = 'تعديل';
$_['button_delete']                    = 'حذف';
$_['button_save']                      = 'حفظ';
$_['button_cancel']                    = 'إلغاء';
$_['button_approve']                   = 'اعتماد';
$_['button_post']                      = 'ترحيل';
$_['button_print']                     = 'طباعة';
$_['button_export']                    = 'تصدير';
$_['button_import']                    = 'استيراد';
$_['button_filter']                    = 'فلترة';
$_['button_clear']                     = 'مسح';
$_['button_search']                    = 'بحث';
$_['button_view']                      = 'عرض';
$_['button_copy']                      = 'نسخ';
$_['button_load_bills']                = 'تحميل الفواتير';
$_['button_add_expense']               = 'إضافة بند';
$_['button_remove']                    = 'حذف';

// Error
$_['error_permission']                 = 'تحذير: ليس لديك صلاحية للوصول إلى سندات الصرف!';
$_['error_voucher_number']             = 'رقم السند مطلوب!';
$_['error_voucher_date']               = 'تاريخ السند مطلوب!';
$_['error_supplier']                   = 'المورد مطلوب!';
$_['error_amount']                     = 'المبلغ مطلوب ويجب أن يكون أكبر من صفر!';
$_['error_payment_method']             = 'طريقة الدفع مطلوبة!';
$_['error_cash_account']               = 'حساب الصندوق مطلوب!';
$_['error_bank_account']               = 'الحساب البنكي مطلوب!';
$_['error_check_number']               = 'رقم الشيك مطلوب!';
$_['error_check_date']                 = 'تاريخ الشيك مطلوب!';
$_['error_bank_name']                  = 'اسم البنك مطلوب!';
$_['error_voucher_not_found']          = 'السند غير موجود!';
$_['error_voucher_already_approved']   = 'السند معتمد مسبقاً!';
$_['error_voucher_already_posted']     = 'السند مرحل مسبقاً!';
$_['error_voucher_not_approved']       = 'يجب اعتماد السند قبل الترحيل!';
$_['error_cannot_delete_posted']       = 'لا يمكن حذف سند مرحل!';
$_['error_allocation_exceeds_remaining'] = 'مبلغ التخصيص يتجاوز المبلغ المتبقي!';
$_['error_expense_items_required']     = 'بنود المصروفات مطلوبة!';
$_['error_invalid_amount']             = 'المبلغ غير صحيح!';

// Help
$_['help_voucher_number']              = 'رقم السند يتم توليده تلقائياً';
$_['help_voucher_date']                = 'تاريخ إجراء عملية الدفع';
$_['help_payment_type']                = 'اختر نوع الدفع: للمورد أو مصروفات عامة';
$_['help_supplier']                    = 'اختر المورد المراد الدفع له';
$_['help_amount']                      = 'إجمالي مبلغ الدفع';
$_['help_currency']                    = 'عملة الدفع';
$_['help_exchange_rate']               = 'سعر صرف العملة مقابل العملة الأساسية';
$_['help_reference']                   = 'رقم مرجعي للسند (اختياري)';
$_['help_payment_method']              = 'طريقة الدفع: نقدي، بنكي، أو شيك';
$_['help_cash_account']                = 'الصندوق المستخدم للدفع النقدي';
$_['help_bank_account']                = 'الحساب البنكي المستخدم للدفع';
$_['help_check_number']                = 'رقم الشيك المستخدم للدفع';
$_['help_check_date']                  = 'تاريخ استحقاق الشيك';
$_['help_bank_name']                   = 'اسم البنك المسحوب عليه الشيك';
$_['help_bill_allocation']             = 'تخصيص مبلغ الدفع على فواتير المورد';
$_['help_expense_items']               = 'تفصيل المصروفات على الحسابات المختلفة';
$_['help_notes']                       = 'ملاحظات إضافية حول السند';

// Info
$_['text_created_by']                  = 'أنشأ بواسطة';
$_['text_created_date']                = 'تاريخ الإنشاء';
$_['text_approved_by']                 = 'اعتمد بواسطة';
$_['text_approved_date']               = 'تاريخ الاعتماد';
$_['text_posted_by']                   = 'رحل بواسطة';
$_['text_posted_date']                 = 'تاريخ الترحيل';
$_['text_journal_id']                  = 'رقم القيد';

// Messages
$_['text_no_bills']                    = 'لا توجد فواتير للمورد المحدد';
$_['text_no_results']                  = 'لا توجد نتائج!';
$_['text_select_supplier_first']       = 'يرجى اختيار المورد أولاً';
$_['text_total_allocation']            = 'إجمالي التخصيص';
$_['text_total_expenses']              = 'إجمالي المصروفات';

// Filter
$_['text_filter']                      = 'فلترة';
$_['entry_filter_voucher_number']      = 'رقم السند';
$_['entry_filter_supplier']            = 'المورد';
$_['entry_filter_status']              = 'الحالة';
$_['entry_filter_date_from']           = 'من تاريخ';
$_['entry_filter_date_to']             = 'إلى تاريخ';
$_['entry_filter_amount_from']         = 'من مبلغ';
$_['entry_filter_amount_to']           = 'إلى مبلغ';

// Tab
$_['tab_general']                      = 'عام';
$_['tab_payment']                      = 'الدفع';
$_['tab_allocation']                   = 'التخصيص';
$_['tab_expenses']                     = 'المصروفات';
$_['tab_notes']                        = 'ملاحظات';
$_['tab_history']                      = 'التاريخ';

// Placeholder
$_['placeholder_voucher_number']       = 'أدخل رقم السند';
$_['placeholder_amount']               = 'أدخل المبلغ';
$_['placeholder_reference']            = 'أدخل المرجع';
$_['placeholder_check_number']         = 'أدخل رقم الشيك';
$_['placeholder_bank_name']            = 'أدخل اسم البنك';
$_['placeholder_notes']                = 'أدخل الملاحظات';
$_['placeholder_description']          = 'أدخل الوصف';

// Validation
$_['text_required']                    = 'مطلوب';
$_['text_invalid']                     = 'غير صحيح';
$_['text_min_length']                  = 'الحد الأدنى للطول';
$_['text_max_length']                  = 'الحد الأقصى للطول';
$_['text_min_value']                   = 'الحد الأدنى للقيمة';
$_['text_max_value']                   = 'الحد الأقصى للقيمة';

// Reports
$_['text_report_title']                = 'تقرير سندات الصرف';
$_['text_report_period']               = 'فترة التقرير';
$_['text_report_total']                = 'الإجمالي';
$_['text_report_count']                = 'العدد';
$_['text_report_average']              = 'المتوسط';

// Export/Import
$_['text_export_excel']                = 'تصدير إلى Excel';
$_['text_export_pdf']                  = 'تصدير إلى PDF';
$_['text_export_csv']                  = 'تصدير إلى CSV';
$_['text_import_excel']                = 'استيراد من Excel';
$_['text_import_csv']                  = 'استيراد من CSV';

// Permissions
$_['text_access']                      = 'الوصول';
$_['text_modify']                      = 'التعديل';
$_['text_approve']                     = 'الاعتماد';
$_['text_post']                        = 'الترحيل';
$_['text_delete']                      = 'الحذف';

// Workflow
$_['text_workflow_draft']              = 'مسودة - يمكن التعديل والحذف';
$_['text_workflow_approved']           = 'معتمد - يمكن الترحيل';
$_['text_workflow_posted']             = 'مرحل - لا يمكن التعديل أو الحذف';

// Notifications
$_['text_notification_created']        = 'تم إنشاء سند صرف جديد';
$_['text_notification_approved']       = 'تم اعتماد سند الصرف';
$_['text_notification_posted']         = 'تم ترحيل سند الصرف';
$_['text_notification_deleted']        = 'تم حذف سند الصرف';

// Integration
$_['text_accounting_integration']      = 'التكامل المحاسبي';
$_['text_journal_entry_created']       = 'تم إنشاء القيد المحاسبي';
$_['text_supplier_balance_updated']    = 'تم تحديث رصيد المورد';

// Advanced Features
$_['text_recurring_payment']           = 'دفع متكرر';
$_['text_payment_schedule']            = 'جدولة الدفع';
$_['text_multi_currency']              = 'متعدد العملات';
$_['text_approval_workflow']           = 'سير عمل الاعتماد';
$_['text_document_attachment']         = 'مرفقات المستندات';
$_['text_audit_trail']                 = 'مسار المراجعة';

// Actions
$_['text_actions']                     = 'الإجراءات';
$_['button_duplicate']                 = 'نسخ';
$_['button_reverse']                   = 'عكس';
$_['button_reports']                   = 'التقارير';
$_['text_confirm_duplicate']           = 'هل تريد نسخ هذا السند؟';
$_['text_enter_reverse_reason']        = 'أدخل سبب العكس:';
$_['text_ajax_error']                  = 'حدث خطأ في الاتصال';
$_['text_select_export_format']        = 'اختر تنسيق التصدير:';
$_['text_advanced_search']             = 'البحث المتقدم';
$_['text_search_results']              = 'نتائج البحث';
$_['button_close']                     = 'إغلاق';
$_['button_generate_reports']          = 'إنشاء التقارير';
$_['text_reports']                     = 'التقارير';
$_['text_summary_report']              = 'تقرير الملخص';
$_['text_total_vouchers']              = 'إجمالي السندات';
$_['text_average_amount']              = 'متوسط المبلغ';
$_['text_max_amount']                  = 'أعلى مبلغ';

// Dashboard Statistics
$_['text_today_payments']              = 'مدفوعات اليوم';
$_['text_month_payments']              = 'مدفوعات الشهر';
$_['text_pending_approval']            = 'في انتظار الاعتماد';
$_['text_pending_posting']             = 'في انتظار الترحيل';

// Bulk Operations
$_['text_bulk_operations']             = 'العمليات المجمعة';
$_['text_bulk_approve']                = 'اعتماد جماعي';
$_['text_bulk_post']                   = 'ترحيل جماعي';
$_['text_bulk_delete']                 = 'حذف جماعي';
$_['text_select_vouchers']             = 'اختر السندات';
$_['text_no_vouchers_selected']        = 'لم يتم اختيار أي سندات';

// Search and Filter
$_['text_filter_results']              = 'تصفية النتائج';
$_['text_clear_filters']               = 'مسح الفلاتر';
$_['text_apply_filters']               = 'تطبيق الفلاتر';
$_['text_save_search']                 = 'حفظ البحث';
$_['text_load_search']                 = 'تحميل بحث محفوظ';

// Notifications
$_['text_email_notification']          = 'إشعار بريد إلكتروني';
$_['text_sms_notification']            = 'إشعار رسالة نصية';
$_['text_system_notification']         = 'إشعار النظام';
$_['text_notification_settings']       = 'إعدادات الإشعارات';

// Templates
$_['text_voucher_template']            = 'قالب السند';
$_['text_save_as_template']            = 'حفظ كقالب';
$_['text_load_template']               = 'تحميل قالب';
$_['text_template_name']               = 'اسم القالب';

// Approval Workflow
$_['text_approval_required']           = 'يتطلب اعتماد';
$_['text_approval_level']              = 'مستوى الاعتماد';
$_['text_approver']                    = 'المعتمد';
$_['text_approval_date']               = 'تاريخ الاعتماد';
$_['text_approval_comments']           = 'تعليقات الاعتماد';

// Document Management
$_['text_attachments']                 = 'المرفقات';
$_['text_upload_document']             = 'رفع مستند';
$_['text_download_document']           = 'تحميل مستند';
$_['text_delete_document']             = 'حذف مستند';
$_['text_document_type']               = 'نوع المستند';
$_['text_document_size']               = 'حجم المستند';

// Security
$_['text_digital_signature']          = 'التوقيع الرقمي';
$_['text_encryption']                  = 'التشفير';
$_['text_access_log']                  = 'سجل الوصول';
$_['text_user_activity']               = 'نشاط المستخدم';

// Integration
$_['text_api_integration']             = 'تكامل API';
$_['text_webhook_url']                 = 'رابط Webhook';
$_['text_external_system']             = 'النظام الخارجي';
$_['text_sync_status']                 = 'حالة المزامنة';

// Mobile
$_['text_mobile_access']               = 'الوصول عبر الجوال';
$_['text_qr_code_scan']                = 'مسح رمز QR';
$_['text_mobile_approval']             = 'اعتماد عبر الجوال';

// Analytics
$_['text_payment_analytics']           = 'تحليلات المدفوعات';
$_['text_trend_analysis']              = 'تحليل الاتجاهات';
$_['text_performance_metrics']         = 'مقاييس الأداء';
$_['text_cost_analysis']               = 'تحليل التكاليف';

// Compliance
$_['text_regulatory_compliance']       = 'الامتثال التنظيمي';
$_['text_tax_compliance']              = 'الامتثال الضريبي';
$_['text_audit_compliance']            = 'امتثال المراجعة';

// Backup and Recovery
$_['text_backup_voucher']              = 'نسخ احتياطي للسند';
$_['text_restore_voucher']             = 'استعادة السند';
$_['text_archive_voucher']             = 'أرشفة السند';

// Performance
$_['text_processing_time']             = 'وقت المعالجة';
$_['text_response_time']               = 'وقت الاستجابة';
$_['text_system_performance']          = 'أداء النظام';

// Customization
$_['text_custom_fields']               = 'حقول مخصصة';
$_['text_field_configuration']         = 'تكوين الحقول';
$_['text_layout_settings']             = 'إعدادات التخطيط';
$_['text_theme_settings']              = 'إعدادات المظهر';

// Help and Support
$_['text_help_documentation']          = 'وثائق المساعدة';
$_['text_video_tutorials']             = 'دروس فيديو';
$_['text_support_ticket']              = 'تذكرة الدعم';
$_['text_contact_support']             = 'اتصل بالدعم';

// Version Control
$_['text_version_history']             = 'تاريخ الإصدارات';
$_['text_version_compare']             = 'مقارنة الإصدارات';
$_['text_rollback_version']            = 'التراجع للإصدار السابق';

// Collaboration
$_['text_team_collaboration']          = 'تعاون الفريق';
$_['text_shared_workspace']            = 'مساحة عمل مشتركة';
$_['text_comment_system']              = 'نظام التعليقات';
$_['text_mention_user']                = 'ذكر مستخدم';

// Automation
$_['text_workflow_automation']         = 'أتمتة سير العمل';
$_['text_auto_approval']               = 'اعتماد تلقائي';
$_['text_scheduled_payments']          = 'مدفوعات مجدولة';
$_['text_recurring_automation']        = 'أتمتة التكرار';
?>
