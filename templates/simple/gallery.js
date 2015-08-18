jQuery(document).ready(function()
{
	var file_gallery_simple_gallery_counter = 1;

	if( 0 < jQuery(".gallery.simple").length )
	{
		var file_gallery_doing_ajax = false;

		// remove the clearing element
		jQuery(".gallery.simple br.clear").remove();
		
		// go through each gallery...
		jQuery(".gallery.simple").each(function()
		{
			var file_gallery_simple_current_image = 1,
				id = "#" + jQuery(this).attr("id"),
				linkrel = jQuery(id + " .gallery-item a:first").attr("rel");
			
			if( linkrel )
				linkrel = ' rel="' + linkrel + '"';
			
			// and each item in gallery
			jQuery(id + " .gallery-item").each(function()
			{
				jQuery(this).children('.gallery-caption').remove();
				
				// if it's the first run...
				if( 1 === file_gallery_simple_current_image )
				{										
					var current_anchor = jQuery(this).find("a:first"),
						diff = jQuery(this).find("span.diff:first").text().split(file_gallery_simple_diff_sep),
						ext_regex = new RegExp("\." + diff[1] + "$"), // .jpg or .png...
						current_image_href = current_anchor.attr("href"),
						current_image_src = current_image_href.replace(ext_regex, diff[0] + '.' + diff[1]),
						current_caption = decodeURIComponent((current_anchor.attr("title") || "").replace(/\+/g, ' '));

					// add two containers, one for the thumbnails on the right and the other one for the bigger image on the left
					jQuery(id).prepend('<div class="gallery_simple_thumbnails"></div>').prepend('<div class="gallery_simple_current"></div>');

					// append the linked bigger image and its caption on the left
					jQuery(id + " .gallery_simple_current").append('<img src="' + file_gallery_loading_img + '" width="16" height="16" alt="" class="file_gallery_simple_loading" style="display: none; border: none;" /><img src="' + current_image_src + '" class="gallery_simple_current_image ' + file_gallery_simple_linkclass + '-' + file_gallery_simple_gallery_counter + '" style="display: none;" /><div class="gallery_simple_current_image_caption"><p>' + current_caption + '</p></div>');
					
					if( "" != file_gallery_simple_link )
					{
						jQuery(".gallery_simple_current_image").wrap('<a href="' + current_image_href + '" title="' + strip_tags(current_caption) + '"' + linkrel +'></a>');

						var lightbox = ("thickbox" == file_gallery_simple_linkclass && "function" === typeof(tb_init)) || jQuery.isFunction( jQuery.fn[file_gallery_simple_linkclass] ) ? true : false;

						// check for lightbox scripts						
						if( lightbox )
						{
							jQuery(id + " .gallery_simple_current a").addClass("colorbox" != file_gallery_simple_linkclass ? file_gallery_simple_linkclass : "cboxElement");

							if( "thickbox" != file_gallery_simple_linkclass )
								eval("jQuery('" + id + " .gallery_simple_current a')." + file_gallery_simple_linkclass + "()");
						}
					}
					
					// and fade in the image and its caption
					jQuery(id + " .gallery_simple_current_image_caption, " + id + " .gallery_simple_current_image").css({"opacity" : 0}).fadeTo(500, 1);
				}

				// move all gallery items into the thumbnails container
				jQuery(this).appendTo(id + " .gallery_simple_thumbnails");
				// advance .gallery-item counter
				file_gallery_simple_current_image++;
			});
			
			// advance gallery counter
			file_gallery_simple_gallery_counter++;
		});

		jQuery(".gallery_simple_thumbnails a").attr("rel", "").removeClass(file_gallery_simple_linkclass);

		// bind a function to each thumbnail link to replace the bigger image on the left
		jQuery("body").on("click", ".gallery.simple .gallery-item a", function(e)
		{
			if( file_gallery_doing_ajax )
				return false;
			
			// ajax (not technically, but hey:)) in progress
			file_gallery_doing_ajax = true;
			
			var id = "#" + jQuery(this).parents(".gallery").attr("id"),
				diff = jQuery(this).parent().find("span.diff:first").text().split(file_gallery_simple_diff_sep),
				ext_regex = new RegExp("\." + diff[1] + "$"), // .jpg or .png...
				new_href = jQuery(this).attr("href"),
				new_src = new_href.replace(ext_regex, diff[0] + '.' + diff[1]),
				new_caption = decodeURIComponent((jQuery(this).attr("title") || "").replace(/\+/g, ' ')),
				new_img = new Image();

			// when the new bigger image is loaded...
			jQuery(new_img).load(function()
			{
				// fade out the loading animation
				jQuery(id + " .file_gallery_simple_loading").fadeTo(250, 0, function()
				{
					// wait for the loading animation to fade out and then replace caption text and fade it in
					jQuery(id + " .gallery_simple_current_image_caption p").html(new_caption).parent().fadeTo(250, 1);
					// replace bigger image source and fade it in, then replace link location and image caption
					jQuery(id + " .gallery_simple_current_image").attr("src", new_src).fadeTo(250, 1);
					
					if( "" != file_gallery_simple_link )
						jQuery(id + " .gallery_simple_current_image").parents("a").attr("href", new_href).attr("title", new_caption);
					
					// ajax no longer in process
					file_gallery_doing_ajax = false;
				});
			});
			
			new_img.src = new_src;
			
			// fade in the loading animation while fading out the old image and its caption
			jQuery(id + " .file_gallery_simple_loading").css({"opacity" : 0}).fadeTo(250, 1);
			jQuery(id + " .gallery_simple_current_image_caption, " + id + " .gallery_simple_current_image").fadeTo(250, 0);

			if( new_img.complete )
				jQuery(new_img).trigger("load");

			e.preventDefault();
			return false;
		});
	}
});


/**
 * thanks to http://phpjs.org/functions/strip_tags:535
 */
function strip_tags (input, allowed) {
   allowed = (((allowed || "") + "")
	  .toLowerCase()
	  .match(/<[a-z][a-z0-9]*>/g) || [])
	  .join(''); // making sure the allowed arg is a string containing only tags in lowercase (<a><b><c>)
   var tags = /<\/?([a-z][a-z0-9]*)\b[^>]*>/gi,
	   commentsAndPhpTags = /<!--[\s\S]*?-->|<\?(?:php)?[\s\S]*?\?>/gi;
   return input.replace(commentsAndPhpTags, '').replace(tags, function($0, $1){
	  return allowed.indexOf('<' + $1.toLowerCase() + '>') > -1 ? $0 : '';
   });
}