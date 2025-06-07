<div class="form-group required">
  <label class="control-label"><?php echo $entry_firstname; ?></label>
  <input type="text" class="form-control" placeholder="<?php echo $entry_firstname; ?>" name="firstname" value="<?php echo $firstname; ?>" />
</div>
<div class="form-group required">
  <label class="control-label"><?php echo $entry_lastname; ?></label>
  <input type="text" class="form-control" placeholder="<?php echo $entry_lastname; ?>" name="lastname" value="<?php echo $lastname; ?>" />
</div>
<div class="form-group required">
  <label class="control-label"><?php echo $entry_email; ?></label>
  <input type="text" class="form-control" placeholder="<?php echo $entry_email; ?>" name="email" value="<?php echo $email; ?>" />
</div>

<div class="form-group">
  <label class="control-label"><?php echo $entry_password_old; ?></label>
  <input type="password" class="form-control" placeholder="<?php echo $entry_password_old; ?>" name="password_previous" autocomplete="off" />
</div>

<div class="form-group">
  <label class="control-label"><?php echo $entry_password; ?></label>
  <input type="password" class="form-control" placeholder="<?php echo $entry_password; ?>" name="password" autocomplete="off" />
</div>
<div class="form-group">
  <label class="control-label"><?php echo $entry_password_confirm; ?></label>
  <input type="password" class="form-control" placeholder="<?php echo $entry_password_confirm; ?>" name="password_confirm" autocomplete="off" />
</div>