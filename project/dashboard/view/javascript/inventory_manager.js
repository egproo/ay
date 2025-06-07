/**
 * Inventory Manager JavaScript
 *
 * Este archivo contiene las funciones necesarias para gestionar el inventario
 * y sus movimientos, utilizando la nueva capa de gestión de inventario.
 */

var InventoryManager = {
    /**
     * Inicializar el gestor de inventario
     */
    init: function() {
        // Inicializar eventos
        this.initEvents();

        // Cargar datos iniciales
        this.loadInventoryData();
        this.loadRecentMovements();

        // Inicializar datepickers
        $('.date').datetimepicker({
            pickTime: false,
            format: 'YYYY-MM-DD'
        });

        // Inicializar tabulador de movimientos cuando se active
        $('a[href="#tab-movement"]').on('shown.bs.tab', function (e) {
            InventoryManager.loadMovements(1);
            InventoryManager.loadCostHistory();
        });
    },

    /**
     * Inicializar eventos
     */
    initEvents: function() {
        // Botón para añadir movimiento de inventario
        $('#add-inventory-movement').on('click', function() {
            InventoryManager.showAdjustmentModal();
        });

        // Guardar ajuste de inventario
        $('#save-adjustment').on('click', function() {
            InventoryManager.saveAdjustment();
        });

        // Actualizar vista de ajuste al cambiar el tipo de movimiento
        $('#adjustment-movement-type').on('change', function() {
            InventoryManager.updateAdjustmentView();
        });

        // Actualizar inventario de sucursal al cambiar la sucursal
        $('#adjustment-branch').on('change', function() {
            InventoryManager.updateBranchInventory();
        });

        // Actualizar inventario de unidad al cambiar la unidad
        $('#adjustment-unit').on('change', function() {
            InventoryManager.updateUnitInventory();
        });

        // Actualizar cálculos al cambiar la cantidad
        $('#adjustment-quantity').on('input', function() {
            InventoryManager.updateCalculations();
        });

        // Actualizar cálculos al cambiar el costo directo
        $('#adjustment-direct-cost').on('input', function() {
            InventoryManager.updateCalculations();
        });

        // Habilitar/deshabilitar botón de guardar según confirmación
        $('#adjustment-confirmation').on('change', function() {
            $('#save-adjustment').prop('disabled', !$(this).is(':checked'));
        });

        // Mostrar/ocultar campo de razón personalizada
        $('#adjustment-reason').on('change', function() {
            InventoryManager.toggleCustomReason();
        });

        // Filtros de movimientos
        $('#apply-movement-filter').on('click', function() {
            InventoryManager.loadMovements(1);
        });

        // Reiniciar filtros de movimientos
        $('#reset-movement-filter').on('click', function() {
            $('#movement-type-filter').val('');
            $('#movement-unit-filter').val('');
            $('#movement-branch-filter').val('');
            $('#movement-date-from').val('');
            $('#movement-date-to').val('');
            InventoryManager.loadMovements(1);
        });

        // Eventos para el tabulador de movimientos
        $('#movement-type-filter, #movement-unit-filter, #movement-branch-filter').on('change', function() {
            // Actualizar estadísticas al cambiar filtros
            InventoryManager.loadMovementStatistics();
        });

        // Exportar movimientos a Excel
        $('#export-movements').on('click', function() {
            var product_id = $('input[name="product_id"]').val();
            var filters = {
                type: $('#movement-type-filter').val(),
                branch_id: $('#movement-branch-filter').val(),
                unit_id: $('#movement-unit-filter').val(),
                date_from: $('#movement-date-from').val(),
                date_to: $('#movement-date-to').val()
            };

            var url = 'index.php?route=catalog/product/exportMovements&user_token=' + getURLVar('user_token') +
                      '&product_id=' + product_id +
                      '&filters=' + encodeURIComponent(JSON.stringify(filters));

            window.open(url, '_blank');
        });

        // Exportar historial de costos a Excel
        $('#export-cost-history').on('click', function() {
            var product_id = $('input[name="product_id"]').val();
            var url = 'index.php?route=catalog/product/exportCostHistory&user_token=' + getURLVar('user_token') +
                      '&product_id=' + product_id;

            window.open(url, '_blank');
        });

        // Imprimir informe de movimientos
        $('#print-movements').on('click', function() {
            var product_id = $('input[name="product_id"]').val();
            var filters = {
                type: $('#movement-type-filter').val(),
                branch_id: $('#movement-branch-filter').val(),
                unit_id: $('#movement-unit-filter').val(),
                date_from: $('#movement-date-from').val(),
                date_to: $('#movement-date-to').val()
            };

            var url = 'index.php?route=catalog/product/printMovements&user_token=' + getURLVar('user_token') +
                      '&product_id=' + product_id +
                      '&filters=' + encodeURIComponent(JSON.stringify(filters));

            window.open(url, '_blank');
        });
    },

    /**
     * Cargar datos de inventario
     */
    loadInventoryData: function() {
        var product_id = $('input[name="product_id"]').val();

        $.ajax({
            url: 'index.php?route=catalog/product/getInventory&user_token=' + getURLVar('user_token'),
            type: 'post',
            data: { product_id: product_id },
            dataType: 'json',
            beforeSend: function() {
                $('#product-inventory tbody').html('<tr><td colspan="8" class="text-center"><i class="fa fa-spinner fa-spin"></i> ' + text_loading + '</td></tr>');
            },
            success: function(json) {
                var html = '';
                var total_value = 0;

                if (json.inventory && json.inventory.length) {
                    $.each(json.inventory, function(index, item) {
                        var item_value = parseFloat(item.quantity) * parseFloat(item.average_cost);
                        total_value += item_value;

                        html += '<tr>';
                        html += '<td>' + item.branch_name + '</td>';
                        html += '<td>' + item.unit_name + '</td>';
                        html += '<td class="text-center">' + item.quantity + '</td>';
                        html += '<td class="text-center">' + item.quantity_available + '</td>';
                        html += '<td class="text-center">' + item.average_cost + '</td>';
                        html += '<td class="text-center">' + item_value.toFixed(2) + '</td>';
                        html += '<td class="text-center">' + (item.is_consignment == 1 ? '<i class="fa fa-check text-success"></i>' : '<i class="fa fa-times text-danger"></i>') + '</td>';
                        html += '<td class="text-center">';
                        html += '<div class="btn-group">';
                        html += '<button type="button" onclick="InventoryManager.showAdjustmentModal(' + item.branch_id + ', \'' + item.unit_id + '\')" class="btn btn-primary btn-sm" title="' + text_adjust + '"><i class="fa fa-balance-scale"></i></button>';
                        html += '<button type="button" onclick="InventoryManager.showCostModal(' + item.branch_id + ', \'' + item.unit_id + '\')" class="btn btn-info btn-sm" title="' + text_edit_cost + '"><i class="fa fa-money"></i></button>';
                        html += '</div>';
                        html += '</td>';
                        html += '</tr>';
                    });
                } else {
                    html = '<tr><td colspan="8" class="text-center">' + text_no_inventory + '</td></tr>';
                }

                $('#product-inventory tbody').html(html);
                $('#total-inventory-value').text(total_value.toFixed(2));
            },
            error: function(xhr, ajaxOptions, thrownError) {
                alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
    },

    /**
     * Cargar movimientos recientes
     */
    loadRecentMovements: function() {
        var product_id = $('input[name="product_id"]').val();

        $.ajax({
            url: 'index.php?route=catalog/product/getRecentMovements&user_token=' + getURLVar('user_token'),
            type: 'post',
            data: { product_id: product_id, limit: 5 },
            dataType: 'json',
            beforeSend: function() {
                $('#recent-movements').html('<tr><td colspan="8" class="text-center"><i class="fa fa-spinner fa-spin"></i> ' + text_loading + '</td></tr>');
            },
            success: function(json) {
                var html = '';

                if (json.movements && json.movements.length) {
                    $.each(json.movements, function(index, movement) {
                        html += '<tr>';
                        html += '<td>' + movement.date_added + '</td>';
                        html += '<td>' + InventoryManager.getMovementTypeText(movement.type) + '</td>';
                        html += '<td class="text-center">' + movement.quantity + '</td>';
                        html += '<td>' + movement.unit_name + '</td>';
                        html += '<td>' + movement.branch_name + '</td>';
                        html += '<td>' + (movement.cost_impact > 0 ? '<span class="text-success">+' + movement.cost_impact + '</span>' : (movement.cost_impact < 0 ? '<span class="text-danger">' + movement.cost_impact + '</span>' : '0.00')) + '</td>';
                        html += '<td>' + movement.user_name + '</td>';
                        html += '<td>' + movement.reference + '</td>';
                        html += '</tr>';
                    });
                } else {
                    html = '<tr><td colspan="8" class="text-center">' + text_no_movements + '</td></tr>';
                }

                $('#recent-movements').html(html);
            },
            error: function(xhr, ajaxOptions, thrownError) {
                alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
    },

    /**
     * Cargar movimientos con filtros
     */
    loadMovements: function(page) {
        var product_id = $('input[name="product_id"]').val();
        page = page || 1;

        var filters = {
            type: $('#movement-type-filter').val(),
            branch_id: $('#movement-branch-filter').val(),
            unit_id: $('#movement-unit-filter').val(),
            date_from: $('#movement-date-from').val(),
            date_to: $('#movement-date-to').val(),
            page: page,
            limit: 10
        };

        $.ajax({
            url: 'index.php?route=catalog/product/getProductMovements&user_token=' + getURLVar('user_token'),
            type: 'post',
            data: {
                product_id: product_id,
                filters: filters
            },
            dataType: 'json',
            beforeSend: function() {
                $('#stock-movements tbody').html('<tr><td colspan="10" class="text-center"><i class="fa fa-spinner fa-spin"></i> ' + text_loading + '</td></tr>');
            },
            success: function(json) {
                var html = '';

                if (json.movements && json.movements.length) {
                    $.each(json.movements, function(index, movement) {
                        html += '<tr>';
                        html += '<td>' + movement.date_added + '</td>';
                        html += '<td>' + InventoryManager.getMovementTypeText(movement.type) + '</td>';
                        html += '<td class="text-center">' + movement.quantity + '</td>';
                        html += '<td>' + movement.unit_name + '</td>';
                        html += '<td>' + movement.branch_name + '</td>';
                        html += '<td>' + movement.reference + '</td>';
                        html += '<td>' + movement.user_name + '</td>';
                        html += '<td class="text-right">' + movement.cost + '</td>';
                        html += '<td class="text-right">' + movement.new_cost + '</td>';
                        html += '<td class="text-center">';
                        html += '<button type="button" onclick="InventoryManager.showMovementDetails(' + movement.movement_id + ')" class="btn btn-info btn-sm" title="' + text_view_details + '"><i class="fa fa-eye"></i></button>';
                        html += '</td>';
                        html += '</tr>';
                    });
                } else {
                    html = '<tr><td colspan="10" class="text-center">' + text_no_movements + '</td></tr>';
                }

                $('#stock-movements tbody').html(html);

                // Actualizar paginación
                InventoryManager.renderPagination(json.pagination, page, 'movement-pagination', InventoryManager.loadMovements);

                // Cargar estadísticas
                InventoryManager.loadMovementStatistics();
            },
            error: function(xhr, ajaxOptions, thrownError) {
                alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
    },

    /**
     * Cargar historial de costos
     */
    loadCostHistory: function() {
        var product_id = $('input[name="product_id"]').val();

        $.ajax({
            url: 'index.php?route=catalog/product/getCostHistory&user_token=' + getURLVar('user_token'),
            type: 'post',
            data: { product_id: product_id },
            dataType: 'json',
            beforeSend: function() {
                $('#cost-history tbody').html('<tr><td colspan="7" class="text-center"><i class="fa fa-spinner fa-spin"></i> ' + text_loading + '</td></tr>');
            },
            success: function(json) {
                var html = '';

                if (json.cost_history && json.cost_history.length) {
                    $.each(json.cost_history, function(index, item) {
                        html += '<tr>';
                        html += '<td>' + item.date_added + '</td>';
                        html += '<td>' + item.unit_name + '</td>';
                        html += '<td class="text-right">' + item.old_cost + '</td>';
                        html += '<td class="text-right">' + item.new_cost + '</td>';
                        html += '<td>' + item.reason + '</td>';
                        html += '<td>' + item.user_name + '</td>';
                        html += '<td>' + item.notes + '</td>';
                        html += '</tr>';
                    });

                    // Crear gráfico de tendencia de costos
                    InventoryManager.createCostTrendChart(json.cost_history);
                } else {
                    html = '<tr><td colspan="7" class="text-center">' + text_no_cost_history + '</td></tr>';
                }

                $('#cost-history tbody').html(html);
            },
            error: function(xhr, ajaxOptions, thrownError) {
                alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
    },

    /**
     * Cargar estadísticas de movimientos
     */
    loadMovementStatistics: function() {
        var product_id = $('input[name="product_id"]').val();

        $.ajax({
            url: 'index.php?route=catalog/product/getMovementStatistics&user_token=' + getURLVar('user_token'),
            type: 'post',
            data: { product_id: product_id },
            dataType: 'json',
            success: function(json) {
                if (json.statistics) {
                    // Actualizar valores de estadísticas
                    $('#total-incoming').text(json.statistics.total_incoming);
                    $('#total-outgoing').text(json.statistics.total_outgoing);
                    $('#net-change').text(json.statistics.net_change);
                    $('#current-stock-total').text(json.statistics.current_stock);

                    // Crear gráficos
                    InventoryManager.createMovementTypeChart(json.statistics.by_type);
                    InventoryManager.createStockTrendChart(json.statistics.stock_trend);
                    InventoryManager.createMovementFrequencyChart(json.statistics.frequency);
                    InventoryManager.createStockByBranchChart(json.statistics.by_branch);
                }
            },
            error: function(xhr, ajaxOptions, thrownError) {
                console.error(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
    },

    /**
     * Mostrar detalles de movimiento
     */
    showMovementDetails: function(movement_id) {
        $.ajax({
            url: 'index.php?route=catalog/product/getMovementDetails&user_token=' + getURLVar('user_token'),
            type: 'post',
            data: { movement_id: movement_id },
            dataType: 'json',
            beforeSend: function() {
                $('#detail-date').text('...');
                $('#detail-type').text('...');
                $('#detail-quantity').text('...');
                $('#detail-unit').text('...');
                $('#detail-branch').text('...');
                $('#detail-reference').text('...');
                $('#detail-user').text('...');
                $('#detail-cost').text('...');
                $('#detail-old-cost').text('...');
                $('#detail-new-cost').text('...');
                $('#detail-cost-impact').text('...');
                $('#detail-value-change').text('...');
                $('#detail-notes').text('...');
                $('#detail-journal-entries tbody').html('<tr><td colspan="3" class="text-center"><i class="fa fa-spinner fa-spin"></i> ' + text_loading + '</td></tr>');
            },
            success: function(json) {
                if (json.movement) {
                    var movement = json.movement;

                    // Información básica
                    $('#detail-date').text(movement.date_added);
                    $('#detail-type').text(InventoryManager.getMovementTypeText(movement.type));
                    $('#detail-quantity').text(movement.quantity);
                    $('#detail-unit').text(movement.unit_name);
                    $('#detail-branch').text(movement.branch_name);
                    $('#detail-reference').text(movement.reference || '-');
                    $('#detail-user').text(movement.user_name);

                    // Impacto financiero
                    $('#detail-cost').text(movement.cost);
                    $('#detail-old-cost').text(movement.old_cost);
                    $('#detail-new-cost').text(movement.new_cost);

                    var costImpact = parseFloat(movement.cost_impact) || 0;
                    var valueChange = parseFloat(movement.value_change) || 0;

                    $('#detail-cost-impact').html(costImpact > 0 ?
                        '<span class="text-success">+' + costImpact.toFixed(2) + '</span>' :
                        (costImpact < 0 ? '<span class="text-danger">' + costImpact.toFixed(2) + '</span>' : '0.00'));

                    $('#detail-value-change').html(valueChange > 0 ?
                        '<span class="text-success">+' + valueChange.toFixed(2) + '</span>' :
                        (valueChange < 0 ? '<span class="text-danger">' + valueChange.toFixed(2) + '</span>' : '0.00'));

                    // Notas
                    $('#detail-notes').text(movement.notes || '-');

                    // Impacto contable
                    if (json.journal_entries && json.journal_entries.length) {
                        var journalHtml = '';

                        $.each(json.journal_entries, function(index, entry) {
                            journalHtml += '<tr>';
                            journalHtml += '<td>' + entry.account_name + '</td>';
                            journalHtml += '<td class="text-right">' + (entry.debit > 0 ? entry.debit : '-') + '</td>';
                            journalHtml += '<td class="text-right">' + (entry.credit > 0 ? entry.credit : '-') + '</td>';
                            journalHtml += '</tr>';
                        });

                        $('#detail-journal-entries tbody').html(journalHtml);
                        $('#detail-journal-section').show();
                    } else {
                        $('#detail-journal-entries tbody').html('<tr><td colspan="3" class="text-center">' + text_no_accounting_impact + '</td></tr>');
                        $('#detail-journal-section').show();
                    }

                    // Mostrar modal
                    $('#movement-details-modal').modal('show');
                } else {
                    alert(text_error_loading);
                }
            },
            error: function(xhr, ajaxOptions, thrownError) {
                alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
    },

    /**
     * Crear gráfico de tipos de movimiento
     */
    createMovementTypeChart: function(data) {
        var ctx = document.getElementById('movement-type-chart').getContext('2d');

        // Destruir gráfico existente si existe
        if (window.movementTypeChart) {
            window.movementTypeChart.destroy();
        }

        var labels = [];
        var incomingData = [];
        var outgoingData = [];

        $.each(data, function(type, values) {
            labels.push(InventoryManager.getMovementTypeText(type));

            if (type === 'purchase' || type === 'adjustment_increase' || type === 'transfer_in' ||
                type === 'initial' || type === 'return_in' || type === 'production') {
                incomingData.push(values.quantity);
                outgoingData.push(0);
            } else {
                incomingData.push(0);
                outgoingData.push(Math.abs(values.quantity));
            }
        });

        window.movementTypeChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: text_total_incoming,
                        data: incomingData,
                        backgroundColor: 'rgba(75, 192, 192, 0.6)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    },
                    {
                        label: text_total_outgoing,
                        data: outgoingData,
                        backgroundColor: 'rgba(255, 99, 132, 0.6)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    },

    /**
     * Crear gráfico de tendencia de stock
     */
    createStockTrendChart: function(data) {
        var ctx = document.getElementById('stock-trend-chart').getContext('2d');

        // Destruir gráfico existente si existe
        if (window.stockTrendChart) {
            window.stockTrendChart.destroy();
        }

        var labels = [];
        var stockData = [];

        $.each(data, function(index, item) {
            labels.push(item.date);
            stockData.push(item.quantity);
        });

        window.stockTrendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: text_stock_level,
                    data: stockData,
                    fill: false,
                    borderColor: 'rgba(54, 162, 235, 1)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    },

    /**
     * Crear gráfico de tendencia de costos
     */
    createCostTrendChart: function(data) {
        var ctx = document.getElementById('cost-trend-chart').getContext('2d');

        // Destruir gráfico existente si existe
        if (window.costTrendChart) {
            window.costTrendChart.destroy();
        }

        var costData = {};

        // Agrupar datos por unidad
        $.each(data, function(index, item) {
            if (!costData[item.unit_name]) {
                costData[item.unit_name] = {
                    dates: [],
                    costs: []
                };
            }

            costData[item.unit_name].dates.push(item.date_added);
            costData[item.unit_name].costs.push(item.new_cost);
        });

        var datasets = [];
        var colors = [
            'rgba(54, 162, 235, 1)',
            'rgba(255, 99, 132, 1)',
            'rgba(75, 192, 192, 1)',
            'rgba(255, 206, 86, 1)',
            'rgba(153, 102, 255, 1)'
        ];

        var i = 0;
        $.each(costData, function(unitName, unitData) {
            datasets.push({
                label: unitName,
                data: unitData.costs,
                fill: false,
                borderColor: colors[i % colors.length],
                tension: 0.1
            });
            i++;
        });

        // Usar las fechas del primer conjunto de datos como etiquetas
        var labels = [];
        if (datasets.length > 0) {
            var firstUnit = Object.keys(costData)[0];
            labels = costData[firstUnit].dates;
        }

        window.costTrendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: datasets
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: false
                    }
                }
            }
        });
    },

    /**
     * Crear gráfico de frecuencia de movimientos
     */
    createMovementFrequencyChart: function(data) {
        var ctx = document.getElementById('movement-frequency-chart').getContext('2d');

        // Destruir gráfico existente si existe
        if (window.movementFrequencyChart) {
            window.movementFrequencyChart.destroy();
        }

        var labels = [];
        var values = [];
        var colors = [];

        var colorMap = {
            'daily': 'rgba(54, 162, 235, 0.6)',
            'weekly': 'rgba(75, 192, 192, 0.6)',
            'monthly': 'rgba(255, 206, 86, 0.6)',
            'quarterly': 'rgba(153, 102, 255, 0.6)',
            'yearly': 'rgba(255, 99, 132, 0.6)'
        };

        $.each(data, function(period, count) {
            labels.push(period);
            values.push(count);
            colors.push(colorMap[period] || 'rgba(201, 203, 207, 0.6)');
        });

        window.movementFrequencyChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: text_movement_count,
                    data: values,
                    backgroundColor: colors,
                    borderColor: colors.map(function(color) {
                        return color.replace('0.6', '1');
                    }),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    },

    /**
     * Crear gráfico de stock por sucursal
     */
    createStockByBranchChart: function(data) {
        var ctx = document.getElementById('stock-by-branch-chart').getContext('2d');

        // Destruir gráfico existente si existe
        if (window.stockByBranchChart) {
            window.stockByBranchChart.destroy();
        }

        var labels = [];
        var values = [];
        var colors = [
            'rgba(54, 162, 235, 0.6)',
            'rgba(255, 99, 132, 0.6)',
            'rgba(75, 192, 192, 0.6)',
            'rgba(255, 206, 86, 0.6)',
            'rgba(153, 102, 255, 0.6)',
            'rgba(255, 159, 64, 0.6)',
            'rgba(201, 203, 207, 0.6)'
        ];

        $.each(data, function(branch, quantity) {
            labels.push(branch);
            values.push(quantity);
        });

        window.stockByBranchChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors.slice(0, labels.length),
                    borderColor: colors.slice(0, labels.length).map(function(color) {
                        return color.replace('0.6', '1');
                    }),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true
            }
        });
    },

    /**
     * Renderizar paginación
     */
    renderPagination: function(pagination, currentPage, containerId, callback) {
        var container = $('#' + containerId);
        container.empty();

        if (!pagination || pagination.total_pages <= 1) {
            return;
        }

        var html = '<ul class="pagination">';

        // Botón anterior
        if (currentPage > 1) {
            html += '<li><a href="javascript:void(0)" onclick="' + callback.name + '(' + (currentPage - 1) + ')">&laquo;</a></li>';
        } else {
            html += '<li class="disabled"><span>&laquo;</span></li>';
        }

        // Páginas
        var startPage = Math.max(1, currentPage - 2);
        var endPage = Math.min(pagination.total_pages, startPage + 4);

        if (startPage > 1) {
            html += '<li><a href="javascript:void(0)" onclick="' + callback.name + '(1)">1</a></li>';
            if (startPage > 2) {
                html += '<li class="disabled"><span>...</span></li>';
            }
        }

        for (var i = startPage; i <= endPage; i++) {
            if (i == currentPage) {
                html += '<li class="active"><span>' + i + '</span></li>';
            } else {
                html += '<li><a href="javascript:void(0)" onclick="' + callback.name + '(' + i + ')">' + i + '</a></li>';
            }
        }

        if (endPage < pagination.total_pages) {
            if (endPage < pagination.total_pages - 1) {
                html += '<li class="disabled"><span>...</span></li>';
            }
            html += '<li><a href="javascript:void(0)" onclick="' + callback.name + '(' + pagination.total_pages + ')">' + pagination.total_pages + '</a></li>';
        }

        // Botón siguiente
        if (currentPage < pagination.total_pages) {
            html += '<li><a href="javascript:void(0)" onclick="' + callback.name + '(' + (currentPage + 1) + ')">&raquo;</a></li>';
        } else {
            html += '<li class="disabled"><span>&raquo;</span></li>';
        }

        html += '</ul>';

        container.html(html);
    },

    /**
     * Obtener texto del tipo de movimiento
     */
    getMovementTypeText: function(type) {
        var types = {
            'purchase': text_purchase,
            'sale': text_sale,
            'adjustment_increase': text_adjustment_increase,
            'adjustment_decrease': text_adjustment_decrease,
            'transfer_in': text_transfer_in,
            'transfer_out': text_transfer_out,
            'initial': text_initial_stock,
            'return_in': text_return_in,
            'return_out': text_return_out,
            'scrap': text_scrap,
            'production': text_production,
            'consumption': text_consumption,
            'cost_adjustment': text_cost_adjustment
        };

        return types[type] || type;
    },

    /**
     * Mostrar modal de ajuste de inventario
     */
    showAdjustmentModal: function(branch_id, unit_id) {
        // Limpiar modal
        $('#adjustment-modal').find('input:not([type=hidden]):not([type=checkbox])').val('');
        $('#adjustment-modal').find('textarea').val('');
        $('#adjustment-modal').find('select').val('');
        $('#adjustment-confirmation').prop('checked', false);
        $('#save-adjustment').prop('disabled', true);

        // Establecer valores predeterminados
        $('#adjustment-movement-type').val('increase');
        $('#adjustment-reason').val('stock_count');
        $('#custom-reason-container').hide();

        // Si se proporcionan branch_id y unit_id, establecerlos
        if (branch_id && unit_id) {
            $('#adjustment-branch-id').val(branch_id);
            $('#adjustment-unit-id').val(unit_id);
            $('#adjustment-branch').val(branch_id);
            $('#adjustment-unit').val(unit_id);
        } else {
            // Seleccionar primera sucursal y unidad
            var first_branch = $('#adjustment-branch option:first').val();
            $('#adjustment-branch').val(first_branch);
            $('#adjustment-branch-id').val(first_branch);

            this.loadProductUnits(function() {
                var first_unit = $('#adjustment-unit option:first').val();
                $('#adjustment-unit').val(first_unit);
                $('#adjustment-unit-id').val(first_unit);
                InventoryManager.updateUnitInventory();
            });
        }

        // Actualizar vista
        this.updateAdjustmentView();
        this.updateBranchInventory();

        // Mostrar modal
        $('#adjustment-modal').modal('show');
    },

    /**
     * Cargar unidades del producto
     */
    loadProductUnits: function(callback) {
        var product_id = $('input[name="product_id"]').val();

        $.ajax({
            url: 'index.php?route=catalog/product/getProductUnits&user_token=' + getURLVar('user_token'),
            type: 'post',
            data: { product_id: product_id },
            dataType: 'json',
            success: function(json) {
                var html = '';

                if (json.units && json.units.length) {
                    $.each(json.units, function(index, unit) {
                        html += '<option value="' + unit.unit_id + '">' + unit.name + '</option>';
                    });
                }

                $('#adjustment-unit').html(html);

                if (typeof callback === 'function') {
                    callback();
                }
            },
            error: function(xhr, ajaxOptions, thrownError) {
                alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
    },

    /**
     * Actualizar vista de ajuste según el tipo de movimiento
     */
    updateAdjustmentView: function() {
        var movement_type = $('#adjustment-movement-type').val();

        // Mostrar/ocultar campo de costo directo
        if (movement_type === 'increase') {
            $('#direct-cost-container').show();
            $('#quantity-label').text(text_quantity_to_add);
        } else if (movement_type === 'decrease') {
            $('#direct-cost-container').hide();
            $('#quantity-label').text(text_quantity_to_remove);
        } else if (movement_type === 'count') {
            $('#direct-cost-container').hide();
            $('#quantity-label').text(text_actual_quantity);
        }

        // Actualizar cálculos
        this.updateCalculations();
    },

    /**
     * Actualizar inventario de sucursal
     */
    updateBranchInventory: function() {
        var branch_id = $('#adjustment-branch').val();
        $('#adjustment-branch-id').val(branch_id);
        $('#current-branch').text($('#adjustment-branch option:selected').text());

        this.updateUnitInventory();
    },

    /**
     * Actualizar inventario de unidad
     */
    updateUnitInventory: function() {
        var product_id = $('input[name="product_id"]').val();
        var branch_id = $('#adjustment-branch').val();
        var unit_id = $('#adjustment-unit').val();

        $('#adjustment-unit-id').val(unit_id);
        $('#current-unit').text($('#adjustment-unit option:selected').text());
        $('#adjustment-unit-name').text($('#adjustment-unit option:selected').text());

        // Obtener inventario actual
        $.ajax({
            url: 'index.php?route=catalog/product/getInventoryByUnit&user_token=' + getURLVar('user_token'),
            type: 'post',
            data: {
                product_id: product_id,
                branch_id: branch_id,
                unit_id: unit_id
            },
            dataType: 'json',
            success: function(json) {
                if (json.inventory) {
                    $('#current-quantity').text(json.inventory.quantity);
                    $('#current-cost').text(json.inventory.average_cost);
                } else {
                    $('#current-quantity').text('0');
                    $('#current-cost').text('0.00');
                }

                InventoryManager.updateCalculations();
            },
            error: function(xhr, ajaxOptions, thrownError) {
                alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
    },

    /**
     * Actualizar cálculos de ajuste
     */
    updateCalculations: function() {
        var movement_type = $('#adjustment-movement-type').val();
        var current_quantity = parseFloat($('#current-quantity').text()) || 0;
        var current_cost = parseFloat($('#current-cost').text()) || 0;
        var adjustment_quantity = parseFloat($('#adjustment-quantity').val()) || 0;
        var direct_cost = parseFloat($('#adjustment-direct-cost').val()) || current_cost;

        var new_quantity = 0;
        var new_cost = current_cost;
        var quantity_change = 0;
        var value_change = 0;

        // Calcular nueva cantidad y costo
        if (movement_type === 'increase') {
            new_quantity = current_quantity + adjustment_quantity;
            quantity_change = adjustment_quantity;

            // Calcular nuevo costo promedio ponderado
            if (new_quantity > 0) {
                new_cost = ((current_quantity * current_cost) + (adjustment_quantity * direct_cost)) / new_quantity;
            } else {
                new_cost = direct_cost;
            }

            value_change = adjustment_quantity * direct_cost;
        } else if (movement_type === 'decrease') {
            new_quantity = Math.max(0, current_quantity - adjustment_quantity);
            quantity_change = -adjustment_quantity;
            value_change = -adjustment_quantity * current_cost;
        } else if (movement_type === 'count') {
            new_quantity = adjustment_quantity;
            quantity_change = adjustment_quantity - current_quantity;
            value_change = quantity_change * current_cost;
        }

        // Actualizar interfaz
        $('#new-quantity').text(new_quantity.toFixed(2));
        $('#new-cost').text(new_cost.toFixed(2));
        $('#quantity-change').text(quantity_change.toFixed(2));
        $('#value-change').text(value_change.toFixed(2));

        // Actualizar impacto contable
        if (value_change > 0) {
            $('#inventory-account-amount').text(value_change.toFixed(2) + ' DR');
            $('#contra-account-amount').text(value_change.toFixed(2) + ' CR');
        } else if (value_change < 0) {
            $('#inventory-account-amount').text(Math.abs(value_change).toFixed(2) + ' CR');
            $('#contra-account-amount').text(Math.abs(value_change).toFixed(2) + ' DR');
        } else {
            $('#inventory-account-amount').text('0.00');
            $('#contra-account-amount').text('0.00');
        }

        // Actualizar vista previa del asiento contable
        this.updateJournalPreview(value_change);

        // Mostrar advertencias si es necesario
        this.showAdjustmentWarnings(movement_type, current_quantity, adjustment_quantity);
    },

    /**
     * Actualizar vista previa del asiento contable
     */
    updateJournalPreview: function(value_change) {
        var movement_type = $('#adjustment-movement-type').val();
        var html = '';

        if (value_change === 0) {
            html = '<tr><td colspan="3" class="text-center">' + text_no_accounting_impact + '</td></tr>';
        } else {
            var inventory_account = text_inventory_account;
            var contra_account = '';

            if (movement_type === 'increase') {
                contra_account = text_inventory_adjustment_account;
            } else if (movement_type === 'decrease') {
                contra_account = text_inventory_adjustment_account;
            } else if (movement_type === 'count') {
                contra_account = text_inventory_adjustment_account;
            }

            if (value_change > 0) {
                html += '<tr>';
                html += '<td>' + inventory_account + '</td>';
                html += '<td class="text-right">' + Math.abs(value_change).toFixed(2) + '</td>';
                html += '<td class="text-right">-</td>';
                html += '</tr>';
                html += '<tr>';
                html += '<td>' + contra_account + '</td>';
                html += '<td class="text-right">-</td>';
                html += '<td class="text-right">' + Math.abs(value_change).toFixed(2) + '</td>';
                html += '</tr>';
            } else {
                html += '<tr>';
                html += '<td>' + contra_account + '</td>';
                html += '<td class="text-right">' + Math.abs(value_change).toFixed(2) + '</td>';
                html += '<td class="text-right">-</td>';
                html += '</tr>';
                html += '<tr>';
                html += '<td>' + inventory_account + '</td>';
                html += '<td class="text-right">-</td>';
                html += '<td class="text-right">' + Math.abs(value_change).toFixed(2) + '</td>';
                html += '</tr>';
            }
        }

        $('#journal-preview tbody').html(html);
    },

    /**
     * Mostrar advertencias de ajuste
     */
    showAdjustmentWarnings: function(movement_type, current_quantity, adjustment_quantity) {
        var warnings = [];

        if (movement_type === 'decrease' && adjustment_quantity > current_quantity) {
            warnings.push(text_warning_insufficient_stock);
        }

        if (warnings.length > 0) {
            var html = '';
            $.each(warnings, function(index, warning) {
                html += '<p><i class="fa fa-exclamation-triangle"></i> ' + warning + '</p>';
            });

            $('#adjustment-warnings').html(html).show();
            $('#save-adjustment').prop('disabled', true);
        } else {
            $('#adjustment-warnings').hide();
            $('#save-adjustment').prop('disabled', !$('#adjustment-confirmation').is(':checked'));
        }
    },

    /**
     * Mostrar/ocultar campo de razón personalizada
     */
    toggleCustomReason: function() {
        if ($('#adjustment-reason').val() === 'other') {
            $('#custom-reason-container').show();
        } else {
            $('#custom-reason-container').hide();
        }
    },

    /**
     * Guardar ajuste de inventario
     */
    saveAdjustment: function() {
        var product_id = $('input[name="product_id"]').val();
        var branch_id = $('#adjustment-branch-id').val();
        var unit_id = $('#adjustment-unit-id').val();
        var movement_type = $('#adjustment-movement-type').val();
        var quantity = parseFloat($('#adjustment-quantity').val()) || 0;
        var direct_cost = parseFloat($('#adjustment-direct-cost').val()) || 0;
        var reason = $('#adjustment-reason').val();
        var custom_reason = $('#adjustment-custom-reason').val();
        var notes = $('#adjustment-notes').val();
        var reference = $('#adjustment-reference').val();

        // Validar datos
        if (quantity <= 0) {
            alert(text_error_quantity);
            return;
        }

        if (movement_type === 'increase' && direct_cost <= 0) {
            alert(text_error_cost);
            return;
        }

        if (reason === 'other' && !custom_reason) {
            alert(text_error_custom_reason);
            return;
        }

        if (!notes) {
            alert(text_error_notes);
            return;
        }

        // Preparar datos
        var data = {
            product_id: product_id,
            branch_id: branch_id,
            unit_id: unit_id,
            movement_type: movement_type,
            quantity: quantity,
            direct_cost: direct_cost,
            reason: reason === 'other' ? custom_reason : reason,
            notes: notes,
            reference: reference
        };

        // Enviar datos
        $.ajax({
            url: 'index.php?route=catalog/product/addInventoryMovement&user_token=' + getURLVar('user_token'),
            type: 'post',
            data: data,
            dataType: 'json',
            beforeSend: function() {
                $('#save-adjustment').button('loading');
            },
            complete: function() {
                $('#save-adjustment').button('reset');
            },
            success: function(json) {
                if (json.success) {
                    $('#adjustment-modal').modal('hide');

                    // Actualizar datos
                    InventoryManager.loadInventoryData();
                    InventoryManager.loadRecentMovements();

                    // Mostrar mensaje de éxito
                    $('#notification-area').html('<div class="alert alert-success alert-dismissible"><i class="fa fa-check-circle"></i> ' + json.success + ' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
                } else if (json.error) {
                    alert(json.error);
                }
            },
            error: function(xhr, ajaxOptions, thrownError) {
                alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
    }
};

// Inicializar cuando el documento esté listo
$(document).ready(function() {
    InventoryManager.init();
});
