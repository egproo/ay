<?php
class ModelAccountsTaxReturn extends Model {
    public function getTaxReturnData($date_start, $date_end) {
        $language_id = (int)$this->config->get('config_language_id');
        $currency_code = $this->config->get('config_currency');

        // معدل الضريبة
        $tax_rate = (float)$this->config->get('config_tax_rate') ?: 22.5;

        // بادئة الحسابات غير القابلة للخصم
        $non_deductible_prefix = $this->config->get('config_non_deductible_accounts_prefix') ?: '59';
        // بادئة الحسابات المعفاة
        $exempt_prefix = $this->config->get('config_exempt_income_accounts_prefix') ?: '49';

        // 1. الحصول على صافي الربح المحاسبي من قائمة الدخل:
        // سنفترض وجود طريقة سهلة لجلب صافي الربح (مثلاً من قاعدة البيانات 
        // أو سنحسب سريعاً من الحسابات 4 (الإيرادات) و5 (المصروفات))

        // إجمالي الإيرادات
        $sql_revenue = "SELECT 
                            COALESCE(SUM(CASE WHEN j.thedate BETWEEN '" . $this->db->escape($date_start) . "' AND '" . $this->db->escape($date_end) . "' 
                                    AND j.is_cancelled=0 THEN (CASE WHEN je.is_debit=1 THEN je.amount ELSE -je.amount END) ELSE 0 END),0) AS rev
                        FROM `" . DB_PREFIX . "accounts` a
                        LEFT JOIN `" . DB_PREFIX . "journal_entries` je ON (je.account_code = a.account_code)
                        LEFT JOIN `" . DB_PREFIX . "journals` j ON (je.journal_id = j.journal_id)
                        WHERE a.account_code LIKE '4%'"; // الإيرادات تبدأ بـ4

        $query_rev = $this->db->query($sql_revenue);
        $total_revenue = (float)$query_rev->row['rev'];

        // إجمالي المصروفات
        $sql_expenses = "SELECT 
                            COALESCE(SUM(CASE WHEN j.thedate BETWEEN '" . $this->db->escape($date_start) . "' AND '" . $this->db->escape($date_end) . "' 
                                    AND j.is_cancelled=0 THEN (CASE WHEN je.is_debit=1 THEN je.amount ELSE -je.amount END) ELSE 0 END),0) AS exp
                        FROM `" . DB_PREFIX . "accounts` a
                        LEFT JOIN `" . DB_PREFIX . "journal_entries` je ON (je.account_code = a.account_code)
                        LEFT JOIN `" . DB_PREFIX . "journals` j ON (je.journal_id = j.journal_id)
                        WHERE a.account_code LIKE '5%'"; // المصروفات تبدأ بـ5

        $query_exp = $this->db->query($sql_expenses);
        $total_expenses = (float)$query_exp->row['exp'];

        $accounting_profit = $total_revenue - $total_expenses;

        // 2. إضافة المصروفات غير القابلة للخصم
        $sql_non_deductible = "SELECT 
                                COALESCE(SUM(CASE WHEN j.thedate BETWEEN '" . $this->db->escape($date_start) . "' AND '" . $this->db->escape($date_end) . "' 
                                        AND j.is_cancelled=0 THEN (CASE WHEN je.is_debit=1 THEN je.amount ELSE -je.amount END) ELSE 0 END),0) AS nd
                              FROM `" . DB_PREFIX . "accounts` a
                              LEFT JOIN `" . DB_PREFIX . "journal_entries` je ON (je.account_code = a.account_code)
                              LEFT JOIN `" . DB_PREFIX . "journals` j ON (je.journal_id = j.journal_id)
                              WHERE a.account_code LIKE '" . $this->db->escape($non_deductible_prefix) . "%'";

        $query_nd = $this->db->query($sql_non_deductible);
        $non_deductible = (float)$query_nd->row['nd'];

        // 3. طرح الدخل المعفى
        $sql_exempt = "SELECT 
                            COALESCE(SUM(CASE WHEN j.thedate BETWEEN '" . $this->db->escape($date_start) . "' AND '" . $this->db->escape($date_end) . "' 
                                    AND j.is_cancelled=0 THEN (CASE WHEN je.is_debit=1 THEN je.amount ELSE -je.amount END) ELSE 0 END),0) AS ex
                       FROM `" . DB_PREFIX . "accounts` a
                       LEFT JOIN `" . DB_PREFIX . "journal_entries` je ON (je.account_code = a.account_code)
                       LEFT JOIN `" . DB_PREFIX . "journals` j ON (je.journal_id = j.journal_id)
                       WHERE a.account_code LIKE '" . $this->db->escape($exempt_prefix) . "%'";

        $query_ex = $this->db->query($sql_exempt);
        $exempt_income = (float)$query_ex->row['ex'];

        // الربح الضريبي
        $taxable_profit = $accounting_profit + $non_deductible - $exempt_income;

        // الضريبة المستحقة
        $tax_due = $taxable_profit * ($tax_rate / 100);

        return [
            'accounting_profit' => $this->currency->format($accounting_profit, $currency_code),
            'non_deductible' => $this->currency->format($non_deductible, $currency_code),
            'exempt_income' => $this->currency->format($exempt_income, $currency_code),
            'taxable_profit' => $this->currency->format($taxable_profit, $currency_code),
            'tax_rate' => $tax_rate,
            'tax_due' => $this->currency->format($tax_due, $currency_code)
        ];
    }
}
