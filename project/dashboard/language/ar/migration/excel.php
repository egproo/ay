<?php
/**
 * نظام أيم ERP: نظام إدارة موارد الشركات المتكامل للتجارة والتوزيع
 * 
 * Language File: Migration from Excel Files (Arabic)
 */

// Heading
$_['heading_title']                    = 'الانتقال من ملفات Excel';
$_['text_migration']                   = 'الانتقال من الأنظمة الأخرى';
$_['text_excel_migration']             = 'استيراد من ملفات Excel';

// Text
$_['text_home']                        = 'الرئيسية';
$_['text_form']                        = 'نموذج الاستيراد';
$_['text_success']                     = 'تم بنجاح';
$_['text_error']                       = 'خطأ';
$_['text_loading']                     = 'جاري التحميل...';
$_['text_importing']                   = 'جاري استيراد البيانات...';
$_['text_progress']                    = 'تقدم العملية';
$_['text_complete']                    = 'اكتمل';
$_['text_pending']                     = 'في الانتظار';
$_['text_processing']                  = 'جاري المعالجة';

// Migration Steps
$_['text_step1_title']                 = 'رفع الملف';
$_['text_step1_description']           = 'اختيار ملف Excel للاستيراد';
$_['text_step2_title']                 = 'ربط الأوراق';
$_['text_step2_description']           = 'ربط أوراق العمل بأنواع البيانات';
$_['text_step3_title']                 = 'مراجعة البيانات';
$_['text_step3_description']           = 'مراجعة واعتماد البيانات المستوردة';
$_['text_step4_title']                 = 'إكمال الاستيراد';
$_['text_step4_description']           = 'تطبيق البيانات في النظام';

// File Upload
$_['entry_excel_file']                 = 'ملف Excel';
$_['help_excel_file']                  = 'اختر ملف Excel (.xlsx أو .xls) يحتوي على البيانات المراد استيرادها';
$_['text_supported_formats']           = 'التنسيقات المدعومة: .xlsx, .xls';
$_['text_max_file_size']               = 'الحد الأقصى لحجم الملف: 50 ميجابايت';

// Sheet Mapping
$_['text_sheet_mapping']               = 'ربط أوراق العمل';
$_['text_core_data']                   = 'البيانات الأساسية';
$_['text_additional_data']             = 'البيانات الإضافية';
$_['text_select']                      = '-- اختر --';

// Data Types
$_['text_products']                    = 'المنتجات';
$_['text_customers']                   = 'العملاء';
$_['text_orders']                      = 'الطلبات';
$_['text_categories']                  = 'فئات المنتجات';
$_['text_suppliers']                   = 'الموردين';
$_['text_inventory']                   = 'المخزون';
$_['text_attributes']                  = 'خصائص المنتجات';
$_['text_manufacturers']               = 'الشركات المصنعة';
$_['text_options']                     = 'خيارات المنتجات';
$_['text_discounts']                   = 'الخصومات';
$_['text_specials']                    = 'العروض الخاصة';
$_['text_reviews']                     = 'تقييمات المنتجات';

// Field Mapping
$_['text_field_mapping']               = 'ربط الحقول';
$_['text_source_field']                = 'الحقل المصدر (Excel)';
$_['text_target_field']                = 'الحقل الهدف (أيم)';
$_['text_auto_mapping']                = 'ربط تلقائي';
$_['text_manual_mapping']              = 'ربط يدوي';
$_['text_skip_field']                  = 'تجاهل الحقل';
$_['text_required_field']              = 'حقل مطلوب';
$_['text_optional_field']              = 'حقل اختياري';

// Validation and Review
$_['text_validation_results']          = 'نتائج التحقق';
$_['text_data_preview']                = 'معاينة البيانات';
$_['text_validation_passed']           = 'تم التحقق بنجاح';
$_['text_validation_failed']           = 'فشل التحقق';
$_['text_validation_warnings']         = 'تحذيرات التحقق';
$_['text_duplicate_records']           = 'سجلات مكررة';
$_['text_missing_data']                = 'بيانات مفقودة';
$_['text_invalid_format']              = 'تنسيق غير صحيح';

// Progress and Status
$_['text_records_imported']            = 'تم استيراد %s سجل';
$_['text_records_processed']           = 'تم معالجة %s من %s سجل';
$_['text_migration_status']            = 'حالة الاستيراد';
$_['text_estimated_time']              = 'الوقت المتوقع';
$_['text_elapsed_time']                = 'الوقت المنقضي';
$_['text_remaining_time']              = 'الوقت المتبقي';

// Buttons
$_['button_upload']                    = 'رفع الملف';
$_['button_import']                    = 'بدء الاستيراد';
$_['button_review']                    = 'مراجعة البيانات';
$_['button_validate']                  = 'التحقق من البيانات';
$_['button_migrate']                   = 'تنفيذ الاستيراد';
$_['button_cancel']                    = 'إلغاء';
$_['button_retry']                     = 'إعادة المحاولة';
$_['button_download_template']         = 'تحميل قالب Excel';
$_['button_download_log']              = 'تحميل سجل العمليات';
$_['button_clear_data']                = 'مسح البيانات المؤقتة';

// Alerts and Messages
$_['alert_backup']                     = 'تحذير: يُنصح بشدة بعمل نسخة احتياطية من قاعدة البيانات قبل بدء عملية الاستيراد!';
$_['alert_required_fields']            = 'يرجى ملء جميع الحقول المطلوبة';
$_['alert_file_uploaded']              = 'تم رفع الملف بنجاح';
$_['alert_migration_complete']         = 'تم إكمال عملية الاستيراد بنجاح';
$_['alert_migration_partial']          = 'تم إكمال الاستيراد جزئياً مع بعض التحذيرات';
$_['alert_test_mode']                  = 'أنت في وضع الاختبار - لن يتم حفظ البيانات فعلياً';

// Error Messages
$_['error_permission']                 = 'تحذير: ليس لديك صلاحية للوصول إلى استيراد البيانات من Excel!';
$_['error_file']                       = 'يرجى اختيار ملف للرفع';
$_['error_file_type']                  = 'نوع الملف غير مدعوم. يرجى اختيار ملف Excel (.xlsx أو .xls)';
$_['error_file_size']                  = 'حجم الملف كبير جداً. الحد الأقصى المسموح: 50 ميجابايت';
$_['error_file_corrupt']               = 'الملف تالف أو لا يمكن قراءته';
$_['error_encoding']                   = 'ترميز الملف غير صحيح';
$_['error_mapping']                    = 'خطأ في ربط الحقول';
$_['error_required']                   = 'هذا الحقل مطلوب';
$_['error_processing']                 = 'خطأ أثناء معالجة البيانات';
$_['error_validation']                 = 'فشل في التحقق من صحة البيانات';
$_['error_duplicate_data']             = 'توجد بيانات مكررة';
$_['error_missing_dependencies']       = 'توجد تبعيات مفقودة';
$_['error_timeout']                    = 'انتهت مهلة العملية';
$_['error_insufficient_space']         = 'مساحة التخزين غير كافية';
$_['error_memory_limit']               = 'تم تجاوز حد الذاكرة المسموح';
$_['error_no_sheets']                  = 'لم يتم العثور على أوراق عمل في الملف';
$_['error_empty_sheet']                = 'ورقة العمل فارغة';
$_['error_invalid_headers']            = 'عناوين الأعمدة غير صحيحة';

// Success Messages
$_['success_file_uploaded']            = 'تم رفع الملف بنجاح';
$_['success_data_imported']            = 'تم استيراد البيانات بنجاح';
$_['success_validation']               = 'تم التحقق من البيانات بنجاح';
$_['success_migration']                = 'تم إكمال عملية الاستيراد بنجاح';
$_['success_mapping_saved']            = 'تم حفظ ربط الحقول بنجاح';

// Help Text
$_['help_migration_mode']              = 'اختر نمط الاستيراد المناسب لاحتياجاتك';
$_['help_field_mapping']               = 'قم بربط أعمدة Excel مع حقول نظام أيم المقابلة';
$_['help_validation']                  = 'التحقق من صحة البيانات قبل الاستيراد الفعلي';
$_['help_backup']                      = 'عمل نسخة احتياطية يحميك من فقدان البيانات';
$_['help_test_mode']                   = 'وضع الاختبار يتيح لك مراجعة النتائج دون تطبيق التغييرات';

// Migration Statistics
$_['text_total_records']               = 'إجمالي السجلات';
$_['text_successful_imports']          = 'الاستيرادات الناجحة';
$_['text_failed_imports']              = 'الاستيرادات الفاشلة';
$_['text_skipped_records']             = 'السجلات المتجاهلة';
$_['text_duplicate_records_found']     = 'السجلات المكررة الموجودة';
$_['text_data_conflicts']              = 'تضارب البيانات';
$_['text_migration_summary']           = 'ملخص عملية الاستيراد';

// Advanced Options
$_['text_advanced_options']            = 'خيارات متقدمة';
$_['text_batch_size']                  = 'حجم الدفعة';
$_['text_timeout_settings']            = 'إعدادات المهلة الزمنية';
$_['text_error_handling']              = 'معالجة الأخطاء';
$_['text_continue_on_error']           = 'المتابعة عند حدوث خطأ';
$_['text_stop_on_error']               = 'التوقف عند حدوث خطأ';
$_['text_log_level']                   = 'مستوى التسجيل';
$_['text_detailed_logging']            = 'تسجيل مفصل';
$_['text_minimal_logging']             = 'تسجيل أساسي';

// Data Transformation
$_['text_data_transformation']         = 'تحويل البيانات';
$_['text_currency_conversion']         = 'تحويل العملات';
$_['text_date_format_conversion']      = 'تحويل تنسيق التاريخ';
$_['text_unit_conversion']             = 'تحويل الوحدات';
$_['text_price_adjustment']            = 'تعديل الأسعار';
$_['text_tax_recalculation']           = 'إعادة حساب الضرائب';

// Excel Specific
$_['text_excel_worksheets']            = 'أوراق عمل Excel';
$_['text_worksheet_selection']         = 'اختيار أوراق العمل';
$_['text_header_row']                  = 'صف العناوين';
$_['text_data_rows']                   = 'صفوف البيانات';
$_['text_skip_rows']                   = 'تجاهل الصفوف';
$_['text_column_mapping']              = 'ربط الأعمدة';
$_['text_data_types']                  = 'أنواع البيانات';
$_['text_cell_formatting']             = 'تنسيق الخلايا';

// Templates
$_['text_excel_templates']             = 'قوالب Excel';
$_['text_download_template']           = 'تحميل القالب';
$_['text_product_template']            = 'قالب المنتجات';
$_['text_customer_template']           = 'قالب العملاء';
$_['text_order_template']              = 'قالب الطلبات';
$_['text_category_template']           = 'قالب الفئات';
$_['text_supplier_template']           = 'قالب الموردين';
$_['text_inventory_template']          = 'قالب المخزون';

// Column Headers (for templates)
$_['column_product_name']              = 'اسم المنتج';
$_['column_product_model']             = 'موديل المنتج';
$_['column_product_sku']               = 'رمز المنتج (SKU)';
$_['column_product_price']             = 'السعر';
$_['column_product_quantity']          = 'الكمية';
$_['column_product_status']            = 'الحالة';
$_['column_product_description']       = 'الوصف';
$_['column_customer_firstname']        = 'الاسم الأول';
$_['column_customer_lastname']         = 'الاسم الأخير';
$_['column_customer_email']            = 'البريد الإلكتروني';
$_['column_customer_telephone']        = 'رقم الهاتف';
$_['column_customer_address']          = 'العنوان';

// Migration Log
$_['text_migration_log']               = 'سجل عملية الاستيراد';
$_['text_view_log']                    = 'عرض السجل';
$_['text_download_log']                = 'تحميل السجل';
$_['text_clear_log']                   = 'مسح السجل';
$_['text_log_entry']                   = 'إدخال السجل';
$_['text_timestamp']                   = 'الطابع الزمني';
$_['text_log_level']                   = 'مستوى السجل';
$_['text_log_message']                 = 'رسالة السجل';

// Status Values
$_['status_pending']                   = 'في الانتظار';
$_['status_in_progress']               = 'قيد التنفيذ';
$_['status_completed']                 = 'مكتمل';
$_['status_failed']                    = 'فاشل';
$_['status_cancelled']                 = 'ملغي';
$_['status_paused']                    = 'متوقف مؤقتاً';

// Tooltips
$_['tooltip_excel_file']               = 'اختر ملف Excel يحتوي على البيانات المراد استيرادها';
$_['tooltip_sheet_mapping']            = 'حدد أي ورقة عمل تحتوي على أي نوع من البيانات';
$_['tooltip_field_mapping']            = 'اربط أعمدة Excel مع حقول النظام';
$_['tooltip_validation']               = 'تحقق من صحة البيانات قبل الاستيراد';
$_['tooltip_backup']                   = 'عمل نسخة احتياطية قبل بدء الاستيراد';

// Migration Types
$_['text_migration_type']              = 'نوع الاستيراد';
$_['text_full_import']                 = 'استيراد كامل';
$_['text_incremental_import']          = 'استيراد تدريجي';
$_['text_update_existing']             = 'تحديث الموجود';
$_['text_insert_new_only']             = 'إدراج الجديد فقط';
