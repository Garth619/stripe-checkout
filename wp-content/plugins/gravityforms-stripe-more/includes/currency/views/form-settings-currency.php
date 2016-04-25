<?php
?>
<table class="gforms_form_settings" cellspacing="0" cellpadding="0">
		<tr>
			<th><?php _e( 'Form Currency', 'gravityforms-stripe-more' ) ?></th>
			<td><?php GFP_More_Stripe_Currency::dropdown_currencies( array( 'selected' => $selected, 'id' => 'form_currency', 'name' => 'form_currency' ) ); ?></td>
		</tr>
	</table>