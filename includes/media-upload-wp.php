<?php

if ( ! isset( $_GET['inline'] ) )
	define( 'IFRAME_REQUEST' , true );

/** Load WordPress Administration Bootstrap */
require_once( admin_url('/admin.php') );

if (!current_user_can('upload_files'))
	wp_die(__('You do not have permission to upload files.'));

wp_enqueue_script('plupload-handlers');
wp_enqueue_script( 'media-gallery' );

@header('Content-Type: ' . get_option('html_type') . '; charset=' . get_option('blog_charset'));

// IDs should be integers
$ID = isset($ID) ? (int) $ID : 0;
$post_id = isset($post_id)? (int) $post_id : 0;

if ( ! empty( $_REQUEST['post_id'] ) && ! current_user_can( 'edit_post' , $_REQUEST['post_id'] ) ) {
	wp_die( __( 'Cheatin&#8217; uh?' ), 403 );
}

$body_id = 'media-upload';

do_action( "media_upload_library" );