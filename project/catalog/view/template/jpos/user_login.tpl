<?php echo $header; ?>
<div id="ps-wrapper" class="clearfix">
  <!--posuser login wrap-->
  <div id="ps-login" class="panel-of panel-login active">
    <div id="ps-login-detail" class="col-sm-12 padding-none">
      <div class="col-sm-6 col-sm-offset-3">
        <div class="form-group">
    		  <label class="label-control"><?php echo $entry_username; ?></label>
    		  <input type="text" name="username" value="" class="form-control" placeholder="<?php echo $entry_username; ?>" />
    		</div>
    		<div class="form-group">
    		  <label class="label-control"><?php echo $entry_password; ?></label>
    		  <input type="password" name="password" value="" class="form-control" placeholder="<?php echo $entry_password; ?>" />
    		</div>
    		<button type="button" class="btn btn-primary user_login"><?php echo $button_login; ?></button>
      </div>
    </div>
  </div>
  <!--posuser login wrap end-->
</div>
<?php echo $footer; ?>