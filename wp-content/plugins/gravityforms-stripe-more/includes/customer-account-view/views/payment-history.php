<?php
/**
 * Payment History View
 */
?>
<div id="stripe-customer-account-view-payment-history-section">
	<p class="label stripe-customer-account-view-section-label"><?php echo $section_label ?></p>
	<p>
		<?php if (empty( $transactions )) { ?>
			<em><?php echo $this->options[ 'empty_section_text' ] ?></em>
		<?php } else { ?>
	<table>
		<?php foreach ( $transactions as $transaction ) { ?>
			<?php if ( in_array( $transaction[ 'transaction_type' ], $transaction_types_to_show ) ) { ?>
				<tr>
					<td><?php echo date_i18n( 'm/d/y g:i A', strtotime( $transaction[ 'date_created' ] ) ) ?></td>
					<td><?php echo $this->get_transaction_description( $transaction ) ?></td>
					<td><?php echo $transaction[ 'currency' ] ?> <?php echo $transaction[ 'amount' ] ?></td>
				</tr>
			<?php } ?>
		<?php } ?>
	</table>
	<?php } ?>
	</p>
</div>