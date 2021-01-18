<?php
namespace FortAwesome;

require_once trailingslashit( FONTAWESOME_DIR_PATH ) . 'includes/class-fontawesome-exception.php';

/**
 * Class to wrap a closure for use with WordPress action hook callbacks.
 *
 * Internal use only.
 *
 * @internal
 * @ignore
 */
class FontAwesome_Command {
	/**
	 * Internal only.
	 *
	 * @internal
	 * @ignore
	 */
	protected $cmd;

	/**
	 * Internal only.
	 *
	 * @internal
	 * @ignore
	 */
	public function __construct( $cmd ) {
		$this->cmd = $cmd;
	}

	/**
	 * Internal only.
	 *
	 * @internal
	 * @ignore
	 */
	public function run( ...$params ) {
		return \call_user_func( $this->cmd, ...$params );
	}
}
