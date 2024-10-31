<?php
/**
 * Provides curl view for the plugin
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

<script type="text/html" id="tmpl-qtpm-curl-data-display">
	<div class="qtpm-curl-data">
		<div class="qtpm-curl-data-header">
			<h2><?php esc_html_e( 'Page: ', 'performance-monitor' ); ?><a href="{{data.page_url}}" title="{{data.page_url}}" target="_blank">{{data.page_title}}</a></h2>
			<div class="qtpm-badge-container">
				<span class="qtpm-badge qtpm-badge-primary"><?php esc_html_e( 'Resource Load Time: ', 'performance-monitor' ); ?>{{data.load_time}} sec</span>
				<span class="qtpm-badge qtpm-badge-primary"><?php esc_html_e( 'Total Size: ', 'performance-monitor' ); ?>{{data.total_size}} </span>
				<# ['CSS', 'JS', 'Media'].forEach(type => { if (data[type.toLowerCase() + '_sizes'] ) { #>
					<span class="qtpm-badge qtpm-badge-secondary">{{type}}: {{data[type.toLowerCase() + '_count']}}</span>
				<# } }); #>
			</div>
			<button class="qtpm-accordion-button" type="button" aria-label="<?php esc_html_e( 'Toggle CSS Sizes', 'performance-monitor' ); ?>">+</button>
		</div>
		<div class="qtpm-curl-data-display" style="display: none;">
			<# ['CSS', 'JS', 'Media'].forEach(type => { if (data[type.toLowerCase() + '_sizes'] ) { #>
				<div class="qtpm-accordion-container">
					<div class="qtpm-accordion-header">
						<h3>{{type}} Sizes</h3>
						<div class="qtpm-badge-container">
							<span class="qtpm-badge qtpm-badge-primary">Total {{type}} Size: {{data[type.toLowerCase() + '_total_size']}}</span>
						</div>
						<button class="qtpm-accordion-button" type="button" aria-label="Toggle {{type}} Sizes">+</button>
					</div>
					<div class="qtpm-resource">
						<table class="qtpm-resource-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'File', 'performance-monitor' ); ?></th>
									<th><?php esc_html_e( 'Version', 'performance-monitor' ); ?></th>
									<th style="width: 13%;"><?php esc_html_e( 'Size', 'performance-monitor' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<# _.each( data[type.toLowerCase() + '_sizes'], function( size ) { #>
									<tr>
										<td><strong><a href="{{size.url}}" target="_blank">{{size.base_name}}</a></strong></td>
										<td>{{size.version}}</td>
										<td>{{size.converted_size}}</td>
									</tr>
								<# }); #>
							</tbody>
						</table>
					</div>
				</div>
			<# } }); #>
		</div>
	</div>
</script>

