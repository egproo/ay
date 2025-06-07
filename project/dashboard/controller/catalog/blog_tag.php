<?php
class ControllerCatalogBlogTag extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('catalog/blog_tag');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('catalog/blog_tag');

        $this->getList();
    }

    public function add() {
        $this->load->language('catalog/blog_tag');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('catalog/blog_tag');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_catalog_blog_tag->addTag($this->request->post);

            $this->session->data['success'] = $this->language->get('text_success_add');

            $url = '';

            if (isset($this->request->get['filter_name'])) {
                $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['sort'])) {
                $url .= '&sort=' . $this->request->get['sort'];
            }

            if (isset($this->request->get['order'])) {
                $url .= '&order=' . $this->request->get['order'];
            }

            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }

            $this->response->redirect($this->url->link('catalog/blog_tag', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    public function edit() {
        $this->load->language('catalog/blog_tag');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('catalog/blog_tag');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_catalog_blog_tag->editTag($this->request->get['tag_id'], $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success_edit');

            $url = '';

            if (isset($this->request->get['filter_name'])) {
                $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['sort'])) {
                $url .= '&sort=' . $this->request->get['sort'];
            }

            if (isset($this->request->get['order'])) {
                $url .= '&order=' . $this->request->get['order'];
            }

            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }

            $this->response->redirect($this->url->link('catalog/blog_tag', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    public function delete() {
        $this->load->language('catalog/blog_tag');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('catalog/blog_tag');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $tag_id) {
                $this->model_catalog_blog_tag->deleteTag($tag_id);
            }

            $this->session->data['success'] = $this->language->get('text_success_delete');

            $url = '';

            if (isset($this->request->get['filter_name'])) {
                $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['sort'])) {
                $url .= '&sort=' . $this->request->get['sort'];
            }

            if (isset($this->request->get['order'])) {
                $url .= '&order=' . $this->request->get['order'];
            }

            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }

            $this->response->redirect($this->url->link('catalog/blog_tag', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getList();
    }

    public function autocomplete() {
        $json = array();

        if (isset($this->request->get['filter_name'])) {
            $this->load->model('catalog/blog_tag');

            $filter_data = array(
                'filter_name' => $this->request->get['filter_name'],
                'sort'        => 'name',
                'order'       => 'ASC',
                'start'       => 0,
                'limit'       => 15
            );

            $results = $this->model_catalog_blog_tag->getBlogTags($filter_data);

            foreach ($results as $result) {
                $json[] = array(
                    'tag_id' => $result['tag_id'],
                    'name'   => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8'))
                );
            }
        }

        $sort_order = array();

        foreach ($json as $key => $value) {
            $sort_order[$key] = $value['name'];
        }

        array_multisort($sort_order, SORT_ASC, $json);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    protected function getList() {
        if (isset($this->request->get['filter_name'])) {
            $filter_name = $this->request->get['filter_name'];
        } else {
            $filter_name = '';
        }

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'name';
        }

        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'ASC';
        }

        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }

        $url = '';

        if (isset($this->request->get['filter_name'])) {
            $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
        }

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
            'href' => $this->url->link('catalog/blog_tag', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        $data['add'] = $this->url->link('catalog/blog_tag/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['delete'] = $this->url->link('catalog/blog_tag/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['back'] = $this->url->link('catalog/blog', 'user_token=' . $this->session->data['user_token'], true);

        $data['tags'] = array();

        $filter_data = array(
            'filter_name'  => $filter_name,
            'sort'         => $sort,
            'order'        => $order,
            'start'        => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit'        => $this->config->get('config_limit_admin')
        );

        $tag_total = $this->model_catalog_blog_tag->getTotalBlogTags($filter_data);

        $results = $this->model_catalog_blog_tag->getBlogTags($filter_data);

        foreach ($results as $result) {
            // Contar cuántos posts tienen esta etiqueta
            $posts_count = $this->model_catalog_blog_tag->getTagPostsCount($result['tag_id']);

            $data['tags'][] = array(
                'tag_id'      => $result['tag_id'],
                'name'        => $result['name'],
                'slug'        => $result['slug'],
                'posts_count' => $posts_count,
                'edit'        => $this->url->link('catalog/blog_tag/edit', 'user_token=' . $this->session->data['user_token'] . '&tag_id=' . $result['tag_id'] . $url, true)
            );
        }

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

        if (isset($this->request->get['filter_name'])) {
            $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
        }

        if ($order == 'ASC') {
            $url .= '&order=DESC';
        } else {
            $url .= '&order=ASC';
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['sort_name'] = $this->url->link('catalog/blog_tag', 'user_token=' . $this->session->data['user_token'] . '&sort=name' . $url, true);
        $data['sort_posts'] = $this->url->link('catalog/blog_tag', 'user_token=' . $this->session->data['user_token'] . '&sort=posts_count' . $url, true);

        $url = '';

        if (isset($this->request->get['filter_name'])) {
            $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        $pagination = new Pagination();
        $pagination->total = $tag_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('catalog/blog_tag', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($tag_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($tag_total - $this->config->get('config_limit_admin'))) ? $tag_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $tag_total, ceil($tag_total / $this->config->get('config_limit_admin')));

        $data['filter_name'] = $filter_name;
        $data['sort'] = $sort;
        $data['order'] = $order;

        // Permisos
        $data['can_add'] = $this->user->hasKey('catalog_blog_tag_add');
        $data['can_edit'] = $this->user->hasKey('catalog_blog_tag_edit');
        $data['can_delete'] = $this->user->hasKey('catalog_blog_tag_delete');

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('catalog/blog_tag_list', $data));
    }

    protected function getForm() {
        $data['text_form'] = !isset($this->request->get['tag_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['name'])) {
            $data['error_name'] = $this->error['name'];
        } else {
            $data['error_name'] = '';
        }

        if (isset($this->error['slug'])) {
            $data['error_slug'] = $this->error['slug'];
        } else {
            $data['error_slug'] = '';
        }

        $url = '';

        if (isset($this->request->get['filter_name'])) {
            $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
        }

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
            'href' => $this->url->link('catalog/blog_tag', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        if (!isset($this->request->get['tag_id'])) {
            $data['action'] = $this->url->link('catalog/blog_tag/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        } else {
            $data['action'] = $this->url->link('catalog/blog_tag/edit', 'user_token=' . $this->session->data['user_token'] . '&tag_id=' . $this->request->get['tag_id'] . $url, true);
        }

        $data['cancel'] = $this->url->link('catalog/blog_tag', 'user_token=' . $this->session->data['user_token'] . $url, true);

        if (isset($this->request->get['tag_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $tag_info = $this->model_catalog_blog_tag->getTag($this->request->get['tag_id']);
        }

        $data['user_token'] = $this->session->data['user_token'];

        if (isset($this->request->post['name'])) {
            $data['name'] = $this->request->post['name'];
        } elseif (!empty($tag_info)) {
            $data['name'] = $tag_info['name'];
        } else {
            $data['name'] = '';
        }

        if (isset($this->request->post['slug'])) {
            $data['slug'] = $this->request->post['slug'];
        } elseif (!empty($tag_info)) {
            $data['slug'] = $tag_info['slug'];
        } else {
            $data['slug'] = '';
        }

        // Obtener posts relacionados con esta etiqueta
        if (isset($this->request->get['tag_id'])) {
            $data['tag_posts'] = $this->model_catalog_blog_tag->getTagPosts($this->request->get['tag_id']);
        } else {
            $data['tag_posts'] = array();
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('catalog/blog_tag_form', $data));
    }

    protected function validateForm() {
        if (!$this->user->hasKey('catalog_blog_tag_edit')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ((utf8_strlen($this->request->post['name']) < 1) || (utf8_strlen($this->request->post['name']) > 64)) {
            $this->error['name'] = $this->language->get('error_name');
        }

        // Validar slug
        if (empty($this->request->post['slug'])) {
            // Si no hay slug, generarlo a partir del nombre
            $this->request->post['slug'] = $this->generateSlug($this->request->post['name']);
        } else {
            // Si hay slug, asegurarse de que sea único
            $slug = $this->request->post['slug'];
            
            // Comprobar si el slug ya existe
            $tag_id = isset($this->request->get['tag_id']) ? $this->request->get['tag_id'] : 0;
            
            if ($this->model_catalog_blog_tag->isSlugExists($slug, $tag_id)) {
                $this->error['slug'] = $this->language->get('error_slug_exists');
            }
        }

        return !$this->error;
    }

    protected function validateDelete() {
        if (!$this->user->hasKey('catalog_blog_tag_delete')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    public function generateSlug($text) {
        // Convertir a minúsculas
        $text = mb_strtolower($text, 'UTF-8');
        
        // Reemplazar espacios con guiones
        $text = preg_replace('/\s+/', '-', $text);
        
        // Eliminar caracteres especiales
        $text = preg_replace('/[^a-z0-9\-]/', '', $text);
        
        // Eliminar guiones duplicados
        $text = preg_replace('/-+/', '-', $text);
        
        // Eliminar guiones al principio y al final
        $text = trim($text, '-');
        
        return $text;
    }
}