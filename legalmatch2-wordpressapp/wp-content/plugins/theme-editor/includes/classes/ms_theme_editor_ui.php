<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;
	
class ms_theme_editor_ui {

    var $colors;
    
    function __construct() {
       
        $this->css()->load_config( 'dict_sel' );
        add_action( 'admin_enqueue_scripts',            array( $this, 'enqueue_scripts' ), 99 );
        add_action( 'all_admin_notices',                array( $this, 'all_admin_notices' ) );
    }
    
    function ctc() {
        return ms_theme_editor_controller::ctc();
    }
    
    function css() {
        return ms_theme_editor_controller::ctc()->css;
    }
    
    function render() {
        // load web fonts for this theme
        if ( $imports = $this->css()->get_prop( 'imports' ) ):
            $ext = 0;
            foreach ( $imports as $import ):
                $this->ctc()->convert_import_to_enqueue( $import, ++$ext, TRUE );
            endforeach;
        endif;
        
        include ( MS_THEME_EDITOR_DIR . '/includes/forms/ms-main.php' ); 
    } 

    function enqueue_is_set(){
        return isset( $this->css()->enqueue ) && $this->css()->get_prop( 'child' );         
    }
    
    function maybe_disable(){
        echo apply_filters( 'chld_thm_cfg_maybe_disable', ( count( $this->ctc()->themes[ 'child' ] ) ? '' : 'ms-disabled' ) );
    }    
    
    function all_admin_notices(){
        do_action( 'chld_thm_cfg_admin_notices' );
    }
    
    function render_theme_menu( $template = 'child', $selected = NULL ) {
        include ( MS_THEME_EDITOR_DIR . '/includes/forms/ms-theme-menu.php' ); 
    }
    
	function ms_theme_directory($template)
	{
		$theme = $this->ctc()->css->get_prop( $template );
		return $theme;
	}
    
    function render_settings_errors() {
        include ( MS_THEME_EDITOR_DIR . '/includes/forms/ms-settings-errors.php' ); 
    }   
    
   function cmp_theme( $a, $b ) {
        return strcmp( strtolower( $a[ 'Name' ] ), strtolower( $b[ 'Name' ] ) );
    }
        
    function enqueue_scripts() {
        wp_enqueue_style( 'chld-thm-cfg-admin', MS_THEME_EDITOR_URL . 'includes/assests/css/ms_child_theme_style.css', array(), '' );        
      
       wp_enqueue_script( 'chld-thm-cfg-admin', MS_THEME_EDITOR_URL . 'includes/assests/js/ms_child_script.js',     array(
                'jquery-ui-autocomplete'  
             ), '', TRUE );
            
        $localize_array = apply_filters( 'chld_thm_cfg_localize_script', array(
            'converted'                 => $this->css()->get_prop( 'converted' ),
            'ssl'                       => is_ssl(),
            'homeurl'                   => home_url( '/' ) . '?ModPagespeed=off&' . ( defined( 'WP_ROCKET_VERSION' ) ? '' : 'ao_noptimize=1&' ) . 'ms_theme_editor_preview=1', 
            'ajaxurl'                   => admin_url( 'admin-ajax.php' ),
            'theme_uri'                 => get_theme_root_uri(),
            'theme_dir'                 => basename( get_theme_root_uri() ),
            'page'                      => 'ms_child_theme_editor',
            'themes'                    => $this->ctc()->themes,
            'source'                    => apply_filters( 'chld_thm_cfg_source_uri', get_theme_root_uri() . '/' 
                . $this->css()->get_prop( 'parnt' ) . '/style.css', $this->css() ),
            'target'                    => apply_filters( 'chld_thm_cfg_target_uri', get_theme_root_uri() . '/' 
                . $this->css()->get_prop( 'child' ) . '/style.css', $this->css() ),
				
            'parnt'                     => $this->css()->get_prop( 'parnt' ),
            'child'                     => $this->css()->get_prop( 'child' ),
            'addl_css'                  => $this->css()->get_prop( 'addl_css' ),
            'forcedep'                  => $this->css()->get_prop( 'forcedep' ),
            'imports'                   => $this->css()->get_prop( 'imports' ),
            'converted'                 => $this->css()->get_prop( 'converted' ),
			'is_debug'                  => $this->ctc()->is_debug,
			
            '_background_url_txt'       => __( 'URL/None', 'te-editor' ),
            '_background_origin_txt'    => __( 'Origin', 'te-editor' ),
            '_background_color1_txt'    => __( 'Color 1', 'te-editor' ),
            '_background_color2_txt'    => __( 'Color 2', 'te-editor' ),
            '_border_width_txt'         => __( 'Width/None', 'te-editor' ),
            '_border_style_txt'         => __( 'Style', 'te-editor' ),
            '_border_color_txt'         => __( 'Color', 'te-editor' ),
			
            /*'swatch_txt'                => '',*/
            'load_txt'                  => __( 'Are you sure you wish to RESET? This will destroy any work you have done in the Configurator.', 'te-editor' ),
            'important_txt'             => __( '<span style="font-size:10px">!</span>', 'te-editor' ),
            'selector_txt'              => __( 'Selectors', 'te-editor' ),
            'close_txt'                 => __( 'Close', 'te-editor' ),
            'edit_txt'                  => __( 'Edit Selector', 'te-editor' ),
            'cancel_txt'                => __( 'Cancel', 'te-editor' ),
            'rename_txt'                => __( 'Rename', 'te-editor' ),
            'css_fail_txt'              => __( 'The stylesheet cannot be displayed.', 'te-editor' ),
            'child_only_txt'            => __( '(Child Only)', 'te-editor' ),
            'inval_theme_txt'           => __( 'Please enter a valid Child Theme.', 'te-editor' ),
            'inval_name_txt'            => __( 'Please enter a valid Child Theme name.', 'te-editor' ),
            'theme_exists_txt'          => __( '<strong>%s</strong> exists. Please enter a different Child Theme', 'te-editor' ),
            'js_txt'                    => __( 'The page could not be loaded correctly.', 'te-editor' ),
            'jquery_txt'                => __( 'Conflicting or out-of-date jQuery libraries were loaded by another plugin:', 'te-editor' ),
            'plugin_txt'                => __( 'Deactivating or replacing plugins may resolve this issue.', 'te-editor' ),
            'contact_txt'               => sprintf( __( '%sWhy am I seeing this?%s', 'te-editor' ),
                '',
                '' ),
        ) );
        wp_localize_script(
            'chld-thm-cfg-admin', 
            'ms_ajax', 
            apply_filters( 'chld_thm_cfg_localize_array', $localize_array )
        );
    }
}
?>