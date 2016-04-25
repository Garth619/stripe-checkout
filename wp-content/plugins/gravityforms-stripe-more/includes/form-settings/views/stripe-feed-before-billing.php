<?php
/**
 *
 */
?>
<tr valign="top" id="stripe_field_container_subscription" class="stripe_field_container"
	<?php echo ( ( 'subscription' != rgars( $feed, 'meta/type' ) ) && ( 'update-subscription' != rgars( $feed, 'meta/type' ) ) ) ? "style='display:none;'" : '' ?>>
	<th scope="row">
		<label for="gfp_stripe_subscription_plan">
			<?php _e( 'Subscription Plan', 'gravityforms-stripe-more' ); ?>
			<?php gform_tooltip( 'stripe_subscription_plan' ) ?>
		</label>
	</th>
	<td>
		<select id="gfp_stripe_subscription_plan" name="gfp_stripe_subscription_plan">
			<?php
			if ( ( rgars( $feed, 'meta/subscription_plan_field' ) ) && ( 'update-subscription' != rgars( $feed, 'meta/type' ) ) ) {
				echo GFP_Stripe::get_product_options( $form, $feed['meta']['subscription_plan_field'], true );
			}
			else if ( ( rgars( $feed, 'meta/subscription_plan_field' ) ) && ( 'update-subscription' == rgars( $feed, 'meta/type' ) ) ) {
				echo GFP_Stripe::get_product_options( $form, $feed['meta']['subscription_plan_field'], false );
			}
			else {
				echo '';
			}
			?>
		</select>
	</td>
</tr>