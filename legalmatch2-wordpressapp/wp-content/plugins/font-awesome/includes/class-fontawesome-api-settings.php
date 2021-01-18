<?php
/**
 * This module is not considered part of the public API, only internal.
 */
namespace FortAwesome;

require_once trailingslashit( FONTAWESOME_DIR_PATH ) . 'includes/class-fontawesome-exception.php';

use \WP_Error, \InvalidArgumentException;

/**
 * Provides read/write access to the Font Awesome API settings.
 */
class FontAwesome_API_Settings {
	/**
	 * Name of the option used to store API settings.
	 *
	 * @since 4.0.0
	 * @ignore
	 */
	const OPTIONS_KEY = 'font-awesome-api-settings';

	/**
	 * Current access token.
	 *
	 * @internal
	 * @ignore
	 */
	protected $access_token = null;

	/**
	 * Expiration time for current access token.
	 *
	 * @internal
	 * @ignore
	 */
	protected $access_token_expiration_time = null;

	/**
	 * Current API token.
	 *
	 * @internal
	 * @ignore
	 */
	protected $api_token = null;

	/**
	 * Singleton instance.
	 *
	 * @internal
	 * @ignore
	 */
	protected static $instance = null;

	/**
	 * Encryption method.
	 *
	 * @internal
	 * @ignore
	 */
	protected $encryption_method = null;

	/**
	 * Encryption cipher length.
	 *
	 * @internal
	 * @ignore
	 */
	protected $encryption_cipher_length = null;

	/**
	 * Encryption key.
	 *
	 * @internal
	 * @ignore
	 */
	protected $encryption_key = null;

	/**
	 * Encryption salt.
	 *
	 * @internal
	 * @ignore
	 */
	protected $encryption_salt = null;

	/**
	 * Preferred encryption method.
	 *
	 * @internal
	 * @ignore
	 */
	const PREFERRED_ENCRYPTION_METHOD = 'aes-256-ctr';

	/**
	 * Returns the FontAwesome_API_Settings singleton instance.
	 *
	 * Internal use only. Not part of this plugin's public API.
	 *
	 * @return FontAwesome_API_Settings
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Resets the singleton instance referenced by this class and returns that new instance.
	 * All previous releases metadata held in the previous instance will be abandoned.
	 *
	 * @return FontAwesome_API_Settings
	 */
	public static function reset() {
		self::$instance = null;
		return self::instance();
	}

	/**
	 * Private constructor.
	 *
	 * @ignore
	 */
	private function __construct() {
		$this->initialize();
	}

	/**
	 * Initialize and instance
	 *
	 * Internal use only.
	 *
	 * @internal
	 * @ignore
	 */
	public function initialize() {
		$this->prepare_encryption();

		$option = get_option( self::OPTIONS_KEY );

		if (
			! is_array( $option ) ||
			! isset( $option['api_token'] ) ||
			! array_key_exists( 'access_token', $option ) ||
			! array_key_exists( 'access_token_expiration_time', $option )
		) {
			return;
		}

		$this->api_token = is_string( $option['api_token'] )
			? $this->decrypt( $option['api_token'] )
			: null;

		$this->access_token = is_string( $option['access_token'] )
			? $this->decrypt( $option['access_token'] )
			: null;

		$this->access_token_expiration_time = is_numeric( $option['access_token_expiration_time'] )
			? $option['access_token_expiration_time']
			: null;
	}

	/**
	 * Writes current config.
	 *
	 * Internal use only. Not part of this plugin's public API.
	 *
	 * @ignore
	 * @internal
	 * @return bool whether write succeeded or needs no update
	 */
	public function write() {
		$new_api_token = is_string( $this->api_token() )
			? $this->encrypt( $this->api_token() )
			: null;

		$new_access_token = is_string( $this->access_token() )
			? $this->encrypt( $this->access_token() )
			: null;

		$new_access_token_expiration_time = is_numeric( $this->access_token_expiration_time() )
			? $this->access_token_expiration_time()
			: null;

		$new_option_value = array(
			'api_token'                    => $new_api_token,
			'access_token'                 => $new_access_token,
			'access_token_expiration_time' => $new_access_token_expiration_time,
		);

		$old_option_value = get_option( self::OPTIONS_KEY );

		if (
			is_array( $old_option_value ) &&
			array_key_exists( 'api_token', $old_option_value ) &&
			$old_option_value['api_token'] === $new_option_value['api_token'] &&
			array_key_exists( 'access_token', $old_option_value ) &&
			$old_option_value['access_token'] === $new_option_value['access_token'] &&
			array_key_exists( 'access_token_expiration_time', $old_option_value ) &&
			$old_option_value['access_token_expiration_time'] === $new_option_value['access_token_expiration_time']
		) {
			// They are already equivalent, so we don't need to write again.
			return true;
		}

		if ( file_exists( trailingslashit( ABSPATH ) . 'font-awesome-api.ini' ) ) {
			/**
			 * Remove the old API settings file if it exists.
			 * Anything previously stored in it will be obsolete.
			 */
			@unlink( trailingslashit( ABSPATH ) . 'font-awesome-api.ini' );
		}

		return update_option(
			self::OPTIONS_KEY,
			$new_option_value
		);
	}

	/**
	 * Removes current API Token and related settings, setting them all to null,
	 * and deletes the option store.
	 *
	 * Internal use only. Not part of this plugin's public API.
	 *
	 * @internal
	 * @ignore
	 */
	public function remove() {
		delete_option( self::OPTIONS_KEY );
		self::reset();
	}

	/**
	 * Returns the current API Token.
	 *
	 * Internal use only. Not part of this plugin's public API.
	 *
	 * @ignore
	 * @internal
	 */
	public function api_token() {
		return $this->api_token;
	}

	/**
	 * Sets the API Token.
	 *
	 * Internal use only. Not part of this plugin's public API.
	 */
	public function set_api_token( $api_token ) {
		$this->api_token = $api_token;
	}

	/**
	 * Returns the current access token.
	 *
	 * Internal use only. Not part of this plugin's public API.
	 *
	 * @ignore
	 * @internal
	 */
	public function access_token() {
		return $this->access_token;
	}

	/**
	 * Sets the current access_token.
	 *
	 * Internal use only. Not part of this plugin's public API.
	 */
	public function set_access_token( $access_token ) {
		$this->access_token = $access_token;
	}

	/**
	 * Sets the current access_token_expiration_time.
	 *
	 * Internal use only. Not part of this plugin's public API.
	 *
	 * @param int $access_token_expiration_time time in unix epoch seconds as non-zero integer value
	 * @throws InvalidArgumentException if the given param is zero or cannot be cast as an integer
	 */
	public function set_access_token_expiration_time( $access_token_expiration_time ) {
		$int_val = intval( $access_token_expiration_time );

		if ( 0 !== $int_val ) {
			$this->access_token_expiration_time = $access_token_expiration_time;
		} else {
			throw new InvalidArgumentException();
		}
	}

	/**
	 * Returns the expiration time for the current access token.
	 *
	 * Internal use only. Not part of this plugin's public API.
	 *
	 * @ignore
	 * @internal
	 */
	public function access_token_expiration_time() {
		return $this->access_token_expiration_time;
	}

	/**
	 * Requests an access_token with the current api_token. Stores the result
	 * upon successfully retrieving an access token.
	 *
	 * Internal use only. Not part of this plugin's API.
	 *
	 * @ignore
	 * @internal
	 * @throws ApiTokenMissingException
	 * @throws ApiTokenEndpointRequestException
	 * @throws ApiTokenEndpointResponseException
	 * @throws ApiTokenInvalidException
	 * @throws AccessTokenStorageException
	 * @return void
	 */
	public function request_access_token() {
		if ( ! is_string( $this->api_token() ) ) {
			throw new ApiTokenMissingException();
		}

		$response = $this->post(
			array(
				'body'    => '',
				'headers' => array(
					'authorization' => 'Bearer ' . $this->api_token(),
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			throw ApiTokenEndpointRequestException::with_wp_error( add_failed_request_diagnostics( $response ) );
		}

		if ( 200 !== $response['response']['code'] ) {
			throw ApiTokenInvalidException::with_wp_response( $response );
		}

		$body = json_decode( $response['body'], true );

		if (
			! isset( $body['access_token'] ) ||
			! is_string( $body['access_token'] ) ||
			! isset( $body['expires_in'] ) ||
			! is_int( $body['expires_in'] )
		) {
			throw ApiTokenEndpointResponseException::with_wp_response( $response );
		}

		$this->set_access_token( $body['access_token'] );

		try {
			$this->set_access_token_expiration_time( $body['expires_in'] + time() );
		} catch ( InvalidArgumentException $e ) {
			throw ApiTokenEndpointResponseException::with_wp_response( $response );
		}

		$result = $this->write();

		if ( ! boolval( $result ) ) {
			throw new AccessTokenStorageException();
		} else {
			return true;
		}
	}

	/**
	 * Determines encryption method, key, salt, and cipher iv length if
	 * the openssl extension is available.
	 *
	 * Internal use only.
	 *
	 * @internal
	 * @ignore
	 */
	public function prepare_encryption() {
		if ( ! extension_loaded( 'openssl' ) ) {
			return;
		}

		$methods = openssl_get_cipher_methods();
		$method  = null;

		if ( array_search( self::PREFERRED_ENCRYPTION_METHOD, $methods, true ) ) {
			$method = self::PREFERRED_ENCRYPTION_METHOD;
		} elseif ( is_array( $methods ) && count( $methods ) > 0 ) {
			// Take the first available method as a fallback.
			$method = $methods[0];
		}

		if (
			$method &&
			defined( 'LOGGED_IN_SALT' ) &&
			is_string( LOGGED_IN_SALT ) &&
			defined( 'LOGGED_IN_KEY' ) &&
			is_string( LOGGED_IN_KEY )
		) {
			$this->encryption_method        = $method;
			$this->encryption_cipher_length = openssl_cipher_iv_length( $method );
			$this->encryption_key           = LOGGED_IN_KEY;
			$this->encryption_salt          = LOGGED_IN_SALT;
		}
	}

	/**
	 * Encrypts and returns data.
	 *
	 * Internal use only.
	 *
	 * This method is patterned after the Data_Encryption::encrypt() method
	 * in the Site Kit by Google plugin, version 1.4.0, licensed under Apache v2.0.
	 * https://www.apache.org/licenses/LICENSE-2.0
	 *
	 * @ignore
	 * @internal
	 */
	public function encrypt( $data ) {
		if ( ! $this->encryption_key ) {
			return $data;
		}

		$init_vec = openssl_random_pseudo_bytes( $this->encryption_cipher_length );

		$raw = openssl_encrypt(
			$data . $this->encryption_salt,
			$this->encryption_method,
			$this->encryption_key,
			0,
			$init_vec
		);

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		return base64_encode( $init_vec . $raw );
	}

	/**
	 * Decrypts and returns data.
	 *
	 * Internal use only.
	 *
	 * This method is patterned after the Data_Encryption::decrypt() method
	 * in the Site Kit by Google plugin, version 1.4.0, licensed under Apache v2.0.
	 * https://www.apache.org/licenses/LICENSE-2.0
	 *
	 * @ignore
	 * @internal
	 */
	public function decrypt( $data ) {
		if ( ! $this->encryption_method ) {
			return $data;
		}

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$raw = base64_decode( $data, true );

		$init_vec = substr( $raw, 0, $this->encryption_cipher_length );

		$raw = substr( $raw, $this->encryption_cipher_length );

		$result = openssl_decrypt(
			$raw,
			$this->encryption_method,
			$this->encryption_key,
			0,
			$init_vec
		);

		if (
			! $result ||
			substr( $result, - strlen( $this->encryption_salt ) ) !== $this->encryption_salt
		) {
			return null;
		}

		return substr( $result, 0, - strlen( $this->encryption_salt ) );
	}

	/**
	 * Wrapper for wp_remote_post(). Mostly to make it easier to mock the network
	 * request with a subclass.
	 *
	 * Internal use only. Not part of this plugin's public API.
	 *
	 * @ignore
	 * @internal
	 * @return WP_Error | array just like wp_remote_post()
	 */
	protected function post( $args ) {
		return wp_remote_post( FONTAWESOME_API_URL . '/token', $args );
	}
}

/**
 * Convenience global function to get a singleton instance of the API Settings.
 *
 * Internal use only. Not part of this plugin's public API.
 *
 * @return FontAwesome_API_Settings
 */
function fa_api_settings() {
	return FontAwesome_API_Settings::instance();
}
