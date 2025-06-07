/**
 * RelationsManager.js - مدير علاقات المنتج (البيع الإضافي والمتقاطع)
 */

const RelationsManager = (function() {
  /**
   * تهيئة مدير العلاقات
   */
  function init() {
    // تهيئة عدادات الصفوف
    upsell_row = $('#upsell tbody tr').length || 0;
    cross_sell_row = $('#cross-sell tbody tr').length || 0;
    
    // إضافة أزرار لإضافة علاقات
    $('#add-upsell').off('click').on('click', function() {
      addUpsell();
    });
    
    $('#add-cross-sell').off('click').on('click', function() {
      addCrossSell();
    });
    
    // إعداد علاقات المنتج (الفئات، المرشحات، إلخ)
    setupProductRelations();
    
    // تهيئة علاقات الاستكمال التلقائي
    initializeAutocompletes();
  }
  
  /**
   * تهيئة علاقات الاستكمال التلقائي
   */
  function initializeAutocompletes() {
    // استكمال تلقائي للحزمة
    for (let i = 0; i < bundle_row; i++) {
      initBundleAutocomplete(i);
    }
    
    // استكمال تلقائي للبيع الإضافي والبيع المتقاطع
    for (let i = 0; i < upsell_row; i++) {
      initUpsellAutocomplete(i);
    }
    
    for (let i = 0; i < cross_sell_row; i++) {
      initCrossSellAutocomplete(i);
    }
  }
  
  /**
   * إضافة بيع إضافي
   */
  function addUpsell() {
    let html = `
      <tr id="upsell-row${upsell_row}">
        <td class="text-center">
          <input type="text" name="product_upsell[${upsell_row}][name]" value="" placeholder="{{ entry_product }}" class="form-control" />
          <input type="hidden" name="product_upsell[${upsell_row}][related_product_id]" value="" />
        </td>
        <td class="text-center">
          <select name="product_upsell[${upsell_row}][unit_id]" class="form-control">
          </select>
        </td>
        <td class="text-center">
          <select name="product_upsell[${upsell_row}][customer_group_id]" class="form-control">
            <option value="0">{{ text_all_customers }}</option>
            {% for customer_group in customer_groups %}
            <option value="{{ customer_group.customer_group_id }}">{{ customer_group.name }}</option>
            {% endfor %}
          </select>
        </td>
        <td class="text-center">
          <input type="number" name="product_upsell[${upsell_row}][priority]" value="0" placeholder="{{ entry_priority }}" class="form-control" min="0" step="1" />
        </td>
        <td class="text-center">
          <select name="product_upsell[${upsell_row}][discount_type]" class="form-control">
            <option value="">{{ text_none }}</option>
            <option value="percentage">{{ text_percentage }}</option>
            <option value="fixed">{{ text_fixed }}</option>
          </select>
        </td>
        <td class="text-center">
          <input type="number" name="product_upsell[${upsell_row}][discount_value]" value="0" placeholder="{{ entry_discount_value }}" class="form-control" min="0" step="0.01" />
        </td>
        <td class="text-center">
          <button type="button" onclick="RelationsManager.removeUpsell(${upsell_row});" data-toggle="tooltip" title="{{ button_remove }}" class="btn btn-danger">
            <i class="fa fa-minus-circle"></i>
          </button>
        </td>
      </tr>
    `;

    $('#upsell tbody').append(html);

    initUpsellAutocomplete(upsell_row);

    upsell_row++;
  }
  
  /**
   * إزالة بيع إضافي
   */
  function removeUpsell(row) {
    $('#upsell-row' + row).remove();
  }
  
  /**
   * تهيئة الاستكمال التلقائي للبيع الإضافي
   */
  function initUpsellAutocomplete(row) {
    $('input[name="product_upsell[' + row + '][name]"]').autocomplete({
      'source': function(request, response) {
        $.ajax({
          url: 'index.php?route=catalog/product/bundleAutocomplete&user_token=' + user_token + '&filter_name=' + encodeURIComponent(request),
          dataType: 'json',
          success: function(json) {
            response($.map(json, function(item) {
              return {
                label: item['name'],
                value: item['product_id'],
                units: item['product'] && item['product']['units'] ? item['product']['units'] : []
              }
            }));
          }
        });
      },
      'select': function(item) {
        $('input[name="product_upsell[' + row + '][name]"]').val(item['label']);
        $('input[name="product_upsell[' + row + '][related_product_id]"]').val(item['value']);
        
        const unitSelect = $('select[name="product_upsell[' + row + '][unit_id]"]');
        unitSelect.empty();
        
        if (item.units && item.units.length > 0) {
          item.units.forEach(function(unit) {
            unitSelect.append(`<option value="${unit.unit_id}">${unit.unit_name}</option>`);
          });
        } else {
          // الاحتياطي لتحميل الوحدات عبر ajax إذا لم تكن متوفرة في بيانات العنصر
          loadProductUnits(item.value, unitSelect);
        }
      }
    });
  }
  
  /**
   * إضافة بيع متقاطع
   */
  function addCrossSell() {
    let html = `
      <tr id="cross-sell-row${cross_sell_row}">
        <td class="text-center">
          <input type="text" name="product_cross_sell[${cross_sell_row}][name]" value="" placeholder="{{ entry_product }}" class="form-control" />
          <input type="hidden" name="product_cross_sell[${cross_sell_row}][related_product_id]" value="" />
        </td>
        <td class="text-center">
          <select name="product_cross_sell[${cross_sell_row}][unit_id]" class="form-control">
          </select>
        </td>
        <td class="text-center">
          <select name="product_cross_sell[${cross_sell_row}][customer_group_id]" class="form-control">
            <option value="0">{{ text_all_customers }}</option>
            {% for customer_group in customer_groups %}
            <option value="{{ customer_group.customer_group_id }}">{{ customer_group.name }}</option>
            {% endfor %}
          </select>
        </td>
        <td class="text-center">
          <input type="number" name="product_cross_sell[${cross_sell_row}][priority]" value="0" placeholder="{{ entry_priority }}" class="form-control" min="0" step="1" />
        </td>
        <td class="text-center">
          <select name="product_cross_sell[${cross_sell_row}][discount_type]" class="form-control">
            <option value="">{{ text_none }}</option>
            <option value="percentage">{{ text_percentage }}</option>
            <option value="fixed">{{ text_fixed }}</option>
          </select>
        </td>
        <td class="text-center">
          <input type="number" name="product_cross_sell[${cross_sell_row}][discount_value]" value="0" placeholder="{{ entry_discount_value }}" class="form-control" min="0" step="0.01" />
        </td>
        <td class="text-center">
          <button type="button" onclick="RelationsManager.removeCrossSell(${cross_sell_row});" data-toggle="tooltip" title="{{ button_remove }}" class="btn btn-danger">
            <i class="fa fa-minus-circle"></i>
          </button>
        </td>
      </tr>
    `;

    $('#cross-sell tbody').append(html);

    initCrossSellAutocomplete(cross_sell_row);

    cross_sell_row++;
  }
  
  /**
   * إزالة بيع متقاطع
   */
  function removeCrossSell(row) {
    $('#cross-sell-row' + row).remove();
  }
  
  /**
   * تهيئة الاستكمال التلقائي للبيع المتقاطع
   */
  function initCrossSellAutocomplete(row) {
    $('input[name="product_cross_sell[' + row + '][name]"]').autocomplete({
      'source': function(request, response) {
        $.ajax({
          url: 'index.php?route=catalog/product/bundleAutocomplete&user_token=' + user_token + '&filter_name=' + encodeURIComponent(request),
          dataType: 'json',
          success: function(json) {
            response($.map(json, function(item) {
              return {
                label: item['name'],
                value: item['product_id'],
                units: item['product'] && item['product']['units'] ? item['product']['units'] : []
              }
            }));
          }
        });
      },
      'select': function(item) {
        $('input[name="product_cross_sell[' + row + '][name]"]').val(item['label']);
        $('input[name="product_cross_sell[' + row + '][related_product_id]"]').val(item['value']);
        
        const unitSelect = $('select[name="product_cross_sell[' + row + '][unit_id]"]');
        unitSelect.empty();
        
        if (item.units && item.units.length > 0) {
          item.units.forEach(function(unit) {
            unitSelect.append(`<option value="${unit.unit_id}">${unit.unit_name}</option>`);
          });
        } else {
          // الاحتياطي لتحميل الوحدات عبر ajax إذا لم تكن متوفرة في بيانات العنصر
          loadProductUnits(item.value, unitSelect);
        }
      }
    });
  }
  
  /**
   * تهيئة الاستكمال التلقائي للحزمة
   */
  function initBundleAutocomplete(row) {
    $('input[name="product_bundle[' + row + '][product]"]').autocomplete({
      'source': function(request, response) {
        $.ajax({
          url: 'index.php?route=catalog/product/bundleAutocomplete&user_token=' + user_token + '&filter_name=' + encodeURIComponent(request),
          dataType: 'json',
          success: function(json) {
            response($.map(json, function(item) {
              return {
                label: item['name'],
                value: item['product_id'],
                units: item['product'] && item['product']['units'] ? item['product']['units'] : []
              }
            }));
          }
        });
      },
      'select': function(item) {
        $('input[name="product_bundle[' + row + '][product]"]').val('');
        
        let unitOptions = '';
        if (item.units && item.units.length > 0) {
          item.units.forEach(function(unit) {
            unitOptions += `<option value="${unit.unit_id}">${unit.unit_name}</option>`;
          });
        } else {
          unitOptions = '<option value="">{{ text_no_units }}</option>';
        }
        
        $('#bundle-products' + row + ' tbody').append(`
          <tr>
            <td>${item.label}<input type="hidden" name="product_bundle[${row}][bundle_item][${item.value}][product_id]" value="${item.value}" /></td>
            <td><input type="number" name="product_bundle[${row}][bundle_item][${item.value}][quantity]" value="1" class="form-control" min="1" step="1" /></td>
            <td><select name="product_bundle[${row}][bundle_item][${item.value}][unit_id]" class="form-control">${unitOptions}</select></td>
            <td><div class="checkbox"><label><input type="checkbox" name="product_bundle[${row}][bundle_item][${item.value}][is_free]" value="1" /></label></div></td>
            <td><button type="button" onclick="$(this).closest('tr').remove();" class="btn btn-danger btn-sm"><i class="fa fa-trash"></i></button></td>
          </tr>
        `);
      }
    });
  }
  
  /**
   * إعداد علاقات المنتج
   */
  function setupProductRelations() {
    // فئة الاستكمال التلقائي
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
        
        $('#product-category').append(`
          <div id="product-category${item['value']}">
            <i class="fa fa-minus-circle"></i> ${item['label']}
            <input type="hidden" name="product_category[]" value="${item['value']}" />
          </div>
        `);
      }
    });
    
    $('#product-category').off('click', '.fa-minus-circle').on('click', '.fa-minus-circle', function() {
      $(this).parent().remove();
    });
    
    // مرشح الاستكمال التلقائي
    $('input[name=\'filter\']').autocomplete({
      'source': function(request, response) {
        $.ajax({
          url: 'index.php?route=catalog/filter/autocomplete&user_token=' + user_token + '&filter_name=' +  encodeURIComponent(request),
          dataType: 'json',
          success: function(json) {
            response($.map(json, function(item) {
              return {
                label: item['name'],
                value: item['filter_id']
              }
            }));
          }
        });
      },
      'select': function(item) {
        $('input[name=\'filter\']').val('');
        
        $('#product-filter' + item['value']).remove();
        
        $('#product-filter').append(`
          <div id="product-filter${item['value']}">
            <i class="fa fa-minus-circle"></i> ${item['label']}
            <input type="hidden" name="product_filter[]" value="${item['value']}" />
          </div>
        `);
      }
    });
    
    $('#product-filter').off('click', '.fa-minus-circle').on('click', '.fa-minus-circle', function() {
      $(this).parent().remove();
    });
    
    // منتج ذو صلة الاستكمال التلقائي
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
        
        $('#product-related').append(`
          <div id="product-related${item['value']}">
            <i class="fa fa-minus-circle"></i> ${item['label']}
            <input type="hidden" name="product_related[]" value="${item['value']}" />
          </div>
        `);
      }
    });
    
    $('#product-related').off('click', '.fa-minus-circle').on('click', '.fa-minus-circle', function() {
      $(this).parent().remove();
    });
    
    // الشركة المصنعة الاستكمال التلقائي
    $('input[name=\'manufacturer\']').autocomplete({
      'source': function(request, response) {
        $.ajax({
          url: 'index.php?route=catalog/manufacturer/autocomplete&user_token=' + user_token + '&filter_name=' +  encodeURIComponent(request),
          dataType: 'json',
          success: function(json) {
            json.unshift({
              manufacturer_id: 0,
              name: '{{ text_none }}'
            });
            
            response($.map(json, function(item) {
              return {
                label: item['name'],
                value: item['manufacturer_id']
              }
            }));
          }
        });
      },
      'select': function(item) {
        $('input[name=\'manufacturer\']').val(item['label']);
        $('input[name=\'manufacturer_id\']').val(item['value']);
      }
    });
  }
  
  /**
   * تحميل وحدات المنتج للقائمة المنسدلة
   */
  function loadProductUnits(productId, selectElement) {
    $.ajax({
      url: 'index.php?route=catalog/product/getProductUnits&user_token=' + user_token + '&product_id=' + productId,
      dataType: 'json',
      success: function(json) {
        let html = '';
        if (json && json.length > 0) {
          json.forEach(function(unit) {
            html += `<option value="${unit.unit_id}">${unit.unit_name}</option>`;
          });
        } else {
          html = '<option value="">{{ text_no_units }}</option>';
        }
        $(selectElement).html(html);
      },
      error: function(xhr, ajaxOptions, thrownError) {
        $(selectElement).html('<option value="">{{ text_no_units }}</option>');
      }
    });
  }
  
  // الواجهة العامة
  return {
    init,
    addUpsell,
    removeUpsell,
    addCrossSell,
    removeCrossSell,
    initBundleAutocomplete,
    initUpsellAutocomplete,
    initCrossSellAutocomplete,
    loadProductUnits,
    setupProductRelations
  };
})();