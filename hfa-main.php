<?php
/**
 * Plugin Name: .html for all url
 * Description: Adds .html to posts, pages, and custom post types in WordPress.
 * Version: 1.3
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: buddydevelopers
 * Author URI: https://www.buddydevelopers.com/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: html-for-all
 *
 * @package HFA
 */

namespace BUDDY_SEO_URL;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HFA_VERSION', '1.3' );
define( 'HFA_PLUGIN_FILE', __FILE__ );
define( 'HFA_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once plugin_dir_path( __FILE__ ) . 'class-html-for-all.php';
require_once plugin_dir_path( __FILE__ ) . 'admin/class-hfa-setting.php';

$html_for_all = Html_For_All::get_instance();

register_activation_hook( __FILE__, array( $html_for_all, 'activate' ) );
register_deactivation_hook( __FILE__, array( $html_for_all, 'deactivate' ) );

Html_For_All_AdminSettings::get_instance();

add_action(
	'init',
	static function () {
		load_plugin_textdomain( 'html-for-all', false, dirname( HFA_PLUGIN_BASENAME ) . '/languages' );
	}
);
