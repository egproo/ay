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

        // استخدام الجداول الجديدة المحسنة
        $sql = "SELECT a.account_code, a.parent_id, a.account_type, a.account_nature,
                       a.opening_balance, a.current_balance, ad.name,
                   COALESCE(SUM(CASE WHEN je.journal_date < '" . $this->db->escape($date_start) . "' AND je.status = 'posted'
                                     THEN (jel.debit_amount - jel.credit_amount) ELSE 0 END), 0) AS opening_balance_calculated,
                   COALESCE(SUM(CASE WHEN je.journal_date BETWEEN '" . $this->db->escape($date_start) . "' AND '" . $this->db->escape($date_end) . "' AND je.status = 'posted'
                                     THEN (jel.debit_amount - jel.credit_amount) ELSE 0 END), 0) AS period_movement
                FROM `" . DB_PREFIX . "accounts` a
                LEFT JOIN `" . DB_PREFIX . "account_description` ad ON (a.account_id = ad.account_id AND ad.language_id = '" . (int)$language_id . "')
                LEFT JOIN `" . DB_PREFIX . "journal_entry_line` jel ON (jel.account_id = a.account_id)
                LEFT JOIN `" . DB_PREFIX . "journal_entry` je ON (jel.journal_id = je.journal_id)
                WHERE a.account_code BETWEEN '" . (int)$account_start . "' AND '" . (int)$account_end . "'
                AND a.is_active = 1
                GROUP BY a.account_id, a.account_code, a.parent_id, a.account_type, a.account_nature, ad.name
                ORDER BY a.account_code ASC";

        $query = $this->db->query($sql);
        $accounts = $query->rows;

        $accountsHierarchy = [];
        $rootAccounts = [];

        // فهرسة بالحساب code
        foreach ($accounts as $acc) {
            $acc['children'] = [];
            $accountsHierarchy[$acc['account_code']] = $acc;
        }

        // بناء الشجرة
        foreach ($accounts as $acc) {
            $code = $acc['account_code'];
            $pcode = $acc['parent_id'];
            if ($pcode == 0) {
                // حساب جذري
                $rootAccounts[] = &$accountsHierarchy[$code];
            } else {
                // حساب فرعي
                if (isset($accountsHierarchy[$pcode])) {
                    $accountsHierarchy[$pcode]['children'][] = &$accountsHierarchy[$code];
                }
            }
        }

        // تجميع الأرصدة
        $this->aggregateBalances($rootAccounts);

        // حساب الرصيد الختامي بناءً على account_type
        $this->finalizeClosingBalance($rootAccounts);

        // تنسيق البيانات
        $formattedAccounts = [];
        $sums = [
            'opening_balance_debit' => 0, 'opening_balance_credit' => 0,
            'total_debit' => 0, 'total_credit' => 0,
            'closing_balance_debit' => 0, 'closing_balance_credit' => 0
        ];

        foreach ($accountsHierarchy as $acc) {
            $formatted = $this->formatAccountData($acc, $currency_code);
            $formattedAccounts[] = $formatted;
            if ($acc['parent_id'] == 0) {
                $this->updateSums($acc, $sums);
            }
        }

        foreach ($sums as $key => $value) {
            $sums[$key . '_formatted'] = $this->currency->format((float)$value, $currency_code);
        }

        return [
            'accounts' => $formattedAccounts,
            'sums' => $sums
        ];
    }

    private function aggregateBalances(&$accounts) {
        foreach ($accounts as &$acc) {
            if (!empty($acc['children'])) {
                $this->aggregateBalances($acc['children']);
                foreach ($acc['children'] as $child) {
                    $acc['opening_balance'] += $child['opening_balance'];
                    $acc['period_movement'] += $child['period_movement'];
                }
            }
        }
    }

    private function finalizeClosingBalance(&$accounts) {
        foreach ($accounts as &$acc) {
            // استخدام طبيعة الحساب الصحيحة من الجدول الجديد
            if ($acc['account_nature'] == 'debit') {
                // الحسابات المدينة: الأصول والمصروفات
                $acc['closing_balance'] = $acc['opening_balance_calculated'] + $acc['period_movement'];
            } else {
                // الحسابات الدائنة: الخصوم وحقوق الملكية والإيرادات
                $acc['closing_balance'] = $acc['opening_balance_calculated'] + $acc['period_movement'];
            }

            if (!empty($acc['children'])) {
                $this->finalizeClosingBalance($acc['children']);
            }
        }
    }

    private function formatAccountData($acc, $currency_code) {
        $ob_debit = $ob_credit = $cb_debit = $cb_credit = $total_debit = $total_credit = 0.0;

        $opening_balance = (float)$acc['opening_balance_calculated'];
        $period_movement = (float)$acc['period_movement'];
        $closing_balance = (float)$acc['closing_balance'];

        // تحديد الرصيد الافتتاحي حسب طبيعة الحساب
        if ($acc['account_nature'] == 'debit') {
            // الحسابات المدينة
            if ($opening_balance >= 0) {
                $ob_debit = $opening_balance;
            } else {
                $ob_credit = abs($opening_balance);
            }

            // الحركة خلال الفترة
            if ($period_movement >= 0) {
                $total_debit = $period_movement;
            } else {
                $total_credit = abs($period_movement);
            }

            // الرصيد الختامي
            if ($closing_balance >= 0) {
                $cb_debit = $closing_balance;
            } else {
                $cb_credit = abs($closing_balance);
            }
        } else {
            // الحسابات الدائنة
            if ($opening_balance >= 0) {
                $ob_credit = $opening_balance;
            } else {
                $ob_debit = abs($opening_balance);
            }

            // الحركة خلال الفترة
            if ($period_movement >= 0) {
                $total_credit = $period_movement;
            } else {
                $total_debit = abs($period_movement);
            }

            // الرصيد الختامي
            if ($closing_balance >= 0) {
                $cb_credit = $closing_balance;
            } else {
                $cb_debit = abs($closing_balance);
            }
        }

        return [
            'account_code' => $acc['account_code'],
            'name' => $acc['name'],
            'account_type' => $acc['account_type'],
            'account_nature' => $acc['account_nature'],
            'opening_balance_debit_formatted' => $this->currency->format($ob_debit, $currency_code),
            'opening_balance_credit_formatted' => $this->currency->format($ob_credit, $currency_code),
            'total_debit_formatted' => $this->currency->format($total_debit, $currency_code),
            'total_credit_formatted' => $this->currency->format($total_credit, $currency_code),
            'closing_balance_debit_formatted' => $this->currency->format($cb_debit, $currency_code),
            'closing_balance_credit_formatted' => $this->currency->format($cb_credit, $currency_code),
            'opening_balance_debit' => $ob_debit,
            'opening_balance_credit' => $ob_credit,
            'total_debit' => $total_debit,
            'total_credit' => $total_credit,
            'closing_balance_debit' => $cb_debit,
            'closing_balance_credit' => $cb_credit,
            'closing_balance' => $closing_balance
        ];
    }

    private function updateSums($acc, &$sums) {
        $ob = (float)$acc['opening_balance'];
        $pm = (float)$acc['period_movement'];
        $cb = (float)$acc['closing_balance'];

        $sums['opening_balance_debit'] += $ob >= 0 ? $ob : 0;
        $sums['opening_balance_credit'] += $ob < 0 ? abs($ob) : 0;
        $sums['total_debit'] += max(0, $pm);
        $sums['total_credit'] += max(0, -$pm);
        $sums['closing_balance_debit'] += $cb >= 0 ? $cb : 0;
        $sums['closing_balance_credit'] += $cb < 0 ? abs($cb) : 0;
    }
}
