<?php
class ControllerSettingSetting extends Controller {
	private $error = array();
/*
# تكوين إعدادات المحاسبة في نظام COD ERP متكامل

بعد مراجعة شاملة للمستندات المرفقة، سأقدم تحليلاً أكثر تفصيلاً وعملية لإعدادات المحاسبة في النظام.

## حسابات دليل الحسابات الرئيسية والترميز الحسابي

النظام يعتمد على شجرة حسابات متكاملة تتبع النظام المحاسبي للشركات التجارية. من خلال الإعدادات في تبويب المحاسبة نلاحظ:

### 1. الأصول المتداولة (تبدأ عادة بـ 1)

- **حسابات النقدية (11000-11999)**
  - حساب النقدية (11000): يُستخدم لتسجيل المبيعات النقدية من نقاط البيع
  - حساب البنك (11100): يسجل فيه الإيداعات والسحوبات البنكية
  - حساب النقدية الصغيرة (11900): للمصروفات النثرية اليومية

- **حسابات الذمم المدينة (12000-12999)**
  - حساب العملاء/المدينون (12000): يسجل فيه مبيعات الآجل والأقساط

- **حسابات المخزون (13000-13999)**
  - حساب المخزون (13000): يتأثر مباشرة بعمليات البيع والشراء
  - حساب المخزون في الطريق (13100): للبضائع المشحونة وغير المستلمة
  - حساب تسويات المخزون (13200): لتسجيل فروقات الجرد والتالف

### 2. الالتزامات (تبدأ عادة بـ 2)

- **الالتزامات المتداولة (21000-21999)**
  - حساب الموردين/الدائنون (21000): لتسجيل مشتريات الآجل
  - حساب ضريبة القيمة المضافة المستحقة (21100): للضرائب المستحقة للمصلحة

### 3. الإيرادات (تبدأ عادة بـ 4)

- **حسابات المبيعات (41000-41999)**
  - حساب المبيعات (41000): لتسجيل إيرادات المبيعات
  - حساب مردودات المبيعات (41100): لتسجيل المرتجعات
  - حساب خصم المبيعات (41200): للخصومات الممنوحة للعملاء

### 4. التكاليف والمصروفات (تبدأ عادة بـ 5)

- **تكلفة البضاعة المباعة (51000-51999)**
  - حساب تكلفة البضاعة المباعة (51000): يتأثر بحركات المخزون وطريقة المتوسط المرجح
  - حساب مشتريات البضائع (51100): لتسجيل المشتريات
  - حساب مردودات المشتريات (51200): لتسجيل المرتجعات للموردين

## القيود المحاسبية التلقائية في العمليات الرئيسية

### 1. عملية البيع (نقاط البيع POS)

عند إتمام عملية بيع من خلال نقطة البيع، يقوم النظام تلقائياً بإنشاء القيد التالي:

```
مدين: حساب النقدية/البطاقات/البنك (حسب طريقة الدفع) [بإجمالي قيمة الفاتورة]
  دائن: حساب المبيعات [بصافي قيمة المبيعات]
  دائن: حساب ضريبة القيمة المضافة المستحقة [بقيمة الضريبة]

مدين: حساب تكلفة البضاعة المباعة [بتكلفة البضاعة المباعة]
  دائن: حساب المخزون [بتكلفة البضاعة المباعة]
```

### 2. عملية الشراء واستلام البضائع

عند استلام البضائع من مورد، يتم عمل القيد التالي:

```
مدين: حساب المخزون [بتكلفة البضاعة المشتراة]
مدين: حساب ضريبة المشتريات [بقيمة الضريبة]
  دائن: حساب الموردين [بإجمالي قيمة الفاتورة]
```

### 3. تسويات المخزون (الجرد)

عند إجراء جرد وتسجيل فروقات:

```
// في حالة وجود زيادة في المخزون
مدين: حساب المخزون [بقيمة الزيادة]
  دائن: حساب تسويات المخزون [بقيمة الزيادة]

// في حالة وجود عجز في المخزون
مدين: حساب تسويات المخزون [بقيمة العجز]
  دائن: حساب المخزون [بقيمة العجز]
```

## الربط مع نظام ETA (الفوترة الإلكترونية)

نظام ETA مرتبط مباشرة بالنظام المحاسبي من خلال:

1. **حسابات الضرائب**: تسجيل ضريبة المبيعات والمشتريات تلقائياً
2. **رقم التسجيل الضريبي**: المستخدم في إصدار الفواتير الإلكترونية
3. **بيانات الممول**: المخزنة في إعدادات النظام وتُستخدم في الفواتير

## الإجراءات العملية لتكوين الإعدادات المحاسبية

1. **تحديد طبيعة النشاط التجاري** للمؤسسة وتصميم شجرة حسابات مناسبة
2. **تكوين دليل الحسابات** في النظام (جدول `cod_accounts`)
3. **ربط الحسابات بالإعدادات المحاسبية** المناسبة في تبويب المحاسبة:
   - حساب المبيعات
   - حساب تكلفة البضاعة المباعة
   - حساب المخزون
   - حساب النقدية
   - حساب البنك
   - حساب المدينين
   - حساب المصروفات العامة

4. **تكوين إعدادات الضرائب** المرتبطة بنظام ETA:
   - حساب ضريبة المبيعات
   - حساب ضريبة المشتريات
   - رقم التسجيل الضريبي
   - كود النشاط

5. **تكوين إعدادات المخزون**:
   - تأكيد اختيار طريقة المتوسط المرجح
   - تحديد حسابات تسويات المخزون

## الإعدادات المطلوبة في حالات خاصة

### 1. إعدادات الأقساط والبيع الآجل
تحتاج إلى تكوين:
- حسابات العملاء (الرئيسي وحسب الفئات)
- حساب الفوائد المكتسبة (إن وجدت)
- حساب الأقساط المستحقة

### 2. إعدادات مرتجعات المبيعات
تحتاج إلى تكوين:
- حساب مردودات المبيعات
- حساب مخصص المرتجعات (إن لزم)

### 3. إعدادات تسوية العملات
في حالة التعامل بعملات متعددة، تحتاج إلى:
- حساب فروق العملة
- حساب تقييم العملات الأجنبية

## توصيات لضمان دقة الإعدادات المحاسبية

1. **التأكد من ترقيم منطقي للحسابات** يتوافق مع المعايير المحاسبية المصرية
2. **تحديد طبيعة كل حساب** بشكل صحيح (مدين/دائن، أصل/التزام/إيراد/مصروف)
3. **اختبار القيود المحاسبية** التلقائية للتأكد من صحتها بإجراء اختبارات على كل نوع من العمليات
4. **مراجعة إعدادات الربط مع ETA** للتأكد من توافقها مع متطلبات مصلحة الضرائب
5. **تدريب المستخدمين** على فهم تأثير حركات المخزون والمبيعات على الحسابات

بهذه الإعدادات المتكاملة، يمكن للنظام أن يعمل بشكل متكامل ويوفر تقارير مالية دقيقة تعكس الواقع الفعلي للمؤسسة، ويسهل عملية الامتثال الضريبي من خلال نظام الفوترة الإلكترونية.

# استكمال تكوين إعدادات المحاسبة في نظام COD ERP المتكامل

## العلاقة بين إعدادات المحاسبة والتقارير المالية

تكوين الإعدادات المحاسبية بشكل صحيح له تأثير مباشر على دقة التقارير المالية التي ينتجها النظام:

### 1. القوائم المالية الأساسية
- **قائمة المركز المالي (الميزانية)**: تعتمد على دقة تصنيف الحسابات إلى أصول والتزامات وحقوق ملكية
- **قائمة الدخل (الأرباح والخسائر)**: تعتمد على ربط حسابات الإيرادات والمصروفات بشكل صحيح
- **قائمة التدفقات النقدية**: تعتمد على تتبع الحركات النقدية وتصنيفها بشكل صحيح

من واقع الجداول، نجد أن النظام يدعم هذه التقارير من خلال:
```
cod_accounts_balance_sheet
cod_accounts_income_statement
cod_accounts_cash_flow
```

### 2. التقارير التحليلية
- **تحليل الربحية**: يعتمد على ربط حسابات المبيعات وتكلفة المبيعات
- **تقارير المقارنة**: تعتمد على دقة تسجيل المعاملات في الفترات المختلفة
- **تحليل التكاليف**: يعتمد على تصنيف المصروفات وتوزيعها

## تكامل الإعدادات المحاسبية مع الوحدات الوظيفية المختلفة

### 1. التكامل مع نظام نقاط البيع (POS)
- **آلية عمل المناوبات (Shifts)**: من خلال جدول `cod_pos_shift` يتم تتبع المناوبات والنقدية المستلمة والمسلمة
- **المعاملات النقدية**: تسجل في جدول `cod_pos_transaction` وترتبط مباشرة بالقيود المحاسبية
- **حساب النقدية في الخزينة**: يتأثر مباشرة بحركات البيع في نقاط البيع

يجب تكوين:
- حساب نقدية لكل نقطة بيع
- حساب إيرادات منفصل لكل فرع (اختياري)
- حساب تكلفة مبيعات لكل فرع (اختياري)

### 2. التكامل مع نظام المخزون
من الجداول المتعلقة بالمخزون:
```
cod_inventory_valuation
cod_inventory_movement
cod_inventory_count
cod_inventory_transfer
```

وتحتاج إلى تكوين:
- **حسابات المخزون لكل فرع**: لتتبع قيمة المخزون في كل موقع
- **حسابات الفروقات المخزنية**: لتسجيل الزيادة والعجز في الجرد
- **حسابات تسويات التكلفة**: للتعديلات على تكلفة المخزون

### 3. التكامل مع إدارة المشتريات
من الجداول:
```
cod_purchase_order
cod_goods_receipt
cod_supplier_invoice
cod_purchase_matching
```

تحتاج إلى تكوين:
- **حساب الموردين الرئيسي**: وربما حسابات فرعية لكبار الموردين
- **حساب البضاعة قيد الاستلام**: للطلبيات التي صدرت ولم تستلم بعد
- **حساب مصاريف الشراء**: لتسجيل مصاريف الشحن والتأمين وغيرها

## الإدارة المالية والحوكمة المرتبطة بالإعدادات المحاسبية

من الجداول نلاحظ اهتمام النظام بالحوكمة والرقابة:
```
cod_audit_log
cod_internal_control
cod_governance_issue
cod_workflow_approval
```

لتفعيل الحوكمة المالية، يجب تكوين:

### 1. إعدادات الصلاحيات المالية
- تحديد المستخدمين المخولين بإنشاء وتعديل القيود المحاسبية
- تحديد حدود الاعتماد المالي لكل مستوى إداري
- تفعيل مسار الموافقات للعمليات المالية الهامة

### 2. إعدادات التدقيق والمراجعة
- تفعيل تسجيل جميع التغييرات على القيود المحاسبية
- تكوين إجراءات المراجعة الدورية للحسابات
- إعداد قواعد التنبيه عن الانحرافات المالية

## التخطيط المالي والموازنات

من جداول النظام:
```
cod_budget
cod_budget_line
cod_financial_forecast
```

يتطلب التخطيط المالي تكوين:

### 1. هيكل الموازنة
- موازنة المبيعات والإيرادات
- موازنة المشتريات والمصروفات
- موازنة الاستثمارات والمشاريع

### 2. إعدادات المقارنة والتحليل
- مقارنة الفعلي بالمخطط
- تحليل الانحرافات وأسبابها
- تقارير الأداء المالي

## خطوات عملية لتكوين النظام المحاسبي (مثال تطبيقي)

لنفترض شركة تجارية متوسطة الحجم تعمل في تجارة التجزئة:

### 1. تصميم شجرة الحسابات
```
10000 - الأصول
  11000 - النقدية والبنوك
    11100 - النقدية بالخزينة
    11200 - حسابات البنوك
  12000 - العملاء والمدينون
    12100 - عملاء عاديون
    12200 - عملاء بالتقسيط
  13000 - المخزون
    13100 - مخزون الفرع الرئيسي
    13200 - مخزون فرع المعادي
    13300 - مخزون فرع مدينة نصر

20000 - الالتزامات
  21000 - الموردون والدائنون
    21100 - موردون محليون
    21200 - موردون خارجيون
  22000 - الالتزامات الضريبية
    22100 - ضريبة القيمة المضافة
    22200 - ضرائب مرتبات

30000 - حقوق الملكية
  31000 - رأس المال
  32000 - الأرباح المحتجزة

40000 - الإيرادات
  41000 - إيرادات المبيعات
    41100 - مبيعات الفرع الرئيسي
    41200 - مبيعات فرع المعادي
  42000 - إيرادات أخرى
    42100 - فوائد دائنة

50000 - المصروفات
  51000 - تكلفة المبيعات
    51100 - تكلفة مبيعات الفرع الرئيسي
    51200 - تكلفة مبيعات فرع المعادي
  52000 - المصروفات التشغيلية
    52100 - رواتب وأجور
    52200 - إيجارات
```

### 2. تعريف الحسابات في النظام
- إدخال كل حساب في جدول `cod_accounts` مع تحديد طبيعته وعلاقته بالحسابات الأخرى

### 3. ربط الحسابات بإعدادات المحاسبة
- ربط حساب المبيعات: 41000
- ربط حساب تكلفة المبيعات: 51000
- ربط حساب المخزون: 13000
- ربط حساب النقدية: 11100
- ربط حساب البنوك: 11200
- ربط حساب العملاء: 12000
- ربط حساب الموردين: 21000
- ربط حساب ضريبة المبيعات: 22100

### 4. إعداد القوالب المحاسبية للعمليات الرئيسية
- قالب قيد البيع النقدي
- قالب قيد البيع الآجل
- قالب قيد استلام البضاعة
- قالب قيد سداد المورد

### 5. اختبار العمليات المحاسبية
- إجراء عملية بيع تجريبية والتحقق من القيد المحاسبي
- إجراء عملية شراء تجريبية والتحقق من القيد المحاسبي
- إجراء جرد مخزون تجريبي والتحقق من قيود التسوية

## تحديات شائعة وحلولها في تكوين الإعدادات المحاسبية

### 1. مشكلة: عدم توازن القيود المحاسبية التلقائية
**الحل**: مراجعة تكوين الحسابات وإعداد معادلات التحقق من توازن القيود قبل ترحيلها

### 2. مشكلة: صعوبة تتبع التكلفة بالمتوسط المرجح
**الحل**: إعداد جدول مساعد لتتبع حركات المخزون وتكلفتها، مثل الموجود في `cod_inventory_valuation`

### 3. مشكلة: تباين في تكلفة المخزون بين الفروع
**الحل**: تطبيق التكلفة الموحدة أو تحديد سياسة نقل التكلفة مع الصنف عند التحويل بين الفروع

### 4. مشكلة: تعقيد الإقرارات الضريبية
**الحل**: إعداد تقارير متخصصة تجمع بين بيانات الفواتير ETA والقيود المحاسبية

## الاستفادة القصوى من نظام التقارير

للاستفادة الكاملة من الإعدادات المحاسبية، يجب تصميم:

1. **لوحات متابعة (Dashboards)** تعرض المؤشرات المالية الرئيسية:
   - معدل دوران المخزون
   - متوسط فترة التحصيل
   - هامش الربح الإجمالي
   - نسبة المصروفات للإيرادات

2. **تقارير مراقبة الأداء** تقارن الفعلي بالمخطط:
   - تقرير مبيعات يومي/أسبوعي/شهري
   - تقرير مشتريات شهري
   - تقرير مصروفات تشغيلية

3. **تقارير تحليلية متقدمة**:
   - تحليل ربحية كل صنف
   - تحليل ربحية كل فرع
   - تحليل مبيعات حسب المناطق الجغرافية

## الخاتمة

تكوين إعدادات المحاسبة هو العمود الفقري لنظام ERP المتكامل، حيث يربط بين جميع العمليات ويُترجمها إلى لغة مالية موحدة.

الأهم من ذلك هو فهم تأثير كل إعداد على سلوك النظام وعلى التقارير المالية. لذلك يجب إشراك المحاسبين والمديرين الماليين في عملية التكوين من البداية، والاستعانة بخبراء في مجال المحاسبة والضرائب لضمان توافق النظام مع المتطلبات القانونية والتشريعية في مصر.

بالتكوين السليم للإعدادات المحاسبية، يتحول النظام من مجرد أداة لتسجيل المعاملات إلى نظام متكامل لدعم اتخاذ القرار ومراقبة الأداء المالي للمؤسسة.
*/
	public function index() {
		$this->load->language('setting/setting');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');
		$this->load->model('accounts/chartaccount'); // تأكد من تحميل النموذج المناسب

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {

			// حفظ إعدادات الاستور
			$this->model_setting_setting->editSetting('config', $this->request->post);

			// إنشاء أو تحديث الفرع الرئيسي
			$this->load->model('branch/branch');
			$main_branch = $this->model_branch_branch->getMainBranch();

			$branch_data = array(
				'name'             => $this->request->post['config_name'],
				'type'             => 'store',
				'eta_branch_id'    => 0, // للفرع الرئيسي
				'available_online' => 1,
				'telephone'        => $this->request->post['config_telephone'],
				'email'            => $this->request->post['config_email'],
				'manager_id'       => 0,
				'firstname'        => '',
				'lastname'         => '',
				'company'          => $this->request->post['config_name'],
				'address_1'        => $this->request->post['config_street'],
				'address_2'        => $this->request->post['config_building_number'],
				'city'             => $this->request->post['config_region_city'],
				'postcode'         => '',
				'country_id'       => 63,
				'zone_id'          => $this->request->post['config_governate']
			);

			if ($main_branch) {
				$this->model_branch_branch->editBranch($main_branch['branch_id'], $branch_data);

			    //نحتاج للتأكد من وجود نقطة بيع رئيسية متاحة
			} else {
				$branch_id = $this->model_branch_branch->addBranch($branch_data);
			    // إضافة نقطة بيع افتراضية
    $this->db->query("INSERT INTO " . DB_PREFIX . "pos_terminal SET
        name = 'نقطة البيع الرئيسية',
        branch_id = '" . (int)$branch_id . "',
        status = '1'");

			}



			// تحديث أسعار العملات إن كانت مفعلة
			if ($this->config->get('config_currency_auto')) {
				$this->load->model('localisation/currency');
				$this->model_localisation_currency->refresh();
			}

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('setting/setting', 'user_token=' . $this->session->data['user_token'], true));
		}

		// جلب قائمة الحسابات
		$data['accounts_list'] = $this->model_accounts_chartaccount->getAllAccountsList();

		// الأخطاء العامة
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}
		$data['user_token'] = $this->session->data['user_token'];

		// الأخطاء التفصيلية
		$data['error_name']                = isset($this->error['name']) ? $this->error['name'] : '';
		$data['error_owner']               = isset($this->error['owner']) ? $this->error['owner'] : '';
		$data['error_address']             = isset($this->error['address']) ? $this->error['address'] : '';
		$data['error_email']               = isset($this->error['email']) ? $this->error['email'] : '';
		$data['error_telephone']           = isset($this->error['telephone']) ? $this->error['telephone'] : '';
		$data['error_meta_title']          = isset($this->error['meta_title']) ? $this->error['meta_title'] : '';
		$data['error_country']             = isset($this->error['country']) ? $this->error['country'] : '';
		$data['error_zone']                = isset($this->error['zone']) ? $this->error['zone'] : '';
		$data['error_customer_group_display'] = isset($this->error['customer_group_display']) ? $this->error['customer_group_display'] : '';
		$data['error_login_attempts']      = isset($this->error['login_attempts']) ? $this->error['login_attempts'] : '';
		$data['error_voucher_min']         = isset($this->error['voucher_min']) ? $this->error['voucher_min'] : '';
		$data['error_voucher_max']         = isset($this->error['voucher_max']) ? $this->error['voucher_max'] : '';
		$data['error_processing_status']   = isset($this->error['processing_status']) ? $this->error['processing_status'] : '';
		$data['error_complete_status']     = isset($this->error['complete_status']) ? $this->error['complete_status'] : '';
		$data['error_log']                 = isset($this->error['log']) ? $this->error['log'] : '';
		$data['error_limit_admin']         = isset($this->error['limit_admin']) ? $this->error['limit_admin'] : '';
		$data['error_encryption']          = isset($this->error['encryption']) ? $this->error['encryption'] : '';

		// مسار الـ breadcrumbs
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_stores'),
			'href' => $this->url->link('setting/setting', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('setting/setting', 'user_token=' . $this->session->data['user_token'], true)
		);

		// رسالة نجاح إن وجدت
		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		// روابط الحفظ والإلغاء
		$data['action'] = $this->url->link('setting/setting', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('setting/setting', 'user_token=' . $this->session->data['user_token'], true);

		// إعدادت الـ config_meta_title
		if (isset($this->request->post['config_meta_title'])) {
			$data['config_meta_title'] = $this->request->post['config_meta_title'];
		} else {
			$data['config_meta_title'] = $this->config->get('config_meta_title');
		}

		// باقي إعدادات الـ meta
		if (isset($this->request->post['config_meta_description'])) {
			$data['config_meta_description'] = $this->request->post['config_meta_description'];
		} else {
			$data['config_meta_description'] = $this->config->get('config_meta_description');
		}
		if (isset($this->request->post['config_meta_keyword'])) {
			$data['config_meta_keyword'] = $this->request->post['config_meta_keyword'];
		} else {
			$data['config_meta_keyword'] = $this->config->get('config_meta_keyword');
		}

		// الثيم
		if (isset($this->request->post['config_theme'])) {
			$data['config_theme'] = $this->request->post['config_theme'];
		} else {
			$data['config_theme'] = $this->config->get('config_theme');
		}

		// URL الاستور
		if ($this->request->server['HTTPS']) {
			$data['store_url'] = HTTPS_CATALOG;
		} else {
			$data['store_url'] = HTTP_CATALOG;
		}

		// جلب الثيمات المثبتة
		$data['themes'] = array();
		$this->load->model('setting/extension');
		$extensions = $this->model_setting_extension->getInstalled('theme');
		foreach ($extensions as $code) {
			$this->load->language('extension/theme/' . $code, 'extension');
			$data['themes'][] = array(
				'text'  => $this->language->get('extension')->get('heading_title'),
				'value' => $code
			);
		}

		// layout
		if (isset($this->request->post['config_layout_id'])) {
			$data['config_layout_id'] = $this->request->post['config_layout_id'];
		} else {
			$data['config_layout_id'] = $this->config->get('config_layout_id');
		}
		$this->load->model('design/layout');
		$data['layouts'] = $this->model_design_layout->getLayouts();

		// اسم المتجر
		if (isset($this->request->post['config_name'])) {
			$data['config_name'] = $this->request->post['config_name'];
		} else {
			$data['config_name'] = $this->config->get('config_name');
		}

		// صاحب المتجر
		if (isset($this->request->post['config_owner'])) {
			$data['config_owner'] = $this->request->post['config_owner'];
		} else {
			$data['config_owner'] = $this->config->get('config_owner');
		}

		// بناء العنوان
		if (isset($this->request->post['config_building_number'])) {
			$data['config_address'] = $this->request->post['config_building_number'] . " - " . $this->request->post['config_region_city'] . " - " . $this->request->post['config_governate'] . " - EGYPT ";
		} else {
			$data['config_address'] = $this->config->get('config_address');
		}
		if (isset($this->request->post['config_governate'])) {
			$data['config_governate'] = $this->request->post['config_governate'];
		} else {
			$data['config_governate'] = $this->config->get('config_governate');
		}
		if (isset($this->request->post['config_street'])) {
			$data['config_street'] = $this->request->post['config_street'];
		} else {
			$data['config_street'] = $this->config->get('config_street');
		}
		if (isset($this->request->post['config_region_city'])) {
			$data['config_region_city'] = $this->request->post['config_region_city'];
		} else {
			$data['config_region_city'] = $this->config->get('config_region_city');
		}
		if (isset($this->request->post['config_building_number'])) {
			$data['config_building_number'] = $this->request->post['config_building_number'];
		} else {
			$data['config_building_number'] = $this->config->get('config_building_number');
		}

		if (isset($this->request->post['config_geocode'])) {
			$data['config_geocode'] = $this->request->post['config_geocode'];
		} else {
			$data['config_geocode'] = $this->config->get('config_geocode');
		}

		if (isset($this->request->post['config_email'])) {
			$data['config_email'] = $this->request->post['config_email'];
		} else {
			$data['config_email'] = $this->config->get('config_email');
		}

		if (isset($this->request->post['config_telephone'])) {
			$data['config_telephone'] = $this->request->post['config_telephone'];
		} else {
			$data['config_telephone'] = $this->config->get('config_telephone');
		}

		if (isset($this->request->post['config_fax'])) {
			$data['config_fax'] = $this->request->post['config_fax'];
		} else {
			$data['config_fax'] = $this->config->get('config_fax');
		}

		// صورة المتجر
		if (isset($this->request->post['config_image'])) {
			$data['config_image'] = $this->request->post['config_image'];
		} else {
			$data['config_image'] = $this->config->get('config_image');
		}
		$this->load->model('tool/image');
		if (isset($this->request->post['config_image']) && is_file(DIR_IMAGE . $this->request->post['config_image'])) {
			$data['thumb'] = $this->model_tool_image->resize($this->request->post['config_image'], 100, 100);
		} elseif ($this->config->get('config_image') && is_file(DIR_IMAGE . $this->config->get('config_image'))) {
			$data['thumb'] = $this->model_tool_image->resize($this->config->get('config_image'), 100, 100);
		} else {
			$data['thumb'] = $this->model_tool_image->resize('no_image.png', 100, 100);
		}
		$data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);

		// أوقات العمل والتعليق
		if (isset($this->request->post['config_open'])) {
			$data['config_open'] = $this->request->post['config_open'];
		} else {
			$data['config_open'] = $this->config->get('config_open');
		}
		if (isset($this->request->post['config_comment'])) {
			$data['config_comment'] = $this->request->post['config_comment'];
		} else {
			$data['config_comment'] = $this->config->get('config_comment');
		}

		// المواقع الإضافية
		$this->load->model('localisation/location');
		$data['locations'] = $this->model_localisation_location->getLocations();
		if (isset($this->request->post['config_location'])) {
			$data['config_location'] = $this->request->post['config_location'];
		} elseif ($this->config->get('config_location')) {
			$data['config_location'] = $this->config->get('config_location');
		} else {
			$data['config_location'] = array();
		}

		// الدولة والمنطقة
		if (isset($this->request->post['config_country_id'])) {
			$data['config_country_id'] = $this->request->post['config_country_id'];
		} else {
			$data['config_country_id'] = $this->config->get('config_country_id');
		}
		$this->load->model('localisation/country');
		$data['countries'] = $this->model_localisation_country->getCountries();
		if (isset($this->request->post['config_zone_id'])) {
			$data['config_zone_id'] = (int)$this->request->post['config_zone_id'];
		} else {
			$data['config_zone_id'] = (int)$this->config->get('config_zone_id');
		}

		// ETA
		if (isset($this->request->post['config_eta_taxpayer_id'])) {
			$data['config_eta_taxpayer_id'] = (int)$this->request->post['config_eta_taxpayer_id'];
		} else {
			$data['config_eta_taxpayer_id'] = (int)$this->config->get('config_eta_taxpayer_id');
		}
		if (isset($this->request->post['config_eta_activity_code'])) {
			$data['config_eta_activity_code'] = (int)$this->request->post['config_eta_activity_code'];
		} else {
			$data['config_eta_activity_code'] = (int)$this->config->get('config_eta_activity_code');
		}

		// التوقيت والمنطقة الزمنية
		if (isset($this->request->post['config_timezone'])) {
			$data['config_timezone'] = $this->request->post['config_timezone'];
		} elseif ($this->config->has('config_timezone')) {
			$data['config_timezone'] = $this->config->get('config_timezone');
		} else {
			$data['config_timezone'] = 'UTC';
		}
		$data['timezones'] = array();
		$timestamp = time();
		$timezones = timezone_identifiers_list();
		foreach ($timezones as $timezone) {
			date_default_timezone_set($timezone);
			$hour = ' (' . date('P', $timestamp) . ')';
			$data['timezones'][] = array(
				'text'  => $timezone . $hour,
				'value' => $timezone
			);
		}
		date_default_timezone_set($this->config->get('config_timezone'));

		// اللغة
		if (isset($this->request->post['config_language'])) {
			$data['config_language'] = $this->request->post['config_language'];
		} else {
			$data['config_language'] = $this->config->get('config_language');
		}
		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();

		// اللغة في لوحة التحكم
		if (isset($this->request->post['config_admin_language'])) {
			$data['config_admin_language'] = $this->request->post['config_admin_language'];
		} else {
			$data['config_admin_language'] = $this->config->get('config_admin_language');
		}

		// العملة
		if (isset($this->request->post['config_currency'])) {
			$data['config_currency'] = $this->request->post['config_currency'];
		} else {
			$data['config_currency'] = $this->config->get('config_currency');
		}
		if (isset($this->request->post['config_currency_auto'])) {
			$data['config_currency_auto'] = $this->request->post['config_currency_auto'];
		} else {
			$data['config_currency_auto'] = $this->config->get('config_currency_auto');
		}
		if (isset($this->request->post['config_currency_engine'])) {
			$data['config_currency_engine'] = $this->request->post['config_currency_engine'];
		} else {
			$data['config_currency_engine'] = $this->config->get('config_currency_engine');
		}
		$this->load->model('localisation/currency');
		$data['currencies'] = $this->model_localisation_currency->getCurrencies();
		$data['currency_engines'] = array();
		$extension_codes = $this->model_setting_extension->getInstalled('currency');
		foreach ($extension_codes as $extension_code) {
			if ($this->config->get('currency_' . $extension_code . '_status')) {
				$this->load->language('extension/currency/' . $extension_code, 'currency_engine');
				$data['currency_engines'][] = array(
					'text'  => $this->language->get('currency_engine')->get('heading_title'),
					'value' => $extension_code
				);
			}
		}

		// وحدات الطول والوزن
		if (isset($this->request->post['config_length_class_id'])) {
			$data['config_length_class_id'] = $this->request->post['config_length_class_id'];
		} else {
			$data['config_length_class_id'] = $this->config->get('config_length_class_id');
		}
		$this->load->model('localisation/length_class');
		$data['length_classes'] = $this->model_localisation_length_class->getLengthClasses();

		if (isset($this->request->post['config_weight_class_id'])) {
			$data['config_weight_class_id'] = $this->request->post['config_weight_class_id'];
		} else {
			$data['config_weight_class_id'] = $this->config->get('config_weight_class_id');
		}
		$this->load->model('localisation/weight_class');
		$data['weight_classes'] = $this->model_localisation_weight_class->getWeightClasses();

		// حدود لوحة التحكم
		if (isset($this->request->post['config_limit_admin'])) {
			$data['config_limit_admin'] = $this->request->post['config_limit_admin'];
		} else {
			$data['config_limit_admin'] = $this->config->get('config_limit_admin') ? $this->config->get('config_limit_admin') : 200;
		}

		// إعدادات المنتجات
		if (isset($this->request->post['config_product_count'])) {
			$data['config_product_count'] = $this->request->post['config_product_count'];
		} else {
			$data['config_product_count'] = $this->config->get('config_product_count');
		}
		if (isset($this->request->post['config_review_status'])) {
			$data['config_review_status'] = $this->request->post['config_review_status'];
		} else {
			$data['config_review_status'] = $this->config->get('config_review_status');
		}
		if (isset($this->request->post['config_review_guest'])) {
			$data['config_review_guest'] = $this->request->post['config_review_guest'];
		} else {
			$data['config_review_guest'] = $this->config->get('config_review_guest');
		}

		// قسائم الهدايا
		if (isset($this->request->post['config_voucher_min'])) {
			$data['config_voucher_min'] = $this->request->post['config_voucher_min'];
		} else {
			$data['config_voucher_min'] = $this->config->get('config_voucher_min');
		}
		if (isset($this->request->post['config_voucher_max'])) {
			$data['config_voucher_max'] = $this->request->post['config_voucher_max'];
		} else {
			$data['config_voucher_max'] = $this->config->get('config_voucher_max');
		}

		// الضريبة
		if (isset($this->request->post['config_tax'])) {
			$data['config_tax'] = $this->request->post['config_tax'];
		} else {
			$data['config_tax'] = $this->config->get('config_tax');
		}
		if (isset($this->request->post['config_tax_default'])) {
			$data['config_tax_default'] = $this->request->post['config_tax_default'];
		} else {
			$data['config_tax_default'] = $this->config->get('config_tax_default');
		}
		if (isset($this->request->post['config_tax_customer'])) {
			$data['config_tax_customer'] = $this->request->post['config_tax_customer'];
		} else {
			$data['config_tax_customer'] = $this->config->get('config_tax_customer');
		}

		// العملاء
		if (isset($this->request->post['config_customer_online'])) {
			$data['config_customer_online'] = $this->request->post['config_customer_online'];
		} else {
			$data['config_customer_online'] = $this->config->get('config_customer_online');
		}
		if (isset($this->request->post['config_customer_activity'])) {
			$data['config_customer_activity'] = $this->request->post['config_customer_activity'];
		} else {
			$data['config_customer_activity'] = $this->config->get('config_customer_activity');
		}
		if (isset($this->request->post['config_customer_search'])) {
			$data['config_customer_search'] = $this->request->post['config_customer_search'];
		} else {
			$data['config_customer_search'] = $this->config->get('config_customer_search');
		}
		if (isset($this->request->post['config_customer_group_id'])) {
			$data['config_customer_group_id'] = $this->request->post['config_customer_group_id'];
		} else {
			$data['config_customer_group_id'] = $this->config->get('config_customer_group_id');
		}
		$this->load->model('customer/customer_group');
		$data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();
		if (isset($this->request->post['config_customer_group_display'])) {
			$data['config_customer_group_display'] = $this->request->post['config_customer_group_display'];
		} elseif ($this->config->get('config_customer_group_display')) {
			$data['config_customer_group_display'] = $this->config->get('config_customer_group_display');
		} else {
			$data['config_customer_group_display'] = array();
		}
		if (isset($this->request->post['config_customer_price'])) {
			$data['config_customer_price'] = $this->request->post['config_customer_price'];
		} else {
			$data['config_customer_price'] = $this->config->get('config_customer_price');
		}

		if (isset($this->request->post['config_login_attempts'])) {
			$data['config_login_attempts'] = $this->request->post['config_login_attempts'];
		} elseif ($this->config->has('config_login_attempts')) {
			$data['config_login_attempts'] = $this->config->get('config_login_attempts');
		} else {
			$data['config_login_attempts'] = 5;
		}

		// الشروط
		if (isset($this->request->post['config_account_id'])) {
			$data['config_account_id'] = $this->request->post['config_account_id'];
		} else {
			$data['config_account_id'] = $this->config->get('config_account_id');
		}
		$this->load->model('catalog/information');
		$data['informations'] = $this->model_catalog_information->getInformations();

		// السلة
		if (isset($this->request->post['config_cart_weight'])) {
			$data['config_cart_weight'] = $this->request->post['config_cart_weight'];
		} else {
			$data['config_cart_weight'] = $this->config->get('config_cart_weight');
		}

		// إكمال الطلب
		if (isset($this->request->post['config_checkout_guest'])) {
			$data['config_checkout_guest'] = $this->request->post['config_checkout_guest'];
		} else {
			$data['config_checkout_guest'] = $this->config->get('config_checkout_guest');
		}
		if (isset($this->request->post['config_checkout_id'])) {
			$data['config_checkout_id'] = $this->request->post['config_checkout_id'];
		} else {
			$data['config_checkout_id'] = $this->config->get('config_checkout_id');
		}
		if (isset($this->request->post['config_invoice_prefix'])) {
			$data['config_invoice_prefix'] = $this->request->post['config_invoice_prefix'];
		} elseif ($this->config->get('config_invoice_prefix')) {
			$data['config_invoice_prefix'] = $this->config->get('config_invoice_prefix');
		} else {
			$data['config_invoice_prefix'] = 'INV-' . date('Y') . '-00';
		}

		// حالة الطلب
		if (isset($this->request->post['config_order_status_id'])) {
			$data['config_order_status_id'] = $this->request->post['config_order_status_id'];
		} else {
			$data['config_order_status_id'] = $this->config->get('config_order_status_id');
		}
		if (isset($this->request->post['config_processing_status'])) {
			$data['config_processing_status'] = $this->request->post['config_processing_status'];
		} elseif ($this->config->get('config_processing_status')) {
			$data['config_processing_status'] = $this->config->get('config_processing_status');
		} else {
			$data['config_processing_status'] = array();
		}
		if (isset($this->request->post['config_complete_status'])) {
			$data['config_complete_status'] = $this->request->post['config_complete_status'];
		} elseif ($this->config->get('config_complete_status')) {
			$data['config_complete_status'] = $this->config->get('config_complete_status');
		} else {
			$data['config_complete_status'] = array();
		}
		if (isset($this->request->post['config_fraud_status_id'])) {
			$data['config_fraud_status_id'] = $this->request->post['config_fraud_status_id'];
		} else {
			$data['config_fraud_status_id'] = $this->config->get('config_fraud_status_id');
		}
		$this->load->model('localisation/order_status');
		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		// APIs
		if (isset($this->request->post['config_api_id'])) {
			$data['config_api_id'] = $this->request->post['config_api_id'];
		} else {
			$data['config_api_id'] = $this->config->get('config_api_id');
		}
		$this->load->model('user/api');
		$data['apis'] = $this->model_user_api->getApis();

		// المخزون
		if (isset($this->request->post['config_stock_display'])) {
			$data['config_stock_display'] = $this->request->post['config_stock_display'];
		} else {
			$data['config_stock_display'] = $this->config->get('config_stock_display');
		}
		if (isset($this->request->post['config_stock_warning'])) {
			$data['config_stock_warning'] = $this->request->post['config_stock_warning'];
		} else {
			$data['config_stock_warning'] = $this->config->get('config_stock_warning');
		}
		if (isset($this->request->post['config_stock_checkout'])) {
			$data['config_stock_checkout'] = $this->request->post['config_stock_checkout'];
		} else {
			$data['config_stock_checkout'] = $this->config->get('config_stock_checkout');
		}

		// التسويق بالعمولة
		if (isset($this->request->post['config_affiliate_group_id'])) {
			$data['config_affiliate_group_id'] = $this->request->post['config_affiliate_group_id'];
		} else {
			$data['config_affiliate_group_id'] = $this->config->get('config_affiliate_group_id');
		}
		if (isset($this->request->post['config_affiliate_approval'])) {
			$data['config_affiliate_approval'] = $this->request->post['config_affiliate_approval'];
		} elseif ($this->config->has('config_affiliate_approval')) {
			$data['config_affiliate_approval'] = $this->config->get('config_affiliate_approval');
		} else {
			$data['config_affiliate_approval'] = '';
		}
		if (isset($this->request->post['config_affiliate_auto'])) {
			$data['config_affiliate_auto'] = $this->request->post['config_affiliate_auto'];
		} elseif ($this->config->has('config_affiliate_auto')) {
			$data['config_affiliate_auto'] = $this->config->get('config_affiliate_auto');
		} else {
			$data['config_affiliate_auto'] = '';
		}
		if (isset($this->request->post['config_affiliate_commission'])) {
			$data['config_affiliate_commission'] = $this->request->post['config_affiliate_commission'];
		} elseif ($this->config->has('config_affiliate_commission')) {
			$data['config_affiliate_commission'] = $this->config->get('config_affiliate_commission');
		} else {
			$data['config_affiliate_commission'] = '5.00';
		}
		if (isset($this->request->post['config_affiliate_id'])) {
			$data['config_affiliate_id'] = $this->request->post['config_affiliate_id'];
		} else {
			$data['config_affiliate_id'] = $this->config->get('config_affiliate_id');
		}

		// المرتجعات
		if (isset($this->request->post['config_return_id'])) {
			$data['config_return_id'] = $this->request->post['config_return_id'];
		} else {
			$data['config_return_id'] = $this->config->get('config_return_id');
		}
		if (isset($this->request->post['config_return_status_id'])) {
			$data['config_return_status_id'] = $this->request->post['config_return_status_id'];
		} else {
			$data['config_return_status_id'] = $this->config->get('config_return_status_id');
		}
		$this->load->model('localisation/return_status');
		$data['return_statuses'] = $this->model_localisation_return_status->getReturnStatuses();

		// الكابتشا
		if (isset($this->request->post['config_captcha'])) {
			$data['config_captcha'] = $this->request->post['config_captcha'];
		} else {
			$data['config_captcha'] = $this->config->get('config_captcha');
		}
		$data['captchas'] = array();
		$extensions = $this->model_setting_extension->getInstalled('captcha');
		foreach ($extensions as $code) {
			$this->load->language('extension/captcha/' . $code, 'extension');
			if ($this->config->get('captcha_' . $code . '_status')) {
				$data['captchas'][] = array(
					'text'  => $this->language->get('extension')->get('heading_title'),
					'value' => $code
				);
			}
		}
		if (isset($this->request->post['config_captcha_page'])) {
			$data['config_captcha_page'] = $this->request->post['config_captcha_page'];
		} elseif ($this->config->has('config_captcha_page')) {
			$data['config_captcha_page'] = $this->config->get('config_captcha_page');
		} else {
			$data['config_captcha_page'] = array();
		}
		$data['captcha_pages'] = array(
			array('text' => $this->language->get('text_register'), 'value' => 'register'),
			array('text' => $this->language->get('text_guest'),    'value' => 'guest'),
			array('text' => $this->language->get('text_review'),   'value' => 'review'),
			array('text' => $this->language->get('text_return'),   'value' => 'return'),
			array('text' => $this->language->get('text_contact'),  'value' => 'contact')
		);

		// اللوجو والأيقونة
		if (isset($this->request->post['config_logo'])) {
			$data['config_logo'] = $this->request->post['config_logo'];
		} else {
			$data['config_logo'] = $this->config->get('config_logo');
		}

		if (isset($this->request->post['config_logo']) && is_file(DIR_IMAGE . $this->request->post['config_logo'])) {
			$data['logo'] = $this->model_tool_image->resize($this->request->post['config_logo'], 100, 100);
		} elseif ($this->config->get('config_logo') && is_file(DIR_IMAGE . $this->config->get('config_logo'))) {
			$data['logo'] = $this->model_tool_image->resize($this->config->get('config_logo'), 100, 100);
		} else {
			$data['logo'] = $this->model_tool_image->resize('no_image.png', 100, 100);
		}


		if (isset($this->request->post['config_icon'])) {
			$data['config_icon'] = $this->request->post['config_icon'];
		} else {
			$data['config_icon'] = $this->config->get('config_icon');
		}
		if (isset($this->request->post['config_icon']) && is_file(DIR_IMAGE . $this->request->post['config_icon'])) {
			$data['icon'] = $this->model_tool_image->resize($this->request->post['config_icon'], 100, 100);
		} elseif ($this->config->get('config_icon') && is_file(DIR_IMAGE . $this->config->get('config_icon'))) {
			$data['icon'] = $this->model_tool_image->resize($this->config->get('config_icon'), 100, 100);
		} else {
			$data['icon'] = $this->model_tool_image->resize('no_image.png', 100, 100);
		}

		// إعدادات البريد
		if (isset($this->request->post['config_mail_engine'])) {
			$data['config_mail_engine'] = $this->request->post['config_mail_engine'];
		} else {
			$data['config_mail_engine'] = $this->config->get('config_mail_engine');
		}
		if (isset($this->request->post['config_mail_parameter'])) {
			$data['config_mail_parameter'] = $this->request->post['config_mail_parameter'];
		} else {
			$data['config_mail_parameter'] = $this->config->get('config_mail_parameter');
		}
		if (isset($this->request->post['config_mail_smtp_hostname'])) {
			$data['config_mail_smtp_hostname'] = $this->request->post['config_mail_smtp_hostname'];
		} else {
			$data['config_mail_smtp_hostname'] = $this->config->get('config_mail_smtp_hostname');
		}
		if (isset($this->request->post['config_mail_smtp_username'])) {
			$data['config_mail_smtp_username'] = $this->request->post['config_mail_smtp_username'];
		} else {
			$data['config_mail_smtp_username'] = $this->config->get('config_mail_smtp_username');
		}
		if (isset($this->request->post['config_mail_smtp_password'])) {
			$data['config_mail_smtp_password'] = $this->request->post['config_mail_smtp_password'];
		} else {
			$data['config_mail_smtp_password'] = $this->config->get('config_mail_smtp_password');
		}
		if (isset($this->request->post['config_mail_smtp_port'])) {
			$data['config_mail_smtp_port'] = $this->request->post['config_mail_smtp_port'];
		} elseif ($this->config->has('config_mail_smtp_port')) {
			$data['config_mail_smtp_port'] = $this->config->get('config_mail_smtp_port');
		} else {
			$data['config_mail_smtp_port'] = 25;
		}
		if (isset($this->request->post['config_mail_smtp_timeout'])) {
			$data['config_mail_smtp_timeout'] = $this->request->post['config_mail_smtp_timeout'];
		} elseif ($this->config->has('config_mail_smtp_timeout')) {
			$data['config_mail_smtp_timeout'] = $this->config->get('config_mail_smtp_timeout');
		} else {
			$data['config_mail_smtp_timeout'] = 5;
		}

		// تنبيهات البريد
		if (isset($this->request->post['config_mail_alert'])) {
			$data['config_mail_alert'] = $this->request->post['config_mail_alert'];
		} elseif ($this->config->has('config_mail_alert')) {
			$data['config_mail_alert'] = $this->config->get('config_mail_alert');
		} else {
			$data['config_mail_alert'] = array();
		}
		$data['mail_alerts'] = array(
			array('text' => $this->language->get('text_mail_account'),   'value' => 'account'),
			array('text' => $this->language->get('text_mail_affiliate'), 'value' => 'affiliate'),
			array('text' => $this->language->get('text_mail_order'),     'value' => 'order'),
			array('text' => $this->language->get('text_mail_review'),    'value' => 'review')
		);
		if (isset($this->request->post['config_mail_alert_email'])) {
			$data['config_mail_alert_email'] = $this->request->post['config_mail_alert_email'];
		} else {
			$data['config_mail_alert_email'] = $this->config->get('config_mail_alert_email');
		}

		// الأمان والـ SEO
		if (isset($this->request->post['config_secure'])) {
			$data['config_secure'] = $this->request->post['config_secure'];
		} else {
			$data['config_secure'] = $this->config->get('config_secure');
		}
		if (isset($this->request->post['config_shared'])) {
			$data['config_shared'] = $this->request->post['config_shared'];
		} else {
			$data['config_shared'] = $this->config->get('config_shared');
		}
		if (isset($this->request->post['config_robots'])) {
			$data['config_robots'] = $this->request->post['config_robots'];
		} else {
			$data['config_robots'] = $this->config->get('config_robots');
		}
		if (isset($this->request->post['config_seo_url'])) {
			$data['config_seo_url'] = $this->request->post['config_seo_url'];
		} else {
			$data['config_seo_url'] = $this->config->get('config_seo_url');
		}

		// رفع الملفات
		if (isset($this->request->post['config_file_max_size'])) {
			$data['config_file_max_size'] = $this->request->post['config_file_max_size'];
		} elseif ($this->config->get('config_file_max_size')) {
			$data['config_file_max_size'] = $this->config->get('config_file_max_size');
		} else {
			$data['config_file_max_size'] = 300000;
		}
		if (isset($this->request->post['config_file_ext_allowed'])) {
			$data['config_file_ext_allowed'] = $this->request->post['config_file_ext_allowed'];
		} else {
			$data['config_file_ext_allowed'] = $this->config->get('config_file_ext_allowed');
		}
		if (isset($this->request->post['config_file_mime_allowed'])) {
			$data['config_file_mime_allowed'] = $this->request->post['config_file_mime_allowed'];
		} else {
			$data['config_file_mime_allowed'] = $this->config->get('config_file_mime_allowed');
		}

		// الصيانة
		if (isset($this->request->post['config_maintenance'])) {
			$data['config_maintenance'] = $this->request->post['config_maintenance'];
		} else {
			$data['config_maintenance'] = $this->config->get('config_maintenance');
		}

		// كلمات مرور
		if (isset($this->request->post['config_password'])) {
			$data['config_password'] = $this->request->post['config_password'];
		} else {
			$data['config_password'] = $this->config->get('config_password');
		}
		if (isset($this->request->post['config_eta_mode'])) {
			$data['config_eta_mode'] = $this->request->post['config_eta_mode'];
		} else {
			$data['config_eta_mode'] = $this->config->get('config_eta_mode');
		}

		// إعدادات ETA
		if (isset($this->request->post['config_eta_client_id'])) {
			$data['config_eta_client_id'] = $this->request->post['config_eta_client_id'];
		} else {
			$data['config_eta_client_id'] = $this->config->get('config_eta_client_id');
		}
		if (isset($this->request->post['config_eta_secret_1'])) {
			$data['config_eta_secret_1'] = $this->request->post['config_eta_secret_1'];
		} else {
			$data['config_eta_secret_1'] = $this->config->get('config_eta_secret_1');
		}
		if (isset($this->request->post['config_eta_secret_2'])) {
			$data['config_eta_secret_2'] = $this->request->post['config_eta_secret_2'];
		} else {
			$data['config_eta_secret_2'] = $this->config->get('config_eta_secret_2');
		}
		if (isset($this->request->post['config_eta_certificate_data'])) {
			$data['config_eta_certificate_data'] = $this->request->post['config_eta_certificate_data'];
		} else {
			$data['config_eta_certificate_data'] = $this->config->get('config_eta_certificate_data');
		}
		if (isset($this->request->post['config_eta_usb_pin'])) {
			$data['config_eta_usb_pin'] = $this->request->post['config_eta_usb_pin'];
		} else {
			$data['config_eta_usb_pin'] = $this->config->get('config_eta_usb_pin');
		}
		if (isset($this->request->post['config_eta_access_token'])) {
			$data['config_eta_access_token'] = $this->request->post['config_eta_access_token'];
		} else {
			$data['config_eta_access_token'] = $this->config->get('config_eta_access_token');
		}
		// لو تكرر مفتاح config_eta_usb_pin
		if (isset($this->request->post['config_eta_usb_pin'])) {
			$data['config_eta_usb_pin'] = $this->request->post['config_eta_usb_pin'];
		} else {
			$data['config_eta_usb_pin'] = $this->config->get('config_eta_usb_pin');
		}

		// التشفير والضغط
		if (isset($this->request->post['config_encryption'])) {
			$data['config_encryption'] = $this->request->post['config_encryption'];
		} else {
			$data['config_encryption'] = $this->config->get('config_encryption');
		}
		if (isset($this->request->post['config_compression'])) {
			$data['config_compression'] = $this->request->post['config_compression'];
		} else {
			$data['config_compression'] = $this->config->get('config_compression');
		}

		// الأخطاء
		if (isset($this->request->post['config_error_display'])) {
			$data['config_error_display'] = $this->request->post['config_error_display'];
		} else {
			$data['config_error_display'] = $this->config->get('config_error_display');
		}
		if (isset($this->request->post['config_error_log'])) {
			$data['config_error_log'] = $this->request->post['config_error_log'];
		} else {
			$data['config_error_log'] = $this->config->get('config_error_log');
		}
		if (isset($this->request->post['config_error_filename'])) {
			$data['config_error_filename'] = $this->request->post['config_error_filename'];
		} else {
			$data['config_error_filename'] = $this->config->get('config_error_filename');
		}

		// ------------------- Accounting Settings -------------------
		// السنة المالية (البداية)
		if (isset($this->request->post['config_accounting_financial_year_start'])) {
			$data['config_accounting_financial_year_start'] = $this->request->post['config_accounting_financial_year_start'];
		} else {
			$data['config_accounting_financial_year_start'] = $this->config->get('config_accounting_financial_year_start');
		}
		// السنة المالية (النهاية)
		if (isset($this->request->post['config_accounting_financial_year_end'])) {
			$data['config_accounting_financial_year_end'] = $this->request->post['config_accounting_financial_year_end'];
		} else {
			$data['config_accounting_financial_year_end'] = $this->config->get('config_accounting_financial_year_end');
		}
		// قفل التاريخ
		if (isset($this->request->post['config_accounting_lock_date'])) {
			$data['config_accounting_lock_date'] = $this->request->post['config_accounting_lock_date'];
		} else {
			$data['config_accounting_lock_date'] = $this->config->get('config_accounting_lock_date');
		}
		// العملة الافتراضية
		if (isset($this->request->post['config_accounting_default_currency'])) {
			$data['config_accounting_default_currency'] = $this->request->post['config_accounting_default_currency'];
		} else {
			$data['config_accounting_default_currency'] = $this->config->get('config_accounting_default_currency') ?: 'EGP';
		}
		// التقريب
		if (isset($this->request->post['config_accounting_currency_rounding'])) {
			$data['config_accounting_currency_rounding'] = $this->request->post['config_accounting_currency_rounding'];
		} else {
			$data['config_accounting_currency_rounding'] = $this->config->get('config_accounting_currency_rounding') ?: 'none';
		}
		// طريقة التقييم المخزني
		$data['config_accounting_inventory_valuation_method'] = 'weighted';

		// حساب تسوية المخزون
		if (isset($this->request->post['config_accounting_inventory_adjustment_account'])) {
			$data['config_accounting_inventory_adjustment_account'] = $this->request->post['config_accounting_inventory_adjustment_account'];
		} else {
			$data['config_accounting_inventory_adjustment_account'] = $this->config->get('config_accounting_inventory_adjustment_account') ?: '0';
		}

		// الحسابات الافتراضية
		if (isset($this->request->post['config_accounting_default_sales_account'])) {
			$data['config_accounting_default_sales_account'] = $this->request->post['config_accounting_default_sales_account'];
		} else {
			$data['config_accounting_default_sales_account'] = $this->config->get('config_accounting_default_sales_account') ?: '0';
		}
		if (isset($this->request->post['config_accounting_default_purchase_account'])) {
			$data['config_accounting_default_purchase_account'] = $this->request->post['config_accounting_default_purchase_account'];
		} else {
			$data['config_accounting_default_purchase_account'] = $this->config->get('config_accounting_default_purchase_account') ?: '0';
		}
		if (isset($this->request->post['config_accounting_default_inventory_account'])) {
			$data['config_accounting_default_inventory_account'] = $this->request->post['config_accounting_default_inventory_account'];
		} else {
			$data['config_accounting_default_inventory_account'] = $this->config->get('config_accounting_default_inventory_account') ?: '0';
		}
		if (isset($this->request->post['config_accounting_default_tax_account'])) {
			$data['config_accounting_default_tax_account'] = $this->request->post['config_accounting_default_tax_account'];
		} else {
			$data['config_accounting_default_tax_account'] = $this->config->get('config_accounting_default_tax_account') ?: '0';
		}
		if (isset($this->request->post['config_accounting_default_ar_account'])) {
			$data['config_accounting_default_ar_account'] = $this->request->post['config_accounting_default_ar_account'];
		} else {
			$data['config_accounting_default_ar_account'] = $this->config->get('config_accounting_default_ar_account') ?: '0';
		}
		if (isset($this->request->post['config_accounting_default_ap_account'])) {
			$data['config_accounting_default_ap_account'] = $this->request->post['config_accounting_default_ap_account'];
		} else {
			$data['config_accounting_default_ap_account'] = $this->config->get('config_accounting_default_ap_account') ?: '0';
		}
		if (isset($this->request->post['config_accounting_tax_regime'])) {
			$data['config_accounting_tax_regime'] = $this->request->post['config_accounting_tax_regime'];
		} else {
			$data['config_accounting_tax_regime'] = $this->config->get('config_accounting_tax_regime') ?: 'vat';
		}
		if (isset($this->request->post['config_accounting_lock_after_audit'])) {
			$data['config_accounting_lock_after_audit'] = $this->request->post['config_accounting_lock_after_audit'];
		} else {
			$data['config_accounting_lock_after_audit'] = $this->config->get('config_accounting_lock_after_audit') ?: '0';
		}
		if (isset($this->request->post['config_accounting_reporting_period'])) {
			$data['config_accounting_reporting_period'] = $this->request->post['config_accounting_reporting_period'];
		} else {
			$data['config_accounting_reporting_period'] = $this->config->get('config_accounting_reporting_period') ?: 'month';
		}

		// Additional Accounting Settings - Assets
		if (isset($this->request->post['config_accounting_cash_account'])) {
			$data['config_accounting_cash_account'] = $this->request->post['config_accounting_cash_account'];
		} else {
			$data['config_accounting_cash_account'] = $this->config->get('config_accounting_cash_account') ?: '0';
		}
		if (isset($this->request->post['config_accounting_petty_cash_account'])) {
			$data['config_accounting_petty_cash_account'] = $this->request->post['config_accounting_petty_cash_account'];
		} else {
			$data['config_accounting_petty_cash_account'] = $this->config->get('config_accounting_petty_cash_account') ?: '0';
		}
		if (isset($this->request->post['config_accounting_bank_account'])) {
			$data['config_accounting_bank_account'] = $this->request->post['config_accounting_bank_account'];
		} else {
			$data['config_accounting_bank_account'] = $this->config->get('config_accounting_bank_account') ?: '0';
		}
		if (isset($this->request->post['config_accounting_inventory_account'])) {
			$data['config_accounting_inventory_account'] = $this->request->post['config_accounting_inventory_account'];
		} else {
			$data['config_accounting_inventory_account'] = $this->config->get('config_accounting_inventory_account') ?: '0';
		}
		if (isset($this->request->post['config_accounting_inventory_transit_account'])) {
			$data['config_accounting_inventory_transit_account'] = $this->request->post['config_accounting_inventory_transit_account'];
		} else {
			$data['config_accounting_inventory_transit_account'] = $this->config->get('config_accounting_inventory_transit_account') ?: '0';
		}
		if (isset($this->request->post['config_accounting_ar_account'])) {
			$data['config_accounting_ar_account'] = $this->request->post['config_accounting_ar_account'];
		} else {
			$data['config_accounting_ar_account'] = $this->config->get('config_accounting_ar_account') ?: '0';
		}

		// Liabilities
		if (isset($this->request->post['config_accounting_ap_account'])) {
			$data['config_accounting_ap_account'] = $this->request->post['config_accounting_ap_account'];
		} else {
			$data['config_accounting_ap_account'] = $this->config->get('config_accounting_ap_account') ?: '0';
		}
		if (isset($this->request->post['config_accounting_loans_account'])) {
			$data['config_accounting_loans_account'] = $this->request->post['config_accounting_loans_account'];
		} else {
			$data['config_accounting_loans_account'] = $this->config->get('config_accounting_loans_account') ?: '0';
		}

		// Purchase Accounts
		if (isset($this->request->post['config_accounting_purchase_account'])) {
			$data['config_accounting_purchase_account'] = $this->request->post['config_accounting_purchase_account'];
		} else {
			$data['config_accounting_purchase_account'] = $this->config->get('config_accounting_purchase_account') ?: '0';
		}
		if (isset($this->request->post['config_accounting_purchase_returns_account'])) {
			$data['config_accounting_purchase_returns_account'] = $this->request->post['config_accounting_purchase_returns_account'];
		} else {
			$data['config_accounting_purchase_returns_account'] = $this->config->get('config_accounting_purchase_returns_account') ?: '0';
		}
		if (isset($this->request->post['config_accounting_purchase_discount_account'])) {
			$data['config_accounting_purchase_discount_account'] = $this->request->post['config_accounting_purchase_discount_account'];
		} else {
			$data['config_accounting_purchase_discount_account'] = $this->config->get('config_accounting_purchase_discount_account') ?: '0';
		}
		if (isset($this->request->post['config_accounting_import_duties_account'])) {
			$data['config_accounting_import_duties_account'] = $this->request->post['config_accounting_import_duties_account'];
		} else {
			$data['config_accounting_import_duties_account'] = $this->config->get('config_accounting_import_duties_account') ?: '0';
		}
		if (isset($this->request->post['config_accounting_freight_charges_account'])) {
			$data['config_accounting_freight_charges_account'] = $this->request->post['config_accounting_freight_charges_account'];
		} else {
			$data['config_accounting_freight_charges_account'] = $this->config->get('config_accounting_freight_charges_account') ?: '0';
		}

		// Sales Accounts
		if (isset($this->request->post['config_accounting_sales_account'])) {
			$data['config_accounting_sales_account'] = $this->request->post['config_accounting_sales_account'];
		} else {
			$data['config_accounting_sales_account'] = $this->config->get('config_accounting_sales_account') ?: '0';
		}
		if (isset($this->request->post['config_accounting_sales_returns_account'])) {
			$data['config_accounting_sales_returns_account'] = $this->request->post['config_accounting_sales_returns_account'];
		} else {
			$data['config_accounting_sales_returns_account'] = $this->config->get('config_accounting_sales_returns_account') ?: '0';
		}
		if (isset($this->request->post['config_accounting_sales_discount_account'])) {
			$data['config_accounting_sales_discount_account'] = $this->request->post['config_accounting_sales_discount_account'];
		} else {
			$data['config_accounting_sales_discount_account'] = $this->config->get('config_accounting_sales_discount_account') ?: '0';
		}
		if (isset($this->request->post['config_accounting_sales_shipping_account'])) {
			$data['config_accounting_sales_shipping_account'] = $this->request->post['config_accounting_sales_shipping_account'];
		} else {
			$data['config_accounting_sales_shipping_account'] = $this->config->get('config_accounting_sales_shipping_account') ?: '0';
		}

		// Tax Accounts
		if (isset($this->request->post['config_accounting_sales_tax_account'])) {
			$data['config_accounting_sales_tax_account'] = $this->request->post['config_accounting_sales_tax_account'];
		} else {
			$data['config_accounting_sales_tax_account'] = $this->config->get('config_accounting_sales_tax_account') ?: '0';
		}
		if (isset($this->request->post['config_accounting_purchase_tax_account'])) {
			$data['config_accounting_purchase_tax_account'] = $this->request->post['config_accounting_purchase_tax_account'];
		} else {
			$data['config_accounting_purchase_tax_account'] = $this->config->get('config_accounting_purchase_tax_account') ?: '0';
		}

		// Expense Accounts
		if (isset($this->request->post['config_accounting_general_expenses_account'])) {
			$data['config_accounting_general_expenses_account'] = $this->request->post['config_accounting_general_expenses_account'];
		} else {
			$data['config_accounting_general_expenses_account'] = $this->config->get('config_accounting_general_expenses_account') ?: '0';
		}
		if (isset($this->request->post['config_accounting_marketing_expenses_account'])) {
			$data['config_accounting_marketing_expenses_account'] = $this->request->post['config_accounting_marketing_expenses_account'];
		} else {
			$data['config_accounting_marketing_expenses_account'] = $this->config->get('config_accounting_marketing_expenses_account') ?: '0';
		}
		if (isset($this->request->post['config_accounting_salaries_expenses_account'])) {
			$data['config_accounting_salaries_expenses_account'] = $this->request->post['config_accounting_salaries_expenses_account'];
		} else {
			$data['config_accounting_salaries_expenses_account'] = $this->config->get('config_accounting_salaries_expenses_account') ?: '0';
		}
		if (isset($this->request->post['config_accounting_other_income_account'])) {
			$data['config_accounting_other_income_account'] = $this->request->post['config_accounting_other_income_account'];
		} else {
			$data['config_accounting_other_income_account'] = $this->config->get('config_accounting_other_income_account') ?: '0';
		}
		// ------------------- End of Accounting Settings -------------------

		// تحميل أجزاء الصفحة
		$data['header']     = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer']     = $this->load->controller('common/footer');

		// عرض الصفحة
		$this->response->setOutput($this->load->view('setting/setting', $data));
	}

	/**
	 * التحقق من صحة البيانات قبل الحفظ
	 */
	protected function validate() {
		// الصلاحيات
		if (!$this->user->hasPermission('modify', 'setting/setting')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		// التحقق من الحقول الأساسية
		if (!$this->request->post['config_meta_title']) {
			$this->error['meta_title'] = $this->language->get('error_meta_title');
		}

		if (!$this->request->post['config_name']) {
			$this->error['name'] = $this->language->get('error_name');
		}

		// التحقق من عنوان الفرع
		if ((utf8_strlen($this->request->post['config_building_number']) < 1) ||
		    (utf8_strlen($this->request->post['config_building_number']) > 256)) {
			$this->error['address'] = $this->language->get('error_address');
		}
		if ((utf8_strlen($this->request->post['config_governate']) < 1) ||
		    (utf8_strlen($this->request->post['config_governate']) > 256)) {
			$this->error['address'] = $this->language->get('error_address');
		}
		if ((utf8_strlen($this->request->post['config_street']) < 1) ||
		    (utf8_strlen($this->request->post['config_street']) > 256)) {
			$this->error['address'] = $this->language->get('error_address');
		}
		if ((utf8_strlen($this->request->post['config_region_city']) < 1) ||
		    (utf8_strlen($this->request->post['config_region_city']) > 256)) {
			$this->error['address'] = $this->language->get('error_address');
		}

		// التحقق من البريد والهاتف
		if ((utf8_strlen($this->request->post['config_email']) > 96) ||
		    !filter_var($this->request->post['config_email'], FILTER_VALIDATE_EMAIL)) {
			$this->error['email'] = $this->language->get('error_email');
		}
		if ((utf8_strlen($this->request->post['config_telephone']) < 3) ||
		    (utf8_strlen($this->request->post['config_telephone']) > 32)) {
			$this->error['telephone'] = $this->language->get('error_telephone');
		}

		// مجموعة العملاء
		if (!empty($this->request->post['config_customer_group_display']) &&
		    !in_array($this->request->post['config_customer_group_id'], $this->request->post['config_customer_group_display'])) {
			$this->error['customer_group_display'] = $this->language->get('error_customer_group_display');
		}

		// الحد الإداري
		if (!$this->request->post['config_limit_admin']) {
			$this->error['limit_admin'] = $this->language->get('error_limit');
		}

		// محاولات الدخول
		if ($this->request->post['config_login_attempts'] < 1) {
			$this->error['login_attempts'] = $this->language->get('error_login_attempts');
		}

		// قسائم الهدايا
		if (!$this->request->post['config_voucher_min']) {
			$this->error['voucher_min'] = $this->language->get('error_voucher_min');
		}
		if (!$this->request->post['config_voucher_max']) {
			$this->error['voucher_max'] = $this->language->get('error_voucher_max');
		}

		// حالة الطلب
		if (!isset($this->request->post['config_processing_status'])) {
			$this->error['processing_status'] = $this->language->get('error_processing_status');
		}
		if (!isset($this->request->post['config_complete_status'])) {
			$this->error['complete_status'] = $this->language->get('error_complete_status');
		}

		// ملف الأخطاء
		if (!$this->request->post['config_error_filename']) {
			$this->error['log'] = $this->language->get('error_log_required');
		} elseif (preg_match('/\.\.[\/\\\]?/', $this->request->post['config_error_filename'])) {
			$this->error['log'] = $this->language->get('error_log_invalid');
		} elseif (substr($this->request->post['config_error_filename'], strrpos($this->request->post['config_error_filename'], '.')) != '.log') {
			$this->error['log'] = $this->language->get('error_log_extension');
		}

		// التشفير
		if ((utf8_strlen($this->request->post['config_encryption']) < 32) ||
		    (utf8_strlen($this->request->post['config_encryption']) > 1024)) {
			$this->error['encryption'] = $this->language->get('error_encryption');
		}

		// التحقق من الإعدادات المحاسبية الأساسية
		$this->validateAccountingSettings();

		// إن وجدت أي أخطاء، ضع تحذير
		if ($this->error && !isset($this->error['warning'])) {
			$this->error['warning'] = $this->language->get('error_warning');
		}

		return !$this->error;
	}

	/**
	 * التحقق من صحة الإعدادات المحاسبية
	 */
	private function validateAccountingSettings() {
		// التحقق من وجود دليل الحسابات
		$this->load->model('accounts/chartaccount');

		// قائمة الحسابات المطلوبة للتحقق
		$required_accounts = array(
			'config_accounting_cash_account' => 'حساب النقدية',
			'config_accounting_bank_account' => 'حساب البنك',
			'config_accounting_inventory_account' => 'حساب المخزون',
			'config_accounting_ar_account' => 'حساب الذمم المدينة',
			'config_accounting_ap_account' => 'حساب الذمم الدائنة',
			'config_accounting_sales_account' => 'حساب المبيعات',
			'config_accounting_purchase_account' => 'حساب المشتريات',
			'config_accounting_sales_tax_account' => 'حساب ضريبة المبيعات',
			'config_accounting_purchase_tax_account' => 'حساب ضريبة المشتريات'
		);

		// التحقق من كل حساب مطلوب
		foreach ($required_accounts as $field => $name) {
			if (isset($this->request->post[$field]) && !empty($this->request->post[$field])) {
				$account_code = $this->request->post[$field];

				// التحقق من وجود الحساب في دليل الحسابات
				if ($account_code != '0') {
					$account = $this->model_accounts_chartaccount->getAccountByCode($account_code);
					if (!$account) {
						$this->error['accounting_' . $field] = 'الحساب المحدد لـ ' . $name . ' غير موجود في دليل الحسابات!';
					}
				}
			}
		}

		// التحقق من إعدادات ETA إذا كانت مفعلة
		if (isset($this->request->post['config_eta_mode']) && $this->request->post['config_eta_mode'] == '1') {
			if (empty($this->request->post['config_eta_taxpayer_id'])) {
				$this->error['eta_taxpayer_id'] = 'رقم التسجيل الضريبي مطلوب في وضع الإنتاج!';
			}

			if (empty($this->request->post['config_eta_activity_code'])) {
				$this->error['eta_activity_code'] = 'كود النشاط مطلوب في وضع الإنتاج!';
			}

			if (empty($this->request->post['config_eta_client_id'])) {
				$this->error['eta_client_id'] = 'معرف العميل ETA مطلوب في وضع الإنتاج!';
			}
		}
	}

	/**
	 * عرض صورة الثيم في الصفحة
	 */
	public function theme() {
		if ($this->request->server['HTTPS']) {
			$server = HTTPS_CATALOG;
		} else {
			$server = HTTP_CATALOG;
		}

		// متوافق مع الثيمات القديمة
		if ($this->request->get['theme'] == 'theme_default') {
			$theme = $this->config->get('theme_default_directory');
		} else {
			$theme = basename($this->request->get['theme']);
		}

		if (is_file(DIR_CATALOG . 'view/theme/' . $theme . '/image/' . $theme . '.png')) {
			$this->response->setOutput($server . 'catalog/view/theme/' . $theme . '/image/' . $theme . '.png');
		} else {
			$this->response->setOutput($server . 'image/no_image.png');
		}
	}
}
