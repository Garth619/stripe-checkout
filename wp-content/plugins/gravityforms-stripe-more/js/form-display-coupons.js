/**
 *
 */

var gfp_stripe_coupon = '';
var gfp_stripe_percent_off = 0;
var gfp_stripe_amount_off = 0;

jQuery( document ).ready( function ( $ ) {
	jQuery( document ).on( 'gform_post_render', gformAddStripeCouponsAction );
} );

function gformAddStripeCouponsAction( event, form_id, current_page ) {
	if ( form_id !== parseInt( gfp_stripe_coupons_js_vars.form_id ) ) {
		return;
	}


	jQuery( '#gform_' + form_id + ' #input_' + form_id + '_' + gfp_stripe_coupons_js_vars.coupon_field_id ).change( function () {
		if ( window["gformCalculateTotalPrice"] ) {
			var totalElement = jQuery( ".ginput_total_" + form_id );
			jQuery( totalElement ).before( '<' + 'img id="gform_ajax_spinner_' + form_id + '"  class="gform_ajax_spinner" src="' + gfp_stripe_coupons_js_vars.spinner_url + '" alt="" />' );
			window["gformCalculateTotalPrice"]( form_id );
		}
	} );
}

function gform_product_total( form_id, total ) {
	var coupon_field_id = gfp_stripe_coupons_js_vars.coupon_field_id;

	jQuery( '#gform_' + form_id + ' li#field_' + form_id + '_' + coupon_field_id ).removeClass( 'gfield_error' );
	jQuery( '#gform_' + form_id + ' li#field_' + form_id + '_' + coupon_field_id + ' div.validation_message' ).remove();
	var totalElement = jQuery( ".ginput_total_" + form_id );
	var current_coupon = jQuery( '#gform_' + form_id + ' #input_' + form_id + '_' + coupon_field_id ).val();
	if ( ( gfp_stripe_coupon.length > 0 ) && ( gfp_stripe_coupon === current_coupon ) && ( gfp_stripe_percent_off > 0 ) ) {
		total = total - (total * ( gfp_stripe_percent_off / 100 ) );
		jQuery( totalElement ).prev( 'img.gform_ajax_spinner' ).remove();
		return total;
	}
	else if ( ( gfp_stripe_coupon.length > 0 ) && ( gfp_stripe_coupon === current_coupon ) && ( gfp_stripe_amount_off > 0 ) ) {
		if ( 0 === gf_global.gf_currency_config['decimals'] ) {
			var amount_off = gfp_stripe_amount_off;
		}
		else {
			var amount_off = ( gfp_stripe_amount_off / 100 );
		}
		total = total - amount_off;
		jQuery( totalElement ).prev( 'img.gform_ajax_spinner' ).remove();
		return total;
	}
	else if ( ( gfp_stripe_coupon.length > 0 ) && ( current_coupon.length === 0 ) ) {
		jQuery( totalElement ).prev( 'img.gform_ajax_spinner' ).remove();
		return total;
	}
	else if ( current_coupon.length > 0 ) {
		jQuery.post( gfp_stripe_coupons_js_vars.ajaxurl, {
						 action: 'gfp_more_stripe_get_coupon',
						 coupon: current_coupon,
						 form: form_id
					 },
					 function ( response ) {
						 if ( true === response.success ) {
							 gfp_stripe_coupon = current_coupon;
							 gfp_stripe_percent_off = response.data['percent_off'];
							 if ( gfp_stripe_percent_off > 0 ) {
								 total = total - (total * ( gfp_stripe_percent_off / 100 ) );
							 }
							 else {
								 gfp_stripe_amount_off = response.data['amount_off'];
								 if ( gfp_stripe_amount_off > 0 ) {
									 if ( 0 === gf_global.gf_currency_config['decimals'] ) {
										 var amount_off = gfp_stripe_amount_off;
									 }
									 else {
										 var amount_off = ( gfp_stripe_amount_off / 100 );
									 }
									 total = total - amount_off;
								 }
							 }
							 if ( totalElement.length > 0 ) {
								 totalElement.next().val( total );
								 jQuery( totalElement ).prev( 'img.gform_ajax_spinner' ).remove();
								 totalElement.html( gformFormatMoney( total ) );
							 }
							 return total;
						 }
						 else {
							 var error_message = response.data['error_message'];
							 jQuery( totalElement ).prev( 'img.gform_ajax_spinner' ).remove();
							 jQuery( '#gform_' + form_id + ' #input_' + form_id + '_' + coupon_field_id ).val( '' );
							 jQuery( '#gform_' + form_id + ' li#field_' + form_id + '_' + coupon_field_id ).addClass( 'gfield_error' );
							 jQuery( '#gform_' + form_id + ' li#field_' + form_id + '_' + coupon_field_id + ' div.ginput_container' ).after( '<div class=\'gfield_description validation_message\'>' + error_message + '</div>' );
							 gfp_stripe_coupon = '';
							 gfp_stripe_percent_off = 0;
							 gfp_stripe_amount_off = 0;
							 return total;
						 }
					 } );
	}
	return total;

}