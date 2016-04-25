<?php
/**
 *
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class GFP_More_Stripe_Currency_Converter {
	/**
	 * Instance of this class.
	 *
	 * @since    1.8.2
	 *
	 * @var      object
	 */
	private static $_this = null;

	private $rates = '';

	/**
	 * Initialize the plugin by setting localization, filters, and administration functions.
	 *
	 * @since     1.8.2
	 *
	 * @uses      wp_die()
	 * @uses      __()
	 * @uses      register_activation_hook()
	 * @uses      add_action()
	 *
	 */
	function __construct () {

		if ( isset( self::$_this ) ) {
			wp_die( sprintf( __( 'There is already an instance of %s.',
								 'gravityforms-stripe-more' ), get_class( $this ) ) );
		}

		self::$_this = $this;

		add_action( 'init', array( $this, 'init' ) );

	}

	/**
	 * Private clone method to prevent cloning of the instance of the
	 * *Singleton* instance.
	 *
	 * @return void
	 */
	private function __clone () {
	}

	/**
	 * Private unserialize method to prevent unserializing of the *Singleton*
	 * instance.
	 *
	 * @return void
	 */
	private function __wakeup () {
	}

	/**
	 * @return GFP_More_Stripe_Currency_Converter|null|object
	 */
	static function this () {
		return self::$_this;
	}

	public function init () {

		add_action( 'gform_enqueue_scripts', array( $this, 'gform_enqueue_scripts' ), 10, 2 );

		add_filter( 'gform_product_info', array( $this, 'gform_product_info' ), 11, 3 );

	}

	public function admin_init () {
		add_filter( 'gform_noconflict_scripts', array( $this, 'gform_noconflict_scripts' ) );
	}

	private function get_current_rates () {
		if ( ! empty( self::$_this->rates ) ) {
			$current_rates = self::$_this->rates;
		}
		else {
			GFP_Stripe::log_debug( __( 'Retrieving current conversion rates', 'gravityforms-stripe-more' ) );
			$api_url  = 'http://openexchangerates.org/api/latest.json?app_id=a46b7ec2995a4f7b96cf2c4309cb10ff';
			$response = wp_remote_post( $api_url );
			$body     = wp_remote_retrieve_body( $response );
			if ( empty( $body ) ) {
				GFP_Stripe::log_error( __( 'Empty response', 'gravityforms-stripe-more' ) );

				return $body;
			}

			$current_rates = json_decode( $body, true );
			if ( ! empty( $current_rates['error'] ) ) {
				GFP_Stripe::log_error( "{$current_rates['message']} {$current_rates['description']}" );

				return '';
			}

			self::$_this->rates = $current_rates;
		}

		return $current_rates;
	}

	public function gform_enqueue_scripts ( $form, $ajax ) {
		if ( GFP_More_Stripe_Currency::has_currency_field( $form ) ) {
			$this->add_currency_field_js( $form );
		}
	}

	private function add_currency_field_js ( $form ) {
		$currency_field_vars = array();

		$current_rates = $this->get_current_rates();
		if ( ! empty( $current_rates ) ) {
			$currency_field_vars['rates']        = $current_rates['rates'];
			$currency_field_vars['base']         = $current_rates['base'];
			$currency_field_vars['default_from'] = ( ! empty( $form['currency'] ) ) ? $form['currency'] : GFCommon::get_currency();

			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			wp_enqueue_script( 'gfp_more_stripe_currency_moneyjs', trailingslashit( GFP_MORE_STRIPE_URL ) . "includes/currency/js/money{$suffix}.js", array( 'jquery' ), GFPMoreStripe::get_version() );

			wp_enqueue_script( 'gfp_more_stripe_currency_field', GFP_MORE_STRIPE_URL . "/includes/currency/js/currency_field{$suffix}.js", array( 'gform_gravityforms', 'gfp_more_stripe_currency_moneyjs' ), GFPMoreStripe::get_version() );

			$form_feeds                               = GFP_Stripe_Data::get_feed_by_form( $form['id'], true );
			$currency_field_vars['currency_field_id'] = GFP_More_Stripe_Currency::get_currency_field_id_from_feed( $form_feeds[0] );

			$currency_field_vars['nonce'] = wp_create_nonce( 'gfp_more_stripe_get_currency' );

			$protocol                       = isset ( $_SERVER['HTTPS'] ) ? 'https://' : 'http://';
			$currency_field_vars['ajaxurl'] = admin_url( 'admin-ajax.php', $protocol );

			$currency_field_vars['spinner_url'] = apply_filters( "gform_ajax_spinner_url_{$form['id']}", apply_filters( "gform_ajax_spinner_url", GFCommon::get_base_url() . "/images/spinner.gif", $form ), $form );

			if ( ! empty( $_POST ) ) {
				$currency_field_vars['is_postback'] = true;
			}

			wp_localize_script( 'gfp_more_stripe_currency_field', 'gf_currency', $currency_field_vars );
		}
		else {
			GFP_Stripe::log_error( __( 'Unable to add currency field JS', 'gravityforms-stripe-more' ) );
		}
	}

	/**
	 * @param $noconflict_scripts
	 *
	 * @return array
	 */
	public function gform_noconflict_scripts ( $noconflict_scripts ) {
		return array_merge( $noconflict_scripts, array( 'gfp_more_stripe_currency_field', 'gfp_more_stripe_currency_moneyjs' ) );
	}

	public function gform_product_info ( $product_info, $form, $lead ) {
		$feed = GFP_Stripe::get_feed_that_meets_condition( $form );
		if ( $feed ) {
			$submitted_currency = GFP_More_Stripe_Currency::get_submitted_currency( $form, $feed, $lead );
			if ( ! empty( $submitted_currency ) ) {
				GFP_More_Stripe_Currency::include_rgcurrency();
				$default_form_currency = ( ! empty( $form['currency'] ) ) ? $form['currency'] : GFCommon::get_currency();

				if ( $default_form_currency !== $submitted_currency ) {

					foreach ( $product_info['products'] as $field_id => $product ) {
						$user_defined_product = $this->is_user_defined_product_field( $form, $field_id );
						if ( $user_defined_product ) {
							$price = GFCommon::to_number( $product['price'], $submitted_currency );
						}
						else {
							$price = GFCommon::to_number( $product['price'], $default_form_currency );
							$price = $this->convert( $price, $default_form_currency, $submitted_currency );
						}
						if ( isset( $product['options'] ) && is_array( $product['options'] ) ) {
							foreach ( $product['options'] as $option_id => $option ) {
								$product_info['products'][$field_id]['options'][$option_id]['price'] = $this->convert( $option['price'], $default_form_currency, $submitted_currency );
							}
						}
						$product_info['products'][$field_id]['price'] = GFCommon::to_money( $price, $submitted_currency );
					}

					if ( ! empty( $product_info['shipping']['name'] ) ) {
						$product_info['shipping']['price'] = $this->convert( $product_info['shipping']['price'], $default_form_currency, $submitted_currency );
					}
				}
			}
		}

		return $product_info;
	}

	public function is_user_defined_product_field ( $form, $field_id ) {
		$is_user_defined_product_field = false;
		foreach ( $form['fields'] as $field ) {
			if ( $field_id == $field['id'] && 'price' == $field['inputType'] ) {
				$is_user_defined_product_field = true;
				break;
			}
		}

		return $is_user_defined_product_field;
	}

	/**
	 * @param $number
	 */
	public function to_money ( $number ) {
	}

	/**
	 * @param $money
	 */
	public function to_number ( $money ) {
	}

	/**
	 * @param $number
	 * @param $from_currency
	 * @param $to_currency
	 *
	 * @return float
	 */
	public static function convert ( $number, $from_currency, $to_currency ) {
		$rate = self::$_this->get_rate( $from_currency, $to_currency );
		if ( ! empty( $rate ) ) {
			$number = $number * $rate;
		}

		return $number;

	}

	private function get_rate ( $from_currency, $to_currency ) {
		$current_rates = self::get_current_rates();
		if ( ! empty( $current_rates ) ) {
			if ( $current_rates['base'] == $from_currency ) {
				$rate = $current_rates['rates'][$to_currency];
			}
			else if ( $current_rates['base'] == $to_currency ) {
				$rate = 1 / $current_rates['rates'][$from_currency];
			}
			else {
				$rate = $current_rates['rates'][$to_currency] * ( 1 / $current_rates['rates'][$from_currency] );
			}

			return $rate;
		}
	}
} 