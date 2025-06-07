<?php
/**
 * AYM ERP System: Advanced Order Processing Controller
 *
 * شاشة تنفيذ الطلبات المتقدمة للكاشير - مطورة للشركات الحقيقية
 *
 * الميزات المتقدمة:
 * - قارئ باركود متقدم مثل POS
 * - دعم الوحدات المتعددة والتحويل التلقائي
 * - حساب الضرائب التلقائي مع SDK المصري
 * - واجهة سريعة ومحسنة للكاشير
 * - تكامل مع نظام المخزون المتقدم
 * - دعم التسعير المتدرج (retail, wholesale, half_wholesale, custom)
 * - تكامل مع الخيارات والوحدات مثل POS
 * - دعم التقسيط مع فاليو وباي تابس
 * - إرسال الإشعارات التلقائية
 * - تكامل مع ETA للفواتير والإيصالات الإلكترونية
 *
 * @package    AYM ERP
 * @author     AYM Development Team
 * @copyright  2024 AYM ERP Systems
 * @license    Commercial License
 * @version    1.0.0
 * @since      2024-01-15
 */

class ControllerSaleOrderProcessing extends Controller {

    /**
     * الشاشة الرئيسية لتنفيذ الطلبات
     *
     * واجهة متقدمة للكاشير مع:
     * - قارئ باركود فوري
     * - عرض المنتجات مع الوحدات والخيارات
     * - حساب الضرائب التلقائي
     * - معاينة الطلب الفورية
     */
    public function index() {
        $this->load->language('sale/order_processing');
        $this->document->setTitle($this->language->get('heading_title'));

        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('access', 'sale/order_processing')) {
            $this->session->data['error'] = $this->language->get('error_permission');
            $this->response->redirect($this->url->link('sale/order', 'user_token=' . $this->session->data['user_token'], true));
        }

        // تحميل النماذج المطلوبة
        $this->load->model('sale/order_processing');
        $this->load->model('pos/pos'); // استخدام نموذج POS للاستفادة من الميزات المتقدمة
        $this->load->model('inventory/inventory');
        $this->load->model('customer/customer');
        $this->load->model('localisation/tax_class');
        $this->load->model('localisation/currency');

        // إعداد البيانات للعرض
        $data = $this->prepareProcessingData();

        // تحميل القوالب
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('sale/order_processing', $data));
    }

    /**
     * فحص الباركود وإضافة المنتج للطلب
     *
     * مثل POS لكن محسن للطلبات:
     * - فحص الباركود مع الوحدات
     * - حساب السعر حسب نوع التسعير
     * - التحقق من المخزون
     * - حساب الضرائب التلقائي
     */
    public function scanBarcode() {
        $this->load->language('sale/order_processing');
        $this->load->model('sale/order_processing');
        $this->load->model('pos/pos');

        $json = array('success' => false);

        if (isset($this->request->post['barcode']) && isset($this->request->post['pricing_type'])) {
            $barcode = $this->request->post['barcode'];
            $pricing_type = $this->request->post['pricing_type'];
            $customer_id = isset($this->request->post['customer_id']) ? (int)$this->request->post['customer_id'] : 0;
            $branch_id = $this->user->getBranchId();

            // البحث عن المنتج بالباركود (استخدام نموذج POS المتقدم)
            $product_info = $this->model_pos_pos->getProductByBarcode($barcode);

            if ($product_info) {
                // جلب خيارات المنتج
                $product_options = $this->model_pos_pos->getProductOptions($product_info['product_id']);

                // التحقق من المخزون
                $stock_info = $this->model_sale_order_processing->checkStock(
                    $product_info['product_id'],
                    $product_info['unit_id'],
                    1,
                    $branch_id
                );

                if (!$stock_info['available']) {
                    $json['error'] = $this->language->get('error_insufficient_stock');
                    $json['message'] = sprintf($this->language->get('error_stock_quantity'), $stock_info['available_quantity']);
                } elseif (empty($product_options) || isset($product_info['option_value_id'])) {
                    // إضافة المنتج مباشرة إذا لم تكن له خيارات
                    $option = isset($product_info['option_value_id']) ?
                        array($product_info['option_id'] => $product_info['option_value_id']) : array();

                    $result = $this->model_sale_order_processing->addToOrderCart(
                        $product_info['product_id'],
                        1,
                        $option,
                        $product_info['unit_id'],
                        $pricing_type,
                        $customer_id,
                        $branch_id
                    );

                    if ($result) {
                        $json['success'] = true;
                        $json['message'] = $this->language->get('text_product_added');

                        // إرجاع بيانات الطلب المحدثة
                        $json['order_data'] = $this->getOrderCartData();
                    } else {
                        $json['error'] = $this->language->get('error_add_to_order');
                    }
                } else {
                    // المنتج له خيارات - إرجاع الخيارات للاختيار
                    $json['product_id'] = $product_info['product_id'];
                    $json['product_name'] = $product_info['name'];
                    $json['options'] = $product_options;
                    $json['units'] = $this->model_pos_pos->getProductUnits($product_info['product_id']);
                    $json['default_unit_id'] = $product_info['unit_id'];
                    $json['requires_options'] = true;
                    $json['message'] = $this->language->get('text_select_options');
                }
            } else {
                $json['error'] = $this->language->get('error_barcode_not_found');
                $json['message'] = sprintf($this->language->get('error_barcode_not_found_details'), $barcode);
            }
        } else {
            $json['error'] = $this->language->get('error_invalid_input');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * إضافة منتج مع خيارات للطلب
     */
    public function addProductWithOptions() {
        $this->load->language('sale/order_processing');
        $this->load->model('sale/order_processing');

        $json = array('success' => false);

        if (isset($this->request->post['product_id']) && isset($this->request->post['quantity'])) {
            $product_id = (int)$this->request->post['product_id'];
            $quantity = (int)$this->request->post['quantity'];
            $unit_id = isset($this->request->post['unit_id']) ? (int)$this->request->post['unit_id'] : 0;
            $options = isset($this->request->post['option']) ? $this->request->post['option'] : array();
            $pricing_type = isset($this->request->post['pricing_type']) ? $this->request->post['pricing_type'] : 'retail';
            $customer_id = isset($this->request->post['customer_id']) ? (int)$this->request->post['customer_id'] : 0;
            $branch_id = $this->user->getBranchId();

            // التحقق من المخزون
            $stock_info = $this->model_sale_order_processing->checkStock($product_id, $unit_id, $quantity, $branch_id);

            if (!$stock_info['available']) {
                $json['error'] = $this->language->get('error_insufficient_stock');
                $json['message'] = sprintf($this->language->get('error_stock_quantity'), $stock_info['available_quantity']);
            } else {
                $result = $this->model_sale_order_processing->addToOrderCart(
                    $product_id,
                    $quantity,
                    $options,
                    $unit_id,
                    $pricing_type,
                    $customer_id,
                    $branch_id
                );

                if ($result) {
                    $json['success'] = true;
                    $json['message'] = $this->language->get('text_product_added');
                    $json['order_data'] = $this->getOrderCartData();
                } else {
                    $json['error'] = $this->language->get('error_add_to_order');
                }
            }
        } else {
            $json['error'] = $this->language->get('error_required_fields');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * تحديث كمية منتج في الطلب
     */
    public function updateQuantity() {
        $this->load->language('sale/order_processing');
        $this->load->model('sale/order_processing');

        $json = array('success' => false);

        if (isset($this->request->post['cart_key']) && isset($this->request->post['quantity'])) {
            $cart_key = $this->request->post['cart_key'];
            $quantity = (int)$this->request->post['quantity'];

            if ($quantity > 0) {
                $result = $this->model_sale_order_processing->updateOrderCartQuantity($cart_key, $quantity);

                if ($result) {
                    $json['success'] = true;
                    $json['message'] = $this->language->get('text_quantity_updated');
                    $json['order_data'] = $this->getOrderCartData();
                } else {
                    $json['error'] = $this->language->get('error_update_quantity');
                }
            } else {
                // حذف المنتج إذا كانت الكمية صفر
                $result = $this->model_sale_order_processing->removeFromOrderCart($cart_key);

                if ($result) {
                    $json['success'] = true;
                    $json['message'] = $this->language->get('text_product_removed');
                    $json['order_data'] = $this->getOrderCartData();
                } else {
                    $json['error'] = $this->language->get('error_remove_product');
                }
            }
        } else {
            $json['error'] = $this->language->get('error_required_fields');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * إزالة منتج من الطلب
     */
    public function removeProduct() {
        $this->load->language('sale/order_processing');
        $this->load->model('sale/order_processing');

        $json = array('success' => false);

        if (isset($this->request->post['cart_key'])) {
            $cart_key = $this->request->post['cart_key'];

            $result = $this->model_sale_order_processing->removeFromOrderCart($cart_key);

            if ($result) {
                $json['success'] = true;
                $json['message'] = $this->language->get('text_product_removed');
                $json['order_data'] = $this->getOrderCartData();
            } else {
                $json['error'] = $this->language->get('error_remove_product');
            }
        } else {
            $json['error'] = $this->language->get('error_required_fields');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * إنهاء الطلب وإنشاؤه في النظام
     */
    public function completeOrder() {
        $this->load->language('sale/order_processing');
        $this->load->model('sale/order_processing');
        $this->load->model('sale/order');

        $json = array('success' => false);

        if (isset($this->request->post['customer_id']) && isset($this->request->post['payment_method'])) {
            $customer_id = (int)$this->request->post['customer_id'];
            $payment_method = $this->request->post['payment_method'];
            $shipping_method = isset($this->request->post['shipping_method']) ? $this->request->post['shipping_method'] : '';
            $notes = isset($this->request->post['notes']) ? $this->request->post['notes'] : '';
            $installment_plan = isset($this->request->post['installment_plan']) ? $this->request->post['installment_plan'] : null;

            // التحقق من وجود منتجات في الطلب
            $order_cart = $this->getOrderCartData();

            if (empty($order_cart['products'])) {
                $json['error'] = $this->language->get('error_empty_order');
            } else {
                // إنشاء الطلب
                $order_data = $this->model_sale_order_processing->prepareOrderData(
                    $customer_id,
                    $payment_method,
                    $shipping_method,
                    $notes,
                    $installment_plan,
                    $order_cart
                );

                $order_id = $this->model_sale_order->addOrder($order_data);

                if ($order_id) {
                    // مسح سلة الطلب
                    $this->model_sale_order_processing->clearOrderCart();

                    // إرسال للفوترة الإلكترونية إذا مطلوب
                    if ($this->config->get('eta_auto_send')) {
                        $this->sendToETA($order_id);
                    }

                    $json['success'] = true;
                    $json['message'] = $this->language->get('text_order_completed');
                    $json['order_id'] = $order_id;
                    $json['redirect'] = $this->url->link('sale/order/view', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $order_id, true);
                } else {
                    $json['error'] = $this->language->get('error_create_order');
                }
            }
        } else {
            $json['error'] = $this->language->get('error_required_fields');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * إعداد البيانات للعرض
     */
    private function prepareProcessingData() {
        $data = array();

        // معلومات أساسية
        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_scan_barcode'] = $this->language->get('text_scan_barcode');
        $data['text_search_products'] = $this->language->get('text_search_products');
        $data['text_order_cart'] = $this->language->get('text_order_cart');
        $data['text_customer_info'] = $this->language->get('text_customer_info');
        $data['text_payment_info'] = $this->language->get('text_payment_info');

        // أزرار
        $data['button_add_product'] = $this->language->get('button_add_product');
        $data['button_complete_order'] = $this->language->get('button_complete_order');
        $data['button_clear_cart'] = $this->language->get('button_clear_cart');

        // إعدادات التسعير
        $data['pricing_types'] = array(
            'retail' => $this->language->get('text_retail'),
            'wholesale' => $this->language->get('text_wholesale'),
            'half_wholesale' => $this->language->get('text_half_wholesale'),
            'custom' => $this->language->get('text_custom')
        );

        // معلومات المستخدم والفرع
        $data['current_branch'] = $this->model_pos_pos->getCurrentUserBranch();
        $data['user_token'] = $this->session->data['user_token'];

        // طرق الدفع والشحن
        $data['payment_methods'] = $this->model_pos_pos->getPaymentMethods();
        $data['shipping_methods'] = $this->model_pos_pos->getShippingMethods();

        // مجموعات العملاء
        $this->load->model('customer/customer_group');
        $data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();

        // بيانات الطلب الحالي
        $data['order_cart'] = $this->getOrderCartData();

        return $data;
    }

    /**
     * الحصول على بيانات سلة الطلب
     */
    private function getOrderCartData() {
        $this->load->model('sale/order_processing');

        $cart_data = $this->model_sale_order_processing->getOrderCart();

        return array(
            'products' => $cart_data['products'],
            'totals' => $cart_data['totals'],
            'total' => $cart_data['total'],
            'tax_total' => $cart_data['tax_total'],
            'product_count' => count($cart_data['products'])
        );
    }

    /**
     * البحث عن المنتجات
     */
    public function searchProducts() {
        $this->load->language('sale/order_processing');
        $this->load->model('sale/order_processing');
        $this->load->model('pos/pos');

        $json = array();

        if (isset($this->request->get['query'])) {
            $query = $this->request->get['query'];
            $pricing_type = isset($this->request->get['pricing_type']) ? $this->request->get['pricing_type'] : 'retail';

            $products = $this->model_pos_pos->searchProducts($query, $pricing_type);

            $json = $products;
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * الحصول على بيانات السلة الحالية
     */
    public function getOrderCart() {
        $this->load->language('sale/order_processing');
        $this->load->model('sale/order_processing');

        $json = array();
        $json['order_data'] = $this->getOrderCartData();

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * البحث عن العملاء
     */
    public function searchCustomers() {
        $this->load->language('sale/order_processing');
        $this->load->model('customer/customer');

        $json = array();

        if (isset($this->request->get['query'])) {
            $query = $this->request->get['query'];

            $filter_data = array(
                'filter_name' => $query,
                'filter_email' => $query,
                'filter_telephone' => $query,
                'start' => 0,
                'limit' => 10
            );

            $customers = $this->model_customer_customer->getCustomers($filter_data);

            foreach ($customers as $customer) {
                $json[] = array(
                    'customer_id' => $customer['customer_id'],
                    'name' => $customer['firstname'] . ' ' . $customer['lastname'],
                    'email' => $customer['email'],
                    'telephone' => $customer['telephone'],
                    'customer_group' => $customer['customer_group']
                );
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * مسح السلة
     */
    public function clearCart() {
        $this->load->language('sale/order_processing');
        $this->load->model('sale/order_processing');

        $json = array('success' => false);

        $result = $this->model_sale_order_processing->clearOrderCart();

        if ($result) {
            $json['success'] = true;
            $json['message'] = $this->language->get('text_cart_cleared');
            $json['order_data'] = $this->getOrderCartData();
        } else {
            $json['error'] = $this->language->get('error_clear_cart');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * إرسال الطلب لنظام الضرائب المصري
     */
    private function sendToETA($order_id) {
        $this->load->model('extension/eta/invoice');

        try {
            $result = $this->model_extension_eta_invoice->sendOrderToETA($order_id);

            if ($result['success']) {
                // تسجيل نجاح الإرسال
                $this->log->write('ETA: Order #' . $order_id . ' sent successfully');
            } else {
                // إضافة للطابور للإرسال لاحقاً
                $this->model_extension_eta_invoice->addToQueue($order_id, 'order');
                $this->log->write('ETA: Order #' . $order_id . ' added to queue');
            }
        } catch (Exception $e) {
            // إضافة للطابور في حالة الخطأ
            $this->model_extension_eta_invoice->addToQueue($order_id, 'order');
            $this->log->write('ETA Error: ' . $e->getMessage());
        }
    }
}
