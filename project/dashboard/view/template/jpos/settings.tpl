<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right">
        <button type="submit" form="form-wachat" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i> <?php echo $button_save; ?></button>
      </div>
      <h1><?php echo $heading_title; ?></h1>
      <ul class="breadcrumb">
         <?php foreach ($breadcrumbs as $breadcrumb) { ?>
        <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
        <?php } ?>
      </ul>
    </div>
  </div>
  <div class="container-fluid">
    <?php if ($error_warning) { ?>
    <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php } ?>
    <?php if ($success) { ?>
    <div class="alert alert-success"><i class="fa fa-exclamation-circle"></i> <?php echo $success; ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php } ?>
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_edit; ?></h3>
        <div class="pull-right">
          <select onchange="location=this.value">
             <?php foreach ($stores as $store) { ?>
            <option value="<?php echo $store['href']; ?>" <?php if ($store_id == $store['store_id']) { ?>selected="selected"<?php } ?>><?php echo $store['name']; ?></option>
             <?php } ?>
          </select>
        </div>
      </div>
      <div class="panel-body">
        <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-wachat" class="form-horizontal">
          <ul class="nav nav-tabs">
            <li class="active"><a href="#tab-general" data-toggle="tab"><i class="fa fa-cogs"></i> <?php echo $tab_general; ?></a></li>
            <li><a href="#tab-css" data-toggle="tab"><i class="fa fa-css3"></i> <?php echo $tab_css; ?></a></li>
            <li><a href="#tab-support" data-toggle="tab"><i class="fa fa-support"></i> <?php echo $tab_support; ?></a></li>
          </ul>
          <div class="tab-content jps-tab-content">
            <div class="tab-pane active" id="tab-general">
              <div class="form-group">
                <label class="col-sm-2 control-label" for="input-status"><?php echo $entry_status; ?></label>
                <div class="col-sm-5">
                  <div class="btn-group btn-group-toggle" data-toggle="buttons">
                    <?php if ($jpos_status) { ?>
                    <label class="btn btn-default active"><input type="radio" name="jpos_status" value="1" checked="checked" /> <?php echo $text_enabled; ?></label>
                    <?php } else { ?>
                    <label class="btn btn-default"><input type="radio" name="jpos_status" value="1" /> <?php echo $text_enabled; ?></label>
                    <?php } ?>
                    <?php if (!$jpos_status) { ?>
                    <label class="btn btn-default active"><input type="radio" name="jpos_status" value="0" checked="checked" /> <?php echo $text_disabled; ?></label>
                    <?php } else { ?>
                    <label class="btn btn-default"><input type="radio" name="jpos_status" value="0" /> <?php echo $text_disabled; ?></label>
                    <?php } ?>
                  </div>
                </div>
              </div>
              <div class="form-group">
                <label class="col-sm-2 control-label"><?php echo $entry_display_shipping; ?></label>
                <div class="col-sm-5">
                  <div class="btn-group btn-group-toggle" data-toggle="buttons">
                    <?php if ($jpos_display_shipping) { ?>
                    <label class="btn btn-default active"><input type="radio" name="jpos_display_shipping" value="1" checked="checked" /> <?php echo $text_show; ?></label>
                    <?php } else { ?>
                    <label class="btn btn-default"><input type="radio" name="jpos_display_shipping" value="1" /> <?php echo $text_show; ?></label>
                    <?php } ?>
                    <?php if (!$jpos_display_shipping) { ?>
                    <label class="btn btn-default active"><input type="radio" name="jpos_display_shipping" value="0" checked="checked" /> <?php echo $text_hide; ?></label>
                    <?php } else { ?>
                    <label class="btn btn-default"><input type="radio" name="jpos_display_shipping" value="0" /> <?php echo $text_hide; ?></label>
                    <?php } ?>
                  </div>
                </div>
              </div>
              <div class="form-group">
                <label class="col-sm-2 control-label"><?php echo $entry_order_note; ?></label>
                <div class="col-sm-5">
                  <div class="btn-group btn-group-toggle" data-toggle="buttons">
                    <?php if ($jpos_order_note) { ?>
                    <label class="btn btn-default active"><input type="radio" name="jpos_order_note" value="1" checked="checked" /> <?php echo $text_show; ?></label>
                    <?php } else { ?>
                    <label class="btn btn-default"><input type="radio" name="jpos_order_note" value="1" /> <?php echo $text_show; ?></label>
                    <?php } ?>
                    <?php if (!$jpos_order_note) { ?>
                    <label class="btn btn-default active"><input type="radio" name="jpos_order_note" value="0" checked="checked" /> <?php echo $text_hide; ?></label>
                    <?php } else { ?>
                    <label class="btn btn-default"><input type="radio" name="jpos_order_note" value="0" /> <?php echo $text_hide; ?></label>
                    <?php } ?>
                  </div>
                </div>
              </div>
              <div class="form-group">
                <label class="col-sm-2 control-label"><?php echo $entry_total_display; ?></label>
                <div class="col-sm-5">
                  <div class="btn-group btn-group-toggle" data-toggle="buttons">
                    <?php if ($jpos_total_display) { ?>
                    <label class="btn btn-default active"><input type="radio" name="jpos_total_display" value="1" checked="checked" /> <?php echo $text_show; ?></label>
                    <?php } else { ?>
                    <label class="btn btn-default"><input type="radio" name="jpos_total_display" value="1" /> <?php echo $text_show; ?></label>
                    <?php } ?>
                    <?php if (!$jpos_total_display) { ?>
                    <label class="btn btn-default active"><input type="radio" name="jpos_total_display" value="0" checked="checked" /> <?php echo $text_hide; ?></label>
                    <?php } else { ?>
                    <label class="btn btn-default"><input type="radio" name="jpos_total_display" value="0" /> <?php echo $text_hide; ?></label>
                    <?php } ?>
                  </div>
                </div>
              </div>
              <div class="form-group">
                <label class="col-sm-2 control-label"><?php echo $entry_cart_block; ?></label>
                <div class="col-sm-5">
                  <div class="btn-group btn-group-toggle" data-toggle="buttons">
                    <?php if ($jpos_cart_block) { ?>
                    <label class="btn btn-default active"><input type="radio" name="jpos_cart_block" value="1" checked="checked" /> <?php echo $text_yes; ?></label>
                    <?php } else { ?>
                    <label class="btn btn-default"><input type="radio" name="jpos_cart_block" value="1" /> <?php echo $text_yes; ?></label>
                    <?php } ?>
                    <?php if (!$jpos_cart_block) { ?>
                    <label class="btn btn-default active"><input type="radio" name="jpos_cart_block" value="0" checked="checked" /> <?php echo $text_no; ?></label>
                    <?php } else { ?>
                    <label class="btn btn-default"><input type="radio" name="jpos_cart_block" value="0" /> <?php echo $text_no; ?></label>
                    <?php } ?>
                  </div>
                </div>
              </div>
              <div class="form-group">
                <label class="col-sm-2 control-label" for="input-order_statues_colors"><?php echo $entry_order_statues_colors; ?></label>
                <div class="col-sm-5">
                  <div class="well well-sm" style="height: 150px; overflow: auto;">
                    <?php foreach ($order_statuses as $order_status) { ?>
                    <div class="input-group colorpicker">
                      <label><?php echo $order_status['name']; ?></label>

                      <span class="input-group-addon"><i></i></span>
                      <input class="form-control" type="text" name="jpos_colors_order_status[<?php echo $order_status['order_status_id']; ?>]" value="<?php if(isset($jpos_colors_order_status[$order_status['order_status_id']])) { echo $jpos_colors_order_status[$order_status['order_status_id']]; } ?>"  />
                    </div>
                    <?php } ?>
                  </div>
                </div>
              </div>
            </div>
            <div class="tab-pane" id="tab-css">
              <div class="form-group">
                <label class="col-sm-12 control-label text-left" for="input-css"><?php echo $entry_css; ?></label>
                <div class="col-sm-12">
                  <textarea name="jpos_css" rows="7" placeholder="<?php echo $entry_css; ?>" id="input-css" class="form-control"><?php echo $jpos_css; ?></textarea>
                </div>
              </div>
            </div>
            <div class="tab-pane" id="tab-support">
              <div class="card-deck mb-3 text-center">
                <div class="card mb-4 shadow-sm">
                  <div class="card-header">
                    <h2 class="my-0">Support</h2>
                  </div>
                  <div class="card-body">
                    <h4 class="card-title pricing-card-title">For Support Send E-mail at <big class="text-muted">extensionstudio.oc@gmail.com</big></h4>
                    <a target="_BLANK" href="https://www.opencart.com/index.php?route=marketplace/extension&filter_member=ExtensionStudio" class="btn btn-lg btn-primary">View Other Extensions</a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
  <style type="text/css">
  .colorpicker label {
    width: 100px;
    display: table-cell;
    vertical-align: middle;
  }
  </style>
  <script type="text/javascript"><!--
  // Color Picker
  $(function() { $('.colorpicker').colorpicker(); });
  //--></script>

</div>
<?php echo $footer; ?>