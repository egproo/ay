$(document).ready(function() {
    'use strict';
    
    // Initialize page
    initSelect2();
    loadQuotations();
    
    // Event handlers
    $('#btn-filter').on('click', function() {
        loadQuotations();
    });
    
    $('#btn-clear-filter').on('click', function() {
        clearFilters();
    });
    
    $('#btn-add-quotation').on('click', function() {
        window.location.href = 'index.php?route=purchase/quotation/form&user_token=' + getUserToken();
    });
    
    // Bulk actions
    $('#btn-bulk-action').on('click', function() {
        var action = $('#bulk-action').val();
        var selected = getSelectedQuotations();
        
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
        loadQuotations();
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

function loadQuotations() {
    var filters = {
        filter_quotation_number: $('#filter-quotation-number').val(),
        filter_supplier_id: $('#filter-supplier').val(),
        filter_status: $('#filter-status').val(),
        filter_branch_id: $('#filter-branch').val(),
        filter_date_from: $('#filter-date-from').val(),
        filter_date_to: $('#filter-date-to').val(),
        page: currentPage || 1
    };
    
    showLoading();
    
    $.ajax({
        url: 'index.php?route=purchase/quotation/ajaxList&user_token=' + getUserToken(),
        type: 'GET',
        data: filters,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                updateQuotationsList(response.data);
                updatePagination(response.pagination);
            } else {
                showError(response.error || 'Failed to load quotations');
            }
            hideLoading();
        },
        error: function() {
            showError('Network error occurred');
            hideLoading();
        }
    });
}

function updateQuotationsList(data) {
    var tbody = $('#quotations-table tbody');
    tbody.empty();
    
    if (data.length === 0) {
        tbody.append('<tr><td colspan="10" class="text-center">' + getLanguageString('text_no_results') + '</td></tr>');
        return;
    }
    
    $.each(data, function(index, quotation) {
        var row = $('<tr>');
        
        // Checkbox
        row.append('<td><input type="checkbox" class="quotation-checkbox" value="' + quotation.quotation_id + '"></td>');
        
        // Quotation Number
        row.append('<td><a href="index.php?route=purchase/quotation/form&quotation_id=' + quotation.quotation_id + '&user_token=' + getUserToken() + '">' + quotation.quotation_number + '</a></td>');
        
        // Supplier
        row.append('<td>' + quotation.supplier_name + '</td>');
        
        // Total Amount
        row.append('<td class="text-end">' + quotation.total_formatted + '</td>');
        
        // Status
        var statusBadge = getStatusBadge(quotation.status);
        row.append('<td>' + statusBadge + '</td>');
        
        // Valid Until
        row.append('<td>' + quotation.valid_until + '</td>');
        
        // Created Date
        row.append('<td>' + quotation.date_added + '</td>');
        
        // Created By
        row.append('<td>' + quotation.created_by_name + '</td>');
        
        // Actions
        var actions = buildActionButtons(quotation);
        row.append('<td>' + actions + '</td>');
        
        tbody.append(row);
    });
    
    // Bind action events
    bindActionEvents();
}

function getStatusBadge(status) {
    var badges = {
        'draft': '<span class="badge bg-secondary">Draft</span>',
        'sent': '<span class="badge bg-info">Sent</span>',
        'received': '<span class="badge bg-warning">Received</span>',
        'approved': '<span class="badge bg-success">Approved</span>',
        'rejected': '<span class="badge bg-danger">Rejected</span>',
        'expired': '<span class="badge bg-dark">Expired</span>'
    };
    
    return badges[status] || '<span class="badge bg-light">' + status + '</span>';
}

function buildActionButtons(quotation) {
    var buttons = '';
    
    // View/Edit button
    buttons += '<a href="index.php?route=purchase/quotation/form&quotation_id=' + quotation.quotation_id + '&user_token=' + getUserToken() + '" class="btn btn-sm btn-outline-primary me-1" title="Edit"><i class="fas fa-edit"></i></a>';
    
    // Approve button
    if (quotation.status === 'received' && window.can_approve) {
        buttons += '<button type="button" class="btn btn-sm btn-outline-success me-1 btn-approve" data-id="' + quotation.quotation_id + '" title="Approve"><i class="fas fa-check"></i></button>';
    }
    
    // Reject button
    if ((quotation.status === 'received' || quotation.status === 'sent') && window.can_reject) {
        buttons += '<button type="button" class="btn btn-sm btn-outline-warning me-1 btn-reject" data-id="' + quotation.quotation_id + '" title="Reject"><i class="fas fa-times"></i></button>';
    }
    
    // Delete button
    if (window.can_delete) {
        buttons += '<button type="button" class="btn btn-sm btn-outline-danger btn-delete" data-id="' + quotation.quotation_id + '" title="Delete"><i class="fas fa-trash"></i></button>';
    }
    
    return buttons;
}

function bindActionEvents() {
    // Approve quotation
    $('.btn-approve').off('click').on('click', function() {
        var quotationId = $(this).data('id');
        approveQuotation(quotationId);
    });
    
    // Reject quotation
    $('.btn-reject').off('click').on('click', function() {
        var quotationId = $(this).data('id');
        rejectQuotation(quotationId);
    });
    
    // Delete quotation
    $('.btn-delete').off('click').on('click', function() {
        var quotationId = $(this).data('id');
        deleteQuotation(quotationId);
    });
}

function approveQuotation(quotationId) {
    if (!confirm(getLanguageString('text_confirm_approve'))) {
        return;
    }
    
    $.ajax({
        url: 'index.php?route=purchase/quotation/ajaxApprove&quotation_id=' + quotationId + '&user_token=' + getUserToken(),
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showSuccess(response.success);
                loadQuotations();
            } else {
                showError(response.error);
            }
        },
        error: function() {
            showError('Network error occurred');
        }
    });
}

function rejectQuotation(quotationId) {
    var reason = prompt(getLanguageString('text_prompt_reject_reason'));
    if (reason === null) {
        return;
    }
    
    $.ajax({
        url: 'index.php?route=purchase/quotation/ajaxReject&user_token=' + getUserToken(),
        type: 'POST',
        data: {
            quotation_id: quotationId,
            reason: reason
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showSuccess(response.success);
                loadQuotations();
            } else {
                showError(response.error);
            }
        },
        error: function() {
            showError('Network error occurred');
        }
    });
}

function deleteQuotation(quotationId) {
    if (!confirm(getLanguageString('text_confirm_delete'))) {
        return;
    }
    
    $.ajax({
        url: 'index.php?route=purchase/quotation/ajaxDelete&quotation_id=' + quotationId + '&user_token=' + getUserToken(),
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showSuccess(response.success);
                loadQuotations();
            } else {
                showError(response.error);
            }
        },
        error: function() {
            showError('Network error occurred');
        }
    });
}

function getSelectedQuotations() {
    var selected = [];
    $('.quotation-checkbox:checked').each(function() {
        selected.push($(this).val());
    });
    return selected;
}

function clearFilters() {
    $('#filter-quotation-number').val('');
    $('#filter-supplier').val(null).trigger('change');
    $('#filter-status').val(null).trigger('change');
    $('#filter-branch').val(null).trigger('change');
    $('#filter-date-from').val('');
    $('#filter-date-to').val('');
    loadQuotations();
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
    $('#pagination-info').text(pagination.text);
}

// Global variables
var currentPage = 1;
