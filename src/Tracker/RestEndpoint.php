<?php

namespace FoldSpy\Tracker;

use FoldSpy\Support\Logger;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

class RestEndpoint {
	protected Logger $logger;

    public function __construct(Logger $logger) {
		$this->logger = $logger;
	}
    
	/**
	 * Initializes the REST endpoint by registering routes.
	 */
	public function boot(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Registers the REST route for logging data.
	 */
	public function register_routes(): void {
		register_rest_route( 'foldspy/v1', '/log', [
			'methods'  => WP_REST_Server::CREATABLE,
			'callback' => [ $this, 'handle_request' ],
			'permission_callback' => '__return_true',
		] );
	}

	/**
	 * Handles the incoming request, validates the data, and logs it.
	 * 
	 * @param WP_REST_Request $request The incoming request object.
	 */
	public function handle_request( WP_REST_Request $request ) {
		$data = $request->get_json_params();

		if (
			! isset($data['screenWidth'], $data['screenHeight'], $data['hrefs']) ||
			! is_array($data['hrefs'])
		) {
			$this->logger->log('Invalid request data received.', 'warning');
			return new WP_Error(
				'foldspy_invalid',
				'Invalid or missing data structure.',
				[ 'status' => 400 ]
			);
		}

		// Save to DB in next Phase 4 for now, just confirm
		error_log( 'FoldSpy received: ' . json_encode( $data ) );

        $this->logger->log('Visit logged successfully.');
		return new WP_REST_Response( [ 'message' => 'Received' ], 200 );
	}
}