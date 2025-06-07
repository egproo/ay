<?php
class ControllerPurchaseRequisition extends Controller {
    private $error = array();

    public function __construct($registry) {
        parent::__construct($registry);
        $this->load->model('purchase/requisition');
        $this->load->language('purchase/requisition');
    }

    public function index() {
        $this->document->setTitle($this->language->get('heading_title'));

        $default_filter_data = array();
        $data['stats'] = $this->model_purchase_requisition->getRequisitionStats($default_filter_data);

        $data['heading_title']             = $this->language->get('heading_title');
        $data['text_requisition_list']     = $this->language->get('text_requisition_list');
        $data['text_total_requisitions']   = $this->language->get('text_total_requisitions');
        $data['text_pending_requisitions'] = $this->language->get('text_pending_requisitions');
        $data['text_approved_requisitions']= $this->language->get('text_approved_requisitions');
        $data['text_rejected_requisitions']= $this->language->get('text_rejected_requisitions');
        $data['text_req_number']           = $this->language->get('text_req_number');
        $data['text_filter_status']        = $this->language->get('text_filter_status');
        $data['text_filter_date_start']    = $this->language->get('text_filter_date_start');
        $data['text_filter_date_end']      = $this->language->get('text_filter_date_end');
        $data['text_all_statuses']         = $this->language->get('text_all_statuses');
        $data['text_select_product']       = $this->language->get('text_select_product');
        $data['text_add_requisition']      = $this->language->get('text_add_requisition');
        $data['button_add_requisition']    = $this->language->get('button_add_requisition');
        $data['button_view_quotations']    = $this->language->get('button_view_quotations');
        $data['text_close']                = $this->language->get('text_close');
        $data['button_save']               = $this->language->get('button_save');
        $data['text_add_item']             = $this->language->get('text_add_item');
        $data['column_product']            = $this->language->get('column_product');
        $data['column_quantity']           = $this->language->get('column_quantity');
        $data['column_unit']               = $this->language->get('column_unit');
        $data['column_description']        = $this->language->get('column_description');
        $data['column_requisition_id']     = $this->language->get('column_requisition_id');
        $data['column_req_number']         = $this->language->get('column_req_number');
        $data['column_branch']             = $this->language->get('column_branch');
        $data['column_user_groups']        = $this->language->get('column_user_groups');
        $data['column_status']             = $this->language->get('column_status');
        $data['column_date_added']         = $this->language->get('column_date_added');
        $data['column_action']             = $this->language->get('column_action');
       	$data['column_unit_price']           = $this->language->get('column_unit_price');
	    $data['column_total']                 = $this->language->get('column_total');
        $data['text_confirm_delete']       = $this->language->get('text_confirm_delete');
        $data['text_confirm_approve']      = $this->language->get('text_confirm_approve');
        $data['text_prompt_reject_reason'] = $this->language->get('text_prompt_reject_reason');

        $data['can_view']    = $this->user->hasKey('purchase_requisition_view');
        $data['can_add']     = $this->user->hasKey('purchase_requisition_add');
        $data['can_edit']    = $this->user->hasKey('purchase_requisition_edit');
        $data['can_delete']  = $this->user->hasKey('purchase_requisition_delete');
        $data['can_approve'] = $this->user->hasKey('purchase_requisition_approve');
        $data['can_reject']  = $this->user->hasKey('purchase_requisition_reject');
        $data['can_manage_quotations'] = $this->user->hasKey('purchase_requisition_manage_quotations');
         $data['can_add_quotation']     = $this->user->hasKey('purchase_requisition_add_quotation');
         $data['text_select_action']   = $this->language->get('text_select_action');
         $data['text_approve_selected']   = $this->language->get('text_approve_selected');
          $data['text_reject_selected'] = $this->language->get('text_reject_selected');
         $data['text_delete_selected']   = $this->language->get('text_delete_selected');
        $data['button_execute']   = $this->language->get('button_execute');
         $data['text_refresh_list'] = $this->language->get('text_refresh_list');
        $data['status_options'] = array(
            array('value' => 'draft',     'text' => $this->language->get('text_status_draft')),
            array('value' => 'pending',   'text' => $this->language->get('text_status_pending')),
            array('value' => 'approved',  'text' => $this->language->get('text_status_approved')),
            array('value' => 'rejected',  'text' => $this->language->get('text_status_rejected')),
            array('value' => 'cancelled', 'text' => $this->language->get('text_status_cancelled'))
        );

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

        $data['branches']    = $this->model_purchase_requisition->getBranches();
        $data['user_groups'] = $this->model_purchase_requisition->getUserGroups();

        $data['user_token']  = $this->session->data['user_token'];
        $data['home']        = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true);

        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('purchase/requisition_list', $data));
    }

    public function ajaxGetProductDetails() {
      $json = array();
      $product_id = (int)($this->request->get['product_id'] ?? 0);
      $branch_id = (int)($this->request->get['branch_id'] ?? 0); // Get branch_id from request

      if ($product_id && $branch_id) { // Ensure both IDs are present
        $details = $this->model_purchase_requisition->getProductDetails($product_id, $branch_id);
        if ($details && isset($details['units'])) {
            $json['units'] = $details['units']; // units now contain stock and cost info
          }

      } else {
          $json['error'] = 'Missing product_id or branch_id.';
      }
      $this->sendJSON($json);
    }

    /**
     * AJAX: جلب الطلبات المعلقة لمنتج معين
     */
    public function ajaxGetPendingRequisitions() {
        $json = array();
        $product_id = (int)($this->request->get['product_id'] ?? 0);
        $exclude_requisition_id = (int)($this->request->get['exclude_requisition_id'] ?? 0); // Optional: exclude current req

        if ($product_id) {
            $pending_reqs = $this->model_purchase_requisition->getPendingRequisitionsForProduct($product_id, $exclude_requisition_id);
            $json['pending_requisitions'] = $pending_reqs;
        } else {
            $json['error'] = 'Missing product_id.';
        }
        $this->sendJSON($json);
    }

    private function getPagination($total, $page, $limit) {
        $pagination = new Pagination();
        $pagination->total = $total;
        $pagination->page = $page;
        $pagination->limit = $limit;
        $pagination->url = 'javascript:void(0);';
        return $pagination->render();
    }

    /**
     * AJAX: جلب قائمة الطلبات
     */
      public function ajaxList() {
          $this->load->model('purchase/requisition');
          $json = array();

          $filter_req_number = isset($this->request->get['filter_req_number']) ? $this->request->get['filter_req_number'] : '';
          $filter_status = isset($this->request->get['filter_status']) ? $this->request->get['filter_status'] : '';
          $filter_date_start = isset($this->request->get['filter_date_start']) ? $this->request->get['filter_date_start'] : '';
          $filter_date_end = isset($this->request->get['filter_date_end']) ? $this->request->get['filter_date_end'] : '';
          $page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
          $limit = isset($this->request->get['limit']) ? (int)$this->request->get['limit'] : 20;

          $data = array(
            'filter_req_number' => $filter_req_number,
            'filter_status'     => $filter_status,
            'filter_date_start' => $filter_date_start,
            'filter_date_end'   => $filter_date_end,
            'start'             => ($page - 1) * $limit,
            'limit'             => $limit
          );

          $requisitions = $this->model_purchase_requisition->getRequisitions($data);
          $total = $this->model_purchase_requisition->getTotalRequisitions($data);

          $json['stats'] = $this->model_purchase_requisition->getRequisitionStats($data);

          $json['requisitions'] = array();
          foreach ($requisitions as $row) {
              $json['requisitions'][] = array(
                  'requisition_id'   => $row['requisition_id'],
                  'req_number'       => $row['req_number'] ?? '',
                  'branch_name'      => $row['branch_name'] ?? '',
                  'user_group_name'  => $row['user_group_name'] ?? '',
                  'status'           => $row['status'],
                  'date_added'       => $row['created_at'],

                  'can_manage_quotations' => $this->user->hasKey('purchase_requisition_manage_quotations'),
                  'can_add_quotation'     => $this->user->hasKey('purchase_requisition_add_quotation'),

                  'can_edit'    => $this->user->hasKey('purchase_requisition_edit'),
                  'can_approve' => ($this->user->hasKey('purchase_requisition_approve') && $row['status'] == 'pending'),
                  'can_reject'  => ($this->user->hasKey('purchase_requisition_reject')  && $row['status'] == 'pending'),
                  'can_delete'  => ($this->user->hasKey('purchase_requisition_delete') && in_array($row['status'], ['draft','pending']))
              );
          }

          // إعداد الـ Pagination
          $pagination = new Pagination();
          $pagination->total = $total;
          $pagination->page = $page;
          $pagination->limit = $limit;
          $pagination->url = 'javascript:void(0);';
          $json['pagination'] = $pagination->render();
          $json['total'] = sprintf($this->language->get('text_pagination'), ($total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($total - $limit)) ? $total : ((($page - 1) * $limit) + $limit), $total, ceil($total / $limit));

          $this->sendJSON($json);
        }


    /**
     * AJAX: إضافة طلب شراء جديد
      */
   public function ajaxAddRequisition() {
       $json = array();
        if (!$this->user->hasKey('purchase_requisition_add')) {
            $json['error'] = $this->language->get('error_permission');
           return $this->sendJSON($json);
        }

        if ($this->request->server['REQUEST_METHOD'] !== 'POST') {
            $json['error'] = "Invalid request method.";
           return $this->sendJSON($json);
        }

       $data = array(
            'branch_id'      => (int)($this->request->post['branch_id']     ?? 0),
            'user_group_id'  => (int)($this->request->post['user_group_id'] ?? 0),
           'required_date'  =>       ($this->request->post['required_date'] ?? ''),
            'priority'       =>       ($this->request->post['priority']      ?? 'low'),
           'notes'          =>       ($this->request->post['notes']         ?? ''),
           'items'          => array(),
            'created_by'    =>  $this->user->getId()
       );

       if (!empty($this->request->post['item_product_id'])) {
            foreach ($this->request->post['item_product_id'] as $i => $prod_id) {
                $qty  = (float)($this->request->post['item_quantity'][$i]   ?? 1);
                $uId  = (int)($this->request->post['item_unit_id'][$i]      ?? 0);
               $desc =       ($this->request->post['item_description'][$i] ?? '');
                 if ($prod_id && $qty > 0) {
                     $data['items'][] = array(
                        'product_id'  => (int)$prod_id,
                         'quantity'    => $qty,
                         'unit_id'     => $uId,
                         'description' => $desc
                   );
              }
          }
        }
      if (empty($data['branch_id'])) {
            $json['error'] = "Branch is required.";
           return $this->sendJSON($json);
       }
       if (empty($data['user_group_id'])) {
            $json['error'] = "User group is required.";
            return $json;
       }
       if (empty($data['items'])) {
            $json['error'] = "Cannot save requisition with no items.";
           return $this->sendJSON($json);
       }

        $res = $this->model_purchase_requisition->addRequisition($data);
        if (!empty($res['error'])) {
            $json['error'] = $res['error'];
        } else {
           $json['success'] = $this->language->get('text_success_add_requisition');
      }
        return $this->sendJSON($json);
   }

        /**
     * إضافة سجل في تاريخ طلب الشراء
     */
    public function addRequisitionHistory($data) {
      return $this->db->query("INSERT INTO `" . DB_PREFIX . "cod_purchase_requisition_history` SET
            requisition_id = '" . (int)$data['requisition_id'] . "',
            user_id = '" . (int)$data['user_id'] . "',
            action = '" . $this->db->escape($data['action']) . "',
            description = '" . $this->db->escape($data['description']) . "',
             created_at = NOW()
        ");
    }

    /**
     * AJAX: تعديل طلب شراء
      */
     public function ajaxEditRequisition() {
         $json = array();
        if (!$this->user->hasKey('purchase_requisition_edit')) {
            $json['error'] = $this->language->get('error_permission');
             return $this->sendJSON($json);
        }

         if ($this->request->server['REQUEST_METHOD'] !== 'POST') {
            $json['error'] = "Invalid request method.";
           return $this->sendJSON($json);
        }
       $data = array(
            'requisition_id' => (int)($this->request->post['requisition_id'] ?? 0),
           'branch_id'      => (int)($this->request->post['branch_id']      ?? 0),
             'user_group_id'  => (int)($this->request->post['user_group_id']  ?? 0),
            'required_date'  =>       ($this->request->post['required_date'] ?? ''),
             'priority'       =>       ($this->request->post['priority']      ?? 'low'),
           'notes'          =>       ($this->request->post['notes']         ?? ''),
            'items'          => array(),
             'updated_by'    => $this->user->getId()
        );

    if (!empty($this->request->post['item_product_id'])) {
            foreach ($this->request->post['item_product_id'] as $i => $pId) {
                $qty  = (float)($this->request->post['item_quantity'][$i]   ?? 1);
                $uId  = (int)($this->request->post['item_unit_id'][$i]      ?? 0);
               $desc =       ($this->request->post['item_description'][$i] ?? '');

             $data['items'][] = array(
                 'product_id'  => (int)$pId,
                    'quantity'    => $qty,
                   'unit_id'     => $uId,
                  'description' => $desc
               );
            }
       }

         $res = $this->model_purchase_requisition->editRequisition($data);
        if (!empty($res['error'])) {
            $json['error'] = $res['error'];
       } else {
           $json['success'] = $this->language->get('text_success_edit_requisition');
        }
        return $this->sendJSON($json);
  }

   /**
     * AJAX: اعتماد طلب شراء
      */
    public function ajaxApprove() {
        $json = array();
        if (!$this->user->hasPermission('modify', 'purchase/requisition')) {
            $json['error'] = $this->language->get('error_permission');
            return $this->sendJSON($json);
        }

        $requisition_id = (int)($this->request->get['requisition_id'] ?? 0);

        if ($requisition_id) {
            $res = $this->model_purchase_requisition->approveRequisition($requisition_id, $this->user->getId());
            if (!empty($res['error'])) {
                $json['error'] = $res['error'];
            } else {
                $json['success'] = $this->language->get('text_success_approve');
            }
        } else {
            $json['error'] = 'Missing requisition_id';
        }
        return $this->sendJSON($json);
    }

  /**
    * AJAX: رفض طلب شراء
      */
    public function ajaxReject() {
        $json = array();
        if (!$this->user->hasPermission('modify', 'purchase/requisition')) {
            $json['error'] = $this->language->get('error_permission');
            return $this->sendJSON($json);
        }

        $requisition_id = (int)($this->request->post['requisition_id'] ?? 0);
        $reason = ($this->request->post['reason'] ?? '');

        if ($requisition_id) {
            $res = $this->model_purchase_requisition->rejectRequisition($requisition_id, $this->user->getId(), $reason);
            if (!empty($res['error'])) {
                $json['error'] = $res['error'];
            } else {
                $json['success'] = $this->language->get('text_success_reject');
            }
        } else {
            $json['error'] = 'Missing requisition_id';
        }
        return $this->sendJSON($json);
    }

    /**
     * AJAX: حذف طلب شراء
      */
    public function ajaxDelete() {
        $json = array();
        if (!$this->user->hasPermission('modify', 'purchase/requisition')) {
            $json['error'] = $this->language->get('error_permission');
            return $this->sendJSON($json);
        }

        $requisition_id = (int)($this->request->get['requisition_id'] ?? 0);

        if ($requisition_id) {
            $res = $this->model_purchase_requisition->deleteRequisition($requisition_id);
            if (!empty($res['error'])) {
                $json['error'] = $res['error'];
            } else {
                $json['success'] = $this->language->get('text_success_delete_requisition');
            }
        } else {
            $json['error'] = 'Missing requisition_id';
        }
        return $this->sendJSON($json);
    }

     /**
      * جلب عروض الأسعار لطلب معين (AJAX)
       */
   public function ajaxGetQuotations() {
         if (!$this->user->hasKey('purchase_requisition_manage_quotations')) {
             $this->response->setOutput('<div class="alert alert-danger">' .
                $this->language->get('error_permission') . '</div>');
             return;
        }
         $requisition_id = (int)($this->request->get['requisition_id'] ?? 0);
         // Load the quotation model
         $this->load->model('purchase/quotation');
         // Prepare filter data for getQuotations
         $filter_data = array(
             'filter_requisition_id' => $requisition_id
             // Add other filters if needed, e.g., status
         );
       $data['quotations'] = $this->model_purchase_quotation->getQuotations($filter_data);

       $this->response->setOutput($this->load->view('purchase/requisition_quotations', $data));
    }

    /**
     * جلب القائمة المنسدلة للطلبات (لاستخدامها في Select2)
      */
  public function ajaxRequisitions() {
        $json = array();
        $search = $this->request->get['q'] ?? '';
       $results = $this->model_purchase_requisition->searchRequisitions($search);

        foreach ($results as $result) {
            $json[] = array(
               'id' => $result['requisition_id'],
                'text' => sprintf('#%s - %s', $result['req_number'], $result['branch_name'])
          );
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
   }

    /**
     * select2Product - البحث عن المنتجات باستخدام Ajax (Select2)
    */
      public function select2Product() {
            $json = array();
            $q = $this->request->get['q'] ?? '';

            $sql = "SELECT p.product_id, pd.name
                FROM `" . DB_PREFIX . "product` p
                LEFT JOIN `" . DB_PREFIX . "product_description` pd
                  ON (p.product_id = pd.product_id AND pd.language_id='" . (int)$this->config->get('config_language_id') . "')
                WHERE pd.name LIKE '%" . $this->db->escape($q) . "%'
                LIMIT 20";

            $query = $this->db->query($sql);
            foreach ($query->rows as $row) {
            $units = array();
              $uSql  = "SELECT u.unit_id, concat(u.desc_en,' - ',u.desc_ar) AS text
                        FROM `" . DB_PREFIX . "product_unit` pu
                        LEFT JOIN `" . DB_PREFIX . "unit` u ON (pu.unit_id = u.unit_id)
                        WHERE pu.product_id='" . (int)$row['product_id'] . "'";
              $uq = $this->db->query($uSql);
                if ($uq->num_rows) {
                foreach ($uq->rows as $u) {
                  $units[] = array(
                      'id'   => $u['unit_id'],
                      'text' => $u['text']
                  );
                  }
               }

              $json[] = array(
                  'id'    => $row['product_id'],
                  'text'  => $row['name'],
                  'units' => $units
              );
            }
         $this->sendJSON($json);
       }    /**
      * AJAX: جلب نموذج التعديل
      */
   public function ajaxGetRequisitionForm() {
        if (!$this->user->hasKey('purchase_requisition_edit')) {
           $this->response->setOutput('<div class="alert alert-danger">' .
               $this->language->get('error_permission') . '</div>');
            return;
       }

        $requisition_id = (int)($this->request->get['requisition_id'] ?? 0);
      $data['requisition'] = $this->model_purchase_requisition->getRequisition($requisition_id);
       if (!$data['requisition']) {
            $this->response->setOutput('<div class="alert alert-danger">' .
                 $this->language->get('error_loading_form') . '</div>');
             return;
        }

         $data['branches']    = $this->model_purchase_requisition->getBranches();
        $data['user_groups'] = $this->model_purchase_requisition->getUserGroups();
       $data['items']       = $this->model_purchase_requisition->getRequisitionItems($requisition_id);
      $data['user_token']  = $this->session->data['user_token'];

        $this->response->setOutput($this->load->view('purchase/requisition_edit_form', $data));
   }

    /**
     * تصدير قائمة طلبات الشراء إلى CSV
     */
    public function exportRequisitions() {
        // Check permission if needed
        // if (!$this->user->hasKey('purchase_requisition_export')) { ... }

        $filter_req_number = isset($this->request->get['filter_req_number']) ? $this->request->get['filter_req_number'] : '';
        $filter_status = isset($this->request->get['filter_status']) ? $this->request->get['filter_status'] : '';
        $filter_date_start = isset($this->request->get['filter_date_start']) ? $this->request->get['filter_date_start'] : '';
        $filter_date_end = isset($this->request->get['filter_date_end']) ? $this->request->get['filter_date_end'] : '';

        $filter_data = array(
            'filter_req_number' => $filter_req_number,
            'filter_status'     => $filter_status,
            'filter_date_start' => $filter_date_start,
            'filter_date_end'   => $filter_date_end
            // No limit for export
        );

        $results = $this->model_purchase_requisition->getRequisitions($filter_data);

        $filename = "requisitions_" . date('Y-m-d_H-i-s') . ".csv";

        $this->response->addHeader('Content-Type: text/csv');
        $this->response->addHeader('Content-Disposition: attachment; filename="' . $filename . '"');
        $this->response->addHeader('Cache-Control: no-cache, no-store, must-revalidate');
        $this->response->addHeader('Pragma: no-cache');
        $this->response->addHeader('Expires: 0');

        $output = fopen('php://output', 'w');

        // Add BOM for UTF-8 compatibility in Excel
        fputs($output, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));

        // Header Row (Using language variables)
        fputcsv($output, array(
            $this->language->get('column_requisition_id'),
            $this->language->get('column_req_number'),
            $this->language->get('column_branch'),
            $this->language->get('column_user_groups'),
            $this->language->get('column_status'),
            $this->language->get('column_date_added')
            // Add more columns if needed
        ));

        // Data Rows
        if ($results) {
            foreach ($results as $result) {
                fputcsv($output, array(
                    $result['requisition_id'],
                    $result['req_number'] ?? '',
                    $result['branch_name'] ?? '',
                    $result['user_group_name'] ?? '',
                    $result['status'],
                    $result['created_at']
                    // Add corresponding data fields
                ));
            }
        }

        fclose($output);
        exit(); // Stop script execution after sending the file
    }

  private function sendJSON($data) {
        $this->response->addHeader('Content-Type: application/json');
      $this->response->setOutput(json_encode($data));
  }
}
