<header>
  <div class="min-heading">
    <h4><?php echo $text_customers; ?></h4>
  </div>
  <div id="customer-block-search">
  	<input type="text" name="customer_block_search" value="" placeholder="<?php echo $placeholder_search_customers; ?>" class="form-control input-lg" />
    <span class="clear_search"> <i class="fa fa-close" aria-hidden="true"></i></span>
	</div>
</header>
<div class="all-customers scrollert customers_area">
  <div class="scrollert-content customers_content" tabindex="4">
    <ul class="list-unstyled">
      <?php echo $customers; ?>
    </ul>
  </div>
</div>