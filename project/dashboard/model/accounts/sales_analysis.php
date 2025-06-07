<?php
class ModelAccountsSalesAnalysis extends Model {
    public function getSalesAnalysisData($date_start, $date_end) {
        $currency_code = $this->config->get('config_currency');

        // نفترض أن حالات الطلب المسددة أو المكتملة هي التي تحتسب في المبيعات. 
        // مثلاً نعتبر order_status_id=5 يعني الطلب مكتمل/مدفوع.
        $completed_status = 5;

        // استعلام لجلب المبيعات حسب المنتج
        $sql = "SELECT op.product_id, pd.name AS product_name,
                       SUM(op.quantity) AS total_quantity,
                       SUM(op.total) AS total_sales,
                       (SUM(op.total)/SUM(op.quantity)) AS avg_price
                FROM " . DB_PREFIX . "order_product op
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (op.product_id = pd.product_id AND pd.language_id=".(int)$this->config->get('config_language_id').")
                LEFT JOIN " . DB_PREFIX . "order o ON (op.order_id = o.order_id)
                WHERE o.order_status_id = '" . (int)$completed_status . "'
                AND o.date_added BETWEEN '" . $this->db->escape($date_start) . "' AND '" . $this->db->escape($date_end) . "'
                GROUP BY op.product_id
                ORDER BY total_sales DESC";

        $query = $this->db->query($sql);
        $products = $query->rows;

        $results = [];
        $total_sales = 0.0;

        foreach ($products as $p) {
            $sales_amount = (float)$p['total_sales'];
            $total_sales += $sales_amount;

            $results[] = [
                'product_id' => $p['product_id'],
                'product_name' => $p['product_name'],
                'total_quantity' => (int)$p['total_quantity'],
                'total_sales' => $this->currency->format($sales_amount, $currency_code),
                'avg_price' => $this->currency->format((float)$p['avg_price'], $currency_code)
            ];
        }

        return [
            'products' => $results,
            'total_sales' => $this->currency->format($total_sales, $currency_code)
        ];
    }
}
