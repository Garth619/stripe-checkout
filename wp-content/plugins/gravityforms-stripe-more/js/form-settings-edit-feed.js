/**
 *
 */

var GFP_Stripe_Rule = {
	form: form,
	form_fields: stripe_edit_feed_settings.form_fields,
	metadata: stripe_edit_feed_settings.metadata
};

jQuery( document ).ready( function () {
	jQuery( document ).on( 'gfp_stripe_rule_select_type', gfp_more_stripe_after_select_type );
	jQuery( document ).on( 'gfp_stripe_rule_invalid_creditcard_error', gfp_more_stripe_invalid_creditcard_error );

	var enable_coupons = jQuery( '#gfp_more_stripe_enable_coupons' );

	enable_coupons.change( gfp_stripe_set_coupon_field_placeholder );

	enable_coupons.click( gfp_stripe_toggle_coupon_setting );

	jQuery( document ).on( 'stripeFormSelected', gfp_more_stripe_reset_metadata );

	if ( 0 == stripe_edit_feed_settings.metadata.length ) {
		GFP_Stripe_Rule.metadata = [new gfp_stripe_metadata_option()];
	}

	if ( 0 !== stripe_edit_feed_settings.form.length ) {
		gfp_stripe_create_custom_metadata_options();
	}

	jQuery( ".metadataname input, .metadatavalue select" ).on( 'change', function () {
		gfp_stripe_save_metadata();
	} );
	
	jQuery( "#gfp_stripe_edit_feed_form" ).submit( gfp_stripe_save_metadata );

	var metadata_key_name_inputs = jQuery( '.custom_metadataname' ).find( 'input' );
	metadata_key_name_inputs.on( 'focus', gfp_stripe_clear_input_placeholder );
	metadata_key_name_inputs.on( 'blur', gfp_stripe_add_input_placeholder );

	jQuery( '#gfp_more_stripe_enable_metadata' ).click( gfp_stripe_toggle_metadata_fields );

} );

function gfp_stripe_toggle_coupon_setting() {
	var type = jQuery( "#gfp_stripe_type" ).val();
			var coupons_field_container = jQuery( '#gfp_more_stripe_coupons_container' );
			var coupons_apply_container = jQuery( '#gfp_more_stripe_coupons_apply_container' );

			if ( jQuery( this ).is( ':checked' ) ) {
				coupons_field_container.show( 'slow' );
				if ( 'subscription' == type ) {
					coupons_apply_container.show( 'slow' );
				}
			} else {
				coupons_field_container.hide( 'slow' );
				if ( 'subscription' == type ) {
					coupons_apply_container.hide( 'slow' );
				}
			}
}

function gfp_stripe_set_coupon_field_placeholder() {
	if ( this.checked ) {
				jQuery( '#gfp_more_stripe_coupons_field' ).val( 'Select a field' );
			}
}

function gfp_stripe_toggle_metadata_fields() {
			var metadata_fields = jQuery( '#custom_metadata_fields' );

			if ( jQuery( this ).is( ':checked' ) ) {
				metadata_fields.show( 'slow' );
			} else {
				metadata_fields.hide( 'slow' );
				gfp_more_stripe_reset_metadata( null, GFP_Stripe_Rule.form, { form_fields: GFP_Stripe_Rule.form_fields } );
			}
}

function gfp_stripe_clear_input_placeholder(){
	if ( stripe_edit_feed_settings.metadata_key_name_placeholder == jQuery( this ).val() ) {
		jQuery( this ).val('');
	}
}

function gfp_stripe_add_input_placeholder(){
	if ( '' == jQuery( this ).val() ) {
		jQuery( this ).val(stripe_edit_feed_settings.metadata_key_name_placeholder);
	}
}

function gfp_more_stripe_after_select_type( event, type ) {
	jQuery( "#stripe_free_trial_no_credit_card_container input:checked" ).prop( 'checked', false );

	if ( 'subscription' == type ) {
		jQuery( '#stripe_free_trial_no_credit_card_container' ).show();
	}
	else {
		jQuery( '#stripe_free_trial_no_credit_card_container' ).hide();
	}
}

function gfp_more_stripe_invalid_creditcard_error() {
	var free_trial_no_credit_card_option_available = jQuery( '#stripe_free_trial_no_credit_card_container' ).css( 'display' );
	if ( 'undefined' !== typeof( free_trial_no_credit_card_option_available ) && 'none' !== free_trial_no_credit_card_option_available ) {
		if ( jQuery( '#gfp_more_stripe_free_trial_no_credit_card' ).prop( 'checked' ) ) {
			stripe_edit_feed_settings.show_invalid_creditcard_error = false;
		}
	}
}

function gfp_more_stripe_reset_metadata( event, form, additional_functions ) {
	GFP_Stripe_Rule.metadata = [new gfp_stripe_metadata_option()];
	GFP_Stripe_Rule.form = form;
	GFP_Stripe_Rule.form_fields = additional_functions.form_fields;
	
	gfp_stripe_create_custom_metadata_options();
}

function gfp_stripe_create_custom_metadata_options() {

	var form = GFP_Stripe_Rule.form;
	var metadata = GFP_Stripe_Rule.metadata;
	var str = '';

	for ( var i = 0; i < metadata.length; i++ ) {

		str += '<div class="margin_vertical_10">';
		str += '<div class="metadataname">';
		str += '<div class="custom_metadataname">' + gfp_stripe_get_metadata_key_name_input( i, metadata[i] ) + '</div></div>';
		str += '<div class="metadatavalue"><select type="text" name="gfp_stripe_metadata_value_' + i + '" id="gfp_stripe_metadata_value_' + i + '" class="meta-value-select width-1">';
		str += gfp_stripe_get_metadata_key_value_options( form, metadata[i].key_value ) + '</select></div>';

		str += "<img src='" + stripe_edit_feed_settings.metadata_add_img_url + "' class='add_field_choice' title='" + stripe_edit_feed_settings.metadata_add_field_tooltip + "' alt='" + stripe_edit_feed_settings.metadata_add_field_tooltip + "' style='cursor:pointer;' onclick=\"gfp_stripe_insert_metadata_field(" + (i + 1) + ", 'metadata');\" />";
		if ( metadata.length > 1 )
			str += "<img src='" + stripe_edit_feed_settings.metadata_remove_img_url + "' title='" + stripe_edit_feed_settings.metadata_remove_field_tooltip + "' alt='" + stripe_edit_feed_settings.metadata_remove_field_tooltip + "' class='delete_field_choice' style='cursor:pointer;' onclick=\"gfp_stripe_delete_metadata_field(" + (i) + ", 'metadata');\" /></li>";

		str += '</div>';
	}

	jQuery( '#custom_metadata_fields' ).html( str );
}

function gfp_stripe_get_metadata_key_value_options( form, meta_value ) {

	var form_fields = GFP_Stripe_Rule.form_fields;

	var str = '<option value=""></option>';
	for ( i = 0; i < form_fields.length; i++ ) {
		if ( form_fields[i][0] == meta_value ) {
			str += '<option value="' + form_fields[i][0] + '" selected="selected">' + form_fields[i][1] + '</option>';
		} else {
			str += '<option value="' + form_fields[i][0] + '">' + form_fields[i][1] + '</option>';
		}
	}

	return str;
}

function gfp_stripe_get_metadata_key_name_input( index, metadata ) {

	var key_name = (metadata.key_name != "") ? metadata.key_name : stripe_edit_feed_settings.metadata_key_name_placeholder;

	str = '<input type="text" name="gfp_stripe_custom_metadata_name_' + index + '" id="gfp_stripe_custom_metadata_name_' + index + '" class="width-1" value="' + key_name + '" maxlength="40" />';

	return str;
}

function gfp_stripe_insert_metadata_field( ruleIndex, meta_group ) {

	GFP_Stripe_Rule[meta_group].splice( ruleIndex, 0, new gfp_stripe_metadata_option() );
	gfp_stripe_create_custom_metadata_options();

}

function gfp_stripe_delete_metadata_field( ruleIndex, meta_group ) {

	GFP_Stripe_Rule[meta_group].splice( ruleIndex, 1 );
	gfp_stripe_create_custom_metadata_options();

}

function gfp_stripe_metadata_option() {
	this.key_name = "";
	this.key_value = "";
	this.custom = false;
}

function gfp_stripe_save_metadata() {

	var metadata = GFP_Stripe_Rule.metadata;

	for ( var i = 0; i < metadata.length; i++ ) {
		metadata[i].custom = true;
		metadata[i].key_name = jQuery( "#gfp_stripe_custom_metadata_name_" + i ).val();
		metadata[i].key_value = jQuery( "#gfp_stripe_metadata_value_" + i ).val();
	}

	GFP_Stripe_Rule.metadata = metadata;

	var json = jQuery.toJSON( metadata );
	jQuery( "#gfp_stripe_metadata" ).val( json );

}