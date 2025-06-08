<?php

namespace Tests\Unit\Src\Tracker;

use PHPUnit\Framework\TestCase;
use Fold_Spy\Tracker\Storage;
use Brain\Monkey\Functions;
use Fold_Spy\Tracker\LogSchema;
use wpdb;
use Mockery;
use PHPUnit\Framework\MockObject\MockObject;

class LogSchemaTest extends TestCase {
	private LogSchema $logSchema;
	private $mockDb;

	/**
	 * Sets up the test environment by mocking the wpdb object and injecting it into the global scope.
	 * Then, it instantiates the LogSchema object with the mocked DB.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Mock wpdb object with Mockery.
		$this->mockDb         = Mockery::mock( 'wpdb' );
		$this->mockDb->prefix = 'wp_';
		$this->mockDb->shouldReceive( 'get_charset_collate' )
			->andReturn( 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci' );

		// Inject mock wpdb into global scope.
		$GLOBALS['wpdb'] = $this->mockDb;

		// Instantiate the schema with the mocked DB.
		$this->logSchema = new LogSchema();
	}

	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * Tests if the create method calls dbDelta with the correct SQL.
	 */
	public function test_create_calls_db_delta_with_correct_sql(): void {
		Functions\expect( 'dbDelta' )->once()->withArgs(
			function ( $sql ) {
				return str_contains( $sql, 'CREATE TABLE wp_foldspy_logs' ) &&
					str_contains( $sql, 'id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT' ) &&
					str_contains( $sql, 'PRIMARY KEY  (id)' );
			}
		);

		$this->logSchema->create( 'dbDelta' );

		// Mark this test as having performed an assertion.
		$this->addToAssertionCount( 1 );
	}

	/**
	 * Tests if the drop method calls query with the correct SQL.
	 */
	public function test_drop_calls_query_with_correct_sql(): void {
		$this->mockDb->shouldReceive( 'query' )
			->once()
			->with(
				Mockery::on(
					function ( $sql ) {
						return str_contains( $sql, 'DROP TABLE IF EXISTS wp_foldspy_logs' );
					}
				)
			)
			->andReturn( true );

		$this->logSchema->drop();

		$this->addToAssertionCount( 1 );
	}
}
