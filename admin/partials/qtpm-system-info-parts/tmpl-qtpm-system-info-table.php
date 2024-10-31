<?php
/**
 * Provides System Info table view for the plugin
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

<script type="text/html" id="tmpl-qtpm-system-info-table">
	<table>
		<# _.each( data, function( value, key ) {
			key = key.replace( /-/g, ' ' ).replace( /\b\w/g, function(char) { return char.toUpperCase(); } );
		#>
			<tr>
				<th>{{key}}</th>
				<td>
					<# if ( Array.isArray( value ) ) { #>
						<ul>
							<# _.each(value, function(plugin) { #>
								<li>{{plugin}}</li>
							<# }); #>
						</ul>
					<# } else {#>
						{{value}}
					<# } #>
				</td>
			</tr>
		<# }); #>
	</table>
</script>
