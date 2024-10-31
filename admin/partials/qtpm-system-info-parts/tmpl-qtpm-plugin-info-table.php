<?php
/**
 * Provides plugin Info table view
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

<script type="text/html" id="tmpl-qtpm-plugin-info-table">
	<table class="fixed striped table-view-list widefat wp-list-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Name', 'performance-monitor' ); ?></th>
				<th><?php esc_html_e( 'Version', 'performance-monitor' ); ?></th>
				<th><?php esc_html_e( 'Latest Version', 'performance-monitor' ); ?></th>
				<th><?php esc_html_e( 'Last Updated', 'performance-monitor' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<# _.each( data, function( plugin) { #>
				<tr>
					<td>
						{{plugin.Name}}
						<# if ( 'yes' === plugin.BuilderPlugin ) { #>
							<span style="color: red;"><?php esc_html_e( '(Builder Plugin)', 'performance-monitor' ); ?></span>
						<# } #>
					</td>
					<td>{{plugin.Version}}</td>
					<td>
						<# if ( 'yes' === plugin.UpdateAvailable ) { #>
							{{{plugin.UpdateLink}}}
						<# } else { #>
							<?php esc_html_e( 'Already Updated', 'performance-monitor' ); ?>
						<# } #>
					</td>
	
					<td>{{plugin.LastUpdated}}</td>
				</tr>
			<# }); #>
		</tbody>
	</table>
</script>
