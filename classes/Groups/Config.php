<?php
namespace BP_REST\Groups;

use BP_REST\Config as Base;

/**
 * Configuration class for the Groups REST API.
 *
* @since 0.1
 */
class Config extends Base {
	/**
	 * Supported API versions.
	 *
	 * @todo Fill this in once we're ready to add a controller.
	 *
	 * @since 0.1
	 */
	protected static $versions = array( 'v1' );

	/**
	 * Register groups REST fields.
	 *
	 * @since 0.1
	 */
	public static function register_rest_fields() {
		// Members
		register_rest_field( 'member', 'groups', array(
			'get_callback' => function( $restdata, $field_name, $request ) {
				$data = array(
					'count' => groups_total_groups_for_user( $restdata['id'] )
				);

				return $data;
			},
			'schema' => array(
				'description' => __( 'Select group data for the member.', 'buddypress' ),
				'type'        => 'object'
			),
		) );
	}

	/**
	 * Register groups meta fields.
	 *
	 * This should probably be in core... maybe one day!
	 *
	 * @since 0.1
	 */
	public static function register_meta() {
		register_meta( 'group', 'total_member_count', array(
			'type' => 'integer',
			'description' => __( 'Total member count for the group', 'buddypress' ),
			'single' => true,
			'sanitize_callback' => 'absint',
			'auth_callback' => '__return_false',
			'show_in_rest' => true
		) );
	}
}