<?php
class ModelAccountsFixedAssetsReport extends Model {
    public function getFixedAssetsReportData($date_start, $date_end) {
        $language_id = (int)$this->config->get('config_language_id');
        $currency_code = $this->config->get('config_currency');

        // جلب الأصول من الجدول
        $sql = "SELECT asset_id, asset_code, name, purchase_date, purchase_value, current_value, depreciation_method, useful_life, salvage_value 
                FROM " . DB_PREFIX . "fixed_assets
                WHERE status = 'active' 
                ORDER BY asset_code ASC";

        $query = $this->db->query($sql);
        $assets = $query->rows;

        $results = [];
        $total_depreciation = 0.0;

        $start_ts = strtotime($date_start);
        $end_ts = strtotime($date_end);
        $days_in_period = ($end_ts - $start_ts)/(60*60*24) + 1; // +1 لتضمين آخر يوم مثلاً

        foreach ($assets as $a) {
            $purchase_value = (float)$a['purchase_value'];
            $salvage_value = (float)$a['salvage_value'];
            $useful_life = (int)$a['useful_life'];
            $method = $a['depreciation_method']; // نفترض "straight_line"
            
            // نفترض الاهلاك يومي على القسط الثابت
            // إهلاك يومي = (purchase_value - salvage_value) / (useful_life * 365)
            $depreciable_amount = max(0, $purchase_value - $salvage_value);
            $daily_depreciation = $useful_life > 0 ? ($depreciable_amount / ($useful_life * 365)) : 0;
            $period_depreciation = $daily_depreciation * $days_in_period;
            
            $total_depreciation += $period_depreciation;

            // القيمة الدفترية الحالية بعد الفترة = current_value - period_depreciation (تقريباً)
            $new_current_value = (float)$a['current_value'] - $period_depreciation;
            if ($new_current_value < 0) {
                $new_current_value = 0;
            }

            $results[] = [
                'asset_code' => $a['asset_code'],
                'name' => $a['name'],
                'purchase_date' => $a['purchase_date'],
                'purchase_value' => $this->currency->format($purchase_value, $currency_code),
                'current_value' => $this->currency->format((float)$a['current_value'], $currency_code),
                'method' => $method,
                'useful_life' => $useful_life,
                'salvage_value' => $this->currency->format($salvage_value, $currency_code),
                'period_depreciation' => $this->currency->format($period_depreciation, $currency_code),
                'new_current_value' => $this->currency->format($new_current_value, $currency_code)
            ];
        }

        return [
            'assets' => $results,
            'total_depreciation' => $this->currency->format($total_depreciation, $currency_code)
        ];
    }
}
