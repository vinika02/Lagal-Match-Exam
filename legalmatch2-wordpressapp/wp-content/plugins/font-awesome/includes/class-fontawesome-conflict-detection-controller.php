<?php
namespace FortAwesome;

require_once trailingslashit( FONTAWESOME_DIR_PATH ) . 'includes/class-fontawesome-exception.php';
require_once trailingslashit( FONTAWESOME_DIR_PATH ) . 'includes/class-fontawesome-rest-response.php';

use \WP_REST_Controller, \WP_Error, \Error, \Exception;

/**
 * REST Controller for managing data on the FontAwesome::CONFLICT_DETECTION_OPTIONS_KEY.
 *
 * Internal use only, not part of this plugin's public API.
 *
 * @ignore
 * @internal
 */
class FontAwesome_Conflict_Detection_Controller extends WP_REST_Controller {
	// phpcs:ignore Generic.Commenting.DocComment.MissingShort
	/**
	 * @ignore
	 */
	private $plugin_slug = null;

	// phpcs:ignore Generic.Commenting.DocComment.MissingShort
	/**
	 * @ignore
	 */
	protected $namespace = null;

	// phpcs:ignore Generic.Commenting.DocComment.MissingShort
	/**
	 * @ignore
	 */
	protected $valid_attrs = array( 'type', 'technology', 'href', 'src', 'innerText', 'tagName' );

	// phpcs:ignore Generic.Commenting.DocComment.MissingShort
	/**
	 * @ignore
	 */
	public function __construct( $plugin_slug, $namespace ) {
		$this->plugin_slug = $plugin_slug;
		$this->namespace   = $namespace;
	}

	// phpcs:ignore Generic.Commenting.DocComment.MissingShort
	/**
	 * @ignore
	 */
	public function register_routes() {
		$route_base = 'conflict-detection';

		register_rest_route(
			$this->namespace,
			'/' . $route_base . '/until',
			array(
				array(
					'methods'             => 'PUT',
					'callback'            => array( $this, 'update_detect_conflicts_until' ),
					'permission_callback' => function() {
						return current_user_can( 'manage_options' ); },
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $route_base . '/conflicts',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'report_conflicts' ),
					'permission_callback' => function() {
						return current_user_can( 'manage_options' ); },
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $route_base . '/conflicts',
			array(
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'delete_conflicts' ),
					'permission_callback' => function() {
						return current_user_can( 'manage_options' ); },
					'args'                => array(),
				),
			)
		);

		/**
		 * The given blocklist, an array of md5s, will become the new blocklist,
		 * for each md5 in the given array that already exists as a key in
		 * the unregisteredClients array.
		 */
		register_rest_route(
			$this->namespace,
			'/' . $route_base . '/conflicts/blocklist',
			array(
				array(
					'methods'             => 'PUT',
					'callback'            => array( $this, 'update_blocklist' ),
					'permission_callback' => function() {
						return current_user_can( 'manage_options' ); },
					'args'                => array(),
				),
			)
		);
	}

	/**
	 * Report conflicts. Adds and/or updates unregisteredClients
	 *
	 * The response will have an HTTP 204 status if the request results in no changes.
	 * If changes are made, the response will have an HTTP 200 status, and
	 * the response body will include just the new status of the
	 * unregisteredClients (not the entire conflict-detection option data).
	 *
	 * If the plugin is not currently in conflict detection mode, this
	 * returns an HTTP 404 status.
	 *
	 * @param WP_REST_Request $request the request.
	 * @return FontAwesome_REST_Response
	 */
	// phpcs:ignore Squiz.Commenting.FunctionCommentThrowTag.Missing
	public function report_conflicts( $request ) {
		try {
			if ( ! fa()->detecting_conflicts() ) {
				return new FontAwesome_REST_Response( null, 404 );
			}

			$item = $this->prepare_unregistered_clients_for_database( $request );

			$prev_option = get_option(
				FontAwesome::CONFLICT_DETECTION_OPTIONS_KEY,
				FontAwesome::DEFAULT_CONFLICT_DETECTION_OPTIONS
			);

			$prev_option_unregistered_clients = (
				isset( $prev_option['unregisteredClients'] )
				&& is_array( $prev_option['unregisteredClients'] )
			)
				? $prev_option['unregisteredClients']
				: array();

			$new_option_unregistered_clients = array_merge(
				$prev_option_unregistered_clients,
				$item
			);

			if ( $this->unregistered_clients_array_has_changes( $prev_option_unregistered_clients, $new_option_unregistered_clients ) ) {
				// Update only the unregisteredClients key, leaving any other keys unchanged.
				$new_option_value = array_merge(
					$prev_option,
					array(
						'unregisteredClients' => $new_option_unregistered_clients,
					)
				);

				if ( update_option( FontAwesome::CONFLICT_DETECTION_OPTIONS_KEY, $new_option_value ) ) {
					return new FontAwesome_REST_Response( $new_option_unregistered_clients, 200 );
				} else {
					throw new ConflictDetectionStorageException();
				}
			} else {
				// No change.
				return new FontAwesome_REST_Response( null, 204 );
			}
		} catch ( FontAwesome_ServerException $e ) {
			return new FontAwesome_REST_Response( wpe_fontawesome_server_exception( $e ), 500 );
		} catch ( FontAwesome_Exception $e ) {
			return new FontAwesome_REST_Response( wpe_fontawesome_client_exception( $e ), 400 );
		} catch ( Exception $e ) {
			return new FontAwesome_REST_Response( wpe_fontawesome_unknown_error( $e ), 500 );
		} catch ( Error $e ) {
			return new FontAwesome_REST_Response( wpe_fontawesome_unknown_error( $e ), 500 );
		}
	}

	/**
	 * Remove (forget) some specified previously detected conflicts.
	 *
	 * The request body should contain an array of md5 values to be
	 * deleted. Any unrecognized md5s are ignored.
	 *
	 * The response will have an HTTP 204 status if the request results in no changes,
	 * otherwise it will include the new set of unregistered clients.
	 *
	 * If the plugin is not currently in conflict detection mode, this
	 * returns an HTTP 404 status.
	 *
	 * @param WP_REST_Request $request the request.
	 * @return FontAwesome_REST_Response
	 */
	// phpcs:ignore Squiz.Commenting.FunctionCommentThrowTag.Missing
	public function delete_conflicts( $request ) {
		try {
			$body = $request->get_json_params();

			if ( ! $this->is_array_of_md5( $body ) ) {
				throw new ConflictDetectionSchemaException();
			}

			$prev_option = get_option(
				FontAwesome::CONFLICT_DETECTION_OPTIONS_KEY,
				FontAwesome::DEFAULT_CONFLICT_DETECTION_OPTIONS
			);

			$prev_option_unregistered_clients = (
				isset( $prev_option['unregisteredClients'] )
				&& is_array( $prev_option['unregisteredClients'] )
			)
				? $prev_option['unregisteredClients']
				: array();

			// Make a copy.
			$new_option_unregistered_clients = array_merge(
				array(),
				$prev_option_unregistered_clients
			);

			foreach ( $body as $md5 ) {
				unset( $new_option_unregistered_clients[ $md5 ] );
			}

			if ( $this->unregistered_clients_array_has_changes( $prev_option_unregistered_clients, $new_option_unregistered_clients ) ) {
				// Update only the unregisteredClients key, leaving any other keys unchanged.
				$new_option_value = array_merge(
					$prev_option,
					array(
						'unregisteredClients' => $new_option_unregistered_clients,
					)
				);

				if ( update_option( FontAwesome::CONFLICT_DETECTION_OPTIONS_KEY, $new_option_value ) ) {
					return new FontAwesome_REST_Response( $new_option_unregistered_clients, 200 );
				} else {
					throw new ConflictDetectionStorageException();
				}
			} else {
				// No change.
				return new FontAwesome_REST_Response( null, 204 );
			}
		} catch ( FontAwesome_ServerException $e ) {
			return new FontAwesome_REST_Response( wpe_fontawesome_server_exception( $e ), 500 );
		} catch ( FontAwesome_Exception $e ) {
			return new FontAwesome_REST_Response( wpe_fontawesome_client_exception( $e ), 400 );
		} catch ( Exception $e ) {
			return new FontAwesome_REST_Response( wpe_fontawesome_unknown_error( $e ), 500 );
		} catch ( Error $e ) {
			return new FontAwesome_REST_Response( wpe_fontawesome_unknown_error( $e ), 500 );
		}
	}

	/**
	 * Update the value of detectConflictsUntil to start/stop conflict detection.
	 *
	 * @throws ConflictDetectionSchemaException
	 * @throws ConflictDetectionStorageException
	 * @param WP_REST_Request $request the request.
	 * @return FontAwesome_REST_Response
	 */
	public function update_detect_conflicts_until( $request ) {
		try {
			$body = $request->get_body();

			$new_value = intval( $body );

			if ( 0 === $new_value && '0' !== $body ) {
				throw new ConflictDetectionSchemaException();
			}

			$prev_option = get_option(
				FontAwesome::CONFLICT_DETECTION_OPTIONS_KEY,
				FontAwesome::DEFAULT_CONFLICT_DETECTION_OPTIONS
			);

			$prev_option_detect_conflicts_until = (
				isset( $prev_option['detectConflictsUntil'] ) &&
				is_integer( $prev_option['detectConflictsUntil'] )
			)
				? $prev_option['detectConflictsUntil']
				: null;

			if ( $prev_option_detect_conflicts_until !== $new_value ) {
				// Update only the detectConflictsUntil key, leaving any other keys unchanged.
				$new_option_value = array_merge(
					$prev_option,
					array( 'detectConflictsUntil' => $new_value )
				);

				if ( update_option( FontAwesome::CONFLICT_DETECTION_OPTIONS_KEY, $new_option_value ) ) {
					return new FontAwesome_REST_Response( array( 'detectConflictsUntil' => $new_value ), 200 );
				} else {
					throw new ConflictDetectionStorageException();
				}
			} else {
				// No change.
				return new FontAwesome_REST_Response( null, 204 );
			}
		} catch ( FontAwesome_ServerException $e ) {
			return new FontAwesome_REST_Response( wpe_fontawesome_server_exception( $e ), 500 );
		} catch ( FontAwesome_Exception $e ) {
			return new FontAwesome_REST_Response( wpe_fontawesome_client_exception( $e ), 400 );
		} catch ( Exception $e ) {
			return new FontAwesome_REST_Response( wpe_fontawesome_unknown_error( $e ), 500 );
		} catch ( Error $e ) {
			return new FontAwesome_REST_Response( wpe_fontawesome_unknown_error( $e ), 500 );
		}
	}

	/**
	 * Updates which unregistered will be blocked.
	 *
	 * The body of the request is an array of md5 values identifying those
	 * unregistered clients that will be blocked as a result of this request.
	 * Any unrecognized md5s will be ignored.
	 *
	 * Internal use only, not part of this plugin's public API.
	 *
	 * @throws ConflictDetectionSchemaException
	 * @throws ConflictDetectionStorageException
	 * @ignore
	 * @internal
	 */
	public function update_blocklist( $request ) {
		try {
			$body = $request->get_json_params();

			if ( ! $this->is_array_of_md5( $body ) ) {
				throw new ConflictDetectionSchemaException();
			}

			$prev_option = get_option(
				FontAwesome::CONFLICT_DETECTION_OPTIONS_KEY,
				FontAwesome::DEFAULT_CONFLICT_DETECTION_OPTIONS
			);

			$prev_option_unregistered_clients = (
				isset( $prev_option['unregisteredClients'] )
				&& is_array( $prev_option['unregisteredClients'] )
			)
				? $prev_option['unregisteredClients']
				: array();

			// Make a copy.
			$new_option_unregistered_clients = array_merge(
				array(),
				$prev_option_unregistered_clients
			);

			foreach ( array_keys( $new_option_unregistered_clients ) as $md5 ) {
				if ( in_array( $md5, $body, true ) ) {
					$new_option_unregistered_clients[ $md5 ]['blocked'] = true;
				} else {
					$new_option_unregistered_clients[ $md5 ]['blocked'] = false;
				}
			}

			if ( $this->unregistered_clients_array_has_changes( $prev_option_unregistered_clients, $new_option_unregistered_clients ) ) {
				// Update only the unregisteredClients key, leaving any other keys unchanged.
				$new_option_value = array_merge(
					$prev_option,
					array(
						'unregisteredClients' => $new_option_unregistered_clients,
					)
				);

				if ( update_option( FontAwesome::CONFLICT_DETECTION_OPTIONS_KEY, $new_option_value ) ) {
					return new FontAwesome_REST_Response( fa()->blocklist(), 200 );
				} else {
					throw new ConflictDetectionStorageException();
				}
			} else {
				// No change.
				return new FontAwesome_REST_Response( null, 204 );
			}
		} catch ( FontAwesome_ServerException $e ) {
			return new FontAwesome_REST_Response( wpe_fontawesome_server_exception( $e ), 500 );
		} catch ( FontAwesome_Exception $e ) {
			return new FontAwesome_REST_Response( wpe_fontawesome_client_exception( $e ), 400 );
		} catch ( Exception $e ) {
			return new FontAwesome_REST_Response( wpe_fontawesome_unknown_error( $e ), 500 );
		} catch ( Error $e ) {
			return new FontAwesome_REST_Response( wpe_fontawesome_unknown_error( $e ), 500 );
		}
	}

	/**
	 * Reads a json body of the given Request, validates it, and turns it
	 * into a valid value for the unregisteredClients key of the conflict detection
	 * option.
	 *
	 * @internal
	 * @ignore
	 * @throws ConflictDetectionSchemaException
	 * @return array
	 */
	protected function prepare_unregistered_clients_for_database( $request ) {
		$body = $request->get_json_params();

		if ( ! \is_array( $body ) || count( $body ) === 0 ) {
			throw new ConflictDetectionSchemaException();
		}

		$validated = array();

		foreach ( $body as $md5 => $attrs ) {
			if ( ! is_string( $md5 ) || ! strlen( $md5 ) === 32 ) {
				throw new ConflictDetectionSchemaException();
			}

			if ( ! is_array( $attrs ) ) {
				throw new ConflictDetectionSchemaException();
			}

			$validated[ $md5 ] = array();

			foreach ( $attrs as $key => $value ) {
				if ( in_array( $key, $this->valid_attrs, true ) ) {
					$validated[ $md5 ][ $key ] = $value;
				}
			}
		}

		return $validated;
	}

	protected function unregistered_clients_array_has_changes( $old, $new ) {
		if ( ! is_array( $old ) ) {
			return true;
		}

		if ( count( array_diff_key( $old, $new ) ) > 0 || count( array_diff_key( $new, $old ) ) > 0 ) {
			return true;
		} else {
			foreach ( $old as $key => $value ) {
				if ( count( array_diff_assoc( $old[ $key ], $new[ $key ] ) ) > 0 ) {
					return true;
				}
			}
			foreach ( $new as $key => $value ) {
				if ( count( array_diff_assoc( $new[ $key ], $old[ $key ] ) ) > 0 ) {
					return true;
				}
			}
			return false;
		}
	}

	protected function is_array_of_md5( $data ) {
		return \is_array( $data ) &&
			count( $data ) === 0 ||
			(
				0 === count(
					array_filter(
						$data,
						function( $md5 ) {
							return ! is_string( $md5 ) || strlen( $md5 ) !== 32;
						}
					)
				)
			);
	}
}
