<?php
// Heading
$_['heading_title']          = 'تخطيط المشتريات';

// Text
$_['text_success']           = 'تم: تم تحديث تخطيط المشتريات بنجاح!';
$_['text_list']              = 'قائمة خطط الشراء';
$_['text_add']               = 'إضافة خطة';
$_['text_edit']              = 'تعديل خطة';
$_['text_view_plan']         = 'عرض الخطة';
$_['text_confirm']           = 'هل أنت متأكد؟';
$_['text_loading']           = 'جاري التحميل...';
$_['text_no_results']        = 'لا توجد نتائج!';
$_['text_planning_report']   = 'تقرير تخطيط المشتريات';

// Column
$_['column_plan_name']       = 'اسم الخطة';
$_['column_plan_period']     = 'فترة الخطة';
$_['column_start_date']      = 'تاريخ البداية';
$_['column_end_date']        = 'تاريخ النهاية';
$_['column_total_budget']    = 'إجمالي الميزانية';
$_['column_used_budget']     = 'الميزانية المستخدمة';
$_['column_remaining_budget'] = 'الميزانية المتبقية';
$_['column_status']          = 'الحالة';
$_['column_progress']        = 'التقدم';
$_['column_created_by']      = 'أنشأ بواسطة';
$_['column_action']          = 'إجراء';

// Entry
$_['entry_plan_name']        = 'اسم الخطة';
$_['entry_plan_description'] = 'وصف الخطة';
$_['entry_plan_period']      = 'فترة الخطة';
$_['entry_start_date']       = 'تاريخ البداية';
$_['entry_end_date']         = 'تاريخ النهاية';
$_['entry_total_budget']     = 'إجمالي الميزانية';
$_['entry_status']           = 'الحالة';
$_['entry_notes']            = 'ملاحظات';
$_['entry_product']          = 'المنتج';
$_['entry_category']         = 'الفئة';
$_['entry_quantity']         = 'الكمية';
$_['entry_estimated_price']  = 'السعر المقدر';
$_['entry_priority']         = 'الأولوية';
$_['entry_item_notes']       = 'ملاحظات العنصر';

// Button
$_['button_filter']          = 'فلترة';
$_['button_add_item']        = 'إضافة عنصر';
$_['button_remove_item']     = 'حذف العنصر';
$_['button_view_progress']   = 'عرض التقدم';
$_['button_export']          = 'تصدير';
$_['button_print']           = 'طباعة';
$_['button_view_report']     = 'عرض التقرير';

// Tab
$_['tab_general']            = 'عام';
$_['tab_items']              = 'العناصر';
$_['tab_budget']             = 'الميزانية';
$_['tab_progress']           = 'التقدم';
$_['tab_analytics']          = 'التحليلات';

// Status
$_['text_status_draft']      = 'مسودة';
$_['text_status_active']     = 'نشط';
$_['text_status_completed']  = 'مكتمل';
$_['text_status_cancelled']  = 'ملغي';

// Period
$_['text_period_monthly']    = 'شهري';
$_['text_period_quarterly']  = 'ربع سنوي';
$_['text_period_yearly']     = 'سنوي';
$_['text_period_custom']     = 'مخصص';

// Priority
$_['text_priority_high']     = 'عالية';
$_['text_priority_medium']   = 'متوسطة';
$_['text_priority_low']      = 'منخفضة';

// Error
$_['error_permission']       = 'تحذير: ليس لديك صلاحية للوصول إلى تخطيط المشتريات!';
$_['error_plan_name']        = 'اسم الخطة يجب أن يكون بين 3 و 255 حرف!';
$_['error_start_date']       = 'تاريخ البداية مطلوب!';
$_['error_end_date']         = 'تاريخ النهاية مطلوب!';
$_['error_end_date_before_start'] = 'تاريخ النهاية يجب أن يكون بعد تاريخ البداية!';
$_['error_total_budget']     = 'إجمالي الميزانية يجب أن يكون أكبر من صفر!';

// Help
$_['help_plan_name']         = 'أدخل اسماً وصفياً للخطة';
$_['help_plan_period']       = 'اختر فترة الخطة (شهري، ربع سنوي، سنوي، أو مخصص)';
$_['help_total_budget']      = 'أدخل إجمالي الميزانية المخصصة لهذه الخطة';
$_['help_plan_items']        = 'أضف المنتجات والكميات المطلوب شراؤها في هذه الخطة';
$_['help_priority']          = 'حدد أولوية كل عنصر (عالية، متوسطة، منخفضة)';

// Success
$_['success_plan_added']     = 'تم إضافة الخطة بنجاح!';
$_['success_plan_updated']   = 'تم تحديث الخطة بنجاح!';
$_['success_plan_deleted']   = 'تم حذف الخطة بنجاح!';
$_['success_export']         = 'تم تصدير البيانات بنجاح!';

// Info
$_['info_planning_help']     = 'استخدم هذه الشاشة لإنشاء وإدارة خطط الشراء';
$_['info_budget_tracking']   = 'يتم تتبع استخدام الميزانية تلقائياً من أوامر الشراء';
$_['info_progress_monitoring'] = 'يمكن مراقبة تقدم تنفيذ الخطة من خلال التقارير';

// Statistics
$_['text_total_plans']       = 'إجمالي الخطط';
$_['text_active_plans']      = 'خطط نشطة';
$_['text_completed_plans']   = 'خطط مكتملة';
$_['text_total_budget']      = 'إجمالي الميزانية';
$_['text_used_budget']       = 'الميزانية المستخدمة';
$_['text_remaining_budget']  = 'الميزانية المتبقية';

// Dashboard Widget
$_['widget_title']           = 'تخطيط المشتريات';
$_['widget_active_plans']    = 'خطط نشطة';
$_['widget_budget_usage']    = 'استخدام الميزانية';
$_['widget_overdue_plans']   = 'خطط متأخرة';
$_['widget_view_all']        = 'عرض الكل';

// Report
$_['report_planning_summary'] = 'ملخص تخطيط المشتريات';
$_['report_budget_analysis'] = 'تحليل الميزانية';
$_['report_performance_metrics'] = 'مقاييس الأداء';
$_['report_by_period']       = 'تقرير حسب الفترة';
$_['report_by_category']     = 'تقرير حسب الفئة';

// Analytics
$_['analytics_by_category']  = 'تحليل حسب الفئة';
$_['analytics_by_priority']  = 'تحليل حسب الأولوية';
$_['analytics_top_products'] = 'أعلى المنتجات';
$_['analytics_budget_utilization'] = 'استخدام الميزانية';

// Progress
$_['progress_planned_items'] = 'العناصر المخططة';
$_['progress_purchased_items'] = 'العناصر المشتراة';
$_['progress_items_percentage'] = 'نسبة العناصر';
$_['progress_quantity_percentage'] = 'نسبة الكمية';
$_['progress_budget_percentage'] = 'نسبة الميزانية';

// Email Templates
$_['email_plan_created_subject'] = 'خطة شراء جديدة - %s';
$_['email_plan_created_body'] = 'تم إنشاء خطة شراء جديدة: %s للفترة من %s إلى %s';
$_['email_plan_completed_subject'] = 'اكتمال خطة الشراء - %s';
$_['email_plan_completed_body'] = 'تم اكتمال خطة الشراء: %s بنجاح';

// Export
$_['export_filename']        = 'خطط_الشراء_%s.csv';
$_['export_headers']         = array(
    'اسم الخطة',
    'فترة الخطة',
    'تاريخ البداية',
    'تاريخ النهاية',
    'إجمالي الميزانية',
    'الميزانية المستخدمة',
    'الحالة',
    'التقدم'
);

// Notifications
$_['notification_plan_created'] = 'تم إنشاء خطة شراء جديدة: %s';
$_['notification_plan_activated'] = 'تم تفعيل خطة الشراء: %s';
$_['notification_plan_completed'] = 'تم اكتمال خطة الشراء: %s';
$_['notification_budget_exceeded'] = 'تحذير: تم تجاوز ميزانية الخطة %s';

// Validation
$_['validation_plan_name_required'] = 'اسم الخطة مطلوب!';
$_['validation_dates_required'] = 'تواريخ البداية والنهاية مطلوبة!';
$_['validation_budget_positive'] = 'الميزانية يجب أن تكون أكبر من صفر!';
$_['validation_items_required'] = 'يجب إضافة عنصر واحد على الأقل!';

// Filters
$_['filter_all_periods']     = 'جميع الفترات';
$_['filter_all_statuses']    = 'جميع الحالات';
$_['filter_active_only']     = 'النشطة فقط';
$_['filter_completed_only']  = 'المكتملة فقط';

// Actions
$_['action_view_details']    = 'عرض التفاصيل';
$_['action_activate']        = 'تفعيل';
$_['action_complete']        = 'إكمال';
$_['action_cancel']          = 'إلغاء';
$_['action_duplicate']       = 'نسخ';

// Bulk Actions
$_['bulk_activate']          = 'تفعيل المحدد';
$_['bulk_complete']          = 'إكمال المحدد';
$_['bulk_cancel']            = 'إلغاء المحدد';
$_['bulk_export']            = 'تصدير المحدد';
$_['bulk_action_success']    = 'تم تنفيذ الإجراء على %d خطة بنجاح!';

// Search
$_['search_placeholder']     = 'البحث في خطط الشراء...';
$_['search_results']         = 'نتائج البحث عن: %s';
$_['search_no_results']      = 'لا توجد نتائج للبحث عن: %s';

// Modal
$_['modal_add_item']         = 'إضافة عنصر جديد';
$_['modal_edit_item']        = 'تعديل العنصر';
$_['modal_confirm_delete']   = 'تأكيد الحذف';
$_['modal_activate_plan']    = 'تفعيل الخطة';
$_['modal_complete_plan']    = 'إكمال الخطة';

// Workflow
$_['workflow_draft']         = 'مسودة';
$_['workflow_active']        = 'نشط';
$_['workflow_completed']     = 'مكتمل';
$_['workflow_cancelled']     = 'ملغي';

// API
$_['api_success']            = 'تم تنفيذ العملية بنجاح';
$_['api_error']              = 'خطأ في تنفيذ العملية';
$_['api_invalid_data']       = 'بيانات غير صحيحة';
$_['api_not_found']          = 'الخطة غير موجودة';
$_['api_permission_denied']  = 'ليس لديك صلاحية لهذه العملية';

// Audit Trail
$_['audit_plan_created']     = 'تم إنشاء خطة جديدة';
$_['audit_plan_updated']     = 'تم تحديث الخطة';
$_['audit_plan_activated']   = 'تم تفعيل الخطة';
$_['audit_plan_completed']   = 'تم إكمال الخطة';
$_['audit_plan_cancelled']   = 'تم إلغاء الخطة';
$_['audit_plan_deleted']     = 'تم حذف الخطة';

// Integration
$_['integration_purchase_orders'] = 'ربط مع أوامر الشراء';
$_['integration_inventory'] = 'ربط مع المخزون';
$_['integration_accounting'] = 'ربط مع المحاسبة';

// Approval Workflow
$_['approval_required']      = 'يتطلب موافقة';
$_['approval_pending']       = 'في انتظار الموافقة';
$_['approval_approved']      = 'معتمد';
$_['approval_rejected']      = 'مرفوض';

// Budget Management
$_['budget_allocated']       = 'مخصص';
$_['budget_committed']       = 'ملتزم';
$_['budget_spent']           = 'منفق';
$_['budget_available']       = 'متاح';

// Performance Indicators
$_['kpi_completion_rate']    = 'معدل الإنجاز';
$_['kpi_budget_utilization'] = 'استخدام الميزانية';
$_['kpi_on_time_delivery']   = 'التسليم في الوقت المحدد';
$_['kpi_cost_variance']      = 'انحراف التكلفة';

// Forecasting
$_['forecast_demand']        = 'توقع الطلب';
$_['forecast_budget']        = 'توقع الميزانية';
$_['forecast_timeline']      = 'توقع الجدول الزمني';

// Risk Management
$_['risk_budget_overrun']    = 'تجاوز الميزانية';
$_['risk_schedule_delay']    = 'تأخير الجدول';
$_['risk_supplier_issues']   = 'مشاكل الموردين';
$_['risk_quality_concerns']  = 'مخاوف الجودة';
