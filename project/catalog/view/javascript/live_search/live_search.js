var LiveSearchJs = function () {

  var init = function(options) {
      var live_search = {
          selector: "#search input[name='search']",
          text_no_matches: options.text_empty,
          height: '50px'
      }
      // console.log(options);

      // Initializing drop down list
      var html = '<div class="live-search"><ul></ul></div>';
      $(live_search.selector).after(html);

      $(live_search.selector).autocomplete({
          'source': function(request, response) {
              var filter_name = $(live_search.selector).val();
              var cat_id = 0;
              var live_search_min_length = options.module_live_search_min_length;
              if (filter_name.length < live_search_min_length) {
                  $('.live-search').css('display','none');
              }
              else{
                  var live_search_href = 'index.php?route=extension/module/live_search&filter_name=';
                  var all_search_href = 'index.php?route=product/search&search=';
              
                  live_search_href = live_search_href + encodeURIComponent(filter_name);
                  all_search_href = all_search_href + encodeURIComponent(filter_name);
              

                  var html  = '<li style="text-align: center;height:10px;">';
                      html += '<img class="loading" src="catalog/view/javascript/live_search/loading.gif" />';
                      html += '</li>';
                  $('.live-search ul').html(html);
                  $('.live-search').css('display','block');

                  $.ajax({
                      url: live_search_href,
                      dataType: 'json',
                      success: function(result) {
                          var products = result.products;
                          $('.live-search ul li').remove();
                          if (!$.isEmptyObject(products)) {
                              var show_image       = options.module_live_search_show_image;
                              $.each(products, function(index,product) {
                                  var html = '<li>';
                                  // show_add_button

                                  html += '<div>';
                                  html += '<a href="' + product.url + '" title="' + product.name + '">';
                                  // show image
                                  if(product.image && show_image == 1){
                                      html += '<div class="product-image"><img alt="' + product.name + '" src="' + product.image + '"></div>';
                                  }
                                  // show name & extra_info
                                  html += '<div class="product-name">' + product.name ;
                                  if(show_description == 1){
                                      html += '<p>' + product.extra_info + '</p>';
                                  }
                                  html += '</div>';
                                  // show price & special price

                                  html += '</a>';
                                  html += '</div>';

                                  html += '</li>';
                                  $('.live-search ul').append(html);
                              });
                          } else {
                              var html = '';
                              html += '<li style="text-align: center;height:10px;">';
                              html += live_search.text_no_matches;
                              html += '</li>';

                              $('.live-search ul').html(html);
                          }
                          // $('.live-search ul li').css('height',live_search.height);
                          $('.live-search').css('display','block');

                          return false;
                      }
                  });

              }
          },
          'select': function(product) {
              $(live_search.selector).val(product.name);
          }
      });

      $(document).bind( "mouseup touchend", function(e){
          var container = $('.live-search');
          if (!container.is(e.target) && container.has(e.target).length === 0) {
              container.hide();
          }
      });
  }

  return {
      //main function to initiate the module
      init: function(options) {
          init(options);
      }
  };

}();

