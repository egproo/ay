/**
 * shared.js - الدوال والبيانات المشتركة بين مديري المنتج
 */

// البيانات العالمية المستخدمة بين جميع المديرين
var productUnits = [];    // وحدات المنتج
var productInventory = []; // مخزون المنتج
var productPricing = [];  // تسعير المنتج
var branches = [];        // الفروع
var allUnits = [];        // جميع الوحدات المتاحة

// عدادات الصفوف
var unitRow = 0;
var inventoryRow = 0;
var pricingRow = 0;
var option_row = 0;
var option_value_row = 0;
var image_row = 0;
var bundle_row = 0;
var discount_row = 0;
var upsell_row = 0;
var cross_sell_row = 0;
var barcode_row = 0;

// استرجاع رمز المستخدم من عنوان URL
function getUrlParam(name) {
  const results = new RegExp('[?&]' + name + '=([^&#]*)').exec(window.location.href);
  return results ? decodeURIComponent(results[1]) : null;
}

// الحصول على رمز المستخدم
var user_token = getUrlParam('user_token');

// وظيفة عرض الإشعارات
function showNotification(type, message) {
  if (typeof toastr !== 'undefined') {
    // استخدام toastr للإشعارات
    toastr.options = {
      "closeButton": true,
      "debug": false,
      "newestOnTop": true,
      "progressBar": true,
      "positionClass": "toast-top-right",
      "preventDuplicates": false,
      "onclick": null,
      "showDuration": "300",
      "hideDuration": "1000",
      "timeOut": "5000",
      "extendedTimeOut": "1000",
      "showEasing": "swing",
      "hideEasing": "linear",
      "showMethod": "fadeIn",
      "hideMethod": "fadeOut"
    };
    
    switch(type) {
      case 'success':
        toastr.success(message);
        break;
      case 'error':
        toastr.error(message);
        break;
      case 'warning':
        toastr.warning(message);
        break;
      default:
        toastr.info(message);
    }
  } else {
    // الطريقة الاحتياطية: استخدام نظام الإشعارات البسيط
    const icons = {
      'success': 'fa-check-circle',
      'error': 'fa-exclamation-circle',
      'warning': 'fa-exclamation-triangle',
      'info': 'fa-info-circle'
    };
    
    const icon = icons[type] || icons.info;
    const className = 'notification-' + type;
    
    const html = `
      <div class="notification ${className}">
        <i class="fa ${icon}"></i> ${message}
        <button type="button" class="close" onclick="$(this).parent().fadeOut();">&times;</button>
      </div>
    `;
    
    $('#notification-area').append(html);
    
    // إزالة الإشعار تلقائياً بعد 5 ثوان
    setTimeout(function() {
      $('.notification').first().fadeOut(500, function() {
        $(this).remove();
      });
    }, 5000);
  }
}

// البحث عن بيانات المخزون
function findInventoryData(branchId, unitId) {
  if (!productInventory || !Array.isArray(productInventory) || productInventory.length === 0) {
    return { 
      quantity: "0", 
      quantity_available: "0", 
      average_cost: "0" 
    };
  }
  
  // تحويل المعرفات إلى أرقام للمقارنة الدقيقة
  const branchIdNum = parseInt(branchId, 10);
  const unitIdNum = parseInt(unitId, 10);
  
  const inventory = productInventory.find(function(item) {
    return parseInt(item.branch_id, 10) === branchIdNum && parseInt(item.unit_id, 10) === unitIdNum;
  });
  
  if (inventory) {
    return inventory;
  } else {
    return { 
      quantity: "0", 
      quantity_available: "0", 
      average_cost: "0" 
    };
  }
}

// البحث عن بيانات التسعير
function findPricingData(unitId) {
  if (!productPricing || !Array.isArray(productPricing) || productPricing.length === 0) {
    return { 
      base_price: 0, 
      special_price: 0, 
      wholesale_price: 0, 
      half_wholesale_price: 0, 
      custom_price: 0 
    };
  }
  
  // تحويل معرف الوحدة إلى رقم للمقارنة
  const unitIdNum = parseInt(unitId);
  
  const pricing = productPricing.find(function(item) {
    return parseInt(item.unit_id) === unitIdNum;
  });
  
  if (pricing) {
    return pricing;
  } else {
    return { 
      base_price: 0, 
      special_price: 0, 
      wholesale_price: 0, 
      half_wholesale_price: 0, 
      custom_price: 0 
    };
  }
}

// تسجيل رسالة في وحدة التحكم
function logMessage(message, data) {
  if (typeof console !== 'undefined' && console.debug) {
    if (data !== undefined) {
      console.debug('[DEBUG] ' + message, data);
    } else {
      console.debug('[DEBUG] ' + message);
    }
  }
}