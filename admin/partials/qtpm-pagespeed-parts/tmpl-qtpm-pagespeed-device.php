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

$translations = array(
	'performance'  => __( 'Performance', 'performance-monitor' ),
	'metrics'      => __( 'Metrics', 'performance-monitor' ),
	'run_analysis' => __( 'Run PageSpeed Analysis', 'performance-monitor' ),
	'show_audits'  => __( 'Show audits relevant to:', 'performance-monitor' ),
	'expand_all'   => __( 'Expand All', 'performance-monitor' ),
);

$audits = array(
	'diagnostics'   => __( 'Diagnostics', 'performance-monitor' ),
	'audit-passed'  => __( 'Passed Audits', 'performance-monitor' ),
);

$audit_filters = array(
	'all' => __( 'All', 'performance-monitor' ),
	'fcp' => 'FCP',
	'lcp' => 'LCP',
	'tbt' => 'TBT',
	'cls' => 'CLS',
);

$is_first = true;
?>

<script type="text/html" id="tmpl-qtpm-pagespeed-device">
	<div class="qtpm-metrics-gauge">
		<div class="qtpm-metrics-gauge-title"><?php echo esc_html( $translations['performance'] ); ?></div>

		<div class="qtpm-metrics-gauge-container" id="qtpm-metrics-gauge-container-{{data}}">
			<canvas id="performanceGauge-{{data}}"></canvas>
		</div>
	</div>

	<div class="qtpm-metrics">
		<div class="qtpm-metrics-title"><?php echo esc_html( $translations['metrics'] ); ?></div>

		<div class="qtpm-metrics-container" id="qtpm-metrics-container-{{data}}">
			<p><?php echo esc_html( $translations['run_analysis'] ); ?></p>
		</div>
	</div>

	<div class="qtpm-metricfilter">
		<span class="qtpm-metricfilter-text"><?php echo esc_html( $translations['show_audits'] ); ?></span>
		<?php foreach ( $audit_filters as $filter => $label ) : ?>
			<input type="radio" name="matricfilter" id="qtpm-metric-<?php echo esc_attr( $filter ); ?>-{{data}}" aria-controls="qtpm-pagespeed-{{data}}-container" data-device="{{data}}">
			<label for="qtpm-metric-<?php echo esc_attr( $filter ); ?>-{{data}}" class="<?php echo $is_first ? 'active' : ''; ?>"><?php echo esc_html( $label ); ?></label>
			<?php $is_first = false; ?>
		<?php endforeach; ?>
	</div>

	<?php foreach ( $audits as $audit_type => $label ) : ?>
		<div class="qtpm-audit">
			<div class="qtpm-audit-heading">
				<div class="qtpm-audit-title"><?php echo esc_html( $label ); ?></div>

				<button class="button button-primary button-small qtpm-autid-expand-all" aria-expanded="false"
					aria-controls="qtpm-<?php echo esc_attr( $audit_type ); ?>-container-{{data}}" type="button"><?php echo esc_html( $translations['expand_all'] ); ?></button>
			</div>

			<div id="qtpm-<?php echo esc_attr( $audit_type ); ?>-container-{{data}}" class="qtpm-audit-accordion">
				<div class="qtpm-audit-accordion-panel"><?php echo esc_html( $translations['run_analysis'] ); ?></div>
			</div>
		</div>
	<?php endforeach; ?>
</script>
