<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       https://qrolic.com
 * @since      1.0.0
 *
 * @package    PerformanceMonitor
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

wp_clear_scheduled_hook( 'qtpm_cron_job' );
wp_clear_scheduled_hook( 'qtpm_cron_job_month_avg' );
wp_clear_scheduled_hook( 'qtpm_inital_cron' );

$options = get_option( 'qtpm_setting_settings', array() );
delete_option( 'qtpm_setting_settings' );
if ( isset( $options['delete_on_uninstall'] ) && 'on' === $options['delete_on_uninstall'] ) {
	$plugin_post_types = get_option( 'qtpm_plugin_post_types', array( 'performance-monitor' ) );

	foreach ( $plugin_post_types as $plugin_post_type ) {
		$args = array(
			'post_type'      => $plugin_post_type,
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'fields'         => 'ids',
		);

		foreach ( get_posts( $args ) as $custom_post_id ) {
			wp_delete_post( $custom_post_id, true );
		}

		unregister_post_type( $plugin_post_type );
	}
}
