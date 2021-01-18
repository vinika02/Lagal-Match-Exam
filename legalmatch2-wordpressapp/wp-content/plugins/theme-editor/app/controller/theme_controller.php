<?php namespace te\app\thm_cnt;
use ZipArchive;
class theme_editor_theme_controller {
	    var $image_type_posibilities = array('png','jpg','gif'); 
		var $download_type_possibilities = array('zip','eot','svg','ttf','woff','otf','woff2','mo','po','pot');
		var $defcmt = 'cobalt';
		/*
		* construct
		*/
		public function __construct() {
		 $opt = get_option('mk_te_settings_options');
		 if(isset($opt['code_editor_theme'])) {
			$this->defcmt = $opt['code_editor_theme']; 
		 }
		}
		/*
		* Theme Data
		*/
		public function te_get_theme_data() {
      	if ( WP_34 ) {
			$themes = wp_get_themes();
		}
		else {
			$themes = get_themes();
		}
		if ( empty( $themes ) ) {
			wp_die( '<p>' . __( 'There are no themes installed on this site.', 'tm-editor' ) . '</p>' );
		}
		if ( isset( $_REQUEST['theme'] ) ) {
			$theme = stripslashes( esc_html( $_REQUEST['theme'] ) );
		}
		if ( isset( $_REQUEST['file'] ) ) {
		    $file = stripslashes( esc_html( $_REQUEST['file'] ) );
			$theme = $_REQUEST['file'];
		}
		
		if ( empty( $theme ) ) {
			if ( WP_34 ) {
				$theme = wp_get_theme();
			}
			else {
				$theme = get_current_theme();
			}
		}
			$stylesheet = '';
		if ( $theme && WP_34 ) {
			$stylesheet = urldecode( $theme );
			if ( is_object( $theme ) ) {
				$stylesheet = urldecode( $theme->stylesheet );
			}
		}
		elseif ( WP_34 ) {
			$stylesheet = get_stylesheet();
		}
		if ( WP_34 ) {
			$wp_theme = wp_get_theme( $stylesheet );
		}
		else {
			$wp_theme = '';
		}
		
		if ( empty( $file ) ) {
			if ( WP_34 ) {
				$file = basename( $wp_theme['Stylesheet Dir'] ) . '/style.css';
			}
			else {
				$file = basename( $themes[ $theme ]['Stylesheet Dir'] ) . '/style.css';
			}
		}
		else {
			$file = stripslashes( $file );
		}
		if ( WP_34 ) {
			$tf = $this->get_files_and_folders( ( WPWINDOWS ) ? str_replace( "/", "\\", $wp_theme['Theme Root'] . '/' . $file ) : $wp_theme['Theme Root'] . '/' . $file, 0, 'theme' );
		}
		else {
			$tf = $this->get_files_and_folders( ( WPWINDOWS ) ? str_replace( "/", "\\", $themes[ $theme ]['Theme Root'] . '/' . $file ) : $themes[ $theme ]['Theme Root'] . '/' . $file, 0, 'theme' );
		}
		foreach ( $tf as $theme_file ) {
			foreach ( $theme_file as $k => $t ) {
				if ( $k == 'file' ) {
					$theme_files[] = $t;
				}
			}
		}		
		$file = validate_file_to_edit( ( WPWINDOWS ) ? str_replace( "/", "\\", $file ) : $file, $theme_files );
		if ( WP_34 ) {
			$current_theme_root = $wp_theme['Theme Root'] . '/' . dirname( $file ) . '/';
		}
		else {
			$current_theme_root = $themes[ $theme ]['Theme Root'] . '/' . dirname( $file ) . '/';
		}
		$real_file = $current_theme_root . basename( $file );
				
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
		
		$scroll_to = isset( $_REQUEST['scroll_to'] ) ? ( int ) $_REQUEST['scroll_to'] : 0;
		
		$data = array(
			'themes' => $themes,
			'theme' => $theme,
			'wp_theme' => $wp_theme,
			'stylesheet' => $stylesheet,
			'theme_files' => $theme_files,
			'current_theme_root' => $current_theme_root,
			'real_file' => $real_file,
			'content' => $content,
			'scroll_to' => $scroll_to,
			'file' => $file,
			'content-type' => 'theme'
		);
		return $data;
	}
	/*
	* Get Files And Folders
	*/
	public static function get_files_and_folders( $dir, $contents, $type ) {
		$slash = '/';
		if ( WPWINDOWS ) {
		  $slash = '\\';
		}
		$output = array();
		if ( is_dir( $dir ) ) {
		  if ( $handle = opendir( $dir ) ) {
			$size_document_root = strlen( $_SERVER['DOCUMENT_ROOT'] );
			$pos = strrpos( $dir, $slash );
			$topdir = substr( $dir, 0, $pos + 1 );
			$i = 0;
			while ( false !== ( $file = readdir( $handle ) ) ) {
			  if ( $file != '.' && $file != '..' && substr( $file, 0, 1 ) != '.' ) {
				$rows[ $i ]['data'] = $file;
				$rows[ $i ]['dir'] = is_dir( $dir . $slash . $file );
				$i++;
			  }
			}
			closedir( $handle );
		  }
	
		  if ( isset( $rows ) ) {  
			$size = count( $rows );
			$rows = self::mk_s_r( $rows );
			for( $i = 0; $i < $size; ++$i ) {
			  $topdir = $dir . $slash . $rows[ $i ]['data'];
			  $output[ $i ]['name'] = $rows[ $i ]['data'];
			  $output[ $i ]['path'] = $topdir;
			  if ( $rows[ $i ]['dir'] ) {
				$output[ $i ]['filetype'] = 'folder';
				$output[ $i ]['extension'] = 'folder';
				$output[ $i ]['filesize'] = '';
			  }
			  else {
				$output[ $i ]['writable'] = false;
				if ( is_writable( $output[ $i ]['path'] ) ) {
				  $output[ $i ]['writable'] = true;
				}
				$output[ $i ]['filetype'] = 'file';
				$path = pathinfo( $output[ $i ]['name'] );
				if ( isset( $path['extension'] ) ) {
				  $output[ $i ]['extension'] = strtolower( $path['extension'] );
				}
				$output[ $i ]['filesize'] = '( ' . round( filesize( $topdir ) * .0009765625, 2) . ' KB)';
				if ( $type == 'theme' ) {
				  $oldFile = str_replace( realpath( get_theme_root() ) . $slash, '', $output[ $i ]['path'] );
				  $oldFile2 = str_replace(get_theme_root(),'', $oldFile);
				  $mlu = str_replace('//','/', $oldFile2);
				  $output[ $i ]['file'] = str_replace('/\\','\\', $oldFile2);
				  //$output[ $i ]['file'] =  $oldFile;
				  $output[ $i ]['file'] = str_replace('//','/', $output[ $i ]['file']);
				  $output[ $i ]['url'] = get_theme_root_uri().$slash.$mlu;
				}
				else {
				  //$output[ $i ]['file'] = str_replace( realpath( WP_PLUGIN_DIR ) . $slash, '', $output[ $i ]['path'] );
				   $oldFile = str_replace( realpath( WP_PLUGIN_DIR ) . $slash, '', $output[ $i ]['path'] );
				  $oldFile2 = str_replace(WP_PLUGIN_DIR,'', $oldFile);
				  $mlu = str_replace('//','/', $oldFile2);
				  $output[ $i ]['file'] = str_replace('/\\','\\', $oldFile2);
				  $output[ $i ]['url'] = plugins_url() . $slash . $mlu;
				}
			  }
			}
		  }
		  else {
			$output[-1] = 'This Folder contains no contents!';
		  }
		}
		elseif ( is_file( $dir ) ) {
		  if ( isset( $contents ) && $contents == 1 ) {
			$output['name'] = basename( $dir );
			$output['path'] = $dir;
			$output['filetype'] = 'file';
			$path = pathinfo( $output['name'] );
			if ( isset( $path['extension'] ) ) {
			  $output['extension'] = strtolower( $path['extension'] );
			}
			$output['content'] = file_get_contents( $dir );
			$output['writable'] = false;
			if ( is_writable( $output['path'] ) ) {
			  $output['writable'] = true;
			}
			if ( $type == 'theme' ) {
			  $output['file'] = str_replace( realpath( get_theme_root() ) . $slash, '', $output['path'] );
			  $output['url'] = get_theme_root_uri() . $slash . $output['file'];
			}
			else {
			  $output['file'] = str_replace( realpath( WP_PLUGIN_DIR ) . $slash, '', $output['path'] );
			  $output['url'] = plugins_url() . $slash . $output['file'];
			}
		  }
		  else {
			$pos = strrpos( $dir, $slash );
			$newdir = substr( $dir, 0, $pos );
			if ( $handle = opendir( $newdir ) ) {
			  $size_document_root = strlen( $_SERVER['DOCUMENT_ROOT'] );
			  $pos = strrpos( $newdir, $slash );
			  $topdir = substr( $newdir, 0, $pos + 1 );
			  $i = 0;
			  while ( false !== ( $file = readdir( $handle ) ) ) {
				if ( $file != '.' && $file != '..' && substr( $file, 0, 1 ) != '.' /*&& $this->allowed_files( $newdir, $file )*/ ) {
				  $rows[ $i ]['data'] = $file;
				  $rows[ $i ]['dir'] = is_dir( $newdir . $slash . $file );
				  $i++;
				}
			  }
			  closedir( $handle );
			}
		  
			if ( isset( $rows ) ) {
			  $size = count( $rows );
			  $rows = self::mk_s_r( $rows );
			  for( $i = 0; $i < $size; ++$i ) {
				$topdir = $newdir . $slash . $rows[ $i ]['data'];
				$output[ $i ]['name'] = $rows[ $i ]['data'];
				$output[ $i ]['path'] = $topdir;
				if ( $rows[ $i ]['dir'] ) {
				  $output[ $i ]['filetype'] = 'folder';
				  $output[ $i ]['extension'] = 'folder';
				  $output[ $i ]['filesize'] = '';
				}
				else {
				  $output[ $i ]['writable'] = false;
				  if ( is_writable( $output[ $i ]['path'] ) ) {
					$output[ $i ]['writable'] = true;

				  }
				  $output[ $i ]['filetype'] = 'file';
				  $path = pathinfo( $rows[ $i ]['data'] );
				  if ( isset( $path['extension'] ) ) {
					$output[ $i ]['extension'] = strtolower( $path['extension'] );
				  }
				  $output[ $i ]['filesize'] = '( ' . round( filesize( $topdir ) * .0009765625, 2) . ' KB)';
				}
				if ( $output[ $i ]['path'] == $dir ) {
				  $output[ $i ]['content'] = file_get_contents( $dir );
				}
				$output[ $i ]['writable'] = false;
				if ( is_writable( $output[ $i ]['path'] ) ) {
				  $output[ $i ]['writable'] = true;
				}
				if ( $type == 'theme' ) {
				  $output[ $i ]['file'] = str_replace( realpath( get_theme_root() ) . $slash, '', $output[ $i ]['path'] );
				  $output[ $i ]['url'] = get_theme_root_uri() . $slash . $output[ $i ]['file'];
				}
				else {
				  $output[ $i ]['file'] = str_replace( realpath( WP_PLUGIN_DIR ) . $slash, '', $output[ $i ]['path'] );
				  $output[ $i ]['url'] = plugins_url() . $slash . $output[ $i ]['file'];
				}
			  }
			}
			else {
			  $output[-1] = 'Unable to open!';
			}
		  }
		}
		else {
		  $output[-1] = 'Unable to open!';
		};
		 return $output;
	  }
	/*
	* mk_s_r
	*/ 
	  public static function mk_s_r( $data ) {
		$size = count( $data );
	
		for( $i = 0; $i < $size; ++$i ) {
		  $row_num = self::mk_f_s( $i, $size, $data );
		  $tmp = $data[ $row_num ];
		  $data[ $row_num ] = $data[ $i ];
		  $data[ $i ] = $tmp;
		}
	
		return $data;
	  }
    /*
	* mk_f_s
	*/
  public static function mk_f_s( $i, $end, $data ) {
    $min['pos'] = $i;
    $min['value'] = $data[ $i ]['data'];
    $min['dir'] = $data[ $i ]['dir'];
    for(; $i < $end; ++$i ) {
      if ( $data[ $i ]['dir'] ) {
        if ( $min['dir'] ) {
          if ( $data[ $i ]['data'] < $min['value'] ) {
            $min['value'] = $data[ $i ]['data'];
            $min['dir'] = $data[ $i ]['dir'];
            $min['pos'] = $i;
          }
        }
        else {
          $min['value'] = $data[ $i ]['data'];
          $min['dir'] = $data[ $i ]['dir'];
          $min['pos'] = $i;
        }
      }
      else {
        if (!$min['dir'] && $data[ $i ]['data'] < $min['value'] ) {
          $min['value'] = $data[ $i ]['data'];
          $min['dir'] = $data[ $i ]['dir'];
          $min['pos'] = $i;
        }
      }
    }
    return $min['pos'];
  }
    /*
	* download_file
	*/
    public  function download_file( $file_path, $type ) {
		if ( ( $type == 'theme' && current_user_can( 'edit_themes' ) ) || ( $type == 'plugin' && current_user_can( 'edit_plugins' ) ) )     {
			  $slash = '/';
			  if ( WPWINDOWS ) {
				$slash = '\\';
			  }
			  if ( file_exists( $file_path ) ) {
				$content = file_get_contents( $file_path );
				$filename = basename( $file_path );
				$filesize = strlen( $content);
				header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
				header( 'Content-Description: File Transfer' );
				header( 'Content-Disposition: attachment; filename=' . $filename );
				header( 'Content-Length: ' . $filesize );
				header( 'Expires: 0' );
				header( 'Pragma: public' );
				ob_clean();
				flush();
				echo $content;
				exit;
			  }
		}
	}
   /*
   * Delete Dirtory
   */
   public function deleteDir($dirPath) {
		if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
			$dirPath .= '/';
		}
		$files = glob($dirPath . '*', GLOB_MARK);
		foreach ($files as $file) {
			if (is_dir($file)) {
				$this->deleteDir($file);
			} else {
				unlink($file);
			}
		}
		return rmdir($dirPath);
   }
   /*
   * Delete Files
   */
   public function deleteFile($filePath) {
		return unlink($filePath);
    }
   /*
   * Download Theme
   */
	public function download_theme( $theme_name ) {
    if ( current_user_can( 'edit_themes' ) ) {
      $slash = '/';
      if ( WPWINDOWS ) {
        $slash = '\\';
      }
      $position = strpos( $theme_name, $slash );
      $theme_name = substr( $theme_name, 0, $position );
      $theme = wp_get_theme( $theme_name );
      
      if ( $theme->exists() ) {
         $directory = $theme->get_stylesheet_directory(). $slash;
         $filename = $theme_name . '.zip';
        $zip = self::compress( $directory, $filename );
        if ( $zip ) {
          header( 'Content-Disposition: attachment; filename="' . $theme_name . '.zip' . '"');
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
          wp_redirect( admin_url( 'themes.php?page=theme_editor_theme&error=3' ) );
          exit;
        }
      }
      else {
        wp_redirect( admin_url( 'themes.php?page=theme_editor_theme&error=2' ) );
        exit;
      }
    }
    else {
      wp_redirect( admin_url( 'themes.php?page=theme_editor_theme&error=1' ) );
      exit;
    }
  }
   /*
   * compress
  */ 
   public static function compress( $directory, $filename ) {
    $zip = new \ZipArchive;
    if ( ! $zip->open( $filename, ZIPARCHIVE::CREATE ) ) {
    }
    self::add_files_to_zip( $directory, $zip );
    return $zip->close();
  }
  /*
   * Zip
  */ 
  public static function add_files_to_zip( $directory, $zip, $zipdir='' ) {
    if ( is_dir( $directory ) ) {
      if ( $dh = opendir( $directory ) ) {
        while ( ( $file = readdir( $dh ) ) !== false ) {
          if (!is_file( $directory . $file ) ) {
            if ( ( $file !== ".") && ( $file !== "..") ) {
              self::add_files_to_zip( $directory . $file . "/", $zip, $zipdir . $file . "/");
            }
          }
          else {
            $zip->addFile( $directory . $file, $zipdir . $file );
          }
        }
      }
    }
  }
  /*
   * Load css
  */ 
  public function load_css() {
	    wp_enqueue_style( 'te_theme_editor', MK_THEME_EDITOR_URL.'app/view/css/theme_editor.css' ); 
		echo '<link rel="stylesheet" href="'.MK_THEME_EDITOR_URL.'app/view/lib/codemirror.css">
			  <link rel="stylesheet" href="'.MK_THEME_EDITOR_URL.'app/view/theme/'.$this->defcmt.'.css"> 
			  <link rel="stylesheet" href="'.MK_THEME_EDITOR_URL.'app/view/addon/dialog/dialog.css">
			  <link rel="stylesheet" href="'.MK_THEME_EDITOR_URL.'app/view/addon/search/matchesonscrollbar.css">
			  <link rel="stylesheet" href="'.MK_THEME_EDITOR_URL.'app/view/css/settings_tabs.css">' ;
  }
  /*
   * Load JS
  */ 
  public function load_js() {
	   echo '<script src="'.MK_THEME_EDITOR_URL.'app/view/lib/codemirror.js"></script>
		<script src="'.MK_THEME_EDITOR_URL.'app/view/addon/selection/active-line.js"></script>
		<script src="'.MK_THEME_EDITOR_URL.'app/view/addon/edit/matchbrackets.js"></script>  
		<script src="'.MK_THEME_EDITOR_URL.'app/view/addon/dialog/dialog.js"></script>
		<script src="'.MK_THEME_EDITOR_URL.'app/view/addon/search/searchcursor.js"></script>
		<script src="'.MK_THEME_EDITOR_URL.'app/view/addon/search/search.js"></script>
		<script src="'.MK_THEME_EDITOR_URL.'app/view/addon/scroll/annotatescrollbar.js"></script>
		<script src="'.MK_THEME_EDITOR_URL.'app/view/addon/search/matchesonscrollbar.js"></script>
		<script src="'.MK_THEME_EDITOR_URL.'app/view/addon/search/jump-to-line.js"></script>
		<script src="'.MK_THEME_EDITOR_URL.'app/view/mode/css/css.js"></script>
		<script src="'.MK_THEME_EDITOR_URL.'app/view/mode/javascript/javascript.js"></script>
		<script src="'.MK_THEME_EDITOR_URL.'app/view/js/theme_editor.js"></script>';
  }
     /*
	 * Get all code mirror Themes
	 */  
	 public function getcmthemes()
	 {
	    $dir = MK_THEME_EDITOR_PATH.'app/view/theme/';
		$theme_files = glob($dir."/*.css");
		$cethemes = array();
		foreach($theme_files as $theme_file){
			$cethemes[basename($theme_file,".css")]=basename($theme_file,".css");
		}
		return $cethemes;
	 }
	 /* 
	 * Save Settings
	 */ 
	 public function __save($fields) {
		$mk_te_settings_options = array();
		$needToUnset = array('submit_mk_te_settings');
		foreach($needToUnset as $noneed):
			unset($fields[$noneed]);
		endforeach;
		foreach($fields as $key => $val):
				$mk_te_settings_options[$key] = $val;
				endforeach;
				$saveSettings = update_option('mk_te_settings_options', $mk_te_settings_options );
				if($saveSettings){
					return '1';
				}
				else {
					return '2';
				}
	 }
	
}