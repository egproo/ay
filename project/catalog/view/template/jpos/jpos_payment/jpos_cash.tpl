<div class="form-horizontal">
	<div class="buttons">
		<div class="col-sm-7">
			<?php if($order_status_duringcheckout) { ?>
				<div class="form-group cash-form">
					<label class="col-sm-4 control-label"><?php echo $entry_order_status; ?></label>
					<div class="col-sm-8">
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
		</div>
		<div class="col-sm-5">
		  <input type="button" value="<?php echo $button_confirm; ?>" id="button-confirm" class="btn btn-success" data-loading-text="<?php echo $text_loading; ?>" />
	  </div>
	</div>
</div>
<script type="text/javascript"><!--
$('#button-confirm').on('click', function() {
	$.ajax({
		url: 'index.php?route=jpos/jpos_payment/jpos_cash/confirm',
		type: 'post',
		data: $('.checkout-area select[name=\'order_status_id\']'),
		dataType: 'json',
		cache: false,
		beforeSend: function() {
			$('#button-confirm').button('loading');
		},
		complete: function() {
		},
		success: function(json) {
			$('#button-confirm').button('reset');

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
