<?php
class ModelAccountsPurchaseAnalysis extends Model {
    public function getPurchaseAnalysisData($date_start, $date_end) {
        $currency_code = $this->config->get('config_currency');

        // استعلام لجلب المشتريات لكل مورد
        // نفترض وجود عمود vendor_id في جدول أوامر الشراء (cod_purchase_order)، وعلاقة مع جدول supplier.
        $sql = "SELECT p.vendor_id, s.firstname, s.lastname, s.company, 
                       COUNT(p.po_id) AS po_count,
                       COALESCE(SUM(p.total_amount),0) AS total_purchases,
                       AVG(p.total_amount) AS avg_po
                FROM " . DB_PREFIX . "purchase_order p
                LEFT JOIN " . DB_PREFIX . "supplier s ON (p.vendor_id = s.supplier_id)
                WHERE p.order_date BETWEEN '" . $this->db->escape($date_start) . "' AND '" . $this->db->escape($date_end) . "'
                AND p.status IN ('approved','completed') 
                GROUP BY p.vendor_id
                ORDER BY total_purchases DESC";

        // نفترض أن حالات الشراء 'approved','completed' تعني تم تنفيذ المشتريات.
        // إذا أردت تخصيص حالات معينة يمكن "إضافة بالإعدادات".

        $query = $this->db->query($sql);
        $vendors = $query->rows;

        $results = [];
        $total_purchases = 0.0;

        foreach ($vendors as $v) {
            $name = trim($v['company']) != '' ? $v['company'] : ($v['firstname'] . ' ' . $v['lastname']);
            $purchase_amount = (float)$v['total_purchases'];
            $total_purchases += $purchase_amount;

            $results[] = [
                'vendor_id' => $v['vendor_id'],
                'vendor_name' => $name,
                'po_count' => (int)$v['po_count'],
                'total_purchases' => $this->currency->format($purchase_amount, $currency_code),
                'avg_po' => $this->currency->format((float)$v['avg_po'], $currency_code)
            ];
        }

        return [
            'vendors' => $results,
            'total_purchases' => $this->currency->format($total_purchases, $currency_code)
        ];
    }
}
