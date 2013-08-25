<?php

function file_gallery_tinymce()
{
	if( current_user_can('edit_posts') && current_user_can('edit_pages') && get_user_option('rich_editing') == 'true') {
		add_filter('mce_external_plugins', 'file_gallery_tinymce_add_plugin');
	}
}
add_action('init', 'file_gallery_tinymce');
 

function file_gallery_tinymce_add_plugin( $plugin_array )
{
	$plugin_array['filegallery'] = FILE_GALLERY_URL . '/js/file-gallery-tinymce.js';
	
	return $plugin_array;
}

