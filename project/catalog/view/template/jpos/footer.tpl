  <footer>
  </footer>
</main>
<script src="catalog/view/javascript/jquery/jpos/jquery.mousewheel.min.js"></script>
<script type="text/javascript"><!--
  $(document).ready(function() {
    var owl = $('.ps-categories .owl-carousel');
    owl.owlCarousel({
      loop: false,
      nav: false,
      dots: false,
      margin: 5,
      autoWidth : true,
      responsive: {
        0: {
          items: 1
        },
        600: {
          items: 3
        },
        960: {
          items: 5
        },
        1200: {
          items: 5
        }
      },
      'onInitialize':function() {
        //console.log("onInitialize")
      },'onInitialized': function() {
          //console.log("onInitialized")
          //console.log(this.$stage.width())
          if (typeof this.$stage.attr('data-owidth') == 'undefined') {
          this.$stage.attr('data-owidth', this.$stage.width()).data('owidth', this.$stage.width())
          }
          var w = parseFloat(this.$stage.width());

          if (typeof this.$stage.attr('data-owidth') != 'undefined') {
            w = parseFloat( this.$stage.attr('data-owidth'));
          }
          this.$stage.css('width', (w + (w*2/100) ));
          //console.log(this.$stage.width())
      },'onResized': function() {
          //console.log("onResized")
          //console.log(this.$stage.width())
          if (typeof this.$stage.attr('data-owidth') == 'undefined') {
          this.$stage.attr('data-owidth', this.$stage.width()).data('owidth', this.$stage.width())
          }
          var w = parseFloat(this.$stage.width());

          if (typeof this.$stage.attr('data-owidth') != 'undefined') {
            w = parseFloat( this.$stage.attr('data-owidth'));
          }
          this.$stage.css('width', (w + (w*2/100) ));
          //console.log(this.$stage.width())
      },'onRefreshed': function() {
          //console.log("onRefreshed")
          //console.log(this.$stage.width())
          if (typeof this.$stage.attr('data-owidth') == 'undefined') {
          this.$stage.attr('data-owidth', this.$stage.width()).data('owidth', this.$stage.width())
          }
          var w = parseFloat(this.$stage.width());

          if (typeof this.$stage.attr('data-owidth') != 'undefined') {
            w = parseFloat( this.$stage.attr('data-owidth'));
          }
          this.$stage.css('width', (w + (w*2/100) ));
          //console.log(this.$stage.width())
      }
    });
    owl.on('mousewheel', '.owl-stage', function(e) {
      if (e.deltaY > 0) {
        owl.trigger('prev.owl');
      } else {
        owl.trigger('next.owl');
      }
      e.preventDefault();
    });
  })
--></script>
<script type="text/javascript"><!--

  $("#button-menu").click(function(){
    $("#posmenu").toggleClass('active');
    $("#ps-wrapper").toggleClass('active');
  });

  $(document).ready(function(){


    initScrollbar($('.scrollert'));

    $(document).delegate('.panel_show', 'click', function() {
      var panel = $(this).attr('data-panel');
      if ($(panel).hasClass('active') && $(this).attr('data-toggle') == 'false') {
        return;
      }
      // remove zindex from style from all active panles
      $('.panel-of.active').each(function() {
        $(this).css('z-index','');
      });


      // incrase zindex of currently open panel and add using style
      if (!$(panel).hasClass('active')) {
        var zindex = $(panel).css('z-index');
        $(panel).css('z-index', ++zindex);
      }

      <?php if (!$user_logged) { ?>
        if (panel == '#ps-login' && !$(panel).hasClass('active')) {
          // $(panel).toggleClass('active');
        }
      <?php } else { ?>
        $(panel).toggleClass('active');
      <?php }  ?>

      if ($(panel).hasClass('active')) {
        $(panel).trigger('jpos.panel.show');
      } else {
        $(panel).trigger('jpos.panel.close');
      }

      setTimeout(function() {
        // close all other panels
        $('.panel-of.active').each(function() {
          var panel_ = '#' + $(this).attr('id');
          // console.log(panel_)
          // console.log(panel)
          // console.log(panel_ != panel)
          if (panel_ != panel) {
            // trigger panel_close so that associated events can be called
            console.log("come here");
            $(panel_).find('.panel_close').trigger('click')

            // $(panel_).removeClass('active');
          }
        });
      },500);
    });

    $(document).delegate('.panel_close', 'click', function(){
      var panel = $(this).attr('data-panel');
      // remove zindex of panel
      $(panel).css('z-index','');
      $(panel).removeClass('active');
      $(panel).trigger('jpos.panel.close');
    });

    $(document).delegate('.panel_close_all', 'click', function(){
      // remove zindex from style from all active panles
      $('.panel-of.active').each(function() {
        $(this).css('z-index','');
        var panel_ = '#' + $(this).attr('id');
        // console.log(panel_)
        // console.log(panel)
        // console.log(panel_ != panel)
        // trigger panel_close so that associated events can be called
        console.log("close all come here");
        $(panel_).find('.panel_close').trigger('click')
        // $(panel_).removeClass('active');
      });
    });

    $(document).delegate('.content_destroy', 'click', function(){
      var target = $(this).attr('data-target');
      $(target).html('');
      // https://learn.jquery.com/events/introduction-to-custom-events/
      $(target).trigger('jpos.content.getempty')

    });
    // checkout panel ev start
    $('#ps-checkout').on('jpos.panel.close', function() {
      $('.cart-block').addClass('hide');
      $('.btn-continue-checkout').attr('disabled', false);
      console.log("do when #ps-checkout panel get close");
    });
    $('#ps-checkout').on('jpos.panel.show', function() {
      console.log("do when #ps-checkout panel get show");
    });
    // checkout panel ev end

    // customer panel ev start
    $('#ps-customer-list').on('jpos.panel.show', function() {
      console.log('customer list panel shows');
      $(this).find('ul li > a.customer_info:first').trigger('click');

    });
    $('#ps-customer-list').on('jpos.panel.close', function() {
      console.log('customer list panel close');
      // remove this code. result in too much recursion or too big regex
      // $(this).find('#ps-customer-detail .panel_close').trigger('click');
    });

    // when customer details edit from customer list
    $('#ps-customer-detail .customer-detail-edit').on('jpos.content.getempty', function() {

      // refresh updated customer details - pending work
      console.log("customer-detail-edit content empty")
      $('#ps-customer-detail .customer-detail').removeClass('hide');
    });

    $('#ps-customer-detail .customer-detail-edit').on('jpos.content.getfill', function() {

      console.log("customer-detail-edit content fill")
      $('#ps-customer-detail .customer-detail').addClass('hide');
    });

    $('#ps-customer-detail .customer-detail').on('jpos.content.getempty', function() {
      // $('#ps-customer-detail .customer-detail').removeClass('hide');
      console.log("customer-detail content empty")
      $('#ps-customer-detail .customer-detail-edit').find('.content_destroy').first().trigger('click');
    });

    $('#ps-customer-detail .customer-detail').on('jpos.content.getfill', function() {
      // $('#ps-customer-detail .customer-detail').removeClass('hide');
      console.log("customer-detail content fill")
      $('#ps-customer-detail .customer-detail-edit').find('.content_destroy').first().trigger('click');
    });
    // customer panel ev end

    // order-history panel ev start
    $('#order-history-wrap').on('jpos.panel.show', function() {
      console.log('orders list panel shows');
      $(this).find('#ps-order-history .order_info:first').trigger('click');

    });
    $('#order-history-wrap').on('jpos.panel.close', function() {
      console.log('orders list panel close');
      // remove this code. result in too much recursion or too big regex
      // $(this).find('#ps-order-detail .panel_close').trigger('click');
    });
    // order-history panel ev end



    $('.all-cate').click(function(){
      var el = this;
     $('.ps-categories').toggleClass(function() {
      if (!$(this).hasClass('active')) {
        // add angel up remove angel down
        $(el).find('i.fa-angle-down').addClass('fa-angle-up').removeClass('fa-angle-down');
      } else {
        // add angel down remove angel up
        $(el).find('i.fa-angle-up').addClass('fa-angle-down').removeClass('fa-angle-up');
      }
      return 'active';
     });
    });

    // remove start
    // //Add User
    // $('.user').click(function(){
    //   $('#adduser').toggleClass('active');
    // });
    // $('.close-adduser').click(function(){
    //   $('#adduser').removeClass('active');
    // });

    // //Checkout

    // $('.makecheckout').click(function(){
    //   $('#ps-checkout').toggleClass('active');
    // });
    // $('.close-makecheckout').click(function(){
    //   $('#ps-checkout').removeClass('active');
    // });

    // //Order History

    // $('.order-history').click(function(){
    //   $('#order-history-wrap').addClass('active');
    // });
    // $('.close-order-history').click(function(){
    //   $('#order-history-wrap').removeClass('active');
    // });

    // //On-Hold Cart

    // $('.on-hold-items').click(function(){
    //   $('#ps-onhold-order').addClass('active');
    // });
    // $('.close-onhold').click(function(){
    //   $('#ps-onhold-order').removeClass('active');
    // });

    // //Customer List

    // $('.customer-list-open').click(function(){
    //   $('#ps-customer-list').addClass('active');
    // });
    // $('.close-customer-list').click(function(){
    //   $('#ps-customer-list').removeClass('active');
    // });

    // //General Settings

    // $('.general-open').click(function(){
    //   $('#ps-general-wrap').addClass('active');
    // });
    // $('.close-general-wrap').click(function(){
    //   $('#ps-general-wrap').removeClass('active');
    // });

    // //Account Settings

    // $('.account-open').click(function(){
    //   $('#ps-account-wrap').addClass('active');
    // });
    // $('.close-account-wrap').click(function(){
    //   $('#ps-account-wrap').removeClass('active');
    // });
    // remove end

    // product wrap starts

    // scroll starts
    productContent_data = {
      product_data : {
        page : 1,
      },
      category_data : {
        path : '',
      },
    };

    var productContent_lastScrollTop = 0;
    var productContent_ajaxGetProducts = null;
    var productContent_getAjaxProducts = true;
    setTimeout(function() {


    $('.products_area .products_content').on('scroll', function() {
      // alert("alla a");
      // console.log("productContent height: " + $(this).height());
      // console.log("productContent innerHeight: " + $(this).innerHeight());
      // console.log("productContent outerHeight: " + $(this).outerHeight());
      // // console.log($(this).offset());
      // console.log("productContent scrollTop :" + $(this).scrollTop());
      // console.log("productContent scrollLeft :" + $(this).scrollLeft());
      // console.log("productContent scrollHeight :" + $(this)[0].scrollHeight);
      // console.log("productContent scrollWidth :" + $(this)[0].scrollWidth);
      // console.log("productContent productContent_lastScrollTop : " + productContent_lastScrollTop);
      // console.log("ass");

      if (productContent_lastScrollTop > $(this).scrollTop()) {
        console.log("productContent going upp");
      } else {
        console.log("productContent going down");

        if ($(this).scrollTop() + $(this).height() > $(this)[0].scrollHeight - 100) {
          if (productContent_ajaxGetProducts == null && productContent_getAjaxProducts == true) {

            productContent_data.product_data.page = productContent_data.product_data.page + 1;

            var data = productContent_getFilterData([]);


            productContent_getAjaxProducts = false;

            productContent_ajaxGetProducts = $.ajax({
              url: 'index.php?route=jpos/product/getPage&a=1&ev=scroll',
              type: 'get',
              data: data.join('&'),
              dataType: 'json',
              beforeSend: function() {
                spinner('#ps-product-block .products_area', 'show');
              },
              complete: function() {
                spinner('#ps-product-block .products_area', 'hide');
              },
              success: function(json) {
                if (json['products']) {
                  $('.products_area .products_content').append(json['products']);

                  updateScrollbar($('.products_area.scrollert'));
                } else {
                   productContent_getAjaxProducts = false;
                  // revert back to previous page
                  productContent_data.product_data.page = productContent_data.product_data.page - 1;
                }
                console.log("productContent_ajaxGetProducts");
                console.log(productContent_ajaxGetProducts);
              },
              error: function(xhr, ajaxOptions, thrownError) {
                console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
              }
            }).always(function(json,type,ajaxObject) {
              console.log("productContent this is promise by ajax and always run")
              productContent_ajaxGetProducts = null;
              // console.log("productContent json")
              // console.log(json)
              // console.log("productContent type")
              // console.log(type)
              // console.log("productContent ajaxObject")
              // console.log(ajaxObject)
              if (json['products']) {
                productContent_getAjaxProducts = true;
              } else {
                productContent_getAjaxProducts = false;
              }
            });
          }
        }
      }

      productContent_lastScrollTop = $(this).scrollTop();

    })
    },500);
    // $('.products_area .scrollert-content')[0].scrollHeight
    // $('.products_area .scrollert-content').prop('scrollHeight')
    // $(".products_area .scrollert-content").animate({ scrollTop: "1000px" });
    // https://www.npmjs.com/package/scrollert#advanced-usage
    // https://stackoverflow.com/questions/22675126/what-is-offsetheight-clientheight-scrollheight
    /*update

    To update the scrollbars. This is necessary when the dimensions of the content element are changed due to DOM or changes.

    $('.scrollert').scrollert('destroy');
    $('.scrollert').scrollert('update');
    */
    // scroll ends

    // get category products
    $(document).delegate('.category_products', 'click', function() {

      // add active class on top_level categories
      if ($(this).hasClass('top_cats')) {
        $('.top_cats').removeClass('active');
        $(this).addClass('active');
      }

      var path = $(this).attr('data-path');
      productContent_resetData();
      productContent_resetVars();
      productContent_data.category_data.path = path;
      // for loading category products
      var data = productContent_getFilterData([]);

      $.ajax({
        url: 'index.php?route=jpos/product/getCategoryProducts&a=1',
        type: 'get',
        data: data.join('&'),
        dataType: 'json',
        beforeSend: function() {
          spinner('#ps-product-block .products_area', 'show');
        },
        complete: function() {
          spinner('#ps-product-block .products_area', 'hide');
        },
        success: function(json) {
          $('.products_area .products_content').html(json['refine']);
          $('.products_area .products_content').append(json['products']);

          updateScrollbar($('.products_area.scrollert'));

        },
        error: function(xhr, ajaxOptions, thrownError) {
          console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
        }
      })

    });

    // search in products
    function productContent_resetVars() {
      // reset on scroll ajax variables to default
      productContent_ajaxGetProducts = null;
      productContent_getAjaxProducts = true;
    }

    var productContent_ajaxSearchProducts = null;

    function productContent_abortSearchProductsAjax() {
      if (productContent_ajaxSearchProducts) {
        if (productContent_ajaxSearchProducts.readyState != 4) {
          productContent_ajaxSearchProducts.abort();
          productContent_ajaxSearchProducts = null;
        }
      }
    }

    function productContent_clearSearchText() {
      $('#product-block-search input[name=\'product_block_search\']').val('');
    }



    function productContent_searchProducts() {
      var value = $('#product-block-search input[name=\'product_block_search\']').val();

      // might want to keep current category
      productContent_resetData({
        updatePath: false
      });
      productContent_resetVars();

      var data = productContent_getFilterData([]);

      productContent_ajaxSearchProducts = $.ajax({
        url: 'index.php?route=jpos/product/getPage&a=1',
        type: 'get',
        data: data.join('&'),
        dataType: 'json',
        beforeSend: function() {
          spinner('#ps-product-block .products_area', 'show');
        },
        complete: function() {
          spinner('#ps-product-block .products_area', 'hide');
        },
        success: function(json) {
          if (productContent_data.category_data.path && json['refine']) {
            $('.products_area .products_content').html(json['refine']);
            $('.products_area .products_content').append(json['products']);
          } else {
            $('.products_area .products_content').html(json['products']);
          }

          updateScrollbar($('.products_area.scrollert'));
        },
        error: function(xhr, ajaxOptions, thrownError) {
          console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
        }
      }).always(function(json,type,ajaxObject) {
        productContent_ajaxSearchProducts = null;
      });
    }

    $('#product-block-search input[name=\'product_block_search\']').first().attr('autocomplete', 'off').bind('keyup', function(e) {
      if (e.which == 38 || e.which == 40) return;

      if ($(this).is(':focus')) {
        productContent_abortSearchProductsAjax();
        keyTypeWatch(function () {
          productContent_searchProducts();
        }, 300);

      }
    });

    $('#product-block-search .clear_search').on('click', productContent_reloadProducts);


    $(document).delegate('.product_info', 'click', function() {
      var $item_info = $(this).parent().find('.item_info');
      var id = $item_info.attr('data-id');

      var $modal = $('#product-info');

      var data = [];
      data.push('product_id=' + id);

      $.ajax({
        url: 'index.php?route=jpos/product/getProduct&a=1',
        type: 'get',
        data: data.join('&'),
        dataType: 'json',
        beforeSend: function() {
        },
        complete: function() {
        },
        success: function(json) {
          $modal.find('.modal-header h3').html(json['output']['data']['product']['name']);
          $modal.find('.modal-body').html(json['output']['html']);
          $modal.modal('show');
        },
        error: function(xhr, ajaxOptions, thrownError) {
          console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
        }
      });
    });

    $(document).delegate('.item_info', 'click', function() {

      var id =  $(this).attr('data-id');
      var minimum =  $(this).attr('data-minimum');
      var hasoptions =  $(this).attr('data-options');

      $('.item_info').removeClass('active');
      $(this).addClass('active');

      var $modal = $('#product-info');

    	var data = [];
    	data.push('product_id=' + id);

      if(hasoptions > 0) {
      	$.ajax({
	        url: 'index.php?route=jpos/product/getProduct&a=1',
	        type: 'get',
	        data: data.join('&'),
	        dataType: 'json',
	        beforeSend: function() {
	        },
	        complete: function() {
	        },
	        success: function(json) {
	          $modal.find('.modal-header h3').html(json['output']['data']['product']['name']);
	          $modal.find('.modal-body').html(json['output']['html']);
	          $modal.modal('show');
	        },
	        error: function(xhr, ajaxOptions, thrownError) {
	          console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
	        }
      	});
  		} else {
  			poscart.quickcartadd(id, minimum);
  		}
    });


    $('#product-info').on('show.bs.modal', function() {
      console.log('before product_info modal show')
      updateDateTimePicker();
    });
    $('#product-info').on('shown.bs.modal', function() {
      console.log('product_info modal show')
    });
    $('#product-info').on('hidden.bs.modal', function() {
      console.log('product_info modal hide')
    });

    function productContent_resetData(options) {
      options = $.extend({
        page : 1,
        path : '',
        updatePage : true,
        updatePath : true,
      },options);
      if (options.updatePage) {
        // set page to 1,
        productContent_data.product_data.page = options.page;
      }
      if (options.updatePath) {
        // set path to empty
        productContent_data.category_data.path = options.path;
      }
    }


    function productContent_getFilterData(data) {
      data = data || [];
      if (!productContent_data.product_data.page) {
        productContent_data.product_data.page = 1;
      }
      data.push('page=' + productContent_data.product_data.page);

      if (productContent_data.category_data.path != '') {
        data.push(productContent_data.category_data.path);
      }

      var value = $('#product-block-search input[name=\'product_block_search\']').val();
      if (value) {
        data.push('search=' + encodeURIComponent(value));
      }

      return data;
    }

    function productContent_reloadProducts() {
      productContent_clearSearchText();
      productContent_searchProducts();
    }

    // product wrap ends


    // Customer List wrap starts

    // if we not declare var, the particular variable refer to window or says global variable and can be access outside function as as well.
    customerContent_data = {
      customer_data : {
        page : 1,
      },
    };

    // scroll starts

    var customerContent_lastScrollTop = 0;
    var customerContent_ajaxGetCustomers = null;
    var customerContent_getAjaxCustomers = true;
    setTimeout(function() {


    $('.customers_area .customers_content').on('scroll', function() {
      // alert("alla a");
      // console.log("customerContent height: " + $(this).height());
      // console.log("customerContent innerHeight: " + $(this).innerHeight());
      // console.log("customerContent outerHeight: " + $(this).outerHeight());
      // // console.log($(this).offset());
      // console.log("customerContent scrollTop :" + $(this).scrollTop());
      // console.log("customerContent scrollLeft :" + $(this).scrollLeft());
      // console.log("customerContent scrollHeight :" + $(this)[0].scrollHeight);
      // console.log("customerContent scrollWidth :" + $(this)[0].scrollWidth);
      // console.log("customerContent productContent_lastScrollTop : " + productContent_lastScrollTop);
      // console.log("customerContent ass");

      if (customerContent_lastScrollTop > $(this).scrollTop()) {
        console.log("customerContent going upp");
      } else {
        console.log("customerContent going down");

        if ($(this).scrollTop() + $(this).height() > $(this)[0].scrollHeight - 100) {
          if (customerContent_ajaxGetCustomers == null && customerContent_getAjaxCustomers == true) {

            customerContent_data.customer_data.page = customerContent_data.customer_data.page + 1;

            var data = customerContent_getFilterData([]);


            customerContent_getAjaxCustomers = false;

            customerContent_ajaxGetCustomers = $.ajax({
              url: 'index.php?route=jpos/customer/getPage&a=1&ev=scroll',
              type: 'get',
              data: data.join('&'),
              dataType: 'json',
              beforeSend: function() {

              },
              complete: function() {

              },
              success: function(json) {
                if (json['customers']) {
                  $('.customers_area .customers_content > ul').append(json['customers']);

                  updateScrollbar($('.customers_area.scrollert'));
                } else {
                   customerContent_getAjaxCustomers = false;
                  // revert back to previous page
                  customerContent_data.customer_data.page = customerContent_data.customer_data.page - 1;
                }
                console.log("customerContent_ajaxGetCustomers");
                console.log(customerContent_ajaxGetCustomers);
              },
              error: function(xhr, ajaxOptions, thrownError) {
                console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
              }
            }).always(function(json,type,ajaxObject) {
              console.log("customerContent this is promise by ajax and always run")
              customerContent_ajaxGetCustomers = null;
              // console.log("customerContent json")
              // console.log(json)
              // console.log("customerContent type")
              // console.log(type)
              // console.log("customerContent ajaxObject")
              // console.log(ajaxObject)
              if (json['customers']) {
                customerContent_getAjaxCustomers = true;
              } else {
                customerContent_getAjaxCustomers = false;
              }
            });
          }
        }
      }

      customerContent_lastScrollTop = $(this).scrollTop();

    })
    },500);
    // $('.products_area .scrollert-content')[0].scrollHeight
    // $('.products_area .scrollert-content').prop('scrollHeight')
    // $(".products_area .scrollert-content").animate({ scrollTop: "1000px" });
    // https://www.npmjs.com/package/scrollert#advanced-usage
    // https://stackoverflow.com/questions/22675126/what-is-offsetheight-clientheight-scrollheight
    /*update

    To update the scrollbars. This is necessary when the dimensions of the content element are changed due to DOM or changes.

    $('.scrollert').scrollert('destroy');
    $('.scrollert').scrollert('update');
    */
    // scroll ends

    // search in customers
    function customerContent_resetVars() {
      // reset on scroll ajax variables to default
      customerContent_ajaxGetCustomers = null;
      customerContent_getAjaxCustomers = true;
    }

    var customerContent_ajaxSearchCustomers = null;

    function customerContent_abortSearchCustomersAjax() {
      if (customerContent_ajaxSearchCustomers) {
        if (customerContent_ajaxSearchCustomers.readyState != 4) {
          customerContent_ajaxSearchCustomers.abort();
          customerContent_ajaxSearchCustomers = null;
        }
      }
    }

    function customerContent_clearSearchText() {
      $('#customer-block-search input[name=\'customer_block_search\']').val('');
    }



    function customerContent_searchCustomers() {
      var value = $('#customer-block-search input[name=\'customer_block_search\']').val();

      customerContent_resetData();
      customerContent_resetVars();

      var data = customerContent_getFilterData([]);

      customerContent_ajaxSearchCustomers = $.ajax({
        url: 'index.php?route=jpos/customer/getPage&a=1',
        type: 'get',
        data: data.join('&'),
        dataType: 'json',
        beforeSend: function() {
        },
        complete: function() {
        },
        success: function(json) {
          $('.customers_area .customers_content > ul').html(json['customers']);

          updateScrollbar($('.customers_area.scrollert'));
        },
        error: function(xhr, ajaxOptions, thrownError) {
          console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
        }
      }).always(function(json,type,ajaxObject) {
        customerContent_ajaxSearchCustomers = null;
      });
    }

    $('#customer-block-search input[name=\'customer_block_search\']').first().attr('autocomplete', 'off').bind('keyup', function(e) {
      if (e.which == 38 || e.which == 40) return;

      if ($(this).is(':focus')) {
        customerContent_abortSearchCustomersAjax();
        keyTypeWatch(function () {
          customerContent_searchCustomers();
        }, 300);

      }
    });

    $('#customer-block-search .clear_search').on('click', function(){
      customerContent_clearSearchText();
      customerContent_searchCustomers();
    });

    $(document).delegate('.customer_info', 'click', function() {

      var id =  $(this).attr('data-id');

      $('.customer_info').removeClass('active');
      $(this).addClass('active');

      var $target = $('#ps-customer-detail .customer-detail');

      var data = [];
      data.push('customer_id=' + id);
      $.ajax({
        url: 'index.php?route=jpos/customer/getCustomer&a=1',
        type: 'get',
        data: data.join('&'),
        dataType: 'json',
        beforeSend: function() {
          spinner('#ps-customer-detail', 'show');
        },
        complete: function() {
          spinner('#ps-customer-detail', 'hide');
        },
        success: function(json) {
          if (json['redirect']) {
            location = json['redirect'];
          }

          $target.html(json['output']['html']);
          $target.trigger('jpos.content.getfill');
        },
        error: function(xhr, ajaxOptions, thrownError) {
          console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
        }
      });
    });

    function customerContent_resetData(options) {
      options = $.extend({
        page : 1,
        updatePage : true,
      },options);
      if (options.updatePage) {
        // set page to 1,
        customerContent_data.customer_data.page = options.page;
      }
    }

    function customerContent_getFilterData(data) {
      data = data || [];
      if (!customerContent_data.customer_data.page) {
        customerContent_data.customer_data.page = 1;
      }
      data.push('page=' + customerContent_data.customer_data.page);

      var value = $('#customer-block-search input[name=\'customer_block_search\']').val();
      if (value) {
        data.push('search=' + encodeURIComponent(value));
      }

      return data;
    }

    $(document).delegate('.form_customer_edit', 'click', function() {
      var el = this;
      var $i = $(this).find('i');
      var id =  $(this).attr('data-id');
      var $target = $('#ps-customer-detail .customer-detail-edit');
      var data = [];
      data.push('customer_id=' + id);
      data.push('action=' + 1);
      $.ajax({
        url: 'index.php?route=jpos/customer/editCustomerForm&a=1',
        type: 'get',
        data: data.join('&'),
        dataType: 'json',
        beforeSend: function() {
          $i.attr('class', 'fa fa-spinner fa-spin');
          $(el).attr('disabled', 'disabled');
          spinner('#ps-customer-detail', 'show');
        },
        complete: function() {
          $i.attr('class', $i.attr('data-class'));
          $(el).removeAttr('disabled');
          spinner('#ps-customer-detail', 'hide');
        },
        success: function(json) {
          $target.html(json['output']['html']);
          $target.trigger('jpos.content.getfill');

          if(typeof $('.edit-customer-form.scrollert').data('pluginScrollert') != 'undefined') {
            updateScrollbar($('.edit-customer-form.scrollert'));
          } else {
            initScrollbar($('.edit-customer-form.scrollert'));
          }

        },
        error: function(xhr, ajaxOptions, thrownError) {
          console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
        }
      });
    });

    $(document).delegate('.customer_editsave', 'click', function() {
      var el = this;
      var $i = $(this).find('i');
      var id =  $(this).attr('data-id');
      var $el = $(this);
      $.ajax({
        url: 'index.php?route=jpos/customer/editCustomerAccount',
        type: 'post',
        data: $('.edit-customer-form input[type=\'text\'], .edit-customer-form input[type=\'hidden\'], .edit-customer-form input[type=\'radio\']:checked, .edit-customer-form input[type=\'checkbox\']:checked, .edit-customer-form select, .edit-customer-form textarea').serialize() + '&customer_id='+ id,
        dataType: 'json',
        beforeSend: function() {
          $i.attr('class', 'fa fa-spinner fa-spin');
          $(el).attr('disabled', 'disabled');
        },
        complete: function() {
          $i.attr('class', $i.attr('data-class'));
          $(el).removeAttr('disabled');
        },
        success: function(json) {
          if (json['redirect']) {
            location = json['redirect'];
          }

          $('.edit-customer-form .alert, .edit-customer-form .text-danger').remove();
          $('.edit-customer-form .form-group').removeClass('has-error');

          $('.notify-message').remove('');

          if (json['error']) {
            if (json['error']['warning']) {
             $('#ps-customer-list .customer-detail-edit .edit-customer-form').prepend('<div class="fly-message notify-message alert alert-danger"><i class="fa fa-exclamation-circle"></i> '+ json['error']['warning'] +'<button type="button" class="close" data-dismiss="alert">&times;</button></div>');

              removeNotifyMessage();
            }

            // $('#ps-customer-list .customer-detail-edit .edit-customer-form')
            for (i in json['error']) {
              var element = $('.edit-customer-form #input-' + i.replace('_', '-'));

              if (element.parent().hasClass('input-group')) {
                element.parent().after('<div class="text-danger">' + json['error'][i] + '</div>');
              } else {
                element.after('<div class="text-danger">' + json['error'][i] + '</div>');
              }
            }

            if (json['error']['custom_field']) {
              for (i in json['error']['custom_field']) {
                var element = $('.edit-customer-form #input-custom-field' + i.replace('_', '-'));

                if (element.parent().hasClass('input-group')) {
                  element.parent().after('<div class="text-danger">' + json['error']['custom_field'][i] + '</div>');
                } else {
                  element.after('<div class="text-danger">' + json['error']['custom_field'][i] + '</div>');
                }
              }
            }

            // Highlight any found errors
            $('.edit-customer-form .text-danger').parent().parent().addClass('has-error');
          }

          if (json['success']) {
            $('#ps-customer-list .customer-detail-edit .edit-customer-form').prepend('<div class="fly-message notify-message alert alert-success"><i class="fa fa-check-circle"></i> '+ json['success'] +'<button type="button" class="close" data-dismiss="alert">&times;</button></div>');

            setTimeout(function() {
              $el.parent().find('.content_destroy').trigger('click');

              $('.notify-message').remove();
            }, 3000);
          }

        },
        error: function(xhr, ajaxOptions, thrownError) {
          console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
        }
      });
    });

    // Customer Country
    $(document).delegate('.edit-customer-form select[name=\'country_id\']', 'change', function() {
      $.ajax({
        url: 'index.php?route=jpos/customer/country&country_id=' + this.value,
        dataType: 'json',
        beforeSend: function() {
          $('.edit-customer-form select[name=\'country_id\']').after(' <i class="fa fa-circle-o-notch fa-spin"></i>');
        },
        complete: function() {
          $('.edit-customer-form .fa-spin').remove();
        },
        success: function(json) {
          var zone_id = $('.edit-customer-form .select-zone').text();
          if (json['postcode_required'] == '1') {
            $('.edit-customer-form input[name=\'postcode\']').parent().parent().addClass('required');
          } else {
            $('.edit-customer-form input[name=\'postcode\']').parent().parent().removeClass('required');
          }

          html = '<option value=""><?php echo $text_select; ?></option>';

          if (json['zone'] && json['zone'] != '') {
            for (i = 0; i < json['zone'].length; i++) {
              html += '<option value="' + json['zone'][i]['zone_id'] + '"';

              if (json['zone'][i]['zone_id'] == zone_id) {
                html += ' selected="selected"';
              }

              html += '>' + json['zone'][i]['name'] + '</option>';
            }
          } else {
            html += '<option value="0" selected="selected"><?php echo $text_none; ?></option>';
          }

          $('.edit-customer-form select[name=\'zone_id\']').html(html);
        }
      });
    });

    $(document).delegate('.customer_addsave', 'click', function() {
      var $el = $(this);
      $.ajax({
        url: 'index.php?route=jpos/customer/addCustomerAccount',
        type: 'post',
        data: $('.add-customer-form input[type=\'text\'], .add-customer-form input[type=\'hidden\'], .add-customer-form input[type=\'radio\']:checked, .add-customer-form input[type=\'checkbox\']:checked, .add-customer-form select, .add-customer-form textarea, .add-customer-form input[type=\'password\']').serialize(),
        dataType: 'json',
        beforeSend: function() {
          $('.customer_addsave').button('loading');
        },
        complete: function() {
          $('.customer_addsave').button('reset');
        },
        success: function(json) {
          $('.add-customer-form .alert, .add-customer-form .text-danger').remove();
          $('.add-customer-form .form-group').removeClass('has-error');

          $('.notify-message').remove('');

          if (json['redirect']) {
            location = json['redirect'];
          }

          if (json['error']) {
            if (json['error']['warning']) {
              $('#adduser').prepend('<div class="notify-message alert alert-danger"><i class="fa fa-exclamation-circle"></i> '+ json['error']['warning'] +'<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
            }

            // $('#ps-customer-list .customer-detail-edit .edit-customer-form')
            for (i in json['error']) {
              var element = $('.add-customer-form #input-' + i.replace('_', '-'));

              if (element.parent().hasClass('input-group')) {
                element.parent().after('<div class="text-danger">' + json['error'][i] + '</div>');
              } else {
                element.after('<div class="text-danger">' + json['error'][i] + '</div>');
              }
            }

            if (json['error']['custom_field']) {
              for (i in json['error']['custom_field']) {
                var element = $('.add-customer-form #input-custom-field' + i.replace('_', '-'));

                if (element.parent().hasClass('input-group')) {
                  element.parent().after('<div class="text-danger">' + json['error']['custom_field'][i] + '</div>');
                } else {
                  element.after('<div class="text-danger">' + json['error']['custom_field'][i] + '</div>');
                }
              }
            }

            // Highlight any found errors
            $('.add-customer-form .text-danger').parent().parent().addClass('has-error');
          }

          if (json['success']) {
            $('#inner-shopping-cart').after('<div class="notify-message alert alert-success"><i class="fa fa-check-circle"></i> '+ json['success'] +'<button type="button" class="close" data-dismiss="alert">&times;</button></div>');

            // Trigger click user panel close
            $('#adduser .close-adduser').trigger('click');

            // Cart Reload
            poscart.reloadcart();

            // reload all products section here
            productContent_reloadProducts();

            // reload customer list section here
            customerContent_searchCustomers();
          }

          removeNotifyMessage();

        },
        error: function(xhr, ajaxOptions, thrownError) {
          console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
        }
      });
    });

    $(document).delegate('.guest_addsave', 'click', function() {
      var $el = $(this);
      $.ajax({
        url: 'index.php?route=jpos/customer/addGuest',
        type: 'post',
        data: $('.add-customer-form input[type=\'text\'], .add-customer-form input[type=\'hidden\'], .add-customer-form input[type=\'radio\']:checked, .add-customer-form input[type=\'checkbox\']:checked, .add-customer-form select, .add-customer-form textarea, .add-customer-form input[type=\'password\']').serialize(),
        dataType: 'json',
        beforeSend: function() {
          $('.guest_addsave').button('loading');
        },
        complete: function() {
          $('.guest_addsave').button('reset');
        },
        success: function(json) {
          $('.add-customer-form .alert, .add-customer-form .text-danger').remove();
          $('.add-customer-form .form-group').removeClass('has-error');

          $('.notify-message').remove('');

          if (json['error']) {
            if (json['error']['warning']) {
              $('#inner-shopping-cart').after('<div class="notify-message alert alert-danger"><i class="fa fa-exclamation-circle"></i> '+ json['error']['warning'] +'<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
            }

            // $('#ps-customer-list .customer-detail-edit .edit-customer-form')
            for (i in json['error']) {
              var element = $('.add-customer-form #input-' + i.replace('_', '-'));

              if (element.parent().hasClass('input-group')) {
                element.parent().after('<div class="text-danger">' + json['error'][i] + '</div>');
              } else {
                element.after('<div class="text-danger">' + json['error'][i] + '</div>');
              }
            }

            if (json['error']['custom_field']) {
              for (i in json['error']['custom_field']) {
                var element = $('.add-customer-form #input-custom-field' + i.replace('_', '-'));

                if (element.parent().hasClass('input-group')) {
                  element.parent().after('<div class="text-danger">' + json['error']['custom_field'][i] + '</div>');
                } else {
                  element.after('<div class="text-danger">' + json['error']['custom_field'][i] + '</div>');
                }
              }
            }

            // Highlight any found errors
            $('.add-customer-form .text-danger').parent().parent().addClass('has-error');
          }

          if (json['success']) {
            $('#inner-shopping-cart').after('<div class="notify-message alert alert-success"><i class="fa fa-check-circle"></i> '+ json['success'] +'<button type="button" class="close" data-dismiss="alert">&times;</button></div>');

            // Trigger click user panel close
            $('#adduser .close-adduser').trigger('click');

            // Cart Reload
            poscart.reloadcart();

            // reload all products section here
            productContent_reloadProducts();
          }

          removeNotifyMessage();
        },
        error: function(xhr, ajaxOptions, thrownError) {
          console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
        }
      });
    });

    // Customer Country
    $(document).delegate('.add-customer-form select[name=\'country_id\']', 'change', function() {
      $.ajax({
        url: 'index.php?route=jpos/customer/country&country_id=' + this.value,
        dataType: 'json',
        beforeSend: function() {
          $('.add-customer-form select[name=\'country_id\']').after(' <i class="fa fa-circle-o-notch fa-spin"></i>');
        },
        complete: function() {
          $('.add-customer-form .fa-spin').remove();
        },
        success: function(json) {
          var zone_id = $('.add-customer-form .select-zone').text();
          if (json['postcode_required'] == '1') {
            $('.add-customer-form input[name=\'postcode\']').parent().parent().addClass('required');
          } else {
            $('.add-customer-form input[name=\'postcode\']').parent().parent().removeClass('required');
          }

          html = '<option value=""><?php echo $text_select; ?></option>';

          if (json['zone'] && json['zone'] != '') {
            for (i = 0; i < json['zone'].length; i++) {
              html += '<option value="' + json['zone'][i]['zone_id'] + '"';

              if (json['zone'][i]['zone_id'] == zone_id) {
                html += ' selected="selected"';
              }

              html += '>' + json['zone'][i]['name'] + '</option>';
            }
          } else {
            html += '<option value="0" selected="selected"><?php echo $text_none; ?></option>';
          }

          $('.add-customer-form select[name=\'zone_id\']').html(html);
        }
      });
    });

    $('.add-customer-form select[name=\'country_id\']').trigger('change');



    // Asssign Customer To Cart
    $(document).delegate('#button-assigncustomer', 'click', function() {
      var id =  $(this).attr('data-id');
      var el = this;
      var $i = $(this).find('i');

      $.ajax({
        url: 'index.php?route=jpos/customer/assignCustomerToCart',
        type: 'post',
        data: 'customer_id='+ id,
        dataType: 'json',
        beforeSend: function() {
          $i.attr('class', 'fa fa-spinner fa-spin');
          $(el).attr('disabled', 'disabled');
          spinner('#ps-customer-detail', 'show');
        },
        complete: function() {
          $i.attr('class', $i.attr('data-class'));
          $(el).removeAttr('disabled');
          spinner('#ps-customer-detail', 'hide');
        },
        success: function(json) {
          if (json['success']) {
            // Close Customr info and list
            $('#ps-customer-list #ps-customer-detail .content_destroy').trigger('click');
            $('#ps-customer-list .close-customer-list').trigger('click');

              // Cart Reload
              poscart.reloadcart();

              // reload all products section here
              productContent_reloadProducts();
          }

        },
        error: function(xhr, ajaxOptions, thrownError) {
          console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
        }
      });
    });

    // Customer List wrap ends

    // Account setting wrap starts


    <?php if (!$user_logged) { ?>
    $('.login_panel').trigger('click');

     $('#ps-login input[name=\'username\'], #ps-login input[name=\'password\']').on('keydown', function(e) {
      if (e.keyCode == 13) {
        $('#ps-login .user_login').trigger('click');
      }
    });

    $(document).delegate('.user_login', 'click', function(){
     var data = $('#ps-login input').serialize();

     $('#ps-login .alert, #ps-login .text-danger').remove();

      $.ajax({
        url: 'index.php?route=jpos/user/login&a=1',
        type: 'post',
        data: data,
        dataType: 'json',
        beforeSend: function() {

        },
        complete: function() {

        },
        success: function(json) {
          if (json['error']) {
            if (typeof json['error']['warning'] != 'undefined' && json['error']['warning']) {
              $('#ps-login #ps-login-detail').before('<div class="alert alert-danger"><i class="fa fa-check-circle"></i> '+ json['error']['warning'] +' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
            }
          }
          if (json['redirect']) {
            window.location = json['redirect'];
          }

        },
        error: function(xhr, ajaxOptions, thrownError) {
          console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
        }
      });
    });
    <?php } ?>



    $(document).delegate('.user_logout', 'click', function(){
     var data = '';

     $('#ps-login .alert, #ps-login .text-danger').remove();

      $.ajax({
        url: 'index.php?route=jpos/user/logout&a=1',
        type: 'post',
        data: data,
        dataType: 'json',
        beforeSend: function() {

        },
        complete: function() {

        },
        success: function(json) {
          if (json['redirect']) {
            window.location = json['redirect'];
          }
        },
        error: function(xhr, ajaxOptions, thrownError) {
          console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
        }
      });
    });

    $(document).delegate('.user_update_info', 'click', function(){
      var data = $('#user-form input').serialize();

      var $el = $(this);

     $('#user-form .alert, #user-form .text-danger').remove();

      $.ajax({
        url: 'index.php?route=jpos/user/updateInfo&a=1',
        type: 'post',
        data: data,
        dataType: 'json',
        beforeSend: function() {

        },
        complete: function() {

        },
        success: function(json) {
          if (json['redirect']) {
            location = json['redirect'];
          }

          if (json['success']) {
            $el.before('<div class="alert alert-success"><i class="fa fa-check-circle"></i> '+ json['success'] +' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
          }
          setTimeout(function() {
            if (json['redirect']) {
              window.location = json['redirect'];
            }
          }, 2000);

          if (json['error']) {
            if (typeof json['error']['warning'] != 'undefined' && json['error']['warning']) {
              $el.before('<div class="alert alert-danger"><i class="fa fa-check-circle"></i> '+ json['error']['warning'] +' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
            }

            if (typeof json['error']['firstname'] != 'undefined' && json['error']['firstname']) {
              $('#user-form input[name="firstname"]').after('<div class="text-danger">'+ json['error']['firstname'] +'</div>');
            }
            if (typeof json['error']['lastname'] != 'undefined' && json['error']['lastname']) {
              $('#user-form input[name="lastname"]').after('<div class="text-danger">'+ json['error']['lastname'] +'</div>');
            }
            if (typeof json['error']['email'] != 'undefined' && json['error']['email']) {
              $('#user-form input[name="email"]').after('<div class="text-danger">'+ json['error']['email'] +'</div>');
            }
            if (typeof json['error']['password_previous'] != 'undefined' && json['error']['password_previous']) {
              $('#user-form input[name="password_previous"]').after('<div class="text-danger">'+ json['error']['password_previous'] +'</div>');
            }
            if (typeof json['error']['password'] != 'undefined' && json['error']['password']) {
              $('#user-form input[name="password"]').after('<div class="text-danger">'+ json['error']['password'] +'</div>');
            }
            if (typeof json['error']['confirm'] != 'undefined' && json['error']['confirm']) {
              $('#user-form input[name="password_confirm"]').after('<div class="text-danger">'+ json['error']['confirm'] +'</div>');
            }
          }
        },
        error: function(xhr, ajaxOptions, thrownError) {
          console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
        }
      });
    });

    // Account setting wrap ends

    // General setting wrap starts
    $(document).delegate('#user-general-form select[name=\'default_location\']', 'change', function() {
      var $el = $(this);
      $.ajax({
        url: 'index.php?route=jpos/user/locationInfo&a=1&location_id=' + this.value,
        dataType: 'json',
        beforeSend: function() {
          $el.after(' <i class="fa fa-circle-o-notch fa-spin"></i>');
        },
        complete: function() {
          $('.fa-spin').remove();
        },
        success: function(json) {

          var currency_html = '<option value=""><?php echo $text_select; ?></option>';

          var default_currency_id = $el.attr('data-currency_id');

          if (json['currency']) {
            for (var i in json['currency']) {
              currency_html += '<option value="' + json['currency'][i]['currency_id'] + '"';

              if (json['currency'][i]['currency_id'] == default_currency_id) {
                currency_html += ' selected="selected"';
                }

                currency_html += '>' + json['currency'][i]['title'] + '</option>';
            }
          } else {
            currency_html += '<option value="0" selected="selected"><?php echo $text_none; ?></option>';
          }

          $('#user-general-form select[name=\'default_currency\']').html(currency_html);

          var language_html = '<option value=""><?php echo $text_select; ?></option>';

          var default_language_id = $el.attr('data-language_id');

          if (json['language']) {
            for (var i in json['language']) {
              language_html += '<option value="' + json['language'][i]['language_id'] + '"';

              if (json['language'][i]['language_id'] == default_language_id) {
                language_html += ' selected="selected"';
                }

                language_html += '>' + json['language'][i]['name'] + '</option>';
            }
          } else {
            language_html += '<option value="0" selected="selected"><?php echo $text_none; ?></option>';
          }

          $('#user-general-form select[name=\'default_language\']').html(language_html);
        },
        error: function(xhr, ajaxOptions, thrownError) {
          alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
        }
      });
    });

    $('#user-general-form select[name=\'default_location\']').trigger('change');

    $(document).delegate('.user_update_general', 'click', function(){
      var data = $('#user-general-form select').serialize();

      var $el = $(this);

     $('#user-general-form .alert, #user-general-form .text-danger').remove();

      $.ajax({
        url: 'index.php?route=jpos/user/updateGeneral&a=1',
        type: 'post',
        data: data,
        dataType: 'json',
        beforeSend: function() {

        },
        complete: function() {

        },
        success: function(json) {
          if (json['redirect']) {
            location = json['redirect'];
          }

          if (json['success']) {
            $el.before('<div class="alert alert-success"><i class="fa fa-check-circle"></i> '+ json['success'] +' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
          }
          setTimeout(function() {
            if (json['redirect']) {
              window.location = json['redirect'];
            }
          }, 2000);

          if (json['error']) {
            if (typeof json['error']['warning'] != 'undefined' && json['error']['warning']) {
              $el.before('<div class="alert alert-danger"><i class="fa fa-check-circle"></i> '+ json['error']['warning'] +' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
            }

            if (typeof json['error']['default_location'] != 'undefined' && json['error']['default_location']) {
              $('#user-general-form select[name="default_location"]').after('<div class="text-danger">'+ json['error']['default_location'] +'</div>');
            }
            if (typeof json['error']['default_language'] != 'undefined' && json['error']['default_language']) {
              $('#user-general-form select[name="default_language"]').after('<div class="text-danger">'+ json['error']['default_language'] +'</div>');
            }
            if (typeof json['error']['default_currency'] != 'undefined' && json['error']['default_currency']) {
              $('#user-general-form select[name="default_currency"]').after('<div class="text-danger">'+ json['error']['default_currency'] +'</div>');
            }
          }
        },
        error: function(xhr, ajaxOptions, thrownError) {
          console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
        }
      });
    });

    // General setting wrap ends

    // Adduser wrap starts

    $(document).delegate('.add_customer', 'click', function(){

      var data = $('#customer-detail-form select, #customer-detail-form input').serialize();

      var $el = $(this);

      $('#customer-detail-form .alert, #customer-detail-form .text-danger').remove();

      if ($el.attr('data-action') == 'addCustomer') {
        var path = 'jpos/customer/addCustomer';
      } else {
        var path = 'jpos/customer/addCustomer';
      }

      $.ajax({
        url: 'index.php?route='+ path +'&a=1',
        type: 'post',
        data: data,
        dataType: 'json',
        beforeSend: function() {
        },
        complete: function() {
        },
        success: function(json) {

          if (json['success']) {
            $el.before('<div class="alert alert-success"><i class="fa fa-check-circle"></i> '+ json['success'] +' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
          }
          setTimeout(function() {
            if (json['redirect']) {
              window.location = json['redirect'];
            }
          }, 2000);

          if (json['error']) {

            if (typeof json['error']['warning'] != 'undefined' && json['error']['warning']) {
              $el.before('<div class="alert alert-danger"><i class="fa fa-check-circle"></i> '+ json['error']['warning'] +' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
            }

            if (typeof json['error']['firstname'] != 'undefined' && json['error']['firstname']) {
              $('#customer-detail-form input[name="firstname"]').after('<div class="text-danger">'+ json['error']['firstname'] +'</div>');
            }
            if (typeof json['error']['lastname'] != 'undefined' && json['error']['lastname']) {
              $('#customer-detail-form input[name="lastname"]').after('<div class="text-danger">'+ json['error']['lastname'] +'</div>');
            }
            if (typeof json['error']['email'] != 'undefined' && json['error']['email']) {
              $('#customer-detail-form input[name="email"]').after('<div class="text-danger">'+ json['error']['email'] +'</div>');
            }
            if (typeof json['error']['telephone'] != 'undefined' && json['error']['telephone']) {
              $('#customer-detail-form input[name="telephone"]').after('<div class="text-danger">'+ json['error']['telephone'] +'</div>');
            }
            if (typeof json['error']['address_1'] != 'undefined' && json['error']['address_1']) {
              $('#customer-detail-form input[name="address_1"]').after('<div class="text-danger">'+ json['error']['address_1'] +'</div>');
            }
            if (typeof json['error']['address_2'] != 'undefined' && json['error']['address_2']) {
              $('#customer-detail-form input[name="address_2"]').after('<div class="text-danger">'+ json['error']['address_2'] +'</div>');
            }
            if (typeof json['error']['city'] != 'undefined' && json['error']['city']) {
              $('#customer-detail-form input[name="city"]').after('<div class="text-danger">'+ json['error']['city'] +'</div>');
            }
            if (typeof json['error']['postcode'] != 'undefined' && json['error']['postcode']) {
              $('#customer-detail-form input[name="postcode"]').after('<div class="text-danger">'+ json['error']['postcode'] +'</div>');
            }
            if (typeof json['error']['country'] != 'undefined' && json['error']['country']) {
              $('#customer-detail-form select[name="country_id"]').after('<div class="text-danger">'+ json['error']['country'] +'</div>');
            }
            if (typeof json['error']['country'] != 'undefined' && json['error']['country']) {
              $('#customer-detail-form select[name="country_id"]').after('<div class="text-danger">'+ json['error']['country'] +'</div>');
            }
            if (typeof json['error']['zone'] != 'undefined' && json['error']['zone']) {
              $('#customer-detail-form select[name="zone_id"]').after('<div class="text-danger">'+ json['error']['zone'] +'</div>');
            }

            if ($el.attr('data-action') == 'addCustomer') {
              if (typeof json['error']['password'] != 'undefined' && json['error']['password']) {
                $('#customer-detail-form input[name="password"]').after('<div class="text-danger">'+ json['error']['password'] +'</div>');
              }
              if (typeof json['error']['confirm'] != 'undefined' && json['error']['confirm']) {
                $('#customer-detail-form input[name="password_confirm"]').after('<div class="text-danger">'+ json['error']['confirm'] +'</div>');
              }
            }

          }
        },
        error: function(xhr, ajaxOptions, thrownError) {
          console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
        }
      });
    });


    // Adduser wrap ends

    // Order List wrap starts

    // scroll starts
    orderContent_data = {
      order_data : {
        page : 1,
      },
    };
    var orderContent_lastScrollTop = 0;
    var orderContent_ajaxGetOrders = null;
    var orderContent_getAjaxOrders = true;
    setTimeout(function() {


    $('.orders_area .orders_content').on('scroll', function() {
      // alert("alla a");
      // console.log("orderContent height: " + $(this).height());
      // console.log("orderContent innerHeight: " + $(this).innerHeight());
      // console.log("orderContent outerHeight: " + $(this).outerHeight());
      // // console.log($(this).offset());
      // console.log("orderContent scrollTop :" + $(this).scrollTop());
      // console.log("orderContent scrollLeft :" + $(this).scrollLeft());
      // console.log("orderContent scrollHeight :" + $(this)[0].scrollHeight);
      // console.log("orderContent scrollWidth :" + $(this)[0].scrollWidth);
      // console.log("orderContent productContent_lastScrollTop : " + productContent_lastScrollTop);
      // console.log("orderContent ass");

      if (orderContent_lastScrollTop > $(this).scrollTop()) {
        console.log("orderContent going upp");
      } else {
        console.log("orderContent going down");

        if ($(this).scrollTop() + $(this).height() > $(this)[0].scrollHeight - 100) {
          if (orderContent_ajaxGetOrders == null && orderContent_getAjaxOrders == true) {

            orderContent_data.order_data.page = orderContent_data.order_data.page + 1;

            var data = orderContent_getFilterData([]);


            orderContent_getAjaxOrders = false;

            orderContent_ajaxGetOrders = $.ajax({
              url: 'index.php?route=jpos/orders/getPage&a=1&ev=scroll',
              type: 'get',
              data: data.join('&'),
              dataType: 'json',
              beforeSend: function() {

              },
              complete: function() {

              },
              success: function(json) {
                if (json['orders_lists']) {
                  $('.orders_area .orders_content').append(json['orders_lists']);

                  updateScrollbar($('.orders_area.scrollert'));
                } else {
                   orderContent_getAjaxOrders = false;
                  // revert back to previous page
                  orderContent_data.order_data.page = orderContent_data.order_data.page - 1;
                }
                console.log("orderContent_ajaxGetOrders");
                console.log(orderContent_ajaxGetOrders);
              },
              error: function(xhr, ajaxOptions, thrownError) {
                console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
              }
            }).always(function(json,type,ajaxObject) {
              console.log("orderContent this is promise by ajax and always run")
              orderContent_ajaxGetOrders = null;
              // console.log("orderContent json")
              // console.log(json)
              // console.log("orderContent type")
              // console.log(type)
              // console.log("orderContent ajaxObject")
              // console.log(ajaxObject)
              if (json['orders_lists']) {
                orderContent_getAjaxOrders = true;
              } else {
                orderContent_getAjaxOrders = false;
              }
            });
          }
        }
      }

      orderContent_lastScrollTop = $(this).scrollTop();

    })
    },500);
    // $('.products_area .scrollert-content')[0].scrollHeight
    // $('.products_area .scrollert-content').prop('scrollHeight')
    // $(".products_area .scrollert-content").animate({ scrollTop: "1000px" });
    // https://www.npmjs.com/package/scrollert#advanced-usage
    // https://stackoverflow.com/questions/22675126/what-is-offsetheight-clientheight-scrollheight
    /*update

    To update the scrollbars. This is necessary when the dimensions of the content element are changed due to DOM or changes.

    $('.scrollert').scrollert('destroy');
    $('.scrollert').scrollert('update');
    */
    // scroll ends

    // search in customers
    function orderContent_resetVars() {
      // reset on scroll ajax variables to default
      orderContent_ajaxGetOrders = null;
      orderContent_getAjaxOrders = true;
    }

    var orderContent_ajaxSearchOrders = null;

    function orderContent_abortSearchOrdersAjax() {
      if (orderContent_ajaxSearchOrders) {
        if (orderContent_ajaxSearchOrders.readyState != 4) {
          orderContent_ajaxSearchOrders.abort();
          orderContent_ajaxSearchOrders = null;
        }
      }
    }

    function orderContent_clearSearchText() {
      $('#orders-block-search input[name=\'orders_block_search\']').val('');
    }


    window['orderContent_searchOrders'] = function () {
      var value = $('#orders-block-search input[name=\'customer_block_search\']').val();

      orderContent_resetData();
      orderContent_resetVars();

      var data = orderContent_getFilterData([]);

      orderContent_ajaxSearchOrders = $.ajax({
        url: 'index.php?route=jpos/orders/getPage&a=1',
        type: 'get',
        data: data.join('&'),
        dataType: 'json',
        beforeSend: function() {
        },
        complete: function() {
        },
        success: function(json) {
          $('.orders_area .orders_content').html(json['orders_lists']);

          updateScrollbar($('.orders_area.scrollert'));
        },
        error: function(xhr, ajaxOptions, thrownError) {
          console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
        }
      }).always(function(json,type,ajaxObject) {
        orderContent_ajaxSearchOrders = null;
      });
    };

    $('#orders-block-search input[name=\'orders_block_search\']').first().attr('autocomplete', 'off').bind('keyup', function(e) {
      if (e.which == 38 || e.which == 40) return;

      if ($(this).is(':focus')) {
        orderContent_abortSearchOrdersAjax();
        keyTypeWatch(function () {
          orderContent_searchOrders();
        }, 300);

      }
    });

    $('#orders-block-search .clear_search').on('click', function(){
      orderContent_clearSearchText();
      orderContent_searchOrders();
    });

    $(document).delegate('.order_info', 'click', function() {

      var id =  $(this).attr('data-id');

      $('.order_info').removeClass('active');
      $(this).addClass('active');

      var $target = $('#ps-order-detail .order-detail');

      var data = [];
      data.push('order_id=' + id);
      $.ajax({
        url: 'index.php?route=jpos/orders/getOrder&a=1',
        type: 'get',
        data: data.join('&'),
        dataType: 'json',
        beforeSend: function() {
          spinner('#ps-order-detail', 'show');
        },
        complete: function() {
          spinner('#ps-order-detail', 'hide');
        },
        success: function(json) {
          if (json['redirect']) {
            location = json['redirect'];
          }

          $target.html(json['output']['html']);
          $target.trigger('jpos.content.getfill');

         if(typeof $('.order-status.scrollert').data('pluginScrollert') != 'undefined') {
            updateScrollbar($('.order-status.scrollert'));
          } else {
            initScrollbar($('.order-status.scrollert'));
          }
        },
        error: function(xhr, ajaxOptions, thrownError) {
          console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
        }
      });
    });

    function orderContent_resetData(options) {
      options = $.extend({
        page : 1,
        updatePage : true,
      },options);
      if (options.updatePage) {
        // set page to 1,
        orderContent_data.order_data.page = options.page;
      }
    }

    function orderContent_getFilterData(data) {
      data = data || [];
      if (!orderContent_data.order_data.page) {
        orderContent_data.order_data.page = 1;
      }
      data.push('page=' + orderContent_data.order_data.page);

      var value = $('#orders-block-search input[name=\'orders_block_search\']').val();
      if (value) {
        data.push('search=' + encodeURIComponent(value));
      }

      // additonal filter is order_statuses
      $('.order_statuses input[type="checkbox"]:checked')

      $('.order_statuses input[type="checkbox"]:checked').each(function() {
        data.push($(this).attr('name') + '=' + encodeURIComponent(this.value));
      });

      return data;
    }

    $(document).delegate('.order_block_order_status', 'change', function() {

      $('.order_info').removeClass('active');

      // $('.order_statuses .l_orderstatus').removeClass('active');
      if ($(this).is(':checked')) {
        $(this).closest('.l_orderstatus').addClass('active');
      } else {
        $(this).closest('.l_orderstatus').removeClass('active');
      }

      var $target = $('#ps-order-detail .order-detail');

      orderContent_resetData();
      orderContent_resetVars();

      var data = orderContent_getFilterData([]);

      orderContent_ajaxSearchOrders = $.ajax({
        url: 'index.php?route=jpos/orders/getPage&a=1',
        type: 'get',
        data: data.join('&'),
        dataType: 'json',
        beforeSend: function() {
        },
        complete: function() {
        },
        success: function(json) {
          $('.orders_area .orders_content').html(json['orders_lists']);

          updateScrollbar($('.orders_area.scrollert'));
        },
        error: function(xhr, ajaxOptions, thrownError) {
          console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
        }
      }).always(function(json,type,ajaxObject) {
        orderContent_ajaxSearchOrders = null;
      });
    });


    // Order List wrap ends

    // Shopping cart column starts
    // Upate Cart Calling
    $(document).delegate('.cartupdate', 'click', function() {
      var cartid = $(this).attr('data-cartid');
      var quantity = $('input[name=\'cart_qty['+ cartid +']\']').val();

      poscart.update(cartid, quantity);
    });

    // Cart add remove functions
    poscart = {
      'cartadd': function() {
        $.ajax({
          url: 'index.php?route=jpos/shopping_kart/add',
          type: 'post',
          data: $('#product-info input[type=\'text\'], #product-info input[type=\'hidden\'], #product-info input[type=\'radio\']:checked, #product-info input[type=\'checkbox\']:checked, #product-info select, #product-info textarea'),
          dataType: 'json',
          beforeSend: function() {
            $('.button-cartadd').button('loading');
            // show cart loader
            $('.cart-process').removeClass('hide');
          },
          complete: function() {
          $('.button-cartadd').button('reset');
          },
          success: function(json) {
            $('.notify-message').remove();
            $('#product-info .alert, #product-info .text-danger').remove();
            $('#product-info .form-group').removeClass('has-error');

            if (json['redirect']) {
                location = json['redirect'];
            }

            if (json['error']) {
              if (json['error']['option']) {
                for (i in json['error']['option']) {
                  var element = $('#product-info #input-option' + i.replace('_', '-'));

                  if (element.parent().hasClass('input-group')) {
                    element.parent().after('<div class="text-danger">' + json['error']['option'][i] + '</div>');
                  } else {
                    element.after('<div class="text-danger">' + json['error']['option'][i] + '</div>');
                  }
                }
              }

              if (json['error']['recurring']) {
                $('#product-info select[name=\'recurring_id\']').after('<div class="text-danger">' + json['error']['recurring'] + '</div>');
              }

              // Highlight any found errors
              $('#product-info .text-danger').parent().addClass('has-error');
            }


            if (json['warning']) {
               $('#inner-shopping-cart').after('<div class="notify-message alert alert-danger"><i class="fa fa-exclamation-circle"></i> '+ json['warning'] +'<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
            }

            if (json['success']) {
              $('#inner-shopping-cart').after('<div class="notify-message alert alert-success"><i class="fa fa-check-circle"></i> '+ json['success'] +'<button type="button" class="close" data-dismiss="alert">&times;</button></div>');

              // Hide Product Info Modal
              $('#product-info').modal('hide');

              // Cart Reload
              poscart.reloadcartWithLoader();
            }

            removeNotifyMessage();
          },
          error: function(xhr, ajaxOptions, thrownError) {
            alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
          }
        });
      },
      'quickcartadd': function(product_id, quantity) {
        $.ajax({
          url: 'index.php?route=jpos/shopping_kart/add',
          type: 'post',
          data: 'product_id='+ product_id + '&quantity='+ quantity,
          dataType: 'json',
          beforeSend: function() {
          	$('.cart-process').removeClass('hide');
          },
          complete: function() {
          },
          success: function(json) {
            $('.notify-message').remove();

            if (json['redirect']) {
                location = json['redirect'];
            }

            if (json['warning']) {
               $('#inner-shopping-cart').after('<div class="notify-message alert alert-danger"><i class="fa fa-exclamation-circle"></i> '+ json['warning'] +'<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
            }

            if (json['success']) {
              $('#inner-shopping-cart').after('<div class="notify-message alert alert-success"><i class="fa fa-check-circle"></i> '+ json['success'] +'<button type="button" class="close" data-dismiss="alert">&times;</button></div>');

              // Hide Product Info Modal
              $('#product-info').modal('hide');

              // Cart Reload
              poscart.reloadcartWithLoader();
            }

            removeNotifyMessage();
          },
          error: function(xhr, ajaxOptions, thrownError) {
            alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
          }
        });
      },
      'reloadcart': function() {
        $.ajax({
          url: 'index.php?route=jpos/shopping_kart/reloadcart',
          type: 'post',
          data: '',
          dataType: 'html',
          beforeSend: function() {
            $('.button-testamentcheckout, #button-confirm').attr('disabled', true);
          },
          complete: function() {
          },
          success: function(html) {
            $('#inner-shopping-cart').html(html);

            // Update Scroll Products
            if(typeof $('.cart_products-wrap.scrollert').data('pluginScrollert') != 'undefined') {
              updateScrollbar($('.cart_products-wrap.scrollert'));
            } else {
              initScrollbar($('.cart_products-wrap.scrollert'));
            }

            // Update Scroll Totals
            if(typeof $('.cart-intotal.scrollert').data('pluginScrollert') != 'undefined') {
              updateScrollbar($('.cart-intotal.scrollert'));
            } else {
              initScrollbar($('.cart-intotal.scrollert'));
            }

            if($('#ps-checkout').hasClass('active')) {
              poscart.validatecart();
            }
          }
        });
      },
      'reloadcartWithLoader': function() {
        $.ajax({
          url: 'index.php?route=jpos/shopping_kart/reloadcart',
          type: 'post',
          data: '',
          dataType: 'html',
          beforeSend: function() {
            $('.button-testamentcheckout, #button-confirm').attr('disabled', true);

            $('.cart-process').removeClass('hide');
          },
          complete: function() {
          	$('.cart-process').addClass('hide');
          },
          success: function(html) {
            $('#inner-shopping-cart').html(html);

            // Update Scroll Products
            if(typeof $('.cart_products-wrap.scrollert').data('pluginScrollert') != 'undefined') {
              updateScrollbar($('.cart_products-wrap.scrollert'));
            } else {
              initScrollbar($('.cart_products-wrap.scrollert'));
            }

            // Update Scroll Totals
            if(typeof $('.cart-intotal.scrollert').data('pluginScrollert') != 'undefined') {
              updateScrollbar($('.cart-intotal.scrollert'));
            } else {
              initScrollbar($('.cart-intotal.scrollert'));
            }

            if($('#ps-checkout').hasClass('active')) {
              poscart.validatecart();
            }
          }
        });
      },
      'update': function(key, quantity) {
        $.ajax({
          url: 'index.php?route=jpos/shopping_kart/update',
          type: 'post',
          data: 'key=' + key + '&quantity=' + (typeof(quantity) != 'undefined' ? quantity : 1),
          dataType: 'json',
          beforeSend: function() {
            $('.button-testamentcheckout, #button-confirm').attr('disabled', true);

            $('.cart-process').removeClass('hide');
          },
          complete: function() {
          },
          success: function(json) {
            $('.notify-message').remove();

            if(json['error']) {
              $('#inner-shopping-cart').after('<div class="notify-message alert alert-danger"><i class="fa fa-exclamation-circle"></i> '+ json['error'] +'<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
            }

            if(json['success']) {
              if(!$('#ps-checkout').hasClass('active')) {
                $('#inner-shopping-cart').after('<div class="notify-message alert alert-success"><i class="fa fa-check-circle"></i> '+ json['success'] +'<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
              }

              // Cart Reload
              poscart.reloadcartWithLoader();
            }

            removeNotifyMessage();
          },
          error: function(xhr, ajaxOptions, thrownError) {
            alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
          }
        });
      },
      'remove': function(key) {
        $.ajax({
          url: 'index.php?route=jpos/shopping_kart/remove',
          type: 'post',
          data: 'key=' + key,
          dataType: 'json',
          beforeSend: function() {
            $('.button-testamentcheckout, #button-confirm').attr('disabled', true);

            $('.cart-process').removeClass('hide');
          },
          complete: function() {
          },
          success: function(json) {
            $('.notify-message').remove();

            if(json['error']) {
              $('#inner-shopping-cart').after('<div class="notify-message alert alert-danger"><i class="fa fa-exclamation-circle"></i> '+ json['error'] +'<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
            }

            if(json['success']) {
              if(!$('#ps-checkout').hasClass('active')) {
                $('#inner-shopping-cart').after('<div class="notify-message alert alert-success"><i class="fa fa-check-circle"></i> '+ json['success'] +'<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
              }

              // Cart Reload
              poscart.reloadcartWithLoader();
            }

            removeNotifyMessage();
          },
          error: function(xhr, ajaxOptions, thrownError) {
            alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
          }
        });
      },
      'clear': function() {
        $.ajax({
          url: 'index.php?route=jpos/shopping_kart/clear',
          type: 'post',
          dataType: 'json',
          beforeSend: function() {
            $('.button-testamentcheckout, #button-confirm').attr('disabled', true);
          },
          complete: function() {
          },
          success: function(json) {
            $('.notify-message').remove();

            if(json['success']) {
              $('#inner-shopping-cart').after('<div class="notify-message alert alert-success"><i class="fa fa-check-circle"></i> '+ json['success'] +'<button type="button" class="close" data-dismiss="alert">&times;</button></div>');

              // Cart Reload
              poscart.reloadcart();
            }

            removeNotifyMessage();
          },
          error: function(xhr, ajaxOptions, thrownError) {
            alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
          }
        });
      },
      'validatecartByButton': function() {
        $.ajax({
          url: 'index.php?route=jpos/shopping_kart/validatecart',
          type: 'post',
          dataType: 'json',
          beforeSend: function() {
            $('.button-testamentcheckout, #button-confirm').attr('disabled', true);

            $('.btn-continue-checkout').button('loading');
          },
          complete: function() {
            $('.btn-continue-checkout').button('reset');
          },
          success: function(json) {
            $('.notify-message').remove();

            if(json['redirect']) {
              location = json['redirect'];
            }

            if(json['warning']) {
              $('#inner-shopping-cart').after('<div class="notify-message alert alert-danger"><i class="fa fa-exclamation-circle"></i> '+ json['warning'] +'<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
            }

            if(json['cart_empty']) {
              $('#inner-shopping-cart').after('<div class="notify-message alert alert-danger"><i class="fa fa-exclamation-circle"></i> '+ json['cart_empty'] +'<button type="button" class="close" data-dismiss="alert">&times;</button></div>');

              if($('#ps-checkout').hasClass('active')) {
                $('#ps-checkout .panel_close').trigger('click');
                $('#ps-checkout').html('');
              }

              // Cart Reload
              poscart.reloadcart();
            }

            /* Permission Granted */
            if(json['success']) {
              setTimeout(function(){
                $('.btn-continue-checkout').attr('disabled', true);
              }, 100);

              // Load Methods
              poscart.LoadMethods();
            }

            removeNotifyMessage();
          },
          error: function(xhr, ajaxOptions, thrownError) {
            alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
          }
        });
      },
      'validatecart': function() {
        $.ajax({
          url: 'index.php?route=jpos/shopping_kart/validatecart',
          type: 'post',
          dataType: 'json',
          beforeSend: function() {
            $('.button-testamentcheckout, #button-confirm').attr('disabled', true);

            $('.btn-continue-checkout').attr('disabled', true);
          },
          complete: function() {
          },
          success: function(json) {
            $('.notify-message').remove();

            if(json['redirect']) {
              location = json['redirect'];
            }

            if(json['warning']) {
              $('.btn-continue-checkout').attr('disabled', false);

              $('#inner-shopping-cart').after('<div class="notify-message alert alert-danger"><i class="fa fa-exclamation-circle"></i> '+ json['warning'] +'<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
            }

            if(json['cart_empty']) {
              $('.btn-continue-checkout').attr('disabled', false);

              $('#inner-shopping-cart').after('<div class="notify-message alert alert-danger"><i class="fa fa-exclamation-circle"></i> '+ json['cart_empty'] +'<button type="button" class="close" data-dismiss="alert">&times;</button></div>');

              if($('#ps-checkout').hasClass('active')) {
                $('#ps-checkout .panel_close').trigger('click');
                $('#ps-checkout').html('');
              }

              // Cart Reload
              poscart.reloadcart();
            }

            /* Permission Granted */
            if(json['success']) {
              // Load Methods
              poscart.LoadMethods();
            }

            removeNotifyMessage();
          },
          error: function(xhr, ajaxOptions, thrownError) {
            alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
          }
        });
      },
      'LoadMethods': function() {
        $.ajax({
          url: 'index.php?route=jpos/load_methods/getMethodsHtml',
          type: 'post',
          dataType: 'html',
          beforeSend: function() {
            $('.button-testamentcheckout, #button-confirm').attr('disabled', true);
          },
          complete: function() {
          },
          success: function(html) {
            $('#ps-checkout').html(html);

            $('#show-checkoutpanel').trigger('click');

            if(typeof $('.checkout-content .scrollert').data('pluginScrollert') != 'undefined') {
              updateScrollbar($('.checkout-content .scrollert'));
            } else {
              initScrollbar($('.checkout-content .scrollert'));
            }

            // Cart Block During Checkout
            <?php if($jpos_cart_block) { ?>
            $('.cart-block').removeClass('hide');
          	<?php } ?>


            var shipping_available = $('#ps-checkout input[name=\'shipping_available\']').val();
            if(shipping_available) {
              // Save Shippings
              poscart.saveShipping();
            } else {
              // Save Payments
              poscart.savePayment();
            }
          },
          error: function(xhr, ajaxOptions, thrownError) {
            alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
          }
        });
      },
      'saveShipping': function() {
        $.ajax({
          url: 'index.php?route=jpos/shipping_method/save',
          type: 'post',
          dataType: 'json',
          data: $('.shipping-area input[type=\'radio\']:checked'),
          beforeSend: function() {
            $('.button-testamentcheckout, #button-confirm').attr('disabled', true);
          },
          complete: function() {
          },
          success: function(json) {
            $('.notify-message').remove();

            if(json['warning']) {
              $('#inner-shopping-cart').after('<div class="notify-message alert alert-danger"><i class="fa fa-exclamation-circle"></i> '+ json['warning'] +'<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
            }

            if(json['success']) {
              // Load Totals
              poscart.loadCartTotals();

              // Load Payments
              poscart.loadPayments();

              // Refesh Cart Total Variable
              if(json['total']) {
                $('.total-payvalue').text(json['total']);
              }
            }

            removeNotifyMessage();
          },
          error: function(xhr, ajaxOptions, thrownError) {
            alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
          }
        });
      },
      'loadCartTotals': function() {
        $.ajax({
          url: 'index.php?route=jpos/shopping_kart/loadCartTotalsAjax',
          type: 'post',
          dataType: 'json',
          beforeSend: function() {
          },
          complete: function() {
          },
          success: function(html) {
            $('.all-totals').html(html.html);

            if(html.data.count_totals > 7) {
              $('.cart-intotal').addClass('scrollert');
              $('.all-totals').addClass('scrollert-content');

              // Update Scroll Totals
              if(typeof $('.cart-intotal.scrollert').data('pluginScrollert') != 'undefined') {
                updateScrollbar($('.cart-intotal.scrollert'));
              } else {
                initScrollbar($('.cart-intotal.scrollert'));
              }
            } else {
              updateScrollbar($('.cart-intotal.scrollert'), 'destroy');

              $('.cart-intotal').removeClass('scrollert');
              $('.all-totals').removeClass('scrollert-content');

            }

          },
          error: function(xhr, ajaxOptions, thrownError) {
            alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
          }
        });
      },
      'loadPayments': function() {
        $.ajax({
          url: 'index.php?route=jpos/payment_method/ajaxload',
          type: 'post',
          dataType: 'html',
          beforeSend: function() {
            $('.button-testamentcheckout, #button-confirm').attr('disabled', true);
          },
          complete: function() {
          },
          success: function(html) {
            $('.payment-area').html(html);

            if(typeof $('.checkout-content .scrollert').data('pluginScrollert') != 'undefined') {
              updateScrollbar($('.checkout-content .scrollert'));
            } else {
              initScrollbar($('.checkout-content .scrollert'));
            }

            // Save Payments
            poscart.savePayment();

          },
          error: function(xhr, ajaxOptions, thrownError) {
            alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
          }
        });
      },
      'savePayment': function() {
        var payment_method = $('.payment-area input[type=\'radio\']:checked').val();
        if(payment_method) {
          $.ajax({
            url: 'index.php?route=jpos/payment_method/save',
            type: 'post',
            dataType: 'json',
            data: $('.payment-area input[type=\'radio\']:checked'),
            beforeSend: function() {
              $('.button-testamentcheckout, #button-confirm').attr('disabled', true);
            },
            complete: function() {
            },
            success: function(json) {
              $('.notify-message').remove();

              if(json['warning']) {
                $('#inner-shopping-cart').after('<div class="notify-message alert alert-danger"><i class="fa fa-exclamation-circle"></i> '+ json['warning'] +'<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
              }

              if(json['success']) {
                // Load Totals
                poscart.loadCartTotals();

                // Load Checkout Button
                poscart.LoadCheckoutButton();

                // Refesh Cart Total Variable
                if(json['total']) {
                  $('.total-payvalue').text(json['total']);
                }
              }

              removeNotifyMessage();
            },
            error: function(xhr, ajaxOptions, thrownError) {
              alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
          });
        } else {
          // Load Checkout Button
          poscart.LoadCheckoutButton();
        }
      },
      'LoadCheckoutButton': function() {
        $.ajax({
          url: 'index.php?route=jpos/checkout_button/ajaxload',
          type: 'post',
          dataType: 'html',
          beforeSend: function() {
            $('.button-testamentcheckout, #button-confirm').attr('disabled', true);
          },
          complete: function() {
            $('.button-testamentcheckout, #button-confirm').attr('disabled', false);
          },
          success: function(html) {
            // Show Checkout Button
            $('.checkout-area').html(html);

            if(typeof $('.checkout-content .scrollert').data('pluginScrollert') != 'undefined') {
              updateScrollbar($('.checkout-content .scrollert'));
            } else {
              initScrollbar($('.checkout-content .scrollert'));
            }
          },
          error: function(xhr, ajaxOptions, thrownError) {
            alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
          }
        });
      },
      'testamentCheckout': function() {
        $.ajax({
          url: 'index.php?route=jpos/checkout_button/testamentCheckout',
          type: 'post',
          dataType: 'json',
          beforeSend: function() {
            $('.button-testamentcheckout').button('loading');
          },
          complete: function() {
          },
          success: function(json) {
            $('.notify-message').remove();

            if(json['redirect']) {
              location = json['redirect'];
            }

            if(json['warning']) {
              $('.button-testamentcheckout').button('reset');

              $('#inner-shopping-cart').after('<div class="notify-message alert alert-danger"><i class="fa fa-exclamation-circle"></i> '+ json['warning'] +'<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
            }

            if(json['redirect']) {
              location = json['redirect'];
            }

            /* Permission Granted */
            if(json['success']) {
              poscart.getPaymentConfirmButton();
            }

            removeNotifyMessage();
          },
          error: function(xhr, ajaxOptions, thrownError) {
            alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
          }
        });
      },
      'getPaymentConfirmButton': function() {
        $.ajax({
          url: 'index.php?route=jpos/checkout_button/getPaymentConfirmButton',
          type: 'post',
          dataType: 'html',
          beforeSend: function() {
          },
          complete: function() {
          },
          success: function(html) {
            $('.button-testamentcheckout').button('reset');

            // Get Payment Confirm Button
            $('.checkout-area').html(html);

            if(typeof $('.checkout-content .scrollert').data('pluginScrollert') != 'undefined') {
              updateScrollbar($('.checkout-content .scrollert'));
            } else {
              initScrollbar($('.checkout-content .scrollert'));
            }
          },
          error: function(xhr, ajaxOptions, thrownError) {
            alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
          }
        });
      },
      'addOrderNote': function() {
        $.ajax({
          url: 'index.php?route=jpos/checkout_button/addOrderNote',
          type: 'post',
          dataType: 'json',
          data: $('textarea[name=\'jpos_order_comment\']'),
          beforeSend: function() {
            $('.button-testamentcheckout, #button-confirm').attr('disabled', true);

            $('#button-addordernote').button('loading');
          },
          complete: function() {
            $('#button-addordernote').button('reset');
          },
          success: function(json) {
            $('.notify-message').remove();

            if(json['success']) {
              $('#addnote').modal('hide');

              $('#inner-shopping-cart').after('<div class="notify-message alert alert-success"><i class="fa fa-check-circle"></i> '+ json['success'] +'<button type="button" class="close" data-dismiss="alert">&times;</button></div>');

              // Load Checkout Button
              poscart.LoadCheckoutButton();
            }

            $('textarea').val('');

            removeNotifyMessage();
          },
          error: function(xhr, ajaxOptions, thrownError) {
            alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
          }
        });
      },
      'SuccessPage': function() {
        $.ajax({
          url: 'index.php?route=jpos/checkout_success/ajaxload',
          type: 'post',
          dataType: 'html',
          beforeSend: function() {
            $('#button-confirm').button('loading');
          },
          complete: function() {
            $('#button-confirm').button('reset');
          },
          success: function(html) {
            $('#ps-checkout-success-detail').html(html);

            $('.checkout-success').trigger('click');

            // Cart Reload Manual
            $.ajax({
              url: 'index.php?route=jpos/shopping_kart/reloadcart',
              type: 'post',
              data: '',
              dataType: 'html',
              beforeSend: function() {
              },
              complete: function() {
              },
              success: function(html) {
                $('#inner-shopping-cart').html(html);
              }
            });

            // Pending Work
            // After Checkout Success Re-Load Orders List And Orders Info (opened function).......here
          },
          error: function(xhr, ajaxOptions, thrownError) {
            alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
          }
        });
      },
    }

    $(document).delegate('.shipping-area input[type=\'radio\']', 'change', function() {
      // Save Shipping
      poscart.saveShipping();
    });

    $(document).delegate('.payment-area input[type=\'radio\']', 'change', function() {
      $('.eachpayment').removeClass('active');

      $(this).parent().addClass('active');
      // Save Payment
      poscart.savePayment();
    });

    // Add Coupon
    $(document).delegate('#button-coupon', 'click', function() {
      $.ajax({
        url: 'index.php?route=jpos/jpos_total/jpos_coupon/coupon',
        type: 'post',
        data: 'jpos_coupon=' + encodeURIComponent($('input[name=\'jpos_coupon\']').val()),
        dataType: 'json',
        beforeSend: function() {
          $('.button-testamentcheckout, #button-confirm').attr('disabled', true);

          $('#button-coupon').button('loading');
        },
        complete: function() {
          $('#button-coupon').button('reset');
        },
        success: function(json) {
          $('.notify-message').remove();
          $('#collapse-discount .alert').remove();

          if (json['error']) {
            $('#collapse-coupon .panel-body').prepend('<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> '+ json['error'] +'<button type="button" class="close" data-dismiss="alert">&times;</button></div>');

            poscart.loadCartTotals();

            // Load Checkout Button
            poscart.LoadCheckoutButton();
          }

          if (json['success']) {
            if(!$('#ps-checkout').hasClass('active')) {
              $('#inner-shopping-cart').after('<div class="notify-message alert alert-success"><i class="fa fa-check-circle"></i> '+ json['success'] +'<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
            }

            // Cart Reload
            poscart.reloadcart();
          }

          removeNotifyMessage();
        }
      });
    });

    // Add Discount
    $(document).delegate('#button-discount', 'click', function() {
      $.ajax({
        url: 'index.php?route=jpos/jpos_total/jpos_discount/adddiscount',
        type: 'post',
        data: 'jpos_discount_type=' + encodeURIComponent($('select[name=\'jpos_discount_type\']').val()) + '&jpos_discount=' + encodeURIComponent($('input[name=\'jpos_discount\']').val()),
        dataType: 'json',
        beforeSend: function() {
          $('.button-testamentcheckout, #button-confirm').attr('disabled', true);

          $('#button-discount').button('loading');
        },
        complete: function() {
          $('#button-discount').button('reset');
        },
        success: function(json) {
          $('.notify-message').remove();
          $('#collapse-discount .alert').remove();

          if (json['error']) {
            $('#collapse-discount .panel-body').prepend('<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> '+ json['error'] +'<button type="button" class="close" data-dismiss="alert">&times;</button></div>');

            poscart.loadCartTotals();

            // Load Checkout Button
            poscart.LoadCheckoutButton();
          }

          if (json['success']) {
            if(!$('#ps-checkout').hasClass('active')) {
              $('#inner-shopping-cart').after('<div class="notify-message alert alert-success"><i class="fa fa-check-circle"></i> '+ json['success'] +'<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
            }

            // Cart Reload
            poscart.reloadcart();
          }

          removeNotifyMessage();
        }
      });
    });

    // Add Charges
    $(document).delegate('#button-charge', 'click', function() {
      $.ajax({
        url: 'index.php?route=jpos/jpos_total/jpos_charge/addcharge',
        type: 'post',
        data: 'jpos_charge_type=' + encodeURIComponent($('select[name=\'jpos_charge_type\']').val()) + '&jpos_charge=' + encodeURIComponent($('input[name=\'jpos_charge\']').val()) + '&jpos_charge_title=' + encodeURIComponent($('input[name=\'jpos_charge_title\']').val()),
        dataType: 'json',
        beforeSend: function() {
          $('.button-testamentcheckout, #button-confirm').attr('disabled', true);

          $('#button-charge').button('loading');
        },
        complete: function() {
          $('#button-charge').button('reset');
        },
        success: function(json) {
          $('.notify-message').remove();
          $('#collapse-charge .alert').remove();

          if (json['error']) {
            $('#collapse-charge .panel-body').prepend('<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> '+ json['error'] +'<button type="button" class="close" data-dismiss="alert">&times;</button></div>');

            poscart.loadCartTotals();

            // Load Checkout Button
            poscart.LoadCheckoutButton();
          }

          if (json['success']) {
            if(!$('#ps-checkout').hasClass('active')) {
              $('#inner-shopping-cart').after('<div class="notify-message alert alert-success"><i class="fa fa-check-circle"></i> '+ json['success'] +'<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
            }

            // Cart Reload
            poscart.reloadcart();
          }

          removeNotifyMessage();
        }
      });
    });

    // Go To Edit Order
    $(document).delegate('#button-editorder', 'click', function() {
      var order_id =  $(this).attr('data-id');

      $.ajax({
            url: 'index.php?route=jpos/orders/GoToEditOrder',
            type: 'post',
            data: 'order_id='+ order_id,
            dataType: 'json',
            beforeSend: function() {
              $('#button-editorder').button('loading');
              spinner('#ps-order-detail', 'show');
            },
            complete: function() {
              $('#button-editorder').button('reset');
              spinner('#ps-order-detail', 'hide');
            },
            success: function(json) {
              if (json['success']) {
                // Close Customr info and list
                // $('#ps-customer-list #ps-customer-detail .content_destroy').trigger('click'); // clear order edit
                $('#ps-order-detail .panel_close').trigger('click');

                  // Cart Reload
                  poscart.reloadcart();

                  // reload all products section here
                  productContent_reloadProducts();
              }

            },
            error: function(xhr, ajaxOptions, thrownError) {
              console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
    });

    // Enabled Checkout button after complte all ajax
    $(document).ajaxStop(function() {
      $('.button-testamentcheckout, #button-confirm').attr('disabled', false);
    });

    $(document).delegate('#inner-shopping-cart .close', 'click', function() {
      $('a[href="'+ $(this).attr('data-dismiss') +'"]').trigger('click');
    });

    // Shopping cart column ends

    // Remove Notify Message Starts
    window['removeNotifyMessage'] = function () {
 			setTimeout(function() {
 				$('.notify-message').remove();
			}, 3000);
		};
    // Remove Notify Message Ends

    // spinner function Starts
    function spinner(target, action) {
      if (action == 'show') {
        if (!$(target + ' .spinner-wrap').length) {
          $(target).append('<div class="spinner-wrap" style="display: none;"><div id="jdspinner5"></div></div>');
        }
        $(target + ' .spinner-wrap').show();
      } else {
        $(target + ' .spinner-wrap').remove();
      }
    }
    // spinner function Ends

    function initScrollbar($el) {
      if ($el) {
        $el.scrollert();
      }
    }

    function updateScrollbar($el, options) {
      options = options || 'update';
      if ($el) {
        $el.scrollert(options);
      }
    }

    var keyTypeWatch = (function() {
      var timer = 0;
      return function(callback, ms) {
      clearTimeout (timer);
      timer = setTimeout(callback, ms);
      }
    })();


    // check native support
    // $('#support').text($.fullscreen.isNativelySupported() ? 'supports' : 'doesn\'t support');\
    if ($.fullscreen.isNativelySupported()) {
      // open in fullscreen
      $(document).delegate('.requestfullscreen', 'click', function() {
        $('#mainwrap').fullscreen();
        return false;
      });
      // exit fullscreen
      $('#mainwrap .exitfullscreen').click(function() {
        $.fullscreen.exit();
        return false;
      });
      // document's event
      $(document).bind('fscreenchange', function(e, state, elem) {
        // if we currently in fullscreen mode
        if ($.fullscreen.isFullScreen()) {
          $('#mainwrap .requestfullscreen').hide();
          $('#mainwrap .exitfullscreen').show();
        } else {
          $('#mainwrap .requestfullscreen').show();
          $('#mainwrap .exitfullscreen').hide();
        }
        $('#state').text($.fullscreen.isFullScreen() ? '' : 'not');
      });
    }

    $(document).delegate('.qty-minus', 'click', function() {
      var $parent = $(this).parents('.qty-group');
      var $input_qty = $parent.find('input[name="quantity"]');
      updateQuantity($input_qty, '-');
    });
    $(document).delegate('.qty-plus', 'click', function() {
      var $parent = $(this).parents('.qty-group');
      var $input_qty = $parent.find('input[name="quantity"]');
      updateQuantity($input_qty, '+');
    });

    function updateQuantity($input_qty, action) {
      // console.log($input_qty);
      var val = parseInt($input_qty.val());
      var min = parseInt($input_qty.attr('data-min'));
      var interval = parseInt($input_qty.attr('data-interval'));
      // console.log("val : " + val);
      var old_val = val;
      if (interval <= 0) {
        interval = 1;
      }

      // console.log("interval : " + interval);

      if (min <= 0) {
        min = 1;
      }
      // console.log("min : " + min);
      if (action == '+') {
        val = (val + interval);
      }

      if (action == '-') {
        val = val - interval;
        if (val < min) {
          val = min;
        }
      }
      // console.log("val : " + val);
      $input_qty.val(val);
      $input_qty.attr('value', val);
      $input_qty.trigger('change');
    }

    function updateDateTimePicker() {
      $('.date').datetimepicker({
        pickTime: false
      });

      $('.datetime').datetimepicker({
        pickDate: true,
        pickTime: true
      });

      $('.time').datetimepicker({
        pickDate: false
      });
    }


    $(document).delegate('button[id^=\'button-upload\']', 'click', function() {
      var node = this;

      $('#form-upload').remove();

      $('body').prepend('<form enctype="multipart/form-data" id="form-upload" style="display: none;"><input type="file" name="file" /></form>');

      $('#form-upload input[name=\'file\']').trigger('click');

      if (typeof timer != 'undefined') {
          clearInterval(timer);
      }

      timer = setInterval(function() {
        if ($('#form-upload input[name=\'file\']').val() != '') {
          clearInterval(timer);

          $.ajax({
            url: 'index.php?route=tool/upload',
            type: 'post',
            dataType: 'json',
            data: new FormData($('#form-upload')[0]),
            cache: false,
            contentType: false,
            processData: false,
            beforeSend: function() {
              $(node).button('loading');
            },
            complete: function() {
              $(node).button('reset');
            },
            success: function(json) {
              $('.text-danger').remove();

              if (json['error']) {
                $(node).parent().find('input').after('<div class="text-danger">' + json['error'] + '</div>');
              }

              if (json['success']) {
                alert(json['success']);

                $(node).parent().find('input').val(json['code']);
              }
            },
            error: function(xhr, ajaxOptions, thrownError) {
              alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
          });
        }
      }, 500);
    });
  });
//--></script>
</body></html>