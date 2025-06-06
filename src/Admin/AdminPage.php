<?php

namespace FoldSpy\Admin;

use FoldSpy\Tracker\Storage;
use FoldSpy\Admin\AdminRenderer;

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
	 * @param Storage $storage The Storage object for managing logs.
	 * @param AdminRenderer $renderer The AdminRenderer object for rendering the admin interface.
	 */
	public function __construct( Storage $storage, AdminRenderer $renderer ) {
		$this->storage = $storage;
		$this->renderer = $renderer;

        add_action('admin_post_foldspy_download_csv', [$this, 'handle_csv_download']);
	}

	/**
	 * Boots the AdminPage by adding the necessary hooks.
	 * 
	 * This method hooks into the 'admin_menu' action to add the FoldSpy logs menu page.
	 */
	public function boot(): void {
		add_action('admin_menu', [$this, 'add_menu']);
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
			[$this, 'render_page'],
			'dashicons-visibility'
		);
	}

	/**
	 * Renders the admin interface for FoldSpy logs.
	 * 
	 * This method generates a CSV export link with a nonce for security, displays the logs table, and renders pagination links.
	 * 
	 * @param array $logs The array of logs to be rendered.
	 */
	public function render_page() {
        if (isset($_GET['view_log'])) {
            return $this->render_single_log((int) $_GET['view_log']);
        }

        // Check if the 'action' parameter is set to 'download_csv' in the URL query and handle CSV download if it is.
        if (isset($_GET['action']) && $_GET['action'] === 'download_csv') {
            $this->handle_csv_download();
            return;
        }

		// Determine the current page number from the URL query, ensuring it's at least 1
		$page = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;
        
        $per_page = apply_filters('foldspy/admin_per_page', 2);
        $logs = $this->storage->get_visits($page, $per_page);

        $this->renderer->render($logs);

        // Calculate the total number of pages based on the total rows and rows per page
        $total = $this->storage->get_total_rows();
        $total_pages = ceil($total / $per_page);

        $this->renderer->render_pagination($page, $total_pages);
	}

    /**
     * Renders a single log entry based on its ID.
     * 
     * This method retrieves a log entry by its ID from the storage and passes it to the renderer for rendering.
     * 
     * @param int $id The ID of the log entry to render.
     */
    private function render_single_log(int $id): void {
        $log = $this->storage->get_visit($id);
        $this->renderer->render_single_log($log);
    }

    /**
     * Handles the CSV download of logs.
     * 
     * This method checks for authorization, sets up the CSV headers, and outputs the logs data to the CSV file.
     */
    public function handle_csv_download(): void {
        // Check if the current user has the capability to manage options
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized', '', ['response' => 403]);
        }
    
        // Check the nonce for security
        check_admin_referer('foldspy_export_csv');
    
        // Apply a filter to determine the number of logs to export, defaulting to 1000
        $per_page = apply_filters('foldspy/admin_export_limit', 1000);
        // Retrieve the logs for export
        $logs = $this->storage->get_visits(1, $per_page);
    
        // Set the HTTP headers for CSV output
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=foldspy-logs-' . date('Y-m-d') . '.csv');
        // Open the output stream for writing
        $output = fopen('php://output', 'w');
        // Output the CSV header
        fputcsv($output, ['Date', 'Width', 'Height', 'User Agent', 'Links']);
    
        // Loop through each log and output its data to the CSV
        foreach ($logs as $log) {
            fputcsv($output, [
                $log['visit_time'],
                $log['screen_width'],
                $log['screen_height'],
                substr($log['user_agent'], 0, 100),
                implode(' | ', json_decode($log['hrefs'], true)),
            ]);
        }
    
        // Close the output stream
        fclose($output);
        // Exit the script to prevent any further output
        exit();
    }
}