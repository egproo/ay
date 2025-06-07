<?php
/**
 * AYM ERP System: ETA Electronic Invoicing Language File (Arabic)
 * 
 * ملف اللغة العربية لتكامل الضرائب المصرية - مطور للشركات الحقيقية
 * 
 * @package    AYM ERP
 * @author     AYM Development Team
 * @copyright  2024 AYM ERP Systems
 * @license    Commercial License
 * @version    1.0.0
 * @since      2024-01-15
 */

// العناوين الرئيسية
$_['heading_title']                    = 'الفوترة الإلكترونية - مصلحة الضرائب المصرية';
$_['heading_title_settings']           = 'إعدادات الفوترة الإلكترونية';
$_['heading_title_dashboard']          = 'لوحة تحكم ETA';

// النصوص الأساسية
$_['text_extension']                   = 'الإضافات';
$_['text_success']                     = 'تم بنجاح!';
$_['text_edit']                        = 'تعديل إعدادات ETA';
$_['text_enabled']                     = 'مفعل';
$_['text_disabled']                    = 'معطل';

// إعدادات البيئة
$_['text_environment']                 = 'بيئة التشغيل';
$_['text_preprod']                     = 'بيئة الاختبار';
$_['text_production']                  = 'بيئة الإنتاج';

// معلومات الاتصال
$_['entry_environment']                = 'بيئة التشغيل';
$_['entry_client_id']                  = 'معرف العميل (Client ID)';
$_['entry_client_secret']              = 'كلمة سر العميل (Client Secret)';
$_['entry_tax_id']                     = 'الرقم الضريبي';
$_['entry_branch_id']                  = 'رقم الفرع';
$_['entry_activity_code']              = 'كود النشاط';

// معلومات الشركة
$_['entry_governate']                  = 'المحافظة';
$_['entry_city']                       = 'المدينة';
$_['entry_street']                     = 'الشارع';
$_['entry_building']                   = 'رقم المبنى';
$_['entry_postal_code']                = 'الرقم البريدي';
$_['entry_floor']                      = 'الطابق';
$_['entry_room']                       = 'الغرفة';
$_['entry_landmark']                   = 'علامة مميزة';
$_['entry_additional_info']            = 'معلومات إضافية';

// الإعدادات التلقائية
$_['entry_auto_send']                  = 'إرسال الفواتير تلقائياً';
$_['entry_auto_receipt']               = 'إرسال الإيصالات تلقائياً';
$_['entry_device_serial']              = 'الرقم التسلسلي للجهاز';

// أنواع المستندات
$_['text_invoice']                     = 'فاتورة';
$_['text_receipt']                     = 'إيصال إلكتروني';
$_['text_credit_note']                 = 'إشعار دائن';
$_['text_debit_note']                  = 'إشعار مدين';

// حالات الإرسال
$_['text_status_pending']              = 'في الانتظار';
$_['text_status_sent']                 = 'تم الإرسال';
$_['text_status_failed']               = 'فشل الإرسال';
$_['text_status_queued']               = 'في الطابور';
$_['text_status_processing']           = 'قيد المعالجة';
$_['text_status_completed']            = 'مكتمل';

// الأزرار والإجراءات
$_['button_send_invoice']              = 'إرسال الفاتورة';
$_['button_send_receipt']              = 'إرسال الإيصال';
$_['button_process_queue']             = 'معالجة الطابور';
$_['button_check_status']              = 'فحص الحالة';
$_['button_resend']                    = 'إعادة الإرسال';
$_['button_view_details']              = 'عرض التفاصيل';
$_['button_download_pdf']              = 'تحميل PDF';
$_['button_test_connection']           = 'اختبار الاتصال';

// الإحصائيات
$_['text_statistics']                  = 'الإحصائيات';
$_['text_total_invoices']              = 'إجمالي الفواتير';
$_['text_sent_invoices']               = 'الفواتير المرسلة';
$_['text_pending_invoices']            = 'الفواتير المعلقة';
$_['text_failed_invoices']             = 'الفواتير الفاشلة';
$_['text_total_receipts']              = 'إجمالي الإيصالات';
$_['text_sent_receipts']               = 'الإيصالات المرسلة';
$_['text_queue_count']                 = 'عدد العناصر في الطابور';
$_['text_success_rate']                = 'معدل النجاح';

// عناوين الأعمدة
$_['column_order_id']                  = 'رقم الطلب';
$_['column_customer']                  = 'العميل';
$_['column_total']                     = 'الإجمالي';
$_['column_status']                    = 'الحالة';
$_['column_eta_uuid']                  = 'معرف ETA';
$_['column_sent_date']                 = 'تاريخ الإرسال';
$_['column_action']                    = 'إجراء';
$_['column_type']                      = 'النوع';
$_['column_attempts']                  = 'المحاولات';
$_['column_next_attempt']              = 'المحاولة التالية';
$_['column_error']                     = 'الخطأ';

// رسائل النجاح
$_['text_success_settings']            = 'تم حفظ الإعدادات بنجاح!';
$_['text_invoice_sent_success']        = 'تم إرسال الفاتورة لمصلحة الضرائب بنجاح!';
$_['text_receipt_sent_success']        = 'تم إرسال الإيصال الإلكتروني بنجاح!';
$_['text_note_sent_success']           = 'تم إرسال الإشعار بنجاح!';
$_['text_queue_processed']             = 'تم معالجة الطابور: %d نجح، %d فشل';
$_['text_connection_success']          = 'تم الاتصال بـ ETA بنجاح!';

// رسائل الخطأ
$_['error_permission']                 = 'تحذير: ليس لديك صلاحية للوصول إلى هذه الصفحة!';
$_['error_client_id']                  = 'معرف العميل مطلوب!';
$_['error_client_secret']              = 'كلمة سر العميل مطلوبة!';
$_['error_tax_id']                     = 'الرقم الضريبي مطلوب!';
$_['error_order_id_required']          = 'رقم الطلب مطلوب!';
$_['error_uuid_required']              = 'معرف UUID مطلوب!';
$_['error_required_fields']            = 'يرجى ملء جميع الحقول المطلوبة!';
$_['error_prepare_invoice_data']       = 'فشل في إعداد بيانات الفاتورة!';
$_['error_prepare_receipt_data']       = 'فشل في إعداد بيانات الإيصال!';
$_['error_prepare_note_data']          = 'فشل في إعداد بيانات الإشعار!';
$_['error_connection_failed']          = 'فشل في الاتصال بـ ETA!';
$_['error_invalid_response']           = 'استجابة غير صحيحة من ETA!';
$_['error_authentication_failed']     = 'فشل في المصادقة مع ETA!';
$_['error_order_not_found']            = 'الطلب غير موجود!';
$_['error_already_sent']               = 'تم إرسال هذا المستند مسبقاً!';

// الطابور (Queue)
$_['text_queue_management']            = 'إدارة الطابور';
$_['text_queue_empty']                 = 'الطابور فارغ';
$_['text_queue_items']                 = 'عناصر الطابور';
$_['text_pending_items']               = 'العناصر المعلقة';
$_['text_failed_items']                = 'العناصر الفاشلة';
$_['text_retry_failed']                = 'إعادة محاولة الفاشل';
$_['text_clear_queue']                 = 'مسح الطابور';

// التفاصيل والمعلومات
$_['text_invoice_details']             = 'تفاصيل الفاتورة';
$_['text_receipt_details']             = 'تفاصيل الإيصال';
$_['text_eta_response']                = 'استجابة ETA';
$_['text_submission_uuid']             = 'معرف الإرسال';
$_['text_document_uuid']               = 'معرف المستند';
$_['text_qr_code']                     = 'رمز QR';
$_['text_pdf_url']                     = 'رابط PDF';

// أنواع الضرائب
$_['text_tax_type_t1']                 = 'ضريبة القيمة المضافة';
$_['text_tax_type_t2']                 = 'ضريبة الجدول';
$_['text_tax_type_t3']                 = 'ضريبة الدمغة';
$_['text_tax_type_t4']                 = 'ضريبة الترفيه';

// أنواع الوحدات
$_['text_unit_pce']                    = 'قطعة';
$_['text_unit_kgm']                    = 'كيلوجرام';
$_['text_unit_ltr']                    = 'لتر';
$_['text_unit_mtr']                    = 'متر';
$_['text_unit_box']                    = 'صندوق';
$_['text_unit_set']                    = 'طقم';

// أنواع العملاء
$_['text_customer_type_b']             = 'شركة';
$_['text_customer_type_p']             = 'فرد';
$_['text_customer_type_f']             = 'أجنبي';

// طرق الدفع
$_['text_payment_cash']                = 'نقدي';
$_['text_payment_card']                = 'بطاقة';
$_['text_payment_check']               = 'شيك';
$_['text_payment_transfer']            = 'تحويل بنكي';

// التقارير
$_['text_reports']                     = 'التقارير';
$_['text_daily_report']                = 'التقرير اليومي';
$_['text_monthly_report']              = 'التقرير الشهري';
$_['text_tax_report']                  = 'تقرير الضرائب';
$_['text_submission_report']           = 'تقرير الإرسال';

// الإشعارات
$_['text_notifications']               = 'الإشعارات';
$_['text_email_notifications']         = 'إشعارات البريد الإلكتروني';
$_['text_sms_notifications']           = 'إشعارات الرسائل النصية';
$_['text_system_notifications']        = 'إشعارات النظام';

// الأمان والتشفير
$_['text_security']                    = 'الأمان';
$_['text_encryption']                  = 'التشفير';
$_['text_digital_signature']           = 'التوقيع الرقمي';
$_['text_certificate']                 = 'الشهادة الرقمية';

// السجلات والتدقيق
$_['text_logs']                        = 'السجلات';
$_['text_audit_trail']                 = 'مسار التدقيق';
$_['text_activity_log']                = 'سجل النشاط';
$_['text_error_log']                   = 'سجل الأخطاء';

// الإعدادات المتقدمة
$_['text_advanced_settings']           = 'الإعدادات المتقدمة';
$_['text_timeout_settings']            = 'إعدادات المهلة الزمنية';
$_['text_retry_settings']              = 'إعدادات إعادة المحاولة';
$_['text_backup_settings']             = 'إعدادات النسخ الاحتياطي';

// المساعدة والدعم
$_['text_help']                        = 'المساعدة';
$_['text_documentation']               = 'التوثيق';
$_['text_support']                     = 'الدعم الفني';
$_['text_contact_eta']                 = 'الاتصال بـ ETA';

// التحديثات والصيانة
$_['text_updates']                     = 'التحديثات';
$_['text_maintenance']                 = 'الصيانة';
$_['text_system_status']               = 'حالة النظام';
$_['text_eta_status']                  = 'حالة ETA';

// التصدير والاستيراد
$_['text_export']                      = 'تصدير';
$_['text_import']                      = 'استيراد';
$_['text_export_data']                 = 'تصدير البيانات';
$_['text_import_data']                 = 'استيراد البيانات';

// التكامل مع الأنظمة الأخرى
$_['text_integration']                 = 'التكامل';
$_['text_api_integration']             = 'تكامل API';
$_['text_webhook_integration']         = 'تكامل Webhook';
$_['text_third_party_integration']     = 'تكامل الطرف الثالث';

// الإعدادات الإقليمية
$_['text_localization']                = 'الإعدادات الإقليمية';
$_['text_currency_settings']           = 'إعدادات العملة';
$_['text_tax_settings']                = 'إعدادات الضرائب';
$_['text_address_format']              = 'تنسيق العنوان';

// التنسيق والعرض
$_['date_format_short']                = 'd/m/Y';
$_['date_format_long']                 = 'd/m/Y H:i:s';
$_['currency_format']                  = '%s %s';
$_['percentage_format']                = '%s%%';

// الترقيم والنتائج
$_['text_pagination']                  = 'عرض %d إلى %d من %d (%d صفحات)';
$_['text_no_results']                  = 'لا توجد نتائج';
$_['text_loading']                     = 'جاري التحميل...';
$_['text_processing']                  = 'جاري المعالجة...';

// breadcrumbs
$_['text_home']                        = 'الرئيسية';
$_['text_eta']                         = 'الفوترة الإلكترونية';

// متنوعة
$_['text_version']                     = 'الإصدار';
$_['text_last_updated']                = 'آخر تحديث';
$_['text_created_date']                = 'تاريخ الإنشاء';
$_['text_modified_date']               = 'تاريخ التعديل';
$_['text_file_size']                   = 'حجم الملف';
$_['text_download']                    = 'تحميل';
$_['text_upload']                      = 'رفع';
$_['text_preview']                     = 'معاينة';
$_['text_print']                       = 'طباعة';
$_['text_email']                       = 'بريد إلكتروني';
$_['text_sms']                         = 'رسالة نصية';
$_['text_notification']                = 'إشعار';
$_['text_alert']                       = 'تنبيه';
$_['text_warning']                     = 'تحذير';
$_['text_info']                        = 'معلومات';
$_['text_debug']                       = 'تصحيح الأخطاء';
?>
