<?php

namespace FoldSpy\Tracker;

class ScriptLoader {
	/**
	 * Boot method to add action for enqueueing script.
	 */
	public function boot(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_script' ] );
	}

	/**
	 * Method to enqueue script.
	 */
	public function enqueue_script(): void {
		// Only on homepage
		if ( ! is_front_page() || is_admin() ) {
			return;
		}

		wp_enqueue_script(
			'foldspy-tracker',
			plugins_url( '/assets/js/foldspy.js', FOLDSPY_PLUGIN_FILENAME ),
			[],
			'1.0.0',
			true
		);

		wp_localize_script(
			'foldspy-tracker',
			'FoldSpyData',
			[
				'nonce' => wp_create_nonce( 'wp_rest' ),
				'endpoint' => esc_url_raw( rest_url( 'foldspy/v1/log' ) ),
			]
		);
	}
}