tinymce.PluginManager.add("filegallery", function (editor)
{
	var parseGallery = function( shortcode )
	{
		if( shortcode === void 0 ) {
			return;
		}

		var s = parseShortcode(shortcode);

		var attachments = file_gallery.data.attachments;
		var defaults = file_gallery.shortcodeDefaults;
		var cache = file_gallery.elementCache;
		var currentShortcode = "[gallery";
		var	external_attachments = [];
		var attrs = s.attrs.named;
		var	attached_ids = [];
		var	gallery_ids = [];
		var selection;
		var	i = 0;
		var el;

		var a = wp.media.file_gallery.attachments(s);
		console.log("wpmfg", a, wp.media.file_gallery);

		file_gallery.set("galleryOptions", attrs);

		for( i in cache )
		{
			el = cache[i];

			if( el.type === "select-one" || el.type === "select" ) {
				el.value = attrs[i];
			}
		}

		// if( attrs.tags )
		// {
		// 	jQuery("#fg_gallery_tags").val(attrs.tags);
		// 	jQuery("#files_or_tags").val("tags");
		// 	this.files_or_tags();
		// 	jQuery("#fg_gallery_tags_from").prop("checked", ! tags_from);
		// 	jQuery("#file_gallery_toggler").show();
		// }
		// else
		// {
		// 	jQuery("#files_or_tags").val("files");
		// 	this.files_or_tags();
		// }

		if( attrs.ids )
		{
			file_gallery.deselectAll( false );

			gallery_ids = _.map(attrs.ids.split(","), Number);
			attached_ids = _.pluck(attachments, "ID");

			if( attached_ids.length )
			{
				external_attachments = _.difference(gallery_ids, attached_ids);

				if( ! external_attachments.length )
				{
					file_gallery.selectAll();
					file_gallery.set("galleryAttachments", attachments);
				}
				else
				{
					file_gallery.ajaxGetAttachmentsById(gallery_ids, function(data)
					{
						_.each(data, function(el){ el.selected = true; })
						file_gallery.set("galleryAttachments", data);
					});

					selection = _.difference(attached_ids, gallery_ids);

					_.each(attachments, function(el, i)
					{
						if( selection.indexOf(el.ID) > -1 ) {
							file_gallery.set("attachments." + i + ".selected", true);
						}
					});

					file_gallery.serialize();
				}
			}
			else
			{
				external_attachments = gallery_ids;
			}
		}
		else
		{
			file_gallery.selectAll();
		}

		file_gallery.updateShortcode(attrs);
	};

	var parseShortcode = function( shortcode )
	{
		var defaults = _.clone(file_gallery.shortcodeDefaults); // default values
		var s = new wp.shortcode({attrs: shortcode}); // currently set values
		var attrs = s.attrs.named;

		console.log(s);

		if( attrs.link === "attachment" ) {
			attrs.link = "post";
		}

		if( attrs.orderby === "menu_order ID" || attrs.orderby === "default" ) {
			attrs.orderby = "";
		}

		if( attrs.id === file_gallery.shortcodeDefaults.id ) {
			attrs.id = "";
		}

		attrs.rel = attrs.rel || attrs.linkrel || void 0;
		attrs.linkrel = attrs.linkrel || attrs.rel || void 0;

		if( ["", "none", "file", "parent_post", "post"].indexOf(attrs.link) === -1 )
		{
			attrs.external_url = decodeURIComponent(attrs.link);
			attrs.link = "external_url";
		}

		if( attrs.linkrel && attrs.linkrel !== "true" && attrs.linkrel !== "false" )
		{
			attrs.rel = "true";
			attrs.linkrel = "true";
			attrs.linkrel_custom = attrs.linkrel.replace(/\\\[/, '[').replace(/\\\]/, ']');
		}
		
		s.attrs.named = _.extend(defaults, attrs); // complete current shortcode values

		return s;
	};

	var updateGallery = function(event)
	{
		var galleryView = wp.mce.views.getInstance( encodeURIComponent(editor.selection.getContent()) );
		
		if( galleryView )
		{
			// brute mode for now - FIX
			galleryView.setContent("", function(){}, true);
			wp.media.editor.insert( file_gallery.currentShortcode );
			deselect_gallery();
		}

		// var node = jQuery( editor.selection.getNode() );
		// if( ! node.hasClass("wpview-type-gallery") ) {
		// 	node = node.parents(".wpview-type-gallery").first();
		// }
		// node.attr("data-wpview-text", encodeURIComponent(file_gallery.currentShortcode));
		// wp.mce.views.refreshView(galleryView, selection);
		// console.log(galleryView, selection);
		// galleryView.setContent(file_gallery.currentShortcode, function(){}, true);
		// galleryView.render();
	};

	var deselect_gallery = function ()
	{
		file_gallery.gallerySelected[editor.id] = false;
		file_gallery.deselectAll();
		file_gallery.set("gallerySelected", false);
		file_gallery.set("galleryAttachments", []);
		file_gallery.set("galleryOptions.ids", "");
		file_gallery.updateShortcode();
	};

	if( window.file_gallery && editor.id !== "replycontent" )
	{
		editor.on("LoadContent", function ()
		{
			if( file_gallery.editor_bound ) {
				return;
			}

			file_gallery.editorBound = true;

			editor.dom.bind(editor.getBody(), "click", function galleryClick (event)
			{
				file_gallery.activeEditorId = editor.id;

				var target = event.target;
				var shortcode = "";
				var parents;

				if( target.className.indexOf("wpview-type-gallery") === -1 )
				{
					parents = jQuery(target).parents(".wpview-type-gallery");
					target = parents.length ? parents[0] : false;
				}

				if( target )
				{
					if( ! file_gallery.gallerySelected[editor.id] )
					{
						shortcode = decodeURIComponent(target.getAttribute("data-wpview-text"));
						file_gallery.gallerySelected[editor.id] = true;
						file_gallery.set("gallerySelected", true);

						if( ! target.id )
						{
							target.id = "file_gallery_tmp_" + file_gallery.tmp;
							file_gallery.tmp++;
						}

						parseGallery(shortcode);
					}
				}
				else
				{
					if( file_gallery.gallerySelected[editor.id] === true ) {
						deselect_gallery();
					}
				}
			});
		});

		editor.on("click", function()
		{
			if( tinymce.isIE && ! editor.isHidden() ) {
				editor.windowManager.insertimagebookmark = editor.selection.getBookmark(1);
			}

			deselect_gallery();
		});

		editor.on("keyup", function(event)
		{
			var sel = file_gallery.gallerySelected;

			if( sel[editor.id] && [46,27].indexOf(event.keyCode) > -1 ) //DEL or ESC
			{
				sel[editor.id] = false;
				file_gallery.deselectAll();
			}
		});

		editor.on("file_gallery_insert_gallery", function(event)
		{
			var node = jQuery( editor.selection.getNode() );

			if( node.hasClass("wpview-type-gallery") || node.parents(".wpview-type-gallery").length ) {
				return updateGallery();
			}

			var selection = editor.selection.getContent();

			console.log(selection);

			if( selection ) {
				editor.selection.setContent(file_gallery.currentShortcode);
			} else {
				wp.media.editor.insert( file_gallery.currentShortcode );
			}
		});

		editor.on("file_gallery_update_gallery", updateGallery);
	}
});
