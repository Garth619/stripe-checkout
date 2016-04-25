<?php
/** @package   GFP_Stripe_Helper
 * @copyright 2014 gravity+
 * @license   GPL-2.0+
 * @since     1.8.13.1
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * GFP_Stripe_Helper Class
 *
 * Common functions
 *
 * @since 1.8.13.1
 *
 */
class GFP_Stripe_Helper {

	/**
	 * Get the current mode of a Stripe form
	 *
	 * Remember: this is the *current* mode of the form
	 *
	 * @since 1.8.13.1
	 *
	 * @param string $form_id
	 *
	 * @return string | null
	 */
	public static function get_form_stripe_mode( $form_id ) {

		$stripe_form_meta = GFP_Stripe_Data::get_stripe_form_meta( $form_id );

		return $stripe_form_meta[ 'form_settings' ][ 'mode' ];
	}

	public static function get_global_stripe_mode() {

		$settings = get_option( 'gfp_stripe_settings' );

		return rgar( $settings, 'mode' );
	}

	public static function get_entry_mode( $entry_id ) {

		$mode = '';

		$transaction = GFP_Stripe_Data::get_transaction_by( 'entry', $entry_id );

		if ( ! empty( $transaction ) ) {
			$mode = $transaction[ 'mode' ];
		}

		return $mode;
	}

	public static function create_wp_user( $first_name, $last_name, $user_login, $user_email = false ) {

		$user_args = array(
			'role'       => apply_filters( 'gfp_more_stripe_user_role', 'stripe_customer' ),
			'user_pass'  => wp_generate_password(),
			'user_login' => $user_login,
			'first_name' => $first_name,
			'last_name'  => $last_name
		);

		if ( $user_email ) {
			$user_args[ 'user_email' ] = $user_email;
		}

		GFP_Stripe::log_debug( sprintf( __( 'Inserting new user — user_login: %s, first_name: %s, last_name: %s, user_email: %s', 'gravityforms-stripe-more' ), $user_login, $first_name, $last_name, $user_email ) );

		$user_id = wp_insert_user( $user_args );

		if ( is_wp_error( $user_id ) ) {

			$user_id = ( 'existing_user_login' == $user_id->get_error_code() ) ? username_exists( $user_login ) : $user_id;

		} else {

			$transaction_response = GFP_Stripe::get_transaction_response();

			if ( ! empty( $transaction_response ) ) {

				$transaction_response[ 'user_id' ]       = $user_id;
				$transaction_response[ 'user_password' ] = $user_args[ 'user_pass' ];

				GFP_Stripe::set_transaction_response( $transaction_response );

			}

		}

		return $user_id;
	}

	public static function get_currency_info( $currency_code = '' ) {

		if ( ! class_exists( 'RGCurrency' ) ) {
			require_once( GFCommon::get_base_path() . '/currency.php' );
		}

		if ( empty( $currency_code ) ) {
			$currency_code = GFCommon::get_currency();
		}

		return RGCurrency::get_currency( $currency_code );
	}


	public static function add_note( $entry_id, $note, $note_type = null ) {

		GFP_Stripe::log_debug( 'Adding entry note' );

		$user_id   = 0;
		$user_name = 'Stripe';

		GFFormsModel::add_note( $entry_id, $user_id, $user_name, $note, $note_type );
	}

	public static function get_early_access() {

		$early_access = false;

		$settings = get_option( 'gfp_stripe_settings' );

		if ( ! empty( $settings ) && ! empty( $settings[ 'enable_early_access' ] ) ) {
			$early_access = true;
		}

		return $early_access;
	}

	public static function is_hidden_field_type( $form, $field_id ) {

		$is_hidden_field_type = false;

		$field = RGFormsModel::get_field( $form, $field_id );

		if ( 'hidden' == RGFormsModel::get_input_type( $field ) ) {
			$is_hidden_field_type = true;
		}

		return $is_hidden_field_type;
	}

	public static function get_customer_payment_method_list( $user_id ) {

		$payment_methods = array();

		$default_card = GFP_More_Stripe_Customer_API::get_default_card( $user_id );

		if ( ! empty( $default_card ) ) {

			$cards = GFP_More_Stripe_Customer_API::get_stripe_customer_cards( $user_id );

			foreach ( $cards as $card ) {

				$payment_methods[ ] = array_merge( $card, array(
					'key'     => $card[ 'id' ],
					'label'   => ( empty( $card[ 'type' ] ) ? $card[ 'brand' ] : $card[ 'type' ] ) . ' (' . __( 'ending in ', 'gravityforms-stripe-more' ) . $card[ 'last4' ] . ')',
					'default' => GFP_More_Stripe_Customer_API::is_default_card( $user_id, $card[ 'id' ] )
				) );

			}

		}

		return $payment_methods;
	}

	public static function get_card_brand_icon( $brand ) {

		$icon = '';

		switch ( $brand ) {
			case 'Visa':
				$icon = '<i class="fa fa-cc-visa"></i>';
				break;
			case 'MasterCard':
				$icon = '<i class="fa fa-cc-mastercard"></i>';
				break;
			case 'American Express':
				$icon = '<i class="fa fa-cc-amex"></i>';
				break;
			case 'Discover':
				$icon = '<i class="fa fa-cc-discover"></i>';
				break;
			default:
				$icon = '<i class="fa fa-credit-card"></i>';
				break;
		}

		return $icon;
	}

	/**
	 * @param $customer_id
	 *
	 * @return string
	 */
	public static function get_legacy_entry_id_from_customer_id( $customer_id ) {

		GFP_Stripe::log_debug( "Looking for entry for {$customer_id} in old subscription meta" );

		$entry_id = '';

		$subscription_entries = self::get_legacy_subscription_meta();

		if ( ! empty( $subscription_entries ) ) {

			foreach ( $subscription_entries as $entry ) {

				if ( $customer_id == $entry[ 'meta_value' ][ 'customer_id' ] ) {

					$entry_id = $entry[ 'lead_id' ];

					GFP_Stripe::log_debug( "Found entry {$entry_id}" );

				}
			}

		}

		return $entry_id;
	}

	public static function get_legacy_subscription_meta() {

		GFP_Stripe::log_debug( "Retrieving all entries that have a saved Stripe subscription" );

		global $wpdb;

		$lead_meta_table = RGFormsModel::get_lead_meta_table_name();
		$entry_id        = 0;

		$sql     = "SELECT lead_id, meta_value
									            FROM {$lead_meta_table}
									            WHERE meta_key = 'Stripe_subscription'";
		$results = $wpdb->get_results( $sql, ARRAY_A );

		if ( ! empty( $results ) ) {

			GFP_Stripe::log_debug( "There are " . count( $results ) . " entries" );

			foreach ( $results as $key => $result ) {
				$results[ $key ][ 'meta_value' ] = maybe_unserialize( $result[ 'meta_value' ] );
			}

		}

		return $results;
	}

	/**
	 * See if a transaction already exists in the transaction table
	 *
	 * If given multiple transaction IDs, this function will return the first match
	 *
	 * @since    1.9.1.1
	 *
	 * @param array $transaction_ids
	 *
	 * @return array|null
	 *
	 */
	public static function check_for_transaction( $transaction_ids ) {

		GFP_Stripe::log_debug( 'Checking to see if one of the following transactions already exist in the transaction table: ' . print_r( $transaction_ids, true ) );

		$transaction = null;

		foreach ( $transaction_ids as $transaction_id ) {

			$transaction = GFP_Stripe_Data::get_transaction_by( 'transaction_id', $transaction_id );

			if ( ! empty( $transaction ) ) {

				GFP_Stripe::log_debug( "{$transaction_id} exists" );

				break;
			}

		}

		return $transaction;
	}

	/**
	 * @see   PPP_Stripe_Event::parse_invoice_line_items
	 *
	 * @since 1.9.2.2
	 *
	 * @param $invoicelineitems
	 *
	 * @return array
	 */
	public static function parse_invoice_line_items( $invoicelineitems ) {

		$line_items = array();

		foreach ( $invoicelineitems as $invoice_line_item ) {

			$currency              = strtoupper( $invoice_line_item[ 'currency' ] );
			$zero_decimal_currency = PPP_Stripe_API::is_zero_decimal_currency( $currency );

			if ( 'invoiceitem' == $invoice_line_item[ 'type' ] ) {

				$line_items[ ] = array(
					'description' => $invoice_line_item[ 'description' ],
					'quantity'    => 1,
					'period'      => date_i18n( 'm/d/Y', $invoice_line_item[ 'period' ][ 'start' ], true ),
					'amount'      => ( $zero_decimal_currency ) ? $invoice_line_item[ 'amount' ] : $invoice_line_item[ 'amount' ] / 100,
					'currency'    => $currency
				);

			} else if ( 'subscription' == $invoice_line_item[ 'type' ] ) {

				$line_items[ ] = array(
					'description' => 'Subscription to ' . $invoice_line_item->plan[ 'name' ],
					'quantity'    => $invoice_line_item[ 'quantity' ],
					'period'      => date_i18n( 'm/d/Y', $invoice_line_item[ 'period' ][ 'start' ], true ) . ' - ' . date_i18n( 'm/d/Y', $invoice_line_item[ 'period' ][ 'end' ], true ),
					'amount'      => ( $zero_decimal_currency ) ? $invoice_line_item[ 'amount' ] : $invoice_line_item[ 'amount' ] / 100,
					'currency'    => $currency
				);

			}

		}

		return $line_items;

	}

}