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
				toolbar = this.selectionStatusToolbar( view );

			// console.log(this, this.options, this.state(), this.toolbar, this.state().get('toolbar'), toolbar, controller.state().get('filterable'));

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
					
					jQuery.post
					(
						wp.media.model.settings.ajaxurl,
						{
							action : "file_gallery_copy_attachments_to_post",
							post_id : wp.media.model.settings.post.id,
							ids : _.pluck(selection._byId, "id").join(","),
							_ajax_nonce : file_gallery_attach_nonce
						},
						function(response)
						{
							var data = response.split("#"),
								attached_ids = data[0];
								response = data[1];

							console.log(response);

							state.reset();
						},
						"html"
					);
				}
			});
		}
	});

	jQuery(document).on("click", ".insert-media, .media-menu-item", function(e)
	{
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
});