<?php
class ModelEtaCodes extends Model {
public function getProducts($filter_data = array()) {
    $sql = "SELECT p.product_id, pd.name, c.egs_code, c.gpc_code, c.eta_status FROM " . DB_PREFIX . "product p 
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) 
            LEFT JOIN " . DB_PREFIX . "product_egs c ON (p.product_id = c.product_id) 
            WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

    if (!empty($filter_data['product_name'])) {
        $sql .= " AND pd.name LIKE '%" . $this->db->escape($filter_data['product_name']) . "%'";
    }

    if (!empty($filter_data['product_id'])) {
        $sql .= " AND p.product_id = '" . (int)$filter_data['product_id'] . "'";
    }

    if (!empty($filter_data['egs_code'])) {
        $sql .= " AND c.egs_code LIKE '%" . $this->db->escape($filter_data['egs_code']) . "%'";
    }

    if (!empty($filter_data['gpc_code'])) {
        $sql .= " AND c.gpc_code = '" . $this->db->escape($filter_data['gpc_code']) . "'";
    }

    // Ensure to order the results and apply the limit
    $sql .= " ORDER BY c.egs_code ASC";  // Adjust according to your needs
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

public function getTotalProducts($filter_data = array()) {
    $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "product p 
            LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) 
            LEFT JOIN " . DB_PREFIX . "product_egs c ON (p.product_id = c.product_id) 
            WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

    if (!empty($filter_data['product_name'])) {
        $sql .= " AND pd.name LIKE '%" . $this->db->escape($filter_data['product_name']) . "%'";
    }

    if (!empty($filter_data['product_id'])) {
        $sql .= " AND p.product_id = '" . (int)$filter_data['product_id'] . "'";
    }

    if (!empty($filter_data['egs_code'])) {
        $sql .= " AND c.egs_code LIKE '%" . $this->db->escape($filter_data['egs_code']) . "%'";
    }

    if (!empty($filter_data['gpc_code'])) {
        $sql .= " AND c.gpc_code = '" . $this->db->escape($filter_data['gpc_code']) . "'";
    }

    $query = $this->db->query($sql);
    return $query->row['total'];
}
    
     public function getGpcs($filter_data = array()) {
        $sql = "SELECT * FROM " . DB_PREFIX . "gpc_codes ";

        $query = $this->db->query($sql);
        return $query->rows;
    }   

    public function updateProduct($product_id, $gpc_code) {
        $this->db->query("UPDATE " . DB_PREFIX . "product_egs SET gpc_code = '" . $this->db->escape($gpc_code) . "' WHERE product_id = '" . (int)$product_id . "'");
    }


public function accessToken() {
    // Initialize URL and credentials stored in your configuration
    $client_id = $this->config->get('config_eta_client_id');
    $client_secret = $this->config->get('config_eta_secret_1');        
    $url = 'https://id.preprod.eta.gov.eg/connect/token';  // Use the correct API endpoint

    // Prepare data for the request
    $data = [
        'grant_type' => 'client_credentials',
        'client_id' => $client_id,
        'client_secret' => $client_secret,
    ];

    // Initialize cURL session
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded')); // Ensure correct content type

    // Execute cURL request
    $response = curl_exec($curl);
    $info = curl_getinfo($curl);

    // Check for cURL errors
    if (curl_errno($curl)) {
        error_log("cURL error while retrieving access token: " . curl_error($curl));
        curl_close($curl);
        return false;
    }

    // Close cURL session
    curl_close($curl);

    // Handle non-200 HTTP responses
    if ($info['http_code'] != 200) {
        error_log("Failed to retrieve access token: HTTP " . $info['http_code']);
        error_log("Response: " . $response);
        return false;
    }

    // Decode JSON response
    $result = json_decode($response, true);
    if (isset($result['error'])) {
        error_log("Error in token response: " . $result['error_description']);
        return false;
    }

    // Return access token, or false if not available
    return $result['access_token'] ?? false;
}

  public function ensureEgsCodes() {
    $sql = "SELECT product_id FROM " . DB_PREFIX . "product WHERE product_id NOT IN (SELECT product_id FROM " . DB_PREFIX . "product_egs)";
    $products = $this->db->query($sql)->rows;

    foreach ($products as $product) {
        $egsCode = 'EG-' . $this->config->get('config_eta_taxpayer_id') . '-' . $product['product_id'];
        $insertSql = "INSERT INTO " . DB_PREFIX . "product_egs (product_id, egs_code) VALUES ('" . $product['product_id'] . "', '" . $this->db->escape($egsCode) . "')";
        $this->db->query($insertSql);
    }
}
public function getProduct($product_id) {
    $query = $this->db->query("SELECT p.product_id, pd.name, egs.egs_code, egs.gpc_code 
                               FROM " . DB_PREFIX . "product p 
                               LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) 
                               LEFT JOIN " . DB_PREFIX . "product_egs egs ON (p.product_id = egs.product_id) 
                               WHERE p.product_id = '" . (int)$product_id . "' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
    return $query->row;
}
 public function createEgsCode($product_id, $itemData) {
    $token = $this->accessToken();
    if ($token) {
            $url = 'https://api.preprod.invoicing.eta.gov.eg/api/v1.0/codetypes/requests/codes';  // Use the live URL in production
        $headers = [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ];

        // Ensure all necessary fields are included
        $data = [
            "items" => [
                [
                    "codeType" => "EGS",
                    "parentCode" => $itemData['gpc_code'],
                    "itemCode" => 'EG-' . $this->config->get('config_eta_taxpayer_id') . '-' . $product_id,
                    "codeName" => $itemData['name'], // Assuming 'name' is part of itemData
                    "codeNameAr" => $itemData['name'], // Assuming 'name' is part of itemData
                    "activeFrom" => gmdate("Y-m-d\TH:i:s.000"),  // Current UTC time in ISO 8601 format
                ]
            ]
        ];

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);

        if ($response === false) {
            error_log("cURL error: " . curl_error($curl));
            return false;
        }

        $info = curl_getinfo($curl);
        if ($info['http_code'] != 200) {
            error_log("Failed to create EGS code: HTTP " . json_encode($info));
            error_log("Response: " . $response);
            return false;
        }

        curl_close($curl);
        return json_decode($response, true);
    }
    return false;
}

public function checkAndUpdateCodeStatus() {
    // جلب الأكواد التي حالتها ليست 'active'
    $result = $this->db->query("SELECT product_id, egs_code FROM " . DB_PREFIX . "product_egs WHERE eta_status != 'active'");
    if ($result->num_rows) {
        foreach ($result->rows as $row) {
            error_log($row['egs_code']);
            $details = $this->getCodeDetails($row['egs_code']);
            error_log(json_encode($details));
            if ($details && $details['eta_status'] === 'active') {
                // تحديث الحالة في الجدول إذا كان الكود فعال
                $this->updateEtaStatus($row['product_id'], 'active');
            }
        }
    }
    return true;
}

public function getCodeDetails($itemCode) {
    $token = $this->accessToken();
    if (!$token) {
        return false;
    }

    $url = 'https://api.preprod.invoicing.eta.gov.eg/api/v1.0/codetypes/EGS/codes/' . $itemCode;
    $headers = [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ];

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
    $response = curl_exec($curl);

    if ($response === false) {
        curl_close($curl);
        return false;
    }

    $info = curl_getinfo($curl);
    curl_close($curl);

    if ($info['http_code'] != 200) {
        return false;
    }

    $decoded_response = json_decode($response, true);
    error_log(json_encode($decoded_response));
    if (isset($decoded_response['active']) && $decoded_response['active']) {
        return ['eta_status' => 'active','success' => $decoded_response['itemCode'].' Active in ETA'];
    }

        return ['eta_status' => 'pending','success' => 'Send EGS Code To ETA'];
}

public function updateEtaStatus($product_id, $eta_status) {
    $this->db->query("UPDATE " . DB_PREFIX . "product_egs SET eta_status = '" . $this->db->escape($eta_status) . "', updated_at = NOW() WHERE product_id = '" . (int)$product_id . "'");
}

   


}
