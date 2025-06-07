<?php
class ControllerCommonCart3 extends Controller {
	public function index() {
		$data['product_edit'] = $this->url->link('checkout/cart/edit');
		$data['product_remove'] = $this->url->link('checkout/cart/remove');
		$data['voucher_remove'] = $this->url->link('checkout/voucher/remove');	    
		$this->load->language('common/cart');

		// Totals
		$this->load->model('setting/extension');

		$totals = array();
		$taxes = $this->cart->getTaxes();
		$total = 0;

		// Because __call can not keep var references so we put them into an array.
		$total_data = array(
			'totals' => &$totals,
			'taxes'  => &$taxes,
			'total'  => &$total
		);
			
		// Display prices
		if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
			$sort_order = array();

			$results = $this->model_setting_extension->getExtensions('total');

			foreach ($results as $key => $value) {
				$sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
			}

			array_multisort($sort_order, SORT_ASC, $results);

			foreach ($results as $result) {
				if ($this->config->get('total_' . $result['code'] . '_status')) {
					$this->load->model('extension/total/' . $result['code']);

					// We have to put the totals in an array so that they pass by reference.
					$this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
				}
			}

			$sort_order = array();

			foreach ($totals as $key => $value) {
				$sort_order[$key] = $value['sort_order'];
			}

			array_multisort($sort_order, SORT_ASC, $totals);
		}


		$data['carttotalproductscount'] = $this->cart->countProducts();

		$data['text_items'] = sprintf($this->cart->countProducts() + (isset($this->session->data['vouchers']) ? count($this->session->data['vouchers']) : 0), $this->currency->format($total, $this->session->data['currency']));
		if(isset($this->session->data['userdevice'])){
        	$data['userdevice'] = $this->session->data['userdevice'];//get it from stratup/session
		}else if(isset($this->request->cookie['userdevice'])){
        	$data['userdevice'] = $this->request->cookie['userdevice'];//get it from stratup/session
		}else{
			$data['userdevice'] = "None";
		}
		
        $this->load->model('tool/image');
        $this->load->model('tool/upload');
        $this->load->model('catalog/product');

        $data['products'] = array();

        foreach ($this->cart->getProducts() as $product) {
            if ($product['image']) {
                $image = $this->model_tool_image->resize($product['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_height'));
            } else {
                $image = $this->model_tool_image->resize('placeholder.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_height'));
            }

            $option_data = array();

            foreach ($product['option'] as $option) {
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
                    'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value),
                    'type'  => $option['type']
                );
            }

            // Get unit name and price
            $unit_name = $this->model_catalog_product->getUnitName($product['unit_id']);
            $unit_price = $product['price'];
            $totalprice = $unit_price * $product['quantity'];

            // حساب السعر والإجمالي مع الضريبة
            $price_with_tax = $this->tax->calculate($unit_price, $product['tax_class_id'], $this->config->get('config_tax'));
            $total_with_tax = $price_with_tax * $product['quantity'];

            // تنسيق الأسعار
            $formatted_price = $this->currency->format($unit_price, $this->session->data['currency']);
            $formatted_total = $this->currency->format($totalprice, $this->session->data['currency']);

            $data['products'][] = array(
                'cart_id'   => $product['cart_id'],
                'thumb'     => $image,
                'name'      => $product['name'],
                'model'     => $product['model'],
                'unit'      => $unit_name,
                'option'    => $option_data,
                'recurring' => ($product['recurring'] ? $product['recurring']['name'] : ''),
                'quantity'  => $product['quantity'],
                'price'     => $formatted_price,
                'total'     => $formatted_total,
                'href'      => $this->url->link('product/product', 'product_id=' . $product['product_id'])
            );
        }

		// Gift Voucher
		$data['vouchers'] = array();

		if (!empty($this->session->data['vouchers'])) {
			foreach ($this->session->data['vouchers'] as $key => $voucher) {
				$data['vouchers'][] = array(
					'key'         => $key,
					'description' => $voucher['description'],
					'amount'      => $this->currency->format($voucher['amount'], $this->session->data['currency'])
				);
			}
		}

		$data['totals'] = array();

		foreach ($totals as $total) {
			$data['totals'][] = array(
				'title' => $total['title'],
				'text'  => $this->currency->format($total['value'], $this->session->data['currency']),
			);
		}

		$data['cart'] = $this->url->link('checkout/cart');
		$data['checkout'] = $this->url->link('checkout/checkout', '', true);
		$data['list'] = $this->load->controller('checkout/cart/list');

		return $this->load->view('common/cart3', $data);
	}

	public function info() {
		$this->response->setOutput($this->index());
	}
	
	
	
	public function index2(): string {
		return $this->cart->countProducts();
	}	
	public function info2(): void {
		$this->response->setOutput($this->index2());
	}	

	public function remove2(): void {
		$this->load->language('checkout/cart');

		$json = [];

		if (isset($this->request->post['key'])) {
			$key = (int)$this->request->post['key'];
		} else {
			$key = 0;
		}


		if (!$json) {
			$this->cart->remove($key);

			$json['success'] = $this->language->get('text_remove');

			unset($this->session->data['shipping_method']);
			unset($this->session->data['shipping_methods']);
			unset($this->session->data['payment_method']);
			unset($this->session->data['payment_methods']);
			unset($this->session->data['reward']);
		}
        
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function removeVoucher(): void {
		$this->load->language('checkout/cart');

		$json = [];

		if (isset($this->request->get['key'])) {
			$key = $this->request->get['key'];
		} else {
			$key = '';
		}

		if (!isset($this->session->data['vouchers'][$key])) {
			$json['error'] = $this->language->get('error_voucher');
		}

		if (!$json) {
			$json['success'] = $this->language->get('text_remove');

			unset($this->session->data['vouchers'][$key]);
			unset($this->session->data['shipping_method']);
			unset($this->session->data['shipping_methods']);
			unset($this->session->data['payment_method']);
			unset($this->session->data['payment_methods']);
			unset($this->session->data['reward']);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}


}