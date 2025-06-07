<?php
/**
 * نموذج لوحة معلومات المخزون المتقدمة (Advanced Inventory Dashboard Model)
 *
 * الهدف: توفير البيانات والإحصائيات المتقدمة للوحة معلومات المخزون
 * الميزات: استعلامات محسنة، تحليلات متقدمة، تكامل مع WAC والمحاسبة
 *
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ModelInventoryDashboard extends Model {

    /**
     * الحصول على إجمالي قيمة المخزون بالتكلفة (WAC)
     */
    public function getTotalInventoryValue() {
        $query = $this->db->query("
            SELECT SUM(pi.quantity * p.average_cost) as total_value
            FROM " . DB_PREFIX . "cod_product_inventory pi
            LEFT JOIN " . DB_PREFIX . "cod_product p ON (pi.product_id = p.product_id)
            WHERE pi.quantity > 0 AND p.status = 1
        ");

        return $query->row['total_value'] ? (float)$query->row['total_value'] : 0;
    }

    /**
     * الحصول على عدد المنتجات منخفضة المخزون
     */
    public function getLowStockProductsCount() {
        $query = $this->db->query("
            SELECT COUNT(*) as count
            FROM " . DB_PREFIX . "cod_product_inventory pi
            LEFT JOIN " . DB_PREFIX . "cod_product p ON (pi.product_id = p.product_id)
            WHERE pi.quantity <= p.minimum_quantity AND p.status = 1
        ");

        return (int)$query->row['count'];
    }

    /**
     * الحصول على المنتجات منخفضة المخزون
     */
    public function getLowStockProducts($limit = 10) {
        $query = $this->db->query("
            SELECT p.product_id, pd.name, pi.quantity, p.minimum_quantity,
                   p.average_cost, b.name as branch_name
            FROM " . DB_PREFIX . "cod_product_inventory pi
            LEFT JOIN " . DB_PREFIX . "cod_product p ON (pi.product_id = p.product_id)
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_branch b ON (pi.branch_id = b.branch_id)
            WHERE pi.quantity <= p.minimum_quantity AND p.status = 1
            AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            ORDER BY (pi.quantity / p.minimum_quantity) ASC
            LIMIT " . (int)$limit
        );

        return $query->rows;
    }

    /**
     * الحصول على عدد المنتجات منتهية الصلاحية قريباً
     */
    public function getExpiringProductsCount($days = 30) {
        $query = $this->db->query("
            SELECT COUNT(DISTINCT pb.product_id) as count
            FROM " . DB_PREFIX . "cod_product_batch pb
            LEFT JOIN " . DB_PREFIX . "cod_product p ON (pb.product_id = p.product_id)
            WHERE pb.expiry_date IS NOT NULL
            AND pb.expiry_date <= DATE_ADD(NOW(), INTERVAL " . (int)$days . " DAY)
            AND pb.quantity > 0 AND p.status = 1
        ");

        return (int)$query->row['count'];
    }

    /**
     * الحصول على المنتجات منتهية الصلاحية قريباً
     */
    public function getExpiringProducts($days = 30) {
        $query = $this->db->query("
            SELECT p.product_id, pd.name, pb.batch_number, pb.expiry_date,
                   pb.quantity, DATEDIFF(pb.expiry_date, NOW()) as days_to_expiry
            FROM " . DB_PREFIX . "cod_product_batch pb
            LEFT JOIN " . DB_PREFIX . "cod_product p ON (pb.product_id = p.product_id)
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            WHERE pb.expiry_date IS NOT NULL
            AND pb.expiry_date <= DATE_ADD(NOW(), INTERVAL " . (int)$days . " DAY)
            AND pb.quantity > 0 AND p.status = 1
            AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            ORDER BY pb.expiry_date ASC
        ");

        return $query->rows;
    }

    /**
     * الحصول على عدد المنتجات بطيئة الحركة
     */
    public function getSlowMovingProducts($days = 90) {
        $query = $this->db->query("
            SELECT p.product_id, pd.name, pi.quantity, p.average_cost,
                   COALESCE(last_movement.last_date, p.date_added) as last_movement_date,
                   DATEDIFF(NOW(), COALESCE(last_movement.last_date, p.date_added)) as days_since_movement
            FROM " . DB_PREFIX . "cod_product p
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_product_inventory pi ON (p.product_id = pi.product_id)
            LEFT JOIN (
                SELECT product_id, MAX(date_added) as last_date
                FROM " . DB_PREFIX . "cod_product_movement
                WHERE movement_type IN ('sale', 'transfer_out')
                GROUP BY product_id
            ) last_movement ON (p.product_id = last_movement.product_id)
            WHERE p.status = 1 AND pi.quantity > 0
            AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            HAVING days_since_movement > " . (int)$days . "
            ORDER BY days_since_movement DESC
        ");

        return $query->rows;
    }

    /**
     * الحصول على عدد جلسات الجرد المعلقة
     */
    public function getPendingStocktakeCount() {
        $query = $this->db->query("
            SELECT COUNT(*) as count
            FROM " . DB_PREFIX . "cod_stocktake
            WHERE status IN ('pending', 'in_progress')
        ");

        return (int)$query->row['count'];
    }

    /**
     * الحصول على متوسط دوران المخزون
     */
    public function getAverageTurnover() {
        $query = $this->db->query("
            SELECT
                AVG(
                    CASE
                        WHEN pi.quantity > 0 AND p.average_cost > 0
                        THEN (
                            SELECT COALESCE(SUM(pm.quantity), 0)
                            FROM " . DB_PREFIX . "cod_product_movement pm
                            WHERE pm.product_id = p.product_id
                            AND pm.movement_type = 'sale'
                            AND pm.date_added >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                        ) / (pi.quantity)
                        ELSE 0
                    END
                ) as avg_turnover
            FROM " . DB_PREFIX . "cod_product p
            LEFT JOIN " . DB_PREFIX . "cod_product_inventory pi ON (p.product_id = pi.product_id)
            WHERE p.status = 1 AND pi.quantity > 0
        ");

        return $query->row['avg_turnover'] ? round((float)$query->row['avg_turnover'], 2) : 0;
    }

    /**
     * الحصول على بيانات رسم بياني لحركة المخزون
     */
    public function getInventoryMovementChart($days = 30) {
        $query = $this->db->query("
            SELECT
                DATE(pm.date_added) as movement_date,
                SUM(CASE WHEN pm.movement_type IN ('purchase', 'adjustment_in', 'transfer_in') THEN pm.quantity ELSE 0 END) as inbound,
                SUM(CASE WHEN pm.movement_type IN ('sale', 'adjustment_out', 'transfer_out') THEN pm.quantity ELSE 0 END) as outbound
            FROM " . DB_PREFIX . "cod_product_movement pm
            WHERE pm.date_added >= DATE_SUB(NOW(), INTERVAL " . (int)$days . " DAY)
            GROUP BY DATE(pm.date_added)
            ORDER BY movement_date ASC
        ");

        return $query->rows;
    }

    /**
     * الحصول على بيانات رسم بياني لقيمة المخزون
     */
    public function getInventoryValueChart($months = 12) {
        $query = $this->db->query("
            SELECT
                DATE_FORMAT(snapshot_date, '%Y-%m') as period,
                SUM(total_value) as inventory_value
            FROM " . DB_PREFIX . "cod_branch_inventory_snapshot
            WHERE snapshot_date >= DATE_SUB(NOW(), INTERVAL " . (int)$months . " MONTH)
            GROUP BY DATE_FORMAT(snapshot_date, '%Y-%m')
            ORDER BY period ASC
        ");

        return $query->rows;
    }

    /**
     * الحصول على المنتجات الأكثر حركة
     */
    public function getTopMovingProducts($limit = 10) {
        $query = $this->db->query("
            SELECT
                p.product_id, pd.name,
                SUM(pm.quantity) as total_movement,
                SUM(pm.quantity * pm.unit_cost) as total_value
            FROM " . DB_PREFIX . "cod_product_movement pm
            LEFT JOIN " . DB_PREFIX . "cod_product p ON (pm.product_id = p.product_id)
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            WHERE pm.date_added >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            GROUP BY p.product_id
            ORDER BY total_movement DESC
            LIMIT " . (int)$limit
        );

        return $query->rows;
    }

    /**
     * الحصول على بيانات رسم بياني للمنتجات الأكثر حركة
     */
    public function getTopProductsChart($limit = 10) {
        $products = $this->getTopMovingProducts($limit);

        $chart_data = array();
        foreach ($products as $product) {
            $chart_data[] = array(
                'label' => $product['name'],
                'value' => (float)$product['total_movement']
            );
        }

        return $chart_data;
    }

    /**
     * الحصول على بيانات رسم بياني لتوزيع المخزون حسب الفروع
     */
    public function getBranchDistributionChart() {
        $query = $this->db->query("
            SELECT
                b.name as branch_name,
                SUM(pi.quantity * p.average_cost) as branch_value
            FROM " . DB_PREFIX . "cod_product_inventory pi
            LEFT JOIN " . DB_PREFIX . "cod_product p ON (pi.product_id = p.product_id)
            LEFT JOIN " . DB_PREFIX . "cod_branch b ON (pi.branch_id = b.branch_id)
            WHERE pi.quantity > 0 AND p.status = 1
            GROUP BY b.branch_id
            ORDER BY branch_value DESC
        ");

        return $query->rows;
    }

    /**
     * الحصول على ملخص تحليل ABC
     */
    public function getABCAnalysisSummary() {
        $query = $this->db->query("
            SELECT
                abc_category,
                COUNT(*) as product_count,
                SUM(annual_value) as total_value,
                AVG(annual_value) as avg_value
            FROM " . DB_PREFIX . "cod_product_abc_analysis
            GROUP BY abc_category
            ORDER BY
                CASE abc_category
                    WHEN 'A' THEN 1
                    WHEN 'B' THEN 2
                    WHEN 'C' THEN 3
                END
        ");

        return $query->rows;
    }

    /**
     * الحصول على بيانات رسم بياني لتحليل ABC
     */
    public function getABCAnalysisChart() {
        $analysis = $this->getABCAnalysisSummary();

        $chart_data = array();
        foreach ($analysis as $category) {
            $chart_data[] = array(
                'label' => 'Category ' . $category['abc_category'],
                'value' => (float)$category['total_value'],
                'count' => (int)$category['product_count']
            );
        }

        return $chart_data;
    }

    /**
     * إنشاء لقطة للمخزون (Inventory Snapshot)
     */
    public function createInventorySnapshot() {
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "cod_branch_inventory_snapshot
            (branch_id, snapshot_date, total_value, total_quantity, created_by, date_created)
            SELECT
                pi.branch_id,
                CURDATE(),
                SUM(pi.quantity * p.average_cost),
                SUM(pi.quantity),
                '" . (int)$this->user->getId() . "',
                NOW()
            FROM " . DB_PREFIX . "cod_product_inventory pi
            LEFT JOIN " . DB_PREFIX . "cod_product p ON (pi.product_id = p.product_id)
            WHERE p.status = 1
            GROUP BY pi.branch_id
            ON DUPLICATE KEY UPDATE
                total_value = VALUES(total_value),
                total_quantity = VALUES(total_quantity),
                updated_by = '" . (int)$this->user->getId() . "',
                date_updated = NOW()
        ");
    }

    /**
     * تحديث تحليل ABC للمنتجات
     */
    public function updateABCAnalysis() {
        // حساب القيمة السنوية لكل منتج
        $this->db->query("
            CREATE TEMPORARY TABLE temp_product_annual_value AS
            SELECT
                p.product_id,
                SUM(pm.quantity * pm.unit_cost) as annual_value,
                SUM(pm.quantity) as annual_quantity
            FROM " . DB_PREFIX . "cod_product p
            LEFT JOIN " . DB_PREFIX . "cod_product_movement pm ON (p.product_id = pm.product_id)
            WHERE pm.date_added >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            AND pm.movement_type = 'sale'
            GROUP BY p.product_id
        ");

        // تحديد فئات ABC
        $this->db->query("
            DELETE FROM " . DB_PREFIX . "cod_product_abc_analysis
        ");

        $this->db->query("
            INSERT INTO " . DB_PREFIX . "cod_product_abc_analysis
            (product_id, annual_value, annual_quantity, abc_category, date_updated)
            SELECT
                product_id,
                annual_value,
                annual_quantity,
                CASE
                    WHEN annual_value >= (SELECT annual_value FROM temp_product_annual_value ORDER BY annual_value DESC LIMIT 1 OFFSET FLOOR(COUNT(*) * 0.2)) THEN 'A'
                    WHEN annual_value >= (SELECT annual_value FROM temp_product_annual_value ORDER BY annual_value DESC LIMIT 1 OFFSET FLOOR(COUNT(*) * 0.5)) THEN 'B'
                    ELSE 'C'
                END as abc_category,
                NOW()
            FROM temp_product_annual_value
        ");

        $this->db->query("DROP TEMPORARY TABLE temp_product_annual_value");
    }
}
