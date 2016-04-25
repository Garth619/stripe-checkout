/**
 *
 */
jQuery( document ).ready( function () {

	jQuery( '#more_stripe_import_form_id' ).change( gfp_stripe_get_rules_for_form );

	jQuery( '#more-stripe-import' ).submit( function ( event ) {
		event.preventDefault();
		jQuery( '#more-stripe-import-body' ).hide();
		jQuery( '#stripe-wait' ).fadeIn();
		jQuery( '#more_stripe_import_submit' ).attr( 'disabled', true );
		var post_data = {
			action: 'import_current_stripe_customers',
			import_current_stripe_customers: more_stripe_import_vars.nonce,
			form: jQuery( '#more_stripe_import_form_id' ).val(),
			rule: jQuery( '#more_stripe_import_rule_id' ).val()
		};
		jQuery.ajax( {
						 url: ajaxurl,
						 type: 'POST',
						 data: post_data,
						 error: function ( jqXHR, errorType, errorMessage ) {
							 jQuery( '#stripe-wait' ).hide();
							 jQuery( '#import-start-message' ).show();
						 }
					 } )
			.done( function ( response ) {

					   jQuery( '#stripe-wait' ).hide();

					   if ( true == response.success ) {
						   jQuery( '#import-success' ).fadeIn();
					   }
					   else {
						   jQuery( '#more_stripe_import_submit' ).attr( 'disabled', false );
						   jQuery( '#more-stripe-import-body' ).show();
						   alert( response.data );
					   }
				   } );

		return false;
	} );
} );

function gfp_stripe_get_rules_for_form() {
	jQuery( '#more_stripe_import_submit' ).attr( 'disabled', true );
	var post_data = {
		action: 'import_get_form_rules',
		import_get_form_rules: more_stripe_import_vars.get_form_rules_nonce,
		form: jQuery( '#more_stripe_import_form_id' ).val()
	};

	jQuery.post( ajaxurl, post_data, function ( response ) {
					  if ( true === response.success ){
						  jQuery( '#more_stripe_import_rule_id' ).html( response.data );
						  jQuery( '#more_stripe_import_submit' ).attr( 'disabled', false );
					  }
		else {
						  alert( response.data );
					  }
				   } );

}