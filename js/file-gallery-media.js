/*global console _ wp jQuery file_gallery_attach_nonce console file_gallery*/

jQuery(document).ready(function ()
{
    "use strict";

    if (!wp || !wp.media) {
        return;
    }

    var menuTitle = (function()
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
    var ready = false;
    var responseContainerAdded = false;
    var $filters = "select.attachment-filters";
    var $responseContainer = jQuery('<div class="file-gallery-response"></div>');
    var $menuItem = jQuery('<a href="#" class="media-menu-item">' + menuTitle + '</a>');
    
    var wpMediaFramePost = wp.media.view.MediaFrame.Post;
    wp.media.view.MediaFrame.Post = wpMediaFramePost.extend(
    {
        mainMenu: function( view )
        {
            wpMediaFramePost.prototype.mainMenu.call(this, view);

            controller = this;

            var content;
            var $menu = view.$el;
            var post_id = parseInt(jQuery("#post_ID").val(), 10);

            var getUnattached = function ( selection )
            {
                if( ! selection ) {
                    return [];
                }

                return selection.filter(function(attachment) {
                    return attachment.get("uploadedTo") !== post_id;
                }).map(function(attachment) {
                    return attachment.get("id");
                });
            };

            var selectionObserver = function ()
            {
                if( ready && file_gallery.tinymce_is_active() )
                {
                    state = controller.state();
                    selection = state.get("selection");

                    var sel = selection && selection.length;
                    var unattached = getUnattached(selection);
                    var len = unattached.length > 0;
                    var state = controller._state === "insert";
                    var allfiles = $filters.val() !== "uploaded";

                    if( len && state && sel && allfiles ) {
                        $menuItem.show();
                    } else {
                        $menuItem.hide();
                    }
                }
            };

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
                        content = controller.content.get();

                        if( content.sidebar )
                        {
                            content.sidebar.$el.append($responseContainer);
                            responseContainerAdded = true;
                        }

                        ready = true;
                    }
                });

                controller.on("activate", selectionObserver);
                controller.on("selection:toggle", selectionObserver);

                if( file_gallery.options.wp_version < 4 ) {
                    jQuery("body").on("click", ".media-frame-content .attachment", selectionObserver);
                }

                $menuItem.on("click", function ( event ) 
                {
                    state = controller.state();
                    selection = state.get("selection");

                    if( ! responseContainerAdded )
                    {
                        content = controller.content.get();

                        if( content.sidebar )
                        {
                            content.sidebar.$el.append($responseContainer);
                            responseContainerAdded = true;
                        }
                    }

                    var unattached = getUnattached(selection);
                    var data;

                    if( unattached.length > 0 )
                    {
                        $responseContainer.stop().fadeOut(75, function() {
                            $responseContainer.html("");
                        });

                        data = {
                            action: "file_gallery_copy_attachments_to_post",
                            post_id: wp.media.model.settings.post.id,
                            ids: _.uniq( unattached ).join(","),
                            _ajax_nonce: file_gallery_attach_nonce
                        };

                        jQuery.post(wp.media.model.settings.ajaxurl, data, function (response)
                        {
                            $responseContainer.html( response.split("#").pop() ).fadeIn(500, function () {
                                $responseContainer.fadeOut(15000);
                            });

                            state.reset();
                        }, "html");
                    }

                    event.preventDefault();
                    return false;
                });

                $menuItem.addClass("file-gallery-media-menu-item").hide();
                $menu.append($menuItem);
            }
        }
    });

    wp.media.view.Attachment.Library = wp.media.view.Attachment.extend(
    {
        buttons: {
            check: true,
            attach: true
        }
    });

    jQuery("#tmpl-attachment").attr("id", "tmpl-attachment_original");
    jQuery("#tmpl-attachment-filegallery").attr("id", "tmpl-attachment");
});