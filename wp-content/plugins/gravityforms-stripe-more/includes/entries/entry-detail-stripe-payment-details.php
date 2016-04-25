<?php
/**
 *
 */
?>
<?php
if ( ! empty( $entry[ 'payment_status' ] ) && 6 == $entry[ 'transaction_type' ] ) {
	_e( 'Status', 'gravityforms-stripe-more' ); ?>: <span
		id="gfp_stripe_payment_status"><?php echo apply_filters( 'gfp_stripe_entry_detail_payment_status', $entry[ 'payment_status' ], $form, $entry ) ?></span>
	<br/><br/>
	<?php
	_e( 'Transaction ID', 'gravityforms-stripe-more' ); ?>: <?php echo apply_filters( 'gfp_stripe_entry_detail_transaction_id', $entry[ 'transaction_id' ], $form, $entry ) ?>
	<br/><br/>
	<?php

	if ( ! rgblank( $entry[ 'payment_amount' ] ) ) {
		_e( 'Amount', 'gravityforms-stripe-more' ); ?>: <?php echo GFCommon::to_money( $entry[ 'payment_amount' ], $entry[ 'currency' ] ) ?>
		<br/><br/>
	<?php
	}
}

if ( ! empty( $customer_id ) ) {
	_e( 'Customer ID', 'gravityforms-stripe-more' ); ?>: <?php echo $customer_id ?>
	<br/><br/>
<?php
}
?>
<?php if ( ! empty( $stripe_subscription ) ) { ?>

	<?php _e( 'Subscriptions', 'gravityforms-stripe-more' ); ?>
	<br/><br/>
	<span id="stripe_subscription_status">
			<?php _e( 'Status', 'gravityforms-stripe-more' ) ?>: <?php echo $stripe_subscription[ 'status' ]; ?>
		</span>
	<br/><br/>
	<span id="stripe_subscription_id">
		<?php _e( 'ID', 'gravityforms-stripe-more' ) ?>: <?php echo $stripe_subscription[ 'id' ]; ?>
	</span>
	<br/><br/>

	<span id="stripe_subscription_plan_amount">
			<?php _e( 'Plan', 'gravityforms-stripe-more' ) ?>
		: <?php echo GFCommon::to_money( $stripe_subscription[ 'plan' ][ 'amount' ], $entry[ 'currency' ] ); ?>
		every <?php echo "{$stripe_subscription[ 'plan' ]['interval_count']} {$stripe_subscription[ 'plan' ]['interval']}"; ?>
		<?php echo ( 1 < $stripe_subscription[ 'plan' ][ 'interval_count' ] ) ? 's' : ''; ?>
		</span>
	<br/><br/>

	<?php if ( ! empty( $stripe_subscription[ 'setup_fee' ] ) ) { ?>
		<span id="stripe_subscription_setup_fee">
			<?php _e( 'Setup Fee', 'gravityforms-stripe-more' ) ?>
			: <?php echo GFCommon::to_money( $stripe_subscription[ 'setup_fee' ][ 'amount' ], $entry[ 'currency' ] ); ?>
			(<?php echo $stripe_subscription[ 'setup_fee' ][ 'id' ]; ?>)
		</span>
	<?php } ?>
	<br/><br/>

	<span id="stripe_subscription_end_after">
	<?php _e( 'Scheduled Payments', 'gravityforms-stripe-more' ) ?>: <?php echo $scheduled_payments; ?>
</span>
	<br/><br/>
	<?php echo $cancelsub_button; ?>
<?php } ?>