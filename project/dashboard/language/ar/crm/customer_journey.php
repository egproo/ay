<?php
/**
 * ملف اللغة العربية - رحلة العميل
 * Arabic Language File - Customer Journey
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

// العناوين الرئيسية
$_['heading_title']         = 'رحلة العميل';
$_['heading_title_view']    = 'عرض رحلة العميل';
$_['heading_title_map']     = 'خريطة رحلة العميل';
$_['heading_title_timeline'] = 'الجدول الزمني لرحلة العميل';
$_['heading_title_analytics'] = 'تحليلات رحلة العميل';

// النصوص العامة
$_['text_list']             = 'قائمة رحلات العملاء';
$_['text_view']             = 'عرض التفاصيل';
$_['text_create']           = 'إنشاء رحلة جديدة';
$_['text_success_create']   = 'تم إنشاء رحلة العميل بنجاح!';
$_['text_success_edit']     = 'تم تعديل رحلة العميل بنجاح!';
$_['text_success_delete']   = 'تم حذف رحلات العملاء المحددة بنجاح!';
$_['text_home']             = 'الرئيسية';

// مراحل الرحلة
$_['text_stage_awareness']  = 'الوعي';
$_['text_stage_interest']   = 'الاهتمام';
$_['text_stage_consideration'] = 'الاعتبار';
$_['text_stage_purchase']   = 'الشراء';
$_['text_stage_retention']  = 'الاحتفاظ';
$_['text_stage_advocacy']   = 'الدعوة';

// نقاط اللمس
$_['text_touchpoint_website'] = 'الموقع الإلكتروني';
$_['text_touchpoint_email'] = 'البريد الإلكتروني';
$_['text_touchpoint_phone'] = 'الهاتف';
$_['text_touchpoint_social'] = 'وسائل التواصل الاجتماعي';
$_['text_touchpoint_ad']    = 'الإعلانات';
$_['text_touchpoint_store'] = 'المتجر الفعلي';
$_['text_touchpoint_event'] = 'الفعاليات';
$_['text_touchpoint_referral'] = 'الإحالات';

// مستويات صحة الرحلة
$_['text_health_excellent'] = 'ممتازة';
$_['text_health_good']      = 'جيدة';
$_['text_health_fair']      = 'مقبولة';
$_['text_health_poor']      = 'ضعيفة';

// أعمدة الجدول
$_['column_journey_id']     = 'رقم الرحلة';
$_['column_customer_name']  = 'اسم العميل';
$_['column_email']          = 'البريد الإلكتروني';
$_['column_phone']          = 'الهاتف';
$_['column_first_touchpoint'] = 'نقطة اللمس الأولى';
$_['column_current_stage']  = 'المرحلة الحالية';
$_['column_total_touchpoints'] = 'إجمالي نقاط اللمس';
$_['column_journey_duration'] = 'مدة الرحلة';
$_['column_journey_health'] = 'صحة الرحلة';
$_['column_conversion_probability'] = 'احتمالية التحويل';
$_['column_total_value']    = 'إجمالي القيمة';
$_['column_last_activity']  = 'آخر نشاط';
$_['column_assigned_to']    = 'مسند إلى';
$_['column_journey_start']  = 'بداية الرحلة';
$_['column_action']         = 'الإجراءات';

// حقول النموذج
$_['entry_customer']        = 'العميل';
$_['entry_first_touchpoint'] = 'نقطة اللمس الأولى';
$_['entry_current_stage']   = 'المرحلة الحالية';
$_['entry_journey_health']  = 'صحة الرحلة';
$_['entry_assigned_to']     = 'مسند إلى';
$_['entry_notes']           = 'ملاحظات';
$_['entry_tags']            = 'العلامات';

// مرشحات البحث
$_['entry_filter_customer'] = 'العميل';
$_['entry_filter_stage']    = 'المرحلة';
$_['entry_filter_health']   = 'صحة الرحلة';
$_['entry_filter_touchpoint'] = 'نقطة اللمس';
$_['entry_filter_assigned_to'] = 'مسند إلى';
$_['entry_filter_date_from'] = 'التاريخ من';
$_['entry_filter_date_to']  = 'التاريخ إلى';

// الأزرار
$_['button_create']         = 'إنشاء رحلة';
$_['button_edit']           = 'تعديل';
$_['button_delete']         = 'حذف';
$_['button_view']           = 'عرض';
$_['button_save']           = 'حفظ';
$_['button_cancel']         = 'إلغاء';
$_['button_filter']         = 'فلترة';
$_['button_clear']          = 'مسح';
$_['button_export']         = 'تصدير';
$_['button_map']            = 'خريطة الرحلة';
$_['button_timeline']       = 'الجدول الزمني';
$_['button_optimize']       = 'تحسين الرحلة';
$_['button_analytics']      = 'التحليلات';
$_['button_templates']      = 'القوالب';
$_['button_touchpoints']    = 'نقاط اللمس';
$_['button_add_touchpoint'] = 'إضافة نقطة لمس';
$_['button_update_stage']   = 'تحديث المرحلة';

// رسائل الخطأ
$_['error_permission']      = 'تحذير: ليس لديك صلاحية للوصول إلى رحلة العميل!';
$_['error_not_found']       = 'رحلة العميل المطلوبة غير موجودة!';
$_['error_customer']        = 'يجب اختيار العميل!';
$_['error_touchpoint']      = 'يجب تحديد نقطة اللمس!';
$_['error_stage']           = 'يجب تحديد المرحلة!';
$_['error_duplicate_journey'] = 'يوجد رحلة نشطة بالفعل لهذا العميل!';

// الإحصائيات
$_['text_total_journeys']   = 'إجمالي الرحلات';
$_['text_active_journeys']  = 'الرحلات النشطة';
$_['text_avg_duration']     = 'متوسط المدة';
$_['text_conversion_rate']  = 'معدل التحويل';
$_['text_top_touchpoint']   = 'أفضل نقطة لمس';
$_['text_health_distribution'] = 'توزيع الصحة';

// تفاصيل الرحلة
$_['text_journey_details']  = 'تفاصيل الرحلة';
$_['text_customer_info']    = 'معلومات العميل';
$_['text_journey_progress'] = 'تقدم الرحلة';
$_['text_touchpoint_history'] = 'تاريخ نقاط اللمس';
$_['text_stage_progression'] = 'تطور المراحل';

// خريطة الرحلة
$_['text_journey_map']      = 'خريطة الرحلة';
$_['text_current_position'] = 'الموقع الحالي';
$_['text_completed_stages'] = 'المراحل المكتملة';
$_['text_upcoming_stages']  = 'المراحل القادمة';
$_['text_alternative_paths'] = 'المسارات البديلة';
$_['text_touchpoint_details'] = 'تفاصيل نقاط اللمس';

// الجدول الزمني
$_['text_timeline']         = 'الجدول الزمني';
$_['text_milestones']       = 'الأحداث المهمة';
$_['text_activity_log']     = 'سجل الأنشطة';
$_['text_interaction_history'] = 'تاريخ التفاعلات';

// التحليلات
$_['text_stage_conversion_rates'] = 'معدلات تحويل المراحل';
$_['text_touchpoint_effectiveness'] = 'فعالية نقاط اللمس';
$_['text_journey_duration_analysis'] = 'تحليل مدة الرحلة';
$_['text_drop_off_points']  = 'نقاط التسرب';
$_['text_customer_segments'] = 'شرائح العملاء';
$_['text_channel_performance'] = 'أداء القنوات';

// مؤشرات الأداء
$_['text_avg_journey_duration'] = 'متوسط مدة الرحلة';
$_['text_overall_conversion_rate'] = 'معدل التحويل الإجمالي';
$_['text_customer_lifetime_value'] = 'قيمة العميل مدى الحياة';
$_['text_journey_completion_rate'] = 'معدل إكمال الرحلة';

// أنواع الأنشطة
$_['text_activity_email_open'] = 'فتح إيميل';
$_['text_activity_email_click'] = 'نقر على رابط';
$_['text_activity_website_visit'] = 'زيارة موقع';
$_['text_activity_form_submit'] = 'إرسال نموذج';
$_['text_activity_phone_call'] = 'مكالمة هاتفية';
$_['text_activity_meeting']   = 'اجتماع';
$_['text_activity_purchase']  = 'عملية شراء';
$_['text_activity_support']   = 'طلب دعم';

// حالات نقاط اللمس
$_['text_touchpoint_active'] = 'نشطة';
$_['text_touchpoint_completed'] = 'مكتملة';
$_['text_touchpoint_skipped'] = 'متجاوزة';
$_['text_touchpoint_failed'] = 'فاشلة';

// نصائح المساعدة
$_['help_journey_health']   = 'صحة الرحلة تعتمد على التفاعل والنشاط الحديث';
$_['help_conversion_probability'] = 'احتمالية التحويل محسوبة بناءً على المرحلة والتفاعل';
$_['help_touchpoints']      = 'نقاط اللمس هي جميع التفاعلات بين العميل والشركة';
$_['help_stages']           = 'المراحل تمثل تطور العميل في رحلة الشراء';

// رسائل التأكيد
$_['text_confirm_delete']   = 'هل أنت متأكد من حذف رحلات العملاء المحددة؟';
$_['text_confirm_stage_update'] = 'هل تريد تحديث مرحلة هذه الرحلة؟';
$_['text_confirm_optimize'] = 'هل تريد تحسين هذه الرحلة؟';

// تصدير واستيراد
$_['text_export_excel']     = 'تصدير إلى Excel';
$_['text_export_pdf']       = 'تصدير إلى PDF';
$_['text_export_journey_map'] = 'تصدير خريطة الرحلة';
$_['text_print_timeline']   = 'طباعة الجدول الزمني';

// إشعارات النجاح
$_['text_journey_created']  = 'تم إنشاء رحلة العميل بنجاح!';
$_['text_stage_updated']    = 'تم تحديث مرحلة الرحلة بنجاح!';
$_['text_touchpoint_added'] = 'تم إضافة نقطة اللمس بنجاح!';
$_['text_journey_optimized'] = 'تم تحسين الرحلة بنجاح!';

// تحذيرات
$_['warning_inactive_journey'] = 'تحذير: هذه الرحلة غير نشطة!';
$_['warning_long_duration'] = 'تحذير: مدة هذه الرحلة طويلة جداً!';
$_['warning_low_engagement'] = 'تحذير: مستوى التفاعل منخفض!';
$_['warning_stuck_stage']   = 'تحذير: العميل عالق في هذه المرحلة!';

// معلومات إضافية
$_['text_journey_statistics'] = 'إحصائيات الرحلة';
$_['text_engagement_score']  = 'نقاط التفاعل';
$_['text_stage_completion_rate'] = 'معدل إكمال المراحل';
$_['text_avg_time_between_touchpoints'] = 'متوسط الوقت بين نقاط اللمس';
$_['text_total_interactions'] = 'إجمالي التفاعلات';

// قوالب الرحلة
$_['text_journey_templates'] = 'قوالب الرحلة';
$_['text_template_b2b']     = 'قالب B2B';
$_['text_template_b2c']     = 'قالب B2C';
$_['text_template_ecommerce'] = 'قالب التجارة الإلكترونية';
$_['text_template_saas']    = 'قالب SaaS';
$_['text_template_custom']  = 'قالب مخصص';

// تقارير
$_['text_journey_report']   = 'تقرير الرحلة';
$_['text_conversion_report'] = 'تقرير التحويل';
$_['text_engagement_report'] = 'تقرير التفاعل';
$_['text_performance_report'] = 'تقرير الأداء';

// رسائل النظام
$_['text_no_journeys']      = 'لا توجد رحلات عملاء';
$_['text_loading']          = 'جاري التحميل...';
$_['text_processing']       = 'جاري المعالجة...';
$_['text_analyzing']        = 'جاري التحليل...';
$_['text_optimizing']       = 'جاري التحسين...';
$_['text_please_wait']      = 'يرجى الانتظار...';

// تنسيق التاريخ والوقت
$_['date_format_short']     = 'd/m/Y';
$_['date_format_long']      = 'd/m/Y H:i:s';
$_['time_format']           = 'H:i:s';

// العملة
$_['text_currency']         = 'ج.م';
$_['text_currency_position'] = 'right';

// التصفح
$_['text_pagination']       = 'عرض %d إلى %d من %d (%d صفحات)';
$_['text_first']            = 'الأولى';
$_['text_last']             = 'الأخيرة';
$_['text_next']             = 'التالي';
$_['text_prev']             = 'السابق';

// الترتيب
$_['text_sort_by']          = 'ترتيب حسب';
$_['text_sort_asc']         = 'تصاعدي';
$_['text_sort_desc']        = 'تنازلي';

// أخرى
$_['text_select']           = 'اختر...';
$_['text_none']             = 'لا يوجد';
$_['text_yes']              = 'نعم';
$_['text_no']               = 'لا';
$_['text_enabled']          = 'مفعل';
$_['text_disabled']         = 'معطل';
$_['text_default']          = 'افتراضي';
$_['text_all']              = 'الكل';

// الوحدات الزمنية
$_['text_days']             = 'أيام';
$_['text_hours']            = 'ساعات';
$_['text_minutes']          = 'دقائق';
$_['text_weeks']            = 'أسابيع';
$_['text_months']           = 'أشهر';

// مستويات التفاعل
$_['text_engagement_high']  = 'عالي';
$_['text_engagement_medium'] = 'متوسط';
$_['text_engagement_low']   = 'منخفض';

// حالات الرحلة
$_['text_journey_active']   = 'نشطة';
$_['text_journey_completed'] = 'مكتملة';
$_['text_journey_abandoned'] = 'متروكة';
$_['text_journey_paused']   = 'متوقفة';

// أنواع التحسين
$_['text_optimization_speed'] = 'تحسين السرعة';
$_['text_optimization_conversion'] = 'تحسين التحويل';
$_['text_optimization_engagement'] = 'تحسين التفاعل';
$_['text_optimization_retention'] = 'تحسين الاحتفاظ';

// خيارات التصدير
$_['text_export_all']       = 'تصدير الكل';
$_['text_export_filtered']  = 'تصدير المفلتر';
$_['text_export_selected']  = 'تصدير المحدد';
$_['text_export_summary']   = 'تصدير الملخص';
$_['text_export_detailed']  = 'تصدير مفصل';

// إعدادات الرحلة
$_['text_journey_settings'] = 'إعدادات الرحلة';
$_['text_auto_progression'] = 'التقدم التلقائي';
$_['text_notification_rules'] = 'قواعد الإشعارات';
$_['text_stage_timeouts']   = 'مهلة المراحل';

// التكامل
$_['text_crm_integration']  = 'تكامل CRM';
$_['text_email_integration'] = 'تكامل البريد الإلكتروني';
$_['text_analytics_integration'] = 'تكامل التحليلات';
$_['text_marketing_integration'] = 'تكامل التسويق';

// الأمان
$_['text_access_level']     = 'مستوى الوصول';
$_['text_view_only']        = 'عرض فقط';
$_['text_edit_allowed']     = 'تعديل مسموح';
$_['text_full_access']      = 'وصول كامل';
$_['text_team_access']      = 'وصول الفريق';
?>
