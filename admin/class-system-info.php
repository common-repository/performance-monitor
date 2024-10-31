<?php

/**
 * The cache functionality of the plugin.
 *
 * @link       https://qrolic.com
 * @since      1.0.0
 *
 * @package    PerformanceMonitor
 * @subpackage Admin
 */

namespace PerformanceMonitor\Admin;

use PerformanceMonitor\Inc\General;
use PerformanceMonitor\Inc\Util;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'PerformanceMonitor\Admin\System_Info' ) ) {
	/**
	 * Class System_Info
	 *
	 * Provides methods to gather information about the caching status of the server.
	 *
	 * @since   1.0.0
	 */
	class System_Info {

		/**
		 * Get cache information including object cache status, browser cache status,
		 * active cache plugins, server details, memory usage, and server response time.
		 *
		 * @since 1.0.0
		 * @access public
		 *
		 * @return array Cache information array.
		 */
		public static function get_cache_info() {
			$cache_control = isset( $_SERVER['HTTP_CACHE_CONTROL'] ) ? sanitize_text_field( $_SERVER['HTTP_CACHE_CONTROL'] ) : '';
			$expires       = isset( $_SERVER['HTTP_EXPIRES'] ) ? sanitize_text_field( $_SERVER['HTTP_EXPIRES'] ) : '';

			$cache_info = array(
				'object-cache'         => wp_using_ext_object_cache() ? 'Enabled' : 'Disabled',
				'browser-cache'        => strpos( $cache_control, 'no-cache' ) !== false ? 'Enabled' : 'Disabled',
				'active-cache_plugin'  => self::get_active_cache_plugin(),
				'server-response-time' => self::measure_server_response_time(),
				'peak-memory-usage'    => Util::qtpm_format_bytes( memory_get_peak_usage( true ) ),
				'current-memory-usage' => Util::qtpm_format_bytes( memory_get_usage() ),
			);

			$existing_posts = General::get_post_ids_by_meta_key( 'cached_cache_data' );

			$post_id = ( ! empty( $existing_posts ) ) ? $existing_posts[0] : wp_insert_post(
				array(
					'post_type'   => QTPM_PLUGIN_POST_TYPE,
					'post_status' => 'publish',
					'post_title'  => 'Cached Cache Data',
				),
			);

			if ( $post_id ) {
				update_post_meta( $post_id, 'cached_cache_data', $cache_info );
			}
			return $cache_info;
		}

		/**
		 * Retrieves system information details from cached data.
		 *
		 * If no cached data is available, fetches the system information directly.
		 *
		 * @return array The system information details.
		 */
		public static function get_system_info_detail() {
			$system_info = General::get_cached_data( 'cached_cache_data' );
			if ( empty( $system_info ) ) {
				$system_info = self::get_cache_info();
			}

			return $system_info;
		}

		/**
		 * Retrieves cached data for installed plugins, including version and update information.
		 *
		 * If cached data is not available, fetches the installed plugins data directly.
		 *
		 * @return array An array of plugin information, including version, last updated time,
		 * update availability, latest version, and update link.
		 */
		public static function get_cached_installed_plugins_data() {
			$plugins_info = General::get_cached_data( 'cached_plugins_data' );

			if ( empty( $plugins_info ) ) {
				return self::get_installed_plugins_data();
			}

			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$all_plugins = get_plugins();
			$update_data = get_site_transient( 'update_plugins' );

			foreach ( $plugins_info as $index => $plugin ) {
				$plugin_path           = $plugin['pluginPath'];
				$slug                  = dirname( $plugin_path );
				$plugin_latest_version = isset( $update_data->response[ $plugin_path ] ) ? esc_html( $update_data->response[ $plugin_path ]->new_version ) : '';
				$update_available      = isset( $update_data->response[ $plugin_path ] );

				$plugins_info[ $index ]['Version']         = $all_plugins[ $plugin_path ]['Version'];
				$plugins_info[ $index ]['LastUpdated']     = gmdate( 'Y-m-d H:i:s', filemtime( WP_PLUGIN_DIR . '/' . $plugin_path ) );
				$plugins_info[ $index ]['UpdateAvailable'] = $update_available ? 'yes' : 'no';
				$plugins_info[ $index ]['LatestVersion']   = $plugin_latest_version;
				$plugins_info[ $index ]['UpdateLink']      = $update_available ? self::plugin_upgrade_link( $plugin['Name'], $slug, $plugin_latest_version ) : '';
			}

			return $plugins_info;
		}

		/**
		 * Get installed plugins data.
		 *
		 * Retrieves filtered details of installed plugins including update availability.
		 *
		 * @since  1.0.0
		 * @access public
		 * @return array Filtered details of installed plugins.
		 */
		public static function get_installed_plugins_data() {
			$filtered_details = array();

			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$all_plugins    = get_plugins();
			$update_data    = get_site_transient( 'update_plugins' );
			$builder_plugin = General::get_plugins_from_api( 'page builder' );

			if ( empty( $all_plugins ) ) {
				return $filtered_details;
			}

			foreach ( $all_plugins as $plugin_path => $plugin_info ) {
				$slug = dirname( $plugin_path );

				if ( empty( $slug ) || '.' === $slug ) {
					$slug = str_replace( '.php', '', basename( $plugin_path ) );
				}

				$plugin_latest_version = isset( $update_data->response[ $plugin_path ] ) ? esc_html( $update_data->response[ $plugin_path ]->new_version ) : '';
				$update_link           = '';

				if ( isset( $update_data->response[ $plugin_path ] ) ) {
					$update_link = self::plugin_upgrade_link( $plugin_info['Name'], $slug, $plugin_latest_version );
				}

				$filtered_details[] = array(
					'Name'            => $plugin_info['Name'],
					'Version'         => $plugin_info['Version'],
					'UpdateAvailable' => isset( $update_data->response[ $plugin_path ] ) ? 'yes' : 'no',
					'UpdateLink'      => $update_link,
					'LatestVersion'   => $plugin_latest_version,
					'LastUpdated'     => gmdate( 'Y-m-d H:i:s', filemtime( WP_PLUGIN_DIR . '/' . $plugin_path ) ),
					'Author'          => $plugin_info['Author'],
					'BuilderPlugin'   => array_key_exists( $slug, $builder_plugin ) ? 'yes' : 'no',
					'RequiresPHP'     => $plugin_info['RequiresPHP'],
					'RequiresWP'      => $plugin_info['RequiresWP'],
					'IsActive'        => is_plugin_active( $plugin_path ) ? 'yes' : 'no',
					'pluginPath'      => $plugin_path,
				);
			}

			General::store_post_data( 'cached_plugins_data', $filtered_details, 'Cached Plugins Data' );

			return $filtered_details;
		}

		/**
		 * Retrieves the details of scheduled cron events.
		 *
		 * This method gathers all cron events and their respective callback functions,
		 * schedules, arguments, and next run times.
		 *
		 * @since 1.0.0
		 * @access public
		 * @return array Details of scheduled cron events.
		 */
		public static function get_schedule_cron_detail() {
			$cron_events = _get_cron_array();
			$cron_detail = array();

			global $wp_filter;

			$default_cron_hooks = array(
				'wp_version_check', // 2.7.0
				'wp_update_plugins', // 2.7.0
				'wp_update_themes', // 2.7.0
				'wp_scheduled_delete', // 2.9.0
				'update_network_counts', // 3.1.0
				'wp_scheduled_auto_draft_delete', // 3.4.0
				'delete_expired_transients', // 4.9.0
				'wp_privacy_delete_old_export_files', // 4.9.6
				'recovery_mode_clean_expired_keys', // 5.2.0
				'wp_site_health_scheduled_check', // 5.4.0
				'wp_https_detection', // 5.7.0
				'wp_update_user_counts', // 6.0.0
				'do_pings', // 2.1.0
				'publish_future_post', // 2.1.0
				'importer_scheduled_cleanup', // 2.5.0
				'upgrader_scheduled_cleanup', // 3.2.2
				'wp_maybe_auto_update', // 3.7.0
				'wp_split_shared_term_batch', // 4.3.0
				'wp_update_comment_type_batch', // 5.5.0
				'wp_delete_temp_updater_backups', // 5.9.0
			);

			foreach ( $cron_events as $timestamp => $hooks ) {
				foreach ( $hooks as $hook => $events ) {
					foreach ( $events as $event ) {

						$callbacks = array();

						if ( isset( $wp_filter[ $hook ] ) ) {
							foreach ( $wp_filter[ $hook ]->callbacks as $priority => $callback_list ) {
								foreach ( $callback_list as $callback_item ) {
									if ( is_array( $callback_item['function'] ) ) {

										if ( is_object( $callback_item['function'][0] ) ) {
											$class  = get_class( $callback_item['function'][0] );
											$access = '->';
										} else {
											$class  = $callback_item['function'][0];
											$access = '::';
										}

										$callbacks[] = $class . $access . $callback_item['function'][1] . '()';
									} elseif ( is_string( $callback_item['function'] ) ) {
										$callbacks[] = $callback_item['function'] . '()';
									} elseif ( $callback_item['function'] instanceof \Closure ) {
										$callbacks[] = __( 'Anonymous function', 'performance-monitor' );
									} else {
										$callbacks[] = __( 'Unknown function type', 'performance-monitor' );
									}
								}
							}
						}

						$is_default_cron = '';
						if ( in_array( $hook, $default_cron_hooks, true ) ) {
							$is_default_cron = true;

							if ( empty( $callbacks ) ) {
								$callbacks = array( $hook . '()' );
							}
						} else {
							$is_default_cron = false;
						}

						$callbacks     = ! empty( $callbacks ) ? $callbacks : array( 'None' );
						$cron_detail[] = array(
							'hook'           => esc_html( $hook ),
							'schedule'       => $event['schedule'] ? $event['schedule'] : __( 'One-time', 'performance-monitor' ),
							'callback'       => implode( ', ', $callbacks ),
							'args'           => ! empty( $event['args'] ) ? implode( ', ', $event['args'] ) : __( 'None', 'performance-monitor' ),
							'next_run'       => esc_html( gmdate( 'Y-m-d H:i:s', $timestamp ) ),
							'next_run_local' => get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $timestamp ), 'c' ),
							'is_default'     => $is_default_cron,
						);
					}
				}
			}

			return $cron_detail;
		}

		/**
		 * Generate plugin upgrade link.
		 *
		 * @since 1.0.0
		 * @access public
		 *
		 * @param string $title               Plugin title.
		 * @param string $name                Plugin name.
		 * @param string $v                   Plugin version.
		 * @return string Plugin upgrade link.
		 */
		public static function plugin_upgrade_link( $title, $name, $v ) {
			$details_url = self_admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . $name . '&section=changelog&TB_iframe=true&width=600&height=800' );
			$file        = $name . '/' . $name . '.php';

			$msg = sprintf(
				/* translators: 1: plugin title, 2: plugin version */
				__( '<a href="%1$s" %2$s>View version %3$s details</a> or <a href="%4$s" %5$s target="_blank">update now</a>.', 'performance-monitor' ),
				esc_url( $details_url ),
				sprintf(
					/* translators: 1: plugin title, 2: plugin version */
					'class="thickbox open-plugin-details-modal" aria-label="%s"',
					esc_attr(
						sprintf(
							/* translators: 1: plugin title, 2: plugin version */
							__( 'View %1$s version %2$s details', 'performance-monitor' ),
							$title,
							$v
						),
					),
				),
				esc_html( $v ),
				wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=' ) . $file, 'upgrade-plugin_' . $file ),
				sprintf(
					'class="update-link" aria-label="%s"',
					esc_attr(
						sprintf(
							/* translators: %s: plugin title */
							__( 'Update %s now', 'performance-monitor' ),
							$title
						),
					),
				),
			);

			return $msg;
		}

		/**
		 * Get active cache plugin.
		 *
		 * @since 1.0.0
		 * @access private
		 *
		 * @return array|string Active cache plugin or 'No Cache Plugin' if not active.
		 */
		private static function get_active_cache_plugin() {
			$active_cache_plugin = array();
			$cache_plugins       = General::get_plugins_from_api( 'cache plugin' );

			$active_plugins = get_option( 'active_plugins' );

			foreach ( $active_plugins as $plugin ) {
				$plugin_slug = basename( $plugin, '.php' );
				if ( array_key_exists( $plugin_slug, $cache_plugins ) ) {
					$active_cache_plugin[] = $cache_plugins[ $plugin_slug ];
				}
			}

			return ! empty( $active_cache_plugin ) ? $active_cache_plugin : 'No Cache Plugin';
		}

		/**
		 * Get the PHP details.
		 *
		 * @since 1.0.0
		 * @access public
		 * @static
		 *
		 * @return array An array containing PHP details.
		 */
		public static function get_php_details() {
			return array(
				'version'             => phpversion() ?? null,
				'SAPI'                => php_sapi_name() ?? null,
				'user'                => get_current_user() ?? null,
				'max-execution-time'  => ini_get( 'max_execution_time' ) ?? null,
				'memory-limit'        => ini_get( 'memory_limit' ) ?? null,
				'upload-max-filesize' => ini_get( 'upload_max_filesize' ) ?? null,
				'post-max-size'       => ini_get( 'post_max_size' ) ?? null,
				'display-errors'      => ini_get( 'display_errors' ) ?? null,
				'log-errors'          => ini_get( 'log_errors' ) ?? null,
				'error-reporting'     => ini_get( 'error_reporting' ) ?? null,
				'extensions'          => count( get_loaded_extensions() ),
			);
		}

		/**
		 * Get the database details.
		 *
		 * @since 1.0.0
		 * @access public
		 * @static
		 * @global \wpdb $wpdb WordPress database abstraction object.
		 *
		 * @return array An array containing database details.
		 */
		public static function get_database_details() {
			global $wpdb;

			$innodb_buffer_pool_size = General::get_mysql_var( 'innodb_buffer_pool_size' );
			$key_buffer_size         = General::get_mysql_var( 'key_buffer_size' );
			$max_allowed_packet      = General::get_mysql_var( 'max_allowed_packet' );
			$max_connections         = General::get_mysql_var( 'max_connections' );

			return array(
				'server-version'          => $wpdb->db_version(),
				'extension'               => get_class( $wpdb->dbh ),
				'client-version'          => $wpdb->dbh->client_info ?? 'Unknown',
				'user'                    => $wpdb->dbuser,
				'host'                    => $wpdb->dbhost,
				'database'                => $wpdb->dbname,
				'innodb-buffer-pool-size' => $innodb_buffer_pool_size . ' (~' . Util::size_convertion( $innodb_buffer_pool_size ) . ')',
				'key-buffer-size'         => $key_buffer_size . ' (~' . Util::size_convertion( $key_buffer_size ) . ')',
				'max-allowed-packet'      => $max_allowed_packet . ' (~' . Util::size_convertion( $max_allowed_packet ) . ')',
				'max-connections'         => $max_connections,
			);
		}

		/**
		 * Get the WordPress details.
		 *
		 * @since 1.0.0
		 * @access public
		 * @static
		 *
		 * @return array An array containing WordPress details.
		 */
		public static function get_wordpress_details() {
			return array(
				'version'             => get_bloginfo( 'version' ),
				'environment-type'    => ( 'undefined' === Util::format_bool_constant( 'WP_ENVIRONMENT_TYPE' ) ) ? __( 'Production', 'performance-monitor' ) : Util::format_bool_constant( 'WP_ENVIRONMENT_TYPE' ),
				'development-mode'    => ( 'empty string' === Util::format_bool_constant( 'WP_DEVELOPMENT_MODE' ) ) ? __( 'No development mode', 'performance-monitor' ) : Util::format_bool_constant( 'WP_DEVELOPMENT_MODE' ),
				'permalink-structure' => get_option( 'permalink_structure' ),
				'using-HTTPS'         => is_ssl() ? __( 'Yes', 'performance-monitor' ) : __( 'No', 'performance-monitor' ),
				'multisite'           => is_multisite() ? __( 'Yes', 'performance-monitor' ) : __( 'No', 'performance-monitor' ),
			);
		}

		/**
		 * Get the WordPress constants details.
		 *
		 * @since 1.0.0
		 * @access public
		 * @static
		 *
		 * @return array An array containing WordPress constants details.
		 */
		public static function get_wp_constants_details() {
			return array(
				'WP_MEMORY_LIMIT'     => Util::format_bool_constant( 'WP_MEMORY_LIMIT' ),
				'WP_MAX_MEMORY_LIMIT' => Util::format_bool_constant( 'WP_MAX_MEMORY_LIMIT' ),
				'WP_DEBUG'            => Util::format_bool_constant( 'WP_DEBUG' ),
				'WP_DEBUG_DISPLAY'    => Util::format_bool_constant( 'WP_DEBUG_DISPLAY' ),
				'WP_DEBUG_LOG'        => Util::format_bool_constant( 'WP_DEBUG_LOG' ),
				'SCRIPT_DEBUG'        => Util::format_bool_constant( 'SCRIPT_DEBUG' ),
				'WP_CACHE'            => Util::format_bool_constant( 'WP_CACHE' ),
				'CONCATENATE_SCRIPTS' => Util::format_bool_constant( 'CONCATENATE_SCRIPTS' ),
				'COMPRESS_SCRIPTS'    => Util::format_bool_constant( 'COMPRESS_SCRIPTS' ),
				'COMPRESS_CSS'        => Util::format_bool_constant( 'COMPRESS_CSS' ),
				'WP_ENVIRONMENT_TYPE' => Util::format_bool_constant( 'WP_ENVIRONMENT_TYPE' ),
				'WP_DEVELOPMENT_MODE' => Util::format_bool_constant( 'WP_DEVELOPMENT_MODE' ),
			);
		}

		/**
		 * Get server details.
		 *
		 * Retrieves detailed information about the server.
		 *
		 * @since  1.0.0
		 * @access public
		 *
		 * @return array Server details array.
		 */
		public static function get_server_details() {
			return array(
				'server-software'  => sanitize_text_field( $_SERVER['SERVER_SOFTWARE'] ),
				'server-ip'        => sanitize_text_field( $_SERVER['SERVER_ADDR'] ),
				'host-name'        => sanitize_text_field( php_uname( 'n' ) ),
				'OS'               => PHP_OS . ' ' . sanitize_text_field( php_uname( 'r' ) ),
				'architecture'     => sanitize_text_field( php_uname( 'm' ) ),
				'operating-system' => sanitize_text_field( php_uname( 's' ) ),
				'server-name'      => sanitize_text_field( $_SERVER['SERVER_NAME'] ),
			);
		}

		/**
		 * Measure server response time.
		 *
		 * Measure server response time and return in a human-readable format.
		 *
		 * @since  1.0.0
		 * @access private
		 *
		 * @return string Server response time in a human-readable format.
		 */
		private static function measure_server_response_time() {
			$start_time = filter_var( $_SERVER['REQUEST_TIME_FLOAT'], FILTER_VALIDATE_FLOAT );
			$end_time   = microtime( true );

			return Util::convert_time_to_human_readable( $end_time - $start_time );
		}
	}
}
