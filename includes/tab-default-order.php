<?php
/*
	default order tab - lets the admin choose an enabled post
	type/taxonomy pair and drag its terms into a default order, used both
	to seed the metabox for new posts and as the site-wide fallback order
	(see functions-default-order.php).
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

$enabled_pairs = get_enabled_pairs();

// Build a flat list of enabled pairs for the picker.
$pair_options = array();
foreach ( $enabled_pairs as $post_type_slug => $taxonomy_slugs ) {

	$post_type_object = get_post_type_object( $post_type_slug );
	if ( ! $post_type_object ) {
		continue;
	}

	foreach ( $taxonomy_slugs as $taxonomy_slug ) {

		$taxonomy = get_taxonomy( $taxonomy_slug );
		if ( ! $taxonomy ) {
			continue;
		}

		$pair_options[] = array(
			'post_type'      => $post_type_slug,
			'taxonomy'       => $taxonomy_slug,
			'label'          => sprintf(
				/* translators: 1: taxonomy label, 2: post type label. */
				__( '%1$s (on %2$s)', 'azrcrv-to' ),
				$taxonomy->labels->name,
				$post_type_object->labels->name
			),
		);
	}
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only pair selector, not a state-changing action.
$selected_key = isset( $_GET['pair'] ) ? sanitize_text_field( wp_unslash( $_GET['pair'] ) ) : '';
$selected     = null;

foreach ( $pair_options as $option ) {
	if ( get_pair_key( $option['post_type'], $option['taxonomy'] ) === $selected_key ) {
		$selected = $option;
		break;
	}
}

if ( ! $selected && ! empty( $pair_options ) ) {
	$selected = $pair_options[0];
}
?>

<p><?php esc_html_e( 'Set a default term order for an enabled post type/taxonomy pair. This is used to pre-fill the order for posts that have not been individually reordered yet, and as the fallback order shown anywhere the plugin sorts that taxonomy for that post type.', 'azrcrv-to' ); ?></p>

<?php if ( empty( $pair_options ) ) : ?>

	<p><em><?php esc_html_e( 'No post type/taxonomy pairs are enabled yet - enable at least one on the Settings tab first.', 'azrcrv-to' ); ?></em></p>

<?php else : ?>

	<form method="get" action="">
		<input type="hidden" name="page" value="<?php echo esc_attr( PLUGIN_HYPHEN ); ?>" />
		<input type="hidden" name="tab" value="default-order" />
		<label for="azrcrv-to-pair-select"><?php esc_html_e( 'Post type/taxonomy pair:', 'azrcrv-to' ); ?></label>
		<select name="pair" id="azrcrv-to-pair-select" onchange="this.form.submit()">
			<?php foreach ( $pair_options as $option ) : ?>
				<?php $key = get_pair_key( $option['post_type'], $option['taxonomy'] ); ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $selected && get_pair_key( $selected['post_type'], $selected['taxonomy'] ) === $key ); ?>>
					<?php echo esc_html( $option['label'] ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<noscript><?php submit_button( __( 'Choose', 'azrcrv-to' ), 'secondary', '', false ); ?></noscript>
	</form>

	<?php if ( $selected ) : ?>

		<?php $ordered_terms = get_ordered_terms_for_pair( $selected['post_type'], $selected['taxonomy'] ); ?>

		<?php if ( empty( $ordered_terms ) ) : ?>

			<p><em><?php esc_html_e( 'This taxonomy has no terms yet.', 'azrcrv-to' ); ?></em></p>

		<?php else : ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">

				<input type="hidden" name="action" value="<?php echo esc_attr( PLUGIN_UNDERSCORE ); ?>_save_default_order" />
				<input type="hidden" name="post_type" value="<?php echo esc_attr( $selected['post_type'] ); ?>" />
				<input type="hidden" name="taxonomy" value="<?php echo esc_attr( $selected['taxonomy'] ); ?>" />
				<?php wp_nonce_field( PLUGIN_HYPHEN . '-save-default-order', PLUGIN_HYPHEN . '-default-order-nonce' ); ?>

				<ul class="azrcrv-to-sortable-list" data-azrcrv-to-hidden-input="term-order">
					<?php foreach ( $ordered_terms as $term ) : ?>
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
				<input type="hidden" id="azrcrv-to-default-order-input" name="term-order" value="<?php echo esc_attr( implode( ',', wp_list_pluck( $ordered_terms, 'term_id' ) ) ); ?>" />
				<p class="description"><?php esc_html_e( 'Drag to reorder, or use the up/down buttons.', 'azrcrv-to' ); ?></p>

				<?php submit_button( __( 'Save Default Order', 'azrcrv-to' ) ); ?>
			</form>

		<?php endif; ?>

	<?php endif; ?>

<?php endif; ?>
