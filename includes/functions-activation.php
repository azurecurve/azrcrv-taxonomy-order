<?php
/*
	activation / deactivation functions
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
 * Plugin activation. Triggers the one-off migration of a pre-2.0.0
 * settings row (see maybe_migrate_legacy_settings() in
 * functions-settings.php) immediately, rather than waiting for the first
 * time get_settings() happens to be called - so an upgraded site's
 * category/tag ordering keeps working from the moment the new version is
 * activated, even before the admin visits the settings page.
 */
function activate_plugin() {
	get_settings();
}

/**
 * Plugin deactivation. Deliberately does NOT delete either settings option
 * (see functions-settings.php and functions-default-order.php) and does
 * NOT touch any wp_term_relationships.term_order values - deactivating a
 * plugin should not lose the admin's configuration or a site's saved
 * ordering; that only conditionally happens on uninstall, and only for
 * term_order, and only if the admin opted in - see uninstall.php.
 */
function deactivate_plugin() {
	// Nothing to do.
}
