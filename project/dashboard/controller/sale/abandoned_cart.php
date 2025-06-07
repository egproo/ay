<?php
class ControllerSaleAbandonedCart extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('sale/abandoned_cart');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('sale/abandoned_cart');

        // Check for permissions
        if (!$this->user->hasKey('sale/abandoned_cart')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }

        // Get customer count - we'll use this for statistics
        $total_customers = $this->model_sale_abandoned_cart->getTotalCustomers();
        $total_abandoned_carts = $this->model_sale_abandoned_cart->getTotalAbandonedCarts();
        $total_value = $this->model_sale_abandoned_cart->getTotalValue();
        $recovery_rate = $this->model_sale_abandoned_cart->getRecoveryRate();
        $average_value = $total_abandoned_carts > 0 ? $total_value / $total_abandoned_carts : 0;

        // Load common styles and scripts
        $this->document->addStyle('view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.css');
        $this->document->addScript('view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js');
        $this->document->addScript('view/javascript/chart.js/chart.min.js');

        // Get the filters from URL
        $filter_customer = isset($this->request->get['filter_customer']) ? $this->request->get['filter_customer'] : '';
        $filter_date_start = isset($this->request->get['filter_date_start']) ? $this->request->get['filter_date_start'] : '';
        $filter_date_end = isset($this->request->get['filter_date_end']) ? $this->request->get['filter_date_end'] : '';
        $filter_status = isset($this->request->get['filter_status']) ? $this->request->get['filter_status'] : '';
        $filter_total_min = isset($this->request->get['filter_total_min']) ? $this->request->get['filter_total_min'] : '';
        $filter_total_max = isset($this->request->get['filter_total_max']) ? $this->request->get['filter_total_max'] : '';
        $filter_email_sent = isset($this->request->get['filter_email_sent']) ? $this->request->get['filter_email_sent'] : '';

        $sort = isset($this->request->get['sort']) ? $this->request->get['sort'] : 'date_created';
        $order = isset($this->request->get['order']) ? $this->request->get['order'] : 'DESC';
        $page = isset($this->request->get['page']) ? $this->request->get['page'] : 1;
        $limit = isset($this->request->get['limit']) ? $this->request->get['limit'] : $this->config->get('config_limit_admin');

        // Prepare URL for pagination
        $url = '';
        
        if (isset($this->request->get['filter_customer'])) {
            $url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_date_start'])) {
            $url .= '&filter_date_start=' . $this->request->get['filter_date_start'];
        }

        if (isset($this->request->get['filter_date_end'])) {
            $url .= '&filter_date_end=' . $this->request->get['filter_date_end'];
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

        if (isset($this->request->get['filter_total_min'])) {
            $url .= '&filter_total_min=' . $this->request->get['filter_total_min'];
        }

        if (isset($this->request->get['filter_total_max'])) {
            $url .= '&filter_total_max=' . $this->request->get['filter_total_max'];
        }

        if (isset($this->request->get['filter_email_sent'])) {
            $url .= '&filter_email_sent=' . $this->request->get['filter_email_sent'];
        }

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

        // Prepare filter data
        $filter_data = array(
            'filter_customer'    => $filter_customer,
            'filter_date_start'  => $filter_date_start,
            'filter_date_end'    => $filter_date_end,
            'filter_status'      => $filter_status,
            'filter_total_min'   => $filter_total_min,
            'filter_total_max'   => $filter_total_max,
            'filter_email_sent'  => $filter_email_sent,
            'sort'               => $sort,
            'order'              => $order,
            'start'              => ($page - 1) * $limit,
            'limit'              => $limit
        );

        // Get abandoned carts data
        $results = $this->model_sale_abandoned_cart->getAbandonedCarts($filter_data);
        $total_carts = $this->model_sale_abandoned_cart->getTotalAbandonedCarts($filter_data);

        // Chart data
        $chart_data = $this->model_sale_abandoned_cart->getAbandonedCartsByDay($filter_data);

        // Prepare data for the template
        $data['abandoned_carts'] = array();

        foreach ($results as $result) {
            $items = $this->model_sale_abandoned_cart->getCartItems($result['cart_id']);
            $item_names = array();
            
            foreach ($items as $item) {
                $item_names[] = $item['name'] . ' x ' . $item['quantity'];
            }

            $data['abandoned_carts'][] = array(
                'cart_id'         => $result['cart_id'],
                'customer_id'     => $result['customer_id'],
                'customer_name'   => $result['customer_id'] ? $result['firstname'] . ' ' . $result['lastname'] : $this->language->get('text_guest'),
                'email'           => $result['email'],
                'telephone'       => $result['telephone'],
                'date_created'    => date($this->language->get('date_format_short'), strtotime($result['date_created'])),
                'last_activity'   => date($this->language->get('date_format_short'), strtotime($result['last_activity'])),
                'items_count'     => $result['items_count'],
                'items'           => implode(', ', $item_names),
                'total_value'     => $this->currency->format($result['total_value'], $this->config->get('config_currency')),
                'status'          => $result['status'],
                'status_text'     => $this->language->get('text_status_' . $result['status']),
                'recovery_email_sent' => $result['recovery_email_sent'],
                'recovery_date'   => $result['recovery_date'] ? date($this->language->get('date_format_short'), strtotime($result['recovery_date'])) : '',
                'order_id'        => $result['order_id'],
                'view'            => $this->url->link('sale/abandoned_cart/view', 'user_token=' . $this->session->data['user_token'] . '&cart_id=' . $result['cart_id'] . $url, true),
                'action'          => $this->url->link('sale/abandoned_cart/action', 'user_token=' . $this->session->data['user_token'] . '&cart_id=' . $result['cart_id'] . $url, true),
                'delete'          => $this->user->hasKey('sale/abandoned_cart/delete') ? $this->url->link('sale/abandoned_cart/delete', 'user_token=' . $this->session->data['user_token'] . '&cart_id=' . $result['cart_id'] . $url, true) : false
            );
        }

        // Template data
        $data['user_token'] = $this->session->data['user_token'];

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        if (isset($this->session->data['error'])) {
            $data['error'] = $this->session->data['error'];
            unset($this->session->data['error']);
        } else {
            $data['error'] = '';
        }

        if (isset($this->request->post['selected'])) {
            $data['selected'] = (array)$this->request->post['selected'];
        } else {
            $data['selected'] = array();
        }

        // Define sort URLs
        $url = '';

        if (isset($this->request->get['filter_customer'])) {
            $url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_date_start'])) {
            $url .= '&filter_date_start=' . $this->request->get['filter_date_start'];
        }

        if (isset($this->request->get['filter_date_end'])) {
            $url .= '&filter_date_end=' . $this->request->get['filter_date_end'];
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

        if (isset($this->request->get['filter_total_min'])) {
            $url .= '&filter_total_min=' . $this->request->get['filter_total_min'];
        }

        if (isset($this->request->get['filter_total_max'])) {
            $url .= '&filter_total_max=' . $this->request->get['filter_total_max'];
        }

        if (isset($this->request->get['filter_email_sent'])) {
            $url .= '&filter_email_sent=' . $this->request->get['filter_email_sent'];
        }

        $data['sort_customer'] = $this->url->link('sale/abandoned_cart', 'user_token=' . $this->session->data['user_token'] . '&sort=customer_id' . '&order=' . ($sort == 'customer_id' && $order == 'ASC' ? 'DESC' : 'ASC') . $url, true);
        $data['sort_date'] = $this->url->link('sale/abandoned_cart', 'user_token=' . $this->session->data['user_token'] . '&sort=date_created' . '&order=' . ($sort == 'date_created' && $order == 'ASC' ? 'DESC' : 'ASC') . $url, true);
        $data['sort_activity'] = $this->url->link('sale/abandoned_cart', 'user_token=' . $this->session->data['user_token'] . '&sort=last_activity' . '&order=' . ($sort == 'last_activity' && $order == 'ASC' ? 'DESC' : 'ASC') . $url, true);
        $data['sort_items'] = $this->url->link('sale/abandoned_cart', 'user_token=' . $this->session->data['user_token'] . '&sort=items_count' . '&order=' . ($sort == 'items_count' && $order == 'ASC' ? 'DESC' : 'ASC') . $url, true);
        $data['sort_total'] = $this->url->link('sale/abandoned_cart', 'user_token=' . $this->session->data['user_token'] . '&sort=total_value' . '&order=' . ($sort == 'total_value' && $order == 'ASC' ? 'DESC' : 'ASC') . $url, true);
        $data['sort_status'] = $this->url->link('sale/abandoned_cart', 'user_token=' . $this->session->data['user_token'] . '&sort=status' . '&order=' . ($sort == 'status' && $order == 'ASC' ? 'DESC' : 'ASC') . $url, true);

        $url = '';

        if (isset($this->request->get['filter_customer'])) {
            $url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_date_start'])) {
            $url .= '&filter_date_start=' . $this->request->get['filter_date_start'];
        }

        if (isset($this->request->get['filter_date_end'])) {
            $url .= '&filter_date_end=' . $this->request->get['filter_date_end'];
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

        if (isset($this->request->get['filter_total_min'])) {
            $url .= '&filter_total_min=' . $this->request->get['filter_total_min'];
        }

        if (isset($this->request->get['filter_total_max'])) {
            $url .= '&filter_total_max=' . $this->request->get['filter_total_max'];
        }

        if (isset($this->request->get['filter_email_sent'])) {
            $url .= '&filter_email_sent=' . $this->request->get['filter_email_sent'];
        }

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        // Pagination
        $pagination = new Pagination();
        $pagination->total = $total_carts;
        $pagination->page = $page;
        $pagination->limit = $limit;
        $pagination->url = $this->url->link('sale/abandoned_cart', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();
        $data['results'] = sprintf($this->language->get('text_pagination'), ($total_carts) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($total_carts - $limit)) ? $total_carts : ((($page - 1) * $limit) + $limit), $total_carts, ceil($total_carts / $limit));

        // Filters
        $data['filter_customer'] = $filter_customer;
        $data['filter_date_start'] = $filter_date_start;
        $data['filter_date_end'] = $filter_date_end;
        $data['filter_status'] = $filter_status;
        $data['filter_total_min'] = $filter_total_min;
        $data['filter_total_max'] = $filter_total_max;
        $data['filter_email_sent'] = $filter_email_sent;

        // Status options
        $data['statuses'] = array(
            '' => $this->language->get('text_all_statuses'),
            'active' => $this->language->get('text_status_active'),
            'recovered' => $this->language->get('text_status_recovered'),
            'expired' => $this->language->get('text_status_expired')
        );

        // Email sent options
        $data['email_sent_options'] = array(
            '' => $this->language->get('text_all'),
            '1' => $this->language->get('text_yes'),
            '0' => $this->language->get('text_no')
        );

        // Statistics
        $data['stats'] = array(
            'total_customers' => $total_customers,
            'total_abandoned_carts' => $total_abandoned_carts,
            'total_value' => $this->currency->format($total_value, $this->config->get('config_currency')),
            'recovery_rate' => number_format($recovery_rate, 2) . '%',
            'average_value' => $this->currency->format($average_value, $this->config->get('config_currency')),
            'chart_data' => json_encode($chart_data)
        );

        // Action buttons permissions
        $data['can_send_email'] = $this->user->hasKey('sale/abandoned_cart/send_email');
        $data['can_send_sms'] = $this->user->hasKey('sale/abandoned_cart/send_sms');
        $data['can_delete'] = $this->user->hasKey('sale/abandoned_cart/delete');
        $data['can_create_coupon'] = $this->user->hasKey('sale/abandoned_cart/create_coupon');

        // Add breadcrumbs
        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('sale/abandoned_cart', 'user_token=' . $this->session->data['user_token'], true)
        );

        // Set the template
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('sale/abandoned_cart', $data));
    }

    public function view() {
        $this->load->language('sale/abandoned_cart');
        $this->load->model('sale/abandoned_cart');
        
        if (isset($this->request->get['cart_id'])) {
            $cart_id = $this->request->get['cart_id'];
        } else {
            $cart_id = 0;
        }
        
        $cart_info = $this->model_sale_abandoned_cart->getAbandonedCart($cart_id);
        
        if ($cart_info) {
            $data['cart'] = $cart_info;
            $data['cart_items'] = $this->model_sale_abandoned_cart->getCartItems($cart_id);
            $data['customer'] = $this->model_sale_abandoned_cart->getCustomerInfo($cart_info['customer_id']);
            $data['recovery_history'] = $this->model_sale_abandoned_cart->getRecoveryHistory($cart_id);
            
            // Cart timeline
            $data['timeline'] = $this->model_sale_abandoned_cart->getCartTimeline($cart_id);
            
            $this->response->setOutput($this->load->view('sale/abandoned_cart_view', $data));
        } else {
            $this->response->setOutput($this->language->get('error_cart_not_found'));
        }
    }

    public function send_email() {
        $this->load->language('sale/abandoned_cart');
        $this->load->model('sale/abandoned_cart');
        
        $json = array();
        
        if (!$this->user->hasKey('sale/abandoned_cart/send_email')) {
            $json['error'] = $this->language->get('error_permission');
        } else if (!isset($this->request->post['cart_id'])) {
            $json['error'] = $this->language->get('error_cart_not_found');
        } else {
            $cart_id = $this->request->post['cart_id'];
            $cart_info = $this->model_sale_abandoned_cart->getAbandonedCart($cart_id);
            
            if (!$cart_info) {
                $json['error'] = $this->language->get('error_cart_not_found');
            } else if (empty($cart_info['email'])) {
                $json['error'] = $this->language->get('error_no_email');
            } else {
                $email_template = isset($this->request->post['email_template']) ? $this->request->post['email_template'] : '';
                $custom_message = isset($this->request->post['custom_message']) ? $this->request->post['custom_message'] : '';
                $include_coupon = isset($this->request->post['include_coupon']) && $this->request->post['include_coupon'] == '1';
                $coupon_code = '';
                
                // Create coupon if needed
                if ($include_coupon) {
                    $coupon_code = $this->createRecoveryCoupon($cart_info);
                }
                
                // Send the email
                $email_sent = $this->model_sale_abandoned_cart->sendRecoveryEmail($cart_info, $email_template, $custom_message, $coupon_code);
                
                if ($email_sent) {
                    // Update cart with email sent info
                    $this->model_sale_abandoned_cart->updateCartEmailSent($cart_id);
                    
                    // Log this activity
                    $this->model_sale_abandoned_cart->addRecoveryLog($cart_id, 'email', array(
                        'template' => $email_template,
                        'coupon' => $coupon_code
                    ));
                    
                    $json['success'] = $this->language->get('text_email_sent');
                } else {
                    $json['error'] = $this->language->get('error_email_not_sent');
                }
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function send_sms() {
        $this->load->language('sale/abandoned_cart');
        $this->load->model('sale/abandoned_cart');
        
        $json = array();
        
        if (!$this->user->hasKey('sale/abandoned_cart/send_sms')) {
            $json['error'] = $this->language->get('error_permission');
        } else if (!isset($this->request->post['cart_id'])) {
            $json['error'] = $this->language->get('error_cart_not_found');
        } else {
            $cart_id = $this->request->post['cart_id'];
            $cart_info = $this->model_sale_abandoned_cart->getAbandonedCart($cart_id);
            
            if (!$cart_info) {
                $json['error'] = $this->language->get('error_cart_not_found');
            } else if (empty($cart_info['telephone'])) {
                $json['error'] = $this->language->get('error_no_telephone');
            } else {
                $sms_template = isset($this->request->post['sms_template']) ? $this->request->post['sms_template'] : '';
                $custom_message = isset($this->request->post['custom_message']) ? $this->request->post['custom_message'] : '';
                $include_coupon = isset($this->request->post['include_coupon']) && $this->request->post['include_coupon'] == '1';
                $coupon_code = '';
                
                // Create coupon if needed
                if ($include_coupon) {
                    $coupon_code = $this->createRecoveryCoupon($cart_info);
                }
                
                // Send the SMS
                $sms_sent = $this->model_sale_abandoned_cart->sendRecoverySMS($cart_info, $sms_template, $custom_message, $coupon_code);
                
                if ($sms_sent) {
                    // Log this activity
                    $this->model_sale_abandoned_cart->addRecoveryLog($cart_id, 'sms', array(
                        'template' => $sms_template,
                        'coupon' => $coupon_code
                    ));
                    
                    $json['success'] = $this->language->get('text_sms_sent');
                } else {
                    $json['error'] = $this->language->get('error_sms_not_sent');
                }
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function create_coupon() {
        $this->load->language('sale/abandoned_cart');
        $this->load->model('sale/abandoned_cart');
        
        $json = array();
        
        if (!$this->user->hasKey('sale/abandoned_cart/create_coupon')) {
            $json['error'] = $this->language->get('error_permission');
        } else if (!isset($this->request->post['cart_id'])) {
            $json['error'] = $this->language->get('error_cart_not_found');
        } else {
            $cart_id = $this->request->post['cart_id'];
            $cart_info = $this->model_sale_abandoned_cart->getAbandonedCart($cart_id);
            
            if (!$cart_info) {
                $json['error'] = $this->language->get('error_cart_not_found');
            } else {
                $coupon_code = $this->createRecoveryCoupon($cart_info);
                
                if ($coupon_code) {
                    // Log this activity
                    $this->model_sale_abandoned_cart->addRecoveryLog($cart_id, 'coupon', array(
                        'code' => $coupon_code
                    ));
                    
                    $json['success'] = sprintf($this->language->get('text_coupon_created'), $coupon_code);
                    $json['coupon_code'] = $coupon_code;
                } else {
                    $json['error'] = $this->language->get('error_coupon_not_created');
                }
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    private function createRecoveryCoupon($cart_info) {
        $this->load->model('marketing/coupon');
        
        // Generate a unique coupon code
        $coupon_code = 'RECOVER' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
        
        // Calculate the expiry date (7 days from now)
        $expiry_date = date('Y-m-d', strtotime('+7 days'));
        
        // Calculate the discount value
        $total_value = $cart_info['total_value'];
        $discount_value = min($total_value * 0.1, 50); // 10% off, max $50
        
        // Create the coupon
        $coupon_data = array(
            'name' => 'Cart Recovery: ' . $coupon_code,
            'code' => $coupon_code,
            'discount' => $discount_value,
            'type' => 'F', // Fixed amount
            'total' => 0, // No minimum order amount
            'logged' => 0, // No login required
            'shipping' => 0, // No free shipping
            'date_start' => date('Y-m-d'),
            'date_end' => $expiry_date,
            'uses_total' => 1, // One-time use
            'uses_customer' => 1, // One use per customer
            'status' => 1 // Active
        );
        
        if (!empty($cart_info['customer_id'])) {
            $coupon_data['customer_id'] = $cart_info['customer_id'];
        }
        
        // Save the coupon
        $this->model_marketing_coupon->addCoupon($coupon_data);
        
        return $coupon_code;
    }

    public function delete() {
        $this->load->language('sale/abandoned_cart');
        $this->load->model('sale/abandoned_cart');
        
        if (!$this->user->hasKey('sale/abandoned_cart/delete')) {
            $this->session->data['error'] = $this->language->get('error_permission');
            $this->response->redirect($this->url->link('sale/abandoned_cart', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        if (isset($this->request->get['cart_id'])) {
            $cart_id = $this->request->get['cart_id'];
            $this->model_sale_abandoned_cart->deleteAbandonedCart($cart_id);
            $this->session->data['success'] = $this->language->get('text_cart_deleted');
        } else if (isset($this->request->post['selected']) && is_array($this->request->post['selected'])) {
            foreach ($this->request->post['selected'] as $cart_id) {
                $this->model_sale_abandoned_cart->deleteAbandonedCart($cart_id);
            }
            $this->session->data['success'] = $this->language->get('text_carts_deleted');
        }
        
        $url = '';
        
        if (isset($this->request->get['filter_customer'])) {
            $url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_date_start'])) {
            $url .= '&filter_date_start=' . $this->request->get['filter_date_start'];
        }

        if (isset($this->request->get['filter_date_end'])) {
            $url .= '&filter_date_end=' . $this->request->get['filter_date_end'];
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

        if (isset($this->request->get['filter_total_min'])) {
$url .= '&filter_total_min=' . $this->request->get['filter_total_min'];
       }

       if (isset($this->request->get['filter_total_max'])) {
           $url .= '&filter_total_max=' . $this->request->get['filter_total_max'];
       }

       if (isset($this->request->get['filter_email_sent'])) {
           $url .= '&filter_email_sent=' . $this->request->get['filter_email_sent'];
       }
       
       if (isset($this->request->get['sort'])) {
           $url .= '&sort=' . $this->request->get['sort'];
       }

       if (isset($this->request->get['order'])) {
           $url .= '&order=' . $this->request->get['order'];
       }

       if (isset($this->request->get['page'])) {
           $url .= '&page=' . $this->request->get['page'];
       }
       
       $this->response->redirect($this->url->link('sale/abandoned_cart', 'user_token=' . $this->session->data['user_token'] . $url, true));
   }

   public function send_whatsapp() {
       $this->load->language('sale/abandoned_cart');
       $this->load->model('sale/abandoned_cart');
       
       $json = array();
       
       if (!$this->user->hasKey('sale/abandoned_cart/send_whatsapp')) {
           $json['error'] = $this->language->get('error_permission');
       } else if (!isset($this->request->post['cart_id'])) {
           $json['error'] = $this->language->get('error_cart_not_found');
       } else {
           $cart_id = $this->request->post['cart_id'];
           $cart_info = $this->model_sale_abandoned_cart->getAbandonedCart($cart_id);
           
           if (!$cart_info) {
               $json['error'] = $this->language->get('error_cart_not_found');
           } else if (empty($cart_info['telephone'])) {
               $json['error'] = $this->language->get('error_no_telephone');
           } else {
               $template = isset($this->request->post['whatsapp_template']) ? $this->request->post['whatsapp_template'] : '';
               $custom_message = isset($this->request->post['custom_message']) ? $this->request->post['custom_message'] : '';
               $include_coupon = isset($this->request->post['include_coupon']) && $this->request->post['include_coupon'] == '1';
               $coupon_code = '';
               
               // Create coupon if needed
               if ($include_coupon) {
                   $coupon_code = $this->createRecoveryCoupon($cart_info);
               }
               
               // Send WhatsApp message
               $whatsapp_sent = $this->model_sale_abandoned_cart->sendRecoveryWhatsApp($cart_info, $template, $custom_message, $coupon_code);
               
               if ($whatsapp_sent) {
                   // Log this activity
                   $this->model_sale_abandoned_cart->addRecoveryLog($cart_id, 'whatsapp', array(
                       'template' => $template,
                       'coupon' => $coupon_code
                   ));
                   
                   $json['success'] = $this->language->get('text_whatsapp_sent');
               } else {
                   $json['error'] = $this->language->get('error_whatsapp_not_sent');
               }
           }
       }
       
       $this->response->addHeader('Content-Type: application/json');
       $this->response->setOutput(json_encode($json));
   }

   public function send_telegram() {
       $this->load->language('sale/abandoned_cart');
       $this->load->model('sale/abandoned_cart');
       
       $json = array();
       
       if (!$this->user->hasKey('sale/abandoned_cart/send_telegram')) {
           $json['error'] = $this->language->get('error_permission');
       } else if (!isset($this->request->post['cart_id'])) {
           $json['error'] = $this->language->get('error_cart_not_found');
       } else {
           $cart_id = $this->request->post['cart_id'];
           $cart_info = $this->model_sale_abandoned_cart->getAbandonedCart($cart_id);
           
           if (!$cart_info) {
               $json['error'] = $this->language->get('error_cart_not_found');
           } else if (empty($cart_info['telephone'])) {
               $json['error'] = $this->language->get('error_no_telephone');
           } else {
               $template = isset($this->request->post['telegram_template']) ? $this->request->post['telegram_template'] : '';
               $custom_message = isset($this->request->post['custom_message']) ? $this->request->post['custom_message'] : '';
               $include_coupon = isset($this->request->post['include_coupon']) && $this->request->post['include_coupon'] == '1';
               $coupon_code = '';
               
               // Create coupon if needed
               if ($include_coupon) {
                   $coupon_code = $this->createRecoveryCoupon($cart_info);
               }
               
               // Send Telegram message
               $telegram_sent = $this->model_sale_abandoned_cart->sendRecoveryTelegram($cart_info, $template, $custom_message, $coupon_code);
               
               if ($telegram_sent) {
                   // Log this activity
                   $this->model_sale_abandoned_cart->addRecoveryLog($cart_id, 'telegram', array(
                       'template' => $template,
                       'coupon' => $coupon_code
                   ));
                   
                   $json['success'] = $this->language->get('text_telegram_sent');
               } else {
                   $json['error'] = $this->language->get('error_telegram_not_sent');
               }
           }
       }
       
       $this->response->addHeader('Content-Type: application/json');
       $this->response->setOutput(json_encode($json));
   }

   public function ajax_search_customer() {
       $this->load->language('sale/abandoned_cart');
       
       $json = array();
       
       if (isset($this->request->get['filter_name']) || isset($this->request->get['filter_email'])) {
           $this->load->model('customer/customer');
           
           if (isset($this->request->get['filter_name'])) {
               $filter_name = $this->request->get['filter_name'];
           } else {
               $filter_name = '';
           }
           
           if (isset($this->request->get['filter_email'])) {
               $filter_email = $this->request->get['filter_email'];
           } else {
               $filter_email = '';
           }
           
           $filter_data = array(
               'filter_name'  => $filter_name,
               'filter_email' => $filter_email,
               'start'        => 0,
               'limit'        => 10
           );
           
           $results = $this->model_customer_customer->getCustomers($filter_data);
           
           foreach ($results as $result) {
               $json[] = array(
                   'customer_id' => $result['customer_id'],
                   'name'        => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8')),
                   'email'       => $result['email']
               );
           }
       }
       
       $this->response->addHeader('Content-Type: application/json');
       $this->response->setOutput(json_encode($json));
   }

   public function templates() {
       $this->load->language('sale/abandoned_cart');
       $this->load->model('sale/abandoned_cart');
       
       $json = array();
       
       $type = isset($this->request->get['type']) ? $this->request->get['type'] : 'email';
       
       $templates = $this->model_sale_abandoned_cart->getTemplates($type);
       
       $json['templates'] = $templates;
       
       $this->response->addHeader('Content-Type: application/json');
       $this->response->setOutput(json_encode($json));
   }
}            