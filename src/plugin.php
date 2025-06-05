<?php
/**
 * Plugin main class
 *
 * @package     TO FILL
 * @since       TO FILL
 * @author      Mathieu Lamiot
 * @license     GPL-2.0-or-later
 */

namespace FoldSpy;
use League\Container\Container as LeagueContainer;
use FoldSpy\Container\ServiceProvider;

/**
 * Main plugin class. It manages initialization, install, and activations.
 */
class FoldSpy_Plugin_Class {
	/**
     * The singleton instance of this container.
     */
    private static ?self $instance = null;

     /**
     * The underlying League\Container instance.
     */
    private LeagueContainer $container;

	/**
	 * Manages plugin initialization
	 *
	 * @return void
	 */
	public function __construct() {
		$this->container = new LeagueContainer();
		$this->container->addServiceProvider( new ServiceProvider() );

		// Register plugin lifecycle hooks.
		register_deactivation_hook( FOLDSPY_PLUGIN_FILENAME, array( $this, 'wpc_deactivate' ) );

		// Boot all tagged services
		foreach ( $this->container->get( 'bootable' ) as $service ) {
			if ( method_exists( $service, 'boot' ) ) {
				$service->boot();
			}
		}
	}

	/**
     * Retrieve the singleton instance of the container.
     */
    public static function get_instance(): self {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

	/**
     * Return the underlying League\Container instance.
     * Useful if you need to resolve or register other services directly.
     */
    public function get_container(): LeagueContainer {
        return $this->container;
    }

	/**
	 * Handles plugin activation:
	 *
	 * @return void
	 */
	public static function wpc_activate() {
		// Security checks.
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		$plugin = isset( $_REQUEST['plugin'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['plugin'] ) ) : '';
		check_admin_referer( "activate-plugin_{$plugin}" );
	}

	/**
	 * Handles plugin deactivation
	 *
	 * @return void
	 */
	public function wpc_deactivate() {
		// Security checks.
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		$plugin = isset( $_REQUEST['plugin'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['plugin'] ) ) : '';
		check_admin_referer( "deactivate-plugin_{$plugin}" );
	}

	/**
	 * Handles plugin uninstall
	 *
	 * @return void
	 */
	public static function wpc_uninstall() {

		// Security checks.
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
	}
}
