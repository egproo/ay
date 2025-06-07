<?php
class ControllerExtensionModuleProductspro extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/module/productspro');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/module');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			if (!isset($this->request->get['module_id'])) {
				$this->model_setting_module->addModule('productspro', $this->request->post);
			} else {
				$this->model_setting_module->editModule($this->request->get['module_id'], $this->request->post);
			}

			$this->cache->delete('product');

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('store/setting', 'user_token=' . $this->session->data['user_token'], true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		} else {
			$data['error_name'] = '';
		}

		if (isset($this->error['width'])) {
			$data['error_width'] = $this->error['width'];
		} else {
			$data['error_width'] = '';
		}

		if (isset($this->error['height'])) {
			$data['error_height'] = $this->error['height'];
		} else {
			$data['error_height'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);

		if (!isset($this->request->get['module_id'])) {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/module/productspro', 'user_token=' . $this->session->data['user_token'], true)
			);
		} else {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/module/productspro', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], true)
			);
		}

		if (!isset($this->request->get['module_id'])) {
			$data['action'] = $this->url->link('extension/module/productspro', 'user_token=' . $this->session->data['user_token'], true);
		} else {
			$data['action'] = $this->url->link('extension/module/productspro', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], true);
		}

		$data['cancel'] = $this->url->link('store/setting', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);
		$this->load->model('localisation/language');
		$languages = $this->model_localisation_language->getLanguages();

		$data['languages'] = $this->model_localisation_language->getLanguages();
		
		if (isset($this->request->get['module_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$module_info = $this->model_setting_module->getModule($this->request->get['module_id']);
			   // print_r($module_info);

		}

		if (isset($this->request->post['name'])) {
			$data['name'] = $this->request->post['name'];
		} elseif (!empty($module_info)) {
			$data['name'] = $module_info['name'];
		} else {
			$data['name'] = '';
		}
		if (isset($module_info['product_count'])) {
			$data['product_count'] = $module_info['product_count'];
		} else {
			$data['product_count'] = 20;
		}		
		
        foreach($languages as $lang){
    		if (isset($module_info['title'])) {
    			$data['title'][$lang['language_id']] = $module_info['title'][$lang['language_id']];
    		} else {
    			$data['title'][$lang['language_id']] = '';
    		}
		}
		$this->load->model('catalog/product');

		$data['ccproducts'] = [];

		if (!empty($module_info['product'])) {
			$products = $module_info['product'];
		} else {
			$products = [];
		}

		foreach ($products as $product_id) {
			$product_info = $this->model_catalog_product->getProduct($product_id);

			if ($product_info) {
				$data['ccproducts'][] = [
					'product_id' => $product_info['product_id'],
					'name'       => $product_info['name']
				];
			}
		}


		$this->load->model('catalog/category');

		$data['product_categories'] = [];

		if (!empty($module_info['product_category'])) {
			$categories = $module_info['product_category'];
		} else {
			$categories = [];
		}

		foreach ($categories as $category_id) {
			$category_info = $this->model_catalog_category->getCategory($category_id);

			if ($category_info) {
				$data['product_categories'][] = [
					'category_id' => $category_info['category_id'],
					'name'       => $category_info['name']
				];
			}
		}
		$this->load->model('catalog/filter');
		$data['product_filters'] = [];
		if (!empty($module_info['product_filter'])) {
			$filters = $module_info['product_filter'];
		} else {
			$filters = [];
		}
		foreach ($filters as $filter_id) {
			$filter_info = $this->model_catalog_filter->getFilter((int)$filter_id);

			if ($filter_info) {
				$data['product_filters'][] = [
					'filter_id' => $filter_info['filter_id'],
					'name'      => $filter_info['group'] . ' &gt; ' . $filter_info['name']
				];
			}
		}
		$this->load->model('catalog/option');
		$data['product_options'] = [];

		if (!empty($module_info['product_option'])) {
			$options = $module_info['product_option'];
		} else {
			$options = [];
		}
		
		foreach ($options as $option) {
			$option_value = $this->model_catalog_option->getValue($option);
			$option_info = $this->model_catalog_option->getOption($option_value['option_id']);
				$data['product_options'][] = [
    					'option_id'    => $option_value['option_value_id'],
    					'name'      => strip_tags(html_entity_decode($option_info['name'] . ' &gt; ' . $option_value['name'], ENT_QUOTES, 'UTF-8')),
				];    				
		}
			
		$data['product_tags'] = [];
		if (!empty($module_info['product_tag'])) {
			$tags = $module_info['product_tag'];
		} else {
			$tags = [];
		}    
		foreach ($tags as $tag) {
    			$data['product_tags'][] = [
    				'tag' => $tag,
    				'name'      => $tag
    			];                
                       
        }
		
		$this->load->model('catalog/manufacturer');

		$data['product_manufacturers'] = [];

		if (!empty($module_info['product_manufacturer'])) {
			$manufacturers = $module_info['product_manufacturer'];
		} else {
			$manufacturers = [];
		}

		foreach ($manufacturers as $manufacturer) {
			$manufacturer_info = $this->model_catalog_manufacturer->getManufacturer((int)$manufacturer);

			if ($manufacturer_info) {
				$data['product_manufacturers'][] = [
					'manufacturer_id' => $manufacturer_info['manufacturer_id'],
					'name'       => $manufacturer_info['name']
				];
			}
		}		
		if (!empty($module_info['axis'])) {
			$data['axis'] = $module_info['axis'];
		} else {
			$data['axis'] ='';
		}
		if (!empty($module_info['type'])) {
			$data['type'] = $module_info['type'];
		} else {
			$data['type'] ='';
		}
		if (!empty($module_info['device'])) {
			$data['device'] = $module_info['device'];
		} else {
			$data['device'] ='';
		}
		if (!empty($module_info['product_type'])) {
			$data['product_type'] = $module_info['product_type'];
		} else {
			$data['product_type'] ='';
		}
		if (isset($this->request->post['limit'])) {
			$data['limit'] = $this->request->post['limit'];
		} elseif (!empty($module_info)) {
			$data['limit'] = $module_info['limit'];
		} else {
			$data['limit'] = 5;
		}

		if (isset($this->request->post['width'])) {
			$data['width'] = $this->request->post['width'];
		} elseif (!empty($module_info)) {
			$data['width'] = $module_info['width'];
		} else {
			$data['width'] = 500;
		}

		if (isset($this->request->post['height'])) {
			$data['height'] = $this->request->post['height'];
		} elseif (!empty($module_info)) {
			$data['height'] = $module_info['height'];
		} else {
			$data['height'] = 500;
		}

		if (isset($this->request->post['status'])) {
			$data['status'] = $this->request->post['status'];
		} elseif (!empty($module_info)) {
			$data['status'] = $module_info['status'];
		} else {
			$data['status'] = '';
		}
		if (isset($this->request->get['module_id'])) {
			$data['module_id'] = (int)$this->request->get['module_id'];
		} else {
			$data['module_id'] = 0;
		}

		$data['user_token'] = $this->session->data['user_token'];
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$this->response->setOutput($this->load->view('extension/module/productspro', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/productspro')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 64)) {
			$this->error['name'] = $this->language->get('error_name');
		}

		if (!$this->request->post['width']) {
			$this->error['width'] = $this->language->get('error_width');
		}

		if (!$this->request->post['height']) {
			$this->error['height'] = $this->language->get('error_height');
		}

		return !$this->error;
	}
}
