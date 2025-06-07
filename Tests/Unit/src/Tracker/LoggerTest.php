<?php

namespace Tests\Unit\Src\Tracker;

use PHPUnit\Framework\TestCase;
use Fold_Spy\Support\Logger;
use Brain\Monkey\Functions;

class LoggerTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		$log_dir = sys_get_temp_dir() . '/foldspy-logs/';

		if ( ! is_dir( $log_dir ) ) {
			mkdir( $log_dir, 0755, true );
		}

		// Mock wp_mkdir_p to avoid undefined function error and simulate directory creation success.
		Functions\when( 'wp_mkdir_p' )->justReturn( true );
	}

	protected function tearDown(): void {
		parent::tearDown();
	}

	public function test_logger_writes_to_fake_file(): void {
		// Mock wp_upload_dir to return a temp directory path
		Functions\when( 'wp_upload_dir' )->justReturn(
			array(
				'basedir' => sys_get_temp_dir(),
				'baseurl' => 'http://example.com/wp-content/uploads',
				'path'    => sys_get_temp_dir(),
				'url'     => 'http://example.com/wp-content/uploads',
				'subdir'  => '',
				'error'   => false,
			)
		);

		// Mock trailingslashit to add trailing slash to a string.
		Functions\when( 'trailingslashit' )->alias(
			function ( $string ) {
				return rtrim( $string, '/\\' ) . '/';
			}
		);

		// Define the expected log file path based on Logger logic.
		$log_path = sys_get_temp_dir() . '/foldspy-logs/foldspy-' . gmdate( 'Y-m-d' ) . '.log';

		// Clean up before running the test
		if ( file_exists( $log_path ) ) {
			unlink( $log_path );
		}

		// Fake file writer that appends log entries to the file.
		$fake_writer = function ( $file, $entry ) {
			file_put_contents( $file, $entry, FILE_APPEND );
		};

		// Instantiate Logger with no custom log_dir to use mocked wp_upload_dir().
		$logger = new Logger( null, $fake_writer );

		// Write a test log entry.
		$logger->log( 'Hello from test', 'debug' );

		// Assert the log file was created.
		$this->assertFileExists( $log_path );

		// Assert the log file contains the test message.
		$content = file_get_contents( $log_path );
		$this->assertStringContainsString( 'Hello from test', $content );
	}
}
