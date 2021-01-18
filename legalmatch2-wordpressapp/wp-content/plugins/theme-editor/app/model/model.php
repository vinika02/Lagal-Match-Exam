<?php namespace te\app\mdl;
use te\app\thm_cnt\theme_editor_theme_controller as run_theme_editor_theme_controller;
class theme_editor_model {
	var $theme_controller;
	
	protected $SERVER = 'http://ikon.digital/plugindata/api.php';

	public function __construct() {
		register_activation_hook(MK_THEME_EDITOR_FILE, array(&$this, 'mk_te_settings'));
		$model_ajax_actions = array(
		                       'wp_ajax_save_mk_theme_editor_theme_files' => 'mk_theme_editor_theme_files',
							   'wp_ajax_mk_theme_editor_folder_open' => 'mk_theme_editor_folder_open',
							   'wp_ajax_mk_plugin_editor_folder_open' => 'mk_plugin_editor_folder_open',
							   'wp_ajax_mk_theme_editor_file_open' => 'mk_theme_editor_file_open',
							   'wp_ajax_mk_theme_editor_file_download' => 'mk_theme_editor_file_download',
							   'wp_ajax_mk_theme_editor_folder_create' => 'mk_theme_editor_folder_create',
							   'wp_ajax_mk_theme_editor_file_create' => 'mk_theme_editor_file_create',
							   'wp_ajax_mk_theme_editor_folder_remove' => 'mk_theme_editor_folder_remove',
							   'wp_ajax_mk_theme_editor_file_remove' => 'mk_theme_editor_file_remove',
							   'wp_ajax_mk_theme_editor_file_upload' => 'mk_theme_editor_file_upload',
							   'wp_ajax_mk_te_close_te_help' => 'mk_te_close_te_help',
		                      );
		foreach($model_ajax_actions as $accepter => $callbacker) {					  
	       add_action($accepter, array($this, $callbacker));
		}
			 /*
			 Lokhal Verify Email 
			 */
			 add_action( 'wp_ajax_mk_theme_editor_verify_email', array(&$this, 'mk_theme_editor_verify_email_callback'));
			 add_action( 'wp_ajax_verify_theme_editor_email', array(&$this, 'verify_theme_editor_email_callback') );

		$this->theme_controller = new run_theme_editor_theme_controller;
	 }
	/* Verify Email*/
		public function mk_theme_editor_verify_email_callback() {
			$current_user = wp_get_current_user();
			$nonce = $_REQUEST['vle_nonce'];
            if ( wp_verify_nonce( $nonce, 'verify-theme-editor-email' ) ) {			
				$action = sanitize_text_field($_POST['todo']);
				$lokhal_email = sanitize_text_field($_POST['lokhal_email']);
				$lokhal_fname = sanitize_text_field($_POST['lokhal_fname']);
				$lokhal_lname = sanitize_text_field($_POST['lokhal_lname']);
				// case - 1 - close
				if($action == 'cancel') {
				   set_transient( 'theme_editor_cancel_lk_popup_'.$current_user->ID, 'theme_editor_cancel_lk_popup_'.$current_user->ID, 60 * 60 * 24 * 30 );			
			 	   update_option( 'theme_editor_email_verified_'.$current_user->ID, 'yes' );
				} else if($action == 'verify') {
				  $engagement = '75';	
				  update_option( 'theme_editor_email_address_'.$current_user->ID, $lokhal_email );
				  update_option( 'verify_theme_editor_fname_'.$current_user->ID, $lokhal_fname );
				  update_option( 'verify_theme_editor_lname_'.$current_user->ID, $lokhal_lname );
				  update_option( 'theme_editor_email_verified_'.$current_user->ID, 'yes' );
				  // Send Email Code
				  $subject = "Email Verification";				  
				  $message = "
					<html>
					<head>
					<title>Email Verification</title>
					</head>
					<body>
					<p>Thanks for signing up! Just click the link below to verify your email and we’ll keep you up-to-date with the latest and greatest brewing in our dev labs!</p>	
					<p><a href='".admin_url('admin-ajax.php?action=verify_theme_editor_email&token='.md5($lokhal_email))."'>Click Here to Verify
</a></p>				
					</body>
					</html>
					";				
				  // Always set content-type when sending HTML email
				  $headers = "MIME-Version: 1.0" . "\r\n";
				  $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
				  $headers .= "From: noreply@ikon.digital" . "\r\n";
                  $mail = mail($lokhal_email,$subject,$message,$headers);
				  $data = $this->verify_on_server($lokhal_email, $lokhal_fname,  $lokhal_lname, $engagement, 'verify','0');
				  if($mail) {
				  echo '1';
				  } else {
				  echo '2';  
				  }
				  	
				}
			}
			else {
				echo 'Nonce';
			}
			die;
		}		
		/*
		* Verify Email
		*/
		public function verify_theme_editor_email_callback() {
			$email = sanitize_text_field($_GET['token']);
			$current_user = wp_get_current_user();
			$lokhal_email_address = md5(get_option('theme_editor_email_address_'.$current_user->ID));
			if($email == $lokhal_email_address) {
			   $this->verify_on_server(get_option('theme_editor_email_address_'.$current_user->ID), get_option('verify_theme_editor_fname_'.$current_user->ID), get_option('verify_theme_editor_lname_'.$current_user->ID), '100', 'verified','1');
			   update_option( 'theme_editor_email_verified_'.$current_user->ID, 'yes' );	
			   echo '<p>Email Verified Successfully. Redirecting please wait.</p>';
			   echo '<script>';
			   echo 'setTimeout(function(){window.location.href="https://filemanager.webdesi9.com?utm_redirect=wp" }, 2000);';
			   echo '</script>';
			   
			}
			die;
		}
	    /*
		Send Data To Server
		*/
		public function verify_on_server($email, $fname, $lname, $engagement, $todo, $verified) {
			global $wpdb, $wp_version;
	
		      $id = get_option( 'page_on_front' );
			    $info = array(
				         'email' => $email,
						 'first_name' => $fname,
						 'last_name' => $lname,
						 'engagement' => $engagement,
						 'SITE_URL' => site_url(),
				         'PHP_version' => phpversion(),
						 'upload_max_filesize' => ini_get('upload_max_filesize'),
						 'post_max_size' => ini_get('post_max_size'),
						 'memory_limit' => ini_get('memory_limit'),
						 'max_execution_time' => ini_get('max_execution_time'),
						 'HTTP_USER_AGENT' => $_SERVER['HTTP_USER_AGENT'],
						 'wp_version' => $wp_version,						 
						 'plugin' => 'Theme Editor',					 
						 'nonce' => 'um235gt9duqwghndewi87s34dhg',
						 'todo' => $todo,
						 'verified' => $verified
						 
				);
				$str = http_build_query($info);
				$curl = curl_init();
				curl_setopt($curl, CURLOPT_URL, $this->SERVER);
				curl_setopt($curl, CURLOPT_POST, 1);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // save to returning 1
				curl_setopt($curl, CURLOPT_POSTFIELDS, $str);
				$result = curl_exec ($curl); 
				$data = json_decode($result,true);
				return $data;
		}
	public function mk_te_settings() {
		            $defaultsettings = array(
											 'e_d_t_e' => 'yes',
											 'code_editor_theme' => 'cobalt',
											 'e_w_d_t_e' => 'yes',
											 'e_d_p_e' => 'yes',
											 'e_w_d_p_e' => 'yes',
											 );
					$opt = get_option('mk_te_settings_options');
					if(!$opt['e_w_d_p_e']) {
						update_option('mk_te_settings_options', $defaultsettings);
					}    
	}
	//theme file save
	public function mk_theme_editor_theme_files() {
		
		if(wp_verify_nonce($_POST['wpnonce'],'ms_theme_editor') && current_user_can('manage_options'))
		{
			$real_file = $_POST['path'];
			if ( isset( $_POST['theme_content'] ) && file_exists( $real_file ) && is_writable( $real_file ) ) {
				  $new_content = stripslashes( $_POST['theme_content'] );
				if ( file_get_contents( $real_file ) === $new_content ) {
					$response = json_encode(array('status' => '2', 'msg' => 'No change in file!'));	
				}
				else {
					$f = fopen( $real_file, 'w+' );
					$save = fwrite( $f, $new_content );
					fclose( $f );
					if($save) {
					 $response = json_encode(array('status' => '1', 'msg' => 'File Saved Successfully!'));	
					} else {
					 $response = json_encode(array('status' => '2', 'msg' => 'File Not Saved!'));		
					}
				}
			} else {
				 $response = json_encode(array('status' => '2', 'msg' => 'File not exists!'));
			}
		}
		else{
			$response = json_encode(array('status' => '2', 'msg' => 'Unable to verify nonce!'));
		}
		echo $response;
		die;
	}
	public function mk_theme_editor_folder_open() {
		
		if(wp_verify_nonce($_POST['_wpnonce'],'ms_theme_editor') && current_user_can('manage_options'))
		{
			$folder_path = str_replace('\\\\','\\',$_POST['path']);
			$child_files = $this->theme_controller->get_files_and_folders( $folder_path, '0', 'theme' );
			$return = '';
			if(!empty($child_files[-1])) {
			  $return .= $child_files[-1];	
			} else {
			$return .= '<ul class="subfolders">';	
			foreach($child_files as $child_file) {
					  $logoImagePath = MK_THEME_EDITOR_PATH.'app/view/images/'.$child_file['extension'].'.png';
					  $logoImage = MK_THEME_EDITOR_URL.'app/view/images/'.$child_file['extension'].'.png';
					  if(!file_exists($logoImagePath)) {
						$logoImage = MK_THEME_EDITOR_URL.'app/view/images/def.png';  
					  }
				 //folder	  
				 if($child_file['filetype'] == 'folder') {
					$return .= '<li class="'.$child_file['extension'].'">';
					$return .= '<a href="javascript:void(0)" class="open_folder" data-path="'.$child_file['path'].'" data-name="'.$child_file['extension'].$child_file['name'].'"><img src="'.MK_THEME_EDITOR_URL.'app/view/images/'.$child_file['extension'].'.png">';
					$return .= $child_file['name'];
					$return .= '</a> <span class="'.$child_file['extension'].$child_file['name'].'"></span>'; 
					$return .= '</li>';
				  } 
				  //img
				  else if(in_array($child_file['extension'], $this->theme_controller->image_type_posibilities)) {
					$return .= '<li class="'.$child_file['extension'].' small_icons">';
					$return .= '<a href="'.$child_file['url'].'" class="open_image thickbox" target="_blank"><img src="'.$child_file['url'].'"> ';
					$return .= $child_file['name'];
					$return .= '</a>'; 
					$return .= '</li>';    
				  }	
				  // dwn
				  else if(in_array($child_file['extension'], $this->theme_controller->download_type_possibilities))	 {
					$return .= '<li class="'.$child_file['extension'].' small_icons">';
					$return .= '<a href="'.$child_file['url'].'" class="dwn_file" target="_blank" download><img src="'.$logoImage.'"> ';
					$return .= $child_file['name'];
					$return .= '</a>'; 
					$return .= '</li>';   
				  } else {
					$return .= '<li class="'.$child_file['extension'].' small_icons">';
					$return .= '<a href="javascript:void(0)" class="open_file" data-path="'.$child_file['path'].'" data-name="'.$child_file['extension'].$child_file['name'].'" data-file="'.$child_file['file'].'" data-downloadfile="'.$child_file['url'].'"><img src="'.$logoImage.'"> ';
					$return .= $child_file['name'];
					$return .= '</a>'; 
					$return .= '</li>'; 
				 }
			}
			$return .= '</ul>';
			}
			echo $return;
		}		
		die;
	}
	
	public function mk_plugin_editor_folder_open() {
			
		if(wp_verify_nonce($_POST['_wpnonce'],'ms_theme_editor') && current_user_can('manage_options'))
		{
			$folder_path = str_replace('\\\\','\\',$_POST['path']);
			$child_files = $this->theme_controller->get_files_and_folders( $folder_path, '0', 'plugin' );
			$return = '';
			if(!empty($child_files[-1])) {
			  $return .= $child_files[-1];	
			} else {
			$return .= '<ul class="subfolders">';	
			foreach($child_files as $child_file) {
					  $logoImagePath = MK_THEME_EDITOR_PATH.'app/view/images/'.$child_file['extension'].'.png';
					  $logoImage = MK_THEME_EDITOR_URL.'app/view/images/'.$child_file['extension'].'.png';
					  if(!file_exists($logoImagePath)) {
						$logoImage = MK_THEME_EDITOR_URL.'app/view/images/def.png';  
					  }
				 //folder	  
				 if($child_file['filetype'] == 'folder') {
					$return .= '<li class="'.$child_file['extension'].'">';
					$return .= '<a href="javascript:void(0)" class="open_folder" data-path="'.$child_file['path'].'" data-name="'.$child_file['extension'].$child_file['name'].'"><img src="'.MK_THEME_EDITOR_URL.'app/view/images/'.$child_file['extension'].'.png">';
					$return .= $child_file['name'];
					$return .= '</a> <span class="'.$child_file['extension'].$child_file['name'].'"></span>'; 
					$return .= '</li>';
				  } 
				  //img
				  else if(in_array($child_file['extension'], $this->theme_controller->image_type_posibilities)) {
					$return .= '<li class="'.$child_file['extension'].' small_icons">';
					$return .= '<a href="'.$child_file['url'].'" class="open_image thickbox" target="_blank"><img src="'.$child_file['url'].'"> ';
					$return .= $child_file['name'];
					$return .= '</a>'; 
					$return .= '</li>';    
				  }	
				  // dwn
				  else if(in_array($child_file['extension'], $this->theme_controller->download_type_possibilities))	 {
					$return .= '<li class="'.$child_file['extension'].' small_icons">';
					$return .= '<a href="'.$child_file['url'].'" class="dwn_file" target="_blank" download><img src="'.$logoImage.'"> ';
					$return .= $child_file['name'];
					$return .= '</a>'; 
					$return .= '</li>';   
				  } else {
					$return .= '<li class="'.$child_file['extension'].' small_icons">';
					$return .= '<a href="javascript:void(0)" class="open_file" data-path="'.$child_file['path'].'" data-name="'.$child_file['extension'].$child_file['name'].'" data-file="'.$child_file['file'].'" data-downloadfile="'.$child_file['url'].'"><img src="'.$logoImage.'"> ';
					$return .= $child_file['name'];
					$return .= '</a>'; 
					$return .= '</li>'; 
				 }
			}
			$return .= '</ul>';
			}
			echo $return;
		}
		die;
	}
	public function mk_theme_editor_file_open() {
		if(wp_verify_nonce($_POST['_wpnonce'],'ms_theme_editor') && current_user_can('manage_options'))
		{
			$real_file = $_POST['path'];
			$data = file_get_contents( $real_file );
			echo $data;
		}
		die;
	}
	public function mk_theme_editor_folder_create() {
		$nonce = $_POST['_nonce'];
		if(wp_verify_nonce( $nonce, 'mk-fd-nonce') && current_user_can('manage_options')) {
		$theme_path = $_POST['theme_path'];
		$folder_path = $_POST['nfafn'];
		$permission = '0777';
		$fullPath = $theme_path.$folder_path;
		if (!file_exists($fullPath)) {
         $createFolder = mkdir($fullPath);
			 if($createFolder) {
				 
				 $response = json_encode(array('status' => '1', 'msg' => 'Folder Created Successfully!'));
				 
			 } else {
				 
				 $response = json_encode(array('status' => '2', 'msg' => 'Unable to create folder! Try again.'));
	
			 }
		 } else {
			 
			 $response = json_encode(array('status' => '2', 'msg' => 'Folder already Exists!'));
			 
		 }
		 echo $response;
		}
		die;
	}
	public function mk_theme_editor_file_create() {
		$nonce = $_POST['_nonce'];
		if(wp_verify_nonce( $nonce, 'mk-fd-nonce') && current_user_can('manage_options')) {
		$theme_path = $_POST['theme_path'];
		$file_path = $_POST['nfafn'];
		$fullPath = $theme_path.$file_path;
		if (!file_exists($fullPath)) {
          $createFile = fopen($fullPath, "w"); 
			 if(!empty($createFile)) {
				 
				 $response = json_encode(array('status' => '1', 'msg' => 'File Created Successfully!'));
				 
			 } else {
				 
				 $response = json_encode(array('status' => '2', 'msg' => 'Unable to create file! Try again.'));
	
			 }
		 } else {
			 
			 $response = json_encode(array('status' => '2', 'msg' => 'File already Exists!'));
			 
		 } 
		 echo $response;
		}
		die;
	}
	
  public function mk_theme_editor_folder_remove() {
	    $nonce = $_POST['_nonce'];
		if(wp_verify_nonce( $nonce, 'mk-fd-nonce') && current_user_can('manage_options')) {
		$theme_path = $_POST['theme_path'];
		$folder_path = $_POST['rfafn'];
		$fullPath = $theme_path.$folder_path;
		  if (!file_exists($fullPath)) {
			 $response = json_encode(array('status' => '2', 'msg' => 'Folder Not Exists!'));  
		  } else {
			 $deleteFolderwithfiles = $this->theme_controller->deleteDir($fullPath); 
			 if($deleteFolderwithfiles) {
			   $response = json_encode(array('status' => '1', 'msg' => 'Folder Deleted Successfully!')); 
			 } else {
			   $response = json_encode(array('status' => '2', 'msg' => 'Unable to Delete Folder!'));  
			 }
		  }
		} else {
			$response = json_encode(array('status' => '2', 'msg' => 'Unable to verify nonce!'));  
		}
		echo $response ;
	  die;
  }
  public function mk_te_close_te_help() {
		   $what_to_do = sanitize_text_field($_POST['what_to_do']);
		   $expire_time = 15;
		  if($what_to_do == 'rate_now' || $what_to_do == 'rate_never') {
			 $expire_time = 365;
		  } else if($what_to_do == 'rate_later') {
			 $expire_time = 15;
		  }	
		  if ( false === ( $mk_te_close_te_help = get_transient( 'mk_te_close_te_help_c' ) ) ) {
			   $set =  set_transient( 'mk_te_close_te_help_c', 'mk_te_close_te_help_c', 60 * 60 * 24 * $expire_time );
				 if($set) {
					 echo 'ok';
				 } else {
					 echo 'oh';
				 }
			   } else {
				    echo 'ac';
			   }
		   die;
	   }
   public function mk_theme_editor_file_remove() {
	    $nonce = $_POST['_nonce'];
		if(wp_verify_nonce( $nonce, 'mk-fd-nonce') && current_user_can('manage_options')) {
		$theme_path = $_POST['theme_path'];
		$file_path = $_POST['rfanf'];
		$fullPath = $theme_path.$file_path;
		  if (!file_exists($fullPath)) {
			 $response = json_encode(array('status' => '2', 'msg' => 'File Not Exists!'));  
		  } else {
			 $deletefile = $this->theme_controller->deleteFile($fullPath); 
			if($deletefile) {
			   $response = json_encode(array('status' => '1', 'msg' => 'File Deleted Successfully!')); 
			 } else {
			   $response = json_encode(array('status' => '2', 'msg' => 'Unable to Delete File!'));  
			 }
		  }
		} else {
			$response = json_encode(array('status' => '2', 'msg' => 'Unable to verify nonce!'));  
		}
		echo $response ;
	  die;
  }
    public function mk_theme_editor_file_upload() {
		$nonce = $_POST['_nonce'];
		if(wp_verify_nonce( $nonce, 'mk-fd-nonce') && current_user_can('manage_options')) {			
		// Theme file upload
		$slash = '/';
		if ( WPWINDOWS ) {
		  $slash = '\\';
		}
    if ( isset( $_FILES["file-0"] ) && isset( $_POST['current_theme_root'] ) ) {
      $current_theme_root = $_POST['current_theme_root'];
      $directory = '';
      if ( isset( $_POST['directory'] ) ) {
        $directory = $_POST['directory'];
        $dir = substr( $directory, -1 );
        if ( $dir != $slash ) {
          $directory = $directory . $slash;
        }
        $dir = substr( $directory, 0, 1 );
        if ( $dir == $slash ) {
          $directory = substr( $directory, 1 );
        }
      }
      $complete_directory = $current_theme_root . $directory;
      if ( !is_dir( $complete_directory ) ) {
        mkdir( $complete_directory);
      }
      
      if ( $_FILES["file-0"]["error"] > 0 ) {
		$response = json_encode(array('status' => '2', 'msg' =>  $_FILES["file-0"]["error"]));  
      }
      else {

        if ( file_exists( $complete_directory . $_FILES["file-0"]["name"] ) ) {
          $error = -1;
		  $response = json_encode(array('status' => '2', 'msg' => $_FILES["file-0"]["name"].' already exists'));  
        }
        else {
          move_uploaded_file( $_FILES["file-0"]["tmp_name"], $current_theme_root . $directory . $_FILES["file-0"]["name"] );
          $success = "File Uploaded Successfully: Uploaded File Path is " . basename( $complete_directory ) . $slash . $_FILES["file-0"]["name"];
		  $response = json_encode(array('status' => '1', 'msg' => $success));  
        }
      }
    }
    else {
	     $response = json_encode(array('status' => '2', 'msg' => 'No File Selected'));  
    }
} else {
		 $response = json_encode(array('status' => '2', 'msg' => 'Unable to verify nonce!'));  
	}
	  echo $response ;
	  die;
  }
  
}