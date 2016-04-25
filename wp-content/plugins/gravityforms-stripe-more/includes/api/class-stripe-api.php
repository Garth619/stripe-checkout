<?php
/**
 * @package   PPP_Stripe_API
 * @copyright 2014-2015 press+
 * @license   GPL-2.0+
 * @since     1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * PPP_Stripe_API Class
 *
 * Makes Stripe API calls
 *
 * @since 1.0.0
 *
 */
class PPP_Stripe_API {

	private static $plugin_slug = '';

	private static $path = '';

	private static $settings_option_name = '';

	private static $mode = '';

	/**
	 * @var GFP_Stripe_API_Logger
	 */
	private static $logger = null;

	private $version = '1.0.0.beta6';

	public function __construct( $args ) {

		self::$plugin_slug          = $args[ 'slug' ];
		self::$path                 = $args[ 'path' ];
		self::$settings_option_name = $args[ 'settings_option_name' ];
		self::$logger               = $args[ 'logger' ];

	}

	public static function get_mode() {
		return self::$mode;
	}

	public static function get_object_mode( $object ) {
		return ( $object[ 'livemode' ] ) ? 'live' : 'test';
	}

	public static function set_mode( $mode ) {
		self::$mode = $mode;
	}

	/**
	 * Include the Stripe library
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function include_api() {

		if ( ! class_exists( 'PPP\\Stripe\\Stripe' ) ) {
			require_once( trailingslashit( self::$path ) . 'includes/api/stripe-php/init.php' );
		}

	}

	/********************
	 * CURRENCY         *
	 ********************/

	public static function get_stripe_currencies() {

		return array(
			'USD' => array(
				'name'               => __( 'United States Dollar', 'ppp-stripe' ),
				'symbol_left'        => '$',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'AED' => array(
				'name'               => __( 'United Arab Emirates Dirham', 'ppp-stripe' ),
				'symbol_left'        => '&#1583;.&#1573;',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'AFN' => array(
				'name'               => __( 'Afghan Afghani', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => '&#1547;',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'ALL' => array(
				'name'               => __( 'Albanian Lek', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'L',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'AMD' => array(
				'name'               => __( 'Armenian Dram', 'ppp-stripe' ),
				'symbol_left'        => 'AMD',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'ANG' => array(
				'name'               => __( 'Netherlands Antillean Gulden', 'ppp-stripe' ),
				'symbol_left'        => '&#402;',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'AOA' => array(
				'name'               => __( 'Angolan Kwanza', 'ppp-stripe' ),
				'symbol_left'        => 'Kz',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'ARS' => array(
				'name'               => __( 'Argentine Peso', 'ppp-stripe' ),
				'symbol_left'        => 'ARS$',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'AUD' => array(
				'name'               => __( 'Australian Dollar', 'ppp-stripe' ),
				'symbol_left'        => 'A$',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'AWG' => array(
				'name'               => __( 'Aruban Florin', 'ppp-stripe' ),
				'symbol_left'        => '&#402;',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'AZN' => array(
				'name'               => __( 'Azerbaijani Manat', 'ppp-stripe' ),
				'symbol_left'        => '&#1084;&#1072;&#1085;',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'BAM' => array(
				'name'               => __( 'Bosnia & Herzegovina Convertible Mark', 'ppp-stripe' ),
				'symbol_left'        => 'KM',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'BBD' => array(
				'name'               => __( 'Barbadian Dollar', 'ppp-stripe' ),
				'symbol_left'        => 'Bbd$',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'BDT' => array(
				'name'               => __( 'Bangladeshi Taka', 'ppp-stripe' ),
				'symbol_left'        => '&#2547;',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'BGN' => array(
				'name'               => __( 'Bulgarian Lev', 'ppp-stripe' ),
				'symbol_left'        => '&#1083;&#1074;',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'BIF' => array(
				'name'               => __( 'Burundian Franc', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'BIF',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 0,
				'american_express'   => true
			),
			'BMD' => array(
				'name'               => __( 'Bermudian Dollar', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'BMD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'BND' => array(
				'name'               => __( 'Brunei Dollar', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'BND',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'BOB' => array(
				'name'               => __( 'Bolivian Boliviano', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'BOB',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'BRL' => array(
				'name'               => __( 'Brazilian Real', 'ppp-stripe' ),
				'symbol_left'        => 'R$',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'BSD' => array(
				'name'               => __( 'Bahamian Dollar', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'BSD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'BWP' => array(
				'name'               => __( 'Botswana Pula', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'BWP',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'BZD' => array(
				'name'               => __( 'Belize Dollar', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'BZD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'CAD' => array(
				'name'               => __( 'Canadian Dollar', 'ppp-stripe' ),
				'symbol_left'        => 'CAD$',
				'symbol_right'       => 'CAD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'CDF' => array(
				'name'               => __( 'Congolese Franc', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'CDF',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'CHF' => array(
				'name'               => __( 'Swiss Franc', 'ppp-stripe' ),
				'symbol_left'        => 'Fr',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => "'",
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'CLP' => array(
				'name'               => __( 'Chilean Peso', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'CLP',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 0,
				'american_express'   => false
			),
			'CNY' => array(
				'name'               => __( 'Chinese Renminbi Yuan', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'CNY',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'COP' => array(
				'name'               => __( 'Colombian Peso', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'COP',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'CRC' => array(
				'name'               => __( 'Costa Rican Colón', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'CRC',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'CVE' => array(
				'name'               => __( 'Cape Verdean Escudo', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'CVE',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'CZK' => array(
				'name'               => __( 'Czech Koruna', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => '&#75;&#269;',
				'symbol_padding'     => ' ',
				'thousand_separator' => ' ',
				'decimal_separator'  => ',',
				'decimals'           => 2,
				'american_express'   => false
			),
			'DJF' => array(
				'name'               => __( 'Djiboutian Franc', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'DJF',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 0,
				'american_express'   => false
			),
			'DKK' => array(
				'name'               => __( 'Danish Krone', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'kr.',
				'symbol_padding'     => ' ',
				'thousand_separator' => '.',
				'decimal_separator'  => ',',
				'decimals'           => 2,
				'american_express'   => true
			),
			'DOP' => array(
				'name'               => __( 'Dominican Peso', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'DOP',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'DZD' => array(
				'name'               => __( 'Algerian Dinar', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'DZD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'EEK' => array(
				'name'               => __( 'Estonian Kroon', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'EEK',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'EGP' => array(
				'name'               => __( 'Egyptian Pound', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'EGP',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'ETB' => array(
				'name'               => __( 'Ethiopian Birr', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'ETB',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'EUR' => array(
				'name'               => __( 'Euro', 'ppp-stripe' ),
				'symbol_left'        => '&#8364;',
				'symbol_right'       => '',
				'symbol_padding'     => '',
				'thousand_separator' => ' ',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'FJD' => array(
				'name'               => __( 'Fijian Dollar', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'FJD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'FKP' => array(
				'name'               => __( 'Falkland Islands Pound', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'FKP',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'GBP' => array(
				'name'               => __( 'British Pound', 'ppp-stripe' ),
				'symbol_left'        => '&#163;',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'GEL' => array(
				'name'               => __( 'Georgian Lari', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'GEL',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'GIP' => array(
				'name'               => __( 'Gibraltar Pound', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'GIP',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'GMD' => array(
				'name'               => __( 'Gambian Dalasi', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'GMD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'GNF' => array(
				'name'               => __( 'Guinean Franc', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'GNF',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 0,
				'american_express'   => false
			),
			'GTQ' => array(
				'name'               => __( 'Guatemalan Quetzal', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'GTQ',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'GYD' => array(
				'name'               => __( 'Guyanese Dollar', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'GYD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'HKD' => array(
				'name'               => __( 'Hong Kong Dollar', 'ppp-stripe' ),
				'symbol_left'        => 'HK$',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'HNL' => array(
				'name'               => __( 'Honduran Lempira', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'HNL',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'HRK' => array(
				'name'               => __( 'Croatian Kuna', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'HRK',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'HTG' => array(
				'name'               => __( 'Haitian Gourde', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'HTG',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'HUF' => array(
				'name'               => __( 'Hungarian Forint', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'Ft',
				'symbol_padding'     => ' ',
				'thousand_separator' => '.',
				'decimal_separator'  => ',',
				'decimals'           => 2,
				'american_express'   => true
			),
			'IDR' => array(
				'name'               => __( 'Indonesian Rupiah', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'IDR',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'ILS' => array(
				'name'               => __( 'Israeli New Sheqel', 'ppp-stripe' ),
				'symbol_left'        => '&#8362;',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'INR' => array(
				'name'               => __( 'Indian Rupee', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'INR',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'ISK' => array(
				'name'               => __( 'Icelandic Króna', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'ISK',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'JMD' => array(
				'name'               => __( 'Jamaican Dollar', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'JMD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'JPY' => array(
				'name'               => __( 'Japanese Yen', 'ppp-stripe' ),
				'symbol_left'        => '&#165;',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '',
				'decimals'           => 0,
				'american_express'   => true
			),
			'KES' => array(
				'name'               => __( 'Kenyan Shilling', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'KES',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'KGS' => array(
				'name'               => __( 'Kyrgyzstani Som', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'KGS',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'KHR' => array(
				'name'               => __( 'Cambodian Riel', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'KHR',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'KMF' => array(
				'name'               => __( 'Comorian Franc', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'KMF',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 0,
				'american_express'   => true
			),
			'KRW' => array(
				'name'               => __( 'South Korean Won', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'KRW',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 0,
				'american_express'   => true
			),
			'KYD' => array(
				'name'               => __( 'Cayman Islands Dollar', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'KYD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'KZT' => array(
				'name'               => __( 'Kazakhstani Tenge', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'KZT',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'LAK' => array(
				'name'               => __( 'Lao Kip', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'LAK',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'LBP' => array(
				'name'               => __( 'Lebanese Pound', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'LBP',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'LKR' => array(
				'name'               => __( 'Sri Lankan Rupee', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'LKR',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'LRD' => array(
				'name'               => __( 'Liberian Dollar', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'LRD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'LSL' => array(
				'name'               => __( 'Lesotho Loti', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'LSL',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'LTL' => array(
				'name'               => __( 'Lithuanian Litas', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'LTL',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'LVL' => array(
				'name'               => __( 'Latvian Lats', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'LVL',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'MAD' => array(
				'name'               => __( 'Moroccan Dirham', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'MAD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'MDL' => array(
				'name'               => __( 'Moldovan Leu', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'MDL',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'MGA' => array(
				'name'               => __( 'Malagasy Ariary', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'MGA',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 0,
				'american_express'   => true
			),
			'MKD' => array(
				'name'               => __( 'Macedonian Denar', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'MKD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'MNT' => array(
				'name'               => __( 'Mongolian Tögrög', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'MNT',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'MOP' => array(
				'name'               => __( 'Macanese Pataca', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'MOP',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'MRO' => array(
				'name'               => __( 'Mauritanian Ouguiya', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'MRO',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'MUR' => array(
				'name'               => __( 'Mauritian Rupee', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'MUR',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'MVR' => array(
				'name'               => __( 'Maldivian Rufiyaa', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'MVR',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'MWK' => array(
				'name'               => __( 'Malawian Kwacha', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'MWK',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'MXN' => array(
				'name'               => __( 'Mexican Peso', 'ppp-stripe' ),
				'symbol_left'        => 'MXN$',
				'symbol_right'       => 'MXN',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'MYR' => array(
				'name'               => __( 'Malaysian Ringgit', 'ppp-stripe' ),
				'symbol_left'        => '&#82;&#77;',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'MZN' => array(
				'name'               => __( 'Mozambican Metical', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'MZN',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'NAD' => array(
				'name'               => __( 'Namibian Dollar', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'NAD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'NGN' => array(
				'name'               => __( 'Nigerian Naira', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'NGN',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'NIO' => array(
				'name'               => __( 'Nicaraguan Córdoba', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'NIO',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'NOK' => array(
				'name'               => __( 'Norwegian Krone', 'ppp-stripe' ),
				'symbol_left'        => 'Kr',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'NPR' => array(
				'name'               => __( 'Nepalese Rupee', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'NPR',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'NZD' => array(
				'name'               => __( 'New Zealand Dollar', 'ppp-stripe' ),
				'symbol_left'        => 'NZ$',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'PAB' => array(
				'name'               => __( 'Panamanian Balboa', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'PAB',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'PEN' => array(
				'name'               => __( 'Peruvian Nuevo Sol', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'PEN',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'PGK' => array(
				'name'               => __( 'Papua New Guinean Kina', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'PGK',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'PHP' => array(
				'name'               => __( 'Philippine Peso', 'ppp-stripe' ),
				'symbol_left'        => '&#8369;',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'PKR' => array(
				'name'               => __( 'Pakistani Rupee', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'PKR',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'PLN' => array(
				'name'               => __( 'Polish Złoty', 'ppp-stripe' ),
				'symbol_left'        => '&#122;&#322;',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => '.',
				'decimal_separator'  => ',',
				'decimals'           => 2,
				'american_express'   => true
			),
			'PYG' => array(
				'name'               => __( 'Paraguayan Guaraní', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'PYG',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 0,
				'american_express'   => false
			),
			'QAR' => array(
				'name'               => __( 'Qatari Riyal', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'QAR',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'RON' => array(
				'name'               => __( 'Romanian Leu', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'RON',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'RSD' => array(
				'name'               => __( 'Serbian Dinar', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'RSD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'RUB' => array(
				'name'               => __( 'Russian Ruble', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'RUB',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'RWF' => array(
				'name'               => __( 'Rwandan Franc', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'RWF',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 0,
				'american_express'   => true
			),
			'SAR' => array(
				'name'               => __( 'Saudi Riyal', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'SAR',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'SBD' => array(
				'name'               => __( 'Solomon Islands Dollar', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'SBD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'SCR' => array(
				'name'               => __( 'Seychellois Rupee', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'SCR',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'SEK' => array(
				'name'               => __( 'Swedish Krona', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'kr',
				'symbol_padding'     => ' ',
				'thousand_separator' => ' ',
				'decimal_separator'  => ',',
				'decimals'           => 2,
				'american_express'   => true
			),
			'SGD' => array(
				'name'               => __( 'Singapore Dollar', 'ppp-stripe' ),
				'symbol_left'        => 'S$',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'SHP' => array(
				'name'               => __( 'Saint Helenian Pound', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'SHP',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'SLL' => array(
				'name'               => __( 'Sierra Leonean Leone', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'SLL',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'SOS' => array(
				'name'               => __( 'Somali Shilling', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'SOS',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'SRD' => array(
				'name'               => __( 'Surinamese Dollar', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'SRD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'STD' => array(
				'name'               => __( 'São Tomé and Príncipe Dobra', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'STD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'SVC' => array(
				'name'               => __( 'Salvadoran Colón', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'SVC',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'SZL' => array(
				'name'               => __( 'Swazi Lilangeni', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'SZL',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'THB' => array(
				'name'               => __( 'Thai Baht', 'ppp-stripe' ),
				'symbol_left'        => '&#3647;',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'TJS' => array(
				'name'               => __( 'Tajikistani Somoni', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'TJS',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'TOP' => array(
				'name'               => __( 'Tongan Paʻanga', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'TOP',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'TRY' => array(
				'name'               => __( 'Turkish Lira', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'TRY',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'TTD' => array(
				'name'               => __( 'Trinidad and Tobago Dollar', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'TTD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'TWD' => array(
				'name'               => __( 'New Taiwan Dollar', 'ppp-stripe' ),
				'symbol_left'        => 'NT$',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'TZS' => array(
				'name'               => __( 'Tanzanian Shilling', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'TZS',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'UAH' => array(
				'name'               => __( 'Ukrainian Hryvnia', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'UAH',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'UGX' => array(
				'name'               => __( 'Ugandan Shilling', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'UGX',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'UYU' => array(
				'name'               => __( 'Uruguayan Peso', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'UYU',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'UZS' => array(
				'name'               => __( 'Uzbekistani Som', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'UZS',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'VEF' => array(
				'name'               => __( 'Venezuelan Bolívar', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'VEF',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'VND' => array(
				'name'               => __( 'Vietnamese Đồng', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'VND',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'VUV' => array(
				'name'               => __( 'Vanuatu Vatu', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'VUV',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 0,
				'american_express'   => true
			),
			'WST' => array(
				'name'               => __( 'Samoan Tala', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'WST',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'XAF' => array(
				'name'               => __( 'Central African Cfa Franc', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'XAF',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 0,
				'american_express'   => true
			),
			'XCD' => array(
				'name'               => __( 'East Caribbean Dollar', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'XCD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'XOF' => array(
				'name'               => __( 'West African Cfa Franc', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'XOF',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 0,
				'american_express'   => false
			),
			'XPF' => array(
				'name'               => __( 'Cfp Franc', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'XPF',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 0,
				'american_express'   => false
			),
			'YER' => array(
				'name'               => __( 'Yemeni Rial', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'YER',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'ZAR' => array(
				'name'               => __( 'South African Rand', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'ZAR',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'ZMW' => array(
				'name'               => __( 'Zambian Kwacha', 'ppp-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'ZMW',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			)
		);
	}

	public static function get_zero_decimal_currencies() {

		$stripe_currencies = self::get_stripe_currencies();

		$zero_decimal_currencies = array();

		foreach ( $stripe_currencies as $currency_code => $currency_info ) {

			if ( 0 == $currency_info[ 'decimals' ] ) {
				$zero_decimal_currencies[ ] = $currency_code;
			}

		}

		return $zero_decimal_currencies;
	}

	public static function get_american_express_currencies() {

		$stripe_currencies = self::get_stripe_currencies();

		$american_express_currencies = array();

		foreach ( $stripe_currencies as $currency_code => $currency_info ) {

			if ( true == $currency_info[ 'american_express' ] ) {
				$american_express_currencies[ ] = $currency_code;
			}

		}

		return $american_express_currencies;
	}

	public static function is_zero_decimal_currency( $currency_code ) {

		return in_array( $currency_code, self::get_zero_decimal_currencies() );
	}

	/********************
	 * ERROR MESSAGES   *
	 ********************/

	/**
	 * Parse error and return a "pretty" error message to display to customers
	 *
	 * @since 1.0.0
	 *
	 * @param      $e
	 * @param bool $mode
	 *
	 * @return mixed|void
	 */
	public static function create_error_message( $e, $mode = false ) {

		$error_class   = get_class( $e );
		$error_message = $e->getMessage();
		$response      = $error_class . ': ' . $error_message;

		self::$logger->log->error( print_r( $response, true ) );

		if ( ! $mode ) {
			$settings = get_option( self::$settings_option_name );
			$mode     = isset( $settings[ 'mode' ] ) ? $settings[ 'mode' ] : '';
		}

		if ( 'live' == $mode ) {

			switch ( $error_class ) {

				case 'PPP\\Stripe\\Error\\InvalidRequest':
					$error_message = 'Unable to process your payment. Please contact site owner.';
					break;
				case 'PPP\\Stripe\\Error\\ApiConnection':
					$error_message = 'There was a temporary network communication error and while we try to make sure these never happen, sometimes they do. Please try your payment again in a few minutes and if this continues, please contact site owner.';
					break;
				case 'PPP\\Stripe\\Error\\Card':
					break;
				default:
					$error_message = 'Unable to process your payment. Please contact site owner.';
			}

		}

		return apply_filters( 'ppp_stripe_error_message', $error_message, self::$plugin_slug, $e );
	}

	/********************
	 * VALIDATION       *
	 ********************/

	public static function validate_key( $api_key ) {

		self::$logger->log->debug( "Validating API key..." );

		$is_valid = false;

		$token = self::create_card_token( $api_key, array(
			'card' => array(
				'number'    => '4242424242424242',
				'exp_month' => 3,
				'exp_year'  => date( 'Y' ) + 1,
				'cvc'       => 314
			)
		), false );

		if ( is_a( $token, 'Exception' ) ) {

			$error       = $token;
			$error_class = get_class( $error );

			if ( 'PPP\\Stripe\\Error\\Card' == $error_class ) {
				$is_valid = true;
			}

		} else {

			$is_valid = true;

		}

		return $is_valid;
	}

	public static function validate_coupon( $api_key, $coupon_id ) {

		self::$logger->log->debug( "Validating coupon..." );

		self::include_api();

		$coupon = self::retrieve( 'coupon', $api_key, $coupon_id );

		if ( is_a( $coupon, 'PPP\\Stripe\\Coupon' ) ) {

			$result = array( 'status' => 'success', 'data' => $coupon );
			GFP_Stripe::log_debug( "Coupon {$coupon['id']} validated." );

		} else {

			$result = array( 'status' => 'error', 'data' => $coupon );

		}

		return $result;
	}

	/********************
	 * CHARGES          *
	 ********************/

	public static function create_charge( $api_key, $args ) {

		self::$logger->log->debug( "Creating a charge" );

		$arguments = array();
		$amount    = $currency = $customer = $source = $description = $metadata = $statement_description = $application_fee = false;
		$capture   = true;

		if ( empty( $args ) ) {

			self::$logger->log->error( "No arguments passed to create charge request" );

			return __( 'Unable to process your request' );

		} else {

			extract( $args );

			$arguments[ 'amount' ] = $amount;

			$arguments[ 'currency' ] = $currency;

			if ( $customer ) {
				$arguments[ 'customer' ] = $customer;
				$arguments[ 'expand' ]   = array( 'customer.default_source' );
			}

			if ( $source ) {
				$arguments[ 'source' ] = $source;
			}

			if ( $description ) {
				$arguments[ 'description' ] = $description;
			}

			if ( $metadata ) {
				$arguments[ 'metadata' ] = $metadata;
			}

			if ( isset( $capture ) ) {
				$arguments[ 'capture' ] = $capture;
			}

			if ( $statement_description ) {
				$arguments[ 'statement_description' ] = $statement_description;
			}

			if ( $application_fee ) {
				$arguments[ 'application_fee' ] = $application_fee;
			}

			self::include_api();

			try {

				$charge = PPP\Stripe\Charge::create( $arguments, $api_key );

			} catch ( Exception $e ) {

				self::$logger->log->error( 'Charge creation failed' );

				$error_message = self::create_error_message( $e, self::$mode );

				return $error_message;

			}

			self::$logger->log->debug( "Charge created successfully. ID: {$charge['id']}" );

			return $charge;

		}

	}

	/**
	 * Update Stripe charge
	 *
	 * @since 1.0.0
	 *
	 * @param string            $api_key
	 * @param PPP\Stripe\Charge $charge
	 * @param array             $args
	 *
	 * @return PPP\Stripe\Charge|string
	 */
	public static function update_charge( $api_key, $charge, $args ) {

		self::$logger->log->debug( 'Updating charge' );

		self::include_api();

		PPP\Stripe\Stripe::setApiKey( $api_key );

		$description = $metadata = false;

		if ( ! empty( $args ) ) {

			extract( $args );

			try {

				if ( $description ) {
					$charge->description = $description;
				}

				if ( $metadata ) {
					$charge->metadata = $metadata;
				}

				$charge = $charge->save();

			} catch ( Exception $e ) {

				self::$logger->log->error( 'Updating charge failed' );

				$error_message = self::create_error_message( $e, self::$mode );

				return $error_message;

			}

			self::$logger->log->debug( 'Charge update successful' );

			return $charge;

		} else {

			self::$logger->log->error( 'No arguments passed to update charge request' );

			return __( 'Unable to process your request' );

		}

	}

	/**
	 * Capture Stripe charge
	 *
	 * @since 1.0.0
	 *
	 * @param string            $api_key
	 * @param PPP\Stripe\Charge $charge
	 *
	 * @param array             $args
	 *
	 * @return PPP\Stripe\Charge|string
	 */
	public static function capture_charge( $api_key, $charge, $args ) {

		self::$logger->log->debug( 'Capturing charge' );

		self::include_api();

		PPP\Stripe\Stripe::setApiKey( $api_key );

		try {

			if ( ! empty( $args ) ) {

				$charge = $charge->capture( $args );

			} else {

				$charge = $charge->capture();

			}

		} catch ( Exception $e ) {

			self::$logger->log->error( 'Capturing charge failed' );

			$error_message = self::create_error_message( $e, self::$mode );

			return $error_message;

		}

		self::$logger->log->debug( 'Charge successfully captured' );

		return $charge;

	}

	/********************
	 * REFUNDS          *
	 ********************/

	/**
	 * Create Stripe refund
	 *
	 * @since                  1.0.0
	 *
	 * @param            $api_key
	 * @param            $charge_id
	 * @param null|array $args {
	 *
	 * @type int         $amount
	 * @type bool        $refund_application_fee
	 * @type array       $metadata
	 *                         }
	 *
	 * @return string|PPP\Stripe\Refund
	 */
	public static function create_refund( $api_key, $charge_id, $args = null ) {

		self::$logger->log->debug( 'Refunding charge' );

		$amount = $refund_application_fee = $metadata = false;

		if ( ! empty( $args ) ) {

			extract( $args );

			if ( $amount ) {
				$arguments[ 'amount' ] = $amount;
			}

			if ( $refund_application_fee ) {
				$arguments[ 'refund_application_fee' ] = $refund_application_fee;
			}

			if ( $metadata ) {
				$arguments[ 'metadata' ] = $metadata;
			}
		}

		self::include_api();

		PPP\Stripe\Stripe::setApiKey( $api_key );

		try {

			$charge = self::retrieve( 'charge', $api_key, $charge_id );

			if ( empty( $arguments ) ) {

				$refund = $charge->refunds->create();

			} else {

				$refund = $charge->refunds->create( $arguments );

			}

		} catch ( Exception $e ) {

			self::$logger->log->error( 'Creating refund failed' );

			$error_message = self::create_error_message( $e, self::$mode );

			return $error_message;

		}

		self::$logger->log->debug( 'Refund successful' );

		return $refund;
	}

	/********************
	 * CUSTOMERS        *
	 ********************/

	/**
	 * Create Stripe customer
	 *
	 * @since 1.0.0
	 *
	 * @param $api_key
	 * @param $args
	 *
	 * @return mixed|\PPP\Stripe\Customer|string|void
	 */
	public static function create_customer( $api_key, $args ) {

		self::$logger->log->debug( "Creating the customer" );

		$arguments       = array();
		$account_balance = $source = $description = $email = $metadata = $plan = $quantity = $trial_end = false;

		if ( empty( $args ) ) {

			self::$logger->log->error( 'No arguments passed to create customer request' );

			return __( 'Unable to process your request' );

		} else {

			extract( $args );

			if ( $account_balance ) {
				$arguments[ 'account_balance' ] = $account_balance;
			}

			if ( $source ) {
				$arguments[ 'source' ] = $source;
			}

			if ( $description ) {
				$arguments[ 'description' ] = $description;
			}

			if ( $email ) {
				$arguments[ 'email' ] = $email;
			}

			if ( $metadata ) {
				$arguments[ 'metadata' ] = $metadata;
			}

			if ( $plan ) {
				$arguments[ 'plan' ] = $plan;
			}

			if ( $quantity ) {
				$arguments[ 'quantity' ] = $quantity;
			}

			if ( $trial_end ) {
				$arguments[ 'trial_end' ] = $trial_end;
			}

			$arguments[ 'expand' ] = array( 'default_source' );

			self::include_api();

			try {

				$customer = PPP\Stripe\Customer::create( $arguments, $api_key );

			} catch ( Exception $e ) {

				self::$logger->log->error( 'Customer failed' );

				$error_message = self::create_error_message( $e, self::$mode );

				return $error_message;

			}

			self::$logger->log->debug( "Customer created successfully. ID: {$customer['id']}" );

			return $customer;
		}
	}

	/**
	 * Update Stripe customer
	 *
	 * @since 1.0.0
	 *
	 * @param $api_key
	 * @param $customer
	 * @param $args
	 *
	 * @return mixed|void
	 */
	public static function update_customer( $api_key, $customer, $args ) {

		self::$logger->log->debug( 'Updating customer' );

		self::include_api();
		PPP\Stripe\Stripe::setApiKey( $api_key );

		$account_balance = $source = $coupon = $default_source = $description = $email = $metadata = false;

		if ( ! empty( $args ) ) {

			extract( $args );

			try {

				if ( $account_balance ) {
					$customer->account_balance = $account_balance;
				}

				if ( $source ) {
					$customer->source = $source;
				}

				if ( $default_source ) { //TODO check to see if still applicable
					$customer->default_source = $default_source;
				}

				if ( $description ) {
					$customer->description = $description;
				}

				if ( $email ) {
					$customer->email = $email;
				}

				if ( $metadata ) {
					$customer->metadata = $metadata;
				}

				$customer = $customer->save();

			} catch ( Exception $e ) {

				self::$logger->log->error( 'Updating customer failed' );

				$error_message = self::create_error_message( $e, self::$mode );

				return $error_message;

			}

			self::$logger->log->debug( "Customer update successful" );

			return $customer;

		} else {

			self::$logger->log->error( "No arguments passed to update customer request" );

			return __( 'Unable to process your request' );

		}

	}

	public static function save_customer() {
	}

	public static function delete_customer( $api_key, $customer ) {

		self::$logger->log->debug( 'Deleting customer...' );

		self::include_api();

		try {

			$customer = $customer->delete();

		} catch ( Exception $e ) {

			self::$logger->log->error( 'Deleting customer failed' );

			$error_message = self::create_error_message( $e, self::$mode );

			return $error_message;

		}

		return $customer;

	}

	public static function list_customers( $api_key, $args ) {

		self::$logger->log->debug( "Retrieving a list of customers" );

		$arguments = array();

		$created = $ending_before = $limit = $starting_after = $include = false;

		if ( ! empty( $args ) ) {
			extract( $args );
		}

		if ( $created ) {
			$arguments[ 'created' ] = $created;
		}

		if ( $ending_before ) {
			$arguments[ 'ending_before' ] = $ending_before;
		}

		if ( $limit ) {
			$arguments[ 'limit' ] = $limit;
		}

		if ( $starting_after ) {
			$arguments[ 'starting_after' ] = $starting_after;
		}

		if ( $include ) {
			$arguments[ 'include' ] = $include;
		}

		self::include_api();

		try {

			if ( ! empty( $arguments ) ) {

				$customers = PPP\Stripe\Customer::all( $arguments, $api_key );

			} else {

				$customers = PPP\Stripe\Customer::all( $api_key );

			}

		} catch ( Exception $e ) {

			self::$logger->log->error( 'Unable to retrieve list of customers' );
			$error_message = self::create_error_message( $e, self::$mode );

			return $error_message;

		}

		self::$logger->log->debug( "Customers retrieved" );

		return $customers;
	}

	/********************
	 * CARDS            *
	 ********************/

	public static function create_card( $api_key, $customer, $card ) {

		self::$logger->log->debug( "Creating card..." );

		self::include_api();

		try {

			$card = $customer->sources->create( array( 'source' => $card ), $api_key );

		} catch ( Exception $e ) {

			self::$logger->log->error( 'Creating card failed' );

			$error_message = self::create_error_message( $e, self::$mode );

			return $error_message;
		}

		return $card;
	}

	public static function delete_card( $api_key, $id, $customer ) {

		self::$logger->log->debug( "Deleting card..." );

		self::include_api();

		try {

			$card = $customer->sources->retrieve( $id )->delete();

		} catch ( Exception $e ) {

			self::$logger->log->error( 'Deleting card failed' );

			$error_message = self::create_error_message( $e, self::$mode );

			return $error_message;

		}

		return $card;
	}

	public static function retrieve_card( $api_key, $customer, $card_id ) {

		self::$logger->log->debug( "Retrieving card..." );

		self::include_api();

		try {

			$card = $customer->sources->retrieve( $card_id );

		} catch ( Exception $e ) {

			self::$logger->log->error( 'Retrieving card failed' );

			$error_message = self::create_error_message( $e, self::$mode );

			return $error_message;

		}

		return $card;
	}

	public static function retrieve_all_cards() {
	}

	/********************
	 * PLANS            *
	 ********************/

	/**
	 * Create Stripe plan
	 *
	 * @since 1.0.0
	 *
	 * @param $api_key
	 * @param $args
	 *
	 * @return mixed|\PPP\Stripe\Plan|string|void
	 */
	public static function create_plan( $api_key, $args ) {

		self::$logger->log->debug( "Creating plan" );

		$arguments = array();
		$id        = $amount = $currency = $interval = $interval_count = $name = $trial_period_days = $metadata = $statement_description = false;

		if ( empty( $args ) ) {
			self::$logger->log->error( 'No arguments passed to create plan request' );

			return __( 'Unable to process your request' );
		} else {

			extract( $args );

			if ( $id ) {
				$arguments[ 'id' ] = (string) $id;
			}
			if ( $amount ) {
				$arguments[ 'amount' ] = (int) $amount;
			}
			if ( $currency ) {
				$arguments[ 'currency' ] = (string) $currency;
			}
			if ( $interval ) {
				$arguments[ 'interval' ] = (string) $interval;
			}
			if ( $interval_count ) {
				$arguments[ 'interval_count' ] = (int) $interval_count;
			}
			if ( $name ) {
				$arguments[ 'name' ] = (string) $name;
			}
			if ( $metadata ) {
				$arguments[ 'metadata' ] = $metadata;
			}
			if ( $trial_period_days ) {
				$arguments[ 'trial_period_days' ] = (int) $trial_period_days;
			}
			if ( $statement_description ) {
				$arguments[ 'statement_description' ] = $statement_description;
			}

			self::include_api();

			try {
				$plan = PPP\Stripe\Plan::create( $arguments, $api_key );

			} catch ( Exception $e ) {
				self::$logger->log->error( 'Plan creation failed' );
				$error_message = self::create_error_message( $e, self::$mode );

				return $error_message;
			}

			return $plan;
		}
	}

	/**
	 * Retrieve Stripe plan
	 *
	 * @since 1.0.0
	 *
	 * @param $api_key
	 * @param $id      Plan ID
	 *
	 * @return mixed|void
	 */
	public static function retrieve_plan( $api_key, $id ) {

		self::$logger->log->debug( "Retrieving plan" );

		self::include_api();

		try {
			$plan = PPP\Stripe\Plan::retrieve( array(
				                                   'id' => (string) $id
			                                   ), $api_key );

		} catch ( Exception $e ) {
			self::$logger->log->error( 'Unable to retrieve plan' );
			$error_message = self::create_error_message( $e, self::$mode );

			return $error_message;
		}

		self::$logger->log->debug( 'Found plan' );

		return $plan;
	}

	public static function delete_plan( $api_key, $plan ) {

		self::$logger->log->debug( 'Deleting plan...' );

		self::include_api();

		try {
			$plan = $plan->delete();
		} catch ( Exception $e ) {
			self::$logger->log->error( 'Deleting plan failed' );
			$error_message = self::create_error_message( $e, self::$mode );

			return $error_message;
		}

		return $plan;
	}

	/********************
	 * INVOICES         *
	 ********************/

	public static function create_invoice( $api_key, $customer_id, $args = null ) {

		self::$logger->log->debug( 'Creating invoice...' );

		$application_fee         = $subscription = false;
		$arguments[ 'customer' ] = $customer_id;

		extract( $args );

		if ( $application_fee ) {
			$arguments[ 'application_fee' ] = $application_fee;
		}
		if ( $subscription ) {
			$arguments[ 'subscription' ] = $subscription;
		}

		self::include_api();

		try {

			$invoice = PPP\Stripe\Invoice::create( $arguments, $api_key );

		} catch ( Exception $e ) {

				self::$logger->log->error( 'Creating invoice failed' );

			$error_message = self::create_error_message( $e, self::$mode );

			return $error_message;
		}

		return $invoice;
	}

	public static function pay_invoice( $api_key, $invoice ) {

		self::$logger->log->debug( 'Paying invoice...' );

		self::include_api();

		try {

				$invoice = $invoice->pay();

		} catch ( Exception $e ) {

				self::$logger->log->error( 'Paying invoice failed' );

			$error_message = self::create_error_message( $e, self::$mode );

			return $error_message;
		}

		return $invoice;
	}

	public static function list_invoices( $api_key, $args ) {

		self::$logger->log->debug( "Retrieving a list of invoices" );

		$arguments = array();
		$customer  = $date = $ending_before = $limit = $starting_after = false;

		if ( ! empty( $args ) ) {
			extract( $args );
		}

		if ( $customer ) {
			$arguments[ 'customer' ] = $customer;
		}
		if ( $date ) {
			$arguments[ 'date' ] = $date;
		}
		if ( $ending_before ) {
			$arguments[ 'ending_before' ] = $ending_before;
		}
		if ( $limit ) {
			$arguments[ 'limit' ] = $limit;
		}
		if ( $starting_after ) {
			$arguments[ 'starting_after' ] = $starting_after;
		}

		self::include_api();

		try {

			if ( ! empty( $arguments ) ) {

					$invoices = PPP\Stripe\Invoice::all( $arguments, $api_key );

			} else {

					$invoices = PPP\Stripe\Invoice::all( $api_key );

			}

		} catch ( Exception $e ) {

				self::$logger->log->error( 'Unable to retrieve list of invoices' );

			$error_message = self::create_error_message( $e, self::$mode );

			return $error_message;
		}

		self::$logger->log->debug( "Invoices retrieved" );

		return $invoices;
	}

	public static function retrieve_upcoming_invoice( $api_key, $customer, $subscription = false ) {

		self::$logger->log->debug( "Retrieving upcoming invoice for customer {$customer}" );

		$arguments[ 'customer' ] = $customer;
		if ( $subscription ) {
			$arguments[ 'subscription' ] = $subscription;
		}

		self::include_api();

		try {

			$invoice = PPP\Stripe\Invoice::upcoming( $arguments, $api_key );

		} catch ( Exception $e ) {

			self::$logger->log->error( 'Unable to retrieve upcoming invoice' );

			$error_message = self::create_error_message( $e, self::$mode );

			return $error_message;
		}

		self::$logger->log->debug( 'Found upcoming invoice' );

		return $invoice;
	}

	public static function create_invoice_item( $api_key, $customer_id, $args ) {

		self::$logger->log->debug( "Creating invoice item" );

		$amount    = $currency = $invoice = $subscription = $description = $metadata = false;
		$arguments = array();

		if ( ! empty( $args ) ) {
			extract( $args );

			$arguments[ 'customer' ] = $customer_id;
			if ( $amount ) {
				$arguments[ 'amount' ] = $amount;
			}
			if ( $currency ) {
				$arguments[ 'currency' ] = $currency;
			}
			if ( $invoice ) {
				$arguments[ 'invoice' ] = $invoice;
			}
			if ( $subscription ) {
				$arguments[ 'subscription' ] = $subscription;
			}
			if ( $description ) {
				$arguments[ 'description' ] = $description;
			}
			if ( $metadata ) {
				$arguments[ 'metadata' ] = $metadata;
			}


			self::include_api();

			try {

				$invoice_item = PPP\Stripe\InvoiceItem::create( $arguments, $api_key );

			} catch ( Exception $e ) {

				self::$logger->log->error( 'Invoice item creation failed' );

				$error_message = self::create_error_message( $e, self::$mode );

				return $error_message;
			}

			self::$logger->log->debug( "Invoice item {$invoice_item['id']} created" );

			return $invoice_item;

		} else {

			self::$logger->log->error( "No arguments passed to create invoice item request" );

			return __( 'Unable to process your request' );

		}
	}

	/********************
	 * SUBSCRIPTIONS    *
	 ********************/

	/**
	 * Create Stripe subscription
	 *
	 * @since 1.0.0
	 *
	 * @uses  PPP\Stripe\Util::convertToStripeObject()
	 *
	 * @param $api_key
	 * @param $customer    Stripe Customer object
	 * @param $args        API call arguments
	 *
	 * @return array|mixed|string|void
	 */
	public static function create_subscription( $api_key, $customer, $args ) {

		$arguments       = array();
		$plan            = $coupon = $trial_end = $source = $quantity = $application_fee_percent = $tax_percent = $metadata = $max_occurrences = $billing_cycle_anchor = false;
		$alternative_api = false;

		if ( empty( $args ) ) {

			self::$logger->log->error( "No arguments passed to create subscription request" );

			return __( 'Unable to process your request', 'ppp-stripe' );

		} else {

			extract( $args );

			if ( $plan ) {

				$arguments[ 'plan' ] = $plan;

			} else {

				self::$logger->log->error( "No plan ID given." );

				return __( 'Unable to process your request', 'ppp-stripe' );
			}

			if ( $coupon ) {
				$arguments[ 'coupon' ] = $coupon;
			}

			if ( $trial_end ) {
				$arguments[ 'trial_end' ] = $trial_end;
			}

			if ( $source ) {
				$arguments[ 'source' ] = $source;
			}

			if ( $quantity ) {
				$arguments[ 'quantity' ] = $quantity;
			}

			if ( $metadata ) {
				$arguments[ 'metadata' ] = $metadata;
			}

			if ( $application_fee_percent ) {
				$arguments[ 'application_fee_percent' ] = $application_fee_percent;
			}

			if ( $tax_percent ) {
				$arguments[ 'tax_percent' ] = $tax_percent;
			}

			if ( $max_occurrences ) {
				$arguments[ 'max_occurrences' ] = $max_occurrences;
			}

			if ( $billing_cycle_anchor ) {
				$arguments[ 'billing_cycle_anchor' ] = $billing_cycle_anchor;
			}

			$arguments[ 'expand' ] = array( 'customer.default_source' );

			self::include_api();

			if ( $alternative_api ) {

				$api_url  = "https://api.stripe.com/v1/customers/{$customer['id']}/subscriptions";
				$headers  = array( 'Authorization' => "Bearer {$api_key}" );
				$response = wp_remote_post( $api_url, array( 'headers' => $headers, 'body' => $arguments ) );

				try {

					$response     = self::_interpretResponse( wp_remote_retrieve_body( $response ), wp_remote_retrieve_response_code( $response ) );
					$subscription = PPP\Stripe\Util\Util::convertToStripeObject( $response, $api_key );

				} catch ( Exception $e ) {

					self::$logger->log->error( 'Subscription failed' );

					$error_message = self::create_error_message( $e, self::$mode );

					return $error_message;
				}

			} else {

				try {

					$subscription = $customer->subscriptions->create( $arguments, $api_key );

				} catch ( Exception $e ) {

					self::$logger->log->error( 'Subscription failed' );

					$error_message = self::create_error_message( $e, self::$mode );

					return $error_message;
				}
			}

			self::$logger->log->debug( "Subscription created successfully. ID: {$subscription['id']}" );

			return $subscription;
		}
	}

	/**
	 * Retrieve Stripe subscription
	 *
	 * @since 1.0.0
	 *
	 * @param string              $api_key
	 * @param string              $id       subscription ID
	 * @param PPP\Stripe\Customer $customer customer object
	 *
	 * @return mixed|void
	 */
	public static function retrieve_subscription( $api_key, $id, $customer ) {

		self::$logger->log->debug( "Retrieving subscription..." );

		self::include_api();

		try {

			$subscription = $customer->subscriptions->retrieve( $id, $api_key );

		} catch ( Exception $e ) {

			self::$logger->log->error( 'Retrieving subscription failed' );

			$error_message = self::create_error_message( $e, self::$mode );

			return $error_message;

		}

		return $subscription;

	}

	/**
	 * Update Stripe subscription
	 *
	 * @since 1.0.0
	 *
	 * @param string              $api_key
	 * @param PPP\Stripe\Customer $customer
	 * @param array               $args
	 *
	 * @return mixed|string|void
	 */
	public static function update_subscription( $api_key, $customer, $subscription_id, $args ) {

		self::$logger->log->debug( "Sending subscription update request" );

		$plan = $coupon = $prorate = $trial_end = $source = $quantity = $application_fee_percent = $tax_percent = $metadata = false;

		if ( empty( $args ) ) {

			self::$logger->log->error( "No arguments passed to update subscription request" );

			return __( 'Unable to process your request', 'ppp-stripe' );

		} else {

			extract( $args );

			self::include_api();
			\PPP\Stripe\Stripe::setApiKey( $api_key );

			try {

				$subscription = $customer->subscriptions->retrieve( $subscription_id );

				if ( $plan ) {
					$subscription->plan = $plan;
				}

				if ( $coupon ) {
					$subscription->coupon = $coupon;
				}

				if ( isset( $prorate ) ) {
					$subscription->prorate = $prorate;
				}

				if ( $trial_end ) {
					$subscription->trial_end = $trial_end;
				}

				if ( $source ) {
					$subscription->source = $source;
				}

				if ( $quantity ) {
					$subscription->quantity = $quantity;
				}

				if ( $application_fee_percent ) {
					$subscription->application_fee_percent = $application_fee_percent;
				}

				if ( $tax_percent ) {
					$subscription->tax_percent = $tax_percent;
				}

				if ( $metadata ) {
					$subscription->metadata = $metadata;
				}

				$subscription = $subscription->save();

			} catch ( Exception $e ) {
				self::$logger->log->error( 'Subscription update failed' );

				$error_message = self::create_error_message( $e, self::$mode );

				return $error_message;
			}

			self::$logger->log->debug( "Subscription update successful" );

			return $subscription;
		}
	}

	public static function cancel_subscription( $api_key, $customer, $id, $at_period_end = false ) {

		self::$logger->log->debug( "Sending cancel subscription request" );

		self::include_api();

		try {

			$canceled_subscription = $customer->subscriptions->retrieve( $id )->cancel( array( 'at_period_end' => $at_period_end ), $api_key );

		} catch ( Exception $e ) {

			self::$logger->log->error( 'Subscription cancelation failed' );

			$error_message = self::create_error_message( $e, self::$mode );

			return $error_message;
		}

		self::$logger->log->debug( "Subscription cancelation successful" );

		return $canceled_subscription;

	}

	public static function list_active_subscriptions( $api_key, $customer_id, $args ) {

		self::$logger->log->debug( "Retrieving a list of customer's active subscriptions" );

		$arguments     = array();
		$ending_before = $limit = $starting_after = false;

		if ( ! empty( $args ) ) {
			extract( $args );
		}

		if ( $ending_before ) {
			$arguments[ 'ending_before' ] = $ending_before;
		}

		if ( $limit ) {
			$arguments[ 'limit' ] = $limit;
		}

		if ( $starting_after ) {
			$arguments[ 'starting_after' ] = $starting_after;
		}

		self::include_api();

		try {

			if ( ! empty( $arguments ) ) {
				$subscriptions = PPP\Stripe\Customer::retrieve( $customer_id )->subscriptions->all( $arguments, $api_key );
			} else {
				$subscriptions = PPP\Stripe\Customer::retrieve( $customer_id )->subscriptions->all( $api_key );
			}

		} catch ( Exception $e ) {

			self::$logger->log->error( 'Unable to retrieve list of active subscriptions' );

			$error_message = self::create_error_message( $e, self::$mode );

			return $error_message;

		}

		self::$logger->log->debug( "Active subscriptions retrieved" );

		return $subscriptions;
	}

	public static function create_card_token( $api_key, $args, $do_error_message = true ) {

		self::$logger->log->debug( "Creating card token..." );

		self::include_api();

		try {

			$token = PPP\Stripe\Token::create(
				$args,
				$api_key
			);

		} catch ( Exception $e ) {

			self::$logger->log->error( 'Token creation failed' );

			if ( $do_error_message ) {
				$error_message = self::create_error_message( $e, self::$mode );

				return $error_message;
			} else {
				return $e;
			}

		}

		return $token;
	}

	/**********************
	 * APPLICATION FEES   *
	 **********************/

	public static function list_application_fees( $api_key, $args ) {

		self::$logger->log->debug( "Retrieving a list of application fees" );

		$arguments = array();
		$charge    = $created = $ending_before = $limit = $starting_after = false;

		if ( ! empty( $args ) ) {
			extract( $args );
		}

		if ( $charge ) {
			$arguments[ 'charge' ] = $charge;
		}
		if ( $created ) {
			$arguments[ 'created' ] = $created;
		}
		if ( $ending_before ) {
			$arguments[ 'ending_before' ] = $ending_before;
		}
		if ( $limit ) {
			$arguments[ 'limit' ] = $limit;
		}
		if ( $starting_after ) {
			$arguments[ 'starting_after' ] = $starting_after;
		}

		self::include_api();

		try {

			if ( ! empty( $arguments ) ) {
				$application_fees = PPP\Stripe\ApplicationFee::all( $arguments, $api_key );
			} else {
				$application_fees = PPP\Stripe\ApplicationFee::all( $api_key );
			}

		} catch ( Exception $e ) {

			self::$logger->log->error( 'Unable to retrieve list of application fees' );
			$error_message = self::create_error_message( $e, self::$mode );

			return $error_message;

		}

		self::$logger->log->debug( "Application fees retrieved" );

		return $application_fees;
	}

	/********************
	 * MISCELLANEOUS    *
	 ********************/

	/**
	 * Retrieve objects from Stripe API
	 *
	 * @since 1.0.0
	 *
	 * @param      $item
	 * @param      $api_key
	 * @param      $id
	 * @param null $args
	 *
	 * @return mixed|void
	 */
	public static function retrieve( $item, $api_key, $id = null, $args = null, $do_error_message = true ) {

		self::$logger->log->debug( "Retrieving {$item}" );

		self::include_api();

		if ( ! empty( $args ) ) {
			extract( $args );
		}

		switch ( $item ) {
			case 'plan':
				break;
			case 'all_plans':
				break;
			case 'customer':
				$api    = 'PPP\Stripe\Customer';
				$params = array( 'id'      => (string) $id,
				                 'expand'  => array( 'default_source' )/*,
				                 'include' => array( 'total_count' )*/
				);
				break;
			case 'all_customers':
				break;
			case 'upcoming_invoice':
				break;
			case 'all_invoices':
			case 'card':
				break;
			case 'all_cards':
				break;
			case 'coupon':
				$api    = 'PPP\Stripe\Coupon';
				$params = array( 'id' => (string) $id );
				$mode   = true;
				break;
				break;
			case 'charge':
				$api    = 'PPP\Stripe\Charge';
				$params = array( 'id' => (string) $id, 'expand' => array( 'customer' ) );
				break;
			case 'all_charges':
			case 'all_coupons':
			case 'invoice':
			case 'invoice_line_items':
			case 'invoice_items':
			case 'transfer':
			case 'all_transfers':
			case 'recipient':
			case 'all_recipients':
			case 'application_fee':
			case 'all_application_fees':
				break;
			case 'account':
				$api = 'PPP\Stripe\Account';
				break;
			case 'balance':
			case 'balance_transaction':
			case 'balance_history':
				break;
			case 'event':
				$api    = 'PPP\Stripe\Event';
				$params = array( 'id' => (string) $id );
				break;
			case 'all_events':
			case 'token':
				break;
		}

		try {

			if ( empty( $params ) ) {
				$object = call_user_func_array( $api . '::retrieve', array( $api_key ) );
			} else {
				$object = call_user_func_array( $api . '::retrieve', array( $params, $api_key ) );
			}

			self::$logger->log->debug( "Found {$item}" );

		} catch ( Exception $e ) {

			if ( $do_error_message ) {

				self::$logger->log->error( "Unable to retrieve {$item}" );

				$error_message = self::create_error_message( $e, self::$mode );

				$object = $error_message;

			} else {

				return $e;
			}

		}

		return $object;
	}

	public static function create_updated_metadata_array( $object, $new_metadata ) {

		$updated_metadata = array();

		$current_metadata = $object->metadata->__toArray();

		$updated_metadata = array_merge( $current_metadata, $new_metadata );

		return $updated_metadata;
	}

	/**
	 * Handle API response for curl requests
	 *
	 * Adapted from Stripe PHP API library
	 *
	 * @param $rbody
	 * @param $rcode
	 *
	 * @return array|mixed
	 * @throws Stripe_ApiError
	 */

	private static function _interpretResponse( $rbody, $rcode ) {

		try {

			$resp = json_decode( $rbody, true );

		} catch ( Exception $e ) {

			throw new PPP\Stripe\Error\Api( "Invalid response body from API: $rbody (HTTP response code was $rcode)", $rcode, $rbody );

		}

		if ( $rcode < 200 || $rcode >= 300 ) {

			$requestor = new PPP\Stripe\ApiRequestor();
			$requestor->handleApiError( $rbody, $rcode, $resp );

		}

		return $resp;

	}

	public static function create_stripe_dashboard_link( $object_id, $object_type, $mode ) {

		$base = 'https://dashboard.stripe.com/';

		switch ( $object_type ) {
			case 'charge':
				$section = 'payments';
				break;
			case 'customer':
				$section = 'customers';
				break;
			case 'card':
				$section = 'logs';
				break;
			case 'subscription':
				break;
			case 'plan':
				$section = 'plans';
				break;
			case 'coupon':
				$section = 'coupons';
				break;
			case 'discount':
				break;
			case 'invoice':
				$section = 'invoices';
				break;
			case 'invoiceitem':
				break;
			case 'dispute':
				$section = 'payments';
				break;
			case 'transfer':
				$section = 'transfers';
				break;
			case 'recipient':
				break;
			case 'application_fee':
				$section = 'applications/fees';
				break;
			case 'account':
			case 'balance':
				break;
			case 'event':
				$section = 'events';
				break;
		}

		$mode = ( $mode ) ? '' : 'test/';

		if ( 'card' !== $object_type ) {
			$stripe_dashboard_link = "{$base}{$mode}{$section}/{$object_id}";
		} else {
			$stripe_dashboard_link = "{$base}{$mode}{$section}?object={$object_id}";
		}

		return $stripe_dashboard_link;
	}
}