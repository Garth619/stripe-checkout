/**
 *
 */

var gf_money = fx.noConflict();
gf_money.base = gf_currency.base;
gf_money.rates = gf_currency.rates;

jQuery( document ).ready( function ( $ ) {
	jQuery( document ).on( 'gform_post_render', gformAddCurrencyFieldAction );
} );

function gformAddCurrencyFieldAction( event, form_id, current_page ) {
	var currency_field = jQuery( '#input_' + form_id + '_' + gf_currency.currency_field_id );
	if ( currency_field.length > 0 ) {

		var currency_field_page_id = currency_field.parents( '.gform_page' ).attr( 'id' );
		if ( 'undefined' !== typeof( currency_field_page_id ) &&  0 < currency_field_page_id.length ) {
			currency_field_page_id = currency_field_page_id.split( '_' );
			var currency_field_page = currency_field_page_id[3];
		}
		else {
			currency_field_page = current_page;
		}

		if ( currency_field_page == current_page ) {


			currency_field.on( 'change', { form_id: form_id }, gformStartUpdateCurrency );

			gform.addFilter( 'gform_product_total', 'gform_product_total_currency' );

			if ( false == gformCurrencyIsPostback( form_id ) ) {
				gf_global.gf_currency_config['code'] = gf_currency.default_from;
				gf_global.default_currency_config = gf_global.gf_currency_config;

				var currency_code = currency_field.val();

				if ( ( 0 < currency_code.length ) && ( gf_global.default_currency_config['code'] !== currency_code ) ) {
					currency_field.trigger( 'change' );
				}
			}
		}
	}
}

function gformStartUpdateCurrency( event ) {
	jQuery( this ).prop( 'disabled', true );
	gformAllProductFields( 'disable' );
	gformSubmitButtons( 'disable' );
	jQuery( this ).after( '<img id="gform_ajax_spinner_currency_field"  class="gform_ajax_spinner" src="' + gf_currency.spinner_url + '" alt="" />' );
	if ( gformIsMultiPage( event.data.form_id ) ) {
		jQuery( '.ginput_total_' + event.data.form_id ).after( '<img id="gform_ajax_spinner_total_field"  class="gform_ajax_spinner" src="' + gf_currency.spinner_url + '" alt="" />' );
	}
	var currency_code = jQuery( this ).val();
	var currency = gformGetCurrency( currency_code, event.data.form_id, this );

}

function gformAllProductFields( action ) {
	jQuery( '.gfield_price' ).each( function () {
		if ( 'disable' == action ) {
			jQuery( this ).find( "input[type=\"text\"], input[type=\"number\"], select" ).prop( 'disabled', true );
			jQuery( this ).find( "input[type=\"radio\"], input[type=\"checkbox\"]" ).prop( 'disabled', true );
		}
		else if ( 'enable' == action ) {
			jQuery( this ).find( "input[type=\"text\"], input[type=\"number\"], select" ).prop( 'disabled', false );
			jQuery( this ).find( "input[type=\"radio\"], input[type=\"checkbox\"]" ).prop( 'disabled', false );
		}
	} );
}

function gformSubmitButtons( action ) {
	if ( 'disable' == action ) {
		jQuery( '.gform_next_button' ).prop( 'disabled', true );
		jQuery( '.gform_previous_button' ).prop( 'disabled', true );
		jQuery( '.gform_button' ).prop( 'disabled', true );
	}
	else if ( 'enable' == action ) {
		jQuery( '.gform_next_button' ).prop( 'disabled', false );
		jQuery( '.gform_previous_button' ).prop( 'disabled', false );
		jQuery( '.gform_button' ).prop( 'disabled', false );
	}
}

function gformGetCurrency( currency_code, form_id, currency_field ) {
	var currency = '';
	var post_data = { action: 'gfp_more_stripe_get_currency',
		gfp_more_stripe_get_currency: gf_currency.nonce,
		currency: currency_code };

	jQuery.post( gf_currency.ajaxurl, post_data, function ( response ) {
		if ( true === response.success ) {
			currency = response.data;
			if ( currency && 0 !== currency.length ) {
				gformFinishUpdateCurrency( currency, form_id );
				jQuery( currency_field ).prop( 'disabled', false );
				gformAllProductFields( 'enable' );
				gformSubmitButtons( 'enable' );
			}
		}
		jQuery( '#gform_ajax_spinner_currency_field' ).remove();
		jQuery( '#gform_ajax_spinner_total_field' ).remove();
	} );
}

function gformFinishUpdateCurrency( currency, form_id ) {
	if ( gf_global.gf_currency_config['code'] !== currency['code'] ) {
		gf_global.prev_currency_config = gf_global.gf_currency_config;
		gf_currency.currency_config = currency;

		for ( var i = 0; i < _gformPriceFields[form_id].length; i++ ) {
			gformChangePriceDisplay( form_id, _gformPriceFields[form_id][i] );
		}

		gf_global.gf_currency_config = currency;

		for ( form_id in _gformPriceFields ) {

			if ( !_gformPriceFields.hasOwnProperty( form_id ) )
				continue;

			gformCalculateTotalPrice( form_id );
		}

	}
}

function gformChangePriceDisplay( form_id, product_field_id ) {
	var suffix = "_" + form_id + "_" + product_field_id;
	var method = 'text';

	var price_element = jQuery( '.gfield_product' + suffix ).find( 'input.ginput_amount' );
	if ( 0 < price_element.length ) {
		method = 'val';
		gformUpdatePriceDisplay( method, price_element );
	} else {

		price_element = jQuery( '.gfield_product' + suffix ).find( 'span.ginput_product_price' );

		if ( 0 < price_element.length ) {
			gformUpdatePriceDisplay( method, price_element );
		}
		else {
			price_element = jQuery( '.gfield_option' + suffix + ', .gfield_shipping_' + form_id ).find( 'span.ginput_price' );

			if ( 0 < price_element.length ) {
				price_element.each( function () {
					var price = jQuery( this ).text();
					price = price.trim();
					var first_char = price.charAt( 0 );
					if ( '+' == first_char || '-' == first_char ) {
						var new_price = first_char;
						price = price.replace( first_char, '' );
						new_price = new_price + gformCurrencyConvertPrice( price );
					}
					else {
						var new_price = gformCurrencyConvertPrice( price );
					}
					jQuery( this ).text( new_price );
				} );
			}
			else {

				price_element = jQuery( '.gfield_option' + suffix + ', .gfield_shipping_' + form_id ).find( 'select' );

				if ( 0 < price_element.length ) {
					var selected_price = gformCurrencyGetPrice( price_element.val() );
					selected_price = gf_money( selected_price ).from( gf_global.default_currency_config['code'] ).to( gf_currency.currency_config['code'] );

					price_element.children( 'option' ).each( function () {
						var choice_element = jQuery( this );
						var label = gformCurrencyGetOptionLabel( choice_element, choice_element.val(), selected_price, form_id, product_field_id );
						choice_element.html( label );
					} );
				}
				else {

					price_element = jQuery( '.gfield_shipping_' + form_id ).find( 'span.ginput_shipping_price' );
					if ( 0 < price_element.length ) {
						gformUpdatePriceDisplay( method, price_element );
					}
				}
			}
		}
	}
}

function gformUpdatePriceDisplay( method, element ) {
	if ( 'val' === method ) {
		var new_price = gformCurrencyConvertPrice( element.val() );
		element.val( new_price );
	} else {
		var new_price = gformCurrencyConvertPrice( element.text() );
		element.text( new_price );
	}
}

function gformCurrencyGetPrice( text ) {
	var val = text.split( '|' );
	var currency = new Currency( gf_global.default_currency_config );

	if ( val.length > 1 && currency.toNumber( val[1] ) !== false ) {
		return currency.toNumber( val[1] );
	}

	return 0;
}

function gformCurrencyConvertPrice( price ) {
	var number = gformCurrencyToNumber( price );
	number = gf_money( number ).from( gf_global.gf_currency_config['code'] ).to( gf_currency.currency_config['code'] );

	gf_global.gf_currency_config = gf_currency.currency_config;

	price = gformFormatMoney( number );

	gf_global.gf_currency_config = gf_global.prev_currency_config;

	return price;
}

function gformCurrencyToNumber( text ) {
	if ( gformIsNumber( text ) ) {
		return parseFloat( text );
	}

	return gformCurrencyCleanNumber( text, gf_global.gf_currency_config['symbol_right'], gf_global.gf_currency_config['symbol_left'], gf_global.gf_currency_config['decimal_separator'] );
}

function gformCurrencyCleanNumber( text, symbol_right, symbol_left, decimal_separator ) {
	text = text + " ";

	text = text.replace( /&.*?;/, "", text );

	text = text.replace( symbol_right, "" );
	text = text.replace( symbol_left, "" );


	var clean_number = "";
	var is_negative = false;
	for ( var i = 0; i < text.length; i++ ) {
		var digit = text.substr( i, 1 );
		if ( parseInt( digit ) >= 0 && parseInt( digit ) <= 9 ) {
			clean_number += digit;
		}
		else if ( ( digit == decimal_separator ) && ( gformIsNumber( text.substr( i + 1, 1 ) ) || gformIsNumber( text.substr( i - 1, 1 ) ) ) ) {
			clean_number += digit;
		}
		else if ( digit == '-' ) {
			is_negative = true;
		}
	}

	var float_number = "";

	for ( var i = 0; i < clean_number.length; i++ ) {
		var char = clean_number.substr( i, 1 );
		if ( char >= '0' && char <= '9' ) {
			float_number += char;
		}
		else if ( char == decimal_separator ) {
			float_number += ".";
		}
	}

	if ( is_negative ) {
		float_number = "-" + float_number;
	}

	return gformIsNumber( float_number ) ? parseFloat( float_number ) : false;
}

function gformIsMultiPage( form_id ) {
	var is_multi_page = false;
	var target_page = jQuery( '#gform_target_page_number_' + form_id ).val();
	var source_page = jQuery( '#gform_source_page_number_' + form_id ).val();

	if ( 0 !== target_page || 1 !== source_page ) {
		is_multi_page = true;
	}

	return is_multi_page;
}

function gformCurrencyIsPostback( form_id ) {
	var is_postback = false;

	is_postback = ( 0 < jQuery( '#gform_ajax_frame_' + form_id ).contents().find( '*' ).html().indexOf( 'GF_AJAX_POSTBACK' ) ) ? true : ( 'undefined' !== typeof gf_currency.is_postback );

	return is_postback;
}

function gformCurrencyGetOptionLabel( element, selected_value, current_price, form_id, field_id ) {
	element = jQuery( element );
	var price = gformCurrencyGetPrice( selected_value );

	price = gf_money( price ).from( gf_global.default_currency_config['code'] ).to( gf_currency.currency_config['code'] );
	var current_diff = element.attr( 'price' );
	var original_label = element.html().replace( /<span(.*)<\/span>/i, "" ).replace( current_diff, "" );

	gf_global.gf_currency_config = gf_currency.currency_config;
	var diff = gformGetPriceDifference( current_price, price );
	diff = gformToNumber( diff ) == 0 ? "" : " " + diff;

	var price_label = element[0].tagName.toLowerCase() == "option" ? " " + diff : "<span class='ginput_price'>" + diff + "</span>";
	var label = original_label + price_label;

	if ( window["gform_format_option_label"] ) {
		label = gform_format_option_label( label, original_label, price_label, current_price, price, form_id, field_id );
	}

	gf_global.gf_currency_config = gf_global.prev_currency_config;

	return label;
}

function gform_product_total_currency( price, formId ) {
	if ( '0' !== formId && 'undefined' !== typeof gf_currency.currency_config ) {
		gf_global.gf_currency_config = gf_global.default_currency_config;
		var new_price = 0;

		_anyProductSelected = false;
		for ( var i = 0; i < _gformPriceFields[formId].length; i++ ) {
			var form_id = formId;
			var productFieldId = _gformPriceFields[formId][i];

			gformCurrencyUpdateLabel( form_id, productFieldId );

			var suffix = "_" + form_id + "_" + productFieldId;
			var productField = jQuery( '.gfield_product' + suffix + ' .ginput_amount' );
			if ( productField.length > 0 ) {
				var field_val = productField.val();

				if ( gformIsHidden( productField ) ) {
					field_val = 0;
				}
				var c = new Currency( gf_currency.currency_config );
				field_val = c.toNumber( field_val );
				new_price += ( field_val === false ) ? 0 : field_val;
			}
			else {
				new_price += gf_money( gformGetBasePrice( form_id, productFieldId ) ).from( gf_global.default_currency_config['code'] ).to( gf_currency.currency_config['code'] );
			}

			jQuery( ".gfield_option" + suffix ).find( "input:checked, select" ).each( function () {
				if ( !gformIsHidden( jQuery( this ) ) )
					new_price += gf_money( gformGetPrice( jQuery( this ).val() ) ).from( gf_global.default_currency_config['code'] ).to( gf_currency.currency_config['code'] );
			} );

			var quantity = gformGetProductQuantity( form_id, productFieldId );

			if ( quantity > 0 ) {
				_anyProductSelected = true;
			}

			new_price = new_price * quantity;
			new_price = Math.round( new_price * 100 ) / 100;
		}

		if ( _anyProductSelected ) {
			var shipping = gformGetShippingPrice( formId );
			new_price += gf_money( shipping ).from( gf_global.default_currency_config['code'] ).to( gf_currency.currency_config['code'] );
		}

		var final_price = 0;
		if ( 0 === gf_currency.currency_config['decimals'] ) {
			final_price = Math.round( new_price );
		}
		else {
			new_price = new_price.toString();
			for ( var j = 0; j < new_price.length; j++ ) {
				var char = new_price.substr( j, 1 );
				if ( gf_global.gf_currency_config['thousand_separator'] == char ) {
					final_price += gf_currency.currency_config['thousand_separator'];
				}
				else if ( gf_global.gf_currency_config['decimal_separator'] == char ) {
					final_price += gf_currency.currency_config['decimal_separator'];
				}
				else {
					final_price += char;
				}
			}
		}
		price = final_price;

		gf_global.gf_currency_config = gf_currency.currency_config;
	}

	return price;
}

function gformCurrencyUpdateLabel( form_id, productFieldId ) {
	var suffix = "_" + form_id + "_" + productFieldId;

	if ( gformCurrencyIsPostback( form_id ) ) {
		var single_product = jQuery( '#ginput_base_price' + suffix );
		if ( 0 < single_product.length ) {
			var price = single_product.val();
			var c = new Currency( gf_global.gf_currency_config );
			price = c.toNumber( price );
			price = ( price === false ) ? 0 : price;
			price = gf_money( price ).from( gf_global.default_currency_config['code'] ).to( gf_currency.currency_config['code'] );
			gf_global.gf_currency_config = gf_currency.currency_config;
			jQuery( '.gfield_product' + suffix ).find( 'span.ginput_product_price' ).text( gformFormatMoney( price ) );
			gf_global.gf_currency_config = gf_global.default_currency_config;
		}
	}

	jQuery( '.gfield_option' + suffix + ', .gfield_shipping_' + form_id ).find( 'select' ).each( function () {

		var dropdown_field = jQuery( this );
		var selected_price = gformGetPrice( dropdown_field.val() );
		selected_price = gf_money( selected_price ).from( gf_global.default_currency_config['code'] ).to( gf_currency.currency_config['code'] );
		var field_id = dropdown_field.attr( 'id' ).split( '_' )[2];
		dropdown_field.children( 'option' ).each( function () {
			var choice_element = jQuery( this );
			var label = gform_product_total_currency_GetOptionLabel( choice_element, choice_element.val(), selected_price, form_id, field_id );
			choice_element.html( label );
		} );
	} );


	jQuery( '.gfield_option' + suffix ).find( '.gfield_checkbox' ).find( 'input' ).each( function () {
		var checkbox_item = jQuery( this );
		var id = checkbox_item.attr( 'id' );
		var field_id = id.split( '_' )[2];
		var label_id = id.replace( 'choice_', '#label_' );
		var label_element = jQuery( label_id );
		var label = gform_product_total_currency_GetOptionLabel( label_element, checkbox_item.val(), 0, form_id, field_id );
		label_element.html( label );
	} );


	jQuery( '.gfield_option' + suffix + ', .gfield_shipping_' + form_id ).find( '.gfield_radio' ).each( function () {
		var selected_price = 0;
		var radio_field = jQuery( this );
		var id = radio_field.attr( 'id' );
		var fieldId = id.split( "_" )[2];
		var selected_value = radio_field.find( 'input:checked' ).val();

		if ( selected_value ) {
			selected_price = gformGetPrice( selected_value );
			selected_price = gf_money( selected_price ).from( gf_global.default_currency_config['code'] ).to( gf_currency.currency_config['code'] );
		}

		jQuery( this ).find( 'input' ).each( function () {
			var radio_item = jQuery( this );
			var label_id = radio_item.attr( 'id' ).replace( 'choice_', '#label_' );
			var label_element = jQuery( label_id );
			var label = gform_product_total_currency_GetOptionLabel( label_element, radio_item.val(), selected_price, form_id, fieldId );
			label_element.html( label );
		} );
	} );
}

function gform_product_total_currency_GetOptionLabel( element, selected_value, current_price, form_id, field_id ) {
	element = jQuery( element );
	var price = gformGetPrice( selected_value );
	price = gf_money( price ).from( gf_global.default_currency_config['code'] ).to( gf_currency.currency_config['code'] );
	var current_diff = element.attr( 'price' );
	var original_label = element.html().replace( /<span(.*)<\/span>/i, "" ).replace( current_diff, "" );

	gf_global.gf_currency_config = gf_currency.currency_config;
	var diff = gformGetPriceDifference( current_price, price );
	diff = gformToNumber( diff ) == 0 ? '' : ' ' + diff;
	element.attr( 'price', diff );

	gf_global.gf_currency_config = gf_global.default_currency_config;

	var price_label = element[0].tagName.toLowerCase() == 'option' ? ' ' + diff : "<span class='ginput_price'>" + diff + "</span>";
	var label = original_label + price_label;

	if ( window['gform_format_option_label'] ) {
		label = gform_format_option_label( label, original_label, price_label, current_price, price, form_id, field_id );
	}

	return label;
}