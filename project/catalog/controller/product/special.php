<?php
class ControllerProductSpecial extends Controller {
    public function index() {
        $this->load->language('product/special');
        $this->load->model('catalog/product');
        $this->load->model('tool/image');

        $filter_data = $this->getFilterData();
        $data = $this->getSearchData($filter_data);

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $data['sorts'] = array();
        $data['direction'] = $this->language->get('direction');

        $url = '';

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        if (isset($this->request->get['limit'])) {
            $url .= '&limit=' . $this->request->get['limit'];
        }

        $data['sorts'][] = array(
            'text'  => $this->language->get('text_default'),
            'value' => 'p.sort_order-ASC',
            'href'  => $this->url->link('product/special', 'sort=p.sort_order&order=ASC' . $url)
        );

        $data['sorts'][] = array(
            'text'  => $this->language->get('text_name_asc'),
            'value' => 'pd.name-ASC',
            'href'  => $this->url->link('product/special', 'sort=pd.name&order=ASC' . $url)
        );

        $data['sorts'][] = array(
            'text'  => $this->language->get('text_name_desc'),
            'value' => 'pd.name-DESC',
            'href'  => $this->url->link('product/special', 'sort=pd.name&order=DESC' . $url)
        );

        $data['sorts'][] = array(
            'text'  => $this->language->get('text_price_asc'),
            'value' => 'ps.price-ASC',
            'href'  => $this->url->link('product/special', 'sort=ps.price&order=ASC' . $url)
        );

        $data['sorts'][] = array(
            'text'  => $this->language->get('text_price_desc'),
            'value' => 'ps.price-DESC',
            'href'  => $this->url->link('product/special', 'sort=ps.price&order=DESC' . $url)
        );

        if ($this->config->get('config_review_status')) {
            $data['sorts'][] = array(
                'text'  => $this->language->get('text_rating_desc'),
                'value' => 'rating-DESC',
                'href'  => $this->url->link('product/special', 'sort=rating&order=DESC' . $url)
            );

            $data['sorts'][] = array(
                'text'  => $this->language->get('text_rating_asc'),
                'value' => 'rating-ASC',
                'href'  => $this->url->link('product/special', 'sort=rating&order=ASC' . $url)
            );
        }

        $data['sorts'][] = array(
            'text'  => $this->language->get('text_model_asc'),
            'value' => 'p.model-ASC',
            'href'  => $this->url->link('product/special', 'sort=p.model&order=ASC' . $url)
        );

        $data['sorts'][] = array(
            'text'  => $this->language->get('text_model_desc'),
            'value' => 'p.model-DESC',
            'href'  => $this->url->link('product/special', 'sort=p.model&order=DESC' . $url)
        );

        $url = '';

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        if (isset($this->request->get['limit'])) {
            $url .= '&limit=' . $this->request->get['limit'];
        }

        $data['limits'] = array();

        $limits = array_unique(array($this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit'), 25, 50, 75, 100));

        sort($limits);

        foreach ($limits as $value) {
            $data['limits'][] = array(
                'text'  => $value,
                'value' => $value,
                'href'  => $this->url->link('product/special', $url . '&limit=' . $value)
            );
        }

        $url = '';

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        if (isset($this->request->get['limit'])) {
            $url .= '&limit=' . $this->request->get['limit'];
        }

        $pagination = new Pagination();
        $pagination->total = $data['product_total'];
        $pagination->page = $filter_data['page'];
        $pagination->limit = $filter_data['limit'];
        $pagination->url = $this->url->link('product/special', $url . '&page={page}');

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($data['product_total']) ? (($filter_data['page'] - 1) * $filter_data['limit']) + 1 : 0, ((($filter_data['page'] - 1) * $filter_data['limit']) > ($data['product_total'] - $filter_data['limit'])) ? $data['product_total'] : ((($filter_data['page'] - 1) * $filter_data['limit']) + $filter_data['limit']), $data['product_total'], ceil($data['product_total'] / $filter_data['limit']));

        $data['sort'] = $filter_data['sort'];
        $data['order'] = $filter_data['order'];
        $data['limit'] = $filter_data['limit'];

        if (isset($this->request->get['ajax']) && $this->request->get['ajax'] == 1) {
            $this->responseAjax($filter_data);
            return;
        }

        $this->response->setOutput($this->load->view('product/special', $data));
    }

    private function responseAjax($filter_data) {
        $results = $this->model_catalog_product->getProductSpecials($filter_data);
        $json = array();

        foreach ($results as $result) {
            $json['products'][] = $this->load->controller('product/thumb', $this->formatProductData($result));
        }

        $json['endOfData'] = count($results) < $filter_data['limit'];

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    private function getFilterData() {
        $filter = $this->request->get['filter'] ?? '';
        $sort = $this->request->get['sort'] ?? 'p.sort_order';
        $order = $this->request->get['order'] ?? 'ASC';
        $page = (int)($this->request->get['page'] ?? 1);
        $limit = (int)($this->request->get['limit'] ?? $this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit'));

        return array(
            'filter_filter' => $filter,
            'sort' => $sort,
            'order' => $order,
            'start' => ($page - 1) * $limit,
            'page' => $page,
            'limit' => $limit
        );
    }

    private function getSearchData($filter_data) {
        $data['products'] = array();
        $results = $this->model_catalog_product->getProductSpecials($filter_data);

        foreach ($results as $result) {
            $data['products'][] = $this->load->controller('product/thumb', $this->formatProductData($result));
        }

        $data['product_total'] = $this->model_catalog_product->getTotalProductSpecials();

        return $data;
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
                            'customer_group_id'       => $option_value['customer_group_id'],
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
            $price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
        } else {
            $price = false;
        }

        if ((float)$result['special']) {
            $special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
        } else {
            $special = false;
        }

        if ($this->config->get('config_tax')) {
            $tax = $this->currency->format((float)$result['special'] ? $result['special'] : $result['price'], $this->session->data['currency']);
        } else {
            $tax = false;
        }

        $category_infox = $this->model_catalog_product->getProductCategories($result['product_id']);

        $category = '';
        $category_href = '';
        if ($category_infox) {
            $category = $category_infox[0]['name'];
            $category_href = $this->url->link('product/category', 'language=' . $this->config->get('config_language') . '&path=' . $category_infox[0]['category_id']);
        }

        $brand = '';
        $brand_href = '';
        $this->load->model('catalog/manufacturer');
        $manufacturer_info = $this->model_catalog_manufacturer->getManufacturer($result['manufacturer_id']);
        if ($manufacturer_info) {
            $brand = $manufacturer_info['name'];
            $brand_href = $this->url->link('product/manufacturer.info', 'language=' . $this->config->get('config_language') . '&manufacturer_id=' . $result['manufacturer_id']);
        }
        $product_data = [
            'product_id'     => $result['product_id'],
            'thumb'          => $image,
            'options'        => $dataoptions?$dataoptions:[],
            'name'           => $result['name'],
            'quantity'     => $result['quantity'],
            'description'    => utf8_substr(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8')), 0, $this->config->get('config_product_description_length')) . '..',
            'price'          => $price,
            'special'        => $special,
            'tax'            => $tax,
            'minimum'        => $result['minimum'] > 0 ? $result['minimum'] : 1,
            'rating'         => (int)$result['rating'],
            'category'       => $category,
            'category_href'  => $category_href,
            'brand'          => $brand,
            'brand_href'     => $brand_href,
            'href'           => $this->url->link('product/product','product_id=' . $result['product_id'])
        ];
        return $product_data;
    }
}
