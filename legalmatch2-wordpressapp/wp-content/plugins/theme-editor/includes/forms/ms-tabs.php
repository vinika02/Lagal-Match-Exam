<?php  if ( !defined( 'ABSPATH' ) ) exit;
$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'parent_child_options'; 
$current_user = wp_get_current_user();
$cuser =  $current_user->user_login;
$crole =  $current_user->roles[0];
?>
<div class="ms_tabs">
	<a id="parent_child_options" href="" 
	class="nav-tab <?php echo 'parent_child_options' == $active_tab ? ' nav-tab-active' : ''; ?>">
	<?php _e( 'Parent/ Child', 'te-editor' ); ?>
	</a>
	<?php	
	if(isset($ac_opt['ms_user_query_selector']) && is_array($ac_opt['ms_user_query_selector']) && in_array($cuser, $ac_opt['ms_user_query_selector']))
	{?>
		<a id="query_selector_options" href="" 
		class="nav-tab <?php $this->maybe_disable(); echo 'query_selector_options' == $active_tab ? ' nav-tab-active' : ''; ?>">
		<?php _e( 'Query/ Selector', 'te-editor' ); ?>
		</a>
		<?php		
	}
	else if(isset($ac_opt['ms_userrole_query_selector']) && is_array($ac_opt['ms_userrole_query_selector']) && in_array($crole, $ac_opt['ms_userrole_query_selector']))
	{
		$count = 0;
		$ct_pm = ms_child_theme_permission();
		foreach($ct_pm as $value)
		{
			if(is_array($ac_opt[$value]) && in_array($cuser, $ac_opt[$value]))
			{
				$count++;
			}
		}		
		if($count==0)
		{?>
			<a id="query_selector_options" href="" 
			class="nav-tab <?php $this->maybe_disable(); echo 'query_selector_options' == $active_tab ? ' nav-tab-active' : ''; ?>">
			<?php _e( 'Query/ Selector', 'te-editor' ); ?>
			</a>
		<?php
		}
		else
		{?>
			<a id="query_selector_options" href="" 
			class="nav-tab <?php $this->maybe_disable(); echo 'query_selector_options' == $active_tab ? ' nav-tab-active' : ''; ?>">
			<?php _e( 'Query/ Selector', 'te-editor' ); ?>
			</a>
		<?php
		}
	}
	else
	{?>
		<a id="query_selector_options" href="" 
		class="nav-tab <?php $this->maybe_disable(); echo 'query_selector_options' == $active_tab ? ' nav-tab-active' : ''; ?>">
		<?php _e( 'Query/ Selector', 'te-editor' ); ?>
		</a>
		<?php	
	}
	//Permission
	if(isset($ac_opt['ms_user_web_font']) && is_array($ac_opt['ms_user_web_font']) && in_array($cuser, $ac_opt['ms_user_web_font']))
	{ 
		if ( $this->ctc()->is_theme() ):  ?>
		<a id="import_options" href="" 
		class="ms_three_tab nav-tab <?php $this->maybe_disable(); echo 'import_options' == $active_tab ? ' nav-tab-active' : ''; ?>">
		<?php _e( 'Web Fonts & CSS', 'te-editor' ); ?>
		</a>
		<?php endif; 
	}
	else if(isset($ac_opt['ms_userrole_web_font']) && is_array($ac_opt['ms_userrole_web_font']) && in_array($crole, $ac_opt['ms_userrole_web_font']))
	{
		$count = 0;
		$ct_pm = ms_child_theme_permission();
		foreach($ct_pm as $value)
		{
			if(is_array($ac_opt[$value]) && in_array($cuser, $ac_opt[$value]))
			{
				$count++;
			}
		}
		if($count==0)
		{
			if ( $this->ctc()->is_theme() ):  ?>
			<a id="import_options" href="" 
			class="ms_three_tab nav-tab <?php $this->maybe_disable(); echo 'import_options' == $active_tab ? ' nav-tab-active' : ''; ?>">
			<?php _e( 'Web Fonts & CSS', 'te-editor' ); ?>
			</a>
			<?php endif; 
		}
		else
		{
			if ( $this->ctc()->is_theme() ):  ?>
			<a id="import_options" href="" 
			class=" ms_three_tab nav-tab <?php $this->maybe_disable(); echo 'import_options' == $active_tab ? ' nav-tab-active' : ''; ?>">
			<?php _e( 'Web Fonts & CSS', 'te-editor' ); ?>
			</a>
			<?php endif;
		}
	}
	else
	{
		if ( $this->ctc()->is_theme() ):  ?>
		<a id="import_options" href="" 
		class=" ms_three_tab nav-tab <?php $this->maybe_disable(); echo 'import_options' == $active_tab ? ' nav-tab-active' : ''; ?>">
		<?php _e( 'Web Fonts & CSS', 'te-editor' ); ?>
		</a>
		<?php endif;	
	}
	?>
	<a id="ms_view_parnt_options" href="" 
	class="nav-tab <?php $this->maybe_disable(); echo 'view_parnt_options' == $active_tab ? ' nav-tab-active' : ''; ?>">
	<?php _e( 'Parent Styles', 'te-editor' ); ?>
	</a>
	<a id="ms_view_child_options" href="" 
	class="nav-tab <?php $this->maybe_disable(); echo 'view_child_options' == $active_tab ? ' nav-tab-active' : ''; ?>">
	<?php _e( 'Child Styles', 'te-editor' ); ?>
	</a>
	<?php if ( $this->ctc()->is_theme() ): 	?>
	<a id="ms_file_options" href="" class=" ms_six_tab nav-tab <?php $this->maybe_disable(); echo 'file_options' == $active_tab ? ' nav-tab-active' : ''; ?>">
		<?php _e( 'Theme Files', 'te-editor' ); ?>
	</a>
	<?php endif; ?>
	<?php if ( $this->ctc()->is_theme()): 	?>
	<a id="ms_file_image_options" href="" class="nav-tab <?php $this->maybe_disable();  echo 'ms_delete_image'==$active_tab ? ' nav-tab-active' : '';?>">
		<?php _e( 'View Child Images', 'te-editor' ); ?>
	</a>
	<?php endif; ?>
	<?php //if ( $this->ctc()->is_theme()): 	?>
	<a id="ms_create_new_file_options" href="" class="nav-tab <?php echo 'ms_create_new_file'== $active_tab ? ' nav-tab-active' : '';?>">
		<?php _e( 'Create New Files', 'te-editor' ); ?>
	</a>
	<?php //endif; ?>
	<?php //if ( $this->ctc()->is_theme() ): 	?>
	<a id="ms_preview_theme" href="" class="nav-tab">
		<?php _e( 'Preview Theme', 'te-editor' ); ?>
	</a>
	<?php //endif; ?>
</div>