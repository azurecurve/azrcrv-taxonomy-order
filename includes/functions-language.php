<?php
/*
	language functions
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
 * Load plugin translations. Loaded from /languages (not /assets/languages)
 * to match this plugin's pre-2.0.0 "Domain Path: /languages" header, so
 * any existing translation files for this plugin keep working unchanged.
 */
function load_languages() {
	load_plugin_textdomain( 'azrcrv-to', false, dirname( plugin_basename( PLUGIN_FILE ) ) . '/assets/languages/' );
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\load_languages' );
