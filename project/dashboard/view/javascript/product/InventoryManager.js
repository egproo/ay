/**
 * InventoryManager.js - مدير مخزون المنتج
 */

const InventoryManager = (function() {
  // متغيرات خاصة
  let inventoryData = [];
  let productId = 0;

  // تهيئة مدير المخزون
  function init() {
    productId = $('input[name="product_id"]').val() || 0;
    setupEventListeners();

    // تحميل بيانات المخزون إذا كان المنتج موجوداً
    if (productId > 0) {
      loadInventory();
    }
  }

  // إعداد مستمعي الأحداث
  function setupEventListeners() {
    // مستمع حدث زر الإضافة
    $('#add-inventory-movement').off('click').on('click', function() {
      openNewAdjustmentModal();
    });

    // مستمع تغيير الكمية في نافذة التعديل
    $(document).off('input', '#adjustment-quantity').on('input', '#adjustment-quantity', updateAdjustmentPreview);

    // مستمع تغيير التكلفة المباشرة في نافذة التعديل
    $(document).off('input', '#adjustment-direct-cost').on('input', '#adjustment-direct-cost', updateAdjustmentPreview);

    // مستمع تغيير نوع الحركة في نافذة التعديل
    $(document).off('change', '#adjustment-movement-type').on('change', '#adjustment-movement-type', updateAdjustmentView);

    // مستمع تغيير الفرع في نافذة التعديل
    $(document).off('change', '#adjustment-branch').on('change', '#adjustment-branch', updateBranchInventory);

    // مستمع تغيير الوحدة في نافذة التعديل
    $(document).off('change', '#adjustment-unit').on('change', '#adjustment-unit', updateUnitInventory);

    // مستمع تغيير سبب التعديل
    $(document).off('change', '#adjustment-reason').on('change', '#adjustment-reason', toggleCustomReason);

    // مستمع تغيير حالة التأكيد
    $(document).off('change', '#adjustment-confirmation').on('change', '#adjustment-confirmation', function() {
      $('#save-adjustment').prop('disabled', !$(this).is(':checked'));
    });

    // مستمع حدث حفظ التعديل
    $(document).off('click', '#save-adjustment').on('click', '#save-adjustment', saveInventoryAdjustment);

    // مستمعات حدث تعديل التكلفة
    setupCostModalEvents();
  }

  // إعداد أحداث نافذة تعديل التكلفة
  function setupCostModalEvents() {
    // مستمع تغيير التكلفة الجديدة
    $(document).off('input', '#new-cost').on('input', '#new-cost', updateCostPreview);

    // مستمع تغيير نوع سبب التكلفة
    $(document).off('change', '#cost-reason-type').on('change', '#cost-reason-type', updateCostReasonField);

    // مستمع تغيير حالة تحديث الأسعار
    $(document).off('change', '#update-prices').on('change', '#update-prices', updateCostPreview);

    // مستمع تغيير هامش الربح
    $(document).off('input', '#profit-margin').on('input', '#profit-margin', updatePriceCalculation);

    // مستمع تغيير حالة التأكيد
    $(document).off('change', '#cost-confirmation').on('change', '#cost-confirmation', function() {
      $('#save-cost').prop('disabled', !$(this).is(':checked'));
    });

    // مستمع حدث حفظ تعديل التكلفة
    $(document).off('click', '#save-cost').on('click', '#save-cost', saveCostAdjustment);
  }

  // تحديث سبب تعديل التكلفة
  function updateCostReasonField() {
    const reasonType = $('#cost-reason-type').val();

    if (reasonType === 'other') {
      $('#cost-reason-container').show();
    } else {
      $('#cost-reason-container').hide();
    }
  }

  // تبديل عرض حقل السبب المخصص
  function toggleCustomReason() {
    const reason = $('#adjustment-reason').val();
    if (reason === 'other') {
      $('#custom-reason-container').show();
    } else {
      $('#custom-reason-container').hide();
    }
  }

  // فتح نافذة تعديل جديدة
  function openNewAdjustmentModal() {
    // إعداد قيم افتراضية
    $('#adjustment-branch-id').val('');
    $('#adjustment-unit-id').val('');
    $('#adjustment-type').val('');

    // تهيئة النافذة بقيم فارغة
    $('#adjustment-title').html('<i class="fa fa-plus-circle"></i> {{ text_add_inventory_movement }}');
    $('#adjustment-movement-type').val('increase').trigger('change');

    // تحميل الوحدات المتاحة
    loadAvailableUnits();

    // تحديد أول فرع في القائمة إذا كان متاحاً
    if ($('#adjustment-branch option').length > 0) {
      $('#adjustment-branch').val($('#adjustment-branch option:first').val());
    }

    // إفراغ حقول الإدخال
    $('#adjustment-quantity').val('');
    $('#adjustment-direct-cost').val('');
    $('#adjustment-reason').val('stock_count');
    $('#adjustment-custom-reason').val('');
    $('#custom-reason-container').hide();
    $('#adjustment-notes').val('');
    $('#adjustment-reference').val('');

    // إعادة تعيين حالة التأكيد
    $('#adjustment-confirmation').prop('checked', false);
    $('#save-adjustment').prop('disabled', true);

    // تحديث معلومات المخزون الحالية بعد تحديد الفرع والوحدة
    updateBranchInventory();

    // عرض النافذة
    $('#adjustment-modal').modal('show');
  }

  // تحميل الوحدات المتاحة
  function loadAvailableUnits() {
    const currentUnits = UnitManager.getCurrentUnits();
    let html = '<option value="">{{ text_select_unit }}</option>';

    if (currentUnits && currentUnits.length > 0) {
      currentUnits.forEach(function(unitId) {
        if (unitId) {
          html += '<option value="' + unitId + '">' + UnitManager.getUnitName(unitId) + '</option>';
        }
      });
    }

    $('#adjustment-unit').html(html);
  }

  // تحديث واجهة نافذة التعديل بناءً على نوع الحركة
  function updateAdjustmentView() {
    const movementType = $('#adjustment-movement-type').val();

    // تغيير عنوان النافذة بناءً على نوع الحركة
    if (movementType === 'increase') {
      $('#adjustment-title').html('<i class="fa fa-plus-circle"></i> {{ text_add_stock }}');
      $('#direct-cost-container').show();
      $('#adjustment-quantity').attr('placeholder', '{{ text_quantity_to_add }}');
    } else if (movementType === 'decrease') {
      $('#adjustment-title').html('<i class="fa fa-minus-circle"></i> {{ text_remove_stock }}');
      $('#direct-cost-container').hide();
      $('#adjustment-quantity').attr('placeholder', '{{ text_quantity_to_remove }}');
    } else if (movementType === 'count') {
      $('#adjustment-title').html('<i class="fa fa-balance-scale"></i> {{ text_stock_count }}');
      $('#direct-cost-container').hide();
      $('#adjustment-quantity').attr('placeholder', '{{ text_actual_quantity }}');
    }

    // تحديث المعاينة
    updateAdjustmentPreview();
  }

  // تحديث معلومات المخزون بناءً على الفرع المحدد
  function updateBranchInventory() {
    const branchId = $('#adjustment-branch').val();
    const unitId = $('#adjustment-unit').val();

    if (!branchId || !unitId) {
      // تفريغ المعلومات إذا لم يتم تحديد فرع أو وحدة
      $('#current-branch').text('-');
      $('#current-unit').text('-');
      $('#current-quantity').text('0');
      $('#current-cost').text('0.00');
      $('#adjustment-unit-name').text('وحدة');

      // تحديث المعاينة
      updateAdjustmentPreview();
      return;
    }

    // البحث عن معلومات المخزون للفرع والوحدة المحددين
    const inventory = findInventoryItem(branchId, unitId);

    // تحديث معلومات المخزون في واجهة المستخدم
    $('#current-branch').text(getBranchName(branchId));
    $('#current-unit').text(UnitManager.getUnitName(unitId));
    $('#adjustment-unit-name').text(UnitManager.getUnitName(unitId));

    const currentQty = inventory ? parseFloat(inventory.quantity || 0) : 0;
    const currentCost = inventory ? parseFloat(inventory.average_cost || 0) : 0;

    $('#current-quantity').text(currentQty.toFixed(4));
    $('#current-cost').text(currentCost.toFixed(4));

    // تعيين التكلفة الحالية كقيمة افتراضية للتكلفة المباشرة
    if (currentCost > 0 && $('#adjustment-direct-cost').val() === '') {
      $('#adjustment-direct-cost').val(currentCost.toFixed(4));
    }

    // تحديث المعاينة
    updateAdjustmentPreview();
  }

  // تحديث معلومات المخزون بناءً على الوحدة المحددة
  function updateUnitInventory() {
    updateBranchInventory();
  }

  // تحديث معاينة التعديل
  function updateAdjustmentPreview() {
    const movementType = $('#adjustment-movement-type').val();
    const quantity = parseFloat($('#adjustment-quantity').val()) || 0;
    const currentQty = parseFloat($('#current-quantity').text()) || 0;
    const currentCost = parseFloat($('#current-cost').text()) || 0;
    const directCost = parseFloat($('#adjustment-direct-cost').val()) || 0;

    // حساب الكمية الجديدة والتكلفة الجديدة
    let newQty, newCost, quantityChange, valueChange;

    if (movementType === 'increase') {
      // إضافة مخزون
      newQty = currentQty + quantity;

      // حساب المتوسط المرجح للتكلفة
      if (currentQty > 0 && currentCost > 0 && quantity > 0) {
        const currentValue = currentQty * currentCost;
        const newValue = quantity * (directCost > 0 ? directCost : currentCost);
        newCost = (currentValue + newValue) / newQty;
      } else {
        newCost = (directCost > 0) ? directCost : currentCost;
      }

      quantityChange = quantity;
      valueChange = quantity * (directCost > 0 ? directCost : currentCost);

    } else if (movementType === 'decrease') {
      // خصم مخزون
      newQty = Math.max(0, currentQty - quantity);
      newCost = currentCost; // لا تتغير التكلفة عند الخصم
      quantityChange = -quantity;
      valueChange = -(quantity * currentCost);

    } else if (movementType === 'count') {
      // جرد مخزون
      newQty = quantity;
      newCost = currentCost; // لا تتغير التكلفة عند الجرد
      quantityChange = quantity - currentQty;
      valueChange = quantityChange * currentCost;
    }

    // تحديث عرض الكميات والتكاليف
    $('#new-quantity').text(newQty.toFixed(4));
    $('#new-cost').text(newCost.toFixed(4));
    $('#quantity-change').text(quantityChange.toFixed(4));
    $('#value-change').text(Math.abs(valueChange).toFixed(2));

    // تغيير لون تغيير القيمة
    $('#value-change').removeClass('text-danger text-success')
                       .addClass(valueChange >= 0 ? 'text-success' : 'text-danger');

    // تحديث التأثير المحاسبي
    updateAccountingImpact(movementType, quantity, currentCost, directCost, valueChange);

    // عرض تحذير إذا كانت الكمية غير متوفرة للخصم
    if (movementType === 'decrease' && quantity > currentQty) {
      $('#adjustment-warnings').html('{{ text_insufficient_stock_warning }}'.replace('%s', currentQty.toFixed(4))).show();
    } else {
      $('#adjustment-warnings').hide();
    }
  }

  // تحديث التأثير المحاسبي
  function updateAccountingImpact(movementType, quantity, currentCost, directCost, valueChange) {
    const reason = $('#adjustment-reason').val();

    // تحديد الحسابات المتأثرة
    let journalEntries = [];

    if (movementType === 'increase') {
      // زيادة المخزون - حساب المخزون مدين
      journalEntries.push({
        account: '{{ text_inventory_account }}',
        debit: Math.abs(valueChange).toFixed(2),
        credit: '0.00'
      });

      // الحساب المقابل - دائن
      let contraAccount = '{{ text_inventory_adjustment_account }}';
      if (reason === 'initial') {
        contraAccount = '{{ text_equity_account }}';
      } else if (reason === 'production') {
        contraAccount = '{{ text_production_account }}';
      }

      journalEntries.push({
        account: contraAccount,
        debit: '0.00',
        credit: Math.abs(valueChange).toFixed(2)
      });

      // تحديث عرض التأثير المحاسبي
      $('#inventory-account-amount').text(Math.abs(valueChange).toFixed(2));
      $('#contra-account-row').html(contraAccount + ': <span id="contra-account-amount">' + Math.abs(valueChange).toFixed(2) + '</span>');

    } else if (movementType === 'decrease') {
      // نقص المخزون - حساب المخزون دائن
      journalEntries.push({
        account: '{{ text_inventory_account }}',
        debit: '0.00',
        credit: Math.abs(valueChange).toFixed(2)
      });

      // الحساب المقابل - مدين
      let contraAccount = '{{ text_inventory_adjustment_account }}';
      if (reason === 'damaged' || reason === 'expired') {
        contraAccount = '{{ text_inventory_loss_account }}';
      } else if (reason === 'sale') {
        contraAccount = '{{ text_cost_of_goods_sold }}';
      }

      journalEntries.push({
        account: contraAccount,
        debit: Math.abs(valueChange).toFixed(2),
        credit: '0.00'
      });

      // تحديث عرض التأثير المحاسبي
      $('#inventory-account-amount').text(Math.abs(valueChange).toFixed(2));
      $('#contra-account-row').html(contraAccount + ': <span id="contra-account-amount">' + Math.abs(valueChange).toFixed(2) + '</span>');

    } else if (movementType === 'count') {
      // جرد المخزون - يعتمد على تغيير الكمية
      if (valueChange > 0) {
        // زيادة في المخزون
        journalEntries.push({
          account: '{{ text_inventory_account }}',
          debit: Math.abs(valueChange).toFixed(2),
          credit: '0.00'
        });

        journalEntries.push({
          account: '{{ text_inventory_adjustment_account }}',
          debit: '0.00',
          credit: Math.abs(valueChange).toFixed(2)
        });

        // تحديث عرض التأثير المحاسبي
        $('#inventory-account-amount').text(Math.abs(valueChange).toFixed(2));
        $('#contra-account-row').html('{{ text_inventory_adjustment_account }}: <span id="contra-account-amount">' + Math.abs(valueChange).toFixed(2) + '</span>');

      } else if (valueChange < 0) {
        // نقص في المخزون
        journalEntries.push({
          account: '{{ text_inventory_account }}',
          debit: '0.00',
          credit: Math.abs(valueChange).toFixed(2)
        });

        journalEntries.push({
          account: '{{ text_inventory_adjustment_account }}',
          debit: Math.abs(valueChange).toFixed(2),
          credit: '0.00'
        });

        // تحديث عرض التأثير المحاسبي
        $('#inventory-account-amount').text(Math.abs(valueChange).toFixed(2));
        $('#contra-account-row').html('{{ text_inventory_adjustment_account }}: <span id="contra-account-amount">' + Math.abs(valueChange).toFixed(2) + '</span>');
      } else {
        // لا تغيير في المخزون
        journalEntries = [];

        // تحديث عرض التأثير المحاسبي
        $('#inventory-account-amount').text('0.00');
        $('#contra-account-row').html('{{ text_contra_account }}: <span id="contra-account-amount">0.00</span>');
      }
    }

    // تحديث جدول القيود المحاسبية
    updateJournalTable(journalEntries);
  }

  // تحديث جدول القيود المحاسبية
  function updateJournalTable(entries) {
    let html = '';

    if (entries.length > 0) {
      entries.forEach(function(entry) {
        html += '<tr>';
        html += '<td>' + entry.account + '</td>';
        html += '<td class="text-right">' + entry.debit + '</td>';
        html += '<td class="text-right">' + entry.credit + '</td>';
        html += '</tr>';
      });
    } else {
      html = '<tr><td colspan="3" class="text-center">{{ text_no_entries }}</td></tr>';
    }

    $('#journal-preview tbody').html(html);
  }

  // تحديث معاينة تغيير التكلفة
  function updateCostPreview() {
    const currentCost = parseFloat($('#current-cost-display').val()) || 0;
    const newCost = parseFloat($('#new-cost').val()) || 0;
    const quantity = parseFloat($('#cost-quantity').text()) || 0;

    const currentTotalValue = currentCost * quantity;
    const newTotalValue = newCost * quantity;
    const valueDifference = newTotalValue - currentTotalValue;

    $('#current-total-value').text(currentTotalValue.toFixed(2));
    $('#new-total-value').text(newTotalValue.toFixed(2));
    $('#cost-value-change').text(Math.abs(valueDifference).toFixed(2));

    // تغيير لون فرق القيمة
    $('#cost-value-change').removeClass('text-danger text-success')
                           .addClass(valueDifference >= 0 ? 'text-success' : 'text-danger');

    // تحديث حساب السعر إذا كان مُفعل
    if ($('#update-prices').is(':checked')) {
      updatePriceCalculation();
    }
  }

  // تحديث حساب السعر بناءً على التكلفة الجديدة
  function updatePriceCalculation() {
    if (!$('#update-prices').is(':checked')) {
      return;
    }

    const newCost = parseFloat($('#new-cost').val()) || 0;
    const margin = parseFloat($('#profit-margin').val()) || 0;

    if (newCost <= 0 || margin >= 100) {
      $('#calculated-new-price').text('0.00');
      return;
    }

    // حساب السعر الجديد
    const calculatedPrice = newCost / (1 - (margin / 100));

    $('#calculated-new-price').text(calculatedPrice.toFixed(2));
  }

  // فتح نافذة تعديل التكلفة
  function openCostDialog(branchId, unitId) {
    // إعداد نافذة تعديل التكلفة
    const inventory = findInventoryItem(branchId, unitId);

    if (!inventory) {
      showNotification('error', '{{ error_inventory_not_found }}');
      return;
    }

    // تعبئة معلومات النافذة
    $('#cost-branch-id').val(branchId);
    $('#cost-unit-id').val(unitId);
    $('#cost-branch-name').text(inventory.branch_name || getBranchName(branchId));
    $('#cost-unit-name').text(inventory.unit_name || UnitManager.getUnitName(unitId));
    $('#cost-quantity').text(parseFloat(inventory.quantity || 0).toFixed(4));

    const currentCost = parseFloat(inventory.average_cost || 0);
    const quantity = parseFloat(inventory.quantity || 0);
    const totalValue = currentCost * quantity;

    $('#current-cost-display').val(currentCost.toFixed(4));
    $('#new-cost').val(currentCost.toFixed(4));
    $('#current-total-value').text(totalValue.toFixed(2));
    $('#new-total-value').text(totalValue.toFixed(2));
    $('#cost-value-change').text('0.00');

    // إعادة تعيين حقول أخرى
    $('#cost-reason-type').val('market');
    $('#cost-reason-custom').val('');
    $('#cost-reason-container').hide();
    $('#cost-notes').val('');
    $('#cost-confirmation').prop('checked', false);
    $('#save-cost').prop('disabled', true);

    // عرض النافذة
    $('#cost-modal').modal('show');
  }

  // حفظ تعديل المخزون
  function saveInventoryAdjustment() {
    // التحقق من وجود بيانات إلزامية
    const branchId = $('#adjustment-branch').val() || $('#adjustment-branch-id').val();
    const unitId = $('#adjustment-unit').val() || $('#adjustment-unit-id').val();
    const movementType = $('#adjustment-movement-type').val();
    const quantity = parseFloat($('#adjustment-quantity').val()) || 0;

    if (!branchId) {
      showNotification('error', '{{ error_branch_required }}');
      return false;
    }

    if (!unitId) {
      showNotification('error', '{{ error_unit_required }}');
      return false;
    }

    if (quantity <= 0) {
      showNotification('error', '{{ error_quantity_must_be_positive }}');
      return false;
    }

    // التحقق من توفر الكمية للخصم
    if (movementType === 'decrease') {
      const currentQty = parseFloat($('#current-quantity').text()) || 0;
      if (quantity > currentQty) {
        showNotification('error', '{{ error_insufficient_stock }}'.replace('%s', currentQty.toFixed(4)).replace('%s', quantity.toFixed(4)));
        return false;
      }
    }

    // التحقق من التأكيد
    if (!$('#adjustment-confirmation').is(':checked')) {
      showNotification('error', '{{ error_confirmation_required }}');
      return false;
    }

    // جمع باقي البيانات
    const directCost = parseFloat($('#adjustment-direct-cost').val()) || 0;
    const currentCost = parseFloat($('#current-cost').text()) || 0;
    const reason = $('#adjustment-reason').val();
    const customReason = $('#adjustment-custom-reason').val();
    const notes = $('#adjustment-notes').val();
    const reference = $('#adjustment-reference').val();

    // تحديد السبب النهائي
    const finalReason = reason === 'other' ? customReason : reason;

    // التحقق من إدخال سبب مخصص إذا تم اختيار "أخرى"
    if (reason === 'other' && !customReason) {
      showNotification('error', '{{ error_custom_reason_required }}');
      return false;
    }

    // الحصول على معلومات المخزون الحالية
    const inventory = findInventoryItem(branchId, unitId) || {
      branch_id: branchId,
      unit_id: unitId,
      quantity: 0,
      quantity_available: 0,
      average_cost: 0,
      is_consignment: 0
    };

    // حساب القيم الجديدة
    let newQuantity, newCost;

    if (movementType === 'increase') {
      // إضافة مخزون
      newQuantity = parseFloat(inventory.quantity || 0) + quantity;

      // حساب متوسط التكلفة المرجح الجديد
      if (parseFloat(inventory.quantity || 0) > 0 && parseFloat(inventory.average_cost || 0) > 0) {
        const totalOldValue = parseFloat(inventory.quantity || 0) * parseFloat(inventory.average_cost || 0);
        const totalNewValue = quantity * (directCost > 0 ? directCost : currentCost);
        newCost = (totalOldValue + totalNewValue) / newQuantity;
      } else {
        newCost = directCost > 0 ? directCost : currentCost;
      }
    } else if (movementType === 'decrease') {
      // خصم مخزون
      newQuantity = Math.max(0, parseFloat(inventory.quantity || 0) - quantity);
      newCost = parseFloat(inventory.average_cost || 0); // لا تتغير التكلفة عند الخصم
    } else if (movementType === 'count') {
      // جرد مخزون
      newQuantity = quantity;
      newCost = parseFloat(inventory.average_cost || 0); // لا تتغير التكلفة عند الجرد
    }

    // إعداد بيانات الحركة
    const movementData = {
      branch_id: branchId,
      unit_id: unitId,
      movement_type: movementType,
      quantity: quantity,
      cost: directCost > 0 ? directCost : currentCost,
      reason: finalReason,
      notes: notes,
      reference: reference,
      is_consignment: inventory.is_consignment || 0
    };

    // تعطيل زر الحفظ لمنع النقرات المتكررة
    $('#save-adjustment').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> {{ text_saving }}');

    // إرسال البيانات للخادم
    const productId = $('input[name="product_id"]').val();

    if (productId) {
      // إذا كان المنتج موجوداً، أرسل طلب AJAX
      $.ajax({
        url: 'index.php?route=catalog/product/saveInventoryMovement&user_token=' + user_token + '&product_id=' + productId,
        type: 'POST',
        data: movementData,
        dataType: 'json',
        success: function(json) {
          if (json.error) {
            showNotification('error', json.error);
            $('#save-adjustment').prop('disabled', false).html('{{ button_save }}');
          } else {
            showNotification('success', json.success || '{{ text_adjustment_saved }}');

            // إغلاق النافذة
            $('#adjustment-modal').modal('hide');

            // تحديث البيانات المعروضة إذا تم إرجاعها من الخادم
            if (json.updated_inventory) {
              // تحديث بيانات المخزون في الذاكرة المحلية
              updateInventoryData(
                branchId,
                unitId,
                json.updated_inventory.quantity,
                json.updated_inventory.average_cost,
                inventory.is_consignment || 0
              );

              // تحديث عرض المخزون
              renderInventoryTable();
            } else {
              // تحديث قائمة المخزون بالكامل
              loadInventory();
            }

            // تحديث سجل الحركات إذا كان مفتوحاً
            if ($('#tab-movement').is(':visible')) {
              loadMovements();
            }
          }
        },
        error: function(xhr, status, error) {
          showNotification('error', '{{ error_save_failed }}' + ': ' + error);
          $('#save-adjustment').prop('disabled', false).html('{{ button_save }}');
        },
        complete: function() {
          // في جميع الحالات، إعادة تفعيل الزر
          $('#save-adjustment').prop('disabled', false).html('{{ button_save }}');
        }
      });
    } else {
      // للمنتجات الجديدة، حفظ التعديل في الذاكرة
      updateInventoryData(branchId, unitId, newQuantity, newCost, inventory.is_consignment || 0);

      // تحديث العرض
      renderInventoryTable();

      // إغلاق النافذة
      $('#adjustment-modal').modal('hide');

      showNotification('success', '{{ text_adjustment_saved_memory }}');
      $('#save-adjustment').prop('disabled', false).html('{{ button_save }}');
    }

    return true;
  }

  // حفظ تعديل التكلفة
  function saveCostAdjustment() {
    const branchId = $('#cost-branch-id').val();
    const unitId = $('#cost-unit-id').val();
    const newCost = parseFloat($('#new-cost').val()) || 0;
    const reasonType = $('#cost-reason-type').val();
    const customReason = $('#cost-reason-custom').val();
    const notes = $('#cost-notes').val();
    const updatePrices = $('#update-prices').is(':checked');
    const marginPercentage = parseFloat($('#profit-margin').val()) || 0;

    // التحقق من صحة البيانات
    if (!branchId || !unitId) {
      showNotification('error', '{{ error_branch_unit_required }}');
      return false;
    }

    if (newCost <= 0) {
      showNotification('error', '{{ error_invalid_cost }}');
      return false;
    }

    // تحقق من سبب التعديل
    if (reasonType === 'other' && !customReason) {
      showNotification('error', '{{ error_custom_reason_required }}');
      return false;
    }

    // تحقق من التأكيد
    if (!$('#cost-confirmation').is(':checked')) {
      showNotification('error', '{{ error_confirmation_required }}');
      return false;
    }

    // إعداد بيانات التعديل
    const reason = reasonType === 'other' ? customReason : getReasonText(reasonType);

    const adjustmentData = {
      branch_id: branchId,
      unit_id: unitId,
      new_cost: newCost,
      reason: reason,
      notes: notes,
      update_prices: updatePrices ? 1 : 0,
      margin_percentage: marginPercentage
    };

    // تعطيل زر الحفظ
    $('#save-cost').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> {{ text_saving }}');

    // إرسال البيانات للخادم
    const productId = $('input[name="product_id"]').val();

    if (productId) {
      $.ajax({
        url: 'index.php?route=catalog/product/updateCost&user_token=' + user_token + '&product_id=' + productId,
        type: 'POST',
        data: adjustmentData,
        dataType: 'json',
        success: function(json) {
          if (json.error) {
            showNotification('error', json.error);
            $('#save-cost').prop('disabled', false).html('{{ button_save }}');
          } else {
            showNotification('success', json.success || '{{ text_cost_updated }}');

            // إغلاق النافذة
            $('#cost-modal').modal('hide');

            // تحديث بيانات المخزون
            loadInventory();

            // تحديث سجل الحركات إذا كان مفتوحاً
            if ($('#tab-movement').is(':visible')) {
              loadMovements();
            }
          }
        },
        error: function(xhr, status, error) {
          showNotification('error', '{{ error_save_failed }}' + ': ' + error);
          $('#save-cost').prop('disabled', false).html('{{ button_save }}');
        }
      });
    } else {
      // للمنتجات الجديدة
      const inventory = findInventoryItem(branchId, unitId);

      if (inventory) {
        inventory.average_cost = newCost;

        // تحديث العرض
        renderInventoryTable();

        // تحديث الأسعار إذا كان مُفعل
        if (updatePrices) {
          // تحديث سعر الوحدة
          updateProductPricing(unitId, newCost, marginPercentage);
        }
      }

      // إغلاق النافذة
      $('#cost-modal').modal('hide');

      showNotification('success', '{{ text_cost_updated_memory }}');
      $('#save-cost').prop('disabled', false).html('{{ button_save }}');
    }

    return true;
  }

  // تحميل بيانات المخزون
  function loadInventory() {
    if (!productId) return;

    $.ajax({
      url: 'index.php?route=catalog/product/getInventoryData&user_token=' + user_token + '&product_id=' + productId,
      type: 'GET',
      dataType: 'json',
      beforeSend: function() {
        $('#product-inventory tbody').html(`
          <tr><td colspan="8" class="text-center">
            <i class="fa fa-spinner fa-spin"></i> {{ text_loading }}
          </td></tr>
        `);
      },
      success: function(json) {
        if (json.inventory && Array.isArray(json.inventory) && json.inventory.length > 0) {
          inventoryData = json.inventory;
          console.log("تم تحميل بيانات المخزون:", inventoryData);

          // عرض البيانات في الجدول
          renderInventoryTable();

          // تحميل الحركات الأخيرة
          loadRecentMovements();
        } else {
          $('#product-inventory tbody').html(`
            <tr><td colspan="8" class="text-center">{{ text_no_inventory_data }}</td></tr>
          `);

          // إعادة تعيين الإجماليات
          $('#total-inventory-value').text('0.00');
          $('#total-average-cost').text('0.00');
        }
      },
      error: function(xhr, status, error) {
        console.error("خطأ في تحميل بيانات المخزون:", error);
        showNotification('error', '{{ error_loading_inventory }}' + ': ' + error);

        $('#product-inventory tbody').html(`
          <tr><td colspan="8" class="text-center text-danger">{{ error_loading_inventory }}</td></tr>
        `);
      }
    });
  }

  /**
   * إنشاء سجل مخزون لوحدة جديدة
   * @param {number} unitId - معرف الوحدة
   */
  function createInventoryForUnit(unitId) {
    if (!unitId) {
      return;
    }

    // التحقق مما إذا كان هناك سجل مخزون موجود بالفعل لهذه الوحدة
    const existingInventory = inventoryData.filter(function(item) {
      return item.unit_id == unitId;
    });

    // إذا كان هناك سجلات موجودة بالفعل لهذه الوحدة، لا تقم بإنشاء سجلات جديدة
    if (existingInventory.length > 0) {
      return;
    }

    // الحصول على قائمة الفروع
    const branches = getBranches();

    // إنشاء سجل مخزون لكل فرع
    branches.forEach(function(branch) {
      // إنشاء سجل مخزون جديد
      const newInventory = {
        product_id: productId,
        branch_id: branch.branch_id,
        branch_name: branch.name,
        unit_id: unitId,
        unit_name: UnitManager.getUnitName(unitId),
        quantity: 0,
        quantity_available: 0,
        average_cost: 0,
        total_value: 0,
        is_consignment: 0
      };

      // إضافة السجل الجديد إلى مصفوفة بيانات المخزون
      inventoryData.push(newInventory);
    });

    // تحديث جدول المخزون
    renderInventoryTable();

    // حفظ سجلات المخزون الجديدة على الخادم إذا كان المنتج موجوداً
    if (productId > 0) {
      saveInventoryRecords(unitId);
    }
  }

  /**
   * حفظ سجلات المخزون الجديدة على الخادم
   * @param {number} unitId - معرف الوحدة
   */
  function saveInventoryRecords(unitId) {
    if (!unitId || !productId) {
      return;
    }

    // الحصول على سجلات المخزون للوحدة المحددة
    const inventoryRecords = inventoryData.filter(function(item) {
      return item.unit_id == unitId;
    });

    // إذا لم تكن هناك سجلات، لا تقم بالحفظ
    if (inventoryRecords.length === 0) {
      return;
    }

    // حفظ سجلات المخزون على الخادم
    $.ajax({
      url: 'index.php?route=catalog/product/saveInventory&user_token=' + user_token,
      type: 'POST',
      data: {
        product_id: productId,
        unit_id: unitId,
        inventory: inventoryRecords
      },
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          showNotification('success', response.success);
        } else {
          showNotification('error', response.error || '{{ error_saving_inventory }}');
        }
      },
      error: function() {
        showNotification('error', '{{ error_ajax }}');
      }
    });
  }

  /**
   * الحصول على قائمة الفروع
   * @returns {Array} قائمة الفروع
   */
  function getBranches() {
    const branches = [];

    // جمع الفروع من القائمة المنسدلة
    $('#adjustment-branch option').each(function() {
      const branchId = $(this).val();
      const branchName = $(this).text();

      if (branchId && branchName) {
        branches.push({
          branch_id: branchId,
          name: branchName
        });
      }
    });

    // إذا لم تكن هناك فروع في القائمة المنسدلة، استخدم الفرع الافتراضي
    if (branches.length === 0) {
      branches.push({
        branch_id: 1,
        name: 'الفرع الرئيسي'
      });
    }

    return branches;
  }

  // تحميل حركات المخزون الأخيرة
  function loadRecentMovements() {
    if (!productId) return;

    $.ajax({
      url: 'index.php?route=catalog/product/getRecentMovements&user_token=' + user_token + '&product_id=' + productId + '&limit=5',
      type: 'GET',
      dataType: 'json',
      success: function(json) {
        let html = '';

        if (json.movements && json.movements.length > 0) {
          json.movements.forEach(function(movement) {
            // تحديد لون الصف حسب نوع الحركة
            let rowClass = '';
            if (movement.type.indexOf('{{ text_addition }}') !== -1 || movement.type.indexOf('{{ text_purchase }}') !== -1) {
              rowClass = 'success';
            } else if (movement.type.indexOf('{{ text_subtraction }}') !== -1 || movement.type.indexOf('{{ text_sale }}') !== -1) {
              rowClass = 'danger';
            }

            html += `
              <tr class="${rowClass}">
                <td>${movement.date_added}</td>
                <td>${movement.type}</td>
                <td class="text-right">${parseFloat(movement.quantity).toFixed(4)}</td>
                <td>${movement.unit_name}</td>
                <td>${movement.branch_name}</td>
                <td class="text-right">${parseFloat(movement.cost_impact || 0).toFixed(2)}</td>
                <td>${movement.user_name}</td>
                <td>${movement.reference || '-'}</td>
              </tr>
            `;
          });
        } else {
          html = `<tr><td colspan="8" class="text-center">{{ text_no_recent_movements }}</td></tr>`;
        }

        $('#recent-movements').html(html);
      },
      error: function(xhr, status, error) {
        console.error("خطأ في تحميل حركات المخزون الأخيرة:", error);
        $('#recent-movements').html(`<tr><td colspan="8" class="text-center text-danger">{{ error_loading_movements }}</td></tr>`);
      }
    });
  }

  // تحميل سجل الحركات
  function loadMovements(useFilters) {
    if (!productId) return;

    let filterData = {};

    if (useFilters) {
      // جمع بيانات الفلتر من واجهة المستخدم
      filterData = {
        type: $('#movement-type-filter').val(),
        branch_id: $('#movement-branch-filter').val(),
        date_from: $('#movement-date-from').val(),
        date_to: $('#movement-date-to').val()
      };
    }

    $.ajax({
      url: 'index.php?route=catalog/product/getInventoryMovements&user_token=' + user_token + '&product_id=' + productId,
      type: 'POST',
      data: filterData,
      dataType: 'json',
      beforeSend: function() {
        $('#stock-movements tbody').html(`<tr><td colspan="9" class="text-center"><i class="fa fa-spinner fa-spin"></i> {{ text_loading }}</td></tr>`);
      },
      success: function(json) {
        let html = '';

        if (json.movements && json.movements.length > 0) {
          json.movements.forEach(function(movement) {
            let rowClass = '';

            if (movement.type.indexOf('{{ text_addition }}') !== -1 || movement.type.indexOf('{{ text_purchase }}') !== -1 || movement.type.indexOf('{{ text_transfer_in }}') !== -1) {
              rowClass = 'success';
            } else if (movement.type.indexOf('{{ text_subtraction }}') !== -1 || movement.type.indexOf('{{ text_sale }}') !== -1) {
              rowClass = 'danger';
            }

            html += `<tr class="${rowClass}">`;
            html += `<td>${movement.date_added}</td>`;
            html += `<td>${movement.type}</td>`;
            html += `<td class="text-right">${movement.quantity}</td>`;
            html += `<td>${movement.unit_name}</td>`;
            html += `<td>${movement.branch_name}</td>`;
            html += `<td>${movement.reference || '-'}</td>`;
            html += `<td>${movement.user_name}</td>`;
            html += `<td class="text-right">${movement.cost || '-'}</td>`;
            html += `<td class="text-right">${movement.new_average_cost || '-'}</td>`;
            html += `</tr>`;
          });
        } else {
          html = `<tr><td colspan="9" class="text-center">{{ text_no_results }}</td></tr>`;
        }

        $('#stock-movements tbody').html(html);

        // تحديث الترقيم إذا كان متاحاً
        if (json.pagination) {
          $('#movement-pagination').html(json.pagination);
        }

        // تحديث الإحصائيات إذا كانت متاحة
        if (json.stats) {
          $('#total-incoming').text(json.stats.total_incoming.toFixed(4));
          $('#total-outgoing').text(json.stats.total_outgoing.toFixed(4));
        }
      },
      error: function(xhr, ajaxOptions, thrownError) {
        $('#stock-movements tbody').html(`<tr><td colspan="9" class="text-center text-danger">${thrownError}</td></tr>`);
      }
    });
  }

  // تحميل سجل الطلبات
  function loadOrders() {
    if (!productId) return;

    $.ajax({
      url: 'index.php?route=catalog/product/getProductOrders&user_token=' + user_token + '&product_id=' + productId,
      type: 'GET',
      dataType: 'json',
      beforeSend: function() {
        $('#product-orders tbody').html(`<tr><td colspan="8" class="text-center"><i class="fa fa-spinner fa-spin"></i> {{ text_loading }}</td></tr>`);
      },
      success: function(json) {
        let html = '';

        if (json.orders && json.orders.length > 0) {
          json.orders.forEach(function(order) {
            html += `<tr>`;
            html += `<td class="text-center">${order.order_id}</td>`;
            html += `<td>${order.customer}</td>`;
            html += `<td class="text-right">${parseFloat(order.quantity).toFixed(4)}</td>`;
            html += `<td>${order.unit_name}</td>`;
            html += `<td class="text-right">${parseFloat(order.price).toFixed(2)}</td>`;
            html += `<td><span class="label label-${order.status_color}">${order.status}</span></td>`;
            html += `<td>${order.date_added}</td>`;
            html += `<td class="text-center"><a href="index.php?route=sale/order/info&user_token=${user_token}&order_id=${order.order_id}" class="btn btn-info btn-xs" data-toggle="tooltip" title="{{ text_view }}"><i class="fa fa-eye"></i></a></td>`;
            html += `</tr>`;
          });
        } else {
          html = `<tr><td colspan="8" class="text-center">{{ text_no_orders }}</td></tr>`;
        }

        $('#product-orders tbody').html(html);

        // تحديث الترقيم إذا كان متاحاً
        if (json.pagination) {
          $('#orders-pagination').html(json.pagination);
        }

        // تحديث الإحصائيات إذا كانت متاحة
        if (json.stats) {
          $('#total-sold').text(json.stats.total_sold.toFixed(4));
          $('#total-revenue').text(json.stats.total_revenue.toFixed(2));
          $('#average-price').text(json.stats.average_price.toFixed(2));
        }
      },
      error: function(xhr, ajaxOptions, thrownError) {
        $('#product-orders tbody').html(`<tr><td colspan="8" class="text-center text-danger">${thrownError}</td></tr>`);
      }
    });
  }

  // تحديث بيانات المخزون في الذاكرة
  function updateInventoryData(branchId, unitId, quantity, cost, isConsignment) {
    if (!inventoryData) {
      inventoryData = [];
    }

    // البحث عن العنصر
    let found = false;

    for (let i = 0; i < inventoryData.length; i++) {
      if (inventoryData[i].branch_id == branchId && inventoryData[i].unit_id == unitId) {
        // تحديث العنصر الموجود
        inventoryData[i].quantity = quantity;
        inventoryData[i].quantity_available = quantity;
        inventoryData[i].average_cost = cost;
        inventoryData[i].is_consignment = isConsignment;
        found = true;
        break;
      }
    }

    if (!found) {
      // إضافة عنصر جديد
      const branchName = getBranchName(branchId);
      const unitName = UnitManager.getUnitName(unitId);

      inventoryData.push({
        branch_id: branchId,
        unit_id: unitId,
        branch_name: branchName,
        unit_name: unitName,
        quantity: quantity,
        quantity_available: quantity,
        average_cost: cost,
        is_consignment: isConsignment
      });
    }

    console.log("تم تحديث بيانات المخزون:", inventoryData);
  }

  // تحديث أسعار المنتج بناءً على التكلفة الجديدة
  function updateProductPricing(unitId, cost, marginPercentage) {
    if (cost <= 0 || marginPercentage >= 100) {
      return;
    }

    // حساب السعر الجديد
    const newPrice = cost / (1 - (marginPercentage / 100));

    // تحديث سعر الوحدة
    for (let i = 0; i < productPricing.length; i++) {
      if (productPricing[i].unit_id == unitId) {
        productPricing[i].base_price = newPrice;
        break;
      }
    }

    // تحديث عرض جدول التسعير
    UnitManager.updateRelatedTables();
  }

  // عرض جدول المخزون
  function renderInventoryTable() {
    let html = '';
    let totalValue = 0;
    let totalInventory = 0;

    if (!inventoryData || !Array.isArray(inventoryData) || inventoryData.length === 0) {
      if (branches && branches.length > 0 && UnitManager.getCurrentUnits().length > 0) {
        // إنشاء صفوف افتراضية لكل فرع ووحدة
        branches.forEach(function(branch) {
          UnitManager.getCurrentUnits().forEach(function(unitId) {
            if (unitId) {
              html += `
                <tr>
                  <td>${branch.name}</td>
                  <td>${UnitManager.getUnitName(unitId)}</td>
                  <td class="text-center">0.0000</td>
                  <td class="text-center">0.0000</td>
                  <td class="text-center">0.0000</td>
                  <td class="text-center">0.00</td>
                  <td class="text-center">-</td>
                  <td class="text-center">
                    <div class="btn-group">
                      <button type="button" class="btn btn-primary btn-sm" onclick="InventoryManager.openAdjustmentDialog('${branch.branch_id}', '${unitId}', 'add')">
                        <i class="fa fa-plus-circle"></i>
                      </button>
                      <button type="button" class="btn btn-danger btn-sm" onclick="InventoryManager.openAdjustmentDialog('${branch.branch_id}', '${unitId}', 'subtract')" disabled>
                        <i class="fa fa-minus-circle"></i>
                      </button>
                      <button type="button" class="btn btn-info btn-sm" onclick="InventoryManager.openAdjustmentDialog('${branch.branch_id}', '${unitId}', 'count')">
                        <i class="fa fa-balance-scale"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              `;
            }
          });
        });
      }

      if (html === '') {
        html = `<tr><td colspan="8" class="text-center">{{ text_no_inventory_data }}</td></tr>`;
      }

      $('#product-inventory tbody').html(html);

      // إعادة تعيين الإجماليات
      $('#total-inventory-value').text('0.00');
      $('#total-average-cost').text('0.00');
      return;
    }

    // بناء صفوف المخزون المتوفرة
    inventoryData.forEach(function(item) {
      const itemQuantity = parseFloat(item.quantity) || 0;
      const itemCost = parseFloat(item.average_cost) || 0;
      const itemValue = itemQuantity * itemCost;

      totalValue += itemValue;
      totalInventory += itemQuantity;

      // أزرار العمليات حسب توفر المخزون
      const decreaseButton = itemQuantity > 0
        ? `<button type="button" class="btn btn-danger btn-sm" onclick="InventoryManager.openAdjustmentDialog('${item.branch_id}', '${item.unit_id}', 'subtract')">
             <i class="fa fa-minus-circle"></i>
           </button>`
        : `<button type="button" class="btn btn-danger btn-sm" disabled>
             <i class="fa fa-minus-circle"></i>
           </button>`;

      // إضافة صف للجدول
      html += `
        <tr>
          <td>${item.branch_name || '{{ text_unknown }}'}</td>
          <td>${item.unit_name || '{{ text_unknown }}'}</td>
          <td class="text-center">${itemQuantity.toFixed(4)}</td>
          <td class="text-center">${parseFloat(item.quantity_available || 0).toFixed(4)}</td>
          <td class="text-center">${itemCost.toFixed(4)}</td>
          <td class="text-center">${itemValue.toFixed(2)}</td>
          <td class="text-center">${item.is_consignment == 1 ? '<span class="label label-info">{{ text_consignment }}</span>' : '-'}</td>
          <td class="text-center">
            <div class="btn-group">
              <button type="button" class="btn btn-primary btn-sm" onclick="InventoryManager.openAdjustmentDialog('${item.branch_id}', '${item.unit_id}', 'add')">
                <i class="fa fa-plus-circle"></i>
              </button>
              ${decreaseButton}
              <button type="button" class="btn btn-info btn-sm" onclick="InventoryManager.openCostDialog('${item.branch_id}', '${item.unit_id}')">
                <i class="fa fa-dollar"></i>
              </button>
            </div>
          </td>
        </tr>
      `;
    });

    // تكملة الجدول بالفروع والوحدات غير المسجلة
    if (branches && branches.length > 0 && UnitManager.getCurrentUnits().length > 0) {
      branches.forEach(function(branch) {
        UnitManager.getCurrentUnits().forEach(function(unitId) {
          if (unitId) {
            // التحقق مما إذا كان الفرع والوحدة موجودين بالفعل
            const exists = inventoryData.some(function(item) {
              return item.branch_id == branch.branch_id && item.unit_id == unitId;
            });

            if (!exists) {
              html += `
                <tr>
                  <td>${branch.name}</td>
                  <td>${UnitManager.getUnitName(unitId)}</td>
                  <td class="text-center">0.0000</td>
                  <td class="text-center">0.0000</td>
                  <td class="text-center">0.0000</td>
                  <td class="text-center">0.00</td>
                  <td class="text-center">-</td>
                  <td class="text-center">
                    <div class="btn-group">
                      <button type="button" class="btn btn-primary btn-sm" onclick="InventoryManager.openAdjustmentDialog('${branch.branch_id}', '${unitId}', 'add')">
                        <i class="fa fa-plus-circle"></i>
                      </button>
                      <button type="button" class="btn btn-danger btn-sm" onclick="InventoryManager.openAdjustmentDialog('${branch.branch_id}', '${unitId}', 'subtract')" disabled>
                        <i class="fa fa-minus-circle"></i>
                      </button>
                      <button type="button" class="btn btn-info btn-sm" onclick="InventoryManager.openAdjustmentDialog('${branch.branch_id}', '${unitId}', 'count')">
                        <i class="fa fa-balance-scale"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              `;
            }
          }
        });
      });
    }

    $('#product-inventory tbody').html(html);

    // تحديث الإجماليات
    $('#total-inventory-value').text(totalValue.toFixed(2));

    // حساب متوسط التكلفة الإجمالي المرجح
    const weightedAverageCost = totalInventory > 0 ? totalValue / totalInventory : 0;
    $('#total-average-cost').text(weightedAverageCost.toFixed(4));
  }

  /**
   * تحديث قائمة الوحدات في جدول المخزون
   * @param {Array} units - قائمة معرفات الوحدات
   */
  function updateUnitsList(units) {
    if (!units || !Array.isArray(units) || units.length === 0) {
      return;
    }

    // تحديث قائمة الوحدات في نافذة التعديل
    let html = '<option value="">{{ text_select_unit }}</option>';

    units.forEach(function(unitId) {
      if (unitId) {
        const unitName = UnitManager.getUnitName(unitId);
        html += '<option value="' + unitId + '">' + unitName + '</option>';
      }
    });

    $('#adjustment-unit').html(html);

    // التحقق من وجود سجلات مخزون لكل وحدة
    units.forEach(function(unitId) {
      if (unitId) {
        // البحث عن سجلات المخزون للوحدة
        const existingInventory = inventoryData.filter(function(item) {
          return item.unit_id == unitId;
        });

        // إذا لم تكن هناك سجلات، قم بإنشاء سجلات جديدة
        if (existingInventory.length === 0) {
          createInventoryForUnit(unitId);
        }
      }
    });
  }

  // فتح نافذة تعديل للمخزون المحدد
  function openAdjustmentDialog(branchId, unitId, type) {
    // تعيين القيم المخفية
    $('#adjustment-branch-id').val(branchId);
    $('#adjustment-unit-id').val(unitId);
    $('#adjustment-type').val(type);

    // تحديد نوع الحركة بناءً على النوع المحدد
    if (type === 'add') {
      $('#adjustment-movement-type').val('increase');
    } else if (type === 'subtract') {
      $('#adjustment-movement-type').val('decrease');
    } else {
      $('#adjustment-movement-type').val('count');
    }

    // تحميل الوحدات المتاحة
    loadAvailableUnits();

    // تحديد الفرع والوحدة في القوائم المنسدلة
    $('#adjustment-branch').val(branchId);
    $('#adjustment-unit').val(unitId);

    // تحديث عرض النافذة
    updateAdjustmentView();

    // تحديث معلومات المخزون الحالية
    updateBranchInventory();

    // إفراغ حقول الإدخال
    $('#adjustment-quantity').val('');
    $('#adjustment-direct-cost').val('');
    $('#adjustment-reason').val('stock_count');
    $('#adjustment-custom-reason').val('');
    $('#custom-reason-container').hide();
    $('#adjustment-notes').val('');
    $('#adjustment-reference').val('');

    // إعادة تعيين حالة التأكيد
    $('#adjustment-confirmation').prop('checked', false);
    $('#save-adjustment').prop('disabled', true);

    // عرض النافذة
    $('#adjustment-modal').modal('show');
  }

  // البحث عن عنصر مخزون
  function findInventoryItem(branchId, unitId) {
    if (!inventoryData || !Array.isArray(inventoryData) || inventoryData.length === 0) {
      return null;
    }

    // تحويل المعرفات إلى أرقام للمقارنة الدقيقة
    const branchIdNum = parseInt(branchId, 10);
    const unitIdNum = parseInt(unitId, 10);

    const inventory = inventoryData.find(function(item) {
      return parseInt(item.branch_id, 10) === branchIdNum && parseInt(item.unit_id, 10) === unitIdNum;
    });

    return inventory || null;
  }

  // الحصول على اسم الفرع
  function getBranchName(branchId) {
    if (!branches || !Array.isArray(branches) || branches.length === 0) {
      return '{{ text_unknown }}';
    }

    const branch = branches.find(function(b) {
      return b.branch_id == branchId;
    });

    return branch ? branch.name : '{{ text_unknown }}';
  }

  // الحصول على نص سبب التعديل
  function getReasonText(reasonType) {
    switch (reasonType) {
      case 'market':
        return '{{ text_market_price_change }}';
      case 'supplier':
        return '{{ text_supplier_price_change }}';
      case 'correction':
        return '{{ text_data_correction }}';
      default:
        return reasonType;
    }
  }

  // واجهة عامة للمدير
  return {
    init,
    openNewAdjustmentModal,
    openAdjustmentDialog,
    openCostDialog,
    updateBranchInventory,
    updateUnitInventory,
    updateAdjustmentView,
    updateAdjustmentPreview,
    toggleCustomReason,
    saveInventoryAdjustment,
    updateCostReasonField,
    updateCostPreview,
    saveCostAdjustment,
    loadInventory,
    loadMovements,
    loadRecentMovements,
    renderInventoryTable,
    findInventoryItem,
    getBranchName,
    createInventoryForUnit,
    updateUnitsList
  };
})();