<?php
/*
	settings functions - settings are stored as a single option
	(SETTINGS_OPTION_NAME, i.e. 'azrcrv-to'), shaped as:

	array(
		'enabled-pairs' => array(
			'post'    => array( 'category', 'post_tag' ),
			'product' => array( 'post_tag', 'product_cat' ),
		),
		'remove-term-order-on-uninstall' => 0,
	)

	Pre-2.0.0 installs stored this option shaped as:

	array(
		'enable-category-order' => 1,
		'enable-tag-order'      => 1,
	)

	get_settings() migrates the old shape into the new one, once, the first
	time it's called after upgrading - see maybe_migrate_legacy_settings().
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
 * Render the admin page (Settings/Default Order/Instructions/Other Plugins,
 * in tabs).
 */
function display_admin_page() {

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'azrcrv-to' ) );
	}

	echo '<div class="wrap ' . esc_attr( PLUGIN_HYPHEN ) . '-wrap">';
	echo '<h1>';
		echo '<a href="' . esc_url_raw( DEVELOPER_RAW_LINK ) . esc_attr( PLUGIN_SHORT_SLUG ) . '/"><img src="' . esc_url_raw( plugins_url( '../assets/images/logo.svg', __FILE__ ) ) . '" style="padding-right: 6px; height: 20px; width: 20px;" alt="' . esc_attr( DEVELOPER_NAME ) . '" /></a>';
		echo esc_html( get_admin_page_title() );
	echo '</h1>';

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only status flag, not a state-changing action.
	if ( isset( $_GET[ PLUGIN_HYPHEN . '-message' ] ) ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$message_key = sanitize_key( wp_unslash( $_GET[ PLUGIN_HYPHEN . '-message' ] ) );
		render_admin_notice( $message_key );
	}

	require_once __DIR__ . '/tabs-output.php';

	echo '</div>';
}

/**
 * Show a dismissible admin notice for a given message key, set via a
 * redirect query arg after a save action.
 */
function render_admin_notice( $message_key ) {

	$messages = array(
		'settings-saved'      => array( 'success', __( 'Settings saved.', 'azrcrv-to' ) ),
		'default-order-saved' => array( 'success', __( 'Default order saved.', 'azrcrv-to' ) ),
		'invalid-nonce'       => array( 'error', __( 'Security check failed - please try again.', 'azrcrv-to' ) ),
		'invalid-pair'        => array( 'error', __( 'That post type/taxonomy combination is not currently enabled.', 'azrcrv-to' ) ),
	);

	if ( ! isset( $messages[ $message_key ] ) ) {
		return;
	}

	list( $type, $text ) = $messages[ $message_key ];
	$css_class            = 'success' === $type ? 'notice-success' : 'notice-error';

	echo '<div class="notice ' . esc_attr( $css_class ) . ' is-dismissible"><p>' . esc_html( $text ) . '</p></div>';
}

/**
 * Build a redirect URL back to the admin page with a status message.
 */
function redirect_with_message( $message_key, $extra_args = array() ) {
	$args = array_merge(
		array(
			'page'                      => PLUGIN_HYPHEN,
			PLUGIN_HYPHEN . '-message' => $message_key,
		),
		$extra_args
	);
	wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
	exit;
}

/**
 * The plugin's built-in default values.
 */
function get_builtin_settings() {
	return array(
		'enabled-pairs'                   => array(),
		'remove-term-order-on-uninstall'  => 0,
	);
}

/**
 * One-off migration of a pre-2.0.0 settings row into the new shape. Returns
 * the migrated array (and persists it) if migration was needed and
 * performed, or null if there was nothing to migrate (either a fresh
 * install, or an install already on the new shape).
 *
 * Legacy keys are left in the option row rather than deleted, in case
 * anything else on the site happens to read them.
 */
function maybe_migrate_legacy_settings( $stored ) {

	if ( isset( $stored['enabled-pairs'] ) ) {
		return null;
	}

	if ( ! isset( $stored['enable-category-order'] ) && ! isset( $stored['enable-tag-order'] ) ) {
		return null;
	}

	$pairs = array();

	if ( ! empty( $stored['enable-category-order'] ) ) {
		$pairs['post'][] = 'category';
	}

	if ( ! empty( $stored['enable-tag-order'] ) ) {
		$pairs['post'][] = 'post_tag';
	}

	$stored['enabled-pairs']                  = $pairs;
	$stored['remove-term-order-on-uninstall'] = 0;

	update_option( SETTINGS_OPTION_NAME, $stored );

	return $stored;
}

/**
 * Get the saved settings, merged over the built-in defaults so every key is
 * always present even for a fresh install (fixes a bug in the pre-2.0.0
 * plugin where a fresh install's un-set option, i.e. get_option() returning
 * false, was read from directly, producing a PHP warning), and migrated
 * from the pre-2.0.0 shape if needed.
 */
function get_settings() {
	$stored = get_option( SETTINGS_OPTION_NAME, array() );

	if ( ! is_array( $stored ) ) {
		$stored = array();
	}

	$migrated = maybe_migrate_legacy_settings( $stored );
	if ( null !== $migrated ) {
		$stored = $migrated;
	}

	$settings = wp_parse_args( $stored, get_builtin_settings() );

	// Always sanitize the enabled-pairs list against what's actually
	// registered right now, in case a post type or taxonomy referenced by
	// a saved pair has since been removed (by deactivating another
	// plugin, a theme switch, etc.) - this is what get_enabled_pairs()
	// elsewhere in the plugin relies on to avoid acting on stale slugs.
	$settings['enabled-pairs'] = sanitize_enabled_pairs( $settings['enabled-pairs'] );

	return $settings;
}

/**
 * Filter a raw enabled-pairs array down to post type/taxonomy combinations
 * that are currently eligible (see functions-post-types.php).
 */
function sanitize_enabled_pairs( $pairs ) {

	if ( ! is_array( $pairs ) ) {
		return array();
	}

	$sanitized = array();

	foreach ( $pairs as $post_type => $taxonomies ) {

		if ( ! is_array( $taxonomies ) ) {
			continue;
		}

		foreach ( $taxonomies as $taxonomy ) {
			if ( is_pair_eligible( $post_type, $taxonomy ) ) {
				$sanitized[ $post_type ][] = $taxonomy;
			}
		}
	}

	return $sanitized;
}

/**
 * Persist the settings.
 */
function save_settings( $settings ) {
	update_option( SETTINGS_OPTION_NAME, $settings );
}

/**
 * Get just the enabled post type/taxonomy pairs.
 *
 * @return array<string, string[]> Taxonomy slugs, keyed by post type slug.
 */
function get_enabled_pairs() {
	$settings = get_settings();
	return $settings['enabled-pairs'];
}

/**
 * Get the taxonomies enabled for a specific post type.
 *
 * @param string $post_type Post type slug.
 *
 * @return string[] Taxonomy slugs.
 */
function get_enabled_taxonomies_for_post_type( $post_type ) {
	$pairs = get_enabled_pairs();
	return isset( $pairs[ $post_type ] ) ? $pairs[ $post_type ] : array();
}

/**
 * Whether a specific post type/taxonomy pair is enabled.
 *
 * @param string $post_type Post type slug.
 * @param string $taxonomy  Taxonomy slug.
 *
 * @return bool
 */
function is_pair_enabled( $post_type, $taxonomy ) {
	return in_array( $taxonomy, get_enabled_taxonomies_for_post_type( $post_type ), true );
}

/**
 * Whether term order data should be reset on uninstall.
 *
 * @return bool
 */
function should_remove_term_order_on_uninstall() {
	$settings = get_settings();
	return ! empty( $settings['remove-term-order-on-uninstall'] );
}

/**
 * Build a sanitized settings array from a submitted settings form ($_POST).
 * Checkboxes are named pairs[{post_type}][{taxonomy}] and are only present
 * in $_POST when checked; every submitted pair is re-validated against
 * is_pair_eligible() so the saved option can never contain a post
 * type/taxonomy combination that isn't actually registered, regardless of
 * what was submitted.
 */
function sanitize_settings_from_post( $post_data ) {

	$pairs = array();

	if ( isset( $post_data['pairs'] ) && is_array( $post_data['pairs'] ) ) {
		foreach ( $post_data['pairs'] as $post_type => $taxonomies ) {

			$post_type = sanitize_key( $post_type );

			if ( ! is_array( $taxonomies ) ) {
				continue;
			}

			foreach ( array_keys( $taxonomies ) as $taxonomy ) {
				$taxonomy = sanitize_key( $taxonomy );
				if ( is_pair_eligible( $post_type, $taxonomy ) ) {
					$pairs[ $post_type ][] = $taxonomy;
				}
			}
		}
	}

	return array(
		'enabled-pairs'                  => $pairs,
		'remove-term-order-on-uninstall' => empty( $post_data['remove-term-order-on-uninstall'] ) ? 0 : 1,
	);
}

/**
 * Handle the "Save Settings" form on the Settings tab.
 */
function handle_save_settings() {

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permissions to perform this action.', 'azrcrv-to' ) );
	}

	if ( ! isset( $_POST[ PLUGIN_HYPHEN . '-nonce' ] ) || ! check_admin_referer( PLUGIN_HYPHEN . '-save-settings', PLUGIN_HYPHEN . '-nonce' ) ) {
		redirect_with_message( 'invalid-nonce' );
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce already verified above.
	$settings = sanitize_settings_from_post( wp_unslash( $_POST ) );

	// A pair that was just disabled may have been the only pair a saved
	// default order referred to; leaving the orphaned default order data
	// in place is harmless (it's simply inert until the pair is re-enabled)
	// so nothing further needs to happen here.
	save_settings( $settings );

	redirect_with_message( 'settings-saved' );
}
add_action( 'admin_post_' . PLUGIN_UNDERSCORE . '_save_settings', __NAMESPACE__ . '\\handle_save_settings' );
