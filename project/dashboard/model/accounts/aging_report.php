<?php
class ModelAccountsAgingReport extends Model {
    public function getAgingReportData($date_end) {
        $currency_code = $this->config->get('config_currency');

        // الحالة المسددة بالكامل نفترضها 5 (يمكن ضبطها عبر الإعدادات لو أردت)
        $paid_status = 5;

        // استعلام لجلب الطلبات غير المسددة بالكامل حتى تاريخه
        // نفترض أن كل طلب له customer_id, total, date_added, order_status_id
        $sql = "SELECT o.customer_id, CONCAT(c.firstname, ' ', c.lastname) AS customer_name, o.total, o.date_added, DATEDIFF('" . $this->db->escape($date_end) . "', o.date_added) AS days_overdue
                FROM " . DB_PREFIX . "order o
                LEFT JOIN " . DB_PREFIX . "customer c ON (o.customer_id=c.customer_id)
                WHERE o.order_status_id <> '" . (int)$paid_status . "' 
                AND o.date_added <= '" . $this->db->escape($date_end) . "'";

        $query = $this->db->query($sql);
        $orders = $query->rows;

        // سنصنف حسب الشرائح: 0-30، 31-60، 61-90، >90
        $buckets = [
            '0-30' => 0.0,
            '31-60' => 0.0,
            '61-90' => 0.0,
            '>90' => 0.0
        ];

        // سنجمع أيضاً حسب العميل لإظهار تفصيل
        $customers_data = [];

        foreach ($orders as $o) {
            $days = (int)$o['days_overdue'];
            $amount = (float)$o['total'];
            $customer_name = $o['customer_name'] ?: 'Unknown';

            // تحديد الشريحة
            if ($days <= 30) {
                $bucket = '0-30';
            } elseif ($days <= 60) {
                $bucket = '31-60';
            } elseif ($days <= 90) {
                $bucket = '61-90';
            } else {
                $bucket = '>90';
            }

            $buckets[$bucket] += $amount;

            // تجميع بالعميل
            if (!isset($customers_data[$o['customer_id']])) {
                $customers_data[$o['customer_id']] = [
                    'customer_name' => $customer_name,
                    '0-30' => 0.0,
                    '31-60' => 0.0,
                    '61-90' => 0.0,
                    '>90' => 0.0
                ];
            }
            $customers_data[$o['customer_id']][$bucket] += $amount;
        }

        // تنسيق الأرقام
        foreach ($buckets as $k => &$v) {
            $v = $this->currency->format($v, $currency_code);
        }

        foreach ($customers_data as $cid => &$cdata) {
            foreach (['0-30','31-60','61-90','>90'] as $bk) {
                $cdata[$bk] = $this->currency->format($cdata[$bk], $currency_code);
            }
        }

        return [
            'buckets' => $buckets,
            'customers_data' => $customers_data
        ];
    }
}