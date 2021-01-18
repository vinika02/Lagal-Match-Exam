<?php if ( !defined( 'ABSPATH' ) ) exit;
$current_user = wp_get_current_user();
$cuser =  $current_user->user_login;
$crole =  $current_user->roles[0];
$ctd = $this->ms_theme_directory('child');
$ctpd = $this->ms_theme_directory('parnt');
$nonce = wp_create_nonce( 'ms_theme_editor' );
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
if(isset($ac_opt['ms_user_deleted_image']) && is_array($ac_opt['ms_user_deleted_image']) && in_array($cuser, $ac_opt['ms_user_deleted_image'])){
	$isc_permission	= 'Yes';
}
else if(isset($ac_opt['ms_userrole_deleted_image']) && is_array($ac_opt['ms_userrole_deleted_image']) && in_array($crole, $ac_opt['ms_userrole_deleted_image'])){
	$count = 0;
	$ct_pm = ms_child_theme_permission();
	foreach($ct_pm as $value){
		if(isset($ac_opt[$value]) && is_array($ac_opt[$value]) && in_array($cuser, $ac_opt[$value])){
			$count++;
		}
	}
	if($count==0){
		$dsc_permission	= 'Yes';
	}
	else{
		$dsc_permission	= 'Yes';
	}
}
else{
	$dsc_permission	= 'Yes';
}
?>
<div class="msFormRow padtop0">
	<label class="mslabelHeading"><span class="labelHeadingText"><?php _e('Upload New Child Theme Image', 'theme-editor');?></span></label>
	<div class=""> 
		<form id="photouploads_ct" method="post" enctype="multipart/form-data">
			<input type="hidden" id="_wpnonce" name="_wpnonce" value="<?php echo $nonce;?>"/>
			<input id="webphotos" class="msFormInput" name="webphotos" value="" type="file" required>
			<p class="mbot0">
				<input data_attr="<?php echo $isc_permission;?>" class="ms_submit button ms_withInputBtn button-primary" id="ms_theme_screenshot_submit imageupload" name="ms_theme_screenshot_submit" value="Upload" type="submit">
			</p>
			<input type="hidden" name="action" value="webphoto_upload"/>
			<input type="hidden" name="ctd" value="<?php echo $ctd;?>"/>
			<input type="hidden" name="ctpd" value="<?php echo $ctpd;?>"/>
		</form>
		<div class="percen"></div>
	</div>
</div>
<div class="ms_delete_section" style="display:none">
	<div class="ms_image_select">
		<input type="checkbox" id="ms_image_select" name="ms_image_select" class="ms_deleted_img"> 
		<input data_attr="<?php echo $dsc_permission;?>" class="ms_image_btn button-primary" id="ms_image_btn" name="ms_image_btn" type="button" value="Delete Selected Images">
		<span class="ms_child_border_bottom"></span>
	</div>
<?php
$image_count = 0;
function ms_image($path,$theme_path,$check_name,$image_count ){
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
					if($ftype == 'jpg'||$ftype == 'jpeg'||$ftype == 'png'||$ftype == 'gif')
					{
						$image_count++;
						$ctheme_path_root = get_theme_root();
						$ctheme_path_root_uri = get_theme_root_uri();
						//echo MS_THEME_EDITOR_URL;
						$download_icon = MS_THEME_EDITOR_URL.'includes/assests/image/download.png';?>
						
						<div class="ms_image_section ms_four_col">
							<div class="ms_img_boxInner">
								<div class="ms_img_box">
									<img src="<?php echo str_replace($ctheme_path_root,$ctheme_path_root_uri,$full_path)?>" data_dir="<?php echo $full_path?>"/>
									<div class="ms_abs">
										<div class="ms_tbl">
											<div class="ms_mdl">
												<div class="ms_container">
													<div class="ms_close_btn">&times;</div>
													<img src="">
												</div>
											</div>
										</div>
									</div>
								</div>
								<div class="ms_img_desc">
									<label class="ms-checkboxFiles">
										<input class="ms_checkbox"  name="<?php echo $check_name;?>[]" value="<?php echo $full_path;?>" type="checkbox"><?php echo $new_file_name;?>
									</label>
									<label>
										<span>
											<a href="<?php echo str_replace($ctheme_path_root,$ctheme_path_root_uri,$full_path)?>" download>
											<img data_count="<?php echo $image_count;?>" src="<?php echo $download_icon;?>"> 
												<span class="ms_txt_dwld"><?php _e('Download', 'theme-editor');?></span>
											</a>
										</span>
									</label>
								</div>
							</div>
						</div>
					<?php
					}
				} else if(is_dir("$path/$file")){
					// Recursively call the function if directories found
					ms_image("$path/$file",$theme_path,$check_name,$image_count);
				}
			}
		} 
	}
}
	$ctheme_path = get_theme_root().'/'.$ctd;
	$check_name ='ms_file_child';
	echo '<div class="ms_theme_iamge_section">';
	echo ms_image($ctheme_path,$ctheme_path,$check_name,$image_count);
	echo '</div>';?>
	<div class="ms_image_select">
		<span class="ms_child_border_top"></span>
		<input type="checkbox" id="ms_image_select" name="ms_image_select" class="ms_deleted_img"> 
		<input data_attr="<?php echo $dsc_permission;?>" class="ms_image_btn button-primary" id="ms_image_btn" name="ms_image_btn" type="button" value="Delete Selected Images">
	</div>
</div>
<script>
	jQuery(".ms_img_box img").click(function(){
		jQuery('.ms_abs').show();
		var src = jQuery(this).attr('src');
		jQuery('.ms_abs img').attr('src',src);
	});
	jQuery('.ms_close_btn').click(function(){
		jQuery('.ms_abs').hide();
	});
	jQuery(document).ready(function() {
		// all selected
	var ms_img_count = jQuery('.ms_img_desc:last-child img').attr('data_count');
		
	if(ms_img_count>0)
	{
		jQuery('.ms_delete_section').show();
	}
	else
	{
		jQuery('.ms_delete_section').hide();
	}
		// all selected
		jQuery(".ms_deleted_img").change(function(){
		if (this.checked) {
			jQuery(".ms_deleted_img").prop("checked",true);
			jQuery(".ms_delete_section .ms_checkbox").each(function(index,value) {
			this.checked = true;			
			});
		} 
		else 
		{
			jQuery(".ms_deleted_img").prop("checked",false);
			jQuery(".ms_delete_section .ms_checkbox").each(function(index,value) {
			this.checked = false;  
			});
		}
	});
	jQuery(".ms_delete_section .ms_checkbox").click(function ()
	{
		if (jQuery(this).is(":checked")) {
			var isAllChecked = 0;
			jQuery(".ms_delete_section .ms_checkbox").each(function(index,value) {
			if (!this.checked){
				isAllChecked = 1;
			}
		});
		if (isAllChecked == 0) {
		jQuery(".ms_deleted_img").prop("checked", true);
			}
		}
		else{
			jQuery(".ms_deleted_img").prop("checked", false);
		}
	});
	jQuery(".ms_image_btn").click(function ()
	{
		var data_attr = jQuery(this).attr('data_attr');
		if(data_attr =='Yes')
		{
			var images_array = [];
			jQuery(".ms_delete_section .ms_checkbox").each(function(index,value) 
			{
				if(this.checked){
					images_array.push(jQuery(this).val());
				}
			});
			if(images_array.length != 0 ){
				var msgbox = confirm("<?php _e('Are You sure to delete select images?', 'theme-editor');?>");
				if (msgbox) {
					jQuery.ajax({
					type : "post",
					url : '<?php echo admin_url( 'admin-ajax.php') ?>',
					data : {
					action: "mk_theme_editor_delete_images",
					images_array:images_array,
					ctd:'<?php echo $ctd;?>',
					ctpd:'<?php echo $ctpd;?>',
					_wpnonce:'<?php echo $nonce;?>',
					},
					success: function(response) {
						window.location.href="admin.php?page=ms_child_theme_editor&tab=ms_delete_image";
					}
					});
				}
			}
			else{
				alert('<?php _e('Please select atleast one image', 'theme-editor');?>');
			}
		}
		else{
			alert('<?php _e('You have not permission to delete images', 'theme-editor');?>');
		}
	});
	jQuery("#photouploads_ct").on('submit',(function(e) {
		e.preventDefault();
		var data_attr = jQuery('.imageupload').attr('data_attr');
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
							jQuery('.percen').html('<div class="myprogress progresss" style="width:0%"></div><span class="precent_count">'+ percentComplete+'% Uploaded</span>');
							jQuery('.progresss').css('width',percentComplete+'%');
						}
					}, false);
					return xhr;
				},
				success: function(data){
					alert('<?php _e('Image uploaded successfully!', 'theme-editor');?>');  
					window.location.href="admin.php?page=ms_child_theme_editor&tab=ms_delete_image";
				},        
			});
		}
		else {
			alert('<?php _e('You have not permission to upload new images', 'theme-editor');?>');
		}
	}));
});
</script>