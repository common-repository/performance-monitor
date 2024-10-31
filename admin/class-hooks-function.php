<?php
/**
 * The admin-custom-hooks callback functionality.
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

if ( ! class_exists( 'PerformanceMonitor\Admin\Hooks_Function' ) ) {
	/**
	 * Class Hooks_function.
	 *
	 * Defines the calback functions for admin custom hooks.
	 *
	 * @since      1.0.0
	 */
	class Hooks_Function {

		public function qtpm_admin_page_title_callback() {
			return esc_html_e( 'Performance Monitor', 'performance-monitor' );
		}
	}
}
