<?php

/**
 * Support for other plugins
 *
 * Supported so far:
 * - WordPress Mobile Edition
 * - Media Tags
 */
function file_gallery_plugins_loaded()
{
	$file_gallery_abspath = WP_PLUGIN_DIR . '/' . basename(dirname(dirname(__FILE__)));
	$file_gallery_abspath = str_replace('\\', '/', $file_gallery_abspath);
	$file_gallery_abspath = preg_replace('#/+#', '/', $file_gallery_abspath);

	if( ! defined('FILE_GALLERY_URL') )
	{
		// file gallery directories and template names
		define('FILE_GALLERY_URL', WP_PLUGIN_URL . '/' . basename(dirname( dirname(__FILE__) )));
		define('FILE_GALLERY_ABSPATH', $file_gallery_abspath);
	}

	$mobile = false;
	$options = get_option('file_gallery');

	// WordPress Mobile Edition
	if( function_exists('cfmobi_check_mobile') && cfmobi_check_mobile() )
	{
		$mobile = true;

		if( ! isset($options['disable_shortcode_handler']) || $options['disable_shortcode_handler'] != true ) {
			add_filter('stylesheet_uri', 'file_gallery_mobile_css');
		}
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
		define('FILE_GALLERY_THEME_ABSPATH', $file_gallery_theme_abspath);
		define('FILE_GALLERY_THEME_TEMPLATES_ABSPATH', apply_filters('file_gallery_templates_folder_abspath', FILE_GALLERY_THEME_ABSPATH . '/file-gallery-templates')) ;
		define('FILE_GALLERY_THEME_TEMPLATES_URL', apply_filters('file_gallery_templates_folder_url', get_bloginfo('stylesheet_directory') . '/file-gallery-templates'));

		define('FILE_GALLERY_CONTENT_TEMPLATES_ABSPATH', apply_filters('file_gallery_content_templates_folder_abspath', WP_CONTENT_DIR . '/file-gallery-templates'));
		define('FILE_GALLERY_CONTENT_TEMPLATES_URL', apply_filters('file_gallery_content_templates_folder_url', WP_CONTENT_URL . '/file-gallery-templates'));

		define('FILE_GALLERY_DEFAULT_TEMPLATE_URL', apply_filters('file_gallery_default_template_url', FILE_GALLERY_URL . '/templates/default'));
		define('FILE_GALLERY_DEFAULT_TEMPLATE_ABSPATH', apply_filters('file_gallery_default_template_abspath', FILE_GALLERY_ABSPATH . '/templates/default'));
		define('FILE_GALLERY_DEFAULT_TEMPLATE_NAME', apply_filters('file_gallery_default_template_name', 'default'));
	}

	// display debug information
	if( ! defined('FILE_GALLERY_DEBUG') ) {
		define('FILE_GALLERY_DEBUG', false);
	}
}
add_action('after_setup_theme', 'file_gallery_after_setup_theme');


/**
 * Adds a link to plugin's settings and help pages (shows up next to the
 * deactivation link on the plugins management page)
 */
function file_gallery_plugin_action_links( $links )
{
	array_unshift($links, '<a href="options-media.php">' . __('Settings', 'file-gallery') . '</a>');
	array_unshift($links, '<a href="' . FILE_GALLERY_URL . '/help/index.html" target="_blank">' . __('Help', 'file-gallery') . '</a>');

	return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'file_gallery_plugin_action_links');


function file_gallery_filter_plugin_row_meta( $plugin_meta, $plugin_file, $plugin_data, $status )
{
	if( $plugin_data['Name'] == 'File Gallery' && is_plugin_active($plugin_file))
	{
		array_push($plugin_meta, '<span style="padding: 2px 4px; background: #FFFFEE; color: #777777; border: 1px solid #EEDDCC; border-radius: 3px; border-radius: 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px; -ms-border-radius: 3px; -o-border-radius: 3px;">Visit <a href="http://wordpress.org/extend/plugins/file-gallery/">plugin page</a> or <a href="http://wordpress.org/support/plugin/file-gallery">plugin support forums</a> on WordPress.org</a></span>');
	}

	return $plugin_meta;
}
add_filter('plugin_row_meta', 'file_gallery_filter_plugin_row_meta', 10, 4);


/**
 * Adds media_tags taxonomy so we can tag attachments
 */
function file_gallery_add_textdomain_and_taxonomies()
{
	global $mediatags, $wp_rewrite;

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
				'name' => __('Media tags', 'file-gallery'),
				'singular_label' => __('Media tag', 'file-gallery')
			)
		);

		register_taxonomy( FILE_GALLERY_MEDIA_TAG_NAME, 'attachment', $args );
	}

	if( get_option('file_gallery_flush_rewrite_rules') == true )
	{
		$wp_rewrite->flush_rules( false );
		delete_option('file_gallery_flush_rewrite_rules');
	}
}
add_action('init', 'file_gallery_add_textdomain_and_taxonomies', 100);