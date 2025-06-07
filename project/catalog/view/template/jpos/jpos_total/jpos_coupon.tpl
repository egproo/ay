<div class="panel panel-default">
  <div class="panel-heading">
    <h4 class="panel-title"><a href="#collapse-coupon" class="accordion-toggle" data-toggle="collapse" data-parent="#accordion"><?php echo $heading_title; ?></a></h4>
  </div>
  <div id="collapse-coupon" class="panel-collapse collapse">
    <div class="panel-body">
      <button type="button" class="close" data-dismiss="#collapse-coupon" style="position: absolute; top: 0; right: 3px;">&times;</button>
      <!-- <label class="col-sm-12 control-label" for="input-coupon"><?php echo $entry_coupon; ?></label> -->
      <div class="input-group">
        <input type="text" name="jpos_coupon" value="<?php echo $jpos_coupon; ?>" placeholder="<?php echo $entry_coupon; ?>" id="input-coupon" class="form-control" />
        <span class="input-group-btn">
        <input type="button" value="<?php echo $button_coupon; ?>" id="button-coupon" data-loading-text="<?php echo $text_loading; ?>"  class="btn btn-primary" />
        </span></div>
    </div>
  </div>
</div>
