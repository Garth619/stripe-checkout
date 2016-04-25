jQuery( document ).ready( function () {
	var stripe_column_header = '<th scope="col" id="stripe" class="manage-column column-cb style="width:90px;">Stripe</th>';
	var form_list_table = jQuery( '#forms_form' ).find( 'table' );
	form_list_table.find( 'thead' ).find( 'tr' ).find( 'th' ).filter( ':last' ).after( stripe_column_header );
	form_list_table.find( 'tfoot' ).find( 'tr' ).find( 'th' ).filter( ':last' ).after( stripe_column_header );
	var forms_list = form_list_table.find( 'tbody' ).find( 'tr' );
	jQuery.each( forms_list, function () {
		var form_id = jQuery( this ).find( 'td.column-id' ).html();
		var has_feed = false;
		var mode = '';
		jQuery.each( stripe_form_list.stripe_feeds, function () {
			if ( this['form_id'] == form_id ) {
				has_feed = true;
				mode = this['form_settings']['mode'];
				return false;
			}
		} );
		if ( true === has_feed ) {
			if ( 'live' == mode ) {
				jQuery( this ).find( 'td' ).filter( ':last' ).after( '<td>Test<img class="gform_active_icon" src="' + stripe_form_list.live_img + '" style="cursor: pointer;vertical-align:middle;margin: 0px 5px;" alt="Live" title="Live" onclick="ToggleStripeMode( this, ' + form_id + ' );"  />Live</td>' );
			}
			else {
				jQuery( this ).find( 'td' ).filter( ':last' ).after( '<td>Test<img class="gform_active_icon" src="' + stripe_form_list.test_img + '" style="cursor: pointer;vertical-align:middle;margin: 0px 5px;" alt="Test" title="Test" onclick="ToggleStripeMode( this, ' + form_id + ' );"  />Live</td>' );
			}
		}
		else {
			jQuery( this ).find( 'td' ).filter( ':last' ).after( '<td></td>' );
		}
	} );
} );

function ToggleStripeMode( img, form_id ) {
	var is_live = img.src.indexOf( 'active1.png' ) >= 0;
	var toggle = '';
	if ( is_live ) {
		img.src = img.src.replace( 'active1.png', 'active0.png' );
		jQuery( img ).attr( 'title', stripe_form_list.test_text ).attr( 'alt', stripe_form_list.test_text );
		toggle = 'test';
	}
	else {
		img.src = img.src.replace( 'active0.png', 'active1.png' );
		jQuery( img ).attr( 'title', stripe_form_list.live_text ).attr( 'alt', stripe_form_list.live_text );
		toggle = 'live';
	}

	var post_data = { action: 'gfp_more_stripe_update_form_stripe_mode',
		gfp_more_stripe_update_form_stripe_mode: stripe_form_list.nonce,
		form_id: form_id,
		mode: toggle };

	jQuery.post( ajaxurl, post_data, function ( response ) {
		if ( '0' !== response ) {
			alert( stripe_form_list.update_mode_error_message );
			if ( 'live' === toggle ) {
				img.src = img.src.replace( 'active1.png', 'active0.png' );
				jQuery( img ).attr( 'title', stripe_form_list.test_text ).attr( 'alt', stripe_form_list.test_text );
			}
			else {
				img.src = img.src.replace( 'active0.png', 'active1.png' );
				jQuery( img ).attr( 'title', stripe_form_list.live_text ).attr( 'alt', stripe_form_list.live_text );
			}
		}
	} );

	return true;
}