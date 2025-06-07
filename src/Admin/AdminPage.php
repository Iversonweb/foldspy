<?php

namespace Fold_Spy\Admin;

use Fold_Spy\Tracker\Storage;
use Fold_Spy\Admin\AdminRenderer;

class AdminPage {
	/**
	 * The storage object for managing logs.
	 *
	 * @var Storage
	 */
	private Storage $storage;
	/**
	 * The renderer object for rendering the admin interface.
	 *
	 * @var AdminRenderer
	 */
	private AdminRenderer $renderer;

	/**
	 * Initializes the AdminPage with a Storage and AdminRenderer object.
	 *
	 * This constructor sets the Storage and AdminRenderer objects for managing logs and rendering the admin interface within the AdminPage.
	 * It also hooks into the 'admin_post_foldspy_download_csv' action to handle CSV downloads.
	 *
	 * @param Storage       $storage The Storage object for managing logs.
	 * @param AdminRenderer $renderer The AdminRenderer object for rendering the admin interface.
	 */
	public function __construct( Storage $storage, AdminRenderer $renderer ) {
		$this->storage  = $storage;
		$this->renderer = $renderer;

		add_action( 'admin_post_foldspy_download_csv', array( $this, 'handle_csv_download' ) );
	}

	/**
	 * Boots the AdminPage by adding the necessary hooks.
	 *
	 * This method hooks into the 'admin_menu' action to add the FoldSpy logs menu page.
	 */
	public function boot(): void {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
	}

	/**
	 * Adds the FoldSpy logs menu page to the WordPress admin menu.
	 *
	 * This method adds a new menu page to the WordPress admin menu for managing FoldSpy logs.
	 */
	public function add_menu(): void {
		add_menu_page(
			'FoldSpy Logs',
			'FoldSpy',
			'manage_options',
			'foldspy-logs',
			array( $this, 'render_page' ),
			'dashicons-visibility'
		);
	}

	/**
	 * Renders the admin interface for FoldSpy logs.
	 *
	 * This method generates a CSV export link with a nonce for security, displays the logs table, and renders pagination links.
	 */
	public function render_page() {
		// Handle viewing a single log with proper nonce verification.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['view_log'] ) ) {
			if ( ! $this->verify_nonce( 'foldspy_view_log' ) ) {
				$this->deny_access();
			}

			$this->render_single_log( (int) $this->get_query_arg( 'view_log' ) );
			return;
		}

		// Handle CSV download action with nonce check.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['action'] ) && 'download_csv' === $this->get_query_arg( 'action' ) ) {
			if ( ! $this->verify_nonce( 'foldspy_export_csv' ) ) {
				$this->deny_access();
			}

			$this->handle_csv_download();
			return;
		}

		// Handle pagination.
		$page        = max( 1, (int) $this->get_query_arg( 'paged', 1 ) );
		$per_page    = apply_filters( 'foldspy_admin_per_page', 10 );
		$logs        = $this->storage->get_visits( $page, $per_page );
		$total       = $this->storage->get_total_rows();
		$total_pages = (int) ceil( $total / $per_page );

		$this->renderer->render( $logs );
		$this->renderer->render_pagination( $page, $total_pages );
	}

	/**
	 * Safely retrieve a sanitized query parameter.
	 *
	 * @param string     $key     The $_GET key to retrieve.
	 * @param mixed|null $fallback The default value if not set.
	 *
	 * @return string|null
	 */
	private function get_query_arg( string $key, $fallback = null ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return isset( $_GET[ $key ] )
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			? sanitize_text_field( wp_unslash( $_GET[ $key ] ) )
			: $fallback;
	}

	/**
	 * Verifies the nonce for a given action.
	 *
	 * This method checks if a nonce is present in the URL query and verifies it against the given action.
	 * It sanitizes and unslashes the nonce before verification.
	 *
	 * @param string $action The action to verify the nonce against.
	 * @return bool Returns true if the nonce is valid, false otherwise.
	 */
	private function verify_nonce( string $action ): bool {
		if ( ! isset( $_GET['_wpnonce'] ) ) {
			return false;
		}
		$nonce = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) );
		return wp_verify_nonce( $nonce, $action );
	}

	/**
	 * Denies access to the user due to a security check failure.
	 *
	 * This method stops the execution of the script and displays a message indicating a security check failure.
	 * It sets the HTTP response code to 403, indicating a forbidden access.
	 */
	private function deny_access(): void {
		wp_die(
			'Security check failed',
			'',
			array(
				'response' => 403,
			)
		);
	}

	/**
	 * Renders a single log entry based on its ID.
	 *
	 * This method retrieves a log entry by its ID from the storage and passes it to the renderer for rendering.
	 *
	 * @param int $id The ID of the log entry to render.
	 */
	private function render_single_log( int $id ): void {
		$log = $this->storage->get_visit( $id );
		$this->renderer->render_single_log( $log );
	}

	/**
	 * Handles the CSV download of logs.
	 *
	 * This method checks for authorization, sets up the CSV headers, and outputs the logs data to the CSV file.
	 */
	public function handle_csv_download(): void {
		// Check if the current user has the capability to manage options.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized', '', array( 'response' => 403 ) );
		}

		// Check the nonce for security.
		check_admin_referer( 'foldspy_export_csv' );

		// Apply a filter to determine the number of logs to export, defaulting to 1000.
		$per_page = apply_filters( 'foldspy_admin_export_limit', 1000 );
		// Retrieve the logs for export.
		$logs = $this->storage->get_visits( 1, $per_page );

		// Set the HTTP headers for CSV output.
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=foldspy-logs-' . gmdate( 'Y-m-d' ) . '.csv' );
		// Open the output stream for writing.
		$output = fopen( 'php://output', 'w' );
		// Output the CSV header.
		fputcsv( $output, array( 'Date', 'Width', 'Height', 'User Agent', 'Links' ) );

		// Loop through each log and output its data to the CSV.
		foreach ( $logs as $log ) {
			fputcsv(
				$output,
				array(
					$log['visit_time'],
					$log['screen_width'],
					$log['screen_height'],
					substr( $log['user_agent'], 0, 100 ),
					implode( ' | ', json_decode( $log['hrefs'], true ) ),
				)
			);
		}

		// Exit the script to prevent any further output.
		exit();
	}
}
