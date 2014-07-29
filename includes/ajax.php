<?php

function file_gallery_init()
{
	global $_wp_additional_image_sizes, $thumb_width, $thumb_height, $thumb_id, $_fg_upload_dir;

	check_ajax_referer('file-gallery');

	$options = get_option('file_gallery');
	$out = array();
	$post_id = (int) $_GET['post_id'];

	$thumb_id = (int) get_post_meta( $post_id, '_thumbnail_id', true );
	$thumb_size  = isset($options["default_metabox_image_size"]) ? $options["default_metabox_image_size"] : 'thumbnail';
	$thumb_width = isset($options["default_metabox_image_width"]) && 0 < $options["default_metabox_image_width"] ? $options["default_metabox_image_width"] : 75;

	if( isset($_wp_additional_image_sizes[$thumb_size]) )
	{
		$ats_width  = $_wp_additional_image_sizes[$thumb_size]['width'];
		$ats_height = $_wp_additional_image_sizes[$thumb_size]['height'];
	}
	else
	{
		$ats_width  = get_option($thumb_size . '_size_w');
		$ats_height = get_option($thumb_size . '_size_h');
	}

	$thumb_ratio = ((int) $ats_width > 0 && (int) $ats_height > 0) ? ($ats_width / $ats_height) : 1;

	if( (string)($thumb_ratio) == '' ) {
		$thumb_ratio = 1;
	}

	$thumb_height = $thumb_width / $thumb_ratio;

	$_fg_upload_dir = wp_upload_dir();

	$query = array(
		  'post_parent' => $post_id,
		  'post_type' => 'attachment',
		  'post_status' => 'inherit',
		  'posts_per_page' => 9999,
		  'orderby' => 'menu_order',
		  'order' => 'ASC'
	);

	$attachments = get_posts( $query );

	foreach( $attachments as $a )
	{
		$out[] = file_gallery_get_attachment_ajax_data($a);
	}

	$media_tags = file_gallery_list_tags(array('type' => 'json', 'echo' => false));

	if( is_array($media_tags) && empty($media_tags) ) {
		$media_tags = '[{}]';
	}

	$media_tags = substr($media_tags, 1);
	$media_tags = substr($media_tags, 0, -1);
	$media_tags = json_decode($media_tags);

	$options['thumbWidth'] = (int) $thumb_width;
	$options['thumbHeight'] = (int) $thumb_height;

	echo json_encode( array('attachments' => $out, 'mediaTags' => $media_tags, 'options' => $options) );

	exit();
}
add_action('wp_ajax_file_gallery_init', 'file_gallery_init');



function file_gallery_get_attachment_ajax_data( $a )
{
	global $thumb_width, $thumb_height, $thumb_id, $_fg_upload_dir;

	if( ! is_object($a) && is_numeric($a) ) {
		$a = get_post((int) $a);
	}

	$itemClasses = array();

	$a->currentUserCanEdit = current_user_can('edit_post', $a->ID);
	$a->meta = wp_get_attachment_metadata($a->ID);
	$hasCopies = maybe_unserialize( get_post_meta($a->ID, '_has_copies', true) );
	$isCopyOf = get_post_meta( $a->ID, '_is_copy_of', true );

	$mimeType = str_replace('/', '-', $a->post_mime_type);
	$itemClasses[] = $mimeType;
	$mimeType = explode('-', $mimeType);
	$itemClasses[] = $mimeType[0];

	$file_url = wp_get_attachment_url($a->ID); // http://.../wp-content/uploads/.../filename
	$file_url_basename = wp_basename($file_url); // filename
	$a->baseUrl = str_replace($file_url_basename, '', $file_url); // http://.../wp-content/uploads/.../

	$a->itemWidth = $thumb_width . 'px';
	$a->itemHeight = $thumb_height . 'px';

	if( file_gallery_file_is_displayable_image( get_attached_file($a->ID) ) )
	{
		$a->isImage = true;
		$a->isPostThumb = ($thumb_id === $a->ID);
		$a->iconWidth = $thumb_width . 'px';
		$a->iconHeight = $thumb_height . 'px';

		$file = $file_url_basename;
		$a->file = $file_url_basename;
		$a->meta['file'] = $file;

		$zoomSrc = array(
			'file' => $file_url,
			'width' => $a->meta['width'],
			'height' => $a->meta['height']
		);

		if( isset($a->meta['sizes']) && isset($a->meta['sizes']['thumbnail']) ) {
			$file = $a->meta['sizes']['thumbnail']['file'];
		}

		if( isset($a->meta['sizes']) && isset($a->meta['sizes']['large']) ) {
			$zoomSrc = $a->meta['sizes']['large'];
			$zoomSrc['file'] = $a->baseUrl . $zoomSrc['file'];
		}
		elseif( isset($a->meta['sizes']) && isset($a->meta['sizes']['medium']) ) {
			$zoomSrc = $a->meta['sizes']['medium'];
			$zoomSrc['file'] = $a->baseUrl . $zoomSrc['file'];
		}

		$a->zoomSrc = $zoomSrc;
		$a->icon = $a->baseUrl . $file;
		$a->imageAltText = get_post_meta($a->ID, '_wp_attachment_image_alt', true);
	}
	else
	{
		$a->isImage = false;
		$a->isPostThumb = false;
		$a->icon = file_gallery_https( wp_mime_type_icon($a->ID) );
		$a->iconWidth = '48px';
		$a->iconHeight = '64px';
		$itemClasses[] = 'non-image';

		$a->zoomSrc = array(
			'file' => $a->icon,
			'width' => 55,
			'height' => 64
		);
	}

	if( is_array($hasCopies) && count($hasCopies) > 0 )
	{
		$itemClasses[] = 'has_copies';
		$a->hasCopies = $hasCopies;
	}
	else {
		$a->hasCopies = false;
	}

	if( is_numeric($isCopyOf) && $isCopyOf > 0 )
	{
		$itemClasses[] = 'copy';
		$a->isCopyOf = (int) $isCopyOf;
	}
	else {
		$a->isCopyOf = false;
	}

	$a->selected = false;
	$a->itemClasses = implode(' ', $itemClasses);

	$a->mediaTags = array();
	$tags = wp_get_object_terms( $a->ID, FILE_GALLERY_MEDIA_TAG_NAME );

	foreach( $tags as $tag )
	{
		$a->mediaTags[] = $tag->name;
	}

	$a->post_date_formatted = date(get_option('date_format'), strtotime($a->post_date));

	$post_author = get_userdata($a->post_author);
	$a->post_author_formatted = $post_author->user_nicename;

	$a->customFieldsTable = '';

	$a->permalink = get_permalink($a->ID);
	$a->post_parent_permalink = get_permalink($a->post_parent);

	unset($a->comment_count);
	unset($a->comment_status);
	unset($a->filter);
	unset($a->guid);
	unset($a->post_content_filtered);
	unset($a->post_type);
	unset($a->post_status);
	unset($a->ping_status);
	unset($a->pinged);
	unset($a->to_ping);

	return $a;
}


function file_gallery_get_attachments_by_id()
{
	$post_ids = array_map('intval', $_GET['attachment_ids']);

	$query = array(
		  'post__in' => $post_ids,
		  'post_type' => 'attachment',
		  'post_status' => 'inherit',
		  'posts_per_page' => 9999,
		  'orderby' => 'post__in',
		  'order' => 'ASC'
	);

	$q = new WP_Query( $query );
	$attachments = $q->posts;

	foreach( $attachments as $a )
	{
		$out[] = file_gallery_get_attachment_ajax_data($a);
	}

	wp_send_json( $out );
}
add_action('wp_ajax_file_gallery_get_attachments_by_id', 'file_gallery_get_attachments_by_id');



/**
 * Media library extensions
 */
function file_gallery_filter_duplicate_attachments( $where )
{
	global $wpdb, $pagenow;

	$options = get_option('file_gallery');
	$filter_duplicates = (isset($options["library_filter_duplicates"]) && $options["library_filter_duplicates"] == true);

	if( isset($_REQUEST['action']) && $_REQUEST['action'] == 'query-attachments' )
	{
		$parent_post = $_REQUEST['post_id'];

		if( ! empty($parent_post) )
		{
			$where .= " AND (($wpdb->posts.ID NOT IN ( SELECT ID FROM $wpdb->posts AS posts INNER JOIN $wpdb->postmeta AS meta ON meta.post_id = posts.ID WHERE meta.meta_key = '_is_copy_of' )) OR ($wpdb->posts.post_parent=" . $parent_post . "))";

			$where .= " AND ($wpdb->posts.ID NOT IN ( SELECT meta.meta_value FROM $wpdb->posts AS posts INNER JOIN $wpdb->postmeta AS meta ON meta.post_id = posts.ID WHERE meta.meta_key = '_is_copy_of' AND posts.post_parent=" . $parent_post . "))";
		}
	}
	else if( is_admin() )
	{
		// affect the query only if we're on a certain page
		if( $pagenow == "media-upload.php" && $_GET["tab"] == "library" && is_numeric($_GET['post_id']) )
		{
			if( isset($_GET['exclude']) && $_GET['exclude'] == "current" ) {
				$where .= " AND `post_parent` != " . (int) $_GET["post_id"] . " ";
			}

			if( $filter_duplicates ) {
				$where .= " AND $wpdb->posts.ID NOT IN ( SELECT ID FROM $wpdb->posts AS ps INNER JOIN $wpdb->postmeta AS pm ON pm.post_id = ps.ID WHERE pm.meta_key = '_is_copy_of' ) ";
			}
		}
		elseif( $pagenow == "upload.php" && $filter_duplicates )
		{
			$where .= " AND $wpdb->posts.ID NOT IN ( SELECT ID FROM $wpdb->posts AS ps INNER JOIN $wpdb->postmeta AS pm ON pm.post_id = ps.ID WHERE pm.meta_key = '_is_copy_of' ) ";
		}
	}	

	return $where;
}
add_filter('posts_where', 'file_gallery_filter_duplicate_attachments');



function file_gallery_filter_ajax_query_attachments_args( $query )
{
	//echo json_encode($_REQUEST);

	//unset($query->post_mime_type);

	return $query;
}
add_filter('ajax_query_attachments_args', 'file_gallery_filter_ajax_query_attachments_args');



function file_gallery_get_attachment_post_custom_html( $attachment_id = null )
{
	ob_start();
		file_gallery_attachment_custom_fields_table($attachment_id);
		$_buffer = ob_get_contents();
	ob_end_clean();

	return $_buffer;
}



function file_gallery_ajax_attachment_post_custom_html()
{
	check_ajax_referer('file-gallery');

	file_gallery_attachment_custom_fields_table((int) $_POST['post_id']);
	exit;
}
add_action('wp_ajax_file_gallery_get_acf', 'file_gallery_ajax_attachment_post_custom_html');



/**
 * returns a list of media tags found in db
 */
function file_gallery_list_tags( $args = array() )
{
	global $wpdb;
	
	$list = array();
	
	extract(
		wp_parse_args(
			$args, 
			array(
				"type" => "html",
				"echo" => true,
				"link" => true,
				"slug" => false,
				"separator" => ", "
	)));
	
	$options = get_option("file_gallery");
	
	$media_tag_tax  = get_taxonomy(FILE_GALLERY_MEDIA_TAG_NAME);
	$media_tag_slug = $media_tag_tax->rewrite["slug"];

	if( isset($options["cache"]) && true == $options["cache"] )
	{
		$transient = "filegallery_mediatags_" . $type;
		$cache     = get_transient($transient);
		
		if( $cache )
		{
			if( ! $echo )
				return $cache;

			echo $cache;	
			return;
		}
	}

	$media_tags = $wpdb->get_results(		 
		"SELECT * FROM $wpdb->terms 
		 LEFT JOIN $wpdb->term_taxonomy ON ( $wpdb->terms.term_id = $wpdb->term_taxonomy.term_id ) 
		 LEFT JOIN $wpdb->term_relationships ON ( $wpdb->term_taxonomy.term_taxonomy_id = $wpdb->term_relationships.term_taxonomy_id ) 
		 WHERE $wpdb->term_taxonomy.taxonomy = '" . FILE_GALLERY_MEDIA_TAG_NAME . "'
		 GROUP BY $wpdb->terms.term_id
		 ORDER BY `name` ASC"
	);

	if( !empty($media_tags) )
	{
		if( "array" == $type || "json" == $type )
		{
			foreach( $media_tags as $tag )
			{
				$list[] = array(
					"term_id" => $tag->term_id,
					"name" => $tag->name,
					"slug" => $tag->slug,
					"term_group" => $tag->term_group,
					"term_taxonomy_id" => $tag->term_taxonomy_id,
					"taxonomy" => $tag->taxonomy,
					"description" => $tag->description,
					"parent" => $tag->parent,
					"count" => $tag->count,
					"object_id" => $tag->object_id,
					"term_order" => $tag->term_order
				);
			}
			
			if( "json" == $type )
				$list = "{" . json_encode($list) . "}";
		}
		elseif( "object" == $type )
		{
			$list = $media_tags;
		}
		else // html
		{
			if( $link )
			{
				global $wp_rewrite;
				
				$taglink = $wp_rewrite->get_tag_permastruct();
				
				$fs = "/";
				$ss = "/";
				$ts = "/";
				
				if( "" == $taglink )
				{
					$fs = "?";
					$ss = "=";
					$ts = "";
				}

				foreach( $media_tags as $tag )
				{						
					$list[] = '<a href="' . file_gallery_https( get_bloginfo('url') ) . $fs . $media_tag_slug . $ss . $tag->slug . $ts . '" class="fg_insert_tag" name="' . $tag->slug . '">' . $tag->name . '</a>';
				}
			}
			else
			{
				if( $slug )
					$whattag = "slug";
				else
					$whattag = "name";

				foreach( $media_tags as $tag )
				{
					$list[] = $tag->{$whattag};
				}
			}
		}
	}

	if( $echo && "html" == $type )
		$list = implode($separator, $list);
	
	if( isset($options["cache"]) && true == $options["cache"] )
		set_transient($transient, $list, $options["cache_time"]);
	
	if( $echo )
		echo $list;
	else
		return $list;
}



function file_gallery_main()
{
	global $wpdb;

	check_ajax_referer('file-gallery');

	$action = isset($_POST['action']) ? $_POST['action'] : '';
	$delete_what = isset($_POST['delete_what']) ? $_POST['delete_what'] : '';
	$attachment_ids = isset($_POST['attachment_ids']) ? $_POST['attachment_ids'] : array();

	$output = array('success' => false, 'error' => false, 'message' => '', 'info' => array() );

	if( $action == '' || $attachment_ids == '' ) {
		$output['error'] = array('Not enough data', $action, $attachment_ids);
	}

	if( ! is_array($attachment_ids) || empty($attachment_ids) ) {
		exit('No attachments supplied');
	}

	$attachment_ids = array_map('intval', $attachment_ids);

	if( $action == 'file_gallery_main_delete' )
	{
		if( $delete_what == '' )
		{
			$output['error'] = array('Not enough data: delete_what', $delete_what);
		}
		else
		{
			$query = new WP_Query( array('post_type' => 'attachment', 'post_status' => 'inherit', 'post__in' => $attachment_ids) );
			$attachments = $query->posts;
			
			// cancel our own 'wp_delete_attachment' filter
			define('FILE_GALLERY_SKIP_DELETE_CANCEL', true);

			foreach( $attachments as $attachment )
			{
				if( current_user_can('delete_post', $attachment) )
				{
					$aid = $attachment->ID;
					$isCopyOf = get_post_meta($aid, '_is_copy_of', true);
					$hasCopies = maybe_unserialize( get_post_meta($aid, '_has_copies', true) );

					if( is_numeric($isCopyOf) )
					{
						file_gallery_delete_attachment( $aid );
						$partially_deleted[] = $aid;
					}
					else if( is_array($hasCopies) && is_numeric($hasCopies[0]) )
					{
						if( $delete_what == 'all' )
						{
							file_gallery_delete_all_attachment_copies( $aid );
							wp_delete_attachment( $aid );
							$fully_deleted[] = $aid;
							$output['info'][] = 'fully_deleted ' . $attachment->ID;
						}
						elseif( $delete_what == 'data_only' )
						{
							file_gallery_promote_first_attachment_copy( $aid );
							file_gallery_delete_attachment( $aid );
							$partially_deleted[] = $aid;
							$output['info'][] = 'partially_deleted ' . $attachment->ID;
						}
					}
					else
					{
						wp_delete_attachment( $aid );
						$fully_deleted[] = $aid;

						$output['info'][] = 'fully_deleted ' . $attachment->ID;
					}
				}
				else 
				{
					$output['info'][] = 'Cannot delete ' . $attachment->ID;
				}
			}
			
			if( empty($fully_deleted) && empty($partially_deleted) )
			{
				$output['message'] = __("No attachments were deleted (capabilities?)", "file-gallery");
			}
			else
			{
				$output['message'] = __("Attachment(s) deleted", "file-gallery");
				$output['success'] = true;
			}
		}
	}
	elseif( $action == 'file_gallery_main_detach' )
	{
		foreach( $attachment_ids as $attachment_id )
		{
			if( false === $wpdb->query( sprintf("UPDATE $wpdb->posts SET `post_parent`='0' WHERE `ID`='%d'", $attachment_id) ) )
				$detach_errors[] = $attachment_id;
		}

		if( empty($detach_errors) )
		{
			$output['message'] = __("Attachment(s) detached", "file-gallery");
			$output['success'] = true;
		}
		else
		{
			$output['message'] = __("Error detaching attachment(s)", "file-gallery");
		}
	}

	echo json_encode($output);
	exit();
}
add_action('wp_ajax_file_gallery_main_delete', 'file_gallery_main');
add_action('wp_ajax_file_gallery_main_detach', 'file_gallery_main');



function file_gallery_ajax_update_attachment_data()
{
	check_ajax_referer('file-gallery');
	
	$attachment_id = (int) $_POST['attachment_id'];

	$new = array();
	$old = array();

	$new['ID'] = $attachment_id;
	$new['post_alt'] = esc_html($_POST['post_alt']);
	$new['post_title'] = esc_attr($_POST['post_title']);
	$new['post_content'] = esc_html($_POST['post_content']);
	$new['post_excerpt'] = esc_html($_POST['post_excerpt']);
	$new['menu_order'] = (int) $_POST['menu_order'];

	$output = __('Error updating attachment data!', 'file-gallery');
	
	// attachment custom fields
	$custom = get_post_custom($attachment_id);
	$custom_fields = isset($_POST['custom_fields']) ? $_POST['custom_fields'] : '';
	
	if( ! empty($custom) && ! empty($custom_fields) )
	{
		foreach( $custom_fields as $key => $val )
		{
			if( isset($custom[$key]) && $custom[$key][0] != $val ) {
				update_post_meta($attachment_id, $key, $val);
			}
		}
	}
	
	// attachment media tags
	$tax_input = '';
	$old_media_tags = '';
	$get_old_media_tags = wp_get_object_terms($attachment_id, FILE_GALLERY_MEDIA_TAG_NAME);
	
	if( ! empty($get_old_media_tags) )
	{
		foreach( $get_old_media_tags as $mt )
		{
			$old_media_tags[] = $mt->name;
		}
		
		$old_media_tags = implode(', ', $old_media_tags);
	}
	
	if( ! empty($_POST['tax_input']) && $old_media_tags != $_POST['tax_input'] )
	{
		$tax_input = preg_replace("#\s+#", ' ', $_POST['tax_input']);
		$tax_input = preg_replace("#,+#", ',', $_POST['tax_input']);
		$tax_input = trim($tax_input, ' ');
		$tax_input = trim($tax_input, ',');
		$tax_input = explode(', ', $tax_input);
		
		$media_tags_result = wp_set_object_terms( $attachment_id, $tax_input, FILE_GALLERY_MEDIA_TAG_NAME );
	}
	elseif( empty($_POST['tax_input']) )
	{
		$media_tags_result = wp_set_object_terms( $attachment_id, NULL, FILE_GALLERY_MEDIA_TAG_NAME );
	}
	
	// check if there were any changes
	$old = get_object_vars( get_post($attachment_id) );
	
	if( file_gallery_file_is_displayable_image(  get_attached_file($attachment_id) ) ) {
		$old['post_alt'] = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
	}
	
	if( (isset($old['post_alt']) && $old['post_alt'] != $new['post_alt']) ||  
	     $old['post_title'] != $new['post_title'] || 
		 $old['post_content'] != $new['post_content'] || 
		 $old['post_excerpt'] != $new['post_excerpt'] ||	
		 $old['menu_order'] != $new['menu_order'] ||
		 is_array($tax_input)
	)
	{
		if( wp_update_post($new) !== 0 )
		{
			update_post_meta($attachment_id, '_wp_attachment_image_alt', $new['post_alt']);
			$output = __('Attachment data updated', 'file-gallery');
		}
	}
	else
	{
		$output = __('No change.', 'file-gallery');
	}

	echo $output;
	exit;
}
add_action('wp_ajax_file_gallery_update_attachment', 'file_gallery_ajax_update_attachment_data');



/**
 * saves attachment order using "menu_order" field
 *
 * @since 1.0
 */
function file_gallery_save_menu()
{
	global $wpdb;
	
	$updates = '';
	
	check_ajax_referer('file-gallery');

	$order = $_POST['attachment_order'];

	array_walk($order, function (&$value, $index) {
		$value = (int) $value;
	});

	foreach($order as $mo => $ID)
	{
		$updates .= sprintf(" WHEN %d THEN %d ", $ID, $mo);
	}
	
	if( false !== $wpdb->query("UPDATE $wpdb->posts SET `menu_order` = CASE `ID` " . $updates . " ELSE `menu_order` END") )
	{
		echo __('Attachment order saved successfully.', 'file-gallery');
	}
	else
	{
		$error = __('Database error! Function: file_gallery_save_menu', 'file-gallery');
		file_gallery_write_log( $error );
		echo $error;
	}
	
	exit();
}
add_action('wp_ajax_file_gallery_save_menu_order', 'file_gallery_save_menu');



/**
 * saves state of gallery and single file insertion options
 *
 * @since 1.5
 */
function file_gallery_save_toggle_state()
{
	check_ajax_referer('file-gallery');
	
	$options = get_option('file_gallery');
	$opt = 'insert_options_state';
	
	switch( $_POST['action'] )
	{
		case 'file_gallery_save_single_toggle_state' :
			$opt = 'insert_single_options_state';
			break;
		case 'file_gallery_save_acf_toggle_state' :
			$opt = 'acf_state';
			break;
		case 'file_gallery_toggle_textual' :
			$opt = 'textual_mode';
			break;
		default : 
			break;
	}
	
	$options[$opt] = (int) $_POST['state'];
	
	update_option('file_gallery', $options);
	
	exit();
}
add_action('wp_ajax_file_gallery_save_toggle_state', 'file_gallery_save_toggle_state');
add_action('wp_ajax_file_gallery_save_single_toggle_state', 'file_gallery_save_toggle_state');
add_action('wp_ajax_file_gallery_save_acf_toggle_state', 'file_gallery_save_toggle_state');
add_action('wp_ajax_file_gallery_toggle_textual', 'file_gallery_save_toggle_state');
