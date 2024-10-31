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

<script type="text/html" id="tmpl-qtpm-pagespeed-gauge">
	<div class="gauge-audit gauge-overall" data-percentage="{{data.overallPerformance}}">
		<span>{{data.overallPerformance}}</span>
	</div>
</script>
