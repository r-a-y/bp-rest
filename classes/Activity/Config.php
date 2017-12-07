<?php
namespace BP_REST\Activity;

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
	 * Register activity REST fields.
	 *
	 * @since 0.1
	 */
	public static function register_rest_fields() {
		// Members
		register_rest_field( 'member', 'activity', array(
			'get_callback' => function( $restdata, $field_name, $request ) {
				$data = array(
					'latest_update' => bp_get_activity_latest_update( $restdata['id'] )
				);
				

				return $data;
			},
			'schema' => array(
				'description' => __( 'Select activity data for the member.', 'buddypress' ),
				'type'        => 'object'
			),
		) );
	}
}