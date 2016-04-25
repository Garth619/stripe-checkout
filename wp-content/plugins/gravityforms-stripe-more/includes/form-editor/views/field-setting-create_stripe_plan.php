<?php
?>
<li class="create_stripe_plan_enable_setting stripe_payment_setting field_setting">
	<input type="checkbox" name="field_create_stripe_plan" id="field_create_stripe_plan"
		   onclick="SetFieldProperty('dynamicStripePlan', this.checked); ToggleDynamicPlanSettings( this.checked ); "/>
	<label for="field_create_stripe_plan" class="inline">
		<?php _e( 'Create plan for this subscription product', 'gravityforms-stripe-more' ); ?>
		<?php gform_tooltip( 'form_field_create_stripe_plan' ) ?>
	</label> <br/>

	<div id="field_stripe_dynamic_plan_container" style="display:none; padding-top:10px;">
		<table>
			<tbody>
			<!--<tr>
				<td><label for="stripe_dynamic_plan_id" class="inline">Plan ID</label></td>
				<td><input type="text" value="undefined" id="stripe_dynamic_plan_id" class="stripe_dynamic_plan"
									 onkeyup="SetFieldProperty( 'dynamicStripePlanID', this.value );"></td>
			</tr>-->
			<tr></tr>
			<tr>
				<td><label for="stripe_dynamic_plan_interval" class="inline">Interval</label></td>
				<td>
					<select id="stripe_dynamic_plan_interval" class="stripe_dynamic_plan"
							onchange="SetFieldProperty( 'dynamicStripePlanInterval', this.value );">
						<option value=""><?php _e( 'Select an interval', 'gravityforms-stripe-more' ) ?></option>
						<option value="day"><?php _e( 'Daily', 'gravityforms-stripe-more' ) ?></option>
						<option value="week"><?php _e( 'Weekly', 'gravityforms-stripe-more' ) ?></option>
						<option value="month"><?php _e( 'Monthly', 'gravityforms-stripe-more' ) ?></option>
						<option value="year"><?php _e( 'Yearly', 'gravityforms-stripe-more' ) ?></option>

					</select></td>
			</tr>
			<tr></tr>
			<tr>
				<td><label for="stripe_dynamic_plan_interval_count" class="inline">Interval Count</label></td>
				<td><input type="text" value="" id="stripe_dynamic_plan_interval_count" class="stripe_dynamic_plan"
						   onkeyup="SetFieldProperty( 'dynamicStripePlanIntervalCount', this.value );"></td>
			</tr>
			<tr>
				<td><label for="stripe_dynamic_plan_trial_days" class="inline">Trial Period (# of Days)</label></td>
				<td><input type="text" value="" id="stripe_dynamic_plan_trial_days" class="stripe_dynamic_plan"
						   onkeyup="SetFieldProperty( 'dynamicStripePlanTrialDays', this.value );"></td>
			</tr>
			<tr></tr>
			</tbody>
		</table>
	</div>

</li>