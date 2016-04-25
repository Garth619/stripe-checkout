function DeleteStripeFeed( id ) {
	jQuery( '#action' ).val( 'delete' );
	jQuery( '#action_argument' ).val( id );
	jQuery( '#stripe_feeds_list_form' )[0].submit();
}

function ToggleStripeFeedActive( img, feed_id, form_id ) {
	var is_active = img.src.indexOf( 'active1.png' ) >= 0;
	var toggle = '';
	if ( is_active ) {
		img.src = img.src.replace( 'active1.png', 'active0.png' );
		jQuery( img ).attr( 'title', stripe_form_settings.inactive_text ).attr( 'alt', stripe_form_settings.inactive_text );
		toggle = 'inactive';
	}
	else {
		img.src = img.src.replace( 'active0.png', 'active1.png' );
		jQuery( img ).attr( 'title', stripe_form_settings.active_text ).attr( 'alt', stripe_form_settings.active_text );
		toggle = 'active';
	}

	var post_data = { action: 'gfp_stripe_update_feed_active',
		gfp_stripe_update_feed_active: stripe_form_settings.nonce,
		feed_id: feed_id,
		form_id: form_id,
		is_active: is_active ? 0 : 1 };

	jQuery.post( ajaxurl, post_data, function ( response ) {
		if ( '0' !== response ) {
			alert( stripe_form_settings.update_feed_error_message );
			if ( 'active' === toggle ) {
				img.src = img.src.replace( 'active1.png', 'active0.png' );
				jQuery( img ).attr( 'title', stripe_form_settings.inactive_text ).attr( 'alt', stripe_form_settings.inactive_text );
			}
			else {
				img.src = img.src.replace( 'active0.png', 'active1.png' );
				jQuery( img ).attr( 'title', stripe_form_settings.active_text ).attr( 'alt', stripe_form_settings.active_text );
			}
		}
	} );

	return true;
}