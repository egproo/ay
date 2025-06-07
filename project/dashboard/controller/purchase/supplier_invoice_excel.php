<?php
class ControllerPurchaseSupplierInvoiceExcel extends Controller {
    public function index() {
        $this->load->language('purchase/supplier_invoice');
        $this->load->model('purchase/supplier_invoice');
        
        // تحميل المكتبات اللازمة
        require_once(DIR_SYSTEM . 'library/PHPExcel/Classes/PHPExcel.php');
        
        // إنشاء ملف Excel جديد
        $objPHPExcel = new PHPExcel();
        
        // تعيين خصائص الملف
        $objPHPExcel->getProperties()->setCreator(CONFIG_NAME)
            ->setLastModifiedBy(CONFIG_NAME)
            ->setTitle($this->language->get('heading_title'))
            ->setSubject($this->language->get('heading_title'))
            ->setDescription($this->language->get('heading_title'))
            ->setKeywords($this->language->get('heading_title'))
            ->setCategory('Export');
        
        // تعيين الورقة النشطة
        $objPHPExcel->setActiveSheetIndex(0);
        
        // تعيين عناوين الأعمدة
        $objPHPExcel->getActiveSheet()->setCellValue('A1', $this->language->get('column_invoice_number'));
        $objPHPExcel->getActiveSheet()->setCellValue('B1', $this->language->get('column_po_number'));
        $objPHPExcel->getActiveSheet()->setCellValue('C1', $this->language->get('column_supplier'));
        $objPHPExcel->getActiveSheet()->setCellValue('D1', $this->language->get('column_invoice_date'));
        $objPHPExcel->getActiveSheet()->setCellValue('E1', $this->language->get('column_due_date'));
        $objPHPExcel->getActiveSheet()->setCellValue('F1', $this->language->get('column_total'));
        $objPHPExcel->getActiveSheet()->setCellValue('G1', $this->language->get('column_status'));
        
        // تنسيق عناوين الأعمدة
        $objPHPExcel->getActiveSheet()->getStyle('A1:G1')->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getStyle('A1:G1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
        $objPHPExcel->getActiveSheet()->getStyle('A1:G1')->getFill()->getStartColor()->setRGB('EEEEEE');
        
        // الحصول على بيانات الفواتير
        $filter_data = array();
        
        // إذا تم تحديد معرف فاتورة محدد
        if (isset($this->request->get['invoice_id'])) {
            $invoice_info = $this->model_purchase_supplier_invoice->getInvoice((int)$this->request->get['invoice_id']);
            if ($invoice_info) {
                $invoices = array($invoice_info);
            } else {
                $invoices = array();
            }
        } else {
            // استخدام نفس معايير التصفية من الصفحة الرئيسية
            if (isset($this->request->get['filter_invoice_number'])) {
                $filter_data['filter_invoice_number'] = $this->request->get['filter_invoice_number'];
            }
            
            if (isset($this->request->get['filter_po_id'])) {
                $filter_data['filter_po_id'] = (int)$this->request->get['filter_po_id'];
            }
            
            if (isset($this->request->get['filter_po_number'])) {
                $filter_data['filter_po_number'] = $this->request->get['filter_po_number'];
            }
            
            if (isset($this->request->get['filter_supplier_id'])) {
                $filter_data['filter_supplier_id'] = (int)$this->request->get['filter_supplier_id'];
            }
            
            if (isset($this->request->get['filter_status'])) {
                $filter_data['filter_status'] = $this->request->get['filter_status'];
            }
            
            if (isset($this->request->get['filter_date_start'])) {
                $filter_data['filter_date_start'] = $this->request->get['filter_date_start'];
            }
            
            if (isset($this->request->get['filter_date_end'])) {
                $filter_data['filter_date_end'] = $this->request->get['filter_date_end'];
            }
            
            // الحصول على جميع الفواتير المطابقة للفلتر
            $invoices = $this->model_purchase_supplier_invoice->getInvoices($filter_data);
        }
        
        // ملء البيانات
        $row = 2;
        foreach ($invoices as $invoice) {
            $currency_code = $invoice['currency_code'] ?? $this->config->get('config_currency');
            
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $row, $invoice['invoice_number']);
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $row, $invoice['po_number'] ?? '');
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $row, $invoice['supplier_name']);
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $row, date($this->language->get('date_format_short'), strtotime($invoice['invoice_date'])));
            $objPHPExcel->getActiveSheet()->setCellValue('E' . $row, $invoice['due_date'] ? date($this->language->get('date_format_short'), strtotime($invoice['due_date'])) : '');
            $objPHPExcel->getActiveSheet()->setCellValue('F' . $row, $this->currency->format($invoice['total_amount'], $currency_code, $invoice['exchange_rate']));
            $objPHPExcel->getActiveSheet()->setCellValue('G' . $row, $this->model_purchase_supplier_invoice->getStatusText($invoice['status']));
            
            $row++;
        }
        
        // تعيين عرض الأعمدة تلقائيًا
        foreach (range('A', 'G') as $column) {
            $objPHPExcel->getActiveSheet()->getColumnDimension($column)->setAutoSize(true);
        }
        
        // تعيين اسم الورقة
        $objPHPExcel->getActiveSheet()->setTitle($this->language->get('heading_title'));
        
        // تعيين الورقة النشطة إلى الأولى
        $objPHPExcel->setActiveSheetIndex(0);
        
        // إخراج الملف
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="supplier_invoices_' . date('Y-m-d') . '.xlsx"');
        header('Cache-Control: max-age=0');
        
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }
    
    // تصدير تفاصيل فاتورة واحدة مع العناصر
    public function invoice() {
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
        require_once(DIR_SYSTEM . 'library/PHPExcel/Classes/PHPExcel.php');
        
        // إنشاء ملف Excel جديد
        $objPHPExcel = new PHPExcel();
        
        // تعيين خصائص الملف
        $objPHPExcel->getProperties()->setCreator(CONFIG_NAME)
            ->setLastModifiedBy(CONFIG_NAME)
            ->setTitle($this->language->get('text_invoice') . ' #' . $invoice_info['invoice_number'])
            ->setSubject($this->language->get('text_invoice'))
            ->setDescription($this->language->get('text_invoice') . ' #' . $invoice_info['invoice_number'])
            ->setKeywords($this->language->get('text_invoice'))
            ->setCategory('Export');
        
        // تعيين الورقة النشطة
        $objPHPExcel->setActiveSheetIndex(0);
        
        // إضافة معلومات الفاتورة
        $objPHPExcel->getActiveSheet()->setCellValue('A1', $this->language->get('text_invoice') . ' #' . $invoice_info['invoice_number']);
        $objPHPExcel->getActiveSheet()->mergeCells('A1:F1');
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setSize(14);
        
        $objPHPExcel->getActiveSheet()->setCellValue('A3', $this->language->get('text_supplier') . ':');
        $objPHPExcel->getActiveSheet()->setCellValue('B3', $invoice_info['supplier_name']);
        
        $objPHPExcel->getActiveSheet()->setCellValue('D3', $this->language->get('text_invoice_date') . ':');
        $objPHPExcel->getActiveSheet()->setCellValue('E3', date($this->language->get('date_format_short'), strtotime($invoice_info['invoice_date'])));
        
        $objPHPExcel->getActiveSheet()->setCellValue('A4', $this->language->get('text_po_number') . ':');
        $objPHPExcel->getActiveSheet()->setCellValue('B4', $invoice_info['po_number'] ?? '');
        
        $objPHPExcel->getActiveSheet()->setCellValue('D4', $this->language->get('text_due_date') . ':');
        $objPHPExcel->getActiveSheet()->setCellValue('E4', date($this->language->get('date_format_short'), strtotime($invoice_info['due_date'])));
        
        $objPHPExcel->getActiveSheet()->setCellValue('A5', $this->language->get('text_status') . ':');
        $objPHPExcel->getActiveSheet()->setCellValue('B5', $this->model_purchase_supplier_invoice->getStatusText($invoice_info['status']));
        
        // تنسيق الخلايا
        $objPHPExcel->getActiveSheet()->getStyle('A3:A5')->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getStyle('D3:D5')->getFont()->setBold(true);
        
        // عناوين جدول العناصر
        $objPHPExcel->getActiveSheet()->setCellValue('A7', $this->language->get('column_product'));
        $objPHPExcel->getActiveSheet()->setCellValue('B7', $this->language->get('column_quantity'));
        $objPHPExcel->getActiveSheet()->setCellValue('C7', $this->language->get('column_unit'));
        $objPHPExcel->getActiveSheet()->setCellValue('D7', $this->language->get('column_unit_price'));
        $objPHPExcel->getActiveSheet()->setCellValue('E7', $this->language->get('column_total'));
        
        // تنسيق عناوين الجدول
        $objPHPExcel->getActiveSheet()->getStyle('A7:E7')->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getStyle('A7:E7')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
        $objPHPExcel->getActiveSheet()->getStyle('A7:E7')->getFill()->getStartColor()->setRGB('EEEEEE');
        
        // الحصول على عناصر الفاتورة
        $invoice_items = $this->model_purchase_supplier_invoice->getInvoiceItems($invoice_id);
        
        // ملء بيانات العناصر
        $row = 8;
        foreach ($invoice_items as $item) {
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $row, $item['product_name']);
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $row, $item['quantity']);
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $row, $item['unit_name']);
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $row, $this->currency->format($item['unit_price'], $invoice_info['currency_code'], $invoice_info['exchange_rate']));
            $objPHPExcel->getActiveSheet()->setCellValue('E' . $row, $this->currency->format($item['line_total'], $invoice_info['currency_code'], $invoice_info['exchange_rate']));
            
            $row++;
        }
        
        // إضافة المجاميع
        $row = $row + 1;
        $objPHPExcel->getActiveSheet()->setCellValue('D' . $row, $this->language->get('text_subtotal') . ':');
        $objPHPExcel->getActiveSheet()->setCellValue('E' . $row, $this->currency->format($invoice_info['subtotal'], $invoice_info['currency_code'], $invoice_info['exchange_rate']));
        
        if ($invoice_info['tax_amount'] > 0) {
            $row++;
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $row, $this->language->get('text_tax') . ':');
            $objPHPExcel->getActiveSheet()->setCellValue('E' . $row, $this->currency->format($invoice_info['tax_amount'], $invoice_info['currency_code'], $invoice_info['exchange_rate']));
        }
        
        $row++;
        $objPHPExcel->getActiveSheet()->setCellValue('D' . $row, $this->language->get('text_total') . ':');
        $objPHPExcel->getActiveSheet()->setCellValue('E' . $row, $this->currency->format($invoice_info['total_amount'], $invoice_info['currency_code'], $invoice_info['exchange_rate']));
        
        // تنسيق المجاميع
        $objPHPExcel->getActiveSheet()->getStyle('D' . ($row-2) . ':D' . $row)->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getStyle('E' . ($row-2) . ':E' . $row)->getFont()->setBold(true);
        
        // إضافة الملاحظات
        if (!empty($invoice_info['notes'])) {
            $row = $row + 2;
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $row, $this->language->get('text_notes') . ':');
            $objPHPExcel->getActiveSheet()->getStyle('A' . $row)->getFont()->setBold(true);
            
            $row++;
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $row, $invoice_info['notes']);
            $objPHPExcel->getActiveSheet()->mergeCells('A' . $row . ':E' . $row);
        }
        
        // تعيين عرض الأعمدة تلقائيًا
        foreach (range('A', 'E') as $column) {
            $objPHPExcel->getActiveSheet()->getColumnDimension($column)->setAutoSize(true);
        }
        
        // تعيين اسم الورقة
        $objPHPExcel->getActiveSheet()->setTitle($this->language->get('text_invoice'));
        
        // تعيين الورقة النشطة إلى الأولى
        $objPHPExcel->setActiveSheetIndex(0);
        
        // إخراج الملف
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="invoice_' . $invoice_info['invoice_number'] . '_' . date('Y-m-d') . '.xlsx"');
        header('Cache-Control: max-age=0');
        
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }
}