<?php

/**
 * Settings class handles the configuration and management of Performance Monitor plugin settings.
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

if ( ! class_exists( 'PerformanceMonitor\Admin\Settings' ) ) {
	/**
	 * Class Settings
	 *
	 * This class defines various tabs, sections, and fields for settings.
	 *
	 * @since 1.0.0
	 */
	class Settings {
		/**
		 * The tabs for different settings sections.
		 *
		 * @var array $pm_tabs Array containing information about different settings tabs.
		 */
		private $tabs;

		/**
		 * Constructor for the Settings class.
		 *
		 * Initializes the Performance Monitor settings by defining tabs and their respective fields.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			add_action( 'admin_menu', array( $this, 'qtpm_admin_menu' ) );
			add_action( 'admin_init', array( $this, 'initialize_settings' ) );

			$this->tabs = apply_filters(
				'qtpm_settings_tabs',
				array(
					'dashboard'   => array(
						'tab_name' => esc_html__( 'Dashboard', 'performance-monitor' ),
						'label'    => esc_html__( 'Performance Analysis', 'performance-monitor' ),
					),
					'system_info' => array(
						'tab_name' => esc_html__( 'System Info', 'performance-monitor' ),
						'label'    => esc_html__( 'System Info', 'performance-monitor' ),
					),
					'curl'        => array(
						'tab_name' => esc_html__( 'CURL', 'performance-monitor' ),
						'label'    => esc_html__( 'Resource Info', 'performance-monitor' ),
					),
					'page_speed'  => array(
						'tab_name' => esc_html__( 'PageSpeed', 'performance-monitor' ),
						'label'    => esc_html__( 'Google PageSpeed ', 'performance-monitor' ),
					),
					'setting'     => array(
						'tab_name' => esc_html__( 'Settings', 'performance-monitor' ),
						'label'    => esc_html__( 'Settings', 'performance-monitor' ),
						'fields'   => array(
							'cron_frequency'      => esc_html__( 'Performance Monitor Frequency', 'performance-monitor' ),
							'delete_on_uninstall' => esc_html__( 'Delete on Uninstall', 'performance-monitor' ),
						),
					),
				),
			);
		}

		/**
		 * Adds the Performance Monitor menu to the admin menu.
		 *
		 * @since  1.0.0
		 * @access public
		 * @return void
		 */
		public function qtpm_admin_menu() {
			add_menu_page(
				__( 'Performance Monitor', 'performance-monitor' ),
				__( 'Performance', 'performance-monitor' ),
				'manage_options',
				'performance-monitor',
				array( $this, 'qtpm_admin_page_callback' ),
				'dashicons-performance'
			);

			foreach ( $this->tabs as $tab => $data ) {
				add_submenu_page(
					'performance-monitor',
					$data['tab_name'],
					$data['tab_name'],
					'manage_options',
					'performance-monitor#qtpm-' . $tab . '-tab',
					array( $this, 'qtpm_admin_page_callback' )
				);
			}

			remove_submenu_page( 'performance-monitor', 'performance-monitor' );
		}

		/**
		 * Callback function to render the admin page.
		 *
		 * @since  1.0.0
		 * @access public
		 * @return void
		 */
		public function qtpm_admin_page_callback() {
			$qtpm_tabs = $this->tabs;
			include QTPM_PLUGIN_DIR . 'admin/partials/qtpm-admin-setting.php';
		}

		/**
		 * Initializes the settings by adding sections, fields, and callbacks.
		 *
		 * @since  1.0.0
		 * @access public
		 * @return void
		 */
		public function initialize_settings() {
			foreach ( $this->tabs as $tab => $data ) {
				add_settings_section(
					'qtpm_' . $tab . '_section',
					$data['label'],
					array( $this, $tab . '_section_info' ),
					'qtpm_' . $tab . '_settings'
				);

				$data['fields'] = apply_filters( 'qtpm_tab_fields_' . $tab, $data['fields'] ?? array() );

				foreach ( $data['fields'] as $field => $field_title ) {
					register_setting(
						'qtpm_' . $tab . '_' . $field,
						'qtpm_' . $tab . '_' . $field,
					);

					$callback = apply_filters( 'qtpm_field_callback', array( $this, $field . '_field_callback' ), $field );

					add_settings_field(
						'qtpm_' . $field,
						esc_html( $field_title ),
						$callback,
						'qtpm_' . $tab . '_settings',
						'qtpm_' . $tab . '_section',
						array( 'field' => $field ),
					);
				}
			}
		}

		/**
		 * Callback to display dashboard settings section info.
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */
		public function dashboard_section_info() {
			include_once QTPM_PLUGIN_DIR . 'admin/partials/qtpm-dashboard-chart-display.php';
		}

		/**
		 * Callback to display system info section.
		 *
		 * @since  1.0.0
		 * @access public
		 * @return void
		 */
		public function dashboard_field_callback() {
			echo '<h1>' . esc_html__( 'General field callback', 'performance-monitor' ) . '</h1>';
		}

		/**
		 * Display information about the system and installed plugins in the system info section.
		 *
		 * @since  1.0.0
		 * @access public
		 * @return void
		 */
		public function system_info_section_info() {
			include_once QTPM_PLUGIN_DIR . 'admin/partials/qtpm-system-info-display.php';
			include_once QTPM_PLUGIN_DIR . 'admin/partials/qtpm-system-info-parts/tmpl-qtpm-system-info-table.php';
			include_once QTPM_PLUGIN_DIR . 'admin/partials/qtpm-system-info-parts/tmpl-qtpm-plugin-info-table.php';
			include_once QTPM_PLUGIN_DIR . 'admin/partials/qtpm-system-info-parts/tmpl-qtpm-cron-info-table.php';
		}

		/**
		 * Callback to display cURL settings section info.
		 *
		 * @since  1.0.0
		 * @access public
		 * @return void
		 */
		public function curl_section_info() {
			include_once QTPM_PLUGIN_DIR . 'admin/partials/qtpm-admin-curl-settings.php';
			include_once QTPM_PLUGIN_DIR . 'admin/partials/tmpl-qtpm-curl-data-display.php';
		}

		/**
		 * Callback to display PageSpeed settings section info.
		 *
		 * @since  1.0.0
		 * @access public
		 * @return void
		 */
		public function page_speed_section_info() {
			include_once QTPM_PLUGIN_DIR . 'admin/partials/qtpm-pagespeed-display.php';
			include_once QTPM_PLUGIN_DIR . 'admin/partials/qtpm-pagespeed-parts/tmpl-qtpm-pagespeed-metrics.php';
			include_once QTPM_PLUGIN_DIR . 'admin/partials/qtpm-pagespeed-parts/tmpl-qtpm-pagespeed-diagnostics.php';
			include_once QTPM_PLUGIN_DIR . 'admin/partials/qtpm-pagespeed-parts/tmpl-qtpm-pagespeed-gauge.php';
			include_once QTPM_PLUGIN_DIR . 'admin/partials/qtpm-pagespeed-parts/tmpl-qtpm-pagespeed-device.php';
		}

		/**
		 * Callback to display setting section.
		 *
		 * @since  1.0.0
		 * @access public
		 * @return void
		 */
		public function setting_section_info() {
			//
		}

		/**
		 * Callback to display setting section.
		 *
		 * @since  1.0.0
		 * @access public
		 * @return void
		 */
		public function cron_frequency_field_callback() {
			$options            = get_option( 'qtpm_setting_settings' );
			$selected_value     = sanitize_text_field( $options['cron_frequency'] ?? 'weekly' );
			$selected_day       = sanitize_text_field( $options['cron_day'] ?? 'sunday' );
			$selected_month_day = absint( $options['cron_month_day'] ?? 28 );
			$time_value         = sanitize_text_field( $options['cron_time'] ?? '23:55' );

			include_once QTPM_PLUGIN_DIR . 'admin/partials/qtpm-setting.php';
		}

		/**
		 * Callback to display setting section for deleting data on plugin uninstall.
		 *
		 * Retrieves and sanitizes the stored option for whether to delete plugin settings
		 * and data upon uninstallation. Renders a checkbox UI element to allow users to
		 * select this option based on their preference.
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */
		public function delete_on_uninstall_field_callback() {
			$options = get_option( 'qtpm_setting_settings' );
			$value   = $options['delete_on_uninstall'] ?? '0';
			$checked = 'on' === $value ? 'checked' : '';
			echo '<label for="delete_on_uninstall">';
			echo '<input type="checkbox" id="qtpm_delete_on_uninstall" name="qtpm_setting_settings[delete_on_uninstall]" ' . esc_attr( $checked ) . '>';
			echo esc_html__( 'When you uninstall this plugin, what do you want to do with your plugin\'s settings and data', 'performance-monitor' );
			echo '</label>';
		}
	}
}
