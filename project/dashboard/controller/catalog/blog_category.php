<?php
class ControllerCatalogBlogCategory extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('catalog/blog_category');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('catalog/blog_category');

        $this->getList();
    }

    public function add() {
        $this->load->language('catalog/blog_category');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('catalog/blog_category');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_catalog_blog_category->addCategory($this->request->post);

            $this->session->data['success'] = $this->language->get('text_success_add');

            $url = '';

            if (isset($this->request->get['filter_name'])) {
                $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['filter_status'])) {
                $url .= '&filter_status=' . $this->request->get['filter_status'];
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

            $this->response->redirect($this->url->link('catalog/blog_category', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    public function edit() {
        $this->load->language('catalog/blog_category');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('catalog/blog_category');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_catalog_blog_category->editCategory($this->request->get['category_id'], $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success_edit');

            $url = '';

            if (isset($this->request->get['filter_name'])) {
                $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['filter_status'])) {
                $url .= '&filter_status=' . $this->request->get['filter_status'];
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

            $this->response->redirect($this->url->link('catalog/blog_category', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    public function delete() {
        $this->load->language('catalog/blog_category');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('catalog/blog_category');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $category_id) {
                $this->model_catalog_blog_category->deleteCategory($category_id);
            }

            $this->session->data['success'] = $this->language->get('text_success_delete');

            $url = '';

            if (isset($this->request->get['filter_name'])) {
                $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['filter_status'])) {
                $url .= '&filter_status=' . $this->request->get['filter_status'];
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

            $this->response->redirect($this->url->link('catalog/blog_category', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getList();
    }

    public function autocomplete() {
        $json = array();

        if (isset($this->request->get['filter_name'])) {
            $this->load->model('catalog/blog_category');

            $filter_data = array(
                'filter_name' => $this->request->get['filter_name'],
                'sort'        => 'name',
                'order'       => 'ASC',
                'start'       => 0,
                'limit'       => 15
            );

            $results = $this->model_catalog_blog_category->getBlogCategories($filter_data);

            foreach ($results as $result) {
                $json[] = array(
                    'category_id' => $result['category_id'],
                    'name'        => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8'))
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

        if (isset($this->request->get['filter_status'])) {
            $filter_status = $this->request->get['filter_status'];
        } else {
            $filter_status = '';
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

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
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
            'href' => $this->url->link('catalog/blog_category', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        $data['add'] = $this->url->link('catalog/blog_category/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['delete'] = $this->url->link('catalog/blog_category/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['back'] = $this->url->link('catalog/blog', 'user_token=' . $this->session->data['user_token'], true);

        $data['categories'] = array();

        $filter_data = array(
            'filter_name'   => $filter_name,
            'filter_status' => $filter_status,
            'sort'          => $sort,
            'order'         => $order,
            'start'         => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit'         => $this->config->get('config_limit_admin')
        );

        $category_total = $this->model_catalog_blog_category->getTotalBlogCategories($filter_data);

        $results = $this->model_catalog_blog_category->getBlogCategories($filter_data);

        foreach ($results as $result) {
            // Contar cuántos posts pertenecen a esta categoría
            $posts_count = $this->model_catalog_blog_category->getCategoryPostsCount($result['category_id']);

            // Obtener nombre de la categoría padre
            $parent_name = '';
            if ($result['parent_id'] > 0) {
                $parent_info = $this->model_catalog_blog_category->getCategory($result['parent_id']);
                if ($parent_info) {
                    $parent_name = $parent_info['name'];
                }
            }

            $data['categories'][] = array(
                'category_id' => $result['category_id'],
                'name'        => $result['name'],
                'parent_id'   => $result['parent_id'],
                'parent_name' => $parent_name,
                'slug'        => $result['slug'],
                'sort_order'  => $result['sort_order'],
                'status'      => ($result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled')),
                'status_class' => ($result['status'] ? 'success' : 'danger'),
                'posts_count' => $posts_count,
                'edit'        => $this->url->link('catalog/blog_category/edit', 'user_token=' . $this->session->data['user_token'] . '&category_id=' . $result['category_id'] . $url, true)
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

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

        if ($order == 'ASC') {
            $url .= '&order=DESC';
        } else {
            $url .= '&order=ASC';
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['sort_name'] = $this->url->link('catalog/blog_category', 'user_token=' . $this->session->data['user_token'] . '&sort=name' . $url, true);
        $data['sort_sort_order'] = $this->url->link('catalog/blog_category', 'user_token=' . $this->session->data['user_token'] . '&sort=sort_order' . $url, true);
        $data['sort_posts'] = $this->url->link('catalog/blog_category', 'user_token=' . $this->session->data['user_token'] . '&sort=posts_count' . $url, true);

        $url = '';

        if (isset($this->request->get['filter_name'])) {
            $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        $pagination = new Pagination();
        $pagination->total = $category_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('catalog/blog_category', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($category_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($category_total - $this->config->get('config_limit_admin'))) ? $category_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $category_total, ceil($category_total / $this->config->get('config_limit_admin')));

        $data['filter_name'] = $filter_name;
        $data['filter_status'] = $filter_status;
        $data['sort'] = $sort;
        $data['order'] = $order;

        // Permisos
        $data['can_add'] = $this->user->hasKey('catalog_blog_category_add');
        $data['can_edit'] = $this->user->hasKey('catalog_blog_category_edit');
        $data['can_delete'] = $this->user->hasKey('catalog_blog_category_delete');

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('catalog/blog_category_list', $data));
    }

    protected function getForm() {
        $data['text_form'] = !isset($this->request->get['category_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

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

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
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
            'href' => $this->url->link('catalog/blog_category', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        if (!isset($this->request->get['category_id'])) {
            $data['action'] = $this->url->link('catalog/blog_category/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        } else {
            $data['action'] = $this->url->link('catalog/blog_category/edit', 'user_token=' . $this->session->data['user_token'] . '&category_id=' . $this->request->get['category_id'] . $url, true);
        }

        $data['cancel'] = $this->url->link('catalog/blog_category', 'user_token=' . $this->session->data['user_token'] . $url, true);

        if (isset($this->request->get['category_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $category_info = $this->model_catalog_blog_category->getCategory($this->request->get['category_id']);
        }

        $data['user_token'] = $this->session->data['user_token'];

        if (isset($this->request->post['name'])) {
            $data['name'] = $this->request->post['name'];
        } elseif (!empty($category_info)) {
            $data['name'] = $category_info['name'];
        } else {
            $data['name'] = '';
        }

        if (isset($this->request->post['slug'])) {
            $data['slug'] = $this->request->post['slug'];
        } elseif (!empty($category_info)) {
            $data['slug'] = $category_info['slug'];
        } else {
            $data['slug'] = '';
        }

        if (isset($this->request->post['description'])) {
            $data['description'] = $this->request->post['description'];
        } elseif (!empty($category_info)) {
            $data['description'] = $category_info['description'];
        } else {
            $data['description'] = '';
        }

        // Imagen
        if (isset($this->request->post['image'])) {
            $data['image'] = $this->request->post['image'];
        } elseif (!empty($category_info)) {
            $data['image'] = $category_info['image'];
        } else {
            $data['image'] = '';
        }

        $this->load->model('tool/image');

        if (isset($this->request->post['image']) && is_file(DIR_IMAGE . $this->request->post['image'])) {
            $data['thumb'] = $this->model_tool_image->resize($this->request->post['image'], 100, 100);
        } elseif (!empty($category_info) && is_file(DIR_IMAGE . $category_info['image'])) {
            $data['thumb'] = $this->model_tool_image->resize($category_info['image'], 100, 100);
        } else {
            $data['thumb'] = $this->model_tool_image->resize('no_image.png', 100, 100);
        }

        $data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);

        // Categoría padre
        $this->load->model('catalog/blog_category');

        if (isset($this->request->post['parent_id'])) {
            $data['parent_id'] = $this->request->post['parent_id'];
        } elseif (!empty($category_info)) {
            $data['parent_id'] = $category_info['parent_id'];
        } else {
            $data['parent_id'] = 0;
        }

        // Lista de categorías para el selector
        $data['categories'] = $this->model_catalog_blog_category->getBlogCategories(array('sort' => 'name'));

        // SEO
        if (isset($this->request->post['meta_title'])) {
            $data['meta_title'] = $this->request->post['meta_title'];
        } elseif (!empty($category_info)) {
            $data['meta_title'] = $category_info['meta_title'];
        } else {
            $data['meta_title'] = '';
        }

        if (isset($this->request->post['meta_description'])) {
            $data['meta_description'] = $this->request->post['meta_description'];
        } elseif (!empty($category_info)) {
            $data['meta_description'] = $category_info['meta_description'];
        } else {
            $data['meta_description'] = '';
        }

        if (isset($this->request->post['meta_keywords'])) {
            $data['meta_keywords'] = $this->request->post['meta_keywords'];
        } elseif (!empty($category_info)) {
            $data['meta_keywords'] = $category_info['meta_keywords'];
        } else {
            $data['meta_keywords'] = '';
        }

        // Otros campos
        if (isset($this->request->post['sort_order'])) {
            $data['sort_order'] = $this->request->post['sort_order'];
        } elseif (!empty($category_info)) {
            $data['sort_order'] = $category_info['sort_order'];
        } else {
            $data['sort_order'] = 0;
        }

        if (isset($this->request->post['status'])) {
            $data['status'] = $this->request->post['status'];
        } elseif (!empty($category_info)) {
            $data['status'] = $category_info['status'];
        } else {
            $data['status'] = 1;
        }

        // Obtener posts relacionados con esta categoría
        if (isset($this->request->get['category_id'])) {
            $data['category_posts'] = $this->model_catalog_blog_category->getCategoryPosts($this->request->get['category_id']);
        } else {
            $data['category_posts'] = array();
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('catalog/blog_category_form', $data));
    }

    protected function validateForm() {
        if (!$this->user->hasKey('catalog_blog_category_edit')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ((utf8_strlen($this->request->post['name']) < 1) || (utf8_strlen($this->request->post['name']) > 255)) {
            $this->error['name'] = $this->language->get('error_name');
        }

        // Validar que no se esté seleccionando a sí misma como categoría padre
        if (isset($this->request->get['category_id']) && $this->request->post['parent_id'] == $this->request->get['category_id']) {
            $this->error['warning'] = $this->language->get('error_parent_self');
        }

        // Validar slug
        if (empty($this->request->post['slug'])) {
            // Si no hay slug, generarlo a partir del nombre
            $this->request->post['slug'] = $this->generateSlug($this->request->post['name']);
        } else {
            // Si hay slug, asegurarse de que sea único
            $slug = $this->request->post['slug'];
            
            // Comprobar si el slug ya existe
            $category_id = isset($this->request->get['category_id']) ? $this->request->get['category_id'] : 0;
            
            if ($this->model_catalog_blog_category->isSlugExists($slug, $category_id)) {
                $this->error['slug'] = $this->language->get('error_slug_exists');
            }
        }

        return !$this->error;
    }

    protected function validateDelete() {
        if (!$this->user->hasKey('catalog_blog_category_delete')) {
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