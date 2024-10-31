<?php
/**
 * Pagespeed class file.
 *
 * Provides methods to interact with the PageSpeed API and retrieve performance data.
 *
 * @link       https://qrolic.com
 * @since      1.0.0
 *
 * @package    PerformanceMonitor
 * @subpackage Admin
 */

namespace PerformanceMonitor\Admin;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'PerformanceMonitor\Admin\Pagespeed' ) ) {
	/**
	 * Class Pagespeed
	 *
	 * Provides methods to interact with the PageSpeed API and retrieve performance data.
	 * The data retrieved includes metrics such as page load time, performance scores, and optimization suggestions.
	 *
	 * @since 1.0.0
	 */
	class Pagespeed {
		/**
		 * Set up PageSpeed API query.
		 *
		 * @since  1.0.0
		 * @access public
		 * @return array PageSpeed API query.
		 */
		public static function set_up_pagespeed_api_query() {
			$url     = esc_url_raw( home_url() );
			$api     = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';
			$api_key = 'AIzaSyA2RoSbFxGsx55wQlWDH--ghbbwKX5D0YY';

			if ( false !== strpos( $url, 'localhost' ) ) {
				$url = 'https://developers.google.com';
			}

			$parameters = array(
				'url'      => rawurlencode( $url ),
				'key'      => $api_key,
				'category' => array(
					'ACCESSIBILITY',
					'BEST_PRACTICES',
					'PERFORMANCE',
					'PWA',
					'SEO',
				),
			);

			$query = $api . '?';

			foreach ( $parameters as $key => $value ) {
				if ( 'category' === $key ) {
					foreach ( $value as $v ) {
						$query .= "$key=$v&";
					}
					continue;
				}
				$query .= "$key=$value&";
			}

			$queries['mobile']  = $query . 'strategy=MOBILE';
			$queries['desktop'] = $query . 'strategy=DESKTOP';

			return $queries;
		}

		/**
		 * Get PageSpeed API data.
		 *
		 * @since 1.0.0
		 *
		 * @return array|false PageSpeed API data.
		 */
		public static function qtpm_get_pagespeed_api_data() {
			$url  = self::set_up_pagespeed_api_query();
			$args = array(
				'timeout' => 300,
			);

			$response['mobile']  = wp_remote_get( $url['mobile'], $args );
			$response['desktop'] = wp_remote_get( $url['desktop'], $args );

			if ( is_wp_error( $response['mobile'] ) || is_wp_error( $response['desktop'] ) ) {
				return false;
			}

			$raw_response['mobile']  = wp_remote_retrieve_body( $response['mobile'] );
			$raw_response['desktop'] = wp_remote_retrieve_body( $response['desktop'] );

			$mobile_response  = json_decode( $raw_response['mobile'], true );
			$desktop_response = json_decode( $raw_response['desktop'], true );

			$mobile_data     = self::prepare_pagespeed_api_response( $mobile_response );
			$desktop_data    = self::prepare_pagespeed_api_response( $desktop_response );
			$filter_response = array(
				'mobile_data'  => $mobile_data,
				'desktop_data' => $desktop_data,
			);

			$post_exists_args = array(
				'post_type'      => QTPM_PLUGIN_POST_TYPE,
				'meta_query'     => array(
					array(
						'key' => 'cached_pagespeed_api_data',
					),
				),
				'posts_per_page' => 1,
			);

			$existing_posts = get_posts( $post_exists_args );

			if ( ! empty( $existing_posts ) ) {
				$post_id = $existing_posts[0]->ID;
			} else {
				$post_id = wp_insert_post(
					array(
						'post_type'   => QTPM_PLUGIN_POST_TYPE,
						'post_status' => 'publish',
						'post_title'  => 'Cached PageSpeed API Data',
					),
				);
			}

			if ( $post_id ) {
				update_post_meta( $post_id, 'cached_pagespeed_api_data', $filter_response );
			}

			return $filter_response;
		}

		/**
		 * Prepares the PageSpeed API response.
		 *
		 * @since 1.0.0
		 *
		 * @param array $response The raw PageSpeed API response.
		 * @return array Prepared PageSpeed API data.
		 */
		private static function prepare_pagespeed_api_response( $response ) {
			$categories          = array();
			$selected_audit_refs = array( 'FCP', 'LCP', 'TBT', 'CLS', 'SI' );
			$selected_audits_id  = array();

			// storing details of accessibility, best-practices, performance, paw, and seo categories.
			foreach ( $response['lighthouseResult']['categories'] as $key => $category ) {
				$categories[ $key ]['id']                = $category['id'] ?? null;
				$categories[ $key ]['title']             = $category['title'] ?? null;
				$categories[ $key ]['score']             = $category['score'] ?? null;
				$categories[ $key ]['description']       = $category['description'] ?? null;
				$categories[ $key ]['manualDescription'] = $category['manualDescription'] ?? null;

				if ( 'performance' === $key ) {
					foreach ( $category['auditRefs'] as $audit_ref ) {
						if ( isset( $audit_ref['acronym'] ) && in_array( $audit_ref['acronym'], $selected_audit_refs, true ) ) {
							$audit_refs[]         = $audit_ref;
							$selected_audits_id[] = $audit_ref['id'];
						}

						if ( 'diagnostics' === $audit_ref['group'] ) {
							$selected_audits_id[] = $audit_ref['id'];
						}
					}

					$categories[ $key ]['auditRefs'] = $audit_refs ?? array();
				}
			}

			$selected_audits_id                     = array_unique( $selected_audits_id );
			$data['lighthouseResult']['categories'] = $categories;

			$audits = array();
			foreach ( $response['lighthouseResult']['audits'] as $key => $audit ) {
				if ( in_array( $key, $selected_audits_id, true ) ) {
					$audits[ $key ]['id']               = $audit['id'] ?? null;
					$audits[ $key ]['title']            = $audit['title'] ?? null;
					$audits[ $key ]['description']      = $audit['description'] ? preg_replace( '/\[(.*?)\]\((.*?)\)/', '<a href="$2" target="_blank">$1<span class="screen-reader-text"> (opens in a new tab)</span><span aria-hidden="true" class="dashicons dashicons-external"></span></a>', $audit['description'] ) : null;
					$audits[ $key ]['score']            = $audit['score'] ?? null;
					$audits[ $key ]['scoreDisplayMode'] = $audit['scoreDisplayMode'] ?? null;
					$audits[ $key ]['displayValue']     = $audit['displayValue'] ?? null;
				}
			}

			$dignostics = array(
				'diagnostics' => array(
					'font-display'                     => array(
						'type' => array(
							'FCP',
							'LCP',
						),
					),
					'critical-request-chains'          => array(
						'type' => array(
							'FCP',
							'LCP',
						),
					),
					'largest-contentful-paint-element' => array(
						'type' => array(
							'LCP',
						),
					),
					'layout-shift-elements'            => array(
						'type' => array(
							'CLS',
						),
					),
					'long-tasks'                       => array(
						'type' => array(
							'TBT',
						),
					),
					'render-blocking-resources'        => array(
						'type' => array(
							'FCP',
							'LCP',
						),
					),
					'unused-css-rules'                 => array(
						'type' => array(
							'FCP',
							'LCP',
						),
					),
					'unminified-css'                   => array(
						'type' => array(
							'FCP',
							'LCP',
						),
					),
					'unminified-javascript'            => array(
						'type' => array(
							'FCP',
							'LCP',
						),
					),
					'unused-javascript'                => array(
						'type' => array(
							'LCP',
						),
					),
					'uses-text-compression'            => array(
						'type' => array(
							'FCP',
							'LCP',
						),
					),
					'uses-rel-preconnect'              => array(
						'type' => array(
							'FCP',
							'LCP',
						),
					),
					'server-response-time'             => array(
						'type' => array(
							'FCP',
							'LCP',
						),
					),
					'redirects'                        => array(
						'type' => array(
							'FCP',
							'LCP',
						),
					),
					'uses-rel-preload'                 => array(
						'type' => array(
							'FCP',
							'LCP',
						),
					),
					'efficient-animated-content'       => array(
						'type' => array(
							'LCP',
						),
					),
					'duplicated-javascript'            => array(
						'type' => array(
							'TBT',
						),
					),
					'legacy-javascript'                => array(
						'type' => array(
							'TBT',
						),
					),
					'total-byte-weight'                => array(
						'type' => array(
							'LCP',
						),
					),
					'dom-size'                         => array(
						'type' => array(
							'TBT',
						),
					),
					'bootup-time'                      => array(
						'type' => array(
							'TBT',
						),
					),
					'mainthread-work-breakdown'        => array(
						'type' => array(
							'TBT',
						),
					),
					'third-party-summary'              => array(
						'type' => array(
							'TBT',
						),
					),
					'third-party-facades'              => array(
						'type' => array(
							'TBT',
						),
					),
					'non-composited-animations'        => array(
						'type' => array(
							'CLS',
						),
					),
					'unsized-images'                   => array(
						'type' => array(
							'CLS',
						),
					),
					'viewport'                         => array(
						'type' => array(
							'TBT',
						),
					),
				),
			);

			// Update audits with diagnostics type information if keys match
			foreach ( $dignostics['diagnostics'] as $diag_key => $diag_value ) {
				if ( isset( $audits[ $diag_key ] ) ) {
					$audits[ $diag_key ]['type'] = $diag_value['type'] ?? array();
				}
			}

			$data['lighthouseResult']['audits']         = $audits;
			$data['lighthouseResult']['configSettings'] = $response['lighthouseResult']['configSettings'];

			return $data;
		}
	}
}
