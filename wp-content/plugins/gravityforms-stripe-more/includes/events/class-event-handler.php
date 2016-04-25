<?php
/**
 * @package   GFP_More_Stripe
 * @copyright 2014-2015 press+
 * @license   GPL-2.0+
 * @since     1.9.2.1
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * GFP_Stripe_Event_Handler Class
 *
 * Receives & processes Stripe events
 *
 * @since 1.9.2.1
 * */
class GFP_Stripe_Event_Handler extends PPP_Stripe_Event {

	/**
	 * @var GFP_Stripe_DB_Events
	 */
	private $events_db = null;

	/**
	 * Constructor
	 *
	 * @since 1.9.2.1
	 *
	 * @param array $args
	 */
	public function __construct( $args ) {


		$this->events_db = $args[ 'db' ];


		add_action( 'parse_request', array( $this, 'parse_request' ) );

		add_filter( 'ppp_stripe_event_api_key', array( $this, 'ppp_stripe_event_api_key' ), 10, 4 );

		add_filter( 'ppp_stripe_pre_process_event', array( $this, 'ppp_stripe_pre_process_event' ), 10, 3 );

		add_action( 'ppp_stripe_post_process_event', array( $this, 'ppp_stripe_post_process_event' ), 10, 2 );


		add_filter( 'gform_notification_events', array( $this, 'gform_notification_events' ) );

		if ( 'gf_entries' == RGForms::get( 'page' ) ) {

			add_filter( 'esc_html', array( $this, 'esc_html' ), 10, 2 );

		}

		add_filter( 'gform_notes_avatar', array( $this, 'gform_notes_avatar' ), 10, 2 );


		parent::__construct( $args );

	}

	/**
	 * Receive Stripe event notification
	 *
	 * @since 1.9.2.1
	 */
	public function parse_request() {

		if ( class_exists( 'RGForms' ) ) {

			$endpoint = RGForms::get( 'page' );

			if ( 'gfp_more_stripe_listener' == $endpoint ) {
				$this->process_event( $endpoint );
			}

		}

		return;
	}

	/**
	 * Cancel processing this event if it's already been processed
	 *
	 * @since 1.9.2.1
	 *
	 * @param bool             $cancel_processing
	 * @param string           $plugin
	 * @param PPP\Stripe_Event $event
	 *
	 * @return bool
	 */
	public function ppp_stripe_pre_process_event( $cancel_processing, $plugin, $event ) {

		if ( GFP_MORE_STRIPE_SLUG == $plugin ) {

			$already_processed = $this->events_db->exists( $event[ 'id' ] );

			if ( $already_processed ) {

				GFP_Stripe::log_debug( 'This event has already been processed.' );

				$cancel_processing = true;
			}

			//For backwards compatibility
			$sanitized_event_type = str_replace( '.', '_', $event[ 'type' ] );

			if ( has_action( 'gfp_more_stripe_event' ) || has_action( "gfp_more_stripe_event_{$sanitized_event_type}" ) ) {

				add_action( 'ppp_stripe_process_event', array( $this, 'ppp_stripe_process_event' ), 10, 2 );

			}

			GFP_Stripe::log_debug( 'Before gfp_more_stripe_pre_event.' );

			$cancel_processing = apply_filters( 'gfp_more_stripe_pre_event', $cancel_processing, $event );

		}

		return $cancel_processing;
	}

	/**
	 * Retrieve correct API key
	 *
	 * @since 1.9.2.1
	 *
	 * @param string           $api_key
	 * @param string           $plugin
	 * @param PPP\Stripe\Event $event
	 * @param array|null       $args
	 *
	 * @return string
	 */
	public function ppp_stripe_event_api_key( $api_key, $plugin, $event, $args = null ) {

		if ( GFP_MORE_STRIPE_SLUG == $plugin ) {

			if ( 'process_event' == $event && ! empty( $args[ 'event' ] ) ) {

				$mode = $this->get_mode( $args[ 'event' ] );

				$api_key = GFP_Stripe::get_api_key( 'secret', $mode );

			} else if ( 'parse_invoice' == $event && ! empty( $args[ 'invoice' ] ) ) {

				$mode = $this->get_mode( $args[ 'invoice' ] );

				$api_key = GFP_Stripe::get_api_key( 'secret', $mode );
			}

		}

		return $api_key;
	}

	/**
	 * Call More Stripe event hooks, for backwards compatibility
	 *
	 * @since 1.9.2.1
	 *
	 * @param string $plugin
	 * @param array  $human_event
	 */
	public function ppp_stripe_process_event( $plugin, $human_event ) {

		if ( GFP_MORE_STRIPE_SLUG == $plugin ) {

			GFP_Stripe::log_debug( 'Before gfp_more_stripe_event.' );


			do_action( 'gfp_more_stripe_event', $human_event );

			$sanitized_event_type = str_replace( '.', '_', $human_event[ 'event_type' ] );

			do_action( "gfp_more_stripe_event_{$sanitized_event_type}", $human_event );


			GFP_Stripe::log_debug( 'After gfp_more_stripe_event.' );

		}

	}

	/**
	 * Add event to events DB
	 *
	 * @since 1.9.2.1
	 *
	 * @param string           $plugin
	 * @param PPP\Stripe\Event $event
	 */
	public function ppp_stripe_post_process_event( $plugin, $event ) {

		if ( GFP_MORE_STRIPE_SLUG == $plugin ) {

			$this->events_db->add( array(
				                       'event_id'     => $event[ 'id' ],
				                       'date_created' => date( 'Y-m-d H:i:s', $event[ 'created' ] )
			                       ) );


			GFP_Stripe::log_debug( 'Before gfp_more_stripe_post_event.' );

			do_action( 'gfp_more_stripe_post_event', $event );

		}

	}

	//------------------------------------------------------
	//------------- EVENTS ------------------------
	//------------------------------------------------------

	/**
	 * Handles failed, refunded, or captured one-time payments,
	 *
	 * Does not handle failed or successful charges for invoice payments.
	 *
	 * @param $event
	 * @param $charge
	 * @param $api_key
	 */
	protected function do_charge_event() {

		GFP_Stripe::log_debug( 'Doing charge event.' );

		$charge = $this->event_object;

		$event_name = explode( '.', $this->event[ 'type' ] );

		if ( 3 == count( $event_name ) ) {

			$sub_resource = $event_name[ 1 ];
			$action       = $event_name[ 2 ];

			if ( 'dispute' == $sub_resource && 'created' == $action ) {
				$action = 'disputed';
			}

		} else {

			$action = $event_name[ 1 ];

		}
		//Possible actions are succeeded, failed, refunded, captured, updated, dispute.created, dispute.updated, dispute.closed
		if ( $this->is_invoice_charge( $charge ) ) {

			GFP_Stripe::log_debug( 'This is an invoice charge' );

			if ( 'succeeded' == $action && ! $this->has_description( $charge ) ) {

				GFP_Stripe::log_debug( 'This is a successful charge without a description — update the description and return' );

				$charge_args = array( 'description' => "Subscription payment: " . $charge[ 'invoice' ] );
				PPP_Stripe_API::update_charge( $this->api_key, $charge, $charge_args );

				return;

			} else if ( 'failed' == $action ) {

				GFP_Stripe::log_debug( 'This is a failed charge — nothing to do so return' );

				return;

			}

		}

		if ( 'succeeded' == $action ) {

			GFP_Stripe::log_debug( 'This is a successful charge — nothing to do so return' );

			return;
		}

		if ( 'updated' == $action ) {

			GFP_Stripe::log_debug( 'This is an updated charge — nothing to do so return' );

			return;
		}

		$customer_id = $charge[ 'customer' ];

		$gf_info = $this->get_gf_info_from_customer( $this->api_key, $customer_id );

		if ( ! empty( $gf_info ) ) {

			$user_id = ! empty( $gf_info[ 'user_id' ] ) ? $gf_info[ 'user_id' ] : false;
			$entry   = $gf_info[ 'entry' ];
			$form    = $gf_info[ 'form' ];

			$human_charge = $this->parse_event( $this->event );

			$note_type = null;

			if ( 'succeeded' == $action || 'captured' == $action ) {

				$note_type = 'success';

			} else if ( 'failed' == $action ) {

				$note_type = 'error';

			}

			$note = $this->create_charge_note( $action, $human_charge );

			if ( ! empty( $note ) ) {

				GFPMoreStripe::add_note( $entry[ 'id' ], $note, $note_type );

			}

			GFPMoreStripe::update_payment_info( $entry, $action, $user_id, $customer_id );

			if ( ( ! empty( $form ) ) && ( ! empty ( $entry ) ) ) {

				$this->send_notification( 'stripe_' . $this->event[ 'type' ], $form, $entry );

			}

			if ( 'refunded' == $action ) {

				$last_refund = end( $human_charge[ 'refunds' ] );
				reset( $human_charge[ 'refunds' ] );

				GFP_Stripe::log_debug( 'Triggering gform_post_payment_refunded action' );

				do_action( 'gform_post_payment_refunded', $entry, array(
					'type'             => 'refund_payment',
					'amount'           => $last_refund[ 'amount' ],
					'transaction_type' => 'refund',
					'transaction_id'   => $last_refund[ 'id' ],
					'entry_id'         => $entry[ 'id' ],
					'payment_status'   => 'Refunded',
					'payment_method'   => $human_charge[ 'payment_card_brand' ],
					'payment_date'     => $last_refund[ 'date' ]
				) );

			}

		} else {

			GFP_Stripe::log_error( "Unable to find entry for {$customer_id}." );

		}
	}

	/*private function do_dispute_event ( $event, $dispute, $api_key ) {

	}*/

	protected function do_subscription_event() {

		GFP_Stripe::log_debug( 'Doing subscription event...' );

		$subscription = $this->event_object;
		$event_name   = explode( '.', $this->event[ 'type' ] );
		$action       = $event_name[ 2 ];

		if ( 'created' == $action ) {

			GFP_Stripe::log_debug( 'This is a creatd subscription — nothing to do so return' );

			return;
		}

		//Possible actions are created, updated, deleted, trial_will_end
		$customer_id = $subscription[ 'customer' ];

		$gf_info = $this->get_gf_info_from_customer( $this->api_key, $customer_id );

		if ( ! empty( $gf_info ) ) {

			$user_id = ! empty( $gf_info[ 'user_id' ] ) ? $gf_info[ 'user_id' ] : false;
			$entry   = $gf_info[ 'entry' ];
			$form    = $gf_info[ 'form' ];

			$human_subscription = $this->parse_event( $this->event );

			if ( 'deleted' == $action ) {

				if ( 'Canceled' == $entry[ 'payment_status' ] ) {

					GFP_Stripe::log_debug( 'Subscription already canceled.' );

				} else {

					GFP_Stripe::log_debug( 'Do canceled subscription actions...' );

					GFPMoreStripe::do_canceled_subscription_actions( $entry, $user_id, $customer_id, $subscription );

				}
			}

			if ( 'updated' == $action ) {

				foreach ( $human_subscription[ 'updates' ] as $attribute => $update ) {

					if ( 'status' == $attribute ) {

						GFPMoreStripe::update_entry_meta_subscription( $entry[ 'id' ], array( 'status' => $update[ 1 ] ) );

						if ( ! empty( $user_id ) ) {

							GFPMoreStripe::update_saved_subscription_attribute( $user_id, $subscription[ 'id' ], 'status', $update[ 1 ] );

						}

						break;

					}

				}

			}

			if ( 'deleted' !== $action ) {

				$note = $this->create_subscription_note( $action, $human_subscription );

				if ( ! empty( $note ) ) {

					GFPMoreStripe::add_note( $entry[ 'id' ], $note );

				}

			}

			if ( ( ! empty( $form ) ) && ( ! empty ( $entry ) ) ) {

				$this->send_notification( 'stripe_' . $this->event[ 'type' ], $form, $entry );

			}

		} else {

			GFP_Stripe::log_error( "Unable to find entry for {$customer_id}." );

		}

	}

	protected function do_invoice_event() {

		GFP_Stripe::log_debug( 'Doing invoice event.' );

		$invoice    = $this->event_object;
		$event_name = explode( '.', $this->event[ 'type' ] );
		$action     = $event_name[ 1 ];

		//created, updated, payment_succeeded, payment_failed

		if ( 'created' == $action || 'updated' == $action ) {

			GFP_Stripe::log_debug( 'Invoice created or updated — nothing to do so return' );

			return;
		}

		$customer_id = $invoice[ 'customer' ];

		$gf_info = $this->get_gf_info_from_customer( $this->$api_key, $customer_id );

		if ( ! empty( $gf_info ) ) {

			$user_id = ! empty( $gf_info[ 'user_id' ] ) ? $gf_info[ 'user_id' ] : false;
            
            if ( ! empty( $gf_info['entry'] ) ) {
			$entry   = $gf_info[ 'entry' ];
            }
            
            if ( ! empty( $gf_info['form'] ) ) {
			$form    = $gf_info[ 'form' ];
            }

			$human_invoice = $this->parse_event( $this->event );

			$note_type = null;

			if ( 'payment_failed' == $action ) {

				$note_type = 'error';

			} else if ( 'payment_succeeded' == $action ) {

				$note_type = 'success';

			}

			$note = $this->create_invoice_note( $action, $human_invoice );

			if ( ! empty( $note ) ) {

				GFPMoreStripe::add_note( $entry[ 'id' ], $note, $note_type );

			}

			if ( ( ! empty( $form ) ) && ( ! empty ( $entry ) ) ) {

				$this->send_notification( 'stripe_' . $this->event[ 'type' ], $form, $entry );

			}

			if ( 'payment_failed' == $action ) {

				GFP_Stripe::log_debug( 'Triggering gform_subscription_payment_failed action' );

				do_action( 'gform_subscription_payment_failed', $entry, $invoice[ 'subscription' ] );

				GFP_Stripe::log_debug( 'Triggering gform_post_fail_subscription_payment action' );

				do_action( 'gform_post_fail_subscription_payment', $entry, array(
					'id'              => $this->event[ 'id' ],
					'subscription_id' => $invoice[ 'subscription' ],
					'entry_id'        => $entry[ 'id' ],
					'transaction_id'  => $invoice[ 'charge' ],
					'type'            => 'fail_subscription_payment',
					'amount'          => $invoice[ 'amount_due' ]
				) );

			} else if ( 'payment_succeeded' == $action ) {

				$transaction_ids_to_check[ ] = $invoice[ 'id' ];

				if ( ! empty( $invoice[ 'charge' ] ) ) {

					$transaction_ids_to_check[ ] = $invoice[ 'charge' ];

				}

				$already_processed = GFP_Stripe_Helper::check_for_transaction( $transaction_ids_to_check );

				if ( empty( $already_processed ) ) {

					GFP_Stripe::log_debug( 'Transaction hasn\'t been processed yet, so let\'s do that' );

					$meta = array(
						'entry_id' => $entry[ 'id' ],
						'object'   => $invoice->__toArray( true )
					);

					$transaction_id = empty( $invoice[ 'charge' ] ) ? $invoice[ 'id' ] : $invoice[ 'charge' ];

					GFP_Stripe_Data::insert_transaction( 0, $user_id, 'event', $transaction_id, $human_invoice[ 'amount_due' ], strtoupper( $invoice[ 'currency' ] ), PPP_Stripe_API::get_object_mode( $invoice ), $meta );

					GFP_Stripe::log_debug( 'Triggering gform_post_add_subscription_payment action' );

					do_action( 'gform_post_add_subscription_payment', $entry, array(
						'id'              => $this->event[ 'id' ],
						'subscription_id' => $invoice[ 'subscription' ],
						'entry_id'        => $entry[ 'id' ],
						'type'            => 'add_subscription_payment',
						'amount'          => $invoice[ 'amount_due' ]
					) );

				} else {

					GFP_Stripe::log_debug( "{$invoice['id']} already exists in transaction table. Will not process." );

				}

			}

		} else {

			GFP_Stripe::log_error( "Unable to find entry for {$customer_id}." );

		}

	}

	//------------------------------------------------------
	//------------- HUMAN TRANSLATION ------------------------
	//------------------------------------------------------

	//------------------------------------------------------
	//------------- NOTES ------------------------
	//------------------------------------------------------

	public function esc_html( $safe_text, $text ) {

		if ( $this->is_event_note( $text ) ) {
			$safe_text = $text;
		}

		return $safe_text;
	}

	private function is_event_note( $text ) {

		$is_event_note = false;

		$trace = debug_backtrace();
		$level = $trace[ 5 ];

		if ( 'notes_grid' == $level[ 'function' ] ) {

			foreach ( $level[ 'args' ][ 0 ] as $arg ) {

				if ( $text == $arg->value && 'Stripe' == $arg->user_name ) {
					$is_event_note = true;
					break;
				}

			}

		}

		return $is_event_note;
	}

	public function gform_notes_avatar( $avatar, $note ) {

		if ( 'Stripe' == $note->user_name ) {
			$avatar = '<span class="icon-stripe"></span>';
		}

		return $avatar;
	}

	//------------------------------------------------------
	//------------- NOTIFICATIONS ------------------------
	//------------------------------------------------------

	/**
	 * Add Stripe events to Gravity Forms notification actions
	 *
	 * @since 1.9.2.1
	 *
	 * @param array $events
	 *
	 * @return array
	 */
	public function gform_notification_events( $events ) {

		$events[ 'stripe_charge.failed' ]                        = __( 'Stripe one-time payment failed', 'gravityforms-stripe-more' );
		$events[ 'stripe_invoice.payment_failed' ]               = __( 'Stripe subscription payment failed', 'gravityforms-stripe-more' );
		$events[ 'stripe_invoice.payment_succeeded' ]            = __( 'Stripe subscription payment succeeded', 'gravityforms-stripe-more' );
		$events[ 'stripe_customer.subscription.updated' ]        = __( 'Stripe subscription changed', 'gravityforms-stripe-more' );
		$events[ 'stripe_customer.subscription.deleted' ]        = __( 'Stripe subscription ended', 'gravityforms-stripe-more' );
		$events[ 'stripe_customer.subscription.trial_will_end' ] = __( 'Stripe subscription trial will end in 3 days', 'gravityforms-stripe-more' );

		return $events;
	}

	/**
	 *
	 *
	 * @since 1.9.2.1
	 *
	 * @param string $id Notification ID
	 * @param array  $form
	 * @param array  $lead
	 */
	private function send_notification( $id, $form, $lead ) {

		GFP_Stripe::log_debug( "Sending {$id} notification" );

		$notifications         = GFCommon::get_notifications_to_send( $id, $form, $lead );
		$notifications_to_send = array();

		//running through filters that disable form submission notifications
		foreach ( $notifications as $notification ) {

			if ( apply_filters( "gform_disable_notification_{$form['id']}", apply_filters( 'gform_disable_notification', false, $notification, $form, $lead ), $notification, $form, $lead ) ) {
				//skip notifications if it has been disabled by a hook
				continue;
			}

			$notifications_to_send[ ] = $notification[ 'id' ];
		}

		GFCommon::send_notifications( $notifications_to_send, $form, $lead, true, $id );
	}

	//------------------------------------------------------
	//------------- UTILITIES ------------------------
	//------------------------------------------------------

	/**
	 * @param $api_key
	 * @param $customer_id
	 *
	 * @return array
	 */
	public function get_gf_info_from_customer( $api_key, $customer_id ) {

		GFP_Stripe::log_debug( "Getting GF info for customer: {$customer_id}" );

		$gf_info         = $metadata = array();
		$update_metadata = false;

		$customer = PPP_Stripe_API::retrieve( 'customer', $api_key, $customer_id );

		if ( is_object( $customer ) ) {

			GFP_Stripe::log_debug( "Customer retrieved from Stripe." );

			$user_id_attached = ! empty( $customer->metadata[ 'wp_user_id' ] );

			GFP_Stripe::log_debug( "Has WP user ID attached? {$user_id_attached}" );

			if ( $user_id_attached ) {

				GFP_Stripe::log_debug( "attached user ID: {$customer->metadata['wp_user_id']}" );

				$user = get_user_by( 'id', $customer->metadata[ 'wp_user_id' ] );


			} else {

				GFP_Stripe::log_debug( "{$customer['id']} did not have a user ID attached as metadata. Searching WP usermeta for customer ID..." );

				$user_id = GFP_More_Stripe_Customer_API::get_user_id_from_customer_id( $customer[ 'id' ] );

				if ( ! empty( $user_id ) ) {

					$update_metadata = true;

					$metadata[ 'wp_user_id' ] = $user_id;

				}

			}

			if ( ! empty( $user ) ) {

				GFP_Stripe::log_debug( "Valid WP user found." );

				$gf_info[ 'user_id' ] = $user->ID;

				GFP_Stripe::log_debug( "User ID {$user->ID}" );

			} else if ( ! empty( $user_id ) ) {

				$gf_info[ 'user_id' ] = $user_id;

				GFP_Stripe::log_debug( "User ID {$user_id}" );

			}

			if ( ! empty( $customer->metadata[ 'gravity_form_entry' ] ) ) {

				GFP_Stripe::log_debug( "Setting GF entry {$customer->metadata['gravity_form_entry']} from customer metadata" );

				$entry_id = $customer->metadata[ 'gravity_form_entry' ];

			} else {

				if ( ! $customer[ 'deleted' ] ) {

					$update_metadata = true;

				}

				$entry_id = GFP_Stripe_Helper::get_legacy_entry_id_from_customer_id( $customer_id );
			}

			if ( ! empty( $entry_id ) ) {

				GFP_Stripe::log_debug( "Getting entry {$entry_id}" );

				$entry = RGFormsModel::get_lead( $entry_id );

			}

			if ( ! empty( $entry ) ) {

				GFP_Stripe::log_debug( "Entry has been found: {$entry['id']}" );

				$metadata[ 'gravity_form_entry' ] = $entry[ 'id' ];

				$gf_info[ 'entry' ] = $entry;

				$form = RGFormsModel::get_form_meta( $entry[ 'form_id' ] );

				if ( ! empty( $form ) ) {

					GFP_Stripe::log_debug( "Form has been found: {$form['id']}" );

					$gf_info[ 'form' ] = $form;

				}

			} else {

				GFP_Stripe::log_debug( "Unable to find entry" );

				$gf_info         = array();
				$update_metadata = false;

			}

			if ( $update_metadata ) {

				GFP_More_Stripe_Customer_API::add_metadata_to_customer( '', $customer, $metadata );

			}

		}

		return $gf_info;
	}

}