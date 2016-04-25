<?php
/**
 * Current Subscription View
 */
?>
<div id="stripe-customer-account-view-current-subscription-section">
	<p class="label stripe-customer-account-view-section-label"><?php echo $section_label ?></p>
	<p>
		<?php if ( empty( $current_subscription ) ) { ?>
			<em><?php echo $this->options['empty_section_text'] ?></em>
		<?php } else { ?>
	<?php if ( ! empty( $update_subscription_page ) ) { ?>
	(<?php echo $current_subscription['quantity'] ?>) x <?php echo $plan_name ?> <a href="<?php echo $update_subscription_link ?>"><?php echo $update_subscription_link_text ?></a>
	<?php } ?>
	<?php if ( ! empty( $cancel_subscription_page ) ) { ?>
		| <a href="<?php echo $cancel_subscription_link ?>"><?php echo $cancel_subscription_link_text ?></a>
		<?php } ?>
		<?php } ?>
	</p>
</div>