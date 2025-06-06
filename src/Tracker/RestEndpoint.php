<?php

namespace FoldSpy\Tracker;

use FoldSpy\Support\Logger;
use FoldSpy\Tracker\Storage;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

class RestEndpoint {
	/**
	 * The storage object for managing data persistence.
	 * 
	 * @var Storage
	 */
	protected Storage $storage;
    
	/**
	 * The logger object for logging events and errors.
	 * 
	 * @var Logger
	 */
	protected Logger $logger;

    /**
     * Initializes the RestEndpoint with the required dependencies.
     * 
     * @param Storage $storage The storage object for managing data persistence.
     * @param Logger $logger The logger object for logging events and errors.
     */
    public function __construct( Storage $storage, Logger $logger ) {
        $this->storage = $storage;
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

        // Check if the required data is set and if the 'hrefs' data is an array
        if (
            ! isset($data[ 'screenWidth' ], $data[ 'screenHeight' ], $data[ 'hrefs' ]) ||
            ! is_array( $data[ 'hrefs' ] )
        ) {
            $this->logger->log(
                'Invalid request data received.', 
                'warning'
            );
            return new WP_Error(
                'foldspy_invalid_data', 
                'Malformed input', 
                [ 'status' => 400 ]
            );
        }
    
        //Security check: nonce + capability
        $nonce = $request->get_header( 'X-WP-Nonce' );
        if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) || ! current_user_can( 'read' ) ) {
            $this->logger->log(
                'Unauthorized Access', 
                'error'
            );
            return new WP_Error(
                'foldspy_forbidden', 
                'Unauthorized Access', 
                [ 'status' => 403 ]
            );
        }
    
        $user_id = get_current_user_id();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
        // Attempt to log the visit to the database
        $success = $this->storage->log_visit( [
            'user_id' => $user_id,
            'screenWidth' => $data[ 'screenWidth' ],
            'screenHeight' => $data[ 'screenHeight' ],
            'hrefs' => $data[ 'hrefs' ],
            'user_agent' => $user_agent,
        ] );
    
        // Check if the logging was successful
        if ( ! $success ) {
            $this->logger->log(
                'Failed to log visit to DB', 
                'error'
            );
            return new WP_Error( 'foldspy_db_error', 'Could not store visit', [ 'status' => 500 ] );
        }
    
        $this->logger->log(
            'Visit logged successfully.'
        );
        return new WP_REST_Response( [ 'message' => 'Visit recorded' ], 200 );
	}
}