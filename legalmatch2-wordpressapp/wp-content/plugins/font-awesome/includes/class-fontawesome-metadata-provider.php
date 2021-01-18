<?php
/**
 * This module is not considered part of the public API, only internal.
 * Any data or functionality that it produces should be exported by the
 * main FontAwesome class and the API documented and semantically versioned there.
 */
namespace FortAwesome;

use \WP_Error, \Error, \Exception;

require_once trailingslashit( FONTAWESOME_DIR_PATH ) . 'includes/class-fontawesome-api-settings.php';

/**
 * Provides metadata about Font Awesome icons.
 *
 * Internal use only. Not part of this plugin's public API.
 *
 * @internal
 */
class FontAwesome_Metadata_Provider {
	/**
	 * Singleton instance.
	 *
	 * @internal
	 */
	protected static $instance = null;

	/**
	 * Post method that wraps wp_remote_post.
	 *
	 * Internal use only. Not part of this plugin's public API.
	 *
	 * @internal
	 */
	protected function post( $url, $args = array() ) {
		return wp_remote_post( $url, $args );
	}

	/**
	 * Returns the FontAwesome_Metadata_Provider singleton instance.
	 *
	 * Internal use only. Not part of this plugin's public API.
	 *
	 * @return FontAwesome_Metadata_Provider
	 * @internal
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
	 * Internal use only. Not part of this plugin's public API.
	 *
	 * @return FontAwesome_Metadata_Provider
	 */
	public static function reset() {
		self::$instance = null;
		return self::instance();
	}

	/**
	 * Returns query errors if there was a problem querying the
	 * graphql api in a readable string.
	 *
	 * @ignore
	 */
	protected function query_errors( $errors ) {
		$error_string = '';

		foreach ( $errors as $error ) {
			$error_string .= $error['message'];
		}

		return $error_string;
	}

	/**
	 * Queries the GraphQL API and returns the response body when the HTTP status
	 * of the response is 200.
	 *
	 * Internal use only. Not part of this plugin's public API.
	 *
	 * External code should use {@see FontAwesome::query()} instead.
	 *
	 * @param $ignore_auth when TRUE this will omit an authorization header on
	 *     the network request, even if an apiToken is present.
	 * @ignore
	 * @internal
	 * @throws ApiTokenMissingException
	 * @throws ApiTokenEndpointRequestException
	 * @throws ApiTokenEndpointResponseException
	 * @throws ApiTokenInvalidException
	 * @throws AccessTokenStorageException
	 * @throws ApiRequestException
	 * @throws ApiResponseException
	 * @return string json encoded response body when the API server response
	 *     has a HTTP 200 status.
	 */
	public function metadata_query( $query_string, $ignore_auth = false ) {
		$args = array(
			'method'  => 'POST',
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'body'    => '{"query": ' . wp_json_encode( $query_string ) . '}',
		);

		if ( ! $ignore_auth ) {
			$access_token                     = $this->current_access_token();
			$args['headers']['authorization'] = "Bearer $access_token";
		}

		$response = $this->post( FONTAWESOME_API_URL, $args );

		if ( $response instanceof WP_Error ) {
			throw ApiRequestException::with_wp_error( add_failed_request_diagnostics( $response ) );
		}

		if ( 200 === $response['response']['code'] ) {
			return $response['body'];
		} else {
			throw ApiResponseException::with_wp_response( $response );
		}
	}

	/**
	 * Returns a current access_token, if available. Attempts to refresh an
	 * access_token if the one we have is near or past expiration and an api_token
	 * is present.
	 *
	 * Returns WP_Error indicating any error when trying to refresh an access_token.
	 * Returns null when there is no api_token.
	 * Otherwise, returns the current access_token as a string.
	 *
	 * @throws ApiTokenMissingException
	 * @throws ApiTokenEndpointRequestException
	 * @throws ApiTokenEndpointResponseException
	 * @throws ApiTokenInvalidException
	 * @throws AccessTokenStorageException
	 * @return string|null access_token if available; null if unavailable
	 */
	protected function current_access_token() {
		if ( ! boolval( fa_api_settings()->api_token() ) ) {
			return null;
		}

		$exp          = fa_api_settings()->access_token_expiration_time();
		$access_token = fa_api_settings()->access_token();

		if ( is_string( $access_token ) && $exp > ( time() - 5 ) ) {
			return $access_token;
		} else {
			// refresh the access token.
			fa_api_settings()->request_access_token();
			return fa_api_settings()->access_token();
		}
	}
}

/**
 * Convenience global function to get a singleton instance of the Metadata Provider.
 *
 * Internal use only. Not part of this plugin's public API.
 *
 * @see FontAwesome_Metadata_Provider::instance()
 * @return FontAwesome_Metadata_Provider
 * @internal
 */
function fa_metadata_provider() {
	return FontAwesome_Metadata_Provider::instance();
}
