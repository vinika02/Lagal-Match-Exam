<?php
/**
 * Exceptions.
 */
namespace FortAwesome;

use \Exception;

// phpcs:disable Generic.Files.OneClassPerFile.MultipleFound
// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound

/**
 * An abstract class defining behavior for most exceptions thrown by this plugin.
 */
abstract class FontAwesome_Exception extends Exception {
	/**
	 * A WP_Error object that was the occassion for this exception.
	 *
	 * Internal use only.
	 *
	 * @ignore
	 * @internal
	 */
	protected $wp_error = null;

	/**
	 * An HTTP response array that is the occassion for this exception.
	 * Array keys should be like an array that would be returned from wp_remote_post().
	 *
	 * Internal use only.
	 *
	 * @ignore
	 * @internal
	 */
	protected $wp_response = null;

	/**
	 * Construct an exception that includes a WP_Error that is the cause of the exception.
	 *
	 * Internal use only.
	 *
	 * @ignore
	 * @internal
	 */
	public static function with_wp_error( $wp_error ) {
		// This is how we invoke the derived class's constructor from an inherited static method.
		$obj = new static();

		if ( ! is_null( $wp_error ) && is_a( $wp_error, 'WP_Error' ) ) {
			$obj->wp_error = $wp_error;
		}

		return $obj;
	}

	/**
	 * Construct an exception with an associated HTTP response, the cause of the exception.
	 *
	 * Internal use only.
	 *
	 * @ignore
	 * @internal
	 * @param $wp_reponse a response array as would be returned by wp_remote_post()
	 *   with keys like: 'headers', 'body', 'response'
	 */
	public static function with_wp_response( $wp_response ) {
		// This is how we invoke the derived class's constructor from an inherited static method.
		$obj = new static();

		if (
			! is_null( $wp_response ) &&
			is_array( $wp_response ) &&
			isset( $wp_response['headers'] ) &&
			isset( $wp_response['body'] ) &&
			isset( $wp_response['response'] )
		) {
			$obj->wp_response = $wp_response;
		}

		return $obj;
	}

	/**
	 * Construct an exception with a previously thrown Error or Exception.
	 *
	 * Internal use only.
	 *
	 * (The Throwable interface is not available until PHP 7, and we support back to 5.6.)
	 *
	 * @ignore
	 * @internal
	 * @param $e Error or Exception
	 */
	public static function with_thrown( $e ) {
		return new static( null, 0, $e );
	}

	/**
	 * The WP_Error associated with this exception, if any.
	 *
	 * @since 4.0.0
	 * @return null|WP_Error
	 */
	public function get_wp_error() {
		return $this->wp_error;
	}

	/**
	 * The response object associated with this exception, if any.
	 *
	 * @since 4.0.0
	 * @return null|array a response array as would be returned by wp_remote_post()
	 *   with keys like: 'headers', 'body', 'response'.
	 */
	public function get_wp_response() {
		return $this->wp_response;
	}
}

/**
 * An abstract parent class for exceptions that should result in an HTTP 500 status.
 *
 * @since 4.0.0
 */
abstract class FontAwesome_ServerException extends FontAwesome_Exception {}

/**
 * An abstract parent class for exceptions that should result in an HTTP 400 status.
 *
 * @since 4.0.0
 */
abstract class FontAwesome_ClientException extends FontAwesome_Exception {}

/**
 * Thrown when an API Token is required but not found.
 *
 * @since 4.0.0
 */
class ApiTokenMissingException extends FontAwesome_ClientException {
	/**
	 * Internal use only.
	 *
	 * @ignore
	 * @internal
	 */
	public function __construct( $message = null, $code = 0, $previous = null ) {
		return parent::__construct(
			esc_html__(
				'Whoops, it looks like you have not provided a Font Awesome API Token. Enter one on the Font Awesome plugin settings page.',
				'font-awesome'
			),
			$code,
			$previous
		);
	}
}

/**
 * Thrown when the WordPress server fails to issue a request to the token endpoint
 * on Font Awesome API server.
 *
 * @since 4.0.0
 */
class ApiTokenEndpointRequestException extends FontAwesome_ServerException {
	/**
	 * Internal use only.
	 *
	 * @ignore
	 * @internal
	 */
	public function __construct( $message = null, $code = 0, $previous = null ) {
		return parent::__construct(
			esc_html__(
				'Your WordPress server failed when trying to communicate with the Font Awesome API token endpoint.',
				'font-awesome'
			),
			$code,
			$previous
		);
	}
}

/**
 * Thrown when the token endpoint on the Font Awesome API server returns an
 * non-200 status when trying to use the configured API Token to get an access_token
 * to use for subsequent API query requests.
 */
class ApiTokenInvalidException extends FontAwesome_ClientException {
	/**
	 * Internal use only.
	 *
	 * @ignore
	 * @internal
	 */
	public function __construct( $message = null, $code = 0, $previous = null ) {
		return parent::__construct(
			esc_html__(
				'Whoops, it looks like that API Token is not valid. Try another one?',
				'font-awesome'
			),
			$code,
			$previous
		);
	}
}

/**
 * Thrown when the Font Awesome API server returns a response with an unexpected schema.
 * This would probably indicate either a programming error in the API server, or a change
 * in the schema such that the Font Awesome plugin's expectations are unmet.
 *
 * @since 4.0.0
 */
class ApiTokenEndpointResponseException extends FontAwesome_ServerException {
	/**
	 * Internal use only.
	 *
	 * @ignore
	 * @internal
	 */
	public function __construct( $message = null, $code = 0, $previous = null ) {
		return parent::__construct(
			esc_html__(
				'Oh no! It looks like your API Token was valid, but the Font Awesome API server returned an invalid response.',
				'font-awesome'
			),
			$code,
			$previous
		);
	}
}

/**
 * Thrown when there is a failure to write a file on the WordPress server filesystem
 * to store the access_token.
 *
 * @since 4.0.0
 */
class AccessTokenStorageException extends FontAwesome_ServerException {
	/**
	 * Internal use only.
	 *
	 * @ignore
	 * @internal
	 */
	public function __construct( $message = null, $code = 0, $previous = null ) {
		return parent::__construct(
			esc_html__(
				'Your API Token was valid but we couldn\'t save it for some reason.',
				'font-awesome'
			),
			$code,
			$previous
		);
	}
}

/**
 * Thrown when a an options configuration is attempted that does not pass validation.
 *
 * @since 4.0.0
 */
class ConfigSchemaException extends FontAwesome_ClientException {
	/**
	 * Internal use only.
	 *
	 * @internal
	 * @ignore
	 */
	public static function webfont_always_enables_pseudo_elements() {
		return new static(
			esc_html__(
				'Pseudo-elements cannot be disabled with webfont technology.',
				'font-awesome'
			)
		);
	}

	/**
	 * Internal use only.
	 *
	 * @internal
	 * @ignore
	 */
	public static function kit_token_no_api_token() {
		return new static(
			esc_html__(
				'A kitToken was given without a valid apiToken',
				'font-awesome'
			)
		);
	}

	/**
	 * Internal use only.
	 *
	 * @internal
	 * @ignore
	 */
	public static function concrete_version_expected() {
		return new static(
			esc_html__(
				'A Font Awesome version number was expected but not given',
				'font-awesome'
			)
		);
	}

	/**
	 * Internal use only.
	 *
	 * @internal
	 * @ignore
	 */
	public static function webfont_v4compat_introduced_later() {
		return new static(
			esc_html__(
				'Whoops! You found a corner case here. Version 4 compatibility for our webfont technology was not introduced until Font Awesome 5.1.0. Try using a newer version, disabling version 4 compatibility, or switch to SVG.',
				'font-awesome'
			)
		);
	}
}

/**
 * Thrown when catching an Error or Exception from a registered theme or plugin.
 *
 * @since 4.0.0
 */
class PreferenceRegistrationException extends FontAwesome_ServerException {
	/**
	 * Internal use only.
	 *
	 * @ignore
	 * @internal
	 */
	public function __construct( $message = null, $code = 0, $previous = null ) {
		return parent::__construct(
			esc_html__(
				'A theme or plugin registered with Font Awesome threw an exception.',
				'font-awesome'
			),
			$code,
			$previous
		);
	}
}

/**
 * Thrown when the WordPress server fails to issue a request to the main query
 * endpoint on Font Awesome API server.
 *
 * @since 4.0.0
 */
class ApiRequestException extends FontAwesome_ServerException {
	/**
	 * Internal use only.
	 *
	 * @ignore
	 * @internal
	 */
	public function __construct( $message = null, $code = 0, $previous = null ) {
		return parent::__construct(
			esc_html__(
				'Your WordPress server failed trying to send a request to the Font Awesome API server.',
				'font-awesome'
			),
			$code,
			$previous
		);
	}
}

/**
 * Thrown when the query endpoint on the Font Awesome API server responds with
 * an unexpected schema. This probably indicates either a programming error
 * in the API server, or a breaking change and this plugin code is out of date.
 *
 * @since 4.0.0
 */
class ApiResponseException extends FontAwesome_ServerException {
	/**
	 * Internal use only.
	 *
	 * @ignore
	 * @internal
	 */
	public function __construct( $message = null, $code = 0, $previous = null ) {
		return parent::__construct(
			esc_html__(
				'An unexpected response was received from the Font Awesome API server.',
				'font-awesome'
			),
			$code,
			$previous
		);
	}
}

/**
 * Thrown when there's a failure to write a transient for storing the Font Awesome
 * releases metadata.
 *
 * @since 4.0.0
 */
class ReleaseProviderStorageException extends FontAwesome_ServerException {
	/**
	 * Internal use only.
	 *
	 * @ignore
	 * @internal
	 */
	public function __construct( $message = null, $code = 0, $previous = null ) {
		return parent::__construct(
			esc_html__(
				'Something when wrong when we tried to store the list of available Font Awesome versions in your WordPress database.',
				'font-awesome'
			),
			$code,
			$previous
		);
	}
}

/**
 * Thrown when the plugin expects release metadata to be present but isn't for some reason.
 *
 * @since 4.0.0
 */
class ReleaseMetadataMissingException extends FontAwesome_ServerException {
	/**
	 * Internal use only.
	 *
	 * @ignore
	 * @internal
	 */
	public function __construct( $message = null, $code = 0, $previous = null ) {
		return parent::__construct(
			esc_html__(
				'Somehow, we\'re missing the list of available Font Awesome versions. Try deactivating and re-activating the Font Awesome plugin.',
				'font-awesome'
			),
			$code,
			$previous
		);
	}
}

/**
 * Thrown when attempting front-end page load logic and the options configuration
 * is invalid. This should never happen, since only valid options configurations
 * should ever be stored. If it is thrown it probably means that either there's
 * a programming error in this plugin, or that the state of database has been
 * changed between the time that options would have been valid upon saving and
 * the time that the page load occurs and those options are found to be invalid.
 *
 * @since 4.0.0
 */
class ConfigCorruptionException extends FontAwesome_ServerException {
	/**
	 * Internal use only.
	 *
	 * @ignore
	 * @internal
	 */
	public function __construct( $message = null, $code = 0, $previous = null ) {
		return parent::__construct(
			esc_html__(
				'When trying to load Font Awesome, the plugin\'s configuration was invalid. Try deactivating, uninstalling, and re-activating the Font Awesome plugin.',
				'font-awesome'
			),
			$code,
			$previous
		);
	}
}

/**
 * Thrown when the conflict detection scanner posts data to a REST endpoint and
 * the data has an invalid schema. This would probably indicate a programming
 * error in this plugin.
 *
 * @since 4.0.0
 */
class ConflictDetectionSchemaException extends FontAwesome_ClientException {
	/**
	 * Internal use only.
	 *
	 * @ignore
	 * @internal
	 */
	public function __construct( $message = null, $code = 0, $previous = null ) {
		return parent::__construct(
			esc_html__(
				'Inconceivable! Somehow the conflict detection information got garbled into something we can\'t understand.',
				'font-awesome'
			),
			$code,
			$previous
		);
	}
}

/**
 * Thrown when there's a failure to store conflict detection data as a transient.
 *
 * @since 4.0.0
 */
class ConflictDetectionStorageException extends FontAwesome_ServerException {
	/**
	 * Internal use only.
	 *
	 * @ignore
	 * @internal
	 */
	public function __construct( $message = null, $code = 0, $previous = null ) {
		return parent::__construct(
			esc_html__(
				'We were not able to save conflict detection data to your WordPress database.',
				'font-awesome'
			),
			$code,
			$previous
		);
	}
}

/**
 * Indicates that an incorrect array schema has been provided by a registerd client.
 *
 * See the `$client preferences` parameter schema for {@see FontAwesome::register()}.
 *
 * @since 4.0.0
 */
class ClientPreferencesSchemaException extends FontAwesome_ServerException {}

/**
 * Indicates a problem during upgrade process.
 *
 * @since 4.0.0
 */
class UpgradeException extends FontAwesome_ServerException {
	/**
	 * Internal use only.
	 *
	 * @internal
	 * @ignore
	 */
	public static function main_option_delete() {
		return new static(
			esc_html__(
				'Failed during upgrade when trying to delete the main Font Awesome option.',
				'font-awesome'
			)
		);
	}
}
