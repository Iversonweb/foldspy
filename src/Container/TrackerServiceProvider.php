<?php

namespace FoldSpy\Container;

use League\Container\ServiceProvider\AbstractServiceProvider;
use FoldSpy\Tracker\ScriptLoader;
use \FoldSpy\Tracker\RestEndpoint;

class TrackerServiceProvider extends AbstractServiceProvider {
	/**
	 * Returns an array of services provided by this service provider.
	 * 
	 * @var array An array of services where the key is the service ID and the value is the class name.
	 */
	protected $provides =  [
		'FoldSpy\\Tracker\\ScriptLoader' => ScriptLoader::class,
		'FoldSpy\\Tracker\\RestEndpoint' => RestEndpoint::class,
	];

	/**
	 * Array of arguments to be passed to services during instantiation.
	 * 
	 * @var array An array of arguments where the key is the service ID and the value is an array of argument class names.
	 */
	protected $arguments = [
        'FoldSpy\\Tracker\\RestEndpoint' => [
            'FoldSpy\\Support\\Logger',
        ],
    ];

	/**
	 * Checks if the service provider provides a service with the given ID.
	 * 
	 * @param string $id The ID of the service to check.
	 * @return bool Returns true if the service provider provides the service, false otherwise.
	 */
	public function provides( string $id ): bool {
		return array_key_exists( $id, $this->provides );
	}

	/**
	 * Registers the services provided by this service provider with the container.
	 * 
	 * This method iterates over the list of services and adds them to the container, setting them as shared.
	 * 
	 * @return void
	 */
	public function register(): void {
		foreach ( $this->provides as $id => $class ) {
            $container = $this->getContainer()
                ->add( $id, $class )
                ->setShared( true );

            if ( isset( $this->arguments[ $id ] ) ) {
                foreach ( $this->arguments[ $id ] as $arg ) {
                    $container->addArgument( $arg );
                }
            }
        }
	}
}