<?php
namespace BP_REST;

abstract class Config {
	/**
	 * Set up supported API versions.
	 *
	 * @since 0.1
	 *
	 * @var array
	 */
	protected static $versions = array();

	/**
	 * Constructor. Intentionally left blank.
	 *
	 * @since 0.1
	 */
	protected function __construct() {}

	/**
	 * Utility method to fetch versions.
	 *
	 * @since 0.1
	 */
	public static function get_versions() {
		return static::$versions;
	}

	/** PLUGGABLE ***********************************************************/

	/**
	 * Register REST fields in this method.
	 *
	 * @since 0.1
	 */
	public static function register_rest_fields() {}

	/**
	 * Register meta fields in this method.
	 *
	 * @since 0.1
	 */
	public static function register_meta() {}
}