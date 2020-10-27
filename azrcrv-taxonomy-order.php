<?php
/**
 * ------------------------------------------------------------------------------
 * Plugin Name: Taxonomy Order
 * Description: Set display order of the category and tag taxonomies of posts.
 * Version: 1.0.0
 * Author: azurecurve
 * Author URI: https://development.azurecurve.co.uk/classicpress-plugins/
 * Plugin URI: https://development.azurecurve.co.uk/classicpress-plugins/azrcrv-taxonomy-order/
 * Text Domain: taxonomy-order
 * Domain Path: /languages
 * ------------------------------------------------------------------------------
 * This is free software released under the terms of the General Public License,
 * version 2, or later. It is distributed WITHOUT ANY WARRANTY; without even the
 * implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. Full
 * text of the license is available at https://www.gnu.org/licenses/gpl-2.0.html.
 * ------------------------------------------------------------------------------
 */

// Prevent direct access.
if (!defined('ABSPATH')){
	die();
}

// include plugin menu
require_once(dirname( __FILE__).'/pluginmenu/menu.php');
add_action('admin_init', 'azrcrv_create_plugin_menu_to');

// include update client
require_once(dirname(__FILE__).'/libraries/updateclient/UpdateClient.class.php');

/**
 * Setup actions, filters and shortcodes.
 *
 * @since 1.0.0
 *
 */
// add actions
add_action('admin_init', 'azrcrv_to_set_default_options');
add_action('admin_menu', 'azrcrv_to_create_admin_menu');
add_action('admin_post_azrcrv_to_save_options', 'azrcrv_to_save_options');
add_action('plugins_loaded', 'azrcrv_to_load_languages');
add_action( 'init', 'azrcrv_to_plugin_register_sorted_taxonomies' );
add_action('admin_menu', 'azrcrv_to_add_sidebar_metabox');
add_action('save_post', 'azrcrv_to_save_sidebar_metabox', 10, 1);

// add filters
add_filter('plugin_action_links', 'azrcrv_to_add_plugin_action_link', 10, 2);
add_filter('codepotent_update_manager_image_path', 'azrcrv_to_custom_image_path');
add_filter('codepotent_update_manager_image_url', 'azrcrv_to_custom_image_url');
add_filter( 'get_the_terms', 'azrcrv_to_plugin_get_the_ordered_terms' , 10, 4 );

/**
 * Load language files.
 *
 * @since 1.0.0
 *
 */
function azrcrv_to_load_languages() {
    $plugin_rel_path = basename(dirname(__FILE__)).'/languages';
    load_plugin_textdomain('taxonomy-order', false, $plugin_rel_path);
}

/**
 * Set default options for plugin.
 *
 * @since 1.0.0
 *
 */
function azrcrv_to_set_default_options($networkwide){
	
	$option_name = 'azrcrv-to';
	
	$new_options = array(
							'enable-category-order' => 0,
							'enable-tag-order' => 1,
							'updated' => strtotime('2020-10-27'),
						);
	
	// set defaults for multi-site
	if (function_exists('is_multisite') && is_multisite()){
		// check if it is a network activation - if so, run the activation function for each blog id
		if ($networkwide){
			global $wpdb;

			$blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
			$original_blog_id = get_current_blog_id();

			foreach ($blog_ids as $blog_id){
				switch_to_blog($blog_id);
				
				azrcrv_to_update_options($option_name, $new_options, false);
			}

			switch_to_blog($original_blog_id);
		}else{
			azrcrv_to_update_options( $option_name, $new_options, false);
		}
		if (get_site_option($option_name) === false){
			azrcrv_to_update_options($option_name, $new_options, true);
		}
	}
	//set defaults for single site
	else{
		azrcrv_to_update_options($option_name, $new_options, false);
	}
}

/**
 * Update options.
 *
 * @since 1.0.0
 *
 */
function azrcrv_to_update_options($option_name, $new_options, $is_network_site){
	if ($is_network_site == true){
		if (get_site_option($option_name) === false){
			add_site_option($option_name, $new_options);
		}else{
			$options = get_site_option($option_name);
			if (!isset($options['updated']) OR $options['updated'] < $new_options['updated'] ){
				$options['updated'] = $new_options['updated'];
				update_site_option($option_name, azrcrv_to_update_default_options($options, $new_options));
			}
		}
	}else{
		if (get_option($option_name) === false){
			add_option($option_name, $new_options);
		}else{
			$options = get_option($option_name);
			if (!isset($options['updated']) OR $options['updated'] < $new_options['updated'] ){
				$options['updated'] = $new_options['updated'];
				update_option($option_name, azrcrv_to_update_default_options($options, $new_options));
			}
		}
	}
}

/**
 * Add default options to existing options.
 *
 * @since 1.0.0
 *
 */
function azrcrv_to_update_default_options( &$default_options, $current_options ) {
    $default_options = (array) $default_options;
    $current_options = (array) $current_options;
    $updated_options = $current_options;
    foreach ($default_options as $key => &$value) {
        if (is_array( $value) && isset( $updated_options[$key])){
            $updated_options[$key] = azrcrv_to_update_default_options($value, $updated_options[$key]);
        } else {
			$updated_options[$key] = $value;
        }
    }
    return $updated_options;
}

/**
 * Add gallery from folder action link on plugins page.
 *
 * @since 1.0.0
 *
 */
function azrcrv_to_add_plugin_action_link($links, $file){
	static $this_plugin;

	if (!$this_plugin){
		$this_plugin = plugin_basename(__FILE__);
	}

	if ($file == $this_plugin){
		$settings_link = '<a href="'.get_bloginfo('wpurl').'/wp-admin/admin.php?page=azrcrv-to"><img src="'.plugins_url('/pluginmenu/images/Favicon-16x16.png', __FILE__).'" style="padding-top: 2px; margin-right: -5px; height: 16px; width: 16px;" alt="azurecurve" />'.esc_html__('Settings' ,'taxonomy-order').'</a>';
		array_unshift($links, $settings_link);
	}

	return $links;
}

/**
 * Add to menu.
 *
 * @since 1.0.0
 *
 */
function azrcrv_to_create_admin_menu(){
	//global $admin_page_hooks;
	
	add_submenu_page("azrcrv-plugin-menu"
						,esc_html__("Taxonomy Order Settings", "taxonomy-order")
						,esc_html__("Taxonomy Order", "taxonomy-order")
						,'manage_options'
						,'azrcrv-to'
						,'azrcrv_to_display_options');
}

/**
 * Custom plugin image path.
 *
 * @since 1.0.0
 *
 */
function azrcrv_to_custom_image_path($path){
    if (strpos($path, 'azrcrv-taxonomy-order') !== false){
        $path = plugin_dir_path(__FILE__).'assets/pluginimages';
    }
    return $path;
}

/**
 * Custom plugin image url.
 *
 * @since 1.0.0
 *
 */
function azrcrv_to_custom_image_url($url){
    if (strpos($url, 'azrcrv-taxonomy-order') !== false){
        $url = plugin_dir_url(__FILE__).'assets/pluginimages';
    }
    return $url;
}

/**
 * Display Settings page.
 *
 * @since 1.0.0
 *
 */
function azrcrv_to_display_options(){
	if (!current_user_can('manage_options')){
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'taxonomy-order'));
    }
	
	// Retrieve plugin configuration options from database
	$options = get_option('azrcrv-to');
	?>
	<div id="azrcrv-n-general" class="wrap">
		<fieldset>
			<h1><?php echo esc_html(get_admin_page_title()); ?></h1>
			<?php if(isset($_GET['settings-updated'])){ ?>
				<div class="notice notice-success is-dismissible">
					<p><strong><?php esc_html_e('Settings have been saved.', 'taxonomy-order'); ?></strong></p>
				</div>
			<?php } ?>
			<form method="post" action="admin-post.php">
				<input type="hidden" name="action" value="azrcrv_to_save_options" />
				<input name="page_options" type="hidden" value="copyright" />
				
				<!-- Adding security through hidden referrer field -->
				<?php wp_nonce_field('azrcrv-to', 'azrcrv-to-nonce'); ?>
				<table class="form-table">
				
					<tr><td colspan="2">
						<?php _e('<p>Taxonomy Order allows the display order of post categories and tags to be amended through the Edit Post page.</p>', 'taxonomy-order'); ?>
					</td></tr>
					
					<tr>
						<th scope="row">
							<label for="enable-category-order"><?php esc_html_e('Enable Category Order', 'taxonomy-order'); ?></label></th>
						<td>
							<label for="enable-category-order"><input name="enable-category-order" type="checkbox" id="enable-category-order" value="1" <?php checked('1', $options['enable-category-order']); ?> /><?php _e('Allow users to set display order for categories.', 'taxonomy-order'); ?></label>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="enable-tag-order"><?php esc_html_e('Enable Tag Order', 'taxonomy-order'); ?></label></th>
						<td>
							<label for="enable-tag-order"><input name="enable-tag-order" type="checkbox" id="enable-tag-order" value="1" <?php checked('1', $options['enable-tag-order']); ?> /><?php _e('Allow users to set display order for tags.', 'taxonomy-order'); ?></label>
						</td>
					</tr>
				
				</table>
				<input type="submit" value="Save Changes" class="button-primary"/>
			</form>
		</fieldset>
	</div>
	<?php
}

/**
 * Save settings.
 *
 * @since 1.0.0
 *
 */
function azrcrv_to_save_options(){
	// Check that user has proper security level
	if (!current_user_can('manage_options')){
		wp_die(esc_html__('You do not have permissions to perform this action', 'taxonomy-order'));
	}
	// Check that nonce field created in configuration form is present
	if (! empty($_POST) && check_admin_referer('azrcrv-to', 'azrcrv-to-nonce')){
	
		// Retrieve original plugin options array
		$options = get_option('azrcrv-to');
		
		$option_name = 'enable-category-order';
		if (isset($_POST[$option_name])){
			$options[$option_name] = 1;
		}else{
			$options[$option_name] = 0;
		}
		
		$option_name = 'enable-tag-order';
		if (isset($_POST[$option_name])){
			$options[$option_name] = 1;
		}else{
			$options[$option_name] = 0;
		}
		
		// Store updated options array to database
		update_option('azrcrv-to', $options);
		
		// Redirect the page to the configuration form that was processed
		wp_redirect(add_query_arg('page', 'azrcrv-to&settings-updated', admin_url('admin.php')));
		exit;
	}
}

/**
 * Add post metabox to sidebar for tags.
 *
 * @since 1.0.0
 *
 */
function azrcrv_to_add_sidebar_metabox(){
	
	$options = get_option('azrcrv-to');
	
	if ($options['enable-category-order'] == 1){
		add_meta_box('azrcrv-to-box-categories', esc_html__('Category Order', 'taxonomy-order'), 'azrcrv_to_generate_sidebar_metabox_categories', array('post'), 'side', 'default');
	}
	if ($options['enable-tag-order'] == 1){
		add_meta_box('azrcrv-to-box-tags', esc_html__('Tag Order', 'taxonomy-order'), 'azrcrv_to_generate_sidebar_metabox_tags', array('post'), 'side', 'default');
	}
}

/**
 * Generate post sidebar metabox for categories.
 *
 * @since 1.0.0
 *
 */
function azrcrv_to_generate_sidebar_metabox_categories(){
	
	global $post;
	
	$options = get_option('azrcrv-to');
	?>
	<p class="autopost">
		<?php
			wp_nonce_field(basename(__FILE__), 'azrcrv-to-nonce');
			
			$categories = wp_get_object_terms($post->ID, 'category', array('orderby' => 'term_order'));
			if ($categories) {
				echo '<table style="width: 100%; ">';
				$category_count = 0;
				foreach($categories as $category) {
					$category_count++;
					echo '<tr><td>'.$category->name.'</td><td><input name="category['.$category->term_id.']" type="number" step="1" min="0" id="sort-order" value="'.$category_count.'" class="small-text" /></td></tr>'; 
				}
				echo '</table>';
			}
		?>
		
	</p>
	
<?
}

/**
 * Generate post sidebar metabox for tags.
 *
 * @since 1.0.0
 *
 */
function azrcrv_to_generate_sidebar_metabox_tags(){
	
	global $post;
	
	$options = get_option('azrcrv-to');
	?>
	<p class="autopost">
		<?php
			wp_nonce_field(basename(__FILE__), 'azrcrv-to-nonce');
			
			$post_tags = get_the_tags();
			$tags = wp_get_object_terms($post->ID, 'post_tag', array( 'orderby' => 'name'));
			if ($tags) {
				echo '<table style="width: 100%; ">';
				$tag_count = 0;
				foreach($tags as $tag) {
					$tag_count++;
					echo '<tr><td>'.$tag->name.'</td><td><input name="tag['.$tag->term_id.']" type="number" step="1" min="0" id="sort-order" value="'.$tag_count.'" class="small-text" /></td></tr>'; 
				}
				echo '</table>';
			}
		?>
		
	</p>
	
<?
}

/**
 * Save sidebar metabox of category and tag order.
 *
 * @since 1.0.0
 *
 */
function azrcrv_to_save_sidebar_metabox($post_id){
	
	global $wpdb;
	
	if(! isset($_POST[ 'azrcrv-to-nonce' ]) || ! wp_verify_nonce($_POST[ 'azrcrv-to-nonce' ], basename(__FILE__))){
		return $post_id;
	}
	
	if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE){
		return $post_id;
	}
	
	if(! current_user_can('edit_post', $post_id)){
		return $post_id;
	}
	
	$post_type = get_post_type( $post_ID );
	
    if ($post_type == 'post') {
		
		$options = get_option('azrcrv-to');
		
		//categories
		if ($options['enable-category-order'] == 1){
			$option_name = 'category';
			if (isset($_POST[$option_name])){
				$table_name = $wpdb->prefix.'term_relationships';
				foreach ($_POST[$option_name] as $key => $value){
					$wpdb->update(
						$table_name
						,array(
							'term_order' => $value,
						)
						,array(
							'object_id' => $post_id,
							'term_taxonomy_id' => $key,
						)
						,array(
							'%d',
						)
						,array(
							'%d',
						)
					);
				}
			}
		}
		//tags
		if ($options['enable-tag-order'] == 1){
			$option_name = 'tag';
			if (isset($_POST[$option_name])){
				$table_name = $wpdb->prefix.'term_relationships';
				foreach ($_POST[$option_name] as $key => $value){
					$wpdb->update(
						$table_name
						,array(
							'term_order' => $value,
						)
						,array(
							'object_id' => $post_id,
							'term_taxonomy_id' => $key,
						)
						,array(
							'%d',
						)
						,array(
							'%d',
						)
					);
				}
			}
		}
	}
	
	return esc_attr($_POST[ 'autopost' ]);
}

/**
 * Sort post_tags by term_order
 *
 * @since 1.0.0
 *
 * @param array $terms array of objects to be replaced with sorted list
 * @param integer $id post id
 * @param string $taxonomy only 'post_tag' is changed.
 * @return array of objects
 */
function azrcrv_to_plugin_get_the_ordered_terms($terms, $id, $taxonomy){
	if ('post_tag' != $taxonomy AND 'category' != $taxonomy ) // only ordering tags and categories for now but could add other taxonomies here.
		return $terms;

	$terms = wp_cache_get($id, "{$taxonomy}_relationships_sorted");
	if ( false === $terms ) {
		$terms = wp_get_object_terms($id, $taxonomy, array( 'orderby' => 'term_order'));
		wp_cache_add($id, $terms, $taxonomy . '_relationships_sorted');
	}

	return $terms;
}

/**
 * Adds sorting by term_order to post_tag by doing a partial register replacing
 * the default
 */
function azrcrv_to_plugin_register_sorted_taxonomies(){
	register_taxonomy('post_tag', 'post', array('sort' => true, 'args' => array('orderby' => 'term_order')));
	//register_taxonomy('post_category', 'post', array('sort' => true, 'args' => array('orderby' => 'term_order')));
}
