# تقرير مراجعة شاشات لوحة التحكم - قسم المشتريات والموردين (Purchase & Suppliers)

## نظرة عامة
قسم المشتريات والموردين يدير كامل دورة الشراء من طلبات الشراء الداخلية وحتى سداد فواتير الموردين، بالإضافة إلى إدارة الموردين وعمليات الاستيراد.

## الشاشات المراجعة

### 1. دورة الشراء

#### 1.1 طلبات الشراء الداخلية
- **الملفات**: 
  - Controller: `dashboard/controller/purchase/requisition.php`
  - Model: `dashboard/model/purchase/requisition.php`
  - View: `dashboard/view/template/purchase/requisition.twig`
  - Language: `dashboard/language/ar/purchase/requisition.php`
- **الحالة**: مكتملة بنسبة 85%
- **المشاكل المحددة**:
  - مشاكل في دورة الموافقات
  - بعض المشاكل في تحويل الطلب الداخلي إلى أمر شراء
  - واجهة المستخدم تحتاج إلى تحسين
- **التكامل مع النظام**:
  - تكامل جيد مع نظام المخزون
  - تكامل متوسط مع نظام الموافقات

#### 1.2 عروض أسعار الموردين
- **الملفات**: 
  - Controller: `dashboard/controller/purchase/supplier_quote.php`
  - Model: `dashboard/model/purchase/supplier_quote.php`
  - View: `dashboard/view/template/purchase/supplier_quote.twig`
  - Language: `dashboard/language/ar/purchase/supplier_quote.php`
- **الحالة**: مكتملة بنسبة 70%
- **المشاكل المحددة**:
  - لا يدعم بشكل كامل مقارنة عروض الأسعار
  - مشاكل في تتبع صلاحية العروض
  - واجهة المستخدم غير مكتملة
- **التكامل مع النظام**:
  - تكامل متوسط مع نظام الموردين
  - تكامل ضعيف مع نظام طلبات الشراء

#### 1.3 أوامر الشراء
- **الملفات**: 
  - Controller: `dashboard/controller/purchase/order.php`
  - Model: `dashboard/model/purchase/order.php`
  - View: `dashboard/view/template/purchase/order.twig`
  - Language: `dashboard/language/ar/purchase/order.php`
- **الحالة**: مكتملة بنسبة 90%
- **المشاكل المحددة**:
  - بعض المشاكل في تعديل أوامر الشراء بعد الإصدار
  - مشاكل في تتبع حالة أوامر الشراء
  - بعض المشاكل في طباعة أوامر الشراء
- **التكامل مع النظام**:
  - تكامل جيد مع نظام الموردين والمخزون
  - تكامل متوسط مع نظام المحاسبة

#### 1.4 إيصالات استلام البضائع
- **الملفات**: 
  - Controller: `dashboard/controller/purchase/goods_receipt.php`
  - Model: `dashboard/model/purchase/goods_receipt.php`
  - View: `dashboard/view/template/purchase/goods_receipt.twig`
  - Language: `dashboard/language/ar/purchase/goods_receipt.php`
- **الحالة**: مكتملة بنسبة 85%
- **المشاكل المحددة**:
  - مشاكل في حساب تكلفة المتوسط المرجح
  - لا يدعم بشكل كامل الاستلام الجزئي
  - مشاكل في إنشاء القيود المحاسبية
- **التكامل مع النظام**:
  - تكامل جيد مع نظام المخزون
  - تكامل متوسط مع نظام المحاسبة

#### 1.5 فواتير الموردين
- **الملفات**: 
  - Controller: `dashboard/controller/purchase/invoice.php`
  - Model: `dashboard/model/purchase/invoice.php`
  - View: `dashboard/view/template/purchase/invoice.twig`
  - Language: `dashboard/language/ar/purchase/invoice.php`
- **الحالة**: مكتملة بنسبة 80%
- **المشاكل المحددة**:
  - مشاكل في ربط الفاتورة بإيصالات استلام متعددة
  - بعض المشاكل في حساب الضرائب
  - مشاكل في إنشاء القيود المحاسبية
- **التكامل مع النظام**:
  - تكامل جيد مع نظام الموردين
  - تكامل متوسط مع نظام المحاسبة

#### 1.6 المطابقة الثلاثية
- **الملفات**: 
  - Controller: `dashboard/controller/purchase/three_way_matching.php`
  - Model: `dashboard/model/purchase/three_way_matching.php`
  - View: `dashboard/view/template/purchase/three_way_matching.twig`
  - Language: `dashboard/language/ar/purchase/three_way_matching.php`
- **الحالة**: مكتملة بنسبة 65%
- **المشاكل المحددة**:
  - واجهة المستخدم غير بديهية
  - مشاكل في تحديد الفروقات بين المستندات
  - آلية المطابقة غير مكتملة
- **التكامل مع النظام**:
  - تكامل متوسط مع نظام المشتريات
  - تكامل ضعيف مع نظام المحاسبة

#### 1.7 مرتجعات المشتريات
- **الملفات**: 
  - Controller: `dashboard/controller/purchase/return.php`
  - Model: `dashboard/model/purchase/return.php`
  - View: `dashboard/view/template/purchase/return.twig`
  - Language: `dashboard/language/ar/purchase/return.php`
- **الحالة**: مكتملة بنسبة 75%
- **المشاكل المحددة**:
  - مشاكل في تأثير المرتجعات على تكلفة المخزون
  - لا يدعم بشكل كامل إشعارات الخصم
  - مشاكل في إنشاء القيود المحاسبية
- **التكامل مع النظام**:
  - تكامل متوسط مع نظام المخزون
  - تكامل ضعيف مع نظام المحاسبة

#### 1.8 مدفوعات الموردين
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
  - تكامل جيد مع نظام الموردين
  - تكامل متوسط مع نظام المحاسبة

### 2. إدارة الموردين

#### 2.1 الموردين
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

#### 2.2 مجموعات الموردين
- **الملفات**: 
  - Controller: `dashboard/controller/supplier/group.php`
  - Model: `dashboard/model/supplier/group.php`
  - View: `dashboard/view/template/supplier/group.twig`
  - Language: `dashboard/language/ar/supplier/group.php`
- **الحالة**: مكتملة بنسبة 90%
- **المشاكل المحددة**:
  - محدودية في تخصيص خصائص المجموعات
  - بعض المشاكل في تطبيق الإعدادات على الموردين
- **التكامل مع النظام**:
  - تكامل جيد مع نظام الموردين
  - لا يحتاج إلى تكامل مع أنظمة أخرى

#### 2.3 تقييم الموردين
- **الملفات**: 
  - Controller: `dashboard/controller/supplier/evaluation.php`
  - Model: `dashboard/model/supplier/evaluation.php`
  - View: `dashboard/view/template/supplier/evaluation.twig`
  - Language: `dashboard/language/ar/supplier/evaluation.php`
- **الحالة**: مكتملة بنسبة 60%
- **المشاكل المحددة**:
  - معايير التقييم غير مكتملة
  - واجهة المستخدم غير بديهية
  - آلية التقييم التلقائي غير مكتملة
- **التكامل مع النظام**:
  - تكامل متوسط مع نظام المشتريات
  - تكامل ضعيف مع نظام الجودة

#### 2.4 حسابات الموردين
- **الملفات**: 
  - Controller: `dashboard/controller/supplier/account.php`
  - Model: `dashboard/model/supplier/account.php`
  - View: `dashboard/view/template/supplier/account.twig`
  - Language: `dashboard/language/ar/supplier/account.php`
- **الحالة**: مكتملة بنسبة 85%
- **المشاكل المحددة**:
  - بعض المشاكل في عرض أعمار الديون
  - مشاكل في تسوية الحسابات
  - بعض المشاكل في طباعة كشوف الحساب
- **التكامل مع النظام**:
  - تكامل جيد مع نظام المحاسبة
  - تكامل جيد مع نظام المشتريات

### 3. إدارة الاستيراد

#### 3.1 ملفات الاستيراد
- **الملفات**: 
  - Controller: `dashboard/controller/import/file.php`
  - Model: `dashboard/model/import/file.php`
  - View: `dashboard/view/template/import/file.twig`
  - Language: `dashboard/language/ar/import/file.php`
- **الحالة**: مكتملة بنسبة 70%
- **المشاكل المحددة**:
  - واجهة المستخدم غير مكتملة
  - مشاكل في تتبع حالة ملفات الاستيراد
  - محدودية في إدارة المستندات المرتبطة
- **التكامل مع النظام**:
  - تكامل متوسط مع نظام المشتريات
  - تكامل ضعيف مع نظام المخزون

#### 3.2 تكاليف الاستيراد
- **الملفات**: 
  - Controller: `dashboard/controller/import/cost.php`
  - Model: `dashboard/model/import/cost.php`
  - View: `dashboard/view/template/import/cost.twig`
  - Language: `dashboard/language/ar/import/cost.php`
- **الحالة**: مكتملة بنسبة 65%
- **المشاكل المحددة**:
  - آلية توزيع التكاليف غير مكتملة
  - مشاكل في إنشاء القيود المحاسبية
  - واجهة المستخدم غير بديهية
- **التكامل مع النظام**:
  - تكامل متوسط مع نظام المخزون
  - تكامل ضعيف مع نظام المحاسبة

#### 3.3 متابعة الشحنات
- **الملفات**: 
  - Controller: `dashboard/controller/import/shipment.php`
  - Model: `dashboard/model/import/shipment.php`
  - View: `dashboard/view/template/import/shipment.twig`
  - Language: `dashboard/language/ar/import/shipment.php`
- **الحالة**: مكتملة بنسبة 60%
- **المشاكل المحددة**:
  - آلية تتبع الشحنات غير مكتملة
  - واجهة المستخدم غير مكتملة
  - لا يدعم التكامل مع خدمات الشحن الخارجية
- **التكامل مع النظام**:
  - تكامل متوسط مع نظام المشتريات
  - تكامل ضعيف مع نظام المخزون

### 4. إعدادات المشتريات

#### 4.1 إعدادات عامة للمشتريات
- **الملفات**: 
  - Controller: `dashboard/controller/purchase/setting.php`
  - Model: `dashboard/model/purchase/setting.php`
  - View: `dashboard/view/template/purchase/setting.twig`
  - Language: `dashboard/language/ar/purchase/setting.php`
- **الحالة**: مكتملة بنسبة 85%
- **المشاكل المحددة**:
  - بعض الإعدادات غير مفعلة
  - واجهة المستخدم تحتاج إلى تحسين
- **التكامل مع النظام**:
  - تكامل جيد مع نظام المشتريات
  - تكامل متوسط مع بقية النظام

#### 4.2 إعدادات الموافقات والصلاحيات
- **الملفات**: 
  - Controller: `dashboard/controller/purchase/approval_setting.php`
  - Model: `dashboard/model/purchase/approval_setting.php`
  - View: `dashboard/view/template/purchase/approval_setting.twig`
  - Language: `dashboard/language/ar/purchase/approval_setting.php`
- **الحالة**: مكتملة بنسبة 70%
- **المشاكل المحددة**:
  - آلية الموافقات المتعددة غير مكتملة
  - مشاكل في تطبيق حدود الصلاحيات
  - واجهة المستخدم غير بديهية
- **التكامل مع النظام**:
  - تكامل متوسط مع نظام المستخدمين والصلاحيات
  - تكامل متوسط مع نظام المشتريات

#### 4.3 إعدادات الإشعارات والتنبيهات
- **الملفات**: 
  - Controller: `dashboard/controller/purchase/notification_setting.php`
  - Model: `dashboard/model/purchase/notification_setting.php`
  - View: `dashboard/view/template/purchase/notification_setting.twig`
  - Language: `dashboard/language/ar/purchase/notification_setting.php`
- **الحالة**: مكتملة بنسبة 75%
- **المشاكل المحددة**:
  - بعض أنواع الإشعارات غير مفعلة
  - مشاكل في إرسال الإشعارات
  - واجهة المستخدم تحتاج إلى تحسين
- **التكامل مع النظام**:
  - تكامل متوسط مع نظام الإشعارات
  - تكامل متوسط مع نظام المشتريات

#### 4.4 إعدادات التقارير والتحليلات
- **الملفات**: 
  - Controller: `dashboard/controller/purchase/report_setting.php`
  - Model: `dashboard/model/purchase/report_setting.php`
  - View: `dashboard/view/template/purchase/report_setting.twig`
  - Language: `dashboard/language/ar/purchase/report_setting.php`
- **الحالة**: مكتملة بنسبة 80%
- **المشاكل المحددة**:
  - بعض خيارات التقارير غير مفعلة
  - واجهة المستخدم تحتاج إلى تحسين
- **التكامل مع النظام**:
  - تكامل جيد مع نظام التقارير
  - تكامل جيد مع نظام المشتريات

## التوصيات
1. تحسين آلية المطابقة الثلاثية وواجهة المستخدم الخاصة بها
2. إصلاح مشاكل تأثير مرتجعات المشتريات على تكلفة المخزون
3. استكمال آلية تقييم الموردين وتحسين واجهة المستخدم
4. تطوير آلية توزيع تكاليف الاستيراد وتكاملها مع المحاسبة
5. تحسين آلية الموافقات المتعددة في إعدادات الموافقات والصلاحيات

## الأولوية
عالية - هذا القسم أساسي لعمليات الشراء والتوريد ويؤثر بشكل مباشر على المخزون والمحاسبة
