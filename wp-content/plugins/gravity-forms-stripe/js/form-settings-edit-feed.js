/**
 *
 */

function SelectType( type ) {
	jQuery( "#stripe_field_group" ).slideUp();

	jQuery( "#stripe_field_group input[type=\"text\"], #stripe_field_group select" ).val( "" );

	jQuery( "#stripe_field_group input:checked" ).prop( "checked", false );

	jQuery( document ).trigger( 'gfp_stripe_rule_select_type', type );

	if ( type ) {
		jQuery( "#stripe_form_container" ).slideDown();
		jQuery( "#gfp_stripe_form" ).val( "" );
	}
	else {
		jQuery( "#stripe_form_container" ).slideUp();
	}
}

function SelectForm( type, formId, settingId ) {
	if ( !formId ) {
		jQuery( '#stripe_field_group' ).slideUp();
		return;
	}

	jQuery( '#stripe_wait' ).show();
	jQuery( '#stripe_field_group' ).slideUp();

	var post_data = { action: 'gfp_select_stripe_form',
		gfp_select_stripe_form: stripe_edit_feed_settings.select_form_nonce,
		type: type,
		form_id: formId,
		setting_id: settingId };

	jQuery.post( ajaxurl, post_data, function ( response ) {
		if ( true === response.success ) {
			EndSelectForm( response.data.form, response.data.customer_fields, response.data.endselectform_args );
		}
		else {
			jQuery( '#stripe_wait' ).hide();
			alert( stripe_edit_feed_settings.select_form_error_message );
		}
	} );

	return true;
}

function EndSelectForm( form_meta, customer_fields, additional_functions ) {

	form = form_meta;

	if ( !( typeof additional_functions === 'null' ) ) {
		var populate_field_options = additional_functions.populate_field_options;
		var post_update_action = additional_functions.post_update_action;
		var show_fields = additional_functions.show_fields;
	}
	else {
		var populate_field_options = '';
		var post_update_action = '';
		var show_fields = '';
	}

	var type = jQuery( "#gfp_stripe_type" ).val();

	jQuery( ".gfp_stripe_invalid_form" ).hide();
	if ( ( 'product' == type || 'subscription' == type || 'update-subscription' == type ) && GetFieldsByType( ['product'] ).length == 0 ) {
		jQuery( "#gfp_stripe_invalid_product_form" ).show();
		jQuery( "#stripe_wait" ).hide();
		return;
	}
	else if ( ( 'product' == type || 'subscription' == type || 'update-billing' == type ) && GetFieldsByType( ['creditcard'] ).length == 0 ) {
		jQuery( document ).trigger( 'gfp_stripe_rule_invalid_creditcard_error' );
		if ( 'undefined' === typeof( stripe_edit_feed_settings.show_invalid_creditcard_error )  || true === stripe_edit_feed_settings.show_invalid_creditcard_error ) {
			jQuery( "#gfp_stripe_invalid_creditcard_form" ).show();
			jQuery( "#stripe_wait" ).hide();
			return;
		}
	}

	jQuery( ".stripe_field_container" ).hide();

	if ( populate_field_options.length > 0 ) {
		var func;
		for ( var i = 0; i < populate_field_options.length; i++ ) {
			func = new Function( 'type', populate_field_options[ i ] );
			func( type );
		}
	}

	jQuery( "#stripe_customer_fields" ).html( customer_fields );

	var post_fields = GetFieldsByType( ["post_title", "post_content", "post_excerpt", "post_category", "post_custom_field", "post_image", "post_tag"] );
	if ( post_update_action.length > 0 ) {
		var func;
		for ( var i = 0; i < post_update_action.length; i++ ) {
			func = new Function( 'type', 'post_fields', post_update_action[ i ] );
			func( type, post_fields );
		}
	}
	else {
		jQuery( "#gfp_stripe_update_post" ).attr( "checked", false );
		jQuery( "#stripe_post_update_action" ).hide();
	}


	jQuery( document ).trigger( 'stripeFormSelected', [form, additional_functions ] );

	jQuery( "#gfp_stripe_conditional_enabled" ).attr( 'checked', false );
	SetStripeCondition( "", "" );

	jQuery( "#stripe_field_container_" + type ).show();
	if ( show_fields.length > 0 ) {
		var func;
		for ( var i = 0; i < show_fields.length; i++ ) {
			func = new Function( 'type', show_fields[ i ] );
			func( type );
		}
	}

	jQuery( "#stripe_field_group" ).slideDown();
	jQuery( "#stripe_wait" ).hide();
}


function GetFieldsByType( types ) {
	var fields = new Array();
	for ( var i = 0; i < form["fields"].length; i++ ) {
		if ( IndexOf( types, form["fields"][i]["type"] ) >= 0 )
			fields.push( form["fields"][i] );
	}
	return fields;
}

function IndexOf( ary, item ) {
	for ( var i = 0; i < ary.length; i++ )
		if ( ary[i] == item )
			return i;

	return -1;
}

var form = stripe_edit_feed_settings.form;

if ( '' === stripe_edit_feed_settings.new_feed ) {

	jQuery( document ).ready( function () {
		var selectedField = stripe_edit_feed_settings.reg_condition_selected_field;
		var selectedValue = stripe_edit_feed_settings.reg_condition_selected_value;
		SetStripeCondition( selectedField, selectedValue );
	} );
}

function SetStripeCondition( selectedField, selectedValue ) {

	jQuery( "#gfp_stripe_conditional_field_id" ).html( GetSelectableFields( selectedField, 20 ) );
	var optinConditionField = jQuery( "#gfp_stripe_conditional_field_id" ).val();
	var checked = jQuery( "#gfp_stripe_conditional_enabled" ).attr( 'checked' );

	if ( optinConditionField ) {
		jQuery( "#gfp_stripe_conditional_message" ).hide();
		jQuery( "#gfp_stripe_conditional_fields" ).show();
		jQuery( "#gfp_stripe_conditional_value_container" ).html( GetFieldValues( optinConditionField, selectedValue, 20 ) );
		jQuery( "#gfp_stripe_conditional_value" ).val( selectedValue );
	}
	else {
		jQuery( "#gfp_stripe_conditional_message" ).show();
		jQuery( "#gfp_stripe_conditional_fields" ).hide();
	}

	if ( !checked ) {
		jQuery( "#gfp_stripe_conditional_container" ).hide();
	}

}

function GetFieldValues( fieldId, selectedValue, labelMaxCharacters ) {
	if ( !fieldId )
		return "";

	var str = "";
	var field = GetFieldById( fieldId );
	if ( !field )
		return "";

	var isAnySelected = false;

	if ( ( 'post_category' == field['type'] ) && field['displayAllCategories'] ) {
		str += stripe_edit_feed_settings.post_categories;
	}
	else if ( field.choices ) {
		str += '<select id="gfp_stripe_conditional_value" name="gfp_stripe_conditional_value" class="optin_select">'

		for ( var i = 0; i < field.choices.length; i++ ) {
			var fieldValue = field.choices[i].value ? field.choices[i].value : field.choices[i].text;
			var isSelected = fieldValue == selectedValue;
			var selected = isSelected ? "selected='selected'" : "";
			if ( isSelected )
				isAnySelected = true;

			str += "<option value='" + fieldValue.replace( /'/g, "&#039;" ) + "' " + selected + ">" + TruncateMiddle( field.choices[i].text, labelMaxCharacters ) + "</option>";
		}

		if ( !isAnySelected && selectedValue ) {
			str += "<option value='" + selectedValue.replace( /'/g, "&#039;" ) + "' selected='selected'>" + TruncateMiddle( selectedValue, labelMaxCharacters ) + "</option>";
		}
		str += "</select>";
	}
	else {
		selectedValue = selectedValue ? selectedValue.replace( /'/g, "&#039;" ) : "";
		str += "<input type='text' placeholder='" + stripe_edit_feed_settings.conditional_value_placeholder + "' id='gfp_stripe_conditional_value' name='gfp_stripe_conditional_value' value='" + selectedValue.replace( /'/g, "&#039;" ) + "'>";
	}

	return str;
}

function GetFieldById( fieldId ) {
	for ( var i = 0; i < form.fields.length; i++ ) {
		if ( form.fields[i].id == fieldId )
			return form.fields[i];
	}
	return null;
}

function TruncateMiddle( text, maxCharacters ) {
	if ( text.length <= maxCharacters )
		return text;
	var middle = parseInt( maxCharacters / 2 );
	return text.substr( 0, middle ) + "..." + text.substr( text.length - middle, middle );
}

function GetSelectableFields( selectedFieldId, labelMaxCharacters ) {
	var str = "";
	var inputType;
	for ( var i = 0; i < form.fields.length; i++ ) {
		fieldLabel = form.fields[i].adminLabel ? form.fields[i].adminLabel : form.fields[i].label;
		fieldLabel = typeof fieldLabel == 'undefined' ? '' : fieldLabel;
		inputType = form.fields[i].inputType ? form.fields[i].inputType : form.fields[i].type;
		if ( IsConditionalLogicField( form.fields[i] ) ) {
			var selected = form.fields[i].id == selectedFieldId ? "selected='selected'" : "";
			str += "<option value='" + form.fields[i].id + "' " + selected + ">" + TruncateMiddle( fieldLabel, labelMaxCharacters ) + "</option>";
		}
	}
	return str;
}

function IsConditionalLogicField( field ) {
	inputType = field.inputType ? field.inputType : field.type;
	var supported_fields = ["checkbox", "radio", "select", "text", "website", "textarea", "email", "hidden", "number", "phone", "multiselect", "post_title",
							"post_tags", "post_custom_field", "post_content", "post_excerpt", "total"];

	var index = jQuery.inArray( inputType, supported_fields );

	return index >= 0;
}
