<!DOCTYPE html>
<html dir="<?php echo $direction; ?>" lang="<?php echo $lang; ?>">
<head>
<meta charset="UTF-8" />
<title><?php echo $title; ?></title>
<base href="<?php echo $base; ?>" />
<link href="catalog/view/javascript/bootstrap/css/bootstrap.min.css" rel="stylesheet" media="all" />
<script type="text/javascript" src="catalog/view/javascript/jquery/jquery-2.1.1.min.js"></script>
<script type="text/javascript" src="catalog/view/javascript/bootstrap/js/bootstrap.min.js"></script>
<link href="catalog/view/javascript/font-awesome/css/font-awesome.min.css" type="text/css" rel="stylesheet" />
<link type="text/css" href="catalog/view/theme/default/stylesheet/jpos/stylesheet.css" rel="stylesheet" media="all" />
</head>
<body>
<div class="container inv-container">
  <?php foreach ($orders as $order) { ?>
  <table class="table">
    <tr>
      <td style="padding: 0;border:none;"><img width="250" src="<?php echo $store_logo; ?>" class="img-responsive"/></td>
      <td style="padding: 0;border:none;width: 225px;" class="store-info">
        <div class="jastore"><?php echo $order['store_name']; ?></div>
        <div class="add"><i class="fa fa-map-marker" aria-hidden="true"></i> <span><?php echo $order['store_address']; ?></span></div>
        <div class="email"><i class="fa fa-envelope" aria-hidden="true"></i> <span><?php echo $order['store_email']; ?></span></div>
        <div class="tele"><i class="fa fa-phone" aria-hidden="true"></i> <span><?php echo $order['store_telephone']; ?></span></div>
        <?php if ($order['store_fax']) { ?>
          <div class="fax"><i class="fa fa-fax" aria-hidden="true"></i> <span><?php echo $order['store_fax']; ?></span></div>
        <?php } ?>
        <div class="web"><i class="fa fa-globe" aria-hidden="true"></i> <span><?php echo $order['store_url']; ?></span></div>
      </td>
    </tr>
  </table>
  <table class="table">
    <tr>
      <td style="padding: 0;border:none;"><?php echo $order['payment_address']; ?></td>
      <td style="padding: 0;border:none; width: 395px;">
        <div class="inv-detail">
          <ul class="list-inline">
            <?php if ($order['invoice_no']) { ?>
              <li class="invoice-nb"><?php echo $text_invoice_no; ?> <?php echo $order['invoice_no']; ?></li>
            <?php } ?>
            <li><b><?php echo $text_order_id; ?></b> <span><?php echo $order['order_id']; ?></span></li>
            <li><b><?php echo $text_date_added; ?></b> <span><?php echo $order['date_added']; ?></span></li>
            <li><b><?php echo $text_payment_method; ?></b> <span><?php echo $order['payment_method']; ?></span></li>
            <?php if ($order['shipping_method']) { ?>
              <li><b><?php echo $text_shipping_method; ?></b> <span><?php echo $order['shipping_method']; ?></span></li>
            <?php } ?>
          </ul>
        </div>
      </td>
    </tr>
  </table>
    <!-- <h1><?php echo $text_invoice; ?> #<?php echo $order['order_id']; ?></h1> -->
    <table class="table table-striped">
      <thead>
        <tr>
          <td><b><?php echo $column_product; ?></b></td>
          <td><b><?php echo $column_model; ?></b></td>
          <td class="text-right"><b><?php echo $column_quantity; ?></b></td>
          <td class="text-right"><b><?php echo $column_price; ?></b></td>
          <td class="text-right"><b><?php echo $column_total; ?></b></td>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($order['product'] as $product) { ?>
        <tr>
          <td><?php echo $product['name']; ?></td>
          <td><?php echo $product['model']; ?></td>
          <td class="text-right"><?php echo $product['quantity']; ?></td>
          <td class="text-right"><?php echo $product['price']; ?></td>
          <td class="text-right"><?php echo $product['total']; ?></td>
        </tr>
        <?php } ?>

      </tbody>
    </table>
    <div class="row">
      <div class="col-sm-5 pull-right">
        <table class="table table-striped totals">
          <tbody>
           <?php foreach ($order['voucher'] as $voucher) { ?>
            <tr>
              <td><?php echo $voucher['description']; ?></td>
              <td></td>
              <td class="text-left">1</td>
              <td class="text-left"><?php echo $voucher['amount']; ?></td>
              <td class="text-left"><?php echo $voucher['amount']; ?></td>
            </tr>
            <?php } ?>

            <?php foreach ($order['total'] as $total) { ?>
            <tr>
              <td class="text-left" colspan="4"><b><?php echo $total['title']; ?></b></td>
              <td class="text-right"><?php echo $total['text']; ?></td>
            </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php if ($order['comment']) { ?>
    <table class="table table-bordered">
      <thead>
        <tr>
          <td><b><?php echo $text_comment; ?></b></td>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><?php echo $order['comment']; ?></td>
        </tr>
      </tbody>
    </table>
    <?php } ?>
  </div>
  <?php } ?>
</div>
</body>
</html>