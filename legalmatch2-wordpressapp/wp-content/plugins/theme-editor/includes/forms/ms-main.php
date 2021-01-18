<?php if ( !defined( 'ABSPATH' ) ) exit;
$css = $this->css();
$ac_opt = get_option('theme_editor_child_theme_permission');
if(empty($ac_opt) && !is_array($ac_opt)){
	$ac_opt = array();
}?>
<div class="wrap msEditorChildTheme" id="theme_editor_main">
	<div class="msEditorWhiteWrap">
		<div class="clearfix">
			<?php 
			if($childname = $this->css()->get_prop('child_name')){
			?>
			<div class="ms_current_theme">
				<h1 class="headingTitle">
					<span class="headingicon">
						<img src="<?php echo plugins_url( '../assests/image/mseditor-permission-icon.png', __FILE__ );?>"/> 
					</span> 
					<span class="headingTxt">
						<?php echo __( 'Current Analysis Theme:', 'te-editor' );?> <span class="headingSmTxt"><?php echo $childname; ?></span> 
					</span>
				</h1> 
			</div>
			<?php   
			}
			?>
		</div>
		<div id="ms_error_notice">
			<?php $this->render_settings_errors(); ?>
		</div>
		<?php include ( MS_THEME_EDITOR_DIR . '/includes/forms/ms-tabs.php' ); ?>
		<div id="ms_option_panel_wrapper" style="position:relative">
			<div class="ms-option-panel-container">
				<?php 
				include ( MS_THEME_EDITOR_DIR . '/includes/forms/ms-parent-child.php' );
				if ( $this->enqueue_is_set()):
					include ( MS_THEME_EDITOR_DIR . '/includes/forms/ms-query-selector.php' );
					if ( $this->ctc()->is_theme()) 
					include ( MS_THEME_EDITOR_DIR . '/includes/forms/ms-webfonts.php' ); ?>
					<div id="ms_view_child_options_panel" class="ms-option-panel" <?php echo 'ms_view_child_options' == $active_tab ? ' ms-option-panel-active' : ''; ?>>
						<?php include ( MS_THEME_EDITOR_DIR . '/includes/forms/ms_child_style.php' ); ?>
					</div>
					<div id="ms_view_parnt_options_panel" class="ms-option-panel" <?php echo 'ms_view_parnt_options' == $active_tab ? ' ms-option-panel-active' : ''; ?>>
						<?php include ( MS_THEME_EDITOR_DIR . '/includes/forms/ms_parent_style.php' );?>
					</div>
					<?php if ( $this->ctc()->is_theme()){ ?>
					<div id="ms_file_options_panel" class="ms-option-panel <?php echo 'ms_file_options' == $active_tab ? ' ms-option-panel-active' : ''; ?>">
						<?php include ( MS_THEME_EDITOR_DIR . '/includes/forms/ms_files.php' ); ?>
					</div>
					<?php } ?>
					<div id="ms_file_image_options_panel" class="ms-option-panel <?php echo 'ms_delete_image' == $active_tab ? ' ms-option-panel-active' : ''; ?>">
						<?php include ( MS_THEME_EDITOR_DIR . '/includes/forms/ms_child_thme_image.php' );?>
					</div>
				<?php
				endif;
				if ( $this->enqueue_is_set()):
					do_action( 'chld_thm_cfg_panels', $active_tab );
				endif;
				?>
				<div id="ms_create_new_file_options_panel" class="ms-option-panel">
				<?php $download_icon = MS_THEME_EDITOR_URL.'includes/assests/image/create_file.jpg';?>
				<div class="ms_pro_section">
					<div class="msProBar">
						<span class="proTextMsg"><?php _e('Note: This is just a screenshot. Buy PRO Version for this feature.', 'theme-editor');?></span>
						<span class="buyProBtnSpan">
							<a href="https://themeeditor.pro/product/theme-editor/" class="" target="_blank"><?php _e('BUY PRO', 'theme-editor');?></a>
						</span>
					</div>
					<img style="width:100%;" src="<?php echo $download_icon;?>">
				</div>
				</div>
				<div id="ms_preview_theme_panel" class="ms-option-panel">
					<?php $download_icon = MS_THEME_EDITOR_URL.'includes/assests/image/preview_tab.jpg';?>
					<div class="ms_pro_section">
						<div class="msProBar">
							<span class="proTextMsg"><?php _e('Note: This is just a screenshot. Buy PRO Version for this feature.', 'theme-editor');?> </span>
							<span class="buyProBtnSpan">
								<a href="https://themeeditor.pro/product/theme-editor/" class="" target="_blank"> <?php _e('BUY PRO', 'theme-editor');?></a>
							</span>
						</div>
						<img style="width:100%;" src="<?php echo $download_icon;?>">
					</div>
				</div>
			</div>
		</div>
	</div>  
</div>