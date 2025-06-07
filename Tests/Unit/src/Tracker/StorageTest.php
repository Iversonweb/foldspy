<?php

namespace Tests\Unit\Src\Tracker;

use PHPUnit\Framework\TestCase;
use Fold_Spy\Tracker\Storage;
use Brain\Monkey\Functions;
use Mockery;
use Fold_Spy\Support\Logger;

// Define ARRAY_A if it's not already defined to ensure compatibility with WordPress constants.
if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
}

/**
 * This class contains unit tests for the Storage class.
 * It verifies the functionality of the Storage class methods.
 */
class StorageTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		global $wpdb;
		$wpdb         = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';

		// WordPress mocks.
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
		Functions\when( 'sanitize_text_field' )->alias( fn( $val ) => strip_tags( $val ) );
		Functions\when( 'wp_cache_delete' )->justReturn( true );
		Functions\when( 'wp_cache_get' )->justReturn( false );
		Functions\when( 'wp_cache_set' )->justReturn( true );
	}

	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * Tests that the get_visits method returns an array of visit records.
	 */
	public function test_get_visits_returns_array(): void {
		global $wpdb;

		$fake_results = array(
			array(
				'id'            => 1,
				'screen_width'  => 1024,
				'screen_height' => 768,
				'hrefs'         => '["https://example.com"]',
			),
		);

		$wpdb->shouldReceive( 'prepare' )
			->once()
			->andReturnUsing(
				function ( $query, $per_page, $offset ) {
					return sprintf( $query, $per_page, $offset );
				}
			);

		$wpdb->shouldReceive( 'get_var' )
			->andReturn( 1 );

		$wpdb->shouldReceive( 'get_results' )
			->once()
			->andReturn( $fake_results );

		$logger  = Mockery::mock( Logger::class );
		$storage = new Storage( $logger );

		$result = $storage->get_visits( 1, 10 );
		$this->assertIsArray( $result );
		$this->assertEquals( $fake_results, $result );
	}

	/**
	 * Tests that the get_total_rows method returns an integer.
	 */
	public function test_get_total_rows_returns_integer(): void {
		global $wpdb;

		$wpdb->shouldReceive( 'get_var' )
			->once()
			->andReturn( 42 );

		$logger  = Mockery::mock( Logger::class );
		$storage = new Storage( $logger );
		$total   = $storage->get_total_rows();

		$this->assertSame( 42, $total );
	}

	/**
	 * Tests that the log_visit method returns true on successful insertion.
	 */
	public function test_log_visit_returns_true(): void {
		global $wpdb;

		$wpdb->shouldReceive( 'insert' )
			->once()
			->withArgs(
				function ( $table, $data ) {
					return isset( $data['screen_width'] ) && is_int( $data['screen_width'] );
				}
			)
			->andReturn( 1 );

		$logger  = Mockery::mock( Logger::class );
		$storage = new Storage( $logger );

		$mock_data = array(
			'user_id'      => 123,
			'screenWidth'  => 1280,
			'screenHeight' => 720,
			'hrefs'        => array( 'https://test.com' ),
			'user_agent'   => '<b>UnitTestBot</b>',
		);

		$result = $storage->log_visit( $mock_data );
		$this->assertTrue( $result );
	}
}
