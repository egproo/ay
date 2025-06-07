# دليل شامل لمشروع ERP/Ecommerce للمطورين

## 1. نظرة عامة على المشروع

### 1.1 مقدمة
هذا المشروع هو نظام ERP/Ecommerce متكامل مبني على OpenCart 3.0.3.7، تم تطويره ليكون بديلاً لأنظمة مثل Odoo وWooCommerce، مع التركيز على احتياجات الشركات التجارية في السوق المصري. المشروع مكتمل بنسبة 75-80% ويحتاج إلى إصلاحات وتحسينات محددة للوصول إلى نظام متكامل بنسبة 100%.

### 1.2 التغييرات الرئيسية على OpenCart الأصلي
- تغيير مجلد `admin` إلى `dashboard`
- إضافة نظام محاسبي متكامل مع دعم للمتوسط المرجح للتكلفة والجرد المستمر
- إضافة نظام مخزون متطور مع دعم تعدد الوحدات للمنتج الواحد
- إضافة مركز إشعارات متقدم
- إضافة نظام مستندات وأرشفة إلكترونية
- إضافة محرر سير عمل مرئي (مشابه لـ n8n)
- إضافة نظام تواصل داخلي

### 1.3 هيكل المشروع
المشروع يتبع نمط MVC (Model-View-Controller) مع التنظيم التالي:
- **Models**: تتعامل مع قاعدة البيانات وتنفذ منطق الأعمال
- **Views**: ملفات Twig تعرض واجهة المستخدم
- **Controllers**: تتحكم في تدفق البيانات بين النماذج والعروض
- **Language Files**: ملفات اللغة لدعم تعدد اللغات

### 1.4 هيكل قاعدة البيانات
تم تعديل قاعدة البيانات بشكل كبير لدعم الوظائف الجديدة:
- **جداول المحاسبة**: `cod_accounts`, `cod_account_description`, `cod_journal_entry`, `cod_journal_entry_line`
- **جداول المخزون**: `cod_branch_inventory_snapshot`, `cod_inventory_movement`, `cod_product_unit`
- **جداول الإشعارات والمستندات**: `cod_notification`, `cod_document`, `cod_document_type`
- **جداول الفروع والمستودعات**: `cod_branch`, `cod_warehouse`, `cod_warehouse_product`

## 2. المشاكل الرئيسية والحلول المقترحة

### 2.1 مشاكل التكامل بين المخزون والمحاسبة

#### المشاكل:
1. **حساب المتوسط المرجح للتكلفة غير دقيق**
   - الدالة `calculateWeightedAverageCost` في `model/catalog/product.php` تحتوي على أخطاء في الحساب
   - لا يتم التعامل بشكل صحيح مع حالات الخطأ والقيم السالبة
   - عدم اتساق في تطبيق المتوسط المرجح عبر النظام

2. **عدم اتساق في استدعاء دوال المحاسبة**
   - استدعاء مباشر لدوال المحاسبة من مختلف أجزاء النظام
   - اختلاف في طريقة استدعاء الدوال بين المبيعات والمشتريات والمخزون
   - عدم وجود طبقة وسيطة للتكامل

3. **عدم وجود معرف موحد يربط بين حركة المخزون والقيود المحاسبية**
   - صعوبة في تتبع العلاقة بين حركة المخزون والقيود المحاسبية
   - عدم وجود حقول ربط في جداول قاعدة البيانات
   - صعوبة في التدقيق والمراجعة

#### الحلول:
1. **إصلاح آلية حساب المتوسط المرجح للتكلفة**
   ```php
   // تعديل الدالة في model/catalog/product.php
   public function calculateWeightedAverageCost($product_id, $quantity, $cost) {
       // الحصول على المخزون الحالي والتكلفة
       $current_stock = $this->getCurrentStock($product_id);
       $current_cost = $this->getCurrentCost($product_id);
       
       // التحقق من القيم السالبة والصفرية
       if ($current_stock <= 0 && $quantity <= 0) {
           return $cost;
       }
       
       if ($current_stock <= 0) {
           return $cost;
       }
       
       // حساب المتوسط المرجح
       $new_cost = (($current_stock * $current_cost) + ($quantity * $cost)) / ($current_stock + $quantity);
       
       // التحقق من صحة النتيجة
       if ($new_cost <= 0 || is_nan($new_cost)) {
           return $current_cost > 0 ? $current_cost : $cost;
       }
       
       return $new_cost;
   }
   ```

2. **إنشاء طبقة وسيطة للتكامل بين المخزون والمحاسبة**
   ```php
   // إنشاء ملف جديد: model/integration/inventory_accounting.php
   class ModelIntegrationInventoryAccounting extends Model {
       // دالة لإنشاء قيود محاسبية لحركة المخزون
       public function createInventoryMovementEntry($movement_id, $movement_type, $product_id, $quantity, $cost) {
           // التحقق من البيانات
           if (empty($movement_id) || empty($product_id)) {
               return false;
           }
           
           // الحصول على معلومات المنتج والحسابات
           $product_info = $this->model_catalog_product->getProduct($product_id);
           $accounts = $this->getInventoryAccounts($product_info['category_id']);
           
           // إنشاء قيد محاسبي حسب نوع الحركة
           switch ($movement_type) {
               case 'purchase':
                   return $this->createPurchaseEntry($movement_id, $accounts, $quantity, $cost);
               case 'sale':
                   return $this->createSaleEntry($movement_id, $accounts, $quantity, $cost);
               case 'transfer':
                   return $this->createTransferEntry($movement_id, $accounts, $quantity, $cost);
               default:
                   return false;
           }
       }
       
       // دوال مساعدة أخرى...
   }
   ```

3. **إضافة معرف موحد يربط بين حركة المخزون والقيود المحاسبية**
   ```sql
   -- تعديل جدول حركة المخزون
   ALTER TABLE `cod_inventory_movement` ADD COLUMN `journal_entry_id` INT(11) NULL;
   
   -- تعديل جدول قيود اليومية
   ALTER TABLE `cod_journal_entry` ADD COLUMN `reference_type` VARCHAR(50) NULL;
   ALTER TABLE `cod_journal_entry` ADD COLUMN `reference_id` INT(11) NULL;
   
   -- إضافة فهرس
   ALTER TABLE `cod_journal_entry` ADD INDEX `idx_reference` (`reference_type`, `reference_id`);
   ```

### 2.2 مشاكل أداء قاعدة البيانات

#### المشاكل:
1. **استعلامات غير محسنة**
   - استعلامات معقدة وغير فعالة في نماذج المخزون والمحاسبة
   - استخدام استعلامات متكررة بدلاً من استعلامات مجمعة
   - عدم استخدام الفهارس بشكل فعال

2. **نقص في الفهارس المناسبة**
   - عدم وجود فهارس للحقول المستخدمة في شروط البحث
   - عدم وجود فهارس مركبة للاستعلامات المعقدة
   - وجود فهارس غير ضرورية تبطئ عمليات الإدراج والتحديث

3. **تراكم البيانات غير الضرورية**
   - عدم وجود آلية لأرشفة البيانات القديمة
   - تراكم البيانات المؤقتة والسجلات
   - عدم تنظيف الجداول المؤقتة

#### الحلول:
1. **تحسين الاستعلامات**
   ```php
   // مثال لتحسين استعلام في model/accounts/journal.php
   // قبل التحسين
   public function getJournalEntries($data = array()) {
       $sql = "SELECT * FROM " . DB_PREFIX . "cod_journal_entry je";
       
       // شروط البحث...
       
       $query = $this->db->query($sql);
       
       $entries = $query->rows;
       
       // استعلام منفصل لكل قيد للحصول على التفاصيل
       foreach ($entries as &$entry) {
           $entry['lines'] = $this->getJournalEntryLines($entry['journal_entry_id']);
       }
       
       return $entries;
   }
   
   // بعد التحسين
   public function getJournalEntries($data = array()) {
       $sql = "SELECT je.*, jel.journal_entry_line_id, jel.account_id, jel.debit, jel.credit, jel.description 
               FROM " . DB_PREFIX . "cod_journal_entry je 
               LEFT JOIN " . DB_PREFIX . "cod_journal_entry_line jel 
               ON je.journal_entry_id = jel.journal_entry_id";
       
       // شروط البحث...
       
       $query = $this->db->query($sql);
       
       // تنظيم النتائج
       $entries = array();
       foreach ($query->rows as $row) {
           if (!isset($entries[$row['journal_entry_id']])) {
               $entries[$row['journal_entry_id']] = array(
                   'journal_entry_id' => $row['journal_entry_id'],
                   'date' => $row['date'],
                   'reference' => $row['reference'],
                   'description' => $row['description'],
                   'lines' => array()
               );
           }
           
           if ($row['journal_entry_line_id']) {
               $entries[$row['journal_entry_id']]['lines'][] = array(
                   'journal_entry_line_id' => $row['journal_entry_line_id'],
                   'account_id' => $row['account_id'],
                   'debit' => $row['debit'],
                   'credit' => $row['credit'],
                   'description' => $row['description']
               );
           }
       }
       
       return array_values($entries);
   }
   ```

2. **إضافة الفهارس المناسبة**
   ```sql
   -- فهارس لجداول المخزون
   ALTER TABLE `cod_inventory_movement` ADD INDEX `idx_product_date` (`product_id`, `date`);
   ALTER TABLE `cod_inventory_movement` ADD INDEX `idx_branch_product` (`branch_id`, `product_id`);
   
   -- فهارس لجداول المحاسبة
   ALTER TABLE `cod_journal_entry` ADD INDEX `idx_date` (`date`);
   ALTER TABLE `cod_journal_entry_line` ADD INDEX `idx_account` (`account_id`);
   
   -- فهارس مركبة
   ALTER TABLE `cod_journal_entry_line` ADD INDEX `idx_entry_account` (`journal_entry_id`, `account_id`);
   ```

3. **تنظيف البيانات غير الضرورية**
   ```php
   // إضافة دالة لأرشفة البيانات القديمة في model/tool/maintenance.php
   public function archiveOldData($months = 12) {
       // أرشفة قيود اليومية القديمة
       $this->archiveJournalEntries($months);
       
       // أرشفة حركة المخزون القديمة
       $this->archiveInventoryMovements($months);
       
       // أرشفة السجلات القديمة
       $this->archiveLogs($months);
       
       // تنظيف الجداول المؤقتة
       $this->cleanTemporaryTables();
   }
   
   // دوال مساعدة...
   ```

### 2.3 مشاكل ترحيل القيود المحاسبية

#### المشاكل:
1. **بطء في ترحيل القيود**
   - ترحيل القيود يتم بشكل متسلسل
   - عدم استخدام المعالجة المتوازية
   - عدم تقسيم عملية الترحيل إلى مجموعات صغيرة

2. **عدم وجود آلية للتراجع عن الترحيل**
   - لا يمكن التراجع عن ترحيل القيود بشكل آمن
   - عدم وجود سجل للتراجع عن الترحيل
   - عدم وجود صلاحيات للتراجع عن الترحيل

3. **واجهة مستخدم غير بديهية**
   - عدم وجود مؤشر تقدم لعملية الترحيل
   - رسائل خطأ غير واضحة
   - محدودية في خيارات التصفية والتحديد

#### الحلول:
1. **تحسين أداء ترحيل القيود**
   ```php
   // تعديل دالة ترحيل القيود في model/accounts/journal.php
   public function postJournalEntries($journal_entry_ids) {
       // التحقق من المدخلات
       if (empty($journal_entry_ids)) {
           return false;
       }
       
       // تقسيم القيود إلى مجموعات صغيرة
       $chunks = array_chunk($journal_entry_ids, 100);
       
       $success = true;
       
       foreach ($chunks as $chunk) {
           // بدء المعاملة
           $this->db->query("START TRANSACTION");
           
           try {
               // ترحيل كل قيد في المجموعة
               foreach ($chunk as $journal_entry_id) {
                   $this->postJournalEntry($journal_entry_id);
               }
               
               // تأكيد المعاملة
               $this->db->query("COMMIT");
           } catch (Exception $e) {
               // التراجع عن المعاملة في حالة الخطأ
               $this->db->query("ROLLBACK");
               $success = false;
               $this->log->write("Error posting journal entries: " . $e->getMessage());
           }
       }
       
       return $success;
   }
   
   // دالة مساعدة لترحيل قيد واحد
   private function postJournalEntry($journal_entry_id) {
       // التحقق من توازن القيد
       if (!$this->isBalanced($journal_entry_id)) {
           throw new Exception("Journal entry is not balanced");
       }
       
       // تحديث حالة القيد
       $this->db->query("UPDATE " . DB_PREFIX . "cod_journal_entry SET posted = 1, posted_date = NOW() WHERE journal_entry_id = '" . (int)$journal_entry_id . "'");
       
       // تحديث أرصدة الحسابات
       $this->updateAccountBalances($journal_entry_id);
       
       // تسجيل العملية
       $this->logActivity("Journal entry posted", $journal_entry_id);
   }
   ```

2. **إضافة آلية للتراجع عن الترحيل**
   ```php
   // إضافة دالة للتراجع عن الترحيل في model/accounts/journal.php
   public function reversePosting($journal_entry_id) {
       // التحقق من الصلاحيات
       if (!$this->user->hasPermission('modify', 'accounts/journal')) {
           return false;
       }
       
       // التحقق من حالة القيد
       $journal_entry = $this->getJournalEntry($journal_entry_id);
       
       if (!$journal_entry || !$journal_entry['posted']) {
           return false;
       }
       
       // بدء المعاملة
       $this->db->query("START TRANSACTION");
       
       try {
           // إنشاء قيد عكسي
           $reverse_entry_id = $this->createReverseEntry($journal_entry_id);
           
           // تحديث حالة القيد الأصلي
           $this->db->query("UPDATE " . DB_PREFIX . "cod_journal_entry SET reversed = 1, reversed_date = NOW(), reversed_by = '" . (int)$this->user->getId() . "', reverse_entry_id = '" . (int)$reverse_entry_id . "' WHERE journal_entry_id = '" . (int)$journal_entry_id . "'");
           
           // تحديث أرصدة الحسابات
           $this->updateAccountBalances($reverse_entry_id);
           
           // تسجيل العملية
           $this->logActivity("Journal entry reversed", $journal_entry_id);
           
           // تأكيد المعاملة
           $this->db->query("COMMIT");
           
           return $reverse_entry_id;
       } catch (Exception $e) {
           // التراجع عن المعاملة في حالة الخطأ
           $this->db->query("ROLLBACK");
           $this->log->write("Error reversing journal entry: " . $e->getMessage());
           return false;
       }
   }
   
   // دالة مساعدة لإنشاء قيد عكسي
   private function createReverseEntry($journal_entry_id) {
       // الحصول على بيانات القيد الأصلي
       $journal_entry = $this->getJournalEntry($journal_entry_id);
       $lines = $this->getJournalEntryLines($journal_entry_id);
       
       // إنشاء قيد جديد
       $data = array(
           'date' => date('Y-m-d'),
           'reference' => 'REV-' . $journal_entry['reference'],
           'description' => 'Reversal of ' . $journal_entry['description'],
           'posted' => 1,
           'posted_date' => date('Y-m-d H:i:s'),
           'reference_type' => 'reversal',
           'reference_id' => $journal_entry_id
       );
       
       $reverse_entry_id = $this->addJournalEntry($data);
       
       // إضافة سطور القيد العكسي
       foreach ($lines as $line) {
           $line_data = array(
               'journal_entry_id' => $reverse_entry_id,
               'account_id' => $line['account_id'],
               'debit' => $line['credit'],
               'credit' => $line['debit'],
               'description' => 'Reversal of ' . $line['description']
           );
           
           $this->addJournalEntryLine($line_data);
       }
       
       return $reverse_entry_id;
   }
   ```

3. **تحسين واجهة المستخدم لترحيل القيود**
   ```javascript
   // إضافة كود JavaScript في view/template/accounts/journal_list.twig
   $(document).ready(function() {
       // إضافة مؤشر تقدم
       function showProgress(message) {
           $('#progress-container').show();
           $('#progress-message').text(message);
       }
       
       function hideProgress() {
           $('#progress-container').hide();
       }
       
       // ترحيل القيود المحددة
       $('#button-post').on('click', function() {
           var selected = $('input[name^="selected"]:checked');
           
           if (selected.length === 0) {
               alert('{{ text_select_entries }}');
               return;
           }
           
           if (!confirm('{{ text_confirm_post }}')) {
               return;
           }
           
           var journal_entry_ids = [];
           
           selected.each(function() {
               journal_entry_ids.push($(this).val());
           });
           
           showProgress('{{ text_posting }}');
           
           $.ajax({
               url: 'index.php?route=accounts/journal/post&user_token={{ user_token }}',
               type: 'POST',
               data: { journal_entry_ids: journal_entry_ids },
               dataType: 'json',
               success: function(json) {
                   hideProgress();
                   
                   if (json.success) {
                       alert(json.success);
                       location.reload();
                   }
                   
                   if (json.error) {
                       alert(json.error);
                   }
               },
               error: function(xhr, status, error) {
                   hideProgress();
                   alert('{{ text_error }}');
               }
           });
       });
       
       // التراجع عن ترحيل القيد
       $('.button-reverse').on('click', function() {
           var journal_entry_id = $(this).data('id');
           
           if (!confirm('{{ text_confirm_reverse }}')) {
               return;
           }
           
           showProgress('{{ text_reversing }}');
           
           $.ajax({
               url: 'index.php?route=accounts/journal/reverse&user_token={{ user_token }}',
               type: 'POST',
               data: { journal_entry_id: journal_entry_id },
               dataType: 'json',
               success: function(json) {
                   hideProgress();
                   
                   if (json.success) {
                       alert(json.success);
                       location.reload();
                   }
                   
                   if (json.error) {
                       alert(json.error);
                   }
               },
               error: function(xhr, status, error) {
                   hideProgress();
                   alert('{{ text_error }}');
               }
           });
       });
   });
   ```

## 3. خطة التنفيذ

### 3.1 المرحلة 1: تحسينات حرجة (1-2 أسابيع)

#### 3.1.1 تحسين تكامل المخزون مع المحاسبة
1. إصلاح آلية حساب المتوسط المرجح للتكلفة
   - تعديل دالة `calculateWeightedAverageCost` في `model/catalog/product.php`
   - إضافة اختبارات للتأكد من دقة الحسابات
   - معالجة حالات الخطأ والقيم السالبة

2. إنشاء طبقة وسيطة للتكامل
   - إنشاء ملف `model/integration/inventory_accounting.php`
   - نقل منطق التكامل إلى الطبقة الوسيطة
   - تعديل جميع نقاط الاستدعاء لاستخدام الطبقة الوسيطة

3. إضافة معرف موحد يربط بين حركة المخزون والقيود المحاسبية
   - تعديل جداول قاعدة البيانات لإضافة حقول الربط
   - تعديل الدوال لاستخدام المعرف الموحد
   - إضافة وظائف للتتبع والاستعلام باستخدام المعرف

#### 3.1.2 تحسين أداء قاعدة البيانات
1. تحسين الاستعلامات
   - مراجعة وتحسين الاستعلامات في نماذج المخزون والمحاسبة
   - استخدام الاستعلامات المجمعة بدلاً من الاستعلامات المتكررة
   - تقليل عدد الاستعلامات في العمليات الشائعة

2. إضافة الفهارس المناسبة
   - تحليل الاستعلامات الأكثر استخداماً وإضافة فهارس لها
   - إضافة فهارس مركبة للاستعلامات المعقدة
   - إزالة الفهارس غير المستخدمة

3. تنظيف البيانات غير الضرورية
   - إضافة آلية لأرشفة البيانات القديمة
   - إضافة آلية لحذف البيانات المؤقتة
   - تحسين هيكل الجداول لتقليل التكرار

#### 3.1.3 إصلاح مشاكل ترحيل القيود المحاسبية
1. تحسين أداء ترحيل القيود
   - تعديل دالة `postJournalEntries` في `model/accounts/journal.php`
   - استخدام المعالجة المتوازية للقيود الكثيرة
   - تقسيم عملية الترحيل إلى مجموعات صغيرة

2. إضافة آلية للتراجع عن الترحيل
   - تصميم وتنفيذ آلية آمنة للتراجع عن الترحيل
   - إضافة سجل للتراجع عن الترحيل
   - إضافة صلاحيات للتراجع عن الترحيل

3. تحسين واجهة المستخدم لترحيل القيود
   - إضافة مؤشر تقدم لعملية الترحيل
   - تحسين رسائل الخطأ والنجاح
   - إضافة خيارات تصفية وتحديد للقيود

### 3.2 المرحلة 2: تحسينات مهمة (2-4 أسابيع)

#### 3.2.1 تحسين تكامل المبيعات والمشتريات مع المحاسبة
1. توحيد آلية إنشاء القيود المحاسبية
   - إنشاء طبقة وسيطة للتكامل بين المبيعات والمشتريات والمحاسبة
   - توحيد واجهة برمجة التطبيقات (API) للتكامل
   - تعديل جميع نقاط الاستدعاء لاستخدام الطبقة الوسيطة

2. إصلاح مشاكل معالجة الضرائب والخصومات
   - تعديل دوال حساب الضرائب والخصومات
   - توحيد آلية معالجة الضرائب والخصومات
   - إضافة اختبارات للتأكد من دقة الحسابات

3. تحسين ربط المدفوعات والتحصيلات بالفواتير
   - تعديل واجهة المستخدم لتسهيل ربط المدفوعات والتحصيلات بالفواتير
   - إضافة دعم لسداد فواتير متعددة بدفعة واحدة
   - تحسين آلية تتبع المدفوعات الجزئية

#### 3.2.2 تحسين نظام التدقيق
1. ضمان تسجيل جميع العمليات
   - تحديد جميع نقاط التسجيل في النظام
   - إضافة تسجيل للعمليات غير المسجلة
   - توحيد آلية التسجيل عبر النظام

2. تحسين أداء سجلات التدقيق
   - تحسين استعلامات سجلات التدقيق
   - إضافة فهارس مناسبة لجداول السجلات
   - إضافة آلية لأرشفة السجلات القديمة

3. إضافة المزيد من التفاصيل في سجلات التدقيق
   - تسجيل القيم القديمة والجديدة في التغييرات
   - تسجيل معلومات إضافية عن المستخدم والجلسة
   - إضافة تصنيف للعمليات المسجلة

#### 3.2.3 توحيد وتنظيم هيكل المشروع
1. دمج مجلدي `accounting` و `accounts`
   - تحليل الوظائف في كلا المجلدين
   - نقل الوظائف المتشابهة إلى مجلد واحد
   - تحديث جميع الاستدعاءات والمراجع

2. توحيد تسمية الملفات والدوال
   - وضع معايير موحدة لتسمية الملفات والدوال
   - تعديل أسماء الملفات والدوال لتتوافق مع المعايير
   - توثيق المعايير للاستخدام المستقبلي

3. إزالة الملفات المكررة وغير المستخدمة
   - تحديد الملفات المكررة وغير المستخدمة
   - نقل الوظائف المفيدة من الملفات المكررة
   - إزالة الملفات غير الضرورية

### 3.3 المرحلة 3: تحسينات إضافية (4-6 أسابيع)

#### 3.3.1 تحسين واجهة المستخدم
1. جعلها أكثر بداهة واتساقاً
   - توحيد تصميم الواجهات عبر النظام
   - تحسين تنظيم العناصر في الشاشات المعقدة
   - إضافة تلميحات ومساعدة للمستخدم

2. تحسين تجربة المستخدم في الأقسام المعقدة
   - تبسيط سير العمل في الأقسام المعقدة
   - إضافة معالجات (wizards) للعمليات المعقدة
   - تحسين التنقل بين الشاشات المترابطة

3. إضافة المزيد من التخصيص
   - إضافة خيارات لتخصيص الشاشات والتقارير
   - إضافة إمكانية حفظ التفضيلات للمستخدم
   - إضافة لوحات معلومات قابلة للتخصيص

#### 3.3.2 تحسين نظام الإشعارات
1. توحيد استدعاء نظام الإشعارات
   - إنشاء واجهة برمجة تطبيقات (API) موحدة للإشعارات
   - تعديل جميع نقاط الاستدعاء لاستخدام الواجهة الموحدة
   - إضافة فئات وأولويات للإشعارات

2. إضافة إعدادات لتفضيلات الإشعارات
   - إضافة شاشة لإدارة تفضيلات الإشعارات
   - إضافة خيارات للإشعارات حسب النوع والأولوية
   - إضافة دعم للإشعارات عبر البريد الإلكتروني

3. تحسين عرض الإشعارات
   - تحسين تصميم مركز الإشعارات
   - إضافة تصفية وتجميع للإشعارات
   - إضافة إمكانية تمييز الإشعارات كمقروءة/غير مقروءة

#### 3.3.3 استكمال الوحدات غير المكتملة
1. استكمال نظام المستندات
   - استكمال واجهة المستخدم لإدارة المستندات
   - إضافة دعم لأنواع مختلفة من المستندات
   - إضافة إمكانية البحث في محتوى المستندات

2. استكمال محرر سير العمل
   - استكمال واجهة المستخدم لمحرر سير العمل
   - إضافة المزيد من العناصر والإجراءات
   - إضافة إمكانية تشغيل سير العمل تلقائياً

3. استكمال نظام التواصل الداخلي
   - استكمال واجهة المستخدم للتواصل الداخلي
   - إضافة دعم للمحادثات الجماعية
   - إضافة إمكانية مشاركة الملفات والروابط

### 3.4 المرحلة 4: مراجعة وتوثيق (2-3 أسابيع)

#### 3.4.1 اختبار شامل للنظام
1. إعداد خطة اختبار شاملة
   - تحديد حالات الاختبار لجميع الوظائف
   - إعداد بيانات اختبار واقعية
   - تحديد معايير النجاح والفشل

2. تنفيذ الاختبارات
   - اختبار الوظائف الأساسية
   - اختبار التكامل بين الوحدات
   - اختبار الأداء والتحمل

3. توثيق نتائج الاختبارات
   - توثيق الأخطاء والمشاكل
   - توثيق الحلول والتحسينات
   - إعداد تقرير نهائي للاختبارات

#### 3.4.2 إصلاح الأخطاء المتبقية
1. تحديد وتصنيف الأخطاء المتبقية
   - تصنيف الأخطاء حسب الخطورة والأولوية
   - تحديد الأخطاء التي تحتاج إلى إصلاح فوري
   - تحديد الأخطاء التي يمكن تأجيلها

2. إصلاح الأخطاء ذات الأولوية العالية
   - إصلاح الأخطاء التي تؤثر على وظائف أساسية
   - إصلاح الأخطاء التي تؤثر على دقة البيانات
   - إصلاح الأخطاء التي تؤثر على أمان النظام

3. إعداد خطة لإصلاح الأخطاء المتبقية
   - تحديد الموارد والوقت اللازم
   - تحديد الأولويات والجدول الزمني
   - تحديد المسؤوليات والمهام

#### 3.4.3 توثيق النظام
1. إعداد دليل المستخدم
   - توثيق جميع الوظائف والشاشات
   - إضافة أمثلة وحالات استخدام
   - إضافة صور توضيحية

2. إعداد دليل المطور
   - توثيق هيكل المشروع وقاعدة البيانات
   - توثيق واجهات برمجة التطبيقات (APIs)
   - توثيق آليات التكامل والتوسعة

3. إعداد دليل الإدارة
   - توثيق إعدادات النظام
   - توثيق إجراءات الصيانة والنسخ الاحتياطي
   - توثيق إجراءات الأمان والتدقيق

## 4. الخلاصة

هذا المشروع هو نظام ERP/Ecommerce متكامل مبني على OpenCart 3.0.3.7، مكتمل بنسبة 75-80%. المشاكل الرئيسية تتركز في التكامل بين المخزون والمحاسبة، وأداء قاعدة البيانات، وترحيل القيود المحاسبية. معالجة هذه المشاكل وفقاً لخطة التنفيذ المقترحة ستؤدي إلى نظام متكامل بنسبة 100% يمكن استخدامه في بيئة الإنتاج.

الخطوات الأولى التي يجب اتخاذها هي:
1. إصلاح آلية حساب المتوسط المرجح للتكلفة
2. إنشاء طبقة وسيطة للتكامل بين المخزون والمحاسبة
3. تحسين أداء قاعدة البيانات
4. إصلاح مشاكل ترحيل القيود المحاسبية

بعد الانتهاء من هذه الخطوات، يمكن الانتقال إلى التحسينات الأخرى وفقاً للأولويات المحددة في خطة التنفيذ.
