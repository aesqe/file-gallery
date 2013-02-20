/*global console _ wp jQuery file_gallery_attach_nonce file_gallery*/
/**/
var wpMediaAttachmentFilters = wp.media.view.AttachmentFilters;
wpMediaAttachmentFilters = wp.media.view.AttachmentFilters.extend(
{
	change: function( event )
	{
		"use strict";

		console.log(event);

		//wpMediaAttachmentFilters.prototype.change.call(this, event);
	},

	select: function( event )
	{
		"use strict";

		console.log("ya");

		//wpMediaAttachmentFilters.prototype.select.call(this, event);
	}
});/**/


var wpMediaFramePost = wp.media.view.MediaFrame.Post;
wp.media.view.MediaFrame.Post = wpMediaFramePost.extend(
{
	mainInsertToolbar: function( view )
	{
		"use strict";

		wpMediaFramePost.prototype.mainInsertToolbar.call(this, view);

		var controller = this;

		this.selectionStatusToolbar( view );

		view.set( "attach", {
			style: "primary",
			priority: 80,
			text: file_gallery.L10n.attach_all_checked_copy,
			requires: { selection: true },

			click: function() {
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