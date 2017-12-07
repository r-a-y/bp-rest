<?php
namespace BP_REST\Members\v1;

//use BP_REST\Controller as Base;
use WP_REST_Users_Controller as Base;
use BP_REST\Controller as BP;
use WP_Error;

// Temporary to pass unit tests.
use WP_REST_User_Meta_Fields;

/**
 * Members controller.
 *
 * Special use case here as we extend the {@link WP_REST_Users_Controller} class,
 * rather than the {@link BP_Rest\Controller} class.  This is done because our
 * Members endpoint is close enough with WP's Users endpoint where we can
 * re-use most of WP's Users controller, but overriding various methods for
 * BuddyPress usage.
 *
 * @since 0.1
 */
class Controller extends Base {
	/**
	 * Members REST base.
	 *
	 * @since 0.1
	 * @access protected
	 * @var string
	 */
	protected $rest_base = 'members';

	/**
	 * API version.
	 *
	 * @since 0.1
	 * @access protected
	 * @var string
	 */
	protected $version = 'v1';

	/** 
	 * Static initializer.
	 *
	 * This is needed because we are extending WP_REST_Users_Controller and not
	 * BP_REST\Controller.
	 *
	 * @since 0.1
	 */
	public static function init() {
		return (new static())->register_routes();
	}

	/**
	 * Constructor.
	 *
	 * Overrides $namespace from {@link WP_REST_Users_Controller::__construct()}.
	 *
	 * @since 0.1
	 */
	public function __construct() {
		$this->namespace = BP::get_namespace_prefix() . '/' . $this->version;

		// Temporary to get get_item_schema() working...
		$this->meta = new WP_REST_User_Meta_Fields();
	}

	/**
	 * Retrieves the member's schema, conforming to JSON Schema.
	 *
	 * @since 0.1
	 * @access public
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/schema#',
			'title'      => 'member',
			'type'       => 'object',
			'properties' => array(
				'id'          => array(
					'description' => __( 'Unique identifier for the member.', 'buddypress' ),
					'type'        => 'integer',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
				'username'    => array(
					'description' => __( 'Login name for the member.', 'buddypress' ),
					'type'        => 'string',
					'context'     => array( 'edit' ),
					'required'    => true,
					'arg_options' => array(
						'sanitize_callback' => array( $this, 'check_username' ),
					),
				),
				'name'        => array(
					'description' => __( 'Display name for the member.', 'buddypress' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'email'       => array(
					'description' => __( 'The email address for the member.', 'buddypress' ),
					'type'        => 'string',
					'format'      => 'email',
					'context'     => array( 'edit' ),
					'required'    => true,
				),
				'link'        => array(
					'description' => __( 'Profile URL of the member.', 'buddypress' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'embed', 'view' ),
					'readonly'    => true,
				),
				'slug'        => array(
					'description' => __( 'An alphanumeric identifier for the member.', 'buddypress' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => array( $this, 'sanitize_slug' ),
					),
				),
				'registered_date' => array(
					'description' => __( 'Registration date for the member.', 'buddypress' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'edit' ),
					'readonly'    => true,
				),
				'password'        => array(
					'description' => __( 'Password for the member (never included).', 'buddypress' ),
					'type'        => 'string',
					'context'     => array(), // Password is never displayed.
					'required'    => true,
					'arg_options' => array(
						'sanitize_callback' => array( $this, 'check_user_password' ),
					),
				),

				// @todo Should we keep these props?  I'm leaning towards 'no'.
				/** Commented out.
				'locale'    => array(
					'description' => __( 'Locale for the member.', 'buddypress' ),
					'type'        => 'string',
					'enum'        => array_merge( array( '', 'en_US' ), get_available_languages() ),
					'context'     => array( 'edit' ),
				),
				*/
				'roles'           => array(
					'description' => __( 'Roles assigned to the member.', 'buddypress' ),
					'type'        => 'array',
					'items'       => array(
						'type'    => 'string',
					),
					'context'     => array( 'edit' ),
				),
				'capabilities'    => array(
					'description' => __( 'All capabilities assigned to the user.', 'buddypress' ),
					'type'        => 'object',
					'context'     => array( 'edit' ),
					'readonly'    => true,
				),
				'extra_capabilities' => array(
					'description' => __( 'Any extra capabilities assigned to the user.', 'buddypress' ),
					'type'        => 'object',
					'context'     => array( 'edit' ),
					'readonly'    => true,
				),
			),
		);

		// BuddyPress props.
		$schema['properties']['member_types'] = array(
			'description' => __( 'Member types associated with the member.', 'buddypress' ),
			'type'        => 'object',
			'context'     => array( 'embed', 'view', 'edit' ),
		);

		// Avatars.
		if ( true === buddypress()->avatar->show_avatars ) {
			$avatar_properties = array();
			$avatar_properties[ 'full' ] = array(
				'description' => sprintf( __( 'Avatar URL with full image size (%1$d x %2$d pixels).', 'buddypress' ), bp_core_avatar_full_width(), bp_core_avatar_full_height() ),
				'type'        => 'string',
				'format'      => 'uri',
				'context'     => array( 'embed', 'view', 'edit' ),
			);
			$avatar_properties[ 'thumb' ] = array(
				'description' => sprintf( __( 'Avatar URL with thumb image size (%1$d x %2$d pixels).', 'buddypress' ), bp_core_avatar_thumb_width(), bp_core_avatar_thumb_height() ),
				'type'        => 'string',
				'format'      => 'uri',
				'context'     => array( 'embed', 'view', 'edit' ),
			);

			$schema['properties']['avatar_urls']  = array(
				'description' => __( 'Avatar URLs for the member.', 'buddypress' ),
				'type'        => 'object',
				'context'     => array( 'embed', 'view', 'edit' ),
				'readonly'    => true,
				'properties'  => $avatar_properties,
			);
		}

		$schema['properties']['meta'] = $this->meta->get_field_schema();

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Prepares a single user output for response.
	 *
	 * Overrides the parent method for BuddyPress usage.
	 *
	 * @since 0.1
	 * @access public
	 *
	 * @param WP_User         $user    User object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function prepare_item_for_response( $user, $request ) {

		$data   = array();
		$schema = $this->get_item_schema();

		if ( ! empty( $schema['properties']['id'] ) ) {
			$data['id'] = $user->ID;
		}

		if ( ! empty( $schema['properties']['username'] ) ) {
			$data['username'] = $user->user_login;
		}

		if ( ! empty( $schema['properties']['name'] ) ) {
			$data['name'] = $user->display_name;
		}

		if ( ! empty( $schema['properties']['email'] ) ) {
			$data['email'] = $user->user_email;
		}

		if ( ! empty( $schema['properties']['link'] ) ) {
			$data['link'] = bp_core_get_user_domain( $user->ID, $user->user_nicename, $user->user_login );
		}

		// @todo Should we do this? bp_is_username_compatibility_mode() ? $user_login : $user_nicename;
		if ( ! empty( $schema['properties']['slug'] ) ) {
			$data['slug'] = $user->user_nicename;
		}

		if ( ! empty( $schema['properties']['registered_date'] ) ) {
			$data['registered_date'] = date( 'c', strtotime( $user->user_registered ) );
		}

		if ( ! empty( $schema['properties']['avatar_urls'] ) ) {
			$data['avatar_urls'] = array(
				'full'  => bp_core_fetch_avatar( array( 'item_id' => $user->ID, 'html' => false, 'type' => 'full' ) ),
				'thumb' => bp_core_fetch_avatar( array( 'item_id' => $user->ID, 'html' => false ) ),
			);
		}

		// BuddyPress props.
		if ( ! empty( $schema['properties']['member_types'] ) ) {
			$data['member_types'] = bp_get_member_type( $user->ID, false );
			if ( false === $data['member_types'] ) {
				$data['member_types'] = array();
			}
		}

		// @todo Subject to removal.
		if ( ! empty( $schema['properties']['locale'] ) ) {
			$data['locale'] = get_user_locale( $user );
		}
		if ( ! empty( $schema['properties']['roles'] ) ) {
			// Defensively call array_values() to ensure an array is returned.
			$data['roles'] = array_values( $user->roles );
		}
		if ( ! empty( $schema['properties']['capabilities'] ) ) {
			$data['capabilities'] = (object) $user->allcaps;
		}
		if ( ! empty( $schema['properties']['extra_capabilities'] ) ) {
			$data['extra_capabilities'] = (object) $user->caps;
		}

		if ( ! empty( $schema['properties']['meta'] ) ) {
			$data['meta'] = $this->meta->get_value( $user->ID, $request );
		}

		$context = ! empty( $request['context'] ) ? $request['context'] : 'embed';

		$data = $this->add_additional_fields_to_object( $data, $request );
		$data = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $user ) );

		/**
		 * Filters user data returned from the REST API.
		 *
		 * @since 4.7.0
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param object           $user     User object used to create response.
		 * @param WP_REST_Request  $request  Request object.
		 */
		return apply_filters( 'rest_prepare_user', $response, $user, $request );
	}

	/**
	 * Checks if a given request has access to read a user.
	 *
	 * @since 0.1
	 * @access public
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has read access for the item, otherwise WP_Error object.
	 */
	public function get_item_permissions_check( $request ) {
		$user = $this->get_user( $request['id'] );
		if ( is_wp_error( $user ) ) {
			return $user;
		}

		// Me, myself and I are always allowed access.
		if ( get_current_user_id() === $user->ID ) {
			return true;
		}

		// Old-school BP mods have access.
		if( current_user_can( 'bp_moderate' ) ) {
			return true;
		}

		/*
		 * For now, all other users require the 'list_users' cap to view others.
		 *
		 * @todo Do checks against 'edit' context.
		 *
		 * - 'edit' === $request['context']
		 * - current_user_can( 'bp_moderate' )
		 * - current_user_can( 'edit_user' )
		 */
		if ( ! current_user_can( 'list_users' ) ) {
			return new WP_Error( 'rest_user_cannot_view', __( 'Sorry, you are not allowed to list users.' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/* CUSTOM METHODS *******************************************************/


}