<?php
class ModelAccountsJournal extends Model {

//اضافة قيد معكوس    
public function addReverseJournal($journal_id) {
    $journal = $this->getJournal($journal_id);
    if (!$journal || $journal['is_cancelled']) {
        return false; // القيد الأصلي غير موجود أو ملغى بالفعل
    }
    $entries = $this->getJournalEntries($journal_id);

    // إعداد بيانات القيد المعكوس
    $reverse_data = array(
        'thedate' => date('Y-m-d'),
        'refnum' => 'Cancellation of ' . $journal['refnum'],
        'description' => 'Cancellation of journal ID ' . $journal_id,
        'added_by' => $this->user->getUserName(),
        'entries' => array(
            'debit' => $entries['credit'],
            'credit' => $entries['debit']
        ),
        'attachments' => array()
    );

    if ($this->addJournal($reverse_data)) {
        // تحديث القيد الأصلي بأنه تم إلغاؤه
        $this->db->query("UPDATE `" . DB_PREFIX . "journals` SET is_cancelled = 1, cancelled_by = '" . $this->db->escape($this->user->getUserName()) . "' WHERE journal_id = '" . (int)$journal_id . "'");
        return true;
    }
    return false;
}

public function cancelJournal($journal_id) {
    $journal = $this->getJournal($journal_id);
    
    if (!$journal || $journal['is_cancelled']) {
        return false; // القيد الأصلي غير موجود أو ملغى بالفعل
    }
    $this->db->query("UPDATE `" . DB_PREFIX . "journals` SET cancelled_date=NOW(),is_cancelled = 1, cancelled_by = '" . $this->db->escape($this->user->getUserName()) . "' WHERE journal_id = '" . (int)$journal_id . "'");
    return true;
}

public function getTotalJournals($data = array()) {
    $sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "journals` j WHERE 1";

    if (isset($data['include_cancelled']) && $data['include_cancelled']=='1') {
        $sql .= " AND j.is_cancelled >= 0";
    }else{
        $sql .= " AND j.is_cancelled = 0";
    }
    
//$data['include_cancelled']

    if (!empty($data['filter_date_start'])) {
        $sql .= " AND DATE(j.thedate) >= '" . $this->db->escape($data['filter_date_start']) . "'";
    }

    if (!empty($data['filter_date_end'])) {
        $sql .= " AND DATE(j.thedate) <= '" . $this->db->escape($data['filter_date_end']) . "'";
    }

    if (!empty($data['filter_journal_id'])) {
        $sql .= " AND j.journal_id = '" . (int)$data['filter_journal_id'] . "'";
    }

    if (!empty($data['filter_description'])) {
        $sql .= " AND j.description LIKE '%" . $this->db->escape($data['filter_description']) . "%'";
    }

    $query = $this->db->query($sql);
    return $query->row['total'];
}

    // Function to add a new journal entry
public function addJournal($data) {
    error_log("Attempting to insert: " . json_encode($data));

    $result = $this->db->query("INSERT INTO `" . DB_PREFIX . "journals` SET 
        thedate = '" . $this->db->escape($data['thedate']) . "',
        refnum = '" . $this->db->escape($data['refnum']) . "',
        entrytype = 1,
        description = '" . $this->db->escape($data['description']) . "', 
        added_by = '" . $this->db->escape($data['added_by']) . "',
        created_at = NOW()");
    $journal_id = $this->db->getLastId();

    if ($journal_id) {
        // Save entries
        foreach ($data['entries'] as $type => $entries) {
            foreach ($entries as $entry) {
                $is_debit = ($type == 'debit') ? 1 : 0; // تحديد ما إذا كان القيد مدين أو دائن
                $this->db->query("INSERT INTO `" . DB_PREFIX . "journal_entries` SET 
                    journal_id = '" . (int)$journal_id . "', 
                    account_code = '" . $this->db->escape($entry['account_code']) . "', 
                    amount = '" . (float)$entry['amount'] . "', 
                    is_debit = '" . (int)$is_debit . "'");
            }
        }
        
        // Manage attachments
        if(!empty($data['attachments'])){
            $attachments = $data['attachments'];
            // محفوظة في مجلد dashboard
            $attachments_dir = 'image/catalog/attachments/' . $journal_id;
            
            if (!is_dir($attachments_dir) && !mkdir($attachments_dir, 0755, true)) {
                error_log("Failed to create directory: " . $attachments_dir);
                return false; // Exit if unable to create directory
            }
            
            // Process each attachment
            for ($i = 0; $i < count($attachments['name']); $i++) {
                if ($attachments['error'][$i] == 0) { // Check for upload error
                    $timestamp = date('YmdHis');
                    $file_name = basename($attachments['name'][$i]);
                    $new_file_name = $timestamp . "_" . $file_name;
                    $file_path = $attachments_dir . '/' . $new_file_name;
            
                    if (move_uploaded_file($attachments['tmp_name'][$i], $file_path)) {
                        $this->db->query("INSERT INTO `" . DB_PREFIX . "journal_attachments` SET 
                            journal_id = '" . (int)$journal_id . "', 
                            file_name = '" . $this->db->escape($new_file_name) . "', 
                            file_path = '" . $this->db->escape($file_path) . "'");
                    } else {
                        error_log("Failed to move uploaded file: " . $file_name);
                    }
                } else {
                    error_log("Upload error for file: " . $attachments['name'][$i] . " with error code: " . $attachments['error'][$i]);
                }
            }
        }
        
        return $journal_id;
    } else {
        error_log("Failed to insert journal: " . $this->db->displayError());
    }
}

  // Function to get a single attachment by its ID
    public function getAttachmentById($attachment_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "journal_attachments` WHERE attachment_id = '" . (int)$attachment_id . "'");
        return $query->row;  // returns the attachment row if found
    }

    // Function to delete a single attachment by its ID
    public function deleteAttachmentById($attachment_id) {
        $attachment = $this->getAttachmentById($attachment_id);
        if ($attachment) {
            $file_path = $attachment['file_path'];
            if (file_exists($file_path)) {
                unlink($file_path);  // Delete the file from the file system
                $this->db->query("DELETE FROM `" . DB_PREFIX . "journal_attachments` WHERE attachment_id = '" . (int)$attachment_id . "'");
                return true;
            }
        }
        return false;
    }
    

    
    // Function to edit an existing journal entry
    public function editJournal($journal_id, $data) {
        $this->db->query("UPDATE `" . DB_PREFIX . "journals` SET 
            thedate = '" . $this->db->escape($data['thedate']) . "',
            refnum = '" . $this->db->escape($data['refnum']) . "',
            entrytype = 1,
            description = '" . $this->db->escape($data['description']) . "', 
            last_edit_by = '" . $this->db->escape($data['last_edit_by']) . "',
            updated_at = NOW() 
            WHERE journal_id = '" . (int)$journal_id . "'");
    
        // Update entries
        $this->db->query("DELETE FROM `" . DB_PREFIX . "journal_entries` WHERE journal_id = '" . (int)$journal_id . "'");

  foreach ($data['entries'] as $type => $entries) {
            foreach ($entries as $entry) {
                $is_debit = ($type == 'debit') ? 1 : 0; // تحديد ما إذا كان القيد مدين أو دائن
                $this->db->query("INSERT INTO `" . DB_PREFIX . "journal_entries` SET 
                    journal_id = '" . (int)$journal_id . "', 
                    account_code = '" . $this->db->escape($entry['account_code']) . "', 
                    amount = '" . (float)$entry['amount'] . "', 
                    is_debit = '" . (int)$is_debit . "'");
            }
        }

        // Manage attachments
        // اصبح الحذف بالاجاكس
        //$this->db->query("DELETE FROM `" . DB_PREFIX . "journal_attachments` WHERE journal_id = '" . (int)$journal_id . "'");

        $attachments = $data['attachments'];
        // محفوظة في مجلد dashboard
        $attachments_dir = 'image/catalog/attachments/' . $journal_id;
        
        if (!is_dir($attachments_dir) && !mkdir($attachments_dir, 0755, true)) {
            error_log("Failed to create directory: " . $attachments_dir);
            return false; // Exit if unable to create directory
        }
        
        // Process each attachment
        for ($i = 0; $i < count($attachments['name']); $i++) {
            if ($attachments['error'][$i] == 0) { // Check for upload error
                $timestamp = date('YmdHis');
                $file_name = basename($attachments['name'][$i]);
                $new_file_name = $timestamp . "_" . $file_name;
                $file_path = $attachments_dir . '/' . $new_file_name;
        
                if (move_uploaded_file($attachments['tmp_name'][$i], $file_path)) {
                    $this->db->query("INSERT INTO `" . DB_PREFIX . "journal_attachments` SET 
                        journal_id = '" . (int)$journal_id . "', 
                        file_name = '" . $this->db->escape($new_file_name) . "', 
                        file_path = '" . $this->db->escape($file_path) . "'");
                } else {
                    error_log("Failed to move uploaded file: " . $file_name);
                }
            } else {
                error_log("Upload error for file: " . $attachments['name'][$i] . " with error code: " . $attachments['error'][$i]);
            }
        }
        
}
    

    

    // Function to delete a journal entry
    public function deleteJournal($journal_id) {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "journal_attachments` WHERE journal_id = '" . (int)$journal_id . "'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "journal_entries` WHERE journal_id = '" . (int)$journal_id . "'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "journals` WHERE journal_id = '" . (int)$journal_id . "'");
    }

    // Function to get journal entry by ID
    public function getJournal($journal_id) {
        $journal_data = array();
    
        // Fetch main journal data
        $query = $this->db->query("SELECT *,
            (SELECT SUM(amount) FROM `" . DB_PREFIX . "journal_entries` WHERE journal_id = '" . (int)$journal_id . "' AND is_debit = '1') AS total_debit,
            (SELECT SUM(amount) FROM `" . DB_PREFIX . "journal_entries` WHERE journal_id = '" . (int)$journal_id . "' AND is_debit = '0') AS total_credit
            FROM `" . DB_PREFIX . "journals` WHERE journal_id = '" . (int)$journal_id . "'");
    
        if ($query->num_rows) {
            $journal_data = $query->row;
    
            // Fetch journal entries
            $entries_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "journal_entries` WHERE journal_id = '" . (int)$journal_id . "' ORDER BY is_debit DESC");
            $journal_data['entries'] = $entries_query->rows;
    
            // Fetch attachments
            $attachments_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "journal_attachments` WHERE journal_id = '" . (int)$journal_id . "'");
            $journal_data['attachments'] = array();
            foreach ($attachments_query->rows as $attachment) {
                $journal_data['attachments'][] = array(
                    'file_name' => $attachment['file_name'],
                    'file_path' => $attachment['file_path']
                );
            }
    
            // Add total debit and total credit to journal data
            $journal_data['total_debit'] = (float)$journal_data['total_debit'];
            $journal_data['total_credit'] = (float)$journal_data['total_credit'];
    
            // Check if the journal is balanced
            $journal_data['is_balanced'] = ($journal_data['total_debit'] === $journal_data['total_credit']);
        }
    
        return $journal_data;
    }


    
    
    public function getJournalEntries($journal_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "journal_entries` WHERE journal_id = '" . (int)$journal_id . "'");
        $entries = $query->rows;
    
        $formattedEntries = [
            'debit' => [],
            'credit' => []
        ];
    
        foreach ($entries as $entry) {
            if ((int)$entry['is_debit'] === 1) {
                $formattedEntries['debit'][] = $entry;
            } else {
                $formattedEntries['credit'][] = $entry;
            }
        }
    
        // Re-index arrays to ensure they are numeric arrays
        $formattedEntries['debit'] = array_values($formattedEntries['debit']);
        $formattedEntries['credit'] = array_values($formattedEntries['credit']);
    
        return $formattedEntries;
    }
    
    
    public function getAttachments($journal_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "journal_attachments` WHERE journal_id = '" . (int)$journal_id . "'");
        return $query->rows;
    }


    // Function to get list of accounts
    public function getAccounts($data = array()) {
        $sql = "SELECT a.account_id, ad.name, a.account_code, a.status, a.parent_id FROM " . DB_PREFIX . "accounts a LEFT JOIN " . DB_PREFIX . "account_description ad ON (a.account_id = ad.account_id) WHERE ad.language_id = '" . (int)$this->config->get('config_language_id') . "'";
    
        if (!empty($data['filter_name'])) {
            $sql .= " AND ad.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }
    
        $sql .= " ORDER BY " . (isset($data['sort']) ? $data['sort'] : 'ad.name') . " " . (isset($data['order']) && $data['order'] == 'DESC' ? 'DESC' : 'ASC');
        $sql .= " LIMIT " . (isset($data['start']) ? (int)$data['start'] : 0) . ", " . (isset($data['limit']) ? (int)$data['limit'] : 3000);
    
        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function getJournals($data = array()) {
        $sql = "SELECT j.*, 
                       (SELECT SUM(amount) FROM `" . DB_PREFIX . "journal_entries` WHERE journal_id = j.journal_id AND is_debit = 1) as total_debit,
                       (SELECT SUM(amount) FROM `" . DB_PREFIX . "journal_entries` WHERE journal_id = j.journal_id AND is_debit = 0) as total_credit
                FROM `" . DB_PREFIX . "journals` j";
    
        $where = [];
    
        // تحديد ما إذا كان يجب تضمين القيود الملغاة أم لا
        if (isset($data['include_cancelled']) && $data['include_cancelled'] == 1) {
            $where[] = "j.is_cancelled >= 0 ";
        }else{
            $where[] = "j.is_cancelled = 0 ";
        }
        
        if (!empty($data['filter_date_start'])) {
            $where[] = "DATE(j.thedate) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }
        if (!empty($data['filter_date_end'])) {
            $where[] = "DATE(j.thedate) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }
        if (!empty($data['filter_journal_id'])) {
            $where[] = "j.journal_id = '" . (int)$data['filter_journal_id'] . "'";
        }
        if (!empty($data['filter_description'])) {
            $where[] = "j.description LIKE '%" . $this->db->escape($data['filter_description']) . "%'";
        }
  
        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
    
        $sql .= " GROUP BY j.journal_id ORDER BY j.thedate DESC";
       if (isset($data['start']) && isset($data['limit'])) {
        $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
       }
        $query = $this->db->query($sql);
        $results = $query->rows;
    
        foreach ($results as &$result) {
            $result['is_balanced'] = ((float)$result['total_debit'] === (float)$result['total_credit']);
        }
    
        return $results;
    }




}
