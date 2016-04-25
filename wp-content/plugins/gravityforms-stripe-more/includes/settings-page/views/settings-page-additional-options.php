<?php
/**
 *
 */
?>
<div class="settings-section additional-options setup" data-toggle="additional-options-settings">
	<h3>
		<span>
			<i class="fa fa-plus-square-o"></i>
			<i class="fa fa-plus-square"></i>
			<i class="fa fa-minus-square"></i>
		</span>
		<?php _e( 'Additional Options', 'gravityforms-stripe-more' ) ?></h3>
</div>
<div class="additional-options-settings hidden">
	<table class="form-table">
		<tr>
			<td colspan="2">
				<input type="checkbox" name="gfp_stripe_disable_save_customers_as_users"
					   id="gfp_stripe_disable_save_customers_as_users" <?php echo rgar( $settings, 'disable_save_customers_as_users' ) ? "checked='checked'" : "" ?>/>
				<label for="gfp_stripe_disable_save_customers_as_users"
					   class="inline"><?php _e( "Disable saving Stripe customers as WP users.", 'gravityforms-stripe-more' ) ?></label>
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<input type="checkbox" name="gfp_stripe_enable_early_access"
					   id="gfp_stripe_enable_early_access" <?php echo rgar( $settings, 'enable_early_access' ) ? "checked='checked'" : "" ?>/>
				<label for="gfp_stripe_enable_early_access"
					   class="inline"><?php _e( "Enable <strong>Early Access</strong> to new features", 'gravityforms-stripe-more' ) ?></label>
			</td>
		</tr>
	</table>
</div>