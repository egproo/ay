<div class="panel panel-default">
  <div class="panel-heading">
    <h4 class="panel-title"><a href="#collapse-charge" class="accordion-toggle" data-toggle="collapse" data-parent="#accordion"><?php echo $heading_title; ?></a></h4>
  </div>
  <div id="collapse-charge" class="panel-collapse collapse">
    <div class="panel-body">
    <button type="button" class="close" data-dismiss="#collapse-charge" style="position: absolute; top: 0; right: 3px;">&times;</button>
      <div class="input-group">
        <input type="text" name="jpos_charge_title" value="<?php echo $jpos_charge_title; ?>" placeholder="<?php echo $entry_charge_title; ?>" class="form-control" />
        <span class="input-group-btn">
          <select name="jpos_charge_type" class="btn" >
            <option value="P" <?php echo $jpos_charge_type == 'P' ? 'selected="selected"' : ''; ?>>Percentage</option>
            <option value="F" <?php echo $jpos_charge_type == 'F' ? 'selected="selected"' : ''; ?>>Fixed</option>
          </select>
        </span>
      </div>
      <div class="input-group">
        <input type="text" name="jpos_charge" value="<?php echo $jpos_charge; ?>" placeholder="<?php echo $entry_charge; ?>" class="form-control" />
        <span class="input-group-btn">
        <input type="button" value="<?php echo $button_charge; ?>" id="button-charge" data-loading-text="<?php echo $text_loading; ?>"  class="btn btn-primary" />
        </span></div>
    </div>
  </div>
</div>
