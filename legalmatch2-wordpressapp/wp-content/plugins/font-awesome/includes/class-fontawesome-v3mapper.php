<?php
namespace FortAwesome;

/**
 * Main plugin class module.
 *
 * @noinspection PhpIncludeInspection
 * @deprecated Provided temporarily for an upgrade path for previous font-awesome plugin users.
 * @ignore
 */

/**
 * FontAwesome_V3Mapper Class
 *
 * @deprecated Provided temporarily for an upgrade path for previous font-awesome plugin users.
 * @ignore
 */
class FontAwesome_V3Mapper {

	/**
	 * @internal
	 * @ignore
	 */
	private $map = null;

	/**
	 * @internal
	 * @ignore
	 */
	private static $instance = null;

	/**
	 * Returns the singleton instance of this class.
	 *
	 * Internal use only, not part of this plugin's public API.
	 *
	 * @deprecated Provided temporarily for an upgrade path for previous font-awesome plugin users.
	 * @return FontAwesome_V3Mapper
	 * @ignore
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * @internal
	 * @ignore
	 */
	private function __construct() {
		$this->load_map();
	}

	/**
	 * Loads the map.
	 *
	 * @deprecated Provided temporarily for an upgrade path for previous font-awesome plugin users.
	 * @internal
	 * @ignore
	 */
	private function load_map() {
		// Don't load again if it's already loaded.
		if ( is_null( $this->map ) ) {
			require trailingslashit( FONTAWESOME_DIR_PATH ) . 'v3shims.php';

			$this->map = get_font_awesome_v3shims();
		}
	}

	/**
	 * Map a Font Awesome version 3 icon name to the equivalent Font Awesome 5 prefix and name.
	 *
	 * @deprecated Provided temporarily for an upgrade path for previous font-awesome plugin users.
	 * @ignore
	 * @internal
	 */
	public function map_v3_to_v5( $v3name ) {
		if ( isset( $this->map[ $v3name ] ) ) {
			return $this->map[ $v3name ];
		} else {
			return '';
		}
	}
}
