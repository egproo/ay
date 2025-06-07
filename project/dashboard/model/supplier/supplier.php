<?php
class ModelSupplierSupplier extends Model {
	public function addsupplier($data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "supplier SET supplier_group_id = '" . (int)$data['supplier_group_id'] . "', firstname = '" . $this->db->escape($data['firstname']) . "', lastname = '" . $this->db->escape($data['lastname']) . "', email = '" . $this->db->escape($data['email']) . "', telephone = '" . $this->db->escape($data['telephone']) . "', custom_field = '" . $this->db->escape(isset($data['custom_field']) ? json_encode($data['custom_field']) : json_encode(array())) . "', newsletter = '" . (int)$data['newsletter'] . "', salt = '" . $this->db->escape($salt = token(9)) . "', password = '" . $this->db->escape(sha1($salt . sha1($salt . sha1($data['password'])))) . "', status = '" . (int)$data['status'] . "', safe = '" . (int)$data['safe'] . "', date_added = NOW()");

		$supplier_id = $this->db->getLastId();

		if (isset($data['address'])) {
			foreach ($data['address'] as $address) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "supplier_address SET supplier_id = '" . (int)$supplier_id . "', firstname = '" . $this->db->escape($address['firstname']) . "', lastname = '" . $this->db->escape($address['lastname']) . "', company = '" . $this->db->escape($address['company']) . "', address_1 = '" . $this->db->escape($address['address_1']) . "', address_2 = '" . $this->db->escape($address['address_2']) . "', city = '" . $this->db->escape($address['city']) . "', postcode = '" . $this->db->escape($address['postcode']) . "', country_id = '" . (int)$address['country_id'] . "', zone_id = '" . (int)$address['zone_id'] . "', custom_field = '" . $this->db->escape(isset($address['custom_field']) ? json_encode($address['custom_field']) : json_encode(array())) . "'");

				if (isset($address['default'])) {
					$address_id = $this->db->getLastId();

					$this->db->query("UPDATE " . DB_PREFIX . "supplier SET address_id = '" . (int)$address_id . "' WHERE supplier_id = '" . (int)$supplier_id . "'");
				}
			}
		}

		return $supplier_id;
	}
   public function getTotalsuppliersByTelephone($telephone) {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "supplier WHERE telephone = '" . $this->db->escape($telephone) . "'");
        return $query->row['total'];
    }
public function getsupplierByEmailOrPhone($identifier) {
    // التحقق مما إذا كان المدخل هو بريد إلكتروني أو رقم هاتف
    if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "supplier WHERE LOWER(email) = '" . $this->db->escape(utf8_strtolower($identifier)) . "'");
    } else {
        // افتراض أن رقم الهاتف يخزن بدون كود الدولة وأنه يتم تخزين آخر 10 أرقام فقط
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "supplier WHERE RIGHT(telephone, 10) = '" . $this->db->escape(substr($identifier, -10)) . "'");
    }

    return $query->row;
}    
	public function editsupplier($supplier_id, $data) {
		$this->db->query("UPDATE " . DB_PREFIX . "supplier SET supplier_group_id = '" . (int)$data['supplier_group_id'] . "', firstname = '" . $this->db->escape($data['firstname']) . "', lastname = '" . $this->db->escape($data['lastname']) . "', email = '" . $this->db->escape($data['email']) . "', telephone = '" . $this->db->escape($data['telephone']) . "', custom_field = '" . $this->db->escape(isset($data['custom_field']) ? json_encode($data['custom_field']) : json_encode(array())) . "', newsletter = '" . (int)$data['newsletter'] . "', status = '" . (int)$data['status'] . "', safe = '" . (int)$data['safe'] . "' WHERE supplier_id = '" . (int)$supplier_id . "'");

		if ($data['password']) {
			$this->db->query("UPDATE " . DB_PREFIX . "supplier SET salt = '" . $this->db->escape($salt = token(9)) . "', password = '" . $this->db->escape(sha1($salt . sha1($salt . sha1($data['password'])))) . "' WHERE supplier_id = '" . (int)$supplier_id . "'");
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "supplier_address WHERE supplier_id = '" . (int)$supplier_id . "'");

		if (isset($data['address'])) {
			foreach ($data['address'] as $address) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "supplier_address SET address_id = '" . (int)$address['address_id'] . "', supplier_id = '" . (int)$supplier_id . "', firstname = '" . $this->db->escape($address['firstname']) . "', lastname = '" . $this->db->escape($address['lastname']) . "', company = '" . $this->db->escape($address['company']) . "', address_1 = '" . $this->db->escape($address['address_1']) . "', address_2 = '" . $this->db->escape($address['address_2']) . "', city = '" . $this->db->escape($address['city']) . "', postcode = '" . $this->db->escape($address['postcode']) . "', country_id = '" . (int)$address['country_id'] . "', zone_id = '" . (int)$address['zone_id'] . "', custom_field = '" . $this->db->escape(isset($address['custom_field']) ? json_encode($address['custom_field']) : json_encode(array())) . "'");

				if (isset($address['default'])) {
					$address_id = $this->db->getLastId();

					$this->db->query("UPDATE " . DB_PREFIX . "supplier SET address_id = '" . (int)$address_id . "' WHERE supplier_id = '" . (int)$supplier_id . "'");
				}
			}
		}
		
		if ($data['affiliate']) {
			$this->db->query("REPLACE INTO " . DB_PREFIX . "supplier_affiliate SET supplier_id = '" . (int)$supplier_id . "', company = '" . $this->db->escape($data['company']) . "', website = '" . $this->db->escape($data['website']) . "', tracking = '" . $this->db->escape($data['tracking']) . "', commission = '" . (float)$data['commission'] . "', tax = '" . $this->db->escape($data['tax']) . "', payment = '" . $this->db->escape($data['payment']) . "', cheque = '" . $this->db->escape($data['cheque']) . "', paypal = '" . $this->db->escape($data['paypal']) . "', bank_name = '" . $this->db->escape($data['bank_name']) . "', bank_branch_number = '" . $this->db->escape($data['bank_branch_number']) . "', bank_swift_code = '" . $this->db->escape($data['bank_swift_code']) . "', bank_account_name = '" . $this->db->escape($data['bank_account_name']) . "', bank_account_number = '" . $this->db->escape($data['bank_account_number']) . "', custom_field = '" . $this->db->escape(isset($data['custom_field']['affiliate']) ? json_encode($data['custom_field']['affiliate']) : json_encode(array())) . "', status = '" . (int)$data['affiliate'] . "', date_added = NOW()");
		}		
	}

	public function editToken($supplier_id, $token) {
		$this->db->query("UPDATE " . DB_PREFIX . "supplier SET token = '" . $this->db->escape($token) . "' WHERE supplier_id = '" . (int)$supplier_id . "'");
	}

	public function deletesupplier($supplier_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "supplier WHERE supplier_id = '" . (int)$supplier_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "supplier_address WHERE supplier_id = '" . (int)$supplier_id . "'");
		
	}

	public function getsupplier($supplier_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "supplier WHERE supplier_id = '" . (int)$supplier_id . "'");

		return $query->row;
	}

	public function getsupplierByEmail($email) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "supplier WHERE LCASE(email) = '" . $this->db->escape(utf8_strtolower($email)) . "'");

		return $query->row;
	}
	
	public function getsuppliers($data = array()) {
		$sql = "SELECT *, CONCAT(c.firstname, ' ', c.lastname) AS name, cgd.name AS supplier_group FROM " . DB_PREFIX . "supplier c LEFT JOIN " . DB_PREFIX . "customer_group_description cgd ON (c.supplier_group_id = cgd.customer_group_id)";
		
	
		
		$sql .= " WHERE cgd.language_id = '" . (int)$this->config->get('config_language_id') . "'";
		
		$implode = array();

		if (!empty($data['filter_name'])) {
			$implode[] = "CONCAT(c.firstname, ' ', c.lastname) LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
		}

		if (!empty($data['filter_email'])) {
			$implode[] = "c.email LIKE '" . $this->db->escape($data['filter_email']) . "%'";
		}
		if (!empty($data['filter_telephone'])) {
			$implode[] = "c.telephone LIKE '" . $this->db->escape($data['filter_telephone']) . "%'";
		}
		if (isset($data['filter_newsletter']) && !is_null($data['filter_newsletter'])) {
			$implode[] = "c.newsletter = '" . (int)$data['filter_newsletter'] . "'";
		}

		if (!empty($data['filter_supplier_group_id'])) {
			$implode[] = "c.supplier_group_id = '" . (int)$data['filter_supplier_group_id'] . "'";
		}

		if (!empty($data['filter_affiliate'])) {
			$implode[] = "ca.status = '" . (int)$data['filter_affiliate'] . "'";
		}
		
		if (!empty($data['filter_ip'])) {
			$implode[] = "c.supplier_id IN (SELECT supplier_id FROM " . DB_PREFIX . "supplier_ip WHERE ip = '" . $this->db->escape($data['filter_ip']) . "')";
		}

		if (isset($data['filter_status']) && $data['filter_status'] !== '') {
			$implode[] = "c.status = '" . (int)$data['filter_status'] . "'";
		}

		if (!empty($data['filter_date_added'])) {
			$implode[] = "DATE(c.date_added) = DATE('" . $this->db->escape($data['filter_date_added']) . "')";
		}

		if ($implode) {
			$sql .= " AND " . implode(" AND ", $implode);
		}

		$sort_data = array(
			'name',
			'c.email',
			'c.telephone',
			'supplier_group',
			'c.status',
			'c.ip',
			'c.date_added'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY name";
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

	public function getAddress($address_id) {
		$address_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "supplier_address WHERE address_id = '" . (int)$address_id . "'");

		if ($address_query->num_rows) {
			$country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$address_query->row['country_id'] . "'");

			if ($country_query->num_rows) {
				$country = $country_query->row['name'];
				$iso_code_2 = $country_query->row['iso_code_2'];
				$iso_code_3 = $country_query->row['iso_code_3'];
				$address_format = $country_query->row['address_format'];
			} else {
				$country = '';
				$iso_code_2 = '';
				$iso_code_3 = '';
				$address_format = '';
			}

			$zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$address_query->row['zone_id'] . "'");

			if ($zone_query->num_rows) {
				$zone = $zone_query->row['name'];
				$zone_code = $zone_query->row['code'];
			} else {
				$zone = '';
				$zone_code = '';
			}

			return array(
				'address_id'     => $address_query->row['address_id'],
				'supplier_id'    => $address_query->row['supplier_id'],
				'firstname'      => $address_query->row['firstname'],
				'lastname'       => $address_query->row['lastname'],
				'company'        => $address_query->row['company'],
				'address_1'      => $address_query->row['address_1'],
				'address_2'      => $address_query->row['address_2'],
				'postcode'       => $address_query->row['postcode'],
				'city'           => $address_query->row['city'],
				'zone_id'        => $address_query->row['zone_id'],
				'zone'           => $zone,
				'zone_code'      => $zone_code,
				'country_id'     => $address_query->row['country_id'],
				'country'        => $country,
				'iso_code_2'     => $iso_code_2,
				'iso_code_3'     => $iso_code_3,
				'address_format' => $address_format,
				'custom_field'   => json_decode($address_query->row['custom_field'], true)
			);
		}
	}

	public function getAddresses($supplier_id) {
		$address_data = array();

		$query = $this->db->query("SELECT address_id FROM " . DB_PREFIX . "supplier_address WHERE supplier_id = '" . (int)$supplier_id . "'");

		foreach ($query->rows as $result) {
			$address_info = $this->getAddress($result['address_id']);

			if ($address_info) {
				$address_data[$result['address_id']] = $address_info;
			}
		}

		return $address_data;
	}

	public function getTotalsuppliers($data = array()) {
		$sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "supplier c";

		$implode = array();

		if (!empty($data['filter_name'])) {
			$implode[] = "CONCAT(firstname, ' ', lastname) LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
		}

		if (!empty($data['filter_email'])) {
			$implode[] = "email LIKE '" . $this->db->escape($data['filter_email']) . "%'";
		}

		if (isset($data['filter_newsletter']) && !is_null($data['filter_newsletter'])) {
			$implode[] = "newsletter = '" . (int)$data['filter_newsletter'] . "'";
		}

		if (!empty($data['filter_supplier_group_id'])) {
			$implode[] = "supplier_group_id = '" . (int)$data['filter_supplier_group_id'] . "'";
		}

		if (!empty($data['filter_ip'])) {
			$implode[] = "supplier_id IN (SELECT supplier_id FROM " . DB_PREFIX . "supplier_ip WHERE ip = '" . $this->db->escape($data['filter_ip']) . "')";
		}

		if (isset($data['filter_status']) && $data['filter_status'] !== '') {
			$implode[] = "status = '" . (int)$data['filter_status'] . "'";
		}

		if (!empty($data['filter_date_added'])) {
			$implode[] = "DATE(date_added) = DATE('" . $this->db->escape($data['filter_date_added']) . "')";
		}

		if ($implode) {
			$sql .= " WHERE " . implode(" AND ", $implode);
		}

		$query = $this->db->query($sql);

		return $query->row['total'];
	}

	public function getTotalAddressesBysupplierId($supplier_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "supplier_address WHERE supplier_id = '" . (int)$supplier_id . "'");

		return $query->row['total'];
	}

	public function getTotalAddressesByCountryId($country_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "supplier_address WHERE country_id = '" . (int)$country_id . "'");

		return $query->row['total'];
	}

	public function getTotalAddressesByZoneId($zone_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "supplier_address WHERE zone_id = '" . (int)$zone_id . "'");

		return $query->row['total'];
	}

	public function getTotalsuppliersBysupplierGroupId($supplier_group_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "supplier WHERE supplier_group_id = '" . (int)$supplier_group_id . "'");

		return $query->row['total'];
	}



/**
 * الحصول على بيانات مورد بناءً على المعرف
 *
 * @param int $supplier_id معرف المورد
 * @return array بيانات المورد
 */
public function getSupplier($supplier_id) {
    $query = $this->db->query("SELECT s.*, 
            CONCAT(s.firstname, ' ', IFNULL(s.lastname, '')) AS name,
            cg.name AS supplier_group_name,
            s.account_code
        FROM `" . DB_PREFIX . "supplier` s
        LEFT JOIN `" . DB_PREFIX . "supplier_group_description` cg 
            ON (s.supplier_group_id = cg.supplier_group_id 
            AND cg.language_id = '" . (int)$this->config->get('config_language_id') . "')
        WHERE s.supplier_id = '" . (int)$supplier_id . "'");

    if ($query->num_rows) {
        $address_query = $this->db->query("SELECT a.*, 
                c.name AS country_name, 
                z.name AS zone_name
            FROM `" . DB_PREFIX . "supplier_address` a
            LEFT JOIN `" . DB_PREFIX . "country` c ON (a.country_id = c.country_id)
            LEFT JOIN `" . DB_PREFIX . "zone` z ON (a.zone_id = z.zone_id)
            WHERE a.supplier_id = '" . (int)$supplier_id . "'
            ORDER BY a.address_id ASC 
            LIMIT 1");

        $address_data = [];
        
        if ($address_query->num_rows) {
            $address_data = [
                'address_id'        => $address_query->row['address_id'],
                'firstname'         => $address_query->row['firstname'],
                'lastname'          => $address_query->row['lastname'],
                'company'           => $address_query->row['company'],
                'address_1'         => $address_query->row['address_1'],
                'address_2'         => $address_query->row['address_2'],
                'city'              => $address_query->row['city'],
                'postcode'          => $address_query->row['postcode'],
                'country_id'        => $address_query->row['country_id'],
                'country'           => $address_query->row['country_name'] ?? '',
                'zone_id'           => $address_query->row['zone_id'],
                'zone'              => $address_query->row['zone_name'] ?? '',
                'formatted_address' => $this->formatAddress($address_query->row)
            ];
        }

        // استعادة البيانات المالية للمورد
        $financial_query = $this->db->query("SELECT
                SUM(CASE WHEN je.is_debit = 1 THEN je.amount ELSE 0 END) AS total_debit,
                SUM(CASE WHEN je.is_debit = 0 THEN je.amount ELSE 0 END) AS total_credit
            FROM `" . DB_PREFIX . "journal_entries` je
            JOIN `" . DB_PREFIX . "accounts` a ON je.account_code = a.account_code
            WHERE a.account_code = '" . (int)$query->row['account_code'] . "'");

        $financial_data = [];
        if ($financial_query->num_rows) {
            $financial_data = [
                'total_debit'  => $financial_query->row['total_debit'] ?? 0,
                'total_credit' => $financial_query->row['total_credit'] ?? 0,
                'balance'      => ($financial_query->row['total_debit'] ?? 0) - ($financial_query->row['total_credit'] ?? 0)
            ];
        }

        // الجمع بين البيانات الأساسية والعنوان والماليات
        return array_merge($query->row, $address_data, $financial_data);
    } else {
        return [];
    }
}

/**
 * تنسيق العنوان
 *
 * @param array $address بيانات العنوان
 * @return string العنوان المنسق
 */
protected function formatAddress($address) {
    $country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` 
                                      WHERE country_id = '" . (int)$address['country_id'] . "'");
    
    if ($country_query->num_rows) {
        $address_format = $country_query->row['address_format'];
    } else {
        $address_format = '{firstname} {lastname}' . "\n" . '{company}' . "\n" . '{address_1}' . "\n" . '{address_2}' . "\n" . '{city} {postcode}' . "\n" . '{zone}' . "\n" . '{country}';
    }

    $find = [
        '{firstname}',
        '{lastname}',
        '{company}',
        '{address_1}',
        '{address_2}',
        '{city}',
        '{postcode}',
        '{zone}',
        '{country}'
    ];

    $zone_query = $this->db->query("SELECT name FROM `" . DB_PREFIX . "zone` 
                                  WHERE zone_id = '" . (int)$address['zone_id'] . "'");
    $country_query = $this->db->query("SELECT name FROM `" . DB_PREFIX . "country` 
                                      WHERE country_id = '" . (int)$address['country_id'] . "'");

    $replace = [
        'firstname' => $address['firstname'],
        'lastname'  => $address['lastname'],
        'company'   => $address['company'],
        'address_1' => $address['address_1'],
        'address_2' => $address['address_2'],
        'city'      => $address['city'],
        'postcode'  => $address['postcode'],
        'zone'      => $zone_query->num_rows ? $zone_query->row['name'] : '',
        'country'   => $country_query->num_rows ? $country_query->row['name'] : ''
    ];

    return str_replace(["\r\n", "\r", "\n"], '<br />', 
        preg_replace(["/\s\s+/", "/\r\r+/", "/\n\n+/"], '<br />', 
            trim(str_replace($find, $replace, $address_format))
        )
    );
}


/**
 * الحصول على تقييمات المورد
 *
 * @param int $supplier_id معرف المورد
 * @return array تقييمات المورد
 */
public function getSupplierEvaluations($supplier_id) {
    $query = $this->db->query("SELECT e.*,
            CONCAT(u.firstname, ' ', u.lastname) AS evaluator_name
        FROM `" . DB_PREFIX . "supplier_evaluation` e
        LEFT JOIN `" . DB_PREFIX . "user` u ON (e.evaluator_id = u.user_id)
        WHERE e.supplier_id = '" . (int)$supplier_id . "'
        ORDER BY e.evaluation_date DESC");
    
    return $query->rows;
}

/**
 * إضافة تقييم جديد للمورد
 *
 * @param array $data بيانات التقييم
 * @return int معرف التقييم الجديد
 */
public function addSupplierEvaluation($data) {
    $this->db->query("INSERT INTO `" . DB_PREFIX . "supplier_evaluation` SET
        supplier_id = '" . (int)$data['supplier_id'] . "',
        evaluator_id = '" . (int)$data['evaluator_id'] . "',
        evaluation_date = '" . $this->db->escape($data['evaluation_date']) . "',
        quality_score = '" . (float)$data['quality_score'] . "',
        delivery_score = '" . (float)$data['delivery_score'] . "',
        price_score = '" . (float)$data['price_score'] . "',
        service_score = '" . (float)$data['service_score'] . "',
        overall_score = '" . (float)$data['overall_score'] . "',
        comments = '" . $this->db->escape($data['comments']) . "'");
    
    return $this->db->getLastId();
}

/**
 * الحصول على متوسط تقييم المورد
 *
 * @param int $supplier_id معرف المورد
 * @return array متوسط التقييمات
 */
public function getSupplierAverageRating($supplier_id) {
    $query = $this->db->query("SELECT 
            AVG(quality_score) AS avg_quality,
            AVG(delivery_score) AS avg_delivery,
            AVG(price_score) AS avg_price,
            AVG(service_score) AS avg_service,
            AVG(overall_score) AS avg_overall
        FROM `" . DB_PREFIX . "supplier_evaluation`
        WHERE supplier_id = '" . (int)$supplier_id . "'");
    
    if ($query->num_rows) {
        return [
            'avg_quality'  => round($query->row['avg_quality'], 2),
            'avg_delivery' => round($query->row['avg_delivery'], 2),
            'avg_price'    => round($query->row['avg_price'], 2),
            'avg_service'  => round($query->row['avg_service'], 2),
            'avg_overall'  => round($query->row['avg_overall'], 2)
        ];
    }
    
    return [
        'avg_quality'  => 0,
        'avg_delivery' => 0,
        'avg_price'    => 0,
        'avg_service'  => 0,
        'avg_overall'  => 0
    ];
}

}
