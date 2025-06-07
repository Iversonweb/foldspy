<?php

namespace Fold_Spy\Container;

use League\Container\ServiceProvider\AbstractServiceProvider;
use Fold_Spy\Support\Logger;

class SupportServiceProvider extends AbstractServiceProvider {
	/**
	 * Array of services provided by this service provider.
	 *
	 * @var array An array of services where the key is the service ID and the value is the class name.
	 */
	protected array $provides = array(
		'Fold_Spy\\Support\\Logger' => Logger::class,
	);

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
			$this->getContainer()
				->add( $id, $class )
				->setShared( true );
		}
	}
}
