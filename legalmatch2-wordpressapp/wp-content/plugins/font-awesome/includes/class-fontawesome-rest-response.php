<?php
/**
 * Custom handling for FontAwesome REST responses.
 */
namespace FortAwesome;

use \WP_REST_Response;

/**
 * Handles FontAwesome-specific enhancements to a REST response.
 */
class FontAwesome_REST_Response extends WP_REST_Response {
	/**
	 * This header will be present on any response that is an instance
	 * of this class.
	 *
	 * Checking for the presence of this header is one way to validate that
	 * there hasn't been some premature output from the PHP process that has
	 * undermined the HTTP response.
	 *
	 * @since 4.0.0
	 */
	const CONFIRMATION_HEADER = 'FontAwesome-Confirmation';

	/**
	 * Internal use only.
	 *
	 * @ignore
	 * @internal
	 */
	public function __construct( $data = null, $status = 200, $headers = array() ) {
		return parent::__construct(
			$data,
			$status,
			array_merge(
				array(
					self::CONFIRMATION_HEADER => 1,
				),
				$headers
			)
		);
	}
}
