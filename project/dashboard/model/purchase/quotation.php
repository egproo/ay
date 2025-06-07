<?php
class ModelPurchaseQuotation extends Model {
/**
 * تنفيذ معاينة المستند
 *
 * @param int $document_id معرف المستند
 * @param bool $thumbnail إنشاء صورة مصغرة إذا كانت true
 * @return bool نجاح العملية
 */
public function previewDocument($document_id, $thumbnail = false) {
    $document_info = $this->getDocument($document_id);

    if (!$document_info || !file_exists(DIR_UPLOAD . $document_info['file_path'])) {
        return false;
    }

    $file = DIR_UPLOAD . $document_info['file_path'];
    $file_ext = pathinfo($file, PATHINFO_EXTENSION);

    // التحقق مما إذا كان الملف صورة
    $image_extensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp'];
    $is_image = in_array(strtolower($file_ext), $image_extensions);

    if ($is_image) {
        if ($thumbnail) {
            // إنشاء صورة مصغرة للمعاينة
            $thumbnail_width = 200;
            $thumbnail_height = 200;

            list($width, $height) = getimagesize($file);

            // حساب نسب التصغير
            $ratio = min($thumbnail_width / $width, $thumbnail_height / $height);
            $new_width = round($width * $ratio);
            $new_height = round($height * $ratio);

            // إنشاء صورة مصغرة
            $thumb = imagecreatetruecolor($new_width, $new_height);

            switch (strtolower($file_ext)) {
                case 'jpg':
                case 'jpeg':
                    $source = imagecreatefromjpeg($file);
                    break;
                case 'png':
                    $source = imagecreatefrompng($file);
                    imagealphablending($thumb, false);
                    imagesavealpha($thumb, true);
                    break;
                case 'gif':
                    $source = imagecreatefromgif($file);
                    break;
                case 'bmp':
                    $source = imagecreatefrombmp($file);
                    break;
            }

            imagecopyresampled($thumb, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

            // تعيين نوع المحتوى
            header('Content-Type: image/jpeg');

            // إخراج الصورة المصغرة
            imagejpeg($thumb, null, 90);

            // تحرير الذاكرة
            imagedestroy($thumb);
            imagedestroy($source);

            return true;
        } else {
            // عرض الصورة الأصلية
            $mime_type = mime_content_type($file);
            header('Content-Type: ' . $mime_type);
            readfile($file);
            return true;
        }
    } elseif (strtolower($file_ext) === 'pdf') {
        // عرض ملف PDF
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $document_info['document_name'] . '"');
        readfile($file);
        return true;
    }

    return false;
}


/**
 * الحصول على معلومات المستندات المرفقة مع إمكانية القراءة/الحذف
 *
 * @param int $quotation_id معرف عرض السعر
 * @return array بيانات المستندات
 */
public function getDocumentsWithPermissions($quotation_id) {
    return array(
        'documents' => $this->getDocuments($quotation_id),
        'can_upload' => $this->user->hasKey('purchase_quotation_document_upload'),
        'can_delete' => $this->user->hasKey('purchase_quotation_document_delete')
    );
}

/**
 * Search products for select2
 *
 * @param string $search Search term
 * @return array Search results
 */
public function searchProducts($search) {
    $sql = "SELECT p.product_id, p.model, pd.name
            FROM `" . DB_PREFIX . "product` p
            LEFT JOIN `" . DB_PREFIX . "product_description` pd
              ON (p.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "')
            WHERE pd.name LIKE '%" . $this->db->escape($search) . "%'
               OR p.model LIKE '%" . $this->db->escape($search) . "%'
            ORDER BY pd.name ASC
            LIMIT 15";

    $query = $this->db->query($sql);

    return $query->rows;
}

/**
 * Get product units
 *
 * @param int $product_id Product ID
 * @return array List of units
 */
public function getProductUnits($product_id) {
    $sql = "SELECT pu.unit_id, pu.conversion_factor, u.code AS unit_code,
                  CONCAT(u.desc_en, ' - ', u.desc_ar) AS unit_name
            FROM `" . DB_PREFIX . "product_unit` pu
            LEFT JOIN `" . DB_PREFIX . "unit` u ON (pu.unit_id = u.unit_id)
            WHERE pu.product_id = '" . (int)$product_id . "'
            ORDER BY pu.unit_id ASC";

    $query = $this->db->query($sql);

    return $query->rows;
}

/**
 * Get product inventory data
 *
 * @param int $product_id Product ID
 * @param int $unit_id Unit ID
 * @return array Inventory data
 */
/**
 * Get product inventory data - Safe version
 *
 * @param int $product_id Product ID
 * @param int $unit_id Unit ID
 * @return array Inventory data
 */
public function getProductInventory($product_id, $unit_id) {
    $result = [
        'quantity_available' => 0,
        'average_cost' => 0
    ];

    try {
        // Get inventory quantity
        $inventory_query = $this->db->query(
            "SELECT SUM(quantity_available) AS quantity_available
             FROM `" . DB_PREFIX . "product_inventory`
             WHERE product_id = '" . (int)$product_id . "'
             AND unit_id = '" . (int)$unit_id . "'"
        );

        if ($inventory_query->row) {
            $result['quantity_available'] = $inventory_query->row['quantity_available'] ?: 0;
        }
    } catch (Exception $e) {
        // استمر في التنفيذ حتى مع وجود خطأ
    }

    try {
        // Get latest average cost
        $cost_query = $this->db->query(
            "SELECT average_cost
             FROM `" . DB_PREFIX . "inventory_valuation`
             WHERE product_id = '" . (int)$product_id . "'
             AND unit_id = '" . (int)$unit_id . "'
             ORDER BY valuation_date DESC
             LIMIT 1"
        );

        if ($cost_query->row) {
            $result['average_cost'] = $cost_query->row['average_cost'] ?: 0;
        }
    } catch (Exception $e) {
        // استمر في التنفيذ حتى مع وجود خطأ
    }

    return $result;
}

/**
 * Get last purchase price for a product
 *
 * @param int $product_id Product ID
 * @param int $unit_id Unit ID
 * @return array Last purchase information
 */
public function getLastPurchasePrice($product_id, $unit_id) {
    $sql = "SELECT poi.unit_price, po.created_at AS date,
                  CONCAT(s.firstname, ' ', s.lastname) AS supplier_name
           FROM `" . DB_PREFIX . "purchase_order_item` poi
           LEFT JOIN `" . DB_PREFIX . "purchase_order` po ON (poi.po_id = po.po_id)
           LEFT JOIN `" . DB_PREFIX . "supplier` s ON (po.supplier_id = s.supplier_id)
           WHERE poi.product_id = '" . (int)$product_id . "'
           AND poi.unit_id = '" . (int)$unit_id . "'
           AND po.status IN ('completed', 'partial')
           ORDER BY po.created_at DESC
           LIMIT 1";

    $query = $this->db->query($sql);

    if ($query->row) {
        return [
            'unit_price' => $query->row['unit_price'],
            'date' => date($this->language->get('date_format_short'), strtotime($query->row['date'])),
            'supplier_name' => $query->row['supplier_name']
        ];
    }

    return [];
}

/**
 * Get detailed product information including units, inventory and costs
 *
 * @param int $product_id Product ID
 * @return array Product details
 */
public function getProductDetails($product_id) {
    $product_info = [];

    // Get basic product info
    $sql = "SELECT p.product_id, p.model, pd.name as product_name
            FROM `" . DB_PREFIX . "product` p
            LEFT JOIN `" . DB_PREFIX . "product_description` pd
              ON (p.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "')
            WHERE p.product_id = '" . (int)$product_id . "'";

    $query = $this->db->query($sql);

    if ($query->row) {
        $product_info = $query->row;

        // Get product units
        $sql_units = "SELECT pu.unit_id, u.code AS unit_code, CONCAT(u.desc_en, ' - ', u.desc_ar) AS unit_name
                      FROM `" . DB_PREFIX . "product_unit` pu
                      LEFT JOIN `" . DB_PREFIX . "unit` u ON (pu.unit_id = u.unit_id)
                      WHERE pu.product_id = '" . (int)$product_id . "'
                      ORDER BY pu.unit_id ASC";

        $query_units = $this->db->query($sql_units);

        $product_info['units'] = [];

        if ($query_units->rows) {
            foreach ($query_units->rows as $unit) {
                $unit_id = $unit['unit_id'];

                // Get inventory quantity for this unit
                $sql_inventory = "SELECT quantity_available
                                 FROM `" . DB_PREFIX . "product_inventory`
                                 WHERE product_id = '" . (int)$product_id . "'
                                   AND unit_id = '" . (int)$unit_id . "'";

                $query_inventory = $this->db->query($sql_inventory);
                $quantity_available = $query_inventory->row ? $query_inventory->row['quantity_available'] : 0;

                // Get average cost for this unit
                $sql_cost = "SELECT average_cost
                            FROM `" . DB_PREFIX . "inventory_valuation`
                            WHERE product_id = '" . (int)$product_id . "'
                              AND unit_id = '" . (int)$unit_id . "'
                            ORDER BY valuation_date DESC
                            LIMIT 1";

                $query_cost = $this->db->query($sql_cost);
                $average_cost = $query_cost->row ? $query_cost->row['average_cost'] : 0;

                $product_info['units'][] = [
                    'unit_id' => $unit_id,
                    'unit_code' => $unit['unit_code'],
                    'unit_name' => $unit['unit_name'],
                    'quantity_available' => $quantity_available,
                    'average_cost' => $this->currency->format($average_cost, $this->config->get('config_currency'))
                ];
            }
        }
    }

    return $product_info;
}

/**
 * Get supplier information including rating and historical data
 *
 * @param int $supplier_id Supplier ID
 * @return array Supplier information
 */
/**
 * Get supplier information including rating and historical data
 *
 * @param int $supplier_id Supplier ID
 * @return array Supplier information
 */
public function getSupplierInfo($supplier_id) {
    // Get basic supplier info
    $supplier_query = $this->db->query("SELECT s.*,
                                       CONCAT(s.firstname, ' ', s.lastname) AS name,
                                       s.email, s.telephone, s.fax,
                                       s.account_code
                                    FROM `" . DB_PREFIX . "supplier` s
                                    WHERE s.supplier_id = '" . (int)$supplier_id . "'");

    if (!$supplier_query->row) {
        return [];
    }

    $supplier_info = $supplier_query->row;

    // Get supplier address
    $address_query = $this->db->query("SELECT sa.*
                                      FROM `" . DB_PREFIX . "supplier_address` sa
                                      WHERE sa.supplier_id = '" . (int)$supplier_id . "'
                                      ORDER BY sa.address_id ASC
                                      LIMIT 1");

    if ($address_query->row) {
        $supplier_info['address'] = $address_query->row['address_1'];
        if (!empty($address_query->row['address_2'])) {
            $supplier_info['address'] .= ', ' . $address_query->row['address_2'];
        }
        $supplier_info['address'] .= ', ' . $address_query->row['city'];
    } else {
        $supplier_info['address'] = '';
    }

    // التقييم الافتراضي بدلاً من الاعتماد على جدول غير موجود
    $supplier_info['rating'] = 0;

    // محاولة جلب تقييم المورد من جدول supplier_evaluation (إذا كان موجودًا)
    try {
        $rating_query = $this->db->query("SELECT AVG(overall_score) AS avg_rating
                                         FROM `" . DB_PREFIX . "supplier_evaluation`
                                         WHERE supplier_id = '" . (int)$supplier_id . "'");

        if ($rating_query->row && !is_null($rating_query->row['avg_rating'])) {
            $supplier_info['rating'] = round($rating_query->row['avg_rating'] * 5, 1); // تحويل النتيجة إلى مقياس من 5
        }
    } catch (Exception $e) {
        // تجاهل الخطأ إذا لم يكن الجدول موجودًا وإبقاء التقييم الافتراضي
    }

    // Get last order date
    $last_order_query = $this->db->query("SELECT created_at
                                        FROM `" . DB_PREFIX . "purchase_order`
                                        WHERE supplier_id = '" . (int)$supplier_id . "'
                                        ORDER BY created_at DESC
                                        LIMIT 1");

    $supplier_info['last_order_date'] = $last_order_query->row ? date($this->language->get('date_format_short'), strtotime($last_order_query->row['created_at'])) : '';

    // Get average delivery days
    $delivery_query = $this->db->query("SELECT AVG(DATEDIFF(gr.receipt_date, po.order_date)) AS avg_days
                                      FROM `" . DB_PREFIX . "purchase_order` po
                                      LEFT JOIN `" . DB_PREFIX . "goods_receipt` gr ON (po.po_id = gr.po_id)
                                      WHERE po.supplier_id = '" . (int)$supplier_id . "'
                                        AND gr.receipt_date IS NOT NULL
                                      GROUP BY po.supplier_id");

    $supplier_info['average_delivery_days'] = $delivery_query->row ? round($delivery_query->row['avg_days']) : 0;

    return $supplier_info;
}

    /**
     * Get quotation statistics for dashboard
     *
     * @param array $filter_data Filter parameters
     * @return array Statistics
     */
    public function getQuotationStats($filter_data = []) {
        $stats = [
            'total'    => 0,
            'pending'  => 0,
            'approved' => 0,
            'rejected' => 0,
            'converted' => 0
        ];

        // Build WHERE clause based on filters
        $where = "WHERE 1=1 ";

        if (!empty($filter_data['filter_quotation_number'])) {
            $where .= " AND q.quotation_number LIKE '%" . $this->db->escape($filter_data['filter_quotation_number']) . "%' ";
        }

        if (!empty($filter_data['filter_requisition_id'])) {
            $where .= " AND q.requisition_id = '" . (int)$filter_data['filter_requisition_id'] . "' ";
        }

        if (!empty($filter_data['filter_supplier_id'])) {
            $where .= " AND q.supplier_id = '" . (int)$filter_data['filter_supplier_id'] . "' ";
        }

        if (!empty($filter_data['filter_status'])) {
            $where .= " AND q.status = '" . $this->db->escape($filter_data['filter_status']) . "' ";
        }

        if (!empty($filter_data['filter_date_start'])) {
            $where .= " AND DATE(q.created_at) >= '" . $this->db->escape($filter_data['filter_date_start']) . "' ";
        }

        if (!empty($filter_data['filter_date_end'])) {
            $where .= " AND DATE(q.created_at) <= '" . $this->db->escape($filter_data['filter_date_end']) . "' ";
        }

        if (!empty($filter_data['filter_validity']) && $filter_data['filter_validity'] != 'all') {
            $today = date('Y-m-d');
            if ($filter_data['filter_validity'] == 'active') {
                $where .= " AND q.validity_date >= '" . $this->db->escape($today) . "' ";
            } else if ($filter_data['filter_validity'] == 'expired') {
                $where .= " AND q.validity_date < '" . $this->db->escape($today) . "' ";
            }
        }

        // Use subqueries to calculate statistics
        $sql = "SELECT
               (SELECT COUNT(*) FROM `" . DB_PREFIX . "purchase_quotation` q $where) AS total,
               (SELECT COUNT(*) FROM `" . DB_PREFIX . "purchase_quotation` q $where AND q.status = 'pending') AS pending,
               (SELECT COUNT(*) FROM `" . DB_PREFIX . "purchase_quotation` q $where AND q.status = 'approved') AS approved,
               (SELECT COUNT(*) FROM `" . DB_PREFIX . "purchase_quotation` q $where AND q.status = 'rejected') AS rejected,
               (SELECT COUNT(*) FROM `" . DB_PREFIX . "purchase_quotation` q $where AND q.status = 'converted') AS converted
               ";

        $query = $this->db->query($sql);

        if ($query->num_rows) {
            $stats['total'] = (int)$query->row['total'];
            $stats['pending'] = (int)$query->row['pending'];
            $stats['approved'] = (int)$query->row['approved'];
            $stats['rejected'] = (int)$query->row['rejected'];
            $stats['converted'] = (int)$query->row['converted'];
        }

        return $stats;
    }

    /**
     * Get list of quotations based on filter criteria
     *
     * @param array $data Filter and pagination parameters
     * @return array List of quotations
     */
    public function getQuotations($data = []) {
        $sql = "SELECT q.*,
                r.req_number AS requisition_number,
                CONCAT(s.firstname, ' ', s.lastname) AS supplier_name,
                c.code AS currency_code,
                (SELECT COUNT(*) FROM `" . DB_PREFIX . "purchase_document` pd WHERE pd.reference_type = 'quotation' AND pd.reference_id = q.quotation_id) AS document_count
               FROM `" . DB_PREFIX . "purchase_quotation` q
               LEFT JOIN `" . DB_PREFIX . "purchase_requisition` r ON (q.requisition_id = r.requisition_id)
               LEFT JOIN `" . DB_PREFIX . "supplier` s ON (q.supplier_id = s.supplier_id)
               LEFT JOIN `" . DB_PREFIX . "currency` c ON (q.currency_id = c.currency_id)
               WHERE 1=1";

        // Apply filters
        if (!empty($data['filter_quotation_number'])) {
            $sql .= " AND q.quotation_number LIKE '%" . $this->db->escape($data['filter_quotation_number']) . "%'";
        }

        if (!empty($data['filter_requisition_id'])) {
            $sql .= " AND q.requisition_id = '" . (int)$data['filter_requisition_id'] . "'";
        }

        if (!empty($data['filter_supplier_id'])) {
            $sql .= " AND q.supplier_id = '" . (int)$data['filter_supplier_id'] . "'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND q.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(q.created_at) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(q.created_at) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        if (!empty($data['filter_validity']) && $data['filter_validity'] != 'all') {
            $today = date('Y-m-d');
            if ($data['filter_validity'] == 'active') {
                $sql .= " AND q.validity_date >= '" . $this->db->escape($today) . "'";
            } else if ($data['filter_validity'] == 'expired') {
                $sql .= " AND q.validity_date < '" . $this->db->escape($today) . "'";
            }
        }

        // Apply sorting
        $sort_data = [
            'q.quotation_number',
            'q.requisition_id',
            's.firstname',
            'q.total_amount',
            'q.status',
            'q.validity_date',
            'q.created_at'
        ];

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY q.created_at";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        // Apply pagination
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
     * Get total count of quotations for pagination
     *
     * @param array $data Filter parameters
     * @return int Total count
     */
    public function getTotalQuotations($data = []) {
        $sql = "SELECT COUNT(DISTINCT q.quotation_id) AS total
               FROM `" . DB_PREFIX . "purchase_quotation` q
               LEFT JOIN `" . DB_PREFIX . "purchase_requisition` r ON (q.requisition_id = r.requisition_id)
               LEFT JOIN `" . DB_PREFIX . "supplier` s ON (q.supplier_id = s.supplier_id)
               WHERE 1=1";

        // Apply filters
        if (!empty($data['filter_quotation_number'])) {
            $sql .= " AND q.quotation_number LIKE '%" . $this->db->escape($data['filter_quotation_number']) . "%'";
        }

        if (!empty($data['filter_requisition_id'])) {
            $sql .= " AND q.requisition_id = '" . (int)$data['filter_requisition_id'] . "'";
        }

        if (!empty($data['filter_supplier_id'])) {
            $sql .= " AND q.supplier_id = '" . (int)$data['filter_supplier_id'] . "'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND q.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(q.created_at) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(q.created_at) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        if (!empty($data['filter_validity']) && $data['filter_validity'] != 'all') {
            $today = date('Y-m-d');
            if ($data['filter_validity'] == 'active') {
                $sql .= " AND q.validity_date >= '" . $this->db->escape($today) . "'";
            } else if ($data['filter_validity'] == 'expired') {
                $sql .= " AND q.validity_date < '" . $this->db->escape($today) . "'";
            }
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    /**
     * Get details of a specific quotation
     *
     * @param int $quotation_id Quotation ID
     * @return array Quotation details
     */
    public function getQuotation($quotation_id) {
        $sql = "SELECT q.*,
                r.req_number AS requisition_number,
                CONCAT(s.firstname, ' ', s.lastname) AS supplier_name,
                c.code AS currency_code
               FROM `" . DB_PREFIX . "purchase_quotation` q
               LEFT JOIN `" . DB_PREFIX . "purchase_requisition` r ON (q.requisition_id = r.requisition_id)
               LEFT JOIN `" . DB_PREFIX . "supplier` s ON (q.supplier_id = s.supplier_id)
               LEFT JOIN `" . DB_PREFIX . "currency` c ON (q.currency_id = c.currency_id)
               WHERE q.quotation_id = '" . (int)$quotation_id . "'";

        $query = $this->db->query($sql);

        return $query->row;
    }

    /**
     * Change the status of a quotation
     *
     * @param int $quotation_id Quotation ID
     * @param string $new_status New status code
     * @param int $user_id User performing the change
     * @param string $reason Optional reason (e.g., for rejection or cancellation)
     * @return bool Success status
     * @throws Exception If status transition is invalid or quotation not found
     */
    public function changeQuotationStatus($quotation_id, $new_status, $user_id, $reason = '') {
        // Get current quotation info
        $quotation_info = $this->getQuotation($quotation_id);

        if (!$quotation_info) {
            throw new Exception($this->language->get('error_quotation_not_found'));
        }

        $current_status = $quotation_info['status'];

        // Define allowed transitions (example, adjust as needed)
        $allowed_transitions = [
            'draft' => ['pending', 'cancelled'],
            'pending' => ['approved', 'rejected', 'cancelled'],
            // Add other transitions if necessary (e.g., from approved to cancelled before conversion)
        ];

        // Check if the transition is allowed
        // Allow changing to the same status (e.g., resubmitting a draft as pending)
        if ($current_status != $new_status && (!isset($allowed_transitions[$current_status]) || !in_array($new_status, $allowed_transitions[$current_status]))) {
             throw new Exception(sprintf($this->language->get('error_invalid_status_transition'), $current_status, $new_status));
        }

        // Specific checks (e.g., reason required for rejection/cancellation)
        if (in_array($new_status, ['rejected', 'cancelled']) && empty($reason)) {
            // Allow rejection without reason for now, but maybe enforce later
            // throw new Exception($this->language->get('error_reason_required'));
        }

        try {
            $this->db->query("START TRANSACTION");

            // Update status and potentially reason
            $sql = "UPDATE `" . DB_PREFIX . "purchase_quotation` SET
                    status = '" . $this->db->escape($new_status) . "',
                    updated_by = '" . (int)$user_id . "',
                    updated_at = NOW()";

            // Only update rejection_reason if the new status is 'rejected' or 'cancelled'
            if (in_array($new_status, ['rejected', 'cancelled']) && !empty($reason)) {
                $sql .= ", rejection_reason = '" . $this->db->escape($reason) . "'";
            } elseif ($new_status == 'pending') {
                 // Clear rejection reason when submitting
                 $sql .= ", rejection_reason = NULL";
            }


            $sql .= " WHERE quotation_id = '" . (int)$quotation_id . "'";

            $this->db->query($sql);

            // Add history record
            $action_type = 'status_changed';
             if ($current_status == 'draft' && $new_status == 'pending') {
                 $action_type = 'submitted';
             } elseif ($new_status == 'cancelled') {
                 $action_type = 'cancelled';
             }
            // Use specific actions for approve/reject as they are handled by separate functions usually
            // but this function can handle general status changes like draft -> pending or pending -> cancelled

            $history_description = sprintf($this->language->get('text_history_status_change'), $this->getStatusText($new_status));
            if (!empty($reason) && in_array($new_status, ['rejected', 'cancelled'])) {
                $history_description .= '. ' . $this->language->get('text_reason') . ': ' . $reason;
            }

            $this->addQuotationHistory([
                'quotation_id' => $quotation_id,
                'user_id'      => $user_id,
                'action'       => $action_type,
                'description'  => $history_description
            ]);

            $this->db->query("COMMIT");
            return true;

        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            // Log the error if needed
            // error_log("Error changing quotation status: " . $e->getMessage());
            throw $e; // Re-throw the exception to be caught by the controller
        } // Missing closing brace for the try block was here
    } // Missing closing brace for the function was here

    /** // This comment and function definition should start correctly now
     * Get line items for a quotation
     *
     * @param int $quotation_id Quotation ID
     * @return array Line items
     */
    public function getQuotationItems($quotation_id) {
        $sql = "SELECT qi.*,
        p.model,
        pd.name AS product_name,
        u.code AS unit_code,
        CONCAT(u.desc_en, ' - ', u.desc_ar) AS unit_name
       FROM `" . DB_PREFIX . "purchase_quotation_item` qi
       LEFT JOIN `" . DB_PREFIX . "product` p ON (qi.product_id = p.product_id)
       LEFT JOIN `" . DB_PREFIX . "product_description` pd ON (qi.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "')
       LEFT JOIN `" . DB_PREFIX . "unit` u ON (qi.unit_id = u.unit_id)
       WHERE qi.quotation_id = '" . (int)$quotation_id . "'
       ORDER BY qi.quotation_item_id ASC";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    /**
     * Add a new quotation
     *
     * @param array $data Quotation data
     * @return int|bool New quotation ID or false on failure
     */

    public function addQuotation($data) {
        try {
            $this->db->query("START TRANSACTION");

            // Generate quotation number
            $quotation_number = $this->generateQuotationNumber();

            // Insert main quotation record
            $this->db->query("INSERT INTO `" . DB_PREFIX . "purchase_quotation` SET
                 quotation_number = '" . $this->db->escape($quotation_number) . "',
                 requisition_id = '" . (int)$data['requisition_id'] . "',
                 supplier_id = '" . (int)$data['supplier_id'] . "',
                 currency_id = '" . (int)$data['currency_id'] . "',
                 exchange_rate = '" . (float)$data['exchange_rate'] . "',
                 validity_date = '" . $this->db->escape($data['validity_date']) . "',
                 payment_terms = '" . $this->db->escape($data['payment_terms']) . "',
                 delivery_terms = '" . $this->db->escape($data['delivery_terms']) . "',
                 notes = '" . $this->db->escape($data['notes']) . "',
                 tax_included = '" . (int)$data['tax_included'] . "',
                 tax_rate = '" . (float)$data['tax_rate'] . "',
                 subtotal = '" . (float)$data['subtotal'] . "',
                 discount_type = '" . $this->db->escape($data['discount_type']) . "',
                 has_discount = '" . (int)$data['has_discount'] . "',
                 discount_value = '" . (float)$data['discount_value'] . "',
                 discount_amount = '" . (float)$data['discount_amount'] . "',
                 tax_amount = '" . (float)$data['tax_amount'] . "',
                 total_amount = '" . (float)$data['total_amount'] . "',
                 status = '" . $this->db->escape($data['status']) . "',
                 created_by = '" . (int)$data['user_id'] . "',
                 created_at = NOW(),
                 updated_at = NOW()
            ");

            $quotation_id = $this->db->getLastId();

            // Insert quotation items
            if (!empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    $this->db->query("INSERT INTO `" . DB_PREFIX . "purchase_quotation_item` SET
                         quotation_id = '" . (int)$quotation_id . "',
                         requisition_item_id = '" . (int)$item['requisition_item_id'] . "',
                         product_id = '" . (int)$item['product_id'] . "',
                         unit_id = '" . (int)$item['unit_id'] . "',
                         quantity = '" . (float)$item['quantity'] . "',
                         unit_price = '" . (float)$item['unit_price'] . "',
                         tax_rate = '" . (float)$item['tax_rate'] . "',
                         discount_type = '" . $this->db->escape($item['discount_type']) . "',
                         discount_rate = '" . (float)$item['discount_rate'] . "',
                         discount_amount = '" . (float)$item['discount_amount'] . "',
                         tax_amount = '" . (float)$item['tax_amount'] . "',
                         line_total = '" . (float)$item['line_total'] . "',
                         notes = '" . $this->db->escape($item['description']) . "'
                    ");
                }
            }

            // Add history record
            $this->addQuotationHistory([
                'quotation_id' => $quotation_id,
                'user_id' => $data['user_id'],
                'action' => 'created',
                'description' => 'Quotation created'
            ]);

            $this->db->query("COMMIT");

            return $quotation_id;
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            throw $e;
        }
    }

    /**
     * Edit an existing quotation
     *
     * @param array $data Quotation data
     * @return bool Success status
     */
    public function editQuotation($data) {
        try {
            // Check if the quotation exists and can be edited
            $quotation_info = $this->getQuotation($data['quotation_id']);

            if (!$quotation_info) {
                throw new Exception("Quotation not found.");
            }

            if (!in_array($quotation_info['status'], ['draft', 'pending'])) {
                throw new Exception("Cannot edit a quotation with status: " . $quotation_info['status']);
            }

            $this->db->query("START TRANSACTION");

            // Update main quotation record
            $this->db->query("UPDATE `" . DB_PREFIX . "purchase_quotation` SET
                 requisition_id = '" . (int)$data['requisition_id'] . "',
                 supplier_id = '" . (int)$data['supplier_id'] . "',
                 currency_id = '" . (int)$data['currency_id'] . "',
                 exchange_rate = '" . (float)$data['exchange_rate'] . "',
                 validity_date = '" . $this->db->escape($data['validity_date']) . "',
                 payment_terms = '" . $this->db->escape($data['payment_terms']) . "',
                 delivery_terms = '" . $this->db->escape($data['delivery_terms']) . "',
                 notes = '" . $this->db->escape($data['notes']) . "',
                 tax_included = '" . (int)$data['tax_included'] . "',
                 tax_rate = '" . (float)$data['tax_rate'] . "',
                 subtotal = '" . (float)$data['subtotal'] . "',
                 discount_type = '" . $this->db->escape($data['discount_type']) . "',
                 has_discount = '" . (int)$data['has_discount'] . "',
                 discount_value = '" . (float)$data['discount_value'] . "',
                 discount_amount = '" . (float)$data['discount_amount'] . "',
                 tax_amount = '" . (float)$data['tax_amount'] . "',
                 total_amount = '" . (float)$data['total_amount'] . "',
                 status = '" . $this->db->escape($data['status']) . "',
                 updated_by = '" . (int)$data['user_id'] . "',
                 updated_at = NOW()
               WHERE quotation_id = '" . (int)$data['quotation_id'] . "'
            ");

            // Delete existing items
            $this->db->query("DELETE FROM `" . DB_PREFIX . "purchase_quotation_item`
                           WHERE quotation_id = '" . (int)$data['quotation_id'] . "'");

            // Insert updated items
            if (!empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    $this->db->query("INSERT INTO `" . DB_PREFIX . "purchase_quotation_item` SET
                         quotation_id = '" . (int)$data['quotation_id'] . "',
                         requisition_item_id = '" . (int)$item['requisition_item_id'] . "',
                         product_id = '" . (int)$item['product_id'] . "',
                         unit_id = '" . (int)$item['unit_id'] . "',
                         quantity = '" . (float)$item['quantity'] . "',
                         unit_price = '" . (float)$item['unit_price'] . "',
                         tax_rate = '" . (float)$item['tax_rate'] . "',
                         discount_type = '" . $this->db->escape($item['discount_type']) . "',
                         discount_rate = '" . (float)$item['discount_rate'] . "',
                         discount_amount = '" . (float)$item['discount_amount'] . "',
                         tax_amount = '" . (float)$item['tax_amount'] . "',
                         line_total = '" . (float)$item['line_total'] . "',
                         notes = '" . $this->db->escape($item['description']) . "'
                    ");
                }
            }

            // Add history record
            $this->addQuotationHistory([
                'quotation_id' => $data['quotation_id'],
                'user_id' => $data['user_id'],
                'action' => 'edited',
                'description' => 'Quotation updated'
            ]);

            $this->db->query("COMMIT");

            return true;
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            throw $e;
        }
    }

    /**
     * Approve a quotation
     *
     * @param int $quotation_id Quotation ID
     * @param int $user_id User who approved
     * @return bool Success status
     */
    public function approveQuotation($quotation_id, $user_id) {
        try {
            // Check if the quotation exists and can be approved
            $quotation_info = $this->getQuotation($quotation_id);

            if (!$quotation_info) {
                throw new Exception("Quotation not found.");
            }

            if ($quotation_info['status'] != 'pending') {
                throw new Exception("Only pending quotations can be approved.");
            }

            $this->db->query("START TRANSACTION");

            // Update quotation status
            $this->db->query("UPDATE `" . DB_PREFIX . "purchase_quotation` SET
                 status = 'approved',
                 updated_by = '" . (int)$user_id . "',
                 updated_at = NOW()
               WHERE quotation_id = '" . (int)$quotation_id . "'");

            // Add history record
            $this->addQuotationHistory([
                'quotation_id' => $quotation_id,
                'user_id' => $user_id,
                'action' => 'approved',
                'description' => 'Quotation approved'
            ]);

            $this->db->query("COMMIT");

            return true;
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            throw $e;
        }
    }

    /**
     * Reject a quotation
     *
     * @param int $quotation_id Quotation ID
     * @param string $reason Rejection reason
     * @param int $user_id User who rejected
     * @return bool Success status
     */
    public function rejectQuotation($quotation_id, $reason, $user_id) {
        try {
            // Check if the quotation exists and can be rejected
            $quotation_info = $this->getQuotation($quotation_id);

            if (!$quotation_info) {
                throw new Exception("Quotation not found.");
            }

            if ($quotation_info['status'] != 'pending') {
                throw new Exception("Only pending quotations can be rejected.");
            }

            $this->db->query("START TRANSACTION");

            // Update quotation status
            $this->db->query("UPDATE `" . DB_PREFIX . "purchase_quotation` SET
                 status = 'rejected',
                 rejection_reason = '" . $this->db->escape($reason) . "',
                 updated_by = '" . (int)$user_id . "',
                 updated_at = NOW()
               WHERE quotation_id = '" . (int)$quotation_id . "'");

            // Add history record
            $this->addQuotationHistory([
                'quotation_id' => $quotation_id,
                'user_id' => $user_id,
                'action' => 'rejected',
                'description' => 'Quotation rejected. Reason: ' . $reason
            ]);

            $this->db->query("COMMIT");

            return true;
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            throw $e;
        }
    }

    /**
     * Delete a quotation
     *
     * @param int $quotation_id Quotation ID
     * @return bool Success status
     */
    public function deleteQuotation($quotation_id) {
        try {
            // Check if the quotation exists and can be deleted
            $quotation_info = $this->getQuotation($quotation_id);

            if (!$quotation_info) {
                throw new Exception("Quotation not found.");
            }

            if (!in_array($quotation_info['status'], ['draft', 'pending', 'rejected'])) {
                throw new Exception("Cannot delete quotations with status: " . $quotation_info['status']);
            }

            $this->db->query("START TRANSACTION");

            // Delete quotation items
            $this->db->query("DELETE FROM `" . DB_PREFIX . "purchase_quotation_item`
                           WHERE quotation_id = '" . (int)$quotation_id . "'");

            // Delete quotation history
            $this->db->query("DELETE FROM `" . DB_PREFIX . "purchase_quotation_history`
                           WHERE quotation_id = '" . (int)$quotation_id . "'");

            // Delete documents related to this quotation
            $documents = $this->getDocuments($quotation_id);
            foreach ($documents as $document) {
                $this->deletePhysicalDocument($document['file_path']);
            }

            $this->db->query("DELETE FROM `" . DB_PREFIX . "purchase_document`
                           WHERE reference_type = 'quotation' AND reference_id = '" . (int)$quotation_id . "'");

            // Delete the quotation itself
            $this->db->query("DELETE FROM `" . DB_PREFIX . "purchase_quotation`
                           WHERE quotation_id = '" . (int)$quotation_id . "'");

            $this->db->query("COMMIT");

            return true;
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            throw $e;
        }
    }

    /**
     * Convert a quotation to a purchase order
     *
     * @param int $quotation_id Quotation ID
     * @param int $user_id User who initiated the conversion
     * @return int|bool Purchase order ID or false on failure
     */
    public function convertToPurchaseOrder($quotation_id, $user_id) {
        try {
            // Check if the quotation exists and can be converted
            $quotation_info = $this->getQuotation($quotation_id);

            if (!$quotation_info) {
                throw new Exception("Quotation not found.");
            }

            if ($quotation_info['status'] != 'approved') {
                throw new Exception("Only approved quotations can be converted to purchase orders.");
            }

            $this->db->query("START TRANSACTION");

            // Load the purchase order model
            $this->load->model('purchase/order');

            // Get quotation items
            $quotation_items = $this->getQuotationItems($quotation_id);

            // Prepare data for purchase order
            $po_data = [
                'supplier_id' => $quotation_info['supplier_id'],
                'quotation_id' => $quotation_id, // تم التغيير من requisition_id
                'currency_id' => $quotation_info['currency_id'],
                'exchange_rate' => $quotation_info['exchange_rate'],
                'order_date' => date('Y-m-d'),
                'expected_delivery_date' => date('Y-m-d', strtotime('+7 days')),
                'payment_terms' => $quotation_info['payment_terms'],
                'delivery_terms' => $quotation_info['delivery_terms'],
                'notes' => $quotation_info['notes'],
                'subtotal' => $quotation_info['subtotal'],
                'tax_amount' => $quotation_info['tax_amount'],
                'discount_amount' => $quotation_info['discount_amount'],
                'total_amount' => $quotation_info['total_amount'],
                'status' => 'pending_review',
                'source_type' => 'quotation',
                'source_id' => $quotation_id,
                'created_by' => $user_id,
                'items' => []
            ];

            // Prepare items for the purchase order
            foreach ($quotation_items as $item) {
                $po_data['items'][] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_id' => $item['unit_id'],
                    'unit_price' => $item['unit_price'],
                    'tax_rate' => $item['tax_rate'],
                    'discount_rate' => $item['discount_rate'],
                    'total_price' => $item['line_total'],
                    'description' => $item['notes'],
                    'source_item_id' => $item['quotation_item_id']
                ];
            }

            // Create the purchase order
            $po_id = $this->model_purchase_order->addPurchaseOrder($po_data);

            if (!$po_id) {
                throw new Exception("Failed to create purchase order.");
            }

            // Update quotation status to converted
            $this->db->query("UPDATE `" . DB_PREFIX . "purchase_quotation` SET
                 status = 'converted',
                 updated_by = '" . (int)$user_id . "',
                 updated_at = NOW()
               WHERE quotation_id = '" . (int)$quotation_id . "'");

            // Add history record
            $this->addQuotationHistory([
                'quotation_id' => $quotation_id,
                'user_id' => $user_id,
                'action' => 'converted',
                'description' => 'Quotation converted to Purchase Order #' . $po_id
            ]);

            $this->db->query("COMMIT");

            return $po_id;
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            throw $e;
        }
    }

    /**
     * Get documents attached to a quotation
     *
     * @param int $quotation_id Quotation ID
     * @return array Document information
     */
    public function getDocuments($quotation_id) {
        $query = $this->db->query("SELECT qd.*, u.username as uploaded_by_name
            FROM " . DB_PREFIX . "quotation_document qd
            LEFT JOIN " . DB_PREFIX . "user u ON (qd.uploaded_by = u.user_id)
            WHERE qd.quotation_id = '" . (int)$quotation_id . "'
            ORDER BY qd.upload_date DESC");

        return $query->rows;
    }

    /**
     * Get a specific document
     *
     * @param int $document_id Document ID
     * @return array Document information
     */
    public function getDocument($document_id) {
        $query = $this->db->query("SELECT qd.*, u.username as uploaded_by_name
            FROM " . DB_PREFIX . "quotation_document qd
            LEFT JOIN " . DB_PREFIX . "user u ON (qd.uploaded_by = u.user_id)
            WHERE qd.document_id = '" . (int)$document_id . "'");

        return $query->row;
    }

    /**
     * Upload a document for a quotation
     *
     * @param int $quotation_id Quotation ID
     * @param array $file File information from $_FILES
     * @param string $document_type Type of document
     * @param int $user_id User who uploaded
     * @return array Upload information
     */
    public function uploadDocument($quotation_id, $file, $document_type, $user_id) {
        // Check if quotation exists
        $quotation_info = $this->getQuotation($quotation_id);
        if (!$quotation_info) {
            throw new Exception("Quotation not found.");
        }

        // Validate file
        $allowed_extensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($file_extension, $allowed_extensions)) {
            throw new Exception("Invalid file type. Allowed types: " . implode(', ', $allowed_extensions));
        }

        if ($file['size'] > 10485760) { // 10MB limit
            throw new Exception("File size exceeds maximum limit (10MB).");
        }

        // Create upload directory if it doesn't exist
        $upload_dir = DIR_UPLOAD . 'purchase/quotations/' . $quotation_id . '/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Generate unique filename
        $filename = 'quotation_' . $quotation_id . '_' . uniqid() . '.' . $file_extension;
        $file_path = 'purchase/quotations/' . $quotation_id . '/' . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], DIR_UPLOAD . $file_path)) {
            throw new Exception("Failed to upload file.");
        }

        // Save document information to database
        $this->db->query("INSERT INTO `" . DB_PREFIX . "purchase_document` SET
             reference_type = 'quotation',
             reference_id = '" . (int)$quotation_id . "',
             document_name = '" . $this->db->escape($file['name']) . "',
             file_path = '" . $this->db->escape($file_path) . "',
             document_type = '" . $this->db->escape($document_type) . "',
             uploaded_by = '" . (int)$user_id . "',
             upload_date = NOW()
        ");

        $document_id = $this->db->getLastId();

        // Add history record
        $this->addQuotationHistory([
            'quotation_id' => $quotation_id,
            'user_id' => $user_id,
            'action' => 'document_uploaded',
            'description' => 'Document uploaded: ' . $file['name']
        ]);

        return [
            'document_id' => $document_id,
            'name' => $file['name'],
            'type' => $document_type,
            'path' => $file_path,
            'size' => $file['size'],
            'upload_date' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Delete a document
     *
     * @param int $document_id Document ID
     * @return bool Success status
     */
    public function deleteDocument($document_id) {
        // Get document info
        $document_info = $this->getDocument($document_id);

        if (!$document_info) {
            throw new Exception("Document not found.");
        }

        // Delete physical file
        $this->deletePhysicalDocument($document_info['file_path']);

        // Delete database record
        $this->db->query("DELETE FROM " . DB_PREFIX . "quotation_document
            WHERE document_id = '" . (int)$document_id . "'");

        return true;
    }

    /**
     * Delete a physical document file
     *
     * @param string $file_path File path relative to upload directory
     * @return bool Success status
     */
    protected function deletePhysicalDocument($file_path) {
        $full_path = DIR_UPLOAD . $file_path;

        if (file_exists($full_path)) {
            return unlink($full_path);
        }

        return true;
    }

    /**
     * Add a history record for quotation
     *
     * @param array $data History data
     * @return bool Success status
     */
    public function addQuotationHistory($data) {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "purchase_quotation_history` SET
             quotation_id = '" . (int)$data['quotation_id'] . "',
             user_id = '" . (int)$data['user_id'] . "',
             action = '" . $this->db->escape($data['action']) . "',
             description = '" . $this->db->escape($data['description']) . "',
             created_at = NOW()
        ");

        return true;
    }

    /**
     * Get history records for a quotation
     *
     * @param int $quotation_id Quotation ID
     * @return array History records
     */
    public function getQuotationHistory($quotation_id) {
        $query = $this->db->query("SELECT h.*, CONCAT(u.firstname, ' ', u.lastname) AS user_name
               FROM `" . DB_PREFIX . "purchase_quotation_history` h
               LEFT JOIN `" . DB_PREFIX . "user` u ON (h.user_id = u.user_id)
               WHERE h.quotation_id = '" . (int)$quotation_id . "'
               ORDER BY h.created_at DESC");

        return $query->rows;
    }

    /**
     * Generate a unique quotation number
     *
     * @return string Quotation number
     */
    protected function generateQuotationNumber() {
        $prefix = 'QUO-';
        $year = date('Y');
        $month = date('m');

        // Get the highest quotation number with this prefix
        $query = $this->db->query("SELECT MAX(CAST(SUBSTRING(quotation_number, " . (strlen($prefix) + 1) . ") AS UNSIGNED)) AS max_number
                                  FROM `" . DB_PREFIX . "purchase_quotation`
                                  WHERE quotation_number LIKE '" . $prefix . "%'");

        $max_number = $query->row['max_number'] ?? 0;
        $next_number = $max_number + 1;

        return $prefix . str_pad($next_number, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Get data for quotation comparison
     *
     * @param int $requisition_id Requisition ID
     * @return array Comparison data
     */
    public function getComparisonData($requisition_id) {
        // Get all quotations for this requisition
        $sql = "SELECT q.*,
                CONCAT(s.firstname, ' ', s.lastname) AS supplier_name,
                c.code AS currency_code
               FROM `" . DB_PREFIX . "purchase_quotation` q
               LEFT JOIN `" . DB_PREFIX . "supplier` s ON (q.supplier_id = s.supplier_id)
               LEFT JOIN `" . DB_PREFIX . "currency` c ON (q.currency_id = c.currency_id)
               WHERE q.requisition_id = '" . (int)$requisition_id . "'
               ORDER BY q.total_amount ASC";

        $query = $this->db->query($sql);
        $quotations = $query->rows;

        if (empty($quotations)) {
            return false;
        }

        // Get requisition information
        $this->load->model('purchase/requisition');
        $requisition_info = $this->model_purchase_requisition->getRequisition($requisition_id);

        if (!$requisition_info) {
            return false;
        }

        // Get all products from the requisition
        $requisition_items = $this->model_purchase_requisition->getRequisitionItems($requisition_id);

        // Build comparison data structure
        $comparison = [];
        $quotation_data = [];

        // Store the lowest total for highlighting
        $lowest_total = PHP_FLOAT_MAX;
        $lowest_total_quotation_id = 0;

        foreach ($quotations as $quotation) {
            $quotation_id = $quotation['quotation_id'];

            // Get items for this quotation
            $items = $this->getQuotationItems($quotation_id);

            $quotation_data[$quotation_id] = [
                'quotation_id' => $quotation_id,
                'quotation_number' => $quotation['quotation_number'],
                'supplier_id' => $quotation['supplier_id'],
                'supplier_name' => $quotation['supplier_name'],
                'currency_code' => $quotation['currency_code'],
                'total_amount' => $quotation['total_amount'],
                'total_formatted' => $this->currency->format($quotation['total_amount'], $quotation['currency_code']),
                'status' => $quotation['status'],
                'status_text' => $this->getStatusText($quotation['status']),
                'items' => $items,
                'has_lowest_total' => false
            ];

            // Check if this quotation has the lowest total
            if ($quotation['total_amount'] < $lowest_total) {
                $lowest_total = $quotation['total_amount'];
                $lowest_total_quotation_id = $quotation_id;
            }

            // Map items by product_id for comparison
            foreach ($items as $item) {
                if (!isset($comparison[$item['product_id']])) {
                    $product_info = $this->getProductInfo($item['product_id']);

                    $comparison[$item['product_id']] = [
                        'product_id' => $item['product_id'],
                        'product_name' => $product_info['name'],
                        'product_model' => $product_info['model'],
                        'quantity' => 0, // Will be set from requisition item
                        'unit_id' => $item['unit_id'],
                        'unit_name' => $item['unit_name'],
                        'supplier_prices' => [],
                        'best_price_supplier_id' => 0,
                        'best_price' => PHP_FLOAT_MAX
                    ];
                }

                // Add this supplier's price
                $comparison[$item['product_id']]['supplier_prices'][$quotation_id] = [
                    'unit_price' => $item['unit_price'],
                    'unit_price_formatted' => $this->currency->format($item['unit_price'], $quotation['currency_code']),
                    'line_total' => $item['line_total'],
                    'line_total_formatted' => $this->currency->format($item['line_total'], $quotation['currency_code']),
                    'is_best_price' => false
                ];

                // Check if this is the best price for this product
                if ($item['unit_price'] < $comparison[$item['product_id']]['best_price']) {
                    $comparison[$item['product_id']]['best_price'] = $item['unit_price'];
                    $comparison[$item['product_id']]['best_price_supplier_id'] = $quotation['supplier_id'];
                }
            }
        }

        // Mark the quotation with the lowest total
        if (isset($quotation_data[$lowest_total_quotation_id])) {
            $quotation_data[$lowest_total_quotation_id]['has_lowest_total'] = true;
        }

        // Mark best prices for each product
        foreach ($comparison as &$product) {
            foreach ($product['supplier_prices'] as $quotation_id => &$price) {
                if ($price['unit_price'] == $product['best_price']) {
                    $price['is_best_price'] = true;
                }
            }
        }

        // Add quantities from requisition items
        foreach ($requisition_items as $req_item) {
            if (isset($comparison[$req_item['product_id']])) {
                $comparison[$req_item['product_id']]['quantity'] = $req_item['quantity'];
            }
        }

        return [
            'requisition' => $requisition_info,
            'quotations' => $quotation_data,
            'comparison' => array_values($comparison) // Convert to indexed array for easier iteration
        ];
    }


    /**
     * Get product information
     *
     * @param int $product_id Product ID
     * @return array Product information
     */
    protected function getProductInfo($product_id) {
        $query = $this->db->query("SELECT p.product_id, p.model, pd.name
                                  FROM `" . DB_PREFIX . "product` p
                                  LEFT JOIN `" . DB_PREFIX . "product_description` pd ON (p.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "')
                                  WHERE p.product_id = '" . (int)$product_id . "'");

        return $query->row;
    }

    /**
     * Search quotations for select2
     *
     * @param string $search Search term
     * @return array Search results
     */
    public function searchQuotations($search) {
        $sql = "SELECT q.quotation_id, q.quotation_number, q.total_amount,
                CONCAT(s.firstname, ' ', s.lastname) AS supplier_name,
                c.code AS currency_code
               FROM `" . DB_PREFIX . "purchase_quotation` q
               LEFT JOIN `" . DB_PREFIX . "supplier` s ON (q.supplier_id = s.supplier_id)
               LEFT JOIN `" . DB_PREFIX . "currency` c ON (q.currency_id = c.currency_id)
               WHERE q.quotation_number LIKE '%" . $this->db->escape($search) . "%'
               OR CONCAT(s.firstname, ' ', s.lastname) LIKE '%" . $this->db->escape($search) . "%'
               ORDER BY q.quotation_number DESC
               LIMIT 15";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    /**
     * Get username for user ID
     *
     * @param int $user_id User ID
     * @return string Username
     */
    public function getUserName($user_id) {
        $query = $this->db->query("SELECT CONCAT(firstname, ' ', lastname) AS name
                                  FROM `" . DB_PREFIX . "user`
                                  WHERE user_id = '" . (int)$user_id . "'");

        return $query->row['name'] ?? 'Unknown User';
    }

    /**
     * Get status text
     *
     * @param string $status Status code
     * @return string Status text
     */
    public function getStatusText($status) {
        $this->load->language('purchase/quotation');

        $statuses = [
            'draft' => $this->language->get('text_status_draft'),
            'pending' => $this->language->get('text_status_pending'),
            'approved' => $this->language->get('text_status_approved'),
            'rejected' => $this->language->get('text_status_rejected'),
            'cancelled' => $this->language->get('text_status_cancelled'),
            'converted' => $this->language->get('text_status_converted')
        ];

        return $statuses[$status] ?? $status;
    }

    /**
     * Get CSS class for status
     *
     * @param string $status Status code
     * @return string CSS class
     */
    public function getStatusClass($status) {
        $classes = [
            'draft' => 'default',
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'cancelled' => 'danger',
            'converted' => 'info'
        ];

        return $classes[$status] ?? 'default';
    }
/**
 * الحصول على بيانات مورد محدد
 *
 * @param int $supplier_id معرف المورد
 * @return array بيانات المورد
 */
public function getSupplier($supplier_id) {
    $query = $this->db->query("SELECT s.*,
                             CONCAT(s.firstname, ' ', IFNULL(s.lastname, '')) AS name,
                             s.email, s.telephone, s.fax,
                             s.account_code,
                             sg.name AS supplier_group_name
                         FROM `" . DB_PREFIX . "supplier` s
                         LEFT JOIN `" . DB_PREFIX . "supplier_group_description` sg
                            ON (s.supplier_group_id = sg.supplier_group_id AND sg.language_id = '" . (int)$this->config->get('config_language_id') . "')
                         WHERE s.supplier_id = '" . (int)$supplier_id . "'");

    if ($query->num_rows) {
        // استرجاع عنوان المورد الافتراضي
        $address_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "supplier_address`
                                        WHERE supplier_id = '" . (int)$supplier_id . "'
                                        ORDER BY address_id ASC LIMIT 1");

        $address_data = array();
        if ($address_query->num_rows) {
            $address_data = array(
                'address_id'     => $address_query->row['address_id'],
                'address_1'      => $address_query->row['address_1'],
                'address_2'      => $address_query->row['address_2'],
                'city'           => $address_query->row['city'],
                'postcode'       => $address_query->row['postcode'],
                'country_id'     => $address_query->row['country_id'],
                'zone_id'        => $address_query->row['zone_id'],
                'company'        => $address_query->row['company']
            );

            // استرجاع اسم الدولة والمنطقة
            $country_query = $this->db->query("SELECT name FROM `" . DB_PREFIX . "country`
                                            WHERE country_id = '" . (int)$address_query->row['country_id'] . "'");
            if ($country_query->num_rows) {
                $address_data['country'] = $country_query->row['name'];
            } else {
                $address_data['country'] = '';
            }

            $zone_query = $this->db->query("SELECT name FROM `" . DB_PREFIX . "zone`
                                         WHERE zone_id = '" . (int)$address_query->row['zone_id'] . "'");
            if ($zone_query->num_rows) {
                $address_data['zone'] = $zone_query->row['name'];
            } else {
                $address_data['zone'] = '';
            }
        }

        return array_merge($query->row, $address_data);
    } else {
        return array();
    }
}

/**
 * الحصول على عنوان المورد
 *
 * @param int $supplier_id معرف المورد
 * @param int $address_id معرف العنوان (اختياري)
 * @return array بيانات العنوان
 */
public function getSupplierAddress($supplier_id, $address_id = 0) {
    $address_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "supplier_address`
                                     WHERE supplier_id = '" . (int)$supplier_id . "'
                                     " . ($address_id ? "AND address_id = '" . (int)$address_id . "'" : "") . "
                                     ORDER BY address_id ASC LIMIT 1");

    if ($address_query->num_rows) {
        $country_query = $this->db->query("SELECT name FROM `" . DB_PREFIX . "country`
                                         WHERE country_id = '" . (int)$address_query->row['country_id'] . "'");
        if ($country_query->num_rows) {
            $country = $country_query->row['name'];
        } else {
            $country = '';
        }

        $zone_query = $this->db->query("SELECT name FROM `" . DB_PREFIX . "zone`
                                      WHERE zone_id = '" . (int)$address_query->row['zone_id'] . "'");
        if ($zone_query->num_rows) {
            $zone = $zone_query->row['name'];
        } else {
            $zone = '';
        }

        // تنسيق العنوان
        $address_format_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country`
                                              WHERE country_id = '" . (int)$address_query->row['country_id'] . "'");

        if ($address_format_query->num_rows) {
            $address_format = $address_format_query->row['address_format'];
        } else {
            $address_format = '{firstname} {lastname}' . "\n" . '{company}' . "\n" . '{address_1}' . "\n" . '{address_2}' . "\n" . '{city} {postcode}' . "\n" . '{zone}' . "\n" . '{country}';
        }

        $find = array(
            '{firstname}',
            '{lastname}',
            '{company}',
            '{address_1}',
            '{address_2}',
            '{city}',
            '{postcode}',
            '{zone}',
            '{country}'
        );

        $replace = array(
            'firstname' => $address_query->row['firstname'],
            'lastname'  => $address_query->row['lastname'],
            'company'   => $address_query->row['company'],
            'address_1' => $address_query->row['address_1'],
            'address_2' => $address_query->row['address_2'],
            'city'      => $address_query->row['city'],
            'postcode'  => $address_query->row['postcode'],
            'zone'      => $zone,
            'country'   => $country
        );

        $formatted_address = str_replace(array("\r\n", "\r", "\n"), '<br />', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br />', trim(str_replace($find, $replace, $address_format))));

        return array(
            'address_id'        => $address_query->row['address_id'],
            'supplier_id'       => $address_query->row['supplier_id'],
            'firstname'         => $address_query->row['firstname'],
            'lastname'          => $address_query->row['lastname'],
            'company'           => $address_query->row['company'],
            'address_1'         => $address_query->row['address_1'],
            'address_2'         => $address_query->row['address_2'],
            'city'              => $address_query->row['city'],
            'postcode'          => $address_query->row['postcode'],
            'zone_id'           => $address_query->row['zone_id'],
            'zone'              => $zone,
            'country_id'        => $address_query->row['country_id'],
            'country'           => $country,
            'formatted_address' => $formatted_address
        );
    } else {
        return array();
    }
}

/**
 * Get price history for a product/unit combination
 */
public function getProductPriceHistory($product_id, $unit_id) {
    $sql = "SELECT qi.unit_price,
                   q.quotation_number,
                   q.created_at as date,
                   CONCAT(s.firstname, ' ', s.lastname) as supplier_name,
                   c.code as currency_code,
                   q.exchange_rate
            FROM " . DB_PREFIX . "purchase_quotation_item qi
            LEFT JOIN " . DB_PREFIX . "purchase_quotation q ON (q.quotation_id = qi.quotation_id)
            LEFT JOIN " . DB_PREFIX . "supplier s ON (s.supplier_id = q.supplier_id)
            LEFT JOIN " . DB_PREFIX . "currency c ON (c.currency_id = q.currency_id)
            WHERE qi.product_id = '" . (int)$product_id . "'
            AND qi.unit_id = '" . (int)$unit_id . "'
            AND q.status IN ('approved', 'converted')
            ORDER BY q.created_at DESC
            LIMIT 10";

    $query = $this->db->query($sql);
    return $query->rows;
}

/**
 * Get supplier performance history for a product
 */
public function getSupplierProductHistory($product_id, $supplier_id) {
    $sql = "SELECT po.po_number,
                   po.created_at as date,
                   COALESCE(gr.quality_rating, 0) as quality_rating,
                   COALESCE(gr.delivery_rating, 0) as delivery_rating,
                   CASE
                       WHEN gr.actual_delivery_date <= po.expected_delivery_date THEN 1
                       ELSE 0
                   END as is_on_time,
                   gr.quality_notes,
                   gr.delivery_notes
            FROM " . DB_PREFIX . "purchase_order po
            LEFT JOIN " . DB_PREFIX . "purchase_order_item poi ON (po.po_id = poi.po_id)
            LEFT JOIN " . DB_PREFIX . "goods_receipt gr ON (po.po_id = gr.po_id)
            WHERE poi.product_id = '" . (int)$product_id . "'
            AND po.supplier_id = '" . (int)$supplier_id . "'
            AND po.status = 'completed'
            ORDER BY po.created_at DESC
            LIMIT 10";

    $query = $this->db->query($sql);
    return $query->rows;
}

/**
 * Get supplier performance analytics
 */
public function getSupplierAnalytics($supplier_id, $period = 'last_year') {
    $date_condition = "";
    switch ($period) {
        case 'last_month':
            $date_condition = "AND po.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            break;
        case 'last_quarter':
            $date_condition = "AND po.created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
            break;
        case 'last_year':
            $date_condition = "AND po.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            break;
        default:
            $date_condition = "";
    }

    $sql = "SELECT
            COUNT(DISTINCT po.po_id) as total_orders,
            AVG(gr.quality_rating) as avg_quality_rating,
            AVG(gr.delivery_rating) as avg_delivery_rating,
            SUM(CASE WHEN gr.actual_delivery_date <= po.expected_delivery_date THEN 1 ELSE 0 END) as on_time_deliveries,
            AVG(CASE WHEN gr.actual_delivery_date <= po.expected_delivery_date THEN 1 ELSE 0 END) as on_time_delivery_rate,
            AVG(DATEDIFF(gr.actual_delivery_date, po.expected_delivery_date)) as avg_delay_days,
            COUNT(DISTINCT CASE WHEN q.status = 'rejected' THEN q.quotation_id END) as rejected_quotations,
            COUNT(DISTINCT q.quotation_id) as total_quotations
            FROM " . DB_PREFIX . "purchase_order po
            LEFT JOIN " . DB_PREFIX . "goods_receipt gr ON (po.po_id = gr.po_id)
            LEFT JOIN " . DB_PREFIX . "purchase_quotation q ON (po.supplier_id = q.supplier_id)
            WHERE po.supplier_id = '" . (int)$supplier_id . "'
            AND po.status = 'completed'
            " . $date_condition;

    $query = $this->db->query($sql);
    return $query->row;
}

/**
 * Get trending analysis for a product
 */
public function getProductPriceTrend($product_id, $unit_id, $period = 'last_year') {
    $date_condition = "";
    switch ($period) {
        case 'last_month':
            $date_condition = "AND q.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            break;
        case 'last_quarter':
            $date_condition = "AND q.created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
            break;
        case 'last_year':
            $date_condition = "AND q.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            break;
        default:
            $date_condition = "";
    }

    $sql = "SELECT
            DATE_FORMAT(q.created_at, '%Y-%m') as month,
            AVG(qi.unit_price * q.exchange_rate) as avg_price,
            MIN(qi.unit_price * q.exchange_rate) as min_price,
            MAX(qi.unit_price * q.exchange_rate) as max_price,
            COUNT(DISTINCT q.supplier_id) as supplier_count
            FROM " . DB_PREFIX . "purchase_quotation_item qi
            LEFT JOIN " . DB_PREFIX . "purchase_quotation q ON (q.quotation_id = qi.quotation_id)
            WHERE qi.product_id = '" . (int)$product_id . "'
            AND qi.unit_id = '" . (int)$unit_id . "'
            AND q.status IN ('approved', 'converted')
            " . $date_condition . "
            GROUP BY DATE_FORMAT(q.created_at, '%Y-%m')
            ORDER BY month DESC";

    $query = $this->db->query($sql);
    return $query->rows;
}

/**
 * Get last approved price for a product
 */
public function getLastApprovedPrice($product_id, $unit_id) {
    $sql = "SELECT qi.unit_price,
                   q.exchange_rate,
                   c.code as currency_code,
                   q.created_at
            FROM " . DB_PREFIX . "purchase_quotation_item qi
            LEFT JOIN " . DB_PREFIX . "purchase_quotation q ON (q.quotation_id = qi.quotation_id)
            LEFT JOIN " . DB_PREFIX . "currency c ON (c.currency_id = q.currency_id)
            WHERE qi.product_id = '" . (int)$product_id . "'
            AND qi.unit_id = '" . (int)$unit_id . "'
            AND q.status IN ('approved', 'converted')
            ORDER BY q.created_at DESC
            LIMIT 1";

    $query = $this->db->query($sql);
    return $query->row;
}

/**
 * Add a document to a quotation
 */
public function addDocument($data) {
    $this->db->query("INSERT INTO " . DB_PREFIX . "quotation_document SET
        quotation_id = '" . (int)$data['quotation_id'] . "',
        filename = '" . $this->db->escape($data['filename']) . "',
        original_filename = '" . $this->db->escape($data['original_filename']) . "',
        file_type = '" . $this->db->escape($data['file_type']) . "',
        file_size = '" . (int)$data['file_size'] . "',
        document_type = '" . $this->db->escape($data['document_type']) . "',
        uploaded_by = '" . (int)$data['uploaded_by'] . "',
        upload_date = '" . $this->db->escape($data['upload_date']) . "'");

    return $this->db->getLastId();
}

/**
 * Get document by ID
 */
public function getDocument($document_id) {
    $query = $this->db->query("SELECT qd.*, u.username as uploaded_by_name
        FROM " . DB_PREFIX . "quotation_document qd
        LEFT JOIN " . DB_PREFIX . "user u ON (qd.uploaded_by = u.user_id)
        WHERE qd.document_id = '" . (int)$document_id . "'");

    return $query->row;
}

/**
 * Get all documents for a quotation
 */
public function getDocuments($quotation_id) {
    $query = $this->db->query("SELECT qd.*, u.username as uploaded_by_name
        FROM " . DB_PREFIX . "quotation_document qd
        LEFT JOIN " . DB_PREFIX . "user u ON (qd.uploaded_by = u.user_id)
        WHERE qd.quotation_id = '" . (int)$quotation_id . "'
        ORDER BY qd.upload_date DESC");

    return $query->rows;
}

/**
 * Delete a document
 */
public function deleteDocument($document_id) {
    $document_info = $this->getDocument($document_id);

    if ($document_info) {
        // Delete the physical file
        $file_path = DIR_UPLOAD . 'quotation_documents/' . $document_info['filename'];
        if (is_file($file_path)) {
            unlink($file_path);
        }

        // Delete database record
        $this->db->query("DELETE FROM " . DB_PREFIX . "quotation_document
            WHERE document_id = '" . (int)$document_id . "'");

        return true;
    }

    return false;
}

/**
 * Clean up orphaned documents
 */
public function cleanupOrphanedDocuments() {
    // Get list of files in upload directory
    $files = glob(DIR_UPLOAD . 'quotation_documents/*');

    if ($files) {
        foreach ($files as $file) {
            $filename = basename($file);

            // Check if file exists in database
            $query = $this->db->query("SELECT document_id FROM " . DB_PREFIX . "quotation_document
                WHERE filename = '" . $this->db->escape($filename) . "'");

            if (!$query->num_rows) {
                // File not in database, delete it
                unlink($file);
            }
        }
    }
}

    public function addQuotation($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "purchase_quotation SET
            requisition_id = '" . (int)$data['requisition_id'] . "',
            supplier_id = '" . (int)$data['supplier_id'] . "',
            quotation_number = '" . $this->generateQuotationNumber() . "',
            currency_id = '" . (int)$data['currency_id'] . "',
            exchange_rate = '" . (float)$data['exchange_rate'] . "',
            validity_date = '" . $this->db->escape($data['validity_date']) . "',
            payment_terms = '" . $this->db->escape($data['payment_terms']) . "',
            delivery_terms = '" . $this->db->escape($data['delivery_terms']) . "',
            notes = '" . $this->db->escape($data['notes']) . "',
            tax_included = '" . (int)$data['tax_included'] . "',
            tax_rate = '" . (float)$data['tax_rate'] . "',
            subtotal = '" . (float)$data['subtotal'] . "',
            discount_amount = '" . (float)$data['discount_amount'] . "',
            tax_amount = '" . (float)$data['tax_amount'] . "',
            total_amount = '" . (float)$data['total_amount'] . "',
            status = '" . $this->db->escape($data['status']) . "',
            created_by = '" . (int)$data['created_by'] . "',
            created_at = NOW()");

        $quotation_id = $this->db->getLastId();

        // Save quotation items
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $item) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "purchase_quotation_item SET
                    quotation_id = '" . (int)$quotation_id . "',
                    product_id = '" . (int)$item['product_id'] . "',
                    quantity = '" . (float)$item['quantity'] . "',
                    unit_id = '" . (int)$item['unit_id'] . "',
                    unit_price = '" . (float)$item['unit_price'] . "',
                    discount_type = '" . $this->db->escape($item['discount_type']) . "',
                    discount_value = '" . (float)$item['discount_value'] . "',
                    discount_amount = '" . (float)$item['discount_amount'] . "',
                    tax_rate = '" . (float)$item['tax_rate'] . "',
                    tax_amount = '" . (float)$item['tax_amount'] . "',
                    line_total = '" . (float)$item['line_total'] . "'");
            }
        }

        // Add history record
        $this->addHistory($quotation_id, 'create', 'Quotation created', $data['created_by']);

        return ['quotation_id' => $quotation_id];
    }

    public function editQuotation($data) {
        // First check if quotation exists and can be edited
        $quotation_info = $this->getQuotation($data['quotation_id']);
        if (!$quotation_info) {
            return ['error' => 'Quotation not found'];
        }

        // Check if status allows editing
        if (!in_array($quotation_info['status'], ['draft', 'pending', 'rejected'])) {
            return ['error' => 'Current status does not allow editing'];
        }

        $this->db->query("UPDATE " . DB_PREFIX . "purchase_quotation SET
            supplier_id = '" . (int)$data['supplier_id'] . "',
            currency_id = '" . (int)$data['currency_id'] . "',
            exchange_rate = '" . (float)$data['exchange_rate'] . "',
            validity_date = '" . $this->db->escape($data['validity_date']) . "',
            payment_terms = '" . $this->db->escape($data['payment_terms']) . "',
            delivery_terms = '" . $this->db->escape($data['delivery_terms']) . "',
            notes = '" . $this->db->escape($data['notes']) . "',
            tax_included = '" . (int)$data['tax_included'] . "',
            tax_rate = '" . (float)$data['tax_rate'] . "',
            subtotal = '" . (float)$data['subtotal'] . "',
            discount_amount = '" . (float)$data['discount_amount'] . "',
            tax_amount = '" . (float)$data['tax_amount'] . "',
            total_amount = '" . (float)$data['total_amount'] . "',
            status = '" . $this->db->escape($data['status']) . "',
            updated_by = '" . (int)$data['updated_by'] . "',
            updated_at = NOW()
            WHERE quotation_id = '" . (int)$data['quotation_id'] . "'");

        // Delete existing items
        $this->db->query("DELETE FROM " . DB_PREFIX . "purchase_quotation_item
            WHERE quotation_id = '" . (int)$data['quotation_id'] . "'");

        // Insert new items
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $item) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "purchase_quotation_item SET
                    quotation_id = '" . (int)$data['quotation_id'] . "',
                    product_id = '" . (int)$item['product_id'] . "',
                    quantity = '" . (float)$item['quantity'] . "',
                    unit_id = '" . (int)$item['unit_id'] . "',
                    unit_price = '" . (float)$item['unit_price'] . "',
                    discount_type = '" . $this->db->escape($item['discount_type']) . "',
                    discount_value = '" . (float)$item['discount_value'] . "',
                    discount_amount = '" . (float)$item['discount_amount'] . "',
                    tax_rate = '" . (float)$item['tax_rate'] . "',
                    tax_amount = '" . (float)$item['tax_amount'] . "',
                    line_total = '" . (float)$item['line_total'] . "'");
            }
        }

        // Add history record
        $this->addHistory($data['quotation_id'], 'edit', 'Quotation updated', $data['updated_by']);

        return ['success' => true];
    }

    public function deleteQuotation($quotation_id, $user_id) {
        // Check if quotation exists and can be deleted
        $quotation_info = $this->getQuotation($quotation_id);
        if (!$quotation_info) {
            return ['error' => 'Quotation not found'];
        }

        // Check if status allows deletion
        if (!in_array($quotation_info['status'], ['draft', 'pending', 'rejected'])) {
            return ['error' => 'Current status does not allow deletion'];
        }

        // Delete items first
        $this->db->query("DELETE FROM " . DB_PREFIX . "purchase_quotation_item
            WHERE quotation_id = '" . (int)$quotation_id . "'");

        // Delete documents if any
        $documents = $this->getQuotationDocuments($quotation_id);
        foreach ($documents as $document) {
            $this->deleteDocument($document['document_id']);
        }

        // Delete quotation
        $this->db->query("DELETE FROM " . DB_PREFIX . "purchase_quotation
            WHERE quotation_id = '" . (int)$quotation_id . "'");

        return ['success' => true];
    }

    public function approveQuotation($quotation_id, $user_id) {
        // Check if quotation exists and can be approved
        $quotation_info = $this->getQuotation($quotation_id);
        if (!$quotation_info) {
            return ['error' => 'Quotation not found'];
        }

        if ($quotation_info['status'] !== 'pending') {
            return ['error' => 'Only pending quotations can be approved'];
        }

        $this->db->query("UPDATE " . DB_PREFIX . "purchase_quotation SET
            status = 'approved',
            approved_by = '" . (int)$user_id . "',
            approved_at = NOW()
            WHERE quotation_id = '" . (int)$quotation_id . "'");

        $this->addHistory($quotation_id, 'approve', 'Quotation approved', $user_id);

        return ['success' => true];
    }

    public function rejectQuotation($quotation_id, $reason, $user_id) {
        // Check if quotation exists and can be rejected
        $quotation_info = $this->getQuotation($quotation_id);
        if (!$quotation_info) {
            return ['error' => 'Quotation not found'];
        }

        if ($quotation_info['status'] !== 'pending') {
            return ['error' => 'Only pending quotations can be rejected'];
        }

        $this->db->query("UPDATE " . DB_PREFIX . "purchase_quotation SET
            status = 'rejected',
            rejection_reason = '" . $this->db->escape($reason) . "',
            rejected_by = '" . (int)$user_id . "',
            rejected_at = NOW()
            WHERE quotation_id = '" . (int)$quotation_id . "'");

        $this->addHistory($quotation_id, 'reject', 'Quotation rejected: ' . $reason, $user_id);

        return ['success' => true];
    }

    public function convertToPurchaseOrder($quotation_id, $user_id) {
        // Check if quotation exists and can be converted
        $quotation_info = $this->getQuotation($quotation_id);
        if (!$quotation_info) {
            return ['error' => 'Quotation not found'];
        }

        if ($quotation_info['status'] !== 'approved') {
            return ['error' => 'Only approved quotations can be converted to purchase orders'];
        }

        // Load purchase order model
        $this->load->model('purchase/order');

        // Prepare data for purchase order
        $po_data = [
            'requisition_id' => $quotation_info['requisition_id'],
            'supplier_id' => $quotation_info['supplier_id'],
            'quotation_id' => $quotation_id,
            'currency_id' => $quotation_info['currency_id'],
            'exchange_rate' => $quotation_info['exchange_rate'],
            'payment_terms' => $quotation_info['payment_terms'],
            'delivery_terms' => $quotation_info['delivery_terms'],
            'notes' => $quotation_info['notes'],
            'tax_included' => $quotation_info['tax_included'],
            'tax_rate' => $quotation_info['tax_rate'],
            'subtotal' => $quotation_info['subtotal'],
            'discount_amount' => $quotation_info['discount_amount'],
            'tax_amount' => $quotation_info['tax_amount'],
            'total_amount' => $quotation_info['total_amount'],
            'status' => 'draft',
            'created_by' => $user_id
        ];

        // Get quotation items
        $items = $this->getQuotationItems($quotation_id);
        $po_data['items'] = [];

        foreach ($items as $item) {
            $po_data['items'][] = [
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_id' => $item['unit_id'],
                'unit_price' => $item['unit_price'],
                'discount_type' => $item['discount_type'],
                'discount_value' => $item['discount_value'],
                'discount_amount' => $item['discount_amount'],
                'tax_rate' => $item['tax_rate'],
                'tax_amount' => $item['tax_amount'],
                'line_total' => $item['line_total']
            ];
        }

        // Create purchase order
        $result = $this->model_purchase_order->addPurchaseOrder($po_data);

        if (isset($result['po_id'])) {
            // Update quotation status
            $this->db->query("UPDATE " . DB_PREFIX . "purchase_quotation SET
                status = 'converted',
                converted_to_po = '" . (int)$result['po_id'] . "',
                converted_by = '" . (int)$user_id . "',
                converted_at = NOW()
                WHERE quotation_id = '" . (int)$quotation_id . "'");

            $this->addHistory($quotation_id, 'convert', 'Converted to Purchase Order #' . $result['po_number'], $user_id);

            return ['success' => true, 'po_id' => $result['po_id']];
        } else {
            return ['error' => 'Failed to create purchase order'];
        }
    }

    public function getQuotation($quotation_id) {
        $query = $this->db->query("SELECT q.*,
            r.req_number as requisition_number,
            CONCAT(s.firstname, ' ', s.lastname) as supplier_name,
            c.code as currency_code,
            CONCAT(u.firstname, ' ', u.lastname) as created_by_name
            FROM " . DB_PREFIX . "purchase_quotation q
            LEFT JOIN " . DB_PREFIX . "purchase_requisition r ON (q.requisition_id = r.requisition_id)
            LEFT JOIN " . DB_PREFIX . "supplier s ON (q.supplier_id = s.supplier_id)
            LEFT JOIN " . DB_PREFIX . "currency c ON (q.currency_id = c.currency_id)
            LEFT JOIN " . DB_PREFIX . "user u ON (q.created_by = u.user_id)
            WHERE q.quotation_id = '" . (int)$quotation_id . "'");

        return $query->row;
    }

    public function getQuotationItems($quotation_id) {
        $query = $this->db->query("SELECT qi.*,
            pd.name as product_name,
            u.desc_en as unit_name
            FROM " . DB_PREFIX . "purchase_quotation_item qi
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (qi.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "unit u ON (qi.unit_id = u.unit_id)
            WHERE qi.quotation_id = '" . (int)$quotation_id . "'
            AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

        return $query->rows;
    }

    public function getQuotations($data = array()) {
        $sql = "SELECT q.*,
            r.req_number as requisition_number,
            CONCAT(s.firstname, ' ', s.lastname) as supplier_name,
            c.code as currency_code,
            (SELECT COUNT(d.document_id) FROM " . DB_PREFIX . "purchase_quotation_document d
             WHERE d.quotation_id = q.quotation_id) as document_count
            FROM " . DB_PREFIX . "purchase_quotation q
            LEFT JOIN " . DB_PREFIX . "purchase_requisition r ON (q.requisition_id = r.requisition_id)
            LEFT JOIN " . DB_PREFIX . "supplier s ON (q.supplier_id = s.supplier_id)
            LEFT JOIN " . DB_PREFIX . "currency c ON (q.currency_id = c.currency_id)
            WHERE 1=1";

        if (!empty($data['filter_quotation_number'])) {
            $sql .= " AND q.quotation_number LIKE '%" . $this->db->escape($data['filter_quotation_number']) . "%'";
        }

        if (!empty($data['filter_requisition_id'])) {
            $sql .= " AND q.requisition_id = '" . (int)$data['filter_requisition_id'] . "'";
        }

        if (!empty($data['filter_supplier_id'])) {
            $sql .= " AND q.supplier_id = '" . (int)$data['filter_supplier_id'] . "'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND q.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(q.created_at) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(q.created_at) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $sort_data = array(
            'q.quotation_number',
            'r.req_number',
            's.firstname',
            'q.total_amount',
            'q.status',
            'q.created_at'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY q.created_at";
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

    public function getTotalQuotations($data = array()) {
        $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "purchase_quotation q WHERE 1=1";

        if (!empty($data['filter_quotation_number'])) {
            $sql .= " AND q.quotation_number LIKE '%" . $this->db->escape($data['filter_quotation_number']) . "%'";
        }

        if (!empty($data['filter_requisition_id'])) {
            $sql .= " AND q.requisition_id = '" . (int)$data['filter_requisition_id'] . "'";
        }

        if (!empty($data['filter_supplier_id'])) {
            $sql .= " AND q.supplier_id = '" . (int)$data['filter_supplier_id'] . "'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND q.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(q.created_at) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(q.created_at) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    // Document management methods
    public function addDocument($quotation_id, $data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "purchase_quotation_document SET
            quotation_id = '" . (int)$quotation_id . "',
            file_name = '" . $this->db->escape($data['file_name']) . "',
            original_name = '" . $this->db->escape($data['original_name']) . "',
            file_type = '" . $this->db->escape($data['file_type']) . "',
            file_size = '" . (int)$data['file_size'] . "',
            uploaded_by = '" . (int)$data['uploaded_by'] . "',
            uploaded_at = NOW()");

        return $this->db->getLastId();
    }

    public function deleteDocument($document_id) {
        $document = $this->getDocument($document_id);
        if ($document) {
            // Delete physical file
            $file_path = DIR_UPLOAD . 'quotation_documents/' . $document['file_name'];
            if (is_file($file_path)) {
                unlink($file_path);
            }

            // Delete from database
            $this->db->query("DELETE FROM " . DB_PREFIX . "purchase_quotation_document
                WHERE document_id = '" . (int)$document_id . "'");

            return true;
        }
        return false;
    }

    public function getDocument($document_id) {
        $query = $this->db->query("SELECT d.*, CONCAT(u.firstname, ' ', u.lastname) as uploaded_by_name
            FROM " . DB_PREFIX . "purchase_quotation_document d
            LEFT JOIN " . DB_PREFIX . "user u ON (d.uploaded_by = u.user_id)
            WHERE d.document_id = '" . (int)$document_id . "'");

        return $query->row;
    }

    public function getQuotationDocuments($quotation_id) {
        $query = $this->db->query("SELECT d.*, CONCAT(u.firstname, ' ', u.lastname) as uploaded_by_name
            FROM " . DB_PREFIX . "purchase_quotation_document d
            LEFT JOIN " . DB_PREFIX . "user u ON (d.uploaded_by = u.user_id)
            WHERE d.quotation_id = '" . (int)$quotation_id . "'
            ORDER BY d.uploaded_at DESC");

        return $query->rows;
    }

    // Helper methods
    private function generateQuotationNumber() {
        $prefix = 'QT' . date('Y');

        $query = $this->db->query("SELECT MAX(CAST(SUBSTRING(quotation_number, 7) AS UNSIGNED)) as max_number
            FROM " . DB_PREFIX . "purchase_quotation
            WHERE quotation_number LIKE '" . $prefix . "%'");

        $max_number = $query->row['max_number'];

        return $prefix . str_pad(($max_number + 1), 4, '0', STR_PAD_LEFT);
    }

    private function addHistory($quotation_id, $action, $description, $user_id) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "purchase_quotation_history SET
            quotation_id = '" . (int)$quotation_id . "',
            action = '" . $this->db->escape($action) . "',
            description = '" . $this->db->escape($description) . "',
            user_id = '" . (int)$user_id . "',
            created_at = NOW()");
    }

    public function getQuotationHistory($quotation_id) {
        $query = $this->db->query("SELECT h.*, CONCAT(u.firstname, ' ', u.lastname) as user_name
            FROM " . DB_PREFIX . "purchase_quotation_history h
            LEFT JOIN " . DB_PREFIX . "user u ON (h.user_id = u.user_id)
            WHERE h.quotation_id = '" . (int)$quotation_id . "'
            ORDER BY h.created_at DESC");

        return $query->rows;
    }

    public function getQuotationStats($data = array()) {
        $stats = array(
            'total' => 0,
            'draft' => 0,
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
            'converted' => 0,
            'total_amount' => 0
        );

        // Base query with filters
        $sql = "SELECT status, COUNT(*) as count, SUM(total_amount) as total
            FROM " . DB_PREFIX . "purchase_quotation
            WHERE 1=1";

        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(created_at) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(created_at) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $sql .= " GROUP BY status";

        $query = $this->db->query($sql);

        foreach ($query->rows as $row) {
            $stats[$row['status']] = $row['count'];
            $stats['total'] += $row['count'];
            $stats['total_amount'] += $row['total'];
        }

        return $stats;
    }

    // Product search for select2
    public function searchProducts($search) {
        $sql = "SELECT p.product_id, pd.name
            FROM " . DB_PREFIX . "product p
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND pd.name LIKE '%" . $this->db->escape($search) . "%'
            LIMIT 20";

        $query = $this->db->query($sql);

        $products = array();
        foreach ($query->rows as $row) {
            // Get product units
            $units = $this->getProductUnits($row['product_id']);

            $products[] = array(
                'id' => $row['product_id'],
                'text' => $row['name'],
                'units' => $units
            );
        }

        return $products;
    }

    // Get product price history
    public function getProductPriceHistory($product_id, $unit_id) {
        $sql = "SELECT unit_price, created_at
            FROM " . DB_PREFIX . "purchase_quotation_item qi
            LEFT JOIN " . DB_PREFIX . "purchase_quotation q ON (qi.quotation_id = q.quotation_id)
            WHERE qi.product_id = '" . (int)$product_id . "'
            AND qi.unit_id = '" . (int)$unit_id . "'
            AND q.status IN ('approved', 'converted')
            ORDER BY q.created_at DESC
            LIMIT 5";

        $query = $this->db->query($sql);

        $history = array();
        foreach ($query->rows as $row) {
            $history[] = array(
                'date' => date($this->language->get('date_format_short'), strtotime($row['created_at'])),
                'price' => $this->currency->format($row['unit_price'], $this->config->get('config_currency'))
            );
        }

        return $history;
    }

    // Get supplier product history
    public function getSupplierProductHistory($product_id, $supplier_id) {
        $sql = "SELECT q.created_at, qi.unit_price, q.delivery_terms, q.payment_terms,
            q.status, q.quotation_number
            FROM " . DB_PREFIX . "purchase_quotation q
            LEFT JOIN " . DB_PREFIX . "purchase_quotation_item qi ON (q.quotation_id = qi.quotation_id)
            WHERE qi.product_id = '" . (int)$product_id . "'
            AND q.supplier_id = '" . (int)$supplier_id . "'
            ORDER BY q.created_at DESC
            LIMIT 5";

        $query = $this->db->query($sql);

        $history = array();
        foreach ($query->rows as $row) {
            $history[] = array(
                'date' => date($this->language->get('date_format_short'), strtotime($row['created_at'])),
                'price' => $this->currency->format($row['unit_price'], $this->config->get('config_currency')),
                'quotation_number' => $row['quotation_number'],
                'status' => $this->getStatusText($row['status']),
                'delivery_terms' => $row['delivery_terms'],
                'payment_terms' => $row['payment_terms']
            );
        }

        return $history;
    }

    public function getStatusText($status) {
        $status_texts = array(
            'draft' => $this->language->get('text_status_draft'),
            'pending' => $this->language->get('text_status_pending'),
            'approved' => $this->language->get('text_status_approved'),
            'rejected' => $this->language->get('text_status_rejected'),
            'converted' => $this->language->get('text_status_converted'),
            'cancelled' => $this->language->get('text_status_cancelled')
        );

        return isset($status_texts[$status]) ? $status_texts[$status] : $status;
    }

    public function getStatusClass($status) {
        $status_classes = array(
            'draft' => 'default',
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'converted' => 'info',
            'cancelled' => 'default'
        );

        return isset($status_classes[$status]) ? $status_classes[$status] : 'default';
    }

    // Get units for a product
    private function getProductUnits($product_id) {
        $sql = "SELECT u.unit_id as id, CONCAT(u.desc_en, ' (', u.desc_ar, ')') as text
            FROM " . DB_PREFIX . "product_unit pu
            LEFT JOIN " . DB_PREFIX . "unit u ON (pu.unit_id = u.unit_id)
            WHERE pu.product_id = '" . (int)$product_id . "'";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    /**
     * Approve quotation
     *
     * @param int $quotation_id Quotation ID
     * @param int $approved_by User ID who approved
     * @return array Result
     */
    public function approveQuotation($quotation_id, $approved_by) {
        $quotation = $this->getQuotation($quotation_id);

        if (!$quotation) {
            return array('error' => 'Quotation not found');
        }

        if ($quotation['status'] != 'received' && $quotation['status'] != 'pending') {
            return array('error' => 'Quotation cannot be approved in current status');
        }

        $this->db->query("UPDATE " . DB_PREFIX . "purchase_quotation SET
            status = 'approved',
            approved_by = '" . (int)$approved_by . "',
            approved_date = NOW(),
            date_modified = NOW()
            WHERE quotation_id = '" . (int)$quotation_id . "'");

        // Add history record
        $this->addHistory($quotation_id, 'approved', 'Quotation approved', $approved_by);

        return array('success' => true);
    }

    /**
     * Reject quotation
     *
     * @param int $quotation_id Quotation ID
     * @param int $rejected_by User ID who rejected
     * @param string $reason Rejection reason
     * @return array Result
     */
    public function rejectQuotation($quotation_id, $rejected_by, $reason = '') {
        $quotation = $this->getQuotation($quotation_id);

        if (!$quotation) {
            return array('error' => 'Quotation not found');
        }

        if ($quotation['status'] != 'received' && $quotation['status'] != 'pending') {
            return array('error' => 'Quotation cannot be rejected in current status');
        }

        $this->db->query("UPDATE " . DB_PREFIX . "purchase_quotation SET
            status = 'rejected',
            rejected_by = '" . (int)$rejected_by . "',
            rejected_date = NOW(),
            rejection_reason = '" . $this->db->escape($reason) . "',
            date_modified = NOW()
            WHERE quotation_id = '" . (int)$quotation_id . "'");

        // Add history record
        $description = 'Quotation rejected';
        if ($reason) {
            $description .= ': ' . $reason;
        }
        $this->addHistory($quotation_id, 'rejected', $description, $rejected_by);

        return array('success' => true);
    }

    /**
     * Delete quotation
     *
     * @param int $quotation_id Quotation ID
     * @return array Result
     */
    public function deleteQuotation($quotation_id) {
        $quotation = $this->getQuotation($quotation_id);

        if (!$quotation) {
            return array('error' => 'Quotation not found');
        }

        if ($quotation['status'] == 'approved' || $quotation['status'] == 'converted') {
            return array('error' => 'Cannot delete approved or converted quotation');
        }

        // Delete quotation items first
        $this->db->query("DELETE FROM " . DB_PREFIX . "purchase_quotation_item
            WHERE quotation_id = '" . (int)$quotation_id . "'");

        // Delete quotation history
        $this->db->query("DELETE FROM " . DB_PREFIX . "purchase_quotation_history
            WHERE quotation_id = '" . (int)$quotation_id . "'");

        // Delete quotation documents
        $this->db->query("DELETE FROM " . DB_PREFIX . "purchase_document
            WHERE reference_type = 'quotation' AND reference_id = '" . (int)$quotation_id . "'");

        // Delete quotation
        $this->db->query("DELETE FROM " . DB_PREFIX . "purchase_quotation
            WHERE quotation_id = '" . (int)$quotation_id . "'");

        return array('success' => true);
    }
}
