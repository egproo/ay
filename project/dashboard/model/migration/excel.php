<?php
namespace Opencart\Admin\Model\Migration;

class Excel extends \Opencart\System\Engine\Model {
    
    public function processExcelImport($file) {
        try {
            // Load PhpSpreadsheet library
            require_once(DIR_SYSTEM . 'library/vendor/autoload.php');
            
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file['tmp_name']);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file['tmp_name']);
            
            $data = [];
            $total_records = 0;
            
            // Process each worksheet
            foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
                $sheetName = $worksheet->getTitle();
                $sheetData = $worksheet->toArray();
                
                if (!empty($sheetData)) {
                    $headers = array_shift($sheetData); // First row as headers
                    $processedData = [];
                    
                    foreach ($sheetData as $row) {
                        if (!empty(array_filter($row))) { // Skip empty rows
                            $processedData[] = array_combine($headers, $row);
                            $total_records++;
                        }
                    }
                    
                    $data[$sheetName] = $processedData;
                }
            }
            
            return [
                'success' => true,
                'total_records' => $total_records,
                'data' => $data,
                'sheets' => array_keys($data)
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'خطأ في معالجة ملف Excel: ' . $e->getMessage()
            ];
        }
    }
    
    public function getExcelSheets($file) {
        try {
            require_once(DIR_SYSTEM . 'library/vendor/autoload.php');
            
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file['tmp_name']);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file['tmp_name']);
            
            $sheets = [];
            $index = 0;
            
            foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
                $sheets[] = [
                    'id' => $index++,
                    'name' => $worksheet->getTitle(),
                    'row_count' => $worksheet->getHighestRow()
                ];
            }
            
            return [
                'success' => true,
                'sheets' => $sheets
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'خطأ في قراءة أوراق العمل: ' . $e->getMessage()
            ];
        }
    }
    
    public function createMigration($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "migration SET 
            source = '" . $this->db->escape($data['source']) . "',
            file_name = '" . $this->db->escape($data['file_name']) . "',
            total_records = '" . (int)$data['total_records'] . "',
            status = '" . $this->db->escape($data['status']) . "',
            date_added = NOW()");
        
        return $this->db->getLastId();
    }
    
    public function storeTemporaryData($migration_id, $data) {
        foreach ($data as $sheet_name => $records) {
            $table_name = $this->getTableNameFromSheet($sheet_name);
            
            foreach ($records as $record) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "migration_temp_excel_" . $table_name . " 
                    SET migration_id = '" . (int)$migration_id . "',
                    sheet_name = '" . $this->db->escape($sheet_name) . "',
                    source_data = '" . $this->db->escape(json_encode($record)) . "',
                    status = 'pending',
                    date_added = NOW()");
            }
        }
    }
    
    public function processProducts($migration_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "migration_temp_excel_products 
            WHERE migration_id = '" . (int)$migration_id . "' AND status = 'pending'");
        
        $processed = 0;
        $errors = [];
        
        foreach ($query->rows as $row) {
            $data = json_decode($row['source_data'], true);
            
            try {
                // Validate required fields
                if (empty($data['name']) || empty($data['model'])) {
                    throw new \Exception('اسم المنتج والموديل مطلوبان');
                }
                
                // Insert product
                $this->db->query("INSERT INTO " . DB_PREFIX . "product SET 
                    model = '" . $this->db->escape($data['model']) . "',
                    sku = '" . $this->db->escape($data['sku'] ?? '') . "',
                    upc = '" . $this->db->escape($data['upc'] ?? '') . "',
                    ean = '" . $this->db->escape($data['ean'] ?? '') . "',
                    jan = '" . $this->db->escape($data['jan'] ?? '') . "',
                    isbn = '" . $this->db->escape($data['isbn'] ?? '') . "',
                    mpn = '" . $this->db->escape($data['mpn'] ?? '') . "',
                    location = '" . $this->db->escape($data['location'] ?? '') . "',
                    quantity = '" . (int)($data['quantity'] ?? 0) . "',
                    stock_status_id = '" . (int)($data['stock_status_id'] ?? 7) . "',
                    image = '" . $this->db->escape($data['image'] ?? '') . "',
                    manufacturer_id = '" . (int)($data['manufacturer_id'] ?? 0) . "',
                    shipping = '" . (int)($data['shipping'] ?? 1) . "',
                    price = '" . (float)($data['price'] ?? 0) . "',
                    points = '" . (int)($data['points'] ?? 0) . "',
                    tax_class_id = '" . (int)($data['tax_class_id'] ?? 0) . "',
                    date_available = '" . $this->db->escape($data['date_available'] ?? date('Y-m-d')) . "',
                    weight = '" . (float)($data['weight'] ?? 0) . "',
                    weight_class_id = '" . (int)($data['weight_class_id'] ?? 1) . "',
                    length = '" . (float)($data['length'] ?? 0) . "',
                    width = '" . (float)($data['width'] ?? 0) . "',
                    height = '" . (float)($data['height'] ?? 0) . "',
                    length_class_id = '" . (int)($data['length_class_id'] ?? 1) . "',
                    subtract = '" . (int)($data['subtract'] ?? 1) . "',
                    minimum = '" . (int)($data['minimum'] ?? 1) . "',
                    sort_order = '" . (int)($data['sort_order'] ?? 0) . "',
                    status = '" . (int)($data['status'] ?? 1) . "',
                    viewed = '0',
                    date_added = NOW(),
                    date_modified = NOW()");
                
                $product_id = $this->db->getLastId();
                
                // Insert product description
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_description SET 
                    product_id = '" . (int)$product_id . "',
                    language_id = '1',
                    name = '" . $this->db->escape($data['name']) . "',
                    description = '" . $this->db->escape($data['description'] ?? '') . "',
                    tag = '" . $this->db->escape($data['tag'] ?? '') . "',
                    meta_title = '" . $this->db->escape($data['meta_title'] ?? $data['name']) . "',
                    meta_description = '" . $this->db->escape($data['meta_description'] ?? '') . "',
                    meta_keyword = '" . $this->db->escape($data['meta_keyword'] ?? '') . "'");
                
                // Insert product to store
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_store SET 
                    product_id = '" . (int)$product_id . "',
                    store_id = '0'");
                
                // Update status to completed
                $this->db->query("UPDATE " . DB_PREFIX . "migration_temp_excel_products 
                    SET status = 'completed', processed_at = NOW() 
                    WHERE migration_temp_id = '" . (int)$row['migration_temp_id'] . "'");
                
                $processed++;
                
            } catch (\Exception $e) {
                $errors[] = 'خطأ في معالجة المنتج: ' . $e->getMessage();
                
                $this->db->query("UPDATE " . DB_PREFIX . "migration_temp_excel_products 
                    SET status = 'error', error_message = '" . $this->db->escape($e->getMessage()) . "' 
                    WHERE migration_temp_id = '" . (int)$row['migration_temp_id'] . "'");
            }
        }
        
        return [
            'processed' => $processed,
            'errors' => $errors
        ];
    }
    
    public function processCustomers($migration_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "migration_temp_excel_customers 
            WHERE migration_id = '" . (int)$migration_id . "' AND status = 'pending'");
        
        $processed = 0;
        $errors = [];
        
        foreach ($query->rows as $row) {
            $data = json_decode($row['source_data'], true);
            
            try {
                // Validate required fields
                if (empty($data['firstname']) || empty($data['email'])) {
                    throw new \Exception('الاسم الأول والبريد الإلكتروني مطلوبان');
                }
                
                // Check if email already exists
                $existing = $this->db->query("SELECT customer_id FROM " . DB_PREFIX . "customer 
                    WHERE email = '" . $this->db->escape($data['email']) . "'");
                
                if ($existing->num_rows) {
                    throw new \Exception('البريد الإلكتروني موجود مسبقاً');
                }
                
                // Insert customer
                $this->db->query("INSERT INTO " . DB_PREFIX . "customer SET 
                    customer_group_id = '" . (int)($data['customer_group_id'] ?? 1) . "',
                    store_id = '0',
                    language_id = '1',
                    firstname = '" . $this->db->escape($data['firstname']) . "',
                    lastname = '" . $this->db->escape($data['lastname'] ?? '') . "',
                    email = '" . $this->db->escape($data['email']) . "',
                    telephone = '" . $this->db->escape($data['telephone'] ?? '') . "',
                    custom_field = '',
                    newsletter = '" . (int)($data['newsletter'] ?? 0) . "',
                    password = '" . password_hash($data['password'] ?? 'temp123', PASSWORD_DEFAULT) . "',
                    status = '" . (int)($data['status'] ?? 1) . "',
                    approved = '1',
                    safe = '0',
                    token = '',
                    code = '',
                    date_added = NOW()");
                
                $customer_id = $this->db->getLastId();
                
                // Insert customer address if provided
                if (!empty($data['address_1'])) {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "address SET 
                        customer_id = '" . (int)$customer_id . "',
                        firstname = '" . $this->db->escape($data['firstname']) . "',
                        lastname = '" . $this->db->escape($data['lastname'] ?? '') . "',
                        company = '" . $this->db->escape($data['company'] ?? '') . "',
                        address_1 = '" . $this->db->escape($data['address_1']) . "',
                        address_2 = '" . $this->db->escape($data['address_2'] ?? '') . "',
                        city = '" . $this->db->escape($data['city'] ?? '') . "',
                        postcode = '" . $this->db->escape($data['postcode'] ?? '') . "',
                        country_id = '" . (int)($data['country_id'] ?? 63) . "',
                        zone_id = '" . (int)($data['zone_id'] ?? 0) . "',
                        custom_field = ''");
                    
                    $address_id = $this->db->getLastId();
                    
                    // Set as default address
                    $this->db->query("UPDATE " . DB_PREFIX . "customer 
                        SET address_id = '" . (int)$address_id . "' 
                        WHERE customer_id = '" . (int)$customer_id . "'");
                }
                
                // Update status to completed
                $this->db->query("UPDATE " . DB_PREFIX . "migration_temp_excel_customers 
                    SET status = 'completed', processed_at = NOW() 
                    WHERE migration_temp_id = '" . (int)$row['migration_temp_id'] . "'");
                
                $processed++;
                
            } catch (\Exception $e) {
                $errors[] = 'خطأ في معالجة العميل: ' . $e->getMessage();
                
                $this->db->query("UPDATE " . DB_PREFIX . "migration_temp_excel_customers 
                    SET status = 'error', error_message = '" . $this->db->escape($e->getMessage()) . "' 
                    WHERE migration_temp_id = '" . (int)$row['migration_temp_id'] . "'");
            }
        }
        
        return [
            'processed' => $processed,
            'errors' => $errors
        ];
    }
    
    private function getTableNameFromSheet($sheet_name) {
        $mapping = [
            'Products' => 'products',
            'المنتجات' => 'products',
            'Customers' => 'customers',
            'العملاء' => 'customers',
            'Orders' => 'orders',
            'الطلبات' => 'orders',
            'Categories' => 'categories',
            'الفئات' => 'categories',
            'Suppliers' => 'suppliers',
            'الموردين' => 'suppliers',
            'Inventory' => 'inventory',
            'المخزون' => 'inventory'
        ];
        
        return $mapping[$sheet_name] ?? strtolower(str_replace(' ', '_', $sheet_name));
    }
    
    public function getMigrationStatus($migration_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "migration 
            WHERE migration_id = '" . (int)$migration_id . "'");
        
        if ($query->num_rows) {
            return $query->row;
        }
        
        return false;
    }
    
    public function getMigrationLog($migration_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "migration_log 
            WHERE migration_id = '" . (int)$migration_id . "' 
            ORDER BY date_added DESC");
        
        return $query->rows;
    }
}
