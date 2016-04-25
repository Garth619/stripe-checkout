<?php
/**
 *
 */
?>
<tr valign="top"
	id="stripe_field_container_setup_fee" <?php echo rgars( $feed, 'meta/type' ) != 'subscription' ? "style='display:none;'" : '' ?>>
	<th scope="row">
		<label for="gfp_more_stripe_setup_fee_enable">
			<?php _e( 'Setup Fee', 'gravityforms-stripe-more' ); ?>
			<?php gform_tooltip( 'stripe_setup_fee_enable' ) ?>
		</label>
	</th>
	<td>
		<input type="checkbox"
			   onchange="if(this.checked) {jQuery('#gfp_more_stripe_setup_fee_amount_field').val('Select a field');}"
			   name="gfp_more_stripe_setup_fee_enable" id="gfp_more_stripe_setup_fee_enable" value="1"
			   onclick="if(jQuery(this).is(':checked')) jQuery('#gfp_more_stripe_setup_fee_container').show('slow'); else jQuery('#gfp_more_stripe_setup_fee_container').hide('slow');" <?php echo rgars( $feed, "meta/setup_fee_enabled" ) ? "checked='checked'" : "" ?> />
		<label class="inline" for="gfp_more_stripe_setup_fee_enable"><?php _e( 'Enable','gravityforms-stripe-more' ); ?></label>
		&nbsp;&nbsp;&nbsp;
			                            <span
											id="gfp_more_stripe_setup_fee_container" <?php echo rgars( $feed, 'meta/setup_fee_enabled' ) ? "" : "style='display:none;'" ?>>
			                                <select id="gfp_more_stripe_setup_fee_amount_field"
													name="gfp_more_stripe_setup_fee_amount_field">
												<?php echo $setup_fee_options; ?>
											</select>
			                            </span>
	</td>
</tr>