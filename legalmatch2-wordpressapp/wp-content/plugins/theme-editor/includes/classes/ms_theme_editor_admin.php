<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

class ms_theme_editor_admin {    
    var $genesis;   
    var $processdone;
    var $childtype;
    var $template;
    var $is_ajax;
    var $is_get;
    var $is_post;
    var $skip_form;
    var $fs;
    var $encoding;

    var $fs_prompt;
    var $fs_method;
    var $uploadsubdir;
    var $menuName;
    var $cache_updates  = TRUE;
    var $debug;
    var $is_debug;
    var $is_new;
    
    var $max_sel;
    var $sel_limit;
    var $mem_limit;
 
    var $themes         = array();
    var $errors         = array();
    var $files          = array();
    var $updates        = array();
    
    var $css;
    var $ui;
   
    var $postarrays     = array(
        'ctc_img',
        'ctc_file_parnt',
        'ctc_file_child',
        'ctc_additional_css',
    );
    var $configfields   = array(
        'theme_parnt', 
        'child_type', 
        'theme_child', 
        'child_template',
       'configtype', 
    );
    var $actionfields   = array(
        'load_styles',
    );
    var $imgmimes       = array(
        'jpg|jpeg|jpe'  => 'image/jpeg',
        'gif'           => 'image/gif',
        'png'           => 'image/png',
    );

    function __construct() {
        $this->processdone  = FALSE;
        $this->genesis      = FALSE;
       
        $this->is_new       = FALSE;
        $this->encoding     = WP_Http_Encoding::is_available();
        $this->menuName     = CHLD_THM_CFG_MENU; 
        $this->is_post      = ( 'POST' == $_SERVER[ 'REQUEST_METHOD' ] );
        $this->is_get       = ( 'GET' == $_SERVER[ 'REQUEST_METHOD' ] );
        $this->is_debug     = get_option( MS_CHILD_THEME_EDITOR . '_debug' );
        $this->debug        = '';
        $this->errors       = array();
    }
  
    function ctc_page_init () {
       
        $this->get_themes();
        $this->childtype = count( $this->themes[ 'child' ] ) ? 'existing' : 'new';
    
        $this->load_config();
      
        do_action( 'chld_thm_cfg_preprocess' );
        do_action( 'chld_thm_cfg_forms' );  
        $this->process_post();
        $this->ui = new ms_theme_editor_ui();
    }
    
    function render() {
        $this->ui->render();
    }

    
    function get( $property, $params = NULL ) {
        return $this->css->get_prop( $property, $params );
    }
    
    function get_themes() {
        
        $this->themes = array( 'child' => array(), 'parnt' => array() );
        foreach ( wp_get_themes() as $theme ):
           
            $group      = $theme->parent() ? 'child' : 'parnt';
           
            $slug       = $theme->get_stylesheet();
            
            $version    = $theme->get( 'Version' );
            
            if ( 'child' == $group ) $version = preg_replace("/\.\d{6}\d+$/", '', $version );
           
            $this->themes[ $group ][ $slug ] = array(
                'Template'      => $theme->get( 'Template' ),
                'Name'          => $theme->get( 'Name' ),
                'ThemeURI'      => $theme->get( 'ThemeURI' ),
                'Author'        => $theme->get( 'Author' ),
                'AuthorURI'     => $theme->get( 'AuthorURI' ),
                'Descr'         => $theme->get( 'Description' ),
                'Tags'          => $theme->get( 'Tags' ),
                'Version'       => $version,
                'screenshot'    => $theme->get_screenshot(),
                'allowed'       => $theme->is_allowed(),
            );
        endforeach;
    }

    function validate_post( $action = 'ms_update', $noncefield = '_wpnonce', $cap = 'install_themes' ) {
       return true;
    }
    
    function load_config() {

        $this->css = new ms_theme_editor_css();
        if ( FALSE !== $this->css->load_config() ):
            $this->debug( 'config exists', __FUNCTION__, __CLASS__, __CLASS__ );
           
            if ( ! $this->check_theme_exists( $this->get( 'child' ) )
                || ! $this->check_theme_exists( $this->get( 'parnt' ) ) ):
                $this->debug( 'theme does not exist', __FUNCTION__, __CLASS__, __CLASS__ );
                
                $this->css = new ms_theme_editor_css();
                $this->css->enqueue = 'enqueue';
            endif;
        else:
            $this->debug( 'config does not exist', __FUNCTION__, __CLASS__, __CLASS__ );
           
            $this->css->enqueue = 'enqueue';
        endif;
        do_action( 'chld_thm_cfg_load' );
        if ( $this->is_get ):
            if ( $this->get( 'child' ) ):
                
                $this->verify_creds();
                $stylesheet = apply_filters( 
                    'chld_thm_cfg_target', 
                    $this->css->get_child_target( $this->get_child_stylesheet() ), 
                    $this->css );
              
                if ( !is_writable( $stylesheet ) && !$this->fs )
                    add_action( 'chld_thm_cfg_admin_notices', array( $this, 'writable_notice' ) );
                if ( $fsize = $this->get( 'fsize' ) ):
                    $test = filesize( $stylesheet );
                    $this->debug( 'filesize saved: ' . $fsize . ' current: ' . $test, __FUNCTION__, __CLASS__, __CLASS__ );
                   
                endif;
              
                if ( !$this->get( 'enqueue' ) ):
                    $this->debug( 'no enqueue:', __FUNCTION__, __CLASS__, __CLASS__ );

                    add_action( 'chld_thm_cfg_admin_notices', array( $this, 'enqueue_notice' ) );     
                endif;
            endif;
         
            if ( fileowner( $this->css->get_child_target( '' ) ) != fileowner( MS_THEME_EDITOR_DIR ) )
                add_action( 'chld_thm_cfg_admin_notices', array( $this, 'owner_notice' ) ); 
        endif;    
    }
    
    function ajax_save_postdata( $action = 'ms_update' ) {
        $this->is_ajax = TRUE;
        $this->debug( 'ajax save ', __FUNCTION__, __CLASS__ );
     
        if ( $this->validate_post( $action ) ):
            if ( 'ctc_plugin' == $action ) do_action( 'chld_thm_cfg_pluginmode' );
            $this->verify_creds(); 
            add_action( 'chld_thm_cfg_cache_updates', array( $this, 'cache_debug' ) );
           
            if ( FALSE !== $this->load_config() ): 
                if ( isset( $_POST[ 'ctc_is_debug' ] ) ):
                   
                    $this->toggle_debug();
                else:
                    $this->css->parse_post_data(); 
                   
                    if ( $this->get( 'child' ) ):
                        
                        do_action( 'chld_thm_cfg_addl_files' );        
						$this->css->write_css();
                    endif;
                    $this->save_config();
                endif;                
                do_action( 'chld_thm_cfg_cache_updates' );
            endif;           
            die( json_encode( $this->css->obj_to_utf8( $this->updates ) ) );
        endif;
        die();
    }
    
    function save_config() {        
        $this->css->save_config();
    }
      
    function ajax_query_css( $action = 'ms_update' ) {
        $this->is_ajax = TRUE;
        if ( $this->validate_post( $action ) ):
            if ( 'ctc_plugin' == $action ) do_action( 'chld_thm_cfg_pluginmode' );
            $this->load_config();
            add_action( 'chld_thm_cfg_cache_updates', array( $this, 'cache_debug' ) );
            $regex = "/^ctc_query_/";
            foreach( preg_grep( $regex, array_keys( $_POST ) ) as $key ):
                $name = preg_replace( $regex, '', $key );
                $param[ $name ] = sanitize_text_field( $_POST[ $key ] );
            endforeach;
            $this->debug( 'ajax params: ' . print_r( $param, TRUE ), __FUNCTION__, __CLASS__, __CLASS__ );
            if ( !empty( $param[ 'obj' ] ) ):
                
                $this->updates[] = array(
                    'key'   => isset( $param[ 'key' ] ) ? $param[ 'key' ] : '',
                    'obj'   => $param[ 'obj' ],
                    'data'  => $this->get( $param[ 'obj' ], $param ),
                );
                do_action( 'chld_thm_cfg_cache_updates' );
                die( json_encode( $this->updates ) );
            endif;
        endif;
        die( 0 );
    }
    
    function process_post() {
       
        if ( $this->is_post ):
		
		    foreach ( $this->actionfields as $field ):
                if ( in_array( 'ctc_' . $field, array_keys( $_POST ) ) ):
                    $actionfield = $field;
                    break;
                endif;
            endforeach;
			
            if ( empty( $actionfield ) ) return FALSE;
            if ( !$this->validate_post( apply_filters( 'chld_thm_cfg_action', 'ms_update' ) ) ):
                $this->errors[] = 2; 
            else:
                $args = preg_grep( "/nonce/", array_keys( $_POST ), PREG_GREP_INVERT );
                $msg = FALSE;
                $this->verify_creds( $args );
                if ( $this->fs )
				{
                    switch( $actionfield )
					{                       
                        case 'load_styles':
                        $msg = $this->ms_create_child_theme();
						break;
                        default:
                        $msg ='';
                    }
                } 
            endif; 
            
            if ( $this->errors ):
                $this->update_redirect( 0 );
          
            elseif ( empty( $this->fs_prompt ) ):
                $this->processdone = TRUE;
                
                $this->update_redirect( $msg );
            endif;
        endif; 
    }
    
    function ms_create_child_theme() {		
		
        $msg = 1;
        $this->is_new = TRUE;        
        foreach ( $this->configfields as $configfield ):
            $varparts = explode( '_', $configfield );
			$varname = end( $varparts );
            ${$varname} = empty( $_POST[ 'ctc_' . $configfield ] ) ? '' : 
                preg_replace( "/\s+/s", ' ', sanitize_text_field( $_POST[ 'ctc_' . $configfield ] ) );
        endforeach;
		
		//Child Theme Parameter		
		$name = sanitize_text_field($_POST['child_name']);//child name		
		$themeuri = sanitize_text_field($_POST['child_theme_uri']);//child theme website
		$author = sanitize_text_field($_POST['child_author']);//child theme website
		$descr = sanitize_text_field($_POST['child_descr']);//child theme website
		$authoruri = sanitize_text_field($_POST['child_author_uri']);//child theme website
		$tags = sanitize_text_field($_POST['child_tags']);//child theme tags
		$version = sanitize_text_field($_POST['child_version']);//child theme version
        
		//child theme handler parameter
		$repairheader = sanitize_text_field($_POST['repairheader']);
        $ignoreparnt = sanitize_text_field($_POST['ignoreparnt']);
        $handling = sanitize_text_field($_POST['handling']);
        $enqueue = sanitize_text_field($_POST['enqueue']);	
		
		if (isset( $type) )
		{
			$this->childtype = $type;
		}
      
        if ( !$this->is_theme( $configtype ) && $this->is_legacy() ):
            $parnt  = $this->get( 'parnt' );
            $child  = $this->get( 'child' );
            $name   = $this->get( 'child_name' );
        endif;        
       
        if ( $parnt ):
            if ( ! $this->check_theme_exists( $parnt ) ):
                $this->errors[] = '3:' . $parnt; 
            endif;
        else:
            $this->errors[] = 5; 
        endif;

       
        if ( 'new' != $type && empty( $child ) ):
            $this->errors[] = 6;
        elseif ( 'new' == $type || 'duplicate' == $type ):
            if ( empty( $template ) && empty( $name ) ):
                $this->errors[] = 7; 
            else:
                $template_sanitized = preg_replace( "%[^\w\-]%", '', empty( $template ) ? $name : $template );
                if ( $this->check_theme_exists( $template_sanitized ) ):
                    $this->errors[] = '8:' . $template_sanitized; 
                elseif ( 'duplicate' == $type ):
                   
                    $this->clone_child_theme( $child, $template_sanitized );
                    if ( !empty( $this->errors ) ) return FALSE;
                   
                    $this->copy_theme_mods( $child, $template_sanitized );
                    $msg = 3;
                else:
                    $msg = 2;
                endif;
                $child = $template_sanitized;
            endif;
        endif;
            
       if ( FALSE === $this->verify_child_dir( $child ) ):
            
            $this->errors[] = 9;
            return FALSE;
        endif;
		if ( 'reset' == $type ):           
            $this->reset_child_theme();
            $this->enqueue_parent_css();
            $msg = 4;
        else:

            if ( !empty( $this->errors ) ) return FALSE;
            if ( empty( $name ) ):
                $name = ucfirst( $child );
            endif;
			$oldhandling        = $this->get( 'handling' );
           
            $this->css          = new ms_theme_editor_css();
          
            if ( !$this->is_theme( $configtype ) )
                $this->css->set_prop( 'enqueue', 'enqueue' );
            else
            $this->css->set_prop( 'enqueue',            $enqueue );
            $this->css->set_prop( 'handling',           $handling );
            $this->css->set_prop( 'ignoreparnt',        $ignoreparnt );
    
            $this->css->set_prop( 'parnt',              $parnt );
            $this->css->set_prop( 'child',              $child );
            $this->css->set_prop( 'child_name',         $name );
            $this->css->set_prop( 'child_author',       $author );
            $this->css->set_prop( 'child_themeuri',     $themeuri );
            $this->css->set_prop( 'child_authoruri',    $authoruri );
            $this->css->set_prop( 'child_descr',        $descr );
            $this->css->set_prop( 'child_tags',         $tags );
            $this->css->set_prop( 'child_version',      strlen( $version ) ? $version : '1.0' );
    
            if ( isset( $_POST[ 'ctc_action' ] ) && 'plugin' == $_POST[ 'ctc_action' ] ):
                
                $this->css->addl_css = array();
                if ( isset( $_POST[ 'ctc_additional_css' ] ) && is_array( $_POST[ 'ctc_additional_css' ] ) ): 
                    foreach ( $_POST[ 'ctc_additional_css' ] as $file )
                        $this->css->addl_css[] = sanitize_text_field( $file );
                endif;
                add_action( 'chld_thm_cfg_parse_stylesheets', array( $this, 'parse_child_stylesheet_to_target' ) );
            elseif ( isset( $_POST['ms_theme_child_analysis'] )):
               
                $this->evaluate_signals();
            endif;
            
            
            $this->css->forcedep = array();
            if ( isset( $_POST[ 'ctc_forcedep' ] ) && is_array( $_POST[ 'ctc_forcedep' ] ) ): 
                foreach ( $_POST[ 'ctc_forcedep' ] as $handle )
                    $this->css->forcedep[ sanitize_text_field( $handle ) ] = 1;
            endif;

            
            if ( $this->genesis ):
                $handling       = 'separate';
                $enqueue        = 'none';
                $ignoreparnt    = TRUE;
                if ( $this->backup_or_restore_file( 'ms-separate-style.css', TRUE, 'style.css' ) &&
                    $this->backup_or_restore_file( 'style.css', TRUE, 'ctc-genesis.css' ) ):
                    $this->delete_child_file( 'ctc-genesis', 'css' );
                else:
                    $this->errors[] = 10; 
                endif;
            endif;
            
            if ( !empty( $this->errors ) ) return FALSE;   
           
            if ( 'enqueue' == $enqueue && ( $this->get( 'parntloaded' ) || !$this->get( 'hasstyles' ) || $ignoreparnt ) ) $enqueue = 'none';       
            
            if ( $this->is_theme( $configtype ) || $this->is_legacy()):                
                
                if ( $this->get( 'hasstyles' ) && !$ignoreparnt ):
                    $this->debug( 'Adding action: parse_parent_stylesheet_to_source', __FUNCTION__, __CLASS__ );
                    add_action( 'chld_thm_cfg_parse_stylesheets', array( $this, 'parse_parent_stylesheet_to_source' ) );
                endif;               
                
                if ( is_multisite())
                    add_action( 'chld_thm_cfg_addl_options', array( $this, 'network_enable' ) );
            endif;
            $this->debug( 'Adding action: parse_additional_stylesheets_to_source', __FUNCTION__, __CLASS__ );
            add_action( 'chld_thm_cfg_parse_stylesheets', array( $this, 'parse_additional_stylesheets_to_source' ) );
        
            if ( 'separate' == $handling ):               
                
                add_action( 'chld_thm_cfg_parse_stylesheets', array( $this, 'parse_child_stylesheet_to_source' ) );
                $this->debug( 'Adding action: parse_custom_stylesheet_to_target', __FUNCTION__, __CLASS__ );
                add_action( 'chld_thm_cfg_parse_stylesheets', array( $this, 'parse_custom_stylesheet_to_target' ) );
            elseif ( 'primary' == $handling ):
               
                add_action( 'chld_thm_cfg_parse_stylesheets', array( $this, 'parse_child_stylesheet_to_target' ) );
                if ( $oldhandling != $handling ):
                    $this->debug( 'Adding action: parse_custom_stylesheet_to_target', __FUNCTION__, __CLASS__ );
                    add_action( 'chld_thm_cfg_parse_stylesheets', array( $this, 'parse_custom_stylesheet_to_target' ) );
                endif;
            endif;           
            
            if ( $this->is_theme( $configtype )):                
                add_action( 'chld_thm_cfg_addl_files', array( $this, 'add_base_files' ), 10, 2 );
                add_action( 'chld_thm_cfg_addl_files', array( $this, 'copy_screenshot' ), 10, 2 );
                add_action( 'chld_thm_cfg_addl_files', array( $this, 'enqueue_parent_css' ), 15, 2 );
                if ( $repairheader && 'reset' != $type ):
                    add_action( 'chld_thm_cfg_addl_files', array( $this, 'repair_header' ) );
                endif;
            endif;    
            do_action( 'chld_thm_cfg_parse_stylesheets' );
            if ( isset( $_POST[ 'ctc_parent_mods' ] ) && 'duplicate' != $type )
                $this->copy_theme_mods( $parnt, $child );
            $this->enqueue_parent_css( TRUE );
            do_action( 'chld_thm_cfg_addl_files' );
            
            if ( !empty ( $this->errors ) ) return FALSE;
            
            if ( 'separate' == $handling ):
                $this->debug( 'Writing new stylesheet header...', __FUNCTION__, __CLASS__ );
                $this->rewrite_stylesheet_header();
            endif;
           
            $this->css->set_prop( 'converted', 1 );
            
          
            $this->debug( 'Writing new CSS...', __FUNCTION__, __CLASS__ );
            if ( FALSE === $this->css->write_css() ):
                
                $this->errors[] = 11; 
                return FALSE;
            endif; 
            
            $this->get_files( $parnt );
        endif;       
        $this->save_config();
       do_action( 'chld_thm_cfg_addl_options' );
       return $msg;
    }
    
    function sanitize_options( $input ) {
        return $input;
    }   
    
    function sanitize_slug( $slug ) {
        return preg_replace( "/[^\w\-]/", '', $slug );
    }    
    function update_redirect( $msg = 1 ) {
        $this->log_debug();
        if ( empty( $this->is_ajax ) ):
            $ctcpage = apply_filters( 'chld_thm_cfg_admin_page', CHLD_THM_CFG_MENU );
            $screen = get_current_screen()->id;
            wp_safe_redirect(
                ( strstr( $screen, '-network' ) ? network_admin_url( 'themes.php' ) : admin_url( 'admin.php' ) ) 
                    . '?page=' . $ctcpage . ( $msg ? '&updated=' . $msg : ( $this->errors ? '&error=' . implode( ',', $this->errors ) : '' ) ) );
            die();
        endif;
    }
    
    function verify_child_dir( $path ) {
        $this->debug( 'Verifying child dir: ' . $path, __FUNCTION__, __CLASS__ );
        if ( !$this->fs ): 
            $this->debug( 'No filesystem access.', __FUNCTION__, __CLASS__ );
            return FALSE; 
        endif;
        global $wp_filesystem;
        $themedir = $wp_filesystem->find_folder( get_theme_root() );
        if ( ! $wp_filesystem->is_writable( $themedir ) ):
            $this->debug( 'Directory not writable: ' . $themedir, __FUNCTION__, __CLASS__ );
            return FALSE;
        endif;
        $childparts = explode( '/', $this->normalize_path( $path ) );
        while ( count( $childparts ) ):
            $subdir = array_shift( $childparts );
            if ( empty( $subdir ) ) continue;
            $themedir = trailingslashit( $themedir ) . $subdir;
            if ( ! $wp_filesystem->is_dir( $themedir ) ):
                if ( ! $wp_filesystem->mkdir( $themedir, FS_CHMOD_DIR ) ):
                $this->debug( 'Could not make directory: ' . $themedir, __FUNCTION__, __CLASS__ );
                    return FALSE;
                endif;
            elseif ( ! $wp_filesystem->is_writable( $themedir ) ):
                $this->debug( 'Directory not writable: ' . $themedir, __FUNCTION__, __CLASS__ );
                return FALSE;
            endif;
        endwhile;
        $this->debug( 'Child dir verified: ' . $themedir, __FUNCTION__, __CLASS__ );
        return TRUE;
    }
    
    function add_base_files( $obj ){
        
        $contents = "<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;
";
        $handling = $this->get( 'handling' );
        $this->write_child_file( 'functions.php', $contents );
        $this->backup_or_restore_file( 'style.css' );
        $contents = $this->css->get_css_header_comment( $handling );
        $this->debug( 'writing initial stylesheet header...' . LF . $contents, __FUNCTION__, __CLASS__ );
        $this->write_child_file( 'style.css', $contents );
        if ( 'separate' == $handling ):
            $this->backup_or_restore_file( 'ms-separate-style.css' );
            $this->write_child_file( 'ms-separate-style.css', $contents . LF );
        endif;
    }
    
   
    function convert_import_to_enqueue( $import, $count, $execute = FALSE ) {
        $relpath    = $this->get( 'child' );
        $import     = preg_replace( "#^.*?url\(([^\)]+?)\).*#", "$1", $import );
        $import     = preg_replace( "#[\'\"]#", '', $import );
        $path       = $this->css->convert_rel_url( trim( $import ), $relpath , FALSE );
        $abs        = preg_match( '%(https?:)?//%', $path );
        if ( $execute )
            wp_enqueue_style( 'chld_thm_cfg_ext' . $count,  $abs ? $path : trailingslashit( get_theme_root_uri() ) . $path );
        else
            return "wp_enqueue_style( 'chld_thm_cfg_ext" . $count . "', " 
                . ( $abs ? "'" . $path . "'" : "trailingslashit( get_theme_root_uri() ) . '" . $path . "'" ) . ' );';
    }    
   
    function convert_enqueue_to_import( $path ) {
        if ( preg_match( '%(https?:)?//%', $path ) ):
            $this->css->imports[ 'child' ]['@import url(' . $path . ')'] = 1;
            return;
        endif;
        $regex  = '#^' . preg_quote( trailingslashit( $this->get( 'child' ) ) ) . '#';
        $path   = preg_replace( $regex, '', $path, -1, $count );
        if ( $count ): 
            $this->css->imports[ 'child' ]['@import url(' . $path . ')'] = 1;
            return;
        endif;
        $parent = trailingslashit( $this->get( 'parnt' ) );
        $regex  = '#^' . preg_quote( $parent ) . '#';
        $path   = preg_replace( $regex, '../' . $parent, $path, -1, $count );
        if ( $count )
            $this->css->imports[ 'child' ]['@import url(' . $path . ')'] = 1;
    }   
    
    function enqueue_parent_code(){
       
        $imports        = $this->get( 'imports' );
        $enqueues       = array();
        $code           = "// AUTO GENERATED - Do not modify or remove comment markers above or below:" . LF;
        $deps           = $this->get( 'parnt_deps' );
        $enq            = $this->get( 'enqueue' );
        $handling       = $this->get( 'handling' );
        $hasstyles      = $this->get( 'hasstyles' );
        $childloaded    = $this->get( 'childloaded' );
        $parntloaded    = $this->get( 'parntloaded' );
        $cssunreg       = $this->get( 'cssunreg' );
        $csswphead      = $this->get( 'csswphead' );
        $cssnotheme     = $this->get( 'cssnotheme' );
        $ignoreparnt    = $this->get( 'ignoreparnt' );
        $priority       = $this->get( 'qpriority' );
        $reorder        = $this->get( 'reorder' );
        $this->debug( 'forcedep: ' . print_r( $this->get( 'forcedep' ), TRUE ) . ' deps: ' . print_r( $deps, TRUE ) . ' enq: ' . $enq . ' handling: ' . $handling
            . ' hasstyles: ' . $hasstyles . ' parntloaded: ' . $parntloaded . ' childloaded: ' . $childloaded . ' reorder: ' . $reorder
            . ' ignoreparnt: ' . $ignoreparnt . ' priority: ' . $priority . ' childtype: ' . $this->childtype, __FUNCTION__, __CLASS__ );
        
        if ( 'enqueue' == $enq && $hasstyles && !$parntloaded && !$ignoreparnt ):
            
            $deps = array_diff( $deps, array( 'chld_thm_cfg_parent' ) );
            $code .= "
if ( !function_exists( 'ms_theme_editor_parent_css' ) ):
    function ms_theme_editor_parent_css() {
        wp_enqueue_style( 'chld_thm_cfg_parent', trailingslashit( get_template_directory_uri() ) . 'style.css', array( " . implode( ',', $deps ) . " ) );
    }
endif;
add_action( 'wp_enqueue_scripts', 'ms_theme_editor_parent_css', " . $priority . " );
";
            $deps = array( "'chld_thm_cfg_parent'" );
        endif;        
        if ( 'separate' != $handling && $childloaded && $reorder && ( $parntloaded || in_array( 'chld_thm_cfg_parent', $deps ) ) ):
            $dephandle = $parntloaded ? $parntloaded : 'chld_thm_cfg_parent';
            $code .= "
if ( !function_exists( 'ms_theme_editor_parent_dep') ):
function ms_theme_editor_parent_dep() {
    global \$wp_styles;
    array_unshift( \$wp_styles->registered[ '" . $childloaded . "' ]->deps, '" . $dephandle . "' );
}
endif;
add_action( 'wp_head', 'ms_theme_editor_parent_dep', 2 );
";
        endif;      
        if ( !empty( $imports ) ):
            $ext = 0;
            foreach ( $imports as $import ):
                if ( !empty( $import ) ):
                    $ext++;
                    $enqueues[] = '        ' . $this->convert_import_to_enqueue( $import, $ext ); 
                endif;
            endforeach;
        endif;       
        
      if ( 'separate' != $handling && ( ( $csswphead || $cssunreg || $cssnotheme ) 
            || ( 'new' != $this->childtype && !$childloaded ) 
            ) ): 
            $deps = array_merge( $deps, $this->get( 'child_deps' ) );
           
            $deps = array_diff( $deps, array( 'chld_thm_cfg_child' ) );
            $enqueues[] = "        wp_enqueue_style( 'chld_thm_cfg_child', trailingslashit( get_stylesheet_directory_uri() ) . 'style.css', array( " . implode( ',', $deps ) . " ) );";
           
            $deps = array( "'chld_thm_cfg_child'" );
        endif;
        if ( 'separate' == $handling ):
            $deps = array_merge( $deps, $this->get( 'child_deps' ) );
           
            $deps = array_diff( $deps, array( 'ms_theme_editor_child_ms_separate' ) );
            $enqueues[] = "        wp_enqueue_style( 'ms_theme_editor_child_ms_separate', trailingslashit( get_stylesheet_directory_uri() ) . 'ms-separate-style.css', array( " . implode( ',', $deps ) . " ) );";
        endif;
        if ( count( $enqueues ) ):
            $code .= "         
if ( !function_exists( 'ms_theme_editor_child_css' ) ):
    function ms_theme_editor_child_css() {" . LF;
            $code .= implode( "\n", $enqueues );
            $code .= "
    }
endif;
add_action( 'wp_enqueue_scripts', 'ms_theme_editor_child_css', " . ( $priority + 10 ) . " );" . LF;
        endif;
        if ( $ignoreparnt )
            $code .= "
defined( 'CHLD_THM_CFG_IGNORE_PARENT' ) or define( 'CHLD_THM_CFG_IGNORE_PARENT', TRUE );" . LF;
       
        return explode( "\n", $code ); 
    }   
    
    function enqueue_parent_css( $getexternals = FALSE ) {
        $this->debug( 'enqueueing parent css: getexternals = ' . $getexternals, __FUNCTION__, __CLASS__ );
        $marker  = 'ENQUEUE PARENT ACTION';
        $insertion  =  $this->enqueue_parent_code();
        if ( $filename   = $this->css->is_file_ok( $this->css->get_child_target( 'functions.php' ), 'write' ) ):
            $this->insert_with_markers( $filename, $marker, $insertion, $getexternals );
           
            if ( !$getexternals && 'reset' == $this->childtype ):
                $marker  = 'CTC ENQUEUE PLUGIN ACTION';
                $this->insert_with_markers( $filename, $marker, array() );
            endif;
        endif;
    }   
  
    function insert_with_markers( $filename, $marker, $insertion, $getexternals = FALSE ) { 
        if ( count( $this->errors ) ):
            $this->debug( 'Errors detected, returning', __FUNCTION__, __CLASS__ );
            return FALSE;
        endif;
       
        if ( $this->is_ajax && is_readable( $filename ) && is_writable( $filename ) ):
            
            $this->debug( 'Ajax update, bypassing wp filesystem.', __FUNCTION__, __CLASS__ );
            $markerdata = explode( "\n", @file_get_contents( $filename ) );
        elseif ( !$this->fs ): 
            $this->debug( 'No filesystem access.', __FUNCTION__, __CLASS__ );
            return FALSE; 
        else:
            global $wp_filesystem;
            if( !$wp_filesystem->exists( $this->fspath( $filename ) ) ):
                if ( $getexternals ):
                    $this->debug( 'Read only and no functions file yet, returning...', __FUNCTION__, __CLASS__ );
                    return FALSE;
                else:
                    
                    $this->debug( 'No functions file, creating...', __FUNCTION__, __CLASS__ );
                    $this->add_base_files( $this );
                endif;
            endif;
            
            $markerdata = explode( "\n", $wp_filesystem->get_contents( $this->fspath( $filename ) ) );
        endif;
        $newfile = '';
        $externals  = array();
        $phpopen    = 0;
        $in_comment = 0;
        $foundit = FALSE;
        if ( $markerdata ):
            $state = TRUE;
            foreach ( $markerdata as $n => $markerline ):
               
                $str = preg_replace( "/\/\/.*$/", '', $markerline );
                preg_match_all("/(<\?|\?>|\*\/|\/\*)/", $str, $matches );
                if ( $matches ):
                    foreach ( $matches[1] as $token ): 
                        if ( '/*' == $token ):
                            $in_comment = 1;
                        elseif ( '*/' == $token ):
                            $in_comment = 0;
                        elseif ( '<?' == $token && !$in_comment ):
                            $phpopen = 1;
                        elseif ( '?>' == $token && !$in_comment ):
                            $phpopen = 0;
                        endif;
                    endforeach;
                endif;
                if ( strpos( $markerline, '// BEGIN ' . $marker ) !== FALSE )
                    $state = FALSE;
                if ( $state ):
                    if ( $n + 1 < count( $markerdata ) )
                        $newfile .= "{$markerline}\n";
                    else
                        $newfile .= "{$markerline}";
                elseif ( $getexternals ):
                   
                    if ( preg_match( "/wp_enqueue_style.+?'chld_thm_cfg_ext\d+'.+?'(.+?)'/", $markerline, $matches ) ):
                        $this->debug( 'external link found : ' . $matches[ 1 ] );
                        $this->convert_enqueue_to_import( $matches[ 1 ] );
                    endif;
                endif;
                if ( strpos( $markerline, '// END ' . $marker ) !== FALSE ):
                    if ( 'reset' != $this->childtype ):
                        $newfile .= "// BEGIN {$marker}\n";
                        if ( is_array( $insertion ) )
                            foreach ( $insertion as $insertline )
                                $newfile .= "{$insertline}\n";
                        $newfile .= "// END {$marker}\n";
                    endif;
                    $state = TRUE;
                    $foundit = TRUE;
                endif;
            endforeach;
        else:
            $this->debug( 'Could not parse functions file', __FUNCTION__, __CLASS__ );
            return FALSE;
        endif;
        if ( $foundit ):
            $this->debug( 'Found marker, replaced inline', __FUNCTION__, __CLASS__ );
        else:
            if ( 'reset' != $this->childtype ):
                
                if ( ! $phpopen ):
                    $this->debug( 'PHP not open', __FUNCTION__, __CLASS__ );
                  
                    $newfile .= '<?php' . LF;
                endif;
                $newfile .= "\n// BEGIN {$marker}\n";
                foreach ( $insertion as $insertline )
                    $newfile .= "{$insertline}\n";
                $newfile .= "// END {$marker}\n";
            endif;
        endif;
        
        if ( $getexternals ):
            $this->debug( 'Read only, returning.', __FUNCTION__, __CLASS__ );
        else:
            $mode = 'direct' == $this->fs_method ? FALSE : 0666;
            $this->debug( 'Writing new functions file...', __FUNCTION__, __CLASS__ );
            if ( $this->is_ajax && is_writable( $filename ) ): 
              
                if ( FALSE === @file_put_contents( $filename, $newfile ) ): 
                    $this->debug( 'Ajax write failed.', __FUNCTION__, __CLASS__ );
                    return FALSE;
                endif;
            elseif ( FALSE === $wp_filesystem->put_contents( 
                $this->fspath( $filename ), 
                $newfile, 
                $mode 
            ) ): 
                $this->debug( 'Filesystem write failed.', __FUNCTION__, __CLASS__ );
                return FALSE;
            endif;
            $this->css->set_prop( 'converted', 1 );
        endif;
    }   
    
    function write_child_file( $file, $contents ) {
        
        if ( !$this->fs ): 
            $this->debug( 'No filesystem access, returning.', __FUNCTION__, __CLASS__ );
            return FALSE; 
        endif;
        global $wp_filesystem;
        if ( $file = $this->css->is_file_ok( $this->css->get_child_target( $file ), 'write' ) ):
            $mode = 'direct' == $this->fs_method ? FALSE : 0666;
            $file = $this->fspath( $file );
            if ( $wp_filesystem->exists( $file ) ):
                $this->debug( 'File exists, returning.', __FUNCTION__, __CLASS__ );
                return FALSE;
            else:
                $this->debug( 'Writing to filesystem: ' . $file . LF . $contents, __FUNCTION__, __CLASS__ );
                if ( FALSE === $wp_filesystem->put_contents( 
                    $file, 
                    $contents,
                    $mode 
                    ) ):
                    $this->debug( 'Filesystem write failed, returning.', __FUNCTION__, __CLASS__ );
                    return FALSE; 
                endif;
            endif;
        else:
            $this->debug( 'No directory, returning.', __FUNCTION__, __CLASS__ );
            return FALSE;
        endif;
        $this->debug( 'Filesystem write successful.', __FUNCTION__, __CLASS__ );
    }
    function copy_screenshot() {        
        $this->copy_parent_file( 'screenshot' ); 
    }
    
    function copy_parent_file( $file, $ext = 'php' ) {
        
        if ( !$this->fs ): 
            $this->debug( 'No filesystem access.', __FUNCTION__, __CLASS__ );
            return FALSE; 
        endif;
        global $wp_filesystem;
        $parent_file = NULL;
        if ( 'screenshot' == $file ):
            foreach ( array_keys( $this->imgmimes ) as $extreg ): 
                foreach( explode( '|', $extreg ) as $ext )
                    if ( ( $parent_file = $this->css->is_file_ok( $this->css->get_parent_source( 'screenshot.' . $ext ) ) ) ) 
                        break;
                if ( $parent_file ):
                    $parent_file = $this->fspath( $parent_file );
                    break;
                endif;
            endforeach;
            if ( !$parent_file ):
                $this->debug( 'No screenshot found.', __FUNCTION__, __CLASS__ );
                return;
            endif;
        else:
            $parent_file = $this->fspath( $this->css->is_file_ok( $this->css->get_parent_source( $file . '.' . $ext ) ) );
        endif;
        
        
        $child_file = $this->css->get_child_target( $file . '.' . $ext );
        
        if ( $wp_filesystem->exists( $this->fspath( $child_file ) ) ) return TRUE;
        $child_dir = dirname( $this->theme_basename( '', $child_file ) );
        $this->debug( 'Verifying child dir... ', __FUNCTION__, __CLASS__ );
        if ( $parent_file 
            && $child_file 
                && $this->verify_child_dir( $child_dir ) 
                    && $wp_filesystem->copy( $parent_file, $this->fspath( $child_file ), FS_CHMOD_FILE ) ):
            $this->debug( 'Filesystem copy successful', __FUNCTION__, __CLASS__ );
            return TRUE;
        endif;
        
        $this->errors[] = '13:' . $parent_file; 
    }
    
    function delete_child_file( $file, $ext = 'php' ) {
        if ( !$this->fs ): 
            $this->debug( 'No filesystem access.', __FUNCTION__, __CLASS__ );
            return FALSE; 
        endif;
        global $wp_filesystem;
        
        $file = ( 'img' == $ext ? $file : $file . '.' . $ext );
        if ( $child_file  = $this->css->is_file_ok( $this->css->get_child_target( $file ), 'write' ) ):
            if ( $wp_filesystem->exists( $this->fspath( $child_file ) ) ):
                
                if ( $wp_filesystem->delete( $this->fspath( $child_file ) ) ):
                    return TRUE;
                else:
                
                    $this->errors[] = '14:' . $ext;
                    $this->debug( 'Could not delete ' . $ext . ' file', __FUNCTION__, __CLASS__ );
        
                endif;
            endif;
        endif;
    }
    
    function get_files( $theme, $type = 'template' ) {
        $isparent = ( $theme === $this->get( 'parnt' ) );
        if ( 'template' == $type && $isparent && ( $templates = $this->get( 'templates' ) ) ): 
            return $templates;
        elseif ( !isset( $this->files[ $theme ] ) ):

            $this->files[ $theme ] = array();
            $imgext = '(' . implode( '|', array_keys( $this->imgmimes ) ) . ')';
            foreach ( $this->css->recurse_directory(
                trailingslashit( get_theme_root() ) . $theme, '', TRUE ) as $filepath ):
                $file = $this->theme_basename( $theme, $filepath );
                if ( preg_match( "/^style\-(\d+)\.css$/", $file, $matches ) ):
                    $date = date_i18n( 'D, j M Y g:i A', strtotime( $matches[ 1 ] ) );
                    $this->files[ $theme ][ 'backup' ][ $file ] = $date;
                   
                elseif ( strstr( $file, "msbackup" ) ):
                    $date = date_i18n( 'D, j M Y g:i A', filemtime( $filepath ) );
                    $this->files[ $theme ][ 'backup' ][ $file ] = $date;               
                   
                elseif ( preg_match( "/\.php$/", $file ) ):
                    if ( $isparent ):
                    
                        if ( ( $file_verified = $this->css->is_file_ok( $this->css->get_parent_source( $file, $theme ) , 'read' ) ) ):
                            $this->debug( 'scanning ' . $file_verified . '... ', __FUNCTION__, __CLASS__ );
                            
                            $template = FALSE;
                            $size = 0;
                            if ( $handle = fopen( $file_verified, "rb") ):
                                while ( !feof( $handle ) ):
                                    $size++;
                                    if ( $size > 10 ) 
                                        break;
                                    $contents = fread($handle, 2048);
                                    if ( preg_match( "/\w+\s*\(/", $contents ) ):
                                        $template = TRUE;
                                        
                                        $contents = preg_replace( "%<script>.+?</script>%s", '', $contents );
                                        $contents = preg_replace( "%(^.+?</script>|<script>.+$)%s", '', $contents );
                                        
                                        if ( preg_match( "/(function \w+?|require(_once)?)\s*\(/", $contents ) ):
                                            $this->debug( 'disqualifying code found in chunk ' . $size, __FUNCTION__, __CLASS__ );
                                            $template = FALSE;
                                            break;
                                        endif;
                                    endif;
                                endwhile;
                                fclose( $handle );
                            endif;
                            if ( $template )
                                $this->files[ $theme ][ 'template' ][] = $file;
                        endif;
                    else:
                       
                        $this->files[ $theme ][ 'template' ][] = $file;
                    endif;
                elseif ( preg_match( "/\.css$/", $file ) 
                    && ( !in_array( $file, array( 
                        'style.css', 
                        'ms-separate-style.css'                         
                    ) ) ) ):
                    $this->files[ $theme ][ 'stylesheet' ][] = $file;
                    
                elseif ( preg_match( "/\.(js|txt)$/", $file ) ):
                    $this->files[ $theme ][ 'txt' ][] = $file;
                elseif ( preg_match( "/^images\/.+?\." . $imgext . "$/", $file ) ):
                    $this->files[ $theme ][ 'img' ][] = $file;
                   
                else:
                    $this->files[ $theme ][ 'other' ][] = $file;
                endif;
            endforeach;
        endif;
        if ( $isparent ):
            
            $this->css->templates = $this->files[ $theme ][ 'template' ];
        endif;
        $types = explode( ",", $type );
        $files = array();
        foreach ( $types as $type ):
            if ( isset( $this->files[ $theme ][ $type ] ) )
                $files = array_merge( $this->files[ $theme ][ $type ], $files );
        endforeach;
        return $files;
    }
        
    function theme_basename( $theme, $file ) {
        $file = $this->normalize_path( $file );
       
        $themedir = trailingslashit( $this->normalize_path( get_theme_root() ) ) . ( '' == $theme ? '' : trailingslashit( $theme ) );
        
        return preg_replace( '%^' . preg_quote( $themedir ) . '%', '', $file );
    }
    
    function uploads_basename( $file ) {
        $file = $this->normalize_path( $file );
        $uplarr = wp_upload_dir();
        $upldir = trailingslashit( $this->normalize_path( $uplarr[ 'basedir' ] ) );
        return preg_replace( '%^' . preg_quote( $upldir ) . '%', '', $file );
    }
    
    function uploads_fullpath( $file ) {
        $file = $this->normalize_path( $file );
        $uplarr = wp_upload_dir();
        $upldir = trailingslashit( $this->normalize_path( $uplarr[ 'basedir' ] ) );
        return $upldir . $file;
    }
    
    function serialize_postarrays() {
        foreach ( $this->postarrays as $field )
            if ( isset( $_POST[ $field ] ) && is_array( $_POST[ $field ] ) )
                $_POST[ $field ] = implode( "%%", $_POST[ $field ] );
    }
    
    function unserialize_postarrays() {
        foreach ( $this->postarrays as $field )
            if ( isset( $_POST[ $field ] ) && !is_array( $_POST[ $field ] ) )
                $_POST[ $field ] = explode( "%%", $_POST[ $field ] );
    }
    
    function set_writable( $file = NULL ) {

        if ( isset( $file ) ):
            $file =  $this->css->get_child_target( $file . '.php' );
        else:
            $file =  apply_filters( 'chld_thm_cfg_target', $this->css->get_child_target( 'separate' == $this->get( 'handling' ) ? 'ms-separate-style.css' : 'style.css' ), $this->css );
        endif;
        if ( $this->fs ): 
            if ( is_writable( $file ) ) return;
            global $wp_filesystem;
            if ( $file && $wp_filesystem->chmod( $this->fspath( $file ), 0666 ) ) 
                return;
        endif;
        $this->errors[] = 28;
        return FALSE;
    }
    
    function clone_child_theme( $child, $clone ) {
        if ( !$this->fs ) return FALSE; 
        global $wp_filesystem;
       
        $this->css->set_prop( 'child', $child );

        $dir        = untrailingslashit( $this->css->get_child_target( '' ) );
        $themedir   = trailingslashit( get_theme_root() );
        $fsthemedir = $this->fspath( $themedir );
        $files = $this->css->recurse_directory( $dir, NULL, TRUE );
        $errors = array();
        foreach ( $files as $file ):
            $childfile  = $this->theme_basename( $child, $this->normalize_path( $file ) );
            $newfile    = trailingslashit( $clone ) . $childfile;
            $childpath  = $fsthemedir . trailingslashit( $child ) . $childfile;
            $newpath    = $fsthemedir . $newfile;
            $this->debug( 'Verifying child dir... ', __FUNCTION__, __CLASS__ );
            if ( $this->verify_child_dir( is_dir( $file ) ? $newfile : dirname( $newfile ) ) ):
                if ( is_file( $file ) && !@$wp_filesystem->copy( $childpath, $newpath ) ):
                    $this->errors[] = '15:' . $newpath; 
                endif;
            else:
                $this->errors[] = '16:' . $newfile; 
            endif;
        endforeach;
    }

    function unset_writable() {
        if ( !$this->fs ) return FALSE; 
        global $wp_filesystem;
        $dir        = untrailingslashit( $this->css->get_child_target( '' ) );
        $child      = $this->theme_basename( '', $dir );
        $newchild   = untrailingslashit( $child ) . '-new';
        $themedir   = trailingslashit( get_theme_root() );
        $fsthemedir = $this->fspath( $themedir );
        
        if ( fileowner( $dir ) == fileowner( $themedir ) ):
            $copy   = FALSE;
            $wp_filesystem->chmod( $dir );
            
        else:
            $copy   = TRUE;
        endif;
        
        $files = $this->css->recurse_directory( $dir, NULL, TRUE );
        $errors = array();
        foreach ( $files as $file ):
            $childfile  = $this->theme_basename( $child, $this->normalize_path( $file ) );
            $newfile    = trailingslashit( $newchild ) . $childfile;
            $childpath  = $fsthemedir . trailingslashit( $child ) . $childfile;
            $newpath    = $fsthemedir . $newfile;
            if ( $copy ):
                $this->debug( 'Verifying child dir... ' . $file, __FUNCTION__, __CLASS__ );
                if ( $this->verify_child_dir( is_dir( $file ) ? $newfile : dirname( $newfile ) ) ):
                    if ( is_file( $file ) && !$wp_filesystem->copy( $childpath, $newpath ) ):
                        $errors[] = '15:' . $newpath; 
                    endif;
                else:
                    $errors[] = '16:' . $newfile; 
                endif;
            else:
                $wp_filesystem->chmod( $this->fspath( $file ) );
            endif;
        endforeach;
        if ( $copy ):
            
            $newfiles = $this->css->recurse_directory( trailingslashit( $themedir ) . $newchild, NULL, TRUE );
            $deleteddirs = $deletedfiles = 0;
            if ( count( $newfiles ) == count( $files ) ):
              
                if ( !$wp_filesystem->exists( trailingslashit( $fsthemedir ) . $child . '-old' ) )
                    $wp_filesystem->move( trailingslashit( $fsthemedir ) . $child, trailingslashit( $fsthemedir ) . $child . '-old' );
                
                if ( !$wp_filesystem->exists( trailingslashit( $fsthemedir ) . $child ) )
                    $wp_filesystem->move( trailingslashit( $fsthemedir ) . $newchild, trailingslashit( $fsthemedir ) . $child );
                
                $oldfiles = $this->css->recurse_directory( trailingslashit( $themedir ) . $child . '-old', NULL, TRUE );
                array_unshift( $oldfiles, trailingslashit( $themedir ) . $child . '-old' );
                foreach ( array_reverse( $oldfiles ) as $file ):
                    if ( $wp_filesystem->delete( $this->fspath( $file ) ) 
                        || ( is_dir( $file ) && @rmdir( $file ) ) 
                            || ( is_file( $file ) && @unlink( $file ) ) ):
                        $deletedfiles++;
                    endif;
                endforeach;
                if ( $deletedfiles != count( $oldfiles ) ):
                    $errors[] = '17:' . $deletedfiles . ':' . count( $oldfiles ); 
                endif;
            else:
                $errors[] = 18; 
            endif;
        endif;
        if ( count( $errors ) ):
            $this->errors[] = 19; 
        endif;
    }
    
    function verify_creds( $args = array() ) {
        $this->fs_prompt = $this->fs = FALSE;        
        $this->serialize_postarrays();        
        $ctcpage = apply_filters( 'chld_thm_cfg_admin_page', CHLD_THM_CFG_MENU );
        $url = is_multisite() ?  network_admin_url( 'themes.php?page=' . $ctcpage ) :
            admin_url( 'admin.php?page=' . $ctcpage );
        $nonce_url = wp_nonce_url( $url, apply_filters( 'chld_thm_cfg_action', 'ms_update' ), '_wpnonce' );
        ob_start();
        if ( $creds = request_filesystem_credentials( $nonce_url, '', FALSE, FALSE, $args ) ):           
            if ( WP_Filesystem( $creds ))                
                $this->fs = TRUE;
            else                
                $creds = request_filesystem_credentials( $nonce_url, '', TRUE, FALSE, $args );
        else:            
            WP_Filesystem();
        endif;
       
        $this->fs_prompt = ob_get_clean();
        $this->debug( 'FS: ' . $this->fs . ' PROMPT: ' . $this->fs_prompt, __FUNCTION__, __CLASS__ );
       $this->unserialize_postarrays();
    }    
    
    function fspath( $file ){
        if ( ! $this->fs ) return FALSE; // return if no filesystem access
        global $wp_filesystem;
        if ( is_dir( $file ) ):
            $dir = $file;
            $base = '';
        else:
            $dir = dirname( $file );
            $base = basename( $file );
        endif;
        $fsdir = $wp_filesystem->find_folder( $dir );
        return trailingslashit( $fsdir ) . $base;
    }       
   
    function normalize_path( $path ) {
        $path = str_replace( '\\', '/', $path );        
        $path = substr( $path, 0, 1 ) . preg_replace( '|/+|','/', substr( $path, 1 ) );
        if ( ':' === substr( $path, 1, 1 ) )
            $path = ucfirst( $path );
        return $path;
    }
    
    function check_theme_exists( $theme ) {
        $search_array = array_map( 'strtolower', array_keys( wp_get_themes() ) );
        return in_array( strtolower( $theme ), $search_array );
    }   
    
    function is_legacy() {
        return defined('CHLD_THM_CFG_PLUGINS_VERSION') 
            && version_compare( CHLD_THM_CFG_PLUGINS_VERSION, '2.0.0', '<' );
    }
    
    function is_theme( $configtype = '' ) {
       $pluginmode = apply_filters( 'chld_thm_cfg_action', NULL );
        if ( $pluginmode || ( !empty( $configtype ) && 'theme' != $configtype ) ):
            return FALSE;
        endif;
        if ( $this->is_legacy()
            && is_object( $this->css ) 
                && ( $configtype = $this->get( 'configtype' ) ) 
                    && !empty( $configtype ) && 'theme' != $configtype ):
            return FALSE;
        endif;
        return TRUE;
    }   
    
    function get_current_parent() {        
        if ( $parent = $this->get( 'parnt' ) )
            return $parent;
        else return get_template();
    }  
   
    function get_current_child() {       
        if ( $child = $this->get( 'child' ) )
            return $child;
        else return get_stylesheet();
    }        
    function toggle_debug() {
        $debug = '';
        if ( $_POST[ 'ctc_is_debug' ] ):
            $this->is_debug = 1;
        else:
            $this->is_debug = 0;
        endif;
        update_option( MS_CHILD_THEME_EDITOR . '_debug', $this->is_debug, FALSE );
        delete_site_transient( MS_CHILD_THEME_EDITOR . '_debug' );
    }
    
    function debug( $msg = NULL, $fn = NULL, $cl = NULL ) {
        if ( $this->is_debug )
            $this->debug .= ( isset( $cl ) ? $cl . '::' : '' ) . ( isset( $fn ) ? $fn . ' -- ' : '' ) . ( isset( $msg ) ? $msg . LF : '' );
    }
    
    function log_debug() {
        $this->debug( '*** END OF REQUEST ***', __FUNCTION__, __CLASS__ );
       
        set_site_transient( MS_CHILD_THEME_EDITOR . '_debug', $this->debug, 3600 );
    }
    function get_debug() {
        return get_site_transient( MS_CHILD_THEME_EDITOR . '_debug' ) . LF . $this->debug;
    }
    function cache_debug() {
        $this->debug( '*** END OF REQUEST ***', __FUNCTION__, __CLASS__ );
        $this->updates[] = array(
            'obj'   => 'debug',
            'key'   => '',
            'data'  => $this->debug,
        );
    }
    function parse_parent_stylesheet_to_source() {
        $this->css->parse_css_file( 'parnt' );
    }
    
    function parse_child_stylesheet_to_source() {
        $this->css->parse_css_file( 'child', 'style.css', 'parnt' );
    }
    
    function parse_child_stylesheet_to_target() {
        $this->css->parse_css_file( 'child', 'style.css' );
    }
    
    function parse_custom_stylesheet_to_target() {
        $this->css->parse_css_file( 'child', 'ms-separate-style.css' );
    }
        
    function parse_genesis_stylesheet_to_source() {
        $this->css->parse_css_file( 'child', 'ctc-genesis.css', 'parnt' );
    }
        
    function parse_additional_stylesheets_to_source() {       
		foreach ( $this->css->addl_css as $file ):
			$this->css->parse_css_file( 'parnt', $file );
		endforeach;
		$this->debug( print_r( $this->css->addl_css, TRUE ), __FUNCTION__, __CLASS__ );
    }
    
    function reset_child_theme() {
        $parnt  = $this->get( 'parnt' );
        $child  = $this->get( 'child' );
        $name   = $this->get( 'child_name' );
        $this->css = new ms_theme_editor_css();
        $this->css->set_prop( 'parnt', $parnt );
        $this->css->set_prop( 'child', $child );
        $this->css->set_prop( 'child_name', $name );
        $this->css->set_prop( 'enqueue', 'enqueue' );
        $this->backup_or_restore_file( 'header.php', TRUE );
        $this->delete_child_file( 'header.msbackup', 'php' );
        $this->backup_or_restore_file( 'style.css', TRUE );
        $this->delete_child_file( 'style.msbackup', 'css' );
        $this->backup_or_restore_file( 'ms-style.css', TRUE );
        $this->delete_child_file( 'ms-style.msbackup', 'css' );
    }
    
    function copy_theme_mods( $from, $to ) {
        if ( strlen( $from ) && strlen( $to ) ):
            $this->set_theme_mods( $to, $this->get_theme_mods( $from ) );
            do_action( 'chld_thm_cfg_copy_theme_mods', $from, $to );
        endif;
    }   
    
    function get_theme_mods( $theme ){
        
        $active_theme = get_stylesheet();
        
        $mods = get_option( 'theme_mods_' . $theme );
        if ( $active_theme == $theme ):
            $this->debug( 'from is active, using active widgets', __FUNCTION__, __CLASS__ );
            
            $mods[ 'sidebars_widgets' ][ 'data' ] = retrieve_widgets();
        else:
            $this->debug( 'from not active, using theme mods widgets', __FUNCTION__, __CLASS__ );
            
            $mods[ 'sidebars_widgets' ][ 'data' ] = empty( $mods[ 'sidebars_widgets' ][ 'data' ] ) ?
                array( 'wp_inactive_widgets' => array() ) : $mods[ 'sidebars_widgets' ][ 'data' ];
        endif;
        return $mods;
    }
    
    function set_theme_mods( $theme, $mods ){
        $active_theme = get_stylesheet();
        $widgets = $mods[ 'sidebars_widgets' ][ 'data' ];
        if ( $active_theme == $theme ):
            $this->debug( 'to active, setting active widgets', __FUNCTION__, __CLASS__ );
            
            wp_set_sidebars_widgets( $mods[ 'sidebars_widgets' ][ 'data' ] );
            
            unset( $mods[ 'sidebars_widgets' ] );
        else:
            $this->debug( 'child not active, saving widgets in theme mods', __FUNCTION__, __CLASS__ );
            
            $mods[ 'sidebars_widgets' ][ 'time' ] = time();
        endif;
        
        update_option( 'theme_mods_' . $theme, $mods );
    }
    
    function network_enable() {
        if ( $child = $this->get( 'child' ) ):
            $allowed_themes = get_site_option( 'allowedthemes' );
            $allowed_themes[ $child ] = true;
            update_site_option( 'allowedthemes', $allowed_themes );
        endif;
    }
    
    function backup_or_restore_file( $source, $restore = FALSE, $target = NULL ){
        $action = $restore ? 'Restore' : 'Backup';
        $this->debug( LF . LF . $action . ' main stylesheet...', __FUNCTION__, __CLASS__ );
        if ( !$this->fs ): 
            $this->debug( 'No filesystem access, returning', __FUNCTION__, __CLASS__ );
            return FALSE; 
        endif;
        list( $base, $suffix ) = explode( '.', $source );
        if ( empty( $target ) )
            $target = $base . '.msbackup.' . $suffix;
        if ( $restore ):
            $source = $target;
            $target = $base . '.' . $suffix;        
        endif;
        $fstarget = $this->fspath( $this->css->get_child_target( $target ) );
        $fssource = $this->fspath( $this->css->get_child_target( $source ) );
        global $wp_filesystem;
        if ( ( !$wp_filesystem->exists( $fssource ) ) || ( !$restore && $wp_filesystem->exists( $fstarget ) ) ):
            $this->debug( 'No stylesheet, returning', __FUNCTION__, __CLASS__ );
            return FALSE;
        endif;
        if ( $wp_filesystem->copy( $fssource, $fstarget, FS_CHMOD_FILE ) ):
            $this->debug( 'Filesystem ' . $action . ' successful', __FUNCTION__, __CLASS__ );
            return TRUE;
        else:
            $this->debug( 'Filesystem ' . $action . ' failed', __FUNCTION__, __CLASS__ );
            return FALSE;
        endif;
    }
    
    function rewrite_stylesheet_header(){
        $this->debug( LF . LF . 'Rewriting main stylesheet header...', __FUNCTION__, __CLASS__ );
        if ( !$this->fs ): 
            $this->debug( 'No filesystem access, returning', __FUNCTION__, __CLASS__ );
            return FALSE; 
        endif;
        $origcss        = $this->css->get_child_target( 'style.css' );
        $fspath         = $this->fspath( $origcss );
        global $wp_filesystem;
        if( !$wp_filesystem->exists( $fspath ) ): 
            $this->debug( 'No stylesheet, returning', __FUNCTION__, __CLASS__ );
            return FALSE;
        endif;
       
        $contents       = $wp_filesystem->get_contents( $fspath );
        $child_headers  = $this->css->get_css_header();
        if ( is_array( $child_headers ) )
            $regex      = implode( '|', array_map( 'preg_quote', array_keys( $child_headers ) ) );
        else $regex     = 'NO HEADERS';
        $regex          = '/(' . $regex . '):.*$/';
        $this->debug( 'regex: ' . $regex, __FUNCTION__, __CLASS__ );
        $header         = str_replace( "\r", LF, substr( $contents, 0, 8192 ) );
        $contents       = substr( $contents, 8192 );
        $this->debug( 'original header: ' . LF . substr( $header, 0, 1024 ), __FUNCTION__, __CLASS__ );
       
        $header = preg_replace( '#\@import\s+url\(.+?\);\s*#s', '', $header );
       
        $headerdata     = explode( "\n", $header );
        $in_comment     = 0;
        $found_header   = 0;
        $headerdone     = 0;
        $newheader      = '';
        if ( $headerdata ):
            $this->debug( 'parsing header...', __FUNCTION__, __CLASS__ );
            foreach ( $headerdata as $n => $headerline ):
                preg_match_all("/(\*\/|\/\*)/", $headerline, $matches );
                if ( $matches ):
                    foreach ( $matches[1] as $token ): 
                        if ( '/*' == $token ):
                            $in_comment = 1;
                        elseif ( '*/' == $token ):
                            $in_comment = 0;
                        endif;
                    endforeach;
                endif;
                if ( $in_comment ):
                    $this->debug( 'in comment', __FUNCTION__, __CLASS__ );
                    if ( preg_match( $regex, $headerline, $matches ) && !empty( $matches[ 1 ] ) ):
                        $found_header = 1;
                        $key = $matches[ 1 ];
                        $this->debug( 'found header: ' . $key, __FUNCTION__, __CLASS__ );
                        if ( array_key_exists( $key, $child_headers ) ):
                            $this->debug( 'child header value exists: ', __FUNCTION__, __CLASS__ );
                            $value = trim( $child_headers[ $key ] );
                            unset( $child_headers[ $key ] );
                            if ( $value ):
                                $this->debug( 'setting ' . $key . ' to ' . $value, __FUNCTION__, __CLASS__ );
                                $count = 0;
                                $headerline = preg_replace( 
                                    $regex, 
                                    ( empty( $value ) ? '' : $key . ': ' . $value ), 
                                    $headerline
                                );
                            else:
                                $this->debug( 'removing ' . $key, __FUNCTION__, __CLASS__ );
                                continue;
                            endif;
                        endif;
                    endif;
                    $newheader .= $headerline . LF;
                elseif ( $found_header && !$headerdone ): 
                    foreach ( $child_headers as $key => $value ):
                        $this->debug( 'inserting ' . $key . ': ' . $value, __FUNCTION__, __CLASS__ );
                        if ( empty( $value ) ) continue;
                        $newheader .= $key . ': ' . trim( $value ) . "\n";
                    endforeach;
                   
                    $newheader .= $headerline . "\n" . $this->css->get_css_imports();
                    $headerdone = 1;
                else:
                  
                    $newheader .= $headerline . LF;
                endif;
            endforeach;
            $this->debug( 'new header: ' . LF . substr( $newheader, 0, 1024 ), __FUNCTION__, __CLASS__ );
            if ( !$found_header ) return FALSE;
        endif;
        $contents = $newheader . $contents;
        if ( FALSE === $wp_filesystem->put_contents( $fspath, $contents ) ):
        else:           
        endif;
    }    
  
    function get_child_stylesheet() {
        $handling = $this->get( 'handling' );
        if ( 'separate' == $handling )
            return 'ms-separate-style.css';
        elseif ( 'reset' == $this->childtype )
            return FALSE;
        else
            return 'style.css';
    }
   
    function repair_header() {
        
        if ( ! $this->get( 'cssunreg' ) && !$this->get( 'csswphead' ) ) return;
        $this->debug( 'repairing parent header', __FUNCTION__, __CLASS__ );
        
        $this->copy_parent_file( 'header' );
        
        $this->backup_or_restore_file( 'header.php' );
        
        global $wp_filesystem;
        $cssstr = "get_template_directory_uri()";
        $wphstr = '<?php // MODIFIED BY CTC' . LF . 'wp_head();' . LF . '?>' . LF . '</head>';
        $filename = $this->css->get_child_target( 'header.php' );
        $contents = $wp_filesystem->get_contents( $this->fspath( $filename ) );
        
        
        if ( $this->get( 'cssunreg' ) || $this->get( 'csswphead' ) ):
            $repairs = 0;
            $contents = preg_replace( "#(get_bloginfo\(\s*['\"]stylesheet_url['\"]\s*\)|get_stylesheet_uri\(\s*\))#s", $cssstr . ' . "/style.css"', $contents, -1, $count ); 
            $repairs += $count;
            $contents = preg_replace( "#([^_])bloginfo\(\s*['\"]stylesheet_url['\"]\s*\)#s", "$1echo " . $cssstr . ' . "/style.css"', $contents, -1, $count );
            $repairs += $count;
            $contents = preg_replace( "#([^_])bloginfo\(\s*['\"]stylesheet_directory['\"]\s*\)#s", "$1echo " . $cssstr, $contents, -1, $count );
            $repairs += $count;
            $contents = preg_replace( "#(trailingslashit\()?(\s*)get_stylesheet_directory_uri\(\s*\)(\s*\))?\s*\.\s*['\"]\/?([\w\-\.\/]+?)\.css['\"]#s", 
                "$2echo $cssstr . '$3.css'", $contents, -1, $count );
            $repairs += $count;
            if ( $repairs )
                $this->css->set_prop( 'parntloaded', TRUE );
        endif;

        
        if ( $this->get( 'csswphead' ) ):
            $contents = preg_replace( "#wp_head\(\s*\)\s*;#s", '', $contents );
            $contents = preg_replace( "#</head>#s", $wphstr, $contents );
            $contents = preg_replace( "#\s*<\?php\s*\?>\s*#s", LF, $contents ); // clean up
        endif;
        
        $this->debug( 'Writing to filesystem: ' . $filename . LF . $contents, __FUNCTION__, __CLASS__ );
        if ( FALSE === $wp_filesystem->put_contents( $this->fspath( $filename ), $contents ) ):
            $this->debug( 'Filesystem write failed, returning.', __FUNCTION__, __CLASS__ );
            return FALSE; 
        endif;
    }
    
    function evaluate_signals() {
        if ( !isset( $_POST[ 'ms_theme_child_analysis' ] ) ) return;
        $analysis   = json_decode( urldecode( $_POST[ 'ms_theme_child_analysis' ] ) );
        
        $unregs     = array( 'thm_past_wphead', 'thm_unregistered', 'dep_unregistered', 'css_past_wphead', 'dep_past_wphead' );
        
        $baseline = $this->get( 'ignoreparnt' ) ? 'child' : 'parnt';
        $this->debug( 'baseline: ' . $baseline, __FUNCTION__, __CLASS__ );

       
        $this->css->parnt_deps  = array();
        $this->css->child_deps  = array();
        $this->css->addl_css    = array();
        
        
        if ( isset( $analysis->parnt->imports ) ):
            foreach ( $analysis->parnt->imports as $import ):
                if ( preg_match( '%(https?:)?//%', $import ) ) continue; 
                $this->css->addl_css[] = sanitize_text_field( $import );
            endforeach;
        endif;

        
        if ( isset( $analysis->{ $baseline } ) ):
            if ( isset( $analysis->{ $baseline }->deps ) ):
                foreach ( $analysis->{ $baseline }->deps[ 0 ] as $deparray ):
                   
                    if ( 'chld_thm_cfg_parent' == $deparray[ 0 ] )
                        continue;
                    if ( !in_array( $deparray[ 0 ], $unregs ) ):
                          $this->css->parnt_deps[] = $deparray[ 0 ];
                    endif;
                    if ( !preg_match( "/^style.*?\.css$/", $deparray[ 1 ] ) ):
                        $this->css->addl_css[] = sanitize_text_field( $deparray[ 1 ] );
                    endif;
                endforeach;
                foreach ( $analysis->{ $baseline }->deps[ 1 ] as $deparray ):
                    if ( 'chld_thm_cfg_child' == $deparray[ 0 ] )
                        continue;
                    if ( !in_array( $deparray[ 0 ], $unregs ) ):
                        $this->css->child_deps[] = $deparray[ 0 ];
                    endif;
                    if ( 'separate' == $this->get( 'handling' ) || !empty( $analysis->{ $baseline }->signals->ctc_child_loaded ) ):
                        if ( !preg_match( "/^style.*?\.css$/", $deparray[ 1 ] ) ):
                            $this->css->addl_css[] = sanitize_text_field( $deparray[ 1 ] );
                        endif;
                    endif;
                endforeach;
            endif;
        endif;
        
        if ( isset( $analysis->{ $baseline }->signals ) ):
            $this->css->set_prop( 'hasstyles', isset( $analysis->{ $baseline }->signals->thm_no_styles ) ? 0 : 1 );
            $this->css->set_prop( 'csswphead', isset( $analysis->{ $baseline }->signals->thm_past_wphead ) ? 1 : 0 );
            $this->css->set_prop( 'cssunreg', isset( $analysis->{ $baseline }->signals->thm_unregistered ) ? 1 : 0 );
            if ( isset( $analysis->{ $baseline }->signals->thm_parnt_loaded ) ):
                $this->set_enqueue_priority( $analysis, $baseline );
            endif;
        endif;
        
        if ( isset( $analysis->child->signals->thm_past_wphead ) )
            $this->css->set_prop( 'csswphead', 1 );
        if ( isset( $analysis->child->signals->thm_unregistered ) )
            $this->css->set_prop( 'cssunreg', 1 );
      
        if ( isset( $analysis->child->signals->thm_notheme ) )
            $this->css->set_prop( 'cssnotheme', 1 );
        if ( isset( $analysis->child->signals->thm_child_loaded ) ):
            $this->css->set_prop( 'childloaded', $analysis->child->signals->thm_child_loaded );
            $this->set_enqueue_priority( $analysis, 'child' );
        else:
            $this->css->set_prop( 'childloaded',  0 );
        endif;
        
        if ( isset( $analysis->child->signals->thm_parnt_loaded ) ):
            $this->css->set_prop( 'parntloaded',  $analysis->child->signals->thm_parnt_loaded );
            if ( 'thm_unregistered' != $analysis->child->signals->thm_parnt_loaded ):
                array_unshift( $this->css->child_deps, $analysis->child->signals->thm_parnt_loaded );
            endif;
        else:
            $this->css->set_prop( 'parntloaded',  0 );
        endif;            
        
        if ( isset( $analysis->child->signals->ctc_parnt_reorder ) )
            $this->css->set_prop( 'reorder', 1 );
       
        if ( isset( $analysis->child->signals->ctc_gen_loaded ) )
            $this->genesis = TRUE;
    }    
    
    function set_enqueue_priority( $analysis, $baseline ){
        foreach ( $analysis->{ $baseline }->irreg as $irreg ):
            $handles = explode( ',', $irreg );
            $priority = array_shift( $handles );
            $handle = $analysis->{ $baseline }->signals->{ 'thm_' . $baseline . '_loaded' };
            if ( in_array( $handle, $handles ) ):
                $this->debug( '(baseline: ' . $baseline . ') match: ' . $handle . ' setting priority: ' . $priority, __FUNCTION__, __CLASS__ );
                $this->css->set_prop( 'qpriority', $priority );
                break;
            endif;
        endforeach;
    }
}