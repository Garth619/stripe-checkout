<?php
/**
 *
 */
?>
<form id="more-stripe-import" action="" method="post">
	<?php wp_nonce_field( 'more_stripe_import', 'gfp_more_stripe_import' ) ?>
	<div class="hr-divider"></div>
	<div class="settings-section importer" data-toggle="importer-settings">
		<h3>
							<span>
													<i class="fa fa-plus-square-o"></i>
													<i class="fa fa-plus-square"></i>
													<i class="fa fa-minus-square"></i>
												</span>
			<?php _e( 'Import Customers from Stripe', 'gravityforms-stripe-more' ) ?></h3>
	</div>
	<div class="importer-settings hidden">
		<div class="import-alert" id="more-stripe-import-body">

			<p><?php _e( 'This operation retrieves your customers from Stripe, and — if the customer has an email address that is not already attached to one of your site users — creates a WordPress user for each customer, saving their currency, cards, and subscriptions for use here on your site.', 'gravityforms-stripe-more' ); ?></p>

			<p><?php _e( 'The WordPress user will be created with the \'Stripe Customer\' role that has no capabilities, and a full report will be emailed to the admin email address for this site, showing each customer and the information that was imported (and if there were any errors).', 'gravityforms-stripe-more' ) ?></p>
<br />
			<?php if ( ! empty( $form_list ) ) { ?>
			<p><?php _e( 'Select the form that holds your subscription notifications:', 'gravityforms-stripe-more' )?>
 <select id="more_stripe_import_form_id" name="more_stripe_import_form_id" style="max-width:25%;">
	<option value=""><?php _e( 'Select form', 'gravityforms-stripe-more' ) ?></option>
	<?php foreach ( $form_list as $form ) { ?>
		<option value="<?php echo $form['id'] ?>"><?php echo $form['title'] ?></option>
	<?php } ?>
</select></p>
				<p><?php _e( 'Select the Stripe rule that holds your user upgrade/downgrade actions:', 'gravityforms-stripe-more' )?>
				 <select id="more_stripe_import_rule_id" name="more_stripe_import_rule_id" style="max-width:25%;">
					<option value=""></option>
				</select></p>
			<?php } ?>
			<p>
				<input type="submit" class="button-primary" id="more_stripe_import_submit" name="more_stripe_import"
					   value="<?php _e( 'Import Stripe Customers', 'gravityforms-stripe-more' ) ?>" class="button"
					   onclick="return confirm('<?php _e( "Just confirming one more time that you\'d like to import your Stripe customers as WordPress users. \'OK\' to import, \'Cancel\' to stop", 'gravityforms-stripe-more' ) ?>')"/>
			</p>

		</div>
		<p id="stripe-wait" style="display: none;"><img
				src="<?php echo GFCommon::get_base_url() ?>/images/spinner.gif"/></p>

		<p id="import-success" style="display:none;"><span class='dashicons dashicons-yes valid_credentials'
														   alt='import success' title='import success'></span></p>

		<p id="import-start-message" style="display:none;">Unable to verify success. Check email for any results.</p>
	</div>
</form>