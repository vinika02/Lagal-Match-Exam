<?php
/**
 * Global constants.
 */

if ( ! defined( 'FONTAWESOME_DIR_PATH' ) ) {
	/**
	 * Active Font Awesome plugin installation directory path.
	 *
	 * The result of `plugin_dir_path()` on the `defines.php` file for the actively
	 * executing installation of the Font Awesome plugin.
	 *
	 * For example, if the example plugin under `integrations/plugins/plugin-sigma`
	 * in this repo were installed, activated, and its copy of the Font Awesome
	 * plugin were the one selected for execution, then the value of this
	 * constant would be something like this:
	 * `/var/www/html/wp-content/plugins/plugin-sigma/vendor/fortawesome/wordpress-fontawesome/`
	 *
	 * However, suppose that a second copy of the Font Awesome plugin were installed
	 * from the WordPress plugins directory, and that copy had a later semantic
	 * version than the one bundled by plugin-sigma. In that case, the value
	 * of this constant would look more like this:
	 * `/var/www/html/wp-content/plugins/font-awesome/`
	 *
	 * @since 4.0.0
	 */
	define( 'FONTAWESOME_DIR_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'FONTAWESOME_DIR_URL' ) ) {
	/**
	 * Active Font Awesome plugin installation directory URL.
	 *
	 * The url that corresponds to the top level of the executing installation
	 * of the Font Awesome plugin (where the `defines.php` file lives).
	 *
	 * For example, if the example plugin under `integrations/plugins/plugin-sigma`
	 * in this repo were installed, activated, and its copy of the Font Awesome
	 * plugin were the one selected for execution, then the value of this
	 * constant would be something like this:
	 * `http://localhost:8765/wp-content/plugins/plugin-sigma/vendor/fortawesome/wordpress-fontawesome/`
	 *
	 * However, suppose that a second copy of the Font Awesome plugin were installed
	 * from the WordPress plugins directory, and that copy had a later semantic
	 * version than the one bundled by plugin-sigma. In that case, the value
	 * of this constant would look more like this:
	 * `http://localhost:8765/wp-content/plugins/font-awesome/`
	 *
	 * This also accounts for the possibility that current Font Awesome is installed
	 * as a composer package required by the current theme. For example, if the
	 * example theme under `integrations/themes/plugin-mu` in this repo were installed,
	 * activated, and its copy of the Font Awesome plugin were the one selected for execution,
	 * then the value of this constant would be something like this:
	 * `http://localhost:8765/wp-content/themes/theme-mu/vendor/fortawesome/wordpress-fontawesome/`
	 *
	 * @since 4.0.0
	*/
	$ss_dir = get_stylesheet_directory();
	/**
	 * If the current file path begins with the stylesheet directory, then we
	 * know that Font Awesome is being loaded as a dependency of a theme.
	 */
	if ( substr( __FILE__, 0, strlen( $ss_dir ) ) === $ss_dir ) {
		$fa_sub_path = substr( __DIR__, strlen( $ss_dir ) );
		define( 'FONTAWESOME_DIR_URL', untrailingslashit( get_stylesheet_directory_uri() ) . '/' . trailingslashit( $fa_sub_path ) );
	} else {
		define( 'FONTAWESOME_DIR_URL', plugin_dir_url( __FILE__ ) );
	}
}

if ( ! defined( 'FONTAWESOME_ENV' ) ) {
	/**
	 * @internal
	 * @ignore
	 */
	define( 'FONTAWESOME_ENV', getenv( 'FONTAWESOME_ENV' ) );
}

if ( ! defined( 'FONTAWESOME_API_URL' ) ) {
	if ( 'test' === FONTAWESOME_ENV ) {
		/**
		 * @internal
		 * @ignore
		 */
		define( 'FONTAWESOME_API_URL', 'no_network_in_test_env' );
	} elseif ( 'development' === getenv( 'FONTAWESOME_ENV' ) && boolval( getenv( 'FONTAWESOME_API_URL' ) ) ) {
		/**
		 * @internal
		 * @ignore
		 */
		define( 'FONTAWESOME_API_URL', untrailingslashit( getenv( 'FONTAWESOME_API_URL' ) ) );
	} else {
		/**
		 * @internal
		 * @ignore
		 */
		define( 'FONTAWESOME_API_URL', 'https://api.fontawesome.com' );
	}
}

if ( ! defined( 'FONTAWESOME_KIT_LOADER_BASE_URL' ) ) {
	if ( 'development' === getenv( 'FONTAWESOME_ENV' ) && boolval( getenv( 'FONTAWESOME_KIT_LOADER_BASE_URL' ) ) ) {
		/**
		 * @internal
		 * @ignore
		 */
		define( 'FONTAWESOME_KIT_LOADER_BASE_URL', untrailingslashit( getenv( 'FONTAWESOME_KIT_LOADER_BASE_URL' ) ) );
	} else {
		/**
		 * @internal
		 * @ignore
		 */
		define( 'FONTAWESOME_KIT_LOADER_BASE_URL', 'https://kit.fontawesome.com' );
	}
}
