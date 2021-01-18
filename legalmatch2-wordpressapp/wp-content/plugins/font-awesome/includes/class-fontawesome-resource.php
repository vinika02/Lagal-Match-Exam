<?php
namespace FortAwesome;

/**
 * Class to provide structure and wrapping around the source URI and integrity key
 * to be used when enqueuing a script or style resource.
 */
class FontAwesome_Resource {
	protected $source;
	protected $integrity_key;

	public function __construct( $source, $integrity_key ) {
		$this->source        = $source;
		$this->integrity_key = $integrity_key;
	}

	public function source() {
		return $this->source; }
	public function integrity_key() {
		return $this->integrity_key; }
}
