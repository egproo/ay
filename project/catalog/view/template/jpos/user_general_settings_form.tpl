<div class="form-group required">
  <label class="control-label" for="input-user-locations"><?php echo $entry_location; ?></label>
  <select name="default_location" id="input-user-locations" class="form-control" data-currency_id="<?php echo $default_currency_id; ?>" data-language_id="<?php echo $default_language_id; ?>">
    <option value=""><?php echo $text_select; ?></option>
    <?php foreach ($locations as $location) { ?>
    <option value="<?php echo $location['jpos_location_id']; ?>" <?php if ($location['jpos_location_id'] == $default_location_id) { ?>selected="selected"<?php } ?>><?php echo $location['name']; ?></option>
    <?php } ?>
  </select>
</div>
<div class="form-group required">
  <label class="control-label" for="input-user-language"><?php echo $entry_language; ?></label>
  <select name="default_language" id="input-user-language" class="form-control">
  </select>
</div>
<div class="form-group required">
  <label class="control-label" for="input-user-currency"><?php echo $entry_currency; ?></label>
  <select name="default_currency" id="input-user-currency" class="form-control">
  </select>
</div>