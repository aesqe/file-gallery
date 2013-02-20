<?php

/**
 * Displays the table for custom fields on attachment editing screen
 * within the File Gallery metabox
 */
function file_gallery_attachment_custom_fields_table( $attachment_id )
{
	if( function_exists('get_taxonomies_for_attachments') ) // WP 3.5
	{
		$attachment = get_post($attachment_id);
		require_once(ABSPATH . '/wp-admin/includes/meta-boxes.php');
		post_custom_meta_box($attachment);
		return;
	}
	
	$form_fields = array();
	$custom = get_post_custom($attachment_id);
	$options = get_option('file_gallery');
	$style = '';
	$class = 'open';
	
	if( isset($options['acf_state']) && true != $options['acf_state'] )
	{
		$class = 'closed';
		$style = ' style="display: none;"';
	}
	
	foreach( (array) $custom as $key => $val )
	{
		if( 1 < count($val) || "_" == substr($key, 0, 1) || is_array($val[0]) )
			continue;
		
		$form_fields[] = '
		<tr class="' . $key . '" id="acf_' . $key . '">
			<th valign="top" class="label" scope="row">
				<label for="attachments[' . $attachment_id . '][' . $key . ']">
					<span class="alignleft">' . $key . '</span>
					<br class="clear" />
				</label>
			</th>
			<td class="field custom_field">
				<textarea name="attachments[' . $attachment_id . '][' . $key . ']" id="attachments[' . $attachment_id . '][' . $key .']">' . $val[0] . '</textarea>
				<input class="button-secondary acf_delete_custom_field" type="button" value="Delete" name="acf_delete_custom_field_' . $key . '" />
			</td>
		</tr>';
	}
	
	$form_fields[] = '
	<tr class="acf_new_custom_field">
		<th valign="top" scope="row" class="label">
			<label for="attachments[' . $attachment_id . '][acf_new_custom_field]">
				<span class="alignleft">' . __("Add New Custom Field", "file-gallery") . '</span>
				<br class="clear" />
			</label>
		</th>
		<td class="field">
			<p>
				<label>'. __("Name:", "file-gallery") . '</label>
				<br />
				<input value="" name="new_custom_field_key" id="new_custom_field_key" class="text" type="text">
				<abbr title="required" class="required">*</abbr>
			</p>
			<p>
				<label>Value:</label>
				<br />
				<textarea value="" name="new_custom_field_value" id="new_custom_field_value" class="textarea"></textarea>
			</p>
			<p>
				<input id="new_custom_field_submit" name="new_custom_field_submit" value="'. __("Add Custom Field", "file-gallery") . '" class="button-secondary" type="submit">
			</p>
			<p class="help"><abbr title="required" class="required">*</abbr>'. __('The "Name" field is required', "file-gallery") . '</p>
		</td>
	</tr>
	';

	echo 
	'<fieldset id="fieldset_attachment_custom_fields">
		<legend>' . __("Custom Fields", "file-gallery") . '</legend>
		<input type="button" id="file_gallery_hide_acf" class="' . $class . '" title="' . __('show/hide this fieldset', 'file-gallery') . '" />
		<table id="media-single-form"' . $style . '>
			<tbody>
				' . implode("", $form_fields) . '
			</tbody>
		</table>
	</fieldset>';
}


/**
 * Adds a new custom field to an attachment via ajax
 *
 * If there is already a custom field with that key, 
 * this function will update it, not convert it to an 
 * array with multiple values.
 *
 * You should probably use your own custom functions 
 * for that sort of stuff. Please do contact me if 
 * you think this is wrong behaviour.
 *
 * @since 1.6.5
 */
function file_gallery_add_new_attachment_custom_field()
{
	check_ajax_referer('add_new_attachment_custom_field_nonce');
	
	$attachment_id = (int) $_POST['attachment_id'];
	$key = $_POST['key'];
	$value = $_POST['value'];
	
	echo update_post_meta($attachment_id, $key, $value);
	
	exit;
}
if( floatval(get_bloginfo('version')) < 3.5 ) {
	add_action('wp_ajax_file_gallery_add_new_attachment_custom_field', 'file_gallery_add_new_attachment_custom_field');
}


/**
 * Deletes a custom field from an attachment via ajax
 *
 * @since 1.6.5
 */
function file_gallery_delete_attachment_custom_field()
{
	check_ajax_referer('delete_attachment_custom_field_nonce');
	
	$attachment_id = (int) $_POST['attachment_id'];
	$key = $_POST['key'];
	$value = $_POST['value'];
	
	echo delete_post_meta($attachment_id, $key, $value);
	
	exit;
}
if( floatval(get_bloginfo('version')) < 3.5 ) {
	add_action('wp_ajax_file_gallery_delete_attachment_custom_field', 'file_gallery_delete_attachment_custom_field');
}


/**
 * Displays attachment custom fields on media editing page (pre 3.5).
 *
 * @since 1.6.5
 *
 * Also adds a button to the edit/insert attachment form
 * to link the attachment to the parent post
 * in addition to itself, the actual file, or nothing.
 *
 * @since unknown
 */
function file_gallery_attachment_fields_to_edit( $form_fields, $attachment )
{	
	global $pagenow, $wpdb, $file_gallery;

	// parent post url button
	if( false === strpos($form_fields['url']['html'], __('Attachment Post URL')) ) // Button title changed in 3.3
		$form_fields['url']['html'] = str_replace( __('Post URL'), __('Attachment URL', 'file-gallery'), $form_fields['url']['html']);

	$form_fields['url']['html'] .= '<button type="button" class="button urlparent" title="' . get_permalink( $wpdb->get_var( $wpdb->prepare("SELECT `post_parent` FROM $wpdb->posts WHERE `ID`='%d'", $attachment->ID) ) ) . '">' . __('Parent Post URL', 'file-gallery') . '</button>';

	// custom fields
	$options = get_option('file_gallery');

	if( true == $options['display_acf'] && 'media.php' == $pagenow && is_numeric($_GET['attachment_id']) && 'edit' == $_GET['action'] )
	{
		$form_fields['acf_custom_fields'] = array( 'label' => '&nbsp;', 'tr' => '<tr><td colspan="2"><h2>' . __('Custom Fields', 'file-gallery') . '</h2></td></tr>' );
		
		$custom = get_post_custom($attachment->ID);
		
		foreach( (array) $custom as $key => $val )
		{
			if( 1 < count($val) || "_" == substr($key, 0, 1) || is_array($val[0]) )
				continue;
			
			$form_fields['fgacf_' . $key] = array(
				'label' => $key, 
				'input' => 'textarea', 
				'value' => $val[0]
			);
		}
		
		$form_fields['acf_new_custom_field'] = array( 
			'label' => __('Add New Custom Field', 'file-gallery'), 
			'helps' => '<abbr class="required" title="required">*</abbr>' . __('The "Name" field is required', 'file-gallery'),
			'input' => 'html', 
			'html'  => '<p><label>'. __('Name:', 'file-gallery') . '</label><br /><input value="" name="new_custom_field_key" id="new_custom_field_key" class="text" type="text"><abbr class="required" title="required">*</abbr></p><p><label>'. __('Value:', 'file-gallery') . '</label><br /><textarea name="new_custom_field_value" id="new_custom_field_value" class="textarea"></textarea></p><p><input id="new_custom_field_submit" name="new_custom_field_submit" value="' . __('Add Custom Field', 'file-gallery') . '" class="button-secondary" type="submit"></p>'
		);
	}
	
	return $form_fields;
}
if( floatval(get_bloginfo('version')) < 3.5 ) {
	add_filter('attachment_fields_to_edit', 'file_gallery_attachment_fields_to_edit', 10, 2);
}


/**
 * Processes new and updated attachment custom field values
 *
 * @since 1.6.5
 */
function file_gallery_attachment_fields_to_save( $attachment, $new_data )
{
	global $pagenow;
	
	if( 'media.php' == $pagenow && is_numeric($_GET['attachment_id']) && 'edit' == $_GET['action'] )
	{
		$custom = get_post_custom($attachment['ID']);
		
		foreach( (array) $custom as $key => $val )
		{
			if( ! isset($new_data[$key]) || '_' == substr($key, 0, 1) )
				continue;
		
			update_post_meta($attachment['ID'], $key, $new_data[$key]);
		}
		
		// no javascript
		if( isset($_POST['new_custom_field_submit']) && isset($_POST['new_custom_field_key']) )
			update_post_meta($attachment['ID'], $_POST['new_custom_field_key'], $_POST['new_custom_field_value']);
	}

	return $attachment;
}
if( floatval(get_bloginfo('version')) < 3.5 ) {
	add_filter('attachment_fields_to_save', 'file_gallery_attachment_fields_to_save', 10, 2);
}


/**
 * for WordPress 3.5
 * @since 1.7.6
 */
function file_gallery_attachment_custom_fields_metabox()
{
	global $post;
	post_custom_meta_box($post);
}