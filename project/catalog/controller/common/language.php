<?php
class ControllerCommonLanguage extends Controller {
public function index() {
    $this->load->language('common/language');

    $current_keyword = isset($this->request->get['_route_']) ? $this->request->get['_route_'] : 'ar';
    $query = $this->getQueryFromSeoKeyword($current_keyword);
    
    if (!$query) {
        $query = 'ar';  // Default fallback if no query found
    }
    
    $data['languages'] = array();
    $seo_urls = $this->getSeoUrlsByQuery($query);
    $languages = $this->model_localisation_language->getLanguages();

    foreach ($languages as $language) {
        if ($language['status']) {
            $href = isset($seo_urls[$language['code']]) ? HTTPS_SERVER . $seo_urls[$language['code']] : HTTPS_SERVER;
            $data['languages'][] = array(
                'name'  => $language['name'],
                'code'  => $language['code'],
                'image' => $language['image'],
                'href'  => $href  // Use the SEO URL
            );
        }
    }

    return $this->load->view('common/language', $data);
}



        
private function getSeoUrlsByQuery($query) {
    $seo_urls = [];
    $results = $this->db->query("SELECT keyword, language_id FROM " . DB_PREFIX . "seo_url WHERE `query` = '" . $this->db->escape($query) . "'");
    
    if ($results->num_rows) {
        foreach ($results->rows as $row) {
            $language_code = $this->getLanguageCodeById($row['language_id']);
            if ($language_code) {
                $seo_urls[$language_code] = $row['keyword'];
            }
        }
    }
    return $seo_urls;  // Return an array of SEO URLs indexed by language codes
}


private function getQueryFromSeoKeyword($keyword) {
    $result = $this->db->query("SELECT query FROM " . DB_PREFIX . "seo_url WHERE keyword = '" . $this->db->escape($keyword) . "'");
    if ($result->num_rows) {
        return $result->row['query'];
    }
    return null;  // Return null if no query is found
}


private function getLanguageCodeById($language_id) {
    $result = $this->db->query("SELECT code FROM " . DB_PREFIX . "language WHERE language_id = '" . (int)$language_id . "'");
    if ($result->num_rows) {
        return $result->row['code'];
    }
    return null;  // Return null if no language code is found
}

}