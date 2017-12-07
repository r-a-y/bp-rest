<?php
namespace BP_REST\Members;

use BP_REST\Config as Base;

class Config extends Base {
	/**
	 * Supported API versions.
	 *
	 * @since 0.1
	 */
	protected static $versions = array( 'v1' );
}