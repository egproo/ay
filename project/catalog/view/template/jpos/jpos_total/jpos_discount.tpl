<div class="panel panel-default">
  <div class="panel-heading">
    <h4 class="panel-title"><a href="#collapse-discount" class="accordion-toggle" data-toggle="collapse" data-parent="#accordion"><?php echo $heading_title; ?></a></h4>
  </div>
  <div id="collapse-discount" class="panel-collapse collapse">
    <div class="panel-body">
      <button type="button" class="close" data-dismiss="#collapse-discount" style="position: absolute; top: 0; right: 3px;">&times;</button>
      <select name="jpos_discount_type" class="form-control" >
          <option value="P" <?php echo $jpos_discount_type == 'P' ? 'selected="selected"' : ''; ?>>Percentage</option>
          <option value="F" <?php echo $jpos_discount_type == 'F' ? 'selected="selected"' : ''; ?>>Fixed</option>
        </select>
      <div class="input-group">
        <input type="text" name="jpos_discount" value="<?php echo $jpos_discount; ?>" placeholder="<?php echo $entry_discount; ?>" class="form-control" />
        <span class="input-group-btn">
        <input type="button" value="<?php echo $button_discount; ?>" id="button-discount" data-loading-text="<?php echo $text_loading; ?>"  class="btn btn-primary" />
        </span></div>
    </div>
  </div>
</div>
