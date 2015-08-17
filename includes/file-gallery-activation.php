<?php

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
	$options = get_option('file_gallery');

	// if options already exist, upgrade
	if( $options )
	{
		// preserve display options when upgrading from below 1.6.5.3
		if( ! isset($options['display_acf']) )
		{
			if( isset($options['insert_options_states']) ) {
				$states = explode(',', $options['insert_options_states']);
			}
			else {
				$states = array('1', '1');
			}

			if( isset($options['display_insert_fieldsets']) ) {
				$display = $options['display_insert_fieldsets'];
			}
			else {
				$display = 1;
			}

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

	print_r(get_option('file_gallery'));

	// clear any existing cache
	file_gallery_clear_cache();
}

/**
 * Do activation procedure on plugin upgrade
 */
function file_gallery_upgrade()
{
	$options = get_option('file_gallery');

	if( isset($options['version']) && version_compare($options['version'], FILE_GALLERY_VERSION, '<') )
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

	if( isset($options['del_options_on_deactivate']) && $options['del_options_on_deactivate'] == true ) {
		delete_option('file_gallery');
	}
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

