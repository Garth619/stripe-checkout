<?php

/**
 * GFP_More_Stripe_API Class
 *
 * Old Stripe API class that simply points to the new PPP_Stripe_API library used in all press+ Stripe extensions.
 * Maintained here for backwards compatibility, so any implementations still using this class will not break.
 *
 * @since 1.9.2.1
 *
 */
class GFP_More_Stripe_API extends PPP_Stripe_API {

	/**
		 * Parse error and return a "pretty" error message to display to customers
		 *
		 * @since 1.9.2.1
		 *
		 * @param      $e
		 * @param bool $mode
		 *
		 * @return mixed|void
		 */
		public static function create_error_message( $e, $mode = false ) {

			$error_message = parent::create_error_message( $e, $mode );

			return apply_filters( 'gfp_stripe_error_message', $error_message, $e );

		}

}