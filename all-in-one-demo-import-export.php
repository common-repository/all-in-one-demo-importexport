<?php
/**
 * Plugin Name: All in one demo Import/Export
 * Plugin URI: https://www.sanyog.in/wordpess-plugin/all-in-one-demo-import-export
 * Description: The All in one demo Import/Export plugin allows you to export or import your WordPress customizer settings, posts, pages, other custom post types, and more..
 * Version: 0.1
 * Author: Sanyog Shelar
 * Author URI: https://www.sanyog.in/
 * License: GNU General Public License v2.0
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: all-in-one-demo-import-export
 */
define( 'AIODIE_VERSION', '0.7' );
define( 'AIODIE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AIODIE_PLUGIN_URL', plugins_url( '/', __FILE__ ) );

/* Classes */
require_once AIODIE_PLUGIN_DIR . 'classes/class-aiodie-core.php';

/* Actions */
add_action( 'plugins_loaded', 'AIODIE_Core::load_plugin_textdomain' );
add_action( 'customize_controls_print_scripts', 'AIODIE_Core::controls_print_scripts' );
add_action( 'customize_controls_enqueue_scripts', 'AIODIE_Core::controls_enqueue_scripts' );
add_action( 'customize_register', 'AIODIE_Core::init', 999999 );
add_action( 'customize_register', 'AIODIE_Core::register' );