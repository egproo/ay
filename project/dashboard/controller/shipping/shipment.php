<?php
/**
 * Controller: Shipment Management
 * نظام إدارة الشحنات المتقدم
 *
 * يوفر إدارة شاملة للشحنات مع تتبع متقدم وتكامل مع شركات الشحن
 *
 * @package    AYM ERP
 * @author     AYM ERP Development Team
 * @copyright  2024 AYM ERP
 * @license    Commercial License
 * @version    1.0.0
 * @link       https://aym-erp.com
 * @since      1.0.0
 */

class ControllerShippingShipment extends Controller {

    /**
     * عرض الصفحة الرئيسية لإدارة الشحنات
     */
    public function index() {
        $this->load->language('shipping/shipment');
        $this->load->model('shipping/shipment');
        $this->load->model('sale/order');
        $this->load->model('shipping/carrier');

        $this->document->setTitle($this->language->get('heading_title'));

        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('access', 'shipping/shipment')) {
            $this->session->data['error'] = $this->language->get('error_permission');
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data = array();

        // إعداد البيانات الأساسية
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('shipping/shipment', 'user_token=' . $this->session->data['user_token'], true)
        );

        // الروابط والأزرار
        $data['add'] = $this->url->link('shipping/shipment/add', 'user_token=' . $this->session->data['user_token'], true);
        $data['delete'] = $this->url->link('shipping/shipment/delete', 'user_token=' . $this->session->data['user_token'], true);
        $data['user_token'] = $this->session->data['user_token'];

        // إحصائيات الشحنات
        $data['statistics'] = $this->model_shipping_shipment->getShipmentStatistics();

        // شركات الشحن
        $data['carriers'] = $this->model_shipping_carrier->getCarriers();

        // حالات الشحن
        $data['statuses'] = $this->model_shipping_shipment->getShipmentStatuses();

        // رسائل النجاح والخطأ
        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        }

        if (isset($this->session->data['error'])) {
            $data['error'] = $this->session->data['error'];
            unset($this->session->data['error']);
        }

        // تحميل النصوص
        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_list'] = $this->language->get('text_list');
        $data['text_no_results'] = $this->language->get('text_no_results');
        $data['text_confirm'] = $this->language->get('text_confirm');
        $data['text_loading'] = $this->language->get('text_loading');

        // أعمدة الجدول
        $data['column_shipment_number'] = $this->language->get('column_shipment_number');
        $data['column_order_number'] = $this->language->get('column_order_number');
        $data['column_customer'] = $this->language->get('column_customer');
        $data['column_carrier'] = $this->language->get('column_carrier');
        $data['column_status'] = $this->language->get('column_status');
        $data['column_date_shipped'] = $this->language->get('column_date_shipped');
        $data['column_tracking_number'] = $this->language->get('column_tracking_number');
        $data['column_action'] = $this->language->get('column_action');

        // أزرار
        $data['button_add'] = $this->language->get('button_add');
        $data['button_edit'] = $this->language->get('button_edit');
        $data['button_delete'] = $this->language->get('button_delete');
        $data['button_filter'] = $this->language->get('button_filter');
        $data['button_export'] = $this->language->get('button_export');
        $data['button_track'] = $this->language->get('button_track');
        $data['button_print_label'] = $this->language->get('button_print_label');

        // نصوص الفلاتر
        $data['entry_shipment_number'] = $this->language->get('entry_shipment_number');
        $data['entry_order_number'] = $this->language->get('entry_order_number');
        $data['entry_customer'] = $this->language->get('entry_customer');
        $data['entry_carrier'] = $this->language->get('entry_carrier');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_date_from'] = $this->language->get('entry_date_from');
        $data['entry_date_to'] = $this->language->get('entry_date_to');

        // روابط AJAX
        $data['ajax_shipments_url'] = $this->url->link('shipping/shipment/getShipments', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_track_url'] = $this->url->link('shipping/shipment/trackShipment', 'user_token=' . $this->session->data['user_token'], true);
        $data['ajax_update_status_url'] = $this->url->link('shipping/shipment/updateStatus', 'user_token=' . $this->session->data['user_token'], true);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('shipping/shipment', $data));
    }

    /**
     * إضافة شحنة جديدة
     */
    public function add() {
        $this->load->language('shipping/shipment');
        $this->load->model('shipping/shipment');
        $this->load->model('sale/order');
        $this->load->model('shipping/carrier');

        $this->document->setTitle($this->language->get('heading_title'));

        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('modify', 'shipping/shipment')) {
            $this->session->data['error'] = $this->language->get('error_permission');
            $this->response->redirect($this->url->link('shipping/shipment', 'user_token=' . $this->session->data['user_token'], true));
        }

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $shipment_id = $this->model_shipping_shipment->addShipment($this->request->post);

            $this->session->data['success'] = $this->language->get('text_success_add');

            $url = '';
            if (isset($this->request->get['sort'])) {
                $url .= '&sort=' . $this->request->get['sort'];
            }
            if (isset($this->request->get['order'])) {
                $url .= '&order=' . $this->request->get['order'];
            }
            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }

            $this->response->redirect($this->url->link('shipping/shipment', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    /**
     * تعديل شحنة موجودة
     */
    public function edit() {
        $this->load->language('shipping/shipment');
        $this->load->model('shipping/shipment');
        $this->load->model('sale/order');
        $this->load->model('shipping/carrier');

        $this->document->setTitle($this->language->get('heading_title'));

        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('modify', 'shipping/shipment')) {
            $this->session->data['error'] = $this->language->get('error_permission');
            $this->response->redirect($this->url->link('shipping/shipment', 'user_token=' . $this->session->data['user_token'], true));
        }

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_shipping_shipment->editShipment($this->request->get['shipment_id'], $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success_edit');

            $url = '';
            if (isset($this->request->get['sort'])) {
                $url .= '&sort=' . $this->request->get['sort'];
            }
            if (isset($this->request->get['order'])) {
                $url .= '&order=' . $this->request->get['order'];
            }
            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }

            $this->response->redirect($this->url->link('shipping/shipment', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    /**
     * حذف شحنة
     */
    public function delete() {
        $this->load->language('shipping/shipment');
        $this->load->model('shipping/shipment');

        // التحقق من الصلاحيات
        if (!$this->user->hasPermission('modify', 'shipping/shipment')) {
            $this->session->data['error'] = $this->language->get('error_permission');
        } else {
            if (isset($this->request->post['selected']) && $this->validateDelete()) {
                foreach ($this->request->post['selected'] as $shipment_id) {
                    $this->model_shipping_shipment->deleteShipment($shipment_id);
                }

                $this->session->data['success'] = $this->language->get('text_success_delete');

                $url = '';
                if (isset($this->request->get['sort'])) {
                    $url .= '&sort=' . $this->request->get['sort'];
                }
                if (isset($this->request->get['order'])) {
                    $url .= '&order=' . $this->request->get['order'];
                }
                if (isset($this->request->get['page'])) {
                    $url .= '&page=' . $this->request->get['page'];
                }
            }
        }

        $this->response->redirect($this->url->link('shipping/shipment', 'user_token=' . $this->session->data['user_token'] . $url, true));
    }

    /**
     * نموذج إضافة/تعديل الشحنة
     */
    protected function getForm() {
        $data = array();

        // رسائل الخطأ
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['order_id'])) {
            $data['error_order_id'] = $this->error['order_id'];
        } else {
            $data['error_order_id'] = '';
        }

        if (isset($this->error['carrier_id'])) {
            $data['error_carrier_id'] = $this->error['carrier_id'];
        } else {
            $data['error_carrier_id'] = '';
        }

        // مسار التنقل
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('shipping/shipment', 'user_token=' . $this->session->data['user_token'], true)
        );

        // تحديد نوع العملية
        if (!isset($this->request->get['shipment_id'])) {
            $data['action'] = $this->url->link('shipping/shipment/add', 'user_token=' . $this->session->data['user_token'], true);
            $data['heading_title'] = $this->language->get('text_add');
        } else {
            $data['action'] = $this->url->link('shipping/shipment/edit', 'user_token=' . $this->session->data['user_token'] . '&shipment_id=' . $this->request->get['shipment_id'], true);
            $data['heading_title'] = $this->language->get('text_edit');
        }

        $data['cancel'] = $this->url->link('shipping/shipment', 'user_token=' . $this->session->data['user_token'], true);
        $data['user_token'] = $this->session->data['user_token'];

        // بيانات الشحنة
        if (isset($this->request->get['shipment_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $shipment_info = $this->model_shipping_shipment->getShipment($this->request->get['shipment_id']);
        }

        // الحقول
        $fields = array(
            'order_id', 'carrier_id', 'tracking_number', 'shipping_method',
            'weight', 'dimensions', 'insurance_value', 'delivery_instructions',
            'pickup_date', 'estimated_delivery', 'shipping_cost', 'status'
        );

        foreach ($fields as $field) {
            if (isset($this->request->post[$field])) {
                $data[$field] = $this->request->post[$field];
            } elseif (!empty($shipment_info)) {
                $data[$field] = $shipment_info[$field];
            } else {
                $data[$field] = '';
            }
        }

        // قوائم البيانات
        $data['orders'] = $this->model_sale_order->getOrdersForShipping();
        $data['carriers'] = $this->model_shipping_carrier->getCarriers();
        $data['statuses'] = $this->model_shipping_shipment->getShipmentStatuses();

        // النصوص
        $this->loadFormTexts($data);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('shipping/shipment_form', $data));
    }

    /**
     * تحميل نصوص النموذج
     */
    private function loadFormTexts(&$data) {
        $texts = array(
            'text_form', 'text_enabled', 'text_disabled', 'text_none', 'text_select',
            'entry_order', 'entry_carrier', 'entry_tracking_number', 'entry_shipping_method',
            'entry_weight', 'entry_dimensions', 'entry_insurance_value', 'entry_delivery_instructions',
            'entry_pickup_date', 'entry_estimated_delivery', 'entry_shipping_cost', 'entry_status',
            'button_save', 'button_cancel', 'help_tracking_number', 'help_dimensions'
        );

        foreach ($texts as $text) {
            $data[$text] = $this->language->get($text);
        }
    }

    /**
     * جلب الشحنات عبر AJAX
     */
    public function getShipments() {
        $this->load->language('shipping/shipment');
        $this->load->model('shipping/shipment');

        if (!$this->user->hasPermission('access', 'shipping/shipment')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $filter_data = array(
                'filter_shipment_number' => isset($this->request->post['filter_shipment_number']) ? $this->request->post['filter_shipment_number'] : '',
                'filter_order_number' => isset($this->request->post['filter_order_number']) ? $this->request->post['filter_order_number'] : '',
                'filter_customer' => isset($this->request->post['filter_customer']) ? $this->request->post['filter_customer'] : '',
                'filter_carrier_id' => isset($this->request->post['filter_carrier_id']) ? $this->request->post['filter_carrier_id'] : '',
                'filter_status' => isset($this->request->post['filter_status']) ? $this->request->post['filter_status'] : '',
                'filter_date_from' => isset($this->request->post['filter_date_from']) ? $this->request->post['filter_date_from'] : '',
                'filter_date_to' => isset($this->request->post['filter_date_to']) ? $this->request->post['filter_date_to'] : '',
                'sort' => isset($this->request->post['sort']) ? $this->request->post['sort'] : 'date_added',
                'order' => isset($this->request->post['order']) ? $this->request->post['order'] : 'DESC',
                'start' => isset($this->request->post['start']) ? $this->request->post['start'] : 0,
                'limit' => isset($this->request->post['limit']) ? $this->request->post['limit'] : 20
            );

            $shipments = $this->model_shipping_shipment->getShipments($filter_data);
            $total = $this->model_shipping_shipment->getTotalShipments($filter_data);

            $json['shipments'] = array();

            foreach ($shipments as $shipment) {
                $json['shipments'][] = array(
                    'shipment_id' => $shipment['shipment_id'],
                    'shipment_number' => $shipment['shipment_number'],
                    'order_number' => $shipment['order_number'],
                    'customer_name' => $shipment['customer_name'],
                    'carrier_name' => $shipment['carrier_name'],
                    'status' => $shipment['status'],
                    'status_text' => $this->language->get('text_status_' . $shipment['status']),
                    'date_shipped' => date($this->language->get('date_format_short'), strtotime($shipment['date_shipped'])),
                    'tracking_number' => $shipment['tracking_number'],
                    'shipping_cost' => $this->currency->format($shipment['shipping_cost'], $this->config->get('config_currency')),
                    'edit' => $this->url->link('shipping/shipment/edit', 'user_token=' . $this->session->data['user_token'] . '&shipment_id=' . $shipment['shipment_id'], true),
                    'track' => $this->url->link('shipping/shipment/track', 'user_token=' . $this->session->data['user_token'] . '&shipment_id=' . $shipment['shipment_id'], true),
                    'print_label' => $this->url->link('shipping/shipment/printLabel', 'user_token=' . $this->session->data['user_token'] . '&shipment_id=' . $shipment['shipment_id'], true)
                );
            }

            $json['total'] = $total;
            $json['success'] = true;
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * تتبع الشحنة
     */
    public function trackShipment() {
        $this->load->language('shipping/shipment');
        $this->load->model('shipping/shipment');
        $this->load->model('shipping/carrier');

        $json = array();

        if (!$this->user->hasPermission('access', 'shipping/shipment')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $shipment_id = isset($this->request->post['shipment_id']) ? (int)$this->request->post['shipment_id'] : 0;

            if (!$shipment_id) {
                $json['error'] = $this->language->get('error_shipment_id_required');
            } else {
                try {
                    $tracking_info = $this->model_shipping_shipment->getTrackingInfo($shipment_id);

                    if ($tracking_info) {
                        $json['success'] = true;
                        $json['tracking_data'] = $tracking_info;
                    } else {
                        $json['error'] = $this->language->get('error_tracking_not_available');
                    }
                } catch (Exception $e) {
                    $json['error'] = $this->language->get('error_tracking_failed') . ': ' . $e->getMessage();
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * تحديث حالة الشحنة
     */
    public function updateStatus() {
        $this->load->language('shipping/shipment');
        $this->load->model('shipping/shipment');

        $json = array();

        if (!$this->user->hasPermission('modify', 'shipping/shipment')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $shipment_id = isset($this->request->post['shipment_id']) ? (int)$this->request->post['shipment_id'] : 0;
            $status = isset($this->request->post['status']) ? $this->request->post['status'] : '';
            $comment = isset($this->request->post['comment']) ? $this->request->post['comment'] : '';

            if (!$shipment_id) {
                $json['error'] = $this->language->get('error_shipment_id_required');
            } elseif (!$status) {
                $json['error'] = $this->language->get('error_status_required');
            } else {
                try {
                    $updated = $this->model_shipping_shipment->updateShipmentStatus($shipment_id, $status, $comment);

                    if ($updated) {
                        $json['success'] = $this->language->get('text_success_status_update');
                    } else {
                        $json['error'] = $this->language->get('error_update_failed');
                    }
                } catch (Exception $e) {
                    $json['error'] = $this->language->get('error_update_failed') . ': ' . $e->getMessage();
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * طباعة ملصق الشحن
     */
    public function printLabel() {
        $this->load->language('shipping/shipment');
        $this->load->model('shipping/shipment');

        if (!$this->user->hasPermission('access', 'shipping/shipment')) {
            $this->session->data['error'] = $this->language->get('error_permission');
            $this->response->redirect($this->url->link('shipping/shipment', 'user_token=' . $this->session->data['user_token'], true));
        }

        $shipment_id = isset($this->request->get['shipment_id']) ? (int)$this->request->get['shipment_id'] : 0;

        if (!$shipment_id) {
            $this->session->data['error'] = $this->language->get('error_shipment_id_required');
            $this->response->redirect($this->url->link('shipping/shipment', 'user_token=' . $this->session->data['user_token'], true));
        }

        try {
            $label_data = $this->model_shipping_shipment->generateShippingLabel($shipment_id);

            if ($label_data) {
                $this->generateLabelPDF($label_data);
            } else {
                $this->session->data['error'] = $this->language->get('error_label_generation_failed');
                $this->response->redirect($this->url->link('shipping/shipment', 'user_token=' . $this->session->data['user_token'], true));
            }
        } catch (Exception $e) {
            $this->session->data['error'] = $this->language->get('error_label_generation_failed') . ': ' . $e->getMessage();
            $this->response->redirect($this->url->link('shipping/shipment', 'user_token=' . $this->session->data['user_token'], true));
        }
    }

    /**
     * إنشاء PDF لملصق الشحن
     */
    private function generateLabelPDF($label_data) {
        require_once(DIR_SYSTEM . 'library/tcpdf/tcpdf.php');

        $pdf = new TCPDF('P', 'mm', array(100, 150), true, 'UTF-8', false);

        $pdf->SetCreator('AYM ERP');
        $pdf->SetAuthor('AYM ERP');
        $pdf->SetTitle('Shipping Label');
        $pdf->SetSubject('Shipping Label');

        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(5, 5, 5);
        $pdf->SetAutoPageBreak(FALSE);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        $pdf->AddPage();

        // محتوى الملصق
        $pdf->SetFont('dejavusans', 'B', 12);
        $pdf->Cell(0, 8, $label_data['carrier_name'], 0, 1, 'C');

        $pdf->SetFont('dejavusans', '', 10);
        $pdf->Cell(0, 6, 'Tracking: ' . $label_data['tracking_number'], 0, 1, 'L');
        $pdf->Ln(3);

        // عنوان المرسل
        $pdf->SetFont('dejavusans', 'B', 9);
        $pdf->Cell(0, 5, 'FROM:', 0, 1, 'L');
        $pdf->SetFont('dejavusans', '', 8);
        $pdf->MultiCell(0, 4, $label_data['sender_address'], 0, 'L');
        $pdf->Ln(3);

        // عنوان المستلم
        $pdf->SetFont('dejavusans', 'B', 9);
        $pdf->Cell(0, 5, 'TO:', 0, 1, 'L');
        $pdf->SetFont('dejavusans', '', 8);
        $pdf->MultiCell(0, 4, $label_data['recipient_address'], 0, 'L');

        // الباركود
        if (!empty($label_data['barcode'])) {
            $pdf->write1DBarcode($label_data['tracking_number'], 'C128', 10, 80, 80, 15, 0.4, array('position'=>'S', 'border'=>false, 'padding'=>0, 'fgcolor'=>array(0,0,0), 'bgcolor'=>array(255,255,255)));
        }

        $filename = 'shipping_label_' . $label_data['shipment_number'] . '.pdf';

        $this->response->addHeader('Content-Type: application/pdf');
        $this->response->addHeader('Content-Disposition: attachment; filename="' . $filename . '"');
        $this->response->setOutput($pdf->Output('', 'S'));
    }

    /**
     * تصدير الشحنات
     */
    public function export() {
        $this->load->language('shipping/shipment');
        $this->load->model('shipping/shipment');

        if (!$this->user->hasPermission('access', 'shipping/shipment')) {
            $this->session->data['error'] = $this->language->get('error_permission');
            $this->response->redirect($this->url->link('shipping/shipment', 'user_token=' . $this->session->data['user_token'], true));
        }

        $filter_data = array(
            'filter_shipment_number' => isset($this->request->get['filter_shipment_number']) ? $this->request->get['filter_shipment_number'] : '',
            'filter_order_number' => isset($this->request->get['filter_order_number']) ? $this->request->get['filter_order_number'] : '',
            'filter_customer' => isset($this->request->get['filter_customer']) ? $this->request->get['filter_customer'] : '',
            'filter_carrier_id' => isset($this->request->get['filter_carrier_id']) ? $this->request->get['filter_carrier_id'] : '',
            'filter_status' => isset($this->request->get['filter_status']) ? $this->request->get['filter_status'] : '',
            'filter_date_from' => isset($this->request->get['filter_date_from']) ? $this->request->get['filter_date_from'] : '',
            'filter_date_to' => isset($this->request->get['filter_date_to']) ? $this->request->get['filter_date_to'] : ''
        );

        $shipments = $this->model_shipping_shipment->getShipments($filter_data);

        $csv_data = "Shipment Number,Order Number,Customer,Carrier,Status,Date Shipped,Tracking Number,Shipping Cost\n";

        foreach ($shipments as $shipment) {
            $csv_data .= '"' . $shipment['shipment_number'] . '",';
            $csv_data .= '"' . $shipment['order_number'] . '",';
            $csv_data .= '"' . $shipment['customer_name'] . '",';
            $csv_data .= '"' . $shipment['carrier_name'] . '",';
            $csv_data .= '"' . $shipment['status'] . '",';
            $csv_data .= '"' . $shipment['date_shipped'] . '",';
            $csv_data .= '"' . $shipment['tracking_number'] . '",';
            $csv_data .= '"' . $shipment['shipping_cost'] . '"' . "\n";
        }

        $filename = 'shipments_export_' . date('Y-m-d_H-i-s') . '.csv';

        $this->response->addHeader('Content-Type: text/csv; charset=utf-8');
        $this->response->addHeader('Content-Disposition: attachment; filename="' . $filename . '"');
        $this->response->setOutput("\xEF\xBB\xBF" . $csv_data);
    }

    /**
     * التحقق من صحة النموذج
     */
    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'shipping/shipment')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['order_id'])) {
            $this->error['order_id'] = $this->language->get('error_order_required');
        }

        if (empty($this->request->post['carrier_id'])) {
            $this->error['carrier_id'] = $this->language->get('error_carrier_required');
        }

        if (!empty($this->request->post['weight']) && !is_numeric($this->request->post['weight'])) {
            $this->error['weight'] = $this->language->get('error_weight_numeric');
        }

        if (!empty($this->request->post['shipping_cost']) && !is_numeric($this->request->post['shipping_cost'])) {
            $this->error['shipping_cost'] = $this->language->get('error_shipping_cost_numeric');
        }

        return !$this->error;
    }

    /**
     * التحقق من صحة الحذف
     */
    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'shipping/shipment')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        $this->load->model('shipping/shipment');

        foreach ($this->request->post['selected'] as $shipment_id) {
            $shipment_info = $this->model_shipping_shipment->getShipment($shipment_id);

            if ($shipment_info && $shipment_info['status'] == 'delivered') {
                $this->error['warning'] = $this->language->get('error_cannot_delete_delivered');
                break;
            }
        }

        return !$this->error;
    }

    /**
     * إنشاء شحنة من طلب
     */
    public function createFromOrder() {
        $this->load->language('shipping/shipment');
        $this->load->model('shipping/shipment');
        $this->load->model('sale/order');

        $json = array();

        if (!$this->user->hasPermission('modify', 'shipping/shipment')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $order_id = isset($this->request->post['order_id']) ? (int)$this->request->post['order_id'] : 0;

            if (!$order_id) {
                $json['error'] = $this->language->get('error_order_id_required');
            } else {
                try {
                    $order_info = $this->model_sale_order->getOrder($order_id);

                    if (!$order_info) {
                        $json['error'] = $this->language->get('error_order_not_found');
                    } elseif ($order_info['order_status_id'] != $this->config->get('config_complete_status_id')) {
                        $json['error'] = $this->language->get('error_order_not_complete');
                    } else {
                        // التحقق من وجود شحنة مسبقة
                        $existing_shipment = $this->model_shipping_shipment->getShipmentByOrderId($order_id);

                        if ($existing_shipment) {
                            $json['error'] = $this->language->get('error_shipment_already_exists');
                        } else {
                            $shipment_data = array(
                                'order_id' => $order_id,
                                'customer_id' => $order_info['customer_id'],
                                'shipping_address' => $order_info['shipping_address_1'] . ', ' . $order_info['shipping_city'] . ', ' . $order_info['shipping_country'],
                                'status' => 'pending'
                            );

                            $shipment_id = $this->model_shipping_shipment->addShipment($shipment_data);

                            if ($shipment_id) {
                                $json['success'] = $this->language->get('text_success_create_from_order');
                                $json['shipment_id'] = $shipment_id;
                                $json['redirect'] = $this->url->link('shipping/shipment/edit', 'user_token=' . $this->session->data['user_token'] . '&shipment_id=' . $shipment_id, true);
                            } else {
                                $json['error'] = $this->language->get('error_create_shipment_failed');
                            }
                        }
                    }
                } catch (Exception $e) {
                    $json['error'] = $this->language->get('error_create_shipment_failed') . ': ' . $e->getMessage();
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * تحديث معلومات التتبع
     */
    public function updateTracking() {
        $this->load->language('shipping/shipment');
        $this->load->model('shipping/shipment');

        $json = array();

        if (!$this->user->hasPermission('modify', 'shipping/shipment')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $shipment_id = isset($this->request->post['shipment_id']) ? (int)$this->request->post['shipment_id'] : 0;

            if (!$shipment_id) {
                $json['error'] = $this->language->get('error_shipment_id_required');
            } else {
                try {
                    $updated_tracking = $this->model_shipping_shipment->updateTrackingFromCarrier($shipment_id);

                    if ($updated_tracking) {
                        $json['success'] = $this->language->get('text_success_tracking_update');
                        $json['tracking_data'] = $updated_tracking;
                    } else {
                        $json['error'] = $this->language->get('error_tracking_update_failed');
                    }
                } catch (Exception $e) {
                    $json['error'] = $this->language->get('error_tracking_update_failed') . ': ' . $e->getMessage();
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * إرسال إشعار للعميل
     */
    public function notifyCustomer() {
        $this->load->language('shipping/shipment');
        $this->load->model('shipping/shipment');
        $this->load->model('mail/mail');

        $json = array();

        if (!$this->user->hasPermission('modify', 'shipping/shipment')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $shipment_id = isset($this->request->post['shipment_id']) ? (int)$this->request->post['shipment_id'] : 0;
            $notification_type = isset($this->request->post['notification_type']) ? $this->request->post['notification_type'] : '';

            if (!$shipment_id) {
                $json['error'] = $this->language->get('error_shipment_id_required');
            } elseif (!$notification_type) {
                $json['error'] = $this->language->get('error_notification_type_required');
            } else {
                try {
                    $sent = $this->model_shipping_shipment->sendCustomerNotification($shipment_id, $notification_type);

                    if ($sent) {
                        $json['success'] = $this->language->get('text_success_notification_sent');
                    } else {
                        $json['error'] = $this->language->get('error_notification_send_failed');
                    }
                } catch (Exception $e) {
                    $json['error'] = $this->language->get('error_notification_send_failed') . ': ' . $e->getMessage();
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * تقرير الشحنات
     */
    public function report() {
        $this->load->language('shipping/shipment');
        $this->load->model('shipping/shipment');

        $this->document->setTitle($this->language->get('heading_title_report'));

        if (!$this->user->hasPermission('access', 'shipping/shipment')) {
            $this->session->data['error'] = $this->language->get('error_permission');
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data = array();

        // مسار التنقل
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('shipping/shipment', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title_report'),
            'href' => $this->url->link('shipping/shipment/report', 'user_token=' . $this->session->data['user_token'], true)
        );

        // إحصائيات الشحنات
        $data['statistics'] = $this->model_shipping_shipment->getDetailedStatistics();

        // تقارير الأداء
        $data['performance_data'] = $this->model_shipping_shipment->getPerformanceData();

        // تحليل شركات الشحن
        $data['carrier_analysis'] = $this->model_shipping_shipment->getCarrierAnalysis();

        // النصوص
        $data['heading_title'] = $this->language->get('heading_title_report');
        $data['text_statistics'] = $this->language->get('text_statistics');
        $data['text_performance'] = $this->language->get('text_performance');
        $data['text_carrier_analysis'] = $this->language->get('text_carrier_analysis');

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('shipping/shipment_report', $data));
    }

    /**
     * تحليل التكاليف
     */
    public function costAnalysis() {
        $this->load->language('shipping/shipment');
        $this->load->model('shipping/shipment');

        $json = array();

        if (!$this->user->hasPermission('access', 'shipping/shipment')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $date_from = isset($this->request->post['date_from']) ? $this->request->post['date_from'] : '';
            $date_to = isset($this->request->post['date_to']) ? $this->request->post['date_to'] : '';
            $carrier_id = isset($this->request->post['carrier_id']) ? (int)$this->request->post['carrier_id'] : 0;

            try {
                $cost_analysis = $this->model_shipping_shipment->getCostAnalysis($date_from, $date_to, $carrier_id);

                $json['success'] = true;
                $json['data'] = $cost_analysis;
            } catch (Exception $e) {
                $json['error'] = $this->language->get('error_analysis_failed') . ': ' . $e->getMessage();
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * تحديث مجمع للحالات
     */
    public function bulkUpdateStatus() {
        $this->load->language('shipping/shipment');
        $this->load->model('shipping/shipment');

        $json = array();

        if (!$this->user->hasPermission('modify', 'shipping/shipment')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $shipment_ids = isset($this->request->post['shipment_ids']) ? $this->request->post['shipment_ids'] : array();
            $status = isset($this->request->post['status']) ? $this->request->post['status'] : '';
            $comment = isset($this->request->post['comment']) ? $this->request->post['comment'] : '';

            if (empty($shipment_ids)) {
                $json['error'] = $this->language->get('error_no_shipments_selected');
            } elseif (!$status) {
                $json['error'] = $this->language->get('error_status_required');
            } else {
                try {
                    $updated_count = 0;

                    foreach ($shipment_ids as $shipment_id) {
                        if ($this->model_shipping_shipment->updateShipmentStatus($shipment_id, $status, $comment)) {
                            $updated_count++;
                        }
                    }

                    if ($updated_count > 0) {
                        $json['success'] = sprintf($this->language->get('text_success_bulk_update'), $updated_count);
                    } else {
                        $json['error'] = $this->language->get('error_bulk_update_failed');
                    }
                } catch (Exception $e) {
                    $json['error'] = $this->language->get('error_bulk_update_failed') . ': ' . $e->getMessage();
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
