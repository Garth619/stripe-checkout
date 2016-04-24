<?php
/**
 * Plugin Name: Storefront Checkout Customiser
 * Plugin URI: http://woothemes.com/products/storefront-checkout-customiser/
 * Description: Adds options to the customise the appearance of the checkout when using WooCommerce.
 * Version: 1.1.4
 * Author: WooThemes
 * Author URI: http://woothemes.com/
 * Requires at least: 4.0.0
 * Tested up to: 4.0.0
 *
 * Text Domain: storefront-checkout-customiser
 * Domain Path: /languages/
 *
 * @package Storefront_Checkout_Customiser
 * @category Core
 * @author James Koster
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) ) {
	require_once( 'woo-includes/woo-functions.php' );
}

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), '827bd9740d92b18dbed4a531b424d141', '538241' );

/**
 * Returns the main instance of Storefront_Checkout_Customiser to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object Storefront_Checkout_Customiser
 */
function Storefront_Checkout_Customiser() {
	return Storefront_Checkout_Customiser::instance();
} // End Storefront_Checkout_Customiser()

Storefront_Checkout_Customiser();

/**
 * Main Storefront_Checkout_Customiser Class
 *
 * @class Storefront_Checkout_Customiser
 * @version	1.0.0
 * @since 1.0.0
 * @package	Storefront_Checkout_Customiser
 */
final class Storefront_Checkout_Customiser {
	/**
	 * Storefront_Checkout_Customiser The single instance of Storefront_Checkout_Customiser.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;

	/**
	 * The token.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $token;

	/**
	 * The version number.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $version;

	// Admin - Start
	/**
	 * The admin object.
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $admin;

	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function __construct () {
		$this->token 			= 'storefront-checkout-customiser';
		$this->plugin_url 		= plugin_dir_url( __FILE__ );
		$this->plugin_path 		= plugin_dir_path( __FILE__ );
		$this->version 			= '1.1.3';

		register_activation_hook( __FILE__, array( $this, 'install' ) );

		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		add_action( 'init', array( $this, 'scc_setup' ) );

		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'scc_plugin_links' ) );
	} // End __construct()

	/**
	 * Main Storefront_Checkout_Customiser Instance
	 *
	 * Ensures only one instance of Storefront_Checkout_Customiser is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see Storefront_Checkout_Customiser()
	 * @return Main Storefront_Checkout_Customiser instance
	 */
	public static function instance () {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	} // End instance()

	/**
	 * Load the localisation file.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'storefront-checkout-customiser', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	} // End load_plugin_textdomain()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '1.0.0' );
	} // End __clone()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '1.0.0' );
	} // End __wakeup()

	/**
	 * Plugin page links
	 *
	 * @since  1.0.2
	 */
	public function scc_plugin_links( $links ) {
		$plugin_links = array(
			'<a href="http://support.woothemes.com/">' . __( 'Support', 'storefront-checkout-customiser' ) . '</a>',
			'<a href="http://docs.woothemes.com/document/storefront-checkout-customiser/">' . __( 'Docs', 'storefront-checkout-customiser' ) . '</a>',
		);

		return array_merge( $plugin_links, $links );
	}

	/**
	 * Installation. Runs on activation.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function install () {
		$this->_log_version_number();

		// get theme customizer url
		$section 		= 'storefront_checkout';
        $url 			= admin_url() . 'customize.php?autofocus[section]=' . $section;

		$notices 		= get_option( 'scc_activation_notice', array() );
		$notices[]		= sprintf( __( '%sThanks for installing the %sStorefront Checkout Customiser%s. This extension reveals a "Checkout" panel in the Customizer with various options. Read more about using this extension in the %sdocumentation%s or %sstart customising%s.%s', 'storefront-woocommerce-customiser' ), '<p>', '<strong>', '</strong>', '<a href="http://docs.woothemes.com/document/storefront-checkout-customiser/">', '</a>', '<a href="' . $url . '">', '</a>', '</p>' );

		update_option( 'scc_activation_notice', $notices );
	} // End install()

	/**
	 * Log the plugin version number.
	 * @access  private
	 * @since   1.0.0
	 * @return  void
	 */
	private function _log_version_number () {
		// Log the version number.
		update_option( $this->token . '-version', $this->version );
	} // End _log_version_number()

	/**
	 * Setup all the things, if Storefront or a child theme using Storefront that has not disabled the Customizer settings is active
	 * @return void
	 */
	public function scc_setup() {
		$theme = wp_get_theme();

		if ( 'Storefront' == $theme->name || 'storefront' == $theme->template && apply_filters( 'storefront_checkout_customizer_enabled', true ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'scc_script' ), 999 );
			add_action( 'customize_register', array( $this, 'scc_customize_register' ) );
			add_filter( 'body_class', array( $this, 'scc_body_class' ) );

			add_action( 'wp', array( $this, 'sdfc_prepare' ) );
			add_action( 'wp', array( $this, 'scc_two_step_checkout' ) );

			add_action( 'admin_notices', array( $this, 'customizer_notice' ) );

			// Hide the 'More' section in the customizer
			add_filter( 'storefront_customizer_more', '__return_false' );
		}
	}

	/**
	 * Display a notice linking to the Customizer
	 * @since   1.0.0
	 * @return  void
	 */
	public function customizer_notice() {
		$notices = get_option( 'scc_activation_notice' );

		if ( $notices = get_option( 'scc_activation_notice' ) ) {

			foreach ( $notices as $notice ) {
				echo '<div class="updated">' . $notice . '</div>';
			}

			delete_option( 'scc_activation_notice' );
		}
	}

	/**
	 * Enqueue CSS.
	 * @since   1.0.0
	 * @return  void
	 */
	public function scc_script() {
		$distraction_free 	= get_theme_mod( 'scc_distraction_free_checkout', false );
		$checkout_layout 	= get_theme_mod( 'scc_checkout_layout', 'default' );
		$two_step_checkout 	= get_theme_mod( 'scc_two_step_checkout', false );

		if ( true == $distraction_free && is_checkout() ) {
			wp_enqueue_style( 'scc-styles', plugins_url( '/assets/css/distraction-free.css', __FILE__ ), '', '1.0.0' );
		}

		if ( 'default' != $checkout_layout && is_checkout() ) {
			wp_enqueue_style( 'scc-layout', plugins_url( '/assets/css/checkout-layout.css', __FILE__ ), '', '1.0.2' );

			// Disable the sticky payment javascript
			wp_dequeue_script( 'storefront-sticky-payment' );
		}

		if ( true == $two_step_checkout && is_checkout() ) {
			wp_enqueue_style( 'scc-two-step', plugins_url( '/assets/css/two-step-checkout.css', __FILE__ ), '', '1.0.1' );
			wp_enqueue_script( 'flexslider', plugins_url( '/assets/js/jquery.flexslider.min.js', __FILE__ ), array( 'jquery' ), '2.5.0' );

			// Disable the sticky payment javascript
			wp_dequeue_script( 'storefront-sticky-payment' );
		}
	}

	/**
	 * Customizer Controls and settings
	 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
	 */
	public function scc_customize_register( $wp_customize ) {
		/**
	     * Checkout Section
	     */
	    $wp_customize->add_section( 'storefront_checkout' , array(
		    'title'      		=> __( 'Checkout', 'storefront-checkout-customiser' ),
		    'priority'   		=> 60,
		    'description' 		=> __( 'Customise the look & feel of the checkout', 'storefront-checkout-customiser' ),
		) );

		/**
		 * Distraction Free Checkout
		 */
		$wp_customize->add_setting( 'scc_distraction_free_checkout', array(
		        'default'           => false,
		    ) );

	    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'scc_distraction_free_checkout', array(
            'label'         	=> __( 'Distraction Free Checkout', 'storefront-checkout-customiser' ),
            'description' 		=> __( 'Toggle the distraction free checkout', 'storefront-checkout-customiser' ),
            'section'       	=> 'storefront_checkout',
            'settings'      	=> 'scc_distraction_free_checkout',
            'type'          	=> 'checkbox',
            'priority'			=> 10,
			'active_callback' 	=> 'is_checkout',
        ) ) );

        /**
	     * Checkout Layout
	     */
        $wp_customize->add_setting( 'scc_checkout_layout', array(
	        'default'           => 'default',
	    ) );


	    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'scc_checkout_layout', array(
            'label'         	=> __( 'Checkout layout', 'storefront-checkout-customiser' ),
            'section'       	=> 'storefront_checkout',
            'settings'      	=> 'scc_checkout_layout',
            'type'     			=> 'select',
            'priority'			=> 20,
			'active_callback' 	=> 'is_checkout',
            'choices'  			=> array(
            	'default'				=> 'Default',
            	'stacked'				=> 'Stacked',
            	'two-column-addreses'	=> 'Two column addresses',
			),
        ) ) );

        /**
		 * Two Step Checkout
		 */
		$wp_customize->add_setting( 'scc_two_step_checkout', array(
		        'default'           => false,
		    ) );

	    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'scc_two_step_checkout', array(
            'label'         	=> __( 'Two Step Checkout', 'storefront-checkout-customiser' ),
            'description' 		=> __( 'Toggle the two step checkout effect', 'storefront-checkout-customiser' ),
            'section'       	=> 'storefront_checkout',
            'settings'      	=> 'scc_two_step_checkout',
            'type'          	=> 'checkbox',
			'active_callback' 	=> 'is_checkout',
            'priority'			=> 30,
        ) ) );

		/**
		 * A prompt to visit the checkout page.
		 */
		$wp_customize->add_setting( 'scc_visit_checkout_prompt', array(
		        'default'           => '',
		    ) );

	    $wp_customize->add_control( new Arbitrary_Storefront_Control( $wp_customize, 'scc_visit_checkout_prompt', array(
            'description' 		=> sprintf( __( 'Please visit your %scheckout page%s to reveal the Customizer options.', 'storefront-checkout-customiser' ), '<a href="' . get_permalink( woocommerce_get_page_id( 'checkout' ) ) . '">', '</a>' ),
            'section'       	=> 'storefront_checkout',
			'type'				=> 'text',
            'settings'      	=> 'scc_visit_checkout_prompt',
			'active_callback' 	=> array( $this, 'is_not_checkout' ),
            'priority'			=> 10,
        ) ) );
	}

	/**
	 * Homepage callback
	 * @return bool
	 */
	public function is_not_checkout() {
		if ( ! is_checkout() ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Storefront Checkout Customiser Body Class
	 * @see get_theme_mod()
	 */
	public function scc_body_class( $classes ) {
		$checkout_layout 		= get_theme_mod( 'scc_checkout_layout', 'default' );
		$distraction_free 		= get_theme_mod( 'scc_distraction_free_checkout', false );

		if ( true == $distraction_free ) {
			$classes[] = 'scc-distraction-free-checkout';
		}

		if ( 'default' != $checkout_layout && is_checkout() ) {
			$classes[] = 'scc-' . $checkout_layout;
		}

		return $classes;
	}

	/**
	 * Create the distraction free checkout.
	 * @since   1.0.0
	 * @return  void
	 */
	public function sdfc_prepare() {
		$distraction_free = get_theme_mod( 'scc_distraction_free_checkout', false );

		if ( class_exists( 'WooCommerce' ) ) {

			if ( is_checkout() && $distraction_free ) {
				// Remove the distractions
				remove_action( 'storefront_header', 'storefront_secondary_navigation', 			30 );
				remove_action( 'storefront_header', 'storefront_primary_navigation', 			50 );
				remove_action( 'storefront_footer', 'storefront_footer_widgets', 				10 );
				remove_action( 'storefront_footer', 'storefront_credit', 						20 );
				remove_action( 'storefront_sidebar','storefront_get_sidebar',					10 );
				remove_action( 'storefront_content_top', 'woocommerce_breadcrumb', 				10 );
				remove_action( 'storefront_header', 'storefront_product_search', 				40 );
				remove_action( 'storefront_header', 'storefront_header_cart', 					60 );
				remove_action( 'storefront_before_content', 'storefront_header_widget_region', 	10 );
			}

		}
	}

	/**
	 * Create the two step checkout.
	 * @since   1.0.0
	 * @return  void
	 */
	public function scc_two_step_checkout() {
		$two_step_checkout 		= get_theme_mod( 'scc_two_step_checkout', false );

		if ( true == $two_step_checkout && is_checkout() ) {
			add_action( 'woocommerce_checkout_before_customer_details', array( $this, 'scc_checkout_form_wrapper_div' ), 1 );
			add_action( 'woocommerce_checkout_before_customer_details', array( $this, 'scc_checkout_form_wrapper' ), 2 );
			add_action( 'woocommerce_checkout_order_review', array( $this, 'scc_checkout_form_wrapper_div_close' ), 30 );
			add_action( 'woocommerce_checkout_order_review', array( $this, 'scc_checkout_form_wrapper_close' ), 30 );
			add_action( 'woocommerce_checkout_before_customer_details', array( $this, 'scc_address_wrapper' ), 5 );
			add_action( 'woocommerce_checkout_after_customer_details', array( $this, 'scc_address_wrapper_close' ) );
			add_action( 'wp_footer', array( $this, 'scc_fire_flexslider' ) );

			if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.3', '>=' ) ) {
				// 2.3 +
				add_action( 'woocommerce_checkout_before_order_review', array( $this, 'scc_order_review_wrap' ), 1 );
				add_action( 'woocommerce_checkout_after_order_review', array( $this, 'scc_order_review_wrap_close' ), 40 );
			} else {
				// < 2.3
				add_action( 'woocommerce_checkout_order_review', array( $this, 'scc_order_review_wrap' ), 1 );
				add_action( 'woocommerce_checkout_order_review', array( $this, 'scc_order_review_wrap_close' ), 40 );
			}
		}
	}

	public function scc_checkout_form_wrapper_div() {
		echo '<div class="checkout-slides">';

		?>
		<ul class="scc-checkout-control-nav">
			<li><a href="#"><?php _e( 'Your Details', 'storefront-checkout-customiser' ); ?></a></li>
			<li><a href="#"><?php _e( 'Your Order', 'storefront-checkout-customiser' ); ?></a></li>
		</ul>
		<?php
	}

	public function scc_checkout_form_wrapper_div_close() {
		echo '</div>';
	}

	public function scc_checkout_form_wrapper() {
		echo '<ul class="scc-two-step-checkout">';
	}

	public function scc_checkout_form_wrapper_close() {
		echo '</ul>';
	}

	public function scc_address_wrapper() {
		echo '<li class="scc-addresses">';
	}

	public function scc_address_wrapper_close() {
		echo '</li>';
	}

	public function scc_order_review_wrap() {
		echo '<li class="order-review">';
		echo '<h3 id="order_review_heading">' . __( 'Your order', 'storefront-checkout-customiser' ) . '</h3>';
	}

	public function scc_order_review_wrap_close() {
		echo '</li>';
	}

	public function scc_fire_flexslider() {
		?>
		<script>
			jQuery( window ).load(function() {
			    jQuery( '.checkout-slides' ).flexslider({
			    	selector: 		'.scc-two-step-checkout > li',
			    	slideshow: 		false,
			    	prevText: 		'<?php _e( 'Back to my details', 'storefront-checkout-customiser' ); ?>',
					nextText: 		'<?php _e( 'Proceed to payment', 'storefront-checkout-customiser' ); ?>',
					animationLoop: 	false,
					manualControls: '.scc-checkout-control-nav li a',
			    });

			    jQuery( '.flex-direction-nav a' ).removeAttr( 'href' ).addClass( 'button' );

				jQuery( '.flex-direction-nav a' ).click(function() {
					jQuery( 'html, body' ).animate( {
						scrollTop: jQuery( 'form.checkout' ).offset().top
					}, 400 );
				});

				jQuery( '.flex-direction-nav a' ).on( 'touchstart', function() {
					jQuery( 'body' ).animate( {
						scrollTop: jQuery( 'form.checkout' ).offset().top
					}, 400 );
				});
			});
		</script>
		<?php
	}

} // End Class