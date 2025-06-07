<?php
/**
 * AYM ERP System: ETA Electronic Invoicing Controller
 *
 * تكامل متقدم مع نظام الضرائب المصري - مطور للشركات الحقيقية
 *
 * الميزات المتقدمة:
 * - تكامل كامل مع ETA SDK
 * - إرسال الفواتير الإلكترونية تلقائياً
 * - إرسال الإيصالات الإلكترونية
 * - نظام طابور للإرسال المؤجل
 * - معالجة الأخطاء المتقدمة
 * - تتبع حالة الإرسال
 * - إعادة المحاولة التلقائية
 * - تشفير البيانات الحساسة
 * - سجل مفصل للعمليات
 * - دعم الإشعارات الدائنة والمدينة
 *
 * @package    AYM ERP
 * @author     AYM Development Team
 * @copyright  2024 AYM ERP Systems
 * @license    Commercial License
 * @version    1.0.0
 * @since      2024-01-15
 */

class ControllerExtensionEtaInvoice extends Controller {

    private $eta_api_url = 'https://api.invoicing.eta.gov.eg/api/v1/';
    private $eta_preprod_url = 'https://api.preprod.invoicing.eta.gov.eg/api/v1/';

    /**
     * الشاشة الرئيسية لإدارة ETA
     */
    public function index() {
        $this->load->language('extension/eta/invoice');
        $this->document->setTitle($this->language->get('heading_title'));

        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('access', 'extension/eta/invoice')) {
            $this->session->data['error'] = $this->language->get('error_permission');
            $this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
        }

        // تحميل النماذج المطلوبة
        $this->load->model('extension/eta/invoice');
        $this->load->model('sale/order');

        // معالجة الإجراءات
        if (isset($this->request->post['action'])) {
            $this->processAction();
        }

        // إعداد البيانات للعرض
        $data = $this->prepareViewData();

        // تحميل القوالب
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/eta/invoice', $data));
    }

    /**
     * إرسال فاتورة لـ ETA
     */
    public function sendInvoice() {
        $this->load->language('extension/eta/invoice');
        $this->load->model('extension/eta/invoice');

        $json = array('success' => false);

        if (isset($this->request->post['order_id'])) {
            $order_id = (int)$this->request->post['order_id'];

            try {
                // إعداد بيانات الفاتورة
                $invoice_data = $this->model_extension_eta_invoice->prepareInvoiceData($order_id);

                if ($invoice_data) {
                    // إرسال للـ ETA
                    $result = $this->sendToETA('documents/submit', $invoice_data);

                    if ($result['success']) {
                        // حفظ النتيجة في قاعدة البيانات
                        $this->model_extension_eta_invoice->saveInvoiceResponse($order_id, $result);

                        $json['success'] = true;
                        $json['message'] = $this->language->get('text_invoice_sent_success');
                        $json['eta_uuid'] = $result['uuid'];
                        $json['submission_uuid'] = $result['submissionUuid'];
                    } else {
                        // إضافة للطابور للإرسال لاحقاً
                        $this->model_extension_eta_invoice->addToQueue($order_id, 'invoice', $invoice_data);

                        $json['error'] = $result['error'];
                        $json['queued'] = true;
                    }
                } else {
                    $json['error'] = $this->language->get('error_prepare_invoice_data');
                }
            } catch (Exception $e) {
                // إضافة للطابور في حالة الخطأ
                $this->model_extension_eta_invoice->addToQueue($order_id, 'invoice');

                $json['error'] = $e->getMessage();
                $json['queued'] = true;
            }
        } else {
            $json['error'] = $this->language->get('error_order_id_required');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * إرسال إيصال إلكتروني لـ ETA
     */
    public function sendReceipt() {
        $this->load->language('extension/eta/invoice');
        $this->load->model('extension/eta/invoice');

        $json = array('success' => false);

        if (isset($this->request->post['order_id'])) {
            $order_id = (int)$this->request->post['order_id'];

            try {
                // إعداد بيانات الإيصال
                $receipt_data = $this->model_extension_eta_invoice->prepareReceiptData($order_id);

                if ($receipt_data) {
                    // إرسال للـ ETA
                    $result = $this->sendToETA('receipts/submit', $receipt_data);

                    if ($result['success']) {
                        // حفظ النتيجة في قاعدة البيانات
                        $this->model_extension_eta_invoice->saveReceiptResponse($order_id, $result);

                        $json['success'] = true;
                        $json['message'] = $this->language->get('text_receipt_sent_success');
                        $json['eta_uuid'] = $result['uuid'];
                    } else {
                        // إضافة للطابور للإرسال لاحقاً
                        $this->model_extension_eta_invoice->addToQueue($order_id, 'receipt', $receipt_data);

                        $json['error'] = $result['error'];
                        $json['queued'] = true;
                    }
                } else {
                    $json['error'] = $this->language->get('error_prepare_receipt_data');
                }
            } catch (Exception $e) {
                // إضافة للطابور في حالة الخطأ
                $this->model_extension_eta_invoice->addToQueue($order_id, 'receipt');

                $json['error'] = $e->getMessage();
                $json['queued'] = true;
            }
        } else {
            $json['error'] = $this->language->get('error_order_id_required');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * معالجة طابور ETA
     */
    public function processQueue() {
        $this->load->language('extension/eta/invoice');
        $this->load->model('extension/eta/invoice');

        $json = array('success' => false);

        try {
            $processed = $this->model_extension_eta_invoice->processQueue();

            $json['success'] = true;
            $json['message'] = sprintf($this->language->get('text_queue_processed'), $processed['success'], $processed['failed']);
            $json['processed'] = $processed;
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * التحقق من حالة الفاتورة في ETA
     */
    public function checkInvoiceStatus() {
        $this->load->language('extension/eta/invoice');
        $this->load->model('extension/eta/invoice');

        $json = array('success' => false);

        if (isset($this->request->get['uuid'])) {
            $uuid = $this->request->get['uuid'];

            try {
                $result = $this->sendToETA('documents/' . $uuid . '/details', null, 'GET');

                if ($result['success']) {
                    $json['success'] = true;
                    $json['status'] = $result['status'];
                    $json['details'] = $result['data'];
                } else {
                    $json['error'] = $result['error'];
                }
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = $this->language->get('error_uuid_required');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * إنشاء إشعار دائن/مدين
     */
    public function createCreditDebitNote() {
        $this->load->language('extension/eta/invoice');
        $this->load->model('extension/eta/invoice');

        $json = array('success' => false);

        if (isset($this->request->post['order_id']) && isset($this->request->post['type'])) {
            $order_id = (int)$this->request->post['order_id'];
            $type = $this->request->post['type']; // 'credit' or 'debit'
            $reason = isset($this->request->post['reason']) ? $this->request->post['reason'] : '';
            $amount = isset($this->request->post['amount']) ? (float)$this->request->post['amount'] : 0;

            try {
                // إعداد بيانات الإشعار
                $note_data = $this->model_extension_eta_invoice->prepareCreditDebitNoteData($order_id, $type, $reason, $amount);

                if ($note_data) {
                    // إرسال للـ ETA
                    $result = $this->sendToETA('documents/submit', $note_data);

                    if ($result['success']) {
                        // حفظ النتيجة في قاعدة البيانات
                        $this->model_extension_eta_invoice->saveCreditDebitNoteResponse($order_id, $type, $result);

                        $json['success'] = true;
                        $json['message'] = $this->language->get('text_note_sent_success');
                        $json['eta_uuid'] = $result['uuid'];
                    } else {
                        $json['error'] = $result['error'];
                    }
                } else {
                    $json['error'] = $this->language->get('error_prepare_note_data');
                }
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        } else {
            $json['error'] = $this->language->get('error_required_fields');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * الحصول على إحصائيات ETA
     */
    public function getStatistics() {
        $this->load->language('extension/eta/invoice');
        $this->load->model('extension/eta/invoice');

        $json = array();

        $statistics = $this->model_extension_eta_invoice->getETAStatistics();

        $json['statistics'] = array(
            'total_invoices' => $statistics['total_invoices'] ?? 0,
            'sent_invoices' => $statistics['sent_invoices'] ?? 0,
            'pending_invoices' => $statistics['pending_invoices'] ?? 0,
            'failed_invoices' => $statistics['failed_invoices'] ?? 0,
            'total_receipts' => $statistics['total_receipts'] ?? 0,
            'sent_receipts' => $statistics['sent_receipts'] ?? 0,
            'queue_count' => $statistics['queue_count'] ?? 0,
            'success_rate' => $statistics['success_rate'] ?? 0
        );

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * إعدادات ETA
     */
    public function settings() {
        $this->load->language('extension/eta/invoice');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateSettings()) {
            $this->load->model('setting/setting');

            $this->model_setting_setting->editSetting('eta', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success_settings');

            $this->response->redirect($this->url->link('extension/eta/invoice/settings', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data = $this->prepareSettingsData();

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/eta/settings', $data));
    }

    /**
     * إرسال البيانات لـ ETA
     */
    private function sendToETA($endpoint, $data = null, $method = 'POST') {
        $url = ($this->config->get('eta_environment') == 'production') ?
            $this->eta_api_url : $this->eta_preprod_url;

        $url .= $endpoint;

        // إعداد headers
        $headers = array(
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $this->getAccessToken()
        );

        // إعداد cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        if ($method == 'POST' && $data) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        // معالجة الاستجابة
        if ($error) {
            return array('success' => false, 'error' => 'cURL Error: ' . $error);
        }

        $response_data = json_decode($response, true);

        if ($http_code >= 200 && $http_code < 300) {
            return array('success' => true, 'data' => $response_data);
        } else {
            $error_message = isset($response_data['error']) ? $response_data['error'] : 'HTTP Error: ' . $http_code;
            return array('success' => false, 'error' => $error_message, 'http_code' => $http_code);
        }
    }

    /**
     * الحصول على Access Token
     */
    private function getAccessToken() {
        // التحقق من وجود token صالح
        $token = $this->config->get('eta_access_token');
        $expires = $this->config->get('eta_token_expires');

        if ($token && $expires && time() < $expires) {
            return $token;
        }

        // طلب token جديد
        $auth_data = array(
            'client_id' => $this->config->get('eta_client_id'),
            'client_secret' => $this->config->get('eta_client_secret'),
            'grant_type' => 'client_credentials'
        );

        $result = $this->sendToETA('auth/token', $auth_data);

        if ($result['success'] && isset($result['data']['access_token'])) {
            $token = $result['data']['access_token'];
            $expires = time() + ($result['data']['expires_in'] - 300); // 5 دقائق أمان

            // حفظ Token
            $this->load->model('setting/setting');
            $this->model_setting_setting->editSetting('eta', array(
                'eta_access_token' => $token,
                'eta_token_expires' => $expires
            ));

            return $token;
        }

        throw new Exception('Failed to get ETA access token');
    }

    /**
     * معالجة الإجراءات
     */
    private function processAction() {
        $action = $this->request->post['action'];

        switch ($action) {
            case 'send_invoice':
                $this->sendInvoice();
                break;
            case 'send_receipt':
                $this->sendReceipt();
                break;
            case 'process_queue':
                $this->processQueue();
                break;
            default:
                break;
        }
    }

    /**
     * إعداد البيانات للعرض
     */
    private function prepareViewData() {
        $data = array();

        // معلومات أساسية
        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_list'] = $this->language->get('text_list');

        // الإحصائيات
        $data['statistics'] = $this->model_extension_eta_invoice->getETAStatistics();

        // الطلبات المعلقة
        $data['pending_orders'] = $this->model_extension_eta_invoice->getPendingOrders();

        // طابور ETA
        $data['queue_items'] = $this->model_extension_eta_invoice->getQueueItems();

        // الروابط
        $data['user_token'] = $this->session->data['user_token'];

        return $data;
    }

    /**
     * إعداد بيانات الإعدادات
     */
    private function prepareSettingsData() {
        $data = array();

        // معلومات أساسية
        $data['heading_title'] = $this->language->get('heading_title_settings');

        // الإعدادات الحالية
        $data['eta_environment'] = $this->config->get('eta_environment');
        $data['eta_client_id'] = $this->config->get('eta_client_id');
        $data['eta_client_secret'] = $this->config->get('eta_client_secret');
        $data['eta_auto_send'] = $this->config->get('eta_auto_send');
        $data['eta_auto_receipt'] = $this->config->get('eta_auto_receipt');

        // خيارات البيئة
        $data['environments'] = array(
            'preprod' => $this->language->get('text_preprod'),
            'production' => $this->language->get('text_production')
        );

        return $data;
    }

    /**
     * اختبار الاتصال مع ETA
     */
    public function testConnection() {
        $this->load->language('extension/eta/invoice');

        $json = array('success' => false);

        try {
            // اختبار الاتصال بـ ETA
            $result = $this->sendToETA('auth/token', array(
                'client_id' => $this->config->get('config_eta_client_id'),
                'client_secret' => $this->config->get('config_eta_client_secret'),
                'grant_type' => 'client_credentials'
            ));

            if ($result['success']) {
                $json['success'] = true;
                $json['message'] = $this->language->get('text_connection_success');
            } else {
                $json['error'] = $result['error'];
            }
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * مسح الطابور
     */
    public function clearQueue() {
        $this->load->language('extension/eta/invoice');
        $this->load->model('extension/eta/invoice');

        $json = array('success' => false);

        try {
            $this->model_extension_eta_invoice->clearQueue();

            $json['success'] = true;
            $json['message'] = $this->language->get('text_queue_cleared');
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * عرض السجلات
     */
    public function logs() {
        $this->load->language('extension/eta/invoice');
        $this->load->model('extension/eta/invoice');

        $this->document->setTitle($this->language->get('heading_title') . ' - ' . $this->language->get('text_logs'));

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/eta/invoice', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_logs'),
            'href' => $this->url->link('extension/eta/invoice/logs', 'user_token=' . $this->session->data['user_token'], true)
        );

        // جلب السجلات
        $data['logs'] = $this->model_extension_eta_invoice->getLogs();

        $data['back'] = $this->url->link('extension/eta/invoice', 'user_token=' . $this->session->data['user_token'], true);

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/eta/logs', $data));
    }

    /**
     * إرسال إشعار دائن/مدين للطلبات المعدلة
     */
    public function sendOrderModificationNote() {
        $this->load->language('extension/eta/invoice');
        $this->load->model('extension/eta/invoice');

        $json = array('success' => false);

        if (isset($this->request->post['order_id']) && isset($this->request->post['modification_type'])) {
            $order_id = (int)$this->request->post['order_id'];
            $modification_type = $this->request->post['modification_type']; // 'increase' or 'decrease'
            $modification_data = $this->request->post['modification_data'] ?? array();

            try {
                // إعداد بيانات الإشعار للطلب المعدل
                $note_data = $this->model_extension_eta_invoice->prepareOrderModificationNote($order_id, $modification_type, $modification_data);

                if ($note_data) {
                    // تحديد نوع الإشعار
                    $note_type = ($modification_type == 'increase') ? 'debit' : 'credit';

                    // إرسال للـ ETA
                    $result = $this->sendToETA('documents/submit', $note_data);

                    if ($result['success']) {
                        // حفظ النتيجة في قاعدة البيانات
                        $this->model_extension_eta_invoice->saveOrderModificationResponse($order_id, $note_type, $result);

                        $json['success'] = true;
                        $json['message'] = $this->language->get('text_modification_note_sent_success');
                        $json['eta_uuid'] = $result['uuid'];
                    } else {
                        // إضافة للطابور للإرسال لاحقاً
                        $this->model_extension_eta_invoice->addToQueue($order_id, 'modification_note', $note_data);

                        $json['error'] = $result['error'];
                        $json['queued'] = true;
                    }
                } else {
                    $json['error'] = $this->language->get('error_prepare_modification_note');
                }
            } catch (Exception $e) {
                // إضافة للطابور في حالة الخطأ
                $this->model_extension_eta_invoice->addToQueue($order_id, 'modification_note');

                $json['error'] = $e->getMessage();
                $json['queued'] = true;
            }
        } else {
            $json['error'] = $this->language->get('error_required_fields');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * التحقق من صحة الإعدادات
     */
    private function validateSettings() {
        if (empty($this->request->post['eta_client_id'])) {
            $this->error['client_id'] = $this->language->get('error_client_id');
        }

        if (empty($this->request->post['eta_client_secret'])) {
            $this->error['client_secret'] = $this->language->get('error_client_secret');
        }

        return !$this->error;
    }
}
