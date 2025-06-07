$(document).ready(function() {
    'use strict';
    
    // Initialize page
    initSelect2();
    loadOrders();
    
    // Event handlers
    $('#btn-filter').on('click', function() {
        loadOrders();
    });
    
    $('#btn-clear-filter').on('click', function() {
        clearFilters();
    });
    
    $('#btn-add-order').on('click', function() {
        window.location.href = 'index.php?route=purchase/order/form&user_token=' + getUserToken();
    });
    
    // Bulk actions
    $('#btn-bulk-action').on('click', function() {
        var action = $('#bulk-action').val();
        var selected = getSelectedOrders();
        
        if (selected.length === 0) {
            alert(getLanguageString('error_no_selection'));
            return;
        }
        
        if (!action) {
            alert(getLanguageString('error_select_action'));
            return;
        }
        
        switch (action) {
            case 'approve':
                bulkApprove(selected);
                break;
            case 'reject':
                bulkReject(selected);
                break;
            case 'delete':
                bulkDelete(selected);
                break;
        }
    });
    
    // Filter by status
    $('.status-filter').on('click', function() {
        var status = $(this).data('status');
        $('#filter-status').val(status);
        loadOrders();
    });
});

function initSelect2() {
    if (typeof $.fn.select2 !== 'undefined') {
        $('#filter-supplier').select2({
            theme: 'bootstrap-5',
            placeholder: getLanguageString('text_select_supplier'),
            allowClear: true
        });
        
        $('#filter-status').select2({
            theme: 'bootstrap-5',
            placeholder: getLanguageString('text_select_status'),
            allowClear: true
        });
        
        $('#filter-quotation').select2({
            theme: 'bootstrap-5',
            placeholder: getLanguageString('text_select_quotation'),
            allowClear: true
        });
        
        $('#bulk-action').select2({
            theme: 'bootstrap-5',
            minimumResultsForSearch: -1
        });
    }
}

function loadOrders() {
    var filters = {
        filter_po_number: $('#filter-po-number').val(),
        filter_supplier_id: $('#filter-supplier').val(),
        filter_quotation_id: $('#filter-quotation').val(),
        filter_status: $('#filter-status').val(),
        filter_date_start: $('#filter-date-start').val(),
        filter_date_end: $('#filter-date-end').val(),
        page: currentPage || 1
    };
    
    showLoading();
    
    $.ajax({
        url: 'index.php?route=purchase/order/ajaxList&user_token=' + getUserToken(),
        type: 'GET',
        data: filters,
        dataType: 'json',
        success: function(response) {
            if (response.orders) {
                updateOrdersList(response.orders);
                updateStats(response.stats);
                updatePagination(response.pagination);
            } else {
                showError(response.error || 'Failed to load orders');
            }
            hideLoading();
        },
        error: function() {
            showError('Network error occurred');
            hideLoading();
        }
    });
}

function updateOrdersList(orders) {
    var tbody = $('#orders-table tbody');
    tbody.empty();
    
    if (orders.length === 0) {
        tbody.append('<tr><td colspan="9" class="text-center">' + getLanguageString('text_no_results') + '</td></tr>');
        return;
    }
    
    $.each(orders, function(index, order) {
        var row = $('<tr>');
        
        // Checkbox
        row.append('<td><input type="checkbox" class="order-checkbox" value="' + order.po_id + '"></td>');
        
        // PO Number
        row.append('<td><a href="index.php?route=purchase/order/view&po_id=' + order.po_id + '&user_token=' + getUserToken() + '">' + order.po_number + '</a></td>');
        
        // Quotation Number
        var quotationLink = order.quotation_number ? 
            '<a href="index.php?route=purchase/quotation/view&quotation_id=' + order.quotation_id + '&user_token=' + getUserToken() + '">' + order.quotation_number + '</a>' : 
            '-';
        row.append('<td>' + quotationLink + '</td>');
        
        // Supplier
        row.append('<td>' + order.supplier_name + '</td>');
        
        // Total Amount
        row.append('<td class="text-end">' + order.total_formatted + '</td>');
        
        // Status
        var statusBadge = getStatusBadge(order.status, order.status_text);
        row.append('<td>' + statusBadge + '</td>');
        
        // Order Date
        row.append('<td>' + order.order_date + '</td>');
        
        // Expected Delivery
        row.append('<td>' + (order.expected_delivery_date || '-') + '</td>');
        
        // Actions
        var actions = buildActionButtons(order);
        row.append('<td>' + actions + '</td>');
        
        tbody.append(row);
    });
    
    // Bind action events
    bindActionEvents();
}

function getStatusBadge(status, statusText) {
    var badges = {
        'draft': '<span class="badge bg-secondary">',
        'pending': '<span class="badge bg-warning">',
        'approved': '<span class="badge bg-success">',
        'rejected': '<span class="badge bg-danger">',
        'cancelled': '<span class="badge bg-dark">',
        'sent_to_vendor': '<span class="badge bg-info">',
        'confirmed_by_vendor': '<span class="badge bg-primary">',
        'partially_received': '<span class="badge bg-warning">',
        'fully_received': '<span class="badge bg-success">',
        'completed': '<span class="badge bg-success">'
    };
    
    var badgeClass = badges[status] || '<span class="badge bg-light">';
    return badgeClass + statusText + '</span>';
}

function buildActionButtons(order) {
    var buttons = '';
    
    // View button
    buttons += '<a href="index.php?route=purchase/order/view&po_id=' + order.po_id + '&user_token=' + getUserToken() + '" class="btn btn-sm btn-outline-info me-1" title="View"><i class="fas fa-eye"></i></a>';
    
    // Edit button
    if (order.can_edit) {
        buttons += '<a href="index.php?route=purchase/order/form&po_id=' + order.po_id + '&user_token=' + getUserToken() + '" class="btn btn-sm btn-outline-primary me-1" title="Edit"><i class="fas fa-edit"></i></a>';
    }
    
    // Approve button
    if (order.can_approve) {
        buttons += '<button type="button" class="btn btn-sm btn-outline-success me-1 btn-approve" data-id="' + order.po_id + '" title="Approve"><i class="fas fa-check"></i></button>';
    }
    
    // Reject button
    if (order.can_reject) {
        buttons += '<button type="button" class="btn btn-sm btn-outline-warning me-1 btn-reject" data-id="' + order.po_id + '" title="Reject"><i class="fas fa-times"></i></button>';
    }
    
    // Print button
    if (order.can_print) {
        buttons += '<a href="index.php?route=purchase/order/print&po_id=' + order.po_id + '&user_token=' + getUserToken() + '" target="_blank" class="btn btn-sm btn-outline-secondary me-1" title="Print"><i class="fas fa-print"></i></a>';
    }
    
    // Create Receipt button
    if (order.can_create_receipt) {
        buttons += '<a href="index.php?route=purchase/order/receipt&po_id=' + order.po_id + '&user_token=' + getUserToken() + '" class="btn btn-sm btn-outline-info me-1" title="Create Receipt"><i class="fas fa-truck"></i></a>';
    }
    
    // Match button
    if (order.can_match) {
        buttons += '<a href="index.php?route=purchase/order/match&po_id=' + order.po_id + '&user_token=' + getUserToken() + '" class="btn btn-sm btn-outline-primary me-1" title="Match"><i class="fas fa-balance-scale"></i></a>';
    }
    
    // Delete button
    if (order.can_delete) {
        buttons += '<button type="button" class="btn btn-sm btn-outline-danger btn-delete" data-id="' + order.po_id + '" title="Delete"><i class="fas fa-trash"></i></button>';
    }
    
    return buttons;
}

function bindActionEvents() {
    // Approve order
    $('.btn-approve').off('click').on('click', function() {
        var orderId = $(this).data('id');
        approveOrder(orderId);
    });
    
    // Reject order
    $('.btn-reject').off('click').on('click', function() {
        var orderId = $(this).data('id');
        rejectOrder(orderId);
    });
    
    // Delete order
    $('.btn-delete').off('click').on('click', function() {
        var orderId = $(this).data('id');
        deleteOrder(orderId);
    });
}

function approveOrder(orderId) {
    if (!confirm(getLanguageString('text_confirm_approve'))) {
        return;
    }
    
    $.ajax({
        url: 'index.php?route=purchase/order/ajaxApprove&po_id=' + orderId + '&user_token=' + getUserToken(),
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showSuccess(response.success);
                loadOrders();
            } else {
                showError(response.error);
            }
        },
        error: function() {
            showError('Network error occurred');
        }
    });
}

function rejectOrder(orderId) {
    var reason = prompt(getLanguageString('text_enter_rejection_reason'));
    if (reason === null) {
        return;
    }
    
    $.ajax({
        url: 'index.php?route=purchase/order/ajaxReject&user_token=' + getUserToken(),
        type: 'POST',
        data: {
            po_id: orderId,
            reason: reason
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showSuccess(response.success);
                loadOrders();
            } else {
                showError(response.error);
            }
        },
        error: function() {
            showError('Network error occurred');
        }
    });
}

function deleteOrder(orderId) {
    if (!confirm(getLanguageString('text_confirm_delete'))) {
        return;
    }
    
    $.ajax({
        url: 'index.php?route=purchase/order/ajaxDelete&po_id=' + orderId + '&user_token=' + getUserToken(),
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showSuccess(response.success);
                loadOrders();
            } else {
                showError(response.error);
            }
        },
        error: function() {
            showError('Network error occurred');
        }
    });
}

function getSelectedOrders() {
    var selected = [];
    $('.order-checkbox:checked').each(function() {
        selected.push($(this).val());
    });
    return selected;
}

function updateStats(stats) {
    if (stats) {
        $('#total-orders').text(stats.total || 0);
        $('#pending-orders').text(stats.pending || 0);
        $('#approved-orders').text(stats.approved || 0);
        $('#received-orders').text(stats.received || 0);
    }
}

function clearFilters() {
    $('#filter-po-number').val('');
    $('#filter-supplier').val(null).trigger('change');
    $('#filter-quotation').val(null).trigger('change');
    $('#filter-status').val(null).trigger('change');
    $('#filter-date-start').val('');
    $('#filter-date-end').val('');
    loadOrders();
}

function getUserToken() {
    return typeof window.user_token !== 'undefined' ? window.user_token : '';
}

function getLanguageString(key) {
    return typeof window.language !== 'undefined' && window.language[key] ? window.language[key] : key;
}

function showLoading() {
    $('#loading-indicator').show();
}

function hideLoading() {
    $('#loading-indicator').hide();
}

function showSuccess(message) {
    // Implementation depends on your notification system
    alert(message);
}

function showError(message) {
    // Implementation depends on your notification system
    alert(message);
}

function updatePagination(pagination) {
    // Implementation for pagination
    $('#pagination-info').html(pagination);
}

// Global variables
var currentPage = 1;
