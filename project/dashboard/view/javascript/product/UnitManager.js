/**
 * UnitManager.js - مدير وحدات المنتج
 */

const UnitManager = (function() {
  /**
   * تهيئة مدير الوحدات
   */
  function init() {
    // تحديث عداد صفوف الوحدات
    unitRow = $('#product-units tbody tr').length || 0;
    // إعداد مستمعي الأحداث
    setupEvents();
    loadUnits();
  }

  /**
   * إعداد مستمعي الأحداث
   */
  function setupEvents() {
    // زر إضافة وحدة
    $('#add-unit').off('click').on('click', function() {
      addUnitRow({});
      updateRelatedTables();
    });

    // تغيير نوع الوحدة أو الوحدة المحددة
    $(document).off('change', '.unit-select, .unit-type').on('change', '.unit-select, .unit-type', function() {
      updateUnitTypes();
      updateRelatedTables();
      updateUnitConversionSelects();
    });
  }

  /**
   * تحميل وحدات المنتج
   */
  function loadUnits() {
    // إزالة أي وحدات محملة سابقاً لضمان عدم التكرار
    $('#product-units tbody').empty();

    if (productUnits && Array.isArray(productUnits) && productUnits.length > 0) {
      logMessage("إضافة الوحدات الموجودة:", productUnits);

      // إضافة وحدات المنتج الموجودة
      productUnits.forEach(function(unit) {
        addUnitRow(unit);
      });

      // تحديث أنواع الوحدات والجداول المرتبطة
      updateUnitTypes();
      updateRelatedTables();
    } else {
      // إضافة وحدة أساسية افتراضية فقط إذا لم تكن هناك وحدات
      addDefaultUnit();
    }
  }

  /**
   * إضافة وحدة أساسية افتراضية
   */
  function addDefaultUnit() {
    // التحقق مما إذا كانت هناك وحدات أساسية بالفعل
    if ($('#product-units tbody tr').length > 0) {
      return; // لا تضف وحدة افتراضية إذا كانت هناك وحدات
    }

    let defaultUnitAdded = false;

    if (allUnits && allUnits.length > 0) {
      // البحث عن وحدة EA (كل واحدة) أو قطعة
      const defaultUnit = allUnits.find(function(u) {
        return u.unit_id == '37' || u.unit_id == 37 ||
               u.unit_name.toLowerCase() === 'قطعة' ||
               u.unit_name.toLowerCase() === 'piece';
      });

      if (defaultUnit) {
        logMessage("إضافة وحدة افتراضية: " + defaultUnit.unit_name);
        addUnitRow({
          unit_id: defaultUnit.unit_id,
          unit_type: 'base',
          conversion_factor: 1,
          is_base: 1
        });
        defaultUnitAdded = true;
      }
    }

    if (!defaultUnitAdded) {
      logMessage("لم يتم العثور على وحدة افتراضية، إضافة أول وحدة متاحة كوحدة أساسية");
      // إذا كانت هناك وحدات متاحة، استخدم الأولى
      if (allUnits && allUnits.length > 0) {
        addUnitRow({
          unit_id: allUnits[0].unit_id,
          unit_type: 'base',
          conversion_factor: 1,
          is_base: 1
        });
      } else {
        // لا توجد وحدات متاحة
        addUnitRow({
          unit_id: '',
          unit_type: 'base',
          conversion_factor: 1,
          is_base: 1
        });
      }
    }
  }

  /**
   * إضافة صف وحدة جديد
   */
  function addUnitRow(unit) {
    logMessage("إضافة صف وحدة بالبيانات:", unit);

    // التحقق مما إذا كانت أول وحدة - جعلها وحدة أساسية
    const isFirstUnit = $('#product-units tbody tr').length === 0;
    const isBaseUnit = isFirstUnit || (unit.unit_type === 'base' || unit.is_base === 1);
    const rowClass = isBaseUnit ? 'base-unit' : '';

    // تحديد معامل التحويل بشكل صحيح
    let conversionFactor = 1;
    if (!isBaseUnit && unit.conversion_factor) {
      conversionFactor = parseFloat(unit.conversion_factor);
    } else if (!isBaseUnit) {
      conversionFactor = 1;
    }

    let html = '<tr id="unit-row' + unitRow + '" class="' + rowClass + '">';
    html += '<td class="text-center"><select name="product_unit[' + unitRow + '][unit_id]" class="form-control unit-select">';
    html += '<option value="">{{ text_select }}</option>';

    // إضافة جميع الوحدات المتاحة إلى القائمة المنسدلة
    if (allUnits && allUnits.length > 0) {
      allUnits.forEach(function(availableUnit) {
        const selected = (unit.unit_id == availableUnit.unit_id) ? ' selected' : '';
        html += '<option value="' + availableUnit.unit_id + '"' + selected + '>' + availableUnit.unit_name + '</option>';
      });
    }

    html += '</select></td>';
    html += '<td class="text-center"><select name="product_unit[' + unitRow + '][unit_type]" class="form-control unit-type" ' + (isBaseUnit ? 'disabled' : '') + '>';
    html += '<option value="base"' + (isBaseUnit ? ' selected' : '') + '>{{ text_base_unit }}</option>';
    html += '<option value="additional"' + (!isBaseUnit ? ' selected' : '') + '>{{ text_additional_unit }}</option>';
    html += '</select>';

    // حقل مخفي للحفاظ على القيمة عند التعطيل
    html += '<input type="hidden" name="product_unit[' + unitRow + '][unit_type]" value="' + (isBaseUnit ? 'base' : 'additional') + '" /></td>';

    html += '<td class="text-center"><input type="number" step="0.0001" min="0.0001" name="product_unit[' + unitRow + '][conversion_factor]" value="' + conversionFactor + '" class="form-control conversion-factor" ' + (isBaseUnit ? 'readonly' : '') + ' /></td>';
    html += '<td class="text-center"><button type="button" onclick="UnitManager.removeUnit(' + unitRow + ');" class="btn btn-danger" ' + (isBaseUnit ? 'disabled' : '') + '><i class="fa fa-minus-circle"></i></button></td>';
    html += '</tr>';

    $('#product-units tbody').append(html);

    // إذا كانت وحدة أساسية، تأكد من أن معامل التحويل هو 1
    if (isBaseUnit) {
      $('#unit-row' + unitRow + ' .conversion-factor').val(1);
    }

    // تحديث أنواع الوحدات والقوائم المنسدلة
    updateUnitTypes();
    updateUnitSelects();

    unitRow++;

    updateUnitVisualDiagram();

    // تحديث المخزون والتسعير بعد إضافة وحدة جديدة
    if (typeof InventoryManager !== 'undefined' && InventoryManager.createInventoryForUnit) {
      InventoryManager.createInventoryForUnit(unit.unit_id || $('#unit-row' + (unitRow - 1) + ' .unit-select').val());
    }

    if (typeof PricingManager !== 'undefined' && PricingManager.createPricingForUnit) {
      PricingManager.createPricingForUnit(unit.unit_id || $('#unit-row' + (unitRow - 1) + ' .unit-select').val());
    }

    if (typeof BarcodeManager !== 'undefined' && BarcodeManager.createBarcodeForUnit) {
      BarcodeManager.createBarcodeForUnit(unit.unit_id || $('#unit-row' + (unitRow - 1) + ' .unit-select').val());
    }
  }

  /**
   * تحديث مخطط الوحدات المرئي
   */
  function updateUnitVisualDiagram() {
    // تحديث اسم الوحدة الأساسية
    const baseUnitId = getBaseUnitId();
    if (baseUnitId) {
      $('#base-unit-name').text(getUnitName(baseUnitId));
    } else {
      $('#base-unit-name').text('لم يتم تحديد وحدة أساسية');
    }

    // تحديث الوحدات الإضافية
    $('#additional-units-container').empty();

    $('.unit-select').each(function() {
      const unitId = $(this).val();
      const $row = $(this).closest('tr');
      const unitType = $row.find('.unit-type').val();

      if (unitId && unitType === 'additional') {
        const unitName = getUnitName(unitId);
        const conversionFactor = parseFloat($row.find('.conversion-factor').val()) || 1;

        // إنشاء مربع الوحدة الإضافية
        const unitBox = `
          <div class="additional-unit-box">
            <div class="additional-unit-name">${unitName}</div>
            <div class="conversion-info">
              <span>1 ${unitName} = ${conversionFactor} ${getUnitName(baseUnitId)}</span>
            </div>
          </div>
        `;

        $('#additional-units-container').append(unitBox);
      }
    });
  }

  /**
   * الحصول على معرف الوحدة الأساسية
   */
  function getBaseUnitId() {
    let baseUnitId = null;

    $('.unit-type').each(function() {
      if ($(this).val() === 'base') {
        const $row = $(this).closest('tr');
        baseUnitId = $row.find('.unit-select').val();
        return false; // الخروج من الحلقة
      }
    });

    return baseUnitId;
  }

  /**
   * إزالة صف وحدة
   */
  function removeUnit(row) {
    // لا تقم بالإزالة إذا كانت الصف الوحيد
    if ($('#product-units tbody tr').length <= 1) {
      showNotification('error', 'لا يمكن إزالة الوحدة الأخيرة');
      return;
    }

    // لا تقم بإزالة الوحدة الأساسية
    if ($('#unit-row' + row).find('.unit-type').val() === 'base') {
      showNotification('error', 'لا يمكن إزالة الوحدة الأساسية');
      return;
    }

    $('#unit-row' + row).remove();
    updateUnitTypes();
    updateRelatedTables();
    updateUnitConversionSelects();
  }

  /**
   * تحديث أنواع الوحدات
   */
  function updateUnitTypes() {
    let hasBaseUnit = false;
    let baseUnitRow = null;
    let firstRow = null;

    // أولاً، تحقق مما إذا كان هناك وحدة أساسية بالفعل
    $('.unit-type').each(function(index) {
      const $row = $(this).closest('tr');

      if ($(this).val() === 'base') {
        hasBaseUnit = true;
        baseUnitRow = $row;
      }

      if (index === 0) {
        firstRow = $row;
      }
    });

    // إذا لم تكن هناك وحدة أساسية ولدينا صفوف، قم بتعيين الأول كأساسي
    if (!hasBaseUnit && firstRow) {
      firstRow.find('.unit-type').val('base').prop('disabled', true);
      firstRow.find('input[type="hidden"][name$="[unit_type]"]').val('base');
      firstRow.addClass('base-unit');
      firstRow.find('.conversion-factor').val(1).prop('readonly', true);
      firstRow.find('button').prop('disabled', true);

      hasBaseUnit = true;
      baseUnitRow = firstRow;
    }

    // تحديث جميع الصفوف بناءً على نوع الوحدة
    $('.unit-type').each(function() {
      let isBase = $(this).val() === 'base';
      const $row = $(this).closest('tr');

      // تأكد من أن لدينا وحدة أساسية واحدة فقط
      if (isBase && hasBaseUnit && baseUnitRow && $row.attr('id') !== baseUnitRow.attr('id')) {
        // إذا كانت هذه ليست الوحدة الأساسية المعينة، قم بتغييرها إلى إضافية
        $(this).val('additional');
        isBase = false;
      }

      // تحديث حالة الصف بناءً على نوع الوحدة
      if (isBase) {
        $row.addClass('base-unit');
        $row.find('.conversion-factor').val(1).prop('readonly', true);
        $row.find('button').prop('disabled', true);
        $row.find('input[type="hidden"][name$="[unit_type]"]').val('base');
        $(this).prop('disabled', true);
      } else {
        $row.removeClass('base-unit');
        $row.find('.conversion-factor').prop('readonly', false);
        $row.find('button').prop('disabled', false);
        $row.find('input[type="hidden"][name$="[unit_type]"]').val('additional');
        $(this).prop('disabled', false);
      }
    });

    // تحديث المخطط المرئي للوحدات
    updateUnitVisualDiagram();

    // تحديث قوائم الوحدات المنسدلة
    updateUnitSelects();

    // تحديث الجداول المرتبطة
    updateRelatedTables();
  }

  /**
   * تحديث قوائم الوحدات المنسدلة
   */
  function updateUnitSelects() {
    const units = getCurrentUnits();

    updateInventoryUnitSelect(units);
    updatePricingUnitSelect(units);
    updateBarcodeUnitSelect(units);
    updateAdjustmentUnitSelect(units);
    updateUnitConversionSelects();
  }

  /**
   * الحصول على الوحدات الحالية
   */
  function getCurrentUnits() {
    var units = [];
    $('.unit-select').each(function() {
      var unitId = $(this).val();
      if (unitId && units.indexOf(unitId) === -1) {
        units.push(unitId);
      }
    });
    return units;
  }

  /**
   * تحديث قائمة وحدات المخزون المنسدلة
   */
  function updateInventoryUnitSelect(units) {
    let html = '<option value="">{{ text_select_unit }}</option>';

    if (units && units.length > 0) {
      for (let i = 0; i < units.length; i++) {
        const unitId = units[i];
        const unitName = getUnitName(unitId);
        if (unitId && unitName) {
          html += '<option value="' + unitId + '">' + unitName + '</option>';
        }
      }
    }

    $('#adjustment-unit').html(html);
  }

  /**
   * تحديث قائمة وحدات التسعير المنسدلة
   */
  function updatePricingUnitSelect(units) {
    let html = '<option value="">{{ text_select_unit }}</option>';

    if (units && units.length > 0) {
      for (let i = 0; i < units.length; i++) {
        const unitId = units[i];
        const unitName = getUnitName(unitId);
        if (unitId && unitName) {
          html += '<option value="' + unitId + '">' + unitName + '</option>';
        }
      }
    }

    $('#calc-unit').html(html);
  }

  /**
   * تحديث قائمة وحدات الباركود المنسدلة
   */
  function updateBarcodeUnitSelect(units) {
    let html = '<option value="">{{ text_select }}</option>';

    if (units && units.length > 0) {
      for (let i = 0; i < units.length; i++) {
        const unitId = units[i];
        const unitName = getUnitName(unitId);
        if (unitId && unitName) {
          html += '<option value="' + unitId + '">' + unitName + '</option>';
        }
      }
    }

    $('.barcode-unit').html(html);
  }

  /**
   * تحديث قوائم تحويل الوحدات المنسدلة
   */
  function updateUnitConversionSelects() {
    const currentUnits = getCurrentUnits();
    let html = '<option value="">{{ text_select_unit }}</option>';

    currentUnits.forEach(function(unitId) {
      html += '<option value="' + unitId + '">' + getUnitName(unitId) + '</option>';
    });

    $('#from-unit, #to-unit').html(html);
  }

  /**
   * تحديث الجداول المرتبطة (المخزون والتسعير والباركود)
   */
  function updateRelatedTables() {
    logMessage("تحديث جداول المخزون والتسعير والباركود...");
    const currentUnits = getCurrentUnits();

    updateInventoryTable(currentUnits);
    updatePricingTable(currentUnits);
    updateBarcodeTable(currentUnits);

    // إنشاء سجلات جديدة للوحدات التي ليس لها سجلات
    createMissingRecords(currentUnits);
  }

  /**
   * تحديث جدول المخزون
   */
  function updateInventoryTable(currentUnits) {
    // تحديث جدول المخزون
    if (typeof InventoryManager !== 'undefined') {
      if (InventoryManager.renderInventoryTable) {
        InventoryManager.renderInventoryTable();
      }

      // تحديث قائمة الوحدات في جدول المخزون
      if (InventoryManager.updateUnitsList) {
        InventoryManager.updateUnitsList(currentUnits);
      }
    }
  }

  /**
   * تحديث جدول التسعير
   */
  function updatePricingTable(currentUnits) {
    // تحديث جدول التسعير
    if (typeof PricingManager !== 'undefined') {
      if (PricingManager.updatePricingTable) {
        PricingManager.updatePricingTable(currentUnits);
      }

      // تحديث قائمة الوحدات في جدول التسعير
      if (PricingManager.updateUnitsList) {
        PricingManager.updateUnitsList(currentUnits);
      }
    }
  }

  /**
   * تحديث جدول الباركود
   */
  function updateBarcodeTable(currentUnits) {
    // تحديث جدول الباركود
    if (typeof BarcodeManager !== 'undefined') {
      if (BarcodeManager.updateBarcodeTable) {
        BarcodeManager.updateBarcodeTable(currentUnits);
      }

      // تحديث قائمة الوحدات في جدول الباركود
      if (BarcodeManager.updateUnitsList) {
        BarcodeManager.updateUnitsList(currentUnits);
      }
    }
  }

  /**
   * إنشاء سجلات جديدة للوحدات التي ليس لها سجلات
   */
  function createMissingRecords(currentUnits) {
    if (!currentUnits || !Array.isArray(currentUnits) || currentUnits.length === 0) {
      return;
    }

    // إنشاء سجلات المخزون للوحدات الجديدة
    if (typeof InventoryManager !== 'undefined' && InventoryManager.createInventoryForUnit) {
      currentUnits.forEach(function(unitId) {
        InventoryManager.createInventoryForUnit(unitId);
      });
    }

    // إنشاء سجلات التسعير للوحدات الجديدة
    if (typeof PricingManager !== 'undefined' && PricingManager.createPricingForUnit) {
      currentUnits.forEach(function(unitId) {
        PricingManager.createPricingForUnit(unitId);
      });
    }

    // إنشاء سجلات الباركود للوحدات الجديدة
    if (typeof BarcodeManager !== 'undefined' && BarcodeManager.createBarcodeForUnit) {
      currentUnits.forEach(function(unitId) {
        BarcodeManager.createBarcodeForUnit(unitId);
      });
    }
  }

  /**
   * تحديث قائمة وحدات التعديل المنسدلة
   */
  function updateAdjustmentUnitSelect(units) {
    let html = '<option value="">{{ text_select_unit }}</option>';

    if (units && units.length > 0) {
      for (let i = 0; i < units.length; i++) {
        const unitId = units[i];
        const unitName = getUnitName(unitId);
        if (unitId && unitName) {
          html += '<option value="' + unitId + '">' + unitName + '</option>';
        }
      }
    }

    $('#adjustment-unit').html(html);
  }

  /**
   * الحصول على متوسط التكلفة للوحدة
   */
  function getAverageCostForUnit(unitId) {
    if (!productInventory || !Array.isArray(productInventory) || productInventory.length === 0) {
      return 0;
    }

    // تحويل معرف الوحدة إلى رقم للمقارنة
    const unitIdNum = parseInt(unitId);

    let totalCost = 0;
    let totalQuantity = 0;

    productInventory.forEach(function(item) {
      if (parseInt(item.unit_id) === unitIdNum) {
        const itemQuantity = parseFloat(item.quantity) || 0;
        const itemCost = parseFloat(item.average_cost) || 0;
        totalCost += itemCost * itemQuantity;
        totalQuantity += itemQuantity;
      }
    });

    return totalQuantity > 0 ? totalCost / totalQuantity : 0;
  }

  /**
   * الحصول على اسم الوحدة
   */
  function getUnitName(unitId) {
    if (!unitId) {
      logMessage("تم استدعاء getUnitName مع معرف وحدة فارغ");
      return 'غير معروف';
    }

    if (!allUnits || !Array.isArray(allUnits) || allUnits.length === 0) {
      logMessage("مصفوفة allUnits فارغة أو غير صالحة");
      return 'غير معروف';
    }

    const unitIdNum = parseInt(unitId);
    const unit = allUnits.find(function(u) {
      return parseInt(u.unit_id) === unitIdNum;
    });

    return unit ? unit.unit_name : 'غير معروف (معرف: ' + unitId + ')';
  }

  /**
   * التأكد من وجود وحدة أساسية
   */
  function ensureBaseUnit() {
    // التأكد من وجود وحدة أساسية محددة
    let hasBaseUnit = false;
    $('.unit-type').each(function() {
      if ($(this).val() === 'base') {
        hasBaseUnit = true;
      }
    });

    if (!hasBaseUnit && $('.unit-type').length > 0) {
      $('.unit-type').first().val('base');
      $('.unit-type').first().closest('tr').addClass('base-unit');
      $('.unit-type').first().closest('tr').find('.conversion-factor').val(1);
    }
  }

  /**
   * التحقق من وجود وحدة أساسية
   */
  function hasBaseUnit() {
    let found = false;
    $('.unit-type').each(function() {
      if ($(this).val() === 'base') {
        found = true;
        return false; // الخروج من الحلقة
      }
    });
    return found;
  }

  /**
   * تحويل بين الوحدات
   */
  function convertBetweenUnits(fromUnitId, toUnitId, quantity) {
    // التحقق من صحة المدخلات
    if (!fromUnitId || !toUnitId || isNaN(quantity)) {
      logMessage("خطأ في مدخلات التحويل بين الوحدات", { fromUnitId, toUnitId, quantity });
      return 0;
    }

    // إذا كانت الوحدتان متطابقتين، أعد الكمية كما هي
    if (fromUnitId === toUnitId) {
      return quantity;
    }

    // الحصول على معاملات التحويل من النموذج
    let fromUnit = {
      unit_id: fromUnitId,
      conversion_factor: 1,
      unit_type: 'base'
    };

    let toUnit = {
      unit_id: toUnitId,
      conversion_factor: 1,
      unit_type: 'base'
    };

    // الحصول على الوحدة الأساسية
    const baseUnitId = getBaseUnitId();

    // البحث عن معاملات التحويل من النموذج
    $('.unit-select').each(function() {
      const unitId = $(this).val();
      const $row = $(this).closest('tr');
      const unitType = $row.find('.unit-type').val();
      const conversionFactor = parseFloat($row.find('.conversion-factor').val()) || 1;

      if (unitId == fromUnitId) {
        fromUnit.conversion_factor = conversionFactor;
        fromUnit.unit_type = unitType;
      }

      if (unitId == toUnitId) {
        toUnit.conversion_factor = conversionFactor;
        toUnit.unit_type = unitType;
      }
    });

    // تسجيل معلومات التحويل للتصحيح
    logMessage("معلومات التحويل", {
      fromUnit,
      toUnit,
      baseUnitId,
      quantity
    });

    // التحويل من خلال الوحدة الأساسية
    let result = 0;

    if (fromUnitId == baseUnitId && toUnit.unit_type === 'additional') {
      // من الوحدة الأساسية إلى وحدة إضافية
      result = quantity * toUnit.conversion_factor;
      logMessage("تحويل من الوحدة الأساسية إلى وحدة إضافية", { quantity, factor: toUnit.conversion_factor, result });
    } else if (fromUnit.unit_type === 'additional' && toUnitId == baseUnitId) {
      // من وحدة إضافية إلى الوحدة الأساسية
      result = quantity / fromUnit.conversion_factor;
      logMessage("تحويل من وحدة إضافية إلى الوحدة الأساسية", { quantity, factor: fromUnit.conversion_factor, result });
    } else if (fromUnit.unit_type === 'additional' && toUnit.unit_type === 'additional') {
      // التحويل من إضافية إلى أساسية، ثم من أساسية إلى إضافية
      const baseQuantity = quantity / fromUnit.conversion_factor;
      result = baseQuantity * toUnit.conversion_factor;
      logMessage("تحويل من وحدة إضافية إلى وحدة إضافية أخرى", {
        quantity,
        fromFactor: fromUnit.conversion_factor,
        toFactor: toUnit.conversion_factor,
        baseQuantity,
        result
      });
    } else {
      // كلاهما وحدات أساسية أو حدث خطأ ما
      result = quantity;
      logMessage("لم يتم التعرف على نمط التحويل، إعادة الكمية كما هي", { quantity });
    }

    // التأكد من أن النتيجة رقم صحيح
    if (isNaN(result)) {
      logMessage("خطأ: نتيجة التحويل ليست رقماً", { result });
      return 0;
    }

    return result;
  }

  // الواجهة العامة
  return {
    init,
    addDefaultUnit,
    addUnitRow,
    removeUnit,
    updateUnitTypes,
    updateRelatedTables,
    updateUnitSelects,
    getCurrentUnits,
    getUnitName,
    loadUnits,
    ensureBaseUnit,
    hasBaseUnit,
    getAverageCostForUnit,
    convertBetweenUnits,
    getBaseUnitId
  };
})();