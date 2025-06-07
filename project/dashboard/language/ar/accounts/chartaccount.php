<?php
// Heading
$_['heading_title']                    = 'دليل الحسابات';

// Text
$_['text_success']                     = 'تم: تم تعديل دليل الحسابات بنجاح!';
$_['text_list']                        = 'قائمة الحسابات';
$_['text_add']                         = 'إضافة حساب';
$_['text_edit']                        = 'تعديل حساب';
$_['text_default']                     = 'افتراضي';
$_['text_enabled']                     = 'مفعل';
$_['text_disabled']                    = 'معطل';
$_['text_yes']                         = 'نعم';
$_['text_no']                          = 'لا';
$_['text_none']                        = 'لا يوجد';
$_['text_select']                      = 'اختر';
$_['text_chart_of_accounts']           = 'دليل الحسابات';
$_['text_account_tree']                = 'شجرة الحسابات';
$_['text_account_balance']             = 'رصيد الحساب';
$_['text_account_statement']           = 'كشف الحساب';
$_['text_parent_account']              = 'الحساب الأب';
$_['text_sub_accounts']                = 'الحسابات الفرعية';

// Column
$_['column_account_code']              = 'رقم الحساب';
$_['column_account_name']              = 'اسم الحساب';
$_['column_account_type']              = 'نوع الحساب';
$_['column_account_nature']            = 'طبيعة الحساب';
$_['column_parent_account']            = 'الحساب الأب';
$_['column_level']                     = 'المستوى';
$_['column_current_balance']           = 'الرصيد الحالي';
$_['column_opening_balance']           = 'رصيد أول المدة';
$_['column_is_parent']                 = 'حساب أب';
$_['column_allow_posting']             = 'يسمح بالترحيل';
$_['column_status']                    = 'الحالة';
$_['column_sort_order']                = 'ترتيب العرض';
$_['column_action']                    = 'إجراء';
$_['column_name']                      = 'اسم الحساب';
$_['column_code']                      = 'رقم الحساب';
$_['column_sort_account_code']         = 'ترتيب الفرز';

// Entry
$_['entry_account_code']               = 'رقم الحساب';
$_['entry_account_name']               = 'اسم الحساب';
$_['entry_account_description']        = 'وصف الحساب';
$_['entry_account_type']               = 'نوع الحساب';
$_['entry_account_nature']             = 'طبيعة الحساب';
$_['entry_parent_account']             = 'الحساب الأب';
$_['entry_opening_balance']            = 'رصيد أول المدة';
$_['entry_is_parent']                  = 'حساب أب';
$_['entry_allow_posting']              = 'يسمح بالترحيل';
$_['entry_status']                     = 'الحالة';
$_['entry_sort_order']                 = 'ترتيب العرض';
$_['entry_name']                       = 'اسم الحساب';
$_['entry_parent']                     = 'الحساب الأب';
$_['entry_filter']                     = 'الفلاتر';

// Account Types
$_['text_account_type_asset']          = 'أصول';
$_['text_account_type_liability']      = 'خصوم';
$_['text_account_type_equity']         = 'حقوق الملكية';
$_['text_account_type_revenue']        = 'إيرادات';
$_['text_account_type_expense']        = 'مصروفات';

// Account Nature
$_['text_account_nature_debit']        = 'مدين';
$_['text_account_nature_credit']       = 'دائن';
$_['text_debit']                       = 'مدين';
$_['text_credit']                      = 'دائن';

// Help
$_['help_account_code']                = 'رقم الحساب يجب أن يكون فريداً ولا يمكن تكراره';
$_['help_account_type']                = 'نوع الحساب يحدد طبيعته المحاسبية';
$_['help_parent_account']              = 'اختر الحساب الأب لإنشاء هيكل شجري';
$_['help_opening_balance']             = 'الرصيد الافتتاحي للحساب';
$_['help_is_parent']                   = 'الحسابات الأب لا يمكن الترحيل إليها مباشرة';
$_['help_allow_posting']               = 'يحدد إمكانية الترحيل المباشر للحساب';
$_['help_sort_order']                  = 'ترتيب عرض الحساب في القوائم';
$_['help_filter']                      = '(اكتب بداية حرف أي كلمة لتظهر القائمة المنسدلة للاستكمال التلقائي)';

// Error
$_['error_permission']                 = 'تحذير: ليس لديك صلاحية تعديل دليل الحسابات!';
$_['error_account_code']               = 'رقم الحساب يجب أن يكون بين 1 و 20 رقم!';
$_['error_account_code_exists']        = 'رقم الحساب موجود مسبقاً!';
$_['error_account_name']               = 'اسم الحساب يجب أن يكون بين 1 و 255 حرف!';
$_['error_account_type']               = 'يجب اختيار نوع الحساب!';
$_['error_parent_account']             = 'الحساب الأب غير صحيح!';
$_['error_parent_self']                = 'لا يمكن أن يكون الحساب أب لنفسه!';
$_['error_has_children']               = 'لا يمكن حذف هذا الحساب لأنه يحتوي على حسابات فرعية!';
$_['error_has_transactions']           = 'لا يمكن حذف هذا الحساب لأنه يحتوي على قيود محاسبية!';
$_['error_opening_balance']            = 'رصيد أول المدة يجب أن يكون رقماً صحيحاً!';
$_['error_warning']                    = 'تحذير: يرجى التحقق من النموذج بعناية للأخطاء!';
$_['error_name']                       = 'اسم الحساب يجب أن يكون بين 1 و 255 رمزاً!';
$_['error_parent']                     = 'الحساب الذي اخترته هو قسم فرعي للقسم الحالي!';

// Button
$_['button_add_account']               = 'إضافة حساب';
$_['button_edit_account']              = 'تعديل حساب';
$_['button_delete_account']            = 'حذف حساب';
$_['button_view_statement']            = 'عرض كشف الحساب';
$_['button_update_balance']            = 'تحديث الرصيد';

// Success Messages
$_['success_account_added']            = 'تم إضافة الحساب بنجاح!';
$_['success_account_updated']          = 'تم تحديث الحساب بنجاح!';
$_['success_account_deleted']          = 'تم حذف الحساب بنجاح!';
$_['success_balance_updated']          = 'تم تحديث الرصيد بنجاح!';
