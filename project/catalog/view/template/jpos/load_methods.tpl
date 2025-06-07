<header>
	<div class="min-heading">
    <h4>
      <?php echo $heading_checkout; ?>
      <?php if($text_editorder) { ?>
        <small><?php echo $text_editorder; ?></small>
      <?php } ?>
    </h4>
     <a class="close-panel close-makecheckout panel_close" data-panel="#ps-checkout"><i class="fa fa-close"></i></a>
     <?php if($display_order_note) { ?>
      <a class="addnote" href="#addnote" data-toggle="modal"><i class="fa fa-edit"></i><?php echo $button_add_note; ?></a>
     <?php } ?>
  </div>
</header>
<div class="checkout-content">
  <div class="scrollert">
    <div class="scrollert-content" tabindex="0">
      <input type="hidden" name="shipping_available" value="<?php echo $shipping_available; ?>" />
      <?php if($shipping_method_html) { ?>
        <div class="shipping-area <?php echo $display_shippings ? '' : 'hide'; ?>">
          <?php echo $shipping_method_html; ?>
        </div>
      <?php } ?>
      <div class="payment-area">
        <?php echo $payment_method_html; ?>
      </div>
      <?php if($display_total_amount) { ?>
        <div class="total-pay">
          <span><?php echo $text_total_pay; ?></span> <span class="total-payvalue"><?php echo $total_value; ?></span>
        </div>
      <?php } ?>
      <div class="checkout-area clearfix">
        <?php echo $checkout_button_html; ?>
      </div>
    </div>
  </div>
</div>