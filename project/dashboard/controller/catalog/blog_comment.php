<?php
class ControllerCatalogBlogComment extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('catalog/blog_comment');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('catalog/blog_comment');

        $this->getList();
    }

    public function add() {
        $this->load->language('catalog/blog_comment');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('catalog/blog_comment');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $data = $this->request->post;
            $data['ip'] = $this->request->server['REMOTE_ADDR'];
            
            $this->model_catalog_blog_comment->addComment($data);

            $this->session->data['success'] = $this->language->get('text_success_add');

            $url = '';

            if (isset($this->request->get['filter_post_id'])) {
                $url .= '&filter_post_id=' . $this->request->get['filter_post_id'];
            }

            if (isset($this->request->get['filter_author'])) {
                $url .= '&filter_author=' . urlencode(html_entity_decode($this->request->get['filter_author'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['filter_status'])) {
                $url .= '&filter_status=' . $this->request->get['filter_status'];
            }

            if (isset($this->request->get['filter_date_added'])) {
                $url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
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

            $this->response->redirect($this->url->link('catalog/blog_comment', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    public function edit() {
        $this->load->language('catalog/blog_comment');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('catalog/blog_comment');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->model_catalog_blog_comment->editComment($this->request->get['comment_id'], $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success_edit');

            $url = '';

            if (isset($this->request->get['filter_post_id'])) {
                $url .= '&filter_post_id=' . $this->request->get['filter_post_id'];
            }

            if (isset($this->request->get['filter_author'])) {
                $url .= '&filter_author=' . urlencode(html_entity_decode($this->request->get['filter_author'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['filter_status'])) {
                $url .= '&filter_status=' . $this->request->get['filter_status'];
            }

            if (isset($this->request->get['filter_date_added'])) {
                $url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
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

            $this->response->redirect($this->url->link('catalog/blog_comment', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    public function delete() {
        $this->load->language('catalog/blog_comment');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('catalog/blog_comment');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $comment_id) {
                $this->model_catalog_blog_comment->deleteComment($comment_id);
            }

            $this->session->data['success'] = $this->language->get('text_success_delete');

            $url = '';

            if (isset($this->request->get['filter_post_id'])) {
                $url .= '&filter_post_id=' . $this->request->get['filter_post_id'];
            }

            if (isset($this->request->get['filter_author'])) {
                $url .= '&filter_author=' . urlencode(html_entity_decode($this->request->get['filter_author'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['filter_status'])) {
                $url .= '&filter_status=' . $this->request->get['filter_status'];
            }

            if (isset($this->request->get['filter_date_added'])) {
                $url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
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

            $this->response->redirect($this->url->link('catalog/blog_comment', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getList();
    }

    public function approve() {
        $this->load->language('catalog/blog_comment');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('catalog/blog_comment');

        if (isset($this->request->get['comment_id']) && $this->validateEdit()) {
            $comment_info = $this->model_catalog_blog_comment->getComment($this->request->get['comment_id']);
            
            if ($comment_info) {
                $comment_info['status'] = 1;
                $this->model_catalog_blog_comment->editComment($this->request->get['comment_id'], $comment_info);
            }

            $this->session->data['success'] = $this->language->get('text_success_approve');

            $url = '';

            if (isset($this->request->get['filter_post_id'])) {
                $url .= '&filter_post_id=' . $this->request->get['filter_post_id'];
            }

            if (isset($this->request->get['filter_author'])) {
                $url .= '&filter_author=' . urlencode(html_entity_decode($this->request->get['filter_author'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['filter_status'])) {
                $url .= '&filter_status=' . $this->request->get['filter_status'];
            }

            if (isset($this->request->get['filter_date_added'])) {
                $url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
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

            $this->response->redirect($this->url->link('catalog/blog_comment', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getList();
    }

    // AJAX endpoint para aprobar/desaprobar comentarios
    public function ajaxApprove() {
        $this->load->language('catalog/blog_comment');
        $json = array();

        if (isset($this->request->post['comment_id']) && $this->validateEdit()) {
            $this->load->model('catalog/blog_comment');
            
            $comment_info = $this->model_catalog_blog_comment->getComment($this->request->post['comment_id']);
            
            if ($comment_info) {
                // Cambiar el estado (aprobar/desaprobar)
                $status = isset($this->request->post['status']) ? (int)$this->request->post['status'] : 1;
                $comment_info['status'] = $status;
                
                $this->model_catalog_blog_comment->editComment($this->request->post['comment_id'], $comment_info);
                
                $json['success'] = $status ? $this->language->get('text_success_approve') : $this->language->get('text_success_disapprove');
                $json['status'] = $status;
            } else {
                $json['error'] = $this->language->get('error_comment');
            }
        } else {
            $json['error'] = $this->language->get('error_permission');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    protected function getList() {
        if (isset($this->request->get['filter_post_id'])) {
            $filter_post_id = $this->request->get['filter_post_id'];
        } else {
            $filter_post_id = '';
        }

        if (isset($this->request->get['filter_author'])) {
            $filter_author = $this->request->get['filter_author'];
        } else {
            $filter_author = '';
        }

        if (isset($this->request->get['filter_status'])) {
            $filter_status = $this->request->get['filter_status'];
        } else {
            $filter_status = '';
        }

        if (isset($this->request->get['filter_date_added'])) {
            $filter_date_added = $this->request->get['filter_date_added'];
        } else {
            $filter_date_added = '';
        }

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'c.date_added';
        }

        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'DESC';
        }

        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }

        $url = '';

        if (isset($this->request->get['filter_post_id'])) {
            $url .= '&filter_post_id=' . $this->request->get['filter_post_id'];
        }

        if (isset($this->request->get['filter_author'])) {
            $url .= '&filter_author=' . urlencode(html_entity_decode($this->request->get['filter_author'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

        if (isset($this->request->get['filter_date_added'])) {
            $url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
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
            'href' => $this->url->link('catalog/blog_comment', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        $data['add'] = $this->url->link('catalog/blog_comment/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['delete'] = $this->url->link('catalog/blog_comment/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);

        $data['comments'] = array();

        $filter_data = array(
            'filter_post_id'    => $filter_post_id,
            'filter_author'     => $filter_author,
            'filter_status'     => $filter_status,
            'filter_date_added' => $filter_date_added,
            'sort'              => $sort,
            'order'             => $order,
            'start'             => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit'             => $this->config->get('config_limit_admin')
        );

        $comment_total = $this->model_catalog_blog_comment->getTotalComments($filter_data);

        $results = $this->model_catalog_blog_comment->getComments($filter_data);

        foreach ($results as $result) {
            // Obtener número de respuestas
            $replies = $this->model_catalog_blog_comment->getCommentReplies($result['comment_id']);
            
            $data['comments'][] = array(
                'comment_id'   => $result['comment_id'],
                'post_title'   => $result['post_title'],
                'author'       => $result['author'],
                'email'        => $result['email'],
                'status'       => $result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
                'status_class' => $result['status'] ? 'success' : 'danger',
                'date_added'   => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                'content'      => nl2br($result['content']),
                'content_short'=> utf8_substr(strip_tags($result['content']), 0, 100) . '...',
                'replies'      => count($replies),
                'view'         => $this->url->link('catalog/blog_comment/view', 'user_token=' . $this->session->data['user_token'] . '&comment_id=' . $result['comment_id'] . $url, true),
                'edit'         => $this->url->link('catalog/blog_comment/edit', 'user_token=' . $this->session->data['user_token'] . '&comment_id=' . $result['comment_id'] . $url, true),
                'approve'      => $this->url->link('catalog/blog_comment/approve', 'user_token=' . $this->session->data['user_token'] . '&comment_id=' . $result['comment_id'] . $url, true),
                'delete'       => $this->url->link('catalog/blog_comment/delete', 'user_token=' . $this->session->data['user_token'] . '&comment_id=' . $result['comment_id'] . $url, true),
                'can_edit'     => $this->user->hasKey('catalog_blog_comment_edit'),
                'can_approve'  => $this->user->hasKey('catalog_blog_comment_edit')
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

        if (isset($this->request->get['filter_post_id'])) {
            $url .= '&filter_post_id=' . $this->request->get['filter_post_id'];
        }

        if (isset($this->request->get['filter_author'])) {
            $url .= '&filter_author=' . urlencode(html_entity_decode($this->request->get['filter_author'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

        if (isset($this->request->get['filter_date_added'])) {
            $url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
        }

        if ($order == 'ASC') {
            $url .= '&order=DESC';
        } else {
            $url .= '&order=ASC';
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['sort_author'] = $this->url->link('catalog/blog_comment', 'user_token=' . $this->session->data['user_token'] . '&sort=c.author' . $url, true);
        $data['sort_post'] = $this->url->link('catalog/blog_comment', 'user_token=' . $this->session->data['user_token'] . '&sort=p.title' . $url, true);
        $data['sort_status'] = $this->url->link('catalog/blog_comment', 'user_token=' . $this->session->data['user_token'] . '&sort=c.status' . $url, true);
        $data['sort_date_added'] = $this->url->link('catalog/blog_comment', 'user_token=' . $this->session->data['user_token'] . '&sort=c.date_added' . $url, true);

        $url = '';

        if (isset($this->request->get['filter_post_id'])) {
            $url .= '&filter_post_id=' . $this->request->get['filter_post_id'];
        }

        if (isset($this->request->get['filter_author'])) {
            $url .= '&filter_author=' . urlencode(html_entity_decode($this->request->get['filter_author'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

        if (isset($this->request->get['filter_date_added'])) {
            $url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
        }

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        $pagination = new Pagination();
        $pagination->total = $comment_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('catalog/blog_comment', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($comment_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($comment_total - $this->config->get('config_limit_admin'))) ? $comment_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $comment_total, ceil($comment_total / $this->config->get('config_limit_admin')));

        $data['filter_post_id'] = $filter_post_id;
        $data['filter_author'] = $filter_author;
        $data['filter_status'] = $filter_status;
        $data['filter_date_added'] = $filter_date_added;

        $data['sort'] = $sort;
        $data['order'] = $order;

        // Load blog posts for filter
        $this->load->model('catalog/blog');
        $data['posts'] = $this->model_catalog_blog->getPosts();

        // Permissions
        $data['can_add'] = $this->user->hasKey('catalog_blog_comment_add');
        $data['can_edit'] = $this->user->hasKey('catalog_blog_comment_edit');
        $data['can_delete'] = $this->user->hasKey('catalog_blog_comment_delete');

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('catalog/blog_comment_list', $data));
    }

    protected function getForm() {
        $data['text_form'] = !isset($this->request->get['comment_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['author'])) {
            $data['error_author'] = $this->error['author'];
        } else {
            $data['error_author'] = '';
        }

        if (isset($this->error['email'])) {
            $data['error_email'] = $this->error['email'];
        } else {
            $data['error_email'] = '';
        }

        if (isset($this->error['content'])) {
            $data['error_content'] = $this->error['content'];
        } else {
            $data['error_content'] = '';
        }

        $url = '';

        if (isset($this->request->get['filter_post_id'])) {
            $url .= '&filter_post_id=' . $this->request->get['filter_post_id'];
        }

        if (isset($this->request->get['filter_author'])) {
            $url .= '&filter_author=' . urlencode(html_entity_decode($this->request->get['filter_author'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

        if (isset($this->request->get['filter_date_added'])) {
            $url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
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
            'href' => $this->url->link('catalog/blog_comment', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        if (!isset($this->request->get['comment_id'])) {
            $data['action'] = $this->url->link('catalog/blog_comment/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        } else {
            $data['action'] = $this->url->link('catalog/blog_comment/edit', 'user_token=' . $this->session->data['user_token'] . '&comment_id=' . $this->request->get['comment_id'] . $url, true);
        }

        $data['cancel'] = $this->url->link('catalog/blog_comment', 'user_token=' . $this->session->data['user_token'] . $url, true);

        if (isset($this->request->get['comment_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $comment_info = $this->model_catalog_blog_comment->getComment($this->request->get['comment_id']);
        }

        // Load blog posts
        $this->load->model('catalog/blog');
        $data['posts'] = $this->model_catalog_blog->getPosts();

        if (isset($this->request->post['post_id'])) {
            $data['post_id'] = $this->request->post['post_id'];
        } elseif (!empty($comment_info)) {
            $data['post_id'] = $comment_info['post_id'];
        } else {
            $data['post_id'] = 0;
        }

        if (isset($this->request->post['parent_id'])) {
            $data['parent_id'] = $this->request->post['parent_id'];
        } elseif (!empty($comment_info)) {
            $data['parent_id'] = $comment_info['parent_id'];
        } else {
            $data['parent_id'] = 0;
        }

        // Get parent comment details
        $data['parent_comment'] = array();
        
        if ($data['parent_id'] > 0) {
            $parent_comment = $this->model_catalog_blog_comment->getComment($data['parent_id']);
            
            if ($parent_comment) {
                $data['parent_comment'] = array(
                    'author'  => $parent_comment['author'],
                    'content' => nl2br($parent_comment['content'])
                );
            }
        }

        if (isset($this->request->post['author'])) {
            $data['author'] = $this->request->post['author'];
        } elseif (!empty($comment_info)) {
            $data['author'] = $comment_info['author'];
        } else {
            $data['author'] = '';
        }

        if (isset($this->request->post['email'])) {
            $data['email'] = $this->request->post['email'];
        } elseif (!empty($comment_info)) {
            $data['email'] = $comment_info['email'];
        } else {
            $data['email'] = '';
        }

        if (isset($this->request->post['website'])) {
            $data['website'] = $this->request->post['website'];
        } elseif (!empty($comment_info)) {
            $data['website'] = $comment_info['website'];
        } else {
            $data['website'] = '';
        }

        if (isset($this->request->post['content'])) {
            $data['content'] = $this->request->post['content'];
        } elseif (!empty($comment_info)) {
            $data['content'] = $comment_info['content'];
        } else {
            $data['content'] = '';
        }

        if (isset($this->request->post['status'])) {
            $data['status'] = $this->request->post['status'];
        } elseif (!empty($comment_info)) {
            $data['status'] = $comment_info['status'];
        } else {
            $data['status'] = 1;
        }

        if (isset($this->request->post['notify'])) {
            $data['notify'] = $this->request->post['notify'];
        } elseif (!empty($comment_info)) {
            $data['notify'] = $comment_info['notify'];
        } else {
            $data['notify'] = 0;
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('catalog/blog_comment_form', $data));
    }

    protected function validateForm() {
        if (!$this->user->hasKey('catalog_blog_comment_edit')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ((utf8_strlen($this->request->post['author']) < 3) || (utf8_strlen($this->request->post['author']) > 64)) {
            $this->error['author'] = $this->language->get('error_author');
        }

        if ((utf8_strlen($this->request->post['email']) < 5) || (utf8_strlen($this->request->post['email']) > 96) || !filter_var($this->request->post['email'], FILTER_VALIDATE_EMAIL)) {
            $this->error['email'] = $this->language->get('error_email');
        }

        if (utf8_strlen($this->request->post['content']) < 5) {
            $this->error['content'] = $this->language->get('error_content');
        }

        return !$this->error;
    }

    protected function validateDelete() {
        if (!$this->user->hasKey('catalog_blog_comment_delete')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    protected function validateEdit() {
        if (!$this->user->hasKey('catalog_blog_comment_edit')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    // Ver detalles de comentario individual (con respuestas)
    public function view() {
        $this->load->language('catalog/blog_comment');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('catalog/blog_comment');
        $this->load->model('catalog/blog');

        if (isset($this->request->get['comment_id'])) {
            $comment_id = $this->request->get['comment_id'];
        } else {
            $comment_id = 0;
        }

        $comment_info = $this->model_catalog_blog_comment->getComment($comment_id);

        if (!$comment_info) {
            $this->response->redirect($this->url->link('catalog/blog_comment', 'user_token=' . $this->session->data['user_token'], true));
        }

        $url = '';

        if (isset($this->request->get['filter_post_id'])) {
            $url .= '&filter_post_id=' . $this->request->get['filter_post_id'];
        }

        if (isset($this->request->get['filter_author'])) {
            $url .= '&filter_author=' . urlencode(html_entity_decode($this->request->get['filter_author'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
        }

        if (isset($this->request->get['filter_date_added'])) {
            $url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
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
            'href' => $this->url->link('catalog/blog_comment', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_view_comment'),
            'href' => $this->url->link('catalog/blog_comment/view', 'user_token=' . $this->session->data['user_token'] . '&comment_id=' . $comment_id . $url, true)
        );

        $data['back'] = $this->url->link('catalog/blog_comment', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['edit'] = $this->url->link('catalog/blog_comment/edit', 'user_token=' . $this->session->data['user_token'] . '&comment_id=' . $comment_id . $url, true);
        $data['reply'] = $this->url->link('catalog/blog_comment/add', 'user_token=' . $this->session->data['user_token'] . '&parent_id=' . $comment_id . $url, true);

        // Post information
        $post_info = $this->model_catalog_blog->getPost($comment_info['post_id']);
        
        if ($post_info) {
            $data['post_title'] = $post_info['title'];
            $data['post_link'] = $this->url->link('catalog/blog/edit', 'user_token=' . $this->session->data['user_token'] . '&post_id=' . $post_info['post_id'], true);
        } else {
            $data['post_title'] = $this->language->get('text_unknown');
            $data['post_link'] = '';
        }

        // Comment details
        $data['comment'] = array(
            'comment_id'  => $comment_info['comment_id'],
            'author'      => $comment_info['author'],
            'email'       => $comment_info['email'],
            'website'     => $comment_info['website'],
            'content'     => nl2br($comment_info['content']),
            'status'      => $comment_info['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
            'status_class'=> $comment_info['status'] ? 'success' : 'danger',
            'status_value'=> $comment_info['status'],
            'ip'          => $comment_info['ip'],
            'date_added'  => date($this->language->get('datetime_format'), strtotime($comment_info['date_added'])),
            'can_edit'    => $this->user->hasKey('catalog_blog_comment_edit'),
            'can_reply'   => $this->user->hasKey('catalog_blog_comment_add')
        );

        // Get parent comment if exists
        if ($comment_info['parent_id']) {
            $parent_info = $this->model_catalog_blog_comment->getComment($comment_info['parent_id']);
            
            if ($parent_info) {
                $data['parent_comment'] = array(
                    'comment_id' => $parent_info['comment_id'],
                    'author'     => $parent_info['author'],
                    'content'    => nl2br($parent_info['content']),
                    'date_added' => date($this->language->get('date_format_short'), strtotime($parent_info['date_added'])),
                    'link'       => $this->url->link('catalog/blog_comment/view', 'user_token=' . $this->session->data['user_token'] . '&comment_id=' . $parent_info['comment_id'], true)
                );
            } else {
                $data['parent_comment'] = array();
            }
        } else {
            $data['parent_comment'] = array();
        }

        // Get replies
        $data['replies'] = array();
        
        $replies = $this->model_catalog_blog_comment->getCommentReplies($comment_id);
        
        foreach ($replies as $reply) {
            $data['replies'][] = array(
                'comment_id'  => $reply['comment_id'],
                'author'      => $reply['author'],
                'content'     => nl2br($reply['content']),
                'status'      => $reply['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
                'status_class'=> $reply['status'] ? 'success' : 'danger',
                'date_added'  => date($this->language->get('date_format_short'), strtotime($reply['date_added'])),
                'link'        => $this->url->link('catalog/blog_comment/view', 'user_token=' . $this->session->data['user_token'] . '&comment_id=' . $reply['comment_id'], true),
                'edit'        => $this->url->link('catalog/blog_comment/edit', 'user_token=' . $this->session->data['user_token'] . '&comment_id=' . $reply['comment_id'], true)
            );
        }

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('catalog/blog_comment_view', $data));
    }
}