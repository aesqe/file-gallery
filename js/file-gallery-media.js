/*global console _ wp jQuery file_gallery_attach_nonce console file_gallery*/

jQuery(document).ready(function()
{
	"use strict";

	var responseContainerAdded = false;
	var responseContainer = jQuery('<div class="file-gallery-response" style="display: none;"></div>');

	var wpMediaFramePost = wp.media.view.MediaFrame.Post;

	wp.media.view.MediaFrame.Post = wpMediaFramePost.extend(
	{
		mainInsertToolbar: function( view )
		{
			wpMediaFramePost.prototype.mainInsertToolbar.call(this, view);

			var controller = this;

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

					if( responseContainerAdded === false )
					{
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
							action: "file_gallery_copy_attachments_to_post",
							post_id: wp.media.model.settings.post.id,
							ids: _.uniq( _.pluck(selection._byId, "id") ).join(","),
							_ajax_nonce: file_gallery_attach_nonce
						},
						function(response)
						{
							responseContainer.html( response.split("#").pop() )
								.fadeIn(500, function() {
									responseContainer.fadeOut(15000);
								});

							state.reset();

							wp.media.editor.get(wpActiveEditor)
								.views._views[".media-frame-content"][0]
								.collection.props.set({ nocache: (new Date()).getTime() });
						},
						"html"
					);
				}
			});
		}
	});

	jQuery(document).on("click", ".insert-media, .media-menu-item", function(e)
	{
		var editor = jQuery(this).data("editor");

		if( editor )
		{
			var toolbar = wp.media.editor.get(editor).views._views[".media-frame-toolbar"][0];

			if( toolbar.selection ) {
				toolbar.selection.reset();
			}

			if( toolbar.views._views[""][1].collection !== void 0 ) {
				toolbar.views._views[""][1].collection.props.set({ nocache: (new Date()).getTime() });
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
				} else {
					attachButton.show();
				}
			});
		}
	});
	
	wp.media.view.Attachment.Library = wp.media.view.Attachment.extend({
		buttons: {
			check: true,
			attach: true
		}
	});

	wp.media.view.Attachment.Details.prototype.events['click .detach-attachment'] = 'detachAttachment';

	wp.media.view.Attachment.Details = wp.media.view.Attachment.Details.extend(
	{
		detachAttachment: function ( event )
		{
			event.preventDefault();

			var controller = this.controller;
			var wp_version = parseFloat(file_gallery.options.wp_version.substring(0,3)) * 10;

			if( responseContainerAdded === false )
			{
				controller.content.get().sidebar.$el.append(responseContainer);
				responseContainerAdded = true;
			}

			if ( confirm( "Really detach attachment from this post?" ) )
			{
				var id = this.model.get("id");
				var attachment = file_gallery.getAttachmentById( id );

				this.model.set("uploadedTo", 0);

				file_gallery.detachAttachments([attachment], function (response)
				{
					if( wp_version < 40 )
					{
						controller.state().reset();

						wp.media.editor.get(wpActiveEditor)
							.views._views[".media-frame-content"][0]
							.collection.props.set({ nocache: (new Date()).getTime() });
					}
					
					responseContainer.html( response )
						.fadeIn(500, function() {
							responseContainer.fadeOut(15000);
						});
				});

				
			}
		}
	});

	jQuery("#tmpl-attachment").remove();
	jQuery("#tmpl-attachment-details").remove();
	jQuery("#tmpl-attachment-details-two-column").remove();

	jQuery("#tmpl-attachment-filegallery").attr("id", "tmpl-attachment");
	jQuery("#tmpl-attachment-details-filegallery").attr("id", "tmpl-attachment-details");
	jQuery("#tmpl-attachment-details-two-column-filegallery").attr("id", "tmpl-attachment-details-two-column");
});