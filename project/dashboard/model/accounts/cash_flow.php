<?php
class ModelAccountsCashFlow extends Model {
    /**
     * جلب القيود النقدية خلال الفترة
     * سنفترض أننا نستخدم الطريقة المباشرة: أي نستخرج مباشرةً المقبوض والمدفوع الفعلي
     */
    public function getCashFlowData($date_start, $date_end) {
        // 1) حدد الحسابات النقدية (cash & bank) من الدليل
        //    هنا افتراض: 1281, 1282, 1283
        $cashAccounts = ['1281','1282','1283'];  // يمكنك سحبها ديناميكيا من الـ DB

        // 2) إحضار القيود من journal_entries التي أثرت في هذه الحسابات خلال الفترة
        //    نجلب الطرف المقابل (account_code_other) ونحدد هل الحركة مدينة أو دائنة
        //    هذا يتطلب أن تكون لديك آلية لحفظ الطرف/الأطراف الأخرى للقيد الواحد
        //    إذا كان لديك جدول detailed journal entries يجب ربطه بحيث:
        //      - إن كان je.account_code in $cashAccounts => الطرف المقابل يحصل عليه
        //    (هذه تفاصيل بنية الـ DB وقد تختلف حسب تصميمك)

        // مثال SQL تقريبي:
        $query = $this->db->query("
            SELECT 
               je.journal_id,
               je.account_code, 
               je.is_debit, 
               je.amount,
               j.thedate,
               j.is_cancelled,
               a2.account_code AS account_code_other,
               ad2.name AS name_other
            FROM " . DB_PREFIX . "journal_entries je
            LEFT JOIN " . DB_PREFIX . "journals j ON (je.journal_id = j.journal_id)
            LEFT JOIN " . DB_PREFIX . "journal_entries je2 ON (je2.journal_id = j.journal_id AND je2.account_code != je.account_code)
            LEFT JOIN " . DB_PREFIX . "accounts a2 ON (a2.account_code = je2.account_code)
            LEFT JOIN " . DB_PREFIX . "account_description ad2 ON (ad2.account_id = a2.account_id 
                  AND ad2.language_id = '" . (int)$this->config->get('config_language_id') . "')
            WHERE je.account_code IN ('1281','1282','1283') 
              AND j.thedate BETWEEN '" . $this->db->escape($date_start) . "' 
                                AND '" . $this->db->escape($date_end) . "'
              AND j.is_cancelled = 0
        ");

        $rawRows = $query->rows;

        // 3) صنف الحركة حسب النشاط (تشغيلي/استثماري/تمويلي) بناءً على "account_code_other"
        //    وسنستخدم خريطة:
        //    مثلاً:
        $operating = [];
        $investing = [];
        $financing = [];
        // لجمع المبالغ
        $totalOp = 0;
        $totalInv = 0;
        $totalFin = 0;

        foreach ($rawRows as $r) {
            $amount = (float)$r['amount'];
            // إن كان je.is_debit=1 => النقدية زادت
            // إن كان je.is_debit=0 => النقدية نقصت
            // لتحويلها إلى موجب/سالب:
            if (!$r['is_debit']) {
                $amount = -$amount;
            }

            $otherCode = $r['account_code_other'] ?: '';
            $otherName = $r['name_other'] ?: '...';

            // صنف حسب خريطة دليل الحسابات
            $activityType = $this->getActivityType($otherCode); 
            // getActivityType => دالة تحدد إن كان تشغيلي/استثماري/تمويلي

            switch ($activityType) {
                case 'operating':
                    $operating[] = [
                        'date' => $r['thedate'],
                        'other_code' => $otherCode,
                        'other_name' => $otherName,
                        'amount' => $amount
                    ];
                    $totalOp += $amount;
                    break;
                case 'investing':
                    $investing[] = [
                        'date' => $r['thedate'],
                        'other_code' => $otherCode,
                        'other_name' => $otherName,
                        'amount' => $amount
                    ];
                    $totalInv += $amount;
                    break;
                case 'financing':
                    $financing[] = [
                        'date' => $r['thedate'],
                        'other_code' => $otherCode,
                        'other_name' => $otherName,
                        'amount' => $amount
                    ];
                    $totalFin += $amount;
                    break;
                default:
                    // لو عندك حالة أخرى
                    break;
            }
        }

        // مجموع
        $netChange = $totalOp + $totalInv + $totalFin;

        return [
            'operating' => $operating,
            'investing' => $investing,
            'financing' => $financing,
            'total_operating' => $totalOp,
            'total_investing' => $totalInv,
            'total_financing' => $totalFin,
            'net_change' => $netChange
        ];
    }


    /**
     *  تحديد نوع النشاط بناءً على account_code من الدليل المرفق
     */
    private function getActivityType($accountCode) {
        // تشغيلي:
        //   - الإيرادات (5...) لكن لاحظ في دليلك أحيانًا 5 هي حساب رئيسي والدخل التفصيلي 51,52,54...
        //   - التكاليف والمصروفات (4...)  
        //   - المخزون (122...)، العملاء (123...), الموردين (321...) => في الواقع تُصنَّف تشغيلي.
        // استثماري:
        //   - الأصول الثابتة (111...), الاستثمارات غير المتداولة (114...), إلخ
        // تمويلي:
        //   - رأس المال (21..), الاحتياطيات (23..), القروض (311, 326..)
        // هذه مجرد أمثلة، تحتاج ضبط أدق.

        // مثال بسيط:
        if (preg_match('/^(4|5|12|123|321)/', $accountCode)) {
            return 'operating';
        } elseif (preg_match('/^(111|114)/', $accountCode)) {
            return 'investing';
        } elseif (preg_match('/^(21|23|24|25|31|32|3)/', $accountCode)) {
            return 'financing';
        }
        return 'operating'; // افتراضي
    }

    /**
     * إحضار رصيد النقدية أول الفترة
     */
    public function getOpeningCashBalance($date_start) {
        // نجلب أرصدة الحسابات النقدية حتى اليوم السابق للـ $date_start
        // الحسابات النقدية: 1281,1282,1283
        $cashAccounts = ['1281','1282','1283'];

        // نفترض عندك جدول balances أو طريقة لجلب الرصيد التراكمي
        // أو يمكنك حسابه من اليومية من بداية النظام حتى اليوم السابق
        // هنا نموذج مبسط:
        $sql = "
          SELECT COALESCE(SUM(CASE WHEN je.is_debit=1 THEN je.amount ELSE -je.amount END),0) as balance
          FROM " . DB_PREFIX . "journal_entries je
          LEFT JOIN " . DB_PREFIX . "journals j ON (je.journal_id = j.journal_id)
          WHERE je.account_code IN ('1281','1282','1283')
            AND j.thedate < '" . $this->db->escape($date_start) . "'
            AND j.is_cancelled=0
        ";
        $q = $this->db->query($sql);
        return (float)$q->row['balance'];
    }
}
