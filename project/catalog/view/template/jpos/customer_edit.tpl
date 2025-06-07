<?php if ($customer) { ?>
<header>
  <div class="min-heading">
    <h4 class="ps-customer-name"><?php echo $customer['name']; ?></h4>
    <a class="close-panel content_destroy" data-target="#ps-customer-list .customer-detail-edit"><i class="fa fa-close"></i></a>
  </div>
</header>
<div class="edit-customer-form scrollert">
  <div class="scrollert-content">
    <div class="customer-form">
    	<fieldset id="edit-account">
    		<legend><?php echo $legend_your_details; ?></legend>
  		  <div class="form-group required clearfix">
  		    <label class="col-sm-2 control-label"><?php echo $entry_firstname; ?></label>
  		    <div class="col-sm-10">
  		      <input name="firstname" type="text" class="form-control" placeholder="<?php echo $entry_firstname; ?>" value="<?php echo $firstname; ?>" id="input-firstname" />
  		    </div>
  		  </div>
  		  <div class="form-group required clearfix">
  		    <label class="col-sm-2 control-label"><?php echo $entry_lastname; ?></label>
  		    <div class="col-sm-10">
  		      <input name="lastname" type="text" class="form-control" placeholder="<?php echo $entry_lastname; ?>" value="<?php echo $lastname; ?>" id="input-lastname" />
  		    </div>
  		  </div>
  		  <div class="form-group required clearfix">
  		    <label class="col-sm-2 control-label"><?php echo $entry_email; ?></label>
  		    <div class="col-sm-10">
  		      <input name="email" type="text" class="form-control" placeholder="<?php echo $entry_email; ?>" value="<?php echo $email; ?>" id="input-email" />
  		    </div>
  		  </div>
  		  <div class="form-group required clearfix">
  		    <label class="col-sm-2 control-label"><?php echo $entry_telephone; ?></label>
  		    <div class="col-sm-10">
  		      <input name="telephone" type="text" class="form-control" placeholder="<?php echo $entry_telephone; ?>" value="<?php echo $telephone; ?>" id="input-telephone" />
  		    </div>
  		  </div>
  		  <div class="form-group clearfix">
  		    <label class="col-sm-2 control-label"><?php echo $entry_fax; ?></label>
  		    <div class="col-sm-10">
  		      <input name="fax" type="text" class="form-control" placeholder="<?php echo $entry_fax; ?>" value="<?php echo $fax; ?>" id="input-fax" />
  		    </div>
  		  </div>
    		<?php foreach ($custom_fields as $custom_field) { ?>
        <?php if ($custom_field['location'] == 'account') { ?>
        <?php if ($custom_field['type'] == 'select') { ?>
        <div id="custom-field<?php echo $custom_field['custom_field_id']; ?>" class="form-group<?php echo ($custom_field['required'] ? ' required' : ''); ?> custom-field clearfix" data-sort="<?php echo $custom_field['sort_order']; ?>">
          <label class="col-sm-2 control-label" for="input-custom-field<?php echo $custom_field['custom_field_id']; ?>"><?php echo $custom_field['name']; ?></label>
          <div class="col-sm-10">
            <select name="custom_field[<?php echo $custom_field['location']; ?>][<?php echo $custom_field['custom_field_id']; ?>]" id="input-custom-field<?php echo $custom_field['custom_field_id']; ?>" class="form-control">
              <option value=""><?php echo $text_select; ?></option>
              <?php foreach ($custom_field['custom_field_value'] as $custom_field_value) { ?>
              <?php if (isset($register_custom_field[$custom_field['custom_field_id']]) && $custom_field_value['custom_field_value_id'] == $register_custom_field[$custom_field['custom_field_id']]) { ?>
              <option value="<?php echo $custom_field_value['custom_field_value_id']; ?>" selected="selected"><?php echo $custom_field_value['name']; ?></option>
              <?php } else { ?>
              <option value="<?php echo $custom_field_value['custom_field_value_id']; ?>"><?php echo $custom_field_value['name']; ?></option>
              <?php } ?>
              <?php } ?>
            </select>
          </div>
        </div>
        <?php } ?>
        <?php if ($custom_field['type'] == 'radio') { ?>
        <div id="custom-field<?php echo $custom_field['custom_field_id']; ?>" class="form-group<?php echo ($custom_field['required'] ? ' required' : ''); ?> custom-field clearfix" data-sort="<?php echo $custom_field['sort_order']; ?>">
          <label class="col-sm-2 control-label"><?php echo $custom_field['name']; ?></label>
          <div class="col-sm-10">
            <div id="input-custom-field<?php echo $custom_field['custom_field_id']; ?>">
              <?php foreach ($custom_field['custom_field_value'] as $custom_field_value) { ?>
              <div class="radio">
                <?php if (isset($register_custom_field[$custom_field['custom_field_id']]) && $custom_field_value['custom_field_value_id'] == $register_custom_field[$custom_field['custom_field_id']]) { ?>
                <label>
                  <input type="radio" name="custom_field[<?php echo $custom_field['location']; ?>][<?php echo $custom_field['custom_field_id']; ?>]" value="<?php echo $custom_field_value['custom_field_value_id']; ?>" checked="checked" />
                  <?php echo $custom_field_value['name']; ?></label>
                <?php } else { ?>
                <label>
                  <input type="radio" name="custom_field[<?php echo $custom_field['location']; ?>][<?php echo $custom_field['custom_field_id']; ?>]" value="<?php echo $custom_field_value['custom_field_value_id']; ?>" />
                  <?php echo $custom_field_value['name']; ?></label>
                <?php } ?>
              </div>
              <?php } ?>
            </div>
          </div>
        </div>
        <?php } ?>
        <?php if ($custom_field['type'] == 'checkbox') { ?>
        <div id="custom-field<?php echo $custom_field['custom_field_id']; ?>" class="form-group<?php echo ($custom_field['required'] ? ' required' : ''); ?> custom-field clearfix" data-sort="<?php echo $custom_field['sort_order']; ?>">
          <label class="col-sm-2 control-label"><?php echo $custom_field['name']; ?></label>
          <div class="col-sm-10">
            <div id="input-custom-field<?php echo $custom_field['custom_field_id']; ?>">
              <?php foreach ($custom_field['custom_field_value'] as $custom_field_value) { ?>
              <div class="checkbox">
                <?php if (isset($register_custom_field[$custom_field['custom_field_id']]) && in_array($custom_field_value['custom_field_value_id'], $register_custom_field[$custom_field['custom_field_id']])) { ?>
                <label>
                  <input type="checkbox" name="custom_field[<?php echo $custom_field['location']; ?>][<?php echo $custom_field['custom_field_id']; ?>][]" value="<?php echo $custom_field_value['custom_field_value_id']; ?>" checked="checked" />
                  <?php echo $custom_field_value['name']; ?></label>
                <?php } else { ?>
                <label>
                  <input type="checkbox" name="custom_field[<?php echo $custom_field['location']; ?>][<?php echo $custom_field['custom_field_id']; ?>][]" value="<?php echo $custom_field_value['custom_field_value_id']; ?>" />
                  <?php echo $custom_field_value['name']; ?></label>
                <?php } ?>
              </div>
              <?php } ?>
            </div>
          </div>
        </div>
        <?php } ?>
        <?php if ($custom_field['type'] == 'text') { ?>
        <div id="custom-field<?php echo $custom_field['custom_field_id']; ?>" class="form-group<?php echo ($custom_field['required'] ? ' required' : ''); ?> custom-field clearfix" data-sort="<?php echo $custom_field['sort_order']; ?>">
          <label class="col-sm-2 control-label" for="input-custom-field<?php echo $custom_field['custom_field_id']; ?>"><?php echo $custom_field['name']; ?></label>
          <div class="col-sm-10">
            <input type="text" name="custom_field[<?php echo $custom_field['location']; ?>][<?php echo $custom_field['custom_field_id']; ?>]" value="<?php echo (isset($register_custom_field[$custom_field['custom_field_id']]) ? $register_custom_field[$custom_field['custom_field_id']] : $custom_field['value']); ?>" placeholder="<?php echo $custom_field['name']; ?>" id="input-custom-field<?php echo $custom_field['custom_field_id']; ?>" class="form-control" />
          </div>
        </div>
        <?php } ?>
        <?php if ($custom_field['type'] == 'textarea') { ?>
        <div id="custom-field<?php echo $custom_field['custom_field_id']; ?>" class="form-group<?php echo ($custom_field['required'] ? ' required' : ''); ?> custom-field clearfix" data-sort="<?php echo $custom_field['sort_order']; ?>">
          <label class="col-sm-2 control-label" for="input-custom-field<?php echo $custom_field['custom_field_id']; ?>"><?php echo $custom_field['name']; ?></label>
          <div class="col-sm-10">
            <textarea name="custom_field[<?php echo $custom_field['location']; ?>][<?php echo $custom_field['custom_field_id']; ?>]" rows="5" placeholder="<?php echo $custom_field['name']; ?>" id="input-custom-field<?php echo $custom_field['custom_field_id']; ?>" class="form-control"><?php echo (isset($register_custom_field[$custom_field['custom_field_id']]) ? $register_custom_field[$custom_field['custom_field_id']] : $custom_field['value']); ?></textarea>
          </div>
        </div>
        <?php } ?>
        <?php if ($custom_field['type'] == 'file') { ?>
        <div id="custom-field<?php echo $custom_field['custom_field_id']; ?>" class="form-group<?php echo ($custom_field['required'] ? ' required' : ''); ?> custom-field clearfix" data-sort="<?php echo $custom_field['sort_order']; ?>">
          <label class="col-sm-2 control-label"><?php echo $custom_field['name']; ?></label>
          <div class="col-sm-10">
            <button type="button" id="button-custom-field<?php echo $custom_field['custom_field_id']; ?>" data-loading-text="<?php echo $text_loading; ?>" class="btn btn-default"><i class="fa fa-upload"></i> <?php echo $button_upload; ?></button>
            <input type="hidden" name="custom_field[<?php echo $custom_field['location']; ?>][<?php echo $custom_field['custom_field_id']; ?>]" value="<?php echo (isset($register_custom_field[$custom_field['custom_field_id']]) ? $register_custom_field[$custom_field['custom_field_id']] : ''); ?>" id="input-custom-field<?php echo $custom_field['custom_field_id']; ?>" />
          </div>
        </div>
        <?php } ?>
        <?php if ($custom_field['type'] == 'date') { ?>
        <div id="custom-field<?php echo $custom_field['custom_field_id']; ?>" class="form-group<?php echo ($custom_field['required'] ? ' required' : ''); ?> custom-field clearfix" data-sort="<?php echo $custom_field['sort_order']; ?>">
          <label class="col-sm-2 control-label" for="input-custom-field<?php echo $custom_field['custom_field_id']; ?>"><?php echo $custom_field['name']; ?></label>
          <div class="col-sm-10">
            <div class="input-group date">
              <input type="text" name="custom_field[<?php echo $custom_field['location']; ?>][<?php echo $custom_field['custom_field_id']; ?>]" value="<?php echo (isset($register_custom_field[$custom_field['custom_field_id']]) ? $register_custom_field[$custom_field['custom_field_id']] : $custom_field['value']); ?>" placeholder="<?php echo $custom_field['name']; ?>" data-date-format="YYYY-MM-DD" id="input-custom-field<?php echo $custom_field['custom_field_id']; ?>" class="form-control" />
              <span class="input-group-btn">
              <button type="button" class="btn btn-default"><i class="fa fa-calendar"></i></button>
              </span></div>
          </div>
        </div>
        <?php } ?>
        <?php if ($custom_field['type'] == 'time') { ?>
        <div id="custom-field<?php echo $custom_field['custom_field_id']; ?>" class="form-group<?php echo ($custom_field['required'] ? ' required' : ''); ?> custom-field clearfix" data-sort="<?php echo $custom_field['sort_order']; ?>">
          <label class="col-sm-2 control-label" for="input-custom-field<?php echo $custom_field['custom_field_id']; ?>"><?php echo $custom_field['name']; ?></label>
          <div class="col-sm-10">
            <div class="input-group time">
              <input type="text" name="custom_field[<?php echo $custom_field['location']; ?>][<?php echo $custom_field['custom_field_id']; ?>]" value="<?php echo (isset($register_custom_field[$custom_field['custom_field_id']]) ? $register_custom_field[$custom_field['custom_field_id']] : $custom_field['value']); ?>" placeholder="<?php echo $custom_field['name']; ?>" data-date-format="HH:mm" id="input-custom-field<?php echo $custom_field['custom_field_id']; ?>" class="form-control" />
              <span class="input-group-btn">
              <button type="button" class="btn btn-default"><i class="fa fa-calendar"></i></button>
              </span></div>
          </div>
        </div>
        <?php } ?>
        <?php if ($custom_field['type'] == 'datetime') { ?>
        <div id="custom-field<?php echo $custom_field['custom_field_id']; ?>" class="form-group<?php echo ($custom_field['required'] ? ' required' : ''); ?> custom-field clearfix" data-sort="<?php echo $custom_field['sort_order']; ?>">
          <label class="col-sm-2 control-label" for="input-custom-field<?php echo $custom_field['custom_field_id']; ?>"><?php echo $custom_field['name']; ?></label>
          <div class="col-sm-10">
            <div class="input-group datetime">
              <input type="text" name="custom_field[<?php echo $custom_field['location']; ?>][<?php echo $custom_field['custom_field_id']; ?>]" value="<?php echo (isset($register_custom_field[$custom_field['custom_field_id']]) ? $register_custom_field[$custom_field['custom_field_id']] : $custom_field['value']); ?>" placeholder="<?php echo $custom_field['name']; ?>" data-date-format="YYYY-MM-DD HH:mm" id="input-custom-field<?php echo $custom_field['custom_field_id']; ?>" class="form-control" />
              <span class="input-group-btn">
              <button type="button" class="btn btn-default"><i class="fa fa-calendar"></i></button>
              </span></div>
          </div>
        </div>
        <?php } ?>
        <?php } ?>
        <?php } ?>
  	  </fieldset>
    	<fieldset id="edit-address">
    	  <legend><?php echo $legend_address; ?></legend>
        <div class="form-group clearfix">
          <label class="col-sm-2 control-label"><?php echo $entry_company; ?></label>
          <div class="col-sm-10">
            <input name="company" type="text" class="form-control" placeholder="<?php echo $entry_company; ?>" value="<?php echo $company; ?>" id="input-company" />
          </div>
        </div>
    	  <div class="form-group required clearfix">
    	    <label class="col-sm-2 control-label"><?php echo $entry_address_1; ?></label>
    	    <div class="col-sm-10">
    	      <input name="address_1" type="text" class="form-control" placeholder="<?php echo $entry_address_1; ?>" value="<?php echo $address_1; ?>" id="input-address-1" />
    	    </div>
    	  </div>
    	  <div class="form-group clearfix">
    	    <label class="col-sm-2 control-label"><?php echo $entry_address_2; ?></label>
    	    <div class="col-sm-10">
    	      <input name="address_2" type="text" class="form-control" placeholder="<?php echo $entry_address_2; ?>" value="<?php echo $address_2; ?>" id="input-address-2" />
    	    </div>
    	  </div>
    	  <div class="form-group required clearfix">
    	    <label class="col-sm-2 control-label"><?php echo $entry_city; ?></label>
    	    <div class="col-sm-10">
    	      <input name="city" type="text" class="form-control" placeholder="<?php echo $entry_city; ?>" value="<?php echo $city; ?>" id="input-city" />
    	    </div>
    	  </div>
    	  <div class="form-group required clearfix">
    	    <label class="col-sm-2 control-label"><?php echo $entry_postcode; ?></label>
    	    <div class="col-sm-10">
    	      <input name="postcode" type="text" class="form-control" placeholder="<?php echo $entry_postcode; ?>" value="<?php echo $postcode; ?>" id="input-postcode" />
    	    </div>
    	  </div>
    	  <div class="form-group required clearfix">
    	    <label class="col-sm-2 control-label"><?php echo $entry_country; ?></label>
    	    <div class="col-sm-10">
    	      <span class="hide select-zone"><?php echo $zone_id; ?></span>
    	      <select class="form-control" name="country_id" id="input-country">
    	        <?php foreach ($countries as $country) { ?>
    	        <option value="<?php echo $country['country_id']; ?>" <?php if ($country['country_id'] == $country_id) { ?> selected="selected" <?php } ?>><?php echo $country['name']; ?></option>
    	        <?php } ?>
    	      </select>
    	    </div>
    	  </div>
    	  <div class="form-group required clearfix">
    	    <label class="col-sm-2 control-label"><?php echo $entry_zone; ?></label>
    	    <div class="col-sm-10">
    	      <select name="zone_id" class="form-control" id="input-zone"></select>
    	    </div>
    	  </div>
    	  <?php foreach ($custom_fields as $custom_field) { ?>
        <?php if ($custom_field['location'] == 'address') { ?>
        <?php if ($custom_field['type'] == 'select') { ?>
        <div id="custom-field<?php echo $custom_field['custom_field_id']; ?>" class="form-group<?php echo ($custom_field['required'] ? ' required' : ''); ?> custom-field clearfix" data-sort="<?php echo $custom_field['sort_order']; ?>">
          <label class="col-sm-2 control-label" for="input-custom-field<?php echo $custom_field['custom_field_id']; ?>"><?php echo $custom_field['name']; ?></label>
          <div class="col-sm-10">
            <select name="custom_field[<?php echo $custom_field['location']; ?>][<?php echo $custom_field['custom_field_id']; ?>]" id="input-custom-field<?php echo $custom_field['custom_field_id']; ?>" class="form-control">
              <option value=""><?php echo $text_select; ?></option>
              <?php foreach ($custom_field['custom_field_value'] as $custom_field_value) { ?>
              <?php if (isset($register_custom_field[$custom_field['custom_field_id']]) && $custom_field_value['custom_field_value_id'] == $register_custom_field[$custom_field['custom_field_id']]) { ?>
              <option value="<?php echo $custom_field_value['custom_field_value_id']; ?>" selected="selected"><?php echo $custom_field_value['name']; ?></option>
              <?php } else { ?>
              <option value="<?php echo $custom_field_value['custom_field_value_id']; ?>"><?php echo $custom_field_value['name']; ?></option>
              <?php } ?>
              <?php } ?>
            </select>
          </div>
        </div>
        <?php } ?>
        <?php if ($custom_field['type'] == 'radio') { ?>
        <div id="custom-field<?php echo $custom_field['custom_field_id']; ?>" class="form-group<?php echo ($custom_field['required'] ? ' required' : ''); ?> custom-field clearfix" data-sort="<?php echo $custom_field['sort_order']; ?>">
          <label class="col-sm-2 control-label"><?php echo $custom_field['name']; ?></label>
          <div class="col-sm-10">
            <div id="input-custom-field<?php echo $custom_field['custom_field_id']; ?>">
              <?php foreach ($custom_field['custom_field_value'] as $custom_field_value) { ?>
              <div class="radio">
                <?php if (isset($register_custom_field[$custom_field['custom_field_id']]) && $custom_field_value['custom_field_value_id'] == $register_custom_field[$custom_field['custom_field_id']]) { ?>
                <label>
                  <input type="radio" name="custom_field[<?php echo $custom_field['location']; ?>][<?php echo $custom_field['custom_field_id']; ?>]" value="<?php echo $custom_field_value['custom_field_value_id']; ?>" checked="checked" />
                  <?php echo $custom_field_value['name']; ?></label>
                <?php } else { ?>
                <label>
                  <input type="radio" name="custom_field[<?php echo $custom_field['location']; ?>][<?php echo $custom_field['custom_field_id']; ?>]" value="<?php echo $custom_field_value['custom_field_value_id']; ?>" />
                  <?php echo $custom_field_value['name']; ?></label>
                <?php } ?>
              </div>
              <?php } ?>
            </div>
          </div>
        </div>
        <?php } ?>
        <?php if ($custom_field['type'] == 'checkbox') { ?>
        <div id="custom-field<?php echo $custom_field['custom_field_id']; ?>" class="form-group<?php echo ($custom_field['required'] ? ' required' : ''); ?> custom-field clearfix" data-sort="<?php echo $custom_field['sort_order']; ?>">
          <label class="col-sm-2 control-label"><?php echo $custom_field['name']; ?></label>
          <div class="col-sm-10">
            <div id="input-custom-field<?php echo $custom_field['custom_field_id']; ?>">
              <?php foreach ($custom_field['custom_field_value'] as $custom_field_value) { ?>
              <div class="checkbox">
                <?php if (isset($register_custom_field[$custom_field['custom_field_id']]) && in_array($custom_field_value['custom_field_value_id'], $register_custom_field[$custom_field['custom_field_id']])) { ?>
                <label>
                  <input type="checkbox" name="custom_field[<?php echo $custom_field['location']; ?>][<?php echo $custom_field['custom_field_id']; ?>][]" value="<?php echo $custom_field_value['custom_field_value_id']; ?>" checked="checked" />
                  <?php echo $custom_field_value['name']; ?></label>
                <?php } else { ?>
                <label>
                  <input type="checkbox" name="custom_field[<?php echo $custom_field['location']; ?>][<?php echo $custom_field['custom_field_id']; ?>][]" value="<?php echo $custom_field_value['custom_field_value_id']; ?>" />
                  <?php echo $custom_field_value['name']; ?></label>
                <?php } ?>
              </div>
              <?php } ?>
            </div>
          </div>
        </div>
        <?php } ?>
        <?php if ($custom_field['type'] == 'text') { ?>
        <div id="custom-field<?php echo $custom_field['custom_field_id']; ?>" class="form-group<?php echo ($custom_field['required'] ? ' required' : ''); ?> custom-field clearfix" data-sort="<?php echo $custom_field['sort_order']; ?>">
          <label class="col-sm-2 control-label" for="input-custom-field<?php echo $custom_field['custom_field_id']; ?>"><?php echo $custom_field['name']; ?></label>
          <div class="col-sm-10">
            <input type="text" name="custom_field[<?php echo $custom_field['location']; ?>][<?php echo $custom_field['custom_field_id']; ?>]" value="<?php echo (isset($register_custom_field[$custom_field['custom_field_id']]) ? $register_custom_field[$custom_field['custom_field_id']] : $custom_field['value']); ?>" placeholder="<?php echo $custom_field['name']; ?>" id="input-custom-field<?php echo $custom_field['custom_field_id']; ?>" class="form-control" />
          </div>
        </div>
        <?php } ?>
        <?php if ($custom_field['type'] == 'textarea') { ?>
        <div id="custom-field<?php echo $custom_field['custom_field_id']; ?>" class="form-group<?php echo ($custom_field['required'] ? ' required' : ''); ?> custom-field clearfix" data-sort="<?php echo $custom_field['sort_order']; ?>">
          <label class="col-sm-2 control-label" for="input-custom-field<?php echo $custom_field['custom_field_id']; ?>"><?php echo $custom_field['name']; ?></label>
          <div class="col-sm-10">
            <textarea name="custom_field[<?php echo $custom_field['location']; ?>][<?php echo $custom_field['custom_field_id']; ?>]" rows="5" placeholder="<?php echo $custom_field['name']; ?>" id="input-custom-field<?php echo $custom_field['custom_field_id']; ?>" class="form-control"><?php echo (isset($register_custom_field[$custom_field['custom_field_id']]) ? $register_custom_field[$custom_field['custom_field_id']] : $custom_field['value']); ?></textarea>
          </div>
        </div>
        <?php } ?>
        <?php if ($custom_field['type'] == 'file') { ?>
        <div id="custom-field<?php echo $custom_field['custom_field_id']; ?>" class="form-group<?php echo ($custom_field['required'] ? ' required' : ''); ?> custom-field clearfix" data-sort="<?php echo $custom_field['sort_order']; ?>">
          <label class="col-sm-2 control-label"><?php echo $custom_field['name']; ?></label>
          <div class="col-sm-10">
            <button type="button" id="button-custom-field<?php echo $custom_field['custom_field_id']; ?>" data-loading-text="<?php echo $text_loading; ?>" class="btn btn-default"><i class="fa fa-upload"></i> <?php echo $button_upload; ?></button>
            <input type="hidden" name="custom_field[<?php echo $custom_field['location']; ?>][<?php echo $custom_field['custom_field_id']; ?>]" value="<?php echo (isset($register_custom_field[$custom_field['custom_field_id']]) ? $register_custom_field[$custom_field['custom_field_id']] : ''); ?>" id="input-custom-field<?php echo $custom_field['custom_field_id']; ?>" />
          </div>
        </div>
        <?php } ?>
        <?php if ($custom_field['type'] == 'date') { ?>
        <div id="custom-field<?php echo $custom_field['custom_field_id']; ?>" class="form-group<?php echo ($custom_field['required'] ? ' required' : ''); ?> custom-field clearfix" data-sort="<?php echo $custom_field['sort_order']; ?>">
          <label class="col-sm-2 control-label" for="input-custom-field<?php echo $custom_field['custom_field_id']; ?>"><?php echo $custom_field['name']; ?></label>
          <div class="col-sm-10">
            <div class="input-group date">
              <input type="text" name="custom_field[<?php echo $custom_field['location']; ?>][<?php echo $custom_field['custom_field_id']; ?>]" value="<?php echo (isset($register_custom_field[$custom_field['custom_field_id']]) ? $register_custom_field[$custom_field['custom_field_id']] : $custom_field['value']); ?>" placeholder="<?php echo $custom_field['name']; ?>" data-date-format="YYYY-MM-DD" id="input-custom-field<?php echo $custom_field['custom_field_id']; ?>" class="form-control" />
              <span class="input-group-btn">
              <button type="button" class="btn btn-default"><i class="fa fa-calendar"></i></button>
              </span></div>
          </div>
        </div>
        <?php } ?>
        <?php if ($custom_field['type'] == 'time') { ?>
        <div id="custom-field<?php echo $custom_field['custom_field_id']; ?>" class="form-group<?php echo ($custom_field['required'] ? ' required' : ''); ?> custom-field clearfix" data-sort="<?php echo $custom_field['sort_order']; ?>">
          <label class="col-sm-2 control-label" for="input-custom-field<?php echo $custom_field['custom_field_id']; ?>"><?php echo $custom_field['name']; ?></label>
          <div class="col-sm-10">
            <div class="input-group time">
              <input type="text" name="custom_field[<?php echo $custom_field['location']; ?>][<?php echo $custom_field['custom_field_id']; ?>]" value="<?php echo (isset($register_custom_field[$custom_field['custom_field_id']]) ? $register_custom_field[$custom_field['custom_field_id']] : $custom_field['value']); ?>" placeholder="<?php echo $custom_field['name']; ?>" data-date-format="HH:mm" id="input-custom-field<?php echo $custom_field['custom_field_id']; ?>" class="form-control" />
              <span class="input-group-btn">
              <button type="button" class="btn btn-default"><i class="fa fa-calendar"></i></button>
              </span></div>
          </div>
        </div>
        <?php } ?>
        <?php if ($custom_field['type'] == 'datetime') { ?>
        <div id="custom-field<?php echo $custom_field['custom_field_id']; ?>" class="form-group<?php echo ($custom_field['required'] ? ' required' : ''); ?> custom-field clearfix" data-sort="<?php echo $custom_field['sort_order']; ?>">
          <label class="col-sm-2 control-label" for="input-custom-field<?php echo $custom_field['custom_field_id']; ?>"><?php echo $custom_field['name']; ?></label>
          <div class="col-sm-10">
            <div class="input-group datetime">
              <input type="text" name="custom_field[<?php echo $custom_field['location']; ?>][<?php echo $custom_field['custom_field_id']; ?>]" value="<?php echo (isset($register_custom_field[$custom_field['custom_field_id']]) ? $register_custom_field[$custom_field['custom_field_id']] : $custom_field['value']); ?>" placeholder="<?php echo $custom_field['name']; ?>" data-date-format="YYYY-MM-DD HH:mm" id="input-custom-field<?php echo $custom_field['custom_field_id']; ?>" class="form-control" />
              <span class="input-group-btn">
              <button type="button" class="btn btn-default"><i class="fa fa-calendar"></i></button>
              </span></div>
          </div>
        </div>
        <?php } ?>
        <?php } ?>
        <?php } ?>
    	</fieldset>
    </div>
    <div class="buttons-update clearfix">
      <button class="btn btn-primary pull-left customer_editsave" data-id="<?php echo $customer['customer_id']; ?>"><i class="fa fa-save" data-class="fa fa-save"></i> <?php echo $button_save; ?></button>
      <button class="btn btn-default pull-right content_destroy" data-target="#ps-customer-list .customer-detail-edit"><i class="fa fa-reply"></i> <?php echo $button_cancel; ?></button>
    </div>
  </div>
</div>
<script type="text/javascript"><!--
// Sort the custom fields
$('#edit-account .form-group[data-sort]').detach().each(function() {
	if ($(this).attr('data-sort') >= 0 && $(this).attr('data-sort') <= $('#edit-account .form-group').length) {
		$('#edit-account .form-group').eq($(this).attr('data-sort')).before(this);
	}

	if ($(this).attr('data-sort') > $('#edit-account .form-group').length) {
		$('#edit-account .form-group:last').after(this);
	}

	if ($(this).attr('data-sort') == $('#edit-account .form-group').length) {
		$('#edit-account .form-group:last').after(this);
	}

	if ($(this).attr('data-sort') < -$('#edit-account .form-group').length) {
		$('#edit-account .form-group:first').before(this);
	}
});

$('#edit-address .form-group[data-sort]').detach().each(function() {
	if ($(this).attr('data-sort') >= 0 && $(this).attr('data-sort') <= $('#edit-address .form-group').length) {
		$('#edit-address .form-group').eq($(this).attr('data-sort')).before(this);
	}

	if ($(this).attr('data-sort') > $('#edit-address .form-group').length) {
		$('#edit-address .form-group:last').after(this);
	}

	if ($(this).attr('data-sort') == $('#edit-address .form-group').length) {
		$('#edit-address .form-group:last').after(this);
	}

	if ($(this).attr('data-sort') < -$('#edit-address .form-group').length) {
		$('#edit-address .form-group:first').before(this);
	}
});
//--></script>
<script type="text/javascript"><!--
$('.edit-customer-form select[name=\'country_id\']').trigger('change');
//--></script>
<?php } ?>
