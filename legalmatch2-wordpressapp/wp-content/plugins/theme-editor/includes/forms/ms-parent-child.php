<?php
if ( !defined( 'ABSPATH' ) )exit;
// Parent/Child Panel

$current_user = wp_get_current_user();
//print_r(get_currentuserinfo());
$cuser =  $current_user->user_login;
$crole =  $current_user->roles[0];
?>
<input type="hidden" value="" id="testname"/>
<div id="parent_child_options_panel" class="ms-option-panel <?php echo 'parent_child_options' == $active_tab ? ' ms-option-panel-active' : ''; ?>">

    <form id="ms_parent_child_form"  method="post" action="">

        <?php if ( $this->ctc()->is_theme() ): ?>

        <?php   // theme inputs 
    wp_nonce_field( 'ms_update' ); 
  
    
?>
<input type="hidden" name="ms_theme_child_analysis" value=""/>

        <div class="msFormRow" id="input_row_child">                      
            <label class="mslabelHeading">
        <span class="ctc-step ctc-step-number labelHeadingNumber">1</span>
		 <span class="labelHeadingText">
			 <?php _e( 'Select an action:', 'te-editor' ); ?>
         </span></label>
         
			<div class="ms_theme_editor">
			<select id="ms_theme_editor_action" class="msFormInput" name="ctc_child_type">
			  <option value="new" <?php echo ( 'new' == $this->ctc()->childtype ? 'selected' : '' ); ?> />Create a new Child Theme</option>
			  <?php if ( count( $this->ctc()->themes[ 'child' ] ) ): ?>
			  <option value="existing" <?php echo ( 'new' != $this->ctc()->childtype ? 'selected' : '' ); ?>>Configure an existing Child Theme</option>
			  <option value="duplicate">Duplicate an existing Child Theme</option>
			  <!--option value="reset">Reset an existing Child Theme</option-->
			     <?php endif; ?>
			</select>
			</div>
      </div>
      
      
      
	  
        <div class="msFormRow" id="input_row_new_theme_option" style="display:block">          
            
            <label class="mslabelHeading">
        <span class="ctc-step ctc-step-number labelHeadingNumber">2</span>
		<span class="labelHeadingText"> 
			<?php _e( 'Select a Parent Theme:', 'te-editor' ); ?>
        </span></label>
        
            <div class="padbot30 mbot30 borderbot">
                <?php $this->render_theme_menu( 'parnt', $this->ctc()->get_current_parent() ); ?>
            </div>
            
            <div class="ms-div">                
                 <label class="mslabelHeading">
                 <span class="ctc-step ctc-step-number labelHeadingNumber">3</span>
                 <?php _e( 'Analyze Parent Theme', 'te-editor' ); ?></label> 
                
                 <div class="msNoteText"> 
         <span class="ctc-analyze-howto">               
                <span class="howto">
                    <?php _e( 'Click "Analyze" to determine stylesheet dependencies and other potential issues.' ); ?>
               </span></span> </div>
           
            </div>
            
				<?php 
				if(isset($ac_opt['ms_user_create_new_child']) && is_array($ac_opt['ms_user_create_new_child']) && in_array($cuser, $ac_opt['ms_user_create_new_child']))
				{
					?>
					<input type="button" class="button button-primary ctc-analyze-theme" value="<?php _e( 'Analyze', 'te-editor' ); ?>"/>
					<?php
				}
				else if(isset($ac_opt['ms_userrole_create_new_child']) && is_array($ac_opt['ms_userrole_create_new_child']) && in_array($crole, $ac_opt['ms_userrole_create_new_child']))
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
					
					if($count==0)
					{
						$configure_permission	= 'Yes';
						?>
						<input type="button" class="button button-primary ctc-analyze-theme" value="<?php _e( 'Analyze', 'te-editor' ); ?>"/>
					<?php 
					}
					else
					{
						$configure_permission	= 'Yes';
						?>
							<input type="button" class="button button-primary ctc-analyze-theme" value="<?php _e( 'Analyze', 'te-editor' ); ?>"/>
					<?php 
					}
				}
				else
				{
					?>
					<input type="button" class="button button-primary ctc-analyze-theme" value="<?php _e( 'Analyze', 'te-editor' ); ?>"/>
					<?php 
				}
				?>            
            <div class="ctc-clear">&nbsp;</div>
            <div class="ctc-analysis" id="parnt_analysis_notice">&nbsp;</div>
        </div>
        <?php if ( count( $this->ctc()->themes[ 'child' ] ) ): ?>
        <div class="msFormRow" id="input_row_existing_theme_option" style="display:block">
             <label class="mslabelHeading">
        <span class="ctc-step ctc-step-number labelHeadingNumber">2</span>
		<span class="labelHeadingText"><?php _e( 'Select a Child Theme:', 'te-editor' ); ?></span></label>
        
            <div class="padbot30 mbot30 borderbot">
                <?php $this->render_theme_menu( 'child', $this->ctc()->get_current_child() ); ?>
                <?php
                //Configuration				
				if(isset($ac_opt['ms_user_configure_child_theme']) && is_array($ac_opt['ms_user_configure_child_theme']) && in_array($cuser, $ac_opt['ms_user_configure_child_theme']))
				{
					$configure_permission = 'Yes';				 
				}
				else if(isset($ac_opt['ms_userrole_configure_child_theme']) && is_array($ac_opt['ms_userrole_configure_child_theme']) && in_array($crole, $ac_opt['ms_userrole_configure_child_theme']))
				{
					 //$configure_permission = 'yes';	
					$count = 0;
					$ct_pm = ms_child_theme_permission();
					foreach($ct_pm as $value)
					{
						if(isset($ac_opt[$value]) && is_array($ac_opt[$value]) && in_array($cuser, $ac_opt[$value]))
						{
							$count++;
						}
					}
					
					if($count==0)
					{
						$configure_permission	= 'Yes';
					}
					else
					{
						$configure_permission	= 'Yes';
					}
				}
				else
				{
					$configure_permission = 'Yes';
				}
				
				//duplicate
				if(isset($ac_opt['ms_user_duplicate_child_theme']) && is_array($ac_opt['ms_user_duplicate_child_theme']) && in_array($cuser, $ac_opt['ms_user_duplicate_child_theme']))
				{
					$duplicate_permission = 'Yes';				 
				}
				else if(isset($ac_opt['ms_userrole_duplicate_child_theme']) && is_array($ac_opt['ms_userrole_duplicate_child_theme']) && in_array($crole, $ac_opt['ms_userrole_duplicate_child_theme']))
				{
					 //$duplicate_permission = 'yes';	
					 
					 //$isc_permission	= 'Yes';
					$count = 0;
					$ct_pm = ms_child_theme_permission();
					foreach($ct_pm as $value)
					{
						if(isset($ac_opt[$value]) && is_array($ac_opt[$value]) && in_array($cuser, $ac_opt[$value]))
						{
							$count++;
						}
					}
					
					if($count==0)
					{
						$duplicate_permission	= 'Yes';
					}
					else
					{
						$duplicate_permission	= 'Yes';
					}
				}
				else
				{
					$duplicate_permission = 'Yes';
				}
				?>
				
            </div>
            
            
            
            <div class="">
             <label class="mslabelHeading">
        <span class="ctc-step ctc-step-number labelHeadingNumber">3</span>
		 <span class="labelHeadingText"><?php _e( 'Analyze Child Theme', 'te-editor' ); ?></span></label> 
                <div class="msNoteText"> 
         <span class="ctc-analyze-howto">              
                <span class="howto">
                    <?php _e( 'Click "Analyze" to determine stylesheet dependencies and other potential issues.', 'te-editor' ); ?>
               </span>
                </span>
         </div>
               <input type="button" data_configuartion="<?php echo $configure_permission;?>" data_duplication="<?php echo $duplicate_permission;?>" class="button button-primary ctc-analyze-theme" value="<?php _e( 'Analyze', 'te-editor' ); ?>"/>
            </div>
            
            <div class="ctc-clear">&nbsp;</div>
            <div class="ctc-analysis" id="child_analysis_notice">&nbsp;</div>
        </div>
        <?php 
    endif; ?>
        <div class="msFormRow" id="input_row_new_theme_slug" style="display:none">
                        
             <label class="mslabelHeading">
        <span class="ctc-step ctc-step-number labelHeadingNumber">4</span>
		<span class="labelHeadingText"><?php _e( 'Name the new theme directory:', 'te-editor' ); ?></span></label>
        
        
            <div class="">
                <input class="ctc_text ctc-themeonly msFormInput" id="ctc_child_template" name="ctc_child_template" type="text" placeholder="<?php _e( 'Directory Name', 'te-editor' ); ?>" autocomplete="off"  />
            </div>
            <div class="msNoteText"> 
         <span class="howto">
         <strong>
          <?php _e( 'NOTE:', 'te-editor' ); ?>
        </strong>
        <?php _e( 'This is NOT the name of the Child Theme. You can customize the name, description, etc. in step 7, below.', 'te-editor' ); ?>
        
        </span>            
            </div>            
        </div>
        
        
        <div class="msFormRow" id="input_row_theme_slug" style="display:none">
                      
             <label class="mslabelHeading">
        <span class="ctc-step ctc-step-number labelHeadingNumber">4</span>
		<span class="labelHeadingText"><?php _e( 'Verify Child Theme directory:', 'te-editor' ); ?></span></label>
            
            <div class="msCodeText">
            <code id="theme_slug_container"></code>           
            </div>
            
            <div class="msNoteText">  <span class="howto">
                    <?php _e( 'For verification only (you cannot modify the directory of an existing Child Theme).', 'te-editor' ); ?>
                </span>
            </div>
        </div>
        
        
        <?php
        $handling = $this->ctc()->get( 'handling' );
        $ignoreparnt = $this->ctc()->get( 'ignoreparnt' );
        $enqueue = $this->ctc()->get( 'enqueue' );
        $this->ctc()->debug( 'handling: ' . $handling . ' ignore: ' . $ignoreparnt . ' enqueue: ' . $enqueue, 'parent-child.php' );
        ?>
        
        
        <div class="msFormRow" id="input_row_stylesheet_handling_container" style="display:none">
          
            <label class="mslabelHeading" id="input_row_stylesheet_handling">
            <span class="ctc-step ctc-step-number labelHeadingNumber">5</span>
            <span class="labelHeadingText"><?php _e( 'Select where to save new styles:', 'te-editor' ); ?></span>
            </label>
                
            <div class="msFormRowInner sep">
                <div id="child_handling_notice"></div>
                <label class="msLabelSmHeading">
          <input class="ctc_radio ctc-themeonly" id="ctc_handling_primary" name="handling" type="radio" 
                value="primary" <?php checked( $handling, 'primary' ); ?> autocomplete="off" />         
          <?php _e( "Primary Stylesheet (style.css)", 'te-editor' ); ?>
          </label>
          
          <p class="howto indent sep">
            <?php _e( 'Save new custom styles directly to the Child Theme primary stylesheet, replacing the existing values. The primary stylesheet will load in the order set by the theme.', 'te-editor' ); ?>
          </p>
        
            </div>
            
            
            
            <div class="msFormRowInner">
             <label class="msLabelSmHeading">
          <input class="ctc_radio ctc-themeonly" id="ctc_handling_separate" name="handling" type="radio" 
                value="separate" <?php checked( $handling, 'separate' ); ?>  autocomplete="off" />
          
          <?php _e( 'Separate Stylesheet', 'te-editor' ); ?>
          </label> 
          <p class="howto indent">
            <?php _e( 'Save new custom styles to a separate stylesheet and combine any existing child theme styles with the parent to form baseline. Select this option if you want to preserve the existing child theme styles instead of overwriting them. This option also allows you to customize stylesheets that load after the primary stylesheet.', 'te-editor' ); ?>
          </p>
         
            </div>
            
        </div>
        
        
        <div class="msFormRow" id="input_row_parent_handling_container" style="display:none">
                        
       <label class="mslabelHeading">
        <span class="ctc-step ctc-step-number labelHeadingNumber">6</span>
		<span class="labelHeadingText"><?php _e( 'Select Parent Theme stylesheet handling:', 'te-editor' ); ?></span>
        </label>
            
            <div class="msFormRowInner sep">
                <div id="parent_handling_notice"></div>
                <?php // deprecated enqueue values
        if ( 'both' == $enqueue || 'child' == $enqueue ): 
            $enqueue = 'enqueue'; 
        endif; ?>
                <label class="msLabelSmHeading">
          <input class="ctc_checkbox ctc-themeonly" id="ctc_enqueue_enqueue" name="enqueue" type="radio" 
                value="enqueue" <?php checked( $enqueue, 'enqueue' ); ?> autocomplete="off" />       
          <?php _e( 'Use the WordPress style queue.', 'te-editor' ); ?>          
          </label>
            

                <p class="howto indent sep">
                    <?php _e( "Let the Configurator determine the appropriate actions and dependencies and update the functions file automatically.", 'te-editor' ); ?>
                </p>
                
                </div>
                
                <div class="msFormRowInner sep">
                <label class="msLabelSmHeading">
          <input class="ctc_checkbox ctc-themeonly" id="ctc_enqueue_import" name="enqueue" type="radio" 
                value="import" <?php checked( $enqueue, 'import' ); ?> autocomplete="off" />
       
          <?php _e( 'Use <code>@import</code> in the child theme stylesheet.', 'te-editor' ); ?>
          
          </label>
            

                <p class="howto indent sep">
                    <?php _e( "Only use this option if the parent stylesheet cannot be loaded using the WordPress style queue. Using <code>@import</code> is not recommended.", 'te-editor' ); ?>
                </p>
                </div>
                
                <div class="msFormRowInner sep">
           <label class="msLabelSmHeading">
          <input class="ctc_checkbox ctc-themeonly" id="ctc_enqueue_none" name="enqueue" type="radio" 
                value="none" <?php checked( $enqueue, 'none' ); ?> autocomplete="off" />
         
          <?php _e( 'Do not add any parent stylesheet handling.', 'te-editor' ); ?>
          
          <p class="howto indent sep">
            <?php _e( "Select this option if this theme already handles the parent theme stylesheet or if the parent theme's <code>style.css</code> file is not used for its appearance.", 'te-editor' ); ?>
          </p>
          </label>
            
            </div>
            
            <label class="mslabelHeading">
        
		<span class="labelHeadingText"> <?php _e( 'Advanced handling options', 'te-editor' ); ?>:</span>
        </label>
            
            
            <div class="msFormRowInner">
            <label class="msLabelSmHeading"><input class="ctc_checkbox ctc-themeonly" id="ctc_ignoreparnt" name="ignoreparnt" type="checkbox" 
                value="1" autocomplete="off" />
          <?php _e( 'Ignore parent theme stylesheets.', 'te-editor' ); ?></label>
          <p class="howto indent"><?php _e( 'Do not load or parse the parent theme styles. Only use this option if the Child Theme uses a Framework like Genesis and uses <em>only child theme stylesheets</em> for its appearance.', 'te-editor' ); ?></p>
            </div>
            
            <div id="ctc_repairheader_container" class="msFormRowInner" style="display:none">
                
                <div class="sep">
                    <label class="msLabelSmHeading"><input class="ctc_checkbox ctc-themeonly" id="ctc_repairheader" name="repairheader" type="checkbox" 
                value="1" autocomplete="off" />
<?php _e( 'Repair the header template in the child theme.', 'te-editor' ); ?>
<p class="howto indent"><?php _e( 'Let the Configurator (try to) resolve any stylesheet issues listed above. This can fix many, but not all, common problems.', 'te-editor' ); ?></p></label>
                </div>
            </div>
            
            
            <div id="ctc_dependencies_container" class="msFormRowInner" style="display:none">
                
                <div class="sep">
                     <label class="msLabelSmHeading">
                        <?php _e( 'Remove stylesheet dependencies', 'te-editor' ); ?>
                    </label>
                    <p class="howto indent">
                        <?php _e( 'By default, the order of stylesheets that load prior to the primary stylesheet is preserved by treating them as dependencies. In some cases, stylesheets are detected in the preview that are not used site-wide. If necessary, dependency can be removed for specific stylesheets below.', 'te-editor' ); ?>
                    </p>
                    <div id="ctc_dependencies"></div>
                </div>
            </div>
            <?php //do_action( 'chld_thm_cfg_enqueue_options' ); // removed for ctc 2.0 ?>
        </div>
        
        
        
        
        <div class="msFormRow padbot0" id="ctc_child_header_parameters" style="display:none">
                        
        <label class="mslabelHeading">
        <span class="ctc-step ctc-step-number labelHeadingNumber">7</span>
		<span class="labelHeadingText"><?php _e( 'Customize the Child Theme Name, Description, Author, Version, etc.:', 'te-editor' ); ?></span>
        </label>
           
            <div class="inputFormWrap" id="ms_theme_attributes_content">
                <div class="ms-inputGroup-row" id="input_row_child_name">
                                       
                    <div class="msCol30">
                        <strong>
                            <?php _e( 'Child Theme Name', 'te-editor' ); ?>
                        </strong>
                    </div>
                    
                    <div class="msCol70">
                        <input class="ctc_text ctc-themeonly msFormInput" id="child_name" name="child_name" type="text" value="<?php echo esc_attr( $this->ctc()->get( 'child_name' ) ); ?>" placeholder="<?php _e( 'Theme Name', 'te-editor' ); ?>" autocomplete="off" />
                    </div>
                </div>
                
                
                <div class="ms-inputGroup-row" id="input_row_child_website">
                    <div class="msCol30">
                        <strong>
                            <?php _e( 'Theme Website', 'te-editor' ); ?>
                        </strong>
                    </div>
                    <div class="msCol70">
                        <input class="ctc_text ctc-themeonly msFormInput" id="child_theme_uri" name="child_theme_uri" type="text" value="<?php echo esc_attr( $this->ctc()->get( 'themeuri' ) ); ?>" placeholder="<?php _e( 'Theme Website', 'te-editor' ); ?>" autocomplete="off" />
                    </div>
                </div>
                
                
                <div class="ms-inputGroup-row" id="input_row_child_author">
                    <div class="msCol30">
                        <strong>
                            <?php _e( 'Author', 'te-editor' ); ?>
                        </strong>
                    </div>
                    <div class="msCol70">
                        <input class="ctc_text msFormInput" id="child_author" name="child_author" type="text" value="<?php echo esc_attr( $this->ctc()->get( 'author' ) ); ?>" placeholder="<?php _e( 'Author', 'te-editor' ); ?>" autocomplete="off"/>
                    </div>
                </div>
                
                
                <div class="ms-inputGroup-row" id="input_row_child_authoruri">
                    <div class="msCol30">
                        <strong>
                            <?php _e( 'Author Website', 'te-editor' ); ?>
                        </strong>
                    </div>
                    <div class="msCol70">
                        <input class="ctc_text ctc-themeonly msFormInput" id="child_author_uri" name="child_author_uri" type="text" value="<?php echo esc_attr( $this->ctc()->get( 'authoruri' ) ); ?>" placeholder="<?php _e( 'Author Website', 'te-editor' ); ?>" autocomplete="off" />
                    </div>
                </div>
                
                
                <div class="ms-inputGroup-row" id="input_row_child_descr">
                    <div class="msCol30">
                        <strong>
                            <?php _e( 'Theme Description', 'te-editor' ); ?>
                        </strong>
                    </div>
                    <div class="msCol70">
                        <textarea class="ctc_text ctc-themeonly msFormInput" id="child_descr" name="child_descr" placeholder="<?php _e( 'Description', 'te-editor' ); ?>" autocomplete="off" ><?php echo esc_textarea( $this->ctc()->get( 'descr' ) ); ?></textarea>
                    </div>
                </div>
                
                
                <div class="ms-inputGroup-row" id="input_row_child_tags">
                    <div class="msCol30">
                        <strong>
                            <?php _e( 'Theme Tags', 'te-editor' ); ?>
                        </strong>
                    </div>
                    <div class="msCol70">
                        <textarea class="ctc_text ctc-themeonly msFormInput" id="child_tags" name="child_tags" placeholder="<?php _e( 'Tags', 'te-editor' ); ?>" autocomplete="off" ><?php echo esc_textarea( $this->ctc()->get( 'tags' ) ); ?></textarea>
                    </div>
                </div>
                
                
                <div class="ms-inputGroup-row" id="input_row_child_version">
                    <div class="msCol30">
                        <strong>
                            <?php _e( 'Version', 'te-editor' ); ?>
                        </strong>
                    </div>
                    <div class="msCol70">
                        <input class="ctc_text msFormInput" id="child_version" name="child_version" type="text" value="<?php echo esc_attr( $this->ctc()->get( 'version' ) ); ?>" placeholder="<?php _e( 'Version', 'te-editor' ); ?>" autocomplete="off"/>
                    </div>
                </div>
            </div>
        </div>
        <?php //if ( ! is_multisite() || ! empty( $this->ctc()->themes[ 'parnt' ][ $this->ctc()->get_current_parent() ][ 'allowed' ] ) ): ?>
        <div class="msFormRow" id="ctc_copy_theme_mods" style="display:none">
            <label for="ctc_parent_mods" class="mslabelHeading">
        <span class="ctc-step ctc-step-number labelHeadingNumber">8</span>
		<span class="labelHeadingText"> <?php _e( 'Copy Menus, Widgets and other Customizer Settings from the Parent Theme to the Child Theme:', 'te-editor' ); ?> </span>         
         </label>
                   <div class="msNoteText"> 
            <div class="howto">
                <label for="ctc_parent_mods">
          <input class="ctc_checkbox ctc-themeonly" id="ctc_parent_mods" name="ctc_parent_mods" type="checkbox" 
                value="1" />
          <strong>
          <?php _e( 'NOTE:', 'te-editor' ); ?>
          </strong>
          <?php _e( "This option replaces the Child Theme's existing Menus, Widgets and other Customizer Settings with those from the Parent Theme. You should only need to use this option the first time you configure a Child Theme.", 'te-editor' ); ?>
        </label>
            
            </div>
            </div>
            
        </div>
        <?php //endif; ?>

        <div class="msFormRow last_msFormRow padbot0" id="ctc_configure_submit" style="display:none">
            <label class="mslabelHeading">
        <span class="ctc-step ctc-step-number labelHeadingNumber">9</span>
		<span class="labelHeadingText"><?php _e( 'Click to run the Configurator:', 'te-editor' ); ?></span></label>
        
            <div class="msFormLastBtn">
                <input class="ctc_submit button button-primary" id="ctc_load_styles" name="ctc_load_styles" type="submit" value="<?php _e( 'Configure Child Theme', 'te-editor' ); ?>" disabled/>
            </div>
        </div>

        <?php
		endif;
		?>
       
    </form>
</div>

<script>
jQuery(window).on('load',function () {
	jQuery('.child_analysis_notice').html('');   
});
</script>