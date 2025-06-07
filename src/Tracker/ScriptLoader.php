<?php

namespace Fold_Spy\Tracker;

class ScriptLoader {
	/**
	 * Boot method to add action for enqueueing script.
	 */
	public function boot(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_script' ) );
	}

	/**
	 * Method to enqueue script.
	 */
	public function enqueue_script(): void {
		// Only on homepage.
		if ( ! is_front_page() || is_admin() ) {
			return;
		}

		wp_enqueue_script(
			'foldspy-tracker',
			plugins_url( '/assets/js/foldspy.js', FOLD_SPY_PLUGIN_FILENAME ),
			array(),
			'1.0.0',
			true
		);

		$script_data = array(
			'nonce'    => wp_create_nonce( 'wp_rest' ),
			'endpoint' => apply_filters(
				'foldspy_tracker_rest_endpoint',
				esc_url_raw( rest_url( 'foldspy/v1/log' ) )
			),
		);

		// Used instead of wp_localize_script to avoid creating a global variable.
		wp_add_inline_script(
			'foldspy-tracker',
			'const FoldSpyData = ' . wp_json_encode( $script_data ) . ';',
			'before'
		);
	}
}
