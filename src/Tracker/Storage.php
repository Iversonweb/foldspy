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

        wp_cache_delete('foldspy_total_rows', 'foldspy');
        wp_cache_delete('foldspy_top_links_7_3', 'foldspy');
    }

    /**
     * Retrieves a paginated list of visits from the database.
     * 
     * This method fetches a limited number of visit records from the database, ordered by visit time in descending order.
     * The pagination is based on the provided page number and the number of records per page.
     * 
     * @param int $page The page number to retrieve. Defaults to 1.
     * @param int $per_page The number of records to retrieve per page. Defaults to 20.
     * @return array An array of visit records in associative array format.
     */
    public function get_visits(int $page = 1, int $per_page = 20): array {
        $offset = ($page - 1) * $per_page;
        return $this->db->get_results(
            $this->db->prepare(
                "SELECT * FROM $this->table ORDER BY visit_time DESC LIMIT %d OFFSET %d",
                $per_page,
                $offset
            ),
            ARRAY_A
        );
    }

    /**
     * Retrieves a single visit record by its ID.
     * 
     * This method fetches a visit record from the database based on the provided ID.
     * 
     * @param int $id The ID of the visit to retrieve.
     * @return ?array The visit record as an associative array, or null if not found.
     */
    public function get_visit(int $id): ?array {
        $cache_key = "foldspy_visit_{$id}";
        $cached = wp_cache_get($cache_key, 'foldspy');

        if ($cached !== false) {
            return $cached;
        }

        $row = $this->db->get_row(
            $this->db->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id),
            ARRAY_A
        );

        if ($row) {
            wp_cache_set($cache_key, $row, 'foldspy', 600);
        }
        return $row ?: null;
    }

    /**
     * Retrieves the total number of rows in the log table.
     * 
     * This method executes a SQL query to count the total number of rows in the log table.
     * 
     * @return int The total number of rows in the log table.
     */
    public function get_total_rows(): int {
        $cache_key = 'foldspy_total_rows';
        $cached = wp_cache_get($cache_key, 'foldspy');

        if ($cached !== false) {
            return $cached;
        }

        $total = (int) $this->db->get_var("SELECT COUNT(*) FROM {$this->table}");
        wp_cache_set($cache_key, $total, 'foldspy', 300);
        return $total;
    }

    /**
     * Returns the top N most frequently seen links from logs within a timeframe.
     *
     * @param int $days Number of days to look back.
     * @param int $limit Number of links to return.
     * @return array List of top links and counts.
     */
    public function get_top_links(int $days = 7, int $limit = 3): array {
        $cache_key = "foldspy_top_links_{$days}_{$limit}";
        $cached = wp_cache_get($cache_key, 'foldspy');
    
        if ($cached !== false) {
            return $cached;
        }
    
        // Retrieve all hrefs from the log table where the visit time is within the last $days days
        $results = $this->db->get_results(
            $this->db->prepare(
                "SELECT hrefs FROM {$this->table} WHERE visit_time > NOW() - INTERVAL %d DAY",
                $days
            ),
            ARRAY_A // Return results as an associative array
        );
    
        $counts = [];

        // Initialize an empty array to store the counts of each href
        foreach ($results as $row) {
            $hrefs = json_decode($row['hrefs'], true) ?? [];
            foreach ($hrefs as $href) {
                $counts[$href] = ($counts[$href] ?? 0) + 1;
            }
        }
    
        // Sort the counts in descending order to get the top links
        arsort($counts);
        $top = array_slice($counts, 0, $limit, true);
    
        wp_cache_set($cache_key, $top, 'foldspy', 300); // cache for 5 mins
        return $top;
    }
    
}