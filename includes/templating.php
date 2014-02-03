<?php

/**
 * Collects template names from theme folder
 */
function file_gallery_get_templates( $where = NULL )
{
	$options = get_option('file_gallery');
	
	if( isset($options['cache']) && true == $options['cache'] )
	{		
		$transient = 'filegallery_templates';
		$cache = get_transient($transient);
		
		if( $cache )
			return $cache;
	}

	$file_gallery_templates = array();
	
	// check if file gallery templates folder exists within theme folder
	if( is_readable(FILE_GALLERY_THEME_TEMPLATES_ABSPATH) )
	{
		$opendir = opendir(FILE_GALLERY_THEME_TEMPLATES_ABSPATH);
		
		while( false !== ($files = readdir($opendir)) )
		{
			if( '.' != $files && '..' != $files )
			{
				$tf = FILE_GALLERY_THEME_TEMPLATES_ABSPATH . '/' . $files;
		
				if( is_readable($tf . '/gallery.php') && is_readable($tf . '/gallery.css') )
					$file_gallery_templates[] = $files;
			}
		}
		
		closedir($opendir);
	}

	// check if file gallery templates folder exists within wp-content/file-gallery-templates folder
	if( is_readable(FILE_GALLERY_CONTENT_TEMPLATES_ABSPATH) )
	{
		$opendir = opendir(FILE_GALLERY_CONTENT_TEMPLATES_ABSPATH);
		
		while( false !== ($files = readdir($opendir)) )
		{
			if( '.' != $files && '..' != $files )
			{
				$tf = FILE_GALLERY_CONTENT_TEMPLATES_ABSPATH . '/' . $files;
		
				if( is_readable($tf . '/gallery.php') && is_readable($tf . '/gallery.css') )
					$file_gallery_templates[] = $files;
			}
		}
		
		closedir($opendir);
	}
	
	$file_gallery_templates = array_unique($file_gallery_templates);
	
	$default_templates = unserialize(FILE_GALLERY_DEFAULT_TEMPLATES);
	
	foreach( $default_templates as $df )
	{
		$file_gallery_templates[] = $df;
	}
	
	if( isset($options['cache']) && true == $options['cache'] )
		set_transient($transient, $file_gallery_templates, $options['cache_time']);

	return $file_gallery_templates;
}



/**
 * Injects CSS links via 'stylesheet_uri' filter, if mobile theme is active
 */
function file_gallery_mobile_css( $stylesheet_url )
{
	$options = get_option('file_gallery');
	
	if( isset($options['disable_shortcode_handler']) && true == $options['disable_shortcode_handler'] )
		return $stylesheet_url;

	file_gallery_css_front( true );
	
	$mobiles = maybe_unserialize(FILE_GALLERY_MOBILE_STYLESHEETS);

	if( !empty($mobiles) )
	{
		array_push($mobiles, $stylesheet_url);
		$glue = '" type="text/css" media="screen" charset="utf-8" />' . "\n\t" . '<link rel="stylesheet" href="';
		return implode($glue, $mobiles);
	}
	
	return $stylesheet_url;	
}


/**
 * Enqueues stylesheets for each gallery template
 */
function file_gallery_css_front( $mobile = false )
{
	global $wp_query, $file_gallery;

	$options = get_option('file_gallery');
	
	if( isset($options['disable_shortcode_handler']) && true == $options['disable_shortcode_handler'] )
		return;

	// if option to show galleries in excerpts is set to false
	if( ! is_singular() && ( ! isset($options['in_excerpt']) || true != $options['in_excerpt']) && false == $mobile )
		return;

	$gallery_matches = 0;
	$galleries = array();
	$missing = array();
	$mobiles = array();
	$columns_required = false;
	$default_templates = unserialize(FILE_GALLERY_DEFAULT_TEMPLATES);
	
	// check for gallery shortcode in all posts
	if( ! empty($wp_query->posts) )
	{
		foreach( $wp_query->posts as $post )
		{
			$m = preg_match_all("#\[gallery([^\]]*)\]#is", $post->post_content, $g);

			// if there's a match...
			if( false !== $m && 0 < $m )
			{
				$gallery_matches += $m;    // ...add the number of matches to global count...
				$galleries = array_merge($galleries, $g[1]); // ...and add the match to galleries array
			}
		}
	}
	
	// no matches...
	if( 0 === $gallery_matches )
		return;
	
	$aqs = array();
	// automaticaly enqueue predefined scripts and styles
	$aqs = explode(',', $options['auto_enqueued_scripts']);
	$aqs = array_map('trim', $aqs);
	$aq_linkclasses = array();
	$galleries_data = array();
	$j = 0;

	// collect template names
	foreach( $galleries as $gallery )
	{
		$galleries_data[$j] = array();

		if( false === $columns_required )
		{
			$zc = preg_match("#\columns=(['\"])0\\1#is", $gallery);
				
			if( false !== $zc && 0 < $zc ) // no error and match found
				$columns_required = false;
			else
				$columns_required = true;
		}
		
		$tm = preg_match("#\stemplate=(['\"])([^'\"]+)\\1#is", $gallery, $gm);

		if( isset($gm[2]) )
		{
			$templates[] = $gm[2];
			$galleries_data[$j]['template'] = $gm[2];
		}
			
		
		$gcm = preg_match("#\slinkclass=(['\"])([^'\"]+)\\1#is", $gallery, $gcg);

		if( isset($gcg[2]) && '' != $gcg[2] )
		{
			$glc = explode(' ', $gcg[2]);
			$galleries_data[$j]['linkclasses'] = array();

			foreach( $glc as $glcs )
			{
				$glcs = trim($glcs);

				if( in_array($glcs, $aqs) )
				{
					$aq_linkclasses[] = $glcs;
					$galleries_data[$j]['linkclasses'][] = $glcs;
				}
			}
		}

		$j++;
	}

	$aq_linkclasses = apply_filters('file_gallery_lightbox_classes', array_unique($aq_linkclasses));

	// auto enqueue scripts
	if( ! empty($aq_linkclasses) )
	{
		if( ! defined('FILE_GALLERY_LIGHTBOX_CLASSES') ) {
			define('FILE_GALLERY_LIGHTBOX_CLASSES', serialize($aq_linkclasses));
		}

		file_gallery_print_scripts( true );
	}
	
	if( empty($templates) )
	{
		// enqueue only the default stylesheet if no template names are found
		if( ! $mobile )
			wp_enqueue_style('file_gallery_default', FILE_GALLERY_DEFAULT_TEMPLATE_URL . '/gallery.css', false, FILE_GALLERY_VERSION);
		else
			$mobiles[] = FILE_GALLERY_DEFAULT_TEMPLATE_URL . '/gallery.css';
	}
	else
	{
		if( count($templates) < count($galleries) )
			$templates[] = 'default';

		// eliminate duplicate entries
		$templates = array_unique($templates);

		// if none of default templates are needed, don't include the 'columns.css' file
		if( array() == array_intersect($templates, $default_templates) )
			$columns_required = false;

		// walk through template names
		foreach($templates as $template)
		{
			$js_dependencies = array();

			foreach( $galleries_data as $gd )
			{
				if( isset($gd['template']) && $gd['template'] == $template )
				{
					foreach( $aq_linkclasses as $aql )
					{
						if( isset( $gd['linkclasses'] ) && in_array($aql, $gd['linkclasses']) ) {
							$js_dependencies[] = $aql;
						}
					}
				}
			}

			// check if file exists in theme's folder
			if( is_readable(FILE_GALLERY_THEME_TEMPLATES_ABSPATH . '/' . $template . '/gallery.css') )
			{
				if( ! $mobile )
					wp_enqueue_style('file_gallery_' . str_replace(' ', '-', $template), FILE_GALLERY_THEME_TEMPLATES_URL . '/' . str_replace(' ', '%20', $template) . '/gallery.css', false, FILE_GALLERY_VERSION);
				else
					$mobiles[] = FILE_GALLERY_THEME_TEMPLATES_URL . '/' . str_replace(' ', '%20', $template) . '/gallery.css';
				
				if( is_readable(FILE_GALLERY_THEME_TEMPLATES_ABSPATH . '/' . $template . '/gallery.js') )
				{
					$overriding = true;
					ob_start();
						include(FILE_GALLERY_THEME_TEMPLATES_ABSPATH . '/' . $template . '/gallery.php');					
					ob_end_clean();
					$overriding = false;

					wp_enqueue_script('file_gallery_' . str_replace(' ', '-', $template), FILE_GALLERY_THEME_TEMPLATES_URL . '/' . str_replace(' ', '%20', $template) . '/gallery.js', $js_dependencies, FILE_GALLERY_VERSION, true);	
				}
			}
			// check if file exists in wp-content/file-gallery-templates folder
			elseif( is_readable(FILE_GALLERY_CONTENT_TEMPLATES_ABSPATH . '/' . $template . '/gallery.css') )
			{
				if( ! $mobile )
					wp_enqueue_style('file_gallery_' . str_replace(' ', '-', $template), FILE_GALLERY_CONTENT_TEMPLATES_URL . '/' . str_replace(' ', '%20', $template) . '/gallery.css', false, FILE_GALLERY_VERSION);
				else
					$mobiles[] = FILE_GALLERY_CONTENT_TEMPLATES_URL . '/' . str_replace(' ', '%20', $template) . '/gallery.css';
				
				if( is_readable(FILE_GALLERY_CONTENT_TEMPLATES_ABSPATH . '/' . $template . '/gallery.js') )
				{
					$overriding = true;
					ob_start();
						include(FILE_GALLERY_CONTENT_TEMPLATES_ABSPATH . '/' . $template . '/gallery.php');					
					ob_end_clean();
					$overriding = false;

					wp_enqueue_script('file_gallery_' . str_replace(' ', '-', $template), FILE_GALLERY_CONTENT_TEMPLATES_URL . '/' . str_replace(' ', '%20', $template) . '/gallery.js', $js_dependencies, FILE_GALLERY_VERSION, true);	
				}
			}
			// check plugin templates folder
			elseif( is_readable(FILE_GALLERY_ABSPATH . "/templates/" . $template . "/gallery.css") )
			{				
				if( ! $mobile )
					wp_enqueue_style('file_gallery_' . $template, FILE_GALLERY_URL . '/templates/' . $template . '/gallery.css', false, FILE_GALLERY_VERSION);
				else
					$mobiles[] = FILE_GALLERY_URL . '/templates/' . $template . '/gallery.css';

				if( is_readable(FILE_GALLERY_ABSPATH . '/templates/' . $template . '/gallery.js') )
				{
					$overriding = true;
					ob_start();
						include(FILE_GALLERY_ABSPATH . '/templates/' . $template . '/gallery.php');
					ob_end_clean();
					$overriding = false;

					wp_enqueue_script('file_gallery_' . str_replace(' ', '-', $template), FILE_GALLERY_URL . '/templates/' . str_replace(' ', '%20', $template) . '/gallery.js', $js_dependencies, FILE_GALLERY_VERSION, true );
				}
			}
			// template does not exist, enqueue default one
			else
			{
				$missing[] = $template;
				wp_enqueue_style('file_gallery_default', FILE_GALLERY_URL . '/templates/default/gallery.css', false, FILE_GALLERY_VERSION);
				
				echo "\n<!-- " . __('file does not exist:', 'file-gallery') . ' ' . $template . '/gallery.css - ' . __('using default style', 'file-gallery') . "-->\n";
			}
		}
	}
	
	if( $columns_required )
	{
		if( ! $mobile )
			wp_enqueue_style('file_gallery_columns', FILE_GALLERY_URL . '/templates/columns.css', false, FILE_GALLERY_VERSION);
		else
			$mobiles[] = FILE_GALLERY_URL . '/templates/columns.css';
	}

	if( $mobile && ! defined('FILE_GALLERY_MOBILE_STYLESHEETS') )
		define('FILE_GALLERY_MOBILE_STYLESHEETS', serialize($mobiles));
}
//add_action('wp_enqueue_styles',  'file_gallery_css_front');
add_action('wp_enqueue_scripts', 'file_gallery_css_front');


/**
 * prints scripts and styles for auto enqueued linkclasses
 */
function file_gallery_print_scripts( $styles = false )
{
	$options = get_option('file_gallery');
	
	if( isset($options['disable_shortcode_handler']) && true == $options['disable_shortcode_handler'] )
		return;

	if( defined('FILE_GALLERY_LIGHTBOX_CLASSES') )
	{
		$linkclasses = maybe_unserialize(FILE_GALLERY_LIGHTBOX_CLASSES);

		if( ! empty($linkclasses) )
		{
			foreach( $linkclasses as $lc )
			{
				if( $styles )
				{
					wp_enqueue_style( $lc );
				}
				else
				{
					if( 'thickbox' == $lc )
					{
echo "\n" . 
'<script type="text/javascript">
	var tb_pathToImage = "' . includes_url() . 'js/thickbox/loadingAnimation.gif";
	var tb_closeImage  = "' . includes_url() . 'js/thickbox/tb-close.png";
</script>'
. "\n";
					}
					
					wp_enqueue_script( $lc );
				}
			}
		}
	}
}
add_action('wp_enqueue_scripts', 'file_gallery_print_scripts');


/**
 * For easy inline overriding of shortcode-set options
 *
 * @since 1.6.5.1
 */
function file_gallery_overrides( $args )
{
	global $file_gallery;

	if( is_string($args) )
		$args = wp_parse_args($args);
	
	$file_gallery->overrides = $args;
}


/**
 * Main shortcode function
 *
 * @since 0.1
 */
function file_gallery_shortcode( $content = false, $attr = false )
{
	global $file_gallery, $wpdb, $post;

	require_once('html5lib/Parser.php');

	// if the function is called directly, not via shortcode
	if( false !== $content && false === $attr )
		$attr = wp_parse_args($content);
		
	if( ! isset($file_gallery->gallery_id) )
		$file_gallery->gallery_id = 1;
	else
		$file_gallery->gallery_id++;
	
	$options = get_option('file_gallery');

	if( isset($options['cache']) && true == $options['cache'] )
	{
		if( 'html' == $attr['output_type'] || ( isset($options['cache_non_html_output']) && true == $options['cache_non_html_output'] ) )
		{
			$transient = 'filegallery_' . md5( $post->ID . "_" . serialize($attr) );
			$cache     = get_transient($transient);
			
			if( $cache )
				return $cache;
		}
	}

	// if option to show galleries in excerpts is set to false...
	// ...replace [gallery] with user selected text
	if( ! is_singular() && ( ! isset($options['in_excerpt']) || true != $options['in_excerpt']) )
		return $options['in_excerpt_replace_content'];
	
	$default_templates = unserialize(FILE_GALLERY_DEFAULT_TEMPLATES);
	
	// We're trusting author input, so let's at least make sure it looks like a valid orderby statement
	if( isset($attr['orderby']) )
	{
		$attr['orderby'] = sanitize_sql_orderby( $attr['orderby'] );
		
		if( ! $attr['orderby'] )
			unset($attr['orderby']);
	}
	
	$defaults = array(
		/* default values: */
		'order'				=> 'ASC',
		'orderby'			=> '',
		'id'				=> $post->ID,
		'columns'			=> 3,
		'size'				=> 'thumbnail',
		'link'				=> 'attachment',
		'include'			=> '',
		'ids'				=> '',
		'exclude'			=> '',

		/* added by file gallery: */
		'template'			=> 'default',
		'linkclass'			=> '',
		'imageclass'		=> '',
		'galleryclass'		=> '',
		'rel'				=> 1,
		'tags'				=> '',
		'tags_from'			=> 'current',
		'output_type'		=> 'html',
		'output_params'		=> 1,				// needed when outputting html
		'attachment_ids'	=> '',				// old alias of 'include' and 'ids'
		'mimetype'			=> '',
		'limit' 			=> -1,
		'offset'			=> -1,
		'paginate'			=> 0,
		'link_size'			=> 'full',
		'include_meta'		=> false
		//,'captions'           => true
	);
	
	if( floatval(get_bloginfo('version')) >= 3.5 ) {
		$defaults['link'] = 'post';
	}
	
	// extract the defaults...
	extract( shortcode_atts($defaults, $attr) );

	if( ! in_array($template, $default_templates) )
	{
		$template_file = FILE_GALLERY_THEME_TEMPLATES_ABSPATH . '/' . $template . '/gallery.php';
		
		if( ! is_readable($template_file) )
			$template_file = FILE_GALLERY_CONTENT_TEMPLATES_ABSPATH . '/' . $template . '/gallery.php';
	}
	else
	{
		if( 'default' == $template )
		{
			$template_file = FILE_GALLERY_DEFAULT_TEMPLATE_ABSPATH . '/gallery.php';
			$template      = FILE_GALLERY_DEFAULT_TEMPLATE_NAME;
		}
		else
		{
			$template_file = FILE_GALLERY_ABSPATH . '/templates/' . $template . '/gallery.php';
		}
	}

	// check if template exists and replace with default if it does not
	if( ! is_readable($template_file) )
	{
		$template_file = FILE_GALLERY_ABSPATH . '/templates/default/gallery.php';
		$template      = 'default';
	}
	
	// get overriding variables from the template file
	$overriding = true;
	ob_start();
		include($template_file);
	ob_end_clean();
	$overriding = false;

	if( is_array($file_gallery->overrides) && ! empty($file_gallery->overrides) )
	{
		extract($file_gallery->overrides);
		$file_gallery->overrides = NULL;
	}

	$limit  = (int) $limit;	
	$offset = (int) $offset;
	$page   = (int) get_query_var('page');

	// if( $captions === 'false' || $captions == '0' ) {
	// 	$captions = false;
	// }

	if( 'false' === $rel || (is_numeric($rel) && 0 === (int) $rel) )
		$_rel = false;
	elseif( 1 === $rel )
		$_rel = true;
	else
		$_rel = $rel;

	if( 'false' === $output_params || (is_numeric($output_params) && 0 === (int) $output_params) )
		$output_params = false;
	else
		$output_params = true;
	
	if( 'false' === $paginate || (is_numeric($paginate) && 0 === (int) $paginate) || 0 > $limit )
	{
		$paginate   = false;
		$found_rows = '';
	}
	else
	{
		$paginate   = true;
		$found_rows = 'SQL_CALC_FOUND_ROWS';
		
		if( 0 === $page )
			$page = 1;

		if( is_singular() && 1 < $page )
			$offset = $limit * ($page - 1);
	}
	
	$file_gallery->debug_add('pagination', compact('paginate', 'page'));

/**/
	$_attachment_ids = explode(',', trim($attachment_ids, ','));
	$_include = explode(',', trim($include, ','));
	$_ids = explode(',', trim($ids, ','));

	$attachment_ids = array_merge($_attachment_ids, $_include, $_ids);
	$attachment_ids = array_unique($attachment_ids);
	$attachment_ids = implode(',', $attachment_ids);
	$attachment_ids = trim($attachment_ids, ',');
	$attachment_ids = trim($attachment_ids);
/**/

	if( ! isset( $linkto ) )
		$linkto = $link;
	
	$sql_mimetype = '';
	
	if( '' != $mimetype )
	{
		$mimetype     = file_gallery_get_mime_type($mimetype);
		$sql_mimetype = wp_post_mime_type_where($mimetype);
	}

	$approved_attachment_post_statuses = apply_filters('file_gallery_approved_attachment_post_statuses', array('inherit'));
	$ignored_attachment_post_statuses  = apply_filters('file_gallery_ignored_attachment_post_statuses', array('trash', 'private', 'pending', 'future'));
	
	if( ! empty($approved_attachment_post_statuses) )
		$post_statuses = " AND (post_status IN ('" . implode("', '", $approved_attachment_post_statuses) . "') ) ";
	elseif( ! empty($ignored_attachment_post_statuses) )
		$post_statuses = " AND (post_status NOT IN ('" . implode("', '", $ignored_attachment_post_statuses) . "') ) ";
	else
		$post_statuses = "";
	
	$file_gallery_query = new stdClass();

	// start with tags because they negate everything else
	if( '' != $tags )
	{
		if( '' == $orderby || 'file_gallery' == $orderby )
			$orderby = "menu_order ID";

		$query = array(
			'post_status'		=> implode(',', $approved_attachment_post_statuses), 
			'post_type'			=> 'attachment', 
			'order'				=> $order, 
			'orderby'			=> $orderby,
			'posts_per_page'	=> $limit,
			'post_mime_type'	=> $mimetype,
			FILE_GALLERY_MEDIA_TAG_NAME => $tags
		);
		
		if( 'current' == $tags_from )
			$query['post_parent'] = $id;
		
		if ( ! empty($exclude) )
			$query['post__not_in'] = explode(',', preg_replace( '/[^0-9,]+/', '', $exclude ));
		
		if( 0 < $offset )
			$query['offset'] = $offset;

		$file_gallery_query = new WP_Query( $query );
		$attachments = $file_gallery_query->posts;

		unset($query);
	}
	elseif( '' != $attachment_ids )
	{
		$attachment_ids = explode(',', $attachment_ids);
		$sql_limit = count($attachment_ids);

		if( 'rand' == $orderby )
			shuffle($attachment_ids);
			
		$attachment_ids = implode(',', $attachment_ids);

		if( '' == $orderby || 'rand' == $orderby || $orderby == 'post__in' )
		{
			$orderby = sprintf("FIELD(ID, %s)", $attachment_ids);
			$order   = '';
		}
		elseif( 'title' == $orderby )
		{
			$orderby = "post_title";
		}
		
		$query = sprintf(
			"SELECT " . $found_rows . " * FROM $wpdb->posts 
			 WHERE ID IN (%s) 
			 AND post_type = 'attachment' 
			" . $post_statuses . " ", 
		$attachment_ids);
		
		$query .= $sql_mimetype;
		$query .= sprintf(" ORDER BY %s %s ", $orderby, $order);

		if( true !== $paginate )
			$limit = $sql_limit;
	}
	else
	{
		if( '' == $orderby )
			$orderby = "menu_order ID";

		$query = array(
			'post_parent'		=> $id,
			'post_status'		=> implode(',', $approved_attachment_post_statuses), 
			'post_type'			=> 'attachment', 
			'order'				=> $order, 
			'orderby'			=> $orderby,
			'posts_per_page'	=> $limit,
			'post_mime_type'	=> $mimetype
		);

		if ( ! empty($exclude) )
			$query['post__not_in'] = explode(',', preg_replace( '/[^0-9,]+/', '', $exclude ));
		
		if( 0 < $offset )
			$query['offset'] = $offset;

		$file_gallery_query = new WP_Query( $query );
		$attachments = $file_gallery_query->posts;

		unset($query);
	}
	
	if( isset($query) )
	{		
		if( 0 < $limit )
			$query .= " LIMIT " . $limit;
		
		if( 0 < $offset )
			$query .= " OFFSET " . $offset;

		$attachments = $wpdb->get_results( $query );

		if( '' != $found_rows )
		{
			$file_gallery_query->found_posts = $wpdb->get_var("SELECT FOUND_ROWS()");
			$file_gallery_query->max_num_pages = ceil($file_gallery_query->found_posts / $limit);
		}
	}

	$file_gallery->debug_add('attachments_query', compact('file_gallery_query'));
	
	if( empty($attachments) )
		return '<!-- "File Gallery" plugin says: - No attachments found for the following shortcode arguments: "' . json_encode($attr) . '" -->';

	// feed
	if( is_feed() )
	{
		$output = "\n";

		foreach( $attachments as $attachment )
		{
			$output .= wp_get_attachment_link($attachment->ID, $size, true) . "\n";
		}
		
		return $output;
	}
	
	$i = 0;
	$unique_ids = array();
	$gallery_items = '';
	
	if( 'object' == $output_type || 'array' == $output_type )
		$gallery_items = array();
	
	$autoqueueclasses = array();
	
	if( defined('FILE_GALLERY_LIGHTBOX_CLASSES') )
		$autoqueueclasses = maybe_unserialize(FILE_GALLERY_LIGHTBOX_CLASSES);
	else
		$autoqueueclasses = explode(',', $options['auto_enqueued_scripts']);
	
	$file_gallery_this_template_counter = 1;

	// create output
	foreach($attachments as $attachment)
	{
		$param = array(
			'image_class' => $imageclass,
			'link_class'  => $linkclass,
			'rel'         => $_rel,
			'title'       => '',
			'caption'     => '',
			'description' => '',
			'thumb_alt'   => ''
		);

		$attachment_file = get_attached_file($attachment->ID);
		$attachment_is_image = file_gallery_file_is_displayable_image($attachment_file);
		$startcol = '';
		$endcol = '';
		$x = '';

		if( $output_params )
		{			
			$plcai = array_intersect($autoqueueclasses, explode(' ', trim($linkclass)));

			if( ! empty($plcai) )
			{
				if( $attachment_is_image )
				{
					if( true === $param['rel'] )
					{
						$param['rel'] = $plcai[0] . '[' .  $file_gallery->gallery_id . ']';
					}
					elseif( ! is_bool($param['rel']) )
					{
						if( false !== strpos($_rel, '$GID$') )
							$param['rel'] = str_replace('$GID$', $file_gallery->gallery_id, $_rel);
						else
							$param['rel'] = $_rel . '[' .  $file_gallery->gallery_id . ']';
					}
					
					$filter_args = array(
						'gallery_id' => $file_gallery->gallery_id, 
						'linkrel'    => $param['rel'],
						'linkclass'  => $param['link_class'],
						'imageclass' => $param['image_class']
					);

					if( $param['rel'] )
						$param['rel'] = apply_filters('file_gallery_lightbox_linkrel',    $param['rel'],         'linkrel',    $filter_args);
					
					$param['link_class']  = apply_filters('file_gallery_lightbox_linkclass',  $param['link_class'],  'linkclass',  $filter_args);
					$param['image_class'] = apply_filters('file_gallery_lightbox_imageclass', $param['image_class'], 'imageclass', $filter_args);
				}
				else
				{
					$param['link_class'] = str_replace( trim(implode(' ', $plcai)), '', trim($linkclass));
				}
			}
			
			// if rel is still true or false
			if( is_bool($param['rel']) )
				$param['rel'] = '';

			switch( $linkto )
			{
				case 'parent_post' :
					$param['link'] = get_permalink( $wpdb->get_var("SELECT post_parent FROM $wpdb->posts WHERE ID = '" . $attachment->ID . "'") );
					break;
				case 'file' :
					$param['link'] = wp_get_attachment_url($attachment->ID);
					break;
				case 'attachment' :
				case 'post' :
					$param['link'] = get_attachment_link($attachment->ID);
					break;
				case 'none' :
					$param['link'] = '';
					break;
				default : // external url
					$param['link'] = urldecode($linkto);
					break;
			}
						
			$param['title'] = $attachment->post_title;
			// $param['caption'] = $captions !== false ? $attachment->post_excerpt : '';
			$param['caption'] = $attachment->post_excerpt;
			$param['description'] = $attachment->post_content;
			
			if( $attachment_is_image )
			{
				$thumb_src             = wp_get_attachment_image_src($attachment->ID, $size);
				$param['thumb_link']   = $thumb_src[0];
				$param['thumb_width']  = 0 == $thumb_src[1] ? file_gallery_get_image_size($param['thumb_link'])       : $thumb_src[1];
				$param['thumb_height'] = 0 == $thumb_src[2] ? file_gallery_get_image_size($param['thumb_link'], true) : $thumb_src[2];	
				
				if( '' != $param['link'] && 'full' != $link_size && in_array($link_size, file_gallery_get_intermediate_image_sizes()) )
				{
					$full_src = wp_get_attachment_image_src($attachment->ID, $link_size);
					$param['link'] = $full_src[0];
				}
			}
			else
			{
				$param['thumb_link']   = wp_mime_type_icon($attachment->ID);
				$param['thumb_link']   = apply_filters('file_gallery_non_image_thumb_link', $param['thumb_link'], $attachment->post_mime_type, $attachment->ID);
				$param['thumb_width']  = '46';
				$param['thumb_height'] = '60';
			}

			if( $thumb_alt = get_post_meta($attachment->ID, '_wp_attachment_image_alt', true) )
				$param['thumb_alt'] = $thumb_alt;
			
			$param['attachment_id'] = $attachment->ID;
		}

		/**
		 * Make sure that all attributes added/filtered via
		 * 'wp_get_attachment_link' filter are included here as well
		 */

		/**
			$dom_document = new DOMDocument();
			@$dom_document->loadHTML(wp_get_attachment_link($attachment->ID)); //
			$wp_attachment_link_attributes = $dom_document->getElementsByTagName('a')->item(0)->attributes;
		**/

		/**
		$wp_attachment_link = new SimpleXMLElement(wp_get_attachment_link($attachment->ID));
		$wp_attachment_link_attributes = $wp_attachment_link->attributes();

		foreach( $wp_attachment_link_attributes as $key => $val )
		{
			if( $key === 'title' ) {
				$param['title'] = $val;
			}
			else if( $key === 'class' ) {
				$param['link_class'] .= ' ' . $val;
			}
			else if( $key === 'rel' ) {
				$param['rel'] .= ' ' . $val;
			}
		}
		**/

		$dom_document = HTML5_Parser::parse(wp_get_attachment_link($attachment->ID));
		$wp_attachment_link_attributes = $dom_document->getElementsByTagName("a")->item(0)->attributes;
		$length = $wp_attachment_link_attributes->length;

		for( $i = 0; $i < $length; ++$i )
		{
			$name = $wp_attachment_link_attributes->item($i)->name;
			$value = $wp_attachment_link_attributes->item($i)->value;

			if( $name === 'title' ) {
				$param['title'] = $value;
			}
			else if( $name === 'class' ) {
				$param['link_class'] .= ' ' . $value;
			}
			else if( $name === 'rel' ) {
				$param['rel'] .= ' ' . $value;
			}
		}

		$param = array_map('trim', $param);
		
		if( $include_meta ) {
			$meta = get_post_custom($attachment->ID);
		}
		
		if( 'object' == $output_type )
		{
			if( $output_params )
				$attachment->params = (object) $param;
			
			if( $include_meta )
				$attachment->meta = (object) $meta;

			$gallery_items[] = $attachment;
		}
		elseif( 'array' == $output_type || 'json' == $output_type)
		{
			if( $output_params )
				$attachment->params = $param;
			
			if( $include_meta )
				$attachment->meta = $meta;
			
			$gallery_items[] = get_object_vars($attachment);
		}
		else
		{
			if( $columns > 0 )
			{
				if( 0 === $i || 0 === $i % $columns )
					$startcol = ' gallery-startcol';
				elseif( ($i+1) % $columns == 0 )// add the column break class
					$endcol = ' gallery-endcol';
			}

			// parse template
			ob_start();
				extract( $param );
				include($template_file);
				$x = ob_get_contents();
			ob_end_clean();
			
			$file_gallery_this_template_counter++;
			
			if ( $columns > 0 && $i+1 % $columns == 0 )
				$x .= $cleartag;
			
			$gallery_items .= $x;
			
			$i++;
		}
	}

	// handle data types
	if( 'object' == $output_type || 'array' == $output_type )
	{
		$output = $gallery_items;
	}
	elseif( 'json' == $output_type )
	{
		$output = json_encode($gallery_items);
	}
	else
	{
		$stc = '';
		$cols = '';
		$pagination_html = '';

		if( 0 < (int) $columns )
			$cols = ' columns_' . $columns;
		
		if( isset($starttag_class) && '' != $starttag_class )
			$stc = ' ' . $starttag_class;
		
		$trans_append = "\n<!-- file gallery output cached on " . date('Y.m.d @ H:i:s', time()) . "-->\n";
		
		if( is_singular() && isset($file_gallery_query->max_num_pages) && 1 < $file_gallery_query->max_num_pages )
			$pagination_html = file_gallery_do_pagination( $file_gallery_query->max_num_pages, $page );

		$gallery_class = apply_filters('file_gallery_galleryclass', 'gallery ' . str_replace(' ', '-', $template) . $cols . $stc . ' ' . $galleryclass);
		
		$output = '<' . $starttag . ' id="gallery-' . $file_gallery->gallery_id . '" class="' . $gallery_class . '">' . "\n" . $gallery_items . "\n" . $pagination_html . "\n</" . $starttag . '>';
	}
	
	if( isset($options['cache']) && true == $options['cache'] )
	{
		if( 'html' == $output_type )
			set_transient($transient, $output . $trans_append, $options['cache_time']); // with a comment appended to the end of cached output
		elseif( isset($options['cache_non_html_output']) && true == $options['cache_non_html_output'] )
			set_transient($transient, $output, $options['cache_time']);
	}
	
	return apply_filters('file_gallery_output', $output, $post->ID, $file_gallery->gallery_id);
}


/**
 * Built-in pagination for galleries
 *
 * @since 1.6.5.1
 */
function file_gallery_do_pagination( $total = 0, $page = 0 )
{
	if( 0 < $total && 0 < $page )
	{
		remove_query_arg('page');

		$options = get_option('file_gallery');
		$out = array('<span class="current">' . $page . '</span>');

		if( ! isset($options['pagination_count']) || empty($options['pagination_count']) || 0 >= $options['pagination_count'] )
			$limit = 9;
		else
			$limit = $options['pagination_count'];

		$c = 0;
		$l = $limit;
		$end = false;
		$start = false;
		$current = $page;

		$sides = ($limit - 1) / 2;
		$sl = ceil($sides);
		$sr = floor($sides);
		
		// skip to first page link
		if( ($limit - $sl) < $current )
			$start = true;
		
		// skip to last page link
		if( ($total - $sr) > $current )
			$end = true;

		// left side
		if( 1 < $current )
		{
			$current--;

			while( 0 < $current && 0 < $sl)
			{
				array_unshift($out, str_replace('<a ', '<a class="page"', _wp_link_page($current)) . $current . '</a>');
				
				$current--;
				$sl--;
				$limit--;
			}
			
			$c = $current;
		}

		$current = $page + 1;
		$sr += $sl;
		
		// right side
		while( $current <= $total && 0 < $sr )
		{
			array_push($out, str_replace('<a ', '<a class="page"', _wp_link_page($current)) . $current . '</a>');
			
			$current++;
			$sr--;
			$limit--;
		}
		
		// leftovers
		while( 1 < $limit && 0 < $c )
		{
			array_unshift($out, str_replace('<a ', '<a class="page"', _wp_link_page($c)) . $c . '</a>');

			$c--;
			$limit--;
		}
		
		if( $start ) {
			array_unshift($out, str_replace('<a ', '<a title="' . __('Skip to first page', 'file-gallery') . '" class="page"', _wp_link_page(1)) . '&laquo;</a>');
		}
		
		if( $end ) {
			array_push($out, str_replace('<a ', '<a title="' . __('Skip to last page', 'file-gallery') . '" class="page"', _wp_link_page($total)) . '&raquo;</a>');
		}

		if( $page > 1 ) {
			array_unshift($out, str_replace('<a ', '<a title="' . __('Previous page', 'file-gallery') . '" class="page"', _wp_link_page($page-1)) . '&lsaquo;</a>');
		}

		if( $page > 0 && $page < $total ) {
			array_push($out, str_replace('<a ', '<a title="' . __('Next page', 'file-gallery') . '" class="page"', _wp_link_page($page+1)) . '&rsaquo;</a>');
		}

		if( 'rtl' == get_bloginfo('text_direction') )
			$out = array_reverse($out);
		
		return '<div class="wp-pagenavi">' . "\n" . implode("\n", $out) . "\n</div>";
	}
	
	return '';
}


function file_gallery_register_shortcode_handler()
{
	$options = get_option('file_gallery');

	if( isset($options['disable_shortcode_handler']) && true == $options['disable_shortcode_handler'] )
		return;

	add_filter('post_gallery', 'file_gallery_shortcode', 10, 2);
}
add_action('init', 'file_gallery_register_shortcode_handler');

