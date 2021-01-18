<?php
// if uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

require_once trailingslashit( dirname( __FILE__ ) ) . 'font-awesome.php';
 
if ( class_exists( 'FortAwesome\FontAwesome_Loader' ) ) :
	FortAwesome\FontAwesome_Loader::maybe_uninstall( trailingslashit( dirname(__FILE__) ) );
endif;
