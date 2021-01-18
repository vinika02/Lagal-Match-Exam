<?php
namespace FortAwesome;

use \WP_Error, \Exception;
require_once dirname( __FILE__ ) . '/../includes/class-fontawesome-command.php';

/**
 * Handle fatal errors
 *
 * @ignore
 * @internal
 */
function notify_admin_fatal_error( $e ) {
	if ( method_exists( 'FortAwesome\FontAwesome_Loader', 'emit_admin_error_output' ) ) {
		$command = new FontAwesome_Command(
			function() use ( $e ) {
				FontAwesome_Loader::emit_admin_error_output( $e );
			}
		);

		add_action(
			'admin_notices',
			array( $command, 'run' )
		);
	}
}

/**
 * Add diagnostic data relevant to failed requests.
 *
 * @ignore
 * @internal
 * @return WP_Error with additional data, if the argument is WP_Error, otherwise
 *   returns that argument unchanged.
 */
function add_failed_request_diagnostics( $e ) {
	if ( ! is_wp_error( $e ) ) {
		return $e;
	}

	$additional_diagnostics = '';

	if ( extension_loaded( 'curl' ) ) {
		$additional_diagnostics .= "curl loaded: true\n";

		if ( function_exists( 'curl_version' ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_version
			$curl_version = curl_version();

			$curl_version_keys_to_report = array(
				'version_number',
				'features',
				'ssl_version_number',
				'version',
				'host',
				'ssl_version',
			);

			foreach ( $curl_version_keys_to_report as $key ) {
				$additional_diagnostics .= array_key_exists( $key, $curl_version )
					? "curl $key: $curl_version[$key]\n"
					: "curl $key: (not available)\n";
			}
		} else {
			$additional_diagnostics .= "curl_version() not avaialble\n";
		}
	} else {
		$additional_diagnostics .= "curl loaded: false\n";
	}

	if ( extension_loaded( 'openssl' ) ) {
		$additional_diagnostics .= "openssl loaded: true\n";

		if ( function_exists( 'openssl_get_cipher_methods' ) ) {
			$additional_diagnostics .= 'openssl cipher methods: ' . implode( ',', openssl_get_cipher_methods() ) . "\n";
		} else {
			$additional_diagnostics .= "openssl_get_cipher_methods() not available\n";
		}
	} else {
		$additional_diagnostics .= "openssl loaded: false\n";
	}

	$e->add_data( $additional_diagnostics );

	return $e;
}

/**
 * Internal use only, not part of this plugin's public API.
 *
 * @internal
 * @ignore
 */
// phpcs:ignore Squiz.Commenting.FunctionCommentThrowTag.Missing
function build_wp_error( $e, $code ) {
	if ( is_a( $e, 'Error' ) || is_a( $e, 'Exception' ) ) {
		$previous = boolval( $e->getPrevious() )
			? build_wp_error( $e->getPrevious(), 'previous_exception' )
			: null;

		$current = new WP_Error(
			$code,
			$e->getMessage(),
			array(
				'trace' => $e->getTraceAsString(),
			)
		);

		if ( ! is_null( $previous ) ) {
			$current->add(
				$previous->get_error_code(),
				$previous->get_error_message(),
				$previous->get_error_data()
			);
		}

		if ( method_exists( $e, 'get_wp_error' ) ) {
			$wpe = $e->get_wp_error();

			if ( $wpe ) {
				$current->add(
					$wpe->get_error_code(),
					$wpe->get_error_message(),
					$wpe->get_error_data()
				);
			}
		}

		return $current;
	} else {
		try {
			$as_string = (
				method_exists( $e, '__toString' ) ||
				is_string( $e ) ||
				is_numeric( $e )
			) ? strval( $e ) : null;

			$message = 'Unexpected Thing, neither Error or Exception';

			if ( is_null( $as_string ) ) {
				$message .= ', which cannot be stringified.';
			} else {
				$message .= ", stringified: $as_string";
			}

			throw new Exception( $message );
		} catch ( Exception $e ) {
			return new WP_Error(
				$code,
				$e->getMessage(),
				array(
					'trace' => $e->getTraceAsString(),
				)
			);
		}
	}

}

/**
 * Internal use only, not part of this plugin's public API.
 *
 * @internal
 * @ignore
 */
function wpe_fontawesome_unknown_error( $e ) {
	return build_wp_error( $e, 'fontawesome_unknown_error' );
}

/**
 * Internal use only, not part of this plugin's public API.
 *
 * @internal
 * @ignore
 */
function wpe_fontawesome_client_exception( $e ) {
	return build_wp_error( $e, 'fontawesome_client_exception' );
}

/**
 * Internal use only, not part of this plugin's public API.
 *
 * @internal
 * @ignore
 */
function wpe_fontawesome_server_exception( $e ) {
	return build_wp_error( $e, 'fontawesome_server_exception' );
}
