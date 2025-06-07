<?php
class ControllerCatalogSeo extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('catalog/seo');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('catalog/seo');
        
        // تحقق من الصلاحيات
        if (!$this->user->hasKey('access.catalog.seo')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $this->getList();
    }
    
    public function keywordTrackings() {
        $this->load->language('catalog/seo');
        $this->document->setTitle($this->language->get('heading_title') . ' - ' . $this->language->get('text_keyword_tracking'));
        
        $this->load->model('catalog/seo');
        
        // تحقق من الصلاحيات
        if (!$this->user->hasKey('access.catalog.seo')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $this->getKeywordTrackingsList();
    }
    
    public function internalLinks() {
        $this->load->language('catalog/seo');
        $this->document->setTitle($this->language->get('heading_title') . ' - ' . $this->language->get('text_internal_links'));
        
        $this->load->model('catalog/seo');
        
        // تحقق من الصلاحيات
        if (!$this->user->hasKey('access.catalog.seo')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $this->getInternalLinksList();
    }
    
    public function pageAnalysis() {
        $this->load->language('catalog/seo');
        $this->document->setTitle($this->language->get('heading_title') . ' - ' . $this->language->get('text_page_analysis'));
        
        $this->load->model('catalog/seo');
        
        // تحقق من الصلاحيات
        if (!$this->user->hasKey('access.catalog.seo')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $this->getPageAnalysisList();
    }
    
    public function settings() {
        $this->load->language('catalog/seo');
        $this->document->setTitle($this->language->get('heading_title') . ' - ' . $this->language->get('text_settings'));
        
        $this->load->model('catalog/seo');
        
        // تحقق من الصلاحيات
        if (!$this->user->hasKey('access.catalog.seo')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateSettingsForm()) {
            $this->model_catalog_seo->updateSeoSettings($this->request->post);
            
            $this->session->data['success'] = $this->language->get('text_success');
            
            $this->response->redirect($this->url->link('catalog/seo/settings', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        $this->getSettingsForm();
    }
    
    protected function getList() {
        $data['user_token'] = $this->session->data['user_token'];
        
        // تحميل إحصائيات السيو
        $statistics = $this->model_catalog_seo->getSEOStatistics();
        $data['statistics'] = $statistics;
        
        // معلومات الصفحة
        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_dashboard'] = $this->language->get('text_dashboard');
        $data['text_keyword_tracking'] = $this->language->get('text_keyword_tracking');
        $data['text_internal_links'] = $this->language->get('text_internal_links');
        $data['text_page_analysis'] = $this->language->get('text_page_analysis');
        $data['text_settings'] = $this->language->get('text_settings');
        
        $data['text_total_keywords'] = $this->language->get('text_total_keywords');
        $data['text_improved_keywords'] = $this->language->get('text_improved_keywords');
        $data['text_declined_keywords'] = $this->language->get('text_declined_keywords');
        $data['text_total_internal_links'] = $this->language->get('text_total_internal_links');
        $data['text_average_page_score'] = $this->language->get('text_average_page_score');
        
        // الروابط
        $data['keyword_tracking_url'] = $this->url->link('catalog/seo/keywordTrackings', 'user_token=' . $this->session->data['user_token'], true);
        $data['internal_links_url'] = $this->url->link('catalog/seo/internalLinks', 'user_token=' . $this->session->data['user_token'], true);
        $data['page_analysis_url'] = $this->url->link('catalog/seo/pageAnalysis', 'user_token=' . $this->session->data['user_token'], true);
        $data['settings_url'] = $this->url->link('catalog/seo/settings', 'user_token=' . $this->session->data['user_token'], true);
        
        // الصفحات الأكثر استهدافاً
        $data['most_linked_pages'] = $this->model_catalog_seo->getMostLinkedPages(5);
        
        // رسالة النجاح
        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }
        
        // أحدث الكلمات المفتاحية المتتبعة
        $latest_keywords = $this->model_catalog_seo->getKeywordTrackings(array('limit' => 5, 'sort' => 'last_checked', 'order' => 'DESC'));
        $data['latest_keywords'] = $latest_keywords;
        
        // تحليلات الصفحات الأخيرة
        $latest_analyses = $this->model_catalog_seo->getPageAnalyses(array('limit' => 5, 'sort' => 'date_analysis', 'order' => 'DESC'));
        $data['latest_analyses'] = $latest_analyses;
        
        // تضمين الهيدر والكولمن وإضافة قالب الصفحة
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('catalog/seo_dashboard', $data));
    }
    
    protected function getKeywordTrackingsList() {
        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $tracking_id) {
                $this->model_catalog_seo->deleteKeywordTracking($tracking_id);
            }
            
            $this->session->data['success'] = $this->language->get('text_success');
            
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
            
            $this->response->redirect($this->url->link('catalog/seo/keywordTrackings', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }
        
        // معلومات الصفحة
        $data['heading_title'] = $this->language->get('heading_title') . ' - ' . $this->language->get('text_keyword_tracking');
        $data['text_list'] = $this->language->get('text_keyword_list');
        $data['text_no_results'] = $this->language->get('text_no_results');
        $data['text_confirm'] = $this->language->get('text_confirm');
        
        $data['column_keyword'] = $this->language->get('column_keyword');
        $data['column_search_engine'] = $this->language->get('column_search_engine');
        $data['column_position'] = $this->language->get('column_position');
        $data['column_previous_position'] = $this->language->get('column_previous_position');
        $data['column_url'] = $this->language->get('column_url');
        $data['column_last_checked'] = $this->language->get('column_last_checked');
        $data['column_status'] = $this->language->get('column_status');
        $data['column_action'] = $this->language->get('column_action');
        
        $data['button_add'] = $this->language->get('button_add');
        $data['button_edit'] = $this->language->get('button_edit');
        $data['button_delete'] = $this->language->get('button_delete');
        $data['button_back'] = $this->language->get('button_back');
        $data['button_check_rankings'] = $this->language->get('button_check_rankings');
        
        $data['user_token'] = $this->session->data['user_token'];
        
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }
        
        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }
        
        if (isset($this->request->post['selected'])) {
            $data['selected'] = (array)$this->request->post['selected'];
        } else {
            $data['selected'] = array();
        }
        
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
        
        // Paginación
        $data['pagination'] = '';
        $data['results'] = '';
        
        // Ordenación
        $data['sort'] = isset($this->request->get['sort']) ? $this->request->get['sort'] : 'keyword';
        $data['order'] = isset($this->request->get['order']) ? $this->request->get['order'] : 'ASC';
        $data['page'] = isset($this->request->get['page']) ? $this->request->get['page'] : 1;
        
        // Consultar los datos de seguimiento de palabras clave
        $filter_data = array(
            'sort'  => $data['sort'],
            'order' => $data['order'],
            'start' => ($data['page'] - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );
        
        $keyword_tracking_total = $this->model_catalog_seo->getTotalKeywordTrackings();
        $keyword_trackings = $this->model_catalog_seo->getKeywordTrackings($filter_data);
        
        $data['keyword_trackings'] = array();
        
        foreach ($keyword_trackings as $tracking) {
            // Determinar clase de estado
            $status_class = '';
            switch ($tracking['status']) {
                case 'improved':
                    $status_class = 'success';
                    break;
                case 'declined':
                    $status_class = 'danger';
                    break;
                case 'unchanged':
                    $status_class = 'info';
                    break;
                case 'new':
                    $status_class = 'warning';
                    break;
            }
            
            $data['keyword_trackings'][] = array(
                'tracking_id'        => $tracking['tracking_id'],
                'keyword'            => $tracking['keyword'],
                'search_engine'      => $tracking['search_engine'],
                'position'           => $tracking['position'],
                'previous_position'  => $tracking['previous_position'],
                'url'                => $tracking['url'],
                'last_checked'       => date($this->language->get('date_format_short'), strtotime($tracking['last_checked'])),
                'status'             => $this->language->get('text_status_' . $tracking['status']),
                'status_class'       => $status_class,
                'edit'               => $this->url->link('catalog/seo/editKeywordTracking', 'user_token=' . $this->session->data['user_token'] . '&tracking_id=' . $tracking['tracking_id'] . $url, true),
                'delete'             => $this->url->link('catalog/seo/deleteKeywordTracking', 'user_token=' . $this->session->data['user_token'] . '&tracking_id=' . $tracking['tracking_id'] . $url, true)
            );
        }
        
        // Paginación
        $pagination = new Pagination();
        $pagination->total = $keyword_tracking_total;
        $pagination->page = $data['page'];
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('catalog/seo/keywordTrackings', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);
        
        $data['pagination'] = $pagination->render();
        
        $data['results'] = sprintf($this->language->get('text_pagination'), ($keyword_tracking_total) ? (($data['page'] - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($data['page'] - 1) * $this->config->get('config_limit_admin')) > ($keyword_tracking_total - $this->config->get('config_limit_admin'))) ? $keyword_tracking_total : ((($data['page'] - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $keyword_tracking_total, ceil($keyword_tracking_total / $this->config->get('config_limit_admin')));
        
        // Enlaces
        $data['add'] = $this->url->link('catalog/seo/addKeywordTracking', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['delete'] = $this->url->link('catalog/seo/deleteKeywordTracking', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['back'] = $this->url->link('catalog/seo', 'user_token=' . $this->session->data['user_token'], true);
        $data['check_rankings'] = $this->url->link('catalog/seo/checkRankings', 'user_token=' . $this->session->data['user_token'] . $url, true);
        
        // Cargar las vistas
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('catalog/seo_keyword_tracking_list', $data));
    }
    
    protected function getInternalLinksList() {
        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $link_id) {
                $this->model_catalog_seo->deleteInternalLink($link_id);
            }
            
            $this->session->data['success'] = $this->language->get('text_success');
            
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
            
            $this->response->redirect($this->url->link('catalog/seo/internalLinks', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }
        
        // Información de la página
        $data['heading_title'] = $this->language->get('heading_title') . ' - ' . $this->language->get('text_internal_links');
        $data['text_list'] = $this->language->get('text_internal_links_list');
        $data['text_no_results'] = $this->language->get('text_no_results');
        $data['text_confirm'] = $this->language->get('text_confirm');
        
        $data['column_source_page'] = $this->language->get('column_source_page');
        $data['column_target_page'] = $this->language->get('column_target_page');
        $data['column_anchor_text'] = $this->language->get('column_anchor_text');
        $data['column_status'] = $this->language->get('column_status');
        $data['column_date_added'] = $this->language->get('column_date_added');
        $data['column_action'] = $this->language->get('column_action');
        
        $data['button_add'] = $this->language->get('button_add');
        $data['button_edit'] = $this->language->get('button_edit');
        $data['button_delete'] = $this->language->get('button_delete');
        $data['button_back'] = $this->language->get('button_back');
        
        $data['user_token'] = $this->session->data['user_token'];
        
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }
        
        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }
        
        if (isset($this->request->post['selected'])) {
            $data['selected'] = (array)$this->request->post['selected'];
        } else {
            $data['selected'] = array();
        }
        
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
        
        // Ordenación
        $data['sort'] = isset($this->request->get['sort']) ? $this->request->get['sort'] : 'source_page';
        $data['order'] = isset($this->request->get['order']) ? $this->request->get['order'] : 'ASC';
        $data['page'] = isset($this->request->get['page']) ? $this->request->get['page'] : 1;
        
        // Consultar datos de enlaces internos
        $filter_data = array(
            'sort'  => $data['sort'],
            'order' => $data['order'],
            'start' => ($data['page'] - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );
        
        $internal_links_total = $this->model_catalog_seo->getTotalInternalLinks();
        $internal_links = $this->model_catalog_seo->getInternalLinks($filter_data);
        
        $data['internal_links'] = array();
        
        foreach ($internal_links as $link) {
            $data['internal_links'][] = array(
                'link_id'       => $link['link_id'],
                'source_page'   => $link['source_page'],
                'target_page'   => $link['target_page'],
                'anchor_text'   => $link['anchor_text'],
                'status'        => $link['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
                'date_added'    => date($this->language->get('date_format_short'), strtotime($link['date_added'])),
                'edit'          => $this->url->link('catalog/seo/editInternalLink', 'user_token=' . $this->session->data['user_token'] . '&link_id=' . $link['link_id'] . $url, true),
                'delete'        => $this->url->link('catalog/seo/deleteInternalLink', 'user_token=' . $this->session->data['user_token'] . '&link_id=' . $link['link_id'] . $url, true)
            );
        }
        
        // Paginación
        $pagination = new Pagination();
        $pagination->total = $internal_links_total;
        $pagination->page = $data['page'];
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('catalog/seo/internalLinks', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);
        
        $data['pagination'] = $pagination->render();
        
        $data['results'] = sprintf($this->language->get('text_pagination'), ($internal_links_total) ? (($data['page'] - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($data['page'] - 1) * $this->config->get('config_limit_admin')) > ($internal_links_total - $this->config->get('config_limit_admin'))) ? $internal_links_total : ((($data['page'] - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $internal_links_total, ceil($internal_links_total / $this->config->get('config_limit_admin')));
        
        // Enlaces
        $data['add'] = $this->url->link('catalog/seo/addInternalLink', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['delete'] = $this->url->link('catalog/seo/deleteInternalLink', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['back'] = $this->url->link('catalog/seo', 'user_token=' . $this->session->data['user_token'], true);
        
        // Cargar las vistas
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('catalog/seo_internal_links_list', $data));
    }
    
    protected function getPageAnalysisList() {
        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $analysis_id) {
                $this->model_catalog_seo->deletePageAnalysis($analysis_id);
            }
            
            $this->session->data['success'] = $this->language->get('text_success');
            
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
            
            $this->response->redirect($this->url->link('catalog/seo/pageAnalysis', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }
        
        // Información de la página
        $data['heading_title'] = $this->language->get('heading_title') . ' - ' . $this->language->get('text_page_analysis');
        $data['text_list'] = $this->language->get('text_page_analysis_list');
        $data['text_no_results'] = $this->language->get('text_no_results');
        $data['text_confirm'] = $this->language->get('text_confirm');
        
        $data['column_page_url'] = $this->language->get('column_page_url');
        $data['column_target_keyword'] = $this->language->get('column_target_keyword');
        $data['column_overall_score'] = $this->language->get('column_overall_score');
        $data['column_date_analysis'] = $this->language->get('column_date_analysis');
        $data['column_action'] = $this->language->get('column_action');
        
        $data['button_add'] = $this->language->get('button_add');
        $data['button_view'] = $this->language->get('button_view');
        $data['button_delete'] = $this->language->get('button_delete');
        $data['button_back'] = $this->language->get('button_back');
        $data['button_analyze'] = $this->language->get('button_analyze');
        
        $data['user_token'] = $this->session->data['user_token'];
        
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }
        
        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }
        
        if (isset($this->request->post['selected'])) {
            $data['selected'] = (array)$this->request->post['selected'];
        } else {
            $data['selected'] = array();
        }
        
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
        
        // Ordenación
        $data['sort'] = isset($this->request->get['sort']) ? $this->request->get['sort'] : 'date_analysis';
        $data['order'] = isset($this->request->get['order']) ? $this->request->get['order'] : 'DESC';
        $data['page'] = isset($this->request->get['page']) ? $this->request->get['page'] : 1;
        
        // Consultar datos de análisis de página
        $filter_data = array(
            'sort'  => $data['sort'],
            'order' => $data['order'],
            'start' => ($data['page'] - 1) * $this->config->get('config_limit_admin'),
            'limit' => $this->config->get('config_limit_admin')
        );
        
        $page_analysis_total = $this->model_catalog_seo->getTotalPageAnalyses();
        $page_analyses = $this->model_catalog_seo->getPageAnalyses($filter_data);
        
        $data['page_analyses'] = array();
        
        foreach ($page_analyses as $analysis) {
            // Determinar clase de puntuación
            $score_class = '';
            $score = $analysis['overall_score'];
            
            if ($score >= 80) {
                $score_class = 'success';
            } elseif ($score >= 60) {
                $score_class = 'warning';
            } else {
                $score_class = 'danger';
            }
            
            $data['page_analyses'][] = array(
                'analysis_id'       => $analysis['analysis_id'],
                'page_url'          => $analysis['page_url'],
                'target_keyword'    => $analysis['target_keyword'],
                'overall_score'     => $analysis['overall_score'],
                'score_class'       => $score_class,
                'date_analysis'     => date($this->language->get('date_format_short'), strtotime($analysis['date_analysis'])),
                'view'              => $this->url->link('catalog/seo/viewPageAnalysis', 'user_token=' . $this->session->data['user_token'] . '&analysis_id=' . $analysis['analysis_id'] . $url, true),
                'delete'            => $this->url->link('catalog/seo/deletePageAnalysis', 'user_token=' . $this->session->data['user_token'] . '&analysis_id=' . $analysis['analysis_id'] . $url, true)
            );
        }
        
        // Paginación
        $pagination = new Pagination();
        $pagination->total = $page_analysis_total;
        $pagination->page = $data['page'];
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('catalog/seo/pageAnalysis', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);
        
        $data['pagination'] = $pagination->render();
        
        $data['results'] = sprintf($this->language->get('text_pagination'), ($page_analysis_total) ? (($data['page'] - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($data['page'] - 1) * $this->config->get('config_limit_admin')) > ($page_analysis_total - $this->config->get('config_limit_admin'))) ? $page_analysis_total : ((($data['page'] - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $page_analysis_total, ceil($page_analysis_total / $this->config->get('config_limit_admin')));
        
        // Enlaces
        $data['add'] = $this->url->link('catalog/seo/addPageAnalysis', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['delete'] = $this->url->link('catalog/seo/deletePageAnalysis', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['back'] = $this->url->link('catalog/seo', 'user_token=' . $this->session->data['user_token'], true);
        $data['analyze'] = $this->url->link('catalog/seo/analyzeAllPages', 'user_token=' . $this->session->data['user_token'] . $url, true);
        
        // Cargar las vistas
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('catalog/seo_page_analysis_list', $data));
    }
    
    protected function getSettingsForm() {
        $data['heading_title'] = $this->language->get('heading_title') . ' - ' . $this->language->get('text_settings');
        
        $data['text_edit'] = $this->language->get('text_edit');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['text_yes'] = $this->language->get('text_yes');
        $data['text_no'] = $this->language->get('text_no');
        
        $data['entry_meta_title_format'] = $this->language->get('entry_meta_title_format');
        $data['entry_meta_description_format'] = $this->language->get('entry_meta_description_format');
        $data['entry_meta_keywords_format'] = $this->language->get('entry_meta_keywords_format');
        $data['entry_auto_keyword'] = $this->language->get('entry_auto_keyword');
        $data['entry_keyword_separator'] = $this->language->get('entry_keyword_separator');
        $data['entry_seo_analytics'] = $this->language->get('entry_seo_analytics');
        $data['entry_check_frequency'] = $this->language->get('entry_check_frequency');
        
        $data['help_meta_title_format'] = $this->language->get('help_meta_title_format');
        $data['help_meta_description_format'] = $this->language->get('help_meta_description_format');
        $data['help_meta_keywords_format'] = $this->language->get('help_meta_keywords_format');
        $data['help_auto_keyword'] = $this->language->get('help_auto_keyword');
        $data['help_keyword_separator'] = $this->language->get('help_keyword_separator');
        $data['help_seo_analytics'] = $this->language->get('help_seo_analytics');
        $data['help_check_frequency'] = $this->language->get('help_check_frequency');
        
        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');
        $data['button_back'] = $this->language->get('button_back');
        
        $data['user_token'] = $this->session->data['user_token'];
        
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }
        
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('catalog/seo', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_settings'),
            'href' => $this->url->link('catalog/seo/settings', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['action'] = $this->url->link('catalog/seo/settings', 'user_token=' . $this->session->data['user_token'], true);
        $data['back'] = $this->url->link('catalog/seo', 'user_token=' . $this->session->data['user_token'], true);
        
        // Cargar configuraciones actuales
        $settings = $this->model_catalog_seo->getSeoSettings();
        
        if (isset($this->request->post['meta_title_format'])) {
            $data['meta_title_format'] = $this->request->post['meta_title_format'];
        } elseif (isset($settings['meta_title_format'])) {
            $data['meta_title_format'] = $settings['meta_title_format'];
        } else {
            $data['meta_title_format'] = '{title} | {site_name}';
        }
        
        if (isset($this->request->post['meta_description_format'])) {
            $data['meta_description_format'] = $this->request->post['meta_description_format'];
        } elseif (isset($settings['meta_description_format'])) {
            $data['meta_description_format'] = $settings['meta_description_format'];
        } else {
            $data['meta_description_format'] = '{description}';
        }
        
        if (isset($this->request->post['meta_keywords_format'])) {
            $data['meta_keywords_format'] = $this->request->post['meta_keywords_format'];
        } elseif (isset($settings['meta_keywords_format'])) {
            $data['meta_keywords_format'] = $settings['meta_keywords_format'];
        } else {
            $data['meta_keywords_format'] = '{keywords}';
        }
        
        if (isset($this->request->post['auto_keyword'])) {
            $data['auto_keyword'] = $this->request->post['auto_keyword'];
        } elseif (isset($settings['auto_keyword'])) {
            $data['auto_keyword'] = $settings['auto_keyword'];
        } else {
            $data['auto_keyword'] = 1;
        }
        
        if (isset($this->request->post['keyword_separator'])) {
            $data['keyword_separator'] = $this->request->post['keyword_separator'];
        } elseif (isset($settings['keyword_separator'])) {
            $data['keyword_separator'] = $settings['keyword_separator'];
        } else {
            $data['keyword_separator'] = '-';
        }
        
        if (isset($this->request->post['seo_analytics'])) {
            $data['seo_analytics'] = $this->request->post['seo_analytics'];
        } elseif (isset($settings['seo_analytics'])) {
            $data['seo_analytics'] = $settings['seo_analytics'];
        } else {
            $data['seo_analytics'] = 1;
        }
        
        if (isset($this->request->post['check_frequency'])) {
            $data['check_frequency'] = $this->request->post['check_frequency'];
        } elseif (isset($settings['check_frequency'])) {
            $data['check_frequency'] = $settings['check_frequency'];
        } else {
            $data['check_frequency'] = 'weekly';
        }
        
        // Opciones para frecuencia de comprobación
        $data['check_frequencies'] = array(
            'daily'   => $this->language->get('text_daily'),
            'weekly'  => $this->language->get('text_weekly'),
            'monthly' => $this->language->get('text_monthly')
        );
        
        // Cargar las vistas
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('catalog/seo_settings_form', $data));
    }
    
    // Métodos para añadir, editar y eliminar keywords, enlaces internos y análisis
    
    public function addKeywordTracking() {
        $this->load->language('catalog/seo');
        $this->document->setTitle($this->language->get('heading_title') . ' - ' . $this->language->get('text_add_keyword'));
        
        $this->load->model('catalog/seo');
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateKeywordForm()) {
            $this->model_catalog_seo->addKeywordTracking($this->request->post);
            
            $this->session->data['success'] = $this->language->get('text_success');
            
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
            
            $this->response->redirect($this->url->link('catalog/seo/keywordTrackings', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }
        
        $this->getKeywordForm();
    }
    
public function editKeywordTracking() {
        $this->load->language('catalog/seo');
        $this->document->setTitle($this->language->get('heading_title') . ' - ' . $this->language->get('text_edit_keyword'));
        
        $this->load->model('catalog/seo');
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateKeywordForm()) {
            $this->model_catalog_seo->updateKeywordTracking($this->request->get['tracking_id'], $this->request->post);
            
            $this->session->data['success'] = $this->language->get('text_success');
            
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
            
            $this->response->redirect($this->url->link('catalog/seo/keywordTrackings', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }
        
        $this->getKeywordForm();
    }
    
    public function deleteKeywordTracking() {
        $this->load->language('catalog/seo');
        $this->load->model('catalog/seo');
        
        if (isset($this->request->get['tracking_id']) && $this->validateDelete()) {
            $this->model_catalog_seo->deleteKeywordTracking($this->request->get['tracking_id']);
            
            $this->session->data['success'] = $this->language->get('text_success');
            
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
            
            $this->response->redirect($this->url->link('catalog/seo/keywordTrackings', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }
        
        $this->getKeywordTrackingsList();
    }
    
    public function checkRankings() {
        $this->load->language('catalog/seo');
        $this->load->model('catalog/seo');
        
        // Aquí se implementaría la lógica para comprobar rankings
        // Puedes integrar con servicios externos de SEO para obtener datos reales
        
        $this->session->data['success'] = $this->language->get('text_check_success');
        
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
        
        $this->response->redirect($this->url->link('catalog/seo/keywordTrackings', 'user_token=' . $this->session->data['user_token'] . $url, true));
    }
    
    public function addInternalLink() {
        $this->load->language('catalog/seo');
        $this->document->setTitle($this->language->get('heading_title') . ' - ' . $this->language->get('text_add_internal_link'));
        
        $this->load->model('catalog/seo');
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateInternalLinkForm()) {
            $this->model_catalog_seo->addInternalLink($this->request->post);
            
            $this->session->data['success'] = $this->language->get('text_success');
            
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
            
            $this->response->redirect($this->url->link('catalog/seo/internalLinks', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }
        
        $this->getInternalLinkForm();
    }
    
    public function editInternalLink() {
        $this->load->language('catalog/seo');
        $this->document->setTitle($this->language->get('heading_title') . ' - ' . $this->language->get('text_edit_internal_link'));
        
        $this->load->model('catalog/seo');
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateInternalLinkForm()) {
            $this->model_catalog_seo->updateInternalLink($this->request->get['link_id'], $this->request->post);
            
            $this->session->data['success'] = $this->language->get('text_success');
            
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
            
            $this->response->redirect($this->url->link('catalog/seo/internalLinks', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }
        
        $this->getInternalLinkForm();
    }
    
    public function deleteInternalLink() {
        $this->load->language('catalog/seo');
        $this->load->model('catalog/seo');
        
        if (isset($this->request->get['link_id']) && $this->validateDelete()) {
            $this->model_catalog_seo->deleteInternalLink($this->request->get['link_id']);
            
            $this->session->data['success'] = $this->language->get('text_success');
            
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
            
            $this->response->redirect($this->url->link('catalog/seo/internalLinks', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }
        
        $this->getInternalLinksList();
    }
    
    public function addPageAnalysis() {
        $this->load->language('catalog/seo');
        $this->document->setTitle($this->language->get('heading_title') . ' - ' . $this->language->get('text_add_page_analysis'));
        
        $this->load->model('catalog/seo');
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validatePageAnalysisForm()) {
            $this->model_catalog_seo->addPageAnalysis($this->request->post);
            
            $this->session->data['success'] = $this->language->get('text_success');
            
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
            
            $this->response->redirect($this->url->link('catalog/seo/pageAnalysis', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }
        
        $this->getPageAnalysisForm();
    }
    
    public function viewPageAnalysis() {
        $this->load->language('catalog/seo');
        $this->document->setTitle($this->language->get('heading_title') . ' - ' . $this->language->get('text_view_page_analysis'));
        
        $this->load->model('catalog/seo');
        
        $this->getPageAnalysisDetail();
    }
    
    public function deletePageAnalysis() {
        $this->load->language('catalog/seo');
        $this->load->model('catalog/seo');
        
        if (isset($this->request->get['analysis_id']) && $this->validateDelete()) {
            $this->model_catalog_seo->deletePageAnalysis($this->request->get['analysis_id']);
            
            $this->session->data['success'] = $this->language->get('text_success');
            
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
            
            $this->response->redirect($this->url->link('catalog/seo/pageAnalysis', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }
        
        $this->getPageAnalysisList();
    }
    
    // Métodos de formulario
    
    protected function getKeywordForm() {
        $data['heading_title'] = $this->language->get('heading_title');
        
        $data['text_form'] = !isset($this->request->get['tracking_id']) ? $this->language->get('text_add_keyword') : $this->language->get('text_edit_keyword');
        
        $data['entry_keyword'] = $this->language->get('entry_keyword');
        $data['entry_search_engine'] = $this->language->get('entry_search_engine');
        $data['entry_position'] = $this->language->get('entry_position');
        $data['entry_url'] = $this->language->get('entry_url');
        
        $data['help_keyword'] = $this->language->get('help_keyword');
        $data['help_search_engine'] = $this->language->get('help_search_engine');
        $data['help_position'] = $this->language->get('help_position');
        $data['help_url'] = $this->language->get('help_url');
        
        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');
        
        $data['user_token'] = $this->session->data['user_token'];
        
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }
        
        if (isset($this->error['keyword'])) {
            $data['error_keyword'] = $this->error['keyword'];
        } else {
            $data['error_keyword'] = '';
        }
        
        if (isset($this->error['search_engine'])) {
            $data['error_search_engine'] = $this->error['search_engine'];
        } else {
            $data['error_search_engine'] = '';
        }
        
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
        
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('catalog/seo', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_keyword_tracking'),
            'href' => $this->url->link('catalog/seo/keywordTrackings', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );
        
        if (!isset($this->request->get['tracking_id'])) {
            $data['action'] = $this->url->link('catalog/seo/addKeywordTracking', 'user_token=' . $this->session->data['user_token'] . $url, true);
        } else {
            $data['action'] = $this->url->link('catalog/seo/editKeywordTracking', 'user_token=' . $this->session->data['user_token'] . '&tracking_id=' . $this->request->get['tracking_id'] . $url, true);
        }
        
        $data['cancel'] = $this->url->link('catalog/seo/keywordTrackings', 'user_token=' . $this->session->data['user_token'] . $url, true);
        
        // Datos del formulario
        if (isset($this->request->get['tracking_id'])) {
            $keyword_info = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_keyword_tracking WHERE tracking_id = '" . (int)$this->request->get['tracking_id'] . "'")->row;
        }
        
        if (isset($this->request->post['keyword'])) {
            $data['keyword'] = $this->request->post['keyword'];
        } elseif (!empty($keyword_info)) {
            $data['keyword'] = $keyword_info['keyword'];
        } else {
            $data['keyword'] = '';
        }
        
        if (isset($this->request->post['search_engine'])) {
            $data['search_engine'] = $this->request->post['search_engine'];
        } elseif (!empty($keyword_info)) {
            $data['search_engine'] = $keyword_info['search_engine'];
        } else {
            $data['search_engine'] = 'google';
        }
        
        if (isset($this->request->post['position'])) {
            $data['position'] = $this->request->post['position'];
        } elseif (!empty($keyword_info)) {
            $data['position'] = $keyword_info['position'];
        } else {
            $data['position'] = '';
        }
        
        if (isset($this->request->post['url'])) {
            $data['url'] = $this->request->post['url'];
        } elseif (!empty($keyword_info)) {
            $data['url'] = $keyword_info['url'];
        } else {
            $data['url'] = '';
        }
        
        // Opciones de motores de búsqueda
        $data['search_engines'] = array(
            'google' => 'Google',
            'bing'   => 'Bing',
            'yahoo'  => 'Yahoo',
            'yandex' => 'Yandex'
        );
        
        // Cargar las vistas
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('catalog/seo_keyword_form', $data));
    }
    
    protected function getInternalLinkForm() {
        $data['heading_title'] = $this->language->get('heading_title');
        
        $data['text_form'] = !isset($this->request->get['link_id']) ? $this->language->get('text_add_internal_link') : $this->language->get('text_edit_internal_link');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        
        $data['entry_source_page'] = $this->language->get('entry_source_page');
        $data['entry_target_page'] = $this->language->get('entry_target_page');
        $data['entry_anchor_text'] = $this->language->get('entry_anchor_text');
        $data['entry_status'] = $this->language->get('entry_status');
        
        $data['help_source_page'] = $this->language->get('help_source_page');
        $data['help_target_page'] = $this->language->get('help_target_page');
        $data['help_anchor_text'] = $this->language->get('help_anchor_text');
        
        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');
        
        $data['user_token'] = $this->session->data['user_token'];
        
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }
        
        if (isset($this->error['source_page'])) {
            $data['error_source_page'] = $this->error['source_page'];
        } else {
            $data['error_source_page'] = '';
        }
        
        if (isset($this->error['target_page'])) {
            $data['error_target_page'] = $this->error['target_page'];
        } else {
            $data['error_target_page'] = '';
        }
        
        if (isset($this->error['anchor_text'])) {
            $data['error_anchor_text'] = $this->error['anchor_text'];
        } else {
            $data['error_anchor_text'] = '';
        }
        
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
        
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('catalog/seo', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_internal_links'),
            'href' => $this->url->link('catalog/seo/internalLinks', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );
        
        if (!isset($this->request->get['link_id'])) {
            $data['action'] = $this->url->link('catalog/seo/addInternalLink', 'user_token=' . $this->session->data['user_token'] . $url, true);
        } else {
            $data['action'] = $this->url->link('catalog/seo/editInternalLink', 'user_token=' . $this->session->data['user_token'] . '&link_id=' . $this->request->get['link_id'] . $url, true);
        }
        
        $data['cancel'] = $this->url->link('catalog/seo/internalLinks', 'user_token=' . $this->session->data['user_token'] . $url, true);
        
        // Datos del formulario
        if (isset($this->request->get['link_id'])) {
            $link_info = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_internal_link WHERE link_id = '" . (int)$this->request->get['link_id'] . "'")->row;
        }
        
        if (isset($this->request->post['source_page'])) {
            $data['source_page'] = $this->request->post['source_page'];
        } elseif (!empty($link_info)) {
            $data['source_page'] = $link_info['source_page'];
        } else {
            $data['source_page'] = '';
        }
        
        if (isset($this->request->post['target_page'])) {
            $data['target_page'] = $this->request->post['target_page'];
        } elseif (!empty($link_info)) {
            $data['target_page'] = $link_info['target_page'];
        } else {
            $data['target_page'] = '';
        }
        
        if (isset($this->request->post['anchor_text'])) {
            $data['anchor_text'] = $this->request->post['anchor_text'];
        } elseif (!empty($link_info)) {
            $data['anchor_text'] = $link_info['anchor_text'];
        } else {
            $data['anchor_text'] = '';
        }
        
        if (isset($this->request->post['status'])) {
            $data['status'] = $this->request->post['status'];
        } elseif (!empty($link_info)) {
            $data['status'] = $link_info['status'];
        } else {
            $data['status'] = 1;
        }
        
        // Obtener páginas disponibles para autocompletar
        $data['available_pages'] = $this->model_catalog_seo->getAvailablePages();
        
        // Cargar las vistas
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('catalog/seo_internal_link_form', $data));
    }
    
    protected function getPageAnalysisForm() {
        $data['heading_title'] = $this->language->get('heading_title');
        
        $data['text_form'] = $this->language->get('text_add_page_analysis');
        
        $data['entry_page_url'] = $this->language->get('entry_page_url');
        $data['entry_target_keyword'] = $this->language->get('entry_target_keyword');
        
        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');
        $data['button_analyze'] = $this->language->get('button_analyze');
        
        $data['user_token'] = $this->session->data['user_token'];
        
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }
        
        if (isset($this->error['page_url'])) {
            $data['error_page_url'] = $this->error['page_url'];
        } else {
            $data['error_page_url'] = '';
        }
        
        if (isset($this->error['target_keyword'])) {
            $data['error_target_keyword'] = $this->error['target_keyword'];
        } else {
            $data['error_target_keyword'] = '';
        }
        
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
        
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('catalog/seo', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_page_analysis'),
            'href' => $this->url->link('catalog/seo/pageAnalysis', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );
        
        $data['action'] = $this->url->link('catalog/seo/addPageAnalysis', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['cancel'] = $this->url->link('catalog/seo/pageAnalysis', 'user_token=' . $this->session->data['user_token'] . $url, true);
        
        // Datos del formulario
        if (isset($this->request->post['page_url'])) {
            $data['page_url'] = $this->request->post['page_url'];
        } else {
            $data['page_url'] = '';
        }
        
        if (isset($this->request->post['target_keyword'])) {
            $data['target_keyword'] = $this->request->post['target_keyword'];
        } else {
            $data['target_keyword'] = '';
        }
        
        // Obtener páginas disponibles para autocompletar
        $data['available_pages'] = $this->model_catalog_seo->getAvailablePages();
        
        // Cargar las vistas
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('catalog/seo_page_analysis_form', $data));
    }
    
    protected function getPageAnalysisDetail() {
        $this->load->language('catalog/seo');
        
        $data['heading_title'] = $this->language->get('heading_title');
        
        $data['text_view'] = $this->language->get('text_view_page_analysis');
        
        $data['entry_page_url'] = $this->language->get('entry_page_url');
        $data['entry_target_keyword'] = $this->language->get('entry_target_keyword');
        $data['entry_title_score'] = $this->language->get('entry_title_score');
        $data['entry_meta_score'] = $this->language->get('entry_meta_score');
        $data['entry_content_score'] = $this->language->get('entry_content_score');
        $data['entry_technical_score'] = $this->language->get('entry_technical_score');
        $data['entry_overall_score'] = $this->language->get('entry_overall_score');
        $data['entry_suggestions'] = $this->language->get('entry_suggestions');
        
        $data['button_back'] = $this->language->get('button_back');
        $data['button_reanalyze'] = $this->language->get('button_reanalyze');
        
        $data['user_token'] = $this->session->data['user_token'];
        
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
        
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('catalog/seo', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_page_analysis'),
            'href' => $this->url->link('catalog/seo/pageAnalysis', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );
        
        $data['back'] = $this->url->link('catalog/seo/pageAnalysis', 'user_token=' . $this->session->data['user_token'] . $url, true);
$data['reanalyze'] = $this->url->link('catalog/seo/reanalyze', 'user_token=' . $this->session->data['user_token'] . '&analysis_id=' . $this->request->get['analysis_id'] . $url, true);
        
        // Cargar datos del análisis
        if (isset($this->request->get['analysis_id'])) {
            $analysis_info = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_page_analysis WHERE analysis_id = '" . (int)$this->request->get['analysis_id'] . "'")->row;
        }
        
        if (!empty($analysis_info)) {
            $data['analysis_id'] = $analysis_info['analysis_id'];
            $data['page_url'] = $analysis_info['page_url'];
            $data['target_keyword'] = $analysis_info['target_keyword'];
            $data['title_score'] = $analysis_info['title_score'];
            $data['meta_score'] = $analysis_info['meta_score'];
            $data['content_score'] = $analysis_info['content_score'];
            $data['technical_score'] = $analysis_info['technical_score'];
            $data['overall_score'] = $analysis_info['overall_score'];
            $data['suggestions'] = $analysis_info['suggestions'];
            $data['date_analysis'] = date($this->language->get('date_format_short'), strtotime($analysis_info['date_analysis']));
            
            // Determinar clases de puntuación para las barras de progreso
            $data['title_score_class'] = $this->getScoreClass($analysis_info['title_score']);
            $data['meta_score_class'] = $this->getScoreClass($analysis_info['meta_score']);
            $data['content_score_class'] = $this->getScoreClass($analysis_info['content_score']);
            $data['technical_score_class'] = $this->getScoreClass($analysis_info['technical_score']);
            $data['overall_score_class'] = $this->getScoreClass($analysis_info['overall_score']);
            
            // Obtener enlaces internos para esta página
            $data['internal_links'] = $this->model_catalog_seo->getInternalLinksForPage($analysis_info['page_url']);
        } else {
            $this->response->redirect($this->url->link('catalog/seo/pageAnalysis', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }
        
        // Cargar las vistas
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('catalog/seo_page_analysis_detail', $data));
    }
    
    // Métodos auxiliares
    
    private function getScoreClass($score) {
        if ($score >= 80) {
            return 'success';
        } elseif ($score >= 60) {
            return 'warning';
        } else {
            return 'danger';
        }
    }
    
    // Métodos de validación
    
    protected function validateKeywordForm() {
        if (!$this->user->hasKey('modify.catalog.seo')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        if ((utf8_strlen($this->request->post['keyword']) < 3) || (utf8_strlen($this->request->post['keyword']) > 255)) {
            $this->error['keyword'] = $this->language->get('error_keyword');
        }
        
        if (empty($this->request->post['search_engine'])) {
            $this->error['search_engine'] = $this->language->get('error_search_engine');
        }
        
        return !$this->error;
    }
    
    protected function validateInternalLinkForm() {
        if (!$this->user->hasKey('modify.catalog.seo')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        if (empty($this->request->post['source_page'])) {
            $this->error['source_page'] = $this->language->get('error_source_page');
        }
        
        if (empty($this->request->post['target_page'])) {
            $this->error['target_page'] = $this->language->get('error_target_page');
        }
        
        if (empty($this->request->post['anchor_text'])) {
            $this->error['anchor_text'] = $this->language->get('error_anchor_text');
        }
        
        return !$this->error;
    }
    
    protected function validatePageAnalysisForm() {
        if (!$this->user->hasKey('modify.catalog.seo')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        if (empty($this->request->post['page_url'])) {
            $this->error['page_url'] = $this->language->get('error_page_url');
        }
        
        if (empty($this->request->post['target_keyword'])) {
            $this->error['target_keyword'] = $this->language->get('error_target_keyword');
        }
        
        return !$this->error;
    }
    
    protected function validateSettingsForm() {
        if (!$this->user->hasKey('modify.catalog.seo')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        return !$this->error;
    }
    
    protected function validateDelete() {
        if (!$this->user->hasKey('modify.catalog.seo')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        return !$this->error;
    }
    
    // Métodos AJAX
    
    public function analyzeUrl() {
        $json = array();
        
        $this->load->language('catalog/seo');
        $this->load->model('catalog/seo');
        
        if (isset($this->request->post['page_url']) && isset($this->request->post['target_keyword'])) {
            // Aquí implementaríamos el análisis real de la URL
            // Para este ejemplo, simularemos los resultados
            
            $title_score = rand(50, 100);
            $meta_score = rand(50, 100);
            $content_score = rand(50, 100);
            $technical_score = rand(50, 100);
            $overall_score = round(($title_score + $meta_score + $content_score + $technical_score) / 4);
            
            $suggestions = array(
                'Optimiza tu título incluyendo la palabra clave al principio.',
                'Mejora la densidad de la palabra clave en el contenido.',
                'Añade más enlaces internos hacia esta página.',
                'Optimiza las imágenes con texto alternativo relevante.'
            );
            
            $json['success'] = true;
            $json['analysis'] = array(
                'title_score' => $title_score,
                'meta_score' => $meta_score,
                'content_score' => $content_score,
                'technical_score' => $technical_score,
                'overall_score' => $overall_score,
                'suggestions' => implode("\n", $suggestions)
            );
        } else {
            $json['error'] = $this->language->get('error_analyze_missing_params');
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function autocompletePages() {
        $json = array();
        
        if (isset($this->request->get['filter_name'])) {
            $this->load->model('catalog/seo');
            
            $filter_data = array(
                'filter_name' => $this->request->get['filter_name'],
                'start'       => 0,
                'limit'       => 5
            );
            
            $pages = $this->model_catalog_seo->getAvailablePages();
            
            $filtered_pages = array();
            foreach ($pages as $page) {
                if (stripos($page['url'], $this->request->get['filter_name']) !== false || 
                    stripos($page['keyword'], $this->request->get['filter_name']) !== false) {
                    $filtered_pages[] = $page;
                }
                
                if (count($filtered_pages) >= 5) {
                    break;
                }
            }
            
            foreach ($filtered_pages as $page) {
                $json[] = array(
                    'url'      => $page['url'],
                    'keyword'  => $page['keyword'],
                    'name'     => $page['keyword'] ? $page['url'] . ' (' . $page['keyword'] . ')' : $page['url']
                );
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}