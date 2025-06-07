<?php
/**
 * نظام أيم ERP: نظام إدارة موارد الشركات المتكامل للتجارة والتوزيع
 * 
 * Language File: Migration from Shopify System (Arabic)
 */

// Heading
$_['heading_title']                    = 'الانتقال من نظام شوبيفاي (Shopify)';
$_['text_migration']                   = 'الانتقال من الأنظمة الأخرى';
$_['text_shopify_migration']           = 'الانتقال من نظام شوبيفاي';

// Text
$_['text_home']                        = 'الرئيسية';
$_['text_form']                        = 'نموذج الانتقال';
$_['text_success']                     = 'تم بنجاح';
$_['text_error']                       = 'خطأ';
$_['text_loading']                     = 'جاري التحميل...';
$_['text_importing']                   = 'جاري استيراد البيانات...';
$_['text_progress']                    = 'تقدم العملية';
$_['text_complete']                    = 'اكتمل';
$_['text_pending']                     = 'في الانتظار';
$_['text_processing']                  = 'جاري المعالجة';

// Migration Steps
$_['text_step1_title']                 = 'إعداد الاتصال';
$_['text_step1_description']           = 'إدخال بيانات الاتصال بمتجر شوبيفاي';
$_['text_step2_title']                 = 'اختيار البيانات';
$_['text_step2_description']           = 'تحديد البيانات المراد نقلها';
$_['text_step3_title']                 = 'مراجعة البيانات';
$_['text_step3_description']           = 'مراجعة واعتماد البيانات المستوردة';
$_['text_step4_title']                 = 'إكمال النقل';
$_['text_step4_description']           = 'تطبيق البيانات في النظام الجديد';

// Connection Settings
$_['entry_shop_domain']                = 'نطاق المتجر';
$_['entry_access_token']               = 'رمز الوصول (Access Token)';
$_['entry_api_version']                = 'إصدار API';
$_['help_shop_domain']                 = 'مثال: mystore (بدون .myshopify.com)';
$_['help_access_token']                = 'رمز الوصول من إعدادات التطبيقات في شوبيفاي';
$_['help_api_setup']                   = 'يجب إنشاء تطبيق خاص في شوبيفاي للحصول على رمز الوصول';

// Data Selection
$_['text_data_selection']              = 'اختيار البيانات للنقل';
$_['text_core_data']                   = 'البيانات الأساسية';
$_['text_additional_data']             = 'البيانات الإضافية';
$_['text_ecommerce_data']              = 'بيانات التجارة الإلكترونية';

// Data Types
$_['text_products']                    = 'المنتجات';
$_['text_customers']                   = 'العملاء';
$_['text_orders']                      = 'الطلبات';
$_['text_collections']                 = 'مجموعات المنتجات';
$_['text_discounts']                   = 'كوبونات الخصم';
$_['text_inventory']                   = 'المخزون';
$_['text_variants']                    = 'تنويعات المنتجات';
$_['text_images']                      = 'صور المنتجات';
$_['text_metafields']                  = 'الحقول الإضافية';
$_['text_fulfillments']                = 'عمليات التنفيذ';
$_['text_transactions']                = 'المعاملات المالية';
$_['text_refunds']                     = 'المرتجعات والاسترداد';
$_['text_gift_cards']                  = 'بطاقات الهدايا';
$_['text_price_rules']                 = 'قواعد التسعير';
$_['text_shipping_zones']              = 'مناطق الشحن';
$_['text_locations']                   = 'المواقع والفروع';

// Product Types
$_['text_simple_products']             = 'المنتجات البسيطة';
$_['text_variant_products']            = 'المنتجات ذات التنويعات';
$_['text_digital_products']            = 'المنتجات الرقمية';
$_['text_gift_card_products']          = 'بطاقات الهدايا';

// Order Status
$_['text_pending_orders']              = 'الطلبات المعلقة';
$_['text_authorized_orders']           = 'الطلبات المعتمدة';
$_['text_partially_paid_orders']       = 'الطلبات مدفوعة جزئياً';
$_['text_paid_orders']                 = 'الطلبات المدفوعة';
$_['text_partially_refunded_orders']   = 'الطلبات مستردة جزئياً';
$_['text_refunded_orders']             = 'الطلبات المستردة';
$_['text_voided_orders']               = 'الطلبات الملغية';

// Fulfillment Status
$_['text_fulfilled_orders']            = 'الطلبات المنفذة';
$_['text_partial_orders']              = 'الطلبات المنفذة جزئياً';
$_['text_restocked_orders']            = 'الطلبات المعادة للمخزون';

// Migration Options
$_['text_migration_mode']              = 'نمط النقل';
$_['text_full_migration']              = 'نقل كامل';
$_['text_selective_migration']         = 'نقل انتقائي';
$_['text_test_migration']              = 'نقل تجريبي';
$_['text_incremental_migration']       = 'نقل تدريجي';

// Data Mapping
$_['text_field_mapping']               = 'ربط الحقول';
$_['text_source_field']                = 'الحقل المصدر (شوبيفاي)';
$_['text_target_field']                = 'الحقل الهدف (أيم)';
$_['text_auto_mapping']                = 'ربط تلقائي';
$_['text_manual_mapping']              = 'ربط يدوي';
$_['text_skip_field']                  = 'تجاهل الحقل';

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
$_['text_migration_status']            = 'حالة النقل';
$_['text_estimated_time']              = 'الوقت المتوقع';
$_['text_elapsed_time']                = 'الوقت المنقضي';
$_['text_remaining_time']              = 'الوقت المتبقي';

// Buttons
$_['button_connect']                   = 'اختبار الاتصال';
$_['button_import']                    = 'بدء الاستيراد';
$_['button_review']                    = 'مراجعة البيانات';
$_['button_validate']                  = 'التحقق من البيانات';
$_['button_migrate']                   = 'تنفيذ النقل';
$_['button_cancel']                    = 'إلغاء';
$_['button_retry']                     = 'إعادة المحاولة';
$_['button_download_log']              = 'تحميل سجل العمليات';
$_['button_export_mapping']            = 'تصدير ربط الحقول';
$_['button_import_mapping']            = 'استيراد ربط الحقول';

// Alerts and Messages
$_['alert_backup']                     = 'تحذير: يُنصح بشدة بعمل نسخة احتياطية من قاعدة البيانات قبل بدء عملية النقل!';
$_['alert_required_fields']            = 'يرجى ملء جميع الحقول المطلوبة';
$_['alert_connection_success']         = 'تم الاتصال بمتجر شوبيفاي بنجاح';
$_['alert_migration_complete']         = 'تم إكمال عملية النقل بنجاح';
$_['alert_migration_partial']          = 'تم إكمال النقل جزئياً مع بعض التحذيرات';
$_['alert_test_mode']                  = 'أنت في وضع الاختبار - لن يتم حفظ البيانات فعلياً';

// Error Messages
$_['error_permission']                 = 'تحذير: ليس لديك صلاحية للوصول إلى نقل البيانات من شوبيفاي!';
$_['error_connection']                 = 'فشل الاتصال بمتجر شوبيفاي. يرجى التحقق من البيانات المدخلة';
$_['error_authentication']             = 'فشل في المصادقة. يرجى التحقق من رمز الوصول';
$_['error_shop_domain']                = 'نطاق المتجر غير صحيح أو غير متاح';
$_['error_api_access']                 = 'لا يمكن الوصول إلى API الخاص بشوبيفاي';
$_['error_invalid_source']             = 'مصدر البيانات غير صحيح';
$_['error_file']                       = 'خطأ في الملف المرفوع';
$_['error_file_type']                  = 'نوع الملف غير مدعوم';
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
$_['error_api_rate_limit']             = 'تم تجاوز حد معدل طلبات API';
$_['error_invalid_token']              = 'رمز الوصول غير صحيح أو منتهي الصلاحية';
$_['error_insufficient_permissions']   = 'صلاحيات رمز الوصول غير كافية';

// Success Messages
$_['success_connection']               = 'تم الاتصال بمتجر شوبيفاي بنجاح';
$_['success_data_imported']            = 'تم استيراد البيانات بنجاح';
$_['success_validation']               = 'تم التحقق من البيانات بنجاح';
$_['success_migration']                = 'تم إكمال عملية النقل بنجاح';
$_['success_mapping_saved']            = 'تم حفظ ربط الحقول بنجاح';

// Help Text
$_['help_migration_mode']              = 'اختر نمط النقل المناسب لاحتياجاتك';
$_['help_field_mapping']               = 'قم بربط حقول شوبيفاي مع حقول نظام أيم المقابلة';
$_['help_validation']                  = 'التحقق من صحة البيانات قبل النقل الفعلي';
$_['help_backup']                      = 'عمل نسخة احتياطية يحميك من فقدان البيانات';
$_['help_test_mode']                   = 'وضع الاختبار يتيح لك مراجعة النتائج دون تطبيق التغييرات';

// Migration Statistics
$_['text_total_records']               = 'إجمالي السجلات';
$_['text_successful_imports']          = 'الاستيرادات الناجحة';
$_['text_failed_imports']              = 'الاستيرادات الفاشلة';
$_['text_skipped_records']             = 'السجلات المتجاهلة';
$_['text_duplicate_records_found']     = 'السجلات المكررة الموجودة';
$_['text_data_conflicts']              = 'تضارب البيانات';
$_['text_migration_summary']           = 'ملخص عملية النقل';

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

// Shopify Specific
$_['text_shopify_apps']                = 'تطبيقات شوبيفاي';
$_['text_themes']                      = 'القوالب';
$_['text_liquid_templates']            = 'قوالب Liquid';
$_['text_webhooks']                    = 'Webhooks';
$_['text_scripts']                     = 'السكريبتات';
$_['text_checkout_settings']           = 'إعدادات الدفع';
$_['text_payment_gateways']            = 'بوابات الدفع';
$_['text_shipping_settings']           = 'إعدادات الشحن';
$_['text_tax_settings']                = 'إعدادات الضرائب';

// Post-Migration
$_['text_post_migration']              = 'ما بعد النقل';
$_['text_data_verification']           = 'التحقق من البيانات';
$_['text_system_optimization']         = 'تحسين النظام';
$_['text_user_training']               = 'تدريب المستخدمين';
$_['text_go_live_checklist']           = 'قائمة التشغيل الفعلي';

// Migration Types
$_['text_migration_type']              = 'نوع النقل';
$_['text_one_time_migration']          = 'نقل لمرة واحدة';
$_['text_ongoing_sync']                = 'مزامنة مستمرة';
$_['text_scheduled_migration']         = 'نقل مجدول';

// Tooltips
$_['tooltip_shop_domain']              = 'أدخل نطاق متجر شوبيفاي (بدون .myshopify.com)';
$_['tooltip_access_token']             = 'رمز الوصول من تطبيق شوبيفاي الخاص';
$_['tooltip_test_connection']          = 'اختبار الاتصال قبل بدء النقل';
$_['tooltip_batch_size']               = 'عدد السجلات المعالجة في كل دفعة';
$_['tooltip_backup']                   = 'عمل نسخة احتياطية قبل بدء النقل';

// Column Headers
$_['column_source_table']              = 'جدول المصدر';
$_['column_target_table']              = 'جدول الهدف';
$_['column_record_count']              = 'عدد السجلات';
$_['column_status']                    = 'الحالة';
$_['column_last_updated']              = 'آخر تحديث';
$_['column_actions']                   = 'الإجراءات';

// Status Values
$_['status_pending']                   = 'في الانتظار';
$_['status_in_progress']               = 'قيد التنفيذ';
$_['status_completed']                 = 'مكتمل';
$_['status_failed']                    = 'فاشل';
$_['status_cancelled']                 = 'ملغي';
$_['status_paused']                    = 'متوقف مؤقتاً';

// File Types
$_['text_supported_formats']           = 'التنسيقات المدعومة';
$_['text_csv_format']                  = 'ملف CSV';
$_['text_xml_format']                  = 'ملف XML';
$_['text_json_format']                 = 'ملف JSON';
$_['text_excel_format']                = 'ملف Excel';

// Migration Log
$_['text_migration_log']               = 'سجل عملية النقل';
$_['text_view_log']                    = 'عرض السجل';
$_['text_download_log']                = 'تحميل السجل';
$_['text_clear_log']                   = 'مسح السجل';
$_['text_log_entry']                   = 'إدخال السجل';
$_['text_timestamp']                   = 'الطابع الزمني';
$_['text_log_level']                   = 'مستوى السجل';
$_['text_log_message']                 = 'رسالة السجل';
