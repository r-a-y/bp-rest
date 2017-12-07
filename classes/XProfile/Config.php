<?php
namespace BP_REST\XProfile;

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
	 * Register XProfile REST fields.
	 *
	 * @since 0.1
	 */
	public static function register_rest_fields() {
		// Members
		register_rest_field( 'member', 'xprofile', array(
			'get_callback' => function( $restdata, $field_name, $request ) {
				$data = array();
				$data['groups'] = array();

				// Remove filters that mess with xprofile data.
				remove_filter( 'bp_get_the_profile_field_value', 'xprofile_filter_format_field_value_by_type', 8, 3 );
				remove_filter( 'bp_get_the_profile_field_value', 'xprofile_filter_link_profile_data', 9, 3 );
				remove_filter( 'bp_get_the_profile_field_value', 'make_clickable' );
				remove_filter( 'bp_get_the_profile_field_value', 'wpautop' );

				if ( bp_has_profile( array( 'user_id' => $restdata['id'] ) ) ) {
					while ( bp_profile_groups() ) : bp_the_profile_group();
						$data['groups'][ bp_get_the_profile_group_id() ] = array(
							'name'   => bp_get_the_profile_group_name(),
							'fields' => array()
						);

						if ( bp_profile_group_has_fields() ) : while ( bp_profile_fields() ) : bp_the_profile_field();
							$data['groups'][ bp_get_the_profile_group_id() ][ 'fields' ][ bp_get_the_profile_field_id() ] = array(
								'name'  => bp_get_the_profile_field_name(),
								'value' => bp_get_the_profile_field_value()
							);
						endwhile; endif;
					endwhile;
				}

				return $data;
			},
			'schema' => array(
				'description' => __( 'Extended profile data for the member.', 'buddypress' ),
				'type'        => 'object'
			),
		) );
	}
}