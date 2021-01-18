<?php if ( !defined( 'ABSPATH' ) ) exit; ?>
<select class="ctc-select msFormInput" id="ctc_theme_<?php echo $template; ?>" name="ctc_theme_<?php echo $template; ?>" 
	<?php echo $this->ctc()->is_theme() ? '' : ' disabled '; ?>>
	<?php
	uasort( $this->ctc()->themes[ $template ], array( $this, 'cmp_theme' ) );
	foreach ( $this->ctc()->themes[ $template ] as $slug => $theme )
	echo '<option value="' . $slug . '"' . ( $slug == $selected ? ' selected' : '' ) . '>' 
	. esc_attr( $theme[ 'Name' ] ) . '</option>' . LF;?>
</select>



