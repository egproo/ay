<?php
/**
 * AYM ERP System: ETA Electronic Invoicing Model
 *
 * نموذج تكامل الضرائب المصرية - مطور للشركات الحقيقية
 *
 * الميزات المتقدمة:
 * - إعداد بيانات الفواتير وفقاً لمعايير ETA
 * - إعداد بيانات الإيصالات الإلكترونية
 * - إدارة طابور الإرسال المتقدم
 * - حفظ وتتبع حالة الإرسال
 * - معالجة الأخطاء والإعادة التلقائية
 * - حساب الضرائب وفقاً للقوانين المصرية
 * - دعم الإشعارات الدائنة والمدينة
 * - تشفير البيانات الحساسة
 * - سجل مفصل للعمليات
 *
 * @package    AYM ERP
 * @author     AYM Development Team
 * @copyright  2024 AYM ERP Systems
 * @license    Commercial License
 * @version    1.0.0
 * @since      2024-01-15
 */

class ModelExtensionEtaInvoice extends Model {

    /**
     * إعداد بيانات الفاتورة لـ ETA
     */
    public function prepareInvoiceData($order_id) {
        $this->load->model('sale/order');
        $this->load->model('customer/customer');

        $order_info = $this->model_sale_order->getOrder($order_id);

        if (!$order_info) {
            return false;
        }

        // معلومات الشركة المُصدرة
        $issuer = array(
            'address' => array(
                'branchID' => $this->config->get('eta_branch_id') ?: '0',
                'country' => 'EG',
                'governate' => $this->config->get('eta_governate') ?: 'Cairo',
                'regionCity' => $this->config->get('eta_city') ?: 'Cairo',
                'street' => $this->config->get('eta_street') ?: 'Main Street',
                'buildingNumber' => $this->config->get('eta_building') ?: '1',
                'postalCode' => $this->config->get('eta_postal_code') ?: '11511',
                'floor' => $this->config->get('eta_floor') ?: '1',
                'room' => $this->config->get('eta_room') ?: '1',
                'landmark' => $this->config->get('eta_landmark') ?: '',
                'additionalInformation' => $this->config->get('eta_additional_info') ?: ''
            ),
            'type' => 'B', // Business
            'id' => $this->config->get('eta_tax_id'),
            'name' => $this->config->get('config_name')
        );

        // معلومات العميل
        $customer_info = $this->model_customer_customer->getCustomer($order_info['customer_id']);

        $receiver = array(
            'address' => array(
                'country' => $order_info['shipping_iso_code_2'] ?: 'EG',
                'governate' => $order_info['shipping_zone'] ?: 'Cairo',
                'regionCity' => $order_info['shipping_city'] ?: 'Cairo',
                'street' => $order_info['shipping_address_1'] ?: 'Main Street',
                'buildingNumber' => '1',
                'postalCode' => $order_info['shipping_postcode'] ?: '11511',
                'floor' => '1',
                'room' => '1',
                'landmark' => $order_info['shipping_address_2'] ?: '',
                'additionalInformation' => ''
            ),
            'type' => $customer_info && $customer_info['tax_id'] ? 'B' : 'P', // Business or Person
            'id' => $customer_info['tax_id'] ?? $customer_info['national_id'] ?? '000000000000000',
            'name' => $order_info['firstname'] . ' ' . $order_info['lastname']
        );

        // تفاصيل الفاتورة
        $invoice_lines = array();
        $order_products = $this->model_sale_order->getOrderProducts($order_id);

        foreach ($order_products as $product) {
            // حساب الضريبة
            $tax_rate = $this->calculateProductTaxRate($product['product_id']);
            $unit_price = $product['price'];
            $tax_amount = ($unit_price * $tax_rate) / 100;
            $total_amount = $unit_price + $tax_amount;

            $invoice_lines[] = array(
                'description' => $product['name'],
                'itemType' => 'GS1', // أو 'EGS' حسب نوع المنتج
                'itemCode' => $product['model'] ?: $product['product_id'],
                'unitType' => $this->getETAUnitType($product['unit_id']),
                'quantity' => (float)$product['quantity'],
                'internalCode' => $product['product_id'],
                'salesTotal' => round($unit_price * $product['quantity'], 2),
                'total' => round($total_amount * $product['quantity'], 2),
                'valueDifference' => 0,
                'totalTaxableFees' => 0,
                'netTotal' => round($unit_price * $product['quantity'], 2),
                'itemsDiscount' => 0,
                'unitValue' => array(
                    'currencySold' => $order_info['currency_code'],
                    'amountEGP' => round($unit_price, 2),
                    'amountSold' => round($unit_price, 2),
                    'currencyExchangeRate' => 1
                ),
                'discount' => array(
                    'rate' => 0,
                    'amount' => 0
                ),
                'taxableItems' => array(
                    array(
                        'taxType' => 'T1', // VAT
                        'amount' => round($tax_amount * $product['quantity'], 2),
                        'subType' => 'V009', // 14% VAT
                        'rate' => $tax_rate
                    )
                )
            );
        }

        // إجماليات الفاتورة
        $subtotal = $order_info['total'] - $this->getOrderTaxAmount($order_id);
        $tax_total = $this->getOrderTaxAmount($order_id);

        $invoice_data = array(
            'issuer' => $issuer,
            'receiver' => $receiver,
            'documentType' => 'I', // Invoice
            'documentTypeVersion' => '1.0',
            'dateTimeIssued' => date('c', strtotime($order_info['date_added'])),
            'taxpayerActivityCode' => $this->config->get('eta_activity_code') ?: '4711',
            'internalID' => $order_info['order_id'],
            'purchaseOrderReference' => $order_info['order_id'],
            'purchaseOrderDescription' => 'Order #' . $order_info['order_id'],
            'salesOrderReference' => $order_info['order_id'],
            'salesOrderDescription' => 'Sales Order #' . $order_info['order_id'],
            'proformaInvoiceNumber' => '',
            'payment' => array(
                'bankName' => '',
                'bankAddress' => '',
                'bankAccountNo' => '',
                'bankAccountIBAN' => '',
                'swiftCode' => '',
                'terms' => $order_info['payment_method']
            ),
            'delivery' => array(
                'approach' => $order_info['shipping_method'],
                'packaging' => '',
                'dateValidity' => date('c', strtotime($order_info['date_added'] . ' +30 days')),
                'exportPort' => '',
                'countryOfOrigin' => 'EG',
                'grossWeight' => 0,
                'netWeight' => 0,
                'terms' => ''
            ),
            'invoiceLines' => $invoice_lines,
            'totalDiscountAmount' => 0,
            'totalSalesAmount' => round($subtotal, 2),
            'netAmount' => round($subtotal, 2),
            'taxTotals' => array(
                array(
                    'taxType' => 'T1',
                    'amount' => round($tax_total, 2)
                )
            ),
            'totalAmount' => round($order_info['total'], 2),
            'extraDiscountAmount' => 0,
            'totalItemsDiscountAmount' => 0
        );

        return $invoice_data;
    }

    /**
     * إعداد بيانات الإيصال الإلكتروني
     */
    public function prepareReceiptData($order_id) {
        $this->load->model('sale/order');

        $order_info = $this->model_sale_order->getOrder($order_id);

        if (!$order_info) {
            return false;
        }

        // بيانات الإيصال مبسطة أكثر من الفاتورة
        $receipt_data = array(
            'header' => array(
                'dateTimeIssued' => date('c', strtotime($order_info['date_added'])),
                'receiptNumber' => 'R-' . $order_info['order_id'],
                'uuid' => $this->generateUUID(),
                'previousUUID' => '',
                'referenceOldUUID' => '',
                'currency' => $order_info['currency_code'],
                'exchangeRate' => 1,
                'sOrderNameCode' => '',
                'orderdeliveryMode' => '',
                'grossWeight' => 0,
                'netWeight' => 0
            ),
            'seller' => array(
                'rin' => $this->config->get('eta_tax_id'),
                'companyTradeName' => $this->config->get('config_name'),
                'branchCode' => $this->config->get('eta_branch_id') ?: '0',
                'branchAddress' => array(
                    'country' => 'EG',
                    'governate' => $this->config->get('eta_governate') ?: 'Cairo',
                    'regionCity' => $this->config->get('eta_city') ?: 'Cairo',
                    'street' => $this->config->get('eta_street') ?: 'Main Street',
                    'buildingNumber' => $this->config->get('eta_building') ?: '1'
                ),
                'deviceSerialNumber' => $this->config->get('eta_device_serial') ?: '1',
                'syndicateLicenseNumber' => '',
                'activityCode' => $this->config->get('eta_activity_code') ?: '4711'
            ),
            'buyer' => array(
                'type' => 'P',
                'id' => '000000000000000',
                'name' => $order_info['firstname'] . ' ' . $order_info['lastname'],
                'mobileNumber' => $order_info['telephone'],
                'paymentNumber' => ''
            ),
            'itemData' => array(),
            'totalSales' => round($order_info['total'], 2),
            'totalCommercialDiscount' => 0,
            'totalItemsDiscount' => 0,
            'extraReceiptDiscount' => 0,
            'netAmount' => round($order_info['total'] - $this->getOrderTaxAmount($order_id), 2),
            'feesAmount' => 0,
            'totalAmount' => round($order_info['total'], 2),
            'taxItems' => array(
                array(
                    'taxType' => 'T1',
                    'amount' => round($this->getOrderTaxAmount($order_id), 2),
                    'subType' => 'V009',
                    'rate' => 14
                )
            ),
            'paymentMethod' => 'C', // Cash
            'adjustment' => 0,
            'contractingPartyRin' => '',
            'contractingPartyName' => '',
            'source' => 'POS',
            'metadata' => array(
                'receiptVersion' => '1.0'
            )
        );

        // إضافة عناصر الإيصال
        $order_products = $this->model_sale_order->getOrderProducts($order_id);

        foreach ($order_products as $product) {
            $receipt_data['itemData'][] = array(
                'internalCode' => $product['product_id'],
                'description' => $product['name'],
                'itemType' => 'GS1',
                'itemCode' => $product['model'] ?: $product['product_id'],
                'unitType' => $this->getETAUnitType($product['unit_id']),
                'quantity' => (float)$product['quantity'],
                'unitPrice' => round($product['price'], 2),
                'totalSales' => round($product['total'], 2),
                'total' => round($product['total'], 2),
                'commercialDiscountData' => array(),
                'itemDiscountData' => array(),
                'taxableItems' => array(
                    array(
                        'taxType' => 'T1',
                        'amount' => round($this->calculateProductTax($product['product_id'], $product['price']), 2),
                        'subType' => 'V009',
                        'rate' => $this->calculateProductTaxRate($product['product_id'])
                    )
                )
            );
        }

        return $receipt_data;
    }

    /**
     * إضافة عنصر لطابور ETA
     */
    public function addToQueue($order_id, $type, $data = null) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "eta_queue SET
                            order_id = '" . (int)$order_id . "',
                            type = '" . $this->db->escape($type) . "',
                            data = '" . $this->db->escape(json_encode($data)) . "',
                            status = 'pending',
                            attempts = 0,
                            created_date = NOW(),
                            next_attempt = NOW()");

        return $this->db->getLastId();
    }

    /**
     * معالجة طابور ETA
     */
    public function processQueue($limit = 10) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "eta_queue
                                  WHERE status = 'pending'
                                  AND next_attempt <= NOW()
                                  AND attempts < 5
                                  ORDER BY created_date ASC
                                  LIMIT " . (int)$limit);

        $processed = array('success' => 0, 'failed' => 0);

        foreach ($query->rows as $item) {
            try {
                $this->db->query("UPDATE " . DB_PREFIX . "eta_queue SET
                                    attempts = attempts + 1,
                                    last_attempt = NOW(),
                                    next_attempt = DATE_ADD(NOW(), INTERVAL POW(2, attempts) MINUTE)
                                  WHERE queue_id = '" . (int)$item['queue_id'] . "'");

                // محاولة الإرسال
                $controller = new ControllerExtensionEtaInvoice($this->registry);

                if ($item['type'] == 'invoice') {
                    $result = $controller->sendInvoice();
                } elseif ($item['type'] == 'receipt') {
                    $result = $controller->sendReceipt();
                }

                if ($result && $result['success']) {
                    $this->db->query("UPDATE " . DB_PREFIX . "eta_queue SET
                                        status = 'completed',
                                        completed_date = NOW()
                                      WHERE queue_id = '" . (int)$item['queue_id'] . "'");
                    $processed['success']++;
                } else {
                    if ($item['attempts'] >= 4) {
                        $this->db->query("UPDATE " . DB_PREFIX . "eta_queue SET
                                            status = 'failed'
                                          WHERE queue_id = '" . (int)$item['queue_id'] . "'");
                    }
                    $processed['failed']++;
                }
            } catch (Exception $e) {
                $this->db->query("UPDATE " . DB_PREFIX . "eta_queue SET
                                    error_message = '" . $this->db->escape($e->getMessage()) . "'
                                  WHERE queue_id = '" . (int)$item['queue_id'] . "'");
                $processed['failed']++;
            }
        }

        return $processed;
    }

    /**
     * حفظ استجابة الفاتورة
     */
    public function saveInvoiceResponse($order_id, $response) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "eta_invoices SET
                            order_id = '" . (int)$order_id . "',
                            eta_uuid = '" . $this->db->escape($response['uuid']) . "',
                            submission_uuid = '" . $this->db->escape($response['submissionUuid']) . "',
                            status = 'sent',
                            response_data = '" . $this->db->escape(json_encode($response)) . "',
                            sent_date = NOW()");
    }

    /**
     * حفظ استجابة الإيصال
     */
    public function saveReceiptResponse($order_id, $response) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "eta_receipts SET
                            order_id = '" . (int)$order_id . "',
                            eta_uuid = '" . $this->db->escape($response['uuid']) . "',
                            status = 'sent',
                            response_data = '" . $this->db->escape(json_encode($response)) . "',
                            sent_date = NOW()");
    }

    /**
     * الحصول على إحصائيات ETA
     */
    public function getETAStatistics() {
        $statistics = array();

        // إحصائيات الفواتير
        $query = $this->db->query("SELECT
                                    COUNT(*) as total_invoices,
                                    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent_invoices,
                                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_invoices,
                                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_invoices
                                  FROM " . DB_PREFIX . "eta_invoices");

        if ($query->row) {
            $statistics = array_merge($statistics, $query->row);
        }

        // إحصائيات الإيصالات
        $query = $this->db->query("SELECT
                                    COUNT(*) as total_receipts,
                                    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent_receipts
                                  FROM " . DB_PREFIX . "eta_receipts");

        if ($query->row) {
            $statistics = array_merge($statistics, $query->row);
        }

        // إحصائيات الطابور
        $query = $this->db->query("SELECT COUNT(*) as queue_count FROM " . DB_PREFIX . "eta_queue WHERE status = 'pending'");

        if ($query->row) {
            $statistics['queue_count'] = $query->row['queue_count'];
        }

        // حساب معدل النجاح
        $total = ($statistics['total_invoices'] ?? 0) + ($statistics['total_receipts'] ?? 0);
        $sent = ($statistics['sent_invoices'] ?? 0) + ($statistics['sent_receipts'] ?? 0);

        $statistics['success_rate'] = $total > 0 ? round(($sent / $total) * 100, 2) : 0;

        return $statistics;
    }

    /**
     * وظائف مساعدة
     */

    private function calculateProductTaxRate($product_id) {
        $query = $this->db->query("SELECT tax_class_id FROM " . DB_PREFIX . "product WHERE product_id = '" . (int)$product_id . "'");

        if ($query->row && $query->row['tax_class_id']) {
            // حساب معدل الضريبة من tax_class
            $tax_query = $this->db->query("SELECT rate FROM " . DB_PREFIX . "tax_rate WHERE tax_class_id = '" . (int)$query->row['tax_class_id'] . "' LIMIT 1");

            return $tax_query->row ? $tax_query->row['rate'] : 14; // افتراضي 14%
        }

        return 14; // معدل الضريبة الافتراضي في مصر
    }

    private function calculateProductTax($product_id, $price) {
        $rate = $this->calculateProductTaxRate($product_id);
        return ($price * $rate) / 100;
    }

    private function getOrderTaxAmount($order_id) {
        $query = $this->db->query("SELECT SUM(value) as tax_total FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int)$order_id . "' AND code = 'tax'");

        return $query->row ? $query->row['tax_total'] : 0;
    }

    private function getETAUnitType($unit_id) {
        // تحويل وحدات النظام لوحدات ETA
        $eta_units = array(
            37 => 'PCE', // قطعة
            38 => 'KGM', // كيلو
            39 => 'LTR', // لتر
            40 => 'MTR', // متر
            41 => 'BOX', // صندوق
            42 => 'SET'  // طقم
        );

        return isset($eta_units[$unit_id]) ? $eta_units[$unit_id] : 'PCE';
    }

    private function generateUUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    public function getPendingOrders() {
        $query = $this->db->query("SELECT o.order_id, o.firstname, o.lastname, o.total, o.date_added
                                  FROM " . DB_PREFIX . "order o
                                  LEFT JOIN " . DB_PREFIX . "eta_invoices ei ON (o.order_id = ei.order_id)
                                  WHERE ei.order_id IS NULL
                                  AND o.order_status_id IN (1, 2, 3, 5, 15)
                                  ORDER BY o.date_added DESC
                                  LIMIT 10");

        return $query->rows;
    }

    public function getQueueItems() {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "eta_queue
                                  WHERE status = 'pending'
                                  ORDER BY created_date DESC
                                  LIMIT 20");

        return $query->rows;
    }

    /**
     * مسح الطابور
     */
    public function clearQueue() {
        $this->db->query("DELETE FROM " . DB_PREFIX . "eta_queue WHERE status IN ('pending', 'failed')");
    }

    /**
     * جلب السجلات
     */
    public function getLogs($limit = 100) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "eta_log
            ORDER BY date_added DESC
            LIMIT " . (int)$limit);

        return $query->rows;
    }

    /**
     * إعداد إشعار تعديل الطلب
     */
    public function prepareOrderModificationNote($order_id, $modification_type, $modification_data) {
        $this->load->model('sale/order');

        $order_info = $this->model_sale_order->getOrder($order_id);

        if (!$order_info) {
            return false;
        }

        // جلب الفاتورة الأصلية من ETA
        $original_invoice = $this->getETAInvoiceByOrderId($order_id);

        if (!$original_invoice) {
            return false;
        }

        // إعداد بيانات الإشعار
        $note_data = array(
            'issuer' => $this->getIssuerData(),
            'receiver' => $this->getReceiverData($order_info),
            'documentType' => ($modification_type == 'increase') ? 'D' : 'C', // D = Debit, C = Credit
            'documentTypeVersion' => '1.0',
            'dateTimeIssued' => date('c'),
            'taxpayerActivityCode' => $this->config->get('config_eta_activity_code'),
            'internalID' => 'NOTE-' . $order_id . '-' . time(),
            'references' => array(
                array(
                    'internalReference' => $original_invoice['internal_id'],
                    'uuid' => $original_invoice['eta_uuid']
                )
            ),
            'invoiceLines' => $this->prepareModificationLines($modification_data, $modification_type),
            'totalSalesAmount' => 0,
            'totalDiscountAmount' => 0,
            'netAmount' => 0,
            'taxTotals' => array(),
            'totalAmount' => 0,
            'extraDiscountAmount' => 0,
            'totalItemsDiscountAmount' => 0
        );

        // حساب المبالغ
        $this->calculateNoteTotals($note_data);

        return $note_data;
    }

    /**
     * إعداد بنود التعديل
     */
    private function prepareModificationLines($modification_data, $modification_type) {
        $lines = array();

        foreach ($modification_data as $item) {
            $quantity = ($modification_type == 'increase') ? $item['quantity'] : -$item['quantity'];
            $unit_price = (float)$item['unit_price'];
            $sales_total = $quantity * $unit_price;

            $line = array(
                'description' => $item['description'],
                'itemType' => 'GS1',
                'itemCode' => $item['item_code'],
                'unitType' => $item['unit_type'] ?? 'EA',
                'quantity' => $quantity,
                'unitValue' => array(
                    'currencySold' => 'EGP',
                    'amountEGP' => $unit_price
                ),
                'salesTotal' => $sales_total,
                'total' => $sales_total,
                'valueDifference' => 0,
                'totalTaxableFees' => 0,
                'netTotal' => $sales_total,
                'itemsDiscount' => 0,
                'taxableItems' => array()
            );

            // إضافة الضرائب
            if (isset($item['taxes']) && is_array($item['taxes'])) {
                foreach ($item['taxes'] as $tax) {
                    $line['taxableItems'][] = array(
                        'taxType' => $tax['type'],
                        'amount' => $tax['amount'],
                        'subType' => $tax['sub_type'] ?? 'V001',
                        'rate' => $tax['rate']
                    );
                }
            }

            $lines[] = $line;
        }

        return $lines;
    }

    /**
     * حساب إجماليات الإشعار
     */
    private function calculateNoteTotals(&$note_data) {
        $total_sales = 0;
        $total_discount = 0;
        $tax_totals = array();

        foreach ($note_data['invoiceLines'] as $line) {
            $total_sales += $line['salesTotal'];
            $total_discount += $line['itemsDiscount'];

            // حساب الضرائب
            foreach ($line['taxableItems'] as $tax_item) {
                $tax_type = $tax_item['taxType'];
                if (!isset($tax_totals[$tax_type])) {
                    $tax_totals[$tax_type] = array(
                        'taxType' => $tax_type,
                        'amount' => 0
                    );
                }
                $tax_totals[$tax_type]['amount'] += $tax_item['amount'];
            }
        }

        $note_data['totalSalesAmount'] = $total_sales;
        $note_data['totalDiscountAmount'] = $total_discount;
        $note_data['netAmount'] = $total_sales - $total_discount;
        $note_data['taxTotals'] = array_values($tax_totals);

        $total_tax = array_sum(array_column($tax_totals, 'amount'));
        $note_data['totalAmount'] = $note_data['netAmount'] + $total_tax;
    }

    /**
     * جلب فاتورة ETA بواسطة رقم الطلب
     */
    private function getETAInvoiceByOrderId($order_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "eta_invoices
            WHERE order_id = '" . (int)$order_id . "'
            AND status = 'sent'
            ORDER BY sent_date DESC
            LIMIT 1");

        return $query->num_rows ? $query->row : false;
    }

    /**
     * حفظ استجابة تعديل الطلب
     */
    public function saveOrderModificationResponse($order_id, $note_type, $response) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "eta_modification_note SET
            order_id = '" . (int)$order_id . "',
            note_type = '" . $this->db->escape($note_type) . "',
            eta_uuid = '" . $this->db->escape($response['uuid']) . "',
            internal_id = '" . $this->db->escape($response['internalID']) . "',
            submission_uuid = '" . $this->db->escape($response['submissionUUID']) . "',
            long_id = '" . $this->db->escape($response['longId']) . "',
            hash_key = '" . $this->db->escape($response['hashKey']) . "',
            status = 'sent',
            response_data = '" . $this->db->escape(json_encode($response)) . "',
            date_added = NOW()");
    }

    /**
     * جلب بيانات المُصدر
     */
    private function getIssuerData() {
        return array(
            'address' => array(
                'branchID' => $this->config->get('config_eta_branch_id') ?: '0',
                'country' => 'EG',
                'governate' => $this->config->get('config_eta_governate') ?: 'Cairo',
                'regionCity' => $this->config->get('config_eta_city') ?: 'Cairo',
                'street' => $this->config->get('config_eta_street') ?: 'Main Street',
                'buildingNumber' => $this->config->get('config_eta_building') ?: '1',
                'postalCode' => $this->config->get('config_eta_postal_code') ?: '11511',
                'floor' => $this->config->get('config_eta_floor') ?: '1',
                'room' => $this->config->get('config_eta_room') ?: '1',
                'landmark' => $this->config->get('config_eta_landmark') ?: '',
                'additionalInformation' => $this->config->get('config_eta_additional_info') ?: ''
            ),
            'type' => 'B', // Business
            'id' => $this->config->get('config_eta_taxpayer_id'),
            'name' => $this->config->get('config_name')
        );
    }

    /**
     * جلب بيانات المستقبل
     */
    private function getReceiverData($order_info) {
        $this->load->model('customer/customer');
        $customer_info = $this->model_customer_customer->getCustomer($order_info['customer_id']);

        return array(
            'address' => array(
                'country' => $order_info['shipping_iso_code_2'] ?: 'EG',
                'governate' => $order_info['shipping_zone'] ?: 'Cairo',
                'regionCity' => $order_info['shipping_city'] ?: 'Cairo',
                'street' => $order_info['shipping_address_1'] ?: 'Main Street',
                'buildingNumber' => '1',
                'postalCode' => $order_info['shipping_postcode'] ?: '11511',
                'floor' => '1',
                'room' => '1',
                'landmark' => $order_info['shipping_address_2'] ?: '',
                'additionalInformation' => ''
            ),
            'type' => $customer_info && $customer_info['tax_id'] ? 'B' : 'P', // Business or Person
            'id' => $customer_info['tax_id'] ?? $customer_info['national_id'] ?? '000000000000000',
            'name' => $order_info['firstname'] . ' ' . $order_info['lastname']
        );
    }
}
