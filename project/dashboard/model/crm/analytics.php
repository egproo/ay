<?php
class ModelCrmAnalytics extends Model {

    // مبيعات عبر الزمن: تعيد labels تمثل الأشهر مثلاً، وdata تمثل مجموع المبيعات
    public function getSalesOverTime($filter_data = array()) {
        // أمثلة بيانات وهمية:
        // يفضل استخدام filter_date_start و filter_date_end في شرط WHERE
        // مثال استعلام (تعديل بما يناسب جداولك):
        // SELECT DATE_FORMAT(order_date,'%Y-%m') as month, SUM(total) as total_sales
        // FROM cod_order WHERE order_date BETWEEN '...' AND '...'
        // GROUP BY month ORDER BY month

        $labels = array('Jan','Feb','Mar','Apr','May','Jun');
        $data = array(1200, 1500, 1800, 2000, 1750, 2200);

        return array(
            'labels' => $labels,
            'data'   => $data
        );
    }

    // مصادر العملاء المحتملين (Leads by Source)
    public function getLeadsBySource($filter_data = array()) {
        // مثال:
        // SELECT source, COUNT(*) as count FROM cod_lead WHERE date_added BETWEEN ... GROUP BY source
        // هنا بيانات وهمية:
        $labels = array('Website','Referral','Social Media','Email Campaign');
        $data   = array(50, 30, 20, 10);

        return array(
            'labels' => $labels,
            'data'   => $data
        );
    }

    // أفضل 5 عملاء من حيث الإنفاق
    public function getTopCustomers($filter_data = array()) {
        // مثال:
        // SELECT c.customer_id, CONCAT(c.firstname,' ',c.lastname) as name, SUM(o.total) as total_spent
        // FROM cod_order o
        // LEFT JOIN cod_customer c ON (o.customer_id = c.customer_id)
        // WHERE o.date_added BETWEEN ...
        // GROUP BY c.customer_id
        // ORDER BY total_spent DESC
        // LIMIT 5

        // بيانات وهمية:
        $customers = array(
            array('name' => 'John Doe', 'total_spent' => '5000'),
            array('name' => 'Jane Smith', 'total_spent' => '4500'),
            array('name' => 'ACME Corp', 'total_spent' => '4000'),
            array('name' => 'Global Inc', 'total_spent' => '3500'),
            array('name' => 'Mary Johnson', 'total_spent' => '3000'),
        );
        return $customers;
    }

    // Sales Funnel Data
    // نفترض مثلاً مراحل المبيعات: Leads -> Opportunities -> Deals Closed
    public function getSalesFunnelData($filter_data = array()) {
        // مثال:
        // COUNT(leads), COUNT(opportunities), COUNT(closed_deals)
        // بيانات وهمية:
        $stages = array('Leads','Opportunities','Closed Deals');
        $values = array(100, 40, 10);

        return array(
            'labels' => $stages,
            'values' => $values
        );
    }

    // Detailed Metrics
    // Metrics مختلفة: Conversion Rate, Average Order Value, etc.
    public function getDetailedMetrics($filter_data = array()) {
        // بيانات وهمية:
        $metrics = array(
            array('metric' => 'Conversion Rate', 'value' => '5.2%'),
            array('metric' => 'Average Order Value', 'value' => '$120'),
            array('metric' => 'Repeat Customers', 'value' => '30%'),
            array('metric' => 'New Leads This Period', 'value' => '60'),
            array('metric' => 'Closed Deals', 'value' => '15')
        );
        return $metrics;
    }
}
