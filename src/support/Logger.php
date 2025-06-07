<?php

namespace Fold_Spy\Support;

class Logger {
	/**
	 * Directory name for FoldSpy logs.
	 */
	const LOG_DIR = 'foldspy-logs';

	/**
	 * Maximum size of a log file in bytes.
	 */
	const MAX_LOG_SIZE = 5242880;

	/**
	 * Maximum number of log files to keep.
	 */
	const MAX_LOG_FILES = 5;

	/**
	 * The path to the current log file.
	 *
	 * @var string
	 */
	private string $log_file;

	/**
	 * A callable function to write to the log file.
	 *
	 * @var mixed
	 */
	private mixed $file_writer;

	/**
	 * Initializes the logger with a specified log directory and file writer.
	 *
	 * This constructor sets up the logger by creating the log directory if it doesn't exist,
	 * generating the path to the current log file, and setting a default file writer function
	 * if none is provided. The default file writer function uses WordPress's file system to
	 * append content to the log file.
	 *
	 * @param string|null   $log_dir The directory where logs will be stored. Defaults to null.
	 * @param callable|null $file_writer A callable function to write to the log file. Defaults to null.
	 */
	public function __construct( ?string $log_dir = null, ?callable $file_writer = null ) {
		$upload_dir = function_exists( 'wp_upload_dir' ) ? wp_upload_dir() : array( 'basedir' => sys_get_temp_dir() );
		$log_dir    = trailingslashit( $upload_dir['basedir'] ) . self::LOG_DIR;

		if ( ! wp_mkdir_p( $log_dir ) ) {
			return;
		}

		$this->log_file = trailingslashit( $log_dir ) . 'foldspy-' . gmdate( 'Y-m-d' ) . '.log';

		$this->file_writer = $file_writer ? $file_writer : function ( string $file, string $content ) {
			if ( ! function_exists( 'WP_Filesystem' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}

			global $wp_filesystem;

			if ( empty( $wp_filesystem ) ) {
				WP_Filesystem();
			}

			if ( $wp_filesystem ) {
				$current = $wp_filesystem->get_contents( $file );
				$wp_filesystem->put_contents( $file, $current . $content, FS_CHMOD_FILE );
			}
		};
	}

	/**
	 * Writes a log entry with a specified level to the log file.
	 *
	 * This method formats a log entry with the current date and time, the specified level, and the message.
	 * It then uses the configured file writer to append the entry to the log file.
	 *
	 * @param string $message The message to be logged.
	 * @param string $level The level of the message (defaults to 'info').
	 */
	public function log( string $message, string $level = 'info' ): void {
		$entry = sprintf( '[%s] [%s] %s%s', gmdate( 'Y-m-d H:i:s' ), strtoupper( $level ), $message, PHP_EOL );
		$this->write_to_log( $entry );
	}

	/**
	 * Writes a log entry to the log file using the configured file writer.
	 *
	 * This method takes a log entry string and uses the file writer function
	 * configured in the constructor to write the entry to the log file.
	 *
	 * @param string $entry The log entry to write.
	 */
	private function write_to_log( string $entry ): void {
		$writer = $this->file_writer;
		$writer( $this->log_file, $entry );
	}
}
