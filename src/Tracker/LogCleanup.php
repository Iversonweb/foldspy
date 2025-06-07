<?php

namespace Fold_Spy\Tracker;

use Fold_Spy\Support\Logger;
use Fold_Spy\Tracker\Storage;

class LogCleanup {
	/**
	 * The storage object for managing data persistence.
	 *
	 * @var Storage
	 */
	protected Storage $storage;

	/**
	 * Logger instance for logging messages.
	 *
	 * @var Logger
	 */
	protected Logger $logger;

	/**
	 * Constructs a new LogCleanup instance with the necessary dependencies.
	 *
	 * This method initializes the LogCleanup object with a Storage instance for managing data persistence and a Logger instance for logging messages.
	 *
	 * @param Storage $storage The storage object for managing data persistence.
	 * @param Logger  $logger The logger to use for logging messages.
	 */
	public function __construct( Storage $storage, Logger $logger ) {
		$this->storage = $storage;
		$this->logger  = $logger;
	}

	/**
	 * Boots the log cleanup process by scheduling a daily cleanup task.
	 */
	public function boot(): void {
		add_action( 'foldspy/tracker/cleanup_logs', array( $this, 'purge_old_records' ) );

		if ( ! wp_next_scheduled( 'foldspy/tracker/cleanup_logs' ) ) {
			wp_schedule_event(
				time(),
				'daily',
				'foldspy/tracker/cleanup_logs'
			);
			$this->logger->log(
				'Scheduled log cleanup task.'
			);
		}
	}

	/**
	 * Purges old log records from the database.
	 */
	public function purge_old_records(): void {
		$before_datetime = gmdate( 'Y-m-d H:i:s', strtotime( '-7 days' ) );

		$result = $this->storage->delete_visits( $before_datetime );

		if ( ! is_wp_error( $result ) ) {
			$this->logger->log(
				"Deleted $result old log(s)."
			);

			// Clear caches related to logs.
			wp_cache_delete( 'foldspy_total_rows', 'foldspy' );
			wp_cache_delete( 'foldspy_top_links_7_3', 'foldspy' );
		} else {
			$this->logger->log(
				'Log cleanup failed: ' . $result->get_error_message(),
				'error'
			);
		}
	}
}
