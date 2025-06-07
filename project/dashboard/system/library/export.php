<?php
/**
 * فئة تصدير البيانات
 * تستخدم لتصدير البيانات إلى تنسيقات مختلفة مثل Excel و PDF
 */
class Export {
    private $registry;

    /**
     * Constructor
     *
     * @param Registry $registry
     */
    public function __construct($registry) {
        $this->registry = $registry;
    }

    /**
     * تصدير البيانات إلى ملف Excel
     *
     * @param array $data البيانات المراد تصديرها
     * @param array $headers عناوين الأعمدة
     * @param string $filename اسم الملف
     * @param array $columnWidths عرض الأعمدة (اختياري)
     * @param array $columnFormats تنسيقات الأعمدة (اختياري)
     * @param string $title عنوان التقرير (اختياري)
     * @param array $metadata بيانات وصفية إضافية (اختياري)
     */
    public function excel($data, $headers, $filename, $columnWidths = [], $columnFormats = [], $title = '', $metadata = []) {
        // التحقق من وجود مكتبة PhpSpreadsheet
        if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            // استخدام طريقة بديلة إذا لم تكن المكتبة متاحة
            $this->excelLegacy($data, $headers, $filename);
            return;
        }

        // استخدام مكتبة PhpSpreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // إعداد عنوان التقرير إذا كان موجودًا
        $row = 1;
        if (!empty($title)) {
            $sheet->setCellValue('A1', $title);
            $sheet->mergeCells('A1:' . $this->getColumnLetter(count($headers)) . '1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $row++;
        }

        // إضافة البيانات الوصفية إذا كانت موجودة
        if (!empty($metadata)) {
            foreach ($metadata as $key => $value) {
                $sheet->setCellValue('A' . $row, $key . ': ' . $value);
                $sheet->mergeCells('A' . $row . ':' . $this->getColumnLetter(count($headers)) . $row);
                $row++;
            }
            $row++; // إضافة سطر فارغ بعد البيانات الوصفية
        }

        // إضافة عناوين الأعمدة
        $col = 0;
        foreach ($headers as $header) {
            $sheet->setCellValue($this->getColumnLetter($col) . $row, $header);
            $col++;
        }

        // تنسيق عناوين الأعمدة
        $headerRange = 'A' . $row . ':' . $this->getColumnLetter(count($headers) - 1) . $row;
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('DDDDDD');
        $sheet->getStyle($headerRange)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // إضافة البيانات
        $row++;
        foreach ($data as $rowData) {
            $col = 0;
            foreach ($rowData as $value) {
                $sheet->setCellValue($this->getColumnLetter($col) . $row, $value);
                
                // تطبيق تنسيق العمود إذا كان محددًا
                if (isset($columnFormats[$col])) {
                    $sheet->getStyle($this->getColumnLetter($col) . $row)->getNumberFormat()->setFormatCode($columnFormats[$col]);
                }
                
                $col++;
            }
            $row++;
        }

        // تعيين عرض الأعمدة
        if (!empty($columnWidths)) {
            for ($i = 0; $i < count($headers); $i++) {
                if (isset($columnWidths[$i])) {
                    $sheet->getColumnDimension($this->getColumnLetter($i))->setWidth($columnWidths[$i]);
                } else {
                    $sheet->getColumnDimension($this->getColumnLetter($i))->setAutoSize(true);
                }
            }
        } else {
            // تعيين العرض التلقائي لجميع الأعمدة
            foreach (range('A', $this->getColumnLetter(count($headers) - 1)) as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
        }

        // إنشاء حدود للبيانات
        $dataRange = 'A' . ($row - count($data)) . ':' . $this->getColumnLetter(count($headers) - 1) . ($row - 1);
        $sheet->getStyle($dataRange)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        // تعيين اسم ورقة العمل
        $sheet->setTitle(substr($title ?: 'Report', 0, 31));

        // إنشاء كائن الكاتب
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        // إعداد رأس HTTP للتنزيل
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        // حفظ الملف إلى مخرج PHP
        $writer->save('php://output');
        exit;
    }

    /**
     * تصدير البيانات إلى ملف Excel باستخدام الطريقة القديمة
     * تستخدم عندما لا تكون مكتبة PhpSpreadsheet متاحة
     *
     * @param array $data البيانات المراد تصديرها
     * @param array $headers عناوين الأعمدة
     * @param string $filename اسم الملف
     */
    private function excelLegacy($data, $headers, $filename) {
        // إنشاء ملف CSV كبديل
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // إضافة BOM للتعامل مع الأحرف العربية
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // إضافة عناوين الأعمدة
        fputcsv($output, $headers);
        
        // إضافة البيانات
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }

    /**
     * تصدير البيانات إلى ملف PDF
     *
     * @param array $data البيانات المراد تصديرها
     * @param array $headers عناوين الأعمدة
     * @param string $filename اسم الملف
     * @param string $title عنوان التقرير (اختياري)
     * @param array $metadata بيانات وصفية إضافية (اختياري)
     */
    public function pdf($data, $headers, $filename, $title = '', $metadata = []) {
        // التحقق من وجود مكتبة TCPDF
        if (!class_exists('TCPDF')) {
            // إذا لم تكن المكتبة متاحة، استخدم Excel بدلاً من ذلك
            $this->excel($data, $headers, $filename . '.xlsx', [], [], $title, $metadata);
            return;
        }

        // إنشاء مستند PDF
        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8');
        
        // إعداد معلومات المستند
        $pdf->SetCreator('AYM ERP System');
        $pdf->SetAuthor('AYM ERP System');
        $pdf->SetTitle($title ?: 'Report');
        
        // إعداد الهوامش
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(10);
        
        // إعداد الرأس والتذييل
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->setFooterData(array(0, 0, 0), array(0, 0, 0));
        $pdf->setFooterFont(Array('helvetica', '', 8));
        $pdf->setFooterMargin(10);
        
        // إضافة صفحة
        $pdf->AddPage();
        
        // إضافة العنوان
        if (!empty($title)) {
            $pdf->SetFont('helvetica', 'B', 16);
            $pdf->Cell(0, 10, $title, 0, 1, 'C');
            $pdf->Ln(5);
        }
        
        // إضافة البيانات الوصفية
        if (!empty($metadata)) {
            $pdf->SetFont('helvetica', '', 10);
            foreach ($metadata as $key => $value) {
                $pdf->Cell(0, 6, $key . ': ' . $value, 0, 1);
            }
            $pdf->Ln(5);
        }
        
        // إنشاء الجدول
        $pdf->SetFont('helvetica', '', 10);
        
        // حساب عرض الأعمدة
        $pageWidth = $pdf->getPageWidth() - 20; // 20 = الهوامش
        $columnWidth = $pageWidth / count($headers);
        
        // إضافة عناوين الأعمدة
        $pdf->SetFillColor(220, 220, 220);
        $pdf->SetFont('helvetica', 'B', 10);
        foreach ($headers as $header) {
            $pdf->Cell($columnWidth, 7, $header, 1, 0, 'C', 1);
        }
        $pdf->Ln();
        
        // إضافة البيانات
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetFillColor(255, 255, 255);
        foreach ($data as $row) {
            foreach ($row as $cell) {
                $pdf->Cell($columnWidth, 6, $cell, 1, 0, 'L');
            }
            $pdf->Ln();
        }
        
        // إخراج الملف
        $pdf->Output($filename, 'D');
        exit;
    }

    /**
     * الحصول على حرف العمود من الرقم
     *
     * @param int $columnNumber رقم العمود (يبدأ من 0)
     * @return string حرف العمود (A, B, C, ...)
     */
    private function getColumnLetter($columnNumber) {
        $letter = '';
        while ($columnNumber >= 0) {
            $letter = chr(65 + ($columnNumber % 26)) . $letter;
            $columnNumber = floor($columnNumber / 26) - 1;
        }
        return $letter;
    }

    /**
     * الحصول على مرجع للسجل
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key) {
        return $this->registry->get($key);
    }
}
