<?php
/*
Plugin Name: Theme Editor
Plugin URI: https://wordpress.org/plugins/theme-editor
Description: create, edit, upload, download, delete Theme Files and folders
Author: mndpsingh287
Version: 2.4
Author URI: https://profiles.wordpress.org/mndpsingh287
Text Domain: theme-editor
*/
define( 'MK_THEME_EDITOR_PATH', WP_PLUGIN_DIR . '/' . basename( dirname( __FILE__ ) ) . '/' );
define( 'MK_THEME_EDITOR_URL', plugin_dir_url( MK_THEME_EDITOR_PATH ) . basename( dirname( __FILE__ ) ) . '/' );
define( 'MK_THEME_EDITOR_FILE', __FILE__);
if (!defined("MK_THEME_EDITOR_DIRNAME")) define("MK_THEME_EDITOR_DIRNAME", plugin_basename(dirname(__FILE__)));
if(!defined('WP_34')) {
$wp_34 = false;
	if ( version_compare( get_bloginfo( 'version' ), '3.4', '>=' ) ) {
		$wp_34 = true;
	}
define( 'WP_34', $wp_34 );
}
if(!defined('WP_43')) {
	$wp_43 = false;
	if ( version_compare( get_bloginfo( 'version' ), '4.3', '>=' ) ) {
			$wp_43 = true;
	}
	define( 'WP_43', $wp_43 );
}
if(!defined('WPWINDOWS')) {
	$windows = false;
	if ( strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN' ) {
	$windows = true;
	}
  define( 'WPWINDOWS', $windows );
}
add_action('init', 'theme_editor_load_text_domain');
function theme_editor_load_text_domain(){
			load_plugin_textdomain('theme-editor', false, MK_THEME_EDITOR_DIRNAME . "/languages");
		}
include('app/app.php');
include('ms_child_theme_editor.php');

use te\pa\theme_editor_app as run_theme_editor_app;
new run_theme_editor_app;