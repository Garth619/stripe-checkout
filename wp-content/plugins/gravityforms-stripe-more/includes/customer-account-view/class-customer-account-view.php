<?php
/** @package   GFPMoreStripe
 * @copyright 2014 gravity+
 * @license   GPL-2.0+
 * @since     1.9.1.1
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * GFP_Stripe_Customer_Account_View Class
 *
 * Provides a customer account view for logged-in users
 *
 * @since 1.9.1.1
 *
 */
class GFP_Stripe_Customer_Account_View {

	private static $_this = null;

	/**
	 * View Options
	 *
	 * @since 1.9.1.1
	 *
	 * @var array
	 */
	private $options = array();

	public function __construct() {

		$this->options = array(
			'show_current_subscription'   => 'yes',
			'allow_update_subscription'   => 'yes',
			'allow_cancel_subscription'   => 'yes',
			'update_subscription_link'    => '',
			'cancel_subscription_link'    => '',
			'show_payment_method'         => 'yes',
			'show_payment_history'        => 'yes',
			'allow_update_payment_method' => 'yes',
			'allow_delete_payment_method' => 'yes',
			'update_payment_method_link'  => ''
		);

		self::$_this = $this;

	}

	public function run() {

		add_action( 'init', array( $this, 'init' ) );

	}

	public function init() {

		add_shortcode( 'stripe_customer_account_view', array( $this, 'shortcode_stripe_customer_account_view' ) );

		add_action( 'wp_ajax_gfp_more_stripe_delete_payment_method', array( $this, 'delete_payment_method' ) );

	}

	public function shortcode_stripe_customer_account_view( $attr, $content = null ) {

		$output = '';

		if ( $user_id = get_current_user_id() ) {

			$this->set_view_options( $attr );

			wp_enqueue_style( 'gfp_stripe_font_awesome', GFP_STRIPE_URL . '/css/font-awesome.min.css', null, GFP_Stripe::get_version() );

			$output = '<div id="stripe-customer-account-view">';

			ob_start();

			if ( 'yes' == $this->options[ 'show_current_subscription' ] ) {
				$this->show_current_subscription();
			}

			if ( 'yes' == $this->options[ 'show_payment_method' ] ) {
				$this->show_payment_method();
			}

			if ( 'yes' == $this->options[ 'show_payment_history' ] ) {
				$this->show_payment_history();
			}

			$output .= ob_get_contents();

			ob_end_clean();

			$output .= '</div>';

		} else {

			$output = "You must be logged in to view this content.";

		}

		return $output;
	}

	private function set_view_options( $options ) {

		$this->options                         = shortcode_atts( $this->options, $options );
		$this->options[ 'empty_section_text' ] = apply_filters( 'gfp_stripe_customer_account_view_empty_section_text', __( 'None', 'gravityforms-stripe-more' ) );

	}

	private function show_current_subscription() {

		$user_id = get_current_user_id();

		$section_label = __( 'Current Subscription(s)', 'gravityforms-stripe-more' );

		$active_subscriptions = GFP_More_Stripe_Customer_API::get_active_subscriptions( $user_id );

		if ( ! empty( $active_subscriptions ) ) {

			$current_subscription = GFP_More_Stripe_Customer_API::get_subscription_info( $user_id, $active_subscriptions[ 0 ] );

			$plan_name  = $current_subscription[ 'plan' ][ 'name' ];
			$plan_id    = $current_subscription[ 'plan' ][ 'id' ];
			$start_date = $current_subscription[ 'start' ];
			$quantity   = $current_subscription[ 'quantity' ];

			if ( 'yes' == $this->options[ 'allow_update_subscription' ] && ! empty( $this->options[ 'update_subscription_link' ] ) ) {

				$update_subscription_page = esc_url( $this->options[ 'update_subscription_link' ], null, '' );

				if ( ! empty( $update_subscription_page ) ) {

					$update_subscription_link_text = apply_filters( 'gfp_stripe_update_subscription_link_text', __( 'Update', 'gravityforms-stripe-more' ) );
					$update_subscription_link      = add_query_arg( array(
						                                                'sub_id'  => $current_subscription[ 'id' ],
						                                                'plan_id' => $plan_id,
						                                                'qty'     => $quantity
					                                                ), $update_subscription_page );

				}

			}

			if ( 'yes' == $this->options[ 'allow_cancel_subscription' ] && ! empty( $this->options[ 'cancel_subscription_link' ] ) ) {

				$cancel_subscription_page = esc_url( $this->options[ 'cancel_subscription_link' ], null, '' );

				if ( ! empty( $cancel_subscription_page ) ) {

					$cancel_subscription_link_text = apply_filters( 'gfp_stripe_cancel_subscription_link_text', __( 'Cancel', 'gravityforms-stripe-more' ) );
					$cancel_subscription_link      = add_query_arg( array(
						                                                'sub_id'  => $current_subscription[ 'id' ],
						                                                'plan_id' => 'cancel'
					                                                ), $cancel_subscription_page );

				}

			}

		}

		include( GFP_MORE_STRIPE_PATH . '/includes/customer-account-view/views/current-subscription.php' );
	}

	private function show_payment_method() {

		$user_id = get_current_user_id();

		$payment_method_list = GFP_Stripe_Helper::get_customer_payment_method_list( $user_id );

		$section_label = __( 'Payment Method(s)', 'gravityforms-stripe-more' );

		if ( ! empty( $payment_method_list ) ) {

			if ( 'yes' == $this->options[ 'allow_update_payment_method' ] && ! empty( $this->options[ 'update_payment_method_link' ] ) ) {

				$update_payment_method_page = esc_url( $this->options[ 'update_payment_method_link' ], null, '' );

				if ( ! empty( $update_payment_method_page ) ) {
					$update_payment_method_link_text = apply_filters( 'gfp_stripe_update_payment_method_link_text', __( 'Update', 'gravityforms-stripe-more' ) );

					/*foreach ( $payment_method_list as $key => $payment_method ) {
						$payment_method_list[ $key ]['update_link'] = add_query_arg( array(
																		 'card_id'  => $payment_method[ 'id' ]
																	 ), $update_payment_method_page );
					}*/

				}

			}

			if ( 'yes' == $this->options[ 'allow_delete_payment_method' ] ) {

				$delete_payment_method_link_text = apply_filters( 'gfp_stripe_delete_payment_method_link_text', __( 'Delete', 'gravityforms-stripe-more' ) );

			}

		}

		include( GFP_MORE_STRIPE_PATH . '/includes/customer-account-view/views/payment-method.php' );
	}

	private function show_payment_history() {

		$user_id = get_current_user_id();

		$transactions              = GFP_Stripe_Data::get_transaction_by( 'user_id', $user_id );
		$transaction_types_to_show = array( 'payment', 'update_subs', 'event' );

		$section_label = __( 'Payment History', 'gravityforms-stripe-more' );

		include( GFP_MORE_STRIPE_PATH . '/includes/customer-account-view/views/payment-history.php' );

	}

	private function get_transaction_description( $transaction ) {

		$description = '';

		if ( ! empty( $transaction[ 'meta' ][ 'object' ] ) ) {

			$object = $this->convert_to_stripe_object( $transaction[ 'meta' ][ 'object' ] );


			switch ( $object[ 'object' ] ) {

				case 'charge':
				case 'customer':
					$description = $object[ 'description' ];
					break;

				case 'subscription':

					$description = $object->plan[ 'name' ];

					if ( ! empty( $transaction[ 'meta' ][ 'invoice' ] ) ) {

						$invoice            = $this->convert_to_stripe_object( $transaction[ 'meta' ][ 'invoice' ] );
						$invoice_line_items = GFP_Stripe_Helper::parse_invoice_line_items( $invoice->lines[ 'data' ], GFPMoreStripe::get_currency_info( strtoupper( $invoice[ 'currency' ] ) ) );

						if ( ! empty( $invoice_line_items ) ) {

							$description = '';

							foreach ( $invoice_line_items as $line_item ) {

								$description .= "{$line_item['description']}. {$line_item['period']}";

								if ( 1 < count( $invoice_line_items ) ) {
									$description .= "<br />";
								}

							}

						}

					}
					break;

				case 'invoice':

					$invoice            = $object;
					$description        = $invoice[ 'description' ];
					$invoice_line_items = GFP_Stripe_Helper::parse_invoice_line_items( $invoice->lines[ 'data' ], GFPMoreStripe::get_currency_info( strtoupper( $invoice[ 'currency' ] ) ) );

					if ( ! empty( $invoice_line_items ) ) {

						$description = '';

						foreach ( $invoice_line_items as $line_item ) {

							$description .= "{$line_item['description']}. {$line_item['period']}";

							if ( 1 < count( $invoice_line_items ) ) {
								$description .= "<br />";
							}

						}

					}
					break;
			}
		}


		return $description;
	}

	private function convert_to_stripe_object( $object_array ) {

		if ( 'subscription' == $object_array[ 'object' ] ) {
			$mode = $object_array[ 'plan' ][ 'livemode' ] ? 'live' : 'test';
		} else {
			$mode = PPP_Stripe_API::get_object_mode( $object_array );
		}

		$api_key = apply_filters( 'gfp_more_stripe_api_key', GFP_Stripe::get_api_key( 'secret', $mode ), 'convert_to_stripe_object' );

		PPP_Stripe_API::include_api();

		return PPP\Stripe\Util\Util::convertToStripeObject( $object_array, $api_key );
	}

	public function delete_payment_method() {

		$card_id = rgpost( 'card_id' );
		$user_id = get_current_user_id();

		$customer_id = GFP_More_Stripe_Customer_API::get_stripe_customer_id( $user_id );

		$customer_object = GFP_More_Stripe_Customer_API::get_customer_object_from_customer_id( null, $customer_id );

		$api_key = apply_filters( 'gfp_more_stripe_api_key', GFP_Stripe::get_api_key( 'secret', PPP_Stripe_API::get_object_mode( $customer_object ) ), 'delete_payment_method' );

		$deleted_card = PPP_Stripe_API::delete_card( $api_key, $card_id, $customer_object );

		if ( is_object( $deleted_card ) && is_a( $deleted_card, 'PPP\\Stripe\\Card' ) ) {//TODO

			GFP_More_Stripe_Customer_API::remove_card( $user_id, $card_id );
			wp_send_json_success();

		} else {

			wp_send_json_error();

		}

	}
}