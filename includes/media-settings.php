<?php

/**
 * Registers "file_gallery" options and adds sections/fields to the 
 * media settings page
 */
function file_gallery_options_init()
{
	global $file_gallery;

	$so = get_option('file_gallery');
	$file_gallery_sizes = file_gallery_get_intermediate_image_sizes();
	
	// add sections
	add_settings_section('intermediate_image_sizes', __('Intermediate image sizes', 'file-gallery'), 'file_gallery_options_sections', 'media');
	add_settings_section('file_gallery_options', __('File Gallery', 'file-gallery'), 'file_gallery_options_sections', 'media');
	
	// register the file_gallery variable
	register_setting('media', 'file_gallery', 'file_gallery_save_media_settings');
	
	// add additional fields and register settings for image sizes...
	foreach( $file_gallery_sizes as $size )
	{
		if( "thumbnail" != $size && "full" != $size )
		{
			$size_translated = " " . __('size', 'file-gallery');
			
			if( "medium" == $size )
			{
				$translated_size = ucfirst(__("Medium size", "file-gallery"));
				$size_translated = "";
			}
			elseif( "large" == $size )
			{
				$translated_size = ucfirst(__("Large size", "file-gallery"));
				$size_translated = "";
			}
			else
			{
				$translated_size = ucfirst($size);
			}
				
			add_settings_field("size_" . $size, $translated_size . $size_translated, function() { echo file_gallery_options_fields( array("name" => "' . $size . '", "type" => "intermediate_image_sizes", "disabled" => 0) ); }, 'media', 'intermediate_image_sizes');
			
			register_setting('media', $size . "_size_w");
			register_setting('media', $size . "_size_h");
			register_setting('media', $size . "_crop");
		}
	}
	
	file_gallery_add_settings();
}
add_action('admin_init', 'file_gallery_options_init');



/**
 *	adds sections text
 */
function file_gallery_options_sections( $args )
{
	switch( $args["id"] )
	{
		case "intermediate_image_sizes" :
			$output = __("Here you can specify width, height and crop attributes for intermediate image sizes added by plugins and/or themes, as well as crop options for the default medium and large sizes", "file-gallery");
			break;
		case "file_gallery_options" :
			$output = '<p id="file-gallery-help-notice" style="margin: 0 10px; background-color: #FFFFE8; border-color: #EEEED0; -moz-border-radius: 3px; -webkit-border-radius: 3px; border-radius: 3px; border-style: solid; border-width: 1px; padding: 0.6em;">' . sprintf(__('File Gallery help file is located in the "help" subfolder of the plugin. You can <a href="%s/help/index.html" target="_blank">click here to open it in new window</a>.', "file-gallery"), FILE_GALLERY_URL) . '</p>';
			break;
	}
	
	if( "" != $output ) {
		echo "<p>" . $output . "</p>";
	}
}


/**
 * Makes sure that plugin options do not disappear just
 * because we're lazy (using checkboxes instead of radio buttons) :D
 *
 * @since 1.6.5.4
 */
function file_gallery_save_media_settings( $options )
{
	global $file_gallery;

	$defaults = $file_gallery->false_defaults;
	$defaults = file_gallery_parse_args( $options, $defaults ); // $defaults = shortcode_atts( $defaults, $options );
	$defaults['folder']  = file_gallery_https( FILE_GALLERY_URL );
	$defaults['abspath'] = FILE_GALLERY_ABSPATH;
	
	return $defaults;
}


/**
 * Parses plugin options
 *
 * @since 1.6.5.2
 */
function file_gallery_parse_args( $args, $defaults )
{
	foreach( $defaults as $key => $val )
	{
		if( ! isset($args[$key]) ) {
			$args[$key] = $val; // if key isn't set, it's a new option - add
		}
		elseif( '' == $args[$key] && (0 === $val || 1 === $val) ) {
			$args[$key] = 0; // if a key's value is empty, but should be a false - make it rather a zero
		}
	}
	
	return $args;
}


/**
 * Creates select option dropdowns
 *
 * @since 1.7
 */
function file_gallery_dropdown( $name, $type )
{
	$output = '';
	$options = get_option('file_gallery');
	
	$current = $options[$name];
	
	if( 'image_size' == $type ) {
		$keys['image_size'] = file_gallery_get_intermediate_image_sizes();
	}
	
	if( 'template' == $type ) {
		$keys['template'] = file_gallery_get_templates('file_gallery_dropdown');
	}

	$keys['align'] = array(
		'none' => __('none', 'file-gallery'), 
		'left' => __('left', 'file-gallery'), 
		'right' => __('right', 'file-gallery'),
		'center' => __('center', 'file-gallery')
	);
	$keys['linkto']	 = array(
		"none" => __("nothing (do not link)", "file-gallery"), 
		"file" => __("file", "file-gallery"), 
		"attachment" => __("attachment page", "file-gallery"),
		"parent_post" => __("parent post", "file-gallery"),
		"external_url" => __("external url", "file-gallery")
	);
	$keys['orderby'] = array(
		"default" => __("file gallery", "file-gallery"), 
		"rand" => __("random", "file-gallery"), 
		"menu_order" => __("menu order", "file-gallery"),
		"post_title" => __("title", "file-gallery"),
		"ID" => __("date / time", "file-gallery")
	);
	$keys['order'] = array(
		"ASC" => __("ascending", "file-gallery"), 
		"DESC" => __("descending", "file-gallery")
	);
	$keys['align'] = array(
		"none" => __("none", "file-gallery"), 
		"left" => __("left", "file-gallery"), 
		"right" => __("right", "file-gallery"),
		"center" => __("center", "file-gallery")
	);
	$keys['columns'] = array(
		0, 1, 2, 3, 4, 5, 6, 7, 8, 9
	);

	if( 'image_size' == $type )
	{
		$output .= '<option value="thumbnail"';
		
		if( $current == 'thumbnail' ) {
			$output .= ' selected="selected"';
		}
		
		$output .= '>' . __('thumbnail', 'file-gallery') . '</option>';
		$output .= '<option value="medium"';
		
		if( $current == 'medium' ) {
			$output .= ' selected="selected"';
		}
		
		$output .= '>' . __('medium', 'file-gallery') . '</option>';
		$output .= '<option value="large"';
		
		if( $current == 'large' ) {
			$output .= ' selected="selected"';
		}
		
		$output .= '>' . __('large', 'file-gallery') . '</option>';
		$output .= '<option value="full"';
		
		if( $current == 'full' ) {
			$output .= ' selected="selected"';
		}
		
		$output .= '>' . __('full', 'file-gallery') . '</option>';
	}
	
	foreach( $keys[$type] as $name => $description )
	{
		if( is_numeric($name) ) {
			$name = $description;
		}

		if( 'image_size' == $type && in_array($name, array('thumbnail', 'medium', 'large', 'full')) ) {
			continue;
		}

		$output .= '<option value="' . $name . '"';
		
		if( $current == $name ) {
			$output .= ' selected="selected"';
		}
		
		$output .= '>' . $description . '</option>';
	}
	
	return $output;
}


/**
 * Returns a checkbox for each post type
 *
 * @since 1.7
 */
function file_gallery_post_type_checkboxes()
{
	$output = '';
	$options = get_option('file_gallery');
	$types = get_post_types(false, 'objects');
	
	foreach( $types as $type )
	{
		if( ! isset($type->labels->name) ) {
			$type->labels->name = $type->label;
		}

		if( ! in_array( $type->name, array('nav_menu_item', 'revision', 'attachment', 'deprecated_log') ) )
		{
			$output .= 
			'<input type="checkbox" name="file_gallery[show_on_post_type_' . $type->name . ']" 
					id="file_gallery_show_on_post_type_' . $type->name . '" 
					value="1" 
					' . str_replace("'", '"', checked('1', isset($options["show_on_post_type_" . $type->name]) && true == $options["show_on_post_type_" . $type->name] ? 1 : 0, 0)) . '
					 />
					<label for="file_gallery_show_on_post_type_' . $type->name . '" class="file_gallery_inline_checkbox_label">' . $type->labels->name . '</label>';
		}
	}
	
	return $output;
}


/**
 * Registers each File Gallery setting to the media settings page
 *
 * @since 1.7
 */
function file_gallery_add_settings()
{
	global $file_gallery;

	file_gallery_do_settings();
	
	$settings = $file_gallery->settings;
	$options = get_option('file_gallery');
	
	foreach( $settings as $key => $val )
	{		
		if( $val['display'] !== false )
		{
			$type = preg_replace("#[^a-z]#", '', $val['type']);

			if( ! isset($val['values']) ) {
				$val['values'] = 0;
			}

			if( $type == 'checkbox' || $type == 'select' ) {
				$values = $val['values'];
			}
			else if( $type == 'textarea' ) {
				$values = esc_textarea($values);
			}
			else {
				$values = esc_attr($values);
			}

			$disabled = ('disabled' === $val['display']) ? true : false;
			$section = $val['section'] ? $val['section'] : 'file_gallery_options';

			$args = array(
				'name' => $key,
				'type' => $type ,
				'current' => $options[$key],
				'values' => $values,
				'disabled' => $disabled
			);

			add_settings_field(
				$key, 
				$val['title'], 
				'file_gallery_options_fields', 
				'media', 
				$section,
				$args
			);
		}
	}
}


/**
 * Returns form elements for the media settings page
 *
 * @since 1.7
 */
function file_gallery_options_fields( $args )
{
	global $_wp_additional_image_sizes;

	$output = '';
	
	$name_id = 'name="file_gallery[' . $args['name'] . ']" id="file_gallery_' . $args['name'] . '"';
	$ro = ($args['disabled'] === true) ? ' readonly="readonly"' : '';
	
	if( in_array($args['type'], array('checkbox', 'button')) ) {
		$ro = ($args['disabled'] === true) ? ' disabled="disabled"' : '';
	}
	
	if( $args['type'] == 'intermediate_image_sizes'  )
	{
		$checked = '';
		$size = $args["name"];
		
		if( get_option($size . "_crop") == 1 || (isset($_wp_additional_image_sizes[$size]['crop']) && $_wp_additional_image_sizes[$size]['crop'] == 1 ) ) {
			$checked = ' checked="checked" ';
		}
		
		if( $size == "medium" )
		{	
			$output = '<input name="medium_crop" id="medium_crop" value="1" ' . $checked . ' type="checkbox" />
						<label for="medium_crop">' . __('Crop medium size to exact dimensions', 'file-gallery') . '</label>';
		}
		elseif( $size == "large" )
		{	
			$output = '<input name="large_crop" id="large_crop" value="1" ' . $checked . ' type="checkbox" />
						<label for="large_crop">' . __('Crop large size to exact dimensions', 'file-gallery') . '</label>';
		}
		else
		{
			$size_w = get_option($size . "_size_w");
			$size_h = get_option($size . "_size_h");
			
			if( ! is_numeric($size_w) ) {
				$size_w = $_wp_additional_image_sizes[$size]['width'];
			}
			
			if( ! is_numeric($size_h) ) {
				$size_h = $_wp_additional_image_sizes[$size]['height'];
			}
			
			$output = 
			'<label for="'  . $size . '_size_w">' . __("Width", 'file-gallery') . '</label>
			 <input name="' . $size . '_size_w" id="' . $size . '_size_w" value="' . $size_w . '" class="small-text" type="text" />
			 <label for="'  . $size . '_size_h">' . __("Height", 'file-gallery') . '</label>
			 <input name="' . $size . '_size_h" id="' . $size . '_size_h" value="' . $size_h . '" class="small-text" type="text" /><br />
			 <input name="' . $size . '_crop" id="' . $size . '_crop" value="1" ' . $checked . ' type="checkbox" />
			 <label for="'  . $size . '_crop">' . sprintf(__('Crop %s size to exact dimensions', 'file-gallery'), $size) . '</label>';
		}
		
		echo $output;
		return;
	}

	switch( $args['type'] )
	{
		case 'checkbox' :
			if( $args['values'] != false ) {
				$output = $args['values'];
			}
			else
			{
				$output = '<input class="file_gallery_checkbox ' . $args['name'] . '" type="checkbox" ' . $name_id . ' value="1"' . checked('1', true == $args['current'] ? 1 : 0, false) . $ro . ' />';
			}
			break;
		case 'select' :
			$output = '<select class="file_gallery_select ' . $args['name'] . '" ' . $name_id . $ro . '>' . $args['values'] . '</select>';
			break;
		case 'textarea' :
			$output = '<textarea cols="51" rows="5" class="file_gallery_textarea ' . $args['name'] . '" ' . $name_id . $ro . '>' . esc_textarea($args['current']) . '</textarea>';
			break;
		case 'text' :
		case 'number' :
			$output = '<input size="63" class="file_gallery_text ' . $args['name'] . '" type="text" ' . $name_id . ' value="' . esc_attr($args['current']) . '"' . $ro . ' />';
			break;
	}

	echo $output;
}
