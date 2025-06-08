<?php

namespace Tests\Unit\Src\Tracker;

use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use Fold_Spy\Tracker\RestEndpoint;
use Fold_Spy\Support\Logger;
use Fold_Spy\Tracker\Storage;
use WP_REST_Request;
use WP_Error;
use WP_REST_Response;
use Mockery;

// Mock WordPress classes if they don't exist
if ( ! class_exists( '\WP_Error' ) ) {
	class_alias( 'Tests\Unit\Src\Tracker\MockWPError', '\WP_Error' );
}

if ( ! class_exists( '\WP_REST_Response' ) ) {
	class_alias( 'Tests\Unit\Src\Tracker\MockWPRESTResponse', '\WP_REST_Response' );
}

if ( ! class_exists( '\WP_REST_Server' ) ) {
	class_alias( 'Tests\Unit\Src\Tracker\MockWPRESTServer', '\WP_REST_Server' );
}

class MockWPError {
	private $code;
	private $message;
	private $data;

	public function __construct( $code, $message, $data = array() ) {
		$this->code    = $code;
		$this->message = $message;
		$this->data    = $data;
	}

	public function get_error_code() {
		return $this->code;
	}

	public function get_error_message() {
		return $this->message;
	}

	public function get_error_data() {
		return $this->data;
	}
}

class MockWPRESTResponse {
	private $data;
	private $status;

	public function __construct( $data, $status = 200 ) {
		$this->data   = $data;
		$this->status = $status;
	}

	public function get_data() {
		return $this->data;
	}

	public function get_status() {
		return $this->status;
	}
}

class MockWPRESTServer {
	const READABLE   = 'GET';
	const CREATABLE  = 'POST';
	const EDITABLE   = 'POST, PUT, PATCH';
	const DELETABLE  = 'DELETE';
	const ALLMETHODS = 'GET, POST, PUT, PATCH, DELETE';
}

class RestEndpointTest extends TestCase {
	protected $mock_storage;
	protected $mock_logger;
	protected RestEndpoint $endpoint;

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'wp_verify_nonce' )->justReturn( true );
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
		Functions\when( 'sanitize_text_field' )->alias( fn( $v ) => $v );
		Functions\when( 'wp_unslash' )->alias( fn( $v ) => $v );
		Functions\when( 'apply_filters' )->alias( fn( $tag, $val ) => $val );

		$this->mock_storage = Mockery::mock( Storage::class );
		$this->mock_logger  = Mockery::mock( Logger::class );

		$this->endpoint = new RestEndpoint( $this->mock_storage, $this->mock_logger );
	}

	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	public function test_boot_registers_rest_route(): void {
		Functions\expect( 'add_action' )
			->once()
			->with( 'rest_api_init', array( $this->endpoint, 'register_routes' ) )
			->andReturn( true );

		$this->endpoint->boot();

		$this->assertTrue( true, 'Boot method should register the rest_api_init action' );
	}

	public function test_register_routes_registers_log_endpoint(): void {
		Functions\expect( 'register_rest_route' )
			->once()
			->with(
				'foldspy/v1',
				'/log',
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this->endpoint, 'handle_request' ),
					'permission_callback' => array( $this->endpoint, 'can_access' ),
				)
			)
			->andReturn( true );

		$this->endpoint->register_routes();

		$this->assertTrue( true, 'Route should be registered successfully' );
	}

	public function test_handle_request_returns_error_if_data_is_invalid(): void {
		$request = Mockery::mock( WP_REST_Request::class );
		$request->shouldReceive( 'get_json_params' )->andReturn( array() );

		$this->mock_logger->shouldReceive( 'log' )
			->once()
			->with( 'Invalid request data received.', 'warning' );

		$response = $this->endpoint->handle_request( $request );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertEquals( 400, $response->get_error_data()['status'] );
		$this->assertEquals( 'Malformed input', $response->get_error_message() );
	}

	public function test_handle_request_returns_error_on_auth_failure(): void {
		Functions\when( 'wp_verify_nonce' )->justReturn( false );

		$request = Mockery::mock( WP_REST_Request::class );
		$request->shouldReceive( 'get_json_params' )->andReturn(
			array(
				'screenWidth'  => 1024,
				'screenHeight' => 768,
				'hrefs'        => array( 'https://example.com' ),
			)
		);
		$request->shouldReceive( 'get_header' )->with( 'X-WP-Nonce' )->andReturn( 'invalid' );

		$this->mock_logger->shouldReceive( 'log' )
			->once()
			->with( 'Unauthorized Access', 'error' );

		$response = $this->endpoint->handle_request( $request );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertEquals( 403, $response->get_error_data()['status'] );
		$this->assertEquals( 'Unauthorized Access', $response->get_error_message() );
	}

	public function test_handle_request_returns_error_on_db_failure(): void {
		$request = Mockery::mock( WP_REST_Request::class );
		$request->shouldReceive( 'get_json_params' )->andReturn(
			array(
				'screenWidth'  => 1440,
				'screenHeight' => 900,
				'hrefs'        => array( 'https://example.com' ),
			)
		);
		$request->shouldReceive( 'get_header' )->with( 'X-WP-Nonce' )->andReturn( 'valid' );

		$_SERVER['HTTP_USER_AGENT'] = 'FakeAgent';

		$this->mock_storage->shouldReceive( 'log_visit' )
			->once()
			->andReturn( false );

		$this->mock_logger->shouldReceive( 'log' )
			->once()
			->with( 'Failed to log visit to DB', 'error' );

		$response = $this->endpoint->handle_request( $request );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertEquals( 500, $response->get_error_data()['status'] );
		$this->assertEquals( 'Could not store visit', $response->get_error_message() );
	}

	public function test_handle_request_returns_success_on_valid_data(): void {
		$request = Mockery::mock( WP_REST_Request::class );
		$request->shouldReceive( 'get_json_params' )->andReturn(
			array(
				'screenWidth'  => 1920,
				'screenHeight' => 1080,
				'hrefs'        => array( 'https://example.com' ),
			)
		);
		$request->shouldReceive( 'get_header' )->with( 'X-WP-Nonce' )->andReturn( 'valid' );

		$_SERVER['HTTP_USER_AGENT'] = 'TestAgent';

		$this->mock_storage->shouldReceive( 'log_visit' )
			->once()
			->andReturn( true );

		$this->mock_logger->shouldReceive( 'log' )
			->once()
			->with( 'Visit logged successfully.' );

		$response = $this->endpoint->handle_request( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( array( 'message' => 'Visit recorded' ), $response->get_data() );
	}
}
