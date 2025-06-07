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
	 * Constructor to initialize the logger.
	 */
	public function __construct() {
		$upload_dir = wp_upload_dir();
		$log_dir    = trailingslashit( $upload_dir['basedir'] ) . self::LOG_DIR;

		if ( ! wp_mkdir_p( $log_dir ) ) {
			return;
		}

		$this->log_file = trailingslashit( $log_dir ) . 'foldspy-' . gmdate( 'Y-m-d' ) . '.log';
	}

	/**
	 * Logs a message with a specified level.
	 *
	 * @param string $message The message to log.
	 * @param string $level The level of the message (default is 'info').
	 */
	public function log( string $message, string $level = 'info' ): void {
		$entry = sprintf( '[%s] [%s] %s%s', gmdate( 'Y-m-d H:i:s' ), strtoupper( $level ), $message, PHP_EOL );
		$this->write_to_log( $entry );
	}

	/**
	 * Writes a log entry to the log file.
	 *
	 * @param string $entry The log entry to write.
	 */
	private function write_to_log( string $entry ): void {
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		global $wp_filesystem;

		if ( empty( $wp_filesystem ) ) {
			WP_Filesystem();
		}

		if ( $wp_filesystem ) {
			// Append to the log file.
			$current_content = $wp_filesystem->get_contents( $this->log_file );
			$new_content     = $current_content . $entry;

			$wp_filesystem->put_contents( $this->log_file, $new_content, FS_CHMOD_FILE );
		}
	}
}
