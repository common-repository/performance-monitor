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

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

?>

<script type="text/html" id="tmpl-qtpm-pagespeed-metrics">
	<#
		var score = data.score * 100;
		var scoreClass = '';

		if( 'undefined' !== typeof data.score || null !== data.score ) {
			if ( 49 >= score ) {
				scoreClass = 'qtpm-metric-fail';
			} else if ( 89 >= score ) {
				scoreClass = 'qtpm-metric-average';
			} else if ( 100 >= score ) {
				scoreClass = 'qtpm-metric-pass';
			}
		}
	#>

	<div class="qtpm-metric-container {{scoreClass}}" id="{{data.id}}-{{data.device}}">
		<div class="qtpm-metric-icon "></div>
		<div class="qtpm-metric-title">{{data.title}}</div>
		<div class="qtpm-metric-value">{{data.displayValue}}</div>
	</div>
</script>
