<?php
// الملف: admin/language/ar/migration/migration.php
// اللغة: العربية - مصر (ar-eg)
// الوصف: ملف اللغة الخاص بقسم الانتقال من الأنظمة الأخرى

// --- قسم الانتقال من الأنظمة الأخرى ---
$_['text_migration']                = 'الانتقال من الأنظمة الأخرى';
$_['text_odoo_migration']           = 'الانتقال من نظام أودو';
$_['text_woocommerce_migration']    = 'الانتقال من ووكومرس';
$_['text_shopify_migration']        = 'الانتقال من شوبيفاي';
$_['text_excel_migration']          = 'استيراد من ملفات إكسل';
$_['text_migration_review']         = 'مراجعة واعتماد البيانات المستوردة';

// --- رسائل النجاح والفشل ---
$_['text_success']                  = 'تم استيراد البيانات بنجاح!';
$_['text_error']                    = 'حدث خطأ أثناء استيراد البيانات!';

// --- الأزرار ---
$_['button_import']                 = 'استيراد';
$_['button_review']                 = 'مراجعة';
$_['button_approve']                = 'اعتماد';
$_['button_reject']                 = 'رفض';
$_['button_rollback']               = 'تراجع';

// --- العناوين ---
$_['heading_title']                 = 'الانتقال من الأنظمة الأخرى';
$_['heading_import']                = 'استيراد البيانات';
$_['heading_review']                = 'مراجعة البيانات';
$_['heading_mapping']               = 'مطابقة الحقول';
$_['heading_history']               = 'سجل عمليات الاستيراد';

// --- التنبيهات ---
$_['alert_backup']                  = 'تأكد من عمل نسخة احتياطية قبل البدء!';
$_['alert_required_fields']         = 'يرجى التأكد من تعبئة جميع الحقول المطلوبة';
$_['alert_review_needed']           = 'يجب مراجعة البيانات قبل الاعتماد النهائي';

// --- الحقول ---
$_['entry_source']                  = 'نظام المصدر:';
$_['entry_file']                    = 'ملف البيانات:';
$_['entry_encoding']                = 'ترميز الملف:';
$_['entry_delimiter']               = 'الفاصل بين الحقول:';
$_['entry_mapping']                 = 'مطابقة الحقول:';
$_['entry_skip_rows']               = 'تخطي الصفوف:';
$_['entry_batch_size']              = 'حجم الدفعة:';

// --- الأعمدة ---
$_['column_source']                 = 'المصدر';
$_['column_destination']            = 'الوجهة';
$_['column_status']                 = 'الحالة';
$_['column_date']                   = 'التاريخ';
$_['column_user']                   = 'المستخدم';
$_['column_records']                = 'عدد السجلات';
$_['column_action']                 = 'الإجراء';

// --- الحالات ---
$_['status_pending']                = 'في انتظار المراجعة';
$_['status_approved']               = 'معتمد';
$_['status_rejected']               = 'مرفوض';
$_['status_completed']              = 'مكتمل';
$_['status_failed']                 = 'فشل';

// --- الأخطاء ---
$_['error_permission']              = 'تحذير: ليس لديك صلاحية تعديل الانتقال من الأنظمة الأخرى!';
$_['error_file']                    = 'لم يتم العثور على الملف!';
$_['error_encoding']                = 'ترميز الملف غير صحيح!';
$_['error_mapping']                 = 'يجب تحديد مطابقة الحقول!';
$_['error_required']                = 'هذا الحقل مطلوب!';
$_['error_credentials']             = 'بيانات الاعتماد غير صحيحة!';
$_['error_connection']              = 'فشل الاتصال بالنظام المصدر!';
$_['error_invalid_source']          = 'نظام المصدر غير صالح!';
$_['error_processing']              = 'حدث خطأ أثناء معالجة البيانات!';
$_['error_validation']              = 'فشل التحقق من صحة البيانات: %s';
$_['error_sync']                    = 'فشل مزامنة البيانات: %s';