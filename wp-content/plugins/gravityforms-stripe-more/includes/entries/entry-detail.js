/**
 *
 */
function cancel_stripe_subscription() {
	jQuery( "#stripe_wait" ).show();
	jQuery( "#cancelsub" ).attr( "disabled", true );
	var lead_id = stripe_entry_detail.lead_id
	var form_id = stripe_entry_detail.form_id
	jQuery.post( ajaxurl, {
					 action: "gfp_more_stripe_cancel_subscription",
					 leadid: lead_id,
					 formid: form_id,
					 gfp_more_stripe_cancel_subscription: stripe_entry_detail.nonce },
				 function ( response ) {

					 jQuery( "#stripe_wait" ).hide();

					 if ( true == response.success ) {
						 jQuery( "#stripe_subscription_status" ).html( stripe_entry_detail.success_message );
						 jQuery( "#cancelsub" ).hide();
					 }
					 else {
						 jQuery( "#cancelsub" ).attr( "disabled", false );
						 alert( stripe_entry_detail.error_message + response.data );
					 }
				 }
	);
}