if( "undefined" == typeof(file_gallery) )
	var file_gallery = { options : file_gallery_options };

jQuery(document).ready(function()
{
	function file_gallery_clear_cache_manual()
	{
		jQuery('#file_gallery_response').stop().fadeTo(0, 1).html('<img src="' + ajaxurl.split("/admin-ajax.php").shift() + '/images/loading.gif" width="16" height="16" alt="loading" id="fg_loading_on_bar" />').show();
		
		jQuery.post
		(
			ajaxurl, 
			{
				action		: "file_gallery_clear_cache_manual",
				_ajax_nonce	: file_gallery.options.clear_cache_nonce
			},
			function(response)
			{
				jQuery('#file_gallery_response').html(response).fadeOut(7500);
			},
			"html"
		);
	}
	
	jQuery("#file_gallery_clear_cache_manual").live("click", function()
	{
		file_gallery_clear_cache_manual();
		
		return false;
	});
});