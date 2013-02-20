<?php

/**
 * does exactly what its name says :)
 *
 * thanks to merlinyoda at dorproject dot net
 * http://php.net/manual/en/function.array-diff.php
 */
if( ! function_exists("array_xor") )
{
	function array_xor( $array_a, $array_b )
	{
		$union_array = array_merge($array_a, $array_b);
		$intersect_array = array_intersect($array_a, $array_b);
		return array_diff($union_array, $intersect_array);
	}
}


/**
 * checks if an array is just full of empty strings
 *
 * http://bytes.com/topic/php/answers/456092-slick-way-check-if-array-contains-empty-elements
 */
if( ! function_exists("empty_array") )
{
	function empty_array( $array = array() )
	{
		foreach ($array as $a)
		{
			if ( 0 < strlen($a) )
				return false;
		}
		
		return true;
	}
}


/**
 * Returns intermediate size file name ending difference
 */
function file_gallery_filename_end_diff( $ori, $size )
{
	$ext = explode('.', $ori);
	$ext = end($ext);
	
	$ori  = str_replace('.' . $ext, '', $ori);
	$size = str_replace('.' . $ext, '', $size);
	
	return array(str_replace($ori, '', $size), $ext);
}

