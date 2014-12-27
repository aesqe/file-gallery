<script type="text/html" id="tmpl-attachment-filegallery">

<!-- FILE GALLERY ADDITION -->
	<div class="{{ <?php echo $post->ID; ?> == data.uploadedTo? 'isattached' : '' }} attachment-preview js--select-attachment type-{{ data.type }} subtype-{{ data.subtype }} {{ data.orientation }}">
<!-- /FILE GALLERY ADDITION -->

		<div class="thumbnail">
			<# if ( data.uploading ) { #>
				<div class="media-progress-bar"><div style="width: {{ data.percent }}%"></div></div>
			<# } else if ( 'image' === data.type && data.sizes ) { #>
				<div class="centered">
					<img src="{{ data.size.url }}" draggable="false" alt="" />
				</div>
			<# } else { #>
				<div class="centered">
					<# if ( data.image && data.image.src && data.image.src !== data.icon ) { #>
						<img src="{{ data.image.src }}" class="thumbnail" draggable="false" />
					<# } else { #>
						<img src="{{ data.icon }}" class="icon" draggable="false" />
					<# } #>
				</div>
				<div class="filename">
					<div>{{ data.filename }}</div>
				</div>
			<# } #>
		</div>
		<# if ( data.buttons.close ) { #>
			<a class="close media-modal-icon" href="#" title="<?php esc_attr_e('Remove'); ?>"></a>
		<# } #>

		<!-- FILE GALLERY ADDITION -->
		<# if ( data.buttons.attach ) { #>
			<a class="attach id_{{ data.id }}" title="<?php _e('This file is attached to current post', 'file-gallery'); ?>"><div class="media-modal-icon"></div></a>
		<# } #>
		<!-- /FILE GALLERY ADDITION -->
	</div>
	<# if ( data.buttons.check ) { #>
		<a class="check" href="#" title="<?php esc_attr_e('Deselect'); ?>" tabindex="-1"><div class="media-modal-icon"></div></a>
	<# } #>
	<#
	var maybeReadOnly = data.can.save || data.allowLocalEdits ? '' : 'readonly';
	if ( data.describe ) {
		if ( 'image' === data.type ) { #>
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
		<# }
	} #>
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
		<div class="thumbnail thumbnail-{{ data.type }}">
			<# if ( data.uploading ) { #>
				<div class="media-progress-bar"><div></div></div>
			<# } else if ( 'image' === data.type && data.sizes ) { #>
				<img src="{{ data.size.url }}" draggable="false" />
			<# } else { #>
				<img src="{{ data.icon }}" class="icon" draggable="false" />
			<# } #>
		</div>
		<div class="details">
			<div class="filename">{{ data.filename }}</div>
			<div class="uploaded">{{ data.dateFormatted }}</div>

			<div class="file-size">{{ data.filesizeHumanReadable }}</div>
			<# if ( 'image' === data.type && ! data.uploading ) { #>
				<# if ( data.width && data.height ) { #>
					<div class="dimensions">{{ data.width }} &times; {{ data.height }}</div>
				<# } #>

				<# if ( data.can.save && data.sizes ) { #>
					<a class="edit-attachment" href="{{ data.editLink }}&amp;image-editor" target="_blank"><?php _e( 'Edit Image' ); ?></a>
					<a class="refresh-attachment" href="#"><?php _e( 'Refresh' ); ?></a>
				<# } #>
			<# } #>

			<# if ( data.fileLength ) { #>
				<div class="file-length"><?php _e( 'Length:' ); ?> {{ data.fileLength }}</div>
			<# } #>

			<# if ( ! data.uploading && data.can.remove ) { #>
				<?php if ( MEDIA_TRASH ): ?>
				<# if ( 'trash' === data.status ) { #>
					<a class="untrash-attachment" href="#"><?php _e( 'Untrash' ); ?></a>
				<# } else { #>
					<a class="trash-attachment" href="#"><?php _e( 'Trash' ); ?></a>
				<# } #>
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

	<label class="setting" data-setting="url">
		<span class="name"><?php _e('URL'); ?></span>
		<input type="text" value="{{ data.url }}" readonly />
	</label>
	<# var maybeReadOnly = data.can.save || data.allowLocalEdits ? '' : 'readonly'; #>
	<label class="setting" data-setting="title">
		<span class="name"><?php _e('Title'); ?></span>
		<input type="text" value="{{ data.title }}" {{ maybeReadOnly }} />
	</label>
	<# if ( 'audio' === data.type ) { #>
	<?php foreach ( array(
		'artist' => __( 'Artist' ),
		'album' => __( 'Album' ),
	) as $key => $label ) : ?>
	<label class="setting" data-setting="<?php echo esc_attr( $key ) ?>">
		<span class="name"><?php echo $label ?></span>
		<input type="text" value="{{ data.<?php echo $key ?> || data.meta.<?php echo $key ?> || '' }}" />
	</label>
	<?php endforeach; ?>
	<# } #>
	<label class="setting" data-setting="caption">
		<span class="name"><?php _e('Caption'); ?></span>
		<textarea {{ maybeReadOnly }}>{{ data.caption }}</textarea>
	</label>
	<# if ( 'image' === data.type ) { #>
		<label class="setting" data-setting="alt">
			<span class="name"><?php _e('Alt Text'); ?></span>
			<input type="text" value="{{ data.alt }}" {{ maybeReadOnly }} />
		</label>
	<# } #>
	<label class="setting" data-setting="description">
		<span class="name"><?php _e('Description'); ?></span>
		<textarea {{ maybeReadOnly }}>{{ data.description }}</textarea>
	</label>

</script>



<script type="text/html" id="tmpl-attachment-details-two-column-filegallery">

	<div class="attachment-media-view {{ data.orientation }}">
		<div class="thumbnail thumbnail-{{ data.type }}">
			<# if ( data.uploading ) { #>
				<div class="media-progress-bar"><div></div></div>
			<# } else if ( 'image' === data.type && data.sizes && data.sizes.large ) { #>
				<img class="details-image" src="{{ data.sizes.large.url }}" draggable="false" />
			<# } else if ( 'image' === data.type && data.sizes && data.sizes.full ) { #>
				<img class="details-image" src="{{ data.sizes.full.url }}" draggable="false" />
			<# } else if ( -1 === jQuery.inArray( data.type, [ 'audio', 'video' ] ) ) { #>
				<img class="details-image" src="{{ data.icon }}" class="icon" draggable="false" />
			<# } #>

			<# if ( 'audio' === data.type ) { #>
			<div class="wp-media-wrapper">
				<audio style="visibility: hidden" controls class="wp-audio-shortcode" width="100%" preload="none">
					<source type="{{ data.mime }}" src="{{ data.url }}"/>
				</audio>
			</div>
			<# } else if ( 'video' === data.type ) {
				var w_rule = h_rule = '';
				if ( data.width ) {
					w_rule = 'width: ' + data.width + 'px;';
				} else if ( wp.media.view.settings.contentWidth ) {
					w_rule = 'width: ' + wp.media.view.settings.contentWidth + 'px;';
				}
				if ( data.height ) {
					h_rule = 'height: ' + data.height + 'px;';
				}
			#>
			<div style="{{ w_rule }}{{ h_rule }}" class="wp-media-wrapper wp-video">
				<video controls="controls" class="wp-video-shortcode" preload="metadata"
					<# if ( data.width ) { #>width="{{ data.width }}"<# } #>
					<# if ( data.height ) { #>height="{{ data.height }}"<# } #>
					<# if ( data.image && data.image.src !== data.icon ) { #>poster="{{ data.image.src }}"<# } #>>
					<source type="{{ data.mime }}" src="{{ data.url }}"/>
				</video>
			</div>
			<# } #>

			<div class="attachment-actions">
				<# if ( 'image' === data.type && ! data.uploading && data.sizes ) { #>
					<a class="button edit-attachment" href="#"><?php _e( 'Edit Image' ); ?></a>
				<# } #>
			</div>
		</div>
	</div>
	<div class="attachment-info">
		<span class="settings-save-status">
			<span class="spinner"></span>
			<span class="saved"><?php esc_html_e('Saved.'); ?></span>
		</span>
		<div class="details">
			<div class="filename"><strong><?php _e( 'File name:' ); ?></strong> {{ data.filename }}</div>
			<div class="filename"><strong><?php _e( 'File type:' ); ?></strong> {{ data.mime }}</div>
			<div class="uploaded"><strong><?php _e( 'Uploaded on:' ); ?></strong> {{ data.dateFormatted }}</div>

			<div class="file-size"><strong><?php _e( 'File size:' ); ?></strong> {{ data.filesizeHumanReadable }}</div>
			<# if ( 'image' === data.type && ! data.uploading ) { #>
				<# if ( data.width && data.height ) { #>
					<div class="dimensions"><strong><?php _e( 'Dimensions:' ); ?></strong> {{ data.width }} &times; {{ data.height }}</div>
				<# } #>
			<# } #>

			<# if ( data.fileLength ) { #>
				<div class="file-length"><strong><?php _e( 'Length:' ); ?></strong> {{ data.fileLength }}</div>
			<# } #>

			<# if ( 'audio' === data.type && data.meta.bitrate ) { #>
				<div class="bitrate">
					<strong><?php _e( 'Bitrate:' ); ?></strong> {{ Math.round( data.meta.bitrate / 1000 ) }}kb/s
					<# if ( data.meta.bitrate_mode ) { #>
					{{ ' ' + data.meta.bitrate_mode.toUpperCase() }}
					<# } #>
				</div>
			<# } #>

			<div class="compat-meta">
				<# if ( data.compat && data.compat.meta ) { #>
					{{{ data.compat.meta }}}
				<# } #>
			</div>
		</div>

		<div class="settings">
			<label class="setting" data-setting="url">
				<span class="name"><?php _e('URL'); ?></span>
				<input type="text" value="{{ data.url }}" readonly />
			</label>
			<# var maybeReadOnly = data.can.save || data.allowLocalEdits ? '' : 'readonly'; #>
			<label class="setting" data-setting="title">
				<span class="name"><?php _e('Title'); ?></span>
				<input type="text" value="{{ data.title }}" {{ maybeReadOnly }} />
			</label>
			<# if ( 'audio' === data.type ) { #>
			<?php foreach ( array(
				'artist' => __( 'Artist' ),
				'album' => __( 'Album' ),
			) as $key => $label ) : ?>
			<label class="setting" data-setting="<?php echo esc_attr( $key ) ?>">
				<span class="name"><?php echo $label ?></span>
				<input type="text" value="{{ data.<?php echo $key ?> || data.meta.<?php echo $key ?> || '' }}" />
			</label>
			<?php endforeach; ?>
			<# } #>
			<label class="setting" data-setting="caption">
				<span class="name"><?php _e( 'Caption' ); ?></span>
				<textarea {{ maybeReadOnly }}>{{ data.caption }}</textarea>
			</label>
			<# if ( 'image' === data.type ) { #>
				<label class="setting" data-setting="alt">
					<span class="name"><?php _e( 'Alt Text' ); ?></span>
					<input type="text" value="{{ data.alt }}" {{ maybeReadOnly }} />
				</label>
			<# } #>
			<label class="setting" data-setting="description">
				<span class="name"><?php _e('Description'); ?></span>
				<textarea {{ maybeReadOnly }}>{{ data.description }}</textarea>
			</label>
			<label class="setting">
				<span class="name"><?php _e( 'Uploaded By' ); ?></span>
				<span class="value">{{ data.authorName }}</span>
			</label>
			<# if ( data.uploadedTo ) { #>
				<label class="setting">
					<span class="name"><?php _e( 'Uploaded To' ); ?></span>
					<# if ( data.uploadedToLink ) { #>
						<span class="value"><a href="{{ data.uploadedToLink }}">{{ data.uploadedToTitle }}</a></span>
					<# } else { #>
						<span class="value">{{ data.uploadedToTitle }}</span>
					<# } #>
				</label>
			<# } #>
			<div class="attachment-compat"></div>
		</div>

		<div class="actions">
			<a class="view-attachment" href="{{ data.link }}"><?php _e( 'View attachment page' ); ?></a> |
			<a href="post.php?post={{ data.id }}&action=edit"><?php _e( 'Edit more details' ); ?></a>
			<# if ( ! data.uploading && data.can.remove ) { #> |
					<?php if ( MEDIA_TRASH ): ?>
					<# if ( 'trash' === data.status ) { #>
						<a class="untrash-attachment" href="#"><?php _e( 'Untrash' ); ?></a>
					<# } else { #>
						<a class="trash-attachment" href="#"><?php _e( 'Trash' ); ?></a>
					<# } #>
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
		</div>

	</div>

</script>
