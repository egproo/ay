$(document).ready(function() {
    'use strict';
    
    // Initialize page
    initSelect2();
    loadReceipts();
    
    // Event handlers
    $('#btn-filter').on('click', function() {
        loadReceipts();
    });
    
    $('#btn-clear-filter').on('click', function() {
        clearFilters();
    });
    
    $('#btn-add-receipt').on('click', function() {
        openAddModal();
    });
    
    // Bulk actions
    $('#btn-bulk-action').on('click', function() {
        var action = $('#bulk-action').val();
        var selected = getSelectedReceipts();
        
        if (selected.length === 0) {
            alert(getLanguageString('error_no_selection'));
            return;
        }
        
        if (!action) {
            alert(getLanguageString('error_select_action'));
            return;
        }
        
        switch (action) {
            case 'complete':
                bulkComplete(selected);
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
        loadReceipts();
    });
    
    // Auto-filter on input change
    $('input[name="filter_receipt_number"], input[name="filter_po_number"], input[name="filter_date_start"], input[name="filter_date_end"], select[name="filter_status"]').on('input change', function() {
        loadReceipts();
    });
});

function initSelect2() {
    if (typeof $.fn.select2 !== 'undefined') {
        $('#filter-status').select2({
            theme: 'bootstrap-5',
            placeholder: getLanguageString('text_select_status'),
            allowClear: true
        });
        
        $('#filter-branch').select2({
            theme: 'bootstrap-5',
            placeholder: getLanguageString('text_select_branch'),
            allowClear: true
        });
        
        $('#bulk-action').select2({
            theme: 'bootstrap-5',
            minimumResultsForSearch: -1
        });
    }
}

function loadReceipts(page) {
    page = page || 1;
    
    var filters = {
        filter_receipt_number: $('input[name="filter_receipt_number"]').val(),
        filter_po_number: $('input[name="filter_po_number"]').val(),
        filter_status: $('select[name="filter_status"]').val(),
        filter_date_start: $('input[name="filter_date_start"]').val(),
        filter_date_end: $('input[name="filter_date_end"]').val(),
        page: page
    };
    
    showLoading();
    
    $.ajax({
        url: 'index.php?route=purchase/goods_receipt/ajaxList&user_token=' + getUserToken(),
        type: 'GET',
        data: filters,
        dataType: 'json',
        success: function(response) {
            if (response.receipts) {
                updateReceiptsList(response.receipts);
                updateStats(response.stats);
                updatePagination(response.pagination);
            } else {
                showError(response.error || 'Failed to load receipts');
            }
            hideLoading();
        },
        error: function() {
            showError('Network error occurred');
            hideLoading();
        }
    });
}

function updateReceiptsList(receipts) {
    var tbody = $('#table-receipts tbody');
    tbody.empty();
    
    if (receipts.length === 0) {
        tbody.append('<tr><td colspan="7" class="text-center">' + getLanguageString('text_no_results') + '</td></tr>');
        return;
    }
    
    $.each(receipts, function(index, receipt) {
        var row = $('<tr>');
        
        // Checkbox
        row.append('<td><input type="checkbox" class="receipt-checkbox" value="' + receipt.goods_receipt_id + '"></td>');
        
        // Receipt Number
        row.append('<td><a href="index.php?route=purchase/goods_receipt/view&goods_receipt_id=' + receipt.goods_receipt_id + '&user_token=' + getUserToken() + '">' + receipt.receipt_number + '</a></td>');
        
        // PO Number
        var poLink = receipt.po_number ? 
            '<a href="index.php?route=purchase/order/view&po_id=' + receipt.po_id + '&user_token=' + getUserToken() + '">' + receipt.po_number + '</a>' : 
            '-';
        row.append('<td>' + poLink + '</td>');
        
        // Receipt Date
        row.append('<td>' + receipt.receipt_date + '</td>');
        
        // Branch
        row.append('<td>' + receipt.branch_name + '</td>');
        
        // Status
        var statusBadge = getStatusBadge(receipt.status, receipt.status_text);
        row.append('<td>' + statusBadge + '</td>');
        
        // Quality Status
        var qualityBadge = getQualityStatusBadge(receipt.quality_status);
        row.append('<td>' + qualityBadge + '</td>');
        
        // Actions
        var actions = buildActionButtons(receipt);
        row.append('<td>' + actions + '</td>');
        
        tbody.append(row);
    });
    
    // Bind action events
    bindActionEvents();
}

function getStatusBadge(status, statusText) {
    var badges = {
        'pending': '<span class="badge bg-warning">',
        'received': '<span class="badge bg-success">',
        'partially_received': '<span class="badge bg-info">',
        'cancelled': '<span class="badge bg-danger">'
    };
    
    var badgeClass = badges[status] || '<span class="badge bg-light">';
    return badgeClass + statusText + '</span>';
}

function getQualityStatusBadge(qualityStatus) {
    var badges = {
        'pending': '<span class="badge bg-warning">Pending</span>',
        'approved': '<span class="badge bg-success">Approved</span>',
        'rejected': '<span class="badge bg-danger">Rejected</span>',
        'partial': '<span class="badge bg-info">Partial</span>'
    };
    
    return badges[qualityStatus] || '<span class="badge bg-light">-</span>';
}

function buildActionButtons(receipt) {
    var buttons = '';
    
    // View button
    buttons += '<a href="index.php?route=purchase/goods_receipt/view&goods_receipt_id=' + receipt.goods_receipt_id + '&user_token=' + getUserToken() + '" class="btn btn-sm btn-outline-info me-1" title="View"><i class="fas fa-eye"></i></a>';
    
    // Edit button
    if (receipt.can_edit) {
        buttons += '<a href="index.php?route=purchase/goods_receipt/form&goods_receipt_id=' + receipt.goods_receipt_id + '&user_token=' + getUserToken() + '" class="btn btn-sm btn-outline-primary me-1" title="Edit"><i class="fas fa-edit"></i></a>';
    }
    
    // Complete button
    if (receipt.can_complete) {
        buttons += '<button type="button" class="btn btn-sm btn-outline-success me-1 btn-complete" data-id="' + receipt.goods_receipt_id + '" title="Complete"><i class="fas fa-check"></i></button>';
    }
    
    // Quality Check button
    if (receipt.can_quality_check) {
        buttons += '<button type="button" class="btn btn-sm btn-outline-info me-1 btn-quality-check" data-id="' + receipt.goods_receipt_id + '" title="Quality Check"><i class="fas fa-flask"></i></button>';
    }
    
    // Print button
    if (receipt.can_view) {
        buttons += '<a href="index.php?route=purchase/goods_receipt/print&goods_receipt_id=' + receipt.goods_receipt_id + '&user_token=' + getUserToken() + '" target="_blank" class="btn btn-sm btn-outline-secondary me-1" title="Print"><i class="fas fa-print"></i></a>';
    }
    
    // Delete button
    if (receipt.can_delete) {
        buttons += '<button type="button" class="btn btn-sm btn-outline-danger btn-delete" data-id="' + receipt.goods_receipt_id + '" title="Delete"><i class="fas fa-trash"></i></button>';
    }
    
    return buttons;
}

function bindActionEvents() {
    // Complete receipt
    $('.btn-complete').off('click').on('click', function() {
        var receiptId = $(this).data('id');
        completeReceipt(receiptId);
    });
    
    // Quality check
    $('.btn-quality-check').off('click').on('click', function() {
        var receiptId = $(this).data('id');
        qualityCheck(receiptId);
    });
    
    // Delete receipt
    $('.btn-delete').off('click').on('click', function() {
        var receiptId = $(this).data('id');
        deleteReceipt(receiptId);
    });
}

function completeReceipt(receiptId) {
    if (!confirm(getLanguageString('text_confirm_complete'))) {
        return;
    }
    
    $.ajax({
        url: 'index.php?route=purchase/goods_receipt/ajaxComplete&user_token=' + getUserToken(),
        type: 'POST',
        data: {
            goods_receipt_id: receiptId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showSuccess(response.success);
                loadReceipts();
            } else {
                showError(response.error);
            }
        },
        error: function() {
            showError('Network error occurred');
        }
    });
}

function qualityCheck(receiptId) {
    $('#modal-quality-check .modal-content').load('index.php?route=purchase/goods_receipt/getQualityForm&user_token=' + getUserToken() + '&goods_receipt_id=' + receiptId, function() {
        initializeQualityForm();
        $('#modal-quality-check').modal('show');
    });
}

function deleteReceipt(receiptId) {
    if (!confirm(getLanguageString('text_confirm_delete'))) {
        return;
    }
    
    $.ajax({
        url: 'index.php?route=purchase/goods_receipt/ajaxDelete&receipt_id=' + receiptId + '&user_token=' + getUserToken(),
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showSuccess(response.success);
                loadReceipts();
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
    $('#modal-add-receipt .modal-body').load('index.php?route=purchase/goods_receipt/getAddForm&user_token=' + getUserToken(), function() {
        initializeAddForm();
        $('#modal-add-receipt').modal('show');
    });
}

function initializeAddForm() {
    // Initialize form elements
    if (typeof $.fn.select2 !== 'undefined') {
        $('#add-form select').select2({
            theme: 'bootstrap-5'
        });
    }
}

function initializeQualityForm() {
    // Initialize quality check form elements
    if (typeof $.fn.select2 !== 'undefined') {
        $('#quality-form select').select2({
            theme: 'bootstrap-5'
        });
    }
}

function getSelectedReceipts() {
    var selected = [];
    $('.receipt-checkbox:checked').each(function() {
        selected.push($(this).val());
    });
    return selected;
}

function updateStats(stats) {
    if (stats) {
        $('#total-receipts').text(stats.total || 0);
        $('#pending-receipts').text(stats.pending || 0);
        $('#received-receipts').text(stats.received || 0);
        $('#partially-received').text(stats.partially_received || 0);
    }
}

function clearFilters() {
    $('input[name="filter_receipt_number"]').val('');
    $('input[name="filter_po_number"]').val('');
    $('select[name="filter_status"]').val(null).trigger('change');
    $('input[name="filter_date_start"]').val('');
    $('input[name="filter_date_end"]').val('');
    loadReceipts();
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
