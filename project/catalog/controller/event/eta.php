<?php
class ControllerEventEta extends Controller {

	// model/checkout/order/addOrderHistory/before
	public function addOrder(&$route, &$args, &$output) {
		$this->load->model('checkout/order');
    error_log('addOrder event triggered');
		$order_info = $this->model_checkout_order->getOrder($args[0]);

		if ($order_info) {
		    
            // ربما نربط ارسال الفاتورة بالحالات ونضيف حالات معينه يتم الارسال عليها لكن قانونا لحظي
			/*
			// If order status not in complete or proccessing remove value to sale total
			if (!in_array($args[1], array_merge($this->config->get('config_processing_status'), $this->config->get('config_complete_status')))) {

			}
			
			// Remove from processing status if new status is not array
			if (in_array($order_info['order_status_id'], $this->config->get('config_processing_status')) && !in_array($args[1], $this->config->get('config_processing_status'))) {

			}
			
			// Add to processing status if new status is not array		
			if (!in_array($order_info['order_status_id'], $this->config->get('config_processing_status')) && in_array($args[1], $this->config->get('config_processing_status'))) {

			}
			
			// Remove from complete status if new status is not array
			if (in_array($order_info['order_status_id'], $this->config->get('config_complete_status')) && !in_array($args[1], $this->config->get('config_complete_status'))) {

			}
			
			// Add to complete status if new status is not array		
			if (!in_array($order_info['order_status_id'], $this->config->get('config_complete_status')) && in_array($args[1], $this->config->get('config_complete_status'))) {

			}
			*/
			
	/*
            if ($order_info['customer_group_id'] == '2') {
                $invoice_data = $this->prepareInvoiceData($order_info['order_id'], $order_info);
                $this->submitInvoice($invoice_data, 'invoice');
            } else if ($order_info['customer_group_id'] == '1') {
                error_log($order_info['customer_group_id']);
                $invoice_data = $this->prepareInvoiceData($order_info['order_id'], $order_info);

                $this->submitInvoice($invoice_data, 'invoice');//ارسال فاتورة
                //$receipt_data = $this->prepareReceiptData($order_info['order_id'], $order_info);
                //$this->submitReceipt($receipt_data, 'receipt');
            }	
            */
			
            $invoice_data = $this->prepareInvoiceData($order_info['order_id'], $order_info);

            // إضافة مهمة إرسال الفاتورة إلى Queue
            $queue = new Queue($this->db);
            $job = [
                'task' => 'send_invoice',
                'data' => [
                    'invoice_data' => $invoice_data
                ]
            ];
            $queue->addJob($job);



        // إعداد بيانات القيد
        $journal_data = $this->prepareJournalData($order_info);
        $journal_job = [
            'task' => 'record_journal',
            'data' => [
                'journal_data' => $journal_data
            ]
        ];
        $queue->addJob($journal_job);
        
        
            // إعلام العميل بأن الطلب قيد المعالجة
          //  $this->response->redirect($this->url->link('checkout/success'));
            
            
		}
	
		
	}

private function prepareJournalData($order_info) {
    error_log('prepareJournalData event triggered');
    
    $entries = [];
    $total_amount = $order_info['total'];
    $customer_code = $this->getCustomerAccountCode($order_info['customer_id']); // جلب كود حساب العميل من بياناته

    // إدخال القيد المدين
    $entries[] = [
        'account_code' => 511, // كود حساب المبيعات
        'amount' => $total_amount,
        'is_debit' => 0 // دائن
    ];

    // إدخال القيد الدائن
    $entries[] = [
        'account_code' => $customer_code,
        'amount' => $total_amount,
        'is_debit' => 1 // مدين
    ];

    return [
        'thedate' => date('Y-m-d'),
        'refnum' => $order_info['order_id'],
        'description' => 'Order #' . $order_info['order_id'],
        'entries' => $entries,
        'added_by' => 'System'
    ];
}

private function getCustomerAccountCode($customer_id) {
    $query = $this->db->query("SELECT account_code FROM " . DB_PREFIX . "customer WHERE customer_id = '" . (int)$customer_id . "'");
    return $query->row['account_code'];
}


/*  eta - الضرائب المصرية*/   
    
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
            curl_close($curl);
            return false;
        }
    
        // Close cURL session
        curl_close($curl);
    
        // Handle non-200 HTTP responses
        if ($info['http_code'] != 200) {
            return false;
        }
    
        // Decode JSON response
        $result = json_decode($response, true);
        if (isset($result['error'])) {
            return false;
        }
    
        // Return access token, or false if not available
        return $result['access_token'] ?? false;
    }


private function submitInvoice($document_data, $type) {
    // تحويل البيانات إلى JSON
    $json_data = json_encode($document_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    $token = $this->accessToken();

    // تحديد عنوان الـ URL للبيئة المبدئية
    $url = "https://api.preprod.invoicing.eta.gov.eg/api/v1.0/documentSubmissions";

    // إعداد الرؤوس للطلب
    $headers = [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ];

    // تهيئة الطلب باستخدام cURL
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $json_data);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    // تنفيذ الطلب
    $response = curl_exec($curl);
    $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE); // الحصول على كود الحالة HTTP

    // التحقق من وجود أخطاء في الاتصال
    if (curl_errno($curl)) {
        $error_msg = curl_error($curl);
    }

    // إغلاق الاتصال بـ cURL
    curl_close($curl);

    // تسجيل الاستجابة للطلب

    // معالجة الاستجابة
    $response_data = json_decode($response, true);

    // تحقق إذا كانت الاستجابة تحتوي على UUID
    if (isset($response_data['submissionUUID'])) {
        // حفظ UUID ورمز QR
        $this->model_checkout_order->updateOrderUUID($document_data['internalId'], $response_data['submissionUUID'], $response_data['acceptedDocuments'][0]['longId']);
    } else {
        // تسجيل الخطأ إذا لم يتم قبول المستند
        error_log("Error submitting invoice: " . json_encode($response_data));
    }
}
private function prepareInvoiceData($order_id, $order_info) {
    $issuer_id = $this->config->get('config_eta_taxpayer_id');
    $issuer_name = $this->config->get('config_name');
    $issuer_branch_code = '0';
    $totalProductsAmount = 0.0;
    $totalOrderAmount = 0.0;
    $totalDiscountAmount = 0.0;
    $totalTaxableFees = 0.0;
    $totalItemsDiscountAmount = 0.0;
    $taxTotalsAmount = 0.0;

    // Get order products
    $order_products = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = " . (int)$order_id)->rows;

    // Get order totals
    $order_totals = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_total WHERE order_id = " . (int)$order_id)->rows;

    foreach ($order_totals as $total) {
        if ($total['code'] == 'sub_total') {
            $totalProductsAmount = round((float)$total['value'], 5);
        } elseif ($total['code'] == 'tax') {
            $taxTotalsAmount += round((float)$total['value'], 5);
        } elseif ($total['code'] == 'total') {
            $totalOrderAmount += round((float)$total['value'], 5);            
        } elseif ($total['value'] < 0) { // For discounts and coupons
            $totalDiscountAmount += abs(round((float)$total['value'], 5));
        }
    }

    $invoice_lines = [];
    foreach ($order_products as $product) {
        $egs_code_query = $this->db->query("SELECT egs_code FROM " . DB_PREFIX . "product_egs WHERE product_id = " . (int)$product['product_id']);
        $egs_code = $egs_code_query->num_rows ? $egs_code_query->row['egs_code'] : '';

        // Calculate item values
        $item_price = round((float)$product['price'], 5);
        $item_quantity = round((float)$product['quantity'], 5);
        $sub_total = round((float)($product['price'] * $product['quantity']), 5);
        $item_tax = round((float)($sub_total * 0.14), 5); // Assuming 14% tax rate
        $item_total = round(($sub_total + $item_tax), 5);
        $item_discount = 0.0;

        $invoice_lines[] = [
            'description' => $product['name'],
            'itemType' => 'EGS',
            'itemCode' => $egs_code,
            'unitType' => 'C62', // Use the appropriate unit type
            'quantity' => $item_quantity,
            'unitValue' => [
                'currencySold' => 'EGP',
                'amountEGP' => $item_price
            ],
            'salesTotal' => $sub_total, // السعر * الكمية
            'total' => $item_total, // إجمالي سعر المنتج + الضرائب
            'netTotal' => $sub_total, // إجمالي سعر المنتج بعد الخصم
            'valueDifference' => 0.00, // Adjust as needed
            'totalTaxableFees' => 0.00000,
            'itemsDiscount' => $item_discount,
            'taxableItems' => [
                [
                    'taxType' => 'T1',
                    'amount' => $item_tax,
                    'subType' => 'V009',
                    'rate' => 14.00 // 14% tax rate
                ]
            ],
            'internalCode' => (string)(int)$product['product_id']
        ];

        // Summing up taxable fees for the invoice
        $totalTaxableFees += $item_tax;
    }

    // Calculate the net amount and total amount
    $netAmount = round($totalProductsAmount - $totalDiscountAmount, 5);
    $totalAmount = round(($netAmount + $totalTaxableFees), 5);

    $invoice_data = [
        'documents' => [
            [
                'issuer' => [
                    'type' => 'B',
                    'id' => $issuer_id,
                    'name' => $issuer_name,
                    'address' => [
                        'branchID' => $issuer_branch_code,
                        'country' => 'EG',
                        'governate' => $this->config->get('config_governate'),
                        'regionCity' => $this->config->get('config_region_city'),
                        'street' => $this->config->get('config_street'),
                        'buildingNumber' => $this->config->get('config_building_number'),
                        'postalCode' => '',
                        'floor' => '',
                        'room' => '',
                        'landmark' => '',
                        'additionalInformation' => ''
                    ]
                ],
                'receiver' => [
                    'type' => ($order_info['customer_group_id'] == '1') ? 'P' : 'B',
                    'id' => $order_info['rin_customer'] ?? '',
                    'name' => $order_info['payment_firstname'] . ' ' . $order_info['payment_lastname'],
                    'address' => [
                        'country' => 'EG',
                        'governate' => $order_info['payment_zone'],
                        'regionCity' => $order_info['payment_city'],
                        'street' => $order_info['payment_address_1'],
                        'buildingNumber' => $order_info['payment_address_2'] ?? '',
                        'postalCode' => '',
                        'floor' => '',
                        'room' => '',
                        'landmark' => '',
                        'additionalInformation' => ''
                    ]
                ],
                'documentType' => 'I',
                'documentTypeVersion' => '0.9',
                'dateTimeIssued' => gmdate('Y-m-d\TH:i:s\Z'),
                'taxpayerActivityCode' => $this->config->get('config_eta_activity_code'),
                'internalID' => (string)(int)$order_info['order_id'],
                'purchaseOrderReference' => '',
                'purchaseOrderDescription' => '',
                'salesOrderReference' => '',
                'salesOrderDescription' => '',
                'proformaInvoiceNumber' => '',
                'invoiceLines' => $invoice_lines,
                'totalSalesAmount' => round($totalProductsAmount, 5), // Ensure numeric value
                'totalDiscountAmount' => 0.0000, // Ensure numeric value
                'netAmount' => round($totalProductsAmount, 5), // Ensure numeric value
                'taxTotals' => [
                    [
                        'taxType' => 'T1',
                        'amount' => round($totalTaxableFees, 5), // Ensure numeric value
                    ]
                ],
                'totalAmount' => round($totalAmount, 5), // Ensure numeric value
                'extraDiscountAmount' => round($totalDiscountAmount, 5),  // order coupon discount
                'totalItemsDiscountAmount' => 0.00000, // item not have discount will test it
                'signatures' => [] // Add this empty array to avoid validation error
            ]
        ]
    ];

    // Log the invoice data for debugging

    return $invoice_data;
}









    private function submitReceipt($document_data, $type) {
        $json_data = json_encode($document_data);
     $token = $this->accessToken();

        $url = "https://api.preprod.invoicing.eta.gov.eg/api/v1/receiptsubmissions";

        $headers = [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ];


        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($document_data));
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);



        curl_close($curl);

        // Process the response
        $response_data = json_decode($response, true);

        if (isset($response_data['submissionUUID'])) {
            // Save UUID and QR code
            $this->model_checkout_order->updateOrderUUID($document_data['issuer']['internalID'], $response_data['submissionUUID'], $response_data['qrCode']);
        }
    }



 
    public function submitDiscountNotification($order_id, $discount_data) {
        $order_info = $this->model_checkout_order->getOrder($order_id);
        $document_data = $this->prepareInvoiceData($order_id, $order_info);
        $document_data['extraDiscountAmount'] = $discount_data['amount'];
        $document_data['totalAmount'] -= $discount_data['amount'];
        
        $this->submitInvoice($document_data, 'discount');
    }

    public function submitAdditionNotification($order_id, $addition_data) {
        $order_info = $this->model_checkout_order->getOrder($order_id);
        $document_data = $this->prepareInvoiceData($order_id, $order_info);
        $document_data['extraAdditionAmount'] = $addition_data['amount'];
        $document_data['totalAmount'] += $addition_data['amount'];
        
        $this->submitInvoice($document_data, 'addition');
    }

    public function cancelInvoice($uuid) {
        $url = "https://api.preprod.invoicing.eta.gov.eg/api/v1.0/cancel-invoice/$uuid";

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response, true);
    }

    public function cancelReceipt($uuid) {
        $url = "https://api.preprod.invoicing.eta.gov.eg/api/v1/cancel-receipt/$uuid";

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response, true);
    }
 
        
    
/* نهاية الضرائب المصرية - eta */    


	
}