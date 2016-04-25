/**
 *
 */
jQuery( document ).ready( function ( jQuery ) {
	fieldSettings['product'] += ', .stripe_payment_setting';

	jQuery( document ).bind( 'gform_load_field_settings', function ( event, field, form ) {
		var enable_stripe_subscription = jQuery( '#field_enable_stripe_subscription' );
		var create_stripe_plan = jQuery( '#field_create_stripe_plan' );
		var create_stripe_plan_enable_setting = jQuery( '.create_stripe_plan_enable_setting' );
		var dynamic_plan_container = jQuery( '#field_stripe_dynamic_plan_container' );

		enable_stripe_subscription.prop( 'checked', field['stripeSubscription'] == true );
		create_stripe_plan.prop( 'checked', field['dynamicStripePlan'] == true );
		create_stripe_plan_enable_setting.hide();
		dynamic_plan_container.hide();

		if ( false !== enable_stripe_subscription.prop( 'checked' ) ) {
			create_stripe_plan_enable_setting.show();
			if ( false !== create_stripe_plan.prop( 'checked' ) ) {
				dynamic_plan_container.show();
			}
		}

		jQuery( '#stripe_dynamic_plan_interval' ).val( field['dynamicStripePlanInterval'] );
		jQuery( '#stripe_dynamic_plan_interval_count' ).val( field['dynamicStripePlanIntervalCount'] );
		jQuery( '#stripe_dynamic_plan_trial_days' ).val( field['dynamicStripePlanTrialDays'] );
	} );
} );

function ToggleDynamicPlan( stripe_subscription ) {
	if ( false == stripe_subscription ) {
		jQuery( '.create_stripe_plan_enable_setting' ).hide( 'slow' );
		jQuery( '#field_create_stripe_plan' ).prop( 'checked', false );
		jQuery( '#field_stripe_dynamic_plan_container' ).hide( 'slow' );
		jQuery( '.stripe_dynamic_plan' ).val( '' );
	}
	else {
		jQuery( '.create_stripe_plan_enable_setting' ).show( 'slow' );
	}
}

function ToggleDynamicPlanSettings( stripe_dynamic_plan ) {
	if ( false !== stripe_dynamic_plan ) {
		jQuery( '#field_stripe_dynamic_plan_container' ).show( 'slow' );
	}
	else {
		jQuery( '#field_stripe_dynamic_plan_container' ).hide( 'slow' );
		jQuery( '.stripe_dynamic_plan' ).val( '' );
	}
}