<?php
class ModelExtensionModulefbcapidyad extends Model {
	private $divcls = 'form-group';
	private $lblcls = 'control-label';
	private $wellcls = 'well well-sm';
	private $grpcls = 'input-group-addon';
	private $slctcls = 'form-control';
	
	private $modpath = 'module/fbcapidyad'; 
	private $modvar = 'model_module_fbcapidyad';
	private $modname = 'fbcapidyad';
	private $modsprtor = '/';
	
	private $evntcode = 'fbcapidyad';
	private $error = array();
	private $status = '';
	private $setting = array();

	public function __construct($registry) {		
		parent::__construct($registry);		
		ini_set("serialize_precision", -1);
		
		if(substr(VERSION,0,3)=='2.3') {
			$this->modpath = 'extension/module/fbcapidyad';
			$this->modvar = 'model_extension_module_fbcapidyad';
		}
		if(substr(VERSION,0,3)=='3.0') {			
			$this->modpath = 'extension/module/fbcapidyad';
			$this->modvar = 'model_extension_module_fbcapidyad';
			$this->modname = 'module_fbcapidyad';
		} 
		if(substr(VERSION,0,3)=='4.0') {
			$this->modpath = 'extension/fbcapidyad/module/fbcapidyad';
			$this->modvar = 'model_extension_fbcapidyad_module_fbcapidyad';
			$this->modname = 'module_fbcapidyad';
			$this->modsprtor = '.';
			$this->divcls = 'row mb-3';
			$this->lblcls = 'col-form-label';
			$this->wellcls = 'form-control';
			$this->grpcls = 'input-group-text';
			$this->slctcls = 'form-select';			
		}
		
		$ismulti_store = 1;
		if($ismulti_store) {
			$this->setting = $this->getSetting();
			$this->status = ($this->config->get($this->modname.'_status') && $this->setting['status']) ? true : false;	
		} else {
			$this->status = $this->config->get($this->modname.'_status');
		}
 	}
	public function getcache() {
		$json = array();
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	public function pageview() {
		if($this->status) {
			$this->callAPIFB('PageView', rand());			

			$fb_g_code = array();
			if($this->setting['pxid']) {
				$userdata = $this->getuserdata(); 
				
				$imgsrc = 'height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id='.$this->setting['pxid'].'&ev=PageView&noscript=1"';			
				$fb_g_code[] = "<!-- Facebook Pixel Code -->
				<script>
				!function(f,b,e,v,n,t,s)
				{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
				n.callMethod.apply(n,arguments):n.queue.push(arguments)};
				if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
				n.queue=[];t=b.createElement(e);t.async=!0;
				t.src=v;s=b.getElementsByTagName(e)[0];
				s.parentNode.insertBefore(t,s)}(window, document,'script',
				'https://connect.facebook.net/en_US/fbevents.js');
				fbq('init', '".$this->setting['pxid']."', ".json_encode($userdata, true).");
				fbq('track', 'PageView');
				</script>
				<noscript> <img ".$imgsrc."/></noscript>
				<!-- End Facebook Pixel Code -->";
			} 
			
			$src = substr(VERSION,0,3) == '4.0' ? 'extension/fbcapidyad/catalog/view/javascript/fbcapidyad.js' : 'catalog/view/javascript/fbcapidyad.js';
			
			$fb_g_code[] = sprintf('<script src="%s" type="text/javascript"></script>',$src);
			
			return join($fb_g_code);
		}			
	}
	public function login() {
		$fb_g_code = array();
		if($this->status) {
 			$evname = 'Login'; $evid = rand(); $flag_cust_ev = 1;			
			
			$this->callAPIFB($evname, $evid, $flag_cust_ev);
			
			$fb_g_code[] = $this->get_fbpxl($evname, $evid, $flag_cust_ev);
			
			return join($fb_g_code);
		}
	}
	public function logoutbefore() {
		$this->session->data['fbcapidyad_logout_flag'] = 1;
	}
	public function logout() {
		$fb_g_code = array();
		if($this->status && isset($this->session->data['fbcapidyad_logout_flag'])) {
			unset($this->session->data['fbcapidyad_logout_flag']);
 			$evname = 'Logout'; $evid = rand(); $flag_cust_ev = 1;
			
			$this->callAPIFB($evname, $evid, $flag_cust_ev);
			
			$fb_g_code[] = $this->get_fbpxl($evname, $evid, $flag_cust_ev);
			
			return join($fb_g_code);
		}
	}
	public function signupbefore() {
		$this->session->data['fbcapidyad_signup_flag'] = 1;
	}
	public function signup() {
		$fb_g_code = array();
		if($this->status && isset($this->session->data['fbcapidyad_signup_flag'])) {
			unset($this->session->data['fbcapidyad_signup_flag']);
 			$evname = 'CompleteRegistration'; $evid = rand();			
			
			$this->callAPIFB($evname, $evid);
			
			$fb_g_code[] = $this->get_fbpxl($evname, $evid);
			
			return join($fb_g_code);
		}
	}
	public function contact() {
		$fb_g_code = array();
		if($this->status) {
 			$evname = 'Contact';
			$evid = rand();
			
			$this->callAPIFB($evname, $evid);
			
			$fb_g_code[] = $this->get_fbpxl($evname, $evid);
			
			return join($fb_g_code);
		}
	}
	public function addtocart() {
		$json['script'] = false;
		if ($this->status && isset($this->request->post['product_id']) && isset($this->request->post['quantity'])) {
			$pid = (int)$this->request->post['product_id'];
			$quantity = (int)$this->request->post['quantity'];
			
			if (isset($this->request->post['option'])) {
				$option = array_filter($this->request->post['option']);
			} else {
				$option = array();
			}
				
			$this->load->model('catalog/product');
			
			$pinfo = $this->model_catalog_product->getProduct($pid);
			
			if ($pinfo) {
				$json = array();
				
				if ((int)$quantity >= $pinfo['minimum']) {
					$quantity = (int)$this->request->post['quantity'];
				} else {
					$quantity = $pinfo['minimum'] ? $pinfo['minimum'] : 1;
				}
				
				if(substr(VERSION,0,3)=='4.0') {
					$product_options = $this->model_catalog_product->getOptions($pid);
				} else {
					$product_options = $this->model_catalog_product->getProductOptions($pid);
				}
	
				foreach ($product_options as $product_option) {
					if ($product_option['required'] && empty($option[$product_option['product_option_id']])) {
						$json['error']['option'][$product_option['product_option_id']] = sprintf($this->language->get('error_required'), $product_option['name']);
					}
				}

				if (!$json) {
					// do add to cart
					$option_price = 0;
	
					foreach ($option as $product_option_id => $value) {
						$option_query = $this->db->query("SELECT po.product_option_id, po.option_id, od.name, o.type FROM " . DB_PREFIX . "product_option po LEFT JOIN `" . DB_PREFIX . "option` o ON (po.option_id = o.option_id) LEFT JOIN " . DB_PREFIX . "option_description od ON (o.option_id = od.option_id) WHERE po.product_option_id = '" . (int)$product_option_id . "' AND po.product_id = '" . (int)$pid . "' AND od.language_id = '" . (int)$this->config->get('config_language_id') . "'");
	
						if ($option_query->num_rows) {
							if ($option_query->row['type'] == 'select' || $option_query->row['type'] == 'radio') {
								$option_value_query = $this->db->query("SELECT pov.option_value_id, ovd.name, pov.quantity, pov.subtract, pov.price, pov.price_prefix, pov.points, pov.points_prefix, pov.weight, pov.weight_prefix FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "option_value ov ON (pov.option_value_id = ov.option_value_id) LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) WHERE pov.product_option_value_id = '" . (int)$value . "' AND pov.product_option_id = '" . (int)$product_option_id . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
	
								if ($option_value_query->num_rows) {
									if ($option_value_query->row['price_prefix'] == '+') {
										$option_price += $option_value_query->row['price'];
									} elseif ($option_value_query->row['price_prefix'] == '-') {
										$option_price -= $option_value_query->row['price'];
									}	
								}
							} elseif ($option_query->row['type'] == 'checkbox' && is_array($value)) {
								foreach ($value as $product_option_value_id) {
									$option_value_query = $this->db->query("SELECT pov.option_value_id, pov.quantity, pov.subtract, pov.price, pov.price_prefix, pov.points, pov.points_prefix, pov.weight, pov.weight_prefix, ovd.name FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (pov.option_value_id = ovd.option_value_id) WHERE pov.product_option_value_id = '" . (int)$product_option_value_id . "' AND pov.product_option_id = '" . (int)$product_option_id . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
	
									if ($option_value_query->num_rows) {
										if ($option_value_query->row['price_prefix'] == '+') {
											$option_price += $option_value_query->row['price'];
										} elseif ($option_value_query->row['price_prefix'] == '-') {
											$option_price -= $option_value_query->row['price'];
										}
									}
								}
							}
						}
					}
					
					$pinfo['price'] = $pinfo['special'] ? $pinfo['special'] : $pinfo['price'];
					
					$pinfo['quantity'] = $quantity;
					
					$fb_g_value = $this->tax->calculate($pinfo['price'] + $option_price, $pinfo['tax_class_id'], $this->config->get('config_tax')) * $quantity;
										
					$evname = 'AddToCart'; $evid = rand(); $flag_cust_ev = 0; $fb_g_pdata = array($pinfo);
					
					$this->callAPIFB($evname, $evid, $flag_cust_ev, $fb_g_pdata, $fb_g_value);
					
					$json['script'] = $this->get_fbpxl($evname, $evid, $flag_cust_ev, $fb_g_pdata, $fb_g_value);
				}
			}
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	public function addtowishlist() {
		$json['script'] = false;
		if($this->status && isset($this->request->post['product_id']) && isset($this->request->post['quantity'])) {	
			$pid = (int)$this->request->post['product_id'];
			$quantity = (int)$this->request->post['quantity'];	
			
			$this->load->model('catalog/product');
			
			$pinfo = $this->model_catalog_product->getProduct($pid);
			
			if ($pinfo) {
				if ((int)$quantity >= $pinfo['minimum']) {
					$quantity = (int)$this->request->post['quantity'];
				} else {
					$quantity = $pinfo['minimum'] ? $pinfo['minimum'] : 1;
				}
				
				$pinfo['price'] = $pinfo['special'] ? $pinfo['special'] : $pinfo['price'];
					
				$pinfo['quantity'] = $quantity;
				
				$fb_g_value = $this->tax->calculate($pinfo['price'], $pinfo['tax_class_id'], $this->config->get('config_tax')) * $quantity;
				
				$evname = 'AddToWishlist'; $evid = rand(); $flag_cust_ev = 0; $fb_g_pdata = array($pinfo);
				
				$this->callAPIFB($evname, $evid, $flag_cust_ev, $fb_g_pdata, $fb_g_value);
				
				$json['script'] = $this->get_fbpxl($evname, $evid, $flag_cust_ev, $fb_g_pdata, $fb_g_value);
			}
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	public function viewcont() {
		$fb_g_code = array();
		if($this->status && isset($this->request->get['product_id'])) { 
   			$this->load->model('catalog/product');
			
			$pinfo = $this->model_catalog_product->getProduct($this->request->get['product_id']);
			
			if($pinfo) {
				$pinfo['price'] = $pinfo['special'] ? $pinfo['special'] : $pinfo['price'];
				$pinfo['quantity'] = $pinfo['minimum'] ? $pinfo['minimum'] : 1;
				
				$fb_g_value = $this->tax->calculate($pinfo['price'], $pinfo['tax_class_id'], $this->config->get('config_tax'));
				
				$evname = 'ViewContent'; $evid = rand(); $flag_cust_ev = 0; $fb_g_pdata = array($pinfo); $flag_nm = 1;
				
				$this->callAPIFB($evname, $evid, $flag_cust_ev, $fb_g_pdata, $fb_g_value, $flag_nm);
				
				$fb_g_code[] = $this->get_fbpxl($evname, $evid, $flag_cust_ev, $fb_g_pdata, $fb_g_value, $flag_nm) . $this->getmicrotag($pinfo);	
				
				return join($fb_g_code);
			}
		}
	}
	public function viewcategory() {
		$fb_g_code = array();
		if($this->status && !empty($this->request->get['path'])) {			
			$this->load->model('catalog/product');
			
			$path = '';
 			$parts = explode('_', (string)$this->request->get['path']);
 			$category_id = (int)end($parts);
			$catname = $this->getcatnamefromID($category_id);
			
			$pinfo = array();
			$ptotal = array();
			$result = $this->getcategory($category_id);

			if($result) {
				foreach($result as $rs) {
					$pid = $rs['product_id'];
					$pdata = $this->model_catalog_product->getProduct($pid);
					if(!$pdata) {continue;}
					$pdata['price'] = $pdata['special'] ? $pdata['special'] : $pdata['price'];
 					$pdata['quantity'] = $pdata['minimum'] ? $pdata['minimum'] : 1;
					
					$pinfo[$pid] = $pdata;
					$pinfo[$pid]['price'] = $pdata['price'];
 					$ptotal[] = $this->tax->calculate($pdata['price'], $pdata['tax_class_id'], $this->config->get('config_tax'));
				}
				
				$fb_g_value = array_sum($ptotal);
				
				$evname = 'ViewCategory'; $evid = rand(); $flag_cust_ev = 1; $fb_g_pdata = $pinfo; $flag_nm = 1;				
				
				$this->callAPIFB($evname, $evid, $flag_cust_ev, $fb_g_pdata, $fb_g_value, $flag_nm);
			
				$fb_g_code[] = $this->get_fbpxl($evname, $evid, $flag_cust_ev, $fb_g_pdata, $fb_g_value, $flag_nm);			
				
				return join($fb_g_code);
			}
		}
	}
	public function search() {
		$fb_g_code = array();
		if($this->status && !empty($this->request->get['search'])) {
			$srchstr = $this->request->get['search'];
			$this->load->model('catalog/product');
			
			$pinfo = array();
			$pinfo = array();
			$result = $this->getsearchrs($this->request->get['search']);
			
			if($result) {
				foreach($result as $rs) {
					$pid = $rs['product_id'];
					$pdata = $this->model_catalog_product->getProduct($pid);
					if(!$pdata) {continue;}
					$pdata['price'] = $pdata['special'] ? $pdata['special'] : $pdata['price'];
 					$pdata['quantity'] = $pdata['minimum'] ? $pdata['minimum'] : 1;
					
					$pinfo[$pid] = $pdata;
					$pinfo[$pid]['price'] = $pdata['price'];
 					$ptotal[] = $this->tax->calculate($pdata['price'], $pdata['tax_class_id'], $this->config->get('config_tax'));
				}
				
				$fb_g_value = array_sum($ptotal);
				
				$evname = 'Search'; $evid = rand(); $flag_cust_ev = 0; $fb_g_pdata = $pinfo; $flag_nm = 1;
 				
				$this->callAPIFB($evname, $evid, $flag_cust_ev, $fb_g_pdata, $fb_g_value, $flag_nm, $srchstr);
			
				$fb_g_code[] = $this->get_fbpxl($evname, $evid, $flag_cust_ev, $fb_g_pdata, $fb_g_value, $flag_nm, $srchstr);			
				
				return join($fb_g_code);
			}
		}
	}
	public function remove_from_cart() {
		$fb_g_code = array();
		if (isset($this->request->post['key']) || isset($this->request->get['remove'])) {
			foreach($this->cart->getProducts() as $cartprod) {
				if((isset($cartprod['key']) && $cartprod['key'] == $this->request->get['remove']) || 
					(isset($cartprod['key']) && $cartprod['key'] == $this->request->post['key']) || 
					(isset($cartprod['cart_id']) && $cartprod['cart_id'] == $this->request->post['key'])) 
				{
					$fb_g_value = $cartprod['total'];
					
					$evname = 'RemoveFromCart'; $evid = rand(); $flag_cust_ev = 1; $fb_g_pdata = array($cartprod);					
					
					$this->callAPIFB($evname, $evid, $flag_cust_ev, $fb_g_pdata, $fb_g_value);
					
					$fb_g_code[] = $this->get_fbpxl($evname, $evid, $flag_cust_ev, $fb_g_pdata, $fb_g_value);			
					
					$this->session->data['event_removecart_code'] = join($fb_g_code);
				}
			}
		}
	}
	public function viewcart() {
		$fb_g_code = array();
		if($this->status && $this->cart->hasProducts()) {
			$fb_g_value = $this->cart->getTotal();
			
			$evname = 'ViewCart'; $evid = rand(); $flag_cust_ev = 1; $fb_g_pdata = $this->cart->getProducts(); 
 			
			$this->callAPIFB($evname, $evid, $flag_cust_ev, $fb_g_pdata, $fb_g_value);
			
			$fb_g_code[] = $this->get_fbpxl($evname, $evid, $flag_cust_ev, $fb_g_pdata, $fb_g_value);			
			
			if(isset($this->session->data['event_removecart_code'])) {
				$fb_g_code[] = $this->session->data['event_removecart_code'];
				unset($this->session->data['event_removecart_code']);
			}
			
			return join($fb_g_code);
		}
	}
	public function beginchk() {
		$fb_g_code = array();
		if($this->status && $this->cart->hasProducts()) {
			$fb_g_value = $this->cart->getTotal();
			
			$evname = 'InitiateCheckout'; $evid = rand(); $flag_cust_ev = 0; $fb_g_pdata = $this->cart->getProducts(); 
			
			$this->callAPIFB($evname, $evid, $flag_cust_ev, $fb_g_pdata, $fb_g_value);
			
			$fb_g_code[] = $this->get_fbpxl($evname, $evid, $flag_cust_ev, $fb_g_pdata, $fb_g_value);			
			
			return join($fb_g_code);
		}
	}
	public function purchasebefore() {
		if(isset($this->session->data['order_id'])) { 
			$this->session->data['fbcapidyad_order_id'] = $this->session->data['order_id'];
		} else if(isset($this->session->data['xsuccess_order_id'])) { 
			$this->session->data['fbcapidyad_order_id'] = $this->session->data['xsuccess_order_id'];
		} else {
			$this->session->data['fbcapidyad_order_id'] = $this->getorderid();
		}
	}
	public function purchase() {
		$this->purchasebefore();
		$fb_g_code = array();
		if($this->status && !empty($this->session->data['fbcapidyad_order_id'])) {
			$this->set_ord_flg($this->session->data['fbcapidyad_order_id']);
			
			$order_id = $this->session->data['fbcapidyad_order_id'];
			unset($this->session->data['fbcapidyad_order_id']);			
			
			$this->load->model('checkout/order');
 			$orderdata = $this->model_checkout_order->getOrder($order_id);
 			$orderdata['order_products'] = $this->getorderproduct($order_id); 
			$orderdata['order_tax'] = $this->getordertax($order_id);
			$orderdata['order_shipping'] = $this->getordershipping($order_id);
			$orderdata['order_coupon'] = $this->getordercoupon($order_id);
			
			$fb_g_value = $orderdata['total'];
			
			$evname = 'Purchase'; $evid = rand(); $flag_cust_ev = 0; $fb_g_pdata = $orderdata['order_products']; $flag_nm = 0; $srchstr = '';
			
			$this->callAPIFB($evname, $evid, $flag_cust_ev, $fb_g_pdata, $fb_g_value, $flag_nm, $srchstr, $orderdata);
			
			$fb_g_code[] = $this->get_fbpxl($evname, $evid, $flag_cust_ev, $fb_g_pdata, $fb_g_value, $flag_nm, $srchstr, $orderdata);
			
			return join($fb_g_code);
		}
	}
	
	// Helpers
	public function get_fbpxl($evname, $evid, $flag_cust_ev = 0, $fb_g_pdata = array(), $fb_g_value = 0, $flag_nm = 0, $srchstr = '', $orderdata = array()) {
 		if($this->status && $this->setting['pxid']) {			
			$cnt = -1; 
			$num_items = array(); 			
			$content_ids = array();
			$contents = array();					
					
 			if($fb_g_pdata) { 
				foreach ($fb_g_pdata as $pinfo) {
					$cnt++;
					$catname = $this->getcatname($pinfo['product_id']);
					$brand_name = $this->getbrandname($pinfo['product_id']);				
					
					$content_ids[] = $pinfo['product_id'];
					$num_items[] = $pinfo['quantity'];
					$contents[$cnt] = array(
						"id" => $pinfo['product_id'],
						"quantity" => $pinfo['quantity'],
					);
				}
			}
			
			$pxldata = array(
				"value" => $this->getcurval($fb_g_value),
				"currency" => $this->session->data['currency'],
			);
			if($content_ids) {
				$pxldata['content_ids'] = $content_ids;
				$pxldata['content_type'] = 'product';
				$pxldata['contents'] = $contents;
				$pxldata['num_items'] = array_sum($num_items);
			}
			if($flag_nm) {
 				if(!empty($catname)) { $pxldata['content_category'] = $catname; }
				if(!empty($fb_g_pdata)) { $pname = reset($fb_g_pdata); $pxldata['content_name'] = htmlspecialchars_decode(strip_tags($pname['name'])); }
				if(!empty($srchstr)) { $pxldata['search_string'] = htmlspecialchars_decode(strip_tags($srchstr)); }
			}
			if($orderdata) {			
				$pxldata['order_id'] = $orderdata['order_id'];
				$pxldata['content_category'] = 'Purchase';
			}

if($flag_cust_ev == 1) { 
return "<script type='text/javascript'> fbq('trackCustom', '".$evname."', ".json_encode($pxldata,true).", {eventID: '".$evid."'}); </script>";
} else {
return "<script type='text/javascript'> fbq('track', '".$evname."', ".json_encode($pxldata,true).", {eventID: '".$evid."'}); </script>";
}
		}
	}
	public function callAPIFB($evname, $evid, $flag_cust_ev = 0, $fb_g_pdata = array(), $fb_g_value = 0, $flag_nm = 0, $srchstr = '', $orderdata = array()) {		
		if($this->status && $this->setting['apitok'] && $this->setting['pxid']) {
			$cnt = -1; 
			$num_items = array(); 			
			$content_ids = array();
			$contents = array();					
					
 			if($fb_g_pdata) { 
				foreach ($fb_g_pdata as $pinfo) {
					$cnt++;
					$catname = $this->getcatname($pinfo['product_id']);
					$brand_name = $this->getbrandname($pinfo['product_id']);				
					
					$content_ids[] = $pinfo['product_id'];
					$num_items[] = $pinfo['quantity'];
					$contents[$cnt] = array(
						"id" => $pinfo['product_id'],
						"quantity" => $pinfo['quantity'],
					);
				}
			}
			
			$json = array(
				"event_name" => $evname,
				"event_id" => $evid,
				"event_time" => time(),
				"event_source_url" => $this->get_page_url(),
				"action_source" => 'website',				
				"custom_data" => array(
 					"currency" => $this->session->data['currency'],
					"value" => $this->getcurval($fb_g_value),
 				),
			);
			
			$json['user_data'] = $this->getuserdata(); 
						
 			if($content_ids) {
				$json['custom_data']['content_ids'] = $content_ids;
				$json['custom_data']['content_type'] = 'product';
				$json['custom_data']['contents'] = $contents;
				$json['custom_data']['num_items'] = array_sum($num_items);
			}
			if($flag_nm) {			
 				if(!empty($catname)) { $json['custom_data']['content_category'] = $catname; }
				if(!empty($fb_g_pdata)) { $pname = reset($fb_g_pdata); $json['custom_data']['content_name'] = htmlspecialchars_decode(strip_tags($pname['name'])); }
				if(!empty($srchstr)) { $json['custom_data']['search_string'] = htmlspecialchars_decode(strip_tags($srchstr)); }
			}
			if($orderdata) {
				$json['custom_data']['order_id'] = $orderdata['order_id'];
				$json['custom_data']['tax'] = $orderdata['order_tax'];
				$json['custom_data']['shipping_charge'] = $orderdata['order_shipping'];
			}
  			
 			
 			// API START			
			$url = 'https://graph.facebook.com/v18.0/'.$this->setting['pxid'].'/events';
			
 			$fields = array();
			$fields['data'] = json_encode(array($json));
			$fields['access_token'] = $this->setting['apitok'];
			if($this->setting['evcd']) { 
				$fields['test_event_code'] = $this->setting['evcd'];
			} 
  			
			$result = '';
			if(function_exists('curl_version')) { 
				$ch = curl_init();
				curl_setopt_array($ch, array(
				  CURLOPT_URL => $url, 
				  CURLOPT_RETURNTRANSFER => true,
				  CURLOPT_ENCODING => "",
				  CURLOPT_MAXREDIRS => 10,
				  CURLOPT_TIMEOUT => 30,
				  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				  CURLOPT_CUSTOMREQUEST => "POST",
				  CURLOPT_POSTFIELDS => http_build_query($fields),
				  CURLOPT_HTTPHEADER => array(
					"cache-control: no-cache",
					"Accept: application/json"  
				  ),
				));
 				$result = curl_exec($ch);
				curl_close($ch);
			} else {
				$this->log->write('curl is disbaled for fb pixel CAPI');
			}
			
			//$this->log->write($result); 
			//$this->log->write($json);
			
			return $result;
		}
	}
	public function getuserdata() {
		$user_data = array(
			"em" => array("309a0a5c3e211326ae75ca18196d301a9bdbd1a882a4d2569511033da23f0abd"),
			"ph" => array("254aa248acb47dd654ca3ea53f48c2c26d641d23d7e2e93a1ec56258df7674c4"),
			"fn" => array("78675cc176081372c43abab3ea9fb70c74381eb02dc6e93fb6d44d161da6eeb3"),
			"ln" => array("6627835f988e2c5e50533d491163072d3f4f41f5c8b04630150debb3722ca2dd"),
			
		"ge" => array("252f10c83610ebca1a059c0bae8255eba2f95be4d1d7bcfa89d7248a82d9f111"),
		"db" => array("069068313b0b2dd680b9f0b8082228a817fb100433ad1d825ccc4d20123669a8"),			
		
			"ct" => array("0007a3d64c01e23d5075203c5977c14e4f7e01a3fc570686fcc50b5e1dd42685"),
			"zp" => array("1794327a4d443904421f5ca1f3d72d0a62ba2f0770d80755ec2220fc0efb052e"),
			"st" => array("6959097001d10501ac7d54c0bdb8db61420f658f2922cc26e46d536119a31126"),
			"country" => array("79adb2a2fce5c6ba215fe5f27f532d4e7edbac4b6a5e09e1ef3a08084a904621"),
		); 
		if(isset($_SERVER['HTTP_USER_AGENT'])) {
			$user_data['client_user_agent'] = $_SERVER['HTTP_USER_AGENT'];
		}
		if(isset($_SERVER['REMOTE_ADDR'])) {
			$user_data['client_ip_address'] = $_SERVER['REMOTE_ADDR'];
		}
		if (isset($this->request->cookie['_fbc'])) {
			$user_data['fbc'] = $this->request->cookie['_fbc'];
		}
		if (isset($this->request->cookie['_fbp'])) {
			$user_data['fbp'] = $this->request->cookie['_fbp'];
		}
		if(! isset($this->session->data['cmpgaadfb_extid'])) {
			$this->session->data['cmpgaadfb_extid'] = rand().rand();			
		}
		$user_data['external_id'] = array(hash('sha256', $this->session->data['cmpgaadfb_extid']));
		
		if($this->customer->getEmail()) {
			$user_data['em'] = array(hash('sha256', $this->customer->getEmail()));
		}
		if($this->customer->getTelephone()) {
			$user_data['ph'] = array(hash('sha256', $this->customer->getTelephone()));
		}
		if($this->customer->getFirstName()) {
			$user_data['fn'] = array(hash('sha256', $this->customer->getFirstName()));
		}
		if($this->customer->getLastName()) {
			$user_data['ln'] = array(hash('sha256', $this->customer->getLastName()));
		}
		
		return $user_data;		
	}
	public function getmicrotag($pinfo){
		// pixel dynamic ads
		$adscode = ''; 
		$server = $this->config->get('config_url');
		if (isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1'))) {
			$server = $this->config->get('config_ssl');
		}
		
		$catname = $this->getcatname($pinfo['product_id']);
		$brand_name = $this->getbrandname($pinfo['product_id']);				
		$stockst = $pinfo['quantity'] > 0 ? 'InStock' : 'OutofStock';
		$price = $this->tax->calculate(($pinfo['price']), $pinfo['tax_class_id'], $this->config->get('config_tax'));
		
		$adscode .= '<div itemscope itemtype="https://schema.org/Product">';
			$adscode .= '<meta itemprop="brand" content="'.$brand_name.'">';
			$adscode .= '<meta itemprop="name" content="'.$pinfo['name'].'">';
			$adscode .= '<meta itemprop="productID" content="'.$pinfo['product_id'].'">';
			$adscode .= '<meta itemprop="description" content="'.$pinfo['name'].'">';
			$adscode .= '<meta itemprop="url" content="'.$this->url->link('product/product', '&product_id=' . $pinfo['product_id']).'">';
			$adscode .= '<meta itemprop="image" content="'.$server.'image/'.$pinfo['image'].'">';
			$adscode .= '<meta itemprop="google_product_category" content="377">';
			$adscode .= '<div itemprop="offers" itemscope itemtype="https://schema.org/Offer">';
				$adscode .= '<link itemprop="availability" href="https://schema.org/'.$stockst.'">';
				$adscode .= '<link itemprop="itemCondition" href="https://schema.org/NewCondition">';
				$adscode .= '<meta itemprop="price" content="'.$this->getcurval($price).'">';
				$adscode .= '<meta itemprop="priceCurrency" content="'.$this->session->data['currency'].'">';
			$adscode .= '</div>';	
		$adscode .= '</div>';
		
		return $adscode;
	}
	public function get_page_url() {
		$url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https://" : "https://";		 
		$url.= $_SERVER['HTTP_HOST'];
		$url.= $_SERVER['REQUEST_URI'];
		return $url;
	}
	public function set_ord_flg($order_id) {
		$this->db->query("UPDATE `" . DB_PREFIX . "order` set fbcapidyad_ordflag = 1 where order_id = '" . (int)$order_id . "' ");		
	}
	public function getorderid() {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` WHERE fbcapidyad_ordflag = 0 and date(date_added) >= curdate() and order_status_id > 0 AND ip like '" . $this->db->escape($this->request->server['REMOTE_ADDR']) . "' order by date_added desc limit 1");		
		if($query->num_rows) {
			return $query->row['order_id'];
		}
		return 0;
	}
	public function getProduct($pid) {
		if($pid) { 
			$query = $this->db->query("SELECT DISTINCT *, pd.name, pd.meta_description, (SELECT price FROM " . DB_PREFIX . "product_discount pd2 WHERE pd2.product_id = p.product_id AND pd2.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND pd2.quantity = '1' AND ((pd2.date_start = '0000-00-00' OR pd2.date_start < NOW()) AND (pd2.date_end = '0000-00-00' OR pd2.date_end > NOW())) ORDER BY pd2.priority ASC, pd2.price ASC LIMIT 1) AS discount, (SELECT price FROM " . DB_PREFIX . "product_special ps WHERE ps.product_id = p.product_id AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())) ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) AS special FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) WHERE p.product_id = '" . (int)$pid . "' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
			
			if ($query->num_rows) {
				$query->row['price'] = $query->row['discount'] ? $query->row['discount'] : $query->row['price'];
				return $query->row;
			} else {
				return false;
			}
		}
		return false;
	}
	public function getstorename() {
		$stq = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "store WHERE store_id = '".(int)$this->config->get('config_store_id')."' ");
		return htmlspecialchars_decode(strip_tags(isset($stq->row['name']) ? $stq->row['name'] : $this->config->get('config_name')));
	}
	public function getcatname($product_id) {
		if($product_id) { 
			$query = $this->db->query("SELECT name FROM " . DB_PREFIX . "category_description cd 
			INNER JOIN " . DB_PREFIX . "product_to_category pc ON pc.category_id = cd.category_id 
			WHERE 1 AND pc.product_id = '".$product_id."' AND cd.language_id = '". (int)$this->config->get('config_language_id') ."' limit 1");
			return htmlspecialchars_decode(strip_tags((!empty($query->row['name'])) ? $query->row['name'] : ''));
		} 
		return '';
	}
	public function getcatnamefromID($category_id) {
		if($category_id) { 
			$query = $this->db->query("SELECT name FROM " . DB_PREFIX . "category_description cd
			WHERE 1 AND category_id = '".$category_id."' AND cd.language_id = '". (int)$this->config->get('config_language_id') ."' limit 1");
			return htmlspecialchars_decode(strip_tags((!empty($query->row['name'])) ? $query->row['name'] : ''));
		} 
		return '';
	}
	public function getbrandname($pid) {
		if($pid) { 
			$query = $this->db->query("SELECT name from " . DB_PREFIX . "manufacturer m INNER JOIN " . DB_PREFIX . "product p on m.manufacturer_id = p.manufacturer_id WHERE 1 AND p.product_id = ".$pid);
			return htmlspecialchars_decode(strip_tags((!empty($query->row['name'])) ? $query->row['name'] : ''));
		}
		return '';
	}
	public function getprorel($pid) {
		$q = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_related pr 
		LEFT JOIN " . DB_PREFIX . "product p ON (pr.related_id = p.product_id) 
		LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) 
		WHERE pr.product_id = '" . (int)$pid . "' AND p.status = '1' 
		AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'");
		return $q->rows;
	}
	public function getcategory($category_id) {
		$sql = "SELECT p.product_id FROM " . DB_PREFIX . "product p 
		LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) 
		LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) 
		LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id)
		WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' 
		AND p2c.category_id = '" . (int)$category_id . "'
		AND p.status = '1' AND p.date_available <= NOW() 
		AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'";
		$sql .= " GROUP BY p.product_id LIMIT 5";
		
		$query = $this->db->query($sql);
			
		return $query->rows;
	}
	public function getsearchrs($srchstr) {
		$filter_data = array('filter_name' => $srchstr, 'start' => 0, 'limit' => 5);
		
		$sql = "SELECT p.product_id FROM " . DB_PREFIX . "product p 
		LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) 
		LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) 
		WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' 
		AND p.status = '1' AND p.date_available <= NOW() 
		AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'";
		$data['filter_name'] = $srchstr;
		if (!empty($data['filter_name'])) {
			$sql .= " AND ( pd.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
			$sql .= " OR LCASE(p.model) = '" . $this->db->escape(strtolower($data['filter_name'])) . "'";
			$sql .= " OR LCASE(p.sku) = '" . $this->db->escape(strtolower($data['filter_name'])) . "'";
			$sql .= " OR LCASE(p.upc) = '" . $this->db->escape(strtolower($data['filter_name'])) . "'";
			$sql .= " OR LCASE(p.ean) = '" . $this->db->escape(strtolower($data['filter_name'])) . "'";
			$sql .= " OR LCASE(p.jan) = '" . $this->db->escape(strtolower($data['filter_name'])) . "'";
			$sql .= " OR LCASE(p.isbn) = '" . $this->db->escape(strtolower($data['filter_name'])) . "'";
			$sql .= " OR LCASE(p.mpn) = '" . $this->db->escape(strtolower($data['filter_name'])) . "'";
			$sql .= ")";
		}
		$sql .= " GROUP BY p.product_id LIMIT 5";
		
		$query = $this->db->query($sql);
			
		return $query->rows;
	}
	public function getorderproduct($order_id) {
 		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "' ");
 		return $query->rows;
	}
	public function getordertax($order_id) {
 		$q = $this->db->query("SELECT sum(value) as taxval FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int)$order_id . "' AND code = 'tax'");
		if (isset($q->row['taxval']) && $q->row['taxval']) {
			return $this->getcurval($q->row['taxval']);
		} 
		return 0;
	}
	public function getordershipping($order_id) {
 		$q = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int)$order_id . "' AND code = 'shipping'");
		if (isset($q->row['value']) && $q->row['value']) {
			return $this->getcurval($q->row['value']);
		} 
		return 0;
	}
	public function getordercoupon($order_id) {
 		$q = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int)$order_id . "' AND code = 'coupon'");
		if (isset($q->row['value']) && abs($q->row['value'])) {
			$couponcode = explode("(", $q->row['title']);
			return array("couponcode" => str_replace(")","",$couponcode[1]), "discount" => $this->getcurval(abs($q->row['value'])));
		} 
		return false;
	}
	public function getcurval($taxprc) {
		return round($this->currency->format($taxprc, $this->session->data['currency'], false, false),2);
	}
	public function GetIP() {
		if (isset($_SERVER['HTTP_CLIENT_IP']))
			$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
		else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
			$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
		else if(isset($_SERVER['HTTP_X_FORWARDED']))
			$ipaddress = $_SERVER['HTTP_X_FORWARDED'];
		else if(isset($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']))
			$ipaddress = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
		else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
			$ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
		else if(isset($_SERVER['HTTP_FORWARDED']))
			$ipaddress = $_SERVER['HTTP_FORWARDED'];
		else if(isset($this->request->server['REMOTE_ADDR']))
			$ipaddress = $this->request->server['REMOTE_ADDR'];
		else
			$ipaddress = 0;
		return $ipaddress;
	}	
	public function getSetting() {		
		$storeid = (int)$this->config->get('config_store_id');
		$cgid = (int)$this->config->get('config_customer_group_id');
		$langid = (int)$this->config->get('config_language_id');
 		$setting = $this->config->get($this->modname.'_setting');
				
 		$setting['status'] = (!isset($setting[$storeid]['status'])) ? false : $setting[$storeid]['status'];
		$setting['pxid'] = (!isset($setting[$storeid]['pxid'])) ? '' : $setting[$storeid]['pxid'];
		$setting['apitok'] = (!isset($setting[$storeid]['apitok'])) ? '' : $setting[$storeid]['apitok'];
		$setting['evcd'] = (!isset($setting[$storeid]['evcd'])) ? '' : $setting[$storeid]['evcd'];
		
 		return $setting;		
	}
	public function loadjscss() {
		if($this->status) {
			$ocstr = substr(VERSION,0,3)=='4.0' ? 'extension/fbcapidyad/' : '';
			$this->document->addScript($ocstr.'catalog/view/javascript/fbcapidyad.js?vr='.rand());
			//$this->document->addStyle($ocstr.'catalog/view/javascript/fbcapidyad.css?vr='.rand());			
		}			
	}
}