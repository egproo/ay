<?php
class ModelSaleAbandonedCart extends Model {
    public function getAbandonedCarts($data = array()) {
        $sql = "SELECT ac.*, c.firstname, c.lastname, c.email, c.telephone FROM " . DB_PREFIX . "abandoned_cart ac LEFT JOIN " . DB_PREFIX . "customer c ON (ac.customer_id = c.customer_id)";

        $where = array();

        if (!empty($data['filter_customer'])) {
            $where[] = "(c.firstname LIKE '%" . $this->db->escape($data['filter_customer']) . "%' OR c.lastname LIKE '%" . $this->db->escape($data['filter_customer']) . "%' OR c.email LIKE '%" . $this->db->escape($data['filter_customer']) . "%')";
        }

        if (!empty($data['filter_date_start'])) {
            $where[] = "DATE(ac.date_created) >= DATE('" . $this->db->escape($data['filter_date_start']) . "')";
        }

        if (!empty($data['filter_date_end'])) {
            $where[] = "DATE(ac.date_created) <= DATE('" . $this->db->escape($data['filter_date_end']) . "')";
        }

        if (!empty($data['filter_status'])) {
            $where[] = "ac.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        if (isset($data['filter_total_min']) && $data['filter_total_min'] !== '') {
            $where[] = "ac.total_value >= '" . (float)$data['filter_total_min'] . "'";
        }

        if (isset($data['filter_total_max']) && $data['filter_total_max'] !== '') {
            $where[] = "ac.total_value <= '" . (float)$data['filter_total_max'] . "'";
        }

        if (isset($data['filter_email_sent']) && $data['filter_email_sent'] !== '') {
            $where[] = "ac.recovery_email_sent = '" . (int)$data['filter_email_sent'] . "'";
        }

        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sort_data = array(
            'customer_id',
            'date_created',
            'last_activity',
            'items_count',
            'total_value',
            'status'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY date_created";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getTotalAbandonedCarts($data = array()) {
        $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "abandoned_cart ac LEFT JOIN " . DB_PREFIX . "customer c ON (ac.customer_id = c.customer_id)";

        $where = array();

        if (!empty($data['filter_customer'])) {
            $where[] = "(c.firstname LIKE '%" . $this->db->escape($data['filter_customer']) . "%' OR c.lastname LIKE '%" . $this->db->escape($data['filter_customer']) . "%' OR c.email LIKE '%" . $this->db->escape($data['filter_customer']) . "%')";
        }

        if (!empty($data['filter_date_start'])) {
            $where[] = "DATE(ac.date_created) >= DATE('" . $this->db->escape($data['filter_date_start']) . "')";
        }

        if (!empty($data['filter_date_end'])) {
            $where[] = "DATE(ac.date_created) <= DATE('" . $this->db->escape($data['filter_date_end']) . "')";
        }

        if (!empty($data['filter_status'])) {
            $where[] = "ac.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        if (isset($data['filter_total_min']) && $data['filter_total_min'] !== '') {
            $where[] = "ac.total_value >= '" . (float)$data['filter_total_min'] . "'";
        }

        if (isset($data['filter_total_max']) && $data['filter_total_max'] !== '') {
            $where[] = "ac.total_value <= '" . (float)$data['filter_total_max'] . "'";
        }

        if (isset($data['filter_email_sent']) && $data['filter_email_sent'] !== '') {
            $where[] = "ac.recovery_email_sent = '" . (int)$data['filter_email_sent'] . "'";
        }

        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    public function getAbandonedCart($cart_id) {
        $query = $this->db->query("SELECT ac.*, c.firstname, c.lastname, c.email, c.telephone FROM " . DB_PREFIX . "abandoned_cart ac LEFT JOIN " . DB_PREFIX . "customer c ON (ac.customer_id = c.customer_id) WHERE ac.cart_id = '" . (int)$cart_id . "'");

        return $query->row;
    }

    public function getCartItems($cart_id) {
        // First try to get from cart serialized data
        $cart_query = $this->db->query("SELECT cart FROM " . DB_PREFIX . "customer WHERE customer_id = (SELECT customer_id FROM " . DB_PREFIX . "abandoned_cart WHERE cart_id = '" . (int)$cart_id . "' AND customer_id > 0)");
        
        if ($cart_query->num_rows && !empty($cart_query->row['cart'])) {
            $cart_data = unserialize($cart_query->row['cart']);
            
            if ($cart_data) {
                $items = array();
                
                foreach ($cart_data as $key => $quantity) {
                    // Parse the cart key to get product_id
                    $key_data = explode(':', $key);
                    $product_id = $key_data[0];
                    
                    // Get product info
                    $product_query = $this->db->query("SELECT p.product_id, pd.name, p.model, p.price, p.tax_class_id FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) WHERE p.product_id = '" . (int)$product_id . "' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
                    
                    if ($product_query->num_rows) {
                        $options = array();
                        
                        // If there are options
                        if (isset($key_data[1])) {
                            $options_data = json_decode(base64_decode($key_data[1]), true);
                            
                            foreach ($options_data as $option_id => $option_value) {
                                $option_query = $this->db->query("SELECT od.name AS option_name, ovd.name AS value_name FROM " . DB_PREFIX . "option_description od LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (od.option_id = ovd.option_id) WHERE od.option_id = '" . (int)$option_id . "' AND ovd.option_value_id = '" . (int)$option_value . "' AND od.language_id = '" . (int)$this->config->get('config_language_id') . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
                                
                                if ($option_query->num_rows) {
                                    $options[] = $option_query->row['option_name'] . ': ' . $option_query->row['value_name'];
                                }
                            }
                        }
                        
                        $items[] = array(
                            'product_id' => $product_query->row['product_id'],
                            'name'       => $product_query->row['name'],
                            'model'      => $product_query->row['model'],
                            'options'    => $options,
                            'quantity'   => $quantity,
                            'price'      => $product_query->row['price'],
                            'total'      => $quantity * $product_query->row['price']
                        );
                    }
                }
                
                return $items;
            }
        }
        
        // Fallback - try to get from session data
        $session_query = $this->db->query("SELECT session_id FROM " . DB_PREFIX . "abandoned_cart WHERE cart_id = '" . (int)$cart_id . "'");
        
        if ($session_query->num_rows) {
            $session_id = $session_query->row['session_id'];
            
            $session_data_query = $this->db->query("SELECT data FROM " . DB_PREFIX . "session WHERE session_id = '" . $this->db->escape($session_id) . "'");
            
            if ($session_data_query->num_rows) {
                $session_data = json_decode($session_data_query->row['data'], true);
                
                if (isset($session_data['cart']) && is_array($session_data['cart'])) {
                    $items = array();
                    
                    foreach ($session_data['cart'] as $key => $quantity) {
                        // Parse the cart key to get product_id
                        $key_data = explode(':', $key);
                        $product_id = $key_data[0];
                        
                        // Get product info
                        $product_query = $this->db->query("SELECT p.product_id, pd.name, p.model, p.price, p.tax_class_id FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) WHERE p.product_id = '" . (int)$product_id . "' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
                        
                        if ($product_query->num_rows) {
                            $options = array();
                            
                            // If there are options
                            if (isset($key_data[1])) {
                                $options_data = json_decode(base64_decode($key_data[1]), true);
                                
                                foreach ($options_data as $option_id => $option_value) {
                                    $option_query = $this->db->query("SELECT od.name AS option_name, ovd.name AS value_name FROM " . DB_PREFIX . "option_description od LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (od.option_id = ovd.option_id) WHERE od.option_id = '" . (int)$option_id . "' AND ovd.option_value_id = '" . (int)$option_value . "' AND od.language_id = '" . (int)$this->config->get('config_language_id') . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
                                    
                                    if ($option_query->num_rows) {
                                        $options[] = $option_query->row['option_name'] . ': ' . $option_query->row['value_name'];
                                    }
                                }
                            }
                            
                            $items[] = array(
                                'product_id' => $product_query->row['product_id'],
                                'name'       => $product_query->row['name'],
                                'model'      => $product_query->row['model'],
                                'options'    => $options,
                                'quantity'   => $quantity,
                                'price'      => $product_query->row['price'],
                                'total'      => $quantity * $product_query->row['price']
                            );
                        }
                    }
                    
                    return $items;
                }
            }
        }
        
        // If we still couldn't get the items, return an empty array
        return array();
    }

    public function getCustomerInfo($customer_id) {
        if (!$customer_id) {
            return array();
        }
        
        $query = $this->db->query("SELECT c.*, cgd.name AS customer_group FROM " . DB_PREFIX . "customer c LEFT JOIN " . DB_PREFIX . "customer_group_description cgd ON (c.customer_group_id = cgd.customer_group_id) WHERE c.customer_id = '" . (int)$customer_id . "' AND cgd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
        
        if ($query->num_rows) {
            $customer_info = $query->row;
            
            // Get customer addresses
            $address_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "address WHERE customer_id = '" . (int)$customer_id . "'");
            
            $customer_info['addresses'] = $address_query->rows;
            
            // Get customer orders
            $orders_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` WHERE customer_id = '" . (int)$customer_id . "' ORDER BY date_added DESC LIMIT 10");
            
            $customer_info['orders'] = $orders_query->rows;
            
            return $customer_info;
        }
        
        return array();
    }

    public function getTotalCustomers() {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "customer");
        
        return $query->row['total'];
    }

    public function getTotalValue() {
        $query = $this->db->query("SELECT SUM(total_value) AS total FROM " . DB_PREFIX . "abandoned_cart");
        
        return $query->row['total'] ? $query->row['total'] : 0;
    }

    public function getRecoveryRate() {
        $query = $this->db->query("SELECT COUNT(*) AS total, 
            (SELECT COUNT(*) FROM " . DB_PREFIX . "abandoned_cart WHERE order_id > 0) AS recovered 
            FROM " . DB_PREFIX . "abandoned_cart");
        
        if ($query->row['total'] > 0) {
            return ($query->row['recovered'] / $query->row['total']) * 100;
        }
        
        return 0;
    }

    public function getAbandonedCartsByDay($data = array()) {
        $sql = "SELECT DATE(date_created) AS day, COUNT(*) AS total, SUM(total_value) AS value FROM " . DB_PREFIX . "abandoned_cart";
        
        $where = array();
        
        if (!empty($data['filter_date_start'])) {
            $where[] = "DATE(date_created) >= DATE('" . $this->db->escape($data['filter_date_start']) . "')";
        } else {
            $where[] = "DATE(date_created) >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)";
        }
        
        if (!empty($data['filter_date_end'])) {
            $where[] = "DATE(date_created) <= DATE('" . $this->db->escape($data['filter_date_end']) . "')";
        }
        
        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        
        $sql .= " GROUP BY day ORDER BY day";
        
        $query = $this->db->query($sql);
        
        $results = array();
        
        foreach ($query->rows as $row) {
            $results[] = array(
                'day'   => $row['day'],
                'total' => $row['total'],
                'value' => $row['value']
            );
        }
        
        return $results;
    }

    public function updateCartEmailSent($cart_id) {
        $this->db->query("UPDATE " . DB_PREFIX . "abandoned_cart SET 
            recovery_email_sent = 1, 
            email_sent_date = NOW() 
            WHERE cart_id = '" . (int)$cart_id . "'");
    }

    public function addRecoveryLog($cart_id, $type, $data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "abandoned_cart_recovery SET 
            cart_id = '" . (int)$cart_id . "', 
            user_id = '" . (int)$this->user->getId() . "', 
            type = '" . $this->db->escape($type) . "', 
            data = '" . $this->db->escape(json_encode($data)) . "', 
            date_added = NOW()");
    }

    public function getRecoveryHistory($cart_id) {
        $query = $this->db->query("SELECT r.*, u.username FROM " . DB_PREFIX . "abandoned_cart_recovery r 
            LEFT JOIN " . DB_PREFIX . "user u ON (r.user_id = u.user_id) 
            WHERE r.cart_id = '" . (int)$cart_id . "' 
            ORDER BY r.date_added DESC");
        
        return $query->rows;
    }

    public function getCartTimeline($cart_id) {
        $cart_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "abandoned_cart WHERE cart_id = '" . (int)$cart_id . "'");
        
        if (!$cart_query->num_rows) {
            return array();
        }
        
        $cart = $cart_query->row;
        
        $timeline = array();
        
        // Creation event
        $timeline[] = array(
            'time'    => $cart['date_created'],
            'type'    => 'created',
            'icon'    => 'fa-shopping-cart',
            'color'   => 'success',
            'title'   => 'Cart Created',
            'message' => 'Customer started shopping'
        );
        
        // Last activity
        $timeline[] = array(
            'time'    => $cart['last_activity'],
            'type'    => 'activity',
            'icon'    => 'fa-clock-o',
            'color'   => 'info',
            'title'   => 'Last Activity',
            'message' => 'Customer last interacted with the cart'
        );
        
        // Recovery events
        $recovery_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "abandoned_cart_recovery WHERE cart_id = '" . (int)$cart_id . "' ORDER BY date_added ASC");
        
        foreach ($recovery_query->rows as $recovery) {
            $type_icons = array(
                'email'    => 'fa-envelope',
                'sms'      => 'fa-mobile',
                'whatsapp' => 'fa-whatsapp',
                'telegram' => 'fa-telegram',
                'coupon'   => 'fa-gift'
            );
            
            $type_titles = array(
                'email'    => 'Email Sent',
                'sms'      => 'SMS Sent',
                'whatsapp' => 'WhatsApp Message Sent',
                'telegram' => 'Telegram Message Sent',
                'coupon'   => 'Coupon Created'
            );
            
            $data = json_decode($recovery['data'], true);
            $message = '';
            
            if ($recovery['type'] == 'email') {
                $message = 'Recovery email sent' . (!empty($data['template']) ? ' using template: ' . $data['template'] : '');
            } elseif ($recovery['type'] == 'sms') {
                $message = 'Recovery SMS sent' . (!empty($data['template']) ? ' using template: ' . $data['template'] : '');
            } elseif ($recovery['type'] == 'whatsapp') {
                $message = 'Recovery WhatsApp message sent' . (!empty($data['template']) ? ' using template: ' . $data['template'] : '');
            } elseif ($recovery['type'] == 'telegram') {
                $message = 'Recovery Telegram message sent' . (!empty($data['template']) ? ' using template: ' . $data['template'] : '');
            } elseif ($recovery['type'] == 'coupon') {
                $message = 'Coupon created: ' . (!empty($data['code']) ? $data['code'] : '');
            }
            
            if (!empty($data['coupon']) && $recovery['type'] != 'coupon') {
                $message .= ' with coupon: ' . $data['coupon'];
            }
            
            $timeline[] = array(
                'time'    => $recovery['date_added'],
                'type'    => $recovery['type'],
                'icon'    => isset($type_icons[$recovery['type']]) ? $type_icons[$recovery['type']] : 'fa-exclamation',
                'color'   => 'warning',
                'title'   => isset($type_titles[$recovery['type']]) ? $type_titles[$recovery['type']] : 'Recovery Action',
                'message' => $message
            );
        }
        
        // Recovery date
        if (!empty($cart['recovery_date'])) {
            $timeline[] = array(
                'time'    => $cart['recovery_date'],
                'type'    => 'recovered',
                'icon'    => 'fa-check-circle',
                'color'   => 'success',
                'title'   => 'Cart Recovered',
                'message' => 'Customer completed order #' . $cart['order_id']
            );
        }
        
        // Sort by time
        usort($timeline, function($a, $b) {
            return strtotime($a['time']) - strtotime($b['time']);
        });
        
        return $timeline;
    }

    public function deleteAbandonedCart($cart_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "abandoned_cart WHERE cart_id = '" . (int)$cart_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "abandoned_cart_recovery WHERE cart_id = '" . (int)$cart_id . "'");
    }

public function sendRecoveryEmail($cart_info, $template, $custom_message, $coupon_code = '') {
       // Load the mail model
       $this->load->model('tool/mail');
       
       // Customer name
       $customer_name = !empty($cart_info['firstname']) ? $cart_info['firstname'] : 'Customer';
       
       // Get the cart items for the email
       $cart_items = $this->getCartItems($cart_info['cart_id']);
       $items_html = '';
       
       foreach ($cart_items as $item) {
           $price = $this->currency->format($item['price'], $this->config->get('config_currency'));
           $total = $this->currency->format($item['total'], $this->config->get('config_currency'));
           
           $items_html .= '<tr>';
           $items_html .= '<td style="padding: 10px; border-bottom: 1px solid #eee;">' . $item['name'] . '</td>';
           $items_html .= '<td style="padding: 10px; border-bottom: 1px solid #eee;">' . $item['quantity'] . '</td>';
           $items_html .= '<td style="padding: 10px; border-bottom: 1px solid #eee; text-align: right;">' . $price . '</td>';
           $items_html .= '<td style="padding: 10px; border-bottom: 1px solid #eee; text-align: right;">' . $total . '</td>';
           $items_html .= '</tr>';
       }
       
       // Store info
       $store_name = $this->config->get('config_name');
       $store_url = $this->config->get('config_url');
       
       // Cart recovery URL
       $recovery_url = $store_url . 'index.php?route=checkout/cart&recover=' . $cart_info['session_id'];
       
       // Choose template or use default
       $email_subject = '';
       $email_content = '';
       
       if ($template && $template != 'custom') {
           // Load the selected template
           $template_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "abandoned_cart_template WHERE template_id = '" . (int)$template . "' AND type = 'email'");
           
           if ($template_query->num_rows) {
               $email_subject = $template_query->row['subject'];
               $email_content = $template_query->row['content'];
           }
       } else if ($template == 'custom' && !empty($custom_message)) {
           // Use custom message
           $email_subject = 'Complete Your Order - ' . $store_name;
           $email_content = $custom_message;
       } else {
           // Use default template
           $email_subject = 'Your Cart is Waiting - ' . $store_name;
           $email_content = '<p>Hello ' . $customer_name . ',</p>';
           $email_content .= '<p>We noticed you left some items in your shopping cart. Would you like to complete your purchase?</p>';
           $email_content .= '<div style="margin: 20px 0;">';
           $email_content .= '<table style="width: 100%; border-collapse: collapse;">';
           $email_content .= '<thead><tr style="background-color: #f8f8f8;">';
           $email_content .= '<th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Product</th>';
           $email_content .= '<th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Quantity</th>';
           $email_content .= '<th style="padding: 10px; text-align: right; border-bottom: 2px solid #ddd;">Price</th>';
           $email_content .= '<th style="padding: 10px; text-align: right; border-bottom: 2px solid #ddd;">Total</th>';
           $email_content .= '</tr></thead>';
           $email_content .= '<tbody>' . $items_html . '</tbody>';
           $email_content .= '<tfoot><tr>';
           $email_content .= '<td colspan="3" style="padding: 10px; text-align: right; font-weight: bold;">Total:</td>';
           $email_content .= '<td style="padding: 10px; text-align: right; font-weight: bold;">' . $this->currency->format($cart_info['total_value'], $this->config->get('config_currency')) . '</td>';
           $email_content .= '</tr></tfoot>';
           $email_content .= '</table>';
           $email_content .= '</div>';
           $email_content .= '<p>Click the button below to return to your cart and complete your order:</p>';
           $email_content .= '<div style="text-align: center; margin: 30px 0;">';
           $email_content .= '<a href="' . $recovery_url . '" style="background-color: #4CAF50; color: white; padding: 12px 20px; text-decoration: none; border-radius: 4px; font-weight: bold;">Complete My Order</a>';
           $email_content .= '</div>';
           
           if ($coupon_code) {
               $email_content .= '<p>As a special offer, we\'re giving you a discount coupon to use with your purchase:</p>';
               $email_content .= '<div style="background-color: #f8f8f8; padding: 15px; text-align: center; margin: 20px 0; border: 1px dashed #ccc;">';
               $email_content .= '<p style="font-size: 18px; margin: 0; font-weight: bold;">Use code: <span style="color: #e74c3c;">' . $coupon_code . '</span></p>';
               $email_content .= '</div>';
           }
           
           $email_content .= '<p>If you need any assistance, please don\'t hesitate to contact us.</p>';
           $email_content .= '<p>Thank you,<br>' . $store_name . ' Team</p>';
       }
       
       // Replace placeholders
       $replacements = array(
           '{customer_name}' => $customer_name,
           '{store_name}' => $store_name,
           '{store_url}' => $store_url,
           '{recovery_url}' => $recovery_url,
           '{coupon_code}' => $coupon_code,
           '{cart_items}' => $items_html,
           '{cart_total}' => $this->currency->format($cart_info['total_value'], $this->config->get('config_currency')),
           '{items_count}' => $cart_info['items_count']
       );
       
       foreach ($replacements as $placeholder => $replacement) {
           $email_subject = str_replace($placeholder, $replacement, $email_subject);
           $email_content = str_replace($placeholder, $replacement, $email_content);
       }
       
       // Prepare the email data
       $mail = new Mail($this->config->get('config_mail_engine'));
       $mail->parameter = $this->config->get('config_mail_parameter');
       $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
       $mail->smtp_username = $this->config->get('config_mail_smtp_username');
       $mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
       $mail->smtp_port = $this->config->get('config_mail_smtp_port');
       $mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

       $mail->setTo($cart_info['email']);
       $mail->setFrom($this->config->get('config_email'));
       $mail->setSender($store_name);
       $mail->setSubject($email_subject);
       $mail->setHtml($email_content);
       
       return $mail->send();
   }

   public function sendRecoverySMS($cart_info, $template, $custom_message, $coupon_code = '') {
       // Load SMS model or service
       $this->load->model('tool/sms');
       
       // Customer name
       $customer_name = !empty($cart_info['firstname']) ? $cart_info['firstname'] : 'Customer';
       
       // Store info
       $store_name = $this->config->get('config_name');
       $store_url = $this->config->get('config_url');
       
       // Cart recovery URL
       $recovery_url = $store_url . 'index.php?route=checkout/cart&recover=' . $cart_info['session_id'];
       
       // Shorten the URL if needed
       $short_url = $this->shortenUrl($recovery_url);
       
       // Choose template or use default message
       $sms_content = '';
       
       if ($template && $template != 'custom') {
           // Load the selected template
           $template_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "abandoned_cart_template WHERE template_id = '" . (int)$template . "' AND type = 'sms'");
           
           if ($template_query->num_rows) {
               $sms_content = $template_query->row['content'];
           }
       } else if ($template == 'custom' && !empty($custom_message)) {
           // Use custom message
           $sms_content = $custom_message;
       } else {
           // Use default template
           $sms_content = 'Hello ' . $customer_name . ', you left items in your ' . $store_name . ' cart. ';
           $sms_content .= 'Complete your order here: ' . $short_url;
           
           if ($coupon_code) {
               $sms_content .= ' Use code: ' . $coupon_code . ' for a special discount!';
           }
       }
       
       // Replace placeholders
       $replacements = array(
           '{customer_name}' => $customer_name,
           '{store_name}' => $store_name,
           '{recovery_url}' => $short_url,
           '{coupon_code}' => $coupon_code,
           '{cart_total}' => $this->currency->format($cart_info['total_value'], $this->config->get('config_currency')),
           '{items_count}' => $cart_info['items_count']
       );
       
       foreach ($replacements as $placeholder => $replacement) {
           $sms_content = str_replace($placeholder, $replacement, $sms_content);
       }
       
       // Send the SMS
       return $this->model_tool_sms->send($cart_info['telephone'], $sms_content);
   }

   public function sendRecoveryWhatsApp($cart_info, $template, $custom_message, $coupon_code = '') {
       // Check if WhatsApp integration is enabled
       if (!$this->config->get('module_abandoned_cart_whatsapp_status')) {
           return false;
       }
       
       // Customer name
       $customer_name = !empty($cart_info['firstname']) ? $cart_info['firstname'] : 'Customer';
       
       // Store info
       $store_name = $this->config->get('config_name');
       $store_url = $this->config->get('config_url');
       
       // Cart recovery URL
       $recovery_url = $store_url . 'index.php?route=checkout/cart&recover=' . $cart_info['session_id'];
       
       // Shorten the URL
       $short_url = $this->shortenUrl($recovery_url);
       
       // Choose template or use default message
       $whatsapp_content = '';
       
       if ($template && $template != 'custom') {
           // Load the selected template
           $template_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "abandoned_cart_template WHERE template_id = '" . (int)$template . "' AND type = 'whatsapp'");
           
           if ($template_query->num_rows) {
               $whatsapp_content = $template_query->row['content'];
           }
       } else if ($template == 'custom' && !empty($custom_message)) {
           // Use custom message
           $whatsapp_content = $custom_message;
       } else {
           // Use default template
           $whatsapp_content = 'Hello ' . $customer_name . '! 👋';
           $whatsapp_content .= "\n\n";
           $whatsapp_content .= 'We noticed you have items in your cart at ' . $store_name . '. Would you like to complete your purchase?';
           $whatsapp_content .= "\n\n";
           $whatsapp_content .= 'You can return to your cart here: ' . $short_url;
           
           if ($coupon_code) {
               $whatsapp_content .= "\n\n";
               $whatsapp_content .= '🎁 Special offer: Use code *' . $coupon_code . '* for a discount on your purchase!';
           }
           
           $whatsapp_content .= "\n\n";
           $whatsapp_content .= 'If you need any assistance, please let us know.';
           $whatsapp_content .= "\n\n";
           $whatsapp_content .= 'Thank you,';
           $whatsapp_content .= "\n";
           $whatsapp_content .= $store_name . ' Team';
       }
       
       // Replace placeholders
       $replacements = array(
           '{customer_name}' => $customer_name,
           '{store_name}' => $store_name,
           '{recovery_url}' => $short_url,
           '{coupon_code}' => $coupon_code,
           '{cart_total}' => $this->currency->format($cart_info['total_value'], $this->config->get('config_currency')),
           '{items_count}' => $cart_info['items_count']
       );
       
       foreach ($replacements as $placeholder => $replacement) {
           $whatsapp_content = str_replace($placeholder, $replacement, $whatsapp_content);
       }
       
       // Get the WhatsApp API settings
       $whatsapp_api_url = $this->config->get('module_abandoned_cart_whatsapp_api_url');
       $whatsapp_api_key = $this->config->get('module_abandoned_cart_whatsapp_api_key');
       
       // Clean and format the phone number
       $phone = preg_replace('/[^0-9]/', '', $cart_info['telephone']);
       
       // Create the API request
       $data = array(
           'phone' => $phone,
           'message' => $whatsapp_content
       );
       
       // Send the WhatsApp message using cURL
       $curl = curl_init();
       
       curl_setopt_array($curl, array(
           CURLOPT_URL => $whatsapp_api_url,
           CURLOPT_RETURNTRANSFER => true,
           CURLOPT_ENCODING => '',
           CURLOPT_MAXREDIRS => 10,
           CURLOPT_TIMEOUT => 30,
           CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
           CURLOPT_CUSTOMREQUEST => 'POST',
           CURLOPT_POSTFIELDS => json_encode($data),
           CURLOPT_HTTPHEADER => array(
               'Content-Type: application/json',
               'Authorization: Bearer ' . $whatsapp_api_key
           ),
       ));
       
       $response = curl_exec($curl);
       $err = curl_error($curl);
       
       curl_close($curl);
       
       if ($err) {
           return false;
       }
       
       $result = json_decode($response, true);
       
       // Check if the message was sent successfully
       if (isset($result['success']) && $result['success']) {
           return true;
       }
       
       return false;
   }

   public function sendRecoveryTelegram($cart_info, $template, $custom_message, $coupon_code = '') {
       // Check if Telegram integration is enabled
       if (!$this->config->get('module_abandoned_cart_telegram_status')) {
           return false;
       }
       
       // Customer name
       $customer_name = !empty($cart_info['firstname']) ? $cart_info['firstname'] : 'Customer';
       
       // Store info
       $store_name = $this->config->get('config_name');
       $store_url = $this->config->get('config_url');
       
       // Cart recovery URL
       $recovery_url = $store_url . 'index.php?route=checkout/cart&recover=' . $cart_info['session_id'];
       
       // Shorten the URL
       $short_url = $this->shortenUrl($recovery_url);
       
       // Choose template or use default message
       $telegram_content = '';
       
       if ($template && $template != 'custom') {
           // Load the selected template
           $template_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "abandoned_cart_template WHERE template_id = '" . (int)$template . "' AND type = 'telegram'");
           
           if ($template_query->num_rows) {
               $telegram_content = $template_query->row['content'];
           }
       } else if ($template == 'custom' && !empty($custom_message)) {
           // Use custom message
           $telegram_content = $custom_message;
       } else {
           // Use default template
           $telegram_content = 'Hello ' . $customer_name . '! 👋';
           $telegram_content .= "\n\n";
           $telegram_content .= 'We noticed you have items in your cart at ' . $store_name . '. Would you like to complete your purchase?';
           $telegram_content .= "\n\n";
           $telegram_content .= 'You can return to your cart here: ' . $short_url;
           
           if ($coupon_code) {
               $telegram_content .= "\n\n";
               $telegram_content .= '🎁 Special offer: Use code *' . $coupon_code . '* for a discount on your purchase!';
           }
           
           $telegram_content .= "\n\n";
           $telegram_content .= 'If you need any assistance, please let us know.';
           $telegram_content .= "\n\n";
           $telegram_content .= 'Thank you,';
           $telegram_content .= "\n";
           $telegram_content .= $store_name . ' Team';
       }
       
       // Replace placeholders
       $replacements = array(
           '{customer_name}' => $customer_name,
           '{store_name}' => $store_name,
           '{recovery_url}' => $short_url,
           '{coupon_code}' => $coupon_code,
           '{cart_total}' => $this->currency->format($cart_info['total_value'], $this->config->get('config_currency')),
           '{items_count}' => $cart_info['items_count']
       );
       
       foreach ($replacements as $placeholder => $replacement) {
           $telegram_content = str_replace($placeholder, $replacement, $telegram_content);
       }
       
       // Get the Telegram API settings
       $telegram_bot_token = $this->config->get('module_abandoned_cart_telegram_bot_token');
       
       // We need to find the Telegram chat ID for this customer
       $chat_id = $this->getTelegramChatId($cart_info['customer_id'], $cart_info['telephone']);
       
       if (!$chat_id) {
           return false;
       }
       
       // Create the API request
       $api_url = 'https://api.telegram.org/bot' . $telegram_bot_token . '/sendMessage';
       
       $data = array(
           'chat_id' => $chat_id,
           'text' => $telegram_content,
           'parse_mode' => 'Markdown'
       );
       
       // Send the Telegram message using cURL
       $curl = curl_init();
       
       curl_setopt_array($curl, array(
           CURLOPT_URL => $api_url,
           CURLOPT_RETURNTRANSFER => true,
           CURLOPT_ENCODING => '',
           CURLOPT_MAXREDIRS => 10,
           CURLOPT_TIMEOUT => 30,
           CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
           CURLOPT_CUSTOMREQUEST => 'POST',
           CURLOPT_POSTFIELDS => json_encode($data),
           CURLOPT_HTTPHEADER => array(
               'Content-Type: application/json'
           ),
       ));
       
       $response = curl_exec($curl);
       $err = curl_error($curl);
       
       curl_close($curl);
       
       if ($err) {
           return false;
       }
       
       $result = json_decode($response, true);
       
       // Check if the message was sent successfully
       if (isset($result['ok']) && $result['ok']) {
           return true;
       }
       
       return false;
   }

   private function getTelegramChatId($customer_id, $telephone) {
       // Check if we have a stored chat ID for this customer
       if ($customer_id) {
           $query = $this->db->query("SELECT telegram_chat_id FROM " . DB_PREFIX . "customer WHERE customer_id = '" . (int)$customer_id . "' AND telegram_chat_id != ''");
           
           if ($query->num_rows && !empty($query->row['telegram_chat_id'])) {
               return $query->row['telegram_chat_id'];
           }
       }
       
       // If not found by customer ID, try to find by telephone
       if ($telephone) {
           $query = $this->db->query("SELECT telegram_chat_id FROM " . DB_PREFIX . "customer WHERE telephone = '" . $this->db->escape($telephone) . "' AND telegram_chat_id != ''");
           
           if ($query->num_rows && !empty($query->row['telegram_chat_id'])) {
               return $query->row['telegram_chat_id'];
           }
       }
       
       return false;
   }

   private function shortenUrl($url) {
       // Check if URL shortening is enabled
       if (!$this->config->get('module_abandoned_cart_url_shortener_status')) {
           return $url;
       }
       
       // Get the URL shortener API settings
       $api_url = $this->config->get('module_abandoned_cart_url_shortener_api');
       $api_key = $this->config->get('module_abandoned_cart_url_shortener_key');
       
       if (empty($api_url) || empty($api_key)) {
           return $url;
       }
       
       // Try to shorten the URL using the configured service
       $curl = curl_init();
       
       curl_setopt_array($curl, array(
           CURLOPT_URL => $api_url,
           CURLOPT_RETURNTRANSFER => true,
           CURLOPT_ENCODING => '',
           CURLOPT_MAXREDIRS => 10,
           CURLOPT_TIMEOUT => 30,
           CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
           CURLOPT_CUSTOMREQUEST => 'POST',
           CURLOPT_POSTFIELDS => json_encode(array('url' => $url)),
           CURLOPT_HTTPHEADER => array(
               'Content-Type: application/json',
               'Authorization: Bearer ' . $api_key
           ),
       ));
       
       $response = curl_exec($curl);
       $err = curl_error($curl);
       
       curl_close($curl);
       
       if ($err) {
           return $url;
       }
       
       $result = json_decode($response, true);
       
       // Check if a shortened URL was returned
       if (isset($result['shortUrl']) && !empty($result['shortUrl'])) {
           return $result['shortUrl'];
       }
       
       return $url;
   }

   public function getTemplates($type) {
       $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "abandoned_cart_template WHERE type = '" . $this->db->escape($type) . "' ORDER BY name ASC");
       
       return $query->rows;
   }
}