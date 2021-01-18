<?php  if ( !defined( 'ABSPATH' ) ) exit;
$ctcpage = apply_filters( 'chld_thm_cfg_admin_page', CHLD_THM_CFG_MENU );
?>
<div id="import_options_panel" 
class="ms-option-panel <?php $this->maybe_disable(); echo 'import_options' == $active_tab ? ' ctc-option-panel-active' : ''; ?>">
<div class="import_sucess_msg"></div>
<form id="ms_import_form" method="post" action=""><!-- ?page=<?php echo $ctcpage; ?>" -->
<?php wp_nonce_field( apply_filters( 'chld_thm_cfg_action', 'ms_update' ) ); ?>
<div class="msFormRow last_msFormRow padtop0 padbot0 clearfix" id="ms_child_imports_row">
<div class="">
<label class="mslabelHeading"><span class="labelHeadingText">
<?php _e( 'Additional Linked Stylesheets', 'te-editor' ); ?>
</span></label>
<p><?php _e( 'Use <code>@import url( [path] );</code> to link additional stylesheets. This Plugin uses the <code>@import</code> keyword to identify them and convert them to <code>&lt;link&gt;</code> tags. <strong>Example:</strong>');?></p> 
<p><code>@import url(http://fonts.googleapis.com/css?family=Oswald);</code></p>
</div>
<div class="">
<textarea id="ms_child_imports" name="ms_child_imports" wrap="off"><?php 
foreach ( $this->css()->get_prop( 'imports' ) as $import ):
echo esc_textarea( $import . ';' . LF );
endforeach; 
?></textarea>
<div class="ms-textarea-button-cell" id="ms_save_imports_cell">
<input type="button" class="ctc-save-input" id="ms_save_imports" 
name="ms_save_imports" value="<?php _e( 'Save', 'te-editor' ); ?>"  disabled />
</div>
</div>
</div>
</form>
</div>