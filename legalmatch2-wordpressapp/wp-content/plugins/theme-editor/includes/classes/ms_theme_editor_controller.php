<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

    class ms_theme_editor_controller {
        
        static $instance;                
		static function init() {
		defined( 'CHLD_THM_CFG_MENU' ) or 
		define( 'CHLD_THM_CFG_MENU', 'ms_child_theme_editor' );

		add_action( 'admin_menu',               'ms_theme_editor_controller::admin' );
		add_action( 'wp_ajax_ms_update',       'ms_theme_editor_controller::save' );
		add_action( 'wp_ajax_ms_query',        'ms_theme_editor_controller::query' );
		add_action( 'wp_ajax_ms_theme_summary',  'ms_theme_editor_controller::ms_theme_summary');
		}
        
		static function ms_theme_summary() {
			
            // 2nd method for analysis theme 
			// using wp_remote_get method
			if(current_user_can('manage_options')){
				$argument = array();			
				$argument['template' ]     = isset( $_POST[ 'template' ] ) ? $_POST[ 'template' ] : '';
				$argument['stylesheet']    = isset( $_POST[ 'stylesheet' ] ) ? $_POST[ 'stylesheet' ] : '';
				$argument['ms_theme_editor_preview']   = wp_create_nonce();
				$argument['now']          = time();
				
				$url = home_url( '/' ) . '?' . build_query( $argument ); 
				
				$parameter = array();
				$parameter['cookies']       = $_COOKIE;
				$parameter['user-agent']    = $_SERVER[ 'HTTP_USER_AGENT' ];
				$parameter['sslverify']    = apply_filters( 'https_local_ssl_verify', false );
				
				$information = wp_remote_get( $url, $parameter );
				$ms_output  = array();
				if ( is_wp_error( $information ) )
				{
					$ms_output[ 'signals' ][ 'httperr' ] = $information->get_error_message();
				}
				else 
				{ 
					$ms_output[ 'signals' ] = array();
					$ms_output[ 'body' ] = $information[ 'body' ];
				}
				echo $information[ 'body' ];
				die();
			}
		}		
		
		static function ctc() {
           
            if ( !isset( self::$instance ) ):
                self::$instance = new ms_theme_editor_admin( __FILE__ );
            endif;
			return self::$instance;
        }
        
        static function save() {
           if(current_user_can('manage_options')){
				self::ctc()->ajax_save_postdata();
		   }
        }
        
        static function query() {		
           if(current_user_can('manage_options')){
				self::ctc()->ajax_query_css();
		   }
        }                
           
        static function analyze() {
            self::ctc()->ajax_analyze();
        }
    
        static function admin() {	
		
			$hook = add_submenu_page( 
				'theme_editor_theme', 
				__( 'Child Theme', 'te-editor' ), 
				__( 'Child Theme', 'te-editor' ),
				'manage_options', 
				'ms_child_theme_editor',
				'ms_theme_editor_controller::render'
			);
			
			add_submenu_page( 
				'theme_editor_theme', 
				__( 'Child Theme Permission', 'te-editor' ), 
				__( 'Child Theme Permission', 'te-editor' ),
				'manage_options', 
				'ms_child_theme_editor_control',
				'ms_theme_editor_controller::ms_child_theme_control'
			);		
			add_action( 'load-' . $hook, 'ms_theme_editor_controller::page_init' );        
        }	

		static function ms_child_theme_control()
		{
			include ( MS_THEME_EDITOR_DIR . '/includes/forms/ms-child_theme_permission_control.php' );
		}
		static function page_init() {
			self::ctc()->ctc_page_init();
        }        
        static function render() {
           self::ctc()->render();
        }
    }    