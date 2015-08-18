<!-- copied and modified from wp-includes/media-template.php -->
<script type="text/html" id="tmpl-attachment-filegallery">
	<!-- FILE GALLERY ADDITION -->
	<div class="{{ <?php echo $post->ID; ?> == data.uploadedTo ? 'isattached' : '' }} parent-{{data.uploadedTo}} attachment-preview js--select-attachment type-{{ data.type }} subtype-{{ data.subtype }} {{ data.orientation }}">
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
			<button type="button" class="button-link attachment-close media-modal-icon"><span class="screen-reader-text"><?php _e( 'Remove' ); ?></span></button>
		<# } #>
		<!-- FILE GALLERY ADDITION -->
		<# if ( data.buttons.attach ) { #>
			<a class="attach id_{{ data.id }}" title="<?php _e('This file is attached to current post', 'file-gallery'); ?>"><div class="media-modal-icon"></div></a>
		<# } #>
		<!-- /FILE GALLERY ADDITION -->
	</div>
	<# if ( data.buttons.check ) { #>
		<button type="button" class="button-link check" tabindex="-1"><span class="media-modal-icon"></span><span class="screen-reader-text"><?php _e( 'Deselect' ); ?></span></button>
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