/**
 *
 */
jQuery( document ).ready( function ( jQuery ) {

							  jQuery( document ).bind( 'gform_load_form_settings', function ( event, form ) {

								  ConvertBasePrices();
							  } );

						  }
);

function ConvertBasePrices() {
	var change_base_price = false;
	change_base_price = gf_global.gf_currency_config['symbol_left'] !== form_currency_info['symbol_left'];
	if ( false === change_base_price ) {
		change_base_price = gf_global.gf_currency_config['symbol_right'] !== form_currency_info['symbol_right'];
	}
	if ( true === change_base_price ) {
		jQuery( "input[id^='ginput_base_price_']" ).each( function () {
				SetConvertedBasePrice( this );
			} );
	}
}

function SetConvertedBasePrice( element ) {
	var base_price_input = jQuery( element );
	var price = base_price_input.val();
	var base_price_input_id = base_price_input.attr( 'id' ).split( '_' );
	var field_id = base_price_input_id[4];
	var field = GetFieldById( field_id );
	field['basePrice'] = price;
}