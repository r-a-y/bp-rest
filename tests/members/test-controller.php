<?php
/**
 * @group members
 */
class BP_Test_REST_Members_Controller extends WP_Test_REST_Controller_Testcase {
	protected static $superadmin;
	protected static $user;
	protected static $editor;
	protected static $editor2;
	protected static $secret_editor;
	protected static $secret_editor2;
	protected static $site;

	public static function wpSetUpBeforeClass( $factory ) {
		self::$user = $factory->user->create( array(
			'role' => 'administrator',
		) );

		return;
		self::$superadmin = $factory->user->create( array(
			'role'       => 'administrator',
			'user_login' => 'superadmin',
		) );
		self::$editor = $factory->user->create( array(
			'role'       => 'editor',
			'user_email' => 'editor@example.com',
		) );
		self::$editor2 = $factory->user->create( array(
			'role'       => 'editor',
			'user_email' => 'editor2@example.com',
		) );
		self::$secret_editor = $factory->user->create( array(
			'role'       => 'editor',
			'user_email' => 'secret_editor@example.com',
		) );
		self::$secret_editor2 = $factory->user->create( array(
			'role'       => 'editor',
			'user_email' => 'secret_editor2@example.com',
		) );

		if ( is_multisite() ) {
			self::$site = $factory->blog->create( array( 'domain' => 'rest.wordpress.org', 'path' => '/' ) );
			update_site_option( 'site_admins', array( 'superadmin' ) );
		}
	}

	public static function wpTearDownAfterClass() {
		self::delete_user( self::$user );
		return;

		self::delete_user( self::$editor );
		self::delete_user( self::$editor2 );
		self::delete_user( self::$secret_editor );
		self::delete_user( self::$secret_editor2 );

		if ( is_multisite() ) {
			wpmu_delete_blog( self::$site, true );
		}
	}

	/**
	 * This function is run before each method
	 */
	public function setUp() {
		parent::setUp();

		buddypress()->members->types = array();

		$this->endpoint = new BP_REST\Members\v1\Controller;
	}

	public function test_register_routes() {
		$routes = $this->server->get_routes();

		$this->assertArrayHasKey( '/bp/v1/members', $routes );
		$this->assertCount( 2, $routes['/bp/v1/members'] );
		$this->assertArrayHasKey( '/bp/v1/members/(?P<id>[\d]+)', $routes );
		$this->assertCount( 3, $routes['/bp/v1/members/(?P<id>[\d]+)'] );
		$this->assertArrayHasKey( '/bp/v1/members/me', $routes );
	}

	public function test_context_param() {
		// Collection
		$request = new WP_REST_Request( 'OPTIONS', '/bp/v1/members' );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$this->assertEquals( 'view', $data['endpoints'][0]['args']['context']['default'] );
		$this->assertEquals( array( 'view', 'embed', 'edit' ), $data['endpoints'][0]['args']['context']['enum'] );
		// Single
		$request = new WP_REST_Request( 'OPTIONS', '/bp/v1/members/' . self::$user );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$this->assertEquals( 'view', $data['endpoints'][0]['args']['context']['default'] );
		$this->assertEquals( array( 'view', 'embed', 'edit' ), $data['endpoints'][0]['args']['context']['enum'] );
	}

	public function test_get_items() {
		wp_set_current_user( self::$user );

		$request = new WP_REST_Request( 'GET', '/bp/v1/members' );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$data = $all_data[0];
		$userdata = get_userdata( $data['id'] );
		$this->check_user_data( $userdata, $data, 'view', $data['_links'] );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item() {
		$user_id = $this->factory->user->create();

		// Register and set member types.
		bp_register_member_type( 'foo' );
		bp_register_member_type( 'bar' );
		bp_set_member_type( $user_id, 'foo' );
		bp_set_member_type( $user_id, 'bar', true );

		// Set up xprofile data.
		

		wp_set_current_user( self::$user );

		$request = new WP_REST_Request( 'GET', sprintf( '/bp/v1/members/%d', $user_id ) );

		$response = $this->server->dispatch( $request );
		$this->check_get_user_response( $response, 'embed' );
	}

	public function test_create_item() {
		$this->allow_user_to_manage_multisite();
		wp_set_current_user( self::$user );

		$params = array(
			'username'    => 'testuser',
			'password'    => 'testpassword',
			'email'       => 'test@example.com',
			'name'        => 'Test User',
			'nickname'    => 'testuser',
			'slug'        => 'test-user',
			'roles'       => array( 'editor' ),
		);

		$request = new WP_REST_Request( 'POST', '/bp/v1/members' );
		$request->add_header( 'content-type', 'application/x-www-form-urlencoded' );
		$request->set_body_params( $params );

		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$this->assertEquals( array( 'editor' ), $data['roles'] );
		$this->check_add_edit_user_response( $response );
	}

	public function test_update_item() {
		return;
		$user_id = $this->factory->user->create( array(
			'user_email' => 'test@example.com',
			'user_pass' => 'sjflsfls',
			'user_login' => 'test_update',
			'first_name' => 'Old Name',
			'user_url' => 'http://apple.com',
			'locale' => 'en_US',
		));
		$this->allow_user_to_manage_multisite();
		wp_set_current_user( self::$user );

		$userdata = get_userdata( $user_id );
		$pw_before = $userdata->user_pass;

		$_POST['email'] = $userdata->user_email;
		$_POST['username'] = $userdata->user_login;
		$_POST['first_name'] = 'New Name';
		$_POST['url'] = 'http://google.com';
		$_POST['locale'] = 'de_DE';

		$request = new WP_REST_Request( 'PUT', sprintf( '/wp/v2/users/%d', $user_id ) );
		$request->add_header( 'content-type', 'application/x-www-form-urlencoded' );
		$request->set_body_params( $_POST );

		$response = $this->server->dispatch( $request );
		$this->check_add_edit_user_response( $response, true );

		// Check that the name has been updated correctly
		$new_data = $response->get_data();
		$this->assertEquals( 'New Name', $new_data['first_name'] );
		$user = get_userdata( $user_id );
		$this->assertEquals( 'New Name', $user->first_name );

		$this->assertEquals( 'http://google.com', $new_data['url'] );
		$this->assertEquals( 'http://google.com', $user->user_url );
		$this->assertEquals( 'de_DE', $user->locale );

		// Check that we haven't inadvertently changed the user's password,
		// as per https://core.trac.wordpress.org/ticket/21429
		$this->assertEquals( $pw_before, $user->user_pass );
	}

	public function test_delete_item() {
		return;
		$user_id = $this->factory->user->create( array( 'display_name' => 'Deleted User' ) );

		$this->allow_user_to_manage_multisite();
		wp_set_current_user( self::$user );

		$userdata = get_userdata( $user_id ); // cache for later
		$request = new WP_REST_Request( 'DELETE', sprintf( '/wp/v2/users/%d', $user_id ) );
		$request->set_param( 'force', true );
		$request->set_param( 'reassign', false );
		$response = $this->server->dispatch( $request );

		// Not implemented in multisite.
		if ( is_multisite() ) {
			$this->assertErrorResponse( 'rest_cannot_delete', $response, 501 );
			return;
		}

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertTrue( $data['deleted'] );
		$this->assertEquals( 'Deleted User', $data['previous']['name'] );
	}

	public function test_prepare_item() {
		return;
		wp_set_current_user( self::$user );
		$request = new WP_REST_Request;
		$request->set_param( 'context', 'edit' );
		$user = get_user_by( 'id', get_current_user_id() );
		$data = $this->endpoint->prepare_item_for_response( $user, $request );
		$this->check_get_user_response( $data, 'edit' );
	}

	public function test_get_item_schema() {
		return;

		$request = new WP_REST_Request( 'OPTIONS', '/wp/v2/users' );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 19, count( $properties ) );
		$this->assertArrayHasKey( 'avatar_urls', $properties );
		$this->assertArrayHasKey( 'capabilities', $properties );
		$this->assertArrayHasKey( 'description', $properties );
		$this->assertArrayHasKey( 'email', $properties );
		$this->assertArrayHasKey( 'extra_capabilities', $properties );
		$this->assertArrayHasKey( 'first_name', $properties );
		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'last_name', $properties );
		$this->assertArrayHasKey( 'link', $properties );
		$this->assertArrayHasKey( 'locale', $properties );
		$this->assertArrayHasKey( 'meta', $properties );
		$this->assertArrayHasKey( 'name', $properties );
		$this->assertArrayHasKey( 'nickname', $properties );
		$this->assertArrayHasKey( 'registered_date', $properties );
		$this->assertArrayHasKey( 'slug', $properties );
		$this->assertArrayHasKey( 'password', $properties );
		$this->assertArrayHasKey( 'url', $properties );
		$this->assertArrayHasKey( 'username', $properties );
		$this->assertArrayHasKey( 'roles', $properties );

	}

	protected function check_user_data( $user, $data, $context, $links ) {
		$this->assertEquals( $user->ID, $data['id'] );
		$this->assertEquals( $user->display_name, $data['name'] );
		$this->assertEquals( bp_core_get_user_domain( $data['id']), $data['link'] );
		$this->assertArrayHasKey( 'avatar_urls', $data );
		$this->assertEquals( $user->user_nicename, $data['slug'] );

		// BP asserts
		$this->assertArrayHasKey( 'member_types', $data );
		if ( false !== $data['member_types'] ) {
			$this->assertEquals( bp_get_member_type( $user->ID, false ), $data['member_types'] );
		}

		if ( 'edit' === $context ) {
			$this->assertEquals( $user->first_name, $data['first_name'] );
			$this->assertEquals( $user->last_name, $data['last_name'] );
			$this->assertEquals( $user->nickname, $data['nickname'] );
			$this->assertEquals( $user->user_email, $data['email'] );
			$this->assertEquals( (object) $user->allcaps, $data['capabilities'] );
			$this->assertEquals( (object) $user->caps, $data['extra_capabilities'] );
			$this->assertEquals( date( 'c', strtotime( $user->user_registered ) ), $data['registered_date'] );
			$this->assertEquals( $user->user_login, $data['username'] );
			$this->assertEquals( $user->roles, $data['roles'] );
			$this->assertEquals( get_user_locale( $user ), $data['locale'] );
		}

		if ( 'edit' !== $context ) {
			$this->assertArrayNotHasKey( 'roles', $data );
			$this->assertArrayNotHasKey( 'capabilities', $data );
			$this->assertArrayNotHasKey( 'registered', $data );
			$this->assertArrayNotHasKey( 'first_name', $data );
			$this->assertArrayNotHasKey( 'last_name', $data );
			$this->assertArrayNotHasKey( 'nickname', $data );
			$this->assertArrayNotHasKey( 'extra_capabilities', $data );
			$this->assertArrayNotHasKey( 'username', $data );
		}

		$this->assertEqualSets( array(
			'self',
			'collection',
		), array_keys( $links ) );

		$this->assertArrayNotHasKey( 'password', $data );
	}

	protected function check_get_user_response( $response, $context = 'view' ) {
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$userdata = get_userdata( $data['id'] );
		$this->check_user_data( $userdata, $data, $context, $response->get_links() );
	}

	protected function allow_user_to_manage_multisite() {
		wp_set_current_user( self::$user );
		$user = wp_get_current_user();

		if ( is_multisite() ) {
			update_site_option( 'site_admins', array( $user->user_login ) );
		}

		return;
	}
}