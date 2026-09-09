<?php
/*
	settings tab - lets the admin enable term ordering per post
	type/taxonomy pair, and choose whether uninstalling the plugin should
	also reset term_order for pairs that were enabled.
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

$settings            = get_settings();
$enabled_pairs       = $settings['enabled-pairs'];
$eligible            = get_eligible_post_types_and_taxonomies();
?>

<p><?php esc_html_e( 'Enable term display ordering for any post type/taxonomy combination below. An "Order" metabox will then appear on the edit screen for that post type, letting terms attached to a post be dragged into a custom order.', 'azrcrv-to' ); ?></p>

<?php if ( empty( $eligible ) ) : ?>

	<p><em><?php esc_html_e( 'No public post types with a public taxonomy were found.', 'azrcrv-to' ); ?></em></p>

<?php else : ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">

		<input type="hidden" name="action" value="<?php echo esc_attr( PLUGIN_UNDERSCORE ); ?>_save_settings" />
		<?php wp_nonce_field( PLUGIN_HYPHEN . '-save-settings', PLUGIN_HYPHEN . '-nonce' ); ?>

		<table class="form-table azrcrv-to-settings" role="presentation">
			<tbody>
				<?php foreach ( $eligible as $post_type_slug => $taxonomies ) : ?>
					<?php $post_type_object = get_post_type_object( $post_type_slug ); ?>
					<tr>
						<th scope="row"><?php echo esc_html( $post_type_object->labels->name ); ?></th>
						<td>
							<?php foreach ( $taxonomies as $taxonomy_slug => $taxonomy ) : ?>
								<label class="azrcrv-to-pair-checkbox">
									<input
										type="checkbox"
										name="pairs[<?php echo esc_attr( $post_type_slug ); ?>][<?php echo esc_attr( $taxonomy_slug ); ?>]"
										value="1"
										<?php checked( in_array( $taxonomy_slug, isset( $enabled_pairs[ $post_type_slug ] ) ? $enabled_pairs[ $post_type_slug ] : array(), true ) ); ?>
									/>
									<?php echo esc_html( $taxonomy->labels->name ); ?>
								</label>
							<?php endforeach; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<h2><?php esc_html_e( 'Uninstall', 'azrcrv-to' ); ?></h2>
		<table class="form-table azrcrv-to-settings" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><?php esc_html_e( 'Remove term order data on uninstall', 'azrcrv-to' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="remove-term-order-on-uninstall" value="1" <?php checked( ! empty( $settings['remove-term-order-on-uninstall'] ) ); ?> />
							<?php esc_html_e( 'When this plugin is uninstalled, also reset the saved display order back to default for every post type/taxonomy pair that was enabled above.', 'azrcrv-to' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Leave this unchecked to keep your saved order intact if you reinstall the plugin later. This only affects pairs that were enabled - it never touches a pair you never turned on.', 'azrcrv-to' ); ?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>

		<?php submit_button( __( 'Save Settings', 'azrcrv-to' ) ); ?>
	</form>

<?php endif; ?>
