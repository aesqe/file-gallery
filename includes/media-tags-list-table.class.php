<?php

require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
require_once(ABSPATH . 'wp-admin/includes/class-wp-terms-list-table.php');

/**
 * Modifies the media tag links on edit-tags.php page (edit.php -> upload.php)
 *
 * @since 1.6.5.3
 */
class File_Gallery_Media_Tags_List_Table extends WP_Terms_List_Table
{
	function column_posts( $tag )
	{
		global $taxonomy;

		$count = number_format_i18n( $tag->count );
		$tax   = get_taxonomy( $taxonomy );
		$args  = array( $tax->query_var => $tag->slug );

		return '<a href="' . add_query_arg( $args, 'upload.php' ) . '">' . $count . '</a>';
	}
}


function file_gallery_filter_terms_list_table( $class )
{
	if( FILE_GALLERY_MEDIA_TAG_NAME == $_GET['taxonomy'] )
		$class = 'File_Gallery_Media_Tags_List_Table';
	
	return $class;
}
add_filter('get_list_table_WP_Terms_List_Table', 'file_gallery_filter_terms_list_table');

