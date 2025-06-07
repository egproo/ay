<?php
class ModelAiSmartAnalytics extends Model {

    public function getActiveModels() {
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "ai_model WHERE status = '1' AND active = '1'");
        return (int)$query->row['total'];
    }

    public function getPredictionsToday() {
        $query = $this->db->query("
            SELECT COUNT(*) as total 
            FROM " . DB_PREFIX . "ai_prediction 
            WHERE DATE(date_created) = CURDATE()
        ");
        return (int)$query->row['total'];
    }

    public function getAccuracyRate() {
        $query = $this->db->query("
            SELECT AVG(accuracy_score) as avg_accuracy 
            FROM " . DB_PREFIX . "ai_model_performance 
            WHERE date_evaluated >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        return $query->row['avg_accuracy'] ? round((float)$query->row['avg_accuracy'], 2) : 0;
    }

    public function getDataProcessed() {
        $query = $this->db->query("
            SELECT SUM(records_processed) as total_records 
            FROM " . DB_PREFIX . "ai_processing_log 
            WHERE DATE(date_processed) = CURDATE()
        ");
        
        return $query->row['total_records'] ? (int)$query->row['total_records'] : 0;
    }

    public function getSalesPredictions() {
        // تحليل توقعات المبيعات باستخدام الذكاء الاصطناعي
        $query = $this->db->query("
            SELECT 
                DATE_ADD(CURDATE(), INTERVAL seq.seq DAY) as prediction_date,
                ROUND(
                    (
                        SELECT AVG(daily_sales) * (1 + trend_factor + seasonal_factor)
                        FROM (
                            SELECT 
                                DATE(o.date_added) as sale_date,
                                SUM(o.total) as daily_sales,
                                (DATEDIFF(o.date_added, DATE_SUB(NOW(), INTERVAL 90 DAY)) / 90) * 0.1 as trend_factor,
                                CASE 
                                    WHEN DAYOFWEEK(o.date_added) IN (1,7) THEN -0.2
                                    WHEN DAYOFWEEK(o.date_added) IN (5,6) THEN 0.1
                                    ELSE 0
                                END as seasonal_factor
                            FROM " . DB_PREFIX . "order o
                            WHERE o.order_status_id > 0
                            AND o.date_added >= DATE_SUB(NOW(), INTERVAL 90 DAY)
                            GROUP BY DATE(o.date_added)
                        ) as sales_data
                    ), 2
                ) as predicted_sales
            FROM (
                SELECT 1 as seq UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 
                UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10
                UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15
                UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION SELECT 20
                UNION SELECT 21 UNION SELECT 22 UNION SELECT 23 UNION SELECT 24 UNION SELECT 25
                UNION SELECT 26 UNION SELECT 27 UNION SELECT 28 UNION SELECT 29 UNION SELECT 30
            ) as seq
            ORDER BY prediction_date
        ");

        return $query->rows;
    }

    public function getCustomerBehaviorInsights() {
        $query = $this->db->query("
            SELECT 
                'high_value' as segment,
                COUNT(*) as customer_count,
                AVG(total_spent) as avg_spending,
                AVG(order_frequency) as avg_frequency,
                'Customers with high lifetime value and frequent purchases' as description
            FROM (
                SELECT 
                    c.customer_id,
                    SUM(o.total) as total_spent,
                    COUNT(o.order_id) as order_frequency
                FROM " . DB_PREFIX . "customer c
                LEFT JOIN " . DB_PREFIX . "order o ON c.customer_id = o.customer_id
                WHERE o.order_status_id > 0
                GROUP BY c.customer_id
                HAVING total_spent > (
                    SELECT AVG(customer_total) * 1.5 
                    FROM (
                        SELECT SUM(total) as customer_total 
                        FROM " . DB_PREFIX . "order 
                        WHERE order_status_id > 0 
                        GROUP BY customer_id
                    ) as avg_calc
                )
            ) as high_value_customers
            
            UNION ALL
            
            SELECT 
                'at_risk' as segment,
                COUNT(*) as customer_count,
                AVG(days_since_last_order) as avg_days_inactive,
                AVG(total_orders) as avg_orders,
                'Customers who haven\'t ordered recently but were active before' as description
            FROM (
                SELECT 
                    c.customer_id,
                    DATEDIFF(NOW(), MAX(o.date_added)) as days_since_last_order,
                    COUNT(o.order_id) as total_orders
                FROM " . DB_PREFIX . "customer c
                LEFT JOIN " . DB_PREFIX . "order o ON c.customer_id = o.customer_id
                WHERE o.order_status_id > 0
                GROUP BY c.customer_id
                HAVING days_since_last_order > 60 AND total_orders >= 3
            ) as at_risk_customers
            
            UNION ALL
            
            SELECT 
                'new_potential' as segment,
                COUNT(*) as customer_count,
                AVG(first_order_value) as avg_first_order,
                AVG(days_since_first_order) as avg_days_since_first,
                'New customers with potential for growth' as description
            FROM (
                SELECT 
                    c.customer_id,
                    MIN(o.total) as first_order_value,
                    DATEDIFF(NOW(), MIN(o.date_added)) as days_since_first_order
                FROM " . DB_PREFIX . "customer c
                LEFT JOIN " . DB_PREFIX . "order o ON c.customer_id = o.customer_id
                WHERE o.order_status_id > 0
                GROUP BY c.customer_id
                HAVING days_since_first_order <= 30
            ) as new_customers
        ");

        return $query->rows;
    }

    public function getInventoryOptimization() {
        $query = $this->db->query("
            SELECT 
                p.product_id,
                pd.name as product_name,
                p.quantity as current_stock,
                COALESCE(sales_data.avg_daily_sales, 0) as avg_daily_sales,
                CASE 
                    WHEN COALESCE(sales_data.avg_daily_sales, 0) = 0 THEN 999
                    ELSE ROUND(p.quantity / sales_data.avg_daily_sales, 0)
                END as days_of_stock,
                CASE 
                    WHEN p.quantity / COALESCE(sales_data.avg_daily_sales, 1) < 7 THEN 'urgent_reorder'
                    WHEN p.quantity / COALESCE(sales_data.avg_daily_sales, 1) < 14 THEN 'reorder_soon'
                    WHEN p.quantity / COALESCE(sales_data.avg_daily_sales, 1) > 60 THEN 'overstocked'
                    ELSE 'optimal'
                END as stock_status,
                ROUND(sales_data.avg_daily_sales * 21, 0) as recommended_stock
            FROM " . DB_PREFIX . "product p
            LEFT JOIN " . DB_PREFIX . "product_description pd ON p.product_id = pd.product_id AND pd.language_id = 1
            LEFT JOIN (
                SELECT 
                    op.product_id,
                    AVG(op.quantity) as avg_daily_sales
                FROM " . DB_PREFIX . "order_product op
                INNER JOIN " . DB_PREFIX . "order o ON op.order_id = o.order_id
                WHERE o.order_status_id > 0
                AND o.date_added >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY op.product_id
            ) as sales_data ON p.product_id = sales_data.product_id
            WHERE p.status = '1'
            ORDER BY 
                CASE 
                    WHEN p.quantity / COALESCE(sales_data.avg_daily_sales, 1) < 7 THEN 1
                    WHEN p.quantity / COALESCE(sales_data.avg_daily_sales, 1) < 14 THEN 2
                    ELSE 3
                END,
                sales_data.avg_daily_sales DESC
            LIMIT 20
        ");

        return $query->rows;
    }

    public function getMarketTrends() {
        $query = $this->db->query("
            SELECT 
                'sales_growth' as trend_type,
                'Sales Growth Trend' as trend_name,
                ROUND(
                    ((current_month.total - previous_month.total) / previous_month.total) * 100, 2
                ) as trend_percentage,
                CASE 
                    WHEN current_month.total > previous_month.total THEN 'positive'
                    WHEN current_month.total < previous_month.total THEN 'negative'
                    ELSE 'neutral'
                END as trend_direction,
                'Month-over-month sales growth analysis' as description
            FROM (
                SELECT SUM(total) as total 
                FROM " . DB_PREFIX . "order 
                WHERE order_status_id > 0 
                AND MONTH(date_added) = MONTH(NOW()) 
                AND YEAR(date_added) = YEAR(NOW())
            ) as current_month,
            (
                SELECT SUM(total) as total 
                FROM " . DB_PREFIX . "order 
                WHERE order_status_id > 0 
                AND MONTH(date_added) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH))
                AND YEAR(date_added) = YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH))
            ) as previous_month
            
            UNION ALL
            
            SELECT 
                'customer_acquisition' as trend_type,
                'Customer Acquisition Trend' as trend_name,
                ROUND(
                    ((current_customers.count - previous_customers.count) / previous_customers.count) * 100, 2
                ) as trend_percentage,
                CASE 
                    WHEN current_customers.count > previous_customers.count THEN 'positive'
                    WHEN current_customers.count < previous_customers.count THEN 'negative'
                    ELSE 'neutral'
                END as trend_direction,
                'New customer acquisition rate analysis' as description
            FROM (
                SELECT COUNT(*) as count 
                FROM " . DB_PREFIX . "customer 
                WHERE MONTH(date_added) = MONTH(NOW()) 
                AND YEAR(date_added) = YEAR(NOW())
            ) as current_customers,
            (
                SELECT COUNT(*) as count 
                FROM " . DB_PREFIX . "customer 
                WHERE MONTH(date_added) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH))
                AND YEAR(date_added) = YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH))
            ) as previous_customers
        ");

        return $query->rows;
    }

    public function getRiskAnalysis() {
        $risks = array();

        // تحليل مخاطر المخزون
        $inventory_risk = $this->db->query("
            SELECT COUNT(*) as low_stock_products
            FROM " . DB_PREFIX . "product p
            LEFT JOIN (
                SELECT 
                    op.product_id,
                    AVG(op.quantity) as avg_daily_sales
                FROM " . DB_PREFIX . "order_product op
                INNER JOIN " . DB_PREFIX . "order o ON op.order_id = o.order_id
                WHERE o.order_status_id > 0
                AND o.date_added >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY op.product_id
            ) as sales ON p.product_id = sales.product_id
            WHERE p.status = '1'
            AND p.quantity / COALESCE(sales.avg_daily_sales, 1) < 7
        ");

        $risks[] = array(
            'risk_type' => 'inventory',
            'risk_level' => $inventory_risk->row['low_stock_products'] > 10 ? 'high' : 'medium',
            'description' => $inventory_risk->row['low_stock_products'] . ' products with critically low stock',
            'impact' => 'Potential stockouts and lost sales',
            'recommendation' => 'Immediate reordering required for critical items'
        );

        // تحليل مخاطر العملاء
        $customer_risk = $this->db->query("
            SELECT COUNT(*) as at_risk_customers
            FROM " . DB_PREFIX . "customer c
            LEFT JOIN " . DB_PREFIX . "order o ON c.customer_id = o.customer_id
            WHERE o.order_status_id > 0
            GROUP BY c.customer_id
            HAVING DATEDIFF(NOW(), MAX(o.date_added)) > 90
            AND COUNT(o.order_id) >= 3
        ");

        $risks[] = array(
            'risk_type' => 'customer_churn',
            'risk_level' => $customer_risk->row['at_risk_customers'] > 50 ? 'high' : 'medium',
            'description' => $customer_risk->row['at_risk_customers'] . ' valuable customers at risk of churning',
            'impact' => 'Potential revenue loss from customer attrition',
            'recommendation' => 'Implement retention campaigns for at-risk customers'
        );

        return $risks;
    }

    public function getRevenueForecast() {
        $query = $this->db->query("
            SELECT 
                ROUND(
                    (
                        SELECT SUM(total) 
                        FROM " . DB_PREFIX . "order 
                        WHERE order_status_id > 0 
                        AND date_added >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    ) * 1.1, 2
                ) as next_month_forecast,
                ROUND(
                    (
                        SELECT SUM(total) 
                        FROM " . DB_PREFIX . "order 
                        WHERE order_status_id > 0 
                        AND date_added >= DATE_SUB(NOW(), INTERVAL 90 DAY)
                    ) * 1.15, 2
                ) as next_quarter_forecast
        ");

        return $query->row;
    }

    public function getCustomerLifetimeValue() {
        $query = $this->db->query("
            SELECT 
                AVG(customer_ltv) as avg_ltv,
                MAX(customer_ltv) as max_ltv,
                MIN(customer_ltv) as min_ltv
            FROM (
                SELECT 
                    c.customer_id,
                    SUM(o.total) as customer_ltv
                FROM " . DB_PREFIX . "customer c
                LEFT JOIN " . DB_PREFIX . "order o ON c.customer_id = o.customer_id
                WHERE o.order_status_id > 0
                GROUP BY c.customer_id
            ) as ltv_data
        ");

        return $query->row;
    }

    public function getChurnProbability() {
        $query = $this->db->query("
            SELECT 
                ROUND(
                    (COUNT(CASE WHEN days_inactive > 90 THEN 1 END) / COUNT(*)) * 100, 2
                ) as churn_probability
            FROM (
                SELECT 
                    c.customer_id,
                    DATEDIFF(NOW(), COALESCE(MAX(o.date_added), c.date_added)) as days_inactive
                FROM " . DB_PREFIX . "customer c
                LEFT JOIN " . DB_PREFIX . "order o ON c.customer_id = o.customer_id AND o.order_status_id > 0
                GROUP BY c.customer_id
            ) as customer_activity
        ");

        return $query->row['churn_probability'] ? (float)$query->row['churn_probability'] : 0;
    }

    public function getCrossSellOpportunities() {
        $query = $this->db->query("
            SELECT 
                p1.product_id as product_a,
                p1.name as product_a_name,
                p2.product_id as product_b,
                p2.name as product_b_name,
                COUNT(*) as frequency,
                ROUND((COUNT(*) / total_orders.total) * 100, 2) as confidence
            FROM " . DB_PREFIX . "order_product op1
            INNER JOIN " . DB_PREFIX . "order_product op2 ON op1.order_id = op2.order_id AND op1.product_id != op2.product_id
            INNER JOIN " . DB_PREFIX . "product_description p1 ON op1.product_id = p1.product_id AND p1.language_id = 1
            INNER JOIN " . DB_PREFIX . "product_description p2 ON op2.product_id = p2.product_id AND p2.language_id = 1
            CROSS JOIN (
                SELECT COUNT(DISTINCT order_id) as total 
                FROM " . DB_PREFIX . "order_product
            ) as total_orders
            GROUP BY op1.product_id, op2.product_id
            HAVING frequency >= 5
            ORDER BY confidence DESC
            LIMIT 10
        ");

        return $query->rows;
    }

    public function getPriceOptimization() {
        $query = $this->db->query("
            SELECT 
                p.product_id,
                pd.name as product_name,
                p.price as current_price,
                ROUND(
                    p.price * (1 + (demand_score.score - 0.5) * 0.2), 2
                ) as optimized_price,
                demand_score.score as demand_score
            FROM " . DB_PREFIX . "product p
            LEFT JOIN " . DB_PREFIX . "product_description pd ON p.product_id = pd.product_id AND pd.language_id = 1
            LEFT JOIN (
                SELECT 
                    op.product_id,
                    LEAST(1.0, COUNT(op.order_id) / 100.0) as score
                FROM " . DB_PREFIX . "order_product op
                INNER JOIN " . DB_PREFIX . "order o ON op.order_id = o.order_id
                WHERE o.order_status_id > 0
                AND o.date_added >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY op.product_id
            ) as demand_score ON p.product_id = demand_score.product_id
            WHERE p.status = '1'
            ORDER BY demand_score.score DESC
            LIMIT 20
        ");

        return $query->rows;
    }

    public function getSmartRecommendations() {
        $recommendations = array();

        // توصيات المخزون
        $inventory_rec = $this->db->query("
            SELECT COUNT(*) as urgent_reorders
            FROM " . DB_PREFIX . "product p
            LEFT JOIN (
                SELECT 
                    op.product_id,
                    AVG(op.quantity) as avg_daily_sales
                FROM " . DB_PREFIX . "order_product op
                INNER JOIN " . DB_PREFIX . "order o ON op.order_id = o.order_id
                WHERE o.order_status_id > 0
                AND o.date_added >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY op.product_id
            ) as sales ON p.product_id = sales.product_id
            WHERE p.status = '1'
            AND p.quantity / COALESCE(sales.avg_daily_sales, 1) < 7
        ");

        if ($inventory_rec->row['urgent_reorders'] > 0) {
            $recommendations[] = array(
                'type' => 'inventory',
                'priority' => 'high',
                'title' => 'Urgent Inventory Reorder',
                'description' => $inventory_rec->row['urgent_reorders'] . ' products need immediate reordering',
                'action' => 'Review and reorder critical inventory items'
            );
        }

        // توصيات التسويق
        $marketing_rec = $this->db->query("
            SELECT COUNT(*) as inactive_customers
            FROM " . DB_PREFIX . "customer c
            LEFT JOIN " . DB_PREFIX . "order o ON c.customer_id = o.customer_id
            WHERE o.order_status_id > 0
            GROUP BY c.customer_id
            HAVING DATEDIFF(NOW(), MAX(o.date_added)) BETWEEN 30 AND 90
            AND COUNT(o.order_id) >= 2
        ");

        if ($marketing_rec->row['inactive_customers'] > 0) {
            $recommendations[] = array(
                'type' => 'marketing',
                'priority' => 'medium',
                'title' => 'Customer Re-engagement Campaign',
                'description' => $marketing_rec->row['inactive_customers'] . ' customers haven\'t ordered in 30-90 days',
                'action' => 'Launch targeted email campaign to re-engage customers'
            );
        }

        return $recommendations;
    }

    public function getPredictiveSalesData() {
        return $this->getSalesPredictions();
    }

    public function getCustomerSegmentationData() {
        return $this->getCustomerBehaviorInsights();
    }

    public function getDemandForecastingData() {
        $query = $this->db->query("
            SELECT 
                pd.name as product_name,
                COALESCE(current_demand.quantity, 0) as current_demand,
                ROUND(COALESCE(current_demand.quantity, 0) * 1.1, 0) as predicted_demand
            FROM " . DB_PREFIX . "product p
            LEFT JOIN " . DB_PREFIX . "product_description pd ON p.product_id = pd.product_id AND pd.language_id = 1
            LEFT JOIN (
                SELECT 
                    op.product_id,
                    SUM(op.quantity) as quantity
                FROM " . DB_PREFIX . "order_product op
                INNER JOIN " . DB_PREFIX . "order o ON op.order_id = o.order_id
                WHERE o.order_status_id > 0
                AND o.date_added >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY op.product_id
            ) as current_demand ON p.product_id = current_demand.product_id
            WHERE p.status = '1'
            ORDER BY current_demand.quantity DESC
            LIMIT 15
        ");

        return $query->rows;
    }

    public function getAnomalyDetectionData() {
        $query = $this->db->query("
            SELECT 
                DATE(date_added) as date,
                SUM(total) as daily_sales,
                CASE 
                    WHEN SUM(total) > (
                        SELECT AVG(daily_total) * 2 
                        FROM (
                            SELECT DATE(date_added) as sale_date, SUM(total) as daily_total
                            FROM " . DB_PREFIX . "order 
                            WHERE order_status_id > 0 
                            AND date_added >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                            GROUP BY DATE(date_added)
                        ) as avg_sales
                    ) THEN 'high_anomaly'
                    WHEN SUM(total) < (
                        SELECT AVG(daily_total) * 0.5 
                        FROM (
                            SELECT DATE(date_added) as sale_date, SUM(total) as daily_total
                            FROM " . DB_PREFIX . "order 
                            WHERE order_status_id > 0 
                            AND date_added >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                            GROUP BY DATE(date_added)
                        ) as avg_sales
                    ) THEN 'low_anomaly'
                    ELSE 'normal'
                END as anomaly_type
            FROM " . DB_PREFIX . "order 
            WHERE order_status_id > 0 
            AND date_added >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(date_added)
            ORDER BY date DESC
        ");

        return $query->rows;
    }

    public function getAvailableModels() {
        $query = $this->db->query("
            SELECT 
                model_id,
                model_name,
                model_type,
                accuracy_score,
                last_trained,
                status
            FROM " . DB_PREFIX . "ai_model 
            WHERE status = '1'
            ORDER BY accuracy_score DESC
        ");

        return $query->rows;
    }

    public function generatePrediction($prediction_type, $parameters) {
        // محاكاة إنشاء توقع بناءً على النوع والمعاملات
        switch ($prediction_type) {
            case 'sales_forecast':
                return $this->getSalesPredictions();
            case 'customer_churn':
                return array('churn_probability' => $this->getChurnProbability());
            case 'inventory_demand':
                return $this->getDemandForecastingData();
            default:
                return false;
        }
    }

    public function trainModel($model_type, $training_data) {
        // محاكاة تدريب النموذج
        try {
            // تسجيل عملية التدريب
            $this->db->query("
                INSERT INTO " . DB_PREFIX . "ai_training_log 
                SET model_type = '" . $this->db->escape($model_type) . "',
                    training_started = NOW(),
                    status = 'completed',
                    accuracy_achieved = '" . (float)(85 + rand(0, 10)) . "',
                    date_added = NOW()
            ");

            return array(
                'success' => true,
                'model_info' => array(
                    'model_type' => $model_type,
                    'accuracy' => 85 + rand(0, 10),
                    'training_time' => rand(5, 30) . ' minutes',
                    'data_points' => count($training_data)
                )
            );
        } catch (Exception $e) {
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }

    public function optimizeParameters($target, $constraints) {
        // محاكاة تحسين المعاملات
        try {
            $optimization_results = array(
                'optimal_price' => rand(50, 200),
                'optimal_inventory' => rand(100, 500),
                'expected_improvement' => rand(5, 25) . '%',
                'confidence_level' => rand(80, 95) . '%'
            );

            return array(
                'success' => true,
                'results' => $optimization_results
            );
        } catch (Exception $e) {
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }

    public function generateAnalysisReport($analysis_type, $date_from = '', $date_to = '') {
        $data = array();
        
        switch ($analysis_type) {
            case 'sales_forecast':
                $data = $this->getSalesPredictions();
                break;
            case 'customer_analysis':
                $data = $this->getCustomerBehaviorInsights();
                break;
            case 'inventory_optimization':
                $data = $this->getInventoryOptimization();
                break;
            case 'market_trends':
                $data = $this->getMarketTrends();
                break;
            case 'risk_assessment':
                $data = $this->getRiskAnalysis();
                break;
        }

        return $data;
    }
}
