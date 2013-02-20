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
			var topWin = window.dialogArguments || opener || parent || top,
				file_gallery_upload_error = false,
				inside_tinymce = false;

			if( "undefined" === typeof(topWin.file_gallery) && "undefined" !== typeof(topWin.top.file_gallery) )
			{
				topWin.file_gallery = topWin.top.file_gallery;
				inside_tinymce = true;
			}

			jQuery(document).ready(function()
			{
				if( ! uploader.features.dragdrop )
				{
					topWin.file_gallery.uploader_dragdrop = false;
					return;
				}
				
				jQuery("#file_gallery_continue").live("click", function(e)
				{
					e.preventDefault();
					topWin.file_gallery.init( "UploadComplete" );
					
					if( inside_tinymce )
						topWin.file_gallery.tinymce_remove_upload_iframe();

					return false;
				});
				
				uploader.bind("FilesAdded", function(up, files)
				{
					jQuery(".drag-drop").slideUp(300);
					jQuery("#media-upload").addClass("started");
				});
				
				uploader.bind("FileUploaded", function(up, file, response)
				{
					if( -1 < response.response.search(/error-div/))
					{
						jQuery(".media-item .error-div").parent().addClass("error");
						file_gallery_upload_error = true;
					}
				});
				
				uploader.bind("UploadComplete", function(up, files)
				{
					if( "undefined" === typeof(topWin) )
						var topWin = window.dialogArguments || opener || parent || top;
					
					if( "undefined" === typeof(topWin.file_gallery) && "undefined" !== typeof(topWin.top.file_gallery) )
					{
						topWin.file_gallery = topWin.top.file_gallery;
						inside_tinymce = true;
					}
					
					if( false === file_gallery_upload_error )
					{
						topWin.file_gallery.init( "UploadComplete" );

						if( inside_tinymce )
							topWin.file_gallery.tinymce_remove_upload_iframe();
					}
					else
					{
						jQuery("#media-items").after('<a href="#" id="file_gallery_continue"><?php _e('Continue', 'file-gallery'); ?></a>')
					}

					topWin.file_gallery.upload_inside = false;
					file_gallery_upload_error = false;
				});
				
				uploader.bind("Error", function(up, err)
				{
					file_gallery_upload_error = true;
					topWin.file_gallery.upload_handle_error(err, up);
				});
			});
		</script>
		<?php
	}
}
add_action( 'post-upload-ui', 'file_gallery_post_upload_ui' );

