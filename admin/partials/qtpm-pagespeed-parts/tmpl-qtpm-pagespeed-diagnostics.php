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

<script type="text/html" id="tmpl-qtpm-pagespeed-diagnostics">
	<#
		var score = data.score * 100;
		var auditClass = '';

		if( 'undefined' !== typeof data.score & null !== data.score ) {
			if ( 49 >= score ) {
				auditClass = 'qtpm-audit-fail';
			} else if ( 89 >= score ) {
				auditClass = 'qtpm-audit-average';
			} else if ( 100 >= score && 'informative' !== data.scoreDisplayMode) {
				auditClass = 'qtpm-audit-pass';
			}
		} else {
			auditClass = 'qtpm-audit-default';
		}
	#>
	

	<div id="qtpm-{{data.id}}-container-{{data.device}}" class="{{auditClass}} qtpm-element-container">
		<h4 class="qtpm-audit-accordion-heading">
			<button aria-expanded="false" class="qtpm-audit-accordion-trigger" aria-controls="qtpm-audit-{{data.id}}-{{data.device}}" type="button">
				<span class="qtpm-audit-icon"></span>
				<span class="title">{{data.title}}</span>
				<# if ( 'undefined' !== typeof data.displayValue && null !== data.displayValue && '' !== data.displayValue ) { #>
					<span class="badge">{{data.displayValue}}</span>
				<# } #>
				<span class="icon"></span>
			</button>
		</h4>

		<div id="qtpm-audit-{{data.id}}-{{data.device}}" class="qtpm-audit-accordion-panel" hidden="hidden">
			{{{data.description}}}
		</div>
	</div>
</script>
