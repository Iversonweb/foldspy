<?php

namespace FoldSpy\Tracker;

use wpdb;

class Storage {
	/**
	 * The WordPress database object.
	 * 
	 * @var wpdb
	 */
	protected wpdb $db;
    
	/**
	 * The name of the table where logs are stored.
	 * 
	 * @var string
	 */
	protected string $table;

	/**
	 * Initializes the storage object with the WordPress database object and sets the table name.
	 */
	public function __construct() {
		global $wpdb;
		$this->db = $wpdb;
		$this->table = $wpdb->prefix . 'foldspy_logs';
	}

	/**
	 * Logs a visit with the provided data.
	 * 
	 * @param array $data The data to log, including screen width, height, and hrefs.
	 * @return bool Returns true if the insertion was successful, false otherwise.
	 */
	public function log_visit(array $data): bool {
        return (bool) $this->db->insert($this->table, [
            'user_id'       => (int) $data['user_id'],
            'screen_width'  => (int) $data['screenWidth'],
            'screen_height' => (int) $data['screenHeight'],
            'hrefs'         => wp_json_encode($data['hrefs']),
            'user_agent'    => sanitize_text_field($data['user_agent']),
        ]);
    }
}