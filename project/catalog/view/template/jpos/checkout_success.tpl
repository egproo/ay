<header>
  <div class="min-heading">
    <h4><?php echo $heading_title; ?></h4>
    <a class="close-panel panel_close" data-panel="#ps-checkout-success"><i class="fa fa-close"></i></a>
  </div>
</header>
<div class="success-in">
  <div class="text-center">
    <h3><?php echo $text_message; ?></h3>
  </div>
  <div class="order-history-buttons">
    <div class="row">
      <div class="col-sm-4">
        <a class="btn btn-block btn-primary close-panel panel_close" data-panel="#ps-checkout-success"><?php echo $button_continue; ?></a>
      </div>

      <?php if($link_print_shipping) { ?>
      <div class="col-sm-4">
        <a href="<?php echo $link_print_shipping; ?>" target="_blank" class="btn btn-block btn-default"><i class="fa fa-print"></i> <?php echo $button_print; ?></a>
      </div>
      <?php } ?>

      <?php if($link_print_invoice) { ?>
      <div class="col-sm-4">
        <a href="<?php echo $link_print_invoice; ?>" target="_blank" class="btn btn-block btn-success"><i class="fa fa-print"></i> <?php echo $button_invoice; ?></a>
      </div>
      <?php } ?>
    </div>
  </div>
</div>