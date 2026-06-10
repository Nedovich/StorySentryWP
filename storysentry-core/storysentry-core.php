<?php
/**
 * Plugin Name: StorySentry Core
 * Plugin URI: https://example.com/storysentry-core
 * Description: Custom post type, editorial blocks, and outbound flow for StorySentry.
 * Version: 0.4.3
 * Author: Nedim Esken
 * License: GPL-2.0-or-later
 * Text Domain: storysentry-core
 *
 * @package StorySentryCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'STORYSENTRY_CORE_FILE', __FILE__ );
define( 'STORYSENTRY_CORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'STORYSENTRY_CORE_URL', plugin_dir_url( __FILE__ ) );

function storysentry_core_register_post_type(): void {
	register_post_type(
		'ss_story',
		array(
			'labels' => array(
				'name'               => __( 'Stories', 'storysentry-core' ),
				'singular_name'      => __( 'Story', 'storysentry-core' ),
				'add_new_item'       => __( 'Add New Story', 'storysentry-core' ),
				'edit_item'          => __( 'Edit Story', 'storysentry-core' ),
				'new_item'           => __( 'New Story', 'storysentry-core' ),
				'view_item'          => __( 'View Story', 'storysentry-core' ),
				'search_items'       => __( 'Search Stories', 'storysentry-core' ),
				'not_found'          => __( 'No stories found.', 'storysentry-core' ),
				'not_found_in_trash' => __( 'No stories found in Trash.', 'storysentry-core' ),
			),
			'public'            => true,
			'show_in_rest'      => true,
			'menu_icon'         => 'dashicons-media-document',
			'has_archive'       => true,
			'rewrite'           => array(
				'slug'       => 'stories',
				'with_front' => false,
			),
			'supports'          => array( 'title', 'editor', 'excerpt', 'author', 'revisions', 'custom-fields' ),
			'taxonomies'        => array( 'category' ),
			'show_in_nav_menus' => true,
			'publicly_queryable'=> true,
			'menu_position'     => 20,
		)
	);
}
add_action( 'init', 'storysentry_core_register_post_type' );

function storysentry_core_register_taxonomy(): void {
	register_taxonomy(
		'ss_source_domain',
		'ss_story',
		array(
			'labels' => array(
				'name'          => __( 'Source Domains', 'storysentry-core' ),
				'singular_name' => __( 'Source Domain', 'storysentry-core' ),
			),
			'public'            => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'hierarchical'      => false,
			'rewrite'           => array(
				'slug'       => 'source',
				'with_front' => false,
			),
		)
	);

	register_taxonomy_for_object_type( 'category', 'ss_story' );
}
add_action( 'init', 'storysentry_core_register_taxonomy' );

function storysentry_core_register_meta(): void {
	$meta_fields = array(
		'link'               => 'esc_url_raw',
		'image_url'          => 'esc_url_raw',
		'source_domain_link' => 'esc_url_raw',
		'published_at'       => 'sanitize_text_field',
	);

	foreach ( $meta_fields as $key => $sanitize_callback ) {
		register_post_meta(
			'ss_story',
			$key,
			array(
				'type'              => 'string',
				'single'            => true,
				'sanitize_callback' => $sanitize_callback,
				'show_in_rest'      => true,
				'auth_callback'     => static function() {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}
}
add_action( 'init', 'storysentry_core_register_meta' );

function storysentry_core_register_rest_fields(): void {
	register_rest_field(
		'ss_story',
		'source_domain',
		array(
			'get_callback'    => static function( array $object ) {
				$terms = get_the_terms( (int) $object['id'], 'ss_source_domain' );

				if ( empty( $terms ) || is_wp_error( $terms ) ) {
					return '';
				}

				return $terms[0]->name;
			},
			'update_callback' => static function( $value, WP_Post $post ) {
				$value = sanitize_text_field( (string) $value );

				if ( '' === $value ) {
					wp_set_object_terms( $post->ID, array(), 'ss_source_domain', false );
					return true;
				}

				$term = term_exists( $value, 'ss_source_domain' );

				if ( ! $term ) {
					$term = wp_insert_term( $value, 'ss_source_domain' );
				}

				if ( is_wp_error( $term ) ) {
					return $term;
				}

				$term_id = is_array( $term ) ? (int) $term['term_id'] : (int) $term;
				wp_set_object_terms( $post->ID, array( $term_id ), 'ss_source_domain', false );

				return true;
			},
			'schema'          => array(
				'description' => __( 'Primary source domain mapped to the ss_source_domain taxonomy.', 'storysentry-core' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
		)
	);

	register_rest_field(
		'ss_story',
		'summary',
		array(
			'get_callback'    => static function( array $object ) {
				return wp_strip_all_tags( get_the_excerpt( (int) $object['id'] ) );
			},
			'update_callback' => static function( $value, WP_Post $post ) {
				$result = wp_update_post(
					array(
						'ID'           => $post->ID,
						'post_excerpt' => sanitize_textarea_field( (string) $value ),
					),
					true
				);

				return ! is_wp_error( $result );
			},
			'schema'          => array(
				'description' => __( 'Story summary stored in the post excerpt.', 'storysentry-core' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
		)
	);

	register_rest_field(
		'ss_story',
		'category',
		array(
			'get_callback'    => static function( array $object ) {
				$terms = get_the_terms( (int) $object['id'], 'category' );

				if ( empty( $terms ) || is_wp_error( $terms ) ) {
					return '';
				}

				return $terms[0]->name;
			},
			'update_callback' => static function( $value, WP_Post $post ) {
				$raw_value = sanitize_text_field( (string) $value );
				$value     = sanitize_title( $raw_value );

				if ( '' === $value ) {
					wp_set_object_terms( $post->ID, array(), 'category', false );
					return true;
				}

				$term = get_term_by( 'slug', $value, 'category' );

				if ( ! $term ) {
					$term = get_term_by( 'name', $raw_value, 'category' );
				}

				if ( ! $term || is_wp_error( $term ) ) {
					return new WP_Error(
						'storysentry_unknown_category',
						__( 'The provided category does not exist in WordPress. Create it first, then send its slug from n8n.', 'storysentry-core' ),
						array( 'status' => 400 )
					);
				}

				wp_set_object_terms( $post->ID, array( (int) $term->term_id ), 'category', false );

				return true;
			},
			'schema'          => array(
				'description' => __( 'Existing WordPress category slug. The term must already exist.', 'storysentry-core' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
		)
	);

	$meta_rest_fields = array(
		'link'               => array(
			'description' => __( 'Original article URL used for outbound redirects.', 'storysentry-core' ),
			'sanitize'    => 'esc_url_raw',
		),
		'image_url'          => array(
			'description' => __( 'Hotlinked image URL.', 'storysentry-core' ),
			'sanitize'    => 'esc_url_raw',
		),
		'source_domain_link' => array(
			'description' => __( 'Canonical URL for the source domain.', 'storysentry-core' ),
			'sanitize'    => 'esc_url_raw',
		),
		'published_at'       => array(
			'description' => __( 'Original source publish timestamp, separate from the WordPress publish date.', 'storysentry-core' ),
			'sanitize'    => 'sanitize_text_field',
		),
	);

	foreach ( $meta_rest_fields as $field_name => $config ) {
		register_rest_field(
			'ss_story',
			$field_name,
			array(
				'get_callback'    => static function( array $object, string $name ) {
					return (string) get_post_meta( (int) $object['id'], $name, true );
				},
				'update_callback' => static function( $value, WP_Post $post, string $name ) use ( $config ) {
					$sanitized = call_user_func( $config['sanitize'], (string) $value );

					if ( '' === $sanitized ) {
						delete_post_meta( $post->ID, $name );
						return true;
					}

					return false !== update_post_meta( $post->ID, $name, $sanitized );
				},
				'schema'          => array(
					'description' => $config['description'],
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
			)
		);
	}
}
add_action( 'rest_api_init', 'storysentry_core_register_rest_fields' );

function storysentry_core_register_routes(): void {
	add_rewrite_rule( '^stories/([^/]+)/interstitial/?$', 'index.php?post_type=ss_story&name=$matches[1]&ss_interstitial=1', 'top' );
	add_rewrite_rule( '^stories/([^/]+)/out/?$', 'index.php?post_type=ss_story&name=$matches[1]&ss_go=1', 'top' );
}
add_action( 'init', 'storysentry_core_register_routes' );

function storysentry_core_query_vars( array $vars ): array {
	$vars[] = 'ss_interstitial';
	$vars[] = 'ss_go';
	return $vars;
}
add_filter( 'query_vars', 'storysentry_core_query_vars' );

function storysentry_core_activation(): void {
	storysentry_core_register_post_type();
	storysentry_core_register_taxonomy();
	storysentry_core_register_routes();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'storysentry_core_activation' );

function storysentry_core_deactivation(): void {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'storysentry_core_deactivation' );

function storysentry_core_filter_search( WP_Query $query ): void {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
		return;
	}

	$query->set( 'post_type', array( 'ss_story' ) );
}
add_action( 'pre_get_posts', 'storysentry_core_filter_search' );

function storysentry_core_register_block_category( array $categories ): array {
	$categories[] = array(
		'slug'  => 'storysentry',
		'title' => __( 'StorySentry', 'storysentry-core' ),
	);

	return $categories;
}
add_filter( 'block_categories_all', 'storysentry_core_register_block_category' );

function storysentry_core_register_editor_assets(): void {
	$script_path = STORYSENTRY_CORE_PATH . 'assets/editor.js';

	if ( ! file_exists( $script_path ) ) {
		return;
	}

	wp_register_script(
		'storysentry-core-editor',
		STORYSENTRY_CORE_URL . 'assets/editor.js',
		array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-server-side-render', 'wp-data', 'wp-core-data', 'wp-i18n' ),
		(string) filemtime( $script_path ),
		true
	);
}
add_action( 'init', 'storysentry_core_register_editor_assets' );

function storysentry_core_get_settings(): array {
	$defaults = array(
		'default_click_target' => 'interstitial',
	);

	$settings = get_option( 'storysentry_core_settings', array() );

	if ( ! is_array( $settings ) ) {
		$settings = array();
	}

	return wp_parse_args( $settings, $defaults );
}

function storysentry_core_get_default_click_target(): string {
	$settings = storysentry_core_get_settings();
	$target   = isset( $settings['default_click_target'] ) ? sanitize_key( (string) $settings['default_click_target'] ) : 'interstitial';

	if ( ! in_array( $target, array( 'single', 'interstitial', 'source' ), true ) ) {
		return 'interstitial';
	}

	return $target;
}

function storysentry_core_sanitize_settings( array $settings ): array {
	$target = isset( $settings['default_click_target'] ) ? sanitize_key( (string) $settings['default_click_target'] ) : 'interstitial';

	if ( ! in_array( $target, array( 'single', 'interstitial', 'source' ), true ) ) {
		$target = 'interstitial';
	}

	return array(
		'default_click_target' => $target,
	);
}

function storysentry_core_register_settings(): void {
	register_setting(
		'storysentry_core_settings_group',
		'storysentry_core_settings',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'storysentry_core_sanitize_settings',
			'default'           => array(
				'default_click_target' => 'interstitial',
			),
		)
	);
}
add_action( 'admin_init', 'storysentry_core_register_settings' );

function storysentry_core_add_settings_page(): void {
	add_options_page(
		__( 'StorySentry Settings', 'storysentry-core' ),
		__( 'StorySentry', 'storysentry-core' ),
		'manage_options',
		'storysentry-core-settings',
		'storysentry_core_render_settings_page'
	);
}
add_action( 'admin_menu', 'storysentry_core_add_settings_page' );

function storysentry_core_render_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$settings = storysentry_core_get_settings();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'StorySentry Settings', 'storysentry-core' ); ?></h1>
		<form action="options.php" method="post">
			<?php settings_fields( 'storysentry_core_settings_group' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="storysentry-default-click-target"><?php esc_html_e( 'Default story click target', 'storysentry-core' ); ?></label>
					</th>
					<td>
						<select id="storysentry-default-click-target" name="storysentry_core_settings[default_click_target]">
							<option value="single" <?php selected( $settings['default_click_target'], 'single' ); ?>><?php esc_html_e( 'Single Story page', 'storysentry-core' ); ?></option>
							<option value="interstitial" <?php selected( $settings['default_click_target'], 'interstitial' ); ?>><?php esc_html_e( 'Interstitial ad page', 'storysentry-core' ); ?></option>
							<option value="source" <?php selected( $settings['default_click_target'], 'source' ); ?>><?php esc_html_e( 'Original source URL', 'storysentry-core' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Controls where story cards go when a block does not explicitly override click behavior.', 'storysentry-core' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

function storysentry_core_get_source_domain( int $post_id ): string {
	$terms = get_the_terms( $post_id, 'ss_source_domain' );

	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return '';
	}

	return $terms[0]->name;
}

function storysentry_core_get_source_domain_link( int $post_id ): string {
	return (string) get_post_meta( $post_id, 'source_domain_link', true );
}

function storysentry_core_format_published_at( int $post_id ): string {
	$published_at = (string) get_post_meta( $post_id, 'published_at', true );

	if ( '' === $published_at ) {
		return get_the_date( 'M j, Y', $post_id );
	}

	$timestamp = strtotime( $published_at );

	if ( false === $timestamp ) {
		return $published_at;
	}

	return wp_date( get_option( 'date_format' ), $timestamp );
}

function storysentry_core_get_excerpt( WP_Post $post ): string {
	if ( has_excerpt( $post ) ) {
		return wp_strip_all_tags( get_the_excerpt( $post ) );
	}

	return wp_trim_words( wp_strip_all_tags( $post->post_content ), 34, '…' );
}

function storysentry_core_get_source_short( int $post_id ): string {
	$source = storysentry_core_get_source_domain( $post_id );

	if ( '' === $source ) {
		return '';
	}

	$normalized = strtoupper( preg_replace( '/\s+/', ' ', trim( $source ) ) );
	$normalized = preg_replace( '/[^A-Z0-9 ]/', '', $normalized );

	if ( strlen( $normalized ) <= 12 ) {
		return $normalized;
	}

	$parts = preg_split( '/\s+/', $normalized );

	if ( count( $parts ) > 1 ) {
		$initials = array_map(
			static function( string $part ): string {
				return substr( $part, 0, 1 );
			},
			$parts
		);

		return implode( '', $initials );
	}

	return substr( $normalized, 0, 12 );
}

function storysentry_core_get_ago( int $post_id ): string {
	$published_at = (string) get_post_meta( $post_id, 'published_at', true );
	$timestamp    = $published_at ? strtotime( $published_at ) : get_post_timestamp( $post_id );

	if ( ! $timestamp ) {
		return '';
	}

	$diff = time() - $timestamp;

	if ( $diff < 60 ) {
		return 'now';
	}

	if ( $diff < HOUR_IN_SECONDS ) {
		return floor( $diff / MINUTE_IN_SECONDS ) . 'm';
	}

	if ( $diff < DAY_IN_SECONDS ) {
		return floor( $diff / HOUR_IN_SECONDS ) . 'h';
	}

	if ( $diff < WEEK_IN_SECONDS ) {
		return floor( $diff / DAY_IN_SECONDS ) . 'd';
	}

	return wp_date( 'M j', $timestamp );
}

function storysentry_core_get_read_time( WP_Post $post ): int {
	$text  = wp_strip_all_tags( $post->post_title . ' ' . $post->post_excerpt . ' ' . $post->post_content );
	$words = str_word_count( $text );
	return max( 3, (int) ceil( $words / 200 ) );
}

function storysentry_core_get_category_term( int $post_id ): ?WP_Term {
	$terms = get_the_terms( $post_id, 'category' );

	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return null;
	}

	return $terms[0];
}

function storysentry_core_render_cat_eyebrow( int $post_id ): string {
	$term = storysentry_core_get_category_term( $post_id );

	if ( ! $term ) {
		return '';
	}

	return sprintf( '<span class="ss-cat-eye">%s</span>', esc_html( $term->name ) );
}

function storysentry_core_render_publine( WP_Post $post, bool $with_read = true ): string {
	$pub  = storysentry_core_get_source_short( $post->ID );
	$ago  = storysentry_core_get_ago( $post->ID );
	$read = storysentry_core_get_read_time( $post );

	$parts   = array();
	$parts[] = sprintf( '<span class="ss-pub">%s</span>', esc_html( $pub ) );

	if ( '' !== $ago ) {
		$parts[] = '<span class="ss-dot">·</span><span class="ss-ago">' . esc_html( $ago ) . ' ago</span>';
	}

	if ( $with_read ) {
		$parts[] = '<span class="ss-dot">·</span><span class="ss-read">' . esc_html( (string) $read ) . ' min read</span>';
	}

	return '<div class="ss-publine">' . implode( '', $parts ) . '</div>';
}

function storysentry_core_render_bg_image( int $post_id, string $class, string $credit = '' ): string {
	$image_url = (string) get_post_meta( $post_id, 'image_url', true );

	if ( '' === $image_url ) {
		return '';
	}

	$style = sprintf( ' style="background-image:url(%s)"', esc_url( $image_url ) );
	$html  = '<div class="' . esc_attr( $class ) . '"' . $style . '>';

	if ( '' !== $credit ) {
		$html .= '<span class="ss-img-cred">' . esc_html( $credit ) . '</span>';
	}

	$html .= '</div>';

	return $html;
}

function storysentry_core_get_interstitial_url( int $post_id ): string {
	return trailingslashit( get_permalink( $post_id ) ) . 'interstitial/';
}

function storysentry_core_get_out_url( int $post_id ): string {
	return trailingslashit( get_permalink( $post_id ) ) . 'out/';
}

function storysentry_core_render_source_badge( string $source_domain, string $source_link = '' ): string {
	$short = strtoupper( preg_replace( '/[^A-Z0-9 ]/', '', $source_domain ) );

	if ( '' === $short ) {
		return '';
	}

	$content = sprintf( '<span class="ss-pub">%s</span>', esc_html( $short ) );

	if ( '' !== $source_link ) {
		return sprintf(
			'<a class="ss-hit" href="%1$s" rel="noopener noreferrer nofollow" target="_blank">%2$s</a>',
			esc_url( $source_link ),
			$content
		);
	}

	return $content;
}

function storysentry_core_render_story_meta( int $post_id ): string {
	$post = get_post( $post_id );

	if ( ! $post instanceof WP_Post ) {
		return '';
	}

	return storysentry_core_render_publine( $post );
}

function storysentry_core_render_story_image( int $post_id, string $size_class = '' ): string {
	return storysentry_core_render_bg_image( $post_id, 'ss-card-img ' . $size_class );
}

function storysentry_core_render_story_card_from_post( WP_Post $post, array $attributes = array() ): string {
	$layout       = isset( $attributes['layout'] ) ? sanitize_key( (string) $attributes['layout'] ) : 'med';
	$show_image   = array_key_exists( 'showImage', $attributes ) ? (bool) $attributes['showImage'] : true;
	$show_excerpt = array_key_exists( 'showExcerpt', $attributes ) ? (bool) $attributes['showExcerpt'] : true;
	$numbered     = array_key_exists( 'numbered', $attributes ) ? (bool) $attributes['numbered'] : false;
	$link_target  = isset( $attributes['linkTarget'] ) && '' !== $attributes['linkTarget'] ? sanitize_key( (string) $attributes['linkTarget'] ) : storysentry_core_get_default_click_target();
	$url          = storysentry_core_get_interstitial_url( $post->ID );

	if ( 'single' === $link_target ) {
		$url = get_permalink( $post );
	} elseif ( 'source' === $link_target ) {
		$source_url = (string) get_post_meta( $post->ID, 'link', true );
		$url        = $source_url ? $source_url : get_permalink( $post );
	}
	$eyebrow      = storysentry_core_render_cat_eyebrow( $post->ID );
	$title        = esc_html( get_the_title( $post ) );
	$excerpt      = esc_html( storysentry_core_get_excerpt( $post ) );
	$publine      = storysentry_core_render_publine( $post, 'lead' === $layout || 'med' === $layout || 'search' === $layout );
	$credit       = 'PHOTO · ' . storysentry_core_get_source_short( $post->ID );

	switch ( $layout ) {
		case 'ticker':
			return sprintf(
				'<a class="ss-ticker-item" href="%1$s"><span class="ss-ticker-pub">%2$s</span><span class="ss-ticker-ttl">%3$s</span><span class="ss-ticker-ago">%4$s</span></a>',
				esc_url( $url ),
				esc_html( storysentry_core_get_source_short( $post->ID ) ),
				$title,
				esc_html( storysentry_core_get_ago( $post->ID ) )
			);

		case 'lead':
		case 'hero':
			return sprintf(
				'<a class="ss-card ss-card--lead ss-hit" href="%1$s">%2$s<div class="ss-card-body">%3$s<h2 class="ss-h-lead">%4$s</h2>%5$s%6$s</div></a>',
				esc_url( $url ),
				$show_image ? storysentry_core_render_bg_image( $post->ID, 'ss-card-img', $credit ) : '',
				$eyebrow,
				$title,
				$show_excerpt ? '<p class="ss-deck">' . $excerpt . '</p>' : '',
				$publine
			);

		case 'row':
		case 'list':
			return sprintf(
				'<a class="ss-row ss-hit" href="%1$s">%2$s%3$s<div class="ss-row-body"><h4 class="ss-h-row">%4$s</h4>%5$s</div></a>',
				esc_url( $url ),
				$numbered ? '<span class="ss-row-num"></span>' : '',
				$show_image ? storysentry_core_render_bg_image( $post->ID, 'ss-row-img' ) : '',
				$title,
				storysentry_core_render_publine( $post, false )
			);

		case 'search':
			return sprintf(
				'<a class="ss-search-row ss-hit" href="%1$s">%2$s<div class="ss-search-row-body">%3$s<h3>%4$s</h3>%5$s%6$s</div></a>',
				esc_url( $url ),
				$show_image ? storysentry_core_render_bg_image( $post->ID, 'ss-search-row-img' ) : '',
				$eyebrow,
				$title,
				$show_excerpt ? '<p class="ss-deck">' . $excerpt . '</p>' : '',
				$publine
			);

		case 'med':
		case 'grid':
		default:
			return sprintf(
				'<a class="ss-card ss-card--med ss-hit" href="%1$s">%2$s<div class="ss-card-body">%3$s<h3 class="ss-h-med">%4$s</h3>%5$s</div></a>',
				esc_url( $url ),
				$show_image ? storysentry_core_render_bg_image( $post->ID, 'ss-card-img--sm' ) : '',
				$eyebrow,
				$title,
				storysentry_core_render_publine( $post )
			);
	}
}

function storysentry_core_get_story_context( WP_Block $block ): ?WP_Post {
	$post_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : get_the_ID();

	if ( ! $post_id ) {
		return null;
	}

	$post = get_post( $post_id );

	if ( ! $post instanceof WP_Post || 'ss_story' !== $post->post_type ) {
		return null;
	}

	return $post;
}

function storysentry_core_is_editor_preview_request(): bool {
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';

	if ( defined( 'REST_REQUEST' ) && REST_REQUEST && false !== strpos( $request_uri, '/wp/v2/block-renderer/' ) ) {
		return true;
	}

	return false;
}

function storysentry_core_render_dummy_media( string $class, string $credit = '' ): string {
	$html = '<div class="' . esc_attr( $class ) . '" style="background:linear-gradient(135deg,#dfe6f2 0%,#f3efe7 52%,#d9dde7 100%);"></div>';

	if ( '' !== $credit ) {
		$html .= '<span class="ss-photo-credit">' . esc_html( $credit ) . '</span>';
	}

	return $html;
}

function storysentry_core_get_dummy_story(): array {
	return array(
		'title'    => 'WATCH: Make-A-Wish recipient now helping children’s dreams come true',
		'summary'  => 'Abigail Hoyle’s journey with Disney and the Make-A-Wish Foundation began years ago and now continues as she helps children experience the same joy as a cast member at the parks.',
		'category' => 'News',
		'source'   => 'ABC News',
		'short'    => 'ABC NEWS',
		'ago'      => '2d ago',
		'read'     => '3 min read',
	);
}

function storysentry_core_get_dummy_archive_items( int $count = 5 ): array {
	$items = array(
		array(
			'category' => 'News',
			'title'    => 'WATCH: Make-A-Wish recipient now helping children’s dreams come true',
			'source'   => 'ABC NEWS',
			'ago'      => '2d ago',
			'read'     => '3 min read',
			'excerpt'  => 'Abigail Hoyle’s journey with Disney and the Make-A-Wish Foundation began years ago and now continues as she helps children experience the same joy.',
		),
		array(
			'category' => 'Politics',
			'title'    => 'Trump admin tightens vise on student aid fraud in ‘ghost student’ crackdown',
			'source'   => 'FOX NEWS',
			'ago'      => '14h ago',
			'read'     => '4 min read',
			'excerpt'  => 'Federal officials say the new system is designed to detect fraud rings earlier and block fabricated enrollments before aid is released.',
		),
		array(
			'category' => 'World',
			'title'    => 'WATCH: Trump speaks after shooting incident outside White House Correspondents’ Dinner',
			'source'   => 'ABC NEWS',
			'ago'      => '2d ago',
			'read'     => '2 min read',
			'excerpt'  => 'Security restrictions tightened around the venue as speakers and guests continued through the evening program.',
		),
		array(
			'category' => 'US',
			'title'    => 'Donald Riegle, who represented Michigan in Congress under 7 presidents, dies at 88',
			'source'   => 'ABC NEWS',
			'ago'      => '1d ago',
			'read'     => '5 min read',
			'excerpt'  => 'The former senator’s long public career spanned multiple administrations and several eras of domestic policy fights.',
		),
		array(
			'category' => 'Tech',
			'title'    => 'WATCH: Crocodile caught on camera climbing into hotel kitchen in Zimbabwe',
			'source'   => 'ABC NEWS',
			'ago'      => '1d ago',
			'read'     => '2 min read',
			'excerpt'  => 'Video spread quickly online after the reptile wandered into a hotel’s service area before being guided out safely.',
		),
		array(
			'category' => 'Science',
			'title'    => 'Remote worker paraglides on the job in an unexpectedly literal office upgrade',
			'source'   => 'WIRED',
			'ago'      => '3h ago',
			'read'     => '6 min read',
			'excerpt'  => 'A playful dispatch from the edge of work culture became one of the most-shared offbeat stories of the morning.',
		),
	);

	return array_slice( $items, 0, max( 1, $count ) );
}

function storysentry_core_render_dummy_story_breadcrumbs( array $attributes = array() ): string {
	$front_label   = isset( $attributes['frontLabel'] ) ? sanitize_text_field( (string) $attributes['frontLabel'] ) : 'Front Page';
	$category_label = isset( $attributes['categoryLabel'] ) ? sanitize_text_field( (string) $attributes['categoryLabel'] ) : 'News';
	$current_label = isset( $attributes['currentLabel'] ) ? sanitize_text_field( (string) $attributes['currentLabel'] ) : 'ABC NEWS';
	$show_category = ! array_key_exists( 'showCategory', $attributes ) || (bool) $attributes['showCategory'];
	$category_html = $show_category ? '<a href="#">' . esc_html( $category_label ) . '</a><span>›</span>' : '';

	return '<div class="ss-art-crumbs"><a href="#">' . esc_html( $front_label ) . '</a><span>›</span>' . $category_html . '<span class="ss-art-crumb-cur">' . esc_html( $current_label ) . '</span></div>';
}

function storysentry_core_render_dummy_story_header( array $attributes = array() ): string {
	$story = storysentry_core_get_dummy_story();
	$eyebrow = isset( $attributes['eyebrowText'] ) && '' !== trim( (string) $attributes['eyebrowText'] ) ? sanitize_text_field( (string) $attributes['eyebrowText'] ) : $story['category'];
	$show_actions = ! array_key_exists( 'showActions', $attributes ) || (bool) $attributes['showActions'];
	$actions_html = $show_actions ? '<div class="ss-art-actions"><button class="ss-art-act">Save</button><button class="ss-art-act">Share</button><button class="ss-art-act">Listen</button></div>' : '';

	return '<header class="ss-art-head"><span class="ss-cat-eyebrow">' . esc_html( strtoupper( $eyebrow ) ) . '</span><h1 class="ss-art-title">' . esc_html( $story['title'] ) . '</h1><div class="ss-art-meta"><div class="ss-publine"><span class="ss-src">' . esc_html( $story['short'] ) . '</span><span class="ss-dot">·</span><span class="ss-ago">' . esc_html( $story['ago'] ) . '</span><span class="ss-dot">·</span><span class="ss-ago">' . esc_html( $story['read'] ) . '</span></div>' . $actions_html . '</div></header>';
}

function storysentry_core_render_dummy_story_image( array $attributes = array() ): string {
	$show_caption = ! array_key_exists( 'showCaption', $attributes ) || (bool) $attributes['showCaption'];
	$caption      = isset( $attributes['captionText'] ) && '' !== trim( (string) $attributes['captionText'] ) ? sanitize_text_field( (string) $attributes['captionText'] ) : 'Photograph via ABC News';
	$caption_html = $show_caption ? '<figcaption>' . esc_html( $caption ) . '</figcaption>' : '';

	return '<figure class="ss-art-fig">' . storysentry_core_render_dummy_media( 'ss-art-img', 'PHOTO · ABC NEWS' ) . $caption_html . '</figure>';
}

function storysentry_core_render_dummy_story_prose( array $attributes = array() ): string {
	$story = storysentry_core_get_dummy_story();
	$summary_tag = isset( $attributes['summaryTag'] ) && '' !== trim( (string) $attributes['summaryTag'] ) ? sanitize_text_field( (string) $attributes['summaryTag'] ) : 'RSS SUMMARY · AGGREGATED FROM ' . $story['short'];
	$paragraph_count = isset( $attributes['paragraphCount'] ) ? max( 1, min( 4, (int) $attributes['paragraphCount'] ) ) : 3;
	$paragraphs = array(
		'<p class="ss-art-lede"><span class="ss-art-dropcap">A</span>bigail Hoyle’s journey with Disney and the Make-A-Wish Foundation began years ago and now continues as she helps children experience the same joy as a cast member at the parks.</p>',
		'<p>The original report continues with additional details, on-scene context, and publisher-specific media. In StorySentry, this summary view is intentionally short so editors can control pacing without duplicating the full article.</p>',
		'<p>For the template editor, this preview uses lightweight dummy copy so the layout stays fast while still showing headline rhythm, metadata density, and vertical spacing.</p>',
		'<p>This extra paragraph exists so you can tune longer story layouts when you want the single template to breathe more before the continue gate.</p>',
	);

	return '<div class="ss-art-prose"><span class="ss-art-summary-tag">' . esc_html( $summary_tag ) . '</span>' . implode( '', array_slice( $paragraphs, 0, $paragraph_count ) ) . '</div>';
}

function storysentry_core_render_dummy_story_continue_gate( array $attributes = array() ): string {
	$eyebrow = isset( $attributes['eyebrowText'] ) && '' !== trim( (string) $attributes['eyebrowText'] ) ? sanitize_text_field( (string) $attributes['eyebrowText'] ) : 'Continue Reading';
	$title   = isset( $attributes['titleText'] ) && '' !== trim( (string) $attributes['titleText'] ) ? sanitize_text_field( (string) $attributes['titleText'] ) : 'The full story continues on ABC News.';
	$body    = isset( $attributes['bodyText'] ) && '' !== trim( (string) $attributes['bodyText'] ) ? sanitize_text_field( (string) $attributes['bodyText'] ) : 'This is a preview-only gate shown in the editor so you can tune spacing and hierarchy without waiting on live story context.';
	$cta     = isset( $attributes['ctaText'] ) && '' !== trim( (string) $attributes['ctaText'] ) ? sanitize_text_field( (string) $attributes['ctaText'] ) : 'Read on ABC News';

	return '<div class="ss-art-gate"><div class="ss-art-gate-fade" aria-hidden="true"></div><div class="ss-art-gate-card"><span class="ss-art-gate-eye">' . esc_html( $eyebrow ) . '</span><h3 class="ss-art-gate-ttl">' . esc_html( $title ) . '</h3><p class="ss-art-gate-sub">' . esc_html( $body ) . '</p><div class="ss-art-gate-row"><a class="ss-art-gate-cta" href="#">' . esc_html( $cta ) . ' <span>→</span></a></div></div></div>';
}

function storysentry_core_render_dummy_story_collection( array $attributes ): string {
	$mode         = isset( $attributes['mode'] ) ? sanitize_key( (string) $attributes['mode'] ) : 'source';
	$posts_to_show = isset( $attributes['postsToShow'] ) ? max( 1, (int) $attributes['postsToShow'] ) : 4;
	$kicker       = isset( $attributes['kicker'] ) ? sanitize_text_field( (string) $attributes['kicker'] ) : '';
	$label        = isset( $attributes['label'] ) ? sanitize_text_field( (string) $attributes['label'] ) : '';
	$items        = storysentry_core_get_dummy_archive_items( $posts_to_show );
	$html         = '<section class="ss-art-foot-sec">' . storysentry_core_render_section_rule(
		'' !== $kicker ? $kicker : ( 'category' === $mode ? 'Related' : 'The Source' ),
		'' !== $label ? $label : ( 'category' === $mode ? 'On this beat' : 'ABC NEWS' )
	);

	$html .= '<div class="ss-grid-4">';

	foreach ( $items as $item ) {
		$html .= '<article class="ss-card ss-card--med"><div class="ss-card-img ss-card-img--sm" style="background:linear-gradient(135deg,#dfe6f2 0%,#f3efe7 52%,#d9dde7 100%);"></div><div class="ss-card-body"><span class="ss-cat-eyebrow">' . esc_html( strtoupper( $item['category'] ) ) . '</span><h3 class="ss-h-med">' . esc_html( $item['title'] ) . '</h3><div class="ss-publine"><span class="ss-src">' . esc_html( $item['source'] ) . '</span><span class="ss-dot">·</span><span class="ss-ago">' . esc_html( $item['ago'] ) . '</span><span class="ss-dot">·</span><span class="ss-ago">' . esc_html( $item['read'] ) . '</span></div></div></article>';
	}

	$html .= '</div></section>';

	return $html;
}

function storysentry_core_render_dummy_archive_term_header(): string {
	return '<header class="ss-cat-head"><span class="ss-cat-eyebrow">SECTION</span><h1 class="ss-cat-title">Politics</h1><p class="ss-cat-sub">2,418+ stories aggregated from 186 sources in the last 24 hours.</p><div class="ss-cat-controls"><div class="ss-pills"><button class="ss-pill is-active">all</button><button class="ss-pill">breaking</button><button class="ss-pill">analysis</button><button class="ss-pill">opinion</button><button class="ss-pill">features</button></div><div class="ss-sort"><span>Sort</span><select><option>Latest</option><option>Most read</option><option>By source</option></select></div></div></header>';
}

function storysentry_core_render_dummy_archive_query_section( array $attributes ): string {
	$variant      = isset( $attributes['variant'] ) ? sanitize_key( (string) $attributes['variant'] ) : 'grid';
	$kicker       = isset( $attributes['kicker'] ) ? sanitize_text_field( (string) $attributes['kicker'] ) : '';
	$label        = isset( $attributes['label'] ) ? sanitize_text_field( (string) $attributes['label'] ) : '';
	$action_text  = isset( $attributes['actionText'] ) ? sanitize_text_field( (string) $attributes['actionText'] ) : '';
	$action_url   = isset( $attributes['actionUrl'] ) ? esc_url_raw( (string) $attributes['actionUrl'] ) : '';
	$show_image   = array_key_exists( 'showImage', $attributes ) ? (bool) $attributes['showImage'] : true;
	$show_excerpt = array_key_exists( 'showExcerpt', $attributes ) ? (bool) $attributes['showExcerpt'] : true;
	$posts_to_show = isset( $attributes['postsToShow'] ) ? max( 1, (int) $attributes['postsToShow'] ) : 5;
	$items        = storysentry_core_get_dummy_archive_items( max( 5, $posts_to_show ) );
	$section_rule = ( '' !== $kicker || '' !== $label || '' !== $action_text ) ? storysentry_core_render_section_rule( $kicker, $label, $action_text, $action_url ) : '';

	switch ( $variant ) {
		case 'lead':
			$item = $items[0];
			$html = '<article class="ss-card ss-card--lead"><div class="ss-card-img ss-card-img--lead" style="background:linear-gradient(135deg,#dfe6f2 0%,#f3efe7 52%,#d9dde7 100%);"></div><div class="ss-card-body"><span class="ss-cat-eyebrow">' . esc_html( strtoupper( $item['category'] ) ) . '</span><h2 class="ss-h-xl">' . esc_html( $item['title'] ) . '</h2>';
			if ( $show_excerpt ) {
				$html .= '<p class="ss-dek">' . esc_html( $item['excerpt'] ) . '</p>';
			}
			$html .= '<div class="ss-publine"><span class="ss-src">' . esc_html( $item['source'] ) . '</span><span class="ss-dot">·</span><span class="ss-ago">' . esc_html( $item['ago'] ) . '</span><span class="ss-dot">·</span><span class="ss-ago">' . esc_html( $item['read'] ) . '</span></div></div></article>';
			return $html;

		case 'numbered':
			$html = $section_rule . '<div class="ss-numlist">';
			foreach ( array_slice( $items, 0, min( 5, $posts_to_show ) ) as $index => $item ) {
				$html .= '<article class="ss-row ss-row--num"><span class="ss-row-num">' . esc_html( str_pad( (string) ( $index + 1 ), 2, '0', STR_PAD_LEFT ) ) . '</span><div class="ss-row-body"><h3 class="ss-row-ttl">' . esc_html( $item['title'] ) . '</h3><div class="ss-publine"><span class="ss-src">' . esc_html( $item['source'] ) . '</span><span class="ss-dot">·</span><span class="ss-ago">' . esc_html( $item['ago'] ) . '</span></div></div></article>';
			}
			$html .= '</div>';
			return $html;

		case 'list':
			$html = $section_rule . '<div class="ss-list">';
			foreach ( array_slice( $items, 0, min( 6, $posts_to_show ) ) as $item ) {
				$html .= '<article class="ss-row">' . ( $show_image ? '<div class="ss-row-thumb" style="background:linear-gradient(135deg,#dfe6f2 0%,#f3efe7 52%,#d9dde7 100%);"></div>' : '' ) . '<div class="ss-row-body"><span class="ss-cat-eyebrow">' . esc_html( strtoupper( $item['category'] ) ) . '</span><h3 class="ss-row-ttl">' . esc_html( $item['title'] ) . '</h3>';
				if ( $show_excerpt ) {
					$html .= '<p class="ss-row-dek">' . esc_html( $item['excerpt'] ) . '</p>';
				}
				$html .= '<div class="ss-publine"><span class="ss-src">' . esc_html( $item['source'] ) . '</span><span class="ss-dot">·</span><span class="ss-ago">' . esc_html( $item['ago'] ) . '</span><span class="ss-dot">·</span><span class="ss-ago">' . esc_html( $item['read'] ) . '</span></div></div></article>';
			}
			$html .= '</div>';
			return $html;

		case 'grid':
		default:
			$html = $section_rule . '<div class="ss-grid-4">';
			foreach ( array_slice( $items, 0, min( 4, $posts_to_show ) ) as $item ) {
				$html .= '<article class="ss-card ss-card--med">' . ( $show_image ? '<div class="ss-card-img ss-card-img--sm" style="background:linear-gradient(135deg,#dfe6f2 0%,#f3efe7 52%,#d9dde7 100%);"></div>' : '' ) . '<div class="ss-card-body"><span class="ss-cat-eyebrow">' . esc_html( strtoupper( $item['category'] ) ) . '</span><h3 class="ss-h-med">' . esc_html( $item['title'] ) . '</h3>';
				if ( $show_excerpt ) {
					$html .= '<p class="ss-dek ss-dek--sm">' . esc_html( $item['excerpt'] ) . '</p>';
				}
				$html .= '<div class="ss-publine"><span class="ss-src">' . esc_html( $item['source'] ) . '</span><span class="ss-dot">·</span><span class="ss-ago">' . esc_html( $item['ago'] ) . '</span><span class="ss-dot">·</span><span class="ss-ago">' . esc_html( $item['read'] ) . '</span></div></div></article>';
			}
			$html .= '</div>';
			return $html;
	}
}

function storysentry_core_render_story_card( array $attributes, string $content, WP_Block $block ): string {
	$post = storysentry_core_get_story_context( $block );

	if ( ! $post ) {
		return '';
	}

	return storysentry_core_render_story_card_from_post( $post, $attributes );
}

function storysentry_core_render_story_hero( array $attributes, string $content, WP_Block $block ): string {
	$post = storysentry_core_get_story_context( $block );

	if ( ! $post ) {
		return '';
	}

	$attributes['layout']      = 'hero';
	$attributes['showImage']   = true;
	$attributes['showExcerpt'] = true;

	return storysentry_core_render_story_card( $attributes, $content, $block );
}

function storysentry_core_render_story_meta_block( array $attributes, string $content, WP_Block $block ): string {
	$post = storysentry_core_get_story_context( $block );

	if ( ! $post ) {
		return '';
	}

	$source_domain = storysentry_core_get_source_domain( $post->ID );
	$source_link   = storysentry_core_get_source_domain_link( $post->ID );

	return sprintf(
		'<div class="ss-publine">%s<span class="ss-dot">·</span><span class="ss-ago">%s</span></div>',
		storysentry_core_render_source_badge( $source_domain, $source_link ),
		esc_html( storysentry_core_format_published_at( $post->ID ) )
	);
}

function storysentry_core_render_story_image_block( array $attributes, string $content, WP_Block $block ): string {
	if ( storysentry_core_is_editor_preview_request() ) {
		return storysentry_core_render_dummy_story_image( $attributes );
	}

	$post = storysentry_core_get_story_context( $block );

	if ( ! $post ) {
		return '';
	}

	$image_url = (string) get_post_meta( $post->ID, 'image_url', true );

	if ( '' === $image_url ) {
		return '';
	}

	$show_caption = ! array_key_exists( 'showCaption', $attributes ) || (bool) $attributes['showCaption'];
	$caption_text = isset( $attributes['captionText'] ) ? sanitize_text_field( (string) $attributes['captionText'] ) : '';
	$caption      = '' !== $caption_text ? $caption_text : 'Photograph via ' . storysentry_core_get_source_domain( $post->ID );
	$caption_html = $show_caption ? '<figcaption>' . esc_html( $caption ) . '</figcaption>' : '';

	return sprintf(
		'<figure class="ss-art-fig"><div class="ss-art-img" style="background-image:url(%1$s)"></div>%2$s</figure>',
		esc_url( $image_url ),
		$caption_html
	);
}

function storysentry_core_render_story_breadcrumbs( array $attributes, string $content, WP_Block $block ): string {
	if ( storysentry_core_is_editor_preview_request() ) {
		return storysentry_core_render_dummy_story_breadcrumbs( $attributes );
	}

	$post = storysentry_core_get_story_context( $block );

	if ( ! $post ) {
		return '';
	}

	$front_label   = isset( $attributes['frontLabel'] ) && '' !== trim( (string) $attributes['frontLabel'] ) ? sanitize_text_field( (string) $attributes['frontLabel'] ) : 'Front Page';
	$short         = isset( $attributes['currentLabel'] ) && '' !== trim( (string) $attributes['currentLabel'] ) ? sanitize_text_field( (string) $attributes['currentLabel'] ) : storysentry_core_get_source_short( $post->ID );
	$category = storysentry_core_get_category_term( $post->ID );
	$show_category = ! array_key_exists( 'showCategory', $attributes ) || (bool) $attributes['showCategory'];
	$category_label = isset( $attributes['categoryLabel'] ) && '' !== trim( (string) $attributes['categoryLabel'] ) ? sanitize_text_field( (string) $attributes['categoryLabel'] ) : ( $category ? $category->name : '' );
	$crumb    = ( $show_category && '' !== $category_label && $category ) ? '<a href="' . esc_url( get_term_link( $category ) ) . '">' . esc_html( $category_label ) . '</a><span>›</span>' : '';

	return '<div class="ss-art-crumbs"><a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html( $front_label ) . '</a><span>›</span>' . $crumb . '<span class="ss-art-crumb-cur">' . esc_html( $short ) . '</span></div>';
}

function storysentry_core_render_story_header_block( array $attributes, string $content, WP_Block $block ): string {
	if ( storysentry_core_is_editor_preview_request() ) {
		return storysentry_core_render_dummy_story_header( $attributes );
	}

	$post = storysentry_core_get_story_context( $block );

	if ( ! $post ) {
		return '';
	}

	$eyebrow     = isset( $attributes['eyebrowText'] ) && '' !== trim( (string) $attributes['eyebrowText'] ) ? '<span class="ss-cat-eyebrow">' . esc_html( strtoupper( sanitize_text_field( (string) $attributes['eyebrowText'] ) ) ) . '</span>' : storysentry_core_render_cat_eyebrow( $post->ID );
	$show_actions = ! array_key_exists( 'showActions', $attributes ) || (bool) $attributes['showActions'];
	$actions_html = $show_actions ? '<div class="ss-art-actions"><button class="ss-art-act">Save</button><button class="ss-art-act">Share</button><button class="ss-art-act">Listen</button></div>' : '';

	return '<header class="ss-art-head">' . $eyebrow . '<h1 class="ss-art-title">' . esc_html( get_the_title( $post ) ) . '</h1><div class="ss-art-meta">' . storysentry_core_render_publine( $post ) . $actions_html . '</div></header>';
}

function storysentry_core_render_story_prose_block( array $attributes, string $content, WP_Block $block ): string {
	if ( storysentry_core_is_editor_preview_request() ) {
		return storysentry_core_render_dummy_story_prose( $attributes );
	}

	$post = storysentry_core_get_story_context( $block );

	if ( ! $post ) {
		return '';
	}

	$summary = storysentry_core_get_excerpt( $post );
	$body    = wp_strip_all_tags( $post->post_content );
	$short   = storysentry_core_get_source_short( $post->ID );

	$paragraphs = array_filter(
		array(
			$summary,
			wp_trim_words( $body ?: $summary, 55, '…' ),
			wp_trim_words( $body ?: $summary, 85, '…' ),
		)
	);

	$summary_tag = isset( $attributes['summaryTag'] ) && '' !== trim( (string) $attributes['summaryTag'] ) ? sanitize_text_field( (string) $attributes['summaryTag'] ) : 'RSS SUMMARY · AGGREGATED FROM ' . $short;
	$paragraph_count = isset( $attributes['paragraphCount'] ) ? max( 1, min( 4, (int) $attributes['paragraphCount'] ) ) : count( $paragraphs );
	$prose = '<div class="ss-art-prose"><span class="ss-art-summary-tag">' . esc_html( $summary_tag ) . '</span>';

	foreach ( array_slice( array_values( $paragraphs ), 0, $paragraph_count ) as $index => $paragraph ) {
		if ( 0 === $index ) {
			$first_char = mb_substr( $paragraph, 0, 1 );
			$rest       = mb_substr( $paragraph, 1 );
			$prose     .= '<p class="ss-art-lede"><span class="ss-art-dropcap">' . esc_html( $first_char ) . '</span>' . esc_html( $rest ) . '</p>';
			continue;
		}

		$prose .= '<p>' . esc_html( $paragraph ) . '</p>';
	}

	$prose .= '</div>';

	return $prose;
}

function storysentry_core_render_story_continue_gate( array $attributes, string $content, WP_Block $block ): string {
	if ( storysentry_core_is_editor_preview_request() ) {
		return storysentry_core_render_dummy_story_continue_gate( $attributes );
	}

	$post = storysentry_core_get_story_context( $block );

	if ( ! $post ) {
		return '';
	}

	$source       = storysentry_core_get_source_domain( $post->ID );
	$interstitial = storysentry_core_get_interstitial_url( $post->ID );
	$eyebrow      = isset( $attributes['eyebrowText'] ) && '' !== trim( (string) $attributes['eyebrowText'] ) ? sanitize_text_field( (string) $attributes['eyebrowText'] ) : 'Continue Reading';
	$title_text   = isset( $attributes['titleText'] ) && '' !== trim( (string) $attributes['titleText'] ) ? sanitize_text_field( (string) $attributes['titleText'] ) : 'The full story continues on ' . $source . '.';
	$body_text    = isset( $attributes['bodyText'] ) && '' !== trim( (string) $attributes['bodyText'] ) ? sanitize_text_field( (string) $attributes['bodyText'] ) : 'Story Sentry shows a short summary aggregated via RSS. The complete article — original photography, charts, and reporting — lives with the publisher.';
	$cta_text     = isset( $attributes['ctaText'] ) && '' !== trim( (string) $attributes['ctaText'] ) ? sanitize_text_field( (string) $attributes['ctaText'] ) : 'Read on ' . $source;

	return '<div class="ss-art-gate"><div class="ss-art-gate-fade" aria-hidden="true"></div><div class="ss-art-gate-card"><span class="ss-art-gate-eye">' . esc_html( $eyebrow ) . '</span><h3 class="ss-art-gate-ttl">' . esc_html( $title_text ) . '</h3><p class="ss-art-gate-sub">' . esc_html( $body_text ) . '</p><div class="ss-art-gate-row"><a class="ss-art-gate-cta" href="' . esc_url( $interstitial ) . '">' . esc_html( $cta_text ) . ' <span>→</span></a></div></div></div>';
}

function storysentry_core_render_story_collection( array $attributes, string $content, WP_Block $block ): string {
	if ( storysentry_core_is_editor_preview_request() ) {
		return storysentry_core_render_dummy_story_collection( $attributes );
	}

	$post = storysentry_core_get_story_context( $block );

	if ( ! $post ) {
		return '';
	}

	$mode       = isset( $attributes['mode'] ) ? sanitize_key( (string) $attributes['mode'] ) : 'source';
	$kicker     = isset( $attributes['kicker'] ) ? sanitize_text_field( (string) $attributes['kicker'] ) : '';
	$label      = isset( $attributes['label'] ) ? sanitize_text_field( (string) $attributes['label'] ) : '';
	$posts_to_show = isset( $attributes['postsToShow'] ) ? max( 1, (int) $attributes['postsToShow'] ) : 4;
	$query_args = array(
		'post_type'      => 'ss_story',
		'posts_per_page' => $posts_to_show,
		'post__not_in'   => array( $post->ID ),
	);

	if ( 'category' === $mode ) {
		$category = storysentry_core_get_category_term( $post->ID );
		if ( ! $category ) {
			return '';
		}
		$query_args['category__in'] = array( $category->term_id );
		$kicker = '' !== $kicker ? $kicker : 'Related';
		$label  = '' !== $label ? $label : 'On this beat';
	} else {
		$source = storysentry_core_get_source_domain( $post->ID );
		if ( '' === $source ) {
			return '';
		}
		$query_args['tax_query'] = array(
			array(
				'taxonomy' => 'ss_source_domain',
				'field'    => 'name',
				'terms'    => array( $source ),
			),
		);
		$kicker = '' !== $kicker ? $kicker : 'The Source';
		$label  = '' !== $label ? $label : storysentry_core_get_source_short( $post->ID );
	}

	$posts = get_posts( $query_args );

	if ( empty( $posts ) ) {
		return '';
	}

	return '<section class="ss-art-foot-sec">' . storysentry_core_render_section_rule( $kicker, $label ) . '<div class="ss-grid-4">' . storysentry_core_render_posts_grid( $posts, 'med', true, false ) . '</div></section>';
}

function storysentry_core_get_archive_term( string $fallback_taxonomy = 'category' ): ?WP_Term {
	$term = get_queried_object();

	if ( $term instanceof WP_Term ) {
		return $term;
	}

	return null;
}

function storysentry_core_render_archive_term_header( array $attributes ): string {
	if ( storysentry_core_is_editor_preview_request() ) {
		return storysentry_core_render_dummy_archive_term_header();
	}

	$term = storysentry_core_get_archive_term();

	if ( ! $term ) {
		return '<div class="ss-empty-state">No archive context found.</div>';
	}

	if ( 'ss_source_domain' === $term->taxonomy ) {
		$query_args   = array(
			'posts_per_page' => 18,
			'tax_query'      => array(
				array(
					'taxonomy' => 'ss_source_domain',
					'field'    => 'term_id',
					'terms'    => array( $term->term_id ),
				),
			),
		);
		$posts        = storysentry_core_get_story_query( $query_args )->posts;
		$story_count  = storysentry_core_count_stories_for_query( $query_args );
		$daily_avg    = max( 1, (int) ceil( $story_count / 30 ) );
		$last_update  = ! empty( $posts ) ? storysentry_core_get_ago( $posts[0]->ID ) : 'now';

		return '<header class="ss-pub-head"><span class="ss-cat-eyebrow">SOURCE PROFILE</span><div class="ss-pub-mast"><h1 class="ss-pub-title">' . esc_html( $term->name ) . '</h1><div class="ss-pub-stats"><div><b>' . esc_html( number_format_i18n( $story_count ) ) . '</b><span>stories indexed</span></div><div><b>' . esc_html( (string) $daily_avg ) . '</b><span>per day, avg</span></div><div><b>' . esc_html( $last_update ) . '</b><span>last update</span></div></div></div><div class="ss-pub-meta"><span>RSS · /feed/all</span><span class="ss-dot">·</span><span>Quality score <b>A+</b></span><span class="ss-dot">·</span><span>Tracked since Jan 2026</span><button class="ss-pub-follow">＋ Follow source</button></div></header>';
	}

	$query_args   = array(
		'posts_per_page' => 18,
		'tax_query'      => array(
			array(
				'taxonomy' => 'category',
				'field'    => 'term_id',
				'terms'    => array( $term->term_id ),
			),
		),
	);
	$posts       = storysentry_core_get_story_query( $query_args )->posts;
	$story_count = storysentry_core_count_stories_for_query( $query_args );
	$source_count = max( 1, storysentry_core_get_unique_sources_for_posts( $posts ) );
	
	$sub_text = '';
	if ( ! empty( $term->description ) ) {
		$sub_text = wp_kses_post( $term->description );
	} else {
		$sub_text = esc_html( number_format_i18n( $story_count ) ) . '+ stories aggregated from ' . esc_html( (string) $source_count ) . ' sources in the last 24 hours.';
	}

	return '<header class="ss-cat-head"><span class="ss-cat-eyebrow">SECTION</span><h1 class="ss-cat-title">' . esc_html( $term->name ) . '</h1><p class="ss-cat-sub">' . $sub_text . '</p><div class="ss-cat-controls"><div class="ss-pills"><button class="ss-pill is-active">all</button><button class="ss-pill">breaking</button><button class="ss-pill">analysis</button><button class="ss-pill">opinion</button><button class="ss-pill">features</button></div><div class="ss-sort"><span>Sort</span><select><option>Latest</option><option>Most read</option><option>By source</option></select></div></div></header>';
}

function storysentry_core_render_archive_query_section( array $attributes ): string {
	if ( storysentry_core_is_editor_preview_request() ) {
		return storysentry_core_render_dummy_archive_query_section( $attributes );
	}

	$term = storysentry_core_get_archive_term();

	if ( ! $term ) {
		return '<div class="ss-empty-state">No archive context found.</div>';
	}

	$taxonomy_key = 'category' === $term->taxonomy ? 'categorySlug' : 'sourceSlug';
	$attributes[ $taxonomy_key ] = $term->slug;

	return storysentry_core_render_query_section( $attributes );
}

function storysentry_core_render_story_summary( array $attributes, string $content, WP_Block $block ): string {
	$post = storysentry_core_get_story_context( $block );

	if ( ! $post ) {
		return '';
	}

	$summary      = storysentry_core_get_excerpt( $post );
	$body         = wp_strip_all_tags( $post->post_content );
	$source       = storysentry_core_get_source_domain( $post->ID );
	$short        = storysentry_core_get_source_short( $post->ID );
	$category     = storysentry_core_get_category_term( $post->ID );
	$interstitial = storysentry_core_get_interstitial_url( $post->ID );
	$image_url    = (string) get_post_meta( $post->ID, 'image_url', true );
	$related_cat  = $category ? get_posts(
		array(
			'post_type'      => 'ss_story',
			'posts_per_page' => 4,
			'post__not_in'   => array( $post->ID ),
			'category__in'   => array( $category->term_id ),
		)
	) : array();
	$related_src  = get_posts(
		array(
			'post_type'      => 'ss_story',
			'posts_per_page' => 4,
			'post__not_in'   => array( $post->ID ),
			'tax_query'      => array(
				array(
					'taxonomy' => 'ss_source_domain',
					'field'    => 'name',
					'terms'    => array( $source ),
				),
			),
		)
	);
	$crumb_cat    = $category ? '<a href="' . esc_url( get_term_link( $category ) ) . '">' . esc_html( $category->name ) . '</a><span>›</span>' : '';
	$image_markup = $image_url ? '<figure class="ss-art-fig"><div class="ss-art-img" style="background-image:url(' . esc_url( $image_url ) . ')"></div><figcaption>Photograph via ' . esc_html( $source ) . '</figcaption></figure>' : '';

	$paragraphs = array_filter(
		array(
			$summary,
			wp_trim_words( $body ?: $summary, 55, '…' ),
			wp_trim_words( $body ?: $summary, 85, '…' ),
		)
	);

	$prose = '';

	foreach ( $paragraphs as $index => $paragraph ) {
		if ( 0 === $index ) {
			$first_char = mb_substr( $paragraph, 0, 1 );
			$rest       = mb_substr( $paragraph, 1 );
			$prose     .= '<p class="ss-art-lede"><span class="ss-art-dropcap">' . esc_html( $first_char ) . '</span>' . esc_html( $rest ) . '</p>';
			continue;
		}

		$prose .= '<p>' . esc_html( $paragraph ) . '</p>';
	}

	$source_cards = '';
	foreach ( $related_src as $related_post ) {
		$source_cards .= storysentry_core_render_story_card_from_post( $related_post, array( 'layout' => 'med' ) );
	}

	$related_cards = '';
	foreach ( $related_cat as $related_post ) {
		$related_cards .= storysentry_core_render_story_card_from_post( $related_post, array( 'layout' => 'med' ) );
	}

	return '<div class="ss-art"><div class="ss-art-crumbs"><a href="/">Front Page</a><span>›</span>' . $crumb_cat . '<span class="ss-art-crumb-cur">' . esc_html( $short ) . '</span></div><article class="ss-art-body"><header class="ss-art-head">' . storysentry_core_render_cat_eyebrow( $post->ID ) . '<h1 class="ss-art-title">' . esc_html( get_the_title( $post ) ) . '</h1><div class="ss-art-meta">' . storysentry_core_render_publine( $post ) . '<div class="ss-art-actions"><button class="ss-art-act">Save</button><button class="ss-art-act">Share</button><button class="ss-art-act">Listen</button></div></div></header>' . $image_markup . '<div class="ss-art-prose"><span class="ss-art-summary-tag">RSS SUMMARY · AGGREGATED FROM ' . esc_html( $short ) . '</span>' . $prose . '</div>' . storysentry_core_render_ad_slot( array( 'slot' => 'article-mid' ) ) . '<div class="ss-art-gate"><div class="ss-art-gate-fade" aria-hidden="true"></div><div class="ss-art-gate-card"><span class="ss-art-gate-eye">Continue Reading</span><h3 class="ss-art-gate-ttl">The full story continues on <em>' . esc_html( $source ) . '</em>.</h3><p class="ss-art-gate-sub">Story Sentry shows a short summary aggregated via RSS. The complete article — original photography, charts, and reporting — lives with the publisher.</p><div class="ss-art-gate-row"><a class="ss-art-gate-cta" href="' . esc_url( $interstitial ) . '">Read on ' . esc_html( $source ) . ' <span>→</span></a></div></div></div><div class="ss-art-foot"><section class="ss-art-foot-sec"><div class="ss-sect-rule"><div class="ss-sect-rule-l"><span class="ss-sect-kicker">The Source</span><h3 class="ss-sect-label">' . esc_html( $short ) . '</h3></div></div><div class="ss-grid-4">' . $source_cards . '</div></section>' . storysentry_core_render_ad_slot( array( 'slot' => 'article-after' ) ) . '<section class="ss-art-foot-sec"><div class="ss-sect-rule"><div class="ss-sect-rule-l"><span class="ss-sect-kicker">Related</span><h3 class="ss-sect-label">On this beat</h3></div></div><div class="ss-grid-4">' . $related_cards . '</div></section></div></article></div>';
}

function storysentry_core_render_section_rule( string $kicker, string $label, string $action = '', string $action_url = '' ): string {
	$html = '<div class="ss-sect-rule"><div class="ss-sect-rule-l">';

	if ( '' !== $kicker ) {
		$html .= '<span class="ss-sect-kicker">' . esc_html( $kicker ) . '</span>';
	}

	$html .= '<h3 class="ss-sect-label">' . esc_html( $label ) . '</h3></div>';

	if ( '' !== $action && '' !== $action_url ) {
		$html .= '<div class="ss-sect-rule-r"><a class="ss-sect-action" href="' . esc_url( $action_url ) . '">' . esc_html( $action ) . ' →</a></div>';
	}

	$html .= '</div>';

	return $html;
}

function storysentry_core_get_story_query( array $args ): WP_Query {
	$defaults = array(
		'post_type'           => 'ss_story',
		'post_status'         => 'publish',
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	);

	return new WP_Query( wp_parse_args( $args, $defaults ) );
}

function storysentry_core_count_stories_for_query( array $args ): int {
	$query = new WP_Query(
		wp_parse_args(
			$args,
			array(
				'post_type'           => 'ss_story',
				'post_status'         => 'publish',
				'ignore_sticky_posts' => true,
				'posts_per_page'      => 1,
				'no_found_rows'       => false,
				'fields'              => 'ids',
			)
		)
	);

	return (int) $query->found_posts;
}

function storysentry_core_get_unique_sources_for_posts( array $posts ): int {
	$terms = array();

	foreach ( $posts as $post ) {
		$post_terms = get_the_terms( $post->ID, 'ss_source_domain' );

		if ( empty( $post_terms ) || is_wp_error( $post_terms ) ) {
			continue;
		}

		foreach ( $post_terms as $term ) {
			$terms[ $term->term_id ] = true;
		}
	}

	return count( $terms );
}

function storysentry_core_render_posts_grid( array $posts, string $layout = 'med', bool $show_image = true, bool $show_excerpt = true ): string {
	$html = '';
	$link_target = isset( $GLOBALS['storysentry_core_section_link_target'] ) ? (string) $GLOBALS['storysentry_core_section_link_target'] : '';

	foreach ( $posts as $post ) {
		if ( ! $post instanceof WP_Post ) {
			continue;
		}

		$html .= storysentry_core_render_story_card_from_post(
			$post,
			array(
				'layout'      => $layout,
				'showImage'   => $show_image,
				'showExcerpt' => $show_excerpt,
				'linkTarget'  => $link_target,
			)
		);
	}

	return $html;
}

function storysentry_core_render_posts_rows( array $posts, bool $with_image = false, bool $numbered = false, string $class = 'ss-list' ): string {
	$html = '<div class="' . esc_attr( $class ) . '">';
	$link_target = isset( $GLOBALS['storysentry_core_section_link_target'] ) ? (string) $GLOBALS['storysentry_core_section_link_target'] : '';

	foreach ( $posts as $post ) {
		if ( ! $post instanceof WP_Post ) {
			continue;
		}

		$html .= storysentry_core_render_story_card_from_post(
			$post,
			array(
				'layout'    => 'row',
				'showImage' => $with_image,
				'numbered'  => $numbered,
				'linkTarget'=> $link_target,
			)
		);
	}

	$html .= '</div>';

	return $html;
}

function storysentry_core_render_search_refine_list( array $label_counts ): string {
	$html = '<ul class="ss-refine">';

	foreach ( $label_counts as $label => $count ) {
		$html .= '<li><label><input type="checkbox" /> ' . esc_html( $label ) . '</label><span>' . esc_html( (string) $count ) . '</span></li>';
	}

	$html .= '</ul>';

	return $html;
}

function storysentry_core_render_site_header_block( array $attributes, string $content, WP_Block $block ): string {
	$show_menu_icon = ! array_key_exists( 'showMenuIcon', $attributes ) || (bool) $attributes['showMenuIcon'];
	$show_saved     = ! array_key_exists( 'showSaved', $attributes ) || (bool) $attributes['showSaved'];
	$show_sign_in   = ! array_key_exists( 'showSignIn', $attributes ) || (bool) $attributes['showSignIn'];
	$logo_url       = isset( $attributes['logoUrl'] ) ? esc_url_raw( (string) $attributes['logoUrl'] ) : '';

	$menu_icon_html = $show_menu_icon ? '<button class="ss-hdr-icon" aria-label="Menu"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 7h16M4 12h16M4 17h16"></path></svg></button>' : '';
	$saved_html     = $show_saved ? '<a class="ss-hdr-link" href="#">Saved</a>' : '';
	$sign_in_html   = $show_sign_in ? '<a class="ss-hdr-cta" href="#">Sign in</a>' : '';

	if ( '' !== $logo_url ) {
		$logo_html = '<img src="' . esc_url( $logo_url ) . '" alt="' . esc_attr( get_bloginfo( 'name' ) ) . '" style="max-height: 36px; width: auto;" />';
	} else {
		$logo_html = '<span class="ss-wordmark" style="font-size:36px"><span class="ss-wordmark__glyph" aria-hidden="true">◆</span><span class="ss-wordmark__name">Story Sentry</span></span>';
	}

	$nav_html = '';
	if ( has_nav_menu( 'header_menu' ) ) {
		$locations = get_nav_menu_locations();
		$menu      = wp_get_nav_menu_object( $locations['header_menu'] );
		$items     = $menu ? wp_get_nav_menu_items( $menu->term_id ) : false;

		if ( $items ) {
			$nav_html = '<nav class="ss-hdr-nav"><div class="ss-hdr-nav-inner">';
			foreach ( $items as $item ) {
				$classes = empty( $item->classes ) ? array() : (array) $item->classes;
				$class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item ) );
				$class_names = $class_names ? 'ss-nav-item ' . esc_attr( $class_names ) : 'ss-nav-item';
				
				// Handle a custom separator class if they want to add the vertical line via a WP menu custom class like `ss-nav-sep`
				if ( in_array( 'ss-nav-sep', $classes, true ) ) {
					$nav_html .= '<span class="ss-nav-sep"></span>';
					continue;
				}

				$nav_html .= '<a class="' . $class_names . '" href="' . esc_url( $item->url ) . '">' . esc_html( $item->title ) . '</a>';
			}
			$nav_html .= '</div></nav>';
		}
	}

	if ( '' === $nav_html ) {
		$nav_html = '<nav class="ss-hdr-nav"><div class="ss-hdr-nav-inner"><a class="ss-nav-item is-active" href="' . esc_url( home_url( '/' ) ) . '">Front Page</a><a class="ss-nav-item" href="' . esc_url( home_url( '/category/us/' ) ) . '">US</a><a class="ss-nav-item" href="' . esc_url( home_url( '/category/politics/' ) ) . '">Politics</a><a class="ss-nav-item" href="' . esc_url( home_url( '/category/news/' ) ) . '">World</a><a class="ss-nav-item" href="' . esc_url( home_url( '/category/tech/' ) ) . '">Technology</a><a class="ss-nav-item" href="' . esc_url( home_url( '/category/innovation/' ) ) . '">Innovation</a><a class="ss-nav-item" href="' . esc_url( home_url( '/category/science/' ) ) . '">Science</a><a class="ss-nav-item" href="' . esc_url( home_url( '/category/sports/' ) ) . '">Sports</a><a class="ss-nav-item" href="' . esc_url( home_url( '/category/luxury/' ) ) . '">Luxury</a><a class="ss-nav-item" href="' . esc_url( home_url( '/category/lifestyle/' ) ) . '">Lifestyle</a><a class="ss-nav-item" href="' . esc_url( home_url( '/category/fashion/' ) ) . '">Fashion</a><span class="ss-nav-sep"></span><a class="ss-nav-item ss-nav-muted" href="#">Newsletters</a><a class="ss-nav-item ss-nav-muted" href="' . esc_url( home_url( '/source/' ) ) . '">Sources</a></div></nav>';
	}

	return '<header class="ss-hdr"><div class="ss-hdr-strip"><span>' . esc_html( wp_date( 'l, F j, Y' ) ) . '</span><span class="ss-hdr-strip-mid">Aggregating <b>2,418</b> sources · Updated 38 seconds ago</span><span>NYC 54° · LON 47° · TOK 61°</span></div><div class="ss-hdr-mast">' . $menu_icon_html . '<a class="ss-hdr-mark" href="' . esc_url( home_url( '/' ) ) . '">' . $logo_html . '</a><div class="ss-hdr-actions"><a class="ss-hdr-link" href="' . esc_url( home_url( '/?s=&post_type=ss_story' ) ) . '"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>Search</a>' . $saved_html . $sign_in_html . '</div></div>' . $nav_html . '</header>';
}

function storysentry_core_render_site_footer_block( array $attributes = array() ): string {
	$tagline = isset( $attributes['tagline'] ) ? wp_kses_post( $attributes['tagline'] ) : 'An aggregator of record. Pulling from 2,418 publishers, refreshed every minute.';
	$copyright = isset( $attributes['copyright'] ) ? wp_kses_post( $attributes['copyright'] ) : '© 2026 Story Sentry, Inc. · A WordPress publication.';
	$sub_title = isset( $attributes['subscribeTitle'] ) ? esc_html( $attributes['subscribeTitle'] ) : 'Subscribe';
	$sub_text  = isset( $attributes['subscribeText'] ) ? wp_kses_post( $attributes['subscribeText'] ) : 'A morning brief, hand-curated.';
	$col1_title = isset( $attributes['col1Title'] ) ? esc_html( $attributes['col1Title'] ) : 'Sections';
	$col2_title = isset( $attributes['col2Title'] ) ? esc_html( $attributes['col2Title'] ) : 'Sources';
	$col3_title = isset( $attributes['col3Title'] ) ? esc_html( $attributes['col3Title'] ) : 'Story Sentry';

	$logo_html = '';
	if ( ! empty( $attributes['logoUrl'] ) ) {
		$logo_html = '<img src="' . esc_url( $attributes['logoUrl'] ) . '" alt="Logo" style="height:28px; width:auto; display:block; margin-bottom: 12px;" />';
	} else {
		$logo_html = '<span class="ss-wordmark" style="font-size:28px"><span class="ss-wordmark__glyph" aria-hidden="true">◆</span><span class="ss-wordmark__name">Story Sentry</span></span>';
	}

	$nav_args = array( 'container' => false, 'echo' => false, 'fallback_cb' => false );
	
	$col1 = wp_nav_menu( array_merge( $nav_args, array( 'theme_location' => 'footer_menu_1' ) ) ) ?: '<ul><li><a href="' . admin_url('nav-menus.php') . '">Setup Footer 1 Menu</a></li></ul>';
	$col2 = wp_nav_menu( array_merge( $nav_args, array( 'theme_location' => 'footer_menu_2' ) ) ) ?: '<ul><li><a href="' . admin_url('nav-menus.php') . '">Setup Footer 2 Menu</a></li></ul>';
	$col3 = wp_nav_menu( array_merge( $nav_args, array( 'theme_location' => 'footer_menu_3' ) ) ) ?: '<ul><li><a href="' . admin_url('nav-menus.php') . '">Setup Footer 3 Menu</a></li></ul>';
	
	$col4 = wp_nav_menu( array_merge( $nav_args, array( 'theme_location' => 'footer_menu_4', 'items_wrap' => '<span id="%1$s" class="%2$s">%3$s</span>', 'depth' => 1 ) ) );
	if ( $col4 ) {
		$col4 = strip_tags( $col4, '<a><span>' );
		$col4 = preg_replace('/<\/a>\s*<a/', '</a> &middot; <a', $col4);
	} else {
		$col4 = '<span><a href="#">Terms</a> &middot; <a href="#">Privacy</a> &middot; <a href="#">Cookies</a> &middot; <a href="#">Editorial standards</a></span>';
	}

	return '<footer class="ss-ftr"><div class="ss-ftr-top"><div>' . $logo_html . '<p class="ss-ftr-tag">' . $tagline . '</p></div><div class="ss-ftr-cols"><div><h5>' . $col1_title . '</h5>' . $col1 . '</div><div><h5>' . $col2_title . '</h5>' . $col2 . '</div><div><h5>' . $col3_title . '</h5>' . $col3 . '</div><div><h5>' . $sub_title . '</h5><p class="ss-ftr-sub">' . $sub_text . '</p><div class="ss-ftr-form"><input placeholder="you@domain.com" /><button>Join</button></div></div></div></div><div class="ss-ftr-bot"><span>' . $copyright . '</span>' . $col4 . '</div></footer>';
}

function storysentry_core_render_newsletter_box(): string {
	return '<div class="ss-newsletter"><h4>The Morning Sentry</h4><p>A 5-minute brief from across the wire. Weekday mornings.</p><div class="ss-nl-form"><input placeholder="you@domain.com" /><button>Subscribe</button></div></div>';
}

function storysentry_core_build_query_args_from_attributes( array $attributes ): array {
	$posts_to_show = isset( $attributes['postsToShow'] ) ? max( 1, (int) $attributes['postsToShow'] ) : 5;
	$offset        = isset( $attributes['offset'] ) ? max( 0, (int) $attributes['offset'] ) : 0;
	$args          = array(
		'posts_per_page' => $posts_to_show,
		'offset'         => $offset,
	);

	$category_slug = isset( $attributes['categorySlug'] ) ? sanitize_title( (string) $attributes['categorySlug'] ) : '';
	$source_slug   = isset( $attributes['sourceSlug'] ) ? sanitize_title( (string) $attributes['sourceSlug'] ) : '';
	$tax_query     = array();

	if ( '' !== $category_slug ) {
		$tax_query[] = array(
			'taxonomy' => 'category',
			'field'    => 'slug',
			'terms'    => array( $category_slug ),
		);
	}

	if ( '' !== $source_slug ) {
		$tax_query[] = array(
			'taxonomy' => 'ss_source_domain',
			'field'    => 'slug',
			'terms'    => array( $source_slug ),
		);
	}

	if ( ! empty( $tax_query ) ) {
		$args['tax_query'] = $tax_query;
	}

	return $args;
}

function storysentry_core_render_query_section( array $attributes ): string {
	$variant      = isset( $attributes['variant'] ) ? sanitize_key( (string) $attributes['variant'] ) : 'grid';
	$kicker       = isset( $attributes['kicker'] ) ? sanitize_text_field( (string) $attributes['kicker'] ) : '';
	$label        = isset( $attributes['label'] ) ? sanitize_text_field( (string) $attributes['label'] ) : '';
	$action_text  = isset( $attributes['actionText'] ) ? sanitize_text_field( (string) $attributes['actionText'] ) : '';
	$action_url   = isset( $attributes['actionUrl'] ) ? esc_url_raw( (string) $attributes['actionUrl'] ) : '';
	$link_target  = isset( $attributes['linkTarget'] ) ? sanitize_key( (string) $attributes['linkTarget'] ) : '';
	$show_image   = array_key_exists( 'showImage', $attributes ) ? (bool) $attributes['showImage'] : true;
	$show_excerpt = array_key_exists( 'showExcerpt', $attributes ) ? (bool) $attributes['showExcerpt'] : true;
	$args         = storysentry_core_build_query_args_from_attributes( $attributes );
	$posts        = storysentry_core_get_story_query( $args )->posts;

	if ( empty( $posts ) ) {
		return '<div class="ss-empty-state">No matching stories yet.</div>';
	}

	$GLOBALS['storysentry_core_section_link_target'] = $link_target;

	$section_rule = ( '' !== $kicker || '' !== $label || '' !== $action_text ) ? storysentry_core_render_section_rule( $kicker, $label, $action_text, $action_url ) : '';

	switch ( $variant ) {
		case 'ticker':
			$html = '<div class="ss-ticker"><span class="ss-ticker-tag"><span class="ss-ticker-dot"></span>LIVE</span><div class="ss-ticker-track">' . storysentry_core_render_posts_grid( array_merge( $posts, $posts ), 'ticker', false, false ) . '</div></div>';
			unset( $GLOBALS['storysentry_core_section_link_target'] );
			return $html;

		case 'lead':
			$html = storysentry_core_render_story_card_from_post(
				$posts[0],
				array(
					'layout'      => 'lead',
					'showImage'   => $show_image,
					'showExcerpt' => $show_excerpt,
					'linkTarget'  => $link_target,
				)
			);
			unset( $GLOBALS['storysentry_core_section_link_target'] );
			return $html;

		case 'numbered':
			$html = $section_rule . storysentry_core_render_posts_rows( $posts, false, true, 'ss-numlist' );
			unset( $GLOBALS['storysentry_core_section_link_target'] );
			return $html;

		case 'list':
			$html = $section_rule . storysentry_core_render_posts_rows( $posts, $show_image, false, 'ss-list' );
			unset( $GLOBALS['storysentry_core_section_link_target'] );
			return $html;

		case 'opinion':
			$html = $section_rule . '<div class="ss-op-list">';
			foreach ( $posts as $post ) {
				$html .= storysentry_core_render_opinion_item( $post );
			}
			$html .= '</div>';
			unset( $GLOBALS['storysentry_core_section_link_target'] );
			return $html;

		case 'most-read':
			$html = $section_rule . '<ol class="ss-mr">';
			foreach ( $posts as $index => $post ) {
				$html .= '<li><span class="ss-mr-n">' . esc_html( str_pad( (string) ( $index + 1 ), 2, '0', STR_PAD_LEFT ) ) . '</span><div><h4>' . esc_html( get_the_title( $post ) ) . '</h4>' . storysentry_core_render_publine( $post, false ) . '</div></li>';
			}
			$html .= '</ol>';
			unset( $GLOBALS['storysentry_core_section_link_target'] );
			return $html;

		case 'grid':
		default:
			$html = $section_rule . '<div class="ss-grid-4">' . storysentry_core_render_posts_grid( $posts, 'med', $show_image, false ) . '</div>';
			unset( $GLOBALS['storysentry_core_section_link_target'] );
			return $html;
	}
}

function storysentry_core_render_opinion_item( WP_Post $post ): string {
	$quote = esc_html( storysentry_core_get_excerpt( $post ) );

	return '<article class="ss-op"><div class="ss-op-quote">“</div><h4 class="ss-op-ttl">' . $quote . '</h4>' . storysentry_core_render_publine( $post, false ) . '</article>';
}

// --- Related Categories Term Meta & UI ---

function storysentry_core_register_term_meta() {
	register_term_meta( 'category', 'ss_related_categories', array(
		'type'         => 'string',
		'description'  => 'Comma-separated list of related category IDs.',
		'single'       => true,
		'show_in_rest' => true,
	) );
}
add_action( 'init', 'storysentry_core_register_term_meta' );

function storysentry_core_enqueue_admin_scripts( $hook ) {
	if ( 'term.php' === $hook || 'edit-tags.php' === $hook ) {
		wp_enqueue_script( 'jquery-ui-sortable' );
	}
}
add_action( 'admin_enqueue_scripts', 'storysentry_core_enqueue_admin_scripts' );

function storysentry_core_category_edit_form_fields( $term ) {
	$related = get_term_meta( $term->term_id, 'ss_related_categories', true );
	$related_array = $related ? explode( ',', $related ) : array();
	
	$categories = get_categories( array( 'hide_empty' => false, 'exclude' => array( $term->term_id ) ) );
	?>
	<tr class="form-field">
		<th scope="row"><label for="ss_related_categories"><?php _e( 'İlişkili Kategoriler (Related Categories)', 'storysentry-core' ); ?></label></th>
		<td>
			<div id="ss-related-cat-list" style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #fff; border-radius: 4px;">
			<?php 
			// Separate selected and unselected so selected appear at top
			$selected_cats = array();
			$unselected_cats = array();
			foreach ( $categories as $cat ) {
				if ( in_array( $cat->term_id, $related_array ) ) {
					// Maintain the saved order!
					$selected_cats[ array_search( $cat->term_id, $related_array ) ] = $cat;
				} else {
					$unselected_cats[] = $cat;
				}
			}
			ksort( $selected_cats );
			$sorted_categories = array_merge( $selected_cats, $unselected_cats );
			
			foreach ( $sorted_categories as $cat ) : ?>
				<label style="display: block; margin-bottom: 5px; padding: 5px; background: #f9f9f9; border: 1px solid #eee; cursor: move;">
					<span style="color: #999; margin-right: 5px;">&#9776;</span>
					<input type="checkbox" name="ss_related_categories[]" value="<?php echo esc_attr( $cat->term_id ); ?>" <?php checked( in_array( $cat->term_id, $related_array ) ); ?> />
					<?php echo esc_html( $cat->name ); ?>
				</label>
			<?php endforeach; ?>
			</div>
			<p class="description"><?php _e( 'Bu kategori sayfasının alt kısmında gösterilmek üzere ilişkili diğer kategorileri seçin. Sıralamayı değiştirmek için kutucukları <strong>sürükleyip bırakabilirsiniz</strong> (Drag & Drop).', 'storysentry-core' ); ?></p>
			<script>
				jQuery(document).ready(function($) {
					if (typeof $.fn.sortable !== 'undefined') {
						$('#ss-related-cat-list').sortable({
							cursor: 'move',
							containment: 'parent',
							update: function(event, ui) {
								// Triggered when order changes
							}
						});
					}
				});
			</script>
		</td>
	</tr>
	<?php
}
add_action( 'category_edit_form_fields', 'storysentry_core_category_edit_form_fields' );

function storysentry_core_category_add_form_fields() {
	$categories = get_categories( array( 'hide_empty' => false ) );
	?>
	<div class="form-field">
		<label for="ss_related_categories"><?php _e( 'İlişkili Kategoriler (Related Categories)', 'storysentry-core' ); ?></label>
		<div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #fff; border-radius: 4px;">
		<?php foreach ( $categories as $cat ) : ?>
			<label style="display: block; margin-bottom: 5px;">
				<input type="checkbox" name="ss_related_categories[]" value="<?php echo esc_attr( $cat->term_id ); ?>" />
				<?php echo esc_html( $cat->name ); ?>
			</label>
		<?php endforeach; ?>
		</div>
		<p class="description"><?php _e( 'Bu kategori sayfasının alt kısmında gösterilmek üzere ilişkili diğer kategorileri seçin.', 'storysentry-core' ); ?></p>
	</div>
	<?php
}
add_action( 'category_add_form_fields', 'storysentry_core_category_add_form_fields' );

function storysentry_core_save_category_fields( $term_id ) {
	if ( isset( $_POST['ss_related_categories'] ) && is_array( $_POST['ss_related_categories'] ) ) {
		$related = array_map( 'intval', $_POST['ss_related_categories'] );
		update_term_meta( $term_id, 'ss_related_categories', implode( ',', $related ) );
	} else {
		delete_term_meta( $term_id, 'ss_related_categories' );
	}
}
add_action( 'edited_category', 'storysentry_core_save_category_fields' );
add_action( 'create_category', 'storysentry_core_save_category_fields' );

// --- Block Render Functions ---

function storysentry_core_render_archive_related_categories( array $attributes ): string {
	if ( storysentry_core_is_editor_preview_request() ) {
		return '<div class="wp-block-group ss-band" style="padding:20px; border:1px dashed #ccc; text-align:center;"><strong>' . __( 'Preview: Related Categories Sections', 'storysentry-core' ) . '</strong><br><em>(Will display sections for categories selected in the category edit screen)</em></div>';
	}

	$term = storysentry_core_get_archive_term();
	if ( ! $term || 'category' !== $term->taxonomy ) {
		return ''; // Only valid on category archives
	}

	$related = get_term_meta( $term->term_id, 'ss_related_categories', true );
	if ( ! $related ) {
		return '';
	}

	$related_ids = explode( ',', $related );
	$html = '';

	foreach ( $related_ids as $cat_id ) {
		$cat = get_term( $cat_id, 'category' );
		if ( ! $cat || is_wp_error( $cat ) ) {
			continue;
		}

		$section_attrs = array(
			'variant'      => isset( $attributes['variant'] ) ? $attributes['variant'] : 'list',
			'kicker'       => __( 'More in', 'storysentry-core' ),
			'label'        => $cat->name,
			'categorySlug' => $cat->slug,
			'postsToShow'  => isset( $attributes['postsToShow'] ) ? $attributes['postsToShow'] : 5,
			'showImage'    => isset( $attributes['showImage'] ) ? $attributes['showImage'] : true,
			'showExcerpt'  => false,
		);

		$html .= '<div class="wp-block-group ss-band">';
		$html .= storysentry_core_render_query_section( $section_attrs );
		$html .= '</div>';
	}

	return $html;
}

function storysentry_core_render_front_page_layout(): string {
	$posts = storysentry_core_get_story_query(
		array(
			'posts_per_page' => 40,
		)
	)->posts;

	if ( empty( $posts ) ) {
		return '<main class="ss-main"><div class="ss-home"><p class="ss-empty-state">No stories have landed yet.</p></div></main>';
	}

	$hero      = $posts[0];
	$breaking  = array_slice( $posts, 0, 5 );
	$brief     = array_slice( $posts, 1, 5 );
	$grid      = array_slice( $posts, 6, 8 );
	$latest    = array_slice( $posts, 14, 12 );
	$opinion   = array_slice( $posts, 26, 3 );
	$most_read = array_slice( $posts, 1, 5 );
	$beat_slugs = array( 'tech', 'politics', 'luxury', 'sports' );
	$beat_cols = '';

	foreach ( $beat_slugs as $slug ) {
		$term = get_term_by( 'slug', $slug, 'category' );

		if ( ! $term instanceof WP_Term ) {
			continue;
		}

		$beat_posts = storysentry_core_get_story_query(
			array(
				'posts_per_page' => 4,
				'tax_query'      => array(
					array(
						'taxonomy' => 'category',
						'field'    => 'term_id',
						'terms'    => array( $term->term_id ),
					),
				),
			)
		)->posts;

		if ( empty( $beat_posts ) ) {
			continue;
		}

		$beat_cols .= '<div class="ss-beat-col"><a class="ss-beat-h" href="' . esc_url( get_term_link( $term ) ) . '">' . esc_html( $term->name ) . ' <span>→</span></a><div class="ss-beat-list">';

		foreach ( $beat_posts as $index => $beat_post ) {
			$beat_cols .= storysentry_core_render_story_card_from_post(
				$beat_post,
				array(
					'layout'    => 'row',
					'showImage' => 0 === $index,
				)
			);
		}

		$beat_cols .= '</div></div>';
	}

	$opinion_markup = '';
	foreach ( $opinion as $post ) {
		$opinion_markup .= storysentry_core_render_opinion_item( $post );
	}

	$most_read_markup = '<ol class="ss-mr">';
	foreach ( $most_read as $index => $post ) {
		$most_read_markup .= '<li><span class="ss-mr-n">' . esc_html( str_pad( (string) ( $index + 1 ), 2, '0', STR_PAD_LEFT ) ) . '</span><div><h4>' . esc_html( get_the_title( $post ) ) . '</h4>' . storysentry_core_render_publine( $post, false ) . '</div></li>';
	}
	$most_read_markup .= '</ol>';

	return '<main class="ss-main"><div class="ss-home"><div class="ss-ticker"><span class="ss-ticker-tag"><span class="ss-ticker-dot"></span>LIVE</span><div class="ss-ticker-track">' . storysentry_core_render_posts_grid( array_merge( $breaking, $breaking ), 'ticker', false, false ) . '</div></div><section class="ss-hero"><div class="ss-hero-lead">' . storysentry_core_render_story_card_from_post( $hero, array( 'layout' => 'lead' ) ) . '</div><aside class="ss-hero-side">' . storysentry_core_render_section_rule( 'The Brief', 'Top stories now' ) . storysentry_core_render_posts_rows( $brief, false, true, 'ss-numlist' ) . '</aside></section><section class="ss-band">' . storysentry_core_render_section_rule( 'Editors’ Desk', 'Top stories', 'See all', home_url( '/stories/' ) ) . '<div class="ss-grid-4">' . storysentry_core_render_posts_grid( $grid, 'med', true, false ) . '</div></section><section class="ss-band ss-band--split"><div class="ss-split-main">' . storysentry_core_render_section_rule( 'The Wire', 'Latest from 2,418 sources' ) . storysentry_core_render_posts_rows( $latest, true ) . '<a class="ss-loadmore ss-hit" href="' . esc_url( home_url( '/stories/' ) ) . '">Load more from the wire →</a></div><aside class="ss-split-side">' . storysentry_core_render_section_rule( 'Opinion', 'Voices' ) . '<div class="ss-op-list">' . $opinion_markup . '</div>' . storysentry_core_render_section_rule( 'Most Read', 'Today' ) . $most_read_markup . storysentry_core_render_newsletter_box() . '</aside></section><section class="ss-band">' . storysentry_core_render_section_rule( 'The Beat', 'Across the desk' ) . '<div class="ss-beat">' . $beat_cols . '</div></section></div></main>';
}

function storysentry_core_render_archive_layout( array $attributes ): string {
	$variant = isset( $attributes['variant'] ) ? sanitize_key( (string) $attributes['variant'] ) : 'category';

	if ( 'stories' === $variant ) {
		$posts = storysentry_core_get_story_query(
			array(
				'posts_per_page' => 24,
			)
		)->posts;

		return '<main class="ss-main"><div class="ss-cat"><header class="ss-cat-head"><span class="ss-cat-eyebrow">THE WIRE</span><h1 class="ss-cat-title">Stories</h1><p class="ss-cat-sub">The full aggregated feed across Story Sentry sources.</p></header><section class="ss-band">' . storysentry_core_render_section_rule( 'The Wire', 'Latest from 2,418 sources' ) . storysentry_core_render_posts_rows( $posts, true ) . '</section></div></main>';
	}

	if ( 'search' === $variant ) {
		$query_string = get_search_query();
		$query        = storysentry_core_get_story_query(
			array(
				's'              => $query_string,
				'posts_per_page' => 18,
				'no_found_rows'  => false,
			)
		);
		$posts        = $query->posts;
		$found        = (int) $query->found_posts;
		$source_terms = get_terms(
			array(
				'taxonomy'   => 'ss_source_domain',
				'hide_empty' => true,
				'number'     => 8,
			)
		);
		$categories   = get_categories(
			array(
				'taxonomy'   => 'category',
				'hide_empty' => true,
				'number'     => 6,
			)
		);
		$source_counts = array();
		$cat_counts    = array();

		foreach ( $source_terms as $term ) {
			$source_counts[ $term->name ] = (int) $term->count;
		}

		foreach ( $categories as $term ) {
			$cat_counts[ $term->name ] = (int) $term->count;
		}

		return '<main class="ss-main"><div class="ss-search"><header class="ss-search-head"><span class="ss-cat-eyebrow">SEARCH</span><div class="ss-search-bar"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg><form role="search" method="get" class="ss-search-form" action="' . esc_url( home_url( '/' ) ) . '"><input type="search" value="' . esc_attr( $query_string ) . '" name="s" /><input type="hidden" name="post_type" value="ss_story" /></form><a class="ss-search-clear" href="' . esc_url( home_url( '/search/' ) ) . '">Clear</a></div><p class="ss-search-meta"><b>' . esc_html( (string) $found ) . '</b> results for <em>“' . esc_html( $query_string ) . '”</em> across <b>2,418</b> sources.</p><div class="ss-search-facets"><button class="ss-facet is-active">All <span>' . esc_html( (string) $found ) . '</span></button><button class="ss-facet">Last 24h <span>14</span></button><button class="ss-facet">Analysis <span>8</span></button><button class="ss-facet">Opinion <span>3</span></button></div></header><section class="ss-band ss-band--split"><div class="ss-split-main"><div class="ss-search-list">' . storysentry_core_render_posts_grid( $posts, 'search', true, true ) . '</div></div><aside class="ss-split-side">' . storysentry_core_render_section_rule( 'Refine', 'Sources' ) . storysentry_core_render_search_refine_list( $source_counts ) . storysentry_core_render_section_rule( 'Refine', 'Section' ) . storysentry_core_render_search_refine_list( $cat_counts ) . '</aside></section></div></main>';
	}

	$term = get_queried_object();

	if ( ! $term instanceof WP_Term ) {
		return '<main class="ss-main"><p>No archive context found.</p></main>';
	}

	$is_source   = 'source' === $variant;
	$taxonomy    = $is_source ? 'ss_source_domain' : 'category';
	$tax_query   = array(
		array(
			'taxonomy' => $taxonomy,
			'field'    => 'term_id',
			'terms'    => array( $term->term_id ),
		),
	);
	$query_args  = array(
		'posts_per_page' => 18,
		'tax_query'      => $tax_query,
	);
	$posts       = storysentry_core_get_story_query( $query_args )->posts;
	$story_count = storysentry_core_count_stories_for_query( $query_args );
	$source_count = $is_source ? 1 : max( 1, storysentry_core_get_unique_sources_for_posts( $posts ) );
	$hero        = isset( $posts[0] ) ? $posts[0] : null;
	$side        = array_slice( $posts, 1, 5 );
	$grid        = array_slice( $posts, 6, 8 );
	$list        = array_slice( $posts, 14 );

	if ( ! $hero instanceof WP_Post ) {
		return '<main class="ss-main"><p>No stories available yet.</p></main>';
	}

	if ( $is_source ) {
		$latest_story = $hero;
		$daily_avg    = max( 1, (int) ceil( $story_count / 30 ) );
		$last_update  = storysentry_core_get_ago( $latest_story->ID );
		$header       = '<header class="ss-pub-head"><span class="ss-cat-eyebrow">SOURCE PROFILE</span><div class="ss-pub-mast"><h1 class="ss-pub-title">' . esc_html( $term->name ) . '</h1><div class="ss-pub-stats"><div><b>' . esc_html( number_format_i18n( $story_count ) ) . '</b><span>stories indexed</span></div><div><b>' . esc_html( (string) $daily_avg ) . '</b><span>per day, avg</span></div><div><b>' . esc_html( $last_update ) . '</b><span>last update</span></div></div></div><div class="ss-pub-meta"><span>RSS · /feed/all</span><span class="ss-dot">·</span><span>Quality score <b>A+</b></span><span class="ss-dot">·</span><span>Tracked since Jan 2026</span><button class="ss-pub-follow">＋ Follow source</button></div></header>';
		$aside_label  = storysentry_core_get_source_short( $hero->ID );
		$archive_grid = '<section class="ss-band">' . storysentry_core_render_section_rule( 'Archive', 'More from this source' ) . '<div class="ss-grid-4">' . storysentry_core_render_posts_grid( $grid, 'med', true, false ) . '</div></section>';
	} else {
		$header       = '<header class="ss-cat-head"><span class="ss-cat-eyebrow">SECTION</span><h1 class="ss-cat-title">' . esc_html( $term->name ) . '</h1><p class="ss-cat-sub">' . esc_html( number_format_i18n( $story_count ) ) . '+ stories aggregated from ' . esc_html( (string) $source_count ) . ' sources in the last 24 hours.</p><div class="ss-cat-controls"><div class="ss-pills"><button class="ss-pill is-active">all</button><button class="ss-pill">breaking</button><button class="ss-pill">analysis</button><button class="ss-pill">opinion</button><button class="ss-pill">features</button></div><div class="ss-sort"><span>Sort</span><select><option>Latest</option><option>Most read</option><option>By source</option></select></div></div></header>';
		$aside_label  = $term->name;
		$archive_grid = '<section class="ss-band"><div class="ss-grid-4">' . storysentry_core_render_posts_grid( $grid, 'med', true, false ) . '</div></section><section class="ss-band">' . storysentry_core_render_section_rule( 'The Wire', 'More in this section' ) . storysentry_core_render_posts_rows( $list, true ) . '</section>';
	}

	return '<main class="ss-main"><div class="' . esc_attr( $is_source ? 'ss-pub-page' : 'ss-cat' ) . '">' . $header . '<section class="ss-hero"><div class="ss-hero-lead">' . storysentry_core_render_story_card_from_post( $hero, array( 'layout' => 'lead' ) ) . '</div><aside class="ss-hero-side">' . storysentry_core_render_section_rule( $is_source ? 'Latest From' : 'Also In', $aside_label ) . storysentry_core_render_posts_rows( $side, false, true, 'ss-numlist' ) . '</aside></section>' . $archive_grid . '</div></main>';
}

function storysentry_core_render_ad_slot( array $attributes ): string {
	$slot   = isset( $attributes['slot'] ) ? sanitize_text_field( (string) $attributes['slot'] ) : '1';
	$shortcode = is_numeric( $slot ) ? sprintf( '[adinserter block="%s"]', $slot ) : sprintf( '[adinserter name="%s"]', $slot );

	$content = do_shortcode( $shortcode );

	if ( '' !== trim( $content ) ) {
		return '<div class="ss-adslot">' . $content . '</div>';
	}

	$size_class = 'ss-adslot--970x250';

	if ( false !== strpos( $slot, 'mid' ) ) {
		$size_class = 'ss-adslot--728x90';
	} elseif ( false !== strpos( $slot, 'main' ) ) {
		$size_class = 'ss-adslot--970x400';
	}

	return '<div class="ss-adslot ' . esc_attr( $size_class ) . '" data-ad-slot="' . esc_attr( $slot ) . '"><div class="ss-adslot-inner"><span class="ss-adslot-tag">Ad Slot</span><span class="ss-adslot-id">#' . esc_html( $slot ) . '</span><span class="ss-adslot-size">' . esc_html( str_replace( 'ss-adslot--', '', $size_class ) ) . '</span><span class="ss-adslot-label">Interstitial / in-article placement</span></div></div>';
}

function storysentry_core_render_interstitial_view( array $attributes, string $content, WP_Block $block ): string {
	$post = storysentry_core_get_story_context( $block );

	if ( ! $post && is_singular( 'ss_story' ) ) {
		$post = get_queried_object();
	}

	if ( ! $post instanceof WP_Post ) {
		return '';
	}

	$slot         = isset( $attributes['slot'] ) ? sanitize_text_field( (string) $attributes['slot'] ) : 'interstitial-main';
	$cta_url      = storysentry_core_get_out_url( $post->ID );
	$source_domain = storysentry_core_get_source_domain( $post->ID );

	return '<div class="ss-int"><div class="ss-int-bar"><div class="ss-int-bar-l"><span class="ss-wordmark"><span class="ss-wordmark__glyph">◆</span><span class="ss-wordmark__name">Story Sentry</span></span><span class="ss-int-bar-sep"></span><span class="ss-int-bar-from">Forwarding to <b>' . esc_html( $source_domain ) . '</b></span></div><div class="ss-int-bar-r"><a class="ss-int-back" href="' . esc_url( get_permalink( $post ) ) . '">← Back to Story Sentry</a></div></div><div class="ss-int-prog"><div class="ss-int-prog-fill" style="width:100%"></div></div><div class="ss-int-stage ss-int-stage--classic">' . storysentry_core_render_ad_slot( array( 'slot' => $slot ) ) . '<div class="ss-int-cont"><a class="ss-int-go is-ready" href="' . esc_url( $cta_url ) . '">Continue to article <span>→</span></a><a class="ss-int-skip" href="' . esc_url( $cta_url ) . '">Skip ad</a></div></div></div>';
}

function storysentry_core_register_blocks(): void {
	$common = array(
		'api_version' => 3,
		'category'    => 'storysentry',
		'editor_script' => 'storysentry-core-editor',
		'supports'    => array(
			'align'      => false,
			'html'       => false,
			'className'  => true,
			'multiple'   => true,
			'reusable'   => true,
		),
	);

	register_block_type(
		'storysentry/site-header',
		array_merge(
			$common,
			array(
				'title'           => __( 'Site Header', 'storysentry-core' ),
				'icon'            => 'align-wide',
				'description'     => __( 'StorySentry branded site header.', 'storysentry-core' ),
				'attributes'      => array(
					'showMenuIcon' => array( 'type' => 'boolean', 'default' => true ),
					'showSaved'    => array( 'type' => 'boolean', 'default' => true ),
					'showSignIn'   => array( 'type' => 'boolean', 'default' => true ),
					'logoUrl'      => array( 'type' => 'string', 'default' => '' ),
				),
				'render_callback' => 'storysentry_core_render_site_header_block',
			)
		)
	);

	register_block_type(
		'storysentry/site-footer',
		array_merge(
			$common,
			array(
				'title'           => __( 'Site Footer', 'storysentry-core' ),
				'icon'            => 'align-wide',
				'description'     => __( 'StorySentry branded site footer.', 'storysentry-core' ),
				'attributes'      => array(
					'tagline'        => array( 'type' => 'string', 'default' => 'An aggregator of record. Pulling from 2,418 publishers, refreshed every minute.' ),
					'copyright'      => array( 'type' => 'string', 'default' => '© 2026 Story Sentry, Inc. · A WordPress publication.' ),
					'subscribeTitle' => array( 'type' => 'string', 'default' => 'Subscribe' ),
					'subscribeText'  => array( 'type' => 'string', 'default' => 'A morning brief, hand-curated.' ),
					'col1Title'      => array( 'type' => 'string', 'default' => 'Sections' ),
					'col2Title'      => array( 'type' => 'string', 'default' => 'Sources' ),
					'col3Title'      => array( 'type' => 'string', 'default' => 'Story Sentry' ),
					'logoUrl'        => array( 'type' => 'string', 'default' => '' ),
				),
				'render_callback' => 'storysentry_core_render_site_footer_block',
			)
		)
	);

	register_block_type(
		'storysentry/newsletter-box',
		array_merge(
			$common,
			array(
				'title'           => __( 'Newsletter Box', 'storysentry-core' ),
				'icon'            => 'email',
				'description'     => __( 'Editorial newsletter signup box.', 'storysentry-core' ),
				'render_callback' => 'storysentry_core_render_newsletter_box',
			)
		)
	);

	register_block_type(
		'storysentry/archive-term-header',
		array_merge(
			$common,
			array(
				'title'           => __( 'Archive Term Header', 'storysentry-core' ),
				'icon'            => 'heading',
				'description'     => __( 'Auto-rendered category/source archive header from the current archive context.', 'storysentry-core' ),
				'render_callback' => 'storysentry_core_render_archive_term_header',
			)
		)
	);

	register_block_type(
		'storysentry/archive-query-section',
		array_merge(
			$common,
			array(
				'title'           => __( 'Archive Query Section', 'storysentry-core' ),
				'icon'            => 'screenoptions',
				'description'     => __( 'Query section automatically filtered by the current archive term.', 'storysentry-core' ),
				'attributes'      => array(
					'variant'      => array( 'type' => 'string', 'default' => 'grid' ),
					'kicker'       => array( 'type' => 'string', 'default' => '' ),
					'label'        => array( 'type' => 'string', 'default' => '' ),
					'actionText'   => array( 'type' => 'string', 'default' => '' ),
					'actionUrl'    => array( 'type' => 'string', 'default' => '' ),
					'linkTarget'   => array( 'type' => 'string', 'default' => '' ),
					'postsToShow'  => array( 'type' => 'number', 'default' => 5 ),
					'offset'       => array( 'type' => 'number', 'default' => 0 ),
					'showImage'    => array( 'type' => 'boolean', 'default' => true ),
					'showExcerpt'  => array( 'type' => 'boolean', 'default' => true ),
				),
				'render_callback' => 'storysentry_core_render_archive_query_section',
			)
		)
	);

	register_block_type(
		'storysentry/archive-related-categories',
		array_merge(
			$common,
			array(
				'title'           => __( 'Archive Related Categories', 'storysentry-core' ),
				'icon'            => 'networking',
				'description'     => __( 'Renders sections for related categories defined in term meta.', 'storysentry-core' ),
				'attributes'      => array(
					'variant'      => array( 'type' => 'string', 'default' => 'list' ),
					'postsToShow'  => array( 'type' => 'number', 'default' => 5 ),
					'showImage'    => array( 'type' => 'boolean', 'default' => true ),
				),
				'render_callback' => 'storysentry_core_render_archive_related_categories',
			)
		)
	);

	register_block_type(
		'storysentry/query-section',
		array_merge(
			$common,
			array(
				'title'           => __( 'Query Section', 'storysentry-core' ),
				'icon'            => 'screenoptions',
				'description'     => __( 'Configurable editorial query section with category/source controls.', 'storysentry-core' ),
				'attributes'      => array(
					'variant'      => array(
						'type'    => 'string',
						'default' => 'grid',
					),
					'kicker'       => array(
						'type'    => 'string',
						'default' => '',
					),
					'label'        => array(
						'type'    => 'string',
						'default' => '',
					),
					'actionText'   => array(
						'type'    => 'string',
						'default' => '',
					),
					'actionUrl'    => array(
						'type'    => 'string',
						'default' => '',
					),
					'linkTarget'   => array(
						'type'    => 'string',
						'default' => '',
					),
					'categorySlug' => array(
						'type'    => 'string',
						'default' => '',
					),
					'sourceSlug'   => array(
						'type'    => 'string',
						'default' => '',
					),
					'postsToShow'  => array(
						'type'    => 'number',
						'default' => 5,
					),
					'offset'       => array(
						'type'    => 'number',
						'default' => 0,
					),
					'showImage'    => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'showExcerpt'  => array(
						'type'    => 'boolean',
						'default' => true,
					),
				),
				'render_callback' => 'storysentry_core_render_query_section',
			)
		)
	);

	register_block_type(
		'storysentry/story-breadcrumbs',
		array_merge(
			$common,
			array(
				'title'           => __( 'Story Breadcrumbs', 'storysentry-core' ),
				'icon'            => 'ellipsis',
				'description'     => __( 'Single story breadcrumb trail.', 'storysentry-core' ),
				'attributes'      => array(
					'frontLabel'    => array( 'type' => 'string', 'default' => 'Front Page' ),
					'categoryLabel' => array( 'type' => 'string', 'default' => '' ),
					'currentLabel'  => array( 'type' => 'string', 'default' => '' ),
					'showCategory'  => array( 'type' => 'boolean', 'default' => true ),
				),
				'uses_context'    => array( 'postId', 'postType' ),
				'render_callback' => 'storysentry_core_render_story_breadcrumbs',
			)
		)
	);

	register_block_type(
		'storysentry/story-header',
		array_merge(
			$common,
			array(
				'title'           => __( 'Story Header', 'storysentry-core' ),
				'icon'            => 'heading',
				'description'     => __( 'Single story title and metadata header.', 'storysentry-core' ),
				'attributes'      => array(
					'eyebrowText' => array( 'type' => 'string', 'default' => '' ),
					'showActions' => array( 'type' => 'boolean', 'default' => true ),
				),
				'uses_context'    => array( 'postId', 'postType' ),
				'render_callback' => 'storysentry_core_render_story_header_block',
			)
		)
	);

	register_block_type(
		'storysentry/story-prose',
		array_merge(
			$common,
			array(
				'title'           => __( 'Story Prose', 'storysentry-core' ),
				'icon'            => 'text-page',
				'description'     => __( 'RSS summary prose for the current story.', 'storysentry-core' ),
				'attributes'      => array(
					'summaryTag'     => array( 'type' => 'string', 'default' => '' ),
					'paragraphCount' => array( 'type' => 'number', 'default' => 3 ),
				),
				'uses_context'    => array( 'postId', 'postType' ),
				'render_callback' => 'storysentry_core_render_story_prose_block',
			)
		)
	);

	register_block_type(
		'storysentry/story-continue-gate',
		array_merge(
			$common,
			array(
				'title'           => __( 'Story Continue Gate', 'storysentry-core' ),
				'icon'            => 'arrow-right-alt',
				'description'     => __( 'Continue reading gate for the current story.', 'storysentry-core' ),
				'attributes'      => array(
					'eyebrowText' => array( 'type' => 'string', 'default' => '' ),
					'titleText'   => array( 'type' => 'string', 'default' => '' ),
					'bodyText'    => array( 'type' => 'string', 'default' => '' ),
					'ctaText'     => array( 'type' => 'string', 'default' => '' ),
				),
				'uses_context'    => array( 'postId', 'postType' ),
				'render_callback' => 'storysentry_core_render_story_continue_gate',
			)
		)
	);

	register_block_type(
		'storysentry/story-collection',
		array_merge(
			$common,
			array(
				'title'           => __( 'Story Collection', 'storysentry-core' ),
				'icon'            => 'images-alt2',
				'description'     => __( 'Related stories collection by source or category.', 'storysentry-core' ),
				'attributes'      => array(
					'mode'        => array( 'type' => 'string', 'default' => 'source' ),
					'kicker'      => array( 'type' => 'string', 'default' => '' ),
					'label'       => array( 'type' => 'string', 'default' => '' ),
					'postsToShow' => array( 'type' => 'number', 'default' => 4 ),
				),
				'uses_context'    => array( 'postId', 'postType' ),
				'render_callback' => 'storysentry_core_render_story_collection',
			)
		)
	);

	register_block_type(
		'storysentry/story-card',
		array_merge(
			$common,
			array(
				'title'        => __( 'Story Card', 'storysentry-core' ),
				'icon'         => 'index-card',
				'description'  => __( 'Context-aware story card for Query Loop templates.', 'storysentry-core' ),
				'attributes'   => array(
					'layout'        => array(
						'type'    => 'string',
						'default' => 'grid',
					),
					'linkTarget'    => array(
						'type'    => 'string',
						'default' => '',
					),
					'showImage'     => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'showExcerpt'   => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'showPublisher' => array(
						'type'    => 'boolean',
						'default' => true,
					),
				),
				'uses_context' => array( 'postId', 'postType' ),
				'render_callback' => 'storysentry_core_render_story_card',
			)
		)
	);

	register_block_type(
		'storysentry/story-hero',
		array_merge(
			$common,
			array(
				'title'           => __( 'Story Hero', 'storysentry-core' ),
				'icon'            => 'format-image',
				'description'     => __( 'Lead story hero for the front page.', 'storysentry-core' ),
				'uses_context'    => array( 'postId', 'postType' ),
				'render_callback' => 'storysentry_core_render_story_hero',
			)
		)
	);

	register_block_type(
		'storysentry/story-meta',
		array_merge(
			$common,
			array(
				'title'           => __( 'Story Meta', 'storysentry-core' ),
				'icon'            => 'editor-ul',
				'description'     => __( 'Source domain and original publish date from ss_story.', 'storysentry-core' ),
				'uses_context'    => array( 'postId', 'postType' ),
				'render_callback' => 'storysentry_core_render_story_meta_block',
			)
		)
	);

	register_block_type(
		'storysentry/story-image',
		array_merge(
			$common,
			array(
				'title'           => __( 'Story Image', 'storysentry-core' ),
				'icon'            => 'format-gallery',
				'description'     => __( 'Hotlinked story image from image_url meta.', 'storysentry-core' ),
				'attributes'      => array(
					'showCaption' => array( 'type' => 'boolean', 'default' => true ),
					'captionText' => array( 'type' => 'string', 'default' => '' ),
				),
				'uses_context'    => array( 'postId', 'postType' ),
				'render_callback' => 'storysentry_core_render_story_image_block',
			)
		)
	);

	register_block_type(
		'storysentry/story-summary',
		array_merge(
			$common,
			array(
				'title'           => __( 'Story Summary', 'storysentry-core' ),
				'icon'            => 'excerpt-view',
				'description'     => __( 'Single story summary with metadata and outbound CTA.', 'storysentry-core' ),
				'uses_context'    => array( 'postId', 'postType' ),
				'render_callback' => 'storysentry_core_render_story_summary',
			)
		)
	);

	register_block_type(
		'storysentry/ad-slot',
		array_merge(
			$common,
			array(
				'title'           => __( 'Ad Slot', 'storysentry-core' ),
				'icon'            => 'megaphone',
				'description'     => __( 'Ad Inserter slot wrapper.', 'storysentry-core' ),
				'attributes'      => array(
					'slot' => array(
						'type'    => 'string',
						'default' => '1',
					),
				),
				'render_callback' => 'storysentry_core_render_ad_slot',
			)
		)
	);

	register_block_type(
		'storysentry/interstitial-view',
		array_merge(
			$common,
			array(
				'title'           => __( 'Interstitial View', 'storysentry-core' ),
				'icon'            => 'welcome-view-site',
				'description'     => __( 'Interstitial screen before the outbound 301 redirect.', 'storysentry-core' ),
				'attributes'      => array(
					'slot' => array(
						'type'    => 'string',
						'default' => '1',
					),
				),
				'uses_context'    => array( 'postId', 'postType' ),
				'render_callback' => 'storysentry_core_render_interstitial_view',
			)
		)
	);

	register_block_type(
		'storysentry/archive-layout',
		array_merge(
			$common,
			array(
				'title'           => __( 'Archive Layout', 'storysentry-core' ),
				'icon'            => 'layout',
				'description'     => __( 'Prototype-matched layout for category, source, search, and archive screens.', 'storysentry-core' ),
				'attributes'      => array(
					'variant' => array(
						'type'    => 'string',
						'default' => 'category',
					),
				),
				'render_callback' => 'storysentry_core_render_archive_layout',
			)
		)
	);

	register_block_type(
		'storysentry/front-page-layout',
		array_merge(
			$common,
			array(
				'title'           => __( 'Front Page Layout', 'storysentry-core' ),
				'icon'            => 'cover-image',
				'description'     => __( 'Prototype-matched Story Sentry front page.', 'storysentry-core' ),
				'render_callback' => 'storysentry_core_render_front_page_layout',
			)
		)
	);
}
add_action( 'init', 'storysentry_core_register_blocks' );

function storysentry_core_maybe_redirect(): void {
	if ( ! is_singular( 'ss_story' ) || ! get_query_var( 'ss_go' ) ) {
		return;
	}

	$post_id      = get_queried_object_id();
	$original_url = (string) get_post_meta( $post_id, 'link', true );

	if ( '' === $original_url ) {
		return;
	}

	nocache_headers();
	wp_redirect( esc_url_raw( $original_url ), 301 );
	exit;
}
add_action( 'template_redirect', 'storysentry_core_maybe_redirect' );

function storysentry_core_is_legacy_news_request(): bool {
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';

	if ( '' === $request_uri ) {
		return false;
	}

	$path = wp_parse_url( $request_uri, PHP_URL_PATH );

	if ( ! is_string( $path ) || '' === $path ) {
		return false;
	}

	return 1 === preg_match( '#^/news(?:/|$)#', $path );
}

function storysentry_core_disable_canonical_for_legacy_news( $redirect_url ) {
	if ( storysentry_core_is_legacy_news_request() ) {
		return false;
	}

	return $redirect_url;
}
add_filter( 'redirect_canonical', 'storysentry_core_disable_canonical_for_legacy_news' );

function storysentry_core_mark_legacy_news_gone(): void {
	if ( ! storysentry_core_is_legacy_news_request() || ! is_404() ) {
		return;
	}

	status_header( 410 );
	nocache_headers();
}
add_action( 'template_redirect', 'storysentry_core_mark_legacy_news_gone', 1 );

function storysentry_core_template_include( string $template ): string {
	if ( is_singular( 'ss_story' ) && get_query_var( 'ss_interstitial' ) ) {
		return STORYSENTRY_CORE_PATH . 'build/interstitial-template.php';
	}

	return $template;
}
add_filter( 'template_include', 'storysentry_core_template_include' );
