<?php
/**
 * This module is not considered part of the public API, only internal.
 * Any data or functionality that it produces should be exported by the
 * main FontAwesome class and the API documented and semantically versioned there.
 */
namespace FortAwesome;

require_once trailingslashit( FONTAWESOME_DIR_PATH ) . 'includes/class-fontawesome-api-settings.php';
require_once trailingslashit( FONTAWESOME_DIR_PATH ) . 'includes/class-fontawesome-exception.php';
require_once trailingslashit( FONTAWESOME_DIR_PATH ) . 'includes/class-fontawesome-rest-response.php';

use \WP_REST_Controller, \WP_Error, \Exception;

/**
 * Controller class for REST endpoint
 *
 * @internal
 * @ignore
 */
class FontAwesome_Config_Controller extends WP_REST_Controller {

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
	public function __construct( $plugin_slug, $namespace ) {
		$this->plugin_slug = $plugin_slug;
		$this->namespace   = $namespace;
	}

	// phpcs:ignore Generic.Commenting.DocComment.MissingShort
	/**
	 * @ignore
	 */
	public function register_routes() {
		$route_base = 'config';

		register_rest_route(
			$this->namespace,
			'/' . $route_base,
			array(
				array(
					'methods'             => 'PUT',
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => function() {
						return current_user_can( 'manage_options' ); },
					'args'                => array(),
				),
			)
		);
	}

	// phpcs:ignore Generic.Commenting.DocComment.MissingShort
	/**
	 * @ignore
	 */
	protected function build_item( $fa ) {
		$preference_registration_error = null;

		try {
			fa()->gather_preferences();
		} catch ( PreferenceRegistrationException $e ) {
			$preference_registration_error = wpe_fontawesome_server_exception( $e );
		}

		$item = array(
			'options'   => $fa->options(),
			'conflicts' => $fa->conflicts_by_option(),
		);

		if ( ! is_null( $preference_registration_error ) ) {
			$item['error'] = $preference_registration_error;
		}

		return $item;
	}

	/**
	 * Update the singleton resource.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return FontAwesome_REST_Response
	 */
	public function update_item( $request ) {
		try {
			$body = $request->get_json_params();

			$given_options = isset( $body['options'] ) ? $body['options'] : null;

			$api_token = isset( $given_options['apiToken'] ) ? $given_options['apiToken'] : null;

			if ( is_string( $api_token ) ) {
				// We're adding an api_token.
				$api_settings = FontAwesome_API_Settings::reset();
				$api_settings->set_api_token( $api_token );
				$api_settings->request_access_token();
			} elseif ( boolval( fa_api_settings()->api_token() ) && ! boolval( $api_token ) ) {
				// We're removing an existing API Token.
				fa_api_settings()->remove();

				/**
				 * We also need to change the version to one that would be
				 * valid for a CDN configuration.
				 */
				$given_options['version'] = fa()->latest_version();
			}

			$db_item = $this->prepare_item_for_database( $given_options );

			update_option(
				FontAwesome::OPTIONS_KEY,
				$db_item
			);

			$return_data = $this->build_item( fa() );
			return new FontAwesome_REST_Response( $return_data, 200 );
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
	 * Filters the incoming data, determines what should actually
	 * be stored in the database, and ensures that it's valid.
	 *
	 * @internal
	 * @ignore
	 * @param array $given_options the options from the request body
	 * @throws ConfigSchemaException
	 * @return array The item to store on the options key
	 */
	protected function prepare_item_for_database( $given_options ) {
		// start with a copy of the defaults and just override them indivually.
		$item = array_merge( array(), FontAwesome::DEFAULT_USER_OPTIONS );

		/**
		 * The apiToken is handled specially.
		 * We only store a boolean value indicating whether and apiToken
		 * has been stored. It's the responsibility of the calling code
		 * to store the actual API Token appropriately.
		 */
		$api_token        = isset( $given_options['apiToken'] ) && boolval( $given_options['apiToken'] );
		$item['apiToken'] = $api_token;

		/**
		 * A kitToken is handled specially.
		 * If one is provided, but there's no API token, then that is invalid.
		 */
		if ( isset( $given_options['kitToken'] ) && is_string( $given_options['kitToken'] ) ) {
			if ( $api_token ) {
				$item['kitToken'] = $given_options['kitToken'];
			} else {
				throw ConfigSchemaException::kit_token_no_api_token();
			}
		}

		/**
		 * For the following options, if they are provided at all, we just
		 * use that to override the default.
		 */
		if ( isset( $given_options['usePro'] ) ) {
			$item['usePro'] = $given_options['usePro'];
		}
		if ( isset( $given_options['v4Compat'] ) ) {
			$item['v4Compat'] = $given_options['v4Compat'];
		}
		if ( isset( $given_options['technology'] ) ) {
			$item['technology'] = $given_options['technology'];
		}
		if ( isset( $given_options['pseudoElements'] ) ) {
			$item['pseudoElements'] = $given_options['pseudoElements'];
		}
		if ( isset( $given_options['usePro'] ) ) {
			$item['usePro'] = $given_options['usePro'];
		}

		$version_is_symbolic_latest = isset( $given_options['version'] )
			&& 'latest' === $given_options['version'];

		$version_is_concrete = isset( $given_options['version'] )
			&& 1 === preg_match( '/[0-9]+\.[0-9]+/', $given_options['version'] );

		/**
		 * The pseudoElements option is handled specially. If technology
		 * is webfont, pseudoElements must be true.
		 */
		if ( 'webfont' === $item['technology'] && ! $item['pseudoElements'] ) {
			throw ConfigSchemaException::webfont_always_enables_pseudo_elements();
		}

		/**
		 * The version is handled specially.
		 *
		 * If this is a non-kit config, then the version must be concrete,
		 * a major.minor.patch version like 5.12.0.
		 *
		 * If this is a kit-based config, then the version must either be
		 * concrete or the exact, case-sensitive, string 'latest'.
		 */
		if ( isset( $given_options['kitToken'] ) && is_string( $given_options['kitToken'] ) && $version_is_symbolic_latest ) {
			$item['version'] = 'latest';
		} elseif ( $version_is_concrete ) {
			$item['version'] = $given_options['version'];
		} else {
			throw ConfigSchemaException::concrete_version_expected();
		}

		if (
			$version_is_concrete &&
			version_compare( '5.1.0', $item['version'], '>' ) &&
			boolval( $item['v4Compat'] ) &&
			'webfont' === $item['technology']
		) {
			throw ConfigSchemaException::webfont_v4compat_introduced_later();
		}

		return $item;
	}

	/**
	 * Allows a test subclass to mock the release provider.
	 *
	 * @ignore
	 */
	protected function release_provider() {
		return fa_release_provider();
	}
}
