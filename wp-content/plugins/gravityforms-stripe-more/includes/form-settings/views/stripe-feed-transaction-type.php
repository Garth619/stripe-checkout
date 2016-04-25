<?php
if ( 'on' == $settings['stripe_webhook_configured'] ) {
	?>
	<tr valign="top">
		<th scope="row">
			<label for="gfp_stripe_type">
				<?php _e( 'Transaction Type', 'gravityforms-stripe-more' ); ?>
				<span class="gfield_required">*</span>
				<?php gform_tooltip( 'stripe_transaction_type' ) ?>
			</label>
		</th>
		<td>
			<select id="gfp_stripe_type" name="gfp_stripe_type" onchange="SelectType(jQuery(this).val());">
				<option value=""><?php _e( 'Select a transaction type', 'gravityforms-stripe-more' ) ?></option>
				<option
					value="product" <?php echo 'product' == rgar( $feed['meta'], 'type' ) ? "selected='selected'" : "" ?>><?php _e( 'One-Time Payment', 'gravityforms-stripe-more' ) ?></option>
				<option
					value="subscription" <?php echo 'subscription' == rgar( $feed['meta'], 'type' ) ? "selected='selected'" : "" ?>><?php _e( 'Subscription', 'gravityforms-stripe-more' ) ?></option>
				<option
					value="update-billing" <?php echo 'update-billing' == rgar( $feed['meta'], 'type' ) ? "selected='selected'" : "" ?>><?php _e( 'Billing Info Update', 'gravityforms-stripe-more' ) ?></option>
				<option
					value="update-subscription" <?php echo 'update-subscription' == rgar( $feed['meta'], 'type' ) ? "selected='selected'" : "" ?>><?php _e( 'Subscription Update', 'gravityforms-stripe-more' ) ?></option>
			</select>
		</td>
	</tr>

<?php
}
else {
	$feed['meta']['type'] = 'product' ?>


	<input id="gfp_stripe_type" type="hidden" name="gfp_stripe_type" value="product">


<?php
}