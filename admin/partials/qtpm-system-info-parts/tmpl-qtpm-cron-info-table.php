<?php
/**
 * Provides cron Info table view
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

<script type="text/html" id="tmpl-qtpm-cron-info-table">
	<table class="fixed striped table-view-list widefat wp-list-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Hook', 'performance-monitor' ); ?></th>
				<th style="width: 10%;"><?php esc_html_e( 'Arguments', 'performance-monitor' ); ?></th>
				<th style="width: 13%;"><?php esc_html_e( 'Next Run (UTC)', 'performance-monitor' ); ?></th>
				<th><?php esc_html_e( 'Action', 'performance-monitor' ); ?></th>
				<th style="width: 8%;"><?php esc_html_e( 'Recurrence', 'performance-monitor' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<# _.each( data, function( cron ) { #>
				<tr>
					<td>
						<# if ( cron.is_default ) { #>
							<span class="dashicons dashicons-wordpress" aria-hidden="true" title="<?php esc_html_e( 'This is a WordPress core event.', 'performance-monitor' ); ?>"></span>
							<span class="screen-reader-text"><?php esc_html_e( 'This is a WordPress core event.', 'performance-monitor' ); ?></span>
						<# } else { #>
							<span class="dashicons dashicons-admin-settings" aria-hidden="true" title="<?php esc_html_e( 'This is a not a WordPress core event.', 'performance-monitor' ); ?>"></span>
							<span class="screen-reader-text"><?php esc_html_e( 'This is a not a WordPress core event.', 'performance-monitor' ); ?></span>
						<# } #>
						{{cron.hook}}
					</td>
					<td>{{cron.args}}</td>
					<td>
						<div>{{cron.next_run}}</div>
						<div class="qtpm-cron-countdown" data-time="{{cron.next_run_local}}"></div>
					</td>
					<td>
						<# if ( 'None' === cron.callback) { #>
							<span class="qtpm-cron-none"><?php esc_html_e( 'None', 'performance-monitor' ); ?></span>
						<# } else { #>
							<code class="qtpm-cron-callback">{{cron.callback}}</code>
						<# } #>
					</td>
					<td>{{cron.schedule}}</td>
				</tr>
			<# }); #>
		</tbody>
	</table>
</script>
