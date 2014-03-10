/*global console _ wp jQuery file_gallery_attach_nonce console file_gallery*/

jQuery(document).ready(function()
{
	"use strict";

	var wpMediaFramePost = wp.media.view.MediaFrame.Post;

	wp.media.view.MediaFrame.Post = wpMediaFramePost.extend(
	{
		mainInsertToolbar: function( view )
		{
			wpMediaFramePost.prototype.mainInsertToolbar.call(this, view);

			var controller = this,
				responseContainerAdded = false,
				responseContainer = jQuery('<div class="file-gallery-response" style="display: none;"></div>');

			view.set( "attach", {
				style: "primary",
				priority: 80,
				text: file_gallery.L10n.attach_all_checked_copy,
				requires: {
					selection: true
				},

				click: function()
				{
					var state = controller.state(),
						selection = state.get("selection");

					if( responseContainerAdded === false ) {
						controller.content.get().sidebar.$el.append(responseContainer);
						responseContainerAdded = true;
					}

					responseContainer.stop().fadeOut(75, function() {
						responseContainer.html("");
					});

					jQuery.post
					(
						wp.media.model.settings.ajaxurl,
						{
							action : "file_gallery_copy_attachments_to_post",
							post_id : wp.media.model.settings.post.id,
							ids : _.uniq( _.pluck(selection._byId, "id") ).join(","),
							_ajax_nonce : file_gallery_attach_nonce
						},
						function(response)
						{
							state.reset();
							wp.media.editor.get(wpActiveEditor).views._views[".media-frame-content"][0].views._views[""][1].collection.props.set({nocache:(+(new Date()))});
							responseContainer.html( response.split("#").pop() ).fadeIn(500, function() {
								responseContainer.fadeOut(15000);
							});
						},
						"html"
					);
				}
			});
		}
	});

	jQuery(document).on("click", ".insert-media, .media-menu-item", function(e)
	{
		var editor = jQuery(this).data("editor"),
			toolbar = wp.media.editor.get(editor).views._views[".media-frame-toolbar"][0];

		toolbar.selection.reset();

		if( toolbar.views._views[""][1].collection !== void 0 ) {
			toolbar.views._views[""][1].collection.props.set({nocache:(+(new Date()))});
		}
		
		var attachButton = jQuery(".media-frame-toolbar .media-button-attach"),
			filters = jQuery("select.attachment-filters");

		if( filters.val() === "uploaded" ) {
			attachButton.hide();
		}

		filters.on("change", function()
		{
			if( this.value === "uploaded" ) {
				attachButton.hide();
			}
			else {
				attachButton.show();
			}
		});
	});
	
	wp.media.view.Attachment.Library = wp.media.view.Attachment.extend({
		buttons: {
			check: true,
			attach: true
		}
	});
	
	jQuery('#tmpl-attachment').remove();
	jQuery('#tmpl-attachment-new').attr('id','tmpl-attachment');
});