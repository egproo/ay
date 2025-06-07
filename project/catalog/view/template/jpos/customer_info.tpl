<?php if ($customer) { ?>
<header>
  <div class="min-heading">
    <h4 class="ps-customer-name"><?php echo $customer['name']; ?></h4>
    <a class="close-panel close-customer-list panel_close" data-panel="#ps-customer-list"><i class="fa fa-close"></i></a>
  </div>
</header>
<div class="edit-customer">
  
  <div class="user-icon">
    <i class="fa fa-user"></i> <?php echo $customer['name_letter']; ?>
  </div>
  <ul class="list-unstyled info">
    <li class="name"><?php echo $customer['name']; ?></li>
    <li><?php echo $customer['telephone']; ?></li>
    <li><?php echo $customer['email']; ?></li>
    <li><?php echo $customer['address']; ?></li>
  </ul>
  <div class="buttons-update clearfix">
    <button class="btn-edit btn btn-primary form_customer_edit" data-id="<?php echo $customer['customer_id']; ?>"><i class="fa fa-edit" data-class="fa fa-edit"></i> <?php echo $button_edit; ?></button>
    <button class="btn-primary btn" id="button-assigncustomer" data-id="<?php echo $customer['customer_id']; ?>"><i class="fa fa-exchange" data-class="fa fa-exchange"></i> <?php echo $button_customer_to_cart; ?></button>
  </div>
</div>
<?php } else { ?>
<header>
  <div class="min-heading">
    <h4 class="ps-customer-name"><?php echo $text_no_customer; ?></h4>
    <a class="close-panel close-customer-list panel_close" data-panel="#ps-customer-list"><i class="fa fa-close"></i></a>
  </div>
</header>
<div class="edit-customer no-results">
  <h1><?php echo $text_no_results; ?></h1>
</div>
<?php } ?>