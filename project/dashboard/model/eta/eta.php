<?php

class ModelEtaEta extends Model {
    //نحتاجها مع كل اتصال
    private function accessToken() {
        $client_id = $this->config->get('config_eta_client_id');
        $client_secret = $this->config->get('config_eta_secret_1');        
        $url = 'https://id.preprod.eta.gov.eg/connect/token';
        //on live  
        //$url = 'https://id.eta.gov.eg/connect/token';
        $data = [
            'grant_type' => 'client_credentials',
            'client_id' => $client_id,
            'client_secret' => $client_secret,
        ];

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);
        curl_close($curl);

        $resposedecoded = json_decode($response, true);
        if ($resposedecoded['access_token']) {
            $this->session->data['eta_access_token'] = $resposedecoded['access_token'];//تخزينها بالجلسة لسهولة الوصول لها من كل مكان
        }else{
            $this->session->data['eta_access_token'] = '';//تصفير التوكن من الجلسة
        }        
        return isset($resposedecoded['access_token']) ? $resposedecoded['access_token'] : false;
    }
  
    public function createEgsCode($itemData) {
        $token = $this->accessToken();
        if ($token) {
            $url = 'https://id.preprod.eta.gov.eg/codetypes/requests/codes';
            $headers = array(
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json'
            );
    
            $data = [
                "items" => [
                    [
                        "codeType" => "EGS",
                        "itemCode" => $itemData['itemCode'],
                        "codeName" => $itemData['codeName'],
                        "codeNameAr" => $itemData['codeNameAr'],
                        "activeFrom" => $itemData['activeFrom'],
                        "activeTo" => '',  // إعداد تاريخ انتهاء اختياري
                        "description" => '', // الوصف فارغ افتراضيا
                        "descriptionAr" => '',  // الوصف بالعربي فارغ افتراضيا
                        "requestReason" => ''  // سبب افتراضي إذا لم يتم تحديده
                    ]
                ]
            ];
    
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($curl);
            curl_close($curl);
    
            return json_decode($response, true);
        }
        return false;
    }

    
    public function updateEgsCode($itemCode, $itemData) {
            $token = $this->accessToken();
            if ($token) {
                $url = 'https://id.preprod.eta.gov.eg/codetypes/requests/codes/' . $itemCode;
                $headers = array(
                    'Authorization: Bearer ' . $token,
                    'Content-Type: application/json'
                );
        
                $data = [
                    "codeName" => $itemData['codeName'],
                    "codeNameAr" => $itemData['codeNameAr'],
                    "activeFrom" => $itemData['activeFrom'], // تاريخ إضافة المنتج
                    "activeTo" => null,  // لا يوجد تاريخ انتهاء
                    "description" => '', // الوصف فارغ
                    "descriptionAr" => ''  // الوصف بالعربي فارغ
                ];
        
                $curl = curl_init($url);
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($curl);
                curl_close($curl);
        
                return json_decode($response, true);
            }
            return false;
        }
    public function fetchEgsCodeStatus($filters) {
        $token = $this->accessToken();
        if ($token) {
            $url = 'https://id.preprod.eta.gov.eg/codetypes/requests/my?' . http_build_query($filters);
            $headers = array(
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json'
            );
    
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_HTTPGET, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($curl);
            curl_close($curl);
    
            return json_decode($response, true);
        }
        return false;
    }
    public function gnerateEgsCodes($filter_data) {
        $token = $this->accessToken();
        if (!$token) {
            return false;
        }
        
        $sql = "SELECT p.product_id, pd.name, p.model, egs.code as egs_code, egs.status as egs_status FROM " . DB_PREFIX . "product p 
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
                LEFT JOIN " . DB_PREFIX . "product_egs egs ON (p.product_id = egs.product_id)";  // Join the EGS codes table
        $sql .= " WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";
        
        // Filters
        if (!empty($filter_data['filter_name'])) {
            $sql .= " AND pd.name LIKE '%" . $this->db->escape($filter_data['filter_name']) . "%'";
        }
        if (!empty($filter_data['filter_model'])) {
            $sql .= " AND p.model LIKE '%" . $this->db->escape($filter_data['filter_model']) . "%'";
        }
        if (!empty($filter_data['filter_status'])) {
            $sql .= " AND egs.status = '" . $this->db->escape($filter_data['filter_status']) . "'";
        }
        
        $product_data = $this->db->query($sql)->rows;
        
        foreach ($product_data as $product) {
            $egsCode = 'EG-' . $this->config->get('config_eta_taxpayer_id') . '-' . $product['product_id'];
            if (!$product['egs']) { // Check if EGS code already exists
                $itemData = [
                    "items" => [
                        [
                            "codeType" => "EGS",
                            "itemCode" => $egsCode,
                            "codeName" => $product['name'],
                            "codeNameAr" => $product['name'],
                            "activeFrom" => date('Y-m-d'),
                            "activeTo" => "",
                            "description" => "",
                            "descriptionAr" => ""
                        ]
                    ]
                ];
                $this->createEgsCode($itemData);
                $this->storeEgsCode($product['product_id'], $egsCode, 'pending');
            }
        }
    
        return true;
    }
    private function storeEgsCode($productId, $egsCode, $status) {
        $sql = "INSERT INTO " . DB_PREFIX . "product_egs (product_id, code, status) VALUES ('" . (int)$productId . "', '" . $this->db->escape($egsCode) . "', '" . $this->db->escape($status) . "')";
        $this->db->query($sql);
    }
    // الفاتورة الالكترونية
    public function sendInvoiceToEta($invoiceData) {
        $token = $this->getAccessToken(); // Method to retrieve stored access token
        $url = 'https://api.preprod.eta.gov.eg/invoices/send'; // Change to live URL in production
        $headers = [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ];
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($invoiceData));
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response, true);
    }
    // الايصالات الالكترونية
    
    // اشعارات خصم 
    
    // اشعارات ااضافة 
}
