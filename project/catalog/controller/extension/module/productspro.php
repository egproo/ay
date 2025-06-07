<?php
error_reporting(E_ALL);
ini_set('display_errors', 'on');

class ControllerExtensionModuleProductspro extends Controller {

    /**
     * إرجاع بيانات السعر (والسعر الخاص) + الكمية لوحدة معينة بصيغة JSON.
     */
    public function getUnitPrice() {
        $json = array();

        if (isset($this->request->post['product_id']) && isset($this->request->post['unit_id'])) {
            $product_id = (int) $this->request->post['product_id'];
            $unit_id    = (int) $this->request->post['unit_id'];

            $this->load->model('catalog/product');

            $price  = $this->getFormattedPrice($product_id, $unit_id);
            $special = $this->getFormattedSpecialPrice($product_id, $unit_id);
            $quantity_available = $this->model_catalog_product->getAvailableQuantityForOnline($product_id, $unit_id);

            $json = array(
                'price'              => $price,
                'special'            => $special,
                'quantity_available' => $quantity_available
            );
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * صياغة السعر مع الضريبة والعملة.
     */
    protected function getFormattedPrice($product_id, $unit_id) {
        $this->load->model('catalog/product');
        
        // جلب السعر الأساسي
        $base_price = $this->model_catalog_product->getProductUnitPrice($product_id, $unit_id);

        // نفترض أننا سنستخدم الضريبة الافتراضية للمتجر (config_tax_class_id)
        // أو يمكنك جلب tax_class_id للمنتج ذاته
        $price_with_tax = $this->tax->calculate($base_price, $this->config->get('config_tax_class_id'), $this->config->get('config_tax'));

        return $this->currency->format($price_with_tax, $this->session->data['currency']);
    }

    /**
     * صياغة السعر الخاص (special) مع الضريبة والعملة.
     */
    protected function getFormattedSpecialPrice($product_id, $unit_id) {
        $this->load->model('catalog/product');
        
        $special = $this->model_catalog_product->getProductUnitSpecialPrice($product_id, $unit_id);
        if ($special > 0) {
            $special_with_tax = $this->tax->calculate($special, $this->config->get('config_tax_class_id'), $this->config->get('config_tax'));
            return $this->currency->format($special_with_tax, $this->session->data['currency']);
        }
        return false;
    }

    /**
     * الدالة الرئيسية لعرض الموديول.
     */
    public function index($setting) {
        $this->load->language('extension/module/productspro');

        // متغيرات عشوائية كما بالكود الأصلي
        $data['axis']      = $setting['axis'];
        $data['title']     = $setting['title'][$this->config->get('config_language_id')];
        $data['type']      = $setting['type'];
        $data['device']    = $setting['device'];
        $data['module_id'] = $setting['module_id'];

        $modern_types = array('modern1','modern1','modern1','modern1','modern1','modern1','modern1','modern1','modern1','modern1');
        $random_type = $modern_types[array_rand($modern_types)];
        $data['random_type'] = $random_type;

        $data['product_type'] = $setting['product_type'];
        $product_count = !empty($setting['product_count']) ? (int)$setting['product_count'] : 20;

        $this->load->model('catalog/product');
        $this->load->model('tool/image');

        $data['products'] = array();

        // سنجلب المنتجات حسب نوع product_type
        $products = array();
        switch ($setting['product_type']) {
            case 'custom':
                if (!empty($setting['product'])) {
                    foreach ($setting['product'] as $pid) {
                        $p_info = $this->model_catalog_product->getProduct($pid);
                        if ($p_info) {
                            $products[] = $p_info;
                        }
                    }
                }
                break;

            case 'random':
                $products = $this->model_catalog_product->getRandom($product_count);
                break;

            case 'bestseller':
                $products = $this->model_catalog_product->getBestSeller($product_count);
                break;

            case 'specials':
                $products = $this->model_catalog_product->getSpecials($product_count);
                break;

            case 'latest':
                $products = $this->model_catalog_product->getLatest($product_count);
                break;

            case 'bycategories':
                if (!empty($setting['product_category'])) {
                    $products = $this->model_catalog_product->getCategoriesProducts($product_count, $setting['product_category']);
                }
                break;

            case 'bybrands':
                if (!empty($setting['product_manufacturer'])) {
                    $products = $this->model_catalog_product->getBrandsProducts($product_count, $setting['product_manufacturer']);
                }
                break;

            case 'mostviews':
                $products = $this->model_catalog_product->getMostviewsProducts($product_count);
                break;

            case 'bytags':
                if (!empty($setting['product_tag'])) {
                    $products = $this->model_catalog_product->getTagsProducts($product_count, $setting['product_tag']);
                }
                break;

            case 'byfilters':
                if (!empty($setting['product_filter'])) {
                    $products = $this->model_catalog_product->getFiltersProducts($product_count, $setting['product_filter']);
                }
                break;

            case 'byoptions':
                if (!empty($setting['product_option'])) {
                    $products = $this->model_catalog_product->getOptionsProducts($product_count, $setting['product_option']);
                }
                break;

            default:
                $products = array();
                break;
        }

        // نبني بيانات كل منتج (الصورة/السعر/الوحدات/الخ...)
        foreach ($products as $product) {
            $result = $this->buildProductData($product, $setting);
            if ($result) {
                // حسب الديزاين
                if (in_array($setting['type'], array('images','simages','modern'))) {
                    $result['fullproduct'] = $this->load->controller('product/thumb', $result);
                    $data['products'][] = $result;
                } else {
                    // سيتم استخدام دالة thumb() لإخراج شكل الكارت
                    $data['products'][] = $this->load->controller('product/thumb', $result);
                }
            }
        }

        // بعد تجهيز $data['products'] نُرجع عرض الواجهة:
        if ($data['products']) {
            return $this->load->view('module/productspro', $data);
        } else {
            return '';
        }
    }

    /**
     * دالة مساعدة: تبني بيانات المنتج (للتمرير إلى الـTwig أو إلى product/thumb).
     */

protected function buildProductData($product_info, $setting) {
    if (empty($product_info['product_id'])) {
        return null;
    }
    $product_id = (int)$product_info['product_id'];

    // Explicitly load units
    $units = $this->model_catalog_product->getProductUnits($product_id);
    
    // Get proper default unit
    $default_unit = null;
    if (!empty($units)) {
        foreach ($units as $unit) {
            if ($unit['unit_type'] == 'base') {
                $default_unit = $unit;
                break;
            }
        }
        // If no base unit found, use the first one
        if (!$default_unit && count($units) > 0) {
            $default_unit = $units[0];
        }
    }
    
    $default_unit_id = $default_unit ? $default_unit['unit_id'] : 37; // Fallback to default unit ID

    // Image handling
    if ($product_info['image']) {
        $image = $this->model_tool_image->resize(html_entity_decode($product_info['image'], ENT_QUOTES, 'UTF-8'), $setting['width'], $setting['height']);
    } else {
        $image = $this->model_tool_image->resize('placeholder.png', $setting['width'], $setting['height']);
    }

    // Get available quantity for the selected unit
    $quantity_available = $this->model_catalog_product->getAvailableQuantityForOnline($product_id, $default_unit_id);

    // Pricing with the correct unit
    $price = false;
    if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
        $unit_price_raw = $this->model_catalog_product->getProductUnitPrice($product_id, $default_unit_id);
        $price = $this->currency->format($this->tax->calculate($unit_price_raw, $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
    }

    // Special price for the selected unit
    $special = false;
    $special_val = $this->model_catalog_product->getProductUnitSpecialPrice($product_id, $default_unit_id);
    if ($special_val > 0) {
        $special = $this->currency->format($this->tax->calculate($special_val, $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
    }

    // Get options for the selected unit
    $product_options = $this->model_catalog_product->getProductOptionsByUnit($product_id, $default_unit_id);
    $dataoptions = array();
    foreach ($product_options as $option) {
        // Process options...
        // (existing code for option processing)
    }

    // Get quantity discounts
    $product_quantity_discounts = $this->model_catalog_product->getProductQuantityDiscounts($product_id, 1, $default_unit_id);

    // Get bundles
    $bundles = $this->model_catalog_product->getProductBundles($product_id);

    // Build the final product data array
    return array(
        'product_id' => $product_id,
        'name'       => $product_info['name'],
        'description'=> utf8_substr(strip_tags(html_entity_decode($product_info['description'], ENT_QUOTES, 'UTF-8')), 0, $this->config->get('config_product_description_length')) . '..',
        'thumb'      => $image,
        'price'      => $price,
        'special'    => $special,
        'quantity'   => $quantity_available,
        'units'      => $units, // Add all units
        'default_unit' => $default_unit,  // Add default unit info
        'default_unit_id' => $default_unit_id, // Add default unit ID
        'options'    => $dataoptions,
        'product_quantity_discounts' => $product_quantity_discounts,
        'bundles'    => $bundles,
        'minimum'    => $product_info['minimum'] ? $product_info['minimum'] : 1,
        'module_id'  => $setting['module_id'],
        'href'       => $this->url->link('product/product', 'product_id=' . $product_id)
    );
}


}
