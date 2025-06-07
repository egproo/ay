<?php
/**
 * نموذج إدارة الأصول الثابتة المحسن
 * يدعم حساب الإهلاك والتخلص من الأصول
 */
class ModelAccountsFixedAssets extends Model {

    /**
     * إضافة أصل ثابت جديد
     */
    public function addFixedAsset($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "fixed_assets SET
            asset_name = '" . $this->db->escape($data['asset_name']) . "',
            asset_code = '" . $this->db->escape($data['asset_code']) . "',
            category_id = '" . (int)($data['category_id'] ?? 0) . "',
            asset_account_id = '" . (int)$data['asset_account_id'] . "',
            depreciation_account_id = '" . (int)$data['depreciation_account_id'] . "',
            expense_account_id = '" . (int)$data['expense_account_id'] . "',
            purchase_date = '" . $this->db->escape($data['purchase_date']) . "',
            purchase_cost = '" . (float)$data['purchase_cost'] . "',
            salvage_value = '" . (float)($data['salvage_value'] ?? 0) . "',
            useful_life_years = '" . (int)$data['useful_life_years'] . "',
            depreciation_method = '" . $this->db->escape($data['depreciation_method']) . "',
            location = '" . $this->db->escape($data['location'] ?? '') . "',
            serial_number = '" . $this->db->escape($data['serial_number'] ?? '') . "',
            supplier_id = '" . (int)($data['supplier_id'] ?? 0) . "',
            warranty_expiry = '" . $this->db->escape($data['warranty_expiry'] ?? '0000-00-00') . "',
            status = '" . $this->db->escape($data['status'] ?? 'active') . "',
            created_by = '" . (int)$this->user->getId() . "',
            date_added = NOW()");

        $asset_id = $this->db->getLastId();

        // إنشاء قيد شراء الأصل
        $this->createPurchaseEntry($asset_id, $data);

        return $asset_id;
    }

    /**
     * الحصول على أصل ثابت واحد
     */
    public function getFixedAsset($asset_id) {
        $query = $this->db->query("SELECT fa.*,
                                          aa.account_code as asset_account_code,
                                          da.account_code as depreciation_account_code,
                                          ea.account_code as expense_account_code,
                                          aad.name as asset_account_name,
                                          dad.name as depreciation_account_name,
                                          ead.name as expense_account_name
                                  FROM " . DB_PREFIX . "fixed_assets fa
                                  LEFT JOIN " . DB_PREFIX . "accounts aa ON fa.asset_account_id = aa.account_id
                                  LEFT JOIN " . DB_PREFIX . "accounts da ON fa.depreciation_account_id = da.account_id
                                  LEFT JOIN " . DB_PREFIX . "accounts ea ON fa.expense_account_id = ea.account_id
                                  LEFT JOIN " . DB_PREFIX . "account_description aad ON (aa.account_id = aad.account_id AND aad.language_id = '" . (int)$this->config->get('config_language_id') . "')
                                  LEFT JOIN " . DB_PREFIX . "account_description dad ON (da.account_id = dad.account_id AND dad.language_id = '" . (int)$this->config->get('config_language_id') . "')
                                  LEFT JOIN " . DB_PREFIX . "account_description ead ON (ea.account_id = ead.account_id AND ead.language_id = '" . (int)$this->config->get('config_language_id') . "')
                                  WHERE fa.asset_id = '" . (int)$asset_id . "'");

        if ($query->num_rows) {
            $asset = $query->row;

            // حساب الإهلاك المتراكم
            $asset['accumulated_depreciation'] = $this->getAccumulatedDepreciation($asset_id);
            $asset['net_book_value'] = $asset['purchase_cost'] - $asset['accumulated_depreciation'];

            return $asset;
        }

        return array();
    }

    /**
     * الحصول على قائمة الأصول الثابتة
     */
    public function getFixedAssets($data = array()) {
        $sql = "SELECT fa.*,
                       aa.account_code as asset_account_code,
                       aad.name as asset_account_name
                FROM " . DB_PREFIX . "fixed_assets fa
                LEFT JOIN " . DB_PREFIX . "accounts aa ON fa.asset_account_id = aa.account_id
                LEFT JOIN " . DB_PREFIX . "account_description aad ON (aa.account_id = aad.account_id AND aad.language_id = '" . (int)$this->config->get('config_language_id') . "')
                WHERE 1";

        if (!empty($data['filter_name'])) {
            $sql .= " AND fa.asset_name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND fa.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        $sql .= " ORDER BY fa.asset_name ASC";

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
        $assets = $query->rows;

        // إضافة بيانات الإهلاك لكل أصل
        foreach ($assets as &$asset) {
            $asset['accumulated_depreciation'] = $this->getAccumulatedDepreciation($asset['asset_id']);
            $asset['net_book_value'] = $asset['purchase_cost'] - $asset['accumulated_depreciation'];
        }

        return $assets;
    }

    /**
     * حساب الإهلاك الشهري للأصل
     */
    public function calculateMonthlyDepreciation($asset_id, $date = null) {
        $asset = $this->getFixedAsset($asset_id);
        if (!$asset) {
            return 0;
        }

        $purchase_cost = (float)$asset['purchase_cost'];
        $salvage_value = (float)$asset['salvage_value'];
        $useful_life_years = (int)$asset['useful_life_years'];
        $depreciation_method = $asset['depreciation_method'];

        $depreciable_amount = $purchase_cost - $salvage_value;

        switch ($depreciation_method) {
            case 'straight_line':
                return $depreciable_amount / ($useful_life_years * 12);

            case 'declining_balance':
                // طريقة الرصيد المتناقص (مبسطة)
                $rate = 2 / $useful_life_years; // معدل مضاعف
                $current_value = $purchase_cost - $this->getAccumulatedDepreciation($asset_id);
                return $current_value * $rate / 12;

            default:
                return $depreciable_amount / ($useful_life_years * 12);
        }
    }

    /**
     * الحصول على الإهلاك المتراكم للأصل
     */
    public function getAccumulatedDepreciation($asset_id) {
        $query = $this->db->query("SELECT COALESCE(SUM(depreciation_amount), 0) as total
                                  FROM " . DB_PREFIX . "asset_depreciation
                                  WHERE asset_id = '" . (int)$asset_id . "'");
        return (float)$query->row['total'];
    }

    /**
     * إنشاء قيد شراء الأصل
     */
    private function createPurchaseEntry($asset_id, $data) {
        $this->load->model('accounts/journal_entry');

        $journal_data = [
            'journal_date' => $data['purchase_date'],
            'journal_number' => 'ASSET-' . $asset_id,
            'description' => 'شراء أصل ثابت: ' . $data['asset_name'],
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
                    'account_id' => $this->getPayableAccountId(),
                    'debit_amount' => 0,
                    'credit_amount' => $data['purchase_cost'],
                    'description' => 'شراء أصل ثابت'
                ]
            ]
        ];

        return $this->model_accounts_journal_entry->addJournalEntry($journal_data);
    }

    /**
     * الحصول على حساب الدائنين الافتراضي
     */
    private function getPayableAccountId() {
        $query = $this->db->query("SELECT account_id FROM " . DB_PREFIX . "accounts WHERE account_type = 'liability' AND is_active = 1 LIMIT 1");
        return $query->num_rows ? $query->row['account_id'] : 1;
    }

    /**
     * الحصول على بيانات الأصول الثابتة للتقارير (متوافق مع الكود القديم)
     */
    public function getFixedAssetsData($date_end) {
        $currency_code = $this->config->get('config_currency');

        // استخدام الجداول الجديدة
        $assets = $this->getBalanceAt('asset', $date_end);
        $accum_depr = $this->getBalanceAt('depreciation', $date_end);
        $net_value = $assets - $accum_depr;

        return [
            'assets' => $this->currency->format($assets, $currency_code),
            'accum_depr' => $this->currency->format($accum_depr, $currency_code),
            'net_value' => $this->currency->format($net_value, $currency_code)
        ];
    }

    /**
     * الحصول على الرصيد حسب النوع (محسن)
     */
    private function getBalanceAt($type, $date) {
        if ($type == 'asset') {
            $account_type = 'asset';
            $account_code_like = '15%'; // الأصول الثابتة
        } else {
            $account_type = 'asset';
            $account_code_like = '16%'; // مجمع الإهلاك
        }

        $sql = "SELECT COALESCE(SUM(jel.debit_amount - jel.credit_amount), 0) AS balance
                FROM " . DB_PREFIX . "journal_entry_line jel
                JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
                JOIN " . DB_PREFIX . "accounts a ON jel.account_id = a.account_id
                WHERE a.account_type = '" . $this->db->escape($account_type) . "'
                AND a.account_code LIKE '" . $this->db->escape($account_code_like) . "'
                AND je.journal_date <= '" . $this->db->escape($date) . "'
                AND je.status = 'posted'";

        $query = $this->db->query($sql);
        return (float)$query->row['balance'];
    }
}

