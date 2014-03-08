<?php

/**
 * Returns current post's attachments
 */
function file_gallery_list_attachments(&$count_attachments, $post_id, $attachment_order, $checked_attachments, $attachment_orderby = 'menu_order' )
{
	global $wpdb, $_wp_additional_image_sizes;
	
	$options = get_option('file_gallery');
	$thumb_id = false;
	$attached_files = '';
	
	if( '' != $attachment_order && false !== strpos($attachment_order, ',') )
	{
		$query = "SELECT * FROM $wpdb->posts 
			 WHERE post_parent = " . $post_id . "
			 AND post_type = 'attachment' 
			 ORDER BY FIELD(ID, " . $attachment_order . ") ";

		$attachments = $wpdb->get_results( $query );
	}
	else
	{
		if( 'DESC' != $attachment_order )
			$attachment_order = 'ASC';
		
		if( ! in_array($attachment_orderby, array('post_title', 'post_name', 'post_date', 'menu_order')) )
			$attachment_orderby = 'menu_order';
		
		$query = array(
			  'post_parent' => $post_id, 
			  'post_type' => 'attachment', 
			  'order' => $attachment_order, 
			  'orderby' => $attachment_orderby,
			  'post_status' => 'inherit'
		);
		
		$attachments = get_children( $query );
	}

	if( $attachments )
	{
		$count_attachments = count($attachments);
		$thumb_id = get_post_meta( $post_id, '_thumbnail_id', true );
		$attachment_thumb_size  = isset($options["default_metabox_image_size"]) ? $options["default_metabox_image_size"] : 'thumbnail';
		$attachment_thumb_width = isset($options["default_metabox_image_width"]) && 0 < $options["default_metabox_image_width"] ? $options["default_metabox_image_width"] : 75;	
		
		if( isset($_wp_additional_image_sizes[$attachment_thumb_size]) )
		{
			$ats_width  = $_wp_additional_image_sizes[$attachment_thumb_size]['width'];
			$ats_height = $_wp_additional_image_sizes[$attachment_thumb_size]['height'];
		}
		else
		{
			$ats_width  = get_option($attachment_thumb_size . '_size_w');
			$ats_height = get_option($attachment_thumb_size . '_size_h');
		}

		$attachment_thumb_ratio = 0 < (int) $ats_width && 0 < (int) $ats_height ? $ats_width / $ats_height : 1;
			
		if( '' == (string)($attachment_thumb_ratio) )
			$attachment_thumb_ratio = 1;

		$attachment_thumb_height = $attachment_thumb_width / $attachment_thumb_ratio;
		
		// start the list...
		$attached_files = '<ul class="ui-sortable' . (($options['textual_mode']) ? ' textual' : '') . '" id="file_gallery_list">' . "\n";
		
		foreach( $attachments as $attachment )
		{
			$classes = array('sortableitem');
			$post_thumb_link = 'set';
			$non_image = '';
			$checked = '';
			$file_type = '';
			
			if ( preg_match( '/^.*?\.(\w+)$/', get_attached_file( $attachment->ID ), $matches ) )
				$file_type = esc_html( strtoupper( $matches[1] ) );
			else
				$file_type = strtoupper( str_replace( 'image/', '', $attachment->post_mime_type ) );
			
			$original_id = get_post_meta($attachment->ID, '_is_copy_of', true);
			$copies 	 = get_post_meta($attachment->ID, '_has_copies', true);
			$cj = json_encode($copies);

			if( '' != (string)($original_id) )
				$classes[] = 'copy copy-of-' . $original_id;
			elseif( $copies && $cj != '[]' )
				$classes[] = 'has_copies copies-' . implode('-', $copies);
			
			if( (int) $thumb_id === (int) $attachment->ID )
			{
				$classes[]       = 'post_thumb';
				$post_thumb_link = 'unset';
			}
			
			$attachment_thumb = wp_get_attachment_image_src($attachment->ID, $attachment_thumb_size);
			$large            = wp_get_attachment_image_src($attachment->ID, 'large');

			if( in_array($attachment->ID, $checked_attachments) )
			{
				$checked = ' checked="checked"';
				$classes[] = 'selected';
			}
			
			// if it's not an image...
			if( '' == $attachment_thumb )
			{
				$attachment_thumb    = array( 0 => file_gallery_https( wp_mime_type_icon($attachment->ID) ) );
				$attachment_width    = '';
				$attachment_height   = '';
				$non_image           = ' non_image';
				$_attachment_thumb_width = 55;
				$image_width_style = '';
				$classes[] = 'non-image';
			}
			else
			{
				$forced_height = '';
				$classes[] = 'image';
				$_attachment_thumb_width = $attachment_thumb_width;
				
				if( 1 === $attachment_thumb_ratio && $attachment_thumb[2] > $attachment_thumb_width )
					$forced_height = 'height: ' . $attachment_thumb_height . 'px';

				$image_width_style = 'style="width: ' . $_attachment_thumb_width . 'px; ' . $forced_height . '"';
			}

			$attached_files .= '
			<li id="image-' . $attachment->ID . '" class="' . implode(' ', $classes) . '" style="width: ' . $_attachment_thumb_width . 'px; height: ' . $attachment_thumb_height . 'px" title="[' . $attachment->ID . '] ' . $attachment->post_title . ' [' . $file_type . ']">
			
			<img src="' . $attachment_thumb[0] . '" alt="' . $attachment->post_title . '" id="in-' . $attachment->ID . '" title="' . $attachment->post_title . '" class="fgtt' . $non_image . '" ' . $image_width_style . ' />
			
			<span class="attachment-title">' . $attachment->post_title . '</span>';
				
			if( "" == $non_image )
			{
				$attached_files .= '<a href="' . $large[0] . '" id="in-' . $attachment->ID . '-zoom" class="img_zoom action">
					<img src="' . file_gallery_https( FILE_GALLERY_URL ) . '/images/famfamfam_silk/magnifier.png" alt="' . __("Zoom", "file-gallery") . '" title="' . __("Zoom", "file-gallery") . '" />
				</a>';
			}
				
			$attached_files .= '<a href="#" id="in-' . $attachment->ID . '-edit" class="img_edit action">
				<img src="' . file_gallery_https( FILE_GALLERY_URL ) . '/images/famfamfam_silk/pencil.png" alt="' . __("Edit", "file-gallery") . '" title="' . __("Edit", "file-gallery") . '" />
			</a>
			<span class="checker_action action" title="' . __('Click to select, or click and drag to change position', 'file-gallery') . '">
				<input type="checkbox" id="att-chk-' . $attachment->ID . '" class="checker"' . $checked . ' title="' . __("Click to select", "file-gallery") . '" />
			</span>';
		
			if( current_user_can('edit_post', $attachment->ID) )
			{				
				if( '' == $non_image && function_exists('current_theme_supports') && current_theme_supports('post-thumbnails') )
				{
					$as_featured = "set" == $post_thumb_link ? __('Set as featured image', 'file-gallery') : __('Unset as featured image', 'file-gallery');
				
					$attached_files .= '<a href="#" class="post_thumb_status action" rel="' . $attachment->ID . '">
							<img src="' . file_gallery_https( FILE_GALLERY_URL ) . '/images/famfamfam_silk/star_' . $post_thumb_link . '.png" alt="' . $as_featured . '" title="' . $as_featured . '" />
						</a>';				
				}
	
				$attached_files .= '<a href="#" class="delete_or_detach_link action" rel="' . $attachment->ID . '">
					<img src="' . file_gallery_https( FILE_GALLERY_URL ) . '/images/famfamfam_silk/delete.png" alt="' . __("Detach / Delete", "file-gallery") . '" title="' . __("Detach / Delete", "file-gallery") . '" />
				</a>
				<div id="detach_or_delete_'  . $attachment->ID . '" class="detach_or_delete">
					<br />';
	
				if( current_user_can('delete_post', $attachment->ID) )
				{
					$attached_files .= '<a href="#" class="do_single_delete" rel="' . $attachment->ID . '">' . __("Delete", "file-gallery") . '</a>
						<br />
						' . __("or", "file-gallery") . '
						<br />';
				}
					
				$attached_files .= '<a href="#" class="do_single_detach" rel="' . $attachment->ID . '">' . __("Detach", "file-gallery") . '</a>
				</div>
				<div id="detach_attachment_'  . $attachment->ID . '" class="detach_attachment">
					' . __("Really detach?", "file-gallery") . ' 
					<a href="#" id="detach[' . $attachment->ID . ']" class="detach">' . __("Continue", "file-gallery") . '</a>
					' . __("or", "file-gallery") . '
					<a href="#" class="detach_cancel" rel="' . $attachment->ID . '">' . __("Cancel", "file-gallery") . '</a>
				</div>';
				
				if( current_user_can('delete_post', $attachment->ID) )
				{
					$attached_files .= '<div id="del_attachment_' . $attachment->ID . '" class="del_attachment">
						' . __("Really delete?", "file-gallery") . ' 
						<a href="#" id="del[' . $attachment->ID . ']" class="delete">' . __("Continue", "file-gallery") . '</a>
						' . __("or", "file-gallery") . '
						<a href="#" class="delete_cancel" rel="' . $attachment->ID . '">' . __("Cancel", "file-gallery") . '</a>
					</div>';
				}
			}
				
			$attached_files .= '</li>
				' . "\n";
		}
		
		//end the list...
		$attached_files .= "</ul>\n";
	}

	return $attached_files;
}



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



/**
 * Displays the main form for inserting shortcodes / single images.
 * also handles attachments edit/delete/detach response
 * and displays atachment thumbnails on post edit screen in admin
 */
function file_gallery_main( $ajax = true )
{
	global $wpdb;

	check_ajax_referer('file-gallery');

	$post_id			  = isset($_POST['post_id']) ? $_POST['post_id'] : '';
	$attachment_order	  = isset($_POST['attachment_order']) ? $_POST['attachment_order'] : '';
	$attachment_orderby	  = isset($_POST['attachment_orderby']) ? $_POST['attachment_orderby'] : '';
	$files_or_tags		  = isset($_POST['files_or_tags']) ? $_POST["files_or_tags"] : '';
	$tags_from			  = isset($_POST['tags_from']) ? $_POST["tags_from"] : '';
	$action				  = isset($_POST['action']) ? $_POST['action'] : '';
	$attachment_ids		  = isset($_POST['attachment_ids']) ? $_POST['attachment_ids'] : '';
	$attachment_data	  = isset($_POST['attachment_data']) ? $_POST['attachment_data'] : '';
	$delete_what          = isset($_POST['delete_what']) ? $_POST['delete_what'] : '';
	$checked_attachments  = isset($_POST['checked_attachments']) ? explode(',', $_POST['checked_attachments']) : array();
	$normals			  = isset($_POST['normals']) ? $_POST['normals'] : '';
	$copies				  = isset($_POST['copies']) ? $_POST['copies'] : '';
	$originals			  = isset($_POST['originals']) ? $_POST['originals'] : '';
	$fieldsets			  = isset($_POST['fieldsets']) ? $_POST['fieldsets'] : '';
	
	$file_gallery_options = get_option('file_gallery');
	
	$gallery_state		  = isset($file_gallery_options['insert_options_state']) && true == $file_gallery_options['insert_options_state'] ? true : false;
	$single_state		  = isset($file_gallery_options['insert_single_options_state']) && true == $file_gallery_options['insert_single_options_state'] ? true : false;
	$output               = "&nbsp;";
	$count_attachments    = 0;
	$hide_form            = '';
	$sizes                = file_gallery_get_intermediate_image_sizes();
	
	$normals   		= explode(',', $normals);
	$copies    		= explode(',', $copies);
	$originals 		= explode(',', $originals);
	$attachment_ids = explode(',', $attachment_ids);
	
	if( empty_array($normals) )
		$normals = array();
	
	if( empty_array($copies) )
		$copies = array();
	
	if( empty_array($originals) )
		$originals = array();
	
	if( empty_array($attachment_ids) )
		$attachment_ids = array();
	
	if( 'file_gallery_main_delete' == $action )
	{
		if( ! empty($copies) && ! empty($originals) )
		{
			$cpluso  = array_merge($copies, $originals);
			$normals = array_xor((array)$attachment_ids, $cpluso);
		}
		elseif( ! empty($copies) )
		{
			$normals = array_xor((array)$attachment_ids, $copies);
		}
		elseif( ! empty($originals) )
		{
			$normals = array_xor((array)$attachment_ids, $originals);
		}
		else
		{
			$normals = $attachment_ids;
		}
		
		// cancel our own 'wp_delete_attachment' filter
		define("FILE_GALLERY_SKIP_DELETE_CANCEL", true);
		
		foreach( $normals as $normal )
		{
			if( current_user_can('delete_post', $normal) )
			{
				wp_delete_attachment( $normal );
			
				$fully_deleted[] = $normal;
			}
		}
		
		foreach( $copies as $copy )
		{
			if( current_user_can('delete_post', $copy) )
			{
				file_gallery_delete_attachment( $copy );
				
				$partially_deleted[] = $copy;
			}
		}
		
		foreach( $originals as $original )
		{
			if( "all" == $delete_what && current_user_can('delete_post', $original) )
			{
				file_gallery_delete_all_attachment_copies( $original );
				wp_delete_attachment( $original );
				
				$fully_deleted[] = $original;
			}
			elseif( "data_only" == $delete_what && current_user_can('delete_post', $original) )
			{
				file_gallery_promote_first_attachment_copy( $original );
				file_gallery_delete_attachment( $original );
				
				$partially_deleted[] = $original;
			}
		}
		
		if( empty($fully_deleted) && empty($partially_deleted) )
			$output = __("No attachments were deleted (capabilities?)", "file-gallery");
		else
			$output = __("Attachment(s) deleted", "file-gallery");
	}
	elseif( "file_gallery_main_detach" == $action )
	{
		foreach( $attachment_ids as $attachment_id )
		{
			if( false === $wpdb->query( sprintf("UPDATE $wpdb->posts SET `post_parent`='0' WHERE `ID`='%d'", $attachment_id) ) )
				$detach_errors[] = $attachment_id;
		}

		if( empty($detach_errors) )
			$output = __("Attachment(s) detached", "file-gallery");
		else
			$output = __("Error detaching attachment(s)", "file-gallery");
	}
	elseif( "file_gallery_main_update" == $action )
	{
		$attachment_id = (int) $_POST['attachment_id'];

		$attachment_data['ID'] 			  = $attachment_id;
		$attachment_data['post_alt']      = $_POST['post_alt'];
		$attachment_data['post_title']    = $_POST['post_title'];
		$attachment_data['post_content']  = $_POST['post_content'];
		$attachment_data['post_excerpt']  = $_POST['post_excerpt'];
		$attachment_data['menu_order'] 	  = $_POST['menu_order'];
		
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
		
		// media_tag taxonomy - attachment tags
		$tax_input = "";
		$old_media_tags = "";
		
		$get_old_media_tags = wp_get_object_terms((int) $_POST['attachment_id'], FILE_GALLERY_MEDIA_TAG_NAME);
		
		if( !empty($get_old_media_tags) )
		{
			foreach( $get_old_media_tags as $mt )
			{
				$old_media_tags[] = $mt->name;
			}
			
			$old_media_tags = implode(", ", $old_media_tags);
		}
		
		if( "" != $_POST['tax_input'] && $old_media_tags != $_POST['tax_input'] )
		{
			$tax_input = preg_replace("#\s+#", " ", $_POST['tax_input']);
			$tax_input = preg_replace("#,+#", ",", $_POST['tax_input']);
			$tax_input = trim($tax_input, " ");
			$tax_input = trim($tax_input, ",");
			$tax_input = explode(", ", $tax_input);
			
			$media_tags_result = wp_set_object_terms( $attachment_id, $tax_input, FILE_GALLERY_MEDIA_TAG_NAME );
		}
		elseif( "" == $_POST['tax_input'] )
		{
			$media_tags_result = wp_set_object_terms( $attachment_id, NULL, FILE_GALLERY_MEDIA_TAG_NAME );
		}
		
		// check if there were any changes
		$old_attachment_data = get_object_vars( get_post($attachment_id) );
		
		if( file_gallery_file_is_displayable_image(  get_attached_file($attachment_id) ) )
			$old_attachment_data['post_alt'] = get_post_meta($attachment_id, "_wp_attachment_image_alt", true);
		
		if( ( isset($old_attachment_data['post_alt']) 
				&& $old_attachment_data['post_alt'] != $attachment_data['post_alt']) ||  
		    $old_attachment_data['post_title']   != $attachment_data['post_title']   || 
			$old_attachment_data['post_content'] != $attachment_data['post_content'] || 
			$old_attachment_data['post_excerpt'] != $attachment_data['post_excerpt'] ||	
			$old_attachment_data['menu_order']   != $attachment_data['menu_order']   ||
			is_array($tax_input) )
		{
			if( 0 !== wp_update_post($attachment_data) )
			{
				update_post_meta($attachment_id, "_wp_attachment_image_alt", $attachment_data['post_alt']);
				$output = __("Attachment data updated", "file-gallery");
			}
			else
			{
				$output = __("Error updating attachment data!", "file-gallery");
			}
		}
		else
		{
			$output = __("No change.", "file-gallery");
		}
	}
	elseif( "file_gallery_set_post_thumb" == $action )
	{
		update_post_meta($post_id, "_thumbnail_id", $attachment_ids[0]);
		exit(_wp_post_thumbnail_html($attachment_ids[0], $post_id));
	}
	elseif( "file_gallery_unset_post_thumb" == $action )
	{
		exit();
	}

	include_once("main-form.php");

	exit();
}
add_action('wp_ajax_file_gallery_load',				'file_gallery_main');
add_action('wp_ajax_file_gallery_main_update',		'file_gallery_main');
add_action('wp_ajax_file_gallery_main_delete',		'file_gallery_main');
add_action('wp_ajax_file_gallery_main_detach',		'file_gallery_main');
add_action('wp_ajax_file_gallery_set_post_thumb',	'file_gallery_main');
add_action('wp_ajax_file_gallery_unset_post_thumb',	'file_gallery_main');

