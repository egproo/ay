<?php if ($error_warning) { ?>
	<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?></div>
<?php } ?>
<?php if ($jpos_shipping_methods) { ?>
	<h3><?php echo $heading_shippings; ?></h3>
	<?php foreach ($jpos_shipping_methods as $jpos_shipping_method) { ?>
		<p><strong><?php echo $jpos_shipping_method['title']; ?></strong></p>
		<?php if (!$jpos_shipping_method['error']) { ?>
		<?php foreach ($jpos_shipping_method['quote'] as $quote) { ?>
		<div class="radio">
		  <label>
		    <?php if ($quote['code'] == $shipping_code || !$shipping_code) { ?>
		    <?php $shipping_code = $quote['code']; ?>
		    <input type="radio" name="jpos_shipping_method" value="<?php echo $quote['code']; ?>" checked="checked" />
		    <?php } else { ?>
		    <input type="radio" name="jpos_shipping_method" value="<?php echo $quote['code']; ?>" />
		    <?php } ?>
		    <?php echo $quote['title']; ?> - <?php echo $quote['text']; ?></label>
		</div>
		<?php } ?>
		<?php } else { ?>
		<div class="alert alert-danger"><?php echo $jpos_shipping_method['error']; ?></div>
		<?php } ?>
	<?php } ?>
<?php } ?>