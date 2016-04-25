/**
 *
 */
jQuery( document ).ready( function ( jQuery ) {
	if ( typeof GFUser !== 'undefined' ) {
		for ( var key = 0; key < GFUser.meta_names.length; key++ ) {
			var meta = GFUser.meta_names[key].name;
			if ( -1 != meta.indexOf( '_gfp_stripe_' ) ) {
				GFUser.meta_names.splice( key, 1 );
				key -= 1;
			}
		}
	}
} );