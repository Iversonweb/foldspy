<?php
/**
 * Plugin Template
 *
 * @package     TO FILL
 * @author      Mathieu Lamiot
 * @copyright   TO FILL
 * @license     GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name: Fold Spy
 * Version:     Tracks above-the-fold hyperlinks seen on homepage visits.
 * Description: 1.0.0
 * Author:      Tobi Babatunde
 */

namespace Fold_Spy;

define( 'FOLD_SPY_PLUGIN_FILENAME', __FILE__ ); // Filename of the plugin, including the file.

if ( ! defined( 'ABSPATH' ) ) { // If WordPress is not loaded.
	exit( 'WordPress not loaded. Can not load the plugin' );
}

// Load the dependencies installed through composer.
require_once __DIR__ . '/src/plugin.php';
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/support/exceptions.php';

// Plugin initialization.
/**
 * Creates the plugin object on plugins_loaded hook
 *
 * @return void
 */
function foldspy_plugin_init() {
	FoldSpy_Plugin_Class::get_instance();
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\foldspy_plugin_init' );

register_activation_hook( __FILE__, __NAMESPACE__ . '\FoldSpy_Plugin_Class::wpc_activate' );
register_uninstall_hook( __FILE__, __NAMESPACE__ . '\FoldSpy_Plugin_Class::wpc_uninstall' );
