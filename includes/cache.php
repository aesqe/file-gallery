<?php

/**
 * Clears File Gallery cache (transients)
 *
 * @since 1.5
 */
function file_gallery_clear_cache( $what = 'all', $post_id = null )
{
	global $wpdb;
	
	/*
	filegallery_{md5} - cached [gallery] shortcode output; hash is md5({postid}_{attributes})
	fileglry_mt_{md5} - mediatags for attachments; hash is md5({postid})
	filegallery_templates - template names
	filegallery_mediatags_{type} - list of all media tags; {type} is html, array or object
	*/
	
	$q = "DELETE FROM $wpdb->options WHERE ";
	
	if( "all" == $what )
	{
		$q .= "option_name LIKE '_transient_timeout_filegallery_%' OR 
			   option_name LIKE '_transient_filegallery_%' OR 
			   option_name LIKE '_transient_timeout_fileglry_mt_%' OR 
			   option_name LIKE '_transient_fileglry_mt_%' OR
			   option_name LIKE '_transient_timeout_filegallery_mediatags_%' OR 
			   option_name LIKE '_transient_filegallery_mediatags_%' OR
			   option_name = '_transient_timeout_filegallery_templates' OR 
			   option_name = '_transient_filegallery_templates%'";
	}
	elseif( "gallery" == $what )
	{
		$q .= "option_name LIKE '_transient_timeout_filegallery_%' OR 
			   option_name LIKE '_transient_filegallery_%'";
	}
	elseif( "mediatags_all" == $what )
	{
		if( null !== $post_id)
		{
			$hash = md5($post_id);
			
			$q .= "option_name LIKE '_transient_timeout_filegallery_mediatags_%' OR 
				   option_name LIKE '_transient_filegallery_mediatags_%' OR 
				   option_name = '_transient_timeout_fileglry_mt_" . $hash . "' OR 
				   option_name = '_transient_fileglry_mt_" . $hash . "'";
		}
		else
		{
			$q .= "option_name LIKE '_transient_timeout_filegallery_mediatags_%' OR 
				   option_name LIKE '_transient_filegallery_mediatags_%' OR 
				   option_name LIKE '_transient_timeout_fileglry_mt_%' OR 
				   option_name LIKE '_transient_fileglry_mt_%'";
		}
	}
	elseif( "mediatags" == $what )
	{
		$q .= "option_name LIKE '_transient_timeout_filegallery_mediatags_%' OR 
			   option_name LIKE '_transient_filegallery_mediatags_%'";
	}
	elseif( "attachment_mediatags" == $what )
	{
		$q .= "option_name LIKE '_transient_timeout_fileglry_mt_%' OR 
			   option_name LIKE '_transient_fileglry_mt_%'";
	}
	elseif( "templates" == $what )
	{
		$q .= "option_name = '_transient_timeout_filegallery_templates' OR 
			   option_name = '_transient_filegallery_templates%'";
	}
	
	return $wpdb->query($q);
}



/**
 * Clears media tags cache
 * int $post_id
 * @since 1.5
 */
function file_gallery_clear_cache_mediatags_all( $post_id )
{
	return file_gallery_clear_cache("mediatags_all", $post_id);
}
add_action("edit_attachment",   "file_gallery_clear_cache_mediatags_all");
add_action("delete_attachment", "file_gallery_clear_cache_mediatags_all");



/**
 * Clears galleries output cache
 *
 * @since 1.5
 */
function file_gallery_clear_cache_gallery()
{
	global $post_id;
	
	return file_gallery_clear_cache("gallery", $post_id);
}
add_action("save_post",   "file_gallery_clear_cache_gallery");
add_action("edit_post",   "file_gallery_clear_cache_gallery");
add_action("delete_post", "file_gallery_clear_cache_gallery");



/**
 * Clears cache completely, via ajax
 *
 * @since 1.5
 */
function file_gallery_clear_cache_manual()
{
	check_ajax_referer('file-gallery-clear_cache');
	
	file_gallery_clear_cache();
	
	_e("You have successfully cleared the File Gallery cache.", "file-gallery");
	
	exit();
}
add_action('wp_ajax_file_gallery_clear_cache_manual', 'file_gallery_clear_cache_manual');

