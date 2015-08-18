var file_gallery_acf =
{
	L10n : file_gallery_acf_L10n,
	options : file_gallery_acf_options
};

jQuery(document).ready(function()
{
	"use strict";

	var admin_url = ajaxurl.split("/wp-admin").shift() + "/wp-admin",
		file_gallery_acf_custom_field_num = 1;
	
	jQuery("#media-single-form tbody tr").each(function()
	{
		if( -1 !== jQuery.inArray(jQuery(this).attr("class"), file_gallery_acf.options.custom_fields) )
		{
			var delete_button = '<input class="button-secondary acf_delete_custom_field" type="button" value="Delete" name="acf_delete_custom_field_' + jQuery(this).attr("class") + '" />';
			jQuery(this).children(".field").append(delete_buton).addClass("custom_field");
		}
	});

	// add new custom field
	jQuery("body").on("click", "#new_custom_field_submit", function(e)
	{
		var key = jQuery("#new_custom_field_key").val(),
			value = jQuery("#new_custom_field_value").val(),
			attachment_id = jQuery("#attachment_id").val() || jQuery("#fgae_attachment_id").val();

		if( key != "" )
		{
			jQuery.post
			(
				ajaxurl,
				{
					action: "file_gallery_add_new_attachment_custom_field",
					attachment_id: attachment_id,
					key: key,
					value: value,
					_ajax_nonce: file_gallery_acf.options.add_new_attachment_custom_field_nonce
				},
				function(response)
				{
					if( 0 < Number(response) )
					{
						jQuery(".acf_new_custom_field")
							.before('<tr class="' + key + '" id="acf_' + file_gallery_acf_custom_field_num + '"><th valign="top" class="label" scope="row"><label for="attachments[' + attachment_id + '][' + key + ']"><span class="alignleft">' + key + '</span><br class="clear" /></label></th><td class="field custom_field"><textarea name="attachments[' + attachment_id + '][' + key + ']" id="attachments[' + attachment_id + '][' + key + ']">' + value + '</textarea><input class="button-secondary acf_delete_custom_field" type="button" value="Delete" name="acf_delete_custom_field_' + key + '" /></td></tr>');
						
						jQuery("#acf_" + file_gallery_acf_custom_field_num).fadeTo(0, 0).css({"visibility" : "visible", "backgroundColor":"#FFFF88"}).fadeTo(250, 1).animate({"backgroundColor" : "#F9F9F9"}, 250);
						
						file_gallery_acf_custom_field_num++;
					}
					else
					{
						alert(file_gallery_acf.L10n.error_adding_attachment_custom_field);
					}
				},
				"html"
			);
		}
		
		e.preventDefault();
		return false;
	});
	
	
	// delete a custom field
	jQuery("body").on("click", ".file_gallery_acf_delete_custom_field", function()
	{
		var that = jQuery(this),
			row = that.parents("tr"),
			key = that.attr("name").replace(/file_gallery_acf_delete_custom_field_/, "");

		jQuery.post
		(
			ajaxurl,
			{
				action: "file_gallery_delete_attachment_custom_field",
				attachment_id:	jQuery("#attachment_id").val() || jQuery("#fgae_attachment_id").val(),
				key: key,
				value: jQuery(".fgacf_" + key + " textarea").val(),
				_ajax_nonce: file_gallery_acf.options.delete_attachment_custom_field_nonce
			},
			function(response)
			{
				response = Number(response);

				if( response === 1 ) {
					row.css({"backgroundColor": "#FF8888"}).fadeTo(250, 0, function(){ row.remove(); });
				}
				else if( response === 0 ) {
					alert(file_gallery_acf.L10n.error_deleting_attachment_custom_field);
				}
				
				return;
			},
			"html"
		);
	});
});