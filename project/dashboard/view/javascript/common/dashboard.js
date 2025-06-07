$(document).ready(function() {
    'use strict';

    // Initialize dashboard data with fallback values
    var orders_status_data = window.orders_status_data || {labels: ['Pending', 'Processing', 'Shipped', 'Complete'], values: [5, 10, 8, 25]};
    var margin_trend_data = window.margin_trend_data || {labels: [], values: []};
    var revenue_chart_data = window.revenue_chart_data || {dates: [], values: []};
    var cash_flow_data = window.cash_flow_data || {dates: [], income: [], expenses: []};
    var settings = window.dashboard_settings || {
        refresh_interval: 120,
        default_tab: 'my-workspace',
        enable_animations: 1,
        enable_cache: 1
    };
    var revenueChart;

    // Initialize GridStack
    var options = {
        float: true,
        cellHeight: 80,
        animate: true,
        resizable: {
            handles: 'e, se, s, sw, w'
        }
    };

    gridstack = GridStack.init(options);

    // Save grid layout when changed
    gridstack.on('change', function() {
        var serializedData = [];
        $('.grid-stack-item.ui-draggable').each(function() {
            var $this = $(this);
            serializedData.push({
                x: $this.attr('data-gs-x'),
                y: $this.attr('data-gs-y'),
                width: $this.attr('data-gs-width'),
                height: $this.attr('data-gs-height'),
                id: $this.attr('data-gs-id')
            });
        });

        $.ajax({
            url: 'index.php?route=common/dashboard/saveLayout&user_token=' + getUrlParam('user_token'),
            type: 'post',
            data: {
                layout: serializedData
            },
            dataType: 'json',
            success: function(json) {
                if (json.success) {
                    toastr.success(json.success);
                }
                if (json.error) {
                    toastr.error(json.error);
                }
            },
            error: function(xhr, ajaxOptions, thrownError) {
                toastr.error(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
    });

    // Initialize Charts
    initRevenueChart();
    initCashFlowChart();
    initOrdersStatusChart();
    initMarginTrendChart();

    // Initialize DateRangePicker
    $('input[name="daterange"]').daterangepicker({
        startDate: moment().subtract(29, 'days'),
        endDate: moment(),
        ranges: {
            'Today': [moment(), moment()],
            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
    }, function(start, end, label) {
        updateDashboardData(start.format('YYYY-MM-DD'), end.format('YYYY-MM-DD'));
        $('#date-range-text').text(label);
    });

    // Refresh Button Handler
    $('#button-refresh').on('click', function() {
        refreshDashboard();
    });

    // Settings Save Handler
    $('#button-save-settings').on('click', function() {
        var settings = {
            refresh_interval: $('select[name="refresh_interval"]').val(),
            default_tab: $('select[name="default_tab"]').val(),
            enable_animations: $('#enable-animations').prop('checked') ? 1 : 0,
            enable_cache: $('#enable-cache').prop('checked') ? 1 : 0
        };

        $.ajax({
            url: 'index.php?route=common/dashboard/saveSettings&user_token=' + getUrlParam('user_token'),
            type: 'post',
            data: {
                settings: settings
            },
            dataType: 'json',
            success: function(json) {
                if (json.success) {
                    toastr.success(json.success);
                    $('#modal-settings').modal('hide');

                    // Apply new settings
                    if (settings.enable_animations) {
                        Chart.defaults.global.animation.duration = 1000;
                    } else {
                        Chart.defaults.global.animation.duration = 0;
                    }

                    if (settings.refresh_interval > 0) {
                        startAutoRefresh(settings.refresh_interval);
                    } else {
                        stopAutoRefresh();
                    }
                }
                if (json.error) {
                    toastr.error(json.error);
                }
            },
            error: function(xhr, ajaxOptions, thrownError) {
                toastr.error(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
    });

    // Chart Initialization Functions
    function initRevenueChart() {
        var ctx = document.getElementById('revenueChart');
        if (!ctx) return;

        revenueChart = new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: {
                labels: revenue_chart_data.dates || [],
                datasets: [{
                    label: 'Revenue',
                    data: revenue_chart_data.values || [],
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    borderColor: '#28a745',
                    borderWidth: 2,
                    pointRadius: 3,
                    pointBackgroundColor: '#28a745'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true,
                            callback: function(value) {
                                return formatMoney(value);
                            }
                        }
                    }]
                },
                tooltips: {
                    callbacks: {
                        label: function(tooltipItem, data) {
                            return formatMoney(tooltipItem.yLabel);
                        }
                    }
                }
            }
        });
    }

    function initCashFlowChart() {
        var ctx = document.getElementById('cashFlowChart');
        if (!ctx) return;

        new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: cash_flow_data.dates || [],
                datasets: [{
                    label: 'Income',
                    data: cash_flow_data.income || [],
                    backgroundColor: 'rgba(40, 167, 69, 0.5)',
                    borderColor: '#28a745',
                    borderWidth: 1
                }, {
                    label: 'Expenses',
                    data: cash_flow_data.expenses || [],
                    backgroundColor: 'rgba(220, 53, 69, 0.5)',
                    borderColor: '#dc3545',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true,
                            callback: function(value) {
                                return formatMoney(value);
                            }
                        }
                    }],
                    xAxes: [{
                        stacked: true
                    }]
                },
                tooltips: {
                    callbacks: {
                        label: function(tooltipItem, data) {
                            return data.datasets[tooltipItem.datasetIndex].label + ': ' + formatMoney(tooltipItem.yLabel);
                        }
                    }
                }
            }
        });
    }

    function initOrdersStatusChart() {
        var ctx = document.getElementById('ordersStatusChart');
        if (!ctx) return;

        new Chart(ctx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: orders_status_data.labels,
                datasets: [{
                    data: orders_status_data.values,
                    backgroundColor: [
                        '#28a745',
                        '#ffc107',
                        '#17a2b8',
                        '#dc3545',
                        '#6c757d'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: {
                    position: 'right'
                }
            }
        });
    }

    function initMarginTrendChart() {
        var ctx = document.getElementById('marginTrendChart');
        if (!ctx) return;

        new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: {
                labels: margin_trend_data.labels || [],
                datasets: [{
                    label: 'Gross Margin',
                    data: margin_trend_data.values || [],
                    backgroundColor: 'rgba(23, 162, 184, 0.1)',
                    borderColor: '#17a2b8',
                    borderWidth: 2,
                    pointRadius: 3,
                    pointBackgroundColor: '#17a2b8'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: false,
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }]
                },
                tooltips: {
                    callbacks: {
                        label: function(tooltipItem, data) {
                            return tooltipItem.yLabel + '%';
                        }
                    }
                }
            }
        });
    }

    // Dashboard Update Functions
    function updateDashboardData(startDate, endDate) {
        showLoading();

        $.ajax({
            url: 'index.php?route=common/dashboard/updateData&user_token=' + getUrlParam('user_token'),
            type: 'post',
            data: {
                start_date: startDate,
                end_date: endDate,
                branch: $('select[name="branch"]').val()
            },
            dataType: 'json',
            success: function(json) {
                if (json.success && json.data) {
                    var data = json.data;

                    if (data.stats) {
                        updateQuickStats(data.stats);
                    }
                    if (data.charts && data.charts.revenue) {
                        updateRevenueChart(data.charts.revenue);
                    }
                    if (data.charts && data.charts.cash_flow) {
                        updateCashFlowChart(data.charts.cash_flow);
                    }
                    if (data.top_products) {
                        updateTopProducts(data.top_products);
                    }
                    if (data.low_stock) {
                        updateLowStock(data.low_stock);
                    }
                    if (data.orders_status) {
                        updateOrdersStatus(data.orders_status);
                    }
                    if (data.margins) {
                        updateMargins(data.margins);
                    }
                }

                hideLoading();
            },
            error: function(xhr, ajaxOptions, thrownError) {
                toastr.error(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                hideLoading();
            }
        });
    }

    function updateQuickStats(stats) {
        if (stats.orders && stats.orders.total !== undefined) {
            $('#total-orders-today').text(stats.orders.total);
        }
        if (stats.revenue && stats.revenue.total !== undefined) {
            $('#total-revenue-today').text(stats.revenue.total);
        }
        if (stats.low_stock && stats.low_stock.count !== undefined) {
            $('#low-stock-count').text(stats.low_stock.count);
        }
        if (stats.approvals && stats.approvals.pending !== undefined) {
            $('#pending-approvals').text(stats.approvals.pending);
        }
    }

    function updateRevenueChart(data) {
        revenueChart.data.labels = data.dates;
        revenueChart.data.datasets[0].data = data.values;
        revenueChart.update();
    }

    function updateTopProducts(products) {
        var html = '';
        products.forEach(function(product) {
            html += '<tr>';
            html += '<td>' + product.name + '</td>';
            html += '<td class="text-end">' + product.quantity + '</td>';
            html += '<td class="text-end">' + product.total + '</td>';
            html += '</tr>';
        });
        $('#top-products').html(html);
    }

    function updateLowStock(products) {
        var html = '';
        products.forEach(function(product) {
            html += '<tr>';
            html += '<td>' + product.name + '</td>';
            html += '<td class="text-end">' + product.quantity + '</td>';
            html += '<td class="text-end">' + product.min_quantity + '</td>';
            html += '</tr>';
        });
        $('#low-stock-products').html(html);
    }

    // Utility Functions
    function formatMoney(value) {
        return new Intl.NumberFormat('{{ language_code }}', {
            style: 'currency',
            currency: '{{ currency_code }}'
        }).format(value);
    }

    function showLoading() {
        $('.grid-stack-item').append('<div class="loading-overlay"><div class="loading-spinner"></div></div>');
    }

    function hideLoading() {
        $('.loading-overlay').remove();
    }

    function getUrlParam(name) {
        var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
        return results ? results[1] : null;
    }

    var refreshTimer;
    function startAutoRefresh(interval) {
        stopAutoRefresh();
        refreshTimer = setInterval(refreshDashboard, interval * 1000);
    }

    function stopAutoRefresh() {
        if (refreshTimer) {
            clearInterval(refreshTimer);
        }
    }

    function refreshDashboard() {
        var dateRange = $('input[name="daterange"]').data('daterangepicker');
        updateDashboardData(dateRange.startDate.format('YYYY-MM-DD'), dateRange.endDate.format('YYYY-MM-DD'));
    }

    // Initialize with saved settings
    if (settings.refresh_interval > 0) {
        startAutoRefresh(settings.refresh_interval);
    }

    if (!settings.enable_animations) {
        Chart.defaults.global.animation.duration = 0;
    }

    // Set initial values in settings modal
    $('select[name="refresh_interval"]').val(settings.refresh_interval);
    $('select[name="default_tab"]').val(settings.default_tab);
    $('#enable-animations').prop('checked', settings.enable_animations == 1);
    $('#enable-cache').prop('checked', settings.enable_cache == 1);
});