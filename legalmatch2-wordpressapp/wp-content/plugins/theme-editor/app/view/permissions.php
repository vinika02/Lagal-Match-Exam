<?php if ( ! defined( 'ABSPATH' ) ) exit; 
?>

<style>
.msEditorWhiteWrap{
	border: 1px solid #ddd;
	background: #fff;
	padding: 30px;
}
h1.headingTitle {
	padding: 15px 0px;
	color: #404040;
	border-bottom: 1px solid #ddd;
	font-family: Arial, Helvetica, sans-serif;
	font-size: 22px;
	line-height: 30px;
	font-weight: 700;
	margin: 0;
	padding-top: 0;
	margin-bottom: 30px;
}
h1.headingTitle .headingicon {
	float: left;
	margin-right: 10px;
	display: inline-block;
}
.msProBar {
	background: #f0dddd;
	padding: 15px;
	margin-bottom: 15px;
	font-weight: 700;
	font-size: 16px;
	border-radius: 6px;
	border: 1px solid #edafaf;
}
.msProBar .buyProBtnSpan a {
	color: #fff;
	text-decoration: none;
	background: #7b3024;
	display: inline-block;
	padding: 12px 20px;
	border-radius: 6px;
	font-size: 14px;
	line-height: 14px;
}
.msProBar .proTextMsg {
	margin-right: 15px;
}
</style>

<div class="wrap te-access-control msEditorWhiteWrap">
<h1 class="headingTitle">
<span class="headingicon"><img src="<?php echo plugins_url( 'images/mseditor-permission-icon.png', __FILE__ );?>"/> </span> 
<span class="headingTxt"><?php _e('Permissions', 'te-editor');?></span>
</h1>

<div class="msProBar">
   <span class="proTextMsg"> <?php _e('Note: This is just a screenshot. Buy PRO Version for this feature.', 'theme-editor');?></span>
   <span class="buyProBtnSpan">
    <a href="https://themeeditor.pro/product/theme-editor/" target="_blank"><?php _e('BUY PRO', 'theme-editor');?> </a>
    </span>
    </div>


<img src="<?php echo MK_THEME_EDITOR_URL.'app/view/images/per1.jpg';?>" style="width:100%">
<img src="<?php echo MK_THEME_EDITOR_URL.'app/view/images/per2.jpg';?>" style="width:100%">
</div>