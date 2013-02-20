<?php

/*
 * @since 1.5.8.
 *
 * Converts mime type shorthands into something WordPress' own 
 * wp_post_mime_type_where() function can easily convert to SQL
 */
function file_gallery_get_mime_type( $mimes )
{
	$mimes = array_map('trim', explode(',', $mimes));

	foreach($mimes as $mime)
	{
		if( false === strpos($mime, '*') && false === strpos($mime, '/') )
		{
			$mime_types = array(
				'word' => '*msword',
				'doc' => '*msword',
				'excel' => '*excel',
				'xls' => '*excel',
				'csv' => 'text/csv, text/comma-separated-values',
				'powerpoint' => '*powerpoint',
				'ppt' => '*powerpoint',
				'pdf' => '*pdf',
				'rar' => '*-rar*',
				'zip' => '*compress*, *zip',
				'7zip' => 'application/x-7z-compressed'
			);
			
			$mime_types = apply_filters('file_gallery_mime_types', $mime_types);
			
			if( isset($mime_types[$mime]) )
				$mime = $mime_types[$mime];
		}
			
		$out[] = $mime;
	}
	
	return implode(',', $out);
}

/**
 * returns descriptive document type
 * used for icons in attachment list
 * both in backend and frontend
 *
 * needs more options and maybe a different approach...
 * @deprecated since 1.7.4
 */
function file_gallery_get_file_type( $mime )
{
	return wp_mime_type_icon($mime);

	if( false !== strpos($mime, "text") || 
		false !== strpos($mime, "xhtml"))
	{
		$type = "text";
	}
	elseif( false !== strpos($mime, "excel") )
	{
		$type = "spreadsheet";
	}
	elseif( false !== strpos($mime, "powerpoint") )
	{
		$type = "interactive";
	}
	elseif( false !== strpos($mime, "code") )
	{
		$type = "code";
	}
	elseif( false !== strpos($mime, "octet-stream") )
	{
		$type = "interactive";
	}
	elseif( false !== strpos($mime, "audio") )
	{
		$type = "audio";
	}
	elseif( false !== strpos($mime, "video") )
	{
		$type = "video";
	}
	elseif( false !== strpos($mime, "stuffit") || 
			false !== strpos($mime, "compressed") || 
			false !== strpos($mime, "x-tar") ||
			false !== strpos($mime, "zip"))
	{
		$type = "archive";
	}
	elseif( false !== strpos($mime, "application") )
	{
		$type = "document";
	}
	else
	{
		$type = "default";
	}
	
	return apply_filters('file_gallery_get_file_type', $type, $mime);
}

