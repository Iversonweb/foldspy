<?php
/**
 * Pagination view for the FoldSpy logs table.
 *
 * @package FoldSpy
 */

// Check if there's only one page to avoid displaying pagination.
if ( $total_pages <= 1 ) {
	return;
}

// Construct the base URL without the 'paged' query argument.
$base_url = remove_query_arg( 'paged' );
// Generate a nonce for security purposes.
$nonce = wp_create_nonce( 'foldspy_pagination' );
?>

<div class="tablenav bottom">
	<div class="tablenav-pages">
		<span class="displaying-num">
			<?php
			/* translators: %s: Number of items. */
			echo esc_html( sprintf( _n( '%s item', '%s items', $total_pages ), number_format_i18n( $total_pages ) ) );
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
													">&laquo; Prev</a>
			<?php else : ?>
				<span class="tablenav-pages-navspan">&laquo; Prev</span>
			<?php endif; ?>

			<span class="paging-input">
				<form method="get" action="<?php echo esc_url( $base_url ); ?>" style="display:inline;">
					<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $nonce ); ?>" />
					<input class="current-page" id="current-page-selector" type="number" name="paged"
							value="<?php echo esc_attr( $current_page ); ?>" min="1" max="<?php echo esc_attr( $total_pages ); ?>" size="1" />
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
													">Next &raquo;</a>
			<?php else : ?>
				<span class="tablenav-pages-navspan">Next &raquo;</span>
			<?php endif; ?>
		</span>
	</div>
</div>
