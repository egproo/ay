<?php
class ControllerCommonHeader extends Controller {
	public function index() {
		// Analytics
		$this->load->model('setting/extension');

		$data['analytics'] = array();

		$analytics = $this->model_setting_extension->getExtensions('analytics');

		foreach ($analytics as $analytic) {
			if ($this->config->get('analytics_' . $analytic['code'] . '_status')) {
				$data['analytics'][] = $this->load->controller('extension/analytics/' . $analytic['code'], $this->config->get('analytics_' . $analytic['code'] . '_status'));
			}
		}

		if ($this->request->server['HTTPS']) {
			$server = $this->config->get('config_ssl');
		} else {
			$server = $this->config->get('config_url');
		}

		if (is_file(DIR_IMAGE . $this->config->get('config_icon'))) {
			$this->document->addLink($server . 'image/' . $this->config->get('config_icon'), 'icon');
		}

		$data['title'] = $this->document->getTitle();

if (empty($this->session->data['csrf_token'])) {
    // توليد سلسلة عشوائية من 32 بايت:
    $this->session->data['csrf_token'] = bin2hex(random_bytes(32));
}
		$data['csrf_token'] =  $this->session->data['csrf_token'];

		$data['base'] = $server;
		$data['description'] = $this->document->getDescription();
		$data['keywords'] = $this->document->getKeywords();
		$data['links'] = $this->document->getLinks();
		$data['styles'] = $this->document->getStyles();
		$data['scripts'] = $this->document->getScripts('header');
		$data['lang'] = $this->language->get('code');
		$data['direction'] = $this->language->get('direction');
		// Hard coding css so they can be replaced via the event's system.
		$data['bootstrap'] = 'catalog/view/stylesheet/bootstrap.css';
		$data['icons'] = 'catalog/view/stylesheet/fonts/fontawesome/css/all.min.css';
		$data['stylesheet'] = 'catalog/view/stylesheet/stylesheet.css';

		if(isset($this->session->data['userdevice'])){
        	$data['userdevice'] = $this->session->data['userdevice'];//get it from stratup/session
		}else if(isset($this->request->cookie['userdevice'])){
        	$data['userdevice'] = $this->request->cookie['userdevice'];//get it from stratup/session
		}else{
			$data['userdevice'] = "None";
		}
		$data['carttotalproductscount'] = $this->cart->countProducts();
				$data['allproducts'] = $this->url->link('product/catalog');
			$data['special'] = $this->url->link('product/special', 'language=' . $this->config->get('config_language'));
			$data['specials'] = $this->url->link('product/special', 'language=' . $this->config->get('config_language') . (isset($this->session->data['customer_token']) ? '&customer_token=' . $this->session->data['customer_token'] : ''));

		// Hard coding scripts so they can be replaced via the event's system.
		$data['jquery'] = 'catalog/view/javascript/jquery/jquery-3.7.0.min.js';

		$data['name'] = $this->config->get('config_name');

		if (is_file(DIR_IMAGE . $this->config->get('config_logo'))) {
			$data['logo'] = $server . 'image/' . $this->config->get('config_logo');
		} else {
			$data['logo'] = '';
		}

		$this->load->language('common/header');

		// Wishlist
		if ($this->customer->isLogged()) {
			$this->load->model('account/wishlist');

			$data['text_wishlist'] = sprintf($this->language->get('text_wishlist'), $this->model_account_wishlist->getTotalWishlist());
		} else {
			$data['text_wishlist'] = sprintf($this->language->get('text_wishlist'), (isset($this->session->data['wishlist']) ? count($this->session->data['wishlist']) : 0));
		}

		$data['text_logged'] = sprintf($this->language->get('text_logged'), $this->url->link('account/account', '', true), $this->customer->getFirstName(), $this->url->link('account/logout', '', true));
		
		if(isset($this->session->data['language'])){
		    if($this->session->data['language'] == "ar"){
		      $data['home'] = $this->config->get('config_ssl').'ar';
		    }else{
		      $data['home'] = $this->config->get('config_ssl').'en';
		    }
		}
		$data['wishlist'] = $this->url->link('account/wishlist', '', true);
		$data['logged'] = $this->customer->isLogged();
		$data['account'] = $this->url->link('account/account', '', true);
		$data['register'] = $this->url->link('account/register', '', true);
		$data['login'] = $this->url->link('account/login', '', true);
		$data['order'] = $this->url->link('account/order', '', true);
		$data['transaction'] = $this->url->link('account/transaction', '', true);
		$data['download'] = $this->url->link('account/download', '', true);
		$data['logout'] = $this->url->link('account/logout', '', true);
		$data['shopping_cart'] = $this->url->link('checkout/cart');
		$data['checkout'] = $this->url->link('checkout/checkout', '', true);
		$data['contact'] = $this->url->link('information/contact');
		$data['telephone'] = $this->config->get('config_telephone');
		
		$data['language'] = $this->load->controller('common/language');
		$data['currency'] = $this->load->controller('common/currency');
		$data['search'] = $this->load->controller('common/search');
		$data['cart'] = $this->load->controller('common/cart');
		$data['cart3'] = $this->load->controller('common/cart3');
		
		$data['menu'] = $this->load->controller('common/menu');
		$this->load->model('catalog/category');

		$this->load->model('catalog/product');
		$this->load->model('tool/image');

		$data['categories'] = [];
		$categories = $this->model_catalog_category->getCategories(0);

		foreach ($categories as $category) {
				if (!empty($category['image'])) {    
					$categoryimage = 'image/'.$category['image'];
				} else {
					$categoryimage = $this->model_tool_image->resize('placeholder.png', 100,100);
				}		    
			if ($category['top']) {
				// Level 2
				$children_data = [];

				$children = $this->model_catalog_category->getCategories($category['category_id']);

				foreach ($children as $child) {
					if (!empty($child['image'])) {
					$childimage = 'image/'.$child['image'];
				} else {
					$childimage = $this->model_tool_image->resize('placeholder.png', 100,100);
				}			    
					$filter_data = [
						'filter_category_id'  => $child['category_id'],
						'filter_sub_category' => true
					];

					$children_data[] = [
						'catid'       => $category['category_id'],
						'childid'       => $child['category_id'],
						'name'  => $child['name'],
						'image'       => $childimage,
						'href'  => $this->url->link('product/category', '&path=' . $category['category_id'] . '_' . $child['category_id'])

					];
				}

				// Level 1
				$data['categories'][] = [
				    'id'       => $category['category_id'],
					'name'     => $category['name'],
					'image'       => $categoryimage,
					'children' => $children_data,
					'column'   => $category['column'] ? $category['column'] : 1,
					'href'     => $this->url->link('product/category', '&path=' . $category['category_id'])
				];
			}
		}
		return $this->load->view('common/header', $data);
	}
}
