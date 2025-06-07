<?php if ($products) { ?>
<?php foreach ($products as $product) { ?>
<div class="col-lg-3 col-md-4 col-sm-4 col-xs-6 product-cols">
  <div class="see_info product_info"><i class="fa fa-info-circle"></i></div>
  <a class="item_info" data-id="<?php echo $product['product_id']; ?>" data-options="<?php echo $product['hasoptions']; ?>" data-minimum="<?php echo $product['minimum']; ?>">
    <div class="product-col">
      <div class="thumb">
        <img src="<?php echo $product['thumb']; ?>" alt="<?php echo $product['name']; ?>" title="<?php echo $product['name']; ?>" class="img-responsive" />
      </div>
      <div class="caption">
        <h4><?php echo $product['name']; ?></h4>
        <?php if (!$product['special']) { ?>
        <div class="price"><?php echo $product['price']; ?></div>
        <?php } else { ?>
        <div class="oprice"><?php echo $product['price']; ?></div>
        <div class="sprice"><?php echo $product['special']; ?></div>
        <?php } ?>
      </div>
    </div>
  </a>
</div>
<?php } ?>
<?php } else { ?>
<div class="col-sm-12">
  <div class="no-results"><h2><?php echo $text_no_results; ?></h2></div>
</div>
<?php } ?>