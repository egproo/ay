<?php
class ModelCatalogSeo extends Model {
    // استرجاع كل الكلمات المفتاحية المتتبعة
    public function getKeywordTrackings($data = array()) {
        $sql = "SELECT * FROM " . DB_PREFIX . "seo_keyword_tracking";
        
        $sort_data = array(
            'keyword',
            'position',
            'last_checked',
            'status'
        );
        
        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY keyword";
        }
        
        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }
        
        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }
            
            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }
            
            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    // عدد الكلمات المفتاحية المتتبعة
    public function getTotalKeywordTrackings() {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "seo_keyword_tracking");
        
        return $query->row['total'];
    }
    
    // إضافة كلمة مفتاحية للتتبع
    public function addKeywordTracking($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "seo_keyword_tracking SET 
            keyword = '" . $this->db->escape($data['keyword']) . "', 
            search_engine = '" . $this->db->escape($data['search_engine']) . "', 
            position = '" . (int)$data['position'] . "', 
            url = '" . $this->db->escape($data['url']) . "', 
            last_checked = NOW(),
            previous_position = NULL,
            status = 'new'");
        
        return $this->db->getLastId();
    }
    
    // تحديث حالة الكلمة المفتاحية
    public function updateKeywordTracking($tracking_id, $data) {
        // احصل على المعلومات الحالية
        $query = $this->db->query("SELECT position FROM " . DB_PREFIX . "seo_keyword_tracking WHERE tracking_id = '" . (int)$tracking_id . "'");
        $current_position = $query->row['position'];
        
        // حدد الحالة الجديدة
        $status = 'unchanged';
        if ($current_position > $data['position']) {
            $status = 'improved';
        } elseif ($current_position < $data['position']) {
            $status = 'declined';
        }
        
        $this->db->query("UPDATE " . DB_PREFIX . "seo_keyword_tracking SET 
            position = '" . (int)$data['position'] . "', 
            url = '" . $this->db->escape($data['url']) . "', 
            last_checked = NOW(),
            previous_position = '" . (int)$current_position . "',
            status = '" . $status . "'
            WHERE tracking_id = '" . (int)$tracking_id . "'");
    }
    
    // حذف تتبع الكلمة المفتاحية
    public function deleteKeywordTracking($tracking_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "seo_keyword_tracking WHERE tracking_id = '" . (int)$tracking_id . "'");
    }
    
    // استرجاع روابط الصفحات الداخلية
    public function getInternalLinks($data = array()) {
        $sql = "SELECT * FROM " . DB_PREFIX . "seo_internal_link";
        
        $sort_data = array(
            'source_page',
            'target_page',
            'date_added'
        );
        
        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY date_added";
        }
        
        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }
        
        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }
            
            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }
            
            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    // عدد الروابط الداخلية
    public function getTotalInternalLinks() {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "seo_internal_link");
        
        return $query->row['total'];
    }
    
    // إضافة رابط داخلي
    public function addInternalLink($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "seo_internal_link SET 
            source_page = '" . $this->db->escape($data['source_page']) . "', 
            target_page = '" . $this->db->escape($data['target_page']) . "', 
            anchor_text = '" . $this->db->escape($data['anchor_text']) . "', 
            status = '" . (int)$data['status'] . "', 
            date_added = NOW()");
        
        return $this->db->getLastId();
    }
    
    // تحديث رابط داخلي
    public function updateInternalLink($link_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "seo_internal_link SET 
            source_page = '" . $this->db->escape($data['source_page']) . "', 
            target_page = '" . $this->db->escape($data['target_page']) . "', 
            anchor_text = '" . $this->db->escape($data['anchor_text']) . "', 
            status = '" . (int)$data['status'] . "'
            WHERE link_id = '" . (int)$link_id . "'");
    }
    
    // حذف رابط داخلي
    public function deleteInternalLink($link_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "seo_internal_link WHERE link_id = '" . (int)$link_id . "'");
    }
    
    // استرجاع تحليلات الصفحات
    public function getPageAnalyses($data = array()) {
        $sql = "SELECT * FROM " . DB_PREFIX . "seo_page_analysis";
        
        $sort_data = array(
            'page_url',
            'target_keyword',
            'overall_score',
            'date_analysis'
        );
        
        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY date_analysis DESC";
        }
        
        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }
        
        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }
            
            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }
            
            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    // عدد تحليلات الصفحات
    public function getTotalPageAnalyses() {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "seo_page_analysis");
        
        return $query->row['total'];
    }
    
    // إضافة تحليل صفحة
    public function addPageAnalysis($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "seo_page_analysis SET 
            page_url = '" . $this->db->escape($data['page_url']) . "', 
            target_keyword = '" . $this->db->escape($data['target_keyword']) . "', 
            title_score = '" . (int)$data['title_score'] . "', 
            meta_score = '" . (int)$data['meta_score'] . "', 
            content_score = '" . (int)$data['content_score'] . "', 
            technical_score = '" . (int)$data['technical_score'] . "', 
            overall_score = '" . (int)$data['overall_score'] . "', 
            suggestions = '" . $this->db->escape($data['suggestions']) . "', 
            date_analysis = NOW()");
        
        return $this->db->getLastId();
    }
    
    // تحديث تحليل صفحة
    public function updatePageAnalysis($analysis_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "seo_page_analysis SET 
            page_url = '" . $this->db->escape($data['page_url']) . "', 
            target_keyword = '" . $this->db->escape($data['target_keyword']) . "', 
            title_score = '" . (int)$data['title_score'] . "', 
            meta_score = '" . (int)$data['meta_score'] . "', 
            content_score = '" . (int)$data['content_score'] . "', 
            technical_score = '" . (int)$data['technical_score'] . "', 
            overall_score = '" . (int)$data['overall_score'] . "', 
            suggestions = '" . $this->db->escape($data['suggestions']) . "', 
            date_analysis = NOW()
            WHERE analysis_id = '" . (int)$analysis_id . "'");
    }
    
    // حذف تحليل صفحة
    public function deletePageAnalysis($analysis_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "seo_page_analysis WHERE analysis_id = '" . (int)$analysis_id . "'");
    }
    
    // الحصول على إحصائيات السيو
    public function getSEOStatistics() {
        $statistics = array();
        
        // عدد الكلمات المفتاحية
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "seo_keyword_tracking");
        $statistics['total_keywords'] = $query->row['total'];
        
        // عدد الكلمات المحسنة
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "seo_keyword_tracking WHERE status = 'improved'");
        $statistics['improved_keywords'] = $query->row['total'];
        
        // عدد الكلمات المتراجعة
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "seo_keyword_tracking WHERE status = 'declined'");
        $statistics['declined_keywords'] = $query->row['total'];
        
        // عدد الروابط الداخلية
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "seo_internal_link");
        $statistics['total_internal_links'] = $query->row['total'];
        
        // متوسط درجة تحليل الصفحات
        $query = $this->db->query("SELECT AVG(overall_score) AS average FROM " . DB_PREFIX . "seo_page_analysis");
        $statistics['average_page_score'] = round($query->row['average'], 2);
        
        return $statistics;
    }
    
    // الحصول على الروابط الداخلية لصفحة معينة
    public function getInternalLinksForPage($page_url) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_internal_link WHERE source_page = '" . $this->db->escape($page_url) . "'");
        
        return $query->rows;
    }
    
    // الحصول على الصفحات الأكثر استهدافاً بالروابط الداخلية
    public function getMostLinkedPages($limit = 10) {
        $query = $this->db->query("SELECT target_page, COUNT(*) AS link_count 
            FROM " . DB_PREFIX . "seo_internal_link 
            GROUP BY target_page 
            ORDER BY link_count DESC 
            LIMIT " . (int)$limit);
        
        return $query->rows;
    }
    
    // استرجاع URL الصفحات المتاحة للمراجعة من جدول seo_url
    public function getAvailablePages() {
        $query = $this->db->query("SELECT DISTINCT query, keyword FROM " . DB_PREFIX . "seo_url 
            WHERE language_id = '" . (int)$this->config->get('config_language_id') . "' 
            ORDER BY query ASC");
        
        $pages = array();
        
        foreach ($query->rows as $row) {
            $pages[] = array(
                'url' => $row['query'],
                'keyword' => $row['keyword']
            );
        }
        
        return $pages;
    }
    
    // تحديث إعدادات السيو
    public function updateSeoSettings($data) {
        foreach ($data as $key => $value) {
            $this->db->query("DELETE FROM " . DB_PREFIX . "seo_settings WHERE `key` = '" . $this->db->escape($key) . "'");
            
            if (is_array($value)) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "seo_settings SET 
                    code = 'seo', 
                    `key` = '" . $this->db->escape($key) . "', 
                    value = '" . $this->db->escape(json_encode($value)) . "', 
                    serialized = '1'");
            } else {
                $this->db->query("INSERT INTO " . DB_PREFIX . "seo_settings SET 
                    code = 'seo', 
                    `key` = '" . $this->db->escape($key) . "', 
                    value = '" . $this->db->escape($value) . "', 
                    serialized = '0'");
            }
        }
    }
    
    // الحصول على إعدادات السيو
    public function getSeoSettings() {
        $settings_data = array();
        
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_settings WHERE code = 'seo'");
        
        foreach ($query->rows as $setting) {
            if ($setting['serialized']) {
                $settings_data[$setting['key']] = json_decode($setting['value'], true);
            } else {
                $settings_data[$setting['key']] = $setting['value'];
            }
        }
        
        return $settings_data;
    }
}