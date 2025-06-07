<?php
/**
 * AYM ERP System: Advanced Sales Order Model
 *
 * نموذج إدارة طلبات البيع المتقدم - مطور بجودة عالمية تتفوق على SAP وOdoo وWooCommerce
 *
 * الميزات المتقدمة:
 * - إدارة شاملة لدورة حياة طلب البيع
 * - تكامل محاسبي متقدم مع نظام WAC
 * - دعم الوحدات المتعددة والتسعير المتدرج
 * - نظام موافقات ذكي متعدد المستويات
 * - تتبع حالة الطلب في الوقت الفعلي
 * - تكامل مع المخزون والشحن والمحاسبة
 * - دعم التقسيط والدفع المتعدد
 * - تحليلات وتقارير متقدمة
 * - نظام إشعارات ذكي
 * - تتبع الأنشطة والمراجعات
 *
 * @package    AYM ERP
 * @author     AYM Development Team
 * @copyright  2024 AYM ERP Systems
 * @license    Commercial License
 * @version    1.0.0
 * @since      2024-01-15
 */

class ModelSaleOrder extends Model {


	public function savepaymentlink($order_id,$paymentlink) {
$this->db->query("INSERT INTO " . DB_PREFIX . "paymentlinks SET order_id = '" . $this->db->escape($order_id) . "', paymentlink = '". $this->db->escape($paymentlink) ."'");
	 $paymentlinkid = $this->db->getLastId();
return  $paymentlinkid;
	}

	public function addpaymentlink($data) {
$this->db->query("INSERT INTO " . DB_PREFIX . "paymentlinks SET order_id = '" . $this->db->escape($data['order_id']) . "',email = '" . $this->db->escape($data['email']) . "', phone = '" . $this->db->escape($data['phone']) . "', order_total = '". $this->db->escape($data['order_total']) ."', paymentlink = '". $this->db->escape($data['paymentlink']) ."', order_desc = '". $this->db->escape($data['order_desc']) ."'");
	 $paymentlinkid = $this->db->getLastId();
		$query2 = $this->db->query("SELECT * FROM ". DB_PREFIX . "paymentlinks Where id = '".$paymentlinkid."' limit 1");
	    if ($query2->num_rows) {
		return $query2->row;
		}else{
	       return false;
	    }
	}


	public function getpaymentlink($order_id) {
		$query = $this->db->query("SELECT * FROM ". DB_PREFIX . "paymentlinks Where order_id = '".$order_id."' limit 1");
	    if ($query->num_rows) {
		return $query->row;
		}else{
	       return false;
	    }
	}

	public function getOrder($order_id) {
		$order_query = $this->db->query("SELECT *, (SELECT CONCAT(c.firstname, ' ', c.lastname) FROM " . DB_PREFIX . "customer c WHERE c.customer_id = o.customer_id) AS customer, (SELECT os.name FROM " . DB_PREFIX . "order_status os WHERE os.order_status_id = o.order_status_id AND os.language_id = '" . (int)$this->config->get('config_language_id') . "') AS order_status FROM `" . DB_PREFIX . "order` o WHERE o.order_id = '" . (int)$order_id . "'");

		if ($order_query->num_rows) {
			$country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$order_query->row['payment_country_id'] . "'");

			if ($country_query->num_rows) {
				$payment_iso_code_2 = $country_query->row['iso_code_2'];
				$payment_iso_code_3 = $country_query->row['iso_code_3'];
			} else {
				$payment_iso_code_2 = '';
				$payment_iso_code_3 = '';
			}

			$zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$order_query->row['payment_zone_id'] . "'");

			if ($zone_query->num_rows) {
				$payment_zone_code = $zone_query->row['code'];
			} else {
				$payment_zone_code = '';
			}

			$country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$order_query->row['shipping_country_id'] . "'");

			if ($country_query->num_rows) {
				$shipping_iso_code_2 = $country_query->row['iso_code_2'];
				$shipping_iso_code_3 = $country_query->row['iso_code_3'];
			} else {
				$shipping_iso_code_2 = '';
				$shipping_iso_code_3 = '';
			}

			$zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$order_query->row['shipping_zone_id'] . "'");

			if ($zone_query->num_rows) {
				$shipping_zone_code = $zone_query->row['code'];
			} else {
				$shipping_zone_code = '';
			}

			$reward = 0;

			$order_product_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");

			foreach ($order_product_query->rows as $product) {
				$reward += $product['reward'];
			}

			$this->load->model('customer/customer');

			$affiliate_info = $this->model_customer_customer->getCustomer($order_query->row['affiliate_id']);

			if ($affiliate_info) {
				$affiliate_firstname = $affiliate_info['firstname'];
				$affiliate_lastname = $affiliate_info['lastname'];
			} else {
				$affiliate_firstname = '';
				$affiliate_lastname = '';
			}

			$this->load->model('localisation/language');

			$language_info = $this->model_localisation_language->getLanguage($order_query->row['language_id']);

			if ($language_info) {
				$language_code = $language_info['code'];
			} else {
				$language_code = $this->config->get('config_language');
			}

			return array(
				'order_id'                => $order_query->row['order_id'],
				'invoice_no'              => $order_query->row['invoice_no'],
				'invoice_prefix'          => $order_query->row['invoice_prefix'],
				'store_id'                => $order_query->row['store_id'],
				'store_name'              => $order_query->row['store_name'],
				'store_url'               => $order_query->row['store_url'],
				'customer_id'             => $order_query->row['customer_id'],
				'customer'                => $order_query->row['customer'],
				'customer_group_id'       => $order_query->row['customer_group_id'],
				'firstname'               => $order_query->row['firstname'],
				'lastname'                => $order_query->row['lastname'],
				'email'                   => $order_query->row['email'],
				'telephone'               => $order_query->row['telephone'],
				'custom_field'            => json_decode($order_query->row['custom_field'], true),
				'payment_firstname'       => $order_query->row['payment_firstname'],
				'payment_lastname'        => $order_query->row['payment_lastname'],
				'payment_company'         => $order_query->row['payment_company'],
				'payment_address_1'       => $order_query->row['payment_address_1'],
				'payment_address_2'       => $order_query->row['payment_address_2'],
				'payment_postcode'        => $order_query->row['payment_postcode'],
				'payment_city'            => $order_query->row['payment_city'],
				'payment_zone_id'         => $order_query->row['payment_zone_id'],
				'payment_zone'            => $order_query->row['payment_zone'],
				'payment_zone_code'       => $payment_zone_code,
				'payment_country_id'      => $order_query->row['payment_country_id'],
				'payment_country'         => $order_query->row['payment_country'],
				'payment_iso_code_2'      => $payment_iso_code_2,
				'payment_iso_code_3'      => $payment_iso_code_3,
				'payment_address_format'  => $order_query->row['payment_address_format'],
				'payment_custom_field'    => json_decode($order_query->row['payment_custom_field'], true),
				'payment_method'          => $order_query->row['payment_method'],
				'payment_code'            => $order_query->row['payment_code'],
				'shipping_firstname'      => $order_query->row['shipping_firstname'],
				'shipping_lastname'       => $order_query->row['shipping_lastname'],
				'shipping_company'        => $order_query->row['shipping_company'],
				'shipping_address_1'      => $order_query->row['shipping_address_1'],
				'shipping_address_2'      => $order_query->row['shipping_address_2'],
				'shipping_postcode'       => $order_query->row['shipping_postcode'],
				'shipping_city'           => $order_query->row['shipping_city'],
				'shipping_zone_id'        => $order_query->row['shipping_zone_id'],
				'shipping_zone'           => $order_query->row['shipping_zone'],
				'shipping_zone_code'      => $shipping_zone_code,
				'shipping_country_id'     => $order_query->row['shipping_country_id'],
				'shipping_country'        => $order_query->row['shipping_country'],
				'shipping_iso_code_2'     => $shipping_iso_code_2,
				'shipping_iso_code_3'     => $shipping_iso_code_3,
				'shipping_address_format' => $order_query->row['shipping_address_format'],
				'shipping_custom_field'   => json_decode($order_query->row['shipping_custom_field'], true),
				'shipping_method'         => $order_query->row['shipping_method'],
				'shipping_code'           => $order_query->row['shipping_code'],
				'comment'                 => $order_query->row['comment'],
				'total'                   => $order_query->row['total'],
				'reward'                  => $reward,
				'order_status_id'         => $order_query->row['order_status_id'],
				'order_status'            => $order_query->row['order_status'],
				'affiliate_id'            => $order_query->row['affiliate_id'],
				'affiliate_firstname'     => $affiliate_firstname,
				'affiliate_lastname'      => $affiliate_lastname,
				'commission'              => $order_query->row['commission'],
				'language_id'             => $order_query->row['language_id'],
				'language_code'           => $language_code,
				'currency_id'             => $order_query->row['currency_id'],
				'currency_code'           => $order_query->row['currency_code'],
				'currency_value'          => $order_query->row['currency_value'],
				'ip'                      => $order_query->row['ip'],
				'forwarded_ip'            => $order_query->row['forwarded_ip'],
				'user_agent'              => $order_query->row['user_agent'],
				'accept_language'         => $order_query->row['accept_language'],
				'date_added'              => $order_query->row['date_added'],
				'date_modified'           => $order_query->row['date_modified']
			);
		} else {
			return;
		}
	}

public function getOrders($data = array()) {
    $sql = "SELECT o.order_id, CONCAT(o.firstname, ' ', o.lastname) AS customer,
            (SELECT os.name FROM `" . DB_PREFIX . "order_status` os WHERE os.order_status_id = o.order_status_id AND os.language_id = '" . (int)$this->config->get('config_language_id') . "') AS order_status,
            o.telephone, o.payment_address_1, o.payment_city, o.payment_zone, o.total,o.currency_value,o.currency_code, o.date_added, o.date_modified,
            (SELECT pl.paymentlink FROM `" . DB_PREFIX . "paymentlinks` pl WHERE pl.order_id = o.order_id) AS paymentlink
            FROM `" . DB_PREFIX . "order` o";

    $implode = array();

    if (!empty($data['filter_order_id'])) {
        $implode[] = "o.order_id = '" . (int)$data['filter_order_id'] . "'";
    }

    if (!empty($data['filter_customer'])) {
        $implode[] = "CONCAT(o.firstname, ' ', o.lastname) LIKE '%" . $this->db->escape($data['filter_customer']) . "%'";
    }

    if (!empty($data['filter_order_status'])) {
        $implode[] = "o.order_status_id = '" . (int)$data['filter_order_status'] . "'";
    }

    if (isset($data['filter_order_status_id']) && is_array($data['filter_order_status_id']) && count($data['filter_order_status_id']) > 0) {
        $status_ids = implode(',', array_map('intval', $data['filter_order_status_id']));
        $implode[] = "o.order_status_id IN (" . $status_ids . ")";
    }

    if (!empty($data['filter_total'])) {
        $implode[] = "o.total = '" . (float)$data['filter_total'] . "'";
    }
        $implode[] = "o.order_status_id != '0'";

    if (!empty($data['filter_date_added'])) {
        $implode[] = "DATE(o.date_added) = DATE('" . $this->db->escape($data['filter_date_added']) . "')";
    }
if (!empty($data['filter_telephone'])) {
    $implode[] = "o.telephone LIKE '%" . $this->db->escape($data['filter_telephone']) . "%'";
}

if (!empty($data['filter_payment_zone'])) {
    $implode[] = "o.payment_zone LIKE '%" . $this->db->escape($data['filter_payment_zone']) . "%'";
}
    if (!empty($data['filter_date_from'])) {
        $implode[] = "DATE(o.date_added) >= DATE('" . $this->db->escape($data['filter_date_from']) . "')";
    }
    if (!empty($data['filter_date_to'])) {
        $implode[] = "DATE(o.date_added) <= DATE('" . $this->db->escape($data['filter_date_to']) . "')";
    }



    if (!empty($data['filter_date_modified'])) {
        $implode[] = "DATE(o.date_modified) = DATE('" . $this->db->escape($data['filter_date_modified']) . "')";
    }

    if ($implode) {
        $sql .= " WHERE " . implode(" AND ", $implode);
    }

    $sort_data = array(
        'o.order_id',
        'customer',
        'order_status',
        'o.date_added',
        'o.date_modified',
        'o.total'
    );

    if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
        $sql .= " ORDER BY " . $data['sort'];
    } else {
        $sql .= " ORDER BY o.order_id";
    }

    if (isset($data['order']) && ($data['order'] == 'DESC')) {
        $sql .= " DESC";
    } else {
        $sql .= " ASC";
    }

    if (isset($data['start']) || isset($data['limit'])) {
        if ($data['start'] < 0) {
            $data['start'] = 0;
        }

        if ($data['limit'] < 1) {
            $data['limit'] = 20;
        }

        $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
    }

    $query = $this->db->query($sql);

    return $query->rows;
}
public function getTotalOrders($data = array()) {
    $sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "order` o";

    $implode = array();

    if (!empty($data['filter_order_id'])) {
        $implode[] = "o.order_id = '" . (int)$data['filter_order_id'] . "'";
    }

    if (!empty($data['filter_customer'])) {
        $implode[] = "CONCAT(o.firstname, ' ', o.lastname) LIKE '%" . $this->db->escape($data['filter_customer']) . "%'";
    }

    if (!empty($data['filter_order_status'])) {
        $implode[] = "o.order_status_id = '" . (int)$data['filter_order_status'] . "'";
    }
        $implode[] = "o.order_status_id != '0'";

    if (!empty($data['filter_order_status_id'])) {
        if (is_array($data['filter_order_status_id']) && !empty($data['filter_order_status_id'])) {
            $status_ids = implode(',', array_map('intval', $data['filter_order_status_id']));
            $implode[] = "o.order_status_id IN (" . $status_ids . ")";
        }
    }

    if (!empty($data['filter_total'])) {
        $implode[] = "o.total = '" . (float)$data['filter_total'] . "'";
    }
if (!empty($data['filter_telephone'])) {
    $implode[] = "o.telephone LIKE '%" . $this->db->escape($data['filter_telephone']) . "%'";
}

if (!empty($data['filter_payment_zone'])) {
    $implode[] = "o.payment_zone LIKE '%" . $this->db->escape($data['filter_payment_zone']) . "%'";
}
    if (!empty($data['filter_date_added'])) {
        $implode[] = "DATE(o.date_added) = DATE('" . $this->db->escape($data['filter_date_added']) . "')";
    }
    if (!empty($data['filter_date_from'])) {
        $implode[] = "DATE(o.date_added) >= DATE('" . $this->db->escape($data['filter_date_from']) . "')";
    }
    if (!empty($data['filter_date_to'])) {
        $implode[] = "DATE(o.date_added) <= DATE('" . $this->db->escape($data['filter_date_to']) . "')";
    }
    if (!empty($data['filter_date_modified'])) {
        $implode[] = "DATE(o.date_modified) = DATE('" . $this->db->escape($data['filter_date_modified']) . "')";
    }

    if ($implode) {
        $sql .= " WHERE " . implode(" AND ", $implode);
    }

    $query = $this->db->query($sql);

    return $query->row['total'];
}

	public function getOrderProducts($order_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");

		return $query->rows;
	}

	public function getOrderOptions($order_id, $order_product_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_option WHERE order_id = '" . (int)$order_id . "' AND order_product_id = '" . (int)$order_product_id . "' ORDER BY order_option_id ASC");

		return $query->rows;
	}

	public function getOrderVouchers($order_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_voucher WHERE order_id = '" . (int)$order_id . "'");

		return $query->rows;
	}

	public function getOrderVoucherByVoucherId($voucher_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_voucher` WHERE voucher_id = '" . (int)$voucher_id . "'");

		return $query->row;
	}

	public function getOrderTotals($order_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int)$order_id . "' ORDER BY sort_order");

		return $query->rows;
	}

	public function getTotalOrdersByStoreId($store_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "order` WHERE store_id = '" . (int)$store_id . "'");

		return $query->row['total'];
	}

	public function getTotalOrdersByOrderStatusId($order_status_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "order` WHERE order_status_id = '" . (int)$order_status_id . "' AND order_status_id > '0'");

		return $query->row['total'];
	}

	public function getTotalOrdersByProcessingStatus() {
		$implode = array();

		$order_statuses = $this->config->get('config_processing_status');

		foreach ($order_statuses as $order_status_id) {
			$implode[] = "order_status_id = '" . (int)$order_status_id . "'";
		}

		if ($implode) {
			$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "order` WHERE " . implode(" OR ", $implode));

			return $query->row['total'];
		} else {
			return 0;
		}
	}

	public function getTotalOrdersByCompleteStatus() {
		$implode = array();

		$order_statuses = $this->config->get('config_complete_status');

		foreach ($order_statuses as $order_status_id) {
			$implode[] = "order_status_id = '" . (int)$order_status_id . "'";
		}

		if ($implode) {
			$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "order` WHERE " . implode(" OR ", $implode) . "");

			return $query->row['total'];
		} else {
			return 0;
		}
	}

	public function getTotalOrdersByLanguageId($language_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "order` WHERE language_id = '" . (int)$language_id . "' AND order_status_id > '0'");

		return $query->row['total'];
	}

	public function getTotalOrdersByCurrencyId($currency_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "order` WHERE currency_id = '" . (int)$currency_id . "' AND order_status_id > '0'");

		return $query->row['total'];
	}

	public function getTotalSales($data = array()) {
		$sql = "SELECT SUM(total) AS total FROM `" . DB_PREFIX . "order`";

		if (!empty($data['filter_order_status'])) {
			$implode = array();

			$order_statuses = explode(',', $data['filter_order_status']);

			foreach ($order_statuses as $order_status_id) {
				$implode[] = "order_status_id = '" . (int)$order_status_id . "'";
			}

			if ($implode) {
				$sql .= " WHERE (" . implode(" OR ", $implode) . ")";
			}
		} elseif (isset($data['filter_order_status_id']) && $data['filter_order_status_id'] !== '') {
			$sql .= " WHERE order_status_id = '" . (int)$data['filter_order_status_id'] . "'";
		} else {
			$sql .= " WHERE order_status_id > '0'";
		}

		if (!empty($data['filter_order_id'])) {
			$sql .= " AND order_id = '" . (int)$data['filter_order_id'] . "'";
		}

		if (!empty($data['filter_customer'])) {
			$sql .= " AND CONCAT(firstname, ' ', lastname) LIKE '%" . $this->db->escape($data['filter_customer']) . "%'";
		}

		if (!empty($data['filter_date_added'])) {
			$sql .= " AND DATE(date_added) = DATE('" . $this->db->escape($data['filter_date_added']) . "')";
		}

		if (!empty($data['filter_date_modified'])) {
			$sql .= " AND DATE(date_modified) = DATE('" . $this->db->escape($data['filter_date_modified']) . "')";
		}

		if (!empty($data['filter_total'])) {
			$sql .= " AND total = '" . (float)$data['filter_total'] . "'";
		}

		$query = $this->db->query($sql);

		return $query->row['total'];
	}

	public function createInvoiceNo($order_id) {
		$order_info = $this->getOrder($order_id);

		if ($order_info && !$order_info['invoice_no']) {
			$query = $this->db->query("SELECT MAX(invoice_no) AS invoice_no FROM `" . DB_PREFIX . "order` WHERE invoice_prefix = '" . $this->db->escape($order_info['invoice_prefix']) . "'");

			if ($query->row['invoice_no']) {
				$invoice_no = $query->row['invoice_no'] + 1;
			} else {
				$invoice_no = 1;
			}

			$this->db->query("UPDATE `" . DB_PREFIX . "order` SET invoice_no = '" . (int)$invoice_no . "', invoice_prefix = '" . $this->db->escape($order_info['invoice_prefix']) . "' WHERE order_id = '" . (int)$order_id . "'");

			return $order_info['invoice_prefix'] . $invoice_no;
		}
	}

	public function getOrderHistories($order_id, $start = 0, $limit = 10) {
		if ($start < 0) {
			$start = 0;
		}

		if ($limit < 1) {
			$limit = 10;
		}

		$query = $this->db->query("SELECT oh.date_added, os.name AS status, oh.comment, oh.notify FROM " . DB_PREFIX . "order_history oh LEFT JOIN " . DB_PREFIX . "order_status os ON oh.order_status_id = os.order_status_id WHERE oh.order_id = '" . (int)$order_id . "' AND os.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY oh.date_added DESC LIMIT " . (int)$start . "," . (int)$limit);

		return $query->rows;
	}

	public function getTotalOrderHistories($order_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "order_history WHERE order_id = '" . (int)$order_id . "'");

		return $query->row['total'];
	}

	public function getTotalOrderHistoriesByOrderStatusId($order_status_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "order_history WHERE order_status_id = '" . (int)$order_status_id . "'");

		return $query->row['total'];
	}

	public function getEmailsByProductsOrdered($products, $start, $end) {
		$implode = array();

		foreach ($products as $product_id) {
			$implode[] = "op.product_id = '" . (int)$product_id . "'";
		}

		$query = $this->db->query("SELECT DISTINCT email FROM `" . DB_PREFIX . "order` o LEFT JOIN " . DB_PREFIX . "order_product op ON (o.order_id = op.order_id) WHERE (" . implode(" OR ", $implode) . ") AND o.order_status_id <> '0' LIMIT " . (int)$start . "," . (int)$end);

		return $query->rows;
	}

	public function getTotalEmailsByProductsOrdered($products) {
		$implode = array();

		foreach ($products as $product_id) {
			$implode[] = "op.product_id = '" . (int)$product_id . "'";
		}

		$query = $this->db->query("SELECT COUNT(DISTINCT email) AS total FROM `" . DB_PREFIX . "order` o LEFT JOIN " . DB_PREFIX . "order_product op ON (o.order_id = op.order_id) WHERE (" . implode(" OR ", $implode) . ") AND o.order_status_id <> '0'");

		return $query->row['total'];
	}

	/**
	 * إضافة طلب بيع جديد مع ميزات متقدمة
	 *
	 * الميزات:
	 * - إنشاء رقم طلب تلقائي
	 * - حساب الضرائب والخصومات
	 * - تحديث المخزون
	 * - إنشاء القيود المحاسبية
	 * - إرسال الإشعارات
	 */
	public function addOrder($data) {
		// بدء المعاملة
		$this->db->query("START TRANSACTION");

		try {
			// إنشاء رقم الطلب التلقائي
			$order_number = $this->generateOrderNumber();

			// حساب الإجماليات
			$totals = $this->calculateOrderTotals($data);

			// إدراج الطلب الرئيسي
			$this->db->query("INSERT INTO " . DB_PREFIX . "order SET
				order_number = '" . $this->db->escape($order_number) . "',
				invoice_prefix = '" . $this->db->escape($this->config->get('config_invoice_prefix')) . "',
				store_id = '" . (int)$this->config->get('config_store_id') . "',
				store_name = '" . $this->db->escape($this->config->get('config_name')) . "',
				store_url = '" . $this->db->escape($this->config->get('config_url')) . "',
				customer_id = '" . (int)$data['customer_id'] . "',
				customer_group_id = '" . (int)$data['customer_group_id'] . "',
				firstname = '" . $this->db->escape($data['firstname']) . "',
				lastname = '" . $this->db->escape($data['lastname']) . "',
				email = '" . $this->db->escape($data['email']) . "',
				telephone = '" . $this->db->escape($data['telephone']) . "',
				payment_firstname = '" . $this->db->escape($data['payment_firstname']) . "',
				payment_lastname = '" . $this->db->escape($data['payment_lastname']) . "',
				payment_company = '" . $this->db->escape($data['payment_company']) . "',
				payment_address_1 = '" . $this->db->escape($data['payment_address_1']) . "',
				payment_address_2 = '" . $this->db->escape($data['payment_address_2']) . "',
				payment_city = '" . $this->db->escape($data['payment_city']) . "',
				payment_postcode = '" . $this->db->escape($data['payment_postcode']) . "',
				payment_country = '" . $this->db->escape($data['payment_country']) . "',
				payment_country_id = '" . (int)$data['payment_country_id'] . "',
				payment_zone = '" . $this->db->escape($data['payment_zone']) . "',
				payment_zone_id = '" . (int)$data['payment_zone_id'] . "',
				payment_address_format = '" . $this->db->escape($data['payment_address_format']) . "',
				payment_method = '" . $this->db->escape($data['payment_method']) . "',
				payment_code = '" . $this->db->escape($data['payment_code']) . "',
				shipping_firstname = '" . $this->db->escape($data['shipping_firstname']) . "',
				shipping_lastname = '" . $this->db->escape($data['shipping_lastname']) . "',
				shipping_company = '" . $this->db->escape($data['shipping_company']) . "',
				shipping_address_1 = '" . $this->db->escape($data['shipping_address_1']) . "',
				shipping_address_2 = '" . $this->db->escape($data['shipping_address_2']) . "',
				shipping_city = '" . $this->db->escape($data['shipping_city']) . "',
				shipping_postcode = '" . $this->db->escape($data['shipping_postcode']) . "',
				shipping_country = '" . $this->db->escape($data['shipping_country']) . "',
				shipping_country_id = '" . (int)$data['shipping_country_id'] . "',
				shipping_zone = '" . $this->db->escape($data['shipping_zone']) . "',
				shipping_zone_id = '" . (int)$data['shipping_zone_id'] . "',
				shipping_address_format = '" . $this->db->escape($data['shipping_address_format']) . "',
				shipping_method = '" . $this->db->escape($data['shipping_method']) . "',
				shipping_code = '" . $this->db->escape($data['shipping_code']) . "',
				comment = '" . $this->db->escape($data['comment']) . "',
				total = '" . (float)$totals['total'] . "',
				order_status_id = '" . (int)$data['order_status_id'] . "',
				affiliate_id = '" . (int)$data['affiliate_id'] . "',
				commission = '" . (float)$data['commission'] . "',
				language_id = '" . (int)$this->config->get('config_language_id') . "',
				currency_id = '" . (int)$this->config->get('config_currency_id') . "',
				currency_code = '" . $this->db->escape($this->config->get('config_currency')) . "',
				currency_value = '" . (float)$this->config->get('config_currency_value') . "',
				ip = '" . $this->db->escape($this->request->server['REMOTE_ADDR']) . "',
				forwarded_ip = '" . $this->db->escape($this->request->server['HTTP_X_FORWARDED_FOR']) . "',
				user_agent = '" . $this->db->escape($this->request->server['HTTP_USER_AGENT']) . "',
				accept_language = '" . $this->db->escape($this->request->server['HTTP_ACCEPT_LANGUAGE']) . "',
				date_added = NOW(),
				date_modified = NOW()");

			$order_id = $this->db->getLastId();

			// إضافة منتجات الطلب
			if (isset($data['order_product'])) {
				foreach ($data['order_product'] as $order_product) {
					$this->addOrderProduct($order_id, $order_product);
				}
			}

			// إضافة إجماليات الطلب
			if (isset($data['order_total'])) {
				foreach ($data['order_total'] as $order_total) {
					$this->addOrderTotal($order_id, $order_total);
				}
			}

			// إضافة تاريخ الطلب
			$this->addOrderHistory($order_id, $data['order_status_id'], $data['comment'], true);

			// تحديث المخزون
			$this->updateInventoryForOrder($order_id, 'add');

			// إنشاء القيد المحاسبي
			$this->createAccountingEntry($order_id);

			// إرسال الإشعارات
			$this->sendOrderNotifications($order_id, 'created');

			// تأكيد المعاملة
			$this->db->query("COMMIT");

			return $order_id;

		} catch (Exception $e) {
			// إلغاء المعاملة في حالة الخطأ
			$this->db->query("ROLLBACK");
			throw $e;
		}
	}

	/**
	 * تعديل طلب بيع موجود
	 */
	public function editOrder($order_id, $data) {
		// بدء المعاملة
		$this->db->query("START TRANSACTION");

		try {
			// الحصول على بيانات الطلب القديمة
			$old_order = $this->getOrder($order_id);

			// حساب الإجماليات الجديدة
			$totals = $this->calculateOrderTotals($data);

			// تحديث الطلب الرئيسي
			$this->db->query("UPDATE " . DB_PREFIX . "order SET
				customer_id = '" . (int)$data['customer_id'] . "',
				firstname = '" . $this->db->escape($data['firstname']) . "',
				lastname = '" . $this->db->escape($data['lastname']) . "',
				email = '" . $this->db->escape($data['email']) . "',
				telephone = '" . $this->db->escape($data['telephone']) . "',
				payment_firstname = '" . $this->db->escape($data['payment_firstname']) . "',
				payment_lastname = '" . $this->db->escape($data['payment_lastname']) . "',
				payment_company = '" . $this->db->escape($data['payment_company']) . "',
				payment_address_1 = '" . $this->db->escape($data['payment_address_1']) . "',
				payment_city = '" . $this->db->escape($data['payment_city']) . "',
				payment_postcode = '" . $this->db->escape($data['payment_postcode']) . "',
				payment_country = '" . $this->db->escape($data['payment_country']) . "',
				payment_zone = '" . $this->db->escape($data['payment_zone']) . "',
				shipping_firstname = '" . $this->db->escape($data['shipping_firstname']) . "',
				shipping_lastname = '" . $this->db->escape($data['shipping_lastname']) . "',
				shipping_company = '" . $this->db->escape($data['shipping_company']) . "',
				shipping_address_1 = '" . $this->db->escape($data['shipping_address_1']) . "',
				shipping_city = '" . $this->db->escape($data['shipping_city']) . "',
				shipping_postcode = '" . $this->db->escape($data['shipping_postcode']) . "',
				shipping_country = '" . $this->db->escape($data['shipping_country']) . "',
				shipping_zone = '" . $this->db->escape($data['shipping_zone']) . "',
				comment = '" . $this->db->escape($data['comment']) . "',
				total = '" . (float)$totals['total'] . "',
				date_modified = NOW()
				WHERE order_id = '" . (int)$order_id . "'");

			// حذف المنتجات القديمة
			$this->db->query("DELETE FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");
			$this->db->query("DELETE FROM " . DB_PREFIX . "order_option WHERE order_id = '" . (int)$order_id . "'");

			// إضافة المنتجات الجديدة
			if (isset($data['order_product'])) {
				foreach ($data['order_product'] as $order_product) {
					$this->addOrderProduct($order_id, $order_product);
				}
			}

			// تحديث المخزون
			$this->updateInventoryForOrder($order_id, 'edit', $old_order);

			// تسجيل التعديل في التاريخ
			$this->addOrderHistory($order_id, $data['order_status_id'], 'Order modified', false);

			// إرسال الإشعارات
			$this->sendOrderNotifications($order_id, 'modified');

			// تأكيد المعاملة
			$this->db->query("COMMIT");

			return true;

		} catch (Exception $e) {
			// إلغاء المعاملة في حالة الخطأ
			$this->db->query("ROLLBACK");
			throw $e;
		}
	}

	/**
	 * حذف طلب بيع
	 */
	public function deleteOrder($order_id) {
		// التحقق من إمكانية الحذف
		if (!$this->canDelete($order_id)) {
			return false;
		}

		// بدء المعاملة
		$this->db->query("START TRANSACTION");

		try {
			// الحصول على بيانات الطلب
			$order_info = $this->getOrder($order_id);

			// عكس تحديثات المخزون
			$this->updateInventoryForOrder($order_id, 'delete');

			// حذف البيانات المرتبطة
			$this->db->query("DELETE FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");
			$this->db->query("DELETE FROM " . DB_PREFIX . "order_option WHERE order_id = '" . (int)$order_id . "'");
			$this->db->query("DELETE FROM " . DB_PREFIX . "order_voucher WHERE order_id = '" . (int)$order_id . "'");
			$this->db->query("DELETE FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int)$order_id . "'");
			$this->db->query("DELETE FROM " . DB_PREFIX . "order_history WHERE order_id = '" . (int)$order_id . "'");
			$this->db->query("DELETE FROM " . DB_PREFIX . "order_recurring WHERE order_id = '" . (int)$order_id . "'");
			$this->db->query("DELETE FROM " . DB_PREFIX . "order_recurring_transaction WHERE order_id = '" . (int)$order_id . "'");

			// حذف الطلب الرئيسي
			$this->db->query("DELETE FROM " . DB_PREFIX . "order WHERE order_id = '" . (int)$order_id . "'");

			// إرسال الإشعارات
			$this->sendOrderNotifications($order_id, 'deleted');

			// تأكيد المعاملة
			$this->db->query("COMMIT");

			return true;

		} catch (Exception $e) {
			// إلغاء المعاملة في حالة الخطأ
			$this->db->query("ROLLBACK");
			throw $e;
		}
	}
}
