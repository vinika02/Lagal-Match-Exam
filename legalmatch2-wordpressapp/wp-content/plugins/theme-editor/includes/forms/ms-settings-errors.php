<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;
$errorstrings = array(    
    '1'   => __( 'Zip file creation failed.', 'te-editor' ),
    '2'   => __( 'You do not have permission to configure child themes.', 'te-editor' ),
    '3'   => __( '%s does not exist. Please select a valid Parent Theme.', 'te-editor' ),
    '4'   => __( 'The Functions file is required and cannot be deleted.', 'te-editor' ),
    '5'   => __( 'Please select a valid Parent Theme.', 'te-editor' ),
    '6'   => __( 'Please select a valid Child Theme.', 'te-editor' ),
    '7'   => __( 'Please enter a valid Child Theme directory name.', 'te-editor' ),
    '8'   => __( '<strong>%s</strong> exists. Please enter a different Child Theme template name.', 'te-editor' ),
    '9'   => __( 'Your theme directories are not writable.', 'te-editor' ),
    '10'  => __( 'Could not upgrade child theme', 'te-editor' ),
    '11'  => __( 'Your stylesheet is not writable.', 'te-editor' ), 
    '12'  => __( 'A closing PHP tag was detected in Child theme functions file so "Parent Stylesheet Handling" option was not configured. Closing PHP at the end of the file is discouraged as it can cause premature HTTP headers. Please edit <code>functions.php</code> to remove the final <code>?&gt;</code> tag and click "Generate/Rebuild Child Theme Files" again.', 'te-editor' ),
    '13'  => __( 'Could not copy file: %s', 'te-editor' ),
    '14'  => __( 'Could not delete %s file.', 'te-editor' ),
    '15'  => __( 'could not copy %s', 'te-editor' ), 
    '16'  => __( 'invalid dir: %s', 'te-editor' ), 
    '17'  => __( 'deleted: %s != %s files', 'te-editor' ), 
    '18'  => __( 'newfiles != files', 'te-editor' ),
    '19'  => __( 'There were errors while resetting permissions.', 'te-editor' ), 
    '20'  => __( 'Could not upload file.', 'te-editor' ),
    '21'  => __( 'Invalid theme root directory.', 'te-editor' ),
    '22'  => __( 'No writable temp directory.', 'te-editor' ),
    '23'  => __( 'PclZip returned zero bytes.', 'te-editor' ),
    '24'  => __( 'Unpack failed -- %s', 'te-editor' ), 
    '25'  => __( 'Pack failed -- %s', 'te-editor' ), 
    '26'  => __( 'Maximum number of styles exceeded.', 'te-editor' ),
    '27'  => __( 'Error moving file: %s', 'te-editor' ),
    '28'  => __( 'Could not set write permissions.', 'te-editor' ), 
);
$writable_errors = array(9,11,19,28);
if ( isset( $_GET[ 'error' ] ) || count( $this->ctc()->errors )):
	$errors = $this->ctc()->errors;
	if ( isset( $_GET[ 'error' ] ) )
		$errors = array_merge( $errors,
		explode( ',', sanitize_text_field( $_GET[ 'error' ] ) )
	);
?>
<div class="error notice is-dismissible dashicons-before">
    <h4><?php _e( 'Error:', 'te-editor' ); ?></h4>
    <ul>
		<?php
		$writable_error = 0;
		foreach ( $errors as $error ):
			$errs = explode( ':', $error );
			$errkey = array_shift( $errs );
			if ( in_array( $errkey, $writable_errors ) )
				$writable_error = 1;
			if ( $errkey && isset( $errorstrings[ $errkey ] ) ):
				$err = $errorstrings[ $errkey ];
			// accommodate zero, one or two arguments
				printf( '<li>' . $err . '</li>' . LF, array_shift( $errs ), array_shift( $errs ) );
			endif;
		endforeach;
		?>
    </ul>
</div>
<?php
	if ($writable_error ):        
	endif;
elseif ( isset( $_GET[ 'updated' ])):
    $child_theme = wp_get_theme( $this->ctc()->get( 'child' ) );
?>
<div class="updated notice is-dismissible">
    <?php
    switch ( $_GET[ 'updated' ] ):
        case '4':
        ?>
    <p>
        <?php printf( __( 'Current Analysis Child Theme <strong>%s</strong> has been reset.', 'te-editor' ), $child_theme->Name ); ?> </p>
    <?php
            break;
        case '7':
        ?>
    <p>
        <?php _e( 'Update Key saved successfully.', 'te-editor' ); ?>
    </p>
    <?php
            break;
        case '8':
        ?>
    <p>
        <?php _e( 'Child Theme files modified successfully.', 'te-editor' ); ?>
    </p>
    <?php
            break;
        default: ?>
    <p class="ms-success-response">
        <?php echo apply_filters( 'chld_thm_cfg_update_msg', sprintf( __( 'Child Theme <strong>%s</strong> has been generated successfully.', 'te-editor' ), $child_theme->Name ), $this->ctc() ); ?>
        <?php
            if ( $this->ctc()->is_theme() ): ?>
    </p>
<?php
            endif;
    endswitch;
?>
</div>
<?php
endif;