<?php
/**
 *
 */
?>
<tr valign="top" id="stripe_field_container_subscription_end_after"
	<?php echo rgars( $feed, 'meta/type' ) != 'subscription' ? "style='display:none;'" : '' ?>>
	<th scope="row">
		<label for="gfp_more_stripe_subscription_end_after">
			<?php _e( 'End Subscription (Split Payments)', 'gravityforms-stripe-more' ); ?>
			<?php gform_tooltip( 'stripe_subscription_end_after' ) ?>
		</label>
	</th>
	<td>
		<select id="gfp_more_stripe_subscription_end_after_field" name="gfp_more_stripe_subscription_end_after_field">
			<?php echo $subscription_end_options; ?>
		</select>
	</td>
</tr>