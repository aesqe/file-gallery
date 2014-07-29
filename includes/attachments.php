<?php

/**
 * Copies an attachment's data and creates a new attachment
 * for the current post using that data
 */
function file_gallery_copy_attachments_to_post()
{
	global $wpdb;
	
	check_ajax_referer('file-gallery-attach');
	
	$post_id = (int) $_POST['post_id'];
	$attached_ids = $_POST['ids'];
	$output = '';
	
	// get checked attachments
	if( $attached_ids != '' && $attached_ids != ',' ) {
		$possible_new_attachments = get_posts('post_type=attachment&include=' . $attached_ids);
	}
	
	// get current post's attachments
	$current_attachments = get_posts('numberposts=-1&post_type=attachment&post_parent=' . $post_id);
	
	if( $current_attachments !== false && ! empty($current_attachments) ) // if post already has attachments
	{
		foreach( $possible_new_attachments as $pna ) // for each checked item...
		{
			foreach( $current_attachments as $ca ) // go through each already present attachment
			{
				if( wp_get_attachment_url($pna->ID) == wp_get_attachment_url($ca->ID) ) // if their URIs match
				{
					$attached_ids = str_replace( $pna->ID, '', $attached_ids ); // remove that id from the list
					$attachments_exist[] = $pna->ID; // and add it to a list of conflicting attachments
				}
			}
		}
	}
	
	$attached_ids = preg_replace('#,+#', ',', $attached_ids ); // remove extra commas
	$attached_ids = trim($attached_ids, ',');
	$attached_ids = trim($attached_ids);
	
	if( $attached_ids != '' ) {
		$attached_ids = explode(',', $attached_ids); // explode into array if not empty
	}
	
	// prepare data and copy attachment to current post
	if( is_array($attached_ids) )
	{
		$attached_ids = array_unique($attached_ids);

		foreach( $attached_ids as $aid )
		{
			file_gallery_copy_attachment_to_post( $aid, $post_id );
		}
	
		// generate output
		if( ! empty($attachments_exist) )
		{
			$output .= __('Some of the checked attachments were successfully attached to current post.', 'file-gallery');
			$output .= '<br />' . __("Additionally, here are ID's of attachments you had selected, but were already attached to current post, according to their URIs.<br />You will be presented with an option to copy those attachments as well in the next version of this plugin. If that makes any sense, that is.", 'file-gallery') . ': ' . implode(',', $attachments_exist);
		}
		else
		{
			$output .= __('Checked attachments were successfully attached to current post.', 'file-gallery');
		}
	}
	else
	{
		if( ! empty($attachments_exist) )
			$output .= __('All of the checked attachments are already attached to current post, according to their URIs.<br />You will be presented with an option to copy those attachments as well in the next version of this plugin. If that makes any sense, that is.', 'file-gallery');
		else
			$output .= __('You must check the checkboxes next to attachments you want to copy to current post.', 'file-gallery');
	}
	
	if( ! is_array($attached_ids) )
		$attached_ids = array();
	
	// return output prepended by a list of checked attachments
	// using # (hash) as the separator
	echo implode(',', $attached_ids) . '#' . $output;
	
	exit();
}
add_action('wp_ajax_file_gallery_copy_attachments_to_post', 'file_gallery_copy_attachments_to_post');



/**
 * Copies an attachment to a post 
 */
function file_gallery_copy_attachment_to_post( $aid, $post_id )
{
	global $wpdb;
	
	if( ! is_numeric($aid) || ! is_numeric($post_id) || 0 === (int) $aid || 0 === (int) $post_id ) {
		return -1;
	}
	
	$attachment = get_post($aid);
	
	// don't duplicate - if it's unattached, just attach it without copying the data
	if( 0 === $attachment->post_parent ) {
		return $wpdb->update( $wpdb->posts, array('post_parent' => $post_id), array('ID' => $attachment->ID), array('%d'), array('%d') );
	}

	$attachment->metadata = get_post_meta($attachment->ID, '_wp_attachment_metadata', true);
	$attachment->attached_file = get_post_meta($attachment->ID, '_wp_attached_file', true);

	unset($attachment->ID);
	
	// maybe include this as an option on media settings screen...?
	$attachment->post_title .= apply_filters('file_gallery_attachment_copy_title_extension', '', $post_id);
	
	// copy main attachment data
	$attachment_id = wp_insert_attachment( $attachment, false, $post_id );
	
	// copy attachment custom fields
	$acf = get_post_custom($aid);
	
	foreach( $acf as $key => $val )
	{
		if( ! in_array($key, array('_is_copy_of', '_has_copies')) )
		{
			foreach( $val as $v )
			{
				add_post_meta($attachment_id, $key, $v);
			}
		}
	}
	
	// other meta values	
	update_post_meta( $attachment_id, '_wp_attached_file',  $attachment->attached_file );
	update_post_meta( $attachment_id, '_wp_attachment_metadata', $attachment->metadata );

	/* copies and originals */

	// if we're duplicating a copy, set duplicate's "_is_copy_of" value to original's ID
	if( $is_a_copy = get_post_meta($aid, '_is_copy_of', true) ) {
		$aid = $is_a_copy;
	}
	
	update_post_meta($attachment_id, '_is_copy_of', $aid);
	
	// meta for the original attachment (array holding ids of its copies)
	$has_copies = get_post_meta($aid, '_has_copies', true);
	$has_copies[] = $attachment_id;
	$has_copies = array_unique($has_copies);
	
	update_post_meta($aid, '_has_copies',  $has_copies);
	
	/*  / copies and originals */

	if( defined('FILE_GALLERY_MEDIA_TAG_NAME') )
	{
		// copy media tags
		$media_tags = wp_get_object_terms(array($aid), FILE_GALLERY_MEDIA_TAG_NAME);
		$tags = array();
		
		foreach( $media_tags as $mt )
		{
			$tags[] = $mt->name;
		}
		
		wp_set_object_terms($attachment_id, $tags, FILE_GALLERY_MEDIA_TAG_NAME);
	}
	
	return $attachment_id;
}


/**
 * copies all attachments from one post to another
 */
function file_gallery_copy_all_attachments()
{
	global $wpdb;
	
	$from_id  = $_POST['from_id'];
	$to_id    = $_POST['to_id'];
	$thumb_id = false;
	
	if( ! is_numeric($from_id) || ! is_numeric($to_id) || 0 === $from_id || 0 === $to_id ){
		exit('ID not numeric or zero! (file_gallery_copy_all_attachments)');
	}
	
	$attachments = $wpdb->get_results( sprintf("SELECT `ID` FROM $wpdb->posts WHERE `post_type`='attachment' AND `post_parent`=%d", $from_id) );
	
	if( false === $attachments )
	{
		$error = __('Database error! (file_gallery_copy_all_attachments)', 'file-gallery');
		file_gallery_write_log( $error );
		exit( $error );
	}
	
	if( 0 === count($attachments) ){
		exit( sprintf( __('Uh-oh. No attachments were found for post ID %d.', 'file-gallery'), $from_id ) );
	}
	
	// if the post we're copying all the attachments to has no attachments...
	if( 0 === count($wpdb->get_results($wpdb->prepare("SELECT `ID` FROM $wpdb->posts WHERE `post_type`='attachment' AND `post_parent`=%d", $to_id))) ) {
		$thumb_id = get_post_meta( $from_id, '_thumbnail_id', true ); // ...automatically set the original post's thumb to the new one
	}
	
	do_action('file_gallery_copy_all_attachments', $from_id, $to_id);
	
	foreach( $attachments as $aid )
	{
		$r = file_gallery_copy_attachment_to_post( $aid->ID, $to_id );
		
		if( -1 === $r ) {
			$errors[] = $aid->ID;
		}
		
		// set post thumb
		if( $aid->ID === $thumb_id ) {
			update_post_meta( $to_id, '_thumbnail_id', $r);
		}
	}
	
	if( ! isset($errors) ) {
		echo sprintf( __('All attachments were successfully copied from post %d.', 'file-gallery'), $from_id );
	}
	else {
		echo 'error ids: ' . implode(', ', $errors);
	}
	
	exit();
}
add_action('wp_ajax_file_gallery_copy_all_attachments', 'file_gallery_copy_all_attachments');



/**
 * This is a copy of wp_delete_attachment function without file deletion bits.
 * 
 * It removes database data only.
 */
function file_gallery_delete_attachment( $post_id )
{
	global $wpdb;
	
	if ( ! current_user_can('delete_post', $post_id) ) {
		return false;
	}

	if ( ! $post = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $wpdb->posts WHERE ID = %d", $post_id) ) ) {
		return $post;
	}

	if ( 'attachment' != $post->post_type ) {
		return false;
	}

	delete_post_meta($post_id, '_wp_trash_meta_status');
	delete_post_meta($post_id, '_wp_trash_meta_time');

	do_action('file_gallery_delete_attachment', $post_id);

	wp_delete_object_term_relationships($post_id, array('category', 'post_tag'));
	wp_delete_object_term_relationships($post_id, get_object_taxonomies($post->post_type));

	$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->postmeta WHERE meta_key = '_thumbnail_id' AND meta_value = %d", $post_id ));

	// delete comments
	$comment_ids = $wpdb->get_col( $wpdb->prepare( "SELECT comment_ID FROM $wpdb->comments WHERE comment_post_ID = %d", $post_id ));
	if ( ! empty( $comment_ids ) )
	{
		do_action( 'delete_comment', $comment_ids );
		foreach ( $comment_ids as $comment_id )
		{
			wp_delete_comment( $comment_id, true );
		}
		do_action( 'deleted_comment', $comment_ids );
	}

	// delete meta values
	$post_meta_ids = $wpdb->get_col( $wpdb->prepare( "SELECT meta_id FROM $wpdb->postmeta WHERE post_id = %d ", $post_id ));
	
	if ( ! empty($post_meta_ids) )
	{
		do_action( 'delete_postmeta', $post_meta_ids );
		$in_post_meta_ids = "'" . implode("', '", $post_meta_ids) . "'";
		$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_id IN($in_post_meta_ids)" );
		do_action( 'deleted_postmeta', $post_meta_ids );
	}

	do_action( 'delete_post', $post_id );
	$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->posts WHERE ID = %d", $post_id ));
	do_action( 'deleted_post', $post_id );

	clean_post_cache($post_id);

	return $post;
}



/**
 * Cancels deletion of the actual _file_ by returning an empty string as file path
 * if the deleted attachment had copies or was a copy itself
 */
function file_gallery_cancel_file_deletion_if_attachment_copies( $file )
{
	global $wpdb;

	if( defined('FILE_GALLERY_SKIP_DELETE_CANCEL') && true === FILE_GALLERY_SKIP_DELETE_CANCEL ) {
		return $file;
	}
	
	$_file = $file;
	$was_original = true;
		
	// get '_wp_attached_file' value based on upload path
	if( false != get_option('uploads_use_yearmonth_folders') )
	{
		$_file = explode('/', $_file);
		$c = count($_file);
		
		$_file = $_file[$c-3] . '/' . $_file[$c-2] . '/' . $_file[$c-1];
	}
	else
	{
		$_file = basename($file);
	}
	
	// find all attachments that share the same file
	$this_copies = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT `post_id` 
			 FROM $wpdb->postmeta 
			 WHERE `meta_key` = '_wp_attached_file' 
			 AND `meta_value` = '%s'", 
			$_file
		)
	);
	
	if( is_array($this_copies) && ! empty($this_copies) )
	{
		foreach( $this_copies as $tc ) // determine if original was deleted
		{
			if( '' != get_post_meta($tc, '_has_copies', true) ) {
				$was_original = false;
			}
		}

		if( $was_original ) { // original is deleted, promote first copy
			$promoted_id = file_gallery_promote_first_attachment_copy(0, $this_copies);
		}
		
		$uploadpath = wp_upload_dir();
		$file_path  = path_join($uploadpath['basedir'], $_file);
		
		if( file_gallery_file_is_displayable_image($file_path) ) { // if it's an image - regenerate its intermediate sizes
			$regenerate = wp_update_attachment_metadata($promoted_id, wp_generate_attachment_metadata($promoted_id, $file_path));
		}

		return '';
	}
	
	return $file;
}
add_filter('wp_delete_file', 'file_gallery_cancel_file_deletion_if_attachment_copies');



/**
 * Deletes all the copies of the original attachment (database data only, not files)
 */
function file_gallery_delete_all_attachment_copies( $attachment_id )
{
	$copies = get_post_meta($attachment_id, '_has_copies', true);
	
	if( is_array($copies) && ! empty($copies) )
	{
		do_action('file_gallery_delete_all_attachment_copies', $attachment_id, array(&$copies));
		
		foreach( $copies as $copy )
		{
			file_gallery_delete_attachment( $copy );
		}
		
		return $copies;
	}
	
	// no copies
	return false;
}



function file_gallery_handle_deleted_attachment( $post_id )
{
	$is_copy_of = get_post_meta($post_id, '_is_copy_of', true);

	if( ! empty($is_copy_of) && is_numeric($is_copy_of) && $copies = get_post_meta($is_copy_of, '_has_copies', true) )
	{
		foreach( $copies as $k => $v )
		{
			if( (int) $post_id === (int) $v ) {
				unset($copies[$k]);
			}
		}
		
		if( empty($copies) ) {
			delete_post_meta($is_copy_of, '_has_copies');
		}
		else {
			update_post_meta($is_copy_of, '_has_copies', $copies);
		}
	}
}
add_action('delete_attachment',              'file_gallery_handle_deleted_attachment');
add_action('file_gallery_delete_attachment', 'file_gallery_handle_deleted_attachment');



/**
 * Promotes the first copy of an attachment (probably to be deleted)
 * into the original (with other copies becoming its copies now)
 */
function file_gallery_promote_first_attachment_copy( $attachment_id, $copies = false )
{
	if( false === $copies ) {
		$copies = get_post_meta($attachment_id, '_has_copies', true);
	}
	
	if( is_array($copies) && ! empty($copies) )
	{
		$promoted_id = array_shift($copies);
		do_action('file_gallery_promote_first_attachment_copy', $attachment_id, array(&$promoted_id));
		delete_post_meta($promoted_id, '_is_copy_of');

		if( ! empty($copies) )
		{
			// update promoted attachments meta
			add_post_meta($promoted_id, '_has_copies', $copies);
			
			// update copies' meta
			foreach( $copies as $copy )
			{
				update_post_meta($copy, '_is_copy_of', $promoted_id);
			}
		}
		
		return $promoted_id;
	}
	
	// no copies
	return false;
}
