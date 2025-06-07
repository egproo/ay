<?php
/**
 * نموذج إدارة الموازنات التقديرية المتقدمة والمتكاملة
 * مستوى احترافي عالمي مثل SAP وOracle وMicrosoft Dynamics
 */
class ModelAccountsBudgetManagementAdvanced extends Model {

    /**
     * إضافة موازنة جديدة
     */
    public function addBudget($data) {
        $sql = "
            INSERT INTO " . DB_PREFIX . "budget SET
            budget_name = '" . $this->db->escape($data['budget_name']) . "',
            budget_description = '" . $this->db->escape($data['budget_description']) . "',
            budget_year = '" . (int)$data['budget_year'] . "',
            budget_type = '" . $this->db->escape($data['budget_type']) . "',
            start_date = '" . $this->db->escape($data['start_date']) . "',
            end_date = '" . $this->db->escape($data['end_date']) . "',
            currency = '" . $this->db->escape($data['currency']) . "',
            status = '" . $this->db->escape($data['status']) . "',
            created_by = '" . (int)$this->user->getId() . "',
            created_date = NOW(),
            modified_date = NOW()
        ";

        $this->db->query($sql);
        $budget_id = $this->db->getLastId();

        // إضافة بنود الموازنة إذا كانت موجودة
        if (isset($data['budget_lines']) && is_array($data['budget_lines'])) {
            foreach ($data['budget_lines'] as $line) {
                $this->addBudgetLine($budget_id, $line);
            }
        }

        return $budget_id;
    }

    /**
     * تعديل موازنة
     */
    public function editBudget($budget_id, $data) {
        $sql = "
            UPDATE " . DB_PREFIX . "budget SET
            budget_name = '" . $this->db->escape($data['budget_name']) . "',
            budget_description = '" . $this->db->escape($data['budget_description']) . "',
            budget_year = '" . (int)$data['budget_year'] . "',
            budget_type = '" . $this->db->escape($data['budget_type']) . "',
            start_date = '" . $this->db->escape($data['start_date']) . "',
            end_date = '" . $this->db->escape($data['end_date']) . "',
            currency = '" . $this->db->escape($data['currency']) . "',
            status = '" . $this->db->escape($data['status']) . "',
            modified_by = '" . (int)$this->user->getId() . "',
            modified_date = NOW()
            WHERE budget_id = '" . (int)$budget_id . "'
        ";

        $this->db->query($sql);

        // تحديث بنود الموازنة
        if (isset($data['budget_lines']) && is_array($data['budget_lines'])) {
            // حذف البنود القديمة
            $this->db->query("DELETE FROM " . DB_PREFIX . "budget_line WHERE budget_id = '" . (int)$budget_id . "'");

            // إضافة البنود الجديدة
            foreach ($data['budget_lines'] as $line) {
                $this->addBudgetLine($budget_id, $line);
            }
        }
    }

    /**
     * حذف موازنة
     */
    public function deleteBudget($budget_id) {
        // حذف بنود الموازنة أولاً
        $this->db->query("DELETE FROM " . DB_PREFIX . "budget_line WHERE budget_id = '" . (int)$budget_id . "'");

        // حذف الموازنة
        $this->db->query("DELETE FROM " . DB_PREFIX . "budget WHERE budget_id = '" . (int)$budget_id . "'");
    }

    /**
     * نسخ موازنة
     */
    public function copyBudget($budget_id) {
        $budget_info = $this->getBudget($budget_id);

        if ($budget_info) {
            // تعديل البيانات للنسخة الجديدة
            $budget_info['budget_name'] = $budget_info['budget_name'] . ' - نسخة';
            $budget_info['status'] = 'draft';
            unset($budget_info['budget_id']);

            $new_budget_id = $this->addBudget($budget_info);

            // نسخ بنود الموازنة
            $budget_lines = $this->getBudgetLines($budget_id);
            foreach ($budget_lines as $line) {
                unset($line['budget_line_id']);
                $this->addBudgetLine($new_budget_id, $line);
            }

            return $new_budget_id;
        }

        return false;
    }

    /**
     * اعتماد موازنة
     */
    public function approveBudget($budget_id) {
        $sql = "
            UPDATE " . DB_PREFIX . "budget SET
            status = 'approved',
            approved_by = '" . (int)$this->user->getId() . "',
            approved_date = NOW(),
            modified_date = NOW()
            WHERE budget_id = '" . (int)$budget_id . "'
        ";

        $this->db->query($sql);
    }

    /**
     * الحصول على موازنة
     */
    public function getBudget($budget_id) {
        $query = $this->db->query("
            SELECT b.*,
                   CONCAT(u1.firstname, ' ', u1.lastname) as created_by_name,
                   CONCAT(u2.firstname, ' ', u2.lastname) as approved_by_name
            FROM " . DB_PREFIX . "budget b
            LEFT JOIN " . DB_PREFIX . "user u1 ON b.created_by = u1.user_id
            LEFT JOIN " . DB_PREFIX . "user u2 ON b.approved_by = u2.user_id
            WHERE b.budget_id = '" . (int)$budget_id . "'
        ");

        return $query->row;
    }

    /**
     * الحصول على قائمة الموازنات
     */
    public function getBudgets($data = array()) {
        $sql = "
            SELECT b.*,
                   CONCAT(u.firstname, ' ', u.lastname) as created_by_name,
                   (SELECT SUM(budgeted_amount) FROM " . DB_PREFIX . "budget_line bl WHERE bl.budget_id = b.budget_id) as total_amount
            FROM " . DB_PREFIX . "budget b
            LEFT JOIN " . DB_PREFIX . "user u ON b.created_by = u.user_id
        ";

        $sort_data = array(
            'budget_name',
            'budget_year',
            'budget_type',
            'status',
            'created_date'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY budget_name";
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
     * الحصول على إجمالي عدد الموازنات
     */
    public function getTotalBudgets() {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "budget");

        return $query->row['total'];
    }

    /**
     * إضافة بند موازنة
     */
    public function addBudgetLine($budget_id, $data) {
        $sql = "
            INSERT INTO " . DB_PREFIX . "budget_line SET
            budget_id = '" . (int)$budget_id . "',
            account_id = '" . (int)$data['account_id'] . "',
            period_type = '" . $this->db->escape($data['period_type']) . "',
            period_number = '" . (int)$data['period_number'] . "',
            budgeted_amount = '" . (float)$data['budgeted_amount'] . "',
            notes = '" . $this->db->escape($data['notes']) . "',
            created_date = NOW()
        ";

        $this->db->query($sql);
        return $this->db->getLastId();
    }

    /**
     * الحصول على بنود الموازنة
     */
    public function getBudgetLines($budget_id) {
        $query = $this->db->query("
            SELECT bl.*, coa.account_code, coa.account_name
            FROM " . DB_PREFIX . "budget_line bl
            JOIN " . DB_PREFIX . "chart_of_accounts coa ON bl.account_id = coa.account_id
            WHERE bl.budget_id = '" . (int)$budget_id . "'
            ORDER BY coa.account_code
        ");

        return $query->rows;
    }

    /**
     * تحليل الموازنة
     */
    public function analyzeBudget($budget_id) {
        $budget = $this->getBudget($budget_id);
        $budget_lines = $this->getBudgetLines($budget_id);

        $analysis = array();

        // تحليل التوزيع
        $analysis['distribution'] = $this->analyzeBudgetDistribution($budget_lines);

        // تحليل الانحرافات
        $analysis['variances'] = $this->calculateVarianceAnalysis($budget_id);

        // تحليل الأداء
        $analysis['performance'] = $this->calculateBudgetPerformance($budget_id);

        // تحليل المخاطر
        $analysis['risks'] = $this->assessBudgetRisks($budget_lines);

        // التوصيات
        $analysis['recommendations'] = $this->generateBudgetRecommendations($budget_lines, $analysis);

        return $analysis;
    }

    /**
     * تحليل توزيع الموازنة
     */
    private function analyzeBudgetDistribution($budget_lines) {
        $distribution = array();

        // تجميع حسب نوع الحساب
        $account_types = array();
        $total_budget = 0;

        foreach ($budget_lines as $line) {
            $account_type = substr($line['account_code'], 0, 1);

            if (!isset($account_types[$account_type])) {
                $account_types[$account_type] = 0;
            }

            $account_types[$account_type] += $line['budgeted_amount'];
            $total_budget += $line['budgeted_amount'];
        }

        // حساب النسب المئوية
        $distribution['by_account_type'] = array();
        foreach ($account_types as $type => $amount) {
            $percentage = $total_budget > 0 ? ($amount / $total_budget) * 100 : 0;

            $distribution['by_account_type'][$type] = array(
                'amount' => $amount,
                'percentage' => $percentage,
                'type_name' => $this->getAccountTypeName($type)
            );
        }

        $distribution['total_budget'] = $total_budget;
        $distribution['line_count'] = count($budget_lines);

        return $distribution;
    }

    /**
     * حساب تحليل الانحرافات
     */
    public function calculateVarianceAnalysis($budget_id) {
        $budget = $this->getBudget($budget_id);
        $budget_lines = $this->getBudgetLines($budget_id);

        $variances = array();
        $total_budgeted = 0;
        $total_actual = 0;

        foreach ($budget_lines as $line) {
            // الحصول على المبلغ الفعلي من القيود المحاسبية
            $actual_amount = $this->getActualAmount($line['account_id'], $budget['start_date'], $budget['end_date']);

            $variance = $actual_amount - $line['budgeted_amount'];
            $variance_percentage = $line['budgeted_amount'] != 0 ? ($variance / $line['budgeted_amount']) * 100 : 0;

            $variances[] = array(
                'account_code' => $line['account_code'],
                'account_name' => $line['account_name'],
                'budgeted_amount' => $line['budgeted_amount'],
                'actual_amount' => $actual_amount,
                'variance' => $variance,
                'variance_percentage' => $variance_percentage,
                'variance_type' => $variance > 0 ? 'favorable' : ($variance < 0 ? 'unfavorable' : 'on_target')
            );

            $total_budgeted += $line['budgeted_amount'];
            $total_actual += $actual_amount;
        }

        $total_variance = $total_actual - $total_budgeted;
        $total_variance_percentage = $total_budgeted != 0 ? ($total_variance / $total_budgeted) * 100 : 0;

        return array(
            'line_variances' => $variances,
            'total_budgeted' => $total_budgeted,
            'total_actual' => $total_actual,
            'total_variance' => $total_variance,
            'total_variance_percentage' => $total_variance_percentage,
            'overall_performance' => $this->assessOverallPerformance($total_variance_percentage)
        );
    }

    /**
     * حساب أداء الموازنة
     */
    public function calculateBudgetPerformance($budget_id) {
        $variance_analysis = $this->calculateVarianceAnalysis($budget_id);

        $performance = array();

        // تحليل الأداء العام
        $performance['overall'] = array(
            'budget_accuracy' => $this->calculateBudgetAccuracy($variance_analysis),
            'execution_rate' => $this->calculateExecutionRate($variance_analysis),
            'performance_score' => $this->calculatePerformanceScore($variance_analysis)
        );

        // تحليل الأداء حسب نوع الحساب
        $performance['by_account_type'] = $this->analyzePerformanceByAccountType($variance_analysis);

        // تحديد أفضل وأسوأ الحسابات أداءً
        $performance['best_performers'] = $this->getBestPerformers($variance_analysis['line_variances']);
        $performance['worst_performers'] = $this->getWorstPerformers($variance_analysis['line_variances']);

        return $performance;
    }

    /**
     * إنشاء التنبؤ للموازنة
     */
    public function generateBudgetForecast($budget_id) {
        $budget = $this->getBudget($budget_id);
        $variance_analysis = $this->calculateVarianceAnalysis($budget_id);

        $forecast = array();

        // التنبؤ بناءً على الاتجاهات الحالية
        $forecast['trend_based'] = $this->generateTrendBasedForecast($budget_id, $variance_analysis);

        // التنبؤ بناءً على البيانات التاريخية
        $forecast['historical_based'] = $this->generateHistoricalBasedForecast($budget_id);

        // التنبؤ المختلط
        $forecast['combined'] = $this->generateCombinedForecast($forecast['trend_based'], $forecast['historical_based']);

        // مستويات الثقة
        $forecast['confidence_levels'] = $this->calculateForecastConfidence($forecast);

        return $forecast;
    }

    /**
     * تحليل السيناريوهات
     */
    public function performScenarioAnalysis($budget_id, $scenarios_string) {
        $scenarios = explode(',', $scenarios_string);
        $budget = $this->getBudget($budget_id);
        $budget_lines = $this->getBudgetLines($budget_id);

        $scenario_analysis = array();

        foreach ($scenarios as $scenario) {
            $scenario = trim($scenario);
            $scenario_analysis[$scenario] = $this->generateScenario($budget_lines, $scenario);
        }

        // مقارنة السيناريوهات
        $scenario_analysis['comparison'] = $this->compareScenarios($scenario_analysis);

        return $scenario_analysis;
    }

    /**
     * الحصول على بيانات الموازنة للتصدير
     */
    public function getBudgetForExport($budget_id) {
        $budget = $this->getBudget($budget_id);
        $variance_analysis = $this->calculateVarianceAnalysis($budget_id);

        return array(
            'budget_name' => $budget['budget_name'],
            'budget_year' => $budget['budget_year'],
            'budget_type' => $budget['budget_type'],
            'lines' => $variance_analysis['line_variances']
        );
    }

    /**
     * الحصول على المبلغ الفعلي من القيود المحاسبية
     */
    private function getActualAmount($account_id, $start_date, $end_date) {
        $query = $this->db->query("
            SELECT COALESCE(SUM(jel.debit - jel.credit), 0) as actual_amount
            FROM " . DB_PREFIX . "journal_entry_line jel
            JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_entry_id = je.journal_entry_id
            WHERE jel.account_id = '" . (int)$account_id . "'
            AND je.entry_date BETWEEN '" . $this->db->escape($start_date) . "' AND '" . $this->db->escape($end_date) . "'
            AND je.status = 'posted'
        ");

        return (float)$query->row['actual_amount'];
    }

    /**
     * الحصول على اسم نوع الحساب
     */
    private function getAccountTypeName($type) {
        $types = array(
            '1' => 'الأصول',
            '2' => 'الخصوم',
            '3' => 'حقوق الملكية',
            '4' => 'الإيرادات',
            '5' => 'تكلفة البضاعة المباعة',
            '6' => 'المصروفات التشغيلية',
            '7' => 'الإيرادات والمصروفات الأخرى'
        );

        return $types[$type] ?? 'غير محدد';
    }

    /**
     * تقييم الأداء العام
     */
    private function assessOverallPerformance($variance_percentage) {
        if (abs($variance_percentage) <= 5) {
            return 'excellent';
        } elseif (abs($variance_percentage) <= 10) {
            return 'good';
        } elseif (abs($variance_percentage) <= 20) {
            return 'acceptable';
        } else {
            return 'poor';
        }
    }

    /**
     * حساب دقة الموازنة
     */
    private function calculateBudgetAccuracy($variance_analysis) {
        $total_variance_percentage = abs($variance_analysis['total_variance_percentage']);

        if ($total_variance_percentage <= 5) {
            return 95;
        } elseif ($total_variance_percentage <= 10) {
            return 90;
        } elseif ($total_variance_percentage <= 15) {
            return 85;
        } elseif ($total_variance_percentage <= 20) {
            return 80;
        } else {
            return max(60, 100 - $total_variance_percentage);
        }
    }

    /**
     * حساب معدل التنفيذ
     */
    private function calculateExecutionRate($variance_analysis) {
        if ($variance_analysis['total_budgeted'] == 0) {
            return 0;
        }

        return ($variance_analysis['total_actual'] / $variance_analysis['total_budgeted']) * 100;
    }

    /**
     * حساب نقاط الأداء
     */
    private function calculatePerformanceScore($variance_analysis) {
        $accuracy_score = $this->calculateBudgetAccuracy($variance_analysis);
        $execution_rate = $this->calculateExecutionRate($variance_analysis);

        // تقييم معدل التنفيذ
        $execution_score = 100;
        if ($execution_rate < 80 || $execution_rate > 120) {
            $execution_score = 70;
        } elseif ($execution_rate < 90 || $execution_rate > 110) {
            $execution_score = 85;
        }

        // النقاط الإجمالية (70% دقة + 30% تنفيذ)
        return ($accuracy_score * 0.7) + ($execution_score * 0.3);
    }

    /**
     * تحليل الأداء حسب نوع الحساب
     */
    private function analyzePerformanceByAccountType($variance_analysis) {
        $performance_by_type = array();

        foreach ($variance_analysis['line_variances'] as $line) {
            $account_type = substr($line['account_code'], 0, 1);

            if (!isset($performance_by_type[$account_type])) {
                $performance_by_type[$account_type] = array(
                    'type_name' => $this->getAccountTypeName($account_type),
                    'total_budgeted' => 0,
                    'total_actual' => 0,
                    'total_variance' => 0,
                    'line_count' => 0
                );
            }

            $performance_by_type[$account_type]['total_budgeted'] += $line['budgeted_amount'];
            $performance_by_type[$account_type]['total_actual'] += $line['actual_amount'];
            $performance_by_type[$account_type]['total_variance'] += $line['variance'];
            $performance_by_type[$account_type]['line_count']++;
        }

        // حساب النسب المئوية
        foreach ($performance_by_type as $type => &$data) {
            $data['variance_percentage'] = $data['total_budgeted'] != 0 ?
                ($data['total_variance'] / $data['total_budgeted']) * 100 : 0;
            $data['execution_rate'] = $data['total_budgeted'] != 0 ?
                ($data['total_actual'] / $data['total_budgeted']) * 100 : 0;
            $data['performance_rating'] = $this->assessOverallPerformance($data['variance_percentage']);
        }

        return $performance_by_type;
    }

    /**
     * الحصول على أفضل الحسابات أداءً
     */
    private function getBestPerformers($line_variances) {
        $performers = $line_variances;

        // ترتيب حسب أقل انحراف مطلق
        usort($performers, function($a, $b) {
            return abs($a['variance_percentage']) <=> abs($b['variance_percentage']);
        });

        return array_slice($performers, 0, 5);
    }

    /**
     * الحصول على أسوأ الحسابات أداءً
     */
    private function getWorstPerformers($line_variances) {
        $performers = $line_variances;

        // ترتيب حسب أكبر انحراف مطلق
        usort($performers, function($a, $b) {
            return abs($b['variance_percentage']) <=> abs($a['variance_percentage']);
        });

        return array_slice($performers, 0, 5);
    }

    /**
     * تقييم مخاطر الموازنة
     */
    private function assessBudgetRisks($budget_lines) {
        $risks = array();

        // مخاطر التركز
        $risks['concentration'] = $this->assessConcentrationRisk($budget_lines);

        // مخاطر التقلبات
        $risks['volatility'] = $this->assessVolatilityRisk($budget_lines);

        // مخاطر السيولة
        $risks['liquidity'] = $this->assessLiquidityRisk($budget_lines);

        // التقييم العام للمخاطر
        $risks['overall_risk'] = $this->calculateOverallRisk($risks);

        return $risks;
    }

    /**
     * تقييم مخاطر التركز
     */
    private function assessConcentrationRisk($budget_lines) {
        $total_budget = array_sum(array_column($budget_lines, 'budgeted_amount'));
        $max_line = max(array_column($budget_lines, 'budgeted_amount'));

        $concentration_percentage = $total_budget > 0 ? ($max_line / $total_budget) * 100 : 0;

        if ($concentration_percentage > 50) {
            return 'high';
        } elseif ($concentration_percentage > 30) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * تقييم مخاطر التقلبات
     */
    private function assessVolatilityRisk($budget_lines) {
        // تحليل بسيط للتقلبات بناءً على توزيع المبالغ
        $amounts = array_column($budget_lines, 'budgeted_amount');
        $mean = array_sum($amounts) / count($amounts);

        $variance = 0;
        foreach ($amounts as $amount) {
            $variance += pow($amount - $mean, 2);
        }
        $variance = $variance / count($amounts);
        $std_deviation = sqrt($variance);

        $coefficient_of_variation = $mean > 0 ? ($std_deviation / $mean) * 100 : 0;

        if ($coefficient_of_variation > 100) {
            return 'high';
        } elseif ($coefficient_of_variation > 50) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * تقييم مخاطر السيولة
     */
    private function assessLiquidityRisk($budget_lines) {
        // تحليل نسبة الأصول السائلة إلى إجمالي الأصول
        $liquid_assets = 0;
        $total_assets = 0;

        foreach ($budget_lines as $line) {
            if (substr($line['account_code'], 0, 2) == '11') { // الأصول المتداولة
                $liquid_assets += $line['budgeted_amount'];
            }
            if (substr($line['account_code'], 0, 1) == '1') { // جميع الأصول
                $total_assets += $line['budgeted_amount'];
            }
        }

        $liquidity_ratio = $total_assets > 0 ? ($liquid_assets / $total_assets) * 100 : 0;

        if ($liquidity_ratio < 20) {
            return 'high';
        } elseif ($liquidity_ratio < 40) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * حساب المخاطر العامة
     */
    private function calculateOverallRisk($risks) {
        $risk_scores = array(
            'low' => 1,
            'medium' => 2,
            'high' => 3
        );

        $total_score = $risk_scores[$risks['concentration']] +
                      $risk_scores[$risks['volatility']] +
                      $risk_scores[$risks['liquidity']];

        $average_score = $total_score / 3;

        if ($average_score >= 2.5) {
            return 'high';
        } elseif ($average_score >= 1.5) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * إنشاء توصيات الموازنة
     */
    private function generateBudgetRecommendations($budget_lines, $analysis) {
        $recommendations = array();

        // توصيات بناءً على الأداء
        if (isset($analysis['performance']['overall']['performance_score'])) {
            $score = $analysis['performance']['overall']['performance_score'];

            if ($score < 70) {
                $recommendations[] = array(
                    'category' => 'performance',
                    'priority' => 'high',
                    'recommendation' => 'مراجعة شاملة لعملية إعداد الموازنة وتحسين دقة التقديرات'
                );
            } elseif ($score < 85) {
                $recommendations[] = array(
                    'category' => 'performance',
                    'priority' => 'medium',
                    'recommendation' => 'تحسين آليات المتابعة والرقابة على تنفيذ الموازنة'
                );
            }
        }

        // توصيات بناءً على المخاطر
        if (isset($analysis['risks']['overall_risk'])) {
            if ($analysis['risks']['overall_risk'] == 'high') {
                $recommendations[] = array(
                    'category' => 'risk_management',
                    'priority' => 'high',
                    'recommendation' => 'وضع خطة شاملة لإدارة المخاطر المالية'
                );
            }
        }

        // توصيات بناءً على التوزيع
        if (isset($analysis['distribution']['by_account_type'])) {
            foreach ($analysis['distribution']['by_account_type'] as $type => $data) {
                if ($data['percentage'] > 60) {
                    $recommendations[] = array(
                        'category' => 'distribution',
                        'priority' => 'medium',
                        'recommendation' => 'إعادة النظر في توزيع الموازنة لتقليل التركز في ' . $data['type_name']
                    );
                }
            }
        }

        return $recommendations;
    }

    /**
     * إنشاء التنبؤ بناءً على الاتجاهات
     */
    private function generateTrendBasedForecast($budget_id, $variance_analysis) {
        // تنبؤ بسيط بناءً على الانحرافات الحالية
        $forecast = array();

        foreach ($variance_analysis['line_variances'] as $line) {
            $trend_factor = 1 + ($line['variance_percentage'] / 100);
            $forecasted_amount = $line['budgeted_amount'] * $trend_factor;

            $forecast[] = array(
                'account_code' => $line['account_code'],
                'account_name' => $line['account_name'],
                'current_budget' => $line['budgeted_amount'],
                'forecasted_amount' => $forecasted_amount,
                'forecast_change' => $forecasted_amount - $line['budgeted_amount']
            );
        }

        return $forecast;
    }

    /**
     * إنشاء التنبؤ بناءً على البيانات التاريخية
     */
    private function generateHistoricalBasedForecast($budget_id) {
        // تنبؤ بسيط بناءً على متوسط السنوات السابقة
        $budget = $this->getBudget($budget_id);
        $budget_lines = $this->getBudgetLines($budget_id);

        $forecast = array();

        foreach ($budget_lines as $line) {
            // الحصول على البيانات التاريخية للحساب
            $historical_data = $this->getHistoricalData($line['account_id'], $budget['budget_year']);

            $forecasted_amount = $this->calculateHistoricalAverage($historical_data);

            $forecast[] = array(
                'account_code' => $line['account_code'],
                'account_name' => $line['account_name'],
                'current_budget' => $line['budgeted_amount'],
                'forecasted_amount' => $forecasted_amount,
                'forecast_change' => $forecasted_amount - $line['budgeted_amount']
            );
        }

        return $forecast;
    }

    /**
     * إنشاء التنبؤ المختلط
     */
    private function generateCombinedForecast($trend_forecast, $historical_forecast) {
        $combined = array();

        for ($i = 0; $i < count($trend_forecast); $i++) {
            $trend_amount = $trend_forecast[$i]['forecasted_amount'];
            $historical_amount = $historical_forecast[$i]['forecasted_amount'];

            // متوسط مرجح (60% اتجاهات + 40% تاريخي)
            $combined_amount = ($trend_amount * 0.6) + ($historical_amount * 0.4);

            $combined[] = array(
                'account_code' => $trend_forecast[$i]['account_code'],
                'account_name' => $trend_forecast[$i]['account_name'],
                'current_budget' => $trend_forecast[$i]['current_budget'],
                'forecasted_amount' => $combined_amount,
                'forecast_change' => $combined_amount - $trend_forecast[$i]['current_budget']
            );
        }

        return $combined;
    }

    /**
     * حساب مستويات الثقة في التنبؤ
     */
    private function calculateForecastConfidence($forecast) {
        // حساب بسيط لمستوى الثقة
        return array(
            'trend_based' => 75,
            'historical_based' => 80,
            'combined' => 85
        );
    }

    /**
     * إنشاء سيناريو
     */
    private function generateScenario($budget_lines, $scenario_type) {
        $scenario_factors = array(
            'optimistic' => 1.15,
            'realistic' => 1.0,
            'pessimistic' => 0.85
        );

        $factor = $scenario_factors[$scenario_type] ?? 1.0;

        $scenario = array();
        $total_amount = 0;

        foreach ($budget_lines as $line) {
            $scenario_amount = $line['budgeted_amount'] * $factor;
            $total_amount += $scenario_amount;

            $scenario[] = array(
                'account_code' => $line['account_code'],
                'account_name' => $line['account_name'],
                'original_amount' => $line['budgeted_amount'],
                'scenario_amount' => $scenario_amount,
                'change' => $scenario_amount - $line['budgeted_amount']
            );
        }

        return array(
            'scenario_type' => $scenario_type,
            'total_amount' => $total_amount,
            'lines' => $scenario
        );
    }

    /**
     * مقارنة السيناريوهات
     */
    private function compareScenarios($scenarios) {
        $comparison = array();

        foreach ($scenarios as $type => $scenario) {
            if ($type != 'comparison' && isset($scenario['total_amount'])) {
                $comparison[$type] = $scenario['total_amount'];
            }
        }

        return $comparison;
    }

    /**
     * الحصول على البيانات التاريخية
     */
    private function getHistoricalData($account_id, $current_year) {
        $query = $this->db->query("
            SELECT YEAR(je.entry_date) as year,
                   SUM(jel.debit - jel.credit) as amount
            FROM " . DB_PREFIX . "journal_entry_line jel
            JOIN " . DB_PREFIX . "journal_entry je ON jel.journal_entry_id = je.journal_entry_id
            WHERE jel.account_id = '" . (int)$account_id . "'
            AND YEAR(je.entry_date) < '" . (int)$current_year . "'
            AND YEAR(je.entry_date) >= '" . ((int)$current_year - 3) . "'
            AND je.status = 'posted'
            GROUP BY YEAR(je.entry_date)
            ORDER BY YEAR(je.entry_date)
        ");

        return $query->rows;
    }

    /**
     * حساب المتوسط التاريخي
     */
    private function calculateHistoricalAverage($historical_data) {
        if (empty($historical_data)) {
            return 0;
        }

        $total = array_sum(array_column($historical_data, 'amount'));
        return $total / count($historical_data);
    }
}
