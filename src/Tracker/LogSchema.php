<?php

namespace FoldSpy\Tracker;

class LogSchema {
	/**
	 * Creates the table for storing logs if it doesn't already exist.
	 */
	public static function create() {
		global $wpdb;
        
		// Include the upgrade script to use dbDelta function
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table = $wpdb->prefix . 'foldspy_logs';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT(20) DEFAULT 0,
			screen_width INT NOT NULL,
			screen_height INT NOT NULL,
			hrefs TEXT NOT NULL,
			user_agent TEXT,
			visit_time DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		dbDelta( $sql );
	}

	/**
	 * Drops the table for storing logs if it exists.
	 */
	public static function drop(): void {
		global $wpdb;
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}foldspy_logs" );
	}
}
