<?php
namespace Jpos;

class Jposcart {
    private array $data = [];

    public function __construct(private $registry) {
        $this->config = $registry->get('config');
        $this->session = $registry->get('session');
        $this->db = $registry->get('db');
        $this->jpostax = $registry->get('jpostax');
        $this->weight = $registry->get('weight');
        $this->log = $registry->get('log');

        // Remove all the expired carts with no customer ID
        $this->db->query("DELETE FROM " . DB_PREFIX . "jposcart WHERE (api_id > '0' OR customer_id = '0') AND date_added < DATE_SUB(NOW(), INTERVAL 1 HOUR)");

        if ($this->session->data['jpos_customer_id']) {
            // We want to change the session ID on all the old items in the customers cart
            $this->db->query("UPDATE " . DB_PREFIX . "jposcart SET session_id = '" . $this->db->escape($this->session->getId()) . "' WHERE api_id = '0' AND customer_id = '" . (int)$this->session->data['jpos_customer_id'] . "'");

            // Once the customer is logged in we want to update the customers cart
            $cart_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "jposcart WHERE api_id = '0' AND customer_id = '0' AND session_id = '" . $this->db->escape($this->session->getId()) . "'");

            foreach ($cart_query->rows as $cart) {
                $this->db->query("DELETE FROM " . DB_PREFIX . "jposcart WHERE jposcart_id = '" . (int)$cart['jposcart_id'] . "'");

                // The advantage of using $this->add is that it will check if the products already exist and increase the quantity if necessary.
                $this->add($cart['product_id'], $cart['quantity'], json_decode($cart['option'], true), $cart['recurring_id']);
            }
        }
    }

    public function getProducts(): array {
        $product_data = [];

        $cart_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "jposcart WHERE api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND customer_id = '" . (int)$this->session->data['jpos_customer_id'] . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "'");

        foreach ($cart_query->rows as $cart) {
            $stock = true;

            $product_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_store p2s LEFT JOIN " . DB_PREFIX . "product p ON (p2s.product_id = p.product_id) LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) WHERE p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND p2s.product_id = '" . (int)$cart['product_id'] . "' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.date_available <= NOW() AND p.status = '1'");

            if ($product_query->num_rows && ($cart['quantity'] > 0)) {
                $option_price = 0;
                $option_points = 0;
                $option_weight = 0;

                $option_data = [];

                foreach (json_decode($cart['option'], true) as $product_option_id => $value) {
                    $option_query = $this->db->query("SELECT po.product_option_id, po.option_id, od.name, o.type FROM " . DB_PREFIX . "product_option po LEFT JOIN `" . DB_PREFIX . "option` o ON (po.option_id = o.option_id) LEFT JOIN " . DB_PREFIX . "option_description od ON (o.option_id = od.option_id) WHERE po.product_option_id = '" . (int)$product_option_id . "' AND po.product_id = '" . (int)$cart['product_id'] . "' AND od.language_id = '" . (int)$this->config->get('config_language_id') . "'");

                    if ($option_query->num_rows) {
                        if ($option_query->row['type'] == 'select' || $option_query->row['type'] == 'radio') {
                            $option_value_query = $this->db->query("SELECT pov.option_value_id, ovd.name, pov.quantity, pov.subtract, pov.price, pov.price_prefix, pov.points, pov.points_prefix, pov.weight, pov.weight_prefix FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "option_value ov ON (pov.option_value_id = ov.option_value_id) LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) WHERE pov.product_option_value_id = '" . (int)$value . "' AND pov.product_option_id = '" . (int)$product_option_id . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

                            if ($option_value_query->num_rows) {
                                if ($option_value_query->row['price_prefix'] == '+') {
                                    $option_price += $option_value_query->row['price'];
                                } elseif ($option_value_query->row['price_prefix'] == '-') {
                                    $option_price -= $option_value_query->row['price'];
                                }

                                if ($option_value_query->row['points_prefix'] == '+') {
                                    $option_points += $option_value_query->row['points'];
                                } elseif ($option_value_query->row['points_prefix'] == '-') {
                                    $option_points -= $option_value_query->row['points'];
                                }

                                if ($option_value_query->row['weight_prefix'] == '+') {
                                    $option_weight += $option_value_query->row['weight'];
                                } elseif ($option_value_query->row['weight_prefix'] == '-') {
                                    $option_weight -= $option_value_query->row['weight'];
                                }

                                if ($option_value_query->row['subtract'] && (!$option_value_query->row['quantity'] || ($option_value_query->row['quantity'] < $cart['quantity']))) {
                                    $stock = false;
                                }

                                $option_data[] = [
                                    'product_option_id'       => $product_option_id,
                                    'product_option_value_id' => $value,
                                    'option_id'               => $option_query->row['option_id'],
                                    'option_value_id'         => $option_value_query->row['option_value_id'],
                                    'name'                    => $option_query->row['name'],
                                    'value'                   => $option_value_query->row['name'],
                                    'type'                    => $option_query->row['type'],
                                    'quantity'                => $option_value_query->row['quantity'],
                                    'subtract'                => $option_value_query->row['subtract'],
                                    'price'                   => $option_value_query->row['price'],
                                    'price_prefix'            => $option_value_query->row['price_prefix'],
                                    'points'                  => $option_value_query->row['points'],
                                    'points_prefix'           => $option_value_query->row['points_prefix'],
                                    'weight'                  => $option_value_query->row['weight'],
                                    'weight_prefix'           => $option_value_query->row['weight_prefix']
                                ];
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

                                    if ($option_value_query->row['points_prefix'] == '+') {
                                        $option_points += $option_value_query->row['points'];
                                    } elseif ($option_value_query->row['points_prefix'] == '-') {
                                        $option_points -= $option_value_query->row['points'];
                                    }

                                    if ($option_value_query->row['weight_prefix'] == '+') {
                                        $option_weight += $option_value_query->row['weight'];
                                    } elseif ($option_value_query->row['weight_prefix'] == '-') {
                                        $option_weight -= $option_value_query->row['weight'];
                                    }

                                    if ($option_value_query->row['subtract'] && (!$option_value_query->row['quantity'] || ($option_value_query->row['quantity'] < $cart['quantity']))) {
                                        $stock = false;
                                    }

                                    $option_data[] = [
                                        'product_option_id'       => $product_option_id,
                                        'product_option_value_id' => $product_option_value_id,
                                        'option_id'               => $option_query->row['option_id'],
                                        'option_value_id'         => $option_value_query->row['option_value_id'],
                                        'name'                    => $option_query->row['name'],
                                        'value'                   => $option_value_query->row['name'],
                                        'type'                    => $option_query->row['type'],
                                        'quantity'                => $option_value_query->row['quantity'],
                                        'subtract'                => $option_value_query->row['subtract'],
                                        'price'                   => $option_value_query->row['price'],
                                        'price_prefix'            => $option_value_query->row['price_prefix'],
                                        'points'                  => $option_value_query->row['points'],
                                        'points_prefix'           => $option_value_query->row['points_prefix'],
                                        'weight'                  => $option_value_query->row['weight'],
                                        'weight_prefix'           => $option_value_query->row['weight_prefix']
                                    ];
                                }
                            }
                        } elseif ($option_query->row['type'] == 'text' || $option_query->row['type'] == 'textarea' || $option_query->row['type'] == 'file' || $option_query->row['type'] == 'date' || $option_query->row['type'] == 'datetime' || $option_query->row['type'] == 'time') {
                            $option_data[] = [
                                'product_option_id'       => $product_option_id,
                                'product_option_value_id' => '',
                                'option_id'               => $option_query->row['option_id'],
                                'option_value_id'         => '',
                                'name'                    => $option_query->row['name'],
                                'value'                   => $value,
                                'type'                    => $option_query->row['type'],
                                'quantity'                => '',
                                'subtract'                => '',
                                'price'                   => '',
                                'price_prefix'            => '',
                                'points'                  => '',
                                'points_prefix'           => '',
                                'weight'                  => '',
                                'weight_prefix'           => ''
                            ];
                        }
                    }
                }

                $price = $product_query->row['price'];

                // Product Discounts
                $discount_quantity = 0;

                foreach ($cart_query->rows as $cart_2) {
                    if ($cart_2['product_id'] == $cart['product_id']) {
                        $discount_quantity += $cart_2['quantity'];
                    }
                }

                $product_discount_query = $this->db->query("SELECT price FROM " . DB_PREFIX . "product_discount WHERE product_id = '" . (int)$cart['product_id'] . "' AND customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND quantity <= '" . (int)$discount_quantity . "' AND ((date_start = '0000-00-00' OR date_start < NOW()) AND (date_end = '0000-00-00' OR date_end > NOW())) ORDER BY quantity DESC, priority ASC, price ASC LIMIT 1");

                if ($product_discount_query->num_rows) {
                    $price = $product_discount_query->row['price'];
                }

                // Product Specials
                $product_special_query = $this->db->query("SELECT price FROM " . DB_PREFIX . "product_special WHERE product_id = '" . (int)$cart['product_id'] . "' AND customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((date_start = '0000-00-00' OR date_start < NOW()) AND (date_end = '0000-00-00' OR date_end > NOW())) ORDER BY priority ASC, price ASC LIMIT 1");

                if ($product_special_query->num_rows) {
                    $price = $product_special_query->row['price'];
                }

                // Reward Points
                $product_reward_query = $this->db->query("SELECT points FROM " . DB_PREFIX . "product_reward WHERE product_id = '" . (int)$cart['product_id'] . "' AND customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "'");

                if ($product_reward_query->num_rows) {
                    $reward = $product_reward_query->row['points'];
                } else {
                    $reward = 0;
                }

// Downloads
                $download_data = [];

                $download_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_download p2d LEFT JOIN " . DB_PREFIX . "download d ON (p2d.download_id = d.download_id) LEFT JOIN " . DB_PREFIX . "download_description dd ON (d.download_id = dd.download_id) WHERE p2d.product_id = '" . (int)$cart['product_id'] . "' AND dd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

                foreach ($download_query->rows as $download) {
                    $download_data[] = [
                        'download_id' => $download['download_id'],
                        'name'        => $download['name'],
                        'filename'    => $download['filename'],
                        'mask'        => $download['mask']
                    ];
                }

                // Stock
                if (!$product_query->row['quantity'] || ($product_query->row['quantity'] < $cart['quantity'])) {
                    $stock = false;
                }

                $recurring_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "recurring r LEFT JOIN " . DB_PREFIX . "product_recurring pr ON (r.recurring_id = pr.recurring_id) LEFT JOIN " . DB_PREFIX . "recurring_description rd ON (r.recurring_id = rd.recurring_id) WHERE r.recurring_id = '" . (int)$cart['recurring_id'] . "' AND pr.product_id = '" . (int)$cart['product_id'] . "' AND rd.language_id = " . (int)$this->config->get('config_language_id') . " AND r.status = 1 AND pr.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "'");

                $recurring = $recurring_query->num_rows ? [
                    'recurring_id'    => $cart['recurring_id'],
                    'name'            => $recurring_query->row['name'],
                    'frequency'       => $recurring_query->row['frequency'],
                    'price'           => $recurring_query->row['price'],
                    'cycle'           => $recurring_query->row['cycle'],
                    'duration'        => $recurring_query->row['duration'],
                    'trial'           => $recurring_query->row['trial_status'],
                    'trial_frequency' => $recurring_query->row['trial_frequency'],
                    'trial_price'     => $recurring_query->row['trial_price'],
                    'trial_cycle'     => $recurring_query->row['trial_cycle'],
                    'trial_duration'  => $recurring_query->row['trial_duration']
                ] : false;

                $product_data[] = [
                    'jposcart_id'     => $cart['jposcart_id'],
                    'product_id'      => $product_query->row['product_id'],
                    'name'            => $product_query->row['name'],
                    'model'           => $product_query->row['model'],
                    'shipping'        => $product_query->row['shipping'],
                    'image'           => $product_query->row['image'],
                    'option'          => $option_data,
                    'download'        => $download_data,
                    'quantity'        => $cart['quantity'],
                    'minimum'         => $product_query->row['minimum'],
                    'subtract'        => $product_query->row['subtract'],
                    'stock'           => $stock,
                    'price'           => ($price + $option_price),
                    'total'           => ($price + $option_price) * $cart['quantity'],
                    'reward'          => $reward * $cart['quantity'],
                    'points'          => ($product_query->row['points'] ? ($product_query->row['points'] + $option_points) * $cart['quantity'] : 0),
                    'tax_class_id'    => $product_query->row['tax_class_id'],
                    'weight'          => ($product_query->row['weight'] + $option_weight) * $cart['quantity'],
                    'weight_class_id' => $product_query->row['weight_class_id'],
                    'length'          => $product_query->row['length'],
                    'width'           => $product_query->row['width'],
                    'height'          => $product_query->row['height'],
                    'length_class_id' => $product_query->row['length_class_id'],
                    'recurring'       => $recurring
                ];
            } else {
                $this->remove($cart['jposcart_id']);
            }
        }

        return $product_data;
    }

    public function add(int $product_id, int $quantity = 1, array $option = [], int $recurring_id = 0): void {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "jposcart WHERE api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND customer_id = '" . (int)$this->session->data['jpos_customer_id'] . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "' AND product_id = '" . (int)$product_id . "' AND recurring_id = '" . (int)$recurring_id . "' AND `option` = '" . $this->db->escape(json_encode($option)) . "'");

        if (!$query->row['total']) {
            $this->db->query("INSERT " . DB_PREFIX . "jposcart SET api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "', customer_id = '" . (int)$this->session->data['jpos_customer_id'] . "', session_id = '" . $this->db->escape($this->session->getId()) . "', product_id = '" . (int)$product_id . "', recurring_id = '" . (int)$recurring_id . "', `option` = '" . $this->db->escape(json_encode($option)) . "', quantity = '" . (int)$quantity . "', date_added = NOW()");
        } else {
            $this->db->query("UPDATE " . DB_PREFIX . "jposcart SET quantity = (quantity + " . (int)$quantity . ") WHERE api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND customer_id = '" . (int)$this->session->data['jpos_customer_id'] . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "' AND product_id = '" . (int)$product_id . "' AND recurring_id = '" . (int)$recurring_id . "' AND `option` = '" . $this->db->escape(json_encode($option)) . "'");
        }
    }

    public function update(int $jposcart_id, int $quantity): void {
        $this->db->query("UPDATE " . DB_PREFIX . "jposcart SET quantity = '" . (int)$quantity . "' WHERE jposcart_id = '" . (int)$jposcart_id . "' AND api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND customer_id = '" . (int)$this->session->data['jpos_customer_id'] . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "'");
    }

    public function remove(int $jposcart_id): void {
        $this->db->query("DELETE FROM " . DB_PREFIX . "jposcart WHERE jposcart_id = '" . (int)$jposcart_id . "' AND api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND customer_id = '" . (int)$this->session->data['jpos_customer_id'] . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "'");
    }

    public function clear(): void {
        $this->db->query("DELETE FROM " . DB_PREFIX . "jposcart WHERE api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND customer_id = '" . (int)$this->session->data['jpos_customer_id'] . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "'");
    }

    public function getRecurringProducts(): array {
        return array_filter($this->getProducts(), fn($value) => $value['recurring']);
    }

    public function getWeight(): float {
        return array_reduce($this->getProducts(), function($weight, $product) {
            return $weight + ($product['shipping'] ? $this->weight->convert($product['weight'], $product['weight_class_id'], $this->config->get('config_weight_class_id')) : 0);
        }, 0);
    }

    public function getSubTotal(): float {
        return array_reduce($this->getProducts(), fn($total, $product) => $total + $product['total'], 0);
    }

    public function getTaxes(): array {
        $tax_data = [];

        foreach ($this->getProducts() as $product) {
            if ($product['tax_class_id']) {
                $tax_rates = $this->jpostax->getRates($product['price'], $product['tax_class_id']);

                foreach ($tax_rates as $tax_rate) {
                    if (!isset($tax_data[$tax_rate['tax_rate_id']])) {
                        $tax_data[$tax_rate['tax_rate_id']] = ($tax_rate['amount'] * $product['quantity']);
                    } else {
                        $tax_data[$tax_rate['tax_rate_id']] += ($tax_rate['amount'] * $product['quantity']);
                    }
                }
            }
        }

        return $tax_data;
    }

    public function getTotal(): float {
        return array_reduce($this->getProducts(), fn($total, $product) => $total + $this->jpostax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')) * $product['quantity'], 0);
    }

    public function countProducts(): int {
        return array_reduce($this->getProducts(), fn($total, $product) => $total + $product['quantity'], 0);
    }

    public function hasProducts(): bool {
        return count($this->getProducts()) > 0;
    }

    public function hasRecurringProducts(): bool {
        return count($this->getRecurringProducts()) > 0;
    }

    public function hasStock(): bool {
        return !in_array(false, array_column($this->getProducts(), 'stock'), true);
    }

    public function hasShipping(): bool {
        return in_array(true, array_column($this->getProducts(), 'shipping'), true);
    }

    public function hasDownload(): bool {
        return (bool)array_filter($this->getProducts(), fn($product) => !empty($product['download']));
    }

    public function SwitchCart(int $customer_id): void {
        if(!empty($this->session->data['jpos_customer_id'])) {
            $this->db->query("UPDATE ". DB_PREFIX ."jposcart SET customer_id = '". (int)$customer_id ."' WHERE customer_id = '". (int)$this->session->data['jpos_customer_id'] ."' AND api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "'");
        }
    }
}