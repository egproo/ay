<header>
  <nav class="clearfix">
    <div class="col-sm-12 order-heading">
      <h4><?php echo $text_orders_onhold; ?></h4>
    </div>
  </nav>
  <div class="clearfix">
    <div id="orders_onhold-block-search">
      <input type="text" name="orders_block_search" value="" placeholder="" class="form-control input-lg" />
      <span class="clear_search"> <i class="fa fa-close" aria-hidden="true"></i></span>
    </div>
  </div>
  <a class="close-panel close-onhold panel_close" data-panel="#ps-order-onhold"><i class="fa fa-close"></i></a>
</header>
<div class="onhold-order-items scrollert">
  <div class="scrollert-content" tabindex="3">
     <div class="order-items">
      <?php if ($orders_onhold) { ?>
      <?php echo $orders_onhold; ?>
      <?php } else { ?>
      <div class="text-center"><strong><?php echo $text_no_results; ?></strong></div>
      <?php } ?>
     </div>
  </div>
</div>