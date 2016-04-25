<?php
/**
 * @wordpress-plugin
 * Plugin Name: Gravity Forms + Stripe (More)
 * Plugin URI: https://gravityplus.pro/gravity-forms-stripe
 * Description: Use Stripe to process credit card payments on your site, easily and securely, with Gravity Forms
 * Version: 1.9.2.7.RC1
 * Author: gravity+
 * Author URI: https://gravityplus.pro
 * Text Domain: gravityforms-stripe-more
 * Domain Path: /languages
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 *
 * @package   GFPMoreStripe
 * @version   1.9.2.7
 * @author    gravity+ <support@gravityplus.pro>
 * @license   GPL-2.0+
 * @link      https://gravityplus.pro
 * @copyright 2012-2015 Naomi C. Bush
 *
 * last updated: February 16, 2016
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'GFP_MORE_STRIPE_FILE', __FILE__ );
define( 'GFP_MORE_STRIPE_PATH', plugin_dir_path( __FILE__ ) );
define( 'GFP_MORE_STRIPE_URL', plugin_dir_url( __FILE__ ) );

/**
 * Unique identifier
 *
 * @since 1.9.2.1
 */
define( 'GFP_MORE_STRIPE_SLUG', plugin_basename( dirname( __FILE__ ) ) );

add_action( 'plugins_loaded', array( 'GFPMoreStripe', 'plugins_loaded' ) );

register_activation_hook( GFP_MORE_STRIPE_FILE, array( 'GFPMoreStripe', 'activate' ) );


/**
 * Class GFPMoreStripe
 */
class GFPMoreStripe {

	private static $path = "gravityforms-stripe-more/more-stripe.php";
	private static $url = 'https://gravityplus.pro';
	private static $slug = 'gravityforms-stripe-more';
	private static $version = '1.9.2.7.RC1';
	private static $min_gfp_stripe_version = '1.9.2.3.RC1';
	private static $min_gravityforms_version = '1.8.17';



	public static function plugins_loaded() {

		self::load_textdomain();

		if ( ! ( isset( $_GET[ 'action' ] ) && ( ( 'upgrade-plugin' == $_GET[ 'action' ] ) || ( 'update-selected' == $_GET[ 'action' ] ) ) ) ) {

			if ( ! self::is_gfp_stripe_supported() ) {

				if ( isset( $_GET[ 'action' ] ) && ( ! ( 'activate' == $_GET[ 'action' ] ) ) ) {

					$message = __( 'More Stripe requires at least Gravity Forms + Stripe ' . self::$min_gfp_stripe_version . '.', 'gravityforms-stripe-more' );

					self::set_admin_notice( $message, 'errors' );

					add_action( 'admin_notices', array( 'GFPMoreStripe', 'admin_notices' ) );

				}

				return;
			}

			if ( ! GFP_Stripe::is_gravityforms_supported() ) {

				if ( isset( $_GET[ 'action' ] ) && ( ! ( 'activate' == $_GET[ 'action' ] ) ) ) {
					$message = __( 'More Stripe requires at least Gravity Forms ' . self::$min_gravityforms_version . '.', 'gravityforms-stripe-more' );
					self::set_admin_notice( $message, 'errors' );
					add_action( 'admin_notices', array( 'GFPMoreStripe', 'admin_notices' ) );
				}

				return;
			}

		}

		if ( ! GFP_Stripe::is_gravityforms_supported() ) {
			return;
		}

		if ( ! class_exists( 'PPP_Stripe_API' ) ) {
			require_once( GFP_MORE_STRIPE_PATH . '/includes/api/class-stripe-api.php' );
		}

		require_once( GFP_MORE_STRIPE_PATH . '/includes/api/class-stripe-api-logger.php' );
		require_once( GFP_MORE_STRIPE_PATH . '/includes/backcompat/class-gfp-more-stripe-api.php' );
		require_once( GFP_MORE_STRIPE_PATH . '/includes/api/class-customer-api.php' );

		$logger = new GFP_Stripe_API_Logger();

		new PPP_Stripe_API(
			array(
				'slug'                 => GFP_STRIPE_SLUG,
				'path'                 => GFP_STRIPE_PATH,
				'settings_option_name' => 'gfp_stripe_settings',
				'logger'               => $logger
			)
		);


		require_once( GFP_MORE_STRIPE_PATH . '/includes/api/class-helper.php' );


		require_once( GFP_MORE_STRIPE_PATH . '/includes/currency/class-gfp-more-stripe-currency.php' );
		require_once( GFP_MORE_STRIPE_PATH . '/includes/currency/class-gfp-more-stripe-currency-field.php' );
		require_once( GFP_MORE_STRIPE_PATH . '/includes/currency/class-gfp-more-stripe-currency-converter.php' );

		new GFP_More_Stripe_Currency_Field();
		new GFP_More_Stripe_Currency_Converter();


		require_once( GFP_MORE_STRIPE_PATH . '/includes/integrations/cpac/class-cpac.php' );

		new GFP_Stripe_CPAC();


		require_once( GFP_MORE_STRIPE_PATH . '/includes/customer-account-view/class-customer-account-view.php' );

		$gfp_stripe_customer_account_view = new GFP_Stripe_Customer_Account_View();
		$gfp_stripe_customer_account_view->run();

		if ( ! class_exists( 'PPP_DB' ) ) {
			require_once( GFP_MORE_STRIPE_PATH . 'includes/events/class-db.php' );
		}

		require_once( GFP_MORE_STRIPE_PATH . 'includes/events/class-db-events.php' );

		$events_db = new GFP_Stripe_DB_Events( array(
			                                       'plugin_slug' => GFP_MORE_STRIPE_SLUG,
			                                       'table_name'  => 'rg_stripe_events',
			                                       'primary_key' => 'id',
			                                       'version'     => self::$version
		                                       ) );

		if ( ! class_exists( 'PPP_Stripe_Event' ) ) {
			require_once( GFP_MORE_STRIPE_PATH . 'includes/events/class-stripe-event.php' );
		}

		require_once( GFP_MORE_STRIPE_PATH . 'includes/events/class-event-handler.php' );

		$event_handler = new GFP_Stripe_Event_Handler(
			array(
				'plugin_slug' => GFP_MORE_STRIPE_SLUG,
				'logger'      => $logger,
				'db'          => $events_db
			)
		);

		if ( self::get_early_access() ) {

			require_once( GFP_MORE_STRIPE_PATH . '/includes/importer/class-importer.php' );

			new GFP_Stripe_Importer();
		}

		add_action( 'init', array( 'GFPMoreStripe', 'init' ) );
	}

	public static function load_textdomain() {

		$gfp_more_stripe_lang_dir = dirname( plugin_basename( GFP_MORE_STRIPE_FILE ) ) . '/languages/';
		$gfp_more_stripe_lang_dir = apply_filters( 'gfp_more_stripe_language_dir', $gfp_more_stripe_lang_dir );

		$locale = apply_filters( 'plugin_locale', get_locale(), 'gravityforms-stripe-more' );
		$mofile = sprintf( '%1$s-%2$s.mo', 'gravityforms-stripe-more', $locale );

		$mofile_local  = $gfp_more_stripe_lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/gravityforms-stripe-more/' . $mofile;

		if ( file_exists( $mofile_global ) ) {
			load_textdomain( 'gravityforms-stripe-more', $mofile_global );
		} elseif ( file_exists( $mofile_local ) ) {
			load_textdomain( 'gravityforms-stripe-more', $mofile_local );
		} else {
			load_plugin_textdomain( 'gravityforms-stripe-more', false, $gfp_more_stripe_lang_dir );
		}
	}

	public static function init() {

		register_deactivation_hook( 'gravity-forms-stripe/gravity-forms-stripe.php', array( 'GFPMoreStripe', 'deactivate_gfp_stripe' ) );

		add_action( 'admin_init', array( 'GFPMoreStripe', 'admin_init' ) );
		add_action( 'admin_notices', array( 'GFPMoreStripe', 'admin_notices' ) );
		add_action( 'admin_enqueue_scripts', array( 'GFPMoreStripe', 'admin_enqueue_scripts' ) );

		add_action( 'wp_ajax_gfp_more_stripe_get_coupon', array( 'GFPMoreStripe', 'gfp_more_stripe_get_coupon' ) );
		add_action( 'wp_ajax_nopriv_gfp_more_stripe_get_coupon', array( 'GFPMoreStripe', 'gfp_more_stripe_get_coupon' ) );

		if ( class_exists( 'GFUser' ) ) {
			add_action( 'gform_subscription_canceled', array( 'GFPMoreStripe', 'downgrade_stripe_user' ), 10, 5 );
			add_action( 'gform_subscription_canceled', array( 'GFPMoreStripe', 'downgrade_stripe_site' ), 10, 2 );
			add_action( 'gform_user_registered', array( 'GFPMoreStripe', 'gform_user_registered' ), 10, 4 );
		}

		if ( basename( $_SERVER[ 'PHP_SELF' ] ) == 'plugins.php' ) {
			add_action( 'after_plugin_row_' . self::$path, array( 'GFPMoreStripe', 'plugin_row' ) );
		}

		if ( is_admin() ) {

			self::setup();

			add_filter( 'pre_set_site_transient_update_plugins', array( 'GFPMoreStripe', 'check_update' ) );
			add_filter( 'plugins_api', array( 'GFPMoreStripe', 'plugins_api' ), 10, 3 );

			add_filter( 'plugin_action_links_' . plugin_basename( GFP_MORE_STRIPE_FILE ), array( 'GFPMoreStripe', 'plugin_action_links' ) );

			add_filter( 'gfp_stripe_usage_stats', array( 'GFPMoreStripe', 'gfp_stripe_usage_stats' ) );

			if ( ( ( 'settings' == rgget( 'view' ) ) && ( 'stripe' == rgget( 'subview' ) ) && ( GFP_Stripe::has_access( 'gfp_stripe_form_settings' ) ) ) ) {

				if ( ! class_exists( 'GFPMoreStripeUpgrade' ) ) {
					require_once( 'plugin-upgrade.php' );
				}

				add_filter( 'gfp_stripe_edit_feed_js_data', array( 'GFPMoreStripe', 'gfp_stripe_edit_feed_js_data' ), 10, 3 );
				add_filter( 'gfp_stripe_list_feeds_product_type', array( 'GFPMoreStripe', 'gfp_stripe_list_feeds_product_type' ) );
				add_filter( 'gfp_stripe_feed_transaction_type', array( 'GFPMoreStripe', 'gfp_stripe_feed_transaction_type' ), 10, 2 );
				add_action( 'gfp_stripe_feed_after_transaction_type', array( 'GFPMoreStripe', 'gfp_stripe_feed_after_transaction_type' ), 10, 2 );
				add_action( 'gfp_stripe_feed_before_billing', array( 'GFPMoreStripe', 'gfp_stripe_feed_before_billing' ), 10, 2 );
				add_filter( 'gfp_stripe_display_billing_info', array( 'GFPMoreStripe', 'gfp_stripe_display_billing_info' ), 10, 2 );
				add_action( 'gfp_stripe_feed_after_billing', array( 'GFPMoreStripe', 'gfp_stripe_feed_after_billing' ), 10, 2 );
				add_action( 'gfp_stripe_feed_options', array( 'GFPMoreStripe', 'gfp_stripe_feed_options' ), 10, 3 );
				add_filter( 'gfp_stripe_before_save_feed', array( 'GFPMoreStripe', 'gfp_stripe_before_save_feed' ) );
				add_action( 'gfp_stripe_after_save_feed', array( 'GFPMoreStripe', 'gfp_stripe_after_save_feed' ), 10, 3 );

			} else if ( in_array( RG_CURRENT_PAGE, array( 'admin-ajax.php' ) ) ) {

				add_action( 'wp_ajax_gfp_more_stripe_cancel_subscription', array( 'GFPMoreStripe', 'gfp_more_stripe_cancel_subscription' ) );
				add_action( 'wp_ajax_gfp_more_stripe_charge_customer_for_post', array( 'GFPMoreStripe', 'gfp_more_stripe_charge_customer_for_post' ) );

				add_filter( 'gfp_stripe_feed_endselectform_args', array( 'GFPMoreStripe', 'gfp_stripe_feed_endselectform_args' ), 10, 2 );

			} else {

				switch ( RGForms::get( 'page' ) ) {

					case 'gf_settings':

						if ( ! class_exists( 'GFPMoreStripeUpgrade' ) ) {
							require_once( 'plugin-upgrade.php' );
						}

						add_action( 'gfp_stripe_settings_page', array( 'GFPMoreStripe', 'gfp_stripe_settings_page' ) );
						add_filter( 'gfp_stripe_save_settings', array( 'GFPMoreStripe', 'gfp_stripe_save_settings' ) );
						add_filter( 'gfp_stripe_settings_page_action', array( 'GFPMoreStripe', 'gfp_stripe_settings_page_action' ) );
						add_action( 'gfp_stripe_before_uninstall_button', array( 'GFPMoreStripe', 'gfp_stripe_before_uninstall_button' ) );
						add_action( 'gfp_stripe_uninstall_condition', array( 'GFPMoreStripe', 'gfp_stripe_uninstall_condition' ) );

						break;

					case 'gf_entries':

						add_action( 'gfp_stripe_payment_details', array( 'GFPMoreStripe', 'gfp_stripe_payment_details' ), 10, 2 );

						break;

					case 'gf_edit_forms':

						add_filter( 'gfp_stripe_list_feeds_product_type', array( 'GFPMoreStripe', 'gfp_stripe_list_feeds_product_type' ) );

						break;
				}
			}

			if ( class_exists( 'GFUser' ) ) {

				add_action( 'gfp_stripe_feed_setting', array( 'GFPMoreStripe', 'add_stripe_user_registration_options' ), 10, 2 );
				add_filter( 'gfp_stripe_before_save_feed', array( 'GFPMoreStripe', 'save_stripe_user_config' ) );

			}

			add_action( 'add_meta_boxes', array( 'GFPMoreStripe', 'add_meta_boxes' ), 10, 2 );

		} else {

			add_filter( 'gform_payment_methods', array( 'GFPMoreStripe', 'gform_payment_methods' ), 10, 3 );
			add_action( 'gform_enqueue_scripts', array( 'GFPMoreStripe', 'gform_enqueue_scripts' ), 10, 2 );

			add_filter( 'gform_product_info', array( 'GFPMoreStripe', 'gform_product_info' ), 12, 3 );

			add_filter( 'gfp_stripe_gform_field_validation', array( 'GFPMoreStripe', 'gfp_stripe_gform_field_validation' ), 10, 3 );
			add_filter( 'gfp_stripe_is_ready_for_capture', array( 'GFPMoreStripe', 'gfp_stripe_is_ready_for_capture' ), 10, 3 );
			add_filter( 'gfp_stripe_gform_validation', array( 'GFPMoreStripe', 'gfp_stripe_gform_validation' ), 10, 2 );
			add_filter( 'gfp_stripe_set_validation_result', array( 'GFPMoreStripe', 'gfp_stripe_set_validation_result' ), 10, 3 );
			add_filter( 'gform_validation_message', array( 'GFPMoreStripe', 'gform_validation_message' ), 10, 2 );
			add_filter( 'gfp_stripe_get_publishable_key', array( 'GFPMoreStripe', 'gfp_stripe_get_publishable_key' ), 10, 3 );
			add_filter( 'gfp_stripe_rule_field_info', array( 'GFPMoreStripe', 'gfp_stripe_rule_field_info' ), 10, 3 );

			add_filter( 'gfp_stripe_get_form_data', array( 'GFPMoreStripe', 'gfp_stripe_get_form_data' ), 10, 5 );
			add_filter( 'gfp_stripe_get_form_data_order_info', array( 'GFPMoreStripe', 'gfp_stripe_get_form_data_order_info' ), 10, 2 );
			add_filter( 'gfp_stripe_get_order_info', array( 'GFPMoreStripe', 'gfp_stripe_get_order_info' ), 10, 3 );
			add_filter( 'gfp_stripe_get_order_info_line_items', array( 'GFPMoreStripe', 'gfp_stripe_get_order_info_line_items' ), 10, 8 );
			add_filter( 'gfp_stripe_get_order_info_shipping', array( 'GFPMoreStripe', 'gfp_stripe_get_order_info_shipping' ), 10, 6 );

			add_filter( 'gfp_more_stripe_cancel_charge', array( 'GFPMoreStripe', 'gfp_more_stripe_cancel_charge' ), 10, 3 );

			add_filter( 'gform_save_field_value', array( 'GFPMoreStripe', 'gform_save_field_value' ), 11, 4 );
			add_filter( 'gfp_stripe_entry_post_save_update_lead', array( 'GFPMoreStripe', 'gfp_stripe_entry_post_save_update_lead' ) );
			add_filter( 'gfp_stripe_entry_post_save_insert_transaction', array( 'GFPMoreStripe', 'gfp_stripe_entry_post_save_insert_transaction' ) );
			add_action( 'gfp_stripe_entry_post_save', array( 'GFPMoreStripe', 'gfp_stripe_entry_post_save' ) );
			add_action( 'gform_after_submission', array( 'GFPMoreStripe', 'gform_after_submission' ), 11, 2 );

		}

	}

	/**
	 *
	 *
	 * @since 1.7.9.0
	 */
	public static function activate() {

		self::check_for_base_plugin();
		self::create_default_role();

		if ( class_exists( 'GFP_Stripe' ) ) {
			GFP_Stripe::set_settings_page_redirect();
		}

		do_action( 'gfp_more_stripe_activate' );
	}

	//------------------------------------------------------------------------

	private static function create_default_role() {

		add_role( 'stripe_customer', 'Stripe Customer' );

	}

	private static function check_for_base_plugin() {

		if ( ( array_key_exists( 'action', $_POST ) ) && ( 'activate-selected' == $_POST[ 'action' ] ) && ( in_array( 'gravity-forms-stripe/gravity-forms-stripe.php', $_POST[ 'checked' ] ) ) ) {

			return;

		} else if ( ! class_exists( 'GFP_Stripe' ) ) {

			deactivate_plugins( basename( GFP_MORE_STRIPE_FILE ) );

			$message = __( 'You must install and activate Gravity Forms + Stripe first.', 'gravityforms-stripe-more' );

			die( $message );
		}

	}

	/**
	 * Create an admin notice
	 *
	 * @since 1.7.11.1
	 *
	 * @uses  get_site_transient()
	 * @uses  get_transient()
	 * @uses  set_site_transient()
	 * @uses  set_transient()
	 *
	 * @param $notice
	 *
	 * @param $type
	 *
	 * @return void
	 */
	private static function set_admin_notice( $notice, $type ) {

		if ( function_exists( 'get_site_transient' ) ) {

			$notices = get_site_transient( 'gfp-more-stripe-admin_notices' );

		} else {

			$notices = get_transient( 'gfp-more-stripe-admin_notices' );

		}

		if ( ! is_array( $notices ) || ! array_key_exists( $type, $notices ) || ! in_array( $notice, $notices[ $type ] ) ) {

			$notices[ $type ][ ] = $notice;

		}

		if ( function_exists( 'set_site_transient' ) ) {

			set_site_transient( 'gfp-more-stripe-admin_notices', $notices );

		} else {

			set_transient( 'gfp-more-stripe-admin_notices', $notices );

		}

	}

	/**
	 * Creates or updates database tables. Will only run when version changes
	 *
	 */
	private static function setup() {

		if ( ( $current_version = get_option( 'gfp_more_stripe_version' ) ) != self::$version ) {

			if ( GFForms::get_wp_option( 'gfp_more_stripe_version' ) != self::$version ) {

				if ( version_compare( $current_version, '1.8.2', '<' ) ) {

					add_action( 'gfp_stripe_data_after_update_table', array( 'GFPMoreStripe', 'gfp_stripe_data_after_update_table' ) );

				}

				if ( version_compare( $current_version, '1.9.2.1', '<' ) ) {

					require_once( GFP_MORE_STRIPE_PATH . '/includes/backcompat/class-convert-stripe-customer-info.php' );

					GFP_Stripe_Convert_Customer_Info::run();

				}

				if ( ( get_option( 'gfp_stripe_version' ) == self::$min_gfp_stripe_version ) && ( GFForms::get_wp_option( 'gfp_stripe_version' ) == self::$min_gfp_stripe_version ) ) {
					do_action( 'gfp_stripe_data_after_update_table' );
				}

				do_action( 'gfp_more_stripe_before_update_version', $current_version );

				update_option( 'gfp_more_stripe_version', self::$version );

				do_action( 'gfp_more_stripe_after_update_version' );
			}
		}
	}

	public static function gfp_stripe_data_after_update_table() {

		if ( version_compare( GFP_Stripe::get_version(), '1.8.2', '>=' ) ) {

			$stripe_form_meta = GFP_Stripe_Data::get_all_feeds();

			foreach ( $stripe_form_meta as $meta ) {

				foreach ( $meta[ 'rules' ] as $feed ) {

					if ( 'subscription' == $feed[ 'type' ] ) {

						if ( is_numeric( $subscription_field_id = $feed[ 'subscription_plan_field' ] ) ) {

							$form_meta = GFFormsModel::get_form_meta( $meta[ 'form_id' ] );

							foreach ( $form_meta[ 'fields' ] as $key => $field ) {
								if ( $subscription_field_id == $field[ 'id' ] ) {
									$form_meta[ 'fields' ][ $key ][ 'stripeSubscription' ] = true;
								}
							}

							unset( $form_meta[ 'notifications' ] );
							unset( $form_meta[ 'confirmations' ] );

							GFFormsModel::update_form_meta( $meta[ 'form_id' ], $form_meta );

						}

					}

				}

				if ( empty( $meta[ 'form_settings' ] ) || empty( $meta[ 'form_settings' ][ 'mode' ] ) ) {

					$mode = GFP_Stripe_Helper::get_global_stripe_mode();

					GFP_Stripe_Data::update_stripe_form_meta( $meta[ 'form_id' ], array( 'mode' => $mode ), 'form_settings' );

				}

			}

		}

	}

	/**
	 * Adds feed tooltips to the list of tooltips
	 *
	 * @param $tooltips
	 *
	 * @return array
	 */
	public static function gform_tooltips( $tooltips ) {

		$more_stripe_tooltips = array(
			'stripe_subscription_plan'              => '<h6>' . __( 'Subscription Plan', 'gravityforms-stripe-more' ) . '</h6>' . __( 'Select which field determines the subscription plan.', 'gravityforms-stripe-more' ),
			'stripe_free_trial_no_credit_card'      => '<h6>' . __( 'Free Trial No Credit Card Required', 'gravityforms-stripe-more' ) . '</h6>' . __( 'Select this option if your subscription plan will have a free trial and you\'d like your customer to be able to subscribe without providing a credit card.', 'gravityforms-stripe-more' ),
			'stripe_enable_coupons'                 => '<h6>' . __( 'Enable Coupons', 'gravityforms-stripe-more' ) . '</h6>' . __( 'When coupons are enabled, the mapped coupon field will be checked for a valid coupon to be submitted to Stripe with the subscription', 'gravityforms-stripe-more' ),
			'stripe_coupons_apply'                  => '<h6>' . __( 'Apply Coupon', 'gravityforms-stripe-more' ) . '</h6>' . __( 'Select whether you want to apply this coupon immediately, or after the first period.', 'gravityforms-stripe-more' ),
			'stripe_subscription_end_after'         => '<h6>' . __( 'End Subscription', 'gravityforms-stripe-more' ) . '</h6>' . __( 'If you\'d like to end the subscription after a certain number of payments, select the field which contains that number', 'gravityforms-stripe-more' ),
			'stripe_setup_fee_enable'               => '<h6>' . __( 'Enable Setup Fee', 'gravityforms-stripe-more' ) . '</h6>' . __( 'When a setup fee is enabled, the mapped setup fee field will be submitted to Stripe as a one-time charge on your customer\'s first invoice', 'gravityforms-stripe-more' ),
			'stripe_alternate_charge_option'        => '<h6>' . __( 'Alternate Charge Option', 'gravityforms-stripe-more' ) . '</h6>' . __( '*Instead of* charging a customer\'s card when the form is submitted, you can choose to save the credit card information *only* or just authorize the amount on the card (which will expire after 7 days)', 'gravityforms-stripe-more' ),
			'stripe_disable_prorate'                => '<h6>' . __( 'Do Not Prorate', 'gravityforms-stripe-more' ) . '</h6>' . __( 'By default, Stripe prorates subscription changes. For example, if a customer signs up on May 1 for a $10 plan, she\'ll be billed $10 immediately. If she then switches to a $20 plan on May 15, on June 1 she\'ll be billed $25 ($20 for a renewal of her subscription and a $5 prorating adjustment for the previous month). Similarly, a downgrade will generate a credit to be applied to the next invoice. Stripe also prorates when you make quantity changes. Check this box if you do *not* want subscription changes to be prorated. Using the example, this would mean the customer would be billed $10 on May 1 and $20 on June 1.', 'gravityforms-stripe-more' ),
			'stripe_charge_upgrade_immediately'     => '<h6>' . __( 'Charge Immediately', 'gravityforms-stripe-more' ) . '</h6>' . __( 'Switching plans does not change the billing date or generate an immediate charge unless you\'re switching between different intervals (e.g. monthly to yearly). Check this box if instead you\'d like to charge for an upgrade immediately.', 'gravityforms-stripe-more' ),
			'stripe_cancel_at_period_end'           => '<h6>' . __( 'Delay Subscription Cancelation', 'gravityforms-stripe-more' ) . '</h6>' . __( 'By default, Stripe terminates the subscription immediately when it is canceled. Check this box if you\'d like the subscription to remain active until the end of the period, at which point it will be canceled and not renewed. Note, however, that any pending invoice items that you\'ve created will still be charged for at the end of the period unless manually deleted. Also, any pending prorations will also be left in place and collected at the end of the period, instead of removed.', 'gravityforms-stripe-more' ),
			'stripe_auto_login'                     => '<h6>' . __( 'Automatically Log In New User', 'gravityforms-stripe-more' ) . '</h6>' . __( 'Check this box to automatically log in the new user created with this form submission. This is helpful for things like upselling, so that credit card information is automatically filled in on form redirect.', 'gravityforms-stripe-more' ),
			'stripe_disable_new_users'              => '<h6>' . __( 'Disable Saving New Users', 'gravityforms-stripe-more' ) . '</h6>' . __( 'Check this box to disable saving Stripe customers as a new user for this form.', 'gravityforms-stripe-more' ),
			'form_field_enable_stripe_subscription' => '<h6>' . __( 'Stripe subscription product?', 'gravityforms-stripe-more' ) . '</h6>' . __( 'Use this product as a Stripe subscription', 'gravityforms-stripe-more' ),
			'form_field_create_stripe_plan'         => '<h6>' . __( 'Create New Stripe Plan', 'gravityforms-stripe-more' ) . '</h6>' . __( 'Create a new Stripe plan', 'gravityforms-stripe-more' ),
		);

		if ( class_exists( 'GFUser' ) ) {
			$more_stripe_tooltips[ 'user_registration_stripe_user_options' ] = '<h6>' . __( 'User Registration', 'gravityforms-stripe-more' ) . '</h6>' . __( 'The selected form also has a User Registration feed. These options allow you to specify how you would like the Stripe and User Registration Add-ons to work together.', 'gravityforms-stripe-more' );
		}

		return array_merge( $tooltips, $more_stripe_tooltips );
	}

	/**
	 * @return bool
	 */
	public static function is_notifications_page() {

		$current_page = trim( strtolower( RGForms::get( 'page' ) ) );

		return in_array( $current_page, array( 'notification_edit' ) );
	}

	/**
	 * @return bool|mixed
	 */
	private static function is_gfp_stripe_supported() {

		$is_correct_version = false;

		if ( class_exists( 'GFP_Stripe' ) ) {
			$is_correct_version = version_compare( GFP_Stripe::get_version(), self::$min_gfp_stripe_version, '>=' );
		}

		return $is_correct_version;
	}

	/**
	 * @return bool|mixed
	 */
	private static function is_gravityforms_supported() {

		$is_correct_version = false;

		if ( class_exists( 'GFCommon' ) ) {
			$is_correct_version = version_compare( GFCommon::$version, self::$min_gravityforms_version, '>=' );
		}

		return $is_correct_version;
	}


	/**
	 *  Disallow Gravity Forms + Stripe deactivation if this plugin is still active
	 *
	 * Prevents a fatal error if this plugin is still active when user attempts to deactivate Gravity Forms + Stripe
	 *
	 * @since 1.7.9.0
	 *
	 * @uses  plugin_basename()
	 * @uses  is_plugin_active()
	 * @uses  __()
	 * @uses  get_site_transient()
	 * @uses  get_transient()
	 * @uses  set_site_transient()
	 * @uses  set_transient()
	 * @uses  self_admin_url()
	 * @uses  wp_redirect()
	 *
	 * @param $network_deactivating
	 *
	 * @return void
	 */
	public static function deactivate_gfp_stripe( $network_deactivating ) {

		$plugin = plugin_basename( trim( GFP_MORE_STRIPE_FILE ) );

		if ( ( array_key_exists( 'action', $_POST ) ) && ( 'deactivate-selected' == $_POST[ 'action' ] ) && ( in_array( $plugin, $_POST[ 'checked' ] ) ) ) {

			return;

		} else if ( is_plugin_active( $plugin ) ) {

			if ( $network_deactivating ) {
				add_action( 'update_site_option_active_sitewide_plugins', array( 'GFPMoreStripe', 'update_site_option_active_sitewide_plugins' ) );
			} else {
				add_action( 'update_option_active_plugins', array( 'GFPMoreStripe', 'update_option_active_plugins' ) );
			}

		}

	}

	public static function update_option_active_plugins() {

		remove_action( 'update_option_active_plugins', array( 'GFPMoreStripe', 'update_options_active_plugins' ) );

		$plugin = plugin_basename( trim( GFP_MORE_STRIPE_FILE ) );

		deactivate_plugins( $plugin );

		update_option( 'recently_activated', array( $plugin => time() ) + (array) get_option( 'recently_activated' ) );
	}

	public static function update_site_option_active_sitewide_plugins() {

		remove_action( 'update_site_option_active_sitewide_plugins', array( 'GFPMoreStripe', 'update_site_option_active_sitewide_plugins' ) );

		$plugin = plugin_basename( trim( GFP_MORE_STRIPE_FILE ) );

		deactivate_plugins( $plugin );
	}

	/**
	 *
	 * @since 1.7.9.0
	 */
	public static function admin_init() {

		if ( in_array( RG_CURRENT_PAGE, array( 'admin-ajax.php' ) ) ) {

			add_action( 'wp_ajax_gfp_more_stripe_update_form_stripe_mode', array( 'GFPMoreStripe', 'gfp_more_stripe_update_form_stripe_mode' ) );

		}

		if ( RGForms::get_page() || ( ( 'settings' == rgget( 'view' ) ) && ( 'stripe' == rgget( 'subview' ) ) && ( GFP_Stripe::has_access( 'gfp_stripe_form_settings' ) ) ) ) {
			add_filter( 'gform_noconflict_styles', array( 'GFPMoreStripe', 'gform_noconflict_styles' ) );
			add_filter( 'gform_tooltips', array( 'GFPMoreStripe', 'gform_tooltips' ) );
		}

		if ( ( 'gf_settings' == RGForms::get( 'page' ) ) && ( 'Stripe' == rgget( 'addon' ) || 'Stripe' == rgget( 'subview' ) ) ) {
			add_filter( 'gform_noconflict_scripts', array( 'GFPMoreStripe', 'gform_noconflict_scripts' ) );
		}

		if ( 'gf_edit_forms' == RGForms::get( 'page' ) ) {

			if ( 1 == count( $_GET ) ) {
				GFPMoreStripe::add_stripe_mode_toggle();
			}

			add_action( 'gform_field_standard_settings', array( 'GFPMoreStripe', 'gform_field_standard_settings' ), 10, 2 );
			add_action( 'gform_editor_js', array( 'GFPMoreStripe', 'gform_editor_js' ) );
			add_filter( 'gform_predefined_choices', array( 'GFPMoreStripe', 'gform_predefined_choices' ) );
			add_filter( 'gform_noconflict_scripts', array( 'GFPMoreStripe', 'gform_noconflict_scripts' ) );
		}

		if ( 'gf_entries' == RGForms::get( 'page' ) ) {
			add_filter( 'gfp_stripe_entry_detail_transaction_id', array( 'GFPMoreStripe', 'gfp_stripe_entry_detail_transaction_id' ), 10, 3 );

		}

	}

	/**
	 *  Output admin notices
	 *
	 * @since 1.7.9.0
	 *
	 * @uses  get_site_transient()
	 * @uses  get_transient()
	 * @uses  delete_site_transient()
	 * @uses  delete_transient()
	 *
	 * @return void
	 */
	public static function admin_notices() {

		$admin_notices = function_exists( 'get_site_transient' ) ? get_site_transient( 'gfp-more-stripe-admin_notices' ) : get_transient( 'gfp-more-stripe-admin_notices' );

		if ( $admin_notices ) {

			$message = '';

			foreach ( $admin_notices as $type => $notices ) {

				if ( ( 'errors' == $type ) && ( ! self::is_gfp_stripe_supported() ) ) {
					foreach ( $notices as $notice ) {
						$message .= '<div class="error"><p>' . $notice . '</p></div>';
					}
				}

				if ( 'updates' == $type ) {
					foreach ( $notices as $notice ) {
						$message .= '<div class="updated"><p>' . $notice . '</p></div>';
					}
				}

			}
			echo $message;

			if ( function_exists( 'delete_site_transient' ) ) {
				delete_site_transient( 'gfp-more-stripe-admin_notices' );
			} else {
				delete_transient( 'gfp-more-stripe-admin_notices' );
			}
		}

	}

	public static function admin_enqueue_scripts() {

		if ( self::is_user_registration_page() ) {

			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			wp_enqueue_script( 'gfp_more_stripe_user_registration_edit_page', GFP_MORE_STRIPE_URL . "/includes/integrations/urao/user_registration_edit_page{$suffix}.js", array( 'jquery' ), GFPMoreStripe::get_version() );

			add_filter( 'gform_noconflict_scripts', array( 'GFPMoreStripe', 'gform_noconflict_scripts' ) );
		}

	}

	/**
	 * Add a link to this plugin's settings page
	 *
	 * @since 1.7.9.0
	 *
	 * @uses  self_admin_url()
	 * @uses  __()
	 *
	 * @param $links
	 *
	 * @return array
	 */
	public static function plugin_action_links( $links ) {

		return array_merge(
			array(
				'settings' => '<a href="' . self_admin_url( 'admin.php?page=gf_settings&subview=Stripe' ) . '">' . __( 'Settings', 'gravityforms-stripe-more' ) . '</a>'
			),
			$links
		);
	}

	/**
	 * @param $noconflict_styles
	 *
	 * @return array
	 */
	public static function gform_noconflict_styles( $noconflict_styles ) {

		return array_merge( $noconflict_styles, array( 'gfp_more_stripe_edit_feed' ) );
	}

	/**
	 * @param $noconflict_scripts
	 *
	 * @return array
	 */
	public static function gform_noconflict_scripts( $noconflict_scripts ) {

		if ( ( 'gf_settings' == RGForms::get( 'page' ) ) && ( 'Stripe' == rgget( 'addon' ) ) ) {

			$noconflict_scripts = array_merge( $noconflict_scripts, array( 'gfp_more_stripe_settings_page_js' ) );

		} else if ( 'gf_edit_forms' == RGForms::get( 'page' ) ) {

			$noconflict_scripts = array_merge( $noconflict_scripts, array(
				'gfp_more_stripe_form_editor_subscription',
				'gfp_more_stripe_form_list'
			) );

			if ( ( 'settings' == rgget( 'view' ) ) && ( 'stripe' == rgget( 'subview' ) ) && ( GFP_Stripe::has_access( 'gfp_stripe_form_settings' ) ) ) {
				$noconflict_scripts = array_merge( $noconflict_scripts, array( 'gfp_more_stripe_form_settings_edit_feed_js' ) );
			}

		} else if ( self::is_user_registration_page() ) {

			$noconflict_scripts = array_merge( $noconflict_scripts, array( 'gfp_more_stripe_user_registration_edit_page' ) );

		} else if ( 'gf_entries' == RGForms::get( 'page' ) ) {

			$noconflict_scripts = array_merge( $noconflict_scripts, array( 'gfp_more_stripe_entry_detail' ) );

		}

		return $noconflict_scripts;
	}

	//------------------------------------------------------
	//------------- AUTOMATIC UPDATES ----------------------
	//------------------------------------------------------

	/**
	 * Get slug
	 *
	 * @since 1.7.9.0
	 *
	 * @return string
	 */
	public static function get_slug() {
		return self::$slug;
	}

	/**
	 * Get version
	 *
	 * @since 1.7.9.0
	 *
	 * @return string
	 */
	public static function get_version() {
		return self::$version;
	}

	/**
	 * Get URL
	 *
	 * @since 1.7.9.0
	 *
	 * @return string
	 */
	public static function get_url() {
		return self::$url;
	}

	/**
	 *
	 *
	 * @since 1.7.9.0
	 *
	 * @return void
	 */
	public static function plugin_row() {

		if ( ! self::is_gravityforms_supported() ) {

			$message = __( 'Gravity Forms ' . self::$min_gravityforms_version . " is required.", 'gravityforms-stripe-more' );
			$style   = 'style="background-color: #ffebe8;"';

			echo '</tr><tr class="plugin-update-tr"><td colspan="5" class="plugin-update"><div class="update-message" ' . $style . '>' . $message . '</div></td>';

		} else if ( ! self::is_gfp_stripe_supported() ) {

			$message = sprintf( __( 'Gravity Forms + Stripe ' . self::$min_gfp_stripe_version . " is required. Activate it now or %sdownload it%s", 'gravityforms-stripe-more' ), "<a href='http://wordpress.org/extend/plugins/gravity-forms-stripe/'>", "</a>" );
			$style   = 'style="background-color: #ffebe8;"';

			echo '</tr><tr class="plugin-update-tr"><td colspan="5" class="plugin-update"><div class="update-message" ' . $style . '>' . $message . '</div></td>';

		} else {

			if ( ! class_exists( 'GFPMoreStripeUpgrade' ) ) {
				require_once( 'plugin-upgrade.php' );
			}

			$version_info = GFPMoreStripeUpgrade::get_version_info( self::$slug, self::get_key(), self::$version, self::get_early_access() );

			if ( ! $version_info[ 'is_valid_key' ] ) {

				$new_version = '';
				$message     = $new_version . sprintf( __( '%sRegister%s your copy of Gravity Forms + (More) Stripe to receive access to automatic updates and support. Need a license key? %sPurchase one now%s.', 'gravityforms-stripe-more' ), '<a href="admin.php?page=gf_settings&subview=Stripe">', '</a>', '<a href="https://gravityplus.pro/gravity-forms-stripe">', '</a>' ) . '</div></td>';

				GFPMoreStripeUpgrade::display_plugin_message( $message );
			}

		}
	}


	/**
	 *
	 *
	 * @since 1.7.9.0
	 *
	 * @param $update_plugins_option
	 *
	 * @return mixed
	 */
	public static function check_update( $update_plugins_option ) {

		if ( empty( $update_plugins_option->checked ) ) {
			return $update_plugins_option;
		}

		if ( ! class_exists( 'GFPMoreStripeUpgrade' ) ) {
			require_once( 'plugin-upgrade.php' );
		}

		return GFPMoreStripeUpgrade::check_update( self::$path, self::$slug, self::$url, self::$slug, self::get_key(), self::$version, self::get_early_access(), $update_plugins_option );
	}

	/**
	 *
	 *
	 * @since 1.7.9.0
	 *
	 * @param $result
	 * @param $action
	 * @param $args
	 *
	 * @return bool|stdClass
	 */
	public static function plugins_api( $result, $action, $args ) {

		if ( ( 'plugin_information' != $action ) || ( $args->slug != self::$slug ) ) {
			return $result;
		}

		if ( ! class_exists( 'GFPMoreStripeUpgrade' ) ) {
			require_once( 'plugin-upgrade.php' );
		}

		return GFPMoreStripeUpgrade::get_version_details( self::$slug, self::get_key(), self::$version, self::get_early_access() );
	}

	/**
	 *
	 *
	 * @since 1.7.9.0
	 *
	 * @return mixed|string|void
	 */
	private static function get_key() {

		$key = '';

		if ( self::is_gfp_stripe_supported() ) {
			$key = get_option( 'gfp_support_key' );
		}

		return $key;
	}

	//------------------------------------------------------
	//------------- USER REGISTRATION INTEGRATION ----------
	//------------------------------------------------------

	/**
	 * Are we on the User Registration feed page?
	 *
	 * @since 1.8.2
	 *
	 * @uses  RGForms::get()
	 *
	 * @return bool
	 */
	public static function is_user_registration_page() {

		$current_page = trim( strtolower( RGForms::get( 'page' ) ) );

		return in_array( $current_page, array( 'gf_user_registration' ) );
	}

	/**
	 * @param $feed
	 * @param $form
	 *
	 * @return void
	 */
	public static function add_stripe_user_registration_options( $feed, $form ) {

		global $wp_roles;


		$id = rgget( 'id' );

		$registration_config = gf_user_registration()->get_feeds( array_key_exists( 'id', $form ) ? $form[ 'id' ] : '' );
		$registration_feeds  = gf_user_registration()->get_feeds();
		$registration_forms  = array();

		foreach ( $registration_feeds as $registration_feed ) {
			$registration_forms[ ] = $registration_feed[ 'form_id' ];
		}

		$json_registration_forms = GFCommon::json_encode( $registration_forms );

		if ( empty( $json_registration_forms ) ) {
			$json_registration_forms = '[]';
		}

		$roles                        = array_keys( $wp_roles->roles );
		$display_registration_options = ! empty( $registration_config ) ? '' : 'display:none;';
		$display_multisite_options    = ( is_multisite() && GFUser::is_root_site() && ( array_key_exists( 'type', $feed[ 'meta' ] ) && $feed[ 'meta' ][ 'type' ] == 'subscription' ) ) ? '' : 'display:none;';

		?>

		<script type="text/javascript">
			jQuery( document ).ready( function ( $ ) {
				$( document ).bind( 'stripeFormSelected', function ( event, form ) {

					var registration_form_ids = <?php echo $json_registration_forms; ?>;
					var transaction_type = $( "#gfp_stripe_type" ).val();
					var form = form;
					var has_registration = false;
					var display_multisite_options = <?php echo (is_multisite() && GFUser::is_root_site()) ? 'true' : 'false' ?>;

					if ( $.inArray( String( form['id'] ), registration_form_ids ) != -1 )
						has_registration = true;

					if ( ( has_registration == true ) && ( transaction_type == "subscription" ) ) {
						$( "#gf_stripe_user_registration_options" ).show();
					} else {
						$( "#gf_stripe_user_registration_options" ).hide();
					}

					$( "#gf_stripe_update_user_option, #gf_stripe_update_site_option" ).hide();

					if ( transaction_type == "subscription" )
						$( "#gf_stripe_update_user_option" ).show();

					if ( transaction_type == "subscription" && display_multisite_options )
						$( "#gf_stripe_update_site_option" ).show();

				} );
			} );
		</script>

		<tr id="gf_stripe_user_registration_options" valign="top" style="<?php echo $display_registration_options; ?>">
			<th scope="row">
				<label>
					<?php _e( 'User Registration', 'gravityforms-stripe-more' ); ?>
					<?php gform_tooltip( 'user_registration_stripe_user_options' ) ?>
				</label>
			</th>
			<td>
				<ul style="overflow:hidden;">


					<li id="gf_stripe_update_user_option" <?php echo rgars( $feed, 'meta/type' ) == 'subscription' ? '' : "style='display:none;'" ?>>
						<input type="checkbox" name="gf_stripe_update_user" id="gf_stripe_update_user"
						       value="1" <?php echo rgars( $feed, 'meta/update_user_action' ) ? "checked='checked'" : "" ?>
						       onclick="var action = this.checked ? '<?php echo $roles[ 0 ]; ?>' : ''; jQuery('#gf_stripe_update_user_action').val(action);"/>
						<label class="inline"
						       for="gf_stripe_update_user"><?php _e( 'Update <strong>user</strong> when subscription is cancelled.', 'gravityforms-stripe-more' ); ?></label>
						<select id="gf_stripe_update_user_action" name="gf_stripe_update_user_action"
						        onchange="var checked = jQuery(this).val() ? 'checked' : false; jQuery('#gf_stripe_update_user').attr('checked', checked);">
							<option value=""></option>
							<?php foreach ( $roles as $role ) {
								$role_name = ucfirst( $role );
								?>
								<option
									value="<?php echo $role ?>" <?php echo rgars( $feed, 'meta/update_user_action' ) == $role ? "selected='selected'" : '' ?>><?php echo sprintf( __( "Set User as %s", 'gravityforms-stripe-more' ), $role_name ); ?></option>
							<?php } ?>
						</select>
					</li>

					<!-- Multisite Options -->

					<li id="gf_stripe_update_site_option" style="<?php echo $display_multisite_options; ?>">
						<input type="checkbox" name="gf_stripe_update_site" id="gf_stripe_update_site"
						       value="1" <?php echo rgars( $feed, 'meta/update_site_action' ) ? "checked='checked'" : "" ?>
						       onclick="var action = this.checked ? 'deactivate' : ''; jQuery('#gf_stripe_update_site_action').val(action);"/>
						<label class="inline"
						       for="gf_stripe_update_site"><?php _e( 'Update <strong>site</strong> when subscription is cancelled.', 'gravityforms-stripe-more' ); ?></label>
						<select id="gf_stripe_update_site_action" name="gf_stripe_update_site_action"
						        onchange="var checked = jQuery(this).val() ? 'checked' : false; jQuery('#gf_stripe_update_site').attr('checked', checked);">
							<option value=""></option>
							<?php $site_options = array(
								'deactivate' => __( 'Deactivate', 'gravityforms-stripe-more' ),
								'delete'     => __( 'Delete', 'gravityforms-stripe-more' )
							); ?>
							<?php foreach ( $site_options as $option_key => $option_label ) { ?>
								<option
									value="<?php echo $option_key; ?>" <?php echo rgars( $feed, 'meta/update_site_action' ) == $option_key ? "selected='selected'" : '' ?>><?php echo sprintf( __( "%s site", 'gravityforms-stripe-more' ), $option_key ); ?></option>
							<?php } ?>
						</select>
					</li>

				</ul>
			</td>
		</tr>

	<?php

	}

	/**
	 * @param $feed
	 *
	 * @return mixed
	 */
	public static function save_stripe_user_config( $feed ) {

		$feed[ 'meta' ][ 'update_user_action' ] = RGForms::post( 'gf_stripe_update_user_action' );
		$feed[ 'meta' ][ 'update_site_action' ] = RGForms::post( 'gf_stripe_update_site_action' );

		return $feed;
	}

	/**
	 * @param $entry
	 * @param $feed
	 * @param $transaction_id
	 */
	public static function downgrade_stripe_user( $entry, $feed, $transaction_id, $processor, $user_id = false ) {

		if ( ( ! $feed ) || ( empty( $feed[ 'meta' ][ 'update_user_action' ] ) ) ) {
			return;
		}

		GFP_Stripe::log_debug( __( 'Downgrading Stripe user...', 'gravityforms-stripe-more' ) );

		if ( is_multisite() ) {

			GFP_Stripe::log_debug( __( 'Multisite, getting created site ID', 'gravityforms-stripe-more' ) );

			$site_id = gf_user_registration()->get_site_by_entry_id( $entry[ 'id' ] );

			if ( $site_id ) {

				GFP_Stripe::log_debug( __( "Switching to site {$site_id}" ) );

				switch_to_blog( $site_id );
			} else {
				GFP_Stripe::log_debug( __( "No created site ID for entry {$entry['id']}" ) );
			}
		}

		if ( $user_id ) {
			$user = get_user_by( 'id', $user_id );
			if ( ! $user ) {
				GFP_Stripe::log_debug( __( "Unable to get user with ID {$user_id}" ) );

				return;
			}
		} else {
			GFP_Stripe::log_debug( __( "Getting user ID from entry" ) );

			$user = gf_user_registration()->get_user_by_entry_id( $entry[ 'id' ] );
			if ( ! $user ) {
				GFP_Stripe::log_debug( __( "Unable to get user ID from entry {$entry['id']}" ) );

				return;
			}
		}

		GFP_Stripe::log_debug( __( "Setting user {$user->ID} role to {$feed['meta']['update_user_action']}" ) );

		$user->set_role( $feed[ 'meta' ][ 'update_user_action' ] );

		if ( is_multisite() && ! empty( $site_id ) ) {

			GFP_Stripe::log_debug( __( 'Multisite, switching back to main site', 'gravityforms-stripe-more' ) );

			restore_current_blog();

			$user = gf_user_registration()->get_user_by_entry_id( $entry[ 'id' ] );
			if ( is_user_member_of_blog( $user->ID ) ) {
				$user->set_role( $feed[ 'meta' ][ 'update_user_action' ] );
			}
		}
	}

	/**
	 * @param $entry
	 * @param $feed
	 */
	public static function downgrade_stripe_site( $entry, $feed ) {

		global $current_site;

		$action = rgar( $feed[ 'meta' ], 'update_site_action' );

		if ( empty( $action ) ) {
			return;
		}

		$site_id = gf_user_registration()->get_site_by_entry_id( $entry[ 'id' ] );

		if ( ! $site_id ) {
			return;
		}

		switch ( $action ) {

			case 'deactivate':

				do_action( 'deactivate_blog', $site_id );
				update_blog_status( $site_id, 'deleted', '1' );

				break;

			case 'delete':

				require_once( ABSPATH . 'wp-admin/includes/ms.php' );

				if ( ( '0' != $site_id ) && ( $site_id != $current_site->blog_id ) ) {
					wpmu_delete_blog( $site_id, true );
				}

				break;
		}

	}

	//------------------------------------------------------
	//------------- SETTINGS PAGE --------------------------
	//------------------------------------------------------
	/**
	 * @return bool
	 */
	public static function gfp_stripe_settings_page_action() {

		if ( isset( $_POST[ 'more_stripe_uninstall' ] ) ) {
			check_admin_referer( 'more_stripe_uninstall', 'gfp_more_stripe_uninstall' );
			self::uninstall();
			?>
			<div class="updated fade" style="padding:20px;">
				<?php echo sprintf( __( "Gravity Forms + (More) Stripe has been successfully uninstalled. It can be re-activated from the %splugins page%s.", 'gravityforms-stripe-more' ), "<a href='plugins.php'>", "</a>" ) ?>
			</div>
			<?php
			return true;
		}
	}

	/**
	 * @param $settings
	 */
	public static function gfp_stripe_settings_page( $settings ) {

		$webhook_url = add_query_arg( 'page', 'gfp_more_stripe_listener', trailingslashit( home_url() ) );

		require_once( GFP_MORE_STRIPE_PATH . '/includes/settings-page/views/settings-page-webhook-setup.php' );
		require_once( GFP_MORE_STRIPE_PATH . '/includes/settings-page/views/settings-page-additional-options.php' );

	}

	/**
	 * @param $settings
	 *
	 * @return array
	 */
	public static function gfp_stripe_save_settings( $settings ) {

		$current_settings = get_option( 'gfp_stripe_settings' );

		if ( ( ! is_array( $current_settings ) ) || ( is_array( $current_settings ) && ! array_key_exists( 'stripe_webhook_configured', $current_settings ) ) ) {
			$settings[ 'do_usage_stats' ] = 'true';
		}

		$settings[ 'stripe_webhook_configured' ]       = rgpost( 'gfp_stripe_webhook_configured' );
		$settings[ 'disable_save_customers_as_users' ] = rgpost( 'gfp_stripe_disable_save_customers_as_users' );
		$settings[ 'enable_early_access' ]             = rgpost( 'gfp_stripe_enable_early_access' );

		if ( ! empty( $settings[ 'enable_early_access' ] ) ) {

			$settings[ 'do_usage_stats' ] = 'true';

			if ( empty( $settings[ 'enable_early_access' ] ) ) {
				do_action( 'gfp_stripe_usage_event', 'enable_early_access' );
			}

		}

		return $settings;
	}

	/**
	 *
	 */
	public static function gfp_stripe_before_uninstall_button( $settings ) {

		if ( GFCommon::current_user_can_any( 'gfp_stripe_uninstall' ) ) {
			require_once( GFP_MORE_STRIPE_PATH . '/includes/settings-page/views/settings-page-uninstall.php' );
		}

	}

	/**
	 *
	 * @since 1.7.9.0
	 *
	 * @uses  get_option()
	 *
	 * @param $data
	 *
	 * @return void
	 */
	public static function gfp_stripe_usage_stats( $data ) {

		$settings                           = get_option( 'gfp_stripe_settings' );

		$data[ 'options' ][ 'more_stripe' ] = array(
			'version'                         => self::$version,
			'disable_save_customers_as_users' => $settings[ 'disable_save_customers_as_users' ],
			'enable_early_access'             => $settings[ 'enable_early_access' ]
		);

		return $data;
	}

	//------------------------------------------------------
	//------------- STRIPE RULES LIST PAGE -----------------
	//------------------------------------------------------
	/**
	 * @param $feed
	 *
	 * @internal param $setting
	 */
	public static function gfp_stripe_list_feeds_product_type( $feed ) {

		switch ( $feed[ 'meta' ][ 'type' ] ) {
			case 'product' :
				_e( 'One-Time Payment', 'gravityforms-stripe-more' );
				break;

			case 'subscription' :
				_e( 'Subscription', 'gravityforms-stripe-more' );
				break;

			case 'update-billing' :
				_e( 'Billing Info Update', 'gravityforms-stripe-more' );
				break;

			case 'update-subscription' :
				_e( 'Subscription Update', 'gravityforms-stripe-more' );
				break;
		}

	}

	//------------------------------------------------------
	//------------- STRIPE RULE EDIT PAGE ------------------
	//------------------------------------------------------

	/**
	 * Add JS vars to edit feed JS
	 *
	 * @since 1.8.20.1
	 *
	 * @param array $edit_feed_js_data
	 * @param array $form
	 * @param array $feed
	 *
	 * @return array
	 */
	public static function gfp_stripe_edit_feed_js_data( $edit_feed_js_data, $form, $feed ) {

		$edit_feed_js_data[ 'metadata' ] = ( empty( $form ) || empty( $feed ) ) ?
			'' :
			rgar( $feed[ 'meta' ], 'metadata' );

		$edit_feed_js_data[ 'form_fields' ] = empty( $form ) ? array() : GFP_Stripe::get_form_fields( $form );

		$edit_feed_js_data[ 'metadata_key_name_placeholder' ] = __( 'Enter Metadata Key Name', 'gravityforms-stripe-more' );
		$edit_feed_js_data[ 'metadata_add_field_tooltip' ]    = __( 'Add another metadata field', 'gravityforms-stripe-more' );
		$edit_feed_js_data[ 'metadata_remove_field_tooltip' ] = __( 'Remove this metadata field', 'gravityforms-stripe-more' );

		$edit_feed_js_data[ 'metadata_add_img_url' ]    = GFCommon::get_base_url() . '/images/add.png';
		$edit_feed_js_data[ 'metadata_remove_img_url' ] = GFCommon::get_base_url() . '/images/remove.png';

		wp_enqueue_style( 'gfp_more_stripe_edit_feed', trailingslashit( GFP_MORE_STRIPE_URL ) . 'includes/form-settings/css/edit-feed.css', array(), self::$version );

		return $edit_feed_js_data;
	}

	/**
	 * @param $more_endselectform_args
	 * @param $form
	 *
	 * @return mixed
	 */
	public static function gfp_stripe_feed_endselectform_args( $more_endselectform_args, $form ) {

		$subscription_plan_fields = GFP_Stripe::get_product_options( $form, '', true );
		$setup_fee_fields         = GFP_Stripe::get_product_options( $form, '', false );
		$coupon_fields            = self::get_coupon_options( $form, '' );
		$subscription_end_fields  = self::get_all_field_options( $form, '' );
		$currency_fields          = GFP_More_Stripe_Currency::get_currency_fields( $form, '' );

		$populate_field_options = "jQuery('#gfp_stripe_subscription_plan').html(\"" . $subscription_plan_fields . "\");
		if ( 'update-subscription' == type ) {
		jQuery(\"#gfp_stripe_subscription_plan option[value='all']\").remove();
		}
		jQuery('#gfp_more_stripe_currency_field').html(\"" . $currency_fields . "\");
			                                                                                        jQuery(\"#gfp_more_stripe_coupons_field\").html(\"" . $coupon_fields . "\");
			                                                                                        jQuery(\"#gfp_more_stripe_subscription_end_after_field\").html(\"" . $subscription_end_fields . "\");
			                                                                                        jQuery(\"#gfp_more_stripe_setup_fee_amount_field\").html(\"" . $setup_fee_fields . "\");";

		$post_update_action = "if (type == 'subscription' && post_fields.length > 0) {
			                                                                                                                        jQuery(\"#stripe_post_update_action\").show();
			                                                                                                                }
			                                                                                                                else {
			                                                                                                                        jQuery(\"#gfp_stripe_update_post\").attr(\"checked\", false);
			                                                                                                                        jQuery(\"#stripe_post_update_action\").hide();
			                                                                                                                }";
		$show_fields        = "if ( type == 'subscription' ) {
			                                                                                                                        jQuery(\"#gfp_stripe_billing_info\").show();
			                                                                                                                        jQuery(\"#stripe_field_container_coupon\").show();
			                                                                                                                        jQuery(\"#stripe_field_container_subscription_end_after\").show();
			                                                                                                                        jQuery(\"#stripe_field_container_setup_fee\").show();
			                                                                                                                        jQuery(\"#stripe_field_container_custom_metadata\").show();
			                                                                                                                        jQuery(\"#stripe_auto_login\").show();
			                                                                                                                        jQuery(\"#stripe_disable_new_users\").show();
			                                                                                                                        jQuery(\"#stripe_field_container_alternate_charge_option\").hide();
			                                                                                                                        jQuery(\"#stripe_disable_prorate\").hide();
			                                                                                                                        jQuery(\"#stripe_charge_upgrade_immediately\").hide();
			                                                                                                                        jQuery(\"#stripe_cancel_at_period_end\").hide();
			                                                                                                                }
			                                                                                                                else if ( type == 'update-subscription' ) {
			                                                                                                                        jQuery(\"#stripe_field_container_subscription\").show();
			                                                                                                                        jQuery(\"#stripe_disable_prorate\").show();
			                                                                                                                        jQuery(\"#stripe_charge_upgrade_immediately\").show();
			                                                                                                                        jQuery(\"#stripe_cancel_at_period_end\").show();
			                                                                                                                        jQuery(\"#stripe_field_container_coupon\").hide();
			                                                                                                                        jQuery(\"#stripe_field_container_subscription_end_after\").hide();
			                                                                                                                        jQuery(\"#stripe_field_container_setup_fee\").hide();
			                                                                                                                        jQuery(\"#stripe_field_container_custom_metadata\").hide();
			                                                                                                                        jQuery(\"#stripe_field_container_alternate_charge_option\").hide();
			                                                                                                                        jQuery(\"#gfp_stripe_billing_info\").hide();
			                                                                                                                        jQuery(\"#stripe_auto_login\").hide();
			                                                                                                                        jQuery(\"#stripe_disable_new_users\").hide();
			                                                                                                                        jQuery(\"#stripe_field_container_currency\").hide();
			                                                                                                                }
			                                                                                                                else if ( type == 'update-billing' ) {
			                                                                                                                        jQuery(\"#stripe_field_container_coupon\").hide();
			                                                                                                                        jQuery(\"#stripe_field_container_subscription_end_after\").hide();
			                                                                                                                        jQuery(\"#stripe_field_container_setup_fee\").hide();
			                                                                                                                        jQuery(\"#stripe_field_container_custom_metadata\").hide();
			                                                                                                                        jQuery(\"#stripe_field_container_alternate_charge_option\").hide();
			                                                                                                                        jQuery(\"#stripe_disable_prorate\").hide();
			                                                                                                                        jQuery(\"#stripe_charge_upgrade_immediately\").hide();
			                                                                                                                        jQuery(\"#stripe_cancel_at_period_end\").hide();
			                                                                                                                        jQuery(\"#stripe_auto_login\").hide();
			                                                                                                                        jQuery(\"#stripe_disable_new_users\").hide();
			                                                                                                                        jQuery(\"#stripe_field_container_currency\").hide();
			                                                                                                                        jQuery(\"#gfp_stripe_billing_info\").show();
			                                                                                                                }
			                                                                                                                else if ( type == 'product' ) {
			                                                                                                                jQuery(\"#gfp_stripe_billing_info\").show();
			                                                                                                                        jQuery(\"#stripe_field_container_alternate_charge_option\").show();
			                                                                                                                        jQuery(\"#stripe_field_container_coupon\").show();
			                                                                                                                        jQuery(\"#stripe_field_container_subscription_end_after\").hide();
			                                                                                                                        jQuery(\"#stripe_field_container_setup_fee\").hide();
			                                                                                                                        jQuery(\"#stripe_field_container_custom_metadata\").show();
			                                                                                                                        jQuery(\"#stripe_auto_login\").show();
			                                                                                                                        jQuery(\"#stripe_disable_new_users\").show();
			                                                                                                                        jQuery(\"#stripe_disable_prorate\").hide();
			                                                                                                                        jQuery(\"#stripe_charge_upgrade_immediately\").hide();
			                                                                                                                        jQuery(\"#stripe_cancel_at_period_end\").hide();
			                                                                                                                }";

		$more_endselectform_args[ 'populate_field_options' ][ ] = $populate_field_options;
		$more_endselectform_args[ 'post_update_action' ][ ]     = $post_update_action;
		$more_endselectform_args[ 'show_fields' ][ ]            = $show_fields;
		$more_endselectform_args[ 'form_fields' ]               = GFP_Stripe::get_form_fields( $form );


		return $more_endselectform_args;
	}

	/**
	 * @param $settings
	 * @param $feed
	 */
	public static function gfp_stripe_feed_transaction_type( $feed, $settings ) {

		require_once( GFP_MORE_STRIPE_PATH . '/includes/form-settings/views/stripe-feed-transaction-type.php' );

		return $feed;
	}

	/**
	 * @param $feed
	 * @param $form
	 */
	public static function gfp_stripe_feed_after_transaction_type( $feed, $form ) {

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_script( 'gfp_more_stripe_form_settings_edit_feed_js', trailingslashit( GFP_MORE_STRIPE_URL ) . "js/form-settings-edit-feed{$suffix}.js", array(
			'jquery',
			'gfp_stripe_form_settings_edit_feed_js'
		), GFPMoreStripe::get_version() );

		require_once( GFP_MORE_STRIPE_PATH . '/includes/form-settings/views/stripe-feed-after-transaction-type.php' );

	}

	/**
	 * @param $feed
	 * @param $form
	 */
	public static function gfp_stripe_feed_before_billing( $feed, $form ) {
		require_once( GFP_MORE_STRIPE_PATH . '/includes/form-settings/views/stripe-feed-before-billing.php' );
	}

	/**
	 * @param $display
	 * @param $feed
	 *
	 * @return bool
	 */
	public static function gfp_stripe_display_billing_info( $display, $feed ) {

		if ( 'update-subscription' == rgars( $feed, 'meta/type' ) ) {
			$display = false;
		}

		return $display;
	}

	/**
	 * @param $feed
	 * @param $form
	 */
	public static function gfp_stripe_feed_after_billing( $feed, $form ) {

		$coupon_options = self::get_coupon_options( $form, rgar( $feed[ 'meta' ], 'coupons_field' ) );
		require_once( GFP_MORE_STRIPE_PATH . '/includes/form-settings/views/stripe-feed-coupons.php' );

		$setup_fee_options = GFP_Stripe::get_product_options( $form, rgar( $feed[ 'meta' ], 'setup_fee_amount_field' ), false );
		require_once( GFP_MORE_STRIPE_PATH . '/includes/form-settings/views/stripe-feed-setup-fee.php' );

		require_once( GFP_MORE_STRIPE_PATH . '/includes/form-settings/views/stripe-feed-custom-metadata.php' );

		$settings = get_option( 'gfp_stripe_settings' );

		if ( array_key_exists( 'enable_early_access', $settings ) && 'on' == $settings[ 'enable_early_access' ] ) {
			$subscription_end_options = self::get_all_field_options( $form, rgar( $feed[ 'meta' ], 'subscription_end_after_field' ) );
			require_once( GFP_MORE_STRIPE_PATH . '/includes/form-settings/views/stripe-feed-subscription-end.php' );
		}

		require_once( GFP_MORE_STRIPE_PATH . '/includes/form-settings/views/stripe-feed-alternate-charge-options.php' );
	}

	/**
	 * @param $feed
	 * @param $form
	 * @param $settings
	 */
	public static function gfp_stripe_feed_options( $feed, $form, $settings ) {

		require_once( GFP_MORE_STRIPE_PATH . '/includes/form-settings/views/stripe-feed-options-update-subscription.php' );

		require_once( GFP_MORE_STRIPE_PATH . '/includes/form-settings/views/stripe-feed-options-disable-new-users.php' );

		require_once( GFP_MORE_STRIPE_PATH . '/includes/form-settings/views/stripe-feed-options-new-users.php' );
	}

	/**
	 * @param $form
	 * @param $selected_field
	 *
	 * @return string
	 */
	public static function get_coupon_options( $form, $selected_field ) {

		$str    = "<option value=''>" . __( 'Select a field', 'gravityforms-stripe-more' ) . '</option>';
		$fields = GFCommon::get_fields_by_type( $form, array( 'text', 'hidden' ) );

		foreach ( $fields as $field ) {

			$field_id    = $field[ "id" ];
			$field_label = RGFormsModel::get_label( $field );

			$selected = $field_id == $selected_field ? "selected='selected'" : "";
			$str .= "<option value='" . $field_id . "' " . $selected . ">" . $field_label . '</option>';

		}


		return $str;
	}

	/**
	 * @param $form
	 * @param $selected_field
	 *
	 * @return string
	 */
	public static function get_all_field_options( $form, $selected_field ) {

		$str    = "<option value=''></option>";
		$fields = GFP_Stripe::get_form_fields( $form );

		foreach ( $fields as $field ) {

			$field_id    = $field[ 0 ];
			$field_label = esc_html( GFCommon::truncate_middle( $field[ 1 ], 40 ) );

			$selected = $field_id == $selected_field ? "selected='selected'" : "";
			$str .= "<option value='" . $field_id . "' " . $selected . ">" . $field_label . '</option>';

		}

		return $str;
	}

	/**
	 * @param $feed
	 *
	 * @return mixed
	 */
	public static function gfp_stripe_before_save_feed( $feed ) {

		//subscription plan field
		$feed[ 'meta' ][ 'free_trial_no_credit_card' ] = rgpost( 'gfp_more_stripe_free_trial_no_credit_card' );

		$feed[ 'meta' ][ 'subscription_plan_field' ] = rgpost( 'gfp_stripe_subscription_plan' );

		$feed[ 'meta' ][ 'coupons_enabled' ] = rgpost( 'gfp_more_stripe_enable_coupons' );
		$feed[ 'meta' ][ 'coupons_field' ]   = rgpost( 'gfp_more_stripe_coupons_field' );
		$feed[ 'meta' ][ 'coupons_apply' ]   = rgpost( 'gfp_more_stripe_coupons_apply' );

		$feed[ 'meta' ][ 'currency_override' ] = rgpost( 'gfp_more_stripe_override_default_currency' );
		$feed[ 'meta' ][ 'currency_field' ]    = rgpost( 'gfp_more_stripe_currency_field' );

		$feed[ 'meta' ][ 'setup_fee_enabled' ]      = rgpost( 'gfp_more_stripe_setup_fee_enable' );
		$feed[ 'meta' ][ 'setup_fee_amount_field' ] = rgpost( 'gfp_more_stripe_setup_fee_amount_field' );

		$feed[ 'meta' ][ 'subscription_end_after_field' ] = rgpost( 'gfp_more_stripe_subscription_end_after_field' );

		$feed[ 'meta' ][ 'alternate_charge_option' ] = rgpost( 'gfp_more_stripe_alternate_charge_option' );

		$feed[ 'meta' ][ 'disable_prorate' ]            = rgpost( 'gfp_more_stripe_disable_prorate' );
		$feed[ 'meta' ][ 'charge_upgrade_immediately' ] = rgpost( 'gfp_more_stripe_charge_upgrade_immediately' );
		$feed[ 'meta' ][ 'cancel_at_period_end' ]       = rgpost( 'gfp_more_stripe_cancel_at_period_end' );

		$feed[ 'meta' ][ 'auto_login' ] = rgpost( 'gfp_more_stripe_auto_login' );

		$feed[ 'meta' ][ 'disable_new_users' ] = rgpost( 'gfp_more_stripe_disable_new_users' );

		$feed[ 'meta' ][ 'metadata_enabled' ] = rgpost( 'gfp_more_stripe_enable_metadata' );

		if ( empty( $feed[ 'meta' ][ 'metadata_enabled' ] ) ) {

				$feed[ 'meta' ][ 'metadata' ] = '';

		} else {

				$json                         = stripslashes( rgpost( 'gfp_stripe_metadata' ) );
			$feed[ 'meta' ][ 'metadata' ] = GFCommon::json_decode( $json );

			if ( is_array( $feed[ 'meta' ][ 'metadata' ] ) ) {

				foreach ( $feed[ 'meta' ][ 'metadata' ] as $key => &$metadata ) {

					if ( empty( $metadata[ 'key_value' ] ) ) {

						unset( $feed[ 'meta' ][ 'metadata' ][ $key ] );

					} else if ( is_array( $metadata[ 'key_value' ] ) ) {

						$metadata[ 'key_value' ] = $metadata[ 'key_value' ][ 0 ];

					}

				}
			}
		}

		return $feed;
	}

	/**
	 * @param $feed
	 * @param $form
	 * @param $new_feed
	 */
	public static function gfp_stripe_after_save_feed( $feed, $form, $new_feed ) {

		if ( $new_feed ) {

			$stripe_form_meta = GFP_Stripe_Data::get_stripe_form_meta( $form[ 'id' ] );

			if ( 1 == count( $stripe_form_meta[ 'rules' ] ) ) {

				$stripe_form_meta[ 'form_settings' ][ 'mode' ] = 'test';
				$result                                        = GFP_Stripe_Data::update_stripe_form_meta( $form[ 'id' ], $stripe_form_meta[ 'form_settings' ], 'form_settings' );

			}
		}
	}


	//------------------------------------------------------
	//------------- FORM EDITOR ---------------------------
	//------------------------------------------------------

	private static function add_stripe_mode_toggle() {

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_script( 'gfp_more_stripe_form_list', trailingslashit( GFP_MORE_STRIPE_URL ) . "js/form-list{$suffix}.js", array( 'jquery' ), GFPMoreStripe::get_version() );

		$form_list_js_data = array(
			'test_text'                 => __( 'Test', 'gravityforms-stripe-more' ),
			'live_text'                 => __( 'Live', 'gravityforms-stripe-more' ),
			'test_img'                  => GFCommon::get_base_url() . '/images/active0.png',
			'live_img'                  => GFCommon::get_base_url() . '/images/active1.png',
			'nonce'                     => wp_create_nonce( 'gfp_more_stripe_update_form_stripe_mode' ),
			'update_mode_error_message' => __( 'Ajax error while updating Stripe mode', 'gravityforms-stripe-more' ),
			'stripe_feeds'              => GFP_Stripe_Data::get_all_feeds()
		);

		wp_localize_script( 'gfp_more_stripe_form_list', 'stripe_form_list', $form_list_js_data );
	}

	public static function gfp_more_stripe_update_form_stripe_mode() {

		check_ajax_referer( 'gfp_more_stripe_update_form_stripe_mode', 'gfp_more_stripe_update_form_stripe_mode' );

		$form_id = intval( $_POST[ 'form_id' ] );
		$mode    = (string) $_POST[ 'mode' ];

		$stripe_form_meta                              = GFP_Stripe_Data::get_stripe_form_meta( $form_id );
		$stripe_form_meta[ 'form_settings' ][ 'mode' ] = $mode;
		$result                                        = GFP_Stripe_Data::update_stripe_form_meta( $form_id, $stripe_form_meta[ 'form_settings' ], 'form_settings' );

		return $result;
	}

	/**
	 *
	 * @since 1.8.2
	 *
	 * @param $position
	 * @param $form_id
	 *
	 * @return void
	 */
	public static function gform_field_standard_settings( $position, $form_id ) {

		if ( 37 == $position ) {
			require_once( GFP_MORE_STRIPE_PATH . '/includes/form-editor/views/field-setting-enable_stripe_subscription.php' );
			require_once( GFP_MORE_STRIPE_PATH . '/includes/form-editor/views/field-setting-create_stripe_plan.php' );
		}

	}

	public static function gform_editor_js() {

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_script( 'gfp_more_stripe_form_editor_subscription', GFP_MORE_STRIPE_URL . "/includes/form-editor/js/form-editor-subscription-settings{$suffix}.js", array( 'gform_form_editor' ), GFPMoreStripe::get_version() );

	}

	/**
	 * @param $predefined_choices
	 *
	 * @return mixed
	 */
	public static function gform_predefined_choices( $predefined_choices ) {

		$available_currencies                   = RGCurrency::get_currencies();
		$choice_category                        = __( 'Currencies', 'gravityforms-stripe-more' );
		$predefined_choices[ $choice_category ] = array_keys( $available_currencies );

		return $predefined_choices;
	}

	//------------------------------------------------------
	//------------- FORM DISPLAY ---------------------------
	//------------------------------------------------------

	/**
	 * @param null $form
	 * @param null $ajax
	 */
	public static function gform_enqueue_scripts( $form = null, $ajax = null ) {

		if ( GFP_Stripe::is_stripe_form( $form ) ) {

			if ( is_user_logged_in() ) {
				$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
				wp_enqueue_script( 'gfp-stripe-creditcard-field', GFP_MORE_STRIPE_URL . "/js/creditcard_field{$suffix}.js", array( 'jquery' ), GFPMoreStripe::get_version() );
			}

			self::enqueue_stripe_coupons_js( $form );
		}

	}

	/**
	 * @param $publishable_key
	 * @param $form_id
	 *
	 * @return string
	 */
	public static function gfp_stripe_get_publishable_key( $publishable_key, $form_id ) {

		return GFP_Stripe::get_api_key( 'publishable', GFPMoreStripe::get_stripe_mode( $form_id ) );
	}

	/**
	 * @param $field_info
	 * @param $stripe_rules
	 *
	 * @return array
	 */
	public static function gfp_stripe_rule_field_info( $field_info, $stripe_rules, $form ) {

		//Find out if there's a coupon field and get field ID
		if ( array_key_exists( 0, $stripe_rules ) && ( is_array( $stripe_rules[ 0 ] ) ) ) {

			foreach ( $stripe_rules as $key => $rule ) {

				if ( ! empty( $rule[ 'meta' ][ 'free_trial_no_credit_card' ] ) ) {
					$field_info[ $key ] = array_merge( $field_info[ $key ], array( 'no_credit_card' => true ) );
				}

				$coupon_field_id = self::get_coupon_id_from_feed( $rule );

				if ( $coupon_field_id && ! GFP_Stripe_Helper::is_hidden_field_type( $form, $coupon_field_id ) ) {

					$field_info[ $key ] = array_merge( $field_info[ $key ], array( 'coupon_field_id' => $coupon_field_id ) );

					continue 1;

				}

			}

		} else {

			if ( ! empty( $stripe_rules[ 'meta' ][ 'free_trial_no_credit_card' ] ) ) {

				$field_info = array_merge( $field_info, array( 'no_credit_card' => true ) );

			}

			$coupon_field_id = self::get_coupon_id_from_feed( $stripe_rules );

			if ( $coupon_field_id && ! GFP_Stripe_Helper::is_hidden_field_type( $form, $coupon_field_id ) ) {

				$field_info = array_merge( $field_info, array( 'coupon_field_id' => $coupon_field_id ) );

			}

		}

		return $field_info;
	}

	/**
	 * Get coupon field ID from feed
	 *
	 * @since 1.7.9.0
	 *
	 * @param array $feed
	 *
	 * @return bool|string
	 */
	private static function get_coupon_id_from_feed( $feed ) {

		$coupon_field_id = false;
		$feed            = $feed[ 'meta' ];

		if ( 'update-subscription' !== $feed[ 'type' ] && 'update-billing' !== $feed[ 'type' ] ) {

			if ( isset( $feed[ 'coupons_enabled' ] ) && ! empty( $feed[ 'coupons_enabled' ] ) ) {
				$coupon_field_id = $feed[ 'coupons_field' ];
			}

		}

		return $coupon_field_id;
	}

	/**
	 * Add JS for coupons
	 *
	 * @since 1.8.17.1
	 *
	 * @param $form
	 *
	 */
	private static function enqueue_stripe_coupons_js( $form ) {

		$field_info = GFP_Stripe::get_stripe_rule_field_info();

		if ( ! empty( $field_info ) ) {

			if ( array_key_exists( 0, $field_info ) && ( is_array( $field_info[ 0 ] ) ) ) {

				foreach ( $field_info as $js_field_info ) {

					if ( isset( $js_field_info[ 'coupon_field_id' ] ) ) {
						$coupon_field_id = $js_field_info[ 'coupon_field_id' ];
						continue 1;
					}

				}

			} else if ( isset( $field_info[ 'coupon_field_id' ] ) ) {

				$coupon_field_id = $field_info[ 'coupon_field_id' ];

			}

		}

		if ( isset( $coupon_field_id ) ) {

			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			wp_enqueue_script( 'gfp_stripe_coupons_js', trailingslashit( GFP_MORE_STRIPE_URL ) . "js/form-display-coupons{$suffix}.js", array( 'jquery' ), self::get_version() );

			$protocol    = isset ( $_SERVER[ "HTTPS" ] ) ? 'https://' : 'http://';
			$ajaxurl     = admin_url( 'admin-ajax.php', $protocol );
			$spinner_url = apply_filters( "gform_ajax_spinner_url_{$form['id']}", apply_filters( "gform_ajax_spinner_url", GFCommon::get_base_url() . "/images/spinner.gif", $form ), $form );

			$gfp_stripe_coupons_js_vars = array(
				'ajaxurl'         => $ajaxurl,
				'spinner_url'     => $spinner_url,
				'form_id'         => $form[ 'id' ],
				'coupon_field_id' => $coupon_field_id
			);

			wp_localize_script( 'gfp_stripe_coupons_js', 'gfp_stripe_coupons_js_vars', $gfp_stripe_coupons_js_vars );
		}
	}

	/**
	 * @param $payment_methods
	 * @param $field
	 * @param $form_id
	 *
	 * @return array
	 */
	public static function gform_payment_methods( $payment_methods, $field, $form_id ) {

		if ( is_user_logged_in() && GFP_Stripe::is_stripe_form( $form_id ) ) {

			$skip       = false;
			$form_feeds = GFP_Stripe_Data::get_feed_by_form( $form_id, true );

			foreach ( $form_feeds as $feed ) {

				if ( 'update-billing' == $feed[ 'meta' ][ 'type' ] ) {
					$skip = true;
				}

			}

			if ( ! $skip ) {

				$user_id             = get_current_user_id();
				$payment_method_list = GFP_Stripe_Helper::get_customer_payment_method_list( $user_id );

				if ( ! empty( $payment_method_list ) ) {
					$payment_methods = $payment_method_list;
				}

			}

		}

		return $payment_methods;
	}

	//------------------------------------------------------
	//-------------------- PROCESSING ----------------------
	//------------------------------------------------------

	/**
	 * @param $validation_result
	 * @param $value
	 * @param $field
	 *
	 * @return mixed
	 */
	public static function gfp_stripe_gform_field_validation( $validation_result, $value, $field ) {

		if ( ! ( rgempty( 'gform_payment_method' ) || ( 'creditcard' == rgpost( 'gform_payment_method' ) ) ) ) {

			$validation_result[ 'is_valid' ] = true;

			unset( $validation_result[ 'message' ] );

			GFP_Stripe::log_error( __( 'Using a saved credit card. Ignore previous empty token error.' ) );

		} else {

			$no_credit_card = rgpost( 'stripe_no_credit_card' );

			if ( ! empty( $no_credit_card ) ) {

				$validation_result[ 'is_valid' ] = true;

				unset( $validation_result[ 'message' ] );

				GFP_Stripe::log_error( __( 'Using a Stripe rule with no credit card required. Ignore previous empty token error.' ) );

			}

		}

		return $validation_result;
	}

	public static function gfp_stripe_is_ready_for_capture( $is_ready_for_capture, $reason, $validation_result ) {

		if ( 'creditcard' == $reason ) {

			$no_credit_card = rgpost( 'stripe_no_credit_card' );

			if ( ! empty( $no_credit_card ) ) {
				$is_ready_for_capture = GFP_Stripe::get_feed_that_meets_condition( $validation_result[ 'form' ] );
			}

		}

		return $is_ready_for_capture;
	}

	/**
	 * @param $validation_result
	 * @param $feed
	 *
	 * @return mixed
	 */
	public static function gfp_stripe_gform_validation( $validation_result, $feed ) {

		switch ( $feed[ 'meta' ][ 'type' ] ) {

			case 'product':
				$validation_result = self::make_one_time_payment( $feed, $validation_result );
				break;

			case 'subscription':
				$validation_result = self::start_subscription( $feed, $validation_result );
				break;

			case 'update-billing':
				$validation_result = self::update_billing_info( $feed, $validation_result );
				break;

			case 'update-subscription':
				$validation_result = self::update_subscription( $feed, $validation_result );
				break;

		}

		return $validation_result;
	}

	/**
	 * @param $validation_result
	 * @param $post
	 * @param $error_message
	 */
	public static function gfp_stripe_set_validation_result( $validation_result, $post, $error_message ) {

		$feed = GFP_Stripe::get_feed_that_meets_condition( $validation_result[ 'form' ] );

		if ( 'update-subscription' == $feed[ 'meta' ][ 'type' ] ) {
			$validation_result[ 'form' ][ 'fields' ][ 0 ][ 'validation_message' ] = $error_message;
		}

		return $validation_result;
	}

	/**
	 * @param $validation_message
	 * @param $form
	 *
	 * @return string
	 */
	public static function gform_validation_message( $validation_message, $form ) {

		$feed = GFP_Stripe::get_feed_that_meets_condition( $form );

		if ( 'update-subscription' == $feed[ 'meta' ][ 'type' ] ) {
			$error_message                                 = $form[ 'fields' ][ 0 ][ 'validation_message' ];
			$form[ 'fields' ][ 0 ][ 'validation_message' ] = '';
			$validation_message                            = "<div class='validation_error'>" . $error_message . "</div>";
		}

		return $validation_message;
	}

//------------------------------------

	/**
	 * @param $feed
	 * @param $validation_result
	 *
	 * @return mixed
	 */
	private static function make_one_time_payment( $feed, $validation_result ) {

		$form = $validation_result[ 'form' ];

		GFP_Stripe::log_debug( "Starting to make a product payment for form: {$form['id']}" );

		$form_data = GFP_Stripe::get_form_data( $form, $feed );

		if ( $form_data[ 'amount' ] < 0.5 ) {
			GFP_Stripe::log_debug( 'Amount is less than $0.50. No need to process payment, but act as if transaction was successful' );

			if ( GFP_Stripe::is_last_page( $form ) ) {
				$card_field                             = GFP_Stripe::get_creditcard_field( $form );
				$_POST[ "input_{$card_field["id"]}_1" ] = '';
			}
			if ( GFP_Stripe::has_visible_products( $form ) ) {
				$transaction_response = array(
					'transaction_id'   => 'N/A',
					'amount'           => $form_data[ "amount" ],
					'transaction_type' => 1
				);
			}

			return $validation_result;
		}

		$api_key = GFP_Stripe::get_api_key( 'secret', $form_data[ 'stripe_mode' ] );

		$create_new_customer = true;
		$create_new_card     = false;

		$new_card            = $form_data[ 'credit_card' ];

		$currency            = ( ! empty( $form_data[ 'currency' ] ) ) ? $form_data[ 'currency' ] : null;

		global $current_user;
		get_currentuserinfo();

		if ( ( is_user_logged_in() ) && ( ! is_wp_error( $customer = self::get_user_customer_object( apply_filters( 'gfp_more_stripe_api_key', $api_key, 'get_user_customer_object' ), $user_id = $current_user->ID ) ) ) ) {
			if ( is_object( $customer ) ) {
				$create_new_customer = apply_filters( 'gfp_more_stripe_create_new_customer', false, $user_id, $customer );
			}
		}

		if ( is_user_logged_in() ) {
			$form_data[ 'name' ] = rtrim( $form_data[ 'name' ] );
			$name                = empty( $form_data[ 'name' ] ) ? $current_user->user_firstname . ' ' . $current_user->user_lastname : $form_data[ 'name' ];
			$email               = empty( $form_data[ 'email' ] ) ? $current_user->user_email : $form_data[ 'email' ];
			if ( ( empty( $currency ) ) || ( 3 !== strlen( $currency ) ) ) {
				$currency = GFP_More_Stripe_Customer_API::get_stripe_customer_currency( $user_id );
				if ( empty( $currency ) ) {
					$currency = GFCommon::get_currency();
				}
			}
			if ( ( ! $create_new_customer ) && ( ! empty( $new_card ) ) ) {
				$create_new_card = true;
				$card            = $new_card;
			} else {
				$card = ( $create_new_customer ) ? $new_card : $form_data[ 'card' ];
			}
		} else {
			$email = $form_data[ 'email' ];
			$name  = $form_data[ 'name' ];
			if ( ( empty( $currency ) ) || ( 3 !== strlen( $currency ) ) ) {
				$currency = GFCommon::get_currency();
			}
			$card = $new_card;
		}

		if ( $create_new_customer ) {

			$customer_args = apply_filters( 'gfp_more_stripe_create_new_customer_args',
			                                array(
				                                'source'        => $card,
				                                'description' => apply_filters( 'gfp_stripe_customer_description', $name, $form_data, $form ),
				                                'email'       => $email,
				                                'metadata'    => empty( $form_data[ 'metadata' ] ) ? '' : $form_data[ 'metadata' ]
			                                ),
			                                $form );

			$customer      = PPP_Stripe_API::create_customer(
				apply_filters( 'gfp_more_stripe_api_key', $api_key, 'create_new_customer' ),
				$customer_args
			);

			$customer      = apply_filters( 'gfp_more_stripe_customer_object', $customer, $create_new_customer, $feed, $form_data, $form );

			if ( ! is_object( $customer ) ) {

				$error_message = $customer;

				return GFP_Stripe::set_validation_result( $validation_result, $_POST, $error_message );

			}

			$card = $customer->default_source[ 'id' ];
		}

		if ( $create_new_card ) {

			//TODO create new card on both Big Daddy and vendor?

			$card = PPP_Stripe_API::create_card(
				apply_filters( 'gfp_more_stripe_api_key', $api_key, 'create_new_card' ),
				$customer,
				$card
			);

			if ( ! is_object( $card ) ) {

				$error_message = $card;

				return GFP_Stripe::set_validation_result( $validation_result, $_POST, $error_message );

			}

		}

		$cancel = apply_filters( 'gfp_more_stripe_cancel_charge', false, $feed, $form );

		if ( $cancel ) {

			GFP_Stripe::log_debug( "Charge creation canceled. Consider transaction successful with customer ID: {$customer['id']}" );

			$transaction_response = array(
				'transaction_id'      => $customer[ 'id' ],
				'amount'              => $form_data[ 'amount' ],
				'transaction_type'    => 3,
				'customer_name'       => $name,
				'new_stripe_customer' => $create_new_customer,
				'currency'            => $currency,
				'customer'            => $customer,
				'new_card'            => false,
				'card'                => $card,
				'feed'                => $feed,
				'mode'                => $form_data[ 'stripe_mode' ],
				'object'              => $customer
			);

			if ( $create_new_card ) {
				$transaction_response[ 'new_card' ] = true;
			}

			if ( ! empty( $user_id ) ) {
				$transaction_response[ 'user_id' ] = $user_id;
			}

			GFP_Stripe::set_transaction_response( $transaction_response );

			$validation_result[ 'is_valid' ] = true;

			return $validation_result;

		} else {

			GFP_Stripe::log_debug( 'Creating the charge, using the customer ID' );

			$currency_info            = self::get_currency_info( $currency );

			$amount                   = ( ( 0 == $currency_info[ 'decimals' ] ) ? round( floatval( $form_data[ 'amount' ] ), 0 ) : round( ( $form_data[ 'amount' ] * 100 ), 0 ) );

			$charge_args              = array(
				'amount'      => $amount,
				'currency'    => $currency,
				'customer'    => $customer[ 'id' ],
				'description' => apply_filters( 'gfp_stripe_customer_charge_description', implode( '\n', $form_data[ 'line_items' ] ), $form )
			);

			$charge_args[ 'source' ]    = ( $create_new_card ) ? $card[ 'id' ] : $card;
			$charge_args[ 'capture' ] = ( 'authorize_only' == rgar( $feed[ 'meta' ], 'alternate_charge_option' ) ) ? false : true;
			$charge_args              = apply_filters( 'gfp_more_stripe_create_charge_args', $charge_args, $form );

			if ( ! is_array( $charge_args ) ) {

				$error_message = $charge_args;

				if ( $create_new_customer ) {

					GFP_Stripe::log_error( 'Deleting new customer' );

					$customer->delete();

				} else if ( $create_new_card ) {

					GFP_Stripe::log_error( 'Deleting new card' );

					PPP_Stripe_API::delete_card( apply_filters( 'gfp_more_stripe_api_key', $api_key, 'delete_card' ), $card[ 'id' ], $customer );

				}

				return GFP_Stripe::set_validation_result( $validation_result, $_POST, $error_message );

			}

			$charge = PPP_Stripe_API::create_charge( apply_filters( 'gfp_more_stripe_api_key', $api_key, 'create_charge' ), $charge_args );

			if ( ! is_object( $charge ) ) {

				$error_message = $charge;

				if ( $create_new_customer ) {

					GFP_Stripe::log_error( 'Deleting new customer' );

					$customer->delete();

				} else if ( $create_new_card ) {

					GFP_Stripe::log_error( 'Deleting new card' );

					PPP_Stripe_API::delete_card( apply_filters( 'gfp_more_stripe_api_key', $api_key, 'delete_card' ), $card[ 'id' ], $customer );

				}

				return GFP_Stripe::set_validation_result( $validation_result, $_POST, $error_message );

			}

			$transaction_response = array(
				'transaction_id'      => $charge[ 'id' ],
				'amount'              => ( 0 == $currency_info[ 'decimals' ] ) ? $charge[ 'amount' ] : ( $charge[ 'amount' ] / 100 ),
				'card'                => $card,
				'transaction_type'    => ( $charge[ 'captured' ] ) ? 1 : 6,
				'customer_name'       => $name,
				'new_stripe_customer' => $create_new_customer,
				'currency'            => $currency,
				'customer'            => ! empty( $charge->customer ) ? $charge->customer : $customer,
				'new_card'            => false,
				'feed'                => $feed,
				'mode'                => $form_data[ 'stripe_mode' ],
				'object'              => $charge
			);

			if ( $create_new_card ) {

				$transaction_response[ 'new_card' ] = true;
				$transaction_response[ 'card' ]     = $card;

			}

			if ( ! empty( $user_id ) ) {

				$transaction_response[ 'user_id' ] = $user_id;

			}

			GFP_Stripe::set_transaction_response( $transaction_response );

			$validation_result[ 'is_valid' ] = true;

			return $validation_result;

		}

	}

	/**
	 * @param $feed
	 * @param $validation_result
	 *
	 * @return mixed
	 */
	private static function start_subscription( $feed, $validation_result ) {

		$form = $validation_result[ 'form' ];

		GFP_Stripe::log_debug( "Starting subscription for form: {$form['id']}" );

		$form_data = GFP_Stripe::get_form_data( $form, $feed );

		if ( isset( $form_data[ 'plan' ] ) || isset( $form_data[ 'subscriptions' ] ) ) {

			$api_key = GFP_Stripe::get_api_key( 'secret', $form_data[ 'stripe_mode' ] );

			$create_new_customer = true;
			$create_new_card     = false;

			$new_card            = ! empty( $form_data[ 'credit_card' ] ) && ! $form_data[ 'no_credit_card' ];

			$currency            = ( ! empty( $form_data[ 'currency' ] ) ) ? $form_data[ 'currency' ] : null;

			if ( ( is_user_logged_in() ) && ( ! is_wp_error( $customer = self::get_user_customer_object( apply_filters( 'gfp_more_stripe_api_key', $api_key, 'get_user_customer_object' ), get_current_user_id() ) ) ) ) {

				if ( is_object( $customer ) ) {
					$create_new_customer = false;
				}

			}

			if ( is_user_logged_in() ) {

				global $current_user;

				get_currentuserinfo();


				$user_id             = $current_user->ID;
				$form_data[ 'name' ] = rtrim( $form_data[ 'name' ] );
				$name                = empty( $form_data[ 'name' ] ) ? $current_user->user_firstname . ' ' . $current_user->user_lastname : $form_data[ 'name' ];
				$email               = empty( $form_data[ 'email' ] ) ? $current_user->user_email : $form_data[ 'email' ];

				if ( ( empty( $currency ) ) || ( 3 !== strlen( $currency ) ) ) {

					$currency = GFP_More_Stripe_Customer_API::get_stripe_customer_currency( $user_id );

					if ( ! $currency ) {
						$currency = GFCommon::get_currency();
					}
				}

				if ( ! $form_data[ 'no_credit_card' ] ) {

					$card = ( $new_card ) ? $form_data[ 'credit_card' ] : $form_data[ 'card' ];

					if ( $new_card && ( ! $create_new_customer ) ) {
						$create_new_card = true;
					}
				}

			} else {

				$email = $form_data[ 'email' ];
				$name  = $form_data[ 'name' ];

				if ( ( empty( $currency ) ) || ( 3 !== strlen( $currency ) ) ) {
					$currency = GFCommon::get_currency();
				}

				if ( ! $form_data[ 'no_credit_card' ] ) {
					$card = $form_data[ 'credit_card' ];
				}

			}

			$currency_info = self::get_currency_info( $currency );

			if ( $create_new_customer ) {

				if ( ! $form_data[ 'no_credit_card' ] ) {
					$customer_args[ 'source' ] = $card;
				}

				$customer_args[ 'description' ] = apply_filters( 'gfp_stripe_customer_description', $name, $form_data, $form );
				$customer_args[ 'email' ]       = $email;

				if ( ! empty( $form_data[ 'metadata' ] ) ) {
					$customer_args[ 'metadata' ] = $form_data[ 'metadata' ];
				}

				$customer_args = apply_filters( 'gfp_more_stripe_create_new_customer_args', $customer_args );

				$customer      = PPP_Stripe_API::create_customer( apply_filters( 'gfp_more_stripe_api_key', $api_key, 'create_new_customer' ), $customer_args );

				if ( ! is_object( $customer ) ) {

					$error_message = $customer;

					return GFP_Stripe::set_validation_result( $validation_result, $_POST, $error_message );
				}

				$customer = apply_filters( 'gfp_more_stripe_customer_object', $customer, $create_new_customer, $feed, $form_data, $form );

				if ( ! is_object( $customer ) ) {

					$error_message = $customer;

					return GFP_Stripe::set_validation_result( $validation_result, $_POST, $error_message );
				}

			}


			if ( ! is_numeric( rgar( $feed[ 'meta' ], 'subscription_plan_field' ) ) ) { //TODO move this inside dynamic plan check

				if ( isset( $form_data[ 'setup_fee_amount' ] ) ) {
					$form_data[ 'amount' ] -= ( 0 == $currency_info[ 'decimals' ] ) ? round( GFCommon::to_number( $form_data[ 'setup_fee_amount' ] ), 0 ) : GFCommon::to_number( $form_data[ 'setup_fee_amount' ] );
				}

				if ( isset( $form_data[ 'coupon' ] ) ) {
					$form_data[ 'amount' ] -= $form_data[ 'coupon' ][ 'price' ];
				}

			}

			if ( ( array_key_exists( 'subscriptions', $form_data ) ) && ( ! empty ( $form_data[ 'subscriptions' ] ) ) ) {

				//foreach ( $form_data['subscriptions'] as $subscription_product ) {
				$subscription_product = $form_data[ 'subscriptions' ][ 0 ];

				if ( is_array( $subscription_product ) ) {

					$plan_id = substr( str_replace( ' ', '-', $form_data[ 'form_title' ] ), 0, 250 ) . substr( "-{$name}-{$email}", 0, 250 );

					$plan    = PPP_Stripe_API::retrieve_plan( apply_filters( 'gfp_more_stripe_api_key', $api_key, 'retrieve_plan' ), $plan_id );

					$currency_info = self::get_currency_info( $currency );
					$plan_amount   = ( ( 0 == $currency_info[ 'decimals' ] ) ? round( floatval( $form_data[ 'amount' ] ), 0 ) : round( ( $form_data[ 'amount' ] * 100 ), 0 ) );

					if ( ! is_object( $plan ) ) {

						$plan_args = array(
							'id'                => $plan_id,
							'amount'            => $plan_amount,
							'currency'          => $currency,
							'interval'          => $subscription_product[ 'dynamic_plan' ][ 'interval' ],
							'interval_count'    => $subscription_product[ 'dynamic_plan' ][ 'interval_count' ],
							'name'              => $form_data[ 'form_title' ] . '-' . $name,
							'trial_period_days' => $subscription_product[ 'dynamic_plan' ][ 'trial_days' ]
						);
						$plan_args = apply_filters( 'gfp_more_stripe_create_plan_args', $plan_args, $form );

						$plan      = PPP_Stripe_API::create_plan( apply_filters( 'gfp_more_stripe_api_key', $api_key, 'create_plan' ),
						                                          $plan_args );

					} /* Plan exists */
					else {

						$plan = PPP_Stripe_API::delete_plan( apply_filters( 'gfp_more_stripe_api_key', $api_key, 'delete_plan' ), $plan );

						if ( is_object( $plan ) ) {

							$plan_args = array(
								'id'                => $plan[ 'id' ],
								'amount'            => $plan_amount,
								'currency'          => $currency,
								'interval'          => $subscription_product[ 'dynamic_plan' ][ 'interval' ],
								'interval_count'    => $subscription_product[ 'dynamic_plan' ][ 'interval_count' ],
								'name'              => $form_data[ 'form_title' ] . '-' . $name,
								'trial_period_days' => $subscription_product[ 'dynamic_plan' ][ 'trial_days' ]
							);
							$plan_args = apply_filters( 'gfp_more_stripe_create_plan_args', $plan_args, $form );

							$plan      = PPP_Stripe_API::create_plan( apply_filters( 'gfp_more_stripe_api_key', $api_key, 'create_plan' ),
							                                          $plan_args );
						}

					}

				} /* Predefined Plan */
				else {

					$plan = PPP_Stripe_API::retrieve_plan( apply_filters( 'gfp_more_stripe_api_key', $api_key, 'retrieve_plan' ), $subscription_product );

				}

				if ( ! is_object( $plan ) ) {

					GFP_Stripe::log_error( 'Unable to create or retrieve plan' );

					if ( $create_new_customer ) {

							GFP_Stripe::log_error( 'Deleting new customer' );

						$customer->delete();
					}

					$error_message = $plan;

					return GFP_Stripe::set_validation_result( $validation_result, $_POST, $error_message );

				}

				$plan     = $plan[ 'id' ];
				$quantity = is_array( $subscription_product ) ? $subscription_product[ 'quantity' ] : $form_data[ 'subscription_qty' ];

				//}

			} /** Old Subscription Method */
			else {

				$plan = PPP_Stripe_API::retrieve_plan( apply_filters( 'gfp_more_stripe_api_key', $api_key, 'retrieve_plan' ), $form_data[ 'plan' ] );

				if ( ! is_object( $plan ) ) {

					$error_message = $plan;

					return GFP_Stripe::set_validation_result( $validation_result, $_POST, $error_message );
				}

				$quantity = $form_data[ 'subscription_qty' ];
				$currency = strtoupper( $plan[ 'currency' ] );
				$plan     = $plan[ 'id' ];

			}

			if ( isset( $form_data[ 'setup_fee_amount' ] ) && ! $form_data[ 'no_credit_card' ] ) {

				$setup_fee_amount = ( ( 0 == $currency_info[ 'decimals' ] ) ? round( GFCommon::to_number( $form_data[ 'setup_fee_amount' ] ), 0 ) : round( ( GFCommon::to_number( $form_data[ 'setup_fee_amount' ] ) * 100 ), 0 ) );

				$setup_fee = self::create_setup_fee( apply_filters( 'gfp_more_stripe_api_key', $api_key, 'create_setup_fee' ),
				                                     $customer[ 'id' ],
				                                     array(
					                                     'amount'      => $setup_fee_amount,
					                                     'currency'    => $currency,
					                                     'description' => apply_filters( 'gfp_more_stripe_setup_fee_description', 'One-time charge: ' . $form_data[ 'setup_fee_name' ], $form_data )
				                                     )
				);

				if ( ! is_object( $setup_fee ) ) {

					GFP_Stripe::log_error( 'Unable to create setup fee' );

					if ( $create_new_customer ) {

						GFP_Stripe::log_error( 'Deleting new customer' );

						$customer->delete();
					}

					$error_message = $setup_fee;

					return GFP_Stripe::set_validation_result( $validation_result, $_POST, $error_message );
				}
			}

			$subscription_args = array(
				'plan'     => $plan,
				'quantity' => $quantity,
				'metadata' => array(
					'gravity_form'             => $form[ 'id' ],
					'gravity_form_stripe_rule' => $feed[ 'id' ]
				)
			);

			if ( ( isset( $form_data[ 'coupon' ] ) ) && 'now' == rgars( $form_data, 'coupon/apply' ) ) {
				$subscription_args[ 'coupon' ] = $form_data[ 'coupon' ][ 'id' ];
			}

			$end_subscription = false;

			if ( is_array( $subscription_product ) && array_key_exists( 'end_after', $subscription_product ) ) {

				$subscription_args[ 'max_occurrences' ] = $subscription_product[ 'end_after' ];
				$end_subscription                       = true;

			} else if ( isset( $form_data[ 'subscription_end_after' ] ) ) {

				$subscription_args[ 'max_occurrences' ] = $form_data[ 'subscription_end_after' ];
				$end_subscription                       = true;

			}

			$subscription_args = apply_filters( 'gfp_more_stripe_create_subscription_args', $subscription_args, $form );

			if ( $create_new_card ) {

				$card = PPP_Stripe_API::create_card( apply_filters( 'gfp_more_stripe_api_key', $api_key, 'create_new_card' ), $customer, $card );

				if ( ! is_object( $card ) ) {

					$error_message = $card;

					return GFP_Stripe::set_validation_result( $validation_result, $_POST, $error_message );
				}

				//TODO is it card, source, or default_source to set new default card
				$updated_customer = PPP_Stripe_API::update_customer( apply_filters( 'gfp_more_stripe_api_key', $api_key, 'update_customer' ), $customer, array( 'default_source' => $card[ 'id' ] ) );

				if ( ! is_object( $updated_customer ) ) {

					$error_message = $updated_customer;
					PPP_Stripe_API::delete_card( apply_filters( 'gfp_more_stripe_api_key', $api_key, 'delete_card' ), $card[ 'id' ], $customer );

					return GFP_Stripe::set_validation_result( $validation_result, $_POST, $error_message );

				}
				else {
					$customer = $updated_customer;
				}

			} else if ( ! $new_card && ! $create_new_customer && ! $form_data[ 'no_credit_card' ] ) {

				if ( ! GFP_More_Stripe_Customer_API::is_default_card( $user_id, $card ) ) {

					//TODO is it card, source, or default_source to set new default card
					$customer = PPP_Stripe_API::update_customer( apply_filters( 'gfp_more_stripe_api_key', $api_key, 'update_customer' ), $customer, array( 'default_source' => $card ) );

					if ( ! is_object( $customer ) ) {

						$error_message = $customer;

						return GFP_Stripe::set_validation_result( $validation_result, $_POST, $error_message );
					}

				}

			}

			$stripe_subscription = self::subscribe_customer_to_plan( apply_filters( 'gfp_more_stripe_api_key', $api_key, 'subscribe_to_plan' ), $customer, $create_new_customer, $subscription_args );

			if ( ! is_object( $stripe_subscription ) ) {

				GFP_Stripe::log_error( 'Subscription failed' );

				if ( isset( $setup_fee ) ) {

					GFP_Stripe::log_error( 'Deleting setup fee' );

					$setup_fee->delete();
				}

				if ( $create_new_customer ) {

					GFP_Stripe::log_error( 'Deleting new customer' );

					$customer->delete();
				}

				$error_message = $stripe_subscription;

				return GFP_Stripe::set_validation_result( $validation_result, $_POST, $error_message );
			}

			if ( isset( $form_data[ 'coupon' ] ) && 'after' == rgars( $form_data, 'coupon/apply' ) ) {
				self::apply_coupon_to_subscription( $api_key, $stripe_subscription->customer, $stripe_subscription[ 'id' ], $form_data[ 'coupon' ][ 'id' ] );
			}

			$subscription = array(
				'id'            => $stripe_subscription[ 'id' ],
				'customer'      => $stripe_subscription[ 'customer' ],
				'customer_name' => $name,
				'status'        => $stripe_subscription[ 'status' ],
				'start'         => $stripe_subscription[ 'current_period_start' ],
				'end'           => $stripe_subscription[ 'current_period_end' ],
				'next_payment'  => '',
				'plan'          => array(
					'id'             => $stripe_subscription->plan[ 'id' ],
					'amount'         => ( 0 == $currency_info[ 'decimals' ] ) ? $stripe_subscription->plan[ 'amount' ] : $stripe_subscription->plan[ 'amount' ] / 100,
					'interval'       => $stripe_subscription->plan[ 'interval' ],
					'interval_count' => $stripe_subscription->plan[ 'interval_count' ],
					'name'           => $stripe_subscription->plan[ 'name' ]
				),
				'trial_end'     => $stripe_subscription[ 'trial_end' ],
				'trial_start'   => $stripe_subscription[ 'trial_start' ],
				'end_after'     => ( $end_subscription ) ? $stripe_subscription[ 'max_occurrences' ] : 0,
				'quantity'      => $stripe_subscription[ 'quantity' ]
			);

			if ( ! empty( $setup_fee ) ) {
				$subscription[ 'setup_fee' ] = array(
					'id'     => $setup_fee[ 'id' ],
					'amount' => ( isset( $setup_fee[ 'amount' ] ) ? ( ( 0 == $currency_info[ 'decimals' ] ) ? $setup_fee[ 'amount' ] : ( $setup_fee[ 'amount' ] / 100 ) ) : 0 )
				);
			}

			GFP_Stripe::log_debug( "Subscription created successfully. ID: {$subscription['customer']['id']} - Amount: {$subscription['plan']['amount']}" );

			$last_invoice = PPP_Stripe_API::list_invoices( apply_filters( 'gfp_more_stripe_api_key', $api_key, 'list_invoices' ), array(
				'customer' => $subscription[ 'customer' ][ 'id' ],
				'limit'    => 1
			) );

			if ( ! is_object( $last_invoice ) ) {
				$last_invoice = '';
			}

			$upcoming_invoice = PPP_Stripe_API::retrieve_upcoming_invoice( apply_filters( 'gfp_more_stripe_api_key', $api_key, 'retrieve_upcoming_invoice' ), $subscription[ 'customer' ][ 'id' ] );

			if ( ! is_object( $upcoming_invoice ) ) {
				$upcoming_invoice = '';
			}

			$subscription[ 'next_payment' ] = array(
				'amount' => empty( $upcoming_invoice ) ? '' : ( ( 0 == $currency_info[ 'decimals' ] ) ? $upcoming_invoice[ 'total' ] : ( $upcoming_invoice[ 'total' ] / 100 ) ),
				'date'   => empty( $upcoming_invoice ) ? '' : $upcoming_invoice[ 'date' ],
			);

			$transaction_response = array(
				'transaction_id'      => empty( $last_invoice ) ? $subscription[ 'customer' ][ 'id' ] : $last_invoice[ 'data' ][ 0 ][ 'id' ],
				'amount'              => empty( $last_invoice ) ? ( ( 0 == $currency_info[ 'decimals' ] ) ? round( floatval( $form_data[ 'amount' ] ), 0 ) : $form_data[ 'amount' ] ) : ( ( 0 == $currency_info[ 'decimals' ] ) ? $last_invoice[ 'data' ][ 0 ][ 'total' ] : ( $last_invoice[ 'data' ][ 0 ][ 'total' ] / 100 ) ),
				'transaction_type'    => 2,
				'subscription'        => $subscription,
				'new_stripe_customer' => $create_new_customer,
				'new_card'            => $create_new_card,
				'no_credit_card'      => $form_data[ 'no_credit_card' ],
				'currency'            => strtoupper( $upcoming_invoice[ 'currency' ] ),
				'feed'                => $feed,
				'mode'                => $form_data[ 'stripe_mode' ],
				'object'              => $stripe_subscription,
				'invoice'             => empty( $last_invoice ) ? $last_invoice : $last_invoice[ 'data' ][ 0 ]
			);

			if ( isset( $card ) ) {
				$transaction_response[ 'card' ] = $card;
			}

			if ( $create_new_card ) {
				$transaction_response[ 'new_card' ] = true;
				$transaction_response[ 'card' ]     = $card;
			}

			if ( ! empty( $user_id ) ) {
				$transaction_response[ 'user_id' ] = $user_id;
			}

			GFP_Stripe::set_transaction_response( $transaction_response );

			$validation_result[ 'is_valid' ] = true;

			return $validation_result;


		} else {

			GFP_Stripe::log_error( 'Unable to send create subscription request.' );
			$error_message = 'This form cannot process subscription payments. Please contact site owner';

			return GFP_Stripe::set_validation_result( $validation_result, $_POST, $error_message );

		}

	}

	/**
	 * @param $feed
	 * @param $validation_result
	 *
	 * @return mixed
	 */
	private static function update_billing_info( $feed, $validation_result ) {

		if ( ! is_user_logged_in() ) {
			return GFP_Stripe::set_validation_result( $validation_result, $_POST, __( 'You must be logged in to update your billing info.', 'gravityforms-stripe-more' ) );
		}

		global $current_user;

		get_currentuserinfo();

		$form      = $validation_result[ 'form' ];
		$form_data = GFP_Stripe::get_form_data( $form, $feed );
		$api_key   = GFP_Stripe::get_api_key( 'secret', $form_data[ 'stripe_mode' ] );

		GFP_Stripe::log_debug( "Starting billing info update for form: {$form['id']} user:{$current_user->user_login}" );

		$customer = self::get_user_customer_object( $api_key, $current_user->ID );

		if ( is_wp_error( $customer ) ) {

			$error_message = $customer->get_error_message( $customer->get_error_code() );

			return GFP_Stripe::set_validation_result( $validation_result, $_POST, $error_message );

		} else if ( ! is_object( $customer ) ) {

				$error_message = $customer;

			return GFP_Stripe::set_validation_result( $validation_result, $_POST, $error_message );

		}

		$card = PPP_Stripe_API::create_card( $api_key, $customer, $form_data[ 'credit_card' ] );

		if ( ! is_object( $card ) ) {

			$error_message = $card;

			return GFP_Stripe::set_validation_result( $validation_result, $_POST, $error_message );

		}

		//TODO is it source, card, or default_source?
		$updated_customer = PPP_Stripe_API::update_customer( $api_key, $customer, array( 'default_source' => $card[ 'id' ] ) );

		if ( ! is_object( $updated_customer ) ) {

			$error_message = $updated_customer;

			PPP_Stripe_API::delete_card( $api_key, $card[ 'id' ], $customer );

			return GFP_Stripe::set_validation_result( $validation_result, $_POST, $error_message );

		}

		GFP_More_Stripe_Customer_API::save_new_card( $current_user->ID, $card );

		$customer_link = PPP_Stripe_API::create_stripe_dashboard_link( $updated_customer[ 'id' ], 'customer', PPP_Stripe_API::get_mode() );
		$customer_link = "<a href=\"{$customer_link}\" alt=\"View this customer on Stripe dashboard\" title=\"View on Stripe dashboard\" target=\"_blank\">{$updated_customer['id']}</a>";

		self::add_note( $updated_customer->metadata[ 'gravity_form_entry' ],
		                sprintf( __( 'Customer %1$s <strong>updated</strong> their billing info to <em>%2$s ending in %3$s</em>', 'gravityforms-stripe-more' ), $customer_link, $card[ 'brand' ], $card[ 'last4' ] ),
		                'success'
		);


		GFP_Stripe::set_transaction_response( array(
			                                      'transaction_id'   => $card[ 'id' ],
			                                      'transaction_type' => 4,
			                                      'user_id'          => $current_user->ID,
			                                      'card'             => $card[ 'id' ],
			                                      'mode'             => $form_data[ 'stripe_mode' ],
			                                      'object'           => $card
		                                      ) );


		$validation_result[ 'is_valid' ] = true;

		return $validation_result;
	}

	/**
	 * @param $feed
	 * @param $validation_result
	 *
	 * @return mixed
	 */
	private static function update_subscription( $feed, $validation_result ) {

		if ( ! is_user_logged_in() ) {
			return GFP_Stripe::set_validation_result( $validation_result, $_POST, __( 'You must be logged in to update your subscription.', 'gravityforms-stripe-more' ) );
		}

		global $current_user;

		get_currentuserinfo();

		$form      = $validation_result[ 'form' ];
		$form_data = GFP_Stripe::get_form_data( $form, $feed );
		$api_key   = GFP_Stripe::get_api_key( 'secret', $form_data[ 'stripe_mode' ] );

		GFP_Stripe::log_debug( "Starting subscription update for form {$form['id']}, user {$current_user->user_login}" );

		$customer = self::get_user_customer_object( $api_key, $current_user->ID );

		if ( is_wp_error( $customer ) ) {

			$error_message = $customer->get_error_message( $customer->get_error_code() );

			return GFP_Stripe::set_validation_result( $validation_result, $_POST, $error_message );

		} else if ( ! is_object( $customer ) ) {

			$error_message = $customer;

			return GFP_Stripe::set_validation_result( $validation_result, $_POST, $error_message );

		}

		if ( isset( $form_data[ 'plan' ] ) ) {

			$active_subscriptions = GFP_More_Stripe_Customer_API::get_active_subscriptions( $current_user->ID );

			if ( empty( $active_subscriptions ) ) {

				GFP_Stripe::log_error( "User {$current_user->ID} does not contain any active subscriptions to update -- perhaps this user was subscribed manually through the Stripe dashboard instead of through this website" );

				return GFP_Stripe::set_validation_result( $validation_result, $_POST, __( 'No active subscriptions to update. Please contact site owner', 'gravityforms-stripe-more' ) );
			}

			$active_subscription = $active_subscriptions[ 0 ];

			$entry_id = self::get_subscription_entry_id( $current_user->ID, $active_subscription );

			if ( 'cancel' == $form_data[ 'plan' ] ) {

				GFP_Stripe::log_debug( "Sending cancel subscription request" );

				$canceled_subscription = self::cancel_subscription( $api_key,
				                                                    $customer,
					( ! empty( $feed[ 'meta' ][ 'cancel_at_period_end' ] ) ? true : false ),
				                                                    $entry_id,
				                                                    true, $current_user->ID
				);

				if ( ! is_object( $canceled_subscription ) ) {
					$error_message = $canceled_subscription;

					return GFP_Stripe::set_validation_result( $validation_result, $_POST, $error_message );
				}

				GFP_Stripe::set_transaction_response( array(
					                                      'transaction_id'   => $canceled_subscription[ 'customer' ],
					                                      'transaction_type' => 5,
					                                      'subscription'     => $canceled_subscription,
					                                      'user_id'          => $current_user->ID,
					                                      'mode'             => $form_data[ 'stripe_mode' ],
					                                      'object'           => $canceled_subscription
				                                      ) );

			} else {

				$new_subscription = PPP_Stripe_API::update_subscription( $api_key, $customer, $active_subscription, array(
					'plan'     => $form_data[ 'plan' ],
					'quantity' => $form_data[ 'subscription_qty' ],
					'prorate'  => ( empty( $feed[ 'meta' ][ 'disable_prorate' ] ) ? true : false )
				) );

				if ( ! is_object( $new_subscription ) ) {

					$error_message = $new_subscription;

					return GFP_Stripe::set_validation_result( $validation_result, $_POST, $error_message );
				}

				if ( ( ! empty( $feed[ 'meta' ][ 'charge_upgrade_immediately' ] ) ) && ( ! empty( $active_subscription ) ) ) {

					GFP_Stripe::log_debug( "Sending request to charge upgrade immediately" );

					$invoice = self::charge_upgrade_immediately( $api_key,
					                                             $customer[ 'id' ],
					                                             $new_subscription[ 'id' ],
					                                             $form,
					                                             $entry_id
					);
				}

				$currency_info = self::get_currency_info( strtoupper( $customer[ 'currency' ] ) );

				GFP_Stripe::log_debug( "Saving new subscription details to database" );

				$new_plan = array(
					'id'             => $new_subscription->plan[ 'id' ],
					'amount'         => ( 0 == $currency_info[ 'decimals' ] ) ? $new_subscription->plan[ 'amount' ] : ( $new_subscription->plan[ 'amount' ] / 100 ),
					'interval'       => $new_subscription->plan[ 'interval' ],
					'interval_count' => $new_subscription->plan[ 'interval_count' ],
					'name'           => $new_subscription->plan[ 'name' ],
				);

				$upcoming_invoice = PPP_Stripe_API::retrieve_upcoming_invoice( $api_key, $new_subscription[ 'customer' ] );

				if ( ! is_object( $upcoming_invoice ) ) {
					$upcoming_invoice = '';
				}

				$next_payment = array(
					'amount' => empty( $upcoming_invoice ) ? '' : ( 0 == $currency_info[ 'decimals' ] ) ? $upcoming_invoice[ 'total' ] : ( $upcoming_invoice[ 'total' ] / 100 ),
					'date'   => empty( $upcoming_invoice ) ? '' : $upcoming_invoice[ 'date' ],
				);

				$subscription_to_save = array(
					'id'           => $new_subscription[ 'id' ],
					'status'       => $new_subscription[ 'status' ],
					'start'        => $new_subscription[ 'current_period_start' ],
					'end'          => $new_subscription[ 'current_period_end' ],
					'next_payment' => $next_payment,
					'plan'         => $new_plan,
					'trial_start'  => $new_subscription[ 'trial_start' ],
					'trial_end'    => $new_subscription[ 'trial_end' ],
					'quantity'     => $new_subscription[ 'quantity' ]
				);

				self::update_entry_meta_subscription( $entry_id, $subscription_to_save );
				self::update_saved_subscription( $current_user->ID, $subscription_to_save, $entry_id );

				$amount   = ( ! empty( $invoice ) ) ? ( ( 0 == $currency_info[ 'decimals' ] ) ? $invoice[ 'total' ] : ( $invoice[ 'total' ] / 100 ) ) : ( ( 0 == $currency_info[ 'decimals' ] ) ? $upcoming_invoice[ 'total' ] : ( $upcoming_invoice[ 'total' ] / 100 ) );
				$quantity = $new_subscription[ 'quantity' ];

				self::add_note( $entry_id, sprintf( __( 'Customer %1$s <strong>updated</strong> subscription to %2$s plan (%3$01.2f/%4$s), quantity %5$d', 'gravityforms-stripe-more' ), $customer[ 'id' ], $new_plan[ 'name' ], $new_plan[ 'amount' ], $new_plan[ 'interval_count' ] . ' ' . $new_plan[ 'interval' ], $quantity ), 'success' );


				GFP_Stripe::set_transaction_response( array(
					                                      'transaction_id'   => ( ! empty( $invoice ) ) ? $invoice[ 'id' ] : $new_subscription[ 'id' ],
					                                      'amount'           => $amount,
					                                      'transaction_type' => 5,
					                                      'user_id'          => $current_user->ID,
					                                      'mode'             => $form_data[ 'stripe_mode' ],
					                                      'subscription'     => $new_subscription,
					                                      'currency'         => strtoupper( $customer[ 'currency' ] ),
					                                      'object'           => $new_subscription,
					                                      'invoice'          => empty( $invoice ) ? '' : $invoice
				                                      ) );

			}

			$validation_result[ 'is_valid' ] = true;

			return $validation_result;


		} else {

			GFP_Stripe::log_error( 'Unable to send update subscription request.' );
			$error_message = __( 'This form cannot process your subscription update. Please contact site owner', 'gravityforms-stripe-more' );

			return GFP_Stripe::set_validation_result( $validation_result, $_POST, $error_message );
		}

	}

//------------------------------------

	/**
	 *
	 *
	 * @since    1.8.2
	 *
	 * @param      $api_key
	 * @param      $customer
	 * @param      $is_new_customer
	 * @param      $args
	 *
	 * @internal param $plan
	 * @internal param null $coupon
	 * @internal param bool $prorate
	 * @internal param null $trial_end
	 * @internal param null $card
	 * @internal param int $quantity
	 * @internal param null $application_fee_percent
	 *
	 * @return mixed|void
	 */
	private static function subscribe_customer_to_plan( $api_key, $customer, $is_new_customer, $args ) {

		GFP_Stripe::log_debug( "Subscribing customer to a plan" );

		if ( $is_new_customer ) {

			$subscription = PPP_Stripe_API::create_subscription( $api_key, $customer, $args );

			if ( is_object( $subscription ) ) {

				GFP_Stripe::log_debug( "Subscription {$subscription['id']} successfully created for {$customer['id']}" );

			}

		} else {

			$user_id              = get_current_user_id();
			$active_subscriptions = GFP_More_Stripe_Customer_API::get_active_subscriptions( $user_id );

			if ( empty( $active_subscriptions ) ) {

				$subscription = PPP_Stripe_API::create_subscription( $api_key, $customer, $args );

				if ( is_object( $subscription ) ) {
					GFP_Stripe::log_debug( "Subscription {$subscription['id']} successfully created for {$customer['id']}" );
				}

			} else {

				$active_subscription = $active_subscriptions[ 0 ];
				$subscription        = PPP_Stripe_API::update_subscription( $api_key, $customer, $active_subscription, $args );

				if ( is_object( $subscription ) ) {
					GFP_Stripe::log_debug( "Subscription {$subscription['id']} successfully updated for {$customer['id']}" );
				}

			}

		}

		return $subscription;
	}

	/**
	 * @param $api_key
	 * @param $customer
	 * @param $args
	 *
	 * @return array|mixed|string|Stripe_InvoiceItem|Stripe_Object|void
	 */
	private static function create_setup_fee( $api_key, $customer, $args ) {

		GFP_Stripe::log_debug( "Creating the setup fee invoice item" );

		$setup_fee = PPP_Stripe_API::create_invoice_item( $api_key, $customer, $args );

		if ( is_object( $setup_fee ) ) {
			GFP_Stripe::log_debug( "Setup fee successfully created for {$setup_fee['customer']}. Invoice ID: {$setup_fee['invoice']} - Amount: {$setup_fee['amount']}" );
		}

		return $setup_fee;
	}

	/**
	 * @param      $api_key
	 * @param      $customer
	 * @param      $at_period_end
	 * @param      $entry_id
	 * @param bool $do_canceled_subscription_actions
	 * @param      $user_id
	 *
	 * @return mixed|void|WP_Error
	 */
	private static function cancel_subscription( $api_key, $customer, $at_period_end, $entry_id, $do_canceled_subscription_actions = true, $user_id ) {

		GFP_Stripe::log_debug( 'Canceling subscription...' );

		if ( ! empty( $user_id ) ) {

			$active_subscriptions = GFP_More_Stripe_Customer_API::get_active_subscriptions( $user_id );

			if ( empty( $active_subscriptions ) ) {

				GFP_Stripe::log_error( "User {$user_id} does not contain any active subscriptions -- perhaps this user was subscribed manually through the Stripe dashboard instead of through this website" );

				return new WP_Error( 'no_active_subscription_for_user', __( 'This form cannot process your user info. Please contact site owner', 'gravityforms-stripe-more' ) );

			}

			$subscription = $active_subscriptions[ 0 ];

		} else {

			$subscription_meta = gform_get_meta( $entry_id, 'stripe_subscription' );
			$subscription      = $subscription_meta[ 0 ][ 'id' ];

		}

		$canceled_subscription = PPP_Stripe_API::cancel_subscription( $api_key, $customer, $subscription, $at_period_end );

		if ( ! is_object( $canceled_subscription ) ) {

			GFP_Stripe::log_error( 'Subscription cancelation failed' );

		} else {

			if ( ! empty( $entry_id ) ) {

				$entry = RGFormsModel::get_lead( $entry_id );

			} else {

				$message = __( 'Subscription canceled, but no entry ID was given so unable to do canceled subscription actions or update entry' );

				return new WP_Error( 'no-entry-id', $message );

			}

			$customer_id = $customer[ 'id' ];

			if ( $do_canceled_subscription_actions && ( false == $canceled_subscription[ 'cancel_at_period_end' ] ) ) {

				self::do_canceled_subscription_actions( $entry, $user_id, $customer_id, $canceled_subscription );

			} else {

				$entry[ 'payment_status' ] = 'Active/Canceled';

				GFAPI::update_entry( $entry );

				if ( ! empty( $user_id ) ) {
					GFP_More_Stripe_Customer_API::remove_active_subscription( $user_id, $canceled_subscription[ 'id' ] );
				}
				self::update_entry_meta_subscription( $entry_id, array( 'status' => 'Active/Canceled' ) );

				$current_period_end = date_i18n( 'm/d/Y', $canceled_subscription[ 'current_period_end' ], true );

				self::add_note( $entry_id, sprintf( __( 'Subscription for %1$s set to <strong>cancel</strong> at the end of the period on %2$s', 'gravityforms-stripe-more' ), $customer_id, $current_period_end ) );
			}
		}

		return $canceled_subscription;

	}

	/**
	 * @param      $api_key
	 * @param      $customer_id
	 * @param      $subscription_id
	 * @param      $form
	 * @param      $lead_id
	 * @param null $application_fee
	 *
	 * @return array|bool|mixed|Stripe_Invoice|Stripe_Object|void
	 */
	private static function charge_upgrade_immediately( $api_key, $customer_id, $subscription_id, $form, $lead_id, $application_fee = null ) {

		$invoice = PPP_Stripe_API::create_invoice( $api_key, $customer_id, array( 'subscription' => $subscription_id ) );

		if ( ! is_object( $invoice ) ) {

			GFP_Stripe::log_error( 'Invoice creation failed. Sending notification to admin.' );

			global $current_user;

			get_currentuserinfo();

			$notification[ 'subject' ] = sprintf( __( "Unable to Charge Immediately for Subscription Update: %s", 'gravityforms-stripe-more' ), $current_user->user_firstname . ' ' . $current_user->user_lastname );
			$notification[ 'message' ] = sprintf( __( "Customer ID: %s\r\n", 'gravityforms-stripe-more' ), $customer_id );
			$notification[ 'message' ] .= sprintf( __( "This customer updated their subscription but the attempt to charge them immediately failed.\r\n", 'gravityforms-stripe-more' ) );
			$notification[ 'to' ] = $notification[ 'from' ] = get_option( 'admin_email' );

			self::notify_internal_error( null, $notification, $form, RGFormsModel::get_lead( $lead_id ) );

			return false;

		} else {

			$invoice = PPP_Stripe_API::pay_invoice( $api_key, $invoice );

			if ( ! is_object( $invoice ) ) {

				GFP_Stripe::log_error( 'Invoice payment failed. Sending notification to admin.' );

				$notification[ 'subject' ] = __( "Unable to Charge Immediately for Subscription Update", 'gravityforms-stripe-more' );
				$notification[ 'message' ] = sprintf( __( "Customer ID: %s\r\n", 'gravityforms-stripe-more' ), $customer_id );
				$notification[ 'message' ] .= sprintf( __( "This customer updated their subscription but the attempt to charge them immediately failed.\r\n", 'gravityforms-stripe-more' ) );
				$notification[ 'to' ] = $notification[ 'from' ] = get_option( 'admin_email' );

				self::notify_internal_error( null, $notification, $form, RGFormsModel::get_lead( $lead_id ) );

				return false;

			} else {

				GFP_Stripe::log_debug( "Invoice created and payment successful!" );

			}

		}

		return $invoice;
	}

	private static function apply_coupon_to_subscription( $api_key, $customer, $subscription_id, $coupon_id ) {

		$subscription = PPP_Stripe_API::update_subscription( $api_key, $customer, $subscription_id, array( 'coupon' => $coupon_id ) );

		if ( ! is_a( $subscription, 'PPP\\Stripe\\Subscription' ) ) {

			GFP_Stripe::log_error( 'Unable to apply coupon to subscription after subscription creation.' );

		} else {

			GFP_Stripe::log_debug( "Coupon applied to subscription" );

		}

		return $subscription;
	}

	//------------------------------------
	/**
	 *
	 * @since 1.8.2
	 *
	 * @param $user_id
	 *
	 * @return array|bool|mixed|WP_Error
	 */
	public static function user_has_subscription( $user_id ) {

		$has_subscription = false;

		$subscription = GFP_More_Stripe_Customer_API::get_active_subscriptions( $user_id );

		if ( ! empty( $subscription ) ) {
			$has_subscription = $subscription;
		}

		return $has_subscription;
	}

	/**
	 * @param $api_key
	 * @param $user_id
	 *
	 * @return mixed|void|WP_Error
	 */
	public static function get_user_customer_object( $api_key, $user_id ) {

		$customer_id = GFP_More_Stripe_Customer_API::get_stripe_customer_id( $user_id );

		if ( empty( $customer_id ) ) {

			GFP_Stripe::log_error( "User {$user_id} does not contain a saved Stripe customer -- perhaps this user was subscribed manually through the Stripe dashboard instead of through this website" );

			return new WP_Error( 'no_customer_id_for_user', __( 'This form cannot process your user info. Please contact site owner', 'gravityforms-stripe-more' ) );
		}

		GFP_Stripe::log_debug( "Retrieving Stripe customer object for user: {$user_id}" );

		$customer = PPP_Stripe_API::retrieve( 'customer', $api_key, $customer_id );

		return $customer;
	}

	/**
	 * @param $user_id
	 * @param $subscription_id
	 *
	 * @return WP_Error
	 */
	private static function get_subscription_entry_id( $user_id, $subscription_id ) {

		GFP_Stripe::log_debug( "Retrieving entry ID for user {$user_id}'s subscription {$subscription_id}" );

		$subscription = GFP_More_Stripe_Customer_API::get_subscription_info( $user_id, $subscription_id );

		if ( empty( $subscription ) ) {

			GFP_Stripe::log_error( "User {$user_id} does not have a saved subscription with this subscription ID -- perhaps this user was subscribed manually through the Stripe dashboard instead of through this website" );

			return new WP_Error( 'subscription_not_saved_for_user', __( 'This form cannot process your user info. Please contact site owner', 'gravityforms-stripe-more' ) );
		}

		$entry_id = $subscription[ 'entry_id' ];

		if ( empty( $entry_id ) ) {

			GFP_Stripe::log_error( "This subscription does not contain the ID of the entry used to create their subscription -- perhaps this user was subscribed manually through the Stripe dashboard instead of through this website" );

			return new WP_Error( 'no_entry_id_for_subscription', __( 'This form cannot process your user info. Please contact site owner', 'gravityforms-stripe-more' ) );
		}

		return $entry_id;
	}

	public static function update_entry_meta_subscription( $entry_id, $new_subscription_info ) {

		GFP_Stripe::log_debug( 'Updating entry meta subscription info' );

		$current_subscription_info = gform_get_meta( $entry_id, 'stripe_subscription' );
		$subscription_info         = array( array_replace( $current_subscription_info[0], $new_subscription_info ) ); //TODO change for multiple subscriptions

		if ( null !== $subscription_info ) {
			gform_update_meta( $entry_id, 'stripe_subscription', $subscription_info );
		}

	}

	/**
	 * @param $user_id
	 * @param $subscription
	 * @param $entry_id
	 */
	private static function update_saved_subscription( $user_id, $subscription, $entry_id ) {

		if ( ( 'canceled' == $subscription[ 'status' ] ) ) {

			/*foreach ( $active_subscriptions as $key => $subscription_id ) {
				if ( $subscription_id == $subscription['id'] ) {
					unset( $active_subscriptions[$key] );
				}
			}
			$active_subscriptions = array_values( $active_subscriptions );*/
			$currency_info         = self::get_currency_info( strtoupper( $subscription->plan[ 'currency' ] ) );
			$canceled_subscription = array(
				'id'          => $subscription[ 'id' ],
				'entry_id'    => $entry_id,
				'status'      => $subscription[ 'status' ],
				'start'       => $subscription[ 'current_period_start' ],
				'end'         => $subscription[ 'current_period_end' ],
				'plan'        => array(
					'id'             => $subscription->plan[ 'id' ],
					'amount'         => ( 0 == $currency_info[ 'decimals' ] ) ? $subscription->plan[ 'amount' ] : $subscription->plan[ 'amount' ] / 100,
					'interval'       => $subscription->plan[ 'interval' ],
					'interval_count' => $subscription->plan[ 'interval_count' ],
					'name'           => $subscription->plan[ 'name' ],
				),
				'trial_start' => $subscription[ 'trial_start' ],
				'trial_end'   => $subscription[ 'trial_end' ],
				'quantity'    => $subscription[ 'quantity' ]
			);
			update_user_meta( $user_id, '_gfp_stripe_subscription_' . $subscription[ 'id' ], $canceled_subscription );

		} else if ( ( 'trialing' == $subscription[ 'status' ] ) || ( 'active' == $subscription[ 'status' ] ) ) {

			/*if ( empty( $active_subscriptions ) ) {
				GFP_Stripe::log_error( "User {$user_id} does not contain any active subscriptions -- perhaps this user was subscribed manually through the Stripe dashboard instead of through this website" );
			}*/
			$current_subscription                   = GFP_More_Stripe_Customer_API::get_subscription_info( $user_id, $subscription[ 'id' ] );
			$current_subscription[ 'id' ]           = $subscription[ 'id' ];
			$current_subscription[ 'status' ]       = $subscription[ 'status' ];
			$current_subscription[ 'start' ]        = $subscription[ 'start' ];
			$current_subscription[ 'end' ]          = $subscription[ 'end' ];
			$current_subscription[ 'next_payment' ] = $subscription[ 'next_payment' ];
			$current_subscription[ 'plan' ]         = $subscription[ 'plan' ];
			$current_subscription[ 'trial_start' ]  = $subscription[ 'trial_start' ];
			$current_subscription[ 'trial_end' ]    = $subscription[ 'trial_end' ];
			$current_subscription[ 'quantity' ]     = $subscription[ 'quantity' ];
			update_user_meta( $user_id, '_gfp_stripe_subscription_' . $subscription[ 'id' ], $current_subscription );

			GFP_More_Stripe_Customer_API::add_active_subscription( $user_id, $subscription[ 'id' ] );

		}

	}

	public static function update_saved_subscription_attribute( $user_id, $subscription_id, $attribute, $value ) {

		GFP_Stripe::log_debug( 'Updating saved subscription attribute' );

		$current_subscription               = GFP_More_Stripe_Customer_API::get_subscription_info( $user_id, $subscription_id );
		$current_subscription[ $attribute ] = $value;

		update_user_meta( $user_id, '_gfp_stripe_subscription_' . $subscription_id, $current_subscription );
	}

	/**
	 * @param      $lead
	 * @param bool $user_id
	 * @param      $customer_id
	 * @param      $canceled_subscription
	 */
	public static function do_canceled_subscription_actions( $lead, $user_id = false, $customer_id, $canceled_subscription ) {

		GFP_Stripe::log_debug( 'Doing canceled subscription actions...' );
		GFP_Stripe::log_debug( '1. Update entry payment status' );

		self::update_payment_info( $lead, 'canceled', $user_id, $customer_id );

		GFP_Stripe::log_debug( '2. Update entry meta subscription' );
		self::update_entry_meta_subscription( $lead[ 'id' ], array( 'status' => $canceled_subscription[ 'status' ] ) );

		if ( ! empty( $user_id ) ) {

			GFP_Stripe::log_debug( '3. Remove user\'s active subscription' );

			GFP_More_Stripe_Customer_API::remove_active_subscription( $user_id, $canceled_subscription[ 'id' ] );

			GFP_Stripe::log_debug( '4. Update user\'s saved subscription' );

			self::update_saved_subscription( $user_id, $canceled_subscription, $lead[ 'id' ] );
		}

		GFP_Stripe::log_debug( '5. Get Stripe rule' );

		$feed_id = gform_get_meta( $lead[ 'id' ], 'stripe_feed_id' );

		$feed = GFP_Stripe_Data::get_feed( $lead[ 'form_id' ], $feed_id );

		if ( ! $feed ) {
			return;
		}

		GFP_Stripe::log_debug( '6. Check for and do post actions' );

		//1- delete post or mark it as a draft based on configuration
		if ( 'draft' == rgars( $feed, 'meta/update_post_action' ) && ! rgempty( 'post_id', $lead ) ) {

			$post              = get_post( $lead[ 'post_id' ] );
			$post->post_status = 'draft';

			wp_update_post( $post );

		} else if ( 'delete' == rgars( $feed, 'meta/update_post_action' ) && ! rgempty( 'post_id', $lead ) ) {

			wp_delete_post( $lead[ 'post_id' ] );

		}

		GFP_Stripe::log_debug( '7. Add note' );

		self::add_note( $lead[ 'id' ], sprintf( __( "%s's subscription %s has been <strong>canceled</strong>.", 'gravityforms-stripe-more' ), $customer_id, $canceled_subscription[ 'id' ] ) );

		if ( empty( $lead[ 'transaction_id' ] ) ) {

			$transaction = GFP_Stripe_Data::get_transaction_by( 'entry', $lead[ 'id' ] );

			if ( ! empty( $transaction[ 'transaction_id' ] ) ) {
				$lead[ 'transaction_id' ] = $transaction[ 'transaction_id' ];
			}

		}

		GFP_Stripe::log_debug( '8. Call subscription canceled hook' );

		do_action( 'gform_subscription_canceled', $lead, $feed, $lead[ 'transaction_id' ], 'stripe', $user_id );

	}

	/**
	 * @param null $e
	 * @param      $notification
	 * @param      $form
	 * @param      $lead
	 */
	private static function notify_internal_error( $e = null, $notification, $form, $lead ) {

		if ( ! empty( $e ) ) {
			$error_class   = get_class( $e );
			$error_message = $e->getMessage();
			$response      = $error_class . ': ' . $error_message;
			GFP_Stripe::log_error( print_r( $response, true ) );
		}

		$notification = GFCommon::send_notification( $notification, $form, $lead );

	}

	/**
	 * @param      $entry
	 * @param      $reason
	 * @param bool $user_id
	 * @param      $customer_id
	 */
	public static function update_payment_info( $entry, $reason, $user_id = false, $customer_id ) {

		GFP_Stripe::log_debug( "Updating entry payment info to {$reason}" );

		switch ( $reason ) {
			case 'refunded':
				$entry[ 'payment_status' ] = 'Refunded';
				break;
			case 'canceled':
				$entry[ 'payment_status' ] = 'Canceled';
				break;
			case 'disputed':
				$entry[ 'payment_status' ] = 'Disputed';
				break;
			case 'captured':
				$entry[ 'payment_status' ] = 'Captured';
				break;
		}

		GFAPI::update_entry( $entry );

		do_action( "gfp_more_stripe_payment_{$reason}", $entry, $user_id, $customer_id );

	}

	//------------------------------------

	/**
	 * @param $product_info
	 * @param $form
	 * @param $lead
	 *
	 * @return mixed
	 */
	public static function gform_product_info( $product_info, $form, $lead ) {

		$feed = GFP_Stripe::get_feed_that_meets_condition( $form );

		if ( $feed ) {

			$feed = $feed[ 'meta' ];

			if ( ( isset( $feed[ 'coupons_enabled' ] ) && ! empty( $feed[ 'coupons_enabled' ] ) ) && ( is_numeric( $feed[ 'coupons_field' ] ) ) && ( 'now' == rgar( $feed, 'coupons_apply' ) || '' == rgars( $feed, 'coupons_apply' ) ) ) {

				$total = GFCommon::get_total( $product_info );

				$coupon = self::get_valid_coupon( $form, $feed, $lead ); //TODO cache this so not making multiple duplicate API calls

				if ( ! empty( $coupon ) ) {

					if ( ! empty( $coupon[ 'percent_off' ] ) ) {

						$line_item_price = - round( $total * ( $coupon[ 'percent_off' ] / 100 ), 2 );
						$line_item_name  = 'Coupon: ' . $coupon[ 'percent_off' ] . '% off';

					} else if ( ! empty( $coupon[ 'amount_off' ] ) ) {

						$currency_info   = self::get_currency_info( strtoupper( $coupon[ 'currency' ] ) );

						$amount_off      = ( 0 == $currency_info[ 'decimals' ] ) ? $coupon[ 'amount_off' ] : ( $coupon[ 'amount_off' ] / 100 );
						$line_item_price = - $amount_off;
						$line_item_name  = 'Coupon: ' . $amount_off . ' off';

					}

					$product_info[ 'products' ][ 'coupon' ] = array(
						'name'     => $line_item_name,
						'price'    => $line_item_price,
						'quantity' => 1,
						'id'       => $coupon[ 'id' ],
						'apply'    => 'now'
					);

				}

			}

			foreach ( $product_info[ 'products' ] as $field_id => $product ) {

				if ( is_numeric( $field_id ) ) {

					//check for subscription field setting
					$field = RGFormsModel::get_field( $form, $field_id );

					if ( ! rgempty( 'stripeSubscription', $field ) ) {

						$product_info[ 'products' ][ $field_id ][ 'stripe_subscription' ] = true;

						if ( ( ! empty( $feed[ 'subscription_end_after_field' ] ) ) && ( is_numeric( $feed[ 'subscription_end_after_field' ] ) ) ) {

							$end_subscription_field_id    = $feed[ 'subscription_end_after_field' ];
							$end_subscription_field       = RGFormsModel::get_field( $form, $end_subscription_field_id );
							$end_subscription_field_value = RGFormsModel::get_lead_field_value( $lead, $end_subscription_field );

							$product_info[ 'products' ][ $field_id ][ 'subscription_end_after' ] = (int) $end_subscription_field_value;
						}

						if ( ! rgempty( 'dynamicStripePlan', $field ) ) {

							$product_info[ 'products' ][ $field_id ][ 'stripe_subscription' ] = array(
								'dynamic_plan' => array(
									'interval'       => trim( rgget( 'dynamicStripePlanInterval', $field ) ),
									'interval_count' => trim( rgget( 'dynamicStripePlanIntervalCount', $field ) ),
									'trial_days'     => trim( rgget( 'dynamicStripePlanTrialDays', $field ) )
								),
								'quantity'     => $product[ 'quantity' ],
								'name'         => $product[ 'name' ],
								'options'      => array_key_exists( 'options', $product ) ? $product[ 'options' ] : null
							);

						}

					}

				}

			}

		}

		return $product_info;
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

		$coupons_enabled    = ! empty ( $feed[ 'meta' ][ 'coupons_enabled' ] );
		$valid_coupon_field = is_numeric( $feed[ 'meta' ][ 'coupons_field' ] );

		if ( $coupons_enabled && $valid_coupon_field ) {

			if ( 'now' == rgars( $feed, 'meta/coupons_apply' ) || '' == rgars( $feed, 'meta/coupons_apply' ) ) {

				$coupon_exists = array_key_exists( 'coupon', $products[ 'products' ] );

				if ( $coupon_exists ) {
					$form_data[ 'coupon' ] = $products[ 'products' ][ 'coupon' ];
				}

			} else if ( 'after' == rgars( $feed, 'meta/coupons_apply' ) ) {

				$coupon = self::get_valid_coupon( $form, $feed[ 'meta' ], $tmp_lead );

				if ( ! empty( $coupon ) ) {
					$coupon_exists         = true;
					$form_data[ 'coupon' ] = array(
						'id'    => $coupon[ 'id' ],
						'apply' => rgars( $feed, 'meta/coupons_apply' )
					);
				}

			}

		}

		if ( 'product' == $feed[ 'meta' ][ 'type' ] ) {

			if ( is_user_logged_in() && empty( $form_data[ 'credit_card' ] ) ) {

				$payment_method = rgpost( 'gform_payment_method' );

				if ( 'creditcard' !== $payment_method ) {
					$form_data[ 'card' ] = $payment_method;
				}

			}

		} else if ( 'subscription' == $feed[ 'meta' ][ 'type' ] || 'update-subscription' == $feed[ 'meta' ][ 'type' ] ) {

			$number_of_products = sizeof( $products[ 'products' ] );
			$subscription_plan  = is_numeric( $feed[ 'meta' ][ 'subscription_plan_field' ] ) ? array_key_exists( 'name', $products[ 'products' ][ $feed[ 'meta' ][ 'subscription_plan_field' ] ] ) : $feed[ 'meta' ][ 'subscription_plan_field' ];
			$setup_fee_enabled  = ! empty( $feed[ 'meta' ][ 'setup_fee_enabled' ] );
			$setup_fee          = false;

			if ( ! empty( $feed[ 'meta' ][ 'setup_fee_amount_field' ] ) ) {
				$setup_fee = array_key_exists( $feed[ 'meta' ][ 'setup_fee_amount_field' ], $products[ 'products' ] ) ? array_key_exists( 'name', $products[ 'products' ][ $feed[ 'meta' ][ 'setup_fee_amount_field' ] ] ) : false;
			}

			if ( ( $subscription_plan ) && ( ( 1 == $number_of_products ) || ( 2 == $number_of_products ) || ( 3 == $number_of_products ) ) ) {

				if ( ( ( 3 == $number_of_products ) && ( ( ! $coupon_exists ) || ( ( ! $setup_fee_enabled ) || ( ! $setup_fee ) ) ) ) ||
				     ( ( 2 == $number_of_products ) && ( ( ( ! $setup_fee_enabled ) || ( ! $setup_fee ) ) && ( ! $coupon_exists ) ) )
				) {

					return $form_data;

				} else {

					foreach ( $products[ 'products' ] as $field_id => $product ) {

						if ( array_key_exists( 'stripe_subscription', $product ) ) {

							$end_subscription = false;

							if ( array_key_exists( 'subscription_end_after', $product ) ) {
								$end_subscription = true;
							}

							if ( is_array( $product[ 'stripe_subscription' ] ) ) {

								if ( $end_subscription ) {
									$product[ 'stripe_subscription' ][ 'end_after' ] = $product[ 'subscription_end_after' ];
								}

								$form_data[ 'subscriptions' ][ ] = $product[ 'stripe_subscription' ];

							} else {

								$form_data[ 'subscriptions' ][ ] = apply_filters( 'gfp_more_stripe_subscription_plan_id', $product[ 'name' ], $field_id, $products, $form, $form_data, $feed );
								$form_data[ 'subscription_qty' ] = $product[ 'quantity' ];

								if ( $end_subscription ) {
									$form_data[ 'subscription_end_after' ] = $product[ 'subscription_end_after' ];
								}

							}

						}

					}

					if ( ( ! array_key_exists( 'subscriptions', $form_data ) ) && is_bool( $subscription_plan ) ) {

						$form_data[ 'plan' ] = $products[ 'products' ][ $feed[ 'meta' ][ 'subscription_plan_field' ] ][ 'name' ];
						$form_data[ 'subscription_qty' ] = $products[ 'products' ][ $feed[ 'meta' ][ 'subscription_plan_field' ] ][ 'quantity' ];

					}

					if ( ( $setup_fee_enabled ) && ( $setup_fee ) ) {

						$form_data[ 'setup_fee_amount' ] = $products[ 'products' ][ $feed[ 'meta' ][ 'setup_fee_amount_field' ] ][ 'price' ];
						$form_data[ 'setup_fee_name' ]   = $products[ 'products' ][ $feed[ 'meta' ][ 'setup_fee_amount_field' ] ][ 'name' ];

					}

					$form_data[ 'no_credit_card' ] = ! empty ( $feed[ 'meta' ][ 'free_trial_no_credit_card' ] );

					if ( is_user_logged_in() && empty( $form_data[ 'credit_card' ] ) && ! $form_data[ 'no_credit_card' ] ) {

						$payment_method = rgpost( 'gform_payment_method' );

						if ( ! empty( $payment_method ) && 'creditcard' !== $payment_method ) {
							$form_data[ 'card' ] = $payment_method;
						}

					}
				}
			}
		}

		$form_data[ 'stripe_mode' ] = GFPMoreStripe::get_stripe_mode( $form[ 'id' ] );
		PPP_Stripe_API::set_mode( $form_data[ 'stripe_mode' ] );

		$form_data[ 'metadata' ] = self::get_custom_metadata( $form, $feed, $tmp_lead );

		return $form_data;

	}

	/**
	 * Get custom metadata to submit to Stripe when form is submitted
	 *
	 * @since 1.8.20.1
	 *
	 * @param $form
	 * @param $feed
	 *
	 * @return array
	 */
	private static function get_custom_metadata( $form, $feed, $tmp_lead ) {

		$custom_metadata = array();

		if ( ! empty ( $feed[ 'meta' ][ 'metadata_enabled' ] ) && ! empty( $feed[ 'meta' ][ 'metadata' ] ) && is_array( $feed[ 'meta' ][ 'metadata' ] ) ) {

			foreach ( $feed[ 'meta' ][ 'metadata' ] as $metadata ) {

				$key_name                     = (string) substr( $metadata[ 'key_name' ], 0, 39 );
				$key_value                    = (string) substr( $tmp_lead[ $metadata[ 'key_value' ] ], 0, 499 );
				$custom_metadata[ $key_name ] = $key_value;

			}

		}

		return $custom_metadata;
	}

	/**
	 * @param $order_info_args
	 * @param $feed
	 *
	 * @return array
	 */
	public static function gfp_stripe_get_form_data_order_info( $order_info_args, $feed ) {

		if ( ( 'subscription' == $feed[ 'meta' ][ 'type' ] ) || ( 'update-subscription' == $feed[ 'meta' ][ 'type' ] ) ) {

			return array(
				'subscription_plan_field' => rgar( $feed[ 'meta' ], 'subscription_plan_field' ),
				'setup_fee_amount_field'  => rgar( $feed[ 'meta' ], 'setup_fee_amount_field' )
			);
		}
	}

	/**
	 * @param $continue_flag
	 * @param $field_id
	 * @param $additional_fields
	 *
	 * @return int
	 */
	public static function gfp_stripe_get_order_info( $continue_flag, $field_id, $additional_fields ) {

		if ( $additional_fields[ 'subscription_plan_field' ] ) {

			if ( is_numeric( $additional_fields[ 'subscription_plan_field' ] ) && ( ( $additional_fields[ 'subscription_plan_field' ] != $field_id ) && ! ( 'coupon' == $field_id ) && ( $additional_fields[ 'setup_fee_amount_field' ] != $field_id ) ) ) {
				return $continue_flag = 1;
			}

		}

	}

	/**
	 * @param $line_items
	 * @param $product_price
	 * @param $field_id
	 * @param $quantity
	 * @param $product
	 * @param $description
	 * @param $item
	 *
	 * @param $form_data
	 *
	 * @return string
	 */
	public static function gfp_stripe_get_order_info_line_items( $line_items, $product_price, $field_id, $quantity, $product, $description, $item, $form_data ) {

		if ( ( $product_price >= 0 ) || ( 'coupon' == $field_id ) ) {

			$line_item = "(" . $quantity . ")\t" . $product[ 'name' ] . "\t" . $description . "\tx\t" . GFCommon::to_money( $product_price, ( ! empty ( $form_data[ 'currency' ] ) ) ? $form_data[ 'currency' ] : '' );

			return $line_item;
		}

	}

	/**
	 * @param $line_items
	 * @param $products
	 * @param $amount
	 * @param $item
	 * @param $additional_fields
	 *
	 * @return array
	 */
	public static function gfp_stripe_get_order_info_shipping( $line_items, $products, $amount, $item, $additional_fields, $form_data ) {

		//if ( $additional_fields['subscription_plan_field'] ) {
		if ( ! empty( $products[ 'shipping' ][ 'name' ] ) /*&& ! is_numeric( $additional_fields['subscription_plan_field'] )*/ ) {

			$line_items[ ] = "(1)\t" . $products[ 'shipping' ][ 'name' ] . "\tx\t" . GFCommon::to_money( $products[ 'shipping' ][ 'price' ], ( ! empty ( $form_data[ 'currency' ] ) ) ? $form_data[ 'currency' ] : '' );
			$amount += $products[ 'shipping' ][ 'price' ];

			return array( 'line_items' => $line_items, 'amount' => $amount );

		}
		//}
	}

	/**
	 * @param $cancel
	 * @param $feed
	 * @param $form
	 *
	 * @return bool
	 */
	public static function gfp_more_stripe_cancel_charge( $cancel, $feed, $form ) {

		if ( 'save_cards_only' == rgar( $feed[ 'meta' ], 'alternate_charge_option' ) ) {
			$cancel = true;
		}

		return $cancel;
	}

	//------------------------------------------------------
	//-------------------- SUBMISSION --------------------
	//----------------------------------------------------

	public static function gform_save_field_value( $value, $lead, $field, $form ) {

		$transaction_response = GFP_Stripe::get_transaction_response();

		if ( ! empty( $transaction_response ) ) {

				$input_type = RGFormsModel::get_input_type( $field );

			if ( ( 'creditcard' == $input_type ) && ( rgpost( "input_{$field['id']}_4" ) !== $value ) ) {

					$transaction_type = $transaction_response[ 'transaction_type' ];

				switch ( $transaction_type ) {

						case '1':
					case '3':
					case '6':

							if ( ! empty( $transaction_response[ 'card' ] ) ) {

							$value = is_object( $transaction_response[ 'card' ] ) ? $transaction_response[ 'card' ][ 'id' ] : $transaction_response[ 'card' ];

							} else {

								$value = $transaction_response[ 'customer' ]->default_source[ 'id' ];

							}

					break;

					case '2':

						if ( ! empty( $transaction_response[ 'new_card' ] ) ) {

							$value = $transaction_response[ 'card' ][ 'id' ];

						} else {

							$value = $transaction_response[ 'subscription' ][ 'customer' ]->default_source[ 'id' ];

						}

						break;

					case '4':
						$value = $transaction_response[ 'card' ];
						break;
				}
			}
		}


		return $value;
	}

	/**
	 * @param $entry
	 *
	 * @internal param $transaction_response
	 *
	 * @return mixed
	 */
	public static function gfp_stripe_entry_post_save_update_lead( $entry ) {

		$transaction_response = GFP_Stripe::get_transaction_response();

		switch ( $transaction_response[ 'transaction_type' ] ) {

			case '2':

				$entry[ 'payment_status' ] = $transaction_response[ 'subscription' ][ 'status' ];
				$entry[ 'is_fulfilled' ]   = true;

				break;

			case '3':

				$entry[ 'payment_status' ] = 'Saved';
				$entry[ 'is_fulfilled' ]   = false;

				break;

			case '4':

				$entry[ 'payment_status' ] = 'Billing_Updated';

				break;

			case '5':

				$entry[ 'payment_status' ] = 'Subs_Updated';

				break;

			case '6':

				$entry[ 'payment_status' ] = 'Authorized';
				$entry[ 'is_fulfilled' ]   = false;
				$transaction_id            = $transaction_response[ 'transaction_id' ];
				$amount                    = array_key_exists( 'amount', $transaction_response ) ? $transaction_response[ 'amount' ] : null;
				$payment_date              = gmdate( 'Y-m-d H:i:s' );

				$entry[ 'payment_amount' ] = $amount;
				$entry[ 'transaction_id' ] = $transaction_id;
				$entry[ 'payment_date' ]   = $payment_date;

				break;
		}

		return $entry;
	}

	/**
	 * @param $transaction
	 *
	 * @internal param $default_type
	 * @internal param $transaction_response_transaction_type
	 *
	 * @return string
	 */
	public static function gfp_stripe_entry_post_save_insert_transaction( $transaction ) {

		$transaction_response = GFP_Stripe::get_transaction_response();;

		switch ( $transaction_response[ 'transaction_type' ] ) {
			case '1':
				$transaction[ 'meta' ][ 'object' ] = $transaction_response[ 'object' ]->__toArray( true );
				break;
			case '2':
				$transaction[ 'meta' ][ 'object' ] = $transaction_response[ 'object' ]->__toArray( true );
				if ( ! empty( $transaction_response[ 'invoice' ] ) ) {
					$transaction[ 'meta' ][ 'invoice' ] = $transaction_response[ 'invoice' ]->__toArray( true );
				}
				break;
			case '3':
				$transaction[ 'type' ]             = 'save_card';
				$transaction[ 'meta' ][ 'object' ] = $transaction_response[ 'object' ]->__toArray( true );
				break;
			case '4':
				$transaction[ 'type' ]             = 'update_billing';
				$transaction[ 'meta' ][ 'object' ] = $transaction_response[ 'object' ]->__toArray( true );
				break;
			case '5':
				$transaction[ 'type' ]             = 'update_subs';
				$transaction[ 'meta' ][ 'object' ] = $transaction_response[ 'object' ]->__toArray( true );
				if ( ! empty( $transaction_response[ 'invoice' ] ) ) {
					$transaction[ 'meta' ][ 'invoice' ] = $transaction_response[ 'invoice' ]->__toArray( true );
				}
				break;
			case '6':
				$transaction[ 'type' ]             = 'auth_card';
				$transaction[ 'meta' ][ 'object' ] = $transaction_response[ 'object' ]->__toArray( true );
				break;
		}

		if ( array_key_exists( 'user_id', $transaction_response ) && ! empty( $transaction_response[ 'user_id' ] ) ) {
			$transaction[ 'user_id' ] = $transaction_response[ 'user_id' ];
		}

		$transaction[ 'mode' ] = $transaction_response[ 'mode' ];

		return $transaction;
	}

	/**
	 * @param $entry
	 */
	public static function gfp_stripe_entry_post_save( $entry ) {

		$transaction_response = GFP_Stripe::get_transaction_response();

		switch ( $transaction_response[ 'transaction_type' ] ) {

			case '1':

				$transaction_response[ 'entry_id' ] = $entry[ 'id' ];

				GFP_Stripe::set_transaction_response( $transaction_response );

				gform_update_meta( $entry[ 'id' ], 'gfp_stripe_customer_id', $transaction_response[ 'customer' ][ 'id' ] );

				break;

			case '2':

				$subscription_meta = array(
					'id'           => $transaction_response[ 'subscription' ][ 'id' ],
					'status'       => $transaction_response[ 'subscription' ][ 'status' ],
					'plan'         => $transaction_response[ 'subscription' ][ 'plan' ],
					'start'        => $transaction_response[ 'subscription' ][ 'start' ],
					'end'          => $transaction_response[ 'subscription' ][ 'end' ],
					'next_payment' => $transaction_response[ 'subscription' ][ 'next_payment' ],
					'trial_end'    => $transaction_response[ 'subscription' ][ 'trial_end' ],
					'trial_start'  => $transaction_response[ 'subscription' ][ 'trial_start' ],
					'quantity'     => $transaction_response[ 'subscription' ][ 'quantity' ]
				);

				if ( isset( $transaction_response[ 'subscription' ][ 'setup_fee' ] ) ) {
					$subscription_meta[ 'setup_fee' ] = $transaction_response[ 'subscription' ][ 'setup_fee' ];
				}

				if ( isset( $transaction_response[ 'subscription' ][ 'end_after' ] ) ) {
					$subscription_meta[ 'end_after' ] = $transaction_response[ 'subscription' ][ 'end_after' ];
				}

				$stripe_subscription = array( $subscription_meta );

				gform_update_meta( $entry[ 'id' ], 'stripe_subscription', $stripe_subscription );
				gform_update_meta( $entry[ 'id' ], 'gfp_stripe_customer_id', $transaction_response[ 'subscription' ][ 'customer' ][ 'id' ] );


				$transaction_response[ 'subscription' ][ 'entry_id' ] = $entry[ 'id' ];

				GFP_Stripe::set_transaction_response( $transaction_response );

				break;

			case '3':
			case '6':

			gform_update_meta( $entry[ 'id' ], 'gfp_stripe_customer_id', $transaction_response[ 'customer' ][ 'id' ] );

					break;

		}

	}

	/**
	 * @param $user_id
	 * @param $feed
	 * @param $entry
	 * @param $password
	 */
	public static function gform_user_registered( $user_id, $feed, $entry, $password ) {

		$transaction_response = GFP_Stripe::get_transaction_response();

		if ( ( ! empty( $transaction_response ) ) && ( $transaction_response[ 'new_stripe_customer' ] ) ) {

			$transaction_response[ 'user_id' ]       = $user_id;
			$transaction_response[ 'user_password' ] = $password;
			GFP_Stripe::set_transaction_response( $transaction_response );

			RGFormsModel::update_lead_property( $entry[ 'id' ], 'created_by', $user_id );
			GFP_Stripe_Data::update_transaction( $entry[ 'id' ], 'user_id', $user_id );

		}

	}

	/**
	 * Create WP user and add Stripe information to meta
	 *
	 * Uses GFP_Stripe::transaction_response, which has transaction_id, amount, transaction_type, subscription, user_id
	 *
	 * @since 1.8.2
	 *
	 * @param array $entry {
	 *                     string form_id
	 *                     string currency
	 *                     string payment_status
	 *                     string payment_date
	 *                     string payment_amount
	 *                     string transaction_id
	 *                     string transaction_type
	 *                     string is_fulfilled
	 *                     }
	 * @param array $form
	 *
	 * @return void
	 */
	public static function gform_after_submission( $entry, $form ) {

		$transaction_response = GFP_Stripe::get_transaction_response();
		$settings             = get_option( 'gfp_stripe_settings' );

		if ( ! empty( $transaction_response ) ) {

			if ( ( empty( $settings[ 'disable_save_customers_as_users' ] ) && empty( $transaction_response[ 'feed' ][ 'meta' ][ 'disable_new_users' ] ) ) || ( array_key_exists( 'user_password', $transaction_response ) ) ) {

				$create_new_user     = $add_meta = $save_card = $save_subscription = false;
				$new_stripe_customer = array_key_exists( 'new_stripe_customer', $transaction_response ) && $transaction_response[ 'new_stripe_customer' ];
				$is_wp_user          = array_key_exists( 'user_id', $transaction_response ) && ! empty( $transaction_response[ 'user_id' ] );

				if ( $is_wp_user ) {
					$user_id = $transaction_response[ 'user_id' ];
				}

				$is_new_card = array_key_exists( 'new_card', $transaction_response ) && $transaction_response[ 'new_card' ];

				switch ( $transaction_response[ 'transaction_type' ] ) {

					case '1':
					case '3':
					case '6':

					$data            = $transaction_response;
						$customer        = $transaction_response[ 'customer' ];
						$create_new_user = $new_stripe_customer && ! $is_wp_user;

					$add_meta        = ( ! $create_new_user ) ? ( $new_stripe_customer && $is_wp_user ) : true;

							$save_card       = ( ! $add_meta ) ? $is_new_card : false;

							if ( $save_card ) {
							$card         = $data[ 'card' ];
							$make_default = false;
						}

					break;

					case '2':

						$data            = $transaction_response[ 'subscription' ];
						$customer        = $transaction_response[ 'subscription' ][ 'customer' ];

						$create_new_user = $new_stripe_customer && ! $is_wp_user;

						$add_meta        = ( ! $create_new_user ) ? ( $new_stripe_customer && $is_wp_user ) : true;

						$save_card       = ( ! $add_meta && ! $transaction_response[ 'no_credit_card' ] ) ? $is_new_card : false;

						if ( $save_card ) {
							$card         = $transaction_response[ 'card' ];
							$make_default = true;
						}

						$save_subscription = ( ! $add_meta ) ? true : false;
						break;
				}

				if ( $create_new_user ) {

					$name       = trim( $data[ 'customer_name' ] );
					$first_name = strstr( $name, ' ', true );
					$last_name  = trim( strstr( $name, ' ' ) );
					$email      = $customer[ 'email' ];
					$user_login = apply_filters( 'gfp_more_stripe_new_user_login', ( empty( $email ) ) ? $data[ 'customer' ][ 'id' ] : $email, $entry, $form );

					GFP_Stripe::log_debug( "Creating WordPress user {$first_name} {$last_name} {$user_login}" );

					$user_email = ( empty( $email ) ) ? false : $email;
					$user_id    = GFP_Stripe_Helper::create_wp_user( $first_name, $last_name, $user_login, $user_email );

					if ( is_wp_error( $user_id ) ) {

						$error_message = $user_id->get_error_message( $user_id->get_error_code() );

						GFP_Stripe::log_debug( "User creation failed: {$error_message}" );

						//notify admin
						$notification[ 'subject' ] = sprintf( __( "Unable to Create WordPress User for New Stripe Customer: %s", 'gravityforms-stripe-more' ), $name );
						$notification[ 'message' ] = sprintf( __( "Form: %s\\%s\r\n", 'gravityforms-stripe-more' ), $form[ 'id' ], $form[ 'title' ] );
						$notification[ 'message' ] .= sprintf( __( "Entry ID: %s\r\n", 'gravityforms-stripe-more' ), $entry[ 'id' ] );
						$notification[ 'message' ] .= sprintf( __( "Customer ID: %s\r\n", 'gravityforms-stripe-more' ), $customer[ 'id' ] );
						$notification[ 'message' ] .= __( "Error message: {$error_message}\r\n", 'gravityforms-stripe-more' );
						$notification[ 'to' ] = $notification[ 'from' ] = get_option( 'admin_email' );

						self::notify_internal_error( null, $notification, $form, $entry );

					} else {

						$entry[ 'created_by' ] = $user_id;

						RGFormsModel::update_lead_property( $entry[ 'id' ], 'created_by', $user_id );

						GFP_Stripe_Data::update_transaction( $entry[ 'id' ], 'user_id', $user_id );

					}

				}

				if ( is_int( $user_id ) ) {

					if ( $add_meta ) {

						gform_update_meta( $entry[ 'id' ], 'gfp_stripe_user_id', $user_id );

						self::add_stripe_meta( $user_id, $data );

						GFP_More_Stripe_Customer_API::add_metadata_to_customer( '', $customer, apply_filters( 'gfp_more_stripe_customer_metadata', array(
							'gravity_form'       => $form[ 'id' ],
							'gravity_form_entry' => $entry[ 'id' ],
							'wp_user_id'         => $user_id
						), $entry, $form ) );

					}

					if ( $save_card ) {

						GFP_More_Stripe_Customer_API::save_new_card( $user_id, $card, $make_default );
						//TODO: save card for each vendor

					}

					if ( $save_subscription ) {

						$already_saved = GFP_More_Stripe_Customer_API::get_subscription_info( $user_id, $data[ 'id' ] );

						if ( ! empty( $already_saved ) ) {

							self::update_saved_subscription( $user_id, $data, $entry[ 'id' ] );

						} else {

							GFP_More_Stripe_Customer_API::save_subscription( $user_id, $data );

						}

						update_user_meta( $user_id, '_gfp_stripe_currency', strtoupper( $customer[ 'currency' ] ) );

						gform_update_meta( $entry[ 'id' ], 'gfp_stripe_user_id', $transaction_response[ 'user_id' ] );

					} else if ( ( ! $create_new_user || ! $add_meta ) && $is_wp_user ) {

						gform_update_meta( $entry[ 'id' ], 'gfp_stripe_user_id', $transaction_response[ 'user_id' ] );

					}

					if ( ! is_user_logged_in() && array_key_exists( 'feed', $transaction_response ) && is_array( $transaction_response[ 'feed' ] ) && ! empty( $transaction_response[ 'feed' ][ 'meta' ][ 'auto_login' ] ) ) {

						$transaction_response = GFP_Stripe::get_transaction_response();
						$user                 = new WP_User( $user_id );
						wp_signon( array(
							           'user_login'    => $user->user_login,
							           'user_password' => $transaction_response[ 'user_password' ]
						           ) );

					}

				}

			} else {

				if ( in_array( $transaction_response[ 'transaction_type' ], array( '1', '2', '3', '6' ) ) ) {

					switch ( $transaction_response[ 'transaction_type' ] ) {
						case '1':
						case '3':
						case '6':
							$customer = $transaction_response[ 'customer' ];
							break;
						case '2':
							$customer = $transaction_response[ 'subscription' ][ 'customer' ];
							break;
					}

					if ( ! empty( $customer ) && empty( $customer->metadata[ 'gravity_form_entry' ] ) ) {

						$metadata = array(
							'gravity_form'       => $form[ 'id' ],
							'gravity_form_entry' => $entry[ 'id' ]
						);

						if ( array_key_exists( 'user_id', $transaction_response ) && ! empty( $transaction_response[ 'user_id' ] ) ) {
							$metadata[ 'wp_user_id' ] = $transaction_response[ 'user_id' ];
						}

						GFP_More_Stripe_Customer_API::add_metadata_to_customer( '', $customer, apply_filters( 'gfp_more_stripe_customer_metadata', $metadata, $entry, $form ) );
					}
				}
			}

			self::do_gf_payment_hooks( $entry );
		}
	}

	private static function do_gf_payment_hooks( $entry ) {

		$transaction_response = GFP_Stripe::get_transaction_response();

		if ( ! empty( $transaction_response ) && ! empty( $transaction_response[ 'transaction_type' ] ) && 2 == $transaction_response[ 'transaction_type' ] ) {

			do_action( 'gform_post_subscription_started', $entry, array(
				'subscription_id' => $transaction_response[ 'subscription' ][ 'id' ],
				'customer_id'     => $transaction_response[ 'subscription' ][ 'customer' ][ 'id' ],
				'is_success'      => true,
				'payment_amount'  => $transaction_response[ 'amount' ]
			) );

		}

	}

	//------------------------------------

	/**
	 * @param $user_id
	 * @param $transaction_type
	 * @param $data
	 */
	public static function add_stripe_meta( $user_id, $data ) {

		GFP_Stripe::log_debug( "Adding Stripe meta to user {$user_id}" );

		$customer_id = $data[ 'customer' ][ 'id' ];

		update_user_meta( $user_id, apply_filters( 'gfp_more_stripe_user_meta_customer_id_key', '_gfp_stripe_customer_id' ), apply_filters( 'gfp_more_stripe_user_meta_customer_id_value', $customer_id ) );

		$transaction_response = GFP_Stripe::get_transaction_response();
		$transaction_type     = $transaction_response[ 'transaction_type' ];

		if ( 1 == $transaction_type || 2 == $transaction_type ) {
			update_user_meta( $user_id, 'gf_entry_id', $data[ 'entry_id' ] );
		}

		$save_card = true;

		if ( '2' == $transaction_type && $transaction_response[ 'no_credit_card' ] ) {
			$save_card = false;
		}

		if ( $save_card ) {

			$card = ( ( ( '1' == $transaction_type ) || ( '3' == $transaction_type ) || ( '6' == $transaction_type ) ) && $data[ 'new_card' ] ) ? $data[ 'card' ] : $data[ 'customer' ]->default_source;

			GFP_More_Stripe_Customer_API::save_new_card( $user_id, $card );

		}

		if ( '2' == $transaction_type ) {
			$subscription = $data;
			GFP_More_Stripe_Customer_API::save_subscription( $user_id, $subscription );
		}

		if ( '3' !== $transaction_type ) {
			update_user_meta( $user_id, '_gfp_stripe_currency', strtoupper( $data[ 'customer' ][ 'currency' ] ) );
		}

		do_action( 'gfp_more_stripe_add_stripe_meta', $user_id, $transaction_type, $data );

	}

	/**
	 * @param $user_id
	 * @param $subscription
	 */
	public static function update_stripe_meta( $user_id, $subscription ) {
	}


	//------------------------------------------------------
	//------------- ENTRY PAGE -----------------------------
	//------------------------------------------------------

	public static function gfp_stripe_entry_detail_transaction_id( $transaction_id, $form, $entry ) {

		switch ( $entry[ 'transaction_type' ] ) {
			case '1':
			case '6':
				$object_type = 'charge';
				break;
			case '2':
				$object_type = 'invoice';
				break;
			case '3':
				$object_type = 'customer';
				break;
			case '4':
				$object_type = 'card';
				break;
		}

		if ( ! empty( $object_type ) ) {

			$transaction    = GFP_Stripe_Data::get_transaction_by( 'entry', $entry[ 'id' ] );

			$mode           = ( 'test' == $transaction[ 'mode' ] ) ? false : true;

			$link           = PPP_Stripe_API::create_stripe_dashboard_link( $transaction[ 'transaction_id' ], $object_type, $mode );

			$transaction_id = "<a href=\"{$link}\" alt=\"View this transaction on Stripe dashboard\" title=\"View on Stripe dashboard\" target=\"_blank\">{$transaction['transaction_id']}</a>";

		}

		return $transaction_id;
	}

	/**
	 * @param $form_id
	 * @param $entry
	 */
	public static function gfp_stripe_payment_details( $form, $entry ) {

		$entry_id = $entry[ 'id' ];
		$transaction_type = $entry[ 'transaction_type' ];

		$cancelsub_button = '';

		$customer_id = gform_get_meta( $entry[ 'id' ], 'gfp_stripe_customer_id' );

		$transaction = GFP_Stripe_Data::get_transaction_by( 'entry', $entry[ 'id' ] );

		$mode        = ( ! empty( $transaction[ 'mode' ] ) && 'test' == $transaction[ 'mode' ] ) ? false : true;

		$link        = PPP_Stripe_API::create_stripe_dashboard_link( $customer_id, 'customer', $mode );

		$customer_id = "<a href=\"{$link}\" alt=\"View this customer on Stripe dashboard\" title=\"View on Stripe dashboard\" target=\"_blank\">{$customer_id}</a>";

		$stripe_subscriptions = gform_get_meta( $entry[ 'id' ], 'stripe_subscription' );

		if ( ! empty( $stripe_subscriptions ) ) {
			$stripe_subscription = $stripe_subscriptions[ 0 ];
			$scheduled_payments  = ( ! empty( $stripe_subscription[ 'end_after' ] ) ) ? $stripe_subscription[ 'end_after' ] : 'Indefinite';

			if ( 'canceled' != $stripe_subscription[ 'status' ] && 'Active/Canceled' != $entry[ 'payment_status' ] ) {
				$cancelsub_button .= '<input id="cancelsub" type="button" name="cancelsub" value="' . __( 'Cancel Subscription', 'gravityforms-stripe-more' ) . '" class="button" onclick=" if( confirm(\'' . __( "Warning! This Stripe Subscription will be canceled. This cannot be undone. \'OK\' to cancel subscription, \'Cancel\' to stop", 'gravityforms-stripe-more' ) . '\')){cancel_stripe_subscription();};"/>';

				$cancelsub_button .= '<img src="' . GFCommon::get_base_url() . '/images/spinner.gif" id="stripe_wait" style="display: none;"/>';

				$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
				wp_enqueue_script( 'gfp_more_stripe_entry_detail', trailingslashit( GFP_MORE_STRIPE_URL ) . "includes/entries/entry-detail{$suffix}.js", array( 'jquery' ), GFPMoreStripe::get_version() );
				$entry_detail_js_data = array(
					'lead_id'         => $entry_id,
					'form_id'         => $form[ 'id' ],
					'nonce'           => wp_create_nonce( 'gfp_more_stripe_cancel_subscription' ),
					'success_message' => __( 'Canceled (at period end)', 'gravityforms-stripe-more' ),
					'error_message'   => __( 'The subscription could not be canceled: ', 'gravityforms-stripe-more' )
				);
				wp_localize_script( 'gfp_more_stripe_entry_detail', 'stripe_entry_detail', $entry_detail_js_data );
			}
		}

		require_once( trailingslashit( GFP_MORE_STRIPE_PATH ) . 'includes/entries/entry-detail-stripe-payment-details.php' );
	}

	/**
	 *
	 */
	public static function gfp_more_stripe_cancel_subscription() {

		check_ajax_referer( 'gfp_more_stripe_cancel_subscription', 'gfp_more_stripe_cancel_subscription' );

		$lead_id = $_POST[ 'leadid' ];
		$form_id = $_POST[ 'formid' ];

		$user_id     = gform_get_meta( $lead_id, 'gfp_stripe_user_id' );
		$customer_id = gform_get_meta( $lead_id, 'gfp_stripe_customer_id' );

		$api_key = GFP_Stripe::get_api_key( 'secret', GFPMoreStripe::get_stripe_mode( $form_id ) );

		$customer = PPP_Stripe_API::retrieve( 'customer', $api_key, $customer_id );

		if ( ! is_object( $customer ) ) {

				$error_message = $customer;

			wp_send_json_error( $error_message );

		}

		GFP_Stripe::log_debug( "Sending cancel subscription request" );

		$canceled_subscription = self::cancel_subscription( $api_key,
		                                                    $customer,
		                                                    true,
		                                                    $lead_id,
		                                                    true,
		                                                    $user_id
		);

		if ( is_wp_error( $canceled_subscription ) ) {

			$error_message = $canceled_subscription->get_error_message( $canceled_subscription->get_error_code() );

			wp_send_json_error( $error_message );

		} else if ( ! is_object( $canceled_subscription ) ) {

			$error_message = $canceled_subscription;

			wp_send_json_error( $error_message );

		}

		wp_send_json_success();


	}

	//------------------------------------------------------
	//------------- STRIPE COUPONS -------------------------
	//------------------------------------------------------

	/**
	 *
	 */
	public static function gfp_more_stripe_get_coupon() {

		$coupon  = rgpost( 'coupon' );
		$form_id = rgpost( 'form' );

		$mode    = self::get_stripe_mode( $form_id );
		$api_key = GFP_Stripe::get_api_key( 'secret', $mode );

		PPP_Stripe_API::set_mode( $mode );

		$result = PPP_Stripe_API::validate_coupon( $api_key, $coupon );

		$response = array();
		$error    = false;

		if ( 'success' == $result[ 'status' ] ) {

			$coupon = $result[ 'data' ];

			if ( $coupon[ 'livemode' ] && ( ! ( $mode == 'live' ) ) ) {

				$message                     = __( 'This is not a valid coupon.', 'gravityforms-stripe-more' );
				$error                       = true;

				$response[ 'error_message' ] = $message;

			} else {

				$message = __( 'This coupon is no longer valid.', 'gravityforms-stripe-more' );

				if ( ( ! empty( $coupon[ 'max_redemptions' ] ) ) && ( ! empty( $coupon[ 'times_redeemed' ] ) ) ) {

					if ( $coupon[ 'times_redeemed' ] == $coupon[ 'max_redemptions' ] ) {

						$error                       = true;

						$response[ 'error_message' ] = $message;

						}

				} else if ( ( ! empty( $coupon[ 'redeem_by' ] ) ) && ( $coupon[ 'redeem_by' ] < strtotime( 'now' ) ) ) {

					$error                       = true;

					$response[ 'error_message' ] = $message;

				}

			}

			if ( ! $error ) {

				if ( ! empty( $coupon[ 'percent_off' ] ) ) {

					$response[ 'percent_off' ] = $coupon[ 'percent_off' ];

				} else if ( ! empty( $coupon[ 'amount_off' ] ) ) {

					$response[ 'amount_off' ] = $coupon[ 'amount_off' ];

				} else {

					$error                       = true;

					$response[ 'error_message' ] = __( 'This form cannot process this coupon. Please contact site owner.', 'gravityforms-stripe-more' );

				}

			}

		} else {

			$error                       = true;

			$response[ 'error_message' ] = $result[ 'data' ];

		}

		if ( $error ) {

			GFP_Stripe::log_error( $response[ 'error_message' ] );
			GFP_Stripe::log_debug( 'Sending error' );

			wp_send_json_error( $response );

		} else {

			GFP_Stripe::log_debug( 'Sending success' );

			wp_send_json_success( $response );

		}

	}

	private static function get_valid_coupon( $form, $feed_meta, $lead ) {

		$valid_coupon = array();

		$coupon_field_id = rgar( $feed_meta, 'coupons_field' );
		$field           = RGFormsModel::get_field( $form, $coupon_field_id );
		$value           = RGFormsModel::get_lead_field_value( $lead, $field );
		$api_key = GFP_Stripe::get_api_key( 'secret', GFP_Stripe_Helper::get_form_stripe_mode( $form[ 'id' ] ) );
		$result  = PPP_Stripe_API::validate_coupon( $api_key, $value );

		if ( 'success' == $result[ 'status' ] ) {
			$valid_coupon = $result[ 'data' ];
		}

		return $valid_coupon;
	}

	//------------------------------------------------------
	//------------- POST METABOX ---------------------------
	//------------------------------------------------------

	/************************************************************************************************
	 * This feature was generously sponsored by: Gerard Ramos of Revelry Labs LLC http://revelry.co/
	 ***********************************************************************************************/

	/**
	 * @param $post_type
	 * @param $post
	 */
	public static function add_meta_boxes( $post_type, $post ) {

		$form_id = get_post_meta( $post->ID, '_gform-form-id', true );
		$lead_id = get_post_meta( $post->ID, '_gform-entry-id', true );

		if ( ( ! empty( $form_id ) ) && ( ! empty( $lead_id ) ) ) {

			$lead = RGFormsModel::get_lead( $lead_id );

			if ( ( 'Saved' == $lead[ 'payment_status' ] ) && ( ! $lead[ 'is_fulfilled' ] ) ) {

				add_meta_box( 'gfp_stripe_metabox', __( 'Stripe', 'gravityforms-stripe-more' ), array(
					'GFPMoreStripe',
					'add_meta_box'
				), null, 'side', 'high', array( 'lead_id' => $lead_id, 'form_id' => $form_id ) );

			}

		}

	}

	/**
	 * @param $post
	 * @param $metabox
	 */
	public static function add_meta_box( $post, $metabox ) {

		$chargecustomer_button = '';
		$chargecustomer_button .= '<input id="chargecustomer" type="button" name="chargecustomer" value="' . __( 'Charge Customer', 'gravityforms-stripe-more' ) . '" class="button" onclick="if( confirm(\'' . __( "Warning! This customer\'s card will be charged. This cannot be undone. \'OK\' to charge card, \'Cancel\' to stop", 'gravityforms-stripe-more' ) . '\')){charge_customer_for_post();};"/>';

		$chargecustomer_button .= '<img src="' . GFCommon::get_base_url() . '/images/spinner.gif" id="stripe_wait" style="display: none;"/>';

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_script( 'gfp_more_stripe_charge_for_post', trailingslashit( GFP_MORE_STRIPE_URL ) . "js/post-metabox-charge-for-post{$suffix}.js", array( 'jquery' ), GFPMoreStripe::get_version() );

		$charge_for_post_js_data = array(
			'lead_id'         => $metabox[ 'args' ][ 'lead_id' ],
			'form_id'         => $metabox[ 'args' ][ 'form_id' ],
			'nonce'           => wp_create_nonce( 'gfp_more_stripe_charge_customer_for_post' ),
			'success_message' => __( 'Charge successful!', 'gravityforms-stripe-more' ),
			'error_message'   => __( 'The customer could not be charged. Please try again later.', 'gravityforms-stripe-more' )
		);

		wp_localize_script( 'gfp_more_stripe_charge_for_post', 'stripe_charge_for_post', $charge_for_post_js_data );

		echo $chargecustomer_button;
	}

	/**
	 *
	 */
	public static function gfp_more_stripe_charge_customer_for_post() {

		check_ajax_referer( 'gfp_more_stripe_charge_customer_for_post', 'gfp_more_stripe_charge_customer_for_post' );

		$entry_id = $_POST[ 'leadid' ];
		$form_id  = $_POST[ 'formid' ];
		$entry    = RGFormsModel::get_lead( $entry_id );

		GFP_Stripe::log_debug( '----CHARGE CUSTOMER FOR POST----' );
		GFP_Stripe::log_debug( "Retrieving customer ID from entry meta (entry $entry_id)..." );

		$customer_id = gform_get_meta( $entry_id, 'gfp_stripe_customer_id' );

		if ( empty( $customer_id ) ) {
			$customer_id = $entry[ 'transaction_id' ];
		}

		GFP_Stripe::log_debug( 'Retrieving saved transaction...' );

		$transaction = GFP_Stripe_Data::get_transaction_by( 'entry', $entry_id );

		if ( empty( $transaction ) ) {

			$error_message = __( 'There is no transaction for this entry', 'gravityforms-stripe-more' );

			GFP_Stripe::log_error( $error_message );
			GFP_Stripe::log_debug( '------------------------' );

			wp_send_json_error( $error_message );

		}

		GFP_Stripe::log_debug( "Retrieving customer {$customer_id} from Stripe..." );

		$api_key = GFP_Stripe::get_api_key( 'secret', $transaction[ 'mode' ] );

		$customer = PPP_Stripe_API::retrieve( 'customer', $api_key, $customer_id );

		if ( ! is_object( $customer ) ) {

			$error_message = $customer;

			GFP_Stripe::log_error( $error_message );
			GFP_Stripe::log_debug( '------------------------' );

			wp_send_json_error( $error_message );

		}

		GFP_Stripe::log_debug( 'Creating the charge for this customer...' );

		GFP_Stripe::log_debug( 'Retrieving card from entry...' );

		$form = RGFormsModel::get_form_meta( $form_id );

		foreach ( $form[ 'fields' ] as $field ) {

			if ( 'creditcard' == $field[ 'type' ] ) {

				$creditcard_field_id = $field[ 'id' ];

				break;
			}

		}

		if ( ! empty( $creditcard_field_id ) ) {
			$card = $entry[ $creditcard_field_id . '.1' ];
		}

		$amount        = round( floatval( $transaction[ 'amount' ] ), 0 );
		$currency_info = self::get_currency_info( $entry[ 'currency' ] );
		$amount        = ( 0 == $currency_info[ 'decimals' ] ) ? $amount : round( ( $amount * 100 ), 0 );

		$charge_args   = array(
			'amount'      => $amount,
			'currency'    => $entry[ 'currency' ],
			'customer'    => $customer[ 'id' ],
			'description' => apply_filters( 'gfp_stripe_customer_charge_description', 'Charge for created post', $form_id )
		);

		if ( ! empty( $card ) ) {
			$charge_args[ 'source' ] = $card;
		}

		$charge_args = apply_filters( 'gfp_more_stripe_create_charge_args', $charge_args, $form_id );

		if ( ! is_array( $charge_args ) ) {

			$error_message = $charge_args;

			GFP_Stripe::log_error( $error_message );
			GFP_Stripe::log_debug( '------------------------' );

			wp_send_json_error( $error_message );

		}

		$charge = PPP_Stripe_API::create_charge( apply_filters( 'gfp_more_stripe_api_key', $api_key, 'create_charge' ), $charge_args );

		if ( ! is_object( $charge ) ) {

			$error_message = $charge;

			GFP_Stripe::log_error( $error_message );
			GFP_Stripe::log_debug( '------------------------' );

			wp_send_json_error( $error_message );

		}

		GFP_Stripe::log_debug( "Charge successful: {$charge['id']}" );
		GFP_Stripe::log_debug( '------------------------' );

		self::mark_post_entry_paid( $entry, $charge );

		wp_send_json_success();
	}

	/**
	 * @param $entry
	 */
	private static function mark_post_entry_paid( $entry, $charge ) {

		$entry[ 'payment_status' ] = 'Paid';
		$entry[ 'is_fulfilled' ]   = true;

		GFAPI::update_entry( $entry );

		$human_charge = GFP_Stripe_Event_Handler::parse_charge( $charge );

		$charge_link = PPP_Stripe_API::create_stripe_dashboard_link( $charge[ 'id' ], 'charge', $charge[ 'livemode' ] );
		$charge      = "<a href=\"{$charge_link}\" alt=\"View this charge on Stripe dashboard\" title=\"View on Stripe dashboard\" target=\"_blank\">{$charge['id']}</a>";

		$amount = "{$human_charge['amount']} {$human_charge['currency']}";

		$card_info = "{$human_charge['payment_card_brand']} ending in {$human_charge['payment_card_last4']}";

		$note = sprintf( __( '<em>%1$s</em> was successfully <strong>charged</strong> <em>%2$s</em> (%3$s) for post.', 'gravityforms-stripe-more' ), $card_info, $amount, $charge );

		GFPMoreStripe::add_note( $entry[ 'id' ], $note, 'success' );
	}

	//------------------------------------------------------
	//------------- UNINSTALL ------------------------------
	//------------------------------------------------------

	/**
	 * Delete lead meta data
	 *
	 * @since
	 *
	 * @return void
	 */
	private static function delete_more_stripe_meta() {

		global $wpdb;

		$table_name = RGFormsModel::get_lead_meta_table_name();
		$wpdb->query( "DELETE FROM $table_name WHERE meta_key in ( 'subscription_amount', 'subscription_payment_count', 'subscription_payment_date', 'gfp_stripe_user_id', 'subscription_end_after', 'Stripe_subscription', 'gfp_stripe_customer_id', 'Stripe_feed_id', 'stripe_feed_id' )" );

		$usermeta_table = $wpdb->prefix . 'usermeta';
		$sql            = "DELETE FROM $usermeta_table WHERE meta_key LIKE '_gfp_stripe_%' OR meta_key IN ( 'gf_entry_id' )";
		$wpdb->query( $sql );
	}

	/**
	 *
	 *
	 * @since
	 *
	 * @return void
	 */
	private static function delete_more_stripe_feeds() {

		$form_meta = GFP_Stripe_Data::get_all_feeds();

		foreach ( $form_meta as $meta ) {

			foreach ( $meta[ 'rules' ] as $feed ) {

				switch ( $feed[ 'type' ] ) {

					case 'subscription':
					case 'update-billing':
					case 'update-subscription':

					GFP_Stripe_Data::delete_feed( $feed[ 'id' ], $meta[ 'form_id' ] );

					break;

				}

			}

		}

	}

	/**
	 *
	 *
	 * @since
	 *
	 * @return void
	 */
	public static function gfp_stripe_uninstall_condition() {

		if ( class_exists( 'GFPMoreStripe' ) ) {
			die( __( 'You must first uninstall Stripe additional feature plugins.', 'gravityforms-stripe-more' ) );
		}

	}

	/**
	 *
	 *
	 * @since
	 *
	 * @return void
	 */
	public static function uninstall() {

		if ( ! GFP_Stripe::has_access( 'gfp_stripe_uninstall' ) ) {
			die( __( 'You don\'t have adequate permission to uninstall the More Stripe Add-On.', 'gravityforms-stripe-more' ) );
		}

		delete_option( 'gfp_more_stripe_version' );

		$settings = get_option( 'gfp_stripe_settings' );

		unset( $settings[ 'stripe_webhook_configured' ] );
		unset( $settings[ 'disable_save_customers_as_users' ] );
		unset( $settings[ 'enable_early_access' ] );

		update_option( 'gfp_stripe_settings', $settings );

		function_exists( 'get_site_transient' ) ? delete_site_transient( 'gfp_more_stripe_version' ) : delete_transient( 'gfp_more_stripe_version' );

		self::delete_more_stripe_meta();

		self::delete_more_stripe_feeds();

		do_action( 'gfp_more_stripe_uninstall' );

		$plugin = 'gravityforms-stripe-more/more-stripe.php';

		deactivate_plugins( $plugin );

		update_option( 'recently_activated', array( $plugin => time() ) + (array) get_option( 'recently_activated' ) );
	}

	//------------------------------------------------------
	//------------- HELPERS ------------------------------
	//------------------------------------------------------
	/**
	 * @param $form_id
	 *
	 * @return mixed
	 */
	public static function get_stripe_mode( $form_id ) {

		//_deprecated_function( "GFPMoreStripe::get_stripe_mode()", "1.8.13.1", "GFP_Stripe_Helper::get_form_stripe_mode()" );
		return GFP_Stripe_Helper::get_form_stripe_mode( $form_id );
	}

	/**
	 * @param string $currency_code
	 *
	 * @return mixed
	 */
	public static function get_currency_info( $currency_code = '' ) {
		return GFP_Stripe_Helper::get_currency_info( $currency_code );
	}

	/**
	 * @param      $entry_id
	 * @param      $note
	 * @param null $note_type
	 */
	public static function add_note( $entry_id, $note, $note_type = null ) {

		GFP_Stripe_Helper::add_note( $entry_id, $note, $note_type );

	}

	public static function get_early_access() {
		return GFP_Stripe_Helper::get_early_access();
	}
}