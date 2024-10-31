<?php
/**
 * The file that defines the core plugin class
 *
 * @link       https://qrolic.com
 * @since      1.0.0
 *
 * @package    PerformanceMonitor
 * @subpackage includes
 */

namespace PerformanceMonitor\Inc;

use PerformanceMonitor\Admin;
use PerformanceMonitor\Admin\Settings;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'PerformanceMonitor\Inc\Main' ) ) {
	/**
	 * Class Main
	 *
	 * This is used to define internationalization, admin-specific hooks, and
	 * public-facing site hooks.
	 *
	 * Also maintains the unique identifier of this plugin as well as the current
	 * version of the plugin.
	 *
	 * @since      1.0.0
	 */
	class Main {

		/**
		 * The loader that's responsible for maintaining and registering all hooks that power
		 * the plugin.
		 *
		 * @since    1.0.0
		 * @access   protected
		 * @var      Loader    $loader    Maintains and registers all hooks for the plugin.
		 */
		protected $loader;

		/**
		 * The unique identifier of this plugin.
		 *
		 * @since    1.0.0
		 * @access   protected
		 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
		 */
		protected $plugin_name;

		/**
		 * The current version of the plugin.
		 *
		 * @since    1.0.0
		 * @access   protected
		 * @var      string    $version    The current version of the plugin.
		 */
		protected $version;

		/**
		 * Define the core functionality of the plugin.
		 *
		 * Set the plugin name and the plugin version that can be used throughout the plugin.
		 * Load the dependencies, define the locale, and set the hooks for the admin area and
		 * the public-facing side of the site.
		 *
		 * @since    1.0.0
		 */
		public function __construct() {
			if ( defined( 'QTPM_PLUGIN_VERSION' ) ) {
				$this->version = QTPM_PLUGIN_VERSION;
			} else {
				$this->version = '1.0.0';
			}

			if ( defined( 'QTPM_PLUGIN_NAME' ) ) {
				$this->plugin_name = QTPM_PLUGIN_NAME;
			} else {
				$this->plugin_name = 'performance-monitor';
			}

			$this->load_dependencies();
			$this->set_locale();
			$this->define_admin_hooks();
		}

		/**
		 * Load the required dependencies for this plugin.
		 *
		 * Include the following files that make up the plugin:
		 *
		 * - PerformanceMonitor_Loader. Orchestrates the hooks of the plugin.
		 * - PerformanceMonitor_i18n. Defines internationalization functionality.
		 * - PerformanceMonitor_Admin. Defines all hooks for the admin area.
		 * - PerformanceMonitor_Public. Defines all hooks for the public side of the site.
		 *
		 * Create an instance of the loader which will be used to register the hooks
		 * with WordPress.
		 *
		 * @since    1.0.0
		 * @access   private
		 */
		private function load_dependencies() {

			/**
			 * The class responsible for orchestrating the actions and filters of the
			 * core plugin.
			 */
			require_once QTPM_PLUGIN_DIR . 'includes/class-loader.php';

			/**
			 * The class responsible for defining internationalization functionality
			 * of the plugin.
			 */
			require_once QTPM_PLUGIN_DIR . 'includes/class-i18n.php';

			/**
			 * The class responsible for defining all actions that occur in the admin area.
			 */

			require_once QTPM_PLUGIN_DIR . 'includes/class-util.php';

			require_once QTPM_PLUGIN_DIR . 'includes/class-general.php';

			require_once QTPM_PLUGIN_DIR . 'includes/class-cron.php';

			require_once QTPM_PLUGIN_DIR . 'admin/class-admin.php';

			require_once QTPM_PLUGIN_DIR . 'admin/class-dashboard.php';

			require_once QTPM_PLUGIN_DIR . 'admin/class-settings.php';

			require_once QTPM_PLUGIN_DIR . 'admin/class-system-info.php';

			require_once QTPM_PLUGIN_DIR . 'admin/class-pagespeed.php';

			require_once QTPM_PLUGIN_DIR . 'admin/class-curl.php';

			require_once QTPM_PLUGIN_DIR . 'includes/class-rest-callback.php';

			require_once QTPM_PLUGIN_DIR . 'includes/class-rest.php';

			$this->loader = new Loader();
		}

		/**
		 * Define the locale for this plugin for internationalization.
		 *
		 * Uses the PerformanceMonitor_i18n class in order to set the domain and to register the hook
		 * with WordPress.
		 *
		 * @since    1.0.0
		 * @access   private
		 */
		private function set_locale() {

			$plugin_i18n = new I18n();

			$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
		}

		/**
		 * Register all of the hooks related to the admin area functionality
		 * of the plugin.
		 *
		 * @since    1.0.0
		 * @access   private
		 */
		private function define_admin_hooks() {

			$plugin_admin = new Admin( $this->get_plugin_name(), $this->get_version() );
			$setting      = new Settings();
			$cron         = new Cron();
			$rest         = new Rest();
			$this->loader->add_action( 'init', $this, 'qtpm_register_post_type' );
			$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
			$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
			$this->loader->add_action( 'script_loader_tag', $plugin_admin, 'load_scripts', 10, 2 );
		}

		/**
		 * Run the loader to execute all of the hooks with WordPress.
		 *
		 * @since    1.0.0
		 */
		public function run() {
			$this->loader->run();
		}

		/**
		 * The name of the plugin used to uniquely identify it within the context of
		 * WordPress and to define internationalization functionality.
		 *
		 * @since     1.0.0
		 * @return    string    The name of the plugin.
		 */
		public function get_plugin_name() {
			return $this->plugin_name;
		}

		/**
		 * The reference to the class that orchestrates the hooks with the plugin.
		 *
		 * @since     1.0.0
		 * @return    Loader    Orchestrates the hooks of the plugin.
		 */
		public function get_loader() {
			return $this->loader;
		}

		/**
		 * Retrieve the version number of the plugin.
		 *
		 * @since     1.0.0
		 * @return    string    The version number of the plugin.
		 */
		public function get_version() {
			return $this->version;
		}

		/**
		 * Registers a custom post type for Performance Monitor plugin.
		 *
		 * This function registers a custom post type with specific settings for the plugin's functionality.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
		public function qtpm_register_post_type() {
			$args = array(
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => false,
				'show_in_menu'       => false,
				'query_var'          => true,
				'rewrite'            => false,
				'capability_type'    => 'post',
				'has_archive'        => false,
				'hierarchical'       => false,
				'menu_position'      => null,
				'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' ),
			);

			register_post_type( QTPM_PLUGIN_POST_TYPE, $args );
		}
	}
}
