$(document).ready(function() {
    'use strict';
    
    // Initialize page
    initSelect2();
    loadInvoices();
    
    // Event handlers
    $('#btn-filter').on('click', function() {
        loadInvoices();
    });
    
    $('#btn-clear-filter').on('click', function() {
        clearFilters();
    });
    
    $('#btn-add-invoice').on('click', function() {
        openAddModal();
    });
    
    // Bulk actions
    $('#btn-bulk-action').on('click', function() {
        var action = $('#bulk-action').val();
        var selected = getSelectedInvoices();
        
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
        loadInvoices();
    });
    
    // Auto-filter on input change
    $('input[name="filter_invoice_number"], input[name="filter_po_number"], input[name="filter_date_start"], input[name="filter_date_end"], select[name="filter_status"], select[name="filter_supplier"]').on('input change', function() {
        loadInvoices();
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

function loadInvoices(page) {
    page = page || 1;
    
    var filters = {
        filter_invoice_number: $('input[name="filter_invoice_number"]').val(),
        filter_po_number: $('input[name="filter_po_number"]').val(),
        filter_supplier_id: $('select[name="filter_supplier"]').val(),
        filter_status: $('select[name="filter_status"]').val(),
        filter_date_start: $('input[name="filter_date_start"]').val(),
        filter_date_end: $('input[name="filter_date_end"]').val(),
        page: page
    };
    
    showLoading();
    
    $.ajax({
        url: 'index.php?route=purchase/supplier_invoice/ajaxList&user_token=' + getUserToken(),
        type: 'GET',
        data: filters,
        dataType: 'json',
        success: function(response) {
            if (response.invoices) {
                updateInvoicesList(response.invoices);
                updateStats(response.stats);
                updatePagination(response.pagination);
            } else {
                showError(response.error || 'Failed to load invoices');
            }
            hideLoading();
        },
        error: function() {
            showError('Network error occurred');
            hideLoading();
        }
    });
}

function updateInvoicesList(invoices) {
    var tbody = $('#table-invoices tbody');
    tbody.empty();
    
    if (invoices.length === 0) {
        tbody.append('<tr><td colspan="8" class="text-center">' + getLanguageString('text_no_results') + '</td></tr>');
        return;
    }
    
    $.each(invoices, function(index, invoice) {
        var row = $('<tr>');
        
        // Checkbox
        row.append('<td><input type="checkbox" class="invoice-checkbox" value="' + invoice.invoice_id + '"></td>');
        
        // Invoice Number
        row.append('<td><a href="javascript:void(0);" onclick="viewInvoice(' + invoice.invoice_id + ')">' + invoice.invoice_number + '</a></td>');
        
        // PO Number
        var poLink = invoice.po_number ? 
            '<a href="index.php?route=purchase/order/view&po_id=' + invoice.po_id + '&user_token=' + getUserToken() + '">' + invoice.po_number + '</a>' : 
            '-';
        row.append('<td>' + poLink + '</td>');
        
        // Supplier
        row.append('<td>' + invoice.supplier_name + '</td>');
        
        // Total
        row.append('<td class="text-right">' + invoice.total_formatted + '</td>');
        
        // Status
        var statusBadge = getStatusBadge(invoice.status, invoice.status_text);
        row.append('<td class="text-center">' + statusBadge + '</td>');
        
        // Invoice Date
        row.append('<td>' + invoice.invoice_date + '</td>');
        
        // Due Date
        row.append('<td>' + (invoice.due_date || '-') + '</td>');
        
        // Actions
        var actions = buildActionButtons(invoice);
        row.append('<td>' + actions + '</td>');
        
        tbody.append(row);
    });
    
    // Bind action events
    bindActionEvents();
}

function getStatusBadge(status, statusText) {
    var badges = {
        'pending_approval': '<span class="badge bg-warning">',
        'approved': '<span class="badge bg-success">',
        'rejected': '<span class="badge bg-danger">',
        'partially_paid': '<span class="badge bg-info">',
        'paid': '<span class="badge bg-success">',
        'cancelled': '<span class="badge bg-secondary">'
    };
    
    var badgeClass = badges[status] || '<span class="badge bg-light">';
    return badgeClass + statusText + '</span>';
}

function buildActionButtons(invoice) {
    var buttons = '';
    
    // View button
    buttons += '<button type="button" class="btn btn-sm btn-outline-info me-1 btn-view" data-id="' + invoice.invoice_id + '" title="View"><i class="fas fa-eye"></i></button>';
    
    // Edit button
    if (invoice.can_edit) {
        buttons += '<button type="button" class="btn btn-sm btn-outline-primary me-1 btn-edit" data-id="' + invoice.invoice_id + '" title="Edit"><i class="fas fa-edit"></i></button>';
    }
    
    // Approve button
    if (invoice.can_approve) {
        buttons += '<button type="button" class="btn btn-sm btn-outline-success me-1 btn-approve" data-id="' + invoice.invoice_id + '" title="Approve"><i class="fas fa-check"></i></button>';
    }
    
    // Reject button
    if (invoice.can_reject) {
        buttons += '<button type="button" class="btn btn-sm btn-outline-warning me-1 btn-reject" data-id="' + invoice.invoice_id + '" title="Reject"><i class="fas fa-times"></i></button>';
    }
    
    // Pay button
    if (invoice.can_pay) {
        buttons += '<button type="button" class="btn btn-sm btn-outline-primary me-1 btn-pay" data-id="' + invoice.invoice_id + '" title="Pay"><i class="fas fa-credit-card"></i></button>';
    }
    
    // Print button
    if (invoice.can_view) {
        buttons += '<a href="index.php?route=purchase/supplier_invoice/print&invoice_id=' + invoice.invoice_id + '&user_token=' + getUserToken() + '" target="_blank" class="btn btn-sm btn-outline-secondary me-1" title="Print"><i class="fas fa-print"></i></a>';
    }
    
    // Delete button
    if (invoice.can_delete) {
        buttons += '<button type="button" class="btn btn-sm btn-outline-danger btn-delete" data-id="' + invoice.invoice_id + '" title="Delete"><i class="fas fa-trash"></i></button>';
    }
    
    return buttons;
}

function bindActionEvents() {
    // View invoice
    $('.btn-view').off('click').on('click', function() {
        var invoiceId = $(this).data('id');
        viewInvoice(invoiceId);
    });
    
    // Edit invoice
    $('.btn-edit').off('click').on('click', function() {
        var invoiceId = $(this).data('id');
        editInvoice(invoiceId);
    });
    
    // Approve invoice
    $('.btn-approve').off('click').on('click', function() {
        var invoiceId = $(this).data('id');
        approveInvoice(invoiceId);
    });
    
    // Reject invoice
    $('.btn-reject').off('click').on('click', function() {
        var invoiceId = $(this).data('id');
        rejectInvoice(invoiceId);
    });
    
    // Pay invoice
    $('.btn-pay').off('click').on('click', function() {
        var invoiceId = $(this).data('id');
        payInvoice(invoiceId);
    });
    
    // Delete invoice
    $('.btn-delete').off('click').on('click', function() {
        var invoiceId = $(this).data('id');
        deleteInvoice(invoiceId);
    });
}

function viewInvoice(invoiceId) {
    $('#modal-invoice-view .modal-content').load('index.php?route=purchase/supplier_invoice/view&user_token=' + getUserToken() + '&invoice_id=' + invoiceId, function() {
        $('#modal-invoice-view').modal('show');
    });
}

function editInvoice(invoiceId) {
    $('#modal-invoice-form .modal-content').load('index.php?route=purchase/supplier_invoice/form&user_token=' + getUserToken() + '&invoice_id=' + invoiceId, function() {
        initializeInvoiceForm();
        $('#modal-invoice-form').modal('show');
    });
}

function approveInvoice(invoiceId) {
    if (!confirm(getLanguageString('text_confirm_approve'))) {
        return;
    }
    
    $.ajax({
        url: 'index.php?route=purchase/supplier_invoice/approve&user_token=' + getUserToken(),
        type: 'POST',
        data: {
            invoice_id: invoiceId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showSuccess(response.success);
                loadInvoices();
            } else {
                showError(response.error);
            }
        },
        error: function() {
            showError('Network error occurred');
        }
    });
}

function rejectInvoice(invoiceId) {
    var reason = prompt(getLanguageString('text_enter_rejection_reason'));
    if (reason === null) {
        return; // User cancelled
    }
    
    $.ajax({
        url: 'index.php?route=purchase/supplier_invoice/reject&user_token=' + getUserToken(),
        type: 'POST',
        data: {
            invoice_id: invoiceId,
            reason: reason
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showSuccess(response.success);
                loadInvoices();
            } else {
                showError(response.error);
            }
        },
        error: function() {
            showError('Network error occurred');
        }
    });
}

function payInvoice(invoiceId) {
    // Redirect to payment voucher creation with invoice pre-selected
    window.location.href = 'index.php?route=finance/payment_voucher/form&invoice_id=' + invoiceId + '&user_token=' + getUserToken();
}

function deleteInvoice(invoiceId) {
    if (!confirm(getLanguageString('text_confirm_delete'))) {
        return;
    }
    
    $.ajax({
        url: 'index.php?route=purchase/supplier_invoice/delete&user_token=' + getUserToken(),
        type: 'POST',
        data: {
            invoice_id: invoiceId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showSuccess(response.success);
                loadInvoices();
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
    $('#modal-invoice-form .modal-content').load('index.php?route=purchase/supplier_invoice/form&user_token=' + getUserToken(), function() {
        initializeInvoiceForm();
        $('#modal-invoice-form').modal('show');
    });
}

function initializeInvoiceForm() {
    // Initialize form elements
    if (typeof $.fn.select2 !== 'undefined') {
        $('#invoice-form select').select2({
            theme: 'bootstrap-5'
        });
    }
    
    // Initialize date pickers
    if (typeof $.fn.datepicker !== 'undefined') {
        $('#invoice-form .date').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true
        });
    }
}

function getSelectedInvoices() {
    var selected = [];
    $('.invoice-checkbox:checked').each(function() {
        selected.push($(this).val());
    });
    return selected;
}

function updateStats(stats) {
    if (stats) {
        $('#total-invoices').text(stats.total || 0);
        $('#pending-invoices').text(stats.pending || 0);
        $('#approved-invoices').text(stats.approved || 0);
        $('#paid-invoices').text(stats.paid || 0);
    }
}

function clearFilters() {
    $('input[name="filter_invoice_number"]').val('');
    $('input[name="filter_po_number"]').val('');
    $('select[name="filter_supplier"]').val(null).trigger('change');
    $('select[name="filter_status"]').val(null).trigger('change');
    $('input[name="filter_date_start"]').val('');
    $('input[name="filter_date_end"]').val('');
    loadInvoices();
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
