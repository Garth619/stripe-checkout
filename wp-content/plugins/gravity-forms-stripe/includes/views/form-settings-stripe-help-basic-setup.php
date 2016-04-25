<?php
?>
<h2><?php _e( 'Gravity Forms + Stripe Basic Setup', 'gravity-forms-stripe' ); ?></h2>
<hr />
<ol>
	<li><?php _e( 'Make sure that Gravity Forms is activated', 'gravity-forms-stripe' ); ?></li>
	<li><?php echo sprintf( __( 'Add your Stripe API keys to the %sStripe settings page%s', 'gravity-forms-stripe' ), '<a href="/wp-admin/admin.php?page=gf_settings&subview=Stripe">', '</a>' ); ?></li>
	<li><?php echo sprintf( __( 'Select and save your currency on the %sGravity Forms settings page%s', 'gravity-forms-stripe' ), '<a href="/wp-admin/admin.php?page=gf_settings&subview=settings">', '</a>' ); ?></li>
	<li><?php echo sprintf( __( 'Add at least one %sProduct%s field to your form, so that your form will always submit a total amount of at least $0.50', 'gravity-forms-stripe' ), '<strong>', '</strong>' ); ?></li>
	<li><?php echo sprintf( __( 'Add a %sCredit Card%s field to your form. If a multi-page form, make sure it\'s on the last page.', 'gravity-forms-stripe' ), '<strong>', '</strong>' ); ?></li>
	<li><?php echo sprintf( __( 'Create a %sStripe Rule%s for your form', 'gravity-forms-stripe' ), '<strong>', '</strong>' ); ?></li>
</ol>