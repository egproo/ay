<?php
class ControllerCatalogBlog extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('catalog/blog');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('catalog/blog');

        $this->getList();
    }

    public function add() {
        $this->load->language('catalog/blog');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('catalog/blog');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $post_id = $this->model_catalog_blog->addPost($this->request->post);

            $this->session->data['success'] = $this->language->get('text_success_add');

            $url = '';

            if (isset($this->request->get['filter_title'])) {
                $url .= '&filter_title=' . urlencode(html_entity_decode($this->request->get['filter_title'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['filter_status'])) {
                $url .= '&filter_status=' . $this->request->get['filter_status'];
            }

            if (isset($this->request->get['filter_date_start'])) {
                $url .= '&filter_date_start=' . $this->request->get['filter_date_start'];
            }

            if (isset($this->request->get['filter_date_end'])) {
                $url .= '&filter_date_end=' . $this->request->get['filter_date_end'];
            }

            if (isset($this->request->get['filter_category'])) {
                $url .= '&filter_category=' . $this->request->get['filter_category'];
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

            $this->response->redirect($this->url->link('catalog/blog', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    public function edit() {
        $this->load->language('catalog/blog');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('catalog/blog');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_catalog_blog->editPost($this->request->get['post_id'], $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success_edit');

            $url = '';

            if (isset($this->request->get['filter_title'])) {
                $url .= '&filter_title=' . urlencode(html_entity_decode($this->request->get['filter_title'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['filter_status'])) {
                $url .= '&filter_status=' . $this->request->get['filter_status'];
            }

            if (isset($this->request->get['filter_date_start'])) {
                $url .= '&filter_date_start=' . $this->request->get['filter_date_start'];
            }

            if (isset($this->request->get['filter_date_end'])) {
                $url .= '&filter_date_end=' . $this->request->get['filter_date_end'];
            }

            if (isset($this->request->get['filter_category'])) {
                $url .= '&filter_category=' . $this->request->get['filter_category'];
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

            $this->response->redirect($this->url->link('catalog/blog', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    public function delete() {
        $this->load->language('catalog/blog');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('catalog/blog');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $post_id) {
                $this->model_catalog_blog->deletePost($post_id);
            }

            $this->session->data['success'] = $this->language->get('text_success_delete');

            $url = '';

            if (isset($this->request->get['filter_title'])) {
                $url .= '&filter_title=' . urlencode(html_entity_decode($this->request->get['filter_title'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['filter_status'])) {
                $url .= '&filter_status=' . $this->request->get['filter_status'];
            }

            if (isset($this->request->get['filter_date_start'])) {
                $url .= '&filter_date_start=' . $this->request->get['filter_date_start'];
            }

            if (isset($this->request->get['filter_date_end'])) {
                $url .= '&filter_date_end=' . $this->request->get['filter_date_end'];
            }

            if (isset($this->request->get['filter_category'])) {
                $url .= '&filter_category=' . $this->request->get['filter_category'];
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

            $this->response->redirect($this->url->link('catalog/blog', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getList();
    }

    public function copy() {
        $this->load->language('catalog/blog');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('catalog/blog');

        if (isset($this->request->post['selected']) && $this->validateCopy()) {
            foreach ($this->request->post['selected'] as $post_id) {
                $this->model_catalog_blog->copyPost($post_id);
            }

            $this->session->data['success'] = $this->language->get('text_success_copy');

            $url = '';

            if (isset($this->request->get['filter_title'])) {
                $url .= '&filter_title=' . urlencode(html_entity_decode($this->request->get['filter_title'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['filter_status'])) {
                $url .= '&filter_status=' . $this->request->get['filter_status'];
            }

            if (isset($this->request->get['filter_date_start'])) {
                $url .= '&filter_date_start=' . $this->request->get['filter_date_start'];
            }

            if (isset($this->request->get['filter_date_end'])) {
                $url .= '&filter_date_end=' . $this->request->get['filter_date_end'];
            }

            if (isset($this->request->get['filter_category'])) {
                $url .= '&filter_category=' . $this->request->get['filter_category'];
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

            $this->response->redirect($this->url->link('catalog/blog', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getList();
    }

    // AJAX endpoint para obtener los datos filtrados en tiempo real
    public function ajaxList() {
        $this->load->language('catalog/blog');
        $this->load->model('catalog/blog');

        $json = array();

        // Para compatibilidad con la paginación y ordenamiento
        $url = '';

        if (isset($this->request->get['filter_title'])) {
            $filter_title = $this->request->get['filter_title'];
            $url .= '&filter_title=' . urlencode(html_entity_decode($this->request->get['filter_title'], ENT_QUOTES, 'UTF-8'));
        } else {
            $filter_title = '';
        }

        if (isset($this->request->get['filter_status'])) {
            $filter_status = $this->request->get['filter_status'];
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        } else {
            $filter_status = '';
        }

        if (isset($this->request->get['filter_date_start'])) {
            $filter_date_start = $this->request->get['filter_date_start'];
            $url .= '&filter_date_start=' . $this->request->get['filter_date_start'];
        } else {
            $filter_date_start = '';
        }

        if (isset($this->request->get['filter_date_end'])) {
            $filter_date_end = $this->request->get['filter_date_end'];
            $url .= '&filter_date_end=' . $this->request->get['filter_date_end'];
        } else {
            $filter_date_end = '';
        }

        if (isset($this->request->get['filter_category'])) {
            $filter_category = $this->request->get['filter_category'];
            $url .= '&filter_category=' . $this->request->get['filter_category'];
        } else {
            $filter_category = '';
        }

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
            $url .= '&sort=' . $this->request->get['sort'];
        } else {
            $sort = 'p.date_published';
        }

        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
            $url .= '&order=' . $this->request->get['order'];
        } else {
            $order = 'DESC';
        }

        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
            $url .= '&page=' . $this->request->get['page'];
        } else {
            $page = 1;
        }

        // Configurar datos para el filtrado
        $filter_data = array(
            'filter_title'      => $filter_title,
            'filter_status'     => $filter_status,
            'filter_date_start' => $filter_date_start,
            'filter_date_end'   => $filter_date_end,
            'filter_category'   => $filter_category,
            'sort'              => $sort,
            'order'             => $order,
            'start'             => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit'             => $this->config->get('config_limit_admin')
        );

        $post_total = $this->model_catalog_blog->getTotalPosts($filter_data);
        $results = $this->model_catalog_blog->getPosts($filter_data);

        $data['posts'] = array();

        // Cargar el modelo de usuarios para obtener datos del autor
        $this->load->model('user/user');

        foreach ($results as $result) {
            // Obtener autor
            $author_info = $this->model_user_user->getUser($result['author_id']);
            
            if ($author_info) {
                $author = $author_info['firstname'] . ' ' . $author_info['lastname'];
            } else {
                $author = $this->language->get('text_unknown');
            }

            // Obtener categorías
            $categories = $this->model_catalog_blog->getPostCategories($result['post_id']);
            $category_names = array();
            
            foreach ($categories as $category) {
                $category_names[] = $category['name'];
            }
            
            // Obtener etiquetas
            $tags = $this->model_catalog_blog->getPostTags($result['post_id']);
            $tag_names = array();
            
            foreach ($tags as $tag) {
                $tag_names[] = $tag['name'];
            }

            $data['posts'][] = array(
                'post_id'       => $result['post_id'],
                'title'         => $result['title'],
                'author'        => $author,
                'categories'    => implode(', ', $category_names),
                'tags'          => implode(', ', $tag_names),
                'status'        => ($result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled')),
                'status_class'  => ($result['status'] ? 'success' : 'danger'),
                'hits'          => $result['hits'],
                'comments'      => $this->model_catalog_blog->getPostCommentsCount($result['post_id']),
                'date_published' => date($this->language->get('date_format_short'), strtotime($result['date_published'])),
                'date_created'  => date($this->language->get('date_format_short'), strtotime($result['date_created'])),
                'edit'          => $this->url->link('catalog/blog/edit', 'user_token=' . $this->session->data['user_token'] . '&post_id=' . $result['post_id'] . $url, true),
                'delete'        => $this->url->link('catalog/blog/delete', 'user_token=' . $this->session->data['user_token'] . '&post_id=' . $result['post_id'] . $url, true),
                'can_edit'      => $this->user->hasKey('catalog_blog_edit'),
                'can_delete'    => $this->user->hasKey('catalog_blog_delete')
            );
        }

        // Construir paginación
        $pagination = new Pagination();
        $pagination->total = $post_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('catalog/blog', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $json['pagination'] = $pagination->render();

        // Información de resultados
        $json['results'] = sprintf($this->language->get('text_pagination'), ($post_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($post_total - $this->config->get('config_limit_admin'))) ? $post_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $post_total, ceil($post_total / $this->config->get('config_limit_admin')));

        // Datos para la tabla
        $json['posts'] = $data['posts'];

        // Estadísticas para el panel de resumen
        $json['stats'] = array(
            'total' => $this->model_catalog_blog->getTotalPosts(array()),
            'published' => $this->model_catalog_blog->getTotalPosts(array('filter_status' => 1)),
            'drafts' => $this->model_catalog_blog->getTotalPosts(array('filter_status' => 0)),
            'comments' => $this->model_catalog_blog->getTotalComments(),
            'active_comments' => $this->model_catalog_blog->getTotalComments(array('filter_status' => 1))
        );

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    protected function getList() {
        // Breadcrumbs
        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('catalog/blog', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['add'] = $this->url->link('catalog/blog/add', 'user_token=' . $this->session->data['user_token'], true);
        $data['copy'] = $this->url->link('catalog/blog/copy', 'user_token=' . $this->session->data['user_token'], true);
        $data['delete'] = $this->url->link('catalog/blog/delete', 'user_token=' . $this->session->data['user_token'], true);
        $data['categories'] = $this->url->link('catalog/blog_category', 'user_token=' . $this->session->data['user_token'], true);
        $data['tags'] = $this->url->link('catalog/blog_tag', 'user_token=' . $this->session->data['user_token'], true);
        $data['comments'] = $this->url->link('catalog/blog_comment', 'user_token=' . $this->session->data['user_token'], true);

        // Permisos
        $data['can_add'] = $this->user->hasKey('catalog_blog_add');
        $data['can_copy'] = $this->user->hasKey('catalog_blog_copy');
        $data['can_delete'] = $this->user->hasKey('catalog_blog_delete');
        $data['can_edit'] = $this->user->hasKey('catalog_blog_edit');

        // Cargar categorías para el filtro
        $this->load->model('catalog/blog_category');
        $data['categories_list'] = $this->model_catalog_blog_category->getBlogCategories();
        
        // Datos para los filtros
        if (isset($this->request->get['filter_title'])) {
            $filter_title = $this->request->get['filter_title'];
        } else {
            $filter_title = '';
        }

        if (isset($this->request->get['filter_status'])) {
            $filter_status = $this->request->get['filter_status'];
        } else {
            $filter_status = '';
        }

        if (isset($this->request->get['filter_date_start'])) {
            $filter_date_start = $this->request->get['filter_date_start'];
        } else {
            $filter_date_start = '';
        }

        if (isset($this->request->get['filter_date_end'])) {
            $filter_date_end = $this->request->get['filter_date_end'];
        } else {
            $filter_date_end = '';
        }

        if (isset($this->request->get['filter_category'])) {
            $filter_category = $this->request->get['filter_category'];
        } else {
            $filter_category = '';
        }
        
        // Configurar datos para el filtrado
        $data['filter_title'] = $filter_title;
        $data['filter_status'] = $filter_status;
        $data['filter_date_start'] = $filter_date_start;
        $data['filter_date_end'] = $filter_date_end;
        $data['filter_category'] = $filter_category;

        // Estadísticas iniciales
        $data['stats'] = array(
            'total' => $this->model_catalog_blog->getTotalPosts(array()),
            'published' => $this->model_catalog_blog->getTotalPosts(array('filter_status' => 1)),
            'drafts' => $this->model_catalog_blog->getTotalPosts(array('filter_status' => 0)),
            'comments' => $this->model_catalog_blog->getTotalComments(),
            'active_comments' => $this->model_catalog_blog->getTotalComments(array('filter_status' => 1))
        );

        // Errores y mensajes de éxito
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

        // Variables del idioma
        $data['user_token'] = $this->session->data['user_token'];
        $data['heading_title'] = $this->language->get('heading_title');
        
        // Incluir todas las variables de idioma
        $data = array_merge($data, $this->language->all());

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('catalog/blog_list', $data));
    }

    protected function getForm() {
        $data['text_form'] = !isset($this->request->get['post_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['title'])) {
            $data['error_title'] = $this->error['title'];
        } else {
            $data['error_title'] = '';
        }

        if (isset($this->error['content'])) {
            $data['error_content'] = $this->error['content'];
        } else {
            $data['error_content'] = '';
        }

        if (isset($this->error['slug'])) {
            $data['error_slug'] = $this->error['slug'];
        } else {
            $data['error_slug'] = '';
        }

        $url = '';

        if (isset($this->request->get['filter_title'])) {
            $url .= '&filter_title=' . urlencode(html_entity_decode($this->request->get['filter_title'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

        if (isset($this->request->get['filter_date_start'])) {
            $url .= '&filter_date_start=' . $this->request->get['filter_date_start'];
        }

        if (isset($this->request->get['filter_date_end'])) {
            $url .= '&filter_date_end=' . $this->request->get['filter_date_end'];
        }

        if (isset($this->request->get['filter_category'])) {
            $url .= '&filter_category=' . $this->request->get['filter_category'];
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
            'href' => $this->url->link('catalog/blog', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        if (!isset($this->request->get['post_id'])) {
            $data['action'] = $this->url->link('catalog/blog/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        } else {
            $data['action'] = $this->url->link('catalog/blog/edit', 'user_token=' . $this->session->data['user_token'] . '&post_id=' . $this->request->get['post_id'] . $url, true);
        }

        $data['cancel'] = $this->url->link('catalog/blog', 'user_token=' . $this->session->data['user_token'] . $url, true);

        if (isset($this->request->get['post_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $post_info = $this->model_catalog_blog->getPost($this->request->get['post_id']);
        }

        $data['user_token'] = $this->session->data['user_token'];

        if (isset($this->request->post['title'])) {
            $data['title'] = $this->request->post['title'];
        } elseif (!empty($post_info)) {
            $data['title'] = $post_info['title'];
        } else {
            $data['title'] = '';
        }

        if (isset($this->request->post['slug'])) {
            $data['slug'] = $this->request->post['slug'];
        } elseif (!empty($post_info)) {
            $data['slug'] = $post_info['slug'];
        } else {
            $data['slug'] = '';
        }

        if (isset($this->request->post['short_description'])) {
            $data['short_description'] = $this->request->post['short_description'];
        } elseif (!empty($post_info)) {
            $data['short_description'] = $post_info['short_description'];
        } else {
            $data['short_description'] = '';
        }

        if (isset($this->request->post['content'])) {
            $data['content'] = $this->request->post['content'];
        } elseif (!empty($post_info)) {
            $data['content'] = $post_info['content'];
        } else {
            $data['content'] = '';
        }

        if (isset($this->request->post['meta_title'])) {
            $data['meta_title'] = $this->request->post['meta_title'];
        } elseif (!empty($post_info)) {
            $data['meta_title'] = $post_info['meta_title'];
        } else {
            $data['meta_title'] = '';
        }

        if (isset($this->request->post['meta_description'])) {
            $data['meta_description'] = $this->request->post['meta_description'];
        } elseif (!empty($post_info)) {
            $data['meta_description'] = $post_info['meta_description'];
        } else {
            $data['meta_description'] = '';
        }

        if (isset($this->request->post['meta_keywords'])) {
            $data['meta_keywords'] = $this->request->post['meta_keywords'];
        } elseif (!empty($post_info)) {
            $data['meta_keywords'] = $post_info['meta_keywords'];
        } else {
            $data['meta_keywords'] = '';
        }

        if (isset($this->request->post['featured_image'])) {
            $data['featured_image'] = $this->request->post['featured_image'];
        } elseif (!empty($post_info)) {
            $data['featured_image'] = $post_info['featured_image'];
        } else {
            $data['featured_image'] = '';
        }

        if (isset($this->request->post['status'])) {
            $data['status'] = $this->request->post['status'];
        } elseif (!empty($post_info)) {
            $data['status'] = $post_info['status'];
        } else {
            $data['status'] = 0;
        }

        if (isset($this->request->post['comment_status'])) {
            $data['comment_status'] = $this->request->post['comment_status'];
        } elseif (!empty($post_info)) {
            $data['comment_status'] = $post_info['comment_status'];
        } else {
            $data['comment_status'] = 1;
        }

        if (isset($this->request->post['sort_order'])) {
            $data['sort_order'] = $this->request->post['sort_order'];
        } elseif (!empty($post_info)) {
            $data['sort_order'] = $post_info['sort_order'];
        } else {
            $data['sort_order'] = 0;
        }

        if (isset($this->request->post['date_published'])) {
            $data['date_published'] = $this->request->post['date_published'];
        } elseif (!empty($post_info)) {
            $data['date_published'] = date('Y-m-d', strtotime($post_info['date_published']));
        } else {
            $data['date_published'] = date('Y-m-d');
        }

        // Categorías
        $this->load->model('catalog/blog_category');
        $data['categories'] = $this->model_catalog_blog_category->getBlogCategories();

        if (isset($this->request->post['post_category'])) {
            $data['post_category'] = $this->request->post['post_category'];
        } elseif (isset($this->request->get['post_id'])) {
            $data['post_category'] = $this->model_catalog_blog->getPostCategories($this->request->get['post_id']);
        } else {
            $data['post_category'] = array();
        }

        // Etiquetas
        $this->load->model('catalog/blog_tag');
        $data['all_tags'] = $this->model_catalog_blog_tag->getBlogTags();

        if (isset($this->request->post['post_tag'])) {
            $data['post_tag'] = $this->request->post['post_tag'];
        } elseif (isset($this->request->get['post_id'])) {
            $data['post_tag'] = $this->model_catalog_blog->getPostTags($this->request->get['post_id']);
        } else {
            $data['post_tag'] = array();
        }

        // Vista previa de la imagen
        $this->load->model('tool/image');

        if (isset($this->request->post['featured_image']) && is_file(DIR_IMAGE . $this->request->post['featured_image'])) {
            $data['thumb'] = $this->model_tool_image->resize($this->request->post['featured_image'], 100, 100);
        } elseif (!empty($post_info) && is_file(DIR_IMAGE . $post_info['featured_image'])) {
            $data['thumb'] = $this->model_tool_image->resize($post_info['featured_image'], 100, 100);
        } else {
            $data['thumb'] = $this->model_tool_image->resize('no_image.png', 100, 100);
        }

        $data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('catalog/blog_form', $data));
    }

    protected function validateForm() {
        if (!$this->user->hasKey('catalog_blog_edit')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ((utf8_strlen($this->request->post['title']) < 1) || (utf8_strlen($this->request->post['title']) > 255)) {
            $this->error['title'] = $this->language->get('error_title');
        }

        if (utf8_strlen($this->request->post['content']) < 3) {
            $this->error['content'] = $this->language->get('error_content');
        }

        // Validar slug
        if (empty($this->request->post['slug'])) {
            // Si no hay slug, generarlo a partir del título
            $this->request->post['slug'] = $this->generateSlug($this->request->post['title']);
        } else {
            // Si hay slug, asegurarse de que sea único
            $slug = $this->request->post['slug'];
            
            // Comprobar si el slug ya existe
            $post_id = isset($this->request->get['post_id']) ? $this->request->get['post_id'] : 0;
            
            if ($this->model_catalog_blog->isSlugExists($slug, $post_id)) {
                $this->error['slug'] = $this->language->get('error_slug_exists');
            }
        }

        if ($this->error && !isset($this->error['warning'])) {
            $this->error['warning'] = $this->language->get('error_form');
        }

        return !$this->error;
    }

    protected function validateDelete() {
        if (!$this->user->hasKey('catalog_blog_delete')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    protected function validateCopy() {
        if (!$this->user->hasKey('catalog_blog_copy')) {
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

    // Método para manejar la carga de imágenes
    public function uploadImage() {
        $this->load->language('catalog/blog');

        $json = array();

        if (!$this->user->hasKey('catalog_blog_edit')) {
            $json['error'] = $this->language->get('error_permission');
        }

        if (!$json && !empty($this->request->files['file']['name']) && is_file($this->request->files['file']['tmp_name'])) {
            // Validar la imagen
            $filename = basename(html_entity_decode($this->request->files['file']['name'], ENT_QUOTES, 'UTF-8'));

            if ((utf8_strlen($filename) < 3) || (utf8_strlen($filename) > 255)) {
                $json['error'] = $this->language->get('error_filename');
            }

            // Comprobar extensiones válidas
            $allowed = array('jpg', 'jpeg', 'png', 'gif', 'webp');

            if (!in_array(strtolower(substr(strrchr($filename, '.'), 1)), $allowed)) {
                $json['error'] = $this->language->get('error_filetype');
            }

            // Comprobar problemas de seguridad
            $allowed = array('image/jpeg', 'image/pjpeg', 'image/png', 'image/x-png', 'image/gif', 'image/webp');

            if (!in_array($this->request->files['file']['type'], $allowed)) {
                $json['error'] = $this->language->get('error_filetype');
            }

            if ($this->request->files['file']['size'] > 2097152) { // 2MB
                $json['error'] = $this->language->get('error_filesize');
            }

            if (!$json) {
                // Subir la imagen
                $directory = 'catalog/blog/' . date('Y') . '/' . date('m') . '/';

                if (!is_dir(DIR_IMAGE . $directory)) {
                    mkdir(DIR_IMAGE . $directory, 0777, true);
                }

                $file = $directory . token(32) . '.' . substr(strrchr($filename, '.'), 1);

                move_uploaded_file($this->request->files['file']['tmp_name'], DIR_IMAGE . $file);

                $json['success'] = $this->language->get('text_uploaded');
                $json['file'] = $file;

                // Generar miniatura
                $this->load->model('tool/image');
                $json['thumb'] = $this->model_tool_image->resize($file, 100, 100);
            }
        } else {
            $json['error'] = $this->language->get('error_upload');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}