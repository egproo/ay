<?php
/**
 * نموذج نظام الرواتب المتطور مع التكامل المحاسبي
 *
 * يوفر إدارة شاملة للرواتب مع:
 * - حساب الرواتب التلقائي
 * - الاستقطاعات والإضافات
 * - التكامل مع المحاسبة
 * - قيود اليومية التلقائية
 * - تقارير الرواتب المتقدمة
 *
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class ModelHrPayrollAdvanced extends Model {

    /**
     * إنشاء دورة رواتب جديدة
     */
    public function createPayrollCycle($data) {
        $this->db->query("
            INSERT INTO cod_payroll_cycle SET
            cycle_name = '" . $this->db->escape($data['cycle_name']) . "',
            period_start = '" . $this->db->escape($data['period_start']) . "',
            period_end = '" . $this->db->escape($data['period_end']) . "',
            pay_date = '" . $this->db->escape($data['pay_date']) . "',
            status = 'draft',
            created_by = '" . (int)$this->user->getId() . "',
            date_created = NOW()
        ");

        $cycle_id = $this->db->getLastId();

        // إنشاء سجلات الرواتب للموظفين النشطين
        $this->generateEmployeePayrollRecords($cycle_id, $data['period_start'], $data['period_end']);

        return $cycle_id;
    }

    /**
     * إنشاء سجلات الرواتب للموظفين
     */
    private function generateEmployeePayrollRecords($cycle_id, $period_start, $period_end) {
        // الحصول على الموظفين النشطين
        $employees = $this->getActiveEmployees();

        foreach ($employees as $employee) {
            // حساب الراتب الأساسي
            $basic_salary = $this->calculateBasicSalary($employee, $period_start, $period_end);

            // حساب الإضافات
            $allowances = $this->calculateAllowances($employee, $period_start, $period_end);

            // حساب الاستقطاعات
            $deductions = $this->calculateDeductions($employee, $period_start, $period_end);

            // حساب الصافي
            $net_salary = $basic_salary + $allowances['total'] - $deductions['total'];

            // إدراج سجل الراتب
            $this->db->query("
                INSERT INTO cod_payroll_record SET
                cycle_id = '" . (int)$cycle_id . "',
                employee_id = '" . (int)$employee['employee_id'] . "',
                basic_salary = '" . (float)$basic_salary . "',
                total_allowances = '" . (float)$allowances['total'] . "',
                total_deductions = '" . (float)$deductions['total'] . "',
                gross_salary = '" . (float)($basic_salary + $allowances['total']) . "',
                net_salary = '" . (float)$net_salary . "',
                working_days = '" . (int)$allowances['working_days'] . "',
                absent_days = '" . (int)$deductions['absent_days'] . "',
                overtime_hours = '" . (float)$allowances['overtime_hours'] . "',
                status = 'calculated',
                date_created = NOW()
            ");

            $record_id = $this->db->getLastId();

            // إدراج تفاصيل الإضافات
            $this->insertAllowanceDetails($record_id, $allowances['details']);

            // إدراج تفاصيل الاستقطاعات
            $this->insertDeductionDetails($record_id, $deductions['details']);
        }
    }

    /**
     * حساب الراتب الأساسي
     */
    private function calculateBasicSalary($employee, $period_start, $period_end) {
        // حساب عدد أيام الفترة
        $total_days = $this->getWorkingDaysBetween($period_start, $period_end);

        // حساب أيام الحضور الفعلي
        $attended_days = $this->getAttendedDays($employee['user_id'], $period_start, $period_end);

        // حساب الراتب اليومي
        $daily_salary = $employee['salary'] / 30; // افتراض 30 يوم في الشهر

        return $daily_salary * $attended_days;
    }

    /**
     * حساب الإضافات
     */
    private function calculateAllowances($employee, $period_start, $period_end) {
        $allowances = [
            'total' => 0,
            'working_days' => 0,
            'overtime_hours' => 0,
            'details' => []
        ];

        // حساب أيام العمل
        $working_days = $this->getAttendedDays($employee['user_id'], $period_start, $period_end);
        $allowances['working_days'] = $working_days;

        // حساب الإضافي
        $overtime_hours = $this->getOvertimeHours($employee['user_id'], $period_start, $period_end);
        $allowances['overtime_hours'] = $overtime_hours;

        if ($overtime_hours > 0) {
            $hourly_rate = $employee['salary'] / (30 * 8); // افتراض 8 ساعات يومياً
            $overtime_amount = $overtime_hours * $hourly_rate * 1.5; // 150% للإضافي

            $allowances['total'] += $overtime_amount;
            $allowances['details'][] = [
                'type' => 'overtime',
                'description' => 'ساعات إضافية',
                'amount' => $overtime_amount,
                'hours' => $overtime_hours
            ];
        }

        // بدل النقل
        $transport_allowance = $this->getEmployeeAllowance($employee['employee_id'], 'transport');
        if ($transport_allowance > 0) {
            $allowances['total'] += $transport_allowance;
            $allowances['details'][] = [
                'type' => 'transport',
                'description' => 'بدل النقل',
                'amount' => $transport_allowance
            ];
        }

        // بدل السكن
        $housing_allowance = $this->getEmployeeAllowance($employee['employee_id'], 'housing');
        if ($housing_allowance > 0) {
            $allowances['total'] += $housing_allowance;
            $allowances['details'][] = [
                'type' => 'housing',
                'description' => 'بدل السكن',
                'amount' => $housing_allowance
            ];
        }

        // مكافآت الأداء
        $performance_bonus = $this->getPerformanceBonus($employee['employee_id'], $period_start, $period_end);
        if ($performance_bonus > 0) {
            $allowances['total'] += $performance_bonus;
            $allowances['details'][] = [
                'type' => 'performance',
                'description' => 'مكافأة الأداء',
                'amount' => $performance_bonus
            ];
        }

        return $allowances;
    }

    /**
     * حساب الاستقطاعات
     */
    private function calculateDeductions($employee, $period_start, $period_end) {
        $deductions = [
            'total' => 0,
            'absent_days' => 0,
            'details' => []
        ];

        // حساب أيام الغياب
        $absent_days = $this->getAbsentDays($employee['user_id'], $period_start, $period_end);
        $deductions['absent_days'] = $absent_days;

        if ($absent_days > 0) {
            $daily_salary = $employee['salary'] / 30;
            $absent_deduction = $absent_days * $daily_salary;

            $deductions['total'] += $absent_deduction;
            $deductions['details'][] = [
                'type' => 'absence',
                'description' => 'خصم أيام الغياب',
                'amount' => $absent_deduction,
                'days' => $absent_days
            ];
        }

        // التأمينات الاجتماعية
        $social_insurance = $employee['salary'] * 0.11; // 11% للموظف
        $deductions['total'] += $social_insurance;
        $deductions['details'][] = [
            'type' => 'social_insurance',
            'description' => 'التأمينات الاجتماعية',
            'amount' => $social_insurance,
            'percentage' => 11
        ];

        // ضريبة الدخل
        $income_tax = $this->calculateIncomeTax($employee['salary']);
        if ($income_tax > 0) {
            $deductions['total'] += $income_tax;
            $deductions['details'][] = [
                'type' => 'income_tax',
                'description' => 'ضريبة الدخل',
                'amount' => $income_tax
            ];
        }

        // السلف والقروض
        $advance_deduction = $this->getAdvanceDeduction($employee['employee_id'], $period_start, $period_end);
        if ($advance_deduction > 0) {
            $deductions['total'] += $advance_deduction;
            $deductions['details'][] = [
                'type' => 'advance',
                'description' => 'استقطاع السلف',
                'amount' => $advance_deduction
            ];
        }

        return $deductions;
    }

    /**
     * اعتماد دورة الرواتب وإنشاء القيود المحاسبية
     */
    public function approvePayrollCycle($cycle_id) {
        // التحقق من حالة الدورة
        $cycle = $this->getPayrollCycle($cycle_id);
        if (!$cycle || $cycle['status'] != 'calculated') {
            throw new Exception('لا يمكن اعتماد هذه الدورة');
        }

        // الحصول على إجمالي الرواتب
        $totals = $this->getPayrollCycleTotals($cycle_id);

        // إنشاء القيد المحاسبي
        $this->load->model('accounts/journal');

        $journal_data = [
            'reference' => 'PAY-' . $cycle['cycle_name'],
            'description' => 'قيد رواتب ' . $cycle['cycle_name'],
            'date' => date('Y-m-d'),
            'entries' => []
        ];

        // مدين: مصروف الرواتب والأجور
        $journal_data['entries'][] = [
            'account_id' => $this->getAccountId('salary_expense'),
            'debit' => $totals['gross_salary'],
            'credit' => 0,
            'description' => 'إجمالي الرواتب والأجور'
        ];

        // دائن: الرواتب المستحقة
        $journal_data['entries'][] = [
            'account_id' => $this->getAccountId('salary_payable'),
            'debit' => 0,
            'credit' => $totals['net_salary'],
            'description' => 'صافي الرواتب المستحقة'
        ];

        // دائن: التأمينات الاجتماعية مستحقة
        $journal_data['entries'][] = [
            'account_id' => $this->getAccountId('social_insurance_payable'),
            'debit' => 0,
            'credit' => $totals['social_insurance'],
            'description' => 'التأمينات الاجتماعية مستحقة'
        ];

        // دائن: ضريبة الدخل مستحقة
        if ($totals['income_tax'] > 0) {
            $journal_data['entries'][] = [
                'account_id' => $this->getAccountId('income_tax_payable'),
                'debit' => 0,
                'credit' => $totals['income_tax'],
                'description' => 'ضريبة الدخل مستحقة'
            ];
        }

        // إنشاء القيد
        $journal_id = $this->model_accounts_journal->addJournalEntry($journal_data);

        // تحديث حالة الدورة
        $this->db->query("
            UPDATE cod_payroll_cycle SET
            status = 'approved',
            journal_id = '" . (int)$journal_id . "',
            approved_by = '" . (int)$this->user->getId() . "',
            date_approved = NOW()
            WHERE cycle_id = '" . (int)$cycle_id . "'
        ");

        return $journal_id;
    }

    /**
     * صرف الرواتب وإنشاء قيد الصرف
     */
    public function disbursePay($cycle_id, $payment_method = 'bank_transfer') {
        // التحقق من حالة الدورة
        $cycle = $this->getPayrollCycle($cycle_id);
        if (!$cycle || $cycle['status'] != 'approved') {
            throw new Exception('يجب اعتماد الدورة أولاً');
        }

        // الحصول على إجمالي الرواتب
        $totals = $this->getPayrollCycleTotals($cycle_id);

        // إنشاء قيد الصرف
        $this->load->model('accounts/journal');

        $journal_data = [
            'reference' => 'PAY-DISB-' . $cycle['cycle_name'],
            'description' => 'صرف رواتب ' . $cycle['cycle_name'],
            'date' => date('Y-m-d'),
            'entries' => []
        ];

        // مدين: الرواتب المستحقة
        $journal_data['entries'][] = [
            'account_id' => $this->getAccountId('salary_payable'),
            'debit' => $totals['net_salary'],
            'credit' => 0,
            'description' => 'صرف الرواتب المستحقة'
        ];

        // دائن: البنك أو النقدية
        $account_key = ($payment_method == 'cash') ? 'cash' : 'bank';
        $journal_data['entries'][] = [
            'account_id' => $this->getAccountId($account_key),
            'debit' => 0,
            'credit' => $totals['net_salary'],
            'description' => 'صرف الرواتب - ' . ($payment_method == 'cash' ? 'نقداً' : 'تحويل بنكي')
        ];

        // إنشاء القيد
        $journal_id = $this->model_accounts_journal->addJournalEntry($journal_data);

        // تحديث حالة الدورة
        $this->db->query("
            UPDATE cod_payroll_cycle SET
            status = 'paid',
            payment_method = '" . $this->db->escape($payment_method) . "',
            disbursement_journal_id = '" . (int)$journal_id . "',
            paid_by = '" . (int)$this->user->getId() . "',
            date_paid = NOW()
            WHERE cycle_id = '" . (int)$cycle_id . "'
        ");

        // تحديث حالة سجلات الموظفين
        $this->db->query("
            UPDATE cod_payroll_record SET
            status = 'paid',
            date_paid = NOW()
            WHERE cycle_id = '" . (int)$cycle_id . "'
        ");

        return $journal_id;
    }

    /**
     * الحصول على الموظفين النشطين
     */
    private function getActiveEmployees() {
        $query = $this->db->query("
            SELECT ep.*, u.firstname, u.lastname, u.email
            FROM cod_employee_profile ep
            LEFT JOIN cod_user u ON (ep.user_id = u.user_id)
            WHERE ep.status = 'active' AND u.status = 1
        ");

        return $query->rows;
    }

    /**
     * الحصول على أيام الحضور
     */
    private function getAttendedDays($user_id, $start_date, $end_date) {
        $query = $this->db->query("
            SELECT COUNT(*) as attended_days
            FROM cod_attendance
            WHERE user_id = '" . (int)$user_id . "'
            AND date BETWEEN '" . $this->db->escape($start_date) . "'
            AND '" . $this->db->escape($end_date) . "'
            AND status IN ('present', 'late')
        ");

        return $query->row['attended_days'];
    }

    /**
     * الحصول على ساعات الإضافي
     */
    private function getOvertimeHours($user_id, $start_date, $end_date) {
        $query = $this->db->query("
            SELECT COALESCE(SUM(overtime_hours), 0) as total_overtime
            FROM cod_attendance
            WHERE user_id = '" . (int)$user_id . "'
            AND date BETWEEN '" . $this->db->escape($start_date) . "'
            AND '" . $this->db->escape($end_date) . "'
        ");

        return $query->row['total_overtime'];
    }

    /**
     * الحصول على معرف الحساب المحاسبي
     */
    private function getAccountId($account_key) {
        $account_mapping = [
            'salary_expense' => 5001, // مصروف الرواتب والأجور
            'salary_payable' => 2001, // الرواتب المستحقة
            'social_insurance_payable' => 2002, // التأمينات الاجتماعية مستحقة
            'income_tax_payable' => 2003, // ضريبة الدخل مستحقة
            'bank' => 1002, // البنك
            'cash' => 1001  // النقدية
        ];

        return isset($account_mapping[$account_key]) ? $account_mapping[$account_key] : 0;
    }

    /**
     * الحصول على أيام الغياب
     */
    private function getAbsentDays($user_id, $start_date, $end_date) {
        $query = $this->db->query("
            SELECT COUNT(*) as absent_days
            FROM cod_attendance
            WHERE user_id = '" . (int)$user_id . "'
            AND date BETWEEN '" . $this->db->escape($start_date) . "'
            AND '" . $this->db->escape($end_date) . "'
            AND status = 'absent'
        ");

        return $query->row['absent_days'];
    }

    /**
     * الحصول على بدل الموظف
     */
    private function getEmployeeAllowance($employee_id, $allowance_type) {
        $query = $this->db->query("
            SELECT amount
            FROM cod_employee_allowance
            WHERE employee_id = '" . (int)$employee_id . "'
            AND allowance_type = '" . $this->db->escape($allowance_type) . "'
            AND status = 'active'
        ");

        return $query->num_rows ? $query->row['amount'] : 0;
    }

    /**
     * الحصول على مكافأة الأداء
     */
    private function getPerformanceBonus($employee_id, $start_date, $end_date) {
        $query = $this->db->query("
            SELECT COALESCE(SUM(bonus_amount), 0) as total_bonus
            FROM cod_performance_bonus
            WHERE employee_id = '" . (int)$employee_id . "'
            AND bonus_date BETWEEN '" . $this->db->escape($start_date) . "'
            AND '" . $this->db->escape($end_date) . "'
            AND status = 'approved'
        ");

        return $query->row['total_bonus'];
    }

    /**
     * حساب ضريبة الدخل
     */
    private function calculateIncomeTax($salary) {
        // شرائح ضريبة الدخل المصرية (مثال)
        if ($salary <= 8000) {
            return 0; // معفى
        } elseif ($salary <= 30000) {
            return ($salary - 8000) * 0.025; // 2.5%
        } elseif ($salary <= 45000) {
            return 550 + ($salary - 30000) * 0.10; // 10%
        } elseif ($salary <= 60000) {
            return 2050 + ($salary - 45000) * 0.15; // 15%
        } else {
            return 4300 + ($salary - 60000) * 0.20; // 20%
        }
    }

    /**
     * الحصول على استقطاع السلف
     */
    private function getAdvanceDeduction($employee_id, $start_date, $end_date) {
        $query = $this->db->query("
            SELECT COALESCE(SUM(installment_amount), 0) as total_deduction
            FROM cod_employee_advance_installment
            WHERE employee_id = '" . (int)$employee_id . "'
            AND due_date BETWEEN '" . $this->db->escape($start_date) . "'
            AND '" . $this->db->escape($end_date) . "'
            AND status = 'pending'
        ");

        return $query->row['total_deduction'];
    }

    /**
     * إدراج تفاصيل الإضافات
     */
    private function insertAllowanceDetails($record_id, $allowances) {
        foreach ($allowances as $allowance) {
            $this->db->query("
                INSERT INTO cod_payroll_allowance SET
                record_id = '" . (int)$record_id . "',
                allowance_type = '" . $this->db->escape($allowance['type']) . "',
                description = '" . $this->db->escape($allowance['description']) . "',
                amount = '" . (float)$allowance['amount'] . "',
                hours = '" . (float)(isset($allowance['hours']) ? $allowance['hours'] : 0) . "'
            ");
        }
    }

    /**
     * إدراج تفاصيل الاستقطاعات
     */
    private function insertDeductionDetails($record_id, $deductions) {
        foreach ($deductions as $deduction) {
            $this->db->query("
                INSERT INTO cod_payroll_deduction SET
                record_id = '" . (int)$record_id . "',
                deduction_type = '" . $this->db->escape($deduction['type']) . "',
                description = '" . $this->db->escape($deduction['description']) . "',
                amount = '" . (float)$deduction['amount'] . "',
                days = '" . (int)(isset($deduction['days']) ? $deduction['days'] : 0) . "',
                percentage = '" . (float)(isset($deduction['percentage']) ? $deduction['percentage'] : 0) . "'
            ");
        }
    }

    /**
     * الحصول على دورة الرواتب
     */
    public function getPayrollCycle($cycle_id) {
        $query = $this->db->query("
            SELECT * FROM cod_payroll_cycle
            WHERE cycle_id = '" . (int)$cycle_id . "'
        ");

        return $query->num_rows ? $query->row : false;
    }

    /**
     * الحصول على إجماليات دورة الرواتب
     */
    public function getPayrollCycleTotals($cycle_id) {
        $query = $this->db->query("
            SELECT
                COUNT(*) as employee_count,
                SUM(basic_salary) as basic_salary,
                SUM(total_allowances) as total_allowances,
                SUM(total_deductions) as total_deductions,
                SUM(gross_salary) as gross_salary,
                SUM(net_salary) as net_salary
            FROM cod_payroll_record
            WHERE cycle_id = '" . (int)$cycle_id . "'
        ");

        $totals = $query->row;

        // حساب إجماليات الاستقطاعات المحددة
        $deduction_query = $this->db->query("
            SELECT
                SUM(CASE WHEN pd.deduction_type = 'social_insurance' THEN pd.amount ELSE 0 END) as social_insurance,
                SUM(CASE WHEN pd.deduction_type = 'income_tax' THEN pd.amount ELSE 0 END) as income_tax,
                SUM(CASE WHEN pd.deduction_type = 'advance' THEN pd.amount ELSE 0 END) as advance_deduction
            FROM cod_payroll_deduction pd
            INNER JOIN cod_payroll_record pr ON (pd.record_id = pr.record_id)
            WHERE pr.cycle_id = '" . (int)$cycle_id . "'
        ");

        $totals = array_merge($totals, $deduction_query->row);

        return $totals;
    }

    /**
     * الحصول على قائمة دورات الرواتب
     */
    public function getPayrollCycles($filter_data = []) {
        $sql = "SELECT pc.*,
                CONCAT(u1.firstname, ' ', u1.lastname) as created_by_name,
                CONCAT(u2.firstname, ' ', u2.lastname) as approved_by_name,
                CONCAT(u3.firstname, ' ', u3.lastname) as paid_by_name,
                (SELECT COUNT(*) FROM cod_payroll_record WHERE cycle_id = pc.cycle_id) as employee_count,
                (SELECT SUM(net_salary) FROM cod_payroll_record WHERE cycle_id = pc.cycle_id) as total_amount
                FROM cod_payroll_cycle pc
                LEFT JOIN cod_user u1 ON (pc.created_by = u1.user_id)
                LEFT JOIN cod_user u2 ON (pc.approved_by = u2.user_id)
                LEFT JOIN cod_user u3 ON (pc.paid_by = u3.user_id)
                WHERE 1";

        if (!empty($filter_data['filter_status'])) {
            $sql .= " AND pc.status = '" . $this->db->escape($filter_data['filter_status']) . "'";
        }

        if (!empty($filter_data['filter_date_start'])) {
            $sql .= " AND pc.period_start >= '" . $this->db->escape($filter_data['filter_date_start']) . "'";
        }

        if (!empty($filter_data['filter_date_end'])) {
            $sql .= " AND pc.period_end <= '" . $this->db->escape($filter_data['filter_date_end']) . "'";
        }

        $sql .= " ORDER BY pc.date_created DESC";

        if (isset($filter_data['start']) || isset($filter_data['limit'])) {
            if ($filter_data['start'] < 0) {
                $filter_data['start'] = 0;
            }

            if ($filter_data['limit'] < 1) {
                $filter_data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$filter_data['start'] . "," . (int)$filter_data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    /**
     * الحصول على سجلات الرواتب لدورة معينة
     */
    public function getPayrollRecords($cycle_id) {
        $query = $this->db->query("
            SELECT pr.*,
                ep.job_title,
                CONCAT(u.firstname, ' ', u.lastname) as employee_name,
                u.email as employee_email
            FROM cod_payroll_record pr
            LEFT JOIN cod_employee_profile ep ON (pr.employee_id = ep.employee_id)
            LEFT JOIN cod_user u ON (ep.user_id = u.user_id)
            WHERE pr.cycle_id = '" . (int)$cycle_id . "'
            ORDER BY u.firstname, u.lastname
        ");

        return $query->rows;
    }

    /**
     * الحصول على تفاصيل راتب موظف
     */
    public function getPayrollRecordDetails($record_id) {
        $record_query = $this->db->query("
            SELECT pr.*,
                ep.job_title,
                CONCAT(u.firstname, ' ', u.lastname) as employee_name,
                u.email as employee_email,
                pc.cycle_name, pc.period_start, pc.period_end
            FROM cod_payroll_record pr
            LEFT JOIN cod_employee_profile ep ON (pr.employee_id = ep.employee_id)
            LEFT JOIN cod_user u ON (ep.user_id = u.user_id)
            LEFT JOIN cod_payroll_cycle pc ON (pr.cycle_id = pc.cycle_id)
            WHERE pr.record_id = '" . (int)$record_id . "'
        ");

        if (!$record_query->num_rows) {
            return false;
        }

        $record = $record_query->row;

        // الحصول على الإضافات
        $allowances_query = $this->db->query("
            SELECT * FROM cod_payroll_allowance
            WHERE record_id = '" . (int)$record_id . "'
            ORDER BY allowance_type
        ");
        $record['allowances'] = $allowances_query->rows;

        // الحصول على الاستقطاعات
        $deductions_query = $this->db->query("
            SELECT * FROM cod_payroll_deduction
            WHERE record_id = '" . (int)$record_id . "'
            ORDER BY deduction_type
        ");
        $record['deductions'] = $deductions_query->rows;

        return $record;
    }

    /**
     * حساب عدد أيام العمل بين تاريخين
     */
    private function getWorkingDaysBetween($start_date, $end_date) {
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start, $interval, $end->add($interval));

        $working_days = 0;
        foreach ($period as $date) {
            // تجاهل الجمعة والسبت (أيام الإجازة)
            if ($date->format('N') < 6) {
                $working_days++;
            }
        }

        return $working_days;
    }

    /**
     * إنشاء قسيمة راتب PDF
     */
    public function generatePayslipPDF($record_id) {
        $record = $this->getPayrollRecordDetails($record_id);

        if (!$record) {
            throw new Exception('سجل الراتب غير موجود');
        }

        // هنا يمكن استخدام مكتبة PDF مثل TCPDF أو FPDF
        // لإنشاء قسيمة راتب مفصلة

        return $record; // مؤقتاً نرجع البيانات
    }

    /**
     * تصدير الرواتب إلى Excel
     */
    public function exportPayrollToExcel($cycle_id) {
        $records = $this->getPayrollRecords($cycle_id);
        $cycle = $this->getPayrollCycle($cycle_id);

        // هنا يمكن استخدام مكتبة PhpSpreadsheet
        // لتصدير البيانات إلى Excel

        return [
            'cycle' => $cycle,
            'records' => $records
        ];
    }
}
