<?php
/**
 * نظام أيم ERP: نظام إدارة موارد الشركات المتكامل للتجارة والتوزيع
 * مع دعم الجرد المستمر والمتوسط المرجح للتكلفة والفروع المتعددة
 *
 * Model: Dashboard Alerts (لوحة التنبيهات والإنذارات)
 */
class ModelDashboardAlerts extends Model {

    /**
     * Get alerts list with filters
     *
     * @param array $data
     * @return array
     */
    public function getAlerts($data = array()) {
        $sql = "SELECT a.*,
            u1.firstname as created_firstname, u1.lastname as created_lastname,
            ar.is_read, ar.read_at, ar.is_dismissed, ar.dismissed_at
            FROM " . DB_PREFIX . "dashboard_alerts a
            LEFT JOIN " . DB_PREFIX . "user u1 ON (a.created_by = u1.user_id)
            LEFT JOIN " . DB_PREFIX . "dashboard_alert_recipients ar ON (a.alert_id = ar.alert_id AND ar.user_id = '" . (int)$this->user->getId() . "')
            WHERE (a.target_type = 'all' OR ar.user_id = '" . (int)$this->user->getId() . "')
            AND (a.expires_at IS NULL OR a.expires_at > NOW())";

        // Apply filters
        if (!empty($data['filter_type'])) {
            $sql .= " AND a.alert_type = '" . $this->db->escape($data['filter_type']) . "'";
        }

        if (!empty($data['filter_priority'])) {
            $sql .= " AND a.priority = '" . $this->db->escape($data['filter_priority']) . "'";
        }

        if (!empty($data['filter_status'])) {
            switch ($data['filter_status']) {
                case 'read':
                    $sql .= " AND ar.is_read = 1";
                    break;
                case 'unread':
                    $sql .= " AND (ar.is_read = 0 OR ar.is_read IS NULL)";
                    break;
                case 'dismissed':
                    $sql .= " AND ar.is_dismissed = 1";
                    break;
                case 'active':
                    $sql .= " AND (ar.is_dismissed = 0 OR ar.is_dismissed IS NULL)";
                    break;
            }
        }

        if (!empty($data['filter_date_from'])) {
            $sql .= " AND DATE(a.created_at) >= '" . $this->db->escape($data['filter_date_from']) . "'";
        }

        if (!empty($data['filter_date_to'])) {
            $sql .= " AND DATE(a.created_at) <= '" . $this->db->escape($data['filter_date_to']) . "'";
        }

        // Add sorting
        $sql .= " ORDER BY a.priority DESC, a.created_at DESC";

        // Add limit
        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 50;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);

        $alerts = array();
        foreach ($query->rows as $alert) {
            // Calculate time ago
            $alert['time_ago'] = $this->timeAgo($alert['created_at']);

            // Determine alert icon and color
            $alert['icon'] = $this->getAlertIcon($alert['alert_type']);
            $alert['color'] = $this->getAlertColor($alert['priority']);

            // Check if alert is new (less than 24 hours)
            $alert['is_new'] = (strtotime($alert['created_at']) > (time() - 86400));

            $alerts[] = $alert;
        }

        return $alerts;
    }

    /**
     * Get alerts summary statistics
     *
     * @return array
     */
    public function getAlertsSummary() {
        $user_id = (int)$this->user->getId();

        $sql = "SELECT
            COUNT(*) as total_alerts,
            SUM(CASE WHEN (ar.is_read = 0 OR ar.is_read IS NULL) AND (ar.is_dismissed = 0 OR ar.is_dismissed IS NULL) THEN 1 ELSE 0 END) as unread_alerts,
            SUM(CASE WHEN ar.is_read = 1 THEN 1 ELSE 0 END) as read_alerts,
            SUM(CASE WHEN ar.is_dismissed = 1 THEN 1 ELSE 0 END) as dismissed_alerts,
            SUM(CASE WHEN a.priority = 'critical' AND (ar.is_dismissed = 0 OR ar.is_dismissed IS NULL) THEN 1 ELSE 0 END) as critical_alerts,
            SUM(CASE WHEN a.priority = 'high' AND (ar.is_dismissed = 0 OR ar.is_dismissed IS NULL) THEN 1 ELSE 0 END) as high_alerts
            FROM " . DB_PREFIX . "dashboard_alerts a
            LEFT JOIN " . DB_PREFIX . "dashboard_alert_recipients ar ON (a.alert_id = ar.alert_id AND ar.user_id = '" . $user_id . "')
            WHERE (a.target_type = 'all' OR ar.user_id = '" . $user_id . "')
            AND (a.expires_at IS NULL OR a.expires_at > NOW())";

        $query = $this->db->query($sql);

        if ($query->num_rows) {
            return $query->row;
        }

        return array(
            'total_alerts' => 0,
            'unread_alerts' => 0,
            'read_alerts' => 0,
            'dismissed_alerts' => 0,
            'critical_alerts' => 0,
            'high_alerts' => 0
        );
    }

    /**
     * Get system-generated alerts
     *
     * @return array
     */
    public function getSystemAlerts() {
        $alerts = array();

        // Low stock alerts
        $low_stock = $this->getLowStockAlerts();
        if ($low_stock) {
            $alerts = array_merge($alerts, $low_stock);
        }

        // Overdue payments alerts
        $overdue_payments = $this->getOverduePaymentsAlerts();
        if ($overdue_payments) {
            $alerts = array_merge($alerts, $overdue_payments);
        }

        // Pending orders alerts
        $pending_orders = $this->getPendingOrdersAlerts();
        if ($pending_orders) {
            $alerts = array_merge($alerts, $pending_orders);
        }

        // Goal deadline alerts
        $goal_deadlines = $this->getGoalDeadlineAlerts();
        if ($goal_deadlines) {
            $alerts = array_merge($alerts, $goal_deadlines);
        }

        // System performance alerts
        $system_performance = $this->getSystemPerformanceAlerts();
        if ($system_performance) {
            $alerts = array_merge($alerts, $system_performance);
        }

        return $alerts;
    }

    /**
     * Create new alert
     *
     * @param array $data
     * @param int $created_by
     * @return int
     */
    public function createAlert($data, $created_by) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "dashboard_alerts SET
            alert_type = '" . $this->db->escape($data['alert_type']) . "',
            title = '" . $this->db->escape($data['title']) . "',
            message = '" . $this->db->escape($data['message']) . "',
            priority = '" . $this->db->escape($data['priority']) . "',
            target_type = '" . (empty($data['target_users']) ? 'all' : 'specific') . "',
            expires_at = " . (isset($data['expires_at']) && $data['expires_at'] ? "'" . $this->db->escape($data['expires_at']) . "'" : "NULL") . ",
            created_by = '" . (int)$created_by . "',
            created_at = NOW()");

        $alert_id = $this->db->getLastId();

        // Add recipients if specific users are targeted
        if (!empty($data['target_users']) && is_array($data['target_users'])) {
            foreach ($data['target_users'] as $user_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "dashboard_alert_recipients SET
                    alert_id = '" . (int)$alert_id . "',
                    user_id = '" . (int)$user_id . "',
                    is_read = 0,
                    is_dismissed = 0");
            }
        }

        return $alert_id;
    }

    /**
     * Mark alert as read
     *
     * @param int $alert_id
     * @param int $user_id
     * @return void
     */
    public function markAsRead($alert_id, $user_id) {
        // Check if recipient record exists
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "dashboard_alert_recipients
            WHERE alert_id = '" . (int)$alert_id . "' AND user_id = '" . (int)$user_id . "'");

        if ($query->num_rows) {
            // Update existing record
            $this->db->query("UPDATE " . DB_PREFIX . "dashboard_alert_recipients SET
                is_read = 1,
                read_at = NOW()
                WHERE alert_id = '" . (int)$alert_id . "' AND user_id = '" . (int)$user_id . "'");
        } else {
            // Create new record
            $this->db->query("INSERT INTO " . DB_PREFIX . "dashboard_alert_recipients SET
                alert_id = '" . (int)$alert_id . "',
                user_id = '" . (int)$user_id . "',
                is_read = 1,
                read_at = NOW(),
                is_dismissed = 0");
        }
    }

    /**
     * Mark all alerts as read for user
     *
     * @param int $user_id
     * @return void
     */
    public function markAllAsRead($user_id) {
        // Get all alerts for user
        $query = $this->db->query("SELECT a.alert_id FROM " . DB_PREFIX . "dashboard_alerts a
            LEFT JOIN " . DB_PREFIX . "dashboard_alert_recipients ar ON (a.alert_id = ar.alert_id AND ar.user_id = '" . (int)$user_id . "')
            WHERE (a.target_type = 'all' OR ar.user_id = '" . (int)$user_id . "')
            AND (a.expires_at IS NULL OR a.expires_at > NOW())
            AND (ar.is_read = 0 OR ar.is_read IS NULL)");

        foreach ($query->rows as $alert) {
            $this->markAsRead($alert['alert_id'], $user_id);
        }
    }

    /**
     * Dismiss alert
     *
     * @param int $alert_id
     * @param int $user_id
     * @return void
     */
    public function dismissAlert($alert_id, $user_id) {
        // Check if recipient record exists
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "dashboard_alert_recipients
            WHERE alert_id = '" . (int)$alert_id . "' AND user_id = '" . (int)$user_id . "'");

        if ($query->num_rows) {
            // Update existing record
            $this->db->query("UPDATE " . DB_PREFIX . "dashboard_alert_recipients SET
                is_dismissed = 1,
                dismissed_at = NOW()
                WHERE alert_id = '" . (int)$alert_id . "' AND user_id = '" . (int)$user_id . "'");
        } else {
            // Create new record
            $this->db->query("INSERT INTO " . DB_PREFIX . "dashboard_alert_recipients SET
                alert_id = '" . (int)$alert_id . "',
                user_id = '" . (int)$user_id . "',
                is_read = 1,
                read_at = NOW(),
                is_dismissed = 1,
                dismissed_at = NOW()");
        }
    }

    /**
     * Get unread alerts count for user
     *
     * @param int $user_id
     * @return int
     */
    public function getUnreadCount($user_id) {
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "dashboard_alerts a
            LEFT JOIN " . DB_PREFIX . "dashboard_alert_recipients ar ON (a.alert_id = ar.alert_id AND ar.user_id = '" . (int)$user_id . "')
            WHERE (a.target_type = 'all' OR ar.user_id = '" . (int)$user_id . "')
            AND (a.expires_at IS NULL OR a.expires_at > NOW())
            AND (ar.is_read = 0 OR ar.is_read IS NULL)
            AND (ar.is_dismissed = 0 OR ar.is_dismissed IS NULL)");

        return $query->row['total'];
    }

    /**
     * Generate system alerts
     *
     * @return void
     */
    public function generateSystemAlerts() {
        // Clean up old system alerts
        $this->db->query("DELETE FROM " . DB_PREFIX . "dashboard_alerts
            WHERE alert_type IN ('low_stock', 'overdue_payment', 'pending_order', 'goal_deadline', 'system_performance')
            AND created_at < DATE_SUB(NOW(), INTERVAL 1 DAY)");

        // Generate new system alerts
        $this->generateLowStockAlerts();
        $this->generateOverduePaymentAlerts();
        $this->generatePendingOrderAlerts();
        $this->generateGoalDeadlineAlerts();
        $this->generateSystemPerformanceAlerts();
    }

    /**
     * Get low stock alerts
     *
     * @return array
     */
    private function getLowStockAlerts() {
        $alerts = array();

        $query = $this->db->query("SELECT COUNT(*) as count FROM " . DB_PREFIX . "product p
            LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id)
            WHERE p.status = 1 AND p.quantity <= p.minimum AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'");

        if ($query->row['count'] > 0) {
            $alerts[] = array(
                'type' => 'low_stock',
                'title' => 'تحذير: أصناف منخفضة المخزون',
                'message' => 'يوجد ' . $query->row['count'] . ' صنف وصل للحد الأدنى من المخزون',
                'priority' => 'high',
                'count' => $query->row['count'],
                'icon' => 'fa-exclamation-triangle',
                'color' => 'warning'
            );
        }

        return $alerts;
    }

    /**
     * Get overdue payments alerts
     *
     * @return array
     */
    private function getOverduePaymentsAlerts() {
        $alerts = array();

        // This would need to be implemented based on your payment/invoice system
        // For now, returning empty array

        return $alerts;
    }

    /**
     * Get pending orders alerts
     *
     * @return array
     */
    private function getPendingOrdersAlerts() {
        $alerts = array();

        $query = $this->db->query("SELECT COUNT(*) as count FROM " . DB_PREFIX . "order
            WHERE order_status_id IN (1, 2) AND store_id = '" . (int)$this->config->get('config_store_id') . "'");

        if ($query->row['count'] > 10) {
            $alerts[] = array(
                'type' => 'pending_orders',
                'title' => 'تنبيه: طلبات معلقة',
                'message' => 'يوجد ' . $query->row['count'] . ' طلب في انتظار المعالجة',
                'priority' => 'medium',
                'count' => $query->row['count'],
                'icon' => 'fa-clock-o',
                'color' => 'info'
            );
        }

        return $alerts;
    }

    /**
     * Get goal deadline alerts
     *
     * @return array
     */
    private function getGoalDeadlineAlerts() {
        $alerts = array();

        // Check if goals table exists
        $query = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "dashboard_goals'");

        if ($query->num_rows) {
            $query = $this->db->query("SELECT COUNT(*) as count FROM " . DB_PREFIX . "dashboard_goals
                WHERE end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                AND status = 'active'");

            if ($query->row['count'] > 0) {
                $alerts[] = array(
                    'type' => 'goal_deadline',
                    'title' => 'تذكير: مواعيد نهائية للأهداف',
                    'message' => 'يوجد ' . $query->row['count'] . ' هدف ينتهي خلال الأسبوع القادم',
                    'priority' => 'medium',
                    'count' => $query->row['count'],
                    'icon' => 'fa-target',
                    'color' => 'warning'
                );
            }
        }

        return $alerts;
    }

    /**
     * Get system performance alerts
     *
     * @return array
     */
    private function getSystemPerformanceAlerts() {
        $alerts = array();

        // Check disk space (if possible)
        if (function_exists('disk_free_space')) {
            $free_space = disk_free_space('/');
            $total_space = disk_total_space('/');

            if ($free_space && $total_space) {
                $usage_percent = (($total_space - $free_space) / $total_space) * 100;

                if ($usage_percent > 90) {
                    $alerts[] = array(
                        'type' => 'system_performance',
                        'title' => 'تحذير: مساحة القرص منخفضة',
                        'message' => 'مساحة القرص المستخدمة: ' . round($usage_percent, 1) . '%',
                        'priority' => 'high',
                        'icon' => 'fa-hdd-o',
                        'color' => 'danger'
                    );
                }
            }
        }

        return $alerts;
    }

    /**
     * Generate low stock alerts
     */
    private function generateLowStockAlerts() {
        $alerts = $this->getLowStockAlerts();

        foreach ($alerts as $alert) {
            // Check if similar alert already exists today
            $existing = $this->db->query("SELECT alert_id FROM " . DB_PREFIX . "dashboard_alerts
                WHERE alert_type = 'low_stock' AND DATE(created_at) = CURDATE()");

            if (!$existing->num_rows) {
                $this->createAlert(array(
                    'alert_type' => 'low_stock',
                    'title' => $alert['title'],
                    'message' => $alert['message'],
                    'priority' => $alert['priority']
                ), 1); // System user
            }
        }
    }

    /**
     * Generate other system alerts (placeholder methods)
     */
    private function generateOverduePaymentAlerts() {
        // Implementation would depend on your payment system
    }

    private function generatePendingOrderAlerts() {
        $alerts = $this->getPendingOrdersAlerts();

        foreach ($alerts as $alert) {
            $existing = $this->db->query("SELECT alert_id FROM " . DB_PREFIX . "dashboard_alerts
                WHERE alert_type = 'pending_orders' AND DATE(created_at) = CURDATE()");

            if (!$existing->num_rows) {
                $this->createAlert(array(
                    'alert_type' => 'pending_orders',
                    'title' => $alert['title'],
                    'message' => $alert['message'],
                    'priority' => $alert['priority']
                ), 1);
            }
        }
    }

    private function generateGoalDeadlineAlerts() {
        $alerts = $this->getGoalDeadlineAlerts();

        foreach ($alerts as $alert) {
            $existing = $this->db->query("SELECT alert_id FROM " . DB_PREFIX . "dashboard_alerts
                WHERE alert_type = 'goal_deadline' AND DATE(created_at) = CURDATE()");

            if (!$existing->num_rows) {
                $this->createAlert(array(
                    'alert_type' => 'goal_deadline',
                    'title' => $alert['title'],
                    'message' => $alert['message'],
                    'priority' => $alert['priority']
                ), 1);
            }
        }
    }

    private function generateSystemPerformanceAlerts() {
        $alerts = $this->getSystemPerformanceAlerts();

        foreach ($alerts as $alert) {
            $existing = $this->db->query("SELECT alert_id FROM " . DB_PREFIX . "dashboard_alerts
                WHERE alert_type = 'system_performance' AND DATE(created_at) = CURDATE()");

            if (!$existing->num_rows) {
                $this->createAlert(array(
                    'alert_type' => 'system_performance',
                    'title' => $alert['title'],
                    'message' => $alert['message'],
                    'priority' => $alert['priority']
                ), 1);
            }
        }
    }

    /**
     * Helper function to calculate time ago
     *
     * @param string $datetime
     * @return string
     */
    private function timeAgo($datetime) {
        $time = time() - strtotime($datetime);

        if ($time < 60) return 'الآن';
        if ($time < 3600) return floor($time/60) . ' دقيقة';
        if ($time < 86400) return floor($time/3600) . ' ساعة';
        if ($time < 2592000) return floor($time/86400) . ' يوم';
        if ($time < 31536000) return floor($time/2592000) . ' شهر';

        return floor($time/31536000) . ' سنة';
    }

    /**
     * Get alert icon based on type
     *
     * @param string $type
     * @return string
     */
    private function getAlertIcon($type) {
        $icons = array(
            'low_stock' => 'fa-exclamation-triangle',
            'overdue_payment' => 'fa-money',
            'pending_order' => 'fa-clock-o',
            'goal_deadline' => 'fa-target',
            'system_performance' => 'fa-server',
            'custom' => 'fa-bell',
            'info' => 'fa-info-circle',
            'warning' => 'fa-exclamation-triangle',
            'error' => 'fa-times-circle',
            'success' => 'fa-check-circle'
        );

        return isset($icons[$type]) ? $icons[$type] : 'fa-bell';
    }

    /**
     * Get alert color based on priority
     *
     * @param string $priority
     * @return string
     */
    private function getAlertColor($priority) {
        $colors = array(
            'low' => 'info',
            'medium' => 'warning',
            'high' => 'danger',
            'critical' => 'danger'
        );

        return isset($colors[$priority]) ? $colors[$priority] : 'info';
    }
}
