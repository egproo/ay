$(document).ready(function() {
    // Initialize Select2
    initSelect2();

    // Load initial data
    loadRequisitions();

    // Add item button click handler
    $('#button-add-item').on('click', function() {
        addItemRow();
    });

    // Remove item button click handler
    $('#req-items').on('click', '.button-remove-item', function() {
        $(this).closest('tr').remove();
    });

    // Form submission handlers
    $('#form-add-requisition').on('submit', function(e) {
        e.preventDefault();
        submitAddForm();
    });
});

function initSelect2() {
    if (typeof $.fn.select2 !== 'undefined') {
        $('.select2-branch').select2({
            theme: 'bootstrap-5'
        });

        $('.select2-user-group').select2({
            theme: 'bootstrap-5'
        });

        $('.select2-priority').select2({
            theme: 'bootstrap-5',
            minimumResultsForSearch: -1
        });
    }
}

function initProductSelect2($elem) {
    if (typeof $.fn.select2 !== 'undefined') {
        $elem.select2({
            theme: 'bootstrap-5',
            ajax: {
                url: 'index.php?route=purchase/requisition/select2Product&user_token=' + getUserToken(),
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term
                    };
                },
                processResults: function(data) {
                    return {
                        results: data
                    };
                },
                cache: true
            },
            placeholder: getLanguageString('text_select_product')
        }).on('select2:select', function(e) {
        let $row = $(this).closest('tr');
        let $unitSelect = $row.find('.unit-select');
        let branchId = $('select[name="branch_id"]').val();

        // Clear previous options
        $unitSelect.empty().append('<option value="">' + getLanguageString('text_select') + '</option>');

        // Add new units if available
        if (e.params.data.units) {
            e.params.data.units.forEach(function(unit) {
                $unitSelect.append(new Option(unit.text, unit.id));
            });
        }

        // Load product details if branch selected
        if (branchId) {
            loadProductDetails(e.params.data.id, branchId, $row);
        }
        });
    }
}

function loadProductDetails(productId, branchId, $row) {
    $.ajax({
        url: 'index.php?route=purchase/requisition/ajaxGetProductDetails&user_token=' + getUserToken(),
        type: 'get',
        data: {
            product_id: productId,
            branch_id: branchId
        },
        dataType: 'json',
        success: function(json) {
            let $detailsDiv = $row.find('.product-details');
            let $pendingDiv = $row.find('.pending-requisitions');

            $detailsDiv.empty();
            $pendingDiv.empty();

            if (json.error) {
                showError(json.error);
                return;
            }

            // Show current stock and other details
            if (json.stock_info) {
                $detailsDiv.html(
                    '<div class="alert alert-info mb-2">' +
                    '<strong>' + getLanguageString('text_current_stock') + ':</strong> ' + json.stock_info.quantity + '<br>' +
                    '<strong>' + getLanguageString('text_average_cost') + ':</strong> ' + json.stock_info.average_cost +
                    '</div>'
                );
            }

            // Show pending requisitions if any
            if (json.pending_requisitions && json.pending_requisitions.length > 0) {
                let pendingHtml = '<div class="alert alert-warning mb-2">' +
                    '<strong>' + getLanguageString('text_pending_requisitions') + ':</strong><br>';

                json.pending_requisitions.forEach(function(req) {
                    pendingHtml += '- ' + getLanguageString('text_req_number') + ': ' + req.req_number +
                        ' (' + req.quantity + ' ' + req.unit_name + ')<br>';
                });

                pendingHtml += '</div>';
                $pendingDiv.html(pendingHtml);
            }
        },
        error: function(xhr, ajaxOptions, thrownError) {
            showError(thrownError || xhr.statusText);
        }
    });
}

function addItemRow() {
    let template = document.getElementById('template-add-item').content.cloneNode(true);
    $('#req-items tbody').append(template);

    // Initialize Select2 for the new product select
    let $latest = $('#req-items tbody tr:last').find('.product-select');
    initProductSelect2($latest);
}

function submitAddForm() {
    $.ajax({
        url: 'index.php?route=purchase/requisition/ajaxAddRequisition&user_token=' + getUserToken(),
        type: 'post',
        data: $('#form-add-requisition').serialize(),
        dataType: 'json',
        beforeSend: function() {
            $('#modal-add-requisition .btn-primary').prop('disabled', true).html(
                '<i class="fas fa-spinner fa-spin"></i> ' + getLanguageString('text_saving')
            );
        },
        complete: function() {
            $('#modal-add-requisition .btn-primary').prop('disabled', false).html(getLanguageString('button_save'));
        },
        success: function(json) {
            $('.alert-dismissible').remove();

            if (json.error) {
                showError(json.error);
            }

            if (json.success) {
                $('#modal-add-requisition').modal('hide');
                showSuccess(json.success);
                loadRequisitions();
            }
        },
        error: function(xhr, ajaxOptions, thrownError) {
            showError(thrownError || xhr.statusText);
        }
    });
}

function loadRequisitions(page) {
    page = page || 1;

    $.ajax({
        url: 'index.php?route=purchase/requisition/ajaxList&user_token=' + getUserToken(),
        type: 'get',
        data: {
            filter_req_number: $('#filter-req-number').val(),
            filter_status: $('#filter-status').val(),
            filter_date_start: $('#filter-date-start').val(),
            filter_date_end: $('#filter-date-end').val(),
            page: page
        },
        dataType: 'json',
        beforeSend: function() {
            $('#loading-overlay').show();
        },
        complete: function() {
            $('#loading-overlay').hide();
        },
        success: function(json) {
            // Update stats
            $('#total-requisitions').text(json.stats.total || 0);
            $('#pending-requisitions').text(json.stats.pending || 0);
            $('#approved-requisitions').text(json.stats.approved || 0);
            $('#rejected-requisitions').text(json.stats.rejected || 0);

            // Clear existing rows
            $('#requisition-list').empty();

            if (json.requisitions && json.requisitions.length) {
                json.requisitions.forEach(function(row) {
                    let html = '<tr>';
                    html += '<td><input type="checkbox" name="selected[]" value="' + row.requisition_id + '"></td>';
                    html += '<td>' + row.requisition_id + '</td>';
                    html += '<td>' + (row.req_number || '') + '</td>';
                    html += '<td>' + (row.branch_name || '') + '</td>';
                    html += '<td>' + (row.user_group_name || '') + '</td>';
                    html += '<td>' + getStatusBadge(row.status) + '</td>';
                    html += '<td>' + row.date_added + '</td>';
                    html += '<td>' + generateActions(row) + '</td>';
                    html += '</tr>';

                    $('#requisition-list').append(html);
                });
            } else {
                $('#requisition-list').append(
                    '<tr><td colspan="8" class="text-center">' +
                    getLanguageString('text_no_results') + '</td></tr>'
                );
            }

            // Update pagination
            $('#pagination').html(json.pagination || '');
            $('#results').html(json.total || '');
        },
        error: function(xhr, ajaxOptions, thrownError) {
            showError(thrownError || xhr.statusText);
        }
    });
}

function getStatusBadge(status) {
    let badgeClass = 'bg-secondary';

    switch(status) {
        case 'pending':
            badgeClass = 'bg-warning';
            break;
        case 'approved':
            badgeClass = 'bg-success';
            break;
        case 'rejected':
            badgeClass = 'bg-danger';
            break;
        case 'cancelled':
            badgeClass = 'bg-secondary';
            break;
    }

    return '<span class="badge ' + badgeClass + '">' +
        getLanguageString('text_status_' + status) + '</span>';
}

function generateActions(row) {
    let actions = [];

    // View action always available
    actions.push('<button type="button" class="btn btn-info btn-sm" onclick="viewRequisition(' +
        row.requisition_id + ');"><i class="fas fa-eye"></i></button>');

    // Edit action
    if (row.can_edit && row.status !== 'approved') {
        actions.push('<button type="button" class="btn btn-primary btn-sm" onclick="editRequisition(' +
            row.requisition_id + ');"><i class="fas fa-edit"></i></button>');
    }

    // Approve action
    if (row.can_approve && row.status === 'pending') {
        actions.push('<button type="button" class="btn btn-success btn-sm" onclick="approveRequisition(' +
            row.requisition_id + ');"><i class="fas fa-check"></i></button>');
    }

    // Reject action
    if (row.can_reject && row.status === 'pending') {
        actions.push('<button type="button" class="btn btn-danger btn-sm" onclick="rejectRequisition(' +
            row.requisition_id + ');"><i class="fas fa-times"></i></button>');
    }

    // Delete action
    if (row.can_delete && ['draft', 'pending'].includes(row.status)) {
        actions.push('<button type="button" class="btn btn-danger btn-sm" onclick="deleteRequisition(' +
            row.requisition_id + ');"><i class="fas fa-trash"></i></button>');
    }

    // Quotations actions
    if (row.status === 'approved') {
        if (row.can_manage_quotations) {
            actions.push('<button type="button" class="btn btn-info btn-sm" onclick="viewQuotations(' +
                row.requisition_id + ');"><i class="fas fa-file-invoice-dollar"></i></button>');
        }
        if (row.can_add_quotation) {
            actions.push('<button type="button" class="btn btn-primary btn-sm" onclick="addQuotation(' +
                row.requisition_id + ');"><i class="fas fa-plus"></i></button>');
        }
    }

    return actions.join(' ');
}

function viewRequisition(requisitionId) {
    // Load edit form in readonly mode
    $.ajax({
        url: 'index.php?route=purchase/requisition/ajaxGetRequisitionForm&user_token=' + getUserToken(),
        type: 'get',
        data: {
            requisition_id: requisitionId,
            readonly: 1
        },
        beforeSend: function() {
            $('#loading-overlay').show();
        },
        complete: function() {
            $('#loading-overlay').hide();
        },
        success: function(html) {
            $('#modal-edit-requisition .modal-content').html(html);
            $('#modal-edit-requisition').modal('show');

            // Initialize any Select2 elements
            $('#modal-edit-requisition .form-control').prop('disabled', true);
        }
    });
}

function editRequisition(requisitionId) {
    $.ajax({
        url: 'index.php?route=purchase/requisition/ajaxGetRequisitionForm&user_token=' + getUserToken(),
        type: 'get',
        data: {
            requisition_id: requisitionId
        },
        beforeSend: function() {
            $('#loading-overlay').show();
        },
        complete: function() {
            $('#loading-overlay').hide();
        },
        success: function(html) {
            $('#modal-edit-requisition .modal-content').html(html);
            $('#modal-edit-requisition').modal('show');

            // Initialize Select2 elements
            initEditFormSelect2();
        }
    });
}

function approveRequisition(requisitionId) {
    if (confirm(getLanguageString('text_confirm_approve'))) {
        $.ajax({
            url: 'index.php?route=purchase/requisition/ajaxApprove&user_token=' + getUserToken(),
            type: 'post',
            data: {
                requisition_id: requisitionId
            },
            dataType: 'json',
            success: function(json) {
                if (json.error) {
                    showError(json.error);
                }

                if (json.success) {
                    showSuccess(json.success);
                    loadRequisitions();
                }
            }
        });
    }
}

function rejectRequisition(requisitionId) {
    let reason = prompt(getLanguageString('text_prompt_reject_reason'));

    if (reason !== null) {
        $.ajax({
            url: 'index.php?route=purchase/requisition/ajaxReject&user_token=' + getUserToken(),
            type: 'post',
            data: {
                requisition_id: requisitionId,
                reason: reason
            },
            dataType: 'json',
            success: function(json) {
                if (json.error) {
                    showError(json.error);
                }

                if (json.success) {
                    showSuccess(json.success);
                    loadRequisitions();
                }
            }
        });
    }
}

function deleteRequisition(requisitionId) {
    if (confirm(getLanguageString('text_confirm_delete'))) {
        $.ajax({
            url: 'index.php?route=purchase/requisition/ajaxDelete&user_token=' + getUserToken(),
            type: 'post',
            data: {
                requisition_id: requisitionId
            },
            dataType: 'json',
            success: function(json) {
                if (json.error) {
                    showError(json.error);
                }

                if (json.success) {
                    showSuccess(json.success);
                    loadRequisitions();
                }
            }
        });
    }
}

function viewQuotations(requisitionId) {
    $.ajax({
        url: 'index.php?route=purchase/requisition/ajaxGetQuotations&user_token=' + getUserToken(),
        type: 'get',
        data: {
            requisition_id: requisitionId
        },
        beforeSend: function() {
            $('#loading-overlay').show();
        },
        complete: function() {
            $('#loading-overlay').hide();
        },
        success: function(html) {
            $('#modal-quotations .modal-content').html(html);
            $('#modal-quotations').modal('show');
        }
    });
}

function addQuotation(requisitionId) {
    window.location = 'index.php?route=purchase/quotation/add&user_token=' +
        getUserToken() + '&requisition_id=' + requisitionId;
}

function executeBulkAction() {
    let action = $('#bulk-action').val();
    let selected = [];

    $('input[name*=\'selected\']:checked').each(function() {
        selected.push($(this).val());
    });

    if (!selected.length) {
        showError(getLanguageString('error_no_selection'));
        return;
    }

    switch(action) {
        case 'approve':
            bulkApprove(selected);
            break;
        case 'reject':
            bulkReject(selected);
            break;
        case 'delete':
            bulkDelete(selected);
            break;
        default:
            showError(getLanguageString('error_select_action'));
    }
}

function bulkApprove(selected) {
    if (confirm(getLanguageString('text_confirm_bulk_approve'))) {
        $.ajax({
            url: 'index.php?route=purchase/requisition/ajaxBulkApprove&user_token=' + getUserToken(),
            type: 'post',
            data: {
                selected: selected
            },
            dataType: 'json',
            success: function(json) {
                if (json.error) {
                    showError(json.error);
                }

                if (json.success) {
                    showSuccess(json.success);
                    loadRequisitions();
                }
            }
        });
    }
}

function bulkReject(selected) {
    let reason = prompt(getLanguageString('text_prompt_reject_reason'));

    if (reason !== null) {
        $.ajax({
            url: 'index.php?route=purchase/requisition/ajaxBulkReject&user_token=' + getUserToken(),
            type: 'post',
            data: {
                selected: selected,
                reason: reason
            },
            dataType: 'json',
            success: function(json) {
                if (json.error) {
                    showError(json.error);
                }

                if (json.success) {
                    showSuccess(json.success);
                    loadRequisitions();
                }
            }
        });
    }
}

function bulkDelete(selected) {
    if (confirm(getLanguageString('text_confirm_bulk_delete'))) {
        $.ajax({
            url: 'index.php?route=purchase/requisition/ajaxBulkDelete&user_token=' + getUserToken(),
            type: 'post',
            data: {
                selected: selected
            },
            dataType: 'json',
            success: function(json) {
                if (json.error) {
                    showError(json.error);
                }

                if (json.success) {
                    showSuccess(json.success);
                    loadRequisitions();
                }
            }
        });
    }
}

function resetFilters() {
    $('#filter-req-number').val('');
    $('#filter-status').val('').trigger('change');
    $('#filter-date-start').val('');
    $('#filter-date-end').val('');
    loadRequisitions();
}

function exportRequisitions() {
    let url = 'index.php?route=purchase/requisition/exportRequisitions&user_token=' + getLoginToken();
    url += '&filter_req_number=' + encodeURIComponent($('#filter-req-number').val());
    url += '&filter_status=' + encodeURIComponent($('#filter-status').val());
    url += '&filter_date_start=' + encodeURIComponent($('#filter-date-start').val());
    url += '&filter_date_end=' + encodeURIComponent($('#filter-date-end').val());

    window.location = url;
}

// Helper functions
function showError(message) {
    $('.alert-dismissible').remove();

    $('#content > .container-fluid').prepend(
        '<div class="alert alert-danger alert-dismissible"><i class="fas fa-exclamation-circle"></i> ' +
        message + ' <button type="button" class="close" data-dismiss="alert">&times;</button></div>'
    );
}

function showSuccess(message) {
    $('.alert-dismissible').remove();

    $('#content > .container-fluid').prepend(
        '<div class="alert alert-success alert-dismissible"><i class="fas fa-check-circle"></i> ' +
        message + ' <button type="button" class="close" data-dismiss="alert">&times;</button></div>'
    );
}

function getLanguageString(key) {
    return typeof window.language[key] !== 'undefined' ? window.language[key] : key;
}

function getUserToken() {
    return typeof window.user_token !== 'undefined' ? window.user_token : '';
}

function initEditFormSelect2() {
    if (typeof $.fn.select2 !== 'undefined') {
        $('#modal-edit-requisition .select2-branch').select2({
            theme: 'bootstrap-5'
        });

        $('#modal-edit-requisition .select2-user-group').select2({
            theme: 'bootstrap-5'
        });

        $('#modal-edit-requisition .select2-priority').select2({
            theme: 'bootstrap-5',
            minimumResultsForSearch: -1
        });

        // Initialize product selects
        $('#modal-edit-requisition .product-select').each(function() {
            initProductSelect2($(this));
        });
    }
}

function getLoginToken() {
    return getUserToken();
}