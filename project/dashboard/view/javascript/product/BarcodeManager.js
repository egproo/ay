/**
 * BarcodeManager.js - مدير باركودات المنتج
 */

const BarcodeManager = (function() {
  /**
   * تهيئة مدير الباركود
   */
  function init() {
    // تهيئة عداد صفوف الباركود
    barcode_row = $('#product-barcodes tbody tr').length || 0;
    
    // إعداد أحداث الباركود
    setupBarcodeEvents();
  }
  
  /**
   * إعداد أحداث الباركود
   */
  function setupBarcodeEvents() {
    // زر إضافة باركود
    $('#add-barcode').off('click').on('click', function() {
      addBarcode();
    });

    // استمع لأحداث تغيير الوحدة لتحديث الخيارات المرتبطة بالوحدة
    $(document).off('change', '.barcode-unit').on('change', '.barcode-unit', function() {
      const rowId = $(this).closest('tr').attr('id').replace('barcode-row', '');
      const unitId = $(this).val();
      
      // تحديث قائمة الخيارات المرتبطة بالوحدة
      updateOptionsForUnit(unitId, rowId);
    });

    // تهيئة أحداث معاينة الباركود الموجودة
    $('.barcode-row').each(function() {
      setupBarcodePreviewEvents(this);
    });
  }
  
  /**
   * إضافة باركود
   */
  function addBarcode() {
    let html = `
      <tr id="barcode-row${barcode_row}" class="barcode-row">
        <td class="text-center"><input type="text" name="product_barcode[${barcode_row}][barcode]" value="" placeholder="{{ entry_barcode }}" class="form-control barcode-value" /></td>
        <td class="text-center">
          <select name="product_barcode[${barcode_row}][type]" class="form-control barcode-type">
            <option value="CODE128">CODE128</option>
            <option value="EAN">EAN</option>
            <option value="UPC">UPC</option>
            <option value="ISBN">ISBN</option>
          </select>
        </td>
        <td class="text-center">
          <select name="product_barcode[${barcode_row}][unit_id]" class="form-control barcode-unit">
            <option value="">{{ text_select }}</option>
            ${getUnitOptionsHtml()}
          </select>
        </td>
        <td class="text-center">
          <select name="product_barcode[${barcode_row}][option_id]" class="form-control barcode-option" onchange="BarcodeManager.updateOptionValues(this, ${barcode_row})">
            <option value="">{{ text_no_option }}</option>
          </select>
        </td>
        <td class="text-center">
          <select name="product_barcode[${barcode_row}][option_value_id]" class="form-control barcode-option-value" disabled>
            <option value="">{{ text_select_option_first }}</option>
          </select>
        </td>
        <td class="text-center">
          <button type="button" onclick="BarcodeManager.removeBarcode(${barcode_row});" data-toggle="tooltip" title="{{ button_remove }}" class="btn btn-danger"><i class="fa fa-minus-circle"></i></button>
        </td>
      </tr>
    `;
    
    $('#product-barcodes tbody').append(html);
    
    // إعداد أحداث معاينة الباركود
    setupBarcodePreviewEvents($('#barcode-row' + barcode_row));
    
    barcode_row++;
  }
  
  /**
   * إزالة باركود
   */
  function removeBarcode(row) {
    $('#barcode-row' + row).remove();
  }
  
  /**
   * الحصول على خيارات الوحدات كـ HTML
   */
  function getUnitOptionsHtml() {
    let html = '';
    const currentUnits = UnitManager.getCurrentUnits();
    
    for (let i = 0; i < currentUnits.length; i++) {
      const unitId = currentUnits[i];
      const unitName = UnitManager.getUnitName(unitId);
      if (unitId && unitName) {
        html += `<option value="${unitId}">${unitName}</option>`;
      }
    }
    
    return html;
  }

  /**
   * تحديث قائمة الخيارات المرتبطة بالوحدة
   */
  function updateOptionsForUnit(unitId, rowId) {
    const $optionSelect = $('#barcode-row' + rowId + ' .barcode-option');
    const $optionValueSelect = $('#barcode-row' + rowId + ' .barcode-option-value');
    
    // إعادة تعيين قوائم الاختيار
    $optionValueSelect.html('<option value="">{{ text_select_option_first }}</option>');
    $optionValueSelect.prop('disabled', true);
    
    let html = '<option value="">{{ text_no_option }}</option>';
    
    // البحث عن الخيارات المرتبطة بالوحدة المحددة
    const optionTabs = $('a[href^="#tab-option"]');
    
    optionTabs.each(function() {
      const href = $(this).attr('href');
      const tabId = href.replace('#tab-option', '');
      const tabOptionId = $('#tab-option' + tabId + ' input[name="product_option[' + tabId + '][option_id]"]').val();
      const tabUnitId = $('#input-unit' + tabId).val();
      const optionName = $('#tab-option' + tabId + ' input[name="product_option[' + tabId + '][name]"]').val();
      
      // إذا كانت الوحدة متوافقة أو لم يتم تحديد وحدة للباركود
      if (!unitId || tabUnitId == unitId) {
        html += `<option value="${tabOptionId}" data-option-id="${tabId}">${optionName}</option>`;
      }
    });
    
    $optionSelect.html(html);
  }

  /**
   * تحديث قيم الخيارات بناءً على الخيار المحدد
   */
  function updateOptionValues(selectElement, rowId) {
    const optionId = $(selectElement).val();
    const $row = $('#barcode-row' + rowId);
    const $optionValueSelect = $row.find('.barcode-option-value');
    const unitId = $row.find('.barcode-unit').val();
    
    if (!optionId) {
      $optionValueSelect.html('<option value="">{{ text_select_option_first }}</option>');
      $optionValueSelect.prop('disabled', true);
      return;
    }
    
    // البحث عن علامة التبويب الخاصة بالخيار
    let optionIndex = null;
    $('a[href^="#tab-option"]').each(function() {
      const href = $(this).attr('href');
      const tabId = href.replace('#tab-option', '');
      const tabOptionId = $('#tab-option' + tabId + ' input[name="product_option[' + tabId + '][option_id]"]').val();
      
      if (tabOptionId == optionId) {
        optionIndex = tabId;
        return false; // توقف عن التكرار
      }
    });
    
    if (optionIndex === null) {
      return;
    }
    
    // التحقق مما إذا كانت الوحدة المحددة تتوافق مع وحدة الخيار
    const optionUnitId = $('#input-unit' + optionIndex).val();
    
    if (unitId && optionUnitId && unitId != optionUnitId) {
      showNotification('warning', 'الخيار "' + $('#tab-option' + optionIndex + ' input[name="product_option[' + optionIndex + '][name]"]').val() + 
                              '" مرتبط بوحدة "' + UnitManager.getUnitName(optionUnitId) + 
                              '" بينما الباركود مرتبط بوحدة "' + UnitManager.getUnitName(unitId) + '"');
    }
    
    let html = '<option value="">{{ text_select }}</option>';
    
    // الحصول على قيم الخيار من علامة تبويب الخيار
    $('#option-value' + optionIndex + ' tbody tr').each(function() {
      const optionValueSelect = $(this).find('select[name*="[option_value_id]"]');
      const optionValueId = optionValueSelect.val();
      const optionValueText = optionValueSelect.find('option:selected').text();
      
      if (optionValueId && optionValueText) {
        html += `<option value="${optionValueId}">${optionValueText}</option>`;
      }
    });
    
    $optionValueSelect.html(html);
    $optionValueSelect.prop('disabled', false);
    
    // توليد الباركود تلقائياً إذا كان فارغاً
    const $barcodeInput = $row.find('.barcode-value');
    if (!$barcodeInput.val()) {
      generateBarcodeWithOption(rowId);
    }
  }

  /**
   * توليد باركود تلقائي مع معلومات الخيار
   */
  function generateBarcodeWithOption(rowId) {
    const productId = $('input[name="product_id"]').val() || '0';
    const unitId = $('#barcode-row' + rowId).find('.barcode-unit').val() || '0';
    const optionId = $('#barcode-row' + rowId).find('.barcode-option').val() || '0';
    const optionValueId = $('#barcode-row' + rowId).find('.barcode-option-value').val() || '0';
    const $barcodeInput = $('#barcode-row' + rowId).find('.barcode-value');
    
    // تنسيق الباركود: product_id-unit_id-option_id-option_value_id
    let code = productId;
    
    if (unitId) {
      code += '-' + unitId;
      
      if (optionId) {
        code += '-' + optionId;
        
        if (optionValueId) {
          code += '-' + optionValueId;
        }
      }
    }
    
    $barcodeInput.val(code);
  }
  
  /**
   * إعداد أحداث معاينة الباركود
   */
  function setupBarcodePreviewEvents(element) {
    // أحداث لعناصر الباركود لتحديث المعاينة
    $(element).find('.barcode-value, .barcode-type').off('change input').on('change input', function() {
      // يمكن إضافة وظيفة معاينة الباركود هنا إذا لزم الأمر
    });
    
    // أحداث لخيارات الباركود لتوليد الباركود تلقائياً
    $(element).find('.barcode-unit, .barcode-option-value').off('change').on('change', function() {
      const rowId = $(element).attr('id').replace('barcode-row', '');
      const $barcodeInput = $(element).find('.barcode-value');
      
      // توليد باركود تلقائياً إذا كان فارغاً
      if (!$barcodeInput.val()) {
        generateBarcodeWithOption(rowId);
      }
    });
  }
  
  // الواجهة العامة
  return {
    init,
    addBarcode,
    removeBarcode,
    updateOptionValues,
    updateOptionsForUnit,
    setupBarcodePreviewEvents,
    generateBarcodeWithOption
  };
})();