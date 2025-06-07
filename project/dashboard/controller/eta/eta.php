<?php

class ControllerEtaEta extends Controller {
    public function index() {
        $client_id = $this->config->get('config_eta_client_id');
        $client_secret = $this->config->get('config_eta_secret_1');

        $response = $this->sendRequest($client_id, $client_secret);

        if(isset($response['access_token'])){
            echo 'Bearer ' .$response['access_token'];
        }
    }

    private function sendRequest($client_id, $client_secret) {
        $url = 'https://id.preprod.eta.gov.eg/connect/token';
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

        return json_decode($response, true);
    } 
    
 /* 
    public function index() {
    //الرئيسية
        $this->load->model('eta/eta');
        $accessToken = $this->model_eta_eta->login();//بيانات الدخول من الاعدادات
echo $accessToken;
    }
   */ 
    //common functions
    public function accessToken() {
        $this->load->model('eta/eta');
        // الحصول على access_token
        $accessToken = $this->model_eta_eta->login();//بيانات الدخول من الاعدادات

        if ($accessToken) {
            $this->session->data['eta_access_token'] = $accessToken;//تخزينها بالجلسة لسهولة الوصول لها من كل مكان
            return $accessToken;    
        }else{
            $this->session->data['eta_access_token'] = '';//تصفير التوكن من الجلسة
            return false;
        }

    }
    
    public function getDocumentTypes($accessToken) {
        $url = $this->config->get('eta_api_base_url') . '/api/v1/documenttypes';

        $curl = curl_init($url);

        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken,
                'Accept-Language: ar' // Set language to Arabic (optional)
            )
        ));

        $response = curl_exec($curl);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            $this->log->write('ETA API error: ' . $error);
            return false;
        }

        $data = json_decode($response, true);

        if (isset($data['error'])) {
            $this->log->write('ETA API error: ' . $data['error_description']);
            return false;
        }

        return $data;
    }
    
    
   public function getDocumentType($accessToken, $documentTypeId) {
        $url = $this->config->get('eta_api_base_url') . '/api/v1/documenttypes/' . $documentTypeId;

        $curl = curl_init($url);

        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken
            )
        ));

        $response = curl_exec($curl);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            $this->log->write('ETA API error: ' . $error);
            return false;
        }

        $data = json_decode($response, true);

        if (isset($data['error'])) {
            $this->log->write('ETA API error: ' . $data['error_description']);
            return false;
        }

        return $data;
    }
    
   public function getDocumentTypeVersion($accessToken, $documentTypeId, $documentTypeVersionID) {
        $url = $this->config->get('eta_api_base_url') . '/api/v1/documenttypes/' . $documentTypeId . '/versions/' . $documentTypeVersionID;

        $curl = curl_init($url);

        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken,
                'Accept-Language: ar' // Set language to Arabic (optional)
            )
        ));

        $response = curl_exec($curl);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            $this->log->write('ETA API error: ' + $error);
            return false;
        }

        $data = json_decode($response, true);

        if (isset($data['error'])) {
            $this->log->write('ETA API error: ' . $data['error_description']);
            return false;
        }

        return $data;
    }
    
    public function getNotifications($accessToken) {
        $url = $this->config->get('eta_api_base_url') . '/api/v1/notifications/taxpayer';

        $params = array(
            'pageSize' => 10, // Optional: Number of notifications per page (default 10)
            'pageNo' => 1, // Optional: Page number (default 1)
            'dateFrom' => '', // Optional: Filter by notification date from (format: YYYY-MM-DD)
            'dateTo' => '', // Optional: Filter by notification date to (format: YYYY-MM-DD)
            'type' => '', // Optional: Filter by notification type
            'language' => 'en', // Optional: Set language preference (default en)
            'status' => '', // Optional: Filter by notification status
            'channel' => '', // Optional: Filter by notification channel
        );

        $url .= '?' . http_build_query($params);

        $curl = curl_init($url);

        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken
            )
        ));

        $response = curl_exec($curl);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            $this->log->write('ETA API error: ' . $error);
            return false;
        }

        $data = json_decode($response, true);

        if (isset($data['error'])) {
            $this->log->write('ETA API error: ' . $data['error_description']);
            return false;
        }

        return $data;
    }  
    
    public function createEgsCodeUsage($accessToken, $data) {
        $url = $this->config->get('eta_api_base_url') . '/api/v1.0/codetypes/requests/codes';

        $curl = curl_init($url);

        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken
            )
        ));

        $response = curl_exec($curl);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            $this->log->write('ETA API error: ' . $error);
            return false;
        }

        $data = json_decode($response, true);

        if (isset($data['error'])) {
            $this->log->write('ETA API error: ' . $data['error_description']);
            return false;
        }

        return $data;
    }  
    

    public function searchEgsCodeUsages($accessToken, $filters = array()) {
        $url = $this->config->get('eta_api_base_url') . '/api/v1.0/codetypes/requests/my';

        $params = array(
            'Active' => 'true', // Filter by active requests (default true)
            'Status' => 'Approved', // Filter by approved requests (default Approved)
            'PageSize' => 10, // Optional: Number of requests per page (default 10)
            'PageNumber' => 1, // Optional: Page number (default 1)
            'OrderDirections' => 'Descending', // Optional: Order direction (default Descending)
            // Add other optional filters here (refer to API documentation)
        );

        // Merge provided filters with defaults
        $params = array_merge($params, $filters);

        $url .= '?' . http_build_query($params);

        $curl = curl_init($url);

        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken,
                'Accept-Language: en' // Set language preference (default en)
            )
        ));

        $response = curl_exec($curl);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            $this->log->write('ETA API error: ' . $error);
            return false;
        }

        $data = json_decode($response, true);

        if (isset($data['error'])) {
            $this->log->write('ETA API error: ' . $data['error_description']);
            return false;
        }

        return $data;
    }
    
    public function requestCodeReuse($accessToken, $data) {
        $url = $this->config->get('eta_api_base_url') . '/api/v1.0/codetypes/requests/codeusages';

        $curl = curl_init($url);
/*

$data = array(
    'items' => array(
        array(
            'codetype' => 'EGS',
            'itemCode' => 'EG-113317713-5598542', // Replace with your code
            'comment' => 'create code usage reason', // Optional: Comment
        ),
        array(
            'codetype' => 'EGS',
            'itemCode' => 'EG-100000053-10015', // Replace with your code
            'comment' => 'create code usage reason', // Optional: Comment
        ),
    ),
);

*/
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken,
                'Accept-Language: en' // Set language preference (default en)
            )
        ));

        $response = curl_exec($curl);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            $this->log->write('ETA API error: ' . $error);
            return false;
        }

        $data = json_decode($response, true);

        if (isset($data['error'])) {
            $this->log->write('ETA API error: ' . $data['error_description']);
            return false;
        }

        return $data;
    }
    
    public function searchPublishedCodes($accessToken, $codeType = 'GS1', $filters = array()) {
        $url = $this->config->get('eta_api_base_url') . '/api/v1.0/codetypes/' . $codeType . '/codes';

        $params = array(
            'ParentLevelName' => 'GPC Level 4 Code - Brick', // Filter by parent level name
            'OnlyActive' => 'true', // Filter by active codes (default true)
            'ActiveFrom' => '2019-01-01T00:00:00Z', // Filter by activation date
            'Ps' => 10, // Page size (default 10)
            'Pn' => 1, // Page number (default 1)
            'OrdDir' => 'Descending', // Order direction (default Descending)
            'CodeTypeLevelNumber' => 5, // Code type level number
            // Add other optional filters here (refer to API documentation)
        );

        // Merge provided filters with defaults
        $params = array_merge($params, $filters);

        $url .= '?' . http_build_query($params);

        $curl = curl_init($url);

        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken,
                'Accept-Language: en' // Set language preference (default en)
            )
        ));

        $response = curl_exec($curl);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            $this->log->write('ETA API error: ' . $error);
            return false;
        }

        $data = json_decode($response, true);

        if (isset($data['error'])) {
            $this->log->write('ETA API error: ' . $data['error_description']);
            return false;
        }

        return $data;
    }    
    
    public function getCodeDetails($accessToken, $codeType, $itemCode) {
        $url = $this->config->get('eta_api_base_url') . '/api/v1.0/codetypes/' . $codeType . '/codes/' . $itemCode;

        $curl = curl_init($url);

        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $accessToken,
                'Accept-Language: en' // Set language preference (default en)
            )
        ));

        $response = curl_exec($curl);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            $this->log->write('ETA API error: ' . $error);
            return false;
        }

        $data = json_decode($response, true);

        if (isset($data['error'])) {
            $this->log->write('ETA API error: ' . $data['error_description']);
            return false;
        }

        return $data;
    }
    
    public function updateEgsCodeUsage($accessToken, $codeUsageRequestId, $data) {
        $url = $this->config->get('eta_api_base_url') . '/api/v1.0/codetypes/requests/codes/' . $codeUsageRequestId;

        $curl = curl_init($url);

        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken,
                'Accept-Language: en' // Set language preference (default en)
            )
        ));

        $response = curl_exec($curl);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            $this->log->write('ETA API error: ' . $error);
            return false;
        }

        $data = json_decode($response, true);

        if (isset($data['error'])) {
            $this->log->write('ETA API error: ' . $data['error_description']);
            return false;
        }

        return $data;
    }
    
    
    public function updateCode($accessToken, $codeType, $itemCode, $data) {
        $url = $this->config->get('eta_api_base_url') . '/api/v1.0/codetypes/' . $codeType . '/codes/' . $itemCode;

        $curl = curl_init($url);
/*
    $data = array(
        'codeDescriptionPrimaryLang' => 'Dh108108 Updated',  // Update primary language description
        'codeDescriptionSecondaryLang' => 'Dh108108 Updated', // Update secondary language description (optional)
        'activeTo' => null, // Set null to leave the active date unchanged
        'linkedCode' => '', // Set empty string to remove any linked code
    );
*/
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken,
                'Accept-Language: en' // Set language preference (default en)
            )
        ));

        $response = curl_exec($curl);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            $this->log->write('ETA API error: ' . $error);
            return false;
        }

        $data = json_decode($response, true);

        if (isset($data['error'])) {
            $this->log->write('ETA API error: ' . $data['error_description']);
            return false;
        }

        return $data;
    }
    
    //e invoicing functions
    public function submitInvoice($accessToken) {
        $url = $this->config->get('eta_api_base_url') . '/api/v1/documentsubmissions';
    
        $data = array(
            "documents" => array(
                array(
                    "issuer" => array(
                        "address" => array(
                            "branchID" => "0",
                            "country" => "EG",
                            "governate" => "Cairo",
                            "regionCity" => "Nasr City",
                            "street" => "580 Clementina Key",
                            "buildingNumber" => "Bldg. 0",
                            "postalCode" => "68030",
                            "floor" => "1",
                            "room" => "123",
                            "landmark" => "7660 Melody Trail",
                            "additionalInformation" => "beside Townhall"
                        ),
                        "type" => "B",
                        "id" => "674859545",
                        "name" => "Issuer Company"
                    ),
                    "receiver" => array(
                        "address" => array(
                            "country" => "EG",
                            "governate" => "Egypt",
                            "regionCity" => "Mufazat al Ismlyah",
                            "street" => "580 Clementina Key",
                            "buildingNumber" => "Bldg. 0",
                            "postalCode" => "68030",
                            "floor" => "1",
                            "room" => "123",
                            "landmark" => "7660 Melody Trail",
                            "additionalInformation" => "beside Townhall"
                        ),
                        "type" => "B",
                        "id" => "313717919",
                        "name" => "Receiver"
                    ),
                    "documentType" => "I",
                    "documentTypeVersion" => "0.9",
                    "dateTimeIssued" => "2021-02-07T02:04:45Z",
                    "taxpayerActivityCode" => "4620",
                    "internalID" => "IID1",
                    "purchaseOrderReference" => "P-233-A6375",
                    "purchaseOrderDescription" => "purchase Order description",
                    "salesOrderReference" => "1231",
                    "salesOrderDescription" => "Sales Order description",
                    "proformaInvoiceNumber" => "SomeValue",
                    "payment" => array(
                        "bankName" => "SomeValue",
                        "bankAddress" => "SomeValue",
                        "bankAccountNo" => "SomeValue",
                        "bankAccountIBAN" => "",
                        "swiftCode" => "",
                        "terms" => "SomeValue"
                    ),
                    "delivery" => array(
                        "approach" => "SomeValue",
                        "packaging" => "SomeValue",
                        "dateValidity" => "2020-09-28T09:30:10Z",
                        "exportPort" => "SomeValue",
                        "grossWeight" => 10.50,
                        "netWeight" => 20.50,
                        "terms" => "SomeValue"
                    ),
                    "invoiceLines" => array(
                        array(
                            "description" => "Computer1",
                            "itemType" => "EGS",
                            "itemCode" => "EG-113317713-123456",
                            "unitType" => "EA",
                            "quantity" => 1,
                            "internalCode" => "IC0",
                            "salesTotal" => 111111111111.00,
                            "total" => 111111111111.00,
                            "valueDifference" => 0.00,
                            "totalTaxableFees" => 0,
                            "netTotal" => 111111111111,
                            "itemsDiscount" => 0,
                            "unitValue" => array(
                                "currencySold" => "EGP",
                                "amountEGP" => 111111111111.00
                            ),
                            "discount" => array(
                                "rate" => 0,
                                "amount" => 0
                            ),
                            "taxableItems" => array(
                                array(
                                    "taxType" => "T1",
                                    "amount" => 0,
                                    "subType" => "V001",
                                    "rate" => 0
                                )
                            )
                        )
                    ),
                    "totalDiscountAmount" => 0,
                    "totalSalesAmount" => 555555555555.00,
                    "netAmount" => 555555555555.00,
                    "taxTotals" => array(
                        array(
                            "taxType" => "T1",
                            "amount" => 0
                        )
                    ),
                    "totalAmount" => 555555555555.00,
                    "extraDiscountAmount" => 0,
                    "totalItemsDiscountAmount" => 0,
                    "signatures" => array(
                        array(
                            "signatureType" => "I",
                            "value" => "<Signature Value>"
                        )
                    )
                )
            )
        );
    
        $curl = curl_init($url);
    
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken
            )
        ));
    
        $response = curl_exec($curl);
        $error = curl_error($curl);
    
        curl_close($curl);
    
        if ($error) {
            $this->log->write('ETA API error: ' . $error);
            return false;
        }
    
        $data = json_decode($response, true);
    
        if (isset($data['error'])) {
            $this->log->write('ETA API error: ' . $data['error_description']);
            return false;
        }
    
        return $data;
    }

    public function submitDebitNote($data) {
    $url = "{{apiBaseUrl}}/api/v1/documentsubmissions";
    
    $curl = curl_init($url);
    
    $headers = array(
        "Content-Type: application/json",
        "Authorization: Bearer " . $data['generatedAccessToken']
    );
    
    $postData = array(
        'documents' => array(
            array(
                'issuer' => $data['issuer'],
                'receiver' => $data['receiver'],
                'documentType' => 'D',
                'documentTypeVersion' => '0.9',
                'dateTimeIssued' => '2021-02-08T23:59:59Z',
                'taxpayerActivityCode' => '4620',
                'internalID' => 'IID1',
                'purchaseOrderReference' => 'P-233-A6375',
                'purchaseOrderDescription' => 'purchase Order description',
                'salesOrderReference' => '1231',
                'salesOrderDescription' => 'Sales Order description',
                'proformaInvoiceNumber' => 'SomeValue',
                'references' => array(
                    '5Z40TP7SXAKADVH8WX71PXNE10'
                ),
                'payment' => $data['payment'],
                'delivery' => $data['delivery'],
                'invoiceLines' => $data['invoiceLines'],
                'totalDiscountAmount' => 76.29,
                'totalSalesAmount' => 1609.90,
                'netAmount' => 1533.61,
                'taxTotals' => array(
                    array(
                        'taxType' => 'T1',
                        'amount' => 477.54
                    ),
                    array(
                        'taxType' => 'T2',
                        'amount' => 365.47
                    )
                ),
                'totalAmount' => 5191.50,
                'extraDiscountAmount' => 5.00,
                'totalItemsDiscountAmount' => 14.00,
                'signatures' => array(
                    array(
                        'signatureType' => 'I',
                        'value' => '<Signature Value>'
                    )
                )
            )
        )
    );
    
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($curl);
    
    curl_close($curl);
    
    return $response;
}


    public function submitCreditNote($data) {
        $url = '{{apiBaseUrl}}/api/v1/documentsubmissions';
        $access_token = $this->getAccessToken(); // Define your function to get access token

		$data = array(
			'documents' => array(
				array(
					'issuer' => array(
						'address' => array(
							'branchID' => '1',
							'country' => 'EG',
							'governate' => 'Cairo',
							'regionCity' => 'Nasr City',
							'street' => '580 Clementina Key',
							'buildingNumber' => 'Bldg. 0',
							'postalCode' => '68030',
							'floor' => '1',
							'room' => '123',
							'landmark' => '7660 Melody Trail',
							'additionalInformation' => 'beside Townhall'
						),
						'type' => 'B',
						'id' => '113317713',
						'name' => 'Issuer Company'
					),
					'receiver' => array(
						'address' => array(
							'country' => 'EG',
							'governate' => 'Egypt',
							'regionCity' => 'Mufazat al Ismlyah',
							'street' => '580 Clementina Key',
							'buildingNumber' => 'Bldg. 0',
							'postalCode' => '68030',
							'floor' => '1',
							'room' => '123',
							'landmark' => '7660 Melody Trail',
							'additionalInformation' => 'beside Townhall'
						),
						'type' => 'B',
						'id' => '313717919',
						'name' => 'Receiver'
					),
					'documentType' => 'C',
					'documentTypeVersion' => '0.9',
					'dateTimeIssued' => '2020-10-29T23:59:59Z',
					'taxpayerActivityCode' => '4620',
					'internalID' => 'IID1',
					'purchaseOrderReference' => 'P-233-A6375',
					'purchaseOrderDescription' => 'purchase Order description',
					'salesOrderReference' => '1231',
					'salesOrderDescription' => 'Sales Order description',
					'proformaInvoiceNumber' => 'SomeValue',
					'references' => array(
						'5Z40TP7SXAKADVH8WX71PXNE10'
					),
					'payment' => array(
						'bankName' => 'SomeValue',
						'bankAddress' => 'SomeValue',
						'bankAccountNo' => 'SomeValue',
						'bankAccountIBAN' => '',
						'swiftCode' => '',
						'terms' => 'SomeValue'
					),
					'delivery' => array(
						'approach' => 'SomeValue',
						'packaging' => 'SomeValue',
						'dateValidity' => '2020-09-28T09:30:10Z',
						'exportPort' => 'SomeValue',
						'countryOfOrigin' => 'EG',
						'grossWeight' => 10.50,
						'netWeight' => 20.50,
						'terms' => 'SomeValue'
					),
					'invoiceLines' => array(
						array(
							'description' => 'Computer1',
							'itemType' => 'EGS',
							'itemCode' => 'EG-113317713-123456',
							'unitType' => 'EA',
							'quantity' => 5,
							'internalCode' => 'IC0',
							'salesTotal' => 947.00,
							'total' => 2969.89,
							'valueDifference' => 7.00,
							'totalTaxableFees' => 817.42,
							'netTotal' => 880.71,
							'itemsDiscount' => 5.00,
							'unitValue' => array(
								'currencySold' => 'EUR',
								'amountEGP' => 189.40,
								'amountSold' => 10.00,
								'currencyExchangeRate' => 18.94
							),
							'discount' => array(
								'rate' => 7,
								'amount' => 66.29
							),
							'taxableItems' => array(
								array(
									'taxType' => 'T1',
									'amount' => 272.07,
									'subType' => 'T1',
									'rate' => 14.00
								),
								array(
									'taxType' => 'T2',
									'amount' => 208.22,
									'subType' => 'T2',
									'rate' => 12
								),
								array(
									'taxType' => 'T3',
									'amount' => 30.00,
									'subType' => 'T3',
									'rate' => 0.00
								),
								array(
									'taxType' => 'T4',
									'amount' => 43.79,
									'subType' => 'T4',
									'rate' => 5.00
								),
								array(
									'taxType' => 'T5',
									'amount' => 123.30,
									'subType' => 'T5',
									'rate' => 14.00
								),
								array(
									'taxType' => 'T6',
									'amount' => 60.00,
									'subType' => 'T6',
									'rate' => 0.00
								),
								array(
									'taxType' => 'T7',
									'amount' => 88.07,
									'subType' => 'T7',
									'rate' => 10.00
								),
								array(
									'taxType' => 'T8',
									'amount' => 123.30,
									'subType' => 'T8',
									'rate' => 14.00
								),
								array(
									'taxType' => 'T9',
									'amount' => 105.69,
									'subType' => 'T9',
									'rate' => 12.00
								),
								array(
									'taxType' => 'T10',
									'amount' => 88.07,
									'subType' => 'T10',
									'rate' => 10.00
								),
								array(
									'taxType' => 'T11',
									'amount' => 123.30,
									'subType' => 'T11',
									'rate' => 14.00
								),
								array(
									'taxType' => 'T12',
									'amount' => 105.69,
									'subType' => 'T12',
									'rate' => 12.00
								),
								array(
									'taxType' => 'T13',
									'amount' => 88.07,
									'subType' => 'T13',
									'rate' => 10.00
								),
								array(
									'taxType' => 'T14',
									'amount' => 123.30,
									'subType' => 'T14',
									'rate' => 14.00
								),
								array(
									'taxType' => 'T15',
									'amount' => 105.69,
									'subType' => 'T15',
									'rate' => 12.00
								),
								array(
									'taxType' => 'T16',
									'amount' => 88.07,
									'subType' => 'T16',
									'rate' => 10.00
								),
								array(
									'taxType' => 'T17',
									'amount' => 88.07,
									'subType' => 'T17',
									'rate' => 10.00
								),
								array(
									'taxType' => 'T18',
									'amount' => 123.30,
									'subType' => 'T18',
									'rate' => 14.00
								),
								array(
									'taxType' => 'T19',
									'amount' => 105.69,
									'subType' => 'T19',
									'rate' => 12.00
								),
								array(
									'taxType' => 'T20',
									'amount' => 88.07,
									'subType' => 'T20',
									'rate' => 10.00
								)
							)
						),
						array(
							'description' => 'Computer2',
							'itemType' => 'GPC',
							'itemCode' => '10003752',
							'unitType' => 'EA',
							'quantity' => 7,
							'internalCode' => 'IC0',
							'salesTotal' => 662.90,
							'total' => 2226.61,
							'valueDifference' => 6.00,
							'totalTaxableFees' => 621.51,
							'netTotal' => 652.90,
							'itemsDiscount' => 9.00,
							'unitValue' => array(
								'currencySold' => 'EUR',
								'amountEGP' => 94.70,
								'amountSold' => 5.00,
								'currencyExchangeRate' => 18.94
							),
							'discount' => array(
								'rate' => 0,
								'amount' => 10.00
							),
							'taxableItems' => array(
								array(
									'taxType' => 'T1',
									'amount' => 205.47,
									'subType' => 'T1',
									'rate' => 14.00
								),
								array(
									'taxType' => 'T2',
									'amount' => 157.25,
									'subType' => 'T2',
									'rate' => 12
								),
								array(
									'taxType' => 'T3',
									'amount' => 30.00,
									'subType' => 'T3',
									'rate' => 0.00
								),
								array(
									'taxType' => 'T4',
									'amount' => 32.20,
									'subType' => 'T4',
									'rate' => 5.00
								),
								array(
									'taxType' => 'T5',
									'amount' => 91.41,
									'subType' => 'T5',
									'rate' => 14.00
								),
								array(
									'taxType' => 'T6',
									'amount' => 60.00,
									'subType' => 'T6',
									'rate' => 0.00
								),
								array(
									'taxType' => 'T7',
									'amount' => 65.29,
									'subType' => 'T7',
									'rate' => 10.00
								),
								array(
									'taxType' => 'T8',
									'amount' => 91.41,
									'subType' => 'T8',
									'rate' => 14.00
								),
								array(
									'taxType' => 'T9',
									'amount' => 78.35,
									'subType' => 'T9',
									'rate' => 12.00
								),
								array(
									'taxType' => 'T10',
									'amount' => 65.29,
									'subType' => 'T10',
									'rate' => 10.00
								),
								array(
									'taxType' => 'T11',
									'amount' => 91.41,
									'subType' => 'T11',
									'rate' => 14.00
								),
								array(
									'taxType' => 'T12',
									'amount' => 78.35,
									'subType' => 'T12',
									'rate' => 12.00
								),
								array(
									'taxType' => 'T13',
									'amount' => 65.29,
									'subType' => 'T13',
									'rate' => 10.00
								),
								array(
									'taxType' => 'T14',
									'amount' => 91.41,
									'subType' => 'T14',
									'rate' => 14.00
								),
								array(
									'taxType' => 'T15',
									'amount' => 78.35,
									'subType' => 'T15',
									'rate' => 12.00
								),
								array(
									'taxType' => 'T16',
									'amount' => 65.29,
									'subType' => 'T16',
									'rate' => 10.00
								),
								array(
									'taxType' => 'T17',
									'amount' => 65.29,
									'subType' => 'T17',
									'rate' => 10.00
								),
								array(
									'taxType' => 'T18',
									'amount' => 91.41,
									'subType' => 'T18',
									'rate' => 14.00
								),
								array(
									'taxType' => 'T19',
									'amount' => 78.35,
									'subType' => 'T19',
									'rate' => 12.00
								),
								array(
									'taxType' => 'T20',
									'amount' => 65.29,
									'subType' => 'T20',
									'rate' => 10.00
								)
							)
						)
					),
					'totalDiscountAmount' => 76.29,
					'totalSalesAmount' => 1609.90,
					'netAmount' => 1533.61,
					'taxTotals' => array(
						array(
							'taxType' => 'T1',
							'amount' => 477.54
						),
						array(
							'taxType' => 'T2',
							'amount' => 365.47
						),
						array(
							'taxType' => 'T3',
							'amount' => 60.00
						),
						array(
							'taxType' => 'T4',
							'amount' => 75.99
						),
						array(
							'taxType' => 'T5',
							'amount' => 214.71
						),
						array(
							'taxType' => 'T6',
							'amount' => 120.00
						),
						array(
							'taxType' => 'T7',
							'amount' => 153.36
						),
						array(
							'taxType' => 'T8',
							'amount' => 214.71
						),
						array(
							'taxType' => 'T9',
							'amount' => 184.04
						),
						array(
							'taxType' => 'T10',
							'amount' => 153.36
						),
						array(
							'taxType' => 'T11',
							'amount' => 214.71
						),
						array(
							'taxType' => 'T12',
							'amount' => 184.04
						),
						array(
							'taxType' => 'T13',
							'amount' => 153.36
						),
						array(
							'taxType' => 'T14',
							'amount' => 214.71
						),
						array(
							'taxType' => 'T15',
							'amount' => 184.04
						),
						array(
							'taxType' => 'T16',
							'amount' => 153.36
						),
						array(
							'taxType' => 'T17',
							'amount' => 153.36
						),
						array(
							'taxType' => 'T18',
							'amount' => 214.71
						),
						array(
							'taxType' => 'T19',
							'amount' => 184.04
						),
						array(
							'taxType' => 'T20',
							'amount' => 153.36
						)
					),
					'totalAmount' => 5191.50,
					'extraDiscountAmount' => 5.00,
					'totalItemsDiscountAmount' => 14.00,
					'signatures' => array(
						array(
							'signatureType' => 'I',
							'value' => '<Signature Value>'
						)
					)
				)
			)
		);
			

		$data_string = json_encode($data);

		$ch = curl_init('http://api_host/api/v1/documentsubmissions');
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Content-Length: ' . strlen($data_string))
		);

		$result = curl_exec($ch);
		curl_close($ch);
		echo $result;
    }

    function submitExportInvoice($accessToken, $apiBaseUrl) {
        // API URL
        $url = $apiBaseUrl . '/api/v1/documentsubmissions';
    
        // Request body
        $data = array(
            'documents' => array(
                array(
                    'issuer' => array(
                        'address' => array(
                            'branchID' => '0',
                            'country' => 'EG',
                            'governate' => 'Cairo',
                            'regionCity' => 'Nasr City',
                            'street' => '580 Clementina Key',
                            'buildingNumber' => 'Bldg. 0'
                        ),
                        'type' => 'B',
                        'id' => '674859545',
                        'name' => 'Company'
                    ),
                    'receiver' => array(
                        'address' => array(
                            'country' => 'IE',
                            'governate' => 'Leinster',
                            'regionCity' => 'Dublin',
                            'street' => 'Carmanhall and Leopardstown',
                            'buildingNumber' => 'One Microsoft Place',
                            'postalCode' => 'D18 P521'
                        ),
                        'type' => 'F',
                        'id' => '1234567890',
                        'name' => 'Receiver Company'
                    ),
                    'documentType' => 'EI',
                    'documentTypeVersion' => '1.0',
                    'dateTimeIssued' => '2023-01-31T15:00:00Z',
                    'taxpayerActivityCode' => '1910',
                    'serviceDeliveryDate' => '2023-11-19',
                    'internalID' => 'Simple1234',
                    'purchaseOrderReference' => '',
                    'purchaseOrderDescription' => '',
                    'salesOrderReference' => '',
                    'salesOrderDescription' => '',
                    'proformaInvoiceNumber' => '',
                    'payment' => array(
                        'bankName' => '',
                        'bankAddress' => '',
                        'bankAccountNo' => '',
                        'bankAccountIBAN' => '',
                        'swiftCode' => '',
                        'terms' => ''
                    ),
                    'delivery' => array(
                        'approach' => '',
                        'packaging' => '',
                        'dateValidity' => '',
                        'exportPort' => '',
                        'countryOfOrigin' => '',
                        'grossWeight' => 0,
                        'netWeight' => 0,
                        'terms' => ''
                    ),
                    'invoiceLines' => array(
                        array(
                            'description' => 'Computer1',
                            'itemType' => 'GS1',
                            'itemCode' => '1000000000003',
                            'unitType' => 'EA',
                            'quantity' => 25,
                            'weightUnitType' => 'EA',
                            'weightQuantity' => 21,
                            'internalCode' => '',
                            'salesTotal' => 250,
                            'total' => 250,
                            'valueDifference' => 0,
                            'totalTaxableFees' => 0,
                            'netTotal' => 250,
                            'itemsDiscount' => 0,
                            'unitValue' => array(
                                'currencySold' => 'EGP',
                                'amountEGP' => 10
                            ),
                            'discount' => array(
                                'rate' => 0,
                                'amount' => 0
                            ),
                            'taxableItems' => array(
                                array(
                                    'taxType' => 'T1',
                                    'amount' => 0,
                                    'subType' => 'V009',
                                    'rate' => 0
                                )
                            )
                        )
                    ),
                    'totalDiscountAmount' => 0,
                    'totalSalesAmount' => 250,
                    'netAmount' => 250,
                    'taxTotals' => array(
                        array(
                            'taxType' => 'T1',
                            'amount' => 0
                        )
                    ),
                    'totalAmount' => 250,
                    'extraDiscountAmount' => 0,
                    'totalItemsDiscountAmount' => 0,
                    'signatures' => array(
                        array(
                            'signatureType' => 'I',
                            'value' => '<Signature Value>'
                        )
                    )
                )
            )
        );
    
        // Convert data to JSON format
        $data_string = json_encode($data);
    
        // Initialize cURL
        $ch = curl_init($url);
    
        // Set cURL options
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken,
            'Content-Length: ' . strlen($data_string))
        );
    
        // Execute cURL request
        $result = curl_exec($ch);
    
        // Close cURL session
        curl_close($ch);
    
        return $result;
    }

    function submitExportDebitNote($accessToken, $apiBaseUrl) {
        // API URL
        $url = $apiBaseUrl . '/api/v1/documentsubmissions';
    
        // Request body
        $data = array(
            'documents' => array(
                array(
                    'issuer' => array(
                        'address' => array(
                            'branchID' => '0',
                            'country' => 'EG',
                            'governate' => 'Cairo',
                            'regionCity' => 'Nasr City',
                            'street' => '580 Clementina Key',
                            'buildingNumber' => 'Bldg. 0'
                        ),
                        'type' => 'B',
                        'id' => '674859545',
                        'name' => 'Company'
                    ),
                    'receiver' => array(
                        'address' => array(
                            'country' => 'IE',
                            'governate' => 'Leinster',
                            'regionCity' => 'Dublin',
                            'street' => 'Carmanhall and Leopardstown',
                            'buildingNumber' => 'One Microsoft Place',
                            'postalCode' => 'D18 P521'
                        ),
                        'type' => 'F',
                        'id' => '1234567890',
                        'name' => 'Receiver Company'
                    ),
                    'documentType' => 'ED',
                    'documentTypeVersion' => '1.0',
                    'dateTimeIssued' => '2023-01-31T15:00:00Z',
                    'taxpayerActivityCode' => '1910',
                    'serviceDeliveryDate' => '2023-11-19',
                    'internalID' => 'Simple1234',
                    'references' => array('90HGMKPEVN0CNAT5Q4GF96RG10'),
                    'purchaseOrderReference' => '',
                    'purchaseOrderDescription' => '',
                    'salesOrderReference' => '',
                    'salesOrderDescription' => '',
                    'proformaInvoiceNumber' => '',
                    'payment' => array(
                        'bankName' => '',
                        'bankAddress' => '',
                        'bankAccountNo' => '',
                        'bankAccountIBAN' => '',
                        'swiftCode' => '',
                        'terms' => ''
                    ),
                    'delivery' => array(
                        'approach' => '',
                        'packaging' => '',
                        'dateValidity' => '',
                        'exportPort' => '',
                        'countryOfOrigin' => '',
                        'grossWeight' => 0,
                        'netWeight' => 0,
                        'terms' => ''
                    ),
                    'invoiceLines' => array(
                        array(
                            'description' => 'Computer1',
                            'itemType' => 'GS1',
                            'itemCode' => '1000000000003',
                            'unitType' => 'EA',
                            'quantity' => 25,
                            'weightUnitType' => 'EA',
                            'weightQuantity' => 21,
                            'internalCode' => '',
                            'salesTotal' => 250,
                            'total' => 250,
                            'valueDifference' => 0,
                            'totalTaxableFees' => 0,
                            'netTotal' => 250,
                            'itemsDiscount' => 0,
                            'unitValue' => array(
                                'currencySold' => 'EGP',
                                'amountEGP' => 10
                            ),
                            'discount' => array(
                                'rate' => 0,
                                'amount' => 0
                            ),
                            'taxableItems' => array(
                                array(
                                    'taxType' => 'T1',
                                    'amount' => 0,
                                    'subType' => 'V009',
                                    'rate' => 0
                                )
                            )
                        )
                    ),
                    'totalDiscountAmount' => 0,
                    'totalSalesAmount' => 250,
                    'netAmount' => 250,
                    'taxTotals' => array(
                        array(
                            'taxType' => 'T1',
                            'amount' => 0
                        )
                    ),
                    'totalAmount' => 250,
                    'extraDiscountAmount' => 0,
                    'totalItemsDiscountAmount' => 0,
                    'signatures' => array(
                        array(
                            'signatureType' => 'I',
                            'value' => '<Signature Value>'
                        )
                    )
                )
            )
        );
    
        // Convert data to JSON format
        $data_string = json_encode($data);
    
        // Initialize cURL
        $ch = curl_init($url);
    
        // Set cURL options
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken,
            'Content-Length: ' . strlen($data_string))
        );
    
        // Execute cURL request
        $result = curl_exec($ch);
    
        // Close cURL session
        curl_close($ch);
    
        return $result;
    }    
 
    function submitExportCreditNote($accessToken, $apiBaseUrl) {
    // API URL
    $url = $apiBaseUrl . '/api/v1/documentsubmissions';

    // Request body
    $data = array(
        'documents' => array(
            array(
                'issuer' => array(
                    'address' => array(
                        'branchID' => '0',
                        'country' => 'EG',
                        'governate' => 'Cairo',
                        'regionCity' => 'Nasr City',
                        'street' => '580 Clementina Key',
                        'buildingNumber' => 'Bldg. 0'
                    ),
                    'type' => 'B',
                    'id' => '674859545',
                    'name' => 'Company'
                ),
                'receiver' => array(
                    'address' => array(
                        'country' => 'IE',
                        'governate' => 'Leinster',
                        'regionCity' => 'Dublin',
                        'street' => 'Carmanhall and Leopardstown',
                        'buildingNumber' => 'One Microsoft Place',
                        'postalCode' => 'D18 P521'
                    ),
                    'type' => 'F',
                    'id' => '1234567890',
                    'name' => 'Receiver Company'
                ),
                'documentType' => 'EC',
                'documentTypeVersion' => '1.0',
                'dateTimeIssued' => '2023-01-31T15:00:00Z',
                'taxpayerActivityCode' => '1910',
                'serviceDeliveryDate' => '2023-11-19',
                'internalID' => 'Simple1234',
                'references' => array('90HGMKPEVN0CNAT5Q4GF96RG10'),
                'purchaseOrderReference' => '',
                'purchaseOrderDescription' => '',
                'salesOrderReference' => '',
                'salesOrderDescription' => '',
                'proformaInvoiceNumber' => '',
                'payment' => array(
                    'bankName' => '',
                    'bankAddress' => '',
                    'bankAccountNo' => '',
                    'bankAccountIBAN' => '',
                    'swiftCode' => '',
                    'terms' => ''
                ),
                'delivery' => array(
                    'approach' => '',
                    'packaging' => '',
                    'dateValidity' => '',
                    'exportPort' => '',
                    'countryOfOrigin' => '',
                    'grossWeight' => 0,
                    'netWeight' => 0,
                    'terms' => ''
                ),
                'invoiceLines' => array(
                    array(
                        'description' => 'Computer1',
                        'itemType' => 'GS1',
                        'itemCode' => '1000000000003',
                        'unitType' => 'EA',
                        'quantity' => 25,
                        'weightUnitType' => 'EA',
                        'weightQuantity' => 21,
                        'internalCode' => '',
                        'salesTotal' => 250,
                        'total' => 250,
                        'valueDifference' => 0,
                        'totalTaxableFees' => 0,
                        'netTotal' => 250,
                        'itemsDiscount' => 0,
                        'unitValue' => array(
                            'currencySold' => 'EGP',
                            'amountEGP' => 10
                        ),
                        'discount' => array(
                            'rate' => 0,
                            'amount' => 0
                        ),
                        'taxableItems' => array(
                            array(
                                'taxType' => 'T1',
                                'amount' => 0,
                                'subType' => 'V009',
                                'rate' => 0
                            )
                        )
                    )
                ),
                'totalDiscountAmount' => 0,
                'totalSalesAmount' => 250,
                'netAmount' => 250,
                'taxTotals' => array(
                    array(
                        'taxType' => 'T1',
                        'amount' => 0
                    )
                ),
                'totalAmount' => 250,
                'extraDiscountAmount' => 0,
                'totalItemsDiscountAmount' => 0,
                'signatures' => array(
                    array(
                        'signatureType' => 'I',
                        'value' => '<Signature Value>'
                    )
                )
            )
        )
    );

        // Convert data to JSON format
        $data_string = json_encode($data);
    
        // Initialize cURL
        $ch = curl_init($url);
    
        // Set cURL options
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken,
            'Content-Length: ' . strlen($data_string))
        );
    
        // Execute cURL request
        $result = curl_exec($ch);
    
        // Close cURL session
        curl_close($ch);
    
        return $result;
    }

    function cancelDocument($accessToken, $apiBaseUrl, $documentUUID, $reason) {
        // API URL
        $url = str_replace(':documentUUID', $documentUUID, $apiBaseUrl . '/api/v1.0/documents/state/:documentUUID/state');
    
        // Request body
        $data = array(
            'status' => 'cancelled',
            'reason' => $reason
        );
    
        // Convert data to JSON format
        $data_string = json_encode($data);
    
        // Initialize cURL
        $ch = curl_init($url);
    
        // Set cURL options
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken,
            'Content-Length: ' . strlen($data_string))
        );
    
        // Execute cURL request
        $result = curl_exec($ch);
    
        // Close cURL session
        curl_close($ch);
    
        return $result;
    }
    
    function rejectDocument($accessToken, $apiBaseUrl, $documentUUID, $reason) {
        // API URL
        $url = str_replace(':documentUUID', $documentUUID, $apiBaseUrl . '/api/v1.0/documents/state/:documentUUID/state');
    
        // Request body
        $data = array(
            'status' => 'rejected',
            'reason' => $reason
        );
    
        // Convert data to JSON format
        $data_string = json_encode($data);
    
        // Initialize cURL
        $ch = curl_init($url);
    
        // Set cURL options
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken,
            'Content-Length: ' . strlen($data_string))
        );
    
        // Execute cURL request
        $result = curl_exec($ch);
    
        // Close cURL session
        curl_close($ch);
    
        return $result;
    }
    
    function getRecentDocuments($accessToken, $apiBaseUrl) {
        // API URL
        $url = $apiBaseUrl . '/api/v1.0/documents/recent?pageNo=1&pageSize=100&submissionDateFrom=2022-12-01T15:00:59&submissionDateTo=2022-12-31T20:00:00&status=Valid&documentType=i';
    
        // Initialize cURL
        $ch = curl_init($url);
    
        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $accessToken,
            'PageSize: 10',
            'PageNo: 1'
        ));
    
        // Execute cURL request
        $result = curl_exec($ch);
    
        // Close cURL session
        curl_close($ch);
    
        return $result;
   }
 
    function requestDocumentPackage($accessToken, $apiBaseUrl) {
        // API URL
        $url = $apiBaseUrl . '/api/v1/documentPackages/requests';
    
        // Request body
        $body = [
            "type" => "Summary",
            "format" => "JSON",
            "queryParameters" => [
                "dateFrom" => "2020-10-01T21:00:28.451Z",
                "dateTo" => "2020-12-30T21:00:28.451Z",
                "statuses" => ["Valid", "Cancelled", "Rejected"],
                "productsInternalCodes" => [],
                "receiverSenderId" => "",
                "receiverSenderType" => "0",
                "branchNumber" => "",
                "itemCodes" => [
                    [
                        "codeValue" => "",
                        "codeType" => ""
                    ]
                ],
                "documentTypeNames" => []
            ]
        ];
    
        // Initialize cURL
        $ch = curl_init($url);
    
        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ));
    
        // Execute cURL request
        $result = curl_exec($ch);
    
        // Close cURL session
        curl_close($ch);
    
        return $result;
    }

    public function requestDocumentPackageAsIntermediary() {
        $url = $this->apiBaseUrl . '/api/v1/documentPackages/requests';

        $data = array(
            'type' => 'Full',
            'format' => 'XML',
            'queryParameters' => array(
                'dateFrom' => '2021-01-01T00:00:00.000Z',
                'dateTo' => '2021-01-31T00:00:00.000Z',
                'statuses' => array('Valid'),
                'productsInternalCodes' => array(),
                'receiverSenderType' => '0',
                'documentTypeNames' => array('C', 'D', 'I'),
                'representedTaxpayerFilterType' => '1',
                'representeeRin' => '',
                'branchNumber' => '',
                'itemCodes' => array(
                    array(
                        'codeValue' => '',
                        'codeType' => ''
                    )
                )
            )
        );

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $this->generatedAccessToken,
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        echo $response;
    }

    public function getPackageRequests() {
        $url = $this->apiBaseUrl . '/api/v1/documentPackages/requests?pageSize=10&pageNo=1';

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $this->generatedAccessToken
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        echo $response;
    }

    public function getDocumentPackage($rid) {
        $url = $this->apiBaseUrl . '/api/v1/documentPackages/' . $rid;

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $this->generatedAccessToken
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        echo $response;
    }

    public function getDocument($documentUUID) {
        $url = $this->apiBaseUrl . '/api/v1/documents/' . $documentUUID . '/raw';

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $this->generatedAccessToken
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        echo $response;
    }

    public function getSubmission($submitionUUID) {
        $url = $this->apiBaseUrl . '/api/v1.0/documentSubmissions/' . $submitionUUID . '?PageSize=1';

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $this->generatedAccessToken,
                'PageSize: 10',
                'PageNo: 1'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        echo $response;
    }

    public function getDocumentPrintout($documentUUID) {
        $url = $this->apiBaseUrl . '/api/v1/documents/' . $documentUUID . '/pdf';

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $this->generatedAccessToken
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        echo $response;
    }

    public function getDocumentDetails($documentUUID) {
        $url = $this->apiBaseUrl . '/api/v1/documents/' . $documentUUID . '/details';

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $this->generatedAccessToken
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        echo $response;
    }

    public function declineCancelDocument($documentUUID) {
        $url = $this->apiBaseUrl . '/api/v1.0/documents/state/' . $documentUUID . '/decline/cancelation';

        $data = array();

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $this->generatedAccessToken,
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        echo $response;
    }

    public function declineRejectionDocument($documentUUID) {
        $url = $this->apiBaseUrl . '/api/v1.0/documents/state/' . $documentUUID . '/decline/rejection';

        $data = array();

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $this->generatedAccessToken,
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        echo $response;
    }

    public function searchDocuments() {
        $url = $this->apiBaseUrl . '/api/v1.0/documents/search';
        $queryParams = http_build_query(array(
            'submissionDateFrom' => '2022-12-01T15:00:59',
            'submissionDateTo' => '2022-12-31T20:00:00',
            'continuationToken' => '',
            'pageSize' => '100',
            'status' => 'Valid',
            'documentType' => 'i'
        ));

        $url .= '?' . $queryParams;

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $this->generatedAccessToken,
                'PageSize: 10',
                'PageNo: 1'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        echo $response;
    }
    
    public function submitReceipt() {
        $url = $this->apiBaseUrl . '/api/v1/receiptsubmissions';

        $data = array(
            'receipts' => array(
                array(
                    'header' => array(
                        'dateTimeIssued' => '2022-06-13T00:34:00Z',
                        'receiptNumber' => '{{receiptNumber1}}',
                        'uuid' => '{{receiptUuid1}}',
                        'previousUUID' => '',
                        'referenceOldUUID' => '',
                        'currency' => 'EGP',
                        'exchangeRate' => 0,
                        'sOrderNameCode' => 'sOrderNameCode',
                        'orderdeliveryMode' => 'FC'
                    ),
                    'documentType' => array(
                        'receiptType' => 'SC',
                        'typeVersion' => '1.2'
                    ),
                    'seller' => array(
                        'rin' => '674859545',
                        'companyTradeName' => 'شركة الصوٝى',
                        'branchCode' => '0',
                        'branchAddress' => array(
                            'country' => 'EG',
                            'governate' => 'cairo',
                            'regionCity' => 'city center',
                            'street' => '16 street',
                            'buildingNumber' => '14BN',
                            'postalCode' => '74235',
                            'floor' => '1F',
                            'room' => '3R',
                            'landmark' => 'tahrir square',
                            'additionalInformation' => 'talaat harb street'
                        ),
                        'deviceSerialNumber' => 'Sofy123',
                        'activityCode' => '4620'
                    ),
                    'buyer' => array(
                        'type' => 'F',
                        'id' => '313717919',
                        'name' => 'taxpayer 1',
                        'mobileNumber' => '+201020567462',
                        'paymentNumber' => '987654'
                    ),
                    'itemData' => array(
                        array(
                            'internalCode' => '880609',
                            'description' => 'Samsung A02 32GB_LTE_BLACK_DS_SM-A022FZKDMEB_A022 _ A022_SM-A022FZKDMEB',
                            'itemType' => 'GS1',
                            'itemCode' => '037000401629',
                            'unitType' => 'EA',
                            'quantity' => 35,
                            'unitPrice' => 247.96000,
                            'netSale' => 7810.74000,
                            'totalSale' => 8678.60000,
                            'total' => 8887.04360,
                            'commercialDiscountData' => array(
                                array(
                                    'amount' => 867.86000,
                                    'description' => 'XYZ',
                                    'rate' => 10
                                )
                            ),
                            'itemDiscountData' => array(
                                array(
                                    'amount' => 10,
                                    'description' => 'ABC',
                                    'rate' => 5.6
                                ),
                                array(
                                    'amount' => 10,
                                    'description' => 'XYZ'
                                )
                            ),
                            'additionalCommercialDiscount' => array(
                                'amount' => 9456.1404,
                                'description' => 'ABC',
                                'rate' => 10.0
                            ),
                            'additionalItemDiscount' => array(
                                'amount' => 9456.1404,
                                'description' => 'XYZ',
                                'rate' => 10.0
                            ),
                            'valueDifference' => 20,
                            'taxableItems' => array(
                                array(
                                    'taxType' => 'T1',
                                    'amount' => 1096.30360,
                                    'subType' => 'V009',
                                    'rate' => 14
                                )
                            )
                        )
                    ),
                    'totalSales' => 8678.60000,
                    'totalCommercialDiscount' => 867.86000,
                    'totalItemsDiscount' => 20,
                    'extraReceiptDiscountData' => array(
                        array(
                            'amount' => 0,
                            'description' => 'ABC',
                            'rate' => 10.12
                        )
                    ),
                    'netAmount' => 7810.74000,
                    'feesAmount' => 0,
                    'totalAmount' => 8887.04360,
                    'taxTotals' => array(
                        array(
                            'taxType' => 'T1',
                            'amount' => 1096.30360
                        )
                    ),
                    'paymentMethod' => 'C',
                    'adjustment' => 0
                )
            )
        );

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $this->generatedAccessToken,
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        echo $response;
    }

    public function submitReturn() {
        $url = $this->apiBaseUrl . '/api/v1/receiptsubmissions';

        $data = array(
            'receipts' => array(
                array(
                    'header' => array(
                        'dateTimeIssued' => '2022-06-13T00:34:00Z',
                        'receiptNumber' => '{{receiptNumber1}}',
                        'uuid' => '{{returnreceiptUuid1}}',
                        'previousUUID' => '',
                        'referenceUUID' => '{{receiptUuid1}}',
                        'referenceOldUUID' => '',
                        'currency' => 'EGP',
                        'exchangeRate' => 0,
                        'sOrderNameCode' => 'sOrderNameCode',
                        'orderdeliveryMode' => 'FC'
                    ),
                    'documentType' => array(
                        'receiptType' => 'RC',
                        'typeVersion' => '1.2'
                    ),
                    'seller' => array(
                        'rin' => '674859545',
                        'companyTradeName' => 'شركة الصوٝى',
                        'branchCode' => '0',
                        'branchAddress' => array(
                            'country' => 'EG',
                            'governate' => 'cairo',
                            'regionCity' => 'city center',
                            'street' => '16 street',
                            'buildingNumber' => '14BN',
                            'postalCode' => '74235',
                            'floor' => '1F',
                            'room' => '3R',
                            'landmark' => 'tahrir square',
                            'additionalInformation' => 'talaat harb street'
                        ),
                        'deviceSerialNumber' => 'Sofy123',
                        'activityCode' => '4620'
                    ),
                    'buyer' => array(
                        'type' => 'F',
                        'id' => '313717919',
                        'name' => 'taxpayer 1',
                        'mobileNumber' => '+201020567462',
                        'paymentNumber' => '987654'
                    ),
                    'itemData' => array(
                        array(
                            'internalCode' => '880609',
                            'description' => 'Samsung A02 32GB_LTE_BLACK_DS_SM-A022FZKDMEB_A022 _ A022_SM-A022FZKDMEB',
                            'itemType' => 'GS1',
                            'itemCode' => '037000401629',
                            'unitType' => 'EA',
                            'quantity' => 35,
                            'unitPrice' => 247.96000,
                            'netSale' => 7810.74000,
                            'totalSale' => 8678.60000,
                            'total' => 8887.04360,
                            'commercialDiscountData' => array(
                                array(
                                    'amount' => 867.86000,
                                    'description' => 'XYZ',
                                    'rate' => 10
                                )
                            ),
                            'itemDiscountData' => array(
                                array(
                                    'amount' => 10,
                                    'description' => 'ABC',
                                    'rate' => 5.6
                                ),
                                array(
                                    'amount' => 10,
                                    'description' => 'XYZ'
                                )
                            ),
                            'additionalCommercialDiscount' => array(
                                'amount' => 9456.1404,
                                'description' => 'ABC',
                                'rate' => 10.0
                            ),
                            'additionalItemDiscount' => array(
                                'amount' => 9456.1404,
                                'description' => 'XYZ',
                                'rate' => 10.0
                            ),
                            'valueDifference' => 20,
                            'taxableItems' => array(
                                array(
                                    'taxType' => 'T1',
                                    'amount' => 1096.30360,
                                    'subType' => 'V009',
                                    'rate' => 14
                                )
                            )
                        )
                    ),
                    'totalSales' => 8678.60000,
                    'totalCommercialDiscount' => 867.86000,
                    'totalItemsDiscount' => 20,
                    'extraReceiptDiscountData' => array(
                        array(
                            'amount' => 0,
                            'description' => 'ABC',
                            'rate' => 10.12
                        )
                    ),
                    'netAmount' => 7810.74000,
                    'feesAmount' => 0,
                    'totalAmount' => 8887.04360,
                    'taxTotals' => array(
                        array(
                            'taxType' => 'T1',
                            'amount' => 1096.30360
                        )
                    ),
                    'paymentMethod' => 'C',
                    'adjustment' => 0
                )
            )
        );

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $this->generatedAccessToken,
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        echo $response;
    }


    
    
    






    
}

