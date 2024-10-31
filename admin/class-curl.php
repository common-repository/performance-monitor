<?php
/**
 * Curl class file.
 *
 * Provides methods to make cURL requests.
 *
 * @link       https://qrolic.com
 * @since      1.0.0
 *
 * @package    PerformanceMonitor
 * @subpackage Admin

 */

namespace PerformanceMonitor\Admin;

use PerformanceMonitor\Inc\Util;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'PerformanceMonitor\Admin\Curl' ) ) {
	/**
	 * Class Curl
	 *
	 * Provides methods to make cURL requests to external APIs or URLs.
	 *
	 * @since   1.0.0
	 */
	class Curl {
		/**
		 * Get analyzed page data.
		 *
		 * @since  1.0.0
		 * @access public
		 * @param  array $data Array containing URL and title of the page.
		 * @return array Analyzed page data.
		 */
		public static function get_analysed_page_data( $data ) {
			$url   = $data['url'];
			$title = $data['title'];

			$start_time = microtime( true );

			$response = wp_remote_get(
				$url,
				array(
					'timeout'   => 100,
					'sslverify' => false,
				),
			);

			$end_time  = microtime( true );
			$load_time = number_format( $end_time - $start_time, 2 );

			if ( is_wp_error( $response ) ) {
				return array(
					'error' => $response->get_error_message(),
				);
			}

			$result = wp_remote_retrieve_body( $response );

			// Extract CSS, JS, and media files
			preg_match_all( '/<link\s+.*?rel=["\']stylesheet["\'].*?href=["\']([^"\']+)["\'].*?(?:\s+id=["\']([^"\']+)["\'])?/i', $result, $css_matches );
			preg_match_all( '/<script\s+.*?src=["\']([^"\']+)["\'].*?\s+id=["\']([^"\']+)["\'].*?>/i', $result, $script_matches ); // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript

			if ( preg_match_all( '/<img\s+.*?data-src=["\']([^"\']+)["\'].*?>/i', $result, $img_matches ) ) {
				$media_matches = $img_matches;
				foreach ( $img_matches as $matches ) {
					foreach ( $matches as $index => $match ) {
						$media_matches['lazy_load'][ $index ] = true;
					}
				}
			} else {
				// No lazy-loaded images, fall back to regular images
				preg_match_all( '/<img\s+.*?src=["\']([^"\']+)["\'].*?>/i', $result, $img_matches );
				$media_matches = $img_matches;
				foreach ( $img_matches as $matches ) {
					foreach ( $matches as $index => $match ) {
						$media_matches['lazy_load'][ $index ] = false;
					}
				}
			}

			$css_sizes   = Util::process_resource_matches( $css_matches, $url );
			$js_sizes    = Util::process_resource_matches( $script_matches, $url );
			$media_sizes = Util::process_resource_matches( $media_matches, $url );

			$total_css_sizes   = Util::calculate_total_size( $css_sizes );
			$total_js_sizes    = Util::calculate_total_size( $js_sizes );
			$total_media_sizes = Util::calculate_total_size( $media_sizes );

			$total_size = $total_css_sizes + $total_js_sizes + $total_media_sizes;

			$page_info = array(
				'page_url'         => esc_url( urldecode( $url ) ),
				'page_title'       => $title,
				'load_time'        => $load_time,
				'css_count'        => count( $css_matches[1] ),
				'js_count'         => count( $script_matches[1] ),
				'media_count'      => count( $media_matches[1] ),
				'css_total_size'   => Util::size_convertion( $total_css_sizes ),
				'js_total_size'    => Util::size_convertion( $total_js_sizes ),
				'media_total_size' => Util::size_convertion( $total_media_sizes ),
				'total_size'       => Util::size_convertion( $total_size ),
				'css_sizes'        => $css_sizes,
				'js_sizes'         => $js_sizes,
				'media_sizes'      => $media_sizes,
			);

			if ( ( home_url() . '/' ) === $data['url'] ) {
				$query_args = array(
					'post_type'   => QTPM_PLUGIN_POST_TYPE,
					'post_status' => 'publish',
					'meta_query'  => array(
						array(
							'key' => 'cached_curl_data',
						),
					),
				);

				$existing_posts = get_posts( $query_args );

				if ( ! empty( $existing_posts ) ) {
					$post_id = $existing_posts[0]->ID;
				} else {
					$post_data = array(
						'post_title'  => __( 'QTPM cached curl Data', 'performance-monitor' ) . gmdate( 'Y-m-d H:i:s' ),
						'post_status' => 'publish',
						'post_type'   => QTPM_PLUGIN_POST_TYPE,
					);

					$post_id = wp_insert_post( $post_data );
				}

				if ( $post_id ) {
					update_post_meta( $post_id, 'cached_curl_data', $page_info );
				}
			}

			return $page_info;
		}
	}
}
