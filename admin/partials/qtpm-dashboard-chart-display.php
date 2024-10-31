<?php
/**
 * Proivde  charts view in plugin
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

$options = array(
	'week'   => __( 'Week', 'performance-monitor' ),
	'month'  => __( 'Month', 'performance-monitor' ),
	'year'   => __( 'Year', 'performance-monitor' ),
	'custom' => __( 'Custom', 'performance-monitor' ),
);

$chart_names = array(
	'lh-score',
	'performance-score',
	'load-time',
	'asset-size',
	'asset-count',
);


ob_start();
?>

<div class="qtpm-chart-select-duration">
	<label>
		<?php esc_html_e( 'Select Device : ', 'performance-monitor' ); ?>
		<select id="qtpm-chart-device">
			<option value="desktop_data"><?php esc_html_e( 'Desktop', 'performance-monitor' ); ?></option>
			<option value="mobile_data"><?php esc_html_e( 'Mobile', 'performance-monitor' ); ?></option>
		</select>
	</label>

	<label>
		<?php esc_html_e( 'Select the chart duration : ', 'performance-monitor' ); ?>
		<select id="qtpm-chart-duration">
			<?php foreach ( $options as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
	</label>
</div>

<div id="qtpm-custom-duration" style="display: none;">
	<label>
		<?php esc_html_e( 'Start Date:', 'performance-monitor' ); ?>
		<input type="date" id="qtpm-chart-start-date" max="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>" />
	</label>

	<label>
		<?php esc_html_e( 'End Date:', 'performance-monitor' ); ?>
		<input type="date" id="qtpm-chart-end-date" max="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>" />
	</label>
</div>
<div id="qtpm-date-error"></div>

<div class='qtpm-chart-container'>
	<?php foreach ( $chart_names as $chart_name ) : ?>
		<div class="qtpm-<?php echo esc_attr( $chart_name ); ?>-chart-container qtpm-chart">
			<div class="qtpm-<?php echo esc_attr( $chart_name ); ?>-canvas qtpm-chart-canvas">
				<div class="qtpm-<?php echo esc_attr( $chart_name ); ?>-chart-message qtpm-chart-messege"></div>
				<canvas id="qtpm-<?php echo esc_attr( $chart_name ); ?>-chart-canvas"></canvas>
			</div>
		</div>
	<?php endforeach; ?>
</div>

<?php
echo wp_kses( Util::minify_html( ob_get_clean() ), Util::get_allowed_html() );
