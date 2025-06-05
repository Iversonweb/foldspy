<?php

namespace FoldSpy\Container;

use League\Container\ServiceProvider\AbstractServiceProvider;
use FoldSpy\Tracker\ScriptLoader;

class TrackerServiceProvider extends AbstractServiceProvider {
	/**
	 * Returns an array of services provided by this service provider.
	 * 
	 * @return array An array of services where the key is the service ID and the value is the class name.
	 */
	protected function getServices(): array {
		return [
			'FoldSpy\\Tracker\\ScriptLoader' => ScriptLoader::class,
		];
	}

	/**
	 * Checks if the service provider provides a service with the given ID.
	 * 
	 * @param string $id The ID of the service to check.
	 * @return bool Returns true if the service provider provides the service, false otherwise.
	 */
	public function provides( string $id ): bool {
		return array_key_exists( $id, $this->getServices() );
	}

	/**
	 * Registers the services provided by this service provider with the container.
	 * 
	 * This method iterates over the list of services and adds them to the container, setting them as shared.
	 * 
	 * @return void
	 */
	public function register(): void {
		foreach ( $this->getServices() as $id => $class ) {
			$this->getContainer()
				->add( $id, $class )
				->setShared( true );
		}
	}
}