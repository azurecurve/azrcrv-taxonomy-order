<?php
/**
 * ------------------------------------------------------------------------------
 * Plugin Name:		Taxonomy Order
 * Description:		Set display order of the taxonomies (categories, tags, and any
 * 					custom taxonomy) of any public post type.
 * Version:			2.0.0
 * Requires CP:		1.0
 * Requires PHP:	8.2
 * Author:			azurecurve
 * Author URI:		https://development.azurecurve.co.uk/classicpress-plugins/
 * Plugin URI:		https://development.azurecurve.co.uk/classicpress-plugins/azrcrv-taxonomy-order/
 * Donate link:		https://development.azurecurve.co.uk/support-development/
 * Text Domain:		azrcrv-to
 * Domain Path:		/assets/languages
 * License:			GPLv2 or later
 * License URI:		http://www.gnu.org/licenses/gpl-2.0.html
 * ------------------------------------------------------------------------------
 * This is free software released under the terms of the General Public License,
 * version 2, or later. It is distributed WITHOUT ANY WARRANTY; without even the
 * implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. Full
 * text of the license is available at https://www.gnu.org/licenses/gpl-2.0.html.
 * ------------------------------------------------------------------------------
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
 * Define constants.
 */
const DEVELOPER_SHORTNAME = 'azurecurve';
const DEVELOPER_NAME      = DEVELOPER_SHORTNAME . ' | Development';
const DEVELOPER_RAW_LINK  = 'https://development.azurecurve.co.uk/classicpress-plugins/';
const DEVELOPER_LINK      = '<a href="' . DEVELOPER_RAW_LINK . '">' . DEVELOPER_NAME . '</a>';

const PLUGIN_NAME       = 'Taxonomy Order';
const PLUGIN_SHORT_SLUG = 'taxonomy-order';
const PLUGIN_SLUG       = 'azrcrv-' . PLUGIN_SHORT_SLUG;
const PLUGIN_FILE       = __FILE__;

// Retained literally as 'azrcrv-to' / 'azrcrv_to' - this is the same slug the
// pre-2.0.0 plugin used for its admin page, option name, and function prefix.
// Keeping the literal values (rather than deriving them from PLUGIN_SHORT_SLUG)
// means upgrading from 1.3.x keeps working with zero migration for the admin
// page URL or the saved settings row - see get_settings() in
// functions-settings.php for the one-off migration of the *shape* of that row.
const PLUGIN_HYPHEN     = 'azrcrv-to';
const PLUGIN_UNDERSCORE = 'azrcrv_to';

// Option names. Both are literal strings the plugin has always used (or, for
// the second, will always use going forward) so they survive future refactors
// of the constants above without an accidental option-name change.
const SETTINGS_OPTION_NAME      = PLUGIN_HYPHEN;                    // 'azrcrv-to'
const DEFAULT_ORDER_OPTION_NAME = PLUGIN_HYPHEN . '-default-order'; // 'azrcrv-to-default-order'

/**
 * Load the remote update client, which integrates with azurecurve's own
 * Update Manager server so this plugin updates the same way as the rest of
 * the azurecurve plugin family.
 *
 * NOTE: per the upgrade PRD, this file is left completely untouched - it
 * already self-registers via UpdateClient::get_instance() under this same
 * namespace at the bottom of the file, exactly as it did before this
 * upgrade.
 */
require_once dirname( PLUGIN_FILE ) . '/libraries/updateclient/UpdateClient.class.php';

/**
 * Load activation/deactivation functions.
 */
require_once dirname( PLUGIN_FILE ) . '/includes/functions-activation.php';

/**
 * Load discovery of eligible post types and their public taxonomies.
 */
require_once dirname( PLUGIN_FILE ) . '/includes/functions-post-types.php';

/**
 * Load settings (enabled post-type/taxonomy pairs, uninstall preference)
 * functions.
 */
require_once dirname( PLUGIN_FILE ) . '/includes/functions-settings.php';

/**
 * Load default-order (per post-type/taxonomy pair) functions.
 */
require_once dirname( PLUGIN_FILE ) . '/includes/functions-default-order.php';

/**
 * Load the edit-post sidebar metabox (render + save) functions.
 */
require_once dirname( PLUGIN_FILE ) . '/includes/functions-metabox.php';

/**
 * Load the front-end/general term-sorting filter.
 */
require_once dirname( PLUGIN_FILE ) . '/includes/functions-sorting.php';

/**
 * Load admin menu functions.
 */
require_once dirname( PLUGIN_FILE ) . '/includes/functions-menu.php';

/**
 * Load custom plugin icon/banner path functions, used by Update Manager.
 */
require_once dirname( PLUGIN_FILE ) . '/includes/functions-plugin-images.php';

/**
 * Load the shared azurecurve cross-plugin menu: populates this plugin's
 * entry into the shared directory of azurecurve plugins, and (if not
 * already added by another azurecurve plugin on this site) registers the
 * shared top-level "azurecurve" admin menu page that lists them all. This
 * plugin is reachable only via that shared menu, not its own top-level
 * ClassicPress admin menu page.
 */
require_once dirname( PLUGIN_FILE ) . '/includes/azurecurve-menu-populate.php';
require_once dirname( PLUGIN_FILE ) . '/includes/azurecurve-menu-display.php';

/**
 * Load admin script/style enqueue functions.
 */
require_once dirname( PLUGIN_FILE ) . '/includes/functions-scripts.php';

/**
 * Load language functions.
 */
require_once dirname( PLUGIN_FILE ) . '/includes/functions-language.php';

/**
 * Load setup of activation/deactivation hooks, actions and filters. This is
 * required last, since it references functions declared in the files above.
 */
require_once dirname( PLUGIN_FILE ) . '/includes/setup.php';
