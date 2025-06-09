<?php if ( ! empty( $top_links ) ) : ?>
	<div class="foldspy-top-links">
		<h2>Top Seen Links (Last 7 Days)</h2>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th>Link</th>
					<th>Visits</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $top_links as $url => $count ) : ?>
					<tr>
						<td>
							<a href="<?php echo esc_url( $url ); ?>" target="_blank">
								<?php echo esc_html( $url ); ?>
							</a>
						</td>
						<td><?php echo esc_html( $count ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
<?php endif; ?>
