<?php
/**
 * The template for displaying the footer
 *
 * Contains the opening of the #site-footer div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package WordPress
 * @subpackage Twenty_Twenty
 * @since Twenty Twenty 1.0
 */
$has_sidebar_1 = is_active_sidebar( 'sidebar-1' );
$has_sidebar_2 = is_active_sidebar( 'sidebar-2' );
$has_sidebar_3 = is_active_sidebar( 'sidebar-3' );
?>
		<div class="c-content footer-container">
			<div class="row">
				<div class="column-footer column-footer-1">
					<?php if ( $has_sidebar_1 ) { ?>

						<?php dynamic_sidebar( 'sidebar-1' ); ?>

					<?php } ?>
				</div>
				<div class="column-footer column-footer-2">
					<?php if ( $has_sidebar_2 ) { ?>

						<?php dynamic_sidebar( 'sidebar-2' ); ?>

					<?php } ?>
				</div>
				<div class="column-footer column-footer-3">
					<?php if ( $has_sidebar_3 ) { ?>

					<div class="footer-widgets column-two grid-item">
						<?php dynamic_sidebar( 'sidebar-3' ); ?>
					</div>

					<?php } ?>
				</div>
			</div>	
		</div>
		<br>
		
			
		<?php wp_footer(); ?>

	</body>
</html>
