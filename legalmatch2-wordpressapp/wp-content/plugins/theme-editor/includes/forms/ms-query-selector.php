<?php  if ( !defined( 'ABSPATH' ) ) exit;
$ctcpage = apply_filters( 'chld_thm_cfg_admin_page', CHLD_THM_CFG_MENU );	
?>
<div id="query_selector_options_panel" class="ms-option-panel <?php $this->maybe_disable(); echo 'query_selector_options' == $active_tab ? ' ctc-option-panel-active' : ''; ?>">
	<div class="import_sucess_msg"></div>
	<div class="ui-widget">
		<input id="ctc_rule_menu" style="border:none;height:0px;width:0px;padding:0px"/>
		<div id="ctc_status_rules" style="float:right"></div>
	</div>
	<p class="howto"><?php _e( 'To find and edit specific selectors within @media query blocks, first choose the query, then the selector. Use the "base" query to edit all other selectors.', 'te-editor' ); ?></p>
	<form id="query_selector_form" method="post" action="?page=<?php echo $ctcpage; ?>">
		<?php wp_nonce_field( apply_filters( 'chld_thm_cfg_action', 'ms_update' ) ); ?>
		<div class="msFormRow clearfix" id="input_row_query">
			<div class=""> 
				<label class="mslabelHeading">
				<span class="labelHeadingText">
					<?php _e( '@media Query', 'te-editor' ); ?>
				</span> <?php _e( '( or "base" )', 'te-editor' ); ?> 
			</label>
			</div>
			<div class="" id="ms_sel_ovrd_query_selected">&nbsp;</div>
			<div class="">
				<div class="ui-widget">
					<input id="ctc_sel_ovrd_query" class="msFormInput"/>
				</div>
			</div>
		</div>
		<div class="msFormRow clearfix" id="input_row_selector">
			<div class=""> <label class="mslabelHeading"><span class="labelHeadingText">
				<?php _e( 'Selector', 'te-editor' ); ?>
				</span> </label>   
			</div>    
			<div class="" id="ctc_sel_ovrd_selector_selected">&nbsp;</div>
			<div class="">
				<div class="ui-widget">
				<input id="ctc_sel_ovrd_selector" class="msFormInput"/>
				<div id="ctc_status_qsid"></div>
				</div>
			</div>
		</div>
		<div class="ctc-selector-row clearfix ms_input_vselector" id="ms_sel_ovrd_rule_inputs_container" style="display:none">
		<div class="ms-input-row clearfix">
			<div class="ms-input-cell"><strong>
			<?php _e( 'Query/Selector Action', 'te-editor' ); ?>
			</strong></div>
			<div id="ctc_status_sel_val"></div>
			<div class="ms-input-cell ctc-button-cell ms_selector_cell" id="ms_save_query_selector_cell">
				<input type="submit" class="button button-primary ctc-save-input" id="ctc_save_query_selector" 
				name="ctc_save_query_selector" value="<?php _e( 'Save Child Values', 'te-editor' ); ?>" disabled />
				<a class="ctc-delete-input" id="ctc_delete_query_selector" href="#"><?php _e( 'Delete Child Values', 'te-editor' ); ?></a>
				<input type="hidden" id="ctc_sel_ovrd_qsid" 
				name="ctc_sel_ovrd_qsid" value="" />
			</div>
		</div>
		<div class="ms-input-row clearfix" id="ctc_sel_ovrd_rule_header" style="display:none">
			<div class="ms-input-cell"> <strong>
			<?php _e( 'Property', 'te-editor' ); ?>
			</strong> </div>
			<div class="ms-input-cell"> <strong>
			<?php _e( 'Baseline Value', 'te-editor' ); ?>
			</strong> </div>
			<div class="ms-input-cell"> <strong>
			<?php _e( 'Child Value', 'te-editor' ); ?>
			</strong> </div>
		</div>
		<div id="ctc_sel_ovrd_rule_inputs" style="display:none"> </div>
			<div class="ms-input-row clearfix" id="ctc_sel_ovrd_new_rule" style="display:none">
				<div class="ms-input-cell"> <strong>
				<?php _e( 'New Property', 'te-editor' ); ?>
				</strong> </div>
				<div class="ms-input-cell">
				<div class="ui-widget">
				<input id="ctc_new_rule_menu" />
				</div>
				</div>
			</div>
			<div id="input_row_load_order" style="display:none">
				<div id="ctc_child_load_order_container">&nbsp;</div>
			</div>
		</div>
	</form>
</div>
<div class="ctc-rule-value-input-container clearfix" id="ctc_rule_value_inputs" style="display:none"></div>