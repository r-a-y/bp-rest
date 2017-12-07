<?php
namespace BP_REST;
use WP_REST_Controller;

/**
 * Base REST controller for BuddyPress.
 *
 * Meant to be extended by BuddyPress components.
 *
 * @since 0.1
 */
class Controller extends WP_REST_Controller {
	/**
	 * API version.
	 *
	 * This is intentionally commented out so fatal errors are thrown if it isn't
	 * defined in your extended class.
	 *
	 * @access protected
	 * @var string
	 */
	//protected static $version = 'v1';

	/**
	 * Namespace prefix used by BuddyPress.
	 *
	 * @see BP_REST\Controller::get_namespace_prefix()
	 *
	 * @since 0.1
	 * @var string
	 */
	protected static $namespace_prefix = 'bp';

	/** 
	 * Static initializer.
	 *
	 * @since 0.1
	 */
	public static function init() {
		return (new static())->register_routes();
	}

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 * @access public
	 */
	final public function __construct() {
		$this->namespace = self::get_namespace_prefix() . '/' . static::$version;

		$this->custom_hooks();
	}

	/**
	 * Get namespace prefix.
	 *
	 * @since 0.1
	 */
	public static function get_namespace_prefix() {
		/**
		 * Filters the REST namespace prefix used by BuddyPress.
		 *
		 * @since 0.1
		 *
		 * @param string
		 */
		return apply_filters( 'bp_rest_namespace_prefix', self::$namespace_prefix );
	}

	protected function custom_hooks() {}
}