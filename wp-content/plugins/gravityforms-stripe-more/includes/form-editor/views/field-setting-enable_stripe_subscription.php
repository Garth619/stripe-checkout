<?php
?>
<li class="stripe_subscription_enable_setting stripe_payment_setting field_setting">
	<input type="checkbox" name="field_enable_stripe_subscription" id="field_enable_stripe_subscription"
		   onclick="SetFieldProperty( 'stripeSubscription', this.checked ); ToggleDynamicPlan( this.checked ); "/>
	<label for="field_enable_stripe_subscription" class="inline">
		<?php _e( 'Stripe subscription product', 'gravityforms-stripe-more' ); ?>
		<?php gform_tooltip( 'form_field_enable_stripe_subscription' ) ?>
	</label>

</li>