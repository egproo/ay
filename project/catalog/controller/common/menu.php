<?php
class ControllerCommonMenu extends Controller {
	public function index() {
		$this->load->language('common/menu');

		// Menu
		$this->load->model('catalog/category');

		$this->load->model('catalog/product');
		$this->load->model('tool/image');

		$data['categories'] = [];
		$categories = $this->model_catalog_category->getCategories(0);

		foreach ($categories as $category) {
				if (is_file(DIR_IMAGE . html_entity_decode($category['image'], ENT_QUOTES, 'UTF-8'))) {
					$categoryimage = 'image/'.$category['image'];
				} else {
					$categoryimage = $this->model_tool_image->resize('placeholder.png', 100,100);
				}		    
			if ($category['top']) {
				// Level 2
				$children_data = [];

				$children = $this->model_catalog_category->getCategories($category['category_id']);

				foreach ($children as $child) {
					if (is_file(DIR_IMAGE . html_entity_decode($child['image'], ENT_QUOTES, 'UTF-8'))) {
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
						'href'  => $this->url->link('product/category', 'path=' . $category['category_id'] . '_' . $child['category_id'])

					];
				}

				// Level 1
				$data['categories'][] = [
				    'id'       => $category['category_id'],
					'name'     => $category['name'],
					'image'       => $categoryimage,
					'children' => $children_data,
					'column'   => $category['column'] ? $category['column'] : 1,
					'href'     => $this->url->link('product/category', 'path=' . $category['category_id'])
				];
			}
		}
		$data['specials'] = $this->url->link('product/special', (isset($this->session->data['customer_token']) ? 'customer_token=' . $this->session->data['customer_token'] : ''));

		if(isset($this->session->data['userdevice'])){
        	$userdevice = $this->session->data['userdevice'];//get it from stratup/session
		}else if(isset($this->request->cookie['userdevice'])){
        	$userdevice = $this->request->cookie['userdevice'];//get it from stratup/session
		}else{
			$userdevice = "None";
		}
		if($userdevice == 'mobile'){
		return $this->load->view('common/menum', $data);
		}else{
		return $this->load->view('common/menu', $data);
		}
	}
	
	
}
