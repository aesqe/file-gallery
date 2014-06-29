<?php

function file_gallery_admin_head( $class )
{
	if( isset($_GET['file_gallery']) || ( isset($_SERVER['HTTP_REFERER']) && false !== strpos($_SERVER['HTTP_REFERER'], 'file_gallery=true')) )
	{
		?>
		<style type="text/css">
			html,
			#media-upload
			{
				background: #FFF !important;
			}
			
			#media-upload
			{
				min-width: 0;
				height: auto;
			}

			#media-items
			{
				margin-top: 1px;
				width: 99%;
			}
			
			.media-item img
			{
				display: inline-block !important;
			}
			
			.media-item.error
			{
				background: #F4E4E0;
			}
			
			.drag-drop #drag-drop-area
			{
				background: #FFF;
				text-align: center;
			}
			
			.media-upload-form
			{
				margin: 0;
			}
			
			#file_gallery_continue
			{
				float: left;
				font-weight: bold;
				margin-top: 20px;
				color: #D54E21;
			}
			
			.toggle,
			.savebutton,
			h3.media-title,
			#media-upload-header,
			.media-item.error a.dismiss,
			.media-item.error .progress,
			#media-upload.started .max-upload-size,
			#media-upload.started .after-file-upload
			{
				display: none !important;
			}
		</style>
		<?php
	}
}
add_action( 'admin_head', 'file_gallery_admin_head' );

function file_gallery_post_upload_ui()
{
	if( isset($_GET['file_gallery']) )
	{
		?>
		<script type="text/javascript">
			window.topWin = window.dialogArguments || opener || parent || top;
			var file_gallery_upload_error = false;
			var inside_tinymce = false;

			if( ! topWin.file_gallery && topWin.top.file_gallery )
			{
				topWin.file_gallery = topWin.top.file_gallery;
				inside_tinymce = true;
			}

			jQuery(document).ready(function()
			{
				jQuery("#media-items").after('<a href="#" id="file_gallery_uploader_cancel"><?php _e('Cancel', 'file-gallery'); ?></a>');

				topWin.file_gallery.uploader = uploader;

				if( uploader.features.dragdrop === false )
				{
					topWin.file_gallery.uploader_dragdrop = false;
					return;
				}
				
				uploader.bind("FilesAdded", function(up, files)
				{
					jQuery(".drag-drop").slideUp(300);
					jQuery("#media-upload").addClass("started");
				});
				
				uploader.bind("FileUploaded", function(up, file, data)
				{
					if( data.response.search(/error-div/) > -1 )
					{
						jQuery(".media-item .error-div").parent().addClass("error");
						file_gallery_upload_error = true;
					}
				});
				
				uploader.bind("UploadComplete", function(up, files)
				{
					if( ! window.topWin ) {
						window.topWin = window.dialogArguments || opener || parent || top;
					}
					
					if( ! topWin.file_gallery && topWin.top.file_gallery )
					{
						topWin.file_gallery = topWin.top.file_gallery;
						inside_tinymce = true;
					}
					
					if( ! file_gallery_upload_error )
					{
						resetUploader();

						if( inside_tinymce ) {
							// topWin.file_gallery.tinymce_remove_upload_iframe();
						}
					}
					else
					{
						jQuery("#media-items").after('<a href="#" id="file_gallery_uploader_continue"><?php _e('Continue', 'file-gallery'); ?></a>');
					}

					topWin.file_gallery.upload_inside = false;
					file_gallery_upload_error = false;
				});
				
				uploader.bind("Error", function(up, err)
				{
					file_gallery_upload_error = true;
					topWin.file_gallery.upload_handle_error(err, up);

					resetUploader();
				});

				jQuery("body").on("click", "#file_gallery_uploader_continue, #file_gallery_uploader_cancel", function(event)
				{
					resetUploader();

					event.preventDefault();
					return false;
				});
			});

			var resetUploader = function()
			{
				jQuery(".drag-drop").slideDown(300);
				jQuery("#media-upload").removeClass("started");

				uploader.splice();
				uploader.refresh();

				jQuery("#media-items").slideDown(300, function ()
				{
					jQuery("#media-items").empty();
					topWin.file_gallery.load();
				});
			}
		</script>
		<?php
	}
}
add_action( 'post-upload-ui', 'file_gallery_post_upload_ui' );

