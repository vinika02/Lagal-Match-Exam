<?php
namespace FortAwesome;

/**
 * Class to wrap a set of resources to be enqueued, along with the version.
 *
 * Internal use only, not part of this plugin's public API.
 *
 * @internal
 * @ignore
 */
class FontAwesome_ResourceCollection {
	protected $resources;
	protected $version;

	public function __construct( $version, $resources ) {
		$this->version   = $version;
		$this->resources = $resources;
	}

	public function resources() {
		return $this->resources;
	}

	public function version() {
		return $this->version;
	}
}
