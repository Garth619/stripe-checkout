<?php
/**
 *
 */
?>
<tr valign="top"
	id="stripe_field_container_coupon" <?php echo ( 'product' == rgars( $feed, 'meta/type' ) || 'subscription' == rgars( $feed, 'meta/type' ) ) ? '' : "style='display:none;'" ?>>
	<th scope="row">
		<label for="gfp_more_stripe_enable_coupons">
			<?php _e( 'Coupons', 'gravityforms-stripe-more' ); ?>
			<?php gform_tooltip( 'stripe_enable_coupons' ) ?>
		</label>
	</th>
	<td>
		<input type="checkbox"
			   name="gfp_more_stripe_enable_coupons"
			   id="gfp_more_stripe_enable_coupons"
			   value="1"
			   <?php echo rgars( $feed, "meta/coupons_enabled" ) ? "checked='checked'" : "" ?> />
		<label class="inline" for="gfp_more_stripe_enable_coupons"><?php _e( 'Enable', 'gravityforms-stripe-more' ); ?></label> &nbsp;&nbsp;&nbsp;
		<span id="gfp_more_stripe_coupons_container" <?php echo rgars( $feed, 'meta/coupons_enabled' ) ? "" : "style='display:none;'" ?>>
			<select id="gfp_more_stripe_coupons_field" name="gfp_more_stripe_coupons_field"><?php echo $coupon_options; ?></select>
		</span>
		<br /><br />
		<span id="gfp_more_stripe_coupons_apply_container" <?php echo ( 'subscription' == rgars( $feed, 'meta/type' ) && rgars( $feed, 'meta/coupons_enabled' ) ) ? "" : "style='display:none;'" ?>>
			<label class="inline"><?php _e( 'Apply', 'gravityforms-stripe-more' ); ?></label>
			<?php gform_tooltip( 'stripe_coupons_apply' ) ?>
			<input type="radio" id="gfp_more_stripe_coupons_apply_now" name="gfp_more_stripe_coupons_apply" value="now"
						    <?php checked( rgars( $feed, 'meta/coupons_apply' ), 'now', true ) ?>>
					<label for="gfp_more_stripe_coupons_apply_now"
						   class="inline"><?php _e( 'Immediately', 'gravityforms-stripe-more' ) ?></label>

					&nbsp;&nbsp;

					<input type="radio" id="gfp_more_stripe_coupons_apply_after" name="gfp_more_stripe_coupons_apply" value="after"
						    <?php checked( rgars( $feed, 'meta/coupons_apply' ), 'after', true ) ?>>
					<label for="gfp_more_stripe_coupons_apply_after" class="inline"><?php _e( 'After 1st period', 'gravityforms-stripe-more' ) ?></label>
			</span>
	</td>
</tr>