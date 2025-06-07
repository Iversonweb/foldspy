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
	 * Renders the admin interface for FoldSpy logs.
	 *
	 * This method generates a CSV export link with a nonce for security, displays the logs table, and renders pagination links.
	 * It takes an array of logs to be rendered, the current page number, and the total number of pages as parameters.
	 * The method first checks if there are any logs to render and if not, displays a message indicating no logs found.
	 * If there are logs, it iterates through each log and displays its details in a table.
	 * Additionally, it renders pagination links based on the current page and total pages.
	 *
	 * @param array $logs The array of logs to be rendered.
	 * @param int   $current_page The current page number.
	 * @param int   $total_pages The total number of pages.
	 */
	public function render( array $logs, int $current_page = 1, int $total_pages = 1 ): void {
		// Export CSV button.
		$export_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=foldspy_download_csv' ),
			'foldspy_export_csv'
		);
		?>
		
		<div class="wrap">
			<h1 class="wp-heading-inline">FoldSpy Logs</h1>
			<a href="<?php echo esc_url( $export_url ); ?>" class="page-title-action">Export CSV</a>
			<hr class="wp-header-end">
			<?php $this->render_top_links(); ?>

			<h2>List of Tracked Logs</h2>
			<form method="post">
				<table class="wp-list-table widefat fixed striped table-view-list">
					<thead>
						<tr>
							<th scope="col" class="manage-column">Date</th>
							<th scope="col" class="manage-column">Screen Size</th>
							<th scope="col" class="manage-column">Links</th>
							<th scope="col" class="manage-column">User Agent</th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $logs ) ) : ?>
							<tr><td colspan="4">No logs found.</td></tr>
						<?php else : ?>
							<?php foreach ( $logs as $log ) : ?>
								<tr>
									<td><?php echo esc_html( $log['visit_time'] ); ?></td>
									<td><?php echo esc_html( "{$log['screen_width']} x {$log['screen_height']}" ); ?></td>
									<td>
										<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=foldspy-logs&view_log=' . $log['id'] ), 'foldspy_view_log' ) ); ?>">
											View Details
										</a>
									</td>
									<td><?php echo esc_html( mb_strimwidth( $log['user_agent'], 0, 100, '...' ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
					<tfoot>
						<tr>
							<th>Date</th>
							<th>Screen Size</th>
							<th>Links</th>
							<th>User Agent</th>
						</tr>
					</tfoot>
				</table>
			</form>
	
			<?php $this->render_pagination( $current_page, $total_pages ); ?>
		</div>
		<?php
	}

	/**
	 * Renders pagination links for the FoldSpy logs.
	 *
	 * This method generates pagination links based on the current page and total pages.
	 *
	 * @param int $current_page The current page number.
	 * @param int $total_pages The total number of pages.
	 */
	public function render_pagination( int $current_page, int $total_pages ): void {
		if ( $total_pages <= 1 ) {
			return; // No pagination needed.
		}

		$base_url = remove_query_arg( 'paged' );
		$nonce    = wp_create_nonce( 'foldspy_pagination' );
		?>
		<div class="tablenav bottom">
			<div class="tablenav-pages">
				<span class="displaying-num">
					<?php
					printf(
						/* translators: %s: Number of items */
						esc_html( _n( '%s item', '%s items', $total_pages ) ),
						esc_html( number_format_i18n( $total_pages ) )
					);
					?>
				</span>
				<span class="pagination-links">
					<?php if ( $current_page > 1 ) : ?>
						<a class="prev-page button" href="
						<?php
						echo esc_url(
							add_query_arg(
								array(
									'paged'    => $current_page - 1,
									'_wpnonce' => $nonce,
								),
								$base_url
							)
						);
						?>
															" aria-label="Previous page">&laquo; Prev</a>
					<?php else : ?>
						<span class="tablenav-pages-navspan" aria-hidden="true">&laquo; Prev</span>
					<?php endif; ?>
	
					<span class="paging-input">
						<label for="current-page-selector" class="screen-reader-text">Current Page</label>
						<form method="get" action="<?php echo esc_url( $base_url ); ?>" style="display:inline;">
							<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $nonce ); ?>" />
							<input class="current-page" id="current-page-selector" type="number" name="paged" value="<?php echo esc_attr( $current_page ); ?>" min="1" max="<?php echo esc_attr( $total_pages ); ?>" size="1" aria-describedby="table-paging" />
							<span class="total-pages"> of <span class="total-pages-count"><?php echo esc_html( $total_pages ); ?></span></span>
						</form>
					</span>
	
					<?php if ( $current_page < $total_pages ) : ?>
						<a class="next-page button" href="
						<?php
						echo esc_url(
							add_query_arg(
								array(
									'paged'    => $current_page + 1,
									'_wpnonce' => $nonce,
								),
								$base_url
							)
						);
						?>
															" aria-label="Next page">Next &raquo;</a>
					<?php else : ?>
						<span class="tablenav-pages-navspan" aria-hidden="true">Next &raquo;</span>
					<?php endif; ?>
				</span>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders a single log entry.
	 *
	 * This method takes an optional log array as input and renders a detailed view of the log entry if it exists. If the log is not found, it displays a notice indicating that the log was not found.
	 *
	 * @param array|null $log The log entry to be rendered.
	 */
	public function render_single_log( ?array $log ): void {
		if ( ! $log ) :
			?>
			<div class="notice notice-error"><p>Log not found.</p></div>
			<?php
			return;
		endif;
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">Visit Details</h1>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=foldspy-logs' ) ); ?>" class="page-title-action" style="margin-left: 10px;">← Back to list</a>
			<hr class="wp-header-end">
	
			<table class="widefat fixed striped">
				<tbody>
					<tr>
						<th scope="row" style="width: 150px;">Date</th>
						<td><?php echo esc_html( $log['visit_time'] ); ?></td>
					</tr>
					<tr>
						<th scope="row">Screen Size</th>
						<td><?php echo esc_html( "{$log['screen_width']} x {$log['screen_height']}" ); ?></td>
					</tr>
					<tr>
						<th scope="row">User Agent</th>
						<td><?php echo esc_html( $log['user_agent'] ); ?></td>
					</tr>
					<tr>
						<th scope="row" valign="top">Links</th>
						<td>
							<ul style="margin: 0; padding-left: 20px;">
								<?php
								$hrefs = json_decode( $log['hrefs'], true );
								if ( is_array( $hrefs ) && count( $hrefs ) > 0 ) :
									foreach ( $hrefs as $href ) :
										?>
										<li>
											<a href="<?php echo esc_url( $href ); ?>" target="_blank" rel="noopener noreferrer">
												<?php echo esc_html( $href ); ?>
											</a>
										</li>
										<?php
									endforeach;
								else :
									?>
									<em>No links recorded.</em>
								<?php endif; ?>
							</ul>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Renders the top links seen in the last 7 days.
	 *
	 * This method retrieves the top links from the storage, checks if there are any links to display, and if so, renders a table with the top links and their view counts.
	 */
	private function render_top_links(): void {
		$top_links = $this->storage->get_top_links();

		// Check if there are any top links to display.
		if ( empty( $top_links ) ) :
			?>
			<p><em>No top links recorded yet.</em></p>
			<?php
			return;
		endif;
		?>
	
		<h2>Top 3 Links Seen (last 7 days)</h2>
		<table class="widefat fixed striped">
			<thead>
				<tr>
					<th scope="col">Link</th>
					<th scope="col" style="width: 100px;">Views</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $top_links as $link => $count ) : ?>
					<tr>
						<td>
							<a href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener noreferrer">
								<?php echo esc_html( $link ); ?>
							</a>
						</td>
						<td><?php echo esc_html( $count ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
			<tfoot>
				<tr>
					<th>Link</th>
					<th>Views</th>
			</tfoot>
		</table>
		<?php
	}
}