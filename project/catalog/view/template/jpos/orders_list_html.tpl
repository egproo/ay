<header>
  <nav class="clearfix">
    <div class="col-sm-12 order-heading">
      <h4><?php echo $text_orders; ?></h4>
    </div>
  </nav>
  <div class="clearfix">
    <div id="orders-block-search">
      <input type="text" name="orders_block_search" value="" placeholder="<?php echo $placeholder_search_orders; ?>" class="form-control input-lg" />
      <span class="clear_search"> <i class="fa fa-close" aria-hidden="true"></i></span>
    </div>
  </div>

</header>
<div class="order-history-items">
   <div class="color-indications order_statuses">
    <?php foreach ($order_statuses as $order_status) { ?>
      <label class="l_orderstatus"><i class="fa fa-square" style="<?php if (!empty($colors_order_status[$order_status['order_status_id']])) { ?>color: <?php echo $colors_order_status[$order_status['order_status_id']]; ?>; <?php } ?>"></i> <?php echo $order_status['name']; ?> <input type="checkbox" name="order_block_filter_order_statues[]" class="order_block_order_status" value="<?php echo $order_status['order_status_id']; ?>" />  </label>
    <?php } ?>
   </div>
   <div class="order-items scrollert orders_area">
     <div class="scrollert-content orders_content">
      <!-- loop of orders list starts -->
      <?php echo $orders_lists; ?>
      <!-- loop of orders list ends -->
     </div>
   </div>
</div>