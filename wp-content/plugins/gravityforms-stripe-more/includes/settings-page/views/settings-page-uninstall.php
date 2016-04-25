<?php
/**
 *
 */
?>
<form action="" method="post">
	<?php wp_nonce_field( 'more_stripe_uninstall', 'gfp_more_stripe_uninstall' ) ?>
	<div class="hr-divider"></div>

	<h3><?php _e( 'Uninstall (More) Stripe', 'gravityforms-stripe-more' ) ?></h3>

	<div
		class="delete-alert"><?php _e( 'Warning! This operation deletes ALL (More) Stripe Rules and saved Stripe customer data.', 'gravityforms-stripe-more' ) ?>
		<?php
		$more_stripe_uninstall_button = '<input type="submit" name="more_stripe_uninstall" value="' . __( 'Uninstall (More) Stripe', 'gravityforms-stripe-more' ) . '" class="button" onclick="return confirm(\'' . __( "Warning! ALL (More) Stripe Rules will be deleted. This cannot be undone. \'OK\' to delete, \'Cancel\' to stop", 'gravityforms-stripe-more' ) . '\');"/>';
		echo apply_filters( 'gfp_more_stripe_uninstall_button', $more_stripe_uninstall_button );
		?>
	</div>
</form>