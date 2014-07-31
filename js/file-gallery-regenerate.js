jQuery(document).ready(function ()
{
	var responseDiv = jQuery('<div id="file_gallery_regenerate_response" style="display: none; opacity: 0;"></div>');
	
	jQuery("#wpbody-content h2 .add-new-h2").after(responseDiv);

	jQuery("#the-list").on("click", ".file_gallery_regenerate_link", function (event)
	{
		var data = {
			action: "file_gallery_regenerate_thumbnails",
			attachment_ids: [this.id.split("-").pop()],
			_ajax_nonce: file_gallery_regenerate_nonce
		};

		var row = jQuery("#post-" + data.attachment_ids[0] + " .media-icon");
		var rowImg = row.find("img").first();

		row.addClass("regenerating");
		rowImg.fadeTo(100, 0.25);

		responseDiv.stop(false, false)
			.css({opacity: 1, display: "inline"})
			.text(file_gallery_regenerate_L10n);

		jQuery.post(ajaxurl, data, function (response)
		{
			row.removeClass("regenerating")
			rowImg.fadeTo(100, 0, function(){ jQuery(rowImg).fadeTo(100, 1);	});
			responseDiv.stop(false, false)
				.css({opacity: 1, display: "inline"})
				.text(response.message)
				.fadeOut(7000);
		}, "json");

		event.preventDefault();
		return false;
	});
});