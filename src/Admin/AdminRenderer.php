<?php

namespace Fold_Spy\Admin;

use Fold_Spy\Tracker\Storage;

class AdminRenderer {
	/**
	 * The storage object for managing logs.
	 *
	 * @var Storage
	 */
	private Storage $storage;

	/**
	 * Initializes the AdminRenderer with a Storage object.
	 *
	 * This constructor sets the Storage object for managing logs within the AdminRenderer.
	 *
	 * @param Storage $storage The Storage object for managing logs.
	 */
	public function __construct( Storage $storage ) {
		$this->storage = $storage;
	}

	/**
	 * Renders the logs table view.
	 *
	 * This method generates a CSV export link, retrieves top links, and includes the logs table view.
	 *
	 * @param array $logs The array of logs to render.
	 * @param int   $current_page The current page number. Defaults to 1.
	 * @param int   $total_pages The total number of pages. Defaults to 1.
	 */
	public function render( array $logs, int $current_page = 1, int $total_pages = 1 ): void {
		$view_data = array(
			'logs'         => $logs,
			'export_url'   => wp_nonce_url( admin_url( 'admin-post.php?action=foldspy_download_csv' ), 'foldspy_export_csv' ),
			'current_page' => $current_page,
			'total_pages'  => $total_pages,
			'top_links'    => $this->storage->get_top_links(),
		);

		$this->render_view( 'logs-table.php', $view_data );
	}

	/**
	 * Renders the pagination links.
	 *
	 * This method includes the pagination view.
	 *
	 * @param int $current_page The current page number.
	 * @param int $total_pages The total number of pages.
	 */
	public function render_pagination( int $current_page, int $total_pages ): void {
		$view_data = array(
			'current_page' => $current_page,
			'total_pages'  => $total_pages,
		);

		$this->render_view( 'pagination.php', $view_data );
	}

	/**
	 * Renders a single log entry view.
	 *
	 * This method includes the single log view.
	 *
	 * @param ?array $log The log entry to render, or null if not found.
	 */
	public function render_single_log( ?array $log ): void {
		$view_data = array(
			'log' => $log,
		);

		$this->render_view( 'single-log.php', $view_data );
	}

	/**
	 * Helper method to render a view file with data.
	 *
	 * @param string $view_file The view file to render.
	 * @param array  $data The data to make available to the view.
	 */
	private function render_view( string $view_file, array $data ): void {
		// Make data available to the view.
		foreach ( $data as $key => $value ) {
			${$key} = $value;
		}

		include __DIR__ . '/views/' . $view_file;
	}
}
