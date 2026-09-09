<?php
/**
 * Discovery of front-end post types and their public taxonomies.
 *
 * Nothing here is cached in the database - it's recomputed from whatever is
 * currently registered, so post types/taxonomies added by other plugins or
 * the active theme show up automatically without any migration step.
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
 * Get the list of post types eligible for taxonomy ordering: public,
 * visible in the admin UI, excluding attachments (which don't have a normal
 * edit-post screen or a meaningful "front end" taxonomy display).
 *
 * @since 2.0.0
 *
 * @return \WP_Post_Type[] Post type objects, keyed by post type slug.
 */
function get_eligible_post_types() {

	$post_types = get_post_types(
		array(
			'public'   => true,
			'show_ui'  => true,
		),
		'objects'
	);

	unset( $post_types['attachment'] );

	/**
	 * Filter the list of post types eligible for taxonomy ordering.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_Post_Type[] $post_types Eligible post type objects, keyed by slug.
	 */
	return apply_filters( PLUGIN_UNDERSCORE . '_eligible_post_types', $post_types );

}

/**
 * Get the public taxonomies registered to a given post type.
 *
 * @since 2.0.0
 *
 * @param string $post_type Post type slug.
 *
 * @return \WP_Taxonomy[] Taxonomy objects, keyed by taxonomy slug.
 */
function get_eligible_taxonomies_for_post_type( $post_type ) {

	$taxonomies = get_object_taxonomies( $post_type, 'objects' );

	foreach ( $taxonomies as $taxonomy_slug => $taxonomy ) {
		if ( empty( $taxonomy->public ) ) {
			unset( $taxonomies[ $taxonomy_slug ] );
		}
	}

	/**
	 * Filter the list of taxonomies eligible for ordering on a post type.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_Taxonomy[] $taxonomies Eligible taxonomy objects, keyed by slug.
	 * @param string         $post_type  Post type slug.
	 */
	return apply_filters( PLUGIN_UNDERSCORE . '_eligible_taxonomies_for_post_type', $taxonomies, $post_type );

}

/**
 * Get every eligible post type together with its eligible taxonomies, in
 * one call - this is what the settings matrix and the default-order picker
 * both iterate over.
 *
 * @since 2.0.0
 *
 * @return array<string, \WP_Taxonomy[]> Eligible taxonomy objects, keyed by
 *                                       taxonomy slug, keyed in turn by post
 *                                       type slug.
 */
function get_eligible_post_types_and_taxonomies() {

	$map = array();

	foreach ( get_eligible_post_types() as $post_type_slug => $post_type ) {

		$taxonomies = get_eligible_taxonomies_for_post_type( $post_type_slug );

		if ( empty( $taxonomies ) ) {
			continue;
		}

		$map[ $post_type_slug ] = $taxonomies;

	}

	return $map;

}

/**
 * Check whether a post type + taxonomy pair is currently eligible (i.e.
 * still exists and is still public) - used to sanitize saved settings and
 * incoming form data against reality, rather than trusting stored slugs
 * blindly.
 *
 * @since 2.0.0
 *
 * @param string $post_type Post type slug.
 * @param string $taxonomy  Taxonomy slug.
 *
 * @return bool
 */
function is_pair_eligible( $post_type, $taxonomy ) {

	$eligible = get_eligible_post_types_and_taxonomies();

	return isset( $eligible[ $post_type ][ $taxonomy ] );

}
