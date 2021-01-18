<?php
namespace FortAwesome;

require_once trailingslashit( dirname( __FILE__ ) ) . 'class-fontawesome.php';
require_once trailingslashit( dirname( __FILE__ ) ) . 'class-fontawesome-api-settings.php';

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 */
class FontAwesome_Deactivator {

	/**
	 * Any caches or transients are removed when deactivating.
	 * To remove options data, use uninstall().
	 */
	public static function deactivate() {
		delete_site_transient( FontAwesome_Release_Provider::RELEASES_TRANSIENT );
		delete_site_transient( FontAwesome_Release_Provider::LAST_USED_RELEASE_TRANSIENT );
		delete_transient( FontAwesome::V3DEPRECATION_TRANSIENT );
	}

	/**
	 * Delete options data.
	 */
	public static function uninstall() {
		delete_option( FontAwesome::OPTIONS_KEY );
		delete_option( FontAwesome::CONFLICT_DETECTION_OPTIONS_KEY );
		delete_option( FontAwesome_API_Settings::OPTIONS_KEY );
	}
}
