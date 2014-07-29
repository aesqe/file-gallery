<?php

/**
 * {@internal Missing Short Description}}
 *
 * @since 2.5.0
 *
 * @param unknown_type $entry
 * @param unknown_type $count
 * @return unknown
 */
function file_gallery_list_meta_row( $entry, &$count ) {
	static $update_nonce = false;

	if ( is_protected_meta( $entry['meta_key'], 'post' ) )
		return;

	if ( !$update_nonce )
		$update_nonce = wp_create_nonce( 'add-meta' );

	$r = '';
	++ $count;
	if ( $count % 2 )
		$style = 'alternate';
	else
		$style = '';

	if ( is_serialized( $entry['meta_value'] ) ) {
		if ( is_serialized_string( $entry['meta_value'] ) ) {
			// this is a serialized string, so we should display it
			$entry['meta_value'] = maybe_unserialize( $entry['meta_value'] );
		} else {
			// this is a serialized array/object so we should NOT display it
			--$count;
			return;
		}
	}

	$entry['meta_key'] = esc_attr($entry['meta_key']);
	$entry['meta_value'] = esc_textarea( $entry['meta_value'] ); // using a <textarea />
	$entry['meta_id'] = (int) $entry['meta_id'];

	$delete_nonce = wp_create_nonce( 'delete-meta_' . $entry['meta_id'] );

	$r .= "\n\t<tr id='meta-{$entry['meta_id']}' class='$style'>";
	$r .= "\n\t\t<td class='left'><label class='screen-reader-text' for='meta[{$entry['meta_id']}][key]'>" . __( 'Key' ) . "</label><input name='meta[{$entry['meta_id']}][key]' id='meta[{$entry['meta_id']}][key]' type='text' size='20' value='{$entry['meta_key']}' />";

	$r .= "\n\t\t<div class='submit'>";
	$r .= get_submit_button( __( 'Delete' ), 'deletemeta small', "deletemeta[{$entry['meta_id']}]", false, array( 'data-wp-lists' => "delete:attachment-the-list:meta-{$entry['meta_id']}::_ajax_nonce=$delete_nonce" ) );
	$r .= "\n\t\t";
	$r .= get_submit_button( __( 'Update' ), 'updatemeta small', "meta-{$entry['meta_id']}-submit", false, array( 'data-wp-lists' => "add:attachment-the-list:meta-{$entry['meta_id']}::_ajax_nonce-add-meta=$update_nonce" ) );
	$r .= "</div>";
	$r .= wp_nonce_field( 'change-meta', '_ajax_nonce', false, false );
	$r .= "</td>";

	$r .= "\n\t\t<td><label class='screen-reader-text' for='meta[{$entry['meta_id']}][value]'>" . __( 'Value' ) . "</label><textarea name='meta[{$entry['meta_id']}][value]' id='meta[{$entry['meta_id']}][value]' rows='2' cols='30'>{$entry['meta_value']}</textarea></td>\n\t</tr>";
	return $r;
}



/**
 * Prints the form in the Custom Fields meta box.
 *
 * @since 1.2.0
 *
 * @param WP_Post $post Optional. The post being edited.
 */
function file_gallery_meta_form( $post )
{
	global $wpdb;

	$limit = (int) apply_filters( 'postmeta_form_limit', 30 );

	$keys = $wpdb->get_col("
		SELECT meta_key
		FROM $wpdb->postmeta
		GROUP BY meta_key
		HAVING meta_key NOT LIKE '\_%'
		ORDER BY meta_key
		LIMIT $limit"
	);

	if ( $keys ) {
		natcasesort($keys);
	}
?>
	<p><strong><?php _e('Add New Custom Field:') ?></strong></p>

	<table id="attachment-newmeta">
		<thead>
			<tr>
				<th class="left"><label for="attachment-metakeyselect"><?php _ex('Name', 'meta name') ?></label></th>
				<th><label for="attachment-metavalue"><?php _e('Value') ?></label></th>
			</tr>
		</thead>

		<tbody>
			<tr>
				<td id="attachment-newmetaleft" class="left">
				<?php if( $keys ) { ?>
					<select id="attachment-metakeyselect" name="metakeyselect">
					<option value="#NONE#"><?php _e('&mdash; Select &mdash;'); ?></option>
					<?php
						foreach( $keys as $key )
						{
							if( ! (is_protected_meta($key, 'post') || ! current_user_can('add_post_meta', $post->ID, $key)) ) {
								echo "\n<option value='" . esc_attr($key) . "'>" . esc_html($key) . "</option>";
							}
						}
					?>
					</select>
					<input class="hide-if-js" type="text" id="attachment-metakeyinput" name="metakeyinput" value="" />
					<a href="#attachment-postcustomstuff" class="hide-if-no-js" onclick="jQuery('#attachment-metakeyinput, #attachment-metakeyselect, #attachment-enternew, #attachment-cancelnew').toggle();return false;">
					<span id="attachment-enternew"><?php _e('Enter new'); ?></span>
					<span id="attachment-cancelnew" class="hidden"><?php _e('Cancel'); ?></span></a>
				<?php } else { ?>
					<input type="text" id="attachment-metakeyinput" name="metakeyinput" value="" />
				<?php } ?>
				</td>
				<td><textarea id="attachment-metavalue" name="metavalue" rows="2" cols="25"></textarea></td>
			</tr>

			<tr>
				<td colspan="2">
					<div class="submit">
					<?php submit_button(__('Add Custom Field'), 'secondary', 'addmeta', false, array('id' => 'attachment-newmeta-submit', 'data-wp-lists' => 'add:attachment-the-list:attachment-newmeta')); ?>
					</div>
					<?php wp_nonce_field('add-meta', '_ajax_nonce-add-meta', false); ?>
				</td>
			</tr>
		</tbody>
	</table>
	<script type="text/javascript">
		jQuery('#attachment-the-list').wpList(
		{
			addAfter: function()
			{
				jQuery('table#attachment-list-table').show();
			},
			addBefore: function( s )
			{
				s.data += '&post_id=<?php echo $post->ID; ?>';
				return s;
			}
		});
	</script>
<?php
}



/**
 * {@internal Missing Short Description}}
 *
 * @since 1.2.0
 *
 * @param unknown_type $meta
 */
function file_gallery_list_meta( $meta )
{
	// Exit if no meta
	if( ! $meta )
	{
		echo '<table id="attachment-list-table" style="display: none;">
				<thead>
				<tr>
					<th class="left">' . _x( 'Name', 'meta name' ) . '</th>
					<th>' . __( 'Value' ) . '</th>
				</tr>
				</thead>
				<tbody id="attachment-the-list" data-wp-lists="list:meta">
				<tr><td></td></tr>
				</tbody>
			</table>'; //TBODY needed for list-manipulation JS

			return;
		}

		$count = 0;
	?>
	<table id="attachment-list-table">
		<thead>
		<tr>
			<th class="left"><?php _ex( 'Name', 'meta name' ) ?></th>
			<th><?php _e( 'Value' ) ?></th>
		</tr>
		</thead>
		<tbody id='attachment-the-list' data-wp-lists='list:meta'>
	<?php
		foreach( $meta as $entry ) {
			echo file_gallery_list_meta_row($entry, $count);
		}
	?>
		</tbody>
	</table>
<?php
}



/**
 * Display custom fields form fields.
 *
 * @since 2.6.0
 *
 * @param object $post
 */
function file_gallery_post_custom_meta_box($post) {
?>
	<div id="attachment-postcustomstuff">
		<div id="attachment-ajax-response"></div>
		<?php
			$metadata = has_meta($post->ID);

			foreach( $metadata as $key => $value )
			{
				if( is_protected_meta($metadata[$key]['meta_key'], 'post') || ! current_user_can('edit_post_meta', $post->ID, $metadata[$key]['meta_key']) ) {
					unset( $metadata[$key] );
				}
			}

			file_gallery_list_meta($metadata);
			file_gallery_meta_form($post);
		?>
	</div>
	<p><?php _e('Custom fields can be used to add extra metadata to a post that you can <a href="http://codex.wordpress.org/Using_Custom_Fields" target="_blank">use in your theme</a>.'); ?></p>
<?php
}



/**
 * Displays the table for custom fields on attachment editing screen
 * within the File Gallery metabox
 */
function file_gallery_attachment_custom_fields_table( $attachment_id )
{
	$attachment = get_post($attachment_id);
	file_gallery_post_custom_meta_box($attachment);
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