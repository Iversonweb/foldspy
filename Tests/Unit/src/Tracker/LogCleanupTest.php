<?php

namespace Tests\Unit\Src\Tracker;

use PHPUnit\Framework\TestCase;
use Brain\Monkey\Functions;
use Fold_Spy\Support\Logger;
use Fold_Spy\Tracker\Storage;
use Fold_Spy\Tracker\LogCleanup;
use Mockery;

class LogCleanupTest extends TestCase {
	protected $mock_logger;
	protected $mock_storage;
	protected LogCleanup $cleanup;

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'wp_cache_delete' )->justReturn( true );

		$this->mock_logger  = Mockery::mock( Logger::class );
		$this->mock_storage = Mockery::mock( Storage::class );

		$this->cleanup = new LogCleanup( $this->mock_storage, $this->mock_logger );
	}

	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	public function test_cleanup_deletes_old_logs(): void {
		$this->mock_storage->shouldReceive( 'delete_visits' )
			->once()
			->withArgs(
				function ( $date ) {
					// Should be a string that looks like a datetime
					return strtotime( $date ) < time();
				}
			)
			->andReturn( 5 ); // 5 rows deleted

		// Logger should NOT log anything when successful
		$this->mock_logger->shouldReceive( 'log' )
			->once()
			->withArgs(
				function ( $msg, $level = null ) {
					return str_starts_with( $msg, 'Deleted' ) && ( $level === null || $level === 'info' );
				}
			);

		$this->cleanup->purge_old_records();

		$this->addToAssertionCount( 1 ); // Mark test as asserted
	}

	public function test_cleanup_logs_error_on_failure(): void {
		// Use Mockery to create a fake WP_Error object
		$mock_wp_error = Mockery::mock( 'WP_Error' );
		$mock_wp_error->shouldReceive( 'get_error_message' )
			->once()
			->andReturn( 'Query failed' );

		$this->mock_storage->shouldReceive( 'delete_visits' )
			->once()
			->andReturn( $mock_wp_error ); // Return the mocked WP_Error

		$this->mock_logger->shouldReceive( 'log' )
			->once()
			->with( 'Log cleanup failed: Query failed', 'error' );

		$this->cleanup->purge_old_records();

		$this->addToAssertionCount( 1 );
	}
}
