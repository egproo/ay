# تشغيل النظام محلياً باستخدام Laragon

## المتطلبات

1. تثبيت [Laragon](https://laragon.org/download/) (النسخة الكاملة)
2. تثبيت PHP 7.4 أو أعلى
3. تثبيت MySQL 5.7 أو أعلى
4. تثبيت Git (يأتي مع Laragon)

## خطوات الإعداد

### 1. إعداد Laragon

1. قم بتثبيت Laragon في المسار الافتراضي `C:\laragon`
2. تأكد من تفعيل خدمات Apache و MySQL
3. قم بتشغيل Laragon وتأكد من أن الخدمات تعمل بشكل صحيح

### 2. إعداد المشروع

1. انسخ مجلد المشروع إلى مجلد `C:\laragon\www\app`
2. قم بإنشاء قاعدة بيانات جديدة باسم `app_db` من خلال phpMyAdmin (يمكن الوصول إليها من خلال Laragon -> Menu -> MySQL -> phpMyAdmin)
3. قم باستيراد ملف قاعدة البيانات `db.sql` إلى قاعدة البيانات الجديدة

### 3. إعداد ملفات التكوين

1. قم بنسخ ملف `config-local.php` إلى `config.php` في المجلد الرئيسي للمشروع:
   ```
   copy C:\laragon\www\app\config-local.php C:\laragon\www\app\config.php
   ```

2. قم بنسخ ملف `dashboard/config-local.php` إلى `dashboard/config.php`:
   ```
   copy C:\laragon\www\app\dashboard\config-local.php C:\laragon\www\app\dashboard\config.php
   ```

3. قم بإنشاء مجلد التخزين إذا لم يكن موجوداً:
   ```
   mkdir C:\laragon\www\app\storage
   mkdir C:\laragon\www\app\storage\cache
   mkdir C:\laragon\www\app\storage\download
   mkdir C:\laragon\www\app\storage\logs
   mkdir C:\laragon\www\app\storage\modification
   mkdir C:\laragon\www\app\storage\session
   mkdir C:\laragon\www\app\storage\upload
   ```

4. قم بتعيين صلاحيات الكتابة لمجلد التخزين:
   ```
   icacls C:\laragon\www\app\storage /grant Everyone:(OI)(CI)F /T
   ```

### 4. إعداد الاستضافة المحلية

1. افتح Laragon وانقر على Menu -> Preferences
2. في تبويب "General"، تأكد من أن "Auto virtual hosts" مفعل
3. في حقل "Document Root"، تأكد من أنه يشير إلى `C:\laragon\www`
4. في حقل "Hostname pattern"، تأكد من أنه `{name}.test`
5. انقر على "OK" لحفظ الإعدادات
6. أعد تشغيل Laragon

### 5. الوصول إلى النظام

1. افتح المتصفح وانتقل إلى `https://app.test` للوصول إلى واجهة المتجر
2. انتقل إلى `https://app.test/dashboard` للوصول إلى لوحة التحكم
3. استخدم بيانات الدخول الافتراضية:
   - اسم المستخدم: `admin`
   - كلمة المرور: `admin`

## ملاحظات هامة

1. إذا واجهت مشكلة في الاتصال بقاعدة البيانات، تأكد من صحة بيانات الاتصال في ملفات `config.php`
2. إذا واجهت مشكلة في الوصول إلى النظام، تأكد من تفعيل SSL في Laragon
3. للتأكد من تفعيل SSL، انقر على Menu -> Apache -> SSL -> Enable
4. قد تحتاج إلى تثبيت شهادة SSL محلية للوصول إلى النظام عبر HTTPS

## استكشاف الأخطاء وإصلاحها

1. **مشكلة: لا يمكن الوصول إلى النظام**
   - تأكد من تشغيل خدمات Apache و MySQL في Laragon
   - تأكد من صحة مسار المشروع
   - تأكد من إضافة النطاق `app.test` إلى ملف hosts

2. **مشكلة: خطأ في الاتصال بقاعدة البيانات**
   - تأكد من إنشاء قاعدة البيانات `app_db`
   - تأكد من صحة بيانات الاتصال في ملفات `config.php`
   - تأكد من تشغيل خدمة MySQL

3. **مشكلة: صفحة بيضاء أو خطأ 500**
   - تحقق من سجلات الأخطاء في `C:\laragon\www\app\storage\logs`
   - تأكد من تعيين صلاحيات الكتابة لمجلد التخزين
   - تأكد من تثبيت جميع امتدادات PHP المطلوبة

4. **مشكلة: مشاكل في تحميل الصور أو الملفات**
   - تأكد من تعيين صلاحيات الكتابة لمجلد `image` و `storage/upload`
