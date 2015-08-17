<?php
	$starttag = 'div';
	$cleartag = "\n<br class='clear' />\n";

	if( isset($overriding) && $overriding === true ) {
		return;
	}
?>
<<?php echo $itemtag; ?> class="gallery-item<?php echo $startcol . $endcol; ?>">
	<<?php echo $icontag; ?> class="gallery-icon"><?php 
	if( ! empty($link) ) :
	?><a href="<?php echo $link; ?>" title="<?php echo $title; ?>"<?php if( ! empty($link_class) ) : ?> class="<?php echo $link_class; ?>"<?php endif; ?><?php if( ! empty($rel) ) : ?> rel="<?php echo $rel; ?>"<?php endif; ?>><?php endif; ?>
			<img src="<?php echo $thumb_link; ?>" width="<?php echo $thumb_width; ?>" height="<?php echo $thumb_height; ?>" title="<?php echo $title; ?>" class="attachment-<?php echo $size ?><?php if( ! empty($image_class) ){ echo ' ' . $image_class;} ?>" alt="<?php if( $thumb_alt ){ echo $thumb_alt; }else{ echo $title; }?><?php ?>" /><?php
		if( ! empty($link) ) :
	?></a><?php 
	endif;
?>	</<?php echo $icontag; ?>>
	<?php if( ! empty($caption) ) :
?>	<<?php echo $captiontag; ?> class="wp-caption-text gallery-caption">
		<?php echo $caption; ?>
	</<?php echo $captiontag; ?>><?php 
	endif; ?>
</<?php echo $itemtag; ?>>
