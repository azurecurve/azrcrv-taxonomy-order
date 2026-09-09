<?php
/*
	sorting functions - re-orders a post's terms wherever core reads them
	via get_the_terms(), for any enabled post type/taxonomy pair. Order of
	precedence: the post's own saved term_order values (if every attached
	term has one), else the pair's default order, else core's normal
	(alphabetical) order - see get_ordered_terms_for_post() in
	functions-metabox.php, which this shares.
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
 * Cache group used for a given taxonomy's sorted-term-order cache.
 */
function get_sorted_terms_cache_group( $taxonomy ) {
	return $taxonomy . '_relationships_sorted';
}

/**
 * Clear the sorted-order cache for one post/taxonomy - called whenever the
 * metabox save handler writes new term_order values, so a freshly-reordered
 * post never shows a stale order (this was missing entirely in the
 * pre-2.0.0 plugin).
 */
function clear_sorted_terms_cache( $post_id, $taxonomy ) {
	wp_cache_delete( $post_id, get_sorted_terms_cache_group( $taxonomy ) );
}

/**
 * Re-order a post's terms for an enabled pair.
 */
function maybe_sort_terms( $terms, $id, $taxonomy ) {

	if ( empty( $terms ) || is_wp_error( $terms ) || ! is_array( $terms ) ) {
		return $terms;
	}

	$post_type = get_post_type( $id );

	if ( ! $post_type || ! is_pair_enabled( $post_type, $taxonomy ) ) {
		return $terms;
	}

	$cache_group = get_sorted_terms_cache_group( $taxonomy );
	$cached      = wp_cache_get( $id, $cache_group );

	if ( false !== $cached ) {
		return $cached;
	}

	$ordered = get_ordered_terms_for_post( $id, $post_type, $taxonomy, $terms );

	wp_cache_add( $id, $ordered, $cache_group );

	return $ordered;
}
add_filter( 'get_the_terms', __NAMESPACE__ . '\\maybe_sort_terms', 10, 3 );
