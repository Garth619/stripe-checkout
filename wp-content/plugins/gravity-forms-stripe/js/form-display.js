/**
 *
 */

jQuery( document ).ready( function ( $ ) {

	jQuery( document ).on( 'gform_post_render', gformAddStripeAction );

} );

function gformAddStripeAction( event, form_id, current_page ) {

	if ( form_id !== parseInt( gfp_stripe_js_vars.form_id ) ) {
		return;
	}

	jQuery( '#gform_' + gfp_stripe_js_vars.form_id ).submit( function () {

		var last_page = jQuery( '#gform_target_page_number_' + gfp_stripe_js_vars.form_id ).val();

		if ( last_page === '0' ) {

			if ( ( jQuery( '#gform_payment_method_creditcard' ).length > 0 ) && !( jQuery( '#gform_payment_method_creditcard' ).is( ':checked' ) ) ) {
				return true;
			}

			gfp_stripe_clear_credit_card_error();

			var form$ = jQuery( '#gform_' + gfp_stripe_js_vars.form_id );
			var card_info = '';
			var card_valid = '';
			var no_credit_card = matched_condition = false;

			if ( 1 < parseInt( gfp_stripe_js_vars.num_of_rules ) ) {

				var stripe_condition = [];

				for ( var i = 0; i < gfp_stripe_js_vars.num_of_rules; i++ ) {

					condition = {
						operator: gfp_stripe_js_vars.rule_field_info[i].operator,
						fieldId: gfp_stripe_js_vars.conditional_field_id,
						value: gfp_stripe_js_vars.rule_field_info[i].value,
						street: gfp_stripe_js_vars.rule_field_info[i].street_input_id,
						city: gfp_stripe_js_vars.rule_field_info[i].city_input_id,
						state: gfp_stripe_js_vars.rule_field_info[i].state_input_id,
						zip: gfp_stripe_js_vars.rule_field_info[i].zip_input_id,
						country: gfp_stripe_js_vars.rule_field_info[i].country_input_id
					};

					if ( 'undefined' !== typeof( gfp_stripe_js_vars.rule_field_info[i].no_credit_card ) ) {
						condition.no_credit_card = gfp_stripe_js_vars.rule_field_info[i].no_credit_card;
					}

					stripe_condition.push( condition );

				}

			}
			else if ( '1' === gfp_stripe_js_vars.rule_has_condition ) {

				var stripe_condition = [];

				var condition = {
					operator: gfp_stripe_js_vars.rule_field_info.operator,
					fieldId: gfp_stripe_js_vars.conditional_field_id,
					value: gfp_stripe_js_vars.rule_field_info.value,
					street: gfp_stripe_js_vars.rule_field_info.street_input_id,
					city: gfp_stripe_js_vars.rule_field_info.city_input_id,
					state: gfp_stripe_js_vars.rule_field_info.state_input_id,
					zip: gfp_stripe_js_vars.rule_field_info.zip_input_id,
					country: gfp_stripe_js_vars.rule_field_info.country_input_id
				};

				if ( 'undefined' !== typeof( gfp_stripe_js_vars.rule_field_info.no_credit_card ) ) {
					condition.no_credit_card = gfp_stripe_js_vars.rule_field_info.no_credit_card;
				}

				stripe_condition.push( condition );

			}
			else {

				var stripe_rule = {
					street: gfp_stripe_js_vars.rule_field_info.street_input_id,
					city: gfp_stripe_js_vars.rule_field_info.city_input_id,
					state: gfp_stripe_js_vars.rule_field_info.state_input_id,
					zip: gfp_stripe_js_vars.rule_field_info.zip_input_id,
					country: gfp_stripe_js_vars.rule_field_info.country_input_id
				};

				if ( 'undefined' == typeof( gfp_stripe_js_vars.rule_field_info.no_credit_card ) ) {

					card_info = gfp_stripe_set_stripe_info( stripe_rule );
					card_valid = gfp_stripe_validate_card( card_info );

				}
				else {

					no_credit_card = true;

				}

			}

			if ( 'undefined' !== typeof( stripe_condition ) && 0 < stripe_condition.length ) {

				for ( var i = 0; i < stripe_condition.length; i++ ) {

						var rule = stripe_condition[i];

					if ( gf_is_match( form_id, rule ) ) {

							matched_condition = true;

						if ( 'undefined' == typeof( rule.no_credit_card ) ) {

								card_info = gfp_stripe_set_stripe_info( rule );
							card_valid = gfp_stripe_validate_card( card_info );

						}
						else {

							no_credit_card = true;

						}

						break;

					}

				}

			}
			else if ( 'undefined' === typeof( stripe_condition ) ) {

					matched_condition = true;

			}

			if ( true === matched_condition ) {

				if ( no_credit_card ) {

					form$.append( "<input type='hidden' name='stripe_no_credit_card' value='true' />" );

					return true;

				}
				else if ( card_info ) {

					if ( !card_valid.card_number || !card_valid.exp_date || !card_valid.cvc || !card_valid.cardholder_name ) {

						var error_message = '';

						if ( !card_valid.card_number ) {
							error_message += gfp_stripe_js_vars.error_messages.card_number;
						}

						if ( !card_valid.exp_date ) {
							error_message += gfp_stripe_js_vars.error_messages.expiration;
						}

						if ( !card_valid.cvc ) {
							error_message += gfp_stripe_js_vars.error_messages.security_code;
						}

						if ( !card_valid.cardholder_name ) {
							error_message += gfp_stripe_js_vars.error_messages.cardholder_name;
						}

						gfp_stripe_set_credit_card_error( error_message );

					} else {

						var token = Stripe.card.createToken( {
															number: card_info.card_number,
															exp_month: card_info.exp_month,
															exp_year: card_info.exp_year,
															cvc: card_info.cvc,
															name: card_info.cardholder_name,
															address_line1: ( !( typeof card_info.address_line1 === 'undefined' ) ) ? card_info.address_line1 : '',
															address_city: ( !( typeof card_info.address_city === 'undefined' ) ) ? card_info.address_city : '',
															address_zip: ( !( typeof card_info.address_zip === 'undefined' ) ) ? card_info.address_zip : '',
															address_state: ( !( typeof card_info.address_state === 'undefined' ) ) ? card_info.address_state : '',
															address_country: ( !( typeof card_info.address_country === 'undefined' ) ) ? card_info.address_country : ''
														}, stripeResponseHandler );

					}

				}
				else {

					gfp_stripe_set_credit_card_error( gfp_stripe_js_vars.error_messages.no_card_info );

				}

				return false;

			}
			else {

				return true;

			}

		}

	} );
}

function stripeResponseHandler( status, response ) {
	var form$ = jQuery( '#gform_' + gfp_stripe_js_vars.form_id );
	var submit_form = false;
	var error_message = '';

	if ( response.error ) {
		error_message = response.error.message;
	} else {
		var funding = response['card']['funding'];
		var card_allowed = jQuery.inArray( funding, gfp_stripe_js_vars.allowed_funding_types );
		if ( -1 == card_allowed ) {
			error_message = funding.charAt( 0 ).toUpperCase() + funding.substring( 1 ) + gfp_stripe_js_vars.error_messages.funding;
		}
		else {
			submit_form = true;
		}
	}

	if ( true === submit_form ) {
		var token = response['id'];
		var last4 = response['card']['last4'];
		var card_brand = response['card']['brand'];
		form$.append( "<input type='hidden' name='stripeToken' value='" + token + "' />" );
		form$.append( "<input type='hidden' name='input_" + gfp_stripe_js_vars.creditcard_field_id + ".1' value='" + last4 + "' />" );
		form$.append( "<input type='hidden' name='input_" + gfp_stripe_js_vars.creditcard_field_id + ".4' value='" + card_brand + "' />" );
		form$.get( 0 ).submit();
	}
	else {
		gfp_stripe_set_credit_card_error( error_message );
	}
}

function gfp_stripe_set_stripe_info( stripe_rule ) {

	form_id = gfp_stripe_js_vars.form_id;

	Stripe.setPublishableKey( gfp_stripe_js_vars.publishable_key );
	var card_number = jQuery( '#gform_' + form_id + ' #input_' + form_id + '_' + gfp_stripe_js_vars.creditcard_field_id + '_1' ).val();
	var exp_month = jQuery( '#gform_' + form_id + ' .ginput_card_expiration_month' ).val();
	var exp_year = jQuery( '#gform_' + form_id + ' .ginput_card_expiration_year' ).val();
	var cvc = jQuery( '#gform_' + form_id + ' .ginput_card_security_code' ).val();
	var cardholder_name = jQuery( '#gform_' + form_id + ' #input_' + form_id + '_' + gfp_stripe_js_vars.creditcard_field_id + '_5' ).val();
	if ( !( 'undefined' === typeof stripe_rule ) ) {
		var address_line1 = ( !( 'undefined' === typeof stripe_rule['street'] ) ) ? jQuery( '#gform_' + form_id + ' #input_' + stripe_rule['street'] ).val() : '';
		var address_city = ( !( 'undefined' === typeof stripe_rule['city'] ) ) ? jQuery( '#gform_' + form_id + ' #input_' + stripe_rule['city'] ).val() : '';
		var address_state = ( !( 'undefined' === typeof stripe_rule['state'] ) ) ? jQuery( '#gform_' + form_id + ' #input_' + stripe_rule['state'] ).val() : '';
		var address_zip = ( !( 'undefined' === typeof stripe_rule['zip'] ) ) ? jQuery( '#gform_' + form_id + ' #input_' + stripe_rule['zip'] ).val() : '';
		var address_country = ( !( 'undefined' === typeof stripe_rule['country'] ) ) ? jQuery( '#gform_' + form_id + ' #input_' + stripe_rule['country'] ).val() : '';
	}
	return {
		card_number: card_number,
		exp_month: exp_month,
		exp_year: exp_year,
		cvc: cvc,
		cardholder_name: cardholder_name,
		address_line1: address_line1,
		address_city: address_city,
		address_state: address_state,
		address_zip: address_zip,
		address_country: address_country
	};
}

function gfp_stripe_validate_card( card_info ) {
	var card_number_valid = Stripe.validateCardNumber( card_info.card_number );
	var exp_date_valid = Stripe.validateExpiry( card_info.exp_month, card_info.exp_year );
	var cvc_valid = Stripe.validateCVC( card_info.cvc );
	if ( card_info.cardholder_name.length > 0 ) {
		var cardholder_name_valid = true;
	}
	else {
		var cardholder_name_valid = false;
	}

	return {
		card_number: card_number_valid,
		exp_date: exp_date_valid,
		cvc: cvc_valid,
		cardholder_name: cardholder_name_valid
	};
}

function gfp_stripe_clear_card_info() {
	var form_id = gfp_stripe_js_vars.form_id;
	var form_element = jQuery( '#gform_' + form_id );

	var card_number_field = form_element.find( '#input_' + form_id + '_' + gfp_stripe_js_vars.creditcard_field_id + '_1' );

	card_number_field.val( '' );
	form_element.find( '.ginput_card_expiration_month' ).val( '' );
	form_element.find( '.ginput_card_expiration_year' ).val( '' );
	form_element.find( '.ginput_card_security_code' ).val( '' );
	form_element.find( '#input_' + form_id + '_' + gfp_stripe_js_vars.creditcard_field_id + '_5' ).val( '' );

	var cardContainer = card_number_field.parents( '.gfield' ).find( '.gform_card_icon_container' );
	jQuery( cardContainer ).find( '.gform_card_icon' ).removeClass( 'gform_card_icon_selected gform_card_icon_inactive' );
}

function gfp_stripe_set_credit_card_error( error_message ) {
	var form_id = gfp_stripe_js_vars.form_id;
	var form_element = jQuery( '#gform_' + form_id );
	var credit_card_item = form_element.find( 'li#field_' + form_id + '_' + gfp_stripe_js_vars.creditcard_field_id );
	var credit_card_fields_container = credit_card_item.find( 'div.ginput_container' );

	gfp_stripe_clear_card_info();

	credit_card_item.addClass( 'gfield_error' );
	credit_card_fields_container.after( '<div id="stripe_validation_error" class="gfield_description validation_message">' + error_message + '</div>' );

	jQuery( 'img#gform_ajax_spinner_' + form_id ).remove();
	window['gf_submitting_' + form_id] = false;
}

function gfp_stripe_clear_credit_card_error() {
	jQuery( '#stripe_validation_error' ).remove();
	jQuery( '#gform_' + gfp_stripe_js_vars.form_id ).find( 'li#field_' + gfp_stripe_js_vars.form_id + '_' + gfp_stripe_js_vars.creditcard_field_id ).removeClass( 'gfield_error' );
}