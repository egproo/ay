/**
 * BundleManager.js - مدير الحزم والخصومات
 */

var BundleManager = {
  bundleRow: 0,
  discountRow: 0,

  /**
   * تهيئة مدير الحزم
   */
  init: function() {
    console.log('تهيئة مدير الحزم...');

    // تحديد عدد الصفوف الحالية
    this.bundleRow = $('#bundle-container .panel').length;
    this.discountRow = $('#discount-container .panel').length;

    // إعداد أحداث تغيير نوع الخصم
    this.setupDiscountTypeEvents();

    console.log('اكتملت تهيئة مدير الحزم. عدد الحزم: ' + this.bundleRow + ', عدد الخصومات: ' + this.discountRow);
  },

  /**
   * تهيئة تاب الحزم
   */
  initBundleTab: function() {
    console.log('تهيئة تاب الحزم...');

    // إعداد الاستكمال التلقائي للمنتجات
    this.setupBundleAutocomplete();

    // تفعيل أدوات اختيار التاريخ
    $('.date').datetimepicker({
      pickTime: false,
      format: 'YYYY-MM-DD'
    });

    // تفعيل select2 إذا كان متاحاً
    if ($.fn.select2) {
      $('.select2').select2();
    }
  },

  /**
   * تهيئة تاب الخصومات
   */
  initDiscountsTab: function() {
    console.log('تهيئة تاب الخصومات...');

    // إعداد أحداث تغيير نوع الخصم
    this.setupDiscountTypeEvents();

    // تفعيل أدوات اختيار التاريخ
    $('.date').datetimepicker({
      pickTime: false,
      format: 'YYYY-MM-DD'
    });
  },

  /**
   * إعداد أحداث تغيير نوع الخصم
   */
  setupDiscountTypeEvents: function() {
    $('.discount-type-select').off('change').on('change', function() {
      var row = $(this).data('row');
      var type = $(this).val();

      if (type === 'buy_x_get_y') {
        $('.get-quantity-container[data-row="' + row + '"]').show();
        $('.discount-type-container[data-row="' + row + '"]').hide();
        $('.discount-value-container[data-row="' + row + '"]').hide();
      } else if (type === 'buy_x_get_discount') {
        $('.get-quantity-container[data-row="' + row + '"]').hide();
        $('.discount-type-container[data-row="' + row + '"]').show();
        $('.discount-value-container[data-row="' + row + '"]').show();
      }
    });
  },

  /**
   * إعداد الاستكمال التلقائي للمنتجات في الحزم
   */
  setupBundleAutocomplete: function() {
    $('input[id^="input-bundle-product"]').each(function() {
      var bundleRow = $(this).attr('id').replace('input-bundle-product', '');

      $(this).autocomplete({
        'source': function(request, response) {
          $.ajax({
            url: 'index.php?route=catalog/product/autocomplete&user_token=' + user_token + '&filter_name=' +  encodeURIComponent(request),
            dataType: 'json',
            success: function(json) {
              response($.map(json, function(item) {
                return {
                  label: item['name'],
                  value: item['product_id'],
                  model: item['model'],
                  units: item['units'] || []
                }
              }));
            }
          });
        },
        'select': function(item) {
          BundleManager.addBundleProduct(bundleRow, item);
          $(this).val('');
          return false;
        }
      });
    });
  },

  /**
   * إضافة منتج إلى الحزمة
   */
  addBundleProduct: function(bundleRow, item) {
    var html = '<tr>';
    html += '  <td>' + item['label'];
    html += '    <input type="hidden" name="product_bundle[' + bundleRow + '][bundle_item][' + item['value'] + '][product_id]" value="' + item['value'] + '" />';
    html += '  </td>';
    html += '  <td>';
    html += '    <input type="number" name="product_bundle[' + bundleRow + '][bundle_item][' + item['value'] + '][quantity]" value="1" class="form-control" min="1" step="1" />';
    html += '  </td>';
    html += '  <td>';
    html += '    <select name="product_bundle[' + bundleRow + '][bundle_item][' + item['value'] + '][unit_id]" class="form-control">';

    if (item['units'] && item['units'].length > 0) {
      for (var i = 0; i < item['units'].length; i++) {
        html += '<option value="' + item['units'][i]['unit_id'] + '">' + item['units'][i]['unit_name'] + '</option>';
      }
    } else {
      html += '<option value="0">{{ text_default_unit }}</option>';
    }

    html += '    </select>';
    html += '  </td>';
    html += '  <td>';
    html += '    <div class="checkbox">';
    html += '      <label>';
    html += '        <input type="checkbox" name="product_bundle[' + bundleRow + '][bundle_item][' + item['value'] + '][is_free]" value="1" />';
    html += '      </label>';
    html += '    </div>';
    html += '  </td>';
    html += '  <td>';
    html += '    <button type="button" onclick="$(this).closest(\'tr\').remove();" class="btn btn-danger btn-sm">';
    html += '      <i class="fa fa-trash"></i>';
    html += '    </button>';
    html += '  </td>';
    html += '</tr>';

    $('#bundle-products' + bundleRow + ' tbody').append(html);
  },

  /**
   * إضافة حزمة جديدة
   */
  addBundle: function() {
    var html = '<div class="panel panel-default" id="bundle-card' + this.bundleRow + '">';
    html += '  <div class="panel-heading">';
    html += '    <div class="pull-right">';
    html += '      <button type="button" onclick="BundleManager.removeBundle(' + this.bundleRow + ');" class="btn btn-danger btn-xs">';
    html += '        <i class="fa fa-trash"></i>';
    html += '      </button>';
    html += '    </div>';
    html += '    <h3 class="panel-title">{{ entry_bundle_name }}</h3>';
    html += '  </div>';
    html += '  <div class="panel-body">';
    html += '    <div class="row mb-3">';
    html += '      <div class="col-md-4">';
    html += '        <div class="form-group">';
    html += '          <label>{{ entry_bundle_name }}</label>';
    html += '          <input type="text" name="product_bundle[' + this.bundleRow + '][name]" value="" placeholder="{{ entry_bundle_name }}" class="form-control" />';
    html += '        </div>';
    html += '      </div>';
    html += '      <div class="col-md-3">';
    html += '        <div class="form-group">';
    html += '          <label>{{ entry_discount_type }}</label>';
    html += '          <select name="product_bundle[' + this.bundleRow + '][discount_type]" class="form-control">';
    html += '            <option value="percentage">{{ text_percentage }}</option>';
    html += '            <option value="fixed">{{ text_fixed }}</option>';
    html += '            <option value="product">{{ text_free_product }}</option>';
    html += '          </select>';
    html += '        </div>';
    html += '      </div>';
    html += '      <div class="col-md-3">';
    html += '        <div class="form-group">';
    html += '          <label>{{ entry_bundle_discount_value }}</label>';
    html += '          <input type="text" name="product_bundle[' + this.bundleRow + '][discount_value]" value="" placeholder="{{ entry_bundle_discount_value }}" class="form-control" />';
    html += '        </div>';
    html += '      </div>';
    html += '      <div class="col-md-2">';
    html += '        <div class="form-group">';
    html += '          <label>{{ entry_status }}</label>';
    html += '          <select name="product_bundle[' + this.bundleRow + '][status]" class="form-control">';
    html += '            <option value="1">{{ text_enabled }}</option>';
    html += '            <option value="0">{{ text_disabled }}</option>';
    html += '          </select>';
    html += '        </div>';
    html += '      </div>';
    html += '    </div>';

    html += '    <h4>{{ entry_bundle_products }}</h4>';
    html += '    <div class="table-responsive mb-3">';
    html += '      <table class="table table-bordered" id="bundle-products' + this.bundleRow + '">';
    html += '        <thead>';
    html += '          <tr>';
    html += '            <th>{{ entry_product }}</th>';
    html += '            <th>{{ entry_quantity }}</th>';
    html += '            <th>{{ entry_unit }}</th>';
    html += '            <th>{{ text_free }}</th>';
    html += '            <th width="90">{{ text_action }}</th>';
    html += '          </tr>';
    html += '        </thead>';
    html += '        <tbody>';
    html += '        </tbody>';
    html += '        <tfoot>';
    html += '          <tr>';
    html += '            <td colspan="5">';
    html += '              <div class="input-group">';
    html += '                <input type="text" name="product_bundle[' + this.bundleRow + '][product]" value="" placeholder="{{ entry_product }}" id="input-bundle-product' + this.bundleRow + '" class="form-control" />';
    html += '                <span class="input-group-btn">';
    html += '                  <button type="button" class="btn btn-primary" onclick="BundleManager.searchBundleProduct(' + this.bundleRow + ');">';
    html += '                    <i class="fa fa-search"></i>';
    html += '                  </button>';
    html += '                </span>';
    html += '              </div>';
    html += '            </td>';
    html += '          </tr>';
    html += '        </tfoot>';
    html += '      </table>';
    html += '    </div>';
    html += '  </div>';
    html += '</div>';

    $('#bundle-container').append(html);

    // إعداد الاستكمال التلقائي للمنتج الجديد
    this.setupBundleAutocomplete();

    this.bundleRow++;
  },

  /**
   * إزالة حزمة
   */
  removeBundle: function(bundleRow) {
    $('#bundle-card' + bundleRow).remove();
  },

  /**
   * البحث عن منتج للحزمة
   */
  searchBundleProduct: function(bundleRow) {
    $('#input-bundle-product' + bundleRow).autocomplete('search', $('#input-bundle-product' + bundleRow).val());
  },

  /**
   * إضافة خصم جديد
   */
  addDiscount: function() {
    var html = '<div class="panel panel-default" id="discount-card' + this.discountRow + '">';
    html += '  <div class="panel-heading">';
    html += '    <div class="pull-right">';
    html += '      <button type="button" onclick="BundleManager.removeDiscount(' + this.discountRow + ');" class="btn btn-danger btn-xs">';
    html += '        <i class="fa fa-trash"></i>';
    html += '      </button>';
    html += '    </div>';
    html += '    <h3 class="panel-title">{{ entry_discount_name }}</h3>';
    html += '  </div>';
    html += '  <div class="panel-body">';
    html += '    <div class="row mb-3">';
    html += '      <div class="col-md-4">';
    html += '        <div class="form-group">';
    html += '          <label>{{ entry_discount_name }}</label>';
    html += '          <input type="text" name="product_discount[' + this.discountRow + '][name]" value="" placeholder="{{ entry_discount_name }}" class="form-control" required />';
    html += '        </div>';
    html += '      </div>';
    html += '      <div class="col-md-4">';
    html += '        <div class="form-group">';
    html += '          <label>{{ entry_discount_type }}</label>';
    html += '          <select name="product_discount[' + this.discountRow + '][type]" class="form-control discount-type-select" data-row="' + this.discountRow + '">';
    html += '            <option value="buy_x_get_y">{{ text_buy_x_get_y }}</option>';
    html += '            <option value="buy_x_get_discount">{{ text_buy_x_get_discount }}</option>';
    html += '          </select>';
    html += '        </div>';
    html += '      </div>';
    html += '      <div class="col-md-4">';
    html += '        <div class="form-group">';
    html += '          <label>{{ entry_status }}</label>';
    html += '          <select name="product_discount[' + this.discountRow + '][status]" class="form-control">';
    html += '            <option value="1">{{ text_enabled }}</option>';
    html += '            <option value="0">{{ text_disabled }}</option>';
    html += '          </select>';
    html += '        </div>';
    html += '      </div>';
    html += '    </div>';

    html += '    <div class="panel panel-default discount-details" data-row="' + this.discountRow + '">';
    html += '      <div class="panel-body">';
    html += '        <div class="row">';
    html += '          <div class="col-md-3">';
    html += '            <div class="form-group">';
    html += '              <label>{{ entry_buy_quantity }}</label>';
    html += '              <input type="number" name="product_discount[' + this.discountRow + '][buy_quantity]" value="1" placeholder="{{ entry_buy_quantity }}" class="form-control" min="1" required />';
    html += '            </div>';
    html += '          </div>';
    html += '          <div class="col-md-3 get-quantity-container">';
    html += '            <div class="form-group">';
    html += '              <label>{{ entry_get_quantity }}</label>';
    html += '              <input type="number" name="product_discount[' + this.discountRow + '][get_quantity]" value="1" placeholder="{{ entry_get_quantity }}" class="form-control" min="0" />';
    html += '            </div>';
    html += '          </div>';
    html += '          <div class="col-md-3 discount-type-container" style="display:none;">';
    html += '            <div class="form-group">';
    html += '              <label>{{ entry_discount_type }}</label>';
    html += '              <select name="product_discount[' + this.discountRow + '][discount_type]" class="form-control">';
    html += '                <option value="percentage">{{ text_percentage }}</option>';
    html += '                <option value="fixed">{{ text_fixed }}</option>';
    html += '              </select>';
    html += '            </div>';
    html += '          </div>';
    html += '          <div class="col-md-3 discount-value-container" style="display:none;">';
    html += '            <div class="form-group">';
    html += '              <label>{{ entry_discount_value }}</label>';
    html += '              <input type="text" name="product_discount[' + this.discountRow + '][discount_value]" value="10" placeholder="{{ entry_discount_value }}" class="form-control discount-value-input" required />';
    html += '            </div>';
    html += '          </div>';
    html += '        </div>';
    html += '      </div>';
    html += '    </div>';

    html += '    <div class="row">';
    html += '      <div class="col-md-4">';
    html += '        <div class="form-group">';
    html += '          <label>{{ entry_unit }}</label>';
    html += '          <select name="product_discount[' + this.discountRow + '][unit_id]" class="form-control">';
    html += '            <option value="0">{{ text_all_units }}</option>';

    // إضافة الوحدات المتاحة
    if (typeof UnitManager !== 'undefined' && typeof UnitManager.getUnits === 'function') {
      var units = UnitManager.getUnits();
      for (var i = 0; i < units.length; i++) {
        html += '<option value="' + units[i].unit_id + '">' + units[i].unit_name + '</option>';
      }
    }

    html += '          </select>';
    html += '        </div>';
    html += '      </div>';
    html += '      <div class="col-md-4">';
    html += '        <div class="form-group">';
    html += '          <label>{{ entry_date_start }}</label>';
    html += '          <div class="input-group date">';
    html += '            <input type="text" name="product_discount[' + this.discountRow + '][date_start]" value="" placeholder="{{ entry_date_start }}" data-date-format="YYYY-MM-DD" class="form-control" />';
    html += '            <span class="input-group-btn">';
    html += '              <button class="btn btn-default" type="button"><i class="fa fa-calendar"></i></button>';
    html += '            </span>';
    html += '          </div>';
    html += '        </div>';
    html += '      </div>';
    html += '      <div class="col-md-4">';
    html += '        <div class="form-group">';
    html += '          <label>{{ entry_date_end }}</label>';
    html += '          <div class="input-group date">';
    html += '            <input type="text" name="product_discount[' + this.discountRow + '][date_end]" value="" placeholder="{{ entry_date_end }}" data-date-format="YYYY-MM-DD" class="form-control" />';
    html += '            <span class="input-group-btn">';
    html += '              <button class="btn btn-default" type="button"><i class="fa fa-calendar"></i></button>';
    html += '            </span>';
    html += '          </div>';
    html += '        </div>';
    html += '      </div>';
    html += '    </div>';

    html += '    <div class="form-group">';
    html += '      <label>{{ entry_notes }}</label>';
    html += '      <textarea name="product_discount[' + this.discountRow + '][notes]" class="form-control" rows="2"></textarea>';
    html += '    </div>';
    html += '  </div>';
    html += '</div>';

    $('#discount-container').append(html);

    // إعداد أحداث تغيير نوع الخصم
    this.setupDiscountTypeEvents();

    // تفعيل أدوات اختيار التاريخ
    $('.date').datetimepicker({
      pickTime: false,
      format: 'YYYY-MM-DD'
    });

    this.discountRow++;
  },

  /**
   * إزالة خصم
   */
  removeDiscount: function(discountRow) {
    $('#discount-card' + discountRow).remove();
  }
};