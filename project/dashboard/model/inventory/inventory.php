<?php
class ModelInventoryInventory extends Model {

    // إحضار القائمة الرئيسية للمخزون + حساب total_value
    public function getInventoryList($data = array()) {
        $sql = "SELECT
                    pi.product_inventory_id,
                    b.name AS branch_name,
                    pd.name AS product_name,
                    u.desc_en AS unit_name,
                    pi.quantity,
                    pi.average_cost,
                    (pi.quantity * pi.average_cost) AS total_value,
                    pi.is_consignment
                FROM " . DB_PREFIX . "product_inventory pi
                LEFT JOIN " . DB_PREFIX . "branch b
                    ON (pi.branch_id = b.branch_id)
                LEFT JOIN " . DB_PREFIX . "product_description pd
                    ON (pi.product_id = pd.product_id AND pd.language_id = '1')
                LEFT JOIN " . DB_PREFIX . "unit u
                    ON (pi.unit_id = u.unit_id)
                WHERE 1 ";

        // ---- الفلاتر ----
        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND pi.branch_id = '".(int)$data['filter_branch_id']."' ";
        }
        if (!empty($data['filter_product_id'])) {
            $sql .= " AND pi.product_id = '".(int)$data['filter_product_id']."' ";
        }
        if (isset($data['filter_consignment']) && $data['filter_consignment'] !== null && $data['filter_consignment'] !== '') {
            $sql .= " AND pi.is_consignment = '".(int)$data['filter_consignment']."' ";
        }
        // البحث العام
        if (!empty($data['search'])) {
            $search = $this->db->escape($data['search']);
            $sql .= " AND (b.name LIKE '%{$search}%'
                       OR pd.name LIKE '%{$search}%'
                       OR u.desc_en LIKE '%{$search}%'
                       ) ";
        }

        // ---- الفرز ----
        $sort_data = array(
            'branch_name',
            'product_name',
            'unit_name',
            'quantity',
            'average_cost',
            'total_value'
        );
        $sort = isset($data['sort']) && in_array($data['sort'], $sort_data) ? $data['sort'] : 'branch_name';
        $order = (isset($data['order']) && strtoupper($data['order'])=='DESC') ? 'DESC' : 'ASC';

        $sql .= " ORDER BY ".$sort." ".$order;

        // ---- الصفحات ----
        if (isset($data['start']) || isset($data['limit'])) {
            $start = isset($data['start']) ? (int)$data['start'] : 0;
            $limit = isset($data['limit']) ? (int)$data['limit'] : 20;

            if ($start < 0) $start = 0;
            if ($limit < 1) $limit = 20;

            $sql .= " LIMIT ".(int)$start.",".(int)$limit;
        }

        $query = $this->db->query($sql);
        return $query->rows;
    }

    // إحضار العدد الإجمالي (لـDataTables)
    public function getTotalInventory($data = array()) {
        $sql = "SELECT COUNT(*) as total
                FROM " . DB_PREFIX . "product_inventory pi
                LEFT JOIN " . DB_PREFIX . "branch b
                    ON (pi.branch_id = b.branch_id)
                LEFT JOIN " . DB_PREFIX . "product_description pd
                    ON (pi.product_id = pd.product_id AND pd.language_id='1')
                LEFT JOIN " . DB_PREFIX . "unit u
                    ON (pi.unit_id = u.unit_id)
                WHERE 1 ";

        // نفس فلاتر getInventoryList
        if (!empty($data['filter_branch_id'])) {
            $sql .= " AND pi.branch_id = '".(int)$data['filter_branch_id']."' ";
        }
        if (!empty($data['filter_product_id'])) {
            $sql .= " AND pi.product_id = '".(int)$data['filter_product_id']."' ";
        }
        if (isset($data['filter_consignment']) && $data['filter_consignment'] !== null && $data['filter_consignment'] !== '') {
            $sql .= " AND pi.is_consignment = '".(int)$data['filter_consignment']."' ";
        }
        if (!empty($data['search'])) {
            $search = $this->db->escape($data['search']);
            $sql .= " AND (b.name LIKE '%{$search}%'
                       OR pd.name LIKE '%{$search}%'
                       OR u.desc_en LIKE '%{$search}%'
                       ) ";
        }

        $query = $this->db->query($sql);
        return $query->row['total'];
    }

    /**
     * الحصول على كمية المنتج المتاحة في فرع معين
     *
     * @param int $branch_id معرف الفرع
     * @param int $product_id معرف المنتج
     * @param int $unit_id معرف الوحدة
     * @return float الكمية المتاحة
     */
    public function getAvailableQuantity($branch_id, $product_id, $unit_id) {
        $query = $this->db->query("SELECT quantity
            FROM " . DB_PREFIX . "product_inventory
            WHERE branch_id = '" . (int)$branch_id . "'
            AND product_id = '" . (int)$product_id . "'
            AND unit_id = '" . (int)$unit_id . "'");

        if ($query->num_rows) {
            return (float)$query->row['quantity'];
        }

        return 0;
    }

    /**
     * التحقق من توفر الكمية المطلوبة في المخزون
     *
     * @param int $branch_id معرف الفرع
     * @param int $product_id معرف المنتج
     * @param int $unit_id معرف الوحدة
     * @param float $quantity الكمية المطلوبة
     * @return bool
     */
    public function checkQuantityAvailable($branch_id, $product_id, $unit_id, $quantity) {
        $available = $this->getAvailableQuantity($branch_id, $product_id, $unit_id);

        return $available >= $quantity;
    }

    /**
     * الحصول على معلومات المنتج في المخزون
     *
     * @param int $branch_id معرف الفرع
     * @param int $product_id معرف المنتج
     * @param int $unit_id معرف الوحدة
     * @return array
     */
    public function getProductInventory($branch_id, $product_id, $unit_id) {
        $query = $this->db->query("SELECT pi.*,
                pd.name AS product_name,
                u.desc_en AS unit_name,
                b.name AS branch_name
            FROM " . DB_PREFIX . "product_inventory pi
            LEFT JOIN " . DB_PREFIX . "product_description pd
                ON (pi.product_id = pd.product_id AND pd.language_id = '1')
            LEFT JOIN " . DB_PREFIX . "unit u
                ON (pi.unit_id = u.unit_id)
            LEFT JOIN " . DB_PREFIX . "branch b
                ON (pi.branch_id = b.branch_id)
            WHERE pi.branch_id = '" . (int)$branch_id . "'
            AND pi.product_id = '" . (int)$product_id . "'
            AND pi.unit_id = '" . (int)$unit_id . "'");

        return $query->row;
    }
}
