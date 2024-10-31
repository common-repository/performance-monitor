<?php
/**
 * Provides setting tab view for the plugin
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
	'none'    => __( 'None', 'performance-monitor' ),
	'daily'   => __( 'Daily', 'performance-monitor' ),
	'weekly'  => __( 'Weekly', 'performance-monitor' ),
	'monthly' => __( 'Monthly', 'performance-monitor' ),
);

$days = array(
	'sunday'    => __( 'Sunday', 'performance-monitor' ),
	'monday'    => __( 'Monday', 'performance-monitor' ),
	'tuesday'   => __( 'Tuesday', 'performance-monitor' ),
	'wednesday' => __( 'Wednesday', 'performance-monitor' ),
	'thursday'  => __( 'Thursday', 'performance-monitor' ),
	'friday'    => __( 'Friday', 'performance-monitor' ),
	'saturday'  => __( 'Saturday', 'performance-monitor' ),
);


ob_start();
?>

<div class="qtpm-setting">
	<div id="qtpm-cron-frequency-fields-container">
		<div id="qtpm-cron-frequency-input">
			<select id="qtpm_cron_frequency" name="qtpm_setting_settings[cron_frequency]">
				<?php
				foreach ( $options as $value => $label ) {
					printf(
						'<option value="%s" %s>%s</option>',
						esc_attr( $value ),
						selected( $selected_value, $value, false ),
						esc_html( $label )
					);
				}
				?>
			</select>
		</div>

		<div id="qtpm-cron-days-input">
			<select id="qtpm_cron_days" name="qtpm_setting_settings[cron_day]">
				<?php
				foreach ( $days as $value => $label ) {
					printf(
						'<option value="%s" %s>%s</option>',
						esc_attr( $value ),
						selected( $selected_day, $value, false ),
						esc_html( $label )
					);
				}
				?>
			</select>
		</div>

		<div id="qtpm-cron-month-days-input">
			<select id="qtpm_cron_month_days" name="qtpm_setting_settings[cron_month_day]">
				<?php for ( $i = 1; $i <= 28; $i++ ) : ?>
					<option value="<?php echo esc_attr( $i ); ?>" <?php selected( $selected_month_day, $i ); ?>>
						<?php echo esc_html( $i ); ?>
					</option>
				<?php endfor; ?>
			</select>
		</div>

		<div id="qtpm-cron-time-input" class="<?php echo 'none' === $selected_value ? 'qtpm-hide' : ''; ?>">
			<input type="time" id="qtpm_cron_time" name="qtpm_setting_settings[cron_time]" value="<?php echo esc_attr( $time_value ); ?>">
		</div>
	</div>
	<p><?php esc_html_e( 'How frequently do you want to monitor your website performance?', 'performance-monitor' ); ?></p>
</div>

<?php
echo wp_kses( Util::minify_html( ob_get_clean() ), Util::get_allowed_html() );
