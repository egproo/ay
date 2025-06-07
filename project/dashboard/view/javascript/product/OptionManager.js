/**
 * OptionManager.js - مدير خيارات المنتج
 */

const OptionManager = (function() {
  /**
   * تهيئة مدير الخيارات
   */
  function init() {
    // إعداد مستمعي الأحداث
    setupEvents();
    
    // تحديث عداد الخيارات
    option_row = $('#option > li').length - 1 || 0;
    
    // تحديث عداد قيم الخيارات
    let maxValueRow = 0;
    $('tr[id^="option-value-row"]').each(function() {
      const rowId = parseInt($(this).attr('id').replace('option-value-row', ''));
      if (rowId > maxValueRow) {
        maxValueRow = rowId;
      }
    });
    option_value_row = maxValueRow + 1 || 0;
  }

  /**
   * إعداد مستمعي الأحداث
   */
  function setupEvents() {
    // استكمال تلقائي للخيار
    $('input[name=\'option\']').autocomplete({
      'source': function(request, response) {
        // الحصول على الوحدة المحددة حاليًا
        let currentUnitId = '';
        if (UnitManager && UnitManager.getCurrentUnits && UnitManager.getCurrentUnits().length > 0) {
          // محاولة استخدام الوحدة الأساسية أولاً
          const baseUnitElement = $('.unit-type').filter(function() { 
            return $(this).val() === 'base'; 
          });
          
          if (baseUnitElement.length > 0) {
            currentUnitId = baseUnitElement.closest('tr').find('.unit-select').val();
          } else if (UnitManager.getCurrentUnits().length > 0) {
            currentUnitId = UnitManager.getCurrentUnits()[0];
          }
        }

        $.ajax({
          url: 'index.php?route=catalog/option/autocomplete&user_token=' + user_token + 
              '&filter_name=' + encodeURIComponent(request) + 
              '&filter_unit_id=' + currentUnitId,
          dataType: 'json',
          success: function(json) {
            response($.map(json, function(item) {
              // إضافة اسم الوحدة إلى التسمية إذا كان متاحًا
              let labelText = item['name'];
              if (item['unit_name']) {
                labelText += ' (' + item['unit_name'] + ')';
              }
              
              return {
                category: item['category'],
                label: labelText,
                name: item['name'],  // الاحتفاظ بالاسم الأصلي بدون اسم الوحدة
                value: item['option_id'],
                type: item['type'],
                unit_id: item['unit_id'],
                unit_name: item['unit_name'],
                option_value: item['option_value']
              }
            }));
          }
        });
      },
      'select': function(item) {
        html  = '<div class="tab-pane" id="tab-option' + option_row + '">';
        html += '  <input type="hidden" name="product_option[' + option_row + '][product_option_id]" value="" />';
        html += '  <input type="hidden" name="product_option[' + option_row + '][name]" value="' + item['name'] + '" />';
        html += '  <input type="hidden" name="product_option[' + option_row + '][option_id]" value="' + item['value'] + '" />';
        html += '  <input type="hidden" name="product_option[' + option_row + '][type]" value="' + item['type'] + '" />';
        
        html += '  <div class="row" style="padding-bottom:10px">';                    
        html += '    <div class="form-group-inline">';
        html += '      <label class="col-sm-1 control-label" for="input-required' + option_row + '">{{ entry_required }}</label>';
        html += '      <div class="col-sm-2">';
        html += '        <select name="product_option[' + option_row + '][required]" id="input-required' + option_row + '" class="form-control">';
        html += '          <option value="1">{{ text_yes }}</option>';
        html += '          <option value="0">{{ text_no }}</option>';
        html += '        </select>';
        html += '      </div>';
        html += '    </div>';
        
        html += '    <div class="form-group-inline">';
        html += '      <label class="col-sm-2 control-label" for="input-unit' + option_row + '">{{ entry_unit }}</label>';
        html += '      <div class="col-sm-5">';
        html += '        <select name="product_option[' + option_row + '][unit_id]" id="input-unit' + option_row + '" class="form-control select2">';
        
        // إضافة خيارات الوحدات
        const currentUnits = UnitManager.getCurrentUnits();
        let hasSelectedUnit = false;
        
        currentUnits.forEach(function(unitId) {
          if (unitId) {
            // ضع الوحدة المرتبطة بالخيار كافتراضية إذا كانت متاحة
            const selected = (item['unit_id'] && item['unit_id'] == unitId) ? ' selected="selected"' : '';
            if (item['unit_id'] && item['unit_id'] == unitId) hasSelectedUnit = true;
            
            html += '<option value="' + unitId + '"' + selected + '>' + UnitManager.getUnitName(unitId) + '</option>';
          }
        });
        
        // إذا لم تكن الوحدة المرتبطة بالخيار موجودة في الخيارات، حدد الوحدة الأساسية
        if (!hasSelectedUnit && currentUnits.length > 0) {
          const baseUnitElement = $('.unit-type').filter(function() { 
            return $(this).val() === 'base'; 
          });
          
          if (baseUnitElement.length > 0) {
            const baseUnit = baseUnitElement.closest('tr').find('.unit-select').val();
            if (baseUnit) {
              // حدد الوحدة الأساسية
              html = html.replace('value="' + baseUnit + '"', 'value="' + baseUnit + '" selected="selected"');
            }
          }
        }
        
        html += '        </select>';
        html += '      </div>';
        html += '    </div>';
        html += '  </div>';
        
        // باقي HTML للخيارات حسب النوع
        if (item['type'] == 'text') {
          html += '  <div class="form-group">';
          html += '    <label class="col-sm-1 control-label" for="input-value' + option_row + '">{{ entry_option_value }}</label>';
          html += '    <div class="col-sm-8">';
          html += '      <input type="text" name="product_option[' + option_row + '][value]" value="" placeholder="{{ entry_option_value }}" id="input-value' + option_row + '" class="form-control" />';
          html += '    </div>';
          html += '  </div>';
        }
        
        if (item['type'] == 'textarea') {
          html += '  <div class="form-group">';
          html += '    <label class="col-sm-2 control-label" for="input-value' + option_row + '">{{ entry_option_value }}</label>';
          html += '    <div class="col-sm-10">';
          html += '      <textarea name="product_option[' + option_row + '][value]" rows="5" placeholder="{{ entry_option_value }}" id="input-value' + option_row + '" class="form-control"></textarea>';
          html += '    </div>';
          html += '  </div>';
        }
        
        if (item['type'] == 'file') {
          html += '  <div class="form-group" style="display: none;">';
          html += '    <label class="col-sm-2 control-label" for="input-value' + option_row + '">{{ entry_option_value }}</label>';
          html += '    <div class="col-sm-10">';
          html += '      <input type="text" name="product_option[' + option_row + '][value]" value="" placeholder="{{ entry_option_value }}" id="input-value' + option_row + '" class="form-control" />';
          html += '    </div>';
          html += '  </div>';
        }
        
        if (item['type'] == 'date') {
          html += '  <div class="form-group">';
          html += '    <label class="col-sm-2 control-label" for="input-value' + option_row + '">{{ entry_option_value }}</label>';
          html += '    <div class="col-sm-3">';
          html += '      <div class="input-group date">';
          html += '        <input type="text" name="product_option[' + option_row + '][value]" value="" placeholder="{{ entry_option_value }}" data-date-format="YYYY-MM-DD" id="input-value' + option_row + '" class="form-control" />';
          html += '        <span class="input-group-btn">';
          html += '          <button class="btn btn-default" type="button"><i class="fa fa-calendar"></i></button>';
          html += '        </span>';
          html += '      </div>';
          html += '    </div>';
          html += '  </div>';
        }
        
        if (item['type'] == 'time') {
          html += '  <div class="form-group">';
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
        }
        
        if (item['type'] == 'datetime') {
          html += '  <div class="form-group">';
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
        }
        
        if (item['type'] == 'select' || item['type'] == 'radio' || item['type'] == 'checkbox' || item['type'] == 'image') {
          html += '<div class="table-responsive">';
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
          html += '        <td class="text-center"><button type="button" onclick="OptionManager.addOptionValue(' + option_row + ');" data-toggle="tooltip" title="{{ button_option_value_add }}" class="btn btn-primary"><i class="fa fa-plus-circle"></i></button></td>';
          html += '      </tr>';
          html += '    </tfoot>';
          html += '  </table>';
          html += '</div>';
          
          html += '  <select id="option-values' + option_row + '" style="display: none;">';
          
          for (var i = 0; i < item['option_value'].length; i++) {
            html += '<option value="' + item['option_value'][i]['option_value_id'] + '">' + item['option_value'][i]['name'] + '</option>';
          }
          
          html += '  </select>';
        }
        
        html += '</div>';
        
        // عرض اسم الوحدة مع اسم الخيار في علامة التبويب
        let optionLabel = item['name'];
        if (item['unit_name']) {
          optionLabel += ' (' + item['unit_name'] + ')';
        } else {
          // إذا لم تكن وحدة الخيار متاحة، حاول العثور على الوحدة من قائمة الوحدات المتاحة
          const selectedUnitId = currentUnits.length > 0 ? currentUnits[0] : null;
          if (selectedUnitId) {
            optionLabel += ' (' + UnitManager.getUnitName(selectedUnitId) + ')';
          }
        }
        
        $('#option').append('<li><a href="#tab-option' + option_row + '" data-toggle="tab" data-option-id="' + option_row + '"><i class="fa fa-minus-circle" onclick="$(\'a[href=\\\'#tab-option' + option_row + '\\\']\').parent().remove(); $(\'#tab-option' + option_row + '\').remove(); $(\'#option a:first\').tab(\'show\');"></i> ' + optionLabel + '</a></li>');
        
        $('#tab-option .tab-content').append(html);
        
        $('#option a[href="#tab-option' + option_row + '"]').tab('show');
        
        $('[data-toggle=\'tooltip\']').tooltip({
          container: 'body',
          html: true
        });
        
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
        
        // تفعيل select2 إذا كان متاحاً
        if ($.fn.select2) {
          $('#input-unit' + option_row).select2();
        }
        
        option_row++;
        
        $('input[name=\'option\']').val('');
      }
    });
  }

  /**
   * إضافة قيمة خيار
   */
  function addOptionValue(option_row) {
    html = '<tr id="option-value-row' + option_value_row + '">';
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
  
  // الواجهة العامة
  return {
    init,
    addOptionValue,
    setupEvents
  };
})();