<?php

namespace FoldSpy\Container;

use League\Container\ServiceProvider\AbstractServiceProvider;
use FoldSpy\Container\TrackerServiceProvider;
use FoldSpy\Container\SupportServiceProvider;

class ServiceProvider extends AbstractServiceProvider {
	/**
	 * Array of service IDs to be booted automatically.
	 * 
	 * @var array
	 */
	protected $provides = [
		'FoldSpy\\Tracker\\ScriptLoader',
		'FoldSpy\\Tracker\\RestEndpoint',
	];
	
	/**
	 * Checks if the service provider provides a service with the given ID.
	 * 
	 * @param string $id The ID of the service to check.
	 * @return bool Returns true if the service provider provides the service, false otherwise.
	 */
	public function provides( string $id ): bool {
		return $id === 'bootable';
	}

	/**
	 * List of all modular service providers to register.
	 */
	protected function getProviders(): array {
		return [
			TrackerServiceProvider::class,
			SupportServiceProvider::class,
		];
	}
	
	/**
	 * Registers the service providers and bootable services with the container.
	 * 
	 * This method iterates over the list of modular service providers and adds them to the container.
	 * It also registers a service named 'bootable' which is a collection of services marked as bootable.
	 * 
	 * @return void
	 */
	public function register(): void {
		$container = $this->getContainer();

		// Register each sub-provider
		foreach ( $this->getProviders() as $provider ) {
			$container->addServiceProvider( new $provider() );
		}

		// Register bootable services
		$container->add( 'bootable', function () use ( $container ) {
			return array_map(
				fn( $id ) => $container->get( $id ),
				$this->provides
			);
		});
	}
}