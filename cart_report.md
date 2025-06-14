# تقرير تفصيلي لشاشة سلة التسوق (Cart)

## معلومات عامة

- **اسم الشاشة**: سلة التسوق (Cart)
- **المسار**: checkout/cart
- **الوظيفة الأساسية**: إدارة سلة التسوق مع دعم الوحدات المتعددة، الباقات، والخصومات حسب الكمية

## مكونات الشاشة (MVC)

### 1. المتحكم (Controller)

- **الملف**: `/project/catalog/controller/checkout/cart.php`
- **الفئة**: `ControllerCheckoutCart`
- **الدوال الرئيسية**:
  - `add()`: إضافة منتج (رئيسي + باقة) إلى السلة (Ajax)
  - `index()`: عرض صفحة السلة
  - `remove()`: إزالة منتج من السلة
  - `update()`: تحديث كمية المنتج في السلة

### 2. النموذج (Model)

- **الملف الرئيسي**: `/project/system/library/cart.php` (مكتبة السلة)
- **الفئة**: `Cart`
- **الدوال الرئيسية**:
  - `add()`: إضافة منتج إلى السلة مع دعم الوحدات والباقات
  - `update()`: تحديث كمية المنتج
  - `remove()`: إزالة منتج من السلة
  - `getProducts()`: جلب منتجات السلة
  - `getTotal()`: حساب إجمالي السلة
  - `hasStock()`: التحقق من توفر المخزون

### 3. العرض (View)

- **الملف**: `/project/catalog/view/template/checkout/cart.twig`
- **الأقسام الرئيسية**:
  - جدول منتجات السلة
  - قسم الكميات والأسعار
  - قسم الخصومات والكوبونات
  - قسم الإجمالي والضرائب
  - أزرار متابعة التسوق والدفع

### 4. ملفات اللغة

- **الملف**: `/project/catalog/language/ar/checkout/cart.php`
- **المتغيرات الرئيسية**:
  - نصوص عامة للسلة
  - نصوص الخصومات والكوبونات
  - نصوص الأخطاء والتنبيهات

## التعديلات والإضافات الرئيسية

### 1. دعم الوحدات المتعددة

- **الوصف**: تم تعديل السلة لدعم الوحدات المتعددة للمنتج الواحد
- **الملفات المعدلة**:
  - Controller: تعديل `add()` لدعم معلمة `unit_id`
  - Model (Cart): تعديل `add()` و `getProducts()` لدعم الوحدات
  - View: عرض اسم الوحدة مع كل منتج
- **حالة الاكتمال**: مكتمل بنسبة 90%
- **المشاكل**:
  - بعض المشاكل في تحديث السعر عند تغيير الوحدة
  - عدم تحديث الكمية المتاحة بشكل صحيح عند تغيير الوحدة

### 2. نظام الباقات والعروض

- **الوصف**: تم إضافة دعم للباقات والعروض المركبة:
  - إضافة باقات كاملة إلى السلة
  - دعم المنتجات المجانية ضمن الباقات
  - ربط منتجات الباقة بالمنتج الرئيسي
- **الملفات المعدلة**:
  - Controller: تعديل `add()` لمعالجة منتجات الباقة
  - Model (Cart): إضافة معلمات `is_free`, `bundle_id`, `group_id`
  - View: عرض منتجات الباقة بطريقة مميزة
- **حالة الاكتمال**: مكتمل بنسبة 85%
- **المشاكل**:
  - مشاكل في إزالة باقة كاملة من السلة
  - عدم تحديث أسعار الباقة عند تغيير الكمية

### 3. نظام الأسعار المتقدم

- **الوصف**: تم تطوير نظام متقدم لحساب الأسعار في السلة:
  - دعم الخصومات حسب الكمية
  - دعم الأسعار الخاصة
  - حساب الضرائب بشكل صحيح
- **الملفات المعدلة**:
  - Controller: استخدام `getUnitPriceData()` لحساب السعر
  - Model (Cart): تعديل `getTotal()` لدعم الخصومات المتقدمة
  - View: عرض الخصومات والتوفير
- **حالة الاكتمال**: مكتمل بنسبة 90%
- **المشاكل**:
  - بعض المشاكل في حساب الخصومات المتداخلة
  - عدم تحديث الإجمالي بشكل صحيح في بعض الحالات

### 4. تكامل مع نظام المخزون

- **الوصف**: تم تحسين التكامل مع نظام المخزون:
  - التحقق من توفر المخزون عند الإضافة
  - تحديث المخزون المتاح في الوقت الفعلي
- **الملفات المعدلة**:
  - Controller: استخدام `getAvailableQuantityForOnline()`
  - Model (Cart): تعديل `hasStock()` لدعم الوحدات
- **حالة الاكتمال**: مكتمل بنسبة 80%
- **المشاكل**:
  - عدم تحديث المخزون في الوقت الفعلي
  - مشاكل في التعامل مع المخزون السالب

## المشاكل والتحديات

### 1. مشاكل في الأداء

- **الوصف**: بطء في تحميل وتحديث السلة بسبب:
  - استعلامات متعددة لقاعدة البيانات
  - حسابات معقدة للأسعار والمخزون
- **الحلول المقترحة**:
  - تحسين استعلامات قاعدة البيانات
  - إضافة تخزين مؤقت (Caching) للبيانات المتكررة
  - تحسين آلية تحديث السلة باستخدام Ajax

### 2. مشاكل في التكامل

- **الوصف**: مشاكل في التكامل مع الأنظمة الأخرى:
  - عدم تكامل كامل مع نظام المخزون
  - مشاكل في التكامل مع نظام الحسابات
  - عدم تحديث القيود المحاسبية عند تغيير السلة
- **الحلول المقترحة**:
  - تحسين التكامل مع نظام المخزون
  - إضافة طبقة وسيطة للتكامل مع نظام الحسابات
  - تأجيل إنشاء القيود المحاسبية حتى إتمام الطلب

### 3. مشاكل في واجهة المستخدم

- **الوصف**: بعض المشاكل في تجربة المستخدم:
  - عدم وضوح الخصومات والعروض
  - صعوبة تحديث الكميات
  - مشاكل في العرض على الأجهزة المحمولة
- **الحلول المقترحة**:
  - تحسين عرض الخصومات والعروض
  - تبسيط عملية تحديث الكميات
  - تحسين التجاوب مع الأجهزة المحمولة

## الاحتياجات والتحسينات

### 1. تحسينات ضرورية

- تصحيح مشاكل إزالة الباقات من السلة
- تحسين أداء السلة وتقليل وقت التحميل
- تحسين التكامل مع نظام المخزون

### 2. تحسينات مهمة

- تحسين عرض الخصومات والعروض
- تحسين آلية تحديث الكميات
- تحسين التجاوب مع الأجهزة المحمولة

### 3. تحسينات إضافية

- إضافة ميزة "حفظ للاحقاً"
- إضافة ميزة "المنتجات المقترحة" في السلة
- تحسين عملية استرداد السلة المهجورة

## الخلاصة

سلة التسوق تحتوي على تعديلات وإضافات كبيرة مقارنة بالنسخة الأصلية من OpenCart، خاصة في دعم الوحدات المتعددة، الباقات والعروض، ونظام الأسعار المتقدم. هناك بعض المشاكل التي تحتاج إلى معالجة، خاصة في الأداء والتكامل مع الأنظمة الأخرى، لكن بشكل عام السلة تعمل بشكل جيد وتوفر وظائف متقدمة للمتجر الإلكتروني.
