<?php
/**
 * Language: Shipment Management (Arabic)
 * ملف اللغة العربية لإدارة الشحنات
 *
 * @package    AYM ERP
 * @author     AYM ERP Development Team
 * @copyright  2024 AYM ERP
 * @license    Commercial License
 * @version    1.0.0
 * @link       https://aym-erp.com
 * @since      1.0.0
 */

// Heading
$_['heading_title']              = 'إدارة الشحنات';
$_['heading_title_report']       = 'تقارير الشحنات';

// Text
$_['text_success']               = 'تم: تم تعديل الشحنات بنجاح!';
$_['text_success_add']           = 'تم: تم إضافة الشحنة بنجاح!';
$_['text_success_edit']          = 'تم: تم تعديل الشحنة بنجاح!';
$_['text_success_delete']        = 'تم: تم حذف الشحنات المحددة بنجاح!';
$_['text_list']                  = 'قائمة الشحنات';
$_['text_add']                   = 'إضافة شحنة';
$_['text_edit']                  = 'تعديل شحنة';
$_['text_form']                  = 'نموذج الشحنة';
$_['text_enabled']               = 'مفعل';
$_['text_disabled']              = 'معطل';
$_['text_none']                  = 'لا يوجد';
$_['text_select']                = 'اختر';
$_['text_all_statuses']          = 'جميع الحالات';
$_['text_all_carriers']          = 'جميع شركات الشحن';
$_['text_confirm']               = 'هل أنت متأكد؟';
$_['text_loading']               = 'جاري التحميل...';
$_['text_no_results']            = 'لا توجد نتائج!';

// Shipment Status
$_['text_status_pending']        = 'معلق';
$_['text_status_processing']     = 'قيد المعالجة';
$_['text_status_shipped']        = 'تم الشحن';
$_['text_status_in_transit']     = 'في الطريق';
$_['text_status_out_for_delivery'] = 'خارج للتسليم';
$_['text_status_delivered']      = 'تم التسليم';
$_['text_status_returned']       = 'مرتجع';
$_['text_status_cancelled']      = 'ملغي';

// Column
$_['column_shipment_number']     = 'رقم الشحنة';
$_['column_order_number']        = 'رقم الطلب';
$_['column_customer']            = 'العميل';
$_['column_carrier']             = 'شركة الشحن';
$_['column_status']              = 'الحالة';
$_['column_date_shipped']        = 'تاريخ الشحن';
$_['column_tracking_number']     = 'رقم التتبع';
$_['column_shipping_cost']       = 'تكلفة الشحن';
$_['column_weight']              = 'الوزن';
$_['column_dimensions']          = 'الأبعاد';
$_['column_estimated_delivery']  = 'التسليم المتوقع';
$_['column_action']              = 'إجراء';

// Entry
$_['entry_order']                = 'الطلب';
$_['entry_customer']             = 'العميل';
$_['entry_carrier']              = 'شركة الشحن';
$_['entry_tracking_number']      = 'رقم التتبع';
$_['entry_shipping_method']      = 'طريقة الشحن';
$_['entry_weight']               = 'الوزن (كجم)';
$_['entry_dimensions']           = 'الأبعاد (طول×عرض×ارتفاع)';
$_['entry_insurance_value']      = 'قيمة التأمين';
$_['entry_delivery_instructions'] = 'تعليمات التسليم';
$_['entry_pickup_date']          = 'تاريخ الاستلام';
$_['entry_estimated_delivery']   = 'التسليم المتوقع';
$_['entry_shipping_cost']        = 'تكلفة الشحن';
$_['entry_status']               = 'الحالة';
$_['entry_shipment_number']      = 'رقم الشحنة';
$_['entry_order_number']         = 'رقم الطلب';
$_['entry_date_from']            = 'من تاريخ';
$_['entry_date_to']              = 'إلى تاريخ';

// Button
$_['button_add']                 = 'إضافة';
$_['button_edit']                = 'تعديل';
$_['button_delete']              = 'حذف';
$_['button_save']                = 'حفظ';
$_['button_cancel']              = 'إلغاء';
$_['button_filter']              = 'فلترة';
$_['button_export']              = 'تصدير';
$_['button_track']               = 'تتبع';
$_['button_print_label']         = 'طباعة الملصق';
$_['button_update_tracking']     = 'تحديث التتبع';
$_['button_notify_customer']     = 'إشعار العميل';
$_['button_create_from_order']   = 'إنشاء من طلب';
$_['button_bulk_update']         = 'تحديث مجمع';
$_['button_cost_analysis']       = 'تحليل التكاليف';

// Tab
$_['tab_general']                = 'عام';
$_['tab_tracking']               = 'التتبع';
$_['tab_history']                = 'التاريخ';
$_['tab_statistics']             = 'الإحصائيات';

// Error
$_['error_permission']           = 'تحذير: ليس لديك صلاحية للوصول إلى الشحنات!';
$_['error_order_required']       = 'الطلب مطلوب!';
$_['error_carrier_required']     = 'شركة الشحن مطلوبة!';
$_['error_weight_numeric']       = 'الوزن يجب أن يكون رقماً!';
$_['error_shipping_cost_numeric'] = 'تكلفة الشحن يجب أن تكون رقماً!';
$_['error_shipment_id_required'] = 'معرف الشحنة مطلوب!';
$_['error_status_required']      = 'الحالة مطلوبة!';
$_['error_order_id_required']    = 'معرف الطلب مطلوب!';
$_['error_order_not_found']      = 'الطلب غير موجود!';
$_['error_order_not_complete']   = 'الطلب غير مكتمل!';
$_['error_shipment_already_exists'] = 'يوجد شحنة مسبقة لهذا الطلب!';
$_['error_create_shipment_failed'] = 'فشل في إنشاء الشحنة!';
$_['error_tracking_not_available'] = 'التتبع غير متوفر!';
$_['error_tracking_failed']      = 'فشل في التتبع!';
$_['error_update_failed']        = 'فشل في التحديث!';
$_['error_tracking_update_failed'] = 'فشل في تحديث التتبع!';
$_['error_notification_type_required'] = 'نوع الإشعار مطلوب!';
$_['error_notification_send_failed'] = 'فشل في إرسال الإشعار!';
$_['error_label_generation_failed'] = 'فشل في إنشاء الملصق!';
$_['error_analysis_failed']      = 'فشل في التحليل!';
$_['error_no_shipments_selected'] = 'لم يتم اختيار أي شحنات!';
$_['error_bulk_update_failed']   = 'فشل في التحديث المجمع!';
$_['error_cannot_delete_delivered'] = 'لا يمكن حذف الشحنات المسلمة!';

// Success Messages
$_['text_success_create_from_order'] = 'تم إنشاء الشحنة من الطلب بنجاح!';
$_['text_success_status_update'] = 'تم تحديث حالة الشحنة بنجاح!';
$_['text_success_tracking_update'] = 'تم تحديث معلومات التتبع بنجاح!';
$_['text_success_notification_sent'] = 'تم إرسال الإشعار بنجاح!';
$_['text_success_bulk_update']   = 'تم تحديث %s شحنة بنجاح!';

// Statistics
$_['text_statistics']            = 'الإحصائيات';
$_['text_total_shipments']       = 'إجمالي الشحنات';
$_['text_pending_shipments']     = 'الشحنات المعلقة';
$_['text_active_shipments']      = 'الشحنات النشطة';
$_['text_completed_shipments']   = 'الشحنات المكتملة';
$_['text_total_shipping_cost']   = 'إجمالي تكلفة الشحن';
$_['text_average_shipping_cost'] = 'متوسط تكلفة الشحن';
$_['text_performance']           = 'الأداء';
$_['text_carrier_analysis']      = 'تحليل شركات الشحن';

// Performance Metrics
$_['text_avg_delivery_time']     = 'متوسط وقت التسليم';
$_['text_on_time_delivery_rate'] = 'معدل التسليم في الوقت المحدد';
$_['text_return_rate']           = 'معدل الإرجاع';
$_['text_delivery_rate']         = 'معدل التسليم';

// Tracking
$_['text_tracking_info']         = 'معلومات التتبع';
$_['text_current_status']        = 'الحالة الحالية';
$_['text_current_location']      = 'الموقع الحالي';
$_['text_estimated_delivery']    = 'التسليم المتوقع';
$_['text_tracking_events']       = 'أحداث التتبع';
$_['text_event_date']            = 'تاريخ الحدث';
$_['text_event_status']          = 'حالة الحدث';
$_['text_event_location']        = 'موقع الحدث';
$_['text_event_description']     = 'وصف الحدث';

// Notifications
$_['text_notification_shipped']  = 'إشعار الشحن';
$_['text_notification_delivered'] = 'إشعار التسليم';
$_['text_notification_status_update'] = 'إشعار تحديث الحالة';

// Help
$_['help_tracking_number']       = 'رقم التتبع المقدم من شركة الشحن';
$_['help_dimensions']            = 'أدخل الأبعاد بالصيغة: طول×عرض×ارتفاع (بالسنتيمتر)';
$_['help_insurance_value']       = 'قيمة التأمين للشحنة (اختياري)';
$_['help_delivery_instructions'] = 'تعليمات خاصة للتسليم';

// Date Format
$_['date_format_short']          = 'd/m/Y';
$_['date_format_long']           = 'd/m/Y H:i';

// Currency Format
$_['currency_format']            = '%s جنيه';

// Weight Format
$_['weight_format']              = '%s كجم';

// Advanced Features
$_['text_advanced_search']       = 'البحث المتقدم';
$_['text_bulk_operations']       = 'العمليات المجمعة';
$_['text_cost_analysis']         = 'تحليل التكاليف';
$_['text_delayed_shipments']     = 'الشحنات المتأخرة';
$_['text_high_value_shipments']  = 'الشحنات عالية القيمة';
$_['text_daily_performance']     = 'الأداء اليومي';

// Reports
$_['text_shipment_report']       = 'تقرير الشحنات';
$_['text_performance_report']    = 'تقرير الأداء';
$_['text_cost_report']           = 'تقرير التكاليف';
$_['text_carrier_report']        = 'تقرير شركات الشحن';

// Export
$_['text_export_csv']            = 'تصدير CSV';
$_['text_export_excel']          = 'تصدير Excel';
$_['text_export_pdf']            = 'تصدير PDF';

// Filters
$_['text_filter_by_status']      = 'فلترة حسب الحالة';
$_['text_filter_by_carrier']     = 'فلترة حسب شركة الشحن';
$_['text_filter_by_date']        = 'فلترة حسب التاريخ';
$_['text_filter_by_cost']        = 'فلترة حسب التكلفة';

// Placeholders
$_['placeholder_search']         = 'البحث في الشحنات...';
$_['placeholder_tracking_number'] = 'أدخل رقم التتبع...';
$_['placeholder_weight']         = 'أدخل الوزن بالكيلوجرام...';
$_['placeholder_dimensions']     = 'مثال: 30x20x15';
$_['placeholder_cost']           = 'أدخل التكلفة...';

// Tooltips
$_['tooltip_track']              = 'تتبع الشحنة';
$_['tooltip_print_label']        = 'طباعة ملصق الشحن';
$_['tooltip_update_tracking']    = 'تحديث معلومات التتبع من شركة الشحن';
$_['tooltip_notify_customer']    = 'إرسال إشعار للعميل';
$_['tooltip_bulk_update']        = 'تحديث عدة شحنات في نفس الوقت';

// Confirmation Messages
$_['confirm_delete']             = 'هل أنت متأكد من حذف الشحنات المحددة؟';
$_['confirm_bulk_update']        = 'هل أنت متأكد من تحديث الشحنات المحددة؟';
$_['confirm_notify_customer']    = 'هل أنت متأكد من إرسال إشعار للعميل؟';

// Info Messages
$_['info_creating_shipment']     = 'جاري إنشاء الشحنة...';
$_['info_updating_tracking']     = 'جاري تحديث معلومات التتبع...';
$_['info_sending_notification']  = 'جاري إرسال الإشعار...';
$_['info_generating_label']      = 'جاري إنشاء ملصق الشحن...';
$_['info_analyzing_costs']       = 'جاري تحليل التكاليف...';

// Warning Messages
$_['warning_no_tracking']        = 'تحذير: لا يوجد رقم تتبع لهذه الشحنة';
$_['warning_delayed_shipment']   = 'تحذير: هذه الشحنة متأخرة عن الموعد المتوقع';
$_['warning_high_cost']          = 'تحذير: تكلفة الشحن عالية';

// Status Colors (for CSS classes)
$_['status_color_pending']       = 'warning';
$_['status_color_processing']    = 'info';
$_['status_color_shipped']       = 'primary';
$_['status_color_in_transit']    = 'info';
$_['status_color_out_for_delivery'] = 'warning';
$_['status_color_delivered']     = 'success';
$_['status_color_returned']      = 'danger';
$_['status_color_cancelled']     = 'secondary';

// Advanced Features - Carrier Integration
$_['text_carrier_integration']   = 'تكامل شركات الشحن';
$_['text_dhl_integration']       = 'تكامل DHL';
$_['text_fedex_integration']     = 'تكامل FedEx';
$_['text_ups_integration']       = 'تكامل UPS';
$_['text_aramex_integration']    = 'تكامل أرامكس';
$_['text_api_settings']          = 'إعدادات API';
$_['text_api_key']               = 'مفتاح API';
$_['text_api_secret']            = 'سر API';
$_['text_test_mode']             = 'وضع الاختبار';

// Advanced Features - Automation
$_['text_automation']            = 'الأتمتة';
$_['text_auto_tracking_update']  = 'تحديث التتبع التلقائي';
$_['text_auto_notifications']    = 'الإشعارات التلقائية';
$_['text_auto_status_update']    = 'تحديث الحالة التلقائي';
$_['text_scheduled_tasks']       = 'المهام المجدولة';

// Advanced Features - Analytics
$_['text_analytics']             = 'التحليلات';
$_['text_shipment_analytics']    = 'تحليلات الشحنات';
$_['text_cost_analytics']        = 'تحليلات التكلفة';
$_['text_performance_analytics'] = 'تحليلات الأداء';
$_['text_trend_analysis']        = 'تحليل الاتجاهات';
$_['text_predictive_analytics']  = 'التحليلات التنبؤية';

// Advanced Features - Optimization
$_['text_optimization']          = 'التحسين';
$_['text_route_optimization']    = 'تحسين المسارات';
$_['text_cost_optimization']     = 'تحسين التكلفة';
$_['text_carrier_selection']     = 'اختيار شركة الشحن';
$_['text_smart_routing']         = 'التوجيه الذكي';

// Advanced Features - Customer Experience
$_['text_customer_experience']   = 'تجربة العميل';
$_['text_tracking_page']         = 'صفحة التتبع';
$_['text_delivery_preferences']  = 'تفضيلات التسليم';
$_['text_delivery_notifications'] = 'إشعارات التسليم';
$_['text_customer_feedback']     = 'تقييم العميل';

// Advanced Features - Inventory Integration
$_['text_inventory_integration'] = 'تكامل المخزون';
$_['text_stock_allocation']      = 'تخصيص المخزون';
$_['text_warehouse_management']  = 'إدارة المستودعات';
$_['text_pick_pack_ship']        = 'اختيار-تعبئة-شحن';

// Advanced Features - International Shipping
$_['text_international_shipping'] = 'الشحن الدولي';
$_['text_customs_declaration']   = 'إقرار جمركي';
$_['text_duty_tax_calculation']  = 'حساب الرسوم والضرائب';
$_['text_restricted_items']      = 'العناصر المحظورة';
$_['text_country_restrictions']  = 'قيود الدول';

// Advanced Features - Insurance
$_['text_shipping_insurance']    = 'تأمين الشحن';
$_['text_insurance_coverage']    = 'تغطية التأمين';
$_['text_insurance_claims']      = 'مطالبات التأمين';
$_['text_damage_reports']        = 'تقارير الأضرار';

// Advanced Features - Returns Management
$_['text_returns_management']    = 'إدارة المرتجعات';
$_['text_return_authorization']  = 'تصريح الإرجاع';
$_['text_return_labels']         = 'ملصقات الإرجاع';
$_['text_return_tracking']       = 'تتبع المرتجعات';
$_['text_refund_processing']     = 'معالجة المبالغ المستردة';

// Advanced Features - Compliance
$_['text_compliance']            = 'الامتثال';
$_['text_shipping_regulations']  = 'لوائح الشحن';
$_['text_hazmat_shipping']       = 'شحن المواد الخطرة';
$_['text_documentation']         = 'التوثيق';
$_['text_audit_trail']           = 'مسار المراجعة';

// Advanced Features - Mobile
$_['text_mobile_features']       = 'ميزات الجوال';
$_['text_mobile_tracking']       = 'تتبع الجوال';
$_['text_driver_app']            = 'تطبيق السائق';
$_['text_delivery_confirmation'] = 'تأكيد التسليم';
$_['text_signature_capture']     = 'التقاط التوقيع';

// Advanced Features - Reporting
$_['text_advanced_reporting']    = 'التقارير المتقدمة';
$_['text_custom_reports']        = 'التقارير المخصصة';
$_['text_scheduled_reports']     = 'التقارير المجدولة';
$_['text_dashboard_widgets']     = 'عناصر لوحة التحكم';
$_['text_kpi_metrics']           = 'مقاييس الأداء الرئيسية';

// Advanced Features - Integration
$_['text_system_integration']    = 'تكامل الأنظمة';
$_['text_erp_integration']       = 'تكامل ERP';
$_['text_crm_integration']       = 'تكامل CRM';
$_['text_ecommerce_integration'] = 'تكامل التجارة الإلكترونية';
$_['text_api_endpoints']         = 'نقاط API';

// Advanced Features - Security
$_['text_security_features']     = 'ميزات الأمان';
$_['text_data_encryption']       = 'تشفير البيانات';
$_['text_access_control']        = 'التحكم في الوصول';
$_['text_user_permissions']      = 'صلاحيات المستخدم';
$_['text_secure_tracking']       = 'التتبع الآمن';

// Advanced Features - Notifications
$_['text_notification_system']   = 'نظام الإشعارات';
$_['text_email_notifications']   = 'إشعارات البريد الإلكتروني';
$_['text_sms_notifications']     = 'إشعارات الرسائل النصية';
$_['text_push_notifications']    = 'الإشعارات الفورية';
$_['text_webhook_notifications'] = 'إشعارات Webhook';

// Advanced Features - Workflow
$_['text_workflow_management']   = 'إدارة سير العمل';
$_['text_approval_workflow']     = 'سير عمل الموافقة';
$_['text_escalation_rules']      = 'قواعد التصعيد';
$_['text_business_rules']        = 'قواعد العمل';

// Advanced Features - Quality Control
$_['text_quality_control']       = 'مراقبة الجودة';
$_['text_delivery_quality']      = 'جودة التسليم';
$_['text_service_level']         = 'مستوى الخدمة';
$_['text_performance_monitoring'] = 'مراقبة الأداء';

// Advanced Error Messages
$_['error_api_connection']       = 'خطأ في الاتصال بـ API شركة الشحن';
$_['error_invalid_tracking']     = 'رقم التتبع غير صحيح';
$_['error_carrier_not_supported'] = 'شركة الشحن غير مدعومة';
$_['error_weight_limit_exceeded'] = 'تم تجاوز حد الوزن المسموح';
$_['error_dimension_limit_exceeded'] = 'تم تجاوز حد الأبعاد المسموح';
$_['error_restricted_destination'] = 'الوجهة محظورة للشحن';
$_['error_insufficient_insurance'] = 'تغطية التأمين غير كافية';

// Advanced Success Messages
$_['text_success_api_connection'] = 'تم الاتصال بـ API شركة الشحن بنجاح';
$_['text_success_label_generated'] = 'تم إنشاء ملصق الشحن بنجاح';
$_['text_success_tracking_updated'] = 'تم تحديث معلومات التتبع بنجاح';
$_['text_success_notification_sent'] = 'تم إرسال الإشعار بنجاح';
$_['text_success_return_processed'] = 'تم معالجة الإرجاع بنجاح';

// Advanced Help Text
$_['help_api_integration']       = 'قم بتكوين إعدادات API للتكامل مع شركات الشحن';
$_['help_auto_tracking']         = 'تحديث معلومات التتبع تلقائياً من شركة الشحن';
$_['help_notification_settings'] = 'تكوين الإشعارات التلقائية للعملاء';
$_['help_insurance_calculation'] = 'حساب قيمة التأمين بناءً على قيمة الشحنة';
$_['help_customs_declaration']   = 'إعداد الإقرار الجمركي للشحنات الدولية';

// Advanced Tooltips
$_['tooltip_api_test']           = 'اختبار الاتصال بـ API شركة الشحن';
$_['tooltip_auto_update']        = 'تحديث تلقائي لمعلومات التتبع';
$_['tooltip_bulk_print']         = 'طباعة ملصقات متعددة';
$_['tooltip_cost_calculator']    = 'حاسبة تكلفة الشحن';
$_['tooltip_route_optimizer']    = 'محسن المسارات';

// Advanced Placeholders
$_['placeholder_api_key']        = 'أدخل مفتاح API...';
$_['placeholder_webhook_url']    = 'أدخل رابط Webhook...';
$_['placeholder_insurance_value'] = 'أدخل قيمة التأمين...';
$_['placeholder_customs_value']  = 'أدخل القيمة الجمركية...';

// Advanced Confirmation Messages
$_['confirm_api_test']           = 'هل تريد اختبار الاتصال بـ API؟';
$_['confirm_bulk_print']         = 'هل تريد طباعة جميع الملصقات المحددة؟';
$_['confirm_auto_update']        = 'هل تريد تفعيل التحديث التلقائي؟';
$_['confirm_return_authorization'] = 'هل تريد إصدار تصريح إرجاع؟';

// Advanced Info Messages
$_['info_api_testing']           = 'جاري اختبار الاتصال بـ API...';
$_['info_bulk_processing']       = 'جاري معالجة العمليات المجمعة...';
$_['info_route_calculating']     = 'جاري حساب أفضل مسار...';
$_['info_cost_calculating']      = 'جاري حساب تكلفة الشحن...';

// Advanced Warning Messages
$_['warning_api_limit']          = 'تحذير: اقتراب من حد استخدام API';
$_['warning_high_value_shipment'] = 'تحذير: شحنة عالية القيمة - تأكد من التأمين';
$_['warning_international_restrictions'] = 'تحذير: قد توجد قيود على الشحن الدولي';
$_['warning_hazmat_detected']    = 'تحذير: تم اكتشاف مواد خطرة';

// Format Strings
$_['format_weight']              = '%s كجم';
$_['format_dimensions']          = '%s سم';
$_['format_currency']            = '%s جنيه';
$_['format_percentage']          = '%s%%';
$_['format_days']                = '%s يوم';
$_['format_hours']               = '%s ساعة';
?>
