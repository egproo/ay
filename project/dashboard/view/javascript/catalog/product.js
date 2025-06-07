// Add this code to the existing product.js file or create a new one if it doesn't exist

// ===== Inventory Management Functions =====

// فتح نافذة إدارة المخزون
function openInventoryModal(branchId, unitId) {
    // تعيين البيانات في النافذة
    $('#modal-branch-id').val(branchId);
    $('#modal-unit-id').val(unitId);
    
    // استعلام عن معلومات المخزون الحالية
    getInventoryInfo(branchId, unitId);
    
    // إظهار النافذة
    $('#inventoryModal').modal('show');
}

// الحصول على معلومات المخزون الحالية
function getInventoryInfo(branchId, unitId) {
    var productId = $('#modal-product-id').val();
    
    $.ajax({
        url: 'index.php?route=catalog/product/getProductInventory&user_token=' + getURLVar('user_token'),
        type: 'POST',
        dataType: 'json',
        data: {
            product_id: productId,
            branch_id: branchId,
            unit_id: unitId
        },
        beforeSend: function() {
            $('#save-movement').prop('disabled', true);
            $('#inventoryModal .modal-content').addClass('loading');
        },
        complete: function() {
            $('#save-movement').prop('disabled', false);
            $('#inventoryModal .modal-content').removeClass('loading');
        },
        success: function(json) {
            if (json.success && json.inventory) {
                updateInventoryModalSummary(json.inventory, json.unit_name, json.branch_name);
            } else {
                alert(json.error || 'Error loading inventory data');
            }
        },
        error: function(xhr, ajaxOptions, thrownError) {
            alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
        }
    });
}

// تحديث ملخص المخزون في النافذة
function updateInventoryModalSummary(inventory, unitName, branchName) {
    // عرض معلومات الفرع والوحدة
    $('#summary-branch').text(branchName);
    $('#summary-unit').text(unitName);
    
    // عرض الكمية والتكلفة الحالية
    $('#summary-current-quantity').text(formatNumber(inventory.quantity));
    $('#summary-current-cost').text(formatCurrency(inventory.average_cost));
    
    // عرض معلومات الكمية المتاحة
    $('#available-quantity-info').html('Current available: <strong>' + formatNumber(inventory.quantity_available) + ' ' + unitName + '</strong>');
    
    // تحديث معاينة الأثر المالي عند تغيير الكمية أو التكلفة
    updateFinancialPreview();
}

// تحديث معاينة الأثر المالي
function updateFinancialPreview() {
    var currentQty = parseFloat($('#summary-current-quantity').text().replace(/,/g, '')) || 0;
    var currentCost = parseFloat($('#summary-current-cost').text().replace(/[^0-9.-]+/g, '')) || 0;
    var movementType = $('#modal-movement-type').val();
    var quantity = parseFloat($('#modal-quantity').val()) || 0;
    var newCost = parseFloat($('#modal-cost').val()) || currentCost;
    var newQty, qtyChange, valueChange;
    
    // حساب الكمية الجديدة حسب نوع الحركة
    if (movementType === 'increase') {
        newQty = currentQty + quantity;
        qtyChange = quantity;
    } else if (movementType === 'decrease') {
        newQty = currentQty - quantity;
        qtyChange = -quantity;
        
        // التحقق من توفر المخزون
        if (newQty < 0) {
            $('#insufficient-stock-warning').show().text(
                $('#text_insufficient_stock_warning').val().replace('%s', formatNumber(currentQty))
            );
        } else {
            $('#insufficient-stock-warning').hide();
        }
    } else if (movementType === 'count') {
        newQty = quantity;
        qtyChange = quantity - currentQty;
    }
    
    // حساب التكلفة الجديدة (فقط للحركات الداخلة)
    var newAvgCost = currentCost;
    if ((movementType === 'increase' || (movementType === 'count' && qtyChange > 0)) && quantity > 0) {
        // حساب المتوسط المرجح للتكلفة
        var totalValueBefore = currentQty * currentCost;
        var incomingValue = quantity * newCost;
        
        if (movementType === 'increase') {
            newAvgCost = (totalValueBefore + incomingValue) / newQty;
        } else if (movementType === 'count') {
            if (qtyChange > 0) {
                newAvgCost = (totalValueBefore + (qtyChange * newCost)) / newQty;
            }
        }
    }
    
    // حساب تغير قيمة المخزون
    valueChange = (newQty * newAvgCost) - (currentQty * currentCost);
    
    // عرض النتائج
    $('#summary-new-quantity').text(formatNumber(newQty));
    $('#summary-new-cost').text(formatCurrency(newAvgCost));
    $('#summary-quantity-change').text(formatNumber(qtyChange));
    $('#summary-value-change').text(formatCurrency(valueChange));
    
    // تحديث معاينة القيد المحاسبي
    updateJournalEntryPreview(movementType, Math.abs(valueChange), newAvgCost);
}

// تحديث معاينة القيد المحاسبي
function updateJournalEntryPreview(movementType, amount, cost) {
    var entriesHtml = '';
    var inventoryAccount = $('#text_inventory_account').val() || 'Inventory';
    var adjustmentAccount = '';
    
    // تحديد الحساب المقابل حسب نوع الحركة
    if (movementType === 'increase') {
        adjustmentAccount = $('#text_inventory_adjustment_account').val() || 'Inventory Adjustment';
        
        entriesHtml += '<tr>' +
            '<td>' + inventoryAccount + '</td>' +
            '<td class="text-right">' + formatCurrency(amount) + '</td>' +
            '<td class="text-right">-</td>' +
            '</tr>' +
            '<tr>' +
            '<td>' + adjustmentAccount + '</td>' +
            '<td class="text-right">-</td>' +
            '<td class="text-right">' + formatCurrency(amount) + '</td>' +
            '</tr>';
    } else if (movementType === 'decrease') {
        adjustmentAccount = $('#text_inventory_loss_account').val() || 'Inventory Loss';
        
        entriesHtml += '<tr>' +
            '<td>' + adjustmentAccount + '</td>' +
            '<td class="text-right">' + formatCurrency(amount) + '</td>' +
            '<td class="text-right">-</td>' +
            '</tr>' +
            '<tr>' +
            '<td>' + inventoryAccount + '</td>' +
            '<td class="text-right">-</td>' +
            '<td class="text-right">' + formatCurrency(amount) + '</td>' +
            '</tr>';
    } else if (movementType === 'count') {
        var qtyChange = parseFloat($('#summary-quantity-change').text().replace(/,/g, '')) || 0;
        
        if (qtyChange > 0) {
            adjustmentAccount = $('#text_inventory_adjustment_account').val() || 'Inventory Adjustment';
            
            entriesHtml += '<tr>' +
                '<td>' + inventoryAccount + '</td>' +
                '<td class="text-right">' + formatCurrency(amount) + '</td>' +
                '<td class="text-right">-</td>' +
                '</tr>' +
                '<tr>' +
                '<td>' + adjustmentAccount + '</td>' +
                '<td class="text-right">-</td>' +
                '<td class="text-right">' + formatCurrency(amount) + '</td>' +
                '</tr>';
        } else if (qtyChange < 0) {
            adjustmentAccount = $('#text_inventory_loss_account').val() || 'Inventory Loss';
            
            entriesHtml += '<tr>' +
                '<td>' + adjustmentAccount + '</td>' +
                '<td class="text-right">' + formatCurrency(amount) + '</td>' +
                '<td class="text-right">-</td>' +
                '</tr>' +
                '<tr>' +
                '<td>' + inventoryAccount + '</td>' +
                '<td class="text-right">-</td>' +
                '<td class="text-right">' + formatCurrency(amount) + '</td>' +
                '</tr>';
        } else {
            entriesHtml = '<tr><td colspan="3" class="text-center text-muted">No journal entry needed (no change)</td></tr>';
        }
    }
    
    $('#journal-entries').html(entriesHtml);
}

// حفظ حركة المخزون
function saveInventoryMovement() {
    // التحقق من الإدخالات المطلوبة
    if (!validateMovementForm()) {
        return false;
    }
    
    var productId = $('#modal-product-id').val();
    var branchId = $('#modal-branch-id').val();
    var unitId = $('#modal-unit-id').val();
    var movementType = $('#modal-movement-type').val();
    var quantity = parseFloat($('#modal-quantity').val());
    var cost = parseFloat($('#modal-cost').val()) || 0;
    var reason = $('#modal-reason').val();
    if (reason === 'other') {
        reason = $('#modal-custom-reason').val();
    }
    var notes = $('#modal-notes').val();
    var reference = $('#modal-reference').val();
    
    // تحديد نوع الحركة لإرسالها إلى الخادم
    var serverMovementType;
    if (movementType === 'increase') {
        serverMovementType = 'adjustment_increase';
    } else if (movementType === 'decrease') {
        serverMovementType = 'adjustment_decrease';
    } else if (movementType === 'count') {
        serverMovementType = 'stock_count';
    }
    
    $.ajax({
        url: 'index.php?route=catalog/product/saveInventoryMovement&user_token=' + getURLVar('user_token'),
        type: 'POST',
        dataType: 'json',
        data: {
            product_id: productId,
            branch_id: branchId,
            unit_id: unitId,
            movement_type: serverMovementType,
            quantity: quantity,
            cost: cost,
            reason: reason,
            notes: notes,
            reference: reference
        },
        beforeSend: function() {
            $('#save-movement').button('loading');
        },
        complete: function() {
            $('#save-movement').button('reset');
        },
        success: function(json) {
            if (json.success) {
                $('#inventoryModal').modal('hide');
                
                // عرض رسالة نجاح
                showSuccessNotification(json.success);
                
                // تحديث معلومات المخزون في الصفحة
                refreshInventoryData();
            } else {
                showErrorNotification(json.error);
            }
        },
        error: function(xhr, ajaxOptions, thrownError) {
            alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
        }
    });
}

// التحقق من صحة نموذج حركة المخزون
function validateMovementForm() {
    var valid = true;
    var errorMessage = '';
    
    // التحقق من الكمية
    var quantity = parseFloat($('#modal-quantity').val());
    if (isNaN(quantity) || quantity <= 0) {
        errorMessage += 'Please enter a valid quantity greater than zero.<br>';
        valid = false;
    }
    
    // التحقق من سبب التعديل
    var reason = $('#modal-reason').val();
    if (!reason) {
        errorMessage += 'Please select an adjustment reason.<br>';
        valid = false;
    } else if (reason === 'other' && !$('#modal-custom-reason').val().trim()) {
        errorMessage += 'Please enter a custom reason.<br>';
        valid = false;
    }
    
    // التحقق من التكلفة للحركات الداخلة
    var movementType = $('#modal-movement-type').val();
    var cost = parseFloat($('#modal-cost').val());
    if ((movementType === 'increase' || movementType === 'count') && ($('#modal-cost-group').is(':visible')) && (isNaN(cost) || cost < 0)) {
        errorMessage += 'Please enter a valid cost.<br>';
        valid = false;
    }
    
    // التحقق من توفر المخزون للحركات الخارجة
    if (movementType === 'decrease') {
        var currentQty = parseFloat($('#summary-current-quantity').text().replace(/,/g, ''));
        if (quantity > currentQty) {
            errorMessage += 'Insufficient stock. Available: ' + formatNumber(currentQty) + '<br>';
            valid = false;
        }
    }
    
    // التحقق من تأكيد التعديل
    if (!$('#modal-confirm').is(':checked')) {
        errorMessage += 'Please confirm the adjustment.<br>';
        valid = false;
    }
    
    if (!valid) {
        showErrorNotification(errorMessage);
    }
    
    return valid;
}

// تحديث بيانات المخزون في الصفحة
function refreshInventoryData() {
    var productId = $('#input-product-id').val();
    
    $.ajax({
        url: 'index.php?route=catalog/product/getProductInventory&user_token=' + getURLVar('user_token'),
        type: 'POST',
        dataType: 'json',
        data: {
            product_id: productId,
            refresh_all: 1
        },
        success: function(json) {
            if (json.success && json.inventory_data) {
                // تحديث جدول المخزون
                updateInventoryTable(json.inventory_data);
                
                // تحديث جدول حركات المخزون الأخيرة
                if (json.recent_movements) {
                    updateRecentMovements(json.recent_movements);
                }
            }
        }
    });
}

// تحديث جدول المخزون
function updateInventoryTable(inventoryData) {
    var html = '';
    var totalQty = 0;
    var totalAvailable = 0;
    var totalValue = 0;
    
    if (inventoryData.length > 0) {
        for (var i = 0; i < inventoryData.length; i++) {
            var item = inventoryData[i];
            var qtyClass = (item.quantity <= parseFloat($('#product-minimum').val())) ? 'text-danger' : '';
            
            html += '<tr id="inventory-row-' + item.product_inventory_id + '">' +
                '<td>' + item.branch_name + '</td>' +
                '<td>' + item.unit_name + '</td>' +
                '<td class="text-right"><span class="' + qtyClass + '">' + formatNumber(item.quantity) + '</span></td>' +
                '<td class="text-right">' + formatNumber(item.quantity_available) + '</td>' +
                '<td class="text-right">' + formatCurrency(item.average_cost) + '</td>' +
                '<td class="text-right">' + formatCurrency(item.total_value) + '</td>' +
                '<td class="text-center">' +
                (item.is_consignment ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-warning">No</span>') +
                '</td>' +
                '<td class="text-center">' +
                '<button type="button" data-toggle="tooltip" title="Add Movement" class="btn btn-primary btn-sm" onclick="openInventoryModal(' + item.branch_id + ', ' + item.unit_id + ')">' +
                '<i class="fas fa-plus-circle"></i></button>' +
                '</td>' +
                '</tr>';
            
            totalQty += parseFloat(item.quantity);
            totalAvailable += parseFloat(item.quantity_available);
            totalValue += parseFloat(item.total_value);
        }
    } else {
        html = '<tr><td colspan="8" class="text-center">No inventory data available for this product</td></tr>';
    }
    
    $('#inventory-table tbody').html(html);
    
    // تحديث الإجماليات
    $('#total-quantity').text(formatNumber(totalQty));
    $('#total-available').text(formatNumber(totalAvailable));
    $('#total-value').text(formatCurrency(totalValue));
    
    if (totalQty > 0) {
        var avgCost = totalValue / totalQty;
        $('#avg-cost').text(formatCurrency(avgCost));
    } else {
        $('#avg-cost').text(formatCurrency(0));
    }
}

// تحديث جدول حركات المخزون الأخيرة
function updateRecentMovements(movements) {
    var html = '';
    
    if (movements.length > 0) {
        for (var i = 0; i < movements.length; i++) {
            var item = movements[i];
            var badgeClass = (item.type === 'in' || item.type.indexOf('increase') > -1 || item.type.indexOf('purchase') > -1 || item.type.indexOf('return_in') > -1 || item.type.indexOf('transfer_in') > -1) ? 'bg-success' : 'bg-warning';
            
            html += '<tr>' +
                '<td>' + item.date_added + '</td>' +
                '<td><span class="badge ' + badgeClass + '">' + item.type_text + '</span></td>' +
                '<td class="text-right">' + formatNumber(item.quantity) + '</td>' +
                '<td>' + item.unit_name + '</td>' +
                '<td>' + item.branch_name + '</td>' +
                '<td>' + item.reference + '</td>' +
                '</tr>';
        }
    } else {
        html = '<tr><td colspan="6" class="text-center">No recent movements found</td></tr>';
    }
    
    $('#recent-movements tbody').html(html);
}

// ===== حاسبة المتوسط المرجح للتكلفة =====
$(document).ready(function() {
    // حساب المتوسط المرجح للتكلفة
    $('#calculate-wac').on('click', function() {
        var currentQuantity = parseFloat($('#current-quantity').val()) || 0;
        var currentCost = parseFloat($('#current-cost').val()) || 0;
        var newQuantity = parseFloat($('#new-quantity').val()) || 0;
        var newCost = parseFloat($('#new-cost').val()) || 0;
        
        if (currentQuantity < 0 || newQuantity < 0) {
            showErrorNotification('Quantities must be positive values');
            return;
        }
        
        if (currentCost < 0 || newCost < 0) {
            showErrorNotification('Cost values must be positive');
            return;
        }
        
        var totalValueBefore = currentQuantity * currentCost;
        var incomingValue = newQuantity * newCost;
        var newTotalQuantity = currentQuantity + newQuantity;
        
        if (newTotalQuantity > 0) {
            var weightedAverage = (totalValueBefore + incomingValue) / newTotalQuantity;
            $('#weighted-result').val(formatCurrency(weightedAverage));
        } else {
            $('#weighted-result').val('N/A - Total quantity is zero');
        }
    });
    
    // محول الوحدات
    $('#convert-units').on('click', function() {
        var fromUnitId = $('#from-unit').val();
        var toUnitId = $('#to-unit').val();
        var quantity = parseFloat($('#convert-quantity').val()) || 0;
        
        if (!fromUnitId || !toUnitId) {
            $('#conversion-result').html('Please select both units');
            return;
        }
        
        if (quantity <= 0) {
            $('#conversion-result').html('Please enter a positive quantity');
            return;
        }
        
        var fromFactor = parseFloat($('#from-unit option:selected').attr('data-factor')) || 1;
        var toFactor = parseFloat($('#to-unit option:selected').attr('data-factor')) || 1;
        var fromUnitName = $('#from-unit option:selected').text();
        var toUnitName = $('#to-unit option:selected').text();
        
        // التحويل إلى الوحدة الأساسية ثم إلى الوحدة المطلوبة
        var baseQuantity = quantity * fromFactor;
        var convertedQuantity = baseQuantity / toFactor;
        
        $('#conversion-result').html(
            formatNumber(quantity) + ' ' + fromUnitName + ' = ' +
            formatNumber(convertedQuantity) + ' ' + toUnitName
        );
    });
    
    // التعديل السريع للمخزون
    $('#apply-adjustment').on('click', function() {
        // التحقق من صحة الإدخالات
        var branchId = $('#adjustment-branch').val();
        var unitId = $('#adjustment-unit').val();
        var adjustmentType = $('#adjustment-type').val();
        var quantity = parseFloat($('#adjustment-quantity').val());
        var cost = parseFloat($('#adjustment-cost').val()) || 0;
        var reason = $('#adjustment-reason').val();
        var customReason = $('#custom-reason').val();
        var notes = $('#adjustment-notes').val();
        var confirmed = $('#adjustment-confirm').is(':checked');
        
        if (!branchId) {
            showErrorNotification('Please select a branch');
            return;
        }
        
        if (!unitId) {
            showErrorNotification('Please select a unit');
            return;
        }
        
        if (isNaN(quantity) || quantity <= 0) {
            showErrorNotification('Please enter a valid quantity greater than zero');
            return;
        }
        
        if ((adjustmentType === 'increase' || adjustmentType === 'count') && (isNaN(cost) || cost < 0)) {
            showErrorNotification('Please enter a valid cost');
            return;
        }
        
        if (!reason) {
            showErrorNotification('Please select an adjustment reason');
            return;
        }
        
        if (reason === 'other' && !customReason.trim()) {
            showErrorNotification('Please enter a custom reason');
            return;
        }
        
        if (!confirmed) {
            showErrorNotification('Please confirm the adjustment');
            return;
        }
        
        // إرسال طلب تعديل المخزون
        var productId = $('#input-product-id').val();
        var actualReason = reason === 'other' ? customReason : reason;
        
        $.ajax({
            url: 'index.php?route=catalog/product/addStockAdjustment&user_token=' + getURLVar('user_token'),
            type: 'POST',
            dataType: 'json',
            data: {
                product_id: productId,
                branch_id: branchId,
                unit_id: unitId,
                adjustment_type: adjustmentType,
                quantity: quantity,
                cost: cost,
                reason: actualReason,
                notes: notes
            },
            beforeSend: function() {
                $('#apply-adjustment').button('loading');
            },
            complete: function() {
                $('#apply-adjustment').button('reset');
            },
            success: function(json) {
                if (json.success) {
                    showSuccessNotification(json.success);
                    
                    // إعادة تعيين النموذج
                    $('#quick-adjustment')[0].reset();
                    $('#custom-reason-group').hide();
                    $('#adjustment-confirm').prop('checked', false);
                    
                    // تحديث بيانات المخزون
                    refreshInventoryData();
                } else {
                    showErrorNotification(json.error);
                }
            },
            error: function(xhr, ajaxOptions, thrownError) {
                alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
    });
    
    // تحديث واجهة النافذة عند تغيير نوع الحركة
    $('#modal-movement-type').on('change', function() {
        var type = $(this).val();
        
        if (type === 'increase') {
            $('#quantity-label').text('Quantity to Add');
            $('#modal-cost-group').show();
        } else if (type === 'decrease') {
            $('#quantity-label').text('Quantity to Remove');
            $('#modal-cost-group').hide();
        } else if (type === 'count') {
            $('#quantity-label').text('Actual Quantity');
            $('#modal-cost-group').show();
        }
        
        updateFinancialPreview();
    });
    
    // تحديث معاينة الأثر المالي عند تغيير الكمية أو التكلفة
    $('#modal-quantity, #modal-cost').on('input', function() {
        updateFinancialPreview();
    });
    
    // إظهار حقل السبب المخصص عند اختيار "أخرى"
    $('#modal-reason').on('change', function() {
        if ($(this).val() === 'other') {
            $('#modal-custom-reason-group').show();
        } else {
            $('#modal-custom-reason-group').hide();
        }
    });
    
    // إظهار حقل السبب المخصص للتعديل السريع
    $('#adjustment-reason').on('change', function() {
        if ($(this).val() === 'other') {
            $('#custom-reason-group').show();
        } else {
            $('#custom-reason-group').hide();
        }
    });
    
    // إخفاء/إظهار حقل التكلفة حسب نوع التعديل
    $('#adjustment-type').on('change', function() {
        if ($(this).val() === 'decrease') {
            $('#cost-group').hide();
        } else {
            $('#cost-group').show();
        }
    });
    
    // حفظ حركة المخزون عند النقر على الزر
    $('#save-movement').on('click', function() {
        saveInventoryMovement();
    });
});

// ===== Helper Functions =====

// تنسيق الأرقام مع الفواصل
function formatNumber(number) {
    return parseFloat(number).toFixed(2).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

// تنسيق القيم المالية
function formatCurrency(amount) {
    return '$' + formatNumber(amount);
}

// عرض إشعار نجاح
function showSuccessNotification(message) {
    var alertHtml = '<div class="alert alert-success alert-dismissible fade show" role="alert">' +
        '<i class="fas fa-check-circle mr-2"></i>' + message +
        '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
        '<span aria-hidden="true">&times;</span>' +
        '</button>' +
        '</div>';
    
    $('#content > .container-fluid').prepend(alertHtml);
    
    // اختفاء تلقائي بعد 5 ثوانٍ
    setTimeout(function() {
        $('.alert-success').fadeTo(500, 0).slideUp(500, function() {
            $(this).remove();
        });
    }, 5000);
}

// عرض إشعار خطأ
function showErrorNotification(message) {
    var alertHtml = '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
        '<i class="fas fa-exclamation-circle mr-2"></i>' + message +
        '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
        '<span aria-hidden="true">&times;</span>' +
        '</button>' +
        '</div>';
    
    $('#content > .container-fluid').prepend(alertHtml);
    
    // اختفاء تلقائي بعد 8 ثوانٍ
    setTimeout(function() {
        $('.alert-danger').fadeTo(500, 0).slideUp(500, function() {
            $(this).remove();
        });
    }, 8000);
} 