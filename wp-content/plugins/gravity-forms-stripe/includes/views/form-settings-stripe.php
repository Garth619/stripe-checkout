<?php
?>
<h3><span class="icon-stripe"></span><?php _e( ' Stripe Settings', 'gravity-forms-stripe' ) ?></h3>
<?php if ( has_action( 'gfp_stripe_form_settings' ) ) { ?>
	<form action="" method="post" id="gfp_stripe_form_settings">
		<?php wp_nonce_field( 'gfp_stripe_save_form_settings', 'gfp_stripe_save_form_settings' ) ?>

		<?php do_action( 'gfp_stripe_form_settings', $form_id ); ?>

		<input type="submit" id="gfp_stripe_update_form_settings" name="gfp_stripe_update_form_settings"
			   value="<?php _e( 'Update Settings', 'gravityforms-stripe-more' ); ?>" class="button-primary gfbutton"/>

	</form>
<?php } ?>

<h2><?php
	_e( 'Rules', 'gravity-forms-stripe' );
	?>
	<a id="add-new_stripe-feed" class="add-new-h2"
	   href="<?php echo $add_new_url ?>"><?php _e( 'Add New', 'gravity-forms-stripe' ) ?></a>

</h2>
<form id="stripe_feeds_list_form" method="post">

	<?php $stripe_feeds_table->display(); ?>

	<input id="action_argument" name="action_argument" type="hidden"/> <input id="action" name="action" type="hidden"/>

	<?php wp_nonce_field( 'gfp_stripe_feeds_list_action', 'gfp_stripe_feeds_list_action' ) ?>

</form>