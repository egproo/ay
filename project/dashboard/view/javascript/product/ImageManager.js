/**
 * ImageManager.js - مدير صور المنتج
 */

const ImageManager = (function() {
  /**
   * تهيئة مدير الصور
   */
  function init() {
    // تهيئة عداد صفوف الصور
    image_row = $('#images tbody tr').length || 0;
    
    // إضافة مستمع لزر إضافة صورة
    $('#button-image-add').off('click').on('click', function() {
      addImage();
    });
    
    // تهيئة مديرات الصور
    initImageManagers();
  }
  
  /**
   * تهيئة مديرات الصور
   */
  function initImageManagers() {
    // تفعيل محرر الصور عند النقر على أي صورة
    $('a[data-toggle="image"]').off('click').on('click', function(e) {
      e.preventDefault();
      
      var $element = $(this);
      var $popover = $element.data('bs.popover'); // element has bs popover?
      
      if ($popover) {
        $popover.popover('hide');
      }
      
      $('#modal-image').remove();
      
      $.ajax({
        url: 'index.php?route=common/filemanager&user_token=' + user_token + '&target=' + $element.parent().find('input').attr('id') + '&thumb=' + $element.attr('id'),
        dataType: 'html',
        beforeSend: function() {
          $element.find('> i').addClass('fa-circle-o-notch fa-spin');
          $element.prop('disabled', true);
        },
        complete: function() {
          $element.find('> i').removeClass('fa-circle-o-notch fa-spin');
          $element.prop('disabled', false);
        },
        success: function(html) {
          $('body').append('<div id="modal-image" class="modal">' + html + '</div>');
          $('#modal-image').modal('show');
        }
      });
    });
  }
  
  /**
   * إضافة صورة جديدة
   */
  function addImage() {
    let html = `
      <tr id="image-row${image_row}">
        <td class="text-center">
          <a href="" id="thumb-image${image_row}" data-toggle="image" class="img-thumbnail">
            <img src="{{ placeholder }}" alt="" title="" data-placeholder="{{ placeholder }}" />
          </a>
          <input type="hidden" name="product_image[${image_row}][image]" value="" id="input-image${image_row}" />
        </td>
        <td class="text-center">
          <input type="text" name="product_image[${image_row}][sort_order]" value="0" placeholder="{{ entry_sort_order }}" class="form-control" />
        </td>
        <td class="text-center">
          <button type="button" onclick="ImageManager.removeImage(${image_row});" data-toggle="tooltip" title="{{ button_remove }}" class="btn btn-danger">
            <i class="fa fa-minus-circle"></i>
          </button>
        </td>
      </tr>
    `;

    $('#images tbody').append(html);
    
    // تهيئة محرر الصورة المضافة
    $('a[data-toggle="image"]').off('click').on('click', function(e) {
      e.preventDefault();
      
      var $element = $(this);
      var $popover = $element.data('bs.popover'); // element has bs popover?
      
      if ($popover) {
        $popover.popover('hide');
      }
      
      $('#modal-image').remove();
      
      $.ajax({
        url: 'index.php?route=common/filemanager&user_token=' + user_token + '&target=' + $element.parent().find('input').attr('id') + '&thumb=' + $element.attr('id'),
        dataType: 'html',
        beforeSend: function() {
          $element.find('> i').addClass('fa-circle-o-notch fa-spin');
          $element.prop('disabled', true);
        },
        complete: function() {
          $element.find('> i').removeClass('fa-circle-o-notch fa-spin');
          $element.prop('disabled', false);
        },
        success: function(html) {
          $('body').append('<div id="modal-image" class="modal">' + html + '</div>');
          $('#modal-image').modal('show');
        }
      });
    });

    image_row++;
  }
  
  /**
   * إزالة صورة
   */
  function removeImage(row) {
    $('#image-row' + row).remove();
  }
  
  // الواجهة العامة
  return {
    init,
    addImage,
    removeImage,
    initImageManagers
  };
})();