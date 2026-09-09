<?php
/*
	edit-post sidebar metabox functions - one metabox per enabled post
	type/taxonomy pair, rendered as a drag-and-drop reorderable list (see
	assets/js/sortable-list.js). Registration and saving are both handled
	here, directly on the WordPress/ClassicPress core hooks, rather than
	going through admin-post.php, since a metabox is saved as part of the
	normal post-edit form submission.
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
 * Register one metabox per taxonomy enabled for the post type currently
 * being edited.
 */
function add_order_metaboxes( $post_type ) {

	foreach ( get_enabled_taxonomies_for_post_type( $post_type ) as $taxonomy_slug ) {

		$taxonomy = get_taxonomy( $taxonomy_slug );

		if ( ! $taxonomy ) {
			continue;
		}

		add_meta_box(
			PLUGIN_HYPHEN . '-order-' . $taxonomy_slug,
			/* translators: %s: taxonomy label, e.g. "Category". */
			sprintf( __( '%s Display Order', 'azrcrv-to' ), $taxonomy->labels->singular_name ),
			__NAMESPACE__ . '\\render_order_metabox',
			$post_type,
			'side',
			'default',
			array( 'taxonomy' => $taxonomy_slug )
		);
	}
}
add_action( 'add_meta_boxes', __NAMESPACE__ . '\\add_order_metaboxes' );

/**
 * Get the term_order values ClassicPress core has stored against a specific
 * post's attachment to a taxonomy, straight from wp_term_relationships -
 * term objects themselves don't carry this value (it's relationship data,
 * not term data), so this can't be read any other way.
 *
 * @param int    $post_id  Post ID.
 * @param string $taxonomy Taxonomy slug.
 *
 * @return array<int, int> term_order values, keyed by term ID.
 */
function get_term_order_map( $post_id, $taxonomy ) {

	global $wpdb;

	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT tt.term_id, tr.term_order
			FROM {$wpdb->term_relationships} tr
			INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
			WHERE tr.object_id = %d AND tt.taxonomy = %s",
			$post_id,
			$taxonomy
		)
	);

	$map = array();
	foreach ( (array) $rows as $row ) {
		$map[ (int) $row->term_id ] = (int) $row->term_order;
	}

	return $map;
}

/**
 * Work out the display order for a post's terms in a taxonomy: the post's
 * own saved term_order values if every attached term has one set, otherwise
 * the pair's default order (falling back to name order for anything not in
 * the default), filtered down to just this post's attached terms.
 *
 * @param int      $post_id   Post ID.
 * @param string   $post_type Post type slug.
 * @param string   $taxonomy  Taxonomy slug.
 * @param \WP_Term[] $terms   The post's currently attached terms.
 *
 * @return \WP_Term[] $terms, re-ordered.
 */
function get_ordered_terms_for_post( $post_id, $post_type, $taxonomy, $terms ) {

	if ( empty( $terms ) ) {
		return $terms;
	}

	$order_map    = get_term_order_map( $post_id, $taxonomy );
	$has_own_order = true;

	foreach ( $terms as $term ) {
		if ( empty( $order_map[ $term->term_id ] ) ) {
			$has_own_order = false;
			break;
		}
	}

	if ( $has_own_order ) {
		usort(
			$terms,
			function ( $a, $b ) use ( $order_map ) {
				return $order_map[ $a->term_id ] <=> $order_map[ $b->term_id ];
			}
		);
		return $terms;
	}

	// No post-specific order yet - fall back to the pair's default order.
	$default_ordered_ids = wp_list_pluck( get_ordered_terms_for_pair( $post_type, $taxonomy ), 'term_id' );
	$terms_by_id          = array();
	foreach ( $terms as $term ) {
		$terms_by_id[ $term->term_id ] = $term;
	}

	$ordered = array();
	foreach ( $default_ordered_ids as $term_id ) {
		if ( isset( $terms_by_id[ $term_id ] ) ) {
			$ordered[] = $terms_by_id[ $term_id ];
			unset( $terms_by_id[ $term_id ] );
		}
	}

	// Anything left wasn't in the default order list either - keep it in
	// whatever order it originally arrived in.
	foreach ( $terms_by_id as $term ) {
		$ordered[] = $term;
	}

	return $ordered;
}

/**
 * Render the drag-and-drop reorderable list for one post type/taxonomy
 * pair.
 */
function render_order_metabox( $post, $metabox ) {

	$taxonomy = $metabox['args']['taxonomy'];

	if ( 0 === $post->ID || 'auto-draft' === $post->post_status ) {
		echo '<p>' . esc_html__( 'Please save the post before setting the display order.', 'azrcrv-to' ) . '</p>';
		return;
	}

	$terms = wp_get_post_terms( $post->ID, $taxonomy, array() );

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		echo '<p>' . esc_html__( 'No terms are attached to this post yet.', 'azrcrv-to' ) . '</p>';
		return;
	}

	$terms = get_ordered_terms_for_post( $post->ID, $post->post_type, $taxonomy, $terms );

	wp_nonce_field( PLUGIN_HYPHEN . '-metabox-' . $taxonomy, PLUGIN_HYPHEN . '-metabox-nonce-' . $taxonomy );

	// Deliberately NOT the same string as this metabox's own ID
	// (PLUGIN_HYPHEN . '-order-' . $taxonomy, registered in
	// add_order_metaboxes()) - ClassicPress/WordPress core renders every
	// metabox inside a wrapper element (<div>/<details class="postbox">)
	// using the metabox's own ID as that wrapper's HTML id attribute. Using
	// the same string here would create two elements sharing one id -
	// invalid HTML, and exactly the bug that caused a saved drag order to
	// keep reverting: document.getElementById() found the *wrapper*, not
	// this input, so JS was updating an element that was never actually
	// part of the form.
	$hidden_input_id = PLUGIN_HYPHEN . '-order-field-' . $taxonomy;
	?>
	<ul class="azrcrv-to-sortable-list" data-azrcrv-to-hidden-input="<?php echo esc_attr( $hidden_input_id ); ?>">
		<?php foreach ( $terms as $term ) : ?>
			<li class="azrcrv-to-sortable-item" draggable="true" data-term-id="<?php echo esc_attr( $term->term_id ); ?>">
				<span class="azrcrv-to-drag-handle" aria-hidden="true">&#9776;</span>
				<span class="azrcrv-to-term-name"><?php echo esc_html( $term->name ); ?></span>
				<span class="azrcrv-to-move-buttons">
					<button type="button" class="azrcrv-to-move-up" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: term name. */ __( 'Move %s up', 'azrcrv-to' ), $term->name ) ); ?>">&#9650;</button>
					<button type="button" class="azrcrv-to-move-down" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: term name. */ __( 'Move %s down', 'azrcrv-to' ), $term->name ) ); ?>">&#9660;</button>
				</span>
			</li>
		<?php endforeach; ?>
	</ul>
	<input type="hidden" id="<?php echo esc_attr( $hidden_input_id ); ?>" name="<?php echo esc_attr( $hidden_input_id ); ?>" value="<?php echo esc_attr( implode( ',', wp_list_pluck( $terms, 'term_id' ) ) ); ?>" />
	<p class="description"><?php esc_html_e( 'Drag to reorder, or use the up/down buttons.', 'azrcrv-to' ); ?></p>
	<?php
}

/**
 * Save the submitted order for every enabled taxonomy on this post, on
 * save_post.
 */
function save_order_metaboxes( $post_id, $post ) {

	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$enabled_taxonomies = get_enabled_taxonomies_for_post_type( $post->post_type );

	if ( empty( $enabled_taxonomies ) ) {
		return;
	}

	foreach ( $enabled_taxonomies as $taxonomy ) {

		$nonce_field = PLUGIN_HYPHEN . '-metabox-nonce-' . $taxonomy;

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified on the next line, per-taxonomy.
		if ( ! isset( $_POST[ $nonce_field ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $nonce_field ] ) ), PLUGIN_HYPHEN . '-metabox-' . $taxonomy ) ) {
			continue;
		}

		$hidden_input = PLUGIN_HYPHEN . '-order-field-' . $taxonomy;

		if ( ! isset( $_POST[ $hidden_input ] ) ) {
			continue;
		}

		$submitted_ids = array_filter( array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_POST[ $hidden_input ] ) ) ) ) );

		if ( empty( $submitted_ids ) ) {
			continue;
		}

		// Only ever write term_order for term_taxonomy_id rows that are
		// actually attached to this post in this taxonomy - a submitted
		// term ID that isn't genuinely attached (tampered with, or a race
		// with another save) is silently dropped rather than trusted.
		//
		// Queried fresh, directly, here - not via wp_get_post_terms() -
		// deliberately: we want the current term_taxonomy_id for each
		// attached term straight from the database at the moment we're
		// about to write to it, with no dependency on WordPress's own
		// object-term-relationship cache being in whatever state it's in
		// at this point in the request.
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT tt.term_id, tr.term_taxonomy_id
				FROM {$wpdb->term_relationships} tr
				INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
				WHERE tr.object_id = %d AND tt.taxonomy = %s",
				$post_id,
				$taxonomy
			)
		);

		$term_taxonomy_id_by_term_id = array();
		foreach ( (array) $rows as $row ) {
			$term_taxonomy_id_by_term_id[ (int) $row->term_id ] = (int) $row->term_taxonomy_id;
		}

		if ( empty( $term_taxonomy_id_by_term_id ) ) {
			continue;
		}

		// Start at 1, not 0: get_ordered_terms_for_post() treats a
		// term_order of 0 the same as "no custom order set" - if the
		// first term saved here got term_order 0, the whole post would
		// look like it had never been reordered.
		$order = 1;
		foreach ( $submitted_ids as $term_id ) {

			if ( ! isset( $term_taxonomy_id_by_term_id[ $term_id ] ) ) {
				continue;
			}

			$wpdb->update(
				$wpdb->term_relationships,
				array( 'term_order' => $order ),
				array(
					'object_id'        => $post_id,
					'term_taxonomy_id' => $term_taxonomy_id_by_term_id[ $term_id ],
				),
				array( '%d' ),
				array( '%d', '%d' )
			);

			++$order;
		}

		// Clear both ClassicPress core's own object-term-relationship
		// cache for this post/taxonomy (the group WordPress/ClassicPress
		// itself uses internally, populated by calls like
		// wp_get_post_terms()) and this plugin's own sorted-order cache
		// (see functions-sorting.php) - so nothing still in memory for
		// this request, or in a persistent object cache, can serve a
		// stale order after this write.
		wp_cache_delete( $post_id, $taxonomy . '_relationships' );
		clear_sorted_terms_cache( $post_id, $taxonomy );
	}
}
add_action( 'save_post', __NAMESPACE__ . '\\save_order_metaboxes', 10, 2 );
