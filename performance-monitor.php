<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://qrolic.com
 * @since             1.0.0
 * @package           PerformanceMonitor
 *
 * @wordpress-plugin
 * Plugin Name:       Performance Monitor
 * Plugin URI:        https://qrolic.com
 * Description:       Performance monitoring tool for your site.
 * Version:           1.0.4
 * Author:            Qrolic
 * Author URI:        https://qrolic.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       performance-monitor
 * Domain Path:       /languages
 */

use PerformanceMonitor\Inc\Main;
use PerformanceMonitor\Inc\Activator;
use PerformanceMonitor\Inc\Deactivator;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Plugin name constant.
 */
if ( ! defined( 'QTPM_PLUGIN_NAME' ) ) {
	define( 'QTPM_PLUGIN_NAME', 'performance-monitor' );
}

/**
 * Plugin version constant.
 */
if ( ! defined( 'QTPM_PLUGIN_VERSION' ) ) {
	define( 'QTPM_PLUGIN_VERSION', '1.0.4' );
}

/**
 * Plugin Root File constant.
 */
if ( ! defined( 'QTPM_PLUGIN_FILE' ) ) {
	define( 'QTPM_PLUGIN_FILE', __FILE__ );
}

/**
 * Plugin base constant.
 */
if ( ! defined( 'QTPM_PLUGIN_BASE' ) ) {
	define( 'QTPM_PLUGIN_BASE', plugin_basename( QTPM_PLUGIN_FILE ) );
}

/**
 * Plugin Folder Path constant.
 */
if ( ! defined( 'QTPM_PLUGIN_DIR' ) ) {
	define( 'QTPM_PLUGIN_DIR', plugin_dir_path( QTPM_PLUGIN_FILE ) );
}

/**
 * Plugin Folder URL constant.
 */
if ( ! defined( 'QTPM_PLUGIN_URL' ) ) {
	define( 'QTPM_PLUGIN_URL', plugin_dir_url( QTPM_PLUGIN_FILE ) );
}

/**
 * Plugin post type constant.
 */
if ( ! defined( 'QTPM_PLUGIN_POST_TYPE' ) ) {
	define( 'QTPM_PLUGIN_POST_TYPE', 'performance-monitor' );
}

if ( ! function_exists( 'performance_monitor_activate' ) ) {
	/**
	 * Activate the plugin.
	 *
	 * This action is documented in includes/class-performance-monitor-activator.php
	 */
	function performance_monitor_activate() {
		require_once QTPM_PLUGIN_DIR . 'includes/class-activator.php';
		Activator::activate();
	}
}
register_activation_hook( __FILE__, 'performance_monitor_activate' );

if ( ! function_exists( 'performance_monitor_deactivate' ) ) {
	/**
	 * Deactivate the plugin.
	 *
	 * This action is documented in includes/class-performance-monitor-deactivator.php
	 */
	function performance_monitor_deactivate() {
		require_once QTPM_PLUGIN_DIR . 'includes/class-deactivator.php';
		Deactivator::deactivate();
	}
}
register_deactivation_hook( __FILE__, 'performance_monitor_deactivate' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-main.php';

if ( ! function_exists( 'performance_monitor_run' ) ) {
	/**
	 * Begins execution of the plugin.
	 *
	 * Since everything within the plugin is registered via hooks,
	 * then kicking off the plugin from this point in the file does
	 * not affect the page life cycle.
	 *
	 * @since    1.0.0
	 */
	function performance_monitor_run() {
		$plugin = new Main();
		$plugin->run();
	}
}
performance_monitor_run();
