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
	}