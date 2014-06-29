<?php
global $post_ID;

$opts = get_option('file_gallery');
$sizes = file_gallery_get_intermediate_image_sizes();
$files_or_tags = 'files';
?>

<div id="file_gallery_inner">
</div>

<script type="text/javascript">
	var file_gallery_setAsThumbnailNonce = "<?php echo wp_create_nonce( "set_post_thumbnail-$post_ID" ); ?>";
</script>


<script type="text/ractive" id="file_gallery_ractive_template">

	<div id="file_gallery_response">
	{{#responseLoading}}<span class="loading">Loading</span>{{/responseLoading}}
	<span class="text {{responseLoading ? 'hidden' : ''}}">{{actionResponse}}</span>
	</div>

	<div id="fg_container" on-dragenter="dragenter" on-dragleave="dragleave" class="{{upload_inside ? ' uploader' : ''}}{{singleEditMode ? ' hidden' : ''}}">

		<div id="file_gallery_form" class="alternative-color-scheme">

			<div id="fg_buttons" class="wp-media-buttons">

				<button on-click="refresh" title="<?php _e("Refresh attachments", "file-gallery"); ?>" id="file_gallery_refresh" class="button"><div class="dashicons dashicons-update"></div><span class="screen-reader-text"><?php _e("Refresh attachments", "file-gallery"); ?></span></button>

				<div class="basic">
					<button on-click="checkAll" title="<?php _e("Check all", "file-gallery"); ?>" id="file_gallery_check_all" class="button"><div class="dashicons dashicons-yes"></div><span class="screen-reader-text"><?php _e("Check all", "file-gallery"); ?></span></button>
					<button on-click="uncheckAll" title="<?php _e("Uncheck all", "file-gallery"); ?>" id="file_gallery_uncheck_all" class="button"><div class="dashicons dashicons-minus"></div><span class="screen-reader-text"><?php _e("Uncheck all", "file-gallery"); ?></span></button>
					<button on-click="deleteChecked" title="<?php _e("Delete all checked", "file-gallery"); ?>" id="file_gallery_delete_checked" class="button"><div class="dashicons dashicons-trash"></div><span class="screen-reader-text"><?php _e("Delete all checked", "file-gallery"); ?></span></button>
					<button on-click="detachChecked" title="<?php _e("Detach all checked", "file-gallery"); ?>" id="file_gallery_detach_checked" class="button"><div class="dashicons dashicons-editor-unlink"></div><span class="screen-reader-text"><?php _e("Detach all checked", "file-gallery"); ?></span></button>
				</div>

				<button id="file_gallery_upload_media" class="insert-media add_media button" title="<?php _e('Upload new files', 'file-gallery');?>"><div class="dashicons dashicons-admin-media"></div><span class="screen-reader-text"><?php _e('Upload new files', 'file-gallery');?></span></button>

				<button on-click="copyAllFrom" title="<?php _e("Copy all attachments from another post", "file-gallery"); ?>" id="file_gallery_copy_all" class="button"><div class="dashicons dashicons-images-alt2"></div><span class="screen-reader-text"><?php _e("Copy all attachments from another post", "file-gallery"); ?></span></button>

				<div class="additional">
					<button title="<?php _e("Clear File Gallery cache", "file-gallery"); ?>" id="file_gallery_clear_cache_manual" class="button"><div class="dashicons dashicons-editor-removeformatting"></div><span class="screen-reader-text"><?php _e("Clear File Gallery cache", "file-gallery"); ?></span></button>
					<button title="<?php _e("Adjust media settings", "file-gallery"); ?>" class="button thickbox" alt="<?php echo admin_url("options-media.php"); ?>?TB_iframe=1" id="file_gallery_adjust_media_settings"><div class="dashicons dashicons-admin-generic"></div><span class="screen-reader-text"><?php _e("Adjust media settings", "file-gallery"); ?></span></button>
					<button title="<?php _e("Open help file", "file-gallery"); ?>" class="thickbox button" alt="<?php echo FILE_GALLERY_URL; ?>/help/index.html?TB_iframe=1" id="file_gallery_open_help"><div class="dashicons dashicons-editor-help"></div><span class="screen-reader-text"><?php _e("Open help file", "file-gallery"); ?></span></button>
				</div>

			</div>



			<div id="file-gallery-content">

				<p id="fg_info">
					<?php _e("Insert checked attachments into post as", "file-gallery"); ?>:
				</p>

				<fieldset id="file_gallery_gallery_options" class="file_gallery_options">

					<h3><?php _e("Gallery", "file-gallery"); ?> <button class="toggler" on-click="fieldsetToggle:hide_gallery_options" title="<?php _e("show/hide this fieldset", "file-gallery"); ?>"><div class="dashicons dashicons-arrow-{{insert_options_state === 0 ? 'down' : 'up'}}"></div></button></h3>

					<div id="file_gallery_toggler" class="{{insert_options_state === 0 ? 'closed' : ''}}">
						<p>
							<label for="file_gallery_size"><?php _e("size", "file-gallery"); ?>:</label>
							<select on-change="changeOption:size" name="file_gallery_size" id="file_gallery_size">
								<option value="thumbnail"><?php _e('thumbnail', 'file-gallery'); ?></option>
								<option value="medium"><?php _e('medium', 'file-gallery'); ?></option>
								<option value="large"><?php _e('large', 'file-gallery'); ?></option>
								<option value="full"><?php _e('full', 'file-gallery'); ?></option>
								<?php
								foreach( $sizes as $size ) :
									if( ! in_array($size, array('thumbnail', 'medium', 'large', 'full')) ) :
								?>
								<option value="<?php echo $size; ?>"><?php echo $size; ?></option>
								<?php
									endif;
								endforeach; ?>
							</select>
						</p>

						<p>
							<label for="file_gallery_link"><?php _e("link to", "file-gallery"); ?>:</label>
							<select on-change="changeOption:link" name="file_gallery_link" id="file_gallery_link">
								<option value="none"><?php _e("nothing (do not link)", "file-gallery"); ?></option>
								<option value="file"><?php _e("file", "file-gallery"); ?></option>
								<option value="post"><?php _e("attachment page", "file-gallery"); ?></option>
								<option value="parent_post"><?php _e("parent post", "file-gallery"); ?></option>
								<option value="external_url"><?php _e("external url", "file-gallery"); ?></option>
							</select>
						</p>

						<p id="file_gallery_external_url_label">
							<label for="file_gallery_external_url"><?php _e("external url", "file-gallery"); ?>:</label>
							<input on-change="changeOption:external_url" type="text" name="file_gallery_external_url" id="file_gallery_external_url" value="{{galleryOptions.external_url}}" />
						</p>

						<p id="file_gallery_linksize_label">
							<label for="file_gallery_linksize"><?php _e("linked image size", "file-gallery"); ?>:</label>
							<select on-change="changeOption:linksize" name="file_gallery_linksize" id="file_gallery_linksize">
								<option value="thumbnail"><?php _e('thumbnail', 'file-gallery'); ?></option>
								<option value="medium"><?php _e('medium', 'file-gallery'); ?></option>
								<option value="large"><?php _e('large', 'file-gallery'); ?></option>
								<option value="full"><?php _e('full', 'file-gallery'); ?></option>
							<?php
							foreach( $sizes as $size ) :
								if( ! in_array($size, array('thumbnail', 'medium', 'large', 'full')) ) :
							?>
								<option value="<?php echo $size; ?>"><?php echo $size; ?></option>
							<?php
								endif;
							endforeach;
								?>
							</select>
						</p>

						<p id="file_gallery_linkrel_label">
							<label for="file_gallery_linkrel"><?php _e("link 'rel' attribute", "file-gallery"); ?>:</label>
							<select on-change="changeOption:linkrel" type="text" name="file_gallery_linkrel" id="file_gallery_linkrel">
								<option value="true"><?php _e("true (auto generated)", "file-gallery"); ?></option>
								<option value="false"><?php _e("false", "file-gallery"); ?></option>
							</select>

							<span id="file_gallery_linkrel_custom_label">
								&nbsp;<em><?php _e('or', 'file-gallery'); ?></em>&nbsp;
								<label for="file_gallery_linkrel_custom"><?php _e("custom value", "file-gallery"); ?>:</label>
								<input on-change="changeOption:linkrel_custom" type="text" name="file_gallery_linkrel_custom" id="file_gallery_linkrel_custom" value="{{galleryOptions.linkrel_custom}}" />
							</span>
						</p>

						<p id="file_gallery_linkclass_label">
							<label for="file_gallery_linkclass"><?php _e("link class", "file-gallery"); ?>:</label>
							<input on-change="changeOption:linkclass" type="text" name="file_gallery_linkclass" id="file_gallery_linkclass" value="{{galleryOptions.linkclass}}" />
						</p>

						<p>
							<label for="file_gallery_orderby"><?php _e("order", "file-gallery"); ?>:</label>
							<select on-change="changeOption:orderby" name="file_gallery_orderby" id="file_gallery_orderby">
								<option value="default"><?php _e("file gallery", "file-gallery"); ?></option>
								<option value="rand"><?php _e("random", "file-gallery"); ?></option>
								<option value="menu_order"><?php _e("menu order", "file-gallery"); ?></option>
								<option value="title"><?php _e("title", "file-gallery"); ?></option>
								<option value="ID"><?php _e("date / time", "file-gallery"); ?></option>
							</select>
							<select on-change="changeOption:order" name="file_gallery_order" id="file_gallery_order">
								<option value="ASC"><?php _e("ASC", "file-gallery"); ?></option>
								<option value="DESC"><?php _e("DESC", "file-gallery"); ?></option>
							</select>
						</p>

						<p>
							<label for="file_gallery_template"><?php _e("template", "file-gallery"); ?>:</label>
							<select on-change="changeOption:template" name="file_gallery_template" id="file_gallery_template">
								<?php
									$file_gallery_templates = file_gallery_get_templates('main-form');
	
									foreach( $file_gallery_templates as $template_name )
									{
										echo '<option value="' . $template_name . '">' . $template_name . "</option>\n";
									}
								?>
							</select>
						</p>

						<p>
							<label for="file_gallery_galleryclass"><?php _e("gallery class", "file-gallery"); ?>:</label>
							<input on-change="changeOption:galleryclass" type="text" name="file_gallery_galleryclass" id="file_gallery_galleryclass" value="{{galleryOptions.galleryclass}}" />
						</p>

						<p>
							<label for="file_gallery_imageclass"><?php _e("image class", "file-gallery"); ?>:</label>
							<input on-change="changeOption:imageclass" type="text" name="file_gallery_imageclass" id="file_gallery_imageclass" value="{{galleryOptions.imageclass}}" />
						</p>

						<p>
							<label for="file_gallery_mimetype"><?php _e("mime type", "file-gallery"); ?>:</label>
							<input on-change="changeOption:mimetype" type="text" name="file_gallery_mimetype" id="file_gallery_mimetype" value="{{galleryOptions.mimetype}}" />
						</p>

						<p>
							<label for="file_gallery_limit"><?php _e("limit", "file-gallery"); ?>:</label>
							<input on-change="changeOption:limit" type="text" name="file_gallery_limit" id="file_gallery_limit" value="{{galleryOptions.limit}}" />
						</p>

						<p>
							<label for="file_gallery_offset"><?php _e("offset", "file-gallery"); ?>:</label>
							<input on-change="changeOption:offset" type="text" name="file_gallery_offset" id="file_gallery_offset" value="{{galleryOptions.offset}}" />
						</p>

						<p id="file_gallery_paginate_label">
							<label for="file_gallery_paginate"><?php _e("paginate", "file-gallery"); ?>:</label>
							<select on-change="changeOption:paginate" type="text" name="file_gallery_paginate" id="file_gallery_paginate">
								<option value="true">true</option>
								<option value="false">false</option>
							</select>
						</p>

						<p>
							<label for="file_gallery_columns"><?php _e("columns", "file-gallery"); ?>:</label>
							<select on-change="changeOption:columns" name="file_gallery_columns" id="file_gallery_columns">
							<?php
								for( $i=0; $i < 10; $i++ )
								{
									echo '<option value="' . $i . '">' . $i . "</option>\n";
								}
							?>
							</select>
						</p>

						<p>
							<label for="file_gallery_id"><?php _e("Post ID:", "file-gallery"); ?></label>
							<input on-change="changeOption:id" type="text" name="file_gallery_id" id="file_gallery_id" value="{{galleryOptions.id}}" />
						</p>

						<p id="file_gallery_tags_container">
							<label for="file_gallery_tags"><?php _e("Media tags", "file-gallery");?>:</label>
							<input on-change="changeOption:tags" type="text" id="file_gallery_tags" name="file_gallery_tags" value="{{galleryOptions.tags}}" />

							<label for="file_gallery_tags_from"><?php _e("current post's attachments only?", "file-gallery"); ?></label>
							<input on-change="changeOption:tags_from" type="checkbox" id="file_gallery_tags_from" name="file_gallery_tags_from" checked="checked" />
						</p>

						<button on-click="insertGallery" class="button button-large button-secondary insert"><?php _e("Insert into post", "file-gallery"); ?></button>

					</div>

				</fieldset>



				<fieldset id="file_gallery_single_options" class="file_gallery_options">

					<h3><?php _e("Single files", "file-gallery"); ?> <button class="toggler" on-click="fieldsetToggle:hide_single_options" title="<?php _e("show/hide this fieldset", "file-gallery"); ?>"><div class="dashicons dashicons-arrow-{{insert_single_options_state === 0 ? 'down' : 'up'}}"></div></button></h3>

					<div id="file_gallery_single_toggler" class="{{insert_single_options_state === 0 ? 'closed' : ''}}">
						<p>
							<label for="file_gallery_single_size"><?php _e("size", "file-gallery"); ?>:</label>
							<select on-change="changeSingleOption:size" name="file_gallery_single_size" id="file_gallery_single_size">
							<?php foreach( $sizes as $size ) : ?>
								<option value="<?php echo $size; ?>"><?php echo $size; ?></option>
							<?php endforeach; ?>
							</select>
						</p>

						<p>
							<label for="file_gallery_single_linkto"><?php _e("link to", "file-gallery"); ?>:</label>
							<select on-change="changeSingleOption:linkto" name="file_gallery_single_linkto" id="file_gallery_single_linkto">
								<option value="none"><?php _e("nothing (do not link)", "file-gallery"); ?></option>
								<option value="file"><?php _e("file", "file-gallery"); ?></option>
								<option value="attachment"><?php _e("attachment page", "file-gallery"); ?></option>
								<option value="parent_post"><?php _e("parent post", "file-gallery"); ?></option>
								<option value="external_url"><?php _e("external url", "file-gallery"); ?></option>
							</select>
						</p>

						<p id="file_gallery_single_external_url_label">
							<label for="file_gallery_single_external_url"><?php _e("external url", "file-gallery"); ?>:</label>
							<input on-change="changeSingleOption:external_url" type="text" name="file_gallery_single_external_url" id="file_gallery_single_external_url" value="{{singleOptions.external_url}}" />
						</p>

						<p id="file_gallery_single_linkclass_label">
							<label for="file_gallery_single_linkclass"><?php _e("link class", "file-gallery"); ?>:</label>
							<input on-change="changeSingleOption:linkclass" type="text" name="file_gallery_single_linkclass" id="file_gallery_single_linkclass" value="{{singleOptions.linkclass}}" />
						</p>

						<p>
							<label for="file_gallery_single_imageclass"><?php _e("image class", "file-gallery"); ?>:</label>
							<input on-change="changeSingleOption:imageclass" type="text" name="file_gallery_single_imageclass" id="file_gallery_single_imageclass" value="{{singleOptions.imageclass}}" />
						</p>

						<p>
							<label for="file_gallery_single_align"><?php _e("alignment", "file-gallery"); ?>:</label>
							<select on-change="changeSingleOption:align" name="file_gallery_single_align" id="file_gallery_single_align">
								<option value="none"><?php _e("none", "file-gallery"); ?></option>
								<option value="left"><?php _e("left", "file-gallery"); ?></option>
								<option value="right"><?php _e("right", "file-gallery"); ?></option>
								<option value="center"><?php _e("center", "file-gallery"); ?></option>
							</select>
						</p>

						<p>
							<label for="file_gallery_single_caption"><?php _e("display caption?", "file-gallery"); ?></label>
							<input type="checkbox" on-change="changeSingleOption:caption" id="file_gallery_single_caption" name="file_gallery_single_caption" checked="{{singleOptions.caption ? 'checked' : ''}}" />
						</p>

						<button on-click="insertSingle" class="button button-large button-secondary insert"><?php _e("Insert into post", "file-gallery"); ?></button>
					</div>
				</fieldset>

				<iframe name="file_gallery_upload_iframe" id="file_gallery_upload_area" src="<?php echo admin_url('media-upload.php?file_gallery=true&post_id=' . $post_ID); ?>" ondragenter="event.stopPropagation(); event.preventDefault();" ondragover="event.stopPropagation(); event.preventDefault();" ondrop="event.stopPropagation(); event.preventDefault();"></iframe>

				<fieldset id="file_gallery_tag_attachment_switcher">
					<button id="file_gallery_switch_to_tags" class="button"><?php _e("Switch to tags", "file-gallery"); ?></button>
					<input type="hidden" id="files_or_tags" value="<?php echo $files_or_tags; ?>" />
				</fieldset>

				<fieldset id="file_gallery_textual_switcher">
					<button id="file_gallery_toggle_textual" class="button"><?php _e('Toggle \'textual\' mode', 'file-gallery'); ?></button>
					<input type="hidden" id="textual" value="<?php echo $files_or_tags; ?>" />
				</fieldset>

				<div id="file_gallery_attachment_list">

					<p id="file_gallery_attachments_sorting">
						<label for="file_gallery_attachments_sortby"><?php _e('Sort attachments by', 'file-gallery'); ?></label>
						<select id="file_gallery_attachments_sortby">
							<option value="menu_order"><?php _e('menu order', 'file-gallery'); ?></option>
							<option value="post_title"><?php _e('title', 'file-gallery'); ?></option>
							<option value="post_name"><?php _e('name', 'file-gallery'); ?></option>
							<option value="post_date"><?php _e('date', 'file-gallery'); ?></option>
						</select>
						<select id="file_gallery_attachments_sort">
							<option value="ASC"><?php _e('ASC', 'file-gallery'); ?></option>
							<option value="DESC"><?php _e('DESC', 'file-gallery'); ?></option>
						</select>
						<button id="file_gallery_attachments_sort_submit" class="button button-secondary"><?php _e('Go', 'file-gallery'); ?></button>
					</p>

					<a href="#" id="file_gallery_save_menu_order_link" class="button button-secondary"><?php _e("Save attachment order", "file-gallery"); ?></a>

					<style type="text/css">
						.file_gallery_list .attachment
						{
							width: {{ parseInt(attachments.0.itemWidth, 10) + 4}}px;
							height: {{ parseInt(attachments.0.itemHeight, 10) + 4}}px;
						}

						.file_gallery_list .attachment-preview,
						.file_gallery_list .attachment-preview .thumbnail
						{
							width: {{attachments.0.iconWidth}};
							height: {{attachments.0.iconHeight}};
						}

						.file_gallery_list .attachment-preview img
						{
							width: auto;
							height: auto;
							max-width: {{attachments.0.iconWidth}};
							max-height: {{attachments.0.iconHeight}};
						}
					</style>

					<ul class="ui-sortable file_gallery_list {{gallerySelected && galleryAttachments.length ? 'active' : ''}}" id="file_gallery_galleryAttachments">
					{{#galleryAttachments:i}}
						<li id="file-gallery-galleryitem-{{ID}}" class="sortableitem attachment {{itemClasses}} {{selected ? 'selected details' : ''}} {{isPostThumb ? 'post_thumb' : ''}}" title="{{post_title}}">

							{{#isImage}}
							<div class="attachment-preview isattached">
								<div class="thumbnail">
									<div class="centered">
										<img src="{{icon}}" draggable="false">
									</div>
								</div>
								<div class="thumbLoadingAnim"></div>
							</div>
							{{/isImage}}

							{{#.isImage === false}}
							<div class="attachment-preview isattached " style="background-image: url({{icon}});"></div>
							{{/.isImage === false}}

							<span class="attachment-title">{{post_title}}</span>

							<a href="{{zoomSrc.file}}" on-click="zoom" class="img_zoom action" title="[{{ID}}] {{post_title}}" rel="file_gallery_list"><div class="dashicons dashicons-search" title="<?php _e('Zoom', 'file-gallery'); ?>"></div></a>

							<a href="#" on-click="edit:{{this}}" class="img_edit action" title="[{{ID}}] {{post_title}}"><div class="dashicons dashicons-edit" title="<?php _e('Edit', 'file-gallery'); ?>"></div></a>

							<span class="checker_action action" title="Click to select, or click and drag to change position" on-click="select:{{this}}">
								{{#selected}}<div class="dashicons dashicons-yes"></div>{{/selected}}
							</span>

							{{#isImage}}
								<a href="#" on-click="{{isPostThumb ? 'unsetAsThumbnail' : 'setAsThumbnail'}}" class="post_thumb_status action" title="[{{ID}}] {{post_title}}">
									<div class="dashicons dashicons-star-{{isPostThumb ? 'filled' : 'empty'}}" title="{{isPostThumb ? '<?php _e('Unset as featured image', 'file-gallery'); ?>' : '<?php _e('Set as featured image', 'file-gallery'); ?>'}}"></div></a>
							{{/isImage}}

							{{#isPostThumb}}
								<div class="dashicons dashicons-star-filled indicator-overlay"></div>
							{{/isPostThumb}}

							<a href="#" on-click="detachDelete" class="delete_or_detach_link action" title="[{{ID}}] {{post_title}}"><div class="dashicons dashicons-trash" title="<?php _e('Detach / delete', 'file-gallery'); ?>"></div></a>

							<div class="detach_or_delete">
								<a href="#" class="do_single_delete">Delete</a> or
								<a href="#" class="do_single_detach">Detach</a>
							</div>
							<div class="detach_attachment">
								Really detach?
								<a href="#" class="detach">Continue</a> or
								<a href="#" class="detach_cancel">Cancel</a>
							</div>
							<div class="del_attachment">
								Really delete?
								<a href="#" class="delete">Continue</a> or
								<a href="#" class="delete_cancel">Cancel</a>
							</div>
						</li>
					{{/galleryAttachments}}
					</ul>


					<ul class="ui-sortable file_gallery_list {{gallerySelected && galleryAttachments.length ? 'inactive' : ''}}" id="file_gallery_list">
					{{#attachments:i}}
						<li id="file-gallery-item-{{ID}}" class="sortableitem attachment {{itemClasses}} {{selected ? 'selected details' : ''}} {{isPostThumb ? 'post_thumb' : ''}}" title="{{post_title}}">

							{{#isImage}}
							<div class="attachment-preview isattached">
								<div class="thumbnail">
									<div class="centered">
										<img src="{{icon}}" draggable="false">
									</div>
								</div>
								<div class="thumbLoadingAnim"></div>
							</div>
							{{/isImage}}

							{{#.isImage === false}}
							<div class="attachment-preview isattached " style="background-image: url({{icon}});"></div>
							{{/.isImage === false}}

							<span class="attachment-title">{{post_title}}</span>

							<a href="{{zoomSrc.file}}" on-click="zoom" class="img_zoom action" title="[{{ID}}] {{post_title}}" rel="file_gallery_list"><div class="dashicons dashicons-search" title="<?php _e('Zoom', 'file-gallery'); ?>"></div></a>

							<a href="#" on-click="edit" class="img_edit action" title="[{{ID}}] {{post_title}}"><div class="dashicons dashicons-edit" title="<?php _e('Edit', 'file-gallery'); ?>"></div></a>

							<span class="checker_action action" title="Click to select, or click and drag to change position" on-click="select:{{this}}">
								{{#selected}}<div class="dashicons dashicons-yes"></div>{{/selected}}
							</span>

							{{#isImage}}
								<a href="#" on-click="{{isPostThumb ? 'unsetAsThumbnail' : 'setAsThumbnail'}}" class="post_thumb_status action" title="[{{ID}}] {{post_title}}">
									<div class="dashicons dashicons-star-{{isPostThumb ? 'filled' : 'empty'}}" title="{{isPostThumb ? '<?php _e('Unset as featured image', 'file-gallery'); ?>' : '<?php _e('Set as featured image', 'file-gallery'); ?>'}}"></div></a>
							{{/isImage}}

							{{#isPostThumb}}
								<div class="dashicons dashicons-star-filled indicator-overlay"></div>
							{{/isPostThumb}}

							<a href="#" on-click="detachDelete" class="delete_or_detach_link action" title="[{{ID}}] {{post_title}}"><div class="dashicons dashicons-trash" title="<?php _e('Detach / delete', 'file-gallery'); ?>"></div></a>

							<div class="detach_or_delete">
								<a href="#" class="do_single_delete">Delete</a> or
								<a href="#" class="do_single_detach">Detach</a>
							</div>
							<div class="detach_attachment">
								Really detach?
								<a href="#" class="detach">Continue</a> or
								<a href="#" class="detach_cancel">Cancel</a>
							</div>
							<div class="del_attachment">
								Really delete?
								<a href="#" class="delete">Continue</a> or
								<a href="#" class="delete_cancel">Cancel</a>
							</div>
						</li>
					{{/attachments}}
					</ul>
				</div>

				

				<div id="file_gallery_tag_list">
				{{#mediatags}}
					<a href="{{url}}" class="fg_insert_tag" name="{{name}}">{{name}}</a>
				{{/mediatags}}
				</div>

			</div>

		</div>

	</div>



	<div id="file_gallery_delete_dialog" title="<?php _e('Delete attachment dialog', 'file-gallery'); ?>">
		<p><strong><?php _e("Warning: one of the attachments you've chosen to delete has copies.", 'file-gallery'); ?></strong></p>
		<p><?php _e('How do you wish to proceed?', 'file-gallery'); ?></p>
		<p><a href="<?php echo FILE_GALLERY_URL; ?>/help/index.html#deleting_originals" target="_blank"><?php _e('Click here if you have no idea what this dialog means', 'file-gallery'); ?></a> <?php _e('(opens File Gallery help in new browser window)', 'file-gallery'); ?></p>
	</div>

	<div id="file_gallery_copy_all_dialog" title="<?php _e('Copy all attachments from another post', 'file-gallery'); ?>">
		<div id="file_gallery_copy_all_wrap">
			<label for="file_gallery_copy_all_from"><?php _e('Post ID:', 'file-gallery'); ?></label>
			<input type="text" id="file_gallery_copy_all_from" value="" />
		</div>
	</div>



	<div id="file_gallery_single_edit" class="{{singleEditMode === true ? 'open' : 'closed'}}">

	{{#attachmentBeingEdited}}

		<div><a href="#" on-click="cancelEditAttachment"><?php _e('Back'); ?></a></div>

		<div id="file_gallery_attachment_edit_image">
		{{#isImage}}
			<a href="{{zoomSrc.file}}" on-click="zoom:{{this}}" title="" class="attachment_edit_thumb"><img src="{{icon}}" alt="image" /></a>
			<p>
				<a href="#" on-click="regenerate:{{this}}" id="file_gallery_regenerate-{{ID}}" class="file_gallery_regenerate"><?php _e("Regenerate this image's thumbnails", 'file-gallery'); ?></a>
			</p>
		{{/isImage}}

		{{#isImage === false}}
			<img src="{{icon}}" alt="image" />
		{{/isImage === false}}

			<div id="attachment_data">
				<p><strong><?php _e('ID:', 'file-gallery'); ?></strong> <a href="<?php echo admin_url('post.php?post={{ID}}&action=edit&TB_iframe=1'); ?>" class="thickbox" onclick="return false;">{{ID}}</a></p>
				<p><strong><?php _e('Date uploaded:', 'file-gallery'); ?></strong> {{post_date_formatted}}</p>
				<p><strong><?php _e('Uploaded by:', 'file-gallery'); ?></strong> {{post_author_formatted}}</p>

			{{#hasCopies.length}}
				<p class="attachment_info_has_copies">
				<?php _e('IDs of copies of this attachment:', 'file-gallery'); ?>
				<strong>
				{{#hasCopies}}
				<a href="<?php echo admin_url('post.php?post={{this}}&action=edit&TB_iframe=1'); ?>" class="thickbox">{{this}}</a>
				{{/hasCopies}}
				</strong>
				</p>
			{{/hasCopies.length}}

			{{#isCopyOf}}
				<p class="attachment_info_is_a_copy">
					<?php _e('This attachment is a copy of attachment ID', 'file-gallery'); ?>
					<strong><a href="<?php echo admin_url('post.php?post={{isCopyOf}}&action=edit&TB_iframe=1'); ?>" class="thickbox" >{{isCopyOf}}</a></strong>
				</p>
			{{/isCopyOf}}
			</div>
		</div>

		<div id="attachment_data_edit_form">

			{{#isImage}}
			<label for="fgae_image_alt_text"><?php _e('Alternate text for this image', 'file-gallery'); ?>: </label>
			<input type="text" name="post_alt" id="fgae_post_alt_text" value="{{imageAltText}}" class="roundborder" readonly="{{currentUserCanEdit === true ? '' : 'readonly'}}" /><br />
			{{/isImage}}

			<label for="fgae_post_title"><?php _e('Title', 'file-gallery'); ?>: </label>
			<input type="text" name="post_title" id="fgae_post_title" value="{{post_title}}" class="roundborder" readonly="{{currentUserCanEdit === true ? '' : 'readonly'}}" /><br />

			<label for="fgae_post_excerpt"><?php _e('Caption', 'file-gallery'); ?>: </label>
			<textarea name="post_excerpt" id="fgae_post_excerpt" class="roundborder" readonly="{{currentUserCanEdit === true ? '' : 'readonly'}}" value="{{post_excerpt}}"></textarea><br />

			<label for="fgae_post_content"><?php _e('Description', 'file-gallery'); ?>: </label>
			<textarea name="post_content" id="fgae_post_content" rows="4" cols="20" class="roundborder" readonly="{{currentUserCanEdit === true ? '' : 'readonly'}}" value="{{post_content}}"></textarea><br />

			<label for="fgae_tax_input"><?php _e('Media tags (separate each tag with a comma)', 'file-gallery'); ?>: </label>
			<input type="text" name="tax_input" id="fgae_tax_input" value="{{mediaTags}}" class="roundborder" readonly="{{currentUserCanEdit === true ? '' : 'readonly'}}" /><br />

			<label for="fgae_menu_order"><?php _e('Menu order', 'file-gallery'); ?>: </label>
			<input type="text" name="menu_order" id="fgae_menu_order" value="{{menu_order}}" class="roundborder" readonly="{{currentUserCanEdit === true ? '' : 'readonly'}}" /><br />

			<label for="fgae_attachment_uri"><?php _e('Attachment file URL:', 'file-gallery'); ?></label>
			<input type="text" name="attachment_uri" id="fgae_attachment_uri" readonly="readonly" value="{{baseUrl + file}}" class="roundborder" />

			{{{customFieldsTable}}}

			<button on-click="saveAttachment" id="file_gallery_edit_attachment_save" class="button-primary"><?php _e('save and return', 'file-gallery'); ?></button>
			<button on-click="cancelEditAttachment" id="file_gallery_edit_attachment_cancel" class="button-secondary"><?php _e('cancel and return', 'file-gallery'); ?></button>
		</div>
	{{/attachmentBeingEdited}}
	</div>

	<div id="file_gallery_zoomed_image" class="{{zoomed ? '' : 'hidden'}}">
	{{#zoomed}}
		<img src="{{zoomed.zoomSrc.file}}" alt="{{zoomed.imageAltText}}" style="margin-left: -{{zoomed.zoomSrc.width / 2}}px; margin-top: -{{zoomed.zoomSrc.height / 2}}px;" />

		{{#isImage === false}}
		<div class="wp_attachment_holder hidden">
			<audio class="wp-audio-shortcode" preload="none" style="width: 100%; visibility: hidden;" controls="controls"><source type="audio/mpeg" src="http://wpcl.localhost/wp-content/uploads/originaldixielandjazzbandwithalbernard-stlouisblues.mp3?_=1" /></audio>
		</div>
		{{/isImage === false}}

		<div id="file_gallery_zoomed_image_description">
			<div id="file_gallery_zoomed_image_description_inner">
				<h2>{{zoomed.post_title}}</h2>
				<div class="file_gallery_zoomed_image_post_content">{{zoomed.post_content}}</div>
			</div>
		</div>

		<a class="media-modal-close" on-click="zoomClose" href="#" title="<?php _e('Close'); ?>"><span class="media-modal-icon"></span></a>

		<span id="file_gallery_zoomed_image_prev" on-click="zoomPrev" class="{{zoomed.previous ? '' : 'hidden'}} dashicons dashicons-arrow-left-alt2"></span>
		<span id="file_gallery_zoomed_image_next" on-click="zoomNext" class="{{zoomed.next ? '' : 'hidden'}} button-large dashicons dashicons-arrow-right-alt2"></span>
		<span id="file_gallery_zoomed_image_edit" class="button button-primary button-large" on-click="edit:{{zoomed}}"><?php _e('Edit'); ?></span>
		{{/zoomed}}
	</div>

</script>

<noscript>
	<div class="error" style="margin: 0;">
		<p><?php _e('File Gallery requires Javascript to function. Please enable it in your browser.', 'file-gallery'); ?></p>
	</div>
</noscript>
