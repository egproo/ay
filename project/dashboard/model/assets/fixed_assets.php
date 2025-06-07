<?php
/**
 * نموذج إدارة الأصول الثابتة المحسن
 * يدعم إدارة الأصول والاستهلاك والتقييم والتكامل المحاسبي
 */
class ModelAssetsFixedAssets extends Model {

    /**
     * إضافة أصل ثابت جديد
     */
    public function addFixedAsset($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "fixed_assets SET
            name = '" . $this->db->escape($data['name']) . "',
            asset_code = '" . $this->db->escape($data['asset_code']) . "',
            description = '" . $this->db->escape($data['description'] ?? '') . "',
            category_id = '" . (int)$data['category_id'] . "',
            purchase_cost = '" . (float)$data['purchase_cost'] . "',
            purchase_date = '" . $this->db->escape($data['purchase_date']) . "',
            useful_life = '" . (int)$data['useful_life'] . "',
            depreciation_method = '" . $this->db->escape($data['depreciation_method']) . "',
            salvage_value = '" . (float)($data['salvage_value'] ?? 0) . "',
            asset_account_id = '" . (int)$data['asset_account_id'] . "',
            depreciation_account_id = '" . (int)$data['depreciation_account_id'] . "',
            accumulated_depreciation_account_id = '" . (int)($data['accumulated_depreciation_account_id'] ?? $data['depreciation_account_id']) . "',
            current_value = '" . (float)$data['purchase_cost'] . "',
            accumulated_depreciation = 0,
            status = '" . $this->db->escape($data['status'] ?? 'active') . "',
            created_by = '" . (int)$this->user->getId() . "',
            date_added = NOW()");

        $asset_id = $this->db->getLastId();

        // إنشاء قيد شراء الأصل
        $this->createPurchaseEntry($asset_id, $data);

        return $asset_id;
    }

    /**
     * تعديل أصل ثابت موجود
     */
    public function editFixedAsset($asset_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "fixed_assets SET
            name = '" . $this->db->escape($data['name']) . "',
            asset_code = '" . $this->db->escape($data['asset_code']) . "',
            description = '" . $this->db->escape($data['description'] ?? '') . "',
            category_id = '" . (int)$data['category_id'] . "',
            purchase_cost = '" . (float)$data['purchase_cost'] . "',
            purchase_date = '" . $this->db->escape($data['purchase_date']) . "',
            useful_life = '" . (int)$data['useful_life'] . "',
            depreciation_method = '" . $this->db->escape($data['depreciation_method']) . "',
            salvage_value = '" . (float)($data['salvage_value'] ?? 0) . "',
            asset_account_id = '" . (int)$data['asset_account_id'] . "',
            depreciation_account_id = '" . (int)$data['depreciation_account_id'] . "',
            accumulated_depreciation_account_id = '" . (int)($data['accumulated_depreciation_account_id'] ?? $data['depreciation_account_id']) . "',
            status = '" . $this->db->escape($data['status'] ?? 'active') . "',
            date_modified = NOW()
            WHERE asset_id = '" . (int)$asset_id . "'");

        return true;
    }

    /**
     * حذف أصل ثابت
     */
    public function deleteFixedAsset($asset_id) {
        // التحقق من وجود حركات استهلاك
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "asset_depreciation WHERE asset_id = '" . (int)$asset_id . "'");
        if ($query->row['total'] > 0) {
            return false; // لا يمكن حذف أصل له حركات استهلاك
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "fixed_assets WHERE asset_id = '" . (int)$asset_id . "'");
        return true;
    }

    /**
     * الحصول على أصل ثابت واحد
     */
    public function getFixedAsset($asset_id) {
        $query = $this->db->query("SELECT fa.*, ac.name as category_name,
                                          a1.account_code as asset_account_code, ad1.name as asset_account_name,
                                          a2.account_code as depreciation_account_code, ad2.name as depreciation_account_name
                                  FROM " . DB_PREFIX . "fixed_assets fa
                                  LEFT JOIN " . DB_PREFIX . "asset_categories ac ON fa.category_id = ac.category_id
                                  LEFT JOIN " . DB_PREFIX . "accounts a1 ON fa.asset_account_id = a1.account_id
                                  LEFT JOIN " . DB_PREFIX . "account_description ad1 ON (a1.account_id = ad1.account_id AND ad1.language_id = '" . (int)$this->config->get('config_language_id') . "')
                                  LEFT JOIN " . DB_PREFIX . "accounts a2 ON fa.depreciation_account_id = a2.account_id
                                  LEFT JOIN " . DB_PREFIX . "account_description ad2 ON (a2.account_id = ad2.account_id AND ad2.language_id = '" . (int)$this->config->get('config_language_id') . "')
                                  WHERE fa.asset_id = '" . (int)$asset_id . "'");
        return $query->row;
    }

    /**
     * الحصول على قائمة الأصول الثابتة
     */
    public function getFixedAssets($data = array()) {
        $sql = "SELECT fa.*, ac.name as category_name
                FROM " . DB_PREFIX . "fixed_assets fa
                LEFT JOIN " . DB_PREFIX . "asset_categories ac ON fa.category_id = ac.category_id
                WHERE 1";

        if (!empty($data['filter_name'])) {
            $sql .= " AND fa.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        if (!empty($data['filter_category'])) {
            $sql .= " AND fa.category_id = '" . (int)$data['filter_category'] . "'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND fa.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        $sql .= " ORDER BY fa.name ASC";

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
     * الحصول على إجمالي عدد الأصول الثابتة
     */
    public function getTotalFixedAssets($data = array()) {
        $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "fixed_assets fa WHERE 1";

        if (!empty($data['filter_name'])) {
            $sql .= " AND fa.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        if (!empty($data['filter_category'])) {
            $sql .= " AND fa.category_id = '" . (int)$data['filter_category'] . "'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND fa.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        $query = $this->db->query($sql);
        return $query->row['total'];
    }

    /**
     * الحصول على فئات الأصول
     */
    public function getAssetCategories() {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "asset_categories ORDER BY name ASC");
        return $query->rows;
    }

    /**
     * حساب الاستهلاك الشهري
     */
    public function calculateMonthlyDepreciation($data) {
        try {
            $depreciation_date = $data['depreciation_date'];
            $month = date('Y-m', strtotime($depreciation_date));

            // التحقق من عدم وجود استهلاك لنفس الشهر
            $check_query = $this->db->query("SELECT COUNT(*) as count FROM " . DB_PREFIX . "asset_depreciation
                                           WHERE DATE_FORMAT(depreciation_date, '%Y-%m') = '" . $this->db->escape($month) . "'");

            if ($check_query->row['count'] > 0) {
                return array('success' => false, 'error' => 'تم حساب الاستهلاك لهذا الشهر مسبقاً');
            }

            // الحصول على الأصول النشطة
            $assets_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "fixed_assets
                                            WHERE status = 'active'
                                            AND purchase_date <= '" . $this->db->escape($depreciation_date) . "'");

            $this->db->query("START TRANSACTION");

            foreach ($assets_query->rows as $asset) {
                $monthly_depreciation = $this->calculateAssetDepreciation($asset, $depreciation_date);

                if ($monthly_depreciation > 0) {
                    // إضافة سجل الاستهلاك
                    $this->db->query("INSERT INTO " . DB_PREFIX . "asset_depreciation SET
                        asset_id = '" . (int)$asset['asset_id'] . "',
                        depreciation_amount = '" . (float)$monthly_depreciation . "',
                        depreciation_date = '" . $this->db->escape($depreciation_date) . "',
                        created_by = '" . (int)$this->user->getId() . "',
                        date_added = NOW()");

                    $depreciation_id = $this->db->getLastId();

                    // تحديث الاستهلاك المتراكم
                    $new_accumulated = $asset['accumulated_depreciation'] + $monthly_depreciation;
                    $new_current_value = $asset['purchase_cost'] - $new_accumulated;

                    $this->db->query("UPDATE " . DB_PREFIX . "fixed_assets SET
                        accumulated_depreciation = '" . (float)$new_accumulated . "',
                        current_value = '" . (float)$new_current_value . "'
                        WHERE asset_id = '" . (int)$asset['asset_id'] . "'");

                    // إنشاء قيد محاسبي للاستهلاك
                    $this->createDepreciationEntry($depreciation_id, $asset, $monthly_depreciation);
                }
            }

            $this->db->query("COMMIT");

            return array('success' => true);

        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            return array('success' => false, 'error' => $e->getMessage());
        }
    }

    /**
     * حساب استهلاك أصل واحد
     */
    private function calculateAssetDepreciation($asset, $depreciation_date) {
        $purchase_cost = (float)$asset['purchase_cost'];
        $salvage_value = (float)$asset['salvage_value'];
        $useful_life = (int)$asset['useful_life'];
        $depreciable_amount = $purchase_cost - $salvage_value;

        // التحقق من أن الأصل لم يستهلك بالكامل
        if ($asset['accumulated_depreciation'] >= $depreciable_amount) {
            return 0;
        }

        switch ($asset['depreciation_method']) {
            case 'straight_line':
                $monthly_depreciation = $depreciable_amount / ($useful_life * 12);
                break;

            case 'declining_balance':
                $rate = 2 / $useful_life; // معدل الاستهلاك المتناقص
                $current_value = $asset['current_value'];
                $monthly_depreciation = ($current_value * $rate) / 12;
                break;

            default:
                $monthly_depreciation = $depreciable_amount / ($useful_life * 12);
        }

        // التأكد من عدم تجاوز المبلغ القابل للاستهلاك
        $remaining_depreciable = $depreciable_amount - $asset['accumulated_depreciation'];
        $monthly_depreciation = min($monthly_depreciation, $remaining_depreciable);

        return max(0, $monthly_depreciation);
    }

    /**
     * إنشاء قيد شراء الأصل
     */
    private function createPurchaseEntry($asset_id, $data) {
        $this->load->model('accounts/journal_entry');

        $journal_data = [
            'journal_date' => $data['purchase_date'],
            'journal_number' => 'ASSET-' . $asset_id,
            'description' => 'شراء أصل ثابت: ' . $data['name'],
            'status' => 'posted',
            'created_by' => $this->user->getId(),
            'reference_type' => 'fixed_asset',
            'reference_id' => $asset_id,
            'lines' => [
                [
                    'account_id' => $data['asset_account_id'],
                    'debit_amount' => $data['purchase_cost'],
                    'credit_amount' => 0,
                    'description' => 'شراء أصل ثابت'
                ],
                [
                    'account_id' => $this->getCashAccountId(),
                    'debit_amount' => 0,
                    'credit_amount' => $data['purchase_cost'],
                    'description' => 'دفع ثمن الأصل'
                ]
            ]
        ];

        return $this->model_accounts_journal_entry->addJournalEntry($journal_data);
    }

    /**
     * إنشاء قيد الاستهلاك
     */
    private function createDepreciationEntry($depreciation_id, $asset, $amount) {
        $this->load->model('accounts/journal_entry');

        $journal_data = [
            'journal_date' => date('Y-m-d'),
            'journal_number' => 'DEP-' . $depreciation_id,
            'description' => 'استهلاك شهري للأصل: ' . $asset['name'],
            'status' => 'posted',
            'created_by' => $this->user->getId(),
            'reference_type' => 'asset_depreciation',
            'reference_id' => $depreciation_id,
            'lines' => [
                [
                    'account_id' => $asset['depreciation_account_id'],
                    'debit_amount' => $amount,
                    'credit_amount' => 0,
                    'description' => 'مصروف الاستهلاك'
                ],
                [
                    'account_id' => $asset['accumulated_depreciation_account_id'],
                    'debit_amount' => 0,
                    'credit_amount' => $amount,
                    'description' => 'الاستهلاك المتراكم'
                ]
            ]
        ];

        return $this->model_accounts_journal_entry->addJournalEntry($journal_data);
    }

    /**
     * التخلص من أصل ثابت
     */
    public function disposeAsset($data) {
        try {
            $asset_id = (int)$data['asset_id'];
            $disposal_date = $data['disposal_date'];
            $disposal_amount = (float)$data['disposal_amount'];

            // الحصول على بيانات الأصل
            $asset = $this->getFixedAsset($asset_id);
            if (!$asset) {
                return array('success' => false, 'error' => 'الأصل غير موجود');
            }

            if ($asset['status'] == 'disposed') {
                return array('success' => false, 'error' => 'تم التخلص من هذا الأصل مسبقاً');
            }

            $this->db->query("START TRANSACTION");

            // تحديث حالة الأصل
            $this->db->query("UPDATE " . DB_PREFIX . "fixed_assets SET
                status = 'disposed',
                disposal_date = '" . $this->db->escape($disposal_date) . "',
                disposal_amount = '" . (float)$disposal_amount . "',
                date_modified = NOW()
                WHERE asset_id = '" . (int)$asset_id . "'");

            // إنشاء قيد التخلص
            $this->createDisposalEntry($asset, $disposal_amount, $disposal_date);

            $this->db->query("COMMIT");

            return array('success' => true);

        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            return array('success' => false, 'error' => $e->getMessage());
        }
    }

    /**
     * إنشاء قيد التخلص من الأصل
     */
    private function createDisposalEntry($asset, $disposal_amount, $disposal_date) {
        $this->load->model('accounts/journal_entry');

        $purchase_cost = (float)$asset['purchase_cost'];
        $accumulated_depreciation = (float)$asset['accumulated_depreciation'];
        $book_value = $purchase_cost - $accumulated_depreciation;
        $gain_loss = $disposal_amount - $book_value;

        $lines = [];

        // النقدية المحصلة
        if ($disposal_amount > 0) {
            $lines[] = [
                'account_id' => $this->getCashAccountId(),
                'debit_amount' => $disposal_amount,
                'credit_amount' => 0,
                'description' => 'متحصلات بيع الأصل'
            ];
        }

        // الاستهلاك المتراكم
        if ($accumulated_depreciation > 0) {
            $lines[] = [
                'account_id' => $asset['accumulated_depreciation_account_id'],
                'debit_amount' => $accumulated_depreciation,
                'credit_amount' => 0,
                'description' => 'إقفال الاستهلاك المتراكم'
            ];
        }

        // ربح أو خسارة البيع
        if ($gain_loss != 0) {
            if ($gain_loss > 0) {
                // ربح البيع
                $lines[] = [
                    'account_id' => $this->getGainOnDisposalAccountId(),
                    'debit_amount' => 0,
                    'credit_amount' => abs($gain_loss),
                    'description' => 'ربح بيع الأصل'
                ];
            } else {
                // خسارة البيع
                $lines[] = [
                    'account_id' => $this->getLossOnDisposalAccountId(),
                    'debit_amount' => abs($gain_loss),
                    'credit_amount' => 0,
                    'description' => 'خسارة بيع الأصل'
                ];
            }
        }

        // إقفال حساب الأصل
        $lines[] = [
            'account_id' => $asset['asset_account_id'],
            'debit_amount' => 0,
            'credit_amount' => $purchase_cost,
            'description' => 'إقفال حساب الأصل'
        ];

        $journal_data = [
            'journal_date' => $disposal_date,
            'journal_number' => 'DISP-' . $asset['asset_id'],
            'description' => 'التخلص من الأصل: ' . $asset['name'],
            'status' => 'posted',
            'created_by' => $this->user->getId(),
            'reference_type' => 'asset_disposal',
            'reference_id' => $asset['asset_id'],
            'lines' => $lines
        ];

        return $this->model_accounts_journal_entry->addJournalEntry($journal_data);
    }

    /**
     * الحصول على تقرير الأصول الثابتة
     */
    public function getAssetsReport($data = array()) {
        $currency_code = $this->config->get('config_currency');

        $sql = "SELECT fa.*, ac.name as category_name
                FROM " . DB_PREFIX . "fixed_assets fa
                LEFT JOIN " . DB_PREFIX . "asset_categories ac ON fa.category_id = ac.category_id
                WHERE 1";

        if (!empty($data['filter_category'])) {
            $sql .= " AND fa.category_id = '" . (int)$data['filter_category'] . "'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND fa.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        $sql .= " ORDER BY ac.name ASC, fa.name ASC";

        $query = $this->db->query($sql);

        $report = array();
        $totals = array(
            'total_cost' => 0,
            'total_depreciation' => 0,
            'total_current_value' => 0
        );

        foreach ($query->rows as $row) {
            $report[] = array(
                'asset_id' => $row['asset_id'],
                'name' => $row['name'],
                'asset_code' => $row['asset_code'],
                'category_name' => $row['category_name'],
                'purchase_cost' => $row['purchase_cost'],
                'purchase_cost_formatted' => $this->currency->format($row['purchase_cost'], $currency_code),
                'accumulated_depreciation' => $row['accumulated_depreciation'],
                'accumulated_depreciation_formatted' => $this->currency->format($row['accumulated_depreciation'], $currency_code),
                'current_value' => $row['current_value'],
                'current_value_formatted' => $this->currency->format($row['current_value'], $currency_code),
                'status' => $row['status'],
                'purchase_date' => $row['purchase_date'],
                'useful_life' => $row['useful_life']
            );

            $totals['total_cost'] += $row['purchase_cost'];
            $totals['total_depreciation'] += $row['accumulated_depreciation'];
            $totals['total_current_value'] += $row['current_value'];
        }

        $totals['total_cost_formatted'] = $this->currency->format($totals['total_cost'], $currency_code);
        $totals['total_depreciation_formatted'] = $this->currency->format($totals['total_depreciation'], $currency_code);
        $totals['total_current_value_formatted'] = $this->currency->format($totals['total_current_value'], $currency_code);

        return array(
            'assets' => $report,
            'totals' => $totals
        );
    }

    /**
     * الحصول على حساب النقدية الافتراضي
     */
    private function getCashAccountId() {
        $query = $this->db->query("SELECT account_id FROM " . DB_PREFIX . "accounts WHERE account_type = 'asset' AND account_code LIKE '1%' AND is_active = 1 LIMIT 1");
        return $query->num_rows ? $query->row['account_id'] : 1;
    }

    /**
     * الحصول على حساب ربح بيع الأصول
     */
    private function getGainOnDisposalAccountId() {
        $query = $this->db->query("SELECT account_id FROM " . DB_PREFIX . "accounts WHERE account_type = 'revenue' AND account_code LIKE '%gain%' AND is_active = 1 LIMIT 1");
        return $query->num_rows ? $query->row['account_id'] : $this->getDefaultIncomeAccountId();
    }

    /**
     * الحصول على حساب خسارة بيع الأصول
     */
    private function getLossOnDisposalAccountId() {
        $query = $this->db->query("SELECT account_id FROM " . DB_PREFIX . "accounts WHERE account_type = 'expense' AND account_code LIKE '%loss%' AND is_active = 1 LIMIT 1");
        return $query->num_rows ? $query->row['account_id'] : $this->getDefaultExpenseAccountId();
    }

    /**
     * الحصول على حساب الإيرادات الافتراضي
     */
    private function getDefaultIncomeAccountId() {
        $query = $this->db->query("SELECT account_id FROM " . DB_PREFIX . "accounts WHERE account_type = 'revenue' AND is_active = 1 LIMIT 1");
        return $query->num_rows ? $query->row['account_id'] : 1;
    }

    /**
     * الحصول على حساب المصروفات الافتراضي
     */
    private function getDefaultExpenseAccountId() {
        $query = $this->db->query("SELECT account_id FROM " . DB_PREFIX . "accounts WHERE account_type = 'expense' AND is_active = 1 LIMIT 1");
        return $query->num_rows ? $query->row['account_id'] : 1;
    }
}
