<?php

namespace Fold_Spy\Container;

use League\Container\ServiceProvider\AbstractServiceProvider;
use Fold_Spy\Tracker\ScriptLoader;
use Fold_Spy\Tracker\RestEndpoint;
use Fold_Spy\Tracker\LogSchema;
use Fold_Spy\Tracker\Storage;
use Fold_Spy\Tracker\LogCleanup;

class TrackerServiceProvider extends AbstractServiceProvider {
	/**
	 * Array of services provided by this service provider.
	 *
	 * @var array An array of services where the key is the service ID and the value is the class name.
	 */
	protected $provides = array(
		'Fold_Spy\\Tracker\\ScriptLoader' => ScriptLoader::class,
		'Fold_Spy\\Tracker\\RestEndpoint' => RestEndpoint::class,
		'Fold_Spy\\Tracker\\LogSchema'    => LogSchema::class,
		'Fold_Spy\\Tracker\\Storage'      => Storage::class,
		'Fold_Spy\\Tracker\\LogCleanup'   => LogCleanup::class,
	);

	/**
	 * Array of arguments to be passed to services during instantiation.
	 *
	 * @var array An array of arguments where the key is the service ID and the value is an array of argument class names.
	 */
	protected $arguments = array(
		'Fold_Spy\\Tracker\\RestEndpoint' => array(
			'Fold_Spy\\Tracker\\Storage',
			'Fold_Spy\\Support\\Logger',
		),
		'Fold_Spy\\Tracker\\LogCleanup'   => array(
			'Fold_Spy\\Tracker\\Storage',
			'Fold_Spy\\Support\\Logger',
		),
		'Fold_Spy\\Tracker\\Storage'      => array(
			'Fold_Spy\\Support\\Logger',
		),
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
