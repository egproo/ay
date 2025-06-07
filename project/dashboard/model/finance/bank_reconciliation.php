<?php
/**
 * نموذج التسوية البنكية المتقدمة
 * مستوى عالمي مثل SAP وOracle وOdoo وMicrosoft Dynamics
 */
class ModelFinanceBankReconciliation extends Model {

    /**
     * إضافة تسوية بنكية جديدة
     */
    public function addBankReconciliation($data) {
        // إدراج التسوية الرئيسية
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "bank_reconciliation SET
            bank_account_id = '" . (int)$data['bank_account_id'] . "',
            period_from = '" . $this->db->escape($data['period_from']) . "',
            period_to = '" . $this->db->escape($data['period_to']) . "',
            statement_balance = '" . (float)$data['statement_balance'] . "',
            notes = '" . $this->db->escape($data['notes']) . "',
            status = 'draft',
            created_by = '" . (int)$this->user->getId() . "',
            created_date = NOW()
        ");

        $reconciliation_id = $this->db->getLastId();

        // حساب الرصيد الدفتري
        $book_balance = $this->calculateBookBalance($data['bank_account_id'], $data['period_to']);

        // تحديث الرصيد الدفتري
        $this->db->query("
            UPDATE " . DB_PREFIX . "bank_reconciliation SET
            book_balance = '" . (float)$book_balance . "',
            difference = '" . (float)($data['statement_balance'] - $book_balance) . "'
            WHERE reconciliation_id = '" . (int)$reconciliation_id . "'
        ");

        // تحميل العناصر غير المسواة
        $this->loadUnreconciledItems($reconciliation_id);

        return $reconciliation_id;
    }

    /**
     * تعديل تسوية بنكية
     */
    public function editBankReconciliation($reconciliation_id, $data) {
        // تحديث التسوية الرئيسية
        $this->db->query("
            UPDATE " . DB_PREFIX . "bank_reconciliation SET
            bank_account_id = '" . (int)$data['bank_account_id'] . "',
            period_from = '" . $this->db->escape($data['period_from']) . "',
            period_to = '" . $this->db->escape($data['period_to']) . "',
            statement_balance = '" . (float)$data['statement_balance'] . "',
            notes = '" . $this->db->escape($data['notes']) . "',
            modified_by = '" . (int)$this->user->getId() . "',
            modified_date = NOW()
            WHERE reconciliation_id = '" . (int)$reconciliation_id . "'
        ");

        // إعادة حساب الرصيد الدفتري
        $book_balance = $this->calculateBookBalance($data['bank_account_id'], $data['period_to']);

        // تحديث الرصيد الدفتري والفرق
        $this->db->query("
            UPDATE " . DB_PREFIX . "bank_reconciliation SET
            book_balance = '" . (float)$book_balance . "',
            difference = '" . (float)($data['statement_balance'] - $book_balance) . "'
            WHERE reconciliation_id = '" . (int)$reconciliation_id . "'
        ");

        return true;
    }

    /**
     * حذف تسوية بنكية
     */
    public function deleteBankReconciliation($reconciliation_id) {
        // حذف عناصر التسوية
        $this->db->query("DELETE FROM " . DB_PREFIX . "bank_reconciliation_items WHERE reconciliation_id = '" . (int)$reconciliation_id . "'");

        // حذف التسوية
        $this->db->query("DELETE FROM " . DB_PREFIX . "bank_reconciliation WHERE reconciliation_id = '" . (int)$reconciliation_id . "'");

        return true;
    }

    /**
     * تنفيذ التسوية التلقائية
     */
    public function performAutoReconciliation($reconciliation_id) {
        $reconciliation = $this->getBankReconciliation($reconciliation_id);

        if (!$reconciliation) {
            throw new Exception('التسوية البنكية غير موجودة');
        }

        $matched_items = 0;

        // البحث عن التطابقات المباشرة (نفس المبلغ والتاريخ)
        $query = $this->db->query("
            SELECT bri.*, bs.statement_date, bs.amount as statement_amount, bs.description as statement_description
            FROM " . DB_PREFIX . "bank_reconciliation_items bri
            LEFT JOIN " . DB_PREFIX . "bank_statement_items bs ON (
                bri.bank_account_id = bs.bank_account_id
                AND ABS(bri.amount - bs.amount) < 0.01
                AND DATE(bri.transaction_date) = DATE(bs.statement_date)
                AND bs.is_reconciled = 0
            )
            WHERE bri.reconciliation_id = '" . (int)$reconciliation_id . "'
            AND bri.is_reconciled = 0
            AND bs.statement_item_id IS NOT NULL
        ");

        foreach ($query->rows as $item) {
            // تطبيق التطابق
            $this->markItemAsReconciled($reconciliation_id, $item['item_id'], $item['statement_item_id']);
            $matched_items++;
        }

        // تحديث إحصائيات التسوية
        $this->updateReconciliationStats($reconciliation_id);

        return array(
            'matched_items' => $matched_items,
            'reconciliation_id' => $reconciliation_id
        );
    }

    /**
     * تنفيذ التطابق الذكي
     */
    public function performSmartMatching($reconciliation_id, $tolerance = 0.01) {
        $reconciliation = $this->getBankReconciliation($reconciliation_id);

        if (!$reconciliation) {
            throw new Exception('التسوية البنكية غير موجودة');
        }

        $suggested_matches = array();

        // البحث عن التطابقات الذكية (مبلغ مشابه ضمن فترة زمنية)
        $query = $this->db->query("
            SELECT
                bri.item_id,
                bri.amount as book_amount,
                bri.transaction_date as book_date,
                bri.description as book_description,
                bs.statement_item_id,
                bs.amount as statement_amount,
                bs.statement_date,
                bs.description as statement_description,
                ABS(bri.amount - bs.amount) as amount_diff,
                ABS(DATEDIFF(bri.transaction_date, bs.statement_date)) as date_diff
            FROM " . DB_PREFIX . "bank_reconciliation_items bri
            CROSS JOIN " . DB_PREFIX . "bank_statement_items bs
            WHERE bri.reconciliation_id = '" . (int)$reconciliation_id . "'
            AND bri.is_reconciled = 0
            AND bs.bank_account_id = '" . (int)$reconciliation['bank_account_id'] . "'
            AND bs.is_reconciled = 0
            AND ABS(bri.amount - bs.amount) <= '" . (float)$tolerance . "'
            AND ABS(DATEDIFF(bri.transaction_date, bs.statement_date)) <= 7
            ORDER BY amount_diff ASC, date_diff ASC
        ");

        foreach ($query->rows as $match) {
            // حساب درجة التطابق
            $match_score = $this->calculateMatchScore($match);

            if ($match_score >= 0.8) { // 80% تطابق أو أكثر
                $suggested_matches[] = array(
                    'item_id' => $match['item_id'],
                    'statement_item_id' => $match['statement_item_id'],
                    'match_score' => $match_score,
                    'book_amount' => $match['book_amount'],
                    'statement_amount' => $match['statement_amount'],
                    'amount_diff' => $match['amount_diff'],
                    'date_diff' => $match['date_diff'],
                    'book_description' => $match['book_description'],
                    'statement_description' => $match['statement_description']
                );
            }
        }

        return array(
            'suggested_matches' => count($suggested_matches),
            'matches' => $suggested_matches,
            'reconciliation_id' => $reconciliation_id
        );
    }

    /**
     * حساب درجة التطابق
     */
    private function calculateMatchScore($match) {
        $score = 0;

        // تطابق المبلغ (50% من النقاط)
        if ($match['amount_diff'] == 0) {
            $score += 0.5;
        } elseif ($match['amount_diff'] <= 0.01) {
            $score += 0.4;
        } elseif ($match['amount_diff'] <= 1) {
            $score += 0.3;
        }

        // تطابق التاريخ (30% من النقاط)
        if ($match['date_diff'] == 0) {
            $score += 0.3;
        } elseif ($match['date_diff'] <= 1) {
            $score += 0.25;
        } elseif ($match['date_diff'] <= 3) {
            $score += 0.2;
        } elseif ($match['date_diff'] <= 7) {
            $score += 0.1;
        }

        // تطابق الوصف (20% من النقاط)
        $description_similarity = $this->calculateStringSimilarity(
            $match['book_description'],
            $match['statement_description']
        );
        $score += $description_similarity * 0.2;

        return $score;
    }

    /**
     * حساب تشابه النصوص
     */
    private function calculateStringSimilarity($str1, $str2) {
        $str1 = strtolower(trim($str1));
        $str2 = strtolower(trim($str2));

        if ($str1 === $str2) {
            return 1.0;
        }

        // استخدام Levenshtein distance
        $len1 = strlen($str1);
        $len2 = strlen($str2);

        if ($len1 == 0 || $len2 == 0) {
            return 0.0;
        }

        $distance = levenshtein($str1, $str2);
        $max_len = max($len1, $len2);

        return 1 - ($distance / $max_len);
    }

    /**
     * استيراد كشف البنك
     */
    public function importBankStatement($file, $bank_account_id, $format) {
        $imported_transactions = 0;

        // إنشاء سجل كشف البنك
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "bank_statements SET
            bank_account_id = '" . (int)$bank_account_id . "',
            file_name = '" . $this->db->escape($file['name']) . "',
            file_format = '" . $this->db->escape($format) . "',
            import_date = NOW(),
            imported_by = '" . (int)$this->user->getId() . "'
        ");

        $statement_id = $this->db->getLastId();

        // معالجة الملف حسب الصيغة
        switch ($format) {
            case 'csv':
                $imported_transactions = $this->importCSVStatement($file, $statement_id, $bank_account_id);
                break;
            case 'excel':
                $imported_transactions = $this->importExcelStatement($file, $statement_id, $bank_account_id);
                break;
            case 'ofx':
                $imported_transactions = $this->importOFXStatement($file, $statement_id, $bank_account_id);
                break;
            case 'qif':
                $imported_transactions = $this->importQIFStatement($file, $statement_id, $bank_account_id);
                break;
            default:
                throw new Exception('صيغة الملف غير مدعومة');
        }

        // تحديث عدد المعاملات المستوردة
        $this->db->query("
            UPDATE " . DB_PREFIX . "bank_statements SET
            total_transactions = '" . (int)$imported_transactions . "'
            WHERE statement_id = '" . (int)$statement_id . "'
        ");

        return array(
            'statement_id' => $statement_id,
            'imported_transactions' => $imported_transactions
        );
    }

    /**
     * استيراد كشف CSV
     */
    private function importCSVStatement($file, $statement_id, $bank_account_id) {
        $imported = 0;

        if (($handle = fopen($file['tmp_name'], "r")) !== FALSE) {
            // تخطي الصف الأول (العناوين)
            fgetcsv($handle);

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (count($data) >= 4) { // تاريخ، وصف، مبلغ، رصيد
                    $transaction_date = date('Y-m-d', strtotime($data[0]));
                    $description = $data[1];
                    $amount = (float)$data[2];
                    $balance = isset($data[3]) ? (float)$data[3] : 0;

                    // إدراج المعاملة
                    $this->db->query("
                        INSERT INTO " . DB_PREFIX . "bank_statement_items SET
                        statement_id = '" . (int)$statement_id . "',
                        bank_account_id = '" . (int)$bank_account_id . "',
                        statement_date = '" . $this->db->escape($transaction_date) . "',
                        description = '" . $this->db->escape($description) . "',
                        amount = '" . (float)$amount . "',
                        balance = '" . (float)$balance . "',
                        is_reconciled = 0
                    ");

                    $imported++;
                }
            }
            fclose($handle);
        }

        return $imported;
    }

    /**
     * إنهاء التسوية البنكية
     */
    public function finalizeReconciliation($reconciliation_id) {
        // التحقق من أن جميع العناصر تم تسويتها
        $unreconciled_count = $this->getUnreconciledItemsCount($reconciliation_id);

        if ($unreconciled_count > 0) {
            throw new Exception("لا يمكن إنهاء التسوية - يوجد {$unreconciled_count} عنصر غير مسوى");
        }

        // تحديث حالة التسوية
        $this->db->query("
            UPDATE " . DB_PREFIX . "bank_reconciliation SET
            status = 'finalized',
            reconciliation_date = NOW(),
            finalized_by = '" . (int)$this->user->getId() . "'
            WHERE reconciliation_id = '" . (int)$reconciliation_id . "'
        ");

        return $this->db->countAffected() > 0;
    }

    /**
     * حساب الرصيد الدفتري
     */
    private function calculateBookBalance($bank_account_id, $end_date) {
        $query = $this->db->query("
            SELECT
                COALESCE(SUM(CASE WHEN jed.debit_amount > 0 THEN jed.debit_amount ELSE -jed.credit_amount END), 0) as balance
            FROM " . DB_PREFIX . "journal_entry_details jed
            INNER JOIN " . DB_PREFIX . "journal_entries je ON jed.journal_id = je.journal_id
            WHERE jed.account_id = '" . (int)$bank_account_id . "'
            AND je.journal_date <= '" . $this->db->escape($end_date) . "'
            AND je.is_posted = 1
        ");

        return $query->row['balance'];
    }

    /**
     * تحميل العناصر غير المسواة
     */
    private function loadUnreconciledItems($reconciliation_id) {
        $reconciliation = $this->getBankReconciliation($reconciliation_id);

        // تحميل المعاملات من دفتر اليومية
        $query = $this->db->query("
            SELECT
                je.journal_id,
                je.journal_date as transaction_date,
                je.description,
                CASE
                    WHEN jed.debit_amount > 0 THEN jed.debit_amount
                    ELSE -jed.credit_amount
                END as amount,
                'journal' as source_type,
                je.reference_type,
                je.reference_id
            FROM " . DB_PREFIX . "journal_entry_details jed
            INNER JOIN " . DB_PREFIX . "journal_entries je ON jed.journal_id = je.journal_id
            WHERE jed.account_id = '" . (int)$reconciliation['bank_account_id'] . "'
            AND je.journal_date BETWEEN '" . $this->db->escape($reconciliation['period_from']) . "'
                AND '" . $this->db->escape($reconciliation['period_to']) . "'
            AND je.is_posted = 1
            AND jed.journal_detail_id NOT IN (
                SELECT journal_detail_id
                FROM " . DB_PREFIX . "bank_reconciliation_items
                WHERE journal_detail_id IS NOT NULL
                AND is_reconciled = 1
            )
            ORDER BY je.journal_date
        ");

        foreach ($query->rows as $item) {
            $this->db->query("
                INSERT INTO " . DB_PREFIX . "bank_reconciliation_items SET
                reconciliation_id = '" . (int)$reconciliation_id . "',
                bank_account_id = '" . (int)$reconciliation['bank_account_id'] . "',
                journal_id = '" . (int)$item['journal_id'] . "',
                transaction_date = '" . $this->db->escape($item['transaction_date']) . "',
                description = '" . $this->db->escape($item['description']) . "',
                amount = '" . (float)$item['amount'] . "',
                source_type = '" . $this->db->escape($item['source_type']) . "',
                reference_type = '" . $this->db->escape($item['reference_type']) . "',
                reference_id = '" . (int)$item['reference_id'] . "',
                is_reconciled = 0
            ");
        }
    }

    /**
     * تطبيق التسوية على عنصر
     */
    private function markItemAsReconciled($reconciliation_id, $item_id, $statement_item_id = null) {
        $this->db->query("
            UPDATE " . DB_PREFIX . "bank_reconciliation_items SET
            is_reconciled = 1,
            statement_item_id = " . ($statement_item_id ? "'" . (int)$statement_item_id . "'" : "NULL") . ",
            reconciled_date = NOW(),
            reconciled_by = '" . (int)$this->user->getId() . "'
            WHERE item_id = '" . (int)$item_id . "'
            AND reconciliation_id = '" . (int)$reconciliation_id . "'
        ");

        // تحديث عنصر كشف البنك إذا كان موجوداً
        if ($statement_item_id) {
            $this->db->query("
                UPDATE " . DB_PREFIX . "bank_statement_items SET
                is_reconciled = 1,
                reconciliation_item_id = '" . (int)$item_id . "'
                WHERE statement_item_id = '" . (int)$statement_item_id . "'
            ");
        }
    }

    /**
     * تطبيق التسوية على عناصر متعددة
     */
    public function markItemsAsReconciled($reconciliation_id, $items) {
        $marked_items = 0;

        foreach ($items as $item) {
            $item_id = $item['item_id'];
            $statement_item_id = isset($item['statement_item_id']) ? $item['statement_item_id'] : null;

            $this->markItemAsReconciled($reconciliation_id, $item_id, $statement_item_id);
            $marked_items++;
        }

        // تحديث إحصائيات التسوية
        $this->updateReconciliationStats($reconciliation_id);

        return $marked_items;
    }

    /**
     * تحديث إحصائيات التسوية
     */
    private function updateReconciliationStats($reconciliation_id) {
        $query = $this->db->query("
            SELECT
                COUNT(*) as total_items,
                COUNT(CASE WHEN is_reconciled = 1 THEN 1 END) as reconciled_items,
                COUNT(CASE WHEN is_reconciled = 0 THEN 1 END) as unreconciled_items
            FROM " . DB_PREFIX . "bank_reconciliation_items
            WHERE reconciliation_id = '" . (int)$reconciliation_id . "'
        ");

        $stats = $query->row;

        $this->db->query("
            UPDATE " . DB_PREFIX . "bank_reconciliation SET
            total_items = '" . (int)$stats['total_items'] . "',
            reconciled_items = '" . (int)$stats['reconciled_items'] . "',
            unreconciled_items = '" . (int)$stats['unreconciled_items'] . "'
            WHERE reconciliation_id = '" . (int)$reconciliation_id . "'
        ");
    }

    /**
     * الحصول على تسوية بنكية
     */
    public function getBankReconciliation($reconciliation_id) {
        $query = $this->db->query("
            SELECT br.*, ca.account_name as bank_account_name,
                   u1.firstname as created_by_name,
                   u2.firstname as finalized_by_name
            FROM " . DB_PREFIX . "bank_reconciliation br
            LEFT JOIN " . DB_PREFIX . "chart_accounts ca ON br.bank_account_id = ca.account_id
            LEFT JOIN " . DB_PREFIX . "user u1 ON br.created_by = u1.user_id
            LEFT JOIN " . DB_PREFIX . "user u2 ON br.finalized_by = u2.user_id
            WHERE br.reconciliation_id = '" . (int)$reconciliation_id . "'
        ");

        return $query->num_rows ? $query->row : false;
    }

    /**
     * الحصول على قائمة التسويات البنكية
     */
    public function getBankReconciliations($data = array()) {
        $sql = "
            SELECT br.*, ca.account_name as bank_account_name,
                   CASE
                       WHEN br.status = 'draft' THEN 'مسودة'
                       WHEN br.status = 'finalized' THEN 'مكتمل'
                       ELSE 'غير محدد'
                   END as status_name
            FROM " . DB_PREFIX . "bank_reconciliation br
            LEFT JOIN " . DB_PREFIX . "chart_accounts ca ON br.bank_account_id = ca.account_id
        ";

        $sort_data = array(
            'bank_account_name',
            'period_from',
            'period_to',
            'difference',
            'status',
            'reconciliation_date'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY br.created_date";
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
     * الحصول على إجمالي التسويات البنكية
     */
    public function getTotalBankReconciliations() {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "bank_reconciliation");

        return $query->row['total'];
    }

    /**
     * الحصول على العناصر غير المسواة
     */
    public function getUnreconciledItems($reconciliation_id) {
        $query = $this->db->query("
            SELECT *
            FROM " . DB_PREFIX . "bank_reconciliation_items
            WHERE reconciliation_id = '" . (int)$reconciliation_id . "'
            AND is_reconciled = 0
            ORDER BY transaction_date, amount
        ");

        return $query->rows;
    }

    /**
     * الحصول على عدد العناصر غير المسواة
     */
    public function getUnreconciledItemsCount($reconciliation_id) {
        $query = $this->db->query("
            SELECT COUNT(*) as count
            FROM " . DB_PREFIX . "bank_reconciliation_items
            WHERE reconciliation_id = '" . (int)$reconciliation_id . "'
            AND is_reconciled = 0
        ");

        return $query->row['count'];
    }

    /**
     * الحصول على ملخص التسوية
     */
    public function getReconciliationSummary($reconciliation_id) {
        $reconciliation = $this->getBankReconciliation($reconciliation_id);

        if (!$reconciliation) {
            throw new Exception('التسوية البنكية غير موجودة');
        }

        // إحصائيات العناصر
        $items_query = $this->db->query("
            SELECT
                COUNT(*) as total_items,
                COUNT(CASE WHEN is_reconciled = 1 THEN 1 END) as reconciled_items,
                COUNT(CASE WHEN is_reconciled = 0 THEN 1 END) as unreconciled_items,
                SUM(CASE WHEN is_reconciled = 1 THEN amount ELSE 0 END) as reconciled_amount,
                SUM(CASE WHEN is_reconciled = 0 THEN amount ELSE 0 END) as unreconciled_amount
            FROM " . DB_PREFIX . "bank_reconciliation_items
            WHERE reconciliation_id = '" . (int)$reconciliation_id . "'
        ");

        $items_stats = $items_query->row;

        // العناصر المعلقة (في الطريق)
        $outstanding_deposits_query = $this->db->query("
            SELECT COUNT(*) as count, SUM(amount) as total
            FROM " . DB_PREFIX . "bank_reconciliation_items
            WHERE reconciliation_id = '" . (int)$reconciliation_id . "'
            AND is_reconciled = 0
            AND amount > 0
        ");

        $outstanding_checks_query = $this->db->query("
            SELECT COUNT(*) as count, SUM(ABS(amount)) as total
            FROM " . DB_PREFIX . "bank_reconciliation_items
            WHERE reconciliation_id = '" . (int)$reconciliation_id . "'
            AND is_reconciled = 0
            AND amount < 0
        ");

        $outstanding_deposits = $outstanding_deposits_query->row;
        $outstanding_checks = $outstanding_checks_query->row;

        // حساب الرصيد المعدل
        $adjusted_balance = $reconciliation['statement_balance']
                          + ($outstanding_deposits['total'] ?? 0)
                          - ($outstanding_checks['total'] ?? 0);

        return array(
            'reconciliation' => $reconciliation,
            'total_items' => $items_stats['total_items'],
            'reconciled_items' => $items_stats['reconciled_items'],
            'unreconciled_items' => $items_stats['unreconciled_items'],
            'reconciled_amount' => $items_stats['reconciled_amount'],
            'unreconciled_amount' => $items_stats['unreconciled_amount'],
            'outstanding_deposits_count' => $outstanding_deposits['count'],
            'outstanding_deposits_total' => $outstanding_deposits['total'] ?? 0,
            'outstanding_checks_count' => $outstanding_checks['count'],
            'outstanding_checks_total' => $outstanding_checks['total'] ?? 0,
            'adjusted_balance' => $adjusted_balance,
            'difference' => $reconciliation['difference'],
            'is_balanced' => abs($reconciliation['difference']) < 0.01
        );
    }

    /**
     * الحصول على عناصر التسوية
     */
    public function getReconciliationItems($reconciliation_id, $reconciled_only = false) {
        $sql = "
            SELECT bri.*, bs.description as statement_description, bs.statement_date
            FROM " . DB_PREFIX . "bank_reconciliation_items bri
            LEFT JOIN " . DB_PREFIX . "bank_statement_items bs ON bri.statement_item_id = bs.statement_item_id
            WHERE bri.reconciliation_id = '" . (int)$reconciliation_id . "'
        ";

        if ($reconciled_only) {
            $sql .= " AND bri.is_reconciled = 1";
        }

        $sql .= " ORDER BY bri.transaction_date, bri.amount";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    /**
     * البحث عن التطابقات المحتملة
     */
    public function findPotentialMatches($reconciliation_id, $item_id) {
        $item_query = $this->db->query("
            SELECT *
            FROM " . DB_PREFIX . "bank_reconciliation_items
            WHERE item_id = '" . (int)$item_id . "'
        ");

        if (!$item_query->num_rows) {
            return array();
        }

        $item = $item_query->row;

        // البحث في عناصر كشف البنك
        $matches_query = $this->db->query("
            SELECT
                bs.*,
                ABS(bs.amount - '" . (float)$item['amount'] . "') as amount_diff,
                ABS(DATEDIFF(bs.statement_date, '" . $this->db->escape($item['transaction_date']) . "')) as date_diff
            FROM " . DB_PREFIX . "bank_statement_items bs
            WHERE bs.bank_account_id = '" . (int)$item['bank_account_id'] . "'
            AND bs.is_reconciled = 0
            AND ABS(bs.amount - '" . (float)$item['amount'] . "') <= 1.00
            AND ABS(DATEDIFF(bs.statement_date, '" . $this->db->escape($item['transaction_date']) . "')) <= 14
            ORDER BY amount_diff ASC, date_diff ASC
            LIMIT 10
        ");

        $matches = array();
        foreach ($matches_query->rows as $match) {
            $match['match_score'] = $this->calculateMatchScore(array(
                'amount_diff' => $match['amount_diff'],
                'date_diff' => $match['date_diff'],
                'book_description' => $item['description'],
                'statement_description' => $match['description']
            ));
            $matches[] = $match;
        }

        return $matches;
    }

    /**
     * تصدير تقرير التسوية
     */
    public function exportReconciliationReport($reconciliation_id, $format = 'excel') {
        $reconciliation = $this->getBankReconciliation($reconciliation_id);
        $summary = $this->getReconciliationSummary($reconciliation_id);
        $items = $this->getReconciliationItems($reconciliation_id);

        $report_data = array(
            'reconciliation' => $reconciliation,
            'summary' => $summary,
            'items' => $items,
            'export_date' => date('Y-m-d H:i:s'),
            'exported_by' => $this->user->getUserName()
        );

        return $report_data;
    }

    /**
     * الحصول على إحصائيات التسوية البنكية
     */
    public function getReconciliationStatistics($bank_account_id = null, $period_from = null, $period_to = null) {
        $where_conditions = array();

        if ($bank_account_id) {
            $where_conditions[] = "br.bank_account_id = '" . (int)$bank_account_id . "'";
        }

        if ($period_from) {
            $where_conditions[] = "br.period_from >= '" . $this->db->escape($period_from) . "'";
        }

        if ($period_to) {
            $where_conditions[] = "br.period_to <= '" . $this->db->escape($period_to) . "'";
        }

        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

        $query = $this->db->query("
            SELECT
                COUNT(*) as total_reconciliations,
                COUNT(CASE WHEN status = 'finalized' THEN 1 END) as completed_reconciliations,
                COUNT(CASE WHEN status = 'draft' THEN 1 END) as pending_reconciliations,
                AVG(ABS(difference)) as avg_difference,
                MAX(ABS(difference)) as max_difference,
                SUM(total_items) as total_items_processed,
                SUM(reconciled_items) as total_items_reconciled,
                AVG(CASE WHEN total_items > 0 THEN (reconciled_items / total_items) * 100 ELSE 0 END) as avg_reconciliation_rate
            FROM " . DB_PREFIX . "bank_reconciliation br
            {$where_clause}
        ");

        return $query->row;
    }

    /**
     * تنظيف البيانات القديمة
     */
    public function cleanupOldReconciliations($days_old = 365) {
        // حذف التسويات القديمة المكتملة
        $this->db->query("
            DELETE FROM " . DB_PREFIX . "bank_reconciliation_items
            WHERE reconciliation_id IN (
                SELECT reconciliation_id
                FROM " . DB_PREFIX . "bank_reconciliation
                WHERE status = 'finalized'
                AND reconciliation_date < DATE_SUB(NOW(), INTERVAL " . (int)$days_old . " DAY)
            )
        ");

        $this->db->query("
            DELETE FROM " . DB_PREFIX . "bank_reconciliation
            WHERE status = 'finalized'
            AND reconciliation_date < DATE_SUB(NOW(), INTERVAL " . (int)$days_old . " DAY)
        ");

        return $this->db->countAffected();
    }

    /**
     * التحقق من صحة التسوية
     */
    public function validateReconciliation($reconciliation_id) {
        $errors = array();
        $warnings = array();

        $reconciliation = $this->getBankReconciliation($reconciliation_id);

        if (!$reconciliation) {
            $errors[] = 'التسوية البنكية غير موجودة';
            return array('errors' => $errors, 'warnings' => $warnings);
        }

        // التحقق من وجود فرق كبير
        if (abs($reconciliation['difference']) > 100) {
            $warnings[] = 'يوجد فرق كبير في التسوية: ' . number_format($reconciliation['difference'], 2);
        }

        // التحقق من العناصر المكررة
        $duplicates_query = $this->db->query("
            SELECT transaction_date, amount, COUNT(*) as count
            FROM " . DB_PREFIX . "bank_reconciliation_items
            WHERE reconciliation_id = '" . (int)$reconciliation_id . "'
            GROUP BY transaction_date, amount
            HAVING count > 1
        ");

        if ($duplicates_query->num_rows > 0) {
            $warnings[] = 'يوجد ' . $duplicates_query->num_rows . ' عنصر مكرر في التسوية';
        }

        // التحقق من العناصر القديمة غير المسواة
        $old_items_query = $this->db->query("
            SELECT COUNT(*) as count
            FROM " . DB_PREFIX . "bank_reconciliation_items
            WHERE reconciliation_id = '" . (int)$reconciliation_id . "'
            AND is_reconciled = 0
            AND transaction_date < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");

        if ($old_items_query->row['count'] > 0) {
            $warnings[] = 'يوجد ' . $old_items_query->row['count'] . ' عنصر قديم غير مسوى (أكثر من 30 يوم)';
        }

        return array(
            'errors' => $errors,
            'warnings' => $warnings,
            'is_valid' => empty($errors)
        );
    }
}
