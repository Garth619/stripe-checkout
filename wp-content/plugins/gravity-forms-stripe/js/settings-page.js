/**
 *
 */
jQuery( document ).ready( function () {

	jQuery( '.settings-section' ).on( 'click', function () {
		jQuery( '.' + jQuery( this ).attr( 'data-toggle' ) ).toggle();
		jQuery( this ).find( '.fa-plus-square-o' ).toggleClass( 'toggled' );
		jQuery( this ).find( '.fa-plus-square' ).toggleClass( 'toggled' );
		jQuery( this ).find( '.fa-minus-square' ).toggle();
	} );

	jQuery( '#gfp_stripe_sign_up_submit' ).on( 'click', function () {

		var email = jQuery( '#gfp_stripe_update_email' ).val();

		if ( 0 !== email.length ) {

			var createButton = jQuery( '#gfp_stripe_sign_up_submit' );
			var spinner = new gfAjaxSpinner( createButton, gfp_stripe_settings_page_vars.baseURL + '/images/spinner.gif' );

			jQuery( '#gfp_stripe_sign_up_error_message' ).html( '' );

			var origVal = createButton.val();
			createButton.val( gfp_stripe_settings_page_vars.status_message );

			var post_data = {
				email: email,
				action: 'gfp_stripe_updates_sign_up',
				gfp_stripe_updates_sign_up: gfp_stripe_settings_page_vars.nonce
			};

			jQuery.post( ajaxurl, post_data, function ( response ) {

				spinner.destroy();

				if ( true == response.success ) {
					createButton.val( gfp_stripe_settings_page_vars.success_message );
					window.setTimeout( tb_remove, 3000 );
				}
				else {
					jQuery( '#gfp_stripe_sign_up_error_message' ).html( response.data['error_message'] );
					createButton.val( origVal );
				}

			} );
		}
		else {
			jQuery( '#gfp_stripe_sign_up_error_message' ).html( gfp_stripe_settings_page_vars.blank_email_message );
		}
	} );

} );

function gfAjaxSpinner( elem, imageSrc, inlineStyles ) {

	var imageSrc = typeof imageSrc == 'undefined' ? '/images/ajax-loader.gif' : imageSrc;
	var inlineStyles = typeof inlineStyles != 'undefined' ? inlineStyles : '';

	this.elem = elem;
	this.image = '<img class="gfspinner" src="' + imageSrc + '" style="' + inlineStyles + '" />';

	this.init = function () {
		this.spinner = jQuery( this.image );
		jQuery( this.elem ).after( this.spinner );
		return this;
	}

	this.destroy = function () {
		jQuery( this.spinner ).remove();
	}

	return this.init();
}

function addSuccessMessage() {
	jQuery( '.gfspinner' ).remove();
	jQuery( '#gfp_stripe_sign_up_submit' ).val( gfp_stripe_settings_page_vars.success_message );
	window.setTimeout( tb_remove, 8000 );
}