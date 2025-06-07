<?php foreach($totals as $total) { ?>
<li>
  <div class="cart-label"><?php echo $total['title']; ?></div>
  <div class="price"><?php echo $total['text']; ?></div>
</li>
<?php } ?>