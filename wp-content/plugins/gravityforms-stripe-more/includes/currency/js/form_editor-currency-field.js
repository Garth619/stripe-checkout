/**
 *
 */
jQuery( document ).ready( function ( jQuery ) {

							  fieldSettings['currency'] = '.label_setting, ' +
														  '.currency_field_type_setting, ' +
														  '.currency_checkbox_setting,' +
														  '.currency_initial_item_setting, ' +
														  '.description_setting, ' +
														  '.rules_setting, ' +
														  '.duplicate_setting, ' +
														  '.admin_label_setting,' +
														  '.size_setting, ' +
														  '.error_message_setting, ' +
														  '.css_class_setting, ' +
														  '.visibility_setting, ' +
														  '.prepopulate_field_setting, ' +
														  '.conditional_logic_field_setting';

							  jQuery( document ).bind( 'gform_load_field_settings', function ( event, field, form ) {
								  var inputType = GetInputType( field );

								  if ( field.displayAllCurrencies ) {
									  jQuery( "#gfield_currency_all" ).prop( "checked", true );
								  }
								  else {
									  jQuery( "#gfield_currency_select" ).prop( "checked", true );
								  }

								  ToggleCurrency( true );

								  jQuery( '#gfield_currency_initial_item_enabled' ).prop( 'checked', field.currencyInitialItemEnabled ? true : false );
								  jQuery( '#field_currency_initial_item' ).val( field.currencyInitialItemEnabled ? field.currencyInitialItem : '' );
								  ToggleCurrencyInitialItem( true );

								  jQuery( '.gfield_currency_checkbox' ).each( function () {
									  if ( field['choices'] ) {
										  for ( var i = 0; i < field['choices'].length; i++ ) {
											  if ( this.value == field['choices'][i].value ) {
												  this.checked = true;
												  return;
											  }
										  }
									  }
									  this.checked = false;
								  } );

								  if ( ( 'currency' === field.type ) && ( 'select' === inputType || 'radio' === inputType ) ) {
									  var selectSettings = fieldSettings[field.inputType];
									  var currencySettings = fieldSettings['currency'];
									  jQuery( selectSettings ).hide();
									  jQuery( currencySettings ).show();
								  }

								  if ( ( 'currency' === field.type ) && ( 'select' !== inputType ) ) {
									  jQuery( '.currency_initial_item_setting' ).hide();
									  jQuery( '#gfield_currency_initial_item_enabled' ).prop( 'checked', false );
									  ToggleCurrencyInitialItem();
								  }

								  jQuery( "#currency_field_type" ).val( field.inputType );
							  } );

							  gform.addFilter( 'gform_conditional_logic_values_input', 'gform_conditional_logic_values_input_currency' );

						  }
);

function StartAddCurrencyField() {
	if ( GetFieldsByType( ['currency'] ).length > 0 ) {
		alert( 'Only one Currency field can be added to the form' );
	}
	else {
		StartAddField( 'currency' );
	}
}

function ToggleCurrency( isInit ) {
	var speed = isInit ? '' : 'slow';

	if ( jQuery( '#gfield_currency_all' ).is( ':checked' ) ) {
		jQuery( '#gfield_settings_currency_container' ).hide( speed );
		SetFieldProperty( 'displayAllCurrencies', true );
		SetFieldProperty( 'choices', new Array() );
	}
	else {
		jQuery( '#gfield_settings_currency_container' ).show( speed );
		SetFieldProperty( 'displayAllCurrencies', false );
	}
}

function ToggleCurrencyInitialItem( isInit ) {
	var speed = isInit ? "" : "slow";

	if ( jQuery( "#gfield_currency_initial_item_enabled" ).is( ":checked" ) ) {
		jQuery( "#gfield_currency_initial_item_container" ).show( speed );

		if ( !isInit ) {
			jQuery( "#field_currency_initial_item" ).val( currency_field_vars.select_currency_text );
		}
	}
	else {
		jQuery( "#gfield_currency_initial_item_container" ).hide( speed );
		jQuery( "#field_currency_initial_item" ).val( '' );
	}

}

function SetCurrencyInitialItem() {
	var enabled = jQuery( '#gfield_currency_initial_item_enabled' ).is( ':checked' );
	SetFieldProperty( 'currencyInitialItem', enabled ? jQuery( '#field_currency_initial_item' ).val() : null );
	SetFieldProperty( 'currencyInitialItemEnabled', enabled );
}

function SetSelectedCurrencies() {
	var field = GetSelectedField();
	field['choices'] = new Array();

	jQuery( '.gfield_currency_checkbox' ).each( function () {
		if ( this.checked ) {
			field['choices'].push( new Choice( this.name, this.value ) );
		}
	} );

	field['choices'].sort( function ( a, b ) {
		return ( a['text'].toLowerCase() > b['text'].toLowerCase() );
	} );
}

function gform_conditional_logic_values_input_currency( str, objectType, ruleIndex, selectedFieldId, selectedValue ) {

	var field = GetFieldById( selectedFieldId );

	if ( field && 'currency' == field['type'] && field['displayAllCurrencies'] ) {

		var obj = GetConditionalObject( objectType ),
			rule = obj['conditionalLogic']['rules'][ruleIndex],
			inputName = rule.value;

		if ( !inputName ) {
			inputName = false;
		}
		var dropdown_id = inputName == false ? objectType + '_rule_value_' + ruleIndex : inputName;

		var dropdown = jQuery( '#' + dropdown_id + '.gfield_currency_dropdown' );

		if ( dropdown.length > 0 ) {

			var options = dropdown.html();
			options = options.replace( "value=\"" + selectedValue + "\"", "value=\"" + selectedValue + "\" selected=\"selected\"" );
			str = "<select id='" + dropdown_id + "' class='gfield_rule_select gfield_rule_value_dropdown gfield_currency_dropdown'>" + options + "</select>";
		}
		else {
			var placeholderName = inputName == false ? "gfield_ajax_placeholder_" + ruleIndex : inputName + "_placeholder";

			jQuery.post( ajaxurl,
						 {   action: 'gfp_more_stripe_get_currency_values',
							 objectType: objectType,
							 ruleIndex: ruleIndex,
							 inputName: inputName,
							 selectedValue: selectedValue
						 },
						 function ( response ) {
							 var dropdown_string = response.data;
							 if ( dropdown_string ) {
								 jQuery( '#' + placeholderName ).replaceWith( dropdown_string.trim() );

								 SetRuleProperty( objectType, ruleIndex, 'value', jQuery( '#' + dropdown_id ).val() );
							 }
						 }
			);

			str = "<select id='" + placeholderName + "' class='gfield_rule_select'><option>" + gf_vars['loading'] + "</option></select>";
		}
	}

	return str;
}