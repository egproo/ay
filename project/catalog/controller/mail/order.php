<?php
class ControllerMailOrder extends Controller {
	public function index(&$route, &$args) {
		// استلام المعاملات
		if (isset($args[0])) {
			$order_id = $args[0];
		} else {
			$order_id = 0;
		}

		if (isset($args[1])) {
			$order_status_id = $args[1];
		} else {
			$order_status_id = 0;
		}

		if (isset($args[2])) {
			$comment = $args[2];
		} else {
			$comment = '';
		}

		if (isset($args[3])) {
			$notify = $args[3];
		} else {
			$notify = '';
		}

		// جلب معلومات الطلب
		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($order_id);

		if ($order_info) {
			// إذا انتقل حالة الطلب من 0 إلى حال أكبر => إرسال بريد الطلب الكامل (HTML)
			if (!$order_info['order_status_id'] && $order_status_id) {
				$this->add($order_info, $order_status_id, $comment, $notify);
			}

			// إذا كان الطلب بحالة !=0 ثم جدّد حالته مع تفعيل التنبيه => أرسل بريد التحديث (نصي)
			if ($order_info['order_status_id'] && $order_status_id && $notify) {
				$this->edit($order_info, $order_status_id, $comment);
			}
		}
	}

	public function add($order_info, $order_status_id, $comment, $notify) {
		// إن كنت تريد تفعيل تحميل المنتجات (كوبونات تحميل مثلاً):
		$download_status = false;

		// تحميل اللغة
		$language = new Language($order_info['language_code']);
		$language->load($order_info['language_code']);
		$language->load('mail/order_add');

		// تجهيز مصفوفة $data المُرسلة إلى القالب
		$data['title'] = sprintf($language->get('text_subject'), $order_info['store_name'], $order_info['order_id']);

		$data['text_greeting']         = sprintf($language->get('text_greeting'), $order_info['store_name']);
		$data['text_link']             = $language->get('text_link');
		$data['text_download']         = $language->get('text_download');
		$data['text_order_detail']     = $language->get('text_order_detail');
		$data['text_instruction']      = $language->get('text_instruction');
		$data['text_order_id']         = $language->get('text_order_id');
		$data['text_date_added']       = $language->get('text_date_added');
		$data['text_payment_method']   = $language->get('text_payment_method');
		$data['text_shipping_method']  = $language->get('text_shipping_method');
		$data['text_email']            = $language->get('text_email');
		$data['text_telephone']        = $language->get('text_telephone');
		$data['text_ip']               = $language->get('text_ip');
		$data['text_order_status']     = $language->get('text_order_status');
		$data['text_payment_address']  = $language->get('text_payment_address');
		$data['text_shipping_address'] = $language->get('text_shipping_address');
		$data['text_product']          = $language->get('text_product');
		$data['text_model']            = $language->get('text_model');
		$data['text_quantity']         = $language->get('text_quantity');
		$data['text_price']            = $language->get('text_price');
		$data['text_total']            = $language->get('text_total');
		$data['text_footer']           = $language->get('text_footer');

		$data['logo']       = $order_info['store_url'] . 'image/' . $this->config->get('config_logo');
		$data['store_name'] = $order_info['store_name'];
		$data['store_url']  = $order_info['store_url'];
		$data['customer_id']= $order_info['customer_id'];

		// رابط تفاصيل الطلب (إن كان العميل مسجّلاً)
		$data['link'] = $order_info['store_url'] . 'index.php?route=account/order/info&order_id=' . $order_info['order_id'];

		if ($download_status) {
			$data['download'] = $order_info['store_url'] . 'index.php?route=account/download';
		} else {
			$data['download'] = '';
		}

		$data['order_id']       = $order_info['order_id'];
		$data['date_added']     = date($language->get('date_format_short'), strtotime($order_info['date_added']));
		$data['payment_method'] = $order_info['payment_method'];
		$data['shipping_method']= $order_info['shipping_method'];
		$data['email']          = $order_info['email'];
		$data['telephone']      = $order_info['telephone'];
		$data['ip']             = $order_info['ip'];

		// اسم حالة الطلب
		$order_status_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_status 
			WHERE order_status_id = '" . (int)$order_status_id . "' 
			  AND language_id = '" . (int)$order_info['language_id'] . "'"
		);

		if ($order_status_query->num_rows) {
			$data['order_status'] = $order_status_query->row['name'];
		} else {
			$data['order_status'] = '';
		}

		if ($comment && $notify) {
			$data['comment'] = nl2br($comment);
		} else {
			$data['comment'] = '';
		}

		// تنسيق عنوان الدفع
		if ($order_info['payment_address_format']) {
			$format = $order_info['payment_address_format'];
		} else {
			$format = '{firstname} {lastname}' . "\n" 
			        . '{company}' . "\n" 
			        . '{address_1}' . "\n" 
			        . '{address_2}' . "\n" 
			        . '{city} {postcode}' . "\n" 
			        . '{zone}' . "\n" 
			        . '{country}';
		}

		$find = array(
			'{firstname}',
			'{lastname}',
			'{company}',
			'{address_1}',
			'{address_2}',
			'{city}',
			'{postcode}',
			'{zone}',
			'{zone_code}',
			'{country}'
		);

		$replace = array(
			'firstname' => $order_info['payment_firstname'],
			'lastname'  => $order_info['payment_lastname'],
			'company'   => $order_info['payment_company'],
			'address_1' => $order_info['payment_address_1'],
			'address_2' => $order_info['payment_address_2'],
			'city'      => $order_info['payment_city'],
			'postcode'  => $order_info['payment_postcode'],
			'zone'      => $order_info['payment_zone'],
			'zone_code' => $order_info['payment_zone_code'],
			'country'   => $order_info['payment_country']
		);

		$data['payment_address'] = str_replace(
			array("\r\n", "\r", "\n"),
			'<br />',
			preg_replace(
				array("/\s\s+/", "/\r\r+/", "/\n\n+/"),
				'<br />',
				trim(str_replace($find, $replace, $format))
			)
		);

		// تنسيق عنوان الشحن
		if ($order_info['shipping_address_format']) {
			$format = $order_info['shipping_address_format'];
		} else {
			$format = '{firstname} {lastname}' . "\n" 
			        . '{company}' . "\n" 
			        . '{address_1}' . "\n" 
			        . '{address_2}' . "\n" 
			        . '{city} {postcode}' . "\n" 
			        . '{zone}' . "\n" 
			        . '{country}';
		}

		$find = array(
			'{firstname}',
			'{lastname}',
			'{company}',
			'{address_1}',
			'{address_2}',
			'{city}',
			'{postcode}',
			'{zone}',
			'{zone_code}',
			'{country}'
		);

		$replace = array(
			'firstname' => $order_info['shipping_firstname'],
			'lastname'  => $order_info['shipping_lastname'],
			'company'   => $order_info['shipping_company'],
			'address_1' => $order_info['shipping_address_1'],
			'address_2' => $order_info['shipping_address_2'],
			'city'      => $order_info['shipping_city'],
			'postcode'  => $order_info['shipping_postcode'],
			'zone'      => $order_info['shipping_zone'],
			'zone_code' => $order_info['shipping_zone_code'],
			'country'   => $order_info['shipping_country']
		);

		$data['shipping_address'] = str_replace(
			array("\r\n", "\r", "\n"),
			'<br />',
			preg_replace(
				array("/\s\s+/", "/\r\r+/", "/\n\n+/"),
				'<br />',
				trim(str_replace($find, $replace, $format))
			)
		);

		// تحميل الموديل الخاص بالرفع (للملفات)
		$this->load->model('tool/upload');

		// ============================
		// ==  جلب تفاصيل المنتجات  ==
		// ============================
		$data['products'] = array();

		// جلب order_products بالطريقة القديمة
		$order_products = $this->model_checkout_order->getOrderProducts($order_info['order_id']);

		// هنا يمكننا إدراج الموديل الجديد لجلب تفاصيل إضافية (مثل اسم الوحدة) إن أردت
		$this->load->model('catalog/product');

		foreach ($order_products as $order_product) {
			// جلب الخيارات
			$option_data = array();
			$order_options = $this->model_checkout_order->getOrderOptions($order_info['order_id'], $order_product['order_product_id']);

			foreach ($order_options as $order_option) {
				if ($order_option['type'] != 'file') {
					$value = $order_option['value'];
				} else {
					// لو الخيار ملف
					$upload_info = $this->model_tool_upload->getUploadByCode($order_option['value']);
					$value = $upload_info ? $upload_info['name'] : '';
				}

				$option_data[] = array(
					'name'  => $order_option['name'],
					'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value)
				);
			}

			// مثال: جلب الوحدة عبر الموديل الجديد (لو حفظت unit_id في order_product)
			$unitName = '';
			if (!empty($order_product['unit_id'])) {
				$unitName = $this->model_catalog_product->getUnitName($order_product['unit_id']);
			}
			// أو اجلب المنتج كاملًا
			// $fullProd = $this->model_catalog_product->getProduct($order_product['product_id']);
			// ...الخ

			$data['products'][] = array(
				'name'     => $order_product['name'],
				'model'    => $order_product['model'],
				'option'   => $option_data,
				'quantity' => $order_product['quantity'],
				'unit_name'=> $unitName,
				'price'    => $this->currency->format(
					$order_product['price'] + ($this->config->get('config_tax') ? $order_product['tax'] : 0), 
					$order_info['currency_code'], 
					$order_info['currency_value']
				),
				'total'    => $this->currency->format(
					$order_product['total'] + ($this->config->get('config_tax') ? $order_product['tax'] * $order_product['quantity'] : 0),
					$order_info['currency_code'],
					$order_info['currency_value']
				),
			);
		}

		// =======================
		// ==  قسائم الهدايا  ==
		// =======================
		$data['vouchers'] = array();

		$order_vouchers = $this->model_checkout_order->getOrderVouchers($order_info['order_id']);
		foreach ($order_vouchers as $order_voucher) {
			$data['vouchers'][] = array(
				'description' => $order_voucher['description'],
				'amount'      => $this->currency->format(
					$order_voucher['amount'],
					$order_info['currency_code'],
					$order_info['currency_value']
				),
			);
		}

		// ======================
		// ==  المجاميع Totals ==
		// ======================
		$data['totals'] = array();
		$order_totals = $this->model_checkout_order->getOrderTotals($order_info['order_id']);
		foreach ($order_totals as $order_total) {
			$data['totals'][] = array(
				'title' => $order_total['title'],
				'text'  => $this->currency->format(
					$order_total['value'],
					$order_info['currency_code'],
					$order_info['currency_value']
				),
			);
		}

		// =======================
		// ==  إعداد الإرسال   ==
		// =======================
		$this->load->model('setting/setting');
		$from = $this->model_setting_setting->getSettingValue('config_email', $order_info['store_id']);
		if (!$from) {
			$from = $this->config->get('config_email');
		}

		// إن وُجد بريد العميل
		if ($order_info['email']) {
			$mail = new Mail($this->config->get('config_mail_engine'));
			$mail->parameter = $this->config->get('config_mail_parameter');
			$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
			$mail->smtp_username = $this->config->get('config_mail_smtp_username');
			$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
			$mail->smtp_port = $this->config->get('config_mail_smtp_port');
			$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

			$mail->setTo($order_info['email']);
			$mail->setFrom($from);
			$mail->setSender(html_entity_decode($order_info['store_name'], ENT_QUOTES, 'UTF-8'));
			$mail->setSubject(html_entity_decode(
				sprintf($language->get('text_subject'), $order_info['store_name'], $order_info['order_id']), 
				ENT_QUOTES, 
				'UTF-8'
			));

			// استدعاء القالب
			$mail->setHtml($this->load->view('mail/order_add', $data));

			// إرسال
			$mail->send();
		}
	}

	public function edit($order_info, $order_status_id, $comment) {
		// بريد التحديث النصي (قصير)
		$language = new Language($order_info['language_code']);
		$language->load($order_info['language_code']);
		$language->load('mail/order_edit');

		$data['text_order_id']     = $language->get('text_order_id');
		$data['text_date_added']   = $language->get('text_date_added');
		$data['text_order_status'] = $language->get('text_order_status');
		$data['text_link']         = $language->get('text_link');
		$data['text_comment']      = $language->get('text_comment');
		$data['text_footer']       = $language->get('text_footer');

		$data['order_id']   = $order_info['order_id'];
		$data['date_added'] = date($language->get('date_format_short'), strtotime($order_info['date_added']));

		// حالة الطلب الجديدة
		$order_status_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_status 
			WHERE order_status_id = '" . (int)$order_status_id . "' 
			  AND language_id = '" . (int)$order_info['language_id'] . "'"
		);

		if ($order_status_query->num_rows) {
			$data['order_status'] = $order_status_query->row['name'];
		} else {
			$data['order_status'] = '';
		}

		// رابط تفاصيل الطلب
		if ($order_info['customer_id']) {
			$data['link'] = $order_info['store_url'] . 'index.php?route=account/order/info&order_id=' . $order_info['order_id'];
		} else {
			$data['link'] = '';
		}

		// التعليق (بسيط)
		$data['comment'] = strip_tags($comment);

		$this->load->model('setting/setting');
		$from = $this->model_setting_setting->getSettingValue('config_email', $order_info['store_id']);
		if (!$from) {
			$from = $this->config->get('config_email');
		}

		// إرسال فقط لو فيه بريد
		if ($order_info['email']) {
			$mail = new Mail($this->config->get('config_mail_engine'));
			$mail->parameter     = $this->config->get('config_mail_parameter');
			$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
			$mail->smtp_username = $this->config->get('config_mail_smtp_username');
			$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
			$mail->smtp_port     = $this->config->get('config_mail_smtp_port');
			$mail->smtp_timeout  = $this->config->get('config_mail_smtp_timeout');

			$mail->setTo($order_info['email']);
			$mail->setFrom($from);
			$mail->setSender(html_entity_decode($order_info['store_name'], ENT_QUOTES, 'UTF-8'));
			$mail->setSubject(html_entity_decode(
				sprintf($language->get('text_subject'), $order_info['store_name'], $order_info['order_id']), 
				ENT_QUOTES, 
				'UTF-8'
			));

			// هنا نستخدم قالب نصي بسيط - أو يمكنك استخدام نفس القالب بأي طريقة
			$mail->setText($this->load->view('mail/order_edit', $data));

			$mail->send();
		}
	}

	// تنبيه المدير (Admin Alert Mail) - كما هو
	public function alert(&$route, &$args) {
		if (isset($args[0])) {
			$order_id = $args[0];
		} else {
			$order_id = 0;
		}

		if (isset($args[1])) {
			$order_status_id = $args[1];
		} else {
			$order_status_id = 0;
		}

		if (isset($args[2])) {
			$comment = $args[2];
		} else {
			$comment = '';
		}

		if (isset($args[3])) {
			$notify = $args[3];
		} else {
			$notify = '';
		}

		$order_info = $this->model_checkout_order->getOrder($order_id);

		if ($order_info && !$order_info['order_status_id'] && $order_status_id && in_array('order', (array)$this->config->get('config_mail_alert'))) {
			$this->load->language('mail/order_alert');

			$data['text_received']     = $this->language->get('text_received');
			$data['text_order_id']     = $this->language->get('text_order_id');
			$data['text_date_added']   = $this->language->get('text_date_added');
			$data['text_order_status'] = $this->language->get('text_order_status');
			$data['text_product']      = $this->language->get('text_product');
			$data['text_total']        = $this->language->get('text_total');
			$data['text_comment']      = $this->language->get('text_comment');

			$data['order_id']   = $order_info['order_id'];
			$data['date_added'] = date($this->language->get('date_format_short'), strtotime($order_info['date_added']));

			$order_status_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_status 
				WHERE order_status_id = '" . (int)$order_status_id . "' 
				  AND language_id = '" . (int)$this->config->get('config_language_id') . "'"
			);

			if ($order_status_query->num_rows) {
				$data['order_status'] = $order_status_query->row['name'];
			} else {
				$data['order_status'] = '';
			}

			$this->load->model('tool/upload');

			// جلب المنتجات
			$data['products'] = array();
			$order_products = $this->model_checkout_order->getOrderProducts($order_id);

			foreach ($order_products as $order_product) {
				$option_data = array();

				$order_options = $this->model_checkout_order->getOrderOptions($order_info['order_id'], $order_product['order_product_id']);

				foreach ($order_options as $order_option) {
					if ($order_option['type'] != 'file') {
						$value = $order_option['value'];
					} else {
						$upload_info = $this->model_tool_upload->getUploadByCode($order_option['value']);
						$value = $upload_info ? $upload_info['name'] : '';
					}

					$option_data[] = array(
						'name'  => $order_option['name'],
						'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value)
					);
				}

				$data['products'][] = array(
					'name'     => $order_product['name'],
					'model'    => $order_product['model'],
					'quantity' => $order_product['quantity'],
					'option'   => $option_data,
					'total'    => html_entity_decode($this->currency->format(
						$order_product['total'] + ($this->config->get('config_tax') 
						? ($order_product['tax'] * $order_product['quantity']) 
						: 0), 
						$order_info['currency_code'], 
						$order_info['currency_value']
					), ENT_NOQUOTES, 'UTF-8')
				);
			}

			// قسائم
			$data['vouchers'] = array();
			$order_vouchers = $this->model_checkout_order->getOrderVouchers($order_id);

			foreach ($order_vouchers as $order_voucher) {
				$data['vouchers'][] = array(
					'description' => $order_voucher['description'],
					'amount'      => html_entity_decode($this->currency->format(
						$order_voucher['amount'], 
						$order_info['currency_code'], 
						$order_info['currency_value']
					), ENT_NOQUOTES, 'UTF-8')
				);
			}

			// المجاميع
			$data['totals'] = array();
			$order_totals = $this->model_checkout_order->getOrderTotals($order_id);

			foreach ($order_totals as $order_total) {
				$data['totals'][] = array(
					'title' => $order_total['title'],
					'value' => html_entity_decode($this->currency->format(
						$order_total['value'],
						$order_info['currency_code'],
						$order_info['currency_value']
					), ENT_NOQUOTES, 'UTF-8')
				);
			}

			$data['comment'] = strip_tags($order_info['comment']);

			// إرسال للبريد الإداري
			$mail = new Mail($this->config->get('config_mail_engine'));
			$mail->parameter     = $this->config->get('config_mail_parameter');
			$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
			$mail->smtp_username = $this->config->get('config_mail_smtp_username');
			$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
			$mail->smtp_port     = $this->config->get('config_mail_smtp_port');
			$mail->smtp_timeout  = $this->config->get('config_mail_smtp_timeout');

			$mail->setTo($this->config->get('config_email'));
			$mail->setFrom($this->config->get('config_email'));
			$mail->setSender(html_entity_decode($order_info['store_name'], ENT_QUOTES, 'UTF-8'));
			$mail->setSubject(html_entity_decode(sprintf(
				$this->language->get('text_subject'), 
				$this->config->get('config_name'), 
				$order_info['order_id']
			), ENT_QUOTES, 'UTF-8'));

			$mail->setText($this->load->view('mail/order_alert', $data));
			$mail->send();

			// إرسال لأي بريد آخر محدد بالإعدادات
			$emails = explode(',', $this->config->get('config_mail_alert_email'));
			foreach ($emails as $email) {
				$email = trim($email);
				if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
					$mail->setTo($email);
					$mail->send();
				}
			}
		}
	}
}
