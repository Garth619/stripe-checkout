<?php
/**
 *
 */
?>
<tr valign="top" id="stripe_field_container_alternate_charge_option"
	<?php echo rgars( $feed, 'meta/type' ) != 'product' ? "style='display:none;'" : '' ?>>
	<th scope="row">
		<label for="gfp_more_stripe_alternate_charge_option">
			<?php _e( 'Alternate Charge Option', 'gravityforms-stripe-more' ); ?>
			<?php gform_tooltip( 'stripe_alternate_charge_option' ) ?>
		</label>
	</th>
	<td>
		<select id="gfp_more_stripe_alternate_charge_option" name="gfp_more_stripe_alternate_charge_option">
			<option value=""></option>
			<?php // The following option was generously sponsored by: Gerard Ramos of Revelry Labs LLC http://revelry.co/ ?>
			<option
				value="save_cards_only" <?php selected( 'save_cards_only', rgar( $feed['meta'], 'alternate_charge_option' ), true ) ?>><?php _e( 'Save Cards Only', 'gravityforms-stripe-more' ) ?></option>
			<option value="authorize_only" <?php selected( 'authorize_only', rgar( $feed['meta'], 'alternate_charge_option' ), true ) ?>><?php _e( 'Authorize Only', 'gravityforms-stripe-more' ) ?></option>
		</select>
	</td>
</tr>