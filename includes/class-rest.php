<?php
/**
 * REST API Handler for Performance Monitor Settings.
 *
 * @link       https://qrolic.com
 * @since      1.0.0
 *
 * @package    PerformanceMonitor
 * @subpackage includes
 */

namespace PerformanceMonitor\Inc;

if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'PerformanceMonitor\Inc\Rest' ) ) {
	/**
	 * Class Rest
	 *
	 * Handles REST API requests related to Performance Monitor settings.
	 *
	 * @since 1.0.0
	 */
	class Rest {

		/**
		 * Instance of Rest_Callback.
		 *
		 * @var Rest_Callback
		 */
		private $rest_callback;

		/**
		 * Namespace for the REST API routes.
		 */
		const NAMESPACE = 'performance-monitor/v1';

		/**
		 * Valid chart types.
		 */
		const VALID_CHART_TYPES = array(
			'lighthouse_score_chart',
			'performance_score_chart',
			'loadtime_insights_chart',
			'asset_size_chart',
			'asset_count_chart',
		);

		/**
		 * Valid duration types.
		 */
		const VALID_DURATION_TYPES = array( 'day', 'week', 'month', 'year', 'custom' );

		/**
		 * Valid info types.
		 */
		const VALID_INFO_TYPES = array(
			'database_info',
			'server_info',
			'php_info',
			'wordPress_info',
			'cache_info',
			'plugin_info',
			'schedule_cron_detail',
		);

		/**
		 * Constructor.
		 *
		 * Initializes the REST API handler by registering REST routes.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			add_action( 'rest_api_init', array( $this, 'register_routes' ) );
			$this->rest_callback = new Rest_Callback();
		}

		/**
		 * Register REST API routes.
		 *
		 * Registers the endpoint for updating Performance Monitor settings.
		 *
		 * @since 1.0.0
		 */
		public function register_routes() {
			$routes = $this->get_routes();

			foreach ( $routes as $route => $route_data ) {
				register_rest_route( self::NAMESPACE, $route, $route_data );
			}
		}

		/**
		 * Get routes definition.
		 *
		 * @since 1.0.0
		 * @return array Array of routes.
		 */
		private function get_routes() {
			return array(
				'settings'           => array(
					'methods'             => 'POST',
					'callback'            => array( $this->rest_callback, 'update_settings' ),
					'permission_callback' => fn() => current_user_can( 'manage_options' ),
				),
				'chart_data'         => array(
					'methods'             => 'GET',
					'callback'            => array( $this->rest_callback, 'get_chart_data' ),
					'args'                => array(
						'type'      => array(
							'required'          => true,
							'validate_callback' => array( $this, 'validate_chart_type' ),
						),
						'duration'  => array(
							'required'          => true,
							'validate_callback' => array( $this, 'validate_chart_duration' ),
						),
						'startDate' => array(
							'required'          => false,
							'validate_callback' => array( $this, 'validate_date' ),
						),
						'endDate'   => array(
							'required'          => false,
							'validate_callback' => array( $this, 'validate_date' ),
						),
					),
					'permission_callback' => '__return_true',
				),
				'system_info'        => array(
					'methods'             => 'GET',
					'callback'            => array( $this->rest_callback, 'get_system_info' ),
					'permission_callback' => '__return_true',
				),
				'latest_info'        => array(
					'methods'             => 'GET',
					'callback'            => array( $this->rest_callback, 'get_latest_info' ),
					'args'                => array(
						'type' => array(
							'required'          => true,
							'validate_callback' => array( $this, 'validate_latest_info_type' ),
						),
					),
					'permission_callback' => '__return_true',
				),
				'curl_data'          => array(
					'methods'             => 'GET',
					'callback'            => array( $this->rest_callback, 'get_curl_data' ),
					'args'                => array(
						'url'   => array(
							'required'          => false,
							'validate_callback' => fn( $param ) => is_string( $param ) && filter_var( $param, FILTER_VALIDATE_URL ),
						),
						'title' => array(
							'required'          => false,
							'validate_callback' => fn( $param ) => is_string( $param ),
						),
					),
					'permission_callback' => '__return_true',
				),
				'latest_curl_data'   => array(
					'methods'             => 'GET',
					'callback'            => array( $this->rest_callback, 'get_latest_curl_data' ),
					'permission_callback' => '__return_true',
				),
				'all_pages_list'     => array(
					'methods'             => 'GET',
					'callback'            => array( $this->rest_callback, 'get_all_pages_list' ),
					'permission_callback' => '__return_true',
				),
				'pagespeed_api_data' => array(
					'methods'             => 'GET',
					'callback'            => array( $this->rest_callback, 'get_pagespeed_api_data' ),
					'args'                => array(
						'cached' => array(
							'required'          => false,
							'validate_callback' => fn( $param ) => is_numeric( $param ),
						),
					),
					'permission_callback' => '__return_true',
				),
			);
		}

		/**
		 * Validate chart type.
		 *
		 * @param string $param Chart type.
		 * @return bool True if valid, false otherwise.
		 *
		 * @since 1.0.0
		 */
		public function validate_chart_type( $param ) {
			return in_array( $param, self::VALID_CHART_TYPES, true );
		}

		/**
		 * Validate chart duration.
		 *
		 * @param string $param Chart duration.
		 * @return bool True if valid, false otherwise.
		 *
		 * @since 1.0.0
		 */
		public function validate_chart_duration( $param ) {
			return in_array( $param, self::VALID_DURATION_TYPES, true );
		}

		/**
		 * Validate date.
		 *
		 * @param string $param Date.
		 * @return bool True if valid, false otherwise.
		 *
		 * @since 1.0.0
		 */
		public function validate_date( $param ) {
			return is_string( $param ) && preg_match( '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $param );
		}

		/**
		 * Validate latest info type.
		 *
		 * @param string $param Info type.
		 * @return bool True if valid, false otherwise.
		 *
		 * @since 1.0.0
		 */
		public function validate_latest_info_type( $param ) {
			return in_array( $param, self::VALID_INFO_TYPES, true );
		}
	}
}
