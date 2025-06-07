function OCTgeturlparam(param) {
	var urlParams = new URLSearchParams(window.location.search);
	return urlParams.get(param) !== null ? param+'='+urlParams.get(param) : '';
}
var OCT4_GET_LANG = '';
var fbcapidyad_ur1201 = 'index.php?route=module/fbcapidyad/';
var fbcapidyad_url230 = 'index.php?route=extension/module/fbcapidyad/';
var fbcapidyad_url401 = 'index.php?route=extension/fbcapidyad/module/fbcapidyad|';
var fbcapidyad_url402 = 'index.php?route=extension/fbcapidyad/module/fbcapidyad.';

$(document).delegate('#button-cart, [data-quick-buy]', 'click', function() {
	postdata = $('#product input[type=\'text\'], #product input[type=\'hidden\'], #product input[type=\'radio\']:checked, #product input[type=\'checkbox\']:checked, #product select, #product textarea')

	if($('#product .button-group-page').length) {
		/*for J3*/
		postdata = $(
		'#product .button-group-page input[type=\'text\'], #product .button-group-page input[type=\'hidden\'], #product .button-group-page input[type=\'radio\']:checked, #product .button-group-page input[type=\'checkbox\']:checked, #product .button-group-page select, #product .button-group-page textarea, ' +
		'#product .product-options input[type=\'text\'], #product .product-options input[type=\'hidden\'], #product .product-options input[type=\'radio\']:checked, #product .product-options input[type=\'checkbox\']:checked, #product .product-options select, #product .product-options textarea, ' +
		'#product select[name="recurring_id"]'
		);
	}
	$.ajax({
		url: fbcapidyad_url230 + 'addtocart' + OCT4_GET_LANG,
		async: true,
		type: 'post',
		dataType: 'json',
		data: postdata,
		success: function(json) {
			if (json['script']) {
				$('body').append(json['script']);
			}
		}
	});
});
$(document).delegate("[onclick*='cart.add'], [onclick*='addToCart'], button[formaction*='checkout/cart.add']", 'click', function() {
	if($(this).closest('form').find("[name*='product_id']").length) {
		var product_id = $(this).closest('form').find("[name*='product_id']").val();	
		var quantity = $(this).closest('form').find("input[name*='quantity']").val();
	} else if($(this).attr('onclick')) {
		var product_id = $(this).attr('onclick').match(/[0-9]+/).toString();
		var quantity = $(this).closest('.product-thumb').find("input[name*='quantity']").val();
	}
	quantity = quantity || 1;

	$.ajax({
		url: fbcapidyad_url230 + 'addtocart' + OCT4_GET_LANG,
		async: true,
		type: 'post',
		dataType: 'json',
		data: {product_id:product_id,quantity:quantity},
		success: function(json) {
			if (json['script']) {
				$('body').append(json['script']);
			}
		}
	});
});
$(document).delegate("[onclick*='wishlist.add'],[onclick*='addToWishList'], button[formaction*='account/wishlist']", 'click', function() {
	if($(this).closest('form').find("[name*='product_id']").length) {
		var product_id = $(this).closest('form').find("[name*='product_id']").val();	
		var quantity = $(this).closest('form').find("input[name*='quantity']").val();
	} else if($(this).attr('onclick')) {
		var product_id = $(this).attr('onclick').match(/[0-9]+/).toString();
		var quantity = $(this).closest('.product-thumb').find("input[name*='quantity']").val();
	}
	quantity = quantity || 1;

	$.ajax({
		url: fbcapidyad_url230 + 'addtowishlist' + OCT4_GET_LANG,
		async: true,
		type: 'post',
		dataType: 'json',
		data: {product_id:product_id,quantity:quantity},
		success: function(json) {
			if (json['script']) {
				$('body').append(json['script']);
			}
		}
	});
});