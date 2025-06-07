/**
 * DiscountManager.js - مدير خصومات المنتج
 */

const DiscountManager = (function() {
  /**
   * تهيئة مدير الخصومات
   */
  function init() {
    // تهيئة عداد صفوف الخصومات
    discount_row = $('#discount-container .panel').length || 0;
    
    // إضافة زر لإضافة خصم
    $('#add-discount').off('click').on('click', function() {
      addDiscount();
    });
    
    // إضافة مستمع لتغيير نوع الخصم
    $(document).off('change', '.discount-type-select').on('change', '.discount-type-select', function() {
      const row = $(this).data('row');
      handleDiscountTypeChange(row, $(this).val());
    });
  }
  
  /**
   * إضافة خصم
   */
  function addDiscount() {
    let html = `
      <div class="panel panel-default" id="discount-card${discount_row}">
        <div class="panel-heading">
          <div class="pull-right">
            <button type="button" onclick="DiscountManager.removeDiscount(${discount_row});" class="btn btn-danger btn-xs">
              <i class="fa fa-trash"></i>
            </button>
          </div>
          <h3 class="panel-title">{{ entry_discount_name }}</h3>
        </div>
        <div class="panel-body">
          <div class="row mb-3">
            <div class="col-md-4">
              <div class="form-group">
                <label>{{ entry_discount_name }}</label>
                <input type="text" name="product_discount[${discount_row}][name]" value="" placeholder="{{ entry_discount_name }}" class="form-control" required />
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>{{ entry_discount_type }}</label>
                <select name="product_discount[${discount_row}][type]" class="form-control discount-type-select" data-row="${discount_row}">
                  <option value="buy_x_get_y">{{ text_buy_x_get_y }}</option>
                  <option value="buy_x_get_discount">{{ text_buy_x_get_discount }}</option>
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>{{ entry_status }}</label>
                <select name="product_discount[${discount_row}][status]" class="form-control">
                  <option value="1">{{ text_enabled }}</option>
                  <option value="0">{{ text_disabled }}</option>
                </select>
              </div>
            </div>
          </div>
          
          <div class="panel panel-default discount-details" data-row="${discount_row}">
            <div class="panel-body">
              <div class="row">
                <div class="col-md-3">
                  <div class="form-group">
                    <label>{{ entry_buy_quantity }}</label>
                    <input type="number" name="product_discount[${discount_row}][buy_quantity]" value="1" placeholder="{{ entry_buy_quantity }}" class="form-control" min="1" required />
                  </div>
                </div>
                <div class="col-md-3 get-quantity-container">
                  <div class="form-group">
                    <label>{{ entry_get_quantity }}</label>
                    <input type="number" name="product_discount[${discount_row}][get_quantity]" value="0" placeholder="{{ entry_get_quantity }}" class="form-control" min="0" />
                  </div>
                </div>
                <div class="col-md-3 discount-type-container" style="display:none;">
                  <div class="form-group">
                    <label>{{ entry_discount_type }}</label>
                    <select name="product_discount[${discount_row}][discount_type]" class="form-control">
                      <option value="percentage">{{ text_percentage }}</option>
                      <option value="fixed">{{ text_fixed }}</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-3 discount-value-container" style="display:none;">
                  <div class="form-group">
                    <label>{{ entry_discount_value }}</label>
                    <input type="text" name="product_discount[${discount_row}][discount_value]" value="0" placeholder="{{ entry_discount_value }}" class="form-control discount-value-input" required />
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>{{ entry_unit }}</label>
                <select name="product_discount[${discount_row}][unit_id]" class="form-control">
                  <option value="0">{{ text_all_units }}</option>
                  
                  <!-- إضافة الوحدات الحالية للقائمة المنسدلة -->
                  ${getUnitOptionsHtml()}
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>{{ entry_date_start }}</label>
                <div class="input-group date">
                  <input type="text" name="product_discount[${discount_row}][date_start]" value="" placeholder="{{ entry_date_start }}" data-date-format="YYYY-MM-DD" class="form-control" />
                  <span class="input-group-btn">
                    <button class="btn btn-default" type="button"><i class="fa fa-calendar"></i></button>
                  </span>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>{{ entry_date_end }}</label>
                <div class="input-group date">
                  <input type="text" name="product_discount[${discount_row}][date_end]" value="" placeholder="{{ entry_date_end }}" data-date-format="YYYY-MM-DD" class="form-control" />
                  <span class="input-group-btn">
                    <button class="btn btn-default" type="button"><i class="fa fa-calendar"></i></button>
                  </span>
                </div>
              </div>
            </div>
          </div>
          
          <div class="form-group">
            <label>{{ entry_notes }}</label>
            <textarea name="product_discount[${discount_row}][notes]" class="form-control" rows="2"></textarea>
          </div>
        </div>
      </div>
    `;

    $('#discount-container').append(html);

    // تهيئة منتقي التاريخ
    $('#discount-card' + discount_row + ' .date').datetimepicker({
      pickTime: false,
      format: 'YYYY-MM-DD'
    });
    
    // تهيئة حدث تغيير نوع الخصم
    handleDiscountTypeChange(discount_row, 'buy_x_get_y');
    
    discount_row++;
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
   * إزالة خصم
   */
  function removeDiscount(row) {
    $('#discount-card' + row).remove();
  }
  
  /**
   * معالجة تغيير نوع الخصم
   */
  function handleDiscountTypeChange(row, type) {
    const $row = $('div.discount-details[data-row="' + row + '"]');
    
    // الحصول على عناصر التحكم
    const $getQuantityContainer = $row.find('.get-quantity-container');
    const $discountTypeContainer = $row.find('.discount-type-container');
    const $discountValueContainer = $row.find('.discount-value-container');
    const $getQuantityInput = $getQuantityContainer.find('input');
    
    if (type === 'buy_x_get_y') {
      // عرض الكمية المجانية، إخفاء إعدادات الخصم
      $getQuantityContainer.show();
      $discountTypeContainer.hide();
      $discountValueContainer.hide();
      $getQuantityInput.prop('readonly', false);
      
      // تعيين القيم الافتراضية للحقول المخفية
      $row.find('select[name$="[discount_type]"]').val('percentage');
      $row.find('input[name$="[discount_value]"]').val('100');
    } else {
      // إخفاء الكمية المجانية، عرض إعدادات الخصم
      $getQuantityContainer.hide();
      $discountTypeContainer.show();
      $discountValueContainer.show();
      $getQuantityInput.prop('readonly', true).val('0');
    }
  }
  
  // الواجهة العامة
  return {
    init,
    addDiscount,
    removeDiscount,
    handleDiscountTypeChange,
    getUnitOptionsHtml
  };
})();