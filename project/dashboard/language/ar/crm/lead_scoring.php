<?php
/**
 * ملف اللغة العربية - تقييم العملاء المحتملين
 * Arabic Language File - Lead Scoring
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

// العناوين الرئيسية
$_['heading_title']         = 'تقييم العملاء المحتملين';
$_['heading_title_view']    = 'عرض تقييم العميل المحتمل';
$_['heading_title_rules']   = 'قواعد التقييم';
$_['heading_title_analytics'] = 'تحليلات التقييم';

// النصوص العامة
$_['text_list']             = 'قائمة العملاء المحتملين';
$_['text_view']             = 'عرض التفاصيل';
$_['text_success_recalculate'] = 'تم إعادة حساب النقاط بنجاح!';
$_['text_success_convert']  = 'تم تحويل العميل المحتمل إلى عميل بنجاح!';
$_['text_success_rules']    = 'تم تحديث قواعد التقييم بنجاح!';
$_['text_home']             = 'الرئيسية';

// الأولويات
$_['text_priority_hot']     = 'ساخن';
$_['text_priority_warm']    = 'دافئ';
$_['text_priority_cold']    = 'بارد';
$_['text_priority_unknown'] = 'غير محدد';

// الحالات
$_['text_status_new']       = 'جديد';
$_['text_status_contacted'] = 'تم التواصل';
$_['text_status_qualified'] = 'مؤهل';
$_['text_status_proposal']  = 'عرض أسعار';
$_['text_status_negotiation'] = 'تفاوض';
$_['text_status_converted'] = 'محول';
$_['text_status_lost']      = 'مفقود';
$_['text_status_unknown']   = 'غير محدد';

// المصادر
$_['text_source_website']   = 'الموقع الإلكتروني';
$_['text_source_social']    = 'وسائل التواصل الاجتماعي';
$_['text_source_email']     = 'البريد الإلكتروني';
$_['text_source_phone']     = 'الهاتف';
$_['text_source_referral']  = 'إحالة';
$_['text_source_ad']        = 'إعلان';
$_['text_source_event']     = 'فعالية';
$_['text_source_other']     = 'أخرى';

// نطاقات النقاط
$_['text_score_hot']        = 'ساخن (80-100)';
$_['text_score_warm']       = 'دافئ (60-79)';
$_['text_score_medium']     = 'متوسط (40-59)';
$_['text_score_cold']       = 'بارد (0-39)';

// فئات القواعد
$_['text_category_demographic'] = 'ديموغرافية';
$_['text_category_behavioral'] = 'سلوكية';
$_['text_category_engagement'] = 'تفاعل';
$_['text_category_company']   = 'الشركة';
$_['text_category_source']    = 'المصدر';

// أعمدة الجدول
$_['column_lead_id']        = 'رقم العميل المحتمل';
$_['column_customer_name']  = 'اسم العميل';
$_['column_email']          = 'البريد الإلكتروني';
$_['column_phone']          = 'الهاتف';
$_['column_company']        = 'الشركة';
$_['column_source']         = 'المصدر';
$_['column_status']         = 'الحالة';
$_['column_total_score']    = 'النقاط الإجمالية';
$_['column_priority']       = 'الأولوية';
$_['column_conversion_probability'] = 'احتمالية التحويل';
$_['column_estimated_value'] = 'القيمة المتوقعة';
$_['column_last_activity']  = 'آخر نشاط';
$_['column_assigned_to']    = 'مسند إلى';
$_['column_date_created']   = 'تاريخ الإنشاء';
$_['column_action']         = 'الإجراءات';

// حقول النموذج
$_['entry_customer_name']   = 'اسم العميل';
$_['entry_email']           = 'البريد الإلكتروني';
$_['entry_phone']           = 'الهاتف';
$_['entry_company']         = 'الشركة';
$_['entry_source']          = 'المصدر';
$_['entry_status']          = 'الحالة';
$_['entry_assigned_to']     = 'مسند إلى';
$_['entry_notes']           = 'ملاحظات';

// مرشحات البحث
$_['entry_filter_name']     = 'اسم العميل';
$_['entry_filter_score_range'] = 'نطاق النقاط';
$_['entry_filter_priority'] = 'الأولوية';
$_['entry_filter_status']   = 'الحالة';
$_['entry_filter_source']   = 'المصدر';
$_['entry_filter_assigned_to'] = 'مسند إلى';

// الأزرار
$_['button_add']            = 'إضافة';
$_['button_edit']           = 'تعديل';
$_['button_delete']         = 'حذف';
$_['button_view']           = 'عرض';
$_['button_save']           = 'حفظ';
$_['button_cancel']         = 'إلغاء';
$_['button_filter']         = 'فلترة';
$_['button_clear']          = 'مسح';
$_['button_export']         = 'تصدير';
$_['button_recalculate']    = 'إعادة حساب النقاط';
$_['button_convert']        = 'تحويل إلى عميل';
$_['button_activities']     = 'الأنشطة';
$_['button_plan_details']   = 'تفاصيل الخطة';
$_['button_bulk_score']     = 'تقييم مجمع';
$_['button_scoring_rules']  = 'قواعد التقييم';
$_['button_analytics']      = 'التحليلات';

// رسائل الخطأ
$_['error_permission']      = 'تحذير: ليس لديك صلاحية للوصول إلى تقييم العملاء المحتملين!';
$_['error_not_found']       = 'العميل المحتمل المطلوب غير موجود!';
$_['error_recalculate']     = 'حدث خطأ أثناء إعادة حساب النقاط!';
$_['error_convert']         = 'حدث خطأ أثناء تحويل العميل المحتمل!';

// تفاصيل النقاط
$_['text_score_breakdown']  = 'تفصيل النقاط';
$_['text_demographic_score'] = 'النقاط الديموغرافية';
$_['text_behavioral_score'] = 'النقاط السلوكية';
$_['text_engagement_score'] = 'نقاط التفاعل';
$_['text_company_score']    = 'نقاط الشركة';
$_['text_source_score']     = 'نقاط المصدر';
$_['text_total_score']      = 'النقاط الإجمالية';

// الإحصائيات
$_['text_total_leads']      = 'إجمالي العملاء المحتملين';
$_['text_hot_leads']        = 'العملاء الساخنون';
$_['text_avg_score']        = 'متوسط النقاط';
$_['text_conversion_rate']  = 'معدل التحويل';
$_['text_monthly_conversions'] = 'التحويلات الشهرية';
$_['text_pipeline_value']   = 'قيمة خط الأنابيب';

// التحليلات
$_['text_score_distribution'] = 'توزيع النقاط';
$_['text_conversion_rates']  = 'معدلات التحويل';
$_['text_source_performance'] = 'أداء المصادر';
$_['text_monthly_trends']    = 'الاتجاهات الشهرية';
$_['text_top_performers']    = 'الأفضل أداءً';
$_['text_prediction_accuracy'] = 'دقة التوقعات';

// الأنشطة
$_['text_activities']       = 'الأنشطة';
$_['text_activity_type']    = 'نوع النشاط';
$_['text_activity_date']    = 'تاريخ النشاط';
$_['text_activity_description'] = 'وصف النشاط';
$_['text_email_open']       = 'فتح إيميل';
$_['text_email_click']      = 'نقر على رابط';
$_['text_website_visit']    = 'زيارة موقع';
$_['text_form_submit']      = 'إرسال نموذج';
$_['text_phone_call']       = 'مكالمة هاتفية';
$_['text_meeting']          = 'اجتماع';

// التوقعات
$_['text_predictions']      = 'التوقعات';
$_['text_conversion_probability'] = 'احتمالية التحويل';
$_['text_estimated_value']  = 'القيمة المتوقعة';
$_['text_expected_close_date'] = 'تاريخ الإغلاق المتوقع';
$_['text_recommended_actions'] = 'الإجراءات الموصى بها';

// قواعد التقييم
$_['text_scoring_rules']    = 'قواعد التقييم';
$_['text_rule_name']        = 'اسم القاعدة';
$_['text_rule_value']       = 'قيمة القاعدة';
$_['text_rule_category']    = 'فئة القاعدة';
$_['text_rule_description'] = 'وصف القاعدة';

// نصائح المساعدة
$_['help_score']            = 'النقاط محسوبة بناءً على عدة عوامل مثل السلوك والتفاعل ومعلومات الشركة';
$_['help_priority']         = 'الأولوية محددة تلقائياً بناءً على النقاط الإجمالية';
$_['help_conversion_probability'] = 'احتمالية التحويل محسوبة باستخدام خوارزميات التعلم الآلي';
$_['help_estimated_value']  = 'القيمة المتوقعة محسوبة بناءً على النقاط وحجم الشركة والميزانية';

// رسائل التأكيد
$_['text_confirm_recalculate'] = 'هل تريد إعادة حساب النقاط لهذا العميل المحتمل؟';
$_['text_confirm_convert']  = 'هل تريد تحويل هذا العميل المحتمل إلى عميل؟';
$_['text_confirm_delete']   = 'هل أنت متأكد من حذف العملاء المحتملين المحددين؟';

// تصدير واستيراد
$_['text_export_excel']     = 'تصدير إلى Excel';
$_['text_export_pdf']       = 'تصدير إلى PDF';
$_['text_print_list']       = 'طباعة القائمة';

// إشعارات النجاح
$_['text_lead_scored']      = 'تم تقييم العميل المحتمل بنجاح!';
$_['text_bulk_scored']      = 'تم تقييم العملاء المحتملين المحددين بنجاح!';
$_['text_rules_updated']    = 'تم تحديث قواعد التقييم بنجاح!';

// تحذيرات
$_['warning_low_score']     = 'تحذير: هذا العميل المحتمل لديه نقاط منخفضة!';
$_['warning_no_activity']   = 'تحذير: لا يوجد نشاط حديث لهذا العميل المحتمل!';
$_['warning_expired_lead']  = 'تحذير: هذا العميل المحتمل قديم ويحتاج متابعة!';

// معلومات إضافية
$_['text_lead_info']        = 'معلومات العميل المحتمل';
$_['text_scoring_info']     = 'معلومات التقييم';
$_['text_activity_history'] = 'تاريخ الأنشطة';
$_['text_prediction_info']  = 'معلومات التوقعات';

// تقارير
$_['text_scoring_report']   = 'تقرير التقييم';
$_['text_performance_report'] = 'تقرير الأداء';
$_['text_conversion_report'] = 'تقرير التحويل';

// رسائل النظام
$_['text_no_leads']         = 'لا توجد عملاء محتملون';
$_['text_loading']          = 'جاري التحميل...';
$_['text_processing']       = 'جاري المعالجة...';
$_['text_calculating']      = 'جاري الحساب...';
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
$_['text_seconds']          = 'ثواني';

// مستويات الثقة
$_['text_confidence_high']  = 'عالية';
$_['text_confidence_medium'] = 'متوسطة';
$_['text_confidence_low']   = 'منخفضة';

// حالات الصحة
$_['text_health_excellent'] = 'ممتازة';
$_['text_health_good']      = 'جيدة';
$_['text_health_fair']      = 'مقبولة';
$_['text_health_poor']      = 'ضعيفة';

// أنواع التقييم
$_['text_auto_scoring']     = 'تقييم تلقائي';
$_['text_manual_scoring']   = 'تقييم يدوي';
$_['text_hybrid_scoring']   = 'تقييم مختلط';

// خيارات التصدير
$_['text_export_all']       = 'تصدير الكل';
$_['text_export_filtered']  = 'تصدير المفلتر';
$_['text_export_selected']  = 'تصدير المحدد';

// إعدادات التقييم
$_['text_scoring_settings'] = 'إعدادات التقييم';
$_['text_auto_recalculate'] = 'إعادة حساب تلقائية';
$_['text_scoring_frequency'] = 'تكرار التقييم';
$_['text_notification_threshold'] = 'حد الإشعارات';

// التكامل
$_['text_crm_integration']  = 'تكامل CRM';
$_['text_email_integration'] = 'تكامل البريد الإلكتروني';
$_['text_analytics_integration'] = 'تكامل التحليلات';

// الأمان
$_['text_access_level']     = 'مستوى الوصول';
$_['text_view_only']        = 'عرض فقط';
$_['text_edit_allowed']     = 'تعديل مسموح';
$_['text_full_access']      = 'وصول كامل';
?>
