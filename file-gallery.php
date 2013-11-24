<?php
/*
Plugin Name: File Gallery
Plugin URI: http://skyphe.org/code/wordpress/file-gallery/
Version: 1.7.8
Description: "File Gallery" extends WordPress' media (attachments) capabilities by adding a new gallery shortcode handler with templating support, a new interface for attachment handling when editing posts, and much more.
Author: Bruno "Aesqe" Babic
Author URI: http://skyphe.org

////////////////////////////////////////////////////////////////////////////

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
	
////////////////////////////////////////////////////////////////////////////

*/


/**
 * Setup default File Gallery options
 */

define('FILE_GALLERY_VERSION', '1.7.8');
define('FILE_GALLERY_DEFAULT_TEMPLATES', serialize( array('default', 'file-gallery', 'list', 'simple') ) );


/**
 * Just a variables placeholder for now
 *
 * @since 1.6.5.1
 */
class File_Gallery
{
	/**
	 * settings, 
	 * their default values, 
	 * and false default values
	 *
	 * @since 1.7
	 */
	var $settings = array();
	var $defaults = array();
	var $false_defaults = array();
	var $debug = array();
	
	/**
	 * Holds the ID number of current post's gallery
	 */
	var $gallery_id;

	/**
	 * Holds gallery options overriden 
	 * via 'file_gallery_overrides' template function
	 */
	var $overrides;
	
	/**
	 * Whether SSL is on for wp-admin
	 */
	var $ssl_admin = false;

	/**
	 * Current version of this plugin
	 */
	var $version = FILE_GALLERY_VERSION;
	
	/***/
	var $admin_thickbox_enqueued = false;

	/***/
	function __construct()
	{
		$this->File_Gallery();
	}

	/***/
	function File_Gallery()
	{
	}
	
	
	function debug_add( $section = 'default', $vars )
	{
		if( ! FILE_GALLERY_DEBUG )
			return;
		
		foreach( $vars as $k => $v )
		{
			$type = gettype($v);
			
			if( 'boolean' === $type )
				$v = false === $v ? 'false' : 'true';
			
			$this->debug[$section][$k] = $v;
		}
	}
	
	
	function debug_print()
	{
		if( ! FILE_GALLERY_DEBUG )
			return;

		$vars = get_object_vars($this);
		
		unset($vars['defaults']);
		unset($vars['false_defaults']);
		unset($vars['settings']);
		unset($vars['ssl_admin']);
		unset($vars['admin_thickbox_enqueued']);

		function block($a)
		{
			$out = '<ul>';
			foreach($a as $k => $v)
			{
				$out .= '<li><strong>' . $k . '</strong> => ';
				$out .= is_array($v) || is_object($v) ? block($v) : (empty($v) ? '""' : $v);
				$out .= '</li>' . "\n";
			}
			$out .= '</ul>' . "\n";
			
			return $out;
		}
		
		return '
		<style scoped="scoped">
			#querylist ul ul
			{
				margin-left: 30px;
			}
		</style>
		<h3 style="font-family: georgia,times,serif; font-size: 22px; margin: 15px 10px 15px 0;">File Gallery debug</h3>
		' . block($vars);
	}
};

function file_gallery_debug_print( $panels )
{
	class Debug_Bar_File_Gallery extends Debug_Bar_Panel
	{
		function init()
		{
			$this->title( __('File Gallery', 'debug-bar') );
		}
	
		function render()
		{
			global $file_gallery;
			echo $file_gallery->debug_print();
		}
	}

	$panels[] = new Debug_Bar_File_Gallery();
	
	return $panels;
}
add_action('debug_bar_panels', 'file_gallery_debug_print');



// Begin
$file_gallery = new File_Gallery();


/**
 * 
 * @since 1.7
 */
function file_gallery_do_settings()
{
	global $file_gallery;
	
	if( ! isset($file_gallery) )
		$file_gallery = new File_Gallery();

	$file_gallery->settings = array(
			'disable_shortcode_handler' => array(
				'default' => 0, 
				'display' => true,
				'title' => __("Disable 'File Gallery' handling of [gallery] shortcode?", 'file-gallery'),
				'type' => 'checkbox',
				'section' => 0,
				'position' => 0
			),
			'show_on_post_type' => array(
				'default' => 1, 
				'display' => true,
				'title' => __('Display File Gallery on which post types?', 'file-gallery'),
				'type' => 'checkbox',
				'values' => file_gallery_post_type_checkboxes(),
				'section' => 0,
				'position' => 0
			),
			'alt_color_scheme' => array(
				'default' => 1, 
				'display' => true,
				'title' => __('Use alternative color scheme (a bit more contrast)?', 'file-gallery'),
				'type' => 'checkbox',
				'section' => 0,
				'position' => 0
			),
			'pagination_count' => array(
				'default' => 9, 
				'display' => true,
				'title' => __('How many page links should be shown in pagination?', 'file-gallery'),
				'type' => 'number',
				'section' => 0,
				'position' => 0
			),
			'auto_enqueued_scripts' => array(
				'default' => 'thickbox', 
				'display' => true,
				'title' => __('Auto enqueue lightbox scripts for which link classes (separate with commas)?', 'file-gallery'),
				'type' => 'text',
				'section' => 0,
				'position' => 0
			),
			'default_metabox_image_size' => array(
				'default' => 'thumbnail', 
				'display' => true,
				'title' => __('Default WordPress image size for thumbnails in File Gallery metabox on post editing screens?', 'file-gallery'),
				'type' => 'select',
				'values' => file_gallery_dropdown( 'default_metabox_image_size', 'image_size' ),
				'section' => 0,
				'position' => 0
			),
			'default_metabox_image_width' => array(
				'default' => 75, 
				'display' => true,
				'title' => __('Default width (in pixels) for thumbnails in File Gallery metabox on post editing screens?', 'file-gallery'),
				'type' => 'number',
				'section' => 0,
				'position' => 0
			),
			
			
			'default_image_size' => array(
				'default' => 'thumbnail',
				'display' => true,
				'title' => '</th></tr><tr><td colspan="2"><strong style="display: block; margin-top: -15px; font-size: 115%; color: #21759B;">' . __('Some default values for when inserting a gallery into a post', 'file-gallery') . '...</strong></td></tr><tr><td colspan="2"><p id="file-gallery-media-settings-notice" style="margin: 0; background-color: #FFFFE8; border-color: #EEEED0; -moz-border-radius: 3px; -webkit-border-radius: 3px; border-radius: 3px; border-style: solid; border-width: 1px; padding: 0.6em;">' . sprintf(__('The following two blocks of options <strong>do not</strong> affect the output/display of your galleries - they are here only so you could set default values for File Gallery metabox on post editing screen. <a href="%s/help/index.html#settings_page" target="_blank">More information is available in the help file</a>.', "file-gallery"), FILE_GALLERY_URL) . '</p></td></tr><tr valign="top"><th scope="row">' . __('size', 'file-gallery'),
				'type' => 'select',
				'values' => file_gallery_dropdown( 'default_image_size', 'image_size' ),
				'section' => 0,
				'position' => 0
			), 
			'default_linkto' => array(
				'default' => 'attachment', 
				'display' => true,
				'title' => __('link', 'file-gallery'),
				'type' => 'select',
				'values' => file_gallery_dropdown( 'default_linkto', 'linkto' ),
				'section' => 0,
				'position' => 0
			),
			'default_linked_image_size' => array(
				'default' => 'full', 
				'display' => true,
				'title' => __('linked image size', 'file-gallery'),
				'type' => 'select',
				'values' => file_gallery_dropdown( 'default_linked_image_size', 'image_size' ),
				'section' => 0,
				'position' => 0
			),
			'default_external_url' => array(
				'default' => '',  
				'display' => true,
				'title' => __('external url', 'file-gallery'),
				'type' => 'text',
				'section' => 0,
				'position' => 0
			),
			'default_orderby' => array(
				'default' => '',  
				'display' => true,
				'title' => __('order by', 'file-gallery'),
				'type' => 'select',
				'values' => file_gallery_dropdown( 'default_orderby', 'orderby' ),
				'section' => 0,
				'position' => 0
			),
			'default_order' => array(
				'default' => 'ASC',  
				'display' => true,
				'title' => __('order', 'file-gallery'),
				'type' => 'select',
				'values' => file_gallery_dropdown( 'default_order', 'order' ),
				'section' => 0,
				'position' => 0
			),
			'default_template' => array(
				'default' => 'default',  
				'display' => true,
				'title' => __('template', 'file-gallery'),
				'type' => 'select',
				'values' => file_gallery_dropdown( 'default_template', 'template' ),
				'section' => 0,
				'position' => 0
			),
			'default_linkclass' => array(
				'default' => '',  
				'display' => true,
				'title' => __('link class', 'file-gallery'),
				'type' => 'text',
				'section' => 0,
				'position' => 0
			),
			'default_imageclass' => array(
				'default' => '',  
				'display' => true,
				'title' => __('image class', 'file-gallery'),
				'type' => 'text',
				'section' => 0,
				'position' => 0
			),
			'default_columns' => array(
				'default' => 3,  
				'display' => true,
				'title' => __('columns', 'file-gallery'),
				'type' => 'select',
				'values' => file_gallery_dropdown( 'default_columns', 'columns' ),
				'section' => 0,
				'position' => 0
			),
			'default_mimetype' => array(
				'default' => '', 
				'display' => true,
				'title' => __('mime type', 'file-gallery'),
				'type' => 'text',
				'section' => 0,
				'position' => 0
			),
			'default_galleryclass' => array(
				'default' => '', 
				'display' => true,
				'title' => __('gallery class', 'file-gallery'),
				'type' => 'text',
				'section' => 0,
				'position' => 0
			),
			
			
			'single_default_image_size' => array(
				'default' => 'thumbnail',  
				'display' => true,
				'title' => '</th></tr><tr><td colspan="2"><strong style="display: block; margin-top: -15px; font-size: 115%; color: #21759B;">' . __('...and for when inserting (a) single image(s) into a post', 'file-gallery') . '</strong></td></tr><tr valign="top"><th scope="row">' . __('size', 'file-gallery'),
				'type' => 'select',
				'values' => file_gallery_dropdown( 'single_default_image_size', 'image_size' ),
				'section' => 0,
				'position' => 0
			),
			'single_default_linkto' => array(
				'default' => 'attachment',  
				'display' => true,
				'title' => __('link', 'file-gallery'),
				'type' => 'select',
				'values' => file_gallery_dropdown( 'single_default_linkto', 'linkto' ),
				'section' => 0,
				'position' => 0
			),
			'single_default_external_url' => array(
				'default' => '',  
				'display' => true,
				'title' => __('external url', 'file-gallery'),
				'type' => 'text',
				'section' => 0,
				'position' => 0
			),
			'single_default_linkclass' => array(
				'default' => '',  
				'display' => true,
				'title' => __('link class', 'file-gallery'),
				'type' => 'text',
				'section' => 0,
				'position' => 0
			),
			'single_default_imageclass' => array(
				'default' => '', 
				'display' => true,
				'title' => __('image class', 'file-gallery'),
				'type' => 'text',
				'section' => 0,
				'position' => 0
			),
			'single_default_align' => array(
				'default' => 'none', 
				'display' => true,
				'title' => __('alignment', 'file-gallery'),
				'type' => 'select',
				'values' => file_gallery_dropdown( 'single_default_align', 'align' ),
				'section' => 0,
				'position' => 0
			),
			
			
			'cache' => array(
				'default' => 0, 
				'display' => true,
				'title' => '</th></tr><tr><td colspan="2"><strong style="display: block; margin-top: -15px; font-size: 115%; color: #21759B;">' . __('Cache', 'file-gallery') . '</strong></td></tr><tr valign="top"><th scope="row">' . __('Enable caching?', 'file-gallery'),
				'type' => 'checkbox',
				'section' => 0,
				'position' => 0
			),
			'cache_time' => array(
				'default' => 3600, // == 1 hour 
				'display' => true,
				'title' => __("Cache expires after how many seconds? (leave as is if you don't know what it means)", 'file-gallery'),
				'type' => 'number',
				'section' => 0,
				'position' => 0
			),
			'cache_non_html_output' => array(
				'default' => 0, 
				'display' => true,
				'title' => __('Cache non-HTML gallery output (<em>array, object, json</em>)', 'file-gallery'),
				'type' => 'checkbox',
				'section' => 0,
				'position' => 0
			),
			
			
			'e_display_attachment_count' => array(
				'default' => 1, 
				'display' => true,
				'title' => '</th></tr><tr><td colspan="2"><strong style="display: block; margin-top: -15px; font-size: 115%; color: #21759B;">' . __('Edit screens options', 'file-gallery') . '</strong></td></tr><tr valign="top"><th scope="row">' . __('Display attachment count?', 'file-gallery'),
				'type' => 'checkbox',
				'section' => 0,
				'position' => 0
			),
			'library_filter_duplicates' => array(
				'default' => 1, 
				'display' => true,
				'title' => __('Filter out duplicate attachments (copies) when browsing media library?', 'file-gallery'),
				'type' => 'checkbox',
				'section' => 0,
				'position' => 0
			),
			'e_display_media_tags' => array(
				'default' => 1, 
				'display' => true,
				'title' => __('Display media tags for attachments in media library?', 'file-gallery'),
				'type' => 'checkbox',
				'section' => 0,
				'position' => 0
			),
			'e_display_post_thumb' => array(
				'default' => 1, 
				'display' => true,
				'title' => __('Display post thumb (if set)?', 'file-gallery'),
				'type' => 'checkbox',
				'section' => 0,
				'position' => 0
			),

		
			'in_excerpt' => array(
				'default' => 1,
				'display' => true,
				'title' => '</th></tr><tr><td colspan="2"><strong style="display: block; margin-top: -15px; font-size: 115%; color: #21759B;">' . __('Other options', 'file-gallery') . '</strong></td></tr><tr valign="top"><th scope="row">' . __('Display galleries within post excerpts?', 'file-gallery'),
				'type' => 'checkbox',
				'section' => 0,
				'position' => 0
			), 
			'in_excerpt_replace_content' => array(
				'default' => '<p><strong>(' . __('galleries are shown on full posts only', 'file-gallery') . ')</strong></p>',
				'display' => true,
				'title' => __("Replacement text for galleries within post excerpts (if you haven't checked the above option)", 'file-gallery'),
				'type' => 'textarea',
				'section' => 0,
				'position' => 0
			), 
			'display_gallery_fieldset' => array(
				'default' => 1, 
				'display' => true,
				'title' => __('Display options for inserting galleries into a post?', 'file-gallery'),
				'type' => 'checkbox',
				'section' => 0,
				'position' => 0
			),
			'display_single_fieldset' => array(
				'default' => 1, 
				'display' => true,
				'title' => __('Display options for inserting single images into a post?', 'file-gallery'),
				'type' => 'checkbox',
				'section' => 0,
				'position' => 0
			),
			'display_acf' => array(
				'default' => 1, 
				'display' => true,
				'title' => __('Display attachment custom fields?', 'file-gallery'),
				'type' => 'checkbox',
				'section' => 0,
				'position' => 0
			),
			'insert_gallery_button' => array(
				'default' => 1, 
				'display' => true,
				'title' => __("Display 'insert gallery' button even if gallery options are hidden?", 'file-gallery'),
				'type' => 'checkbox',
				'section' => 0,
				'position' => 0
			),
			'insert_single_button' => array(
				'default' => 1, 
				'display' => true,
				'title' => __("Display 'insert single image(s)' button even if single image options are hidden?", 'file-gallery'),
				'type' => 'checkbox',
				'section' => 0,
				'position' => 0
			),	
			'del_options_on_deactivate' => array(
				'default' => 0, 
				'display' => true,
				'title' => __('Delete all options on deactivation?', 'file-gallery'),
				'type' => 'checkbox',
				'section' => 0,
				'position' => 0
			),
			
			
			/**
			 * Disabled options
			 */
			'version' => array(
				'default' => FILE_GALLERY_VERSION,
				'display' => 'disabled',
				'title' => __('File Gallery version', 'file-gallery'),
				'type' => 'text',
				'section' => 0,
				'position' => 0
			),
			'folder' => array(
				'default' => file_gallery_https( FILE_GALLERY_URL ),
				'display' => 'disabled',
				'title' => __('File Gallery folder', 'file-gallery'),
				'type' => 'text',
				'section' => 0,
				'position' => 0
			), 
			'abspath' => array(
				'default' => FILE_GALLERY_ABSPATH,
				'display' => 'disabled',
				'title' => __('File Gallery path', 'file-gallery'),
				'type' => 'text',
				'section' => 0,
				'position' => 100
			),
			'media_tag_taxonomy_name' => array(
				'default' => 'media_tag',
				'display' => 'disabled',
				'title' => __('Media tags Taxonomy name', 'file-gallery') . ' <em>(<a href="' . admin_url('options-permalink.php') . '" style="text-transform: lowercase;">' . __('Edit') . '</a>)</em>',
				'type' => 'text',
				'section' => 0,
				'position' => 0
			),
			'media_tag_taxonomy_slug' => array(
				'default' => 'media-tag',
				'display' => 'disabled',
				'title' => __('Media tags URL slug', 'file-gallery') . ' <em>(<a href="' . admin_url('options-permalink.php') . '" style="text-transform: lowercase;">' . __('Edit') . '</a>)</em>',
				'type' => 'text',
				'section' => 0,
				'position' => 0
			),

			
			/**
			 * Hidden options
			 */
			'insert_options_state' => array(
				'default' => 1, 
				'display' => true,
				'title' => __('Gallery insert options state', 'file-gallery'),
				'type' => 'checkbox',
				'section' => 0,
				'position' => 0
			),
			'insert_single_options_state' => array(
				'default' => 1, 
				'display' => true,
				'title' => __('Single images insert options state', 'file-gallery'),
				'type' => 'checkbox',
				'section' => 0,
				'position' => 0
			),
			'acf_state' => array(
				'default' => 1, 
				'display' => true,
				'title' => __('Attachment custom fields state', 'file-gallery'),
				'type' => 'checkbox',
				'section' => 0,
				'position' => 0
			),
			'textual_mode' => array(
				'default' => 0, 
				'display' => true,
				'title' => __('"Textual" mode', 'file-gallery'),
				'type' => 'checkbox',
				'section' => 0,
				'position' => 0
			)
		);
	
	foreach( $file_gallery->settings as $key => $val )
	{
		$file_gallery->defaults[$key] = $val['default'];
		
		if( is_bool($val['default']) || 1 === $val['default'] || 0 === $val['default'] )
			$file_gallery->false_defaults[$key] = 0;
	}
}



/* ACTIVATION, DEACTIVATION, MULTISITE */

// many thanks to shiba for writing a multisite tutorial:
// http://shibashake.com/wordpress-theme/write-a-plugin-for-wordpress-multi-site
function file_gallery_activate( $networkwide )
{
	global $wpdb;
	
	if( function_exists('is_multisite') && is_multisite() )
	{
		// check if it is a network activation - if so, run the activation function for each blog id
		if( $networkwide )
		{
			// Get all blog ids
			$blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");

			foreach( $blogids as $blog_id )
			{
				switch_to_blog($blog_id);
				_file_gallery_activate();
			}
			
			switch_to_blog($wpdb->blogid);
			return;
		}
	}
	
	_file_gallery_activate();		
}
register_activation_hook( __FILE__, 'file_gallery_activate' );

/**
 * Registers default File Gallery options when plugin is activated
 */
function _file_gallery_activate()
{
	global $file_gallery;

	file_gallery_plugins_loaded();
	file_gallery_after_setup_theme();
	file_gallery_do_settings();
	
	$defaults = $file_gallery->defaults;

	// if options already exist, upgrade
	if( $options = get_option('file_gallery') )
	{
		// preserve display options when upgrading from below 1.6.5.3
		if( ! isset($options['display_acf']) )
		{
			if( isset($options['insert_options_states']) )
				$states = explode(',', $options['insert_options_states']);
			else
				$states = array('1', '1');
			
			if( isset($options['display_insert_fieldsets']) )
				$display = $options['display_insert_fieldsets'];
			else
				$display = 1;
	
			$defaults['insert_options_state'] = (int) $states[0];
			$defaults['insert_single_options_state'] = (int) $states[1];
			$defaults['acf_state'] = 1;
		
			$defaults['display_gallery_fieldset'] = $display;
			$defaults['display_single_fieldset'] = $display;
			$defaults['display_acf'] = 1;
		}

		$defaults = file_gallery_parse_args( $options, $defaults);
		$defaults['folder']  = file_gallery_https( FILE_GALLERY_URL );
		$defaults['abspath'] = FILE_GALLERY_ABSPATH;
		$defaults['version'] = FILE_GALLERY_VERSION;
	}
	else // Fresh installation, show on posts and pages
	{
		$defaults['show_on_post_type_post'] = 1;
		$defaults['show_on_post_type_page'] = 1;
	}
	
	update_option('file_gallery', $defaults);
	
	// clear any existing cache
	file_gallery_clear_cache();
}

/**
 * Do activation procedure on plugin upgrade
 */
function file_gallery_upgrade()
{
	$options = get_option('file_gallery');
	
	if( isset($options['version']) && version_compare( $options['version'], FILE_GALLERY_VERSION, '<') )
	{
		$networkwide = is_plugin_active_for_network(basename(dirname(__FILE__)) . '/' . basename(__FILE__));
		file_gallery_activate( $networkwide );
	}
}
add_action( 'admin_init', 'file_gallery_upgrade' );


/**
 * Some cleanup on deactivation
 */
function file_gallery_deactivate( $networkwide )
{
	global $wpdb;
	
	if( function_exists('is_multisite') && is_multisite() )
	{
		// check if it is a network activation - if so, run the activation function for each blog id
		if( $networkwide )
		{
			// Get all blog ids
			$blogids = $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM $wpdb->blogs"));
			
			foreach ($blogids as $blog_id)
			{
				switch_to_blog($blog_id);
				_file_gallery_deactivate();
			}
	
			switch_to_blog($wpdb->blogid);
			return;
		}
	}
	
	_file_gallery_deactivate();		
}
register_deactivation_hook( __FILE__, 'file_gallery_deactivate' );

function _file_gallery_deactivate()
{
	file_gallery_clear_cache();
	
	$options = get_option('file_gallery');
	
	if( isset($options['del_options_on_deactivate']) && true == $options['del_options_on_deactivate'] )
		delete_option('file_gallery');
}

// activate on new ms blog automatically if it's active for the whole network
function file_gallery_wpmu_new_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta )
{
	global $wpdb;
	
	if( is_plugin_active_for_network(basename(dirname(__FILE__)) . '/' . basename(__FILE__)) )
	{
		switch_to_blog($blog_id);
		_file_gallery_activate();
		switch_to_blog($wpdb->blogid);
	}
}
add_action( 'wpmu_new_blog', 'file_gallery_wpmu_new_blog', 10, 6); 

/* / A / D / MS */



/**
 * Support for other plugins
 *
 * Supported so far:
 * - WordPress Mobile Edition
 * - Media Tags
 */
function file_gallery_plugins_loaded()
{
	$file_gallery_abspath = WP_PLUGIN_DIR . '/' . basename(dirname(__FILE__));
	$file_gallery_abspath = str_replace('\\', '/', $file_gallery_abspath);
	$file_gallery_abspath = preg_replace('#/+#', '/', $file_gallery_abspath);
	
	if( ! defined('FILE_GALLERY_URL') )
	{
		// file gallery directories and template names
		define('FILE_GALLERY_URL', WP_PLUGIN_URL . '/' . basename( dirname(__FILE__) ));
		define('FILE_GALLERY_ABSPATH', $file_gallery_abspath);
	}
	
	$mobile = false;
	$options = get_option('file_gallery');
	
	// WordPress Mobile Edition
	if( function_exists('cfmobi_check_mobile') && cfmobi_check_mobile() )
	{
		$mobile = true;
	
		if( ! isset($options['disable_shortcode_handler']) || true != $options['disable_shortcode_handler'] )
			add_filter('stylesheet_uri', 'file_gallery_mobile_css');
	}

	if( ! defined('FILE_GALLERY_MOBILE') ) {
		define('FILE_GALLERY_MOBILE', $mobile);
	}
	
	file_gallery_media_tags_get_taxonomy_slug();
}
add_action('plugins_loaded', 'file_gallery_plugins_loaded', 100);


/*
 * Some constants you can filter even with your theme's functions.php file
 *
 * @since 1.6.3
 */
function file_gallery_after_setup_theme()
{
	$stylesheet_directory = get_stylesheet_directory();
	$file_gallery_theme_abspath = str_replace('\\', '/', $stylesheet_directory);
	$file_gallery_theme_abspath = preg_replace('#/+#', '/', $file_gallery_theme_abspath);

	if( ! defined('FILE_GALLERY_THEME_ABSPATH') )
	{
		define( 'FILE_GALLERY_THEME_ABSPATH', $file_gallery_theme_abspath );
		define( 'FILE_GALLERY_THEME_TEMPLATES_ABSPATH', apply_filters('file_gallery_templates_folder_abspath', FILE_GALLERY_THEME_ABSPATH . '/file-gallery-templates') ) ;
		define( 'FILE_GALLERY_THEME_TEMPLATES_URL', apply_filters('file_gallery_templates_folder_url', get_bloginfo('stylesheet_directory') . '/file-gallery-templates') );
		
		define( 'FILE_GALLERY_CONTENT_TEMPLATES_ABSPATH', apply_filters('file_gallery_content_templates_folder_abspath', WP_CONTENT_DIR . '/file-gallery-templates') );
		define( 'FILE_GALLERY_CONTENT_TEMPLATES_URL', apply_filters('file_gallery_content_templates_folder_url', WP_CONTENT_URL . '/file-gallery-templates') );
		
		define( 'FILE_GALLERY_DEFAULT_TEMPLATE_URL', apply_filters('file_gallery_default_template_url', FILE_GALLERY_URL . '/templates/default') );
		define( 'FILE_GALLERY_DEFAULT_TEMPLATE_ABSPATH', apply_filters('file_gallery_default_template_abspath', FILE_GALLERY_ABSPATH . '/templates/default') );
		define( 'FILE_GALLERY_DEFAULT_TEMPLATE_NAME', apply_filters('file_gallery_default_template_name', 'default') );
	}

	// display debug information
	if( ! defined( 'FILE_GALLERY_DEBUG' ) ) {
		define( 'FILE_GALLERY_DEBUG', false );
	}
}
add_action('after_setup_theme', 'file_gallery_after_setup_theme');


/**
 * Adds a link to plugin's settings and help pages (shows up next to the 
 * deactivation link on the plugins management page)
 */
function file_gallery_plugin_action_links( $links )
{ 
	array_unshift( $links, '<a href="options-media.php">' . __('Settings', 'file-gallery') . '</a>' );
	array_unshift( $links, '<a href="' . FILE_GALLERY_URL . '/help/index.html" target="_blank">' . __('Help', 'file-gallery') . '</a>' );
	
	return $links; 
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'file_gallery_plugin_action_links' );


function file_gallery_filter_plugin_row_meta( $plugin_meta, $plugin_file, $plugin_data, $status )
{
	if( 'File Gallery' == $plugin_data['Name'] && is_plugin_active($plugin_file))
	{
		array_push($plugin_meta, '<span style="padding: 2px 4px; background: #FFFFEE; color: #777777; border: 1px solid #EEDDCC; border-radius: 3px; border-radius: 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px; -ms-border-radius: 3px; -o-border-radius: 3px;">Visit <a href="http://wordpress.org/extend/plugins/file-gallery/">plugin page</a> or <a href="http://wordpress.org/support/plugin/file-gallery">plugin support forums</a> on WordPress.org</a></span>');
	}
	
	return $plugin_meta;
}
add_filter( 'plugin_row_meta', 'file_gallery_filter_plugin_row_meta', 10, 4 );


/**
 * Adds media_tags taxonomy so we can tag attachments
 */
function file_gallery_add_textdomain_and_taxonomies()
{
	global $mediatags;

	load_plugin_textdomain('file-gallery', false, dirname(plugin_basename(__FILE__)) . '/languages');

	if( ! (isset($mediatags) && is_a($mediatags, 'MediaTags') && defined('MEDIA_TAGS_TAXONOMY')) )
	{
		$args = array(
			'public' => true,
			'show_ui' => true,
			'update_count_callback' => 'file_gallery_update_media_tag_term_count',
			'rewrite' => array(
				'slug' => FILE_GALLERY_MEDIA_TAG_SLUG
			),
			'labels' => array(
				'name'           => __('Media tags', 'file-gallery'),
				'singular_label' => __('Media tag', 'file-gallery')
			)
		);
		
		register_taxonomy( FILE_GALLERY_MEDIA_TAG_NAME, 'attachment', $args );
	}
	
	if( true == get_option('file_gallery_flush_rewrite_rules') )
	{
		global $wp_rewrite;
		$wp_rewrite->flush_rules( false );

		delete_option('file_gallery_flush_rewrite_rules');
	}
}
add_action('init', 'file_gallery_add_textdomain_and_taxonomies', 100);


/**
 * A slightly modified copy of WordPress' _update_post_term_count function
 * 
 * Updates number of posts that use a certain media_tag
 */
function file_gallery_update_media_tag_term_count( $terms )
{
	global $wpdb;

	foreach ( (array) $terms as $term )
	{
		$count = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM $wpdb->term_relationships, $wpdb->posts 
						 WHERE $wpdb->posts.ID = $wpdb->term_relationships.object_id 
						 AND post_type = 'attachment' 
						 AND term_taxonomy_id = %d",
					$term )
		);
		
		do_action( 'edit_term_taxonomy', $term );
		
		$wpdb->update( $wpdb->term_taxonomy, compact( 'count' ), array( 'term_taxonomy_id' => $term ) );
		
		do_action( 'edited_term_taxonomy', $term );
	}
	
	// clear cache
	file_gallery_clear_cache('mediatags_all');
}


/**
 * Adds media tags submenu
 */
function file_gallery_media_submenu()
{
	global $mediatags, $wp_version;
	
	if( ! (isset($mediatags) && is_a($mediatags, 'MediaTags') && defined('MEDIA_TAGS_TAXONOMY')) )
	{
		if( floatval($wp_version) < 3.5 )
		{
			add_submenu_page('upload.php',
				__('Media tags', 'file-gallery'),
				__('Media tags', 'file-gallery'),
				'upload_files',
				'edit-tags.php?taxonomy=' . FILE_GALLERY_MEDIA_TAG_NAME . '&post_type=attachment'
			);
		}
    }
}
add_action('admin_menu', 'file_gallery_media_submenu');


/**
 * Gets intermediate image sizes
 */
function file_gallery_get_intermediate_image_sizes()
{
	$sizes = array();

	if( function_exists('get_intermediate_image_sizes') )
		$sizes = get_intermediate_image_sizes();

	$additional_intermediate_sizes = apply_filters('intermediate_image_sizes', $sizes);
	
	array_unshift($additional_intermediate_sizes, 'thumbnail', 'medium', 'large', 'full');
	
	return array_unique($additional_intermediate_sizes);
}


/**
 * Media library extensions
 */
function file_gallery_add_library_query_vars( $input )
{
	global $wpdb, $pagenow;
	
	if( is_admin() )
	{
		$options = get_option('file_gallery');
	
		// affect the query only if we're on a certain page
		if( "media-upload.php" == $pagenow && "library" == $_GET["tab"] && is_numeric($_GET['post_id']) )
		{
			if( isset($_GET['exclude']) && "current" == $_GET['exclude'] )
				$input .= " AND `post_parent` != " . (int) $_GET["post_id"] . " ";
	
			if( isset($options["library_filter_duplicates"]) && true == $options["library_filter_duplicates"] )
				$input .= " AND $wpdb->posts.ID NOT IN ( SELECT ID FROM $wpdb->posts AS ps INNER JOIN $wpdb->postmeta AS pm ON pm.post_id = ps.ID WHERE pm.meta_key = '_is_copy_of' ) ";
		}
		elseif( "upload.php" == $pagenow && isset($options["library_filter_duplicates"]) && true == $options["library_filter_duplicates"] )
		{
			$input .= " AND $wpdb->posts.ID NOT IN ( SELECT ID FROM $wpdb->posts AS ps INNER JOIN $wpdb->postmeta AS pm ON pm.post_id = ps.ID WHERE pm.meta_key = '_is_copy_of' ) ";
		}
	}

	return $input;
}
add_filter('posts_where', 'file_gallery_add_library_query_vars');


remove_action( 'wp_ajax_query-attachments', 'wp_ajax_query_attachments', 1 );
add_action( 'wp_ajax_query-attachments', 'my_ajax_query_attachments', 1 );

$attach_parent = null;

function my_ajax_query_attachments(){
global $attach_parent;

$options = get_option('file_gallery');

	$query = isset( $_REQUEST['query'] ) ? (array) $_REQUEST['query'] : array();
	$query = array_intersect_key( $query, array_flip( array(
		's', 'order', 'orderby', 'posts_per_page', 'paged', 'post_mime_type',
		'post_parent', 'post__in', 'post__not_in',
	) ) );

	$query['post_type'] = 'attachment';
	$query['post_status'] = 'inherit';
	if ( current_user_can( get_post_type_object( 'attachment' )->cap->read_private_posts ) )
		$query['post_status'] .= ',private';
	
	if( isset($options["library_filter_duplicates"]) && true == $options["library_filter_duplicates"] ){
	$attach_parent = $_REQUEST['post_id'];
	add_filter('posts_where', 'filter_duplicate_attachments');
	}
	
	$query = new WP_Query( $query );
	
	if( isset($options["library_filter_duplicates"]) && true == $options["library_filter_duplicates"] ){
	remove_filter('posts_where', 'filter_duplicate_attachments');
	}
	$attach_parent = null;
	$posts = array_map( 'wp_prepare_attachment_for_js', $query->posts );
	$posts = array_filter( $posts );

	wp_send_json_success( $posts );
}

function filter_duplicate_attachments( $input )
{
	global $wpdb, $post, $attach_parent;

	if( ! empty($attach_parent) )
	{
		$input .= " AND (($wpdb->posts.ID NOT IN ( SELECT ID FROM $wpdb->posts AS ps INNER JOIN $wpdb->postmeta AS pm ON pm.post_id = ps.ID WHERE pm.meta_key = '_is_copy_of' )) OR ($wpdb->posts.post_parent=" . $attach_parent . "))";

		$input .= " AND ($wpdb->posts.ID NOT IN ( SELECT pm.meta_value FROM $wpdb->posts AS ps INNER JOIN $wpdb->postmeta AS pm ON pm.post_id = ps.ID WHERE pm.meta_key = '_is_copy_of' and ps.post_parent=" . $attach_parent . "))";
	}

	return $input;
}

/**
 * Adds js to admin area
 */
function file_gallery_js_admin()
{
	global $pagenow, $current_screen, $wp_version, $post_ID, $file_gallery;

	$s = array('{"', '",', '"}', '\/', '"[', ']"');
	$r = array("\n{\n\"", "\",\n", "\"\n}", '/', '[', ']');

	if(
	      "post.php" == $pagenow 
	   || "post-new.php" == $pagenow
	   || "page.php" == $pagenow 
	   || "page-new.php" == $pagenow 
	   || ("post" == $current_screen->base && isset($current_screen->post_type))
	  )
	{
		// file_gallery.L10n
		$file_gallery_localize = array(
			"switch_to_tags" 			 => __("Switch to tags", "file-gallery"),
			"switch_to_files" 			 => __("Switch to list of attachments", "file-gallery"),
			"fg_info" 					 => __("Insert checked attachments into post as", "file-gallery"),
			"no_attachments_upload" 	 => __("No files are currently attached to this post.", "file-gallery"),
			"sure_to_delete" 			 => __("Are you sure that you want to delete these attachments? Press [OK] to delete or [Cancel] to abort.", "file-gallery"),
			"saving_attachment_data" 	 => __("saving attachment data...", "file-gallery"),
			"loading_attachment_data"	 => __("loading attachment data...", "file-gallery"),
			"deleting_attachment" 		 => __("deleting attachment...", "file-gallery"),
			"deleting_attachments" 		 => __("deleting attachments...", "file-gallery"),
			"loading" 					 => __("loading...", "file-gallery"),
			"detaching_attachment"		 => __("detaching attachment", "file-gallery"),
			"detaching_attachments"		 => __("detaching attachments", "file-gallery"),
			"sure_to_detach"			 => __("Are you sure that you want to detach these attachments? Press [OK] to detach or [Cancel] to abort.", "file-gallery"),
			"close"						 => __("close", "file-gallery"),
			"loading_attachments"		 => __("loading attachments", "file-gallery"),
			"post_thumb_set"			 => __("Featured image set successfully", "file-gallery"),
			"post_thumb_unset"			 => __("Featured image removed", "file-gallery"),
			'copy_all_from_original'	 => __('Copy all attachments from the original post', 'file-gallery'),
			'copy_all_from_original_'	 => __('Copy all attachments from the original post?', 'file-gallery'),
			'copy_all_from_translation'  => __('Copy all attachments from this translation', 'file-gallery'),
			'copy_all_from_translation_' => __('Copy all attachments from this translation?', 'file-gallery'),
			"set_as_featured"			 => __("Set as featured image", "file-gallery"),
			"unset_as_featured"			 => __("Unset as featured image", "file-gallery"),
			'copy_from_is_nan_or_zero'   => __('Supplied ID (%d) is zero or not a number, please correct.', 'file-gallery'),
			'regenerating'               => __('regenerating...', 'file-gallery'),
			'gallery_updated'            => __('Gallery contents updated', 'file-gallery'),
			'attach_all_checked_copy' => __("Attach all checked items to current post", "file-gallery"),
			'exclude_current' => __("Exclude current post's attachments", "file-gallery"),
			'include_current' => __("Include current post's attachments", "file-gallery")
		);
		
		// file_gallery.options
		$file_gallery_options = array( 
			"file_gallery_url"   => file_gallery_https( FILE_GALLERY_URL ),
			"file_gallery_nonce" => wp_create_nonce('file-gallery'),
			"file_gallery_mode"  => "list",
			"file_gallery_version"  => FILE_GALLERY_VERSION,

			"num_attachments"    => 1,
			"tags_from"          => true,
			"clear_cache_nonce"  => wp_create_nonce('file-gallery-clear_cache'),
			"post_thumb_nonce"   => wp_create_nonce( "set_post_thumbnail-" . $post_ID ),
			"wp_version"         => floatval(get_bloginfo('version'))
		);

		$dependencies = array('jquery', 'jquery-ui-core', 'jquery-ui-draggable', 'jquery-ui-sortable', 'jquery-ui-dialog');
		
		wp_enqueue_script('file-gallery-main',  file_gallery_https( FILE_GALLERY_URL ) . '/js/file-gallery.js', $dependencies, FILE_GALLERY_VERSION);
		wp_enqueue_script('file-gallery-clear_cache',  file_gallery_https( FILE_GALLERY_URL ) . '/js/file-gallery-clear_cache.js', false, FILE_GALLERY_VERSION);
		wp_enqueue_script('file-gallery-media', file_gallery_https( FILE_GALLERY_URL ) . '/js/file-gallery-media.js', array('media-views'), FILE_GALLERY_VERSION);

		$script = '
		<script type="text/javascript">
			var file_gallery_L10n = ' . str_replace($s, $r, json_encode($file_gallery_localize)) . ',
				file_gallery_options = ' . str_replace($s, $r, json_encode($file_gallery_options)) . ',
				file_gallery_attach_nonce = "' . wp_create_nonce( 'file-gallery-attach' ) . '",
				init_file_gallery = true';
		
		if( floatval(get_bloginfo('version')) < 3.5 ) {
			$script .= ',
			file_gallery_acf_L10n = ' . str_replace($s, $r, json_encode($acf_localize)) . ',
			file_gallery_acf_options = ' . str_replace($s, $r, json_encode($acf_options)) . ';';
		}
		else {
			$script .= ';';
		}
		
		$script .= '</script>
		';
		
		echo $script;
		
		if( floatval(get_bloginfo('version')) < 3.5 )
		{
			// acf.L10n
			$acf_localize = array(
				'new_custom_field' => __("Add New Custom Field", "file-gallery"),
				'add_new_custom_field' => __("Add Custom Field", "file-gallery"),
				'error_deleting_attachment_custom_field' => __("Error deleting attachment custom field!", "file-gallery"),
				'error_adding_attachment_custom_field' => __("Error adding attachment custom field!", "file-gallery"),
				'name' => __("Name:", "file-gallery"),
				'value' => __("Value:", "file-gallery")
			);
			
			// acf.options
			$acf_options = array( 
				'add_new_attachment_custom_field_nonce' => wp_create_nonce( 'add_new_attachment_custom_field_nonce' ),
				'delete_attachment_custom_field_nonce' => wp_create_nonce( 'delete_attachment_custom_field_nonce' ),
				'custom_fields' => '[]'
			);
		
			wp_enqueue_script('acf-attachment-custom-fields', file_gallery_https( FILE_GALLERY_URL ) . '/js/file-gallery-attachment_custom_fields.js', false, FILE_GALLERY_VERSION);
		}
	}
	elseif( "edit.php" == $pagenow || "upload.php" == $pagenow  )
	{
		$file_gallery_options = array( 
			"file_gallery_url"   => file_gallery_https( FILE_GALLERY_URL ),
			"file_gallery_nonce" => wp_create_nonce('file-gallery'),
			"file_gallery_version"  => FILE_GALLERY_VERSION
		);
		
		wp_enqueue_script('file-gallery-main',  file_gallery_https( FILE_GALLERY_URL ) . '/js/file-gallery.js', array('jquery'), FILE_GALLERY_VERSION);

		echo '
		<script type="text/javascript">
			var file_gallery_L10n = {regenerating: "' . __('regenerating...', 'file-gallery') . '"},
				file_gallery_options = ' . str_replace($s, $r, json_encode($file_gallery_options)) . ',
				init_file_gallery = false;
		</script>
		';
	}
	elseif( "media.php" == $pagenow && is_numeric($_GET['attachment_id']) && "edit" == $_GET["action"] && floatval(get_bloginfo('version')) < 3.5 )
	{
		$custom_fields = array();
		$custom = get_post_custom($_GET['attachment_id']);

		foreach( (array) $custom as $key => $val )
		{
			if( 1 < count($val) || "_" == substr($key, 0, 1) || is_array($val[0]) )
				continue;
	
			$custom_fields[] = $key;
		}

		$custom_fields = (! empty($custom_fields)) ? "'" . implode("','", $custom_fields) . "'" : "";

		$acf_localize = array(
			'new_custom_field' => __("Add New Custom Field", "file-gallery"),
			'add_new_custom_field' => __("Add Custom Field", "file-gallery"),
			'error_deleting_attachment_custom_field' => __("Error deleting attachment custom field!", "file-gallery"),
			'error_adding_attachment_custom_field' => __("Error adding attachment custom field!", "file-gallery"),
			'name' => __("Name:", "file-gallery"),
			'value' => __("Value:", "file-gallery")
		);
		
		$acf_options = array( 
			'add_new_attachment_custom_field_nonce' => wp_create_nonce( 'add_new_attachment_custom_field_nonce' ),
			'delete_attachment_custom_field_nonce' => wp_create_nonce( 'delete_attachment_custom_field_nonce' ),
			'custom_fields' => '[' . $custom_fields . ']'
		);

		wp_enqueue_script('acf-attachment-custom-fields', file_gallery_https( FILE_GALLERY_URL ) . '/js/file-gallery-attachment_custom_fields.js', false, FILE_GALLERY_VERSION);
		
		echo '
		<script type="text/javascript">
			var file_gallery_acf_L10n = ' . str_replace($s, $r, json_encode($acf_localize)) . ',
				file_gallery_acf_options = ' . str_replace($s, $r, json_encode($acf_options)) . ';
		</script>
		';
	}
	elseif( "media-upload.php" == $pagenow && isset($_GET["tab"]) && "library" == $_GET["tab"] )
	{
		$file_gallery_localize = array(
			'attach_all_checked_copy' => __("Attach all checked items to current post", "file-gallery"),
			'exclude_current' => __("Exclude current post's attachments", "file-gallery"),
			'include_current' => __("Include current post's attachments", "file-gallery")
		);

		wp_enqueue_script('file-gallery-attach', file_gallery_https( FILE_GALLERY_URL ) . '/js/file-gallery-attach.js', false, FILE_GALLERY_VERSION);
		
		echo '
		<style type="text/css">
			#library-form .media-item.child-of-' . $_GET["post_id"] . '
			{
				background-color: #FFE;
			}
		</style>
		<script type="text/javascript">
			var file_gallery_attach_nonce = "' . wp_create_nonce( 'file-gallery-attach' ) . '",
				file_gallery_L10n = ' . str_replace($s, $r, json_encode($file_gallery_localize)) . ';
		</script>
		';
	}
	elseif( "options-media.php" == $pagenow )
	{
		echo '
		<script type="text/javascript">
			var file_gallery_options = {
				clear_cache_nonce : "' . wp_create_nonce('file-gallery-clear_cache') . '"
			};
		</script>
		';

		wp_enqueue_script('file-gallery-clear_cache', file_gallery_https( FILE_GALLERY_URL ) . '/js/file-gallery-clear_cache.js', false, FILE_GALLERY_VERSION);
	}
	elseif( 'edit-tags.php' == $pagenow && FILE_GALLERY_MEDIA_TAG_NAME == $_GET['taxonomy'] && 3 > floatval($wp_version) )
	{
		echo '
		<script type="text/javascript">
			jQuery(document).ready(function()
			{
				jQuery("h2").html("' . __("Media tags", "file-gallery") . '");
			});
		</script>
		';
	}
}
add_action('admin_print_scripts', 'file_gallery_js_admin');



/**
 * Adds css to admin area
 */
function file_gallery_css_admin()
{
	global $pagenow, $current_screen, $file_gallery;

	
	
	if(
		   'post.php' 			== $pagenow
		|| 'post-new.php' 		== $pagenow 
		|| 'page.php' 			== $pagenow 
		|| 'page-new.php' 		== $pagenow 
		|| 'media.php' 			== $pagenow 
		|| 'options-media.php'	== $pagenow 
		|| 'media-upload.php'	== $pagenow 
		|| 'upload.php'			== $pagenow 
		|| 'edit.php'			== $pagenow 
		|| 'options-permalink.php' == $pagenow
		|| (isset($current_screen->post_type) && 'post' == $current_screen->base)
	  )
	{
		wp_enqueue_style('file_gallery_admin', apply_filters('file_gallery_admin_css_location', file_gallery_https( FILE_GALLERY_URL ) . '/css/file-gallery.css'), false, FILE_GALLERY_VERSION );

		if( get_bloginfo('text_direction') == 'rtl' ) {
			wp_enqueue_style('file_gallery_admin_rtl', apply_filters('file_gallery_admin_rtl_css_location', file_gallery_https( FILE_GALLERY_URL ) . '/css/file-gallery-rtl.css'), false, FILE_GALLERY_VERSION );
		}
		
		if( floatval(get_bloginfo('version')) < 3.5 )
		{
			wp_enqueue_style('file_gallery_admin_buttons', apply_filters('file_gallery_admin_css_location', file_gallery_https( FILE_GALLERY_URL ) . '/css/file-gallery-pre35.css'), false, FILE_GALLERY_VERSION );
			
			if( get_bloginfo('text_direction') == 'rtl' ) {
				wp_enqueue_style('file_gallery_admin_rtl_buttons', apply_filters('file_gallery_admin_rtl_css_location', file_gallery_https( FILE_GALLERY_URL ) . '/css/file-gallery-pre35-rtl.css'), false, FILE_GALLERY_VERSION );
			}
		}
	}
}
add_action('admin_print_styles', 'file_gallery_css_admin');


/**
 * Edit post/page meta box content
 */
function file_gallery_content()
{
	global $post;

	echo 
	'<div id="fg_container">
		<noscript>
			<div class="error" style="margin: 0;">
				<p>' . __('File Gallery requires Javascript to function. Please enable it in your browser.', 'file-gallery') . '</p>
			</div>
		</noscript>
	</div>
				
	<div id="file_gallery_image_dialog">
	</div>
	
	<div id="file_gallery_delete_dialog" title="' . __('Delete attachment dialog', 'file-gallery') . '">
		<p><strong>' . __("Warning: one of the attachments you've chosen to delete has copies.", 'file-gallery') . '</strong></p>
		<p>' . __('How do you wish to proceed?', 'file-gallery') . '</p>
		<p><a href="' . FILE_GALLERY_URL . '/help/index.html#deleting_originals" target="_blank">' . __('Click here if you have no idea what this dialog means', 'file-gallery') . '</a> ' . __('(opens File Gallery help in new browser window)', 'file-gallery') . '</p>
	</div>
	
	<div id="file_gallery_copy_all_dialog" title="' . __('Copy all attachments from another post', 'file-gallery') . '">
		<div id="file_gallery_copy_all_wrap">
			<label for="file_gallery_copy_all_from">' . __('Post ID:', 'file-gallery') . '</label>
			<input type="text" id="file_gallery_copy_all_from" value="" />
		</div>
	</div>';
}


/**
 * Creates meta boxes on post editing screen
 */
function file_gallery()
{
	$options = get_option('file_gallery');
	
	if( function_exists('get_post_types') )
	{
		$types = get_post_types();
		
		foreach( $types as $type )
		{
			if( ! in_array( $type, array('nav_menu_item', 'revision', 'attachment') ) && isset($options['show_on_post_type_' . $type]) && true == $options['show_on_post_type_' . $type] ) {
				add_meta_box('file_gallery', __( 'File Gallery', 'file-gallery' ), 'file_gallery_content', $type, 'normal');
			}
		}
	}
	else // pre 2.9
	{
		add_meta_box('file_gallery', __( 'File Gallery', 'file-gallery' ), 'file_gallery_content', 'post', 'normal');
		add_meta_box('file_gallery', __( 'File Gallery', 'file-gallery' ), 'file_gallery_content', 'page', 'normal');
	}
	
	if( function_exists('get_taxonomies_for_attachments') ) // WP 3.5
	{
		add_meta_box( 'file_gallery_attachment_custom_fields', __('File Gallery: Attachment Custom Fields'), 'file_gallery_attachment_custom_fields_metabox', 'attachment', 'normal' );
	}
}
add_action('admin_menu', 'file_gallery');


/**
 * Outputs attachment count in the proper column
 */
function file_gallery_posts_custom_column($column_name, $post_id)
{
	global $wpdb, $file_gallery;
	
	$options = get_option('file_gallery');

	if( 'attachment_count' == $column_name && isset($options['e_display_attachment_count']) && true == $options['e_display_attachment_count'] )
	{
		$count = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(ID) FROM $wpdb->posts WHERE post_type='attachment' AND post_parent=%d", $post_id) );
		
		echo apply_filters('file_gallery_post_attachment_count', $count, $post_id);
	}
	elseif( 'post_thumb' == $column_name && isset($options['e_display_post_thumb']) && true == $options['e_display_post_thumb'] )
	{
		if( $thumb_id = get_post_meta( $post_id, '_thumbnail_id', true ) )
		{
			$content = str_replace('<a ', '<a class="thickbox" rel="file-gallery-post-list" ', wp_get_attachment_link($thumb_id, 'thumbnail', false ));
			
			if( false === $file_gallery->admin_thickbox_enqueued )
			{
				wp_enqueue_style('thickbox');
				wp_enqueue_script('thickbox');
				$file_gallery->admin_thickbox_enqueued = true;
			}
			
			echo apply_filters('file_gallery_post_thumb_content', $content, $post_id, $thumb_id);
		}
		else
		{
			echo apply_filters('file_gallery_no_post_thumb_content', '<span class="no-post-thumbnail">-</span>', $post_id);
		}
	}
}
add_action('manage_posts_custom_column', 'file_gallery_posts_custom_column', 100, 2);
add_action('manage_pages_custom_column', 'file_gallery_posts_custom_column', 100, 2);


/**
 * Adds attachment count column to the post and page edit screens
 */
function file_gallery_posts_columns( $columns )
{
	$options = get_option('file_gallery');
	
	if( isset($options['e_display_attachment_count']) && true == $options['e_display_attachment_count'] ) {
		$columns['attachment_count'] = __('No. of attachments', 'file-gallery');
	}
		
	if( isset($options['e_display_post_thumb']) && true == $options['e_display_post_thumb'] )
	{
		$new = array( key($columns) => array_shift($columns), 'post_thumb' => '' );
		$columns = $new + $columns;
	}

	return $columns;
}
add_filter('manage_posts_columns', 'file_gallery_posts_columns');
add_filter('manage_pages_columns', 'file_gallery_posts_columns');


/**
 * Outputs attachment media tags in the proper column
 */
function file_gallery_media_custom_column($column_name, $post_id)
{
	global $wpdb;
	
	$options = get_option('file_gallery');
	
	if( 'media_tags' == $column_name && isset($options['e_display_media_tags']) && true == $options['e_display_media_tags'])
	{
		if( isset($options['cache']) && true == $options['cache'] )
		{
			$transient = 'fileglry_mt_' . md5($post_id);
			$cache = get_transient($transient);
			
			if( $cache )
			{
				echo $cache;
				return;
			}
		}
		
		$l = '?taxonomy=' . FILE_GALLERY_MEDIA_TAG_NAME . '&amp;term=';
		$out = __('No Media Tags', 'file-gallery');
		
		$q = "SELECT `name`, `slug` 
			  FROM $wpdb->terms
			  LEFT JOIN $wpdb->term_taxonomy ON ( $wpdb->terms.term_id = $wpdb->term_taxonomy.term_id ) 
			  LEFT JOIN $wpdb->term_relationships ON ( $wpdb->term_taxonomy.term_taxonomy_id = $wpdb->term_relationships.term_taxonomy_id ) 
			  WHERE `taxonomy` = '" . FILE_GALLERY_MEDIA_TAG_NAME . "'
			  AND `object_id` = %d
			  ORDER BY `name` ASC";
		
		if( $r = $wpdb->get_results($wpdb->prepare($q, $post_id)) )
		{
			$out = array();
			
			foreach( $r as $tag )
			{
				$out[] = '<a href="' . $l . $tag->slug . '">' . $tag->name . '</a>';
			}
			
			$out = implode(', ', $out);
		}
		
		if( isset($options['cache']) && true == $options['cache'] ) {
			set_transient($transient, $out, $options['cache_time']);
		}
		
		echo $out;
	}
}
add_action('manage_media_custom_column', 'file_gallery_media_custom_column', 100, 2);


/**
 * Adds media tags column to attachments
 */
function file_gallery_media_columns( $columns )
{
	global $mediatags;

	if( ! (is_a($mediatags, 'MediaTags') && defined('MEDIA_TAGS_TAXONOMY')) ) {
		$columns['media_tags'] = __('Media tags', 'file-gallery');
	}
	
	return $columns;
}
add_filter('manage_media_columns', 'file_gallery_media_columns');

function print_new_attachment_template(){
global $post;
?>

<script type="text/html" id="tmpl-attachment-new">
		<# 	if ( <?php echo $post->ID; ?> == data.uploadedTo ) { #>
		<div class="attachment-preview isattached type-{{ data.type }} subtype-{{ data.subtype }} {{ data.orientation }}">
		<# } else { #>
		<div class="attachment-preview type-{{ data.type }} subtype-{{ data.subtype }} {{ data.orientation }}">
		<# } #>
			<# if ( data.uploading ) { #>
				<div class="media-progress-bar"><div></div></div>
			<# } else if ( 'image' === data.type ) { #>
				<div class="thumbnail">
					<div class="centered">
						<img src="{{ data.size.url }}" draggable="false" />
					</div>
				</div>
			<# } else { #>
				<img src="{{ data.icon }}" class="icon" draggable="false" />
				<div class="filename">
					<div>{{ data.filename }}</div>
				</div>
			<# } #>

			<# if ( data.buttons.close ) { #>
				<a class="close media-modal-icon" href="#" title="<?php _e('Remove'); ?>"></a>
			<# } #>

			<# if ( data.buttons.check ) { #>
				<a class="check" href="#" title="<?php _e('Deselect'); ?>"><div class="media-modal-icon"></div></a>
			<# } #>
			
			<# if ( data.buttons.attach ) { #>
				<a class="attach id_{{ data.id }}" href="#" title="attach/detach"><div class="media-modal-icon"></div></a>
			<# } #>

		</div>
		<#
		var maybeReadOnly = data.can.save || data.allowLocalEdits ? '' : 'readonly';
		if ( data.describe ) { #>
			<# if ( 'image' === data.type ) { #>
				<input type="text" value="{{ data.caption }}" class="describe" data-setting="caption"
					placeholder="<?php esc_attr_e('Caption this image&hellip;'); ?>" {{ maybeReadOnly }} />
			<# } else { #>
				<input type="text" value="{{ data.title }}" class="describe" data-setting="title"
					<# if ( 'video' === data.type ) { #>
						placeholder="<?php esc_attr_e('Describe this video&hellip;'); ?>"
					<# } else if ( 'audio' === data.type ) { #>
						placeholder="<?php esc_attr_e('Describe this audio file&hellip;'); ?>"
					<# } else { #>
						placeholder="<?php esc_attr_e('Describe this media file&hellip;'); ?>"
					<# } #> {{ maybeReadOnly }} />
			<# } #>
		<# } #>
	</script>


<?php
 }
 
add_action('print_media_templates','print_new_attachment_template');

/**
 * Includes
 */
require_once('includes/media-tags.php');
require_once('includes/media-settings.php');
require_once('includes/attachments.php');
require_once('includes/miscellaneous.php');
require_once('includes/mime-types.php');
require_once('includes/lightboxes-support.php');
require_once('includes/templating.php');
require_once('includes/main.php');
require_once('includes/functions.php');
require_once('includes/cache.php');
require_once('includes/regenerate-images.php');
require_once('includes/attachments-custom-fields.php');
require_once('includes/tinymce.php');

if( 3.1 <= floatval(get_bloginfo('version')) ) {
	require_once('includes/media-tags-list-table.class.php');
}

if( 3.3 <= floatval(get_bloginfo('version')) ) {
	require_once('includes/media-upload.php');
}

/* DEBUG 
function save_error()
{
    update_option('plugin_error', ob_get_contents());
}
add_action('activated_plugin','save_error');
echo get_option('plugin_error');
delete_option('plugin_error');
*/

