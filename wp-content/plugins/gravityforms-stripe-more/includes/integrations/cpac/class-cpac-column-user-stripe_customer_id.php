<?php

/**
 * Stripe Customer ID column
 */
class CPAC_Column_User_Stripe_Customer_ID extends CPAC_Column {

	public function init () {

		parent::init();

		// Identifier, pick an unique name.
		$this->properties[ 'type' ] = 'column-userstripecustomerid';

		// Default column label.
		$this->properties[ 'label' ] = __( 'Stripe Customer ID' );

		// (optional) You can make it support sorting with the pro add-on enabled. Sorting will done by it's raw value.
		$this->properties[ 'is_sortable' ] = true;

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

		$customer_id = $this->get_raw_value( $id );

		// optionally you can change the display of the value.
		return $customer_id;
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

		$customer_id = GFP_More_Stripe_Customer_API::get_stripe_customer_id( $id, true );

		if ( is_array( $customer_id ) ) {
			foreach ( $customer_id as $date_created => $id ) {
				$date_created = ( 0 == $date_created ) ? 'current' : date_i18n( 'm/d/Y', $date_created, true );
				$ids[ ]       = "$id ({$date_created})";
			}
			$value = implode( '<br />', $ids );
		}
		else if ( ! empty( $customer_id ) && is_string( $customer_id ) ) {
			$value = $customer_id;
		}
		else {
			$value = '';
		}

		return $value;
	}
} 