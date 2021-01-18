<?php
namespace FortAwesome;

require_once trailingslashit( dirname( __FILE__ ) ) . '../defines.php';
require_once trailingslashit( dirname( __FILE__ ) ) . 'class-fontawesome.php';

/**
 * Plugin activation logic.
 *
 * Client code that depends upon this plugin should use {@see FontAwesome_Loader::initialize()}.
 *
 * @since 4.0.0
 */
class FontAwesome_Activator {
	/**
	 * Initializes plugin options only if they are empty.
	 *
	 * @since 4.0.0
	 * @throws ApiRequestException
	 * @throws ApiResponseException
	 * @throws ReleaseProviderStorageException
	 */
	public static function activate() {
		self::initialize();
	}

	/**
	 * Initializes plugin options with defaults only if they are empty.
	 *
	 * Internal use only, not part of this plugin's public API.
	 *
	 * Otherwise, it leaves alone options that are already present.
	 *
	 * Sets default user options. Will attempt to get the latest available version,
	 * which requires access to the Font Awesome API server. Throws an exception
	 * if that request fails.
	 *
	 * @param bool $force if true, overwrite any existing options with defaults
	 *
	 * @ignore
	 * @internal
	 * @throws ApiRequestException
	 * @throws ApiResponseException
	 * @throws ReleaseProviderStorageException
	 */
	public static function initialize( $force = false ) {
		if ( $force || ! get_option( FontAwesome::OPTIONS_KEY ) ) {
			self::initialize_user_options();
		}

		if ( $force || ! get_option( FontAwesome::CONFLICT_DETECTION_OPTIONS_KEY ) ) {
			self::initialize_conflict_detection_options();
		}
	}

	/**
	 * Internal use only.
	 *
	 * @ignore
	 * @internal
	 * @throws ApiRequestException
	 * @throws ApiResponseException
	 * @throws ReleaseProviderStorageException
	 */
	private static function initialize_user_options() {
		fa()->refresh_releases();
		$version = fa()->latest_version();
		$options = array_merge( FontAwesome::DEFAULT_USER_OPTIONS, array( 'version' => $version ) );
		update_option( FontAwesome::OPTIONS_KEY, $options );
	}

	private static function initialize_conflict_detection_options() {
		update_option( FontAwesome::CONFLICT_DETECTION_OPTIONS_KEY, FontAwesome::DEFAULT_CONFLICT_DETECTION_OPTIONS );
	}
}

