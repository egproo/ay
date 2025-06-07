<?php if ($order) { ?>
<header>
  <h3><?php echo $text_order_id; ?> <?php echo $order['order_id']; ?></h3>
  <a class="close-panel close-order-history panel_close" data-panel="#order-history-wrap"><i class="fa fa-close"></i></a>
</header>
<div class="order-status scrollert">
  <div class="scrollert-content">
    <div class="clearfix">
      <div class="col-sm-6">
        <div class="order-status-row">
          <div class="order-status-left">
            <div class="order-total"><?php echo $order['total']; ?></div>
          </div>
          <div class="order-status-right">
            <table class="table table-bordered">
              <tr>
                <td><?php echo $text_status; ?></td>
                <td><i class="fa fa-square" style="<?php if (!empty($colors_order_status[$order['order_status_id']])) { ?>color: <?php echo $colors_order_status[$order['order_status_id']]; ?>;<?php } ?>"></i> <?php echo $order['order_status']; ?> </td>
              </tr>
              <tr>
                <td><?php echo $text_date_added; ?></td>
                <td><?php echo $order['date_added']; ?></td>
              </tr>
              <tr>
                <td><?php echo $text_payment_method; ?></td>
                <td><?php echo $order['payment_method']; ?></td>
              </tr>
              <?php if ($order['shipping_method']) { ?>
              <tr>
                <td><?php echo $text_shipping_method; ?></td>
                <td><?php echo $order['shipping_method']; ?></td>
              </tr>
              <?php } ?>
            </table>
          </div>
        </div>
      </div>
      <div class="col-sm-6">
        <div class="address-panels">
          <div class="billing-address">
            <div class="panel panel-default">
              <div class="panel-heading">
                <h3 class="panel-title"><?php echo $text_address_billing; ?></h3>
              </div>
              <div class="panel-body">
                <?php echo $order['payment_address']; ?>
              </div>
            </div>
          </div>

          <?php if ($order['shipping_method']) { ?>
          <div class="shipping-address">
            <div class="panel panel-default">
              <div class="panel-heading">
                <h3 class="panel-title"><?php echo $text_address_delivery; ?></h3>
              </div>
              <div class="panel-body">
                <?php echo $order['shipping_address']; ?>
              </div>
            </div>
          </div>
          <?php } ?>

        </div>
      </div>
    </div>
    <div class="payment-panels">
     <div class="row">
      <div class="col-sm-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <h3 class="panel-title"><?php echo $text_order_products; ?></h3>
          </div>
          <div class="panel-body">
            <table class="table table-bordered">
              <thead>
                <tr>
                  <td class="text-left"><?php echo $column_product; ?></td>
                  <td class="text-left"><?php echo $column_model; ?></td>
                  <td class="text-right"><?php echo $column_quantity; ?></td>
                  <td class="text-right"><?php echo $column_price; ?></td>
                  <td class="text-right"><?php echo $column_total; ?></td>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($order['products'] as $product) { ?>
                <tr>
                  <td class="text-left"><?php echo $product['name']; ?>
                    <?php foreach ($product['option'] as $option) { ?>
                    <br />
                    <?php if ($option['type'] != 'file') { ?>
                    &nbsp;<small> - <?php echo $option['name']; ?>: <?php echo $option['value']; ?></small>
                    <?php } else { ?>
                    &nbsp;<small> - <?php echo $option['name']; ?>: <a href="<?php echo $option['href']; ?>"><?php echo $option['value']; ?></a></small>
                    <?php } ?>
                    <?php } ?></td>
                  <td class="text-left"><?php echo $product['model']; ?></td>
                  <td class="text-right"><?php echo $product['quantity']; ?></td>
                  <td class="text-right"><?php echo $product['price']; ?></td>
                  <td class="text-right"><?php echo $product['total']; ?></td>
                </tr>
                <?php } ?>
                <?php foreach ($order['vouchers'] as $voucher) { ?>
                <tr>
                  <td class="text-left"><a href="<?php echo $voucher['href']; ?>"><?php echo $voucher['description']; ?></a></td>
                  <td class="text-left"></td>
                  <td class="text-right">1</td>
                  <td class="text-right"><?php echo $voucher['amount']; ?></td>
                  <td class="text-right"><?php echo $voucher['amount']; ?></td>
                </tr>
                <?php } ?>
                <?php foreach ($order['totals'] as $total) { ?>
                <tr>
                  <td colspan="4" class="text-right"><?php echo $total['title']; ?></td>
                  <td class="text-right"><?php echo $total['text']; ?></td>
                </tr>
                <?php } ?>
              </tbody>
            </table>
            <?php if ($order['comment']) { ?>
            <table class="table table-bordered">
              <thead>
                <tr>
                  <td><?php echo $text_comment; ?></td>
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
        </div>
      </div>
      </div>
    </div>
    <div class="order-history-buttons">
      <div class="row">
        <div class="col-sm-4">
          <button type="button" class="btn btn-block btn-primary" id="button-editorder" data-id="<?php echo $order['order_id']; ?>"><i class="fa fa-pencil"></i> <?php echo $button_edit; ?></button>
        </div>
        <div class="col-sm-4">
          <a href="<?php echo $link_print_receipt; ?>" target="_blank" class="btn btn-block btn-default"><i class="fa fa-print"></i> <?php echo $button_print; ?></a>
        </div>
        <div class="col-sm-4">
          <a href="<?php echo $link_print_invoice; ?>" target="_blank" class="btn btn-block btn-success"><i class="fa fa-print"></i> <?php echo $button_invoice; ?></a>
        </div>
      </div>
    </div>
  </div>
</div>
<?php } else { ?>
<header>
  <h3><?php echo $text_no_order; ?></h3>
  <a class="close-panel close-order-history panel_close" data-panel="#order-history-wrap"><i class="fa fa-close"></i></a>
</header>
<div class="order-status scrollert">
  <div class="scrollert-content">
    <div class="no-results">
      <h2><?php echo $text_no_results; ?></h2>
    </div>
  </div>
</div>
<?php } ?>