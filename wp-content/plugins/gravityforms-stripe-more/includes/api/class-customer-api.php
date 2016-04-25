<?php
/** @package   GFP_More_Stripe_Customer_API
 * @copyright 2014 press+
 * @license   GPL-2.0+
 * @since     1.8.2
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * GFP_More_Stripe_Customer_API Class
 *
 * Retrieves and updates saved Stripe customer information
 *
 * @since 1.8.2
 *
 */
class GFP_More_Stripe_Customer_API {

	/**
	 * Get Stripe customer ID for user
	 *
	 * @since 1.8.2
	 *
	 * @param int  $user_id
	 * @param bool $all Whether to return all of the customer IDs for this user, from when you had to create multiple customers
	 *                  because Stripe didn't allow multiple cards or multiple anything per customer
	 *
	 * @return string|array Returns either a single customer ID (if there are multiple, the most recent) or an array of customer IDs sorted and indexed by their
	 *                      creation date (timestamp) in descending order
	 */
	public static function get_stripe_customer_id( $user_id, $all = false ) {

		GFP_Stripe::log_debug( "Retrieving Stripe customer ID for user: {$user_id}" );


		$customer_id = get_user_meta( $user_id, '_gfp_stripe_customer_id', true );

		if ( is_array( $customer_id ) ) {

			if ( 1 == count( $customer_id ) ) {

				$customer_id = $customer_id[ 0 ];

			} else {

				ksort( $customer_id, SORT_NUMERIC );

				if ( ! $all ) {
					$customer_id = array_slice( $customer_id, 0, 1 );
					$customer_id = $customer_id[ 0 ];
				}

			}

		}

		return $customer_id;
	}

	/**
	 * Get WordPress user ID for a Stripe customer
	 *
	 * @since 1.8.13.1
	 *
	 * @param string $customer_id
	 *
	 * @return string | null
	 */
	public static function get_user_id_from_customer_id( $customer_id ) {

		global $wpdb;

		GFP_Stripe::log_debug( "Getting user_id for {$customer_id}" );

		$user_id = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = '_gfp_stripe_customer_id' AND meta_value = %s", $customer_id ) );

		if ( empty( $user_id ) ) {
			GFP_Stripe::log_debug( 'Unable to find user ID for this customer' );
		}

		return $user_id;
	}

	/**
	 * Get WordPress user ID from a Stripe charge ID
	 *
	 * @since 1.8.13.1
	 *
	 * @param string $charge_id
	 *
	 * @return null|string
	 */
	public static function get_user_id_from_charge_id( $charge_id ) {

		$user_id = '';

		$mode = GFP_Stripe_Helper::get_global_stripe_mode();

		$api_key = apply_filters( 'gfp_more_stripe_api_key', GFP_Stripe::get_api_key( 'secret', $mode ), 'get_user_id_from_charge_id' );

		GFP_Stripe::log_debug( "Getting user_id for {$charge_id} in {$mode} mode" );

		$charge = PPP_Stripe_API::retrieve( 'charge', $api_key, $charge_id );

		if ( ! is_object( $charge ) || ! is_a( $charge, 'PPP\\Stripe\\Charge' ) ) {

			$mode = ( 'live' == $mode ) ? 'test' : 'live';

			GFP_Stripe::log_debug( "Invalid charge, so let's try in {$mode} mode" );

			$api_key = apply_filters( 'gfp_more_stripe_api_key', GFP_Stripe::get_api_key( 'secret', $mode ), 'get_user_id_from_charge_id' );

			$charge = PPP_Stripe_API::retrieve( 'charge', $api_key, $charge_id );

		}

		if ( is_object( $charge ) && is_a( $charge, 'PPP\\Stripe\\Charge' ) ) {

			GFP_Stripe::log_debug( "Cool. Found the charge, let's see if it has a user ID in the customer metadata" );

			$user_id = $charge->customer[ 'metadata' ][ 'wp_user_id' ];

			if ( empty( $user_id ) ) {

				GFP_Stripe::log_debug( "{$charge->customer['id']} did not have a user ID attached as metadata. Searching WP usermeta for customer ID..." );

				$user_id = GFP_More_Stripe_Customer_API::get_user_id_from_customer_id( $charge->customer[ 'id' ] );

				if ( ! empty( $user_id ) ) {
					GFP_More_Stripe_Customer_API::add_metadata_to_customer( $user_id, $charge->customer, array( 'wp_user_id' => $user_id ) );
				}

			}

		}

		GFP_Stripe::log_debug( "User ID {$user_id}" );

		return $user_id;
	}

	/**
	 * Get user's active subscription IDs
	 *
	 * @since 1.8.2
	 *
	 * @param int $user_id
	 *
	 * @return array of active subscription IDs
	 */
	public static function get_active_subscriptions( $user_id ) {

		$active_subscriptions = get_user_meta( $user_id, '_gfp_stripe_subscription_active', true );

		return $active_subscriptions;
	}

	/**
	 * Get saved subscription info
	 *
	 * @since 1.8.2
	 *
	 * @param int    $user_id
	 * @param string $subscription_id
	 *
	 * @return array {
	 * @type string  $entry_id
	 * @type string  $status
	 * @type string  $start
	 * @type string  $end
	 * @type array   $next_payment {
	 * @type int     $amount
	 * @type string  $date
	 * }
	 * @type array   $plan         {
	 * @type string  $id
	 * @type int     $amount
	 * @type string  $interval
	 * @type int     $interval_count
	 * @type string  $name
	 * }
	 * @type string  $trial_start
	 * @type string  $trial_end
	 * @type int     $setup_fee
	 * @type int     $end_after
	 *                }
	 */
	public static function get_subscription_info( $user_id, $subscription_id, $for_display = false ) {

		$subscription = get_user_meta( $user_id, '_gfp_stripe_subscription_' . $subscription_id, true );

		if ( ! empty( $subscription ) ) {

			$subscription[ 'start' ]                  = ( $for_display ) ? date_i18n( 'm/d/Y', $subscription[ 'start' ], true ) : $subscription[ 'start' ];
			$subscription[ 'end' ]                    = ( $for_display && ! empty( $subscription[ 'end' ] ) ) ? date_i18n( 'm/d/Y', $subscription[ 'end' ], true ) : $subscription[ 'end' ];
			$subscription[ 'next_payment' ][ 'date' ] = ( $for_display ) ? date_i18n( 'm/d/Y', $subscription[ 'next_payment' ][ 'date' ], true ) : $subscription[ 'next_payment' ][ 'date' ];

			if ( ! empty( $subscription[ 'trial_start' ] ) ) {
				$subscription[ 'trial_start' ] = ( $for_display ) ? date_i18n( 'm/d/Y', $subscription[ 'trial_start' ], true ) : $subscription[ 'trial_start' ];
			}

			if ( ! empty( $subscription[ 'trial_end' ] ) ) {
				$subscription[ 'trial_end' ] = ( $for_display ) ? date_i18n( 'm/d/Y', $subscription[ 'trial_end' ], true ) : $subscription[ 'trial_end' ];
			}

		}

		return $subscription;
	}

	/**
	 * Get user's currency
	 *
	 * @since 1.8.2
	 *
	 * @param $user_id
	 *
	 * @return string 3-digit ISO currency code
	 */
	public static function get_stripe_customer_currency( $user_id ) {

		$currency = get_user_meta( $user_id, '_gfp_stripe_currency', true );

		return $currency;
	}

	/**
	 * Get user's default card
	 *
	 * @since 1.8.2
	 *
	 * @param $user_id
	 *
	 * @return string
	 */
	public static function get_default_card( $user_id ) {

		$default_card_id = get_user_meta( $user_id, '_gfp_stripe_card_default', true );

		return $default_card_id;
	}

	/**
	 * Get user's saved cards
	 *
	 * @since 1.8.2
	 *
	 * @param       $user_id
	 *
	 * @return array of $cards, with each card containing {
	 * @type string $id
	 * @type string $last4 last 4 digits of this card number
	 * @type string $type  or $brand    card brand e.g. Visa, MasterCard. Depending on Stripe API version
	 * @type string $fingerprint
	 * }
	 */
	public static function get_stripe_customer_cards( $user_id ) {

		$cards = get_user_meta( $user_id, '_gfp_stripe_card', false );

		return $cards;
	}

	/**
	 * Return whether or not the card ID is the customer's default card
	 *
	 * @since 1.8.17.1
	 *
	 * @param $user_id
	 * @param $card_id
	 *
	 * @return bool
	 */
	public static function is_default_card( $user_id, $card_id ) {

		$is_default_card = false;

		$default_card = self::get_default_card( $user_id );

		if ( $default_card == $card_id ) {
			$is_default_card = true;
		}

		return $is_default_card;
	}

	/**
	 * Add metadata to a Stripe customer
	 *
	 * Can either pass a user ID, PPP\Stripe\Customer object, or Stripe customer ID
	 * - Will make sure that current metadata does not get overwritten, unless you use the same key.
	 * - Using the same key will overwrite current key value.
	 * - Assumes you are using string keys and not numeric keys
	 *
	 * If there are multiple customer IDs for this user from earlier Stripe days, the newest customer ID will be used
	 *
	 * @since 1.8.13.1
	 *
	 * @param string                     $user_id
	 * @param PPP\Stripe\Customer|string $customer
	 * @param array                      $metadata
	 *
	 * @return bool
	 */
	public static function add_metadata_to_customer( $user_id = '', $customer = null, $metadata ) {

		$metadata_added = false;

		if ( ! is_array( $metadata ) ) {

			GFP_Stripe::log_error( "Metadata is not an array — unable to add to Stripe customer. metadata: {$metadata}" );

			return $metadata_added;
		}

		$stripe_customer = null;

		if ( ! empty( $customer ) && is_object( $customer ) && is_a( $customer, 'PPP\\Stripe\\Customer' ) ) {

			$stripe_customer = $customer;
			$api_key         = apply_filters( 'gfp_more_stripe_api_key', GFP_Stripe::get_api_key( 'secret', PPP_Stripe_API::get_object_mode( $stripe_customer ) ), 'add_metadata_to_customer' );

		} else {

			$customer_id = '';

		}

		if ( empty( $stripe_customer ) && ! empty( $customer ) && is_string( $customer ) ) {

			$customer_id = $customer;

		}

		if ( empty( $stripe_customer ) && empty( $customer_id ) && ! empty( $user_id ) && is_int( $user_id ) ) {

			$customer_id = self::get_stripe_customer_id( $user_id );

		}

		if ( empty( $stripe_customer ) && ! empty( $customer_id ) ) {

			$customer = self::get_customer_object_from_customer_id( null, $customer );

			if ( ! empty( $customer ) ) {

				$stripe_customer = $customer;
				$api_key         = apply_filters( 'gfp_more_stripe_api_key', GFP_Stripe::get_api_key( 'secret', PPP_Stripe_API::get_object_mode( $stripe_customer ) ), 'add_metadata_to_customer' );

			}

		}

		if ( ! empty( $stripe_customer ) ) {

			GFP_Stripe::log_debug( "Adding metadata to {$customer['id']}" );

			$updated_metadata = PPP_Stripe_API::create_updated_metadata_array( $customer, $metadata );

			$customer = PPP_Stripe_API::update_customer( $api_key, $stripe_customer, array( 'metadata' => $updated_metadata ) );

			if ( is_object( $customer ) && is_a( $customer, 'PPP\\Stripe\\Customer' ) ) {
				$metadata_added = true;
			}
		}

		return $metadata_added;

	}

	/**
	 * Save a new card to Stripe customer's WP user meta
	 *
	 * @since 1.8.2
	 *
	 * @param int  $user_id
	 * @param PPP\\Stripe\Card $card
	 * @param bool $make_default
	 */
	public static function save_new_card( $user_id, $card, $make_default = true ) {

		add_user_meta( $user_id, '_gfp_stripe_card', array(
			'id'          => $card[ 'id' ],
			'last4'       => $card[ 'last4' ],
			'brand'       => $card[ 'brand' ],
			'fingerprint' => $card[ 'fingerprint' ]
		) );

		if ( $make_default ) {

			self::set_user_default_card( $user_id, $card[ 'id' ] );

		}
	}

	/**
	 * Remove a customer's saved card
	 *
	 * Note that this does not delete it from Stripe — it simply removes it from the WordPress user's saved data
	 *
	 * @since 1.8.13.5
	 *
	 * @param $user_id
	 * @param $card_id
	 */
	public static function remove_card( $user_id, $card_id ) {

		$cards = GFP_More_Stripe_Customer_API::get_stripe_customer_cards( $user_id );

		foreach ( $cards as $card ) {

			if ( $card[ 'id' ] == $card_id ) {

				GFP_Stripe::log_debug( __( "Removing user {$user_id}'s {$card_id}", 'gravityforms-stripe-more' ) );

				delete_user_meta( $user_id, '_gfp_stripe_card', $card );

				break;

			}

		}

		if ( GFP_More_Stripe_Customer_API::get_default_card( $user_id ) == $card_id ) {

			GFP_More_Stripe_Customer_API::remove_user_default_card( $user_id );
			//TODO set most recently added card as the new default

		}

	}

	/**
	 * Remove all of a user's saved cards
	 *
	 * Note that this does not delete them from Stripe — it simply removes them from the WordPress user's saved data
	 *
	 * @since 1.8.13.4
	 *
	 * @param $user_id
	 */
	public static function remove_all_cards( $user_id ) {

		GFP_Stripe::log_debug( __( "Removing user {$user_id}'s saved cards", 'gravityforms-stripe-more' ) );

		delete_user_meta( $user_id, '_gfp_stripe_card' );

		GFP_More_Stripe_Customer_API::remove_user_default_card( $user_id );

	}

	/**
	 * Set a Stripe card ID as the default
	 *
	 * @since 1.8.2.1
	 *
	 * @param int    $user_id
	 * @param string $card_id
	 */
	public static function set_user_default_card( $user_id, $card_id ) {

		update_user_meta( $user_id, '_gfp_stripe_card_default', $card_id );

	}

	/**
	 * Remove the user's default card
	 *
	 * Likely used after deleting the card or when needing to reset the saved Stripe data
	 *
	 * @since 1.8.13.5
	 *
	 * @param $user_id
	 */
	public static function remove_user_default_card( $user_id ) {

		GFP_Stripe::log_debug( __( "Removing user {$user_id}'s default card", 'gravityforms-stripe-more' ) );

		delete_user_meta( $user_id, '_gfp_stripe_card_default' );

	}

	/**
	 * Adds a subscription ID to the list of active subscriptions for a user
	 *
	 * @since 1.8.2
	 *
	 * @param int    $user_id
	 * @param string $subscription_id
	 */
	public static function add_active_subscription( $user_id, $subscription_id ) {

		$active_subscriptions = GFP_More_Stripe_Customer_API::get_active_subscriptions( $user_id );
		$already_active       = false;

		if ( ! empty( $active_subscriptions ) ) {

			foreach ( $active_subscriptions as $active_subscription_id ) {

				if ( $active_subscription_id == $subscription_id ) {
					$already_active = true;
				}

			}

		}

		if ( ! $already_active ) {

			GFP_Stripe::log_debug( __( "Adding active subscription {$subscription_id}", 'gravityforms-stripe-more' ) );

			$active_subscriptions[ ] = $subscription_id;

			update_user_meta( $user_id, '_gfp_stripe_subscription_active', $active_subscriptions );

		}

	}

	/**
	 * Removes a subscription ID from the user's list of active Stripe subscriptions
	 *
	 * @since 1.8.2
	 *
	 * @param int    $user_id
	 * @param string $subscription_id
	 */
	public static function remove_active_subscription( $user_id, $subscription_id ) {

		$active_subscriptions = GFP_More_Stripe_Customer_API::get_active_subscriptions( $user_id );

		if ( ! empty( $active_subscriptions ) ) {

			foreach ( $active_subscriptions as $key => $subscription ) {

				if ( $subscription == $subscription_id ) {
					unset( $active_subscriptions[ $key ] );
				}

			}

			$active_subscriptions = array_values( $active_subscriptions );

			update_user_meta( $user_id, '_gfp_stripe_subscription_active', $active_subscriptions );

		}

	}

	/**
	 * Saves Stripe subscription information to WP user meta
	 *
	 *
	 *
	 * @since 1.8.2
	 *
	 * @param int   $user_id
	 * @param array $subscription
	 */
	public static function save_subscription( $user_id, $subscription ) {

		add_user_meta( $user_id, '_gfp_stripe_subscription_' . $subscription[ 'id' ], array(
			'id'           => $subscription[ 'id' ],
			'entry_id'     => $subscription[ 'entry_id' ],
			'status'       => $subscription[ 'status' ],
			'start'        => $subscription[ 'start' ],
			'end'          => $subscription[ 'end' ],
			'next_payment' => $subscription[ 'next_payment' ],
			'plan'         => $subscription[ 'plan' ],
			'trial_start'  => $subscription[ 'trial_start' ],
			'trial_end'    => $subscription[ 'trial_end' ],
			'setup_fee'    => isset( $subscription[ 'setup_fee' ] ) ? $subscription[ 'setup_fee' ] : null,
			'end_after'    => $subscription[ 'end_after' ],
			'quantity'     => $subscription[ 'quantity' ]
		), true );

		self::add_active_subscription( $user_id, $subscription[ 'id' ] );

	}

	/**
	 * Get full Stripe customer object for a Stripe customer ID
	 *
	 * @since 1.8.13.1
	 *
	 * @param string $api_key
	 * @param string $customer_id
	 *
	 * @return PPP\Stripe\Customer | null
	 */
	public static function get_customer_object_from_customer_id( $api_key = null, $customer_id ) {

		if ( empty( $api_key ) ) {

			$mode = GFP_Stripe_Helper::get_global_stripe_mode();

			$api_key = apply_filters( 'gfp_more_stripe_api_key', GFP_Stripe::get_api_key( 'secret', $mode ), 'get_customer_object_from_customer_id' );

			$customer_object = PPP_Stripe_API::retrieve( 'customer', $api_key, $customer_id );

			if ( ! is_object( $customer_object ) || ! is_a( $customer_object, 'PPP\\Stripe\\Customer' ) ) {

				$mode    = ( 'live' == $mode ) ? 'test' : 'live';
				$api_key = apply_filters( 'gfp_more_stripe_api_key', GFP_Stripe::get_api_key( 'secret', $mode ), 'get_customer_object_from_customer_id' );

				$customer_object = PPP_Stripe_API::retrieve( 'customer', $api_key, $customer_id );

			}

		} else {

			$customer_object = PPP_Stripe_API::retrieve( 'customer', $api_key, $customer_id );

		}

		if ( ! is_object( $customer_object ) || ! is_a( $customer_object, 'PPP\\Stripe\\Customer' ) ) {

			$customer_object = null;

		}

		return $customer_object;
	}

}