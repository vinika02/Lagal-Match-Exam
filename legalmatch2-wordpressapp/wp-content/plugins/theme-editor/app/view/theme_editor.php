<?php if ( ! defined( 'ABSPATH' ) ) exit; 
$this->theme_controller->load_css();  ?>
<?php $current_user = wp_get_current_user(); 
$vle_nonce = wp_create_nonce( 'verify-theme-editor-email' ); ?>
<script>
var vle_nonce = "<?php echo $vle_nonce;?>";
</script>
<div class="wrap">
<?php $this->load_help_desk(); ?>
<?php //screen_icon();
add_thickbox();  ?>
<h2><?php _e( 'Edit Themes', 'theme-editor' ); ?> <a href="https://themeeditor.pro/product/theme-editor/" class="button button-primary" target="_blank"><?php _e('BUY PRO', 'theme-editor');?> </a></h2>
<?php 
$nonce = wp_create_nonce( 'ms_theme_editor' );
$theme = wp_get_theme();	
$data = $this->theme_controller->te_get_theme_data();
$image_types = $this->theme_controller->image_type_posibilities;
$parent_files = $this->theme_controller->get_files_and_folders( $data['current_theme_root'], '0', 'theme' );
if ( is_object( $data['wp_theme'] ) && $data['wp_theme']->name == $theme->name ): ?>
		<div class="updated">
			<p><?php _e( '<strong>This theme is currently activated!<br />Warning:</strong> Making changes to active themes is not recommended.', 'theme-editor' ); ?></p>
		</div>
	<?php endif; ?>   
   <div class="fileedit-sub">
		<div class="alignleft">
			<h3>
				<?php if ( WP_34 ): ?>
					<?php echo $data['wp_theme']->display( 'Name' ) . ': '; ?>
				<?php else: ?>
					<?php echo $data['themes'][ $data['theme'] ]['Name'] . ': '; ?>
				<?php endif; ?>
				<?php
					if ( is_writable( $data['real_file'] ) ) {
						echo '<span class="writable_status">' . __( 'Editing', 'theme-editor' ) . '</span> <span class="current_file">' . $data['file'] . '</span>';
					}
					else {
						echo '<span class="writable_status">' . __( 'Browsing', 'theme-editor' ) . '</span> <span class="current_file">' . $data['file'] . '</span>';
					}
				?>
			</h3>
		</div>
		<div class="alignright">
			<form action="themes.php?page=theme_editor_theme" method="post">
				<strong><label for="plugin"><?php _e( 'Select theme to edit:', 'theme-editor' ); ?></label></strong>
				<select name="theme" id="theme">
					<?php if ( WP_34 ): ?>
						<?php
							foreach ( wp_get_themes( array( 'errors' => null ) ) as $a_stylesheet => $a_theme ) {
								if ( $a_theme->errors() && 'theme_no_stylesheet' == $a_theme->errors()->get_error_code() ) {
									continue;
								}
								$selected = $a_stylesheet == strtolower( $data['stylesheet'] ) ? ' selected="selected"' : '';
								echo "\n\t" . '<option value="' . esc_attr( $a_stylesheet) . '"' . $selected . '>' . $a_theme->display( 'Name' ) . '</option>';
							}
						?>
					<?php else: ?>
						<?php
							foreach ( $data['themes'] as $a_theme ) {
								$theme_name = $a_theme['Name'];
								if ( $theme_name == $data['theme'] ) {
									$selected = ' selected="selected"';
								}
								else {
									$selected = '';
								}
								$theme_name = esc_attr( $theme_name ); ?>
								<option value="<?php echo $theme_name; ?>" <?php echo $selected; ?>><?php echo $theme_name; ?></option>
							<?php
							}
						?>
					<?php endif; ?>
				</select>
				<input type='submit' name='submit' class="button-secondary" value="<?php _e( 'Select', 'theme-editor' ); ?>" />
			</form>
            <div class="theme_action_section"><a href="#TB_inline?width=600&height=200&inlineId=theme_upload" class="thickbox button button-primary" title="Upload Files and Folders"><?php _e( 'Upload', 'theme-editor' ); ?></a> <a href="#TB_inline?width=600&height=450&inlineId=create_folder" class="thickbox button button-primary" title="Create Folder and File"><?php _e( 'Create', 'theme-editor' ); ?></a> <a href="#TB_inline?width=600&height=450&inlineId=remove_folder" class="thickbox button button-primary" title="Remove Folder and File"><?php _e( 'Remove ', 'theme-editor' ); ?></a></div>
		</div>
		<br class="clear" />
	</div>
    
<div class="ms_theme_conatiner_full_width">	
	
 <div id="templateside">
		
		<h3><?php _e( 'Theme Files', 'theme-editor' ); ?></h3>
		<div id="theme-editor-files">
			<ul id="theme-folders" class="theme-folders">
			<input type="hidden" id="tf_wpnonce" name="tf_wpnonce" value="<?php echo $nonce;?>">
            <?php /* code start */ 
			if(!empty($parent_files)) {
				foreach($parent_files as $parent_file) { 
				  $logoImagePath = MK_THEME_EDITOR_PATH.'app/view/images/'.$parent_file['extension'].'.png';
				  $logoImage = MK_THEME_EDITOR_URL.'app/view/images/'.$parent_file['extension'].'.png';
				  if(!file_exists($logoImagePath)) {
					   $logoImage = MK_THEME_EDITOR_URL.'app/view/images/def.png';  
				     }
				 //folder	
				 if($parent_file['filetype'] == 'folder') { ?>
					<li class="<?php echo $parent_file['extension'];?> small_icons"><a href="javascript:void(0)" class="open_folder" data-path="<?php echo $parent_file['path']?>" data-name="<?php echo $parent_file['extension'].$parent_file['name']?>"><img src="<?php echo MK_THEME_EDITOR_URL.'app/view/images/'.$parent_file['extension']?>.png" /> <?php echo $parent_file['name']?></a>
                      <span class="<?php echo $parent_file['extension'].$parent_file['name'];?>"></span>               
                    </li> 
				   <?php  }
				   //img	
				    else if(in_array($parent_file['extension'], $this->theme_controller->image_type_posibilities)) { ?>
					<li class="<?php echo $parent_file['extension'];?> small_icons">
 <a href="<?php echo $parent_file['url']?>" class="open_image thickbox" target="_blank"><img src="<?php echo $parent_file['url']?>" /> <?php echo $parent_file['name']?> </a>
                    </li>	
					<?php }
					 //dwn
					else if(in_array($parent_file['extension'], $this->theme_controller->download_type_possibilities)) { ?>
					<li class="<?php echo $parent_file['extension'];?> small_icons">
<a href="<?php echo $parent_file['url']?>" class="dwn_file" target="_blank" download><img src="<?php echo $logoImage; ?>" /> <?php echo $parent_file['name']?></a>
                    </li>	
					<?php } else { ?>
					<li class="<?php echo $parent_file['extension'];?> small_icons">
                    <a href="javascript:void(0)" class="open_file" data-path="<?php echo $parent_file['path']?>" data-name="<?php echo $parent_file['extension'].$parent_file['name']?>" data-file="<?php echo $parent_file['file'];?>" data-downloadfile="<?php echo $parent_file['url'];?>"><img src="<?php echo $logoImage;?>" /> <?php echo $parent_file['name']?></a>
                    </li>	
				<?php }					
				} // end parent foreach
			}
			/* end code */
			?>
            </ul>
		</div>
	</div>   
    
    
<form name="template" id="template_form" action="" method="post" class="ajax-editor-update" style="float:left width:auto;overflow:hidden;">
<div class="te_popup" style="display:none;">
<div class="te_popup_message"></div>
<div class="clear"></div>
</div>
		<?php //wp_nonce_field( 'edit-theme_' . $data['real_file'] ); ?>
		<div>
			<textarea cols="70" rows="25" name="new-content" id="new-content" tabindex="1"><?php echo $data['content'] ?></textarea>
			<input type="hidden" id="path" name="path" value="<?php echo esc_attr( $data['real_file'] ); ?>" />
            <input type="hidden" id="file_url" name="file_url" value="<?php echo get_theme_root_uri().'/'.esc_attr( $data['file'] ); ?>" />
            <input type="hidden" id="theme_name" name="theme_name" value="<?php echo esc_attr( $data['file'] ); ?>" />
			<input type="hidden" id="_wpnonce" name="_wpnonce" value="<?php echo $nonce;?>">
			<?php
				$pathinfo = pathinfo( $data['file'] );
			?>
		</div>
		<p class="submit">
			<?php if ( isset( $_GET['phperror'] ) ): ?>
				<input type="hidden" name="phperror" value="1" />
				<input type="submit" name="submit" class="button-primary" value="<?php _e( 'Update File and Attempt to Reactivate', 'theme-editor' ); ?>" />
			<?php else: ?>
				<input type="submit" name='submit' class="button-primary update_file" value="<?php _e( 'Update File', 'theme-editor' ); ?>" />
			<?php endif; ?>
			
			<input type="button" class="button-secondary download-file" value="<?php _e( 'Download File', 'theme-editor' ); ?>"/>
			<input type="button" class="button-secondary download-theme" value="<?php _e( 'Download Theme', 'theme-editor' ); ?>" />
		</p>
		<?php if (!is_writable( $data['real_file'] ) ): ?>
			<div class="error writable-error">
				<p>
					<em><?php _e( 'You need to make this file writable before you can save your changes. See <a href="http://codex.wordpress.org/Changing_File_Permissions" target="_blank">the Codex</a> for more information.' ); ?></em>
				</p>
			</div>
		<?php endif; ?>
	</form> 
</div>	
<?php $nonce = wp_create_nonce( 'mk-fd-nonce' ); 
$current_theme = str_replace('\\','/',$data['current_theme_root']); 
?>
   <script>
	   var mk_nonce = "<?php echo $nonce; ?>";
	   var mk_current_theme = "<?php echo $current_theme; ?>";
	   var current_cm_theme = "<?php echo $this->theme_controller->defcmt;?>";
   </script>
   <?php $this->theme_controller->load_js(); ?>  
<?php /* Upload Process Start */ ?>    
<div id="theme_upload" style="display:none;">
<div class="te_upload_folder_file">
<h4><?php _e( 'Upload ', 'theme-editor' ); ?></h4>
<span class="up_response"></span>
	<form enctype="multipart/form-data" id="theme_upload_form" method="POST">
							<p class="description">
								<?php _e( 'To', 'theme-editor' ); ?>: <?php echo basename( dirname( $data['current_theme_root'] ) ) . '/' . basename( $data['current_theme_root'] ) . '/'; ?>
							</p>
							<input type="hidden" name="current_theme_root" value="<?php echo $data['current_theme_root']; ?>" id="current_theme_root" />
							<input type="text" name="directory" id="file_directory" placeholder="<?php _e( 'Optional: Sub-Directory', 'theme-editor' ); ?>" class="regular-text" />
							<input name="file" type="file" id="upload_file" style="width:180px" />
					        <input id="submit" class="button button-primary" name="submit" value="Upload File" type="submit">
					</form>   
</div>
</div>
<?php /* end upload Process */?> 

<?php /* Create Folder Process Start */ ?>    
<div id="create_folder" style="display:none;">
<span class="te_response"></span>

<div class="te_create_folder">
<h4><?php _e( 'Create a New Folder: ', 'theme-editor' ); ?></h4>
<p>
<label for="new-folder-path"><?php _e( 'New folder will be created in: ', 'theme-editor' ); ?></label> <br>
<img alt="" src="<?php echo MK_THEME_EDITOR_URL.'app/view/images/';?>homeb.gif" height="15" width="15"> <code><?php echo $current_theme;?></code>
</p>
<p><label for="newdir"><?php _e( 'New Folder Name: ', 'theme-editor' ); ?></label><input type="text" id="nfafn" name="nfafn" value="" class="regular-text" /></p>
 <p><input name="submit" class="button-primary" value="Create New Folder" type="button" id="cfaf"></p>
 </div>
<hr /> 
<div class="te_create_file">
<h4><?php _e( 'Create a New File: ', 'theme-editor' ); ?></h4>
<p>
<label for="new-folder-path"><?php _e( 'New File will be created in: ', 'theme-editor' ); ?></label> <br>
<img alt="" src="<?php echo MK_THEME_EDITOR_URL.'app/view/images/';?>homeb.gif" height="15" width="15"> <code><?php echo $current_theme;?></code>
</p>
<p><label for="newdir"><?php _e( 'New File Name: ', 'theme-editor' ); ?></label><input type="text" id="nfanf" name="nfanf" value="" class="regular-text" /></p>
 <p><input name="submit" class="button-primary" value="Create New File" type="button" id="cffa"></p>
 </div>
</div>
<?php /* end Create Folder Process */ ?> 

<?php /* Remove File and folder Start */ ?>
<div id="remove_folder" style="display:none;">
<div class="te_create_folder">
<p class="te_error"><?php _e( 'Warning: Please be careful before remove any folder or file.', 'theme-editor' ); ?></p>
<span class="ter_response"></span>  
<p>
<label for="new-folder-path"><?php _e( 'Current Theme Path: ', 'theme-editor' ); ?></label> <br>
<img alt="" src="<?php echo MK_THEME_EDITOR_URL.'app/view/images/';?>homeb.gif" height="15" width="15"> <code><?php echo $current_theme;?></code>
</p>
<h4><?php _e( 'Remove Folder: ', 'theme-editor' ); ?></h4>
<p><label for="newdir"><?php _e( 'Folder Path which you want to remove: ', 'theme-editor' ); ?></label><input type="text" id="rfafn" name="nfafn" value="" class="regular-text" /></p>
 <p><input name="submit" class="button-primary" value="Remove Folder" type="button" id="rfaf"></p>
 </div>
<hr /> 
 <div class="te_create_folder">
<h4><?php _e( 'Remove File: ', 'theme-editor' ); ?></h4>
<p><label for="newdir"><?php _e( 'File Path which you want to remove: ', 'theme-editor' ); ?></label><input type="text" id="rfanf" name="nfanf" value="" class="regular-text" /></p>
 <p><input name="submit" class="button-primary" value="Remove File" type="button" id="rffa"></p>
 </div> 
</div>
<?php /* Remove File and folder end */ ?>


<?php ///***** Verify Lokhal Popup Start *****/// 
//delete_transient( 'theme_editor_cancel_lk_popup_'.$current_user->ID );
?>
<?php if(false === get_option( 'theme_editor_email_verified_'.$current_user->ID ) && ( false === ( get_transient( 'theme_editor_cancel_lk_popup_'.$current_user->ID ) ) ) ) { ?>
<div id="lokhal_verify_email_popup" class="lokhal_verify_email_popup">
<div class="lokhal_verify_email_popup_overlay"></div>
<div class="lokhal_verify_email_popup_tbl">
<div class="lokhal_verify_email_popup_cel">
<div class="lokhal_verify_email_popup_content">
<a href="javascript:void(0)" class="lokhal_cancel"> <img src="<?php echo plugins_url( 'view/images/fm_close_icon.png', dirname(__FILE__) ); ?>" class="wp_fm_loader" /></a>
<div class="popup_inner_lokhal">
<h3><?php  _e('Welcome to Theme Editor', 'theme-editor'); ?></h3>
<p class="lokhal_desc"><?php  _e('We love making new friends! Subscribe below and we promise to  
keep you up-to-date with our latest new plugins, updates,
awesome deals and a few special offers.', 'theme-editor'); ?></p>
<form>
<div class="form_grp">
<div class="form_twocol">
<input name="verify_lokhal_fname" id="verify_lokhal_fname" class="regular-text" type="text" value="<?php echo (null == get_option('verify_theme_editor_fname_'.$current_user->ID)) ? $current_user->user_firstname : get_option('verify_theme_editor_fname_'.$current_user->ID);?>" placeholder="First Name" />
<span id="fname_error" class="error_msg"><?php  _e('Please Enter First Name.', 'theme-editor'); ?></span>
</div>
<div class="form_twocol">
<input name="verify_lokhal_lname" id="verify_lokhal_lname" class="regular-text" type="text" value="<?php echo (null == 
get_option('verify_theme_editor_lname_'.$current_user->ID)) ? $current_user->user_lastname : get_option('verify_theme_editor_lname_'.$current_user->ID);?>" placeholder="Last Name" />
<span id="lname_error" class="error_msg"><?php  _e('Please Enter Last Name.', 'theme-editor'); ?></span>
</div>
</div>
<div class="form_grp">
<div class="form_onecol">
<input name="verify_lokhal_email" id="verify_lokhal_email" class="regular-text" type="text" value="<?php echo (null == get_option('theme_editor_email_address_'.$current_user->ID)) ? $current_user->user_email :  get_option('theme_editor_email_address_'.$current_user->ID);?>" placeholder="Email Address" />
<span id="email_error" class="error_msg"><?php  _e('Please Enter Email Address.', 'theme-editor'); ?></span>
</div>
</div>
<div class="btn_dv">
<button class="verify verify_local_email button button-primary "><span class="btn-text">Verify
          </span>
          <span class="btn-text-icon">
            <img src="<?php echo plugins_url( 'view/images/btn-arrow-icon.png', dirname(__FILE__) ); ?>"/>
          </span></button>
<button class="lokhal_cancel button">No Thanks</button>
</div>
</form>
</div>
<div class="fm_bot_links">
  <a href="http://ikon.digital/terms.html" target="_blank"><?php  _e('Terms of Service', 'theme-editor'); ?></a>   <a href="http://ikon.digital/privacy.html" target="_blank"><?php  _e('Privacy Policy', 'theme-editor'); ?></a>
</div>

</div>
</div>
</div>
</div>

<?php } ///***** Verify Lokhal Popup End *****/// ?>



</div>