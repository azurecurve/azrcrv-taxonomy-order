<?php
/*
	setup - registration of activation/deactivation hooks and the handful
	of actions/filters not already self-registered alongside the functions
	they call:
	- admin_post_azrcrv_to_save_settings is registered in functions-settings.php
	- admin_post_azrcrv_to_save_default_order is registered in functions-default-order.php
	- add_meta_boxes / save_post are registered in functions-metabox.php
	- get_the_terms is registered in functions-sorting.php
	- admin_enqueue_scripts is registered in functions-scripts.php
	- plugins_loaded (language) is registered in functions-language.php
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

// Activation / deactivation.
register_activation_hook( PLUGIN_FILE, __NAMESPACE__ . '\\activate_plugin' );
register_deactivation_hook( PLUGIN_FILE, __NAMESPACE__ . '\\deactivate_plugin' );

// Admin menu.
add_action( 'admin_menu', __NAMESPACE__ . '\\create_admin_menu' );
add_filter( 'plugin_action_links_' . plugin_basename( PLUGIN_FILE ), __NAMESPACE__ . '\\add_plugin_action_link', 10, 2 );

// Update Manager: tell it where to find this plugin's own icon/banner
// images (assets/images) rather than falling back to a generic default.
$plugin_slug_for_um = plugin_basename( trim( PLUGIN_FILE ) );
add_filter( 'codepotent_update_manager_' . $plugin_slug_for_um . '_image_path', __NAMESPACE__ . '\\custom_image_path' );
add_filter( 'codepotent_update_manager_' . $plugin_slug_for_um . '_image_url', __NAMESPACE__ . '\\custom_image_url' );
