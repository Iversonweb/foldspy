<?php

namespace Fold_Spy\Tracker;

use Fold_Spy\Support\Logger;
use WP_Error;
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
	 * Logger instance for logging messages.
	 *
	 * @var Logger
	 */
	protected Logger $logger;

	/**
	 * Constructs a new Storage instance with the WordPress database object and sets the table name for logging.
	 *
	 * @param Logger $logger The logger instance for logging messages.
	 */
	public function __construct( Logger $logger ) {
		global $wpdb;
		$this->db     = $wpdb;
		$this->table  = $wpdb->prefix . 'foldspy_logs';
		$this->logger = $logger;
	}

	/**
	 * Logs a visit to the database.
	 *
	 * @param array $data {
	 *     Data for the visit logs.
	 *
	 *     @type int    $user_id       User ID.
	 *     @type int    $screenWidth   Screen width in pixels.
	 *     @type int    $screenHeight  Screen height in pixels.
	 *     @type array  $hrefs         Array of hrefs visited.
	 *     @type string $user_agent    User agent string.
	 * }
	 * @return bool True on success, false on failure.
	 */
	public function log_visit( array $data ): bool {
		$result = (bool) $this->db->insert(
			$this->table,
			array(
				'user_id'       => (int) $data['user_id'],
				'screen_width'  => (int) $data['screenWidth'],
				'screen_height' => (int) $data['screenHeight'],
				'hrefs'         => wp_json_encode( $data['hrefs'] ),
				'user_agent'    => sanitize_text_field( $data['user_agent'] ),
			)
		);

		if ( ! $result ) {
			$this->logger->log(
				'Failed to insert visit: ' . $this->db->last_error,
				'error'
			);
		}

		wp_cache_delete( 'foldspy_total_rows', 'foldspy' );
		wp_cache_delete( 'foldspy_top_links_7_3', 'foldspy' );

		return $result;
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
	public function get_visits( int $page = 1, int $per_page = 20 ): array {
		$total_rows  = $this->get_total_rows();
		$total_pages = max( 1, ceil( $total_rows / $per_page ) );
		// Clamp page to total_pages.
		$page = min( $page, $total_pages );

		$offset = ( $page - 1 ) * $per_page;
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
	public function get_visit( int $id ): ?array {
		$cache_key = "foldspy_visit_{$id}";
		$cached    = wp_cache_get( $cache_key, 'foldspy' );

		if ( false !== $cached ) {
			return $cached;
		}

		$row = $this->db->get_row(
			$this->db->prepare( "SELECT * FROM {$this->table} WHERE id = %d", $id ),
			ARRAY_A
		);

		if ( $row ) {
			wp_cache_set( $cache_key, $row, 'foldspy', 600 );
		}
		return $row ? $row : null;
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
		$cached    = wp_cache_get( $cache_key, 'foldspy' );

		if ( false !== $cached ) {
			return $cached;
		}

		$total = (int) $this->db->get_var( "SELECT COUNT(*) FROM {$this->table}" );
		wp_cache_set( $cache_key, $total, 'foldspy', 300 );
		return $total;
	}

	/**
	 * Returns the top N most frequently seen links from logs within a timeframe.
	 *
	 * @param int $days Number of days to look back.
	 * @param int $limit Number of links to return.
	 * @return array List of top links and counts.
	 */
	public function get_top_links( int $days = 7, int $limit = 3 ): array {
		$cache_key = "foldspy_top_links_{$days}_{$limit}";
		$cached    = wp_cache_get( $cache_key, 'foldspy' );

		if ( false !== $cached ) {
			return $cached;
		}

		// Retrieve all hrefs from the log table where the visit time is within the last $days days.
		$results = $this->db->get_results(
			$this->db->prepare(
				"SELECT hrefs FROM {$this->table} WHERE visit_time > NOW() - INTERVAL %d DAY",
				$days
			),
			ARRAY_A // Return results as an associative array.
		);

		$counts = array();

		// Initialize an empty array to store the counts of each href.
		foreach ( $results as $row ) {
			$hrefs = json_decode( $row['hrefs'], true ) ?? array();
			foreach ( $hrefs as $href ) {
				$counts[ $href ] = ( $counts[ $href ] ?? 0 ) + 1;
			}
		}

		// Sort the counts in descending order to get the top links.
		arsort( $counts );
		$top = array_slice( $counts, 0, $limit, true );

		wp_cache_set( $cache_key, $top, 'foldspy', 300 ); // Cache for 5 mins.
		return $top;
	}

	/**
	 * Deletes log entries older than the specified datetime.
	 *
	 * This method deletes all log entries from the database that have a visit_time older than the specified datetime.
	 * It also clears the caches related to log data to ensure they are updated after deletion.
	 *
	 * @param string $before_datetime The datetime before which all log entries should be deleted.
	 * @return int|WP_Error The number of rows affected by the deletion query, or a WP_Error object on failure.
	 */
	public function delete_visits( string $before_datetime ): int|WP_Error {
		$table = esc_sql( $this->db->prefix . 'foldspy_logs' );
		$query = "DELETE FROM `$table` WHERE visit_time < %s";

		$sql = $this->db->prepare( $query, $before_datetime );

		$result = $this->db->query( $sql );

		if ( false === $result ) {
			return new WP_Error( 'db_error', $this->db->last_error );
		}

		// Clear caches related to logs.
		wp_cache_delete( 'foldspy_total_rows', 'foldspy' );
		wp_cache_delete( 'foldspy_top_links_7_3', 'foldspy' );

		return $result;
	}
}
