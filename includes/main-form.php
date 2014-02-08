<?php
/**
 * html form in which all the attachments are 
 * displayed on edit post screen in admin
 */ 

	$fgf_class = '';
	
	if( isset($file_gallery_options['alt_color_scheme']) && true == $file_gallery_options['alt_color_scheme'] )
		$fgf_class .= ' alternative-color-scheme';
	
	if( isset($file_gallery_options["display_gallery_fieldset"]) && false == $file_gallery_options["display_gallery_fieldset"] && isset($file_gallery_options["display_single_fieldset"]) && false == $file_gallery_options["display_single_fieldset"] )
	{
		$fgf_class .= ' no-fieldsets';
	}
	else if( isset($file_gallery_options["display_gallery_fieldset"]) && false == $file_gallery_options["display_gallery_fieldset"] )
	{
		$fgf_class .= ' no-gallery-fieldset';
	}
	else if( isset($file_gallery_options["display_single_fieldset"]) && false == $file_gallery_options["display_single_fieldset"] )
	{
		$fgf_class .= ' no-single-fieldset';
	}
	
	if( '' != $fgf_class )
		$fgf_class = ' class="' . trim($fgf_class) . '"';
?>
<div id="file_gallery_response_inner"><?php echo $output; ?></div>

<div id="file_gallery_form"<?php echo $fgf_class; ?>>

	<input type="hidden" name="data_collector"           id="data_collector"           value="" style="width: 90%" />
	<input type="hidden" name="data_collector_checked"   id="data_collector_checked"   value="<?php echo implode(',', $checked_attachments); ?>" style="width: 90%" />
	<input type="hidden" name="data_collector_full"      id="data_collector_full"      value="" style="width: 90%" />
	<input type="hidden" name="file_gallery_delete_what" id="file_gallery_delete_what" value="<?php echo $delete_what; ?>" style="width: 90%" />
	<input type="hidden" name="file_gallery_copies"      id="file_gallery_copies"      value="" style="width: 90%" />
	<input type="hidden" name="file_gallery_originals"   id="file_gallery_originals"   value="" style="width: 90%" />
	
	<div id="fg_buttons"<?php if( ( ! isset($file_gallery_options["display_gallery_fieldset"]) && ! isset($file_gallery_options["display_single_fieldset"]) ) || ( isset($file_gallery_options["display_gallery_fieldset"]) && isset($file_gallery_options["display_single_fieldset"]) && true != $file_gallery_options["display_gallery_fieldset"] && true != $file_gallery_options["display_single_fieldset"] ) ){ echo ' class="alt"'; }?>>
	
		<input type="button" value="<?php _e("Refresh attachments", "file-gallery"); ?>" title="<?php _e("Refresh attachments", "file-gallery"); ?>" class="button" id="file_gallery_refresh" />
	
		<div class="basic">
			<input type="button" value="<?php _e("Check all", "file-gallery"); ?>" title="<?php _e("Check all", "file-gallery"); ?>" class="button" id="file_gallery_check_all" />
			<input type="button" value="<?php _e("Uncheck all", "file-gallery"); ?>" title="<?php _e("Uncheck all", "file-gallery"); ?>" class="button" id="file_gallery_uncheck_all" />
			<input type="button" value="<?php _e("Delete all checked", "file-gallery"); ?>" title="<?php _e("Delete all checked", "file-gallery"); ?>" class="button" id="file_gallery_delete_checked" />
			<input type="button" value="<?php _e("Detach all checked", "file-gallery"); ?>" title="<?php _e("Detach all checked", "file-gallery"); ?>" class="button" id="file_gallery_detach_checked" />
		</div>
		
	<?php if( floatval(get_bloginfo('version')) >= 3.5 ) : ?>
		<a id="file_gallery_upload_media" href="#" class="insert-media button" title="<?php _e('Upload new files', 'file-gallery');?>"><?php _e('Upload new files', 'file-gallery');?></a>
	<?php else : ?>
		<a id="file_gallery_upload_media" href="media-upload.php?post_id=<?php echo $post_id;?>&amp;type=image&amp;TB_iframe=1&amp;tab=library" class="thickbox button" title="<?php _e('Upload new files', 'file-gallery');?>"><?php _e('Add an Image', 'file-gallery');?></a>
	<?php endif; ?>
		
		<input type="button" value="<?php _e("Copy all attachments from another post", "file-gallery"); ?>" title="<?php _e("Copy all attachments from another post", "file-gallery"); ?>" class="button" id="file_gallery_copy_all" />
	
		<div class="additional">
			<input type="button" value="<?php _e("Save attachment order", "file-gallery"); ?>" title="<?php _e("Save attachment order", "file-gallery"); ?>" class="button" id="file_gallery_save_menu_order" />
			<input type="button" value="<?php _e("Clear File Gallery cache", "file-gallery"); ?>" title="<?php _e("Clear File Gallery cache", "file-gallery"); ?>" class="button" id="file_gallery_clear_cache_manual" />
			<input type="button" value="<?php _e("Adjust media settings", "file-gallery"); ?>" title="<?php _e("Adjust media settings", "file-gallery"); ?>" class="button thickbox" alt="<?php echo admin_url("options-media.php"); ?>?TB_iframe=1" id="file_gallery_adjust_media_settings"  />
			<input type="button" value="<?php _e("Open help file", "file-gallery"); ?>" title="<?php _e("Open help file", "file-gallery"); ?>" class="button thickbox" alt="<?php echo FILE_GALLERY_URL; ?>/help/index.html?TB_iframe=1" id="file_gallery_open_help"  />
		</div>
		
	</div><!-- / fg_butons-->
	
	<div id="file-gallery-content">
	
	<?php if( (isset($file_gallery_options["display_gallery_fieldset"]) && true == $file_gallery_options["display_gallery_fieldset"]) || (isset($file_gallery_options["display_single_fieldset"]) && true == $file_gallery_options["display_single_fieldset"]) ) : ?>
		<p id="fg_info">
			<?php _e("Insert checked attachments into post as", "file-gallery"); ?>:
		</p>
	<?php endif; ?>
	
		<fieldset id="file_gallery_gallery_options"<?php if( false == $file_gallery_options["display_gallery_fieldset"] ){ echo ' class="hidden"'; }elseif( false == $gallery_state ){ echo ' class="closed"'; } ?>>
		
			<legend class="button-primary" id="file_gallery_send_gallery_legend" title="<?php _e("Insert a gallery", "file-gallery"); ?>"><?php _e("Insert a gallery", "file-gallery"); ?>:</legend>
			<input type="button" id="file_gallery_hide_gallery_options" class="<?php if( false == $gallery_state ){ echo 'closed'; }else{ echo 'open'; } ?>" title="<?php _e("show/hide this fieldset", "file-gallery"); ?>" />
	
			<div id="file_gallery_toggler"<?php if( false == $gallery_state ){ echo ' style="display: none;"'; } ?>>
	
				<p>
					<label for="file_gallery_size"><?php _e("size", "file-gallery"); ?>:</label>
					<select name="file_gallery_size" id="file_gallery_size">
					
						<option value="thumbnail"<?php if( "thumbnail" == $file_gallery_options["default_image_size"]){ ?> selected="selected"<?php } ?>><?php _e('thumbnail', 'file-gallery'); ?></option>
						<option value="medium"<?php if( "medium" == $file_gallery_options["default_image_size"]){ ?> selected="selected"<?php } ?>><?php _e('medium', 'file-gallery'); ?></option>
						<option value="large"<?php if( "large" == $file_gallery_options["default_image_size"]){ ?> selected="selected"<?php } ?>><?php _e('large', 'file-gallery'); ?></option>
						<option value="full"<?php if( "full" == $file_gallery_options["default_image_size"]){ ?> selected="selected"<?php } ?>><?php _e('full', 'file-gallery'); ?></option>
	
						<?php foreach( $sizes as $size ) : if( in_array($size, array('thumbnail', 'medium', 'large', 'full')) ){ continue; } ?>
						<option value="<?php echo $size; ?>"<?php if( $size == $file_gallery_options["default_image_size"]){ ?> selected="selected"<?php } ?>><?php echo $size; ?></option>
						<?php endforeach; ?>
					</select>
				</p>
			
				<p>
					<label for="file_gallery_linkto"><?php _e("link to", "file-gallery"); ?>:</label>
					<select name="file_gallery_linkto" id="file_gallery_linkto">
						<option value="none"<?php if( "none" == $file_gallery_options["default_linkto"]){ ?> selected="selected"<?php } ?>><?php _e("nothing (do not link)", "file-gallery"); ?></option>
						<option value="file"<?php if( "file" == $file_gallery_options["default_linkto"]){ ?> selected="selected"<?php } ?>><?php _e("file", "file-gallery"); ?></option>
						<?php if( floatval(get_bloginfo('version')) >= 3.5 ) : ?>
							<option value="post"<?php if( "attachment" == $file_gallery_options["default_linkto"]){ ?> selected="selected"<?php } ?>><?php _e("attachment page", "file-gallery"); ?></option>
						<?php else: ?>
							<option value="attachment"<?php if( "attachment" == $file_gallery_options["default_linkto"]){ ?> selected="selected"<?php } ?>><?php _e("attachment page", "file-gallery"); ?></option>
						<?php endif; ?>

						<option value="parent_post"<?php if( "parent_post" == $file_gallery_options["default_linkto"]){ ?> selected="selected"<?php } ?>><?php _e("parent post", "file-gallery"); ?></option>
						<option value="external_url"<?php if( "external_url" == $file_gallery_options["default_external_url"]){ ?> selected="selected"<?php } ?>><?php _e("external url", "file-gallery"); ?></option>
					</select>
				</p>
				
				<p id="file_gallery_external_url_label">
					<label for="file_gallery_external_url"><?php _e("external url", "file-gallery"); ?>:</label>
					<input type="text" name="file_gallery_external_url" id="file_gallery_external_url" value="<?php echo $file_gallery_options["default_external_url"]; ?>" />
				</p>
				
				<p id="file_gallery_linksize_label">
					<label for="file_gallery_linksize"><?php _e("linked image size", "file-gallery"); ?>:</label>
					<select name="file_gallery_linksize" id="file_gallery_linksize">
					<option value="thumbnail"<?php if( "thumbnail" == $file_gallery_options["default_linked_image_size"]){ ?> selected="selected"<?php } ?>><?php _e('thumbnail', 'file-gallery'); ?></option>
						<option value="medium"<?php if( "medium" == $file_gallery_options["default_linked_image_size"]){ ?> selected="selected"<?php } ?>><?php _e('medium', 'file-gallery'); ?></option>
						<option value="large"<?php if( "large" == $file_gallery_options["default_linked_image_size"]){ ?> selected="selected"<?php } ?>><?php _e('large', 'file-gallery'); ?></option>
						<option value="full"<?php if( "full" == $file_gallery_options["default_linked_image_size"]){ ?> selected="selected"<?php } ?>><?php _e('full', 'file-gallery'); ?></option>
	
						<?php foreach( $sizes as $size ) : if( in_array($size, array('thumbnail', 'medium', 'large', 'full')) ){ continue; } ?>
						<option value="<?php echo $size; ?>"<?php if( $size == $file_gallery_options["default_linked_image_size"]){ ?> selected="selected"<?php } ?>><?php echo $size; ?></option>
						<?php endforeach; ?>
					</select>
				</p>
				
				<p id="file_gallery_linkrel_label">
					<label for="file_gallery_linkrel"><?php _e("link 'rel' attribute", "file-gallery"); ?>:</label>
					<select type="text" name="file_gallery_linkrel" id="file_gallery_linkrel">
						<option value="true"><?php _e("true (auto generated)", "file-gallery"); ?></option>
						<option value="false"><?php _e("false", "file-gallery"); ?></option>
					</select>
	
					<span id="file_gallery_linkrel_custom_label">
						&nbsp;<em><?php _e('or', 'file-gallery'); ?></em>&nbsp;
						<label for="file_gallery_linkrel_custom"><?php _e("custom value", "file-gallery"); ?>:</label>
						<input type="text" name="file_gallery_linkrel_custom" id="file_gallery_linkrel_custom" value="" />
					</span>
				</p>
				
				<p id="file_gallery_linkclass_label">
					<label for="file_gallery_linkclass"><?php _e("link class", "file-gallery"); ?>:</label>
					<input type="text" name="file_gallery_linkclass" id="file_gallery_linkclass" value="<?php echo $file_gallery_options["default_linkclass"]; ?>" />
				</p>
			
				<p>
					<label for="file_gallery_orderby"><?php _e("order", "file-gallery"); ?>:</label>
					<select name="file_gallery_orderby" id="file_gallery_orderby">
						<option value="default"<?php if( "default" == $file_gallery_options["default_orderby"]){ ?> selected="selected"<?php } ?>><?php _e("file gallery", "file-gallery"); ?></option>
						<option value="rand"<?php if( "rand" == $file_gallery_options["default_orderby"]){ ?> selected="selected"<?php } ?>><?php _e("random", "file-gallery"); ?></option>
						<option value="menu_order"<?php if( "menu_order" == $file_gallery_options["default_orderby"]){ ?> selected="selected"<?php } ?>><?php _e("menu order", "file-gallery"); ?></option>
						<option value="title"<?php if( "title" == $file_gallery_options["default_orderby"]){ ?> selected="selected"<?php } ?>><?php _e("title", "file-gallery"); ?></option>
						<option value="ID"<?php if( "ID" == $file_gallery_options["default_orderby"]){ ?> selected="selected"<?php } ?>><?php _e("date / time", "file-gallery"); ?></option>
					</select>
					<select name="file_gallery_order" id="file_gallery_order">
						<option value="ASC"<?php if( "ASC" == $file_gallery_options["default_order"]){ ?> selected="selected"<?php } ?>><?php _e("ASC", "file-gallery"); ?></option>
						<option value="DESC"<?php if( "DESC" == $file_gallery_options["default_order"]){ ?> selected="selected"<?php } ?>><?php _e("DESC", "file-gallery"); ?></option>
					</select>
				</p>
			
				<p>
					<label for="file_gallery_template"><?php _e("template", "file-gallery"); ?>:</label>
					<select name="file_gallery_template" id="file_gallery_template">
						<?php
							$file_gallery_templates = file_gallery_get_templates('main-form');
					
							foreach( $file_gallery_templates as $template_name )
							{
								$templates_dropdown .= "<option value=\"" . $template_name . "\"";
								
								if( $file_gallery_options["default_template"] == $template_name )
									$templates_dropdown .= ' selected="selected"';
								
								$templates_dropdown .=">" . $template_name . "</option>\n";
							}
							
							echo $templates_dropdown;
						?>
					</select>
				</p>
				
				<p>
					<label for="file_gallery_galleryclass"><?php _e("gallery class", "file-gallery"); ?>:</label>
					<input type="text" name="file_gallery_galleryclass" id="file_gallery_galleryclass" value="<?php echo $file_gallery_options["default_galleryclass"]; ?>" />
				</p>
		
				<p>
					<label for="file_gallery_imageclass"><?php _e("image class", "file-gallery"); ?>:</label>
					<input type="text" name="file_gallery_imageclass" id="file_gallery_imageclass" value="<?php echo $file_gallery_options["default_imageclass"]; ?>" />
				</p>
				
				<p>
					<label for="file_gallery_mimetype"><?php _e("mime type", "file-gallery"); ?>:</label>
					<input type="text" name="file_gallery_mimetype" id="file_gallery_mimetype" value="<?php echo $file_gallery_options["default_mimetype"]; ?>" />
				</p>
				
				<p>
					<label for="file_gallery_limit"><?php _e("limit", "file-gallery"); ?>:</label>
					<input type="text" name="file_gallery_limit" id="file_gallery_limit" value="" />
				</p>
				
				<p>
					<label for="file_gallery_offset"><?php _e("offset", "file-gallery"); ?>:</label>
					<input type="text" name="file_gallery_offset" id="file_gallery_offset" value="" />
				</p>
				
				<p id="file_gallery_paginate_label">
					<label for="file_gallery_paginate"><?php _e("paginate", "file-gallery"); ?>:</label>
					<select type="text" name="file_gallery_paginate" id="file_gallery_paginate">
						<option value="true">true</option>
						<option value="false">false</option>
					</select>
				</p>
				
				<p>
					<label for="file_gallery_columns"><?php _e("columns", "file-gallery"); ?>:</label>
					<select name="file_gallery_columns" id="file_gallery_columns">
					<?php
						$col_def = $file_gallery_options["default_columns"];
						
						for( $i=0; $i < 10; $i++ )
						{
							$selected = "";
	
							if( $i == $col_def )
								$selected = ' selected="selected"';
							
							echo '<option value="' . $i . '"' . $selected . '>' . $i . "</option>\n";
						}
					?>
					</select>
				</p>
				
				<p>
					<label for="file_gallery_postid"><?php _e("Post ID:", "file-gallery"); ?></label>
					<input type="text" name="file_gallery_postid" id="file_gallery_postid" value="" />
				</p>
				
				<br />
				
				<input type="button" id="file_gallery_send_gallery" value="<?php _e("Insert a gallery", "file-gallery"); ?>" class="button-primary" />&nbsp;
				
				<br class="clear" />
				
				<p id="fg_gallery_tags_container">
					<label for="fg_gallery_tags"><?php _e("Media tags", "file-gallery");?>:</label>
					<input type="text" id="fg_gallery_tags" name="fg_gallery_tags" value="<?php if( isset($_POST["tag_list"]) ){ echo $_POST["tag_list"];} ?>" />
		
					<label for="fg_gallery_tags_from"><?php _e("current post's attachments only?", "file-gallery"); ?></label>
					<input type="checkbox" id="fg_gallery_tags_from" name="fg_gallery_tags_from" checked="checked" />
				</p>
				
				<!--<input type="button" onclick="file_gallery_preview_template(jQuery('#file_gallery_template').val()); return false;" value="&uArr;" title="preview template" class="button" />-->
				
			</div>
			
		</fieldset>
		
	<?php if( false == $file_gallery_options["display_gallery_fieldset"] && true == $file_gallery_options['insert_gallery_button'] ) : ?>
		<input type="button" class="button-primary" id="file_gallery_send_gallery_legend" value="<?php _e("Insert a gallery", "file-gallery"); ?>" />
	<?php endif; ?>
		
		<!-- SINGLE IMAGE OPTIONS -->
	
		<fieldset id="file_gallery_single_options"<?php if( false == $file_gallery_options["display_single_fieldset"] ){ echo ' class="hidden"'; }elseif( false == $single_state ){ echo ' class="closed"'; } ?>>
		
			<legend class="button-primary" id="file_gallery_send_single_legend" title="<?php _e("Insert single files", "file-gallery"); ?>"><?php _e("Insert single files", "file-gallery"); ?>:</legend>
			<input type="button" id="file_gallery_hide_single_options" class="<?php if( false == $single_state ){ echo 'closed'; }else{ echo 'open'; } ?>" title="<?php _e("show/hide this fieldset", "file-gallery"); ?>" />
	
			<div id="file_gallery_single_toggler"<?php if( false == $single_state ){ echo ' style="display: none;"'; } ?>>
				<p>
					<label for="file_gallery_single_size"><?php _e("size", "file-gallery"); ?>:</label>
					<select name="file_gallery_single_size" id="file_gallery_single_size">
						<?php foreach( $sizes as $size ) : ?>
						<option value="<?php echo $size; ?>"<?php if( $size == $file_gallery_options["single_default_image_size"]){ ?> selected="selected"<?php } ?>><?php echo $size; ?></option>
						<?php endforeach; ?>
					</select>
				</p>
				
				<p>
					<label for="file_gallery_single_linkto"><?php _e("link to", "file-gallery"); ?>:</label>
					<select name="file_gallery_single_linkto" id="file_gallery_single_linkto">
						<option value="none"<?php if( "none" == $file_gallery_options["single_default_linkto"]){ ?> selected="selected"<?php } ?>><?php _e("nothing (do not link)", "file-gallery"); ?></option>
						<option value="file"<?php if( "file" == $file_gallery_options["single_default_linkto"]){ ?> selected="selected"<?php } ?>><?php _e("file", "file-gallery"); ?></option>
						<option value="attachment"<?php if( "attachment" == $file_gallery_options["single_default_linkto"]){ ?> selected="selected"<?php } ?>><?php _e("attachment page", "file-gallery"); ?></option>
						<option value="parent_post"<?php if( "parent_post" == $file_gallery_options["single_default_linkto"]){ ?> selected="selected"<?php } ?>><?php _e("parent post", "file-gallery"); ?></option>
						<option value="external_url"<?php if( "external_url" == $file_gallery_options["single_default_external_url"]){ ?> selected="selected"<?php } ?>><?php _e("external url", "file-gallery"); ?></option>
					</select>
				</p>
				
				<p id="file_gallery_single_external_url_label">
					<label for="file_gallery_single_external_url"><?php _e("external url", "file-gallery"); ?>:</label>
					<input type="text" name="file_gallery_single_external_url" id="file_gallery_single_external_url" value="<?php echo $file_gallery_options["single_default_external_url"]; ?>" />
				</p>
				
				<p id="file_gallery_single_linkclass_label">
					<label for="file_gallery_single_linkclass"><?php _e("link class", "file-gallery"); ?>:</label>
					<input type="text" name="file_gallery_single_linkclass" id="file_gallery_single_linkclass" value="<?php echo $file_gallery_options["single_default_linkclass"]; ?>" />
				</p>
				
				<p>
					<label for="file_gallery_single_imageclass"><?php _e("image class", "file-gallery"); ?>:</label>
					<input type="text" name="file_gallery_single_imageclass" id="file_gallery_single_imageclass" value="<?php echo $file_gallery_options["single_default_imageclass"]; ?>" />
				</p>
				
				<p>
					<label for="file_gallery_single_align"><?php _e("alignment", "file-gallery"); ?>:</label>
					<select name="file_gallery_single_align" id="file_gallery_single_align">
						<option value="none"<?php if( "none" == $file_gallery_options["single_default_align"]){ ?> selected="selected"<?php } ?>><?php _e("none", "file-gallery"); ?></option>
						<option value="left"<?php if( "left" == $file_gallery_options["single_default_align"]){ ?> selected="selected"<?php } ?>><?php _e("left", "file-gallery"); ?></option>
						<option value="right"<?php if( "right" == $file_gallery_options["single_default_align"]){ ?> selected="selected"<?php } ?>><?php _e("right", "file-gallery"); ?></option>
						<option value="center"<?php if( "center" == $file_gallery_options["single_default_align"]){ ?> selected="selected"<?php } ?>><?php _e("center", "file-gallery"); ?></option>
					</select>
				</p>
				
				<p>
					<label for="file_gallery_single_caption"><?php _e("display caption?", "file-gallery"); ?></label>
					<input type="checkbox" id="file_gallery_single_caption" name="file_gallery_single_caption" checked="checked" />
				</p>
				
				<br />
				
				<input type="button" id="file_gallery_send_single" value="<?php _e("Insert single files", "file-gallery"); ?>" class="button-primary" />&nbsp;
			</div>
			
		</fieldset>
		
	<?php if( 3.3 <= floatval(get_bloginfo('version')) ) : ?>
		<iframe name="file_gallery_upload_iframe" id="file_gallery_upload_area" src="<?php echo admin_url('media-upload.php?file_gallery=true&post_id=' . $post_id); ?>" ondragenter="event.stopPropagation(); event.preventDefault();" ondragover="event.stopPropagation(); event.preventDefault();" ondrop="event.stopPropagation(); event.preventDefault();"></iframe>
	<?php endif; ?>
	
	<?php if( false == $file_gallery_options["display_single_fieldset"] && true == $file_gallery_options['insert_single_button'] ) : ?>
		<input type="button" class="button-primary" id="file_gallery_send_single_legend" value="<?php _e("Insert single files", "file-gallery"); ?>" />
	<?php endif; ?>
		
		<fieldset id="file_gallery_tag_attachment_switcher">
		
			<input type="button" id="file_gallery_switch_to_tags" value="<?php _e("Switch to tags", "file-gallery"); ?>" class="button" />
			<input type="hidden" id="files_or_tags" value="<?php echo $files_or_tags; ?>" />
		
		</fieldset>
		
		<fieldset id="file_gallery_textual_switcher">
		
			<input type="button" id="file_gallery_toggle_textual" value="<?php _e('Toggle \'textual\' mode', 'file-gallery'); ?>" class="button" />
			<input type="hidden" id="textual" value="<?php echo $files_or_tags; ?>" />
		
		</fieldset>
	
	
	
		<div id="file_gallery_attachment_list">
		
			<p id="file_gallery_attachments_sorting">
				<label for="file_gallery_attachments_sortby"><?php _e('Sort attachments by', 'file-gallery'); ?></label>
			
				<select id="file_gallery_attachments_sortby">
					<option value="menu_order"<?php if( 'menu_order' == $attachment_orderby){ echo ' selected="selected"'; } ?>><?php _e('menu order', 'file-gallery'); ?></option>
					<option value="post_title"<?php if( 'post_title' == $attachment_orderby){ echo ' selected="selected"'; } ?>><?php _e('title', 'file-gallery'); ?></option>
					<option value="post_name"<?php if( 'post_name' == $attachment_orderby){ echo ' selected="selected"'; } ?>><?php _e('name', 'file-gallery'); ?></option>
					<option value="post_date"<?php if( 'post_date' == $attachment_orderby){ echo ' selected="selected"'; } ?>><?php _e('date', 'file-gallery'); ?></option>
				</select>
				
				<select id="file_gallery_attachments_sort">
					<option value="ASC"<?php if( 'ASC' == $attachment_order){ echo ' selected="selected"'; } ?>><?php _e('ASC', 'file-gallery'); ?></option>
					<option value="DESC"<?php if( 'DESC' == $attachment_order){ echo ' selected="selected"'; } ?>><?php _e('DESC', 'file-gallery'); ?></option>
				</select>
				
				<input type="button" id="file_gallery_attachments_sort_submit" class="button button-primary" value="<?php _e('Go', 'file-gallery'); ?>" />
			</p>
			
			<a href="#" id="file_gallery_save_menu_order_link" class="button button-secondary"><?php _e("Save attachment order", "file-gallery"); ?></a>
		
			<?php echo file_gallery_list_attachments($count_attachments, $post_id, $attachment_order, $checked_attachments, $attachment_orderby); ?>
		
		</div>
		
		<div id="file_gallery_tag_list">
			<?php file_gallery_list_tags( array("link" => true, "separator" => " ") ); ?>
		</div>
	
	</div><!-- / file-gallery-content -->
	
</div>

<?php

// prints number of attachments
$print_attachment_count = __("File Gallery &mdash; %d attachment.", "file-gallery");

if( 0 == $count_attachments || $count_attachments > 1 )
	$print_attachment_count = __("File Gallery &mdash; %d attachments.", "file-gallery");

echo '<script type="text/javascript">
		if( ' . $count_attachments . ' )
			jQuery("#file_gallery").addClass("has-attachments").removeClass("no-attachments");
		else
			jQuery("#file_gallery").removeClass("has-attachments").addClass("no-attachments");
			
		jQuery("#file_gallery .hndle").html("<span>' . sprintf($print_attachment_count, $count_attachments) . '</span>");
	  </script>';
