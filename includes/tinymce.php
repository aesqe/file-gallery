<?php

function file_gallery_tinymce()
{
	// Don't bother doing this stuff if the current user lacks permissions
	if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') )
		return;
	
	// Add only in Rich Editor mode
	if ( get_user_option('rich_editing') == 'true')
	{
		add_filter('mce_external_plugins', 'file_gallery_tinymce_add_plugin');
		// add_filter('mce_buttons', 'file_gallery_tinymce_register_button');
	}
}
add_action('init', 'file_gallery_tinymce');
 

function file_gallery_tinymce_add_plugin( $plugin_array )
{
	$plugin_array['file_gallery'] = FILE_GALLERY_URL . '/js/file-gallery-tinymce.js';
	
	return $plugin_array;
}


function file_gallery_tinymce_register_button( $buttons )
{
	array_push($buttons, 'separator', 'file_gallery');
	
	return $buttons;
}

