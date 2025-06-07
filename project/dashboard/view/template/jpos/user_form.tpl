<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right">
        <button type="submit" form="form-user" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
        <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a></div>
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
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_form; ?></h3>
      </div>
      <div class="panel-body">
        <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-user" class="form-horizontal">
          <ul class="nav nav-tabs">
            <li class="active"><a href="#tab-personal" data-toggle="tab"><i class="fa fa-user"></i> <?php echo $tab_personal; ?></a></li>
            <li><a href="#tab-store" data-toggle="tab"><i class="fa fa-home"></i> <?php echo $tab_store; ?></a></li>
          </ul>
          <div class="tab-content">
            <div class="tab-pane active" id="tab-personal">
              <div class="form-group required">
                <label class="col-sm-2 control-label" for="input-username"><?php echo $entry_username; ?></label>
                <div class="col-sm-10">
                  <input type="text" name="username" value="<?php echo $username; ?>" placeholder="<?php echo $entry_username; ?>" id="input-username" class="form-control" />
                  <?php if ($error_username) { ?>
                  <div class="text-danger"><?php echo $error_username; ?></div>
                  <?php } ?>
                </div>
              </div>
              <div class="form-group required">
                <label class="col-sm-2 control-label" for="input-firstname"><?php echo $entry_firstname; ?></label>
                <div class="col-sm-10">
                  <input type="text" name="firstname" value="<?php echo $firstname; ?>" placeholder="<?php echo $entry_firstname; ?>" id="input-firstname" class="form-control" />
                  <?php if ($error_firstname) { ?>
                  <div class="text-danger"><?php echo $error_firstname; ?></div>
                  <?php } ?>
                </div>
              </div>
              <div class="form-group required">
                <label class="col-sm-2 control-label" for="input-lastname"><?php echo $entry_lastname; ?></label>
                <div class="col-sm-10">
                  <input type="text" name="lastname" value="<?php echo $lastname; ?>" placeholder="<?php echo $entry_lastname; ?>" id="input-lastname" class="form-control" />
                  <?php if ($error_lastname) { ?>
                  <div class="text-danger"><?php echo $error_lastname; ?></div>
                  <?php } ?>
                </div>
              </div>
              <div class="form-group required">
                <label class="col-sm-2 control-label" for="input-email"><?php echo $entry_email; ?></label>
                <div class="col-sm-10">
                  <input type="text" name="email" value="<?php echo $email; ?>" placeholder="<?php echo $entry_email; ?>" id="input-email" class="form-control" />
                  <?php if ($error_email) { ?>
                  <div class="text-danger"><?php echo $error_email; ?></div>
                  <?php } ?>
                </div>
              </div>
              <div class="form-group required">
                <label class="col-sm-2 control-label" for="input-location"><?php echo $entry_location; ?></label>
                <div class="col-sm-10">
                  <div class="well well-sm" style="height: 150px; overflow: auto;">
                    <?php foreach ($jpos_locations as $location) { ?>
                    <div class="checkbox">
                      <label><input type="checkbox" name="locations[]" value="<?php echo $location['jpos_location_id']; ?>" <?php if ( in_array($location['jpos_location_id'], $locations)) { ?>checked="checked"<?php } ?> /> <?php echo $location['name']; ?></label>
                    </div>
                    <?php } ?>
                  </div>
                  <?php if ($error_locations) { ?>
                  <div class="text-danger"><?php echo $error_locations; ?></div>
                  <?php } ?>
                </div>
              </div>
              <div class="form-group">
                <label class="col-sm-2 control-label" for="input-image"><?php echo $entry_image; ?></label>
                <div class="col-sm-10"><a href="" id="thumb-image" data-toggle="image" class="img-thumbnail"><img src="<?php echo $thumb; ?>" alt="" title="" data-placeholder="<?php echo $placeholder; ?>" /></a>
                  <input type="hidden" name="image" value="<?php echo $image; ?>" id="input-image" />
                </div>
              </div>
              <div class="form-group required">
                <label class="col-sm-2 control-label" for="input-password"><?php echo $entry_password; ?></label>
                <div class="col-sm-10">
                  <input type="password" name="password" value="<?php echo $password; ?>" placeholder="<?php echo $entry_password; ?>" id="input-password" class="form-control" autocomplete="off" />
                  <?php if ($error_password) { ?>
                  <div class="text-danger"><?php echo $error_password; ?></div>
                  <?php  } ?>
                </div>
              </div>
              <div class="form-group required">
                <label class="col-sm-2 control-label" for="input-confirm"><?php echo $entry_confirm; ?></label>
                <div class="col-sm-10">
                  <input type="password" name="confirm" value="<?php echo $confirm; ?>" placeholder="<?php echo $entry_confirm; ?>" id="input-confirm" class="form-control" />
                  <?php if ($error_confirm) { ?>
                  <div class="text-danger"><?php echo $error_confirm; ?></div>
                  <?php  } ?>
                </div>
              </div>
              <div class="form-group">
                <label class="col-sm-2 control-label" for="input-status"><?php echo $entry_status; ?></label>
                <div class="col-sm-10">
                  <select name="status" id="input-status" class="form-control">
                    <?php if ($status) { ?>
                    <option value="0"><?php echo $text_disabled; ?></option>
                    <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                    <?php } else { ?>
                    <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                    <option value="1"><?php echo $text_enabled; ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
            </div>
            <div class="tab-pane" id="tab-store">
              <div class="form-group">
                <label class="col-sm-2 control-label"><?php echo $entry_store_name; ?></label>
                <div class="col-sm-10">
                  <input type="text" name="store_name" value="<?php echo $store_name; ?>" placeholder="<?php echo $entry_store_name; ?>" class="form-control" />
                </div>
              </div>


              <div class="form-group">
                <label class="col-sm-2 control-label"><?php echo $entry_store_owner; ?></label>
                <div class="col-sm-10">
                  <input type="text" name="store_owner" value="<?php echo $store_owner; ?>" placeholder="<?php echo $entry_store_owner; ?>" class="form-control" />
                </div>
              </div>


              <div class="form-group">
                <label class="col-sm-2 control-label"><?php echo $entry_store_address; ?></label>
                <div class="col-sm-10">
                  <textarea name="store_address" placeholder="<?php echo $entry_store_address; ?>" rows="5" class="form-control"><?php echo $store_address; ?></textarea>
                </div>
              </div>


              <div class="form-group">
                <label class="col-sm-2 control-label"><?php echo $entry_store_email; ?></label>
                <div class="col-sm-10">
                  <input type="text" name="store_email" value="<?php echo $store_email; ?>" placeholder="<?php echo $entry_email; ?>" class="form-control" />
                  <?php if ($error_store_email) { ?>
                  <div class="text-danger"><?php echo $error_store_email; ?></div>
                  <?php } ?>
                </div>
              </div>

              <div class="form-group">
                <label class="col-sm-2 control-label"><?php echo $entry_store_telephone; ?></label>
                <div class="col-sm-10">
                  <input type="text" name="store_telephone" value="<?php echo $store_telephone; ?>" placeholder="<?php echo $entry_store_telephone; ?>" class="form-control" />
                </div>
              </div>

              <div class="form-group">
                <label class="col-sm-2 control-label"><?php echo $entry_store_logo; ?></label>
                <div class="col-sm-10"><a href="" id="thumb-store-logo" data-toggle="image" class="img-thumbnail"><img src="<?php echo $thumb_store_logo; ?>" alt="" title="" data-placeholder="<?php echo $placeholder; ?>" /></a>
                  <input type="hidden" name="store_logo" value="<?php echo $store_logo; ?>" id="input-store-logo" />
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php echo $footer; ?>