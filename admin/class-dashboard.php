<?php

/**
 * Dashboard functionality for Performance Monitor plugin.
 *
 * This file contains the Dashboard class which handles the functionality for
 * generating chart data for various performance metrics.
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

if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'PerformanceMonitor\Admin\Dashboard' ) ) {
	/**
	 * Class Dashboard
	 *
	 * Provides methods to display performance metrics, charts, and summary information.
	 *
	 * @since      1.0.0
	 */
	class Dashboard {
		/**
		 * Fetches and processes the Lighthouse score chart data.
		 *
		 * @param array $params Parameters for fetching the chart data.
		 *
		 * @return \WP_REST_Response The REST response object containing the chart data.
		 * @since 1.0.0
		 */
		public static function lighthouse_score_chart_data( $params ) {
			$duration = sanitize_text_field( $params['duration'] );
			$meta_key = 'pagespeed_api_data';

			if ( 'year' === $duration ) {
				$meta_key = 'qtpm_chart_month_cron_data';
			}
			$chart_data = array();
			$posts      = General::fetch_chart_data( $meta_key, $params );

			$x_axis_title = array();
			foreach ( $posts as $post_id ) {
				setup_postdata( $post_id );
				$page_data = get_post_meta( $post_id, $meta_key, true );
				$post_date = get_post_field( 'post_date', $post_id );

				$existing_dates = array_column( $chart_data, 'date' );
				if ( ! in_array( $post_date, $existing_dates, true ) ) {
					$label = Util::get_label_title( $post_date, $duration, $page_data['curl_data']['month_of_date'] ?? null );
					Util::set_x_axis_title( $x_axis_title, $duration, $post_date );
					$chart_data[] = array(
						'date'         => sanitize_text_field( $post_date ),
						'label'        => esc_html( $label ),
						'desktop_data' => array(
							'performance'    => General::get_category_score( $page_data, 'desktop_data', 'performance', 100 ),
							'accessibility'  => General::get_category_score( $page_data, 'desktop_data', 'accessibility', 100 ),
							'best_practices' => General::get_category_score( $page_data, 'desktop_data', 'best-practices', 100 ),
							'seo'            => General::get_category_score( $page_data, 'desktop_data', 'seo', 100 ),
						),
						'mobile_data'  => array(
							'performance'    => General::get_category_score( $page_data, 'mobile_data', 'performance', 100 ),
							'accessibility'  => General::get_category_score( $page_data, 'mobile_data', 'accessibility', 100 ),
							'best_practices' => General::get_category_score( $page_data, 'mobile_data', 'best-practices', 100 ),
							'seo'            => General::get_category_score( $page_data, 'mobile_data', 'seo', 100 ),
						),
					);
				}
			}

			wp_reset_postdata();

			return Util::send_chart_response( $chart_data, $x_axis_title );
		}

		/**
		 * Fetches and processes the performance score chart data.
		 *
		 * @param array $params Parameters for fetching the chart data.
		 *
		 * @return \WP_REST_Response The REST response object containing the chart data.
		 * @since 1.0.0
		 */
		public static function performance_score_chart_data( $params ) {
			$duration = sanitize_text_field( $params['duration'] );
			$meta_key = 'pagespeed_api_data';

			if ( 'year' === $duration ) {
				$meta_key = 'qtpm_chart_month_cron_data';
			}
			$x_axis_title = array();
			$chart_data   = array();
			$posts        = General::fetch_chart_data( $meta_key, $params );

			foreach ( $posts as $post_id ) {
				setup_postdata( $post_id );
				$page_data = get_post_meta( $post_id, $meta_key, true );
				$post_date = get_post_field( 'post_date', $post_id );

				$existing_dates = array_column( $chart_data, 'date' );
				if ( ! in_array( $post_date, $existing_dates, true ) ) {
					$label = Util::get_label_title( $post_date, $duration, $page_data['curl_data']['month_of_date'] ?? null );
					Util::set_x_axis_title( $x_axis_title, $duration, $post_date );
					$chart_data[] = array(
						'date'         => sanitize_text_field( $post_date ),
						'label'        => esc_html( $label ),
						'desktop_data' => array(
							'fcp' => General::get_audit_score( $page_data, 'desktop_data', 'first-contentful-paint', 10 ),
							'lcp' => General::get_audit_score( $page_data, 'desktop_data', 'largest-contentful-paint', 10 ),
							'cls' => General::get_audit_score( $page_data, 'desktop_data', 'cumulative-layout-shift', 30 ),
							'si'  => General::get_audit_score( $page_data, 'desktop_data', 'speed-index', 25 ),
							'tbt' => General::get_audit_score( $page_data, 'desktop_data', 'total-blocking-time', 25 ),
						),
						'mobile_data'  => array(
							'fcp' => General::get_audit_score( $page_data, 'mobile_data', 'first-contentful-paint', 10 ),
							'lcp' => General::get_audit_score( $page_data, 'mobile_data', 'largest-contentful-paint', 10 ),
							'cls' => General::get_audit_score( $page_data, 'mobile_data', 'cumulative-layout-shift', 30 ),
							'si'  => General::get_audit_score( $page_data, 'mobile_data', 'speed-index', 25 ),
							'tbt' => General::get_audit_score( $page_data, 'mobile_data', 'total-blocking-time', 25 ),
						),
					);
				}
			}

			wp_reset_postdata();

			return Util::send_chart_response( $chart_data, $x_axis_title );
		}

		/**
		 * Fetches and processes the load time insights chart data.
		 *
		 * @param array $params Parameters for fetching the chart data.
		 *
		 * @return \WP_REST_Response The REST response object containing the chart data.
		 * @since 1.0.0
		 */
		public static function loadtime_insights_chart_data( $params ) {
			$duration = sanitize_text_field( $params['duration'] );

			$meta_key_pagespeed = 'pagespeed_api_data';
			$meta_key_curl      = 'curl_data';
			$x_axis_title       = array();

			$chart_data = array();
			$home_url   = home_url() . '/';

			if ( 'year' === $duration ) {
				$meta_key_pagespeed = 'qtpm_chart_month_cron_data';
				$meta_key_curl      = 'qtpm_chart_month_cron_data';
			}

			$posts = General::fetch_chart_data( $meta_key_pagespeed, $params );

			foreach ( $posts as $post_id ) {
				setup_postdata( $post_id );
				$page_data      = get_post_meta( $post_id, $meta_key_pagespeed, true );
				$post_date      = get_post_field( 'post_date', $post_id );
				$existing_dates = array_column( $chart_data, 'date' );

				if ( ! in_array( $post_date, $existing_dates, true ) ) {
					if ( 'year' === $duration ) {
						$post_date = ( new \DateTime( $page_data['curl_data']['month_of_date'] ) )->format( 'M-Y' );
					}

					$label = Util::get_label_title( $post_date, $duration, $page_data['curl_data']['month_of_date'] ?? null );
					Util::set_x_axis_title( $x_axis_title, $duration, $post_date );
					$chart_data[] = array(
						'date'         => sanitize_text_field( $post_date ),
						'label'        => esc_html( $label ),
						'desktop_data' => array(
							'fcp' => isset( $page_data['desktop_data']['lighthouseResult']['audits']['first-contentful-paint']['displayValue'] ) ? round( (float) $page_data['desktop_data']['lighthouseResult']['audits']['first-contentful-paint']['displayValue'], 2 ) : 0,
							'lcp' => isset( $page_data['desktop_data']['lighthouseResult']['audits']['largest-contentful-paint']['displayValue'] ) ? round( (float) $page_data['desktop_data']['lighthouseResult']['audits']['largest-contentful-paint']['displayValue'], 2 ) : 0,
							'tbt' => isset( $page_data['desktop_data']['lighthouseResult']['audits']['total-blocking-time']['displayValue'] ) && is_numeric( $page_data['desktop_data']['lighthouseResult']['audits']['total-blocking-time']['displayValue'] ) ? round( (float) $page_data['desktop_data']['lighthouseResult']['audits']['total-blocking-time']['displayValue'] / 1000, 2 ) : 0,
							'cls' => isset( $page_data['desktop_data']['lighthouseResult']['audits']['cumulative-layout-shift']['displayValue'] ) ? round( (float) $page_data['desktop_data']['lighthouseResult']['audits']['cumulative-layout-shift']['displayValue'], 2 ) : 0,
							'si'  => isset( $page_data['desktop_data']['lighthouseResult']['audits']['speed-index']['displayValue'] ) ? round( (float) $page_data['desktop_data']['lighthouseResult']['audits']['speed-index']['displayValue'], 2 ) : 0,
						),
						'mobile_data'  => array(
							'fcp' => isset( $page_data['mobile_data']['lighthouseResult']['audits']['first-contentful-paint']['displayValue'] ) ? round( (float) $page_data['mobile_data']['lighthouseResult']['audits']['first-contentful-paint']['displayValue'], 2 ) : 0,
							'lcp' => isset( $page_data['mobile_data']['lighthouseResult']['audits']['largest-contentful-paint']['displayValue'] ) ? round( (float) $page_data['mobile_data']['lighthouseResult']['audits']['largest-contentful-paint']['displayValue'], 2 ) : 0,
							'tbt' => isset( $page_data['mobile_data']['lighthouseResult']['audits']['total-blocking-time']['displayValue'] ) && is_numeric( $page_data['mobile_data']['lighthouseResult']['audits']['total-blocking-time']['displayValue'] ) ? round( (float) $page_data['mobile_data']['lighthouseResult']['audits']['total-blocking-time']['displayValue'] / 1000, 2 ) : 0,
							'cls' => isset( $page_data['mobile_data']['lighthouseResult']['audits']['cumulative-layout-shift']['displayValue'] ) ? round( (float) $page_data['mobile_data']['lighthouseResult']['audits']['cumulative-layout-shift']['displayValue'], 2 ) : 0,
							'si'  => isset( $page_data['mobile_data']['lighthouseResult']['audits']['speed-index']['displayValue'] ) ? round( (float) $page_data['mobile_data']['lighthouseResult']['audits']['speed-index']['displayValue'], 2 ) : 0,
						),
					);
				}
			}

			$posts = General::fetch_chart_data( $meta_key_curl, $params );

			foreach ( $posts as $post_id ) {
				setup_postdata( $post_id );

				$curl_data = get_post_meta( $post_id, $meta_key_curl, true );
				$page_url  = $curl_data['page_url'] ?? '';

				if ( 'year' === $duration ) {
					$page_url = $curl_data['curl_data']['page_url'];
				}

				if ( $home_url === $page_url ) {
					$post_date = get_post_field( 'post_date', $post_id );
					$load_time = $curl_data['load_time'] ?? 0;

					if ( 'year' === $duration ) {
						$post_date = $curl_data['curl_data']['month_of_date'];
						$load_time = (float) ( $curl_data['desktop_data']['load_time'] ?? 0 );
					}
					foreach ( $chart_data as &$chart_item ) {
						$chart_item_date = gmdate( 'Y-m-d', strtotime( $chart_item['date'] ) );
						$post_date       = gmdate( 'Y-m-d', strtotime( $post_date ) );
						if ( $chart_item_date === $post_date ) {
							$chart_item['desktop_data']['load_time'] = (float) round( $load_time, 2 );
							$chart_item['mobile_data']['load_time']  = (float) round( $load_time, 2 );
							break;
						}
					}
				}
			}

			wp_reset_postdata();

			return Util::send_chart_response( $chart_data, $x_axis_title );
		}

		/**
		 * Fetches and processes the asset size chart data.
		 *
		 * @param array $params Parameters for fetching the chart data.
		 *
		 * @return \WP_REST_Response The REST response object containing the chart data.
		 * @since 1.0.0
		 */
		public static function asset_size_chart_data( $params ) {
			$duration     = sanitize_text_field( $params['duration'] );
			$asset_data   = array();
			$meta_key     = 'curl_data';
			$x_axis_title = array();

			if ( 'year' === $duration ) {
				$meta_key = 'qtpm_chart_month_cron_data';
			}
			$posts = General::fetch_chart_data( $meta_key, $params );

			foreach ( $posts as $post_id ) {
				$curl_data = get_post_meta( $post_id, $meta_key, true );
				$post_date = get_post_field( 'post_date', $post_id );

				if ( 'year' === $duration ) {
					$curl_data = $curl_data['curl_data'];
					$post_date = ( new \DateTime( $curl_data['month_of_date'] ) )->format( 'M-Y' );
				}

				$label = Util::get_label_title( $post_date, $duration, $curl_data['month_of_date'] ?? null );
				Util::set_x_axis_title( $x_axis_title, $duration, $post_date );
				$asset_data[] = array(
					'total_css_size'   => round( (float) $curl_data['css_total_size'] ?? 0, 2 ),
					'total_js_size'    => round( (float) $curl_data['js_total_size'] ?? 0, 2 ),
					'media_total_size' => round( (float) $curl_data['media_total_size'] ?? 0, 2 ),
					'total_size'       => round( (float) $curl_data['total_size'] ?? 0, 2 ),
					'date'             => $post_date,
					'label'            => $label,
				);
			}

			return Util::send_chart_response( $asset_data, $x_axis_title );
		}

		/**
		 * Fetches and processes the asset count chart data.
		 *
		 * @param array $params Parameters for fetching the chart data.
		 *
		 * @return \WP_REST_Response The REST response object containing the chart data.
		 * @since 1.0.0
		 */
		public static function asset_count_chart_data( $params ) {
			$duration         = sanitize_text_field( $params['duration'] );
			$asset_count_data = array();
			$meta_key         = 'curl_data';
			$x_axis_title     = array();

			if ( 'year' === $duration ) {
				$meta_key = 'qtpm_chart_month_cron_data';
			}
			$curl_posts = General::fetch_chart_data( $meta_key, $params );

			foreach ( $curl_posts as $curl_post_id ) {
				$active_plugin_count = 0;
				$curl_data           = get_post_meta( $curl_post_id, $meta_key, true );
				$post_date           = get_post_field( 'post_date', $curl_post_id );

				if ( 'year' === $duration ) {
					$post_date           = ( new \DateTime( $curl_data['curl_data']['month_of_date'] ) )->format( 'M-Y' );
					$css                 = (float) $curl_data['curl_data']['css'] ?? 0;
					$js                  = (float) $curl_data['curl_data']['js'] ?? 0;
					$media               = (float) $curl_data['curl_data']['media'] ?? 0;
					$total_asset         = $js + $css + $media;
					$active_plugin_count = (float) $curl_data['curl_data']['active_plugins'] ?? 0;
				} else {
					$css         = (int) $curl_data['css_count'] ?? 0;
					$js          = (int) $curl_data['js_count'] ?? 0;
					$media       = (int) $curl_data['media_count'] ?? 0;
					$total_asset = $js + $css + $media;

					$plugin_args = array(
						'post_type'      => QTPM_PLUGIN_POST_TYPE,
						'post_status'    => 'publish',
						'posts_per_page' => -1,
						'orderby'        => 'post_date',
						'fields'         => 'ids',
						'order'          => 'ASC',
						'date_query'     => array(
							array(
								'year'  => gmdate( 'Y', strtotime( $post_date ) ),
								'month' => gmdate( 'm', strtotime( $post_date ) ),
								'day'   => gmdate( 'd', strtotime( $post_date ) ),
							),
						),
						'meta_query'     => array(
							array(
								'key' => 'plugins_data',
							),
						),
					);

					$plugin_post = get_posts( $plugin_args );

					$plugins_data = get_post_meta( $plugin_post[0], 'plugins_data', true );

					if ( ! empty( $plugins_data ) ) {
						foreach ( $plugins_data as $data ) {
							if ( 'yes' === $data['IsActive'] ) {
								++$active_plugin_count;
							}
						}
					}
				}

				$label = Util::get_label_title( $post_date, $duration, $curl_data['curl_data']['month_of_date'] ?? null );
				Util::set_x_axis_title( $x_axis_title, $duration, $post_date );
				$asset_count_data[] = array(
					'date'          => $post_date,
					'label'         => $label,
					'css'           => round( $css, 2 ),
					'js'            => round( $js, 2 ),
					'total_asset'   => round( $total_asset, 2 ),
					'media'         => round( $media, 2 ),
					'active_plugin' => round( $active_plugin_count, 2 ),
				);
			}

			return Util::send_chart_response( $asset_count_data, $x_axis_title );
		}
	}
}
