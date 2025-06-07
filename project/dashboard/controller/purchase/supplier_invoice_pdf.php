<?php
class ControllerPurchaseSupplierInvoicePdf extends Controller {
    public function index() {
        $this->load->language('purchase/supplier_invoice');
        $this->load->model('purchase/supplier_invoice');
        
        if (isset($this->request->get['invoice_id'])) {
            $invoice_id = (int)$this->request->get['invoice_id'];
        } else {
            $this->response->redirect($this->url->link('purchase/supplier_invoice', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $invoice_info = $this->model_purchase_supplier_invoice->getInvoice($invoice_id);
        
        if (!$invoice_info) {
            $this->response->redirect($this->url->link('purchase/supplier_invoice', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        // تحميل المكتبات اللازمة
        require_once(DIR_SYSTEM . 'library/tcpdf/tcpdf.php');
        
        // إنشاء ملف PDF جديد
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        
        // تعيين معلومات المستند
        $pdf->SetCreator(CONFIG_NAME);
        $pdf->SetAuthor(CONFIG_NAME);
        $pdf->SetTitle($this->language->get('text_invoice') . ' #' . $invoice_info['invoice_number']);
        $pdf->SetSubject($this->language->get('text_invoice'));
        
        // تعيين الهوامش
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(10);
        
        // إعداد الرأس والتذييل
        $pdf->setHeaderFont(Array('dejavusans', '', 10));
        $pdf->setFooterFont(Array('dejavusans', '', 8));
        
        // إعداد الصفحة الافتراضية
        $pdf->SetHeaderData('', 0, CONFIG_NAME, $this->language->get('text_invoice') . ' #' . $invoice_info['invoice_number']);
        $pdf->setHeaderMargin(5);
        
        // تعيين الخط الافتراضي
        $pdf->SetDefaultMonospacedFont('courier');
        
        // تعيين الفواصل التلقائية للصفحة
        $pdf->SetAutoPageBreak(TRUE, 15);
        
        // تعيين نسبة الصورة الافتراضية
        $pdf->setImageScale(1.25);
        
        // تعيين اللغة
        $pdf->setLanguageArray(['ar' => 'arabic']);
        
        // إضافة صفحة
        $pdf->AddPage();
        
        // تعيين الخط
        $pdf->SetFont('dejavusans', '', 10);
        
        // الحصول على بيانات الفاتورة
        $invoice_items = $this->model_purchase_supplier_invoice->getInvoiceItems($invoice_id);
        $supplier_info = $this->model_purchase_supplier_invoice->getSupplierInfo($invoice_info['supplier_id']);
        
        // بناء محتوى الفاتورة
        $html = '<h1>' . $this->language->get('text_invoice') . ' #' . $invoice_info['invoice_number'] . '</h1>';
        $html .= '<table border="0" cellspacing="0" cellpadding="5">';
        $html .= '<tr><td width="50%"><strong>' . $this->language->get('text_supplier') . ':</strong> ' . $supplier_info['name'] . '</td>';
        $html .= '<td width="50%"><strong>' . $this->language->get('text_invoice_date') . ':</strong> ' . date($this->language->get('date_format_short'), strtotime($invoice_info['invoice_date'])) . '</td></tr>';
        $html .= '<tr><td><strong>' . $this->language->get('text_po_number') . ':</strong> ' . $invoice_info['po_number'] . '</td>';
        $html .= '<td><strong>' . $this->language->get('text_due_date') . ':</strong> ' . date($this->language->get('date_format_short'), strtotime($invoice_info['due_date'])) . '</td></tr>';
        $html .= '<tr><td><strong>' . $this->language->get('text_status') . ':</strong> ' . $this->model_purchase_supplier_invoice->getStatusText($invoice_info['status']) . '</td>';
        $html .= '<td></td></tr>';
        $html .= '</table>';
        
        $html .= '<br><br>';
        
        // جدول العناصر
        $html .= '<table border="1" cellspacing="0" cellpadding="5">';
        $html .= '<tr style="background-color:#f2f2f2;">';
        $html .= '<th width="40%">' . $this->language->get('column_product') . '</th>';
        $html .= '<th width="15%">' . $this->language->get('column_quantity') . '</th>';
        $html .= '<th width="15%">' . $this->language->get('column_unit') . '</th>';
        $html .= '<th width="15%">' . $this->language->get('column_unit_price') . '</th>';
        $html .= '<th width="15%">' . $this->language->get('column_total') . '</th>';
        $html .= '</tr>';
        
        foreach ($invoice_items as $item) {
            $html .= '<tr>';
            $html .= '<td>' . $item['product_name'] . '</td>';
            $html .= '<td align="right">' . $item['quantity'] . '</td>';
            $html .= '<td>' . $item['unit_name'] . '</td>';
            $html .= '<td align="right">' . $this->currency->format($item['unit_price'], $invoice_info['currency_code'], $invoice_info['exchange_rate']) . '</td>';
            $html .= '<td align="right">' . $this->currency->format($item['line_total'], $invoice_info['currency_code'], $invoice_info['exchange_rate']) . '</td>';
            $html .= '</tr>';
        }
        
        // المجاميع
        $html .= '<tr>';
        $html .= '<td colspan="4" align="right"><strong>' . $this->language->get('text_subtotal') . ':</strong></td>';
        $html .= '<td align="right">' . $this->currency->format($invoice_info['subtotal'], $invoice_info['currency_code'], $invoice_info['exchange_rate']) . '</td>';
        $html .= '</tr>';
        
        if ($invoice_info['tax_amount'] > 0) {
            $html .= '<tr>';
            $html .= '<td colspan="4" align="right"><strong>' . $this->language->get('text_tax') . ':</strong></td>';
            $html .= '<td align="right">' . $this->currency->format($invoice_info['tax_amount'], $invoice_info['currency_code'], $invoice_info['exchange_rate']) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '<tr>';
        $html .= '<td colspan="4" align="right"><strong>' . $this->language->get('text_total') . ':</strong></td>';
        $html .= '<td align="right">' . $this->currency->format($invoice_info['total_amount'], $invoice_info['currency_code'], $invoice_info['exchange_rate']) . '</td>';
        $html .= '</tr>';
        $html .= '</table>';
        
        // الملاحظات
        if (!empty($invoice_info['notes'])) {
            $html .= '<br><br>';
            $html .= '<strong>' . $this->language->get('text_notes') . ':</strong><br>';
            $html .= $invoice_info['notes'];
        }
        
        // إضافة المحتوى إلى PDF
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // إغلاق وإخراج PDF
        $pdf->Output('invoice_' . $invoice_info['invoice_number'] . '.pdf', 'D');
    }
}