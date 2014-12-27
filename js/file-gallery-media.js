/*global console _ wp jQuery file_gallery_attach_nonce console file_gallery*/

jQuery(document).ready(function()
{
	"use strict";

    if( !wp || !wp.media ) {
        return;
    }

    var menuTitle = (function ()
    {
        var t = file_gallery.L10n.attach_all_checked_copy.split(" ");
        var len = t.length;
        var j = 0;
        var i = 0;

        for( i; i < len; i++ )
        {
            j += t[i].length;

            if( j >= 18 )
            {
                t[i] += "<br />";
                j = 0;
            }
        }

        return t.join(" ");
    }());

    var state;
    var selection;
    var controller;
    var sel = false;
    var ready = false;
    var $filters = "select.attachment-filters";
    var ajaxurl = wp.media.model.settings.ajaxurl;
    var $responseContainer = jQuery('<div class="file-gallery-response"></div>');
    var $menuItem = jQuery('<a href="#" class="media-menu-item">' + menuTitle + '</a>');
    
    var wpMediaFramePost = wp.media.view.MediaFrame.Post;
    wp.media.view.MediaFrame.Post = wpMediaFramePost.extend(
    {
        mainMenu: function ( view )
        {
            wpMediaFramePost.prototype.mainMenu.call(this, view);

            var $menu = view.$el;
            controller = this;

            if( file_gallery.tinymce_is_active() )
            {
                controller.on("ready", function () 
                {
                    if( ! ready )
                    {
                        $filters = jQuery($filters);

                        $filters.on("change", function ()
                        {
                            if( this.value === "uploaded" ) {
                                $menuItem.hide();
                            } else {
                                $menuItem.show();
                            }
                        });

                        $responseContainer.hide();
                        controller.content.get().sidebar.$el.append($responseContainer);

                        ready = true;
                    }
                });

                controller.on("selection:toggle activate", function ()
                {
                    if( ready && file_gallery.tinymce_is_active() )
                    {
                        state = controller.state();
                        selection = state.get("selection");
                        sel = selection && selection.length ? selection.length : 0;

                        if( controller._state === "insert" && sel > 0 && $filters.val() !== "uploaded" ) {
                            $menuItem.show();
                        } else {
                            $menuItem.hide();
                        }
                    }
                });

                $menuItem.on("click", function () 
                {
                    state = controller.state();
                    selection = state.get("selection");

                    if( selection.length && $filters.val() !== "uploaded" )
                    {
                        $responseContainer.stop().fadeOut(75, function () {
                            $responseContainer.html("");
                        });

                        var data = {
                            action: "file_gallery_copy_attachments_to_post",
                            post_id: wp.media.model.settings.post.id,
                            ids: _.uniq( _.pluck(selection._byId, "id") ).join(","),
                            _ajax_nonce: file_gallery_attach_nonce
                        };

                        jQuery.post(ajaxurl, data, function (response)
                        {
                        	response = response.split("#").pop();
                            $responseContainer.html(response).fadeIn(500, function () {
                                $responseContainer.fadeOut(15000);
                            });
                        }, "html");
                    }
                });

                $menuItem.addClass("file-gallery-media-menu-item").hide();
                $menu.append($menuItem);
            }
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

			if( confirm("Really detach attachment from this post?") )
			{
				var attachment = file_gallery.getAttachmentById( this.model.get("id") );
				this.model.set("uploadedTo", 0);

				file_gallery.detachAttachments([attachment], function (response)
				{					
					responseContainer.html( response ).fadeIn(500, function () {
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