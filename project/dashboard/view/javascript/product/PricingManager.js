/**
 * PricingManager.js - مدير تسعير المنتج
 */

const PricingManager = (function() {
  /**
   * تهيئة مدير التسعير
   */
  function init() {
    // إعداد حاسبة السعر
    setupPriceCalculator();
    
    // إعداد محول الوحدات
    setupUnitConversionCalculator();
    
    // إعداد أحداث تعديل الأسعار
    setupPriceEditEvents();
  }
  
  /**
   * إعداد حاسبة السعر
   */
  function setupPriceCalculator() {
    // تحديث خيارات تحديد الوحدة
    updatePriceCalculatorUnitSelect(UnitManager.getCurrentUnits());
    
    // إضافة مستمعي الأحداث
    $('#calc-unit').off('change').on('change', function() {
      const unitId = $(this).val();
      
      if (unitId) {
        // البحث عن متوسط التكلفة لهذه الوحدة
        const averageCost = UnitManager.getAverageCostForUnit(unitId);
        
        $('#calc-cost').val(averageCost.toFixed(2));
        
        // تحميل السعر الحالي
        const pricing = findPricingData(unitId);
        const currentPrice = parseFloat(pricing.base_price) || 0;
        
        $('#calc-price').val(currentPrice.toFixed(2));
        
        // حساب الهامش الحالي
        if (averageCost > 0 && currentPrice > 0) {
          const margin = (1 - averageCost / currentPrice) * 100;
          $('#calc-margin').val(margin.toFixed(2));
        } else {
          $('#calc-margin').val('30.00');
        }
        
        // تحديث تفصيل السعر
        updatePriceBreakdown(unitId, averageCost, currentPrice);
      }
    });
    
    // أحداث لأزرار الحساب
    $('#calc-from-cost').off('click').on('click', function() {
      calculatePriceFromCost();
    });
    
    $('#calc-from-price').off('click').on('click', function() {
      calculateMarginFromPrice();
    });
  }
  
  /**
   * إعداد حاسبة تحويل الوحدات
   */
  function setupUnitConversionCalculator() {
    // تحديث خيارات تحديد الوحدة
    updateUnitConversionSelects();
    
    // إضافة مستمعي الأحداث
    $('#from-unit, #to-unit, #from-quantity').off('change input').on('change input', function() {
      updateConversionResult();
    });
  }
  
  /**
   * إعداد أحداث تعديل الأسعار
   */
  function setupPriceEditEvents() {
    // مستمع حدث حفظ السعر
    $('#save-price').off('click').on('click', function() {
      savePriceEdit();
    });
  }
  
  /**
   * تحديث قوائم تحويل الوحدات المنسدلة
   */
  function updateUnitConversionSelects() {
    const currentUnits = UnitManager.getCurrentUnits();
    let html = '<option value="">{{ text_select_unit }}</option>';
    
    if (currentUnits && currentUnits.length > 0) {
      for (let i = 0; i < currentUnits.length; i++) {
        const unitId = currentUnits[i];
        const unitName = UnitManager.getUnitName(unitId);
        if (unitId && unitName) {
          html += '<option value="' + unitId + '">' + unitName + '</option>';
        }
      }
    }
    
    $('#from-unit, #to-unit').html(html);
  }
  
  /**
   * تحديث قائمة وحدات حاسبة السعر المنسدلة
   */
  function updatePriceCalculatorUnitSelect(units) {
    let html = '<option value="">{{ text_select_unit }}</option>';
    
    if (units && units.length > 0) {
      for (let i = 0; i < units.length; i++) {
        const unitId = units[i];
        const unitName = UnitManager.getUnitName(unitId);
        if (unitId && unitName) {
          html += '<option value="' + unitId + '">' + unitName + '</option>';
        }
      }
    }
    
    $('#calc-unit').html(html);
  }
  
  /**
   * تحديث نتيجة التحويل
   */
  function updateConversionResult() {
    const fromUnitId = $('#from-unit').val();
    const toUnitId = $('#to-unit').val();
    const fromQuantity = parseFloat($('#from-quantity').val()) || 0;
    
    if (fromUnitId && toUnitId && fromQuantity > 0) {
      const result = UnitManager.convertBetweenUnits(fromUnitId, toUnitId, fromQuantity);
      $('#conversion-result').html(`
        <span class="conversion-from">${fromQuantity.toFixed(4)} ${UnitManager.getUnitName(fromUnitId)}</span>
        = <span class="conversion-to">${result.toFixed(4)} ${UnitManager.getUnitName(toUnitId)}</span>
      `);
      
      // عرض مثال تطبيقي
      $('#conversion-example').html(`
        مثال: ${fromQuantity.toFixed(2)} ${UnitManager.getUnitName(fromUnitId)}
        من منتج بتكلفة أساسية ${formatCurrency(10)} ستكلف
        ${formatCurrency(10 * (fromQuantity / result))} لكل ${UnitManager.getUnitName(toUnitId)}
      `);
    } else {
      $('#conversion-result').html('{{ text_conversion_result }}');
      $('#conversion-example').html('');
    }
  }
  
  /**
   * تنسيق العملة
   */
  function formatCurrency(value) {
    return value.toFixed(2); // في التطبيق الفعلي، استخدم تنسيق العملة من النظام
  }
  
  /**
   * تحديث جدول التسعير
   */
  function updatePricingTable(currentUnits) {
    if (!currentUnits || !currentUnits.length) {
      $('#product-pricing tbody').html('<tr><td colspan="7" class="text-center">{{ text_no_units_selected }}</td></tr>');
      return;
    }
    
    let pricingHtml = '';
    let pricingRow = 0;
    
    currentUnits.forEach(function(unitId) {
      // الحصول على بيانات التسعير أو إنشاء افتراضي
      const pricing = findPricingData(unitId);
      
      // الحصول على متوسط التكلفة لهذه الوحدة
      const averageCost = UnitManager.getAverageCostForUnit(unitId);
      
      const basePrice = parseFloat(pricing.base_price) || 0;
      const specialPrice = parseFloat(pricing.special_price) || 0;
      const wholesalePrice = parseFloat(pricing.wholesale_price) || 0;
      const halfWholesalePrice = parseFloat(pricing.half_wholesale_price) || 0;
      const customPrice = parseFloat(pricing.custom_price) || 0;
      
      // حساب هامش الربح
      const profitMargin = averageCost > 0 && basePrice > 0 ? 
        ((basePrice - averageCost) / basePrice * 100).toFixed(2) + '%' : 'غير متاح';
      
      // تمييز الأسعار الخاصة إذا كانت أقل من السعر الأساسي
      const specialPriceClass = specialPrice > 0 && specialPrice < basePrice ? 'text-danger' : '';
      
      pricingHtml += '<tr>';
      pricingHtml += '<td>' + UnitManager.getUnitName(unitId) + '</td>';
      pricingHtml += '<td class="text-center">' + averageCost.toFixed(2) + '</td>';
      pricingHtml += '<td class="text-center">' + basePrice.toFixed(2) + '</td>';
      pricingHtml += '<td class="text-center ' + specialPriceClass + '">' + (specialPrice > 0 ? specialPrice.toFixed(2) : '-') + '</td>';
      pricingHtml += '<td class="text-center">' + (wholesalePrice > 0 ? wholesalePrice.toFixed(2) : '-') + '</td>';
      pricingHtml += '<td class="text-center profit-margin">' + profitMargin + '</td>';
      
      // إضافة زر التعديل
      pricingHtml += '<td class="text-center">';
      pricingHtml += '  <button type="button" class="btn btn-primary btn-sm" onclick="openPriceEditDialog(\'' + unitId + '\')">';
      pricingHtml += '    <i class="fa fa-pencil"></i>';
      pricingHtml += '  </button>';
      pricingHtml += '</td>';
      
      // حقول مخفية للحفاظ على البيانات
      pricingHtml += '<input type="hidden" name="product_pricing[' + pricingRow + '][unit_id]" value="' + unitId + '" />';
      pricingHtml += '<input type="hidden" name="product_pricing[' + pricingRow + '][base_price]" value="' + basePrice + '" />';
      pricingHtml += '<input type="hidden" name="product_pricing[' + pricingRow + '][special_price]" value="' + specialPrice + '" />';
      pricingHtml += '<input type="hidden" name="product_pricing[' + pricingRow + '][wholesale_price]" value="' + wholesalePrice + '" />';
      pricingHtml += '<input type="hidden" name="product_pricing[' + pricingRow + '][half_wholesale_price]" value="' + (pricing.half_wholesale_price || 0) + '" />';
      pricingHtml += '<input type="hidden" name="product_pricing[' + pricingRow + '][custom_price]" value="' + (pricing.custom_price || 0) + '" />';
      pricingHtml += '</tr>';
      
      pricingRow++;
    });
    
    $('#product-pricing tbody').html(pricingHtml);
  }
  
  /**
   * فتح نافذة تعديل السعر
   */
  function openPriceEditDialog(unitId) {
    // إعداد النافذة
    $('#price-edit-unit-id').val(unitId);
    
    // استرجاع بيانات الأسعار الحالية
    const pricing = findPricingData(unitId);
    $('#price-edit-base').val(parseFloat(pricing.base_price || 0).toFixed(2));
    $('#price-edit-special').val(parseFloat(pricing.special_price || 0).toFixed(2));
    $('#price-edit-wholesale').val(parseFloat(pricing.wholesale_price || 0).toFixed(2));
    $('#price-edit-half-wholesale').val(parseFloat(pricing.half_wholesale_price || 0).toFixed(2));
    $('#price-edit-custom').val(parseFloat(pricing.custom_price || 0).toFixed(2));
    
    // إفراغ حقل السبب
    $('#price-edit-reason').val('');
    
    // عنوان النافذة
    $('.modal-title').text('تعديل أسعار ' + UnitManager.getUnitName(unitId));
    
    // عرض النافذة
    $('#price-edit-modal').modal('show');
    
    // التركيز على حقل السعر الأساسي
    setTimeout(function() {
      $('#price-edit-base').focus().select();
    }, 500);
  }
  
  /**
   * حفظ تعديل السعر
   */
  function savePriceEdit() {
    const unitId = $('#price-edit-unit-id').val();
    const basePrice = parseFloat($('#price-edit-base').val());
    const specialPrice = parseFloat($('#price-edit-special').val());
    const wholesalePrice = parseFloat($('#price-edit-wholesale').val());
    const halfWholesalePrice = parseFloat($('#price-edit-half-wholesale').val());
    const customPrice = parseFloat($('#price-edit-custom').val());
    const reason = $('#price-edit-reason').val();
    
    // التحقق من صحة البيانات
    if (isNaN(basePrice) || basePrice < 0) {
      showNotification('error', 'يرجى إدخال سعر أساسي صالح');
      return;
    }
    
    if (!reason) {
      showNotification('error', 'يرجى إدخال سبب التعديل');
      return;
    }
    
    // تعطيل الزر لمنع النقرات المتكررة
    $('#save-price').prop('disabled', true);
    
    // إرسال طلب تعديل السعر
    const productId = $('input[name="product_id"]').val();
    if (productId) {
      $.ajax({
        url: 'index.php?route=catalog/product/updatePricing&user_token=' + user_token + '&product_id=' + productId,
        type: 'POST',
        data: {
          unit_id: unitId,
          base_price: basePrice,
          special_price: specialPrice || 0,
          wholesale_price: wholesalePrice || 0,
          half_wholesale_price: halfWholesalePrice || 0,
          custom_price: customPrice || 0,
          reason: reason
        },
        dataType: 'json',
        beforeSend: function() {
          $('#save-price').button('loading');
        },
        complete: function() {
          $('#save-price').button('reset');
          $('#save-price').prop('disabled', false);
        },
        success: function(json) {
          if (json.error) {
            showNotification('error', json.error);
          } else {
            showNotification('success', json.success);
            
            // إغلاق النافذة
            $('#price-edit-modal').modal('hide');
            
            // تحديث بيانات التسعير
            updateProductPricing(unitId, basePrice, specialPrice, wholesalePrice, halfWholesalePrice, customPrice);
            updatePricingTable(UnitManager.getCurrentUnits());
          }
        },
        error: function(xhr, ajaxOptions, thrownError) {
          showNotification('error', thrownError);
          $('#save-price').prop('disabled', false);
        }
      });
    } else {
      // للمنتجات الجديدة، حفظ التعديل في الذاكرة
      updateProductPricing(unitId, basePrice, specialPrice, wholesalePrice, halfWholesalePrice, customPrice);
      
      // تحديث العرض
      updatePricingTable(UnitManager.getCurrentUnits());
      
      // إغلاق النافذة
      $('#price-edit-modal').modal('hide');
      
      showNotification('success', 'تم تحديث الأسعار. سيتم تسجيل التغيير عند حفظ المنتج.');
      $('#save-price').prop('disabled', false);
    }
  }
  
  /**
   * تحديث بيانات التسعير في الذاكرة
   */
  function updateProductPricing(unitId, basePrice, specialPrice, wholesalePrice, halfWholesalePrice, customPrice) {
    // البحث عن العنصر الموجود أو إنشاء عنصر جديد
    let updated = false;
    
    if (productPricing && productPricing.length > 0) {
      for (let i = 0; i < productPricing.length; i++) {
        if (productPricing[i].unit_id == unitId) {
          productPricing[i].base_price = basePrice;
          productPricing[i].special_price = specialPrice || 0;
          productPricing[i].wholesale_price = wholesalePrice || 0;
          productPricing[i].half_wholesale_price = halfWholesalePrice || 0;
          productPricing[i].custom_price = customPrice || 0;
          updated = true;
          break;
        }
      }
    }
    
    if (!updated) {
      if (!productPricing) productPricing = [];
      
      productPricing.push({
        unit_id: unitId,
        base_price: basePrice,
        special_price: specialPrice || 0,
        wholesale_price: wholesalePrice || 0,
        half_wholesale_price: halfWholesalePrice || 0,
        custom_price: customPrice || 0
      });
    }
  }
  
  /**
   * حساب السعر من التكلفة والهامش
   */
  function calculatePriceFromCost() {
    const cost = parseFloat($('#calc-cost').val()) || 0;
    const margin = parseFloat($('#calc-margin').val()) || 0;
    const marginType = $('#margin-type').val() || 'markup';
    
    if (cost <= 0) {
      showNotification('error', 'يرجى إدخال تكلفة صحيحة أكبر من صفر');
      return;
    }
    
    if (margin < 0) {
      showNotification('error', 'يجب أن يكون الهامش قيمة موجبة');
      return;
    }
    
    let price;
    
    // حساب السعر بناءً على نوع الهامش
    if (marginType === 'markup') {
      // هامش كنسبة إضافية على التكلفة (markup)
      price = cost * (1 + margin / 100);
    } else {
      // هامش كنسبة من السعر النهائي (margin)
      // عندما يكون الهامش 100% فإن المعادلة غير معرفة رياضياً، لذا نتحقق
      if (margin >= 100) {
        showNotification('error', 'عند حساب الهامش كنسبة من السعر النهائي، يجب أن تكون النسبة أقل من 100%');
        return;
      }
      price = cost / (1 - margin / 100);
    }
    
    $('#calc-price').val(price.toFixed(2));
    $('#apply-calculated-price').prop('disabled', false);
    
    // تحديث تفصيل السعر
    const unitId = $('#calc-unit').val();
    updatePriceBreakdown(unitId, cost, price, margin, marginType);
  }
  
  /**
   * حساب الهامش من التكلفة والسعر
   */
  function calculateMarginFromPrice() {
    const cost = parseFloat($('#calc-cost').val()) || 0;
    const price = parseFloat($('#calc-price').val()) || 0;
    const marginType = $('#margin-type').val() || 'markup';
    
    if (cost <= 0) {
      showNotification('error', 'يرجى إدخال تكلفة صحيحة أكبر من صفر');
      return;
    }
    
    if (price <= 0) {
      showNotification('error', 'يرجى إدخال سعر صحيح أكبر من صفر');
      return;
    }
    
    if (price < cost) {
      showNotification('warning', 'السعر أقل من التكلفة! هذا سيؤدي إلى خسارة.');
    }
    
    let margin;
    
    // حساب الهامش بناءً على نوع الهامش
    if (marginType === 'markup') {
      // هامش كنسبة إضافية على التكلفة (markup)
      margin = ((price - cost) / cost) * 100;
    } else {
      // هامش كنسبة من السعر النهائي (margin)
      margin = ((price - cost) / price) * 100;
    }
    
    $('#calc-margin').val(margin.toFixed(2));
    $('#apply-calculated-price').prop('disabled', false);
    
    // تحديث تفصيل السعر
    const unitId = $('#calc-unit').val();
    updatePriceBreakdown(unitId, cost, price, margin, marginType);
  }

  /**
   * تحديث تفصيل السعر
   */
  function updatePriceBreakdown(unitId, cost, price, margin, marginType) {
    if (!unitId || isNaN(cost) || isNaN(price)) {
      $('#price-breakdown').html('');
      return;
    }
    
    const profit = price - cost;
    const profitPercent = cost > 0 ? (profit / cost * 100) : 0;
    const marginPercent = marginType === 'markup' ? profitPercent : (price > 0 ? (profit / price * 100) : 0);
    
    // تحديد فئة اللون بناءً على الربح
    let profitClass = 'text-success';
    if (profit < 0) {
      profitClass = 'text-danger';
    } else if (profit === 0) {
      profitClass = 'text-warning';
    }
    
    const html = `
      <div class="price-breakdown">
        <h5 class="breakdown-title">تفاصيل السعر (${UnitManager.getUnitName(unitId)})</h5>
        <div class="breakdown-item">
          <span class="label">التكلفة:</span> 
          <span class="value">${cost.toFixed(2)}</span>
        </div>
        <div class="breakdown-item">
          <span class="label">الربح:</span> 
          <span class="value ${profitClass}">${profit.toFixed(2)} (${profitPercent.toFixed(2)}% من التكلفة)</span>
        </div>
        <div class="breakdown-item">
          <span class="label">هامش الربح:</span> 
          <span class="value ${profitClass}">${marginPercent.toFixed(2)}% ${marginType === 'markup' ? 'من التكلفة' : 'من السعر'}</span>
        </div>
        <div class="breakdown-item">
          <span class="label">سعر البيع:</span> 
          <span class="value">${price.toFixed(2)}</span>
        </div>
      </div>
    `;
    
    $('#price-breakdown').html(html);
  }
  
  // الواجهة العامة
  return {
    init,
    calculatePriceFromCost,
    calculateMarginFromPrice,
    updatePriceCalculatorUnitSelect,
    updatePricingTable,
    updateConversionResult,
    updateProductPricing,
    openPriceEditDialog,
    savePriceEdit
  };
})();