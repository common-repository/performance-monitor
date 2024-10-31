<?php
/**
 * REST Callbacks for Performance Monitor plugin.
 *
 * @link       https://qrolic.com
 * @since      1.0.0
 *
 * @package    PerformanceMonitor
 * @subpackage includes
 */

namespace PerformanceMonitor\Inc;

use PerformanceMonitor\Admin\Curl;
use PerformanceMonitor\Admin\Dashboard;
use PerformanceMonitor\Admin\Pagespeed;
use PerformanceMonitor\Admin\System_Info;
use PerformanceMonitor\Inc\General;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'PerformanceMonitor\Inc\Rest' ) ) {
	/**
	 * Class Rest_Callback
	 *
	 * Handles REST API callbacks for Performance Monitor.
	 *
	 * @since 1.0.0
	 */
	class Rest_Callback {

		/**
		 * Sends a WP_REST_Response.
		 *
		 * @param mixed $data The response data.
		 * @param bool $success The success status.
		 * @param int $status_code The HTTP status code.
		 * @param string|null $message An optional message.
		 * @return WP_REST_Response
		 *
		 * @since 1.0.0
		 */
		private function send_response( $data, $success = true, $status_code = 200, $message = null ) {
			return new WP_REST_Response(
				array(
					'data'    => $data,
					'success' => $success,
					'message' => $message,
				),
				$status_code
			);
		}

		/**
		 * Callback function to update settings via REST API.
		 *
		 * Updates Performance Monitor settings based on the provided data.
		 *
		 * @param WP_REST_Request $request The REST request object.
		 * @return WP_REST_Response The REST response object.
		 *
		 * @since 1.0.0
		 */
		public function update_settings( WP_REST_Request $request ) {
			$parameters  = $request->get_json_params();
			$option_page = $parameters['option_page'];

			$cleaned_settings = array();
			foreach ( $parameters as $key => $value ) {
				if ( preg_match( '/' . $option_page . '\[(.*?)\]/', $key, $matches ) ) {
					$cleaned_settings[ $matches[1] ] = sanitize_text_field( $value );
				}
			}

			update_option( $option_page, $cleaned_settings );

			return $this->send_response( $cleaned_settings );
		}

		/**
		 * Fetch chart data.
		 *
		 * @param WP_REST_Request $request The REST request object.
		 * @return WP_REST_Response
		 *
		 * @since 1.0.0
		 */
		public function get_chart_data( WP_REST_Request $request ) {
			$params   = $request->get_params();
			$callback = array( Dashboard::class, $params['type'] . '_data' );
			if ( is_callable( $callback ) ) {
				return call_user_func( $callback, $params );
			} else {
				return $this->send_response( null, false, 400, __( 'Invalid type parameter', 'performance-monitor' ) );
			}
		}

		/**
		 * Get system information.
		 *
		 * @return WP_REST_Response
		 *
		 * @since 1.0.0
		 */
		public function get_system_info() {
			$data = array(
				'phpInfo'         => System_Info::get_php_details(),
				'databaseInfo'    => System_Info::get_database_details(),
				'wordPressInfo'   => System_Info::get_wordpress_details(),
				'serverInfo'      => System_Info::get_server_details(),
				'cacheInfo'       => System_Info::get_system_info_detail(),
				'wpConstantsInfo' => System_Info::get_wp_constants_details(),
				'pluginsInfo'     => System_Info::get_cached_installed_plugins_data(),
				'cronInfo'        => System_Info::get_schedule_cron_detail(),
			);

			return $this->send_response( $data );
		}

		/**
		 * Get the latest information based on type.
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 * @return WP_REST_Response
		 *
		 * @since 1.0.0
		 */
		public function get_latest_info( WP_REST_Request $request ) {
			$params       = $request->get_params();
			$function_map = array(
				'php_info'             => 'get_php_details',
				'database_info'        => 'get_database_details',
				'wordPress_info'       => 'get_wordpress_details',
				'server_info'          => 'get_server_details',
				'cache_info'           => 'get_cache_info',
				'plugin_info'          => 'get_installed_plugins_data',
				'schedule_cron_detail' => 'get_schedule_cron_detail',
			);

			$function = $function_map[ $params['type'] ] ?? '';

			if ( ! $function || ! method_exists( System_Info::class, $function ) ) {
				return $this->send_response( null, false, 400, __( 'Invalid type parameter', 'performance-monitor' ) );
			}

			$data = call_user_func( array( System_Info::class, $function ) );

			return $this->send_response( $data );
		}

		/**
		 * Get curl data.
		 *
		 * @param WP_REST_Request $request The REST request object.
		 * @return WP_REST_Response
		 *
		 * @since 1.0.0
		 */
		public function get_curl_data( WP_REST_Request $request ) {
			$params = $request->get_params();
			$result = array();
			if ( isset( $params['url'] ) && ! empty( $params['url'] ) ) {
				$data = array(
					'url'   => esc_url( $params['url'] ),
					'title' => sanitize_text_field( $params['title'] ),
				);
			} else {
				$data = array(
					'url'   => home_url() . '/',
					'title' => get_bloginfo( 'name' ),
				);
			}

			if ( ( home_url() . '/' ) === $data['url'] ) {

				$cached_curl_data = General::get_cached_data( 'cached_curl_data' );
				if ( $cached_curl_data ) {
					$result = $cached_curl_data;
				} else {
					$result = Curl::get_analysed_page_data( $data );
				}
			} else {
				$result = Curl::get_analysed_page_data( $data );
			}

			return $this->send_response( $result );
		}

		/**
		 * Get the latest curl data.
		 *
		 * @return WP_REST_Response
		 *
		 * @since 1.0.0
		 */
		public function get_latest_curl_data() {
			$data = array(
				'url'   => home_url() . '/',
				'title' => get_bloginfo( 'name' ),
			);

			$result = Curl::get_analysed_page_data( $data );

			return $this->send_response( $result );
		}

		/**
		 * Get all pages list.
		 *
		 * @return WP_REST_Response
		 *
		 * @since 1.0.0
		 */
		public function get_all_pages_list() {
			$pages = get_posts(
				array(
					'post_type'      => 'page',
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'fields'         => 'ids',
				)
			);

			$urls   = array( home_url() . '/' );
			$titles = array( get_bloginfo( 'name' ) );

			foreach ( $pages as $page_id ) {
				$urls[]   = get_permalink( $page_id );
				$titles[] = get_the_title( $page_id );
			}

			$data = array_map(
				function ( $url, $title ) {
					return array(
						'url'   => $url,
						'title' => $title,
					);
				},
				$urls,
				$titles
			);

			return $this->send_response( $data );
		}

		/**
		 * Get pagespeed API data.
		 *
		 * @param WP_REST_Request $request The REST request object.
		 * @return WP_REST_Response
		 *
		 * @since 1.0.0
		 */
		public function get_pagespeed_api_data( WP_REST_Request $request ) {
			$params = $request->get_params();
			$data   = isset( $params['cached'] ) && $params['cached']
				? General::get_cached_data( 'cached_pagespeed_api_data' )
				: Pagespeed::qtpm_get_pagespeed_api_data();

			return $this->send_response( $data );
		}
	}
}
