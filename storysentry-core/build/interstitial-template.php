<?php
/**
 * StorySentry interstitial template.
 *
 * @package StorySentryCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $post;

if ( $post instanceof WP_Post ) {
	setup_postdata( $post );
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'ss-interstitial-page' ); ?>>
<?php wp_body_open(); ?>
<div class="wp-site-blocks">
	<?php echo do_blocks( '<!-- wp:pattern {"slug":"storysentry/interstitial"} /-->' ); ?>
</div>
<?php wp_footer(); ?>
</body>
</html>
<?php
if ( $post instanceof WP_Post ) {
	wp_reset_postdata();
}
