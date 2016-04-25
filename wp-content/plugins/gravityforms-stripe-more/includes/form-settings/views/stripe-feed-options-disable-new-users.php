<?php
/**
 *
 */
?>
<li id="stripe_disable_new_users" <?php echo ( 'product' == rgars( $feed, 'meta/type' ) || 'subscription' == rgars( $feed, 'meta/type' ) ) ? '' : "style='display:none;'" ?>>
	<input type="checkbox" name="gfp_more_stripe_disable_new_users"
		   id="gfp_more_stripe_disable_new_users" <?php echo rgar( $feed['meta'], 'disable_new_users' ) ? "checked='checked'" : "value='1'" ?> />
	<label class="inline"
		   for="gfp_more_stripe_disable_new_users"><?php _e( "Disable saving Stripe customers as WP users for this form.", 'gravityforms-stripe-more' ) ?> <?php gform_tooltip( 'stripe_disable_new_users' ) ?></label>
</li>