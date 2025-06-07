/**
 * product.js - ملف JavaScript الرئيسي لصفحة المنتج
 */

$(document).ready(function() {
  // تهيئة المديرين بترتيب صحيح
  initializeManagers();

  // إعداد أحداث علامات التبويب
  setupTabs();

  // إعداد تأكيد الحذف
  setupDeleteConfirmation();

  // إعداد الاستكمال التلقائي للفئات والمنتجات المشابهة
  setupAutocomplete();

  // إعداد أدوات اختيار التاريخ
  setupDatepickers();

  // تنفيذ وظائف التهيئة الأخرى
  setupMiscellaneous();
});

/**
 * تهيئة المديرين بترتيب صحيح
 */
function initializeManagers() {
  console.log('بدء تهيئة المديرين...');

  // تهيئة مدير الوحدات أولاً (أساسي)
  if (typeof UnitManager !== 'undefined') {
    console.log('تهيئة مدير الوحدات...');
    UnitManager.init();
  } else {
    console.warn('مدير الوحدات غير متوفر!');
  }

  // ثم تهيئة مدير المخزون (أساسي)
  if (typeof InventoryManager !== 'undefined') {
    console.log('تهيئة مدير المخزون...');
    InventoryManager.init();
  } else {
    console.warn('مدير المخزون غير متوفر!');
  }

  // ثم تهيئة مدير التسعير (أساسي)
  if (typeof PricingManager !== 'undefined') {
    console.log('تهيئة مدير التسعير...');
    PricingManager.init();
  } else {
    console.warn('مدير التسعير غير متوفر!');
  }

  // ثم تهيئة مدير الباركود
  if (typeof BarcodeManager !== 'undefined') {
    console.log('تهيئة مدير الباركود...');
    BarcodeManager.init();
  }

  // تهيئة مدير الحزم
  if (typeof BundleManager !== 'undefined') {
    console.log('تهيئة مدير الحزم...');
    if (typeof BundleManager.init === 'function') {
      BundleManager.init();
    }
  }

  // تهيئة مدير التوصيات
  if (typeof RecommendationManager !== 'undefined') {
    console.log('تهيئة مدير التوصيات...');
    if (typeof RecommendationManager.init === 'function') {
      RecommendationManager.init();
    }
  }

  // تهيئة مدير الخيارات
  if (typeof OptionManager !== 'undefined') {
    console.log('تهيئة مدير الخيارات...');
    if (typeof OptionManager.init === 'function') {
      OptionManager.init();
    }
  }

  // تحديث العلاقات بين المديرين بعد التهيئة
  setTimeout(function() {
    console.log('تحديث العلاقات بين المديرين...');

    // تحديث الجداول المرتبطة بالوحدات
    if (typeof UnitManager !== 'undefined' && typeof UnitManager.updateRelatedTables === 'function') {
      console.log('تحديث الجداول المرتبطة بالوحدات...');
      UnitManager.updateRelatedTables();
    }

    // تحديث جدول المخزون
    if (typeof InventoryManager !== 'undefined' && typeof InventoryManager.renderInventoryTable === 'function') {
      console.log('تحديث جدول المخزون...');
      InventoryManager.renderInventoryTable();
    }

    // تحديث جدول التسعير
    if (typeof PricingManager !== 'undefined' && typeof PricingManager.renderPricingTable === 'function') {
      console.log('تحديث جدول التسعير...');
      PricingManager.renderPricingTable();
    }

    console.log('اكتملت تهيئة المديرين بنجاح.');
  }, 500);
}

/**
 * إعداد أحداث علامات التبويب
 */
function setupTabs() {
  // أحداث علامات التبويب الرئيسية
  $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
    const tabId = $(e.target).attr('href');
    console.log('تم تنشيط علامة التبويب:', tabId);

    // تحميل البيانات الخاصة بعلامة التبويب النشطة
    if (tabId === '#tab-movement' && typeof InventoryManager !== 'undefined') {
      console.log('تحميل حركات المخزون...');
      if (typeof InventoryManager.loadMovements === 'function') {
        InventoryManager.loadMovements();
      }
    } else if (tabId === '#tab-orders' && typeof InventoryManager !== 'undefined') {
      console.log('تحميل الطلبات...');
      if (typeof InventoryManager.loadOrders === 'function') {
        InventoryManager.loadOrders();
      }
    } else if (tabId === '#tab-inventory' && typeof InventoryManager !== 'undefined') {
      console.log('تحديث جدول المخزون...');
      if (typeof InventoryManager.renderInventoryTable === 'function') {
        InventoryManager.renderInventoryTable();
      }
    } else if (tabId === '#tab-pricing' && typeof PricingManager !== 'undefined') {
      console.log('تحديث جدول التسعير...');
      if (typeof PricingManager.renderPricingTable === 'function') {
        PricingManager.renderPricingTable();
      }
    } else if (tabId === '#tab-barcode' && typeof BarcodeManager !== 'undefined') {
      console.log('تحديث جدول الباركود...');
      if (typeof BarcodeManager.renderBarcodeTable === 'function') {
        BarcodeManager.renderBarcodeTable();
      }
    } else if (tabId === '#tab-units' && typeof UnitManager !== 'undefined') {
      console.log('تحديث مخطط الوحدات...');
      if (typeof UnitManager.updateUnitVisualDiagram === 'function') {
        UnitManager.updateUnitVisualDiagram();
      }
    } else if (tabId === '#tab-bundle' && typeof BundleManager !== 'undefined') {
      console.log('تحديث الحزم...');
      if (typeof BundleManager.initBundleTab === 'function') {
        BundleManager.initBundleTab();
      }
    } else if (tabId === '#tab-recommendation' && typeof RecommendationManager !== 'undefined') {
      console.log('تحديث التوصيات...');
      if (typeof RecommendationManager.loadRecommendations === 'function') {
        RecommendationManager.loadRecommendations();
      }
    } else if (tabId === '#tab-option') {
      console.log('تحديث الخيارات...');
      // تفعيل أدوات الاختيار في تاب الخيارات
      $('.date').datetimepicker({
        pickTime: false,
        format: 'YYYY-MM-DD'
      });
      $('.time').datetimepicker({
        pickDate: false,
        format: 'HH:mm'
      });
      $('.datetime').datetimepicker({
        pickDate: true,
        pickTime: true,
        format: 'YYYY-MM-DD HH:mm'
      });
      // تفعيل select2 إذا كان متاحاً
      if ($.fn.select2) {
        $('.select2').select2();
      }
    }

    // حفظ علامة التبويب النشطة في التخزين المحلي
    localStorage.setItem('activeProductTab', tabId);
  });

  // أحداث علامات التبويب الفرعية
  $('#bundle-tabs a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
    const tabId = $(e.target).attr('href');
    console.log('تم تنشيط علامة التبويب الفرعية:', tabId);

    if (tabId === '#tab-bundles-content' && typeof BundleManager !== 'undefined') {
      if (typeof BundleManager.initBundlesTab === 'function') {
        BundleManager.initBundlesTab();
      }
    } else if (tabId === '#tab-discounts-content' && typeof BundleManager !== 'undefined') {
      if (typeof BundleManager.initDiscountsTab === 'function') {
        BundleManager.initDiscountsTab();
      }
    }
  });

  // تحميل علامة التبويب المحددة من URL أو التخزين المحلي
  const activeTab = getUrlParam('active_tab') || localStorage.getItem('activeProductTab');
  if (activeTab) {
    $('a[href="' + activeTab + '"]').tab('show');
  }
}

/**
 * إعداد تأكيد الحذف
 */
function setupDeleteConfirmation() {
  // تأكيد حذف المنتج
  $('#button-delete').on('click', function(e) {
    e.preventDefault();

    if (confirm('{{ text_confirm }}')) {
      $('#form-product').attr('action', $(this).attr('href')).submit();
    }
  });
}

/**
 * إعداد الاستكمال التلقائي
 */
function setupAutocomplete() {
  // التصنيف
  $('input[name=\'category\']').autocomplete({
    'source': function(request, response) {
      $.ajax({
        url: 'index.php?route=catalog/category/autocomplete&user_token=' + user_token + '&filter_name=' +  encodeURIComponent(request),
        dataType: 'json',
        success: function(json) {
          response($.map(json, function(item) {
            return {
              label: item['name'],
              value: item['category_id']
            }
          }));
        }
      });
    },
    'select': function(item) {
      $('input[name=\'category\']').val('');

      $('#product-category' + item['value']).remove();

      $('#product-category').append('<div id="product-category' + item['value'] + '"><i class="fa fa-minus-circle"></i> ' + item['label'] + '<input type="hidden" name="product_category[]" value="' + item['value'] + '" /></div>');
    }
  });

  $('#product-category').on('click', '.fa-minus-circle', function() {
    $(this).parent().remove();
  });

  // المنتجات ذات الصلة
  $('input[name=\'related\']').autocomplete({
    'source': function(request, response) {
      $.ajax({
        url: 'index.php?route=catalog/product/autocomplete&user_token=' + user_token + '&filter_name=' +  encodeURIComponent(request),
        dataType: 'json',
        success: function(json) {
          response($.map(json, function(item) {
            return {
              label: item['name'],
              value: item['product_id']
            }
          }));
        }
      });
    },
    'select': function(item) {
      $('input[name=\'related\']').val('');

      $('#product-related' + item['value']).remove();

      $('#product-related').append('<div id="product-related' + item['value'] + '"><i class="fa fa-minus-circle"></i> ' + item['label'] + '<input type="hidden" name="product_related[]" value="' + item['value'] + '" /></div>');
    }
  });

  $('#product-related').on('click', '.fa-minus-circle', function() {
    $(this).parent().remove();
  });
}

/**
 * إعداد أدوات اختيار التاريخ
 */
function setupDatepickers() {
  $('.date').datetimepicker({
    pickTime: false,
    format: 'YYYY-MM-DD'
  });

  $('.time').datetimepicker({
    pickDate: false,
    format: 'HH:mm'
  });

  $('.datetime').datetimepicker({
    pickDate: true,
    pickTime: true,
    format: 'YYYY-MM-DD HH:mm'
  });
}

/**
 * إعداد وظائف متنوعة
 */
function setupMiscellaneous() {
  // محرر summernote
  $('[data-toggle=\'summernote\']').summernote({
    height: 300,
    lang: '{{ summernote }}'
  });

  // tooltip
  $('[data-toggle=\'tooltip\']').tooltip({
    container: 'body',
    html: true
  });

  // تفعيل select2 إذا كان متاحاً
  if ($.fn.select2) {
    $('.select2').select2();
  }
}

/**
 * الحصول على قيمة معلمة من URL
 * @param {string} name - اسم المعلمة
 * @returns {string|null} - قيمة المعلمة أو null إذا لم تكن موجودة
 */
function getUrlParam(name) {
  const results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
  if (results && results.length > 1) {
    return decodeURIComponent(results[1]);
  }
  return null;
}

/**
 * إضافة صورة إضافية
 */
function addImage() {
  let html = '<tr id="image-row' + image_row + '">';
  html += '  <td class="text-center"><a href="" id="thumb-image' + image_row + '" data-toggle="image" class="img-thumbnail"><img src="{{ placeholder }}" alt="" title="" data-placeholder="{{ placeholder }}" /></a><input type="hidden" name="product_image[' + image_row + '][image]" value="" id="input-image' + image_row + '" /></td>';
  html += '  <td class="text-center"><input type="text" name="product_image[' + image_row + '][sort_order]" value="0" placeholder="{{ entry_sort_order }}" class="form-control" /></td>';
  html += '  <td class="text-center"><button type="button" onclick="$(\'#image-row' + image_row + '\').remove();" data-toggle="tooltip" title="{{ button_remove }}" class="btn btn-danger"><i class="fa fa-minus-circle"></i></button></td>';
  html += '</tr>';

  $('#images tbody').append(html);

  image_row++;
}

/**
 * إضافة خيار
 */
function addOption() {
  html = '<li><input type="text" name="option" value="" placeholder="{{ entry_option }}" id="input-option" class="form-control" /></li>';

  $('#option').append(html);

  $('input[name=\'option\']').autocomplete({
    'source': function(request, response) {
      $.ajax({
        url: 'index.php?route=catalog/option/autocomplete&user_token=' + user_token + '&filter_name=' +  encodeURIComponent(request),
        dataType: 'json',
        success: function(json) {
          response($.map(json, function(item) {
            return {
              category: item['category'],
              label: item['name'],
              value: item['option_id'],
              type: item['type'],
              option_value: item['option_value']
            }
          }));
        }
      });
    },
    'select': function(item) {
      // إنشاء HTML للخيار
      var optionHTML = createOptionHTML(item);

      $('#tab-option .tab-content').append(optionHTML);

      // إضافة علامة تبويب للخيار
      $('#option li:last-child').before('<li><a href="#tab-option' + option_row + '" data-toggle="tab"><i class="fa fa-minus-circle" onclick="$(\'a[href=\\\'#tab-option' + option_row + '\\\']\').parent().remove(); $(\'#tab-option' + option_row + '\').remove(); $(\'#option a:first\').tab(\'show\');"></i> ' + item['label'] + '</a></li>');

      $('#option a[href="#tab-option' + option_row + '"]').tab('show');

      // تفعيل أدوات الاختيار
      $('.date').datetimepicker({
        pickTime: false
      });

      $('.time').datetimepicker({
        pickDate: false
      });

      $('.datetime').datetimepicker({
        pickDate: true,
        pickTime: true
      });

      option_row++;

      $('input[name=\'option\']').val('');
    }
  });
}

/**
 * إنشاء HTML للخيار
 */
function createOptionHTML(item) {
  var html = '<div class="tab-pane" id="tab-option' + option_row + '">';
  html += '  <input type="hidden" name="product_option[' + option_row + '][product_option_id]" value="" />';
  html += '  <input type="hidden" name="product_option[' + option_row + '][name]" value="' + item['label'] + '" />';
  html += '  <input type="hidden" name="product_option[' + option_row + '][option_id]" value="' + item['value'] + '" />';
  html += '  <input type="hidden" name="product_option[' + option_row + '][type]" value="' + item['type'] + '" />';

  // إضافة الإعدادات العامة للخيار
  html += '  <div class="form-group">';
  html += '    <label class="col-sm-2 control-label" for="input-required' + option_row + '">{{ entry_required }}</label>';
  html += '    <div class="col-sm-10">';
  html += '      <select name="product_option[' + option_row + '][required]" id="input-required' + option_row + '" class="form-control">';
  html += '        <option value="1">{{ text_yes }}</option>';
  html += '        <option value="0" selected="selected">{{ text_no }}</option>';
  html += '      </select>';
  html += '    </div>';
  html += '  </div>';

  // إضافة حقول خاصة بكل نوع خيار
  if (item['type'] == 'text') {
    html += textOptionHTML(option_row);
  } else if (item['type'] == 'textarea') {
    html += textareaOptionHTML(option_row);
  } else if (item['type'] == 'file') {
    html += fileOptionHTML(option_row);
  } else if (item['type'] == 'date') {
    html += dateOptionHTML(option_row);
  } else if (item['type'] == 'time') {
    html += timeOptionHTML(option_row);
  } else if (item['type'] == 'datetime') {
    html += datetimeOptionHTML(option_row);
  } else if (item['type'] == 'select' || item['type'] == 'radio' || item['type'] == 'checkbox' || item['type'] == 'image') {
    html += selectOptionHTML(option_row, item);
  }

  html += '</div>';

  return html;
}

/**
 * إنشاء HTML لخيار النص
 */
function textOptionHTML(option_row) {
  var html = '  <div class="form-group">';
  html += '    <label class="col-sm-2 control-label" for="input-value' + option_row + '">{{ entry_option_value }}</label>';
  html += '    <div class="col-sm-10">';
  html += '      <input type="text" name="product_option[' + option_row + '][value]" value="" placeholder="{{ entry_option_value }}" id="input-value' + option_row + '" class="form-control" />';
  html += '    </div>';
  html += '  </div>';

  return html;
}

/**
 * إنشاء HTML لخيار منطقة النص
 */
function textareaOptionHTML(option_row) {
  var html = '  <div class="form-group">';
  html += '    <label class="col-sm-2 control-label" for="input-value' + option_row + '">{{ entry_option_value }}</label>';
  html += '    <div class="col-sm-10">';
  html += '      <textarea name="product_option[' + option_row + '][value]" rows="5" placeholder="{{ entry_option_value }}" id="input-value' + option_row + '" class="form-control"></textarea>';
  html += '    </div>';
  html += '  </div>';

  return html;
}

/**
 * إنشاء HTML لخيار الملف
 */
function fileOptionHTML(option_row) {
  var html = '  <div class="form-group" style="display: none;">';
  html += '    <label class="col-sm-2 control-label" for="input-value' + option_row + '">{{ entry_option_value }}</label>';
  html += '    <div class="col-sm-10">';
  html += '      <input type="text" name="product_option[' + option_row + '][value]" value="" placeholder="{{ entry_option_value }}" id="input-value' + option_row + '" class="form-control" />';
  html += '    </div>';
  html += '  </div>';

  return html;
}

/**
 * إنشاء HTML لخيار التاريخ
 */
function dateOptionHTML(option_row) {
  var html = '  <div class="form-group">';
  html += '    <label class="col-sm-2 control-label" for="input-value' + option_row + '">{{ entry_option_value }}</label>';
  html += '    <div class="col-sm-10">';
  html += '      <div class="input-group date">';
  html += '        <input type="text" name="product_option[' + option_row + '][value]" value="" placeholder="{{ entry_option_value }}" data-date-format="YYYY-MM-DD" id="input-value' + option_row + '" class="form-control" />';
  html += '        <span class="input-group-btn">';
  html += '          <button class="btn btn-default" type="button"><i class="fa fa-calendar"></i></button>';
  html += '        </span>';
  html += '      </div>';
  html += '    </div>';
  html += '  </div>';

  return html;
}

/**
 * إنشاء HTML لخيار الوقت
 */
function timeOptionHTML(option_row) {
  var html = '  <div class="form-group">';
  html += '    <label class="col-sm-2 control-label" for="input-value' + option_row + '">{{ entry_option_value }}</label>';
  html += '    <div class="col-sm-10">';
  html += '      <div class="input-group time">';
  html += '        <input type="text" name="product_option[' + option_row + '][value]" value="" placeholder="{{ entry_option_value }}" data-date-format="HH:mm" id="input-value' + option_row + '" class="form-control" />';
  html += '        <span class="input-group-btn">';
  html += '          <button type="button" class="btn btn-default"><i class="fa fa-calendar"></i></button>';
  html += '        </span>';
  html += '      </div>';
  html += '    </div>';
  html += '  </div>';

  return html;
}

/**
 * إنشاء HTML لخيار التاريخ والوقت
 */
function datetimeOptionHTML(option_row) {
  var html = '  <div class="form-group">';
  html += '    <label class="col-sm-2 control-label" for="input-value' + option_row + '">{{ entry_option_value }}</label>';
  html += '    <div class="col-sm-10">';
  html += '      <div class="input-group datetime">';
  html += '        <input type="text" name="product_option[' + option_row + '][value]" value="" placeholder="{{ entry_option_value }}" data-date-format="YYYY-MM-DD HH:mm" id="input-value' + option_row + '" class="form-control" />';
  html += '        <span class="input-group-btn">';
  html += '          <button type="button" class="btn btn-default"><i class="fa fa-calendar"></i></button>';
  html += '        </span>';
  html += '      </div>';
  html += '    </div>';
  html += '  </div>';

  return html;
}

/**
 * إنشاء HTML لخيار القائمة أو الصور
 */
function selectOptionHTML(option_row, item) {
  var html = '<div class="table-responsive">';
  html += '  <table id="option-value' + option_row + '" class="table table-striped table-bordered table-hover">';
  html += '    <thead>';
  html += '      <tr>';
  html += '        <th class="text-center">{{ entry_option_value }}</th>';
  html += '        <th class="text-center">{{ entry_quantity }}</th>';
  html += '        <th class="text-center">{{ entry_subtract }}</th>';
  html += '        <th class="text-center">{{ entry_price }}</th>';
  html += '        <th class="text-center">{{ entry_option_points }}</th>';
  html += '        <th class="text-center">{{ entry_weight }}</th>';
  html += '        <th class="text-center" width="90">{{ entry_action }}</th>';
  html += '      </tr>';
  html += '    </thead>';
  html += '    <tbody></tbody>';
  html += '    <tfoot>';
  html += '      <tr>';
  html += '        <td colspan="6"></td>';
  html += '        <td class="text-center"><button type="button" onclick="addOptionValue(' + option_row + ');" data-toggle="tooltip" title="{{ button_option_value_add }}" class="btn btn-primary"><i class="fa fa-plus-circle"></i></button></td>';
  html += '      </tr>';
  html += '    </tfoot>';
  html += '  </table>';
  html += '</div>';

  html += '  <select id="option-values' + option_row + '" style="display: none;">';

  for (var i = 0; i < item['option_value'].length; i++) {
    html += '<option value="' + item['option_value'][i]['option_value_id'] + '">' + item['option_value'][i]['name'] + '</option>';
  }

  html += '  </select>';

  return html;
}

/**
 * إضافة قيمة لخيار
 */
function addOptionValue(option_row) {
  var html = '<tr id="option-value-row' + option_value_row + '">';
  html += '  <td class="text-center" style="min-width: 150px;text-align: center;"><select name="product_option[' + option_row + '][product_option_value][' + option_value_row + '][option_value_id]" class="form-control">';
  html += $('#option-values' + option_row).html();
  html += '  </select><input type="hidden" name="product_option[' + option_row + '][product_option_value][' + option_value_row + '][product_option_value_id]" value="" /></td>';
  html += '  <td class="text-center"><input type="number" name="product_option[' + option_row + '][product_option_value][' + option_value_row + '][quantity]" value="1" placeholder="{{ entry_quantity }}" class="form-control" min="0" step="1"/></td>';
  html += '  <td class="text-center"><select name="product_option[' + option_row + '][product_option_value][' + option_value_row + '][subtract]" class="form-control">';
  html += '    <option value="1">{{ text_yes }}</option>';
  html += '    <option value="0">{{ text_no }}</option>';
  html += '  </select></td>';
  html += '  <td class="text-center"><div class="input-group">';
  html += '    <select name="product_option[' + option_row + '][product_option_value][' + option_value_row + '][price_prefix]" class="input-group-addon">';
  html += '      <option value="+">+</option>';
  html += '      <option value="-">-</option>';
  html += '      <option value="=">=</option>';
  html += '    </select>';
  html += '    <input type="number" name="product_option[' + option_row + '][product_option_value][' + option_value_row + '][price]" value="0" placeholder="{{ entry_price }}" class="form-control" min="0" step="0.01"/></div></td>';
  html += '  <td class="text-center"><div class="input-group">';
  html += '    <select name="product_option[' + option_row + '][product_option_value][' + option_value_row + '][points_prefix]" class="input-group-addon">';
  html += '      <option value="+">+</option>';
  html += '      <option value="-">-</option>';
  html += '    </select>';
  html += '    <input type="number" name="product_option[' + option_row + '][product_option_value][' + option_value_row + '][points]" value="0" placeholder="{{ entry_points }}" class="form-control" min="0" step="1"/></div></td>';
  html += '  <td class="text-center"><div class="input-group">';
  html += '    <select name="product_option[' + option_row + '][product_option_value][' + option_value_row + '][weight_prefix]" class="input-group-addon">';
  html += '      <option value="+">+</option>';
  html += '      <option value="-">-</option>';
  html += '    </select>';
  html += '    <input type="number" name="product_option[' + option_row + '][product_option_value][' + option_value_row + '][weight]" value="0" placeholder="{{ entry_weight }}" class="form-control" min="0" step="0.01"/></div></td>';
  html += '  <td class="text-center"><button type="button" onclick="$(this).tooltip(\'destroy\');$(\'#option-value-row' + option_value_row + '\').remove();" data-toggle="tooltip" title="{{ button_remove }}" class="btn btn-danger"><i class="fa fa-minus-circle"></i></button></td>';
  html += '</tr>';

  $('#option-value' + option_row + ' tbody').append(html);
  $('[data-toggle=\'tooltip\']').tooltip();

  option_value_row++;
}