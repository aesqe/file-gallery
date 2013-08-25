<?php
	$starttag = 'div';
	$cleartag = "\n<br class='clear' />\n";
	$mimetype = 'image';
	$diff_sep = ';#;#;';

	if( isset($attachment_id) )
	{
		$intermediate_size = wp_get_attachment_image_src($attachment_id, 'medium');
		$diff = file_gallery_filename_end_diff($link, $intermediate_size[0]);
	}

	if( isset($js_dependencies) )
		array_unshift($js_dependencies, 'jquery');
	
	if( isset($overriding) && true === $overriding )
		return;
	
	if( 1 === $file_gallery_this_template_counter )
	{
		echo 
'<script type="text/javascript">
	var file_gallery_loading_img = "' . FILE_GALLERY_URL . '/images/loading.gif",
		file_gallery_simple_linkclass = "' .  $link_class. '",
		file_gallery_simple_link = "' . $link . '";
		file_gallery_simple_diff_sep = "' . $diff_sep . '";
</script>';
	}
?>
<dl class="gallery-item<?php echo $endcol; ?>">
	<dt class="gallery-icon">
    	<span class="diff" style="position: absolute; top: -9999em; left: -em; width: 0; height: 0;"><?php echo $diff[0] . $diff_sep . $diff[1]; ?></span>
		<a href="<?php echo $link; ?>" title="<?php echo urlencode($caption); ?>"<?php if( ! empty($link_class) ) : ?> class="<?php echo $link_class; ?>"<?php endif; ?><?php if( ! empty($rel) ) : ?> rel="<?php echo $rel; ?>"<?php endif; ?>><img src="<?php echo $thumb_link; ?>" width="<?php echo $thumb_width; ?>" height="<?php echo $thumb_height; ?>" title="<?php echo $title; ?>" class="attachment-<?php echo $size ?><?php if( ! empty($image_class) ){ echo ' ' . $image_class;} ?>" alt="<?php if( $thumb_alt ){ echo $thumb_alt; }else{ echo $title; }?><?php ?>" /></a>
	</dt>
	<?php if( ! empty($caption) ) : ?>
	<dd class="gallery-caption">
		<?php echo $caption; ?>
	</dd>
	<?php endif; ?>
</dl>
