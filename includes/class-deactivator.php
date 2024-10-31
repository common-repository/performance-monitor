<?php
/**
 * Fired during plugin deactivation
 *
 * @link       https://qrolic.com
 * @since      1.0.0
 *
 * @package    PerformanceMonitor
 * @subpackage includes
 */

namespace PerformanceMonitor\Inc;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'PerformanceMonitor\Inc\Deactivator' ) ) {
	/**
	 * Class Deactivator
	 *
	 * This class defines all code necessary to run during the plugin's deactivation.
	 *
	 * @since      1.0.0
	 */
	class Deactivator {

		/**
		 * Deactivates the plugin.
		 *
		 * This function is called when the plugin is deactivated.
		 *
		 * @since    1.0.0
		 */
		public static function deactivate() {
			$plugin_post_type = get_option( 'qtpm_plugin_post_types', array( 'performance-monitor' ) );

			if ( ! in_array( QTPM_PLUGIN_POST_TYPE, $plugin_post_type, true ) ) {
				$plugin_post_type[] = QTPM_PLUGIN_POST_TYPE;
			}

			update_option( 'qtpm_plugin_post_types', $plugin_post_type );
		}
	}
}
