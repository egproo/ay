$(document).ready(function() {
    'use strict';
    
    // Initialize page
    initSelect2();
    loadReturns();
    
    // Event handlers
    $('#btn-filter').on('click', function() {
        loadReturns();
    });
    
    $('#btn-clear-filter').on('click', function() {
        clearFilters();
    });
    
    $('#btn-add-return').on('click', function() {
        openAddModal();
    });
    
    // Bulk actions
    $('#btn-bulk-action').on('click', function() {
        var action = $('#bulk-action').val();
        var selected = getSelectedReturns();
        
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
        loadReturns();
    });
    
    // Auto-filter on input change
    $('input[name="filter_return_id"], input[name="filter_po_number"], input[name="filter_date_start"], input[name="filter_date_end"], select[name="filter_status"], select[name="filter_supplier"]').on('input change', function() {
        loadReturns();
    });
});

function initSelect2() {
    if (typeof $.fn.select2 !== 'undefined') {
        $('#filter-status').select2({
            theme: 'bootstrap-5',
            placeholder: getLanguageString('text_select_status'),
            allowClear: true
        });
        
        $('#filter-supplier').select2({
            theme: 'bootstrap-5',
            placeholder: getLanguageString('text_select_supplier'),
            allowClear: true
        });
        
        $('#bulk-action').select2({
            theme: 'bootstrap-5',
            minimumResultsForSearch: -1
        });
    }
}

function loadReturns(page) {
    page = page || 1;
    
    var filters = {
        filter_return_id: $('input[name="filter_return_id"]').val(),
        filter_po_id: $('select[name="filter_po_number"]').val(),
        filter_supplier_id: $('select[name="filter_supplier"]').val(),
        filter_status: $('select[name="filter_status"]').val(),
        filter_date_start: $('input[name="filter_date_start"]').val(),
        filter_date_end: $('input[name="filter_date_end"]').val(),
        page: page
    };
    
    showLoading();
    
    $.ajax({
        url: 'index.php?route=purchase/return/ajaxList&user_token=' + getUserToken(),
        type: 'GET',
        data: filters,
        dataType: 'json',
        success: function(response) {
            if (response.returns) {
                updateReturnsList(response.returns);
                updateStats(response.stats);
                updatePagination(response.pagination);
            } else {
                showError(response.error || 'Failed to load returns');
            }
            hideLoading();
        },
        error: function() {
            showError('Network error occurred');
            hideLoading();
        }
    });
}

function updateReturnsList(returns) {
    var tbody = $('#table-returns tbody');
    tbody.empty();
    
    if (returns.length === 0) {
        tbody.append('<tr><td colspan="7" class="text-center">' + getLanguageString('text_no_results') + '</td></tr>');
        return;
    }
    
    $.each(returns, function(index, returnItem) {
        var row = $('<tr>');
        
        // Checkbox
        row.append('<td><input type="checkbox" class="return-checkbox" value="' + returnItem.return_id + '"></td>');
        
        // Return ID
        row.append('<td><a href="javascript:void(0);" onclick="viewReturn(' + returnItem.return_id + ')">' + returnItem.return_id + '</a></td>');
        
        // PO Number
        var poLink = returnItem.po_number ? 
            '<a href="index.php?route=purchase/order/view&po_id=' + returnItem.po_id + '&user_token=' + getUserToken() + '">' + returnItem.po_number + '</a>' : 
            '-';
        row.append('<td>' + poLink + '</td>');
        
        // Supplier
        row.append('<td>' + returnItem.supplier_name + '</td>');
        
        // Status
        var statusBadge = getStatusBadge(returnItem.status_id, returnItem.status_text);
        row.append('<td class="text-center">' + statusBadge + '</td>');
        
        // Date Added
        row.append('<td>' + returnItem.date_added + '</td>');
        
        // Date Modified
        row.append('<td>' + (returnItem.date_modified || '-') + '</td>');
        
        // Actions
        var actions = buildActionButtons(returnItem);
        row.append('<td>' + actions + '</td>');
        
        tbody.append(row);
    });
    
    // Bind action events
    bindActionEvents();
}

function getStatusBadge(statusId, statusText) {
    var badges = {
        '1': '<span class="badge bg-warning">',  // Pending
        '2': '<span class="badge bg-success">',  // Approved
        '3': '<span class="badge bg-danger">',   // Rejected
        '4': '<span class="badge bg-secondary">' // Cancelled
    };
    
    var badgeClass = badges[statusId] || '<span class="badge bg-light">';
    return badgeClass + statusText + '</span>';
}

function buildActionButtons(returnItem) {
    var buttons = '';
    
    // View button
    buttons += '<button type="button" class="btn btn-sm btn-outline-info me-1 btn-view" data-id="' + returnItem.return_id + '" title="View"><i class="fas fa-eye"></i></button>';
    
    // Edit button
    if (returnItem.can_edit) {
        buttons += '<button type="button" class="btn btn-sm btn-outline-primary me-1 btn-edit" data-id="' + returnItem.return_id + '" title="Edit"><i class="fas fa-edit"></i></button>';
    }
    
    // Approve button
    if (returnItem.can_approve) {
        buttons += '<button type="button" class="btn btn-sm btn-outline-success me-1 btn-approve" data-id="' + returnItem.return_id + '" title="Approve"><i class="fas fa-check"></i></button>';
    }
    
    // Reject button
    if (returnItem.can_reject) {
        buttons += '<button type="button" class="btn btn-sm btn-outline-warning me-1 btn-reject" data-id="' + returnItem.return_id + '" title="Reject"><i class="fas fa-times"></i></button>';
    }
    
    // Credit Note button
    if (returnItem.can_create_credit) {
        buttons += '<button type="button" class="btn btn-sm btn-outline-info me-1 btn-credit-note" data-id="' + returnItem.return_id + '" title="Create Credit Note"><i class="fas fa-file-invoice"></i></button>';
    }
    
    // Print button
    if (returnItem.can_view) {
        buttons += '<a href="index.php?route=purchase/return/print&return_id=' + returnItem.return_id + '&user_token=' + getUserToken() + '" target="_blank" class="btn btn-sm btn-outline-secondary me-1" title="Print"><i class="fas fa-print"></i></a>';
    }
    
    // Delete button
    if (returnItem.can_delete) {
        buttons += '<button type="button" class="btn btn-sm btn-outline-danger btn-delete" data-id="' + returnItem.return_id + '" title="Delete"><i class="fas fa-trash"></i></button>';
    }
    
    return buttons;
}

function bindActionEvents() {
    // View return
    $('.btn-view').off('click').on('click', function() {
        var returnId = $(this).data('id');
        viewReturn(returnId);
    });
    
    // Edit return
    $('.btn-edit').off('click').on('click', function() {
        var returnId = $(this).data('id');
        editReturn(returnId);
    });
    
    // Approve return
    $('.btn-approve').off('click').on('click', function() {
        var returnId = $(this).data('id');
        approveReturn(returnId);
    });
    
    // Reject return
    $('.btn-reject').off('click').on('click', function() {
        var returnId = $(this).data('id');
        rejectReturn(returnId);
    });
    
    // Create credit note
    $('.btn-credit-note').off('click').on('click', function() {
        var returnId = $(this).data('id');
        createCreditNote(returnId);
    });
    
    // Delete return
    $('.btn-delete').off('click').on('click', function() {
        var returnId = $(this).data('id');
        deleteReturn(returnId);
    });
}

function viewReturn(returnId) {
    $('#modal-return-view .modal-content').load('index.php?route=purchase/return/view&user_token=' + getUserToken() + '&return_id=' + returnId, function() {
        $('#modal-return-view').modal('show');
    });
}

function editReturn(returnId) {
    $('#modal-return-form .modal-content').load('index.php?route=purchase/return/form&user_token=' + getUserToken() + '&return_id=' + returnId, function() {
        initializeReturnForm();
        $('#modal-return-form').modal('show');
    });
}

function approveReturn(returnId) {
    if (!confirm(getLanguageString('text_confirm_approve'))) {
        return;
    }
    
    $.ajax({
        url: 'index.php?route=purchase/return/approve&user_token=' + getUserToken(),
        type: 'POST',
        data: {
            return_id: returnId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showSuccess(response.success);
                loadReturns();
            } else {
                showError(response.error);
            }
        },
        error: function() {
            showError('Network error occurred');
        }
    });
}

function rejectReturn(returnId) {
    var reason = prompt(getLanguageString('text_enter_rejection_reason'));
    if (reason === null) {
        return; // User cancelled
    }
    
    $.ajax({
        url: 'index.php?route=purchase/return/reject&user_token=' + getUserToken(),
        type: 'POST',
        data: {
            return_id: returnId,
            reason: reason
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showSuccess(response.success);
                loadReturns();
            } else {
                showError(response.error);
            }
        },
        error: function() {
            showError('Network error occurred');
        }
    });
}

function createCreditNote(returnId) {
    if (!confirm(getLanguageString('text_confirm_create_credit'))) {
        return;
    }
    
    $.ajax({
        url: 'index.php?route=purchase/return/createCreditNote&user_token=' + getUserToken(),
        type: 'POST',
        data: {
            return_id: returnId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showSuccess(response.success);
                loadReturns();
            } else {
                showError(response.error);
            }
        },
        error: function() {
            showError('Network error occurred');
        }
    });
}

function deleteReturn(returnId) {
    if (!confirm(getLanguageString('text_confirm_delete'))) {
        return;
    }
    
    $.ajax({
        url: 'index.php?route=purchase/return/delete&user_token=' + getUserToken(),
        type: 'POST',
        data: {
            return_id: returnId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showSuccess(response.success);
                loadReturns();
            } else {
                showError(response.error);
            }
        },
        error: function() {
            showError('Network error occurred');
        }
    });
}

function openAddModal() {
    $('#modal-return-form .modal-content').load('index.php?route=purchase/return/form&user_token=' + getUserToken(), function() {
        initializeReturnForm();
        $('#modal-return-form').modal('show');
    });
}

function initializeReturnForm() {
    // Initialize form elements
    if (typeof $.fn.select2 !== 'undefined') {
        $('#return-form select').select2({
            theme: 'bootstrap-5'
        });
    }
    
    // Initialize date pickers
    if (typeof $.fn.datepicker !== 'undefined') {
        $('#return-form .date').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true
        });
    }
}

function getSelectedReturns() {
    var selected = [];
    $('.return-checkbox:checked').each(function() {
        selected.push($(this).val());
    });
    return selected;
}

function updateStats(stats) {
    if (stats) {
        $('#total-returns').text(stats.total || 0);
        $('#pending-returns').text(stats.pending || 0);
        $('#approved-returns').text(stats.approved || 0);
        $('#rejected-returns').text(stats.rejected || 0);
    }
}

function clearFilters() {
    $('input[name="filter_return_id"]').val('');
    $('select[name="filter_po_number"]').val(null).trigger('change');
    $('select[name="filter_supplier"]').val(null).trigger('change');
    $('select[name="filter_status"]').val(null).trigger('change');
    $('input[name="filter_date_start"]').val('');
    $('input[name="filter_date_end"]').val('');
    loadReturns();
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
    $('#pagination').html(pagination);
}

// Global variables
var currentPage = 1;
