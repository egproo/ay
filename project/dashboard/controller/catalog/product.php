<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class ControllerCatalogProduct extends Controller {
    private $error = array();

    public function exportInventoryReport() {
        $this->load->language('catalog/product');
        $this->load->model('catalog/product');

        $filter_data = array(
            'filter_name'         => isset($this->request->get['filter_name']) ? $this->request->get['filter_name'] : '',
            'filter_model'        => isset($this->request->get['filter_model']) ? $this->request->get['filter_model'] : '',
            'filter_branch_id'    => isset($this->request->get['filter_branch_id']) ? $this->request->get['filter_branch_id'] : '',
            'filter_unit_id'      => isset($this->request->get['filter_unit_id']) ? $this->request->get['filter_unit_id'] : '',
            'filter_quantity_min' => isset($this->request->get['filter_quantity_min']) ? $this->request->get['filter_quantity_min'] : '',
            'filter_quantity_max' => isset($this->request->get['filter_quantity_max']) ? $this->request->get['filter_quantity_max'] : '',
            'filter_cost_min'     => isset($this->request->get['filter_cost_min']) ? $this->request->get['filter_cost_min'] : '',
            'filter_cost_max'     => isset($this->request->get['filter_cost_max']) ? $this->request->get['filter_cost_max'] : '',
            'sort'                => isset($this->request->get['sort']) ? $this->request->get['sort'] : 'product_name',
            'order'               => isset($this->request->get['order']) ? $this->request->get['order'] : 'ASC'
        );

        $inventory_data = $this->model_catalog_product->getInventoryValuationReport($filter_data);

        require_once(DIR_SYSTEM . 'library/PHPExcel/Classes/PHPExcel.php');

        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()
            ->setCreator($this->config->get('config_name'))
            ->setLastModifiedBy($this->user->getUserName())
            ->setTitle($this->language->get('text_inventory_report'))
            ->setSubject($this->language->get('text_inventory_valuation'))
            ->setDescription($this->language->get('text_report_date') . ' ' . date('Y-m-d H:i:s'))
            ->setKeywords($this->language->get('text_inventory'))
            ->setCategory($this->language->get('text_inventory_reports'));

        $objPHPExcel->setActiveSheetIndex(0);
        $sheet = $objPHPExcel->getActiveSheet();
        $sheet->setTitle($this->language->get('text_inventory_valuation'));

        $column_headers = array(
            'A' => $this->language->get('column_product_id'),
            'B' => $this->language->get('column_name'),
            'C' => $this->language->get('column_model'),
            'D' => $this->language->get('column_sku'),
            'E' => $this->language->get('column_unit'),
            'F' => $this->language->get('column_branch'),
            'G' => $this->language->get('column_quantity'),
            'H' => $this->language->get('column_average_cost'),
            'I' => $this->language->get('column_total_value')
        );

        $header_style = array(
            'font' => array(
                'bold' => true,
                'color' => array('rgb' => 'FFFFFF'),
            ),
            'fill' => array(
                'type' => PHPExcel_Style_Fill::FILL_SOLID,
                'color' => array('rgb' => '4472C4')
            ),
            'alignment' => array(
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            )
        );

        foreach ($column_headers as $column => $header) {
            $sheet->setCellValue($column . '1', $header);
        }

        $sheet->getStyle('A1:I1')->applyFromArray($header_style);

        $row = 2;
        $total_value = 0;

        foreach ($inventory_data as $item) {
            $sheet->setCellValue('A' . $row, $item['product_id']);
            $sheet->setCellValue('B' . $row, $item['product_name']);
            $sheet->setCellValue('C' . $row, $item['model']);
            $sheet->setCellValue('D' . $row, $item['sku']);
            $sheet->setCellValue('E' . $row, $item['unit_name']);
            $sheet->setCellValue('F' . $row, $item['branch_name']);
            $sheet->setCellValue('G' . $row, $item['quantity']);
            $sheet->setCellValue('H' . $row, $item['average_cost']);
            $sheet->setCellValue('I' . $row, $item['total_value']);

            $total_value += $item['total_value'];

            $sheet->getStyle('G' . $row . ':I' . $row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

            $row++;
        }

        $sheet->setCellValue('A' . $row, '');
        $sheet->setCellValue('B' . $row, $this->language->get('text_total'));
        $sheet->setCellValue('C' . $row, '');
        $sheet->setCellValue('D' . $row, '');
        $sheet->setCellValue('E' . $row, '');
        $sheet->setCellValue('F' . $row, '');
        $sheet->setCellValue('G' . $row, '');
        $sheet->setCellValue('H' . $row, '');
        $sheet->setCellValue('I' . $row, $total_value);

        $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray(array(
            'font' => array('bold' => true),
            'fill' => array(
                'type' => PHPExcel_Style_Fill::FILL_SOLID,
                'color' => array('rgb' => 'E7E6E6')
            )
        ));

        $sheet->getStyle('I' . $row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

        foreach (range('A', 'I') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $sheet->freezePane('A2');

        $filename = 'inventory_report_' . date('Y-m-d_H-i-s') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }

    public function runABCAnalysis() {
        $this->load->language('catalog/product');
        $this->load->model('catalog/product');

        $json = array();

        if (!$this->user->hasPermission('modify', 'catalog/product')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $data = array(
                'branch_id' => isset($this->request->post['branch_id']) ? $this->request->post['branch_id'] : 0,
                'period_start' => isset($this->request->post['period_start']) ? $this->request->post['period_start'] : date('Y-m-d', strtotime('-1 year')),
                'period_end' => isset($this->request->post['period_end']) ? $this->request->post['period_end'] : date('Y-m-d'),
                'analysis_type' => isset($this->request->post['analysis_type']) ? $this->request->post['analysis_type'] : 'value'
            );

            if (empty($data['branch_id'])) {
                $json['error'] = $this->language->get('error_branch_required');
            } else {
                $analysis_results = $this->model_catalog_product->getABCAnalysis($data);

                $json['success'] = $this->language->get('text_analysis_success');
                $json['results'] = $analysis_results;

                $json['chart_data'] = array(
                    'labels' => array('A', 'B', 'C'),
                    'percentages' => array(
                        round($analysis_results['summary']['a_percentage'], 2),
                        round($analysis_results['summary']['b_percentage'], 2),
                        round($analysis_results['summary']['c_percentage'], 2)
                    ),
                    'counts' => array(
                        count($analysis_results['a_items']),
                        count($analysis_results['b_items']),
                        count($analysis_results['c_items'])
                    )
                );
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function viewInventoryTurnover() {
        $this->load->language('catalog/product');
        $this->load->model('catalog/product');

        $json = array();

        if (!$this->user->hasPermission('access', 'catalog/product')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $data = array(
                'branch_id' => isset($this->request->post['branch_id']) ? $this->request->post['branch_id'] : 0,
                'period_start' => isset($this->request->post['period_start']) ? $this->request->post['period_start'] : date('Y-m-d', strtotime('-1 year')),
                'period_end' => isset($this->request->post['period_end']) ? $this->request->post['period_end'] : date('Y-m-d')
            );

            if (empty($data['branch_id'])) {
                $json['error'] = $this->language->get('error_branch_required');
            } else {
                $turnover_results = $this->model_catalog_product->getInventoryTurnoverReport($data);

                $json['results'] = array();
                $total_items = count($turnover_results);
                $total_turnover = 0;
                $total_average_inventory = 0;
                $total_cogs = 0;

                foreach ($turnover_results as $item) {
                    $item['beginning_value_formatted'] = $this->currency->format($item['beginning_value'], $this->config->get('config_currency'));
                    $item['ending_value_formatted'] = $this->currency->format($item['ending_value'], $this->config->get('config_currency'));
                    $item['average_inventory_formatted'] = $this->currency->format($item['average_inventory'], $this->config->get('config_currency'));
                    $item['cost_of_goods_sold_formatted'] = $this->currency->format($item['cost_of_goods_sold'], $this->config->get('config_currency'));

                    $total_turnover += $item['turnover_ratio'];
                    $total_average_inventory += $item['average_inventory'];
                    $total_cogs += $item['cost_of_goods_sold'];

                    $json['results'][] = $item;
                }

                $json['summary'] = array(
                    'total_items' => $total_items,
                    'average_turnover' => $total_items > 0 ? $total_turnover / $total_items : 0,
                    'total_average_inventory' => $total_average_inventory,
                    'total_average_inventory_formatted' => $this->currency->format($total_average_inventory, $this->config->get('config_currency')),
                    'total_cogs' => $total_cogs,
                    'total_cogs_formatted' => $this->currency->format($total_cogs, $this->config->get('config_currency')),
                    'overall_turnover' => $total_average_inventory > 0 ? $total_cogs / $total_average_inventory : 0,
                    'overall_days_on_hand' => $total_average_inventory > 0 && $total_cogs > 0 ? round(365 / ($total_cogs / $total_average_inventory)) : 0
                );

                $json['success'] = $this->language->get('text_turnover_success');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function viewExpiringProducts() {
        $this->load->language('catalog/product');
        $this->load->model('catalog/product');

        $json = array();

        if (!$this->user->hasPermission('access', 'catalog/product')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $data = array(
                'alert_days' => isset($this->request->post['alert_days']) ? (int)$this->request->post['alert_days'] : 30,
                'branch_id' => isset($this->request->post['branch_id']) ? (int)$this->request->post['branch_id'] : 0,
                'start' => isset($this->request->post['start']) ? (int)$this->request->post['start'] : 0,
                'limit' => isset($this->request->post['limit']) ? (int)$this->request->post['limit'] : 20
            );

            $expiring_products = $this->model_catalog_product->getBatchExpiringSoon($data);

            $json['products'] = array();

            foreach ($expiring_products as $product) {
                $json['products'][] = array(
                    'batch_id' => $product['batch_id'],
                    'product_id' => $product['product_id'],
                    'name' => $product['name'],
                    'model' => $product['model'],
                    'batch_number' => $product['batch_number'],
                    'expiry_date' => date($this->language->get('date_format_short'), strtotime($product['expiry_date'])),
                    'days_remaining' => $product['days_remaining'],
                    'remaining_quantity' => $product['remaining_quantity'],
                    'unit_name' => $product['unit_name'],
                    'branch_name' => $product['branch_name'],
                    'cost' => $this->currency->format($product['cost'], $this->config->get('config_currency')),
                    'total_value' => $this->currency->format($product['remaining_quantity'] * $product['cost'], $this->config->get('config_currency')),
                    'status_class' => $product['days_remaining'] <= 7 ? 'danger' : ($product['days_remaining'] <= 15 ? 'warning' : 'info')
                );
            }

            $json['success'] = $this->language->get('text_expiring_products_found');
            $json['total'] = count($expiring_products);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function createStockCount() {
        $this->load->language('catalog/product');
        $this->load->model('catalog/product');

        $json = array();

        if (!$this->user->hasPermission('modify', 'catalog/product')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (!isset($this->request->post['branch_id']) || empty($this->request->post['branch_id'])) {
                $json['error'] = $this->language->get('error_branch_required');
            } else {
                $data = array(
                    'branch_id' => (int)$this->request->post['branch_id'],
                    'reference_code' => isset($this->request->post['reference_code']) ? $this->request->post['reference_code'] : 'SC-' . date('YmdHis'),
                    'count_date' => isset($this->request->post['count_date']) ? $this->request->post['count_date'] : date('Y-m-d'),
                    'notes' => isset($this->request->post['notes']) ? $this->request->post['notes'] : '',
                    'category_id' => isset($this->request->post['category_id']) ? (int)$this->request->post['category_id'] : 0,
                    'products' => isset($this->request->post['products']) ? $this->request->post['products'] : array()
                );

                $stock_count_id = $this->model_catalog_product->createStockCount($data);

                if ($stock_count_id) {
                    $json['success'] = $this->language->get('text_stock_count_created');
                    $json['stock_count_id'] = $stock_count_id;
                    $json['redirect'] = $this->url->link('inventory/stock_count/edit', 'user_token=' . $this->session->data['user_token'] . '&stock_count_id=' . $stock_count_id, true);
                } else {
                    $json['error'] = $this->language->get('error_stock_count_creation');
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function updateStockCountItems() {
        $this->load->language('catalog/product');
        $this->load->model('catalog/product');

        $json = array();

        if (!$this->user->hasPermission('modify', 'catalog/product')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (!isset($this->request->post['stock_count_id']) || empty($this->request->post['stock_count_id'])) {
                $json['error'] = $this->language->get('error_stock_count_id_required');
            } elseif (!isset($this->request->post['items']) || !is_array($this->request->post['items'])) {
                $json['error'] = $this->language->get('error_items_required');
            } else {
                $success_count = 0;
                $failure_count = 0;

                foreach ($this->request->post['items'] as $item) {
                    if (isset($item['count_item_id']) && isset($item['counted_qty'])) {
                        $notes = isset($item['notes']) ? $item['notes'] : '';
                        $result = $this->model_catalog_product->updateStockCountItem($item['count_item_id'], $item['counted_qty'], $notes);

                        if ($result) {
                            $success_count++;
                        } else {
                            $failure_count++;
                        }
                    } else {
                        $failure_count++;
                    }
                }

                $json['success'] = sprintf($this->language->get('text_stock_count_items_updated'), $success_count);

                if ($failure_count > 0) {
                    $json['warning'] = sprintf($this->language->get('warning_some_items_failed'), $failure_count);
                }

                $json['updated_count'] = $success_count;
                $json['failed_count'] = $failure_count;
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function completeStockCount() {
        $this->load->language('catalog/product');
        $this->load->model('catalog/product');

        $json = array();

        if (!$this->user->hasPermission('modify', 'catalog/product')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (!isset($this->request->post['stock_count_id']) || empty($this->request->post['stock_count_id'])) {
                $json['error'] = $this->language->get('error_stock_count_id_required');
            } else {
                $stock_count_id = (int)$this->request->post['stock_count_id'];
                $apply_adjustments = isset($this->request->post['apply_adjustments']) && $this->request->post['apply_adjustments'] == '1';

                $result = $this->model_catalog_product->completeStockCount($stock_count_id, $apply_adjustments);

                if ($result) {
                    $json['success'] = $this->language->get('text_stock_count_completed');

                    if ($apply_adjustments) {
                        $json['success'] .= ' ' . $this->language->get('text_adjustments_applied');
                    }

                    $json['redirect'] = $this->url->link('inventory/stock_count', 'user_token=' . $this->session->data['user_token'], true);
                } else {
                    $json['error'] = $this->language->get('error_completing_stock_count');
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function viewInventoryAlerts() {
        $this->load->language('catalog/product');
        $this->load->model('catalog/product');

        $json = array();

        if (!$this->user->hasPermission('access', 'catalog/product')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $data = array(
                'filter_alert_type' => isset($this->request->post['filter_alert_type']) ? $this->request->post['filter_alert_type'] : '',
                'filter_status' => isset($this->request->post['filter_status']) ? $this->request->post['filter_status'] : 'active',
                'filter_branch_id' => isset($this->request->post['filter_branch_id']) ? (int)$this->request->post['filter_branch_id'] : 0,
                'filter_product_id' => isset($this->request->post['filter_product_id']) ? (int)$this->request->post['filter_product_id'] : 0,
                'filter_name' => isset($this->request->post['filter_name']) ? $this->request->post['filter_name'] : '',
                'start' => isset($this->request->post['start']) ? (int)$this->request->post['start'] : 0,
                'limit' => isset($this->request->post['limit']) ? (int)$this->request->post['limit'] : 20
            );

            $alerts = $this->model_catalog_product->getInventoryAlerts($data);

            $json['alerts'] = array();

            foreach ($alerts as $alert) {
                $alert_types = array(
                    'minimum' => $this->language->get('text_minimum_stock'),
                    'maximum' => $this->language->get('text_maximum_stock'),
                    'expired' => $this->language->get('text_expiring_stock'),
                    'slow_moving' => $this->language->get('text_slow_moving_stock'),
                    'damaged' => $this->language->get('text_damaged_stock')
                );

                $alert_statuses = array(
                    'active' => $this->language->get('text_active'),
                    'acknowledged' => $this->language->get('text_acknowledged'),
                    'resolved' => $this->language->get('text_resolved'),
                    'ignored' => $this->language->get('text_ignored')
                );

                $json['alerts'][] = array(
                    'alert_id' => $alert['alert_id'],
                    'product_id' => $alert['product_id'],
                    'product_name' => $alert['product_name'],
                    'model' => $alert['model'],
                    'branch_id' => $alert['branch_id'],
                    'branch_name' => $alert['branch_name'],
                    'unit_id' => $alert['unit_id'],
                    'unit_name' => $alert['unit_name'],
                    'alert_type' => $alert['alert_type'],
                    'alert_type_text' => isset($alert_types[$alert['alert_type']]) ? $alert_types[$alert['alert_type']] : $alert['alert_type'],
                    'status' => $alert['status'],
                    'status_text' => isset($alert_statuses[$alert['status']]) ? $alert_statuses[$alert['status']] : $alert['status'],
                    'quantity' => $alert['quantity'],
                    'current_quantity' => $alert['quantity'],
                    'threshold' => $alert['threshold'],
                    'created_at' => date($this->language->get('datetime_format'), strtotime($alert['created_at'])),
                    'days_no_movement' => $alert['days_no_movement'],
                    'last_movement_date' => $alert['last_movement_date'] ? date($this->language->get('date_format_short'), strtotime($alert['last_movement_date'])) : '',
                    'recommended_action' => $alert['recommended_action'],
                    'notes' => $alert['notes'],
                    'acknowledged_by' => $alert['user_name'],
                    'acknowledged_at' => $alert['acknowledged_at'] ? date($this->language->get('datetime_format'), strtotime($alert['acknowledged_at'])) : '',
                    'resolved_at' => $alert['resolved_at'] ? date($this->language->get('datetime_format'), strtotime($alert['resolved_at'])) : '',
                    'value' => $this->currency->format($alert['quantity'] * $alert['average_cost'], $this->config->get('config_currency')),
                    'alert_class' => $this->getAlertClass($alert)
                );
            }

            $json['success'] = $this->language->get('text_alerts_loaded');
            $json['total'] = count($alerts);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    private function getAlertClass($alert) {
        switch ($alert['alert_type']) {
            case 'minimum':
                return 'danger';
            case 'maximum':
                return 'warning';
            case 'expired':
                if ($alert['days_left'] < 7) {
                    return 'danger';
                } elseif ($alert['days_left'] < 30) {
                    return 'warning';
                } else {
                    return 'info';
                }
            case 'slow_moving':
                if ($alert['days_no_movement'] > 180) {
                    return 'danger';
                } elseif ($alert['days_no_movement'] > 90) {
                    return 'warning';
                } else {
                    return 'info';
                }
            case 'damaged':
                return 'danger';
            default:
                return 'info';
        }
    }

    public function updateAlertStatus() {
        $this->load->language('catalog/product');
        $this->load->model('catalog/product');

        $json = array();

        if (!$this->user->hasPermission('modify', 'catalog/product')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (!isset($this->request->post['alert_id']) || empty($this->request->post['alert_id'])) {
                $json['error'] = $this->language->get('error_alert_id_required');
            } elseif (!isset($this->request->post['status']) || empty($this->request->post['status'])) {
                $json['error'] = $this->language->get('error_status_required');
            } else {
                $alert_id = (int)$this->request->post['alert_id'];
                $status = $this->request->post['status'];
                $notes = isset($this->request->post['notes']) ? $this->request->post['notes'] : '';

                $result = $this->model_catalog_product->updateAlertStatus($alert_id, $status, $notes);

                if ($result) {
                    $json['success'] = $this->language->get('text_alert_status_updated');
                } else {
                    $json['error'] = $this->language->get('error_updating_alert');
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function viewMinMaxLevels() {
        $this->load->language('catalog/product');
        $this->load->model('catalog/product');

        $json = array();

        if (!$this->user->hasPermission('access', 'catalog/product')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $data = array(
                'filter_branch_id' => isset($this->request->post['filter_branch_id']) ? (int)$this->request->post['filter_branch_id'] : 0,
                'filter_product_id' => isset($this->request->post['filter_product_id']) ? (int)$this->request->post['filter_product_id'] : 0,
                'filter_name' => isset($this->request->post['filter_name']) ? $this->request->post['filter_name'] : '',
                'filter_needs_reorder' => isset($this->request->post['filter_needs_reorder']) && $this->request->post['filter_needs_reorder'] == '1',
                'sort' => isset($this->request->post['sort']) ? $this->request->post['sort'] : 'product_name',
                'order' => isset($this->request->post['order']) ? $this->request->post['order'] : 'ASC',
                'start' => isset($this->request->post['start']) ? (int)$this->request->post['start'] : 0,
'limit' => isset($this->request->post['limit']) ? (int)$this->request->post['limit'] : 20
            );

            $levels = $this->model_catalog_product->getMinMaxLevels($data);

            $json['levels'] = array();

            foreach ($levels as $level) {
                $reorder_status = 'normal';
                $reorder_text = $this->language->get('text_stock_normal');

                if ($level['quantity'] <= $level['minimum_level']) {
                    $reorder_status = 'below';
                    $reorder_text = $this->language->get('text_below_minimum');
                } elseif ($level['quantity'] >= $level['maximum_level']) {
                    $reorder_status = 'above';
                    $reorder_text = $this->language->get('text_above_maximum');
                }

                $json['levels'][] = array(
                    'level_id' => $level['level_id'],
                    'product_id' => $level['product_id'],
                    'product_name' => $level['product_name'],
                    'model' => $level['model'],
                    'branch_id' => $level['branch_id'],
                    'branch_name' => $level['branch_name'],
                    'unit_id' => $level['unit_id'],
                    'unit_name' => $level['unit_name'],
                    'minimum_level' => $level['minimum_level'],
                    'maximum_level' => $level['maximum_level'],
                    'reorder_point' => $level['reorder_point'],
                    'economic_order_quantity' => $level['economic_order_quantity'],
                    'quantity' => $level['quantity'],
                    'quantity_available' => $level['quantity_available'],
                    'reorder_status' => $reorder_status,
                    'reorder_text' => $reorder_text,
                    'status_class' => $reorder_status == 'below' ? 'danger' : ($reorder_status == 'above' ? 'warning' : 'success')
                );
            }

            $json['success'] = $this->language->get('text_levels_loaded');
            $json['total'] = count($levels);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function saveMinMaxLevels() {
        $this->load->language('catalog/product');
        $this->load->model('catalog/product');

        $json = array();

        if (!$this->user->hasPermission('modify', 'catalog/product')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (!isset($this->request->post['product_id']) || empty($this->request->post['product_id'])) {
                $json['error'] = $this->language->get('error_product_id_required');
            } elseif (!isset($this->request->post['branch_id']) || empty($this->request->post['branch_id'])) {
                $json['error'] = $this->language->get('error_branch_id_required');
            } elseif (!isset($this->request->post['unit_id']) || empty($this->request->post['unit_id'])) {
                $json['error'] = $this->language->get('error_unit_id_required');
            } elseif (!isset($this->request->post['minimum_level']) || $this->request->post['minimum_level'] === '') {
                $json['error'] = $this->language->get('error_minimum_level_required');
            } else {
                $data = array(
                    'product_id' => (int)$this->request->post['product_id'],
                    'branch_id' => (int)$this->request->post['branch_id'],
                    'unit_id' => (int)$this->request->post['unit_id'],
                    'minimum_level' => (float)$this->request->post['minimum_level'],
                    'maximum_level' => isset($this->request->post['maximum_level']) ? (float)$this->request->post['maximum_level'] : 0,
                    'reorder_point' => isset($this->request->post['reorder_point']) ? (float)$this->request->post['reorder_point'] : 0,
                    'economic_order_quantity' => isset($this->request->post['economic_order_quantity']) ? (float)$this->request->post['economic_order_quantity'] : 0
                );

                $existing = $this->db->query("SELECT level_id FROM " . DB_PREFIX . "reorder_level
                    WHERE product_id = '" . (int)$data['product_id'] . "'
                    AND branch_id = '" . (int)$data['branch_id'] . "'
                    AND unit_id = '" . (int)$data['unit_id'] . "'");

                if ($existing->num_rows) {
                    $this->db->query("UPDATE " . DB_PREFIX . "reorder_level SET
                        minimum_level = '" . (float)$data['minimum_level'] . "',
                        maximum_level = '" . (float)$data['maximum_level'] . "',
                        reorder_point = '" . (float)$data['reorder_point'] . "',
                        economic_order_quantity = '" . (float)$data['economic_order_quantity'] . "'
                        WHERE level_id = '" . (int)$existing->row['level_id'] . "'");

                    $json['success'] = $this->language->get('text_levels_updated');
                } else {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "reorder_level SET
                        product_id = '" . (int)$data['product_id'] . "',
                        branch_id = '" . (int)$data['branch_id'] . "',
                        unit_id = '" . (int)$data['unit_id'] . "',
                        minimum_level = '" . (float)$data['minimum_level'] . "',
                        maximum_level = '" . (float)$data['maximum_level'] . "',
                        reorder_point = '" . (float)$data['reorder_point'] . "',
                        economic_order_quantity = '" . (float)$data['economic_order_quantity'] . "'");

                    $json['success'] = $this->language->get('text_levels_added');
                }

                $inventory = $this->model_catalog_product->getProductInventoryByUnit(
                    $data['product_id'],
                    $data['unit_id'],
                    $data['branch_id']
                );

                if ($inventory && $inventory['quantity'] <= $data['minimum_level']) {
                    $alert_query = $this->db->query("SELECT alert_id
                        FROM " . DB_PREFIX . "inventory_alert
                        WHERE product_id = '" . (int)$data['product_id'] . "'
                        AND branch_id = '" . (int)$data['branch_id'] . "'
                        AND unit_id = '" . (int)$data['unit_id'] . "'
                        AND alert_type = 'minimum'
                        AND status IN ('active', 'acknowledged')");

                    if (!$alert_query->num_rows) {
                        $this->db->query("INSERT INTO " . DB_PREFIX . "inventory_alert SET
                            product_id = '" . (int)$data['product_id'] . "',
                            branch_id = '" . (int)$data['branch_id'] . "',
                            unit_id = '" . (int)$data['unit_id'] . "',
                            alert_type = 'minimum',
                            quantity = '" . (float)$inventory['quantity'] . "',
                            threshold = '" . (float)$data['minimum_level'] . "',
                            status = 'active',
                            recommended_action = 'reorder',
                            created_at = NOW(),
                            notes = 'مخزون أقل من الحد الأدنى'");
                    }
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getLowStockProducts() {
        $this->load->language('catalog/product');
        $this->load->model('catalog/product');

        $json = array();

        $results = $this->model_catalog_product->getLowStockProducts();

        $json['products'] = array();
        foreach ($results as $result) {
            $json['products'][] = array(
                'product_id'  => $result['product_id'],
                'name'        => $result['name'],
                'unit_name'   => $result['unit_name'],
                'branch_name' => $result['branch_name'],
                'quantity'    => $result['quantity'],
                'min_stock'   => $result['min_stock'],
                'edit'        => $this->url->link('catalog/product/edit', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $result['product_id'], true)
            );
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getExpiringProducts() {
        $this->load->language('catalog/product');
        $this->load->model('catalog/product');

        $json = array();

        $results = $this->model_catalog_product->getExpiringProducts();

        $json['products'] = array();
        foreach ($results as $result) {
            $json['products'][] = array(
                'product_id'   => $result['product_id'],
                'name'         => $result['name'],
                'batch_number' => $result['batch_number'],
                'branch_name'  => $result['branch_name'],
                'quantity'     => $result['quantity'],
                'expiry_date'  => date($this->language->get('date_format_short'), strtotime($result['expiry_date'])),
                'days_left'    => $result['days_left'],
                'edit'         => $this->url->link('catalog/product/edit', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $result['product_id'], true)
            );
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function bulkUpdatePrices() {
        $this->load->language('catalog/product');
        $json = array();

        if (!$this->user->hasPermission('modify', 'catalog/product')) {
            $json['error'] = $this->language->get('error_permission');
        } elseif (!isset($this->request->post['products']) || !is_array($this->request->post['products']) || empty($this->request->post['products'])) {
            $json['error'] = $this->language->get('error_no_products_selected');
        } elseif (!isset($this->request->post['update_type'])) {
            $json['error'] = $this->language->get('error_update_type_required');
        } elseif (!isset($this->request->post['value']) || !is_numeric($this->request->post['value'])) {
            $json['error'] = $this->language->get('error_value_required');
        } elseif (!isset($this->request->post['price_field'])) {
            $json['error'] = $this->language->get('error_price_field_required');
        } else {
            $this->load->model('catalog/product');

            $update_type = $this->request->post['update_type'];
            $value = (float)$this->request->post['value'];
            $price_field = $this->request->post['price_field'];
            $unit_id = isset($this->request->post['unit_id']) ? $this->request->post['unit_id'] : 'all';

            $valid = true;
            if ($update_type === 'percentage' && ($value < -100 || $value > 1000)) {
                $json['error'] = $this->language->get('error_percentage_range');
                $valid = false;
            } elseif ($update_type === 'fixed' && $value < -10000) {
                $json['error'] = $this->language->get('error_fixed_range');
                $valid = false;
            } elseif ($update_type === 'cost_based' && ($value <= 0 || $value >= 100)) {
                $json['error'] = $this->language->get('error_margin_range');
                $valid = false;
            }

            $allowed_fields = array('base_price', 'special_price', 'wholesale_price', 'half_wholesale_price', 'custom_price');
            if (!in_array($price_field, $allowed_fields)) {
                $json['error'] = $this->language->get('error_invalid_price_field');
                $valid = false;
            }

            if ($valid) {
                try {
                    $this->db->query("START TRANSACTION");

                    $result = $this->model_catalog_product->updateProductPrices(
                        $this->request->post['products'],
                        $update_type,
                        $value,
                        $price_field,
                        $unit_id
                    );

                    $this->db->query("COMMIT");

                    $json['success'] = sprintf($this->language->get('text_prices_updated'), $result);
                    $json['updated_count'] = $result;
                } catch (Exception $e) {
                    $this->db->query("ROLLBACK");
                    $json['error'] = $this->language->get('error_update_failed') . ': ' . $e->getMessage();
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function bulkStockUpdate() {
        $this->load->language('catalog/product');
        $this->load->model('catalog/product');

        $json = array();

        if (!$this->user->hasPermission('modify', 'catalog/product')) {
            $json['error'] = $this->language->get('error_permission');
        } elseif (isset($this->request->post['products']) && isset($this->request->post['branch_id']) && isset($this->request->post['unit_id']) && isset($this->request->post['update_type']) && isset($this->request->post['value'])) {
            $products = $this->request->post['products'];
            $branchId = $this->request->post['branch_id'];
            $unitId = $this->request->post['unit_id'];
            $updateType = $this->request->post['update_type'];
            $value = (float)$this->request->post['value'];
            $reason = isset($this->request->post['reason']) ? $this->request->post['reason'] : $this->language->get('text_bulk_update');
            $updateCost = isset($this->request->post['update_cost']) && $this->request->post['update_cost'] == '1';
            $costValue = isset($this->request->post['cost_value']) ? (float)$this->request->post['cost_value'] : 0;

            $result = $this->model_catalog_product->updateProductStock($products, $branchId, $unitId, $updateType, $value, $reason, $updateCost, $costValue);

            if ($result) {
                $json['success'] = $this->language->get('text_success_operation');
                $json['updated_count'] = $result;
            } else {
                $json['error'] = $this->language->get('error_update_failed');
            }
        } else {
            $json['error'] = $this->language->get('error_missing_data');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function bulkCostUpdate() {
        $this->load->language('catalog/product');
        $this->load->model('catalog/product');

        $json = array();

        if (!$this->user->hasPermission('modify', 'catalog/product')) {
            $json['error'] = $this->language->get('error_permission');
        } elseif (isset($this->request->post['products']) && isset($this->request->post['update_type']) && isset($this->request->post['value']) && isset($this->request->post['unit_id'])) {
            $products = $this->request->post['products'];
            $updateType = $this->request->post['update_type'];
            $value = (float)$this->request->post['value'];
            $unitId = $this->request->post['unit_id'];
            $updatePrices = isset($this->request->post['update_prices']) && $this->request->post['update_prices'] == '1';
            $marginPercentage = isset($this->request->post['margin_percentage']) ? (float)$this->request->post['margin_percentage'] : 0;
            $note = isset($this->request->post['note']) ? $this->request->post['note'] : $this->language->get('text_bulk_update');

            $result = $this->model_catalog_product->updateProductCost($products, $updateType, $value, $unitId, $updatePrices, $marginPercentage, $note);

            if ($result) {
                $json['success'] = $this->language->get('text_success_operation');
                $json['updated_count'] = $result;
            } else {
                $json['error'] = $this->language->get('error_update_failed');
            }
        } else {
            $json['error'] = $this->language->get('error_missing_data');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function bulkStatusUpdate() {
        $this->load->language('catalog/product');
        $this->load->model('catalog/product');

        $json = array();

        if (!$this->user->hasPermission('modify', 'catalog/product')) {
            $json['error'] = $this->language->get('error_permission');
        } elseif (isset($this->request->post['products']) && isset($this->request->post['status'])) {
            $products = $this->request->post['products'];
            $status = (int)$this->request->post['status'];

            $result = $this->model_catalog_product->updateProductStatus($products, $status);

            if ($result) {
                $json['success'] = $this->language->get('text_success_operation');
                $json['updated_count'] = $result;
            } else {
                $json['error'] = $this->language->get('error_update_failed');
            }
        } else {
            $json['error'] = $this->language->get('error_missing_data');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function printBarcodes() {
        $this->load->language('catalog/product');
        $this->load->model('catalog/product');

        if (!isset($this->request->get['product_ids'])) {
            $this->response->redirect($this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'], true));
            return;
        }

        $data = array(
            'type' => isset($this->request->get['type']) ? $this->request->get['type'] : 'CODE128',
            'format' => isset($this->request->get['format']) ? $this->request->get['format'] : 'individual',
            'quantity' => isset($this->request->get['quantity']) ? (int)$this->request->get['quantity'] : 1,
            'unit_id' => isset($this->request->get['unit_id']) ? $this->request->get['unit_id'] : 'all',
            'include_price' => isset($this->request->get['include_price']) && $this->request->get['include_price'] == '1',
            'page_size' => isset($this->request->get['page_size']) ? $this->request->get['page_size'] : 'A4',
            'product_ids' => $this->request->get['product_ids']
        );

        $products = array();
        foreach ($data['product_ids'] as $product_id) {
            $product_info = $this->model_catalog_product->getProduct($product_id);
            if ($product_info) {
                if ($data['unit_id'] == 'all') {
                    $units = $this->model_catalog_product->getProductUnits($product_id);
                } else {
                    $units = array($this->model_catalog_product->getProductUnit($product_id, $data['unit_id']));
                }

                foreach ($units as $unit) {
                    $barcode = $this->model_catalog_product->getProductBarcodeByUnit($product_id, $unit['unit_id']);

                    if (!$barcode) {
                        $barcode = $product_id . str_pad($unit['unit_id'], 3, '0', STR_PAD_LEFT);
                    }

                    $price = '';
                    if ($data['include_price']) {
                        $price_info = $this->model_catalog_product->getProductPriceByUnit($product_id, $unit['unit_id']);
                        if ($price_info) {
                            $price = $this->currency->format($price_info['base_price'], $this->config->get('config_currency'));
                        }
                    }

                    for ($i = 0; $i < $data['quantity']; $i++) {
                        $products[] = array(
                            'product_id' => $product_id,
                            'name' => $product_info['name'],
                            'unit_name' => $unit['unit_name'],
                            'barcode' => $barcode,
                            'price' => $price
                        );
                    }
                }
            }
        }

        $data['products'] = $products;
        $data['store_name'] = $this->config->get('config_name');

        $this->document->setTitle($this->language->get('text_print_barcodes'));

        $data['header'] = $this->load->controller('common/header');
        $data['footer'] = $this->load->controller('common/footer');

        if ($data['format'] == 'sheet') {
            $this->response->setOutput($this->load->view('catalog/product_barcode_sheet', $data));
        } else {
            $this->response->setOutput($this->load->view('catalog/product_barcode_individual', $data));
        }
    }

    public function createInventoryCountSheet() {
        $this->load->language('catalog/product');
        $this->load->model('catalog/product');

        $json = array();

        if (!$this->user->hasPermission('modify', 'catalog/product')) {
            $json['error'] = $this->language->get('error_permission');
        } elseif (!isset($this->request->post['branch_id'])) {
            $json['error'] = $this->language->get('error_branch_required');
        } else {
            $filter_data = array(
                'branch_id' => $this->request->post['branch_id'],
                'filter_category_id' => isset($this->request->post['filter_category_id']) ? $this->request->post['filter_category_id'] : null,
                'filter_name' => isset($this->request->post['filter_name']) ? $this->request->post['filter_name'] : null,
                'count_type' => isset($this->request->post['count_type']) ? $this->request->post['count_type'] : 'full',
                'sheet_format' => isset($this->request->post['sheet_format']) ? $this->request->post['sheet_format'] : 'print',
                'notes' => isset($this->request->post['notes']) ? $this->request->post['notes'] : ''
            );

            $sheet_id = $this->model_catalog_product->createInventorySheet($filter_data);

            if ($sheet_id) {
                $json['success'] = $this->language->get('text_sheet_created');
                $json['sheet_id'] = $sheet_id;

                if ($filter_data['sheet_format'] == 'print') {
                    $json['redirect'] = $this->url->link('catalog/product/printCountSheet', 'user_token=' . $this->session->data['user_token'] . '&sheet_id=' . $sheet_id, true);
                } elseif ($filter_data['sheet_format'] == 'excel') {
                    $json['redirect'] = $this->url->link('catalog/product/exportCountSheet', 'user_token=' . $this->session->data['user_token'] . '&sheet_id=' . $sheet_id, true);
                } else {
                    $json['redirect'] = $this->url->link('inventory/count/sheet', 'user_token=' . $this->session->data['user_token'] . '&sheet_id=' . $sheet_id, true);
                }
            } else {
                $json['error'] = $this->language->get('error_sheet_creation');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function transferStock() {
        $this->load->language('catalog/product');
        $this->load->model('catalog/product');

        $json = array();

        if (!$this->user->hasPermission('modify', 'catalog/product')) {
            $json['error'] = $this->language->get('error_permission');
        } elseif (!isset($this->request->post['source_branch_id'])) {
            $json['error'] = $this->language->get('error_source_branch_required');
        } elseif (!isset($this->request->post['destination_branch_id'])) {
            $json['error'] = $this->language->get('error_destination_branch_required');
        } elseif ($this->request->post['source_branch_id'] == $this->request->post['destination_branch_id']) {
            $json['error'] = $this->language->get('error_same_branch');
        } elseif (empty($this->request->post['items']) || !is_array($this->request->post['items'])) {
            $json['error'] = $this->language->get('error_items_required');
        } else {
            $unavailable = array();
            foreach ($this->request->post['items'] as $item) {
                $inventory_check = $this->model_catalog_product->checkProductInventory(
                    $item['product_id'],
                    $item['unit_id'],
                    $this->request->post['source_branch_id'],
                    $item['quantity']
                );

                if (!$inventory_check['available']) {
                    $product_info = $this->model_catalog_product->getProduct($item['product_id']);
                    $unit_info = $this->model_catalog_product->getProductUnit($item['product_id'], $item['unit_id']);

                    $unavailable[] = array(
                        'product_id' => $item['product_id'],
                        'name' => $product_info['name'],
                        'unit_name' => $unit_info['unit_name'],
                        'available' => $inventory_check['quantity_available'],
                        'requested' => $item['quantity']
                    );
                }
            }

            if (!empty($unavailable)) {
                $json['error'] = $this->language->get('error_insufficient_stock');
                $json['unavailable_items'] = $unavailable;
            } else {
                $transfer_data = array(
                    'transfer_number' => 'TRN-' . date('YmdHis'),
                    'source_branch_id' => $this->request->post['source_branch_id'],
                    'destination_branch_id' => $this->request->post['destination_branch_id'],
                    'transfer_date' => date('Y-m-d'),
                    'notes' => isset($this->request->post['notes']) ? $this->request->post['notes'] : '',
                    'status' => 'completed',
                    'items' => $this->request->post['items'],
                    'created_by' => $this->user->getId()
                );

                $transfer_id = $this->model_catalog_product->transferInventory($transfer_data);

                if ($transfer_id) {
                    $json['success'] = $this->language->get('text_transfer_added');
                    $json['transfer_id'] = $transfer_id;
                } else {
                    $json['error'] = $this->language->get('error_transfer_failed');
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getProductInventory() {
        $this->load->language('catalog/product');
        $this->load->model('catalog/product');

        $json = array();

        if (isset($this->request->get['product_id'])) {
            $product_id = (int)$this->request->get['product_id'];
            $branch_id = isset($this->request->post['branch_id']) ? (int)$this->request->post['branch_id'] : null;

            $inventory = $this->model_catalog_product->getProductInventoryDetailed($product_id, $branch_id);

            $json['inventory'] = array();
            foreach ($inventory as $item) {
                $json['inventory'][] = array(
                    'branch_id' => $item['branch_id'],
                    'branch_name' => $item['branch_name'],
                    'unit_id' => $item['unit_id'],
                    'unit_name' => $item['unit_name'],
                    'quantity' => $item['quantity'],
                    'quantity_available' => $item['quantity_available'],
                    'average_cost' => $item['average_cost'],
                    'total_value' => $item['quantity'] * $item['average_cost']
                );
            }
        } else {
            $json['error'] = $this->language->get('error_product_id');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getInventoryMovements() {
        $this->load->language('catalog/product');

        $json = array();

        if (isset($this->request->get['product_id'])) {
            $this->load->model('catalog/product');
$filter_data = array(
                'filter_product_id' => $this->request->get['product_id']
            );

            if (isset($this->request->post['type'])) {
                $filter_data['filter_type'] = $this->request->post['type'];
            }

            if (isset($this->request->post['branch_id'])) {
                $filter_data['filter_branch_id'] = $this->request->post['branch_id'];
            }

            if (isset($this->request->post['date_from'])) {
                $filter_data['filter_date_start'] = $this->request->post['date_from'];
            }

            if (isset($this->request->post['date_to'])) {
                $filter_data['filter_date_end'] = $this->request->post['date_to'];
            }

            if (isset($this->request->post['page'])) {
                $page = $this->request->post['page'];
            } else {
                $page = 1;
            }

            $limit = 10;
            $filter_data['start'] = ($page - 1) * $limit;
            $filter_data['limit'] = $limit;

            $results = $this->model_catalog_product->getInventoryMovementsDetailed($filter_data);

            $movement_total = $this->model_catalog_product->getTotalInventoryMovements($filter_data);

            $json['movements'] = array();

            $total_incoming = 0;
            $total_outgoing = 0;
            $movement_by_month = array();

            foreach ($results as $result) {
                $movement_class = '';
                $quantity = (float)$result['quantity'];

                if (in_array($result['type'], array('purchase', 'adjustment_increase', 'transfer_in'))) {
                    $movement_class = 'movement-receipt';
                    $total_incoming += $quantity;
                } elseif (in_array($result['type'], array('sale', 'adjustment_decrease', 'transfer_out'))) {
                    $movement_class = 'movement-sale';
                    $total_outgoing += $quantity;
                }

                $month = date('Y-m', strtotime($result['date_added']));
                if (!isset($movement_by_month[$month])) {
                    $movement_by_month[$month] = array(
                        'in' => 0,
                        'out' => 0
                    );
                }

                if (in_array($result['type'], array('purchase', 'adjustment_increase', 'transfer_in'))) {
                    $movement_by_month[$month]['in'] += $quantity;
                } else {
                    $movement_by_month[$month]['out'] += $quantity;
                }

                $json['movements'][] = array(
                    'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                    'type' => $this->getMovementTypeText($result['type']),
                    'quantity' => $quantity,
                    'unit_name' => $result['unit_name'],
                    'branch_name' => $result['branch_name'],
                    'reference' => $result['reference'],
                    'user_name' => $result['user_name'],
                    'cost' => $this->currency->format($result['unit_cost'], $this->config->get('config_currency')),
                    'new_average_cost' => $this->currency->format($result['new_average_cost'], $this->config->get('config_currency')),
                    'css_class' => $movement_class
                );
            }

            $json['stats'] = array(
                'total_incoming' => $total_incoming,
                'total_outgoing' => $total_outgoing,
                'movement_by_month' => $movement_by_month
            );

            $pagination = new Pagination();
            $pagination->total = $movement_total;
            $pagination->page = $page;
            $pagination->limit = $limit;
            $pagination->url = 'javascript:loadMovementsHistory({page})';

            $json['pagination'] = $pagination->render();
            $json['total'] = $movement_total;
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getCostHistory() {
        $this->load->language('catalog/product');
        $json = array();

        if (isset($this->request->get['product_id'])) {
            $this->load->model('catalog/product');
            $product_id = (int)$this->request->get['product_id'];

            $history = $this->model_catalog_product->getProductCostHistory($product_id);

            $json['history'] = array();
            foreach ($history as $item) {
                $json['history'][] = array(
                    'date_added' => date($this->language->get('date_format_short'), strtotime($item['date_added'])),
                    'unit_name' => $item['unit_name'],
                    'old_cost' => $this->currency->format($item['old_cost'], $this->config->get('config_currency')),
                    'new_cost' => $this->currency->format($item['new_cost'], $this->config->get('config_currency')),
                    'change_reason' => $this->getCostChangeReasonText($item['change_reason']),
                    'notes' => $item['notes'],
                    'user_name' => $item['user_name']
                );
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    private function getCostChangeReasonText($reason) {
        $this->load->language('catalog/product');

        $reasons = array(
            'purchase' => $this->language->get('text_reason_purchase'),
            'manual' => $this->language->get('text_reason_manual'),
            'adjustment' => $this->language->get('text_reason_adjustment'),
            'transfer' => $this->language->get('text_reason_transfer')
        );

        return isset($reasons[$reason]) ? $reasons[$reason] : $reason;
    }

    public function getPriceHistory() {
        $this->load->language('catalog/product');
        $json = array();

        if (isset($this->request->get['product_id'])) {
            $this->load->model('catalog/product');
            $product_id = (int)$this->request->get['product_id'];

            $history = $this->model_catalog_product->getProductPriceHistory($product_id);

            $json['history'] = array();
            foreach ($history as $item) {
                $json['history'][] = array(
                    'change_date' => date($this->language->get('date_format_short'), strtotime($item['change_date'])),
                    'unit_name' => $item['unit_name'],
                    'price_type' => $item['price_type'],
                    'old_price' => $item['old_price'],
                    'new_price' => $item['new_price'],
                    'change_type' => $item['change_type'],
                    'user_name' => $item['user_name']
                );
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function exportExcel() {
        $this->load->language('catalog/product');
        $this->load->model('catalog/product');

        $product_ids = array();
        if (isset($this->request->get['product_ids']) && is_array($this->request->get['product_ids'])) {
            $product_ids = array_map('intval', $this->request->get['product_ids']);
        }

        if (empty($product_ids)) {
            $filter_data = array(
                'filter_name'         => isset($this->request->get['filter_name']) ? $this->request->get['filter_name'] : '',
                'filter_model'        => isset($this->request->get['filter_model']) ? $this->request->get['filter_model'] : '',
                'filter_unit'         => isset($this->request->get['filter_unit']) ? $this->request->get['filter_unit'] : '',
                'filter_has_image'    => isset($this->request->get['filter_has_image']) ? $this->request->get['filter_has_image'] : '',
                'filter_quantity_min' => isset($this->request->get['filter_quantity_min']) ? $this->request->get['filter_quantity_min'] : '',
                'filter_quantity_max' => isset($this->request->get['filter_quantity_max']) ? $this->request->get['filter_quantity_max'] : '',
                'filter_price'        => isset($this->request->get['filter_price']) ? $this->request->get['filter_price'] : '',
                'filter_status'       => isset($this->request->get['filter_status']) ? $this->request->get['filter_status'] : '',
                'sort'                => isset($this->request->get['sort']) ? $this->request->get['sort'] : 'pd.name',
                'order'               => isset($this->request->get['order']) ? $this->request->get['order'] : 'ASC',
                'start'               => 0,
                'limit'               => 2000
            );

            $results = $this->model_catalog_product->getProducts($filter_data);

            foreach ($results as $result) {
                $product_ids[] = $result['product_id'];
            }
        }

        if (!empty($product_ids)) {
            require_once(DIR_SYSTEM . 'library/PHPExcel/Classes/PHPExcel.php');

            $objPHPExcel = new PHPExcel();
            $objPHPExcel->getProperties()
                ->setCreator($this->config->get('config_name'))
                ->setLastModifiedBy($this->user->getUserName())
                ->setTitle($this->language->get('text_product_export'))
                ->setSubject($this->language->get('text_product_list'))
                ->setDescription($this->language->get('text_export_generated') . ' ' . date('Y-m-d H:i:s'))
                ->setKeywords($this->language->get('text_products'))
                ->setCategory($this->language->get('heading_title'));

            $objPHPExcel->setActiveSheetIndex(0);
            $sheet = $objPHPExcel->getActiveSheet();
            $sheet->setTitle($this->language->get('text_products'));

            $headers = array(
                'A' => 'ID',
                'B' => $this->language->get('column_image'),
                'C' => $this->language->get('column_name'),
                'D' => $this->language->get('column_model'),
                'E' => $this->language->get('entry_unit'),
                'F' => $this->language->get('column_stock'),
                'G' => $this->language->get('column_stock_online'),
                'H' => $this->language->get('column_base_price'),
                'I' => $this->language->get('column_special_price'),
                'J' => $this->language->get('column_wholesale_price'),
                'K' => $this->language->get('column_half_wholesale_price'),
                'L' => $this->language->get('column_average_cost'),
                'M' => $this->language->get('column_status')
            );

            $headerStyle = array(
                'font' => array(
                    'bold' => true,
                    'color' => array('rgb' => 'FFFFFF'),
                ),
                'fill' => array(
                    'type' => PHPExcel_Style_Fill::FILL_SOLID,
                    'color' => array('rgb' => '4472C4')
                ),
                'alignment' => array(
                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                )
            );

            foreach ($headers as $column => $header) {
                $sheet->setCellValue($column . '1', $header);
            }

            $sheet->getStyle('A1:M1')->applyFromArray($headerStyle);

            $row = 2;
            $chunk_size = 100;
            $total_products = count($product_ids);

            for ($i = 0; $i < $total_products; $i += $chunk_size) {
                $chunk = array_slice($product_ids, $i, $chunk_size);

                foreach ($chunk as $product_id) {
                    $product_info = $this->model_catalog_product->getProduct($product_id);

                    if ($product_info) {
                        $units = $this->model_catalog_product->getProductUnits($product_id);

                        foreach ($units as $unit) {
                            $inventory = $this->model_catalog_product->getProductInventoryByUnit($product_id, $unit['unit_id']);
                            $pricing = $this->model_catalog_product->getProductPriceByUnit($product_id, $unit['unit_id']);

                            $quantity = 0;
                            $quantity_available = 0;
                            $average_cost = 0;

                            if (is_array($inventory)) {
                                foreach ($inventory as $inv) {
                                    $quantity += $inv['quantity'];
                                    $quantity_available += $inv['quantity_available'];
                                    $average_cost = ($average_cost * $quantity + $inv['average_cost'] * $inv['quantity']) /
                                                    ($quantity > 0 ? $quantity : 1);
                                }
                            } else if (is_array($inventory) && isset($inventory['quantity'])) {
                                $quantity = $inventory['quantity'];
                                $quantity_available = $inventory['quantity_available'];
                                $average_cost = $inventory['average_cost'];
                            }

                            $base_price = isset($pricing['base_price']) ? $pricing['base_price'] : 0;
                            $special_price = isset($pricing['special_price']) ? $pricing['special_price'] : 0;
                            $wholesale_price = isset($pricing['wholesale_price']) ? $pricing['wholesale_price'] : 0;
                            $half_wholesale_price = isset($pricing['half_wholesale_price']) ? $pricing['half_wholesale_price'] : 0;

                            $sheet->setCellValue('A' . $row, $product_id);
                            $sheet->setCellValue('B' . $row, '');
                            $sheet->setCellValue('C' . $row, $product_info['name']);
                            $sheet->setCellValue('D' . $row, $product_info['model']);
                            $sheet->setCellValue('E' . $row, $unit['unit_name']);
                            $sheet->setCellValue('F' . $row, $quantity);
                            $sheet->setCellValue('G' . $row, $quantity_available);
                            $sheet->setCellValue('H' . $row, $base_price);
                            $sheet->setCellValue('I' . $row, $special_price);
                            $sheet->setCellValue('J' . $row, $wholesale_price);
                            $sheet->setCellValue('K' . $row, $half_wholesale_price);
                            $sheet->setCellValue('L' . $row, $average_cost);
                            $sheet->setCellValue('M' . $row, $product_info['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'));

                            $sheet->getStyle('F' . $row . ':L' . $row)
                                ->getNumberFormat()
                                ->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

                            if ($quantity < 5) {
                                $sheet->getStyle('F' . $row)->applyFromArray([
                                    'font' => ['color' => ['rgb' => 'FF0000']]
                                ]);
                            }

                            $row++;
                        }
                    }
                }

                gc_collect_cycles();
            }

            foreach (range('A', 'M') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }

            $sheet->freezePane('A2');

            $fileName = 'products_export_' . date('Y-m-d_H-i-s') . '.xlsx';

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $fileName . '"');
            header('Cache-Control: max-age=0');
            header('Pragma: public');

            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
            $objWriter->save('php://output');
            exit;
        } else {
            $this->session->data['error'] = $this->language->get('error_no_products_to_export');
            $this->response->redirect($this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'], true));
        }
    }

    public function exportPdf() {
        $this->load->language('catalog/product');
        $this->load->model('catalog/product');

        if (isset($this->request->get['product_ids'])) {
            $product_ids = $this->request->get['product_ids'];
        } else {
            $product_ids = array();
        }

        if (empty($product_ids)) {
            $filter_data = array();

            if (isset($this->request->get['filter_name'])) {
                $filter_data['filter_name'] = $this->request->get['filter_name'];
            }

            if (isset($this->request->get['filter_model'])) {
                $filter_data['filter_model'] = $this->request->get['filter_model'];
            }

            if (isset($this->request->get['filter_unit'])) {
                $filter_data['filter_unit'] = $this->request->get['filter_unit'];
            }

            if (isset($this->request->get['filter_has_image'])) {
                $filter_data['filter_has_image'] = $this->request->get['filter_has_image'];
            }

            if (isset($this->request->get['filter_quantity_min'])) {
                $filter_data['filter_quantity_min'] = $this->request->get['filter_quantity_min'];
            }

            if (isset($this->request->get['filter_quantity_max'])) {
                $filter_data['filter_quantity_max'] = $this->request->get['filter_quantity_max'];
            }

            if (isset($this->request->get['filter_price'])) {
                $filter_data['filter_price'] = $this->request->get['filter_price'];
            }

            if (isset($this->request->get['filter_status'])) {
                $filter_data['filter_status'] = $this->request->get['filter_status'];
            }

            $results = $this->model_catalog_product->getProducts($filter_data);

            foreach ($results as $result) {
                $product_ids[] = $result['product_id'];
            }
        }

        $products = array();
        foreach ($product_ids as $product_id) {
            $product_info = $this->model_catalog_product->getProduct($product_id);

            if ($product_info) {
                $units = $this->model_catalog_product->getProductUnits($product_id);
                $product_units = array();

                foreach ($units as $unit) {
                    $inventory = $this->model_catalog_product->getProductInventoryByUnit($product_id, $unit['unit_id']);
                    $pricing = $this->model_catalog_product->getProductPriceByUnit($product_id, $unit['unit_id']);

                    $product_units[] = array(
                        'unit_name' => $unit['unit_name'],
                        'quantity' => isset($inventory['quantity']) ? $inventory['quantity'] : 0,
                        'base_price' => isset($pricing['base_price']) ? $pricing['base_price'] : 0,
                        'special_price' => isset($pricing['special_price']) ? $pricing['special_price'] : 0,
                        'wholesale_price' => isset($pricing['wholesale_price']) ? $pricing['wholesale_price'] : 0,
                        'half_wholesale_price' => isset($pricing['half_wholesale_price']) ? $pricing['half_wholesale_price'] : 0,
                        'average_cost' => isset($inventory['average_cost']) ? $inventory['average_cost'] : 0
                    );
                }

                $products[] = array(
                    'product_id' => $product_id,
                    'name' => $product_info['name'],
                    'model' => $product_info['model'],
                    'units' => $product_units,
                    'status' => $product_info['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled')
                );
            }
        }

        if (!empty($products)) {
            require_once(DIR_SYSTEM . 'library/tcpdf/tcpdf.php');

            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor($this->config->get('config_name'));
            $pdf->SetTitle($this->language->get('text_products_report'));
            $pdf->SetSubject($this->language->get('text_products_report'));

            $pdf->setPrintHeader(true);
            $pdf->setPrintFooter(true);

            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

            $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
            $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
            $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

            $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

            $pdf->AddPage();

            $pdf->SetFont('dejavusans', '', 10);

            $pdf->Cell(0, 10, $this->language->get('text_products_report'), 0, 1, 'C');
            $pdf->Ln(10);

            $headers = array(
                $this->language->get('column_name'),
                $this->language->get('column_model'),
                $this->language->get('entry_unit'),
                $this->language->get('column_stock'),
                $this->language->get('column_base_price'),
                $this->language->get('column_average_cost')
            );

            $html = '<table border="1" cellpadding="5">';
            $html .= '<tr>';
            foreach ($headers as $header) {
                $html .= '<th style="font-weight:bold;">' . $header . '</th>';
            }
            $html .= '</tr>';

            foreach ($products as $product) {
                foreach ($product['units'] as $unit) {
                    $html .= '<tr>';
                    $html .= '<td>' . $product['name'] . '</td>';
                    $html .= '<td>' . $product['model'] . '</td>';
                    $html .= '<td>' . $unit['unit_name'] . '</td>';
                    $html .= '<td>' . $unit['quantity'] . '</td>';
                    $html .= '<td>' . $this->currency->format($unit['base_price'], $this->config->get('config_currency')) . '</td>';
                    $html .= '<td>' . $this->currency->format($unit['average_cost'], $this->config->get('config_currency')) . '</td>';
                    $html .= '</tr>';
                }
            }

            $html .= '</table>';

            $pdf->writeHTML($html, true, false, true, false, '');

            $pdf->Output('products_report_' . date('Y-m-d_H-i-s') . '.pdf', 'I');
            exit;
        } else {
            $this->response->redirect($this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'], true));
        }
    }

    public function getProductUnits() {
        $this->load->language('catalog/product');
        $json = array();

        if (isset($this->request->get['product_id'])) {
            $product_id = (int)$this->request->get['product_id'];
        } else {
            $product_id = 0;
        }

        $this->load->model('catalog/product');

        $units = $this->model_catalog_product->getProductUnits($product_id);

        if ($units) {
            foreach ($units as $unit) {
                $json[] = array(
                    'unit_id' => $unit['unit_id'],
                    'unit_name' => $unit['unit_name'],
                );
            }
        } else {
            $json['error'] = $this->language->get('error_units');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getProducts() {
        $json = array();

        $this->load->model('catalog/product');

        if (isset($this->request->get['filter_name'])) {
            $filter_name = $this->request->get['filter_name'];
        } else {
            $filter_name = '';
        }

        $filter_data = array(
            'filter_name' => $filter_name,
            'start'       => 0,
            'limit'       => 10
        );

        $results = $this->model_catalog_product->getProducts($filter_data);

        foreach ($results as $result) {
            $json[] = array(
                'product_id' => $result['product_id'],
                'product'  => $this->loadProductData($result['product_id']),
                'name'       => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8'))
            );
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function autocompletefull() {
        $json = array();

        if (isset($this->request->get['filter_name']) || isset($this->request->get['filter_model'])) {
            $this->load->model('catalog/product');

            if (isset($this->request->get['filter_name'])) {
                $filter_name = '%'.$this->request->get['filter_name'].'%';
            } else {
                $filter_name = '';
            }

            if (isset($this->request->get['filter_model'])) {
                $filter_model = $this->request->get['filter_model'];
            } else {
                $filter_model = '';
            }

            if (isset($this->request->get['limit'])) {
                $limit = $this->request->get['limit'];
            } else {
                $limit = 5;
            }

            $filter_data = array(
                'filter_name'  => $filter_name,
                'filter_model' => $filter_model,
                'start'        => 0,
                'limit'        => $limit
            );

            $results = $this->model_catalog_product->getProducts($filter_data);

            foreach ($results as $result) {
                $json[] = array(
                    'product_id' => $result['product_id'],
                    'product'  => $this->loadProductData($result['product_id']),
                    'name'       => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8')),
                    'model'      => $result['model']
                );
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getProductOrders() {
        $this->load->language('catalog/product');

        $json = array();

        if (isset($this->request->get['product_id'])) {
            $this->load->model('catalog/product');

            $filter_data = array(
                'filter_product_id' => $this->request->get['product_id'],
                'start'             => 0,
                'limit'             => 10
            );

            $results = $this->model_catalog_product->getOrdersWithProduct($filter_data);

            foreach ($results as $result) {
                $json['orders'][] = array(
                    'order_id'   => $result['order_id'],
                    'customer'   => $result['customer'],
                    'status'     => $result['status'],
                    'total'      => $this->currency->format($result['total'], $result['currency_code'], $result['currency_value']),
                    'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                    'view'       => $this->url->link('sale/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $result['order_id'], true)
                );
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function index() {
        $this->load->language('catalog/product');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('catalog/product');
        $this->getList();
    }

    public function add() {
        $this->load->language('catalog/product');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('catalog/product');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            // Procesar los datos del producto
            $product_id = $this->model_catalog_product->addProduct($this->request->post);

            // Procesar los datos de los bundles
            if (isset($this->request->post['product_bundle'])) {
                foreach ($this->request->post['product_bundle'] as $bundle) {
                    $this->model_catalog_product->addProductBundle($product_id, $bundle);
                }
            }

            // Procesar los datos de los descuentos
            if (isset($this->request->post['product_discount'])) {
                foreach ($this->request->post['product_discount'] as $discount) {
                    $this->model_catalog_product->addProductQuantityDiscount($product_id, $discount);
                }
            }

            $this->session->data['success'] = $this->language->get('text_success');
            $url = '';

            if (isset($this->request->get['filter_name'])) {
                $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['filter_model'])) {
                $url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
            }

if (isset($this->request->get['filter_unit'])) {
                $url .= '&filter_unit=' . urlencode(html_entity_decode($this->request->get['filter_unit'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['filter_has_image'])) {
                $url .= '&filter_has_image=' . $this->request->get['filter_has_image'];
            }

            if (isset($this->request->get['filter_quantity_min'])) {
                $url .= '&filter_quantity_min=' . $this->request->get['filter_quantity_min'];
            }
            if (isset($this->request->get['filter_quantity_max'])) {
                $url .= '&filter_quantity_max=' . $this->request->get['filter_quantity_max'];
            }

            if (isset($this->request->get['filter_price'])) {
                $url .= '&filter_price=' . $this->request->get['filter_price'];
            }

            if (isset($this->request->get['filter_quantity'])) {
                $url .= '&filter_quantity=' . $this->request->get['filter_quantity'];
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

            $this->response->redirect($this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    public function update_categories() {
        $this->load->model('catalog/product');

        if (isset($this->request->post['products']) && isset($this->request->post['categories'])) {
            $products = $this->request->post['products'];
            $categories = $this->request->post['categories'];

            foreach ($products as $product_id) {
                $this->model_catalog_product->updateProductCategories($product_id, $categories);
            }

            $this->response->setOutput(json_encode(['success' => 'Categories updated successfully.']));
        }
    }

    public function edit() {
        $this->load->language('catalog/product');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('catalog/product');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            // Procesar los datos del producto
            $this->model_catalog_product->editProduct($this->request->get['product_id'], $this->request->post);

            // Procesar los datos de los bundles
            if (isset($this->request->post['product_bundle'])) {
                // Eliminar bundles existentes
                $this->db->query("DELETE FROM " . DB_PREFIX . "product_bundle WHERE product_id = '" . (int)$this->request->get['product_id'] . "'");
                $this->db->query("DELETE FROM " . DB_PREFIX . "product_bundle_item WHERE bundle_id IN (SELECT bundle_id FROM " . DB_PREFIX . "product_bundle WHERE product_id = '" . (int)$this->request->get['product_id'] . "')");

                // Agregar nuevos bundles
                foreach ($this->request->post['product_bundle'] as $bundle) {
                    $this->model_catalog_product->addProductBundle($this->request->get['product_id'], $bundle);
                }
            }

            // Procesar los datos de los descuentos
            if (isset($this->request->post['product_discount'])) {
                // Eliminar descuentos existentes
                $this->db->query("DELETE FROM " . DB_PREFIX . "product_quantity_discounts WHERE product_id = '" . (int)$this->request->get['product_id'] . "'");

                // Agregar nuevos descuentos
                foreach ($this->request->post['product_discount'] as $discount) {
                    $this->model_catalog_product->addProductQuantityDiscount($this->request->get['product_id'], $discount);
                }
            }

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            if (isset($this->request->get['filter_name'])) {
                $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['filter_model'])) {
                $url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
            }
            if (isset($this->request->get['filter_unit'])) {
                $url .= '&filter_unit=' . urlencode(html_entity_decode($this->request->get['filter_unit'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['filter_has_image'])) {
                $url .= '&filter_has_image=' . $this->request->get['filter_has_image'];
            }

            if (isset($this->request->get['filter_quantity_min'])) {
                $url .= '&filter_quantity_min=' . $this->request->get['filter_quantity_min'];
            }
            if (isset($this->request->get['filter_quantity_max'])) {
                $url .= '&filter_quantity_max=' . $this->request->get['filter_quantity_max'];
            }
            if (isset($this->request->get['filter_price'])) {
                $url .= '&filter_price=' . $this->request->get['filter_price'];
            }

            if (isset($this->request->get['filter_quantity'])) {
                $url .= '&filter_quantity=' . $this->request->get['filter_quantity'];
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

            $this->response->redirect($this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getForm();
    }

    public function delete() {
        $this->load->language('catalog/product');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('catalog/product');

        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ($this->request->post['selected'] as $product_id) {
                $this->model_catalog_product->deleteProduct($product_id);
            }

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            if (isset($this->request->get['filter_name'])) {
                $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['filter_model'])) {
                $url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
            }
            if (isset($this->request->get['filter_unit'])) {
                $url .= '&filter_unit=' . urlencode(html_entity_decode($this->request->get['filter_unit'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['filter_has_image'])) {
                $url .= '&filter_has_image=' . $this->request->get['filter_has_image'];
            }

            if (isset($this->request->get['filter_quantity_min'])) {
                $url .= '&filter_quantity_min=' . $this->request->get['filter_quantity_min'];
            }
            if (isset($this->request->get['filter_quantity_max'])) {
                $url .= '&filter_quantity_max=' . $this->request->get['filter_quantity_max'];
            }
            if (isset($this->request->get['filter_price'])) {
                $url .= '&filter_price=' . $this->request->get['filter_price'];
            }

            if (isset($this->request->get['filter_quantity'])) {
                $url .= '&filter_quantity=' . $this->request->get['filter_quantity'];
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

            $this->response->redirect($this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getList();
    }

    public function copy() {
        $this->load->language('catalog/product');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('catalog/product');

        if (isset($this->request->post['selected']) && $this->validateCopy()) {
            foreach ($this->request->post['selected'] as $product_id) {
                $this->model_catalog_product->copyProduct($product_id);
            }

            $this->session->data['success'] = $this->language->get('text_success');

            $url = '';

            if (isset($this->request->get['filter_name'])) {
                $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['filter_model'])) {
                $url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
            }
            if (isset($this->request->get['filter_unit'])) {
                $url .= '&filter_unit=' . urlencode(html_entity_decode($this->request->get['filter_unit'], ENT_QUOTES, 'UTF-8'));
            }

            if (isset($this->request->get['filter_has_image'])) {
                $url .= '&filter_has_image=' . $this->request->get['filter_has_image'];
            }

            if (isset($this->request->get['filter_quantity_min'])) {
                $url .= '&filter_quantity_min=' . $this->request->get['filter_quantity_min'];
            }
            if (isset($this->request->get['filter_quantity_max'])) {
                $url .= '&filter_quantity_max=' . $this->request->get['filter_quantity_max'];
            }
            if (isset($this->request->get['filter_price'])) {
                $url .= '&filter_price=' . $this->request->get['filter_price'];
            }

            if (isset($this->request->get['filter_quantity'])) {
                $url .= '&filter_quantity=' . $this->request->get['filter_quantity'];
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

            $this->response->redirect($this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }

        $this->getList();
    }

    protected function getList() {
        if (isset($this->request->get['filter_category'])) {
            $filter_category = $this->request->get['filter_category'];
        } else {
            $filter_category = '';
        }
        if (isset($this->request->get['filter_name'])) {
            $filter_name = $this->request->get['filter_name'];
        } else {
            $filter_name = '';
        }

        if (isset($this->request->get['filter_unit'])) {
            $filter_unit = $this->request->get['filter_unit'];
        } else {
            $filter_unit = '';
        }

        if (isset($this->request->get['filter_quantity_min'])) {
            $filter_quantity_min = $this->request->get['filter_quantity_min'];
        } else {
            $filter_quantity_min = null;
        }

        if (isset($this->request->get['filter_quantity_max'])) {
            $filter_quantity_max = $this->request->get['filter_quantity_max'];
        } else {
            $filter_quantity_max = null;
        }

        if (isset($this->request->get['filter_has_image'])) {
            $filter_has_image = $this->request->get['filter_has_image'];
        } else {
            $filter_has_image = '';
        }

        if (isset($this->request->get['filter_model'])) {
            $filter_model = $this->request->get['filter_model'];
        } else {
            $filter_model = '';
        }

        if (isset($this->request->get['filter_price'])) {
            $filter_price = $this->request->get['filter_price'];
        } else {
            $filter_price = '';
        }

        if (isset($this->request->get['filter_quantity'])) {
            $filter_quantity = $this->request->get['filter_quantity'];
        } else {
            $filter_quantity = '';
        }

        if (isset($this->request->get['filter_status'])) {
            $filter_status = $this->request->get['filter_status'];
        } else {
            $filter_status = '';
        }

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'p.product_id';
        }

        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'DESC';
        }

        if (isset($this->request->get['page'])) {
            $page = (int)$this->request->get['page'];
        } else {
            $page = 1;
        }

        $url = '';
        if (isset($this->request->get['filter_category'])) {
            $url .= '&filter_category=' . $this->request->get['filter_category'];
        }
        if (isset($this->request->get['filter_name'])) {
            $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_model'])) {
            $url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
        }
        if (isset($this->request->get['filter_unit'])) {
            $url .= '&filter_unit=' . urlencode(html_entity_decode($this->request->get['filter_unit'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_has_image'])) {
            $url .= '&filter_has_image=' . $this->request->get['filter_has_image'];
        }

        if (isset($this->request->get['filter_quantity_min'])) {
            $url .= '&filter_quantity_min=' . $this->request->get['filter_quantity_min'];
        }
        if (isset($this->request->get['filter_quantity_max'])) {
            $url .= '&filter_quantity_max=' . $this->request->get['filter_quantity_max'];
        }
        if (isset($this->request->get['filter_price'])) {
            $url .= '&filter_price=' . $this->request->get['filter_price'];
        }

        if (isset($this->request->get['filter_quantity'])) {
            $url .= '&filter_quantity=' . $this->request->get['filter_quantity'];
        }

        if (isset($this->request->get['filter_status'])) {
            $url .= '&filter_status=' . $this->request->get['filter_status'];
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
            'href' => $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        $data['add'] = $this->url->link('catalog/product/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['copy'] = $this->url->link('catalog/product/copy', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['delete'] = $this->url->link('catalog/product/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['export_action'] = $this->url->link('extension/export_import/download', 'user_token=' . $this->session->data['user_token'], true);

        $data['branches'] = $this->model_catalog_product->getBranches();
        $data['products'] = array();

        $filter_data = array(
            'filter_name'       => $filter_name,
            'filter_model'      => $filter_model,
            'filter_category'   => $filter_category,
            'filter_price'      => $filter_price,
            'filter_quantity'   => $filter_quantity,
            'filter_status'     => $filter_status,
            'filter_unit'       => $filter_unit,
            'filter_quantity_min' => $filter_quantity_min,
            'filter_quantity_max' => $filter_quantity_max,
            'filter_has_image'  => $filter_has_image,
            'sort'              => $sort,
            'order'             => $order,
            'start'             => ($page - 1) * $this->config->get('config_limit_admin'),
            'limit'             => $this->config->get('config_limit_admin')
        );


        $this->load->model('tool/image');

        $product_total = $this->model_catalog_product->getTotalProducts($filter_data);

        $results = $this->model_catalog_product->getProducts($filter_data);
        foreach ($results as $result) {
            if (is_file(DIR_IMAGE . $result['image'])) {
                $image = $this->model_tool_image->resize($result['image'], 40, 40);
            } else {
                $image = $this->model_tool_image->resize('no_image.png', 40, 40);
            }

            $special = false;
            $product_specials = $this->model_catalog_product->getProductSpecials($result['product_id']);
            foreach ($product_specials as $product_special) {
                $special = $this->currency->format($product_special['price'], $this->config->get('config_currency')) . ' (' . $product_special['unit_name'] . ')';
                break;
            }

            $prices = $this->model_catalog_product->getProductPricing($result['product_id']);
            $product_units = $this->model_catalog_product->getProductUnits($result['product_id']);
            $product_inventory = $this->model_catalog_product->getProductInventory($result['product_id']);

            $units_data = array();

            foreach ($product_units as $unit) {
                $unit_id = $unit['unit_id'];
                $units_data[$unit_id] = array(
                    'unit_name' => $unit['unit_name'],
                    'quantity'  => 0,
                    'quantity_available' => 0
                );
            }

            foreach ($prices as $price) {
                $unit_id = $price['unit_id'];
                if (!isset($units_data[$unit_id])) {
                    $units_data[$unit_id] = array(
                        'unit_name' => $price['unit_name'],
                        'quantity'  => 0,
                        'quantity_available' => 0
                    );
                }
                $units_data[$unit_id]['base_price'] = $price['base_price'];
                $units_data[$unit_id]['special_price'] = $price['special_price'];
                $units_data[$unit_id]['wholesale_price'] = $price['wholesale_price'];
                $units_data[$unit_id]['half_wholesale_price'] = $price['half_wholesale_price'];
                $units_data[$unit_id]['custom_price'] = $price['custom_price'];
            }

            foreach ($product_inventory as $inventory) {
                $unit_id = $inventory['unit_id'];
                if (isset($units_data[$unit_id])) {
                    $units_data[$unit_id]['quantity'] += $inventory['quantity'];
                    $units_data[$unit_id]['quantity_available'] += $inventory['quantity_available'];
                }
            }

            $sorted_units = array_values($units_data);

            $data['products'][] = array(
                'product_id' => $result['product_id'],
                'image'      => $image,
                'name'       => $result['name'],
                'egs_code'   => $result['egs_code'],
                'model'      => $result['model'],
                'price'      => $this->currency->format($result['price'], $this->config->get('config_currency')),
                'special'    => $special,
                'sorted_units' => $sorted_units,
                'status'     => $result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
                'edit'       => $this->url->link('catalog/product/edit', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $result['product_id'] . $url, true)
            );
        }
        $data['units'] = $this->model_catalog_product->getUnits();

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

        if (isset($this->request->get['filter_model'])) {
            $url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_unit'])) {
            $url .= '&filter_unit=' . urlencode(html_entity_decode($this->request->get['filter_unit'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_has_image'])) {
            $url .= '&filter_has_image=' . $this->request->get['filter_has_image'];
        }

        if (isset($this->request->get['filter_quantity_min'])) {
            $url .= '&filter_quantity_min=' . $this->request->get['filter_quantity_min'];
        }
        if (isset($this->request->get['filter_quantity_max'])) {
            $url .= '&filter_quantity_max=' . $this->request->get['filter_quantity_max'];
        }

        if (isset($this->request->get['filter_price'])) {
            $url .= '&filter_price=' . $this->request->get['filter_price'];
        }

        if (isset($this->request->get['filter_quantity'])) {
            $url .= '&filter_quantity=' . $this->request->get['filter_quantity'];
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

        $data['sort_name'] = $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . '&sort=pd.name' . $url, true);
        $data['sort_product_id'] = $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . '&sort=p.product_id' . $url, true);
        $data['sort_model'] = $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . '&sort=p.model' . $url, true);
        $data['sort_price'] = $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . '&sort=p.price' . $url, true);
        $data['sort_quantity'] = $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . '&sort=p.quantity' . $url, true);
        $data['sort_status'] = $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . '&sort=p.status' . $url, true);
        $data['sort_order'] = $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . '&sort=p.sort_order' . $url, true);

        $url = '';

        if (isset($this->request->get['filter_name'])) {
            $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_model'])) {
            $url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_unit'])) {
            $url .= '&filter_unit=' . urlencode(html_entity_decode($this->request->get['filter_unit'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_has_image'])) {
            $url .= '&filter_has_image=' . $this->request->get['filter_has_image'];
        }

        if (isset($this->request->get['filter_quantity_min'])) {
            $url .= '&filter_quantity_min=' . $this->request->get['filter_quantity_min'];
        }
        if (isset($this->request->get['filter_quantity_max'])) {
            $url .= '&filter_quantity_max=' . $this->request->get['filter_quantity_max'];
        }

        if (isset($this->request->get['filter_price'])) {
            $url .= '&filter_price=' . $this->request->get['filter_price'];
        }

        if (isset($this->request->get['filter_quantity'])) {
            $url .= '&filter_quantity=' . $this->request->get['filter_quantity'];
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
        $pagination->total = $product_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_limit_admin');
        $pagination->url = $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($product_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($product_total - $this->config->get('config_limit_admin'))) ? $product_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $product_total, ceil($product_total / $this->config->get('config_limit_admin')));
        $data['filter_unit'] = $filter_unit;
        $data['filter_quantity_min'] = $filter_quantity_min;
        $data['filter_quantity_max'] = $filter_quantity_max;
        $data['filter_has_image'] = $filter_has_image;
        $data['filter_name'] = $filter_name;
        $data['filter_model'] = $filter_model;
        $data['filter_price'] = $filter_price;
        $data['filter_category'] = $filter_category;
        $data['filter_quantity'] = $filter_quantity;
$data['filter_status'] = $filter_status;
        $data['sort'] = $sort;
        $data['order'] = $order;
        $data['text_category'] = $this->language->get('text_category');
        $data['categories'] = array();
        $data['xcategories'] = array();
        $this->load->model('catalog/category');
        $cat_filter = array('sort' => 'name', 'order'=> 'ASC');
        $results = $this->model_catalog_category->getCategories($cat_filter);
        foreach ($results as $result) {
            $data['categories'][] = array(
                'category_id' => $result['category_id'],
                'name'        => $result['name'],
            );
        }
        foreach ($results as $result) {
            $data['xcategories'][] = array(
                'category_id' => $result['category_id'],
                'name'        => $result['name'],
            );
        }
        array_unshift($data['categories'], array('category_id' => 'no_cat', 'name' => 'Without category'));
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('catalog/product_list', $data));
    }

    protected function getForm() {
        // Cargar scripts necesarios para el formulario de productos
        $this->document->addScript('view/javascript/product/product.js');
        $this->document->addScript('view/javascript/product/UnitManager.js');
        $this->document->addScript('view/javascript/product/InventoryManager.js');
        $this->document->addScript('view/javascript/product/PricingManager.js');
        $this->document->addScript('view/javascript/product/BarcodeManager.js');
        $this->document->addScript('view/javascript/product/BundleManager.js');
        $this->document->addScript('view/javascript/product/RecommendationManager.js');

        $data['text_form'] = !isset($this->request->get['product_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['name'])) {
            $data['error_name'] = $this->error['name'];
        } else {
            $data['error_name'] = array();
        }

        if (isset($this->error['meta_title'])) {
            $data['error_meta_title'] = $this->error['meta_title'];
        } else {
            $data['error_meta_title'] = array();
        }

        if (isset($this->error['model'])) {
            $data['error_model'] = $this->error['model'];
        } else {
            $data['error_model'] = '';
        }

        if (isset($this->error['keyword'])) {
            $data['error_keyword'] = $this->error['keyword'];
        } else {
            $data['error_keyword'] = '';
        }

        $url = '';

        if (isset($this->request->get['filter_name'])) {
            $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_model'])) {
            $url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_unit'])) {
            $url .= '&filter_unit=' . urlencode(html_entity_decode($this->request->get['filter_unit'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_has_image'])) {
            $url .= '&filter_has_image=' . $this->request->get['filter_has_image'];
        }

        if (isset($this->request->get['filter_quantity_min'])) {
            $url .= '&filter_quantity_min=' . $this->request->get['filter_quantity_min'];
        }
        if (isset($this->request->get['filter_quantity_max'])) {
            $url .= '&filter_quantity_max=' . $this->request->get['filter_quantity_max'];
        }

        if (isset($this->request->get['filter_price'])) {
            $url .= '&filter_price=' . $this->request->get['filter_price'];
        }

        if (isset($this->request->get['filter_quantity'])) {
            $url .= '&filter_quantity=' . $this->request->get['filter_quantity'];
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
            'href' => $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . $url, true)
        );

        if (!isset($this->request->get['product_id'])) {
            $data['action'] = $this->url->link('catalog/product/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
        } else {
            $data['action'] = $this->url->link('catalog/product/edit', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $this->request->get['product_id'] . $url, true);
        }

        $data['cancel'] = $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'] . $url, true);
        if (isset($this->request->get['product_id'])) {
            $product_info = $this->loadProductData($this->request->get['product_id']);
            $data['product_id'] = $this->request->get['product_id'];
        }

        $data['js_product_units'] = '[]';
        $data['js_product_inventory'] = '[]';
        $data['js_product_pricing'] = '[]';
        $data['js_branches'] = '[]';

        $branches = $this->model_catalog_product->getBranches();
        $data['js_branches'] = json_encode($branches ?: [], JSON_HEX_APOS | JSON_HEX_QUOT);

        $all_units = $this->model_catalog_product->getUnits();
        $data['js_all_units'] = json_encode($all_units ?: [], JSON_HEX_APOS | JSON_HEX_QUOT);

        if (isset($this->request->get['product_id'])) {
            $product_id = (int)$this->request->get['product_id'];

            $units = $this->model_catalog_product->getProductUnits($product_id);
            $data['js_product_units'] = json_encode($units ?: [], JSON_HEX_APOS | JSON_HEX_QUOT);

            $inventory = $this->model_catalog_product->getProductInventory($product_id);
            $data['js_product_inventory'] = json_encode($inventory ?: [], JSON_HEX_APOS | JSON_HEX_QUOT);

            $pricing = $this->model_catalog_product->getProductPricing($product_id);
            $data['js_product_pricing'] = json_encode($pricing ?: [], JSON_HEX_APOS | JSON_HEX_QUOT);

        } else {
            $data['js_product_units'] = '[]';
            $data['js_product_inventory'] = '[]';
            $data['js_product_pricing'] = '[]';
        }

        $data['user_token'] = $this->session->data['user_token'];
        $this->load->model('catalog/product');
        $this->load->model('localisation/language');

        $data['languages'] = $this->model_localisation_language->getLanguages();

        if (isset($this->request->post['product_description'])) {
            $data['product_description'] = $this->request->post['product_description'];
        } elseif (isset($this->request->get['product_id'])) {
            $data['product_description'] = $this->model_catalog_product->getProductDescriptions($this->request->get['product_id']);
        } else {
            $data['product_description'] = array();
        }

        $data['product_upsells'] = $this->model_catalog_product->getProductUpsells($this->request->get['product_id']);
        $data['product_cross_sells'] = $this->model_catalog_product->getProductCrossSells($this->request->get['product_id']);

        $data['product_units'] = array();
        if (isset($this->request->post['product_unit'])) {
            $data['product_units'] = $this->request->post['product_unit'];
        } elseif (!empty($product_info)) {
            $data['product_units'] = $this->model_catalog_product->getProductUnits($this->request->get['product_id']);
        }

        $data['product_inventory'] = array();
        if (isset($this->request->post['product_inventory'])) {
            $data['product_inventory'] = $this->request->post['product_inventory'];
        } elseif (!empty($product_info)) {
            $data['product_inventory'] = $this->model_catalog_product->getProductInventory($this->request->get['product_id']);
        }

        $data['branches'] = $this->model_catalog_product->getBranches();

        $data['product_pricing'] = array();
        if (isset($this->request->post['product_pricing'])) {
            $data['product_pricing'] = $this->request->post['product_pricing'];
        } elseif (!empty($product_info)) {
            $data['product_pricing'] = $this->model_catalog_product->getProductPricing($this->request->get['product_id']);
        }

        $data['units'] = $this->model_catalog_product->getUnits();

        if (isset($this->request->get['product_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $data['product_barcodes'] = $this->model_catalog_product->getProductBarcodes($this->request->get['product_id']);
        } else {
            $data['product_barcodes'] = array();
        }

        if (isset($this->request->get['product_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $data['product_bundles'] = $this->model_catalog_product->getProductBundles($this->request->get['product_id']);
        } else {
            $data['product_bundles'] = array();
        }

        if (isset($this->request->get['product_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $data['product_discounts'] = $this->model_catalog_product->getProductQuantityDiscounts($this->request->get['product_id']);
        } else {
            $data['product_discounts'] = array();
        }

        $this->load->model('customer/customer_group');
        $data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();
        $data['product_units'] = $this->model_catalog_product->getProductUnits($this->request->get['product_id']);

        if (isset($this->request->post['model'])) {
            $data['model'] = $this->request->post['model'];
        } elseif (!empty($product_info)) {
            $data['model'] = $product_info['model'];
        } else {
            $data['model'] = '';
        }

        if (isset($this->request->post['sku'])) {
            $data['sku'] = $this->request->post['sku'];
        } elseif (!empty($product_info)) {
            $data['sku'] = $product_info['sku'];
        } else {
            $data['sku'] = '';
        }

        if (isset($this->request->post['upc'])) {
            $data['upc'] = $this->request->post['upc'];
        } elseif (!empty($product_info)) {
            $data['upc'] = $product_info['upc'];
        } else {
            $data['upc'] = '';
        }

        if (isset($this->request->post['ean'])) {
            $data['ean'] = $this->request->post['ean'];
        } elseif (!empty($product_info)) {
            $data['ean'] = $product_info['ean'];
        } else {
            $data['ean'] = '';
        }

        if (isset($this->request->post['jan'])) {
            $data['jan'] = $this->request->post['jan'];
        } elseif (!empty($product_info)) {
            $data['jan'] = $product_info['jan'];
        } else {
            $data['jan'] = '';
        }

        if (isset($this->request->post['isbn'])) {
            $data['isbn'] = $this->request->post['isbn'];
        } elseif (!empty($product_info)) {
            $data['isbn'] = $product_info['isbn'];
        } else {
            $data['isbn'] = '';
        }

        if (isset($this->request->post['mpn'])) {
            $data['mpn'] = $this->request->post['mpn'];
        } elseif (!empty($product_info)) {
            $data['mpn'] = $product_info['mpn'];
        } else {
            $data['mpn'] = '';
        }

        if (isset($this->request->post['location'])) {
            $data['location'] = $this->request->post['location'];
        } elseif (!empty($product_info)) {
            $data['location'] = $product_info['location'];
        } else {
            $data['location'] = '';
        }

        $this->load->model('setting/store');

        $data['stores'] = array();

        $data['stores'][] = array(
            'store_id' => 0,
            'name'     => $this->language->get('text_default')
        );

        $stores = $this->model_setting_store->getStores();

        foreach ($stores as $store) {
            $data['stores'][] = array(
                'store_id' => $store['store_id'],
                'name'     => $store['name']
            );
        }

        if (isset($this->request->post['product_store'])) {
            $data['product_store'] = $this->request->post['product_store'];
        } elseif (isset($this->request->get['product_id'])) {
            $data['product_store'] = $this->model_catalog_product->getProductStores($this->request->get['product_id']);
        } else {
            $data['product_store'] = array(0);
        }

        if (isset($this->request->post['shipping'])) {
            $data['shipping'] = $this->request->post['shipping'];
        } elseif (!empty($product_info)) {
            $data['shipping'] = $product_info['shipping'];
        } else {
            $data['shipping'] = 1;
        }

        if (isset($this->request->post['price'])) {
            $data['price'] = $this->request->post['price'];
        } elseif (!empty($product_info)) {
            $data['price'] = $product_info['price'];
        } else {
            $data['price'] = '';
        }

        $this->load->model('localisation/tax_class');

        $data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();

        if (isset($this->request->post['tax_class_id'])) {
            $data['tax_class_id'] = $this->request->post['tax_class_id'];
        } elseif (!empty($product_info)) {
            $data['tax_class_id'] = $product_info['tax_class_id'];
        } else {
            $data['tax_class_id'] = 0;
        }

        if (isset($this->request->post['date_available'])) {
            $data['date_available'] = $this->request->post['date_available'];
        } elseif (!empty($product_info)) {
            $data['date_available'] = ($product_info['date_available'] != '0000-00-00') ? $product_info['date_available'] : '';
        } else {
            $data['date_available'] = date('Y-m-d');
        }

        if (isset($this->request->post['quantity'])) {
            $data['quantity'] = $this->request->post['quantity'];
        } elseif (!empty($product_info)) {
            $data['quantity'] = $product_info['quantity'];
        } else {
            $data['quantity'] = 1;
        }

        if (isset($this->request->post['minimum'])) {
            $data['minimum'] = $this->request->post['minimum'];
        } elseif (!empty($product_info)) {
            $data['minimum'] = $product_info['minimum'];
        } else {
            $data['minimum'] = 1;
        }

        if (isset($this->request->post['subtract'])) {
            $data['subtract'] = $this->request->post['subtract'];
        } elseif (!empty($product_info)) {
            $data['subtract'] = $product_info['subtract'];
        } else {
            $data['subtract'] = 1;
        }

        if (isset($this->request->post['sort_order'])) {
            $data['sort_order'] = $this->request->post['sort_order'];
        } elseif (!empty($product_info)) {
            $data['sort_order'] = $product_info['sort_order'];
        } else {
            $data['sort_order'] = 1;
        }

        $this->load->model('localisation/stock_status');

        $data['stock_statuses'] = $this->model_localisation_stock_status->getStockStatuses();

        if (isset($this->request->post['stock_status_id'])) {
            $data['stock_status_id'] = $this->request->post['stock_status_id'];
        } elseif (!empty($product_info)) {
            $data['stock_status_id'] = $product_info['stock_status_id'];
        } else {
            $data['stock_status_id'] = 0;
        }

        if (isset($this->request->post['status'])) {
            $data['status'] = $this->request->post['status'];
        } elseif (!empty($product_info)) {
            $data['status'] = $product_info['status'];
        } else {
            $data['status'] = true;
        }

        if (isset($this->request->post['weight'])) {
            $data['weight'] = $this->request->post['weight'];
        } elseif (!empty($product_info)) {
            $data['weight'] = $product_info['weight'];
        } else {
            $data['weight'] = '';
        }

        $this->load->model('localisation/weight_class');

        $data['weight_classes'] = $this->model_localisation_weight_class->getWeightClasses();

        if (isset($this->request->post['weight_class_id'])) {
            $data['weight_class_id'] = $this->request->post['weight_class_id'];
        } elseif (!empty($product_info)) {
            $data['weight_class_id'] = $product_info['weight_class_id'];
        } else {
            $data['weight_class_id'] = $this->config->get('config_weight_class_id');
        }

        if (isset($this->request->post['length'])) {
            $data['length'] = $this->request->post['length'];
        } elseif (!empty($product_info)) {
            $data['length'] = $product_info['length'];
        } else {
            $data['length'] = '';
        }

        if (isset($this->request->post['width'])) {
            $data['width'] = $this->request->post['width'];
        } elseif (!empty($product_info)) {
            $data['width'] = $product_info['width'];
        } else {
            $data['width'] = '';
        }

        if (isset($this->request->post['height'])) {
            $data['height'] = $this->request->post['height'];
        } elseif (!empty($product_info)) {
            $data['height'] = $product_info['height'];
        } else {
            $data['height'] = '';
        }

        $this->load->model('localisation/length_class');

        $data['length_classes'] = $this->model_localisation_length_class->getLengthClasses();

        if (isset($this->request->post['length_class_id'])) {
            $data['length_class_id'] = $this->request->post['length_class_id'];
        } elseif (!empty($product_info)) {
            $data['length_class_id'] = $product_info['length_class_id'];
        } else {
            $data['length_class_id'] = $this->config->get('config_length_class_id');
        }

        $this->load->model('catalog/manufacturer');

        if (isset($this->request->post['manufacturer_id'])) {
            $data['manufacturer_id'] = $this->request->post['manufacturer_id'];
        } elseif (!empty($product_info)) {
            $data['manufacturer_id'] = $product_info['manufacturer_id'];
        } else {
            $data['manufacturer_id'] = 0;
        }

        if (isset($this->request->post['manufacturer'])) {
            $data['manufacturer'] = $this->request->post['manufacturer'];
        } elseif (!empty($product_info)) {
            $manufacturer_info = $this->model_catalog_manufacturer->getManufacturer($product_info['manufacturer_id']);

            if ($manufacturer_info) {
                $data['manufacturer'] = $manufacturer_info['name'];
            } else {
                $data['manufacturer'] = '';
            }
        } else {
            $data['manufacturer'] = '';
        }

        $this->load->model('catalog/category');

        if (isset($this->request->post['product_category'])) {
            $categories = $this->request->post['product_category'];
        } elseif (isset($this->request->get['product_id'])) {
            $categories = $this->model_catalog_product->getProductCategories($this->request->get['product_id']);
        } else {
            $categories = array();
        }

        $data['product_categories'] = array();

        foreach ($categories as $category_id) {
            $category_info = $this->model_catalog_category->getCategory($category_id);

            if ($category_info) {
                $data['product_categories'][] = array(
                    'category_id' => $category_info['category_id'],
                    'name' => ($category_info['path']) ? $category_info['path'] . ' &gt; ' . $category_info['name'] : $category_info['name']
                );
            }
        }

        $this->load->model('catalog/filter');

        if (isset($this->request->post['product_filter'])) {
            $filters = $this->request->post['product_filter'];
        } elseif (isset($this->request->get['product_id'])) {
            $filters = $this->model_catalog_product->getProductFilters($this->request->get['product_id']);
        } else {
            $filters = array();
        }

        $data['product_filters'] = array();

        foreach ($filters as $filter_id) {
            $filter_info = $this->model_catalog_filter->getFilter($filter_id);

            if ($filter_info) {
                $data['product_filters'][] = array(
                    'filter_id' => $filter_info['filter_id'],
                    'name'      => $filter_info['group'] . ' &gt; ' . $filter_info['name']
                );
            }
        }

        $this->load->model('catalog/attribute');

        if (isset($this->request->post['product_attribute'])) {
            $product_attributes = $this->request->post['product_attribute'];
        } elseif (isset($this->request->get['product_id'])) {
            $product_attributes = $this->model_catalog_product->getProductAttributes($this->request->get['product_id']);
        } else {
            $product_attributes = array();
        }

        $data['product_attributes'] = array();

        foreach ($product_attributes as $product_attribute) {
            $attribute_info = $this->model_catalog_attribute->getAttribute($product_attribute['attribute_id']);

            if ($attribute_info) {
                $data['product_attributes'][] = array(
                    'attribute_id'                  => $product_attribute['attribute_id'],
                    'name'                          => $attribute_info['name'],
                    'product_attribute_description' => $product_attribute['product_attribute_description']
                );
            }
        }

        $this->load->model('catalog/option');

        if (isset($this->request->post['product_option'])) {
            $product_options = $this->request->post['product_option'];
        } elseif (isset($this->request->get['product_id'])) {
            $product_options = $this->model_catalog_product->getProductOptions($this->request->get['product_id']);
        } else {
            $product_options = array();
        }

        $data['product_options'] = array();

        foreach ($product_options as $product_option) {
            $product_option_value_data = array();

            if (isset($product_option['product_option_value'])) {
                foreach ($product_option['product_option_value'] as $product_option_value) {
                    $product_option_value_data[] = array(
                        'product_option_value_id' => $product_option_value['product_option_value_id'],
                        'option_value_id'         => $product_option_value['option_value_id'],
                        'quantity'                => $product_option_value['quantity'],
                        'subtract'                => $product_option_value['subtract'],
                        'price'                   => $product_option_value['price'],
                        'price_prefix'            => $product_option_value['price_prefix'],
                        'points'                  => $product_option_value['points'],
                        'points_prefix'           => $product_option_value['points_prefix'],
                        'weight'                  => $product_option_value['weight'],
                        'weight_prefix'           => $product_option_value['weight_prefix']
                    );
                }
            }

            $data['product_options'][] = array(
                'product_option_id'    => $product_option['product_option_id'],
                'product_option_value' => $product_option_value_data,
                'option_id'            => $product_option['option_id'],
                'unit_id'              => $product_option['unit_id'],
                'name'                 => $product_option['name'],
                'type'                 => $product_option['type'],
                'value'                => isset($product_option['value']) ? $product_option['value'] : '',
                'required'             => $product_option['required']
            );
        }

        $data['option_values'] = array();

        foreach ($data['product_options'] as $product_option) {
            if ($product_option['type'] == 'select' || $product_option['type'] == 'radio' || $product_option['type'] == 'checkbox' || $product_option['type'] == 'image') {
                if (!isset($data['option_values'][$product_option['option_id']])) {
                    $data['option_values'][$product_option['option_id']] = $this->model_catalog_option->getOptionValues($product_option['option_id']);
                }
            }
        }

        $this->load->model('customer/customer_group');

        $data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();

        if (isset($this->request->post['image'])) {
            $data['image'] = $this->request->post['image'];
        } elseif (!empty($product_info)) {
            $data['image'] = $product_info['image'];
        } else {
            $data['image'] = '';
        }

        $this->load->model('tool/image');

        if (isset($this->request->post['image']) && is_file(DIR_IMAGE . $this->request->post['image'])) {
            $data['thumb'] = $this->model_tool_image->resize($this->request->post['image'], 100, 100);
        } elseif (!empty($product_info) && is_file(DIR_IMAGE . $product_info['image'])) {
            $data['thumb'] = $this->model_tool_image->resize($product_info['image'], 100, 100);
        } else {
            $data['thumb'] = $this->model_tool_image->resize('no_image.png', 100, 100);
        }

        $data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);

        if (isset($this->request->post['product_image'])) {
            $product_images = $this->request->post['product_image'];
        } elseif (isset($this->request->get['product_id'])) {
            $product_images = $this->model_catalog_product->getProductImages($this->request->get['product_id']);
        } else {
            $product_images = array();
        }

        $data['product_images'] = array();

        foreach ($product_images as $product_image) {
            if (is_file(DIR_IMAGE . $product_image['image'])) {
                $image = $product_image['image'];
                $thumb = $product_image['image'];
            } else {
                $image = '';
                $thumb = 'no_image.png';
            }

            $data['product_images'][] = array(
                'image'      => $image,
                'thumb'      => $this->model_tool_image->resize($thumb, 100, 100),
                'sort_order' => $product_image['sort_order']
            );
        }

        if (isset($this->request->post['product_related'])) {
            $products = $this->request->post['product_related'];
        } elseif (isset($this->request->get['product_id'])) {
            $products = $this->model_catalog_product->getProductRelated($this->request->get['product_id']);
        } else {
            $products = array();
        }

        $data['product_relateds'] = array();

        foreach ($products as $product_id) {
            $related_info = $this->model_catalog_product->getProduct($product_id);

            if ($related_info) {
$data['product_relateds'][] = array(
                    'product_id' => $related_info['product_id'],
                    'name'       => $related_info['name']
                );
            }
        }

        if (isset($this->request->post['points'])) {
            $data['points'] = $this->request->post['points'];
        } elseif (!empty($product_info)) {
            $data['points'] = $product_info['points'];
        } else {
            $data['points'] = '';
        }

        if (isset($this->request->post['product_reward'])) {
            $data['product_reward'] = $this->request->post['product_reward'];
        } elseif (isset($this->request->get['product_id'])) {
            $data['product_reward'] = $this->model_catalog_product->getProductRewards($this->request->get['product_id']);
        } else {
            $data['product_reward'] = array();
        }

        if (isset($this->request->post['product_seo_url'])) {
            $data['product_seo_url'] = $this->request->post['product_seo_url'];
        } elseif (isset($this->request->get['product_id'])) {
            $data['product_seo_url'] = $this->model_catalog_product->getProductSeoUrls($this->request->get['product_id']);
        } else {
            $data['product_seo_url'] = array();
        }

        if (isset($this->request->post['product_layout'])) {
            $data['product_layout'] = $this->request->post['product_layout'];
        } elseif (isset($this->request->get['product_id'])) {
            $data['product_layout'] = $this->model_catalog_product->getProductLayouts($this->request->get['product_id']);
        } else {
            $data['product_layout'] = array();
        }
    $data['tab_general'] = $this->load->view('catalog/product_tab_general', $data);
    $data['tab_data'] = $this->load->view('catalog/product_tab_data', $data);
    $data['tab_image'] = $this->load->view('catalog/product_tab_image', $data);
    $data['tab_units'] = $this->load->view('catalog/product_tab_units', $data);
    $data['tab_inventory'] = $this->load->view('catalog/product_tab_inventory', $data);
    $data['tab_pricing'] = $this->load->view('catalog/product_tab_pricing', $data);
    $data['tab_barcode'] = $this->load->view('catalog/product_tab_barcode', $data);
    $data['tab_option'] = $this->load->view('catalog/product_tab_option', $data);
    $data['tab_bundle'] = $this->load->view('catalog/product_tab_bundle', $data);
    $data['tab_recommend'] = $this->load->view('catalog/product_tab_recommend', $data);
    $data['tab_movement'] = $this->load->view('catalog/product_tab_movement', $data);
    $data['tab_orders'] = $this->load->view('catalog/product_tab_orders', $data);

        $this->load->model('design/layout');

        $data['layouts'] = $this->model_design_layout->getLayouts();

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('catalog/product_form', $data));
    }

    public function bundleAutocomplete() {
        $json = array();

        if (isset($this->request->get['filter_name'])) {
            $this->load->model('catalog/product');

            $filter_data = array(
                'filter_name' => '%'.$this->request->get['filter_name'].'%',
                'start'       => 0,
                'limit'       => 5
            );

            $results = $this->model_catalog_product->getProducts($filter_data);

            foreach ($results as $result) {
                $json[] = array(
                    'product_id' => $result['product_id'],
                    'product' => $this->model_catalog_product->getProduct($result['product_id']),
                    'name'       => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8'))
                );
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'catalog/product')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        foreach ($this->request->post['product_description'] as $language_id => $value) {
            if ((utf8_strlen($value['name']) < 1) || (utf8_strlen($value['name']) > 255)) {
                $this->error['name'][$language_id] = $this->language->get('error_name');
            }

            if ((utf8_strlen($value['meta_title']) < 1) || (utf8_strlen($value['meta_title']) > 255)) {
                $this->error['meta_title'][$language_id] = $this->language->get('error_meta_title');
            }
        }

        if ((utf8_strlen($this->request->post['model']) < 1) || (utf8_strlen($this->request->post['model']) > 64)) {
            $this->error['model'] = $this->language->get('error_model');
        }

        if (isset($this->request->post['product_upsell'])) {
            foreach ($this->request->post['product_upsell'] as $upsell) {
                if (!isset($upsell['related_product_id']) || !$upsell['related_product_id']) {
                    $this->error['upsell'] = $this->language->get('error_upsell_product');
                }
                if (!isset($upsell['unit_id']) || !$upsell['unit_id']) {
                    $this->error['upsell'] = $this->language->get('error_upsell_unit');
                }
            }
        }

        if (isset($this->request->post['product_cross_sell'])) {
            foreach ($this->request->post['product_cross_sell'] as $cross_sell) {
                if (!isset($cross_sell['related_product_id']) || !$cross_sell['related_product_id']) {
                    $this->error['cross_sell'] = $this->language->get('error_cross_sell_product');
                }
                if (!isset($cross_sell['unit_id']) || !$cross_sell['unit_id']) {
                    $this->error['cross_sell'] = $this->language->get('error_cross_sell_unit');
                }
            }
        }

        if (isset($this->request->post['product_bundle'])) {
            foreach ($this->request->post['product_bundle'] as $bundle) {
                if ((utf8_strlen($bundle['name']) < 1) || (utf8_strlen($bundle['name']) > 255)) {
                    $this->error['bundle'][] = $this->language->get('error_bundle_name');
                }
            }
        }

        if (!$this->validateUnits()) {
        }

        if (!$this->validateInventory()) {
        }

        if (!$this->validatePricing()) {
        }

        if (!$this->validateOptionQuantities($this->request->post['product_option'], $this->request->post['product_inventory'])) {
            $this->error['warning'] = $this->language->get('error_quantity_exceeded');
        }

        if ($this->request->post['product_seo_url']) {
            $this->load->model('design/seo_url');

            foreach ($this->request->post['product_seo_url'] as $store_id => $language) {
                foreach ($language as $language_id => $keyword) {
                    if (!empty($keyword)) {
                        if (count(array_keys($language, $keyword)) > 1) {
                            $this->error['keyword'][$store_id][$language_id] = $this->language->get('error_unique');
                        }

                        $seo_urls = $this->model_design_seo_url->getSeoUrlsByKeyword($keyword,$language_id);

                        foreach ($seo_urls as $seo_url) {
                            if (($seo_url['store_id'] == $store_id) && (!isset($this->request->get['product_id']) || (($seo_url['query'] != 'product_id=' . $this->request->get['product_id'])))) {
                                $this->error['keyword'][$store_id][$language_id] = $this->language->get('error_keyword');
                                break;
                            }
                        }
                    }
                }
            }
        }

        if ($this->error && !isset($this->error['warning'])) {
            $this->error['warning'] = $this->language->get('error_warning');
        }

        return !$this->error;
    }

    protected function loadProductData($product_id) {
        $this->load->model('catalog/product');
        $product_info = $this->model_catalog_product->getProduct($product_id);

        if ($product_info) {
            $product_info['product_description'] = $this->model_catalog_product->getProductDescriptions($product_id);
            $product_info['product_category'] = $this->model_catalog_product->getProductCategories($product_id);
            $product_info['product_filter'] = $this->model_catalog_product->getProductFilters($product_id);
            $product_info['product_attribute'] = $this->model_catalog_product->getProductAttributes($product_id);
            $product_info['product_option'] = $this->model_catalog_product->getProductOptions($product_id);
            $product_info['product_related'] = $this->model_catalog_product->getProductRelated($product_id);
            $product_info['product_reward'] = $this->model_catalog_product->getProductRewards($product_id);
            $product_info['product_special'] = $this->model_catalog_product->getProductSpecials($product_id);
            $product_info['product_image'] = $this->model_catalog_product->getProductImages($product_id);
            $product_info['product_store'] = $this->model_catalog_product->getProductStores($product_id);
            $product_info['product_layout'] = $this->model_catalog_product->getProductLayouts($product_id);
            $product_info['product_unit'] = $this->model_catalog_product->getProductUnits($product_id);
            $product_info['product_units'] = $this->model_catalog_product->getProductUnits($product_id);
            $product_info['product_inventory'] = $this->model_catalog_product->getProductInventory($product_id);
            $product_info['product_pricing'] = $this->model_catalog_product->getProductPricing($product_id);
        }

        return $product_info;
    }

    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'catalog/product')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    protected function validateCopy() {
        if (!$this->user->hasPermission('modify', 'catalog/product')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    public function autocomplete() {
        $json = array();

        if (isset($this->request->get['filter_name']) || isset($this->request->get['filter_model'])) {
            $this->load->model('catalog/product');
            $this->load->model('catalog/option');

            if (isset($this->request->get['filter_name'])) {
                $filter_name = $this->request->get['filter_name'];
            } else {
                $filter_name = '';
            }

            if (isset($this->request->get['filter_model'])) {
                $filter_model = $this->request->get['filter_model'];
            } else {
                $filter_model = '';
            }

            if (isset($this->request->get['limit'])) {
                $limit = $this->request->get['limit'];
            } else {
                $limit = 5;
            }

            $filter_data = array(
                'filter_name'  => $filter_name,
                'filter_model' => $filter_model,
                'start'        => 0,
                'limit'        => $limit
            );

            $results = $this->model_catalog_product->getProducts($filter_data);

            foreach ($results as $result) {
                $option_data = array();

                $product_options = $this->model_catalog_product->getProductOptions($result['product_id']);

                foreach ($product_options as $product_option) {
                    $option_info = $this->model_catalog_option->getOption($product_option['option_id']);

                    if ($option_info) {
                        $product_option_value_data = array();

                        foreach ($product_option['product_option_value'] as $product_option_value) {
                            $option_value_info = $this->model_catalog_option->getOptionValue($product_option_value['option_value_id']);

                            if ($option_value_info) {
                                $product_option_value_data[] = array(
                                    'product_option_value_id' => $product_option_value['product_option_value_id'],
                                    'option_value_id'         => $product_option_value['option_value_id'],
                                    'name'                    => $option_value_info['name'],
                                    'price'                   => (float)$product_option_value['price'] ? $this->currency->format($product_option_value['price'], $this->config->get('config_currency')) : false,
                                    'price_prefix'            => $product_option_value['price_prefix']
                                );
                            }
                        }

                        $option_data[] = array(
                            'product_option_id'    => $product_option['product_option_id'],
                            'product_option_value' => $product_option_value_data,
                            'option_id'            => $product_option['option_id'],
                            'unit_id'              => $product_option['unit_id'],
                            'name'                 => $option_info['name'],
                            'type'                 => $option_info['type'],
                            'value'                => $product_option['value'],
                            'required'             => $product_option['required']
                        );
                    }
                }

                $json[] = array(
                    'product_id' => $result['product_id'],
                    'name'       => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8')),
                    'model'      => $result['model'],
                    'option'     => $option_data,
                    'price'      => $result['price']
                );
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function autocompletetag() {
        $json = [];

        if (isset($this->request->get['filter_name'])) {
            $this->load->model('catalog/product');

            $filter_data = [
                'filter_name' => $this->request->get['filter_name'],
                'start'       => 0,
                'limit'       => 5
            ];

            $tags = $this->model_catalog_product->getProductTags($filter_data);

            foreach ($tags as $tag) {
                $explodedTags = explode(',', $tag['tag']);

                foreach ($explodedTags as $explodedTag) {
                    $json[] = [
                        'tag'  => $explodedTag,
                        'name' => $explodedTag
                    ];
                }
            }
        }

        $sort_order = [];

        foreach ($json as $key => $value) {
            $sort_order[$key] = $value['name'];
        }

        array_multisort($sort_order, SORT_ASC, $json);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Obtener inventario del producto
     */
    public function getInventory() {
        $this->load->language('catalog/product');

        $json = array();

        if (isset($this->request->post['product_id'])) {
            $product_id = (int)$this->request->post['product_id'];

            $this->load->model('catalog/product');

            // Obtener datos de inventario
            $inventory_data = $this->model_catalog_product->getProductInventory($product_id);

            $json['inventory'] = array();

            foreach ($inventory_data as $inventory) {
                $json['inventory'][] = array(
                    'product_id' => $inventory['product_id'],
                    'branch_id' => $inventory['branch_id'],
                    'branch_name' => $inventory['branch_name'],
                    'unit_id' => $inventory['unit_id'],
                    'unit_name' => $inventory['unit_name'],
                    'quantity' => $inventory['quantity'],
                    'quantity_available' => $inventory['quantity_available'],
                    'average_cost' => $inventory['average_cost'],
                    'is_consignment' => $inventory['is_consignment']
                );
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Obtener inventario por unidad
     */
    public function getInventoryByUnit() {
        $this->load->language('catalog/product');

        $json = array();

        if (isset($this->request->post['product_id']) && isset($this->request->post['branch_id']) && isset($this->request->post['unit_id'])) {
            $product_id = (int)$this->request->post['product_id'];
            $branch_id = (int)$this->request->post['branch_id'];
            $unit_id = $this->request->post['unit_id'];

            $this->load->model('catalog/product');

            // Obtener datos de inventario por unidad
            $inventory = $this->model_catalog_product->getProductInventoryByUnit($product_id, $unit_id, $branch_id);

            if ($inventory) {
                $json['inventory'] = array(
                    'product_id' => $inventory['product_id'],
                    'branch_id' => $inventory['branch_id'],
                    'branch_name' => $inventory['branch_name'],
                    'unit_id' => $inventory['unit_id'],
                    'unit_name' => $inventory['unit_name'],
                    'quantity' => $inventory['quantity'],
                    'quantity_available' => $inventory['quantity_available'],
                    'average_cost' => $inventory['average_cost'],
                    'is_consignment' => $inventory['is_consignment']
                );
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Obtener unidades del producto
     */
    public function getProductUnits() {
        $this->load->language('catalog/product');

        $json = array();

        if (isset($this->request->post['product_id'])) {
            $product_id = (int)$this->request->post['product_id'];

            $this->load->model('catalog/product');

            // Obtener unidades del producto
            $units = $this->model_catalog_product->getProductUnits($product_id);

            $json['units'] = array();

            foreach ($units as $unit) {
                $json['units'][] = array(
                    'unit_id' => $unit['unit_id'],
                    'name' => $unit['unit_name'],
                    'is_base' => $unit['is_base_unit'],
                    'conversion_factor' => $unit['conversion_factor']
                );
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Obtener movimientos recientes
     */
    public function getRecentMovements() {
        $this->load->language('catalog/product');

        $json = array();

        if (isset($this->request->post['product_id'])) {
            $product_id = (int)$this->request->post['product_id'];
            $limit = isset($this->request->post['limit']) ? (int)$this->request->post['limit'] : 5;

            $this->load->model('catalog/product');

            // Cargar el modelo de inventario
            $this->load->model('catalog/inventory_manager');

            // Obtener movimientos recientes
            $filters = array(
                'limit' => $limit
            );

            $movements = $this->model_catalog_inventory_manager->getProductMovements($product_id, $filters);

            $json['movements'] = array();

            foreach ($movements as $movement) {
                $json['movements'][] = array(
                    'movement_id' => $movement['movement_id'],
                    'product_id' => $movement['product_id'],
                    'type' => $movement['movement_type'],
                    'quantity' => $movement['quantity'],
                    'unit_id' => $movement['unit_id'],
                    'unit_name' => $movement['unit_name'],
                    'branch_id' => $movement['warehouse_id'],
                    'branch_name' => $movement['warehouse_name'],
                    'reference_type' => $movement['reference_type'],
                    'reference_id' => $movement['reference_id'],
                    'cost' => $movement['cost'],
                    'cost_impact' => $movement['movement_type'] == 'in' ? $movement['quantity'] * $movement['cost'] : -$movement['quantity'] * $movement['cost'],
                    'notes' => $movement['notes'],
                    'date_added' => date($this->language->get('date_format_short'), strtotime($movement['date_added'])),
                    'user_name' => $movement['username']
                );
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Obtener todos los movimientos de un producto con filtros
     */
    public function getProductMovements() {
        $this->load->language('catalog/product');

        $json = array();

        if (isset($this->request->post['product_id'])) {
            $product_id = (int)$this->request->post['product_id'];

            $this->load->model('catalog/product');
            $this->load->model('catalog/inventory_manager');

            // Procesar filtros
            $filters = array();

            if (isset($this->request->post['filters'])) {
                $post_filters = $this->request->post['filters'];

                if (isset($post_filters['type']) && $post_filters['type'] != '') {
                    $filters['type'] = $post_filters['type'];
                }

                if (isset($post_filters['branch_id']) && $post_filters['branch_id'] != '') {
                    $filters['branch_id'] = (int)$post_filters['branch_id'];
                }

                if (isset($post_filters['unit_id']) && $post_filters['unit_id'] != '') {
                    $filters['unit_id'] = $post_filters['unit_id'];
                }

                if (isset($post_filters['date_from']) && $post_filters['date_from'] != '') {
                    $filters['date_from'] = $post_filters['date_from'];
                }

                if (isset($post_filters['date_to']) && $post_filters['date_to'] != '') {
                    $filters['date_to'] = $post_filters['date_to'];
                }

                if (isset($post_filters['page']) && $post_filters['page'] > 0) {
                    $page = (int)$post_filters['page'];
                } else {
                    $page = 1;
                }

                if (isset($post_filters['limit']) && $post_filters['limit'] > 0) {
                    $limit = (int)$post_filters['limit'];
                } else {
                    $limit = 10;
                }

                $filters['start'] = ($page - 1) * $limit;
                $filters['limit'] = $limit;
            } else {
                $page = 1;
                $limit = 10;
                $filters['start'] = 0;
                $filters['limit'] = $limit;
            }

            // Obtener movimientos
            $movements = $this->model_catalog_inventory_manager->getProductMovements($product_id, $filters);
            $total_movements = $this->model_catalog_inventory_manager->getTotalProductMovements($product_id, $filters);

            $json['movements'] = array();

            foreach ($movements as $movement) {
                $json['movements'][] = array(
                    'movement_id' => $movement['movement_id'],
                    'product_id' => $movement['product_id'],
                    'type' => $movement['movement_type'],
                    'quantity' => $movement['quantity'],
                    'unit_id' => $movement['unit_id'],
                    'unit_name' => $movement['unit_name'],
                    'branch_id' => $movement['warehouse_id'],
                    'branch_name' => $movement['warehouse_name'],
                    'reference' => $movement['reference'],
                    'cost' => $movement['cost'],
                    'old_cost' => $movement['old_average_cost'],
                    'new_cost' => $movement['new_average_cost'],
                    'notes' => $movement['notes'],
                    'date_added' => date($this->language->get('date_format_short'), strtotime($movement['date_added'])),
                    'user_name' => $movement['username']
                );
            }

            // Paginación
            $pagination = new Pagination();
            $pagination->total = $total_movements;
            $pagination->page = $page;
            $pagination->limit = $limit;
            $pagination->url = '';

            $json['pagination'] = array(
                'total' => $total_movements,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total_movements / $limit)
            );
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Obtener historial de costos de un producto
     */
    public function getCostHistory() {
        $this->load->language('catalog/product');

        $json = array();

        if (isset($this->request->post['product_id'])) {
            $product_id = (int)$this->request->post['product_id'];

            $this->load->model('catalog/product');
            $this->load->model('catalog/inventory_manager');

            // Obtener historial de costos
            $cost_history = $this->model_catalog_inventory_manager->getProductCostHistory($product_id);

            $json['cost_history'] = array();

            foreach ($cost_history as $item) {
                $json['cost_history'][] = array(
                    'cost_history_id' => $item['cost_history_id'],
                    'product_id' => $item['product_id'],
                    'unit_id' => $item['unit_id'],
                    'unit_name' => $item['unit_name'],
                    'branch_id' => $item['branch_id'],
                    'branch_name' => $item['branch_name'],
                    'old_cost' => $item['old_cost'],
                    'new_cost' => $item['new_cost'],
                    'change_type' => $item['change_type'],
                    'reason' => $item['reason'],
                    'notes' => $item['notes'],
                    'date_added' => date($this->language->get('date_format_short'), strtotime($item['date_added'])),
                    'user_id' => $item['user_id'],
                    'user_name' => $item['username']
                );
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Obtener estadísticas de movimientos de un producto
     */
    public function getMovementStatistics() {
        $this->load->language('catalog/product');

        $json = array();

        if (isset($this->request->post['product_id'])) {
            $product_id = (int)$this->request->post['product_id'];

            $this->load->model('catalog/product');
            $this->load->model('catalog/inventory_manager');

            // Procesar filtros
            $filters = array();

            if (isset($this->request->post['filters'])) {
                $post_filters = $this->request->post['filters'];

                if (isset($post_filters['type']) && $post_filters['type'] != '') {
                    $filters['type'] = $post_filters['type'];
                }

                if (isset($post_filters['branch_id']) && $post_filters['branch_id'] != '') {
                    $filters['branch_id'] = (int)$post_filters['branch_id'];
                }

                if (isset($post_filters['unit_id']) && $post_filters['unit_id'] != '') {
                    $filters['unit_id'] = $post_filters['unit_id'];
                }

                if (isset($post_filters['date_from']) && $post_filters['date_from'] != '') {
                    $filters['date_from'] = $post_filters['date_from'];
                }

                if (isset($post_filters['date_to']) && $post_filters['date_to'] != '') {
                    $filters['date_to'] = $post_filters['date_to'];
                }
            }

            // Obtener estadísticas
            $statistics = $this->model_catalog_inventory_manager->getProductMovementStatistics($product_id, $filters);

            $json['statistics'] = $statistics;
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Obtener detalles de un movimiento específico
     */
    public function getMovementDetails() {
        $this->load->language('catalog/product');

        $json = array();

        if (isset($this->request->post['movement_id'])) {
            $movement_id = (int)$this->request->post['movement_id'];

            $this->load->model('catalog/inventory_manager');

            // Obtener detalles del movimiento
            $movement = $this->model_catalog_inventory_manager->getMovementDetails($movement_id);

            if ($movement) {
                $json['movement'] = array(
                    'movement_id' => $movement['movement_id'],
                    'product_id' => $movement['product_id'],
                    'type' => $movement['movement_type'],
                    'quantity' => $movement['quantity'],
                    'unit_id' => $movement['unit_id'],
                    'unit_name' => $movement['unit_name'],
                    'branch_id' => $movement['warehouse_id'],
                    'branch_name' => $movement['warehouse_name'],
                    'reference' => $movement['reference'],
                    'cost' => $movement['cost'],
                    'old_cost' => $movement['old_average_cost'],
                    'new_cost' => $movement['new_average_cost'],
                    'cost_impact' => $movement['cost_impact'],
                    'value_change' => $movement['value_change'],
                    'notes' => $movement['notes'],
                    'date_added' => date($this->language->get('date_format_short'), strtotime($movement['date_added'])),
                    'user_id' => $movement['user_id'],
                    'user_name' => $movement['username']
                );

                // Obtener asientos contables relacionados
                $journal_entries = $this->model_catalog_inventory_manager->getMovementJournalEntries($movement_id);

                $json['journal_entries'] = array();

                foreach ($journal_entries as $entry) {
                    $json['journal_entries'][] = array(
                        'journal_entry_id' => $entry['journal_entry_id'],
                        'account_id' => $entry['account_id'],
                        'account_name' => $entry['account_name'],
                        'debit' => $entry['debit'],
                        'credit' => $entry['credit'],
                        'description' => $entry['description']
                    );
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Exportar movimientos a Excel
     */
    public function exportMovements() {
        $this->load->language('catalog/product');

        if (isset($this->request->get['product_id'])) {
            $product_id = (int)$this->request->get['product_id'];

            $this->load->model('catalog/product');
            $this->load->model('catalog/inventory_manager');

            // Procesar filtros
            $filters = array();

            if (isset($this->request->get['filters'])) {
                $post_filters = json_decode(html_entity_decode($this->request->get['filters']), true);

                if (isset($post_filters['type']) && $post_filters['type'] != '') {
                    $filters['type'] = $post_filters['type'];
                }

                if (isset($post_filters['branch_id']) && $post_filters['branch_id'] != '') {
                    $filters['branch_id'] = (int)$post_filters['branch_id'];
                }

                if (isset($post_filters['unit_id']) && $post_filters['unit_id'] != '') {
                    $filters['unit_id'] = $post_filters['unit_id'];
                }

                if (isset($post_filters['date_from']) && $post_filters['date_from'] != '') {
                    $filters['date_from'] = $post_filters['date_from'];
                }

                if (isset($post_filters['date_to']) && $post_filters['date_to'] != '') {
                    $filters['date_to'] = $post_filters['date_to'];
                }
            }

            // Obtener movimientos
            $movements = $this->model_catalog_inventory_manager->getProductMovements($product_id, $filters);

            // Obtener información del producto
            $product_info = $this->model_catalog_product->getProduct($product_id);

            // Crear archivo Excel
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Establecer título
            $sheet->setCellValue('A1', $this->language->get('text_movement_history') . ': ' . $product_info['name']);
            $sheet->mergeCells('A1:I1');
            $sheet->getStyle('A1')->getFont()->setBold(true);
            $sheet->getStyle('A1')->getFont()->setSize(14);

            // Establecer encabezados
            $sheet->setCellValue('A3', $this->language->get('column_date_added'));
            $sheet->setCellValue('B3', $this->language->get('column_type'));
            $sheet->setCellValue('C3', $this->language->get('column_quantity'));
            $sheet->setCellValue('D3', $this->language->get('column_unit'));
            $sheet->setCellValue('E3', $this->language->get('column_branch'));
            $sheet->setCellValue('F3', $this->language->get('column_reference'));
            $sheet->setCellValue('G3', $this->language->get('column_cost'));
            $sheet->setCellValue('H3', $this->language->get('column_new_cost'));
            $sheet->setCellValue('I3', $this->language->get('column_user'));

            $sheet->getStyle('A3:I3')->getFont()->setBold(true);

            // Llenar datos
            $row = 4;
            foreach ($movements as $movement) {
                $sheet->setCellValue('A' . $row, date($this->language->get('date_format_short'), strtotime($movement['date_added'])));
                $sheet->setCellValue('B' . $row, $this->getMovementTypeText($movement['movement_type']));
                $sheet->setCellValue('C' . $row, $movement['quantity']);
                $sheet->setCellValue('D' . $row, $movement['unit_name']);
                $sheet->setCellValue('E' . $row, $movement['warehouse_name']);
                $sheet->setCellValue('F' . $row, $movement['reference']);
                $sheet->setCellValue('G' . $row, $movement['cost']);
                $sheet->setCellValue('H' . $row, $movement['new_average_cost']);
                $sheet->setCellValue('I' . $row, $movement['username']);

                $row++;
            }

            // Ajustar ancho de columnas
            $sheet->getColumnDimension('A')->setAutoSize(true);
            $sheet->getColumnDimension('B')->setAutoSize(true);
            $sheet->getColumnDimension('C')->setAutoSize(true);
            $sheet->getColumnDimension('D')->setAutoSize(true);
            $sheet->getColumnDimension('E')->setAutoSize(true);
            $sheet->getColumnDimension('F')->setAutoSize(true);
            $sheet->getColumnDimension('G')->setAutoSize(true);
            $sheet->getColumnDimension('H')->setAutoSize(true);
            $sheet->getColumnDimension('I')->setAutoSize(true);

            // Establecer nombre de archivo
            $filename = 'movement_history_' . $product_info['model'] . '_' . date('Y-m-d') . '.xlsx';

            // Establecer encabezados
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
        }
    }

    /**
     * Exportar historial de costos a Excel
     */
    public function exportCostHistory() {
        $this->load->language('catalog/product');

        if (isset($this->request->get['product_id'])) {
            $product_id = (int)$this->request->get['product_id'];

            $this->load->model('catalog/product');
            $this->load->model('catalog/inventory_manager');

            // Obtener historial de costos
            $cost_history = $this->model_catalog_inventory_manager->getProductCostHistory($product_id);

            // Obtener información del producto
            $product_info = $this->model_catalog_product->getProduct($product_id);

            // Crear archivo Excel
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Establecer título
            $sheet->setCellValue('A1', $this->language->get('text_cost_history') . ': ' . $product_info['name']);
            $sheet->mergeCells('A1:G1');
            $sheet->getStyle('A1')->getFont()->setBold(true);
            $sheet->getStyle('A1')->getFont()->setSize(14);

            // Establecer encabezados
            $sheet->setCellValue('A3', $this->language->get('column_date_added'));
            $sheet->setCellValue('B3', $this->language->get('column_unit'));
            $sheet->setCellValue('C3', $this->language->get('column_old_cost'));
            $sheet->setCellValue('D3', $this->language->get('column_new_cost'));
            $sheet->setCellValue('E3', $this->language->get('text_change_reason'));
            $sheet->setCellValue('F3', $this->language->get('column_user'));
            $sheet->setCellValue('G3', $this->language->get('text_notes'));

            $sheet->getStyle('A3:G3')->getFont()->setBold(true);

            // Llenar datos
            $row = 4;
            foreach ($cost_history as $item) {
                $sheet->setCellValue('A' . $row, date($this->language->get('date_format_short'), strtotime($item['date_added'])));
                $sheet->setCellValue('B' . $row, $item['unit_name']);
                $sheet->setCellValue('C' . $row, $item['old_cost']);
                $sheet->setCellValue('D' . $row, $item['new_cost']);
                $sheet->setCellValue('E' . $row, $item['reason']);
                $sheet->setCellValue('F' . $row, $item['username']);
                $sheet->setCellValue('G' . $row, $item['notes']);

                $row++;
            }

            // Ajustar ancho de columnas
            $sheet->getColumnDimension('A')->setAutoSize(true);
            $sheet->getColumnDimension('B')->setAutoSize(true);
            $sheet->getColumnDimension('C')->setAutoSize(true);
            $sheet->getColumnDimension('D')->setAutoSize(true);
            $sheet->getColumnDimension('E')->setAutoSize(true);
            $sheet->getColumnDimension('F')->setAutoSize(true);
            $sheet->getColumnDimension('G')->setAutoSize(true);

            // Establecer nombre de archivo
            $filename = 'cost_history_' . $product_info['model'] . '_' . date('Y-m-d') . '.xlsx';

            // Establecer encabezados
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
        }
    }

    /**
     * Imprimir informe de movimientos
     */
    public function printMovements() {
        $this->load->language('catalog/product');

        if (isset($this->request->get['product_id'])) {
            $product_id = (int)$this->request->get['product_id'];

            $this->load->model('catalog/product');
            $this->load->model('catalog/inventory_manager');

            // Procesar filtros
            $filters = array();

            if (isset($this->request->get['filters'])) {
                $post_filters = json_decode(html_entity_decode($this->request->get['filters']), true);

                if (isset($post_filters['type']) && $post_filters['type'] != '') {
                    $filters['type'] = $post_filters['type'];
                }

                if (isset($post_filters['branch_id']) && $post_filters['branch_id'] != '') {
                    $filters['branch_id'] = (int)$post_filters['branch_id'];
                }

                if (isset($post_filters['unit_id']) && $post_filters['unit_id'] != '') {
                    $filters['unit_id'] = $post_filters['unit_id'];
                }

                if (isset($post_filters['date_from']) && $post_filters['date_from'] != '') {
                    $filters['date_from'] = $post_filters['date_from'];
                }

                if (isset($post_filters['date_to']) && $post_filters['date_to'] != '') {
                    $filters['date_to'] = $post_filters['date_to'];
                }
            }

            // Obtener movimientos
            $movements = $this->model_catalog_inventory_manager->getProductMovements($product_id, $filters);

            // Obtener información del producto
            $product_info = $this->model_catalog_product->getProduct($product_id);

            // Obtener estadísticas
            $statistics = $this->model_catalog_inventory_manager->getProductMovementStatistics($product_id, $filters);

            // Preparar datos para la vista
            $data = array();

            $data['title'] = $this->language->get('text_movement_history') . ': ' . $product_info['name'];
            $data['product'] = $product_info;
            $data['movements'] = $movements;
            $data['statistics'] = $statistics;

            // Textos
            $data['text_movement_history'] = $this->language->get('text_movement_history');
            $data['text_product'] = $this->language->get('text_product');
            $data['text_model'] = $this->language->get('text_model');
            $data['text_sku'] = $this->language->get('text_sku');
            $data['text_total_incoming'] = $this->language->get('text_total_incoming');
            $data['text_total_outgoing'] = $this->language->get('text_total_outgoing');
            $data['text_net_change'] = $this->language->get('text_net_change');
            $data['text_current_stock'] = $this->language->get('text_current_stock');

            $data['column_date_added'] = $this->language->get('column_date_added');
            $data['column_type'] = $this->language->get('column_type');
            $data['column_quantity'] = $this->language->get('column_quantity');
            $data['column_unit'] = $this->language->get('column_unit');
            $data['column_branch'] = $this->language->get('column_branch');
            $data['column_reference'] = $this->language->get('column_reference');
            $data['column_cost'] = $this->language->get('column_cost');
            $data['column_new_cost'] = $this->language->get('column_new_cost');
            $data['column_user'] = $this->language->get('column_user');

            // Cargar vista
            $this->response->setOutput($this->load->view('catalog/product_movement_print', $data));
        }
    }

    /**
     * Obtener texto del tipo de movimiento
     */
    private function getMovementTypeText($type) {
        $types = array(
            'purchase' => $this->language->get('text_purchase'),
            'sale' => $this->language->get('text_sale'),
            'adjustment_increase' => $this->language->get('text_adjustment_increase'),
            'adjustment_decrease' => $this->language->get('text_adjustment_decrease'),
            'transfer_in' => $this->language->get('text_transfer_in'),
            'transfer_out' => $this->language->get('text_transfer_out'),
            'initial' => $this->language->get('text_initial_stock'),
            'return_in' => $this->language->get('text_return_in'),
            'return_out' => $this->language->get('text_return_out'),
            'scrap' => $this->language->get('text_scrap'),
            'production' => $this->language->get('text_production'),
            'consumption' => $this->language->get('text_consumption'),
            'cost_adjustment' => $this->language->get('text_cost_adjustment')
        );

        return isset($types[$type]) ? $types[$type] : $type;
    }

    /**
     * Agregar movimiento de inventario
     */
    public function addInventoryMovement() {
        $this->load->language('catalog/product');

        $json = array();

        if (!$this->user->hasPermission('modify', 'catalog/product')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            if (isset($this->request->post['product_id']) &&
                isset($this->request->post['branch_id']) &&
                isset($this->request->post['unit_id']) &&
                isset($this->request->post['movement_type']) &&
                isset($this->request->post['quantity'])) {

                $product_id = (int)$this->request->post['product_id'];
                $branch_id = (int)$this->request->post['branch_id'];
                $unit_id = $this->request->post['unit_id'];
                $movement_type = $this->request->post['movement_type'];
                $quantity = (float)$this->request->post['quantity'];
                $direct_cost = isset($this->request->post['direct_cost']) ? (float)$this->request->post['direct_cost'] : 0;
                $reason = isset($this->request->post['reason']) ? $this->request->post['reason'] : '';
                $notes = isset($this->request->post['notes']) ? $this->request->post['notes'] : '';
                $reference = isset($this->request->post['reference']) ? $this->request->post['reference'] : '';

                // Validar datos
                if ($quantity <= 0) {
                    $json['error'] = $this->language->get('error_quantity');
                } else {
                    // Cargar el modelo de inventario
                    $this->load->model('catalog/inventory_manager');

                    // Determinar el tipo de movimiento y la cantidad
                    $movement_quantity = $quantity;
                    if ($movement_type == 'decrease') {
                        $movement_quantity = -$quantity;
                    } else if ($movement_type == 'count') {
                        // Obtener la cantidad actual
                        $current_inventory = $this->model_catalog_product->getProductInventoryByUnit($product_id, $unit_id, $branch_id);
                        $current_quantity = $current_inventory ? $current_inventory['quantity'] : 0;
                        $movement_quantity = $quantity - $current_quantity;
                    }

                    // Determinar el costo
                    $cost = null;
                    if ($movement_type == 'increase' && $direct_cost > 0) {
                        $cost = $direct_cost;
                    }

                    // Actualizar el inventario
                    $result = $this->model_catalog_inventory_manager->updateStock(
                        $product_id,
                        $movement_quantity,
                        $unit_id,
                        $branch_id,
                        'adjustment',
                        0,
                        $notes . ' (' . $reason . ')' . ($reference ? ' Ref: ' . $reference : ''),
                        $cost
                    );

                    if ($result) {
                        $json['success'] = $this->language->get('text_success_movement');
                    } else {
                        $json['error'] = $this->language->get('error_movement');
                    }
                }
            } else {
                $json['error'] = $this->language->get('error_missing_data');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getStockMovements() {
        $this->load->language('catalog/product');

        $json = array();

        if (isset($this->request->get['product_id'])) {
            $this->load->model('catalog/product');

            $results = $this->model_catalog_product->getStockMovements($this->request->get['product_id']);

            foreach ($results as $result) {
                $json['movements'][] = array(
                    'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                    'type'       => $result['type'],
                    'quantity'   => $result['quantity'],
                    'unit_name'  => $result['unit_name'],
                    'reference'  => $result['reference']
                );
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function addStockMovement() {
        $this->load->language('catalog/product');
        $json = array();

        if (!$this->user->hasPermission('modify', 'catalog/product')) {
            $json['error'] = $this->language->get('error_permission');
        } elseif (!isset($this->request->get['product_id'])) {
            $json['error'] = $this->language->get('error_product_id');
        } elseif (!isset($this->request->post['type']) || !isset($this->request->post['quantity']) || !isset($this->request->post['unit_id'])) {
            $json['error'] = $this->language->get('error_missing_data');
        } elseif (!isset($this->request->post['branch_id']) || empty($this->request->post['branch_id'])) {
            $json['error'] = $this->language->get('error_branch_required');
        } else {
            $this->load->model('catalog/product');

            $product_id = (int)$this->request->get['product_id'];
            $movement_type = $this->request->post['type'];
            $quantity = (float)$this->request->post['quantity'];
            $unit_id = (int)$this->request->post['unit_id'];
            $branch_id = (int)$this->request->post['branch_id'];

            if ($quantity <= 0) {
                $json['error'] = $this->language->get('error_quantity_must_be_positive');
            } elseif ($movement_type === 'adjustment_decrease' || $movement_type === 'transfer_out') {
                $inventory_check = $this->model_catalog_product->checkProductInventory(
                    $product_id, $unit_id, $branch_id, $quantity
                );

                if (!$inventory_check['available']) {
                    $json['error'] = sprintf($this->language->get('error_insufficient_stock'),
                        $inventory_check['quantity_available'], $quantity);
                }
            }

            if (!isset($json['error'])) {
                $movement_data = array(
                    'product_id' => $product_id,
                    'type' => $movement_type,
                    'quantity' => $quantity,
                    'unit_id' => $unit_id,
                    'branch_id' => $branch_id,
                    'reference' => isset($this->request->post['reason']) ? $this->request->post['reason'] : '',
                    'user_id' => $this->user->getId()
                );

                if (isset($this->request->post['cost']) && is_numeric($this->request->post['cost'])) {
                    $movement_data['unit_cost'] = (float)$this->request->post['cost'];
                }

                $movementinfo = $this->model_catalog_product->addInventoryMovement($movement_data);
                $movement_id = $movementinfo['movement_id'];

                if ($movement_id) {
                    $json['success'] = $this->language->get('text_movement_added');
                    $json['movement_id'] = $movement_id;

                    $inventory = $this->model_catalog_product->getProductInventoryByUnit(
                        $product_id, $unit_id, $branch_id
                    );

                    if ($inventory) {
                        $json['updated_inventory'] = array(
                            'quantity' => $inventory['quantity'],
                            'quantity_available' => $inventory['quantity_available'],
                            'average_cost' => $inventory['average_cost']
                        );
                    }
                } else {
                    $json['error'] = $this->language->get('error_movement_failed');
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    private function getMovementTypeText($type) {
        $this->load->language('catalog/product');

        $types = array(
            'purchase' => $this->language->get('text_purchase'),
            'sale' => $this->language->get('text_sale'),
            'adjustment_increase' => $this->language->get('text_adjustment_increase'),
            'adjustment_decrease' => $this->language->get('text_adjustment_decrease'),
            'transfer_in' => $this->language->get('text_transfer_in'),
            'transfer_out' => $this->language->get('text_transfer_out')
        );

        return isset($types[$type]) ? $types[$type] : $type;
    }

    public function addStockAdjustment() {
        $this->load->language('catalog/product');
        $json = array();

        if (!$this->user->hasPermission('modify', 'catalog/product')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $this->load->model('catalog/product');

            if (empty($this->request->post['branch_id'])) {
                $json['error'] = $this->language->get('error_branch_required');
            } elseif (empty($this->request->post['type'])) {
                $json['error'] = $this->language->get('error_adjustment_type_required');
            } elseif (empty($this->request->post['items']) || !is_array($this->request->post['items'])) {
                $json['error'] = $this->language->get('error_items_required');
            } else {
                $adjustment_data = array(
                    'branch_id' => $this->request->post['branch_id'],
                    'adjustment_number' => 'ADJ-' . date('YmdHis'),
                    'type' => $this->request->post['type'],
                    'adjustment_date' => date('Y-m-d'),
                    'notes' => isset($this->request->post['notes']) ? $this->request->post['notes'] : '',
                    'status' => 'approved',
                    'items' => $this->request->post['items']
                );

                $adjustment_id = $this->model_catalog_product->addStockAdjustment($adjustment_data);

                if ($adjustment_id) {
                    $json['success'] = $this->language->get('text_adjustment_added');
                    $json['adjustment_id'] = $adjustment_id;
                } else {
                    $json['error'] = $this->language->get('error_adjustment_failed');
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function updateCost() {
        $this->load->language('catalog/product');
        $json = array();

        if (!$this->user->hasPermission('modify', 'catalog/product')) {
            $json['error'] = $this->language->get('error_permission');
        } elseif (isset($this->request->get['product_id'])) {
            $this->load->model('catalog/product');

            if (isset($this->request->post['unit_id']) && isset($this->request->post['new_cost'])) {
                $unit_id = $this->request->post['unit_id'];
                $new_cost = (float)$this->request->post['new_cost'];

                if ($new_cost <= 0) {
                    $json['error'] = $this->language->get('error_invalid_cost');
                } else {
                    $notes = isset($this->request->post['notes']) ? $this->request->post['notes'] : $this->language->get('text_manual_cost_update');

                    $this->db->query("UPDATE " . DB_PREFIX . "product_inventory SET
                        average_cost = '" . (float)$new_cost . "'
                        WHERE product_id = '" . (int)$this->request->get['product_id'] . "'
                        AND unit_id = '" . (int)$unit_id . "'");

                    $this->db->query("INSERT INTO " . DB_PREFIX . "inventory_cost_history SET
                        product_id = '" . (int)$this->request->get['product_id'] . "',
                        unit_id = '" . (int)$unit_id . "',
                        old_cost = '0',
                        new_cost = '" . (float)$new_cost . "',
                        change_reason = 'manual',
                        notes = '" . $this->db->escape($notes) . "',
                        user_id = '" . (int)$this->user->getId() . "',
                        date_added = NOW()");

                    if (isset($this->request->post['update_prices']) && $this->request->post['update_prices'] && isset($this->request->post['margin_percentage'])) {
                        $margin_percentage = (float)$this->request->post['margin_percentage'];
                        $this->model_catalog_product->updateProductPricesByMargin(
                            $this->request->get['product_id'],
                            $unit_id,
                            $new_cost,
                            $margin_percentage
                        );

                        $json['prices_updated'] = true;
                    }

                    $json['success'] = $this->language->get('text_cost_updated');
                }
            } else {
                $json['error'] = $this->language->get('error_required_data');
            }
        } else {
            $json['error'] = $this->language->get('error_product_id');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    protected function validateInventory() {
        if (!isset($this->request->post['product_inventory']) || !is_array($this->request->post['product_inventory'])) {
            return true;
        }

        foreach ($this->request->post['product_inventory'] as $inventory) {
            if (!isset($inventory['branch_id']) || $inventory['branch_id'] === '') {
                $this->error['inventory'] = $this->language->get('error_branch_required');
                return false;
            }
            if (!isset($inventory['unit_id']) || $inventory['unit_id'] === '') {
                $this->error['inventory'] = $this->language->get('error_unit_required');
                return false;
            }
            if (!isset($inventory['quantity']) || !is_numeric($inventory['quantity'])) {
                $this->error['inventory'] = $this->language->get('error_quantity_invalid');
                return false;
            }
        }

        return true;
    }

    protected function validatePricing() {
        if (!isset($this->request->post['product_pricing']) || !is_array($this->request->post['product_pricing'])) {
            $this->error['pricing'] = $this->language->get('error_pricing_required');
            return false;
        }

        foreach ($this->request->post['product_pricing'] as $pricing) {
            if (!isset($pricing['unit_id']) || $pricing['unit_id'] === '') {
                $this->error['pricing'] = $this->language->get('error_unit_required');
                return false;
            }
            if (!isset($pricing['base_price']) || !is_numeric($pricing['base_price'])) {
                $this->error['pricing'] = $this->language->get('error_base_price_invalid');
                return false;
            }
        }

        return true;
    }

    protected function validateUnits() {
        if (!isset($this->request->post['product_unit']) || !is_array($this->request->post['product_unit'])) {
            $this->error['unit'] = $this->language->get('error_unit_required');
            return false;
        }

        $hasBaseUnit = false;

        foreach ($this->request->post['product_unit'] as $index => $unit) {
            if (!isset($unit['unit_id']) || empty($unit['unit_id'])) {
                $this->error['unit'] = $this->language->get('error_unit_id_required');
                return false;
            }

            if (isset($unit['unit_type']) && $unit['unit_type'] === 'additional') {
                if (!isset($unit['conversion_factor']) || $unit['conversion_factor'] <= 0) {
                    $this->error['unit'] = $this->language->get('error_conversion_factor');
                    return false;
                }
            }

            if (isset($unit['unit_type']) && $unit['unit_type'] === 'base') {
                if ($hasBaseUnit) {
                    $this->request->post['product_unit'][$index]['unit_type'] = 'additional';
                    if (!isset($unit['conversion_factor']) || $unit['conversion_factor'] <= 0) {
                        $this->request->post['product_unit'][$index]['conversion_factor'] = 1;
                    }
                } else {
                    $hasBaseUnit = true;
                    $this->request->post['product_unit'][$index]['conversion_factor'] = 1;
                }
            }
        }

        if (!$hasBaseUnit && !empty($this->request->post['product_unit'])) {
            return false;
        }

        return true;
    }

    protected function validateOptionQuantities($productOptions, $productInventory) {
        if (empty($productOptions) || empty($productInventory)) {
            return true;
        }

        foreach ($productOptions as $option) {
            if (!isset($option['unit_id']) || !isset($option['product_option_value'])) {
                continue;
            }

            $unitId = $option['unit_id'];
            $totalOptionQuantity = 0;

            foreach ($option['product_option_value'] as $optionValue) {
                $totalOptionQuantity += (float)($optionValue['quantity'] ?? 0);
            }

            $totalAvailable = 0;
            foreach ($productInventory as $inventory) {
                if ($inventory['unit_id'] == $unitId) {
                    $totalAvailable += (float)($inventory['quantity_available'] ?? 0);
                }
            }

            if ($totalOptionQuantity > $totalAvailable) {
                $this->error['options'] = "كمية الخيارات (" . $totalOptionQuantity . ") للوحدة تتجاوز المخزون المتاح (" . $totalAvailable . ")";
                return false;
            }
        }

        return true;
    }

    public function getPurchaseHistory() {
        $this->load->language('catalog/product');
        $json = array();

        if (isset($this->request->get['product_id'])) {
            $this->load->model('catalog/product');
            $product_id = (int)$this->request->get['product_id'];

            $purchases = $this->model_catalog_product->getProductPurchaseHistory($product_id);

            foreach ($purchases as $purchase) {
                $unit_name = $this->model_catalog_product->getUnitName($purchase['unit_id']);
$supplier_name = $this->model_catalog_product->getSupplierName($purchase['supplier_id']);

               $json['history'][] = array(
                   'po_id' => $purchase['po_id'],
                   'po_number' => $purchase['po_number'],
                   'order_date' => date($this->language->get('date_format_short'), strtotime($purchase['order_date'])),
                   'supplier_id' => $purchase['supplier_id'],
                   'supplier_name' => $supplier_name,
                   'unit_id' => $purchase['unit_id'],
                   'unit_name' => $unit_name,
                   'quantity' => $purchase['quantity'],
                   'unit_price' => $this->currency->format($purchase['unit_price'], $this->config->get('config_currency')),
                   'receipt_number' => $purchase['receipt_number'],
                   'receipt_date' => $purchase['receipt_date'] ? date($this->language->get('date_format_short'), strtotime($purchase['receipt_date'])) : '',
                   'status' => $purchase['status']
               );
           }
       }

       $this->response->addHeader('Content-Type: application/json');
       $this->response->setOutput(json_encode($json));
   }

   public function getSupplierPricing() {
       $this->load->language('catalog/product');
       $json = array();

       if (isset($this->request->get['product_id'])) {
           $this->load->model('catalog/product');
           $product_id = (int)$this->request->get['product_id'];

           $pricing = $this->model_catalog_product->getProductSupplierPricing($product_id);

           foreach ($pricing as $price) {
               $unit_name = $this->model_catalog_product->getUnitName($price['unit_id']);
               $supplier_name = $this->model_catalog_product->getSupplierName($price['supplier_id']);
               $currency_code = $this->model_catalog_product->getCurrencyCode($price['currency_id']);

               $json['pricing'][] = array(
                   'price_id' => $price['price_id'],
                   'supplier_id' => $price['supplier_id'],
                   'supplier_name' => $supplier_name,
                   'unit_id' => $price['unit_id'],
                   'unit_name' => $unit_name,
                   'price' => $price['price'],
                   'currency_id' => $price['currency_id'],
                   'currency_code' => $currency_code,
                   'min_quantity' => $price['min_quantity'],
                   'last_purchase_date' => $price['last_purchase_date'] ? date($this->language->get('date_format_short'), strtotime($price['last_purchase_date'])) : '',
                   'is_default' => $price['is_default'] ? true : false
               );
           }
       }

       $this->response->addHeader('Content-Type: application/json');
       $this->response->setOutput(json_encode($json));
   }

   public function getSupplierPriceForm() {
       $this->load->language('catalog/product');

       $data = array();

       $data['user_token'] = $this->session->data['user_token'];
       $data['product_id'] = isset($this->request->get['product_id']) ? (int)$this->request->get['product_id'] : 0;

       $this->load->model('catalog/product');
       $data['units'] = $this->model_catalog_product->getProductUnits($data['product_id']);

       $this->load->model('catalog/supplier');
       $data['suppliers'] = $this->model_catalog_supplier->getSuppliers();

       $this->load->model('localisation/currency');
       $data['currencies'] = $this->model_localisation_currency->getCurrencies();

       if (isset($this->request->get['price_id'])) {
           $price_id = (int)$this->request->get['price_id'];
           $price_info = $this->model_catalog_product->getSupplierPrice($price_id);

           if ($price_info) {
               $data['price_id'] = $price_id;
               $data['supplier_id'] = $price_info['supplier_id'];
               $data['unit_id'] = $price_info['unit_id'];
               $data['price'] = $price_info['price'];
               $data['currency_id'] = $price_info['currency_id'];
               $data['min_quantity'] = $price_info['min_quantity'];
               $data['is_default'] = $price_info['is_default'];
           }
       } else {
           $data['price_id'] = 0;
           $data['supplier_id'] = 0;
           $data['unit_id'] = 0;
           $data['price'] = 0;
           $data['currency_id'] = $this->config->get('config_currency_id');
           $data['min_quantity'] = 1;
           $data['is_default'] = 0;
       }

       $this->response->setOutput($this->load->view('catalog/supplier_price_form', $data));
   }

   public function saveSupplierPrice() {
       $this->load->language('catalog/product');
       $json = array();

       if (!$this->user->hasPermission('modify', 'catalog/product')) {
           $json['error'] = $this->language->get('error_permission');
       } elseif (isset($this->request->get['product_id'])) {
           $this->load->model('catalog/product');
           $product_id = (int)$this->request->get['product_id'];

           $price_data = array(
               'product_id' => $product_id,
               'supplier_id' => isset($this->request->post['supplier_id']) ? (int)$this->request->post['supplier_id'] : 0,
               'unit_id' => isset($this->request->post['unit_id']) ? (int)$this->request->post['unit_id'] : 0,
               'price' => isset($this->request->post['price']) ? (float)$this->request->post['price'] : 0,
               'currency_id' => isset($this->request->post['currency_id']) ? (int)$this->request->post['currency_id'] : 0,
               'min_quantity' => isset($this->request->post['min_quantity']) ? (int)$this->request->post['min_quantity'] : 1,
               'is_default' => isset($this->request->post['is_default']) ? 1 : 0
           );

           if (!$price_data['supplier_id']) {
               $json['error'] = $this->language->get('error_supplier_required');
           } elseif (!$price_data['unit_id']) {
               $json['error'] = $this->language->get('error_unit_required');
           } elseif ($price_data['price'] <= 0) {
               $json['error'] = $this->language->get('error_price_required');
           } else {
               if (isset($this->request->post['price_id']) && (int)$this->request->post['price_id'] > 0) {
                   $price_id = (int)$this->request->post['price_id'];
                   $this->model_catalog_product->updateSupplierPrice($price_id, $price_data);
                   $json['success'] = $this->language->get('text_price_updated');
               } else {
                   $price_id = $this->model_catalog_product->addSupplierPrice($price_data);
                   $json['success'] = $this->language->get('text_price_added');
               }

               $json['price_id'] = $price_id;
           }
       } else {
           $json['error'] = $this->language->get('error_product');
       }

       $this->response->addHeader('Content-Type: application/json');
       $this->response->setOutput(json_encode($json));
   }

   public function deleteSupplierPrice() {
       $this->load->language('catalog/product');
       $json = array();

       if (!$this->user->hasPermission('modify', 'catalog/product')) {
           $json['error'] = $this->language->get('error_permission');
       } elseif (isset($this->request->post['price_id'])) {
           $this->load->model('catalog/product');
           $price_id = (int)$this->request->post['price_id'];

           $this->model_catalog_product->deleteSupplierPrice($price_id);
           $json['success'] = $this->language->get('text_price_deleted');
       } else {
           $json['error'] = $this->language->get('error_price_id');
       }

       $this->response->addHeader('Content-Type: application/json');
       $this->response->setOutput(json_encode($json));
   }

   public function generateBarcode() {
       $this->load->language('catalog/product');

       $type = isset($this->request->get['type']) ? $this->request->get['type'] : 'CODE128';
       $value = isset($this->request->get['value']) ? $this->request->get['value'] : '';

       if (empty($value)) {
           $this->response->setOutput($this->load->view('error/not_found', []));
           return;
       }

       require_once(DIR_SYSTEM . 'library/Picqer/Barcode/BarcodeGenerator.php');
       require_once(DIR_SYSTEM . 'library/Picqer/Barcode/BarcodeGeneratorPNG.php');
       $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();

       try {
           $barcode_type = $this->getBarcodeType($type);

           $barcode = $generator->getBarcode($value, $barcode_type, 2, 60);

           $this->response->addHeader('Content-Type: image/png');
           $this->response->addHeader('Content-Disposition: inline; filename="barcode-' . $value . '.png"');
           $this->response->addHeader('Cache-Control: max-age=86400');

           $this->response->setOutput($barcode);
       } catch (\Exception $e) {
           error_log('Barcode Generation Error: ' . $e->getMessage());

           $this->response->setOutput($this->load->view('error/not_found', []));
       }
   }

   private function getBarcodeType($type) {
       switch (strtoupper($type)) {
           case 'CODE128':
               return 'C128';
           case 'EAN':
               return 'EAN13';
           default:
               return 'C128';
       }
   }

    /**
     * Handles inventory adjustments from the product page inventory tab
     * This function processes the AJAX request from InventoryManager.js
     */
    public function saveInventoryMovement() {
        $this->load->language('catalog/product');
        $json = array();

        if (!$this->user->hasPermission('modify', 'catalog/product')) {
            $json['error'] = $this->language->get('error_permission');
        } elseif (!isset($this->request->get['product_id'])) {
            $json['error'] = $this->language->get('error_product_id');
        } elseif (!isset($this->request->post['branch_id']) || !isset($this->request->post['unit_id'])
                 || !isset($this->request->post['movement_type']) || !isset($this->request->post['quantity'])) {
            $json['error'] = $this->language->get('error_missing_data');
        } else {
            $this->load->model('catalog/product');

            $product_id = (int)$this->request->get['product_id'];
            $branch_id = (int)$this->request->post['branch_id'];
            $unit_id = (int)$this->request->post['unit_id'];
            $movement_type = $this->request->post['movement_type'];
            $quantity = (float)$this->request->post['quantity'];
            $reason = isset($this->request->post['reason']) ? $this->request->post['reason'] : '';
            $notes = isset($this->request->post['notes']) ? $this->request->post['notes'] : '';
            $reference = isset($this->request->post['reference']) ? $this->request->post['reference'] : '';
            $is_consignment = isset($this->request->post['is_consignment']) ? (int)$this->request->post['is_consignment'] : 0;

            // Validate quantity
            if ($quantity <= 0) {
                $json['error'] = $this->language->get('error_quantity_must_be_positive');
            }
            // For decrease and count adjustments, check if enough stock is available
            elseif (($movement_type === 'decrease' && $quantity > 0) ||
                   ($movement_type === 'count')) {

                $inventory = $this->model_catalog_product->getProductInventoryByUnit(
                    $product_id, $unit_id, $branch_id
                );

                if ($movement_type === 'decrease' &&
                    (!$inventory || $inventory['quantity'] < $quantity)) {
                    $available = $inventory ? $inventory['quantity'] : 0;
                    $json['error'] = sprintf($this->language->get('error_insufficient_stock'),
                        $available, $quantity);
                }
            }

            if (!isset($json['error'])) {
                // Determine the actual movement type for the inventory_movement table
                $actual_movement_type = '';
                if ($movement_type === 'increase') {
                    $actual_movement_type = 'adjustment_increase';
                } elseif ($movement_type === 'decrease') {
                    $actual_movement_type = 'adjustment_decrease';
                } elseif ($movement_type === 'count') {
                    // For count, we need to compare with current quantity to determine if it's increase or decrease
                    $inventory = $this->model_catalog_product->getProductInventoryByUnit(
                        $product_id, $unit_id, $branch_id
                    );
                    $current_qty = $inventory ? $inventory['quantity'] : 0;

                    if ($quantity > $current_qty) {
                        $actual_movement_type = 'adjustment_increase';
                        $quantity = $quantity - $current_qty; // Adjust to reflect the actual increase
                    } elseif ($quantity < $current_qty) {
                        $actual_movement_type = 'adjustment_decrease';
                        $quantity = $current_qty - $quantity; // Adjust to reflect the actual decrease
                    } else {
                        // No change in quantity, no movement needed
                        $json['success'] = $this->language->get('text_no_change_needed');
                        $this->response->addHeader('Content-Type: application/json');
                        $this->response->setOutput(json_encode($json));
                        return;
                    }
                }

                // Prepare movement data
                $movement_data = array(
                    'product_id' => $product_id,
                    'type' => $actual_movement_type,
                    'quantity' => $quantity,
                    'unit_id' => $unit_id,
                    'branch_id' => $branch_id,
                    'reference' => $reason,
                    'notes' => $notes,
                    'user_id' => $this->user->getId()
                );

                // If cost is provided for increases, include it
                if ($movement_type === 'increase' && isset($this->request->post['cost']) && is_numeric($this->request->post['cost'])) {
                    $movement_data['unit_cost'] = (float)$this->request->post['cost'];
                }

                // Add the inventory movement
                $movementinfo = $this->model_catalog_product->addInventoryMovement($movement_data);

                if ($movementinfo && isset($movementinfo['movement_id'])) {
                    $json['success'] = $this->language->get('text_movement_added');
                    $json['movement_id'] = $movementinfo['movement_id'];

                    // Return updated inventory data
                    $inventory = $this->model_catalog_product->getProductInventoryByUnit(
                        $product_id, $unit_id, $branch_id
                    );

                    if ($inventory) {
                        $json['updated_inventory'] = array(
                            'quantity' => $inventory['quantity'],
                            'quantity_available' => $inventory['quantity_available'],
                            'average_cost' => $inventory['average_cost']
                        );
                    }
                } else {
                    $json['error'] = $this->language->get('error_movement_failed');
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Returns basic product information for the quick adjustment modal
     */
    public function getProductInfo() {
        $this->load->language('catalog/product');
        $json = array();

        if (!isset($this->request->get['product_id'])) {
            $json['error'] = $this->language->get('error_product_id');
        } else {
            $this->load->model('catalog/product');
            $product_id = (int)$this->request->get['product_id'];

            $product_info = $this->model_catalog_product->getProduct($product_id);

            if ($product_info) {
                $this->load->model('tool/image');

                if ($product_info['image'] && is_file(DIR_IMAGE . $product_info['image'])) {
                    $image = $this->model_tool_image->resize($product_info['image'], 40, 40);
                } else {
                    $image = $this->model_tool_image->resize('no_image.png', 40, 40);
                }

                // Get product description
                $this->load->model('catalog/product');
                $product_description = $this->model_catalog_product->getProductDescriptions($product_id);
                $name = '';

                if (isset($product_description[1])) { // Assuming language_id 1 is the default
                    $name = $product_description[1]['name'];
                } elseif (!empty($product_description)) {
                    // If language_id 1 not found, take the first available
                    $first_description = reset($product_description);
                    $name = $first_description['name'];
                }

                if (empty($name)) {
                    $name = $product_info['name'] ?? ('Product #' . $product_id);
                }

                $json['success'] = true;
                $json['product_id'] = $product_info['product_id'];
                $json['name'] = $name;
                $json['model'] = $product_info['model'];
                $json['image'] = $image;
            } else {
                $json['error'] = $this->language->get('error_product_not_found');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Returns the current quantity for a product in a specific branch and unit
     */
    public function getProductQuantity() {
        $this->load->language('catalog/product');
        $json = array();

        if (!isset($this->request->get['product_id'])) {
            $json['error'] = $this->language->get('error_product_id');
        } elseif (!isset($this->request->post['branch_id']) || !isset($this->request->post['unit_id'])) {
            $json['error'] = $this->language->get('error_missing_data');
        } else {
            $this->load->model('catalog/product');

            $product_id = (int)$this->request->get['product_id'];
            $branch_id = (int)$this->request->post['branch_id'];
            $unit_id = (int)$this->request->post['unit_id'];

            $quantity = $this->model_catalog_product->getProductQuantity($product_id, $unit_id, $branch_id);

            $json['quantity'] = $quantity;
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Returns all branches/warehouses for the quick adjustment modal
     */
    public function getBranches() {
        $this->load->language('catalog/product');
        $json = array();

        $this->load->model('catalog/product');
        $branches = $this->model_catalog_product->getBranches();

        $json['branches'] = array();

        foreach ($branches as $branch) {
            $json['branches'][] = array(
                'branch_id' => $branch['branch_id'],
                'name' => $branch['name']
            );
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Process batch inventory adjustments for multiple products
     */
    public function batchInventoryAdjustment() {
        $this->load->language('catalog/product');
        $json = array();

        if (!$this->user->hasPermission('modify', 'catalog/product')) {
            $json['error'] = $this->language->get('error_permission');
        } elseif (empty($this->request->post['adjustments']) || !is_array($this->request->post['adjustments'])) {
            $json['error'] = $this->language->get('error_items_required');
        } else {
            $this->load->model('catalog/product');

            $adjustments = $this->request->post['adjustments'];
            $branch_id = isset($this->request->post['branch_id']) ? (int)$this->request->post['branch_id'] : 0;
            $reason = isset($this->request->post['reason']) ? $this->request->post['reason'] : '';
            $notes = isset($this->request->post['notes']) ? $this->request->post['notes'] : '';

            if (!$branch_id) {
                $json['error'] = $this->language->get('error_branch_required');
            } else {
                $success_count = 0;
                $error_count = 0;
                $errors = array();

                foreach ($adjustments as $adjustment) {
                    if (empty($adjustment['product_id']) || empty($adjustment['unit_id'])
                        || !isset($adjustment['quantity']) || empty($adjustment['movement_type'])) {
                        $error_count++;
                        continue;
                    }

                    $product_id = (int)$adjustment['product_id'];
                    $unit_id = (int)$adjustment['unit_id'];
                    $quantity = (float)$adjustment['quantity'];
                    $movement_type = $adjustment['movement_type'];

                    // Skip invalid quantities
                    if ($quantity <= 0) {
                        $errors[] = sprintf($this->language->get('error_product_quantity_invalid'),
                            $adjustment['product_name']);
                        $error_count++;
                        continue;
                    }

                    // Determine the actual movement type
                    $actual_movement_type = '';
                    if ($movement_type === 'increase') {
                        $actual_movement_type = 'adjustment_increase';
                    } elseif ($movement_type === 'decrease') {
                        $actual_movement_type = 'adjustment_decrease';

                        // Check if there's enough stock
                        $inventory = $this->model_catalog_product->getProductInventoryByUnit(
                            $product_id, $unit_id, $branch_id
                        );

                        if (!$inventory || $inventory['quantity'] < $quantity) {
                            $errors[] = sprintf($this->language->get('error_product_insufficient_stock'),
                                $adjustment['product_name'],
                                $inventory ? $inventory['quantity'] : 0,
                                $quantity);
                            $error_count++;
                            continue;
                        }
                    } elseif ($movement_type === 'count') {
                        // For count, we need to compare with current quantity
                        $inventory = $this->model_catalog_product->getProductInventoryByUnit(
                            $product_id, $unit_id, $branch_id
                        );
                        $current_qty = $inventory ? $inventory['quantity'] : 0;

                        if ($quantity > $current_qty) {
                            $actual_movement_type = 'adjustment_increase';
                            $quantity = $quantity - $current_qty; // Adjust to reflect the actual increase
                        } elseif ($quantity < $current_qty) {
                            $actual_movement_type = 'adjustment_decrease';
                            $quantity = $current_qty - $quantity; // Adjust to reflect the actual decrease
                        } else {
                            // No change needed for this product
                            continue;
                        }
                    }

                    // Process the movement
                    $movement_data = array(
                        'product_id' => $product_id,
                        'type' => $actual_movement_type,
                        'quantity' => $quantity,
                        'unit_id' => $unit_id,
                        'branch_id' => $branch_id,
                        'reference' => $reason,
                        'notes' => $notes,
                        'user_id' => $this->user->getId()
                    );

                    // Add cost for increases if provided
                    if ($movement_type === 'increase' && isset($adjustment['cost']) && is_numeric($adjustment['cost'])) {
                        $movement_data['unit_cost'] = (float)$adjustment['cost'];
                    }

                    // Add the movement
                    $result = $this->model_catalog_product->addInventoryMovement($movement_data);

                    if ($result && isset($result['movement_id'])) {
                        $success_count++;
                    } else {
                        $errors[] = sprintf($this->language->get('error_product_adjustment_failed'),
                            $adjustment['product_name']);
                        $error_count++;
                    }
                }

                if ($success_count > 0) {
                    $json['success'] = sprintf($this->language->get('text_batch_adjustment_success'), $success_count);
                }

                if ($error_count > 0) {
                    $json['warning'] = sprintf($this->language->get('text_batch_adjustment_partial'),
                        $success_count, $error_count);
                    $json['errors'] = $errors;
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // ===== Inventory Management Methods =====

    /**
     * API method to get product inventory information
     */
    public function getProductInventory() {
        $json = array();

        if (isset($this->request->post['product_id'])) {
            $product_id = (int)$this->request->post['product_id'];

            $this->load->model('catalog/product');

            if (isset($this->request->post['branch_id']) && isset($this->request->post['unit_id'])) {
                // Get specific inventory
                $branch_id = (int)$this->request->post['branch_id'];
                $unit_id = (int)$this->request->post['unit_id'];

                // Get inventory information
                $inventory = $this->model_catalog_product->getProductInventoryItem($product_id, $branch_id, $unit_id);

                if ($inventory) {
                    // Get unit and branch names
                    $this->load->model('localisation/unit');
                    $this->load->model('branch/branch');

                    $unit_info = $this->model_localisation_unit->getUnit($unit_id);
                    $branch_info = $this->model_branch_branch->getBranch($branch_id);

                    $json['inventory'] = $inventory;
                    $json['unit_name'] = $unit_info ? $unit_info['desc_en'] : '';
                    $json['branch_name'] = $branch_info ? $branch_info['name'] : '';
                    $json['success'] = true;
                } else {
                    $json['error'] = $this->language->get('error_no_inventory_found');
                }
            } else if (isset($this->request->post['refresh_all']) && $this->request->post['refresh_all']) {
                // Get all inventory for the product
                $inventory_data = $this->model_catalog_product->getProductInventory($product_id);
                $recent_movements = $this->model_catalog_product->getInventoryMovements($product_id, array('limit' => 5));

                $json['inventory_data'] = $inventory_data;
                $json['recent_movements'] = $recent_movements;
                $json['success'] = true;
            } else {
                $json['error'] = $this->language->get('error_missing_parameters');
            }
        } else {
            $json['error'] = $this->language->get('error_product_id');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * API method to get inventory movements
     */
    public function getInventoryMovements() {
        $json = array();

        if (isset($this->request->post['product_id'])) {
            $product_id = (int)$this->request->post['product_id'];

            $filter_data = array();

            // Apply filters if provided
            if (isset($this->request->post['filter_type']) && $this->request->post['filter_type'] != '') {
                $filter_data['filter_type'] = $this->request->post['filter_type'];
            }

            if (isset($this->request->post['filter_branch_id']) && $this->request->post['filter_branch_id'] > 0) {
                $filter_data['filter_branch_id'] = (int)$this->request->post['filter_branch_id'];
            }

            if (isset($this->request->post['filter_date_start']) && $this->request->post['filter_date_start'] != '') {
                $filter_data['filter_date_start'] = $this->request->post['filter_date_start'];
            }

            if (isset($this->request->post['filter_date_end']) && $this->request->post['filter_date_end'] != '') {
                $filter_data['filter_date_end'] = $this->request->post['filter_date_end'];
            }

            if (isset($this->request->post['start']) && isset($this->request->post['limit'])) {
                $filter_data['start'] = (int)$this->request->post['start'];
                $filter_data['limit'] = (int)$this->request->post['limit'];
            } else {
                $filter_data['start'] = 0;
                $filter_data['limit'] = 25; // Default limit
            }

            $this->load->model('catalog/product');

            // Get movements with the given filters
            $movements = $this->model_catalog_product->getInventoryMovements($product_id, $filter_data);
            $total_movements = $this->model_catalog_product->getTotalInventoryMovements($product_id, $filter_data);

            // Prepare movement statistics
            $stats = $this->model_catalog_product->getInventoryMovementStats($product_id, $filter_data);

            $json['movements'] = $movements;
            $json['total'] = $total_movements;
            $json['stats'] = $stats;
            $json['success'] = true;
        } else {
            $json['error'] = $this->language->get('error_product_id');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * API method to save inventory movement
     */
    public function saveInventoryMovement() {
        $json = array();

        if (!$this->user->hasPermission('modify', 'catalog/product')) {
            $json['error'] = $this->language->get('error_permission');
        } else if (!isset($this->request->post['product_id']) || !isset($this->request->post['branch_id']) ||
                   !isset($this->request->post['unit_id']) || !isset($this->request->post['movement_type']) ||
                   !isset($this->request->post['quantity'])) {
            $json['error'] = $this->language->get('error_missing_movement_data');
        } else {
            $this->load->model('catalog/product');

            $product_id = (int)$this->request->post['product_id'];
            $branch_id = (int)$this->request->post['branch_id'];
            $unit_id = (int)$this->request->post['unit_id'];
            $movement_type = $this->request->post['movement_type'];
            $quantity = (float)$this->request->post['quantity'];
            $reason = isset($this->request->post['reason']) ? $this->request->post['reason'] : '';
            $notes = isset($this->request->post['notes']) ? $this->request->post['notes'] : '';
            $reference = isset($this->request->post['reference']) ? $this->request->post['reference'] : '';

            // For incoming movements, get the cost
            $cost = 0;
            if (in_array($movement_type, array('adjustment_increase', 'stock_count')) && isset($this->request->post['cost'])) {
                $cost = (float)$this->request->post['cost'];
            }

            // Prepare movement data
            $movement_data = array(
                'product_id' => $product_id,
                'branch_id' => $branch_id,
                'unit_id' => $unit_id,
                'type' => $movement_type,
                'quantity' => $quantity,
                'cost' => $cost,
                'reference' => $reference,
                'reason' => $reason,
                'notes' => $notes,
                'user_id' => $this->user->getId()
            );

            try {
                // Validate movement
                $validation = $this->validateInventoryMovement($movement_data);

                if ($validation['status']) {
                    // Add the movement
                    $movement_id = $this->model_catalog_product->addStockMovement($movement_data);

                    if ($movement_id) {
                        $json['success'] = $this->language->get('text_movement_added');
                        $json['movement_id'] = $movement_id;
                    } else {
                        $json['error'] = $this->language->get('error_movement_failed');
                    }
                } else {
                    $json['error'] = $validation['message'];
                }
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Add stock adjustment
     */
    public function addStockAdjustment() {
        $json = array();

        if (!$this->user->hasPermission('modify', 'catalog/product')) {
            $json['error'] = $this->language->get('error_permission');
        } else if (!isset($this->request->post['product_id']) || !isset($this->request->post['branch_id']) ||
                   !isset($this->request->post['unit_id']) || !isset($this->request->post['adjustment_type']) ||
                   !isset($this->request->post['quantity']) || !isset($this->request->post['reason'])) {
            $json['error'] = $this->language->get('error_missing_data');
        } else {
            $this->load->model('catalog/product');

            $product_id = (int)$this->request->post['product_id'];
            $branch_id = (int)$this->request->post['branch_id'];
            $unit_id = (int)$this->request->post['unit_id'];
            $adjustment_type = $this->request->post['adjustment_type'];
            $quantity = (float)$this->request->post['quantity'];
            $reason = $this->request->post['reason'];
            $notes = isset($this->request->post['notes']) ? $this->request->post['notes'] : '';

            // Convert adjustment type to movement type
            $movement_type = '';
            if ($adjustment_type == 'increase') {
                $movement_type = 'adjustment_increase';
            } else if ($adjustment_type == 'decrease') {
                $movement_type = 'adjustment_decrease';
            } else if ($adjustment_type == 'count') {
                $movement_type = 'stock_count';
            } else {
                $json['error'] = $this->language->get('error_invalid_adjustment_type');
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode($json));
                return;
            }

            // For incoming movements, get the cost
            $cost = 0;
            if (in_array($movement_type, array('adjustment_increase', 'stock_count')) && isset($this->request->post['cost'])) {
                $cost = (float)$this->request->post['cost'];
            }

            // Prepare movement data
            $movement_data = array(
                'product_id' => $product_id,
                'branch_id' => $branch_id,
                'unit_id' => $unit_id,
                'type' => $movement_type,
                'quantity' => $quantity,
                'cost' => $cost,
                'reference' => $reason,
                'reason' => $reason,
                'notes' => $notes,
                'user_id' => $this->user->getId()
            );

            try {
                // Validate movement
                $validation = $this->validateInventoryMovement($movement_data);

                if ($validation['status']) {
                    // Add the movement
                    $movement_id = $this->model_catalog_product->addStockMovement($movement_data);

                    if ($movement_id) {
                        $json['success'] = $this->language->get('text_adjustment_added');
                        $json['movement_id'] = $movement_id;
                    } else {
                        $json['error'] = $this->language->get('error_adjustment_failed');
                    }
                } else {
                    $json['error'] = $validation['message'];
                }
            } catch (Exception $e) {
                $json['error'] = $e->getMessage();
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Get cost history for a product
     */
    public function getCostHistory() {
        $json = array();

        if (isset($this->request->post['product_id'])) {
            $product_id = (int)$this->request->post['product_id'];

            $filter_data = array();

            // Apply filters if provided
            if (isset($this->request->post['filter_unit_id']) && $this->request->post['filter_unit_id'] > 0) {
                $filter_data['filter_unit_id'] = (int)$this->request->post['filter_unit_id'];
            }

            if (isset($this->request->post['filter_branch_id']) && $this->request->post['filter_branch_id'] > 0) {
                $filter_data['filter_branch_id'] = (int)$this->request->post['filter_branch_id'];
            }

            if (isset($this->request->post['filter_date_start']) && $this->request->post['filter_date_start'] != '') {
                $filter_data['filter_date_start'] = $this->request->post['filter_date_start'];
            }

            if (isset($this->request->post['filter_date_end']) && $this->request->post['filter_date_end'] != '') {
                $filter_data['filter_date_end'] = $this->request->post['filter_date_end'];
            }

            if (isset($this->request->post['start']) && isset($this->request->post['limit'])) {
                $filter_data['start'] = (int)$this->request->post['start'];
                $filter_data['limit'] = (int)$this->request->post['limit'];
            } else {
                $filter_data['start'] = 0;
                $filter_data['limit'] = 25; // Default limit
            }

            $this->load->model('catalog/product');

            // Get cost history
            $cost_history = $this->model_catalog_product->getCostHistory($product_id, $filter_data);
            $total = $this->model_catalog_product->getTotalCostHistory($product_id, $filter_data);

            $json['cost_history'] = $cost_history;
            $json['total'] = $total;
            $json['success'] = true;
        } else {
            $json['error'] = $this->language->get('error_product_id');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Update product cost
     */
    public function updateCost() {
        $json = array();

        if (!$this->user->hasPermission('modify', 'catalog/product')) {
            $json['error'] = $this->language->get('error_permission');
        } else if (!isset($this->request->post['product_id']) || !isset($this->request->post['branch_id']) ||
                   !isset($this->request->post['unit_id']) || !isset($this->request->post['new_cost'])) {
            $json['error'] = $this->language->get('error_missing_data');
        } else {
            $this->load->model('catalog/product');

            $product_id = (int)$this->request->post['product_id'];
            $branch_id = (int)$this->request->post['branch_id'];
            $unit_id = (int)$this->request->post['unit_id'];
            $new_cost = (float)$this->request->post['new_cost'];
            $reason = isset($this->request->post['reason']) ? $this->request->post['reason'] : 'manual';
            $notes = isset($this->request->post['notes']) ? $this->request->post['notes'] : '';

            // Get current inventory
            $inventory = $this->model_catalog_product->getProductInventoryItem($product_id, $branch_id, $unit_id);

            if (!$inventory) {
                $json['error'] = $this->language->get('error_no_inventory_found');
            } else if ($new_cost <= 0) {
                $json['error'] = $this->language->get('error_invalid_cost');
            } else {
                try {
                    // Update the cost
                    $result = $this->model_catalog_product->updateInventoryCost($product_id, $branch_id, $unit_id, $new_cost, $reason, $notes);

                    if ($result) {
                        $json['success'] = $this->language->get('text_cost_updated');

                        // Update pricing if requested
                        if (isset($this->request->post['update_prices']) && $this->request->post['update_prices']) {
                            $margin = isset($this->request->post['margin']) ? (float)$this->request->post['margin'] : 0;

                            if ($margin >= 0 && $margin <= 100) {
                                $this->model_catalog_product->updatePricesBasedOnCost($product_id, $unit_id, $new_cost, $margin);
                                $json['success'] .= ' ' . $this->language->get('text_prices_updated');
                            } else {
                                $json['error'] = $this->language->get('error_invalid_margin');
                            }
                        }
                    } else {
                        $json['error'] = $this->language->get('error_update_failed');
                    }
                } catch (Exception $e) {
                    $json['error'] = $e->getMessage();
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Get price history for a product
     */
    public function getPriceHistory() {
        $json = array();

        if (isset($this->request->post['product_id'])) {
            $product_id = (int)$this->request->post['product_id'];

            $filter_data = array();

            // Apply filters if provided
            if (isset($this->request->post['filter_unit_id']) && $this->request->post['filter_unit_id'] > 0) {
                $filter_data['filter_unit_id'] = (int)$this->request->post['filter_unit_id'];
            }

            if (isset($this->request->post['filter_price_type']) && $this->request->post['filter_price_type'] != '') {
                $filter_data['filter_price_type'] = $this->request->post['filter_price_type'];
            }

            if (isset($this->request->post['filter_date_start']) && $this->request->post['filter_date_start'] != '') {
                $filter_data['filter_date_start'] = $this->request->post['filter_date_start'];
            }

            if (isset($this->request->post['filter_date_end']) && $this->request->post['filter_date_end'] != '') {
                $filter_data['filter_date_end'] = $this->request->post['filter_date_end'];
            }

            if (isset($this->request->post['start']) && isset($this->request->post['limit'])) {
                $filter_data['start'] = (int)$this->request->post['start'];
                $filter_data['limit'] = (int)$this->request->post['limit'];
            } else {
                $filter_data['start'] = 0;
                $filter_data['limit'] = 25; // Default limit
            }

            $this->load->model('catalog/product');

            // Get price history
            $price_history = $this->model_catalog_product->getPriceHistory($product_id, $filter_data);
            $total = $this->model_catalog_product->getTotalPriceHistory($product_id, $filter_data);

            $json['price_history'] = $price_history;
            $json['total'] = $total;
            $json['success'] = true;
        } else {
            $json['error'] = $this->language->get('error_product_id');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Calculate weight average cost
     */
    public function calculateWeightedAverageCost() {
        $json = array();

        if (isset($this->request->post['current_quantity']) && isset($this->request->post['current_cost']) &&
            isset($this->request->post['new_quantity']) && isset($this->request->post['new_cost'])) {

            $current_quantity = (float)$this->request->post['current_quantity'];
            $current_cost = (float)$this->request->post['current_cost'];
            $new_quantity = (float)$this->request->post['new_quantity'];
            $new_cost = (float)$this->request->post['new_cost'];

            if ($current_quantity < 0 || $new_quantity < 0) {
                $json['error'] = $this->language->get('error_negative_quantity');
            } else if ($current_cost < 0 || $new_cost < 0) {
                $json['error'] = $this->language->get('error_negative_cost');
            } else {
                $total_value = ($current_quantity * $current_cost) + ($new_quantity * $new_cost);
                $total_quantity = $current_quantity + $new_quantity;

                if ($total_quantity > 0) {
                    $weighted_average = $total_value / $total_quantity;
                    $json['weighted_average'] = round($weighted_average, 4);
                    $json['success'] = true;
                } else {
                    $json['error'] = $this->language->get('error_zero_quantity');
                }
            }
        } else {
            $json['error'] = $this->language->get('error_missing_data');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Convert between product units
     */
    public function convertUnits() {
        $json = array();

        if (isset($this->request->post['product_id']) && isset($this->request->post['from_unit_id']) &&
            isset($this->request->post['to_unit_id']) && isset($this->request->post['quantity'])) {

            $product_id = (int)$this->request->post['product_id'];
            $from_unit_id = (int)$this->request->post['from_unit_id'];
            $to_unit_id = (int)$this->request->post['to_unit_id'];
            $quantity = (float)$this->request->post['quantity'];

            $this->load->model('catalog/product');
            $this->load->model('localisation/unit');

            // Get unit information
            $from_unit = $this->model_localisation_unit->getUnit($from_unit_id);
            $to_unit = $this->model_localisation_unit->getUnit($to_unit_id);

            if (!$from_unit || !$to_unit) {
                $json['error'] = $this->language->get('error_unit_not_found');
            } else if ($quantity <= 0) {
                $json['error'] = $this->language->get('error_quantity_positive');
            } else {
                // Get unit conversion factors
                $from_factor = $this->model_catalog_product->getUnitConversionFactor($product_id, $from_unit_id);
                $to_factor = $this->model_catalog_product->getUnitConversionFactor($product_id, $to_unit_id);

                if ($from_factor === false || $to_factor === false) {
                    $json['error'] = $this->language->get('error_unit_conversion_not_found');
                } else if ($to_factor <= 0) {
                    $json['error'] = $this->language->get('error_invalid_conversion_factor');
                } else {
                    // Convert to base unit then to target unit
                    $base_quantity = $quantity * $from_factor;
                    $converted_quantity = $base_quantity / $to_factor;

                    $json['from'] = array(
                        'unit_id' => $from_unit_id,
                        'name' => $from_unit['desc_en'],
                        'factor' => $from_factor,
                        'quantity' => $quantity
                    );

                    $json['to'] = array(
                        'unit_id' => $to_unit_id,
                        'name' => $to_unit['desc_en'],
                        'factor' => $to_factor,
                        'quantity' => $converted_quantity
                    );

                    $json['formula'] = sprintf('%s %s × %s ÷ %s = %s %s',
                        $quantity,
                        $from_unit['desc_en'],
                        $from_factor,
                        $to_factor,
                        round($converted_quantity, 4),
                        $to_unit['desc_en']
                    );

                    $json['success'] = true;
                }
            }
        } else {
            $json['error'] = $this->language->get('error_missing_data');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Helper to validate inventory movement before adding
     */
    private function validateInventoryMovement($data) {
        $this->load->model('catalog/product');

        $result = array(
            'status' => true,
            'message' => ''
        );

        if ($data['quantity'] <= 0) {
            $result['status'] = false;
            $result['message'] = $this->language->get('error_quantity_must_be_positive');
            return $result;
        }

        // For outgoing movements, check if there's enough stock
        if (in_array($data['type'], array('adjustment_decrease', 'sale', 'transfer_out'))) {
            $inventory = $this->model_catalog_product->getProductInventoryItem($data['product_id'], $data['branch_id'], $data['unit_id']);

            if (!$inventory) {
                $result['status'] = false;
                $result['message'] = $this->language->get('error_no_inventory_found');
                return $result;
            }

            if ($inventory['quantity_available'] < $data['quantity']) {
                $result['status'] = false;
                $result['message'] = sprintf($this->language->get('error_insufficient_stock'), $inventory['quantity_available'], $data['quantity']);
                return $result;
            }
        }

        // For inventory count, calculate the difference
        if ($data['type'] == 'stock_count') {
            $inventory = $this->model_catalog_product->getProductInventoryItem($data['product_id'], $data['branch_id'], $data['unit_id']);

            if (!$inventory) {
                // It's OK to not have inventory for a count - it will create a new record
                $data['quantity'] = $data['quantity']; // The new quantity
            } else {
                // Adjust for current quantity
                $current_qty = $inventory['quantity'];

                if ($current_qty == $data['quantity']) {
                    $result['status'] = false;
                    $result['message'] = $this->language->get('text_no_change_needed');
                    return $result;
                }
            }
        }

        // For incoming movements with cost, validate the cost
        if (in_array($data['type'], array('adjustment_increase', 'purchase', 'return_in')) && isset($data['cost'])) {
            if ($data['cost'] <= 0) {
                $result['status'] = false;
                $result['message'] = $this->language->get('error_invalid_cost');
                return $result;
            }
        }

        return $result;
    }
}