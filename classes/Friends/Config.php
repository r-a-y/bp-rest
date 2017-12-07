<?php
namespace BP_REST\Friends;

use BP_REST\Config as Base;

class Config extends Base {
	/**
	 * Supported API versions.
	 *
	 * @todo Fill this in once we're ready to add a controller.
	 *
	 * @since 0.1
	 */
	protected static $versions = array();

	/**
	 * Register friends REST fields.
	 *
	 * @since 0.1
	 */
	public static function register_rest_fields() {
		// Members
		register_rest_field( 'member', 'friends', array(
			'get_callback' => function( $restdata, $field_name, $request ) {
				$data = array(
					'count' => friends_get_total_friend_count( $restdata['id'] )
				);

				return $data;
			},
			'schema' => array(
				'description' => __( 'Select friend data for the member. Currently includes friend count.', 'buddypress' ),
				'type'        => 'object'
			),
		) );
	}
}