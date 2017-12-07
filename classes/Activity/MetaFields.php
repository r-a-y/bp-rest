<?php

namespace BP_REST\Activity;

use WP_REST_Meta_Fields as Meta;

/**
 * Core class used to manage meta values for activity items via the REST API.
 *
 * @since 0.1
 *
 * @see WP_REST_Meta_Fields
 */
class MetaFields extends Meta {

	/**
	 * Retrieves the object meta type.
	 *
	 * @since 0.1
	 * @access protected
	 *
	 * @return string The meta type.
	 */
	protected function get_meta_type() {
		return 'activity';
	}

	/**
	 * Retrieves the type for register_rest_field().
	 *
	 * @since 0.1
	 * @access public
	 *
	 * @return string The REST field type.
	 */
	public function get_rest_field_type() {
		return 'activity';
	}
}