<?php
/**
 * The template for displaying all pages.
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site will use a
 * different template.
 *
 * @package storefront
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?> <?php storefront_html_tag_schema(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="profile" href="http://gmpg.org/xfn/11">
<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">

<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<div id="page" class="hfeed site">
	<?php
	do_action( 'storefront_before_header' ); ?>

<!--
	<header id="masthead" class="site-header" role="banner" <?php if ( get_header_image() != '' ) { echo 'style="background-image: url(' . esc_url( get_header_image() ) . ');"'; } ?>>
		<div class="col-full">
-->

			<?php
			/**
			 * @hooked storefront_skip_links - 0
			 * @hooked storefront_social_icons - 10
			 * @hooked storefront_site_branding - 20
			 * @hooked storefront_secondary_navigation - 30
			 * @hooked storefront_product_search - 40
			 * @hooked storefront_primary_navigation - 50
			 * @hooked storefront_header_cart - 60
			 */
			// do_action( 'storefront_header' ); ?>

<!--
		</div>
	</header>
--><!-- #masthead -->

	<?php
	/**
	 * @hooked storefront_header_widget_region - 10
	 */
	//do_action( 'storefront_before_content' ); ?>

<!-- 	<div id="content" class="garrett site-content" tabindex="-1"> -->
<!-- 		<div class="col-full"> -->

		<?php
		/**
		 * @hooked woocommerce_breadcrumb - 10
		 */
		do_action( 'storefront_content_top' ); ?>

<!--
	<div id="primary" class="content-area">
		<main id="main" class="site-main" role="main">
-->

			<?php while ( have_posts() ) : the_post(); ?>

				<?php
				do_action( 'storefront_page_before' );
				?>

				<?php get_template_part( 'content', 'page' ); ?>

				<?php
				/**
				 * @hooked storefront_display_comments - 10
				 */
				do_action( 'storefront_page_after' );
				?>

			<?php endwhile; // end of the loop. ?>

<!-- 		</main> --><!-- #main -->
<!-- 	</div> --><!-- #primary -->

<?php  // do_action( 'storefront_sidebar' ); ?>
<!-- 		</div> --><!-- .col-full -->
<!-- 	</div> --><!-- #content -->

	<?php do_action( 'storefront_before_footer' ); ?>

	
	<?php do_action( 'storefront_after_footer' ); ?>

</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
