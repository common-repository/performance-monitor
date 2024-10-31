<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://qrolic.com
 * @since      1.0.0
 *
 * @package    PerformanceMonitor
 * @subpackage Admin
 */

namespace PerformanceMonitor;

use PerformanceMonitor\Admin\Hooks_Function;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'PerformanceMonitor\Admin' ) ) {
	/**
	 * Class Admin
	 *
	 * Defines the plugin name, version, and enqueues the admin-specific stylesheet and JavaScript.
	 *
	 * @since      1.0.0
	 */
	class Admin {

		/**
		 * The ID of this plugin.
		 *
		 * @since    1.0.0
		 * @access   private
		 * @var      string    $plugin_name    The ID of this plugin.
		 */
		private $plugin_name;

		/**
		 * The version of this plugin.
		 *
		 * @since    1.0.0
		 * @access   private
		 * @var      string    $version    The current version of this plugin.
		 */
		private $version;

		/**
		 * Initialize the class and set its properties.
		 *
		 * @since      1.0.0
		 * @param      string    $plugin_name    The name of this plugin.
		 * @param      string    $version        The version of this plugin.
		 */
		public function __construct( $plugin_name, $version ) {

			$this->plugin_name = $plugin_name;
			$this->version     = $version;

			$this->define_custome_hooks();
		}

		/**
		 * Register the stylesheets for the admin area.
		 *
		 * @since    1.0.0
		 */
		public function enqueue_styles() {
			$screen = get_current_screen();

			if ( ! is_admin() || 'toplevel_page_performance-monitor' !== $screen->base ) {
				return;
			}

			wp_enqueue_style( $this->plugin_name, QTPM_PLUGIN_URL . 'admin/src/css/style.css', array(), $this->version, 'all' );
		}

		/**
		 * Register the JavaScript for the admin area.
		 *
		 * @since    1.0.0
		 */
		public function enqueue_scripts() {
			$screen = get_current_screen();

			if ( ! is_admin() || 'toplevel_page_performance-monitor' !== $screen->base ) {
				return;
			}

			wp_enqueue_script( $this->plugin_name, QTPM_PLUGIN_URL . 'admin/src/js/main.js', array( 'jquery', 'wp-util' ), $this->version, true );

			wp_localize_script(
				$this->plugin_name,
				'qtpmRestSettings',
				array(
					'url'   => esc_url( rest_url( 'performance-monitor/v1' ) ),
					'nonce' => wp_create_nonce( 'wp_rest' ),
				),
			);

			wp_localize_script(
				$this->plugin_name,
				'qtpmMessageObject',
				array(
					'date_range_error' => esc_html__( 'Please select a valid date range', 'performance-monitor' ),
					'start_end_error'  => esc_html__( 'Start date cannot be greater than end date', 'performance-monitor' ),
					'loading'          => esc_html__( 'Loading...', 'performance-monitor' ),
					'collapse_all'     => esc_html__( 'Collapse All', 'performance-monitor' ),
					'expand_all'       => esc_html__( 'Expand All', 'performance-monitor' ),
					'collapse'         => esc_html__( 'Collapse', 'performance-monitor' ),
					'expand'           => esc_html__( 'Expand', 'performance-monitor' ),
				),
			);
		}

		public function load_scripts( $tag, $handle ) {

			if ( $this->plugin_name === $handle ) {
				$tag = str_replace( ' src', ' type="module" src', $tag );
			}
			return $tag;
		}
		/**
		 * Define custom hooks.
		 *
		 * @since    1.0.0
		 */
		public function define_custome_hooks() {
			include QTPM_PLUGIN_DIR . 'admin/class-hooks-function.php';

			$callback = new Hooks_Function();
			add_filter( 'qtpm_admin_page_title', array( $callback, 'qtpm_admin_page_title_callback' ) );
		}
	}
}
