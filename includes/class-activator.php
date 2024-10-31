<?php
/**
 * Fired during plugin activation
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

if ( ! class_exists( 'PerformanceMonitor\Inc\Activator' ) ) {
	/**
	 * Class Activator
	 *
	 * Defines all code necessary to run during the plugin's activation.
	 *
	 * @since      1.0.0
	 */
	class Activator {

		/**
		 * Activates the plugin.
		 *
		 * This function is called when the plugin is activated. It sets default values for plugin settings,
		 * updates options, schedules the performance monitor cron on first plugin activation, and flushes rewrite rules.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public static function activate() {
			$setting_options = get_option( 'qtpm_setting_settings', array() );

			$setting_options = array_merge(
				array(
					'cron_frequency' => empty( $setting_options['cron_frequency'] ) ? 'weekly' : sanitize_text_field( $setting_options['cron_frequency'] ),
					'cron_day'       => empty( $setting_options['cron_day'] ) ? strtolower( gmdate( 'l' ) ) : sanitize_text_field( $setting_options['cron_day'] ),
					'cron_month_day' => empty( $setting_options['cron_month_day'] ) ? gmdate( 'j' ) : absint( $setting_options['cron_month_day'] ),
					'cron_time'      => empty( $setting_options['cron_time'] ) ? gmdate( 'H:i' ) : sanitize_text_field( $setting_options['cron_time'] ),
				),
				$setting_options
			);

			update_option( 'qtpm_setting_settings', $setting_options );

			wp_schedule_single_event( time() + 60, 'qtpm_inital_cron' );

			flush_rewrite_rules();
		}
	}
}
