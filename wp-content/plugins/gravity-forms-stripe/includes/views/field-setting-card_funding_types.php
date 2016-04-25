<?php
/************************************************************************************************
	 * This feature was generously sponsored by: Two Paper Dolls http://twopaperdolls.com/
 ***********************************************************************************************/
?>
<li class="credit_card_funding_type_setting field_setting">
	<label>
		<?php _e( 'Supported Funding Types', 'gravity-forms-stripe' ); ?>
		<?php gform_tooltip( 'form_field_credit_card_funding_type' ) ?>
	</label>
	<ul>
		<li>
			<input type="checkbox" id="field_credit_card_funding_credit" value="credit" class="field_credit_card_funding_type"/>
			<label for="field_credit_card_funding_credit" class="inline"><?php _e( 'Credit', 'gravity-forms-stripe' ) ?></label>
		</li>
		<li>
			<input type="checkbox" id="field_credit_card_funding_debit" value="debit" class="field_credit_card_funding_type"/>
			<label for="field_credit_card_funding_debit" class="inline"><?php _e( 'Debit', 'gravity-forms-stripe' ) ?></label>
		</li>
		<li>
			<input type="checkbox" id="field_credit_card_funding_prepaid" value="prepaid" class="field_credit_card_funding_type"/>
			<label for="field_credit_card_funding_prepaid" class="inline"><?php _e( 'Prepaid', 'gravity-forms-stripe' ) ?></label>
		</li>
		<li>
			<input type="checkbox" id="field_credit_card_funding_unknown" value="unknown" class="field_credit_card_funding_type"/>
			<label for="field_credit_card_funding_unknown" class="inline"><?php _e( 'Unknown', 'gravity-forms-stripe' ) ?></label>
		</li>
	</ul>
</li>