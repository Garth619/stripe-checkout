<?php
/**
 *
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_action( 'init', array( 'GFP_More_Stripe_Currency', 'init' ) );

/**
 * Class GFP_More_Stripe_Currency
 */
class GFP_More_Stripe_Currency {

	private static $default_form_currency = '';
	private static $submitted_form_currency = '';

	public static function init() {

		add_action( 'admin_init', array( 'GFP_More_Stripe_Currency', 'admin_init' ) );

		add_action( 'wp_ajax_gfp_more_stripe_get_currency', array( 'GFP_More_Stripe_Currency', 'get_currency' ) );
		add_action( 'wp_ajax_nopriv_gfp_more_stripe_get_currency', array(
			'GFP_More_Stripe_Currency',
			'get_currency'
		) );

		add_filter( 'gform_currency', array( 'GFP_More_Stripe_Currency', 'gform_currency' ), 11 );

		add_filter( 'gfp_stripe_get_form_data', array(
			'GFP_More_Stripe_Currency',
			'gfp_stripe_get_form_data'
		), 10, 5 );
	}

	public static function admin_init() {

		if ( 'gf_edit_forms' == RGForms::get( 'page' ) ) {

			if ( ( ( 'settings' == rgget( 'view' ) ) && ( 'stripe' == rgget( 'subview' ) ) && ( GFP_Stripe::has_access( 'gfp_stripe_form_settings' ) ) ) ) {

				add_action( 'gfp_stripe_form_settings', array(
					'GFP_More_Stripe_Currency',
					'gfp_stripe_form_settings'
				) );
				add_filter( 'gfp_stripe_pre_form_settings_save', array(
					'GFP_More_Stripe_Currency',
					'gfp_stripe_pre_form_settings_save'
				), 10, 2 );
				add_action( 'gfp_stripe_feed_after_billing', array(
					'GFP_More_Stripe_Currency',
					'gfp_stripe_feed_after_billing'
				), 9, 2 );

			}

		}
	}

	public static function get_currency() {

		check_ajax_referer( 'gfp_more_stripe_get_currency', 'gfp_more_stripe_get_currency' );

		$currency_code = rgpost( 'currency' );

		GFP_More_Stripe_Currency::include_rgcurrency();

		$currency = RGCurrency::get_currency( $currency_code );

		if ( ! empty( $currency ) && is_array( $currency ) ) {

			$currency[ 'code' ] = $currency_code;

			wp_send_json_success( $currency );

		} else {

			wp_send_json_error();

		}

	}

	/**
	 * Get currency
	 *
	 * @param $currency
	 *
	 * @return string
	 */
	public static function gform_currency( $currency ) {

		$trace                   = debug_backtrace();
		$check_for_form_currency = false;

		if ( 'js.php' === basename( $trace[ 3 ][ 'file' ] ) ) {


			$check_for_form_currency = true;
			$form_id                 = rgget( 'id' );

		} else {

			switch ( $trace[ 4 ][ 'function' ] ) {

				case 'gf_global':

					$check_for_form_currency = true;
					$form_id                 = IS_ADMIN ? rgget( 'id' ) : $trace[ 5 ][ 'args' ][ 0 ][ 'id' ];
					break;

				case 'gf_vars':

					$check_for_form_currency = true;
					$form_id                 = rgget( 'id' );
					break;

			}

			if ( ! $check_for_form_currency ) {

				if ( array_key_exists( 5, $trace ) ) {

					switch ( $trace[ 5 ][ 'function' ] ) {

						case 'get_field_input':

							$check_for_form_currency = true;
							$form_id                 = IS_ADMIN ? rgget( 'id' ) : $trace[ 5 ][ 'args' ][ 0 ][ 'id' ];
							break;

						case 'get_select_choices':

							$check_for_form_currency = true;
							$form_id                 = IS_ADMIN ? rgget( 'id' ) : $trace[ 5 ][ 'args' ][ 0 ][ 'formId' ];
							break;

						case 'get_radio_choices':
						case 'get_checkbox_choices':

							$check_for_form_currency = true;
							$form_id                 = IS_ADMIN ? rgget( 'id' ) : $trace[ 5 ][ 'args' ][ 2 ];
							break;

						case 'get_state':
						case 'get_product_fields':

							$check_for_form_currency = true;
							$form_id                 = IS_ADMIN ? rgget( 'id' ) : $trace[ 5 ][ 'args' ][ 0 ][ 'id' ];
							break;

						case 'failed_state_validation':

							$check_for_form_currency = true;
							$form_id                 = $trace[ 5 ][ 'args' ][ 0 ];
							break;

						case 'get_total':

							if ( 'get_order_total' == $trace[ 6 ][ 'function' ] ) {

								if ( ! empty( self::$submitted_form_currency ) ) {
									$currency = self::$submitted_form_currency;
								} else {
									$check_for_form_currency = true;
									$form_id                 = $trace[ 6 ][ 'args' ][ 0 ][ 'id' ];
								}

							} else if ( 'gform_product_info' == $trace[ 6 ][ 'function' ] ) {

								if ( ! empty( self::$submitted_form_currency ) ) {
									$currency = self::$submitted_form_currency;
								} else {
									$check_for_form_currency = true;
									$form_id                 = $trace[ 6 ][ 'args' ][ 1 ][ 'id' ];
								}

							}
							break;

						case 'lead_detail_grid':

							$currency = $trace[ 5 ][ 'args' ][ 1 ][ 'currency' ];
							break;

					}

				}

			}

		}

		if ( $check_for_form_currency && ! empty( $form_id ) ) {

			$form = RGFormsModel::get_form_meta( $form_id );

			if ( ! empty( $form[ 'currency' ] ) && $currency !== $form[ 'currency' ] ) {

				$currency = $form[ 'currency' ];

			}

		}

		return $currency;
	}

	/**
	 * @param $form_data
	 * @param $feed
	 * @param $products
	 *
	 * @param $form
	 * @param $tmp_lead
	 *
	 * @return mixed
	 */
	public static function gfp_stripe_get_form_data( $form_data, $feed, $products, $form, $tmp_lead ) {

		if ( ! empty( self::$submitted_form_currency ) ) {

			$form_data[ 'currency' ] = self::$submitted_form_currency;

		} else if ( ! empty( self::$default_form_currency ) ) {

			$form_data[ 'currency' ] = self::$default_form_currency;

		} else if ( ! empty( $form[ 'currency' ] ) ) {

			$form_data[ 'currency' ] = self::$default_form_currency = $form[ 'currency' ];

		}

		return $form_data;
	}

	public static function gfp_stripe_form_settings( $form_id ) {

		$form     = RGFormsModel::get_form_meta( $form_id );

		$selected = ( ! empty( $form[ 'currency' ] ) ) ? $form[ 'currency' ] : GFCommon::get_currency();

		require_once( GFP_MORE_STRIPE_PATH . '/includes/currency/views/form-settings-currency.php' );

	}

	public static function gfp_stripe_pre_form_settings_save( $updated_form, $form ) {

		$new_currency     = rgpost( 'form_currency' );
		$has_old_currency = ! empty( $form[ 'currency' ] );

		if ( ! empty( $new_currency ) ) {

			if ( $has_old_currency && ( $form[ 'currency' ] !== $new_currency ) ) {

				$updated_form               = self::convert_field_base_prices( $form, $form[ 'currency' ], $new_currency );
				$updated_form[ 'currency' ] = $new_currency;

			} else if ( ! $has_old_currency ) {

				$updated_form               = self::convert_field_base_prices( $form, GFCommon::get_currency(), $new_currency );
				$updated_form[ 'currency' ] = $new_currency;

			}

		}

		return $updated_form;
	}

	private static function convert_field_base_prices( $form, $from_currency, $to_currency ) {

		$convert_base_price    = false;
		$convert_option_prices = false;

		foreach ( $form[ 'fields' ] as &$field ) {

			if ( 'product' == $field[ 'type' ] || 'shipping' == $field[ 'type' ] ) {

				if ( ! empty( $field[ 'basePrice' ] ) ) {
					$convert_base_price = true;
				}

				if ( ! empty( $field[ 'choices' ] ) ) {
					$convert_option_prices = true;
				}

			} else if ( 'option' == $field[ 'type' ] ) {

				if ( ! empty( $field[ 'choices' ] ) ) {
					$convert_option_prices = true;
				}

			}

			if ( $convert_base_price ) {

				$basePrice            = GFCommon::to_number( $field[ 'basePrice' ], $from_currency );
				$basePrice            = GFP_More_Stripe_Currency_Converter::convert( $basePrice, $from_currency, $to_currency );

				$field[ 'basePrice' ] = GFCommon::to_money( $basePrice, $to_currency );

				$convert_base_price   = false;

			}

			if ( $convert_option_prices ) {

				foreach ( $field[ 'choices' ] as &$choice ) {

					if ( ! empty( $choice[ 'price' ] ) ) {

						$choice_price      = GFCommon::to_number( $choice[ 'price' ], $from_currency );
						$choice_price      = GFP_More_Stripe_Currency_Converter::convert( $choice_price, $from_currency, $to_currency );

						$choice[ 'price' ] = GFCommon::to_money( $choice_price, $to_currency );

					}

				}

				$convert_option_prices = false;

			}

		}

		return $form;
	}

	/**
	 * @param $feed
	 * @param $form
	 */
	public static function gfp_stripe_feed_after_billing( $feed, $form ) {

		$currency_override_options = GFP_More_Stripe_Currency::get_currency_fields( $form, rgar( $feed[ 'meta' ], 'currency_field' ) );

		require_once( GFP_MORE_STRIPE_PATH . '/includes/currency/views/stripe-feed-override-currency.php' );
	}

	public static function include_rgcurrency() {

		if ( ! class_exists( 'RGCurrency' ) ) {
			require_once( GFCommon::get_base_path() . '/currency.php' );
		}

	}

	/**
	 * @return array
	 */
	public static function get_currencies() {

		self::include_rgcurrency();

		$currencies = array_keys( RGCurrency::get_currencies() );

		if ( is_array( $currencies ) ) {
			usort( $currencies, create_function( '$a,$b', 'return strcmp($a, $b);' ) );
		}

		return $currencies;
	}

	public static function get_stripe_default_currency() {

		$stripe_currency = get_transient( 'gfp_stripe_currency' );

		return $stripe_currency[ 'default' ];
	}

	/**
	 * @param $form
	 * @param $selected_field
	 *
	 * @return string
	 */
	public static function get_currency_fields( $form, $selected_field ) {

		$str    = "<option value=''>" . __( 'Select a field', 'gravityforms-stripe-more' ) . '</option>';
		$fields = GFCommon::get_fields_by_type( $form, array( 'currency' ) );

		foreach ( $fields as $field ) {

			$field_id    = $field[ 'id' ];
			$field_label = RGFormsModel::get_label( $field );

			$selected = $field_id == $selected_field ? "selected='selected'" : "";
			$str .= "<option value='" . $field_id . "' " . $selected . ">" . $field_label . '</option>';
		}


		return $str;
	}

	/**
	 * Builds a dropdown list of currencies
	 *
	 * Adapted from wp_dropdown_categories()
	 *
	 * @since 1.8.2
	 *
	 * @param string $args
	 *
	 * @return string
	 */
	public static function dropdown_currencies( $args = '' ) {

		$defaults = array(
			'show_option_all'  => '',
			'show_option_none' => '',
			'exclude'          => '',
			'echo'             => 1,
			'selected'         => 0,
			'name'             => '',
			'id'               => '',
			'class'            => '',
			'tab_index'        => 0
		);

		$build_options = wp_parse_args( $args, $defaults );

		extract( $build_options );

		$tab_index_attribute = '';

		if ( (int) $tab_index > 0 ) {
			$tab_index_attribute = " tabindex=\"$tab_index\"";
		}

		$currencies = GFP_More_Stripe_Currency::get_currencies();
		$name       = esc_attr( $name );
		$class      = esc_attr( $class );
		$id         = $id ? esc_attr( $id ) : $name;

		if ( ! empty( $currencies ) ) {
			$output = "<select name='$name' id='$id' class='$class' $tab_index_attribute>\n";
		} else {
			$output = '';
		}

		if ( empty( $currencies ) && ! empty( $show_option_none ) ) {
			$output .= "\t<option value='-1' selected='selected'>$show_option_none</option>\n";
		}

		if ( ! empty( $currencies ) ) {

			if ( ! empty( $show_option_all ) ) {
				$selected = ( '0' === strval( $build_options[ 'selected' ] ) ) ? " selected='selected'" : '';
				$output .= "\t<option value='0'$selected>$show_option_all</option>\n";
			}

			if ( ! empty( $show_option_none ) ) {
				$selected = ( '-1' === strval( $build_options[ 'selected' ] ) ) ? " selected='selected'" : '';
				$output .= "\t<option value='-1'$selected>$show_option_none</option>\n";
			}

			foreach ( $currencies as $currency ) {
				$output .= "\t<option value=\"" . $currency . "\"";
				if ( $currency == $args[ 'selected' ] ) {
					$output .= ' selected="selected"';
				}
				$output .= '>';
				$output .= $currency;
				$output .= "</option>\n";
			}
		}

		if ( ! empty( $currencies ) ) {
			$output .= "</select>\n";
		}

		if ( $echo ) {
			echo $output;
		}

		return $output;
	}

	/**
	 * @param $form
	 *
	 * @return bool
	 */
	public static function has_currency_field( $form ) {

		$currency_fields = GFCommon::get_fields_by_type( $form, array( 'currency' ) );

		return ! empty( $currency_fields );
	}


	/**
	 * @param $symbol
	 *
	 * @return string
	 */
	public static function get_currency_from_symbol( $symbol ) {

		GFP_More_Stripe_Currency::include_rgcurrency();

		$currency = '';

		$currencies = RGCurrency::get_currencies();

		foreach ( $currencies as $currency_code => $currency_info ) {

			if ( ( $currency_info[ 'symbol_left' ] == $symbol )
			     || ( $currency_info[ 'symbol_right' ] == $symbol )
			     || ( html_entity_decode( $currency_info[ 'symbol_left' ], ENT_QUOTES, 'UTF-8' ) == $symbol )
			     || ( html_entity_decode( $currency_info[ 'symbol_right' ], ENT_QUOTES, 'UTF-8' ) == $symbol )
			) {
				$currency = $currency_code;
				break;
			}
		}

		return $currency;
	}

	/**
	 * @param $text
	 *
	 * @return string
	 */
	public static function get_currency_symbol_from_text( $text ) {

		$text = strval( $text );

		$text = preg_replace( "/&.*?;/", "", $text );

		$array           = str_split( $text );
		$currency_symbol = '';

		foreach ( $array as $key => $char ) {

			if ( ( ' ' !== $char ) && ( ! ctype_digit( $char ) ) && ( ',' !== $char ) ) {

				if ( ( '.' !== $char ) || ( ( ! ctype_digit( $array[ $key - 1 ] ) ) && ( ! ctype_digit( $array[ $key + 1 ] ) ) ) ) {
					$currency_symbol .= $char;
				}


				}
		}

		return $currency_symbol;
	}

	/**
	 * @param $feed
	 *
	 * @return string
	 */
	public static function get_currency_field_id_from_feed( $feed ) {

		$currency_field_id = '';
		$feed              = $feed[ 'meta' ];

		if ( rgars( $feed, 'type' ) != 'update-subscription' && rgars( $feed, 'type' ) != 'update-billing' ) {

			$currency_override = ! empty ( $feed[ 'currency_override' ] );
			$currency_field    = ( ! empty( $feed[ 'currency_field' ] ) ) && ( is_numeric( $feed[ 'currency_field' ] ) );

			if ( $currency_override && $currency_field ) {
				$currency_field_id = $feed[ 'currency_field' ];
			}

		}

		return $currency_field_id;
	}

	public static function get_currency_field_id_from_form( $form ) {

		$currency_field_id = 0;
		$currency_fields   = GFCommon::get_fields_by_type( $form, array( 'currency' ) );

		if ( ! empty( $currency_fields ) ) {
			$currency_field_id = $currency_fields[ 0 ][ 'id' ];
		}

		return $currency_field_id;
	}

	/**
	 * @param $form
	 * @param $feed
	 * @param $lead
	 *
	 * @return string
	 */
	public static function get_submitted_currency( $form, $feed, $lead ) {

		if ( empty( self::$submitted_form_currency ) ) {

			$currency_override = ! empty ( $feed[ 'meta' ][ 'currency_override' ] );
			$currency_field    = ( ! empty( $feed[ 'meta' ][ 'currency_field' ] ) ) && ( is_numeric( $feed[ 'meta' ][ 'currency_field' ] ) );

			if ( $currency_override && $currency_field ) {

				$currency_field_id             = $feed[ 'meta' ][ 'currency_field' ];
				$currency_field                = RGFormsModel::get_field( $form, $currency_field_id );
				$currency_field_value          = RGFormsModel::get_lead_field_value( $lead, $currency_field );

				self::$submitted_form_currency = trim( strtoupper( $currency_field_value ) );
			}

		}

		return self::$submitted_form_currency;
	}
} 