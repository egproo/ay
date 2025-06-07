<?php
class ModelAccountsInventoryValuation extends Model {
    public function getInventoryValuationData($date_start, $date_end) {
        $language_id = (int)$this->config->get('config_language_id');
        $currency_code = $this->config->get('config_currency');

        // أولاً: جلب قائمة المنتجات مع المتوسط والاسم
        $sql_products = "SELECT p.product_id, pd.name, p.average_cost
                         FROM " . DB_PREFIX . "product p
                         LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id AND pd.language_id=".(int)$language_id.")
                         WHERE p.status=1";

        $query_p = $this->db->query($sql_products);
        $products = $query_p->rows;

        // سنحتاج الحصول على الكمية الافتتاحية لكل منتج قبل date_start
        // والكمية الختامية عند date_end
        // والحركات خلال الفترة
        // نفترض جدول `cod_product_inventory` يسجل الرصيد الفعلي الحالي، 
        // وجدول `cod_product_movement` يسجل الحركات (type: purchase, sale, transfer_in, transfer_out)

        $results = [];
        $total_value = 0.0;

        foreach ($products as $prod) {
            $product_id = (int)$prod['product_id'];

            // الكمية الافتتاحية: هي الرصيد قبل date_start
            $sql_opening = "SELECT COALESCE(SUM(CASE WHEN m.type IN('purchase','transfer_in') THEN m.quantity 
                                                     WHEN m.type IN('sale','transfer_out') THEN -m.quantity ELSE 0 END),0) AS opening_qty
                            FROM " . DB_PREFIX . "product_movement m
                            WHERE m.product_id = '" . $product_id . "' 
                            AND m.date_added < '" . $this->db->escape($date_start) . "'";
            $q_open = $this->db->query($sql_opening);
            $opening_qty = (float)$q_open->row['opening_qty'];

            // الحركات خلال الفترة
            $sql_period = "SELECT 
                            COALESCE(SUM(CASE WHEN m.type='purchase' THEN m.quantity ELSE 0 END),0) as total_purchases,
                            COALESCE(SUM(CASE WHEN m.type='transfer_in' THEN m.quantity ELSE 0 END),0) as total_transfers_in,
                            COALESCE(SUM(CASE WHEN m.type='sale' THEN m.quantity ELSE 0 END),0) as total_sales,
                            COALESCE(SUM(CASE WHEN m.type='transfer_out' THEN m.quantity ELSE 0 END),0) as total_transfers_out
                           FROM " . DB_PREFIX . "product_movement m
                           WHERE m.product_id = '" . $product_id . "'
                           AND m.date_added BETWEEN '" . $this->db->escape($date_start) . "' AND '" . $this->db->escape($date_end) . "'";

            $q_period = $this->db->query($sql_period);
            $purchases = (float)$q_period->row['total_purchases'];
            $transfers_in = (float)$q_period->row['total_transfers_in'];
            $sales = (float)$q_period->row['total_sales'];
            $transfers_out = (float)$q_period->row['total_transfers_out'];

            // الكمية الختامية = الكمية الافتتاحية + الوارد - الصادر
            // الوارد = مشتريات + تحويلات داخلية
            // الصادر = مبيعات + تحويلات خارجية
            $in_qty = $purchases + $transfers_in;
            $out_qty = $sales + $transfers_out;
            $closing_qty = $opening_qty + $in_qty - $out_qty;

            // قيمة المخزون النهائي
            $average_cost = (float)$prod['average_cost'];
            $inventory_value = $closing_qty * $average_cost;
            $total_value += $inventory_value;

            $results[] = [
                'product_id' => $product_id,
                'product_name' => $prod['name'],
                'opening_qty' => $opening_qty,
                'in_qty' => $in_qty,
                'out_qty' => $out_qty,
                'closing_qty' => $closing_qty,
                'average_cost' => $this->currency->format($average_cost, $currency_code),
                'inventory_value' => $this->currency->format($inventory_value, $currency_code)
            ];
        }

        return [
            'products' => $results,
            'total_value' => $this->currency->format($total_value, $currency_code)
        ];
    }
}