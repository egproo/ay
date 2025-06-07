<?php if ($customers) { ?>
<?php foreach ($customers as $customer) { ?>
<li>
  <a class="customer_info" data-target="" data-id="<?php echo $customer['customer_id']; ?>">
    <div class="user-name"><?php echo $customer['name_letter']; ?></div>
    <div class="user-info">
      <h3><?php echo $customer['name']; ?></h3>
      <span><i class="fa fa-envelope"></i> <?php echo $customer['email']; ?></span>
      <span><i class="fa fa-map-marker"></i> <?php echo $customer['address']; ?></span>
    </div>
  </a>
</li>
<?php } ?>
<?php } else { ?>
<li class="no-results">
	<a class="customer_info" data-target="" data-id="0">
		<div class="user-info">
			<strong><?php echo $text_no_results; ?></strong>
		</div>
	</a>
</li>
<?php } ?>