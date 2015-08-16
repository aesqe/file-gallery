<?php
/**
 *
 * @since 1.7
 */
function file_gallery_do_settings()
{
	global $file_gallery;

	if( ! isset($file_gallery) ) {
		$file_gallery = new File_Gallery();
	}

	$file_gallery->settings = array(
			'disable_shortcode_handler' => array(
				'default' => 0,
				'display' => true,
				'title' => __("Disable 'File Gallery' handling of [gallery] shortcode?", 'file-gallery'),
				'type' => 'checkbox',
				'section' => 0,
				'position' => 0
			),
			'show_on_post_type' => array(
				'default' => 1,
				'display' => true,
				'title' => __('Display File Gallery on which post types?', 'file-gallery'),
				'type' => 'checkbox',
				'values' => file_gallery_post_type_checkboxes(),
				'section' => 0,
				'position' => 0
			),
			'alt_color_scheme' => array(
				'default' => 1,
				'display' => true,
				'title' => __('Use alternative color scheme (a bit more contrast)?', 'file-gallery'),
				'type' => 'checkbox',
				'section' => 0,
				'position' => 0
			),
			'pagination_count' => array(
				'default' => 9,
				'display' => true,
				'title' => __('How many page links should be shown in pagination?', 'file-gallery'),
				'type' => 'number',
				'section' => 0,
				'position' => 0
			),
			'auto_enqueued_scripts' => array(
				'default' => 'thickbox',
				'display' => true,
				'title' => __('Auto enqueue lightbox scripts for which link classes (separate with commas)?', 'file-gallery'),
				'type' => 'text',
				'section' => 0,
				'position' => 0
			),
			'default_metabox_image_size' => array(
				'default' => 'thumbnail',
				'display' => true,
				'title' => __('Default WordPress image size for thumbnails in File Gallery metabox on post editing screens?', 'file-gallery'),
				'type' => 'select',
				'values' => file_gallery_dropdown( 'default_metabox_image_size', 'image_size' ),
				'section' => 0,
				'position' => 0
			),
			'default_metabox_image_width' => array(
				'default' => 75,
				'display' => true,
				'title' => __('Default width (in pixels) for thumbnails in File Gallery metabox on post editing screens?', 'file-gallery'),
				'type' => 'number',
				'section' => 0,
				'position' => 0
			),


			'default_image_size' => array(
				'default' => 'thumbnail',
				'display' => true,
				'title' => '</th></tr><tr><td colspan="2"><strong style="display: block; margin-top: -15px; font-size: 115%; color: #21759B;">' . __('Some default values for when inserting a gallery into a post', 'file-gallery') . '...</strong></td></tr><tr><td colspan="2"><p id="file-gallery-media-settings-notice" style="margin: 0; background-color: #FFFFE8; border-color: #EEEED0; -moz-border-radius: 3px; -webkit-border-radius: 3px; border-radius: 3px; border-style: solid; border-width: 1px; padding: 0.6em;">' . sprintf(__('The following two blocks of options <strong>do not</strong> affect the output/display of your galleries - they are here only so you could set default values for File Gallery metabox on post editing screen. <a href="%s/help/index.html#settings_page" target="_blank">More information is available in the help file</a>.', "file-gallery"), FILE_GALLERY_URL) . '</p></td></tr><tr valign="top"><th scope="row">' . __('size', 'file-gallery'),
				'type' => 'select',
				'values' => file_gallery_dropdown( 'default_image_size', 'image_size' ),
				'section' => 0,
				'position' => 0
			),
			'default_linkto' => array(
				'default' => 'attachment',
				'display' => true,
				'title' => __('link', 'file-gallery'),
				'type' => 'select',
				'values' => file_gallery_dropdown( 'default_linkto', 'linkto' ),
				'section' => 0,
				'position' => 0
			),
			'default_linked_image_size' => array(
				'default' => 'full',
				'display' => true,
				'title' => __('linked image size', 'file-gallery'),
				'type' => 'select',
				'values' => file_gallery_dropdown( 'default_linked_image_size', 'image_size' ),
				'section' => 0,
				'position' => 0
			),
			'default_external_url' => array(
				'default' => '',
				'display' => true,
				'title' => __('external url', 'file-gallery'),
				'type' => 'text',
				'section' => 0,
				'position' => 0
			),
			'default_orderby' => array(
				'default' => '',
				'display' => true,
				'title' => __('order by', 'file-gallery'),
				'type' => 'select',
				'values' => file_gallery_dropdown( 'default_orderby', 'orderby' ),
				'section' => 0,
				'position' => 0
			),
			'default_order' => array(
				'default' => 'ASC',
				'display' => true,
				'title' => __('order', 'file-gallery'),
				'type' => 'select',
				'values' => file_gallery_dropdown( 'default_order', 'order' ),
				'section' => 0,
				'position' => 0
			),
			'default_template' => array(
				'default' => 'default',
				'display' => true,
				'title' => __('template', 'file-gallery'),
				'type' => 'select',
				'values' => file_gallery_dropdown( 'default_template', 'template' ),
				'section' => 0,
				'position' => 0
			),
			'default_linkclass' => array(
				'default' => '',
				'display' => true,
				'title' => __('link class', 'file-gallery'),
				'type' => 'text',
				'section' => 0,
				'position' => 0
			),
			'default_imageclass' => array(
				'default' => '',
				'display' => true,
				'title' => __('image class', 'file-gallery'),
				'type' => 'text',
				'section' => 0,
				'position' => 0
			),
			'default_columns' => array(
				'default' => 3,
				'display' => true,
				'title' => __('columns', 'file-gallery'),
				'type' => 'select',
				'values' => file_gallery_dropdown( 'default_columns', 'columns' ),
				'section' => 0,
				'position' => 0
			),
			'default_mimetype' => array(
				'default' => '',
				'display' => true,
				'title' => __('mime type', 'file-gallery'),
				'type' => 'text',
				'section' => 0,
				'position' => 0
			),
			'default_galleryclass' => array(
				'default' => '',
				'display' => true,
				'title' => __('gallery class', 'file-gallery'),
				'type' => 'text',
				'section' => 0,
				'position' => 0
			),


			'single_default_image_size' => array(
				'default' => 'thumbnail',
				'display' => true,
				'title' => '</th></tr><tr><td colspan="2"><strong style="display: block; margin-top: -15px; font-size: 115%; color: #21759B;">' . __('...and for when inserting (a) single image(s) into a post', 'file-gallery') . '</strong></td></tr><tr valign="top"><th scope="row">' . __('size', 'file-gallery'),
				'type' => 'select',
				'values' => file_gallery_dropdown( 'single_default_image_size', 'image_size' ),
				'section' => 0,
				'position' => 0
			),
			'single_default_linkto' => array(
				'default' => 'attachment',
				'display' => true,
				'title' => __('link', 'file-gallery'),
				'type' => 'select',
				'values' => file_gallery_dropdown( 'single_default_linkto', 'linkto' ),
				'section' => 0,
				'position' => 0
			),
			'single_default_external_url' => array(
				'default' => '',
				'display' => true,
				'title' => __('external url', 'file-gallery'),
				'type' => 'text',
				'section' => 0,
				'position' => 0
			),
			'single_default_linkclass' => array(
				'default' => '',
				'display' => true,
				'title' => __('link class', 'file-gallery'),
				'type' => 'text',
				'section' => 0,
				'position' => 0
			),
			'single_default_imageclass' => array(
				'default' => '',
				'display' => true,
				'title' => __('image class', 'file-gallery'),
				'type' => 'text',
				'section' => 0,
				'position' => 0
			),
			'single_default_align' => array(
				'default' => 'none',
				'display' => true,
				'title' => __('alignment', 'file-gallery'),
				'type' => 'select',
				'values' => file_gallery_dropdown( 'single_default_align', 'align' ),
				'section' => 0,
				'position' => 0
			),


			'cache' => array(
				'default' => 0,
				'display' => true,
				'title' => '</th></tr><tr><td colspan="2"><strong style="display: block; margin-top: -15px; font-size: 115%; color: #21759B;">' . __('Cache', 'file-gallery') . '</strong></td></tr><tr valign="top"><th scope="row">' . __('Enable caching?', 'file-gallery'),
				'type' => 'checkbox',
				'section' => 0,
				'position' => 0
			),
			'cache_time' => array(
				'default' => 3600, // == 1 hour
				'display' => true,
				'title' => __("Cache expires after how many seconds? (leave as is if you don't know what it means)", 'file-gallery'),
				'type' => 'number',
				'section' => 0,
				'position' => 0
			),
			'cache_non_html_output' => array(
				'default' => 0,
				'display' => true,
				'title' => __('Cache non-HTML gallery output (<em>array, object, json</em>)', 'file-gallery'),
				'type' => 'checkbox',
				'section' => 0,
				'position' => 0
			),


			'e_display_attachment_count' => array(
				'default' => 1,
				'display' => true,
				'title' => '</th></tr><tr><td colspan="2"><strong style="display: block; margin-top: -15px; font-size: 115%; color: #21759B;">' . __('Edit screens options', 'file-gallery') . '</strong></td></tr><tr valign="top"><th scope="row">' . __('Display attachment count?', 'file-gallery'),
				'type' => 'checkbox',
				'section' => 0,
				'position' => 0
			),
			'library_filter_duplicates' => array(
				'default' => 1,
				'display' => true,
				'title' => __('Filter out duplicate attachments (copies) when browsing media library?', 'file-gallery'),
				'type' => 'checkbox',
				'section' => 0,
				'position' => 0
			),
			'e_display_media_tags' => array(
				'default' => 1,
				'display' => true,
				'title' => __('Display media tags for attachments in media library?', 'file-gallery'),
				'type' => 'checkbox',
				'section' => 0,
				'position' => 0
			),
			'e_display_post_thumb' => array(
				'default' => 1,
				'display' => true,
				'title' => __('Display post thumb (if set)?', 'file-gallery'),
				'type' => 'checkbox',
				'section' => 0,
				'position' => 0
			),


			'in_excerpt' => array(
				'default' => 1,
				'display' => true,
				'title' => '</th></tr><tr><td colspan="2"><strong style="display: block; margin-top: -15px; font-size: 115%; color: #21759B;">' . __('Other options', 'file-gallery') . '</strong></td></tr><tr valign="top"><th scope="row">' . __('Display galleries within post excerpts?', 'file-gallery'),
				'type' => 'checkbox',
				'section' => 0,
				'position' => 0
			),
			'in_excerpt_replace_content' => array(
				'default' => '<p><strong>(' . __('galleries are shown on full posts only', 'file-gallery') . ')</strong></p>',
				'display' => true,
				'title' => __("Replacement text for galleries within post excerpts (if you haven't checked the above option)", 'file-gallery'),
				'type' => 'textarea',
				'section' => 0,
				'position' => 0
			),
			'display_gallery_fieldset' => array(
				'default' => 1,
				'display' => true,
				'title' => __('Display options for inserting galleries into a post?', 'file-gallery'),
				'type' => 'checkbox',
				'section' => 0,
				'position' => 0
			),
			'display_single_fieldset' => array(
				'default' => 1,
				'display' => true,
				'title' => __('Display options for inserting single images into a post?', 'file-gallery'),
				'type' => 'checkbox',
				'section' => 0,
				'position' => 0
			),
			'display_acf' => array(
				'default' => 1,
				'display' => true,
				'title' => __('Display attachment custom fields?', 'file-gallery'),
				'type' => 'checkbox',
				'section' => 0,
				'position' => 0
			),
			'insert_gallery_button' => array(
				'default' => 1,
				'display' => true,
				'title' => __("Display 'insert gallery' button even if gallery options are hidden?", 'file-gallery'),
				'type' => 'checkbox',
				'section' => 0,
				'position' => 0
			),
			'insert_single_button' => array(
				'default' => 1,
				'display' => true,
				'title' => __("Display 'insert single image(s)' button even if single image options are hidden?", 'file-gallery'),
				'type' => 'checkbox',
				'section' => 0,
				'position' => 0
			),
			'del_options_on_deactivate' => array(
				'default' => 0,
				'display' => true,
				'title' => __('Delete all options on deactivation?', 'file-gallery'),
				'type' => 'checkbox',
				'section' => 0,
				'position' => 0
			),


			/**
			 * Disabled options
			 */
			'version' => array(
				'default' => FILE_GALLERY_VERSION,
				'display' => 'disabled',
				'title' => __('File Gallery version', 'file-gallery'),
				'type' => 'text',
				'section' => 0,
				'position' => 0
			),
			'folder' => array(
				'default' => file_gallery_https( FILE_GALLERY_URL ),
				'display' => 'disabled',
				'title' => __('File Gallery folder', 'file-gallery'),
				'type' => 'text',
				'section' => 0,
				'position' => 0
			),
			'abspath' => array(
				'default' => FILE_GALLERY_ABSPATH,
				'display' => 'disabled',
				'title' => __('File Gallery path', 'file-gallery'),
				'type' => 'text',
				'section' => 0,
				'position' => 100
			),
			'media_tag_taxonomy_name' => array(
				'default' => 'media_tag',
				'display' => 'disabled',
				'title' => __('Media tags Taxonomy name', 'file-gallery') . ' <em>(<a href="' . admin_url('options-permalink.php') . '" style="text-transform: lowercase;">' . __('Edit') . '</a>)</em>',
				'type' => 'text',
				'section' => 0,
				'position' => 0
			),
			'media_tag_taxonomy_slug' => array(
				'default' => 'media-tag',
				'display' => 'disabled',
				'title' => __('Media tags URL slug', 'file-gallery') . ' <em>(<a href="' . admin_url('options-permalink.php') . '" style="text-transform: lowercase;">' . __('Edit') . '</a>)</em>',
				'type' => 'text',
				'section' => 0,
				'position' => 0
			),


			/**
			 * Hidden options
			 */
			'insert_options_state' => array(
				'default' => 1,
				'display' => true,
				'title' => __('Gallery insert options state', 'file-gallery'),
				'type' => 'checkbox',
				'section' => 0,
				'position' => 0
			),
			'insert_single_options_state' => array(
				'default' => 1,
				'display' => true,
				'title' => __('Single images insert options state', 'file-gallery'),
				'type' => 'checkbox',
				'section' => 0,
				'position' => 0
			),
			'acf_state' => array(
				'default' => 1,
				'display' => true,
				'title' => __('Attachment custom fields state', 'file-gallery'),
				'type' => 'checkbox',
				'section' => 0,
				'position' => 0
			),
			'textual_mode' => array(
				'default' => 0,
				'display' => true,
				'title' => __('"Textual" mode', 'file-gallery'),
				'type' => 'checkbox',
				'section' => 0,
				'position' => 0
			)
		);

	foreach( $file_gallery->settings as $key => $val )
	{
		$def = $val['default'];
		$file_gallery->defaults[$key] = $def;

		if( is_bool($def) || $def === 1 || $def === 0 ) {
			$file_gallery->false_defaults[$key] = 0;
		}
	}
}
