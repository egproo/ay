<?php if(!empty($payment)) { ?>
  <?php echo $payment; ?>
<?php } else { ?>
  <div class="checkout-footer">
    <div class="buttons clearfix">
      <button type="button" disabled="disabled" class="btn btn-success button-testamentcheckout" onclick="poscart.testamentCheckout();"><?php echo $button_checkout; ?></button>
    </div>
  </div>
<?php } ?>