<?php namespace te\app\plg_cnt;
use te\app\thm_cnt\theme_editor_theme_controller;
class theme_editor_plugin_controller {
  public function __construct() {
	 
  }
  public function te_get_plugin_data() {
			if ( !current_user_can( 'edit_plugins' ) ) {
			wp_die( '<p>' . __( 'You do not have sufficient permissions to edit plugins for this site.', 'te-editor' ) . '</p>' );
		}
		
		$plugins = get_plugins();

		if ( empty( $plugins ) ) {
			wp_die( '<p>' . __( 'There are no plugins installed on this site.', 'te-editor' ) . '</p>' );
		}
		
		if ( isset( $_REQUEST['plugin'] ) ) {
			$plugin = stripslashes( esc_html( $_REQUEST['plugin'] ) );
		}
		if ( isset( $_REQUEST['file'] ) ) {
			$file = stripslashes( esc_html( $_REQUEST['file'] ) );
		}

		if ( empty( $plugin) ) {
			$plugin = array_keys( $plugins );
			$plugin = $plugin[0];
		}
		$plugin_files[] = $plugin;
		
		if ( empty( $file ) ) {
			$file = $plugin_files[0];
		}
		else {
			$file = stripslashes( $file );
			$plugin = $file;
		}
		$pf = theme_editor_theme_controller::get_files_and_folders( ( WPWINDOWS ) ? str_replace( "/", "\\", WP_PLUGIN_DIR . '/' . $file ) : WP_PLUGIN_DIR . '/' . $file, 0, 'plugin' );
		foreach( $pf as $plugin_file ) {
			foreach( $plugin_file as $k => $p) {
				if ( $k == 'file' ) {
					$plugin_files[] = $p;
				}
			}
		}
		
		$file = validate_file_to_edit( ( WPWINDOWS ) ? str_replace( "/", "\\", $file ) : $file, $plugin_files );
		$current_plugin_root = WP_PLUGIN_DIR . '/' . dirname( $file );
		$real_file = WP_PLUGIN_DIR . '/' . $plugin;
		
		if ( isset( $_POST['new-content'] ) && file_exists( $real_file ) && is_writable( $real_file ) ) {
			$new_content = stripslashes( $_POST['new-content'] );
			if ( file_get_contents( $real_file ) === $new_content ) {
				
			}
			else {
				$f = fopen( $real_file, 'w+' );
				fwrite( $f, $new_content );
				fclose( $f );
			}
		}
		
		$content = file_get_contents( $real_file );

		$content = esc_textarea( $content );
		
		$scroll_to = isset( $_REQUEST['scroll_to'] ) ? (int) $_REQUEST['scroll_to'] : 0;
		
		$data = array(
			'plugins' => $plugins,
			'plugin' => $plugin,
			'plugin_files' => $plugin_files,
			'current_plugin_root' => $current_plugin_root,
			'real_file' => $real_file,
			'content' => $content,
			'scroll_to' => $scroll_to,
			'file' => str_replace('\\','/',$file),
			'content-type' => 'plugin'
		);
		return $data;
  }
    public static function download_plugin( $plugin_name ) {
    if ( current_user_can( 'edit_plugins' ) ) {
      $slash = '/';
      if ( WPWINDOWS ) {
        $slash = '\\';
      }
      //Get the directory to zip
      $plugin_name = basename( $plugin_name );
      $position = strpos( $plugin_name, '.' );
      $directory = WP_PLUGIN_DIR . $slash . $plugin_name . $slash;
      $filename = $plugin_name . '.zip';
      if ( is_dir( $directory ) ) {
       $zip = theme_editor_theme_controller::compress( $directory, $filename );
        if ( $zip ) {
          header( 'Content-Disposition: attachment; filename="' . $plugin_name . '.zip' . '"');
          header( 'Content-Description: File Transfer' );
          header( 'Content-Type: application/octet-stream' );
          header( 'Content-Transfer-Encoding: binary' );
          header( 'Pragma: public' );
          header( 'Content-Length: ' . filesize( $filename ) );
          ob_clean();
          flush();
          readfile( $filename );
          unlink( $filename );
          exit;
        }
        else {
          wp_redirect( admin_url( 'plugins.php?page=theme_editor_plugin&error=3' ) );
          exit;
        }
      }
      else {
        wp_redirect( admin_url( 'plugins.php?page=theme_editor_plugin&error=2' ) );
        exit;
      }
    }
    else {
      wp_redirect( admin_url( 'plugins.php?page=theme_editor_plugin&error=1' ) );
      exit;
    }
  }
}