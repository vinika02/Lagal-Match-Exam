<?php namespace te\app\cnt;
use te\app\thm_cnt\theme_editor_theme_controller as run_theme_editor_theme_controller;
use te\app\plg_cnt\theme_editor_plugin_controller as run_theme_editor_plugin_controller;
class theme_editor_controller {
	var $theme_controller;
	var $plugin_controller;
	public function __construct() {
	   $this->init();
	}	
	public function init() {
	    $opt = get_option('mk_te_settings_options');
	    add_action( 'admin_menu', array($this, 'theme_editor_menu_page'));
	    // Add Plugin Editor Page
		  if(isset($opt['e_d_p_e']) && $opt['e_d_p_e'] == 'yes') {
           add_action( 'admin_menu', array( $this, 'theme_editor_plugins_page' ) );
		  }
        // Add Theme Editor Page
	    if(isset($opt['e_d_t_e']) && $opt['e_d_t_e'] == 'yes') {	
          add_action( 'admin_menu', array( $this, 'theme_editor_themes_page' ) );
	    }		
	    add_action('_admin_menu', array($this, 'remove_editor_menu'), 1);		 	   
	    add_action( 'admin_post_mk_theme_editor_export_te_files', array($this, 'export_te_files') );	   
	    add_action( 'admin_post_mk_theme_editor_download_te_theme', array($this, 'download_te_theme') );      
       	add_action( 'admin_post_mk_theme_editor_download_te_plugin', array($this, 'download_te_plugin') );  
		add_action( 'admin_init', array($this, 'load_custom_scripts_settings'));  
	    if(is_admin()) {
	       include('theme_controller.php');
		   include('plugin_controller.php');
         }
		 
	   $this->theme_controller = new run_theme_editor_theme_controller;
       $this->plugin_controller = new run_theme_editor_plugin_controller;
	   add_action("wp_ajax_mk_te_close_te_help", array($this, "mk_te_close_te_help"));
	}
	
	public function theme_editor_menu_page() {
		 add_menu_page(
        __( 'Theme Editor', 'te-editor' ),
        'Theme Editor',
        'manage_options',
        'theme_editor_theme',
        array($this, 'add_themes_page'),	
        plugins_url( '/app/view/images/te.png', MK_THEME_EDITOR_FILE )
    ); 
	add_submenu_page( 'theme_editor_theme', __( 'Plugin Editor', 'te-editor' ), __( 'Plugin Editor', 'te-editor' ), 'manage_options', 'theme_editor_plugin', array(&$this, 'add_plugin_page'));
	add_submenu_page( 'theme_editor_theme', __( 'Settings', 'te-editor' ), __( 'Settings', 'te-editor' ), 'manage_options', 'theme_editor_settings', array(&$this, 'theme_editor_settings_callback'));
	add_submenu_page( 'theme_editor_theme', __( 'Access Control', 'te-editor' ), __( 'Access Control', 'te-editor' ), 'manage_options', 'theme_editor_permisions', array(&$this, 'theme_editor_settings_permisions'));
	add_submenu_page( 'theme_editor_theme', __( 'Notify Me', 'te-editor' ), __( 'Notify Me', 'te-editor' ), 'manage_options', 'theme_editor_notify', array(&$this, 'theme_editor_notify'));
	}	
	public function theme_editor_settings_callback() {
		include(MK_THEME_EDITOR_PATH.'app/view/settings.php');	
	}
	public function theme_editor_settings_permisions() {
		if(is_admin() && current_user_can('manage_options')) {
		  include(MK_THEME_EDITOR_PATH.'app/view/permissions.php');	
		}
	}
	public function theme_editor_notify() {
		if(is_admin() && current_user_can('manage_options')) {
		  include(MK_THEME_EDITOR_PATH.'app/view/notify.php');	
				}
	}
	
	public function theme_editor_themes_page() {
		  $page_title = __( 'Theme Editor', 'te-editor' );
          $menu_title = __( 'Theme Editor', 'te-editor' );
          $capability = 'manage_options';
          $menu_slug = 'theme_editor_theme';
          add_theme_page( $page_title, $menu_title, $capability, $menu_slug, array( $this, 'add_themes_page' ) );
	}
	
	public function add_themes_page() {
		include(MK_THEME_EDITOR_PATH.'app/view/theme_editor.php');
	}
	
	public function theme_editor_plugins_page() {
		$page_title = __( 'Plugin Editor', 'te-editor' );
		$menu_title = __( 'Plugin Editor', 'te-editor' );
		$capability = 'manage_options';
		$menu_slug = 'theme_editor_plugin';
		   add_plugins_page( $page_title, $menu_title, $capability, $menu_slug, array( $this, 'add_plugin_page' ) );
	 }
	 
	public function add_plugin_page() {
	   include(MK_THEME_EDITOR_PATH.'app/view/plugin_editor.php');		
	}
	public function remove_editor_menu() {
		 $opt = get_option('mk_te_settings_options');
		 if(isset($opt['e_w_d_t_e']) && $opt['e_w_d_t_e'] == 'yes') {
		   remove_action('admin_menu', '_add_themes_utility_last', 101);
		 }
		 if(isset($opt['e_w_d_p_e']) && $opt['e_w_d_p_e'] == 'yes') {
		   global $submenu;
           unset( $submenu['plugins.php'][15] );
		  }
	}
	public function export_te_files() {
		$nonce = $_REQUEST['_wpnonce'];
		if(wp_verify_nonce( $nonce, 'mk-fd-nonce') && current_user_can('manage_options')) {
			$file_path = $_GET['file'];
			if(file_exists( $file_path )) {
			$this->theme_controller->download_file( $file_path, 'theme' );
			} else {
			wp_die('File Not Exists! <a href="themes.php?page=theme_editor_theme">&larr; Back</a>');
			}
		}
	}
	
	public function download_te_theme() {
		$nonce = $_REQUEST['_wpnonce'];
		if(wp_verify_nonce( $nonce, 'mk-fd-nonce') && current_user_can('manage_options')) {
			$theme_name = $_GET['theme_name'];
			if(!empty($theme_name)) {
			  $this->theme_controller->download_theme( $theme_name );
			}
		}
	}	
	public function download_te_plugin() {
		$nonce = $_REQUEST['_wpnonce'];
		if(wp_verify_nonce( $nonce, 'mk-fd-nonce') && current_user_can('manage_options')) {
			$plugin_name = $_GET['plugin_name'];
			if(!empty($plugin_name)) {
			  $this->plugin_controller->download_plugin( $plugin_name );
			}
		}
	}	
	public function sava_mk_settings($fields) {
		echo 'Saving Please wait...';
		 $save = $this->theme_controller->__save( $fields );
		 if($save) {
		   $this->redirect('admin.php?page=theme_editor_settings&msg='.$save);
		 }
	}
	public function success($msg) {
		  _e( '<div class="updated settings-error notice is-dismissible" id="setting-error-settings_updated"> 
<p><strong>'.$msg.'</strong></p><button class="notice-dismiss" type="button"><span class="screen-reader-text">Dismiss this notice.</span></button></div>', 'te-editor');	
	}
	public function error($msg) {
		  _e( '<div class="error settings-error notice is-dismissible" id="setting-error-settings_updated"> 
<p><strong>'.$msg.'</strong></p><button class="notice-dismiss" type="button"><span class="screen-reader-text">Dismiss this notice.</span></button></div>', 'te-editor');	
	}
	 public function redirect($url) {
		echo '<script>';
		 echo 'window.location.href="'.$url.'"';
		echo '</script>' ;
	 }
	 public function load_custom_scripts_settings() {
		 $current_page = isset($_GET['page']) ? $_GET['page'] : ''; 
		 if($current_page == 'theme_editor_settings') {
			  wp_enqueue_script('jquery-ui-core');// enqueue jQuery UI Core
			  wp_enqueue_script('jquery-ui-tabs');// enqueue jQuery UI Tabs
			  wp_enqueue_script( 'te-settings-tabs-js', MK_THEME_EDITOR_URL.'app/view/js/settings_tabs.js', array() );
			  wp_enqueue_style( 'te-settings-tabs-css', MK_THEME_EDITOR_URL.'app/view/css/settings_tabs.css' );
		 }
		  wp_enqueue_script( 'te-help_desk-js', MK_THEME_EDITOR_URL.'app/view/js/help_desk.js', array() );

	 }
	 	public function load_help_desk() {
			$mkcontent = '';
			$mkcontent .='<div class="wters">';
			$mkcontent .='<div class="l_wters">';
			$mkcontent .='';
			$mkcontent .='</div>';
            $mkcontent .='<div class="r_wters">';
            $mkcontent .='<a class="close_te_help te_close_btn" href="javascript:void(0)" data-ct="rate_later" title="close">X</a><strong>Theme Editor</strong><p>We love and care about you. Our team is putting maximum efforts to provide you the best functionalities. It would be highly appreciable if you could spend a couple of seconds to give a Nice Review to the plugin to appreciate our efforts. So we can work hard to provide new features regularly :)</p><a class="close_te_help te_close_btn_1" href="javascript:void(0)" data-ct="rate_later" title="Remind me later">Later</a> <a class="close_te_help te_close_btn_2" href="https://wordpress.org/support/plugin/theme-editor/reviews/?filter=5" data-ct="rate_now" title="Rate us now" target="_blank">Rate Us</a> <a class="close_te_help te_close_btn_3" href="javascript:void(0)" data-ct="rate_never" title="Not interested">Never</a>';
			$mkcontent .='</div></div>';
            if ( false === ( $mk_te_close_te_help_c = get_transient( 'mk_te_close_te_help_c' ) ) ) {
			  	echo apply_filters('the_content', $mkcontent);  
		    } 
		}
	   public function mk_te_close_te_help() {
		   $what_to_do = sanitize_text_field($_POST['what_to_do']);
		   $expire_time = 15;
		  if($what_to_do == 'rate_now' || $what_to_do == 'rate_never') {
			 $expire_time = 365;
		  } else if($what_to_do == 'rate_later') {
			 $expire_time = 15;
		  }	
		  if ( false === ( $mk_te_close_te_help_c = get_transient( 'mk_te_close_te_help_c' ) ) ) {
			   $set =  set_transient( 'mk_te_close_te_help_c', 'mk_te_close_te_help_c', 60 * 60 * 24 * $expire_time );
				 if($set) {
					 echo 'ok';
				 } else {
					 echo 'oh';
				 }
			   } else {
				    echo 'ac';
			   }
			  echo 'Working Fine.'; 
		   die; 
	   }
}