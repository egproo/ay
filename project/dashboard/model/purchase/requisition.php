<?php
class ModelPurchaseRequisition extends Model {

    /**
     * Get requisition statistics
     */
    public function getRequisitionStats($data = array()) {
        $stats = array(
            'total' => 0,
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0
        );

        // Base WHERE clause from filter data
        $where = array();

        if (!empty($data['filter_req_number'])) {
            $where[] = "req_number LIKE '%" . $this->db->escape($data['filter_req_number']) . "%'";
        }

        if (!empty($data['filter_date_start'])) {
            $where[] = "DATE(created_at) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $where[] = "DATE(created_at) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $where_sql = $where ? " WHERE " . implode(" AND ", $where) : "";

        // Get counts for each status
        $sql = "SELECT status, COUNT(*) as count
                FROM " . DB_PREFIX . "purchase_requisition
                " . $where_sql . "
                GROUP BY status";

        $query = $this->db->query($sql);

        foreach ($query->rows as $row) {
            switch ($row['status']) {
                case 'pending':
                    $stats['pending'] = $row['count'];
                    break;
                case 'approved':
                    $stats['approved'] = $row['count'];
                    break;
                case 'rejected':
                    $stats['rejected'] = $row['count'];
                    break;
            }
            $stats['total'] += $row['count'];
        }

        return $stats;
    }

    /**
     * Get list of requisitions with filters
     */
    public function getRequisitions($data = array()) {
        $sql = "SELECT r.*,
                b.name AS branch_name,
                ug.name AS user_group_name,
                CONCAT(u.firstname, ' ', u.lastname) as created_by_name
                FROM `" . DB_PREFIX . "purchase_requisition` r
                LEFT JOIN `" . DB_PREFIX . "branch` b ON (r.branch_id = b.branch_id)
                LEFT JOIN `" . DB_PREFIX . "user_group` ug ON (r.user_group_id = ug.user_group_id)
                LEFT JOIN `" . DB_PREFIX . "user` u ON (r.created_by = u.user_id)
                WHERE 1=1";

        if (!empty($data['filter_req_number'])) {
            $sql .= " AND r.req_number LIKE '%" . $this->db->escape($data['filter_req_number']) . "%'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND r.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(r.created_at) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(r.created_at) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $sql .= " ORDER BY r.created_at DESC";

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        return $this->db->query($sql)->rows;
    }

    /**
     * Get total count of requisitions with filters
     */
    public function getTotalRequisitions($data = array()) {
        $sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "purchase_requisition` WHERE 1=1";

        if (!empty($data['filter_req_number'])) {
            $sql .= " AND req_number LIKE '%" . $this->db->escape($data['filter_req_number']) . "%'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(created_at) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(created_at) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $query = $this->db->query($sql);
        return $query->row['total'];
    }

    /**
     * Get single requisition details
     */
    public function getRequisition($requisition_id) {
        $sql = "SELECT r.*,
                b.name AS branch_name,
                ug.name AS user_group_name,
                CONCAT(u.firstname, ' ', u.lastname) as created_by_name,
                CONCAT(u2.firstname, ' ', u2.lastname) as updated_by_name,
                CONCAT(u3.firstname, ' ', u3.lastname) as approved_by_name,
                CONCAT(u4.firstname, ' ', u4.lastname) as rejected_by_name
                FROM `" . DB_PREFIX . "purchase_requisition` r
                LEFT JOIN `" . DB_PREFIX . "branch` b ON (r.branch_id = b.branch_id)
                LEFT JOIN `" . DB_PREFIX . "user_group` ug ON (r.user_group_id = ug.user_group_id)
                LEFT JOIN `" . DB_PREFIX . "user` u ON (r.created_by = u.user_id)
                LEFT JOIN `" . DB_PREFIX . "user` u2 ON (r.updated_by = u2.user_id)
                LEFT JOIN `" . DB_PREFIX . "user` u3 ON (r.approved_by = u3.user_id)
                LEFT JOIN `" . DB_PREFIX . "user` u4 ON (r.rejected_by = u4.user_id)
                WHERE r.requisition_id = '" . (int)$requisition_id . "'";

        return $this->db->query($sql)->row;
    }

    /**
     * Get requisition items
     */
    public function getRequisitionItems($requisition_id) {
        $sql = "SELECT i.*,
                pd.name AS product_name,
                p.model,
                p.sku,
                u.desc_en AS unit_name_en,
                u.desc_ar AS unit_name_ar
                FROM `" . DB_PREFIX . "purchase_requisition_item` i
                LEFT JOIN `" . DB_PREFIX . "product` p ON (i.product_id = p.product_id)
                LEFT JOIN `" . DB_PREFIX . "product_description` pd ON (p.product_id = pd.product_id AND pd.language_id = 1)
                LEFT JOIN `" . DB_PREFIX . "unit` u ON (i.unit_id = u.unit_id)
                WHERE i.requisition_id = '" . (int)$requisition_id . "'
                ORDER BY i.requisition_item_id";

        return $this->db->query($sql)->rows;
    }

    /**
     * Add new requisition
     */
    public function addRequisition($data) {
        try {
            $this->db->query("START TRANSACTION");

            // Generate requisition number
            $req_number = $this->generateRequisitionNumber();

            // Insert main requisition
            $this->db->query("INSERT INTO `" . DB_PREFIX . "purchase_requisition` SET
                req_number = '" . $this->db->escape($req_number) . "',
                branch_id = '" . (int)$data['branch_id'] . "',
                user_group_id = '" . (int)$data['user_group_id'] . "',
                required_date = '" . $this->db->escape($data['required_date']) . "',
                priority = '" . $this->db->escape($data['priority']) . "',
                status = 'pending',
                notes = '" . $this->db->escape($data['notes']) . "',
                created_by = '" . (int)$data['created_by'] . "',
                created_at = NOW()");

            $requisition_id = $this->db->getLastId();

            // Insert items
            if (!empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    $this->db->query("INSERT INTO `" . DB_PREFIX . "purchase_requisition_item` SET
                        requisition_id = '" . (int)$requisition_id . "',
                        product_id = '" . (int)$item['product_id'] . "',
                        quantity = '" . (float)$item['quantity'] . "',
                        unit_id = '" . (int)$item['unit_id'] . "',
                        description = '" . $this->db->escape($item['description']) . "'");
                }
            }

            // Add history record
            $this->addHistory($requisition_id, array(
                'user_id' => $data['created_by'],
                'action' => 'create',
                'description' => 'Requisition created'
            ));

            $this->db->query("COMMIT");
            return array('requisition_id' => $requisition_id);

        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            return array('error' => $e->getMessage());
        }
    }

    /**
     * Edit existing requisition
     */
    public function editRequisition($data) {
        try {
            $this->db->query("START TRANSACTION");

            // Update main requisition
            $this->db->query("UPDATE `" . DB_PREFIX . "purchase_requisition` SET
                branch_id = '" . (int)$data['branch_id'] . "',
                user_group_id = '" . (int)$data['user_group_id'] . "',
                required_date = '" . $this->db->escape($data['required_date']) . "',
                priority = '" . $this->db->escape($data['priority']) . "',
                notes = '" . $this->db->escape($data['notes']) . "',
                updated_by = '" . (int)$data['updated_by'] . "',
                updated_at = NOW()
                WHERE requisition_id = '" . (int)$data['requisition_id'] . "'");

            // Delete existing items
            $this->db->query("DELETE FROM `" . DB_PREFIX . "purchase_requisition_item`
                WHERE requisition_id = '" . (int)$data['requisition_id'] . "'");

            // Insert new items
            if (!empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    $this->db->query("INSERT INTO `" . DB_PREFIX . "purchase_requisition_item` SET
                        requisition_id = '" . (int)$data['requisition_id'] . "',
                        product_id = '" . (int)$item['product_id'] . "',
                        quantity = '" . (float)$item['quantity'] . "',
                        unit_id = '" . (int)$item['unit_id'] . "',
                        description = '" . $this->db->escape($item['description']) . "'");
                }
            }

            // Add history record
            $this->addHistory($data['requisition_id'], array(
                'user_id' => $data['updated_by'],
                'action' => 'edit',
                'description' => 'Requisition updated'
            ));

            $this->db->query("COMMIT");
            return array('success' => true);

        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            return array('error' => $e->getMessage());
        }
    }

    /**
     * Delete requisition
     */
    public function deleteRequisition($requisition_id) {
        try {
            $this->db->query("START TRANSACTION");

            // Check if requisition can be deleted
            $requisition = $this->getRequisition($requisition_id);
            if (!$requisition || !in_array($requisition['status'], array('draft', 'pending'))) {
                throw new Exception("Requisition cannot be deleted in its current state");
            }

            // Delete items
            $this->db->query("DELETE FROM `" . DB_PREFIX . "purchase_requisition_item`
                WHERE requisition_id = '" . (int)$requisition_id . "'");

            // Delete requisition
            $this->db->query("DELETE FROM `" . DB_PREFIX . "purchase_requisition`
                WHERE requisition_id = '" . (int)$requisition_id . "'");

            $this->db->query("COMMIT");
            return array('success' => true);

        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            return array('error' => $e->getMessage());
        }
    }

    /**
     * Approve requisition
     */
    public function approveRequisition($requisition_id, $user_id) {
        try {
            $this->db->query("START TRANSACTION");

            // Check if requisition can be approved
            $requisition = $this->getRequisition($requisition_id);
            if (!$requisition || $requisition['status'] !== 'pending') {
                throw new Exception("Requisition cannot be approved in its current state");
            }

            // Update status
            $this->db->query("UPDATE `" . DB_PREFIX . "purchase_requisition` SET
                status = 'approved',
                approved_by = '" . (int)$user_id . "',
                approved_at = NOW()
                WHERE requisition_id = '" . (int)$requisition_id . "'");

            // Add history record
            $this->addHistory($requisition_id, array(
                'user_id' => $user_id,
                'action' => 'approve',
                'description' => 'Requisition approved'
            ));

            $this->db->query("COMMIT");
            return array('success' => true);

        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            return array('error' => $e->getMessage());
        }
    }

    /**
     * Reject requisition
     */
    public function rejectRequisition($requisition_id, $user_id, $reason) {
        try {
            $this->db->query("START TRANSACTION");

            // Check if requisition can be rejected
            $requisition = $this->getRequisition($requisition_id);
            if (!$requisition || $requisition['status'] !== 'pending') {
                throw new Exception("Requisition cannot be rejected in its current state");
            }

            // Update status
            $this->db->query("UPDATE `" . DB_PREFIX . "purchase_requisition` SET
                status = 'rejected',
                rejected_by = '" . (int)$user_id . "',
                rejection_reason = '" . $this->db->escape($reason) . "',
                rejected_at = NOW()
                WHERE requisition_id = '" . (int)$requisition_id . "'");

            // Add history record
            $this->addHistory($requisition_id, array(
                'user_id' => $user_id,
                'action' => 'reject',
                'description' => 'Requisition rejected: ' . $reason
            ));

            $this->db->query("COMMIT");
            return array('success' => true);

        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            return array('error' => $e->getMessage());
        }
    }

    /**
     * Get product details including stock info
     */
    public function getProductDetails($product_id, $branch_id) {
        $result = array(
            'units' => array()
        );

        // Get all units for the product with their stock and cost information
        $sql = "SELECT pu.unit_id,
                       CONCAT(u.desc_en, ' - ', u.desc_ar) as unit_name,
                       COALESCE(pi.quantity_available, 0) as quantity_available,
                       COALESCE(pi.average_cost, 0) as average_cost
                FROM " . DB_PREFIX . "product_unit pu
                LEFT JOIN " . DB_PREFIX . "unit u ON (u.unit_id = pu.unit_id)
                LEFT JOIN " . DB_PREFIX . "product_inventory pi ON (pi.product_id = pu.product_id
                    AND pi.unit_id = pu.unit_id AND pi.branch_id = '" . (int)$branch_id . "')
                WHERE pu.product_id = '" . (int)$product_id . "'";

        $query = $this->db->query($sql);
        $result['units'] = $query->rows;

        return $result;
    }

    /**
     * Get pending requisitions for a product
     */
    public function getPendingRequisitionsForProduct($product_id, $exclude_requisition_id = 0) {
        $sql = "SELECT r.requisition_id, r.req_number, r.created_at,
                       b.name as branch_name,
                       ri.quantity,
                       CONCAT(u.desc_en, ' - ', u.desc_ar) as unit_name
                FROM " . DB_PREFIX . "purchase_requisition_item ri
                LEFT JOIN " . DB_PREFIX . "purchase_requisition r ON (r.requisition_id = ri.requisition_id)
                LEFT JOIN " . DB_PREFIX . "branch b ON (b.branch_id = r.branch_id)
                LEFT JOIN " . DB_PREFIX . "unit u ON (u.unit_id = ri.unit_id)
                WHERE ri.product_id = '" . (int)$product_id . "'
                AND r.status IN ('pending', 'approved')";

        if ($exclude_requisition_id) {
            $sql .= " AND r.requisition_id != '" . (int)$exclude_requisition_id . "'";
        }

        $sql .= " ORDER BY r.created_at DESC";

        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * Add history record
     */
    private function addHistory($requisition_id, $data) {
        return $this->db->query("INSERT INTO `" . DB_PREFIX . "purchase_requisition_history` SET
            requisition_id = '" . (int)$requisition_id . "',
            user_id = '" . (int)$data['user_id'] . "',
            action = '" . $this->db->escape($data['action']) . "',
            description = '" . $this->db->escape($data['description']) . "',
            created_at = NOW()");
    }

    /**
     * Generate requisition number
     */
    private function generateRequisitionNumber() {
        $prefix = 'REQ-' . date('Y');

        $sql = "SELECT MAX(CAST(SUBSTRING_INDEX(req_number, '-', -1) AS UNSIGNED)) as max_number
                FROM `" . DB_PREFIX . "purchase_requisition`
                WHERE req_number LIKE '" . $prefix . "-%'";

        $query = $this->db->query($sql);
        $number = $query->row['max_number'];

        if ($number) {
            $number++;
        } else {
            $number = 1;
        }

        return $prefix . '-' . sprintf('%06d', $number);
    }

    /**
     * Get branches
     */
    public function getBranches() {
        $sql = "SELECT * FROM `" . DB_PREFIX . "branch` ORDER BY name";
        return $this->db->query($sql)->rows;
    }

    /**
     * Get user groups
     */
    public function getUserGroups() {
        $sql = "SELECT * FROM `" . DB_PREFIX . "user_group` ORDER BY name";
        return $this->db->query($sql)->rows;
    }

    /**
     * Search requisitions for select2
     */
    public function searchRequisitions($q) {
        $sql = "SELECT r.requisition_id, r.req_number, b.name AS branch_name
                FROM `" . DB_PREFIX . "purchase_requisition` r
                LEFT JOIN `" . DB_PREFIX . "branch` b ON (r.branch_id = b.branch_id)
                WHERE (r.req_number LIKE '%" . $this->db->escape($q) . "%'
                OR b.name LIKE '%" . $this->db->escape($q) . "%')
                AND r.status = 'approved'
                ORDER BY r.created_at DESC
                LIMIT 20";

        return $this->db->query($sql)->rows;
    }
}
