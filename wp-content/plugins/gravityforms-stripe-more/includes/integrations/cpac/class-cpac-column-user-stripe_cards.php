<?php

/**
 * Stripe Cards column
 */
class CPAC_Column_User_Stripe_Cards extends CPAC_Column {

	public function init() {

		parent::init();

		// Identifier, pick a unique name.
		$this->properties['type'] = 'column-userstripecards';

		// Default column label.
		$this->properties['label'] = __( 'Stripe Cards' );

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

		$cards = $this->get_raw_value( $id );

		// optionally you can change the display of the value.
		return $cards;
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

		$saved_cards = GFP_More_Stripe_Customer_API::get_stripe_customer_cards( $id );
		if ( ! empty( $saved_cards ) ) {
			foreach ( $saved_cards as $card ) {
				$card_brand = empty( $card['type'] ) ? $card['brand'] : $card['type'];
				$cards[] = $card_brand . ' (' . $card['last4'] . ')';
			}
			$value = implode( '<br />', $cards );
		}
		else {
			$value = '';
		}

		return $value;
	}
} 