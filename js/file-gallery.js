var file_gallery =
{
	L10n : file_gallery_L10n,
	options : file_gallery_options
};

// add access and prop for older versions of jQuery
if( typeof(jQuery.access) !== "function" )
{
	jQuery.extend({
		access: function( elems, key, value, exec, fn, pass ) {
			var length = elems.length;
	
			// Setting many attributes
			if ( typeof key === "object" ) {
				for ( var k in key ) {
					jQuery.access( elems, k, key[k], exec, fn, value );
				}
				return elems;
			}
	
			// Setting one attribute
			if ( value !== undefined ) {
				// Optionally, function values get executed if exec is true
				exec = !pass && exec && jQuery.isFunction(value);
	
				for ( var i = 0; i < length; i++ ) {
					fn( elems[i], key, exec ? value.call( elems[i], i, fn( elems[i], key ) ) : value, pass );
				}
	
				return elems;
			}
	
			// Getting an attribute
			return length ? fn( elems[0], key ) : undefined;
		}
	});
}
	
if( typeof(jQuery.fn.prop) !== "function" )
{
	jQuery.fn.extend({
		prop: function( name, value ) {
			
			if( "checked" === name || "selected" === name || "disabled" === name || "readonly" === name )
			{
				if( true === value ) {
					value = name;
				}
				else if( false === value ) {
					value = "";
				}
			}
			
			return jQuery.access( this, name, value, true, jQuery.attr );
		}
	});
}

jQuery(document).ready(function($)
{	
	$.extend(file_gallery,
	{
		tmp : [],
		last_clicked_gallery : [],
		gallery_image_clicked : [],
		refreshed : false,
		upload_inside : false,
		uploader_dragdrop : true,
		initialized : false,

		
		tinymce_get_editor: function()
		{
			if( "undefined" === typeof(wpActiveEditor) )
				wpActiveEditor = "content";
			
			if( "undefined" !== typeof(tinymce) )
				return tinymce.EditorManager.get(wpActiveEditor);
			
			return null;
		},
		
		
		tinymce_remove_upload_iframe : function()
		{
			var ed = file_gallery.tinymce_get_editor();
			
			$( ed.getBody() ).find("#file_gallery_tinymce_upload").remove();
		},


		/**
		 * updates the contents of [gallery] shortcode
		 */
		tinymce_maybe_update_gallery_data : function( serial )
		{
			var ed = file_gallery.tinymce_get_editor();

			// update tinymce gallery
			if( ed && file_gallery.gallery_image_clicked[ed.id] )
			{
				if( "" == ed.selection.getContent() )
				{
					ed.focus();
					ed.selection.select( ed.getDoc().getElementById( file_gallery.last_clicked_gallery[ed.id] ) ) ;
					tinymce.execCommand("mceFocus", false, ed.id);
				}

				if( "" != ed.selection.getContent() )
				{
					// skips setContent for webkit browsers if tinyMCE version is below 3.3.6
					if( (! $.browser.webkit && ! $.browser.safari) || (3 <= parseFloat(tinymce.majorVersion) && 3.6 <= parseFloat(tinymce.minorVersion)) )
					{
						var ed = file_gallery.tinymce_get_editor(),
							new_gallery_id = "file_gallery_tmp_" + file_gallery.tmp[ed.id];
							new_content = serial.replace(/\[gallery([^\]]*)\]/g, function(a,b)
							{
								return "<img src='" + tinymce.baseURL + "/plugins/wpgallery/img/t.gif' class='wpGallery mceItem' title='gallery" + tinymce.DOM.encode(b).replace(/\[/, '\[').replace(/\]/, '\]') + "' id='" + new_gallery_id + "' />";
							});

						ed.focus();
							ed.selection.select( ed.getDoc().getElementById(file_gallery.last_clicked_gallery[ed.id]) );
								ed.selection.setContent( new_content );
							ed.selection.select( ed.getDoc().getElementById(new_gallery_id) );
						tinymce.execCommand( "mceFocus", false, ed.id );
						
						file_gallery.last_clicked_gallery[ed.id] = new_gallery_id;
						file_gallery.tmp[ed.id]++;
					}
					
					$('#file_gallery_response').html(file_gallery.L10n.gallery_updated).show().fadeOut(1000);
				}
			}
		},


		/**
		 * sets up the file gallery options when clicked on a gallery already
		 * inserted into visual editor
		 */
		tinymce_gallery : function( title )
		{
			if( typeof title === "undefined" ) {
				title = $("#data_collector").val();
			}
			
			var opt = title.replace("gallery", "").replace("attachment_ids", "ids"), // gets gallery options from image title
				attachment_ids = opt.match(/ids=['"]([0-9,]+)['"]/),
				attachment_includes = opt.match(/include=['"]([0-9,]+)['"]/),
				post_id = opt.match(/id=['"](\d+)['"]/),
				size = opt.match(/(^|[\s]+)size=['"]([^'"]+)['"]/i),
				linkto = opt.match(/link=['"]([^'"]+)['"]/i),
				thelink = linkto ? linkto[1] : "attachment",
				linkrel = opt.match(/rel=['"]([^'"]+)['"]/i),
				linksize = opt.match(/link_size=['"]([^'"]+)['"]/i),
				external_url = '',
				template = opt.match(/template=['"]([^'"]+)['"]/i),
				order = opt.match(/order=['"]([^'"]+)['"]/i),
				orderby = opt.match(/orderby=['"]([^'"]+)['"]/i),
				linkclass = opt.match(/linkclass=['"]([^'"]+)['"]/i),
				imageclass = opt.match(/imageclass=['"]([^'"]+)['"]/i),
				galleryclass = opt.match(/galleryclass=['"]([^'"]+)['"]/i),
				mimetype = opt.match(/mimetype=['"]([^'"]+)['"]/i),
				limit = opt.match(/limit=['"](\d+)['"]/),
				offset = opt.match(/offset=['"](\d+)['"]/),
				paginate = opt.match(/paginate=['"]([^'"]+)['"]/i),
				columns = opt.match(/columns=['"](\d+)['"]/),
				tags = opt.match(/tags=['"]([^'"]+)['"]/i),
				tags_from = opt.match(/tags_from=['"]([^'"]+)['"]/i),
				this_post_attachment_ids = [],
				external_attachments = [],
				i = 0;

			if( thelink === "attachment" && file_gallery_options.wp_version >= 3.5 ) {
				thelink = "post";
			}

			if( linkto && thelink !== "none" && thelink !== "file" && thelink !== "parent_post" && thelink !== "post" )
			{
				external_url = decodeURIComponent(thelink);
				thelink = "external_url";
			}
		
			$("#file_gallery_postid").val( post_id ? post_id[1] : ""  );
			$("#file_gallery_size").val(size ? size[1] : "thumbnail" );
			$("#file_gallery_linkto").val( thelink );
			$("#file_gallery_linkrel").val(linkrel ? linkrel[1] : "true" );
			$("#file_gallery_linksize").val(linksize ? linksize[1] : "full" );
			$("#file_gallery_external_url").val( external_url );
			$("#file_gallery_template").val(template ? template[1] : "default" );
			$("#file_gallery_order").val(order ? order[1] : "ASC" );
			$("#file_gallery_orderby").val(orderby ? orderby[1] : "file gallery" );
			$("#file_gallery_linkclass").val(linkclass ? linkclass[1] : "" );
			$("#file_gallery_imageclass").val(imageclass ? imageclass[1] : "" );
			$("#file_gallery_galleryclass").val(galleryclass ? galleryclass[1] : "" );
			$("#file_gallery_mimetype").val(mimetype ? mimetype[1] : "" );
			$("#file_gallery_limit").val(limit ? limit[1] : "" );
			$("#file_gallery_offset").val(offset ? offset[1] : "" );
			$("#file_gallery_paginate").val(paginate ? paginate[1] : "false" );
			$("#file_gallery_columns").val(columns ? columns[1] : "3" );
			
			if( linkrel && "true" != linkrel[1] && "false" != linkrel[1])
			{
				$("#file_gallery_linkrel").val("true");
				$("#file_gallery_linkrel_custom").val( linkrel[1].replace(/\\\[/, '[').replace(/\\\]/, ']') );
			}
			
			if( tags )
			{
				$("#fg_gallery_tags").val(tags[1]);
				$("#files_or_tags").val("tags");
				file_gallery.files_or_tags( false );
				
				if( tags_from )
					$("#fg_gallery_tags_from").prop("checked", false);
				else
					$("#fg_gallery_tags_from").prop("checked", true);
				
				$("#file_gallery_toggler").show();
			}
			else
			{
				$("#files_or_tags").val("files");
				file_gallery.files_or_tags( false );
			}

			if( null !== attachment_ids ) {
				attachment_ids = attachment_ids[1].split(",");
			}
			else if( null !== attachment_includes ) {
				attachment_ids = attachment_includes[1].split(",");
			}
			else {
				attachment_ids = "all";
			}
			
			if( 0 < file_gallery.options.num_attachments )
			{
				$("#file_gallery_uncheck_all").trigger("click_tinymce_gallery");
				
				$("#fg_container .sortableitem .checker").map(function()
				{
					var id = $(this).attr("id").replace("att-chk-", "");
					
					this_post_attachment_ids.push(id);
					
					if( "all" === attachment_ids || -1 < attachment_ids.indexOf(id) )
					{
						$(this).parents(".sortableitem").addClass("selected");
						return this.checked = true;
					}
				});
				
				for( i; i < attachment_ids.length; i++ )
				{
					if( this_post_attachment_ids.indexOf(attachment_ids[i]) === -1 ) {
						external_attachments.push(attachment_ids[i]);
					}
				}
				
				file_gallery.serialize("tinymce_gallery", external_attachments);
			}
			else
			{
				file_gallery.serialize("tinymce_gallery", attachment_ids);
			}
		},


		/**
		 * collapses selection if gallery placeholder is clicked
		 */
		tinymce_deselect : function( force )
		{
			if( "undefined" === typeof(force) )
				force = false;

			var ed = file_gallery.tinymce_get_editor();

			if( "undefined" !== ed || (ed.id && false === file_gallery.gallery_image_clicked[ed.id] && false === force) )
				return;

			if( force && 0 < $("#TB_overlay").length )
				return setTimeout( function(){ file_gallery.tinymce_deselect( force ); }, 100 );
			
			if( "undefined" !== typeof(ed) )
			{
				if( ed.selection )
					ed.selection.collapse(false);
			
				tinymce.execCommand("mceRepaint", false, ed.id);
				tinymce.execCommand("mceFocus", false, ed.id);
			}
		},


		/**
		 * checks if all the attachments are, eh, checked...
		 */
		is_all_checked : function()
		{
			var all_checked = true;
			
			$("#fg_container .sortableitem .checker").map(function()
			{
				if( ! this.checked )
				{
					all_checked = false;
					// return as soon as an unchecked item is found
					return;
				}
			});
			
			return all_checked;
		},


		/**
		 * loads main file gallery data via ajax
		 */
		init : function( response_message )
		{
			var tags_from = $("#fg_gallery_tags_from").prop("checked"), 
				container = $("#fg_container"), 
				fieldsets = $("#file_gallery_fieldsets").val(),
				data = null,
				attachment_order = $("#data_collector_full").val();
			
			$("#file_gallery").removeClass("uploader");
			$("#fg_container").css({ minHeight: 0 });
			
			if( 0 === $("#file_gallery_response").length )
				$("#file_gallery.postbox").prepend('<div id="file_gallery_response"></div>');
			
			if( "return_from_single_attachment" == response_message )
			{
				file_gallery.tinymce_deselect();
			}
			else if( "refreshed" == response_message )
			{
				file_gallery.refreshed = true;
			}
			else if( "sorted" == response_message )
			{
				file_gallery.refreshed = true;
				attachment_order = $("#file_gallery_attachments_sort").val();
			}
			else if( "UploadComplete" == response_message )
			{
				file_gallery.refreshed = true;
			}
			
			if( "undefined" == typeof(fieldsets) )
				fieldsets = "";
			
			if( true === tags_from || "undefined" == typeof( tags_from ) || "undefined" == tags_from )
				tags_from = true;
			else
				tags_from = false;

			data = {
				action				: "file_gallery_load",
				post_id 			: $("#post_ID").val(),
				attachment_order 	: attachment_order,
				attachment_orderby 	: $("#file_gallery_attachments_sortby").val(),
				checked_attachments : $("#data_collector_checked").val(),
				files_or_tags 		: $("#files_or_tags").val(),
				tag_list 			: $("#fg_gallery_tags").val(),
				tags_from 			: tags_from,
				fieldsets			: fieldsets,
				_ajax_nonce			: file_gallery.options.file_gallery_nonce
			};
			
			response_message = null;

			container
				.empty()
				.append('<p class="loading_image"><img src="' + file_gallery.options.file_gallery_url + '/images/ajax-loader.gif" alt="' + file_gallery.L10n.loading_attachments + '" /><br />' + file_gallery.L10n.loading_attachments + '<br /></p>')
				.css({height : "auto"})
				.show();
			
			var request = $.post
			(
				ajaxurl, 
				data,
				function(response)
				{
					container.html(response);
					file_gallery.setup();
				},
				"html"
			);

			return;
		},


		/**
		 * some basic show / hide setup
		 */
		setup : function()
		{
			var container = $("#fg_container"),
				files_or_tags = $("#files_or_tags");
			
			if( 0 === container.length || (0 === files_or_tags.length && 0 < $("file_gallery_gallery_options").length) )
				return;

			file_gallery.options.num_attachments = $("#fg_container #file_gallery_list li").length;
			
			$("#file_gallery_copy_all").appendTo("#fg_buttons .advanced");

			container.css({height : "auto"});
			$("#file_gallery_switch_to_tags").show();
			
			// hide elements if post has no attachments
			if( 0 === file_gallery.options.num_attachments )
			{
				$("#file_gallery_attachments_sorting, #file_gallery_save_menu_order_link").hide();
				
				if( 0 === $("#fg_info").length )
					$("#file_gallery_form").append('<div id="fg_info"></div>');
				
				$("#fg_info").html(file_gallery.L10n.no_attachments_upload).appendTo("#file_gallery_attachment_list").show();
				container.css({overflow:"hidden", paddingBottom: 0});
			}
			else
			{
				container.css({overflow:"auto"});
			}
			
			$("#file_gallery fieldset").not(".hidden").show();
			
			// tags from current post only checkbox
			$("#fg_gallery_tags_from").prop("checked", ("false" == file_gallery.options.tags_from) ? false : true);
			
			// clickable tags
			$(".fg_insert_tag").each( function()
			{
				var ct = "," + $("#fg_gallery_tags").val() + ",",
					ns = "," + $(this).attr("name") + ",",
					nn = "," + $(this).html() + ",";
				
				if ( -1 < ct.search(ns) || -1 < ct.search(nn) )
					$(this).addClass("selected");
				else
					$(this).removeClass("selected");
			});
			
			// display tags or attachments
			if( "undefined" == typeof( files_or_tags.val() ) || "undefined" == files_or_tags.val() )
				files_or_tags.val("tags");

			// load files / tags respectively
			file_gallery.files_or_tags( true );
			file_gallery.do_plugins();
			file_gallery.serialize();
			file_gallery.initialized = true;
			file_gallery.fieldset_toggle();
		},


		/**
		 * processes attachments data, builds the [gallery] shortcode
		 */
		serialize : function( internal_event, external_attachments )
		{
			var serial = "",
				id = ""
				size = "",
				linkto = "",
				linkrel = "",
				linksize = "",
				linkto_val = $("#file_gallery_linkto").val(),
				external_url = $("#file_gallery_external_url").val(),
				template = "",
				order = "",
				orderby = "",
				linkclass = "",
				imageclass = "",
				galleryclass = "",
				mimetype = "",
				limit = "",
				offset = "",
				paginate = "",
				columns = "",
				tags = "",
				tags_from = "",
				ctlen = ""
				ct = "",
				ns = "",
				nn = "",
				copies = "",
				originals = "",
				file_gallery_order = "",
				file_gallery_orderby = "",
				include_attribute_name = file_gallery_options.wp_version >= 3.5 ? "ids" : "include";
			
			if( "undefined" == typeof(internal_event) ) {
				internal_event = "normal";
			}
			
			if( "" != $("#file_gallery_linkrel_custom").val() && "undefined" != typeof($("#file_gallery_linkrel_custom").val()) )
			{
				$("#file_gallery_linkrel_custom").val( $("#file_gallery_linkrel_custom").val().replace(/\[/, '').replace(/\]/, '') );
				linkrel = ' rel="' + $("#file_gallery_linkrel_custom").val() + '"';
			}
			else if( "false" == $("#file_gallery_linkrel").val() )
			{
				linkrel = ' rel="false"';
			}

			if( "external_url" == linkto_val ) {
				linkto_val = encodeURIComponent(external_url);
			}


			// tags
			if( 0 < $("#fg_gallery_tags").length )
			{
				if( "undefined" == typeof( $("#fg_gallery_tags").val() ) || "undefined" == $("#fg_gallery_tags").val() )
					$("#fg_gallery_tags").val("");
				
				tags = $("#fg_gallery_tags").val();
				tags_from = $("#fg_gallery_tags_from").prop("checked");
				
				tags = tags.replace(/\s+/g, " ").replace(/\s+,/g, ",").replace(/,+\s*/g, ",");
			
				ctlen = tags.length;
				
				if( "," == tags[0] ) {
					tags = tags.substring(1);
				}
				
				if( "," == tags[ctlen-2] ) {
					tags = tags.substring(0, ctlen-1);
				}
			
				$("#fg_gallery_tags").val(tags);
				
				$(".fg_insert_tag").each( function()
				{
					ct = "," + $("#fg_gallery_tags").val() + ",";
					ns = "," + $(this).attr("name") + ",";
					nn = "," + $(this).html() + ",";
					
					if ( -1 < ct.search(ns) || -1 < ct.search(nn) ) {
						$(this).addClass("selected");
					}
					else {
						$(this).removeClass("selected");
					}
				});
			}


			if( 0 < file_gallery.options.num_attachments ) {
				serial = $("#file_gallery_list").sortable("serialize");
			}
			
			serial = serial.toString().replace(/image\[\]=/g, '').replace(/&/g, ',').replace(/,+/g, ',');
			$("#data_collector_full").val(serial);
			
			// get checked items
			serial = file_gallery.map("checked", serial);
			$("#data_collector_checked").val(serial);
			
			// get checked copies
			copies = file_gallery.map("copy", serial);
			$("#file_gallery_copies").val(copies);
		
			// get checked originals
			originals = file_gallery.map("has_copies", serial);
			$("#file_gallery_originals").val(originals);
			
			if( "" == $("#file_gallery_originals").val() && "" == $("#file_gallery_copies").val() ) {
				$("#file_gallery_delete_what").val("all");
			}
			
			file_gallery_order = $("#file_gallery_order");
			file_gallery_orderby = $("#file_gallery_orderby");
			
			order = ' order="' + file_gallery_order.val() + '"';
				
			if( "default" != file_gallery_orderby.val() )
			{
				if( "rand" == file_gallery_orderby.val() )
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
			
			if( "external_url" == $("#file_gallery_linkto").val() ) {
				$("#file_gallery_external_url_label").show();
			}
			else {
				$("#file_gallery_external_url_label").hide();
			}
			
			if( "external_url" == $("#file_gallery_single_linkto").val() ) {
				$("#file_gallery_single_external_url_label").show();
			}
			else {
				$("#file_gallery_single_external_url_label").hide();
			}

			if( "none" == $("#file_gallery_linkto").val() ) {
				$("#file_gallery_linkclass_label").hide();
			}
			else {
				$("#file_gallery_linkclass_label").show();
			}

			if( "none" == $("#file_gallery_single_linkto").val() ) {
				$("#file_gallery_single_linkclass_label").hide();
			}
			else {
				$("#file_gallery_single_linkclass_label").show();
			}
			
			if( 0 < Number($("#file_gallery_limit").val()) ) {
				$("#file_gallery_paginate_label").show();
			}
			else {
				$("#file_gallery_paginate_label").hide();
			}
			
			if( "file" == $("#file_gallery_linkto").val() || "external_url" == $("#file_gallery_linkto").val())
			{
				$("#file_gallery_linksize_label").show();
				$("#file_gallery_linkrel_custom_label").show();

				if( "full" != $("#file_gallery_linksize").val() ) {
					linksize = ' link_size="' + $("#file_gallery_linksize").val() + '"';
				}
			}
			else
			{
				$("#file_gallery_linksize_label").hide();
				linksize = "";
			}

			if( tags_from ) {
				tags_from = "";
			}
			else {
				tags_from = ' tags_from="all"';
			}

			if( typeof external_attachments !== "undefined" ) {
				
				if( serial == "" ) {
					serial = external_attachments.join(",");
				}
				else {
					serial += "," + external_attachments.join(",");
				}
			}

			if( "" != tags ) {
				serial = '[gallery tags="' + tags + '"' + tags_from;
			}
			else if( "" != serial && false === file_gallery.is_all_checked() ) {
				serial = '[gallery ' + include_attribute_name + '="' + serial + '"';
			}
			else {
				serial = '[gallery';
			}
		
			if( "thumbnail" != $("#file_gallery_size").val() )
				size = ' size="' + $("#file_gallery_size").val() + '"';

			if( "attachment" != $("#file_gallery_linkto").val() )
				linkto = ' link="' + linkto_val + '"';
		
			if( "default" != $("#file_gallery_template").val() )
				template = ' template="' + $("#file_gallery_template").val() + '"';
			
			if( "" != $("#file_gallery_linkclass").val() && "none" != $("#file_gallery_linkto").val() )
				linkclass = ' linkclass="' + $("#file_gallery_linkclass").val() + '"';
			
			if( "" != $("#file_gallery_imageclass").val() )
				imageclass = ' imageclass="' + $("#file_gallery_imageclass").val() + '"';
			
			if( "" != $("#file_gallery_galleryclass").val() )
				galleryclass = ' galleryclass="' + $("#file_gallery_galleryclass").val() + '"';
			
			if( "" != $("#file_gallery_mimetype").val() )
				mimetype = ' mimetype="' + $("#file_gallery_mimetype").val() + '"';
				
			if( 0 < Number($("#file_gallery_limit").val()) )
			{
				limit = ' limit="' + $("#file_gallery_limit").val() + '"';
				
				if( "true" == $("#file_gallery_paginate").val() )
					limit += ' paginate="true"';
			}
			
			if( 0 < Number($("#file_gallery_offset").val()) )
				limit += ' offset="' + $("#file_gallery_offset").val() + '"';
			
			if( "" != $("#file_gallery_postid").val() )
				id = ' id="' + $("#file_gallery_postid").val() + '"';
			
			if( "" != $("#file_gallery_columns").val() && "3" != $("#file_gallery_columns").val() )
				columns = ' columns="' + $("#file_gallery_columns").val() + '"';
			
			serial += id + size + linkto + linksize + linkclass + imageclass + galleryclass + mimetype + limit + order + orderby + template + columns + linkrel + "]\n";
			
			$("#data_collector").val(serial);

			if( "normal" == internal_event )
				file_gallery.tinymce_maybe_update_gallery_data(serial);
		},


		/**
		 * binds jquery plugins to objects
		 */
		do_plugins : function()
		{
			try
			{
				$("#file_gallery_list")
					.sortable(
					{
						placeholder: "ui-selected",
						tolerance: "pointer",
						items: "li",
						opacity: 0.6,
						start: function()
						{
							var sitem = $("#file_gallery_list .sortableitem.image:first-child");
							$("#fg_container .fgtt").unbind("click.file_gallery");
							$("#file_gallery_list .ui-selected").css({width : sitem.width()  + "px", height : sitem.height() + "px"});
						},
						update: function(){ file_gallery.serialize(); }
					});
			}
			catch(error)
			{
				alert("Error initializing $.ui.sortables: " + error.description);
			};
			
			if( true !== file_gallery.refreshed )
			{
				// set up draggable / sortable list of attachments
				$("#file_gallery_list").sortable(
				{
					placeholder: "ui-selected",
					tolerance: "pointer",
					items: "li",
					opacity: 0.6,
					start: function()
					{
						var sitem = $("#file_gallery_list .sortableitem.image:first-child");
						$("#fg_container .fgtt").unbind("click.file_gallery");
						$("#file_gallery_list .ui-selected").css({"width"  : sitem.width()  + "px", "height" : sitem.height() + "px"});
					},
					update: function(){ file_gallery.serialize(); }
				});
				
				// set up delete originals choice dialog
				$("#file_gallery_delete_dialog").dialog(
				{
					autoOpen: false,
					closeText: file_gallery.L10n.close,
					bgiframe: true,
					resizable: false,
					width: 600,
					modal: true,
					draggable: false,
					dialogClass: 'wp-dialog',
					close: function(event, ui)
					{
						var id = $("#file_gallery_delete_dialog").data("single_delete_id");
						$("#detach_or_delete_" + id + ", #detach_attachment_" + id + ",#del_attachment_" + id).fadeOut(100);
					},
					buttons: {
	
						"Cancel" : function()
						{
							var id = $("#file_gallery_delete_dialog").data("single_delete_id");
							
							$("#file_gallery_delete_what").val("data_only");
							$("#detach_or_delete_" + id + ", #detach_attachment_" + id + ",#del_attachment_" + id).fadeOut(100);
							$("#file_gallery_delete_dialog").removeData("single_delete_id");
							
							$(this).dialog("close");
						},
						"Delete attachment data only" : function()
						{
							var message = false, id = "";
							
							if( $(this).hasClass("single") )
							{
								id = $("#file_gallery_delete_dialog").data("single_delete_id");
							}
							else
							{
								message = file_gallery.L10n.sure_to_delete;
								id = $('#data_collector_checked').val();
							}
							
							$("#file_gallery_delete_what").val("data_only");
							file_gallery.delete_attachments( id, message );
							
							$(this).dialog("close");
						},
						"Delete attachment data, its copies and the files" : function()
						{
							var message = false, id;
							
							if( $(this).hasClass("single") )
							{
								id = $("#file_gallery_delete_dialog").data("single_delete_id");
							}
							else
							{
								message = file_gallery.L10n.sure_to_delete;
								id = $('#data_collector_checked').val();
							}
							
							$("#file_gallery_delete_what").val("all");
							file_gallery.delete_attachments( id, message );
							
							$(this).dialog("close");
						}
					}
				});
					
				$("#file_gallery_image_dialog").dialog(
				{
					autoOpen: false,
					closeText: file_gallery.L10n.close,
					bgiframe: true,
					resizable: false,
					position: "center",
					modal: true,
					draggable: false,
					dialogClass: 'wp-dialog'
				});
				
				$("#file_gallery_copy_all_dialog").dialog(
				{
					autoOpen: false,
					closeText: file_gallery.L10n.close,
					bgiframe: true,
					resizable: false,
					position: "center",
					width: 500,
					modal: true,
					draggable: false,
					dialogClass: 'wp-dialog',
					buttons: {
						"Cancel": function(){ $(this).dialog("close"); },
						"Continue": function()
						{
							var from_id = parseInt($("#file_gallery_copy_all_dialog input#file_gallery_copy_all_from").val());
							
							if( isNaN(from_id) || from_id === 0 )
							{
								if( isNaN(from_id) ) {
									from_id = "-none-";
								}

								alert(file_gallery.L10n.copy_from_is_nan_or_zero.replace(/%d/, from_id));
								
								return false;
							}
							
							file_gallery.copy_all_attachments(from_id);
							$(this).dialog("close");
						}
					}
				});
			}
		},


		/**
		 * Displays the jQuery UI modal delete dialog
		 */
		delete_dialog : function( id, single )
		{
			var m = false,
				delete_dialog = $("#file_gallery_delete_dialog"),
				o = $("#file_gallery_originals").val();
			
			if( single )
				delete_dialog.addClass("single");
			else
				m = file_gallery.L10n.sure_to_delete
			
			if( ("" != o && "undefined" != o && "undefined" != typeof( o )) || $("#image-" + id).hasClass("has_copies") )
				delete_dialog.data("single_delete_id", id).dialog('open'); //originals present in checked list
			else
				file_gallery.delete_attachments( id, m );
			
			return false;
		},


		/**
		 * handles adding and removing of tags that will be used
		 * in gallery shortcode instead of attachment_ids,
		 * both when edited by hand and when a tag link is clicked
		 */
		add_remove_tags : function( tag )
		{
			var current_tags 	= $("#fg_gallery_tags").val(),
				newtag_slug  	= $(tag).attr("name"),
				newtag_name		= $(tag).html(),
				ct 			 	= "," + current_tags + ",",
				ns			 	= "," + newtag_slug  + ",",
				nn			 	= "," + newtag_name  + ",",
				ctlen			= 0;
			
			if( "-1" == ct.search(ns) && "-1" == ct.search(nn) )
			{
				$(tag).addClass("selected");
				
				if( "" != current_tags )
					newtag_slug = "," + newtag_slug;
				
				current_tags += newtag_slug;
			}
			else
			{
				$(tag).removeClass("selected");
		
				if( "-1" != ct.search(ns) )
					current_tags = ct.replace(ns, ",");
				else if( "-1" != ct.search(nn) )
					current_tags = ct.replace(nn, ",");
			}
			
			// clean up whitespace
			current_tags = current_tags.replace(/\s+/g, " ").replace(/\s+,/g, ",").replace(/,+\s*/g, ",");
		
			ctlen = current_tags.length;
			
			if( "," == current_tags[0] )
				current_tags = current_tags.substr(1);
			
			if( "," == current_tags[ctlen-2] )
				current_tags = current_tags.substr(0, ctlen-2);
			
			$("#fg_gallery_tags").val(current_tags);
			
			file_gallery.serialize();
			
			return false;
		},


		/**
		 * maps attachment data (checked, has copies, is a copy)
		 */
		map : function(what, data)
		{
			data = data.split(',');
			var dl = data.length;
			
			if( "checked" == what )
			{
				while( 0 < dl )
				{
					if( false === $("#att-chk-" + data[dl-1]).prop('checked') )
						delete data[dl-1];
					
					dl--;
				}
			}
			else if( "copy" == what || "has_copies" == what )
			{
				while( 0 < dl )
				{
					if( false === $("#image-" + data[dl-1]).hasClass(what) )
						delete data[dl-1];
					
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
			var files_or_tags = $("#files_or_tags");
			
			if( do_switch )
			{
				if( "files" == files_or_tags.val() )
					files_or_tags.val("tags")
				else
					files_or_tags.val("files")
			}
			
			if( "files" == files_or_tags.val() || "undefined" == typeof( files_or_tags.val() ) || "undefined" == files_or_tags.val() )
			{
				$("#file_gallery_switch_to_tags").attr("value", file_gallery.L10n.switch_to_tags);
				$("#fg_gallery_tags_container, #file_gallery_tag_list").fadeOut(250, function(){ $("#file_gallery_attachment_list").fadeIn(); });
				$("#fg_gallery_tags").val('');
				
				files_or_tags.val("tags");
			}
			else if( "tags" == $("#files_or_tags").val() )
			{
				$("#file_gallery_switch_to_tags").attr("value", file_gallery.L10n.switch_to_files);
				$("#file_gallery_attachment_list").fadeOut(250, function(){ $("#fg_gallery_tags_container, #file_gallery_tag_list").fadeIn(); });
				
				files_or_tags.val("files");
			}
			
			if( "undefined" == typeof(do_switch) || false === do_switch )
				file_gallery.serialize("files_or_tags");
		},


		/**
		 * saves attachment metadata
		 */
		save_attachment : function( attachment_data )
		{
			file_gallery.options.file_gallery_mode = "list";
			
			$("#fg_container")
				.html("<p class=\"loading_image\"><img src=\"" + file_gallery.options.file_gallery_url + "/images/ajax-loader.gif\" alt=\"" + file_gallery.L10n.saving_attachment_data + "\" /><br />" + file_gallery.L10n.saving_attachment_data + "</p>");
			
			$.post
			(
				ajaxurl, 
				{
					post_id 			: $("#post_ID").val(),
					attachment_id 		: attachment_data.id, 
					action 				: "file_gallery_main_update",
					post_alt	   		: attachment_data.alt,
					post_title   		: attachment_data.title,
					post_content 		: attachment_data.content,
					post_excerpt 		: attachment_data.excerpt,
					tax_input	 		: attachment_data.tax_input,
					menu_order   		: attachment_data.menu_order,
					custom_fields   	: attachment_data.custom_fields,
					attachment_order 	: $("#attachment_order").val(),
					checked_attachments : $("#checked_attachments").val(),
					_ajax_nonce			: file_gallery.options.file_gallery_nonce
				},
				function(response)
				{
					$("#fg_container").html(response).css({height : "auto"});
					$("#file_gallery_response").html($("#file_gallery_response_inner").html()).stop().fadeTo(0, 1).show().fadeOut(7500);
					
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
			var delete_what 	= $("#file_gallery_delete_what"),
				delete_what_val = delete_what.val(),
				a,
				copies,
				originals,
				data,
				attachment_count = 1;
			
			if( "" == attachment_ids || "undefined" == typeof( attachment_ids ) || "" == delete_what_val || "undefined" == typeof( delete_what_val ) || "undefined" == delete_what_val )
				return false;
			
			if( "undefined" == typeof( message ) )
				message = false;
		
			if( (false !== message && confirm(message)) || false === message )
			{
				if( "-1" != attachment_ids.search(/,/) )
					attachment_count = attachment_ids.split(",").length;
				
				if( 1 < attachment_count )
					a = file_gallery.L10n.deleting_attachments;
				else
					a = file_gallery.L10n.deleting_attachment;
				
				if( 2 > attachment_count )
				{
					if( $("#image-" + attachment_ids).hasClass("copy") )
						$("#file_gallery_copies").val(attachment_ids);
					else if( $("#image-" + attachment_ids).hasClass("has_copies") )
						$("#file_gallery_originals").val(attachment_ids);
				}
				
				copies 	  = $("#file_gallery_copies").val();
				originals = $("#file_gallery_originals").val();
				
				if( "" == copies || "undefined" == copies || "undefined" == typeof( copies ))
					copies = "";
				
				if( "" == originals || "undefined" == originals || "undefined" == typeof( originals ))
					originals = "";
					
				$("#fg_container")
					.css({height : $("#fg_container").height()})
					.html('<p class="loading_image"><img src="' + file_gallery.options.file_gallery_url + '/images/ajax-loader.gif" alt="' + file_gallery.L10n.loading + '" /><br />' + a + '</p>');
				
				data = {
						post_id 			: $("#post_ID").val(),
						action 				: "file_gallery_main_delete",
						attachment_ids 		: attachment_ids, 
						attachment_order 	: $("#data_collector_full").val(),
						checked_attachments : $("#data_collector_checked").val(),
						copies				: copies,
						originals			: originals,
						delete_what			: delete_what_val,
						_ajax_nonce			: file_gallery.options.file_gallery_nonce
				};
				
				$.post
				(
					ajaxurl, 
					data,
					function(response)
					{
						$('#fg_container').html(response).css({height : "auto"});
						$('#file_gallery_response').html($("#file_gallery_response_inner").html()).stop().fadeTo(0, 1).css({display : "block"}).fadeOut(7500);
						
						file_gallery.setup();
					},
					"html"
				);
			}
			
			delete_what.val("data_only")
		},


		/**
		 * detaches checked attachments
		 */
		detach_attachments : function( attachment_ids, message )
		{
			if( "" == attachment_ids || "undefined" == typeof( attachment_ids ) )
				return false;
			
			if( "undefined" == typeof( message ) )
				message = false;
		
			if( (false !== message && confirm(message)) || false === message )
			{
				var attachment_count = 1,
					a = file_gallery.L10n.detaching_attachment;
				
				if( "-1" != attachment_ids.search(/,/) )
					attachment_count = attachment_ids.split(",").length;
		
				if( 1 < attachment_count )
					a = file_gallery.L10n.detaching_attachments;
		
				$("#fg_container")
					.css({"height" : $("#fg_container").height()})
					.html('<p class="loading_image"><img src="' + file_gallery.options.file_gallery_url + '/images/ajax-loader.gif" alt="' + file_gallery.L10n.loading + '" /><br />' + a + '</p>');
		
				data = {
						post_id 			: $("#post_ID").val(),
						action 				: "file_gallery_main_detach",
						attachment_ids 		: attachment_ids, 
						attachment_order 	: $("#data_collector_full").val(),
						checked_attachments : $("#data_collector_checked").val(),
						_ajax_nonce			: file_gallery.options.file_gallery_nonce
				};
				
				$.post
				(
					ajaxurl, 
					data,
					function(response)
					{
						$("#fg_container")
							.html(response)
							.css({height : "auto"});
						
						$("#file_gallery_response")
							.html($("#file_gallery_response_inner").html())
							.stop()
							.fadeTo(0, 1)
							.show()
							.fadeOut(7500);
						
						file_gallery.setup();
					},
					"html"
				);
			}
			
			return false;
		},


		/**
		 * saves attachment order as menu_order
		 */ 
		save_menu_order : function()
		{
			var attachment_order = $("#data_collector_full").val(),
				admin_url = ajaxurl.split("/admin-ajax.php").shift(),
				data;
		
			if( "undefined" == attachment_order || "" == attachment_order )
				return false;
			
			$('#file_gallery_response').stop().fadeTo(0, 1).html('<img src="' + admin_url + '/images/loading.gif" width="16" height="16" alt="' + file_gallery.L10n.loading + '" id="fg_loading_on_bar" />').show();
			
			data = {
				action			 : "file_gallery_save_menu_order",
				post_id 		 : $("#post_ID").val(),
				attachment_order : attachment_order,
				_ajax_nonce		 : file_gallery.options.file_gallery_nonce
			};
			
			$.post
			(
				ajaxurl, 
				data,
				function(response)
				{
					$("#file_gallery_response").html(response).fadeOut(7500);
				},
				"html"
			);
		},

		
		send_to_editor : function( id )
		{
			var ed = file_gallery.tinymce_get_editor();
			
			if( "file_gallery_send_gallery_legend" == id )
			{
				var gallery_data = $('#data_collector').val();
				
				if( "" == gallery_data || "undefined" == typeof(gallery_data) )
					return false;
				
				send_to_editor(gallery_data);
				$("#file_gallery_uncheck_all").trigger("click");
			}
			else
			{
				attachment_id = $("#data_collector_checked").val();
		
				if( "" == attachment_id || "undefined" == typeof(attachment_id) )
					return false;
				
				var data = {
					action		  : "file_gallery_send_single",
					attachment_id : attachment_id,
					size 		  : $("#file_gallery_single_size").val(),
					linkto 		  : $("#file_gallery_single_linkto").val(),
					external_url  : $("#file_gallery_single_external_url").val(),
					linkclass 	  : $("#file_gallery_single_linkclass").val(),
					imageclass 	  : $("#file_gallery_single_imageclass").val(),
					align 	      : $("#file_gallery_single_align").val(),
					post_id 	  : $("#post_ID").val(),
					caption       : $("#file_gallery_single_caption:checked").length ? true : false,
					_ajax_nonce	  : file_gallery.options.file_gallery_nonce
				};
				
				$.post
				(
					ajaxurl, 
					data,
					function( single_data )
					{
						send_to_editor(single_data);
						$("#file_gallery_uncheck_all").trigger("click");
					},
					"html"
				);
			}
		},


		/**
		 * loads the attachment metadata edit page into fg_container
		 */
		edit : function( attachment_id )
		{
			if( "" == attachment_id || "undefined" == typeof( attachment_id ) )
				return false;
			
			file_gallery.options.file_gallery_mode = "edit";
			
			var data = {
				action				: "file_gallery_edit_attachment",
				post_id 			: $("#post_ID").val(),
				attachment_id 		: attachment_id, 
				attachment_order 	: $("#data_collector_full").val(),
				checked_attachments : $("#data_collector_checked").val(),
				_ajax_nonce			: file_gallery.options.file_gallery_nonce
			};
			
			$("#fg_container")
				.html("<p class=\"loading_image\"><img src=\"" + file_gallery.options.file_gallery_url + "/images/ajax-loader.gif\" alt=\"" + file_gallery.L10n.loading_attachment_data + "\" /><br />" + file_gallery.L10n.loading_attachment_data + "</p>");
			
			$.post
			(
				ajaxurl, 
				data,
				function(response)
				{
					$('#fg_container').html(response);
					
					file_gallery.tinymce_deselect();
				},
				"html"
			);
			
			return false;
		},


		/**
		 * zooms the thumbnail (needs to be replaced with lightbox)
		 */
		zoom : function( element )
		{
			var image = new Image();
		
			$("#file_gallery_image_dialog")
				.html('<p class="loading_image"><img src="' + file_gallery.options.file_gallery_url + '/images/ajax-loader.gif" alt="' + file_gallery.L10n.loading + '" />	</p>')
				.dialog( 'option', 'width',  'auto' )
				.dialog( 'option', 'height', 'auto' )
				.dialog("open");
			
			$(image).bind("load", function()
			{
				var ih    = this.height,
					iw    = this.width,
					src   = this.src,
					ratio = iw/ih,
					ww    = $(window).width(),
					wh    = $(window).height();
				
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
				
				$("#file_gallery_image_dialog")
					.html('<img src="' + src + '" width="' + iw + '" height="' + ih + '" alt="image" />')
					.dialog( 'option', 'position', 'center');
			});
			
			image.src = $(element).attr("href");
			
			return false;
		},
		

		fieldset_toggle : function( toggler )
		{
			if( "undefined" == typeof( toggler ) )
				return;
			
			var	state = 0,
				togglee = "file_gallery_toggler",
				action = "file_gallery_save_toggle_state",
				fieldset = "file_gallery_gallery_options";

			switch( toggler )
			{
				case "file_gallery_hide_single_options" : 
					togglee = "file_gallery_single_toggler";
					action = "file_gallery_save_single_toggle_state";
					fieldset = "file_gallery_single_options"
					break;
				case "file_gallery_hide_acf" : 
					togglee = "fieldset_attachment_custom_fields #media-single-form";
					action = "file_gallery_save_acf_toggle_state";
					break;
				default : 
					break;
			}

			if( $("#" + toggler).hasClass("open") )
			{
				$("#" + toggler + ", #" + fieldset).removeClass("open").addClass("closed");
			}
			else
			{
				$("#" + toggler + ", #" + fieldset).removeClass("closed").addClass("open");
				state = 1;
			}

			$("#" + togglee).toggle();
			
			$.post
			(
				ajaxurl, 
				{
					'action'		: action,
					'state'			: state,
					'_ajax_nonce'	: file_gallery.options.file_gallery_nonce
				}
			);
		},


		copy_all_attachments : function(from_id)
		{
			if( "" == from_id || "undefined" == typeof( from_id ) )
				return false;
			
			var admin_url = ajaxurl.split("/admin-ajax.php").shift();
			
			file_gallery.options.file_gallery_mode = "list";
			
			var data = {
				action				: "file_gallery_copy_all_attachments",
				to_id 				: $("#post_ID").val(),
				from_id 		    : from_id, 
				_ajax_nonce			: file_gallery.options.file_gallery_nonce
			};
			
			$('#file_gallery_response').stop().fadeTo(0, 1).html('<img src="' + admin_url + '/images/loading.gif" width="16" height="16" alt="' + file_gallery.L10n.loading + '" id="fg_loading_on_bar" />').show();
			
			$.post
			(
				ajaxurl, 
				data,
				function(response)
				{
					$("#file_gallery_response").stop().html(response).show().css({opacity : 1}).fadeOut(7500);
					file_gallery.init("refreshed");
				},
				"html"
			);
		},
		
		
		/**
		 * set / unset image as post thumb
		 */
		set_post_thumb : function( attachment_ids, unset )
		{
			if( "" == attachment_ids || "undefined" == typeof( attachment_ids ) )
				return false;
			
			var action = "file_gallery_unset_post_thumb";
			
			if( false === unset )
				action = "file_gallery_set_post_thumb";
			
			var admin_url = ajaxurl.split("/admin-ajax.php").shift();

			$('#file_gallery_response').stop().fadeTo(0, 1).html('<img src="' + admin_url + '/images/loading.gif" width="16" height="16" alt="' + file_gallery.L10n.loading + '" id="fg_loading_on_bar" />').show();

			$("#image-" + attachment_ids).append('<img src="' + file_gallery.options.file_gallery_url + '/images/loading-big.gif" width="32" height="32" alt="' + file_gallery.L10n.loading + '" id="fg_loading_on_thumb" class="thumb_switch_load" />').children("#fg_loading_on_thumb").fadeIn(250);
			
			data = {
				action			: action,
				post_id			: $("#post_ID").val(),
				attachment_ids	: attachment_ids,
				_ajax_nonce		: file_gallery.options.file_gallery_nonce
			};
			
			$.post
			(
				ajaxurl, 
				data,
				function( new_thumb )
				{
					var src = $("#image-" + attachment_ids + " .post_thumb_status img").attr("src"),
						response = file_gallery.L10n.post_thumb_set;
					
					$("#fg_loading_on_thumb").fadeOut(250).remove();
					
					if( "file_gallery_set_post_thumb" == action )
					{
						$(".sortableitem.post_thumb .post_thumb_status img")
							.attr("alt", file_gallery.L10n.set_as_featured)
							.attr("src", src.replace(/star_unset.png/, "star_set.png"))
							.parent()
								.attr("title", file_gallery.L10n.set_as_featured)
								.parent()
									.removeClass("post_thumb");
						
						$("#image-" + attachment_ids + " .post_thumb_status img")
							.attr("src", src.replace(/star_set.png/, "star_unset.png"))
							.attr("alt", file_gallery.L10n.unset_as_featured)
							.parent()
								.attr("title", file_gallery.L10n.unset_as_featured);
						
						$("#image-" + attachment_ids).addClass("post_thumb");
						
						$("#postimagediv .inside")
							.html(new_thumb);
					}
					else
					{						
						WPRemoveThumbnail(file_gallery.options.post_thumb_nonce);
						
						response = file_gallery.L10n.post_thumb_unset;
						
						$("#image-" + attachment_ids + " .post_thumb_status img")
							.attr("alt", file_gallery.L10n.set_as_featured)
							.attr("src", src.replace(/star_unset.png/, "star_set.png"))
							.parent()
								.attr("title", file_gallery.L10n.set_as_featured)
								.parent()
									.removeClass("post_thumb");
					}
					
					$('#file_gallery_response').html(response).fadeOut(7500);
				}
			);
			
			return false;
		},


		post_edit_screen_adjust : function()
		{
			if( 800 > $(window).width() )
			{
				$("th.column-post_thumb, th.column-attachment_count").css({fontSize: 0, width: "20px"});
				$("td.column-post_thumb, td.column-attachment_count").css({padding: 0});
			}
			else
			{
				$("th.column-post_thumb, th.column-attachment_count").css({fontSize: "inherit", width: ""});
				
				if( 70 < $("#post_thumb").width() )
					$("th.column-post_thumb").width(70);
				
				if( 150 < $("#attachment_count").width() )
					$("th.column-attachment_count").width(150);
				
				$("td.column-post_thumb, td.column-attachment_count").css({padding: "inherit"});
			}
		},
		
		
		get_attachment_custom_fields : function()
		{
			var output = {};
			
			$("#attachment_data_edit_form #media-single-form .custom_field textarea").each(function()
			{
				var key = $(this).attr("name").match(/attachments\[\d+\]\[([^\]]+)\]/)[1], // attachments[ID][FIELDNAME]
					val = $(this).val();
				
				output[key] = val;
			});
			
			return output;
		},
		
		
		regenerate_thumbnails : function( attachment_ids )
		{
			var el = 1 < attachment_ids.length ? "a.file_gallery_regenerate" : "#file_gallery_regenerate-" + attachment_ids[0],
				text = $(el).html();
			
			if( 0 < $("#file_gallery_response").length )
				$(el).html('<img src="' + file_gallery.options.file_gallery_url + '/images/ajax-loader.gif" alt="' + file_gallery.L10n.regenerating + '" />' + file_gallery.L10n.regenerating);
			else
				$(el).html(file_gallery.L10n.regenerating);

			$.post
			(
				ajaxurl, 
				{
					action : "file_gallery_regenerate_thumbnails",
					attachment_ids : attachment_ids
				},
				function(response)
				{
					if( 0 < $("#file_gallery_response").length )
					{
						$("#file_gallery_response").stop().html(response.message).show().css({opacity : 1}).fadeOut(7500);
						$("#fg_loading_on_thumb").fadeOut(250).remove();
						$(el).html(text);
					}
					else
					{
						$(el).html(response.message).fadeTo(2000, 1, function(){ $(el).html(text); });
					}
				},
				"json"
			);
		}
	});


	/* end file_gallery object */

	
	if( "undefined" !== typeof(init_file_gallery) && true === init_file_gallery )
	{	
		// WPML
		if( $("#icl_div").length > 0 )
		{
			if( $("#icl_translations_table").length > 0 )
			{
				$("#icl_translations_table a[title=edit]").each(function()
				{
					var fg_icl_trans_id = $(this).attr('href').match(/post=([\d]+)&/) || false;

					if (fg_icl_trans_id) {
						fg_icl_trans_id = Number(fg_icl_trans_id.pop());
					}
		
					if( "number" == typeof(fg_icl_trans_id) )
					{
						$(this).after('<a title="' + file_gallery.L10n.copy_all_from_translation + '" href="#" id="copy-from-translation-' + fg_icl_trans_id + '"><img src="' + file_gallery.options.file_gallery_url + '/images/famfamfam_silk/image_add.png" alt="' + file_gallery.L10n.copy_all_from_translation + '" /></a>');
		
						$("#copy-from-translation-" + fg_icl_trans_id).bind("click", function()
						{
							if( confirm(file_gallery.L10n.copy_all_from_translation_) )
								file_gallery.copy_all_attachments(fg_icl_trans_id);
		
							return false;
						});
					}
				});
			}
			else
			{
				var fg_icl_ori_id = $("#icl_translation_of option:selected").val();
		
				if( "undefined" != typeof(fg_icl_ori_id) && "undefined" != fg_icl_ori_id )
				{
					$("#icl_div .inside").append('<a href="#" id="file_gallery_copy_from_wmpl_original">' + file_gallery.L10n.copy_all_from_original + '</a>');
		
					$("#file_gallery_copy_from_wmpl_original").bind("click", function()
					{
						if( confirm(file_gallery.L10n.copy_all_from_original_) )
							file_gallery.copy_all_attachments(fg_icl_ori_id);
		
						return false;
					});
				}
			}
		} 
	
	
		// show / hide additional gallery options depending on preselected values
		if( "default" != $("#file_gallery_orderby").val() )
		{
			if( "rand" == $("#file_gallery_orderby").val() )
			{
				$("#file_gallery_order").css({display : "none"});
				order = "";
			}
			else
			{
				$("#file_gallery_order").css({display : "inline"});
			}
			
			orderby = ' orderby="' + $("#file_gallery_orderby").val() + '"';
		}
		else
		{
			$("#file_gallery_order").css({display : "none"});
			order 	= "";
			orderby = "";
		}
	
	
	
		// start file gallery
		file_gallery.init();
	
	
	
		/* === BINDINGS === */
		

		
	/**
	 * uploader
	 * thanks to http://stackoverflow.com/questions/7110353/html5-dragleave-fired-when-hovering-a-child-element
	 */ 
		$('#file_gallery').live(
		{
			dragenter: function()
			{
				if( ! file_gallery.uploader_dragdrop )
					return;
				
				if( 0 < $("#file_gallery_upload_area").length && false === file_gallery.upload_inside )
				{
					$("#file_gallery").addClass("uploader");
					$("#fg_container").css({ minHeight: "350px"	});
	
					$("#file_gallery_upload_area").css({
						top: "5px", 
						width: $("#file-gallery-content").width() + "px", 
						height: $("#file-gallery-content").height() + "px",
						minHeight: "350px",
						backgroundImage: $("#file_gallery").css("backgroundImage")
					});
	
					file_gallery.upload_inside = true;
				}
			},
			
			dragleave: function(e)
			{
				if( ! file_gallery.uploader_dragdrop )
					return;
				
				var related = e.relatedTarget,
					inside = false;
			
				if( null === related ) // webkit
					related = e.target;
				
				if( related !== this )
				{
					if( related )
					{
						if( $.contains(this, related) || $.contains($("#file_gallery_tinymce_upload"), related) )
							inside = true;
					}
				}
				else
				{
					if( null === e.relatedTarget ) // webkit
						inside = false;
				}
				
				if( ! inside && 0 < $("#file_gallery_upload_area").length && true === file_gallery.upload_inside )
				{
					$("#file_gallery_upload_area").css({top: "-9999em"});
					$("#fg_container").css({ minHeight: 0 });
					$("#file_gallery").removeClass("uploader");
			
					file_gallery.upload_inside = false;
				}
			}
		});
		
		file_gallery.upload_handle_error = function(error, uploader)
		{
			// console.log(error);
		}
		
		
	
		$("#file_gallery_linkclass, #file_gallery_imageclass, #file_gallery_galleryclass, #file_gallery_mimetype, #file_gallery_limit, #file_gallery_offset, #file_gallery_external_url, #file_gallery_single_linkclass, #file_gallery_single_imageclass, #file_gallery_single_external_url, #fg_gallery_tags, #file_gallery_postid, #file_gallery_mimetype, #file_gallery_linkrel_custom").live('keypress keyup', function(e)
		{
			// on enter
			if ( 13 === e.which || 13 === e.keyCode )
			{
				file_gallery.serialize();
				
				if( "file_gallery_limit" == $(this).attr("id") )
				{
					if( 0 < Number($(this).val()) )
						$("#file_gallery_paginate_label").show();
					else
						$("#file_gallery_paginate_label").hide();
				}
				
				return false;
			}
		});
	
		
		$("#fgae_post_alt, #fgae_post_title, #fgae_post_excerpt, #fgae_tax_input, #fgae_menu_order").live('keypress keyup', function(e)
		{
			if ( 13 === e.which || 13 === e.keyCode ) // on enter
			{
				$("#file_gallery_edit_attachment_save").trigger("click");
				e.preventDefault();
				return false;
			}
			else if( 27 === e.which || 27 === e.keyCode ) // on esc
			{
				$("#file_gallery_edit_attachment_cancel").trigger("click");
			}
		});
	
		$("a.post_thumb_status").live("click", function()
		{
			var what = false;
			
			if( $(this).parent().hasClass("post_thumb") )
				what = true;
			
			return file_gallery.set_post_thumb($(this).attr("rel"), what);
		});
			
		$("#remove-post-thumbnail").attr("onclick", "").live("click.file_gallery", function()
		{		
			if( 0 < $(".sortableitem.post_thumb").length )
				return file_gallery.set_post_thumb($(".sortableitem.post_thumb").attr("id").split("-").pop(), true);
	
			WPRemoveThumbnail(file_gallery.options.post_thumb_nonce);
			
			return false;
		});
		
		$("#file_gallery_copy_all_form").bind("submit", function(){ return false; });
	
	
		// copy all attachments from another post
		$("#file_gallery_copy_all").live("click", function()
		{
			$("#file_gallery_copy_all_dialog").dialog("open");
		});
		
		
		// toggle fieldsets
		$("#file_gallery_hide_gallery_options, #file_gallery_hide_single_options, #file_gallery_hide_acf").live("click", function()
		{
			file_gallery.fieldset_toggle( $(this).attr("id") );
		});
	
	
	/* attachment edit screen */
		
		// save attachment
		$("#file_gallery_edit_attachment_save").live("click", function()
		{
			var attachment_data =
			{
				id : $('#fgae_attachment_id').val(),
				alt : $('#fgae_post_alt').val(),
				title : $('#fgae_post_title').val(),
				excerpt : $('#fgae_post_excerpt').val(),
				content : $('#fgae_post_content').val(),
				tax_input : $('#fgae_tax_input').val(),
				menu_order : $('#fgae_menu_order').val(),
				custom_fields : file_gallery.get_attachment_custom_fields()
			};
			
			return file_gallery.save_attachment( attachment_data );
		});
		
		// cancel changes
		$("#file_gallery_edit_attachment_cancel").live("click", function()
		{
			return file_gallery.init('return_from_single_attachment');
		});
		
		// acf enter on new field name
		$("#new_custom_field_key").live("keypress keyup", function(e)
		{
			if ( 13 === e.which || 13 === e.keyCode ) // on enter
			{
				$("#new_custom_field_submit").trigger("click");
				e.preventDefault();
			}
		});
	
	
	/* thumbnails */
		
		// attachment thumbnail click
		$("#fg_container .fgtt, #fg_container .checker_action").live("click.file_gallery", function()
		{
			var p = $(this).parent(),
				c = "#att-chk-" + p.attr("id").replace("image-", "");
			
			p.toggleClass("selected");
			$(c).prop("checked", $(c).prop("checked") ? false : true).change();
		});
		
		// attachment thumbnail double click
		$("#fg_container .fgtt, #fg_container .checker_action").live("dblclick", function()
		{
			file_gallery.edit( $(this).parent("li:first").attr("id").replace("image-", "") );
		});
		
		// edit attachment button click
		$("#fg_container .img_edit").live("click", function()
		{
			return file_gallery.edit( $(this).attr("id").replace('in-', '').replace('-edit', '') );
		});
	
		// zoom attachment button click
		$("#fg_container .img_zoom, .attachment_edit_thumb").live("click", function()
		{
			return file_gallery.zoom( this );
		});
	
		// delete or detach single attachment link click
		$("#fg_container .delete_or_detach_link").live("click", function()
		{
			var id = $(this).attr("rel"),
				 a = '#detach_or_delete_' + id,
				 b = '#detach_attachment_' + id,
				 c = '#del_attachment_' + id;
	
			if( $(a).is(":hidden") && $(b).is(":hidden") && $(c).is(":hidden") )
				$(a).fadeIn(100);
			else
				$(a + ", " + b + ", " + c).fadeOut(100);
			
			return false;
		});
			
		// detach single attachment link click
		$("#fg_container .do_single_detach").live("click", function()
		{
			var id = $(this).attr("rel");
			
			$('#detach_or_delete_' + id).fadeOut(250);
			$('#detach_attachment_' + id).fadeIn(100);
			
			return false;
		});
			
		// delete single attachment link click
		$("#fg_container .do_single_delete").live("click", function()
		{
			var id = $(this).attr("rel");
			
			if( $("#image-" + id).hasClass("has_copies") )
				return file_gallery.delete_dialog( id, true );
	
			$('#detach_or_delete_' + id).fadeOut(100);
			$('#del_attachment_' + id).fadeIn(100);
	
			return false;
		});	
			
		// delete single attachment link confirm
		$("#fg_container .delete").live("click", function()
		{
			var id = $(this).parent("div").attr("id").replace(/del_attachment_/, "");
			
			if( $("#image-" + id).hasClass("copy") )
				$("#file_gallery_delete_what").val("data_only");
			else
				$("#file_gallery_delete_what").val("all");
	
			return file_gallery.delete_dialog( id, true );
		});
			
		// delete single attachment link confirm
		$("#fg_container .detach").live("click", function()
		{
			return file_gallery.detach_attachments( $(this).parent("div").attr("id").replace(/detach_attachment_/, ""), false );
		});
		
		// delete / detach single attachment link cancel
		$("#fg_container .delete_cancel, #fg_container .detach_cancel").live("click", function()
		{
			 $(this).parent("div").fadeOut(250);
			 return false;
		});
	
	
	/* send gallery or single image(s) to editor */
		
		$("#file_gallery_send_gallery_legend, #file_gallery_send_single_legend").live("click", function(e)
		{
			file_gallery.send_to_editor( $(this).attr("id") );
		});
	
	
	/* main menu buttons */
	
		// refresh attachments button click
		$("#file_gallery_refresh").live("click", function()
		{
			 file_gallery.init( "refreshed" );
		});
		
		// resort attachments button click
		$("#file_gallery_attachments_sort_submit").live("click", function()
		{
			 file_gallery.init( "sorted" );
		});
		
		// delete checked attachments button click
		$("#file_gallery_delete_checked").live("click", function()
		{
			file_gallery.delete_dialog( $("#data_collector_checked").val() );
		});
			
		// detach checked attachments button click
		$("#file_gallery_detach_checked").live("click", function()
		{
			file_gallery.detach_attachments($("#data_collector_checked").val(), file_gallery.L10n.sure_to_detach);
		});
		
		// save attachments menu order button click
		$("#file_gallery_save_menu_order, #file_gallery_save_menu_order_link").live("click", function(e)
		{
			file_gallery.save_menu_order();
			
			e.preventDefault();
			return false;
		});
			
		// check all attachments button click
		$("#file_gallery_check_all").live("click", function()
		{
			if( $("#data_collector_checked").val() != $("#data_collector_full").val() )
			{
				$("#fg_container .sortableitem .checker").map(function()
				{
					$(this).parents(".sortableitem").addClass("selected");
					return this.checked = true;
				});
				
				file_gallery.serialize();
			}
		});
			
		// uncheck all attachments button click
		$("#file_gallery_uncheck_all").live("click click_tinymce_gallery", function(e)
		{
			if( "" != $("#data_collector_checked").val() )
			{
				$("#fg_container .sortableitem .checker").map(function()
				{
					$(this).parents(".sortableitem").removeClass("selected");
					return this.checked = false;
				});
			}
			
			// with serialization if tinymce gallery placeholder isn't clicked
			if( "click" === e.type ) {
				file_gallery.serialize();
			}
		});
		
	
	/* other bindings */
		
		// bind dropdown select boxes change to serialize attachments list
		$("#file_gallery_size, #file_gallery_linkto, #file_gallery_orderby, #file_gallery_order, #file_gallery_template, #file_gallery_single_linkto, #fg_container .sortableitem .checker, #file_gallery_columns, #file_gallery_linkrel,  #file_gallery_paginate, #file_gallery_linksize").live("change", function()
		{
			file_gallery.serialize();
		});
		
		// tags from current post only checkbox, switch to tags button
		$("#fg_gallery_tags_from, #file_gallery_switch_to_tags").live("click", function()
		{
			file_gallery.serialize();
		});
		
		// blur binding for text inputs and dropdown selects
		$("#fg_gallery_tags, #file_gallery_linkclass, #file_gallery_imageclass, #file_gallery_galleryclass, #file_gallery_single_linkclass, #file_gallery_single_imageclass, #file_gallery_single_external_url, #file_gallery_external_url, #file_gallery_postid, #file_gallery_limit").live("blur", function()
		{
			file_gallery.serialize();
		});
	
		// whether to show tags or list of attachments
		$("#file_gallery_switch_to_tags").live("click", function()
		{
			file_gallery.files_or_tags( false );
		});
			
		// clickable tag links
		$(".fg_insert_tag").live("click", function()
		{
			return file_gallery.add_remove_tags( this );
		});
		
		
		
		// alternative display mode, with smaller thumbs and attachment titles
		$("#file_gallery_toggle_textual").live("click", function()
		{
			var label = $(this).val();
			
			$("#file_gallery_list").toggleClass("textual");
			$(this).prop("disabled", true);
			
			$.post
			(
				ajaxurl, 
				{
					"action" : "file_gallery_toggle_textual",
					"state" : $("#file_gallery_list").hasClass("textual") ? 1 : 0,
					"_ajax_nonce" : file_gallery.options.file_gallery_nonce
				},
				function( response )
				{
					$("#file_gallery_toggle_textual").prop("disabled", false);
				}
			);
		});
		
		if( file_gallery_options.wp_version >= 3.5 )
		{
			jQuery('.media-modal-close').live("click", function(e)
			{
				file_gallery.tinymce_deselect( true );
				file_gallery.init();
			});
		}
		else
		{
			// thickbox window closed
			// WP >= 3.3
			if( "function" === typeof(jQuery.fn.on) )
			{
				jQuery(document.body).on("tb_unload", "#TB_window", function(e)
				{
					file_gallery.tinymce_deselect( true );
					file_gallery.init();
				});
			}
			else // WP < 3.3
			{
				jQuery('#TB_window').live("unload", function(e)
				{
					file_gallery.tinymce_deselect( true );
					file_gallery.init();
				});
			}
		}
	}

	// min/max-width/height adjustments for post thumbnails on edit.php screens
	if( 0 < $(".column-post_thumb").length )
	{		
		$(window).bind("load resize", function()
		{
			file_gallery.post_edit_screen_adjust();
		});
	}
	
	// regenerate thumbnails
	$("a.file_gallery_regenerate").live("click", function(e)
	{
		var id = $(this).attr("id").split(/-/).pop();
		
		file_gallery.regenerate_thumbnails( [id] );
		
		e.preventDefault();
		return false;
	});
});


// --------------------------------------------------------- //


/**
 * thanks to http://soledadpenades.com/2007/05/17/arrayindexof-in-internet-explorer/
 */
if( ! Array.indexOf )
{
	Array.prototype.indexOf = function(obj)
	{
		var l = this.length, i;
		
		for( i=0; i<l; i++ ){
			if( this[i] == obj )
				return i;
		}
		
		return -1;
	}
}


/**
 * thanks to http://phpjs.org/functions/strip_tags:535
 */
function strip_tags (input, allowed)
{
	allowed = (((allowed || "") + "")
		.toLowerCase()
		.match(/<[a-z][a-z0-9]*>/g) || [])
		.join(''); // making sure the allowed arg is a string containing only tags in lowercase (<a><b><c>)
	
	var tags = /<\/?([a-z][a-z0-9]*)\b[^>]*>/gi,
		commentsAndPhpTags = /<!--[\s\S]*?-->|<\?(?:php)?[\s\S]*?\?>/gi;

	return input.replace(commentsAndPhpTags, '').replace(tags, function($0, $1)
	{
		return allowed.indexOf('<' + $1.toLowerCase() + '>') > -1 ? $0 : '';
	});
}