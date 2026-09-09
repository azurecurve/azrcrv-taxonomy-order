<?php
/*
	admin script/style enqueue functions - the tab UI assets load only on
	this plugin's own admin screen (or the shared azurecurve cross-plugin
	menu page); the sortable-list widget assets load only on edit-post
	screens for a post type that has at least one enabled taxonomy pair.
*/

/**
 * Declare the Namespace.
 */
namespace azurecurve\TaxonomyOrder;

/**
 * Prevent direct access.
 */
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Get a cache-busting version string for a plugin asset, based on the
 * file's own last-modified time rather than a hardcoded version number -
 * so every future edit to a CSS/JS file automatically gets a new
 * ?ver=... query string and browsers (and any server-side/CDN caching)
 * are guaranteed to fetch the updated file, without this plugin ever
 * needing to remember to bump a version constant by hand.
 *
 * @param string $relative_path Path relative to the plugin's root
 *                               directory, e.g. 'assets/js/sortable-list.js'.
 *
 * @return string
 */
function get_asset_version( $relative_path ) {

	$file = plugin_dir_path( PLUGIN_FILE ) . $relative_path;

	if ( file_exists( $file ) ) {
		return (string) filemtime( $file );
	}

	// Fall back to the plugin version if the file is somehow missing -
	// still better than a version string that never changes.
	return '2.0.0';
}

/**
 * Enqueue admin CSS/JS.
 */
function enqueue_admin_assets( $hook ) {

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only page identifier, not a state-changing action.
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

	$is_own_settings_page = ( PLUGIN_HYPHEN === $page || 'azrcrv-plugin-menu' === $page );
	$is_edit_post_screen  = in_array( $hook, array( 'post.php', 'post-new.php' ), true );

	if ( ! $is_own_settings_page && ! $is_edit_post_screen ) {
		return;
	}

	if ( $is_edit_post_screen ) {
		$screen = get_current_screen();
		if ( ! $screen || empty( get_enabled_taxonomies_for_post_type( $screen->post_type ) ) ) {
			$is_edit_post_screen = false;
		}
	}

	if ( ! $is_own_settings_page && ! $is_edit_post_screen ) {
		return;
	}

	wp_enqueue_style(
		PLUGIN_HYPHEN . '-admin',
		plugins_url( 'assets/css/admin.css', PLUGIN_FILE ),
		array(),
		get_asset_version( 'assets/css/admin.css' )
	);

	if ( $is_own_settings_page ) {

		wp_enqueue_style(
			PLUGIN_HYPHEN . '-admin-standard',
			plugins_url( 'assets/css/admin-standard.css', PLUGIN_FILE ),
			array(),
			get_asset_version( 'assets/css/admin-standard.css' )
		);

		wp_enqueue_style(
			PLUGIN_HYPHEN . '-admin-pluginmenu',
			plugins_url( 'assets/css/admin-pluginmenu.css', PLUGIN_FILE ),
			array(),
			get_asset_version( 'assets/css/admin-pluginmenu.css' )
		);

		wp_enqueue_script(
			PLUGIN_HYPHEN . '-admin-standard',
			plugins_url( 'assets/js/admin-standard.js', PLUGIN_FILE ),
			array(),
			get_asset_version( 'assets/js/admin-standard.js' ),
			true
		);
	}

	// The vanilla-JS drag-and-drop reorder widget: used both by the
	// edit-post metabox(es) and by the Default Order tab's term-order
	// editor, so it's loaded on both kinds of screen.
	wp_enqueue_script(
		PLUGIN_HYPHEN . '-sortable-list',
		plugins_url( 'assets/js/sortable-list.js', PLUGIN_FILE ),
		array(),
		get_asset_version( 'assets/js/sortable-list.js' ),
		true
	);
}
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue_admin_assets' );
