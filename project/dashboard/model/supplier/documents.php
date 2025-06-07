<?php
/**
 * AYM ERP - Supplier Documents Model
 *
 * @author AYM ERP Development Team
 * @copyright 2024 AYM ERP
 * @license Commercial License
 * @version 1.0.0
 * @link https://aym-erp.com
 */

class ModelSupplierDocuments extends Model {

    public function addDocument($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "supplier_document SET
            supplier_id = '" . (int)$data['supplier_id'] . "',
            title = '" . $this->db->escape($data['title']) . "',
            description = '" . $this->db->escape($data['description']) . "',
            document_type = '" . $this->db->escape($data['document_type']) . "',
            expiry_date = " . (!empty($data['expiry_date']) ? "'" . $this->db->escape($data['expiry_date']) . "'" : "NULL") . ",
            tags = '" . $this->db->escape($data['tags']) . "',
            status = '" . (int)$data['status'] . "',
            created_by = '" . (int)$this->user->getId() . "',
            date_added = NOW(),
            date_modified = NOW()");

        $document_id = $this->db->getLastId();

        // Log document creation
        $this->addDocumentHistory($document_id, 'created', 'Document created');

        return $document_id;
    }

    public function editDocument($document_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "supplier_document SET
            supplier_id = '" . (int)$data['supplier_id'] . "',
            title = '" . $this->db->escape($data['title']) . "',
            description = '" . $this->db->escape($data['description']) . "',
            document_type = '" . $this->db->escape($data['document_type']) . "',
            expiry_date = " . (!empty($data['expiry_date']) ? "'" . $this->db->escape($data['expiry_date']) . "'" : "NULL") . ",
            tags = '" . $this->db->escape($data['tags']) . "',
            status = '" . (int)$data['status'] . "',
            modified_by = '" . (int)$this->user->getId() . "',
            date_modified = NOW()
            WHERE document_id = '" . (int)$document_id . "'");

        // Log document modification
        $this->addDocumentHistory($document_id, 'modified', 'Document updated');
    }

    public function deleteDocument($document_id) {
        // Get document info for cleanup
        $document_info = $this->getDocument($document_id);

        // Delete physical file
        if ($document_info && $document_info['file_path'] && file_exists(DIR_UPLOAD . $document_info['file_path'])) {
            unlink(DIR_UPLOAD . $document_info['file_path']);
        }

        // Delete document versions
        $this->db->query("DELETE FROM " . DB_PREFIX . "supplier_document_version WHERE document_id = '" . (int)$document_id . "'");

        // Delete document history
        $this->db->query("DELETE FROM " . DB_PREFIX . "supplier_document_history WHERE document_id = '" . (int)$document_id . "'");

        // Delete document
        $this->db->query("DELETE FROM " . DB_PREFIX . "supplier_document WHERE document_id = '" . (int)$document_id . "'");
    }

    public function getDocument($document_id) {
        $query = $this->db->query("SELECT sd.*, s.name as supplier_name,
            u1.username as created_by_name, u2.username as modified_by_name
            FROM " . DB_PREFIX . "supplier_document sd
            LEFT JOIN " . DB_PREFIX . "supplier s ON (sd.supplier_id = s.supplier_id)
            LEFT JOIN " . DB_PREFIX . "user u1 ON (sd.created_by = u1.user_id)
            LEFT JOIN " . DB_PREFIX . "user u2 ON (sd.modified_by = u2.user_id)
            WHERE sd.document_id = '" . (int)$document_id . "'");

        return $query->row;
    }

    public function getDocuments($data = array()) {
        $sql = "SELECT sd.*, s.name as supplier_name,
                u.username as created_by_name
                FROM " . DB_PREFIX . "supplier_document sd
                LEFT JOIN " . DB_PREFIX . "supplier s ON (sd.supplier_id = s.supplier_id)
                LEFT JOIN " . DB_PREFIX . "user u ON (sd.created_by = u.user_id)";

        $sql .= " WHERE 1=1";

        if (!empty($data['filter_title'])) {
            $sql .= " AND sd.title LIKE '" . $this->db->escape($data['filter_title']) . "%'";
        }

        if (!empty($data['filter_supplier_id'])) {
            $sql .= " AND sd.supplier_id = '" . (int)$data['filter_supplier_id'] . "'";
        }

        if (!empty($data['filter_document_type'])) {
            $sql .= " AND sd.document_type = '" . $this->db->escape($data['filter_document_type']) . "'";
        }

        if (isset($data['filter_status']) && $data['filter_status'] !== '') {
            $sql .= " AND sd.status = '" . (int)$data['filter_status'] . "'";
        }

        if (!empty($data['filter_expiry_start'])) {
            $sql .= " AND DATE(sd.expiry_date) >= DATE('" . $this->db->escape($data['filter_expiry_start']) . "')";
        }

        if (!empty($data['filter_expiry_end'])) {
            $sql .= " AND DATE(sd.expiry_date) <= DATE('" . $this->db->escape($data['filter_expiry_end']) . "')";
        }

        $sort_data = array(
            'sd.title',
            's.name',
            'sd.document_type',
            'sd.expiry_date',
            'sd.date_added',
            'sd.date_modified'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY sd.title";
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

    public function getTotalDocuments($data = array()) {
        $sql = "SELECT COUNT(*) AS total
                FROM " . DB_PREFIX . "supplier_document sd
                LEFT JOIN " . DB_PREFIX . "supplier s ON (sd.supplier_id = s.supplier_id)";

        $sql .= " WHERE 1=1";

        if (!empty($data['filter_title'])) {
            $sql .= " AND sd.title LIKE '" . $this->db->escape($data['filter_title']) . "%'";
        }

        if (!empty($data['filter_supplier_id'])) {
            $sql .= " AND sd.supplier_id = '" . (int)$data['filter_supplier_id'] . "'";
        }

        if (!empty($data['filter_document_type'])) {
            $sql .= " AND sd.document_type = '" . $this->db->escape($data['filter_document_type']) . "'";
        }

        if (isset($data['filter_status']) && $data['filter_status'] !== '') {
            $sql .= " AND sd.status = '" . (int)$data['filter_status'] . "'";
        }

        if (!empty($data['filter_expiry_start'])) {
            $sql .= " AND DATE(sd.expiry_date) >= DATE('" . $this->db->escape($data['filter_expiry_start']) . "')";
        }

        if (!empty($data['filter_expiry_end'])) {
            $sql .= " AND DATE(sd.expiry_date) <= DATE('" . $this->db->escape($data['filter_expiry_end']) . "')";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    public function updateDocumentFile($document_id, $file_data) {
        // Create new version if file already exists
        $current_doc = $this->getDocument($document_id);
        if ($current_doc && $current_doc['file_path']) {
            $this->addDocumentVersion($document_id, $current_doc);
        }

        $this->db->query("UPDATE " . DB_PREFIX . "supplier_document SET
            file_path = '" . $this->db->escape($file_data['file_path']) . "',
            original_name = '" . $this->db->escape($file_data['original_name']) . "',
            file_size = '" . (int)$file_data['file_size'] . "',
            mime_type = '" . $this->db->escape($file_data['mime_type']) . "',
            date_modified = NOW()
            WHERE document_id = '" . (int)$document_id . "'");

        // Log file upload
        $this->addDocumentHistory($document_id, 'file_uploaded', 'File uploaded: ' . $file_data['original_name']);
    }

    public function addDocumentVersion($document_id, $document_data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "supplier_document_version SET
            document_id = '" . (int)$document_id . "',
            version_number = '" . (int)($this->getNextVersionNumber($document_id)) . "',
            file_path = '" . $this->db->escape($document_data['file_path']) . "',
            original_name = '" . $this->db->escape($document_data['original_name']) . "',
            file_size = '" . (int)$document_data['file_size'] . "',
            mime_type = '" . $this->db->escape($document_data['mime_type']) . "',
            created_by = '" . (int)$this->user->getId() . "',
            date_added = NOW()");
    }

    public function getDocumentVersions($document_id) {
        $query = $this->db->query("SELECT sdv.*, u.username as created_by_name
            FROM " . DB_PREFIX . "supplier_document_version sdv
            LEFT JOIN " . DB_PREFIX . "user u ON (sdv.created_by = u.user_id)
            WHERE sdv.document_id = '" . (int)$document_id . "'
            ORDER BY sdv.version_number DESC");

        return $query->rows;
    }

    public function getNextVersionNumber($document_id) {
        $query = $this->db->query("SELECT MAX(version_number) as max_version
            FROM " . DB_PREFIX . "supplier_document_version
            WHERE document_id = '" . (int)$document_id . "'");

        return ($query->row['max_version'] ?? 0) + 1;
    }

    public function addDocumentHistory($document_id, $action, $description) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "supplier_document_history SET
            document_id = '" . (int)$document_id . "',
            action = '" . $this->db->escape($action) . "',
            description = '" . $this->db->escape($description) . "',
            user_id = '" . (int)$this->user->getId() . "',
            date_added = NOW()");
    }

    public function getDocumentHistory($document_id) {
        $query = $this->db->query("SELECT sdh.*, u.username
            FROM " . DB_PREFIX . "supplier_document_history sdh
            LEFT JOIN " . DB_PREFIX . "user u ON (sdh.user_id = u.user_id)
            WHERE sdh.document_id = '" . (int)$document_id . "'
            ORDER BY sdh.date_added DESC");

        return $query->rows;
    }

    public function logDownload($document_id, $user_id) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "supplier_document_download SET
            document_id = '" . (int)$document_id . "',
            user_id = '" . (int)$user_id . "',
            ip_address = '" . $this->db->escape($this->request->server['REMOTE_ADDR']) . "',
            user_agent = '" . $this->db->escape($this->request->server['HTTP_USER_AGENT']) . "',
            date_added = NOW()");

        // Update download count
        $this->db->query("UPDATE " . DB_PREFIX . "supplier_document SET
            download_count = download_count + 1
            WHERE document_id = '" . (int)$document_id . "'");

        // Log in history
        $this->addDocumentHistory($document_id, 'downloaded', 'Document downloaded');
    }

    public function archiveDocument($document_id) {
        $this->db->query("UPDATE " . DB_PREFIX . "supplier_document SET
            status = '0',
            archived_by = '" . (int)$this->user->getId() . "',
            archived_date = NOW()
            WHERE document_id = '" . (int)$document_id . "'");

        // Log archive action
        $this->addDocumentHistory($document_id, 'archived', 'Document archived');
    }

    public function getDocumentTypes() {
        return array(
            'contract' => 'Contract',
            'certificate' => 'Certificate',
            'license' => 'License',
            'insurance' => 'Insurance',
            'tax_document' => 'Tax Document',
            'bank_document' => 'Bank Document',
            'quality_certificate' => 'Quality Certificate',
            'compliance_document' => 'Compliance Document',
            'technical_specification' => 'Technical Specification',
            'product_catalog' => 'Product Catalog',
            'price_list' => 'Price List',
            'invoice' => 'Invoice',
            'receipt' => 'Receipt',
            'delivery_note' => 'Delivery Note',
            'other' => 'Other'
        );
    }

    public function getExpiringDocuments($days = 30) {
        $query = $this->db->query("SELECT sd.*, s.name as supplier_name
            FROM " . DB_PREFIX . "supplier_document sd
            LEFT JOIN " . DB_PREFIX . "supplier s ON (sd.supplier_id = s.supplier_id)
            WHERE sd.status = '1'
            AND sd.expiry_date IS NOT NULL
            AND sd.expiry_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL " . (int)$days . " DAY)
            ORDER BY sd.expiry_date ASC");

        return $query->rows;
    }

    public function getExpiredDocuments() {
        $query = $this->db->query("SELECT sd.*, s.name as supplier_name
            FROM " . DB_PREFIX . "supplier_document sd
            LEFT JOIN " . DB_PREFIX . "supplier s ON (sd.supplier_id = s.supplier_id)
            WHERE sd.status = '1'
            AND sd.expiry_date IS NOT NULL
            AND sd.expiry_date < NOW()
            ORDER BY sd.expiry_date DESC");

        return $query->rows;
    }

    public function getDocumentsBySupplier($supplier_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "supplier_document
            WHERE supplier_id = '" . (int)$supplier_id . "'
            AND status = '1'
            ORDER BY document_type, title ASC");

        return $query->rows;
    }

    public function getDocumentsByType($document_type) {
        $query = $this->db->query("SELECT sd.*, s.name as supplier_name
            FROM " . DB_PREFIX . "supplier_document sd
            LEFT JOIN " . DB_PREFIX . "supplier s ON (sd.supplier_id = s.supplier_id)
            WHERE sd.document_type = '" . $this->db->escape($document_type) . "'
            AND sd.status = '1'
            ORDER BY sd.title ASC");

        return $query->rows;
    }

    public function searchDocuments($search_term) {
        $query = $this->db->query("SELECT sd.*, s.name as supplier_name
            FROM " . DB_PREFIX . "supplier_document sd
            LEFT JOIN " . DB_PREFIX . "supplier s ON (sd.supplier_id = s.supplier_id)
            WHERE sd.status = '1'
            AND (sd.title LIKE '%" . $this->db->escape($search_term) . "%'
                OR sd.description LIKE '%" . $this->db->escape($search_term) . "%'
                OR sd.tags LIKE '%" . $this->db->escape($search_term) . "%'
                OR s.name LIKE '%" . $this->db->escape($search_term) . "%')
            ORDER BY sd.title ASC");

        return $query->rows;
    }

    public function getDocumentStatistics() {
        $stats = array();

        // Total documents
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "supplier_document WHERE status = '1'");
        $stats['total_documents'] = $query->row['total'];

        // Documents by type
        $query = $this->db->query("SELECT document_type, COUNT(*) as count
            FROM " . DB_PREFIX . "supplier_document
            WHERE status = '1'
            GROUP BY document_type
            ORDER BY count DESC");
        $stats['by_type'] = $query->rows;

        // Expiring documents
        $query = $this->db->query("SELECT COUNT(*) as count
            FROM " . DB_PREFIX . "supplier_document
            WHERE status = '1'
            AND expiry_date IS NOT NULL
            AND expiry_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY)");
        $stats['expiring_soon'] = $query->row['count'];

        // Expired documents
        $query = $this->db->query("SELECT COUNT(*) as count
            FROM " . DB_PREFIX . "supplier_document
            WHERE status = '1'
            AND expiry_date IS NOT NULL
            AND expiry_date < NOW()");
        $stats['expired'] = $query->row['count'];

        // Recent uploads (last 30 days)
        $query = $this->db->query("SELECT COUNT(*) as count
            FROM " . DB_PREFIX . "supplier_document
            WHERE status = '1'
            AND date_added >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stats['recent_uploads'] = $query->row['count'];

        // Total file size
        $query = $this->db->query("SELECT SUM(file_size) as total_size
            FROM " . DB_PREFIX . "supplier_document
            WHERE status = '1'
            AND file_size > 0");
        $stats['total_file_size'] = $query->row['total_size'] ?? 0;

        return $stats;
    }

    public function getRecentActivity($limit = 10) {
        $query = $this->db->query("SELECT sdh.*, sd.title, s.name as supplier_name, u.username
            FROM " . DB_PREFIX . "supplier_document_history sdh
            LEFT JOIN " . DB_PREFIX . "supplier_document sd ON (sdh.document_id = sd.document_id)
            LEFT JOIN " . DB_PREFIX . "supplier s ON (sd.supplier_id = s.supplier_id)
            LEFT JOIN " . DB_PREFIX . "user u ON (sdh.user_id = u.user_id)
            ORDER BY sdh.date_added DESC
            LIMIT " . (int)$limit);

        return $query->rows;
    }

    public function getMostDownloadedDocuments($limit = 10) {
        $query = $this->db->query("SELECT sd.*, s.name as supplier_name
            FROM " . DB_PREFIX . "supplier_document sd
            LEFT JOIN " . DB_PREFIX . "supplier s ON (sd.supplier_id = s.supplier_id)
            WHERE sd.status = '1'
            AND sd.download_count > 0
            ORDER BY sd.download_count DESC
            LIMIT " . (int)$limit);

        return $query->rows;
    }

    public function cleanupExpiredDocuments($days_after_expiry = 365) {
        // Get expired documents older than specified days
        $query = $this->db->query("SELECT document_id, file_path
            FROM " . DB_PREFIX . "supplier_document
            WHERE expiry_date IS NOT NULL
            AND expiry_date < DATE_SUB(NOW(), INTERVAL " . (int)$days_after_expiry . " DAY)");

        $cleaned_count = 0;

        foreach ($query->rows as $document) {
            // Delete physical file
            if ($document['file_path'] && file_exists(DIR_UPLOAD . $document['file_path'])) {
                unlink(DIR_UPLOAD . $document['file_path']);
            }

            // Archive the document record
            $this->archiveDocument($document['document_id']);
            $cleaned_count++;
        }

        return $cleaned_count;
    }
}
