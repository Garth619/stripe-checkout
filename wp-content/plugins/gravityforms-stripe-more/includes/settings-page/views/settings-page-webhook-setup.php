<?php
/**
 *
 */
?>
<div class="settings-section webhook-setup setup" data-toggle="webhook-settings">
	<h3>
			<span>
									<i class="fa fa-plus-square-o"></i>
									<i class="fa fa-plus-square"></i>
									<i class="fa fa-minus-square"></i>
								</span>
		<?php _e( 'Stripe Webhook Setup', 'gravityforms-stripe-more' ) ?></h3>
</div>
<div class="webhook-settings hidden">
	<p style="text-align: left;">
		<?php _e( 'To receive information and send notifications about a customer subscription and other events from Stripe, you must create a webhook for this site in your Stripe dashboard \'Account Settings\'. Follow the steps below to confirm.', 'gravityforms-stripe-more' ) ?>
	</p>
	<blockquote>
		<ol>
			<li><?php echo sprintf( __( 'Navigate to your Stripe dashboard\'s %sAccount Settings->Webhooks page%s.', 'gravityforms-stripe-more' ), "<a href='https://dashboard.stripe.com/#account/webhooks' target='_blank'>", "</a>" ) ?></li>
			<li><?php echo sprintf( __( 'Click the \'Add URL\' button and enter the following URL, with the mode underneath the URL box set as \'Test\': %s', 'gravityforms-stripe-more' ), "<blockquote><strong>$webhook_url</strong></blockquote>" ) ?></li>
			<li><?php _e( 'Click the \'Add URL\' button and enter the same URL, with the mode underneath the URL box set as \'Live\'', 'gravityforms-stripe-more' ) ?></li>
			<li><?php _e( 'Once your webhooks are setup and see your site\'s webhook URL listed twice — once for Test mode and once for Live mode — check the confirmation box below and you are ready to go!', 'gravityforms-stripe-more' ) ?></li>
		</ol>
	</blockquote>
	<table class="form-table">
		<tr>
			<td colspan="2">
				<input type="checkbox" name="gfp_stripe_webhook_configured"
					   id="gfp_stripe_webhook_configured" <?php echo rgar( $settings, 'stripe_webhook_configured' ) ? "checked='checked'" : "" ?>/>
				<label for="gfp_stripe_webhook_configured"
					   class="inline"><?php _e( 'Both Stripe webhooks are setup in my Stripe dashboard.', 'gravityforms-stripe-more' ) ?></label>
			</td>
		</tr>
	</table>
</div>