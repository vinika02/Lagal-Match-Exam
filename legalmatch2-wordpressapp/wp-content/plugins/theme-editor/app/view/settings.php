<?php if ( ! defined( 'ABSPATH' ) ) exit;
$opt = get_option('mk_te_settings_options');
$msg = isset($_GET['msg']) ? $_GET['msg'] : '';
if(isset($_POST['submit_mk_te_settings']) && wp_verify_nonce( $_POST['mk_te_nonce_field'], 'mk_te_action' )):
 $this->sava_mk_settings($_POST);
endif; ?>

<div class="wrap te-settings">
<?php $this->load_help_desk();?>
<h1 class="headingTitle"><span class="headingicon"><img src="<?php echo plugins_url( 'images/ms-editor-setting-icon.png', __FILE__ );?>"/> </span> <span class="headingTxt"><?php _e('Settings', 'te-editor');?></span></h1>

<?php if(!empty($msg) && $msg == 1):
 $this->success('Success: Settings Saved!');
elseif(!empty($msg) && $msg == 2):
 $this->error('Error: Settings Not Saved!'); 
endif; 
$cm_themes = $this->theme_controller->getcmthemes(); ?>
<div class="msEditorContentWrap">
<form action="" method="post">
<?php  wp_nonce_field( 'mk_te_action', 'mk_te_nonce_field' ); ?>
<div id="tabs" class="te_settings_tabs">
    <ul>
        <li><a href="#theme-settings"><span><?php _e('Theme Editor', 'theme-editor');?></span></a></li>
        <li><a href="#plugin-settings"><span><?php _e('Plugin Editor', 'theme-editor');?></span></a></li>
        <li><a href="#editor-settings"><span><?php _e('Code Editor', 'theme-editor');?></span></a></li>
    </ul>
    <div id="theme-settings" class="te_settings_tabs_sec">
     <?php /* Theme Settings */ ?>
        <table class="form-table trBorderTbl">
        <tbody>
        <tr>
        <th scope="row"><label for="e_d_t_e"><?php _e('Enable code editor for theme', 'theme-editor');?></label></th>
        <td><input type="radio" value="yes" name="e_d_t_e" <?php if(isset($opt['e_d_t_e']) && $opt['e_d_t_e'] == 'yes') { ?>checked="checked"<?php } ?> /><?php _e('Yes', 'theme-editor');?> <input type="radio" value="no" name="e_d_t_e" <?php if(isset($opt['e_d_t_e']) && $opt['e_d_t_e'] == 'no') { ?>checked="checked"<?php } ?> /><?php _e('No', 'theme-editor');?>
        <p class="description" id="tagline-e_d_t_e"><?php _e('This will Enable/Disable the theme editor.<br/><strong>Default: </strong>Yes', 'theme-editor');?></p></td>
        </tr>
        <tr>
        <th scope="row"><label for="e_w_d_t_e"><?php _e('Disable Default Theme Editor?', 'theme-editor');?></label></th>
        <td><input type="radio" value="yes" name="e_w_d_t_e" <?php if(isset($opt['e_w_d_t_e']) && $opt['e_w_d_t_e'] == 'yes') { ?>checked="checked"<?php } ?> /><?php _e('Yes', 'theme-editor');?> <input type="radio" value="no" name="e_w_d_t_e" <?php if(isset($opt['e_w_d_t_e']) && $opt['e_w_d_t_e'] == 'no') { ?>checked="checked"<?php } ?> /><?php _e('No', 'theme-editor');?>
        <p class="description" id="tagline-e_w_d_t_e"><?php _e('This will Enable/Disable the Default theme editor.<br/><strong>Default: </strong>Yes', 'theme-editor');?></p></td>
        </tr>
        </tbody>
        </table> 
    </div>
    <div id="plugin-settings" class="te_settings_tabs_sec">
       <?php /* Plugin Settings */ ?>
        <table class="form-table trBorderTbl">
        <tbody>
        <tr>
        <th scope="row"><label for="e_d_p_e"><?php _e('Enable code editor for plugin', 'theme-editor');?></label></th>
        <td><input type="radio" value="yes" name="e_d_p_e" <?php if(isset($opt['e_d_p_e']) && $opt['e_d_p_e'] == 'yes') { ?>checked="checked"<?php } ?> /><?php _e('Yes', 'theme-editor');?> <input type="radio" value="no" name="e_d_p_e" <?php if(isset($opt['e_d_p_e']) && $opt['e_d_p_e'] == 'no') { ?>checked="checked"<?php } ?> /><?php _e('No', 'theme-editor');?>
        <p class="description" id="tagline-e_d_p_e"><?php _e('This will Enable/Disable the plugin editor.<br/><strong>Default: </strong>Yes', 'theme-editor');?></p></td>
        </tr>
        <tr>
        <th scope="row"><label for="e_w_d_p_e"><?php _e('Disable Default Plugin Editor?', 'theme-editor');?></label></th>
        <td><input type="radio" value="yes" name="e_w_d_p_e" <?php if(isset($opt['e_w_d_p_e']) && $opt['e_w_d_p_e'] == 'yes') { ?>checked="checked"<?php } ?> /><?php _e('Yes', 'theme-editor');?> <input type="radio" value="no" name="e_w_d_p_e" <?php if(isset($opt['e_w_d_p_e']) && $opt['e_w_d_p_e'] == 'no') { ?>checked="checked"<?php } ?> /><?php _e('No', 'theme-editor');?>
        <p class="description" id="tagline-e_w_d_p_e"><?php _e('This will Enable/Disable the Default plugin editor.<br/><strong>Default: </strong>Yes', 'theme-editor');?></p></td>
        </tr>
        </tbody>
        </table>
    </div>
    <div id="editor-settings" class="te_settings_tabs_sec">
    <?php /* Code Editor */?>
    <table class="form-table trBorderTbl">
    <tbody>
    <tr>
    <th scope="row"><label for="code_editor_theme"><?php _e('Code Editor Theme', 'theme-editor');?></label></th>
    <td>
    <select name="code_editor_theme" class="msStyledSelect msEditorTxtb" id="code_editor_theme">
      <?php foreach($cm_themes as $key => $cm_theme):
      if(isset($opt['code_editor_theme']) && $opt['code_editor_theme'] == $cm_theme) {  ?>
      <option value="<?php echo $cm_theme; ?>" selected="selected"><?php echo ucwords(str_replace('-',' ',$cm_theme)); ?></option>
      <?php } else { ?>
      <option value="<?php echo $cm_theme; ?>"><?php echo ucwords(str_replace('-',' ',$cm_theme)); ?></option>
      <?php } ?>
      <?php endforeach;?>
    </select>
    <p class="description" id="tagline-code_editor_theme"><?php _e('Allows you to select theme for theme editor.<br/><strong>Default: </strong>Cobalt', 'theme-editor');?></p></td>
    </td>
    </tr>
    </tbody>
    </table>
    </div>
</div>

<div class="btnDv"><input name="submit_mk_te_settings" id="submit" class="button button-primary msEditorBtn" value="Save Changes" type="submit"></div>
</form>
</div>
</div>