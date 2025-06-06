<?php

namespace FoldSpy\Tracker;

use wpdb;

class LogSchema {
	/**
	 * The WordPress database object.
	 * 
	 * @var wpdb
	 */
	private wpdb $db;

	/**
	 * The name of the table used for storing logs.
	 * 
	 * @var string
	 */
	private string $table;

	/**
	 * Initializes the LogSchema object with the WordPress database object and sets the table name.
	 */
	public function __construct() {
		global $wpdb;
		$this->db = $wpdb;
		$this->table = $this->db->prefix . 'foldspy_logs';
	}

	/**
	 * Creates the table for storing logs if it doesn't already exist.
	 */
	public function create() {
		// Include the upgrade script to use dbDelta function
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table = $wpdb->prefix . 'foldspy_logs';
		$charset_collate = $this->db->get_charset_collate();

		$sql = "CREATE TABLE {$this->table} (
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
	public function drop(): void {
		$this->db->query("DROP TABLE IF EXISTS {$this->table}");
	}
}
