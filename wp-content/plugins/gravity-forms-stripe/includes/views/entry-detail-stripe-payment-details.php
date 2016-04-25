<?php
/**
 *
 */
?>
<div id="submitdiv" class="stuffbox">
	<h3>
		<span class="hndle"><?php _e( 'Stripe', 'gravity-forms-stripe' ); ?></span>
	</h3>

	<div class="inside">
		<div id="submitcomment" class="submitbox">
			<div id="minor-publishing" style="padding:10px;">
				<br/>
				<?php
				if ( ! empty( $entry['payment_status'] ) && 1 == $entry['transaction_type'] ) {
					_e( 'Status', 'gravity-forms-stripe' ); ?>: <span
						id="gfp_stripe_payment_status"><?php echo apply_filters( 'gfp_stripe_entry_detail_payment_status', $entry['payment_status'], $form, $entry ) ?></span>
					<br/><br/>
					<?php
					_e( 'Transaction ID', 'gravity-forms-stripe' ); ?>: <?php echo apply_filters( 'gfp_stripe_entry_detail_transaction_id', $entry['transaction_id'], $form, $entry ) ?>
					<br/><br/>
					<?php

					if ( ! rgblank( $entry['payment_amount'] ) ) {
						_e( 'Amount', 'gravity-forms-stripe' ); ?>: <?php echo GFCommon::to_money( $entry['payment_amount'], $entry['currency'] ) ?>
						<br/><br/>
					<?php
					}
				}
				do_action( 'gfp_stripe_payment_details', $form, $entry );

				?>
			</div>
		</div>
	</div>
</div>