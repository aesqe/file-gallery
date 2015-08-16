<?php

/**
 * Adds js to admin area
 */
function file_gallery_js_admin()
{
	global $current_screen, $wp_version, $post_ID;

	$options = get_option('file_gallery');

	$s = array('{"', '",', '"}', '\/', '"[', ']"');
	$r = array("\n{\n\"", "\",\n", "\"\n}", '/', '[', ']');

	$base = $current_screen->base;
	$post_type = $current_screen->post_type;
	$show_on_post_type = (isset($options['show_on_post_type_' . $post_type]) && $options['show_on_post_type_' . $post_type] == true);

	if( $base === 'post' && $show_on_post_type )
	{
		wp_enqueue_script('ractivejs', file_gallery_https( FILE_GALLERY_URL ) . '/lib/ractive/ractive.js', false, FILE_GALLERY_VERSION);

		// file_gallery.L10n
		$file_gallery_localize = array(
			"switch_to_tags" 			 => __("Switch to tags", "file-gallery"),
			"switch_to_files" 			 => __("Switch to list of attachments", "file-gallery"),
			"fg_info" 					 => __("Insert checked attachments into post as", "file-gallery"),
			"no_attachments_upload" 	 => __("No files are currently attached to this post.", "file-gallery"),
			"sure_to_delete" 			 => __("Are you sure that you want to delete these attachments? Press [OK] to delete or [Cancel] to abort.", "file-gallery"),
			"saving_attachment_data" 	 => __("saving attachment data...", "file-gallery"),
			"loading_attachment_data"	 => __("loading attachment data...", "file-gallery"),
			"deleting_attachment" 		 => __("deleting attachment...", "file-gallery"),
			"deleting_attachments" 		 => __("deleting attachments...", "file-gallery"),
			"loading" 					 => __("loading...", "file-gallery"),
			"detaching_attachment"		 => __("detaching attachment", "file-gallery"),
			"detaching_attachments"		 => __("detaching attachments", "file-gallery"),
			"sure_to_detach"			 => __("Are you sure that you want to detach these attachments? Press [OK] to detach or [Cancel] to abort.", "file-gallery"),
			"close"						 => __("close", "file-gallery"),
			"loading_attachments"		 => __("loading attachments", "file-gallery"),
			"post_thumb_set"			 => __("Featured image set successfully", "file-gallery"),
			"post_thumb_unset"			 => __("Featured image removed", "file-gallery"),
			'copy_all_from_original'	 => __('Copy all attachments from the original post', 'file-gallery'),
			'copy_all_from_original_'	 => __('Copy all attachments from the original post?', 'file-gallery'),
			'copy_all_from_translation'  => __('Copy all attachments from this translation', 'file-gallery'),
			'copy_all_from_translation_' => __('Copy all attachments from this translation?', 'file-gallery'),
			"set_as_featured"			 => __("Set as featured image", "file-gallery"),
			"unset_as_featured"			 => __("Unset as featured image", "file-gallery"),
			'copy_from_is_nan_or_zero'   => __('Supplied ID (%d) is zero or not a number, please correct.', 'file-gallery'),
			'regenerating'               => __('regenerating...', 'file-gallery'),
			'gallery_updated'            => __('Gallery contents updated', 'file-gallery'),
			'attach_all_checked_copy' => __('Attach to post', 'file-gallery'),
			'exclude_current' => __("Exclude current post's attachments", 'file-gallery'),
			'include_current' => __("Include current post's attachments", 'file-gallery'),
			'setThumbError' => __('Could not set that as the thumbnail image. Try a different attachment.', 'file-gallery')
		);

		// file_gallery.options
		$file_gallery_options = array(
			"file_gallery_url"   => file_gallery_https( FILE_GALLERY_URL ),
			"file_gallery_nonce" => wp_create_nonce('file-gallery'),
			"file_gallery_mode"  => "list",
			"file_gallery_version"  => FILE_GALLERY_VERSION,

			"insert_options_state" => (int) $options['insert_options_state'],
			"insert_single_options_state" => (int) $options['insert_single_options_state'],
			"acf_state" => (int) $options['acf_state'],
			"textual_mode" => (int) $options['textual_mode'],

			"num_attachments"    => 1,
			"tags_from"          => true,
			"clear_cache_nonce"  => wp_create_nonce('file-gallery-clear_cache'),
			"post_thumb_nonce"   => wp_create_nonce( "set_post_thumbnail-" . $post_ID ),
			"wp_version"         => $wp_version
		);

		$dependencies = array('jquery', 'jquery-ui-core', 'jquery-ui-draggable', 'jquery-ui-sortable', 'jquery-ui-dialog');

		wp_enqueue_script('file-gallery-main',  file_gallery_https( FILE_GALLERY_URL ) . '/js/file-gallery.js', $dependencies, FILE_GALLERY_VERSION);
		wp_enqueue_script('file-gallery-clear_cache',  file_gallery_https( FILE_GALLERY_URL ) . '/js/file-gallery-clear_cache.js', false, FILE_GALLERY_VERSION);
		wp_enqueue_script('file-gallery-media', file_gallery_https( FILE_GALLERY_URL ) . '/js/file-gallery-media.js', array('media-views'), FILE_GALLERY_VERSION);

		$script = '
		<script type="text/javascript">
			var file_gallery_L10n = ' . str_replace($s, $r, json_encode($file_gallery_localize)) . ';
			var file_gallery_options = ' . str_replace($s, $r, json_encode($file_gallery_options)) . ';
			var file_gallery_attach_nonce = "' . wp_create_nonce( 'file-gallery-attach' ) . '";
			var file_gallery_regenerate_nonce = "' . wp_create_nonce('file_gallery_regenerate_nonce') .'";
		</script>
		';

		echo $script;
	}
	elseif( $base === 'upload' ) // media listing
	{
		wp_enqueue_script('file-gallery-regenerate',  file_gallery_https( FILE_GALLERY_URL ) . '/js/file-gallery-regenerate.js', array('jquery'), FILE_GALLERY_VERSION);

		echo '
		<script type="text/javascript">
			var file_gallery_regenerate_L10n = "' . __('Regenerating...', 'file-gallery') . '";
			var file_gallery_regenerate_nonce = "' . wp_create_nonce('file_gallery_regenerate_nonce') .'";
		</script>
		';
	}
	elseif( $base === 'options-media' )
	{
		echo '
		<script type="text/javascript">
			var file_gallery_options = {
				clear_cache_nonce : "' . wp_create_nonce('file-gallery-clear_cache') . '"
			};
		</script>
		';

		wp_enqueue_script('file-gallery-clear_cache', file_gallery_https( FILE_GALLERY_URL ) . '/js/file-gallery-clear_cache.js', false, FILE_GALLERY_VERSION);
	}
}
add_action('admin_print_scripts', 'file_gallery_js_admin');



/**
 * Adds css to admin area
 */
function file_gallery_css_admin()
{
	global $current_screen, $file_gallery;

	$options = get_option('file_gallery');

	$base = $current_screen->base;
	$post_type = $current_screen->post_type;
	$show_on_post_type = (isset($options['show_on_post_type_' . $post_type]) && $options['show_on_post_type_' . $post_type] == true);

	if( ($base === 'post' && $show_on_post_type) || in_array($base, array('upload', 'options-media', 'options-permalink')) )
	{
		wp_enqueue_style('file_gallery_admin', apply_filters('file_gallery_admin_css_location', file_gallery_https( FILE_GALLERY_URL ) . '/css/file-gallery.css'), false, FILE_GALLERY_VERSION );

		if( is_rtl() ) {
			wp_enqueue_style('file_gallery_admin_rtl', apply_filters('file_gallery_admin_rtl_css_location', file_gallery_https( FILE_GALLERY_URL ) . '/css/file-gallery-rtl.css'), false, FILE_GALLERY_VERSION );
		}
	}
}
add_action('admin_print_styles', 'file_gallery_css_admin');


/**
 * Edit post/page meta box content
 */
function file_gallery_content()
{
	global $post;

	require_once('includes/templates-main.php');
}



/**
 * Creates meta boxes on post editing screen
 */
function file_gallery()
{
	$options = get_option('file_gallery');
	$types = get_post_types();

	foreach( $types as $type )
	{
		if( ! in_array( $type, array('nav_menu_item', 'revision', 'attachment') ) && isset($options['show_on_post_type_' . $type]) && true == $options['show_on_post_type_' . $type] ) {
			add_meta_box('file_gallery', __( 'File Gallery', 'file-gallery' ), 'file_gallery_content', $type, 'normal');
		}
	}

	add_meta_box( 'file_gallery_attachment_custom_fields', __('File Gallery: Attachment Custom Fields'), 'file_gallery_attachment_custom_fields_metabox', 'attachment', 'normal' );
}
add_action('admin_menu', 'file_gallery');



/**
 * Outputs attachment count in the proper column
 */
function file_gallery_posts_custom_column($column_name, $post_id)
{
	global $wpdb, $file_gallery;

	$options = get_option('file_gallery');

	if( 'attachment_count' == $column_name && isset($options['e_display_attachment_count']) && true == $options['e_display_attachment_count'] )
	{
		$count = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(ID) FROM $wpdb->posts WHERE post_type='attachment' AND post_parent=%d", $post_id) );
		echo apply_filters('file_gallery_post_attachment_count', $count, $post_id);
	}
	elseif( 'post_thumb' == $column_name && isset($options['e_display_post_thumb']) && true == $options['e_display_post_thumb'] )
	{
		if( $thumb_id = get_post_meta( $post_id, '_thumbnail_id', true ) )
		{
			$content = str_replace('<a ', '<a class="thickbox" rel="file-gallery-post-list" ', wp_get_attachment_link($thumb_id, 'thumbnail', false ));

			if( false === $file_gallery->admin_thickbox_enqueued )
			{
				wp_enqueue_style('thickbox');
				wp_enqueue_script('thickbox');
				$file_gallery->admin_thickbox_enqueued = true;
			}

			echo apply_filters('file_gallery_post_thumb_content', $content, $post_id, $thumb_id);
		}
		else
		{
			echo apply_filters('file_gallery_no_post_thumb_content', '<span class="no-post-thumbnail">-</span>', $post_id);
		}
	}
}
add_action('manage_posts_custom_column', 'file_gallery_posts_custom_column', 100, 2);
add_action('manage_pages_custom_column', 'file_gallery_posts_custom_column', 100, 2);


/**
 * Adds attachment count column to the post and page edit screens
 */
function file_gallery_posts_columns( $columns )
{
	$options = get_option('file_gallery');

	if( isset($options['e_display_attachment_count']) && true == $options['e_display_attachment_count'] ) {
		$columns['attachment_count'] = __('No. of attachments', 'file-gallery');
	}

	if( isset($options['e_display_post_thumb']) && true == $options['e_display_post_thumb'] )
	{
		$new = array( key($columns) => array_shift($columns), 'post_thumb' => '' );
		$columns = $new + $columns;
	}

	return $columns;
}
add_filter('manage_posts_columns', 'file_gallery_posts_columns');
add_filter('manage_pages_columns', 'file_gallery_posts_columns');


/**
 * Outputs attachment media tags in the proper column
 */
function file_gallery_media_custom_column($column_name, $post_id)
{
	global $wpdb;

	$options = get_option('file_gallery');

	if( 'media_tags' == $column_name && isset($options['e_display_media_tags']) && true == $options['e_display_media_tags'])
	{
		if( isset($options['cache']) && true == $options['cache'] )
		{
			$transient = 'fileglry_mt_' . md5($post_id);
			$cache = get_transient($transient);

			if( $cache )
			{
				echo $cache;
				return;
			}
		}

		$l = '?taxonomy=' . FILE_GALLERY_MEDIA_TAG_NAME . '&amp;term=';
		$out = __('No Media Tags', 'file-gallery');

		$q = "SELECT `name`, `slug`
			  FROM $wpdb->terms
			  LEFT JOIN $wpdb->term_taxonomy ON ( $wpdb->terms.term_id = $wpdb->term_taxonomy.term_id )
			  LEFT JOIN $wpdb->term_relationships ON ( $wpdb->term_taxonomy.term_taxonomy_id = $wpdb->term_relationships.term_taxonomy_id )
			  WHERE `taxonomy` = '" . FILE_GALLERY_MEDIA_TAG_NAME . "'
			  AND `object_id` = %d
			  ORDER BY `name` ASC";

		if( $r = $wpdb->get_results($wpdb->prepare($q, $post_id)) )
		{
			$out = array();

			foreach( $r as $tag )
			{
				$out[] = '<a href="' . $l . $tag->slug . '">' . $tag->name . '</a>';
			}

			$out = implode(', ', $out);
		}

		if( isset($options['cache']) && true == $options['cache'] ) {
			set_transient($transient, $out, $options['cache_time']);
		}

		echo $out;
	}
}
add_action('manage_media_custom_column', 'file_gallery_media_custom_column', 100, 2);


/**
 * Adds media tags column to attachments
 */
function file_gallery_media_columns( $columns )
{
	global $mediatags;

	if( ! (is_a($mediatags, 'MediaTags') && defined('MEDIA_TAGS_TAXONOMY')) ) {
		$columns['media_tags'] = __('Media tags', 'file-gallery');
	}

	return $columns;
}
add_filter('manage_media_columns', 'file_gallery_media_columns');



function file_gallery_print_media_templates()
{
	global $post, $wp_version;

	$v = (string) $wp_version;
	$v = str_replace('.', '', $v);
	$v = substr($v, 0, 2);
?>
	<?php require_once('includes/templates-media-wp' . $v . '.php'); ?>
<?php
}
add_action('print_media_templates','file_gallery_print_media_templates');
