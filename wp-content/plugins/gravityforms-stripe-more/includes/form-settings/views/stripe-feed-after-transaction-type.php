<?php
	?>
<?php if ( ('subscription' == rgars( $feed, 'meta/type' )) || 'product' == rgars( $feed, 'meta/type' ) ) { ?>
<input type="hidden" name="gfp_stripe_metadata" id="gfp_stripe_metadata" value="" />
<?php } ?>
	<tr valign="top" id="stripe_free_trial_no_credit_card_container" <?php echo 'subscription' !== rgars( $feed, 'meta/type' ) ? "style='display:none;'" : '' ?>>
		<th scope="row">
			<label for="gfp_more_stripe_free_trial_no_credit_card">
				<?php _e( 'No Credit Card Required', 'gravityforms-stripe-more' ); ?>
				<?php gform_tooltip( 'stripe_free_trial_no_credit_card' ) ?>
			</label>
		</th>
		<td>
			<input type="checkbox"
					name="gfp_more_stripe_free_trial_no_credit_card"
					id="gfp_more_stripe_free_trial_no_credit_card"
					value="1"
					<?php checked( '1', rgars( $feed, 'meta/free_trial_no_credit_card' ), true ); ?> />
			<label class="inline" for="gfp_more_stripe_free_trial_no_credit_card"><?php _e( 'Enable', 'gravityforms-stripe-more' ); ?></label>

		</td>
	</tr>