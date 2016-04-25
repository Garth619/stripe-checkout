<?php
/**
 *
 */
?>
<li id="stripe_auto_login" <?php echo ( 'product' == rgars( $feed, 'meta/type' ) || 'subscription' == rgars( $feed, 'meta/type' ) ) ? '' : "style='display:none;'" ?>>
	<input type="checkbox" name="gfp_more_stripe_auto_login"
		   id="gfp_more_stripe_auto_login" <?php echo rgar( $feed['meta'], 'auto_login' ) ? "checked='checked'" : "value='1'" ?> />
	<label class="inline"
		   for="gfp_more_stripe_auto_login"><?php _e( 'Automatically log in new user.', 'gravityforms-stripe-more' ); ?> <?php gform_tooltip( 'stripe_auto_login' ) ?></label>
</li>