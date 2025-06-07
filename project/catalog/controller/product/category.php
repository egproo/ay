<?php
class ControllerProductCategory extends Controller {
    public function getUnitPrice() {
        $json = array();

        if (isset($this->request->post['product_id']) && isset($this->request->post['unit_id'])) {
            $product_id = (int)$this->request->post['product_id'];
            $unit_id = (int)$this->request->post['unit_id'];

            $this->load->model('catalog/product');

            $price = $this->getFormattedPrice($product_id, $unit_id);
            $special = $this->getFormattedSpecialPrice($product_id, $unit_id);
            $quantity_available = $this->model_catalog_product->getAvailableQuantityForOnline($product_id, $unit_id);

            $json = array(
                'price' => $price,
                'special' => $special,
                'quantity_available' => $quantity_available
            );
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    protected function getDefaultUnit($units) {
        foreach ($units as $unit) {
            if ($unit['unit_type'] == 'base') {
                return $unit;
            }
        }
        return $units[0]; // إذا لم يتم العثور على وحدة أساسية، نعيد الوحدة الأولى
    }

    protected function getFormattedPrice($product_id, $unit_id) {
        $price = $this->model_catalog_product->getProductUnitPrice($product_id, $unit_id);
        $price_with_tax = $this->tax->calculate($price, $this->config->get('config_tax_class_id'), $this->config->get('config_tax'));
        return $this->currency->format($price_with_tax, $this->session->data['currency']);
    }

    protected function getFormattedSpecialPrice($product_id, $unit_id) {
        $special = $this->model_catalog_product->getProductUnitSpecialPrice($product_id, $unit_id);
        if ($special > 0) {
            $special_with_tax = $this->tax->calculate($special, $this->config->get('config_tax_class_id'), $this->config->get('config_tax'));
            return $this->currency->format($special_with_tax, $this->session->data['currency']);
        }
        return false;
    }
    
      
    private $category_id = 0;

    public function index() {
        $this->load->language('product/category');
        $this->load->model('catalog/category');
        $this->load->model('catalog/product');
        $this->load->model('tool/image');

    if (isset($this->request->get['path'])) {
        $parts = explode('_', (string)$this->request->get['path']);
        if(!empty($parts)){
        $this->category_id = (int)array_pop($parts);
        }else{
        $this->category_id = (int)$this->request->get['path'];
        }
    } else {
        $this->category_id = 0;
    }

        $category_info = $this->model_catalog_category->getCategory($this->category_id);

        if ($category_info) {
            $this->document->setTitle($category_info['meta_title']);
            $this->document->setDescription($category_info['meta_description']);
            $this->document->setKeywords($category_info['meta_keyword']);

            $data['heading_title'] = $category_info['name'];

            $filter_data = $this->getFilterData();
            $data = $this->getCatalogData($filter_data);

            $data['category_id'] = $this->category_id;
            $data['category_info'] = $category_info;
            $data['filter_groups'] = $this->model_catalog_category->getCategoryFilters($this->category_id);

	$data['sorts'] = array();
		$data['direction'] = $this->language->get('direction');

			$url = '';
			$data['sorts'][] = array(
				'text'  => $this->language->get('text_name_asc'),
				'value' => 'pd.name-ASC',
				'href'  => $this->url->link('product/catalog', 'sort=pd.name&order=ASC' . $url)
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_name_desc'),
				'value' => 'pd.name-DESC',
				'href'  => $this->url->link('product/catalog', 'sort=pd.name&order=DESC' . $url)
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_price_asc'),
				'value' => 'price-ASC',
				'href'  => $this->url->link('product/catalog', 'sort=price&order=ASC' . $url)
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_price_desc'),
				'value' => 'price-DESC',
				'href'  => $this->url->link('product/catalog', 'sort=price&order=DESC' . $url)
			);

			if ($this->config->get('config_review_status')) {
				$data['sorts'][] = array(
					'text'  => $this->language->get('text_rating_desc'),
					'value' => 'rating-DESC',
					'href'  => $this->url->link('product/catalog', 'sort=rating&order=DESC' . $url)
				);

				$data['sorts'][] = array(
					'text'  => $this->language->get('text_rating_asc'),
					'value' => 'rating-ASC',
					'href'  => $this->url->link('product/catalog', 'sort=rating&order=ASC' . $url)
				);
			}

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_model_asc'),
				'value' => 'p.model-ASC',
				'href'  => $this->url->link('product/catalog', 'sort=p.model&order=ASC' . $url)
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_model_desc'),
				'value' => 'p.model-DESC',
				'href'  => $this->url->link('product/catalog', 'sort=p.model&order=DESC' . $url)
			);
			$data['limits'] = array();

			$limits = array_unique(array($this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit'), 1, 2, 3, 4));

			sort($limits);

			foreach($limits as $value) {
				$data['limits'][] = array(
					'text'  => $value,
					'value' => $value,
					'href'  => $this->url->link('product/catalog', $url . '&limit=' . $value)
				);
			}			



            $data['column_left'] = $this->load->controller('common/column_left');
            $data['column_right'] = $this->load->controller('common/column_right');
            $data['content_top'] = $this->load->controller('common/content_top');
            $data['content_bottom'] = $this->load->controller('common/content_bottom');
            $data['footer'] = $this->load->controller('common/footer');
            $data['header'] = $this->load->controller('common/header');     

            if (isset($this->request->get['ajax']) && $this->request->get['ajax'] == 1) {
                $this->responseAjax($filter_data);
                return;
            }

            $this->response->setOutput($this->load->view('product/category', $data));
        } else {
            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_error'),
                'href' => $this->url->link('product/category', 'path=' . $this->category_id)
            );

            $this->document->setTitle($this->language->get('text_error'));

            $data['continue'] = $this->url->link('common/home');

            $this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');

            $data['column_left'] = $this->load->controller('common/column_left');
            $data['column_right'] = $this->load->controller('common/column_right');
            $data['content_top'] = $this->load->controller('common/content_top');
            $data['content_bottom'] = $this->load->controller('common/content_bottom');
            $data['footer'] = $this->load->controller('common/footer');
            $data['header'] = $this->load->controller('common/header');

            $this->response->setOutput($this->load->view('error/not_found', $data));
        }
    }


private function getFilterData() {
    $filter = $this->request->get['filter'] ?? '';
    $sort = $this->request->get['sort'] ?? 'p.sort_order';
    $order = $this->request->get['order'] ?? 'ASC';
    $page = (int)($this->request->get['page'] ?? 1);
    $limit = (int)($this->request->get['limit'] ?? $this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit'));
    $unit = $this->request->get['unit'] ?? '';
    $price_min = $this->request->get['price_min'] ?? '';
    $price_max = $this->request->get['price_max'] ?? '';

    return array(
        'filter_filter'  => $filter,
        'filter_category_id' => $this->category_id,
        'sort'           => $sort,
        'order'          => $order,
        'start'          => ($page - 1) * $limit,
        'limit'          => $limit,
        'filter_unit'    => $unit,
        'filter_price_min' => $price_min,
        'filter_price_max' => $price_max
    );
}

    private function getCatalogData($filter_data) {
        $data['products'] = array();
        $results = $this->model_catalog_product->getProducts($filter_data);
        $product_ids = array_column($results, 'product_id');

        $units = $this->model_catalog_product->getProductsUnits($product_ids, $filter_data);
        $data['units'] = $this->getUniqueUnits($units);
        
        $price_range = $this->model_catalog_product->getProductPriceRange($filter_data);
        $data['price_range'] = [
            'min' => $price_range['min_price'],
            'max' => $price_range['max_price']
        ];   
        
        foreach ($results as $result) {
            $data['products'][] = $this->formatProductData($result);
        }

        return $data;
    }

private function responseAjax($filter_data) {
    try {
        $this->load->model('catalog/product');

        $results = $this->model_catalog_product->getProducts($filter_data);
     
        $json = array();

        $json['products'] = array();
        foreach ($results as $result) {
            $json['products'][] = $this->load->controller('product/thumb', $this->formatProductData($result));
        }
        $json['category_id'] = $this->category_id;

        $product_ids = array_column($results, 'product_id');
        $units = $this->model_catalog_product->getProductsUnits($product_ids, $filter_data);
        $json['units'] = $this->getUniqueUnits($units);

        $json['endOfData'] = count($results) < $filter_data['limit'];
     $json['totalFilteredProducts'] =   count($results); 
        $price_range = $this->model_catalog_product->getProductPriceRange($filter_data);
        $json['price_range'] = [
            'min' => $price_range['min_price'],
            'max' => $price_range['max_price']
        ];
        

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    } catch (Exception $e) {
        $this->response->addHeader('HTTP/1.0 500 Internal Server Error');
        $this->response->setOutput(json_encode(['error' => $e->getMessage()]));
    }
}

    private function getUniqueUnits($units) {
        $unique_units = array();
        foreach ($units as $product_units) {
            foreach ($product_units as $unit) {
                $unit_key = $unit['unit_id'] . '_' . $unit['unit_name'];
                if (!isset($unique_units[$unit_key])) {
                    $unique_units[$unit_key] = $unit;
                }
            }
        }
        return array_values($unique_units);
    }

    private function formatProductData($result) {
 							$dataoptions = [];

			$product_options = $this->model_catalog_product->getOptions($result['product_id']);
			$product_id = (int)$result['product_id'];
			
        				$product_info = $this->model_catalog_product->getProduct($product_id);

			foreach ($product_options as $option) {
				if ($product_id && !isset($product_info['override']['variant'][$option['product_option_id']])) {
					$product_option_value_data = [];

					foreach ($option['product_option_value'] as $option_value) {
						if (!$option_value['subtract'] || ($option_value['quantity'] > 0)) {
							if ((($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) && (float)$option_value['price']) {
								$price = $this->currency->format($this->tax->calculate($option_value['price'], $product_info['tax_class_id'], $this->config->get('config_tax') ? 'P' : false), $this->session->data['currency']);
							} else {
								$price = false;
							}

							if (is_file(DIR_IMAGE . html_entity_decode($option_value['image'], ENT_QUOTES, 'UTF-8'))) {
								$image = $this->model_tool_image->resize(html_entity_decode($option_value['image'], ENT_QUOTES, 'UTF-8'), 50, 50);
							} else {
								$image = '';
							}

							$product_option_value_data[] = [
								'product_option_value_id' => $option_value['product_option_value_id'],
								'option_value_id'         => $option_value['option_value_id'],
								'name'                    => $option_value['name'],
								'image'                   => $image,
								'price'                   => $price,
								'price_prefix'            => ''
							];
						}
					}

					$dataoptions[] = [
						'product_option_id'    => $option['product_option_id'],
						'product_option_value' => $product_option_value_data,
						'option_id'            => $option['option_id'],
						'name'                 => $option['name'],
						'type'                 => $option['type'],
						'value'                => $option['value'],
						'required'             => $option['required']
					];
				}
			}	
			

				if ($result['image']) {
					$image = $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
				} else {
					$image = $this->model_tool_image->resize('placeholder.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
				}


        
        				if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
        				                        $units = $this->model_catalog_product->getProductUnits($result['product_id']);

                        $price = $this->model_catalog_product->getProductUnitPrice($result['product_id'], $this->getDefaultUnit($units)['unit_id']);
                        $price_formatted = $this->currency->format($this->tax->calculate($price, $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                        } else {
        					$price = false;
        				}
        
                    $special = $this->model_catalog_product->getProductUnitSpecialPrice($result['product_id'], $this->getDefaultUnit($units)['unit_id']);
                    if ($special > 0) {
                        $special_formatted = $this->currency->format($this->tax->calculate($special, $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                    } else {
                        $special_formatted = false;
                    }
        
        				if ($this->config->get('config_tax')) {
        					$tax = $this->currency->format((float)$result['special'] ? $result['special'] : $result['price'], $this->session->data['currency']);
        				} else {
        					$tax = false;
        				}
        
$category_info = $this->model_catalog_product->getProductCategories($result['product_id']);

$category = '';
$category_href = '';
if ($category_info) {
    $category = $category_info[0]['name'];
    $category_href = $this->url->link('product/category', 'path=' . $category_info[0]['category_id']);
}

$brand = '';
$brand_href = '';
$this->load->model('catalog/manufacturer');
$manufacturer_info = $this->model_catalog_manufacturer->getManufacturer($result['manufacturer_id']);
if ($manufacturer_info) {
    $brand = $manufacturer_info['name'];
    $brand_href = $this->url->link('product/manufacturer', 'manufacturer_id=' . $result['manufacturer_id']);
}
$units = $this->model_catalog_product->getProductUnits($result['product_id']);
$default_unit = $this->getDefaultUnit($units);
$product = $this->model_catalog_product->getProduct($result['product_id']);
$product_data = [
    'product_id'     => $result['product_id'],
    'quantity'     => $this->model_catalog_product->getAvailableQuantityForOnline($result['product_id'], $this->getDefaultUnit($units)['unit_id']),
    'module_id' => 1,
    'thumb'          => $image,
    'name'           => $result['name'],
    'description'    => utf8_strlen(strip_tags(html_entity_decode($product['description'], ENT_QUOTES, 'UTF-8')), 0, $this->config->get('config_product_description_length')) . '..',
    'price'          => $price_formatted,
    'special'        => $special_formatted,
    'tax'            => $tax,
    'minimum'        => $result['minimum'] > 0 ? $result['minimum'] : 1,
    'rating'         => (int)$product['rating'],
    'category'       => $category,
    'category_href'  => $category_href,
    'units'          => $units,
    'default_unit'   => $default_unit,    
    'brand'          => $brand,
    'brand_href'     => $brand_href,
    'options'        => $dataoptions,
    'href'           => $this->url->link('product/product', 'product_id=' . $result['product_id'])
];

return $product_data;
}




}