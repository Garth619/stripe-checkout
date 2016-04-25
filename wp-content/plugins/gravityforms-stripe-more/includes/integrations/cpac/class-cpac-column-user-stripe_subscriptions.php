<?php

/**
 * Stripe Subscriptions column
 */
class CPAC_Column_User_Stripe_Subscriptions extends CPAC_Column {

	public function init() {

		parent::init();

		// Identifier, pick an unique name.
		$this->properties['type'] = 'column-userstripesubscriptions';

		// Default column label.
		$this->properties['label'] = __( 'Stripe Subscriptions' );

		// (optional) You can make it support sorting with the pro add-on enabled. Sorting will done by it's raw value.
		$this->properties['is_sortable'] = true;

	}

	/**
	 * Get value
	 *
	 * Returns the value for the column.
	 *
	 * @param int $id ID
	 *
	 * @return string Value
	 */
	public function get_value ( $id ) {

		$subscriptions = $this->get_raw_value( $id );

		// optionally you can change the display of the value.
		return $subscriptions;
	}

	/**
	 * Get the raw, underlying value for the column
	 * Not suitable for direct display, use get_value() for that
	 *
	 * @param int $id ID
	 *
	 * @return mixed Value
	 */
	public function get_raw_value ( $id ) {

		$active_subscriptions = GFP_More_Stripe_Customer_API::get_active_subscriptions( $id );
		if ( ! empty( $active_subscriptions ) ) {
			foreach ( $active_subscriptions as $active_subscription_id ) {
				$subscription = GFP_More_Stripe_Customer_API::get_subscription_info( $id, $active_subscription_id, true );
				$plans[]      = $subscription['plan']['name'];
			}

			$value = implode( '<br />', $plans );
		}
		else {
			$value = '';
		}

		return $value;
	}
} 