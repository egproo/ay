<form id="payment" class="form-horizontal">
  <fieldset>
    <legend><?php echo $text_credit_card; ?></legend>
    <div class="form-group required">
      <label class="col-sm-2 control-label" for="input-cc-owner"><?php echo $entry_cc_owner; ?></label>
      <div class="col-sm-10">
        <input type="text" name="cc_owner" value="" placeholder="<?php echo $entry_cc_owner; ?>" id="input-cc-owner" class="form-control" />
      </div>
    </div>
    <div class="form-group required">
      <label class="col-sm-2 control-label" for="input-cc-number"><?php echo $entry_cc_number; ?></label>
      <div class="col-sm-10">
        <input type="text" name="cc_number" value="" placeholder="<?php echo $entry_cc_number; ?>" id="input-cc-number" class="form-control" />
      </div>
    </div>
    <div class="form-group required">
      <label class="col-sm-2 control-label" for="input-cc-expire-date"><?php echo $entry_cc_expire_date; ?></label>
      <div class="col-sm-3">
        <select name="cc_expire_date_month" id="input-cc-expire-date" class="form-control">
          <?php foreach ($months as $month) { ?>
          <option value="<?php echo $month['value']; ?>"><?php echo $month['text']; ?></option>
          <?php } ?>
        </select>
       </div>
       <div class="col-sm-3">
        <select name="cc_expire_date_year" class="form-control">
          <?php foreach ($year_expire as $year) { ?>
          <option value="<?php echo $year['value']; ?>"><?php echo $year['text']; ?></option>
          <?php } ?>
        </select>
      </div>
    </div>
    <div class="form-group required">
      <label class="col-sm-2 control-label" for="input-cc-cvv2"><?php echo $entry_cc_cvv2; ?></label>
      <div class="col-sm-10">
        <input type="text" name="cc_cvv2" value="" placeholder="<?php echo $entry_cc_cvv2; ?>" id="input-cc-cvv2" class="form-control" />
      </div>
    </div>
  </fieldset>

  <?php if($order_status_duringcheckout) { ?>
  <div class="form-group card-form">
    <label class="col-sm-2 control-label"><?php echo $entry_order_status; ?></label>
    <div class="col-sm-10">
      <select name="order_status_id" class="form-control">
        <?php foreach($order_statuses as $order_status) { ?>
          <?php if($order_status['order_status_id'] == $order_status_id) { ?>
          <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
          <?php } else { ?>
          <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
          <?php } ?>
        <?php } ?>
      </select>
    </div>
  </div>
  <?php } ?>

</form>
<div class="buttons clearfix">
  <div class="pull-right">
    <input type="button" value="<?php echo $button_confirm; ?>" id="button-confirm" class="btn btn-success" />
  </div>
</div>
<script type="text/javascript"><!--
$('#button-confirm').on('click', function() {
	$.ajax({
    <?php if(!empty($sendby_editorder)) { ?>

    url: 'index.php?route=jpos/jpos_payment/jpos_authorizenet_aim/editconfirm',
    <?php } else { ?>
    url: 'index.php?route=jpos/jpos_payment/jpos_authorizenet_aim/send',
    <?php } ?>

		type: 'post',
		data: $('#payment :input'),
		dataType: 'json',
		cache: false,
		beforeSend: function() {
			$('#button-confirm').button('loading');
		},
		complete: function() {
		},
		success: function(json) {
      $('#button-confirm').button('reset');

      $('.notify-message').remove();

			if (json['error']) {
				$('#ps-shopping-cart').prepend('<div class="notify-message alert alert-danger"><i class="fa fa-exclamation-circle"></i> '+ json['error'] +'<button type="button" class="close" data-dismiss="alert">×</button></div>');

        // window.removeNotifyMessage();
			}

			if (json['success']) {
				// Load Checkout Success Page
        poscart.SuccessPage();

        // reload order list section here
        window.orderContent_searchOrders();
			}
		}
	});
});
//--></script>