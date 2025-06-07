<?php
/**
 * نظام أيم ERP: نموذج المساعد الذكي
 * يوفر وظائف لمعالجة استعلامات المساعد الذكي وإدارة المحادثات والإعدادات
 */
class ModelCommonAiAssistant extends Model {
    /**
     * الحصول على إعدادات المساعد الذكي للمستخدم
     *
     * @param int $user_id معرف المستخدم
     * @return array
     */
    public function getSettings($user_id) {
        $query = $this->db->query("SELECT * FROM cod_ai_assistant_settings WHERE user_id = '" . (int)$user_id . "'");
        
        if ($query->num_rows) {
            return json_decode($query->row['settings'], true);
        }
        
        // الإعدادات الافتراضية
        return array(
            'ai_model' => 'default',
            'ai_save_history' => 1,
            'ai_suggestions' => 1,
            'ai_auto_complete' => 0,
            'ai_access_sales' => 1,
            'ai_access_inventory' => 1,
            'ai_access_customers' => 1,
            'ai_access_reports' => 1
        );
    }
    
    /**
     * حفظ إعدادات المساعد الذكي للمستخدم
     *
     * @param int $user_id معرف المستخدم
     * @param array $settings الإعدادات
     * @return bool
     */
    public function saveSettings($user_id, $settings) {
        $this->db->query("INSERT INTO cod_ai_assistant_settings SET 
            user_id = '" . (int)$user_id . "',
            settings = '" . $this->db->escape(json_encode($settings)) . "'
            ON DUPLICATE KEY UPDATE 
            settings = '" . $this->db->escape(json_encode($settings)) . "'");
            
        return true;
    }
    
    /**
     * إضافة رسالة إلى المحادثة
     *
     * @param int $user_id معرف المستخدم
     * @param string $sender المرسل (user/ai)
     * @param string $message نص الرسالة
     * @return bool
     */
    public function addConversationMessage($user_id, $sender, $message) {
        $this->db->query("INSERT INTO cod_ai_assistant_conversation SET 
            user_id = '" . (int)$user_id . "',
            sender = '" . $this->db->escape($sender) . "',
            message = '" . $this->db->escape($message) . "',
            date_added = NOW()");
            
        return true;
    }
    
    /**
     * الحصول على المحادثة للمستخدم
     *
     * @param int $user_id معرف المستخدم
     * @param int $limit عدد الرسائل (اختياري)
     * @return array
     */
    public function getConversation($user_id, $limit = 50) {
        $query = $this->db->query("SELECT * FROM cod_ai_assistant_conversation 
            WHERE user_id = '" . (int)$user_id . "' 
            ORDER BY date_added ASC 
            LIMIT " . (int)$limit);
        
        $conversation = array();
        
        foreach ($query->rows as $row) {
            $conversation[] = array(
                'sender' => $row['sender'],
                'text' => $row['message'],
                'time' => date('Y-m-d H:i:s', strtotime($row['date_added']))
            );
        }
        
        return $conversation;
    }
    
    /**
     * مسح المحادثة للمستخدم
     *
     * @param int $user_id معرف المستخدم
     * @return bool
     */
    public function clearConversation($user_id) {
        $this->db->query("DELETE FROM cod_ai_assistant_conversation WHERE user_id = '" . (int)$user_id . "'");
        
        return true;
    }
    
    /**
     * معالجة استعلام المستخدم والحصول على رد المساعد الذكي
     *
     * @param string $query استعلام المستخدم
     * @param int $user_id معرف المستخدم
     * @return string
     */
    public function processQuery($query, $user_id) {
        // الحصول على إعدادات المساعد الذكي للمستخدم
        $settings = $this->getSettings($user_id);
        
        // تحديد نوع الاستعلام وتوجيهه إلى الوظيفة المناسبة
        if (stripos($query, 'مبيعات') !== false || stripos($query, 'طلبات') !== false || stripos($query, 'فواتير') !== false) {
            return $this->processSalesQuery($query, $settings);
        } elseif (stripos($query, 'مخزون') !== false || stripos($query, 'منتجات') !== false || stripos($query, 'بضاعة') !== false) {
            return $this->processInventoryQuery($query, $settings);
        } elseif (stripos($query, 'عملاء') !== false || stripos($query, 'زبائن') !== false) {
            return $this->processCustomersQuery($query, $settings);
        } elseif (stripos($query, 'تقارير') !== false || stripos($query, 'إحصائيات') !== false) {
            return $this->processReportsQuery($query, $settings);
        } else {
            // استعلام عام
            return $this->processGeneralQuery($query, $settings);
        }
    }
    
    /**
     * معالجة استعلامات المبيعات
     *
     * @param string $query استعلام المستخدم
     * @param array $settings إعدادات المساعد الذكي
     * @return string
     */
    private function processSalesQuery($query, $settings) {
        // التحقق من صلاحيات الوصول
        if (empty($settings['ai_access_sales'])) {
            return 'عذراً، ليس لديك صلاحية للوصول إلى بيانات المبيعات. يرجى تفعيل هذه الصلاحية من إعدادات المساعد الذكي.';
        }
        
        // استعلامات المبيعات الشائعة
        if (stripos($query, 'إجمالي المبيعات') !== false || stripos($query, 'مجموع المبيعات') !== false) {
            // الحصول على إجمالي المبيعات
            $total_sales = $this->getTotalSales();
            return 'إجمالي المبيعات: ' . $this->currency->format($total_sales, $this->config->get('config_currency'));
        } elseif (stripos($query, 'آخر الطلبات') !== false || stripos($query, 'أحدث الطلبات') !== false) {
            // الحصول على آخر الطلبات
            return $this->getRecentOrders();
        } elseif (stripos($query, 'طلبات اليوم') !== false) {
            // الحصول على طلبات اليوم
            return $this->getTodayOrders();
        } else {
            return 'يمكنني مساعدتك في استعلامات المبيعات. يمكنك سؤالي عن إجمالي المبيعات، آخر الطلبات، أو طلبات اليوم.';
        }
    }
    
    /**
     * معالجة استعلامات المخزون
     *
     * @param string $query استعلام المستخدم
     * @param array $settings إعدادات المساعد الذكي
     * @return string
     */
    private function processInventoryQuery($query, $settings) {
        // التحقق من صلاحيات الوصول
        if (empty($settings['ai_access_inventory'])) {
            return 'عذراً، ليس لديك صلاحية للوصول إلى بيانات المخزون. يرجى تفعيل هذه الصلاحية من إعدادات المساعد الذكي.';
        }
        
        // استعلامات المخزون الشائعة
        if (stripos($query, 'منتجات نفدت') !== false || stripos($query, 'نفاد المخزون') !== false) {
            // الحصول على المنتجات التي نفدت من المخزون
            return $this->getOutOfStockProducts();
        } elseif (stripos($query, 'أكثر المنتجات مبيعاً') !== false || stripos($query, 'الأكثر مبيعاً') !== false) {
            // الحصول على أكثر المنتجات مبيعاً
            return $this->getBestSellingProducts();
        } else {
            return 'يمكنني مساعدتك في استعلامات المخزون. يمكنك سؤالي عن المنتجات التي نفدت من المخزون أو أكثر المنتجات مبيعاً.';
        }
    }
    
    /**
     * معالجة استعلامات العملاء
     *
     * @param string $query استعلام المستخدم
     * @param array $settings إعدادات المساعد الذكي
     * @return string
     */
    private function processCustomersQuery($query, $settings) {
        // التحقق من صلاحيات الوصول
        if (empty($settings['ai_access_customers'])) {
            return 'عذراً، ليس لديك صلاحية للوصول إلى بيانات العملاء. يرجى تفعيل هذه الصلاحية من إعدادات المساعد الذكي.';
        }
        
        // استعلامات العملاء الشائعة
        if (stripos($query, 'عدد العملاء') !== false) {
            // الحصول على عدد العملاء
            $total_customers = $this->getTotalCustomers();
            return 'إجمالي عدد العملاء: ' . $total_customers;
        } elseif (stripos($query, 'أفضل العملاء') !== false || stripos($query, 'كبار العملاء') !== false) {
            // الحصول على أفضل العملاء
            return $this->getTopCustomers();
        } else {
            return 'يمكنني مساعدتك في استعلامات العملاء. يمكنك سؤالي عن عدد العملاء أو أفضل العملاء.';
        }
    }
    
    /**
     * معالجة استعلامات التقارير
     *
     * @param string $query استعلام المستخدم
     * @param array $settings إعدادات المساعد الذكي
     * @return string
     */
    private function processReportsQuery($query, $settings) {
        // التحقق من صلاحيات الوصول
        if (empty($settings['ai_access_reports'])) {
            return 'عذراً، ليس لديك صلاحية للوصول إلى التقارير. يرجى تفعيل هذه الصلاحية من إعدادات المساعد الذكي.';
        }
        
        // استعلامات التقارير الشائعة
        if (stripos($query, 'تقرير المبيعات') !== false) {
            // الحصول على تقرير المبيعات
            return $this->getSalesReport();
        } elseif (stripos($query, 'تقرير المخزون') !== false) {
            // الحصول على تقرير المخزون
            return $this->getInventoryReport();
        } elseif (stripos($query, 'تقرير الأرباح') !== false) {
            // الحصول على تقرير الأرباح
            return $this->getProfitReport();
        } else {
            return 'يمكنني مساعدتك في استعلامات التقارير. يمكنك سؤالي عن تقرير المبيعات، تقرير المخزون، أو تقرير الأرباح.';
        }
    }
    
    /**
     * معالجة الاستعلامات العامة
     *
     * @param string $query استعلام المستخدم
     * @param array $settings إعدادات المساعد الذكي
     * @return string
     */
    private function processGeneralQuery($query, $settings) {
        // الاستعلامات العامة
        if (stripos($query, 'مرحبا') !== false || stripos($query, 'أهلا') !== false) {
            return 'مرحباً! أنا المساعد الذكي لنظام أيم ERP. كيف يمكنني مساعدتك اليوم؟';
        } elseif (stripos($query, 'ماذا يمكنك أن تفعل') !== false || stripos($query, 'كيف تساعدني') !== false) {
            return 'يمكنني مساعدتك في العديد من المهام مثل:\n\n' .
                   '- الاستعلام عن المبيعات والطلبات\n' .
                   '- معرفة حالة المخزون والمنتجات\n' .
                   '- الاستعلام عن العملاء\n' .
                   '- الحصول على تقارير وإحصائيات\n\n' .
                   'يمكنك طرح سؤالك بشكل مباشر وسأحاول مساعدتك.';
        } else {
            return 'عذراً، لم أفهم استعلامك. يمكنك سؤالي عن المبيعات، المخزون، العملاء، أو التقارير. أو يمكنك سؤالي عما يمكنني فعله لمساعدتك.';
        }
    }
    
    /**
     * الحصول على إجمالي المبيعات
     *
     * @return float
     */
    private function getTotalSales() {
        $query = $this->db->query("SELECT SUM(total) AS total FROM cod_order WHERE order_status_id > 0");
        
        return $query->row['total'] ? $query->row['total'] : 0;
    }
    
    /**
     * الحصول على آخر الطلبات
     *
     * @param int $limit عدد الطلبات
     * @return string
     */
    private function getRecentOrders($limit = 5) {
        $query = $this->db->query("SELECT o.order_id, o.firstname, o.lastname, o.total, o.currency_code, o.currency_value, o.date_added, os.name AS status 
            FROM cod_order o 
            LEFT JOIN cod_order_status os ON (o.order_status_id = os.order_status_id) 
            WHERE o.order_status_id > 0 
            ORDER BY o.date_added DESC 
            LIMIT " . (int)$limit);
        
        if ($query->num_rows) {
            $response = "آخر " . $limit . " طلبات:\n\n";
            
            foreach ($query->rows as $row) {
                $response .= "الطلب #" . $row['order_id'] . " - " . $row['firstname'] . " " . $row['lastname'] . "\n";
                $response .= "المبلغ: " . $this->currency->format($row['total'], $row['currency_code'], $row['currency_value']) . "\n";
                $response .= "الحالة: " . $row['status'] . "\n";
                $response .= "التاريخ: " . date('Y-m-d H:i', strtotime($row['date_added'])) . "\n\n";
            }
            
            return $response;
        } else {
            return "لا توجد طلبات حديثة.";
        }
    }
    
    /**
     * الحصول على طلبات اليوم
     *
     * @return string
     */
    private function getTodayOrders() {
        $query = $this->db->query("SELECT COUNT(*) AS total, SUM(total) AS amount 
            FROM cod_order 
            WHERE DATE(date_added) = CURDATE() AND order_status_id > 0");
        
        $count = $query->row['total'];
        $amount = $query->row['amount'] ? $query->row['amount'] : 0;
        
        return "طلبات اليوم: " . $count . " طلب بقيمة إجمالية " . $this->currency->format($amount, $this->config->get('config_currency'));
    }
    
    /**
     * الحصول على المنتجات التي نفدت من المخزون
     *
     * @param int $limit عدد المنتجات
     * @return string
     */
    private function getOutOfStockProducts($limit = 5) {
        $query = $this->db->query("SELECT p.product_id, pd.name, p.quantity 
            FROM cod_product p 
            LEFT JOIN cod_product_description pd ON (p.product_id = pd.product_id) 
            WHERE p.quantity <= 0 
            ORDER BY p.date_modified DESC 
            LIMIT " . (int)$limit);
        
        if ($query->num_rows) {
            $response = "المنتجات التي نفدت من المخزون:\n\n";
            
            foreach ($query->rows as $row) {
                $response .= $row['name'] . " (الكمية: " . $row['quantity'] . ")\n";
            }
            
            return $response;
        } else {
            return "لا توجد منتجات نفدت من المخزون.";
        }
    }
    
    /**
     * الحصول على أكثر المنتجات مبيعاً
     *
     * @param int $limit عدد المنتجات
     * @return string
     */
    private function getBestSellingProducts($limit = 5) {
        $query = $this->db->query("SELECT op.product_id, pd.name, SUM(op.quantity) AS total 
            FROM cod_order_product op 
            LEFT JOIN cod_product_description pd ON (op.product_id = pd.product_id) 
            LEFT JOIN cod_order o ON (op.order_id = o.order_id) 
            WHERE o.order_status_id > 0 
            GROUP BY op.product_id 
            ORDER BY total DESC 
            LIMIT " . (int)$limit);
        
        if ($query->num_rows) {
            $response = "أكثر المنتجات مبيعاً:\n\n";
            
            foreach ($query->rows as $row) {
                $response .= $row['name'] . " (الكمية المباعة: " . $row['total'] . ")\n";
            }
            
            return $response;
        } else {
            return "لا توجد بيانات عن المنتجات الأكثر مبيعاً.";
        }
    }
    
    /**
     * الحصول على عدد العملاء
     *
     * @return int
     */
    private function getTotalCustomers() {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM cod_customer");
        
        return $query->row['total'];
    }
    
    /**
     * الحصول على أفضل العملاء
     *
     * @param int $limit عدد العملاء
     * @return string
     */
    private function getTopCustomers($limit = 5) {
        $query = $this->db->query("SELECT c.customer_id, CONCAT(c.firstname, ' ', c.lastname) AS name, 
            COUNT(o.order_id) AS order_count, SUM(o.total) AS total_amount 
            FROM cod_customer c 
            LEFT JOIN cod_order o ON (c.customer_id = o.customer_id) 
            WHERE o.order_status_id > 0 
            GROUP BY c.customer_id 
            ORDER BY total_amount DESC 
            LIMIT " . (int)$limit);
        
        if ($query->num_rows) {
            $response = "أفضل العملاء:\n\n";
            
            foreach ($query->rows as $row) {
                $response .= $row['name'] . "\n";
                $response .= "عدد الطلبات: " . $row['order_count'] . "\n";
                $response .= "إجمالي المشتريات: " . $this->currency->format($row['total_amount'], $this->config->get('config_currency')) . "\n\n";
            }
            
            return $response;
        } else {
            return "لا توجد بيانات عن أفضل العملاء.";
        }
    }
    
    /**
     * الحصول على تقرير المبيعات
     *
     * @return string
     */
    private function getSalesReport() {
        // الحصول على إحصائيات المبيعات للأسبوع الحالي
        $query = $this->db->query("SELECT COUNT(*) AS total_orders, SUM(total) AS total_amount 
            FROM cod_order 
            WHERE order_status_id > 0 
            AND date_added >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
        
        $total_orders = $query->row['total_orders'];
        $total_amount = $query->row['total_amount'] ? $query->row['total_amount'] : 0;
        
        // الحصول على متوسط قيمة الطلب
        $average_order = $total_orders > 0 ? $total_amount / $total_orders : 0;
        
        $response = "تقرير المبيعات للأسبوع الحالي:\n\n";
        $response .= "إجمالي الطلبات: " . $total_orders . "\n";
        $response .= "إجمالي المبيعات: " . $this->currency->format($total_amount, $this->config->get('config_currency')) . "\n";
        $response .= "متوسط قيمة الطلب: " . $this->currency->format($average_order, $this->config->get('config_currency')) . "\n";
        
        return $response;
    }
    
    /**
     * الحصول على تقرير المخزون
     *
     * @return string
     */
    private function getInventoryReport() {
        // الحصول على إحصائيات المخزون
        $query = $this->db->query("SELECT COUNT(*) AS total_products, 
            SUM(CASE WHEN quantity <= 0 THEN 1 ELSE 0 END) AS out_of_stock, 
            SUM(CASE WHEN quantity > 0 AND quantity <= 5 THEN 1 ELSE 0 END) AS low_stock 
            FROM cod_product");
        
        $total_products = $query->row['total_products'];
        $out_of_stock = $query->row['out_of_stock'];
        $low_stock = $query->row['low_stock'];
        
        $response = "تقرير المخزون:\n\n";
        $response .= "إجمالي المنتجات: " . $total_products . "\n";
        $response .= "المنتجات التي نفدت من المخزون: " . $out_of_stock . "\n";
        $response .= "المنتجات ذات المخزون المنخفض: " . $low_stock . "\n";
        
        return $response;
    }
    
    /**
     * الحصول على تقرير الأرباح
     *
     * @return string
     */
    private function getProfitReport() {
        // هذه مجرد بيانات توضيحية، يجب تعديلها حسب هيكل قاعدة البيانات الفعلي
        $response = "تقرير الأرباح للشهر الحالي:\n\n";
        $response .= "إجمالي المبيعات: " . $this->currency->format(50000, $this->config->get('config_currency')) . "\n";
        $response .= "إجمالي التكاليف: " . $this->currency->format(30000, $this->config->get('config_currency')) . "\n";
        $response .= "صافي الربح: " . $this->currency->format(20000, $this->config->get('config_currency')) . "\n";
        $response .= "هامش الربح: 40%\n";
        
        return $response;
    }
}