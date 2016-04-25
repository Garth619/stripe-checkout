<?php
/** @package   PPP_Stripe_Event
 * @copyright 2014 press+
 * @license   GPL-2.0+
 * @since     1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * PPP_Stripe_Event Class
 *
 * Processes Stripe events
 *
 * @since 1.0.0
 *
 */
abstract class PPP_Stripe_Event {

	protected $plugin_slug = '';

	protected $event = null;

	protected $event_object = null;

	protected $logger = null;

	protected $api_key = '';

	public function __construct( $args ) {

		$this->logger      = $args[ 'logger' ];
		$this->plugin_slug = $args[ 'plugin_slug' ];

	}

	final protected function get_mode( $event ) {

		return ( $event[ 'livemode' ] ) ? 'live' : 'test';

	}

	protected function validate_event( $api_key, $event ) {

		$this->logger->log->debug( "Validating Stripe event..." );
		$valid_event = false;

		$valid_event = PPP_Stripe_API::retrieve( 'event', $api_key, $event[ 'id' ] );

		if ( ! is_object( $valid_event ) ) {

			$this->logger->log->error( 'ERROR: event could not be verified by Stripe. Aborting.' );
			$valid_event = false;

		}

		return $valid_event;
	}

	final public function process_event( $endpoint, $api_key = false ) {

		$this->logger->log->debug( "Stripe {$endpoint} received. Starting to process..." );

		$body = @file_get_contents( 'php://input' );
		$this->logger->log->debug( print_r( $body, true ) );
		$event = json_decode( $body, true );

		$this->api_key = $api_key = apply_filters( 'ppp_stripe_event_api_key', $api_key, $this->plugin_slug, 'process_event', array( 'event' => $event ) );
		$event   = $this->validate_event( $api_key, $event );

		if ( ! $event ) {
			return;
		}

		$this->event = $event;
		$this->logger->log->debug( $event[ 'type' ] . ' event successfully verified by Stripe' );

		$cancel = $this->cancel_event_processing();

		if ( $cancel ) {
			$this->logger->log->debug( 'Stripe event processing canceled by the ppp_stripe_pre_process_event filter. Aborting.' );

			return;
		}

		$this->event_object = $object = $event->data->object;
		if ( method_exists( $this, "do_{$object['object']}_event" ) && is_callable( array(
			                                                                            $this,
			                                                                            "do_{$object['object']}_event"
		                                                                            ) )
		) {
			call_user_func( array( $this, "do_{$object['object']}_event" ) );
		}

		$sanitized_event_type = str_replace( '.', '_', $event[ 'type' ] );

		if ( has_action( 'ppp_stripe_process_event' ) || has_action( "ppp_stripe_process_event_{$sanitized_event_type}" ) ) {

			$human_event = $this->get_human_event();

			$this->logger->log->debug( 'Before ppp_stripe_event.' );

			do_action( 'ppp_stripe_process_event', $this->plugin_slug, $human_event );
			do_action( "ppp_stripe_process_event_{$sanitized_event_type}", $this->plugin_slug, $human_event );

			$this->logger->log->debug( 'After ppp_stripe_event.' );

		}

		$this->logger->log->debug( 'Before ppp_stripe_post_process_event.' );

		do_action( 'ppp_stripe_post_process_event', $this->plugin_slug, $event );

		$this->logger->log->debug( 'Stripe event processing complete.' );
	}

	final private function cancel_event_processing() {

		$this->logger->log->debug( 'Before ppp_stripe_pre_event.' );

		$cancel = apply_filters( 'ppp_stripe_pre_process_event', false, $this->plugin_slug, $this->event );

		if ( 'ping' == $this->event[ 'type' ] ) {
			$cancel = true;
		}

		return $cancel;
	}

	//------------------------------------------------------
	//------------- EVENTS ------------------------
	//------------------------------------------------------

	protected function do_account_event() {
	}

	protected function do_application_event() {
	}

	protected function do_application_fee_event() {
	}

	protected function do_balance_event() {
	}

	protected function do_charge_event() {
	}

	protected function do_dispute_event() {
	}

	protected function do_customer_event() {
	}

	protected function do_card_event() {
	}

	protected function do_subscription_event() {
	}

	protected function do_discount_event() {
	}

	protected function do_invoice_event() {
	}

	protected function do_invoiceitem_event() {
	}

	protected function do_plan_event() {
	}

	protected function do_coupon_event() {
	}

	protected function do_transfer_event() {
	}

	//------------------------------------------------------
	//------------- HUMAN TRANSLATION ------------------------
	//------------------------------------------------------

	final private function get_human_event() {

		$parsed_event                 = $this->parse_event( $this->event );
		$parsed_event[ 'event_type' ] = $this->event[ 'type' ];

		if ( ! empty( $this->event[ 'user_id' ] ) ) {
			$parsed_event[ 'stripe_user_account_id' ] = $this->event[ 'user_id' ];
		}

		return $parsed_event;
	}

	final public function parse_event( $event ) {

		$object     = $event->data->object;
		$event_name = explode( '.', $event[ 'type' ] );

		if ( 3 == count( $event_name ) ) {
			$resource = $event_name[ 1 ];
			$action   = $event_name[ 2 ];
		} else {
			$resource = $event_name[ 0 ];
			$action   = $event_name[ 1 ];
		}


		$event_information = call_user_func_array( array( $this, "parse_{$resource}" ), array( $object ) );

		if ( 'updated' == $action ) {

			$updated_attributes = $event->data->previous_attributes->keys();

			foreach ( $updated_attributes as $attribute ) {

				$prev_value = $event->data->previous_attributes[ $attribute ];

				if ( ! empty( $object->$attribute ) ) {
					$new_value = $object->$attribute;
				} else if ( ! empty( $object[ $attribute ] ) ) {
					$new_value = $object[ $attribute ];
				} else {
					$new_value = '';
				}

				$event_information[ 'updates' ][ $attribute ] = array( $prev_value, $new_value );

			}

		}

		return $event_information;
	}

	public function parse_account( $account ) {
		return $account;
	}

	public function parse_application( $application ) {
		return $application;
	}

	public function parse_application_fee( $application_fee ) {
		return $application_fee;
	}

	public function parse_balance( $balance ) {
		return $balance;
	}

	/**
	 * Parse the charge object and return helpful, human-readable information for this charge
	 *
	 * @param object $charge
	 *
	 * @return array {
	 * @type string  $currency
	 * @type int     $amount
	 * @type string  $payment_card_last4
	 * @type string  $payment_card_brand
	 * @type string  $payment_card_cardholder_name
	 * @type string  $reason_for_failure
	 * @type array   $refunds {
	 * @type int     $amount
	 * @type string  $date
	 *  }
	 *
	 * }
	 */
	public function parse_charge( $charge ) {

		$human_charge = array();

		$human_charge[ 'currency' ] = strtoupper( $charge[ 'currency' ] );
		$zero_decimal_currency      = PPP_Stripe_API::is_zero_decimal_currency( $human_charge[ 'currency' ] );

		$human_charge[ 'amount' ] = ( $zero_decimal_currency ) ? $charge[ 'amount' ] : $charge[ 'amount' ] / 100;

		if ( 'card' == $charge['source']['object'] ) {

			$human_charge[ 'payment_card_last4' ]           = $charge[ 'source' ][ 'last4' ];
			$human_charge[ 'payment_card_brand' ]           = $charge[ 'source' ][ 'brand' ];
			$human_charge[ 'payment_card_cardholder_name' ] = $charge[ 'source' ][ 'name' ];

		}

		if ( ! $charge[ 'paid' ] || ! $charge['succeeded'] ) {

			$human_charge[ 'reason_for_failure' ] = $charge[ 'failure_message' ];

		}

		if ( 0 < $charge[ 'amount_refunded' ] ) {

			foreach ( $charge->refunds[ 'data' ] as $refund ) {

				$refunds[ ] = array(
					'amount' => ( $zero_decimal_currency ) ? $refund[ 'amount' ] : $refund[ 'amount' ] / 100,
					'date'   => date_i18n( 'm/d/Y', $refund[ 'created' ], true )
				);

			}

			$human_charge[ 'refunds' ] = $refunds;
		}

		return $human_charge;
	}

	public function parse_dispute( $dispute ) {
		return $dispute;
	}

	public function parse_customer( $customer ) {
		return $customer;
	}

	public function parse_card( $card ) {
		return $card;
	}

	public function parse_subscription( $subscription ) {

		$human_subscription = array();

		if ( 'trialing' == $subscription[ 'status' ] ) {
			$human_subscription[ 'trial_end_date' ] = date_i18n( 'm/d/Y', $subscription[ 'trial_end' ], true );
		}

		return $human_subscription;
	}

	public function parse_discount( $discount ) {
		return $discount;
	}

	/**
	 * Parse the invoice object and return helpful, human-readable information for this invoice
	 *
	 * @param object $invoice
	 *
	 * @return array {
	 * @type string  $date
	 * @type int     $amount_due
	 * @type string  $currency
	 * @type bool    $is_paid
	 * @type string  $period
	 * @type int     $number_of_attempts
	 * @type string  $next_payment_attempt
	 * @type array   $line_items {
	 * @type string  $description
	 * @type int     $quantity
	 * @type string  $period
	 * @type int     $amount
	 * @type string  $currency
	 *  }
	 * @type string  $customer_email
	 * @type string  $wp_user_email
	 * @type string  $payment_card_last4
	 * @type string  $payment_card_brand
	 * @type string  $payment_card_cardholder_name
	 * }
	 */
	public function parse_invoice( $invoice ) {

		$invoice_info                 = array();

		$invoice_info[ 'date' ]       = date_i18n( 'm/d/Y', $invoice[ 'date' ], true );

		$invoice_info[ 'currency' ]   = strtoupper( $invoice[ 'currency' ] );

		$zero_decimal_currency        = PPP_Stripe_API::is_zero_decimal_currency( $invoice_info[ 'currency' ] );
		$invoice_info[ 'amount_due' ] = ( $zero_decimal_currency ) ? $invoice[ 'amount_due' ] : $invoice[ 'amount_due' ] / 100;

		$invoice_info[ 'is_paid' ] = $invoice[ 'paid' ];

		$period_start              = date_i18n( 'm/d/Y', $invoice[ 'period_start' ], true );
		$period_end                = date_i18n( 'm/d/Y', $invoice[ 'period_end' ], true );

		$invoice_info[ 'period' ]  = "{$period_start} - {$period_end}";

		if ( ! $invoice_info[ 'is_paid' ] ) {

			$invoice_info[ 'number_of_attempts' ]   = $invoice[ 'attempt_count' ];
			$invoice_info[ 'next_payment_attempt' ] = date_i18n( 'm/d/Y', $invoice[ 'next_payment_attempt' ], true );

		}

		$invoice_info[ 'number_of_line_items' ] = $invoice->lines[ 'total_count' ];//TODO test

		$invoice_info[ 'line_items' ] = $this->parse_invoice_line_items( $invoice->lines['data']);

		if ( ! empty( $invoice->discount ) ) {

			$discount = array(
				'coupon_id' => $invoice->discount->coupon[ 'id' ],
				'end_date'  => ( empty( $invoice->discount[ 'end' ] ) ) ? $invoice->discount[ 'end' ] : date_i18n( 'm/d/Y', $invoice->discount[ 'end' ], true )
			);

			if ( empty( $invoice->discount->coupon[ 'percent_off' ] ) ) {
				$discount[ 'amount_off' ] = ( $zero_decimal_currency ) ? $invoice->discount->coupon[ 'amount_off' ] : $invoice->discount->coupon[ 'amount_off' ] / 100;
			} else if ( empty( $invoice->discount->coupon[ 'amount_off' ] ) ) {
				$discount[ 'percent_off' ] = $invoice->discount->coupon[ 'percent_off' ];
			}

			$invoice_info[ 'discount' ] = $discount;

		}

		$customer_id = $invoice[ 'customer' ];

		$customer    = PPP_Stripe_API::retrieve( 'customer', apply_filters( 'ppp_stripe_event_api_key', $this->api_key, $this->plugin_slug, 'parse_invoice', array( 'invoice' => $invoice ) ), $customer_id );

		if ( ( ! is_wp_error( $customer ) ) && ( is_object( $customer ) ) ) {

			$invoice_info[ 'customer_email' ]               = $customer[ 'email' ];

			$wp_user_id                                     = $customer[ 'metadata' ][ 'wp_user_id' ];
			$wp_user                                        = ( ! empty( $wp_user_id ) ) ? get_userdata( $wp_user_id ) : null;

			$invoice_info[ 'wp_user_email' ]                = ( $wp_user ) ? $wp_user->user_email : null;

			if ( 'card' == $customer->default_source[ 'object' ]) {

				$invoice_info[ 'payment_card_last4' ] = $customer->default_source[ 'last4' ];

				$invoice_info[ 'payment_card_brand' ] = $customer->default_source[ 'brand' ];

				$invoice_info[ 'payment_card_cardholder_name' ] = $customer->default_source[ 'name' ];

			}

		}

		return $invoice_info;
	}

	public function parse_invoice_line_items( $invoicelineitems ) {

		$line_items = array();

				foreach ( $invoicelineitems as $invoice_line_item ) {

					$currency = strtoupper( $invoice_line_item[ 'currency' ] );
					$zero_decimal_currency        = PPP_Stripe_API::is_zero_decimal_currency( $currency );

					if ( 'invoiceitem' == $invoice_line_item[ 'type' ] ) {

						$line_items[ ] = array(
							'description' => $invoice_line_item[ 'description' ],
							'quantity'    => 1,
							'period'      => date_i18n( 'm/d/Y', $invoice_line_item[ 'period' ][ 'start' ], true ),
							'amount'      => ($zero_decimal_currency) ? $invoice_line_item[ 'amount' ] : $invoice_line_item[ 'amount' ] / 100,
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

	public function parse_invoiceitem( $invoiceitem ) {
		return $invoiceitem;
	}

	public function parse_plan( $plan ) {
		return $plan;
	}

	public function parse_coupon( $coupon ) {
		return $coupon;
	}

	public function parse_transfer( $transfer ) {
		return $transfer;
	}

	/**
	 * Parse the refund object and return helpful, human-readable information for this refund
	 *
	 * @param object $refund
	 *
	 * @return array {
	 * @type int     $amount
	 * @type string  $date
	 * @type string  $currency
	 * @type array   $metadata
	 * }
	 */
	public function parse_refund( $refund ) {

		$human_refund = array();

		$human_refund[ 'currency' ] = strtoupper( $refund[ 'currency' ] );
		$human_refund[ 'amount' ]   = ( PPP_Stripe_API::is_zero_decimal_currency( $human_refund[ 'currency' ] ) ) ? $refund[ 'amount' ] : $refund[ 'amount' ] / 100;
		$human_refund[ 'date' ]     = date_i18n( 'm/d/Y', $refund[ 'created' ], true );
		$human_refund[ 'metadata' ] = $refund[ 'metadata' ];

		return $human_refund;

	}

	//------------------------------------------------------
	//------------- NOTES ------------------------
	//------------------------------------------------------

	protected function create_charge_note( $action, $human_charge ) {

		$this->logger->log->debug( 'Creating charge note.' );

		$note = '';

		$charge_id   = $this->event_object[ 'id' ];
		$charge_link = PPP_Stripe_API::create_stripe_dashboard_link( $charge_id, 'charge', $this->event[ 'livemode' ] );
		$charge      = "<a href=\"{$charge_link}\" alt=\"View this charge on Stripe dashboard\" title=\"View on Stripe dashboard\" target=\"_blank\">{$charge_id}</a>";

		$amount = "{$human_charge['amount']} {$human_charge['currency']}";

		$card_info = "{$human_charge['payment_card_brand']} ending in {$human_charge['payment_card_last4']}";

		if ( 'failed' == $action ) {

			$failure_message = $human_charge[ 'reason_for_failure' ];
			$note            = sprintf( __( 'Uh oh! Charge %1$s for <em>%2$s</em> <strong>failed</strong> with <em>%3$s</em>. Reason: %4$s', 'ppp-stripe' ), $charge, $amount, $card_info, $failure_message );

		} else if ( 'refunded' == $action ) {

			$last_refund        = end( $human_charge[ 'refunds' ] );
			$last_refund_amount = "{$last_refund['amount']} {$human_charge['currency']}";

			reset( $human_charge[ 'refunds' ] );

			$note = sprintf( __( 'Charge %1$s was <strong>refunded</strong> <em>%2$s</em> to <em>%3$s</em>.', 'ppp-stripe' ), $charge, $last_refund_amount, $card_info );

		} else if ( 'captured' == $action ) {

			$note = sprintf( __( 'Success! Charge %1$s for <em>%2$s</em> was <strong>captured</strong> with <em>%3$s</em>.', 'ppp-stripe' ), $charge, $amount, $card_info );

		}

		return $note;
	}

	protected function create_subscription_note( $action, $human_subscription ) {

		$this->logger->log->debug( 'Creating subscription note.' );

		$note = '';

		$customer_id   = $this->event_object[ 'customer' ];
		$customer_link = PPP_Stripe_API::create_stripe_dashboard_link( $customer_id, 'customer', $this->event[ 'livemode' ] );
		$customer      = "<a href=\"{$customer_link}\" alt=\"View this customer on Stripe dashboard\" title=\"View on Stripe dashboard\" target=\"_blank\">{$customer_id}</a>";

		$subscription_id = $this->event_object[ 'id' ];

		if ( 'updated' == $action ) {

			$updates = '<br /><br />';

			foreach ( $human_subscription[ 'updates' ] as $attribute => $update ) {

				$old_value = $update[ 0 ];
				$new_value = $update[ 1 ];

				switch ( $attribute ) {

						case 'plan':
						$attribute_name = __( 'Plan', 'ppp-stripe' );
						$old_value      = $old_value[ 'name' ];
						$new_value      = $new_value[ 'name' ];
						break;

					case 'discount':
						$attribute_name = __( 'Coupon', 'ppp-stripe' );
						$old_value      = $old_value->coupon[ 'id' ];
						$new_value      = $new_value->coupon[ 'id' ];
						break;

					case 'prorate':
						$attribute_name = __( 'Prorate', 'ppp-stripe' );
						$old_value      = var_export( $old_value, true );
						$new_value      = var_export( $new_value, true );
						break;

					case 'trial_end':
						$attribute_name = __( 'End of Trial', 'ppp-stripe' );
						$old_value      = ! empty( $old_value ) ? date_i18n( 'm/d/Y', $old_value, true ) : '';
						$new_value      = ! empty( $new_value ) ? date_i18n( 'm/d/Y', $new_value, true ) : '';
						break;

					case 'trial_start':
						$attribute_name = __( 'Start of Trial', 'ppp-stripe' );
						$old_value      = ! empty( $old_value ) ? date_i18n( 'm/d/Y', $old_value, true ) : '';
						$new_value      = ! empty( $new_value ) ? date_i18n( 'm/d/Y', $new_value, true ) : '';
						break;

					case 'card':
						$attribute_name = __( 'Card', 'ppp-stripe' );
						break;

					case 'quantity':
						$attribute_name = __( 'Quantity', 'ppp-stripe' );
						break;

					case 'application_fee_percent':
						$attribute_name = __( 'Application Fee Percent', 'ppp-stripe' );
						break;

					case 'status':
						$attribute_name = __( 'Status', 'ppp-stripe' );
						break;

					case 'start':
						$attribute_name = __( 'Start Date', 'ppp-stripe' );
						$old_value      = ! empty( $old_value ) ? date_i18n( 'm/d/Y', $old_value, true ) : '';
						$new_value      = ! empty( $new_value ) ? date_i18n( 'm/d/Y', $new_value, true ) : '';
						break;

					case 'cancel_at_period_end':
						$attribute_name = __( 'Cancel At Period End', 'ppp-stripe' );
						$old_value      = var_export( $old_value, true );
						$new_value      = var_export( $new_value, true );
						break;

					case 'canceled_at':
						$attribute_name = __( 'Canceled At', 'ppp-stripe' );
						$old_value      = ! empty( $old_value ) ? date_i18n( 'm/d/Y', $old_value, true ) : '';
						$new_value      = ! empty( $new_value ) ? date_i18n( 'm/d/Y', $new_value, true ) : '';
						break;

					case 'current_period_end':
						$attribute_name = __( 'End of Current Period', 'ppp-stripe' );
						$old_value      = ! empty( $old_value ) ? date_i18n( 'm/d/Y', $old_value, true ) : '';
						$new_value      = ! empty( $new_value ) ? date_i18n( 'm/d/Y', $new_value, true ) : '';
						break;

					case 'current_period_start':
						$attribute_name = __( 'Start of Current Period', 'ppp-stripe' );
						$old_value      = ! empty( $old_value ) ? date_i18n( 'm/d/Y', $old_value, true ) : '';
						$new_value      = ! empty( $new_value ) ? date_i18n( 'm/d/Y', $new_value, true ) : '';
						break;

				}

				$updates .= sprintf( __( '%1$s: %2$s &#10142; %3$s<br /><br />', 'ppp-stripe' ), $attribute_name, $old_value, $new_value );

			}

			$note = sprintf( __( '%1$s\'s subscription %2$s was <strong>updated</strong>.%3$s', 'ppp-stripe' ), $customer, $subscription_id, $updates );

		} else if ( 'trial_will_end' == $action ) {

			$note = sprintf( __( '%1$s\'s subscription %2$s <strong>trial will end</strong> <em>%3$s</em>.', 'ppp-stripe' ), $customer, $subscription_id, $human_subscription[ 'trial_end_date' ] );

		}

		return $note;
	}

	protected function create_invoice_note( $action, $human_invoice ) {

		$this->logger->log->debug( 'Creating invoice note.' );

		$note = '';

		$customer_id   = $this->event_object[ 'customer' ];
		$customer_link = PPP_Stripe_API::create_stripe_dashboard_link( $customer_id, 'customer', $this->event[ 'livemode' ] );
		$customer      = "<a href=\"{$customer_link}\" alt=\"View this customer on Stripe dashboard\" title=\"View on Stripe dashboard\" target=\"_blank\">{$customer_id}</a>";

		$invoice_id   = $this->event_object[ 'id' ];
		$invoice_link = PPP_Stripe_API::create_stripe_dashboard_link( $invoice_id, $this->event_object[ 'object' ], $this->event[ 'livemode' ] );
		$invoice      = "<a href=\"{$invoice_link}\" alt=\"View this invoice on Stripe dashboard\" title=\"View on Stripe dashboard\" target=\"_blank\">{$invoice_id}</a>";

		$period = $human_invoice[ 'period' ];
		$amount = "{$human_invoice['amount_due']} {$human_invoice['currency']}";

		if ( 'payment_failed' != $action && ! empty( $human_invoice[ 'discount' ] ) ) {

			$coupon_id   = $human_invoice[ 'discount' ][ 'coupon_id' ];

			$coupon_link = PPP_Stripe_API::create_stripe_dashboard_link( $coupon_id, 'coupon', $this->event[ 'livemode' ] );

			if ( ! empty( $human_invoice[ 'discount' ][ 'amount_off' ] ) ) {

				$coupon = "<a href=\"{$coupon_link}\" alt=\"View this coupon on Stripe dashboard\" title=\"View on Stripe dashboard\" target=\"_blank\">{$human_invoice['discount']['amount_off']} off coupon</a>";

			} else if ( ! empty( $human_invoice[ 'discount' ][ 'percent_off' ] ) ) {

				$coupon = "<a href=\"{$coupon_link}\" alt=\"View this coupon on Stripe dashboard\" title=\"View on Stripe dashboard\" target=\"_blank\">{$human_invoice['discount']['percent_off']}% off coupon</a>";

			}

			$amount .= " with {$coupon}";

		}

		$card_info = "{$human_invoice['payment_card_brand']} ending in {$human_invoice['payment_card_last4']}";

		if ( 'payment_failed' == $action ) {

			$next_payment_attempt = $human_invoice[ 'next_payment_attempt' ];
			$note                 = sprintf( __( 'Uh oh! %1$s\'s invoice %2$s for period %3$s (%4$s) <strong>failed</strong> payment with <em>%5$s</em>. The next payment attempt will be %6$s.', 'ppp-stripe' ), $customer, $invoice, $period, $amount, $card_info, $next_payment_attempt );

		} else if ( 'payment_succeeded' == $action ) {

			$note = sprintf( __( 'Success! %1$s\'s invoice %2$s for period %3$s (%4$s) was successfully <strong>paid</strong> with <em>%5$s</em>', 'ppp-stripe' ), $customer, $invoice, $period, $amount, $card_info );

		}

		return $note;
	}

	//------------------------------------------------------
	//------------- UTILITIES ------------------------
	//------------------------------------------------------

	/**
	 * @param $action
	 * @param $charge
	 *
	 * @return bool
	 */
	final protected function is_invoice_charge( $charge ) {
		return ( null !== $charge[ 'invoice' ] );
	}

	/**
	 * @param $object
	 *
	 * @return bool
	 */
	final protected function has_description( $object ) {
		return ( null !== $object[ 'description' ] );
	}

}