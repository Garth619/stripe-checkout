<?php
?>
<h2><?php _e( 'Troubleshooting', 'gravity-forms-stripe' ); ?></h2>
<hr/>
<h3><?php _e( 'This page is unsecured. Do not enter a real credit card number. Use this field only for testing purposes.', 'gravity-forms-stripe' ); ?></h3>
<p><?php _e( 'Your page needs to be secured with an SSL certificate. Here\'s how:', 'gravity-forms-stripe' ); ?></p>
<ol>
	<li><?php echo sprintf( __( 'Obtain an SSL certificate. You can get this from your host, or you can %spurchase your own at Namecheap for $9%s if your host allows you to use your own certificate', 'gravity-forms-stripe' ), '<a href="https://www.namecheap.com/security/ssl-certificates/comodo/positivessl.aspx?aff=79126" target="_blank">', '</a>' ); ?></li>
	<li><?php _e( 'Have your host install and activate the SSL certificate on your site', 'gravity-forms-stripe' ); ?></li>
	<li><?php echo sprintf( __( 'Use the %sReally Simple SSL plugin%s or the %sWordPress HTTPS plugin%s to secure the page that contains your payment form', 'gravity-forms-stripe' ), '<a href="https://wordpress.org/plugins/really-simple-ssl/" target="_blank">', '</a>', '<a href="http://wordpress.org/plugins/wordpress-https/" target="_blank">', '</a>' ); ?></li>
</ol>
</p>
<h3><?php _e( 'This form cannot process your payment.', 'gravity-forms-stripe' ); ?></h3>
<p><?php echo sprintf( __( 'This is a "pretty" error displayed when your form is in live mode and Stripe has returned an error that is unsafe to show to customers. To see the actual errors, either a) place your form in %1$stest mode%2$s or b) use the %1$sGravity Forms Logging Tool%2$s.', 'gravity-forms-stripe' ), '<strong>', '</strong>' ); ?></p>
<p><?php echo sprintf( __( 'The Gravity Forms Stripe Add-On integrates with the Gravity Forms Logging Tool (available on the %sGravity Forms Downloads page%s), allowing you to see exactly what’s going on when an error is encountered. Here’s how to set it up:', 'gravity-forms-stripe' ), '<a href="http://www.gravityhelp.com/downloads/" target="_blank">', '</a>' ); ?>
<ol>
	<li><?php _e( 'Install and activate the Logging Tool', 'gravity-forms-stripe' ); ?></li>
	<li><?php echo sprintf( __( 'On the %sLogging settings page%s (Forms->Settings->Logging), turn logging on for \'Gravity Forms + Stripe\'', 'gravity-forms-stripe' ), '<a href="/wp-admin/admin.php?page=gf_settings&subview=Logging">', '</a>' ); ?></li>
	<li><?php _e( 'Once some activity has occurred on your site, you can come back to the Logging settings page and see the link to view the log', 'gravity-forms-stripe' ); ?></li>
</ol>
</p>
<h3><?php _e( 'Empty token or Empty string given for card', 'gravity-forms-stripe' ); ?></h3>
<p><?php _e( 'This plugin is required to use JavaScript, therefore the most common error is the JavaScript error also known
as \'Empty token\' or\'Empty string given for card\'.', 'gravity-forms-stripe' ) ?>
</p>
<p><?php _e( 'Here are a few possible reasons, listed in the order they are most likely to occur:', 'gravity-forms-stripe' ); ?></p>
<ol>
	<li><?php _e( "Your theme (especially if purchased from Themeforest) is stripping the shortcode, preventing the Stripe JS from working. Here's what you want to look for in your theme files (the code is in yellow) and disable by placing a '//' without the quotes in front of those lines:", 'gravity-forms-stripe' ); ?>
		<br/><br/>
		<?php echo sprintf( __( '%sOffending lines%s', 'gravity-forms-stripe' ), '<a href="http://kaptinlin.com/support/discussion/7420/gravity-forms-code-of-the-raw-shortcode-discussion-thread/p1" target="_blank">', '</a>' ); ?>
		<br/><br/>
		<?php _e( 'This code may also be in a file called shortcodes.php or ThemeShortcodes.php.', 'gravity-forms-stripe' ); ?>
	</li>
	<li><?php _e( "Another theme or plugin is modifying the standard Gravity Forms dropdowns and removing the classes, which breaks the Stripe JS. You'll want to contact the theme author to learn how to prevent this.", 'gravity-forms-stripe' ); ?>
	</li>
	<li><?php echo sprintf( __( "You've embedded your Gravity Form directly into the page and missed one of the %sGravity Forms instructions%s — happens to the best of us!", 'gravity-forms-stripe' ), '<a href="http://www.gravityhelp.com/documentation/page/Embedding_A_Form" target="_blank">', '</a>' ); ?>
	</li>
	<li><?php echo sprintf( __( "Another plugin is preventing the JS from working. Follow the %s procedure outlined here by Gravity Forms%s in order to troubleshoot", 'gravity-forms-stripe' ), '<a href="http://www.gravityhelp.com/documentation/page/Testing_for_a_Theme/Plugin_Conflict" target="_blank">', '</a>' ); ?>
	</li>
	<li><?php _e( 'JavaScript is not available to browser', 'gravity-forms-stripe' ); ?></li>
</ol>
<br/>
<h3><?php _e( 'Reported Conflicts', 'gravity-forms-stripe' ); ?></h3>
<p><?php _e( 'These conflicts were last reported in 2012/2013', 'gravity-forms-stripe' ); ?></p>
<ul>
	<li><?php _e( 'plugin: Shortcodes Ultimate', 'gravity-forms-stripe' ); ?></li>
	<li><?php _e( 'plugin: PHP Shortcode Version 1.3', 'gravity-forms-stripe' ); ?></li>
	<li><?php _e( 'plugin: Root Relative URLs', 'gravity-forms-stripe' ); ?></li>
</ul>
<br />
<h3><?php _e( 'Still receiving an error?', 'gravity-forms-stripe' ); ?></h3>
<p><?php echo sprintf( __( "If you have tried the troubleshooting steps and things still aren't working, you will need to %spurchase support%s", 'gravity-forms-stripe' ), "<a href='http://gravityplus.pro/gravity-forms-stripe/get-help?utm_source=gravity-forms-stripe&utm_medium=link&utm_content=stripe-help-troubleshooting&utm_campaign=gravity-forms-stripe' target='_blank'>", '</a>' );?></p>