<?php echo $header; ?>
<div id="ps-wrapper" class="clearfix">
  <!--Product Block wrap-->
  <div id="ps-product-block" class="col-md-8 col-sm-7 padding-none">
    <header>
      <nav class="clearfix">
        <?php /* <div class="col-sm-2">
          <div class="menu-toggle">
            <i class="fa fa-bell-o" aria-hidden="true"></i>
          </div>
        </div> */ ?>
        <div class="col-md-9 col-sm-7 col-xs-9">
          <div id="product-block-search">
            <input type="text" name="product_block_search" value="" placeholder="<?php echo $placeholder_search_product; ?>" class="form-control input-lg" />
            <span class="clear_search"> <i class="fa fa-close" aria-hidden="true"></i></span>
          </div>
        </div>
        <div class="col-md-3 col-sm-5 col-xs-3">
          <div class="all-cate">
            <i class="fa fa-th" aria-hidden="true"></i> <span class="hidden-xs"><?php echo $text_allcategories; ?></span> <i class="fa fa-angle-down" aria-hidden="true"></i>
          </div>
        </div>
      </nav>
    </header>
    <div class="ps-categories">
      <ul class="list-inline owl-carousel">
        <?php foreach ($categories as $category) { ?>
        <li class="item"><a class="category category_products top_cats" data-path="<?php echo $category['path']; ?>"><?php echo $category['name']; ?></a></li>
        <?php } ?>
      </ul>
    </div>
    <div class="content scrollert products_area">
      <div class="scrollert-content products_content" tabindex="1">
        <?php echo $products_list_html; ?>
      </div>
    </div>
    <!--Checkout wrap-->
    <div id="ps-checkout" class="panel-of panel-checkout"></div>
    <!--Checkout end-->
    <!--Adduser wrap-->
    <div id="adduser" class="adduser-content panel-of panel-adduser">
      <div class="user-heading">
        <h4><?php echo $text_add_customer; ?></h4>
        <a class="close-panel close-adduser panel_close" data-panel="#adduser"><i class="fa fa-close"></i></a>
      </div>
      <div class="scrollert">
        <div class="scrollert-content">
          <div id="customer-detail-form" class="form-horizontal" autocomplete="off">
            <div class="add-customer-form">
              <?php echo $customer_form; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!--Adduser wrap end-->
    <!--General setting wrap-->
	  <div id="ps-general-wrap" class="panel-of panel-general-wrap">
  		<header>
  			<div class="min-heading">
  				<h4><?php echo $text_general_settings; ?></h4>
  				<a class="close-panel close-general-wrap panel_close" data-panel="#ps-general-wrap"><i class="fa fa-close"></i></a>
  			</div>
  		</header>
  		<div id="user-general-form" class="general-content" autocomplete="off">
        <?php echo $user_general_settings_form; ?>
  			<button class="btn btn-success user_update_general" type="button"><i class="fa fa-save"></i> <?php echo $button_general_update; ?></button>
  		</div>
	  </div>
	  <!--General setting wrap end-->
	  <!--Account setting wrap-->
	  <div id="ps-account-wrap" class="panel-of panel-account-wrap">
  		<header>
  			<div class="min-heading">
  				<h4><?php echo $text_account_settings; ?></h4>
  				<a class="close-panel close-account-wrap panel_close" data-panel="#ps-account-wrap"><i class="fa fa-close"></i></a>
  			</div>
  		</header>
  		<div class="account-content">
        <div id="user-form" autocomplete="off">
          <?php echo $user_form; ?>
          <button class="btn btn-success user_update_info" type="button"><i class="fa fa-save"></i> <?php echo $button_account_update; ?></button>
        </div>
  		</div>
	  </div>
	  <!--Account setting wrap end-->
  </div>
  <!--Product Block wrap end-->

  <!--Checkout success wrap-->
  <div id="ps-checkout-success" class="panel-of panel-checkout-success">
    <div id="ps-checkout-success-detail" class="col-sm-12 padding-none">

    </div>
  </div>
  <!--Checkout success end-->

  <!--Shopping cart column-->
  <div id="ps-shopping-cart" class="col-md-4 col-sm-5 padding-none">
    <div id="inner-shopping-cart">
      <?php echo $shopping_kart_list_html; ?>
    </div>

    <!-- Cart Block During Checkout -->
    <div class="cart-block hide"></div>

    <!-- Cart Loader -->
    <div class="cart-process hide" >
      <img src="catalog/view/theme/default/image/jpos/cart-loader-icon.svg"/>
      <div class="cart-inprocess"></div>
    </div>
  </div>
  <!--Shopping cart column end-->
  <!--Order List wrap-->
  <div id="order-history-wrap" class="panel-of panel-order-history">
	  <div id="ps-order-history" class="col-sm-4 padding-none">
      <?php echo $orders_list_html; ?>
	  </div>
	  <div id="ps-order-detail" class="col-sm-8 padding-none">
      <div class="order-detail"></div>
    </div>
	</div>
  <!--Order List wrap end-->
  <!--On-Hold wrap-->
  <div id="ps-order-onhold" class="panel-of panel-order-onhold">
    <div id="ps-onhold-carts" class="col-sm-4 padding-none">
      <?php echo $orders_onhold_list_html; ?>
    </div>
    <div id="ps-onhold-order-detail" class="col-sm-8 padding-none">
      <div class="onhold-order-detail"></div>
    </div>
  </div>
  <!--On-Hold wrap end-->

  <!--Customer List wrap-->
  <div id="ps-customer-list" class="panel-of panel-customer-list">
  	<div class="col-sm-4 padding-none">
      <?php echo $customers_list_html; ?>
  	</div>
  	<div id="ps-customer-detail" class="col-sm-8 padding-none">
      <div class="customer-detail"></div>
      <div class="customer-detail-edit"></div>
  	</div>
  </div>
  <!--Customer List wrap end-->

  <!--Product Info Modal-->
  <div class="modal fade" id="product-info" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
         <h3></h3> <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only sr-only-focusable"><?php echo $text_close; ?></span></button>
        </div>
        <div class="modal-body">
        </div>
      </div>
    </div>
  </div>
  <!--Product Info Modal end-->
  <!--Add Order Note Modal-->
  <div class="modal fade" id="addnote" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xs">
      <div class="modal-content">
        <div class="modal-header">
         <h3><?php echo $text_add_note; ?></h3> <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only sr-only-focusable"><?php echo $text_close; ?></span></button>
        </div>
        <div class="modal-body">
          <form class="form-horizontal">
            <div class="col-sm-12">
              <div class="form-group">
                <textarea rows="6" class="form-control" name="jpos_order_comment" placeholder="<?php echo $placeholder_add_note; ?>"><?php echo $jpos_order_comment; ?></textarea>
              </div>
            </div>
            <div class="buttons text-right">
              <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-close"></i> <?php echo $button_note_close; ?></button>
              <button type="button" class="btn btn-primary" id="button-addordernote" onclick="poscart.addOrderNote();"><i class="fa fa-save"></i> <?php echo $button_note_save; ?></button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
  <!--Add Order Note Modal end-->
</div>
<?php echo $footer; ?>