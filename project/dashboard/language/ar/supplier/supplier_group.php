<?php
// Heading
$_['heading_title']     = 'مجموعات الموردين';

// Text
$_['text_success']      = 'تم: تم تحديث مجموعات الموردين بنجاح!';
$_['text_list']         = 'قائمة مجموعات الموردين';
$_['text_add']          = 'إضافة مجموعة مورد';
$_['text_edit']         = 'تعديل مجموعة مورد';
$_['text_default']      = 'افتراضي';
$_['text_enabled']      = 'مفعل';
$_['text_disabled']     = 'معطل';
$_['text_yes']          = 'نعم';
$_['text_no']           = 'لا';
$_['text_select']       = 'اختر';
$_['text_confirm']      = 'هل أنت متأكد؟';
$_['text_loading']      = 'جاري التحميل...';
$_['text_no_results']   = 'لا توجد نتائج!';

// Column
$_['column_name']       = 'اسم المجموعة';
$_['column_description'] = 'الوصف';
$_['column_approval']   = 'يتطلب موافقة';
$_['column_sort_order'] = 'ترتيب الفرز';
$_['column_action']     = 'إجراء';
$_['column_supplier_count'] = 'عدد الموردين';

// Entry
$_['entry_name']        = 'اسم المجموعة';
$_['entry_description'] = 'الوصف';
$_['entry_approval']    = 'موافقة الموردين الجدد';
$_['entry_sort_order']  = 'ترتيب الفرز';

// Tab
$_['tab_general']       = 'عام';
$_['tab_data']          = 'البيانات';

// Button
$_['button_filter']     = 'فلترة';
$_['button_copy']       = 'نسخ';
$_['button_export']     = 'تصدير';
$_['button_import']     = 'استيراد';
$_['button_set_default'] = 'تعيين كافتراضي';
$_['button_move_suppliers'] = 'نقل الموردين';
$_['button_toggle_approval'] = 'تبديل الموافقة';

// Help
$_['help_approval']     = 'إذا كان مفعلاً، فسيحتاج الموردين الجدد في هذه المجموعة إلى موافقة المدير قبل تفعيل حساباتهم.';
$_['help_sort_order']   = 'ترتيب عرض المجموعات في القوائم المنسدلة.';
$_['help_name']         = 'اسم المجموعة كما سيظهر للموردين والمديرين.';
$_['help_description']  = 'وصف تفصيلي لمجموعة الموردين وخصائصها.';

// Error
$_['error_permission']  = 'تحذير: ليس لديك صلاحية للوصول إلى مجموعات الموردين!';
$_['error_name']        = 'اسم المجموعة يجب أن يكون بين 3 و 32 حرف!';
$_['error_default']     = 'تحذير: لا يمكن حذف مجموعة الموردين الافتراضية!';
$_['error_supplier']    = 'تحذير: لا يمكن حذف هذه المجموعة لأنها مرتبطة بـ %s مورد!';
$_['error_exists']      = 'تحذير: اسم المجموعة موجود بالفعل!';

// Success
$_['success_add']       = 'تم إضافة مجموعة المورد بنجاح!';
$_['success_edit']      = 'تم تحديث مجموعة المورد بنجاح!';
$_['success_delete']    = 'تم حذف مجموعة المورد بنجاح!';
$_['success_copy']      = 'تم نسخ مجموعة المورد بنجاح!';
$_['success_export']    = 'تم تصدير مجموعات الموردين بنجاح!';
$_['success_default']   = 'تم تعيين المجموعة الافتراضية بنجاح!';
$_['success_move']      = 'تم نقل الموردين بنجاح!';
$_['success_toggle']    = 'تم تبديل حالة الموافقة بنجاح!';

// Info
$_['info_total_groups'] = 'إجمالي المجموعات';
$_['info_approval_required'] = 'تتطلب موافقة';
$_['info_no_approval'] = 'لا تتطلب موافقة';
$_['info_default_group'] = 'المجموعة الافتراضية';
$_['info_group_help']   = 'استخدم مجموعات الموردين لتصنيف الموردين وتطبيق قواعد مختلفة عليهم';

// Statistics
$_['text_statistics']   = 'إحصائيات المجموعات';
$_['text_group_distribution'] = 'توزيع الموردين على المجموعات';
$_['text_approval_stats'] = 'إحصائيات الموافقة';

// Modal
$_['modal_copy_title']  = 'نسخ مجموعة مورد';
$_['modal_copy_text']   = 'هل تريد نسخ هذه المجموعة؟';
$_['modal_move_title']  = 'نقل الموردين';
$_['modal_move_text']   = 'اختر المجموعة الجديدة لنقل الموردين إليها:';
$_['modal_delete_title'] = 'حذف مجموعة مورد';
$_['modal_delete_text'] = 'هل أنت متأكد من حذف هذه المجموعة؟ سيتم نقل جميع الموردين إلى المجموعة الافتراضية.';

// Validation
$_['validation_name_required'] = 'اسم المجموعة مطلوب!';
$_['validation_name_length'] = 'اسم المجموعة يجب أن يكون بين 3 و 32 حرف!';
$_['validation_name_unique'] = 'اسم المجموعة موجود بالفعل!';
$_['validation_sort_order_numeric'] = 'ترتيب الفرز يجب أن يكون رقماً!';

// Notifications
$_['notification_new_supplier_title'] = 'مورد جديد في انتظار الموافقة';
$_['notification_new_supplier_message'] = 'مورد جديد %s انضم إلى مجموعة %s ويحتاج موافقة';
$_['notification_supplier_approved_title'] = 'تم الموافقة على مورد';
$_['notification_supplier_approved_message'] = 'تم الموافقة على المورد %s في مجموعة %s';

// Export
$_['export_filename']   = 'مجموعات_الموردين_%s.csv';
$_['export_headers']    = array(
    'معرف المجموعة',
    'اسم المجموعة',
    'الوصف',
    'يتطلب موافقة',
    'ترتيب الفرز',
    'عدد الموردين',
    'تاريخ الإنشاء'
);

// Import
$_['import_title']      = 'استيراد مجموعات الموردين';
$_['import_help']       = 'ارفع ملف CSV يحتوي على بيانات مجموعات الموردين';
$_['import_sample']     = 'تحميل ملف نموذجي';
$_['import_success']    = 'تم استيراد %d مجموعة مورد بنجاح!';
$_['import_error']      = 'خطأ في استيراد الملف: %s';

// Bulk Actions
$_['bulk_delete']       = 'حذف المحدد';
$_['bulk_enable_approval'] = 'تفعيل الموافقة للمحدد';
$_['bulk_disable_approval'] = 'إلغاء الموافقة للمحدد';
$_['bulk_export']       = 'تصدير المحدد';
$_['bulk_action_success'] = 'تم تنفيذ الإجراء على %d مجموعة بنجاح!';

// Search
$_['search_placeholder'] = 'البحث في مجموعات الموردين...';
$_['search_results']    = 'نتائج البحث عن: %s';
$_['search_no_results'] = 'لا توجد نتائج للبحث عن: %s';

// Filters
$_['filter_approval']   = 'فلترة حسب الموافقة';
$_['filter_all']        = 'الكل';
$_['filter_approval_required'] = 'تتطلب موافقة';
$_['filter_no_approval'] = 'لا تتطلب موافقة';
$_['filter_sort_order'] = 'فلترة حسب الترتيب';
$_['filter_name']       = 'فلترة حسب الاسم';

// Permissions
$_['permission_view']   = 'عرض مجموعات الموردين';
$_['permission_add']    = 'إضافة مجموعة مورد';
$_['permission_edit']   = 'تعديل مجموعة مورد';
$_['permission_delete'] = 'حذف مجموعة مورد';
$_['permission_export'] = 'تصدير مجموعات الموردين';
$_['permission_import'] = 'استيراد مجموعات الموردين';

// Activity Log
$_['activity_add']      = 'أضاف مجموعة مورد جديدة: %s';
$_['activity_edit']     = 'عدل مجموعة المورد: %s';
$_['activity_delete']   = 'حذف مجموعة المورد: %s';
$_['activity_copy']     = 'نسخ مجموعة المورد: %s';
$_['activity_set_default'] = 'عين مجموعة المورد الافتراضية: %s';
$_['activity_move_suppliers'] = 'نقل %d مورد من مجموعة %s إلى %s';

// Dashboard Widget
$_['widget_title']      = 'مجموعات الموردين';
$_['widget_total']      = 'إجمالي المجموعات';
$_['widget_active']     = 'مجموعات نشطة';
$_['widget_pending']    = 'تحتاج موافقة';
$_['widget_view_all']   = 'عرض الكل';

// Quick Actions
$_['quick_add']         = 'إضافة سريعة';
$_['quick_edit']        = 'تعديل سريع';
$_['quick_duplicate']   = 'تكرار';
$_['quick_activate']    = 'تفعيل';
$_['quick_deactivate']  = 'إلغاء تفعيل';

// Advanced Features
$_['advanced_settings'] = 'إعدادات متقدمة';
$_['auto_approval']     = 'موافقة تلقائية';
$_['approval_workflow'] = 'سير عمل الموافقة';
$_['group_permissions'] = 'صلاحيات المجموعة';
$_['custom_fields']     = 'حقول مخصصة';

// Integration
$_['integration_accounting'] = 'ربط مع المحاسبة';
$_['integration_crm']   = 'ربط مع إدارة العلاقات';
$_['integration_inventory'] = 'ربط مع المخزون';

// Reports
$_['report_group_performance'] = 'تقرير أداء المجموعات';
$_['report_supplier_distribution'] = 'تقرير توزيع الموردين';
$_['report_approval_stats'] = 'تقرير إحصائيات الموافقة';

// Email Templates
$_['email_new_supplier_subject'] = 'مورد جديد في انتظار الموافقة';
$_['email_new_supplier_body'] = 'مورد جديد %s انضم إلى مجموعة %s ويحتاج موافقة.';
$_['email_approval_subject'] = 'تم الموافقة على حسابك';
$_['email_approval_body'] = 'تم الموافقة على حسابك في مجموعة %s. يمكنك الآن الدخول إلى النظام.';

// API
$_['api_success']       = 'تم تنفيذ العملية بنجاح';
$_['api_error']         = 'خطأ في تنفيذ العملية';
$_['api_invalid_data']  = 'بيانات غير صحيحة';
$_['api_not_found']     = 'المجموعة غير موجودة';
$_['api_permission_denied'] = 'ليس لديك صلاحية لهذه العملية';
