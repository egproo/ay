<?php if ($product) { ?>
<div class="row flex">
  <div class="col-sm-4">
    <img src="<?php echo $product['thumb']; ?>" alt="<?php echo $product['name']; ?>" title="<?php echo $product['name']; ?>" class="img-responsive" />
  </div>
  <div class="col-sm-8 flex-dir">

      <ul class="nav nav-tabs nav-justified">
        <li class="active"><a data-toggle="tab" href="#item_options"><?php echo $tab_options; ?></a></li>
        <li><a data-toggle="tab" href="#item_detail"><?php echo $tab_details; ?></a></li>
      </ul>
      <div class="tab-content">
        <div id="item_options" class="tab-pane fade in active">
          <div class="product-bar">
            <ul class="list-inline">
              <li class="name"><?php echo $product['name']; ?><?php if ($product['model']) { ?><span><?php echo $text_model; ?> <?php echo $product['model']; ?></span><?php } ?><span><?php if ($product['points']) { ?>
              <?php echo $text_points; ?> <?php echo $product['points']; ?></span></li>
              <?php } ?></li>
              <?php if ($product['price']) { ?>
              <li class="price price-group">
                <?php if (!$product['special']) { ?>
                 <div class="product-price"><?php echo $product['price']; ?></div>
               <?php } else { ?>
                 <div class="product-price"><?php echo $product['special']; ?></div>
                 <div class="product-price-old"><?php echo $product['price']; ?></div>
                 <?php if ($product['tax']) { ?>
                    <div class="product-tax"><?php echo $text_tax; ?> <?php echo $product['tax']; ?></div>
                 <?php } ?>
               <?php } ?>
              </li>
              <?php } ?>
            </ul>
          </div>
          <form class="form-horizontal">
            <div class="form-group">
              <label class="control-label col-sm-3"><?php echo $text_qty; ?></label>
              <div class="col-sm-4">
                <div class="input-group qty-group">
                  <span class="input-group-addon qty-minus" data-action="+"><i class="fa fa-minus"></i></span>
                  <input type="text" name="quantity" class="form-control" value="<?php echo $product['minimum']; ?>" data-min="<?php echo $product['minimum']; ?>" data-interval="1">
                  <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                  <span class="input-group-addon qty-plus" data-action="-"><i class="fa fa-plus"></i></span>
                </div>
              </div>
            </div>
            <?php if ($product['options']) { ?>
              <?php foreach ($product['options'] as $option) { ?>
              <?php if ($option['type'] == 'select') { ?>
              <div class="form-group<?php echo ($option['required'] ? ' required' : ''); ?>">
                <label class="control-label col-sm-3" for="input-option<?php echo $option['product_option_id']; ?>"><?php echo $option['name']; ?></label>
                <div class="col-sm-9">
                  <select name="option[<?php echo $option['product_option_id']; ?>]" id="input-option<?php echo $option['product_option_id']; ?>" class="form-control">
                    <option value=""><?php echo $text_select; ?></option>
                    <?php foreach ($option['product_option_value'] as $option_value) { ?>
                    <option value="<?php echo $option_value['product_option_value_id']; ?>"><?php echo $option_value['name']; ?>
                    <?php if ($option_value['price']) { ?>
                    (<?php echo $option_value['price_prefix']; ?><?php echo $option_value['price']; ?>)
                    <?php } ?>
                    </option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <?php } ?>
              <?php if ($option['type'] == 'radio') { ?>
              <div class="form-group<?php echo ($option['required'] ? ' required' : ''); ?>">
                <label class="control-label col-sm-3"><?php echo $option['name']; ?></label>
                <div class="col-sm-9" id="input-option<?php echo $option['product_option_id']; ?>">
                  <?php foreach ($option['product_option_value'] as $option_value) { ?>
                  <div class="radio">
                    <label>
                      <input type="radio" name="option[<?php echo $option['product_option_id']; ?>]" value="<?php echo $option_value['product_option_value_id']; ?>" />
                      <?php if ($option_value['image']) { ?>
                      <img src="<?php echo $option_value['image']; ?>" alt="<?php echo $option_value['name'] . ($option_value['price'] ? ' ' . $option_value['price_prefix'] . $option_value['price'] : ''); ?>" class="img-thumbnail" />
                      <?php } ?>
                      <?php echo $option_value['name']; ?>
                      <?php if ($option_value['price']) { ?>
                      (<?php echo $option_value['price_prefix']; ?><?php echo $option_value['price']; ?>)
                      <?php } ?>
                    </label>
                  </div>
                  <?php } ?>
                </div>
              </div>
              <?php } ?>
              <?php if ($option['type'] == 'checkbox') { ?>
              <div class="form-group<?php echo ($option['required'] ? ' required' : ''); ?>">
                <label class="control-label col-sm-3"><?php echo $option['name']; ?></label>
                <div class="col-sm-9" id="input-option<?php echo $option['product_option_id']; ?>">
                  <?php foreach ($option['product_option_value'] as $option_value) { ?>
                  <div class="checkbox">
                    <label>
                      <input type="checkbox" name="option[<?php echo $option['product_option_id']; ?>][]" value="<?php echo $option_value['product_option_value_id']; ?>" />
                      <?php if ($option_value['image']) { ?>
                      <img src="<?php echo $option_value['image']; ?>" alt="<?php echo $option_value['name'] . ($option_value['price'] ? ' ' . $option_value['price_prefix'] . $option_value['price'] : ''); ?>" class="img-thumbnail" />
                      <?php } ?>
                      <?php echo $option_value['name']; ?>
                      <?php if ($option_value['price']) { ?>
                      (<?php echo $option_value['price_prefix']; ?><?php echo $option_value['price']; ?>)
                      <?php } ?>
                    </label>
                  </div>
                  <?php } ?>
                </div>
              </div>
              <?php } ?>
              <?php if ($option['type'] == 'text') { ?>
              <div class="form-group<?php echo ($option['required'] ? ' required' : ''); ?>">
                <label class="col-sm-3 control-label" for="input-option<?php echo $option['product_option_id']; ?>"><?php echo $option['name']; ?></label>
                <div class="col-sm-9">
                  <input type="text" name="option[<?php echo $option['product_option_id']; ?>]" value="<?php echo $option['value']; ?>" placeholder="<?php echo $option['name']; ?>" id="input-option<?php echo $option['product_option_id']; ?>" class="form-control" />
                </div>
              </div>
              <?php } ?>
              <?php if ($option['type'] == 'textarea') { ?>
              <div class="form-group<?php echo ($option['required'] ? ' required' : ''); ?>">
                <label class="control-label col-sm-3" for="input-option<?php echo $option['product_option_id']; ?>"><?php echo $option['name']; ?></label>
                <div class="col-sm-9">
                  <textarea name="option[<?php echo $option['product_option_id']; ?>]" rows="5" placeholder="<?php echo $option['name']; ?>" id="input-option<?php echo $option['product_option_id']; ?>" class="form-control"><?php echo $option['value']; ?></textarea>
                </div>
              </div>
              <?php } ?>
              <?php if ($option['type'] == 'file') { ?>
              <div class="form-group<?php echo ($option['required'] ? ' required' : ''); ?>">
                <label class="control-label col-sm-3"><?php echo $option['name']; ?></label>
                <div class="col-sm-9">
                  <button type="button" id="button-upload<?php echo $option['product_option_id']; ?>" data-loading-text="<?php echo $text_loading; ?>" class="btn btn-default btn-block"><i class="fa fa-upload"></i> <?php echo $button_upload; ?></button>
                  <input type="hidden" name="option[<?php echo $option['product_option_id']; ?>]" value="" id="input-option<?php echo $option['product_option_id']; ?>" />
                </div>
              </div>
              <?php } ?>
              <?php if ($option['type'] == 'date') { ?>
              <div class="form-group<?php echo ($option['required'] ? ' required' : ''); ?>">
                <label class="control-label col-sm-3" for="input-option<?php echo $option['product_option_id']; ?>"><?php echo $option['name']; ?></label>
                <div class="input-group date col-sm-9">
                  <input type="text" name="option[<?php echo $option['product_option_id']; ?>]" value="<?php echo $option['value']; ?>" data-date-format="YYYY-MM-DD" id="input-option<?php echo $option['product_option_id']; ?>" class="form-control" />
                  <span class="input-group-btn">
                  <button class="btn btn-default" type="button"><i class="fa fa-calendar"></i></button>
                  </span>
                </div>
              </div>
              <?php } ?>
              <?php if ($option['type'] == 'datetime') { ?>
              <div class="form-group<?php echo ($option['required'] ? ' required' : ''); ?>">
                <label class="col-sm-3 control-label" for="input-option<?php echo $option['product_option_id']; ?>"><?php echo $option['name']; ?></label>
                <div class="col-sm-9 input-group datetime">
                  <input type="text" name="option[<?php echo $option['product_option_id']; ?>]" value="<?php echo $option['value']; ?>" data-date-format="YYYY-MM-DD HH:mm" id="input-option<?php echo $option['product_option_id']; ?>" class="form-control" />
                  <span class="input-group-btn">
                  <button type="button" class="btn btn-default"><i class="fa fa-calendar"></i></button>
                  </span></div>
              </div>
              <?php } ?>
              <?php if ($option['type'] == 'time') { ?>
              <div class="form-group<?php echo ($option['required'] ? ' required' : ''); ?>">
                <label class="col-sm-3 control-label" for="input-option<?php echo $option['product_option_id']; ?>"><?php echo $option['name']; ?></label>
                <div class="input-group time col-sm-9">
                  <input type="text" name="option[<?php echo $option['product_option_id']; ?>]" value="<?php echo $option['value']; ?>" data-date-format="HH:mm" id="input-option<?php echo $option['product_option_id']; ?>" class="form-control" />
                  <span class="input-group-btn">
                  <button type="button" class="btn btn-default"><i class="fa fa-calendar"></i></button>
                  </span>
                </div>
              </div>
              <?php } ?>
              <?php } ?>
            <?php } ?>
         </form>
        </div>
        <div id="item_detail" class="tab-pane fade">
          <table class="table table-striped">
            <tbody>
              <tr>
                <td><?php echo $text_product_id; ?></td>
                <td>#<?php echo $product['product_id']; ?></td>
              </tr>
              <tr>
                <td><?php echo $text_qty_available; ?></td>
                <td><?php echo $product['quantity']; ?></td>
              </tr>
              <tr>
                <td><?php echo $text_instock; ?></td>
                <td><?php echo $product['stock_status']; ?></td>
              </tr>
              <?php if ($product['tax_class_id'] && $product['tax_class']) { ?>
              <tr>
                <td><?php echo $text_tax_class; ?></td>
                <td><?php echo $product['tax_class']; ?></td>
              </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    <div class="buttons text-right">
      <button class="btn btn-default" data-dismiss="modal"><i class="fa fa-close"></i> <?php echo $button_cancel; ?></button>
      <button class="btn btn-primary button-cartadd" onclick="poscart.cartadd();"><i class="fa fa-cart-plus"></i> <?php echo $button_add_to_cart; ?></button>
    </div>
  </div>
</div>
<?php } ?>