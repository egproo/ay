<?php if ($error_warning) { ?>
<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?></div>
<?php } ?>

<?php if ($jpos_payment_methods) { ?>
<h3><?php echo $heading_payments; ?></h3>
    <div class="payment-method">
        <ul class="list-inline">
            <?php foreach ($jpos_payment_methods as $jpos_payment_method) { ?>
            <li>
                <?php if ($jpos_payment_method['code'] == $payment_code || !$payment_code) { ?>
                <?php $payment_code = $jpos_payment_method['code']; ?>
                <label class="eachpayment active">
                    <i class="fa fa-credit-card"></i> <span><?php echo $jpos_payment_method['title']; ?></span>
                    <input type="radio" name="jpos_payment_method" value="<?php echo $jpos_payment_method['code']; ?>" checked="checked" />
                </label>
                <?php } else { ?>
                <label class="eachpayment">
                    <i class="fa fa-credit-card"></i> <span><?php echo $jpos_payment_method['title']; ?></span>
                    <input type="radio" name="jpos_payment_method" value="<?php echo $jpos_payment_method['code']; ?>" />
                </label>
                <?php } ?>
            </li>
            <?php } ?>
        </ul>
    </div>
<?php } ?>