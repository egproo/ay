# تقرير مراجعة شاشات لوحة التحكم - قسم المحاسبة والمالية (Accounting & Finance)

## نظرة عامة
قسم المحاسبة والمالية يعتبر من الأقسام الأساسية في النظام، حيث يدير كامل العمليات المالية والمحاسبية، بما في ذلك دفتر الأستاذ العام، الحسابات المدينة والدائنة، إدارة النقدية، والتقارير المالية.

## الشاشات المراجعة

### 1. دفتر الأستاذ العام

#### 1.1 شجرة الحسابات
- **الملفات**: 
  - Controller: `dashboard/controller/accounts/chartaccount.php`
  - Model: `dashboard/model/accounts/chartaccount.php`
  - View: `dashboard/view/template/accounts/chartaccount.twig`
  - Language: `dashboard/language/ar/accounts/chartaccount.php`
- **الحالة**: مكتملة بنسبة 95%
- **المشاكل المحددة**:
  - بعض المشاكل في عرض الحسابات الهرمية العميقة
  - محدودية في تخصيص مستويات الحسابات
- **التكامل مع النظام**:
  - تكامل ممتاز مع نظام المحاسبة
  - تكامل جيد مع بقية النظام

#### 1.2 قيود اليومية
- **الملفات**: 
  - Controller: `dashboard/controller/accounts/journal.php`
  - Model: `dashboard/model/accounts/journal.php`
  - View: `dashboard/view/template/accounts/journal.twig`
  - Language: `dashboard/language/ar/accounts/journal.php`
- **الحالة**: مكتملة بنسبة 90%
- **المشاكل المحددة**:
  - واجهة المستخدم تحتاج إلى تحسين
  - بعض المشاكل في القيود المتكررة
  - محدودية في تخصيص نماذج القيود
- **التكامل مع النظام**:
  - تكامل ممتاز مع نظام المحاسبة
  - تكامل جيد مع بقية النظام

#### 1.3 ترحيل القيود
- **الملفات**: 
  - Controller: `dashboard/controller/accounts/posting.php`
  - Model: `dashboard/model/accounts/posting.php`
  - View: `dashboard/view/template/accounts/posting.twig`
  - Language: `dashboard/language/ar/accounts/posting.php`
- **الحالة**: مكتملة بنسبة 85%
- **المشاكل المحددة**:
  - بطء في ترحيل القيود الكثيرة
  - بعض المشاكل في التراجع عن الترحيل
  - واجهة المستخدم تحتاج إلى تحسين
- **التكامل مع النظام**:
  - تكامل ممتاز مع نظام المحاسبة
  - لا يحتاج إلى تكامل مع أنظمة أخرى

#### 1.4 الفترات المحاسبية
- **الملفات**: 
  - Controller: `dashboard/controller/accounting/period.php`
  - Model: `dashboard/model/accounting/period.php`
  - View: `dashboard/view/template/accounting/period.twig`
  - Language: `dashboard/language/ar/accounting/period.php`
- **الحالة**: مكتملة بنسبة 90%
- **المشاكل المحددة**:
  - بعض المشاكل في إقفال الفترات
  - محدودية في تخصيص الفترات
- **التكامل مع النظام**:
  - تكامل ممتاز مع نظام المحاسبة
  - تكامل جيد مع بقية النظام

#### 1.5 إقفال السنة المالية
- **الملفات**: 
  - Controller: `dashboard/controller/accounting/year_end.php`
  - Model: `dashboard/model/accounting/year_end.php`
  - View: `dashboard/view/template/accounting/year_end.twig`
  - Language: `dashboard/language/ar/accounting/year_end.php`
- **الحالة**: مكتملة بنسبة 80%
- **المشاكل المحددة**:
  - آلية إقفال السنة غير مكتملة
  - بعض المشاكل في ترحيل الأرصدة
  - واجهة المستخدم غير بديهية
- **التكامل مع النظام**:
  - تكامل جيد مع نظام المحاسبة
  - لا يحتاج إلى تكامل مع أنظمة أخرى

### 2. الحسابات المدينة

#### 2.1 العملاء
- **الملفات**: 
  - Controller: `dashboard/controller/customer/customer.php`
  - Model: `dashboard/model/customer/customer.php`
  - View: `dashboard/view/template/customer/customer.twig`
  - Language: `dashboard/language/ar/customer/customer.php`
- **الحالة**: مكتملة بنسبة 95%
- **المشاكل المحددة**:
  - بعض المشاكل في إدارة جهات الاتصال المتعددة
  - محدودية في تخصيص حقول العملاء
- **التكامل مع النظام**:
  - تكامل ممتاز مع نظام المبيعات
  - تكامل جيد مع نظام المحاسبة

#### 2.2 فواتير العملاء
- **الملفات**: 
  - Controller: `dashboard/controller/sale/invoice.php`
  - Model: `dashboard/model/sale/invoice.php`
  - View: `dashboard/view/template/sale/invoice.twig`
  - Language: `dashboard/language/ar/sale/invoice.php`
- **الحالة**: مكتملة بنسبة 90%
- **المشاكل المحددة**:
  - بعض المشاكل في تعديل الفواتير بعد الإصدار
  - محدودية في تخصيص نماذج الطباعة
  - بعض المشاكل في تطبيق الضرائب المعقدة
- **التكامل مع النظام**:
  - تكامل ممتاز مع نظام المبيعات
  - تكامل جيد مع نظام المحاسبة

#### 2.3 تحصيلات العملاء
- **الملفات**: 
  - Controller: `dashboard/controller/sale/receipt.php`
  - Model: `dashboard/model/sale/receipt.php`
  - View: `dashboard/view/template/sale/receipt.twig`
  - Language: `dashboard/language/ar/sale/receipt.php`
- **الحالة**: مكتملة بنسبة 85%
- **المشاكل المحددة**:
  - مشاكل في ربط التحصيل بفواتير متعددة
  - بعض المشاكل في إنشاء القيود المحاسبية
  - لا يدعم بشكل كامل تحصيل الشيكات
- **التكامل مع النظام**:
  - تكامل جيد مع نظام المبيعات
  - تكامل جيد مع نظام المحاسبة

#### 2.4 أعمار الديون
- **الملفات**: 
  - Controller: `dashboard/controller/accounts/aging_report.php`
  - Model: `dashboard/model/accounts/aging_report.php`
  - View: `dashboard/view/template/accounts/aging_report.twig`
  - Language: `dashboard/language/ar/accounts/aging_report.php`
- **الحالة**: مكتملة بنسبة 90%
- **المشاكل المحددة**:
  - بطء في تحميل التقارير الكبيرة
  - محدودية في تخصيص فترات الأعمار
  - بعض المشاكل في تصدير البيانات
- **التكامل مع النظام**:
  - تكامل ممتاز مع نظام المحاسبة
  - تكامل جيد مع نظام المبيعات

### 3. الحسابات الدائنة

#### 3.1 الموردين
- **الملفات**: 
  - Controller: `dashboard/controller/supplier/supplier.php`
  - Model: `dashboard/model/supplier/supplier.php`
  - View: `dashboard/view/template/supplier/supplier.twig`
  - Language: `dashboard/language/ar/supplier/supplier.php`
- **الحالة**: مكتملة بنسبة 95%
- **المشاكل المحددة**:
  - بعض المشاكل في إدارة جهات الاتصال المتعددة
  - محدودية في تخصيص حقول الموردين
- **التكامل مع النظام**:
  - تكامل ممتاز مع نظام المشتريات
  - تكامل جيد مع نظام المحاسبة

#### 3.2 فواتير الموردين
- **الملفات**: 
  - Controller: `dashboard/controller/purchase/invoice.php`
  - Model: `dashboard/model/purchase/invoice.php`
  - View: `dashboard/view/template/purchase/invoice.twig`
  - Language: `dashboard/language/ar/purchase/invoice.php`
- **الحالة**: مكتملة بنسبة 85%
- **المشاكل المحددة**:
  - مشاكل في ربط الفاتورة بإيصالات استلام متعددة
  - بعض المشاكل في حساب الضرائب
  - مشاكل في إنشاء القيود المحاسبية
- **التكامل مع النظام**:
  - تكامل جيد مع نظام المشتريات
  - تكامل متوسط مع نظام المحاسبة

#### 3.3 مدفوعات الموردين
- **الملفات**: 
  - Controller: `dashboard/controller/purchase/payment.php`
  - Model: `dashboard/model/purchase/payment.php`
  - View: `dashboard/view/template/purchase/payment.twig`
  - Language: `dashboard/language/ar/purchase/payment.php`
- **الحالة**: مكتملة بنسبة 85%
- **المشاكل المحددة**:
  - مشاكل في سداد فواتير متعددة
  - لا يدعم بشكل كامل الخصومات النقدية
  - بعض المشاكل في إنشاء القيود المحاسبية
- **التكامل مع النظام**:
  - تكامل جيد مع نظام المشتريات
  - تكامل متوسط مع نظام المحاسبة

#### 3.4 أعمار الديون للموردين
- **الملفات**: 
  - Controller: `dashboard/controller/accounts/supplier_aging.php`
  - Model: `dashboard/model/accounts/supplier_aging.php`
  - View: `dashboard/view/template/accounts/supplier_aging.twig`
  - Language: `dashboard/language/ar/accounts/supplier_aging.php`
- **الحالة**: مكتملة بنسبة 85%
- **المشاكل المحددة**:
  - بطء في تحميل التقارير الكبيرة
  - محدودية في تخصيص فترات الأعمار
  - بعض المشاكل في تصدير البيانات
- **التكامل مع النظام**:
  - تكامل جيد مع نظام المحاسبة
  - تكامل جيد مع نظام المشتريات

### 4. إدارة النقدية

#### 4.1 الحسابات البنكية
- **الملفات**: 
  - Controller: `dashboard/controller/banking/account.php`
  - Model: `dashboard/model/banking/account.php`
  - View: `dashboard/view/template/banking/account.twig`
  - Language: `dashboard/language/ar/banking/account.php`
- **الحالة**: مكتملة بنسبة 90%
- **المشاكل المحددة**:
  - محدودية في تخصيص حقول الحسابات البنكية
  - بعض المشاكل في إدارة العملات المتعددة
- **التكامل مع النظام**:
  - تكامل ممتاز مع نظام المحاسبة
  - تكامل جيد مع نظام المدفوعات والتحصيلات

#### 4.2 الإيداعات البنكية
- **الملفات**: 
  - Controller: `dashboard/controller/banking/deposit.php`
  - Model: `dashboard/model/banking/deposit.php`
  - View: `dashboard/view/template/banking/deposit.twig`
  - Language: `dashboard/language/ar/banking/deposit.php`
- **الحالة**: مكتملة بنسبة 85%
- **المشاكل المحددة**:
  - مشاكل في ربط الإيداع بتحصيلات متعددة
  - بعض المشاكل في إنشاء القيود المحاسبية
  - واجهة المستخدم تحتاج إلى تحسين
- **التكامل مع النظام**:
  - تكامل جيد مع نظام المحاسبة
  - تكامل متوسط مع نظام المبيعات

#### 4.3 السحوبات البنكية
- **الملفات**: 
  - Controller: `dashboard/controller/banking/withdrawal.php`
  - Model: `dashboard/model/banking/withdrawal.php`
  - View: `dashboard/view/template/banking/withdrawal.twig`
  - Language: `dashboard/language/ar/banking/withdrawal.php`
- **الحالة**: مكتملة بنسبة 85%
- **المشاكل المحددة**:
  - مشاكل في ربط السحب بمدفوعات متعددة
  - بعض المشاكل في إنشاء القيود المحاسبية
  - واجهة المستخدم تحتاج إلى تحسين
- **التكامل مع النظام**:
  - تكامل جيد مع نظام المحاسبة
  - تكامل متوسط مع نظام المشتريات

#### 4.4 التسوية البنكية
- **الملفات**: 
  - Controller: `dashboard/controller/banking/reconciliation.php`
  - Model: `dashboard/model/banking/reconciliation.php`
  - View: `dashboard/view/template/banking/reconciliation.twig`
  - Language: `dashboard/language/ar/banking/reconciliation.php`
- **الحالة**: مكتملة بنسبة 75%
- **المشاكل المحددة**:
  - آلية التسوية غير مكتملة
  - واجهة المستخدم غير بديهية
  - مشاكل في تسوية العمليات المعلقة
- **التكامل مع النظام**:
  - تكامل متوسط مع نظام المحاسبة
  - تكامل ضعيف مع نظام البنوك

#### 4.5 إدارة الشيكات
- **الملفات**: 
  - Controller: `dashboard/controller/banking/cheque.php`
  - Model: `dashboard/model/banking/cheque.php`
  - View: `dashboard/view/template/banking/cheque.twig`
  - Language: `dashboard/language/ar/banking/cheque.php`
- **الحالة**: مكتملة بنسبة 70%
- **المشاكل المحددة**:
  - آلية تتبع حالات الشيكات غير مكتملة
  - واجهة المستخدم غير بديهية
  - مشاكل في إنشاء القيود المحاسبية
- **التكامل مع النظام**:
  - تكامل متوسط مع نظام المحاسبة
  - تكامل ضعيف مع نظام المبيعات والمشتريات

### 5. التقارير المالية

#### 5.1 ميزان المراجعة
- **الملفات**: 
  - Controller: `dashboard/controller/accounts/trial_balance.php`
  - Model: `dashboard/model/accounts/trial_balance.php`
  - View: `dashboard/view/template/accounts/trial_balance.twig`
  - Language: `dashboard/language/ar/accounts/trial_balance.php`
- **الحالة**: مكتملة بنسبة 95%
- **المشاكل المحددة**:
  - بطء في تحميل التقارير الكبيرة
  - محدودية في تخصيص التقرير
  - بعض المشاكل في تصدير البيانات
- **التكامل مع النظام**:
  - تكامل ممتاز مع نظام المحاسبة
  - لا يحتاج إلى تكامل مع أنظمة أخرى

#### 5.2 قائمة الدخل
- **الملفات**: 
  - Controller: `dashboard/controller/accounts/income_statement.php`
  - Model: `dashboard/model/accounts/income_statement.php`
  - View: `dashboard/view/template/accounts/income_statement.twig`
  - Language: `dashboard/language/ar/accounts/income_statement.php`
- **الحالة**: مكتملة بنسبة 90%
- **المشاكل المحددة**:
  - بطء في تحميل التقارير الكبيرة
  - محدودية في تخصيص التقرير
  - بعض المشاكل في المقارنات بين الفترات
- **التكامل مع النظام**:
  - تكامل ممتاز مع نظام المحاسبة
  - لا يحتاج إلى تكامل مع أنظمة أخرى

#### 5.3 الميزانية العمومية
- **الملفات**: 
  - Controller: `dashboard/controller/accounts/balance_sheet.php`
  - Model: `dashboard/model/accounts/balance_sheet.php`
  - View: `dashboard/view/template/accounts/balance_sheet.twig`
  - Language: `dashboard/language/ar/accounts/balance_sheet.php`
- **الحالة**: مكتملة بنسبة 90%
- **المشاكل المحددة**:
  - بطء في تحميل التقارير الكبيرة
  - محدودية في تخصيص التقرير
  - بعض المشاكل في المقارنات بين الفترات
- **التكامل مع النظام**:
  - تكامل ممتاز مع نظام المحاسبة
  - لا يحتاج إلى تكامل مع أنظمة أخرى

#### 5.4 قائمة التدفقات النقدية
- **الملفات**: 
  - Controller: `dashboard/controller/accounts/cash_flow.php`
  - Model: `dashboard/model/accounts/cash_flow.php`
  - View: `dashboard/view/template/accounts/cash_flow.twig`
  - Language: `dashboard/language/ar/accounts/cash_flow.php`
- **الحالة**: مكتملة بنسبة 80%
- **المشاكل المحددة**:
  - آلية حساب التدفقات النقدية غير مكتملة
  - بطء في تحميل التقارير الكبيرة
  - محدودية في تخصيص التقرير
- **التكامل مع النظام**:
  - تكامل جيد مع نظام المحاسبة
  - لا يحتاج إلى تكامل مع أنظمة أخرى

#### 5.5 قائمة التغيرات في حقوق الملكية
- **الملفات**: 
  - Controller: `dashboard/controller/accounts/changes_in_equity.php`
  - Model: `dashboard/model/accounts/changes_in_equity.php`
  - View: `dashboard/view/template/accounts/changes_in_equity.twig`
  - Language: `dashboard/language/ar/accounts/changes_in_equity.php`
- **الحالة**: مكتملة بنسبة 75%
- **المشاكل المحددة**:
  - آلية حساب التغيرات في حقوق الملكية غير مكتملة
  - بطء في تحميل التقارير الكبيرة
  - محدودية في تخصيص التقرير
- **التكامل مع النظام**:
  - تكامل متوسط مع نظام المحاسبة
  - لا يحتاج إلى تكامل مع أنظمة أخرى

### 6. الموازنات والتخطيط المالي

#### 6.1 إعداد الموازنات
- **الملفات**: 
  - Controller: `dashboard/controller/accounting/budget.php`
  - Model: `dashboard/model/accounting/budget.php`
  - View: `dashboard/view/template/accounting/budget.twig`
  - Language: `dashboard/language/ar/accounting/budget.php`
- **الحالة**: مكتملة بنسبة 70%
- **المشاكل المحددة**:
  - آلية إعداد الموازنات غير مكتملة
  - واجهة المستخدم غير بديهية
  - محدودية في تخصيص الموازنات
- **التكامل مع النظام**:
  - تكامل متوسط مع نظام المحاسبة
  - تكامل ضعيف مع بقية النظام

#### 6.2 مقارنة الموازنات بالفعلي
- **الملفات**: 
  - Controller: `dashboard/controller/accounting/budget_comparison.php`
  - Model: `dashboard/model/accounting/budget_comparison.php`
  - View: `dashboard/view/template/accounting/budget_comparison.twig`
  - Language: `dashboard/language/ar/accounting/budget_comparison.php`
- **الحالة**: مكتملة بنسبة 65%
- **المشاكل المحددة**:
  - آلية المقارنة غير مكتملة
  - بطء في تحميل التقارير الكبيرة
  - محدودية في تخصيص التقرير
- **التكامل مع النظام**:
  - تكامل متوسط مع نظام المحاسبة
  - لا يحتاج إلى تكامل مع أنظمة أخرى

#### 6.3 التنبؤات المالية
- **الملفات**: 
  - Controller: `dashboard/controller/accounting/forecast.php`
  - Model: `dashboard/model/accounting/forecast.php`
  - View: `dashboard/view/template/accounting/forecast.twig`
  - Language: `dashboard/language/ar/accounting/forecast.php`
- **الحالة**: مكتملة بنسبة 60%
- **المشاكل المحددة**:
  - آلية التنبؤ غير مكتملة
  - واجهة المستخدم غير بديهية
  - محدودية في نماذج التنبؤ
- **التكامل مع النظام**:
  - تكامل ضعيف مع نظام المحاسبة
  - تكامل ضعيف مع بقية النظام

### 7. إعدادات المحاسبة

#### 7.1 إعدادات عامة للمحاسبة
- **الملفات**: 
  - Controller: `dashboard/controller/accounting/setting.php`
  - Model: `dashboard/model/accounting/setting.php`
  - View: `dashboard/view/template/accounting/setting.twig`
  - Language: `dashboard/language/ar/accounting/setting.php`
- **الحالة**: مكتملة بنسبة 90%
- **المشاكل المحددة**:
  - بعض الإعدادات غير مفعلة
  - واجهة المستخدم تحتاج إلى تحسين
- **التكامل مع النظام**:
  - تكامل ممتاز مع نظام المحاسبة
  - تكامل جيد مع بقية النظام

#### 7.2 إعدادات الضرائب
- **الملفات**: 
  - Controller: `dashboard/controller/accounting/tax.php`
  - Model: `dashboard/model/accounting/tax.php`
  - View: `dashboard/view/template/accounting/tax.twig`
  - Language: `dashboard/language/ar/accounting/tax.php`
- **الحالة**: مكتملة بنسبة 85%
- **المشاكل المحددة**:
  - محدودية في أنواع الضرائب المدعومة
  - بعض المشاكل في حساب الضرائب المعقدة
  - واجهة المستخدم تحتاج إلى تحسين
- **التكامل مع النظام**:
  - تكامل جيد مع نظام المحاسبة
  - تكامل جيد مع نظام المبيعات والمشتريات

#### 7.3 إعدادات العملات
- **الملفات**: 
  - Controller: `dashboard/controller/accounting/currency.php`
  - Model: `dashboard/model/accounting/currency.php`
  - View: `dashboard/view/template/accounting/currency.twig`
  - Language: `dashboard/language/ar/accounting/currency.php`
- **الحالة**: مكتملة بنسبة 85%
- **المشاكل المحددة**:
  - بعض المشاكل في تحديث أسعار الصرف
  - محدودية في تخصيص تنسيق العملات
  - واجهة المستخدم تحتاج إلى تحسين
- **التكامل مع النظام**:
  - تكامل جيد مع نظام المحاسبة
  - تكامل جيد مع بقية النظام

## التوصيات
1. تحسين أداء التقارير المالية للبيانات الكبيرة
2. استكمال آلية إقفال السنة المالية وتحسين واجهة المستخدم
3. تطوير آلية التسوية البنكية وإدارة الشيكات
4. استكمال آليات الموازنات والتنبؤات المالية
5. تحسين تكامل نظام المحاسبة مع نظام المشتريات والمبيعات، خاصة في إنشاء القيود المحاسبية

## الأولوية
عالية جداً - هذا القسم أساسي للنظام ويؤثر بشكل مباشر على التقارير المالية والامتثال القانوني
