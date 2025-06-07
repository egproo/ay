<?php
/**
 * نموذج إدارة الأصول الثابتة المتقدمة والمتكاملة
 * مستوى احترافي عالمي مثل SAP وOracle وMicrosoft Dynamics
 */
class ModelAccountsFixedAssetsAdvanced extends Model {

    /**
     * إضافة أصل ثابت جديد
     */
    public function addAsset($data) {
        $sql = "
            INSERT INTO " . DB_PREFIX . "fixed_assets SET
            asset_code = '" . $this->db->escape($data['asset_code']) . "',
            asset_name = '" . $this->db->escape($data['asset_name']) . "',
            asset_description = '" . $this->db->escape($data['asset_description']) . "',
            category_id = '" . (int)$data['category_id'] . "',
            purchase_date = '" . $this->db->escape($data['purchase_date']) . "',
            purchase_cost = '" . (float)$data['purchase_cost'] . "',
            salvage_value = '" . (float)$data['salvage_value'] . "',
            useful_life_years = '" . (int)$data['useful_life_years'] . "',
            useful_life_months = '" . (int)$data['useful_life_months'] . "',
            depreciation_method = '" . $this->db->escape($data['depreciation_method']) . "',
            location = '" . $this->db->escape($data['location']) . "',
            department = '" . $this->db->escape($data['department']) . "',
            responsible_person = '" . $this->db->escape($data['responsible_person']) . "',
            supplier_id = '" . (int)$data['supplier_id'] . "',
            warranty_start_date = '" . $this->db->escape($data['warranty_start_date']) . "',
            warranty_end_date = '" . $this->db->escape($data['warranty_end_date']) . "',
            insurance_policy = '" . $this->db->escape($data['insurance_policy']) . "',
            insurance_value = '" . (float)$data['insurance_value'] . "',
            asset_account_id = '" . (int)$data['asset_account_id'] . "',
            depreciation_account_id = '" . (int)$data['depreciation_account_id'] . "',
            accumulated_depreciation_account_id = '" . (int)$data['accumulated_depreciation_account_id'] . "',
            status = '" . $this->db->escape($data['status']) . "',
            notes = '" . $this->db->escape($data['notes']) . "',
            created_by = '" . (int)$this->user->getId() . "',
            created_date = NOW(),
            modified_date = NOW()
        ";

        $this->db->query($sql);
        $asset_id = $this->db->getLastId();

        // حساب القيمة الحالية
        $current_value = $this->calculateCurrentValue($asset_id);
        $this->updateCurrentValue($asset_id, $current_value);

        // إنشاء جدول الاستهلاك
        $this->generateDepreciationSchedule($asset_id);

        return $asset_id;
    }

    /**
     * تعديل أصل ثابت
     */
    public function editAsset($asset_id, $data) {
        $sql = "
            UPDATE " . DB_PREFIX . "fixed_assets SET
            asset_code = '" . $this->db->escape($data['asset_code']) . "',
            asset_name = '" . $this->db->escape($data['asset_name']) . "',
            asset_description = '" . $this->db->escape($data['asset_description']) . "',
            category_id = '" . (int)$data['category_id'] . "',
            purchase_date = '" . $this->db->escape($data['purchase_date']) . "',
            purchase_cost = '" . (float)$data['purchase_cost'] . "',
            salvage_value = '" . (float)$data['salvage_value'] . "',
            useful_life_years = '" . (int)$data['useful_life_years'] . "',
            useful_life_months = '" . (int)$data['useful_life_months'] . "',
            depreciation_method = '" . $this->db->escape($data['depreciation_method']) . "',
            location = '" . $this->db->escape($data['location']) . "',
            department = '" . $this->db->escape($data['department']) . "',
            responsible_person = '" . $this->db->escape($data['responsible_person']) . "',
            supplier_id = '" . (int)$data['supplier_id'] . "',
            warranty_start_date = '" . $this->db->escape($data['warranty_start_date']) . "',
            warranty_end_date = '" . $this->db->escape($data['warranty_end_date']) . "',
            insurance_policy = '" . $this->db->escape($data['insurance_policy']) . "',
            insurance_value = '" . (float)$data['insurance_value'] . "',
            asset_account_id = '" . (int)$data['asset_account_id'] . "',
            depreciation_account_id = '" . (int)$data['depreciation_account_id'] . "',
            accumulated_depreciation_account_id = '" . (int)$data['accumulated_depreciation_account_id'] . "',
            status = '" . $this->db->escape($data['status']) . "',
            notes = '" . $this->db->escape($data['notes']) . "',
            modified_by = '" . (int)$this->user->getId() . "',
            modified_date = NOW()
            WHERE asset_id = '" . (int)$asset_id . "'
        ";

        $this->db->query($sql);

        // إعادة حساب القيمة الحالية
        $current_value = $this->calculateCurrentValue($asset_id);
        $this->updateCurrentValue($asset_id, $current_value);

        // إعادة إنشاء جدول الاستهلاك
        $this->regenerateDepreciationSchedule($asset_id);
    }

    /**
     * حذف أصل ثابت
     */
    public function deleteAsset($asset_id) {
        // حذف جدول الاستهلاك
        $this->db->query("DELETE FROM " . DB_PREFIX . "fixed_assets_depreciation WHERE asset_id = '" . (int)$asset_id . "'");

        // حذف سجلات الصيانة
        $this->db->query("DELETE FROM " . DB_PREFIX . "fixed_assets_maintenance WHERE asset_id = '" . (int)$asset_id . "'");

        // حذف سجلات التقييم
        $this->db->query("DELETE FROM " . DB_PREFIX . "fixed_assets_valuation WHERE asset_id = '" . (int)$asset_id . "'");

        // حذف الأصل
        $this->db->query("DELETE FROM " . DB_PREFIX . "fixed_assets WHERE asset_id = '" . (int)$asset_id . "'");
    }

    /**
     * الحصول على أصل ثابت
     */
    public function getAsset($asset_id) {
        $query = $this->db->query("
            SELECT fa.*,
                   fac.category_name,
                   s.name as supplier_name,
                   CONCAT(u1.firstname, ' ', u1.lastname) as created_by_name,
                   CONCAT(u2.firstname, ' ', u2.lastname) as modified_by_name,
                   aa.account_code as asset_account_code,
                   da.account_code as depreciation_account_code,
                   ada.account_code as accumulated_depreciation_account_code
            FROM " . DB_PREFIX . "fixed_assets fa
            LEFT JOIN " . DB_PREFIX . "fixed_assets_category fac ON fa.category_id = fac.category_id
            LEFT JOIN " . DB_PREFIX . "supplier s ON fa.supplier_id = s.supplier_id
            LEFT JOIN " . DB_PREFIX . "user u1 ON fa.created_by = u1.user_id
            LEFT JOIN " . DB_PREFIX . "user u2 ON fa.modified_by = u2.user_id
            LEFT JOIN " . DB_PREFIX . "chart_of_accounts aa ON fa.asset_account_id = aa.account_id
            LEFT JOIN " . DB_PREFIX . "chart_of_accounts da ON fa.depreciation_account_id = da.account_id
            LEFT JOIN " . DB_PREFIX . "chart_of_accounts ada ON fa.accumulated_depreciation_account_id = ada.account_id
            WHERE fa.asset_id = '" . (int)$asset_id . "'
        ");

        return $query->row;
    }

    /**
     * الحصول على قائمة الأصول الثابتة
     */
    public function getAssets($data = array()) {
        $sql = "
            SELECT fa.*,
                   fac.category_name,
                   s.name as supplier_name,
                   CONCAT(u.firstname, ' ', u.lastname) as created_by_name
            FROM " . DB_PREFIX . "fixed_assets fa
            LEFT JOIN " . DB_PREFIX . "fixed_assets_category fac ON fa.category_id = fac.category_id
            LEFT JOIN " . DB_PREFIX . "supplier s ON fa.supplier_id = s.supplier_id
            LEFT JOIN " . DB_PREFIX . "user u ON fa.created_by = u.user_id
            WHERE 1
        ";

        if (!empty($data['filter_name'])) {
            $sql .= " AND fa.asset_name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        if (!empty($data['filter_category'])) {
            $sql .= " AND fa.category_id = '" . (int)$data['filter_category'] . "'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND fa.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        if (!empty($data['filter_location'])) {
            $sql .= " AND fa.location LIKE '%" . $this->db->escape($data['filter_location']) . "%'";
        }

        $sort_data = array(
            'asset_name',
            'asset_code',
            'category_name',
            'purchase_date',
            'purchase_cost',
            'current_value',
            'status'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY asset_name";
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
     * الحصول على إجمالي عدد الأصول
     */
    public function getTotalAssets($data = array()) {
        $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "fixed_assets fa WHERE 1";

        if (!empty($data['filter_name'])) {
            $sql .= " AND fa.asset_name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        if (!empty($data['filter_category'])) {
            $sql .= " AND fa.category_id = '" . (int)$data['filter_category'] . "'";
        }

        if (!empty($data['filter_status'])) {
            $sql .= " AND fa.status = '" . $this->db->escape($data['filter_status']) . "'";
        }

        if (!empty($data['filter_location'])) {
            $sql .= " AND fa.location LIKE '%" . $this->db->escape($data['filter_location']) . "%'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    /**
     * حساب الاستهلاك
     */
    public function calculateDepreciation($data) {
        $period_start = $data['period_start'];
        $period_end = $data['period_end'];
        $method = $data['method'] ?? 'all'; // all, specific_assets
        $asset_ids = $data['asset_ids'] ?? array();

        $sql = "
            SELECT fa.*, fac.category_name
            FROM " . DB_PREFIX . "fixed_assets fa
            LEFT JOIN " . DB_PREFIX . "fixed_assets_category fac ON fa.category_id = fac.category_id
            WHERE fa.status = 'active'
            AND fa.purchase_date <= '" . $this->db->escape($period_end) . "'
        ";

        if ($method == 'specific_assets' && !empty($asset_ids)) {
            $sql .= " AND fa.asset_id IN (" . implode(',', array_map('intval', $asset_ids)) . ")";
        }

        $query = $this->db->query($sql);

        $depreciation_entries = array();
        $total_depreciation = 0;

        foreach ($query->rows as $asset) {
            $depreciation_amount = $this->calculateAssetDepreciation($asset, $period_start, $period_end);

            if ($depreciation_amount > 0) {
                $depreciation_entries[] = array(
                    'asset_id' => $asset['asset_id'],
                    'asset_code' => $asset['asset_code'],
                    'asset_name' => $asset['asset_name'],
                    'category_name' => $asset['category_name'],
                    'depreciation_amount' => $depreciation_amount,
                    'depreciation_account_id' => $asset['depreciation_account_id'],
                    'accumulated_depreciation_account_id' => $asset['accumulated_depreciation_account_id']
                );

                $total_depreciation += $depreciation_amount;
            }
        }

        return array(
            'period_start' => $period_start,
            'period_end' => $period_end,
            'entries' => $depreciation_entries,
            'total_depreciation' => $total_depreciation,
            'entry_count' => count($depreciation_entries)
        );
    }

    /**
     * ترحيل قيد الاستهلاك
     */
    public function postDepreciation($depreciation_result) {
        $this->load->model('accounts/journal_entry');

        // إنشاء قيد محاسبي جماعي
        $journal_data = array(
            'entry_date' => $depreciation_result['period_end'],
            'reference' => 'DEP-' . date('Ymd', strtotime($depreciation_result['period_end'])),
            'description' => 'استهلاك الأصول الثابتة للفترة من ' . $depreciation_result['period_start'] . ' إلى ' . $depreciation_result['period_end'],
            'total_debit' => $depreciation_result['total_depreciation'],
            'total_credit' => $depreciation_result['total_depreciation'],
            'status' => 'posted',
            'lines' => array()
        );

        // تجميع الحسابات
        $account_totals = array();

        foreach ($depreciation_result['entries'] as $entry) {
            // حساب مصروف الاستهلاك (مدين)
            $debit_account = $entry['depreciation_account_id'];
            if (!isset($account_totals[$debit_account])) {
                $account_totals[$debit_account] = array('debit' => 0, 'credit' => 0);
            }
            $account_totals[$debit_account]['debit'] += $entry['depreciation_amount'];

            // حساب مجمع الاستهلاك (دائن)
            $credit_account = $entry['accumulated_depreciation_account_id'];
            if (!isset($account_totals[$credit_account])) {
                $account_totals[$credit_account] = array('debit' => 0, 'credit' => 0);
            }
            $account_totals[$credit_account]['credit'] += $entry['depreciation_amount'];
        }

        // إنشاء بنود القيد
        foreach ($account_totals as $account_id => $amounts) {
            if ($amounts['debit'] > 0) {
                $journal_data['lines'][] = array(
                    'account_id' => $account_id,
                    'debit' => $amounts['debit'],
                    'credit' => 0,
                    'description' => 'مصروف استهلاك الأصول الثابتة'
                );
            }

            if ($amounts['credit'] > 0) {
                $journal_data['lines'][] = array(
                    'account_id' => $account_id,
                    'debit' => 0,
                    'credit' => $amounts['credit'],
                    'description' => 'مجمع استهلاك الأصول الثابتة'
                );
            }
        }

        $journal_entry_id = $this->model_accounts_journal_entry->addJournalEntry($journal_data);

        // تسجيل تفاصيل الاستهلاك
        foreach ($depreciation_result['entries'] as $entry) {
            $this->recordDepreciationEntry($entry, $journal_entry_id, $depreciation_result['period_end']);
        }

        return $journal_entry_id;
    }

    /**
     * التخلص من أصل ثابت
     */
    public function disposeAsset($data) {
        $asset_id = $data['asset_id'];
        $disposal_date = $data['disposal_date'];
        $disposal_method = $data['disposal_method']; // sale, scrap, transfer
        $disposal_amount = $data['disposal_amount'] ?? 0;
        $disposal_costs = $data['disposal_costs'] ?? 0;

        $asset = $this->getAsset($asset_id);

        // حساب الاستهلاك حتى تاريخ التخلص
        $accumulated_depreciation = $this->calculateAccumulatedDepreciation($asset_id, $disposal_date);

        // حساب القيمة الدفترية
        $book_value = $asset['purchase_cost'] - $accumulated_depreciation;

        // حساب الربح أو الخسارة
        $net_proceeds = $disposal_amount - $disposal_costs;
        $gain_loss = $net_proceeds - $book_value;

        // تسجيل عملية التخلص
        $disposal_id = $this->recordDisposal($asset_id, $data, $book_value, $gain_loss);

        // إنشاء القيد المحاسبي
        $journal_entry_id = $this->createDisposalJournalEntry($asset, $disposal_amount, $disposal_costs, $accumulated_depreciation, $gain_loss, $disposal_date);

        // تحديث حالة الأصل
        $this->updateAssetStatus($asset_id, 'disposed');

        return array(
            'disposal_id' => $disposal_id,
            'journal_entry_id' => $journal_entry_id,
            'book_value' => $book_value,
            'gain_loss' => $gain_loss,
            'accumulated_depreciation' => $accumulated_depreciation
        );
    }

    /**
     * تحليل الأصل
     */
    public function analyzeAsset($asset_id) {
        $asset = $this->getAsset($asset_id);

        $analysis = array();

        // التحليل المالي
        $analysis['financial'] = $this->analyzeAssetFinancial($asset);

        // تحليل الاستهلاك
        $analysis['depreciation'] = $this->analyzeAssetDepreciation($asset);

        // تحليل الصيانة
        $analysis['maintenance'] = $this->analyzeAssetMaintenance($asset_id);

        // تحليل الأداء
        $analysis['performance'] = $this->analyzeAssetPerformance($asset);

        // التوصيات
        $analysis['recommendations'] = $this->generateAssetRecommendations($asset, $analysis);

        return $analysis;
    }

    /**
     * إنشاء جدول الاستهلاك
     */
    public function generateDepreciationSchedule($asset_id) {
        $asset = $this->getAsset($asset_id);

        if (!$asset) {
            return false;
        }

        // حذف الجدول القديم
        $this->db->query("DELETE FROM " . DB_PREFIX . "fixed_assets_depreciation_schedule WHERE asset_id = '" . (int)$asset_id . "'");

        $depreciable_amount = $asset['purchase_cost'] - $asset['salvage_value'];
        $total_months = ($asset['useful_life_years'] * 12) + $asset['useful_life_months'];

        if ($total_months <= 0) {
            return false;
        }

        $schedule = array();
        $accumulated_depreciation = 0;

        for ($month = 1; $month <= $total_months; $month++) {
            $period_date = date('Y-m-d', strtotime($asset['purchase_date'] . ' +' . $month . ' months'));

            switch ($asset['depreciation_method']) {
                case 'straight_line':
                    $monthly_depreciation = $depreciable_amount / $total_months;
                    break;

                case 'declining_balance':
                    $rate = 2 / $asset['useful_life_years']; // Double declining balance
                    $remaining_value = $asset['purchase_cost'] - $accumulated_depreciation;
                    $monthly_depreciation = ($remaining_value * $rate) / 12;

                    // لا يمكن أن يتجاوز القيمة المتبقية
                    if ($accumulated_depreciation + $monthly_depreciation > $depreciable_amount) {
                        $monthly_depreciation = $depreciable_amount - $accumulated_depreciation;
                    }
                    break;

                case 'sum_of_years':
                    $sum_of_years = ($asset['useful_life_years'] * ($asset['useful_life_years'] + 1)) / 2;
                    $remaining_years = $asset['useful_life_years'] - floor(($month - 1) / 12);
                    $yearly_depreciation = ($remaining_years / $sum_of_years) * $depreciable_amount;
                    $monthly_depreciation = $yearly_depreciation / 12;
                    break;

                default:
                    $monthly_depreciation = $depreciable_amount / $total_months;
            }

            $accumulated_depreciation += $monthly_depreciation;
            $book_value = $asset['purchase_cost'] - $accumulated_depreciation;

            $schedule[] = array(
                'asset_id' => $asset_id,
                'period_date' => $period_date,
                'period_number' => $month,
                'depreciation_amount' => $monthly_depreciation,
                'accumulated_depreciation' => $accumulated_depreciation,
                'book_value' => $book_value
            );

            // إدراج في قاعدة البيانات
            $this->db->query("
                INSERT INTO " . DB_PREFIX . "fixed_assets_depreciation_schedule SET
                asset_id = '" . (int)$asset_id . "',
                period_date = '" . $this->db->escape($period_date) . "',
                period_number = '" . (int)$month . "',
                depreciation_amount = '" . (float)$monthly_depreciation . "',
                accumulated_depreciation = '" . (float)$accumulated_depreciation . "',
                book_value = '" . (float)$book_value . "',
                status = 'scheduled'
            ");
        }

        return $schedule;
    }

    /**
     * حساب القيمة الحالية للأصل
     */
    public function calculateCurrentValue($asset_id) {
        $asset = $this->getAsset($asset_id);

        if (!$asset) {
            return 0;
        }

        $accumulated_depreciation = $this->calculateAccumulatedDepreciation($asset_id, date('Y-m-d'));

        return $asset['purchase_cost'] - $accumulated_depreciation;
    }

    /**
     * حساب الاستهلاك المتراكم
     */
    public function calculateAccumulatedDepreciation($asset_id, $as_of_date) {
        $query = $this->db->query("
            SELECT COALESCE(SUM(depreciation_amount), 0) as accumulated_depreciation
            FROM " . DB_PREFIX . "fixed_assets_depreciation_schedule
            WHERE asset_id = '" . (int)$asset_id . "'
            AND period_date <= '" . $this->db->escape($as_of_date) . "'
            AND status = 'posted'
        ");

        return (float)$query->row['accumulated_depreciation'];
    }

    /**
     * تحديث القيمة الحالية
     */
    public function updateCurrentValue($asset_id, $current_value) {
        $this->db->query("
            UPDATE " . DB_PREFIX . "fixed_assets
            SET current_value = '" . (float)$current_value . "',
                modified_date = NOW()
            WHERE asset_id = '" . (int)$asset_id . "'
        ");
    }

    /**
     * حساب استهلاك أصل واحد
     */
    private function calculateAssetDepreciation($asset, $period_start, $period_end) {
        // الحصول على الاستهلاك المجدول للفترة
        $query = $this->db->query("
            SELECT SUM(depreciation_amount) as period_depreciation
            FROM " . DB_PREFIX . "fixed_assets_depreciation_schedule
            WHERE asset_id = '" . (int)$asset['asset_id'] . "'
            AND period_date BETWEEN '" . $this->db->escape($period_start) . "' AND '" . $this->db->escape($period_end) . "'
            AND status = 'scheduled'
        ");

        return (float)$query->row['period_depreciation'];
    }

    /**
     * تسجيل قيد الاستهلاك
     */
    private function recordDepreciationEntry($entry, $journal_entry_id, $period_date) {
        $this->db->query("
            INSERT INTO " . DB_PREFIX . "fixed_assets_depreciation SET
            asset_id = '" . (int)$entry['asset_id'] . "',
            journal_entry_id = '" . (int)$journal_entry_id . "',
            depreciation_date = '" . $this->db->escape($period_date) . "',
            depreciation_amount = '" . (float)$entry['depreciation_amount'] . "',
            created_date = NOW()
        ");

        // تحديث حالة الجدول المجدول
        $this->db->query("
            UPDATE " . DB_PREFIX . "fixed_assets_depreciation_schedule
            SET status = 'posted'
            WHERE asset_id = '" . (int)$entry['asset_id'] . "'
            AND period_date = '" . $this->db->escape($period_date) . "'
        ");
    }
