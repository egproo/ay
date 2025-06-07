<?php
/**
 * Controller for Inventory Movement History
 *
 * This controller handles the display and management of inventory movement history,
 * allowing users to view, filter, and export inventory movement records.
 */
class ControllerInventoryMovementHistory extends Controller {
    /**
     * Main entry point - displays the movement history page
     */
    public function index() {
        $this->load->language('inventory/movement_history');
        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('catalog/inventory_manager');
        $this->load->model('catalog/product');
        $this->load->model('branch/branch');

        // Default filter values
        $filter_product_id = '';
        $filter_product = '';
        $filter_branch_id = '';
        $filter_movement_type = '';
        $filter_reference_type = '';
        $filter_date_start = date('Y-m-d', strtotime('-30 days'));
        $filter_date_end = date('Y-m-d');
        $filter_sort = 'sm.date_added';
        $filter_order = 'DESC';
        $page = 1;
        $limit = $this->config->get('config_limit_admin');

        // Process filter inputs
        if (isset($this->request->get['filter_product'])) {
            $filter_product = $this->request->get['filter_product'];

            // Try to get product ID from name
            $product_info = $this->model_catalog_product->getProductByName($filter_product);
            if ($product_info) {
                $filter_product_id = $product_info['product_id'];
            }
        }

        if (isset($this->request->get['filter_product_id'])) {
            $filter_product_id = $this->request->get['filter_product_id'];

            // Get product name for display
            $product_info = $this->model_catalog_product->getProduct($filter_product_id);
            if ($product_info) {
                $filter_product = $product_info['name'];
            }
        }

        if (isset($this->request->get['filter_branch_id'])) {
            $filter_branch_id = $this->request->get['filter_branch_id'];
        }

        if (isset($this->request->get['filter_movement_type'])) {
            $filter_movement_type = $this->request->get['filter_movement_type'];
        }

        if (isset($this->request->get['filter_reference_type'])) {
            $filter_reference_type = $this->request->get['filter_reference_type'];
        }

        if (isset($this->request->get['filter_date_start'])) {
            $filter_date_start = $this->request->get['filter_date_start'];
        }

        if (isset($this->request->get['filter_date_end'])) {
            $filter_date_end = $this->request->get['filter_date_end'];
        }

        if (isset($this->request->get['sort'])) {
            $filter_sort = $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $filter_order = $this->request->get['order'];
        }

        if (isset($this->request->get['page'])) {
            $page = (int)$this->request->get['page'];
        }

        // Build filter array
        $filter_data = array(
            'filter_product_id'    => $filter_product_id,
            'filter_branch_id'     => $filter_branch_id,
            'filter_movement_type' => $filter_movement_type,
            'filter_reference_type' => $filter_reference_type,
            'filter_date_start'    => $filter_date_start,
            'filter_date_end'      => $filter_date_end,
            'sort'                 => $filter_sort,
            'order'                => $filter_order,
            'start'                => ($page - 1) * $limit,
            'limit'                => $limit
        );

        // Get movements based on filters
        $movements = array();
        $movement_total = 0;

        if ($filter_product_id) {
            // If product is selected, get movements for that product
            $results = $this->model_catalog_inventory_manager->getProductMovements($filter_product_id, $filter_data);
            $movement_total = $this->model_catalog_inventory_manager->getTotalProductMovements($filter_product_id, $filter_data);
        } else {
            // Otherwise get all movements
            $results = $this->model_catalog_inventory_manager->getAllMovements($filter_data);
            $movement_total = $this->model_catalog_inventory_manager->getTotalAllMovements($filter_data);
        }

        // Process movement data for display
        foreach ($results as $result) {
            $reference_link = '';
            $reference_text = $result['reference_type'] . ' #' . $result['reference_id'];

            // Generate appropriate links based on reference type
            switch ($result['reference_type']) {
                case 'purchase':
                    $reference_link = $this->url->link('purchase/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $result['reference_id'], true);
                    break;
                case 'sale':
                    $reference_link = $this->url->link('sale/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $result['reference_id'], true);
                    break;
                case 'adjustment':
                    $reference_link = $this->url->link('inventory/adjustment/info', 'user_token=' . $this->session->data['user_token'] . '&adjustment_id=' . $result['reference_id'], true);
                    break;
                case 'transfer':
                    $reference_link = $this->url->link('inventory/transfer/info', 'user_token=' . $this->session->data['user_token'] . '&transfer_id=' . $result['reference_id'], true);
                    break;
                // Add more reference types as needed
            }

            // Format movement type for display
            $movement_type_text = '';
            if ($result['movement_type'] == 'in') {
                $movement_type_text = '<span class="badge bg-success">' . $this->language->get('text_in') . '</span>';
            } else {
                $movement_type_text = '<span class="badge bg-danger">' . $this->language->get('text_out') . '</span>';
            }

            // Format cost
            $cost_formatted = $this->currency->format($result['cost'], $this->config->get('config_currency'));

            // Add to movements array
            $movements[] = array(
                'movement_id'    => $result['movement_id'],
                'date_added'     => date($this->language->get('datetime_format'), strtotime($result['date_added'])),
                'product_name'   => $result['product_name'],
                'warehouse_name' => $result['warehouse_name'],
                'unit_name'      => $result['unit_name'],
                'quantity'       => $result['quantity'],
                'movement_type'  => $movement_type_text,
                'reference_type' => $result['reference_type'],
                'reference_id'   => $result['reference_id'],
                'reference_text' => $reference_text,
                'reference_link' => $reference_link,
                'cost'           => $cost_formatted,
                'notes'          => $result['notes'],
                'username'       => $result['username']
            );
        }

        // Prepare data for the view
        $data['movements'] = $movements;

        // Breadcrumbs
        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('inventory/movement_history', 'user_token=' . $this->session->data['user_token'], true)
        );

        // URLs
        $data['export'] = $this->url->link('inventory/movement_history/export', 'user_token=' . $this->session->data['user_token'], true);

        // Filters for the view
        $data['filter_product'] = $filter_product;
        $data['filter_product_id'] = $filter_product_id;
        $data['filter_branch_id'] = $filter_branch_id;
        $data['filter_movement_type'] = $filter_movement_type;
        $data['filter_reference_type'] = $filter_reference_type;
        $data['filter_date_start'] = $filter_date_start;
        $data['filter_date_end'] = $filter_date_end;

        // Get branches for dropdown
        $data['branches'] = $this->model_branch_branch->getBranches();

        // Movement types for dropdown
        $data['movement_types'] = array(
            '' => $this->language->get('text_all_types'),
            'in' => $this->language->get('text_in'),
            'out' => $this->language->get('text_out')
        );

        // Reference types for dropdown
        $data['reference_types'] = array(
            '' => $this->language->get('text_all_references'),
            'purchase' => $this->language->get('text_purchase'),
            'sale' => $this->language->get('text_sale'),
            'adjustment' => $this->language->get('text_adjustment'),
            'transfer' => $this->language->get('text_transfer'),
            'return' => $this->language->get('text_return'),
            'production' => $this->language->get('text_production')
            // Add more reference types as needed
        );

        // Pagination
        $pagination = new Pagination();
        $pagination->total = $movement_total;
        $pagination->page = $page;
        $pagination->limit = $limit;
        $pagination->url = $this->url->link('inventory/movement_history', 'user_token=' . $this->session->data['user_token'] . '&page={page}', true);

        $data['pagination'] = $pagination->render();
        $data['results'] = sprintf($this->language->get('text_pagination'), ($movement_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($movement_total - $limit)) ? $movement_total : ((($page - 1) * $limit) + $limit), $movement_total, ceil($movement_total / $limit));

        // Sort URLs
        $url = '';

        if (isset($this->request->get['filter_product'])) {
            $url .= '&filter_product=' . urlencode($this->request->get['filter_product']);
        }

        if (isset($this->request->get['filter_branch_id'])) {
            $url .= '&filter_branch_id=' . $this->request->get['filter_branch_id'];
        }

        if (isset($this->request->get['filter_movement_type'])) {
            $url .= '&filter_movement_type=' . $this->request->get['filter_movement_type'];
        }

        if (isset($this->request->get['filter_reference_type'])) {
            $url .= '&filter_reference_type=' . $this->request->get['filter_reference_type'];
        }

        if (isset($this->request->get['filter_date_start'])) {
            $url .= '&filter_date_start=' . $this->request->get['filter_date_start'];
        }

        if (isset($this->request->get['filter_date_end'])) {
            $url .= '&filter_date_end=' . $this->request->get['filter_date_end'];
        }

        $data['sort_date'] = $this->url->link('inventory/movement_history', 'user_token=' . $this->session->data['user_token'] . '&sort=sm.date_added' . $url, true);
        $data['sort_product'] = $this->url->link('inventory/movement_history', 'user_token=' . $this->session->data['user_token'] . '&sort=p.name' . $url, true);
        $data['sort_warehouse'] = $this->url->link('inventory/movement_history', 'user_token=' . $this->session->data['user_token'] . '&sort=w.name' . $url, true);
        $data['sort_quantity'] = $this->url->link('inventory/movement_history', 'user_token=' . $this->session->data['user_token'] . '&sort=sm.quantity' . $url, true);

        $data['sort'] = $filter_sort;
        $data['order'] = $filter_order;

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('inventory/movement_history', $data));
    }

    /**
     * Export movement history to Excel
     */
    public function export() {
        $this->load->language('inventory/movement_history');

        if (isset($this->request->get['filter_product_id'])) {
            $filter_product_id = $this->request->get['filter_product_id'];
        } else {
            $filter_product_id = '';
        }

        if (isset($this->request->get['filter_branch_id'])) {
            $filter_branch_id = $this->request->get['filter_branch_id'];
        } else {
            $filter_branch_id = '';
        }

        if (isset($this->request->get['filter_movement_type'])) {
            $filter_movement_type = $this->request->get['filter_movement_type'];
        } else {
            $filter_movement_type = '';
        }

        if (isset($this->request->get['filter_reference_type'])) {
            $filter_reference_type = $this->request->get['filter_reference_type'];
        } else {
            $filter_reference_type = '';
        }

        if (isset($this->request->get['filter_date_start'])) {
            $filter_date_start = $this->request->get['filter_date_start'];
        } else {
            $filter_date_start = date('Y-m-d', strtotime('-30 days'));
        }

        if (isset($this->request->get['filter_date_end'])) {
            $filter_date_end = $this->request->get['filter_date_end'];
        } else {
            $filter_date_end = date('Y-m-d');
        }

        // Build filter array
        $filter_data = array(
            'filter_product_id'    => $filter_product_id,
            'filter_branch_id'     => $filter_branch_id,
            'filter_movement_type' => $filter_movement_type,
            'filter_reference_type' => $filter_reference_type,
            'filter_date_start'    => $filter_date_start,
            'filter_date_end'      => $filter_date_end,
            'sort'                 => 'sm.date_added',
            'order'                => 'DESC',
            'start'                => 0,
            'limit'                => 5000 // Limit for export
        );

        $this->load->model('catalog/inventory_manager');
        $this->load->model('catalog/product');

        // Get movements based on filters
        if ($filter_product_id) {
            $results = $this->model_catalog_inventory_manager->getProductMovements($filter_product_id, $filter_data);
            $product_info = $this->model_catalog_product->getProduct($filter_product_id);
            $title = $this->language->get('text_movement_history') . ': ' . $product_info['name'];
        } else {
            $results = $this->model_catalog_inventory_manager->getAllMovements($filter_data);
            $title = $this->language->get('text_all_movement_history');
        }

        // Create Excel file
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set title
        $sheet->setCellValue('A1', $title);
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getFont()->setBold(true);
        $sheet->getStyle('A1')->getFont()->setSize(14);

        // Set filter information
        $sheet->setCellValue('A2', $this->language->get('text_date_range') . ': ' . $filter_date_start . ' - ' . $filter_date_end);
        $sheet->mergeCells('A2:H2');

        // Set headers
        $sheet->setCellValue('A4', $this->language->get('column_date'));
        $sheet->setCellValue('B4', $this->language->get('column_product'));
        $sheet->setCellValue('C4', $this->language->get('column_warehouse'));
        $sheet->setCellValue('D4', $this->language->get('column_unit'));
        $sheet->setCellValue('E4', $this->language->get('column_quantity'));
        $sheet->setCellValue('F4', $this->language->get('column_movement_type'));
        $sheet->setCellValue('G4', $this->language->get('column_reference'));
        $sheet->setCellValue('H4', $this->language->get('column_cost'));
        $sheet->setCellValue('I4', $this->language->get('column_notes'));
        $sheet->setCellValue('J4', $this->language->get('column_user'));

        $sheet->getStyle('A4:J4')->getFont()->setBold(true);

        // Fill data
        $row = 5;
        foreach ($results as $result) {
            $sheet->setCellValue('A' . $row, date($this->language->get('datetime_format'), strtotime($result['date_added'])));
            $sheet->setCellValue('B' . $row, $result['product_name']);
            $sheet->setCellValue('C' . $row, $result['warehouse_name']);
            $sheet->setCellValue('D' . $row, $result['unit_name']);
            $sheet->setCellValue('E' . $row, $result['quantity']);
            $sheet->setCellValue('F' . $row, $result['movement_type'] == 'in' ? $this->language->get('text_in') : $this->language->get('text_out'));
            $sheet->setCellValue('G' . $row, $result['reference_type'] . ' #' . $result['reference_id']);
            $sheet->setCellValue('H' . $row, $result['cost']);
            $sheet->setCellValue('I' . $row, $result['notes']);
            $sheet->setCellValue('J' . $row, $result['username']);

            $row++;
        }

        // Auto size columns
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Set filename
        $filename = 'inventory_movement_history_' . date('Y-m-d_H-i-s') . '.xlsx';

        // Redirect output to a client's web browser (Xlsx)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Get movement details via AJAX
     */
    public function getMovementDetails() {
        $this->load->language('inventory/movement_history');

        $json = array();

        if (isset($this->request->get['movement_id'])) {
            $movement_id = (int)$this->request->get['movement_id'];

            $this->load->model('catalog/inventory_manager');

            $movement = $this->model_catalog_inventory_manager->getMovementDetails($movement_id);

            if ($movement) {
                // Format data for display
                $movement['date_added_formatted'] = date($this->language->get('datetime_format'), strtotime($movement['date_added']));
                $movement['cost_formatted'] = $this->currency->format($movement['cost'], $this->config->get('config_currency'));
                $movement['movement_type_text'] = $movement['movement_type'] == 'in' ? $this->language->get('text_in') : $this->language->get('text_out');

                $json['movement'] = $movement;
                $json['success'] = true;
            } else {
                $json['error'] = $this->language->get('error_movement_not_found');
            }
        } else {
            $json['error'] = $this->language->get('error_movement_id');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
