<?php
/*
	default order functions - the default order for each enabled post
	type/taxonomy pair is stored as a single option
	(DEFAULT_ORDER_OPTION_NAME, i.e. 'azrcrv-to-default-order'), shaped as:

	array(
		'post|category' => array( 12, 4, 9, 1 ),  // term IDs, in display order
		'post|post_tag' => array( 7, 2 ),
	)

	(array key = "{post_type}|{taxonomy}")

	This is used two ways (see the PRD):
	- it seeds the sortable list in the edit-post metabox for a post whose
	  own terms have no post-specific order yet;
	- it's the site-wide fallback order used by the sorting filter (see
	  functions-sorting.php) for any post that hasn't been individually
	  reordered.
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
 * Build the option array key for a post type/taxonomy pair.
 */
function get_pair_key( $post_type, $taxonomy ) {
	return $post_type . '|' . $taxonomy;
}

/**
 * Get the full saved default-order option.
 *
 * @return array<string, int[]> Ordered term ID arrays, keyed by pair key.
 */
function get_default_order_settings() {
	$stored = get_option( DEFAULT_ORDER_OPTION_NAME, array() );
	return is_array( $stored ) ? $stored : array();
}

/**
 * Get the saved default term-ID order for a pair, exactly as stored (no
 * fallback appending of missing/new terms - see get_ordered_terms_for_pair()
 * for that).
 *
 * @param string $post_type Post type slug.
 * @param string $taxonomy  Taxonomy slug.
 *
 * @return int[] Term IDs, in order.
 */
function get_default_order_for_pair( $post_type, $taxonomy ) {
	$all = get_default_order_settings();
	$key = get_pair_key( $post_type, $taxonomy );
	return isset( $all[ $key ] ) && is_array( $all[ $key ] ) ? array_map( 'absint', $all[ $key ] ) : array();
}

/**
 * Save the default term-ID order for a pair.
 *
 * @param string $post_type Post type slug.
 * @param string $taxonomy  Taxonomy slug.
 * @param int[]  $term_ids  Term IDs, in order.
 */
function save_default_order_for_pair( $post_type, $taxonomy, $term_ids ) {
	$all              = get_default_order_settings();
	$key              = get_pair_key( $post_type, $taxonomy );
	$all[ $key ]      = array_values( array_map( 'absint', $term_ids ) );
	update_option( DEFAULT_ORDER_OPTION_NAME, $all );
}

/**
 * Get every term in a taxonomy, ordered: first the terms present in the
 * saved default order (in that saved order), then any remaining terms
 * (new since the default was last set, or never included) appended
 * alphabetically by name.
 *
 * @param string $post_type Post type slug (used only to look up the saved
 *                           default; the terms themselves come from the
 *                           taxonomy as a whole, not from any one post).
 * @param string $taxonomy  Taxonomy slug.
 *
 * @return \WP_Term[] Term objects, in order.
 */
function get_ordered_terms_for_pair( $post_type, $taxonomy ) {

	$all_terms = get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);

	if ( is_wp_error( $all_terms ) || empty( $all_terms ) ) {
		return array();
	}

	$terms_by_id = array();
	foreach ( $all_terms as $term ) {
		$terms_by_id[ $term->term_id ] = $term;
	}

	$ordered      = array();
	$default_order = get_default_order_for_pair( $post_type, $taxonomy );

	foreach ( $default_order as $term_id ) {
		if ( isset( $terms_by_id[ $term_id ] ) ) {
			$ordered[] = $terms_by_id[ $term_id ];
			unset( $terms_by_id[ $term_id ] );
		}
	}

	// Anything left in $terms_by_id wasn't in the saved default order
	// (either it's new, or the default has never been set) - append it,
	// already in name order from the get_terms() call above.
	foreach ( $terms_by_id as $term ) {
		$ordered[] = $term;
	}

	return $ordered;
}

/**
 * Handle the "Save Default Order" form on the Default Order tab.
 */
function handle_save_default_order() {

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permissions to perform this action.', 'azrcrv-to' ) );
	}

	if ( ! isset( $_POST[ PLUGIN_HYPHEN . '-default-order-nonce' ] ) || ! check_admin_referer( PLUGIN_HYPHEN . '-save-default-order', PLUGIN_HYPHEN . '-default-order-nonce' ) ) {
		redirect_with_message( 'invalid-nonce' );
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce already verified above.
	$posted    = wp_unslash( $_POST );
	$post_type = isset( $posted['post_type'] ) ? sanitize_key( $posted['post_type'] ) : '';
	$taxonomy  = isset( $posted['taxonomy'] ) ? sanitize_key( $posted['taxonomy'] ) : '';

	if ( ! is_pair_enabled( $post_type, $taxonomy ) ) {
		redirect_with_message( 'invalid-pair', array( 'tab' => 'default-order' ) );
	}

	$term_ids = array();
	if ( ! empty( $posted['term-order'] ) ) {
		$term_ids = array_filter( array_map( 'absint', explode( ',', $posted['term-order'] ) ) );
	}

	// Only accept term IDs that actually belong to this taxonomy - ignore
	// anything else the submitted form might contain.
	$valid_term_ids = array();
	foreach ( $term_ids as $term_id ) {
		if ( get_term( $term_id, $taxonomy ) instanceof \WP_Term ) {
			$valid_term_ids[] = $term_id;
		}
	}

	save_default_order_for_pair( $post_type, $taxonomy, $valid_term_ids );

	redirect_with_message(
		'default-order-saved',
		array(
			'tab'  => 'default-order',
			'pair' => get_pair_key( $post_type, $taxonomy ),
		)
	);
}
add_action( 'admin_post_' . PLUGIN_UNDERSCORE . '_save_default_order', __NAMESPACE__ . '\\handle_save_default_order' );
