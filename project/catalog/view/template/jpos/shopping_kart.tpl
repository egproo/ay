<header>
  <nav>
    <button class="delete-button" onclick="poscart.clear();"><i class="fa fa-trash-o" aria-hidden="true"></i></button>
    <div class="cart-head">
      <?php echo $text_cart; ?>
      <?php if($text_editorder) { ?>
      <small><?php echo $text_editorder; ?></small>
      <?php } ?>
    </div>
    <div class="toggle-group">
      <button class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-ellipsis-h" aria-hidden="true"></i></button>
      <ul class="dropdown-menu dropdown-menu-right">
        <li><a class="requestfullscreen"><i class="fa fa-sign-in"></i> <?php echo $text_enter_fullscreen; ?></a></li>
        <li><a class="exitfullscreen" style="display: none;"><i class="fa fa-sign-out"></i> <?php echo $text_exit_fullscreen; ?></a></li>
      </ul>
    </div>
  </nav>
  <div class="add-cart clearfix">
    <div class="user-info">
    <?php if($customer_name) { ?>
      <div class="user-icon">
        <i class="fa fa-user" aria-hidden="true"></i>
      </div>
      <div class="user"><span><?php echo $customer_name; ?></span></div>
    <?php } ?>
    </div>
    <button class="btn panel_show" data-panel="#adduser"><i class="fa fa-plus" aria-hidden="true"></i> <?php echo $text_add_new_customer; ?></button>
  </div>
</header>
<!-- products loop start -->
<?php if($products) { ?>
  <div class="cart_products-wrap scrollert">
    <div class="cart_products scrollert-content" tabindex="10">
      <?php foreach($products as $product) { ?>
      <div class="cart-item">
        <div class="cart-outer">
          <div class="add-item-wrap flex-wrap">
            <div class="add-item-row">
              <div class="add-item-left-col">
                <div class="add-product">
                  <label><?php echo $product['name']; ?>
                  <?php if ($product['option']) { ?>
                  <i class="fa fa-info-circle">
                    <div class="otnhvr">
                      <?php foreach ($product['option'] as $option) { ?>
                        <small><?php echo $option['name']; ?>: <?php echo $option['value']; ?></small>
                      <?php } ?>
                    </div>
                  </i>
                  <?php } ?>
                  </label>
                  <?php if (!$product['stock']) { ?>
                    <span class="text-danger">***</span>
                  <?php } ?>

                  <?php if ($product['recurring']) { ?>
                  <br />
                  <span class="label label-info"><?php echo $text_recurring_item; ?></span> <small><?php echo $product['recurring']; ?></small>
                  <?php } ?>
                </div>
              </div>
              <div class="add-item-right-col">
                <div class="product-price">
                  <?php echo $product['price']; ?>
                </div>
              </div>
            </div>
            <div class="add-item-row">
              <div class="add-item-left-col">
                <div class="units">
                  <div class="input-group input-group-sm quantity">
                    <input type="text" name="cart_qty[<?php echo $product['jposcart_id']; ?>]" data-cartid="<?php echo $product['jposcart_id']; ?>" value="<?php echo $product['quantity']; ?>" class="form-control" />
                    <span class="input-group-btn">
                      <button class="btn btn-default button cartupdate" data-cartid="<?php echo $product['jposcart_id']; ?>"><i class="fa fa-refresh"></i></button>
                    </span>
                  </div>
                </div>
              </div>
              <div class="add-item-right-col">
                <div class="total-product-price">
                  <?php echo $product['total']; ?>
                </div>
              </div>
            </div>
          </div>
          <div class="item-remove flex-wrap">
            <button class="btn btn-danger" onclick="poscart.remove('<?php echo $product['jposcart_id']; ?>');"><i class="fa fa-trash"></i></button>
          </div>
        </div>
        <div class="cart-inner">
        </div>
      </div>
      <?php } ?>
    </div>
  </div>
  <!-- products loop end -->

  <!-- cart subtotal, total start -->
  <div class="cart-total">
    <div class="cart-intotal <?php echo (count($totals) > 7) ? 'scrollert' : ''; ?>" id="cart-intotal">
      <ul class="list-unstyled all-totals <?php echo (count($totals) > 7) ? 'scrollert-content' : ''; ?>"  tabindex="11">
        <?php echo $shopping_kar_total; ?>
      </ul>
    </div>
    <div class="cart-buttons no-gutters clearfix flex">
      <div class="col-sm-8 flex">
          <?php if ($modules) { ?>
          <div class="panel-group clearfix" id="accordion">
            <?php foreach ($modules as $module) { ?>
            <?php echo $module; ?>
            <?php } ?>
          </div>
          <?php } ?>
      </div>
      <div class="col-sm-4 flex">
        <button class="btn btn-success btn-continue-checkout makecheckout" onclick="poscart.validatecartByButton();"><?php echo $button_pay; ?></button>
        <span id="show-checkoutpanel" class="panel_show" data-panel="#ps-checkout" data-toggle="false"></span>
      </div>
    </div>
  </div>
<?php } else { ?>
  <div class="empty-cart">
    <img class="img-responsive" src="catalog/view/theme/default/image/jpos/empty-cart.jpg" alt="" title="" />
    <h3><?php echo $text_cart_empty; ?></h3>
    <h4><?php echo $text_look; ?></h4>
  </div>
<?php } ?>
<!-- cart subtotal, total end -->