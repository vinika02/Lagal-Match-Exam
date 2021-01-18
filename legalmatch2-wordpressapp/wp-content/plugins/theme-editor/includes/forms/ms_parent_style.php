<?php if ( !defined( 'ABSPATH' ) ) exit;
$ctd = $this->ms_theme_directory( 'child' );
$ctpd = $this->ms_theme_directory( 'parnt' );
$basic_style_dir = get_theme_root().'/'.$ctpd.'/style.css'; 
$myfile = fopen($basic_style_dir, "r");
$myfile_data =fread($myfile,filesize($basic_style_dir));
?>
<div class="ms_basicStyleWrap">
	<div class="basicStyleEditor">
		<textarea id="ms_basic_style" name="ms_basic_style" class="ms_basic_style" wrap="off" readonly>
			<?php echo trim($myfile_data);?>
		</textarea>
	</div>
</div>
