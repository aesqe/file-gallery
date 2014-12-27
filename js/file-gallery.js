/*global jQuery, wpActiveEditor, tinymce, WPRemoveThumbnail, file_gallery_L10n, file_gallery_options, ajaxurl, console, alert, confirm, send_to_editor, init_file_gallery */

"use strict";

var file_gallery;

var FileGallery = Ractive.extend(
{

// properties

	uploader_dragdrop: true,
	activeEditorId: null,
	upload_inside: false,
	editorBound: false,
	responseDiv: null,
	uploader: null,

	shortcodeDefaults: {},
	gallerySelected: {},
	elementCache: {},
	options: {},
	L10n: {},

	selectedItems: [],
	allItems: [],

	attachment_sortby: "menu_order, ASC",
	currentShortcode: "",
	deleteWhat: "data_only",
	originals: "",
	copies: "",

	featuredImageId: 0, //wp.media.view.settings.post.featuredImageId
	tmp: 0,

	singleTemplates:
	{
		_default: '<img class="align{{align}} size-{{sizeName}} wp-image-{{ID}}{{imageclass}}" src="{{sizeFile}}" alt="{{imageAltText}}" width="{{sizeWidth}}" height="{{sizeHeight}}" />',
		linked: '<a href="{{link}}"{{linkclass}}><img class="align{{align}} size-{{sizeName}} wp-image-{{ID}}{{imageclass}}" src="{{sizeFile}}" alt="{{imageAltText}}" width="{{sizeWidth}}" height="{{sizeHeight}}" /></a>',
		captioned: '[caption id="attachment_{{ID}}" align="align{{align}}" width="{{sizeWidth}}"]<img class="wp-image-{{ID}} size-{{sizeName}}{{imageclass}}" src="{{sizeFile}}" alt="{{imageAltText}}" width="{{sizeWidth}}" height="{{sizeHeight}}" />{{caption}}[/caption]',
		captionedLinked: '[caption id="attachment_{{ID}}" align="align{{align}}" width="{{sizeWidth}}"]<a href="{{link}}"{{linkclass}}><img class="wp-image-{{ID}} size-{{sizeName}}{{imageclass}}" src="{{sizeFile}}" alt="{{imageAltText}}" width="{{sizeWidth}}" height="{{sizeHeight}}" /></a>{{caption}}[/caption]'
	},



// methods

	init: function ()
	{
		var self = this;
		var list = ["id", "size", "link",
			"linkrel", "linkrel_custom", "linksize",
			"external_url", "template", "order",
			"orderby", "linkclass", "imageclass",
			"galleryclass", "mimetype", "limit",
			"offset", "paginate", "columns"];
		var wpdefs = wp.media.gallery.defaults || {};
		var cache = this.elementCache;
		var galleryOptions = {};
		var len = list.length;
		var val = "";
		var j = "";
		var i = 0;

		this.options = file_gallery_options;
		this.L10n = file_gallery_L10n;
		this.responseDiv = jQuery("#file_gallery_response");
		this.shortcodeDefaults = {
			_file_gallery: true,
			linksize: "large",
			template: "default",
			linkclass: "",
			imageclass: "",
			galleryclass: "",
			linkrel: "true",
			linkrel_custom: "",
			external_url: "",
			mimetype: "",
			tags: "",
			tags_from: "",
			limit: "",
			offset: "",
			paginate: "false"
		};

		_.extend(this.shortcodeDefaults, wpdefs);
		galleryOptions = _.clone(this.shortcodeDefaults);

		// cache option elements
		for( i = 0; i < len; i++ )
		{
			val = list[i];
			cache[val] = document.getElementById("file_gallery_" + val);
			galleryOptions[val] = cache[val].value;
		}
		this.set("galleryOptions", galleryOptions);

		this.set("singleOptions", {
			size: jQuery("#file_gallery_single_size").val(),
			link: jQuery("#file_gallery_single_linkto").val(),
			external_url: jQuery("#file_gallery_single_external_url").val(),
			linkclass: jQuery("#file_gallery_single_linkclass").val(),
			imageclass: jQuery("#file_gallery_single_imageclass").val(),
			align: jQuery("#file_gallery_single_align").val(),
			caption: jQuery("#file_gallery_single_caption").prop("checked")
		});

		this.on({
			"selectAll": self.selectAll,
			"deselectAll": self.deselectAll,
			"refresh": self.refresh,
			"select": self.select,
			"setAsThumbnail": self.setAsThumbnail,
			"unsetAsThumbnail": self.unsetAsThumbnail,
			"dragenter": self.dragenter,
			"dragleave": self.dragleave,
			"edit": self.editSingle,
			"detachDelete": self.detachDelete,
			"detachSingle": self.detachSingle,
			"deleteSingle": self.deleteSingle,
			"saveAttachment": self.saveAttachment,
			"cancelEditAttachment": self.cancelEditAttachment,
			"zoom": self.zoom,
			"zoomClose": self.zoomClose,
			"zoomPrev": self.zoomPrev,
			"zoomNext": self.zoomNext,
			"regenerate": self.regenerate,
			"insertGallery": self.insertGallery,
			"insertSingle": self.insertSingle,
			"changeOption": self.changeOption,
			"changeSingleOption": self.changeSingleOption,
			"fieldsetToggle": self.fieldsetToggle
		});

		this.getEditor();
		this.load();
	},

	load: function ()
	{
		var self = this;
		var post_id = jQuery("#post_ID").val();
		var singleOptions = _.clone(this.data.singleOptions);
		var galleryOptions = _.clone(this.data.galleryOptions);
		var data = {
			action: "file_gallery_init",
			post_id: post_id,
			_ajax_nonce: self.options.file_gallery_nonce
		};

		this.set("attachments", []);

		jQuery("#file_gallery").removeClass("uploader");

		jQuery.get(ajaxurl, data, function ( data )
		{
			console.log(data);

			var singleEditMode = self.data.singleEditMode;
			var attachments = data["attachments"];
			var attachmentBeingEdited = null;
			var len = attachments.length;
			var atID = 0;

			if( singleEditMode )
			{
				atID = self.data.attachmentBeingEdited.ID;
				attachmentBeingEdited = _.findWhere(attachments, {ID: atID});

				if( attachmentBeingEdited === void 0 )
				{
					attachmentBeingEdited = null;
					singleEditMode = false;
				}
			}

			self.reset({
				insert_single_options_state: self.options.insert_single_options_state,
				insert_options_state: self.options.insert_options_state,
				attachmentBeingEdited: attachmentBeingEdited,
				singleEditMode: singleEditMode,
				galleryOptions: galleryOptions,
				singleOptions: singleOptions,
				mediaTags: data["mediaTags"],
				attachments: attachments,
				gallerySelected: false,
				upload_inside: false,
				actionResponse: "",
				zoomed: false,
			});

			_.extend(self.options, data["options"]);

			self.set("thumbWidth", self.options.thumbWidth);
			self.set("thumbHeight", self.options.thumbHeight);

			self.do_plugins();
			self.serialize();
			self.updateShortcode();

			self.deleteDialog = jQuery("#file_gallery_delete_dialog");

			if( singleEditMode ) {
				self.loadAttachmentCustomFields(attachmentBeingEdited);
			}
		}, "json")
		.fail(function (data) {
			console.log("error", data.responseText);
		});
	},

	refresh: function (event)
	{
		this.load();

		event.original.preventDefault();
		return false;
	},

	ajaxGetAttachmentsById: function ( ids, callback )
	{
		var data = {
			action: "file_gallery_get_attachments_by_id",
			attachment_ids: ids,
			_ajax_nonce: this.options.file_gallery_nonce
		};

		jQuery.get(ajaxurl, data, function ( data ) {
			callback(data);
		}, "json");
	},

	insertGallery: function (event)
	{
		var editor = this.getEditor();

		if( editor ) {
			editor.fire("file_gallery_insert_gallery");
		} else {
			send_to_editor(this.currentShortcode);
		}

		event.original.preventDefault();
		return false;
	},

	insertSingle: function (event)
	{
		var files = this.getSelectedAttachments();
		var len = files.length;

		if( len > 0 )
		{
			var f;
			var size;
			var o = this.data.singleOptions;
			var linked = (o.linkto !== "none");
			var templates = this.singleTemplates;
			var template = "";
			var output = "";
			var link = "";
			var i = 0;

			if( linked ) {
				template = o.caption ? templates.captionedLinked : templates.linked;
			}
			else if( o.caption ) {
				template = templates.captioned;
			}
			else {
				template = templates._default;
			}

			o.linkclass = o.linkclass ? ' class="' + o.linkclass + '"' : '';

			template = template.replace(/{{align}}/g, o.align)
							   .replace("{{sizeName}}", o.size)
							   .replace("{{linkclass}}", o.linkclass)
							   .replace("{{imageclass}}", o.imageclass);

			for( i; i < len; i++ )
			{
				f = files[i];
				size = f.meta.sizes[o.size] || f.meta;

				if( linked )
				{
					switch( o.linkto )
					{
						case "external_url": link = o.external_url; break;
						case "file": link = f.baseUrl + f.file; break;
						case "attachment": link = f.permalink; break;
						case "parent_post": link = f.parent_post_permalink; break;
					}
				}

				output += "\n" + template.replace(/{{ID}}/g, f.ID)
								  .replace("{{link}}", link)
								  .replace(/{{sizeWidth}}/g, size.width)
								  .replace("{{sizeHeight}}", size.height)
								  .replace("{{sizeFile}}", f.baseUrl + size.file)
								  .replace("{{imageAltText}}", f.imageAltText || f.post_title)
								  .replace(/{{caption}}/g, f.post_excerpt);
			}

			send_to_editor(output);
		}

		event.original.preventDefault();
		return false;
	},

	serialize: function ()
	{
		this.allItems = _.pluck(this.data.attachments, "ID");
		this.selectedItems = _.pluck(this.getSelectedAttachments(), "ID");
	},

	updateShortcode: function ( attrs )
	{
		attrs = attrs || this.data.galleryOptions;

		if( attrs.orderby === "default" ) {
			attrs.orderby = "menu_order ID";
		}

		var defaults = this.shortcodeDefaults;
		var currentShortcode = "[gallery";
		var ignored = ["", "", "undefined"];

		_.each(attrs, function (value, key, list)
		{
			value = String(value);
			ignored[0] = String(defaults[key]);

			if( ! _.contains(ignored, value) ) {
				currentShortcode += " " + key + '="' + value + '"';
			}
		});

		this.currentShortcode = currentShortcode + "]";
	},

	changeOption: function (event, option)
	{
		var el = event.node;
		var value = el.value;
		var editor = this.getEditor();

		if( el.type === "checkbox" ) {
			value = el.checked;
		}

		this.set("galleryOptions." + option, value);
		this.updateShortcode();

		if( editor && this.gallerySelected[editor.id] ) {
			editor.fire("file_gallery_update_gallery");
		}
	},

	changeSingleOption: function (event, option)
	{
		var el = event.node;
		var value = el.value;

		if( el.type === "checkbox" ) {
			value = el.checked;
		}

		this.set("singleOptions." + option, value);
	},

	hasAttachments: function ()
	{
		return (this.data.attachments.length > 0);
	},

	updateShortcodeIds: function ()
	{
		var current = this.get("galleryOptions.ids");
		var editor = this.getEditor();
		var selected = [];
		var external = [];

		if( this.isAnySelected() && ! this.isAllSelected() ) {
			selected = this.selectedItems;
		}

		external = _.difference(current, selected);

		this.set("galleryOptions.ids", selected);

		this.updateShortcode();

		if( editor && this.gallerySelected[editor.id] ) {
			editor.fire("file_gallery_update_gallery");
		}
	},

	isAnySelected: function ()
	{
		return (this.selectedItems.length > 0);
	},

	isAllSelected: function ()
	{
		var all = this.allItems;
		var selected = this.selectedItems;

		if( all.length && selected.length ) {
			return (_.difference(all, selected).length === 0);
		}

		return false;
	},

	select: function (event, attachment)
	{
		this.set(event.keypath + ".selected", ! attachment.selected);
		this.serialize();
	},

	selectAll: function (event)
	{
		if( this.hasAttachments() && ! this.isAllSelected() )
		{
			var self = this;

			_.each(this.data.attachments, function (el, i)
			{
				if( ! el.selected ) {
					self.set("attachments." + i + ".selected", true);
				}
			});

			this.serialize();
		}

		if( event )
		{
			event.original.preventDefault();
			return false;
		}
	},

	deselectAll: function (event)
	{
		if( this.hasAttachments() && this.isAnySelected() )
		{
			var self = this;

			_.each(this.data.attachments, function (el, i)
			{
				if( el.selected ) {
					self.set("attachments." + i + ".selected", false);
				}
			});

			this.serialize();
		}

		if( event )
		{
			event.original.preventDefault();
			return false;
		}
	},

	getSelectedAttachments: function ()
	{
		return _.where(this.data.attachments, {selected: true});
	},

	getAttachmentById: function (id)
	{
		return _.findWhere(this.data.attachments, {ID: id});
	},

	getEditor: function ()
	{
		if( window.tinymce !== void 0 )
		{
			var edID = this.activeEditorId || window.wpActiveEditor || "content";
			var editor = tinymce.EditorManager.get(edID);

			if( editor )
			{
				this.activeEditorId = editor.id;
				return editor;
			}
		}

		return null;
	},

	tinymce_is_active: function ()
	{
		var editor = file_gallery.tinymce_get_editor();
		return editor && ! editor.isHidden()
	},

	zoom: function (event, data)
	{
		var self = this;
		var zoomed = (data !== void 0) ? data : this.get(event.keypath);
		var all = this.allItems;
		var len = all.length
		var i = all.indexOf(zoomed.ID);

		console.log(zoomed, data, event);

		// FIXED THESE?
			zoomed.previous = (i > 0) ? this.getAttachmentById(all[i-1]) : false;
			zoomed.next = (i+1 !== len) ? this.getAttachmentById(all[i+1]) : false;

		this.set("zoomed", zoomed);

		jQuery(document).on("keyup.fileGalleryZoom", function (event)
		{
			switch( event.keyCode )
			{
				case 27: self.zoomClose(event); break; //ESC
				case 37: self.zoomPrev(event); break; // left arrow
				case 39: self.zoomNext(event); break; // right arrow
				case 69: self.editSingle(event, self.data.zoomed); break; // E
			}
		});

		if( event.original ) {
			event = event.original;
		}

		event.preventDefault();
		return false;
	},

	zoomPrev: function (event)
	{
		if( this.data.zoomed.previous ) {
			this.zoom(event, this.data.zoomed.previous);
		}

		if( event.original ) {
			event = event.original;
		}

		event.preventDefault();
		return false;
	},

	zoomNext: function (event)
	{
		if( this.data.zoomed.next ) {
			this.zoom(event, this.data.zoomed.next);
		}

		if( event.original ) {
			event = event.original;
		}

		event.preventDefault();
		return false;
	},

	zoomClose: function (event)
	{
		jQuery(document).off("keyup.fileGalleryZoom");
		this.set("zoomed", false);

		if( event.original ) {
			event = event.original;
		}

		event.preventDefault();
		return false;
	},

	regenerate: function ( event, attachments )
	{
		var self = this;
		var el = event.node;
		var len = attachments.length;
		var data = {
			action: "file_gallery_regenerate_thumbnails",
			attachment_ids: len ? _.pluck(attachments, "ID") : [attachments.ID],
			_ajax_nonce: file_gallery_regenerate_nonce
		};

		this.set("regenerating", true);

		jQuery.post(ajaxurl, data, function (response)
		{
			self.displayResponse(response.message);
			self.set("regenerating", false);
		}, "json");

		event.original.preventDefault();
		return false;
	},

	save_menu_order: function (event, ui)
	{
		this.updateOrder(this.data.attachments, ui.item.context._ractive.index.i, ui.item.index());
		this.serialize();

		if( ! this.allItems ) {
			return false;
		}

		var self = this;
		var data = {
			action: "file_gallery_save_menu_order",
			post_id: jQuery("#post_ID").val(),
			attachment_order: this.allItems,
			_ajax_nonce: this.options.file_gallery_nonce
		};

		this.set("responseLoading", true);

		jQuery.post(ajaxurl, data, function (response) {
			self.displayResponse(response);
		}, "html");
	},

	updateOrder: function (list, oldIndex, newIndex)
	{
		var source = list[oldIndex];

		list.splice( oldIndex, 1 );
		list.splice( newIndex, 0, source);
	},

	// modified copy of WPSetAsThumbnail
	setAsThumbnail: function (event)
	{
		var self = this;
		var loader = jQuery("#file-gallery-item-" + event.context.ID).find(".thumbLoadingAnim");
		var data = {
			action: "set-post-thumbnail",
			post_id: event.context.post_parent,
			thumbnail_id: event.context.ID,
			_ajax_nonce: file_gallery_setAsThumbnailNonce,
			cookie: encodeURIComponent( document.cookie )
		};

		this.set("responseLoading", true);
		loader.show();

		jQuery.post(ajaxurl, data, function (str)
		{
			loader.hide();

			if ( str == "0" ) {
				self.displayResponse(self.L10n.setThumbError);
			}
			else
			{
				var currentThumb = jQuery("#file_gallery_list .post_thumb");

				jQuery('a.wp-post-thumbnail').show();
				WPSetThumbnailID(event.context.ID);
				WPSetThumbnailHTML(str);

				if( currentThumb.length ) {
					self.set(currentThumb[0]._ractive.keypath + ".isPostThumb", false);
				}

				self.set(event.keypath + ".isPostThumb", true);
				self.featuredImageId = event.context.ID;
				self.displayResponse(self.L10n.post_thumb_set);
			}
		});

		event.original.preventDefault();
		return false;
	},

	unsetAsThumbnail: function (event)
	{
		jQuery("#remove-post-thumbnail").trigger("click");
		this.set(event.keypath + ".isPostThumb", false);

		event.original.preventDefault();
		return false;
	},

	removeThumbnail: function ()
	{
		var currentThumb = jQuery("#file_gallery_list .post_thumb");

		if( currentThumb.length ) {
			this.set(currentThumb[0]._ractive.keypath + ".isPostThumb", false);
		}
	},

	detachSingle: function( event )
	{
		var attachment = this.get(event.keypath);
		this.detachAttachments([attachment]);

		event.original.preventDefault();
		return false;
	},

	deleteSingle: function( event )
	{
		var attachment = this.get(event.keypath);

		if( attachment.isCopyOf ) {
			this.deleteWhat = "data_only";
		} else {
			this.deleteWhat = "all";
		}

		this.showDeleteDialog( [attachment] );

		event.original.preventDefault();
		return false;
	},

	detachDelete: function ( event, action )
	{
		var keypath = event.keypath;

		switch ( action )
		{
			case "detach":
				this.set(keypath + ".detachDeleting", false);
				this.set(keypath + ".detaching", true);
				this.set(keypath + ".deleting", false);
				break;
			case "delete":
				this.set(keypath + ".detachDeleting", false);
				this.set(keypath + ".detaching", false);
				this.set(keypath + ".deleting", true);
				break;
			case "cancel":
				this.set(keypath + ".detachDeleting", false);
				this.set(keypath + ".detaching", false);
				this.set(keypath + ".deleting", false);
				break;
			default:
				this.set(keypath + ".detachDeleting", true);
				this.set(keypath + ".detaching", false);
				this.set(keypath + ".deleting", false);
				break;
		}

		event.original.preventDefault();
		return false;
	},

	fieldsetToggle: function ( event, what )
	{
		what = what || "hide_gallery_options";

		var	state = 0;
		var action = "file_gallery_save_toggle_state";
		var option = "insert_options_state";

		switch( what )
		{
			case "hide_single_options":
				action = "file_gallery_save_single_toggle_state";
				option = "insert_single_options_state";
				break;
			/*case "hide_acf":
				action = "file_gallery_save_acf_toggle_state";
				option = "acf_state";
				break;*/
		}

		if( this.data[option] === 0 ) {
			state = 1;
		}

		this.set(option, state);

		jQuery.post(ajaxurl, {
			action: action,
			state: state,
			_ajax_nonce: this.options.file_gallery_nonce
		});

		event.original.preventDefault();
		return false;
	},

	dragenter: function (event)
	{
		if( this.uploader_dragdrop && ! this.data.upload_inside ) {
			this.set("upload_inside", true);
		}

		event.original.stopPropagation();
		event.original.preventDefault();
		return false;
	},

	dragleave: function (event)
	{
		var target = event.original.target;
		var container = document.getElementById("fg_container");

		if( this.uploader_dragdrop )
		{
			// http://stackoverflow.com/questions/7110353/
			if( target !== container && jQuery.contains(container, target) ) {
				// still inside container
			} else if( this.data.upload_inside ) {
				this.set("upload_inside", false);
			}
		}

		event.original.stopPropagation();
		event.original.preventDefault();
		return false;
	},

	upload_handle_error: function (error, uploader)
	{
		if( console && console.log ) {
			console.log(error);
		}
	},

	editSingle: function (event, attachment)
	{
		if( this.data.zoomed ) {
			this.zoomClose(event);
		}

		var self = this;
		var target = attachment || this.data.attachments[event.index.i];
			target.customFieldsTable = "<p>Loading attachment custom fields...</p>"; // FIXIT

		this.set("attachmentBeingEdited", target);
		this.set("singleEditMode", true);
		this.loadAttachmentCustomFields(target);
		document.getElementById("file_gallery").scrollIntoView(true);

		event.original.preventDefault();
		return false;
	},

	loadAttachmentCustomFields: function (attachment)
	{
		var self = this;
		var data = {
			action: "file_gallery_get_acf",
			post_id: attachment.ID,
			_ajax_nonce: this.options.file_gallery_nonce
		};

		jQuery.post(ajaxurl, data, function (data)
		{
			self.set("attachmentBeingEdited.customFieldsTable", data);

			jQuery("#attachment-the-list").wpList(
			{
				addAfter: function ()
				{
					jQuery("table#attachment-list-table").show();
				},
				addBefore: function ( s )
				{
					s.data += "&post_id=" + attachment.ID;
					return s;
				}
			});
		}, "html");
	},

	getAttachmentCustomFields: function ()
	{
		var output = {};

		jQuery("#attachment_data_edit_form .custom_field textarea").each(function ()
		{
			// attachments[ID][FIELDNAME]
			var key = this.name.match(/attachments\[\d+\]\[([^\]]+)\]/)[1];
			output[key] = this.value;
		});

		return output;
	},

	saveAttachment: function ( event )
	{
		var self = this;
		var data = {
			post_id: event.context.post_parent,
			attachment_id: event.context.ID,
			action: "file_gallery_update_attachment",
			post_alt: jQuery("#file_gallery_attachment_post_alt_text").val(),
			post_title: jQuery("#file_gallery_attachment_post_title").val(),
			post_content: jQuery("#file_gallery_attachment_post_content").val(),
			post_excerpt: jQuery("#file_gallery_attachment_post_excerpt").val(),
			tax_input: jQuery("#file_gallery_attachment_tax_input").val(),
			menu_order: jQuery("#file_gallery_attachment_menu_order").val(),
			custom_fields: this.getAttachmentCustomFields(),
			_ajax_nonce: this.options.file_gallery_nonce
		};

		jQuery.post(ajaxurl, data, function (response)
		{
			self.displayResponse(response);
			self.set("singleEditMode", false);
		}, "html");

		event.original.preventDefault();
		return false;
	},

	cancelEditAttachment: function ( event )
	{
		this.set("singleEditMode", false);

		event.original.preventDefault();
		return false;
	},

	displayResponse: function (response, fade)
	{
		var div = this.responseDiv.children(".text");

		this.set("responseLoading", false);
		fade = (fade === void 0) ? 7000 : Number(fade);

		if( isNaN(fade) ) {
			fade = 0;
		}

		div.stop(true, true).css({"opacity": 0, "display": "none"});
		this.set("actionResponse", response);
		div.css({"opacity": 1, "display": "block"});

		if( fade > 0 ) {
			div.fadeOut(fade);
		}
	},

	do_plugins: function ()
	{
		var self = this;

		jQuery(".file_gallery_list").sortable(
		{
			// connectWith: ".file_gallery_list", // TODO
			placeholder: "attachment ui-selected",
			tolerance: "pointer",
			items: "li",
			opacity: 0.6,
			start: function () {},
			update: function (event, ui)
			{
				var editor = self.getEditor();

				if( this.id === "file_gallery_list" )
				{
					self.save_menu_order(event, ui);

					if( self.data.galleryAttachments.length === 0 )
					{
						var ids = _.pluck(self.data.attachments, "ID");

						self.set("galleryOptions.ids", ids);
						self.updateShortcode();

						if( editor && self.gallerySelected[editor.id] ) {
							editor.fire("file_gallery_update_gallery");
						}
					}
				}
				else if( this.id = "file_gallery_galleryAttachments" )
				{
					var list = self.data.galleryAttachments;

					self.updateOrder(list, ui.item.context._ractive.index.i, ui.item.index());
					self.set("galleryOptions.ids", _.pluck(list, "ID"));
					self.updateShortcode();

					if( editor && self.gallerySelected[editor.id] ) {
						editor.fire("file_gallery_update_gallery");
					}
				}
			}
		});

		// set up delete originals choice dialog
		jQuery("#file_gallery_delete_dialog").dialog(
		{
			autoOpen: false,
			bgiframe: true,
			resizable: false,
			modal: true,
			draggable: false,
			closeText: self.L10n.close,
			dialogClass: "wp-dialog",
			width: 600,
			close: function (event, ui) {},
			buttons: {
				"Cancel": function ()
				{
					self.deleteWhat = "data_only";
					self.deleteDialog.dialog("close");
				},
				"Delete attachment data only": function ()
				{
					var message = attachments.length > 1 ? self.L10n.sure_to_delete : false;
					var attachments = self.deleteDialog.data("attachments");

					self.deleteWhat = "data_only";
					self.deleteAttachments(attachments, message);
					self.deleteDialog.dialog("close");
				},
				"Delete attachment data, its copies and the files": function ()
				{
					var message = attachments.length > 1 ? self.L10n.sure_to_delete : false;
					var attachments = self.deleteDialog.data("attachments");

					self.deleteWhat = "all";
					self.deleteAttachments(attachments, message);
					self.deleteDialog.dialog("close");
				}
			}
		});

		jQuery("#file_gallery_copy_all_dialog").dialog(
		{
			autoOpen: false,
			bgiframe: true,
			resizable: false,
			modal: true,
			draggable: false,
			closeText: self.L10n.close,
			dialogClass: "wp-dialog",
			position: "center",
			width: 500,
			buttons: {
				"Cancel": function () {
					jQuery(this).dialog("close");
				},
				"Continue": function ()
				{
					var from_id = parseInt(jQuery("#file_gallery_copy_all_dialog input#file_gallery_copy_all_from").val(), 10);

					if( isNaN(from_id) || from_id === 0 )
					{
						if( isNaN(from_id) ) {
							from_id = "-none-";
						}

						alert(self.L10n.copy_from_is_nan_or_zero.replace(/%d/, from_id));

						return false;
					}

					self.copy_all_attachments(from_id);
					jQuery(this).dialog("close");
				}
			}
		});
	},

	showDeleteDialog: function ( attachments )
	{
		var message = attachments.length ? false : this.L10n.sure_to_delete;
		var haveCopies = _.filter(attachments, function(attachment){ return attachment.hasCopies; });

		if( haveCopies.length ) {
			this.deleteDialog.data("attachments", _.pluck(attachments, "ID")).dialog("open");
		} else {
			this.deleteAttachments(attachments, message);
		}

		return false;
	},

	detachAttachments: function( attachments, callback )
	{
		var self = this;
		var message = attachments.length === 1 ? this.L10n.detaching_attachment : this.L10n.detaching_attachments;
		var data = {
			action: "file_gallery_main_detach",
			attachment_ids: _.pluck(attachments, "ID"),
			_ajax_nonce: this.options.file_gallery_nonce
		};

		this.displayResponse(message);

		jQuery.post(ajaxurl, data, function (response)
		{
			var message = response.message || response.error;

			self.displayResponse(message);

			if( response.success ) {
				self.removeAttachmentsFromList(data.attachment_ids);
			}

			if( typeof callback === "function" ) {
				callback(message);
			}
		}, "json");
	},

	/**
	 * deletes checked attachments
	 */
	deleteAttachments : function( attachments, message )
	{
		if( ! attachments ) {
			return false;
		}

		message = message || false;

		var self = this;
		var len = attachments.length;
		var deleteWhat = _.contains(["all", "data_only"], this.deleteWhat);

		if( ! deleteWhat ) {
			return false;
		}

		if( ! len ) {
			return false;
		}

		if( ! message || confirm(message)  )
		{
			var text = len > 1 ? 
				this.L10n.deleting_attachments : this.L10n.deleting_attachment;
			var data = {
				action: "file_gallery_main_delete",
				attachment_ids: _.pluck(attachments, "ID"),
				delete_what: this.deleteWhat,
				_ajax_nonce: this.options.file_gallery_nonce
			};

			self.displayResponse(text);

			jQuery.post(ajaxurl, data, function ( response )
			{
				var message = response.message || response.error;

				self.displayResponse(message);

				if( response.success ) {
					self.removeAttachmentsFromList(data.attachment_ids);
				}
			}, "json");
		}

		this.deleteWhat = "data_only";
	},

	removeAttachmentsFromList: function (attachment_ids)
	{
		var list = this.get("attachments");

		_.each(list, function(attachment, index)
		{
			if( _.contains(attachment_ids, attachment.ID) ) {
				list.splice( index, 1 );
			}
		})
	},

	tinymceDeselect: function ()
	{
		var editor = this.getEditor();

		if( editor )
		{
			if( editor.selection ) {
				editor.selection.collapse(false);
			}

			tinymce.execCommand("mceRepaint", false, editor.id);
			tinymce.execCommand("mceFocus", false, editor.id);
		}
	}
});

jQuery(document).ready(function ()
{
	if( ! wp || ! wp.media ) {
		return;
	}

	file_gallery = new FileGallery(
	{
		el: "file_gallery_inner",
		template: "#file_gallery_ractive_template",
		data: {
			actionResponse: "",
			insert_options_state: file_gallery_options.insert_options_state,
			insert_single_options_state: file_gallery_options.insert_single_options_state,
			upload_inside: false,
			singleEditMode: false,
			gallerySelected: false,
			zoomed: null,
			singleOptions: {},
			attachments: [],
			mediaTags: []
		}
	});

	if( wp.media.collection )
	{
		wp.media.file_gallery = new wp.media.collection({
			tag: 'gallery',
			type : null,
			editTitle : wp.media.view.l10n.editGalleryTitle,
			defaults : file_gallery.shortcodeDefaults
		});
	}

























	/* === BINDINGS === */

	jQuery("#postimagediv").on("click", "#remove-post-thumbnail", function (event)
	{
		file_gallery.removeThumbnail();
	});



	// single attachment editing view
	jQuery("#file_gallery").on("keypress keyup", "#file_gallery_attachment_post_alt, #file_gallery_attachment_post_title, #file_gallery_attachment_post_excerpt, #file_gallery_attachment_tax_input, #file_gallery_attachment_menu_order", function (e)
	{
		if( e.which === 13 || e.keyCode === 13 ) // on enter
		{
			// Disabled. http://wordpress.org/support/topic/how-to-stop-enter-key-to-save-in-attachment-editing-screen?replies=1
			// jQuery("#file_gallery_edit_attachment_save").trigger("click");
			// e.preventDefault();
			return false;
		}
		/**
		else if( e.which === 27 || e.keyCode === 27 ) // on esc
		{
			jQuery("#file_gallery_edit_attachment_cancel").trigger("click");
		}
		/**/
	});


	jQuery("#file_gallery").on("submit", "#file_gallery_copy_all_form", function () {
		return false;
	});


	// copy all attachments from another post
	jQuery("#file_gallery").on("click", "#file_gallery_copy_all", function () {
		jQuery("#file_gallery_copy_all_dialog").dialog("open");
	});



	/* attachment edit screen */

	// acf enter on new field name
	jQuery("#file_gallery").on("keypress keyup", "#new_custom_field_key", function (e)
	{
		if( e.which === 13 || e.keyCode === 13 ) // on enter
		{
			jQuery("#file_gallery #new_custom_field_submit").trigger("click");
			e.preventDefault();
		}
	});


	/* thumbnails */




	/* main menu buttons */

	// delete checked attachments button click
	jQuery("#file_gallery").on("click", "#file_gallery_delete_checked", function () {
		file_gallery.showDeleteDialog( file_gallery.getSelectedAttachments() );
	});

	// detach checked attachments button click
	jQuery("#file_gallery").on("click", "#file_gallery_detach_checked", function () {
		file_gallery.detachAttachments(file_gallery.getSelectedAttachments(), file_gallery.L10n.sure_to_detach);
	});




	/* other bindings */

	// tags from current post only checkbox, switch to tags button
	jQuery("#file_gallery").on("click", "#fg_gallery_tags_from", function () {
		file_gallery.serialize();
	});

	// whether to show tags or list of attachments
	jQuery("#file_gallery").on("click", "#file_gallery_switch_to_tags", function ()
	{
		file_gallery.serialize();
		file_gallery.files_or_tags();
	});

	// clickable tag links
	jQuery("#file_gallery").on("click", ".fg_insert_tag", function () {
		return file_gallery.add_remove_tags( this );
	});

	// alternative display mode, with smaller thumbs and attachment titles
	jQuery("#file_gallery").on("click", "#file_gallery_toggle_textual", function ()
	{
		jQuery("#file_gallery_list").toggleClass("textual");
		jQuery(this).prop("disabled", true);

		jQuery.post
		(
			ajaxurl,
			{
				action: "file_gallery_toggle_textual",
				state: jQuery("#file_gallery_list").hasClass("textual") ? 1 : 0,
				_ajax_nonce: file_gallery.options.file_gallery_nonce
			},
			function () {
				jQuery("#file_gallery_toggle_textual").prop("disabled", false);
			}
		);
	});

	jQuery("body").on("click", ".media-frame .media-frame-content .attachment", function ()
	{
		jQuery(".file-gallery-response").hide();
	});

	wp.media.view.Modal.prototype.on("close", function ()
	{
		file_gallery.tinymceDeselect();
		file_gallery.load();
	});
});


// --------------------------------------------------------- //


// thanks to http://phpjs.org/functions/strip_tags:535
function strip_tags(input, allowed)
{
	"use strict";
	// making sure the allowed arg is a string containing only tags in lowercase (<a><b><c>)
	allowed = (((allowed || "") + "").toLowerCase().match(/<[a-z][a-z0-9]*>/g) || []).join('');
	var tags = /<\/?([a-z][a-z0-9]*)\b[^>]*>/gi, commentsAndPhpTags = /<!--[\s\S]*?-->|<\?(?:php)?[\s\S]*?\?>/gi;
	return input.replace(commentsAndPhpTags, '').replace(tags, function($0, $1)	{
		return allowed.indexOf('<' + $1.toLowerCase() + '>') > -1 ? $0 : '';
	});
}
