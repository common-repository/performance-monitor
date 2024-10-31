<?php
/**
 * Provides Tab view for the plugin
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

<div class="wrap">
	<h2><?php echo esc_html( apply_filters( 'qtpm_admin_page_title', 'Plugin Title' ) ); ?></h2>

	<h2 class="nav-tab-wrapper">
		<?php
		foreach ( $qtpm_tabs as $qtpm_tab => $tab_data ) {
			echo '<button class="nav-tab" data-id="#qtpm-' . esc_attr( $qtpm_tab ) . '">' . esc_html( ucfirst( $tab_data['tab_name'] ) ) . '</button>';
		}
		?>
	</h2>

	<?php foreach ( $qtpm_tabs as $qtpm_tab => $tab_data ) : ?>
		<div id="qtpm-<?php echo esc_attr( $qtpm_tab ); ?>" class="qtpm-tab-content">
			<form action="options.php" method="post">
				<?php settings_fields( 'qtpm_' . $qtpm_tab . '_settings' ); ?>
				<?php do_settings_sections( 'qtpm_' . $qtpm_tab . '_settings' ); ?>
			</form>
		</div>
	<?php endforeach; ?>
</div>
