<?php

use PerformanceMonitor\Inc\Util;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

ob_start();
?>
<div>
	<div class="qtpm-button-container">
		<div class="qtpm-curl-button-container">
			<button class="button button-primary" type="button" id="qtpm-get-latest-curl-data"><?php esc_html_e( 'Get latest metrix', 'performance-monitor' ); ?></button>
			<button class="button button-primary" type="button" id="qtpm-get-all-page-matrix"><?php esc_html_e( 'Get All Pages Matrix', 'performance-monitor' ); ?></button>
		</div>
		<button class="button button-primary" type="button" id="qtpm-collapse-button" data-action="expand"><?php esc_html_e( 'Expand', 'performance-monitor' ); ?></button>
	</div>
	<div class="qtpm-progress_bar">
		<div class="qtpm-progress_bar_item">
			<div class="qtpm-item_label"><?php esc_html_e( 'Loading...', 'performance-monitor' ); ?></div>
			<div class="qtpm-item_value">0%</div>
			<div class="qtpm-item_bar">
				<div class="qtpm-progress" data-progress="0"></div>
			</div>
		</div>
	</div>
	<div id="qtpm-curl-display"></div>
</div>

<?php
echo wp_kses( Util::minify_html( ob_get_clean() ), Util::get_allowed_html() );
