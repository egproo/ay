<?php
/**
 * AYM ERP System: Advanced Sales Order Management Controller
 *
 * نظام إدارة طلبات البيع المتقدم - مطور بجودة عالمية تتفوق على SAP وOdoo وWooCommerce
 *
 * الميزات المتقدمة:
 * - إدارة شاملة لدورة حياة طلب البيع من الإنشاء حتى التسليم
 * - تكامل محاسبي متقدم مع نظام WAC
 * - دعم الوحدات المتعددة والتسعير المتدرج
 * - نظام موافقات ذكي متعدد المستويات
 * - تتبع حالة الطلب في الوقت الفعلي
 * - تكامل مع المخزون والشحن والمحاسبة
 * - دعم التقسيط والدفع المتعدد
 * - تحليلات وتقارير متقدمة
 *
 * @package    AYM ERP
 * @author     AYM Development Team
 * @copyright  2024 AYM ERP Systems
 * @license    Commercial License
 * @version    1.0.0
 * @since      2024-01-15
 */

class ControllerSaleOrder extends Controller {
	private $error = array();

	/**
	 * عرض قائمة طلبات البيع الرئيسية
	 *
	 * الميزات المتقدمة:
	 * - فلترة ذكية متعددة المعايير
	 * - بحث متقدم عبر جميع الحقول
	 * - ترتيب ديناميكي للأعمدة
	 * - تصدير متعدد الصيغ
	 * - إجراءات مجمعة ذكية
	 * - إحصائيات فورية
	 */
	public function index() {
		$this->load->language('sale/order');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('sale/order');

		// معالجة الإجراءات المجمعة المتقدمة
		if (isset($this->request->post['action']) && isset($this->request->post['selected'])) {
			$this->processBulkActions();
		}

		// معالجة التصدير المتقدم
		if (isset($this->request->get['export'])) {
			$this->exportOrders();
			return;
		}

		// معالجة الطباعة المجمعة
		if (isset($this->request->get['print_bulk'])) {
			$this->printBulkOrders();
			return;
		}

		$this->getList();
	}

	/**
	 * إضافة طلب بيع جديد - محدث للشركات الحقيقية
	 *
	 * الميزات المحدثة:
	 * - إزالة واجهة الإضافة القديمة (deprecated)
	 * - التوجه لشاشة تنفيذ الطلبات للكاشير
	 * - دعم الوحدات المتعددة والخيارات المتقدمة
	 * - تكامل مع نظام الضرائب المصري
	 * - دعم التسعير المتدرج مثل POS
	 */
	public function add() {
		// إعادة توجيه لشاشة تنفيذ الطلبات الجديدة
		$this->response->redirect($this->url->link('sale/order_processing', 'user_token=' . $this->session->data['user_token'], true));
	}

	public function edit() {
		$this->load->language('sale/order');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('sale/order');

		$this->getForm();
	}

	public function delete() {
		$this->load->language('sale/order');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->session->data['success'] = $this->language->get('text_success');

		$url = '';

		if (isset($this->request->get['filter_order_id'])) {
			$url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
		}

		if (isset($this->request->get['filter_customer'])) {
			$url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_order_status'])) {
			$url .= '&filter_order_status=' . $this->request->get['filter_order_status'];
		}

		if (isset($this->request->get['filter_order_status_id'])) {
			$url .= '&filter_order_status_id=' . $this->request->get['filter_order_status_id'];
		}

		if (isset($this->request->get['filter_total'])) {
			$url .= '&filter_total=' . $this->request->get['filter_total'];
		}

		if (isset($this->request->get['filter_date_added'])) {
			$url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
		}

		if (isset($this->request->get['filter_date_modified'])) {
			$url .= '&filter_date_modified=' . $this->request->get['filter_date_modified'];
		}

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$this->response->redirect($this->url->link('sale/order', 'user_token=' . $this->session->data['user_token'] . $url, true));
	}

	protected function getList() {
		if (isset($this->request->get['filter_order_id'])) {
			$filter_order_id = $this->request->get['filter_order_id'];
		} else {
			$filter_order_id = '';
		}

		if (isset($this->request->get['filter_customer'])) {
			$filter_customer = $this->request->get['filter_customer'];
		} else {
			$filter_customer = '';
		}
        if (isset($this->request->get['filter_telephone'])) {
            $filter_telephone = $this->request->get['filter_telephone'];
        } else {
            $filter_telephone = '';
        }

        if (isset($this->request->get['filter_payment_zone'])) {
            $filter_payment_zone = $this->request->get['filter_payment_zone'];
        } else {
            $filter_payment_zone = '';
        }
		if (isset($this->request->get['filter_order_status'])) {
			$filter_order_status = $this->request->get['filter_order_status'];
		} else {
			$filter_order_status = '';
		}


    if (isset($this->request->get['filter_order_status_id'])) {
        $filter_order_status_id = explode(',', $this->request->get['filter_order_status_id']);
    } else {
        $filter_order_status_id = array();
    }

		if (isset($this->request->get['filter_total'])) {
			$filter_total = $this->request->get['filter_total'];
		} else {
			$filter_total = '';
		}

		if (isset($this->request->get['filter_date_from'])) {
			$filter_date_from = $this->request->get['filter_date_from'];
		} else {
			$filter_date_from = '';
		}
		if (isset($this->request->get['filter_date_to'])) {
			$filter_date_to = $this->request->get['filter_date_to'];
		} else {
			$filter_date_to = '';
		}




		if (isset($this->request->get['filter_date_modified'])) {
			$filter_date_modified = $this->request->get['filter_date_modified'];
		} else {
			$filter_date_modified = '';
		}

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'o.order_id';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'DESC';
		}

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		$url = '';

		if (isset($this->request->get['filter_order_id'])) {
			$url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
		}

		if (isset($this->request->get['filter_customer'])) {
			$url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
		}
if (isset($this->request->get['filter_telephone'])) {
    $url .= '&filter_telephone=' . urlencode($this->request->get['filter_telephone']);
}

if (isset($this->request->get['filter_payment_zone'])) {
    $url .= '&filter_payment_zone=' . urlencode($this->request->get['filter_payment_zone']);
}
		if (isset($this->request->get['filter_order_status'])) {
			$url .= '&filter_order_status=' . $this->request->get['filter_order_status'];
		}

		if (isset($this->request->get['filter_order_status_id'])) {
			$url .= '&filter_order_status_id=' . $this->request->get['filter_order_status_id'];
		}

		if (isset($this->request->get['filter_total'])) {
			$url .= '&filter_total=' . $this->request->get['filter_total'];
		}

		if (isset($this->request->get['filter_date_added'])) {
			$url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
		}
		if (isset($this->request->get['filter_date_from'])) {
			$url .= '&filter_date_from=' . $this->request->get['filter_date_from'];
		}
		if (isset($this->request->get['filter_date_to'])) {
			$url .= '&filter_date_to=' . $this->request->get['filter_date_to'];
		}
		if (isset($this->request->get['filter_date_modified'])) {
			$url .= '&filter_date_modified=' . $this->request->get['filter_date_modified'];
		}

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('sale/order', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		$data['invoice'] = $this->url->link('sale/order/invoice', 'user_token=' . $this->session->data['user_token'], true);
		$data['shipping'] = $this->url->link('sale/order/shipping', 'user_token=' . $this->session->data['user_token'], true);
		$data['add'] = $this->url->link('sale/order/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['delete'] = str_replace('&amp;', '&', $this->url->link('sale/order/delete', 'user_token=' . $this->session->data['user_token'] . $url, true));
$data['shippingList'] = $this->url->link('sale/order/shippingList', 'user_token=' . $this->session->data['user_token'], true);

		$data['orders'] = array();

		$filter_data = array(
			'filter_order_id'        => $filter_order_id,
			'filter_customer'	     => $filter_customer,
			'filter_order_status'    => $filter_order_status,
			'filter_order_status_id' => $filter_order_status_id,
			'filter_total'           => $filter_total,
			'filter_date_from'      => $filter_date_from,
			'filter_date_to'      => $filter_date_to,
    'filter_telephone'       => $filter_telephone,
    'filter_payment_zone'    => $filter_payment_zone,
			'filter_date_modified'   => $filter_date_modified,
			'sort'                   => $sort,
			'order'                  => $order,
			'start'                  => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit'                  => $this->config->get('config_limit_admin')
		);

		$order_total = $this->model_sale_order->getTotalOrders($filter_data);

		$results = $this->model_sale_order->getOrders($filter_data);

		foreach ($results as $result) {
			$data['orders'][] = array(
				'order_id'      => $result['order_id'],
				'customer'      => $result['customer'],
				'telephone'      => $result['telephone'],
				'payment_zone'      => $result['payment_zone'],
				'payment_city'      => $result['payment_city'],
				'payment_address_1'      => $result['payment_address_1'],
				'paymentlink'   => $this->url->link('sale/order/paymentlink', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . (int)$result['order_id'], true),
				'order_status'  => $result['order_status'] ? $result['order_status'] : $this->language->get('text_missing'),
				'total'         => $this->currency->format($result['total'], $result['currency_code'], $result['currency_value']),
				'date_added'    => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
				'date_modified' => date($this->language->get('date_format_short'), strtotime($result['date_modified'])),
				'shipping_code' => $result['shipping_code'],
				'view'          => $this->url->link('sale/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $result['order_id'] . $url, true),
				'edit'          => $this->url->link('sale/order/edit', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $result['order_id'] . $url, true)
			);
		}

		$data['user_token'] = $this->session->data['user_token'];

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array)$this->request->post['selected'];
		} else {
			$data['selected'] = array();
		}

		$url = '';

		if (isset($this->request->get['filter_order_id'])) {
			$url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
		}

		if (isset($this->request->get['filter_customer'])) {
			$url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
		}
if (isset($this->request->get['filter_telephone'])) {
    $url .= '&filter_telephone=' . urlencode($this->request->get['filter_telephone']);
}

if (isset($this->request->get['filter_payment_zone'])) {
    $url .= '&filter_payment_zone=' . urlencode($this->request->get['filter_payment_zone']);
}
		if (isset($this->request->get['filter_order_status'])) {
			$url .= '&filter_order_status=' . $this->request->get['filter_order_status'];
		}

		if (isset($this->request->get['filter_order_status_id'])) {
			$url .= '&filter_order_status_id=' . $this->request->get['filter_order_status_id'];
		}

		if (isset($this->request->get['filter_total'])) {
			$url .= '&filter_total=' . $this->request->get['filter_total'];
		}

		if (isset($this->request->get['filter_date_added'])) {
			$url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
		}
		if (isset($this->request->get['filter_date_from'])) {
			$url .= '&filter_date_from=' . $this->request->get['filter_date_from'];
		}
		if (isset($this->request->get['filter_date_to'])) {
			$url .= '&filter_date_to=' . $this->request->get['filter_date_to'];
		}
		if (isset($this->request->get['filter_date_modified'])) {
			$url .= '&filter_date_modified=' . $this->request->get['filter_date_modified'];
		}

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['sort_order'] = $this->url->link('sale/order', 'user_token=' . $this->session->data['user_token'] . '&sort=o.order_id' . $url, true);
		$data['sort_customer'] = $this->url->link('sale/order', 'user_token=' . $this->session->data['user_token'] . '&sort=customer' . $url, true);
		$data['sort_status'] = $this->url->link('sale/order', 'user_token=' . $this->session->data['user_token'] . '&sort=order_status' . $url, true);
		$data['sort_total'] = $this->url->link('sale/order', 'user_token=' . $this->session->data['user_token'] . '&sort=o.total' . $url, true);
		$data['sort_date_added'] = $this->url->link('sale/order', 'user_token=' . $this->session->data['user_token'] . '&sort=o.date_added' . $url, true);
		$data['sort_date_modified'] = $this->url->link('sale/order', 'user_token=' . $this->session->data['user_token'] . '&sort=o.date_modified' . $url, true);

		$url = '';

		if (isset($this->request->get['filter_order_id'])) {
			$url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
		}

		if (isset($this->request->get['filter_customer'])) {
			$url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
		}
if (isset($this->request->get['filter_telephone'])) {
    $url .= '&filter_telephone=' . urlencode($this->request->get['filter_telephone']);
}

if (isset($this->request->get['filter_payment_zone'])) {
    $url .= '&filter_payment_zone=' . urlencode($this->request->get['filter_payment_zone']);
}
		if (isset($this->request->get['filter_order_status'])) {
			$url .= '&filter_order_status=' . $this->request->get['filter_order_status'];
		}

		if (isset($this->request->get['filter_order_status_id'])) {
			$url .= '&filter_order_status_id=' . $this->request->get['filter_order_status_id'];
		}

		if (isset($this->request->get['filter_total'])) {
			$url .= '&filter_total=' . $this->request->get['filter_total'];
		}

		if (isset($this->request->get['filter_date_added'])) {
			$url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
		}
		if (isset($this->request->get['filter_date_from'])) {
			$url .= '&filter_date_from=' . $this->request->get['filter_date_from'];
		}
		if (isset($this->request->get['filter_date_to'])) {
			$url .= '&filter_date_to=' . $this->request->get['filter_date_to'];
		}
		if (isset($this->request->get['filter_date_modified'])) {
			$url .= '&filter_date_modified=' . $this->request->get['filter_date_modified'];
		}

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $order_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('sale/order', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($order_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($order_total - $this->config->get('config_limit_admin'))) ? $order_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $order_total, ceil($order_total / $this->config->get('config_limit_admin')));

		$data['filter_order_id'] = $filter_order_id;
		$data['filter_customer'] = $filter_customer;
		$data['filter_order_status'] = $filter_order_status;
		$data['filter_order_status_id'] = $filter_order_status_id;
		$data['filter_total'] = $filter_total;
		$data['filter_date_to'] = $filter_date_to;
		$data['filter_date_from'] = $filter_date_from;
		$data['filter_date_modified'] = $filter_date_modified;
$data['filter_telephone'] = $filter_telephone;
$data['filter_payment_zone'] = $filter_payment_zone;
		$data['sort'] = $sort;
		$data['order'] = $order;

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		// API login
		$data['catalog'] = $this->request->server['HTTPS'] ? HTTPS_CATALOG : HTTP_CATALOG;

		// API login
		$this->load->model('user/api');

		$api_info = $this->model_user_api->getApi($this->config->get('config_api_id'));

		if ($api_info && $this->user->hasPermission('modify', 'sale/order')) {
			$session = new Session($this->config->get('session_engine'), $this->registry);

			$session->start();

			$this->model_user_api->deleteApiSessionBySessionId($session->getId());

			$this->model_user_api->addApiSession($api_info['api_id'], $session->getId(), $this->request->server['REMOTE_ADDR']);

			$session->data['api_id'] = $api_info['api_id'];

			$data['api_token'] = $session->getId();
		} else {
			$data['api_token'] = '';
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('sale/order_list', $data));
	}

	public function getForm() {
		$data['text_form'] = !isset($this->request->get['order_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

		$url = '';

		if (isset($this->request->get['filter_order_id'])) {
			$url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
		}

		if (isset($this->request->get['filter_customer'])) {
			$url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_order_status'])) {
			$url .= '&filter_order_status=' . $this->request->get['filter_order_status'];
		}

		if (isset($this->request->get['filter_order_status_id'])) {
			$url .= '&filter_order_status_id=' . $this->request->get['filter_order_status_id'];
		}

		if (isset($this->request->get['filter_total'])) {
			$url .= '&filter_total=' . $this->request->get['filter_total'];
		}

		if (isset($this->request->get['filter_date_added'])) {
			$url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
		}

		if (isset($this->request->get['filter_date_modified'])) {
			$url .= '&filter_date_modified=' . $this->request->get['filter_date_modified'];
		}

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('sale/order', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		$data['cancel'] = $this->url->link('sale/order', 'user_token=' . $this->session->data['user_token'] . $url, true);

		$data['user_token'] = $this->session->data['user_token'];

		if (isset($this->request->get['order_id'])) {
			$order_info = $this->model_sale_order->getOrder($this->request->get['order_id']);
		}

		if (!empty($order_info)) {
			$data['order_id'] = (int)$this->request->get['order_id'];
			$data['store_id'] = $order_info['store_id'];
			$data['store_url'] = $this->request->server['HTTPS'] ? HTTPS_CATALOG : HTTP_CATALOG;

			$data['customer'] = $order_info['customer'];
			$data['customer_id'] = $order_info['customer_id'];
			$data['customer_group_id'] = $order_info['customer_group_id'];
			$data['firstname'] = $order_info['firstname'];
			$data['lastname'] = $order_info['lastname'];
			$data['email'] = $order_info['email'];
			$data['telephone'] = $order_info['telephone'];
			$data['account_custom_field'] = $order_info['custom_field'];

			$this->load->model('customer/customer');

			$data['addresses'] = $this->model_customer_customer->getAddresses($order_info['customer_id']);

			$data['payment_firstname'] = $order_info['payment_firstname'];
			$data['payment_lastname'] = $order_info['payment_lastname'];
			$data['payment_company'] = $order_info['payment_company'];
			$data['payment_address_1'] = $order_info['payment_address_1'];
			$data['payment_address_2'] = $order_info['payment_address_2'];
			$data['payment_city'] = $order_info['payment_city'];
			$data['payment_postcode'] = $order_info['payment_postcode'];
			$data['payment_country_id'] = $order_info['payment_country_id'];
			$data['payment_zone_id'] = $order_info['payment_zone_id'];
			$data['payment_custom_field'] = $order_info['payment_custom_field'];
			$data['payment_method'] = $order_info['payment_method'];
			$data['payment_code'] = $order_info['payment_code'];

			$data['shipping_firstname'] = $order_info['shipping_firstname'];
			$data['shipping_lastname'] = $order_info['shipping_lastname'];
			$data['shipping_company'] = $order_info['shipping_company'];
			$data['shipping_address_1'] = $order_info['shipping_address_1'];
			$data['shipping_address_2'] = $order_info['shipping_address_2'];
			$data['shipping_city'] = $order_info['shipping_city'];
			$data['shipping_postcode'] = $order_info['shipping_postcode'];
			$data['shipping_country_id'] = $order_info['shipping_country_id'];
			$data['shipping_zone_id'] = $order_info['shipping_zone_id'];
			$data['shipping_custom_field'] = $order_info['shipping_custom_field'];
			$data['shipping_method'] = $order_info['shipping_method'];
			$data['shipping_code'] = $order_info['shipping_code'];

			// Products
			$data['order_products'] = array();

			$products = $this->model_sale_order->getOrderProducts($this->request->get['order_id']);

			foreach ($products as $product) {
				$data['order_products'][] = array(
					'product_id' => $product['product_id'],
					'name'       => $product['name'],
					'model'      => $product['model'],
					'option'     => $this->model_sale_order->getOrderOptions($this->request->get['order_id'], $product['order_product_id']),
					'quantity'   => $product['quantity'],
					'price'      => $product['price'],
					'total'      => $product['total'],
					'reward'     => $product['reward']
				);
			}

			// Vouchers
			$data['order_vouchers'] = $this->model_sale_order->getOrderVouchers($this->request->get['order_id']);

			$data['coupon'] = '';
			$data['voucher'] = '';
			$data['reward'] = '';

			$data['order_totals'] = array();

			$order_totals = $this->model_sale_order->getOrderTotals($this->request->get['order_id']);

			foreach ($order_totals as $order_total) {
				// If coupon, voucher or reward points
				$start = strpos($order_total['title'], '(') + 1;
				$end = strrpos($order_total['title'], ')');

				if ($start && $end) {
					$data[$order_total['code']] = substr($order_total['title'], $start, $end - $start);
				}
			}

			$data['order_status_id'] = $order_info['order_status_id'];
			$data['comment'] = $order_info['comment'];
			$data['affiliate_id'] = $order_info['affiliate_id'];
			$data['affiliate'] = $order_info['affiliate_firstname'] . ' ' . $order_info['affiliate_lastname'];
			$data['currency_code'] = $order_info['currency_code'];
		} else {
			$data['order_id'] = 0;
			$data['store_id'] = 0;
			$data['store_url'] = $this->request->server['HTTPS'] ? HTTPS_CATALOG : HTTP_CATALOG;

			$data['customer'] = '';
			$data['customer_id'] = '';
			$data['customer_group_id'] = $this->config->get('config_customer_group_id');
			$data['firstname'] = '';
			$data['lastname'] = '';
			$data['email'] = '';
			$data['telephone'] = '';
			$data['customer_custom_field'] = array();

			$data['addresses'] = array();

			$data['payment_firstname'] = '';
			$data['payment_lastname'] = '';
			$data['payment_company'] = '';
			$data['payment_address_1'] = '';
			$data['payment_address_2'] = '';
			$data['payment_city'] = '';
			$data['payment_postcode'] = '';
			$data['payment_country_id'] = '';
			$data['payment_zone_id'] = '';
			$data['payment_custom_field'] = array();
			$data['payment_method'] = '';
			$data['payment_code'] = '';

			$data['shipping_firstname'] = '';
			$data['shipping_lastname'] = '';
			$data['shipping_company'] = '';
			$data['shipping_address_1'] = '';
			$data['shipping_address_2'] = '';
			$data['shipping_city'] = '';
			$data['shipping_postcode'] = '';
			$data['shipping_country_id'] = '';
			$data['shipping_zone_id'] = '';
			$data['shipping_custom_field'] = array();
			$data['shipping_method'] = '';
			$data['shipping_code'] = '';

			$data['order_products'] = array();
			$data['order_vouchers'] = array();
			$data['order_totals'] = array();

			$data['order_status_id'] = $this->config->get('config_order_status_id');
			$data['comment'] = '';
			$data['affiliate_id'] = '';
			$data['affiliate'] = '';
			$data['currency_code'] = $this->config->get('config_currency');

			$data['coupon'] = '';
			$data['voucher'] = '';
			$data['reward'] = '';
		}

		// Stores
		$this->load->model('setting/store');

		$data['stores'] = array();

		$data['stores'][] = array(
			'store_id' => 0,
			'name'     => $this->language->get('text_default')
		);

		$results = $this->model_setting_store->getStores();

		foreach ($results as $result) {
			$data['stores'][] = array(
				'store_id' => $result['store_id'],
				'name'     => $result['name']
			);
		}

		// Customer Groups
		$this->load->model('customer/customer_group');

		$data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();

		// Custom Fields
		$this->load->model('customer/custom_field');
		$this->load->model('tool/upload');

		$data['custom_fields'] = array();

		$custom_field_locations = array(
			'account_custom_field',
			'payment_custom_field',
			'shipping_custom_field'
		);

		$filter_data = array(
			'sort'  => 'cf.sort_order',
			'order' => 'ASC'
		);

		$custom_fields = $this->model_customer_custom_field->getCustomFields($filter_data);

		foreach ($custom_fields as $custom_field) {
			$data['custom_fields'][] = array(
				'custom_field_id'    => $custom_field['custom_field_id'],
				'custom_field_value' => $this->model_customer_custom_field->getCustomFieldValues($custom_field['custom_field_id']),
				'name'               => $custom_field['name'],
				'value'              => $custom_field['value'],
				'type'               => $custom_field['type'],
				'location'           => $custom_field['location'],
				'sort_order'         => $custom_field['sort_order']
			);

			if($custom_field['type'] == 'file') {
				foreach($custom_field_locations as $location) {
					if(isset($data[$location][$custom_field['custom_field_id']])) {
						$code = $data[$location][$custom_field['custom_field_id']];

						$upload_result = $this->model_tool_upload->getUploadByCode($code);

						$data[$location][$custom_field['custom_field_id']] = array();
						if($upload_result) {
							$data[$location][$custom_field['custom_field_id']]['name'] = $upload_result['name'];
							$data[$location][$custom_field['custom_field_id']]['code'] = $upload_result['code'];
						} else {
							$data[$location][$custom_field['custom_field_id']]['name'] = "";
							$data[$location][$custom_field['custom_field_id']]['code'] = $code;
						}
					}
				}
			}
		}

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		$this->load->model('localisation/country');

		$data['countries'] = $this->model_localisation_country->getCountries();

		$this->load->model('localisation/currency');

		$data['currencies'] = $this->model_localisation_currency->getCurrencies();

		$data['voucher_min'] = $this->config->get('config_voucher_min');

		$this->load->model('sale/voucher_theme');

		$data['voucher_themes'] = $this->model_sale_voucher_theme->getVoucherThemes();

		// API login
		$data['catalog'] = $this->request->server['HTTPS'] ? HTTPS_CATALOG : HTTP_CATALOG;

		// API login
		$this->load->model('user/api');

		$api_info = $this->model_user_api->getApi($this->config->get('config_api_id'));

		if ($api_info && $this->user->hasPermission('modify', 'sale/order')) {
			$session = new Session($this->config->get('session_engine'), $this->registry);

			$session->start();

			$this->model_user_api->deleteApiSessionBySessionId($session->getId());

			$this->model_user_api->addApiSession($api_info['api_id'], $session->getId(), $this->request->server['REMOTE_ADDR']);

			$session->data['api_id'] = $api_info['api_id'];

			$data['api_token'] = $session->getId();
		} else {
			$data['api_token'] = '';
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('sale/order_form', $data));
	}

	public function info() {
		$this->load->model('sale/order');

		if (isset($this->request->get['order_id'])) {
			$order_id = $this->request->get['order_id'];
		} else {
			$order_id = 0;
		}

		$order_info = $this->model_sale_order->getOrder($order_id);

		if ($order_info) {
			$this->load->language('sale/order');

			$this->document->setTitle($this->language->get('heading_title'));

			$data['text_ip_add'] = sprintf($this->language->get('text_ip_add'), $this->request->server['REMOTE_ADDR']);
			$data['text_order'] = sprintf($this->language->get('text_order'), $this->request->get['order_id']);

			$url = '';

			if (isset($this->request->get['filter_order_id'])) {
				$url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
			}

			if (isset($this->request->get['filter_customer'])) {
				$url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['filter_order_status'])) {
				$url .= '&filter_order_status=' . $this->request->get['filter_order_status'];
			}

			if (isset($this->request->get['filter_order_status_id'])) {
				$url .= '&filter_order_status_id=' . $this->request->get['filter_order_status_id'];
			}

			if (isset($this->request->get['filter_total'])) {
				$url .= '&filter_total=' . $this->request->get['filter_total'];
			}

			if (isset($this->request->get['filter_date_added'])) {
				$url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
			}

			if (isset($this->request->get['filter_date_modified'])) {
				$url .= '&filter_date_modified=' . $this->request->get['filter_date_modified'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$data['breadcrumbs'] = array();

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
			);

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('sale/order', 'user_token=' . $this->session->data['user_token'] . $url, true)
			);

			$data['shipping'] = $this->url->link('sale/order/shipping', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . (int)$this->request->get['order_id'], true);
			$data['invoice'] = $this->url->link('sale/order/invoice', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . (int)$this->request->get['order_id'], true);
			$data['edit'] = $this->url->link('sale/order/edit', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . (int)$this->request->get['order_id'], true);
			$data['cancel'] = $this->url->link('sale/order', 'user_token=' . $this->session->data['user_token'] . $url, true);

			$data['user_token'] = $this->session->data['user_token'];

			$data['order_id'] = (int)$this->request->get['order_id'];

			$data['store_id'] = $order_info['store_id'];
			$data['store_name'] = $order_info['store_name'];

			if ($order_info['store_id'] == 0) {
				$data['store_url'] = $this->request->server['HTTPS'] ? HTTPS_CATALOG : HTTP_CATALOG;
			} else {
				$data['store_url'] = $order_info['store_url'];
			}

			if ($order_info['invoice_no']) {
				$data['invoice_no'] = $order_info['invoice_prefix'] . $order_info['invoice_no'];
			} else {
				$data['invoice_no'] = '';
			}

			$data['date_added'] = date($this->language->get('date_format_short'), strtotime($order_info['date_added']));

			$data['firstname'] = $order_info['firstname'];
			$data['lastname'] = $order_info['lastname'];

			if ($order_info['customer_id']) {
				$data['customer'] = $this->url->link('customer/customer/edit', 'user_token=' . $this->session->data['user_token'] . '&customer_id=' . $order_info['customer_id'], true);
			} else {
				$data['customer'] = '';
			}

			$this->load->model('customer/customer_group');

			$customer_group_info = $this->model_customer_customer_group->getCustomerGroup($order_info['customer_group_id']);

			if ($customer_group_info) {
				$data['customer_group'] = $customer_group_info['name'];
			} else {
				$data['customer_group'] = '';
			}

			$data['email'] = $order_info['email'];
			$data['telephone'] = $order_info['telephone'];

			$data['shipping_method'] = $order_info['shipping_method'];
			$data['payment_method'] = $order_info['payment_method'];

			// Payment Address
			if ($order_info['payment_address_format']) {
				$format = $order_info['payment_address_format'];
			} else {
				$format = '{firstname} {lastname}' . "\n" . '{company}' . "\n" . '{address_1}' . "\n" . '{address_2}' . "\n" . '{city} {postcode}' . "\n" . '{zone}' . "\n" . '{country}';
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

			$data['payment_address'] = str_replace(array("\r\n", "\r", "\n"), '<br />', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br />', trim(str_replace($find, $replace, $format))));

			// Shipping Address
			if ($order_info['shipping_address_format']) {
				$format = $order_info['shipping_address_format'];
			} else {
				$format = '{firstname} {lastname}' . "\n" . '{company}' . "\n" . '{address_1}' . "\n" . '{address_2}' . "\n" . '{city} {postcode}' . "\n" . '{zone}' . "\n" . '{country}';
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

			$data['shipping_address'] = str_replace(array("\r\n", "\r", "\n"), '<br />', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br />', trim(str_replace($find, $replace, $format))));

			// Uploaded files
			$this->load->model('tool/upload');

			$data['products'] = array();

			$products = $this->model_sale_order->getOrderProducts($this->request->get['order_id']);

			foreach ($products as $product) {
				$option_data = array();

				$options = $this->model_sale_order->getOrderOptions($this->request->get['order_id'], $product['order_product_id']);

				foreach ($options as $option) {
					if ($option['type'] != 'file') {
						$option_data[] = array(
							'name'  => $option['name'],
							'value' => $option['value'],
							'type'  => $option['type']
						);
					} else {
						$upload_info = $this->model_tool_upload->getUploadByCode($option['value']);

						if ($upload_info) {
							$option_data[] = array(
								'name'  => $option['name'],
								'value' => $upload_info['name'],
								'type'  => $option['type'],
								'href'  => $this->url->link('tool/upload/download', 'user_token=' . $this->session->data['user_token'] . '&code=' . $upload_info['code'], true)
							);
						}
					}
				}

				$data['products'][] = array(
					'order_product_id' => $product['order_product_id'],
					'product_id'       => $product['product_id'],
					'name'    	 	   => $product['name'],
					'model'    		   => $product['model'],
					'option'   		   => $option_data,
					'quantity'		   => $product['quantity'],
					'price'    		   => $this->currency->format($product['price'] + ($this->config->get('config_tax') ? $product['tax'] : 0), $order_info['currency_code'], $order_info['currency_value']),
					'total'    		   => $this->currency->format($product['total'] + ($this->config->get('config_tax') ? ($product['tax'] * $product['quantity']) : 0), $order_info['currency_code'], $order_info['currency_value']),
					'href'     		   => $this->url->link('catalog/product/edit', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $product['product_id'], true)
				);
			}

			$data['vouchers'] = array();

			$vouchers = $this->model_sale_order->getOrderVouchers($this->request->get['order_id']);

			foreach ($vouchers as $voucher) {
				$data['vouchers'][] = array(
					'description' => $voucher['description'],
					'amount'      => $this->currency->format($voucher['amount'], $order_info['currency_code'], $order_info['currency_value']),
					'href'        => $this->url->link('sale/voucher/edit', 'user_token=' . $this->session->data['user_token'] . '&voucher_id=' . $voucher['voucher_id'], true)
				);
			}

			$data['totals'] = array();

			$totals = $this->model_sale_order->getOrderTotals($this->request->get['order_id']);

			foreach ($totals as $total) {
				$data['totals'][] = array(
					'title' => $total['title'],
					'text'  => $this->currency->format($total['value'], $order_info['currency_code'], $order_info['currency_value'])
				);
			}

			$data['comment'] = nl2br($order_info['comment']);

			$this->load->model('customer/customer');

			$data['reward'] = $order_info['reward'];

			$data['reward_total'] = $this->model_customer_customer->getTotalCustomerRewardsByOrderId($this->request->get['order_id']);

			$data['affiliate_firstname'] = $order_info['affiliate_firstname'];
			$data['affiliate_lastname'] = $order_info['affiliate_lastname'];

			if ($order_info['affiliate_id']) {
				$data['affiliate'] = $this->url->link('customer/customer/edit', 'user_token=' . $this->session->data['user_token'] . '&customer_id=' . $order_info['affiliate_id'], true);
			} else {
				$data['affiliate'] = '';
			}

			$data['commission'] = $this->currency->format($order_info['commission'], $order_info['currency_code'], $order_info['currency_value']);

			$data['commission_total'] = $this->model_customer_customer->getTotalTransactionsByOrderId($this->request->get['order_id']);

			$this->load->model('localisation/order_status');

			$order_status_info = $this->model_localisation_order_status->getOrderStatus($order_info['order_status_id']);

			if ($order_status_info) {
				$data['order_status'] = $order_status_info['name'];
			} else {
				$data['order_status'] = '';
			}

			$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

			$data['order_status_id'] = $order_info['order_status_id'];

			$data['account_custom_field'] = $order_info['custom_field'];

			// Custom Fields
			$this->load->model('customer/custom_field');

			$data['account_custom_fields'] = array();

			$filter_data = array(
				'sort'  => 'cf.sort_order',
				'order' => 'ASC'
			);

			$custom_fields = $this->model_customer_custom_field->getCustomFields($filter_data);

			foreach ($custom_fields as $custom_field) {
				if ($custom_field['location'] == 'account' && isset($order_info['custom_field'][$custom_field['custom_field_id']])) {
					if ($custom_field['type'] == 'select' || $custom_field['type'] == 'radio') {
						$custom_field_value_info = $this->model_customer_custom_field->getCustomFieldValue($order_info['custom_field'][$custom_field['custom_field_id']]);

						if ($custom_field_value_info) {
							$data['account_custom_fields'][] = array(
								'name'  => $custom_field['name'],
								'value' => $custom_field_value_info['name']
							);
						}
					}

					if ($custom_field['type'] == 'checkbox' && is_array($order_info['custom_field'][$custom_field['custom_field_id']])) {
						foreach ($order_info['custom_field'][$custom_field['custom_field_id']] as $custom_field_value_id) {
							$custom_field_value_info = $this->model_customer_custom_field->getCustomFieldValue($custom_field_value_id);

							if ($custom_field_value_info) {
								$data['account_custom_fields'][] = array(
									'name'  => $custom_field['name'],
									'value' => $custom_field_value_info['name']
								);
							}
						}
					}

					if ($custom_field['type'] == 'text' || $custom_field['type'] == 'textarea' || $custom_field['type'] == 'file' || $custom_field['type'] == 'date' || $custom_field['type'] == 'datetime' || $custom_field['type'] == 'time') {
						$data['account_custom_fields'][] = array(
							'name'  => $custom_field['name'],
							'value' => $order_info['custom_field'][$custom_field['custom_field_id']]
						);
					}

					if ($custom_field['type'] == 'file') {
						$upload_info = $this->model_tool_upload->getUploadByCode($order_info['custom_field'][$custom_field['custom_field_id']]);

						if ($upload_info) {
							$data['account_custom_fields'][] = array(
								'name'  => $custom_field['name'],
								'value' => $upload_info['name']
							);
						}
					}
				}
			}

			// Custom fields
			$data['payment_custom_fields'] = array();

			foreach ($custom_fields as $custom_field) {
				if ($custom_field['location'] == 'address' && isset($order_info['payment_custom_field'][$custom_field['custom_field_id']])) {
					if ($custom_field['type'] == 'select' || $custom_field['type'] == 'radio') {
						$custom_field_value_info = $this->model_customer_custom_field->getCustomFieldValue($order_info['payment_custom_field'][$custom_field['custom_field_id']]);

						if ($custom_field_value_info) {
							$data['payment_custom_fields'][] = array(
								'name'  => $custom_field['name'],
								'value' => $custom_field_value_info['name'],
								'sort_order' => $custom_field['sort_order']
							);
						}
					}

					if ($custom_field['type'] == 'checkbox' && is_array($order_info['payment_custom_field'][$custom_field['custom_field_id']])) {
						foreach ($order_info['payment_custom_field'][$custom_field['custom_field_id']] as $custom_field_value_id) {
							$custom_field_value_info = $this->model_customer_custom_field->getCustomFieldValue($custom_field_value_id);

							if ($custom_field_value_info) {
								$data['payment_custom_fields'][] = array(
									'name'  => $custom_field['name'],
									'value' => $custom_field_value_info['name'],
									'sort_order' => $custom_field['sort_order']
								);
							}
						}
					}

					if ($custom_field['type'] == 'text' || $custom_field['type'] == 'textarea' || $custom_field['type'] == 'file' || $custom_field['type'] == 'date' || $custom_field['type'] == 'datetime' || $custom_field['type'] == 'time') {
						$data['payment_custom_fields'][] = array(
							'name'  => $custom_field['name'],
							'value' => $order_info['payment_custom_field'][$custom_field['custom_field_id']],
							'sort_order' => $custom_field['sort_order']
						);
					}

					if ($custom_field['type'] == 'file') {
						$upload_info = $this->model_tool_upload->getUploadByCode($order_info['payment_custom_field'][$custom_field['custom_field_id']]);

						if ($upload_info) {
							$data['payment_custom_fields'][] = array(
								'name'  => $custom_field['name'],
								'value' => $upload_info['name'],
								'sort_order' => $custom_field['sort_order']
							);
						}
					}
				}
			}

			// Shipping
			$data['shipping_custom_fields'] = array();

			foreach ($custom_fields as $custom_field) {
				if ($custom_field['location'] == 'address' && isset($order_info['shipping_custom_field'][$custom_field['custom_field_id']])) {
					if ($custom_field['type'] == 'select' || $custom_field['type'] == 'radio') {
						$custom_field_value_info = $this->model_customer_custom_field->getCustomFieldValue($order_info['shipping_custom_field'][$custom_field['custom_field_id']]);

						if ($custom_field_value_info) {
							$data['shipping_custom_fields'][] = array(
								'name'  => $custom_field['name'],
								'value' => $custom_field_value_info['name'],
								'sort_order' => $custom_field['sort_order']
							);
						}
					}

					if ($custom_field['type'] == 'checkbox' && is_array($order_info['shipping_custom_field'][$custom_field['custom_field_id']])) {
						foreach ($order_info['shipping_custom_field'][$custom_field['custom_field_id']] as $custom_field_value_id) {
							$custom_field_value_info = $this->model_customer_custom_field->getCustomFieldValue($custom_field_value_id);

							if ($custom_field_value_info) {
								$data['shipping_custom_fields'][] = array(
									'name'  => $custom_field['name'],
									'value' => $custom_field_value_info['name'],
									'sort_order' => $custom_field['sort_order']
								);
							}
						}
					}

					if ($custom_field['type'] == 'text' || $custom_field['type'] == 'textarea' || $custom_field['type'] == 'file' || $custom_field['type'] == 'date' || $custom_field['type'] == 'datetime' || $custom_field['type'] == 'time') {
						$data['shipping_custom_fields'][] = array(
							'name'  => $custom_field['name'],
							'value' => $order_info['shipping_custom_field'][$custom_field['custom_field_id']],
							'sort_order' => $custom_field['sort_order']
						);
					}

					if ($custom_field['type'] == 'file') {
						$upload_info = $this->model_tool_upload->getUploadByCode($order_info['shipping_custom_field'][$custom_field['custom_field_id']]);

						if ($upload_info) {
							$data['shipping_custom_fields'][] = array(
								'name'  => $custom_field['name'],
								'value' => $upload_info['name'],
								'sort_order' => $custom_field['sort_order']
							);
						}
					}
				}
			}

			$data['ip'] = $order_info['ip'];
			$data['forwarded_ip'] = $order_info['forwarded_ip'];
			$data['user_agent'] = $order_info['user_agent'];
			$data['accept_language'] = $order_info['accept_language'];

			// Additional Tabs
			$data['tabs'] = array();

			if ($this->user->hasPermission('access', 'extension/payment/' . $order_info['payment_code'])) {
				if (is_file(DIR_CATALOG . 'controller/extension/payment/' . $order_info['payment_code'] . '.php')) {
					$content = $this->load->controller('extension/payment/' . $order_info['payment_code'] . '/order');
				} else {
					$content = '';
				}

				if ($content) {
					$this->load->language('extension/payment/' . $order_info['payment_code']);

					$data['tabs'][] = array(
						'code'    => $order_info['payment_code'],
						'title'   => $this->language->get('heading_title'),
						'content' => $content
					);
				}
			}

			$this->load->model('setting/extension');

			$extensions = $this->model_setting_extension->getInstalled('fraud');

			foreach ($extensions as $extension) {
				if ($this->config->get('fraud_' . $extension . '_status')) {
					$this->load->language('extension/fraud/' . $extension, 'extension');

					$content = $this->load->controller('extension/fraud/' . $extension . '/order');

					if ($content) {
						$data['tabs'][] = array(
							'code'    => $extension,
							'title'   => $this->language->get('extension')->get('heading_title'),
							'content' => $content
						);
					}
				}
			}

			// The URL we send API requests to
			$data['catalog'] = $this->request->server['HTTPS'] ? HTTPS_CATALOG : HTTP_CATALOG;

			// API login
			$this->load->model('user/api');

			$api_info = $this->model_user_api->getApi($this->config->get('config_api_id'));

			if ($api_info && $this->user->hasPermission('modify', 'sale/order')) {
				$session = new Session($this->config->get('session_engine'), $this->registry);

				$session->start();

				$this->model_user_api->deleteApiSessionBySessionId($session->getId());

				$this->model_user_api->addApiSession($api_info['api_id'], $session->getId(), $this->request->server['REMOTE_ADDR']);

				$session->data['api_id'] = $api_info['api_id'];

				$data['api_token'] = $session->getId();
			} else {
				$data['api_token'] = '';
			}

			$data['header'] = $this->load->controller('common/header');
			$data['column_left'] = $this->load->controller('common/column_left');
			$data['footer'] = $this->load->controller('common/footer');

			$this->response->setOutput($this->load->view('sale/order_info', $data));
		} else {
			return new Action('error/not_found');
		}
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'sale/order')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	public function createInvoiceNo() {
		$this->load->language('sale/order');

		$json = array();

		if (!$this->user->hasPermission('modify', 'sale/order')) {
			$json['error'] = $this->language->get('error_permission');
		} elseif (isset($this->request->get['order_id'])) {
			if (isset($this->request->get['order_id'])) {
				$order_id = $this->request->get['order_id'];
			} else {
				$order_id = 0;
			}

			$this->load->model('sale/order');

			$invoice_no = $this->model_sale_order->createInvoiceNo($order_id);

			if ($invoice_no) {
				$json['invoice_no'] = $invoice_no;
			} else {
				$json['error'] = $this->language->get('error_action');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function addReward() {
		$this->load->language('sale/order');

		$json = array();

		if (!$this->user->hasPermission('modify', 'sale/order')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			if (isset($this->request->get['order_id'])) {
				$order_id = $this->request->get['order_id'];
			} else {
				$order_id = 0;
			}

			$this->load->model('sale/order');

			$order_info = $this->model_sale_order->getOrder($order_id);

			if ($order_info && $order_info['customer_id'] && ($order_info['reward'] > 0)) {
				$this->load->model('customer/customer');

				$reward_total = $this->model_customer_customer->getTotalCustomerRewardsByOrderId($order_id);

				if (!$reward_total) {
					$this->model_customer_customer->addReward($order_info['customer_id'], $this->language->get('text_order_id') . ' #' . $order_id, $order_info['reward'], $order_id);
				}
			}

			$json['success'] = $this->language->get('text_reward_added');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function removeReward() {
		$this->load->language('sale/order');

		$json = array();

		if (!$this->user->hasPermission('modify', 'sale/order')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			if (isset($this->request->get['order_id'])) {
				$order_id = $this->request->get['order_id'];
			} else {
				$order_id = 0;
			}

			$this->load->model('sale/order');

			$order_info = $this->model_sale_order->getOrder($order_id);

			if ($order_info) {
				$this->load->model('customer/customer');

				$this->model_customer_customer->deleteReward($order_id);
			}

			$json['success'] = $this->language->get('text_reward_removed');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function addCommission() {
		$this->load->language('sale/order');

		$json = array();

		if (!$this->user->hasPermission('modify', 'sale/order')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			if (isset($this->request->get['order_id'])) {
				$order_id = $this->request->get['order_id'];
			} else {
				$order_id = 0;
			}

			$this->load->model('sale/order');

			$order_info = $this->model_sale_order->getOrder($order_id);

			if ($order_info) {
				$this->load->model('customer/customer');

				$affiliate_total = $this->model_customer_customer->getTotalTransactionsByOrderId($order_id);

				if (!$affiliate_total) {
					$this->model_customer_customer->addTransaction($order_info['affiliate_id'], $this->language->get('text_order_id') . ' #' . $order_id, $order_info['commission'], $order_id);
				}
			}

			$json['success'] = $this->language->get('text_commission_added');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function removeCommission() {
		$this->load->language('sale/order');

		$json = array();

		if (!$this->user->hasPermission('modify', 'sale/order')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			if (isset($this->request->get['order_id'])) {
				$order_id = $this->request->get['order_id'];
			} else {
				$order_id = 0;
			}

			$this->load->model('sale/order');

			$order_info = $this->model_sale_order->getOrder($order_id);

			if ($order_info) {
				$this->load->model('customer/customer');

				$this->model_customer_customer->deleteTransactionByOrderId($order_id);
			}

			$json['success'] = $this->language->get('text_commission_removed');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function history() {
		$this->load->language('sale/order');

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		$data['histories'] = array();

		$this->load->model('sale/order');

		$results = $this->model_sale_order->getOrderHistories($this->request->get['order_id'], ($page - 1) * 10, 10);

		foreach ($results as $result) {
			$data['histories'][] = array(
				'notify'     => $result['notify'] ? $this->language->get('text_yes') : $this->language->get('text_no'),
				'status'     => $result['status'],
				'comment'    => nl2br($result['comment']),
				'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added']))
			);
		}

		$history_total = $this->model_sale_order->getTotalOrderHistories($this->request->get['order_id']);

		$pagination = new Pagination();
		$pagination->total = $history_total;
		$pagination->page = $page;
		$pagination->limit = 10;
		$pagination->url = $this->url->link('sale/order/history', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $this->request->get['order_id'] . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($history_total) ? (($page - 1) * 10) + 1 : 0, ((($page - 1) * 10) > ($history_total - 10)) ? $history_total : ((($page - 1) * 10) + 10), $history_total, ceil($history_total / 10));

		$this->response->setOutput($this->load->view('sale/order_history', $data));
	}

	public function invoice() {
		$this->load->language('sale/order');

		$data['title'] = $this->language->get('text_invoice');

		if ($this->request->server['HTTPS']) {
			$data['base'] = HTTPS_SERVER;
		} else {
			$data['base'] = HTTP_SERVER;
		}

		$data['direction'] = $this->language->get('direction');

		$data['lang'] = $this->language->get('code');

		$this->load->model('sale/order');

		$this->load->model('setting/setting');

		$data['orders'] = array();

		$orders = array();

		if (isset($this->request->post['selected'])) {
			$orders = $this->request->post['selected'];
		} elseif (isset($this->request->get['order_id'])) {
			$orders[] = $this->request->get['order_id'];
		}

		foreach ($orders as $order_id) {
			$order_info = $this->model_sale_order->getOrder($order_id);

			$text_order = sprintf($this->language->get('text_order'), $order_id);

			if ($order_info) {
				$store_info = $this->model_setting_setting->getSetting('config', $order_info['store_id']);

				if ($store_info) {
					$store_address = $store_info['config_address'];
					$store_email = $store_info['config_email'];
					$store_telephone = $store_info['config_telephone'];
					$store_fax = $store_info['config_fax'];
				} else {
					$store_address = $this->config->get('config_address');
					$store_email = $this->config->get('config_email');
					$store_telephone = $this->config->get('config_telephone');
					$store_fax = $this->config->get('config_fax');
				}

				if ($order_info['invoice_no']) {
					$invoice_no = $order_info['invoice_prefix'] . $order_info['invoice_no'];
				} else {
					$invoice_no = '';
				}

				if ($order_info['payment_address_format']) {
					$format = $order_info['payment_address_format'];
				} else {
					$format = '{firstname} {lastname}' . "\n" . '{company}' . "\n" . '{address_1}' . "\n" . '{address_2}' . "\n" . '{city} {postcode}' . "\n" . '{zone}' . "\n" . '{country}';
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

				$payment_address = str_replace(array("\r\n", "\r", "\n"), '<br />', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br />', trim(str_replace($find, $replace, $format))));

				if ($order_info['shipping_address_format']) {
					$format = $order_info['shipping_address_format'];
				} else {
					$format = '{firstname} {lastname}' . "\n" . '{company}' . "\n" . '{address_1}' . "\n" . '{address_2}' . "\n" . '{city} {postcode}' . "\n" . '{zone}' . "\n" . '{country}';
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

				$shipping_address = str_replace(array("\r\n", "\r", "\n"), '<br />', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br />', trim(str_replace($find, $replace, $format))));

				$this->load->model('tool/upload');

				$product_data = array();

				$products = $this->model_sale_order->getOrderProducts($order_id);

				foreach ($products as $product) {
					$option_data = array();

					$options = $this->model_sale_order->getOrderOptions($order_id, $product['order_product_id']);

					foreach ($options as $option) {
						if ($option['type'] != 'file') {
							$value = $option['value'];
						} else {
							$upload_info = $this->model_tool_upload->getUploadByCode($option['value']);

							if ($upload_info) {
								$value = $upload_info['name'];
							} else {
								$value = '';
							}
						}

						$option_data[] = array(
							'name'  => $option['name'],
							'value' => $value
						);
					}

					$product_data[] = array(
						'name'     => $product['name'],
						'model'    => $product['model'],
						'option'   => $option_data,
						'quantity' => $product['quantity'],
						'price'    => $this->currency->format($product['price'] + ($this->config->get('config_tax') ? $product['tax'] : 0), $order_info['currency_code'], $order_info['currency_value']),
						'total'    => $this->currency->format($product['total'] + ($this->config->get('config_tax') ? ($product['tax'] * $product['quantity']) : 0), $order_info['currency_code'], $order_info['currency_value'])
					);
				}

				$voucher_data = array();

				$vouchers = $this->model_sale_order->getOrderVouchers($order_id);

				foreach ($vouchers as $voucher) {
					$voucher_data[] = array(
						'description' => $voucher['description'],
						'amount'      => $this->currency->format($voucher['amount'], $order_info['currency_code'], $order_info['currency_value'])
					);
				}

				$total_data = array();

				$totals = $this->model_sale_order->getOrderTotals($order_id);

				foreach ($totals as $total) {
					$total_data[] = array(
						'title' => $total['title'],
						'text'  => $this->currency->format($total['value'], $order_info['currency_code'], $order_info['currency_value'])
					);
				}

				$data['orders'][] = array(
					'order_id'	   => $order_id,
					'invoice_no'       => $invoice_no,
					'text_order'	   => $text_order,
					'date_added'       => date($this->language->get('date_format_short'), strtotime($order_info['date_added'])),
					'store_name'       => $order_info['store_name'],
					'store_url'        => rtrim($order_info['store_url'], '/'),
					'store_address'    => nl2br($store_address),
					'store_email'      => $store_email,
					'store_telephone'  => $store_telephone,
					'store_fax'        => $store_fax,
					'email'            => $order_info['email'],
					'telephone'        => $order_info['telephone'],
					'shipping_address' => $shipping_address,
					'shipping_method'  => $order_info['shipping_method'],
					'payment_address'  => $payment_address,
					'payment_method'   => $order_info['payment_method'],
					'product'          => $product_data,
					'voucher'          => $voucher_data,
					'total'            => $total_data,
					'comment'          => nl2br($order_info['comment'])
				);
			}
		}

		$this->response->setOutput($this->load->view('sale/order_invoice', $data));
	}

	public function shipping() {
		$this->load->language('sale/order');

		$data['title'] = $this->language->get('text_shipping');

		if ($this->request->server['HTTPS']) {
			$data['base'] = HTTPS_SERVER;
		} else {
			$data['base'] = HTTP_SERVER;
		}

		$data['direction'] = $this->language->get('direction');
		$data['lang'] = $this->language->get('code');

		$this->load->model('sale/order');

		$this->load->model('catalog/product');

		$this->load->model('setting/setting');

		$data['orders'] = array();

		$orders = array();

		if (isset($this->request->post['selected'])) {
			$orders = $this->request->post['selected'];
		} elseif (isset($this->request->get['order_id'])) {
			$orders[] = $this->request->get['order_id'];
		}

		foreach ($orders as $order_id) {
			$order_info = $this->model_sale_order->getOrder($order_id);

			// Make sure there is a shipping method
			if ($order_info && $order_info['shipping_code']) {
				$store_info = $this->model_setting_setting->getSetting('config', $order_info['store_id']);

				if ($store_info) {
					$store_address = $store_info['config_address'];
					$store_email = $store_info['config_email'];
					$store_telephone = $store_info['config_telephone'];
				} else {
					$store_address = $this->config->get('config_address');
					$store_email = $this->config->get('config_email');
					$store_telephone = $this->config->get('config_telephone');
				}

				if ($order_info['invoice_no']) {
					$invoice_no = $order_info['invoice_prefix'] . $order_info['invoice_no'];
				} else {
					$invoice_no = '';
				}

				if ($order_info['shipping_address_format']) {
					$format = $order_info['shipping_address_format'];
				} else {
					$format = '{firstname} {lastname}' . "\n" . '{company}' . "\n" . '{address_1}' . "\n" . '{address_2}' . "\n" . '{city} {postcode}' . "\n" . '{zone}' . "\n" . '{country}';
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

				$shipping_address = str_replace(array("\r\n", "\r", "\n"), '<br />', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br />', trim(str_replace($find, $replace, $format))));

				$this->load->model('tool/upload');

				$product_data = array();

				$products = $this->model_sale_order->getOrderProducts($order_id);

				foreach ($products as $product) {
					$option_weight = 0;

					$product_info = $this->model_catalog_product->getProduct($product['product_id']);

					if ($product_info) {
						$option_data = array();

						$options = $this->model_sale_order->getOrderOptions($order_id, $product['order_product_id']);

						foreach ($options as $option) {
							if ($option['type'] != 'file') {
								$value = $option['value'];
							} else {
								$upload_info = $this->model_tool_upload->getUploadByCode($option['value']);

								if ($upload_info) {
									$value = $upload_info['name'];
								} else {
									$value = '';
								}
							}

							$option_data[] = array(
								'name'  => $option['name'],
								'value' => $value
							);

							$product_option_value_info = $this->model_catalog_product->getProductOptionValue($product['product_id'], $option['product_option_value_id']);

							if (!empty($product_option_value_info['weight'])) {
								if ($product_option_value_info['weight_prefix'] == '+') {
									$option_weight += $product_option_value_info['weight'];
								} elseif ($product_option_value_info['weight_prefix'] == '-') {
									$option_weight -= $product_option_value_info['weight'];
								}
							}
						}

						$product_data[] = array(
							'name'     => $product_info['name'],
							'model'    => $product_info['model'],
							'option'   => $option_data,
							'quantity' => $product['quantity'],
							'location' => $product_info['location'],
							'sku'      => $product_info['sku'],
							'upc'      => $product_info['upc'],
							'ean'      => $product_info['ean'],
							'jan'      => $product_info['jan'],
							'isbn'     => $product_info['isbn'],
							'mpn'      => $product_info['mpn'],
							'weight'   => $this->weight->format(($product_info['weight'] + (float)$option_weight) * $product['quantity'], $product_info['weight_class_id'], $this->language->get('decimal_point'), $this->language->get('thousand_point'))
						);
					}
				}

				$data['orders'][] = array(
					'order_id'	       => $order_id,
					'invoice_no'       => $invoice_no,
					'date_added'       => date($this->language->get('date_format_short'), strtotime($order_info['date_added'])),
					'store_name'       => $order_info['store_name'],
					'store_url'        => rtrim($order_info['store_url'], '/'),
					'store_address'    => nl2br($store_address),
					'store_email'      => $store_email,
					'store_telephone'  => $store_telephone,
					'email'            => $order_info['email'],
					'telephone'        => $order_info['telephone'],
					'shipping_address' => $shipping_address,
					'shipping_method'  => $order_info['shipping_method'],
					'product'          => $product_data,
					'comment'          => nl2br($order_info['comment'])
				);
			}
		}

		$this->response->setOutput($this->load->view('sale/order_shipping', $data));
	}

/*

تغيير الاعدادات للدمج مع بنك qnp

public function paymentlink() {

		$this->load->model('catalog/product');
		$this->load->model('setting/setting');
        $this->load->model('sale/order');
        $expiryDateTime = date('Y-m-d\TH:i:s.v\Z', strtotime('+2 month'));
        $order_id = $this->request->get['order_id'];
        $order_info = $this->model_sale_order->getOrder($order_id);
if($order_info){
            $this->load->model('sale/order');

        $checkifhavelink = $this->model_sale_order->getpaymentlink($order_id);
if(!$checkifhavelink){
        $bankCharges = $order_info['total'] * 0.02;
        $orderTotalWithCharges = $order_info['total'] + $bankCharges;
        $fraction = $orderTotalWithCharges - floor($orderTotalWithCharges);

        // قائمة الكسور المقبولة
        $allowedFractions = [0.00, 0.25, 0.50, 0.75];

        // التحقق مما إذا كان الكسر موجودًا ضمن الكسور المقبولة
        if (!in_array($fraction, $allowedFractions)) {
            // إذا لم يكن الكسر موجودًا، نقوم بتقريب القيمة إلى أقرب ربع
            $orderTotalWithCharges = floor($orderTotalWithCharges) + round($fraction * 4) / 4;
        }

		$customerEmail = 'MANDATORY';//$order_info['email'];
		$orderTotalAmount = $orderTotalWithCharges;//$order_info['total'];
		$customerTelephone = 'MANDATORY';//$order_info['telephone'];
		$customerAdress = 'MANDATORY';//$order_info['shipping_address_1'].' - '.$order_info['shipping_city'].' - '.$order_info['shipping_zone'];
$orderDescription = 'سداد قيمة الطلب رقم : '.$order_id.' - ';
$orderDescription .= 'المنتجات : ';

				$products = $this->model_sale_order->getOrderProducts($order_id);

$first_product = true;

foreach ($products as $product) {
    $product_info = $this->model_catalog_product->getProduct($product['product_id']);

    if (!$first_product) {
        $orderDescription .= 'و';
    } else {
        $first_product = false;
    }

    $orderDescription .= $product_info['name'];
}
    // قم بتجميع البيانات اللازمة لإنشاء ال JSON
    $json_data = array(
        "apiOperation" => "INITIATE_CHECKOUT",
        "checkoutMode" => "PAYMENT_LINK",
        "paymentLink" => array(
            "expiryDateTime" => $expiryDateTime,
            "numberOfAllowedAttempts" => "20",
        ),
        "interaction" => array(
            "displayControl" => array(
                "billingAddress" => $customerAdress,
                "customerEmail" => $customerEmail
            ),
            "operation" => "PURCHASE",
            "merchant" => array(
                "name" => "TECHSHOP",//will change on live
                "url" => "https://www.techshopeg.com",
                "logo" => "https://techshopeg.com/image/cache/catalog/logo2023/logo2023-1480x1272.png.webp"
            )
        ),
        "order" => array(
            "currency" => "EGP",
            "id" => $order_id,
            "description" => $orderDescription,
            "amount" => $orderTotalAmount
        )
    );


    // تحويل البيانات إلى JSON
    $json_string = json_encode($json_data);

    // إعداد إعدادات cURL
    $curl_options = array(
        CURLOPT_URL => "https://qnbalahli.gateway.mastercard.com/api/rest/version/67/merchant/TECHSHOP/session",
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_USERPWD => "merchant.TECHSHOP:afe4494f6c06dded9f6bb370b87a00fb",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $json_string,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($json_string)
        )
    );

    // إنشاء مقبض cURL وتنفيذ الطلب
    $ch = curl_init();
    curl_setopt_array($ch, $curl_options);
    $response = curl_exec($ch);
// Close the cURL session
curl_close($ch);

    // تحويل الرد من صيغة JSON إلى مصفوفة
    $response_array = json_decode($response, true);

    // استخراج رابط الدفع من الرد
    $payment_link = $response_array['paymentLink']['url'];

    // تمرير رابط الدفع إلى القالب Twig لعرضه
    $data['payment_link'] = $payment_link;
    $data['payment_dlink'] = 'https://techshopeg.com/pay?order='.$order_info['order_id'];
    $data['customerEmail'] = $order_info['email'];
    $data['customerTelephone'] = $order_info['telephone'];
    $data['order_id'] = $order_info['order_id'];
    			$data['header'] = $this->load->controller('common/header');
			$data['column_left'] = $this->load->controller('common/column_left');
			$data['footer'] = $this->load->controller('common/footer');

//حفظ في الداتا بيز
if(!empty($payment_link)){
$savepaymentlink = $this->model_sale_order->savepaymentlink($order_id,$payment_link);

	$this->response->setOutput($this->load->view('sale/order_paymentlink', $data));
}else{

}



}else{
    $data['payment_dlink'] = 'https://techshopeg.com/pay?order='.$order_info['order_id'];
    $data['payment_link'] = $checkifhavelink['paymentlink'];
    $data['order_id'] = $order_info['order_id'];
    $data['customerEmail'] = $order_info['email'];
    $data['customerTelephone'] = $order_info['telephone'];
			$data['header'] = $this->load->controller('common/header');
			$data['column_left'] = $this->load->controller('common/column_left');
			$data['footer'] = $this->load->controller('common/footer');

	$this->response->setOutput($this->load->view('sale/order_paymentlink', $data));


}

}

}

	public function addpaymentform() {
		$data['text_form'] = 'توليد رابط دفع';
		$data['title'] = 'توليد رابط دفع';
		$this->document->setTitle('توليد رابط دفع');

		$data['user_token'] = $this->session->data['user_token'];
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$data['action'] = $this->url->link('sale/order/funcsavepaymentlink', 'user_token=' . $this->session->data['user_token'], true);
		$data['error_warning'] = $this->session->data['error_warning'];
		$data['success'] = $this->session->data['success'];
  		$this->response->setOutput($this->load->view('sale/paymentlink_form', $data));



	}
	public function addpaymentlink() {
		$data['text_form'] = 'توليد رابط دفع';
		$data['title'] = 'توليد رابط دفع';
		$this->document->setTitle('توليد رابط دفع');
		$this->addpaymentform();
	}
	public function funcsavepaymentlink() {
		if (($this->request->server['REQUEST_METHOD'] != 'POST')) {
	        $this->session->data['success'] = '';
			$this->session->data['error_warning'] = 'وصول غير صالح';
			$this->response->redirect($this->url->link('sale/order/addpaymentlink', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		if (($this->request->server['REQUEST_METHOD'] == 'POST')) {
		    if($this->validatepaymenylinkForm($this->request->post)){

                $data = $this->request->post;
        		$this->load->model('sale/order');
                $expiryDateTime = date('Y-m-d\TH:i:s.v\Z', strtotime('+2 month'));
    			$this->session->data['success'] = 'تم التنفيذ بنجاح';
    			$order_id = $data['order_id'];
                $order_info = $this->model_sale_order->getOrder($order_id);

                $checkifhavelink = $this->model_sale_order->getpaymentlink($order_id);
                if(!$checkifhavelink){
                		$customerEmail = 'MANDATORY';//$order_info['email'];
                        $bankCharges = $data['order_total'] * 0.02;
                        $orderTotalWithCharges = $data['order_total'] + $bankCharges;
                        $fraction = $orderTotalWithCharges - floor($orderTotalWithCharges);

                        // قائمة الكسور المقبولة
                        $allowedFractions = [0.00, 0.25, 0.50, 0.75];

                        // التحقق مما إذا كان الكسر موجودًا ضمن الكسور المقبولة
                        if (!in_array($fraction, $allowedFractions)) {
                            // إذا لم يكن الكسر موجودًا، نقوم بتقريب القيمة إلى أقرب ربع
                            $orderTotalWithCharges = floor($orderTotalWithCharges) + round($fraction * 4) / 4;
                        }
                		$orderTotalAmount = $orderTotalWithCharges;//$data['order_total'];
                		$customerTelephone = 'MANDATORY';//$order_info['telephone'];
                		$customerAdress = 'MANDATORY';//$order_info['shipping_address_1'].' - '.$order_info['shipping_city'].' - '.$order_info['shipping_zone'];
                        $orderDescription = 'سداد قيمة الطلب رقم : '.$data['order_id'];
                            // قم بتجميع البيانات اللازمة لإنشاء ال JSON
                        if(isset($data['order_desc'])){
                            if(!empty($data['order_desc'])){
                               $orderDescription .=  ' وذلك عن : ';
                               $orderDescription .=  $data['order_desc'];
                            }
                        }
                    $json_data = array(
                        "apiOperation" => "INITIATE_CHECKOUT",
                        "checkoutMode" => "PAYMENT_LINK",
                        "paymentLink" => array(
                            "expiryDateTime" => $expiryDateTime,
                            "numberOfAllowedAttempts" => "20",
                        ),
                        "interaction" => array(
                            "displayControl" => array(
                                "billingAddress" => $customerAdress,
                                "customerEmail" => $customerEmail
                            ),
                            "operation" => "PURCHASE",
                            "merchant" => array(
                                "name" => "TECHSHOP",//will change on live
                                "url" => "https://www.techshopeg.com",
                                "logo" => "https://techshopeg.com/image/cache/catalog/logo2023/logo2023-1480x1272.png.webp"
                            )
                        ),
                        "order" => array(
                            "currency" => $data['order_currency'],
                            "id" => $data['order_id'],
                            "description" => $orderDescription,
                            "amount" => $orderTotalWithCharges
                        )
                    );

                    // تحويل البيانات إلى JSON
                    $json_string = json_encode($json_data);

                    // إعداد إعدادات cURL
                    $curl_options = array(
                        CURLOPT_URL => "https://qnbalahli.gateway.mastercard.com/api/rest/version/67/merchant/TECHSHOP/session",
                        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
                        CURLOPT_USERPWD => "merchant.TECHSHOP:afe4494f6c06dded9f6bb370b87a00fb",
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_POST => true,
                        CURLOPT_POSTFIELDS => $json_string,
                        CURLOPT_HTTPHEADER => array(
                            'Content-Type: application/json',
                            'Content-Length: ' . strlen($json_string)
                        )
                    );

                    // إنشاء مقبض cURL وتنفيذ الطلب
                    $ch = curl_init();
                    curl_setopt_array($ch, $curl_options);
                    $response = curl_exec($ch);
                // Close the cURL session
                curl_close($ch);

                    // تحويل الرد من صيغة JSON إلى مصفوفة
                    $response_array = json_decode($response, true);

                    // استخراج رابط الدفع من الرد
                    $payment_link = $response_array['paymentLink']['url'];

                    // تمرير رابط الدفع إلى القالب Twig لعرضه
                    $data['payment_link'] = $payment_link;
                    $data['customerEmail'] = $data['email'];
                    $data['customerTelephone'] = $data['phone'];
                    $data['order_id'] = $data['order_id'];


                //حفظ في الداتا بيز
                    $data['payment_dlink'] = 'https://techshopeg.com/pay?order='.$data['order_id'];
                $data['paymentlink'] = $payment_link;
                    if(!empty($payment_link)){
                        $savepaymentlink = $this->model_sale_order->addpaymentlink($data);
            			$data['header'] = $this->load->controller('common/header');
            			$data['column_left'] = $this->load->controller('common/column_left');
            			$data['footer'] = $this->load->controller('common/footer');
                    	$this->response->setOutput($this->load->view('sale/order_paymentlink', $data));
                    }
                }else{
                    $data['payment_dlink'] = 'https://techshopeg.com/pay?order='.$checkifhavelink['order_id'];
                    $data['payment_link'] = $checkifhavelink['paymentlink'];
                    $data['order_id'] = $checkifhavelink['order_id'];
                    $data['customerEmail'] = $checkifhavelink['email'];
                    $data['customerTelephone'] = $checkifhavelink['phone'];
        			$data['header'] = $this->load->controller('common/header');
        			$data['column_left'] = $this->load->controller('common/column_left');
        			$data['footer'] = $this->load->controller('common/footer');

                	$this->response->setOutput($this->load->view('sale/order_paymentlink', $data));
                }

                $this->session->data['error_warning'] = '';
                $this->session->data['success'] = '';



    		}else{
    			$this->session->data['error_warning'] = 'أكمل بيانات الفورم قبل الضغط على الزر';
    			$this->session->data['success'] = '';
    			$this->response->redirect($this->url->link('sale/order/addpaymentlink', 'user_token=' . $this->session->data['user_token'] . $url, true));
    		}
		}
  	}
	protected function validatepaymenylinkForm($data) {
		if (!$this->user->hasPermission('modify', 'sale/order')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}


		if (((utf8_strlen($data['order_id']) < 1 )) || ((utf8_strlen($data['order_desc']) < 1 )) || ((utf8_strlen($data['order_total']) < 1 )) || ((utf8_strlen($data['email']) < 1 )) || ((utf8_strlen($data['phone']) < 1 ))) {
		return false;

		}else{
		  return true;
		}
	}


public function sendToOdoo() {
    $order_id = $this->request->get['order_id'];
    $result = $this->syncOrderToOdoo($order_id);

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($result));
}

public function bulkSyncToOdoo() {
    $selected = isset($this->request->post['selected']) ? $this->request->post['selected'] : array();
    $results = array();

    foreach ($selected as $order_id) {
        $results[$order_id] = $this->syncOrderToOdoo($order_id);
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($results));
}

private function syncOrderToOdoo($order_id) {
    $this->load->model('sale/order');
    $order_info = $this->model_sale_order->getOrder($order_id);

    if (!$order_info) {
        return array('success' => false, 'message' => 'Order not found');
    }

    // Prepare customer data
    $customer_data = array(
        'name' => $order_info['firstname'] . ' ' . $order_info['lastname'],
        'source_customer_id' => $order_info['customer_id'],
        'mobile' => $order_info['telephone'],
        'email' => $order_info['email'],
        'phone' => $order_info['telephone'],
        'come_from' => 'TECHSHOP'
    );

    // Add customer to Odoo
    $customer_response = $this->addCustomerToOdoo($customer_data);
    $customer_response_decoded = json_decode($customer_response, true);

    if (!isset($customer_response_decoded['result']['Data']['ID'])) {
        return array('success' => false, 'message' => 'Failed to add customer to Odoo');
    }

    $odoo_partner_id = $customer_response_decoded['result']['Data']['ID'];

    // Prepare order data
    $order_data = array(
        'partner_id' => (int)$odoo_partner_id,
        'date_order' => $order_info['date_added'],
        'come_from' => 'TECHSHOP',
        'order_line' => array(),
        'total_amount' => (float)$order_info['total'] + (float)$order_info['shipping_cost']
    );

    // Add order lines
    $order_products = $this->model_sale_order->getOrderProducts($order_id);
    foreach ($order_products as $product) {
        $odoo_product_id = $this->getOdooProductId($product['product_id']);
        if (!$odoo_product_id) {
            return array('success' => false, 'message' => 'Product ID Not Correct');
        }
        $order_data['order_line'][] = array(
            'product_id' => (int)$odoo_product_id,
            'product_quantity' => (float)$product['quantity'],
            'unit_price' => (float)$product['price']
        );
    }

    // Log order data for debugging
    $this->log->write('Odoo Order Data: ' . json_encode($order_data));

    // Send order to Odoo
    $order_response = $this->addOrderToOdoo($order_data);
    $odoo_order_result = json_decode($order_response, true);

    // Log the response for debugging
    $this->log->write('Odoo Order Response: ' . print_r($odoo_order_result, true));

    if (isset($odoo_order_result['result']['Data']['ID'])) {
        // Update local order with Odoo ID
        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET odoo_id = '" . (int)$odoo_order_result['result']['Data']['ID'] . "' WHERE order_id = '" . (int)$order_id . "'");
        return array('success' => true, 'message' => 'Order synced successfully');
    } else {
        return array('success' => false, 'message' => 'Failed to sync order to Odoo');
    }
}

private function getOdooProductId($product_id) {
    $query = $this->db->query("SELECT odoo_id FROM `" . DB_PREFIX . "product` WHERE product_id = '" . (int)$product_id . "'");
    if ($query->num_rows) {
        return $query->row['odoo_id'];
    } else {
        return false;
    }
}

*/

public function shippingList() {
    $this->load->language('sale/order');

    $this->document->setTitle($this->language->get('heading_title'));

    $this->load->model('sale/order');
    $this->load->model('setting/setting');
    $this->load->model('tool/upload');

    $data['title'] = 'قائمة تسليم الطلبات';
    $data['base'] = HTTP_SERVER;
    $data['direction'] = $this->language->get('direction');
    $data['lang'] = $this->language->get('code');

    $data['orders'] = array();

    $orders = array();

    if (isset($this->request->post['selected'])) {
        $orders = $this->request->post['selected'];
    }

    if (isset($this->request->get['order_id'])) {
        $orders[] = (int)$this->request->get['order_id'];
    }

    foreach ($orders as $order_id) {
        $order_info = $this->model_sale_order->getOrder($order_id);

        if ($order_info) {
            $products = $this->model_sale_order->getOrderProducts($order_id);
            $product_data = array();
            $total_quantity = 0;

            foreach ($products as $product) {
                $product_data[] = array(
                    'name'  => $product['name'],
                    'model' => $product['model'],
                    'quantity' => $product['quantity']
                );
                $total_quantity += $product['quantity'];
            }

            $formatted_address = $order_info['shipping_address_1'].' - '.$order_info['shipping_city'].' - '.$order_info['shipping_zone'];
            $telephone = $order_info['telephone'];
            if (strpos($telephone, '@') !== false) {
                $telephone = '-';
            }
            $data['orders'][] = array(
                'order_id'        => $order_info['order_id'],
                'customername'    => $order_info['firstname'] . ' ' . $order_info['lastname'],
                'telephone'       => $telephone,
                'payment_address' => $formatted_address,
                'product'         => $product_data,
                'product_count'   => count($product_data),
                'total_quantity'  => $total_quantity,
                'total'           => round($order_info['total'], 2),
                'shipping_method' => $order_info['shipping_method'],
                'comment'         => $order_info['comment'],
                'order_status'    => $order_info['order_status'],
                'formatted_address' => $formatted_address
            );
        }
    }

    $this->response->setOutput($this->load->view('sale/order_shipping_list', $data));
}

/**
 * معالجة الإجراءات المجمعة المتقدمة
 *
 * الميزات:
 * - تحديث حالة متعدد
 * - طباعة مجمعة
 * - تصدير مجمع
 * - حذف مجمع مع التحقق
 */
private function processBulkActions() {
    $this->load->language('sale/order');
    $json = array();

    if (!$this->user->hasPermission('modify', 'sale/order')) {
        $json['error'] = $this->language->get('error_permission');
    }

    if (!$json && isset($this->request->post['selected']) && is_array($this->request->post['selected'])) {
        $action = $this->request->post['action'];
        $selected = $this->request->post['selected'];

        switch ($action) {
            case 'delete':
                $this->bulkDeleteOrders($selected);
                break;
            case 'update_status':
                $this->bulkUpdateStatus($selected, $this->request->post['new_status']);
                break;
            case 'print':
                $this->bulkPrintOrders($selected);
                break;
            case 'export':
                $this->bulkExportOrders($selected);
                break;
            default:
                $json['error'] = $this->language->get('error_invalid_action');
        }
    }

    if (!$json) {
        $json['success'] = $this->language->get('text_bulk_success');
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
}

/**
 * تصدير طلبات البيع بصيغ متعددة
 *
 * الصيغ المدعومة:
 * - Excel (XLSX)
 * - CSV
 * - PDF
 * - JSON
 */
private function exportOrders() {
    $this->load->language('sale/order');
    $this->load->model('sale/order');

    $format = isset($this->request->get['format']) ? $this->request->get['format'] : 'excel';
    $filter_data = $this->getFilterData();

    $orders = $this->model_sale_order->getOrders($filter_data);

    switch ($format) {
        case 'excel':
            $this->exportToExcel($orders);
            break;
        case 'csv':
            $this->exportToCSV($orders);
            break;
        case 'pdf':
            $this->exportToPDF($orders);
            break;
        case 'json':
            $this->exportToJSON($orders);
            break;
        default:
            $this->exportToExcel($orders);
    }
}

/**
 * إرسال إشعارات الطلب التلقائية
 *
 * الإشعارات:
 * - إشعار العميل
 * - إشعار فريق المبيعات
 * - إشعار المخزون
 * - إشعار المحاسبة
 */
private function sendOrderNotifications($order_id, $action) {
    $this->load->model('sale/order');
    $this->load->model('tool/notification');

    $order_info = $this->model_sale_order->getOrder($order_id);

    if ($order_info) {
        // إشعار العميل
        $this->model_tool_notification->sendCustomerNotification($order_info['customer_id'], 'order_' . $action, $order_info);

        // إشعار فريق المبيعات
        $this->model_tool_notification->sendTeamNotification('sales', 'order_' . $action, $order_info);

        // إشعار المخزون إذا كان الطلب يؤثر على المخزون
        if (in_array($action, ['created', 'confirmed', 'cancelled'])) {
            $this->model_tool_notification->sendTeamNotification('inventory', 'order_' . $action, $order_info);
        }

        // إشعار المحاسبة للطلبات المؤكدة
        if (in_array($action, ['confirmed', 'invoiced', 'paid'])) {
            $this->model_tool_notification->sendTeamNotification('accounting', 'order_' . $action, $order_info);
        }
    }
}

/**
 * تحديث المخزون والمحاسبة
 *
 * العمليات:
 * - حجز المخزون
 * - إنشاء القيود المحاسبية
 * - تحديث WAC
 * - تسجيل حركات المخزون
 */
private function updateInventoryAndAccounting($order_id, $action) {
    $this->load->model('sale/order');
    $this->load->model('inventory/inventory');
    $this->load->model('accounting/journal');

    $order_info = $this->model_sale_order->getOrder($order_id);
    $order_products = $this->model_sale_order->getOrderProducts($order_id);

    if ($order_info && $order_products) {
        foreach ($order_products as $product) {
            // تحديث المخزون
            $this->model_inventory_inventory->updateStock(
                $product['product_id'],
                $product['unit_id'],
                -$product['quantity'], // خصم من المخزون
                'sale_order',
                $order_id,
                'Order #' . $order_info['order_number']
            );

            // تحديث WAC
            $this->model_inventory_inventory->updateWAC(
                $product['product_id'],
                $product['unit_id'],
                $product['quantity'],
                $product['price']
            );
        }

        // إنشاء القيد المحاسبي
        if ($order_info['order_status_id'] == $this->config->get('config_order_status_confirmed')) {
            $this->createAccountingEntry($order_id);
        }
    }
}

/**
 * إنشاء القيد المحاسبي للطلب
 */
private function createAccountingEntry($order_id) {
    $this->load->model('sale/order');
    $this->load->model('accounting/journal');

    $order_info = $this->model_sale_order->getOrder($order_id);

    if ($order_info) {
        $journal_data = array(
            'reference' => 'SO-' . $order_info['order_number'],
            'description' => 'Sale Order #' . $order_info['order_number'],
            'date' => date('Y-m-d'),
            'entries' => array(
                array(
                    'account_code' => $this->config->get('config_account_receivable'),
                    'debit' => $order_info['total'],
                    'credit' => 0,
                    'description' => 'Customer: ' . $order_info['customer_name']
                ),
                array(
                    'account_code' => $this->config->get('config_account_sales'),
                    'debit' => 0,
                    'credit' => $order_info['total'],
                    'description' => 'Sales Revenue'
                )
            )
        );

        $this->model_accounting_journal->addJournalEntry($journal_data);
    }
}

/**
 * بناء URL إعادة التوجيه مع الفلاتر
 */
private function buildRedirectUrl() {
    $url = '';

    $filters = array('filter_order_id', 'filter_customer', 'filter_order_status', 'filter_total', 'filter_date_from', 'filter_date_to', 'sort', 'order', 'page');

    foreach ($filters as $filter) {
        if (isset($this->request->get[$filter])) {
            $url .= '&' . $filter . '=' . urlencode($this->request->get[$filter]);
        }
    }

    return $url;
}

/**
 * الحصول على بيانات الفلاتر
 */
private function getFilterData() {
    $filter_data = array();

    // إعداد الفلاتر الأساسية
    $filters = array(
        'filter_order_id' => '',
        'filter_customer' => '',
        'filter_order_status' => '',
        'filter_total' => '',
        'filter_date_from' => '',
        'filter_date_to' => '',
        'sort' => 'o.order_id',
        'order' => 'DESC'
    );

    foreach ($filters as $key => $default) {
        $filter_data[$key] = isset($this->request->get[$key]) ? $this->request->get[$key] : $default;
    }

    // إعداد الترقيم
    $page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
    $limit = $this->config->get('config_limit_admin');

    $filter_data['start'] = ($page - 1) * $limit;
    $filter_data['limit'] = $limit;

    return $filter_data;
}

}
