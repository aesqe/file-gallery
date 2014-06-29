<?php

function file_gallery_add_media_actions( $actions, $post )
{
	if( ! isset($actions['sis-regenerate']) && ! isset($action['regenerate_thumbnails']) && file_is_displayable_image(get_attached_file($post->ID)) ) {
		$actions['file_gallery_regenerate'] = '<a href="#" id="file_gallery_regenerate-' . $post->ID . '" class="file_gallery_regenerate_link">' . __('Regenerate', 'file-gallery') . '</a>';
	}

	return $actions;
}
add_filter( 'media_row_actions', 'file_gallery_add_media_actions', 1000, 2 );


function file_gallery_regenerate_thumbnails( $attachment_ids = "" )
{
	$data = array('errors' => array(), 'success' => array(), 'message' => 'aye');

	if( $attachment_ids === "" && isset($_REQUEST['attachment_ids']) )
	{
		check_ajax_referer('file_gallery_regenerate_nonce');
		$attachment_ids = $_REQUEST['attachment_ids'];
	}

	if( empty($attachment_ids) ) {
		$data['errors'][] =  __('No valid attachment IDs were supplied!', 'file-gallery');
	}

	foreach( $attachment_ids as $aid )
	{
		$fullsizepath = get_attached_file($aid);

		@set_time_limit( 120 ); // 2 minutes per image should be PLENTY

		$metadata = wp_generate_attachment_metadata( $aid, $fullsizepath );

		if( is_wp_error($metadata) ) {
			$data['errors'][] = sprintf( __('Error: %s while regenerating image ID %d', 'file-gallery'), $metadata->get_error_message(), $aid);
		}
		elseif( empty($metadata) ) {
			$data['errors'][] = sprintf( __('Unknown error while regenerating image ID %d', 'file-gallery'), $aid);
		}
		else {
			$data['success'][] = $aid;
		}

		// If this fails, then it just means that nothing was changed (old value == new value)
		wp_update_attachment_metadata( $aid, $metadata );
	}

	if( empty($data['errors']) )
	{
		if( count($attachment_ids) === 1 ) {
			$data['message'] = __('Attachment thumbnails were successfully regenerated', 'file-gallery');
		}
		else {
			$data['message'] = __("All attachments' thumbnails were successfully regenerated", 'file-gallery');
		}
	}
	else
	{
		if( ! empty($data['success']) ) {
			$data['message'] = __("There were errors and some of the attachments' thumbnails weren't successfully regenerated!", 'file-gallery');
		}
		else {
			$data['message'] = __("There were errors and none of the attachments' thumbnails were successfully regenerated!", 'file-gallery');
		}
	}

	header('Content-type: application/json');

	echo json_encode($data);

	exit();
}
add_action('wp_ajax_file_gallery_regenerate_thumbnails', 'file_gallery_regenerate_thumbnails');

