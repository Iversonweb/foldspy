<?php if ( ! $log ) : ?>
	<div class="notice notice-error"><p>Log not found.</p></div>
	<?php return; ?>
<?php endif; ?>

<div class="wrap">
	<h1 class="wp-heading-inline">Visit Details</h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=foldspy-logs' ) ); ?>" class="page-title-action" style="margin-left: 10px;">← Back to list</a>
	<hr class="wp-header-end">

	<table class="widefat fixed striped">
		<tbody>
			<tr><th style="width:150px;">Date</th><td><?php echo esc_html( $log['visit_time'] ); ?></td></tr>
			<tr><th>Screen Size</th><td><?php echo esc_html( "{$log['screen_width']} x {$log['screen_height']}" ); ?></td></tr>
			<tr><th>User Agent</th><td><?php echo esc_html( $log['user_agent'] ); ?></td></tr>
			<tr>
				<th>Links</th>
				<td>
					<ul style="margin:0; padding-left:20px;">
						<?php
						// Decode the JSON string of hrefs into an array.
						$hrefs = json_decode( $log['hrefs'], true );
						if ( is_array( $hrefs ) && count( $hrefs ) > 0 ) :
							foreach ( $hrefs as $href ) :
								?>
								<li><a href="<?php echo esc_url( $href ); ?>" target="_blank"><?php echo esc_html( $href ); ?></a></li>
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
