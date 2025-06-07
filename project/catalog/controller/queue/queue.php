<?php
class ControllerQueueQueue extends Controller {
    public function process() {
        // تحميل النموذج الخاص بالمهام
        $this->load->model('queue/queue');

        // الحصول على المهام المعلقة
        $tasks = $this->model_queue_queue->getPendingTasks();

        foreach ($tasks as $task) {
            // تأكيد أن المهمة ما زالت في حالة "معلقة" قبل تعيينها كـ "قيد التنفيذ"
            if ($this->model_queue_queue->updateTaskStatus($task['id'], 'processing')) {
                try {
                    // تنفيذ المهمة
                    $this->executeTask(json_decode($task['job'], true));

                    // تحديث حالة المهمة إلى "منفذة"
                    $this->model_queue_queue->updateTaskStatus($task['id'], 'done');
                } catch (Exception $e) {
                    // إذا فشلت المهمة، زيادة عدد المحاولات وتحديث حالتها إلى "فشلت"
                    $this->model_queue_queue->incrementTaskAttempts($task['id']);
                    $this->model_queue_queue->updateTaskStatus($task['id'], 'failed');
                }
            }
        }

        $this->response->setOutput('Queue processed successfully');
    }

    private function executeTask($task) {
        if ($task['task'] == 'send_invoice') {
            $this->submitInvoice($task['data']['invoice_data'], 'invoice'); // إرسال فاتورة
        } elseif ($task['task'] == 'record_journal') {
            $this->addJournal($task['data']['journal_data']); // تسجيل قيد اليومية
        } elseif ($task['task'] == 'save_invoice') {
            $this->saveInvoice($task['data']['invoice_data']); // حفظ الفاتورة
    
        }
    }
private function saveInvoice($invoice_data) {
    $document_details = $invoice_data['invoice'];

    // Extract invoice details from the document details
    $invoice = [
        'customer_id' => $document_details['receiver']['id'] ?? 0,
        'order_id' => $document_details['internalID'],
        'issuer_type' => $document_details['issuer']['type'],
        'issuer_id' => $document_details['issuer']['id'],
        'issuer_name' => $document_details['issuer']['name'],
        'issuer_country' => $document_details['issuer']['address']['country'],
        'issuer_governate' => $document_details['issuer']['address']['governate'],
        'issuer_region_city' => $document_details['issuer']['address']['regionCity'],
        'issuer_street' => $document_details['issuer']['address']['street'],
        'issuer_building_number' => $document_details['issuer']['address']['buildingNumber'],
        'issuer_postal_code' => $document_details['issuer']['address']['postalCode'],
        'issuer_floor' => $document_details['issuer']['address']['floor'],
        'issuer_room' => $document_details['issuer']['address']['room'],
        'issuer_landmark' => $document_details['issuer']['address']['landmark'],
        'issuer_additional_info' => $document_details['issuer']['address']['additionalInformation'],
        'receiver_type' => $document_details['receiver']['type'],
        'receiver_id' => $document_details['receiver']['id'],
        'receiver_name' => $document_details['receiver']['name'],
        'receiver_country' => $document_details['receiver']['address']['country'],
        'receiver_governate' => $document_details['receiver']['address']['governate'],
        'receiver_region_city' => $document_details['receiver']['address']['regionCity'],
        'receiver_street' => $document_details['receiver']['address']['street'],
        'receiver_building_number' => $document_details['receiver']['address']['buildingNumber'],
        'receiver_postal_code' => $document_details['receiver']['address']['postalCode'],
        'receiver_floor' => $document_details['receiver']['address']['floor'],
        'receiver_room' => $document_details['receiver']['address']['room'],
        'receiver_landmark' => $document_details['receiver']['address']['landmark'],
        'receiver_additional_info' => $document_details['receiver']['address']['additionalInformation'],
        'document_type' => $document_details['documentType'],
        'document_version' => $document_details['documentTypeVersion'],
        'date_time_issued' => $document_details['dateTimeIssued'],
        'taxpayer_activity_code' => $document_details['taxpayerActivityCode'],
        'internal_id' => $document_details['internalID'],
        'purchase_order_reference' => $document_details['purchaseOrderReference'],
        'purchase_order_description' => $document_details['purchaseOrderDescription'],
        'sales_order_reference' => $document_details['salesOrderReference'],
        'sales_order_description' => $document_details['salesOrderDescription'],
        'proforma_invoice_number' => $document_details['proformaInvoiceNumber'],
        'total_sales_amount' => $document_details['totalSales'],
        'total_discount_amount' => $document_details['totalDiscount'],
        'net_amount' => $document_details['netAmount'],
        'total_amount' => $document_details['totalAmount'],
        'extra_discount_amount' => $document_details['extraDiscountAmount'],
        'total_items_discount_amount' => $document_details['totalItemsDiscountAmount'],
        'submission_uuid' => $invoice_data['submission_data']['uuid'],
        'status' => $document_details['status'] ?? 'submitted',
        'rejection_reason' => $document_details['rejection_reason'] ?? ''
    ];

    // Insert the invoice data into the database
    $this->db->query("INSERT INTO cod_invoices SET 
        customer_id = '" . (int)$invoice['customer_id'] . "',
        order_id = '" . $this->db->escape($invoice['order_id']) . "',
        issuer_type = '" . $this->db->escape($invoice['issuer_type']) . "',
        issuer_id = '" . $this->db->escape($invoice['issuer_id']) . "',
        issuer_name = '" . $this->db->escape($invoice['issuer_name']) . "',
        issuer_country = '" . $this->db->escape($invoice['issuer_country']) . "',
        issuer_governate = '" . $this->db->escape($invoice['issuer_governate']) . "',
        issuer_region_city = '" . $this->db->escape($invoice['issuer_region_city']) . "',
        issuer_street = '" . $this->db->escape($invoice['issuer_street']) . "',
        issuer_building_number = '" . $this->db->escape($invoice['issuer_building_number']) . "',
        issuer_postal_code = '" . $this->db->escape($invoice['issuer_postal_code']) . "',
        issuer_floor = '" . $this->db->escape($invoice['issuer_floor']) . "',
        issuer_room = '" . $this->db->escape($invoice['issuer_room']) . "',
        issuer_landmark = '" . $this->db->escape($invoice['issuer_landmark']) . "',
        issuer_additional_info = '" . $this->db->escape($invoice['issuer_additional_info']) . "',
        receiver_type = '" . $this->db->escape($invoice['receiver_type']) . "',
        receiver_id = '" . $this->db->escape($invoice['receiver_id']) . "',
        receiver_name = '" . $this->db->escape($invoice['receiver_name']) . "',
        receiver_country = '" . $this->db->escape($invoice['receiver_country']) . "',
        receiver_governate = '" . $this->db->escape($invoice['receiver_governate']) . "',
        receiver_region_city = '" . $this->db->escape($invoice['receiver_region_city']) . "',
        receiver_street = '" . $this->db->escape($invoice['receiver_street']) . "',
        receiver_building_number = '" . $this->db->escape($invoice['receiver_building_number']) . "',
        receiver_postal_code = '" . $this->db->escape($invoice['receiver_postal_code']) . "',
        receiver_floor = '" . $this->db->escape($invoice['receiver_floor']) . "',
        receiver_room = '" . $this->db->escape($invoice['receiver_room']) . "',
        receiver_landmark = '" . $this->db->escape($invoice['receiver_landmark']) . "',
        receiver_additional_info = '" . $this->db->escape($invoice['receiver_additional_info']) . "',
        document_type = '" . $this->db->escape($invoice['document_type']) . "',
        document_version = '" . $this->db->escape($invoice['document_version']) . "',
        date_time_issued = '" . $this->db->escape($invoice['date_time_issued']) . "',
        taxpayer_activity_code = '" . $this->db->escape($invoice['taxpayer_activity_code']) . "',
        internal_id = '" . $this->db->escape($invoice['internal_id']) . "',
        purchase_order_reference = '" . $this->db->escape($invoice['purchase_order_reference']) . "',
        purchase_order_description = '" . $this->db->escape($invoice['purchase_order_description']) . "',
        sales_order_reference = '" . $this->db->escape($invoice['sales_order_reference']) . "',
        sales_order_description = '" . $this->db->escape($invoice['sales_order_description']) . "',
        proforma_invoice_number = '" . $this->db->escape($invoice['proforma_invoice_number']) . "',
        total_sales_amount = '" . (float)$invoice['total_sales_amount'] . "',
        total_discount_amount = '" . (float)$invoice['total_discount_amount'] . "',
        net_amount = '" . (float)$invoice['net_amount'] . "',
        total_amount = '" . (float)$invoice['total_amount'] . "',
        extra_discount_amount = '" . (float)$invoice['extra_discount_amount'] . "',
        total_items_discount_amount = '" . (float)$invoice['total_items_discount_amount'] . "',
        submission_uuid = '" . $this->db->escape($invoice['submission_uuid']) . "',
        status = '" . $this->db->escape($invoice['status']) . "',
        rejection_reason = '" . $this->db->escape($invoice['rejection_reason']) . "'");
}

    
 public function addJournal($data) {
        // إضافة سجل القيد الرئيسي
        $this->db->query("INSERT INTO `" . DB_PREFIX . "journals` SET 
            thedate = '" . $this->db->escape($data['thedate']) . "',
            refnum = '" . $this->db->escape($data['refnum']) . "',
            entrytype = '" . (isset($data['entrytype']) ? (int)$data['entrytype'] : 2) . "',
            description = '" . $this->db->escape($data['description']) . "', 
            added_by = '" . $this->db->escape($data['added_by']) . "',
            created_at = NOW()");

        // الحصول على معرف القيد المضاف حديثًا
        $journal_id = $this->db->getLastId();

        if ($journal_id) {
            // إضافة إدخالات القيد
            foreach ($data['entries'] as $entry) {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "journal_entries` SET 
                    journal_id = '" . (int)$journal_id . "', 
                    account_code = '" . $this->db->escape($entry['account_code']) . "', 
                    amount = '" . (float)$entry['amount'] . "', 
                    is_debit = '" . (int)$entry['is_debit'] . "'");
            }


            return $journal_id;
        } else {
            error_log("Failed to insert journal: " . $this->db->displayError());
        }
        return false;
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
        $json_data = json_encode($document_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
       // error_log($json_data);
        $token = $this->accessToken();
        $url = "https://api.preprod.invoicing.eta.gov.eg/api/v1.0/documentSubmissions";

        $headers = [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ];

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($curl);
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if (curl_errno($curl)) {
            $error_msg = curl_error($curl);
            error_log("Curl error: " . $error_msg);
        }

        curl_close($curl);

        $response_data = json_decode($response, true);
            $uuid = $response_data['acceptedDocuments'][0]['uuid'];
            $submissionId = $response_data['submissionId'];

        if (isset($response_data['acceptedDocuments'][0]['uuid'])) {
        
            if($uuid){
            $this->updateOrderUUID($response_data['acceptedDocuments'][0]['internalId'], $uuid,$response_data['acceptedDocuments'][0]['longId'],$response_data['acceptedDocuments'][0]['hashKey'],$response_data['submissionId']);
            $this->queueInvoiceData($uuid,$submissionId);
            }
        } else {
            error_log("Error submitting invoice: " . json_encode($response_data));
        }

        error_log("Invoice submission response: " . json_encode($response_data));
    }
    
    public function updateOrderUUID($order_id, $uuid,$longId,$hashKey,$submissionId) {
        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET `uuid` = '" . $this->db->escape($uuid) . "', `longId` = '" . $this->db->escape($longId) . "', `hashKey` = '" . $this->db->escape($hashKey) . "', `submissionId` = '" . $this->db->escape($submissionId) . "' WHERE `order_id` = '" . (int)$order_id . "'");
    }

private function queueInvoiceData($uuid,$submissionId) {
    $queue = new Queue($this->db);

    // محاولات متعددة مع تأخير لجلب بيانات الطلب
    $retryCount = 0;
    $maxRetries = 10;
    $delaySeconds = 10;

    while ($retryCount < $maxRetries) {
        // Get submission data and validate the status
        $submission_data = $this->getSubmissionData($submissionId);
        
        if (isset($submission_data['overallStatus']) && $submission_data['overallStatus'] == 'Valid') {
            $invoice_data = [
                'submission_data' => $submission_data,
                'invoice' => $this->getDocumentDetails($uuid)
            ];
            
            $invoice_job = [
                'task' => 'save_invoice',
                'data' => [
                    'invoice_data' => $invoice_data
                ]
            ];

            $queue->addJob($invoice_job);
            return;
        } else {
            error_log("Document status is not valid: " . json_encode($submission_data));
        }

        $retryCount++;
        sleep($delaySeconds);
    }
    error_log("Failed to get valid submission data after $maxRetries attempts");//invalid
}

private function getSubmissionData($uuid) {
    $token = $this->accessToken();
    if (!$token) {
        error_log("Failed to get access token");
        return null;
    }
    
    $url = "https://api.preprod.invoicing.eta.gov.eg/api/v1.0/documentsubmissions/$uuid";

    $headers = [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ];

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($curl);
    curl_close($curl);

    $response_data = json_decode($response, true);
    return $response_data;
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




public function getDocumentDetails($uuid) {
    $token = $this->accessToken();
    $url = "https://api.preprod.invoicing.eta.gov.eg/api/v1/documents/$uuid/details";

    $headers = [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ];

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($curl);
    $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    if (curl_errno($curl)) {
        $error_msg = curl_error($curl);
        error_log("Curl error: " . $error_msg);
    }

    curl_close($curl);

    $response_data = json_decode($response, true);
    error_log("Invoice details: " . json_encode($response_data));

    return $response_data;
}


private function getDocumentPrintout($uuid) {
    $token = $this->accessToken();
    $url = "https://api.preprod.invoicing.eta.gov.eg/api/v1.0/documents/$uuid/print";
    return $this->sendGetRequest($url, $token);
}

private function getRecentDocuments() {
    $token = $this->accessToken();
    $url = "https://api.preprod.invoicing.eta.gov.eg/api/v1.0/recentDocuments";
    return $this->sendGetRequest($url, $token);
}

/* نحتاج لاضافة دالة */ 
/* بناءا على احدث الفواتير سنحدث قائمة الفواتير  وحالة الفواتير السابقة */

 private function invoiceData($uuid) {
    $submissionData = $this->getSubmissionData($uuid);
    $documentDetails = $this->getDocumentDetails($uuid);
    $documentPrintout = $this->getDocumentPrintout($uuid);
    // Assuming documentDetails contains the needed invoice data
    $invoiceData = [
        'submission_data' => $submissionData,//طلب الارسال
        'document_details' => $documentDetails,//الفاتورة
        'document_printout' => $documentPrintout//طباعة pdf
    ];
    error_log(json_encode($invoiceData));
    return $invoiceData;
}

private function sendGetRequest($url, $token) {
        $token = $this->accessToken();
        
        $headers = [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ];

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, true);
        //curl_setopt($curl, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($curl);
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if (curl_errno($curl)) {
            $error_msg = curl_error($curl);
            error_log("Curl error: " . $error_msg);
        }

        curl_close($curl);

        $response_data = json_decode($response, true);
    error_log(json_encode($response_data));
        
        return $response_data;
}



        
    
/* نهاية الضرائب المصرية - eta */    
}
