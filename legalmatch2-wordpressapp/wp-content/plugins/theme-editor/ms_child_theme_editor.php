<?Php
//Defines Constant Here For Child Theme Module
defined( 'MS_THEME_EDITOR_DIR' ) or define( 'MS_THEME_EDITOR_DIR', dirname( __FILE__ ) );
defined( 'MS_THEME_EDITOR_URL' ) or define( 'MS_THEME_EDITOR_URL', plugin_dir_url( __FILE__ ) );
defined( 'MS_CHILD_THEME_EDITOR' ) or define( 'MS_CHILD_THEME_EDITOR', 'ms_child_theme_editor' );
defined( 'LF' ) or define( 'LF', "\n" );

if(!function_exists('ms_child_theme_permission'))
{
	function ms_child_theme_permission()
	{
		$child_theme_permission = array(
			'ms_user_create_new_child',
			'ms_user_configure_child_theme',
			'ms_user_duplicate_child_theme',
			'ms_user_query_selector',
			'ms_user_web_font',
			'ms_user_file_parent_to_child',
			'ms_user_deleted_file',
			'ms_user_upload_new_screenshoot',
			'ms_user_upload_new_images',
			'ms_user_deleted_image',
			'ms_user_download_image',
			'ms_user_create_new_directory',
			'ms_user_create_new_files',
			'ms_user_export_theme'						
		);
		return 	$child_theme_permission;
	}
}
    
// activate autoloader
spl_autoload_register( 'ms_theme_editor_autoload' );

function ms_theme_editor_autoload( $class ) {	
	$file = dirname( __FILE__ ) . '/includes/classes/' . $class . '.php';
    if ( file_exists( $file ) )
        include_once( $file );
}     

if ( is_admin()){
  add_action( 'plugins_loaded', 'ms_theme_editor_controller::init', 5 );
}

if ( isset( $_GET['ms_theme_editor_preview'] ) ){
	
    remove_action( 'setup_theme', 'preview_theme' );
    add_action( 'setup_theme', 'ms_switch_theme'  );
	add_filter( 'wp_redirect_status', 'wp_redirect_status',1000);
	
	// remove inbulit hook for update theme and plugins and wp_cron
	remove_action( 'init', 'wp_cron' );
	remove_action( 'admin_init', '_maybe_update_core' );
	remove_action( 'admin_init', '_maybe_update_plugins' );
	remove_action( 'admin_init', '_maybe_update_themes' );
}

if(!function_exists('ms_switch_theme')){
	function ms_switch_theme(){
		if ( empty( $_GET['ms_theme_editor_preview'] ) || !current_user_can( 'switch_themes' ) )
		return;
		$exit_stylesheet = get_stylesheet();
		$theme = wp_get_theme( isset( $_GET[ 'stylesheet' ] ) ? $_GET[ 'stylesheet' ] : '' );
		//switch_theme($_GET['template']);
		$sstylesheet  = sanitize_text_field($_GET['stylesheet']);
		if($sstylesheet  != $exit_stylesheet)
		{
			add_filter( 'template', 'ms_get_template'  );
			add_filter( 'stylesheet', 'ms_get_stylesheet' );
			add_filter( 'pre_option_stylesheet', 'ms_get_stylesheet'  );
			add_filter( 'pre_option_template', 'ms_get_template' );
			add_filter( 'pre_option_theme_mods_' . $sstylesheet, 'ms_preview_mods');
		}	
		add_action( 'wp_print_styles', 'ms_style_css', 999999 );
		add_action( 'wp_footer', 'ms_convert_stylesheet_parse'  );
		send_origin_headers();
		show_admin_bar( false );//hide admin bar
		//switch_theme($original_stylesheet);
	}
}
if(!function_exists('ms_get_template'))
{
	function ms_get_template(){
		return $sstylesheet  = $_GET['template'];
	}
}
if(!function_exists('ms_get_stylesheet'))
{
	function ms_get_stylesheet()
	{
		return $sstylesheet  = $_GET['stylesheet'];
	}
}

if(!function_exists('ms_preview_mods'))
{
	function ms_preview_mods() { 
		$exit_stylesheet = get_stylesheet();
		$sstylesheet  = sanitize_text_field($_GET['stylesheet']);
		
		if($exit_stylesheet == $sstylesheet)
		{
			return false;
		}
		return get_option( 'theme_mods_' .ms_get_stylesheet());
	}
}

if(!function_exists('ms_style_css'))
{
	function ms_style_css(){
		wp_enqueue_style( 'ctc-test', get_stylesheet_directory_uri() . '/ctc-test.css' );
	}
}

if(!function_exists('ms_convert_stylesheet_parse'))
{
function ms_convert_stylesheet_parse() {	
	
	echo '<script>/*<![CDATA[' . LF;
	global $wp_styles, $wp_filter;
	$queue = implode( "\n", array_keys( $wp_styles->registered ) );
	echo 'BEGIN WP QUEUE' . LF . $queue . LF . 'END WP QUEUE' . LF;
	if ( is_child_theme() ):
		
		$file = get_stylesheet_directory() . '/style.css';
		if ( file_exists( $file ) && ( $styles = @file_get_contents( $file ) ) ):
			
			if ( defined( 'CHLD_THM_CFG_IGNORE_PARENT' ) ):
				echo 'CHLD_THM_CFG_IGNORE_PARENT' . LF;
			endif;
			
			if ( preg_match( '#\nUpdated: \d\d\d\d\-\d\d\-\d\d \d\d:\d\d:\d\d\n#s', $styles ) ):
				echo 'IS_CTC_THEME' . LF;
			endif;
			
			if ( preg_match( '#\@import\s+url\(.+?\/' . preg_quote( get_template() ) . '\/style\.css.*?\);#s', $styles ) ):
				echo 'HAS_CTC_IMPORT' . LF;
			endif;
		endif;
	else:
		
		$file = get_template_directory() . '/style.css';
		if ( file_exists( $file ) && ( $styles = @file_get_contents( $file ) ) ):
			$styles = preg_replace( '#\/\*.*?\*\/#s', '', $styles );
			if ( preg_match_all( '#\@import\s+(url\()?(.+?)(\))?;#s', $styles, $imports ) ):
				echo 'BEGIN IMPORT STYLESHEETS' . LF;
				foreach ( $imports[ 2 ] as $import )
					echo trim( str_replace( array( "'", '"' ), '', $import ) ) . LF;
				echo 'END IMPORT STYLESHEETS' . LF;
				
			elseif ( !preg_match( '#\s*([\[\.\#\:\w][\w\-\s\(\)\[\]\'\^\*\.\#\+:,"=>]+?)\s*\{(.*?)\}#s', $styles ) ):
				echo 'NO_CTC_STYLES' . LF;
			endif;
		endif;
	endif;
   
	echo 'BEGIN CTC IRREGULAR' . LF;
	
	foreach ( $wp_filter[ 'wp_enqueue_scripts' ] as $priority => $arr ):
	   
		if ( $priority != 10 ):
			
			foreach ( $arr as $funcarr ):
				
				$wp_styles->queue = array();
				
				if ( !is_null($funcarr['function']) )
					call_user_func_array( $funcarr[ 'function' ], array( 0 ) );
			endforeach;
		   
			if ( !empty( $wp_styles->queue ) )
				echo $priority . ',' . implode( ",", $wp_styles->queue ) . LF;
		endif;
	endforeach;
	echo 'END CTC IRREGULAR' . LF;
	if ( defined( 'WP_CACHE' ) && WP_CACHE )
		echo 'HAS_WP_CACHE' . LF;
	if ( defined( 'AUTOPTIMIZE_PLUGIN_DIR' ) )
		echo 'HAS_AUTOPTIMIZE' . LF;
	if ( defined( 'WP_ROCKET_VERSION' ) )
		echo 'HAS_WP_ROCKET' . LF;
	echo ']]>*/</script>' . LF;
}
}
if(!function_exists('wp_redirect_status'))
{
	function wp_redirect_status()
	{
		$status =200;
		return $status;
	}
}
if(!function_exists('ms_get_theme_name_count'))
{
	function ms_get_theme_name_count()
	{
		$parent_child_count = array();
		$themes  = wp_get_themes();
		foreach ($themes as $theme_basedir_name => $theme_obj) {
			
			$theme_name = $theme_obj->Name;  
			$theme_dir =  $theme_basedir_name;
			$parent_theme = $theme_obj->get('Template'); //getting template
		
			if(!empty($parent_theme))
			{
				if(array_key_exists($parent_theme,$parent_child_count))
				{
				  $parent_child_count[$parent_theme]=$parent_child_count[$parent_theme]+1;
				}
				else
				{
				  $parent_child_count[$parent_theme] = 1;
				}
			}
		}
		return  $parent_child_count;
	}
}

if(!function_exists('ms_get_theme_name'))
{
	function ms_get_theme_name($ms_theme_type)
	{
		$child_theme_array = array();
		$parent_theme_array = array();
		
		$themes  = wp_get_themes();
		foreach ($themes as $theme_basedir_name => $theme_obj) {
			
			$theme_name = $theme_obj->Name;  
			$theme_dir =  $theme_basedir_name;
			$parent_theme = $theme_obj->get('Template'); //getting template
		
			if(!empty($parent_theme))
			{
				$child_theme_array[$theme_dir] = $theme_name;
			}
			else
			{
				$parent_theme_array[$theme_dir] = $theme_name;
			}
		}
		if($ms_theme_type == 'parent_theme')
		{
			return  $parent_theme_array;
		}
		else
		{
			return  $child_theme_array;
		}
	}
}

/*
Filters an enqueued style’s fully-qualified URL.
*/

add_filter( 'style_loader_src', 'ms_theme_editor_src', 10, 2 );
if(!function_exists('ms_theme_editor_src'))
{
	function ms_theme_editor_src( $src, $handle ) {
		if ( strstr( $src, get_stylesheet() ) ):
			$src = preg_replace( "/ver=(.*?)(\&|$)/", 'ver=' . wp_get_theme()->Version . "$2", $src );
		endif;
		return $src;
	}
}

add_action('wp_ajax_mk_theme_editor_file_move', 'mk_theme_editor_file_move');
if(!function_exists('mk_theme_editor_file_move'))
{
	function mk_theme_editor_file_move()
	{
		if(wp_verify_nonce($_REQUEST['_wpnonce'],'ms_theme_editor') && current_user_can('manage_options'))
		{
			$ctd = sanitize_text_field($_REQUEST['ctd']);//child directory
			$ctpd = sanitize_text_field($_REQUEST['ctpd']);//parent directory
			$file_select = $_REQUEST['file_selected'];
			$child_theme_path = get_theme_root().'/'.$ctd;
			$parent_theme_path = get_theme_root().'/'.$ctpd;			
			if(is_dir($child_theme_path)&& is_dir($parent_theme_path))
			{
				foreach($file_select as $value)
				{
					$move_file = str_replace("/\\",'/',urldecode(htmlspecialchars_decode($value)));
					$full_child_theme_path = $child_theme_path;
					$reminder_part =  str_replace($parent_theme_path,'',$move_file );
					$ms_directory_part = explode('/',$reminder_part);
					if(count($ms_directory_part) !=1)
					{
						for($flag=0;$flag<count($ms_directory_part)-1;$flag++)
						{
							if($ms_directory_part[$flag] !='')
							{
								$full_child_theme_path = rtrim($full_child_theme_path).'/'.$ms_directory_part[$flag];
								
								if (!file_exists($full_child_theme_path)) 
								{
									$permission = '0755';
									$createFolder = mkdir($full_child_theme_path);
								}
							}
						}
						$full_child_theme_path = $full_child_theme_path.'/'.basename($move_file);
					}
					else{
						$full_child_theme_path = $child_theme_path.'/'.basename($move_file);
					}
					
					if(!file_exists($full_child_theme_path))
					{
						$verfied_file = copy($move_file,$full_child_theme_path);
						if($verfied_file){
							?>
							<label class="ms-checkboxFiles">
							<input class="ms_checkbox" name="ms_file_child[]" value="<?php echo $full_child_theme_path;?>" type="checkbox">
							  <?php //echo basename($move_file);
							  echo ltrim($reminder_part,'/');
							  ?>
							</label>
							<?php
						}
					}
				}
			}
		}
		else
		{
			echo 'demo';
		}
		die();
	}
}

add_action('wp_ajax_mk_theme_editor_child_file_delete', 'mk_theme_editor_child_file_delete');
if(!function_exists('mk_theme_editor_child_file_delete'))
{
	function mk_theme_editor_child_file_delete()
	{
		if(wp_verify_nonce($_REQUEST['_wpnonce'],'ms_theme_editor') && current_user_can('manage_options'))
		{
			$ctd = sanitize_text_field($_REQUEST['ctd']);//child directory
			$ctpd = sanitize_text_field($_REQUEST['ctpd']);//parent directory
			$file_select = $_REQUEST['file_selected'];
			
			$child_theme_path = get_theme_root().'/'.$ctd;
			$parent_theme_path = get_theme_root().'/'.$ctpd;
			if(is_dir($child_theme_path)&& is_dir($parent_theme_path))
			{
				foreach($file_select as $value)
				{
					$child_file_dir = str_replace("/\\",'/',urldecode(htmlspecialchars_decode($value)));
					unlink($child_file_dir);
				}
			}
		}
	die();
	}
}

add_action('wp_ajax_webphoto_upload', 'webphoto_upload');
if(!function_exists('webphoto_upload'))
{
	function webphoto_upload(){
		if(wp_verify_nonce($_REQUEST['_wpnonce'],'ms_theme_editor') && current_user_can('manage_options'))
		{
			global $wpdb;
			//print_r($_FILES["webphotos"]);	
			$ctd = sanitize_text_field($_REQUEST['ctd']);//child directory
			$ctpd = sanitize_text_field($_REQUEST['ctpd']);//parent directory
				
			$theme_path = get_theme_root().'/';//theme root dir path
			$permission = '0755';
			$fullPath = $theme_path.$ctd.'/images';
			require_once(ABSPATH . "wp-admin" . '/includes/image.php');
			require_once(ABSPATH . "wp-admin" . '/includes/file.php');
			require_once(ABSPATH . "wp-admin" . '/includes/media.php');
			if (!file_exists($fullPath)) 
			{
				$createFolder = mkdir($fullPath);
				if($createFolder) {
					$go_head = true;
				}
				else
				{
					$go_head = false;
				}
			}
			else{
				$go_head = true;
			}
			
			if($go_head){
			 $uploads_dir = $fullPath;
			 $file    = $_FILES['webphotos']['name'];
			 $source      = $_FILES['webphotos']['tmp_name'];
			 $newfilename = $file;
			 $destination = trailingslashit( $uploads_dir ) . $newfilename;
			 move_uploaded_file( $source, $destination );
			}
			die();
		}
	}
}

add_action('wp_ajax_screenshot_upload', 'screenshot_upload');
if(!function_exists('screenshot_upload')){
	function screenshot_upload(){
		
		if(wp_verify_nonce($_REQUEST['_wpnonce'],'ms_theme_editor') && current_user_can('manage_options'))
		{
			global $wpdb;	
			$ctd = sanitize_text_field($_REQUEST['ctd']);//child directory
			$ctpd = sanitize_text_field($_REQUEST['ctpd']);//parent directory
				
			$theme_path = get_theme_root().'/';//theme root dir path
			$permission = '0755';
			$fullPath = $theme_path.$ctd;
			require_once(ABSPATH . "wp-admin" . '/includes/image.php');
			require_once(ABSPATH . "wp-admin" . '/includes/file.php');
			require_once(ABSPATH . "wp-admin" . '/includes/media.php');
			if (!file_exists($fullPath)){
				$createFolder = mkdir($fullPath);
				if($createFolder) {
					$go_head = true;
				}
				else{
					$go_head = false;
				}
			}
			else{
				$go_head = true;
			}
			$image = array('jpg','jpeg','png','gif');
			foreach($image as $img_key => $img_value){
				$full_child_dir = get_theme_root().'/'.$ctd."/screenshot.".$img_value;
				$extension = pathinfo($full_child_dir, PATHINFO_EXTENSION);
				$child_image_url = get_theme_root_uri().'/'.$ctd.'/screenshot.'.$img_value;
				if (file_exists($full_child_dir)){
					unlink($full_child_dir);
				}
			}

			$uploads_dir = $fullPath;
			$file    = $_FILES['ms_theme_screenshot']['name'];
			$extension = pathinfo($file, PATHINFO_EXTENSION);
			$source      = $_FILES['ms_theme_screenshot']['tmp_name'];
			$newfilename = $file;
			$extension ='jpg';
			$file = 'screenshot.'.$extension;
			$destination = trailingslashit( $uploads_dir ).$file;
			$ms_move = move_uploaded_file( $source, $destination );
			if($ms_move){
				echo $extension;
			}
			else{
				echo 0;
			}
			
		}
		else{
			
			echo 12;
		}
		die();
	}
}

add_action('wp_ajax_mk_theme_editor_delete_images', 'mk_theme_editor_delete_images');
if(!function_exists('mk_theme_editor_delete_images')){
	function mk_theme_editor_delete_images()
	{
		if(wp_verify_nonce($_REQUEST['_wpnonce'],'ms_theme_editor') && current_user_can('manage_options'))
		{
			global $wpdb;	
			$ctd = sanitize_text_field($_REQUEST['ctd']);//child directory
			$ctpd = sanitize_text_field($_REQUEST['ctpd']);//parent directory
			$images_array= $_REQUEST['images_array'];
			//print_r($images_array);

			foreach($images_array as $dkey => $dvalue){
				$dvalue= str_replace("/\\",'/',$dvalue);
				$success = unlink($dvalue);
				if($success){
					echo 'deleted';
				}
				else{
					echo 'notdeleted';
				}
			}
		}
		die();
	}
}

add_action('wp_ajax_ms_new_directory', 'ms_new_directory');
if(!function_exists('ms_new_directory')){
	function ms_new_directory(){
		if(wp_verify_nonce($_REQUEST['_wpnonce'],'ms_theme_editor') && current_user_can('manage_options'))
		{
			global $wpdb;	
			$ctd = sanitize_text_field($_REQUEST['ctd']);//child directory
			$ctpd = sanitize_text_field($_REQUEST['ctpd']);//parent directory		
			$ms_new_directory = sanitize_text_field($_REQUEST['ms_new_directory']);
			$permission = '0755';
			$theme_path = get_theme_root().'/';
			$fullPath = $theme_path.$ctd.'/'.$ms_new_directory;
			if (!file_exists($fullPath)){
				$createFolder = mkdir($fullPath);
				if($createFolder) {
					echo ' Created';
				}
				else{
					echo 'Not Created';
				}
			}
			else{
				echo 'Already Exists';
			}
		}
		die();
	}
}

add_action('wp_ajax_ms_new_file', 'ms_new_file');
if(!function_exists('ms_new_file')){
function ms_new_file()
{
	if(wp_verify_nonce($_REQUEST['_wpnonce'],'ms_theme_editor') && current_user_can('manage_options'))
	{
		global $wpdb;	
		$ctd = sanitize_text_field($_REQUEST['ctd']);//child directory
		$ctpd = sanitize_text_field($_REQUEST['ctpd']);//parent directory
		
		//Parmeter
		$ms_new_file=sanitize_text_field($_REQUEST['ms_new_file']);
		$ms_file_type=sanitize_text_field($_REQUEST['ms_file_type']);
		$ms_php_type=sanitize_text_field($_REQUEST['ms_php_type']);
		$ms_template=sanitize_text_field($_REQUEST['ms_template']);
		
		//permission
		$permission = '0755';
		$theme_path = get_theme_root().'/';
		$fullPath = $theme_path.$ctd.'/'.$ms_new_file.$ms_file_type;
		if (!file_exists($fullPath)) 
		{
			$createFile = fopen($fullPath, "w");
			
			if($ms_file_type == '.php')
			{
				if($ms_template  != '' && $ms_php_type !='simple')
				{
$template_contents ="<?php
/*
Template Name: $ms_template
*/
?>";
					$createFile = fopen($fullPath, "w"); 
					$twrite = fwrite($createFile,$template_contents);
					if($twrite)
					{
						echo 'Wordpress Template File Created';
					}
					else
					{
						echo 'Wordpress Template Not Created';
					}
				}
				else
				{
					if($createFile) {
						echo 'PHP Created File';
					}
					else
					{
						echo 'Not Created';
					}
				}
			}
			else 
			{
				if($createFile) {
					echo 'Created';
				}
				else
				{
					echo 'Not Created';
				}
			}
		}
		else
		{
			echo 'Already Exists';
		}
	}
	die();
}
}
?>