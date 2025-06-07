<?php
class ModelCatalogProduct extends Model {
    public function getLowStockProducts($limit = 15) {
        $query = $this->db->query("SELECT p.product_id, pd.name, pi.branch_id, b.name as branch_name, pi.unit_id, u.desc_en as unit_name, pi.quantity, pi.quantity_available, IFNULL(pm.min_quantity, 0) as min_stock
            FROM " . DB_PREFIX . "product p
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "product_inventory pi ON (p.product_id = pi.product_id)
            LEFT JOIN " . DB_PREFIX . "branch b ON (pi.branch_id = b.branch_id)
            LEFT JOIN " . DB_PREFIX . "unit u ON (pi.unit_id = u.unit_id)
            WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND p.status = '1'
            AND pi.quantity < 5)
            ORDER BY pi.quantity ASC
            LIMIT " . (int)$limit);

        return $query->rows;
    }

    public function updateProductPrices($product_ids, $update_type, $value, $price_field, $unit_id) {
        $count = 0;
        $user_id = $this->user->getId();
        $now = date('Y-m-d H:i:s');

        foreach ($product_ids as $product_id) {
            $units = array();
            if ($unit_id == 'all') {
                $product_units = $this->getProductUnits($product_id);
                foreach ($product_units as $unit) {
                    $units[] = $unit['unit_id'];
                }
            } else {
                $units[] = $unit_id;
            }

            foreach ($units as $unit) {
                $current_price = $this->getProductPriceByUnit($product_id, $unit);

                if ($current_price) {
                    $old_price = $current_price[$price_field];
                    $new_price = 0;

                    switch ($update_type) {
                        case 'percentage':
                            $new_price = $old_price * (1 + ($value / 100));
                            break;
                        case 'fixed':
                            $new_price = $old_price + $value;
                            break;
                        case 'cost_based':
                            $inventory = $this->getProductInventoryByUnit($product_id, $unit);
                            $cost = isset($inventory['average_cost']) ? $inventory['average_cost'] : 0;
                            $margin = $value / 100;
                            $new_price = $cost / (1 - $margin);
                            break;
                        default:
                            $new_price = $value;
                    }

                    $new_price = max(0, $new_price);

                    $this->db->query("UPDATE " . DB_PREFIX . "product_pricing
                        SET " . $this->db->escape($price_field) . " = '" . (float)$new_price . "',
                        last_updated = NOW()
                        WHERE product_id = '" . (int)$product_id . "'
                        AND unit_id = '" . (int)$unit . "'");

                    $this->db->query("INSERT INTO " . DB_PREFIX . "product_price_history SET
                        product_id = '" . (int)$product_id . "',
                        unit_id = '" . (int)$unit . "',
                        price_type = '" . $this->db->escape($price_field) . "',
                        old_price = '" . (float)$old_price . "',
                        new_price = '" . (float)$new_price . "',
                        change_date = '" . $now . "',
                        change_type = '" . $this->db->escape($update_type) . "',
                        changed_by = '" . (int)$user_id . "'");

                    $count++;
                }
            }
        }

        return $count;
    }

    public function updateProductStock($product_ids, $warehouse_id, $unit_id, $update_type, $value, $reason, $update_cost = false, $cost_value = 0) {
        try {
            $this->db->query("START TRANSACTION");

            $count = 0;
            $errors = array();

            // تحميل مدير المخزون
            $this->load->model('catalog/inventory_manager');

            foreach ($product_ids as $product_id) {
                $units = array();
                if ($unit_id == 'all') {
                    $product_units = $this->getProductUnits($product_id);
                    foreach ($product_units as $unit) {
                        $units[] = $unit['unit_id'];
                    }
                } else {
                    $units[] = $unit_id;
                }

                foreach ($units as $unit) {
                    // تحديد نوع المرجع والكمية
                    $reference_type = 'adjustment';
                    $quantity_change = 0;

                    // الحصول على معلومات المخزون الحالية
                    $current_stock = $this->model_catalog_inventory_manager->getCurrentStock($product_id, $warehouse_id);
                    $old_quantity = $current_stock['quantity'];

                    switch ($update_type) {
                        case 'set':
                            $quantity_change = $value - $old_quantity;
                            break;
                        case 'increase':
                            $quantity_change = $value;
                            break;
                        case 'decrease':
                            // التحقق من توفر المخزون
                            if ($old_quantity < $value) {
                                $errors[] = sprintf(
                                    $this->language->get('error_insufficient_stock_for_product'),
                                    $product_id,
                                    $old_quantity,
                                    $value
                                );
                                continue 2;
                            }
                            $quantity_change = -$value;
                            break;
                        case 'percentage':
                            $percentage_change = $value / 100;
                            $new_quantity = $old_quantity * (1 + $percentage_change);
                            $quantity_change = $new_quantity - $old_quantity;
                            break;
                    }

                    // تجاهل التغييرات الصغيرة جداً
                    if (abs($quantity_change) < 0.0001) {
                        continue;
                    }

                    // تحديث المخزون باستخدام مدير المخزون
                    $cost = $update_cost ? $cost_value : null;
                    $result = $this->model_catalog_inventory_manager->updateStock(
                        $product_id,
                        $quantity_change,
                        $unit,
                        $warehouse_id,
                        'adjustment',
                        0,
                        $reason,
                        $cost
                    );

                    if ($result) {
                        $count++;
                    } else {
                        $errors[] = sprintf(
                            $this->language->get('error_movement_failed_for_product'),
                            $product_id,
                            $quantity_change > 0 ? 'increase' : 'decrease'
                        );
                    }
                }
            }

            if (!empty($errors)) {
                $this->db->query("ROLLBACK");
                throw new Exception(implode("\n", $errors));
            }

            $this->db->query("COMMIT");
            return $count;
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            error_log("Error in updateProductStock: " . $e->getMessage());
            return false;
        }
    }

    public function updateProductCost($product_ids, $update_type, $value, $unit_id, $update_prices = false, $margin_percentage = 0, $note = '') {
        $count = 0;

        // تحميل مدير المخزون
        $this->load->model('catalog/inventory_manager');

        foreach ($product_ids as $product_id) {
            $units = array();
            if ($unit_id == 'all') {
                $product_units = $this->getProductUnits($product_id);
                foreach ($product_units as $unit) {
                    $units[] = $unit['unit_id'];
                }
            } else {
                $units[] = $unit_id;
            }

            foreach ($units as $unit) {
                // الحصول على معلومات المخزون الحالية
                $warehouse_id = 1; // المستودع الرئيسي
                $current_stock = $this->model_catalog_inventory_manager->getCurrentStock($product_id, $warehouse_id);
                $old_cost = $current_stock['cost'];
                $new_cost = 0;

                switch ($update_type) {
                    case 'set':
                        $new_cost = $value;
                        break;
                    case 'increase':
                        $new_cost = $old_cost + $value;
                        break;
                    case 'decrease':
                        $new_cost = max(0, $old_cost - $value);
                        break;
                    case 'percentage':
                        $new_cost = $old_cost * (1 + ($value / 100));
                        break;
                }

                $new_cost = max(0, $new_cost);

                if ($new_cost == $old_cost) {
                    continue;
                }

                // تحديث المخزون مع التكلفة الجديدة
                $result = $this->model_catalog_inventory_manager->updateStock(
                    $product_id,
                    0, // لا تغيير في الكمية
                    $unit,
                    $warehouse_id,
                    'cost_adjustment',
                    0,
                    $note,
                    $new_cost
                );

                if ($update_prices && $margin_percentage > 0) {
                    $this->updateProductPricesByMargin($product_id, $unit, $new_cost, $margin_percentage);
                }

                if ($result) {
                    $count++;
                }
            }
        }

        return $count;
    }

    public function updateProductStatus($product_ids, $status) {
        $count = 0;

        foreach ($product_ids as $product_id) {
            $this->db->query("UPDATE " . DB_PREFIX . "product SET
                status = '" . (int)$status . "',
                date_modified = NOW()
                WHERE product_id = '" . (int)$product_id . "'");

            $count++;
        }

        return $count;
    }

    public function getProductCostHistory($product_id) {
        $query = $this->db->query("SELECT ich.*,
                CONCAT(u.desc_en, ' - ', u.desc_ar) as unit_name,
                CONCAT(us.firstname, ' ', us.lastname) as user_name
            FROM " . DB_PREFIX . "inventory_cost_history ich
            LEFT JOIN " . DB_PREFIX . "unit u ON (ich.unit_id = u.unit_id)
            LEFT JOIN " . DB_PREFIX . "user us ON (ich.user_id = us.user_id)
            WHERE ich.product_id = '" . (int)$product_id . "'
            ORDER BY ich.date_added DESC");

        return $query->rows;
    }

    public function checkProductInventory($product_id, $unit_id, $branch_id, $quantity_needed) {
        $query = $this->db->query("SELECT quantity, quantity_available
            FROM " . DB_PREFIX . "product_inventory
            WHERE product_id = '" . (int)$product_id . "'
            AND unit_id = '" . (int)$unit_id . "'
            AND branch_id = '" . (int)$branch_id . "'");

        if ($query->num_rows) {
            $quantity = $query->row['quantity'];
            $quantity_available = $query->row['quantity_available'];

            return array(
                'available' => $quantity_available >= $quantity_needed,
                'quantity' => $quantity,
                'quantity_available' => $quantity_available,
                'needed' => $quantity_needed,
                'shortage' => max(0, $quantity_needed - $quantity_available)
            );
        }

        return array(
            'available' => false,
            'quantity' => 0,
            'quantity_available' => 0,
            'needed' => $quantity_needed,
            'shortage' => $quantity_needed
        );
    }

    public function getProductInventoryDetailed($product_id, $branch_id = null) {
        $sql = "SELECT pi.*, b.name as branch_name, CONCAT(u.desc_en, ' - ', u.desc_ar) as unit_name
            FROM " . DB_PREFIX . "product_inventory pi
            LEFT JOIN " . DB_PREFIX . "branch b ON (pi.branch_id = b.branch_id)
            LEFT JOIN " . DB_PREFIX . "unit u ON (pi.unit_id = u.unit_id)
            WHERE pi.product_id = '" . (int)$product_id . "'";

        if ($branch_id !== null) {
            $sql .= " AND pi.branch_id = '" . (int)$branch_id . "'";
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getProductInventoryByUnit($product_id, $unit_id, $branch_id = null) {
        $sql = "SELECT pi.*,
                b.name AS branch_name,
                CONCAT(u.desc_en, ' - ', u.desc_ar) AS unit_name
            FROM " . DB_PREFIX . "product_inventory pi
            LEFT JOIN " . DB_PREFIX . "branch b ON (pi.branch_id = b.branch_id)
            LEFT JOIN " . DB_PREFIX . "unit u ON (pi.unit_id = u.unit_id)
            WHERE pi.product_id = '" . (int)$product_id . "'
            AND pi.unit_id = '" . (int)$unit_id . "'";

        if ($branch_id !== null) {
            $sql .= " AND pi.branch_id = '" . (int)$branch_id . "'";
            $query = $this->db->query($sql);

            return $query->row;
        } else {
            $query = $this->db->query($sql);

            return $query->rows;
        }
    }

    public function getProductPriceByUnit($product_id, $unit_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_pricing
            WHERE product_id = '" . (int)$product_id . "'
            AND unit_id = '" . (int)$unit_id . "'");

        return $query->row;
    }

    public function getProductBarcodeByUnit($product_id, $unit_id) {
        $query = $this->db->query("SELECT barcode FROM " . DB_PREFIX . "product_barcode
            WHERE product_id = '" . (int)$product_id . "'
            AND unit_id = '" . (int)$unit_id . "'
            AND product_option_id IS NULL
            ORDER BY product_barcode_id DESC
            LIMIT 1");

        return $query->num_rows ? $query->row['barcode'] : '';
    }

    public function getProductUnit($product_id, $unit_id) {
        $query = $this->db->query("SELECT pu.*, CONCAT(u.desc_en, ' - ', u.desc_ar) as unit_name
            FROM " . DB_PREFIX . "product_unit pu
            LEFT JOIN " . DB_PREFIX . "unit u ON (pu.unit_id = u.unit_id)
            WHERE pu.product_id = '" . (int)$product_id . "'
            AND pu.unit_id = '" . (int)$unit_id . "'");

        return $query->row;
    }

    public function getInventoryMovementsDetailed($filter_data) {
        $sql = "SELECT pm.*, CONCAT(u.desc_en, ' - ', u.desc_ar) as unit_name,
                b.name as branch_name, CONCAT(us.firstname, ' ', us.lastname) as user_name
            FROM " . DB_PREFIX . "product_movement pm
            LEFT JOIN " . DB_PREFIX . "unit u ON (pm.unit_id = u.unit_id)
            LEFT JOIN " . DB_PREFIX . "branch b ON (pm.branch_id = b.branch_id)
            LEFT JOIN " . DB_PREFIX . "user us ON (pm.user_id = us.user_id)
            WHERE pm.product_id = '" . (int)$filter_data['filter_product_id'] . "'";

        if (isset($filter_data['filter_type']) && !empty($filter_data['filter_type'])) {
            $sql .= " AND pm.type = '" . $this->db->escape($filter_data['filter_type']) . "'";
        }

        if (isset($filter_data['filter_branch_id']) && !empty($filter_data['filter_branch_id'])) {
            $sql .= " AND pm.branch_id = '" . (int)$filter_data['filter_branch_id'] . "'";
        }

        if (isset($filter_data['filter_date_start']) && !empty($filter_data['filter_date_start'])) {
            $sql .= " AND DATE(pm.date_added) >= '" . $this->db->escape($filter_data['filter_date_start']) . "'";
        }

        if (isset($filter_data['filter_date_end']) && !empty($filter_data['filter_date_end'])) {
            $sql .= " AND DATE(pm.date_added) <= '" . $this->db->escape($filter_data['filter_date_end']) . "'";
        }

        $sql .= " ORDER BY pm.date_added DESC";

        if (isset($filter_data['start']) || isset($filter_data['limit'])) {
            if ($filter_data['start'] < 0) {
                $filter_data['start'] = 0;
            }

            if ($filter_data['limit'] < 1) {
                $filter_data['limit'] = 10;
            }

            $sql .= " LIMIT " . (int)$filter_data['start'] . "," . (int)$filter_data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getTotalInventoryMovements($filter_data) {
        $sql = "SELECT COUNT(*) AS total
            FROM " . DB_PREFIX . "product_movement
            WHERE product_id = '" . (int)$filter_data['filter_product_id'] . "'";

        if (isset($filter_data['filter_type']) && !empty($filter_data['filter_type'])) {
            $sql .= " AND type = '" . $this->db->escape($filter_data['filter_type']) . "'";
        }

        if (isset($filter_data['filter_branch_id']) && !empty($filter_data['filter_branch_id'])) {
            $sql .= " AND branch_id = '" . (int)$filter_data['filter_branch_id'] . "'";
        }

        if (isset($filter_data['filter_date_start']) && !empty($filter_data['filter_date_start'])) {
            $sql .= " AND DATE(date_added) >= '" . $this->db->escape($filter_data['filter_date_start']) . "'";
        }

        if (isset($filter_data['filter_date_end']) && !empty($filter_data['filter_date_end'])) {
            $sql .= " AND DATE(date_added) <= '" . $this->db->escape($filter_data['filter_date_end']) . "'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    public function addProductQuantityDiscount($product_id, $discount_data) {
        if (!isset($discount_data['name']) || !isset($discount_data['type'])) {
            return false;
        }

        $this->db->query("INSERT INTO " . DB_PREFIX . "product_quantity_discounts SET
            product_id = '" . (int)$product_id . "',
            name = '" . $this->db->escape($discount_data['name']) . "',
            type = '" . $this->db->escape($discount_data['type']) . "',
            buy_quantity = '" . (int)$discount_data['buy_quantity'] . "',
            get_quantity = '" . (isset($discount_data['get_quantity']) ? (int)$discount_data['get_quantity'] : 0) . "',
            discount_type = '" . $this->db->escape($discount_data['discount_type']) . "',
            discount_value = '" . (float)$discount_data['discount_value'] . "',
            status = '" . (int)$discount_data['status'] . "',
            unit_id = '" . (int)$discount_data['unit_id'] . "',
            date_start = " . (isset($discount_data['date_start']) ? "'" . $this->db->escape($discount_data['date_start']) . "'" : "NULL") . ",
            date_end = " . (isset($discount_data['date_end']) ? "'" . $this->db->escape($discount_data['date_end']) . "'" : "NULL") . ",
            notes = " . (isset($discount_data['notes']) ? "'" . $this->db->escape($discount_data['notes']) . "'" : "NULL") . "
        ");

        return $this->db->getLastId();
    }

    public function getProductQuantityDiscounts($product_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_quantity_discounts
            WHERE product_id = '" . (int)$product_id . "'
            ORDER BY buy_quantity ASC");

        return $query->rows;
    }

    public function deleteProductQuantityDiscounts($product_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_quantity_discounts
            WHERE product_id = '" . (int)$product_id . "'");
    }

    public function editProductQuantityDiscount($discount_id, $discount_data) {
        $this->db->query("UPDATE " . DB_PREFIX . "product_quantity_discounts SET
            name = '" . $this->db->escape($discount_data['name']) . "',
            discount_type = '" . $this->db->escape($discount_data['discount_type']) . "',
            discount_value = '" . (float)$discount_data['discount_value'] . "',
            buy_quantity = '" . (int)$discount_data['buy_quantity'] . "',
            get_quantity = '" . (int)$discount_data['get_quantity'] . "',
            type = '" . $discount_data['type'] . "',
            status = '" . (int)$discount_data['status'] . "',
            unit_id = '" . (int)$discount_data['unit_id'] . "',
            date_start = " . (isset($discount_data['date_start']) ? "'" . $this->db->escape($discount_data['date_start']) . "'" : "NULL") . ",
            date_end = " . (isset($discount_data['date_end']) ? "'" . $this->db->escape($discount_data['date_end']) . "'" : "NULL") . ",
            notes = " . (isset($discount_data['notes']) ? "'" . $this->db->escape($discount_data['notes']) . "'" : "NULL") . "
            WHERE discount_id = '" . (int)$discount_id . "'");
    }

    public function getProductUpsells($product_id) {
        $upsells = array();

        $query = $this->db->query("SELECT pr.*, p.image, pd.name, u.unit_id, CONCAT(u.desc_en, ' - ', u.desc_ar) as unit_name
            FROM " . DB_PREFIX . "product_recommendation pr
            LEFT JOIN " . DB_PREFIX . "product p ON (pr.related_product_id = p.product_id)
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "unit u ON (pr.unit_id = u.unit_id)
            WHERE pr.product_id = '" . (int)$product_id . "'
            AND pr.type = 'upsell'
            AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

        foreach ($query->rows as $result) {
            $upsells[] = array(
                'related_product_id' => $result['related_product_id'],
                'name'               => $result['name'],
                'unit_id'            => $result['unit_id'],
                'unit_name'          => $result['unit_name'],
                'customer_group_id'  => $result['customer_group_id'],
                'priority'           => $result['priority'],
                'discount_type'      => $result['discount_type'],
                'discount_value'     => $result['discount_value'],
                'units'              => $this->getProductUnits($result['related_product_id'])
            );
        }

        return $upsells;
    }

    public function getProductCrossSells($product_id) {
        $cross_sells = array();

        $query = $this->db->query("SELECT pr.*, p.image, pd.name, u.unit_id, CONCAT(u.desc_en, ' - ', u.desc_ar) as unit_name
            FROM " . DB_PREFIX . "product_recommendation pr
            LEFT JOIN " . DB_PREFIX . "product p ON (pr.related_product_id = p.product_id)
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "unit u ON (pr.unit_id = u.unit_id)
            WHERE pr.product_id = '" . (int)$product_id . "'
            AND pr.type = 'cross_sell'
            AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

        foreach ($query->rows as $result) {
            $cross_sells[] = array(
                'related_product_id' => $result['related_product_id'],
                'name'               => $result['name'],
                'unit_id'            => $result['unit_id'],
                'unit_name'          => $result['unit_name'],
                'customer_group_id'  => $result['customer_group_id'],
                'priority'           => $result['priority'],
                'discount_type'      => $result['discount_type'],
                'discount_value'     => $result['discount_value'],
                'units'              => $this->getProductUnits($result['related_product_id'])
            );
        }

        return $cross_sells;
    }

    public function getProductRecommendations($product_id) {
        $query = $this->db->query("SELECT pr.*, pd.name, p.model, p.price, p.image, u.unit_id, CONCAT(u.desc_en, ' - ', u.desc_ar) AS unit_name, cgd.name AS customer_group
            FROM " . DB_PREFIX . "product_recommendation pr
            LEFT JOIN " . DB_PREFIX . "product p ON (pr.related_product_id = p.product_id)
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "unit u ON (pr.unit_id = u.unit_id)
            LEFT JOIN " . DB_PREFIX . "customer_group_description cgd ON (pr.customer_group_id = cgd.customer_group_id)
            WHERE pr.product_id = '" . (int)$product_id . "'
            AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND cgd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            ORDER BY pr.priority ASC");
        return $query->rows;
    }

    public function addRecommendationRule($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "recommendation_rule SET
            name = '" . $this->db->escape($data['name']) . "',
            condition_type = '" . $this->db->escape($data['condition_type']) . "',
            condition_value = '" . $this->db->escape(json_encode($data['condition_value'])) . "',
            recommendation_type = '" . $this->db->escape($data['recommendation_type']) . "',
            product_ids = '" . $this->db->escape(json_encode($data['product_ids'])) . "',
            priority = '" . (int)$data['priority'] . "',
            status = '" . (int)$data['status'] . "'");
    }

    public function editRecommendationRule($rule_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "recommendation_rule SET
            name = '" . $this->db->escape($data['name']) . "',
            condition_type = '" . $this->db->escape($data['condition_type']) . "',
            condition_value = '" . $this->db->escape(json_encode($data['condition_value'])) . "',
            recommendation_type = '" . $this->db->escape($data['recommendation_type']) . "',
            product_ids = '" . $this->db->escape(json_encode($data['product_ids'])) . "',
            priority = '" . (int)$data['priority'] . "',
            status = '" . (int)$data['status'] . "'
            WHERE rule_id = '" . (int)$rule_id . "'");
    }

    public function getRecommendationRules() {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "recommendation_rule ORDER BY priority ASC");
        return $query->rows;
    }

    public function addProductBundle($product_id, $data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "product_bundle SET product_id = '" . (int)$product_id . "', name = '" . $this->db->escape($data['name']) . "', discount_type = '" . $this->db->escape($data['discount_type']) . "', discount_value = '" . (float)$data['discount_value'] . "', status = '" . (int)$data['status'] . "'");

        $bundle_id = $this->db->getLastId();

        if (isset($data['bundle_item'])) {
            foreach ($data['bundle_item'] as $item) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_bundle_item SET bundle_id = '" . (int)$bundle_id . "', product_id = '" . (int)$item['product_id'] . "', quantity = '" . (int)$item['quantity'] . "', unit_id = '" . (int)$item['unit_id'] . "', is_free = '" . (int)$item['is_free'] . "'");
            }
        }

        return $bundle_id;
    }

    public function editProductBundle($bundle_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "product_bundle SET name = '" . $this->db->escape($data['name']) . "', discount_type = '" . $this->db->escape($data['discount_type']) . "', discount_value = '" . (float)$data['discount_value'] . "', status = '" . (int)$data['status'] . "' WHERE bundle_id = '" . (int)$bundle_id . "'");

        $this->db->query("DELETE FROM " . DB_PREFIX . "product_bundle_item WHERE bundle_id = '" . (int)$bundle_id . "'");

        if (isset($data['bundle_item'])) {
            foreach ($data['bundle_item'] as $item) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_bundle_item SET bundle_id = '" . (int)$bundle_id . "', product_id = '" . (int)$item['product_id'] . "', quantity = '" . (int)$item['quantity'] . "', unit_id = '" . (int)$item['unit_id'] . "', is_free = '" . (int)$item['is_free'] . "'");
            }
        }
    }

    public function deleteProductBundle($bundle_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_bundle WHERE bundle_id = '" . (int)$bundle_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_bundle_item WHERE bundle_id = '" . (int)$bundle_id . "'");
    }

    public function getProductBundles($product_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_bundle WHERE product_id = '" . (int)$product_id . "'");

        $bundles = $query->rows;

        foreach ($bundles as &$bundle) {
            $bundle['items'] = $this->getProductBundleItems($bundle['bundle_id']);
        }

        return $bundles;
    }

    public function getProductBundleItems($bundle_id) {
        $items = array();

        $query = $this->db->query("SELECT pbi.*, pd.name FROM " . DB_PREFIX . "product_bundle_item pbi LEFT JOIN " . DB_PREFIX . "product_description pd ON (pbi.product_id = pd.product_id) WHERE pbi.bundle_id = '" . (int)$bundle_id . "' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

        foreach ($query->rows as $item) {
            $unit_query = $this->db->query("SELECT u.unit_id, CONCAT(u.desc_en, ' - ', u.desc_ar) AS unit_name FROM " . DB_PREFIX . "unit u WHERE u.unit_id = '" . (int)$item['unit_id'] . "'");

            $item['units'] = $unit_query->rows;

            $items[] = $item;
        }

        return $items;
    }

    public function addProductBarcodes($product_id, $barcodes) {
        foreach ($barcodes as $barcode) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "product_barcode SET product_id = '" . (int)$product_id . "', barcode = '" . $this->db->escape($barcode['barcode']) . "', type = '" . $this->db->escape($barcode['type']) . "'");
        }
    }

    public function editProductBarcodes($product_id, $barcodes) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_barcode WHERE product_id = '" . (int)$product_id . "'");
        $this->addProductBarcodes($product_id, $barcodes);
    }

    public function getProductBarcodes($product_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_barcode WHERE product_id = '" . (int)$product_id . "'");
        return $query->rows;
    }

    public function getOrdersWithProduct($data = array()) {
        $sql = "SELECT o.order_id, CONCAT(o.firstname, ' ', o.lastname) AS customer,
                (SELECT os.name FROM " . DB_PREFIX . "order_status os
                 WHERE os.order_status_id = o.order_status_id
                 AND os.language_id = '" . (int)$this->config->get('config_language_id') . "') AS status,
                o.total, o.currency_code, o.currency_value, o.date_added
                FROM `" . DB_PREFIX . "order` o
                JOIN " . DB_PREFIX . "order_product op ON (o.order_id = op.order_id)
                WHERE op.product_id = '" . (int)$data['filter_product_id'] . "' and o.order_status_id > 0";

        if (!empty($data['filter_order_status_id'])) {
            $sql .= " AND o.order_status_id = '" . (int)$data['filter_order_status_id'] . "'";
        }

        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(o.date_added) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(o.date_added) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $sql .= " GROUP BY o.order_id";

        $sort_data = array(
            'o.order_id',
            'customer',
            'status',
            'o.date_added'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY o.order_id";
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

    public function getTotalOrdersWithProduct($data = array()) {
        $sql = "SELECT COUNT(DISTINCT o.order_id) AS total
                FROM `" . DB_PREFIX . "order` o
                JOIN " . DB_PREFIX . "order_product op ON (o.order_id = op.order_id)
                WHERE op.product_id = '" . (int)$data['filter_product_id'] . "'";

        if (!empty($data['filter_order_status_id'])) {
            $sql .= " AND o.order_status_id = '" . (int)$data['filter_order_status_id'] . "'";
        }

        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(o.date_added) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(o.date_added) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    private function validateProductUnits($units) {
        if (empty($units)) {
            return false;
        }

        $has_base_unit = false;
        foreach ($units as $unit) {
            if ($unit['unit_type'] === 'base') {
                $has_base_unit = true;
                break;
            }
        }

        return $has_base_unit;
    }

    public function addStockMovement($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "product_movement SET
            product_id = '" . (int)$data['product_id'] . "',
            type = '" . $this->db->escape($data['type']) . "',
            quantity = '" . (float)$data['quantity'] . "',
            unit_id = '" . (int)$data['unit_id'] . "',
            reference = '" . $this->db->escape($data['reference']) . "',
            date_added = NOW()");
        $movement_id = $this->db->getLastId();

        $sign = ($data['type'] == 'purchase' || $data['type'] == 'adjustment_increase') ? '+' : '-';

        $this->db->query("UPDATE " . DB_PREFIX . "product_inventory
            SET quantity = quantity " . $sign . " '" . (float)$data['quantity'] . "',
                quantity_available = quantity_available " . $sign . " '" . (float)$data['quantity'] . "'
            WHERE product_id = '" . (int)$data['product_id'] . "'
            AND unit_id = '" . (int)$data['unit_id'] . "'
            AND branch_id = '" . (int)$data['branch_id'] . "'");

        return $movement_id;
    }

    public function getStockMovements($product_id) {
        $query = $this->db->query("SELECT pm.*, CONCAT(u.desc_en, ' - ', u.desc_ar) AS unit_name
            FROM " . DB_PREFIX . "product_movement pm
            LEFT JOIN " . DB_PREFIX . "unit u ON (pm.unit_id = u.unit_id)
            WHERE pm.product_id = '" . (int)$product_id . "'
            ORDER BY pm.date_added DESC");
        if($query->rows){
           return $query->rows;
        }else{
           return [];
        }
    }

    public function getProductUnits($product_id) {
        $query = $this->db->query("SELECT pu.*, CONCAT(u.desc_en, ' - ', u.desc_ar) AS unit_name
            FROM " . DB_PREFIX . "product_unit pu
            LEFT JOIN " . DB_PREFIX . "unit u ON (pu.unit_id = u.unit_id)
            WHERE pu.product_id = '" . (int)$product_id . "'");

        return $query->rows;
    }

    public function getProductInventory($product_id) {
        $query = $this->db->query("SELECT pi.*,
            b.name AS branch_name,
            b.type AS branch_type,
            CONCAT(u.desc_en, ' - ', u.desc_ar) AS unit_name
            FROM " . DB_PREFIX . "product_inventory pi
            LEFT JOIN " . DB_PREFIX . "branch b ON (pi.branch_id = b.branch_id)
            LEFT JOIN " . DB_PREFIX . "unit u ON (pi.unit_id = u.unit_id)
            WHERE pi.product_id = '" . (int)$product_id . "'");

        return $query->rows;
    }

    public function getBranches() {
        $sql = "
            SELECT b.branch_id, b.name, b.type, b.eta_branch_id, b.available_online, b.telephone, b.email, b.manager_id,
                   ba.address_1, ba.address_2, ba.city, ba.postcode, ba.country_id, ba.zone_id
            FROM " . DB_PREFIX . "branch b
            LEFT JOIN " . DB_PREFIX . "branch_address ba ON (b.branch_id = ba.branch_id)
            ORDER BY b.name ASC
        ";
        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getProductPricing($product_id) {
        $query = $this->db->query("SELECT pp.*, CONCAT(u.desc_en, ' - ', u.desc_ar) AS unit_name
            FROM " . DB_PREFIX . "product_pricing pp
            LEFT JOIN " . DB_PREFIX . "unit u ON (pp.unit_id = u.unit_id)
            WHERE pp.product_id = '" . (int)$product_id . "'");

        return $query->rows;
    }

    public function getProductSeoKeyword($product_id) {
        $query = $this->db->query("SELECT keyword FROM " . DB_PREFIX . "seo_url WHERE query = 'product_id=" . (int)$product_id . "'");
        return $query->num_rows ? $query->row['keyword'] : '';
    }

    public function deleteProductRecommendations($product_id, $type) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_recommendation
                        WHERE product_id = '" . (int)$product_id . "'
                        AND type = '" . $this->db->escape($type) . "'");
    }

    public function addProductRecommendation($product_id, $data, $type) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "product_recommendation SET
            product_id = '" . (int)$product_id . "',
            related_product_id = '" . (int)$data['related_product_id'] . "',
            unit_id = '" . (int)$data['unit_id'] . "',
            customer_group_id = '" . (int)$data['customer_group_id'] . "',
            type = '" . $this->db->escape($type) . "',
            priority = '" . (int)$data['priority'] . "',
            discount_type = '" . $this->db->escape($data['discount_type']) . "',
            discount_value = '" . (float)$data['discount_value'] . "'");
    }

    public function addProduct($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "product SET model = '" . $this->db->escape($data['model']) . "', sku = '" . $this->db->escape($data['sku']) . "', upc = '" . $this->db->escape($data['upc']) . "', ean = '" . $this->db->escape($data['ean']) . "', jan = '" . $this->db->escape($data['jan']) . "', isbn = '" . $this->db->escape($data['isbn']) . "', mpn = '" . $this->db->escape($data['mpn']) . "', location = '" . $this->db->escape($data['location']) . "', quantity = '" . (int)$data['quantity'] . "', minimum = '" . (int)$data['minimum'] . "', subtract = '" . (int)$data['subtract'] . "', stock_status_id = '" . (int)$data['stock_status_id'] . "', date_available = '" . $this->db->escape($data['date_available']) . "', manufacturer_id = '" . (int)$data['manufacturer_id'] . "', shipping = '" . (int)$data['shipping'] . "', price = '" . (float)$data['price'] . "', points = '" . (int)$data['points'] . "', weight = '" . (float)$data['weight'] . "', weight_class_id = '" . (int)$data['weight_class_id'] . "', length = '" . (float)$data['length'] . "', width = '" . (float)$data['width'] . "', height = '" . (float)$data['height'] . "', length_class_id = '" . (int)$data['length_class_id'] . "', status = '" . (int)$data['status'] . "', tax_class_id = '" . (int)$data['tax_class_id'] . "', sort_order = '" . (int)$data['sort_order'] . "', date_added = NOW(), date_modified = NOW()");

        $product_id = $this->db->getLastId();

        if (isset($this->request->post['product_upsell'])) {
            foreach ($this->request->post['product_upsell'] as $upsell) {
                $this->addProductRecommendation($product_id, $upsell, 'upsell');
            }
        }

        if (isset($this->request->post['product_cross_sell'])) {
            foreach ($this->request->post['product_cross_sell'] as $cross_sell) {
                $this->addProductRecommendation($product_id, $cross_sell, 'cross_sell');
            }
        }

        if (isset($data['product_unit'])) {
            foreach ($data['product_unit'] as $unit) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_unit SET
                    product_id = '" . (int)$product_id . "',
                    unit_id = '" . (int)$unit['unit_id'] . "',
                    unit_type = '" . $this->db->escape($unit['unit_type']) . "',
                    conversion_factor = '" . (float)$unit['conversion_factor'] . "'");
            }
        }

        if (isset($data['product_unit']) && !$this->validateProductUnits($data['product_unit'])) {
            $data['product_unit'][] = [
                'unit_id' => 37,
                'unit_type' => 'base',
                'conversion_factor' => 1
            ];
        }

        if (isset($data['product_inventory'])) {
            foreach ($data['product_inventory'] as $inventory) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_inventory SET
                    product_id = '" . (int)$product_id . "',
                    branch_id = '" . (int)$inventory['branch_id'] . "',
                    unit_id = '" . (int)$inventory['unit_id'] . "',
                    quantity = '" . (float)$inventory['quantity'] . "',
                    quantity_available = '" . (float)$inventory['quantity_available'] . "'");
            }
        }

        if (isset($data['product_inventory'])) {
            $this->addInitialStock($product_id, $data['product_inventory']);
        }

        if (isset($data['product_pricing'])) {
            foreach ($data['product_pricing'] as $pricing) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_pricing SET
                    product_id = '" . (int)$product_id . "',
                    unit_id = '" . (int)$pricing['unit_id'] . "',
                    base_price = '" . (float)$pricing['base_price'] . "',
                    special_price = '" . (float)$pricing['special_price'] . "',
                    wholesale_price = '" . (float)$pricing['wholesale_price'] . "',
                    custom_price = '" . (float)$pricing['custom_price'] . "',
                    half_wholesale_price = '" . (float)$pricing['half_wholesale_price'] . "'");
            }
        }

        if (isset($data['image'])) {
            $this->db->query("UPDATE " . DB_PREFIX . "product SET image = '" . $this->db->escape($data['image']) . "' WHERE product_id = '" . (int)$product_id . "'");
        }

        foreach ($data['product_description'] as $language_id => $value) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "product_description SET product_id = '" . (int)$product_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "', description = '" . $this->db->escape($value['description']) . "', tag = '" . $this->db->escape($value['tag']) . "', meta_title = '" . $this->db->escape($value['meta_title']) . "', meta_description = '" . $this->db->escape($value['meta_description']) . "', meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "'");
        }

        if (isset($data['product_store'])) {
            foreach ($data['product_store'] as $store_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_store SET product_id = '" . (int)$product_id . "', store_id = '" . (int)$store_id . "'");
            }
        }

        if (isset($data['product_attribute'])) {
            foreach ($data['product_attribute'] as $product_attribute) {
                if ($product_attribute['attribute_id']) {
                    $this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' AND attribute_id = '" . (int)$product_attribute['attribute_id'] . "'");

                    foreach ($product_attribute['product_attribute_description'] as $language_id => $product_attribute_description) {
                        $this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' AND attribute_id = '" . (int)$product_attribute['attribute_id'] . "' AND language_id = '" . (int)$language_id . "'");

                        $this->db->query("INSERT INTO " . DB_PREFIX . "product_attribute SET product_id = '" . (int)$product_id . "', attribute_id = '" . (int)$product_attribute['attribute_id'] . "', language_id = '" . (int)$language_id . "', text = '" .  $this->db->escape($product_attribute_description['text']) . "'");
                    }
                }
            }
        }

        if (isset($data['product_option'])) {
            foreach ($data['product_option'] as $product_option) {
                if ($product_option['type'] == 'select' || $product_option['type'] == 'radio' || $product_option['type'] == 'checkbox' || $product_option['type'] == 'image') {
                    if (isset($product_option['product_option_value'])) {
                        $this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_id = '" . (int)$product_id . "', unit_id = '" . (int)$product_option['unit_id'] . "', option_id = '" . (int)$product_option['option_id'] . "', required = '" . (int)$product_option['required'] . "'");

                        $product_option_id = $this->db->getLastId();

                        foreach ($product_option['product_option_value'] as $product_option_value) {
                            $this->db->query("INSERT INTO " . DB_PREFIX . "product_option_value SET product_option_id = '" . (int)$product_option_id . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', option_value_id = '" . (int)$product_option_value['option_value_id'] . "', quantity = '" . (int)$product_option_value['quantity'] . "', subtract = '" . (int)$product_option_value['subtract'] . "', price = '" . (float)$product_option_value['price'] . "', price_prefix = '" . $this->db->escape($product_option_value['price_prefix']) . "', points = '" . (int)$product_option_value['points'] . "', points_prefix = '" . $this->db->escape($product_option_value['points_prefix']) . "', weight = '" . (float)$product_option_value['weight'] . "', weight_prefix = '" . $this->db->escape($product_option_value['weight_prefix']) . "'");
                        }
                    }
                } else {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_id = '" . (int)$product_id . "', unit_id = '" . (int)$product_option['unit_id'] . "', option_id = '" . (int)$product_option['option_id'] . "', value = '" . $this->db->escape($product_option['value']) . "', required = '" . (int)$product_option['required'] . "'");
                }
            }
        }

        if (isset($data['product_image'])) {
            foreach ($data['product_image'] as $product_image) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_image SET product_id = '" . (int)$product_id . "', image = '" . $this->db->escape($product_image['image']) . "', sort_order = '" . (int)$product_image['sort_order'] . "'");
            }
        }

        if (isset($data['product_category'])) {
            foreach ($data['product_category'] as $category_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$category_id . "'");
            }
        }

        if (isset($data['product_filter'])) {
            foreach ($data['product_filter'] as $filter_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_filter SET product_id = '" . (int)$product_id . "', filter_id = '" . (int)$filter_id . "'");
            }
        }

        if (isset($data['product_related'])) {
            foreach ($data['product_related'] as $related_id) {
                $this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$product_id . "' AND related_id = '" . (int)$related_id . "'");
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_related SET product_id = '" . (int)$product_id . "', related_id = '" . (int)$related_id . "'");
                $this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$related_id . "' AND related_id = '" . (int)$product_id . "'");
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_related SET product_id = '" . (int)$related_id . "', related_id = '" . (int)$product_id . "'");
            }
        }

        if (isset($data['product_reward'])) {
            foreach ($data['product_reward'] as $customer_group_id => $product_reward) {
                if ((int)$product_reward['points'] > 0) {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "product_reward SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$customer_group_id . "', points = '" . (int)$product_reward['points'] . "'");
                }
            }
        }

        if (isset($data['product_seo_url'])) {
            foreach ($data['product_seo_url'] as $store_id => $language) {
                foreach ($language as $language_id => $keyword) {
                    if (!empty($keyword)) {
                        $this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET store_id = '" . (int)$store_id . "', language_id = '" . (int)$language_id . "', query = 'product_id=" . (int)$product_id . "', keyword = '" . $this->db->escape($keyword) . "'");
                    }
                }
            }
        }

        if (isset($data['product_layout'])) {
            foreach ($data['product_layout'] as $store_id => $layout_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_layout SET product_id = '" . (int)$product_id . "', store_id = '" . (int)$store_id . "', layout_id = '" . (int)$layout_id . "'");
            }
        }

        if (isset($data['product_barcode'])) {
            $this->model_catalog_product->addProductBarcodes($product_id, $data['product_barcode']);
        }

        if (isset($data['product_bundle'])) {
            foreach ($data['product_bundle'] as $bundle) {
                $this->model_catalog_product->addProductBundle($product_id, $bundle);
            }
        }

if (isset($data['product_quantity_discount'])) {
            foreach ($data['product_quantity_discount'] as $discount) {
                $this->addProductQuantityDiscount($product_id, $discount);
            }
        }

        $this->cache->delete('product');

        return $product_id;
    }

    public function updateAverageCost($product_id, $unit_id, $branch_id, $quantity, $cost, $movement_type, $reference_id = 0) {
        try {
            $query = $this->db->query("SELECT quantity, average_cost
                FROM " . DB_PREFIX . "product_inventory
                WHERE product_id = '" . (int)$product_id . "'
                AND unit_id = '" . (int)$unit_id . "'
                AND branch_id = '" . (int)$branch_id . "'");

            $current_quantity = $query->num_rows ? $query->row['quantity'] : 0;
            $current_cost = $query->num_rows ? $query->row['average_cost'] : 0;

            $old_average_cost = $current_cost;
            $new_average_cost = $current_cost;
            $total_value_before = $current_quantity * $current_cost;

            if ($quantity > 0 && in_array($movement_type, array('purchase', 'adjustment_increase', 'transfer_in'))) {
                $total_value_after = $total_value_before + ($quantity * $cost);
                $new_quantity = $current_quantity + $quantity;

                if ($new_quantity > 0) {
                    $new_average_cost = $total_value_after / $new_quantity;
                } else {
                    $new_average_cost = $cost;
                }

                $this->recordCostHistory($product_id, $unit_id, $branch_id, $old_average_cost, $new_average_cost, $movement_type, $reference_id);
            } else if ($quantity < 0 && in_array($movement_type, array('sale', 'adjustment_decrease', 'transfer_out'))) {
                $new_average_cost = $current_cost;
            }

            $detailed_calculation = json_encode([
                'previous_qty' => $current_quantity,
                'previous_cost' => $current_cost,
                'previous_value' => $total_value_before,
                'change_qty' => $quantity,
                'change_cost' => $cost,
                'change_value' => $quantity * $cost,
                'new_qty' => $current_quantity + $quantity,
                'new_cost' => $new_average_cost,
                'new_value' => ($current_quantity + $quantity) * $new_average_cost
            ]);

            $this->db->query("UPDATE " . DB_PREFIX . "product_inventory
                SET average_cost = '" . (float)$new_average_cost . "'
                WHERE product_id = '" . (int)$product_id . "'
                AND unit_id = '" . (int)$unit_id . "'
                AND branch_id = '" . (int)$branch_id . "'");

            $effect_on_cost = ($new_average_cost > $old_average_cost) ? 'increase' :
                             (($new_average_cost < $old_average_cost) ? 'decrease' : 'no_change');

            $this->db->query("UPDATE " . DB_PREFIX . "product_movement
                SET old_average_cost = '" . (float)$old_average_cost . "',
                    new_average_cost = '" . (float)$new_average_cost . "',
                    effect_on_cost = '" . $this->db->escape($effect_on_cost) . "',
                    detailed_calculation = '" . $this->db->escape($detailed_calculation) . "'
                WHERE product_id = '" . (int)$product_id . "'
                AND movement_reference_id = '" . (int)$reference_id . "'
                AND movement_reference_type = '" . $this->db->escape($movement_type) . "'
                ORDER BY product_movement_id DESC
                LIMIT 1");

            $this->recordInventoryValuation($product_id, $unit_id, $branch_id, $new_average_cost, $quantity, $movement_type, $reference_id);

            return $new_average_cost;
        } catch (Exception $e) {
            $this->log->write('Error in updateAverageCost: ' . $e->getMessage());
            return false;
        }
    }

    public function recordInventoryValuation($product_id, $unit_id, $branch_id, $average_cost, $movement_quantity, $movement_type, $reference_id = 0) {
        try {
            $query = $this->db->query("SELECT average_cost, quantity, total_value
                FROM " . DB_PREFIX . "inventory_valuation
                WHERE product_id = '" . (int)$product_id . "'
                AND unit_id = '" . (int)$unit_id . "'
                AND branch_id = '" . (int)$branch_id . "'
                ORDER BY valuation_id DESC
                LIMIT 1");

            $previous_cost = $query->num_rows ? $query->row['average_cost'] : 0;
            $previous_quantity = $query->num_rows ? $query->row['quantity'] : 0;
            $previous_value = $query->num_rows ? $query->row['total_value'] : 0;

            $new_quantity = $previous_quantity;

            if (in_array($movement_type, array('purchase', 'adjustment_increase', 'transfer_in'))) {
                $new_quantity += $movement_quantity;
            } else if (in_array($movement_type, array('sale', 'adjustment_decrease', 'transfer_out'))) {
                $new_quantity -= $movement_quantity;
            }

            $new_value = $new_quantity * $average_cost;
            $movement_value = abs($movement_quantity * $average_cost);

            $this->db->query("INSERT INTO " . DB_PREFIX . "inventory_valuation SET
                product_id = '" . (int)$product_id . "',
                unit_id = '" . (int)$unit_id . "',
                branch_id = '" . (int)$branch_id . "',
                valuation_date = CURDATE(),
                average_cost = '" . (float)$average_cost . "',
                quantity = '" . (float)$new_quantity . "',
                total_value = '" . (float)$new_value . "',
                date_added = NOW(),
                transaction_reference_id = '" . (int)$reference_id . "',
                transaction_type = '" . $this->db->escape($movement_type) . "',
                previous_quantity = '" . (float)$previous_quantity . "',
                previous_cost = '" . (float)$previous_cost . "',
                previous_value = '" . (float)$previous_value . "',
                movement_quantity = '" . (float)$movement_quantity . "',
                movement_cost = '" . (float)$average_cost . "',
                movement_value = '" . (float)$movement_value . "'");

            if ($this->config->get('config_sync_inventory_accounting')) {
                $this->syncInventoryWithAccounting($product_id, $unit_id, $branch_id, $new_value, $previous_value);
            }
        } catch (Exception $e) {
            $this->log->write('Error in recordInventoryValuation: ' . $e->getMessage());
            return false;
        }
    }

    public function syncInventoryWithAccounting($product_id, $unit_id, $branch_id, $new_value, $previous_value) {
        if (abs($new_value - $previous_value) < 0.001) {
            return true;
        }

        try {
            $inventory_account = $this->config->get('config_inventory_account');
            if (!$inventory_account) {
                $inventory_account = '1420';
            }

            $cogs_account = $this->config->get('config_cogs_account');
            if (!$cogs_account) {
                $cogs_account = '5110';
            }

            $value_difference = $new_value - $previous_value;

            if ($value_difference > 0) {
                $journal_data = [
                    'refnum' => 'INV-' . date('YmdHis'),
                    'thedate' => date('Y-m-d'),
                    'description' => 'تحديث قيمة المخزون - المنتج ' . $this->getProductName($product_id),
                    'entries' => [
                        [
                            'account_code' => $inventory_account,
                            'is_debit' => 1,
                            'amount' => abs($value_difference)
                        ],
                        [
                            'account_code' => $this->config->get('config_inventory_adjustment_account') ?: '4110',
                            'is_debit' => 0,
                            'amount' => abs($value_difference)
                        ]
                    ],
                    'entrytype' => 2
                ];
            } else {
                $journal_data = [
                    'refnum' => 'INV-' . date('YmdHis'),
                    'thedate' => date('Y-m-d'),
                    'description' => 'تحديث قيمة المخزون - المنتج ' . $this->getProductName($product_id),
                    'entries' => [
                        [
                            'account_code' => $this->config->get('config_inventory_adjustment_account') ?: '5110',
                            'is_debit' => 1,
                            'amount' => abs($value_difference)
                        ],
                        [
                            'account_code' => $inventory_account,
                            'is_debit' => 0,
                            'amount' => abs($value_difference)
                        ]
                    ],
                    'entrytype' => 2
                ];
            }

            $this->load->model('accounts/journal');
            $journal_id = $this->model_accounts_journal->addJournal($journal_data);

            if ($journal_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "inventory_accounting_reconciliation_item SET
                    reconciliation_id = '0',
                    product_id = '" . (int)$product_id . "',
                    unit_id = '" . (int)$unit_id . "',
                    system_quantity = '" . (float)$this->getProductQuantity($product_id, $unit_id, $branch_id) . "',
                    system_value = '" . (float)$new_value . "',
                    accounting_value = '" . (float)$new_value . "',
                    difference_value = '0',
                    adjustment_journal_id = '" . (int)$journal_id . "',
                    is_adjusted = '1',
                    notes = 'تعديل تلقائي للقيمة المحاسبية'");
            }

            return true;
        } catch (Exception $e) {
            $this->log->write("Error in syncInventoryWithAccounting: " . $e->getMessage());
            return false;
        }
    }

    public function getProductName($product_id) {
        $query = $this->db->query("SELECT name FROM " . DB_PREFIX . "product_description
            WHERE product_id = '" . (int)$product_id . "'
            AND language_id = '" . (int)$this->config->get('config_language_id') . "'");

        return $query->num_rows ? $query->row['name'] : 'منتج #' . $product_id;
    }

    public function getProductQuantity($product_id, $unit_id, $branch_id) {
        $query = $this->db->query("SELECT quantity
            FROM " . DB_PREFIX . "product_inventory
            WHERE product_id = '" . (int)$product_id . "'
            AND unit_id = '" . (int)$unit_id . "'
            AND branch_id = '" . (int)$branch_id . "'");

        return $query->num_rows ? $query->row['quantity'] : 0;
    }

    public function getABCAnalysis($data) {
        $branch_id = isset($data['branch_id']) ? $data['branch_id'] : 0;
        $period_start = isset($data['period_start']) ? $data['period_start'] : date('Y-m-d', strtotime('-1 year'));
        $period_end = isset($data['period_end']) ? $data['period_end'] : date('Y-m-d');
        $analysis_type = isset($data['analysis_type']) ? $data['analysis_type'] : 'value';

        switch ($analysis_type) {
            case 'value':
                $sql = "SELECT p.product_id, pd.name, p.model, pi.unit_id,
                        CONCAT(u.desc_en, ' - ', u.desc_ar) as unit_name,
                        pi.quantity, pi.average_cost,
                        (pi.quantity * pi.average_cost) as item_value
                    FROM " . DB_PREFIX . "product_inventory pi
                    JOIN " . DB_PREFIX . "product p ON (pi.product_id = p.product_id)
                    JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
                    JOIN " . DB_PREFIX . "unit u ON (pi.unit_id = u.unit_id)
                    WHERE pi.branch_id = '" . (int)$branch_id . "'
                    AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
                    AND pi.quantity > 0
                    ORDER BY item_value DESC";
                break;

            case 'movement':
                $sql = "SELECT p.product_id, pd.name, p.model, pm.unit_id,
                        CONCAT(u.desc_en, ' - ', u.desc_ar) as unit_name,
                        SUM(IF(pm.type IN ('sale', 'transfer_out', 'adjustment_decrease'), pm.quantity, 0)) as total_out,
                        AVG(pm.new_average_cost) as average_cost,
                        SUM(IF(pm.type IN ('sale', 'transfer_out', 'adjustment_decrease'), pm.quantity * pm.new_average_cost, 0)) as item_value
                    FROM " . DB_PREFIX . "product_movement pm
                    JOIN " . DB_PREFIX . "product p ON (pm.product_id = p.product_id)
                    JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
                    JOIN " . DB_PREFIX . "unit u ON (pm.unit_id = u.unit_id)
                    WHERE pm.branch_id = '" . (int)$branch_id . "'
                    AND DATE(pm.date_added) BETWEEN '" . $this->db->escape($period_start) . "' AND '" . $this->db->escape($period_end) . "'
                    AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
                    GROUP BY p.product_id, pm.unit_id
                    ORDER BY total_out DESC";
                break;

            case 'frequency':
                $sql = "SELECT p.product_id, pd.name, p.model, pm.unit_id,
                        CONCAT(u.desc_en, ' - ', u.desc_ar) as unit_name,
                        COUNT(pm.product_movement_id) as movement_count,
                        AVG(pm.new_average_cost) as average_cost,
                        SUM(pm.quantity) as total_quantity,
                        COUNT(pm.product_movement_id) * AVG(pm.new_average_cost) as item_value
                    FROM " . DB_PREFIX . "product_movement pm
                    JOIN " . DB_PREFIX . "product p ON (pm.product_id = p.product_id)
                    JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
                    JOIN " . DB_PREFIX . "unit u ON (pm.unit_id = u.unit_id)
                    WHERE pm.branch_id = '" . (int)$branch_id . "'
                    AND pm.type IN ('sale', 'transfer_out')
                    AND DATE(pm.date_added) BETWEEN '" . $this->db->escape($period_start) . "' AND '" . $this->db->escape($period_end) . "'
                    AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
                    GROUP BY p.product_id, pm.unit_id
                    ORDER BY movement_count DESC";
                break;
        }

        $query = $this->db->query($sql);
        $items = $query->rows;

        if (empty($items)) {
            return [
                'a_items' => [],
                'b_items' => [],
                'c_items' => [],
                'summary' => [
                    'total_items' => 0,
                    'total_value' => 0,
                    'a_percentage' => 0,
                    'b_percentage' => 0,
                    'c_percentage' => 0
                ]
            ];
        }

        $total_value = 0;
        foreach ($items as $item) {
            $total_value += $item['item_value'];
        }

        $cumulative_percent = 0;
        $classified_items = [];

        foreach ($items as $index => $item) {
            $percent_of_total = ($total_value > 0) ? ($item['item_value'] / $total_value * 100) : 0;
            $cumulative_percent += $percent_of_total;

            if ($cumulative_percent <= 80) {
                $abc_class = 'A';
            } elseif ($cumulative_percent <= 95) {
                $abc_class = 'B';
            } else {
                $abc_class = 'C';
            }

            $classified_items[] = array_merge($item, [
                'percent_of_total' => $percent_of_total,
                'cumulative_percent' => $cumulative_percent,
                'abc_class' => $abc_class
            ]);

            $this->db->query("INSERT INTO " . DB_PREFIX . "inventory_abc_analysis SET
                product_id = '" . (int)$item['product_id'] . "',
                branch_id = '" . (int)$branch_id . "',
                period_start = '" . $this->db->escape($period_start) . "',
                period_end = '" . $this->db->escape($period_end) . "',
                value_contribution = '" . (float)$item['item_value'] . "',
                percentage_of_total = '" . (float)$percent_of_total . "',
                cumulative_percentage = '" . (float)$cumulative_percent . "',
                abc_class = '" . $this->db->escape($abc_class) . "',
                analysis_date = NOW(),
                created_by = '" . (int)$this->user->getId() . "'");
        }

        $a_items = array_filter($classified_items, function($item) { return $item['abc_class'] == 'A'; });
        $b_items = array_filter($classified_items, function($item) { return $item['abc_class'] == 'B'; });
        $c_items = array_filter($classified_items, function($item) { return $item['abc_class'] == 'C'; });

        $a_value = array_sum(array_column($a_items, 'item_value'));
        $b_value = array_sum(array_column($b_items, 'item_value'));
        $c_value = array_sum(array_column($c_items, 'item_value'));

        return [
            'a_items' => array_values($a_items),
            'b_items' => array_values($b_items),
            'c_items' => array_values($c_items),
            'summary' => [
                'total_items' => count($classified_items),
                'total_value' => $total_value,
                'a_percentage' => ($total_value > 0) ? ($a_value / $total_value * 100) : 0,
                'b_percentage' => ($total_value > 0) ? ($b_value / $total_value * 100) : 0,
                'c_percentage' => ($total_value > 0) ? ($c_value / $total_value * 100) : 0
            ]
        ];
    }

    public function getInventoryTurnoverReport($data) {
        $branch_id = isset($data['branch_id']) ? $data['branch_id'] : 0;
        $period_start = isset($data['period_start']) ? $data['period_start'] : date('Y-m-d', strtotime('-1 year'));
        $period_end = isset($data['period_end']) ? $data['period_end'] : date('Y-m-d');

        $result = [];

        $beginning_inventory_query = $this->db->query("
            SELECT p.product_id, pd.name, p.model, u.unit_id, CONCAT(u.desc_en, ' - ', u.desc_ar) as unit_name,
                   piv.quantity, piv.average_cost, piv.total_value
            FROM " . DB_PREFIX . "inventory_valuation piv
            JOIN " . DB_PREFIX . "product p ON (piv.product_id = p.product_id)
            JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            JOIN " . DB_PREFIX . "unit u ON (piv.unit_id = u.unit_id)
            WHERE piv.branch_id = '" . (int)$branch_id . "'
            AND piv.valuation_date <= '" . $this->db->escape($period_start) . "'
            AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            GROUP BY p.product_id, u.unit_id
            ORDER BY piv.valuation_date DESC");

        $beginning_inventory = [];
        foreach ($beginning_inventory_query->rows as $row) {
            $key = $row['product_id'] . '_' . $row['unit_id'];
            if (!isset($beginning_inventory[$key])) {
                $beginning_inventory[$key] = $row;
            }
        }

        $ending_inventory_query = $this->db->query("
            SELECT p.product_id, pd.name, p.model, u.unit_id, CONCAT(u.desc_en, ' - ', u.desc_ar) as unit_name,
                   piv.quantity, piv.average_cost, piv.total_value
            FROM " . DB_PREFIX . "inventory_valuation piv
            JOIN " . DB_PREFIX . "product p ON (piv.product_id = p.product_id)
            JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            JOIN " . DB_PREFIX . "unit u ON (piv.unit_id = u.unit_id)
            WHERE piv.branch_id = '" . (int)$branch_id . "'
            AND piv.valuation_date <= '" . $this->db->escape($period_end) . "'
            AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            GROUP BY p.product_id, u.unit_id
            ORDER BY piv.valuation_date DESC");

        $ending_inventory = [];
        foreach ($ending_inventory_query->rows as $row) {
            $key = $row['product_id'] . '_' . $row['unit_id'];
            if (!isset($ending_inventory[$key])) {
                $ending_inventory[$key] = $row;
            }
        }

        $cogs_query = $this->db->query("
            SELECT pm.product_id, pm.unit_id,
                   SUM(IF(pm.type = 'sale', pm.quantity, 0)) as quantity_sold,
                   SUM(IF(pm.type = 'sale', pm.quantity * pm.unit_cost, 0)) as cogs
            FROM " . DB_PREFIX . "product_movement pm
            WHERE pm.branch_id = '" . (int)$branch_id . "'
            AND pm.type = 'sale'
            AND DATE(pm.date_added) BETWEEN '" . $this->db->escape($period_start) . "' AND '" . $this->db->escape($period_end) . "'
            GROUP BY pm.product_id, pm.unit_id");

        $cogs = [];
        foreach ($cogs_query->rows as $row) {
            $key = $row['product_id'] . '_' . $row['unit_id'];
            $cogs[$key] = $row;
        }

        foreach ($ending_inventory as $key => $item) {
            $product_id = $item['product_id'];
            $unit_id = $item['unit_id'];

            $beginning_value = isset($beginning_inventory[$key]) ? $beginning_inventory[$key]['total_value'] : 0;
            $ending_value = $item['total_value'];
            $average_inventory = ($beginning_value + $ending_value) / 2;

            $cost_of_goods_sold = isset($cogs[$key]) ? $cogs[$key]['cogs'] : 0;

            $turnover_ratio = ($average_inventory > 0) ? ($cost_of_goods_sold / $average_inventory) : 0;

            $days_on_hand = ($turnover_ratio > 0) ? round(365 / $turnover_ratio) : 0;

            $this->db->query("INSERT INTO " . DB_PREFIX . "inventory_turnover SET
                product_id = '" . (int)$product_id . "',
                branch_id = '" . (int)$branch_id . "',
                period_start = '" . $this->db->escape($period_start) . "',
                period_end = '" . $this->db->escape($period_end) . "',
                beginning_inventory = '" . (float)$beginning_value . "',
                ending_inventory = '" . (float)$ending_value . "',
                average_inventory = '" . (float)$average_inventory . "',
                cost_of_goods_sold = '" . (float)$cost_of_goods_sold . "',
                turnover_ratio = '" . (float)$turnover_ratio . "',
                days_on_hand = '" . (int)$days_on_hand . "',
                analysis_date = NOW(),
                created_by = '" . (int)$this->user->getId() . "'");

            $result[] = [
                'product_id' => $product_id,
                'name' => $item['name'],
                'model' => $item['model'],
                'unit_id' => $unit_id,
                'unit_name' => $item['unit_name'],
                'beginning_inventory' => isset($beginning_inventory[$key]) ? $beginning_inventory[$key]['quantity'] : 0,
                'beginning_value' => $beginning_value,
                'ending_inventory' => $item['quantity'],
                'ending_value' => $ending_value,
                'average_inventory' => ($beginning_value + $ending_value) / 2,
                'cost_of_goods_sold' => $cost_of_goods_sold,
                'quantity_sold' => isset($cogs[$key]) ? $cogs[$key]['quantity_sold'] : 0,
                'turnover_ratio' => $turnover_ratio,
                'days_on_hand' => $days_on_hand
            ];
        }

        return $result;
    }

    public function getSlowMovingItems($data) {
        $branch_id = isset($data['branch_id']) ? $data['branch_id'] : 0;
        $days_threshold = isset($data['days_threshold']) ? $data['days_threshold'] : 90;
        $min_value = isset($data['min_value']) ? $data['min_value'] : 0;

        $sql = "SELECT p.product_id, pd.name, p.model, pi.unit_id,
                    CONCAT(u.desc_en, ' - ', u.desc_ar) as unit_name,
                    pi.quantity, pi.average_cost, (pi.quantity * pi.average_cost) as total_value,
                    MAX(pm.date_added) as last_movement_date,
                    DATEDIFF(NOW(), MAX(pm.date_added)) as days_no_movement
                FROM " . DB_PREFIX . "product_inventory pi
                JOIN " . DB_PREFIX . "product p ON (pi.product_id = p.product_id)
                JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
                JOIN " . DB_PREFIX . "unit u ON (pi.unit_id = u.unit_id)
                LEFT JOIN " . DB_PREFIX . "product_movement pm ON (
                    pi.product_id = pm.product_id AND
                    pi.unit_id = pm.unit_id AND
                    pi.branch_id = pm.branch_id AND
                    pm.type IN ('sale', 'transfer_out', 'adjustment_decrease')
                )
                WHERE pi.branch_id = '" . (int)$branch_id . "'
                AND pi.quantity > 0
                AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
                GROUP BY p.product_id, pi.unit_id
                HAVING (
                    (days_no_movement IS NULL) OR
                    (days_no_movement >= " . (int)$days_threshold . ")
                )
                AND total_value >= " . (float)$min_value . "
                ORDER BY days_no_movement DESC, total_value DESC";

        $query = $this->db->query($sql);
        $results = $query->rows;

        foreach ($results as $item) {
            $alert_query = $this->db->query("SELECT alert_id
                FROM " . DB_PREFIX . "inventory_alert
                WHERE product_id = '" . (int)$item['product_id'] . "'
                AND unit_id = '" . (int)$item['unit_id'] . "'
                AND branch_id = '" . (int)$branch_id . "'
                AND alert_type = 'slow_moving'
                AND status IN ('active', 'acknowledged')");

            if (!$alert_query->num_rows) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "inventory_alert SET
                    product_id = '" . (int)$item['product_id'] . "',
                    branch_id = '" . (int)$branch_id . "',
                    alert_type = 'slow_moving',
                    quantity = '" . (float)$item['quantity'] . "',
                    threshold = '" . (int)$days_threshold . "',
                    status = 'active',
                    days_no_movement = '" . (isset($item['days_no_movement']) ? (int)$item['days_no_movement'] : 0) . "',
                    last_movement_date = " . (isset($item['last_movement_date']) ? "'" . $this->db->escape($item['last_movement_date']) . "'" : "NULL") . ",
                    recommended_action = 'inventory_review',
                    created_at = NOW(),
                    notes = 'منتج بطيء الحركة - قيمة المخزون: " . $this->currency->format($item['total_value'], $this->config->get('config_currency')) . "'");
            }
        }

        return $results;
    }

    public function getBatchExpiringSoon($data = array()) {
        $alert_days = isset($data['alert_days']) ? (int)$data['alert_days'] : 30;
        $branch_id = isset($data['branch_id']) ? (int)$data['branch_id'] : 0;

        $sql = "SELECT pb.batch_id, pb.product_id, pd.name, p.model, p.sku,
                     pb.batch_number, pb.manufacturing_date, pb.expiry_date,
                     pb.remaining_quantity, pb.cost, u.unit_id,
                     CONCAT(u.desc_en, ' - ', u.desc_ar) as unit_name,
                     b.name as branch_name,
                     DATEDIFF(pb.expiry_date, CURDATE()) as days_remaining
                FROM " . DB_PREFIX . "product_batch pb
                JOIN " . DB_PREFIX . "product p ON (pb.product_id = p.product_id)
                JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
                JOIN " . DB_PREFIX . "unit u ON (p.track_batch_unit_id = u.unit_id)
                JOIN " . DB_PREFIX . "branch b ON (pb.branch_id = b.branch_id)
                WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
                AND pb.status = 'active'
                AND pb.remaining_quantity > 0
                AND pb.expiry_date IS NOT NULL
                AND DATEDIFF(pb.expiry_date, CURDATE()) BETWEEN 0 AND " . $alert_days;

        if ($branch_id > 0) {
            $sql .= " AND pb.branch_id = '" . $branch_id . "'";
        }

        $sql .= " ORDER BY days_remaining ASC";

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

        $expiring_products = array();

        foreach ($query->rows as $row) {
            $alert_query = $this->db->query("SELECT alert_id
                FROM " . DB_PREFIX . "inventory_alert
                WHERE product_id = '" . (int)$row['product_id'] . "'
                AND branch_id = '" . (int)$row['branch_id'] . "'
                AND alert_type = 'expired'
                AND status IN ('active', 'acknowledged')");

            if (!$alert_query->num_rows) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "inventory_alert SET
                    product_id = '" . (int)$row['product_id'] . "',
                    branch_id = '" . (int)$row['branch_id'] . "',
                    alert_type = 'expired',
                    quantity = '" . (float)$row['remaining_quantity'] . "',
                    threshold = '" . (int)$alert_days . "',
                    status = 'active',
                    days_left = '" . (int)$row['days_remaining'] . "',
                    recommended_action = 'inventory_review',
                    created_at = NOW(),
                    notes = 'منتج سينتهي خلال " . $row['days_remaining'] . " يوم - رقم الباتش: " . $row['batch_number'] . "'");
            }

            $expiring_products[] = $row;
        }

        return $expiring_products;
    }

    public function createStockCount($data) {
        try {
            $this->db->query("START TRANSACTION");

            $this->db->query("INSERT INTO " . DB_PREFIX . "stock_count SET
                branch_id = '" . (int)$data['branch_id'] . "',
                reference_code = '" . $this->db->escape($data['reference_code']) . "',
                count_date = '" . $this->db->escape($data['count_date']) . "',
                status = 'draft',
                notes = '" . $this->db->escape($data['notes']) . "',
                created_by = '" . (int)$this->user->getId() . "',
                created_at = NOW()");

            $stock_count_id = $this->db->getLastId();

            $sql = "SELECT p.product_id, pu.unit_id, pi.quantity as system_qty,
                       pd.name, CONCAT(u.desc_en, ' - ', u.desc_ar) as unit_name
                    FROM " . DB_PREFIX . "product p
                    JOIN " . DB_PREFIX . "product_unit pu ON (p.product_id = pu.product_id)
                    JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
                    JOIN " . DB_PREFIX . "unit u ON (pu.unit_id = u.unit_id)
                    LEFT JOIN " . DB_PREFIX . "product_inventory pi ON (
                        p.product_id = pi.product_id AND
                        pu.unit_id = pi.unit_id AND
                        pi.branch_id = '" . (int)$data['branch_id'] . "'
                    )
                    WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
                    AND p.status = '1'";

            if (!empty($data['category_id'])) {
                $sql .= " AND p.product_id IN (
                            SELECT product_id
                            FROM " . DB_PREFIX . "product_to_category
                            WHERE category_id = '" . (int)$data['category_id'] . "'
                          )";
            }

            if (!empty($data['products']) && is_array($data['products'])) {
                $sql .= " AND p.product_id IN (" . implode(',', array_map('intval', $data['products'])) . ")";
            }

            $product_query = $this->db->query($sql);

            foreach ($product_query->rows as $product) {
                $system_qty = isset($product['system_qty']) ? $product['system_qty'] : 0;

                $this->db->query("INSERT INTO " . DB_PREFIX . "stock_count_item SET
                    stock_count_id = '" . (int)$stock_count_id . "',
                    product_id = '" . (int)$product['product_id'] . "',
                    unit_id = '" . (int)$product['unit_id'] . "',
                    system_qty = '" . (float)$system_qty . "',
                    counted_qty = '0',
                    difference = '" . (float)(-$system_qty) . "'");
            }

            $this->db->query("COMMIT");
            return $stock_count_id;
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            $this->log->write("Error in createStockCount: " . $e->getMessage());
            return false;
        }
    }

    public function updateStockCountItem($count_item_id, $counted_qty, $notes = '') {
        try {
            $query = $this->db->query("SELECT stock_count_id, product_id, unit_id, system_qty
                FROM " . DB_PREFIX . "stock_count_item
                WHERE count_item_id = '" . (int)$count_item_id . "'");

            if (!$query->num_rows) {
                return false;
            }

            $item = $query->row;

            $difference = $counted_qty - $item['system_qty'];

            $this->db->query("UPDATE " . DB_PREFIX . "stock_count_item SET
                counted_qty = '" . (float)$counted_qty . "',
                difference = '" . (float)$difference . "',
                notes = '" . $this->db->escape($notes) . "'
                WHERE count_item_id = '" . (int)$count_item_id . "'");

            $count_query = $this->db->query("SELECT status
                FROM " . DB_PREFIX . "stock_count
                WHERE stock_count_id = '" . (int)$item['stock_count_id'] . "'");

            if ($count_query->row['status'] == 'draft') {
                $this->db->query("UPDATE " . DB_PREFIX . "stock_count SET
                    status = 'in_progress',
                    updated_by = '" . (int)$this->user->getId() . "',
                    updated_at = NOW()
                    WHERE stock_count_id = '" . (int)$item['stock_count_id'] . "'");
            }

            return true;
        } catch (Exception $e) {
            $this->log->write("Error in updateStockCountItem: " . $e->getMessage());
            return false;
        }
    }

    public function completeStockCount($stock_count_id, $apply_adjustments = false) {
        try {
            $this->db->query("START TRANSACTION");

            $count_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "stock_count
                WHERE stock_count_id = '" . (int)$stock_count_id . "'");

            if (!$count_query->num_rows || $count_query->row['status'] == 'completed') {
                throw new Exception("Invalid stock count or already completed");
            }

            $count_info = $count_query->row;
            $branch_id = $count_info['branch_id'];

            $this->db->query("UPDATE " . DB_PREFIX . "stock_count SET
                status = 'completed',
                updated_by = '" . (int)$this->user->getId() . "',
                updated_at = NOW()
                WHERE stock_count_id = '" . (int)$stock_count_id . "'");

            if ($apply_adjustments) {
                $items_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "stock_count_item
                    WHERE stock_count_id = '" . (int)$stock_count_id . "'
                    AND difference != 0");

                if ($items_query->num_rows) {
                    $increase_items = array_filter($items_query->rows, function($item) {
                        return $item['difference'] > 0;
                    });

                    if (!empty($increase_items)) {
                        $adjustment_data = array(
                            'adjustment_number' => 'ADJ-COUNT-' . $stock_count_id . '-INC',
                            'branch_id' => $branch_id,
                            'type' => 'increase',
                            'status' => 'approved',
                            'adjustment_date' => date('Y-m-d'),
                            'notes' => 'تسوية زيادة من الجرد #' . $stock_count_id,
                            'items' => array()
                        );

                        foreach ($increase_items as $item) {
                            $adjustment_data['items'][] = array(
                                'product_id' => $item['product_id'],
                                'quantity' => $item['difference'],
                                'unit_id' => $item['unit_id'],
                                'reason' => 'زيادة من الجرد #' . $stock_count_id
                            );
                        }

                        $this->addStockAdjustment($adjustment_data);
                    }

                    $decrease_items = array_filter($items_query->rows, function($item) {
                        return $item['difference'] < 0;
                    });

                    if (!empty($decrease_items)) {
                        $adjustment_data = array(
                            'adjustment_number' => 'ADJ-COUNT-' . $stock_count_id . '-DEC',
                            'branch_id' => $branch_id,
                            'type' => 'decrease',
                            'status' => 'approved',
                            'adjustment_date' => date('Y-m-d'),
                            'notes' => 'تسوية نقص من الجرد #' . $stock_count_id,
                            'items' => array()
                        );

                        foreach ($decrease_items as $item) {
                            $adjustment_data['items'][] = array(
                                'product_id' => $item['product_id'],
                                'quantity' => abs($item['difference']),
                                'unit_id' => $item['unit_id'],
                                'reason' => 'نقص من الجرد #' . $stock_count_id
                            );
                        }

                        $this->addStockAdjustment($adjustment_data);
                    }
                }
            }

            $this->db->query("COMMIT");
            return true;
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            $this->log->write("Error in completeStockCount: " . $e->getMessage());
            return false;
        }
    }

    public function createProductBatch($data) {
        try {
            $product_query = $this->db->query("SELECT track_batch FROM " . DB_PREFIX . "product
                WHERE product_id = '" . (int)$data['product_id'] . "'");

            if (!$product_query->num_rows || !$product_query->row['track_batch']) {
                throw new Exception("Product does not exist or does not track batches");
            }

            if (empty($data['batch_number']) || empty($data['expiry_date']) || empty($data['initial_quantity'])) {
                throw new Exception("Missing required batch data");
            }

            $this->db->query("INSERT INTO " . DB_PREFIX . "product_batch SET
                product_id = '" . (int)$data['product_id'] . "',
                branch_id = '" . (int)$data['branch_id'] . "',
                batch_number = '" . $this->db->escape($data['batch_number']) . "',
                manufacturing_date = " . (isset($data['manufacturing_date']) ? "'" . $this->db->escape($data['manufacturing_date']) . "'" : "NULL") . ",
                expiry_date = '" . $this->db->escape($data['expiry_date']) . "',
                initial_quantity = '" . (float)$data['initial_quantity'] . "',
                remaining_quantity = '" . (float)$data['initial_quantity'] . "',
                cost = '" . (float)$data['cost'] . "',
                status = 'active',
                notes = '" . (isset($data['notes']) ? $this->db->escape($data['notes']) : "") . "',
                created_by = '" . (int)$this->user->getId() . "',
                created_at = NOW()");

            $batch_id = $this->db->getLastId();

            if (isset($data['create_movement']) && $data['create_movement']) {
                $movement_data = array(
                    'product_id' => $data['product_id'],
                    'type' => 'initial',
                    'quantity' => $data['initial_quantity'],
                    'unit_id' => $data['unit_id'],
                    'branch_id' => $data['branch_id'],
                    'reference' => 'BATCH-' . $data['batch_number'],
                    'movement_reference_type' => 'batch',
                    'movement_reference_id' => $batch_id,
                    'unit_cost' => $data['cost'],
                    'user_id' => $this->user->getId()
                );

                $this->addInventoryMovement($movement_data);
            }

            return $batch_id;
        } catch (Exception $e) {
            $this->log->write("Error in createProductBatch: " . $e->getMessage());
            return false;
        }
    }

    public function getInventoryAlerts($data = array()) {
        $sql = "SELECT ia.*, p.model, pd.name as product_name,
                   b.name as branch_name, u.unit_id,
                   CONCAT(u.desc_en, ' - ', u.desc_ar) as unit_name,
                   pi.quantity, pi.quantity_available, pi.average_cost,
                   CONCAT(user.firstname, ' ', user.lastname) as user_name
                FROM " . DB_PREFIX . "inventory_alert ia
                JOIN " . DB_PREFIX . "product p ON (ia.product_id = p.product_id)
                JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
                JOIN " . DB_PREFIX . "branch b ON (ia.branch_id = b.branch_id)
                LEFT JOIN " . DB_PREFIX . "product_inventory pi ON (
                    ia.product_id = pi.product_id AND
                    ia.unit_id = pi.unit_id AND
                    ia.branch_id = pi.branch_id
                )
                LEFT JOIN " . DB_PREFIX . "unit u ON (ia.unit_id = u.unit_id)
                LEFT JOIN " . DB_PREFIX . "user user ON (ia.acknowledged_by = user.user_id)
                WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        if (isset($data['filter_alert_type']) && !empty($data['filter_alert_type'])) {
            $sql .= " AND ia.alert_type = '" . $this->db->escape($data['filter_alert_type']) . "'";
        }

        if (isset($data['filter_status']) && !empty($data['filter_status'])) {
            $sql .= " AND ia.status = '" . $this->db->escape($data['filter_status']) . "'";
        } else {
            $sql .= " AND ia.status IN ('active', 'acknowledged')";
        }

        if (isset($data['filter_branch_id']) && !empty($data['filter_branch_id'])) {
            $sql .= " AND ia.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }

        if (isset($data['filter_product_id']) && !empty($data['filter_product_id'])) {
            $sql .= " AND ia.product_id = '" . (int)$data['filter_product_id'] . "'";
        }

        if (isset($data['filter_name']) && !empty($data['filter_name'])) {
            $sql .= " AND pd.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        $sql .= " ORDER BY ia.created_at DESC";

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

    public function updateAlertStatus($alert_id, $status, $notes = '') {
        try {
            $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "inventory_alert
                WHERE alert_id = '" . (int)$alert_id . "'");

            if (!$query->num_rows) {
                return false;
            }

            $alert = $query->row;

            $this->db->query("UPDATE " . DB_PREFIX . "inventory_alert SET
                status = '" . $this->db->escape($status) . "',
                notes = CONCAT(notes, ' | " . $this->db->escape(date('Y-m-d H:i:s') . ': ' . $notes) . "')
                " . ($status == 'acknowledged' ? ", acknowledged_by = '" . (int)$this->user->getId() . "', acknowledged_at = NOW()" : "") . "
                " . ($status == 'resolved' ? ", resolved_at = NOW()" : "") . "
                WHERE alert_id = '" . (int)$alert_id . "'");

            return true;
        } catch (Exception $e) {
            $this->log->write("Error in updateAlertStatus: " . $e->getMessage());
            return false;
        }
    }

    public function getMinMaxLevels($data = array()) {
        $sql = "SELECT rl.*, p.model, pd.name as product_name,
                   b.name as branch_name, u.unit_id,
                   CONCAT(u.desc_en, ' - ', u.desc_ar) as unit_name,
                   pi.quantity, pi.quantity_available
                FROM " . DB_PREFIX . "reorder_level rl
                JOIN " . DB_PREFIX . "product p ON (rl.product_id = p.product_id)
                JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
                JOIN " . DB_PREFIX . "branch b ON (rl.branch_id = b.branch_id)
                JOIN " . DB_PREFIX . "unit u ON (rl.unit_id = u.unit_id)
                LEFT JOIN " . DB_PREFIX . "product_inventory pi ON (
                    rl.product_id = pi.product_id AND
                    rl.unit_id = pi.unit_id AND
                    rl.branch_id = pi.branch_id
                )
                WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        if (isset($data['filter_branch_id']) && !empty($data['filter_branch_id'])) {
            $sql .= " AND rl.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }

        if (isset($data['filter_product_id']) && !empty($data['filter_product_id'])) {
            $sql .= " AND rl.product_id = '" . (int)$data['filter_product_id'] . "'";
        }

        if (isset($data['filter_name']) && !empty($data['filter_name'])) {
            $sql .= " AND pd.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        if (isset($data['filter_needs_reorder']) && $data['filter_needs_reorder']) {
            $sql .= " AND pi.quantity <= rl.minimum_level";
        }

        if (isset($data['sort']) && isset($data['order'])) {
            switch ($data['sort']) {
                case 'product_name':
                    $sql .= " ORDER BY pd.name";
                    break;
                case 'branch_name':
                    $sql .= " ORDER BY b.name";
                    break;
                case 'quantity':
                    $sql .= " ORDER BY pi.quantity";
                    break;
                case 'minimum_level':
                    $sql .= " ORDER BY rl.minimum_level";
                    break;
                case 'maximum_level':
                    $sql .= " ORDER BY rl.maximum_level";
                    break;
                default:
                    $sql .= " ORDER BY pd.name";
            }

            $sql .= ($data['order'] == 'DESC') ? ' DESC' : ' ASC';
        } else {
            $sql .= " ORDER BY pd.name ASC";
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

    public function recordInventoryHistory($product_id, $branch_id, $unit_id, $transaction_type, $reference_id, $reference_type, $movement_quantity, $cost_after, $movement_id) {
        $query = $this->db->query("SELECT quantity, quantity_available, average_cost
            FROM " . DB_PREFIX . "product_inventory
            WHERE product_id = '" . (int)$product_id . "'
            AND branch_id = '" . (int)$branch_id . "'
            AND unit_id = '" . (int)$unit_id . "'");

        $quantity_after = $query->num_rows ? $query->row['quantity'] : 0;
        $quantity_before = $quantity_after;

        if (in_array($transaction_type, array('purchase', 'adjustment_increase', 'transfer_in'))) {
            $quantity_before -= $movement_quantity;
        } else if (in_array($transaction_type, array('sale', 'adjustment_decrease', 'transfer_out'))) {
            $quantity_before += $movement_quantity;
        }

        $this->db->query("INSERT INTO " . DB_PREFIX . "product_inventory_history SET
            product_id = '" . (int)$product_id . "',
            branch_id = '" . (int)$branch_id . "',
            unit_id = '" . (int)$unit_id . "',
            transaction_date = NOW(),
            transaction_type = '" . $this->db->escape($transaction_type) . "',
            reference_id = '" . (int)$reference_id . "',
            reference_type = '" . $this->db->escape($reference_type) . "',
            quantity_before = '" . (float)$quantity_before . "',
            quantity_after = '" . (float)$quantity_after . "',
            quantity_change = '" . (float)$movement_quantity . "',
            cost_before = '" . (float)($query->num_rows ? $query->row['average_cost'] : 0) . "',
            cost_after = '" . (float)$cost_after . "',
            created_by = '" . (int)($this->session->data['user_id'] ?? 0) . "',
            created_at = NOW()");
    }

    public function addStockAdjustment($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "stock_adjustment SET
            adjustment_number = '" . $this->db->escape($data['adjustment_number']) . "',
            branch_id = '" . (int)$data['branch_id'] . "',
            type = '" . $this->db->escape($data['type']) . "',
            status = 'draft',
            adjustment_date = '" . $this->db->escape($data['adjustment_date']) . "',
            notes = '" . $this->db->escape($data['notes']) . "',
            created_by = '" . (int)($this->session->data['user_id'] ?? 0) . "',
            created_at = NOW(),
            updated_at = NOW()");

        $adjustment_id = $this->db->getLastId();

        $total_value = 0;

        foreach ($data['items'] as $item) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "stock_adjustment_item SET
                adjustment_id = '" . (int)$adjustment_id . "',
                product_id = '" . (int)$item['product_id'] . "',
                quantity = '" . (float)$item['quantity'] . "',
                unit_id = '" . (int)$item['unit_id'] . "',
                reason = '" . $this->db->escape($item['reason']) . "'");

            $adjustment_item_id = $this->db->getLastId();

            $query = $this->db->query("SELECT average_cost FROM " . DB_PREFIX . "product_inventory
                WHERE product_id = '" . (int)$item['product_id'] . "'
                AND unit_id = '" . (int)$item['unit_id'] . "'
                AND branch_id = '" . (int)$data['branch_id'] . "'");

            $average_cost = $query->num_rows ? $query->row['average_cost'] : 0;

            $item_value = $item['quantity'] * $average_cost;
            $total_value += $item_value;
        }

        if ($data['status'] == 'approved') {
            $journal_id = $this->createAdjustmentJournalEntry($adjustment_id, $data, $total_value);

            $this->db->query("UPDATE " . DB_PREFIX . "stock_adjustment SET
                journal_id = '" . (int)$journal_id . "',
                status = 'approved',
                approved_by = '" . (int)($this->session->data['user_id'] ?? 0) . "',
                updated_at = NOW()
                WHERE adjustment_id = '" . (int)$adjustment_id . "'");

            foreach ($data['items'] as $item) {
                $movement_type = ($data['type'] == 'increase') ? 'adjustment_increase' : 'adjustment_decrease';

                $this->addInventoryMovement([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_id' => $item['unit_id'],
                    'type' => $movement_type,
                    'branch_id' => $data['branch_id'],
                    'reference' => $data['adjustment_number'],
                    'movement_reference_type' => 'adjustment',
                    'movement_reference_id' => $adjustment_id,
                    'journal_id' => $journal_id
                ]);
            }
        }

        return $adjustment_id;
    }

    private function createAdjustmentJournalEntry($adjustment_id, $data, $total_value) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "stock_adjustment WHERE adjustment_id = '" . (int)$adjustment_id . "'");
        $adjustment = $query->row;

        $inventory_account = '1420';
        $adjustment_account = ($data['type'] == 'increase') ? '4110' : '5110';

        $this->db->query("INSERT INTO " . DB_PREFIX . "journals SET
            refnum = 'ADJ-" . (int)$adjustment_id . "',
            thedate = '" . $this->db->escape($data['adjustment_date']) . "',
            description = 'تعديل مخزون #" . $this->db->escape($data['adjustment_number']) . "',
            added_by = '" . (int)($this->session->data['user_id'] ?? 0) . "',
            created_at = NOW(),
            entrytype = 2");

        $journal_id = $this->db->getLastId();

        if ($data['type'] == 'increase') {
            $this->db->query("INSERT INTO " . DB_PREFIX . "journal_entries SET
                journal_id = '" . (int)$journal_id . "',
                account_code = '" . $inventory_account . "',
                debit = '" . (float)$total_value . "',
                credit = '0',
                description = 'تعديل مخزون (زيادة) - #" . $this->db->escape($data['adjustment_number']) . "'");

            $this->db->query("INSERT INTO " . DB_PREFIX . "journal_entries SET
                journal_id = '" . (int)$journal_id . "',
                account_code = '" . $adjustment_account . "',
                debit = '0',
                credit = '" . (float)$total_value . "',
                description = 'تعديل مخزون (زيادة) - #" . $this->db->escape($data['adjustment_number']) . "'");
        } else {
            $this->db->query("INSERT INTO " . DB_PREFIX . "journal_entries SET
                journal_id = '" . (int)$journal_id . "',
                account_code = '" . $adjustment_account . "',
                debit = '" . (float)$total_value . "',
                credit = '0',
                description = 'تعديل مخزون (نقص) - #" . $this->db->escape($data['adjustment_number']) . "'");

            $this->db->query("INSERT INTO " . DB_PREFIX . "journal_entries SET
                journal_id = '" . (int)$journal_id . "',
                account_code = '" . $inventory_account . "',
                debit = '0',
                credit = '" . (float)$total_value . "',
                description = 'تعديل مخزون (نقص) - #" . $this->db->escape($data['adjustment_number']) . "'");
        }

        return $journal_id;
    }

    public function getInventoryValuationReport($data = array()) {
        $sql = "SELECT piv.*, p.model, pd.name as product_name,
                 CONCAT(u.desc_en, ' - ', u.desc_ar) as unit_name,
                 b.name as branch_name
                FROM " . DB_PREFIX . "product_inventory piv
                LEFT JOIN " . DB_PREFIX . "product p ON (piv.product_id = p.product_id)
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
                LEFT JOIN " . DB_PREFIX . "unit u ON (piv.unit_id = u.unit_id)
                LEFT JOIN " . DB_PREFIX . "branch b ON (piv.branch_id = b.branch_id)
                WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        if (!empty($data['filter_product_id'])) {
            $sql .= " AND piv.product_id = '" . (int)$data['filter_product_id'] . "'";
        }

        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND piv.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }

        if (!empty($data['filter_unit_id'])) {
            $sql .= " AND piv.unit_id = '" . (int)$data['filter_unit_id'] . "'";
        }

        if (isset($data['filter_quantity_min']) && $data['filter_quantity_min'] !== '') {
            $sql .= " AND piv.quantity >= '" . (float)$data['filter_quantity_min'] . "'";
        }

        if (isset($data['filter_quantity_max']) && $data['filter_quantity_max'] !== '') {
            $sql .= " AND piv.quantity <= '" . (float)$data['filter_quantity_max'] . "'";
        }

        $sql .= " ORDER BY pd.name ASC, piv.branch_id ASC, piv.unit_id ASC";

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

    public function getProductPriceHistory($product_id) {
        $query = $this->db->query("SELECT pph.*,
                CONCAT(u.desc_en, ' - ', u.desc_ar) as unit_name,
                CONCAT(us.firstname, ' ', us.lastname) as user_name
            FROM " . DB_PREFIX . "product_price_history pph
            LEFT JOIN " . DB_PREFIX . "unit u ON (pph.unit_id = u.unit_id)
            LEFT JOIN " . DB_PREFIX . "user us ON (pph.user_id = us.user_id)
            WHERE pph.product_id = '" . (int)$product_id . "'
            ORDER BY pph.change_date DESC");

        return $query->rows;
    }

    public function transferInventory($data) {
        try {
            $this->db->query("START TRANSACTION");

            if ($data['source_branch_id'] == $data['destination_branch_id']) {
                throw new Exception($this->language->get('error_same_branch'));
            }

            if (empty($data['items']) || !is_array($data['items'])) {
                throw new Exception($this->language->get('error_items_required'));
            }

            if (empty($data['transfer_number'])) {
                $data['transfer_number'] = 'TRN-' . date('YmdHis');
            }

            if (empty($data['transfer_date'])) {
                $data['transfer_date'] = date('Y-m-d');
            }

            $this->db->query("INSERT INTO " . DB_PREFIX . "inventory_transfer SET
                transfer_number = '" . $this->db->escape($data['transfer_number']) . "',
                source_branch_id = '" . (int)$data['source_branch_id'] . "',
                destination_branch_id = '" . (int)$data['destination_branch_id'] . "',
                transfer_date = '" . $this->db->escape($data['transfer_date']) . "',
                notes = '" . $this->db->escape($data['notes'] ?? '') . "',
                status = 'pending',
                created_by = '" . (int)($data['created_by'] ?? $this->user->getId()) . "',
                created_at = NOW()");

            $transfer_id = $this->db->getLastId();

            foreach ($data['items'] as $item) {
                if (empty($item['product_id']) || empty($item['unit_id']) || !isset($item['quantity'])) {
                    throw new Exception($this->language->get('error_invalid_item'));
                }

                $product_id = (int)$item['product_id'];
                $unit_id = (int)$item['unit_id'];
                $quantity = (float)$item['quantity'];

                if ($quantity <= 0) {
                    throw new Exception($this->language->get('error_quantity_must_be_positive'));
                }

                $inventory_check = $this->checkProductInventory(
                    $product_id,
                    $unit_id,
                    $data['source_branch_id'],
                    $quantity
                );

                if (!$inventory_check['available']) {
                    $product_info = $this->getProduct($product_id);
                    $unit_info = $this->getProductUnit($product_id, $unit_id);

                    throw new Exception(sprintf(
                        $this->language->get('error_insufficient_stock_for_transfer'),
                        $product_info['name'],
                        $unit_info['unit_name'],
                        $inventory_check['quantity_available'],
                        $quantity
                    ));
                }

                $this->db->query("INSERT INTO " . DB_PREFIX . "inventory_transfer_item SET
                    transfer_id = '" . (int)$transfer_id . "',
                    product_id = '" . (int)$product_id . "',
                    unit_id = '" . (int)$unit_id . "',
                    quantity = '" . (float)$quantity . "',
                    cost = '" . (float)($inventory_check['average_cost'] ?? 0) . "'");
            }

            if (isset($data['status']) && $data['status'] == 'completed') {
                $this->completeInventoryTransfer($transfer_id);
            }

            $this->db->query("COMMIT");
            return $transfer_id;
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            error_log("Error in transferInventory: " . $e->getMessage());
            throw $e;
        }
    }

    public function completeInventoryTransfer($transfer_id) {
        try {
            $this->db->query("START TRANSACTION");

            $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "inventory_transfer
                WHERE transfer_id = '" . (int)$transfer_id . "'");

            if (!$query->num_rows) {
                throw new Exception($this->language->get('error_transfer_not_found'));
            }

            $transfer = $query->row;

            if ($transfer['status'] == 'completed') {
                throw new Exception($this->language->get('error_transfer_already_completed'));
            }

            $items_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "inventory_transfer_item
                WHERE transfer_id = '" . (int)$transfer_id . "'");

            if (!$items_query->num_rows) {
                throw new Exception($this->language->get('error_transfer_no_items'));
            }

            $items = $items_query->rows;

            foreach ($items as $item) {
                $product_id = $item['product_id'];
                $unit_id = $item['unit_id'];
                $quantity = $item['quantity'];

                $source_inventory = $this->checkProductInventory(
                    $product_id,
                    $unit_id,
                    $transfer['source_branch_id'],
                    $quantity
                );

                if (!$source_inventory['available']) {
                    throw new Exception(sprintf(
                        $this->language->get('error_insufficient_stock_for_transfer_item'),
                        $product_id,
                        $source_inventory['quantity_available'],
                        $quantity
                    ));
                }

                $source_cost = $source_inventory['average_cost'] ?? 0;

                $this->addInventoryMovement([
                    'product_id' => $product_id,
                    'type' => 'transfer_out',
                    'quantity' => $quantity,
                    'unit_id' => $unit_id,
                    'branch_id' => $transfer['source_branch_id'],
                    'reference' => $transfer['transfer_number'],
                    'movement_reference_type' => 'transfer',
                    'movement_reference_id' => $transfer_id,
                    'unit_cost' => $source_cost,
                    'user_id' => $this->user->getId()
                ]);

                $this->addInventoryMovement([
                    'product_id' => $product_id,
                    'type' => 'transfer_in',
                    'quantity' => $quantity,
                    'unit_id' => $unit_id,
                    'branch_id' => $transfer['destination_branch_id'],
                    'reference' => $transfer['transfer_number'],
                    'movement_reference_type' => 'transfer',
                    'movement_reference_id' => $transfer_id,
                    'unit_cost' => $source_cost,
                    'user_id' => $this->user->getId()
                ]);
            }

            $this->db->query("UPDATE " . DB_PREFIX . "inventory_transfer SET
                status = 'completed',
                completed_by = '" . (int)$this->user->getId() . "',
                completed_at = NOW()
                WHERE transfer_id = '" . (int)$transfer_id . "'");

            $this->db->query("COMMIT");
            return true;
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            error_log("Error in completeInventoryTransfer: " . $e->getMessage());
            throw $e;
        }
    }

    public function addInventoryAlert($product_id, $data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "inventory_alert SET
            product_id = '" . (int)$product_id . "',
            branch_id = '" . (int)$data['branch_id'] . "',
            unit_id = '" . (int)$data['unit_id'] . "',
            min_quantity = '" . (float)$data['min_quantity'] . "',
            max_quantity = '" . (float)$data['max_quantity'] . "',
            notify_email = '" . (int)$data['notify_email'] . "',
            status = '" . (int)$data['status'] . "',
            created_by = '" . (int)($this->session->data['user_id'] ?? 0) . "',
            date_added = NOW()");

        return $this->db->getLastId();
    }

    public function getProductInventoryAlerts($product_id) {
        $query = $this->db->query("SELECT ia.*,
                CONCAT(u.desc_en, ' - ', u.desc_ar) as unit_name,
                b.name as branch_name
            FROM " . DB_PREFIX . "inventory_alert ia
            LEFT JOIN " . DB_PREFIX . "branch b ON (ia.branch_id = b.branch_id)
            LEFT JOIN " . DB_PREFIX . "unit u ON (ia.unit_id = u.unit_id)
            WHERE ia.product_id = '" . (int)$product_id . "'
            ORDER BY ia.branch_id, ia.unit_id");

        return $query->rows;
    }

    public function updateReservedInventory($product_id, $unit_id, $branch_id, $quantity, $operation) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_inventory
            WHERE product_id = '" . (int)$product_id . "'
            AND unit_id = '" . (int)$unit_id . "'
            AND branch_id = '" . (int)$branch_id . "'");

        if (!$query->num_rows) {
            return false;
        }

        $inventory = $query->row;

        if ($operation == 'reserve') {
            if ($inventory['quantity_available'] < $quantity) {
                return false;
            }

            $reserved_quantity = $inventory['quantity'] - $inventory['quantity_available'] + $quantity;
            $available_quantity = $inventory['quantity'] - $reserved_quantity;

            $this->db->query("UPDATE " . DB_PREFIX . "product_inventory SET
                quantity_available = '" . (float)$available_quantity . "'
                WHERE product_id = '" . (int)$product_id . "'
                AND unit_id = '" . (int)$unit_id . "'
                AND branch_id = '" . (int)$branch_id . "'");
        } elseif ($operation == 'release') {
            $reserved_quantity = $inventory['quantity'] - $inventory['quantity_available'] - $quantity;
            $reserved_quantity = max(0, $reserved_quantity);
            $available_quantity = $inventory['quantity'] - $reserved_quantity;

            $this->db->query("UPDATE " . DB_PREFIX . "product_inventory SET
                quantity_available = '" . (float)$available_quantity . "'
                WHERE product_id = '" . (int)$product_id . "'
                AND unit_id = '" . (int)$unit_id . "'
                AND branch_id = '" . (int)$branch_id . "'");
        }

        return true;
    }

    public function calculateOrderCOGS($order_id) {
        $query = $this->db->query("SELECT op.product_id, op.quantity, op.unit_id,
                pm.average_cost
            FROM " . DB_PREFIX . "order_product op
            LEFT JOIN " . DB_PREFIX . "product_movement pm ON (op.product_id = pm.product_id AND op.unit_id = pm.unit_id)
            WHERE op.order_id = '" . (int)$order_id . "'
            ORDER BY pm.date_added DESC");

        $total_cogs = 0;
        $processed_products = array();

        foreach ($query->rows as $item) {
            $product_unit_key = $item['product_id'] . '_' . $item['unit_id'];
            if (isset($processed_products[$product_unit_key])) {
                continue;
            }

            $total_cogs += $item['quantity'] * $item['average_cost'];
            $processed_products[$product_unit_key] = true;
        }

        return $total_cogs;
    }

    public function recordOrderCOGS($order_id, $total_cogs = null) {
        if ($total_cogs === null) {
            $total_cogs = $this->calculateOrderCOGS($order_id);
        }

        $this->db->query("INSERT INTO " . DB_PREFIX . "order_cogs SET
            order_id = '" . (int)$order_id . "',
            total_cogs = '" . (float)$total_cogs . "',
            date_added = NOW()");

        return true;
    }

    public function checkInventoryAvailability($products, $branch_id) {
        $unavailable_items = array();

        foreach ($products as $product) {
            $product_id = $product['product_id'];
            $unit_id = $product['unit_id'];
            $quantity = $product['quantity'];

            $query = $this->db->query("SELECT quantity_available
                FROM " . DB_PREFIX . "product_inventory
                WHERE product_id = '" . (int)$product_id . "'
                AND unit_id = '" . (int)$unit_id . "'
                AND branch_id = '" . (int)$branch_id . "'");

            if (!$query->num_rows || $query->row['quantity_available'] < $quantity) {
                $unavailable_items[] = array(
                    'product_id' => $product_id,
                    'unit_id' => $unit_id,
                    'requested_quantity' => $quantity,
                    'available_quantity' => $query->num_rows ? $query->row['quantity_available'] : 0
                );
            }
        }

        if (empty($unavailable_items)) {
            return true;
        } else {
            return $unavailable_items;
        }
    }

    public function recordInventoryCount($product_id, $unit_id, $branch_id, $counted_quantity, $notes = '') {
        $query = $this->db->query("SELECT quantity FROM " . DB_PREFIX . "product_inventory
            WHERE product_id = '" . (int)$product_id . "'
            AND unit_id = '" . (int)$unit_id . "'
            AND branch_id = '" . (int)$branch_id . "'");

        $system_quantity = $query->num_rows ? $query->row['quantity'] : 0;
        $quantity_difference = $counted_quantity - $system_quantity;

        $this->db->query("INSERT INTO " . DB_PREFIX . "inventory_count SET
            product_id = '" . (int)$product_id . "',
            unit_id = '" . (int)$unit_id . "',
            branch_id = '" . (int)$branch_id . "',
            system_quantity = '" . (float)$system_quantity . "',
            counted_quantity = '" . (float)$counted_quantity . "',
            quantity_difference = '" . (float)$quantity_difference . "',
            counted_by = '" . (int)($this->session->data['user_id'] ?? 0) . "',
            count_date = NOW(),
            notes = '" . $this->db->escape($notes) . "',
            status = 'pending'");

        return $this->db->getLastId();
    }

    public function applyInventoryCount($count_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "inventory_count WHERE count_id = '" . (int)$count_id . "'");

        if (!$query->num_rows || $query->row['status'] != 'pending') {
            return false;
        }

        $count = $query->row;

        if ($count['quantity_difference'] != 0) {
            $adjustment_type = $count['quantity_difference'] > 0 ? 'adjustment_increase' : 'adjustment_decrease';
            $adjustment_quantity = abs($count['quantity_difference']);

            $this->addInventoryMovement([
                'product_id' => $count['product_id'],
                'quantity' => $adjustment_quantity,
                'unit_id' => $count['unit_id'],
                'type' => $adjustment_type,
                'branch_id' => $count['branch_id'],
                'reference' => 'COUNT-' . $count_id,
                'movement_reference_type' => 'count',
                'movement_reference_id' => $count_id
            ]);

            $this->db->query("UPDATE " . DB_PREFIX . "inventory_count SET
                status = 'applied',
                applied_by = '" . (int)($this->session->data['user_id'] ?? 0) . "',
                applied_date = NOW()
                WHERE count_id = '" . (int)$count_id . "'");
        } else {
            $this->db->query("UPDATE " . DB_PREFIX . "inventory_count SET
                status = 'applied',
                applied_by = '" . (int)($this->session->data['user_id'] ?? 0) . "',
                applied_date = NOW()
                WHERE count_id = '" . (int)$count_id . "'");
        }

        return true;
    }

    public function createInventorySheet($branch_id, $filter_data = array()) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "inventory_sheet SET
            branch_id = '" . (int)$branch_id . "',
            sheet_date = NOW(),
            status = 'draft',
            created_by = '" . (int)($this->session->data['user_id'] ?? 0) . "',
            created_at = NOW()");

        $sheet_id = $this->db->getLastId();

        $sql = "SELECT p.product_id, pi.unit_id, pi.quantity, pd.name
            FROM " . DB_PREFIX . "product_inventory pi
            LEFT JOIN " . DB_PREFIX . "product p ON (pi.product_id = p.product_id)
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            WHERE pi.branch_id = '" . (int)$branch_id . "'
            AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        if (!empty($filter_data['filter_category_id'])) {
            $sql .= " AND p.product_id IN (SELECT product_id FROM " . DB_PREFIX . "product_to_category WHERE category_id = '" . (int)$filter_data['filter_category_id'] . "')";
        }

        if (!empty($filter_data['filter_name'])) {
            $sql .= " AND pd.name LIKE '%" . $this->db->escape($filter_data['filter_name']) . "%'";
        }

        $query = $this->db->query($sql);

        foreach ($query->rows as $product) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "inventory_sheet_item SET
                sheet_id = '" . (int)$sheet_id . "',
                product_id = '" . (int)$product['product_id'] . "',
                unit_id = '" . (int)$product['unit_id'] . "',
                system_qty = '" . (float)$product['quantity'] . "',
                counted_qty = '0',
                notes = ''");
        }

        return $sheet_id;
    }

    public function updateInventorySheetCounts($sheet_id, $items) {
        foreach ($items as $item) {
            $this->db->query("UPDATE " . DB_PREFIX . "inventory_sheet_item SET
                counted_qty = '" . (float)$item['counted_qty'] . "',
                notes = '" . $this->db->escape($item['notes']) . "'
                WHERE sheet_id = '" . (int)$sheet_id . "'
                AND product_id = '" . (int)$item['product_id'] . "'
                AND unit_id = '" . (int)$item['unit_id'] . "'");
        }

        return true;
    }

    public function finishInventorySheet($sheet_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "inventory_sheet WHERE sheet_id = '" . (int)$sheet_id . "'");

        if (!$query->num_rows || $query->row['status'] != 'draft') {
            return false;
        }

        $sheet = $query->row;

        $items_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "inventory_sheet_item WHERE sheet_id = '" . (int)$sheet_id . "'");

        foreach ($items_query->rows as $item) {
            if ($item['counted_qty'] > 0 || $item['counted_qty'] === '0') {
                $count_id = $this->recordInventoryCount(
                    $item['product_id'],
                    $item['unit_id'],
                    $sheet['branch_id'],
                    $item['counted_qty'],
                    $item['notes']
                );

                $this->applyInventoryCount($count_id);

                $this->db->query("UPDATE " . DB_PREFIX . "inventory_sheet_item SET
                    count_id = '" . (int)$count_id . "'
                    WHERE sheet_item_id = '" . (int)$item['sheet_item_id'] . "'");
            }
        }

        $this->db->query("UPDATE " . DB_PREFIX . "inventory_sheet SET
            status = 'completed',
            completed_by = '" . (int)($this->session->data['user_id'] ?? 0) . "',
            completed_at = NOW()
            WHERE sheet_id = '" . (int)$sheet_id . "'");

        return true;
    }

    public function updateProductCategories($product_id, $categories) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "'");

        foreach ($categories as $category_id) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$category_id . "'");
        }
    }

    public function getProductTags($filter_data = array()) {
        $sql = "SELECT DISTINCT tag FROM " . DB_PREFIX . "product_description WHERE 1";

        if (!empty($filter_data['filter_name'])) {
            $filter_tags = explode(',', $this->db->escape($filter_data['filter_name']));
            $tag_conditions = array();

            foreach ($filter_tags as $filter_tag) {
                $tag_conditions[] = "tag LIKE '%" . $this->db->escape($filter_tag) . "%'";
            }

            if (!empty($tag_conditions)) {
                $sql .= " AND (" . implode(" OR ", $tag_conditions) . ")";
            }
        }

        $sql .= " ORDER BY tag ASC LIMIT " . (int)$filter_data['start'] . "," . (int)$filter_data['limit'];

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function editProduct($product_id, $data) {
$this->db->query("UPDATE " . DB_PREFIX . "product SET model = '" . $this->db->escape($data['model']) . "', sku = '" . $this->db->escape($data['sku']) . "', upc = '" . $this->db->escape($data['upc']) . "', ean = '" . $this->db->escape($data['ean']) . "', jan = '" . $this->db->escape($data['jan']) . "', isbn = '" . $this->db->escape($data['isbn']) . "', mpn = '" . $this->db->escape($data['mpn']) . "', location = '" . $this->db->escape($data['location']) . "', quantity = '" . (int)$data['quantity'] . "', minimum = '" . (int)$data['minimum'] . "', subtract = '" . (int)$data['subtract'] . "', stock_status_id = '" . (int)$data['stock_status_id'] . "', date_available = '" . $this->db->escape($data['date_available']) . "', manufacturer_id = '" . (int)$data['manufacturer_id'] . "', shipping = '" . (int)$data['shipping'] . "', price = '" . (float)$data['price'] . "', points = '" . (int)$data['points'] . "', weight = '" . (float)$data['weight'] . "', weight_class_id = '" . (int)$data['weight_class_id'] . "', length = '" . (float)$data['length'] . "', width = '" . (float)$data['width'] . "', height = '" . (float)$data['height'] . "', length_class_id = '" . (int)$data['length_class_id'] . "', status = '" . (int)$data['status'] . "', tax_class_id = '" . (int)$data['tax_class_id'] . "', sort_order = '" . (int)$data['sort_order'] . "', date_modified = NOW() WHERE product_id = '" . (int)$product_id . "'");

        if (isset($data['image'])) {
            $this->db->query("UPDATE " . DB_PREFIX . "product SET image = '" . $this->db->escape($data['image']) . "' WHERE product_id = '" . (int)$product_id . "'");
        }

        $this->deleteProductRecommendations($product_id, 'upsell');
        if (isset($this->request->post['product_upsell'])) {
            foreach ($this->request->post['product_upsell'] as $upsell) {
                $this->addProductRecommendation($product_id, $upsell, 'upsell');
            }
        }

        $this->deleteProductRecommendations($product_id, 'cross_sell');
        if (isset($this->request->post['product_cross_sell'])) {
            foreach ($this->request->post['product_cross_sell'] as $cross_sell) {
                $this->addProductRecommendation($product_id, $cross_sell, 'cross_sell');
            }
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "product_unit WHERE product_id = '" . (int)$product_id . "'");
        if (isset($data['product_unit'])) {
            foreach ($data['product_unit'] as $unit) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_unit SET
                    product_id = '" . (int)$product_id . "',
                    unit_id = '" . (int)$unit['unit_id'] . "',
                    unit_type = '" . $this->db->escape($unit['unit_type']) . "',
                    conversion_factor = '" . (float)$unit['conversion_factor'] . "'");
            }
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "product_inventory WHERE product_id = '" . (int)$product_id . "'");
        if (isset($data['product_inventory'])) {
            foreach ($data['product_inventory'] as $inventory) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_inventory SET
                    product_id = '" . (int)$product_id . "',
                    branch_id = '" . (int)$inventory['branch_id'] . "',
                    unit_id = '" . (int)$inventory['unit_id'] . "',
                    quantity = '" . (float)$inventory['quantity'] . "',
                    quantity_available = '" . (float)$inventory['quantity_available'] . "'");
            }
        }

        if (isset($data['product_inventory'])) {
            $this->updateInventoryWithMovements($product_id, $data['product_inventory']);
        }

        $oldPricingRows = $this->db->query("
            SELECT *
            FROM " . DB_PREFIX . "product_pricing
            WHERE product_id = '" . (int)$product_id . "'
        ")->rows;

        $changes = [];
        foreach ($data['product_pricing'] as $newRow) {
            $unit_id = (int)$newRow['unit_id'];
            $oldRow = array_filter($oldPricingRows, function($r) use ($unit_id) {
                return $r['unit_id'] == $unit_id;
            });

            if ($oldRow) {
                $oldRow = reset($oldRow);

                if ((float)$oldRow['base_price']       !== (float)$newRow['base_price'] ||
                    (float)$oldRow['special_price']    !== (float)$newRow['special_price'] ||
                    (float)$oldRow['wholesale_price']  !== (float)$newRow['wholesale_price'] ||
                    (float)$oldRow['custom_price']     !== (float)$newRow['custom_price'] ||
                    (float)$oldRow['half_wholesale_price'] !== (float)$newRow['half_wholesale_price'])
                {
                    $changes[] = $unit_id;
                }
            } else {
                $changes[] = $unit_id;
            }
        }

        if (!empty($changes)) {
            $this->db->query("
                DELETE FROM " . DB_PREFIX . "cart
                WHERE product_id = '" . (int)$product_id . "'
                   OR group_id   = '" . (int)$product_id . "'
            ");
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "product_pricing WHERE product_id = '" . (int)$product_id . "'");
        if (isset($data['product_pricing'])) {
            foreach ($data['product_pricing'] as $pricing) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_pricing SET
                    product_id = '" . (int)$product_id . "',
                    unit_id = '" . (int)$pricing['unit_id'] . "',
                    base_price = '" . (float)$pricing['base_price'] . "',
                    special_price = '" . (float)$pricing['special_price'] . "',
                    wholesale_price = '" . (float)$pricing['wholesale_price'] . "',
                    custom_price = '" . (float)$pricing['custom_price'] . "',
                    half_wholesale_price = '" . (float)$pricing['half_wholesale_price'] . "'");
            }
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "product_description WHERE product_id = '" . (int)$product_id . "'");

        foreach ($data['product_description'] as $language_id => $value) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "product_description SET product_id = '" . (int)$product_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "', description = '" . $this->db->escape($value['description']) . "', tag = '" . $this->db->escape($value['tag']) . "', meta_title = '" . $this->db->escape($value['meta_title']) . "', meta_description = '" . $this->db->escape($value['meta_description']) . "', meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "'");
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_store WHERE product_id = '" . (int)$product_id . "'");

        if (isset($data['product_store'])) {
            foreach ($data['product_store'] as $store_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_store SET product_id = '" . (int)$product_id . "', store_id = '" . (int)$store_id . "'");
            }
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "'");

        if (!empty($data['product_attribute'])) {
            foreach ($data['product_attribute'] as $product_attribute) {
                if ($product_attribute['attribute_id']) {
                    $this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' AND attribute_id = '" . (int)$product_attribute['attribute_id'] . "'");

                    foreach ($product_attribute['product_attribute_description'] as $language_id => $product_attribute_description) {
                        $this->db->query("INSERT INTO " . DB_PREFIX . "product_attribute SET product_id = '" . (int)$product_id . "', attribute_id = '" . (int)$product_attribute['attribute_id'] . "', language_id = '" . (int)$language_id . "', text = '" .  $this->db->escape($product_attribute_description['text']) . "'");
                    }
                }
            }
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "product_option WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_option_value WHERE product_id = '" . (int)$product_id . "'");

        if (isset($data['product_option'])) {
            foreach ($data['product_option'] as $product_option) {
                if ($product_option['type'] == 'select' || $product_option['type'] == 'radio' || $product_option['type'] == 'checkbox' || $product_option['type'] == 'image') {
                    if (isset($product_option['product_option_value'])) {
                        $this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_option_id = '" . (int)$product_option['product_option_id'] . "', product_id = '" . (int)$product_id . "', unit_id = '" . (int)$product_option['unit_id'] . "', option_id = '" . (int)$product_option['option_id'] . "', required = '" . (int)$product_option['required'] . "'");

                        $product_option_id = $this->db->getLastId();

                        foreach ($product_option['product_option_value'] as $product_option_value) {
                            $this->db->query("INSERT INTO " . DB_PREFIX . "product_option_value SET product_option_value_id = '" . (int)$product_option_value['product_option_value_id'] . "', product_option_id = '" . (int)$product_option_id . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', option_value_id = '" . (int)$product_option_value['option_value_id'] . "', quantity = '" . (int)$product_option_value['quantity'] . "', subtract = '" . (int)$product_option_value['subtract'] . "', price = '" . (float)$product_option_value['price'] . "', price_prefix = '" . $this->db->escape($product_option_value['price_prefix']) . "', points = '" . (int)$product_option_value['points'] . "', points_prefix = '" . $this->db->escape($product_option_value['points_prefix']) . "', weight = '" . (float)$product_option_value['weight'] . "', weight_prefix = '" . $this->db->escape($product_option_value['weight_prefix']) . "'");
                        }
                    }
                } else {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_option_id = '" . (int)$product_option['product_option_id'] . "', product_id = '" . (int)$product_id . "', unit_id = '" . (int)$product_option['unit_id'] . "', option_id = '" . (int)$product_option['option_id'] . "', value = '" . $this->db->escape($product_option['value']) . "', required = '" . (int)$product_option['required'] . "'");
                }
            }
        }

        $this->db->query("DELETE FROM `" . DB_PREFIX . "product_recurring` WHERE product_id = " . (int)$product_id);

        if (isset($data['product_recurring'])) {
            foreach ($data['product_recurring'] as $product_recurring) {
                $query = $this->db->query("SELECT `product_id` FROM `" . DB_PREFIX . "product_recurring` WHERE `product_id` = '" . (int)$product_id . "' AND `customer_group_id` = '" . (int)$product_recurring['customer_group_id'] . "' AND `recurring_id` = '" . (int)$product_recurring['recurring_id'] . "'");

                if (!$query->num_rows) {
                    $this->db->query("INSERT INTO `" . DB_PREFIX . "product_recurring` SET `product_id` = '" . (int)$product_id . "', `customer_group_id` = '" . (int)$product_recurring['customer_group_id'] . "', `recurring_id` = '" . (int)$product_recurring['recurring_id'] . "'");
                }
            }
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int)$product_id . "'");

        if (isset($data['product_image'])) {
            foreach ($data['product_image'] as $product_image) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_image SET product_id = '" . (int)$product_id . "', image = '" . $this->db->escape($product_image['image']) . "', sort_order = '" . (int)$product_image['sort_order'] . "'");
            }
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "'");

        if (isset($data['product_category'])) {
            foreach ($data['product_category'] as $category_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$category_id . "'");
            }
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "product_filter WHERE product_id = '" . (int)$product_id . "'");

        if (isset($data['product_filter'])) {
            foreach ($data['product_filter'] as $filter_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_filter SET product_id = '" . (int)$product_id . "', filter_id = '" . (int)$filter_id . "'");
            }
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE related_id = '" . (int)$product_id . "'");

        if (isset($data['product_related'])) {
            foreach ($data['product_related'] as $related_id) {
                $this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$product_id . "' AND related_id = '" . (int)$related_id . "'");
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_related SET product_id = '" . (int)$product_id . "', related_id = '" . (int)$related_id . "'");
                $this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$related_id . "' AND related_id = '" . (int)$product_id . "'");
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_related SET product_id = '" . (int)$related_id . "', related_id = '" . (int)$product_id . "'");
            }
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "product_reward WHERE product_id = '" . (int)$product_id . "'");

        if (isset($data['product_reward'])) {
            foreach ($data['product_reward'] as $customer_group_id => $value) {
                if ((int)$value['points'] > 0) {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "product_reward SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$customer_group_id . "', points = '" . (int)$value['points'] . "'");
                }
            }
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'product_id=" . (int)$product_id . "'");

        if (isset($data['product_seo_url'])) {
            foreach ($data['product_seo_url']as $store_id => $language) {
                foreach ($language as $language_id => $keyword) {
                    if (!empty($keyword)) {
                        $this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET store_id = '" . (int)$store_id . "', language_id = '" . (int)$language_id . "', query = 'product_id=" . (int)$product_id . "', keyword = '" . $this->db->escape($keyword) . "'");
                    }
                }
            }
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_layout WHERE product_id = '" . (int)$product_id . "'");

        if (isset($data['product_layout'])) {
            foreach ($data['product_layout'] as $store_id => $layout_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_layout SET product_id = '" . (int)$product_id . "', store_id = '" . (int)$store_id . "', layout_id = '" . (int)$layout_id . "'");
            }
        }

        if (isset($data['product_barcode'])) {
            $this->editProductBarcodes($product_id, $data['product_barcode']);
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "product_quantity_discounts WHERE product_id = '" . (int)$product_id . "'");
        if (isset($data['product_discount'])) {
            foreach ($data['product_discount'] as $discount) {
                $this->addProductQuantityDiscount($product_id, array(
                    'name' => $discount['name'],
                    'type' => $discount['type'],
                    'buy_quantity' => $discount['buy_quantity'],
                    'get_quantity' => $discount['get_quantity'],
                    'discount_type' => $discount['discount_type'],
                    'discount_value' => $discount['discount_value'],
                    'status' => $discount['status'],
                    'unit_id' => isset($discount['unit_id']) ? $discount['unit_id'] : 0,
                    'date_start' => isset($discount['date_start']) ? $discount['date_start'] : null,
                    'date_end' => isset($discount['date_end']) ? $discount['date_end'] : null,
                    'notes' => isset($discount['notes']) ? $discount['notes'] : null
                ));
            }
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "product_bundle WHERE product_id = '" . (int)$product_id . "'");
        if (isset($data['product_bundle'])) {
            foreach ($data['product_bundle'] as $bundle) {
                $bundle_id = $this->addProductBundle($product_id, array(
                    'name' => $bundle['name'],
                    'discount_type' => $bundle['discount_type'],
                    'discount_value' => $bundle['discount_value'],
                    'status' => $bundle['status']
                ));

                if (isset($bundle['bundle_item'])) {
                    foreach ($bundle['bundle_item'] as $item) {
                        $this->db->query("INSERT INTO " . DB_PREFIX . "product_bundle_item SET
                            bundle_id = '" . (int)$bundle_id . "',
                            product_id = '" . (int)$item['product_id'] . "',
                            quantity = '" . (int)$item['quantity'] . "',
                            unit_id = '" . (int)$item['unit_id'] . "',
                            is_free = '" . (isset($item['is_free']) ? (int)$item['is_free'] : 0) . "'");
                    }
                }
            }
        }

        $this->cache->delete('product');
    }

    public function copyProduct($product_id) {
        $query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "product p WHERE p.product_id = '" . (int)$product_id . "'");

        if ($query->num_rows) {
            $data = $query->row;

            $data['sku'] = '';
            $data['upc'] = '';
            $data['viewed'] = '0';
            $data['keyword'] = '';
            $data['status'] = '0';

            $data['product_attribute'] = $this->getProductAttributes($product_id);
            $data['product_description'] = $this->getProductDescriptions($product_id);
            $data['product_filter'] = $this->getProductFilters($product_id);
            $data['product_image'] = $this->getProductImages($product_id);
            $data['product_option'] = $this->getProductOptions($product_id);
            $data['product_related'] = $this->getProductRelated($product_id);
            $data['product_reward'] = $this->getProductRewards($product_id);
            $data['product_category'] = $this->getProductCategories($product_id);
            $data['product_layout'] = $this->getProductLayouts($product_id);
            $data['product_store'] = $this->getProductStores($product_id);
            $data['product_units'] = $this->getProductUnits($product_id);
            $data['product_inventory'] = $this->getProductInventory($product_id);
            $data['product_pricing'] = $this->getProductPricing($product_id);

            $this->addProduct($data);
        }
    }

    public function deleteProduct($product_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "product WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_description WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_filter WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_option WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_option_value WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE related_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_reward WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_layout WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_store WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_recurring WHERE product_id = " . (int)$product_id);
        $this->db->query("DELETE FROM " . DB_PREFIX . "review WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'product_id=" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "coupon_product WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_unit WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_inventory WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_pricing WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_movement WHERE product_id = '" . (int)$product_id . "'");

        $this->cache->delete('product');
    }

    public function getProduct($product_id) {
        $query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_egs egs ON (p.product_id = egs.product_id)  WHERE p.product_id = '" . (int)$product_id . "' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

        if ($query->num_rows) {
            $product_data = $query->row;
            $product_data['units'] = $this->getProductUnits($product_id);
            $product_data['inventory'] = $this->getProductInventory($product_id);
            $product_data['pricing'] = $this->getProductPricing($product_id);
            $product_data['product_quantity_discounts'] = $this->getProductQuantityDiscounts($product_id);

            return $product_data;
        } else {
            return false;
        }
    }

    private function addInitialStock($product_id, $inventory_data) {
        foreach ($inventory_data as $inventory) {
            if ($inventory['quantity'] > 0) {
                $this->addStockMovement([
                    'product_id' => $product_id,
                    'type' => 'purchase',
                    'quantity' => $inventory['quantity'],
                    'unit_id' => $inventory['unit_id'],
                    'reference' => 'الرصيد الافتتاحي',
                    'branch_id' => $inventory['branch_id']
                ]);
            }
        }
    }


     private function updateInventoryWithMovements($product_id, $new_inventory) {
    // التحقق من وجود دالة addStockMovement
    if (!method_exists($this, 'addStockMovement')) {
        error_log("Method addStockMovement not found. Using addInventoryMovement instead.");
        $method_name = 'addInventoryMovement';
    } else {
        $method_name = 'addStockMovement';
    }

    // جلب المخزون القديم
    $old_inventory = $this->getProductInventory($product_id);

    // تحويل المخزون القديم إلى مصفوفة أسهل للمقارنة
    $old_inv_map = [];
    foreach ($old_inventory as $old_item) {
        $key = $old_item['branch_id'] . '_' . $old_item['unit_id'];
        $old_inv_map[$key] = $old_item;
    }

    // المقارنة وتسجيل الحركات
    foreach ($new_inventory as $new_item) {
        if (empty($new_item['branch_id']) || empty($new_item['unit_id'])) {
            continue; // تخطي العناصر الفارغة
        }

        $key = $new_item['branch_id'] . '_' . $new_item['unit_id'];

        if (isset($old_inv_map[$key])) {
            // وجدنا نفس الوحدة في نفس الفرع
            $old_qty = (float)$old_inv_map[$key]['quantity'];
            $new_qty = (float)$new_item['quantity'];
            $diff = $new_qty - $old_qty;

            if (abs($diff) >= 0.0001) {
                // هناك فرق يجب تسجيله كحركة
                $movement_data = [
                    'product_id' => $product_id,
                    'type' => ($diff > 0) ? 'adjustment_increase' : 'adjustment_decrease',
                    'quantity' => abs($diff),
                    'unit_id' => $new_item['unit_id'],
                    'branch_id' => $new_item['branch_id'],
                    'reference' => 'تعديل المخزون',
                    'user_id' => isset($this->session->data['user_id']) ? $this->session->data['user_id'] : 0
                ];

                $this->$method_name($movement_data);
            }
        } else if ((float)$new_item['quantity'] >= 0.0001) {
            // وحدة جديدة أو فرع جديد
            $movement_data = [
                'product_id' => $product_id,
                'type' => 'adjustment_increase',
                'quantity' => $new_item['quantity'],
                'unit_id' => $new_item['unit_id'],
                'branch_id' => $new_item['branch_id'],
                'reference' => 'إضافة وحدة/فرع جديد',
                'user_id' => isset($this->session->data['user_id']) ? $this->session->data['user_id'] : 0
            ];

            $this->$method_name($movement_data);
        }
    }

    return true;
}
public function getUnits() {
    $query = $this->db->query("SELECT unit_id, code, CONCAT(desc_en, ' - ', desc_ar) AS unit_name FROM " . DB_PREFIX . "unit ORDER BY desc_en ASC");
    return $query->rows;
}

public function getUnit($unit_id) {
    $query = $this->db->query("SELECT unit_id, code, CONCAT(desc_en, ' - ', desc_ar) AS unit_name FROM " . DB_PREFIX . "unit WHERE unit_id = '" . (int)$unit_id . "'");
    return $query->row;
}

public function getProducts($data = array()) {
    $sql = "SELECT DISTINCT p.*, pd.name, egs.*,
                COALESCE((SELECT SUM(pi.quantity) FROM " . DB_PREFIX . "product_inventory pi WHERE pi.product_id = p.product_id), 0) as quantity,
                COALESCE((SELECT pp.base_price FROM " . DB_PREFIX . "product_pricing pp
                    WHERE pp.product_id = p.product_id AND pp.unit_id =
                        (SELECT pu.unit_id FROM " . DB_PREFIX . "product_unit pu
                         WHERE pu.product_id = p.product_id AND pu.unit_type = 'base' LIMIT 1)
                    LIMIT 1), 0.0) as price
            FROM " . DB_PREFIX . "product p
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "product_egs egs ON (p.product_id = egs.product_id)
            LEFT JOIN " . DB_PREFIX . "product_unit pu ON (p.product_id = pu.product_id)
            LEFT JOIN " . DB_PREFIX . "product_pricing pp ON (p.product_id = pp.product_id AND pu.unit_id = pp.unit_id)";

    // Add conditional JOIN statements
    if (!empty($data['filter_category'])) {
        $sql .= " LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id)";
    }

    if (!empty($data['filter_filter'])) {
        $sql .= " LEFT JOIN " . DB_PREFIX . "product_filter pf ON (p.product_id = pf.product_id)";
    }

    // Base WHERE clause
    $where = "WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

    // Handle filter conditions
    if (!empty($data['filter_name'])) {
        $where .= " AND pd.name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
    }

    if (!empty($data['filter_model'])) {
        $where .= " AND p.model LIKE '" . $this->db->escape($data['filter_model']) . "%'";
    }

    if (!empty($data['filter_price'])) {
        $where .= " AND p.price LIKE '" . $this->db->escape($data['filter_price']) . "%'";
    }

    if (!empty($data['filter_quantity'])) {
        $where .= " AND p.quantity = '" . (int)$data['filter_quantity'] . "'";
    }

    if (isset($data['filter_status']) && $data['filter_status'] !== '') {
        $where .= " AND p.status = '" . (int)$data['filter_status'] . "'";
    }

    if (!empty($data['filter_category'])) {
        $where .= " AND p2c.category_id = '" . (int)$data['filter_category'] . "'";
    }

    if (!empty($data['filter_unit'])) {
        $where .= " AND pu.unit_id = '" . (int)$data['filter_unit'] . "'";
    }

    if (!empty($data['filter_filter'])) {
        $implode = array();
        $filters = explode(',', $data['filter_filter']);

        foreach ($filters as $filter_id) {
            $implode[] = (int)$filter_id;
        }

        $where .= " AND pf.filter_id IN (" . implode(',', $implode) . ")";
    }

    if (isset($data['filter_has_image']) && $data['filter_has_image'] !== '') {
        if ($data['filter_has_image'] == '1') {
            $where .= " AND p.image != '' AND p.image IS NOT NULL";
        } else {
            $where .= " AND (p.image = '' OR p.image IS NULL)";
        }
    }

    if (isset($data['filter_quantity_min']) && $data['filter_quantity_min'] !== '') {
        $where .= " AND p.quantity >= '" . (float)$data['filter_quantity_min'] . "'";
    }

    if (isset($data['filter_quantity_max']) && $data['filter_quantity_max'] !== '') {
        $where .= " AND p.quantity <= '" . (float)$data['filter_quantity_max'] . "'";
    }

    // Append WHERE clause to SQL
    $sql .= $where;

    // Handle sorting
    $sort_data = array(
        'pd.name',
        'p.model',
        'pp.base_price',
        'quantity',
        'p.status',
        'p.sort_order'
    );

    if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
        $sql .= " ORDER BY " . $data['sort'];
    } else {
        $sql .= " ORDER BY pd.name";
    }

    if (isset($data['order']) && ($data['order'] == 'DESC')) {
        $sql .= " DESC";
    } else {
        $sql .= " ASC";
    }

    // Handle pagination
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
public function getProductSpecials($product_id) {
    $query = $this->db->query("SELECT pp.unit_id, pp.special_price as price, CONCAT(u.desc_en, ' - ', u.desc_ar) AS unit_name
                               FROM " . DB_PREFIX . "product_pricing pp
                               LEFT JOIN " . DB_PREFIX . "unit u ON (pp.unit_id = u.unit_id)
                               WHERE pp.product_id = '" . (int)$product_id . "'
                               AND pp.special_price IS NOT NULL
                               AND pp.special_price > 0");
    return $query->rows;
}
	public function getProductsByCategoryId($category_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p2c.category_id = '" . (int)$category_id . "' ORDER BY pd.name ASC");

		return $query->rows;
	}

	public function getProductDescriptions($product_id) {
		$product_description_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_description WHERE product_id = '" . (int)$product_id . "'");

		foreach ($query->rows as $result) {
			$product_description_data[$result['language_id']] = array(
				'name'             => $result['name'],
				'description'      => $result['description'],
				'meta_title'       => $result['meta_title'],
				'meta_description' => $result['meta_description'],
				'meta_keyword'     => $result['meta_keyword'],
				'tag'              => $result['tag']
			);
		}

		return $product_description_data;
	}

	public function getProductCategories($product_id) {
		$product_category_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "'");

		foreach ($query->rows as $result) {
			$product_category_data[] = $result['category_id'];
		}

		return $product_category_data;
	}

	public function getProductFilters($product_id) {
		$product_filter_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_filter WHERE product_id = '" . (int)$product_id . "'");

		foreach ($query->rows as $result) {
			$product_filter_data[] = $result['filter_id'];
		}

		return $product_filter_data;
	}

	public function getProductAttributes($product_id) {
		$product_attribute_data = array();

		$product_attribute_query = $this->db->query("SELECT attribute_id FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' GROUP BY attribute_id");

		foreach ($product_attribute_query->rows as $product_attribute) {
			$product_attribute_description_data = array();

			$product_attribute_description_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' AND attribute_id = '" . (int)$product_attribute['attribute_id'] . "'");

			foreach ($product_attribute_description_query->rows as $product_attribute_description) {
				$product_attribute_description_data[$product_attribute_description['language_id']] = array('text' => $product_attribute_description['text']);
			}

			$product_attribute_data[] = array(
				'attribute_id'                  => $product_attribute['attribute_id'],
				'product_attribute_description' => $product_attribute_description_data
			);
		}

		return $product_attribute_data;
	}

	public function getProductOptions($product_id) {
		$product_option_data = array();

		$product_option_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product_option` po LEFT JOIN `" . DB_PREFIX . "option` o ON (po.option_id = o.option_id) LEFT JOIN `" . DB_PREFIX . "option_description` od ON (o.option_id = od.option_id) WHERE po.product_id = '" . (int)$product_id . "' AND od.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY o.sort_order ASC");

		foreach ($product_option_query->rows as $product_option) {
			$product_option_value_data = array();

			$product_option_value_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "option_value ov ON(pov.option_value_id = ov.option_value_id) WHERE pov.product_option_id = '" . (int)$product_option['product_option_id'] . "' ORDER BY ov.sort_order ASC");

			foreach ($product_option_value_query->rows as $product_option_value) {
				$product_option_value_data[] = array(
					'product_option_value_id' => $product_option_value['product_option_value_id'],
					'option_value_id'         => $product_option_value['option_value_id'],
					'quantity'                => $product_option_value['quantity'],
					'subtract'                => $product_option_value['subtract'],
					'price'                   => $product_option_value['price'],
					'price_prefix'            => $product_option_value['price_prefix'],
					'points'                  => $product_option_value['points'],
					'points_prefix'           => $product_option_value['points_prefix'],
					'weight'                  => $product_option_value['weight'],
					'weight_prefix'           => $product_option_value['weight_prefix']
				);
			}

			$product_option_data[] = array(
				'product_option_id'    => $product_option['product_option_id'],
				'product_option_value' => $product_option_value_data,
				'option_id'            => $product_option['option_id'],
				'unit_id'            => $product_option['unit_id'],
				'name'                 => $product_option['name'],
				'type'                 => $product_option['type'],
				'value'                => $product_option['value'],
				'required'             => $product_option['required']
			);
		}

		return $product_option_data;
	}

	public function getProductOptionValue($product_id, $product_option_value_id) {
		$query = $this->db->query("SELECT pov.option_value_id, ovd.name, pov.quantity, pov.subtract, pov.price, pov.price_prefix, pov.points, pov.points_prefix, pov.weight, pov.weight_prefix FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "option_value ov ON (pov.option_value_id = ov.option_value_id) LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) WHERE pov.product_id = '" . (int)$product_id . "' AND pov.product_option_value_id = '" . (int)$product_option_value_id . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

		return $query->row;
	}

	public function getProductImages($product_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int)$product_id . "' ORDER BY sort_order ASC");

		return $query->rows;
	}

	public function getProductRewards($product_id) {
		$product_reward_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_reward WHERE product_id = '" . (int)$product_id . "'");

		foreach ($query->rows as $result) {
			$product_reward_data[$result['customer_group_id']] = array('points' => $result['points']);
		}

		return $product_reward_data;
	}


	public function getProductStores($product_id) {
		$product_store_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_store WHERE product_id = '" . (int)$product_id . "'");

		foreach ($query->rows as $result) {
			$product_store_data[] = $result['store_id'];
		}

		return $product_store_data;
	}

	public function getProductSeoUrls($product_id) {
		$product_seo_url_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE query = 'product_id=" . (int)$product_id . "'");

		foreach ($query->rows as $result) {
			$product_seo_url_data[$result['store_id']][$result['language_id']] = $result['keyword'];
		}

		return $product_seo_url_data;
	}

/**
 * إضافة حركة مخزون جديدة مع المعالجة الكاملة
 *
 * @param array $data بيانات الحركة
 * @return int|bool معرّف الحركة أو false في حالة الفشل
 */
public function addInventoryMovement($data) {
    try {
        // Validate required fields
        $required_fields = array('product_id', 'type', 'quantity', 'unit_id', 'branch_id');
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                throw new Exception("Missing required field for inventory movement: " . $field);
            }
        }

        // Validate movement type
        $valid_types = array(
            'purchase', 'sale', 'adjustment_increase', 'adjustment_decrease',
            'transfer_in', 'transfer_out', 'initial', 'return_in', 'return_out',
            'scrap', 'production', 'consumption'
        );

        if (!in_array($data['type'], $valid_types)) {
            throw new Exception("Invalid movement type: " . $data['type']);
        }

        // تحميل مدير المخزون
        $this->load->model('catalog/inventory_manager');

        // تحديد اتجاه الحركة (إضافة أو خصم)
        $quantity = $data['quantity'];
        if (in_array($data['type'], array('sale', 'adjustment_decrease', 'transfer_out', 'return_out', 'scrap', 'consumption'))) {
            $quantity = -$quantity;
        }

        // تحديد التكلفة
        $cost = isset($data['unit_cost']) ? $data['unit_cost'] : null;

        // تحديد الملاحظات
        $notes = isset($data['reference']) ? $data['reference'] : '';
        if (isset($data['notes'])) {
            $notes = $data['notes'];
        }

        // استخدام مدير المخزون لتحديث المخزون
        $result = $this->model_catalog_inventory_manager->updateStock(
            $data['product_id'],
            $quantity,
            $data['unit_id'],
            $data['branch_id'],
            $data['type'],
            isset($data['movement_reference_id']) ? $data['movement_reference_id'] : 0,
            $notes,
            $cost
        );

        if (!$result) {
            throw new Exception("Failed to update stock using inventory manager");
        }

        // الحصول على معلومات المخزون المحدثة
        $current_stock = $this->model_catalog_inventory_manager->getCurrentStock($data['product_id'], $data['branch_id']);

        return [
            'movement_id' => $result,
            'new_cost' => $current_stock['cost'],
            'quantity' => $current_stock['quantity'],
            'quantity_available' => $current_stock['quantity']
        ];
    } catch (Exception $e) {
        $this->log->write("Error in addInventoryMovement: " . $e->getMessage());
        return false;
    }
}


/**
 * تحديث لقطة المخزون الحالية
 *
 * @param int $product_id معرّف المنتج
 * @param int $branch_id معرّف الفرع
 * @param int $unit_id معرّف الوحدة
 * @param float $quantity الكمية الحالية
 * @param float $average_cost متوسط التكلفة الحالي
 * @param int $last_movement_id معرّف آخر حركة
 * @return bool نجاح أو فشل العملية
 */
public function updateInventorySnapshot($product_id, $branch_id, $unit_id, $quantity, $average_cost, $last_movement_id) {
    try {
        // التحقق مما إذا كانت هناك لقطة سابقة
        $query = $this->db->query("SELECT snapshot_id
            FROM " . DB_PREFIX . "branch_inventory_snapshot
            WHERE product_id = '" . (int)$product_id . "'
            AND branch_id = '" . (int)$branch_id . "'
            AND unit_id = '" . (int)$unit_id . "'");

        if ($query->num_rows) {
            // تحديث اللقطة
            $this->db->query("UPDATE " . DB_PREFIX . "branch_inventory_snapshot
                SET snapshot_date = NOW(),
                    quantity = '" . (float)$quantity . "',
                    average_cost = '" . (float)$average_cost . "',
                    last_movement_id = '" . (int)$last_movement_id . "'
                WHERE product_id = '" . (int)$product_id . "'
                AND branch_id = '" . (int)$branch_id . "'
                AND unit_id = '" . (int)$unit_id . "'");
        } else {
            // إنشاء لقطة جديدة
            $this->db->query("INSERT INTO " . DB_PREFIX . "branch_inventory_snapshot
                SET product_id = '" . (int)$product_id . "',
                    branch_id = '" . (int)$branch_id . "',
                    unit_id = '" . (int)$unit_id . "',
                    snapshot_date = NOW(),
                    quantity = '" . (float)$quantity . "',
                    average_cost = '" . (float)$average_cost . "',
                    last_movement_id = '" . (int)$last_movement_id . "'");
        }

        return true;
    } catch (Exception $e) {
        $this->log->write("Error in updateInventorySnapshot: " . $e->getMessage());
        return false;
    }
}


/**
 * تسجيل تاريخ تغيير التكلفة للمنتج
 *
 * @param int $product_id معرّف المنتج
 * @param int $unit_id معرّف الوحدة
 * @param int $branch_id معرّف الفرع
 * @param float $old_cost التكلفة القديمة
 * @param float $new_cost التكلفة الجديدة
 * @param string $change_reason سبب التغيير
 * @param string $notes ملاحظات إضافية
 * @return int|bool معرّف السجل أو false في حالة الفشل
 */
public function recordCostHistory($product_id, $unit_id, $branch_id, $old_cost, $new_cost, $change_reason, $notes = '') {
    try {
        $this->db->query("INSERT INTO " . DB_PREFIX . "inventory_cost_history SET
            product_id = '" . (int)$product_id . "',
            unit_id = '" . (int)$unit_id . "',
            branch_id = '" . (int)$branch_id . "',
            old_cost = '" . (float)$old_cost . "',
            new_cost = '" . (float)$new_cost . "',
            change_reason = '" . $this->db->escape($change_reason) . "',
            notes = '" . $this->db->escape($notes) . "',
            user_id = '" . (int)$this->user->getId() . "',
            date_added = NOW()");

        return $this->db->getLastId();
    } catch (Exception $e) {
        $this->log->write("Error in recordCostHistory: " . $e->getMessage());
        return false;
    }
}

	public function getProductLayouts($product_id) {
		$product_layout_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_layout WHERE product_id = '" . (int)$product_id . "'");

		foreach ($query->rows as $result) {
			$product_layout_data[$result['store_id']] = $result['layout_id'];
		}

		return $product_layout_data;
	}

	public function getProductRelated($product_id) {
		$product_related_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$product_id . "'");

		foreach ($query->rows as $result) {
			$product_related_data[] = $result['related_id'];
		}

		return $product_related_data;
	}

	public function getRecurrings($product_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product_recurring` WHERE product_id = '" . (int)$product_id . "'");

		return $query->rows;
	}

public function getTotalProducts($data = array()) {
    $sql = "SELECT COUNT(DISTINCT p.product_id) AS total
            FROM " . DB_PREFIX . "product p
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "product_unit pu ON (p.product_id = pu.product_id) ";

    // Add conditional JOINs
    if (!empty($data['filter_category'])) {
        $sql .= " LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id)";
    }

    if (!empty($data['filter_filter'])) {
        $sql .= " LEFT JOIN " . DB_PREFIX . "product_filter pf ON (p.product_id = pf.product_id)";
    }

    // Base WHERE clause
    $where = "WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

    // Add filter conditions
    if (!empty($data['filter_name'])) {
        $where .= " AND pd.name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
    }

    if (!empty($data['filter_model'])) {
        $where .= " AND p.model LIKE '" . $this->db->escape($data['filter_model']) . "%'";
    }

    if (isset($data['filter_price']) && $data['filter_price'] !== '') {
        $where .= " AND p.price LIKE '" . $this->db->escape($data['filter_price']) . "%'";
    }

    if (!empty($data['filter_unit'])) {
        $where .= " AND pu.unit_id = '" . (int)$data['filter_unit'] . "'";
    }

    if (isset($data['filter_has_image']) && $data['filter_has_image'] !== '') {
        if ($data['filter_has_image'] == '1') {
            $where .= " AND p.image != '' AND p.image IS NOT NULL";
        } else {
            $where .= " AND (p.image = '' OR p.image IS NULL)";
        }
    }

    if (!empty($data['filter_filter'])) {
        $implode = array();
        $filters = explode(',', $data['filter_filter']);

        foreach ($filters as $filter_id) {
            $implode[] = (int)$filter_id;
        }

        $where .= " AND pf.filter_id IN (" . implode(',', $implode) . ")";
    }

    if (isset($data['filter_quantity_min']) && $data['filter_quantity_min'] !== '') {
        $where .= " AND (SELECT SUM(pi.quantity) FROM " . DB_PREFIX . "product_inventory pi WHERE pi.product_id = p.product_id) >= '" . (float)$data['filter_quantity_min'] . "'";
    }

    if (isset($data['filter_quantity_max']) && $data['filter_quantity_max'] !== '') {
        $where .= " AND (SELECT SUM(pi.quantity) FROM " . DB_PREFIX . "product_inventory pi WHERE pi.product_id = p.product_id) <= '" . (float)$data['filter_quantity_max'] . "'";
    }

    if (isset($data['filter_quantity']) && $data['filter_quantity'] !== '') {
        $where .= " AND p.quantity = '" . (int)$data['filter_quantity'] . "'";
    }

    if (isset($data['filter_status']) && $data['filter_status'] !== '') {
        $where .= " AND p.status = '" . (int)$data['filter_status'] . "'";
    }

    if (!empty($data['filter_category'])) {
        $where .= " AND p2c.category_id = '" . (int)$data['filter_category'] . "'";
    }

    // Combine SQL and WHERE clause
    $sql .= $where;

    $query = $this->db->query($sql);
    return $query->row['total'];
}



	public function getTotalProductsByTaxClassId($tax_class_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "product WHERE tax_class_id = '" . (int)$tax_class_id . "'");

		return $query->row['total'];
	}

	public function getTotalProductsByStockStatusId($stock_status_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "product WHERE stock_status_id = '" . (int)$stock_status_id . "'");

		return $query->row['total'];
	}

	public function getTotalProductsByWeightClassId($weight_class_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "product WHERE weight_class_id = '" . (int)$weight_class_id . "'");

		return $query->row['total'];
	}

	public function getTotalProductsByLengthClassId($length_class_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "product WHERE length_class_id = '" . (int)$length_class_id . "'");

		return $query->row['total'];
	}


	public function getTotalProductsByManufacturerId($manufacturer_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "product WHERE manufacturer_id = '" . (int)$manufacturer_id . "'");

		return $query->row['total'];
	}

	public function getTotalProductsByAttributeId($attribute_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "product_attribute WHERE attribute_id = '" . (int)$attribute_id . "'");

		return $query->row['total'];
	}

	public function getTotalProductsByOptionId($option_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "product_option WHERE option_id = '" . (int)$option_id . "'");

		return $query->row['total'];
	}

	public function getTotalProductsByProfileId($recurring_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "product_recurring WHERE recurring_id = '" . (int)$recurring_id . "'");

		return $query->row['total'];
	}

	public function getTotalProductsByLayoutId($layout_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "product_to_layout WHERE layout_id = '" . (int)$layout_id . "'");

		return $query->row['total'];
	}

    /**
     * Get inventory for a specific product, branch, and unit
     *
     * @param int $product_id
     * @param int $branch_id
     * @param int $unit_id
     * @return array|bool
     */
    public function getProductInventoryItem($product_id, $branch_id, $unit_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_inventory
            WHERE product_id = '" . (int)$product_id . "'
            AND branch_id = '" . (int)$branch_id . "'
            AND unit_id = '" . (int)$unit_id . "'");

        return $query->row;
    }

    /**
     * Get all inventory items for a product
     *
     * @param int $product_id
     * @param array $data optional filter data
     * @return array
     */
    public function getProductInventory($product_id, $data = array()) {
        $sql = "SELECT pi.*,
                    b.name AS branch_name,
                    u.desc_en AS unit_name,
                    pi.average_cost,
                    (pi.quantity * pi.average_cost) AS total_value
                FROM " . DB_PREFIX . "product_inventory pi
                LEFT JOIN " . DB_PREFIX . "branch b ON (pi.branch_id = b.branch_id)
                LEFT JOIN " . DB_PREFIX . "unit u ON (pi.unit_id = u.unit_id)
                WHERE pi.product_id = '" . (int)$product_id . "'";

        // Apply filters if provided
        if (isset($data['filter_branch_id']) && $data['filter_branch_id'] > 0) {
            $sql .= " AND pi.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }

        if (isset($data['filter_unit_id']) && $data['filter_unit_id'] > 0) {
            $sql .= " AND pi.unit_id = '" . (int)$data['filter_unit_id'] . "'";
        }

        if (isset($data['filter_consignment'])) {
            $sql .= " AND pi.is_consignment = '" . (int)$data['filter_consignment'] . "'";
        }

        $sql .= " ORDER BY b.name, u.desc_en";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    /**
     * Add inventory history record
     *
     * @param int $product_id
     * @param int $branch_id
     * @param int $unit_id
     * @param string $movement_type
     * @param int $movement_id
     * @param float $quantity
     * @param float $cost
     * @param int $user_id
     * @return int
     */
    public function addInventoryHistory($product_id, $branch_id, $unit_id, $movement_type, $movement_id, $quantity, $cost, $user_id) {
        // الحصول على معلومات المخزون بعد الحركة
        $query = $this->db->query("SELECT quantity, quantity_available, average_cost
                                 FROM " . DB_PREFIX . "product_inventory
                                 WHERE product_id = '" . (int)$product_id . "'
                                 AND branch_id = '" . (int)$branch_id . "'
                                 AND unit_id = '" . (int)$unit_id . "'");

        if ($query->num_rows) {
            $post_quantity = $query->row['quantity'];
            $post_available = $query->row['quantity_available'];
            $post_cost = $query->row['average_cost'];
        } else {
            $post_quantity = 0;
            $post_available = 0;
            $post_cost = 0;
        }

        // إدراج سجل تاريخ المخزون
        $this->db->query("INSERT INTO " . DB_PREFIX . "product_inventory_history SET
            product_id = '" . (int)$product_id . "',
            branch_id = '" . (int)$branch_id . "',
            unit_id = '" . (int)$unit_id . "',
            movement_type = '" . $this->db->escape($movement_type) . "',
            movement_id = '" . (int)$movement_id . "',
            movement_quantity = '" . (float)$quantity . "',
            movement_cost = '" . (float)$cost . "',
            post_quantity = '" . (float)$post_quantity . "',
            post_available = '" . (float)$post_available . "',
            post_cost = '" . (float)$post_cost . "',
            user_id = '" . (int)$user_id . "',
            date_added = NOW()
        ");

        return $this->db->getLastId();
    }

    /**
     * Get inventory history for a product
     *
     * @param int $product_id
     * @param array $data optional filter data
     * @return array
     */
    public function getInventoryHistory($product_id, $data = array()) {
        $sql = "SELECT pih.*,
                    u.desc_en AS unit_name,
                    b.name AS branch_name,
                    CONCAT(u2.firstname, ' ', u2.lastname) AS user_name
                FROM " . DB_PREFIX . "product_inventory_history pih
                LEFT JOIN " . DB_PREFIX . "unit u ON (pih.unit_id = u.unit_id)
                LEFT JOIN " . DB_PREFIX . "branch b ON (pih.branch_id = b.branch_id)
                LEFT JOIN " . DB_PREFIX . "user u2 ON (pih.user_id = u2.user_id)
                WHERE pih.product_id = '" . (int)$product_id . "'";

        // Apply filters if provided
        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND pih.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }

        if (!empty($data['filter_unit_id'])) {
            $sql .= " AND pih.unit_id = '" . (int)$data['filter_unit_id'] . "'";
        }

        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(pih.date_added) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(pih.date_added) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $sql .= " ORDER BY pih.date_added DESC";

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . ", " . (int)$data['limit'];
        }

        $query = $this->db->query($sql);

        $history = $query->rows;

        // Add movement type text to each row
        foreach ($history as &$record) {
            $record['movement_type_text'] = $this->getMovementTypeText($record['movement_type']);
        }

        return $history;
    }

    /**
     * Get total number of inventory history records for a product
     *
     * @param int $product_id
     * @param array $data optional filter data
     * @return int
     */
    public function getTotalInventoryHistory($product_id, $data = array()) {
        $sql = "SELECT COUNT(*) AS total
                FROM " . DB_PREFIX . "product_inventory_history
                WHERE product_id = '" . (int)$product_id . "'";

        // Apply filters if provided
        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }

        if (!empty($data['filter_unit_id'])) {
            $sql .= " AND unit_id = '" . (int)$data['filter_unit_id'] . "'";
        }

        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(date_added) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(date_added) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    /**
     * Add cost history record
     *
     * @param int $product_id
     * @param int $branch_id
     * @param int $unit_id
     * @param float $old_cost
     * @param float $new_cost
     * @param string $reason
     * @param string $notes
     * @param int $user_id
     * @return int
     */
    public function addCostHistory($product_id, $branch_id, $unit_id, $old_cost, $new_cost, $reason, $notes, $user_id) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "product_cost_history SET
            product_id = '" . (int)$product_id . "',
            branch_id = '" . (int)$branch_id . "',
            unit_id = '" . (int)$unit_id . "',
            old_cost = '" . (float)$old_cost . "',
            new_cost = '" . (float)$new_cost . "',
            reason = '" . $this->db->escape($reason) . "',
            notes = '" . $this->db->escape($notes) . "',
            user_id = '" . (int)$user_id . "',
            date_added = NOW()
        ");

        return $this->db->getLastId();
    }

    /**
     * Get cost history for a product
     *
     * @param int $product_id
     * @param array $data optional filter data
     * @return array
     */
    public function getCostHistory($product_id, $data = array()) {
        $sql = "SELECT pch.*,
                    u.desc_en AS unit_name,
                    b.name AS branch_name,
                    CONCAT(u2.firstname, ' ', u2.lastname) AS user_name
                FROM " . DB_PREFIX . "product_cost_history pch
                LEFT JOIN " . DB_PREFIX . "unit u ON (pch.unit_id = u.unit_id)
                LEFT JOIN " . DB_PREFIX . "branch b ON (pch.branch_id = b.branch_id)
                LEFT JOIN " . DB_PREFIX . "user u2 ON (pch.user_id = u2.user_id)
                WHERE pch.product_id = '" . (int)$product_id . "'";

        // Apply filters if provided
        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND pch.branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }

        if (!empty($data['filter_unit_id'])) {
            $sql .= " AND pch.unit_id = '" . (int)$data['filter_unit_id'] . "'";
        }

        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(pch.date_added) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(pch.date_added) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $sql .= " ORDER BY pch.date_added DESC";

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . ", " . (int)$data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    /**
     * Get total number of cost history records for a product
     *
     * @param int $product_id
     * @param array $data optional filter data
     * @return int
     */
    public function getTotalCostHistory($product_id, $data = array()) {
        $sql = "SELECT COUNT(*) AS total
                FROM " . DB_PREFIX . "product_cost_history
                WHERE product_id = '" . (int)$product_id . "'";

        // Apply filters if provided
        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND branch_id = '" . (int)$data['filter_branch_id'] . "'";
        }

        if (!empty($data['filter_unit_id'])) {
            $sql .= " AND unit_id = '" . (int)$data['filter_unit_id'] . "'";
        }

        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(date_added) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(date_added) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }
}
