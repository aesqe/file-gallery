<?php
/*
Plugin Name: File Gallery
Plugin URI: http://skyphe.org/code/wordpress/file-gallery/
Version: 2.0-beta5
Description: "File Gallery" extends WordPress' media (attachments) capabilities by adding a new gallery shortcode handler with templating support, a new interface for attachment handling when editing posts, and much more.
Author: Bruno "Aesqe" Babic
Author URI: http://skyphe.org

////////////////////////////////////////////////////////////////////////////

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

////////////////////////////////////////////////////////////////////////////

*/


/**
 * Setup default File Gallery options
 */

define('FILE_GALLERY_VERSION', '2.0-beta5');
define('FILE_GALLERY_DEFAULT_TEMPLATES', serialize( array('default', 'file-gallery', 'list', 'simple') ) );


/**
 * Just a variables placeholder for now
 *
 * @since 1.6.5.1
 */
class File_Gallery
{
	/**
	 * settings,
	 * their default values,
	 * and false default values
	 *
	 * @since 1.7
	 */
	var $settings = array();
	var $defaults = array();
	var $false_defaults = array();
	var $debug = array();

	/**
	 * Holds the ID number of current post's gallery
	 */
	var $gallery_id;

	/**
	 * Holds gallery options overriden
	 * via 'file_gallery_overrides' template function
	 */
	var $overrides;

	/**
	 * Whether SSL is on for wp-admin
	 */
	var $ssl_admin = false;

	/**
	 * Current version of this plugin
	 */
	var $version = FILE_GALLERY_VERSION;

	/***/
	var $admin_thickbox_enqueued = false;

	/***/
	function __construct()
	{
		$this->File_Gallery();
	}

	/***/
	function File_Gallery()
	{
	}


	function debug_add( $section = 'default', $vars )
	{
		if( ! FILE_GALLERY_DEBUG ) {
			return;
		}

		foreach( $vars as $k => $v )
		{
			$type = gettype($v);

			if( $type === 'boolean' ) {
				$v = ($v === false) ? 'false' : 'true';
			}

			$this->debug[$section][$k] = $v;
		}
	}


	function debug_print()
	{
		if( ! FILE_GALLERY_DEBUG ) {
			return;
		}

		$vars = get_object_vars($this);

		unset($vars['defaults']);
		unset($vars['false_defaults']);
		unset($vars['settings']);
		unset($vars['ssl_admin']);
		unset($vars['admin_thickbox_enqueued']);

		function block($a)
		{
			$out = '<ul>';
			foreach($a as $k => $v)
			{
				$out .= '<li><strong>' . $k . '</strong> => ';
				$out .= (is_array($v) || is_object($v)) ? block($v) : (empty($v) ? '""' : $v);
				$out .= '</li>' . "\n";
			}
			$out .= '</ul>' . "\n";

			return $out;
		}

		return '<style scoped="scoped">
				#querylist ul ul
				{
					margin-left: 30px;
				}
			</style>
			<h3 style="font-family: georgia,times,serif; font-size: 22px; margin: 15px 10px 15px 0;">File Gallery debug</h3>
			' . block($vars);
	}
};

// Begin
$file_gallery = new File_Gallery();

require_once('includes/file-gallery-settings.php');
require_once('includes/file-gallery-activation.php');
require_once('includes/file-gallery-admin.php');
require_once('includes/file-gallery-loader.php');
require_once('includes/attachments.php');
require_once('includes/tinymce.php');
require_once('includes/functions.php');
require_once('includes/ajax.php');
require_once('includes/media-tags.php');
require_once('includes/media-settings.php');
require_once('includes/miscellaneous.php');
require_once('includes/mime-types.php');
require_once('includes/lightboxes-support.php');
require_once('includes/templating.php');
require_once('includes/cache.php');
require_once('includes/regenerate-images.php');
require_once('includes/attachments-custom-fields.php');
require_once('includes/media-upload.php');
