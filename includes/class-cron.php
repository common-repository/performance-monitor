<?php
/**
 * Handles the cron related to Performance Monitor plugin.
 *
 * @link       https://qrolic.com
 * @since      1.0.0
 *
 * @package    PerformanceMonitor
 * @subpackage includes
 */

namespace PerformanceMonitor\Inc;

use PerformanceMonitor\Admin\System_Info;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'PerformanceMonitor\Inc\Cron' ) ) {
	/**
	 * Class Cron
	 *
	 * This class provides methods to manage cron jobs for data collection and processing related to performance monitoring.
	 * Cron jobs are scheduled based on user-defined settings such as frequency (daily, weekly, monthly) and specific days/times.
	 *
	 * @since 1.0.0
	 */
	class Cron {

		/**
		 * Constructor for the Cron class.
		 *
		 * Registers init hook to initiate cron setup.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			add_filter( 'cron_schedules', array( $this, 'cron_schedules' ) );
			add_action( 'init', array( $this, 'init' ) );
			add_action( 'qtpm_cron_job', array( $this, 'cron_job' ) );
			add_action( 'qtpm_cron_job_month_avg', array( $this, 'cron_job_month_avg' ) );
			add_action( 'qtpm_inital_cron', array( $this, 'cron_job' ) );
		}

		/**
		 * Customizes cron schedules.
		 *
		 * Adds a custom monthly schedule if it doesn't already exist.
		 *
		 * @param array $schedules An array of available cron schedules.
		 * @return array Modified array of cron schedules.
		 */
		public function cron_schedules( $schedules ) {
			if ( ! isset( $schedules['monthly'] ) ) {
				$schedules['monthly'] = array(
					'interval' => 2419200,
					'display'  => __( 'Once Monthly', 'performance-monitor' ),
				);
			}

			return $schedules;
		}

		/**
		 * Initializes cron setup based on plugin settings.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function init() {
			$options = get_option( 'qtpm_setting_settings', array() );

			$cron_frequency = $options['cron_frequency'] ?? 'weekly';
			$cron_day       = $options['cron_day'] ?? 'sunday';
			$cron_month_day = $options['cron_month_day'] ?? 28;
			$cron_time      = $options['cron_time'] ?? '23:55';

			if ( 'none' === $cron_frequency ) {
				$this->unschedule_cron_job();
				return;
			}

			$current_schedule = wp_get_schedule( 'qtpm_cron_job' );
			$recurrence       = $cron_frequency;
			$timestamp        = 0;

			if ( 'daily' === $cron_frequency ) {
				$timestamp = strtotime( 'today ' . $cron_time );
			} elseif ( 'weekly' === $cron_frequency ) {
				$timestamp = strtotime( 'this ' . $cron_day . ' ' . $cron_time );
			} elseif ( 'monthly' === $cron_frequency ) {
				$day_of_month = (int) $cron_month_day;
				if ( 1 <= $day_of_month && 28 >= $day_of_month ) {
					$timestamp = strtotime( 'first day of next month ' . $cron_time );
					$timestamp = strtotime( '+' . ( $day_of_month - 1 ) . ' days', $timestamp );
				}
			}

			if ( $current_schedule !== $recurrence ) {
				$this->unschedule_cron_job();
				$this->schedule_cron_job( $timestamp, $recurrence );
				return;
			}

			// Check if the scheduled event matches the current settings
			if ( $this->is_cron_job_mismatched( $recurrence, $cron_day, $cron_month_day, $cron_time ) ) {
				$this->unschedule_cron_job();
				$this->schedule_cron_job( $timestamp, $recurrence );
				return;
			}

			$this->chart_monthly_cron();
		}

		/**
		 * Schedules a cron job.
		 *
		 * @since    1.0.0
		 * @access   public
		 * @param    int     $timestamp    timestamp for the cron job.
		 * @param    string  $recurrence   Recurrence of the cron job (daily, weekly, monthly).
		 */
		public function schedule_cron_job( $timestamp, $recurrence ) {
			wp_schedule_event( $timestamp, $recurrence, 'qtpm_cron_job' );
		}

		/**
		 * Unschedules a cron job.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function unschedule_cron_job() {
			if ( wp_next_scheduled( 'qtpm_cron_job' ) ) {
				wp_clear_scheduled_hook( 'qtpm_cron_job' );
			}
		}

		public function chart_monthly_cron() {
			if ( ! wp_next_scheduled( 'qtpm_cron_job_month_avg' ) ) {
				wp_schedule_single_event( strtotime( 'first day of next month' ), 'qtpm_cron_job_month_avg' );
			}
		}

		/**
		 * Checks if the cron job is mismatched with the current settings.
		 *
		 * @since    1.0.0
		 * @access   public
		 * @param    string  $recurrence             Recurrence of the cron job.
		 * @param    string  $cron_day     Day for the cron job.
		 * @param    string  $cron_month_day   Month day for the cron job.
		 * @param    string  $cron_time    Time for the cron job.
		 */
		public function is_cron_job_mismatched( $recurrence, $cron_day, $cron_month_day, $cron_time ) {
			if ( 'daily' === $recurrence ) {
				$scheduled_time = gmdate( 'H:i', wp_next_scheduled( 'qtpm_cron_job' ) );

				return $cron_time !== $scheduled_time;
			}

			if ( 'weekly' === $recurrence ) {
				$scheduled_day  = strtolower( gmdate( 'l', wp_next_scheduled( 'qtpm_cron_job' ) ) );
				$scheduled_time = gmdate( 'H:i', wp_next_scheduled( 'qtpm_cron_job' ) );

				return $cron_day !== $scheduled_day || $cron_time !== $scheduled_time;
			}

			if ( 'monthly' === $recurrence ) {
				$scheduled_day  = gmdate( 'j', wp_next_scheduled( 'qtpm_cron_job' ) );
				$scheduled_time = gmdate( 'H:i', wp_next_scheduled( 'qtpm_cron_job' ) );

				return $cron_month_day !== $scheduled_day || $cron_time !== $scheduled_time;
			}

			return false;
		}

		/**
		 * Callback function for the cron job.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function cron_job() {
			if ( ! defined( 'ABSPATH' ) && wp_doing_cron() ) {
				/** Set up WordPress environment */
				require_once __DIR__ . '/wp-load.php';
			}

			General::store_installed_plugins_data();
			General::store_curl_data();
			General::store_pagespeed_api_data();
			$system_info = new System_Info();
			$system_info->get_cache_info();
		}

		/**
		 * Retrieves data for chart on a monthly basis and stores it in a post.
		 *
		 * @since  1.0.0
		 * @access public
		 */
		public function cron_job_month_avg() {
			$data             = array();
			$home_url         = home_url() . '/';
			$last_month_start = strtotime( 'first day of last month' );
			$last_month_end   = strtotime( 'last day of last month' );
			$date_query       = array( 'date_query' => Util::generate_date_query( 'first day of last month', 'last day of last month' ) );

			$pagespeed_post = General::get_post_ids_by_meta_key( 'pagespeed_api_data', $date_query, -1 );
			$curl_post      = General::get_post_ids_by_meta_key( 'curl_data', $date_query, -1 );
			$plugin_post    = General::get_post_ids_by_meta_key( 'plugins_data', $date_query, -1 );

			$num_pagespeed_posts = count( $pagespeed_post ) ?? 0;
			$num_curl_posts      = count( $curl_post ) ?? 0;

			foreach ( $pagespeed_post as $post_id ) {

				$pagespeed_api_data = get_post_meta( $post_id, 'pagespeed_api_data', true );

				$data[] = array(
					'desktop_data' => General::extract_lighthouse_data( $pagespeed_api_data['desktop_data'] ),
					'mobile_data'  => General::extract_lighthouse_data( $pagespeed_api_data['mobile_data'] ),
				);
			}

			foreach ( $curl_post as $index => $post_id ) {
				$curl_data = get_post_meta( $post_id, 'curl_data', true );
				if ( isset( $data[ $index ] ) ) {
					General::add_curl_data( $data[ $index ], $curl_data );
				}
			}

			foreach ( $plugin_post as $index => $post_id ) {
				$plugins_data = get_post_meta( $post_id, 'plugins_data', true );
				General::add_plugin_data( $data[ $index ], $plugins_data );
			}

			$sum_data = Util::initialize_sum_data();

			foreach ( $data as $entry ) {
				if ( isset( $entry['desktop_data'] ) && isset( $entry['mobile_data'] ) && isset( $entry['curl_data'] ) ) {
					General::sum_data( $sum_data['desktop_data'], $entry['desktop_data'] );
					General::sum_data( $sum_data['mobile_data'], $entry['mobile_data'] );
					General::sum_curl_data( $sum_data['curl_data'], $entry['curl_data'] );
				}
			}

			$avg_data                               = General::calculate_averages( $sum_data, $num_pagespeed_posts, $num_curl_posts );
			$avg_data['curl_data']['month_of_date'] = gmdate( 'Y-m-d', $last_month_end );
			$avg_data['curl_data']['page_url']      = $home_url;

			General::save_avg_data( $avg_data );
		}
	}
}
