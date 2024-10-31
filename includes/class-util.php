<?php
/**
 * Util class contains utility methods for formatting bytes and converting time to human-readable format.
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

if ( ! class_exists( 'PerformanceMonitor\Inc\Util' ) ) {
	/**
	 * Class Util
	 *
	 * Utility methods for formatting bytes and converting time to human-readable format.
	 * Includes methods for converting bytes to a readable format (KB, MB, GB) and
	 * formatting timestamps into user-friendly strings.
	 *
	 * @since 1.0.0
	 */
	class Util {
		/**
		 * Format bytes into a human-readable size.
		 *
		 * @since 1.0.0
		 *
		 * @param  float  $bytes The size in bytes to be formatted.
		 * @return string Formatted size in bytes, kilobytes, megabytes, or gigabytes.
		 */
		public static function qtpm_format_bytes( $bytes ) {
			$bytes = floatval( $bytes );
			if ( 1073741824 <= $bytes ) {
				$bytes = number_format( $bytes / 1073741824, 2 ) . ' GB';
			} elseif ( 1048576 <= $bytes ) {
				$bytes = number_format( $bytes / 1048576, 2 ) . ' MB';
			} elseif ( 1024 <= $bytes ) {
				$bytes = number_format( $bytes / 1024, 2 ) . ' KB';
			} elseif ( 1 < $bytes ) {
				$bytes = $bytes . ' bytes';
			} elseif ( 1 === $bytes ) {
				$bytes = $bytes . ' byte';
			} else {
				$bytes = '0 bytes';
			}

			return $bytes;
		}

		/**
		 * Convert time to a human-readable format.
		 *
		 * @since 1.0.0
		 *
		 * @param float $time The time to be converted.
		 * @return string Human-readable time format in seconds, milliseconds, or microseconds.
		 */
		public static function convert_time_to_human_readable( $time ) {
			$milliseconds = $time * 1000;

			// Determine the appropriate time unit based on the time
			if ( $milliseconds < 1 ) {
				return round( $milliseconds * 1000, 2 ) . ' microseconds';
			} elseif ( $milliseconds < 1000 ) {
				return round( $milliseconds, 2 ) . ' milliseconds';
			} else {
				return round( $time, 2 ) . ' seconds';
			}
		}

		/**
		 * Process resource matches to get their sizes and counts.
		 *
		 * @since  1.0.0
		 * @param array  $matches  The matches array.
		 * @param string $base_url The base URL of the page.
		 * @return array The processed resource data.
		 */
		public static function process_resource_matches( $matches, $base_url ) {
			$sizes = array();
			$count = array();

			foreach ( $matches[1] as $index => $resource_url ) {
				$resource_url  = self::ensure_absolute_url( $resource_url, $base_url );
				$resource_size = strlen( wp_remote_retrieve_body( wp_remote_get( $resource_url ) ) );
				$resource_id   = isset( $matches[2][ $index ] ) ? ( '' !== $matches[2][ $index ] ? $matches[2][ $index ] : $index ) : basename( $resource_url );
				$filename      = pathinfo( wp_parse_url( $resource_url )['path'], PATHINFO_BASENAME );

				if ( isset( $count[ $resource_id ] ) ) {
					$count[ $resource_id ] = $count[ $resource_id ] + 1;
				} else {
					$count[ $resource_id ] = 1;
				}

				$sizes[ $resource_id ] = array(
					'url'            => $resource_url,
					'size'           => $resource_size,
					'converted_size' => self::size_convertion( $resource_size ),
					'base_name'      => isset( $matches[2][ $index ] ) && '' !== $matches[2][ $index ] ? $matches[2][ $index ] : $filename,
					'count'          => $count[ $resource_id ],
					'version'        => self::get_version_from_url( $resource_url ),
				);

				if ( isset( $matches['lazy_load'][ $index ] ) ) {
					$sizes[ $resource_id ]['lazy_load'] = $matches['lazy_load'][ $index ];
				}
			}

			return $sizes;
		}

		/**
		 * Calculate the total size of resources considering their counts.
		 *
		 * @since  1.0.0
		 * @param array $resources The resources data.
		 * @return int The total size of resources.
		 */
		public static function calculate_total_size( $resources ) {
			$total_size = 0;
			foreach ( $resources as $resource ) {
				$total_size += $resource['size'] * $resource['count'];
			}

			return $total_size;
		}

		/**
		 * Ensure that a URL is absolute by adding scheme if missing.
		 *
		 * @since  1.0.0
		 * @access public
		 * @param  string $url           The URL to ensure as absolute.
		 * @param  string $original_url  The original URL to extract scheme if necessary.
		 * @return string                The absolute URL.
		 */
		public static function ensure_absolute_url( $url, $original_url ) {
			$parsed_url = wp_parse_url( $url );
			if ( ! isset( $parsed_url['scheme'] ) ) {
				$parsed_original_url = wp_parse_url( $original_url );
				$scheme              = isset( $parsed_original_url['scheme'] ) ? $parsed_original_url['scheme'] : 'http';
				$url                 = $scheme . '://' . ltrim( $url, '/' );
			}
			return $url;
		}

		/**
		 * Convert file size to a human-readable format.
		 *
		 * @since  1.0.0
		 * @access public
		 * @param int $size  The file size to convert.
		 * @return string    The human-readable size.
		 */
		public static function size_convertion( $size ) {
			if ( 1024 < $size ) {
				$size = round( $size / 1024, 2 );
				if ( 1024 < $size ) {
					$size = round( $size / 1024, 2 ) . ' MB';
					return $size;
				} else {
					$size = $size . ' KB';
					return $size;
				}
			} else {
				$size = $size . ' bytes';
				return $size;
			}
		}

		/**
		 * Formats a boolean constant value for display.
		 *
		 * @param string $constant The name of the constant to format.
		 * @return string The formatted constant value.
		 */
		public static function format_bool_constant( $constant ) {
			if ( ! defined( $constant ) ) {
				/* translators: Undefined PHP constant */
				return __( 'undefined', 'performace-monitor' );
			} elseif ( constant( $constant ) === '' ) {
				return __( 'empty string', 'performace-monitor' );
			} elseif ( is_string( constant( $constant ) ) && ! is_numeric( constant( $constant ) ) ) {
				return constant( $constant );
			} elseif ( ! constant( $constant ) ) {
				return 'false';
			} else {
				return 'true';
			}
		}

		/**
		 * Generate date query for a specific duration.
		 *
		 * This method generates a date query based on the specified duration.
		 *
		 * @since  1.0.0
		 * @access public
		 * @param string $duration  Duration for the date query ('week', 'month', 'year').
		 * @return array            Date query arguments.
		 */
		public static function data_duration( $duration ) {
			$date_query = array();

			switch ( $duration ) {
				case 'week':
					$date_query = self::generate_date_query( 'last monday' );
					break;
				case 'month':
					$date_query = self::generate_date_query( 'first day of this month' );
					break;
				case 'year':
					$date_query = self::generate_date_query( gmdate( 'Y' ) . '-01-01' );
					break;
				default:
					$date_query = self::generate_date_query( 'last monday' );
					break;
			}
			return $date_query;
		}

		/**
		 * Generate date query based on a time ago.
		 *
		 * This method generates a date query based on a specified time ago.
		 *
		 * @since 1.0.0
		 * @param string $time_ago The time ago string (e.g., '1 week ago').
		 * @return array           Date query arguments.
		 */
		public static function generate_date_query( $time_ago, $time_before = 'now' ) {

			return array(
				'post_date' => array(
					'after'  => gmdate( 'Y-m-d 00:00:00', strtotime( $time_ago ) ),
					'before' => gmdate( 'Y-m-d 24:59:59', strtotime( $time_before ) ),
				),
			);
		}

		/**
		 * Extracts the numeric value from a given string.
		 *
		 * @param string $str The input string to extract the numeric value.
		 * @since 1.0.0
		 */
		public static function extractnumeric( $str ) {
			preg_match( '/[\d,\.]+/', $str, $matches );
			return isset( $matches[0] ) ? (float) $matches[0] : 0;
		}

		/**
		 * Sets the x-axis title based on the duration and post date.
		 *
		 * @param array  $x_axis_title Reference to the array of x-axis titles.
		 * @param string $duration     The duration type (year, week, month, custom).
		 * @param string $post_date    The post date.
		 */
		public static function set_x_axis_title( &$x_axis_title, $duration, &$post_date ) {
			$date  = new \DateTime( $post_date, new \DateTimeZone( 'UTC' ) );
			$title = '';

			switch ( $duration ) {
				case 'year':
					$title = 'Year ' . $date->format( 'Y' );
					break;

				case 'week':
					$year  = $date->format( 'Y' );
					$week  = $date->format( 'W' );
					$title = "Week $week, $year";
					break;

				case 'month':
					$month = $date->format( 'M' );
					$year  = $date->format( 'Y' );
					$title = $month . '-' . $year;
					break;

				case 'custom':
					$title = 'Date';
			}

			if ( ! in_array( $title, $x_axis_title, true ) ) {
				$x_axis_title[] = $title;
			}
		}

		/**
		 * Sends a chart response with data and x-axis title.
		 *
		 * @param mixed $data The data to be sent in the response.
		 * @param mixed $x_axis_title Optional. The x-axis title. Default null.
		 *
		 * @return \WP_REST_Response The REST response object.
		 * @since 1.0.0
		 */
		public static function send_chart_response( $data, $x_axis_title = null ) {

			if ( null === $x_axis_title ) {
				$x_axis_title = __( 'Date', 'performance-monitor' );
			}

			$x_axis_title = is_array( $x_axis_title ) ? implode( '-', $x_axis_title ) : $x_axis_title;

			if ( empty( $data ) ) {
				return new \WP_REST_Response(
					array(
						'success' => false,
						'message' => __( 'Not enough data to analyse the performance.', 'performance-monitor' ),
					),
					200,
				);
			}

			return new \WP_REST_Response(
				array(
					'data'       => $data,
					'success'    => true,
					'xAxisTitle' => $x_axis_title,
				),
				200,
			);
		}

		/**
		 * Gets the label title based on the duration and post date.
		 *
		 * @param string $post_date Reference to the post date.
		 * @param string $duration  The duration type (year, month, week, custom).
		 * @param string $date      The date.
		 *
		 * @return string The label title.
		 */
		public static function get_label_title( &$post_date, $duration, $date ) {

			if ( $date ) {
				$date_time = new \DateTime( $date );
			} else {
				$date_time = new \DateTime( $post_date );
			}

			$y_axis_title = '';
			switch ( $duration ) {
				case 'year':
					$post_date    = $date;
					$y_axis_title = $date_time->format( 'M-Y' );
					break;

				case 'month':
					$y_axis_title = $date_time->format( 'd' );
					break;
				case 'week':
					$y_axis_title = $date_time->format( 'D' );
					break;
				case 'custom':
					$y_axis_title = $date_time->format( 'd-m-Y' );
					break;
			}
			return $y_axis_title;
		}

		/**
		 * Retrieve the version parameter from a URL.
		 *
		 * This function parses a given URL and extracts the version (`ver`) parameter
		 * from the query string if it exists.
		 *
		 * @param string $url The URL to parse.
		 * @return string The version number if found, or 'None' if not.
		 */
		public static function get_version_from_url( $url ) {
			$url_components = wp_parse_url( $url );

			if ( isset( $url_components['query'] ) ) {
				parse_str( $url_components['query'], $query_params );

				if ( isset( $query_params['ver'] ) ) {
					return $query_params['ver'];
				}
			}

			return 'None';
		}

		/**
		 * Initializes default sum data.
		 *
		 * @return array The initialized sum data.
		 */
		public static function initialize_sum_data() {
			return array(
				'desktop_data' => self::initialize_lighthouse_default(),
				'mobile_data'  => self::initialize_lighthouse_default(),
				'curl_data'    => array(
					'css'              => 0,
					'js'               => 0,
					'media'            => 0,
					'total_asset'      => 0,
					'total_css_size'   => 0,
					'total_js_size'    => 0,
					'media_total_size' => 0,
					'total_size'       => 0,
					'active_plugins'   => 0,
					'month_of_date'    => 0,
				),
			);
		}

		/**
		 * Initializes default Lighthouse data.
		 *
		 * @return array The initialized Lighthouse data.
		 */
		public static function initialize_lighthouse_default() {
			return array(
				'lighthouseResult' => array(
					'audits'     => array(
						'first-contentful-paint'   => array(
							'score'        => 0,
							'displayValue' => 0,
						),
						'largest-contentful-paint' => array(
							'score'        => 0,
							'displayValue' => 0,
						),
						'cumulative-layout-shift'  => array(
							'score'        => 0,
							'displayValue' => 0,
						),
						'speed-index'              => array(
							'score'        => 0,
							'displayValue' => 0,
						),
						'total-blocking-time'      => array(
							'score'        => 0,
							'displayValue' => 0,
						),
					),
					'categories' => array(
						'performance'    => array( 'score' => 0 ),
						'accessibility'  => array( 'score' => 0 ),
						'best-practices' => array( 'score' => 0 ),
						'seo'            => array( 'score' => 0 ),
					),
				),
				'load_time'        => 0,
			);
		}

		public static function minify_html( $html ) {
			return preg_replace(
				array(
					'/\>[^\S ]+/s',
					'/[^\S ]+\</s',
					'/(\s)+/s',
					'/>\s</',
				),
				array(
					'>',
					'<',
					'\\1',
					'><',
				),
				$html
			);
		}

		/**
		 * Returns an array of allowed HTML tags and attributes for use in the plugin.
		 *
		 * The allowed HTML includes common elements like `div`, `label`, `select`, `option`, `input`, and `canvas`, with a variety of attributes.
		 * This list is merged with the default allowed HTML for posts, as defined by WordPress.
		 *
		 * @since 1.0.0
		 * @return array The allowed HTML tags and attributes.
		 */
		public static function get_allowed_html() {
			$allowed_html = array(
				'select' => array(
					'id'   => array(),
					'name' => array(),
				),
				'option' => array(
					'value'    => array(),
					'selected' => array(),
				),
				'input'  => array(
					'name'     => array(),
					'type'     => array(),
					'value'    => array(),
					'id'       => array(),
					'class'    => array(),
					'max'      => array(),
					'disabled' => array(),
				),
				'canvas' => array(
					'id' => array(),
				),
			);

			// Merge with default allowed HTML for posts.
			return array_merge( $allowed_html, wp_kses_allowed_html( 'post' ) );
		}
	}
}
