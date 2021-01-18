<?php
namespace FortAwesome;

require_once trailingslashit( FONTAWESOME_DIR_PATH ) . 'includes/class-fontawesome-rest-response.php';
use \WP_REST_Controller, \WP_Error, \Error, \Exception;

/**
 * Controller class for REST endpoint
 *
 * Internal use only, not part of this plugin's public API.
 *
 * @ignore
 * @internal
 */
class FontAwesome_V3Deprecation_Controller extends WP_REST_Controller {

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

		$this->namespace = $namespace;
	}

	// phpcs:ignore Generic.Commenting.DocComment.MissingShort
	/**
	 * @ignore
	 */
	public function register_routes() {
		$route_base = 'v3deprecation';

		register_rest_route(
			$this->namespace,
			'/' . $route_base,
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => function() {
						return current_user_can( 'manage_options' ); },
					'args'                => array(),
				),
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

	/**
	 * Internal use only.
	 *
	 * @internal
	 * @ignore
	 */
	protected function build_item() {
		return array(
			'v3DeprecationWarning' => fa()->get_v3deprecation_warning_data(),
		);
	}

	/**
	 * Get the deprecation data, a singleton resource.
	 *
	 * Internal use only, not part of this plugin's public API.
	 *
	 * @ignore
	 * @internal
	 * @param WP_REST_Request $request Full data about the request.
	 * @return FontAwesome_REST_Response
	 */
	public function get_item( $request ) {
		try {
			$data = $this->build_item();

			return new FontAwesome_REST_Response( $data, 200 );
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
	 * Update the singleton resource.
	 *
	 * Internal use only, not part of this plugin's public API.
	 *
	 * @ignore
	 * @internal
	 * @param WP_REST_Request $request Full data about the request.
	 * @return FontAwesome_REST_Response
	 */
	public function update_item( $request ) {
		try {
			$item = $this->prepare_item_for_database( $request );

			if ( isset( $item['snooze'] ) && $item['snooze'] ) {
				fa()->snooze_v3deprecation_warning();
			}

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
	 * Internal use only.
	 *
	 * @internal
	 * @ignore
	 */
	protected function prepare_item_for_database( $request ) {
		$body = $request->get_json_params();
		return array_merge( array(), $body );
	}
}
