<?php
/**
 *
 */
?>
<tr valign="top"
	id="stripe_field_container_currency" <?php echo ( rgars( $feed, 'meta/type' ) == 'update-subscription' || rgars( $feed, 'meta/type' ) == 'update-billing' ) ? "style='display:none;'" : '' ?>>
	<th scope="row">
		<label for="gfp_more_stripe_override_default_currency">
			<?php _e( 'Currency', 'gravityforms-stripe-more' ); ?>
			<?php gform_tooltip( 'stripe_override_default_currency' ) ?>
		</label>
	</th>
	<td>
		<input type="checkbox"
			   onchange="if(this.checked) {jQuery('#gfp_more_stripe_currency_field').val('Select a field');}"
			   name="gfp_more_stripe_override_default_currency" id="gfp_more_stripe_override_default_currency" value="1"
			   onclick="if(jQuery(this).is(':checked')) jQuery('#gfp_more_stripe_currency_container').show('slow'); else jQuery('#gfp_more_stripe_currency_container').hide('slow');" <?php echo rgars( $feed, 'meta/currency_override' ) ? "checked='checked'" : "" ?> />
		<label class="inline"
			   for="gfp_more_stripe_override_default_currency"><?php _e( 'Override Default', 'gravityforms-stripe-more' ); ?></label>
		&nbsp;&nbsp;&nbsp;
			                            <span
											id="gfp_more_stripe_currency_container" <?php echo rgars( $feed, 'meta/currency_override' ) ? "" : "style='display:none;'" ?>>
			                                <select id="gfp_more_stripe_currency_field"
													name="gfp_more_stripe_currency_field">

												<?php echo $currency_override_options; ?>
											</select>
			                            </span>
	</td>
</tr>