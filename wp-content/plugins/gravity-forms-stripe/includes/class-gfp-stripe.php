<?php
/**
 * @package   GFP_Stripe
 * @copyright 2013-2014 gravity+
 * @license   GPL-2.0+
 * @since     0.1.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * GFP_Stripe Class
 *
 * Controls everything
 *
 * @since 0.1.0
 * */
class GFP_Stripe {

	/**
	 * Instance of this class.
	 *
	 * @since    1.7.9.1
	 *
	 * @var      object
	 */
	private static $_this = null;

	/**
	 *
	 *
	 * @since 0.1.0
	 *
	 * @var string
	 */
	private static $slug = "gravity-forms-stripe";

	/**
	 *
	 * @since 0.1.0
	 *
	 * @var string
	 */
	private static $version = '1.9.2.7';

	/**
	 *
	 *
	 * @since
	 *
	 * @var string
	 */
	private static $min_gravityforms_version = '1.9';

	/**
	 *
	 *
	 * @since 1.9.2.6
	 *
	 * @var string
	 */
	private static $min_gravityforms_stripe_more_version = '1.9.2.3';

	/**
	 *
	 *
	 * @since 0.1.0
	 *
	 * @var string
	 */
	private static $transaction_response = '';

	/**
	 * Holds information for mapped fields in Stripe rule, used for Stripe JS
	 *
	 * @since 1.8.17.1
	 *
	 * @var array
	 */
	private static $stripe_rule_field_info = array();

	/**
	 * @since 1.8.2
	 *
	 * @var bool
	 */
	public static $do_usage_stats = false;

	/**
	 * Initialize the plugin by setting localization, filters, and administration functions.
	 *
	 * @since     1.7.9.1
	 *
	 * @uses      wp_die()
	 * @uses      __()
	 * @uses      register_activation_hook()
	 * @uses      add_action()
	 *
	 */
	function __construct() {

		if ( isset( self::$_this ) ) {
			wp_die( sprintf( __( 'There is already an instance of %s.',
			                     'gravity-forms-stripe' ), get_class( $this ) ) );
		}

		self::$_this = $this;

		register_activation_hook( GFP_STRIPE_FILE, array( 'GFP_Stripe', 'activate' ) );

		register_activation_hook( 'gravityforms-stripe-more/more-stripe.php', array(
			'GFP_Stripe',
			'more_stripe_activate'
		) );

		register_activation_hook( 'gravityforms-stripe-more/gravityforms-stripe-more.php', array(
			'GFP_Stripe',
			'more_stripe_activate'
		) );

		add_action( 'init', array( $this, 'init' ) );

		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );

		add_filter( 'upgrader_post_install', array( $this, 'upgrader_post_install' ), 10, 2 );

	}

	/**
	 * Private clone method to prevent cloning of the instance of the
	 * *Singleton* instance.
	 *
	 * @return void
	 */
	private function __clone() {
	}

	/**
	 * Private unserialize method to prevent unserializing of the *Singleton*
	 * instance.
	 *
	 * @return void
	 */
	private function __wakeup() {
	}

	/**
	 * @return GFP_Stripe|null|object
	 */
	static function this() {
		return self::$_this;
	}

	//------------------------------------------------------
	//------------- SETUP --------------------------
	//------------------------------------------------------

	/**
	 * Activation
	 *
	 * @since 0.1.0
	 *
	 * @uses  GFP_Stripe::check_for_gravity_forms()
	 * @uses  GFP_Stripe::check_server_requirements()
	 * @uses  GFP_Stripe::add_permissions()
	 * @uses  GFP_Stripe::redirect_to_settings_page()
	 * @uses  delete_transient()
	 *
	 * @return void
	 */
	public static function activate() {

		delete_transient( 'gfp_stripe_currency' );
		delete_transient( 'gfp_stripe_usage_stats_cache_data' );

		self::$_this->check_for_gravity_forms();

		self::$_this->check_for_more_stripe();

		self::$_this->check_server_requirements();

		self::$_this->add_permissions();
		self::$_this->set_settings_page_redirect();

	}

	/**
	 * When More Stripe is being activated, deactivate it if it doesn't meet the minimum version for this version of +Stripe
	 *
	 * @since 1.9.2.6
	 */
	public static function more_stripe_activate() {

		if ( ( array_key_exists( 'action', $_POST ) ) && ( 'activate-selected' == $_POST[ 'action' ] ) && ( in_array( 'gravity-forms-stripe/gravity-forms-stripe.php', $_POST[ 'checked' ] ) ) ) {

			return;

		} else {

			$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . 'gravityforms-stripe-more/more-stripe.php' );

			if ( empty( $plugin_data[ 'Version' ] ) ) {

				$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . 'gravityforms-stripe-more/gravityforms-stripe-more.php' );


				if ( empty( $plugin_data[ 'Version' ] ) ) {

					return;
				}
			}

			if ( ! version_compare( $plugin_data[ 'Version' ], self::$min_gravityforms_stripe_more_version, '>=' ) ) {

				deactivate_plugins( 'gravityforms-stripe-more/more-stripe.php' );
				deactivate_plugins( 'gravityforms-stripe-more/gravityforms-stripe-more.php' );

				$message = "Your current version of Gravity Forms + Stripe requires at least More Stripe " . self::$min_gravityforms_stripe_more_version . '. You may need to renew your license to get the latest version.';
				die( $message );
			}

		}
	}

	/**
	 * If More Stripe is installed, deactivate it when we upgrade +Stripe
	 *
	 * @since 1.9.2.7
	 *
	 * @param $return
	 * @param $plugin
	 *
	 * @return bool
	 */
	public function upgrader_post_install( $return, $plugin ) {

		if ( $return ) {

				$plugin = isset( $plugin[ 'plugin' ] ) ? $plugin[ 'plugin' ] : '';

				if ( 'gravity-forms-stripe/gravity-forms-stripe.php' == $plugin ) {

					$more_stripe_options = array(
						'gravityforms-stripe-more/more-stripe.php',
						'gravityforms-stripe-more/gravityforms-stripe-more.php'
					);

					foreach ( $more_stripe_options as $more_stripe ) {

						if ( is_plugin_active( $more_stripe ) ) {

							deactivate_plugins( $more_stripe );

							break;

						}

					}

					delete_transient( 'gfp_stripe_currency' );

				}

			}

		return $return;
	}

	/**
	 * Make sure Gravity Forms is installed before activating this plugin
	 *
	 * @since 1.7.9.1
	 *
	 * @uses  deactivate_plugins()
	 * @uses  __()
	 *
	 * @return void
	 */
	public function check_for_gravity_forms() {
		if ( ( array_key_exists( 'action', $_POST ) ) && ( 'activate-selected' == $_POST[ 'action' ] ) && ( in_array( 'gravityforms/gravityforms.php', $_POST[ 'checked' ] ) ) ) {
			return;
		} else if ( ! class_exists( 'GFForms' ) ) {
			deactivate_plugins( basename( GFP_STRIPE_FILE ) );
			$message = __( 'You must install and activate Gravity Forms first.', 'gravity-forms-stripe' );
			die( $message );
		}
	}

	/**
	 * If More Stripe is installed when activating +Stripe, make sure it's deactivated
	 *
	 * @since 1.9.2.6
	 */
	public function check_for_more_stripe() {

		if ( ( array_key_exists( 'action', $_POST ) ) && ( 'activate-selected' == $_POST[ 'action' ] ) && ( in_array( 'gravityforms-stripe-more/more-stripe.php', $_POST[ 'checked' ] ) || in_array( 'gravityforms-stripe-more/gravityforms-stripe-more.php', $_POST[ 'checked' ] ) ) ) {

			return;

		} else if ( class_exists( 'GFPMoreStripe' ) ) {

			deactivate_plugins( 'gravityforms-stripe-more/more-stripe.php' );
			deactivate_plugins( 'gravityforms-stripe-more/gravityforms-stripe-more.php' );

		}

	}

	/**
	 *  Make sure necessary extensions are available on server
	 *
	 * The plugin will not work without certain server extensions
	 *
	 * @since 1.8.2
	 *
	 * @uses  deactivate_plugins()
	 * @uses  __()
	 *
	 * @return bool
	 */
	public function check_server_requirements() {
		$server_requirements  = array( 'curl', 'mbstring' );
		$missing_requirements = array();
		foreach ( $server_requirements as $extension ) {
			if ( ! extension_loaded( $extension ) ) {
				$missing_requirements[ ] = $extension;
			}
		}

		if ( ! empty( $missing_requirements ) ) {
			deactivate_plugins( plugin_basename( trim( GFP_STRIPE_FILE ) ) );
			$missing_requirements = implode( ', ', $missing_requirements );
			$message              = __( "Gravity Forms + Stripe needs {$missing_requirements} to be installed on your server. Please contact your host to enable this.", 'gravity-forms-stripe' );
			die( $message );
		}
	}

	/**
	 *  Add permissions
	 *
	 * @since 0.1.0
	 *
	 * @uses  add_cap()
	 *
	 * @return void
	 */
	public function add_permissions() {
		global $wp_roles;
		$wp_roles->add_cap( 'administrator', 'gfp_stripe' );
		$wp_roles->add_cap( 'administrator', 'gfp_stripe_settings' );
		$wp_roles->add_cap( 'administrator', 'gfp_stripe_form_settings' );
		$wp_roles->add_cap( 'administrator', 'gfp_stripe_uninstall' );
	}

	/**
	 * Set option to redirect to settings page
	 *
	 * @since 1.7.9.1
	 *
	 * @uses  set_transient()
	 *
	 * @return void
	 */
	public static function set_settings_page_redirect() {
		set_transient( 'gfp_stripe_settings_page_redirect', true, HOUR_IN_SECONDS );
	}

	public function plugins_loaded() {

		$this->load_textdomain();

	}

	public function load_textdomain() {

		$gfp_stripe_lang_dir = dirname( plugin_basename( GFP_STRIPE_FILE ) ) . '/languages/';
		$gfp_stripe_lang_dir = apply_filters( 'gfp_stripe_language_dir', $gfp_stripe_lang_dir );

		$locale = apply_filters( 'plugin_locale', get_locale(), 'gravity-forms-stripe' );
		$mofile = sprintf( '%1$s-%2$s.mo', 'gravity-forms-stripe', $locale );

		$mofile_local  = $gfp_stripe_lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/gravity-forms-stripe/' . $mofile;

		if ( file_exists( $mofile_global ) ) {
			load_textdomain( 'gravity-forms-stripe', $mofile_global );
		} elseif ( file_exists( $mofile_local ) ) {
			load_textdomain( 'gravity-forms-stripe', $mofile_local );
		} else {
			load_plugin_textdomain( 'gravity-forms-stripe', false, $gfp_stripe_lang_dir );
		}
	}

	/**
	 * Plugin initialization
	 *
	 * @since 0.1.0
	 *
	 * @uses  add_action()
	 * @uses  add_filter()
	 * @uses  load_plugin_textdomain()
	 */
	public function init() {

		if ( ( ! $this->is_gravityforms_supported() ) && ( ! ( isset( $_GET[ 'action' ] ) && ( ( 'upgrade-plugin' == $_GET[ 'action' ] ) || ( 'update-selected' == $_GET[ 'action' ] ) ) ) ) ) {

			$message = __( 'Gravity Forms + Stripe requires at least Gravity Forms ' . self::$min_gravityforms_version . '.', 'gravity-forms-stripe' );

			$this->set_admin_notice( $message, 'errors' );

			add_action( 'admin_notices', array( $this, 'admin_notices' ) );

			return;
		}

		$settings       = get_option( 'gfp_stripe_settings' );
		$do_usage_stats = ! empty( $settings[ 'do_usage_stats' ] ) || ! empty( $settings[ 'do_presstrends' ] );

		self::$do_usage_stats = $do_usage_stats;

		if ( $do_usage_stats ) {
			add_action( 'gfp_stripe_usage_event', array( $this, 'gfp_stripe_usage_event' ), 1, 1 );
		}

		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'wp_loaded', array( $this, 'wp_loaded' ) );

		register_deactivation_hook( 'gravityforms/gravityforms.php', array( $this, 'deactivate_gravityforms' ) );

		add_filter( 'gform_logging_supported', array( $this, 'gform_logging_supported' ) );

		add_filter( 'gform_currency', array( $this, 'gform_currency' ) );
		add_filter( 'gform_currencies', array( $this, 'gform_currencies' ), 9 );

		add_action( 'gform_after_delete_form', array( $this, 'gform_after_delete_form' ) );

		if ( ! is_admin() ) {

			add_action( 'gform_enqueue_scripts', array( $this, 'gform_enqueue_scripts' ), 10, 2 );

			add_filter( 'gform_field_content', array( $this, 'gform_field_content' ), 10, 5 );

			add_filter( 'gform_field_validation', array( $this, 'gform_field_validation' ), 10, 4 );
			add_filter( 'gform_validation', array( $this, 'gform_validation' ), 1000, 4 );
			add_filter( 'gform_save_field_value', array( $this, 'gform_save_field_value' ), 10, 4 );
			add_filter( 'gform_entry_post_save', array( $this, 'gform_entry_post_save' ), 10, 2 );

		}
	}

	public function admin_menu() {
		if ( ( 'gf_edit_forms' == RGForms::get( 'page' ) ) && ( 'settings' == rgget( 'view' ) ) && ( 'stripe' == rgget( 'subview' ) || 'Stripe' == rgget( 'addon' ) ) && ( self::$_this->has_access( 'gfp_stripe_form_settings' ) ) ) {
			add_action( 'load-toplevel_page_gf_edit_forms', array( $this, 'load_toplevel_page_gf_edit_forms' ) );
		}
	}

	/**
	 *
	 *
	 * @since 1.7.9.1
	 *
	 * @uses  add_filter()
	 * @uses  GFP_Stripe::setup()
	 * @uses  wp_enqueue_script()
	 * @uses  GFCommon::get_base_path()
	 * @uses  RGForms::get()
	 * @uses  RGForms::add_settings_page()
	 * @uses  GFP_Stripe::get_base_url()
	 * @uses  GFCommon::get_base_path()
	 *
	 * @return void
	 *
	 */
	public function admin_init() {

		add_filter( 'plugin_action_links_' . plugin_basename( GFP_STRIPE_FILE ), array(
			self::$_this,
			'plugin_action_links'
		) );

		add_filter( 'plugin_row_meta', array( self::$_this, 'plugin_row_meta' ), 10, 2 );

		self::$_this->setup();
		self::$_this->redirect_to_settings_page();

		$settings       = get_option( 'gfp_stripe_settings' );
		$do_usage_stats = ! empty( $settings[ 'do_usage_stats' ] ) || ! empty( $settings[ 'do_presstrends' ] );
		if ( $do_usage_stats ) {
			self::$_this->do_usage_stats();
		}

		if ( function_exists( 'members_get_capabilities' ) ) {
			add_filter( 'members_get_capabilities', array( self::$_this, 'members_get_capabilities' ) );
		}

		add_filter( 'gform_enable_credit_card_field', '__return_true' );

		add_filter( 'gform_form_settings_menu', array( $this, 'gform_form_settings_menu' ), 10, 2 );

		if ( in_array( RG_CURRENT_PAGE, array( 'admin-ajax.php' ) ) ) {

			add_action( 'wp_ajax_gfp_stripe_update_feed_active', array(
				self::$_this,
				'gfp_stripe_update_feed_active'
			) );
			add_action( 'wp_ajax_gfp_select_stripe_form', array( self::$_this, 'gfp_select_stripe_form' ) );
			add_action( 'wp_ajax_gfp_stripe_updates_sign_up', array( self::$_this, 'gfp_stripe_updates_sign_up' ) );

		} else {

			switch ( RGForms::get( 'page' ) ) {

				case 'gf_settings':

					RGForms::add_settings_page( 'Stripe', array( self::$_this, 'settings_page' ) );

					add_action( 'gform_currency_setting_message', array(
						self::$_this,
						'gform_currency_setting_message'
					) );

					if ( 'Stripe' == rgget( 'subview' ) || 'Stripe' == rgget( 'addon' ) ) {

						require_once( GFCommon::get_base_path() . '/tooltips.php' );

						add_filter( 'gform_tooltips', array( self::$_this, 'gform_tooltips' ) );

						wp_enqueue_style( 'gfp_stripe_admin', trailingslashit( GFP_STRIPE_URL ) . 'css/admin.css', array(), self::$version );
						add_filter( 'gform_noconflict_styles', array( $this, 'gform_noconflict_styles' ) );
						add_filter( 'gform_noconflict_scripts', array( $this, 'gform_noconflict_scripts' ) );
					}

					break;

				case 'gf_entries':

					add_filter( 'gform_enable_entry_info_payment_details', '__return_false' );
					add_action( 'gform_entry_detail_sidebar_middle', array(
						$this,
						'gform_entry_detail_sidebar_middle'
					), 10, 2 );

					wp_enqueue_style( 'gfp_stripe_admin', trailingslashit( GFP_STRIPE_URL ) . 'css/admin.css', array(), self::$version );

					add_filter( 'gform_noconflict_styles', array( $this, 'gform_noconflict_styles' ) );

					break;

				case 'gf_edit_forms':

					require_once( GFCommon::get_base_path() . '/tooltips.php' );
					add_filter( 'gform_tooltips', array( self::$_this, 'gform_tooltips' ) );

					if ( ( 'settings' == rgget( 'view' ) ) && ( 'stripe' == rgget( 'subview' ) || 'Stripe' == rgget( 'addon' ) ) && ( self::$_this->has_access( 'gfp_stripe_form_settings' ) ) ) {

						add_action( 'gform_form_settings_page_stripe', array(
							$this,
							'gform_form_settings_page_stripe'
						) );

						wp_enqueue_style( 'gfp_stripe_admin', trailingslashit( GFP_STRIPE_URL ) . 'css/admin.css', array(), self::$version );

						add_filter( 'gform_noconflict_styles', array( $this, 'gform_noconflict_styles' ) );
						add_filter( 'gform_noconflict_scripts', array( $this, 'gform_noconflict_scripts' ) );
					}

					add_action( 'gform_field_standard_settings', array(
						$this,
						'gform_field_standard_settings'
					), 10, 2 );
					add_action( 'gform_editor_js', array( $this, 'gform_editor_js' ) );
					add_filter( 'gform_noconflict_scripts', array( $this, 'gform_noconflict_scripts' ) );

					break;
			}
		}

	}

	/**
	 * Create or update database tables.
	 *
	 * Will only run when version changes.
	 *
	 * @since 0.1.0
	 *
	 * @uses  get_option()
	 * @uses  GFP_Stripe_Data::update_table()
	 * @uses  update_option()
	 *
	 * @return void
	 */
	private function setup() {

		if ( ( $current_version = get_option( 'gfp_stripe_version' ) ) != self::$version ) {

			if ( GFForms::get_wp_option( 'gfp_stripe_version' ) != self::$version ) {

				delete_transient( 'gfp_stripe_currency' );

				delete_transient( 'gfp_stripe_usage_stats_cache_data' );

				GFP_Stripe_Data::update_table( $current_version );

				if ( ( ! empty( $current_version ) ) && ( version_compare( $current_version, '1.8.2', '<' ) ) ) {

					delete_transient( 'gfp_stripe_presstrends_cache_data' );

					$message = sprintf( __( 'You need to %supgrade your Stripe API to the latest version%s in your Stripe dashboard', 'gravity-forms-stripe' ), '<a href="https://dashboard.stripe.com/account/apikeys" target="_blank">', '</a>' );

					if ( ! file_exists( dirname( GFP_STRIPE_FILE ) . '/../gravityforms-stripe-more' ) ) {

						$message .= '<?php add_thickbox(); ?>';

						ob_start();

						include( trailingslashit( GFP_STRIPE_PATH ) . 'includes/views/update-api-message.php' );

						$message .= ob_get_contents();

						ob_end_clean();

					}

					$this->set_admin_notice( $message, 'updates' );

					delete_transient( 'gfp_stripe_settings_page_redirect' );

				}

				update_option( 'gfp_stripe_version', self::$version );

			}
		}
	}

	/**
	 *  Redirect to settings page if not activating multiple plugins at once
	 *
	 * @since 1.7.9.1
	 *
	 * @uses  get_transient()
	 * @uses  delete_transient()
	 * @uses  admin_url()
	 * @uses  wp_redirect()
	 *
	 * @return void
	 */
	public static function redirect_to_settings_page() {
		if ( true == get_transient( 'gfp_stripe_settings_page_redirect' ) ) {
			delete_transient( 'gfp_stripe_settings_page_redirect' );
			if ( ! isset( $_GET[ 'activate-multi' ] ) ) {
				wp_redirect( self_admin_url( 'admin.php?page=gf_settings&subview=Stripe' ) );
			}
		}
	}

	/**
	 * @return bool|mixed
	 */
	public static function is_gravityforms_supported() {
		if ( class_exists( 'GFCommon' ) ) {
			$is_correct_version = version_compare( GFCommon::$version, self::$min_gravityforms_version, '>=' );

			return $is_correct_version;
		} else {
			return false;
		}
	}

	/**
	 * @return bool
	 */
	public static function is_gravityforms_stripe_more_supported() {

		if ( class_exists( 'GFPMoreStripe' ) ) {

			$is_correct_version = version_compare( GFPMoreStripe::get_version(), self::$min_gravityforms_stripe_more_version, '>=' );

			return $is_correct_version;

		} else {

			return true;

		}

	}

	//------------------------------------------------------
	//------------- GENERAL ADMIN --------------------------
	//------------------------------------------------------
	/**
	 *  Output admin notices
	 *
	 * @since 1.7.9.1
	 *
	 * @uses  get_site_transient()
	 * @uses  get_transient()
	 * @uses  delete_site_transient()
	 * @uses  delete_transient()
	 *
	 * @return void
	 */
	public function admin_notices() {

		$admin_notices = function_exists( 'get_site_transient' ) ? get_site_transient( 'gfp-stripe-admin_notices' ) : get_transient( 'gfp-stripe-admin_notices' );
		if ( $admin_notices ) {
			$message = '';
			foreach ( $admin_notices as $type => $notices ) {

				if ( ( 'errors' == $type ) && ( ! $this->is_gravityforms_supported() ) ) {
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
				delete_site_transient( 'gfp-stripe-admin_notices' );
			} else {
				delete_transient( 'gfp-stripe-admin_notices' );
			}
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
	 * @param $type
	 *
	 * @return void
	 */
	private function set_admin_notice( $notice, $type ) {
		if ( function_exists( 'get_site_transient' ) ) {
			$notices = get_site_transient( 'gfp-stripe-admin_notices' );
		} else {
			$notices = get_transient( 'gfp-stripe-admin_notices' );
		}

		if ( ! is_array( $notices ) || ! array_key_exists( $type, $notices ) || ! in_array( $notice, $notices[ $type ] ) ) {
			$notices[ $type ][ ] = $notice;
		}

		if ( function_exists( 'set_site_transient' ) ) {
			set_site_transient( 'gfp-stripe-admin_notices', $notices );
		} else {
			set_transient( 'gfp-stripe-admin_notices', $notices );
		}
	}

	/**
	 * Add a link to this plugin's settings page
	 *
	 * @uses self_admin_url()
	 * @uses __()
	 *
	 * @param $links
	 *
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		return array_merge(
			array(
				'settings' => '<a href="' . self_admin_url( 'admin.php?page=gf_settings&subview=Stripe' ) . '">' . __( 'Settings', 'gravity-forms-stripe' ) . '</a>'
			),
			$links
		);
	}

	/**
	 * Add helpful gravity+ links
	 *
	 * @since 1.8.12.1
	 *
	 * @param array  $plugin_meta
	 * @param string $plugin_file
	 *
	 * @return mixed
	 */
	public function plugin_row_meta( $plugin_meta, $plugin_file ) {
		$plugin = plugin_basename( trim( GFP_STRIPE_FILE ) );

		if ( $plugin == $plugin_file ) {
			if ( ! file_exists( dirname( GFP_STRIPE_FILE ) . '/../gravityforms-stripe-more' ) ) {
				$links[ ] = sprintf( __( '%sGet important updates%s', 'gravity-forms-stripe' ), '<a href="https://gravityplus.pro/gravity-forms-stripe/updates/?utm_source=gravity-forms-stripe&utm_medium=link&utm_content=plugins-list&utm_campaign=gravity-forms-stripe" target="_blank">', '</a>' );
			}
			$links[ ]    = sprintf( __( '%sFollow on Twitter%s', 'gravity-forms-stripe' ), '<a href="https://twitter.com/gravityplus" target="_blank">', '</a>' );
			$plugin_meta = array_merge( $plugin_meta, $links );
		}

		return $plugin_meta;
	}

	/**
	 *  Disallow Gravity Forms deactivation if this plugin is still active
	 *
	 * Prevents a fatal error if this plugin is still active when user attempts to deactivate Gravity Forms
	 *
	 * @since 1.7.9.1
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
	public function deactivate_gravityforms( $network_deactivating ) {
		$plugin = plugin_basename( trim( GFP_STRIPE_FILE ) );
		if ( ( array_key_exists( 'action', $_POST ) ) && ( 'deactivate-selected' == $_POST[ 'action' ] ) && ( in_array( $plugin, $_POST[ 'checked' ] ) ) ) {
			return;
		} else if ( is_plugin_active( $plugin ) ) {
			if ( $network_deactivating ) {
				add_action( 'update_site_option_active_sitewide_plugins', array(
					$this,
					'update_site_option_active_sitewide_plugins'
				) );
			} else {
				add_action( 'update_option_active_plugins', array( $this, 'update_option_active_plugins' ) );
			}
		}
	}

	public function update_option_active_plugins() {
		remove_action( 'update_option_active_plugins', array( $this, 'update_options_active_plugins' ) );
		$plugin = plugin_basename( trim( GFP_STRIPE_FILE ) );
		deactivate_plugins( $plugin );
		update_option( 'recently_activated', array( $plugin => time() ) + (array) get_option( 'recently_activated' ) );
	}

	public function update_site_option_active_sitewide_plugins() {
		remove_action( 'update_site_option_active_sitewide_plugins', array(
			$this,
			'update_site_option_active_sitewide_plugins'
		) );
		$plugin = plugin_basename( trim( GFP_STRIPE_FILE ) );
		deactivate_plugins( $plugin );
	}

	/**
	 * Get version
	 *
	 * @since 1.8.2
	 *
	 * @return string
	 */
	public static function get_version() {
		return self::$version;
	}

	/**
	 * @param $noconflict_scripts
	 *
	 * @return array
	 */
	function gform_noconflict_scripts( $noconflict_scripts ) {

		if ( ( 'gf_settings' == RGForms::get( 'page' ) ) && ( 'Stripe' == rgget( 'subview' ) ) ) {
			$noconflict_scripts = array_merge( $noconflict_scripts, array( 'gfp_stripe_settings_page_js' ) );
		} else if ( 'gf_edit_forms' == RGForms::get( 'page' ) ) {

			if ( ( 'settings' == rgget( 'view' ) ) && ( 'stripe' == rgget( 'subview' ) ) && ( self::$_this->has_access( 'gfp_stripe_form_settings' ) ) ) {
				$noconflict_scripts = array_merge( $noconflict_scripts, array(
					'gfp_stripe_form_settings_stripe_js',
					'gfp_stripe_form_settings_edit_feed_js'
				) );
			} else {
				$noconflict_scripts = array_merge( $noconflict_scripts, array( 'gfp_stripe_form_editor_card_funding_types' ) );
			}

		}

		return $noconflict_scripts;
	}

	/**
	 * @param $noconflict_styles
	 *
	 * @return array
	 */
	function gform_noconflict_styles( $noconflict_styles ) {

		return array_merge( $noconflict_styles, array( 'gfp_stripe_admin' ) );
	}

	function load_toplevel_page_gf_edit_forms() {
		$screen = get_current_screen();

		if ( 'toplevel_page_gf_edit_forms' == $screen->id ) {
			ob_start();

			include( trailingslashit( GFP_STRIPE_PATH ) . 'includes/views/form-settings-stripe-help-sidebar.php' );
			$sidebar = ob_get_contents();
			ob_clean();

			include( trailingslashit( GFP_STRIPE_PATH ) . 'includes/views/form-settings-stripe-help-basic-setup.php' );
			$setup = ob_get_contents();
			ob_clean();

			include( trailingslashit( GFP_STRIPE_PATH ) . 'includes/views/form-settings-stripe-help-troubleshooting.php' );
			$troubleshooting = ob_get_contents();
			ob_clean();

			ob_end_clean();

			$screen->set_help_sidebar( $sidebar );

			$screen->add_help_tab( array(
				                       'id'      => 'stripe-settings-basic-setup',
				                       'title'   => __( 'Basic Setup', 'gravity-forms-stripe' ),
				                       'content' => $setup
			                       ) );

			$screen->add_help_tab( array(
				                       'id'      => 'stripe-settings-troubleshooting',
				                       'title'   => __( 'Troubleshooting', 'gravity-forms-stripe' ),
				                       'content' => $troubleshooting
			                       ) );

			do_action( 'gfp_stripe_form_settings_contextual_help', $screen );
		}
	}

	/**
	 * Remove Gravity Forms admin_print_scripts hook
	 *
	 * RGForms::print_scripts calls wp_print_scripts, causing default WordPress JS to break (such as contextual help
	 * tabs). It is unnecessary to call wp_print_scripts because it will be called automatically in admin-header.php,
	 * and on every page load in wp_head.
	 *
	 * @since 1.8.2
	 *
	 */
	function wp_loaded() {
		if ( ( 'gf_edit_forms' == RGForms::get( 'page' ) ) && ( 'settings' == rgget( 'view' ) ) && ( 'stripe' == rgget( 'subview' ) || 'Stripe' == rgget( 'addon' ) ) && ( self::$_this->has_access( 'gfp_stripe_form_settings' ) ) ) {
			remove_action( 'admin_print_scripts', array( 'RGForms', 'print_scripts' ) );
			wp_enqueue_script( 'sack' );
		}
	}

	//------------------------------------------------------
	//------------- MEMBERS PLUGIN INTEGRATION -------------
	//------------------------------------------------------
	/**
	 * Provide the Members plugin with this plugin's list of capabilities
	 *
	 * @since 0.1.0
	 *
	 * @param $caps
	 *
	 * @return array
	 */
	public function members_get_capabilities( $caps ) {
		return array_merge( $caps, array(
			'gfp_stripe',
			'gfp_stripe_settings',
			'gfp_stripe_form_settings',
			'gfp_stripe_uninstall'
		) );
	}

	/**
	 * Check if user has the required permission
	 *
	 * @since 0.1.0
	 *
	 * @uses  current_user_can()
	 *
	 * @param $required_permission
	 *
	 * @return bool|string
	 */
	public static function has_access( $required_permission ) {
		$has_members_plugin = function_exists( 'members_get_capabilities' );
		$has_access         = $has_members_plugin ? current_user_can( $required_permission ) : current_user_can( 'level_7' );
		if ( $has_access ) {
			return $has_members_plugin ? $required_permission : 'level_7';
		} else {
			return false;
		}
	}

	//------------------------------------------------------
	//------------- CURRENCY --------------------------
	//------------------------------------------------------
	/**
	 * Get the currency or currencies supported by the Stripe account
	 *
	 * @since
	 *
	 * @uses get_transient()
	 * @uses GFP_Stripe::include_api()
	 * @uses GFP_Stripe::get_api_key()
	 * @uses PPP\Stripe\Account::retrieve()
	 * @uses set_transient()
	 *
	 * @param $currency
	 *
	 * @return mixed
	 */
	public function gform_currency( $currency ) {

		$stripe_currency = get_transient( 'gfp_stripe_currency' );

		if ( false === $stripe_currency ) {

			self::$_this->include_api();

			$api_key = self::$_this->get_api_key( 'secret' );

			if ( ! empty( $api_key ) ) {

				PPP\Stripe\Stripe::setApiKey( $api_key );

				try {
					$account = PPP\Stripe\Account::retrieve();
				} catch ( Exception $e ) {
					$error_message = GFP_Stripe::gfp_stripe_create_error_message( $e );
					GFP_Stripe::log_error( "Unable to retrieve account to get default Stripe currency: {$error_message}" );

					return $currency;
				}

				$stripe_default_currency = strtoupper( $account[ 'default_currency' ] );

				$stripe_account_country = strtoupper( $account[ 'country' ] );

				$stripe_country_spec = PPP\Stripe\CountrySpec::retrieve( $stripe_account_country );

				$stripe_currencies_supported = array_map( 'strtoupper', $stripe_country_spec[ 'supported_payment_currencies' ] );

				set_transient( 'gfp_stripe_currency',
				               array(
					               'default'   => $stripe_default_currency,
					               'supported' => $stripe_currencies_supported
				               ),
				               24 * HOUR_IN_SECONDS );

				if ( ( $stripe_default_currency !== $currency ) && ( ! in_array( $currency, $stripe_currencies_supported ) ) ) {
					$currency = $stripe_default_currency;
				}

			} else {

				update_option( 'rg_gforms_currency', 'USD' );
				$currency = 'USD';

			}

		} else if ( ! in_array( $currency, $stripe_currency[ 'supported' ] ) ) {
			$currency = $stripe_currency[ 'default' ];
		}

		return $currency;
	}

	/**
	 * Currencies supported by Stripe account
	 *
	 * @since 1.7.9.1
	 *
	 * @uses  get_transient()
	 * @uses  GFP_Stripe::include_api()
	 * @uses  GFP_Stripe::get_api_key()
	 * @uses  PPP\Stripe\Stripe::setApiKey()
	 * @uses  PPP\Stripe\Account::retrieve()
	 * @uses  set_transient()
	 *
	 * @param $currencies
	 *
	 * @return array
	 */
	public function gform_currencies( $currencies ) {

		$currencies = array_merge( $currencies, $this->all_currencies() );

		$current_currency = get_transient( 'gfp_stripe_currency' );

		if ( false === $current_currency ) {

			self::$_this->include_api();

			$api_key = self::$_this->get_api_key( 'secret' );

			if ( ! empty( $api_key ) ) {
				PPP\Stripe\Stripe::setApiKey( $api_key );

				try {
					$account = PPP\Stripe\Account::retrieve();
				} catch ( Exception $e ) {
					$error_message = GFP_Stripe::gfp_stripe_create_error_message( $e );
					GFP_Stripe::log_error( "Unable to retrieve account to get currencies: {$error_message}" );

					return $currencies;
				}

				$default_currency = strtoupper( $account[ 'default_currency' ] );

				$stripe_account_country = strtoupper( $account[ 'country' ] );

				$stripe_country_spec = PPP\Stripe\CountrySpec::retrieve( $stripe_account_country );

				$currencies_supported = array_map( 'strtoupper', $stripe_country_spec[ 'supported_payment_currencies' ] );

				set_transient( 'gfp_stripe_currency',
				               array(
					               'default'   => $default_currency,
					               'supported' => $currencies_supported
				               ),
				               24 * HOUR_IN_SECONDS );

			}

		}

		if ( ( ! empty( $current_currency ) ) || ( ! empty( $default_currency ) ) && ( ! empty( $currencies_supported ) ) ) {

			$new_currencies_list = array_intersect_key( $currencies, ( $current_currency ) ? array_flip( $current_currency[ 'supported' ] ) : array_flip( $currencies_supported ) );

			if ( ! empty( $new_currencies_list ) ) {

				ksort( $new_currencies_list );

				$currencies = $new_currencies_list;

			}

		}

		return $currencies;
	}

	/**
	 * Currency setting message
	 *
	 * @since
	 *
	 * @uses GFP_Stripe::get_api_key()
	 * @uses GFCommon::get_currency()
	 * @uses RGCurrency::get_currency()
	 * @uses __()
	 *
	 * @return void
	 */
	public function gform_currency_setting_message() {
		$api_key = self::$_this->get_api_key( 'secret' );
		if ( ! empty( $api_key ) ) {
			if ( ! class_exists( 'RGCurrency' ) ) {
				require_once( GFCommon::get_base_path() . '/currency.php' );
			}
			$currency_name = RGCurrency::get_currency( GFCommon::get_currency() );
			$currency_name = $currency_name[ 'name' ];
			echo '<div class=\'gform_currency_message\'>' . __( "Your Stripe account allows these currencies.", 'gravity-forms-stripe' ) . '</div>';
		} else {
			echo '<div class=\'gform_currency_message\'>' . sprintf( __( "Your %sStripe settings%s are not filled in -- using default currency.", 'gravity-forms-stripe' ), '<a href="admin.php?page=gf_settings&addon=Stripe">', '</a>' ) . '</div>';
		}
	}

	public function all_currencies() {
		return array(
			'USD' => array(
				'name'               => __( 'United States Dollar', 'gravity-forms-stripe' ),
				'symbol_left'        => '$',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'AED' => array(
				'name'               => __( 'United Arab Emirates Dirham', 'gravity-forms-stripe' ),
				'symbol_left'        => '&#1583;.&#1573;',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'AFN' => array(
				'name'               => __( 'Afghan Afghani', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => '&#1547;',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'ALL' => array(
				'name'               => __( 'Albanian Lek', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'L',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'AMD' => array(
				'name'               => __( 'Armenian Dram', 'gravity-forms-stripe' ),
				'symbol_left'        => 'AMD',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'ANG' => array(
				'name'               => __( 'Netherlands Antillean Gulden', 'gravity-forms-stripe' ),
				'symbol_left'        => '&#402;',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'AOA' => array(
				'name'               => __( 'Angolan Kwanza', 'gravity-forms-stripe' ),
				'symbol_left'        => 'Kz',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'ARS' => array(
				'name'               => __( 'Argentine Peso', 'gravity-forms-stripe' ),
				'symbol_left'        => 'ARS$',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'AUD' => array(
				'name'               => __( 'Australian Dollar', 'gravity-forms-stripe' ),
				'symbol_left'        => 'A$',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'AWG' => array(
				'name'               => __( 'Aruban Florin', 'gravity-forms-stripe' ),
				'symbol_left'        => '&#402;',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'AZN' => array(
				'name'               => __( 'Azerbaijani Manat', 'gravity-forms-stripe' ),
				'symbol_left'        => '&#1084;&#1072;&#1085;',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'BAM' => array(
				'name'               => __( 'Bosnia & Herzegovina Convertible Mark', 'gravity-forms-stripe' ),
				'symbol_left'        => 'KM',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'BBD' => array(
				'name'               => __( 'Barbadian Dollar', 'gravity-forms-stripe' ),
				'symbol_left'        => 'Bbd$',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'BDT' => array(
				'name'               => __( 'Bangladeshi Taka', 'gravity-forms-stripe' ),
				'symbol_left'        => '&#2547;',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'BGN' => array(
				'name'               => __( 'Bulgarian Lev', 'gravity-forms-stripe' ),
				'symbol_left'        => '&#1083;&#1074;',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'BIF' => array(
				'name'               => __( 'Burundian Franc', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'BIF',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 0,
				'american_express'   => true
			),
			'BMD' => array(
				'name'               => __( 'Bermudian Dollar', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'BMD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'BND' => array(
				'name'               => __( 'Brunei Dollar', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'BND',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'BOB' => array(
				'name'               => __( 'Bolivian Boliviano', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'BOB',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'BRL' => array(
				'name'               => __( 'Brazilian Real', 'gravity-forms-stripe' ),
				'symbol_left'        => 'R$',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'BSD' => array(
				'name'               => __( 'Bahamian Dollar', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'BSD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'BWP' => array(
				'name'               => __( 'Botswana Pula', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'BWP',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'BZD' => array(
				'name'               => __( 'Belize Dollar', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'BZP',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'CAD' => array(
				'name'               => __( 'Canadian Dollar', 'gravity-forms-stripe' ),
				'symbol_left'        => 'CAD$',
				'symbol_right'       => 'CAD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'CDF' => array(
				'name'               => __( 'Congolese Franc', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'CDF',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'CHF' => array(
				'name'               => __( 'Swiss Franc', 'gravity-forms-stripe' ),
				'symbol_left'        => 'Fr',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => "'",
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'CLP' => array(
				'name'               => __( 'Chilean Peso', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'CLP',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 0,
				'american_express'   => false
			),
			'CNY' => array(
				'name'               => __( 'Chinese Renminbi Yuan', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'CNY',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'COP' => array(
				'name'               => __( 'Colombian Peso', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'COP',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'CRC' => array(
				'name'               => __( 'Costa Rican Colón', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'CRC',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'CVE' => array(
				'name'               => __( 'Cape Verdean Escudo', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'CVE',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'CZK' => array(
				'name'               => __( 'Czech Koruna', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => '&#75;&#269;',
				'symbol_padding'     => ' ',
				'thousand_separator' => ' ',
				'decimal_separator'  => ',',
				'decimals'           => 2,
				'american_express'   => false
			),
			'DJF' => array(
				'name'               => __( 'Djiboutian Franc', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'DJF',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 0,
				'american_express'   => false
			),
			'DKK' => array(
				'name'               => __( 'Danish Krone', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'kr.',
				'symbol_padding'     => ' ',
				'thousand_separator' => '.',
				'decimal_separator'  => ',',
				'decimals'           => 2,
				'american_express'   => true
			),
			'DOP' => array(
				'name'               => __( 'Dominican Peso', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'DOP',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'DZD' => array(
				'name'               => __( 'Algerian Dinar', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'DZD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'EEK' => array(
				'name'               => __( 'Estonian Kroon', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'EEK',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'EGP' => array(
				'name'               => __( 'Egyptian Pound', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'EGP',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'ETB' => array(
				'name'               => __( 'Ethiopian Birr', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'ETB',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'EUR' => array(
				'name'               => __( 'Euro', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => '&#8364;',
				'symbol_padding'     => '',
				'thousand_separator' => '.',
				'decimal_separator'  => ',',
				'decimals'           => 2,
				'american_express'   => true
			),
			'FJD' => array(
				'name'               => __( 'Fijian Dollar', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'FJD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'FKP' => array(
				'name'               => __( 'Falkland Islands Pound', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'FKP',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'GBP' => array(
				'name'               => __( 'British Pound', 'gravity-forms-stripe' ),
				'symbol_left'        => '&#163;',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'GEL' => array(
				'name'               => __( 'Georgian Lari', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'GEL',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'GIP' => array(
				'name'               => __( 'Gibraltar Pound', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'GIP',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'GMD' => array(
				'name'               => __( 'Gambian Dalasi', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'GMD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'GNF' => array(
				'name'               => __( 'Guinean Franc', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'GNF',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 0,
				'american_express'   => false
			),
			'GTQ' => array(
				'name'               => __( 'Guatemalan Quetzal', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'GTQ',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'GYD' => array(
				'name'               => __( 'Guyanese Dollar', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'GYD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'HKD' => array(
				'name'               => __( 'Hong Kong Dollar', 'gravity-forms-stripe' ),
				'symbol_left'        => 'HK$',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'HNL' => array(
				'name'               => __( 'Honduran Lempira', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'HNL',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'HRK' => array(
				'name'               => __( 'Croatian Kuna', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'HRK',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'HTG' => array(
				'name'               => __( 'Haitian Gourde', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'HTG',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'HUF' => array(
				'name'               => __( 'Hungarian Forint', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'Ft',
				'symbol_padding'     => ' ',
				'thousand_separator' => '.',
				'decimal_separator'  => ',',
				'decimals'           => 2,
				'american_express'   => true
			),
			'IDR' => array(
				'name'               => __( 'Indonesian Rupiah', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'IDR',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'ILS' => array(
				'name'               => __( 'Israeli New Sheqel', 'gravity-forms-stripe' ),
				'symbol_left'        => '&#8362;',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'INR' => array(
				'name'               => __( 'Indian Rupee', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'INR',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'ISK' => array(
				'name'               => __( 'Icelandic Króna', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'ISK',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'JMD' => array(
				'name'               => __( 'Jamaican Dollar', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'JMD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'JPY' => array(
				'name'               => __( 'Japanese Yen', 'gravity-forms-stripe' ),
				'symbol_left'        => '&#165;',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '',
				'decimals'           => 0,
				'american_express'   => true
			),
			'KES' => array(
				'name'               => __( 'Kenyan Shilling', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'KES',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'KGS' => array(
				'name'               => __( 'Kyrgyzstani Som', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'KGS',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'KHR' => array(
				'name'               => __( 'Cambodian Riel', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'KHR',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'KMF' => array(
				'name'               => __( 'Comorian Franc', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'KMF',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 0,
				'american_express'   => true
			),
			'KRW' => array(
				'name'               => __( 'South Korean Won', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'KRW',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 0,
				'american_express'   => true
			),
			'KYD' => array(
				'name'               => __( 'Cayman Islands Dollar', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'KYD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'KZT' => array(
				'name'               => __( 'Kazakhstani Tenge', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'KZT',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'LAK' => array(
				'name'               => __( 'Lao Kip', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'LAK',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'LBP' => array(
				'name'               => __( 'Lebanese Pound', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'LBP',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'LKR' => array(
				'name'               => __( 'Sri Lankan Rupee', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'LKR',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'LRD' => array(
				'name'               => __( 'Liberian Dollar', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'LRD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'LSL' => array(
				'name'               => __( 'Lesotho Loti', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'LSL',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'LTL' => array(
				'name'               => __( 'Lithuanian Litas', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'LTL',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'LVL' => array(
				'name'               => __( 'Latvian Lats', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'LVL',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'MAD' => array(
				'name'               => __( 'Moroccan Dirham', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'MAD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'MDL' => array(
				'name'               => __( 'Moldovan Leu', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'MDL',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'MGA' => array(
				'name'               => __( 'Malagasy Ariary', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'MGA',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 0,
				'american_express'   => true
			),
			'MKD' => array(
				'name'               => __( 'Macedonian Denar', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'MKD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'MNT' => array(
				'name'               => __( 'Mongolian Tögrög', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'MNT',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'MOP' => array(
				'name'               => __( 'Macanese Pataca', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'MOP',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'MRO' => array(
				'name'               => __( 'Mauritanian Ouguiya', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'MRO',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'MUR' => array(
				'name'               => __( 'Mauritian Rupee', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'MUR',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'MVR' => array(
				'name'               => __( 'Maldivian Rufiyaa', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'MVR',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'MWK' => array(
				'name'               => __( 'Malawian Kwacha', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'MWK',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'MXN' => array(
				'name'               => __( 'Mexican Peso', 'gravity-forms-stripe' ),
				'symbol_left'        => 'MXN$',
				'symbol_right'       => 'MXN',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'MYR' => array(
				'name'               => __( 'Malaysian Ringgit', 'gravity-forms-stripe' ),
				'symbol_left'        => '&#82;&#77;',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'MZN' => array(
				'name'               => __( 'Mozambican Metical', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'MZN',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'NAD' => array(
				'name'               => __( 'Namibian Dollar', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'NAD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'NGN' => array(
				'name'               => __( 'Nigerian Naira', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'NGN',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'NIO' => array(
				'name'               => __( 'Nicaraguan Córdoba', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'NIO',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'NOK' => array(
				'name'               => __( 'Norwegian Krone', 'gravity-forms-stripe' ),
				'symbol_left'        => 'Kr',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'NPR' => array(
				'name'               => __( 'Nepalese Rupee', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'NPR',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'NZD' => array(
				'name'               => __( 'New Zealand Dollar', 'gravity-forms-stripe' ),
				'symbol_left'        => 'NZ$',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'PAB' => array(
				'name'               => __( 'Panamanian Balboa', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'PAB',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'PEN' => array(
				'name'               => __( 'Peruvian Nuevo Sol', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'PEN',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'PGK' => array(
				'name'               => __( 'Papua New Guinean Kina', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'PGK',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'PHP' => array(
				'name'               => __( 'Philippine Peso', 'gravity-forms-stripe' ),
				'symbol_left'        => '&#8369;',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'PKR' => array(
				'name'               => __( 'Pakistani Rupee', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'PKR',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'PLN' => array(
				'name'               => __( 'Polish Złoty', 'gravity-forms-stripe' ),
				'symbol_left'        => '&#122;&#322;',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => '.',
				'decimal_separator'  => ',',
				'decimals'           => 2,
				'american_express'   => true
			),
			'PYG' => array(
				'name'               => __( 'Paraguayan Guaraní', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'PYG',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 0,
				'american_express'   => false
			),
			'QAR' => array(
				'name'               => __( 'Qatari Riyal', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'QAR',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'RON' => array(
				'name'               => __( 'Romanian Leu', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'RON',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'RSD' => array(
				'name'               => __( 'Serbian Dinar', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'RSD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'RUB' => array(
				'name'               => __( 'Russian Ruble', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'RUB',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'RWF' => array(
				'name'               => __( 'Rwandan Franc', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'RWF',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 0,
				'american_express'   => true
			),
			'SAR' => array(
				'name'               => __( 'Saudi Riyal', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'SAR',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'SBD' => array(
				'name'               => __( 'Solomon Islands Dollar', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'SBD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'SCR' => array(
				'name'               => __( 'Seychellois Rupee', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'SCR',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'SEK' => array(
				'name'               => __( 'Swedish Krona', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'kr',
				'symbol_padding'     => ' ',
				'thousand_separator' => ' ',
				'decimal_separator'  => ',',
				'decimals'           => 2,
				'american_express'   => true
			),
			'SGD' => array(
				'name'               => __( 'Singapore Dollar', 'gravity-forms-stripe' ),
				'symbol_left'        => 'S$',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'SHP' => array(
				'name'               => __( 'Saint Helenian Pound', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'SHP',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'SLL' => array(
				'name'               => __( 'Sierra Leonean Leone', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'SLL',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'SOS' => array(
				'name'               => __( 'Somali Shilling', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'SOS',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'SRD' => array(
				'name'               => __( 'Surinamese Dollar', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'SRD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'STD' => array(
				'name'               => __( 'São Tomé and Príncipe Dobra', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'STD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'SVC' => array(
				'name'               => __( 'Salvadoran Colón', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'SVC',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'SZL' => array(
				'name'               => __( 'Swazi Lilangeni', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'SZL',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'THB' => array(
				'name'               => __( 'Thai Baht', 'gravity-forms-stripe' ),
				'symbol_left'        => '&#3647;',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'TJS' => array(
				'name'               => __( 'Tajikistani Somoni', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'TJS',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'TOP' => array(
				'name'               => __( 'Tongan Paʻanga', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'TOP',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'TRY' => array(
				'name'               => __( 'Turkish Lira', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'TRY',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'TTD' => array(
				'name'               => __( 'Trinidad and Tobago Dollar', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'TTD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'TWD' => array(
				'name'               => __( 'New Taiwan Dollar', 'gravity-forms-stripe' ),
				'symbol_left'        => 'NT$',
				'symbol_right'       => '',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'TZS' => array(
				'name'               => __( 'Tanzanian Shilling', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'TZS',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'UAH' => array(
				'name'               => __( 'Ukrainian Hryvnia', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'UAH',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'UGX' => array(
				'name'               => __( 'Ugandan Shilling', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'UGX',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'UYU' => array(
				'name'               => __( 'Uruguayan Peso', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'UYU',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'UZS' => array(
				'name'               => __( 'Uzbekistani Som', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'UZS',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'VEF' => array(
				'name'               => __( 'Venezuelan Bolívar', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'VEF',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => false
			),
			'VND' => array(
				'name'               => __( 'Vietnamese Đồng', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'VND',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'VUV' => array(
				'name'               => __( 'Vanuatu Vatu', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'VUV',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 0,
				'american_express'   => true
			),
			'WST' => array(
				'name'               => __( 'Samoan Tala', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'WST',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'XAF' => array(
				'name'               => __( 'Central African Cfa Franc', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'XAF',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 0,
				'american_express'   => true
			),
			'XCD' => array(
				'name'               => __( 'East Caribbean Dollar', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'XCD',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'XOF' => array(
				'name'               => __( 'West African Cfa Franc', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'XOF',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 0,
				'american_express'   => false
			),
			'XPF' => array(
				'name'               => __( 'Cfp Franc', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'XPF',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 0,
				'american_express'   => false
			),
			'YER' => array(
				'name'               => __( 'Yemeni Rial', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'YER',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'ZAR' => array(
				'name'               => __( 'South African Rand', 'gravity-forms-stripe' ),
				'symbol_left'        => '',
				'symbol_right'       => 'ZAR',
				'symbol_padding'     => ' ',
				'thousand_separator' => ',',
				'decimal_separator'  => '.',
				'decimals'           => 2,
				'american_express'   => true
			),
			'ZMW' => array(
				'name'               => __( 'Zambian Kwacha', 'gravity-forms-stripe' ),
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

	//------------------------------------------------------
	//------------- SETTINGS PAGE --------------------------
	//------------------------------------------------------

	/**
	 * Render settings page
	 *
	 * @since 0.1.0
	 *
	 * @uses  check_admin_referer()
	 * @uses  GFP_Stripe::uninstall()
	 * @uses  _e()
	 * @uses  rgpost()
	 * @uses  apply_filters()
	 * @uses  update_option()
	 * @uses  get_option()
	 * @uses  delete_option()
	 * @uses  has_filter()
	 * @uses  wp_nonce_field()
	 * @uses  gform_tooltip()
	 * @uses  GFPMoreStripe::get_slug()
	 * @uses  GFPMoreStripe::get_version()
	 * @uses  GFPMoreStripeUpgrade::get_version_info()
	 * @uses  GFCommon::get_base_url()
	 * @uses  rgar()
	 * @uses  esc_attr()
	 * @uses  GFP_Stripe::get_base_url()
	 * @uses  do_action()
	 * @uses  GFCommon::current_user_can_any()
	 * @uses  __()
	 *
	 * @return void
	 */
	public function settings_page() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_script( 'gfp_stripe_settings_page_js', trailingslashit( GFP_STRIPE_URL ) . "js/settings-page{$suffix}.js", array( 'jquery' ), GFP_Stripe::get_version() );
		$js_vars = array(
			'baseURL'             => GFCommon::get_base_url(),
			'status_message'      => __( 'Signing Up...', 'gravity-forms-stripe' ),
			'nonce'               => wp_create_nonce( 'gfp_stripe_updates_sign_up' ),
			'blank_email_message' => __( 'Please enter an email address', 'gravity-forms-stripe' ),
			'success_message'     => __( 'All Set!', 'gravity-forms-stripe' )
		);
		wp_localize_script( 'gfp_stripe_settings_page_js', 'gfp_stripe_settings_page_vars', $js_vars );

		if ( isset( $_POST[ 'uninstall' ] ) ) {
			check_admin_referer( 'uninstall', 'gfp_stripe_uninstall' );
			$this->uninstall();

			?>
			<div class="updated fade"
			     style="padding:20px;"><?php echo sprintf( __( "Gravity Forms Stripe Add-On has been successfully uninstalled. It can be re-activated from the %splugins page%s.", 'gravity-forms-stripe' ), "<a href='plugins.php'>", "</a>" ); ?></div>
			<?php
			return;
		} else if ( isset( $_POST[ 'gfp_stripe_submit' ] ) ) {
			check_admin_referer( 'update', 'gfp_stripe_update' );
			$settings = array(
				'test_secret_key'      => trim( rgpost( 'gfp_stripe_test_secret_key' ) ),
				'test_publishable_key' => trim( rgpost( 'gfp_stripe_test_publishable_key' ) ),
				'live_secret_key'      => trim( rgpost( 'gfp_stripe_live_secret_key' ) ),
				'live_publishable_key' => trim( rgpost( 'gfp_stripe_live_publishable_key' ) ),
				'mode'                 => rgpost( 'gfp_stripe_mode' ),
				'do_usage_stats'       => rgpost( 'gfp_stripe_do_usage_stats' )
			);
			$settings = apply_filters( 'gfp_stripe_save_settings', $settings );


			update_option( 'gfp_stripe_settings', $settings );
			$usage_event_option = get_option( 'gfp_stripe_usage_events' );
			if ( empty( $usage_event_option ) ) {
				add_option( 'gfp_stripe_usage_events' );
			}

			$gfp_support_key = get_option( 'gfp_support_key' );
			$key             = rgpost( 'gfp_support_key' );
			if ( empty( $key ) ) {
				delete_option( 'gfp_support_key' );
			} else if ( $gfp_support_key != $key ) {
				$key = md5( trim( $key ) );
				update_option( 'gfp_support_key', $key );
			}

			delete_transient( 'gfp_stripe_currency' );
			if ( ! empty( $settings[ 'test_secret_key' ] ) ) {
				update_option( 'rg_gforms_currency', $this->gform_currency( get_option( 'rg_gforms_currency' ) ) );
			}
		} else if ( has_filter( 'gfp_stripe_settings_page_action' ) ) {
			$do_return = '';
			$do_return = apply_filters( 'gfp_stripe_settings_page_action', $do_return );
			if ( $do_return ) {
				return;
			}
		}

		$settings        = get_option( 'gfp_stripe_settings' );
		$gfp_support_key = get_option( 'gfp_support_key' );
		if ( ! empty( $settings ) ) {
			$is_valid = $this->is_valid_key();
		} else {
			$is_valid = array();
		}

		?>

		<form method="post" action="">
			<?php wp_nonce_field( 'update', 'gfp_stripe_update' ) ?>

			<h3><span class="icon-stripe"></span><?php _e( ' Stripe Settings', 'gravity-forms-stripe' ); ?></h3>

			<!--<div class="hr-divider"></div>-->
			<table id="support-license-key" class="form-table">
				<tbody>
				<tr valign="top">
					<th scope="row">
						<label
							for="gfp_support_key"><?php _e( "gravity+ Support License Key", "gfp-stripe" ); ?></label> <?php gform_tooltip( 'stripe_support_license_key' ) ?>
					</th>
					<td>
						<?php

						$key_field    = '<input type="password" ' . ( class_exists( 'GFPMoreStripeUpgrade' ) ? '' : 'disabled' ) . ' name="gfp_support_key" id="gfp_support_key" value="' . ( empty( $gfp_support_key ) ? '' : $gfp_support_key ) . '" />';
						$valid_icon   = "&nbsp;<span class='dashicons dashicons-yes gf_keystatus_valid valid_credentials' alt='valid key' title='valid key'></span>";
						$invalid_icon = "&nbsp;<span class='dashicons dashicons-no gf_keystatus_invalid invalid_credentials' alt='invalid key' title='invalid key'></span>";
						if ( class_exists( 'GFPMoreStripeUpgrade' ) ) {
							$version_info = GFPMoreStripeUpgrade::get_version_info( GFPMoreStripe::get_slug(), $gfp_support_key, GFPMoreStripe::get_version(), ( isset( $_POST[ "gfp_stripe_submit" ] ) ? false : true ) );
							if ( $version_info[ 'is_valid_key' ] ) {
								$key_field .= $valid_icon;
							} else if ( ! empty( $gfp_support_key ) ) {
								$key_field .= $invalid_icon;
							}
						}
						echo $key_field;
						?>
						<br/>
						<?php echo sprintf( __( "The license key is used for access to %s+(More) Stripe%s automatic upgrades and support. Activate +(More) Stripe to enter your license key.", 'gravity-forms-stripe' ), "<a href='https://gravityplus.pro' target='_blank'>", "</a>" ); ?>
					</td>
				</tr>
				</tbody>
			</table>

			<div class="settings-section account-information setup" data-toggle="account-information-settings">
				<h3>
					<span><i class="fa fa-plus-square-o"></i>
					<i class="fa fa-plus-square"></i>
						<i class="fa fa-minus-square"></i>
					</span>
					<?php _e( 'Stripe Account Information', 'gravity-forms-stripe' ) ?></h3>
			</div>
			<div class="account-information-settings hidden">
				<p style="text-align: left;">
					<?php echo sprintf( __( "Stripe is a payment gateway for merchants. Use Gravity Forms to collect payment information and automatically integrate to your client's Stripe account. If you don't have a Stripe account, you can %ssign up for one here%s", 'gravity-forms-stripe' ), "<a href='http://www.stripe.com' target='_blank'>", "</a>" ) ?>
				</p>
				<table class="form-table">

					<tr>
						<th scope="row" nowrap="nowrap"><label
								for="gfp_stripe_mode"><?php _e( 'API Mode', 'gravity-forms-stripe' ); ?> <?php gform_tooltip( 'stripe_api' ) ?></label>
						</th>
						<td width="88%">
							<input type="radio" name="gfp_stripe_mode" id="gfp_stripe_mode_live"
							       value="live" <?php echo rgar( $settings, 'mode' ) != 'test' ? "checked='checked'" : '' ?>/>
							<label class="inline"
							       for="gfp_stripe_mode_live"><?php _e( 'Live', 'gravity-forms-stripe' ); ?></label>
							&nbsp;&nbsp;&nbsp; <input type="radio" name="gfp_stripe_mode" id="gfp_stripe_mode_test"
							                          value="test" <?php echo 'test' == rgar( $settings, 'mode' ) ? "checked='checked'" : '' ?>/>
							<label class="inline"
							       for="gfp_stripe_mode_test"><?php _e( 'Test', 'gravity-forms-stripe' ); ?></label>
						</td>
					</tr>
					<tr>
						<td colspan='2'>
							<p><?php echo sprintf( __( "You can find your <strong>Stripe API keys</strong> needed below in your Stripe dashboard 'Account Settings' %shere%s", 'gravity-forms-stripe' ), "<a href='https://dashboard.stripe.com/account/apikeys' target='_blank'>", "</a>" ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row" nowrap="nowrap"><label
								for="gfp_stripe_test_secret_key"><?php _e( 'Test Secret Key', 'gravity-forms-stripe' ); ?> <?php gform_tooltip( 'stripe_test_secret_key' ) ?></label>
						</th>
						<td width="88%">
							<input class="size-1" id="gfp_stripe_test_secret_key" name="gfp_stripe_test_secret_key"
							       value="<?php echo trim( esc_attr( rgar( $settings, 'test_secret_key' ) ) ) ?>"/>
							<?php echo ( array_key_exists( 1, $is_valid ) ) ? ( $is_valid[ 1 ][ 'test_secret_key' ] ? $valid_icon : $invalid_icon ) : $invalid_icon ?>
							<br/>
						</td>
					</tr>
					<tr>
						<th scope="row" nowrap="nowrap"><label
								for="gfp_stripe_test_publishable_key"><?php _e( 'Test Publishable Key', 'gravity-forms-stripe' ); ?> <?php gform_tooltip( 'stripe_test_publishable_key' ) ?></label>
						</th>
						<td width="88%">
							<input class="size-1" id="gfp_stripe_test_publishable_key"
							       name="gfp_stripe_test_publishable_key"
							       value="<?php echo trim( esc_attr( rgar( $settings, 'test_publishable_key' ) ) ) ?>"/>
							<?php echo ( array_key_exists( 1, $is_valid ) ) ? ( $is_valid[ 1 ][ 'test_publishable_key' ] ? $valid_icon : $invalid_icon ) : $invalid_icon ?>
							<br/>
						</td>
					</tr>
					<tr>
						<th scope="row" nowrap="nowrap"><label
								for="gfp_stripe_live_secret_key"><?php _e( 'Live Secret Key', 'gravity-forms-stripe' ); ?> <?php gform_tooltip( 'stripe_live_secret_key' ) ?></label>
						</th>
						<td width="88%">
							<input class="size-1" id="gfp_stripe_live_secret_key" name="gfp_stripe_live_secret_key"
							       value="<?php echo trim( esc_attr( rgar( $settings, 'live_secret_key' ) ) ) ?>"/>
							<?php echo ( array_key_exists( 1, $is_valid ) ) ? ( $is_valid[ 1 ][ 'live_secret_key' ] ? $valid_icon : $invalid_icon ) : $invalid_icon ?>
							<?php
							if ( array_key_exists( 1, $is_valid ) && ! $is_valid[ 1 ][ 'live_secret_key' ] && array_key_exists( 2, $is_valid ) && ! empty( $is_valid[ 2 ] ) ) {
								if ( 'PPP\Stripe\Error\InvalidRequest' == $is_valid[ 2 ][ 'live_secret_key' ][ 0 ] ) {
									?>
									<span class="invalid_credentials invalid_key_error">*You must activate your Stripe account to use this key</span>
								<?php
								} else {
									?>
									<span
										class="invalid_credentials invalid_key_error"><?php echo "{$is_valid[2]['live_secret_key'][0]}: {$is_valid[2]['live_secret_key'][1]}"; ?></span>
								<?php
								}
							}
							?>
							<br/>
						</td>
					</tr>
					<tr>
						<th scope="row" nowrap="nowrap"><label
								for="gfp_stripe_live_publishable_key"><?php _e( 'Live Publishable Key', 'gravity-forms-stripe' ); ?> <?php gform_tooltip( 'stripe_live_publishable_key' ) ?></label>
						</th>
						<td width="88%">
							<input class="size-1" id="gfp_stripe_live_publishable_key"
							       name="gfp_stripe_live_publishable_key"
							       value="<?php echo trim( esc_attr( rgar( $settings, 'live_publishable_key' ) ) ) ?>"/>
							<?php echo ( array_key_exists( 1, $is_valid ) ) ? ( $is_valid[ 1 ][ 'live_publishable_key' ] ? $valid_icon : $invalid_icon ) : $invalid_icon ?>
							<?php
							if ( array_key_exists( 1, $is_valid ) && ! $is_valid[ 1 ][ 'live_publishable_key' ] && array_key_exists( 2, $is_valid ) && ! empty( $is_valid[ 2 ] ) ) {
								if ( 'PPP\Stripe\Error\InvalidRequest' == $is_valid[ 2 ][ 'live_publishable_key' ][ 0 ] ) {
									?>
									<span class="invalid_credentials invalid_key_error">*You must activate your Stripe account to use this key</span>
								<?php
								} else {
									?>
									<span
										class="invalid_credentials invalid_key_error"><?php echo "{$is_valid[2]['live_publishable_key'][0]}: {$is_valid[2]['live_publishable_key'][1]}"; ?></span>
								<?php
								}
							}
							?>
							<br/>
						</td>
					</tr>
				</table>
				<br/>

				<div class="push-alert-green">
					<p>
					<span class="strong">
						<?php _e( "Broken and difficult to use plugins suck! ", 'gravity-forms-stripe' );
						?>
					</span>
						<?php _e( "But that doesn't have to be your experience. ", 'gravity-forms-stripe' );
						?>
					</p>

					<p>
					<span class="strong">
						<?php printf( __( 'Enable completely anonymous usage stats,', 'gravity-forms-stripe' ) );
						?>
					</span>
						<?php _e( "so I know which themes, plugins,
				and configurations to test with to keep your site working!", 'gravity-forms-stripe' );
						?>
					</p>
				</div>
				<table class="form-table">
					<tr>
						<td width="88%">
							<input type="checkbox" name="gfp_stripe_do_usage_stats" id="gfp_stripe_do_usage_stats"
							       value="true" <?php checked( true, rgar( $settings, 'do_usage_stats' ) || rgar( $settings, 'do_presstrends' ), true ) ?>/>
							<label class="inline"
							       for="gfp_stripe_do_usage_stats"><?php _e( 'Yes, I want to make sure this plugin works on my site (and <span style="color: #07a501; font-weight: bold;">receive 10% off any gravity+ service</span>).', 'gravity-forms-stripe' ); ?></label>
							&nbsp;&nbsp;&nbsp;
						</td>
					</tr>
				</table>
			</div>
			<?php
			do_action( 'gfp_stripe_settings_page', $settings );
			?>
			<p class="submit" style="text-align: left;">
				<input type="submit" name="gfp_stripe_submit" class="button-primary"
				       value="<?php _e( 'Save Settings', 'gravity-forms-stripe' ) ?>"/>
			</p>
		</form>
		<?php if ( ! class_exists( 'GFPMoreStripe' ) ) { ?>
			<div class="settings-section review setup">
				<h3>
					<div class="dashicons dashicons-star-filled"></div>
					<?php echo sprintf( __( '%sRate this plugin%s', 'gravity-forms-stripe' ), "<a href='http://wordpress.org/support/view/plugin-reviews/gravity-forms-stripe' target='_blank'>", '</a>' ) ?>
				</h3>
			</div>
			<div class="settings-section email-updates setup">
				<?php add_thickbox(); ?>
				<h3>
					<div class="dashicons dashicons-email-alt"></div>
					<?php echo sprintf( __( '%sGet important updates%s', 'gravity-forms-stripe' ), "<a href='#TB_inline?width=375&inlineId=gfp_stripe_sign_up_container' class='thickbox' title='Send Me Updates'>", '</a>' ) ?>
				</h3>
			</div>
			<div id="gfp_stripe_sign_up_container" style="display:none;">
				<form id="gfp_stripe_sign_up">
					<div id="email-icon" class="dashicons dashicons-email-alt"></div>
					<p>From time to time, there are important announcements that accompany a new version of the plugin,
					   such as updating your Stripe API. Don’t miss out on anything that can affect your payments.</p>
					<input type="text" id="gfp_stripe_update_email" name="gfp_stripe_update_email"
					       value="<?php echo get_option( 'admin_email' ); ?>"/> <input id="gfp_stripe_sign_up_submit"
					                                                                   type="button"
					                                                                   class="button button-large button-primary"
					                                                                   value="<?php _e( 'Send Me Updates', 'gravity-forms-stripe' ); ?>"/>

					<div id="gfp_stripe_sign_up_error_message"></div>
				</form>
			</div>
		<?php } ?>
		<br/><br/>
		<div class="settings-section services">
			<h3>
				<div class="service feature-request">
					<div class="dashicons dashicons-pressthis"></div>
					<?php echo sprintf( __( 'Request a %snew feature%s', 'gravity-forms-stripe' ), "<a href='http://gravityplus.pro/support/gravity-forms-stripe/request-feature?utm_source=gravity-forms-stripe&utm_medium=link&utm_content=settings-page&utm_campaign=gravity-forms-stripe' target='_blank'>", '</a>' ) ?>
				</div>
				<div class="service help">
					<div class="dashicons dashicons-shield-alt"></div>
					<?php echo sprintf( __( 'Get %sprofessional support%s', 'gravity-forms-stripe' ), "<a href='http://gravityplus.pro/gravity-forms-stripe/get-help?utm_source=gravity-forms-stripe&utm_medium=link&utm_content=settings-page&utm_campaign=gravity-forms-stripe' target='_blank'>", '</a>' ) ?>
				</div>
				<div class="service custom-setup">
					<div class="dashicons dashicons-hammer"></div>
					<?php echo sprintf( __( '%sSetup or customize%s for me', 'gravity-forms-stripe' ), "<a href='http://gravityplus.pro/gravity-forms-stripe/custom-setup?utm_source=gravity-forms-stripe&utm_medium=link&utm_content=settings-page&utm_campaign=gravity-forms-stripe' target='_blank'>", '</a>' ) ?>
				</div>
			</h3>
		</div>
		<?php
		do_action( 'gfp_stripe_before_uninstall_button', $settings );

		if ( ! class_exists( 'GFPMoreStripe' ) ) {
			?>
			<form action="" method="post">
				<?php wp_nonce_field( 'uninstall', 'gfp_stripe_uninstall' ) ?>
				<?php if ( GFCommon::current_user_can_any( 'gfp_stripe_uninstall' ) ) { ?>
					<div class="hr-divider"></div>

					<h3><?php _e( 'Uninstall Stripe Add-On', 'gravity-forms-stripe' ) ?></h3>
					<div
						class="delete-alert"><?php _e( 'Warning! This operation deletes ALL Stripe Rules.', 'gravity-forms-stripe' ) ?>
						<?php
						$uninstall_button = '<input type="submit" name="uninstall" value="' . __( 'Uninstall Stripe Add-On', 'gravity-forms-stripe' ) . '" class="button" onclick="return confirm(\'' . __( "Warning! ALL Stripe Rules will be deleted. This cannot be undone. \'OK\' to delete, \'Cancel\' to stop", 'gravity-forms-stripe' ) . '\');"/>';
						echo apply_filters( 'gfp_stripe_uninstall_button', $uninstall_button );
						?>
					</div>
				<?php } ?>
			</form>
		<?php
		}
		do_action( 'gfp_stripe_after_uninstall_button' );
		?>

	<?php
	}

	/**
	 * Uninstall
	 *
	 * @since
	 *
	 * @uses GFP_Stripe::has_access()
	 * @uses do_action()
	 * @uses GFP_Stripe_Data::drop_tables()
	 * @uses delete_option()
	 * @uses delete_transient()
	 * @uses deactivate_plugins()
	 * @uses update_option()
	 * @uses get_option()
	 *
	 * @return void
	 */
	public function uninstall() {

		if ( ! self::$_this->has_access( 'gfp_stripe_uninstall' ) ) {
			die( __( 'You don\'t have adequate permission to uninstall the Stripe Add-On.', 'gravity-forms-stripe' ) );
		}

		do_action( 'gfp_stripe_uninstall_condition' );

		GFP_Stripe_Data::drop_tables();

		delete_option( 'gfp_stripe_version' );
		delete_option( 'gfp_stripe_settings' );
		delete_option( 'gfp_support_key' );
		delete_option( 'gfp_stripe_usage_events' );

		delete_transient( 'gfp_stripe_currency' );
		delete_transient( 'gfp_stripe_usage_stats_cache_data' );

		$plugin = plugin_basename( trim( GFP_STRIPE_FILE ) );
		deactivate_plugins( $plugin );
		update_option( 'recently_activated', array( $plugin => time() ) + (array) get_option( 'recently_activated' ) );
	}

	/**
	 * Add feed & settings page tooltips to the list of tooltips
	 *
	 * @since 0.1.0
	 *
	 * @uses  __()
	 *
	 * @param $tooltips
	 *
	 * @return array
	 */
	public function gform_tooltips( $tooltips ) {
		$stripe_tooltips = array(
			'stripe_rule_name'                    => '<h6>' . __( 'Rule Name', 'gravity-forms-stripe' ) . '</h6>' . __( 'Enter a name to uniquely identify this Stripe rule.', 'gravity-forms-stripe' ),
			'stripe_transaction_type'             => '<h6>' . __( 'Transaction Type', 'gravity-forms-stripe' ) . '</h6>' . __( 'Select which Stripe transaction type should be used. One-Time Payments, Subscription, or Billing Info Update.', 'gravity-forms-stripe' ),
			'stripe_gravity_form'                 => '<h6>' . __( 'Gravity Form', 'gravity-forms-stripe' ) . '</h6>' . __( 'Select which Gravity Forms you would like to integrate with Stripe.', 'gravity-forms-stripe' ),
			'stripe_customer'                     => '<h6>' . __( 'Customer', 'gravity-forms-stripe' ) . '</h6>' . __( 'Map your Form Fields to the available Stripe customer information fields.', 'gravity-forms-stripe' ),
			'stripe_options'                      => '<h6>' . __( 'Options', 'gravity-forms-stripe' ) . '</h6>' . __( 'Turn on or off the available Stripe checkout options.', 'gravity-forms-stripe' ),
			'stripe_support_license_key'          => '<h6>' . __( 'gravity+ Support License Key', 'gravity-forms-stripe' ) . '</h6>' . __( 'Your gravity+ support license key is used to enable automatic updates for +(More) Stripe and receive support.', 'gravity-forms-stripe' ),
			'stripe_api'                          => '<h6>' . __( 'API', 'gravity-forms-stripe' ) . '</h6>' . __( 'Select the Stripe API you would like to use. Select \'Live\' to use your Live API keys. Select \'Test\' to use your Test API keys.', 'gravity-forms-stripe' ),
			'stripe_test_secret_key'              => '<h6>' . __( 'API Test Secret Key', 'gravity-forms-stripe' ) . '</h6>' . __( 'Enter the API Test Secret Key for your Stripe account.', 'gravity-forms-stripe' ),
			'stripe_test_publishable_key'         => '<h6>' . __( 'API Test Publishable Key', 'gravity-forms-stripe' ) . '</h6>' . __( 'Enter the API Test Publishable Key for your Stripe account.', 'gravity-forms-stripe' ),
			'stripe_live_secret_key'              => '<h6>' . __( 'API Live Secret Key', 'gravity-forms-stripe' ) . '</h6>' . __( 'Enter the API Live Secret Key for your Stripe account.', 'gravity-forms-stripe' ),
			'stripe_live_publishable_key'         => '<h6>' . __( 'API Live Publishable Key', 'gravity-forms-stripe' ) . '</h6>' . __( 'Enter the API Live Publishable Key for your Stripe account.', 'gravity-forms-stripe' ),
			'stripe_conditional'                  => '<h6>' . __( 'Stripe Condition', 'gravity-forms-stripe' ) . '</h6>' . __( 'When the Stripe condition is enabled, form submissions will only be sent to Stripe when the condition is met. When disabled all form submissions will be sent to Stripe.', 'gravity-forms-stripe' ),
			'form_field_credit_card_funding_type' => '<h6>' . __( 'Funding Type', 'gravity-forms-stripe' ) . '</h6>' . __( 'Select the funding types you want to accept.', 'gravity-forms-stripe' )

		);

		return array_merge( $tooltips, $stripe_tooltips );
	}

	public function gfp_stripe_updates_sign_up() {

		check_ajax_referer( 'gfp_stripe_updates_sign_up', 'gfp_stripe_updates_sign_up' );

		$email = rgpost( 'email' );
		if ( ! is_email( $email ) ) {
			wp_send_json_error( array( 'error_message' => __( 'Invalid email address', 'gravity-forms-stripe' ) ) );
		}

		$api_url    = "https://gravityplus.pro/?gpp_action=updates_sign_up&email={$email}";
		$user_agent = 'GFP_Stripe/' . self::get_version() . '; ' . get_bloginfo( 'url' );
		$args       = array( 'user-agent' => $user_agent, 'body' => $email );

		$raw_response = wp_remote_post( $api_url, $args );
		if ( is_wp_error( $raw_response ) ) {
			$error_message = $raw_response->get_error_message( $raw_response->get_error_code() );
			wp_send_json_error( array( 'error_message' => $error_message ) );
		} else {
			$response = json_decode( wp_remote_retrieve_body( $raw_response ), true );
			if ( true == $response[ 'success' ] ) {
				wp_send_json_success();
			} else {
				wp_send_json_error( array( 'error_message' => $response[ 'error' ] ) );
			}
		}
	}

	//------------------------------------------------------
	//------------- STRIPE RULES EDIT PAGE ------------------
	//------------------------------------------------------

	/**
	 *
	 */
	public function gfp_select_stripe_form() {

		check_ajax_referer( 'gfp_select_stripe_form', 'gfp_select_stripe_form' );

		$type       = rgpost( 'type' );
		$form_id    = intval( rgpost( 'form_id' ) );
		$setting_id = intval( rgpost( 'setting_id' ) );

		if ( ! empty( $form_id ) ) {
			$form = RGFormsModel::get_form_meta( $form_id );

			$customer_fields         = $this->get_customer_information( $form );
			$more_endselectform_args = array(
				'populate_field_options' => array(),
				'post_update_action'     => array(),
				'show_fields'            => array()
			);
			$more_endselectform_args = apply_filters( 'gfp_stripe_feed_endselectform_args', $more_endselectform_args, $form );

			$response = array(
				'form'               => $form,
				'customer_fields'    => $customer_fields,
				'endselectform_args' => $more_endselectform_args
			);

			wp_send_json_success( $response );
		} else {
			wp_send_json_error();
		}
	}

	/**
	 *
	 */
	public function gfp_stripe_update_feed_active() {
		check_ajax_referer( 'gfp_stripe_update_feed_active', 'gfp_stripe_update_feed_active' );
		$feed_id   = $_POST[ 'feed_id' ];
		$form_id   = $_POST[ 'form_id' ];
		$is_active = $_POST[ 'is_active' ];

		$stripe_form_meta = GFP_Stripe_Data::get_stripe_form_meta( $form_id );
		if ( ! isset( $stripe_form_meta[ 'rules' ][ $feed_id ] ) ) {
			return new WP_Error( 'not_found', __( 'Feed not found', 'gravity-forms-stripe' ) );
		}
		$stripe_form_meta[ 'rules' ][ $feed_id ][ 'is_active' ] = (bool) $is_active;
		$result                                                 = GFP_Stripe_Data::update_stripe_form_meta( $form_id, $stripe_form_meta[ 'rules' ], 'rules' );

		return $result;
	}

	/**
	 * @param      $form
	 * @param null $feed
	 *
	 * @return string
	 */
	private function get_customer_information( $form, $feed = null ) {

		$form_fields = $this->get_form_fields( $form );

		$str             = "<table cellpadding='0' cellspacing='0'><tr><td class='stripe_col_heading'>" . __( 'Stripe Fields', 'gravity-forms-stripe' ) . "</td><td class='stripe_col_heading'>" . __( 'Form Fields', 'gravity-forms-stripe' ) . '</td></tr>';
		$customer_fields = $this->get_customer_fields();
		foreach ( $customer_fields as $field ) {
			$selected_field = $feed ? $feed[ 'meta' ][ 'customer_fields' ][ $field[ 'name' ] ] : '';
			$str .= "<tr><td class='stripe_field_cell'>" . $field[ "label" ] . "</td><td class='stripe_field_cell'>" . $this->get_mapped_field_list( $field[ 'name' ], $selected_field, $form_fields ) . '</td></tr>';
		}
		$str .= '</table>';

		return $str;
	}

	/**
	 * @return array
	 */
	private function get_customer_fields() {
		return
			array(
				array(
					'name'  => 'first_name',
					'label' => __( 'First Name', 'gravity-forms-stripe' )
				),
				array(
					'name'  => 'last_name',
					'label' => __( 'Last Name', 'gravity-forms-stripe' )
				),
				array(
					'name'  => 'email',
					'label' => __( 'Email', 'gravity-forms-stripe' )
				),
				array(
					'name'  => 'address1',
					'label' => __( 'Address', 'gravity-forms-stripe' )
				),
				array(
					'name'  => 'address2',
					'label' => __( 'Address 2', 'gravity-forms-stripe' )
				),
				array(
					'name'  => 'city',
					'label' => __( 'City', 'gravity-forms-stripe' )
				),
				array(
					'name'  => 'state',
					'label' => __( 'State', 'gravity-forms-stripe' )
				),
				array(
					'name'  => 'zip',
					'label' => __( 'Zip', 'gravity-forms-stripe' )
				),
				array(
					'name'  => 'country',
					'label' => __( 'Country', 'gravity-forms-stripe' )
				)
			);
	}

	/**
	 * @param $variable_name
	 * @param $selected_field
	 * @param $fields
	 *
	 * @return string
	 */
	private function get_mapped_field_list( $variable_name, $selected_field, $fields ) {
		$field_name = 'stripe_customer_field_' . $variable_name;
		$str        = "<select name='{$field_name}' id='{$field_name}'><option value=''></option>";
		foreach ( $fields as $field ) {
			$field_id    = $field[ 0 ];
			$field_label = esc_html( GFCommon::truncate_middle( $field[ 1 ], 40 ) );

			$selected = $field_id == $selected_field ? "selected='selected'" : '';
			$str .= "<option value='{$field_id}' {$selected} >{$field_label}</option>";
		}
		$str .= '</select>';

		return $str;
	}

	/**
	 * @param $form
	 * @param $selected_field
	 * @param $field_total
	 *
	 * @return string
	 */
	public static function get_product_options( $form, $selected_field, $field_total ) {
		$str    = "<option value=''>" . __( 'Select a field', 'gravity-forms-stripe' ) . '</option>';
		$fields = GFCommon::get_fields_by_type( $form, array( 'product' ) );
		foreach ( $fields as $field ) {
			$field_id    = $field[ "id" ];
			$field_label = RGFormsModel::get_label( $field );

			$selected = $field_id == $selected_field ? "selected='selected'" : "";
			$str .= "<option value='" . $field_id . "' " . $selected . ">" . $field_label . '</option>';
		}

		if ( $field_total ) {
			$selected = $selected_field == 'all' ? "selected='selected'" : "";
			$str .= "<option value='all' " . $selected . ">" . __( 'Subscription Field Total', 'gravity-forms-stripe' ) . "</option>";
		}


		return $str;
	}

	/**
	 * @param $form
	 *
	 * @return array
	 */
	public static function get_form_fields( $form ) {
		$fields = array();

		if ( is_array( $form[ 'fields' ] ) ) {
			foreach ( $form[ 'fields' ] as $field ) {
				if ( is_array( rgar( $field, 'inputs' ) ) ) {

					foreach ( $field[ 'inputs' ] as $input ) {
						$fields[ ] = array( $input[ 'id' ], GFCommon::get_label( $field, $input[ 'id' ] ) );
					}
				} else if ( ! rgar( $field, 'displayOnly' ) ) {
					$fields[ ] = array( $field[ 'id' ], GFCommon::get_label( $field ) );
				}
			}
		}

		return $fields;
	}

	/**
	 *
	 * @since 1.7.9.1
	 *
	 * @uses  get_transient()
	 * @uses  wp_count_posts()
	 * @uses  wp_count_comments()
	 * @uses  wp_get_theme()
	 * @uses  get_stylesheet_directory()
	 * @uses  get_plugins()
	 * @uses  get_plugin_data
	 * @uses  site_url()
	 * @uses  get_bloginfo()
	 * @uses  get_option()
	 * @uses  wp_remote_get()
	 * @uses  set_transient
	 *
	 * @return void
	 */
	private static function do_usage_stats() {

		global $wpdb;

		$data = get_transient( 'gfp_stripe_usage_stats_cache_data' );

		if ( ! $data || $data == '' ) {

			$api_url        = 'https://gravityplus.pro/?gpp_action=update_usage';
			$count_posts    = wp_count_posts();
			$count_pages    = wp_count_posts( 'page' );
			$comments_count = wp_count_comments();

			if ( function_exists( 'wp_get_theme' ) ) {
				$theme_data = wp_get_theme();
				$theme      = array(
					'Name'      => $theme_data[ 'Name' ],
					'ThemeURI'  => $theme_data[ 'ThemeURI' ],
					'Author'    => $theme_data[ 'Author' ],
					'AuthorURI' => $theme_data[ 'AuthorURI' ],
					'Version'   => $theme_data[ 'Version' ]
				);
			} else {
				$theme_data = get_theme_data( get_stylesheet_directory() . '/style.css' );
				$theme      = $theme_data[ 'Name' ] . ' ' . $theme_data[ 'Version' ];
			}

			if ( ! function_exists( 'get_plugins' ) ) {
				include ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$plugins        = array_keys( get_plugins() );
			$active_plugins = get_option( 'active_plugins', array() );

			foreach ( $plugins as $key => $plugin ) {
				if ( in_array( $plugin, $active_plugins ) ) {
					unset( $plugins[ $key ] );
				}
			}

			$plugin_data = get_plugin_data( GFP_STRIPE_FILE );
			$currency    = GFCommon::get_currency();
			$form_meta   = GFP_Stripe_Data::get_all_feeds();
			$feed_count  = 0;
			$feed_types  = $feed_options = array();
			foreach ( $form_meta as $meta ) {
				foreach ( $meta[ 'rules' ] as $feed ) {
					$feed_count ++;
					if ( empty( $feed_types[ $feed[ 'type' ] ] ) ) {
						$feed_types[ $feed[ 'type' ] ] = 1;
					} else {
						$feed_types[ $feed[ 'type' ] ] ++;
					}
					foreach ( $feed as $key => $value ) {
						if ( empty( $feed_options[ $key ] ) ) {
							$feed_options[ $key ] = ( ! empty( $value ) );
						}
					}
				}
			}
			$events     = get_option( 'gfp_stripe_usage_events' );
			$data       = array(
				'url'              => home_url(),
				'posts'            => $count_posts->publish,
				'pages'            => $count_pages->publish,
				'comments'         => $comments_count->total_comments,
				'approved'         => $comments_count->approved,
				'spam'             => $comments_count->spam,
				'pingbacks'        => $wpdb->get_var( "SELECT COUNT(comment_ID) FROM $wpdb->comments WHERE comment_type = 'pingback'" ),
				'plugin'           => $plugin_data,
				'theme'            => $theme,
				'active_plugins'   => $active_plugins,
				'inactive_plugins' => $plugins,
				'wpversion'        => get_bloginfo( 'version' ),
				'multisite'        => is_multisite(),
				'users'            => $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->users" ),
				'currency'         => $currency,
				'feeds'            => $feed_count,
				'feed_types'       => $feed_types,
				'feed_options'     => $feed_options,
				'events'           => $events
			);
			$data       = apply_filters( 'gfp_stripe_usage_stats', $data );
			$user_agent = 'GFP_Stripe/' . self::get_version() . '; ' . get_bloginfo( 'url' );
			$args       = array( 'user-agent' => $user_agent, 'blocking' => false, 'body' => $data );

			wp_remote_post( $api_url, $args );
			set_transient( 'gfp_stripe_usage_stats_cache_data', $data, 60 * 60 * 24 );
			update_option( 'gfp_stripe_usage_events', array() );
		}
	}

	/**
	 * @param $event_name
	 */
	public function gfp_stripe_usage_event( $event_name ) {
		$usage_events = get_option( 'gfp_stripe_usage_events' );
		$usage_events[ $event_name ] ++;

		update_option( 'gfp_stripe_usage_events', $usage_events );
	}

//------------------------------------------------------
//------------- FORM ---------------------------
//------------------------------------------------------

	/**
	 * Add a Stripe form settings tab
	 *
	 * @since 1.8.2
	 *
	 * @param $setting_tabs
	 * @param $form_id
	 *
	 * @return mixed
	 */
	public function gform_form_settings_menu( $setting_tabs, $form_id ) {

		$setting_tabs[ '15' ] = array(
			'name'  => 'stripe',
			'label' => __( 'Stripe', 'gravity-forms-stripe' ),
			'query' => array( 'sid' => null )
		);

		return $setting_tabs;
	}

	/**
	 * Stripe form settings page
	 *
	 * @since 1.8.2
	 *
	 */
	public function gform_form_settings_page_stripe() {

		$form_id = RGForms::get( 'id' );

		$stripe_feed_id = rgempty( 'stripe_feed_id' ) ? rgget( 'sid' ) : rgpost( 'stripe_feed_id' );

		if ( ! rgblank( $stripe_feed_id ) ) {
			$this->form_settings_edit_feed_page( $form_id, $stripe_feed_id );
		} else {
			$this->form_settings_page( $form_id );
		}

		GFFormSettings::page_footer();
	}

	/**
	 * @param $form_id
	 */
	private function form_settings_page( $form_id ) {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_script( 'gfp_stripe_form_settings_stripe_js', trailingslashit( GFP_STRIPE_URL ) . "js/form-settings-stripe{$suffix}.js", array( 'jquery' ), GFP_Stripe::get_version() );

		if ( 'delete' == rgpost( 'action' ) && check_admin_referer( 'gfp_stripe_feeds_list_action', 'gfp_stripe_feeds_list_action' ) ) {
			$sid = rgpost( 'action_argument' );
			if ( ! empty( $sid ) ) {
				$feed_deleted = GFP_Stripe_Data::delete_feed( $sid, $form_id );
				if ( $feed_deleted ) {
					GFCommon::add_message( __( 'Stripe rule deleted.', 'gravity-forms-stripe' ) );
				} else {
					GFCommon::add_error_message( __( 'There was an issue deleting this rule.', 'gravity-forms-stripe' ) );
				}
			}
		}

		if ( rgpost( 'gfp_stripe_save_form_settings' ) ) {

			check_admin_referer( 'gfp_stripe_save_form_settings', 'gfp_stripe_save_form_settings' );
			$form = GFFormsModel::get_form_meta( $form_id );

			$updated_form = apply_filters( 'gfp_stripe_pre_form_settings_save', array(), $form );

			if ( ! empty( $updated_form ) ) {

				$form = $updated_form;

				$update_result = GFFormsModel::update_form_meta( $form_id, $form );

				if ( false !== $update_result ) {
					GFCommon::add_message( __( 'Settings updated', 'gravityforms-stripe-more' ) );
				} else {
					GFCommon::add_error_message( __( 'There was an error while saving your settings', 'gravityforms-stripe-more' ) );
				}
			}
		}

		GFFormSettings::page_header( __( 'Stripe', 'gravity-forms-stripe' ) );
		$add_new_url = add_query_arg( array( 'sid' => 0 ) );
		$form_id     = rgget( 'id' );

		$stripe_feeds_table = new GFP_Stripe_List_Table( $form_id );
		$stripe_feeds_table->prepare_items();

		$form_settings_js_data = array(
			'inactive_text'             => __( 'Inactive', 'gravity-forms-stripe' ),
			'active_text'               => __( 'Active', 'gravity-forms-stripe' ),
			'nonce'                     => wp_create_nonce( 'gfp_stripe_update_feed_active' ),
			'update_feed_error_message' => __( 'Ajax error while updating rule', 'gravity-forms-stripe' )
		);
		wp_localize_script( 'gfp_stripe_form_settings_stripe_js', 'stripe_form_settings', $form_settings_js_data );

		require_once( trailingslashit( GFP_STRIPE_PATH ) . 'includes/views/form-settings-stripe.php' );

	}

	/**
	 * @param $form_id
	 * @param $feed_id
	 */
	private function form_settings_edit_feed_page( $form_id, $feed_id ) {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_script( 'gfp_stripe_form_settings_edit_feed_js', trailingslashit( GFP_STRIPE_URL ) . "js/form-settings-edit-feed{$suffix}.js", array( 'jquery' ), GFP_Stripe::get_version() );
		wp_enqueue_script( 'gform_gravityforms' );

		if ( ! rgempty( 'stripe_feed_id' ) ) {
			$feed_id = rgpost( 'stripe_feed_id' );
		}

		$stripe_form_meta = GFP_Stripe_Data::get_stripe_form_meta( $form_id );
		if ( $stripe_form_meta ) {
			$current_feed_ids = array_keys( $stripe_form_meta[ 'rules' ] );
			ksort( $current_feed_ids );
		}

		$feed = ! $feed_id ? array(
			'meta'      => array(),
			'is_active' => true
		) : self::get_feed( $stripe_form_meta, $feed_id );

		$new_feed = empty( $feed_id ) || empty( $feed );

		$form     = RGFormsModel::get_form_meta( $form_id );
		$list_url = remove_query_arg( 'sid' );

		if ( rgpost( 'save' ) ) {

			check_admin_referer( 'gfp_stripe_save_feed', 'gfp_stripe_save_feed' );

			if ( $new_feed ) {
				if ( ! empty( $current_feed_ids ) ) {
					$feed_id = ( string ) ( ( array_pop( $current_feed_ids ) ) + 0.1 );
				} else {
					$feed_id = (string) ( number_format( ( float ) $form_id, 1, '.', ',' ) );
				}
				$feed[ 'id' ] = $feed_id;
			}

			$feed[ 'form_id' ]                      = $form_id;
			$feed[ 'meta' ][ 'rule_name' ]          = rgpost( 'gfp_stripe_rule_name' );
			$feed[ 'meta' ][ 'type' ]               = rgpost( 'gfp_stripe_type' );
			$feed[ 'meta' ][ 'update_post_action' ] = rgpost( 'gfp_stripe_update_action' );

			$feed[ 'meta' ][ 'stripe_conditional_enabled' ]  = rgpost( 'gfp_stripe_conditional_enabled' );
			$feed[ 'meta' ][ 'stripe_conditional_field_id' ] = rgpost( 'gfp_stripe_conditional_field_id' );
			$feed[ 'meta' ][ 'stripe_conditional_operator' ] = rgpost( 'gfp_stripe_conditional_operator' );
			$feed[ 'meta' ][ 'stripe_conditional_value' ]    = rgpost( 'gfp_stripe_conditional_value' );

			//-----------------

			$customer_fields                     = $this->get_customer_fields();
			$feed[ 'meta' ][ 'customer_fields' ] = array();
			foreach ( $customer_fields as $field ) {
				$feed[ 'meta' ][ 'customer_fields' ][ $field[ 'name' ] ] = rgpost( "stripe_customer_field_{$field['name']}" );
			}

			$feed = apply_filters( 'gfp_stripe_before_save_feed', $feed, $form );

			$is_valid = apply_filters( 'gfp_stripe_feed_validation', true, $feed );

			if ( $is_valid ) {
				$rule                                    = $feed[ 'meta' ];
				$rule[ 'id' ]                            = $feed[ 'id' ];
				$rule[ 'is_active' ]                     = $feed[ 'is_active' ];
				$stripe_form_meta[ 'rules' ][ $feed_id ] = $rule;

				GFP_Stripe_Data::save_feeds( $form_id, $stripe_form_meta[ 'rules' ] );
				do_action( 'gfp_stripe_after_save_feed', $feed, $form, $new_feed );
				$new_feed = false;

				GFCommon::add_message( sprintf( __( 'Rule saved successfully. %sBack to list.%s', 'gravity-forms-stripe' ), '<a href="' . $list_url . '">', '</a>' ) );
			} else {
				GFCommon::add_error_message( __( 'There was an issue saving your rule. Please check all required information below.', 'gravity-forms-stripe' ) );
			}
		}
		GFFormSettings::page_header( __( 'Stripe', 'gravity-forms-stripe' ) );
		$settings = get_option( 'gfp_stripe_settings' );

		$post_categories                 = wp_dropdown_categories( array(
			                                                           'orderby'      => 'name',
			                                                           'hide_empty'   => 0,
			                                                           'echo'         => false,
			                                                           'hierarchical' => true,
			                                                           'name'         => 'gfp_stripe_conditional_value',
			                                                           'id'           => 'gfp_stripe_conditional_value',
			                                                           'class'        => 'optin_select'
		                                                           ) );
		$post_categories                 = str_replace( "\n", "", str_replace( "'", "\\'", $post_categories ) );
		$conditional_value_placeholder   = __( 'Enter value', 'gravity-forms-stripe' );
		$form_settings_edit_feed_js_data = array(
			'form_id'                       => $form_id,
			'form'                          => $form,
			'new_feed'                      => $new_feed,
			'select_form_nonce'             => wp_create_nonce( 'gfp_select_stripe_form' ),
			'select_form_error_message'     => __( 'Ajax error while selecting a form', 'gravity-forms-stripe' ),
			'post_categories'               => $post_categories,
			'conditional_value_placeholder' => $conditional_value_placeholder
		);
		if ( ! $new_feed ) {
			$form_settings_edit_feed_js_data[ 'reg_condition_selected_field' ] = str_replace( '"', '\"', $feed[ 'meta' ][ 'stripe_conditional_field_id' ] );
			$form_settings_edit_feed_js_data[ 'reg_condition_selected_value' ] = str_replace( '"', '\"', $feed[ 'meta' ][ 'stripe_conditional_value' ] );
		}
		wp_localize_script( 'gfp_stripe_form_settings_edit_feed_js', 'stripe_edit_feed_settings', apply_filters( 'gfp_stripe_edit_feed_js_data', $form_settings_edit_feed_js_data, $form, $feed ) );
		require_once( trailingslashit( GFP_STRIPE_PATH ) . 'includes/views/form-settings-edit-feed.php' );
	}

	public static function gform_field_standard_settings( $position, $form_id ) {
		if ( 1435 == $position ) {
			require_once( GFP_STRIPE_PATH . '/includes/views/field-setting-card_funding_types.php' );
		}
	}

	public static function gform_editor_js() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_script( 'gfp_stripe_form_editor_card_funding_types', GFP_STRIPE_URL . "js/form-editor-card-funding-types{$suffix}.js", array( 'gform_form_editor' ), self::get_version() );
	}

	/**
	 * Add Stripe JS
	 *
	 * @since 0.1.0
	 *
	 * @uses  GFCommon::has_credit_card_field()
	 * @uses  GFP_Stripe_Data::get_feed_by_form()
	 * @uses  enqueue_stripe_js()
	 *
	 * @param null $form
	 * @param null $ajax
	 *
	 * @return void
	 */
	public function gform_enqueue_scripts( $form = null, $ajax = null ) {

		if ( self::is_stripe_form( $form ) ) {
			$this->enqueue_stripe_js( $form );
		}

	}

	/**
	 * Remove input field name attribute so credit card information is not sent to server
	 *
	 * @since 0.1.0
	 *
	 * @uses  GFP_Stripe_Data::get_feed_by_form()
	 *
	 * @param $field_content
	 * @param $field
	 * @param $default_value
	 * @param $lead_id
	 * @param $form_id
	 *
	 * @return mixed
	 */
	public function gform_field_content( $field_content, $field, $default_value, $lead_id, $form_id ) {

		if ( 'creditcard' == $field[ 'type' ] ) {

			$form_feeds = GFP_Stripe_Data::get_feed_by_form( $form_id, true );

			if ( ! empty( $form_feeds ) ) {

				$search          = array();
				$exp_date_input  = $field[ 'id' ] . '.2';
				$card_type_input = $field[ 'id' ] . '.4';
				foreach ( $field[ 'inputs' ] as $input ) {
					if ( $card_type_input == $input[ 'id' ] ) {
						continue;
					} else {
						( $input[ 'id' ] == $exp_date_input ) ? ( $search[ ] = "name='input_" . $input[ 'id' ] . "[]'" ) : ( $search[ ] = "name='input_" . $input[ 'id' ] . "'" );
					}
				}
				$field_content = str_ireplace( $search, '', $field_content );
			}
		}

		return $field_content;

	}

	/**
	 * Check to see if ID is an input ID
	 *
	 * @since 1.7.9.1
	 *
	 * @param $id
	 *
	 * @return int
	 */
	private function is_input_id( $id ) {
		$is_input_id = stripos( $id, '.' );

		return $is_input_id;
	}

	/**
	 * Get field ID from the ID saved in Stripe feed
	 *
	 * @since    1.7.9.1
	 *
	 * @uses     GFP_Stripe::is_input_id()
	 *
	 * @param $id
	 *
	 * @return mixed
	 */
	private function get_field_id( $id ) {
		$input_id = $this->is_input_id( $id );
		if ( $input_id ) {
			$id = substr( $id, 0, $input_id );
		}

		return $id;
	}

	/**
	 * Get rule fields
	 *
	 * @since 1.7.9.1
	 *
	 * @param $form_rule
	 *
	 * @return array
	 */
	private function get_rule_fields( $form_rule ) {
		return array(
			'rule_field_address1' => $form_rule[ 'meta' ][ 'customer_fields' ][ 'address1' ],
			'rule_field_city'     => $form_rule[ 'meta' ][ 'customer_fields' ][ 'city' ],
			'rule_field_state'    => $form_rule[ 'meta' ][ 'customer_fields' ][ 'state' ],
			'rule_field_zip'      => $form_rule[ 'meta' ][ 'customer_fields' ][ 'zip' ],
			'rule_field_country'  => $form_rule[ 'meta' ][ 'customer_fields' ][ 'country' ]
		);
	}

	/**
	 * Get field IDs
	 *
	 * @since 1.7.9.1
	 *
	 * @uses  GFP_Stripe::get_field_id()
	 *
	 * @param $feed_fields
	 *
	 * @return array
	 */
	private function get_field_ids( $feed_fields ) {
		$feed_field_address1 = $feed_field_city = $feed_field_state = $feed_field_zip = $feed_field_country = '';
		extract( $feed_fields );

		return array(
			'address1_field_id' => $this->get_field_id( $feed_field_address1 ),
			'city_field_id'     => $this->get_field_id( $feed_field_city ),
			'state_field_id'    => $this->get_field_id( $feed_field_state ),
			'zip_field_id'      => $this->get_field_id( $feed_field_zip ),
			'country_field_id'  => $this->get_field_id( $feed_field_country )
		);
	}

	/**
	 * Get field input ID
	 *
	 * @since 1.7.9.1
	 *
	 * @param $field_input_id
	 *
	 * @return string
	 */
	private function get_field_input_id( $field_input_id ) {
		$separator_position = stripos( $field_input_id, '.' );
		$input_id           = substr( $field_input_id, $separator_position + 1 );

		return $input_id;
	}

	/**
	 * Get form input IDs
	 *
	 * @since 1.7.9.1
	 *
	 * @uses  GFP_Stripe::get_field_input_id()
	 *
	 * @param $form
	 * @param $rule_fields
	 * @param $rule_field_ids
	 *
	 * @return array
	 */
	private function get_form_input_ids( $form, $rule_fields, $rule_field_ids ) {
		$form_input_ids      = array(
			'street_input_id'  => '',
			'city_input_id'    => '',
			'state_input_id'   => '',
			'zip_input_id'     => '',
			'country_input_id' => ''
		);
		$rule_field_address1 = $rule_field_city = $rule_field_state = $rule_field_zip = $rule_field_country = '';
		extract( $rule_fields );
		$address1_field_id = $city_field_id = $state_field_id = $zip_field_id = $country_field_id = '';
		extract( $rule_field_ids );

		foreach ( $form[ 'fields' ] as $field ) {
			if ( 'creditcard' == $field[ 'type' ] ) {
				$form_input_ids[ 'creditcard_field_id' ] = $field[ 'id' ];
			} else if ( ! empty( $field[ 'inputs' ] ) ) {
				foreach ( $field[ 'inputs' ] as $input ) {
					switch ( $input[ 'id' ] ) {
						case $rule_field_address1:
							$input_id                            = $this->get_field_input_id( $input[ 'id' ] );
							$street_input_id                     = $form[ 'id' ] . '_' . $field[ 'id' ] . '_' . $input_id;
							$form_input_ids[ 'street_input_id' ] = $street_input_id;
							break;
						case $rule_field_city:
							$input_id                          = $this->get_field_input_id( $input[ 'id' ] );
							$city_input_id                     = $form[ 'id' ] . '_' . $field[ 'id' ] . '_' . $input_id;
							$form_input_ids[ 'city_input_id' ] = $city_input_id;
							break;
						case $rule_field_state:
							$input_id                           = $this->get_field_input_id( $input[ 'id' ] );
							$state_input_id                     = $form[ 'id' ] . '_' . $field[ 'id' ] . '_' . $input_id;
							$form_input_ids[ 'state_input_id' ] = $state_input_id;
							break;
						case $rule_field_zip:
							$input_id                         = $this->get_field_input_id( $input[ 'id' ] );
							$zip_input_id                     = $form[ 'id' ] . '_' . $field[ 'id' ] . '_' . $input_id;
							$form_input_ids[ 'zip_input_id' ] = $zip_input_id;
							break;
						case $rule_field_country:
							$input_id                             = $this->get_field_input_id( $input[ 'id' ] );
							$country_input_id                     = $form[ 'id' ] . '_' . $field[ 'id' ] . '_' . $input_id;
							$form_input_ids[ 'country_input_id' ] = $country_input_id;
							break;
					}
				}
			} else {
				switch ( $field[ 'id' ] ) {
					case $address1_field_id:
						$form_input_ids[ 'street_input_id' ] = $form[ 'id' ] . '_' . $field[ 'id' ];
						break;
					case $city_field_id:
						$form_input_ids[ 'city_input_id' ] = $form[ 'id' ] . '_' . $field[ 'id' ];
						break;
					case $state_field_id:
						$form_input_ids[ 'state_input_id' ] = $form[ 'id' ] . '_' . $field[ 'id' ];
						break;
					case $zip_field_id:
						$form_input_ids[ 'zip_input_id' ] = $form[ 'id' ] . '_' . $field[ 'id' ];
						break;
					case $country_field_id:
						$form_input_ids[ 'country_input_id' ] = $form[ 'id' ] . '_' . $field[ 'id' ];
						break;
				}
			}
		}

		return $form_input_ids;
	}

	/**
	 * Does rule have conditional logic
	 *
	 * @since 1.7.9.1
	 *
	 * @param $rule
	 * @param $conditional_field_id
	 *
	 * @return bool
	 */
	private function rule_has_condition( $rule, $conditional_field_id ) {

		$has_condition = ( ( '1' == $rule[ 'meta' ][ 'stripe_conditional_enabled' ] ) && ( $conditional_field_id == $rule[ 'meta' ][ 'stripe_conditional_field_id' ] ) );

		return $has_condition;

	}

	/**
	 * @param $rule
	 *
	 * @return array
	 */
	private function get_rule_condition( $rule ) {

		$rule_condition = array();

		$rule_condition[ 'operator' ] = $rule[ 'meta' ][ 'stripe_conditional_operator' ];
		$rule_condition[ 'value' ]    = $rule[ 'meta' ][ 'stripe_conditional_value' ];

		return $rule_condition;

	}

	/**
	 * Enqueue Stripe JS
	 *
	 * @since 1.8.17.1
	 *
	 * @uses  GFP_Stripe_Data::get_feed_by_form()
	 * @uses  rule_has_condition()
	 * @uses  wp_enqueue_script()
	 * @uses  wp_localize_script()
	 * @uses  GFP_Stripe::get_version()
	 * @uses  GFP_Stripe::get_api_key()
	 * @uses  GFP_Stripe::get_rule_fields()
	 * @uses  GFP_Stripe::get_field_ids()
	 * @uses  GFP_Stripe::get_form_input_ids()
	 * @uses  GFP_Stripe::get_rule_condition()
	 * @uses  apply_filters()
	 * @uses  GFCommon::get_base_url()
	 *
	 * @param $form
	 *
	 */
	private function enqueue_stripe_js( $form ) {

		$stripe_rules = GFP_Stripe_Data::get_feed_by_form( $form[ 'id' ], true );

		$conditional_field_id = 0;

		if ( 1 == count( $stripe_rules ) ) {

			$stripe_rules         = $stripe_rules[ 0 ];
			$conditional_field_id = $stripe_rules[ 'meta' ][ 'stripe_conditional_field_id' ];

		} else if ( 1 < count( $stripe_rules ) ) {

			$valid_rules          = 0;
			$conditional_field_id = $stripe_rules[ 0 ][ 'meta' ][ 'stripe_conditional_field_id' ];

			foreach ( $stripe_rules as $rule ) {

				if ( $this->rule_has_condition( $rule, $conditional_field_id ) ) {
					$valid_rules ++;
				}

			}

			if ( $valid_rules !== count( $stripe_rules ) ) {

				$stripe_rules         = $stripe_rules[ 0 ];
				$conditional_field_id = $stripe_rules[ 'meta' ][ 'stripe_conditional_field_id' ];

			}

		}

		if ( ! empty( $stripe_rules ) ) {

			$form_id = $form[ 'id' ];

			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			$creditcard_field_id = '';

			$multiple_rules = isset( $valid_rules ) && ( 1 < $valid_rules );

			if ( $multiple_rules ) {

				$num_of_rules    = count( $stripe_rules );
				$rule_field_info = array();

				foreach ( $stripe_rules as $rule ) {

					$rule_fields    = $this->get_rule_fields( $rule );
					$rule_field_ids = $this->get_field_ids( $rule_fields );
					$form_input_ids = $this->get_form_input_ids( $form, $rule_fields, $rule_field_ids );
					$rule_condition = $this->get_rule_condition( $rule );

					$rule_field_info[ ] = array_merge( $form_input_ids, $rule_condition );

				}

				foreach ( $rule_field_info as $field_info ) {

					if ( ! empty( $field_info[ 'creditcard_field_id' ] ) ) {

						$creditcard_field_id = $field_info[ 'creditcard_field_id' ];

						break;

					}
				}

				unset( $field_info );


			} else {

				$num_of_rules = 1;

				$rule_field_info = array();

				$rule_fields     = $this->get_rule_fields( $stripe_rules );
				$rule_field_ids  = $this->get_field_ids( $rule_fields );
				$street_input_id = $city_input_id = $state_input_id = $zip_input_id = $country_input_id = '';

				extract( $this->get_form_input_ids( $form, $rule_fields, $rule_field_ids ) );

				if ( ! empty( $creditcard_field_id ) ) {
					$rule_field_info[ 'creditcard_field_id' ] = $creditcard_field_id;
				}

				$rule_field_info[ 'street_input_id' ]  = $street_input_id;
				$rule_field_info[ 'city_input_id' ]    = $city_input_id;
				$rule_field_info[ 'state_input_id' ]   = $state_input_id;
				$rule_field_info[ 'zip_input_id' ]     = $zip_input_id;
				$rule_field_info[ 'country_input_id' ] = $country_input_id;
			}

			$rule_field_info              = apply_filters( 'gfp_stripe_gform_get_form_filter', $rule_field_info, $stripe_rules, $form );
			self::$stripe_rule_field_info = $rule_field_info = apply_filters( 'gfp_stripe_rule_field_info', $rule_field_info, $stripe_rules, $form );

			$rule_has_condition = false;

			if ( array_key_exists( 0, $rule_field_info ) && ( is_array( $rule_field_info[ 0 ] ) ) ) {

				$rule_has_condition = true;

				wp_enqueue_script( 'gform_conditional_logic', GFCommon::get_base_url() . '/js/conditional_logic.js', array(
					'jquery',
					'gforms_gravityforms'
				), GFCommon::$version );

			} else if ( ( $conditional_field_id ) && ( $this->rule_has_condition( $stripe_rules, $conditional_field_id ) ) ) {

				$rule_field_info = array_merge( $rule_field_info, $this->get_rule_condition( $stripe_rules ) );

				if ( array_key_exists( 'operator', $rule_field_info ) ) {

					$rule_has_condition = true;

					wp_enqueue_script( 'gform_conditional_logic', GFCommon::get_base_url() . '/js/conditional_logic.js', array(
						'jquery',
						'gforms_gravityforms'
					), GFCommon::$version );

				}

			}

			if ( ! empty( $creditcard_field_id ) ) {

				wp_enqueue_script( 'stripe-js', 'https://js.stripe.com/v2/', array( 'jquery' ), self::get_version() );

				$publishable_key = apply_filters( 'gfp_stripe_get_publishable_key', self::$_this->get_api_key( 'publishable' ), $form_id );

				wp_enqueue_script( 'gfp_stripe_js', trailingslashit( GFP_STRIPE_URL ) . "js/form-display{$suffix}.js", array(
					'jquery',
					'stripe-js'
				), self::get_version() );

				$creditcard_field      = GFFormsModel::get_field( $form, $creditcard_field_id );
				$allowed_funding_types = rgar( $creditcard_field, 'creditCardFundingTypes' );

				if ( empty( $allowed_funding_types ) ) {
					$allowed_funding_types = array( 'credit', 'debit', 'prepaid', 'unknown' );
				}

				$gfp_stripe_js_vars = array(
					'form_id'               => $form_id,
					'publishable_key'       => $publishable_key,
					'creditcard_field_id'   => $creditcard_field_id,
					'allowed_funding_types' => $allowed_funding_types,
					'num_of_rules'          => $num_of_rules,
					'rule_field_info'       => $rule_field_info,
					'rule_has_condition'    => $rule_has_condition,
					'conditional_field_id'  => $conditional_field_id,
					'error_messages'        => array(
						'funding'         => __( ' cards are not accepted.', 'gravity-forms-stripe' ),
						'card_number'     => __( 'Invalid card number.', 'gravity-forms-stripe' ),
						'expiration'      => __( ' Invalid expiration date.', 'gravity-forms-stripe' ),
						'security_code'   => __( ' Invalid security code.', 'gravity-forms-stripe' ),
						'cardholder_name' => __( ' Invalid cardholder name.', 'gravity-forms-stripe' ),
						'no_card_info'    => __( 'Unable to read card information', 'gravity-forms-stripe' )
					)
				);

				wp_localize_script( 'gfp_stripe_js', 'gfp_stripe_js_vars', $gfp_stripe_js_vars );

			}

		}

	}

	/**
	 * @param $form_id
	 */
	public function gform_after_delete_form( $form_id ) {
		GFP_Stripe_Data::delete_stripe_form_meta( $form_id );
	}


	public static function get_stripe_rule_field_info() {
		return self::$stripe_rule_field_info;
	}

	//------------------------------------------------------
	//------------- PROCESSING ---------------------------
	//------------------------------------------------------

	/**
	 * @param $validation_result
	 * @param $value
	 * @param $form
	 * @param $field
	 *
	 * @return mixed
	 */
	public function gform_field_validation( $validation_result, $value, $form, $field ) {

		$form_feeds = GFP_Stripe_Data::get_feed_by_form( $form[ 'id' ], true );

		if ( ! empty( $form_feeds ) ) {

			if ( 'creditcard' == $field[ 'type' ] ) {

				$token = rgpost( 'stripeToken' );

				if ( empty( $token ) ) {
					$validation_result[ 'is_valid' ] = false;
					$validation_result[ 'message' ]  = __( 'This form cannot process your payment. Please contact the site owner.', 'gravity-forms-stripe' );
					self::log_error( __( 'Empty token', 'gravity-forms-stripe' ) );
				} else {
					$validation_result[ 'is_valid' ] = true;
					unset( $validation_result[ 'message' ] );
				}

				$validation_result = apply_filters( 'gfp_stripe_gform_field_validation', $validation_result, $value, $field );
			}

		}

		return $validation_result;
	}

	/**
	 * @param $validation_result
	 *
	 * @return mixed|void
	 */
	public function gform_validation( $validation_result ) {

		$feed = $this->is_ready_for_capture( $validation_result );

		if ( ! $feed ) {
			return $validation_result;
		}

		if ( ( 'product' == $feed[ 'meta' ][ 'type' ] ) && ( ! class_exists( 'GFPMoreStripe' ) ) ) {

			$validation_result = $this->make_product_payment( $feed, $validation_result );

		} else {
			$validation_result = apply_filters( 'gfp_stripe_gform_validation', $validation_result, $feed );
		}

		return $validation_result;
	}

	/**
	 * @param $validation_result
	 *
	 * @return bool
	 */
	private function is_ready_for_capture( $validation_result ) {

		$is_ready_for_capture = true;
		$reason               = '';

		if ( false == $validation_result[ 'is_valid' ] || ! $this->is_last_page( $validation_result[ 'form' ] ) ) {
			$is_ready_for_capture = false;
			$reason               = 'form';
		}

		if ( $is_ready_for_capture ) {

			$feed = self::$_this->get_feed_that_meets_condition( $validation_result[ 'form' ] );

			if ( ! $feed ) {
				$is_ready_for_capture = false;
				$reason               = 'feed';
			} else {
				$is_ready_for_capture = $feed;
			}

		}

		if ( false !== $is_ready_for_capture ) {

			$creditcard_field = self::$_this->get_creditcard_field( $validation_result[ 'form' ] );

			if ( $creditcard_field && RGFormsModel::is_field_hidden( $validation_result[ 'form' ], $creditcard_field, array() ) ) {
				$is_ready_for_capture = false;
				$reason               = 'creditcard';
			}

		}

		return apply_filters( 'gfp_stripe_is_ready_for_capture', $is_ready_for_capture, $reason, $validation_result );
	}

	/**
	 * @param $form
	 *
	 * @return bool
	 */
	public static function is_last_page( $form ) {

		$current_page = GFFormDisplay::get_source_page( $form[ "id" ] );
		$target_page  = GFFormDisplay::get_target_page( $form, $current_page, rgpost( 'gform_field_values' ) );

		return ( $target_page == 0 );
	}

	/**
	 * @param $stripe_form_meta
	 * @param $feed_id
	 *
	 * @return array
	 */
	public static function get_feed( $stripe_form_meta, $feed_id ) {

		$feed = array();

		foreach ( $stripe_form_meta[ 'rules' ] as $id => $rule ) {

			if ( $id == $feed_id ) {

				$feed = array(
					'id'      => $rule[ 'id' ],
					'meta'    => $rule,
					'form_id' => stristr( $rule[ 'id' ], '.', true )
				);

				if ( isset( $rule[ 'is_active' ] ) ) {
					$feed[ 'is_active' ] = $rule[ 'is_active' ];
				}

				break;

			}

		}

		return $feed;
	}

	/**
	 * @param $form
	 *
	 * @return bool
	 */
	public static function get_feed_that_meets_condition( $form ) {

		$feeds = GFP_Stripe_Data::get_feed_by_form( $form[ 'id' ], true );

		if ( ! $feeds ) {
			return false;
		}

		foreach ( $feeds as $feed ) {

			if ( self::$_this->has_stripe_condition( $form, $feed ) ) {
				return $feed;
			}

		}

		return false;
	}

	/**
	 * @param $form
	 * @param $feed
	 *
	 * @return bool
	 */
	public function has_stripe_condition( $form, $feed ) {

		$feed = $feed[ 'meta' ];

		$operator = $feed[ 'stripe_conditional_operator' ];
		$field    = RGFormsModel::get_field( $form, $feed[ 'stripe_conditional_field_id' ] );

		if ( empty( $field ) || ! $feed[ 'stripe_conditional_enabled' ] ) {
			return true;
		}

		$is_visible = ! RGFormsModel::is_field_hidden( $form, $field, array() );

		$field_value = RGFormsModel::get_field_value( $field, array() );

		$is_value_match = RGFormsModel::is_value_match( $field_value, $feed[ 'stripe_conditional_value' ], $operator );
		$do_stripe      = $is_value_match && $is_visible;

		return $do_stripe;
	}

	/**
	 * Get credit card field
	 *
	 * @since
	 *
	 * @uses GFCommon::get_fields_by_type()
	 *
	 * @param $form
	 *
	 * @return bool
	 */
	public static function get_creditcard_field( $form ) {

		$fields = GFCommon::get_fields_by_type( $form, array( 'creditcard' ) );

		return empty( $fields ) ? false : $fields[ 0 ];

	}

	/**
	 * Process payment
	 *
	 * @since 0.1.0
	 *
	 * @uses  GFP_Stripe::log_debug()
	 * @uses  GFP_Stripe::get_form_data()
	 * @uses  GFP_Stripe::is_last_page()
	 * @uses  GFP_Stripe::get_creditcard_field()
	 * @uses  GFP_Stripe::has_visible_products()
	 * @uses  GFP_Stripe::include_api()
	 * @uses  GFP_Stripe::get_api_key()
	 * @uses  PPP\Stripe\Stripe::setApiKey()
	 * @uses  PPP\Stripe\Customer::create()
	 * @uses  apply_filters()
	 * @uses  GFP_Stripe::log_error()
	 * @uses  GFP_Stripe::gfp_stripe_create_error_message()
	 * @uses  GFP_Stripe::set_validation_result()
	 * @uses  PPP\Stripe\Charge::create(
	 * @uses  GFCommon::get_currency()
	 *
	 * @param $feed
	 * @param $validation_result
	 *
	 * @return mixed
	 */
	private function make_product_payment( $feed, $validation_result ) {

		$form = $validation_result[ 'form' ];

		self::$_this->log_debug( "Starting to make a product payment for form: {$form['id']}" );

		$form_data = self::$_this->get_form_data( $form, $feed );

		if ( $form_data[ 'amount' ] < 0.5 ) {

			self::$_this->log_debug( 'Amount is less than $0.50. No need to process payment, but act as if transaction was successful' );

			if ( $this->is_last_page( $form ) ) {

				$card_field                             = self::$_this->get_creditcard_field( $form );
				$_POST[ "input_{$card_field["id"]}_1" ] = '';

			}

			if ( $this->has_visible_products( $form ) ) {

				self::$transaction_response = array(
					'transaction_id'   => 'N/A',
					'amount'           => $form_data[ 'amount' ],
					'transaction_type' => 1
				);

			}

			return $validation_result;
		}

		self::$_this->include_api();

		$secret_api_key = self::$_this->get_api_key( 'secret' );

		self::$_this->log_debug( 'Creating the customer' );

		try {

			$customer = PPP\Stripe\Customer::create( array(
				                                         'description' => apply_filters( 'gfp_stripe_customer_description', $form_data[ 'name' ], $form_data, $form ),
				                                         'source'      => $form_data[ 'credit_card' ],
				                                         'email'       => $form_data[ 'email' ],
				                                         'expand'      => array( 'default_source' )
			                                         ), $secret_api_key );

		} catch ( Exception $e ) {

			self::$_this->log_error( 'Customer creation failed' );

			$error_message = self::$_this->gfp_stripe_create_error_message( $e );

			return self::$_this->set_validation_result( $validation_result, $_POST, $error_message );

		}

		if ( ! class_exists( 'RGCurrency' ) ) {
			require_once( GFCommon::get_base_path() . '/currency.php' );
		}

		$currency_info = RGCurrency::get_currency( GFCommon::get_currency() );

		try {

			self::$_this->log_debug( 'Creating the charge, using the customer ID' );

			$response = PPP\Stripe\Charge::create( array(
				                                       'amount'      => ( 0 == $currency_info[ 'decimals' ] ) ? round( floatval( $form_data[ 'amount' ] ), 0 ) : ( $form_data[ 'amount' ] * 100 ),
				                                       'currency'    => GFCommon::get_currency(),
				                                       'customer'    => $customer[ 'id' ],
				                                       'description' => apply_filters( 'gfp_stripe_customer_charge_description', implode( '\n', $form_data[ 'line_items' ] ), $form )
			                                       ), $secret_api_key );

			self::$_this->log_debug( "Charge successful. ID: {$response['id']} - Amount: {$response['amount']}" );

			self::$transaction_response = array(
				'transaction_id'   => $response[ 'id' ],
				'amount'           => $response[ 'amount' ] / 100,
				'transaction_type' => 1,
				'customer'         => $customer
			);

			$validation_result[ 'is_valid' ] = true;

			return $validation_result;

		} catch ( Exception $e ) {

			self::$_this->log_error( 'Charge failed' );

			$error_message = self::$_this->gfp_stripe_create_error_message( $e );

			return self::$_this->set_validation_result( $validation_result, $_POST, $error_message );
		}

	}

	/**
	 * Get form data
	 *
	 * @since
	 *
	 * @uses RGFormsModel::create_lead()
	 * @uses GFCommon::get_product_fields()
	 * @uses rgpost()
	 * @uses apply_filters()
	 * @uses GFP_Stripe::get_order_info()
	 *
	 * @param $form
	 * @param $feed
	 *
	 * @return mixed|void
	 */
	public static function get_form_data( $form, $feed ) {

		$tmp_lead  = RGFormsModel::create_lead( $form );
		$products  = GFCommon::get_product_fields( $form, $tmp_lead );
		$form_data = array();

		$form_data[ 'form_title' ]  = $form[ 'title' ];
		$form_data[ 'name' ]        = rgpost( 'input_' . str_replace( '.', '_', $feed[ 'meta' ][ 'customer_fields' ][ 'first_name' ] ) ) . ' ' . rgpost( 'input_' . str_replace( '.', '_', $feed[ 'meta' ][ 'customer_fields' ][ 'last_name' ] ) );
		$form_data[ 'email' ]       = rgpost( 'input_' . str_replace( '.', '_', $feed[ 'meta' ][ 'customer_fields' ][ 'email' ] ) );
		$form_data[ 'address1' ]    = rgpost( 'input_' . str_replace( '.', '_', $feed[ 'meta' ][ 'customer_fields' ][ 'address1' ] ) );
		$form_data[ 'address2' ]    = rgpost( 'input_' . str_replace( '.', '_', $feed[ 'meta' ][ 'customer_fields' ][ 'address2' ] ) );
		$form_data[ 'city' ]        = rgpost( 'input_' . str_replace( '.', '_', $feed[ 'meta' ][ 'customer_fields' ][ 'city' ] ) );
		$form_data[ 'state' ]       = rgpost( 'input_' . str_replace( '.', '_', $feed[ 'meta' ][ 'customer_fields' ][ 'state' ] ) );
		$form_data[ 'zip' ]         = rgpost( 'input_' . str_replace( '.', '_', $feed[ 'meta' ][ 'customer_fields' ][ 'zip' ] ) );
		$form_data[ 'country' ]     = rgpost( 'input_' . str_replace( '.', '_', $feed[ 'meta' ][ 'customer_fields' ][ 'country' ] ) );
		$form_data[ 'credit_card' ] = rgpost( 'stripeToken' );

		$form_data       = apply_filters( 'gfp_stripe_get_form_data', $form_data, $feed, $products, $form, $tmp_lead );
		$order_info_args = '';
		$order_info      = self::$_this->get_order_info( $products, apply_filters( 'gfp_stripe_get_form_data_order_info', $order_info_args, $feed ), $form_data );

		$form_data[ 'line_items' ] = $order_info[ 'line_items' ];
		$form_data[ 'amount' ]     = $order_info[ 'amount' ];

		return $form_data;
	}

	/**
	 * Get order info
	 *
	 * @since
	 *
	 * @uses apply_filters()
	 * @uses GFCommon::to_number()
	 * @uses __()
	 * @uses has_action()
	 *
	 * @param $products
	 * @param $additional_fields
	 * @param $form_data
	 *
	 * @return array
	 */
	private function get_order_info( $products, $additional_fields, $form_data ) {

		$amount        = 0;
		$line_items    = array();
		$item          = 1;
		$continue_flag = 0;
		$new_line_item = '';

		foreach ( $products[ 'products' ] as $field_id => $product ) {

			$continue_flag = apply_filters( 'gfp_stripe_get_order_info', $continue_flag, $field_id, $additional_fields );

			if ( $continue_flag ) {
				continue;
			}

			$quantity = $product[ 'quantity' ] ? $product[ 'quantity' ] : 1;

			$product_price = GFCommon::to_number( $product[ 'price' ], ( ! empty ( $form_data[ 'currency' ] ) ) ? $form_data[ 'currency' ] : '' );

			$options = array();

			if ( isset( $product[ 'options' ] ) && is_array( $product[ 'options' ] ) ) {

				foreach ( $product[ 'options' ] as $option ) {

					$options[ ] = $option[ 'option_label' ];
					$product_price += $option[ 'price' ];

				}

			}

			$amount += $product_price * $quantity;

			$description = '';

			if ( ! empty( $options ) ) {
				$description = __( 'options: ', 'gravity-forms-stripe' ) . ' ' . implode( ', ', $options );
			}

			if ( has_action( 'gfp_stripe_get_order_info_line_items' ) ) {

				$new_line_item = apply_filters( 'gfp_stripe_get_order_info_line_items', $line_items, $product_price, $field_id, $quantity, $product, $description, $item, $form_data );

				if ( ! empty( $new_line_item ) ) {

					$line_items[ ] = $new_line_item;
					$new_line_item = '';

					$item ++;

				}

			} else if ( ( $product_price >= 0 ) ) {

				$line_items[ ] = "(" . $quantity . ")\t" . $product[ "name" ] . "\t" . $description . "\tx\t" . GFCommon::to_money( $product_price, ( ! empty ( $form_data[ 'currency' ] ) ) ? $form_data[ 'currency' ] : '' );

				$item ++;

			}

		}

		if ( has_action( 'gfp_stripe_get_order_info_shipping' ) ) {

			$shipping_info = apply_filters( 'gfp_stripe_get_order_info_shipping', $line_items, $products, $amount, $item, $additional_fields, $form_data );

			if ( ! empty( $shipping_info ) ) {

				$line_items = $shipping_info[ 'line_items' ];
				$amount     = $shipping_info[ 'amount' ];

				$shipping_info = '';

			}

		} else if ( ! empty( $products[ 'shipping' ][ 'name' ] ) ) {

			$line_items[ ] = $item . "\t" . $products[ 'shipping' ][ 'name' ] . "\t" . "1" . "\t" . $products[ 'shipping' ][ 'price' ];

			$amount += $products[ 'shipping' ][ 'price' ];

		}

		return array(
			'amount'     => $amount,
			'line_items' => $line_items
		);
	}

	/**
	 * @param $product
	 *
	 * @return mixed
	 */
	public static function get_product_unit_price( $product ) {

		$product_total = $product[ 'price' ];

		foreach ( $product[ 'options' ] as $option ) {

			$options[ ] = $option[ 'option_label' ];

			$product_total += $option[ 'price' ];

		}

		return $product_total;
	}

	/**
	 * Has visible products
	 *
	 * @since
	 *
	 * @uses RGFormsModel::is_field_hidden()
	 *
	 * @param $form
	 *
	 * @return bool
	 */
	public static function has_visible_products( $form ) {

		foreach ( $form[ 'fields' ] as $field ) {

			if ( $field[ 'type' ] == 'product' && ! RGFormsModel::is_field_hidden( $form, $field, '' ) ) {
				return true;
			}

		}

		return false;
	}

	/**
	 * @param $validation_result
	 * @param $post
	 * @param $error_message
	 *
	 * @return mixed
	 */
	public static function set_validation_result( $validation_result, $post, $error_message ) {

		$credit_card_page = 0;

		foreach ( $validation_result[ 'form' ][ 'fields' ] as &$field ) {

			if ( 'creditcard' == $field[ 'type' ] ) {

				$field[ 'failed_validation' ]  = true;
				$field[ 'validation_message' ] = $error_message;

				$credit_card_page = $field[ 'pageNumber' ];

				break;

			}

		}

		$validation_result[ 'is_valid' ] = false;

		GFFormDisplay::set_current_page( $validation_result[ 'form' ][ 'id' ], $credit_card_page );

		$validation_result = apply_filters( 'gfp_stripe_set_validation_result', $validation_result, $post, $error_message );


		return $validation_result;
	}

	/**
	 * @param      $e
	 * @param bool $mode
	 *
	 * @return mixed|void
	 */
	public static function gfp_stripe_create_error_message( $e, $mode = false ) {

		$error_class   = get_class( $e );
		$error_message = $e->getMessage();
		$response      = $error_class . ': ' . $error_message;

		self::$_this->log_error( print_r( $response, true ) );

		if ( ! $mode ) {
			$settings = get_option( 'gfp_stripe_settings' );
			$mode     = rgar( $settings, 'mode' );
		}

		if ( 'live' === $mode ) {

			switch ( $error_class ) {

				case 'PPP\Stripe\Error\InvalidRequest':
					$error_message = 'This form cannot process your payment. Please contact site owner.';
					break;
				case 'PPP\Stripe\Error\ApiConnection':
					$error_message = 'There was a temporary network communication error and while we try to make sure these never happen, sometimes they do. Please try your payment again in a few minutes and if this continues, please contact site owner.';
					break;
				case 'PPP\Stripe\Error\Card':
					break;
				default:
					$error_message = 'This form cannot process your payment. Please contact site owner.';

			}

		}

		return apply_filters( 'gfp_stripe_error_message', $error_message, $e );
	}

//------------------------------------------------------
//------------- ENTRY ---------------------------
//------------------------------------------------------

	/**
	 * @param $value
	 * @param $lead
	 * @param $field
	 * @param $form
	 *
	 * @return mixed|string
	 */
	public function gform_save_field_value( $value, $lead, $field, $form ) {

		if ( ! empty( self::$transaction_response ) ) {

			$input_type = RGFormsModel::get_input_type( $field );

			if ( ( 'creditcard' == $input_type ) && ( rgpost( "input_{$field['id']}_4" ) !== $value ) ) {

				$transaction_type = self::$transaction_response[ 'transaction_type' ];

				if ( 1 == $transaction_type ) {

					$value = self::$transaction_response[ 'customer' ]->default_source[ 'id' ];
				}

			}

		}

		return $value;
	}

	/**
	 * Save payment information to DB
	 *
	 * @since  1.7.9.1
	 *
	 * @uses   rgar()
	 * @uses   GFCommon::get_currency()
	 * @uses   rgpost()
	 * @uses   RGFormsModel::get_lead_details_table_name()
	 * @uses   wpdb->prepare()
	 * @uses   wpdb->get_results()
	 * @uses   RGFormsModel::get_lead_detail_id()
	 * @uses   wpdb->update()
	 * @uses   wpdb->insert()
	 * @uses   RGFormsModel::update_lead()
	 * @uses   apply_filters()
	 * @uses   GFP_Stripe::get_feed_that_meets_condition()
	 * @uses   gform_update_meta()
	 * @uses   GFP_Stripe_Data::insert_transaction()
	 *
	 * @param $entry
	 * @param $form
	 *
	 * @return $entry
	 */
	public function gform_entry_post_save( $entry, $form ) {

		global $wpdb;

		$entry_id = rgar( $entry, 'id' );

		if ( ! empty( self::$transaction_response ) ) {

			$transaction_id   = self::$transaction_response[ 'transaction_id' ];
			$transaction_type = self::$transaction_response[ 'transaction_type' ];

			$amount = array_key_exists( 'amount', self::$transaction_response ) ? self::$transaction_response[ 'amount' ] : null;

			$payment_date = gmdate( 'Y-m-d H:i:s' );

			$entry[ 'currency' ] = array_key_exists( 'currency', self::$transaction_response ) ? self::$transaction_response[ 'currency' ] : null;

			if ( '1' == $transaction_type ) {

				$entry[ 'payment_status' ] = 'Paid';
				$entry[ 'payment_amount' ] = $amount;
				$entry[ 'is_fulfilled' ]   = true;
				$entry[ 'transaction_id' ] = $transaction_id;
				$entry[ 'payment_date' ]   = $payment_date;

			}

			$entry[ 'transaction_type' ] = $transaction_type;

			$entry = apply_filters( 'gfp_stripe_entry_post_save_update_lead', $entry );

			GFAPI::update_entry( $entry );

			$feed = self::$_this->get_feed_that_meets_condition( $form );
			gform_update_meta( $entry_id, 'stripe_feed_id', $feed[ 'id' ] );

			gform_update_meta( $entry_id, 'payment_gateway', 'stripe' );

			$settings = get_option( 'gfp_stripe_settings' );
			$mode     = rgar( $settings, 'mode' );

			$transaction = apply_filters( 'gfp_stripe_entry_post_save_insert_transaction', array(
				'entry_id' => $entry[ 'id' ],
				'user_id'  => null,
				'type'     => 'payment',
				'id'       => $transaction_id,
				'amount'   => $amount,
				'mode'     => $mode,
				'meta'     => ''
			) );

			GFP_Stripe_Data::insert_transaction( $transaction[ 'entry_id' ], $transaction[ 'user_id' ], $transaction[ 'type' ], $transaction[ 'id' ], $transaction[ 'amount' ], $entry[ 'currency' ], $transaction[ 'mode' ], $transaction[ 'meta' ] );

			do_action( 'gfp_stripe_entry_post_save', $entry );

			if ( 1 == $transaction_type || 2 == $transaction_type ) {

				do_action( 'gform_post_payment_completed', $entry, array(
					'type'             => 'complete_payment',
					'amount'           => $transaction[ 'amount' ],
					'transaction_type' => $transaction[ 'type' ],
					'transaction_id'   => $transaction[ 'id' ],
					'subscription_id'  => ( 2 == $transaction_type ) ? self::$transaction_response[ 'subscription' ][ 'id' ] : false,
					'entry_id'         => $transaction[ 'entry_id' ],
					'payment_status'   => $entry[ 'payment_status' ],
					'payment_date'     => $payment_date,
					'payment_method'   => ( 2 == $transaction_type ) ? self::$transaction_response[ 'subscription' ][ 'customer' ]->default_source[ 'brand' ] : self::$transaction_response[ 'customer' ]->default_source[ 'brand' ]
				) );

			}

		}

		return $entry;

	}

	/**
	 *
	 * @since 1.8.2
	 *
	 * @param $form_id
	 * @param $entry
	 */
	public function gform_entry_detail_sidebar_middle( $form, $entry ) {

		if ( self::is_stripe_entry( $entry[ 'id' ] ) ) {

			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			wp_enqueue_script( 'gfp_stripe_entries_page_js', trailingslashit( GFP_STRIPE_URL ) . "js/entries-page{$suffix}.js", array( 'jquery' ), GFP_Stripe::get_version() );

			require_once( trailingslashit( GFP_STRIPE_PATH ) . 'includes/views/entry-detail-stripe-payment-details.php' );

		}

	}

	//------------------------------------------------------
	//------------- HELPERS --------------------------
	//------------------------------------------------------

	/**
	 * Validate Stripe API keys
	 *
	 * @since 0.1.0
	 *
	 * @uses  GFP_Stripe::include_api()
	 * @uses  get_option()
	 * @uses  PPP\Stripe\Stripe::setApiKey()
	 * @uses  PPP\Stripe\Token::create()
	 *
	 * @return array
	 */
	private function is_valid_key() {

		self::$_this->include_api();
		$settings = get_option( 'gfp_stripe_settings' );

		$year = date( 'Y' ) + 1;

		$valid_keys = array(
			'test_secret_key'      => false,
			'test_publishable_key' => false,
			'live_secret_key'      => false,
			'live_publishable_key' => false
		);
		$valid      = false;
		$flag_false = 0;

		foreach ( $valid_keys as $key => $value ) {

			if ( ! empty( $settings[ $key ] ) ) {

				try {

					PPP\Stripe\Stripe::setApiKey( $settings[ $key ] );
					PPP\Stripe\Token::create( array(
						                          'card' => array(
							                          'number'    => '4242424242424242',
							                          'exp_month' => 3,
							                          'exp_year'  => $year,
							                          'cvc'       => 314
						                          ),
					                          ) );
					$valid_keys[ $key ] = true;

				} catch ( Exception $e ) {

					$class   = get_class( $e );
					$message = $e->getMessage();

					if ( 'PPP\Stripe\Error\Card' == $class ) {
						$valid_keys[ $key ] = true;
					} else {
						$flag_false ++;
					}

					$errors[ $key ] = array( $class, $message );

				}

			} else {
				$flag_false ++;
			}

		}

		if ( 0 == $flag_false ) {
			$valid = true;

			return array( $valid, $valid_keys );
		} else {
			return array( $valid, $valid_keys, isset( $errors ) ? $errors : null );
		}

	}

	/**
	 * Return the desired API key from the database
	 *
	 * @since
	 *
	 * @uses get_option()
	 * @uses rgar()
	 * @uses esc_attr()
	 *
	 * @param      $type
	 * @param bool $mode
	 *
	 * @return string
	 */
	public static function get_api_key( $type, $mode = false ) {
		$settings = get_option( 'gfp_stripe_settings' );
		if ( ! $mode ) {
			$mode = rgar( $settings, 'mode' );
		}
		$key = $mode . '_' . $type . '_key';

		return trim( esc_attr( rgar( $settings, $key ) ) );

	}


	/**
	 * Include the Stripe library
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public static function include_api() {

		if ( ! class_exists( 'PPP\\Stripe\\Stripe' ) ) {
			require_once( GFP_STRIPE_PATH . '/includes/api/stripe-php/init.php' );
		}

	}

	/**
	 * Return the url of the plugin's root folder
	 *
	 * @since
	 *
	 * @uses plugins_url()
	 *
	 * @return string
	 */
	public static function get_base_url() {

		return plugins_url( null, GFP_STRIPE_FILE );

	}

	/**
	 * Return the physical path of the plugin's root folder
	 *
	 * @since
	 *
	 * @return string
	 */
	private static function get_base_path() {
		$folder = basename( dirname( GFP_STRIPE_FILE ) );

		return WP_PLUGIN_DIR . '/' . $folder;
	}

	/**
	 *
	 *
	 * @param $form
	 *
	 * @return bool
	 */
	public static function is_stripe_form( $form ) {
		$is_stripe_form = false;

		if ( is_numeric( $form ) ) {
			$form = RGFormsModel::get_form_meta( $form );
		}
		if ( ( ! $form == null ) && ( GFCommon::has_credit_card_field( $form ) ) ) {
			$form_feeds = GFP_Stripe_Data::get_feed_by_form( $form[ 'id' ], true );

			if ( ! empty( $form_feeds ) ) {
				$is_stripe_form = true;
			}
		}

		return $is_stripe_form;
	}

	/**
	 * Set transaction response
	 *
	 * @since
	 *
	 * @param $response
	 *
	 * @return void
	 */
	public static function set_transaction_response( $response ) {
		self::$transaction_response = $response;
	}

	/**
	 * @return string
	 */
	public static function get_transaction_response() {
		return self::$transaction_response;
	}

	public static function is_stripe_entry( $entry_id ) {
		$is_stripe_entry = false;
		$transaction     = GFP_Stripe_Data::get_transaction_by( 'entry', $entry_id );

		if ( ! empty( $transaction ) ) {
			$is_stripe_entry = true;
		}

		return $is_stripe_entry;
	}

	//------------------------------------------------------
	//------------- LOGGING --------------------------
	//------------------------------------------------------

	/**
	 * Add this plugin to Gravity Forms Logging Add-On
	 *
	 * @since
	 *
	 * @param $plugins
	 *
	 * @return mixed
	 */
	function gform_logging_supported( $plugins ) {

		$plugins[ self::$slug ] = 'Gravity Forms + Stripe';

		return $plugins;

	}

	/**
	 * Log an error message
	 *
	 * @since
	 *
	 * @uses GFLogging::include_logger()
	 * @uses GFLogging::log_message
	 *
	 * @param $message
	 *
	 * @return void
	 */
	public static function log_error( $message ) {

		if ( class_exists( 'GFLogging' ) ) {

			GFLogging::include_logger();

			GFLogging::log_message( self::$slug, $message, KLogger::ERROR );

		}

	}

	/**
	 * Log a debug message
	 *
	 * @since
	 *
	 * @uses GFLogging::include_logger()
	 * @uses GFLogging::log_message
	 *
	 * @param $message
	 *
	 * @return void
	 */
	public static function log_debug( $message ) {

		if ( class_exists( 'GFLogging' ) ) {

			GFLogging::include_logger();

			GFLogging::log_message( self::$slug, $message, KLogger::DEBUG );

		}
	}
}