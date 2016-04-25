<?php

/**
 * Class GFP_Stripe_Importer
 *
 * Imports current Stripe customer information into WP user info
 *
 * @since 1.8.13.1
 */
class GFP_Stripe_Importer {

	private $processed_customers = array();

	private $form = array();

	private $rule_id = 0.0;

	private $default_card = null;

	private $new_entry_id = 0;

	public function __construct() {

		if ( is_admin() ) {

			add_action( 'gfp_more_stripe_activate', array( $this, 'gfp_more_stripe_activate' ) );

			if ( 'gf_settings' == RGForms::get( 'page' ) ) {

				add_action( 'gfp_stripe_before_uninstall_button', array(
					$this,
					'gfp_stripe_before_uninstall_button'
				), 9 );
				add_filter( 'gfp_stripe_settings_page_action', array( $this, 'gfp_stripe_settings_page_action' ) );
				add_filter( 'gform_noconflict_scripts', array( $this, 'gform_noconflict_scripts' ) );

			}

			if ( in_array( RG_CURRENT_PAGE, array( 'admin-ajax.php' ) ) ) {

				add_action( 'wp_ajax_import_get_form_rules', array(
					$this,
					'import_get_form_rules'
				) );

				add_action( 'wp_ajax_import_current_stripe_customers', array(
					$this,
					'import_current_stripe_customers'
				) );

			}

			if ( function_exists( 'members_get_capabilities' ) ) {
				add_filter( 'members_get_capabilities', array( $this, 'members_get_capabilities' ) );
			}
		}
	}

	public function gfp_more_stripe_activate() {

		$this->add_permissions();

	}

	private function add_permissions() {

		global $wp_roles;

		$wp_roles->add_cap( 'administrator', 'gfp_stripe_import' );

	}

	/**
	 * Provide the Members plugin with this plugin's list of capabilities
	 *
	 * @since 1.8.2
	 *
	 * @param $caps
	 *
	 * @return array
	 */
	public function members_get_capabilities( $caps ) {

		return array_merge( $caps, array( 'gfp_stripe_import' ) );

	}

	public function gform_noconflict_scripts( $noconflict_scripts ) {

		$noconflict_scripts = array_merge( $noconflict_scripts, array( 'gfp_more_stripe_settings_page_import_js' ) );

		return $noconflict_scripts;
	}

	public function gfp_stripe_before_uninstall_button( $settings ) {

		if ( ( GFCommon::current_user_can_any( 'gfp_stripe_import' ) ) && ( rgar( $settings, 'enable_early_access' ) ) ) {

			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			wp_enqueue_script( 'gfp_more_stripe_settings_page_import_js', trailingslashit( GFP_MORE_STRIPE_URL ) . "includes/importer/js/settings-page-import{$suffix}.js", array( 'jquery' ), GFPMoreStripe::get_version() );
			wp_localize_script( 'gfp_more_stripe_settings_page_import_js', 'more_stripe_import_vars', array( 'nonce'                => wp_create_nonce( 'import_current_stripe_customers' ),
			                                                                                                 'get_form_rules_nonce' => wp_create_nonce( 'import_get_form_rules' )
			) );

			$forms = GFAPI::get_forms();

			if ( ! empty( $forms ) ) {

				foreach ( $forms as $form ) {
					$form_list[ ] = array(
						'id'    => $form[ 'id' ],
						'title' => $form[ 'title' ]
					);
				}

			}

			require_once( GFP_MORE_STRIPE_PATH . '/includes/importer/views/settings-page-import.php' );
		}

	}

	public function import_get_form_rules() {

		check_ajax_referer( 'import_get_form_rules', 'import_get_form_rules' );

		$form_id = rgpost( 'form' );

		if ( empty( $form_id ) ) {
			wp_send_json_error( __( 'No form ID', 'gravityforms-stripe-more' ) );
		}

		$feeds      = GFP_Stripe_Data::get_feed_by_form( $form_id, true );
		$form_rules = '';
		$form_rules .= '<option value="">' . __( 'Select Stripe rule', 'gravityforms-stripe-more' ) . '</option>';

		foreach ( $feeds as $feed ) {
			$form_rules .= '<option value="' . $feed[ 'id' ] . '">' . $feed[ 'meta' ][ 'rule_name' ] . '</option>';
		}

		wp_send_json_success( $form_rules );
	}

	public function gfp_stripe_settings_page_action() {

		if ( isset( $_POST[ 'more_stripe_import' ] ) ) {

			check_admin_referer( 'more_stripe_import', 'gfp_more_stripe_import' );

			?>
			<div class="updated fade" style="padding:20px;">
				<?php _e( 'Import complete. A report has been sent to your admin email. Or, you can check the log if the Logging Add-On is enabled.', 'gravityforms-stripe-more' ); ?>
			</div>
		<?php

		}

	}

	public function import_current_stripe_customers() {

		check_ajax_referer( 'import_current_stripe_customers', 'import_current_stripe_customers' );

		$form_id       = rgpost( 'form' );
		$this->rule_id = rgpost( 'rule' );

		if ( ! empty( $form_id ) ) {
			$this->form = GFAPI::get_form( $form_id );
		}

		GFP_Stripe::log_debug( __( 'IMPORTER: Stripe customer import triggered...', 'gravityforms-stripe-more' ) );

		if ( GFP_Stripe::$do_usage_stats ) {
			do_action( 'gfp_stripe_usage_event', 'import_current_stripe_customers' );
		}

		$api_key = GFP_Stripe::get_api_key( 'secret', 'live' );

		$limit     = 10;
		$args      = array( 'limit' => $limit, 'include' => array( 'total_count' ) );
		$customers = array();
		$results   = array();

		GFP_Stripe::log_debug( __( "IMPORTER: Retrieving the first {$limit} customers...", 'gravityforms-stripe-more' ) );

		$customer_list = PPP_Stripe_API::list_customers( $api_key, $args );

		if ( ! is_object( $customer_list ) ) {
			$error_message = __( "IMPORTER: Unable to retrieve customers: {$customer_list}", 'gravityforms-stripe-more' );
			GFP_Stripe::log_error( $error_message );
		}

		if ( ! empty( $error_message ) ) {

			$results[ ] = $error_message;

		} else {

			$customers = $customer_list[ 'data' ];
			$has_more  = $customer_list[ 'has_more' ];

			GFP_Stripe::log_debug( __( "IMPORTER: Importing {$customer_list['total_count']} customers", 'gravityforms-stripe-more' ) );

			if ( $has_more ) {

				set_time_limit( 300 );

				while ( $has_more ) {

					$last_customer = end( $customers );
					reset( $customers );
					$starting_after = $last_customer[ 'id' ];

					GFP_Stripe::log_debug( __( "IMPORTER: Retrieving the next {$limit} customers after {$starting_after}", 'gravityforms-stripe-more' ) );

					$args          = array( 'limit' => $limit, 'starting_after' => $starting_after );

					$customer_list = PPP_Stripe_API::list_customers( $api_key, $args );

					if ( ! is_object( $customer_list ) ) {

						$error_message = __( "IMPORTER: Unable to retrieve customers: {$customer_list}", 'gravityforms-stripe-more' );

						GFP_Stripe::log_error( $error_message );

						break;

					}

					$customers = array_merge( $customers, $customer_list[ 'data' ] );
					$has_more  = $customer_list[ 'has_more' ];

				}

			}

			if ( ! empty( $error_message ) ) {

				$results[ ] = $error_message;

			} else {

				GFP_Stripe::log_debug( sprintf( __( "IMPORTER: Retrieved %d customers from your Stripe account. Now saving customer data.", 'gravityforms-stripe-more' ), count( $customers ) ) );

				$results[ ] = sprintf( __( "Retrieved %d customers from your Stripe account.", 'gravityforms-stripe-more' ), count( $customers ) );

				foreach ( $customers as $customer ) {

					if ( empty( $customer[ 'email' ] ) ) {

						$no_email_message = '*' . $customer[ 'id' ] . ': ' . __( 'Stripe customer does not have an email address.', 'gravityforms-stripe-more' );

						GFP_Stripe::log_debug( $no_email_message );

						$results[ ] = $no_email_message;

					} else {

						GFP_Stripe::log_debug( __( "Stripe customer: {$customer['email']}", 'gravityforms-stripe-more' ) );
						GFP_Stripe::log_debug( __( "IMPORTER: Email address or user ID in this Stripe customer's metadata is a WP user?", 'gravityforms-stripe-more' ) );

						$is_wp_user = $this->import_is_wp_user( $customer );

						if ( $is_wp_user ) {

							GFP_Stripe::log_debug( __( "IMPORTER: Stripe customer ID saved to this user?", 'gravityforms-stripe-more' ) );

							$is_stripe_customer = GFP_More_Stripe_Customer_API::get_stripe_customer_id( $is_wp_user->ID, true );

							if ( ! empty( $is_stripe_customer ) ) {

								if ( is_string( $is_stripe_customer ) && $is_stripe_customer == $customer[ 'id' ] ) {

									GFP_Stripe::log_debug( __( 'IMPORTER: This customer is already saved. Moving on to next customer.', 'gravityforms-stripe-more' ) );

									$results[ ] = '*' . sprintf( __( '%s already exists as user %d', 'gravityforms-stripe-more' ), $customer[ 'id' ], $is_wp_user->ID );

								} else if ( is_string( $is_stripe_customer ) ) {

									GFP_Stripe::log_debug( __( 'IMPORTER: There is a customer ID for this user, but it is not this customer ID. Saving customer ID to this WP user.', 'gravityforms-stripe-more' ) );

									$results[ ] = $this->import_save_stripe_customer_info( $is_wp_user->ID, $customer );

								} else if ( is_array( $is_stripe_customer ) ) {

									GFP_Stripe::log_debug( __( "IMPORTER: There are multiple customer IDs for this user ID. Let's see if this is one of them", 'gravityforms-stripe-more' ) );

									$already_saved = false;

									foreach ( $is_stripe_customer as $date_created => $id ) {

										if ( $id == $customer[ 'id' ] ) {

											$already_saved = true;

											break;

										}

									}

									if ( $already_saved ) {

										GFP_Stripe::log_debug( __( 'IMPORTER: This customer is already saved. Moving on to next customer.', 'gravityforms-stripe-more' ) );

										$results[ ] = '*' . sprintf( __( '%s already exists as user %d', 'gravityforms-stripe-more' ), $customer[ 'id' ], $is_wp_user->ID );

									} else {

										GFP_Stripe::log_debug( __( 'IMPORTER: this customer ID was not saved to this user. Saving customer ID to this WP user.', 'gravityforms-stripe-more' ) );

										$results[ ] = $this->import_save_stripe_customer_info( $is_wp_user->ID, $customer );

									}

								}

							} else {

								GFP_Stripe::log_debug( __( "IMPORTER: No customer ID found. Saving Stripe information to this WP user.", 'gravityforms-stripe-more' ) );

								$results[ ] = $this->import_save_stripe_customer_info( $is_wp_user->ID, $customer );

							}

						} else {

							GFP_Stripe::log_debug( __( 'No. Creating new WP user', 'gravityforms-stripe-more' ) );

							$user_id = $this->import_create_wp_user( $customer );

							if ( is_wp_error( $user_id ) ) {

								$results[ ] = '*' . $customer[ 'id' ] . ': ' . $user_id->get_error_message( $user_id->get_error_code() );

								continue;

							}
							GFP_Stripe::log_debug( __( "New user {$user_id} created. Saving Stripe information to this WP user.", 'gravityforms-stripe-more' ) );

							$results[ ] = $this->import_save_stripe_customer_info( $user_id, $customer );

						}

					}

					$this->processed_customers[ $customer[ 'id' ] ] = $customer;

				}

			}

		}

		GFP_Stripe::log_debug( __( "Finished saving customer data. Sending email.", 'gravityforms-stripe-more' ) );

		$to      = get_option( 'admin_email' );
		$subject = __( '[gravity+] Import Stripe Customers to WordPress REPORT', 'gravityforms-stripe-more' );
		$message = '';

		foreach ( $results as $result ) {

			if ( is_array( $result ) ) {

				$message .= $result[ 'customer_id' ] . "\r\n";
				$message .= '......Cards' . "\r\n";

				foreach ( $result[ 'cards' ] as $card ) {
					$message .= '......' . $card . "\r\n";
				}

				if ( array_key_exists( 'subscriptions', $result ) ) {

					$message .= '......Subscriptions' . "\r\n";

					foreach ( $result[ 'subscriptions' ] as $subscription ) {
						$message .= '......' . $subscription . "\r\n";
					}

				}

				if ( array_key_exists( 'currency', $result ) ) {
					$message .= '......Currency: ' . strtoupper( $result[ 'currency' ] ) . "\r\n";
				}

			} else {
				$message .= $result . "\r\n";
			}

		}

		$sent = wp_mail( $to, $subject, $message );

		if ( ! empty( $error_message ) ) {
			wp_send_json_error( $error_message );
		} else {
			wp_send_json_success();
		}

	}

	/**
	 * @param Stripe_Customer $stripe_customer
	 *
	 * @return bool
	 */
	public function import_is_wp_user( $stripe_customer ) {

		$is_wp_user = false;

		if ( ! empty( $stripe_customer->metadata[ 'wp_user_id' ] ) ) {

			$user = get_user_by( 'id', $stripe_customer->metadata[ 'wp_user_id' ] );

			if ( $user ) {

				$is_wp_user = $user;

				GFP_Stripe::log_debug( __( "IMPORTER: WP user ID {$user->ID} from customer metadata is valid.", 'gravityforms-stripe-more' ) );
			}

		} else {

			$user = get_user_by( 'email', $stripe_customer[ 'email' ] );

			if ( $user ) {

				$is_wp_user = $user;

				GFP_Stripe::log_debug( __( "IMPORTER: {$stripe_customer['email']} already exists as WP user ID {$user->ID}.", 'gravityforms-stripe-more' ) );

				GFP_More_Stripe_Customer_API::add_metadata_to_customer( '', $stripe_customer, array( 'wp_user_id' => $user->ID ) );

			}

		}

		return $is_wp_user;
	}

	/**
	 * @param $customer
	 *
	 * @return int|null|WP_Error
	 */
	public function import_create_wp_user( $customer ) {

		if ( 0 < $customer->sources[ 'total_count' ] ) {

			foreach ( $customer->sources[ 'data' ] as $source ) {

				//TODO what if default source is not a card?
				if ( ('card' == $source['object']) && ($source[ 'id' ] == $customer[ 'default_source' ]) ) {

					$card = $source;

					$this->default_card = $card;
					$name               = $card[ 'name' ];

					break;
				}

			}

		}

		$first_name = ( ! empty( $name ) ) ? strstr( $name, ' ', true ) : '';
		$last_name  = ( ! empty( $name ) ) ? trim( strstr( $name, ' ' ) ) : '';

		$user_id    = GFP_Stripe_Helper::create_wp_user( $first_name, $last_name, $customer[ 'email' ], $customer[ 'email' ] );

		return $user_id;
	}

	/**
	 * @param $user_id
	 * @param $customer
	 *
	 * @return array
	 */
	public function import_save_cards( $user_id, $customer ) {

		$results_args = array();

		foreach ( $customer->sources[ 'data' ] as $source ) {

			if ( 'card' == $source['object'] ) {

				$card = $source;

				$make_default = true;

				if ( $card[ 'id' ] !== $customer[ 'default_source' ] ) {

					$make_default = false;

				} else {

						$this->default_card = $card;

				}

				GFP_Stripe::log_debug( __( "IMPORTER: Saving card {$card['id']}, make_default = {$make_default}", 'gravityforms-stripe-more' ) );

				GFP_More_Stripe_Customer_API::save_new_card( $user_id, $card, $make_default );

				$results_args[ ] = $card[ 'id' ];
			}

		}

		return $results_args;
	}

	/**
	 * @param $user_id
	 * @param $customer
	 *
	 * @return array
	 */
	public function import_save_subscriptions( $user_id, $customer ) {

		$results_args = array();

		foreach ( $customer->subscriptions[ 'data' ] as $subscription ) {

			$currency_info     = GFP_Stripe_Helper::get_currency_info( strtoupper( $subscription->plan[ 'currency' ] ) );

			$subscription_data = array(
				'id'           => $subscription[ 'id' ],
				'customer'     => $customer,
				'status'       => $subscription[ 'status' ],
				'start'        => $subscription[ 'current_period_start' ],
				'end'          => $subscription[ 'current_period_end' ],
				'next_payment' => null,
				'plan'         => array(
					'id'             => $subscription->plan[ 'id' ],
					'amount'         => ( 0 == $currency_info[ 'decimals' ] ) ? $subscription->plan[ 'amount' ] : ( $subscription->plan[ 'amount' ] / 100 ),
					'interval'       => $subscription->plan[ 'interval' ],
					'interval_count' => $subscription->plan[ 'interval_count' ],
					'name'           => $subscription->plan[ 'name' ]
				),
				'trial_end'    => $subscription[ 'trial_end' ],
				'trial_start'  => $subscription[ 'trial_start' ],
				'entry_id'     => empty( $this->new_entry_id ) ? null : $this->new_entry_id,
				'setup_fee'    => null
			);

			GFP_Stripe::log_debug( __( "IMPORTER: Saving subscription {$subscription['id']}", 'gravityforms-stripe-more' ) );

			GFP_More_Stripe_Customer_API::save_subscription( $user_id, $subscription_data );

			if ( ! empty( $this->new_entry_id ) ) {

				gform_update_meta( $this->new_entry_id, 'stripe_subscription', array( $subscription_data ) );
				gform_update_meta( $this->new_entry_id, 'gfp_stripe_customer_id', $customer[ 'id' ] );

				if ( 0 !== $this->rule_id ) {
					gform_update_meta( $this->new_entry_id, 'stripe_feed_id', $this->rule_id );
				}

			}

			$results_args[ ] = $subscription[ 'id' ];

		}

		return $results_args;
	}

	/**
	 * @param $user_id
	 * @param $customer
	 *
	 * @return array
	 */
	public function import_save_stripe_customer_info( $user_id, $customer ) {

		$results               = $customer_ids = array();
		$make_default_customer = $delete_current_data = false;

		$results[ 'customer_id' ] = $customer[ 'id' ];
		$results[ 'user_id' ]     = $user_id;


		if ( 0 < $customer->subscriptions[ 'total_count' ] ) {

			$active_subscriptions = GFP_More_Stripe_Customer_API::get_active_subscriptions( $user_id );

			if ( empty( $active_subscriptions ) ) {
				$make_default_customer = true;
			}

		}

		GFP_Stripe::log_debug( __( "Saving customer ID {$customer['id']}", 'gravityforms-stripe-more' ) );

		$current_customer_info = GFP_More_Stripe_Customer_API::get_stripe_customer_id( $user_id, true );

		if ( ! empty( $current_customer_info ) && is_string( $current_customer_info ) ) {

			if ( $make_default_customer ) {

				GFP_Stripe::log_debug( __( "Making it the default customer ID for this user", 'gravityforms-stripe-more' ) );

				$customer_ids[ $this->processed_customers[ $current_customer_info ][ 'created' ] ] = $current_customer_info;
				$customer_ids[ 0 ]                                                                 = $customer[ 'id' ];
				$delete_current_data                                                               = true;

			} else {

				$customer_ids[ 0 ]                      = $current_customer_info;
				$customer_ids[ $customer[ 'created' ] ] = $customer[ 'id' ];

			}

		} else if ( ! empty( $current_customer_info ) && is_array( $current_customer_info ) ) {

			if ( $make_default_customer ) {

				GFP_Stripe::log_debug( __( "Making it the default customer ID for this user", 'gravityforms-stripe-more' ) );

				$customer_ids                                                                           = $current_customer_info;
				$customer_ids[ $this->processed_customers[ $current_customer_info[ 0 ] ][ 'created' ] ] = $customer_ids[ 0 ];
				$customer_ids[ 0 ]                                                                      = $customer[ 'id' ];
				$delete_current_data                                                                    = true;

			} else {

				$customer_ids                           = $current_customer_info;
				$customer_ids[ $customer[ 'created' ] ] = $customer[ 'id' ];

			}

		} else if ( empty( $current_customer_info ) ) {

			$customer_ids[ 0 ] = $customer[ 'id' ];

		}

		if ( ! empty( $customer_ids ) ) {
			update_user_meta( $user_id, '_gfp_stripe_customer_id', $customer_ids );
		}

		if ( $delete_current_data ) {

			GFP_More_Stripe_Customer_API::remove_all_cards( $user_id );

			GFP_Stripe::log_debug( __( "Removing user {$user_id}'s active subscriptions", 'gravityforms-stripe-more' ) );

			delete_user_meta( $user_id, '_gfp_stripe_subscription_active' );

		}

		//-cards
		if ( 0 < $customer->sources[ 'total_count' ] ) {

			$results[ 'cards' ] = $this->import_save_cards( $user_id, $customer );

		}

		if ( 0 < $customer->subscriptions[ 'total_count' ] ) {

			$this->import_create_entry( $user_id );

			$results[ 'subscriptions' ] = $this->import_save_subscriptions( $user_id, $customer );

			$save_currency              = update_user_meta( $user_id, '_gfp_stripe_currency', strtoupper( $customer[ 'currency' ] ) );

			if ( $save_currency ) {
				$results[ 'currency' ] = $customer[ 'currency' ];
			}

		}

		$metadata[ 'wp_user_id' ] = $user_id;

		if ( ! empty( $this->new_entry_id ) ) {

			$metadata[ 'gravity_form' ]       = $this->form[ 'id' ];
			$metadata[ 'gravity_form_entry' ] = $this->new_entry_id;

		}

		GFP_More_Stripe_Customer_API::add_metadata_to_customer( '', $customer, $metadata );

		return $results;
	}

	public function import_create_entry( $user_id ) {

		$entry = array();

		if ( empty( $this->form ) ) {
			return false;
		}

		$entry[ 'form_id' ]    = $this->form[ 'id' ];
		$entry[ 'created_by' ] = $user_id;

		$card_field = GFP_Stripe::get_creditcard_field( $this->form );

		if ( $card_field && ! empty( $this->default_card ) ) {
//TODO what if default source is not a card?
			$entry[ "{$card_field['id']}.1" ] = $this->default_card[ 'id' ];
			$entry[ "{$card_field['id']}.4" ] = $this->default_card[ 'brand' ];

		}

		$feeds = GFP_Stripe_Data::get_feed_by_form( $this->form[ 'id' ], true );

		if ( ! $feeds ) {
			return false;
		}

		if ( 1 < count( $feeds ) ) {

			foreach ( $feeds as $feed ) {

				if ( empty( $feed[ 'meta' ][ 'customer_fields' ][ 'first_name' ] ) || empty( $feed[ 'meta' ][ 'customer_fields' ][ 'last_name' ] ) || empty( $feed[ 'meta' ][ 'customer_fields' ][ 'email' ] ) ) {
					continue;
				} else {
					break;
				}

			}

		} else {

			$feed = $feeds[ 0 ];

		}

		$user                                                          = get_user_by( 'id', $user_id );

		$entry[ $feed[ 'meta' ][ 'customer_fields' ][ 'first_name' ] ] = $user->first_name;
		$entry[ $feed[ 'meta' ][ 'customer_fields' ][ 'last_name' ] ]  = $user->last_name;
		$entry[ $feed[ 'meta' ][ 'customer_fields' ][ 'email' ] ]      = $user->user_email;

		if ( ! empty( $this->default_card ) ) {

			$entry[ $feed[ 'meta' ][ 'customer_fields' ][ 'address1' ] ] = $this->default_card[ 'address_line1' ];
			$entry[ $feed[ 'meta' ][ 'customer_fields' ][ 'address2' ] ] = $this->default_card[ 'address_line2' ];
			$entry[ $feed[ 'meta' ][ 'customer_fields' ][ 'city' ] ]     = $this->default_card[ 'address_city' ];
			$entry[ $feed[ 'meta' ][ 'customer_fields' ][ 'state' ] ]    = $this->default_card[ 'address_state' ];
			$entry[ $feed[ 'meta' ][ 'customer_fields' ][ 'zip' ] ]      = $this->default_card[ 'address_zip' ];
			$entry[ $feed[ 'meta' ][ 'customer_fields' ][ 'country' ] ]  = $this->default_card[ 'address_country' ];

		}

		$entry_id = GFAPI::add_entry( $entry );

		if ( ! is_wp_error( $entry_id ) && ! is_wp_error( GFAPI::get_entry( $entry_id ) ) ) {

			$this->new_entry_id = $entry_id;

			GFP_Stripe_Helper::add_note( $entry_id, 'Manually created by Stripe importer' );

			return true;

		} else {

			return false;

		}

	}

}