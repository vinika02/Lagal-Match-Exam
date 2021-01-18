<?php if ( !defined( 'ABSPATH' ) ) exit;
$ctd = $this->ms_theme_directory( 'child' );
$ctpd = $this->ms_theme_directory( 'parnt' );
$child_style_dir = get_theme_root().'/'.$ctd.'/style.css'; 
$myfile = fopen($child_style_dir, "r");
$myfile_data =fread($myfile,filesize($child_style_dir));
?>
<div class="ms_childStyleWrap">
	<div class="childStyleEditor">
		<textarea id="ms_child_style" name="ms_child_style" class="ms_child_style" wrap="off" readonly><?php echo $myfile_data; fclose($myfile);?></textarea>
	</div>
</div>
