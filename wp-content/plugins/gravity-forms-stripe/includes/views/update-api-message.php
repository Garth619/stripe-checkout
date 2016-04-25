</p></div>
<script language="javascript">
	jQuery( window ).load( function () {
		tb_show( 'Important Gravity Forms + Stripe Update Message', "#TB_inline?height=400&amp;width=400&amp;inlineId=gfp_stripe_upgrade_message_container" );
	} );
</script>
<style>
	body div#TB_window[style] {
		width: 435px !important;
		height: 430px !important;
		margin-left: -202px !important;
		background-image: none;
	}
</style>
<div id="gfp_stripe_upgrade_message_container" style="display:none;">
	<div id="gfp_stripe_update_message">
		<h1><?php _e( 'I hate popups.', 'gravity-forms-stripe' ); ?></h1>

		<h2 style="line-height: 1.2"><?php echo sprintf( __( 'But since I don\'t have your email address, I couldn\'t email you to let you know that %syou need to update your Stripe API%s for this version of Gravity Forms + Stripe.', 'gravity-forms-stripe' ), '<strong>', '</strong>' ); ?></h2>

		<h2 style="line-height: 1.2"><?php _e( 'And I don\'t want your payments to stop working.', 'gravity-forms-stripe' ); ?></h2>

		<h2><?php echo sprintf( __( 'So %supdate your Stripe API %shere%s%s.', 'gravity-forms-stripe' ), '<strong>', '<a href="https://dashboard.stripe.com/account/apikeys" target="_blank">', '</a>', '</strong>' ); ?></h2>

		<h2 style="line-height: 1.2"><?php echo sprintf( __( 'Then, %slet me know what your email address is%s so I can email you important plugin updates like this, instead of having to use this ugly popup :-)', 'gravity-forms-stripe' ), '<a href="https://gravityplus.pro/gravity-forms-stripe/updates/?utm_source=gravity-forms-stripe&utm_medium=link&utm_content=upgrade-message&utm_campaign=gravity-forms-stripe" target="_blank">', '</a>' ); ?></h2>
	</div>
</div>
<div><p>