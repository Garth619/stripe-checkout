<?php
/*
 * Plugin Name: Stripe WooCommerce Addon
 * Plugin URI: https://wordpress.org/plugins/stripe-woocommerce-addon/
 * Description: This plugin adds a payment option in WooCommerce for customers to pay with their Credit Cards Via Stripe.
 * Version: 1.0.6
 * Author: Syed Nazrul Hassan
 * Author URI: https://nazrulhassan.wordpress.com/
 * Author Email : nazrulhassanmca@gmail.com
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * PCI Compliance by: Conner Imrie (https://github.com/cimrie) and Stephen Zuniga (https://github.com/stezu/)
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
function stripe_init()
{

	if(!class_exists('Stripe'))
	{
		include(plugin_dir_path( __FILE__ )."lib/Stripe.php");
	}
	function add_stripe_gateway_class( $methods )
	{
		$methods[] = 'WC_Stripe_Gateway';
		return $methods;
	}
	add_filter( 'woocommerce_payment_gateways', 'add_stripe_gateway_class' );

	if(class_exists('WC_Payment_Gateway'))
	{
		class WC_Stripe_Gateway extends WC_Payment_Gateway
		{

			public function __construct()
			{

				$this->id               = 'stripe';
				$this->icon             = plugins_url( 'images/stripe.png' , __FILE__ ) ;
				$this->has_fields       = true;
				$this->method_title     = 'Stripe Cards Settings';
				$this->init_form_fields();
				$this->init_settings();

				$this->supports                 = array( 'default_credit_card_form','products','refunds');

				$this->title               	   	= $this->get_option( 'stripe_title' );
				$this->stripe_description       = $this->get_option( 'stripe_description');
				$this->stripe_testpublickey     = $this->get_option( 'stripe_testpublickey' );
				$this->stripe_testsecretkey     = $this->get_option( 'stripe_testsecretkey' );
				$this->stripe_livepublickey     = $this->get_option( 'stripe_livepublickey' );
				$this->stripe_livesecretkey     = $this->get_option( 'stripe_livesecretkey' );
				
				$this->stripe_sandbox           = $this->get_option( 'stripe_sandbox' );
				$this->stripe_authorize_only    = $this->get_option( 'stripe_authorize_only' );
				$this->stripe_cardtypes         = $this->get_option( 'stripe_cardtypes');
				$this->stripe_enable_for_methods= $this->get_option( 'stripe_enable_for_methods', array() );
				$this->stripe_meta_cartspan     = $this->get_option( 'stripe_meta_cartspan');

				$this->stripe_zerodecimalcurrency      = array("BIF","CLP","DJF","GNF","JPY","KMF","KRW","MGA","PYG","RWF","VND","VUV","XAF","XOF","XPF");


				if(!defined("STRIPE_TRANSACTION_MODE"))
					{ define("STRIPE_TRANSACTION_MODE"  , ($this->stripe_authorize_only =='yes'? false : true)); }

				add_action( 'wp_enqueue_scripts', array( $this, 'load_stripe_scripts' ) );


				if('yes'  == $this->stripe_sandbox  )
					{ Stripe::setApiKey($this->stripe_testsecretkey);  }
				else
					{ Stripe::setApiKey($this->stripe_livesecretkey);  }

				if (is_admin())
				{
					add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
				}

				add_action('admin_notices' , array($this, 'do_ssl_check'    ));
			}

			

			public function do_ssl_check()
			{
				if( 'yes'  != $this->stripe_sandbox && "no" == get_option( 'woocommerce_force_ssl_checkout' )  && "yes" == $this->enabled ) {
					echo "<div class=\"error\"><p>". sprintf( __( "<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>" ), $this->method_title, admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) ."</p></div>";	
				}
			}


			public function load_stripe_scripts() {

				wp_enqueue_script( 'stripe', 'https://js.stripe.com/v2/', false, '2.0', true );

				wp_enqueue_script( 'stripewoojs', plugins_url( 'assets/js/stripewoo.js',  __FILE__  ), array( 'stripe', 'wc-credit-card-form' ), '', true );

				$stripe_array = array(
					'stripe_publishablekey'    => $this->stripe_sandbox == 'yes' ? $this->stripe_testpublickey : $this->stripe_livepublickey);


				if ( is_checkout_pay_page() ) {
					$order_key = urldecode( $_GET['key'] );
					$order_id  = absint( get_query_var( 'order-pay' ) );
					$order     = new WC_Order( $order_id );

					if ( $order->id == $order_id && $order->order_key == $order_key ) {
						$stripe_array['billing_name']      = $order->billing_first_name.' '.$order->billing_last_name;
						$stripe_array['billing_address_1'] = $order->billing_address_1;
						$stripe_array['billing_address_2'] = $order->billing_address_2;
						$stripe_array['billing_city']      = $order->billing_city;
						$stripe_array['billing_state']     = $order->billing_state;
						$stripe_array['billing_postcode']  = $order->billing_postcode;
						$stripe_array['billing_country']   = $order->billing_country;
					}
				}


				wp_localize_script( 'stripewoojs', 'stripe_array', $stripe_array );

			}


			public function admin_options()
			{
				?>
				<h3><?php _e( 'Stripe addon for Woocommerce', 'woocommerce' ); ?></h3>
				<p><?php  _e( 'Stripe is a company that provides a way for individuals and businesses to accept payments over the Internet.', 'woocommerce' ); ?></p>
				<table class="form-table">
					<?php $this->generate_settings_html(); ?>
				</table>
				<?php
			}



			public function init_form_fields()
			{

				$this->form_fields = array(
					'enabled' => array(
						'title' => __( 'Enable/Disable', 'woocommerce' ),
						'type' => 'checkbox',
						'label' => __( 'Enable Stripe', 'woocommerce' ),
						'default' => 'yes'
						),
					'stripe_title' => array(
						'title' => __( 'Title', 'woocommerce' ),
						'type' => 'text',
						'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
						'default' => __( 'Stripe', 'woocommerce' ),
						'desc_tip'      => true,
						),
					'stripe_description' => array(
						'title' => __( 'Description', 'woocommerce' ),
						'type' => 'textarea',
						'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
						'default' => __( 'Note: This form processes payments securely via &copy; <a href="https://stripe.com" target="_blank">Stripe</a>. Your card details <strong>never</strong> hit our server', 'woocommerce' ),
						'desc_tip'      => true,
						),
					'stripe_testsecretkey' => array(
						'title' => __( 'Test Secret Key', 'woocommerce' ),
						'type' => 'text',
						'description' => __( 'This is the Secret Key found in API Keys in Account Dashboard.', 'woocommerce' ),
						'default' => '',
						'desc_tip'      => true,
						'placeholder' => 'Stripe Test Secret Key'
						),

					'stripe_testpublickey' => array(
						'title' => __( 'Test Publishable Key', 'woocommerce' ),
						'type' => 'text',
						'description' => __( 'This is the Publishable Key found in API Keys in Account Dashboard.', 'woocommerce' ),
						'default' => '',
						'desc_tip'      => true,
						'placeholder' => 'Stripe Test Publishable Key'
						),
					
					'stripe_livesecretkey' => array(
						'title' => __( 'Live Secret Key', 'woocommerce' ),
						'type' => 'text',
						'description' => __( 'This is the Secret Key found in API Keys in Account Dashboard.', 'woocommerce' ),
						'default' => '',
						'desc_tip'      => true,
						'placeholder' => 'Stripe Live Secret Key'
						),

					'stripe_livepublickey' => array(
						'title' => __( 'Live Publishable Key', 'woocommerce' ),
						'type' => 'text',
						'description' => __( 'This is the Publishable Key found in API Keys in Account Dashboard.', 'woocommerce' ),
						'default' => '',
						'desc_tip'      => true,
						'placeholder' => 'Stripe Live Publishable Key'
						),
					

					'stripe_sandbox' => array(
						'title'       => __( 'Stripe Sandbox', 'woocommerce' ),
						'type'        => 'checkbox',
						'label'       => __( 'Enable stripe sandbox (Live Mode if Unchecked)', 'woocommerce' ),
						'description' => __( 'If checked its in sanbox mode and if unchecked its in live mode', 'woocommerce' ),
						'desc_tip'      => true,
						'default'     => 'no',
						),

					'stripe_authorize_only' => array(
						'title'       => __( 'Authorize Only', 'woocommerce' ),
						'type'        => 'checkbox',
						'label'       => __( 'Enable Authorize Only Mode (Authorize & Capture If Unchecked)', 'woocommerce' ),
						'description' => __( 'If checked will only authorize the credit card only upon checkout.', 'woocommerce' ),
						'desc_tip'      => true,
						'default'     => 'no',
						),

					'stripe_cardtypes' => array(
						'title'    => __( 'Accepted Cards', 'woocommerce' ),
						'type'     => 'multiselect',
						'class'    => 'chosen_select',
						'css'      => 'width: 350px;',
						'desc_tip' => __( 'Select the card types to accept.', 'woocommerce' ),
						'options'  => array(
							'mastercard'       => 'MasterCard',
							'visa'             => 'Visa',
							'discover'         => 'Discover',
							'amex' 		       => 'American Express',
							'jcb'		       => 'JCB',
							'dinersclub'       => 'Dinners Club',
							),
						'default' => array( 'mastercard', 'visa', 'discover', 'amex' ),
						),


					'stripe_meta_cartspan' => array(
						'title'       => __( 'Enable CartSpan', 'woocommerce' ),
						'type'        => 'checkbox',
						'label'       => __( 'Enable <a href="http://www.cartspan.com/">CartSpan</a> to Stores Last4 & Brand of Card (Active If Checked)', 'woocommerce' ),
						'description' => __( 'If checked will store last4 and card brand in local db from charge object.', 'woocommerce' ),
						'desc_tip'      => true,
						'default'     => 'no',
						)
);
}



  		//Function to check IP
		function get_client_ip()
		{
			$ipaddress = '';
			if (getenv('HTTP_CLIENT_IP'))
				$ipaddress = getenv('HTTP_CLIENT_IP');
			else if(getenv('HTTP_X_FORWARDED_FOR'))
				$ipaddress = getenv('HTTP_X_FORWARDED_FOR');
			else if(getenv('HTTP_X_FORWARDED'))
				$ipaddress = getenv('HTTP_X_FORWARDED');
			else if(getenv('HTTP_FORWARDED_FOR'))
				$ipaddress = getenv('HTTP_FORWARDED_FOR');
			else if(getenv('HTTP_FORWARDED'))
				$ipaddress = getenv('HTTP_FORWARDED');
			else if(getenv('REMOTE_ADDR'))
				$ipaddress = getenv('REMOTE_ADDR');
			else
				$ipaddress = '0.0.0.0';
			return $ipaddress;
		}

		//End of function to check IP

		public function get_description() {
			return apply_filters( 'woocommerce_gateway_description',$this->stripe_description, $this->id );
		}


		/*Is Avalaible*/
		public function is_available() {
			if ( ! in_array( get_woocommerce_currency(), apply_filters( 'stripe_woocommerce_supported_currencies', array( 'AED','ALL','ANG','ARS','AUD','AWG','BBD','BDT','BIF','BMD','BND','BOB','BRL','BSD','BWP','BZD','CAD','CHF','CLP','CNY','COP','CRC','CVE','CZK','DJF','DKK','DOP','DZD','EGP','ETB','EUR','FJD','FKP','GBP','GIP','GMD','GNF','GTQ','GYD','HKD','HNL','HRK','HTG','HUF','IDR','ILS','INR','ISK','JMD','JPY','KES','KHR','KMF','KRW','KYD','KZT','LAK','LBP','LKR','LRD','MAD','MDL','MNT','MOP','MRO','MUR','MVR','MWK','MXN','MYR','NAD','NGN','NIO','NOK','NPR','NZD','PAB','PKR','PLN','PYG','QAR','RUB','SAR','SBD','SCR','SEK','SGD','SHP','SLL','SOS','STD','SVC','SZL','THB','TOP','TTD','TWD','TZS','UAH','UGX','USD','UYU','UZS','VND','VUV','WST','XAF','XOF','XPF','YER','ZAR','AFN','AMD','AOA','AZN','BAM','BGN','CDF','GEL','KGS','LSL','MGA','MKD','MZN','RON','RSD','RWF','SRD','TJS','TRY','XCD','ZMW' ) ) ) ) 
			{ return false; }


			if(empty($this->stripe_testpublickey) || empty($this->stripe_testsecretkey) || empty($this->stripe_livepublickey) || empty($this->stripe_livesecretkey) ) 
			{ return false;	}

			return true;
		}
		/*end is availaible*/



		/*Get Icon*/
		public function get_icon() {
			$icon = '';
			if(is_array($this->stripe_cardtypes))
			{
				foreach ( $this->stripe_cardtypes as $card_type ) {

					if ( $url = $this->stripe_get_active_card_logo_url( $card_type ) ) {

						$icon .= '<img width="45" src="'.esc_url( $url ).'" alt="'.esc_attr( strtolower( $card_type ) ).'" />';
					}
				}
			}
			else
			{
				$icon .= '<img  src="'.esc_url( plugins_url( 'images/stripe.png' , __FILE__ ) ).'" alt="Stripe Gateway" />';
			}

			return apply_filters( 'woocommerce_stripe_icon', $icon, $this->id );
		}

		public function stripe_get_active_card_logo_url( $type ) {

			$image_type = strtolower( $type );
			return  WC_HTTPS::force_https_url( plugins_url( 'images/' . $image_type . '.png' , __FILE__ ) );
		}

		


		/*Process Payment*/

		public function process_payment( $order_id )
		{
			global $error;
			global $woocommerce;
			$wc_order 	    = wc_get_order( $order_id );
			$grand_total 	= $wc_order->order_total;


			if(in_array($wc_order->get_order_currency() ,$this->stripe_zerodecimalcurrency ))
			{
				$amount 	     = number_format($grand_total,0,".","") ;
			}
			else
			{
				$amount 	     = $grand_total * 100 ;
			}

			try
			{


				// create token for customer/buyer credit card
				$token_id = sanitize_text_field($_POST['stripe_token']);
				$charge = Stripe_Charge::create(array(
					'amount' 	     		=> $amount,
					'currency' 				=> $wc_order->get_order_currency(),
					'card'					=> $token_id,
					'capture'				=> STRIPE_TRANSACTION_MODE,
					'statement_descriptor'  => 'Online Shopping',
					'metadata' 				=> array(
						'Order #' 	  		=> $wc_order->get_order_number(),
						'Total Tax'      	=> $wc_order->get_total_tax(),
						'Total Shipping' 	=> $wc_order->get_total_shipping(),
						'Customer IP'	  	=> $this->get_client_ip(),
						'WP customer #'  	=> $wc_order->user_id,
						'Billing Email'  	=> $wc_order->billing_email,
						) ,
					'receipt_email'         => $wc_order->billing_email,
					'description'  			=> get_bloginfo('blogname').' Order #'.$wc_order->get_order_number(),
					'shipping' 		    	=> array(
						'address' => array(
							'line1'			=> $wc_order->shipping_address_1,
							'line2'			=> $wc_order->shipping_address_2,
							'city'			=> $wc_order->shipping_city,
							'state'			=> $wc_order->shipping_state,
							'country'		=> $wc_order->shipping_country,
							'postal_code'	=> $wc_order->shipping_postcode
							),
						'name' => $wc_order->shipping_first_name.' '.$wc_order->shipping_last_name,
						'phone'=> $wc_order->billing_phone
						)

					)
				);

				if($token_id !='')
				{
					if ($charge->paid == true)
					{

						$epoch     = $charge->created;
						$dt        = new DateTime("@$epoch");
						$timestamp = $dt->format('Y-m-d H:i:s e');
						$chargeid  = $charge->id ;

						$wc_order->add_order_note(__( 'Payment completed at-'.$timestamp.',Charge ID='.$charge->id.',Card='.$charge->source->brand.' : '.$charge->source->last4.' : '.$charge->source->exp_month.'/'.$charge->source->exp_year,'woocommerce'));

						$wc_order->payment_complete($chargeid);
						WC()->cart->empty_cart();

						if('yes' == $this->stripe_meta_cartspan)
						{
							$stripe_metas_for_cartspan = array(
								'cc_type' 			=> $charge->source->brand,
								'cc_last4' 			=> $charge->source->last4,
								'cc_trans_id' 		=> $charge->id,
								);
							add_post_meta( $order_id, '_stripe_metas_for_cartspan', $stripe_metas_for_cartspan);
						}


					if(true == $charge->captured && true == $charge->paid)
					{
						add_post_meta( $order_id, '_stripe_charge_status', 'charge_auth_captured');
					}

					if(false == $charge->captured && true == $charge->paid)
					{
						add_post_meta( $order_id, '_stripe_charge_status', 'charge_auth_only');
					}


						return array (
							'result'   => 'success',
							'redirect' => $this->get_return_url( $wc_order ),
							);
					}
					else
					{
						$wc_order->add_order_note( __( 'Stripe payment failed.'.$error, 'woocommerce' ) );
						wc_add_notice($error, $notice_type = 'error' );

					}

				}
			}

			catch (Exception $e)
			{

				$body         = $e->getJsonBody();
				$error        = $body['error']['message'];
				$wc_order->add_order_note( __( 'Stripe payment failed due to.'.$error, 'woocommerce' ) );
				wc_add_notice($error,  $notice_type = 'error' );
			}




		} // end of function process_payment()


		/*process refund function*/
		public function process_refund($order_id, $amount = NULL, $reason = '' ) {


			if($amount > 0 )
			{
				$CHARGE_ID 	= get_post_meta( $order_id , '_transaction_id', true );
				$charge 		= Stripe_Charge::retrieve($CHARGE_ID);
				$refund 		= $charge->refunds->create(
					array(
						'amount' 		=> $amount * 100,
						'metadata'	=> array('Order #' 		=> $order_id,
							'Refund reason' => $reason
							),
						)
					);
				if($refund)
				{

					$repoch      = $refund->created;
					$rdt         = new DateTime("@$repoch");
					$rtimestamp  = $rdt->format('Y-m-d H:i:s e');
					$refundid    = $refund->id;
					$wc_order    = new WC_Order( $order_id );
					$wc_order->add_order_note( __( 'Stripe Refund completed at. '.$rtimestamp.' with Refund ID = '.$refundid , 'woocommerce' ) );
					return true;
				}
				else
				{
					return false;
				}


			}
			else
			{
				return false;
			}



		}// end of  process_refund()



	}  // end of class WC_Stripe_Gateway

} // end of if class exist WC_Gateway

}

/*Activation hook*/
add_action( 'plugins_loaded', 'stripe_init' );

function stripe_woocommerce_addon_activate() {

	if(!function_exists('curl_exec'))
	{
		wp_die( '<pre>This plugin requires PHP CURL library installled in order to be activated </pre>' );
	}
}
register_activation_hook( __FILE__, 'stripe_woocommerce_addon_activate' );
/*Activation hook*/

/*Plugin Settings Link*/


function stripe_woocommerce_addon_settings_link( $links ) {
	$settings_link = '<a href="admin.php?page=wc-settings&tab=checkout&section=wc_stripe_gateway">' . __( 'Settings' ) . '</a>';
	array_push( $links, $settings_link );
	return $links;
}
$plugin = plugin_basename( __FILE__ );
add_filter( "plugin_action_links_$plugin", 'stripe_woocommerce_addon_settings_link' );
/*Plugin Settings Link*/

/*Capture Charge*/

function stripe_capture_meta_box() {
	global $post;
	$chargestatus = get_post_meta( $post->ID, '_stripe_charge_status', true );
	if($chargestatus == 'charge_auth_only')
	{
			add_meta_box(
				'stripe_capture_chargeid',
				__( 'Capture Charge', 'woocommerce' ),
				'stripe_capture_meta_box_callback',
				'shop_order',
				'side',
				'default'
			);
	}
}
add_action( 'add_meta_boxes', 'stripe_capture_meta_box' );


function stripe_capture_meta_box_callback( $post ) {

	//charge_auth_only, charge_auth_captured, charge_auth_captured_later
	echo '<input type="checkbox" name="_stripe_capture_charge" value="1"/>&nbsp;Check & Save Order to Capture';
}


/*Execute charge on order save*/
function stripe_capture_meta_box_action($order_id, $items )
{
	if(isset($items['_stripe_capture_charge']) && (1 ==$items['_stripe_capture_charge']) ) 
	{
		global $post;
		$chargeid = get_post_meta( $post->ID, '_transaction_id', true );
		if(class_exists('WC_Stripe_Gateway'))
		{
			$stripepg = new WC_Stripe_Gateway();

			if('yes'  == $stripepg->stripe_sandbox  )
			{ Stripe::setApiKey($stripepg->stripe_testsecretkey);  }
			else
			{ Stripe::setApiKey($stripepg->stripe_livesecretkey);  }

		}


		$capturecharge   = Stripe_Charge::retrieve($chargeid);
		$captureresponse = $capturecharge->capture();

		
		if(true == $captureresponse->captured && true == $captureresponse->paid)
		{
			$epoch     = $captureresponse->created;
			$dt        = new DateTime("@$epoch"); 
			$timestamp = $dt->format('Y-m-d H:i:s e');

			$wc_order = new WC_Order($order_id);
			update_post_meta( $order_id, '_stripe_charge_status', 'charge_auth_captured_later');
			
			$wc_order->add_order_note(__( 'Stripe charge captured at-'.$timestamp.',Charge ID='.$captureresponse->id.',Card='.$captureresponse->source->brand.' : '.$captureresponse->source->last4.' : '.$captureresponse->source->exp_month.'/'.$captureresponse->source->exp_year,'woocommerce'));
			unset($wc_order);
		}

	}	

}
add_action ("woocommerce_saved_order_items", "stripe_capture_meta_box_action", 10,2);
/*Execute charge on order save*/

add_action('admin_notices', 'stripe_admin_notice');

function stripe_admin_notice() {
	global $current_user ;
        $user_id = $current_user->ID;
        /* Check that the user hasn't already clicked to ignore the message */
	if ( ! get_user_meta($user_id, 'stripe_ignore_notice') ) {
        echo '<div class="wc_plugin_upgrade_notice">'; 
        printf(__('Stripe WooCommerce Addon 1.0.6 is a major release of this plugin and requires you to <a href="%2$s">Re Configure Plugin Settings</a> for PCI Compliance | <a href="%1$s">Hide Notice</a>'), '?stripe_warning_ignore=0','admin.php?page=wc-settings&tab=checkout&section=wc_stripe_gateway');
        echo "</div>";
	}
}

add_action('admin_init', 'stripe_warning_ignore');

function stripe_warning_ignore() {
	global $current_user;
        $user_id = $current_user->ID;
        if ( isset($_GET['stripe_warning_ignore']) && '0' == $_GET['stripe_warning_ignore'] ) {
             add_user_meta($user_id, 'stripe_ignore_notice', 'true', true);
	}
}