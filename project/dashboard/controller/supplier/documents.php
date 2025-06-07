<?php
/**
 * AYM ERP - Supplier Documents Controller
 *
 * @author AYM ERP Development Team
 * @copyright 2024 AYM ERP
 * @license Commercial License
 * @version 1.0.0
 * @link https://aym-erp.com
 */

class ControllerSupplierDocuments extends Controller {

    private $error = array();

    public function index() {
        $this->load->language('supplier/documents');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('supplier/documents', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['add'] = $this->url->link('supplier/documents/add', 'user_token=' . $this->session->data['user_token'], true);
        $data['delete'] = $this->url->link('supplier/documents/delete', 'user_token=' . $this->session->data['user_token'], true);

        $this->getList($data);
    }

    public function add() {
        $this->load->language('supplier/documents');

        $this->document->setTitle($this->language->get('heading_title'));

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->load->model('supplier/documents');

            $document_id = $this->model_supplier_documents->addDocument($this->request->post);

            // Handle file upload
            if (isset($this->request->files['document_file']) && $this->request->files['document_file']['error'] == 0) {
                $this->uploadDocument($document_id, $this->request->files['document_file']);
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

            $this->response->redirect($this->url->link('supplier/documents', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    public function edit() {
        $this->load->language('supplier/documents');

        $this->document->setTitle($this->language->get('heading_title'));

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            $this->load->model('supplier/documents');

            $this->model_supplier_documents->editDocument($this->request->get['document_id'], $this->request->post);

            // Handle file upload
            if (isset($this->request->files['document_file']) && $this->request->files['document_file']['error'] == 0) {
                $this->uploadDocument($this->request->get['document_id'], $this->request->files['document_file']);
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

            $this->response->redirect($this->url->link('supplier/documents', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    public function delete() {
        $this->load->language('supplier/documents');

        $this->document->setTitle($this->language->get('heading_title'));

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            $this->load->model('supplier/documents');

            foreach ($this->request->post['selected'] as $document_id) {
                $this->model_supplier_documents->deleteDocument($document_id);
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

            $this->response->redirect($this->url->link('supplier/documents', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getList();
    }

    public function download() {
        $this->load->language('supplier/documents');
        $this->load->model('supplier/documents');

        if (isset($this->request->get['document_id'])) {
            $document_id = $this->request->get['document_id'];
            $document_info = $this->model_supplier_documents->getDocument($document_id);

            if ($document_info && $document_info['file_path'] && file_exists(DIR_UPLOAD . $document_info['file_path'])) {
                $file = DIR_UPLOAD . $document_info['file_path'];

                if (file_exists($file)) {
                    header('Content-Description: File Transfer');
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: attachment; filename="' . basename($document_info['original_name']) . '"');
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate');
                    header('Pragma: public');
                    header('Content-Length: ' . filesize($file));

                    readfile($file);

                    // Log download
                    $this->model_supplier_documents->logDownload($document_id, $this->user->getId());

                    exit;
                }
            }
        }

        $this->response->redirect($this->url->link('supplier/documents', 'user_token=' . $this->session->data['user_token'], true));
    }

    public function view() {
        $this->load->language('supplier/documents');
        $this->load->model('supplier/documents');

        if (isset($this->request->get['document_id'])) {
            $document_id = $this->request->get['document_id'];
            $document_info = $this->model_supplier_documents->getDocument($document_id);

            if ($document_info) {
                $this->document->setTitle($this->language->get('heading_title') . ' - ' . $document_info['title']);

                $data['breadcrumbs'] = array();

                $data['breadcrumbs'][] = array(
                    'text' => $this->language->get('text_home'),
                    'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
                );

                $data['breadcrumbs'][] = array(
                    'text' => $this->language->get('heading_title'),
                    'href' => $this->url->link('supplier/documents', 'user_token=' . $this->session->data['user_token'], true)
                );

                $data['breadcrumbs'][] = array(
                    'text' => $document_info['title'],
                    'href' => $this->url->link('supplier/documents/view', 'user_token=' . $this->session->data['user_token'] . '&document_id=' . $document_id, true)
                );

                $data['document'] = $document_info;
                $data['document_versions'] = $this->model_supplier_documents->getDocumentVersions($document_id);
                $data['document_history'] = $this->model_supplier_documents->getDocumentHistory($document_id);

                $data['download'] = $this->url->link('supplier/documents/download', 'user_token=' . $this->session->data['user_token'] . '&document_id=' . $document_id, true);
                $data['edit'] = $this->url->link('supplier/documents/edit', 'user_token=' . $this->session->data['user_token'] . '&document_id=' . $document_id, true);
                $data['back'] = $this->url->link('supplier/documents', 'user_token=' . $this->session->data['user_token'], true);

                $data['user_token'] = $this->session->data['user_token'];

                $data['header'] = $this->load->controller('common/header');
                $data['column_left'] = $this->load->controller('common/column_left');
                $data['footer'] = $this->load->controller('common/footer');

                $this->response->setOutput($this->load->view('supplier/documents_view', $data));
            } else {
                $this->response->redirect($this->url->link('supplier/documents', 'user_token=' . $this->session->data['user_token'], true));
            }
        } else {
            $this->response->redirect($this->url->link('supplier/documents', 'user_token=' . $this->session->data['user_token'], true));
        }
    }

    public function archive() {
        $this->load->language('supplier/documents');
        $this->load->model('supplier/documents');

        if (isset($this->request->get['document_id'])) {
            $document_id = $this->request->get['document_id'];

            if ($this->validateArchive()) {
                $this->model_supplier_documents->archiveDocument($document_id);

                $this->session->data['success'] = $this->language->get('text_success_archive');
            }
        }

        $this->response->redirect($this->url->link('supplier/documents', 'user_token=' . $this->session->data['user_token'], true));
    }

    protected function getList(&$data = array()) {
        if (isset($this->request->get['filter_title'])) {
            $filter_title = $this->request->get['filter_title'];
        } else {
            $filter_title = '';
        }

        if (isset($this->request->get['filter_supplier_id'])) {
            $filter_supplier_id = $this->request->get['filter_supplier_id'];
        } else {
            $filter_supplier_id = '';
        }

        if (isset($this->request->get['filter_document_type'])) {
            $filter_document_type = $this->request->get['filter_document_type'];
        } else {
            $filter_document_type = '';
        }

        if (isset($this->request->get['filter_status'])) {
            $filter_status = $this->request->get['filter_status'];
        } else {
            $filter_status = '';
        }

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'sd.title';
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

        if (isset($this->request->get['filter_title'])) {
            $url .= '&filter_title=' . urlencode(html_entity_decode($this->request->get['filter_title'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_supplier_id'])) {
            $url .= '&filter_supplier_id=' . $this->request->get['filter_supplier_id'];
        }

        if (isset($this->request->get['filter_document_type'])) {
            $url .= '&filter_document_type=' . $this->request->get['filter_document_type'];
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

        $data['documents'] = array();

        $filter_data = array(
            'filter_title'         => $filter_title,
            'filter_supplier_id'   => $filter_supplier_id,
            'filter_document_type' => $filter_document_type,
            'filter_status'        => $filter_status,
            'sort'                 => $sort,
            'order'                => $order,
            'start'                => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit'                => $this->config->get('config_limit_admin')
        );

        $this->load->model('supplier/documents');

        $document_total = $this->model_supplier_documents->getTotalDocuments($filter_data);

        $results = $this->model_supplier_documents->getDocuments($filter_data);

        foreach ($results as $result) {
            $data['documents'][] = array(
                'document_id'     => $result['document_id'],
                'title'           => $result['title'],
                'supplier_name'   => $result['supplier_name'],
                'document_type'   => $result['document_type'],
                'file_size'       => $this->formatFileSize($result['file_size']),
                'expiry_date'     => $result['expiry_date'] ? date($this->language->get('date_format_short'), strtotime($result['expiry_date'])) : '',
                'status'          => $result['status'] ? $this->language->get('text_active') : $this->language->get('text_archived'),
                'date_added'      => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                'view'            => $this->url->link('supplier/documents/view', 'user_token=' . $this->session->data['user_token'] . '&document_id=' . $result['document_id'] . $url, true),
                'edit'            => $this->url->link('supplier/documents/edit', 'user_token=' . $this->session->data['user_token'] . '&document_id=' . $result['document_id'] . $url, true),
                'download'        => $this->url->link('supplier/documents/download', 'user_token=' . $this->session->data['user_token'] . '&document_id=' . $result['document_id'], true)
            );
        }

        $data['user_token'] = $this->session->data['user_token'];

        // Load suppliers for filter
        $this->load->model('supplier/supplier');
        $data['suppliers'] = $this->model_supplier_supplier->getSuppliers();

        // Document types
        $data['document_types'] = $this->model_supplier_documents->getDocumentTypes();

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

        if (isset($this->request->get['filter_title'])) {
            $url .= '&filter_title=' . urlencode(html_entity_decode($this->request->get['filter_title'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_supplier_id'])) {
            $url .= '&filter_supplier_id=' . $this->request->get['filter_supplier_id'];
        }

        if (isset($this->request->get['filter_document_type'])) {
            $url .= '&filter_document_type=' . $this->request->get['filter_document_type'];
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

        $data['sort_title'] = $this->url->link('supplier/documents', 'user_token=' . $this->session->data['user_token'] . '&sort=sd.title' . $url, true);
        $data['sort_supplier'] = $this->url->link('supplier/documents', 'user_token=' . $this->session->data['user_token'] . '&sort=s.name' . $url, true);
        $data['sort_type'] = $this->url->link('supplier/documents', 'user_token=' . $this->session->data['user_token'] . '&sort=sd.document_type' . $url, true);
        $data['sort_date_added'] = $this->url->link('supplier/documents', 'user_token=' . $this->session->data['user_token'] . '&sort=sd.date_added' . $url, true);
        $data['sort_expiry_date'] = $this->url->link('supplier/documents', 'user_token=' . $this->session->data['user_token'] . '&sort=sd.expiry_date' . $url, true);

        $url = '';

        if (isset($this->request->get['filter_title'])) {
            $url .= '&filter_title=' . urlencode(html_entity_decode($this->request->get['filter_title'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_supplier_id'])) {
            $url .= '&filter_supplier_id=' . $this->request->get['filter_supplier_id'];
        }

        if (isset($this->request->get['filter_document_type'])) {
            $url .= '&filter_document_type=' . $this->request->get['filter_document_type'];
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
        $pagination->total = $document_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('supplier/documents', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($document_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($document_total - $this->config->get('config_limit_admin'))) ? $document_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $document_total, ceil($document_total / $this->config->get('config_limit_admin')));

        $data['filter_title'] = $filter_title;
        $data['filter_supplier_id'] = $filter_supplier_id;
        $data['filter_document_type'] = $filter_document_type;
        $data['filter_status'] = $filter_status;

        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('supplier/documents_list', $data));
    }

    protected function getForm() {
        $data['text_form'] = !isset($this->request->get['document_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

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

        if (isset($this->error['supplier'])) {
            $data['error_supplier'] = $this->error['supplier'];
        } else {
            $data['error_supplier'] = '';
        }

        if (isset($this->error['document_type'])) {
            $data['error_document_type'] = $this->error['document_type'];
        } else {
            $data['error_document_type'] = '';
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
            'href' => $this->url->link('supplier/documents', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        if (!isset($this->request->get['document_id'])) {
            $data['action'] = $this->url->link('supplier/documents/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        } else {
            $data['action'] = $this->url->link('supplier/documents/edit', 'user_token=' . $this->session->data['user_token'] . '&document_id=' . $this->request->get['document_id'] . $url, true);
        }

        $data['cancel'] = $this->url->link('supplier/documents', 'user_token=' . $this->session->data['user_token'] . $url, true);

        if (isset($this->request->get['document_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $document_info = $this->model_supplier_documents->getDocument($this->request->get['document_id']);
        }

        $data['user_token'] = $this->session->data['user_token'];

        if (isset($this->request->post['title'])) {
            $data['title'] = $this->request->post['title'];
        } elseif (!empty($document_info)) {
            $data['title'] = $document_info['title'];
        } else {
            $data['title'] = '';
        }

        if (isset($this->request->post['supplier_id'])) {
            $data['supplier_id'] = $this->request->post['supplier_id'];
        } elseif (!empty($document_info)) {
            $data['supplier_id'] = $document_info['supplier_id'];
        } else {
            $data['supplier_id'] = '';
        }

        if (isset($this->request->post['document_type'])) {
            $data['document_type'] = $this->request->post['document_type'];
        } elseif (!empty($document_info)) {
            $data['document_type'] = $document_info['document_type'];
        } else {
            $data['document_type'] = '';
        }

        if (isset($this->request->post['description'])) {
            $data['description'] = $this->request->post['description'];
        } elseif (!empty($document_info)) {
            $data['description'] = $document_info['description'];
        } else {
            $data['description'] = '';
        }

        if (isset($this->request->post['expiry_date'])) {
            $data['expiry_date'] = $this->request->post['expiry_date'];
        } elseif (!empty($document_info)) {
            $data['expiry_date'] = $document_info['expiry_date'];
        } else {
            $data['expiry_date'] = '';
        }

        if (isset($this->request->post['tags'])) {
            $data['tags'] = $this->request->post['tags'];
        } elseif (!empty($document_info)) {
            $data['tags'] = $document_info['tags'];
        } else {
            $data['tags'] = '';
        }

        if (isset($this->request->post['status'])) {
            $data['status'] = $this->request->post['status'];
        } elseif (!empty($document_info)) {
            $data['status'] = $document_info['status'];
        } else {
            $data['status'] = 1;
        }

        // Load suppliers
        $this->load->model('supplier/supplier');
        $data['suppliers'] = $this->model_supplier_supplier->getSuppliers();

        // Document types
        $data['document_types'] = $this->model_supplier_documents->getDocumentTypes();

        // Current file info if editing
        if (!empty($document_info) && $document_info['file_path']) {
            $data['current_file'] = array(
                'name' => $document_info['original_name'],
                'size' => $this->formatFileSize($document_info['file_size']),
                'download' => $this->url->link('supplier/documents/download', 'user_token=' . $this->session->data['user_token'] . '&document_id=' . $this->request->get['document_id'], true)
            );
        } else {
            $data['current_file'] = false;
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('supplier/documents_form', $data));
    }

    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'supplier/documents')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ((utf8_strlen($this->request->post['title']) < 3) || (utf8_strlen($this->request->post['title']) > 128)) {
            $this->error['title'] = $this->language->get('error_title');
        }

        if (empty($this->request->post['supplier_id'])) {
            $this->error['supplier'] = $this->language->get('error_supplier');
        }

        if (empty($this->request->post['document_type'])) {
            $this->error['document_type'] = $this->language->get('error_document_type');
        }

        return !$this->error;
    }

    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'supplier/documents')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    protected function validateArchive() {
        if (!$this->user->hasPermission('modify', 'supplier/documents')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    protected function uploadDocument($document_id, $file) {
        $this->load->model('supplier/documents');

        $allowed_extensions = array('pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif', 'txt', 'zip', 'rar');
        $max_file_size = 10 * 1024 * 1024; // 10MB

        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($file_extension, $allowed_extensions)) {
            $this->error['file'] = $this->language->get('error_file_type');
            return false;
        }

        if ($file['size'] > $max_file_size) {
            $this->error['file'] = $this->language->get('error_file_size');
            return false;
        }

        $filename = 'supplier_doc_' . $document_id . '_' . time() . '.' . $file_extension;
        $filepath = 'supplier/documents/' . $filename;

        if (!is_dir(DIR_UPLOAD . 'supplier/documents/')) {
            mkdir(DIR_UPLOAD . 'supplier/documents/', 0755, true);
        }

        if (move_uploaded_file($file['tmp_name'], DIR_UPLOAD . $filepath)) {
            $this->model_supplier_documents->updateDocumentFile($document_id, array(
                'file_path' => $filepath,
                'original_name' => $file['name'],
                'file_size' => $file['size'],
                'mime_type' => $file['type']
            ));

            return true;
        }

        return false;
    }

    protected function formatFileSize($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
}