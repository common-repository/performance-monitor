<?php
/**
 * Provides System Info tab view
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

$info_section = array(
	'container' => array(
		'php'          => __( 'PHP Info', 'performance-monitor' ),
		'database'     => __( 'Database Info', 'performance-monitor' ),
		'wordPress'    => __( 'WordPress Info', 'performance-monitor' ),
		'server'       => __( 'Server Info', 'performance-monitor' ),
		'cache'        => __( 'Cache Info', 'performance-monitor' ),
		'wp-constants' => __( 'WordPress Constants', 'performance-monitor' ),
	),
	'table'     => array(
		'plugin' => __( 'Plugin Info', 'performance-monitor' ),
		'cron'   => __( 'Scheduled Events Info', 'performance-monitor' ),
	),
);

ob_start();
?>

<div>
	<button class="button button-primary" type="button" id="qtpm-get-latest-info"><?php esc_html_e( 'Get Latest Information', 'performance-monitor' ); ?></button>

	<?php foreach ( array_chunk( $info_section['container'], 3, true ) as $rows ) : ?>
		<div class="qtpm-system-info-container">
			<?php foreach ( $rows as $key => $value ) : ?>
				<div class="qtpm-system-info-wrapper" id="qtpm-system-info-<?php echo esc_attr( $key ); ?>">
					<div class="qtpm-system-info-title"><?php echo esc_html( $value ); ?></div>
					<div class="qtpm-system-info-table-wrapper"><?php esc_html_e( 'Loading...', 'performance-monitor' ); ?></div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endforeach; ?>

	<?php foreach ( $info_section['table'] as $key => $value ) : ?>
		<div class="qtpm-system-info-wrapper" id="qtpm-system-info-<?php echo esc_attr( $key ); ?>">
			<div class="qtpm-system-info-title"><?php echo esc_html( $value ); ?></div>
			<div class="qtpm-system-info-table-wrapper"><?php esc_html_e( 'Loading...', 'performance-monitor' ); ?></div>
		</div>
	<?php endforeach; ?>
</div>

<?php
echo wp_kses( Util::minify_html( ob_get_clean() ), Util::get_allowed_html() );
