<?php

function file_gallery_filter_parse_query( $wp_query )
{
	if( isset($wp_query->query_vars[FILE_GALLERY_MEDIA_TAG_NAME]) )
		$wp_query->query_vars['post_status'] = 'inherit';
}
add_action('parse_query', 'file_gallery_filter_parse_query');


/*
show media tag slug input field 
on permalink settings page (same as media tags plugin)
only if media tags plugin is not active

show media tags taxonomy name input field
and disable it if media tags is active
because its taxonomy name is alway media-tags
*/
function file_gallery_media_tags_add_permalink_fields()
{
	global $mediatags, $wp_rewrite;

	$options = get_option('file_gallery');
	$current_tax_name = $options['media_tag_taxonomy_name'];

	if( isset($_POST['media_tag_taxonomy_slug']) )
		$options['media_tag_taxonomy_slug'] = $_POST['media_tag_taxonomy_slug'];
		
	if( isset($_POST['media_tag_taxonomy_name']) )
		$options['media_tag_taxonomy_name'] = $_POST['media_tag_taxonomy_name'];
		
	if( isset($_POST['mediatag_base']) )
		$options['media_tag_taxonomy_slug'] = $_POST['mediatag_base'];

	if( isset($_POST['media_tag_taxonomy_slug']) || isset($_POST['media_tag_taxonomy_name']) || isset($_POST['mediatag_base']) )
	{
		update_option('file_gallery', $options);
		
		file_gallery_media_tags_update_taxonomy_slug( $current_tax_name, $options['media_tag_taxonomy_name'] );
	}
	
	add_settings_field(
		'media_tag_taxonomy_name', __('Media tags Taxonomy name', 'file-gallery'),
		create_function('', 'file_gallery_media_tags_permalink_fields("media_tag_taxonomy_name");'),
		'permalink', 'optional');
	
	if( ! (isset($mediatags) && is_a($mediatags, 'MediaTags') && defined('MEDIA_TAGS_TAXONOMY')) )
	{
		add_settings_field(
			'media_tag_taxonomy_slug', __('Media tags URL slug', 'file-gallery'),
			create_function('', 'file_gallery_media_tags_permalink_fields("media_tag_taxonomy_slug");'),
			'permalink', 'optional');
	}
}
add_action( 'admin_init', 'file_gallery_media_tags_add_permalink_fields' );


function file_gallery_media_tags_permalink_fields( $field )
{
	global $mediatags;

	$options = get_option('file_gallery');
	$disabled = isset($mediatags) && is_a($mediatags, 'MediaTags') && defined('MEDIA_TAGS_TAXONOMY') ? ' disabled="disabled"' : '';
	
	switch( $field )
	{
		case 'media_tag_taxonomy_name' : 				
		?>
			<input name="media_tag_taxonomy_name" id="media_tag_taxonomy_name" type="text" value="<?php echo $options['media_tag_taxonomy_name']; ?>" class="regular-text code"<?php echo $disabled; ?> /> 
		<?php
		break;
		
		case 'media_tag_taxonomy_slug' :
		?>
			<input name="media_tag_taxonomy_slug" id="media_tag_taxonomy_slug" type="text" value="<?php echo $options['media_tag_taxonomy_slug']; ?>" class="regular-text code" /> 
		<?php
		break;
	}
}


function file_gallery_media_tags_get_taxonomy_slug()
{
	global $wpdb, $mediatags;

	if( defined('FILE_GALLERY_MEDIA_TAG_NAME') )
		return FILE_GALLERY_MEDIA_TAG_NAME;

	//file_gallery_do_settings();
	
	$options = get_option('file_gallery');

	$current_tax_name = $options['media_tag_taxonomy_name'];
	$current_tax_slug = $options['media_tag_taxonomy_slug'];
	$tax_name = $current_tax_name ? $current_tax_name : 'media_tag';
	$tax_slug = $current_tax_slug ? $current_tax_slug : 'media-tag';

	// Media Tags plugin
	if( isset($mediatags) && is_a($mediatags, 'MediaTags') && defined('MEDIA_TAGS_TAXONOMY') )
	{
		$tax_name = MEDIA_TAGS_TAXONOMY;
		$tax_slug = MEDIA_TAGS_URL;
	}

	// change taxonomy slug if Media Tags has been activated
	if( $tax_name != $current_tax_name || $tax_slug != $current_tax_slug )
	{
		$options['media_tag_taxonomy_name'] = $tax_name;
		$options['media_tag_taxonomy_slug'] = $tax_slug;
		update_option('file_gallery', $options);

		if( $tax_name != $current_tax_name )
			file_gallery_media_tags_update_taxonomy_slug( $current_tax_name, $tax_name );
	}

	define('FILE_GALLERY_MEDIA_TAG_SLUG', $tax_slug);
	define('FILE_GALLERY_MEDIA_TAG_NAME', $tax_name);
}


function file_gallery_media_tags_update_taxonomy_slug( $old_slug = '', $new_slug = '' )
{
	global $wpdb;

	if( empty($old_slug) || empty($new_slug) )
		return -1;
	
	if( 0 <= $wpdb->update($wpdb->term_taxonomy, array('taxonomy' => $new_slug), array('taxonomy' => $old_slug) ) )
	{
		update_option('file_gallery_flush_rewrite_rules', 1);
		
		return true;
	}
	
	return false;
}

