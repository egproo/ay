<?php
/**
 * نموذج تقرير الأعمار المتقدم والمتكامل
 * مستوى احترافي عالمي مثل SAP وOracle وMicrosoft Dynamics
 */
class ModelAccountsAgingReportAdvanced extends Model {

    /**
     * إنشاء تقرير الأعمار المتقدم
     */
    public function generateAgingReport($filter_data) {
        $aging_periods = $this->parseAgingPeriods($filter_data['aging_periods']);

        $details = array();
        $summary = array();

        if ($filter_data['report_type'] == 'receivables' || $filter_data['report_type'] == 'both') {
            $receivables = $this->getReceivablesAging($filter_data, $aging_periods);
            $details = array_merge($details, $receivables);
        }

        if ($filter_data['report_type'] == 'payables' || $filter_data['report_type'] == 'both') {
            $payables = $this->getPayablesAging($filter_data, $aging_periods);
            $details = array_merge($details, $payables);
        }

        // ترتيب النتائج
        $details = $this->sortAgingData($details, $filter_data['sort_by']);

        // حساب الملخص
        $summary = $this->calculateAgingSummary($details, $aging_periods);

        return array(
            'details' => $details,
            'summary' => $summary,
            'aging_periods' => $aging_periods,
            'filter_data' => $filter_data,
            'generated_at' => date('Y-m-d H:i:s'),
            'generated_by' => $this->user->getId(),
            'currency' => $filter_data['currency']
        );
    }

    /**
     * الحصول على أعمار المدينين
     */
    private function getReceivablesAging($filter_data, $aging_periods) {
        $sql = "
            SELECT
                c.customer_id,
                CONCAT(c.firstname, ' ', c.lastname) as customer_name,
                c.email,
                c.telephone,
                cg.name as customer_group,
                SUM(ar.amount) as total_amount,
                MIN(ar.due_date) as earliest_due_date,
                MAX(ar.due_date) as latest_due_date,
                DATEDIFF('" . $this->db->escape($filter_data['as_of_date']) . "', MIN(ar.due_date)) as days_outstanding
            FROM " . DB_PREFIX . "accounts_receivable ar
            JOIN " . DB_PREFIX . "customer c ON ar.customer_id = c.customer_id
            LEFT JOIN " . DB_PREFIX . "customer_group cg ON c.customer_group_id = cg.customer_group_id
            WHERE ar.status = 'outstanding'
            AND ar.due_date <= '" . $this->db->escape($filter_data['as_of_date']) . "'
        ";

        // فلترة مجموعة العملاء
        if (!empty($filter_data['customer_group'])) {
            $sql .= " AND c.customer_group_id = '" . (int)$filter_data['customer_group'] . "'";
        }

        $sql .= " GROUP BY c.customer_id, c.firstname, c.lastname, c.email, c.telephone, cg.name";

        // فلترة الأرصدة الصفرية
        if (!$filter_data['include_zero_balances']) {
            $sql .= " HAVING SUM(ar.amount) > 0";
        }

        $query = $this->db->query($sql);

        $receivables = array();
        foreach ($query->rows as $row) {
            $periods = $this->calculateAgingPeriods($row['customer_id'], 'receivables', $filter_data['as_of_date'], $aging_periods);

            $receivables[] = array(
                'id' => $row['customer_id'],
                'name' => $row['customer_name'],
                'type' => 'receivable',
                'email' => $row['email'],
                'telephone' => $row['telephone'],
                'customer_group' => $row['customer_group'],
                'total_amount' => (float)$row['total_amount'],
                'total_amount_formatted' => $this->currency->format($row['total_amount'], $filter_data['currency']),
                'periods' => $periods,
                'days_outstanding' => (int)$row['days_outstanding'],
                'earliest_due_date' => $row['earliest_due_date'],
                'latest_due_date' => $row['latest_due_date'],
                'risk_level' => $this->calculateRiskLevel($row['days_outstanding'], $row['total_amount'])
            );
        }

        return $receivables;
    }

    /**
     * الحصول على أعمار الدائنين
     */
    private function getPayablesAging($filter_data, $aging_periods) {
        $sql = "
            SELECT
                s.supplier_id,
                s.name as supplier_name,
                s.email,
                s.telephone,
                SUM(ap.amount) as total_amount,
                MIN(ap.due_date) as earliest_due_date,
                MAX(ap.due_date) as latest_due_date,
                DATEDIFF('" . $this->db->escape($filter_data['as_of_date']) . "', MIN(ap.due_date)) as days_outstanding
            FROM " . DB_PREFIX . "accounts_payable ap
            JOIN " . DB_PREFIX . "supplier s ON ap.supplier_id = s.supplier_id
            WHERE ap.status = 'outstanding'
            AND ap.due_date <= '" . $this->db->escape($filter_data['as_of_date']) . "'
            GROUP BY s.supplier_id, s.name, s.email, s.telephone
        ";

        // فلترة الأرصدة الصفرية
        if (!$filter_data['include_zero_balances']) {
            $sql .= " HAVING SUM(ap.amount) > 0";
        }

        $query = $this->db->query($sql);

        $payables = array();
        foreach ($query->rows as $row) {
            $periods = $this->calculateAgingPeriods($row['supplier_id'], 'payables', $filter_data['as_of_date'], $aging_periods);

            $payables[] = array(
                'id' => $row['supplier_id'],
                'name' => $row['supplier_name'],
                'type' => 'payable',
                'email' => $row['email'],
                'telephone' => $row['telephone'],
                'total_amount' => (float)$row['total_amount'],
                'total_amount_formatted' => $this->currency->format($row['total_amount'], $filter_data['currency']),
                'periods' => $periods,
                'days_outstanding' => (int)$row['days_outstanding'],
                'earliest_due_date' => $row['earliest_due_date'],
                'latest_due_date' => $row['latest_due_date'],
                'priority_level' => $this->calculatePaymentPriority($row['days_outstanding'], $row['total_amount'])
            );
        }

        return $payables;
    }

    /**
     * حساب فترات الأعمار لعميل أو مورد معين
     */
    private function calculateAgingPeriods($entity_id, $type, $as_of_date, $aging_periods) {
        $table = $type == 'receivables' ? 'accounts_receivable' : 'accounts_payable';
        $id_field = $type == 'receivables' ? 'customer_id' : 'supplier_id';

        $periods = array();

        // تهيئة الفترات
        foreach ($aging_periods as $period) {
            $periods[$period['label']] = 0;
        }

        // الحصول على التفاصيل
        $sql = "
            SELECT amount, due_date,
                   DATEDIFF('" . $this->db->escape($as_of_date) . "', due_date) as days_overdue
            FROM " . DB_PREFIX . $table . "
            WHERE " . $id_field . " = '" . (int)$entity_id . "'
            AND status = 'outstanding'
            AND due_date <= '" . $this->db->escape($as_of_date) . "'
        ";

        $query = $this->db->query($sql);

        foreach ($query->rows as $row) {
            $days_overdue = (int)$row['days_overdue'];
            $amount = (float)$row['amount'];

            // تحديد الفترة المناسبة
            $period_found = false;
            foreach ($aging_periods as $period) {
                if ($days_overdue >= $period['min_days'] && $days_overdue <= $period['max_days']) {
                    $periods[$period['label']] += $amount;
                    $period_found = true;
                    break;
                }
            }

            // إذا لم توجد فترة مناسبة، أضف للفترة الأخيرة
            if (!$period_found && !empty($aging_periods)) {
                $last_period = end($aging_periods);
                $periods[$last_period['label']] += $amount;
            }
        }

        return $periods;
    }

    /**
     * تحليل فترات الأعمار من النص
     */
    private function parseAgingPeriods($periods_string) {
        $periods_array = explode(',', $periods_string);
        $aging_periods = array();

        $previous_max = -1;

        foreach ($periods_array as $index => $period) {
            $period = (int)trim($period);

            if ($index == 0) {
                // الفترة الأولى: الحالي (0 إلى الفترة الأولى)
                $aging_periods[] = array(
                    'label' => 'الحالي',
                    'min_days' => 0,
                    'max_days' => $period - 1
                );
                $previous_max = $period - 1;
            }

            // الفترة التالية
            if ($index == count($periods_array) - 1) {
                // الفترة الأخيرة: من الفترة السابقة إلى ما لا نهاية
                $aging_periods[] = array(
                    'label' => ($previous_max + 1) . '+ يوم',
                    'min_days' => $previous_max + 1,
                    'max_days' => 999999
                );
            } else {
                // فترة متوسطة
                $next_period = isset($periods_array[$index + 1]) ? (int)trim($periods_array[$index + 1]) : $period + 30;
                $aging_periods[] = array(
                    'label' => ($previous_max + 1) . '-' . ($next_period - 1) . ' يوم',
                    'min_days' => $previous_max + 1,
                    'max_days' => $next_period - 1
                );
                $previous_max = $next_period - 1;
            }
        }

        return $aging_periods;
    }

    /**
     * ترتيب بيانات الأعمار
     */
    private function sortAgingData($data, $sort_by) {
        switch ($sort_by) {
            case 'amount_desc':
                usort($data, function($a, $b) {
                    return $b['total_amount'] <=> $a['total_amount'];
                });
                break;
            case 'amount_asc':
                usort($data, function($a, $b) {
                    return $a['total_amount'] <=> $b['total_amount'];
                });
                break;
            case 'name_asc':
                usort($data, function($a, $b) {
                    return strcmp($a['name'], $b['name']);
                });
                break;
            case 'name_desc':
                usort($data, function($a, $b) {
                    return strcmp($b['name'], $a['name']);
                });
                break;
            case 'days_desc':
                usort($data, function($a, $b) {
                    return $b['days_outstanding'] <=> $a['days_outstanding'];
                });
                break;
            case 'days_asc':
                usort($data, function($a, $b) {
                    return $a['days_outstanding'] <=> $b['days_outstanding'];
                });
                break;
        }

        return $data;
    }

    /**
     * حساب ملخص الأعمار
     */
    private function calculateAgingSummary($details, $aging_periods) {
        $summary = array();

        // تهيئة الملخص
        $summary['periods'] = array();
        foreach ($aging_periods as $period) {
            $summary['periods'][$period['label']] = 0;
        }
        $summary['total'] = 0;
        $summary['count'] = count($details);

        // حساب الإجماليات
        foreach ($details as $detail) {
            $summary['total'] += $detail['total_amount'];

            foreach ($detail['periods'] as $period_label => $amount) {
                if (isset($summary['periods'][$period_label])) {
                    $summary['periods'][$period_label] += $amount;
                }
            }
        }

        // حساب النسب المئوية
        $summary['percentages'] = array();
        foreach ($summary['periods'] as $period_label => $amount) {
            $summary['percentages'][$period_label] = $summary['total'] > 0 ? ($amount / $summary['total']) * 100 : 0;
        }

        return $summary;
    }

    /**
     * حساب مستوى المخاطر
     */
    private function calculateRiskLevel($days_outstanding, $amount) {
        $risk_score = 0;

        // نقاط حسب الأيام
        if ($days_outstanding > 90) {
            $risk_score += 40;
        } elseif ($days_outstanding > 60) {
            $risk_score += 30;
        } elseif ($days_outstanding > 30) {
            $risk_score += 20;
        } else {
            $risk_score += 10;
        }

        // نقاط حسب المبلغ
        if ($amount > 100000) {
            $risk_score += 30;
        } elseif ($amount > 50000) {
            $risk_score += 20;
        } elseif ($amount > 10000) {
            $risk_score += 10;
        } else {
            $risk_score += 5;
        }

        // تحديد مستوى المخاطر
        if ($risk_score >= 60) {
            return 'high';
        } elseif ($risk_score >= 40) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * حساب أولوية الدفع
     */
    private function calculatePaymentPriority($days_outstanding, $amount) {
        $priority_score = 0;

        // نقاط حسب الأيام
        if ($days_outstanding > 90) {
            $priority_score += 50;
        } elseif ($days_outstanding > 60) {
            $priority_score += 40;
        } elseif ($days_outstanding > 30) {
            $priority_score += 30;
        } else {
            $priority_score += 20;
        }

        // نقاط حسب المبلغ
        if ($amount > 100000) {
            $priority_score += 30;
        } elseif ($amount > 50000) {
            $priority_score += 20;
        } elseif ($amount > 10000) {
            $priority_score += 10;
        }

        // تحديد أولوية الدفع
        if ($priority_score >= 70) {
            return 'urgent';
        } elseif ($priority_score >= 50) {
            return 'high';
        } elseif ($priority_score >= 30) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * تحليل تقرير الأعمار
     */
    public function analyzeAging($aging_data, $filter_data) {
        $analysis = array();

        // تحليل التوزيع
        $analysis['distribution'] = $this->analyzeAgingDistribution($aging_data);

        // تحليل المخاطر
        $analysis['risk_analysis'] = $this->analyzeAgingRisks($aging_data);

        // تحليل الأداء
        $analysis['performance'] = $this->analyzeCollectionPerformance($aging_data);

        // إحصائيات عامة
        $analysis['statistics'] = $this->calculateAgingStatistics($aging_data);

        // التوصيات
        $analysis['recommendations'] = $this->generateAgingRecommendations($aging_data);

        return $analysis;
    }

    /**
     * تحليل توزيع الأعمار
     */
    private function analyzeAgingDistribution($aging_data) {
        $distribution = array();
        $summary = $aging_data['summary'];

        // تحليل التركز
        $total = $summary['total'];
        $distribution['concentration'] = array();

        foreach ($summary['periods'] as $period => $amount) {
            $percentage = $total > 0 ? ($amount / $total) * 100 : 0;
            $distribution['concentration'][$period] = array(
                'amount' => $amount,
                'percentage' => $percentage,
                'risk_level' => $this->getPeriodRiskLevel($period)
            );
        }

        // تحديد الفترة المهيمنة
        $max_amount = 0;
        $dominant_period = '';
        foreach ($summary['periods'] as $period => $amount) {
            if ($amount > $max_amount) {
                $max_amount = $amount;
                $dominant_period = $period;
            }
        }

        $distribution['dominant_period'] = $dominant_period;
        $distribution['dominant_percentage'] = $total > 0 ? ($max_amount / $total) * 100 : 0;

        return $distribution;
    }

    /**
     * تحليل مخاطر الأعمار
     */
    private function analyzeAgingRisks($aging_data) {
        $risks = array();
        $details = $aging_data['details'];

        // تصنيف حسب مستوى المخاطر
        $risk_levels = array('high' => 0, 'medium' => 0, 'low' => 0);
        $risk_amounts = array('high' => 0, 'medium' => 0, 'low' => 0);

        foreach ($details as $detail) {
            if ($detail['type'] == 'receivable') {
                $risk_level = $detail['risk_level'];
                $risk_levels[$risk_level]++;
                $risk_amounts[$risk_level] += $detail['total_amount'];
            }
        }

        $risks['risk_distribution'] = array(
            'counts' => $risk_levels,
            'amounts' => $risk_amounts,
            'percentages' => array()
        );

        // حساب النسب المئوية
        $total_amount = array_sum($risk_amounts);
        foreach ($risk_amounts as $level => $amount) {
            $risks['risk_distribution']['percentages'][$level] = $total_amount > 0 ? ($amount / $total_amount) * 100 : 0;
        }

        // تحديد العملاء عالي المخاطر
        $high_risk_customers = array();
        foreach ($details as $detail) {
            if ($detail['type'] == 'receivable' && $detail['risk_level'] == 'high') {
                $high_risk_customers[] = array(
                    'name' => $detail['name'],
                    'amount' => $detail['total_amount'],
                    'days' => $detail['days_outstanding']
                );
            }
        }

        // ترتيب حسب المبلغ
        usort($high_risk_customers, function($a, $b) {
            return $b['amount'] <=> $a['amount'];
        });

        $risks['high_risk_customers'] = array_slice($high_risk_customers, 0, 10);

        return $risks;
    }

    /**
     * تحليل أداء التحصيل
     */
    private function analyzeCollectionPerformance($aging_data) {
        $performance = array();
        $summary = $aging_data['summary'];

        // حساب معدل التحصيل
        $current_amount = $summary['periods']['الحالي'] ?? 0;
        $total_amount = $summary['total'];

        $performance['collection_rate'] = $total_amount > 0 ? ($current_amount / $total_amount) * 100 : 0;

        // تقييم الأداء
        if ($performance['collection_rate'] >= 80) {
            $performance['rating'] = 'excellent';
        } elseif ($performance['collection_rate'] >= 60) {
            $performance['rating'] = 'good';
        } elseif ($performance['collection_rate'] >= 40) {
            $performance['rating'] = 'average';
        } else {
            $performance['rating'] = 'poor';
        }

        // حساب متوسط الأيام المتأخرة
        $total_days = 0;
        $count = 0;
        foreach ($aging_data['details'] as $detail) {
            if ($detail['type'] == 'receivable') {
                $total_days += $detail['days_outstanding'];
                $count++;
            }
        }

        $performance['average_days_outstanding'] = $count > 0 ? $total_days / $count : 0;

        return $performance;
    }

    /**
     * حساب إحصائيات الأعمار
     */
    private function calculateAgingStatistics($aging_data) {
        $statistics = array();
        $details = $aging_data['details'];

        // إحصائيات عامة
        $statistics['total_customers'] = count(array_filter($details, function($d) { return $d['type'] == 'receivable'; }));
        $statistics['total_suppliers'] = count(array_filter($details, function($d) { return $d['type'] == 'payable'; }));
        $statistics['total_amount'] = $aging_data['summary']['total'];

        // إحصائيات المبالغ
        $amounts = array_column($details, 'total_amount');
        if (!empty($amounts)) {
            $statistics['max_amount'] = max($amounts);
            $statistics['min_amount'] = min($amounts);
            $statistics['average_amount'] = array_sum($amounts) / count($amounts);
            $statistics['median_amount'] = $this->calculateMedian($amounts);
        }

        // إحصائيات الأيام
        $days = array_column($details, 'days_outstanding');
        if (!empty($days)) {
            $statistics['max_days'] = max($days);
            $statistics['min_days'] = min($days);
            $statistics['average_days'] = array_sum($days) / count($days);
            $statistics['median_days'] = $this->calculateMedian($days);
        }

        return $statistics;
    }

    /**
     * إنشاء توصيات الأعمار
     */
    private function generateAgingRecommendations($aging_data) {
        $recommendations = array();
        $summary = $aging_data['summary'];

        // توصيات التحصيل
        $overdue_amount = 0;
        foreach ($summary['periods'] as $period => $amount) {
            if ($period != 'الحالي') {
                $overdue_amount += $amount;
            }
        }

        $overdue_percentage = $summary['total'] > 0 ? ($overdue_amount / $summary['total']) * 100 : 0;

        if ($overdue_percentage > 50) {
            $recommendations[] = array(
                'category' => 'collection',
                'priority' => 'high',
                'recommendation' => 'تطبيق استراتيجية تحصيل عاجلة - أكثر من 50% من المبالغ متأخرة'
            );
        } elseif ($overdue_percentage > 30) {
            $recommendations[] = array(
                'category' => 'collection',
                'priority' => 'medium',
                'recommendation' => 'مراجعة سياسات التحصيل وتحسين المتابعة'
            );
        }

        // توصيات إدارة المخاطر
        $high_risk_count = 0;
        foreach ($aging_data['details'] as $detail) {
            if ($detail['type'] == 'receivable' && $detail['risk_level'] == 'high') {
                $high_risk_count++;
            }
        }

        if ($high_risk_count > 0) {
            $recommendations[] = array(
                'category' => 'risk_management',
                'priority' => 'high',
                'recommendation' => "مراجعة عاجلة لـ {$high_risk_count} عميل عالي المخاطر"
            );
        }

        // توصيات السياسات
        $current_percentage = $summary['total'] > 0 ? (($summary['periods']['الحالي'] ?? 0) / $summary['total']) * 100 : 0;

        if ($current_percentage < 60) {
            $recommendations[] = array(
                'category' => 'policies',
                'priority' => 'medium',
                'recommendation' => 'مراجعة شروط الدفع وسياسات الائتمان'
            );
        }

        return $recommendations;
    }

    /**
     * حساب أولوية التحصيل
     */
    public function calculateCollectionPriority($aging_data) {
        $priority_list = array();

        foreach ($aging_data['details'] as $detail) {
            if ($detail['type'] == 'receivable') {
                $priority_score = 0;

                // نقاط حسب المبلغ (40%)
                if ($detail['total_amount'] > 100000) {
                    $priority_score += 40;
                } elseif ($detail['total_amount'] > 50000) {
                    $priority_score += 30;
                } elseif ($detail['total_amount'] > 10000) {
                    $priority_score += 20;
                } else {
                    $priority_score += 10;
                }

                // نقاط حسب الأيام (40%)
                if ($detail['days_outstanding'] > 90) {
                    $priority_score += 40;
                } elseif ($detail['days_outstanding'] > 60) {
                    $priority_score += 30;
                } elseif ($detail['days_outstanding'] > 30) {
                    $priority_score += 20;
                } else {
                    $priority_score += 10;
                }

                // نقاط حسب مستوى المخاطر (20%)
                switch ($detail['risk_level']) {
                    case 'high':
                        $priority_score += 20;
                        break;
                    case 'medium':
                        $priority_score += 15;
                        break;
                    case 'low':
                        $priority_score += 10;
                        break;
                }

                $priority_list[] = array(
                    'customer_id' => $detail['id'],
                    'customer_name' => $detail['name'],
                    'amount' => $detail['total_amount'],
                    'days_outstanding' => $detail['days_outstanding'],
                    'risk_level' => $detail['risk_level'],
                    'priority_score' => $priority_score,
                    'priority_level' => $this->getPriorityLevel($priority_score)
                );
            }
        }

        // ترتيب حسب نقاط الأولوية
        usort($priority_list, function($a, $b) {
            return $b['priority_score'] <=> $a['priority_score'];
        });

        return $priority_list;
    }

    /**
     * تقييم مخاطر التحصيل
     */
    public function assessCollectionRisk($aging_data, $filter_data) {
        $risk_assessment = array();

        // تحليل المخاطر العامة
        $total_amount = $aging_data['summary']['total'];
        $overdue_amount = 0;

        foreach ($aging_data['summary']['periods'] as $period => $amount) {
            if ($period != 'الحالي') {
                $overdue_amount += $amount;
            }
        }

        $overdue_percentage = $total_amount > 0 ? ($overdue_amount / $total_amount) * 100 : 0;

        // تقييم المخاطر العامة
        if ($overdue_percentage > 60) {
            $risk_assessment['overall_risk'] = 'very_high';
        } elseif ($overdue_percentage > 40) {
            $risk_assessment['overall_risk'] = 'high';
        } elseif ($overdue_percentage > 20) {
            $risk_assessment['overall_risk'] = 'medium';
        } else {
            $risk_assessment['overall_risk'] = 'low';
        }

        $risk_assessment['overdue_percentage'] = $overdue_percentage;
        $risk_assessment['overdue_amount'] = $overdue_amount;

        // تحليل المخاطر حسب الفترات
        $risk_assessment['period_risks'] = array();
        foreach ($aging_data['summary']['periods'] as $period => $amount) {
            $percentage = $total_amount > 0 ? ($amount / $total_amount) * 100 : 0;
            $risk_level = $this->getPeriodRiskLevel($period);

            $risk_assessment['period_risks'][$period] = array(
                'amount' => $amount,
                'percentage' => $percentage,
                'risk_level' => $risk_level
            );
        }

        // العملاء عالي المخاطر
        $high_risk_customers = array();
        foreach ($aging_data['details'] as $detail) {
            if ($detail['type'] == 'receivable' && $detail['risk_level'] == 'high') {
                $high_risk_customers[] = $detail;
            }
        }

        $risk_assessment['high_risk_customers_count'] = count($high_risk_customers);
        $risk_assessment['high_risk_amount'] = array_sum(array_column($high_risk_customers, 'total_amount'));

        return $risk_assessment;
    }

    /**
     * تحليل اتجاهات الأعمار
     */
    public function analyzeAgingTrends($filter_data) {
        $trends = array();

        // مقارنة مع نفس الفترة الشهر الماضي
        $current_date = $filter_data['as_of_date'];
        $previous_month_date = date('Y-m-d', strtotime($current_date . ' -1 month'));

        $current_filter = $filter_data;
        $previous_filter = $filter_data;
        $previous_filter['as_of_date'] = $previous_month_date;

        $current_aging = $this->generateAgingReport($current_filter);
        $previous_aging = $this->generateAgingReport($previous_filter);

        // مقارنة الإجماليات
        $current_total = $current_aging['summary']['total'];
        $previous_total = $previous_aging['summary']['total'];

        $total_change = $current_total - $previous_total;
        $total_change_percentage = $previous_total > 0 ? ($total_change / $previous_total) * 100 : 0;

        $trends['total_change'] = array(
            'amount' => $total_change,
            'percentage' => $total_change_percentage,
            'direction' => $total_change > 0 ? 'increasing' : ($total_change < 0 ? 'decreasing' : 'stable')
        );

        // مقارنة الفترات
        $trends['period_changes'] = array();
        foreach ($current_aging['summary']['periods'] as $period => $current_amount) {
            $previous_amount = $previous_aging['summary']['periods'][$period] ?? 0;
            $change = $current_amount - $previous_amount;
            $change_percentage = $previous_amount > 0 ? ($change / $previous_amount) * 100 : 0;

            $trends['period_changes'][$period] = array(
                'current_amount' => $current_amount,
                'previous_amount' => $previous_amount,
                'change' => $change,
                'change_percentage' => $change_percentage,
                'direction' => $change > 0 ? 'increasing' : ($change < 0 ? 'decreasing' : 'stable')
            );
        }

        // تحليل الاتجاه العام
        $overdue_periods = array_slice($current_aging['summary']['periods'], 1); // كل الفترات عدا الحالي
        $current_overdue = array_sum($overdue_periods);

        $previous_overdue_periods = array_slice($previous_aging['summary']['periods'], 1);
        $previous_overdue = array_sum($previous_overdue_periods);

        $overdue_change = $current_overdue - $previous_overdue;
        $overdue_change_percentage = $previous_overdue > 0 ? ($overdue_change / $previous_overdue) * 100 : 0;

        $trends['overdue_trend'] = array(
            'current_overdue' => $current_overdue,
            'previous_overdue' => $previous_overdue,
            'change' => $overdue_change,
            'change_percentage' => $overdue_change_percentage,
            'direction' => $overdue_change > 0 ? 'worsening' : ($overdue_change < 0 ? 'improving' : 'stable')
        );

        return $trends;
    }

    /**
     * الحصول على تفاصيل عميل معين
     */
    public function getCustomerAgingDetails($customer_id, $filter_data) {
        $sql = "
            SELECT
                ar.invoice_number,
                ar.invoice_date,
                ar.due_date,
                ar.amount,
                ar.status,
                DATEDIFF('" . $this->db->escape($filter_data['as_of_date']) . "', ar.due_date) as days_overdue
            FROM " . DB_PREFIX . "accounts_receivable ar
            WHERE ar.customer_id = '" . (int)$customer_id . "'
            AND ar.status = 'outstanding'
            AND ar.due_date <= '" . $this->db->escape($filter_data['as_of_date']) . "'
            ORDER BY ar.due_date ASC
        ";

        $query = $this->db->query($sql);

        $details = array();
        foreach ($query->rows as $row) {
            $days_overdue = (int)$row['days_overdue'];

            $details[] = array(
                'invoice_number' => $row['invoice_number'],
                'invoice_date' => $row['invoice_date'],
                'due_date' => $row['due_date'],
                'amount' => (float)$row['amount'],
                'amount_formatted' => $this->currency->format($row['amount'], $filter_data['currency']),
                'days_overdue' => $days_overdue,
                'status' => $row['status'],
                'risk_level' => $this->calculateRiskLevel($days_overdue, $row['amount'])
            );
        }

        return $details;
    }

    /**
     * الحصول على مستوى مخاطر الفترة
     */
    private function getPeriodRiskLevel($period) {
        if (strpos($period, 'الحالي') !== false) {
            return 'low';
        } elseif (strpos($period, '90+') !== false || strpos($period, '120+') !== false) {
            return 'very_high';
        } elseif (strpos($period, '60') !== false || strpos($period, '90') !== false) {
            return 'high';
        } elseif (strpos($period, '30') !== false) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * الحصول على مستوى الأولوية
     */
    private function getPriorityLevel($priority_score) {
        if ($priority_score >= 80) {
            return 'critical';
        } elseif ($priority_score >= 60) {
            return 'high';
        } elseif ($priority_score >= 40) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * حساب الوسيط
     */
    private function calculateMedian($array) {
        sort($array);
        $count = count($array);

        if ($count == 0) {
            return 0;
        }

        if ($count % 2 == 0) {
            return ($array[$count / 2 - 1] + $array[$count / 2]) / 2;
        } else {
            return $array[floor($count / 2)];
        }
    }

    /**
     * الحصول على ملخص تقرير الأعمار
     */
    public function getAgingReportSummary($filter_data) {
        $aging_report = $this->generateAgingReport($filter_data);

        return array(
            'total_amount' => $aging_report['summary']['total'],
            'total_count' => $aging_report['summary']['count'],
            'periods_summary' => $aging_report['summary']['periods'],
            'as_of_date' => $filter_data['as_of_date'],
            'report_type' => $filter_data['report_type'],
            'generated_at' => date('Y-m-d H:i:s')
        );
    }
}