<?php
/**
 * General class for handling general functionalities.
 *
 * @link       https://qrolic.com
 * @since      1.0.0
 *
 * @package    PerformanceMonitor
 * @subpackage includes
 */

namespace PerformanceMonitor\Inc;

use PerformanceMonitor\Admin\Curl;
use PerformanceMonitor\Admin\Pagespeed;
use PerformanceMonitor\Admin\System_Info;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'PerformanceMonitor\Inc\General' ) ) {
	/**
	 * General class
	 *
	 * This class provides methods for handling various general functionalities related to the Performance Monitor plugin.
	 *
	 * @since   1.0.0
	 */
	class General {
		/**
		 * Retrieve post IDs by meta key.
		 *
		 * @param string $meta_key The meta key to search for.
		 * @param array $custom_query Custom query arguments.
		 * @param int $posts_per_page Number of posts to retrieve.
		 * @return array List of post IDs.
		 */
		public static function get_post_ids_by_meta_key( $meta_key, $custom_query = array(), $posts_per_page = 1 ) {
			$query_args = array(
				'post_type'      => QTPM_PLUGIN_POST_TYPE,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key' => $meta_key,
					),
				),
				'posts_per_page' => $posts_per_page,
			);

			// Merge custom query arguments
			$query_args = array_merge( $query_args, $custom_query );

			// Retrieve posts based on the meta key
			return get_posts( $query_args );
		}

		/**
		 * Get cached data by meta key.
		 *
		 * @param string $meta_key The meta key to retrieve cached data for.
		 * @return mixed|null The cached data, or null if not found.
		 */
		public static function get_cached_data( $meta_key ) {
			$cached_data_posts = self::get_post_ids_by_meta_key( $meta_key );

			if ( empty( $cached_data_posts ) ) {
				return null;
			}

			$cached_data = get_post_meta( $cached_data_posts[0], $meta_key, true );

			return ! empty( $cached_data ) ? $cached_data : Pagespeed::qtpm_get_pagespeed_api_data();
		}

		/**
		 * Store post data with meta key.
		 *
		 * @param string $meta_key The meta key to store data with.
		 * @param mixed $post_data The data to be stored.
		 * @param string $post_title The title of the post to be stored.
		 * @param array $custom_query Custom query arguments for post retrieval.
		 * @return int|false The post ID on success, false on failure.
		 **/
		public static function store_post_data( $meta_key, $post_data, $post_title, $custom_query = array() ) {
			$existing_posts = self::get_post_ids_by_meta_key( $meta_key, $custom_query );
			$post_id        = ( ! empty( $existing_posts ) ) ? $existing_posts[0] : wp_insert_post(
				array(
					'post_type'   => QTPM_PLUGIN_POST_TYPE,
					'post_status' => 'publish',
					'post_title'  => sanitize_text_field( $post_title ),
				),
			);

			if ( $post_id ) {
				update_post_meta( $post_id, $meta_key, $post_data );
			}

			return $post_id;
		}

		/**
		 * Fetch chart data based on meta key and duration.
		 *
		 * @param string $meta_key The meta key to retrieve data for.
		 * @param string $duration The duration for which data is to be retrieved.
		 * @return array List of posts containing chart data.
		 */
		public static function fetch_chart_data( $meta_key, $params = array() ) {
			$duration = htmlspecialchars( $params['duration'] );

			if ( 'custom' === $duration ) {
				$start_date    = htmlspecialchars( $params['startDate'] );
				$end_date      = htmlspecialchars( $params['endDate'] );
				$date_duration = array(
					'start_date' => $start_date,
					'end_date'   => $end_date,
				);
				$date_query    = Util::generate_date_query( $date_duration['start_date'], $date_duration['end_date'] );
			} else {
				$date_query = Util::data_duration( $duration );
			}

			$args = array(
				'post_type'      => QTPM_PLUGIN_POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'post_date',
				'order'          => 'ASC',
				'fields'         => 'ids',
				'date_query'     => $date_query,
				'meta_query'     => array(
					array(
						'key' => $meta_key,
					),
				),
			);

			return get_posts( $args );
		}

		public static function get_mysql_var( $mysql_var ) {
			global $wpdb;

			$result = $wpdb->get_row(
				$wpdb->prepare( 'SHOW VARIABLES LIKE %s', $mysql_var ),
				ARRAY_A
			);

			if ( ! empty( $result ) && array_key_exists( 'Value', $result ) ) {
				return $result['Value'];
			}

			return null;
		}
		/**
		 * Get list of cache plugins from WordPress.org API.
		 *
		 * This method retrieves information about cache plugins from the WordPress.org Plugins API.
		 * It first attempts to retrieve cached data, and if not available, it fetches the data from the API,
		 * processes it, and caches it for future use.
		 *
		 * @since 1.0.0
		 * @access public
		 *
		 * @return array List of cache plugins with their slugs as keys and names as values.
		 */
		public static function get_plugins_from_api( $search_string ) {
			// Attempt to retrieve cached data
			$cache_plugins = wp_cache_get( 'qtpm_cached_plugins_' . $search_string, 'qtpm_plugin_cache_group' );

			// If cached data does not exist, fetch it from the API
			if ( false === $cache_plugins ) {
				// Initialize an empty array to store cache plugins
				$cache_plugins = array();

				// WordPress.org Plugins API endpoint URL
				$api_url = 'https://api.wordpress.org/plugins/info/1.2/';

				// Fetch data from WordPress.org API
				$response = wp_remote_get(
					add_query_arg(
						array(
							'action'  => 'query_plugins',
							'request' => array(
								'search'   => $search_string,
								'fields'   => array(
									'slug'              => true,
									'name'              => true,
									'description'       => false,
									'requires'          => false,
									'tags'              => false,
									'compatibility'     => false,
									'rating'            => false,
									'ratings'           => false,
									'homepage'          => false,
									'active_installs'   => false,
									'downloaded'        => false,
									'last_updated'      => false,
									'added'             => false,
									'short_description' => false,
									'download_link'     => false,
									'tested'            => false,
									'requires_php'      => false,
									'donate_link'       => false,
									'icons'             => false,
								),
								'per_page' => 100,
							),
						),
						$api_url
					)
				);

				// Check if API request was successful
				if ( ! is_wp_error( $response ) && 200 === $response['response']['code'] ) {
					$body = wp_remote_retrieve_body( $response );
					$data = json_decode( $body, true );

					// Extract plugin names and slugs
					foreach ( $data['plugins'] as $plugin ) {
						$cache_plugins[ $plugin['slug'] ] = $plugin['name'];
					}

					// Cache the data using wp_cache_set()
					wp_cache_set( 'qtpm_cached_plugins_' . $search_string, $cache_plugins, 'qtpm_plugin_cache_group', DAY_IN_SECONDS );
				}
			}

			return $cache_plugins;
		}

		/**
		 * Get date query for current day.
		 *
		 * @since 1.0.0
		 * @access public
		 *
		 * @return array Date query for current day.
		 */
		public static function get_date_query() {
			return array(
				'date_query' => array(
					array(
						'after'     => 'today',
						'before'    => 'tomorrow',
						'inclusive' => true,
					),
				),
			);
		}

		/**
		 * Store PageSpeed API data.
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */
		public static function store_pagespeed_api_data() {
			$pagespeed_api_data = Pagespeed::qtpm_get_pagespeed_api_data();

			self::store_post_data( 'pagespeed_api_data', $pagespeed_api_data, 'PageSpeed API Data ' . gmdate( 'F j, Y' ), self::get_date_query() );
		}

		/**
		 * Store installed plugins data.
		 *
		 * @since  1.0.0
		 * @access public
		 * @return void
		 */
		public static function store_installed_plugins_data() {
			$plugins_data = System_Info::get_installed_plugins_data();

			self::store_post_data( 'plugins_data', $plugins_data, 'Installed Plugins Data ' . gmdate( 'F j, Y' ), self::get_date_query() );
		}

		/**
		 * Store cURL data.
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */
		public static function store_curl_data() {
			$data      = array(
				'url'   => home_url() . '/',
				'title' => get_bloginfo( 'name' ),
			);
			$curl_data = Curl::get_analysed_page_data( $data );
			self::store_post_data( 'curl_data', $curl_data, 'QTPM curl Data ' . gmdate( 'F j, Y' ), self::get_date_query() );
		}

		/**
		 * Extracts audit data.
		 *
		 * @param array $audit The audit data.
		 * @return array The extracted audit data.
		 */
		public static function extract_audit( $audit ) {
			return array(
				'score'        => $audit['score'] ?? 0,
				'displayValue' => isset( $audit['displayValue'] ) ? (float) $audit['displayValue'] : 0,
			);
		}

		/**
		 * Extracts Lighthouse data.
		 *
		 * @param array $data The Lighthouse data.
		 * @return array The extracted Lighthouse data.
		 */
		public static function extract_lighthouse_data( $data ) {
			return array(
				'lighthouseResult' => array(
					'audits'     => array(
						'first-contentful-paint'   => self::extract_audit( $data['lighthouseResult']['audits']['first-contentful-paint'] ),
						'largest-contentful-paint' => self::extract_audit( $data['lighthouseResult']['audits']['largest-contentful-paint'] ),
						'cumulative-layout-shift'  => self::extract_audit( $data['lighthouseResult']['audits']['cumulative-layout-shift'] ),
						'speed-index'              => self::extract_audit( $data['lighthouseResult']['audits']['speed-index'] ),
						'total-blocking-time'      => self::extract_audit( $data['lighthouseResult']['audits']['total-blocking-time'] ),
					),
					'categories' => array(
						'performance'    => array( 'score' => $data['lighthouseResult']['categories']['performance']['score'] ?? 0 ),
						'accessibility'  => array( 'score' => $data['lighthouseResult']['categories']['accessibility']['score'] ?? 0 ),
						'best-practices' => array( 'score' => $data['lighthouseResult']['categories']['best-practices']['score'] ?? 0 ),
						'seo'            => array( 'score' => $data['lighthouseResult']['categories']['seo']['score'] ?? 0 ),
					),
				),
				'load_time'        => 0,
			);
		}

		/**
		 * Adds curl data to the data entry.
		 *
		 * @param array $data_entry The data entry to update.
		 * @param array $curl_data  The curl data to add.
		 */
		public static function add_curl_data( &$data_entry, $curl_data ) {
			if ( $curl_data ) {
				$load_time   = $curl_data['load_time'] ?? 0;
				$css         = (int) ( $curl_data['css_count'] ?? 0 );
				$js          = (int) ( $curl_data['js_count'] ?? 0 );
				$media       = (int) ( $curl_data['media_count'] ?? 0 );
				$total_asset = $js + $css + $media;

				$data_entry['desktop_data']['load_time'] = $load_time;
				$data_entry['mobile_data']['load_time']  = $load_time;

				$data_entry['curl_data'] = array(
					'css'              => $css,
					'js'               => $js,
					'media'            => $media,
					'total_asset'      => $total_asset,
					'css_total_size'   => (float) ( $curl_data['css_total_size'] ?? 0 ),
					'js_total_size'    => (float) ( $curl_data['js_total_size'] ?? 0 ),
					'media_total_size' => (float) ( $curl_data['media_total_size'] ?? 0 ),
					'total_size'       => (float) ( $curl_data['total_size'] ?? 0 ),
				);
			}
		}

		/**
		 * Adds plugin data to the data entry.
		 *
		 * @param array $data_entry   The data entry to update.
		 * @param array $plugins_data The plugin data to add.
		 */
		public static function add_plugin_data( &$data_entry, $plugins_data ) {
			$active_plugin_count = 0;
			if ( ! empty( $plugins_data ) ) {
				foreach ( $plugins_data as $plugin ) {
					if ( 'yes' === $plugin['IsActive'] ) {
						++$active_plugin_count;
					}
				}
			}
			$data_entry['curl_data']['active_plugins'] = $active_plugin_count;
		}

		/**
		 * Sums data entries into a cumulative sum data array.
		 *
		 * @param array $sum_data The cumulative sum data.
		 * @param array $entry    The individual data entry.
		 */
		public static function sum_data( &$sum_data, $entry ) {
			if ( is_numeric( $entry['load_time'] ) ) {
				$sum_data['load_time'] += (float) $entry['load_time'];
			}

			foreach ( array( 'first-contentful-paint', 'largest-contentful-paint', 'cumulative-layout-shift', 'speed-index', 'total-blocking-time' ) as $audit ) {
				foreach ( $entry['lighthouseResult']['audits'][ $audit ] as $metric => $value ) {
					$sum_data['lighthouseResult']['audits'][ $audit ][ $metric ] += (float) Util::extractnumeric( $value );
				}
			}

			foreach ( array( 'performance', 'accessibility', 'best-practices', 'seo' ) as $category ) {
				foreach ( $entry['lighthouseResult']['categories'][ $category ] as $metric => $value ) {
					$sum_data['lighthouseResult']['categories'][ $category ][ $metric ] += (float) Util::extractnumeric( $value );
				}
			}
		}

		/**
		 * Sums curl data entries into a cumulative sum curl data array.
		 *
		 * @param array $sum_curl_data  The cumulative sum curl data.
		 * @param array $entry_curl_data The individual curl data entry.
		 */
		public static function sum_curl_data( &$sum_curl_data, $entry_curl_data ) {
			foreach ( $entry_curl_data as $metric => $value ) {
				if ( is_numeric( $value ) ) {
					$sum_curl_data[ $metric ] += (float) $value;
				}
			}
		}

		/**
		 * Calculates averages from sum data.
		 *
		 * @param array $sum_data             The sum data.
		 * @param int   $num_pagespeed_posts  The number of PageSpeed posts.
		 * @param int   $num_curl_posts       The number of curl posts.
		 * @return array The average data.
		 */
		public static function calculate_averages( $sum_data, $num_pagespeed_posts, $num_curl_posts ) {
			$avg_data = array();
			if ( 0 < $num_pagespeed_posts ) {
				$avg_data['desktop_data'] = self::calculate_average( $sum_data['desktop_data'], $num_pagespeed_posts );
				$avg_data['mobile_data']  = self::calculate_average( $sum_data['mobile_data'], $num_pagespeed_posts );
			}

			if ( 0 < $num_curl_posts ) {
				foreach ( $sum_data['curl_data'] as $metric => $value ) {
					$avg_data['curl_data'][ $metric ] = $value / $num_curl_posts;
				}
			}

			return $avg_data;
		}

		/**
		 * Calculates the average for a set of sum data.
		 *
		 * @param array $sum_data The sum data.
		 * @param int   $count    The count of data entries.
		 * @return array The average data.
		 */
		private static function calculate_average( $sum_data, $count ) {
			$avg_data              = array();
			$avg_data['load_time'] = $sum_data['load_time'] / $count;

			foreach ( $sum_data['lighthouseResult']['audits'] as $audit => $metrics ) {
				foreach ( $metrics as $metric => $value ) {
					$avg_data['lighthouseResult']['audits'][ $audit ][ $metric ] = $value / $count;
				}
			}

			foreach ( $sum_data['lighthouseResult']['categories'] as $category => $metrics ) {
				foreach ( $metrics as $metric => $value ) {
					$avg_data['lighthouseResult']['categories'][ $category ][ $metric ] = $value / $count;
				}
			}

			return $avg_data;
		}

		/**
		 * Saves the average data.
		 *
		 * @param array $avg_data The average data to save.
		 */
		public static function save_avg_data( $avg_data ) {
			$existing_post = get_posts(
				array(
					'post_type'      => QTPM_PLUGIN_POST_TYPE,
					'fields'         => 'ids',
					'meta_query'     => array(
						'key'     => 'month_of_date',
						'value'   => array(
							gmdate( 'Y-m-d 00:00:00', strtotime( 'first day of last month' ) ),
							gmdate( 'Y-m-d 23:59:59', strtotime( 'last day of last month' ) ),
						),
						'compare' => 'BETWEEN',
						'type'    => 'DATE',
					),
					'posts_per_page' => 1,
				)
			);

			if ( ! empty( $existing_post ) ) {
				$post_id = $existing_post[0];

				update_post_meta( $post_id, 'qtpm_chart_month_cron_data', $avg_data );
			} else {
				wp_insert_post(
					array(
						'post_title' => 'Monthly average data',
						'post_type'  => QTPM_PLUGIN_POST_TYPE,
						'meta_input' => array(
							'qtpm_chart_month_cron_data' => $avg_data,
							'month_of_date'              => gmdate( 'Y-m-d', strtotime( 'first day of last month' ) ),
						),
					)
				);
			}
		}

		public static function get_category_score( $page_data, $device_type, $category, $multiply = 1 ) {
			return isset( $page_data[ $device_type ]['lighthouseResult']['categories'][ $category ]['score'] )
				? round( $page_data[ $device_type ]['lighthouseResult']['categories'][ $category ]['score'] * $multiply, 2 )
				: null;
		}

		public static function get_audit_score( $page_data, $device_type, $category, $multiply = 1 ) {
			return isset( $page_data[ $device_type ]['lighthouseResult']['audits'][ $category ]['score'] )
				? round( $page_data[ $device_type ]['lighthouseResult']['audits'][ $category ]['score'] * $multiply, 2 )
				: null;
		}
	}
}
