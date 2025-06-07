<?php
class ModelPurchaseOrderTracking extends Model {

    /**
     * الحصول على قائمة أوامر الشراء مع معلومات التتبع
     */
    public function getOrders($data = array()) {
        $sql = "SELECT po.po_id, po.po_number, po.order_date, po.total_amount, po.status,
                       s.firstname, s.lastname, CONCAT(s.firstname, ' ', COALESCE(s.lastname, '')) AS supplier_name,
                       c.code AS currency_code,
                       pot.expected_delivery_date, pot.actual_delivery_date,
                       (SELECT status_change FROM " . DB_PREFIX . "purchase_order_tracking pot2 
                        WHERE pot2.po_id = po.po_id 
                        ORDER BY pot2.status_date DESC LIMIT 1) AS current_status
                FROM " . DB_PREFIX . "purchase_order po
                LEFT JOIN " . DB_PREFIX . "supplier s ON (po.supplier_id = s.supplier_id)
                LEFT JOIN " . DB_PREFIX . "currency c ON (po.currency_id = c.currency_id)
                LEFT JOIN (
                    SELECT po_id, 
                           MAX(CASE WHEN expected_delivery_date IS NOT NULL THEN expected_delivery_date END) AS expected_delivery_date,
                           MAX(CASE WHEN actual_delivery_date IS NOT NULL THEN actual_delivery_date END) AS actual_delivery_date
                    FROM " . DB_PREFIX . "purchase_order_tracking 
                    GROUP BY po_id
                ) pot ON (po.po_id = pot.po_id)
                WHERE 1 = 1";

        if (!empty($data['filter_po_number'])) {
            $sql .= " AND po.po_number LIKE '%" . $this->db->escape($data['filter_po_number']) . "%'";
        }

        if (!empty($data['filter_supplier_id'])) {
            $sql .= " AND po.supplier_id = '" . (int)$data['filter_supplier_id'] . "'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND po.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(po.order_date) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(po.order_date) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $sort_data = array(
            'po.po_number',
            'po.order_date',
            'supplier_name',
            'po.total_amount',
            'po.status',
            'pot.expected_delivery_date',
            'pot.actual_delivery_date'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY po.order_date";
        }

        if (isset($data['order']) && ($data['order'] == 'ASC')) {
            $sql .= " ASC";
        } else {
            $sql .= " DESC";
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
     * الحصول على إجمالي عدد أوامر الشراء
     */
    public function getTotalOrders($data = array()) {
        $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "purchase_order po
                LEFT JOIN " . DB_PREFIX . "supplier s ON (po.supplier_id = s.supplier_id)
                WHERE 1 = 1";

        if (!empty($data['filter_po_number'])) {
            $sql .= " AND po.po_number LIKE '%" . $this->db->escape($data['filter_po_number']) . "%'";
        }

        if (!empty($data['filter_supplier_id'])) {
            $sql .= " AND po.supplier_id = '" . (int)$data['filter_supplier_id'] . "'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND po.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(po.order_date) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(po.order_date) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    /**
     * الحصول على معلومات أمر شراء واحد
     */
    public function getOrder($po_id) {
        $query = $this->db->query("SELECT po.*, 
                                          s.firstname, s.lastname, CONCAT(s.firstname, ' ', COALESCE(s.lastname, '')) AS supplier_name,
                                          s.email AS supplier_email, s.telephone AS supplier_telephone,
                                          c.code AS currency_code, c.symbol_left, c.symbol_right
                                   FROM " . DB_PREFIX . "purchase_order po
                                   LEFT JOIN " . DB_PREFIX . "supplier s ON (po.supplier_id = s.supplier_id)
                                   LEFT JOIN " . DB_PREFIX . "currency c ON (po.currency_id = c.currency_id)
                                   WHERE po.po_id = '" . (int)$po_id . "'");

        return $query->row;
    }

    /**
     * الحصول على سجل تتبع أمر شراء
     */
    public function getTrackingHistory($po_id) {
        $query = $this->db->query("SELECT pot.*, 
                                          u.firstname, u.lastname, CONCAT(u.firstname, ' ', COALESCE(u.lastname, '')) AS created_by_name
                                   FROM " . DB_PREFIX . "purchase_order_tracking pot
                                   LEFT JOIN " . DB_PREFIX . "user u ON (pot.created_by = u.user_id)
                                   WHERE pot.po_id = '" . (int)$po_id . "'
                                   ORDER BY pot.status_date DESC");

        return $query->rows;
    }

    /**
     * إضافة سجل تتبع جديد
     */
    public function addTracking($po_id, $data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "purchase_order_tracking SET 
                         po_id = '" . (int)$po_id . "',
                         status_change = '" . $this->db->escape($data['status_change']) . "',
                         status_date = NOW(),
                         expected_delivery_date = " . (!empty($data['expected_delivery_date']) ? "'" . $this->db->escape($data['expected_delivery_date']) . "'" : "NULL") . ",
                         actual_delivery_date = " . (!empty($data['actual_delivery_date']) ? "'" . $this->db->escape($data['actual_delivery_date']) . "'" : "NULL") . ",
                         notes = '" . $this->db->escape($data['notes']) . "',
                         created_by = '" . (int)$data['created_by'] . "'");

        $tracking_id = $this->db->getLastId();

        // تحديث حالة أمر الشراء إذا لزم الأمر
        $this->updateOrderStatus($po_id, $data['status_change']);

        return $tracking_id;
    }

    /**
     * تحديث سجل التتبع
     */
    public function updateTracking($po_id, $data) {
        // إضافة سجل تتبع جديد
        return $this->addTracking($po_id, $data);
    }

    /**
     * تحديث حالة أمر الشراء بناءً على حالة التتبع
     */
    private function updateOrderStatus($po_id, $tracking_status) {
        $order_status_mapping = array(
            'created' => 'pending',
            'sent_to_vendor' => 'sent',
            'confirmed_by_vendor' => 'confirmed',
            'partially_received' => 'partially_received',
            'fully_received' => 'received',
            'cancelled' => 'cancelled',
            'closed' => 'closed'
        );

        if (isset($order_status_mapping[$tracking_status])) {
            $new_status = $order_status_mapping[$tracking_status];
            
            $this->db->query("UPDATE " . DB_PREFIX . "purchase_order 
                             SET status = '" . $this->db->escape($new_status) . "',
                                 date_modified = NOW()
                             WHERE po_id = '" . (int)$po_id . "'");
        }
    }

    /**
     * الحصول على آخر حالة تتبع لأمر شراء
     */
    public function getLatestTrackingStatus($po_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "purchase_order_tracking 
                                   WHERE po_id = '" . (int)$po_id . "'
                                   ORDER BY status_date DESC 
                                   LIMIT 1");

        return $query->row;
    }

    /**
     * الحصول على إحصائيات التتبع
     */
    public function getTrackingStatistics($date_start = '', $date_end = '') {
        $sql = "SELECT 
                    status_change,
                    COUNT(*) as count,
                    AVG(DATEDIFF(COALESCE(actual_delivery_date, CURDATE()), expected_delivery_date)) as avg_delay_days
                FROM " . DB_PREFIX . "purchase_order_tracking pot
                INNER JOIN " . DB_PREFIX . "purchase_order po ON (pot.po_id = po.po_id)
                WHERE 1 = 1";

        if (!empty($date_start)) {
            $sql .= " AND DATE(pot.status_date) >= '" . $this->db->escape($date_start) . "'";
        }

        if (!empty($date_end)) {
            $sql .= " AND DATE(pot.status_date) <= '" . $this->db->escape($date_end) . "'";
        }

        $sql .= " GROUP BY status_change
                  ORDER BY count DESC";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    /**
     * الحصول على أوامر الشراء المتأخرة
     */
    public function getOverdueOrders() {
        $query = $this->db->query("SELECT po.po_id, po.po_number, po.order_date,
                                          s.firstname, s.lastname, CONCAT(s.firstname, ' ', COALESCE(s.lastname, '')) AS supplier_name,
                                          pot.expected_delivery_date,
                                          DATEDIFF(CURDATE(), pot.expected_delivery_date) as days_overdue
                                   FROM " . DB_PREFIX . "purchase_order po
                                   INNER JOIN " . DB_PREFIX . "supplier s ON (po.supplier_id = s.supplier_id)
                                   INNER JOIN " . DB_PREFIX . "purchase_order_tracking pot ON (po.po_id = pot.po_id)
                                   WHERE pot.expected_delivery_date < CURDATE()
                                   AND po.status NOT IN ('received', 'cancelled', 'closed')
                                   AND pot.actual_delivery_date IS NULL
                                   ORDER BY days_overdue DESC");

        return $query->rows;
    }

    /**
     * الحصول على أوامر الشراء المتوقع تسليمها قريباً
     */
    public function getUpcomingDeliveries($days = 7) {
        $query = $this->db->query("SELECT po.po_id, po.po_number, po.order_date,
                                          s.firstname, s.lastname, CONCAT(s.firstname, ' ', COALESCE(s.lastname, '')) AS supplier_name,
                                          pot.expected_delivery_date,
                                          DATEDIFF(pot.expected_delivery_date, CURDATE()) as days_until_delivery
                                   FROM " . DB_PREFIX . "purchase_order po
                                   INNER JOIN " . DB_PREFIX . "supplier s ON (po.supplier_id = s.supplier_id)
                                   INNER JOIN " . DB_PREFIX . "purchase_order_tracking pot ON (po.po_id = pot.po_id)
                                   WHERE pot.expected_delivery_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL " . (int)$days . " DAY)
                                   AND po.status NOT IN ('received', 'cancelled', 'closed')
                                   AND pot.actual_delivery_date IS NULL
                                   ORDER BY pot.expected_delivery_date ASC");

        return $query->rows;
    }

    /**
     * تحديث تاريخ التسليم المتوقع
     */
    public function updateExpectedDeliveryDate($po_id, $expected_date, $notes = '') {
        $this->db->query("INSERT INTO " . DB_PREFIX . "purchase_order_tracking SET 
                         po_id = '" . (int)$po_id . "',
                         status_change = 'delivery_date_updated',
                         status_date = NOW(),
                         expected_delivery_date = '" . $this->db->escape($expected_date) . "',
                         notes = '" . $this->db->escape($notes) . "',
                         created_by = '" . (int)$this->user->getId() . "'");

        return $this->db->getLastId();
    }

    /**
     * تحديث تاريخ التسليم الفعلي
     */
    public function updateActualDeliveryDate($po_id, $actual_date, $notes = '') {
        $this->db->query("INSERT INTO " . DB_PREFIX . "purchase_order_tracking SET 
                         po_id = '" . (int)$po_id . "',
                         status_change = 'delivery_completed',
                         status_date = NOW(),
                         actual_delivery_date = '" . $this->db->escape($actual_date) . "',
                         notes = '" . $this->db->escape($notes) . "',
                         created_by = '" . (int)$this->user->getId() . "'");

        // تحديث حالة أمر الشراء إلى مستلم بالكامل
        $this->updateOrderStatus($po_id, 'fully_received');

        return $this->db->getLastId();
    }
}
