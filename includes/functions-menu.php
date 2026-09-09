<?php
/*
	menu functions - admin menu registration and page rendering. The
	admin-post save handlers live in functions-settings.php and
	functions-default-order.php, alongside the functions they call.

	Like the pre-2.0.0 plugin, this plugin is deliberately reachable only
	via the shared azurecurve cross-plugin menu (registered in
	azurecurve-menu-display.php), not its own top-level ClassicPress admin
	menu page.
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
 * Add settings link on the Plugins list page.
 */
function add_plugin_action_link( $links, $file ) {

	$this_plugin = PLUGIN_SLUG . '/' . PLUGIN_SLUG . '.php';

	if ( $file === $this_plugin ) {
		$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=' . PLUGIN_HYPHEN ) ) . '"><img src="' . esc_url( plugins_url( '../assets/images/logo.svg', __FILE__ ) ) . '" style="padding-top: 2px; margin-right: -5px; height: 16px; width: 16px;" alt="azurecurve" />' . esc_html__( 'Settings', 'azrcrv-to' ) . '</a>';
		array_unshift( $links, $settings_link );
	}

	return $links;
}

/**
 * Add this plugin's entry under the shared azurecurve cross-plugin menu.
 */
function create_admin_menu() {

	add_submenu_page(
		'azrcrv-plugin-menu',
		esc_html__( 'Taxonomy Order Settings', 'azrcrv-to' ),
		esc_html__( 'Taxonomy Order', 'azrcrv-to' ),
		'manage_options',
		PLUGIN_HYPHEN,
		__NAMESPACE__ . '\\display_admin_page'
	);
}
