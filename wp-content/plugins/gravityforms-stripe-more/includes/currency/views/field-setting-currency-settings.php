<?php
?>
<li class="currency_setting field_setting">
	<label for="field_currency">
		<?php _e( 'Currency', 'gravityforms-stripe-more' ); ?>
		<?php gform_tooltip( 'form_field_currency' ) ?>
	</label>
	<?php GFP_More_Stripe_Currency::dropdown_currencies( array( 'selected' => $currency, 'id' => 'field_currency', 'name' => 'field_currency' ) ); ?>
</li>

<li class="currency_field_type_setting field_setting">
	<label for="currency_field_type">
		<?php _e( 'Field Type', 'gravityforms-stripe-more' ); ?>
		<?php gform_tooltip( 'form_field_type' ) ?>
	</label> <select id="currency_field_type"
					 onchange="jQuery('#field_settings').slideUp(function(){StartChangeInputType( jQuery('#currency_field_type').val() );});">
		<option value="select"><?php _e( 'Drop Down', 'gravityforms-stripe-more' ); ?></option>
		<option value="radio"><?php _e( 'Radio Buttons', 'gravityforms-stripe-more' ); ?></option>
	</select>
</li>

<li class="currency_checkbox_setting field_setting">
	<label for="field_currency">
		<?php _e( 'Currency', 'gravityforms-stripe-more' ); ?>
		<?php gform_tooltip( 'form_field_currency_selection' ) ?>
	</label>

	<input type="radio" id="gfield_currency_all" name="gfield_currency" value="all" onclick="ToggleCurrency();"/> <label
		for="gfield_currency_all" class="inline">
		<?php _e( 'All Currencies', 'gravityforms-stripe-more' ); ?>
	</label> &nbsp;&nbsp; <input type="radio" id="gfield_currency_select" name="gfield_currency" value="select"
								 onclick="ToggleCurrency();"/> <label for="form_button_image" class="inline">
		<?php _e( 'Select Currencies', 'gravityforms-stripe-more' ); ?>
	</label>

	<div id="gfield_settings_currency_container">
		<table cellpadding="0" cellspacing="5">
			<?php
			$currencies = GFP_More_Stripe_Currency::get_currencies();
			$count = 0;
			$currency_rows = '';

			echo $this->currency_rows( $currencies, $count, $currency_rows );
			?>
		</table>
	</div>
</li>


<li class="currency_initial_item_setting field_setting">
	<input type="checkbox" id="gfield_currency_initial_item_enabled"
		   onclick="ToggleCurrencyInitialItem(); SetCurrencyInitialItem();"/> <label
		for="gfield_currency_initial_item_enabled" class="inline">
		<?php _e( 'Display placeholder', 'gravityforms-stripe-more' ); ?>
		<?php gform_tooltip( 'form_field_currency_initial_item' ) ?>
	</label>
</li>
<li id="gfield_currency_initial_item_container">
	<label for="field_currency_initial_item">
		<?php _e( 'Placeholder Label', 'gravityforms-stripe-more' ); ?>
	</label> <input type="text" id="field_currency_initial_item" onchange="SetCurrencyInitialItem();"
					class="fieldwidth-3" size="35"/>
</li>