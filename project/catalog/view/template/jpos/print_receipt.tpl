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
<div class="container pcontainer">
  <?php foreach ($orders as $order) { ?>
  <div style="page-break-after: always;">
    <div class="address-bar">
      <h1 class="text-center"><?php echo $text_invoice; ?></h1>
      <address><?php echo $order['store_address']; ?></address>
    </div>
    <div class="agent-info">
      <table class="table" style="margin: 0;">
        <tr>
          <td style="padding: 0;">
            <?php echo $order['store_name']; ?>
            <br>
            <?php echo $text_served; ?> <?php echo $order['store_owner']; ?>
            <br>
            <?php echo $text_telephone; ?> <?php echo $order['store_telephone']; ?>
            <br>
            <?php if ($order['store_fax']) { ?>
              <?php echo $text_fax; ?> <?php echo $order['store_fax']; ?><br>
            <?php } ?>
            <?php echo $text_email; ?> <?php echo $order['store_email']; ?>
            <br>
            <?php echo $text_website; ?> <?php echo $order['store_url']; ?>
          </td>
          <td style="padding: 0;" align="right">
            <?php echo $text_date_added; ?> <?php echo $order['date_added']; ?>
            <br>
            <?php if ($order['invoice_no']) { ?>
            <?php echo $text_invoice_no; ?> <?php echo $order['invoice_no']; ?><br>
            <?php } ?>
            <?php echo $text_order_id; ?> <?php echo $order['order_id']; ?>
            <br>
            <?php echo $text_payment_method; ?> <?php echo $order['payment_method']; ?>
            <br>
            <?php if ($order['shipping_method']) { ?>
            <?php echo $text_shipping_method; ?> <?php echo $order['shipping_method']; ?><br>
            <?php } ?>
            <?php echo $order['name']; ?>
          </td>
        </tr>
      </table>
    </div>
    <table class="table">
      <thead>
        <tr>
          <td><b><?php echo $column_product; ?></b></td>
          <td><b><?php echo $column_model; ?></b></td>
          <td class="text-right"><b><?php echo $column_quantity; ?></b></td>
          <td class="text-right" style="min-width: 85px;"><b><?php echo $column_price; ?></b></td>
          <td class="text-right"><b><?php echo $column_total; ?></b></td>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($order['product'] as $product) { ?>
        <tr>
          <td><?php echo $product['name']; ?>
            <?php /* foreach ($product['option'] as $option) { ?>
            <br />
            &nbsp;<small> - <?php echo $option['name']; ?>: <?php echo $option['value']; ?></small>
            <?php } */?></td>
          <td><?php echo $product['model']; ?></td>
          <td class="text-right"><?php echo $product['quantity']; ?></td>
          <td class="text-right"><?php echo $product['price']; ?></td>
          <td class="text-right"><?php echo $product['total']; ?></td>
        </tr>
        <?php } ?>
        <?php foreach ($order['voucher'] as $voucher) { ?>
        <tr>
          <td><?php echo $voucher['description']; ?></td>
          <td></td>
          <td class="text-right">1</td>
          <td class="text-right"><?php echo $voucher['amount']; ?></td>
          <td class="text-right"><?php echo $voucher['amount']; ?></td>
        </tr>
        <?php } ?>
      </tbody>
      <tfoot>
        <?php foreach ($order['total'] as $total) { ?>
        <tr>
          <td colspan="4"><?php echo $total['title']; ?></td>
          <td class="text-right"><?php echo $total['text']; ?></td>
        </tr>
        <?php } ?>
      </tfoot>
    </table>
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