<?php
/*
	instructions tab
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
?>

<h2><?php esc_html_e( 'Instructions', 'azrcrv-to' ); ?></h2>

<p><?php esc_html_e( 'This plugin lets the display order of a taxonomy\'s terms (categories, tags, or any custom taxonomy) be set for any public post type, either per individual post or as a shared default.', 'azrcrv-to' ); ?></p>

<ol>
	<li><?php esc_html_e( 'Open the Settings tab and enable ordering for each post type/taxonomy combination you want to control.', 'azrcrv-to' ); ?></li>
	<li><?php esc_html_e( 'Optionally, open the Default Order tab, choose one of the enabled pairs, and drag its terms into the order you want to use as a starting point everywhere that pair applies.', 'azrcrv-to' ); ?></li>
	<li><?php esc_html_e( 'Edit any post of an enabled post type: an "Order" box appears in the sidebar for each enabled taxonomy attached to that post, showing its current terms.', 'azrcrv-to' ); ?></li>
	<li><?php esc_html_e( 'Drag terms into the order you want, or use the up/down buttons, then save/update the post.', 'azrcrv-to' ); ?></li>
</ol>

<h3><?php esc_html_e( 'How the order is chosen', 'azrcrv-to' ); ?></h3>
<p>
	<?php esc_html_e( 'A post that has been individually reordered and saved always keeps its own order. A post that has not been individually reordered falls back to the default order set on the Default Order tab, if one has been set for that pair; otherwise terms display in their normal order.', 'azrcrv-to' ); ?>
</p>

<h3><?php esc_html_e( 'Where the order is used', 'azrcrv-to' ); ?></h3>
<p>
	<?php esc_html_e( 'The chosen order is applied anywhere a post\'s terms are retrieved in the normal way (for example, in category/tag lists shown on a single post, or in theme templates that loop over a post\'s terms), for any post type/taxonomy pair enabled on the Settings tab.', 'azrcrv-to' ); ?>
</p>
