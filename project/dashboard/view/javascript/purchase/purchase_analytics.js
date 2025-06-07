$(document).ready(function() {
    'use strict';
    
    // Initialize page
    initDatePickers();
    initCharts();
    loadAnalyticsData();
    
    // Event handlers
    $('#btn-filter').on('click', function() {
        loadAnalyticsData();
    });
    
    $('#btn-export').on('click', function() {
        exportReport();
    });
    
    $('#btn-refresh').on('click', function() {
        refreshAllCharts();
    });
    
    // Auto-refresh every 5 minutes
    setInterval(function() {
        if ($('#auto-refresh').is(':checked')) {
            refreshAllCharts();
        }
    }, 300000); // 5 minutes
    
    // Date range change handler
    $('#date-start, #date-end').on('change', function() {
        if ($('#auto-update').is(':checked')) {
            loadAnalyticsData();
        }
    });
    
    // Chart type change handlers
    $('.chart-type-selector').on('change', function() {
        var chartType = $(this).val();
        var chartId = $(this).data('chart');
        updateChartType(chartId, chartType);
    });
});

function initDatePickers() {
    if (typeof $.fn.datepicker !== 'undefined') {
        $('#date-start, #date-end').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            todayHighlight: true
        });
    }
}

function initCharts() {
    // Initialize all chart containers
    initSpendingByCategoryChart();
    initSpendingTrendChart();
    initTopSuppliersChart();
    initSupplierPerformanceChart();
    initPOStatusChart();
    initLeadTimeChart();
    initInvoiceMatchingChart();
    initPriceVarianceChart();
}

function loadAnalyticsData() {
    var dateStart = $('#date-start').val();
    var dateEnd = $('#date-end').val();
    
    if (!dateStart || !dateEnd) {
        showError(getLanguageString('error_date_range_required'));
        return;
    }
    
    showLoading();
    
    // Load all analytics data
    Promise.all([
        loadSpendingByCategory(dateStart, dateEnd),
        loadSpendingTrend(dateStart, dateEnd),
        loadTopSuppliers(dateStart, dateEnd),
        loadSupplierPerformance(dateStart, dateEnd),
        loadPOStatus(dateStart, dateEnd),
        loadLeadTime(dateStart, dateEnd),
        loadInvoiceMatching(dateStart, dateEnd),
        loadPriceVariance(dateStart, dateEnd)
    ]).then(function() {
        hideLoading();
        updateLastRefreshTime();
    }).catch(function(error) {
        hideLoading();
        showError('Error loading analytics data: ' + error);
    });
}

function loadSpendingByCategory(dateStart, dateEnd) {
    return new Promise(function(resolve, reject) {
        $.ajax({
            url: 'index.php?route=purchase/purchase_analytics/ajaxGetSpendingData&user_token=' + getUserToken(),
            type: 'GET',
            data: {
                type: 'category',
                date_start: dateStart,
                date_end: dateEnd
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data) {
                    updateSpendingByCategoryChart(response.data);
                    resolve(response.data);
                } else {
                    reject(response.error || 'Failed to load spending by category data');
                }
            },
            error: function() {
                reject('Network error occurred');
            }
        });
    });
}

function loadSpendingTrend(dateStart, dateEnd) {
    return new Promise(function(resolve, reject) {
        $.ajax({
            url: 'index.php?route=purchase/purchase_analytics/ajaxGetSpendingData&user_token=' + getUserToken(),
            type: 'GET',
            data: {
                type: 'trend',
                date_start: dateStart,
                date_end: dateEnd
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data) {
                    updateSpendingTrendChart(response.data);
                    resolve(response.data);
                } else {
                    reject(response.error || 'Failed to load spending trend data');
                }
            },
            error: function() {
                reject('Network error occurred');
            }
        });
    });
}

function loadTopSuppliers(dateStart, dateEnd) {
    return new Promise(function(resolve, reject) {
        $.ajax({
            url: 'index.php?route=purchase/purchase_analytics/ajaxGetSpendingData&user_token=' + getUserToken(),
            type: 'GET',
            data: {
                type: 'supplier',
                date_start: dateStart,
                date_end: dateEnd
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data) {
                    updateTopSuppliersChart(response.data);
                    resolve(response.data);
                } else {
                    reject(response.error || 'Failed to load top suppliers data');
                }
            },
            error: function() {
                reject('Network error occurred');
            }
        });
    });
}

function loadSupplierPerformance(dateStart, dateEnd) {
    return new Promise(function(resolve, reject) {
        $.ajax({
            url: 'index.php?route=purchase/purchase_analytics/ajaxGetSpendingData&user_token=' + getUserToken(),
            type: 'GET',
            data: {
                type: 'performance',
                date_start: dateStart,
                date_end: dateEnd
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data) {
                    updateSupplierPerformanceChart(response.data);
                    resolve(response.data);
                } else {
                    reject(response.error || 'Failed to load supplier performance data');
                }
            },
            error: function() {
                reject('Network error occurred');
            }
        });
    });
}

function loadPOStatus(dateStart, dateEnd) {
    return new Promise(function(resolve, reject) {
        $.ajax({
            url: 'index.php?route=purchase/purchase_analytics/ajaxGetSpendingData&user_token=' + getUserToken(),
            type: 'GET',
            data: {
                type: 'status',
                date_start: dateStart,
                date_end: dateEnd
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data) {
                    updatePOStatusChart(response.data);
                    resolve(response.data);
                } else {
                    reject(response.error || 'Failed to load PO status data');
                }
            },
            error: function() {
                reject('Network error occurred');
            }
        });
    });
}

function loadLeadTime(dateStart, dateEnd) {
    return new Promise(function(resolve, reject) {
        $.ajax({
            url: 'index.php?route=purchase/purchase_analytics/ajaxGetSpendingData&user_token=' + getUserToken(),
            type: 'GET',
            data: {
                type: 'lead_time',
                date_start: dateStart,
                date_end: dateEnd
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data) {
                    updateLeadTimeChart(response.data);
                    resolve(response.data);
                } else {
                    reject(response.error || 'Failed to load lead time data');
                }
            },
            error: function() {
                reject('Network error occurred');
            }
        });
    });
}

function loadInvoiceMatching(dateStart, dateEnd) {
    return new Promise(function(resolve, reject) {
        $.ajax({
            url: 'index.php?route=purchase/purchase_analytics/ajaxGetSpendingData&user_token=' + getUserToken(),
            type: 'GET',
            data: {
                type: 'matching',
                date_start: dateStart,
                date_end: dateEnd
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data) {
                    updateInvoiceMatchingChart(response.data);
                    resolve(response.data);
                } else {
                    reject(response.error || 'Failed to load invoice matching data');
                }
            },
            error: function() {
                reject('Network error occurred');
            }
        });
    });
}

function loadPriceVariance(dateStart, dateEnd) {
    return new Promise(function(resolve, reject) {
        $.ajax({
            url: 'index.php?route=purchase/purchase_analytics/ajaxGetSpendingData&user_token=' + getUserToken(),
            type: 'GET',
            data: {
                type: 'variance',
                date_start: dateStart,
                date_end: dateEnd
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data) {
                    updatePriceVarianceChart(response.data);
                    resolve(response.data);
                } else {
                    reject(response.error || 'Failed to load price variance data');
                }
            },
            error: function() {
                reject('Network error occurred');
            }
        });
    });
}

// Chart initialization functions
function initSpendingByCategoryChart() {
    if (typeof Chart !== 'undefined') {
        var ctx = document.getElementById('spending-category-chart');
        if (ctx) {
            window.spendingCategoryChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: [
                            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                            '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    }
}

function initSpendingTrendChart() {
    if (typeof Chart !== 'undefined') {
        var ctx = document.getElementById('spending-trend-chart');
        if (ctx) {
            window.spendingTrendChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: getLanguageString('text_spending_trend'),
                        data: [],
                        borderColor: '#36A2EB',
                        backgroundColor: 'rgba(54, 162, 235, 0.1)',
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    }
}

function initTopSuppliersChart() {
    if (typeof Chart !== 'undefined') {
        var ctx = document.getElementById('top-suppliers-chart');
        if (ctx) {
            window.topSuppliersChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: getLanguageString('text_amount'),
                        data: [],
                        backgroundColor: '#4BC0C0'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    }
}

function initSupplierPerformanceChart() {
    if (typeof Chart !== 'undefined') {
        var ctx = document.getElementById('supplier-performance-chart');
        if (ctx) {
            window.supplierPerformanceChart = new Chart(ctx, {
                type: 'radar',
                data: {
                    labels: [],
                    datasets: []
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
        }
    }
}

function initPOStatusChart() {
    if (typeof Chart !== 'undefined') {
        var ctx = document.getElementById('po-status-chart');
        if (ctx) {
            window.poStatusChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: [
                            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                            '#9966FF', '#FF9F40'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    }
}

function initLeadTimeChart() {
    if (typeof Chart !== 'undefined') {
        var ctx = document.getElementById('lead-time-chart');
        if (ctx) {
            window.leadTimeChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: getLanguageString('text_avg_lead_time'),
                        data: [],
                        backgroundColor: '#9966FF'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: getLanguageString('text_days')
                            }
                        }
                    }
                }
            });
        }
    }
}

function initInvoiceMatchingChart() {
    if (typeof Chart !== 'undefined') {
        var ctx = document.getElementById('invoice-matching-chart');
        if (ctx) {
            window.invoiceMatchingChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: [
                        getLanguageString('text_full_match'),
                        getLanguageString('text_partial_match'),
                        getLanguageString('text_no_match')
                    ],
                    datasets: [{
                        data: [],
                        backgroundColor: ['#4BC0C0', '#FFCE56', '#FF6384']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    }
}

function initPriceVarianceChart() {
    // Price variance will be displayed as a table, not a chart
    updatePriceVarianceTable([]);
}

// Chart update functions
function updateSpendingByCategoryChart(data) {
    if (window.spendingCategoryChart && data) {
        window.spendingCategoryChart.data.labels = data.map(item => item.name);
        window.spendingCategoryChart.data.datasets[0].data = data.map(item => item.amount);
        window.spendingCategoryChart.update();
    }
}

function updateSpendingTrendChart(data) {
    if (window.spendingTrendChart && data) {
        window.spendingTrendChart.data.labels = data.map(item => item.date_period);
        window.spendingTrendChart.data.datasets[0].data = data.map(item => item.amount);
        window.spendingTrendChart.update();
    }
}

function updateTopSuppliersChart(data) {
    if (window.topSuppliersChart && data) {
        window.topSuppliersChart.data.labels = data.map(item => item.name);
        window.topSuppliersChart.data.datasets[0].data = data.map(item => item.amount);
        window.topSuppliersChart.update();
    }
}

function updateSupplierPerformanceChart(data) {
    if (window.supplierPerformanceChart && data) {
        var labels = ['Quality Rate', 'On-Time Rate', 'Price Competitiveness'];
        var datasets = data.map((supplier, index) => ({
            label: supplier.name,
            data: [supplier.quality_rate, supplier.on_time_rate, supplier.price_score],
            backgroundColor: `rgba(${54 + index * 50}, ${162 - index * 30}, ${235 + index * 20}, 0.2)`,
            borderColor: `rgba(${54 + index * 50}, ${162 - index * 30}, ${235 + index * 20}, 1)`,
            pointBackgroundColor: `rgba(${54 + index * 50}, ${162 - index * 30}, ${235 + index * 20}, 1)`
        }));
        
        window.supplierPerformanceChart.data.labels = labels;
        window.supplierPerformanceChart.data.datasets = datasets;
        window.supplierPerformanceChart.update();
    }
}

function updatePOStatusChart(data) {
    if (window.poStatusChart && data) {
        window.poStatusChart.data.labels = data.map(item => item.status);
        window.poStatusChart.data.datasets[0].data = data.map(item => item.count);
        window.poStatusChart.update();
    }
}

function updateLeadTimeChart(data) {
    if (window.leadTimeChart && data) {
        window.leadTimeChart.data.labels = data.map(item => item.supplier_name);
        window.leadTimeChart.data.datasets[0].data = data.map(item => item.avg_lead_time);
        window.leadTimeChart.update();
    }
}

function updateInvoiceMatchingChart(data) {
    if (window.invoiceMatchingChart && data) {
        window.invoiceMatchingChart.data.datasets[0].data = [
            data.full_match || 0,
            data.partial_match || 0,
            data.no_match || 0
        ];
        window.invoiceMatchingChart.update();
    }
}

function updatePriceVarianceTable(data) {
    var tbody = $('#price-variance-table tbody');
    tbody.empty();
    
    if (!data || data.length === 0) {
        tbody.append('<tr><td colspan="6" class="text-center">' + getLanguageString('text_no_results') + '</td></tr>');
        return;
    }
    
    $.each(data, function(index, item) {
        var row = $('<tr>');
        row.append('<td>' + item.po_number + '</td>');
        row.append('<td>' + item.product_name + '</td>');
        row.append('<td>' + item.supplier_name + '</td>');
        row.append('<td class="text-right">' + item.order_price + '</td>');
        row.append('<td class="text-right">' + item.invoice_price + '</td>');
        row.append('<td class="text-right ' + (item.variance_percent > 0 ? 'text-danger' : 'text-success') + '">' + item.variance_percent + '%</td>');
        tbody.append(row);
    });
}

function updateChartType(chartId, chartType) {
    // Implementation for changing chart types
    switch (chartId) {
        case 'spending-category':
            if (window.spendingCategoryChart) {
                window.spendingCategoryChart.config.type = chartType;
                window.spendingCategoryChart.update();
            }
            break;
        case 'spending-trend':
            if (window.spendingTrendChart) {
                window.spendingTrendChart.config.type = chartType;
                window.spendingTrendChart.update();
            }
            break;
        // Add more cases as needed
    }
}

function refreshAllCharts() {
    loadAnalyticsData();
}

function exportReport() {
    var dateStart = $('#date-start').val();
    var dateEnd = $('#date-end').val();
    
    if (!dateStart || !dateEnd) {
        showError(getLanguageString('error_date_range_required'));
        return;
    }
    
    var exportUrl = 'index.php?route=purchase/purchase_analytics/export&user_token=' + getUserToken() + 
                   '&date_start=' + encodeURIComponent(dateStart) + 
                   '&date_end=' + encodeURIComponent(dateEnd);
    
    window.open(exportUrl, '_blank');
}

function updateLastRefreshTime() {
    var now = new Date();
    $('#last-refresh-time').text(now.toLocaleString());
}

function getUserToken() {
    return typeof window.user_token !== 'undefined' ? window.user_token : '';
}

function getLanguageString(key) {
    return typeof window.language !== 'undefined' && window.language[key] ? window.language[key] : key;
}

function showLoading() {
    $('#loading-indicator').show();
    $('.chart-container').addClass('loading');
}

function hideLoading() {
    $('#loading-indicator').hide();
    $('.chart-container').removeClass('loading');
}

function showSuccess(message) {
    // Implementation depends on your notification system
    alert(message);
}

function showError(message) {
    // Implementation depends on your notification system
    alert(message);
}

// Global variables
var currentDateStart = null;
var currentDateEnd = null;
