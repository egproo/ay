class ControllerCommonSidebarCheckout extends Controller {
    public function index() {
        $this->load->language('common/cart');

        $data['products'] = array();
        $this->load->model('tool/image');
        $this->load->model('catalog/product');

        $products = $this->cart->getProducts();

        foreach ($products as $product) {
            // Get unit name and price
            $unit_name = $this->model_catalog_product->getUnitName($product['unit_id']);
            $unit_price = $this->model_catalog_product->getProductUnitPrice($product['product_id'], $product['unit_id']);
            $formatted_price = $this->currency->format($this->tax->calculate($unit_price, $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
            $formatted_total = $this->currency->format($this->tax->calculate($unit_price * $product['quantity'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);

            $data['products'][] = array(
                'cart_id'   => $product['cart_id'],
                'thumb'     => $this->model_tool_image->resize($product['image'] ? $product['image'] : 'placeholder.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_height')),
                'name'      => $product['name'],
                'quantity'  => $product['quantity'],
                'unit'      => $unit_name,
                'stock'     => $product['stock'] ? true : !$this->config->get('config_stock_checkout') || $this->config->get('config_stock_warning'),
                'price'     => $formatted_price,
                'total'     => $formatted_total,
                'href'      => $this->url->link('product/product', 'product_id=' . $product['product_id'])
            );
        }

        $data['totals'] = array();
        $totals = array();
        $taxes = $this->cart->getTaxes();
        $total = 0;

        // Because __call can not keep var references so we put them into an array. 
        $total_data = array(
            'totals' => &$totals,
            'taxes'  => &$taxes,
            'total'  => &$total
        );

        $this->load->model('setting/extension');

        $sort_order = array();

        $results = $this->model_setting_extension->getExtensions('total');

        foreach ($results as $key => $value) {
            $sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
        }

        array_multisort($sort_order, SORT_ASC, $results);

        foreach ($results as $result) {
            if ($this->config->get('total_' . $result['code'] . '_status')) {
                $this->load->model('extension/total/' . $result['code']);
                $this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
            }
        }

        foreach ($totals as $total) {
            $data['totals'][] = array(
                'title' => $total['title'],
                'text'  => $this->currency->format($total['value'], $this->session->data['currency']),
            );
        }

        $data['checkout'] = $this->url->link('checkout/checkout', '', true);

        $this->response->setOutput($this->load->view('common/sidebar_checkout', $data));
    }
}
