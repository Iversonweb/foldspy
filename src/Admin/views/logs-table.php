<div class="wrap">
	<h1 class="wp-heading-inline">FoldSpy Logs</h1>
	<a href="<?php echo esc_url( $export_url ); ?>" class="page-title-action">Export CSV</a>
	<hr class="wp-header-end">

	<?php // Include top links section. ?>
	<?php require __DIR__ . '/top-links.php'; ?>

	<h2>List of Tracked Logs</h2>
	<form method="post">
		<table class="wp-list-table widefat fixed striped table-view-list">
			<thead>
				<tr>
					<th>Date</th>
					<th>Screen Size</th>
					<th>Links</th>
					<th>User Agent</th>
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

	<?php // Include pagination section. ?>
	<?php require __DIR__ . '/pagination.php'; ?>
</div>
