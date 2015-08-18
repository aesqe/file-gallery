(function()
{
	"use strict";

    tinymce.create("tinymce.plugins.filegallery",
	{
        init: function(ed, url) {
			this.add_events(ed);
        },
		
		add_events: function( ed )
		{
			var $ = jQuery;
			
			if( ed.id !== "replycontent" )
			{
				ed.on("mousedown", function(mouseEvent)
				{
					wpActiveEditor = ed.id;

					var t = mouseEvent.target;

					if( t.className.indexOf("wpGallery") > -1 || t.className.indexOf("wp-gallery") > -1 )
					{
						file_gallery.gallery_image_clicked[ed.id] = true;

						if( ! t.id )
						{
							t.id = "file_gallery_tmp_" + file_gallery.tmp[ed.id];
							file_gallery.tmp[ed.id]++;
						}
	
						file_gallery.last_clicked_gallery[ed.id] = t.id;
						
						// call tinymce_gallery with image title as argument (title holds gallery options)
						file_gallery.tinymce_gallery( t.title );
					}
					else
					{
						// uncheck all items and serialize
						if( true === file_gallery.gallery_image_clicked[ed.id] )
						{
							file_gallery.gallery_image_clicked[ed.id] = false;
							$("#file_gallery_uncheck_all").trigger("click");
						}
					}
				});
				
				
				ed.on("mouseup", function(mouseEvent)
				{
					if ( tinymce.isIE && ! ed.isHidden() ) {
						ed.windowManager.insertimagebookmark = ed.selection.getBookmark(1);
					}
				});

				if( typeof ed.on === "function" ) // tinyMCE 4.x
				{
					ed.on("keyup", function(e)
					{
						if( file_gallery.gallery_image_clicked[ed.id] === true && (e.keyCode === 46 || e.keyCode === 27) )
						{
							$("#file_gallery_uncheck_all").trigger("click");
							file_gallery.gallery_image_clicked[ed.id] = false;
						}
					});
				}
				else
				{
					ed.onEvent.add(function(ed, e)
					{
						if( e.type === "keyup" && file_gallery.gallery_image_clicked[ed.id] === true && (e.keyCode === 46 || e.keyCode === 27) )
						{
							$("#file_gallery_uncheck_all").trigger("click");
							file_gallery.gallery_image_clicked[ed.id] = false;
						}
					});
				}
			}
		},
		
        getInfo: function() {
            return {
                longname : "File Gallery",
                author : 'Bruno "Aesqe" babic',
                authorurl : "http://skyphe.org/",
                infourl : "http://wordpress.org/plugins/file-gallery/",
                version : file_gallery.options.file_gallery_version
            };
        }
    });
	
    tinymce.PluginManager.add("filegallery", tinymce.plugins.filegallery);
})();