<?php
/**
 *
 */
?>
<li id="stripe_disable_prorate" <?php echo rgars( $feed, 'meta/type' ) != 'update-subscription' ? "style='display:none;'" : '' ?>>
	<input type="checkbox" name="gfp_more_stripe_disable_prorate"
		   id="gfp_more_stripe_disable_prorate" <?php echo rgar( $feed['meta'], 'disable_prorate' ) ? "checked='checked'" : "value='1'" ?> />
	<label class="inline"
		   for="gfp_more_stripe_disable_prorate"><?php _e( "Do not prorate.", "gfp-more-stripe" ); ?> <?php gform_tooltip( "stripe_disable_prorate" ) ?></label>
</li>
<li id="stripe_charge_upgrade_immediately" <?php echo rgars( $feed, 'meta/type' ) != 'update-subscription' ? "style='display:none;'" : '' ?>>
	<input type="checkbox" name="gfp_more_stripe_charge_upgrade_immediately"
		   id="gfp_more_stripe_charge_upgrade_immediately" <?php echo rgar( $feed['meta'], 'charge_upgrade_immediately' ) ? "checked='checked'" : "value='1'" ?> />
	<label class="inline"
		   for="gfp_more_stripe_charge_upgrade_immediately"><?php _e( "Charge upgrade immediately.", "gfp-more-stripe" ); ?> <?php gform_tooltip( "stripe_charge_upgrade_immediately" ) ?></label>
</li>
<li id="stripe_cancel_at_period_end" <?php echo rgars( $feed, 'meta/type' ) != 'update-subscription' ? "style='display:none;'" : '' ?>>
	<input type="checkbox" name="gfp_more_stripe_cancel_at_period_end"
		   id="gfp_more_stripe_cancel_at_period_end" <?php echo rgar( $feed['meta'], 'cancel_at_period_end' ) ? "checked='checked'" : "value='1'" ?> />
	<label class="inline"
		   for="gfp_more_stripe_cancel_at_period_end"><?php _e( "Delay subscription cancelation.", "gfp-more-stripe" ); ?> <?php gform_tooltip( "stripe_cancel_at_period_end" ) ?></label>
</li>