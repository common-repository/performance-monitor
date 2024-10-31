<?php
/**
 * Provides PageSpeed view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://qrolic.com
 * @since      1.0.0
 *
 * @package    PerformanceMonitor
 */

use PerformanceMonitor\Inc\Util;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

ob_start();
?>

<div>
	<button class="button button-primary" type="button" id="run-pagespeed-api">
		<?php esc_html_e( 'Run PageSpeed Analysis', 'performance-monitor' ); ?>
	</button>

	<div class="qtpm-pagespeed-device-tab">
		<input type="radio" name="qtpm_pagespeed_device" id="qtpm-pagespeed-desktop">
		<label for="qtpm-pagespeed-desktop" class="active"><?php esc_html_e( 'Desktop', 'performance-monitor' ); ?></label>

		<input type="radio" name="qtpm_pagespeed_device" id="qtpm-pagespeed-mobile">
		<label for="qtpm-pagespeed-mobile"><?php esc_html_e( 'Mobile', 'performance-monitor' ); ?></label>
	</div>

	<div class="qtpm-pagespeed-device-container" id="qtpm-pagespeed-mobile-container">
	<?php esc_html_e( 'Loading...', 'performance-monitor' ); ?>
	</div>

	<div class="qtpm-pagespeed-device-container" id="qtpm-pagespeed-desktop-container">
	<?php esc_html_e( 'Loading...', 'performance-monitor' ); ?>
	</div>
</div>

<?php
echo wp_kses( Util::minify_html( ob_get_clean() ), Util::get_allowed_html() );
