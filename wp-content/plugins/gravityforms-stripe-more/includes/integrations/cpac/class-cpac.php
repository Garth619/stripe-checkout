<?php

/**
 * Class GFP_Stripe_CPAC
 *
 * Integrates with CodePress Admin Columns to display Stripe user information
 *
 * @since 1.8.2
 */
class GFP_Stripe_CPAC {

	public function __construct () {
		add_filter( 'cac/columns/custom/type=user', array( $this, 'cac_register_custom_columns' ) );
	}

	/**
	 * Register CPAC custom columns
	 *
	 * @since 1.8.2
	 *
	 * @param $columns
	 *
	 * @return array
	 */
	public function cac_register_custom_columns ( $columns ) {

		// Class name and absolute filepath of the custom column
		$columns[ 'CPAC_Column_User_Stripe_Customer_ID' ]   = trailingslashit( GFP_MORE_STRIPE_PATH ) . 'includes/integrations/cpac/class-cpac-column-user-stripe_customer_id.php';
		$columns[ 'CPAC_Column_User_Stripe_Cards' ]         = trailingslashit( GFP_MORE_STRIPE_PATH ) . 'includes/integrations/cpac/class-cpac-column-user-stripe_cards.php';
		$columns[ 'CPAC_Column_User_Stripe_Subscriptions' ] = trailingslashit( GFP_MORE_STRIPE_PATH ) . 'includes/integrations/cpac/class-cpac-column-user-stripe_subscriptions.php';

		return $columns;
	}
}