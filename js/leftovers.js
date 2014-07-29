// clickable tags
		jQuery(".fg_insert_tag").each( function()
		{
			var ct = "," + jQuery("#fg_gallery_tags").val() + ",",
				ns = "," + jQuery(this).attr("name") + ",",
				nn = "," + jQuery(this).html() + ",";

			if ( ct.search(ns) > -1 || ct.search(nn) > -1 ) {
				jQuery(this).addClass("selected");
			}
			else {
				jQuery(this).removeClass("selected");
			}
		});

// show / hide additional gallery options depending on preselected values
		if( jQuery("#file_gallery_orderby").val() !== "default" )
		{
			if( jQuery("#file_gallery_orderby").val() === "rand" ) {
				jQuery("#file_gallery_order").css({display : "none"});
			}
			else {
				jQuery("#file_gallery_order").css({display : "inline"});
			}
		}
		else
		{
			jQuery("#file_gallery_order").css({display : "none"});
		}




// WPML
		if( jQuery("#icl_div").length > 0 )
		{
			if( jQuery("#icl_translations_table").length > 0 )
			{
				jQuery("#icl_translations_table a[title=edit]").each(function()
				{
					var fg_icl_trans_id = jQuery(this).attr('href').match(/post=([\d]+)&/) || false;

					if( fg_icl_trans_id ) {
						fg_icl_trans_id = parseInt(fg_icl_trans_id.pop(), 10);
					}
		
					if( ! isNaN(fg_icl_trans_id) )
					{
						jQuery(this).after('<a title="' + file_gallery.L10n.copy_all_from_translation + '" href="#" id="copy-from-translation-' + fg_icl_trans_id + '"><img src="' + file_gallery.options.file_gallery_url + '/images/famfamfam_silk/image_add.png" alt="' + file_gallery.L10n.copy_all_from_translation + '" /></a>');
		
						jQuery("#copy-from-translation-" + fg_icl_trans_id).bind("click", function()
						{
							if( confirm(file_gallery.L10n.copy_all_from_translation_) ) {
								file_gallery.copy_all_attachments(fg_icl_trans_id);
							}
		
							return false;
						});
					}
				});
			}
			else
			{
				var fg_icl_ori_id = jQuery("#icl_translation_of option:selected").val();
		
				if( fg_icl_ori_id !== void 0 )
				{
					jQuery("#icl_div .inside").append('<a href="#" id="file_gallery_copy_from_wmpl_original">' + file_gallery.L10n.copy_all_from_original + '</a>');
		
					jQuery("#file_gallery_copy_from_wmpl_original").bind("click", function()
					{
						if( confirm(file_gallery.L10n.copy_all_from_original_) ) {
							file_gallery.copy_all_attachments(fg_icl_ori_id);
						}
		
						return false;
					});
				}
			}
		}





tinymce_update_gallery_data : function( serial )
	{
		var editor = this.tinymce_get_editor();

		if( editor !== null )
		{
			if( ! this.tmp[editor.id] ) {
				this.tmp[editor.id] = 0;
			}

			if( ! this.last_clicked_gallery[editor.id] ) {
				this.last_clicked_gallery[editor.id] = "";
			}

			if( editor && this.gallery_image_clicked[editor.id] )
			{
				var view = editor.getDoc().getElementById(this.last_clicked_gallery[editor.id]);
				editor.selection.select(view);
				editor.selection.setContent(serial);
				this.tmp[editor.id]++;
				jQuery('#file_gallery_response').html(this.L10n.gallery_updated).show().fadeOut(1000);
			}
		}
	},


// sets up the file gallery options when clicked on a gallery already inserted into visual editor
	tinymce_gallery : function( shortcode )
	{
		if( shortcode === void 0 ) {
			return;
		}

		var attrs = this.parseShortcode(shortcode),
			attachment_ids,
			this_post_attachment_ids = [],
			external_attachments = [],
			i = 0;

		_.defaults(attrs, file_gallery.shortcodeDefaults);

		if( attrs.link === "attachment" ) {
			attrs.link = "post";
		}

		if( attrs.link !== "none" && attrs.link !== "file" && attrs.link !== "parent_post" && attrs.link !== "post" )
		{
			attrs.external_url = decodeURIComponent(attrs.link);
			attrs.link = "external_url";
		}

		if( attrs.orderby == "menu_order ID" ) {
			attrs.orderby = "default";
		}

		if( attrs.id === file_gallery.shortcodeDefaults.id ) {
			attrs.id = "";
		}

		jQuery("#file_gallery_postid").val(attrs.id);
		jQuery("#file_gallery_size").val(attrs.size);
		jQuery("#file_gallery_linkto").val(attrs.link);
		jQuery("#file_gallery_linkrel").val(attrs.rel);
		jQuery("#file_gallery_linksize").val(attrs.linksize);
		jQuery("#file_gallery_external_url").val(attrs.external_url);
		jQuery("#file_gallery_template").val(attrs.template);
		jQuery("#file_gallery_order").val(attrs.order);
		jQuery("#file_gallery_orderby").val(attrs.orderby);
		jQuery("#file_gallery_linkclass").val(attrs.linkclass);
		jQuery("#file_gallery_imageclass").val(attrs.imageclass);
		jQuery("#file_gallery_galleryclass").val(attrs.galleryclass);
		jQuery("#file_gallery_mimetype").val(attrs.mimetype);
		jQuery("#file_gallery_limit").val(attrs.limit);
		jQuery("#file_gallery_offset").val(attrs.offset);
		jQuery("#file_gallery_paginate").val(attrs.paginate);
		jQuery("#file_gallery_columns").val(attrs.columns);

		if( attrs.rel && attrs.rel !== "true" && attrs.rel !== "false" )
		{
			jQuery("#file_gallery_linkrel").val("true");
			jQuery("#file_gallery_linkrel_custom").val( attrs.rel.replace(/\\\[/, '[').replace(/\\\]/, ']') );
		}

		if( attrs.tags )
		{
			jQuery("#fg_gallery_tags").val(attrs.tags);
			jQuery("#files_or_tags").val("tags");
			this.files_or_tags();
			jQuery("#fg_gallery_tags_from").prop("checked", ! tags_from);
			jQuery("#file_gallery_toggler").show();
		}
		else
		{
			jQuery("#files_or_tags").val("files");
			this.files_or_tags();
		}

		attachment_ids = attrs.include ? attrs.include.split(",") : "all";

		if( this.options.num_attachments > 0 )
		{
			this.uncheckAll( false );

			jQuery("#fg_container .sortableitem .checker").map(function()
			{
				var id = jQuery(this).attr("id").replace("att-chk-", "");

				this_post_attachment_ids.push(id);

				if( attachment_ids === "all" || attachment_ids.indexOf(id) > -1 )
				{
					jQuery(this).parents(".sortableitem").addClass("selected");
					this.checked = true;
					return;
				}
			});

			for( i; i < attachment_ids.length; i++ )
			{
				if( this_post_attachment_ids.indexOf(attachment_ids[i]) === -1 ) {
					external_attachments.push(attachment_ids[i]);
				}
			}

			this.serialize("tinymce_gallery", external_attachments);
		}
		else
		{
			this.serialize("tinymce_gallery", attachment_ids);
		}
	},


//collapses the selection if gallery placeholder is selected
	tinymce_deselect : function( force )
	{
		if( force === void 0 ) {
			force = false;
		}

		var ed = this.tinymce_get_editor();

		if( ! ed || (ed.id && this.gallery_image_clicked[ed.id] === false && force === false) ) {
			return;
		}

		if( ed !== void 0 )
		{
			if( ed.selection ) {
				ed.selection.collapse(false);
			}

			tinymce.execCommand("mceRepaint", false, ed.id);
			tinymce.execCommand("mceFocus", false, ed.id);
		}
	},


send_to_editor : function( id )
	{
		var gallery_data = "",
			attachment_id = "";
		
		if( id === "file_gallery_send_gallery_legend" )
		{
			gallery_data = this.currentShortcode;
			
			if( this.currentShortcode === "" || this.currentShortcode === void 0 ) {
				return false;
			}
			
			send_to_editor(this.currentShortcode);
			console.log("sent: ", this.currentShortcode);
			this.uncheckAll();
		}
		else
		{
			attachment_id = this.checkedItems;
	
			if( attachment_id === "" || attachment_id === void 0 ) {
				return false;
			}
			
			var data = {
				action : "file_gallery_send_single",
				attachment_id : attachment_id,
				size : jQuery("#file_gallery_single_size").val(),
				linkto : jQuery("#file_gallery_single_linkto").val(),
				external_url  : jQuery("#file_gallery_single_external_url").val(),
				linkclass : jQuery("#file_gallery_single_linkclass").val(),
				imageclass : jQuery("#file_gallery_single_imageclass").val(),
				align : jQuery("#file_gallery_single_align").val(),
				post_id : jQuery("#post_ID").val(),
				caption : jQuery("#file_gallery_single_caption:checked").length ? true : false,
				_ajax_nonce : this.options.file_gallery_nonce
			};
			
			$.post
			(
				ajaxurl,
				data,
				function( single_data )
				{
					send_to_editor(single_data);
					this.uncheckAll();
				},
				"html"
			);
		}
	},

	edit : function( attachment_id )
	{
		if( attachment_id === "" || attachment_id === void 0 ) {
			return false;
		}
		
		this.options.file_gallery_mode = "edit";
		
		var data = {
			action : "file_gallery_edit_attachment",
			post_id : jQuery("#post_ID").val(),
			attachment_id : attachment_id,
			attachment_order : this.allItems,
			checked_attachments : this.checkedItems,
			_ajax_nonce : this.options.file_gallery_nonce
		};
		
		jQuery("#fg_container")
			.html("<p class=\"loading_image\"><img src=\"" + this.options.file_gallery_url + "/images/ajax-loader.gif\" alt=\"" + this.L10n.loading_attachment_data + "\" /><br />" + this.L10n.loading_attachment_data + "</p>");
		
		$.post
		(
			ajaxurl,
			data,
			function(response)
			{
				jQuery('#fg_container').html(response);
				file_gallery.tinymce_deselect();
			},
			"html"
		);
		
		return false;
	},

	zoom : function( element )
	{
		var image = new Image();
	
		jQuery("#file_gallery_image_dialog")
			.html('<p class="loading_image"><img src="' + this.options.file_gallery_url + '/images/ajax-loader.gif" alt="' + this.L10n.loading + '" />	</p>')
			.dialog( 'option', 'width',  'auto' )
			.dialog( 'option', 'height', 'auto' )
			.dialog("open");
		
		jQuery(image).bind("load", function()
		{
			var ih = this.height,
				iw = this.width,
				src = this.src,
				ratio = iw/ih,
				ww = jQuery(window).width(),
				wh = jQuery(window).height();
			
			if( ih > (wh - 50) )
			{
				ih = wh - 50;
				iw = ratio * ih;
			}
			else if( iw > (ww - 50) )
			{
				iw = ww - 50;
				ih = ratio * iw;
			}
			
			jQuery("#file_gallery_image_dialog")
				.html('<img src="' + src + '" width="' + iw + '" height="' + ih + '" alt="image" />')
				.dialog( 'option', 'position', 'center');
		});
		
		image.src = jQuery(element).attr("href");
		
		return false;
	},

	copy_all_attachments : function(from_id)
	{
		if( from_id === "" || from_id === void 0 ) {
			return false;
		}
		
		var admin_url = ajaxurl.split("/admin-ajax.php").shift();
		
		this.options.file_gallery_mode = "list";
		
		var data = {
			action : "file_gallery_copy_all_attachments",
			to_id : jQuery("#post_ID").val(),
			from_id : from_id,
			_ajax_nonce : this.options.file_gallery_nonce
		};
		
		jQuery('#file_gallery_response')
			.stop().fadeTo(0, 1)
			.html('<img src="' + admin_url + '/images/loading.gif" width="16" height="16" alt="' + this.L10n.loading + '" id="fg_loading_on_bar" />').show();
		
		$.post
		(
			ajaxurl,
			data,
			function(response)
			{
				jQuery("#file_gallery_response").stop().html(response).show().css({opacity : 1}).fadeOut(7500);
				file_gallery.init("refreshed");
			},
			"html"
		);
	},

	

	

	serialize : function( internal_event, external_attachments )
	{
		var serial = "",
			id = "",
			size = "",
			linkto = "",
			linkrel = "",
			linksize = "",
			linkto_val = jQuery("#file_gallery_linkto").val(),
			external_url = jQuery("#file_gallery_external_url").val(),
			template = "",
			order = "",
			orderby = "",
			linkclass = "",
			imageclass = "",
			galleryclass = "",
			mimetype = "",
			limit = "",
			columns = "",
			tags = "",
			tags_from = "",
			ctlen = "",
			ct = "",
			ns = "",
			nn = "",
			copies = "",
			originals = "",
			file_gallery_order = "",
			file_gallery_orderby = "",
			include_attribute_name = "ids";

		if( jQuery("#file_gallery_linkrel_custom").val() !== "" && jQuery("#file_gallery_linkrel_custom").val() !== void 0 )
		{
			jQuery("#file_gallery_linkrel_custom").val( jQuery("#file_gallery_linkrel_custom").val().replace(/\[/, '').replace(/\]/, '') );
			linkrel = ' rel="' + jQuery("#file_gallery_linkrel_custom").val() + '"';
		}
		else if( jQuery("#file_gallery_linkrel").val() === "false" )
		{
			linkrel = ' rel="false"';
		}

		if( linkto_val === "external_url" ) {
			linkto_val = encodeURIComponent(external_url);
		}


		// tags
		if( jQuery("#fg_gallery_tags").length > 0 )
		{
			if( jQuery("#fg_gallery_tags").val() === void 0 ) {
				jQuery("#fg_gallery_tags").val("");
			}

			tags = jQuery("#fg_gallery_tags").val();
			tags_from = jQuery("#fg_gallery_tags_from").prop("checked");

			tags = tags.replace(/\s+/g, " ").replace(/\s+,/g, ",").replace(/,+\s*/g, ",");

			ctlen = tags.length;

			if( tags[0] && tags[0] === "," ) {
				tags = tags.substring(1);
			}

			if( tags[ctlen-2] && tags[ctlen-2] === "," ) {
				tags = tags.substring(0, ctlen-1);
			}

			jQuery("#fg_gallery_tags").val(tags);

			jQuery(".fg_insert_tag").each( function()
			{
				ct = "," + jQuery("#fg_gallery_tags").val() + ",";
				ns = "," + jQuery(this).attr("name") + ",";
				nn = "," + jQuery(this).html() + ",";

				if ( ct.search(ns) > -1 || ct.search(nn) > -1 ) {
					jQuery(this).addClass("selected");
				}
				else {
					jQuery(this).removeClass("selected");
				}
			});
		}

		if( this.options.num_attachments > 0 ) {
			serial = jQuery("#file_gallery_list").sortable("serialize");
		}

		serial = serial.toString().replace(/image\[\]=/g, '').replace(/&/g, ',').replace(/,+/g, ',');
		this.allItems = serial;

		// get checked items
		serial = this.map("checked", serial);
		this.checkedItems = serial;

		// get checked copies
		copies = this.map("copy", serial);
		this.copies = copies;

		// get checked originals
		originals = this.map("has_copies", serial);
		this.originals = originals;

		if( this.originals === "" && this.copies === "" ) {
			jQuery("#file_gallery_delete_what").val("all");
		}

		file_gallery_order = jQuery("#file_gallery_order");
		file_gallery_orderby = jQuery("#file_gallery_orderby");

		order = ' order="' + file_gallery_order.val() + '"';

		if( file_gallery_orderby.val() !== "default" )
		{
			if( file_gallery_orderby.val() === "rand" )
			{
				file_gallery_order.hide();
				order = "";
			}
			else
			{
				file_gallery_order.css({display : "inline"});
			}

			orderby = ' orderby="' + file_gallery_orderby.val() + '"';
		}
		else
		{
			file_gallery_order.hide();
			order = "";
			orderby = "";
		}

		if( jQuery("#file_gallery_linkto").val() === "external_url" ) {
			jQuery("#file_gallery_external_url_label").show();
		}
		else {
			jQuery("#file_gallery_external_url_label").hide();
		}

		if( jQuery("#file_gallery_single_linkto").val() === "external_url" ) {
			jQuery("#file_gallery_single_external_url_label").show();
		}
		else {
			jQuery("#file_gallery_single_external_url_label").hide();
		}

		if( jQuery("#file_gallery_linkto").val() === "none" ) {
			jQuery("#file_gallery_linkclass_label").hide();
		}
		else {
			jQuery("#file_gallery_linkclass_label").show();
		}

		if( jQuery("#file_gallery_single_linkto").val() === "none" ) {
			jQuery("#file_gallery_single_linkclass_label").hide();
		}
		else {
			jQuery("#file_gallery_single_linkclass_label").show();
		}

		if( parseInt(jQuery("#file_gallery_limit").val(), 10) > 0 ) {
			jQuery("#file_gallery_paginate_label").show();
		}
		else {
			jQuery("#file_gallery_paginate_label").hide();
		}

		if( jQuery("#file_gallery_linkto").val() === "file" || jQuery("#file_gallery_linkto").val() === "external_url" )
		{
			jQuery("#file_gallery_linksize_label").show();
			jQuery("#file_gallery_linkrel_custom_label").show();

			if( jQuery("#file_gallery_linksize").val() === "full" ) {
				linksize = ' link_size="' + jQuery("#file_gallery_linksize").val() + '"';
			}
		}
		else
		{
			jQuery("#file_gallery_linksize_label").hide();
			linksize = "";
		}

		if( tags_from ) {
			tags_from = "";
		}
		else {
			tags_from = ' tags_from="all"';
		}

		if( external_attachments !== void 0 ) {

			if( serial === "" ) {
				serial = external_attachments.join(",");
			}
			else {
				serial += "," + external_attachments.join(",");
			}
		}

		if( tags !== "" ) {
			serial = '[gallery tags="' + tags + '"' + tags_from;
		}
		else if( serial !== "" && this.is_all_checked() === false ) {
			serial = '[gallery ' + include_attribute_name + '="' + serial + '"';
		}
		else {
			serial = '[gallery';
		}

		if( jQuery("#file_gallery_size").val() !== "thumbnail" ) {
			size = ' size="' + jQuery("#file_gallery_size").val() + '"';
		}

		if( jQuery("#file_gallery_linkto").val() !== "attachment" ) {
			linkto = ' link="' + linkto_val + '"';
		}

		if( jQuery("#file_gallery_template").val() !== "default" ) {
			template = ' template="' + jQuery("#file_gallery_template").val() + '"';
		}

		if( jQuery("#file_gallery_linkclass").val() !== "" && jQuery("#file_gallery_linkto").val() !== "none" ) {
			linkclass = ' linkclass="' + jQuery("#file_gallery_linkclass").val() + '"';
		}

		if( jQuery("#file_gallery_imageclass").val() !== "" ) {
			imageclass = ' imageclass="' + jQuery("#file_gallery_imageclass").val() + '"';
		}

		if( jQuery("#file_gallery_galleryclass").val() !== "" ) {
			galleryclass = ' galleryclass="' + jQuery("#file_gallery_galleryclass").val() + '"';
		}

		if( jQuery("#file_gallery_mimetype").val() !== "" ) {
			mimetype = ' mimetype="' + jQuery("#file_gallery_mimetype").val() + '"';
		}

		if( parseInt(jQuery("#file_gallery_limit").val(), 10) > 0 )
		{
			limit = ' limit="' + jQuery("#file_gallery_limit").val() + '"';

			if( jQuery("#file_gallery_paginate").val() === "true" ) {
				limit += ' paginate="true"';
			}
		}

		if( parseInt(jQuery("#file_gallery_offset").val(), 10) > 0 ) {
			limit += ' offset="' + jQuery("#file_gallery_offset").val() + '"';
		}

		if( jQuery("#file_gallery_postid").val() !== "" ) {
			id = ' id="' + jQuery("#file_gallery_postid").val() + '"';
		}

		var cols = jQuery("#file_gallery_columns").val();

		if( cols !== "" && cols !== "3" ) {
			columns = ' columns="' + cols + '"';
		}

		serial += id + size + linkto + linksize + linkclass + imageclass + galleryclass + mimetype + limit + order + orderby + template + columns + linkrel + "]\n";

		this.currentShortcode = serial;

		if( internal_event === "normal" ) {
			this.tinymce_update_gallery_data(serial);
		}
	},

	

	detach_attachments : function( attachment_ids, message )
	{
		if( attachment_ids === "" || attachment_ids === void 0 ) {
			return false;
		}
		
		if( message === void 0 ) {
			message = false;
		}
	
		if( (message !== false && confirm(message)) || message === false )
		{
			var attachment_count = 1,
				a = this.L10n.detaching_attachment,
				data;
			
			if( attachment_ids.indexOf(/,/) > -1 ) {
				attachment_count = attachment_ids.split(",").length;
			}
	
			if( attachment_count > 1 ) {
				a = this.L10n.detaching_attachments;
			}
	
			jQuery("#fg_container")
				.css({"height" : jQuery("#fg_container").height()})
				.html('<p class="loading_image"><img src="' + this.options.file_gallery_url + '/images/ajax-loader.gif" alt="' + this.L10n.loading + '" /><br />' + a + '</p>');
	
			data = {
				post_id : jQuery("#post_ID").val(),
				action : "file_gallery_main_detach",
				attachment_ids : attachment_ids,
				attachment_order : this.allItems,
				checked_attachments : this.checkedItems,
				_ajax_nonce : this.options.file_gallery_nonce
			};
			
			$.post
			(
				ajaxurl,
				data,
				function(response)
				{
					jQuery("#fg_container")
						.html(response)
						.css({height : "auto"});
					
					jQuery("#file_gallery_response")
						.html(jQuery("#file_gallery_response_inner").html())
						.stop().fadeTo(0, 1).show().fadeOut(7500);
					
					file_gallery.setup();
				},
				"html"
			);
		}
		
		return false;
	},






/**
	 * handles adding and removing of tags that will be used
	 * in gallery shortcode instead of attachment_ids,
	 * both when edited by hand and when a tag link is clicked
	 */
	add_remove_tags : function( tag )
	{
		var current_tags = jQuery("#fg_gallery_tags").val(),
			newtag_slug = jQuery(tag).attr("name"),
			newtag_name = jQuery(tag).html(),
			ct = "," + current_tags + ",",
			ns = "," + newtag_slug  + ",",
			nn = "," + newtag_name  + ",",
			ctlen = 0;

		if( ct.indexOf(ns) === -1 && ct.indexOf(nn) === -1 )
		{
			jQuery(tag).addClass("selected");

			if( current_tags !== "" ) {
				newtag_slug = "," + newtag_slug;
			}

			current_tags += newtag_slug;
		}
		else
		{
			jQuery(tag).removeClass("selected");
	
			if( ct.indexOf(ns) > -1 ) {
				current_tags = ct.replace(ns, ",");
			}
			else if( ct.indexOf(nn) > -1 ) {
				current_tags = ct.replace(nn, ",");
			}
		}
		
		// clean up whitespace
		current_tags = current_tags.replace(/\s+/g, " ").replace(/\s+,/g, ",").replace(/,+\s*/g, ",");
	
		ctlen = current_tags.length;
		
		if( current_tags[0] !== void 0 && current_tags[0] === "," ) {
			current_tags = current_tags.substr(1);
		}
		
		if( current_tags[ctlen-2] !== void 0 && current_tags[ctlen-2] === "," ) {
			current_tags = current_tags.substr(0, ctlen-2);
		}
		
		jQuery("#fg_gallery_tags").val(current_tags);
		
		this.serialize();
		
		return false;
	},


	/**
	 * maps attachment data (checked, has copies, is a copy)
	 */
	map : function(what, data)
	{
		data = data.split(",");
		var dl = data.length;
		
		if( what === "checked" )
		{
			while( dl > 0 )
			{
				if( jQuery("#att-chk-" + data[dl-1]).prop("checked") === false ) {
					delete data[dl-1];
				}
				
				dl--;
			}
		}
		else if( what === "copy" || what === "has_copies" )
		{
			while( dl > 0 )
			{
				if( jQuery("#image-" + data[dl-1]).hasClass(what) === false ) {
					delete data[dl-1];
				}
				
				dl--;
			}
		}
		else
		{
			return false;
		}

		data = '"' + data.toString() + '"';

		return data.replace(/,+/g, ',').replace(/",/g, '').replace(/,"/g, '').replace(/"/g, '');
	},


	/**
	 * displays attachments thumbnails or the tag list
	 */
	files_or_tags : function( do_switch )
	{
		var el = jQuery("#files_or_tags");
		
		if( do_switch === true )
		{
			if( el.val() === "files" ) {
				el.val("tags");
			}
			else {
				el.val("files");
			}
		}
		else
		{
			do_switch = false;
		}
		
		var which = el.val();

		if( which === "files" || which === void 0 )
		{
			jQuery("#file_gallery_switch_to_tags").attr("value", this.L10n.switch_to_tags);

			jQuery("#fg_gallery_tags_container, #file_gallery_tag_list").fadeOut(250, function(){
				jQuery("#file_gallery_attachment_list").fadeIn();
			});

			jQuery("#fg_gallery_tags").val("");
			
			el.val("tags");
		}
		else if( which === "tags" )
		{
			jQuery("#file_gallery_switch_to_tags").attr("value", this.L10n.switch_to_files);

			jQuery("#file_gallery_attachment_list").fadeOut(250, function() {
				jQuery("#fg_gallery_tags_container, #file_gallery_tag_list").fadeIn();
			});
			
			el.val("files");
		}
		
		if( do_switch === false ) {
			this.serialize("files_or_tags");
		}
	},


	/**
	 * saves attachment metadata
	 */
	save_attachment : function( attachment_data )
	{
		this.options.file_gallery_mode = "list";
		
		jQuery("#fg_container")
			.html("<p class=\"loading_image\"><img src=\"" + this.options.file_gallery_url + "/images/ajax-loader.gif\" alt=\"" + this.L10n.saving_attachment_data + "\" /><br />" + this.L10n.saving_attachment_data + "</p>");
		
		$.post
		(
			ajaxurl,
			{
				post_id : jQuery("#post_ID").val(),
				attachment_id : attachment_data.id,
				action : "file_gallery_main_update",
				post_alt : attachment_data.alt,
				post_title : attachment_data.title,
				post_content : attachment_data.content,
				post_excerpt : attachment_data.excerpt,
				tax_input : attachment_data.tax_input,
				menu_order : attachment_data.menu_order,
				custom_fields : attachment_data.custom_fields,
				attachment_order : jQuery("#attachment_order").val(),
				checked_attachments : jQuery("#checked_attachments").val(),
				_ajax_nonce : this.options.file_gallery_nonce
			},
			function(response)
			{
				jQuery("#fg_container").html(response).css({height : "auto"});
				jQuery("#file_gallery_response").html(jQuery("#file_gallery_response_inner").html()).stop().fadeTo(0, 1).show().fadeOut(7500);
				
				file_gallery.setup();
			},
			"html"
		);
		
		return false;
	},


	/**
	 * deletes checked attachments
	 */
	delete_attachments : function( attachment_ids, message )
	{
		var delete_what = jQuery("#file_gallery_delete_what"),
			delete_what_val = delete_what.val(),
			attachment_count = 1,
			originals,
			copies,
			data,
			a;
		
		if( attachment_ids === "" || attachment_ids === void 0 || delete_what_val === "" || delete_what_val === void 0 ) {
			return false;
		}
		
		if( message === void 0 ) {
			message = false;
		}
	
		if( (message !== false && confirm(message)) || message === false )
		{
			if( attachment_ids.indexOf(/,/) > -1 ) {
				attachment_count = attachment_ids.split(",").length;
			}
			
			a = attachment_count > 1 ? this.L10n.deleting_attachments : this.L10n.deleting_attachment;
			
			if( attachment_count < 2 )
			{
				if( jQuery("#image-" + attachment_ids).hasClass("copy") ) {
					this.copies = attachment_ids;
				}
				else if( jQuery("#image-" + attachment_ids).hasClass("has_copies") ) {
					this.originals = attachment_ids;
				}
			}
			
			copies = this.copies;
			originals = this.originals;
			
			if( copies === void 0 ) {
				copies = "";
			}
			
			if( originals === void 0 ) {
				originals = "";
			}
				
			jQuery("#fg_container")
				.css({height : jQuery("#fg_container").height()})
				.html('<p class="loading_image"><img src="' + this.options.file_gallery_url + '/images/ajax-loader.gif" alt="' + this.L10n.loading + '" /><br />' + a + '</p>');
			
			data = {
				post_id : jQuery("#post_ID").val(),
				action : "file_gallery_main_delete",
				attachment_ids : attachment_ids,
				attachment_order : this.allItems,
				checked_attachments : this.checkedItems,
				copies : copies,
				originals : originals,
				delete_what : delete_what_val,
				_ajax_nonce : this.options.file_gallery_nonce
			};
			
			$.post
			(
				ajaxurl,
				data,
				function(response)
				{
					jQuery('#fg_container').html(response).css({height : "auto"});

					jQuery('#file_gallery_response')
						.html(jQuery("#file_gallery_response_inner").html())
						.stop().fadeTo(0, 1).css({display : "block"}).fadeOut(7500);
					
					file_gallery.setup();
				},
				"html"
			);
		}
		
		delete_what.val("data_only");
	}























