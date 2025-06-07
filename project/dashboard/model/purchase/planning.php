<?php
class ModelPurchasePlanning extends Model {

    /**
     * إضافة خطة شراء جديدة
     */
    public function addPlan($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "purchase_plan SET 
                         plan_name = '" . $this->db->escape($data['plan_name']) . "',
                         plan_description = '" . $this->db->escape($data['plan_description']) . "',
                         plan_period = '" . $this->db->escape($data['plan_period']) . "',
                         start_date = '" . $this->db->escape($data['start_date']) . "',
                         end_date = '" . $this->db->escape($data['end_date']) . "',
                         total_budget = '" . (float)$data['total_budget'] . "',
                         used_budget = '0.0000',
                         status = '" . $this->db->escape($data['status']) . "',
                         notes = '" . $this->db->escape($data['notes']) . "',
                         created_by = '" . (int)$this->user->getId() . "',
                         date_added = NOW()");

        $plan_id = $this->db->getLastId();

        // إضافة عناصر الخطة
        if (isset($data['plan_items'])) {
            foreach ($data['plan_items'] as $item) {
                if (!empty($item['product_id']) && !empty($item['quantity'])) {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "purchase_plan_item SET 
                                     plan_id = '" . (int)$plan_id . "',
                                     product_id = '" . (int)$item['product_id'] . "',
                                     category_id = '" . (int)($item['category_id'] ?? 0) . "',
                                     planned_quantity = '" . (float)$item['quantity'] . "',
                                     estimated_price = '" . (float)($item['price'] ?? 0) . "',
                                     total_amount = '" . (float)($item['quantity'] * ($item['price'] ?? 0)) . "',
                                     priority = '" . $this->db->escape($item['priority'] ?? 'medium') . "',
                                     notes = '" . $this->db->escape($item['notes'] ?? '') . "'");
                }
            }
        }

        return $plan_id;
    }

    /**
     * تعديل خطة شراء
     */
    public function editPlan($plan_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "purchase_plan SET 
                         plan_name = '" . $this->db->escape($data['plan_name']) . "',
                         plan_description = '" . $this->db->escape($data['plan_description']) . "',
                         plan_period = '" . $this->db->escape($data['plan_period']) . "',
                         start_date = '" . $this->db->escape($data['start_date']) . "',
                         end_date = '" . $this->db->escape($data['end_date']) . "',
                         total_budget = '" . (float)$data['total_budget'] . "',
                         status = '" . $this->db->escape($data['status']) . "',
                         notes = '" . $this->db->escape($data['notes']) . "',
                         modified_by = '" . (int)$this->user->getId() . "',
                         date_modified = NOW()
                         WHERE plan_id = '" . (int)$plan_id . "'");

        // حذف العناصر القديمة
        $this->db->query("DELETE FROM " . DB_PREFIX . "purchase_plan_item WHERE plan_id = '" . (int)$plan_id . "'");

        // إضافة العناصر الجديدة
        if (isset($data['plan_items'])) {
            foreach ($data['plan_items'] as $item) {
                if (!empty($item['product_id']) && !empty($item['quantity'])) {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "purchase_plan_item SET 
                                     plan_id = '" . (int)$plan_id . "',
                                     product_id = '" . (int)$item['product_id'] . "',
                                     category_id = '" . (int)($item['category_id'] ?? 0) . "',
                                     planned_quantity = '" . (float)$item['quantity'] . "',
                                     estimated_price = '" . (float)($item['price'] ?? 0) . "',
                                     total_amount = '" . (float)($item['quantity'] * ($item['price'] ?? 0)) . "',
                                     priority = '" . $this->db->escape($item['priority'] ?? 'medium') . "',
                                     notes = '" . $this->db->escape($item['notes'] ?? '') . "'");
                }
            }
        }
    }

    /**
     * حذف خطة شراء
     */
    public function deletePlan($plan_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "purchase_plan_item WHERE plan_id = '" . (int)$plan_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "purchase_plan WHERE plan_id = '" . (int)$plan_id . "'");
    }

    /**
     * الحصول على خطة شراء
     */
    public function getPlan($plan_id) {
        $query = $this->db->query("SELECT pp.*, 
                                          CONCAT(u.firstname, ' ', COALESCE(u.lastname, '')) AS created_by_name,
                                          CONCAT(u2.firstname, ' ', COALESCE(u2.lastname, '')) AS modified_by_name
                                   FROM " . DB_PREFIX . "purchase_plan pp
                                   LEFT JOIN " . DB_PREFIX . "user u ON (pp.created_by = u.user_id)
                                   LEFT JOIN " . DB_PREFIX . "user u2 ON (pp.modified_by = u2.user_id)
                                   WHERE pp.plan_id = '" . (int)$plan_id . "'");

        return $query->row;
    }

    /**
     * الحصول على قائمة خطط الشراء
     */
    public function getPlans($data = array()) {
        $sql = "SELECT pp.*, 
                       CONCAT(u.firstname, ' ', COALESCE(u.lastname, '')) AS created_by_name
                FROM " . DB_PREFIX . "purchase_plan pp
                LEFT JOIN " . DB_PREFIX . "user u ON (pp.created_by = u.user_id)
                WHERE 1 = 1";

        if (!empty($data['filter_name'])) {
            $sql .= " AND pp.plan_name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND pp.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        if (!empty($data['filter_period'])) {
            $sql .= " AND pp.plan_period = '" . $this->db->escape($data['filter_period']) . "'";
        }

        $sort_data = array(
            'pp.plan_id',
            'pp.plan_name',
            'pp.plan_period',
            'pp.start_date',
            'pp.end_date',
            'pp.total_budget',
            'pp.status'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY pp.plan_name";
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

    /**
     * الحصول على إجمالي عدد خطط الشراء
     */
    public function getTotalPlans($data = array()) {
        $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "purchase_plan pp WHERE 1 = 1";

        if (!empty($data['filter_name'])) {
            $sql .= " AND pp.plan_name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND pp.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        if (!empty($data['filter_period'])) {
            $sql .= " AND pp.plan_period = '" . $this->db->escape($data['filter_period']) . "'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    /**
     * الحصول على عناصر خطة الشراء
     */
    public function getPlanItems($plan_id) {
        $query = $this->db->query("SELECT ppi.*, 
                                          p.name AS product_name,
                                          p.model AS product_model,
                                          p.sku AS product_sku,
                                          c.name AS category_name
                                   FROM " . DB_PREFIX . "purchase_plan_item ppi
                                   LEFT JOIN " . DB_PREFIX . "product p ON (ppi.product_id = p.product_id)
                                   LEFT JOIN " . DB_PREFIX . "category_description c ON (ppi.category_id = c.category_id AND c.language_id = '" . (int)$this->config->get('config_language_id') . "')
                                   WHERE ppi.plan_id = '" . (int)$plan_id . "'
                                   ORDER BY ppi.priority DESC, ppi.item_id ASC");

        return $query->rows;
    }

    /**
     * الحصول على تقدم تنفيذ الخطة
     */
    public function getPlanProgress($plan_id) {
        $progress = array();

        // إجمالي العناصر المخططة
        $query = $this->db->query("SELECT COUNT(*) as total_items, 
                                          SUM(planned_quantity) as total_quantity,
                                          SUM(total_amount) as total_amount
                                   FROM " . DB_PREFIX . "purchase_plan_item 
                                   WHERE plan_id = '" . (int)$plan_id . "'");
        
        $progress['planned'] = $query->row;

        // العناصر المشتراة
        $query = $this->db->query("SELECT COUNT(DISTINCT ppi.item_id) as purchased_items,
                                          SUM(poi.quantity) as purchased_quantity,
                                          SUM(poi.quantity * poi.price) as purchased_amount
                                   FROM " . DB_PREFIX . "purchase_plan_item ppi
                                   LEFT JOIN " . DB_PREFIX . "purchase_order_item poi ON (ppi.product_id = poi.product_id)
                                   LEFT JOIN " . DB_PREFIX . "purchase_order po ON (poi.order_id = po.order_id)
                                   WHERE ppi.plan_id = '" . (int)$plan_id . "'
                                   AND po.order_date BETWEEN (SELECT start_date FROM " . DB_PREFIX . "purchase_plan WHERE plan_id = '" . (int)$plan_id . "')
                                   AND (SELECT end_date FROM " . DB_PREFIX . "purchase_plan WHERE plan_id = '" . (int)$plan_id . "')
                                   AND po.status IN ('confirmed', 'completed')");

        $progress['purchased'] = $query->row;

        // حساب النسب المئوية
        if ($progress['planned']['total_items'] > 0) {
            $progress['items_percentage'] = round(($progress['purchased']['purchased_items'] / $progress['planned']['total_items']) * 100, 2);
        } else {
            $progress['items_percentage'] = 0;
        }

        if ($progress['planned']['total_quantity'] > 0) {
            $progress['quantity_percentage'] = round(($progress['purchased']['purchased_quantity'] / $progress['planned']['total_quantity']) * 100, 2);
        } else {
            $progress['quantity_percentage'] = 0;
        }

        if ($progress['planned']['total_amount'] > 0) {
            $progress['budget_percentage'] = round(($progress['purchased']['purchased_amount'] / $progress['planned']['total_amount']) * 100, 2);
        } else {
            $progress['budget_percentage'] = 0;
        }

        return $progress;
    }

    /**
     * الحصول على تحليلات الخطة
     */
    public function getPlanAnalytics($plan_id) {
        $analytics = array();

        // تحليل حسب الفئة
        $query = $this->db->query("SELECT c.name as category_name,
                                          COUNT(ppi.item_id) as items_count,
                                          SUM(ppi.planned_quantity) as total_quantity,
                                          SUM(ppi.total_amount) as total_amount
                                   FROM " . DB_PREFIX . "purchase_plan_item ppi
                                   LEFT JOIN " . DB_PREFIX . "category_description c ON (ppi.category_id = c.category_id AND c.language_id = '" . (int)$this->config->get('config_language_id') . "')
                                   WHERE ppi.plan_id = '" . (int)$plan_id . "'
                                   GROUP BY ppi.category_id
                                   ORDER BY total_amount DESC");

        $analytics['by_category'] = $query->rows;

        // تحليل حسب الأولوية
        $query = $this->db->query("SELECT priority,
                                          COUNT(item_id) as items_count,
                                          SUM(planned_quantity) as total_quantity,
                                          SUM(total_amount) as total_amount
                                   FROM " . DB_PREFIX . "purchase_plan_item 
                                   WHERE plan_id = '" . (int)$plan_id . "'
                                   GROUP BY priority
                                   ORDER BY FIELD(priority, 'high', 'medium', 'low')");

        $analytics['by_priority'] = $query->rows;

        // أعلى 10 منتجات من حيث القيمة
        $query = $this->db->query("SELECT ppi.*, p.name as product_name
                                   FROM " . DB_PREFIX . "purchase_plan_item ppi
                                   LEFT JOIN " . DB_PREFIX . "product p ON (ppi.product_id = p.product_id)
                                   WHERE ppi.plan_id = '" . (int)$plan_id . "'
                                   ORDER BY ppi.total_amount DESC
                                   LIMIT 10");

        $analytics['top_products'] = $query->rows;

        return $analytics;
    }

    /**
     * الحصول على إحصائيات التخطيط
     */
    public function getPlanningStatistics() {
        $statistics = array();

        // إجمالي الخطط
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "purchase_plan");
        $statistics['total_plans'] = $query->row['total'];

        // الخطط النشطة
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "purchase_plan WHERE status = 'active'");
        $statistics['active_plans'] = $query->row['total'];

        // الخطط المكتملة
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "purchase_plan WHERE status = 'completed'");
        $statistics['completed_plans'] = $query->row['total'];

        // إجمالي الميزانيات
        $query = $this->db->query("SELECT SUM(total_budget) as total, SUM(used_budget) as used FROM " . DB_PREFIX . "purchase_plan WHERE status IN ('active', 'completed')");
        $statistics['total_budget'] = $query->row['total'] ? $query->row['total'] : 0;
        $statistics['used_budget'] = $query->row['used'] ? $query->row['used'] : 0;
        $statistics['remaining_budget'] = $statistics['total_budget'] - $statistics['used_budget'];

        return $statistics;
    }

    /**
     * تقرير التخطيط
     */
    public function getPlanningReport($data = array()) {
        $sql = "SELECT pp.*, 
                       CONCAT(u.firstname, ' ', COALESCE(u.lastname, '')) AS created_by_name
                FROM " . DB_PREFIX . "purchase_plan pp
                LEFT JOIN " . DB_PREFIX . "user u ON (pp.created_by = u.user_id)
                WHERE 1 = 1";

        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(pp.start_date) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(pp.end_date) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $sql .= " ORDER BY pp.start_date DESC";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    /**
     * تحليل الميزانية
     */
    public function getBudgetAnalysis($data = array()) {
        $sql = "SELECT 
                pp.plan_period,
                COUNT(*) as plans_count,
                SUM(pp.total_budget) as total_budget,
                SUM(pp.used_budget) as used_budget,
                AVG(pp.total_budget) as avg_budget
                FROM " . DB_PREFIX . "purchase_plan pp
                WHERE 1 = 1";

        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(pp.start_date) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(pp.end_date) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $sql .= " GROUP BY pp.plan_period ORDER BY total_budget DESC";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    /**
     * مقاييس الأداء
     */
    public function getPerformanceMetrics($data = array()) {
        $metrics = array();

        // معدل إنجاز الخطط
        $query = $this->db->query("SELECT 
                                   COUNT(*) as total_plans,
                                   SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_plans,
                                   SUM(CASE WHEN status = 'active' AND end_date < CURDATE() THEN 1 ELSE 0 END) as overdue_plans
                                   FROM " . DB_PREFIX . "purchase_plan pp
                                   WHERE 1 = 1");

        if (!empty($data['filter_date_start'])) {
            $query .= " AND DATE(pp.start_date) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $query .= " AND DATE(pp.end_date) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $result = $this->db->query($query);
        $metrics['completion_rate'] = $result->row;

        // متوسط استخدام الميزانية
        $query = $this->db->query("SELECT 
                                   AVG(CASE WHEN total_budget > 0 THEN (used_budget / total_budget) * 100 ELSE 0 END) as avg_budget_utilization
                                   FROM " . DB_PREFIX . "purchase_plan pp
                                   WHERE status IN ('active', 'completed')");

        if (!empty($data['filter_date_start'])) {
            $query .= " AND DATE(pp.start_date) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $query .= " AND DATE(pp.end_date) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $result = $this->db->query($query);
        $metrics['budget_utilization'] = $result->row;

        return $metrics;
    }

    /**
     * تحديث الميزانية المستخدمة للخطة
     */
    public function updateUsedBudget($plan_id) {
        // حساب الميزانية المستخدمة من أوامر الشراء
        $query = $this->db->query("SELECT SUM(poi.quantity * poi.price) as used_budget
                                   FROM " . DB_PREFIX . "purchase_plan_item ppi
                                   LEFT JOIN " . DB_PREFIX . "purchase_order_item poi ON (ppi.product_id = poi.product_id)
                                   LEFT JOIN " . DB_PREFIX . "purchase_order po ON (poi.order_id = po.order_id)
                                   WHERE ppi.plan_id = '" . (int)$plan_id . "'
                                   AND po.order_date BETWEEN (SELECT start_date FROM " . DB_PREFIX . "purchase_plan WHERE plan_id = '" . (int)$plan_id . "')
                                   AND (SELECT end_date FROM " . DB_PREFIX . "purchase_plan WHERE plan_id = '" . (int)$plan_id . "')
                                   AND po.status IN ('confirmed', 'completed')");

        $used_budget = $query->row['used_budget'] ? $query->row['used_budget'] : 0;

        $this->db->query("UPDATE " . DB_PREFIX . "purchase_plan 
                         SET used_budget = '" . (float)$used_budget . "'
                         WHERE plan_id = '" . (int)$plan_id . "'");
    }
}
