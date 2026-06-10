<?php
/**
 * StorySentry theme bootstrap.
 *
 * @package StorySentry
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function storysentry_theme_setup(): void {
	add_editor_style( 'style.css' );

	$categories = array(
		'storysentry-front-page' => __( 'StorySentry Front Page', 'storysentry' ),
		'storysentry-archives'   => __( 'StorySentry Archives', 'storysentry' ),
		'storysentry-single'     => __( 'StorySentry Single', 'storysentry' ),
		'storysentry-utility'    => __( 'StorySentry Utility', 'storysentry' ),
	);

	foreach ( $categories as $slug => $label ) {
		if ( function_exists( 'register_block_pattern_category' ) ) {
			register_block_pattern_category(
				$slug,
				array(
					'label' => $label,
				)
			);
		}
	}

	register_nav_menus( array(
		'header_menu'   => __( 'Header Menu', 'storysentry' ),
		'footer_menu_1' => __( 'Footer Menu 1 (Left Column)', 'storysentry' ),
		'footer_menu_2' => __( 'Footer Menu 2 (Middle Column)', 'storysentry' ),
		'footer_menu_3' => __( 'Footer Menu 3 (Right Column)', 'storysentry' ),
		'footer_menu_4' => __( 'Footer Bottom Links', 'storysentry' ),
	) );
}
add_action( 'after_setup_theme', 'storysentry_theme_setup' );



function storysentry_theme_enqueue_assets(): void {
	$style_path = get_stylesheet_directory() . '/style.css';
	$version    = file_exists( $style_path ) ? (string) filemtime( $style_path ) : wp_get_theme()->get( 'Version' );

	wp_enqueue_style(
		'storysentry-theme',
		get_stylesheet_uri(),
		array(),
		$version
	);
}
add_action( 'wp_enqueue_scripts', 'storysentry_theme_enqueue_assets' );
