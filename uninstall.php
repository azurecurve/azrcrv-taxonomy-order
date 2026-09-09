<?php

// Check that code was called from ClassicPress with uninstallation constant declared.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Hard-coded rather than referencing the plugin's constants: ClassicPress
// calls uninstall.php standalone, without loading the main plugin file
// first, so those constants are never defined here. These are the literal
// option names the plugin stores its settings under (see
// SETTINGS_OPTION_NAME and DEFAULT_ORDER_OPTION_NAME in the main plugin
// file).
$settings_option_name      = 'azrcrv-to';
$default_order_option_name = 'azrcrv-to-default-order';

/**
 * Reset term_order back to 0 for every wp_term_relationships row belonging
 * to a taxonomy that was part of an enabled post type/taxonomy pair -
 * i.e. only for pairs this plugin actually wrote to. A pair that was
 * never enabled is left completely untouched.
 *
 * Only runs when the admin has explicitly opted in via the "Remove term
 * order data on uninstall" setting - see functions-settings.php - since
 * wp_term_relationships is shared core storage, not something this plugin
 * owns exclusively, so wiping it is destructive and hard to reverse.
 *
 * @param array $enabled_pairs Taxonomy slugs, keyed by post type slug (as
 *                              stored under 'enabled-pairs' in the
 *                              settings option).
 */
function azrcrv_to_uninstall_reset_term_order( $enabled_pairs ) {

	global $wpdb;

	if ( ! is_array( $enabled_pairs ) ) {
		return;
	}

	// Flatten to a unique list of taxonomy slugs - term_order is stored per
	// (object, term_taxonomy) relationship, not per post type, so once a
	// taxonomy was ever part of an enabled pair for any post type, every
	// row for that taxonomy is in scope.
	$taxonomies = array();
	foreach ( $enabled_pairs as $post_type_taxonomies ) {
		if ( is_array( $post_type_taxonomies ) ) {
			foreach ( $post_type_taxonomies as $taxonomy ) {
				$taxonomies[ $taxonomy ] = true;
			}
		}
	}

	if ( empty( $taxonomies ) ) {
		return;
	}

	foreach ( array_keys( $taxonomies ) as $taxonomy ) {

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- uninstall routine, runs once, no plugin API available for a bulk taxonomy-scoped term_order reset.
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->term_relationships} tr
				INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
				SET tr.term_order = 0
				WHERE tt.taxonomy = %s",
				$taxonomy
			)
		);
	}
}

/**
 * Delete the plugin's own options, and - only if the admin opted in - reset
 * term_order for taxonomies that were part of an enabled pair, for the
 * current site.
 */
function azrcrv_to_uninstall_cleanup( $settings_option_name, $default_order_option_name ) {

	$settings = get_option( $settings_option_name );

	if ( is_array( $settings ) && ! empty( $settings['remove-term-order-on-uninstall'] ) && ! empty( $settings['enabled-pairs'] ) ) {
		azrcrv_to_uninstall_reset_term_order( $settings['enabled-pairs'] );
	}

	delete_option( $settings_option_name );
	delete_option( $default_order_option_name );
}

// Remove from single site.
if ( ! is_multisite() ) {

	azrcrv_to_uninstall_cleanup( $settings_option_name, $default_order_option_name );

	// Remove from every site on a multisite network.
} else {
	global $wpdb;

	$site_ids         = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
	$original_site_id = get_current_blog_id();

	foreach ( $site_ids as $site_id ) {
		switch_to_blog( $site_id );

		azrcrv_to_uninstall_cleanup( $settings_option_name, $default_order_option_name );
	}

	switch_to_blog( $original_site_id );
}
