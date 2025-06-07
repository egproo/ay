<?php
class ModelPurchasePurchaseAnalytics extends Model {
	
	/**
	 * الحصول على الإنفاق حسب الفئة
	 * @param string $date_start تاريخ البداية
	 * @param string $date_end تاريخ النهاية
	 * @return array
	 */
	public function getSpendingByCategory($date_start, $date_end) {
		$sql = "SELECT c.name, 
				SUM(poi.total) AS amount, 
				ROUND((SUM(poi.total) / (SELECT SUM(total) FROM " . DB_PREFIX . "purchase_order_item WHERE purchase_order_id IN (
					SELECT po.purchase_order_id FROM " . DB_PREFIX . "purchase_order po 
					WHERE po.date_added BETWEEN '" . $this->db->escape($date_start) . " 00:00:00' AND '" . $this->db->escape($date_end) . " 23:59:59'
				))) * 100, 2) AS percentage
			FROM " . DB_PREFIX . "purchase_order_item poi
			LEFT JOIN " . DB_PREFIX . "purchase_order po ON (poi.purchase_order_id = po.purchase_order_id)
			LEFT JOIN " . DB_PREFIX . "product p ON (poi.product_id = p.product_id)
			LEFT JOIN " . DB_PREFIX . "category_description c ON (p.category_id = c.category_id)
			WHERE po.date_added BETWEEN '" . $this->db->escape($date_start) . " 00:00:00' AND '" . $this->db->escape($date_end) . " 23:59:59'
			GROUP BY c.name
			ORDER BY amount DESC";
			
		$query = $this->db->query($sql);
		
		return $query->rows;
	}
	
	/**
	 * الحصول على اتجاه الإنفاق
	 * @param string $date_start تاريخ البداية
	 * @param string $date_end تاريخ النهاية
	 * @return array
	 */
	public function getSpendingTrend($date_start, $date_end) {
		// Determine if we should group by day, week, or month based on date range
		$date_diff = strtotime($date_end) - strtotime($date_start);
		$days = round($date_diff / (60 * 60 * 24));
		
		if ($days <= 31) {
			// Group by day for date ranges up to 31 days
			$group_by = "DATE(po.date_added)";
			$date_format = "DATE_FORMAT(po.date_added, '%Y-%m-%d')";
		} elseif ($days <= 90) {
			// Group by week for date ranges up to 90 days
			$group_by = "YEARWEEK(po.date_added)";
			$date_format = "DATE_FORMAT(po.date_added, '%Y-%u')"; // Year and week number
		} else {
			// Group by month for longer date ranges
			$group_by = "DATE_FORMAT(po.date_added, '%Y-%m')";
			$date_format = "DATE_FORMAT(po.date_added, '%Y-%m')";
		}
		
		$sql = "SELECT " . $date_format . " AS date_period, 
				   SUM(po.total) AS amount
			FROM " . DB_PREFIX . "purchase_order po
			WHERE po.date_added BETWEEN '" . $this->db->escape($date_start) . " 00:00:00' AND '" . $this->db->escape($date_end) . " 23:59:59'
			GROUP BY " . $group_by . "
			ORDER BY date_period ASC";
			
		$query = $this->db->query($sql);
		
		return $query->rows;
	}
	
	/**
	 * الحصول على أفضل الموردين
	 * @param string $date_start تاريخ البداية
	 * @param string $date_end تاريخ النهاية
	 * @return array
	 */
	public function getTopSuppliers($date_start, $date_end) {
		$sql = "SELECT s.name, 
				   COUNT(po.purchase_order_id) AS orders, 
				   SUM(po.total) AS amount
			FROM " . DB_PREFIX . "purchase_order po
			LEFT JOIN " . DB_PREFIX . "supplier s ON (po.supplier_id = s.supplier_id)
			WHERE po.date_added BETWEEN '" . $this->db->escape($date_start) . " 00:00:00' AND '" . $this->db->escape($date_end) . " 23:59:59'
			GROUP BY s.supplier_id
			ORDER BY amount DESC
			LIMIT 10";
			
		$query = $this->db->query($sql);
		
		return $query->rows;
	}
	
	/**
	 * الحصول على أداء الموردين
	 * @param string $date_start تاريخ البداية
	 * @param string $date_end تاريخ النهاية
	 * @return array
	 */
	public function getSupplierPerformance($date_start, $date_end) {
		$sql = "SELECT s.name, 
				   AVG(DATEDIFF(gr.date_added, po.date_added)) AS lead_time,
				   (SELECT COUNT(*) FROM " . DB_PREFIX . "goods_receipt_item gri 
					LEFT JOIN " . DB_PREFIX . "goods_receipt gr2 ON (gri.receipt_id = gr2.receipt_id)
					WHERE gr2.supplier_id = s.supplier_id AND gri.quality_status = 'passed'
					AND gr2.date_added BETWEEN '" . $this->db->escape($date_start) . " 00:00:00' AND '" . $this->db->escape($date_end) . " 23:59:59') AS quality_passed,
				   (SELECT COUNT(*) FROM " . DB_PREFIX . "goods_receipt_item gri 
					LEFT JOIN " . DB_PREFIX . "goods_receipt gr2 ON (gri.receipt_id = gr2.receipt_id)
					WHERE gr2.supplier_id = s.supplier_id 
					AND gr2.date_added BETWEEN '" . $this->db->escape($date_start) . " 00:00:00' AND '" . $this->db->escape($date_end) . " 23:59:59') AS total_items,
				   CASE WHEN DATE_ADD(po.expected_date, INTERVAL 1 DAY) >= gr.date_added THEN 1 ELSE 0 END AS on_time_delivery
			FROM " . DB_PREFIX . "purchase_order po
			LEFT JOIN " . DB_PREFIX . "supplier s ON (po.supplier_id = s.supplier_id)
			LEFT JOIN " . DB_PREFIX . "goods_receipt gr ON (po.purchase_order_id = gr.purchase_order_id)
			WHERE po.date_added BETWEEN '" . $this->db->escape($date_start) . " 00:00:00' AND '" . $this->db->escape($date_end) . " 23:59:59'
			AND gr.date_added IS NOT NULL
			GROUP BY s.supplier_id
			ORDER BY lead_time ASC";
			
		$query = $this->db->query($sql);
		
		$results = array();
		
		foreach ($query->rows as $row) {
			$quality_rate = ($row['total_items'] > 0) ? ($row['quality_passed'] / $row['total_items'] * 100) : 0;
			
			$results[] = array(
				'name' => $row['name'],
				'lead_time' => round($row['lead_time'], 1),
				'quality_rate' => round($quality_rate, 2),
				'on_time_rate' => round($row['on_time_delivery'] * 100, 2)
			);
		}
		
		return $results;
	}
	
	/**
	 * الحصول على تفصيل حالة أوامر الشراء
	 * @param string $date_start تاريخ البداية
	 * @param string $date_end تاريخ النهاية
	 * @return array
	 */
	public function getPOStatusBreakdown($date_start, $date_end) {
		$sql = "SELECT po.status, COUNT(po.purchase_order_id) AS total
			FROM " . DB_PREFIX . "purchase_order po
			WHERE po.date_added BETWEEN '" . $this->db->escape($date_start) . " 00:00:00' AND '" . $this->db->escape($date_end) . " 23:59:59'
			GROUP BY po.status";
			
		$query = $this->db->query($sql);
		
		return $query->rows;
	}
	
	/**
	 * الحصول على متوسط فترة التوريد
	 * @param string $date_start تاريخ البداية
	 * @param string $date_end تاريخ النهاية
	 * @return array
	 */
	public function getAverageLeadTime($date_start, $date_end) {
		$sql = "SELECT AVG(DATEDIFF(gr.date_added, po.date_added)) AS avg_lead_time
			FROM " . DB_PREFIX . "purchase_order po
			LEFT JOIN " . DB_PREFIX . "goods_receipt gr ON (po.purchase_order_id = gr.purchase_order_id)
			WHERE po.date_added BETWEEN '" . $this->db->escape($date_start) . " 00:00:00' AND '" . $this->db->escape($date_end) . " 23:59:59'
			AND gr.date_added IS NOT NULL";
			
		$query = $this->db->query($sql);
		
		return array(
			'overall' => round($query->row['avg_lead_time'], 1),
			'by_supplier' => $this->getLeadTimeBySupplier($date_start, $date_end)
		);
	}
	
	/**
	 * الحصول على فترة التوريد حسب المورد
	 * @param string $date_start تاريخ البداية
	 * @param string $date_end تاريخ النهاية
	 * @return array
	 */
	private function getLeadTimeBySupplier($date_start, $date_end) {
		$sql = "SELECT s.name, AVG(DATEDIFF(gr.date_added, po.date_added)) AS avg_lead_time
			FROM " . DB_PREFIX . "purchase_order po
			LEFT JOIN " . DB_PREFIX . "supplier s ON (po.supplier_id = s.supplier_id)
			LEFT JOIN " . DB_PREFIX . "goods_receipt gr ON (po.purchase_order_id = gr.purchase_order_id)
			WHERE po.date_added BETWEEN '" . $this->db->escape($date_start) . " 00:00:00' AND '" . $this->db->escape($date_end) . " 23:59:59'
			AND gr.date_added IS NOT NULL
			GROUP BY s.supplier_id
			ORDER BY avg_lead_time ASC";
			
		$query = $this->db->query($sql);
		
		$results = array();
		
		foreach ($query->rows as $row) {
			$results[] = array(
				'name' => $row['name'],
				'lead_time' => round($row['avg_lead_time'], 1)
			);
		}
		
		return $results;
	}
	
	/**
	 * الحصول على إحصائيات المطابقة للفواتير
	 * @param string $date_start تاريخ البداية
	 * @param string $date_end تاريخ النهاية
	 * @return array
	 */
	public function getInvoiceMatchingStats($date_start, $date_end) {
		$sql = "SELECT matching_status, COUNT(supplier_invoice_id) AS total
			FROM " . DB_PREFIX . "supplier_invoice
			WHERE date_added BETWEEN '" . $this->db->escape($date_start) . " 00:00:00' AND '" . $this->db->escape($date_end) . " 23:59:59'
			GROUP BY matching_status";
			
		$query = $this->db->query($sql);
		
		$results = array(
			'MATCH_FULL' => 0,
			'MATCH_PARTIAL' => 0,
			'MATCH_NONE' => 0
		);
		
		foreach ($query->rows as $row) {
			$results[$row['matching_status']] = (int)$row['total'];
		}
		
		return $results;
	}
	
	/**
	 * الحصول على تنبيهات الاختلاف في الأسعار
	 * @param string $date_start تاريخ البداية
	 * @param string $date_end تاريخ النهاية
	 * @return array
	 */
	public function getPriceVarianceAlerts($date_start, $date_end) {
		$sql = "SELECT si.invoice_number, po.order_number, p.name as product_name, 
				   poi.price as po_price, sii.price as invoice_price,
				   (sii.price - poi.price) as price_diff,
				   ROUND(((sii.price - poi.price) / poi.price * 100), 2) as percentage_diff,
				   s.name as supplier_name
			FROM " . DB_PREFIX . "supplier_invoice_item sii
			LEFT JOIN " . DB_PREFIX . "supplier_invoice si ON (sii.invoice_id = si.supplier_invoice_id)
			LEFT JOIN " . DB_PREFIX . "purchase_order po ON (si.purchase_order_id = po.purchase_order_id)
			LEFT JOIN " . DB_PREFIX . "purchase_order_item poi ON (poi.purchase_order_id = po.purchase_order_id AND poi.product_id = sii.product_id)
			LEFT JOIN " . DB_PREFIX . "product p ON (sii.product_id = p.product_id)
			LEFT JOIN " . DB_PREFIX . "supplier s ON (po.supplier_id = s.supplier_id)
			WHERE si.date_added BETWEEN '" . $this->db->escape($date_start) . " 00:00:00' AND '" . $this->db->escape($date_end) . " 23:59:59'
			AND ABS(((sii.price - poi.price) / poi.price * 100)) > 5
			ORDER BY percentage_diff DESC
			LIMIT 20";
			
		$query = $this->db->query($sql);
		
		return $query->rows;
	}
} 