<?php
class ModelInventoryStockCount extends Model {

    /**
     * إحضار جميع جلسات الجرد
     */
    public function getStockCounts() {
        $sql = "SELECT sc.*,
                       b.name AS branch_name,
                       u.firstname AS user_fname,
                       u.lastname AS user_lname
                FROM `cod_stock_count` sc
                LEFT JOIN `cod_branch` b ON (sc.branch_id = b.branch_id)
                LEFT JOIN `cod_user`   u ON (sc.created_by = u.user_id)
                ORDER BY sc.stock_count_id DESC";

        $query = $this->db->query($sql);

        $result = array();
        foreach ($query->rows as $row) {
            $row['created_by_name'] = $row['user_fname'] . ' ' . $row['user_lname'];
            $result[] = $row;
        }
        return $result;
    }

    /**
     * جلب بيانات جلسة جرد واحدة
     *
     * @param int $stock_count_id
     * @return array
     */
    public function getStockCount($stock_count_id) {
        $sql = "SELECT sc.*,
                       b.name AS branch_name,
                       u.firstname AS user_fname,
                       u.lastname AS user_lname
                FROM `cod_stock_count` sc
                LEFT JOIN `cod_branch` b ON (sc.branch_id = b.branch_id)
                LEFT JOIN `cod_user`   u ON (sc.created_by = u.user_id)
                WHERE sc.stock_count_id = '" . (int)$stock_count_id . "'";

        $query = $this->db->query($sql);
        return $query->row;
    }

    /**
     * إضافة جلسة جرد
     *
     * @param array $data
     * @return int  المعرف (ID) للجلسة الجديدة
     */
    public function addStockCount($data) {
        // إدراج رأس الجرد
        $this->db->query("INSERT INTO `cod_stock_count` SET
            branch_id      = '" . (int)$data['branch_id'] . "',
            reference_code = '" . $this->db->escape($data['reference_code']) . "',
            count_date     = '" . $this->db->escape($data['count_date']) . "',
            notes          = '" . $this->db->escape($data['notes']) . "',
            created_by     = '" . (int)$this->user->getId() . "',
            created_at     = NOW(),
            status         = 'draft'
        ");

        $stock_count_id = $this->db->getLastId();

        // إضافة البنود
        if (!empty($data['items'])) {
            foreach ($data['items'] as $item) {
                $this->insertStockCountItem($stock_count_id, $data['branch_id'], $item);
            }
        }

        return $stock_count_id;
    }

    /**
     * تعديل جلسة جرد
     *
     * @param int   $stock_count_id
     * @param array $data
     * @return void
     */
    public function editStockCount($stock_count_id, $data) {
        // تحديث رأس الجرد
        $this->db->query("UPDATE `cod_stock_count` SET
            branch_id      = '" . (int)$data['branch_id'] . "',
            reference_code = '" . $this->db->escape($data['reference_code']) . "',
            count_date     = '" . $this->db->escape($data['count_date']) . "',
            notes          = '" . $this->db->escape($data['notes']) . "',
            updated_by     = '" . (int)$this->user->getId() . "',
            updated_at     = NOW()
            WHERE stock_count_id = '" . (int)$stock_count_id . "'
        ");

        // حذف البنود السابقة المرتبطة بهذه الجلسة
        $this->db->query("DELETE FROM `cod_stock_count_item`
                          WHERE stock_count_id = '" . (int)$stock_count_id . "'");

        // إعادة الإدراج
        if (!empty($data['items'])) {
            foreach ($data['items'] as $item) {
                $this->insertStockCountItem($stock_count_id, $data['branch_id'], $item);
            }
        }
    }

    /**
     * البنود المرتبطة بجلسة جرد معينة
     *
     * @param int $stock_count_id
     * @return array
     */
    public function getStockCountItems($stock_count_id) {
        $sql = "SELECT sci.*,
                       p.model AS product_model,
                       pd.name AS product_name,
                       u.desc_en AS unit_name
                FROM `cod_stock_count_item` sci
                LEFT JOIN `cod_product` p ON (sci.product_id = p.product_id)
                LEFT JOIN `cod_product_description` pd 
                       ON (sci.product_id = pd.product_id AND pd.language_id = '1')
                LEFT JOIN `cod_unit` u ON (sci.unit_id = u.unit_id)
                WHERE sci.stock_count_id = '" . (int)$stock_count_id . "'
                ORDER BY sci.count_item_id ASC";

        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * إدراج بند جرد واحد
     * ملاحظة: يتم هنا احتساب الـ system_qty بالوحدة المختارة عبر عامل التحويل.
     *
     * @param int   $stock_count_id  معرف الجلسة
     * @param int   $branch_id       فرع/مخزن الجرد
     * @param array $item            بيانات البند
     */
    private function insertStockCountItem($stock_count_id, $branch_id, $item) {
        $product_id  = (int)$item['product_id'];
        $unit_id     = (int)$item['unit_id'];
        $counted_qty = isset($item['counted_qty']) ? (float)$item['counted_qty'] : 0.0;

        // اجلب الكمية من النظام (مخزنة بالوحدة الأساسية) ثم حوّلها إلى الوحدة المطلوبة
        $system_qty_in_unit = $this->getSystemQuantityInUnit($product_id, $branch_id, $unit_id);

        // الفرق
        $difference = $counted_qty - $system_qty_in_unit;

        $barcode = isset($item['barcode']) ? $this->db->escape($item['barcode']) : '';
        $notes   = isset($item['notes'])   ? $this->db->escape($item['notes'])   : '';

        $this->db->query("INSERT INTO `cod_stock_count_item` SET
            stock_count_id = '" . (int)$stock_count_id . "',
            product_id     = '" . $product_id . "',
            unit_id        = '" . $unit_id . "',
            system_qty     = '" . (float)$system_qty_in_unit . "',
            counted_qty    = '" . $counted_qty . "',
            difference     = '" . $difference . "',
            barcode        = '" . $barcode . "',
            notes          = '" . $notes . "'
        ");
    }

    /**
     * جلب الكمية الموجودة في النظام (بوحدة الأساس) ثم تحويلها للوحدة المطلوبة
     *
     * @param int $product_id
     * @param int $branch_id
     * @param int $unit_id
     * @return float
     */
    private function getSystemQuantityInUnit($product_id, $branch_id, $unit_id) {
        // الكمية بالوحدة الأساسية
        $base_qty = $this->getBaseQuantity($product_id, $branch_id);

        // عامل التحويل للوحدة المختارة
        $factor  = $this->getConversionFactor($product_id, $unit_id);

        // حسب الافتراض: 1 من هذه الـ(units) = $factor من الوحدة الأساسية
        // أي إذا كان factor=12، فهذا يعني 1 كرتون=12 حبة.
        // عندنا base_qty (حبات)، فنريد تحويله إلى عدد الكراتين => (base_qty / factor).
        // (يمكن قلب المعادلة لو كان أسلوب التخزين مختلف)
        if ($factor > 0) {
            return (float)($base_qty / $factor);
        } else {
            // لو كان 0 أو غير موجود، نعيد القيمـة كما هي 
            return (float)$base_qty;
        }
    }

    /**
     * جلب الكمية المخزنة في النظام (بالوحدة الأساسية)
     *
     * @param int $product_id
     * @param int $branch_id
     * @return float
     */
    private function getBaseQuantity($product_id, $branch_id) {
        $sql = "SELECT quantity
                FROM `cod_product_inventory`
                WHERE product_id = '" . (int)$product_id . "'
                  AND branch_id  = '" . (int)$branch_id . "'
                LIMIT 1";

        $query = $this->db->query($sql);
        if ($query->num_rows) {
            return (float)$query->row['quantity'];
        }
        return 0.0;
    }

    /**
     * جلب عامل التحويل لوحدة معيّنة لهذا المنتج
     * إن لم يوجد، نرجع 1 (أي وحدة الأساس نفسها)
     *
     * @param int $product_id
     * @param int $unit_id
     * @return float
     */
    private function getConversionFactor($product_id, $unit_id) {
        $sql = "SELECT conversion_factor
                FROM `cod_product_unit`
                WHERE product_id = '" . (int)$product_id . "'
                  AND unit_id    = '" . (int)$unit_id . "'
                LIMIT 1";
        $query = $this->db->query($sql);

        if ($query->num_rows) {
            return (float)$query->row['conversion_factor'];
        } else {
            return 1.0; // في حال لم يكن موجوداً، نفترض أن العامل = 1
        }
    }

    /**
     * إتمام (إغلاق) الجرد
     *
     * @param int $stock_count_id
     * @return array
     */
    public function completeStockCount($stock_count_id) {
        // تحقق من وجود الجلسة
        $sql = "SELECT * FROM `cod_stock_count`
                WHERE stock_count_id = '" . (int)$stock_count_id . "'";
        $query = $this->db->query($sql);
        if (!$query->num_rows) {
            return array('error' => 'Invalid stock_count_id', 'success' => '');
        }

        // جلب البنود
        $items = $this->getStockCountItems($stock_count_id);
        if (!$items) {
            return array('error' => $this->language->get('error_no_items'), 'success' => '');
        }

        // في حال أردت عمل تسويات أو قيود...
        // $this->createStockAdjustments($items, $query->row['branch_id']);

        // تحديث الحالة
        $this->db->query("UPDATE `cod_stock_count`
                          SET status = 'completed',
                              updated_by = '" . (int)$this->user->getId() . "',
                              updated_at = NOW()
                          WHERE stock_count_id = '" . (int)$stock_count_id . "'");

        return array('error' => '', 'success' => $this->language->get('text_success'));
    }

    /**
     * بحث في المنتجات والباركود (للاستخدام مع Select2 مثلاً)
     *
     * @param array $filter_data [
     *   'filter_keyword' => '',
     *   'branch_id' => 0,
     *   'start' => 0,
     *   'limit' => 20
     * ]
     * @return array
     */
    public function searchProducts($filter_data = array()) {
        $keyword   = !empty($filter_data['filter_keyword']) ? $this->db->escape($filter_data['filter_keyword']) : '';
        $branch_id = !empty($filter_data['branch_id']) ? (int)$filter_data['branch_id'] : 0;
        $start     = isset($filter_data['start']) ? (int)$filter_data['start'] : 0;
        $limit     = isset($filter_data['limit']) ? (int)$filter_data['limit'] : 20;

        $where = array();
        if ($keyword !== '') {
            $where[] = "(pd.name LIKE '%" . $keyword . "%' 
                         OR pb.barcode LIKE '%" . $keyword . "%')";
        }

        $sql = "SELECT p.product_id,
                       pd.name,
                       GROUP_CONCAT(DISTINCT pb.barcode SEPARATOR ', ') AS all_barcodes
                FROM `cod_product` p
                LEFT JOIN `cod_product_description` pd 
                       ON (p.product_id = pd.product_id AND pd.language_id = '1')
                LEFT JOIN `cod_product_barcode` pb
                       ON (p.product_id = pb.product_id)";

        if ($where) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $sql .= " GROUP BY p.product_id
                  ORDER BY pd.name ASC
                  LIMIT " . (int)$start . "," . (int)$limit;

        $query = $this->db->query($sql);

        $results = array();
        foreach ($query->rows as $row) {
            // الكمية بوحدة الأساس
            $base_qty = 0;
            if ($branch_id) {
                $base_qty = $this->getBaseQuantity($row['product_id'], $branch_id);
            }

            // جلب الوحدات
            $units = $this->getProductUnits($row['product_id']);

            $label = $row['name'];
            if (!empty($row['all_barcodes'])) {
                $label .= ' — [' . $row['all_barcodes'] . ']';
            }

            $results[] = array(
                'product_id' => $row['product_id'],
                'label'      => $label,
                'base_qty'   => (float)$base_qty, // الكمية بوحدة الأساس
                'units'      => $units
            );
        }

        return $results;
    }

    /**
     * جلب الوحدات الخاصة بمنتج معيّن (والـconversion_factor)
     *
     * @param int $product_id
     * @return array
     */
    public function getProductUnits($product_id) {
        $sql = "SELECT pu.unit_id,
                       u.desc_en,
                       u.desc_ar,
                       pu.unit_type,
                       pu.conversion_factor
                FROM `cod_product_unit` pu
                LEFT JOIN `cod_unit` u ON (pu.unit_id = u.unit_id)
                WHERE pu.product_id = '" . (int)$product_id . "'
                ORDER BY pu.unit_type DESC";

        $query = $this->db->query($sql);
        return $query->rows;
    }
}
