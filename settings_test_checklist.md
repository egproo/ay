# قائمة اختبار شاشة الإعدادات الأساسية

## 🧪 اختبارات سريعة للتحقق من عمل الإعدادات

### 1. اختبار تحميل الصفحة
- [ ] الوصول إلى `https://app.test/dashboard/index.php?route=setting/setting`
- [ ] التأكد من تحميل الصفحة بدون أخطاء
- [ ] التأكد من ظهور التبويبات الثلاثة (عام، المحاسبة، ETA)
- [ ] التأكد من تحميل النصوص العربية بشكل صحيح

### 2. اختبار تبويب المحاسبة
- [ ] النقر على تبويب "المحاسبة"
- [ ] التأكد من ظهور رسالة التحذير
- [ ] التأكد من عمل Select2 في حقول الحسابات
- [ ] التأكد من ظهور النصوص العربية في جميع الحقول

### 3. اختبار التحقق من البيانات
- [ ] ترك الحقول المطلوبة فارغة والضغط على حفظ
- [ ] التأكد من ظهور رسائل الخطأ باللغة العربية
- [ ] التأكد من تحويل التركيز إلى تبويب المحاسبة عند وجود أخطاء
- [ ] التأكد من تمييز الحقول الخاطئة بلون أحمر

### 4. اختبار إعدادات ETA
- [ ] النقر على تبويب "ETA"
- [ ] اختيار وضع "الإنتاج"
- [ ] ترك الحقول المطلوبة فارغة والضغط على حفظ
- [ ] التأكد من ظهور رسائل خطأ ETA

### 5. اختبار الحفظ الناجح
- [ ] ملء جميع الحقول المطلوبة
- [ ] الضغط على زر الحفظ
- [ ] التأكد من ظهور رسالة النجاح
- [ ] التأكد من حفظ البيانات في قاعدة البيانات

## 🔧 إصلاح المشاكل المحتملة

### إذا لم تظهر النصوص العربية:
```bash
# التأكد من وجود ملف اللغة
ls -la project/dashboard/language/ar/setting/setting.php

# التأكد من صلاحيات الملف
chmod 644 project/dashboard/language/ar/setting/setting.php
```

### إذا لم يعمل Select2:
1. التأكد من تحميل مكتبة Select2 في header.twig
2. التأكد من عدم وجود أخطاء JavaScript في console المتصفح

### إذا لم تعمل دالة التحقق:
1. التأكد من وجود نموذج `accounts/chartaccount`
2. التأكد من وجود دالة `getAccountByCode()` في النموذج

## 📝 استعلامات قاعدة البيانات للاختبار

### التحقق من حفظ الإعدادات:
```sql
SELECT * FROM cod_setting WHERE code = 'config' AND key LIKE 'config_accounting_%';
```

### التحقق من إعدادات ETA:
```sql
SELECT * FROM cod_setting WHERE code = 'config' AND key LIKE 'config_eta_%';
```

### التحقق من وجود دليل الحسابات:
```sql
SELECT COUNT(*) FROM cod_chart_account;
```

## 🚨 مشاكل محتملة وحلولها

### المشكلة: رسالة خطأ "النموذج غير موجود"
**الحل:**
```php
// التأكد من وجود الملف
project/dashboard/model/accounts/chartaccount.php

// التأكد من وجود الدالة
public function getAccountByCode($code) {
    // كود الدالة
}
```

### المشكلة: لا تظهر الحسابات في Select2
**الحل:**
```php
// في الكنترولر، التأكد من تمرير البيانات
$data['accounts_list'] = $this->model_accounts_chartaccount->getAllAccountsList();
```

### المشكلة: أخطاء JavaScript
**الحل:**
1. فتح Developer Tools في المتصفح
2. التحقق من تبويب Console للأخطاء
3. التأكد من تحميل jQuery و Select2

## ✅ معايير النجاح

### الحد الأدنى للنجاح:
- [ ] تحميل الصفحة بدون أخطاء
- [ ] ظهور النصوص العربية
- [ ] عمل التحقق من البيانات الأساسي
- [ ] حفظ الإعدادات بنجاح

### النجاح الكامل:
- [ ] جميع معايير الحد الأدنى
- [ ] عمل Select2 بشكل مثالي
- [ ] عمل جميع التحققات المتقدمة
- [ ] تصميم متجاوب وجميل
- [ ] رسائل خطأ واضحة ومفيدة

## 📞 في حالة الحاجة لمساعدة

### معلومات مهمة للدعم:
- إصدار OpenCart: 3.0.3.7
- المتصفح المستخدم: [اذكر المتصفح]
- رسالة الخطأ الكاملة: [انسخ الرسالة]
- خطوات إعادة إنتاج المشكلة: [اذكر الخطوات]

### ملفات السجلات المهمة:
- `project/system/storage/logs/error.log`
- Developer Tools Console في المتصفح
- Network tab في Developer Tools

---

**ملاحظة:** هذه القائمة تغطي الاختبارات الأساسية. للاختبار الشامل، يُنصح بإجراء اختبارات إضافية على التكامل مع الأنظمة الأخرى.
