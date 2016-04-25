/**
 *
 */
function charge_customer_for_post() {
	jQuery( '#stripe_wait' ).show();
	jQuery( '#chargecustomer' ).attr( 'disabled', true );
	var lead_id = stripe_charge_for_post.lead_id
	var form_id = stripe_charge_for_post.form_id
	jQuery.post( ajaxurl, {
					 action: 'gfp_more_stripe_charge_customer_for_post',
					 leadid: lead_id,
					 formid: form_id,
					 gfp_more_stripe_charge_customer_for_post: stripe_charge_for_post.nonce },
				 function ( response ) {

					 jQuery( '#stripe_wait' ).hide();

					 if ( true === response.success ) {
						 jQuery( '#chargecustomer' ).hide();
						 jQuery( '#chargecustomer' ).after( stripe_charge_for_post.success_message );
					 }
					 else {
						 jQuery( '#chargecustomer' ).attr( 'disabled', false );
						 alert( 'Error: ' + response.data );
					 }
				 }
	);
}