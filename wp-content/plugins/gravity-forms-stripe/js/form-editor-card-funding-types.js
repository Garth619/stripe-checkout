/**
 *
 */
jQuery( document ).ready( function ( jQuery ) {

	fieldSettings['creditcard'] += ', .credit_card_funding_type_setting';

	jQuery( document ).bind( 'gform_load_field_settings', function ( event, field, form ) {

		var inputType = GetInputType( field );

		if ( 'creditcard' == inputType ) {

			if ( !field.creditCardFundingTypes || 0 >= field.creditCardFundingTypes.length ) {
				field.creditCardFundingTypes = ['credit', 'debit', 'prepaid', 'unknown'];
			}

			for ( i in field.creditCardFundingTypes ) {

				if ( !field.creditCardFundingTypes.hasOwnProperty( i ) ) {
					continue;
				}

				jQuery( '#field_credit_card_funding_' + field.creditCardFundingTypes[i] ).prop( 'checked', true );
			}

		}

		jQuery( '.field_credit_card_funding_type' ).change( function () {
			SetCardFundingType( this, this.value );
		} );

	} );
} );

function SetCardFundingType( elem, value ) {

	var funding_types = GetSelectedField()['creditCardFundingTypes'] ? GetSelectedField()['creditCardFundingTypes'] : [];

	if ( jQuery( elem ).is( ':checked' ) ) {

		if ( -1 == jQuery.inArray( value, funding_types ) ) {
			funding_types[funding_types.length] = value;
		}

	} else {

		var index = jQuery.inArray( value, funding_types );

		if ( -1 != index ) {
			funding_types.splice( index, 1 );
		}

	}

	SetFieldProperty( 'creditCardFundingTypes', funding_types );
}