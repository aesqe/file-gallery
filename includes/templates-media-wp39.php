<script type="text/html" id="tmpl-attachment-filegallery">
	<!-- FILE GALLERY ADDITION -->
	<div class="{{ <?php echo $post->ID; ?> == data.uploadedTo? 'isattached' : '' }} attachment-preview type-{{ data.type }} subtype-{{ data.subtype }} {{ data.orientation }}">
	<!-- /FILE GALLERY ADDITION -->
	
		<# if ( data.uploading ) { #>
			<div class="media-progress-bar"><div></div></div>
		<# } else if ( 'image' === data.type ) { #>
			<div class="thumbnail">
				<div class="centered">
					<img src="{{ data.size.url }}" draggable="false" />
				</div>
			</div>
		<# } else { #>
			<img src="{{ data.icon }}" class="icon" draggable="false" />
			<div class="filename">
				<div>{{ data.filename }}</div>
			</div>
		<# } #>

		<# if ( data.buttons.close ) { #>
			<a class="close media-modal-icon" href="#" title="<?php esc_attr_e('Remove'); ?>"></a>
		<# } #>

		<# if ( data.buttons.check ) { #>
			<a class="check" href="#" title="<?php esc_attr_e('Deselect'); ?>"><div class="media-modal-icon"></div></a>
		<# } #>

		<!-- FILE GALLERY ADDITION -->
		<# if ( data.buttons.attach ) { #>
			<a class="attach id_{{ data.id }}" title="<?php _e('This file is attached to current post', 'file-gallery'); ?>"><div class="media-modal-icon"></div></a>
		<# } #>
		<!-- /FILE GALLERY ADDITION -->
	</div>
	<#
	var maybeReadOnly = data.can.save || data.allowLocalEdits ? '' : 'readonly';
	if ( data.describe ) { #>
		<# if ( 'image' === data.type ) { #>
			<input type="text" value="{{ data.caption }}" class="describe" data-setting="caption"
				placeholder="<?php esc_attr_e('Caption this image&hellip;'); ?>" {{ maybeReadOnly }} />
		<# } else { #>
			<input type="text" value="{{ data.title }}" class="describe" data-setting="title"
				<# if ( 'video' === data.type ) { #>
					placeholder="<?php esc_attr_e('Describe this video&hellip;'); ?>"
				<# } else if ( 'audio' === data.type ) { #>
					placeholder="<?php esc_attr_e('Describe this audio file&hellip;'); ?>"
				<# } else { #>
					placeholder="<?php esc_attr_e('Describe this media file&hellip;'); ?>"
				<# } #> {{ maybeReadOnly }} />
		<# } #>
	<# } #>
</script>



<script type="text/html" id="tmpl-attachment-details-filegallery">
	<h3>
		<?php _e('Attachment Details'); ?>

		<span class="settings-save-status">
			<span class="spinner"></span>
			<span class="saved"><?php esc_html_e('Saved.'); ?></span>
		</span>
	</h3>
	<div class="attachment-info">
		<div class="thumbnail">
			<# if ( data.uploading ) { #>
				<div class="media-progress-bar"><div></div></div>
			<# } else if ( 'image' === data.type ) { #>
				<img src="{{ data.size.url }}" draggable="false" />
			<# } else { #>
				<img src="{{ data.icon }}" class="icon" draggable="false" />
			<# } #>
		</div>
		<div class="details">
			<div class="filename">{{ data.filename }}</div>
			<div class="uploaded">{{ data.dateFormatted }}</div>

			<# if ( 'image' === data.type && ! data.uploading ) { #>
				<# if ( data.width && data.height ) { #>
					<div class="dimensions">{{ data.width }} &times; {{ data.height }}</div>
				<# } #>

				<# if ( data.can.save ) { #>
					<a class="edit-attachment" href="{{ data.editLink }}&amp;image-editor" target="_blank"><?php _e( 'Edit Image' ); ?></a>
					<a class="refresh-attachment" href="#"><?php _e( 'Refresh' ); ?></a>
				<# } #>
			<# } #>

			<# if ( data.fileLength ) { #>
				<div class="file-length"><?php _e( 'Length:' ); ?> {{ data.fileLength }}</div>
			<# } #>

			<# if ( ! data.uploading && data.can.remove ) { #>
				<?php if ( MEDIA_TRASH ): ?>
					<a class="trash-attachment" href="#"><?php _e( 'Trash' ); ?></a>
				<?php else: ?>
					<a class="delete-attachment" href="#"><?php _e( 'Delete Permanently' ); ?></a>
				<?php endif; ?>

				<!-- FILE GALLERY ADDITION -->
				<# 	if ( <?php echo $post->ID; ?> == data.uploadedTo ) { #>
					<a class="detach-attachment" href="#"><?php _e( 'Detach', 'file-gallery' ); ?></a>
				<# } else { #>
					<a class="attach-attachment" href="#"><?php _e( 'Attach', 'file-gallery' ); ?></a>
				<# } #>
				<!-- / FILE GALLERY ADDITION -->
			<# } #>

			<div class="compat-meta">
				<# if ( data.compat && data.compat.meta ) { #>
					{{{ data.compat.meta }}}
				<# } #>
			</div>
		</div>
	</div>

	<# var maybeReadOnly = data.can.save || data.allowLocalEdits ? '' : 'readonly'; #>
		<label class="setting" data-setting="title">
			<span><?php _e('Title'); ?></span>
			<input type="text" value="{{ data.title }}" {{ maybeReadOnly }} />
		</label>
		<label class="setting" data-setting="caption">
			<span><?php _e('Caption'); ?></span>
			<textarea {{ maybeReadOnly }}>{{ data.caption }}</textarea>
		</label>
	<# if ( 'image' === data.type ) { #>
		<label class="setting" data-setting="alt">
			<span><?php _e('Alt Text'); ?></span>
			<input type="text" value="{{ data.alt }}" {{ maybeReadOnly }} />
		</label>
	<# } #>
		<label class="setting" data-setting="description">
			<span><?php _e('Description'); ?></span>
			<textarea {{ maybeReadOnly }}>{{ data.description }}</textarea>
		</label>
</script>
