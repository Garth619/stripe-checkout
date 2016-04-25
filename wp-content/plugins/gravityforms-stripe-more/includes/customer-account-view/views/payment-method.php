<?php
/**
 * Payment Method View
 */
?>
<div id="stripe-customer-account-view-payment-method-section">
	<p class="label stripe-customer-account-view-section-label"><?php echo $section_label ?>
		<?php if ( ! empty( $update_payment_method_page ) ) { ?>
			<a href="<?php echo $update_payment_method_page ?>"><?php echo $update_payment_method_link_text ?></a>
		<?php } ?>
	</p>
	<p>
	<?php if ( empty( $payment_method_list ) ) { ?>
				<em><?php echo $this->options['empty_section_text'] ?></em>
			<?php } else { ?>
	<?php foreach ( $payment_method_list as $payment_method ) { ?>
		<div class="stripe-customer-account-view-payment-method">
		<?php echo GFP_Stripe_Helper::get_card_brand_icon( ( empty( $payment_method[ 'type' ] ) ? $payment_method[ 'brand' ] : $payment_method[ 'type' ] ) ); ?>
		<?php echo $payment_method['label'] ?>
		<?php if ( true === $payment_method['default'] ) { ?>
			<span class="label">Default</span>
		<?php } ?>
			</div>
	  <?php } ?>
	<?php } ?>
	</p>
</div>