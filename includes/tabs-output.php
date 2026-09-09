<?php
/*
	tab output on the admin page - markup/classes match the shared
	azrcrv-ui-tabs component used across azurecurve's plugins (see
	assets/css/admin-standard.css and assets/js/admin-standard.js), rather
	than ClassicPress's native nav-tab-wrapper.

	Which tab starts active is controlled by the ?tab= query arg (used when
	redirecting back from the Default Order save handler), defaulting to
	Settings.
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

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only tab selector, not a state-changing action.
$active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'settings';
if ( ! in_array( $active_tab, array( 'settings', 'default-order', 'instructions', 'plugins' ), true ) ) {
	$active_tab = 'settings';
}

$tab_settings_label      = esc_html__( 'Settings', 'azrcrv-to' );
$tab_default_order_label = esc_html__( 'Default Order', 'azrcrv-to' );
$tab_instructions_label  = esc_html__( 'Instructions', 'azrcrv-to' );

ob_start();
require_once __DIR__ . '/tab-settings.php';
$tab_settings = ob_get_clean();

ob_start();
require_once __DIR__ . '/tab-default-order.php';
$tab_default_order = ob_get_clean();

ob_start();
require_once __DIR__ . '/tab-instructions.php';
$tab_instructions = ob_get_clean();

// tab-other-plugins.php sets $tab_plugins_label and $tab_plugins directly
// (matching the shared pattern used across azurecurve's other plugins)
// rather than being captured via ob_start(), since it builds its output as
// a string rather than echoing it.
require_once __DIR__ . '/tab-other-plugins.php';

$tabs = array(
	'settings'      => array( $tab_settings_label, $tab_settings ),
	'default-order' => array( $tab_default_order_label, $tab_default_order ),
	'instructions'  => array( $tab_instructions_label, $tab_instructions ),
	'plugins'       => array( $tab_plugins_label, $tab_plugins ),
);
?>

<div id="tabs" class="azrcrv-ui-tabs">
	<ul class="azrcrv-ui-tabs-nav azrcrv-ui-widget-header" role="tablist">
		<?php foreach ( $tabs as $tab_key => $tab ) : ?>
			<?php $is_active = ( $tab_key === $active_tab ); ?>
			<li class="azrcrv-ui-state-default<?php echo $is_active ? ' azrcrv-ui-state-active' : ''; ?>" aria-controls="tab-panel-<?php echo esc_attr( $tab_key ); ?>" aria-labelledby="tab-<?php echo esc_attr( $tab_key ); ?>" aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>" aria-expanded="<?php echo $is_active ? 'true' : 'false'; ?>" role="tab">
				<a id="tab-<?php echo esc_attr( $tab_key ); ?>" class="azrcrv-ui-tabs-anchor" href="#tab-panel-<?php echo esc_attr( $tab_key ); ?>"><?php echo $tab[0]; // phpcs:ignore. ?></a>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php foreach ( $tabs as $tab_key => $tab ) : ?>
		<?php $is_active = ( $tab_key === $active_tab ); ?>
		<div id="tab-panel-<?php echo esc_attr( $tab_key ); ?>" class="azrcrv-ui-tabs-scroll<?php echo $is_active ? '' : ' azrcrv-ui-tabs-hidden'; ?>" role="tabpanel" aria-hidden="<?php echo $is_active ? 'false' : 'true'; ?>">
			<fieldset>
				<legend class="screen-reader-text"><?php echo $tab[0]; // phpcs:ignore. ?></legend>
				<?php echo $tab[1]; // phpcs:ignore. ?>
			</fieldset>
		</div>
	<?php endforeach; ?>
</div>
<?php
/*
	donate button
*/
?>
<div class="azrcrv-donate">
	<?php esc_html_e( 'Support', 'azrcrv-to' ); ?>
	azurecurve | Development
	<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
		<input type="hidden" name="cmd" value="_s-xclick">
		<input type="hidden" name="hosted_button_id" value="MCJQN9SJZYLWJ">
		<input type="image" src="https://www.paypalobjects.com/en_US/GB/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal – The safer, easier way to pay online.">
		<img alt="" border="0" src="https://www.paypalobjects.com/en_GB/i/scr/pixel.gif" width="1" height="1">
	</form>
	<span>
		<?php esc_html_e( 'You can help support the development of our free plugins by donating a small amount of money.', 'azrcrv-to' ); ?>
	</span>
</div>
