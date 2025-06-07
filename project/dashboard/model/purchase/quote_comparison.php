<?php
class ModelPurchaseQuoteComparison extends Model {
    /**
     * الحصول على عروض الأسعار المرتبطة بطلب شراء معين
     * 
     * @param int $po_id معرف طلب الشراء
     * @return array مصفوفة تحتوي على عروض الأسعار
     */
    public function getQuotationsByPurchaseOrder($po_id) {
        $this->load->model('purchase/quotation');
        
        $po_info = $this->getPurchaseOrderInfo($po_id);
        if (!$po_info) {
            return [];
        }
        
        // الحصول على عروض الأسعار المرتبطة بطلب الشراء
        $quotations = [];
        
        // إذا كان طلب الشراء مرتبط بطلب توريد، نجلب عروض الأسعار المرتبطة بطلب التوريد
        if (!empty($po_info['requisition_id'])) {
            $query = $this->db->query("SELECT quotation_id FROM " . DB_PREFIX . "purchase_quotation 
                                   WHERE requisition_id = '" . (int)$po_info['requisition_id'] . "'
                                   AND status IN ('approved', 'pending')");
            
            foreach ($query->rows as $row) {
                $quotation_info = $this->model_purchase_quotation->getQuotation($row['quotation_id']);
                if ($quotation_info) {
                    // إضافة بنود عرض السعر
                    $quotation_info['items'] = $this->model_purchase_quotation->getQuotationItems($row['quotation_id']);
                    $quotations[] = $quotation_info;
                }
            }
        }
        
        // إضافة معلومات التقييم والمقارنة
        $quotations = $this->addComparisonData($quotations, $po_id);
        
        return $quotations;
    }
    
    /**
     * الحصول على معلومات طلب الشراء
     * 
     * @param int $po_id معرف طلب الشراء
     * @return array معلومات طلب الشراء
     */
    public function getPurchaseOrderInfo($po_id) {
        $this->load->model('purchase/order');
        
        $po_info = $this->model_purchase_order->getPurchaseOrder($po_id);
        if (!$po_info) {
            return [];
        }
        
        // إضافة معلومات إضافية مثل اسم الفرع
        if (!empty($po_info['branch_id'])) {
            $query = $this->db->query("SELECT name FROM " . DB_PREFIX . "branch WHERE branch_id = '" . (int)$po_info['branch_id'] . "'");
            $po_info['branch_name'] = $query->row['name'] ?? '';
        } else {
            $po_info['branch_name'] = '';
        }
        
        return $po_info;
    }
    
    /**
     * إضافة بيانات المقارنة لعروض الأسعار
     * 
     * @param array $quotations مصفوفة عروض الأسعار
     * @param int $po_id معرف طلب الشراء
     * @return array عروض الأسعار مع بيانات المقارنة
     */
    private function addComparisonData($quotations, $po_id) {
        if (empty($quotations)) {
            return [];
        }
        
        // تحويل بنود العروض إلى تنسيق أسهل للمقارنة
        foreach ($quotations as &$quotation) {
            $items_by_product = [];
            foreach ($quotation['items'] as $item) {
                $items_by_product[$item['product_id']] = $item;
            }
            $quotation['items'] = $items_by_product;
        }
        unset($quotation);
        
        // تحديد أفضل سعر لكل منتج
        $best_prices = [];
        $all_products = [];
        
        // جمع كل المنتجات من جميع العروض
        foreach ($quotations as $quotation) {
            foreach ($quotation['items'] as $product_id => $item) {
                if (!isset($all_products[$product_id])) {
                    $all_products[$product_id] = [
                        'product_id' => $product_id,
                        'product_name' => $item['product_name'],
                        'product_code' => $item['product_code'] ?? ''
                    ];
                }
                
                // تحويل السعر إلى العملة الأساسية للمقارنة
                $base_price = $item['unit_price'] * $quotation['exchange_rate'];
                
                if (!isset($best_prices[$product_id]) || $base_price < $best_prices[$product_id]['price']) {
                    $best_prices[$product_id] = [
                        'price' => $base_price,
                        'quotation_id' => $quotation['quotation_id']
                    ];
                }
            }
        }
        
        // تحديد العرض ذو أفضل سعر إجمالي
        $best_price_quotation = null;
        $lowest_total = PHP_FLOAT_MAX;
        
        foreach ($quotations as &$quotation) {
            // تحويل السعر الإجمالي إلى العملة الأساسية
            $base_total = $quotation['total_amount'] * $quotation['exchange_rate'];
            
            if ($base_total < $lowest_total) {
                $lowest_total = $base_total;
                $best_price_quotation = $quotation['quotation_id'];
            }
            
            // وضع علامة على العرض ذو أفضل سعر
            $quotation['is_best_price'] = false;
            
            // وضع علامة على المنتجات ذات أفضل سعر
            foreach ($quotation['items'] as $product_id => &$item) {
                $item['is_best_price'] = ($best_prices[$product_id]['quotation_id'] == $quotation['quotation_id']);
            }
            unset($item);
            
            // إضافة تقييمات افتراضية للجودة والتسليم
            $quotation['quality_rating'] = $this->getSupplierQualityRating($quotation['supplier_id']);
            $quotation['delivery_rating'] = $this->getSupplierDeliveryRating($quotation['supplier_id']);
            
            // التحقق من صلاحية العرض
            $quotation['is_expired'] = (strtotime($quotation['validity_date']) < time());
        }
        unset($quotation);
        
        // وضع علامة على العرض ذو أفضل سعر إجمالي
        foreach ($quotations as &$quotation) {
            if ($quotation['quotation_id'] == $best_price_quotation) {
                $quotation['is_best_price'] = true;
            }
        }
        unset($quotation);
        
        // تحديد العرض ذو أفضل قيمة (مزيج من السعر والجودة والتسليم)
        $best_value_quotation = $this->determineBestValueQuotation($quotations);
        
        // حساب نسبة الفرق في السعر بين أفضل عرض وأسوأ عرض
        $price_difference_percentage = $this->calculatePriceDifferencePercentage($quotations);
        
        return [
            'quotations' => $quotations,
            'items' => array_values($all_products),
            'best_price_quotation' => $this->findQuotationById($quotations, $best_price_quotation),
            'best_value_quotation' => $this->findQuotationById($quotations, $best_value_quotation),
            'price_difference_percentage' => $price_difference_percentage
        ];
    }
    
    /**
     * الحصول على تقييم جودة المورد
     * 
     * @param int $supplier_id معرف المورد
     * @return int تقييم الجودة (1-5)
     */
    private function getSupplierQualityRating($supplier_id) {
        // يمكن استبدال هذا بمنطق حقيقي لاسترجاع تقييم الجودة من قاعدة البيانات
        // حالياً نستخدم قيمة عشوائية بين 3 و 5 للعرض التوضيحي
        return rand(3, 5);
    }
    
    /**
     * الحصول على تقييم التسليم للمورد
     * 
     * @param int $supplier_id معرف المورد
     * @return int تقييم التسليم (1-5)
     */
    private function getSupplierDeliveryRating($supplier_id) {
        // يمكن استبدال هذا بمنطق حقيقي لاسترجاع تقييم التسليم من قاعدة البيانات
        // حالياً نستخدم قيمة عشوائية بين 3 و 5 للعرض التوضيحي
        return rand(3, 5);
    }
    
    /**
     * تحديد العرض ذو أفضل قيمة (مزيج من السعر والجودة والتسليم)
     * 
     * @param array $quotations مصفوفة عروض الأسعار
     * @return int معرف العرض ذو أفضل قيمة
     */
    private function determineBestValueQuotation($quotations) {
        $best_value_score = 0;
        $best_value_quotation = null;
        
        foreach ($quotations as $quotation) {
            // حساب درجة القيمة بناءً على مزيج من السعر والجودة والتسليم
            // السعر: 60%، الجودة: 25%، التسليم: 15%
            $price_score = $quotation['is_best_price'] ? 100 : (100 - (($quotation['total_amount'] * $quotation['exchange_rate'] / $lowest_total - 1) * 100));
            $quality_score = $quotation['quality_rating'] * 20; // تحويل من 5 إلى 100
            $delivery_score = $quotation['delivery_rating'] * 20; // تحويل من 5 إلى 100
            
            $value_score = ($price_score * 0.6) + ($quality_score * 0.25) + ($delivery_score * 0.15);
            
            if ($value_score > $best_value_score) {
                $best_value_score = $value_score;
                $best_value_quotation = $quotation['quotation_id'];
            }
        }
        
        return $best_value_quotation;
    }
    
    /**
     * حساب نسبة الفرق في السعر بين أفضل عرض وأسوأ عرض
     * 
     * @param array $quotations مصفوفة عروض الأسعار
     * @return float نسبة الفرق في السعر
     */
    private function calculatePriceDifferencePercentage($quotations) {
        if (count($quotations) < 2) {
            return 0;
        }
        
        $min_price = PHP_FLOAT_MAX;
        $max_price = 0;
        
        foreach ($quotations as $quotation) {
            $base_price = $quotation['total_amount'] * $quotation['exchange_rate'];
            
            if ($base_price < $min_price) {
                $min_price = $base_price;
            }
            
            if ($base_price > $max_price) {
                $max_price = $base_price;
            }
        }
        
        if ($min_price == 0) {
            return 0;
        }
        
        return round(($max_price - $min_price) / $min_price * 100, 2);
    }
    
    /**
     * البحث عن عرض سعر بواسطة المعرف
     * 
     * @param array $quotations مصفوفة عروض الأسعار
     * @param int $quotation_id معرف عرض السعر
     * @return array|null معلومات عرض السعر أو null إذا لم يتم العثور عليه
     */
    private function findQuotationById($quotations, $quotation_id) {
        foreach ($quotations as $quotation) {
            if ($quotation['quotation_id'] == $quotation_id) {
                return $quotation;
            }
        }
        
        return null;
    }
    
    /**
     * تصدير مقارنة عروض الأسعار إلى ملف Excel
     * 
     * @param array $comparison_data بيانات المقارنة
     * @return string مسار الملف المصدر
     */
    public function exportComparisonToExcel($comparison_data) {
        // يمكن تنفيذ هذه الدالة لتصدير بيانات المقارنة إلى ملف Excel
        // باستخدام مكتبة PHPExcel أو مكتبة مماثلة
        
        return '';
    }
    
    /**
     * تصدير مقارنة عروض الأسعار إلى ملف PDF
     * 
     * @param array $comparison_data بيانات المقارنة
     * @return string مسار الملف المصدر
     */
    public function exportComparisonToPDF($comparison_data) {
        // يمكن تنفيذ هذه الدالة لتصدير بيانات المقارنة إلى ملف PDF
        // باستخدام مكتبة مثل MPDF أو TCPDF
        
        return '';
    }
}