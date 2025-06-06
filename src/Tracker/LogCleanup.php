<?php

namespace FoldSpy\Tracker;

use FoldSpy\Support\Logger;

class LogCleanup {
	protected Logger $logger;

	/**
	 * Initializes the LogCleanup object with a Logger instance.
	 * 
	 * @param Logger $logger The logger to use for logging messages.
	 */
	public function __construct( Logger $logger ) {
		$this->logger = $logger;
	}

	/**
	 * Boots the log cleanup process by scheduling a daily cleanup task.
	 */
	public function boot(): void {
		add_action( 'foldspy/tracker/cleanup_logs', [ $this, 'purge_old_records' ] );

		if ( ! wp_next_scheduled( 'foldspy/tracker/cleanup_logs' ) ) {
			wp_schedule_event( time(), 
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
		global $wpdb;

		$table = $wpdb->prefix . 'foldspy_logs';
		$sql   = $wpdb->prepare( 
            "DELETE FROM $table WHERE visit_time < %s", gmdate( 'Y-m-d H:i:s', strtotime( '-7 days' ) ) 
        );
		$count = $wpdb->query( $sql );

		if ( ! is_wp_error( $count ) ) {
			$this->logger->log( 
                "Deleted $count old log(s)." 
            );
		} else {
			$this->logger->log( 
                "Log cleanup failed: " . $count->get_error_message(), 
                'error' 
            );
		}
	}
}
