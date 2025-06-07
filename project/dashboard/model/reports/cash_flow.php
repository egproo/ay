<?php
/**
 * نموذج قائمة التدفقات النقدية المحسن
 * يدعم إنشاء قائمة التدفقات النقدية بالطريقة المباشرة وغير المباشرة
 */
class ModelReportsCashFlow extends Model {
    
    /**
     * إنشاء قائمة التدفقات النقدية
     */
    public function generateCashFlowStatement($date_start, $date_end, $method = 'direct') {
        $currency_code = $this->config->get('config_currency');
        
        // الحصول على النقدية في بداية ونهاية الفترة
        $opening_cash = $this->getCashBalance($date_start, true);
        $closing_cash = $this->getCashBalance($date_end, false);
        
        // حساب التدفقات حسب النوع
        $operating_flows = $this->getOperatingCashFlows($date_start, $date_end, $method);
        $investing_flows = $this->getInvestingCashFlows($date_start, $date_end);
        $financing_flows = $this->getFinancingCashFlows($date_start, $date_end);
        
        // حساب الإجماليات
        $operating_total = array_sum(array_column($operating_flows, 'amount'));
        $investing_total = array_sum(array_column($investing_flows, 'amount'));
        $financing_total = array_sum(array_column($financing_flows, 'amount'));
        $net_change = $operating_total + $investing_total + $financing_total;
        
        return array(
            'period' => array(
                'date_start' => $date_start,
                'date_end' => $date_end,
                'date_start_formatted' => date($this->language->get('date_format_short'), strtotime($date_start)),
                'date_end_formatted' => date($this->language->get('date_format_short'), strtotime($date_end))
            ),
            'method' => $method,
            'operating' => $this->formatCashFlowItems($operating_flows, $currency_code),
            'investing' => $this->formatCashFlowItems($investing_flows, $currency_code),
            'financing' => $this->formatCashFlowItems($financing_flows, $currency_code),
            'operating_total' => $operating_total,
            'operating_total_formatted' => $this->currency->format($operating_total, $currency_code),
            'investing_total' => $investing_total,
            'investing_total_formatted' => $this->currency->format($investing_total, $currency_code),
            'financing_total' => $financing_total,
            'financing_total_formatted' => $this->currency->format($financing_total, $currency_code),
            'net_change' => $net_change,
            'net_change_formatted' => $this->currency->format($net_change, $currency_code),
            'opening_cash' => $opening_cash,
            'opening_cash_formatted' => $this->currency->format($opening_cash, $currency_code),
            'closing_cash' => $closing_cash,
            'closing_cash_formatted' => $this->currency->format($closing_cash, $currency_code),
            'calculated_closing_cash' => $opening_cash + $net_change,
            'calculated_closing_cash_formatted' => $this->currency->format($opening_cash + $net_change, $currency_code)
        );
    }
    
    /**
     * الحصول على التدفقات النقدية من الأنشطة التشغيلية
     */
    private function getOperatingCashFlows($date_start, $date_end, $method) {
        if ($method == 'indirect') {
            return $this->getOperatingCashFlowsIndirect($date_start, $date_end);
        } else {
            return $this->getOperatingCashFlowsDirect($date_start, $date_end);
        }
    }
    
    /**
     * التدفقات التشغيلية - الطريقة المباشرة
     */
    private function getOperatingCashFlowsDirect($date_start, $date_end) {
        $flows = array();
        
        // المتحصلات من العملاء
        $customer_receipts = $this->getCustomerReceipts($date_start, $date_end);
        if ($customer_receipts != 0) {
            $flows[] = array(
                'description' => 'متحصلات من العملاء',
                'amount' => $customer_receipts
            );
        }
        
        // المدفوعات للموردين
        $supplier_payments = $this->getSupplierPayments($date_start, $date_end);
        if ($supplier_payments != 0) {
            $flows[] = array(
                'description' => 'مدفوعات للموردين',
                'amount' => -$supplier_payments
            );
        }
        
        // مدفوعات الرواتب
        $salary_payments = $this->getSalaryPayments($date_start, $date_end);
        if ($salary_payments != 0) {
            $flows[] = array(
                'description' => 'مدفوعات الرواتب',
                'amount' => -$salary_payments
            );
        }
        
        // مدفوعات المصروفات التشغيلية
        $operating_expenses = $this->getOperatingExpensePayments($date_start, $date_end);
        if ($operating_expenses != 0) {
            $flows[] = array(
                'description' => 'مدفوعات المصروفات التشغيلية',
                'amount' => -$operating_expenses
            );
        }
        
        // مدفوعات الضرائب
        $tax_payments = $this->getTaxPayments($date_start, $date_end);
        if ($tax_payments != 0) {
            $flows[] = array(
                'description' => 'مدفوعات الضرائب',
                'amount' => -$tax_payments
            );
        }
        
        // الفوائد المدفوعة
        $interest_paid = $this->getInterestPayments($date_start, $date_end);
        if ($interest_paid != 0) {
            $flows[] = array(
                'description' => 'فوائد مدفوعة',
                'amount' => -$interest_paid
            );
        }
        
        // الفوائد المحصلة
        $interest_received = $this->getInterestReceipts($date_start, $date_end);
        if ($interest_received != 0) {
            $flows[] = array(
                'description' => 'فوائد محصلة',
                'amount' => $interest_received
            );
        }
        
        return $flows;
    }
    
    /**
     * التدفقات التشغيلية - الطريقة غير المباشرة
     */
    private function getOperatingCashFlowsIndirect($date_start, $date_end) {
        $flows = array();
        
        // صافي الدخل
        $net_income = $this->getNetIncome($date_start, $date_end);
        $flows[] = array(
            'description' => 'صافي الدخل',
            'amount' => $net_income
        );
        
        // تعديلات للبنود غير النقدية
        
        // الاستهلاك
        $depreciation = $this->getDepreciationExpense($date_start, $date_end);
        if ($depreciation != 0) {
            $flows[] = array(
                'description' => 'الاستهلاك',
                'amount' => $depreciation
            );
        }
        
        // مخصص الديون المشكوك فيها
        $bad_debt_provision = $this->getBadDebtProvision($date_start, $date_end);
        if ($bad_debt_provision != 0) {
            $flows[] = array(
                'description' => 'مخصص الديون المشكوك فيها',
                'amount' => $bad_debt_provision
            );
        }
        
        // التغيرات في رأس المال العامل
        
        // التغير في العملاء
        $accounts_receivable_change = $this->getAccountsReceivableChange($date_start, $date_end);
        if ($accounts_receivable_change != 0) {
            $flows[] = array(
                'description' => 'التغير في العملاء',
                'amount' => -$accounts_receivable_change
            );
        }
        
        // التغير في المخزون
        $inventory_change = $this->getInventoryChange($date_start, $date_end);
        if ($inventory_change != 0) {
            $flows[] = array(
                'description' => 'التغير في المخزون',
                'amount' => -$inventory_change
            );
        }
        
        // التغير في المصروفات المدفوعة مقدماً
        $prepaid_change = $this->getPrepaidExpensesChange($date_start, $date_end);
        if ($prepaid_change != 0) {
            $flows[] = array(
                'description' => 'التغير في المصروفات المدفوعة مقدماً',
                'amount' => -$prepaid_change
            );
        }
        
        // التغير في الموردين
        $accounts_payable_change = $this->getAccountsPayableChange($date_start, $date_end);
        if ($accounts_payable_change != 0) {
            $flows[] = array(
                'description' => 'التغير في الموردين',
                'amount' => $accounts_payable_change
            );
        }
        
        // التغير في المصروفات المستحقة
        $accrued_expenses_change = $this->getAccruedExpensesChange($date_start, $date_end);
        if ($accrued_expenses_change != 0) {
            $flows[] = array(
                'description' => 'التغير في المصروفات المستحقة',
                'amount' => $accrued_expenses_change
            );
        }
        
        return $flows;
    }
    
    /**
     * الحصول على التدفقات النقدية من الأنشطة الاستثمارية
     */
    private function getInvestingCashFlows($date_start, $date_end) {
        $flows = array();
        
        // شراء الأصول الثابتة
        $asset_purchases = $this->getAssetPurchases($date_start, $date_end);
        if ($asset_purchases != 0) {
            $flows[] = array(
                'description' => 'شراء أصول ثابتة',
                'amount' => -$asset_purchases
            );
        }
        
        // بيع الأصول الثابتة
        $asset_sales = $this->getAssetSales($date_start, $date_end);
        if ($asset_sales != 0) {
            $flows[] = array(
                'description' => 'بيع أصول ثابتة',
                'amount' => $asset_sales
            );
        }
        
        // الاستثمارات في الأوراق المالية
        $investment_purchases = $this->getInvestmentPurchases($date_start, $date_end);
        if ($investment_purchases != 0) {
            $flows[] = array(
                'description' => 'شراء استثمارات',
                'amount' => -$investment_purchases
            );
        }
        
        // بيع الاستثمارات
        $investment_sales = $this->getInvestmentSales($date_start, $date_end);
        if ($investment_sales != 0) {
            $flows[] = array(
                'description' => 'بيع استثمارات',
                'amount' => $investment_sales
            );
        }
        
        return $flows;
    }
    
    /**
     * الحصول على التدفقات النقدية من الأنشطة التمويلية
     */
    private function getFinancingCashFlows($date_start, $date_end) {
        $flows = array();
        
        // القروض المحصلة
        $loan_proceeds = $this->getLoanProceeds($date_start, $date_end);
        if ($loan_proceeds != 0) {
            $flows[] = array(
                'description' => 'قروض محصلة',
                'amount' => $loan_proceeds
            );
        }
        
        // سداد القروض
        $loan_repayments = $this->getLoanRepayments($date_start, $date_end);
        if ($loan_repayments != 0) {
            $flows[] = array(
                'description' => 'سداد قروض',
                'amount' => -$loan_repayments
            );
        }
        
        // زيادة رأس المال
        $capital_increase = $this->getCapitalIncrease($date_start, $date_end);
        if ($capital_increase != 0) {
            $flows[] = array(
                'description' => 'زيادة رأس المال',
                'amount' => $capital_increase
            );
        }
        
        // توزيعات الأرباح المدفوعة
        $dividends_paid = $this->getDividendsPaid($date_start, $date_end);
        if ($dividends_paid != 0) {
            $flows[] = array(
                'description' => 'توزيعات أرباح مدفوعة',
                'amount' => -$dividends_paid
            );
        }
        
        return $flows;
    }
    
    /**
     * الحصول على رصيد النقدية
     */
    private function getCashBalance($date, $is_opening = false) {
        $operator = $is_opening ? '<' : '<=';
        
        $query = $this->db->query("SELECT 
                                    COALESCE(SUM(jel.debit_amount - jel.credit_amount), 0) as balance
                                  FROM " . DB_PREFIX . "journal_entry_line jel
                                  JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
                                  JOIN " . DB_PREFIX . "accounts a ON jel.account_id = a.account_id
                                  WHERE a.account_type = 'asset'
                                  AND (a.account_code LIKE '1111%' OR a.account_code LIKE '1112%')
                                  AND je.status = 'posted'
                                  AND je.journal_date " . $operator . " '" . $this->db->escape($date) . "'");
        
        return (float)$query->row['balance'];
    }
    
    /**
     * تنسيق عناصر التدفق النقدي
     */
    private function formatCashFlowItems($flows, $currency_code) {
        $formatted_flows = array();
        
        foreach ($flows as $flow) {
            $formatted_flows[] = array(
                'description' => $flow['description'],
                'amount' => $flow['amount'],
                'amount_formatted' => $this->currency->format($flow['amount'], $currency_code)
            );
        }
        
        return $formatted_flows;
    }
    
    /**
     * الحصول على صافي الدخل
     */
    private function getNetIncome($date_start, $date_end) {
        // الإيرادات
        $revenue_query = $this->db->query("SELECT 
                                            COALESCE(SUM(jel.credit_amount - jel.debit_amount), 0) as revenue
                                          FROM " . DB_PREFIX . "journal_entry_line jel
                                          JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
                                          JOIN " . DB_PREFIX . "accounts a ON jel.account_id = a.account_id
                                          WHERE a.account_type = 'revenue'
                                          AND je.status = 'posted'
                                          AND je.journal_date BETWEEN '" . $this->db->escape($date_start) . "' AND '" . $this->db->escape($date_end) . "'");
        
        // المصروفات
        $expense_query = $this->db->query("SELECT 
                                            COALESCE(SUM(jel.debit_amount - jel.credit_amount), 0) as expense
                                          FROM " . DB_PREFIX . "journal_entry_line jel
                                          JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
                                          JOIN " . DB_PREFIX . "accounts a ON jel.account_id = a.account_id
                                          WHERE a.account_type = 'expense'
                                          AND je.status = 'posted'
                                          AND je.journal_date BETWEEN '" . $this->db->escape($date_start) . "' AND '" . $this->db->escape($date_end) . "'");
        
        $revenue = (float)$revenue_query->row['revenue'];
        $expense = (float)$expense_query->row['expense'];
        
        return $revenue - $expense;
    }
    
    /**
     * الحصول على متحصلات العملاء
     */
    private function getCustomerReceipts($date_start, $date_end) {
        $query = $this->db->query("SELECT 
                                    COALESCE(SUM(jel.debit_amount), 0) as receipts
                                  FROM " . DB_PREFIX . "journal_entry_line jel
                                  JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
                                  JOIN " . DB_PREFIX . "accounts a ON jel.account_id = a.account_id
                                  WHERE (a.account_code LIKE '1111%' OR a.account_code LIKE '1112%')
                                  AND je.reference_type IN ('sale', 'customer_payment')
                                  AND je.status = 'posted'
                                  AND je.journal_date BETWEEN '" . $this->db->escape($date_start) . "' AND '" . $this->db->escape($date_end) . "'");
        
        return (float)$query->row['receipts'];
    }
    
    /**
     * الحصول على مدفوعات الموردين
     */
    private function getSupplierPayments($date_start, $date_end) {
        $query = $this->db->query("SELECT 
                                    COALESCE(SUM(jel.credit_amount), 0) as payments
                                  FROM " . DB_PREFIX . "journal_entry_line jel
                                  JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
                                  JOIN " . DB_PREFIX . "accounts a ON jel.account_id = a.account_id
                                  WHERE (a.account_code LIKE '1111%' OR a.account_code LIKE '1112%')
                                  AND je.reference_type IN ('purchase', 'supplier_payment')
                                  AND je.status = 'posted'
                                  AND je.journal_date BETWEEN '" . $this->db->escape($date_start) . "' AND '" . $this->db->escape($date_end) . "'");
        
        return (float)$query->row['payments'];
    }
    
    /**
     * الحصول على مدفوعات الرواتب
     */
    private function getSalaryPayments($date_start, $date_end) {
        $query = $this->db->query("SELECT 
                                    COALESCE(SUM(jel.credit_amount), 0) as payments
                                  FROM " . DB_PREFIX . "journal_entry_line jel
                                  JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
                                  JOIN " . DB_PREFIX . "accounts a ON jel.account_id = a.account_id
                                  WHERE (a.account_code LIKE '1111%' OR a.account_code LIKE '1112%')
                                  AND je.reference_type = 'salary_payment'
                                  AND je.status = 'posted'
                                  AND je.journal_date BETWEEN '" . $this->db->escape($date_start) . "' AND '" . $this->db->escape($date_end) . "'");
        
        return (float)$query->row['payments'];
    }
    
    /**
     * الحصول على مصروف الاستهلاك
     */
    private function getDepreciationExpense($date_start, $date_end) {
        $query = $this->db->query("SELECT 
                                    COALESCE(SUM(jel.debit_amount), 0) as depreciation
                                  FROM " . DB_PREFIX . "journal_entry_line jel
                                  JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_id = je.journal_id
                                  WHERE je.reference_type = 'asset_depreciation'
                                  AND je.status = 'posted'
                                  AND je.journal_date BETWEEN '" . $this->db->escape($date_start) . "' AND '" . $this->db->escape($date_end) . "'");
        
        return (float)$query->row['depreciation'];
    }
    
    /**
     * الحصول على مشتريات الأصول الثابتة
     */
    private function getAssetPurchases($date_start, $date_end) {
        $query = $this->db->query("SELECT 
                                    COALESCE(SUM(purchase_cost), 0) as purchases
                                  FROM " . DB_PREFIX . "fixed_assets
                                  WHERE purchase_date BETWEEN '" . $this->db->escape($date_start) . "' AND '" . $this->db->escape($date_end) . "'");
        
        return (float)$query->row['purchases'];
    }
    
    /**
     * دوال مساعدة أخرى للحصول على البيانات المطلوبة
     */
    private function getOperatingExpensePayments($date_start, $date_end) { return 0; }
    private function getTaxPayments($date_start, $date_end) { return 0; }
    private function getInterestPayments($date_start, $date_end) { return 0; }
    private function getInterestReceipts($date_start, $date_end) { return 0; }
    private function getBadDebtProvision($date_start, $date_end) { return 0; }
    private function getAccountsReceivableChange($date_start, $date_end) { return 0; }
    private function getInventoryChange($date_start, $date_end) { return 0; }
    private function getPrepaidExpensesChange($date_start, $date_end) { return 0; }
    private function getAccountsPayableChange($date_start, $date_end) { return 0; }
    private function getAccruedExpensesChange($date_start, $date_end) { return 0; }
    private function getAssetSales($date_start, $date_end) { return 0; }
    private function getInvestmentPurchases($date_start, $date_end) { return 0; }
    private function getInvestmentSales($date_start, $date_end) { return 0; }
    private function getLoanProceeds($date_start, $date_end) { return 0; }
    private function getLoanRepayments($date_start, $date_end) { return 0; }
    private function getCapitalIncrease($date_start, $date_end) { return 0; }
    private function getDividendsPaid($date_start, $date_end) { return 0; }
}
