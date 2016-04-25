<?php

class GFP_Stripe_Convert_Customer_Info {

	private static $entry_id = 0;

	public static function run() {

		GFP_Stripe::log_debug( 'Converting Stripe customer WP user meta to new format' );

		$wp_users = get_users( array( 'meta_key' => 'stripe_subscription_entry_id', 'fields' => array( 'ID' ) ) );

		if ( ! empty( $wp_users ) ) {

			$need_to_be_converted = count( $wp_users );

			GFP_Stripe::log_debug( "There are {$need_to_be_converted} users that need to be converted" );

			foreach ( $wp_users as $user ) {

				GFP_Stripe::log_debug( "----User {$user->ID}" );

				self::$entry_id = $entry_id = get_user_meta( $user->ID, 'stripe_subscription_entry_id', true );

				if ( ! empty( $entry_id ) ) {

					GFP_Stripe::log_debug( "Stripe subscription entry ID is {$entry_id}" );

					$customer_id = GFP_More_Stripe_Customer_API::get_stripe_customer_id( $user->ID );

					if ( ! empty( $customer_id ) ) {

						GFP_Stripe::log_debug( 'User\'s information has already been converted. Removing old info' );

						delete_user_meta( $user->ID, 'stripe_subscription_entry_id' );

						break;
					}

					$stripe_info = gform_get_meta( $entry_id, 'stripe_subscription' );

					if ( ! empty( $stripe_info ) && ! empty( $stripe_info[ 'customer_id' ] ) ) {

						GFP_Stripe::log_debug( "Getting Stripe information for {$stripe_info['customer_id']}" );

						$customer = GFP_More_Stripe_Customer_API::get_customer_object_from_customer_id( null, $stripe_info[ 'customer_id' ] );

						if ( ! empty( $customer ) ) {

							self::convert_save_stripe_customer_info( $user->ID, $customer );

						} else {

							GFP_Stripe::log_debug( "A customer object does not exist in Stripe for this customer ID" );
						}
					} else {

						GFP_Stripe::log_debug( "This entry does not have any saved Stripe customer info" );

					}

				} else {

					GFP_Stripe::log_debug( "Unable to find entry ID" );

				}

			}

		}

	}

	/**
	 * @param $user_id
	 * @param $customer
	 *
	 * @return array
	 */
	private static function convert_save_cards( $user_id, $customer ) {

		$results_args = array();

		foreach ( $customer->sources[ 'data' ] as $source ) {

			if ( ( 'card' == $source[ 'object' ] ) ) {

				$card = $source;

				$make_default = true;

				if ( $card[ 'id' ] !== $customer[ 'default_source' ] ) {
					$make_default = false;
				}

				GFP_Stripe::log_debug( __( "CONVERTER: Saving card {$card['id']}, make_default = {$make_default}", 'gravityforms-stripe-more' ) );

				GFP_More_Stripe_Customer_API::save_new_card( $user_id, $card, $make_default );

				$results_args[ ] = $card[ 'id' ];
			}

		}

		return $results_args;
	}

	/**
	 * @param $user_id
	 * @param $customer
	 *
	 * @return array
	 */
	private static function convert_save_subscriptions( $user_id, $customer ) {

		$results_args = array();

		foreach ( $customer->subscriptions[ 'data' ] as $subscription ) {

			$currency_info = GFP_Stripe_Helper::get_currency_info( strtoupper( $subscription->plan[ 'currency' ] ) );

			$subscription_data = array(
				'id'           => $subscription[ 'id' ],
				'customer'     => $customer,
				'status'       => $subscription[ 'status' ],
				'start'        => $subscription[ 'current_period_start' ],
				'end'          => $subscription[ 'current_period_end' ],
				'next_payment' => null,
				'plan'         => array(
					'id'             => $subscription->plan[ 'id' ],
					'amount'         => ( 0 == $currency_info[ 'decimals' ] ) ? $subscription->plan[ 'amount' ] : ( $subscription->plan[ 'amount' ] / 100 ),
					'interval'       => $subscription->plan[ 'interval' ],
					'interval_count' => $subscription->plan[ 'interval_count' ],
					'name'           => $subscription->plan[ 'name' ]
				),
				'trial_end'    => $subscription[ 'trial_end' ],
				'trial_start'  => $subscription[ 'trial_start' ],
				'entry_id'     => empty( self::$entry_id ) ? null : self::$entry_id,
				'setup_fee'    => null
			);

			GFP_Stripe::log_debug( __( "CONVERTER: Saving subscription {$subscription['id']}", 'gravityforms-stripe-more' ) );

			GFP_More_Stripe_Customer_API::save_subscription( $user_id, $subscription_data );

			if ( ! empty( self::$entry_id ) ) {

				gform_update_meta( self::$entry_id, 'stripe_subscription', array( $subscription_data ) );
				gform_update_meta( self::$entry_id, 'gfp_stripe_customer_id', $customer[ 'id' ] );

			}

			$results_args[ ] = $subscription[ 'id' ];

		}

		return $results_args;
	}

	/**
	 * @param $user_id
	 * @param $customer
	 *
	 * @return array
	 */
	private static function convert_save_stripe_customer_info( $user_id, $customer ) {

		//save Stripe meta
		//-customer ID
		if ( 0 < $customer->subscriptions[ 'total_count' ] ) {

			$active_subscriptions = GFP_More_Stripe_Customer_API::get_active_subscriptions( $user_id );

			if ( empty( $active_subscriptions ) ) {

				$make_default_customer = true;

			}

		}

		GFP_Stripe::log_debug( __( "Saving customer ID {$customer['id']}", 'gravityforms-stripe-more' ) );

		$customer_ids[ 0 ] = $customer[ 'id' ];

		update_user_meta( $user_id, '_gfp_stripe_customer_id', $customer_ids );

		//-cards
		if ( 0 < $customer->sources[ 'total_count' ] ) {

			self::convert_save_cards( $user_id, $customer );

		}

		//-subscriptions
		if ( 0 < $customer->subscriptions[ 'total_count' ] ) {

			self::convert_save_subscriptions( $user_id, $customer );

			$save_currency = update_user_meta( $user_id, '_gfp_stripe_currency', strtoupper( $customer[ 'currency' ] ) );

		}

		//add user ID to Stripe customer meta
		$metadata[ 'wp_user_id' ] = $user_id;

		if ( ! empty( self::$entry_id ) ) {

			$entry = GFAPI::get_entry( self::$entry_id );

			if ( ! empty( $entry[ 'form_id' ] ) ) {

				$metadata[ 'gravity_form' ] = $entry[ 'form_id' ];

			}

			$metadata[ 'gravity_form_entry' ] = self::$entry_id;

		}

		GFP_More_Stripe_Customer_API::add_metadata_to_customer( '', $customer, $metadata );

		gform_update_meta( self::$entry_id, 'gfp_stripe_user_id', $user_id );

	}

}