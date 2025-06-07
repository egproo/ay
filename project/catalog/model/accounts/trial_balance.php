<?php
class ModelAccountsTrialBalance extends Model {
    public function getMinAccountCode() {
        $query = $this->db->query("SELECT MIN(account_code) AS min_code FROM " . DB_PREFIX . "accounts");
        return $query->row['min_code'];
    }
    
    public function getMaxAccountCode() {
        $query = $this->db->query("SELECT MAX(account_code) AS max_code FROM " . DB_PREFIX . "accounts");
        return $query->row['max_code'];
    }
public function getAccountRangeData($date_start, $date_end, $account_start, $account_end) {
    $language_id = (int)$this->config->get('config_language_id');
    $currency_code = $this->config->get('config_currency');

    // استعلام لجمع الأرصدة الختامية والافتتاحية على مستوى الحسابات
    $sql = "SELECT a.account_code, ad.name, a.parent_id,
               COALESCE(SUM(CASE WHEN j.thedate < '" . $date_start . "' AND j.is_cancelled = 0 THEN (CASE WHEN je.is_debit = 1 THEN je.amount ELSE -je.amount END) ELSE 0 END), 0) AS opening_balance,
               COALESCE(SUM(CASE WHEN j.thedate BETWEEN '" . $date_start . "' AND '" . $date_end . "' AND j.is_cancelled = 0 THEN (CASE WHEN je.is_debit = 1 THEN je.amount ELSE -je.amount END) ELSE 0 END), 0) AS period_movement,
               COALESCE(SUM(CASE WHEN j.thedate <= '" . $date_end . "' AND j.is_cancelled = 0 THEN (CASE WHEN je.is_debit = 1 THEN je.amount ELSE -je.amount END) ELSE 0 END), 0) AS closing_balance
            FROM `" . DB_PREFIX . "accounts` a
            LEFT JOIN `" . DB_PREFIX . "account_description` ad ON a.account_id = ad.account_id AND ad.language_id = '" . $language_id . "'
            LEFT JOIN `" . DB_PREFIX . "journal_entries` je ON je.account_code = a.account_code
            LEFT JOIN `" . DB_PREFIX . "journals` j ON je.journal_id = j.journal_id
            WHERE a.account_code BETWEEN '" . $account_start . "' AND '" . $account_end . "'
            GROUP BY a.account_code, ad.name, a.parent_id
            ORDER BY a.account_code ASC";

    $query = $this->db->query($sql);
    $accounts = $query->rows;

   // تجميع البيانات من الأسفل إلى الأعلى
    $aggregatedData = [];
    foreach ($accounts as $account) {
        $current_id = $account['account_code'];
        $parent_id = $account['parent_id'];

        if (!isset($aggregatedData[$current_id])) {
            $aggregatedData[$current_id] = $account;
        } else {
            $aggregatedData[$current_id]['opening_balance'] += $account['opening_balance'];
            $aggregatedData[$current_id]['closing_balance'] += $account['closing_balance'];
            $aggregatedData[$current_id]['period_movement'] += $account['period_movement'];
        }

        // Aggregate to parent accounts
        $current_parent_id = $parent_id;
        while ($current_parent_id != 0) {
            if (!isset($aggregatedData[$current_parent_id])) {
                $aggregatedData[$current_parent_id] = ['opening_balance' => 0, 'closing_balance' => 0, 'period_movement' => 0, 'name' => 'Parent Account', 'account_code' => $current_parent_id, 'parent_id' => $accounts[$current_parent_id]['parent_id'] ?? 0];
            }
            $aggregatedData[$current_parent_id]['opening_balance'] += $account['opening_balance'];
            $aggregatedData[$current_parent_id]['closing_balance'] += $account['closing_balance'];
            $aggregatedData[$current_parent_id]['period_movement'] += $account['period_movement'];
            $current_parent_id = $aggregatedData[$current_parent_id]['parent_id'];
        }
    }


    // تنسيق البيانات للعرض
    $formattedAccounts = [];
    $sums = ['opening_balance_debit' => 0, 'opening_balance_credit' => 0,
             'total_debit' => 0, 'total_credit' => 0,
             'closing_balance_debit' => 0, 'closing_balance_credit' => 0];

    foreach ($aggregatedData as $account) {
        $formattedAccount = $this->formatAccountData($account, $currency_code);
        $formattedAccounts[] = $formattedAccount;

        // تحديث الإجماليات
        $this->updateSums($account, $sums);
    }

    // تنسيق الإجماليات للعرض
    foreach ($sums as $key => $value) {
        $sums[$key . '_formatted'] = $this->currency->format($value, $currency_code);
    }

    return [
        'accounts' => $formattedAccounts,
        'sums' => $sums
    ];
}

private function formatAccountData($account, $currency_code) {
    $ob_debit = $ob_credit = $cb_debit = $cb_credit = 0;
    if ($account['opening_balance'] >= 0) {
        $ob_debit = $account['opening_balance'];
    } else {
        $ob_credit = abs($account['opening_balance']);
    }
    if ($account['closing_balance'] >= 0) {
        $cb_debit = $account['closing_balance'];
    } else {
        $cb_credit = abs($account['closing_balance']);
    }

    return [
        'account_code' => $account['account_code'],
        'name' => $account['name'],
        'opening_balance_debit_formatted' => $this->currency->format($ob_debit, $currency_code),
        'opening_balance_credit_formatted' => $this->currency->format($ob_credit, $currency_code),
        'total_debit_formatted' => $this->currency->format(max(0, $account['period_movement']), $currency_code),
        'total_credit_formatted' => $this->currency->format(max(0, -$account['period_movement']), $currency_code),
        'closing_balance_debit_formatted' => $this->currency->format($cb_debit, $currency_code),
        'closing_balance_credit_formatted' => $this->currency->format($cb_credit, $currency_code)
    ];
}

private function updateSums($account, &$sums) {
    $sums['opening_balance_debit'] += $account['opening_balance'] >= 0 ? $account['opening_balance'] : 0;
    $sums['opening_balance_credit'] += $account['opening_balance'] < 0 ? abs($account['opening_balance']) : 0;
    $sums['total_debit'] += max(0, $account['period_movement']);
    $sums['total_credit'] += max(0, -$account['period_movement']);
    $sums['closing_balance_debit'] += $account['closing_balance'] >= 0 ? $account['closing_balance'] : 0;
    $sums['closing_balance_credit'] += $account['closing_balance'] < 0 ? abs($account['closing_balance']) : 0;
}




}
