<?php
/**
 * AYM ERP - Supplier Documents Language File (Arabic)
 * 
 * @author AYM ERP Development Team
 * @copyright 2024 AYM ERP
 * @license Commercial License
 * @version 1.0.0
 * @link https://aym-erp.com
 */

// Heading
$_['heading_title']          = 'مستندات الموردين';

// Text
$_['text_success']           = 'تم: تم تحديث مستندات الموردين بنجاح!';
$_['text_success_archive']   = 'تم: تم أرشفة المستند بنجاح!';
$_['text_list']              = 'قائمة المستندات';
$_['text_add']               = 'إضافة مستند';
$_['text_edit']              = 'تعديل مستند';
$_['text_view']              = 'عرض المستند';
$_['text_upload']            = 'رفع ملف';
$_['text_download']          = 'تحميل';
$_['text_archive']           = 'أرشفة';
$_['text_active']            = 'نشط';
$_['text_archived']          = 'مؤرشف';
$_['text_expired']           = 'منتهي الصلاحية';
$_['text_expiring_soon']     = 'ينتهي قريباً';
$_['text_no_expiry']         = 'بدون انتهاء صلاحية';
$_['text_all_suppliers']     = 'جميع الموردين';
$_['text_all_types']         = 'جميع الأنواع';
$_['text_select']            = 'اختر';
$_['text_none']              = 'لا يوجد';
$_['text_current_file']      = 'الملف الحالي';
$_['text_replace_file']      = 'استبدال الملف';
$_['text_file_info']         = 'معلومات الملف';
$_['text_versions']          = 'الإصدارات';
$_['text_history']           = 'السجل';
$_['text_statistics']        = 'الإحصائيات';

// Column
$_['column_title']           = 'عنوان المستند';
$_['column_supplier']        = 'المورد';
$_['column_document_type']   = 'نوع المستند';
$_['column_file_size']       = 'حجم الملف';
$_['column_expiry_date']     = 'تاريخ الانتهاء';
$_['column_status']          = 'الحالة';
$_['column_date_added']      = 'تاريخ الإضافة';
$_['column_date_modified']   = 'تاريخ التعديل';
$_['column_created_by']      = 'أنشأ بواسطة';
$_['column_action']          = 'إجراء';
$_['column_version']         = 'الإصدار';
$_['column_download_count']  = 'عدد التحميلات';
$_['column_tags']            = 'العلامات';

// Entry
$_['entry_title']            = 'عنوان المستند';
$_['entry_supplier']         = 'المورد';
$_['entry_document_type']    = 'نوع المستند';
$_['entry_description']      = 'الوصف';
$_['entry_expiry_date']      = 'تاريخ الانتهاء';
$_['entry_tags']             = 'العلامات';
$_['entry_status']           = 'الحالة';
$_['entry_file']             = 'الملف';
$_['entry_search']           = 'البحث';

// Help
$_['help_title']             = 'عنوان وصفي للمستند';
$_['help_description']       = 'وصف تفصيلي لمحتوى المستند';
$_['help_expiry_date']       = 'تاريخ انتهاء صلاحية المستند (اختياري)';
$_['help_tags']              = 'كلمات مفتاحية للبحث، مفصولة بفواصل';
$_['help_file_upload']       = 'الملفات المدعومة: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG, ZIP (حد أقصى 10MB)';

// Document Types
$_['type_contract']          = 'عقد';
$_['type_certificate']       = 'شهادة';
$_['type_license']           = 'ترخيص';
$_['type_insurance']         = 'تأمين';
$_['type_tax_document']      = 'مستند ضريبي';
$_['type_bank_document']     = 'مستند بنكي';
$_['type_quality_certificate'] = 'شهادة جودة';
$_['type_compliance_document'] = 'مستند امتثال';
$_['type_technical_specification'] = 'مواصفات فنية';
$_['type_product_catalog']   = 'كتالوج منتجات';
$_['type_price_list']        = 'قائمة أسعار';
$_['type_invoice']           = 'فاتورة';
$_['type_receipt']           = 'إيصال';
$_['type_delivery_note']     = 'مذكرة تسليم';
$_['type_other']             = 'أخرى';

// Tab
$_['tab_general']            = 'عام';
$_['tab_file']               = 'الملف';
$_['tab_versions']           = 'الإصدارات';
$_['tab_history']            = 'السجل';
$_['tab_details']            = 'التفاصيل';

// Button
$_['button_upload']          = 'رفع ملف';
$_['button_download']        = 'تحميل';
$_['button_view']            = 'عرض';
$_['button_archive']         = 'أرشفة';
$_['button_restore']         = 'استعادة';
$_['button_search']          = 'بحث';
$_['button_filter']          = 'فلترة';
$_['button_export']          = 'تصدير';
$_['button_bulk_archive']    = 'أرشفة متعددة';
$_['button_cleanup']         = 'تنظيف المنتهية';

// Error
$_['error_permission']       = 'تحذير: ليس لديك صلاحية تعديل مستندات الموردين!';
$_['error_title']            = 'يجب أن يكون عنوان المستند بين 3 و 128 حرف!';
$_['error_supplier']         = 'مطلوب اختيار المورد!';
$_['error_document_type']    = 'مطلوب اختيار نوع المستند!';
$_['error_file_type']        = 'نوع الملف غير مدعوم!';
$_['error_file_size']        = 'حجم الملف كبير جداً! الحد الأقصى 10MB';
$_['error_file_upload']      = 'فشل في رفع الملف!';
$_['error_file_not_found']   = 'الملف غير موجود!';
$_['error_expiry_date']      = 'تاريخ الانتهاء غير صحيح!';

// Success
$_['success_upload']         = 'تم رفع الملف بنجاح!';
$_['success_download']       = 'تم تحميل الملف بنجاح!';
$_['success_archive']        = 'تم أرشفة المستند بنجاح!';
$_['success_restore']        = 'تم استعادة المستند بنجاح!';
$_['success_delete']         = 'تم حذف المستند بنجاح!';
$_['success_cleanup']        = 'تم تنظيف %d مستند منتهي الصلاحية!';

// Info
$_['info_no_documents']      = 'لا توجد مستندات';
$_['info_no_file']           = 'لم يتم رفع ملف';
$_['info_file_replaced']     = 'تم استبدال الملف';
$_['info_document_expired']  = 'هذا المستند منتهي الصلاحية';
$_['info_expiring_in']       = 'ينتهي خلال %d يوم';
$_['info_total_documents']   = 'إجمالي المستندات: %d';
$_['info_total_size']        = 'إجمالي الحجم: %s';
$_['info_recent_uploads']    = 'الرفعات الأخيرة (30 يوم): %d';

// Action
$_['action_created']         = 'تم إنشاء المستند';
$_['action_modified']        = 'تم تعديل المستند';
$_['action_file_uploaded']   = 'تم رفع ملف';
$_['action_downloaded']      = 'تم تحميل المستند';
$_['action_archived']        = 'تم أرشفة المستند';
$_['action_restored']        = 'تم استعادة المستند';
$_['action_deleted']         = 'تم حذف المستند';

// Filter
$_['filter_title']           = 'عنوان المستند';
$_['filter_supplier']        = 'المورد';
$_['filter_document_type']   = 'نوع المستند';
$_['filter_status']          = 'الحالة';
$_['filter_expiry_start']    = 'انتهاء من تاريخ';
$_['filter_expiry_end']      = 'انتهاء إلى تاريخ';
$_['filter_date_added']      = 'تاريخ الإضافة';

// Status
$_['status_active']          = 'نشط';
$_['status_archived']        = 'مؤرشف';
$_['status_expired']         = 'منتهي الصلاحية';
$_['status_expiring']        = 'ينتهي قريباً';

// Notification
$_['notification_expiring']  = 'تنبيه: لديك %d مستند ينتهي خلال 30 يوم';
$_['notification_expired']   = 'تحذير: لديك %d مستند منتهي الصلاحية';
$_['notification_new_upload'] = 'تم رفع مستند جديد: %s';

// Report
$_['report_title']           = 'تقرير مستندات الموردين';
$_['report_period']          = 'الفترة: %s إلى %s';
$_['report_summary']         = 'ملخص المستندات';
$_['report_by_type']         = 'المستندات حسب النوع';
$_['report_by_supplier']     = 'المستندات حسب المورد';
$_['report_expiry_status']   = 'حالة انتهاء الصلاحية';

// Dashboard
$_['dashboard_title']        = 'لوحة تحكم المستندات';
$_['dashboard_overview']     = 'نظرة عامة';
$_['dashboard_recent']       = 'النشاط الأخير';
$_['dashboard_expiring']     = 'المستندات المنتهية قريباً';
$_['dashboard_statistics']   = 'الإحصائيات';

// Version
$_['version_current']        = 'الإصدار الحالي';
$_['version_previous']       = 'الإصدارات السابقة';
$_['version_number']         = 'رقم الإصدار';
$_['version_created']        = 'تاريخ الإنشاء';
$_['version_size']           = 'الحجم';

// History
$_['history_action']         = 'الإجراء';
$_['history_description']    = 'الوصف';
$_['history_user']           = 'المستخدم';
$_['history_date']           = 'التاريخ';
$_['history_ip']             = 'عنوان IP';

// Search
$_['search_placeholder']     = 'البحث في المستندات...';
$_['search_results']         = 'نتائج البحث';
$_['search_no_results']      = 'لا توجد نتائج للبحث';
$_['search_in_title']        = 'في العنوان';
$_['search_in_description']  = 'في الوصف';
$_['search_in_tags']         = 'في العلامات';
$_['search_in_supplier']     = 'في اسم المورد';

// Validation
$_['validation_required']    = 'هذا الحقل مطلوب';
$_['validation_min_length']  = 'الحد الأدنى %d أحرف';
$_['validation_max_length']  = 'الحد الأقصى %d حرف';
$_['validation_file_size']   = 'حجم الملف يجب أن يكون أقل من %s';
$_['validation_file_type']   = 'نوع الملف غير مدعوم';

// Bulk Actions
$_['bulk_archive_selected']  = 'أرشفة المحدد';
$_['bulk_delete_selected']   = 'حذف المحدد';
$_['bulk_export_selected']   = 'تصدير المحدد';
$_['bulk_confirm_archive']   = 'هل أنت متأكد من أرشفة المستندات المحددة؟';
$_['bulk_confirm_delete']    = 'هل أنت متأكد من حذف المستندات المحددة؟';

// File Management
$_['file_original_name']     = 'الاسم الأصلي';
$_['file_current_name']      = 'الاسم الحالي';
$_['file_mime_type']         = 'نوع الملف';
$_['file_upload_date']       = 'تاريخ الرفع';
$_['file_download_count']    = 'عدد التحميلات';
$_['file_last_download']     = 'آخر تحميل';

// Security
$_['security_access_denied'] = 'تم رفض الوصول';
$_['security_file_scan']     = 'فحص الملف للفيروسات';
$_['security_clean']         = 'الملف آمن';
$_['security_infected']      = 'الملف مصاب بفيروس';

// Maintenance
$_['maintenance_cleanup']    = 'تنظيف المستندات';
$_['maintenance_optimize']   = 'تحسين قاعدة البيانات';
$_['maintenance_backup']     = 'نسخ احتياطي للمستندات';
$_['maintenance_restore']    = 'استعادة من النسخة الاحتياطية';
