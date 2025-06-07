<?php
/**
 * AYM ERP System: Export/Import Language File (Arabic)
 *
 * ملف اللغة العربية لنظام التصدير والاستيراد - مطور بجودة عالمية
 *
 * الميزات المتقدمة:
 * - تصدير واستيراد شامل للبيانات
 * - دعم صيغ متعددة (Excel, CSV, ODS)
 * - معالجة مجمعة للبيانات
 * - تحقق من صحة البيانات
 * - نظام إعدادات متقدم
 * - واجهة مستخدم احترافية
 *
 * @package    AYM ERP
 * @author     AYM Development Team
 * @copyright  2024 AYM ERP Systems
 * @license    Commercial License
 * @version    1.0.0
 * @since      2024-01-15
 */

// العناوين الرئيسية
$_['heading_title']                    = 'تصدير واستيراد البيانات';

// النصوص الأساسية
$_['text_success']                     = 'تم بنجاح: تم حفظ إعدادات التصدير والاستيراد!';
$_['text_success_settings']            = 'تم بنجاح: تم حفظ الإعدادات!';
$_['text_home']                        = 'الرئيسية';
$_['text_loading_notifications']       = 'جاري تحميل الإشعارات...';
$_['text_retry']                       = 'إعادة المحاولة';
$_['text_license']                     = 'الترخيص';
$_['text_welcome']                     = 'مرحباً بك في نظام التصدير والاستيراد الإصدار %1';
$_['text_yes']                         = 'نعم';
$_['text_no']                          = 'لا';
$_['text_nochange']                    = 'لم يتم إجراء أي تغييرات على البيانات';

// أنواع التصدير
$_['text_export_type_category']        = 'الفئات (مع الفلاتر)';
$_['text_export_type_category_old']    = 'الفئات (بدون فلاتر)';
$_['text_export_type_product']         = 'المنتجات (مع الفلاتر)';
$_['text_export_type_product_old']     = 'المنتجات (بدون فلاتر)';
$_['text_export_type_poa']             = 'خيارات المنتجات والخصائص';
$_['text_export_type_option']          = 'الخيارات';
$_['text_export_type_attribute']       = 'الخصائص';
$_['text_export_type_filter']          = 'الفلاتر';
$_['text_export_type_customer']        = 'العملاء';

// معلومات المعرفات المستخدمة
$_['text_used_category_ids']           = 'معرفات الفئات المستخدمة: %1 إلى %2';
$_['text_used_product_ids']            = 'معرفات المنتجات المستخدمة: %1 إلى %2';

// حقول الإدخال
$_['entry_export']                     = 'تصدير';
$_['entry_import']                     = 'استيراد';
$_['entry_export_type']                = 'نوع التصدير';
$_['entry_range_type']                 = 'نوع النطاق';
$_['entry_category_filter']            = 'فلتر الفئة';
$_['entry_category']                   = 'الفئة';
$_['entry_start_id']                   = 'معرف البداية';
$_['entry_start_index']                = 'فهرس البداية';
$_['entry_end_id']                     = 'معرف النهاية';
$_['entry_end_index']                  = 'فهرس النهاية';
$_['entry_incremental']                = 'تحديث تدريجي';
$_['entry_upload']                     = 'رفع الملف';
$_['entry_version']                    = 'إصدار الإضافة';
$_['entry_oc_version']                 = 'إصدار OpenCart';
$_['entry_license']                    = 'مفتاح الترخيص';

// إعدادات متقدمة
$_['entry_settings_use_option_id']     = 'استخدام معرف الخيار';
$_['entry_settings_use_option_value_id'] = 'استخدام معرف قيمة الخيار';
$_['entry_settings_use_attribute_group_id'] = 'استخدام معرف مجموعة الخصائص';
$_['entry_settings_use_attribute_id']  = 'استخدام معرف الخاصية';
$_['entry_settings_use_filter_group_id'] = 'استخدام معرف مجموعة الفلاتر';
$_['entry_settings_use_filter_id']     = 'استخدام معرف الفلتر';

// التبويبات
$_['tab_export']                       = 'تصدير';
$_['tab_import']                       = 'استيراد';
$_['tab_settings']                     = 'الإعدادات';
$_['tab_support']                      = 'الدعم';

// الأزرار
$_['button_export']                    = 'تصدير';
$_['button_import']                    = 'استيراد';
$_['button_settings']                  = 'حفظ الإعدادات';
$_['button_export_id']                 = 'تصدير بالمعرف';
$_['button_export_page']               = 'تصدير بالصفحة';
$_['button_back']                      = 'رجوع';

// نصوص المساعدة
$_['help_range_type']                  = 'اختر نوع النطاق: بالمعرف أو بالصفحة';
$_['help_category_filter']             = 'فلتر المنتجات حسب الفئة المحددة';
$_['help_incremental_yes']             = 'التحديث التدريجي: إضافة البيانات الجديدة فقط';
$_['help_incremental_no']              = 'الاستبدال الكامل: حذف البيانات الموجودة واستبدالها';
$_['help_import']                      = 'رفع ملف Excel/ODS لاستيراد البيانات (مع دعم الفلاتر)';
$_['help_import_old']                  = 'رفع ملف Excel/ODS لاستيراد البيانات (بدون دعم الفلاتر)';
$_['help_format']                      = 'الصيغ المدعومة: .xls, .xlsx, .ods';

// رسائل الخطأ
$_['error_permission']                 = 'تحذير: ليس لديك صلاحية للوصول إلى نظام التصدير والاستيراد!';
$_['error_upload']                     = 'خطأ: فشل في رفع الملف!';
$_['error_select_file']                = 'خطأ: يرجى اختيار ملف للرفع!';
$_['error_post_max_size']              = 'خطأ: حجم الملف يتجاوز الحد الأقصى المسموح (%1)!';
$_['error_upload_max_filesize']        = 'خطأ: حجم الملف يتجاوز الحد الأقصى لرفع الملفات (%1)!';
$_['error_upload_name']                = 'خطأ: اسم الملف غير صحيح!';
$_['error_upload_ext']                 = 'خطأ: صيغة الملف غير مدعومة! الصيغ المدعومة: .xls, .xlsx, .ods';
$_['error_incremental']                = 'خطأ: يرجى تحديد نوع التحديث (تدريجي أم كامل)!';
$_['error_id_no_data']                 = 'خطأ: لا توجد بيانات في النطاق المحدد!';
$_['error_page_no_data']               = 'خطأ: لا توجد بيانات في الصفحة المحددة!';
$_['error_param_not_number']           = 'خطأ: المعاملات يجب أن تكون أرقام!';
$_['error_notifications']              = 'خطأ: فشل في تحميل الإشعارات!';
$_['error_no_news']                    = 'لا توجد أخبار جديدة';
$_['error_batch_number']               = 'خطأ: رقم الدفعة غير صحيح!';
$_['error_min_item_id']                = 'خطأ: معرف العنصر الأدنى غير صحيح!';

// أخطاء التحقق من الأسماء المكررة
$_['error_option_name']                = 'خطأ: اسم الخيار "%1" مكرر! يرجى تفعيل "استخدام معرف الخيار" في الإعدادات';
$_['error_option_value_name']          = 'خطأ: اسم قيمة الخيار "%1" مكرر! يرجى تفعيل "استخدام معرف قيمة الخيار" في الإعدادات';
$_['error_attribute_group_name']       = 'خطأ: اسم مجموعة الخصائص "%1" مكرر! يرجى تفعيل "استخدام معرف مجموعة الخصائص" في الإعدادات';
$_['error_attribute_name']             = 'خطأ: اسم الخاصية "%1" مكرر! يرجى تفعيل "استخدام معرف الخاصية" في الإعدادات';
$_['error_filter_group_name']          = 'خطأ: اسم مجموعة الفلاتر "%1" مكرر! يرجى تفعيل "استخدام معرف مجموعة الفلاتر" في الإعدادات';
$_['error_filter_name']                = 'خطأ: اسم الفلتر "%1" مكرر! يرجى تفعيل "استخدام معرف الفلتر" في الإعدادات';

// نصوص تفصيلية للسجل
$_['text_log_details_3_x']             = 'لمزيد من التفاصيل، راجع <a href="%1">سجل الأخطاء</a>';

// نصوص إضافية للواجهة
$_['text_export_data']                 = 'تصدير البيانات';
$_['text_import_data']                 = 'استيراد البيانات';
$_['text_file_format']                 = 'صيغة الملف';
$_['text_data_range']                  = 'نطاق البيانات';
$_['text_export_options']              = 'خيارات التصدير';
$_['text_import_options']              = 'خيارات الاستيراد';
$_['text_advanced_settings']           = 'الإعدادات المتقدمة';
$_['text_system_info']                 = 'معلومات النظام';
$_['text_support_info']                = 'معلومات الدعم';

// معلومات الملف
$_['text_file_size_limit']             = 'الحد الأقصى لحجم الملف';
$_['text_supported_formats']           = 'الصيغ المدعومة';
$_['text_upload_progress']             = 'تقدم الرفع';
$_['text_processing']                  = 'جاري المعالجة...';
$_['text_completed']                   = 'تم الانتهاء';
$_['text_failed']                      = 'فشل';

// إحصائيات التصدير/الاستيراد
$_['text_total_records']               = 'إجمالي السجلات';
$_['text_exported_records']            = 'السجلات المصدرة';
$_['text_imported_records']            = 'السجلات المستوردة';
$_['text_skipped_records']             = 'السجلات المتجاهلة';
$_['text_error_records']               = 'السجلات الخاطئة';

// أنواع البيانات
$_['text_categories']                  = 'الفئات';
$_['text_products']                    = 'المنتجات';
$_['text_customers']                   = 'العملاء';
$_['text_orders']                      = 'الطلبات';
$_['text_options']                     = 'الخيارات';
$_['text_attributes']                  = 'الخصائص';
$_['text_filters']                     = 'الفلاتر';
$_['text_manufacturers']               = 'الشركات المصنعة';

// حالات المعالجة
$_['text_status_pending']              = 'في الانتظار';
$_['text_status_processing']           = 'قيد المعالجة';
$_['text_status_completed']            = 'مكتمل';
$_['text_status_failed']               = 'فشل';
$_['text_status_cancelled']            = 'ملغي';

// رسائل التأكيد
$_['text_confirm_export']              = 'هل أنت متأكد من تصدير البيانات المحددة؟';
$_['text_confirm_import']              = 'هل أنت متأكد من استيراد البيانات؟ سيتم استبدال البيانات الموجودة.';
$_['text_confirm_overwrite']           = 'هل تريد استبدال البيانات الموجودة؟';
$_['text_confirm_incremental']         = 'هل تريد إضافة البيانات الجديدة فقط؟';

// نصائح وإرشادات
$_['text_tip_export']                  = 'نصيحة: استخدم التصدير لإنشاء نسخة احتياطية من بياناتك';
$_['text_tip_import']                  = 'نصيحة: تأكد من صحة البيانات قبل الاستيراد';
$_['text_tip_backup']                  = 'نصيحة: قم بإنشاء نسخة احتياطية قبل الاستيراد';
$_['text_tip_format']                  = 'نصيحة: استخدم الملف المصدر كقالب للاستيراد';

// معلومات النظام
$_['text_php_version']                 = 'إصدار PHP';
$_['text_memory_limit']                = 'حد الذاكرة';
$_['text_max_execution_time']          = 'أقصى وقت تنفيذ';
$_['text_upload_max_filesize']         = 'أقصى حجم ملف للرفع';
$_['text_post_max_size']               = 'أقصى حجم POST';

// رسائل النجاح التفصيلية
$_['success_export_completed']         = 'تم تصدير %d سجل بنجاح';
$_['success_import_completed']         = 'تم استيراد %d سجل بنجاح';
$_['success_file_uploaded']            = 'تم رفع الملف بنجاح';
$_['success_settings_saved']           = 'تم حفظ الإعدادات بنجاح';

// رسائل تحذيرية
$_['warning_large_file']               = 'تحذير: الملف كبير الحجم، قد يستغرق وقتاً أطول للمعالجة';
$_['warning_memory_limit']             = 'تحذير: قد تحتاج لزيادة حد الذاكرة للملفات الكبيرة';
$_['warning_execution_time']           = 'تحذير: قد تحتاج لزيادة وقت التنفيذ للملفات الكبيرة';
$_['warning_backup_recommended']      = 'تحذير: يُنصح بإنشاء نسخة احتياطية قبل الاستيراد';

// تسميات الأعمدة للتصدير
$_['column_id']                        = 'المعرف';
$_['column_name']                      = 'الاسم';
$_['column_description']               = 'الوصف';
$_['column_status']                    = 'الحالة';
$_['column_sort_order']                = 'ترتيب الفرز';
$_['column_date_added']                = 'تاريخ الإضافة';
$_['column_date_modified']             = 'تاريخ التعديل';

// تنسيق التواريخ والأرقام
$_['date_format']                      = 'd/m/Y';
$_['datetime_format']                  = 'd/m/Y H:i:s';
$_['number_format']                    = '#,##0.00';

// رسائل التقدم
$_['text_preparing_export']            = 'جاري تحضير التصدير...';
$_['text_generating_file']             = 'جاري إنشاء الملف...';
$_['text_validating_data']             = 'جاري التحقق من البيانات...';
$_['text_importing_data']              = 'جاري استيراد البيانات...';
$_['text_finalizing']                  = 'جاري الانتهاء...';

// معلومات الترخيص والدعم
$_['text_license_info']                = 'معلومات الترخيص';
$_['text_support_contact']             = 'للدعم الفني، تواصل معنا';
$_['text_documentation']               = 'الوثائق والمساعدة';
$_['text_version_info']                = 'معلومات الإصدار';

?>
