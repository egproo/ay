<?php if ($orders) { ?>
<?php foreach ($orders as $order) { ?>
<div class="order_info item" data-id="<?php echo $order['order_id']; ?>">
<div class="item-row">
  <div class="item-left">
    <i class="fa fa-square" style="<?php if (!empty($colors_order_status[$order['order_status_id']])) { ?>color: <?php echo $colors_order_status[$order['order_status_id']]; ?>;<?php } ?>"></i>
    <div class="order">#<?php echo $order['order_id']; ?></div>
  </div>
  <div class="item-right">
    <div class="item-price"><?php echo $order['total']; ?></div>
  </div>
</div>
<div class="item-row">
  <div class="item-left">
    <div class="pos-type"><?php echo $order['customer']; ?></div>
  </div>
  <div class="item-right">
    <div class="pos-time text-right"><?php echo $order['date_added']; ?></div>
  </div>
</div>
</div>
<?php } ?>
<?php } else { ?>
<div class="order_info item" data-id="0">
  <div class="item-row">
    <div class="no-results"><strong><?php echo $text_no_results; ?></strong></div>
  </div>
</div>
<?php } ?>