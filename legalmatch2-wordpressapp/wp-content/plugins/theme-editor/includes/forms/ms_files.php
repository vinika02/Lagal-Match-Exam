<?php if ( !defined( 'ABSPATH' ) ) exit;
$current_user = wp_get_current_user();
$cuser =  $current_user->user_login;
$crole =  $current_user->roles[0];
$ctd = $this->ms_theme_directory( 'child' );
$ctpd = $this->ms_theme_directory( 'parnt' );
$nonce = wp_create_nonce( 'ms_theme_editor' );
?>
<div id="files-setting" class="ms_te_settings_tabs_sec">
	<div class="ms_filesWrap">
		<div class="msFormRow padtop0">
			<div id="ms_file_notice"></div>
			<div class="ms-text">  
				<label class="mslabelHeading">
					<span class="labelHeadingText"><?php _e('Parent Templates', 'theme-editor');?></span> 
				</label>
				<p class="htxt">
					<?php _e('Copy PHP templates from the parent theme by selecting them here. The Configurator defines a template as a Theme PHP file having no PHP functions or classes. Other PHP files cannot be safely overridden by a child theme. ', 'theme-editor');?>
				</p>  
				<p class="htxt">
					<strong>
					<?php _e('CAUTION: If your child theme is active, the child theme version of the file will be used instead of the parent immediately after it is copied.', 'theme-editor');?>
					</strong>
				</p>
				<p class="htxt">  <?php _e('The', 'theme-editor');?>
				<code><?php _e('functions.php', 'theme-editor');?></code> <?php _e('file is generated separately and cannot be copied here.', 'theme-editor');?></p>
			</div>
			<div class="ms_fileNames ms_parentfile">
				<?php 
				if(!empty($ctpd)){
					$theme_path = get_theme_root().'/'.$ctpd;	
					$check_name ='ms_file_parnt';
					$check_point = outputFiles($theme_path,$theme_path,$check_name);
				}
				//Copy File Permission
				if(isset($ac_opt['ms_user_file_parent_to_child']) && is_array($ac_opt['ms_user_file_parent_to_child']) && in_array($cuser, $ac_opt['ms_user_file_parent_to_child'])){
					$fc_permission	= 'Yes';
				}
				else if(isset($ac_opt['ms_userrole_file_parent_to_child']) && is_array($ac_opt['ms_userrole_file_parent_to_child']) && in_array($crole, $ac_opt['ms_userrole_file_parent_to_child']))
				{					
					$count = 0;
					$ct_pm = ms_child_theme_permission();
					foreach($ct_pm as $value)
					{
						if(isset($ac_opt[$value]) && is_array($ac_opt[$value]) && in_array($cuser, $ac_opt[$value]))
						{
							$count++;
						}
					}
					if($count==0){
						$fc_permission	= 'Yes';
					}
					else{
						$fc_permission	= 'Yes';
					}
				}
				else{
					$fc_permission	= 'Yes';
				}
				?>
			</div>
			<div class="ms_pbtn ms_clear padtop15">
				<input data-attr="<?php echo $fc_permission;?>" class="ms_submit ms_copy" id="ms_parnt_templates_submit" name="ms_parnt_templates_submit" type="button" value="Copy Selected to Child Theme">
			</div>
		</div>
		<div class="msFormRow">
			<div class="ms-text">
				<label class="mslabelHeading">
					<span class="labelHeadingText"><?php _e('Child Theme Files', 'theme-editor');?></span> 
				</label> 
				<p class="htxt">
					<a href="<?php echo site_url();?>/wp-admin/admin.php?page=theme_editor_theme" title="Click to edit functions.php">
					<?php _e('Click to edit files using the Theme Editor', 'theme-editor');?></a>      
				</p>
				<p class="htxt">				 
					<?php _e('Delete child theme templates by selecting them here.', 'theme-editor');?>
				</p>
			</div>
			<div class="ms_fileNames ms_childfile">
			<?php 
				$child_dir = array();
				if(!empty($ctd)){
					$ctheme_path = get_theme_root().'/'.$ctd;
					$check_name ='ms_file_child';
					echo outputFiles($ctheme_path,$ctheme_path,$check_name);		
				}
				//Copy File Permission
				if(isset($ac_opt['ms_user_deleted_file']) && is_array($ac_opt['ms_user_deleted_file']) && in_array($cuser, $ac_opt['ms_user_deleted_file'])){
					$dl_permission	= 'Yes';
				}
				else if(isset($ac_opt['ms_userrole_deleted_file']) && is_array($ac_opt['ms_userrole_deleted_file']) && in_array($crole, $ac_opt['ms_userrole_deleted_file']))
				{
					//$dl_permission	= 'Yes';
					$count = 0;
					$ct_pm = ms_child_theme_permission();
					foreach($ct_pm as $value)
					{
						if(isset($ac_opt[$value]) && is_array($ac_opt[$value]) && in_array($cuser, $ac_opt[$value])){
							$count++;
						}
					}
					if($count==0){
						$dl_permission	= 'Yes';
					}
					else{
						$dl_permission	= 'Yes';
					}
				}
				else{
					$dl_permission	= 'Yes';
				}
				?>
			</div>
			<div class="ms_cbtn ms_clear padtop15">
				<input data-attr="<?php echo $dl_permission;?>" class="ms_submit ms_delete_btn" id="ms_child_del" name="ms_child_del" type="button" value="Delete Selected">
			</div>
		</div>
		<div class="ms_containerOuter">
			<div class="ms_col30">
				<div class="ms-text"><strong><?php _e('Child Theme Screenshot', 'theme-editor');?></strong></div>
			</div>
			<div class="ms_col70">
				<div class="ms-input-box-wide ms_screen_shot_img"> 
					<?php 
					$image = array('jpg','jpeg','png','gif');
					foreach($image as $img_key => $img_value)
					{
						$full_child_dir = get_theme_root().'/'.$ctd."/screenshot.".$img_value;
						$extension = pathinfo($full_child_dir, PATHINFO_EXTENSION);
						$ms_child_dir_notice = get_theme_root_uri().'/'.$ctd.'/screenshot.';
						$child_image_url = get_theme_root_uri().'/'.$ctd.'/screenshot.'.$img_value;
						if (file_exists($full_child_dir)){ ?>
							<img src="<?php echo $child_image_url;?>" width="200" height="150">
							<?php
							break;
						}
					}
					//New Screenshot permission
					if(isset($ac_opt['ms_user_upload_new_screenshoot']) && is_array($ac_opt['ms_user_upload_new_screenshoot']) && in_array($cuser, $ac_opt['ms_user_upload_new_screenshoot'])){
						$nsc_permission	= 'Yes';
					}
					else if(isset($ac_opt['ms_userrole_upload_new_screenshoot']) && is_array($ac_opt['ms_userrole_upload_new_screenshoot']) && in_array($crole, $ac_opt['ms_userrole_upload_new_screenshoot'])){		
						$count = 0;
						$ct_pm = ms_child_theme_permission();
						foreach($ct_pm as $value){
							if(isset($ac_opt[$value]) && is_array($ac_opt[$value]) && in_array($cuser, $ac_opt[$value])){
								$count++;
							}
						}
						if($count==0){
							$nsc_permission	= 'Yes';
						}else{
							$nsc_permission	= 'Yes';
						}
					}
					else{
						$nsc_permission	= 'Yes';
					}
					//New Upload Image permission
					if(isset($ac_opt['ms_user_upload_new_images']) && is_array($ac_opt['ms_user_upload_new_images']) && in_array($cuser, $ac_opt['ms_user_upload_new_images'])){
						$isc_permission	= 'Yes';
					}
					else if(isset($ac_opt['ms_userrole_upload_new_images']) && is_array($ac_opt['ms_userrole_upload_new_images']) && in_array($crole, $ac_opt['ms_userrole_upload_new_images'])){				
						$count = 0;
						$ct_pm = ms_child_theme_permission();
						foreach($ct_pm as $value){
							if(isset($ac_opt[$value]) && is_array($ac_opt[$value]) && in_array($cuser, $ac_opt[$value])){
								$count++;
							}
						}
						if($count==0){
							$isc_permission	= 'Yes';
						}
						else{
							$isc_permission	= 'Yes';
						}
					}
					else{
						$isc_permission	= 'Yes';
					}
					?>
				</div>
			</div>
		</div>
		<div class="msFormRow">
			<div class="ms-text">
				<label class="mslabelHeading"><span class="labelHeadingText"><?php _e('Upload New Screenshot', 'theme-editor');?></span> </label>
				<p class="htxt"><?php _e('The theme screenshot should be a 4:3 ratio (e.g., 880px x 660px) JPG, PNG or GIF. It will be renamed', 'theme-editor');?> <code><?php _e('screenshot', 'theme-editor');?></code>.</p>
			</div>
			<div class="ms-input-box-wide"> 
				<form id="screenshotuploads" method="post" enctype="multipart/form-data">
				    <input type="hidden" id="_wpnonce" name="_wpnonce" value="<?php echo $nonce;?>"/>
					<input id="ms_theme_screenshot" class="msFormInput" name="ms_theme_screenshot" required value="" type="file">
					<div class="padtop15">
					<input data-attr="<?php echo $nsc_permission; ?>" class="ms_submit ms_withInputBtn screenshot" id="ms_theme_screenshot_submit" name="ms_theme_screenshot_submit" value="Upload" type="submit">
					</div>
					<input type="hidden" name="action" value="screenshot_upload"/>
					<input type="hidden" name="ctd" value="<?php echo $ctd;?>"/>
					<input type="hidden" name="ctpd" value="<?php echo $ctpd;?>"/>
				</form>
				<div class="percent"></div>	
			</div>
		</div>
		<div class="msFormRow">
			<div class="ms-text">
				<label class="mslabelHeading"><span class="labelHeadingText">
				<?php _e('Upload New Child Theme Image', 'theme-editor');?>
				</span></label>
				<p class="htxt">
					<?php _e('Theme images reside under the images directory in your child theme and are meant for stylesheet use only. Use the Media Library for content images.   ', 'theme-editor');?>
				</p>
			</div>
			<div class="ms-input-box-wide"> 
				<form id="photouploads" method="post" enctype="multipart/form-data">
				 <input type="hidden" id="_wpnonce" name="_wpnonce" value="<?php echo $nonce;?>"/>
				<input id="webphotos" name="webphotos" class="msFormInput" value="" type="file" required>
				<div class="padtop15">
				<input data_attr="<?php echo $isc_permission;?>" class="ms_submit ms_withInputBtn imageupload" id="ms_theme_screenshot_submit" name="ms_theme_screenshot_submit" value="Upload" type="submit">
				</div>
				<input type="hidden" name="action" value="webphoto_upload"/>
				<input type="hidden" name="ctd" value="<?php echo $ctd;?>"/>
				<input type="hidden" name="ctpd" value="<?php echo $ctpd;?>"/>
				</form>
				<div class="percen"></div>
			</div>
		</div>
		<div class="msFormRow">
			<div class="ms-text">
			<label class="mslabelHeading">
				<span class="labelHeadingText"><?php _e('Preview Current Child Theme (Current analysis)', 'theme-editor');?></span></label>
			</div>
			<div class="padtop15">
				<?php 
				$get_the_detail = wp_get_theme($ctd);
				$child_theme_name = $get_the_detail->Name;
				?>
				<a  id='ms_file_pexport' class="img_box_preview" href="<?php echo site_url();?>/wp-admin/customize.php?theme=<?php echo $ctd;?>&return=<?php echo site_url();?>/wp-admin/admin.php?page=ms_child_theme_editor&tab=file_options">
				<?php _e('Preview Current Child Theme', 'theme-editor');?>				
				</a>
			</div>
		</div>
		<div class="msFormRow last_msFormRow padbot0">
			<div class="ms-text">
				<label class="mslabelHeading">
					<span class="labelHeadingText">
					<?php _e('Export Child Theme as Zip Archive', 'theme-editor');?>					
					</span>
				</label>
				<p class="htxt"><?php _e('Click "Export Zip" to save a backup of the currently loaded child theme. You can export any of your themes from the Parent/Child tab.', 'theme-editor');?></p>
			</div>
			<div class="">
				<?php 
				$get_the_detail = wp_get_theme($ctd);
				$child_theme_name = $get_the_detail->Name;
				//New Upload Image permission
				if(isset($ac_opt['ms_user_export_theme']) && is_array($ac_opt['ms_user_export_theme']) && in_array($cuser, $ac_opt['ms_user_export_theme'])){
					$esc_permission	= 'Yes';
				}
				else if(isset($ac_opt['ms_userrole_export_theme']) && is_array($ac_opt['ms_userrole_export_theme']) && in_array($crole, $ac_opt['ms_userrole_export_theme']))
				{
					$count = 0;
					$ct_pm = ms_child_theme_permission();
					foreach($ct_pm as $value)
					{
						if(isset($ac_opt[$value]) && is_array($ac_opt[$value]) && in_array($cuser, $ac_opt[$value])){
							$count++;
						}
					}
					if($count==0){
						$esc_permission	= 'Yes';
					}
					else{
						$esc_permission	= 'Yes';
					}
				}
				else{
					$esc_permission	= 'Yes';
				}
				?>
				<input type="hidden" name="theme_name" id="theme_name" value="<?php echo $ctd.'\style.css';?>"/>
				<div class="ms-input-box-wide">
					<form id="ms_export_theme_form" method="post" action="">
						<input data-attr="<?php echo $esc_permission;?>" id='ms_file_export' class="ms_submit ms_withInputBtn download-theme" name="ms_export_child_zip" value="Export Child Theme" type="button">
					</form> 
				</div>
			</div>
		</div>
	</div>
</div>
<?php 

function outputFiles($path,$theme_path,$check_name ){
	$begin_path = $theme_path;
	// Check directory exists or not
	if(file_exists($path) && is_dir($path)){
		// Scan the files in this directory
		$result = scandir($path);
		// Filter out the current (.) and parent (..) directories
		$files = array_diff($result, array('.', '..'));
		if(count($files) > 0){
			// Loop through retuned array
			foreach($files as $file){
				if(is_file("$path/$file")){
				// Display filename
				$full_path  =$path.'/'.$file;
				$ftype = pathinfo($full_path, PATHINFO_EXTENSION);
				$new_file_name = str_replace($begin_path.'/',"", $full_path);
					if($ftype == 'php'){
						$ms_disabled="";
						$ms_show = true;
						if($check_name == 'ms_file_child' && $new_file_name =='functions.php'){
							$ms_disabled="onclick='return false;'";		
                         $ms_show = false;							
						}
						else{
							$ms_disabled="";
							$ms_show = true;
						}					
					?>
					<label class="ms-checkboxFiles">
					<?php if($ms_show )
					{?>
						<input  class="ms_checkbox"  name="<?php echo $check_name;?>[]" value="<?php echo $full_path;?>" type="checkbox" <?php echo $ms_disabled;?>>
					<?php } ?>
						<?php echo $new_file_name;?>
					</label>
					<?php
					}
				} 
				else if(is_dir("$path/$file")){		
					outputFiles("$path/$file",$theme_path,$check_name);
				}
			}
		}
	}
}

?>
<script>
jQuery(document).on('click', '#ms_parnt_templates_submit', function() {
	var data_attr = jQuery(this).attr('data-attr');
	if(data_attr == 'Yes')
	{
		var checked = [];
		jQuery("input[name='ms_file_parnt[]']:checked").each(function (){
			checked.push(encodeURIComponent(jQuery(this).val()));
		});
		if(checked.length>0){
			var msg = confirm("<?php _e('Are you sure to Copy Parent Files into child Theme?', 'theme-editor');?>");
			if(msg){
				jQuery.ajax({
						type : "post",
						url : '<?php echo admin_url( 'admin-ajax.php') ?>',
						data : {
						action: "mk_theme_editor_file_move",
						_wpnonce:'<?php echo $nonce;?>',
						file_selected:checked,
						ctd:'<?php echo $ctd;?>',
						ctpd:'<?php echo $ctpd;?>',
					},
					success: function(response) {
						//alert(response);
						jQuery('.ms_childfile').append(response);
						jQuery('#ms_file_notice').html('<div class="updated notice is-dismissible"><p>Child Theme Files modified successfully.</p></div>');
					}
				});
			}
		}
		else{
			alert('<?php _e('Please select Files', 'theme-editor');?>');
		}
	}
	else{
		alert('<?php _e("You don\'t have permission to Copy Files", "theme-editor");?>');
	}
});


jQuery(document).on('click', '#ms_child_del', function() {
	var data_attr = jQuery(this).attr('data-attr');
	if(data_attr == 'Yes'){
		var checked = [];
		jQuery("input[name='ms_file_child[]']:checked").each(function (){
			checked.push(encodeURIComponent(jQuery(this).val()));
		});
		if(checked.length>0){
			var msg = confirm("<?php _e('Are you sure to want Deleted Selected Files?', 'theme-editor');?>");
			if(msg){
				jQuery.ajax({
					type : "post",
					url : '<?php echo admin_url( 'admin-ajax.php') ?>',
					data : {
					action: "mk_theme_editor_child_file_delete",
					file_selected:checked,
					ctd:'<?php echo $ctd;?>',
					ctpd:'<?php echo $ctpd;?>',
					_wpnonce:'<?php echo $nonce;?>',
					},
					success: function(response) {
					//alert(response);
					alert('<?php _e('All selected File are deleted Sucessfully.', 'theme-editor');?>');
					window.location.reload();
					}
				});
			}
		}
		else{
			alert('<?php _e('Please Select Files.', 'theme-editor');?>');
		}
	}
	else{
		alert('<?php _e('You have not permission to Delete Child Files.', 'theme-editor');?>');
	}
});

jQuery(document).ready(function (e) {

	jQuery("#photouploads").on('submit',(function(e) {
		e.preventDefault();
		var data_attr = jQuery('.imageupload').attr('data_attr');
		//alert(data_attr);
		if(data_attr =='Yes')
		{
			jQuery.ajax({
				url: "<?php echo admin_url('admin-ajax.php'); ?>",
				type: "POST",
				data:  new FormData(this),
				contentType: false,
				cache: false,
				processData:false,
				xhr: function (){
					var xhr = new window.XMLHttpRequest();
					xhr.upload.addEventListener("progress", function (evt) {
					if (evt.lengthComputable) {
					var percentComplete = evt.loaded / evt.total;
					percentComplete = parseInt(percentComplete * 100);
					jQuery('.percen').html('<div class="myprogress progresss" style="width:0%"></div><span class="precent_count">'+ percentComplete+'% Uploaded</span>');
					jQuery('.progresss').css('width',percentComplete+'%');
					}
					}, false);
					return xhr;
				},
				success: function(data){
					alert('<?php _e('Image uploaded successfully!', 'theme-editor');?>');
					window.location.reload();
				},        
			});
		}
		else{
			alert('<?php _e('You have not permission to upload new images', 'theme-editor');?>');
		}
	}));
	jQuery("#screenshotuploads").on('submit',(function(e) {
		e.preventDefault();
		var data_attr = jQuery('.screenshot').attr('data-attr');
		if(data_attr =='Yes')
		{
			jQuery.ajax({
				url: "<?php echo admin_url('admin-ajax.php'); ?>",
				type: "POST",
				data:  new FormData(this),
				contentType: false,
				cache: false,
				processData:false,
				xhr: function () {
				var xhr = new window.XMLHttpRequest();
				xhr.upload.addEventListener("progress", function (evt) {
					if (evt.lengthComputable) {
						var percentComplete = evt.loaded / evt.total;
						percentComplete = parseInt(percentComplete * 100);
						jQuery('.percent').html('<div class="myprogress progresss" style="width:0%"></div><span class="precent_count">'+ percentComplete+'%</span>');
						jQuery('.progresss').css('width',percentComplete+'%');
					}
				}, false);
					return xhr;
				},

				success: function(data)
				{
				//window.location.reload();
				
				alert(data);
				
				if(data!=0){ 
					jQuery('#ms_file_notice').html('<div class="updated notice is-dismissible"><p>Child Theme Screenshot Updated successfully.</p></div>');
					var img_src = "<?php echo $ms_child_dir_notice;?>"+data+ "?" + (new Date()).getTime();
					//alert(img_src);
					var html_img ='<img src="'+img_src+'" width="200" height="150"/>';
					var old_src = jQuery('.ms_screen_shot_img img').remove();
					var old_src = jQuery('.ms_screen_shot_img').html(html_img);
					jQuery('html, body').animate({
					'scrollTop':   jQuery('#ms_file_notice').offset().top
					}, 100);				
				}
				else
				{
					jQuery('#ms_file_notice').html('<div class="updated notice is-dismissible"><p>Child Theme files cann\'t modified successfully.</p></div>');
				}

				},        
			});
		}
		else{
			alert('<?php _e('You have not permission to upload new screenshot.', 'theme-editor');?>');
		}
	}));
		
	jQuery(document).on('click', '.download-theme', function() {
		var data_attr = jQuery(this).attr('data-attr');
		if(data_attr  == 'Yes'){
			var theme_name = jQuery('#theme_name').val();
			mk_nonce ='<?php echo wp_create_nonce( 'mk-fd-nonce' );?>';
			window.location.href="admin-post.php?action=mk_theme_editor_download_te_theme&theme_name="+theme_name+"&_wpnonce="+mk_nonce;
		}
		else {
			alert('<?php _e('You have not permission to Export Child Theme.', 'theme-editor');?>');
		}
	});
});
</script>