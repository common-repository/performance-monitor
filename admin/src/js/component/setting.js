import { debounce, makeRestRequest } from './util.js';

/**
 * Initializes the settings.
 *
 * @since 1.0.0
 */
const settingInit = () => {
	const $ = jQuery;
	// Toggle visibility based on cron frequency
	const $cronTimeInput          = $( '#qtpm-cron-time-input' );
	const $cronDaysInput          = $( '#qtpm-cron-days-input' );
	const $cronMonthDaysInput     = $( '#qtpm-cron-month-days-input' );
	const $pagespeedCronFrequency = $( '#qtpm_cron_frequency' );

	/**
	 * Toggles the visibility of cron input fields based on the selected frequency.
	 */
	const toggleCronInputs = () => {
		$cronTimeInput.toggleClass( 'qtpm-hide', 'none' === $pagespeedCronFrequency.val() );
		$cronDaysInput.toggleClass( 'qtpm-hide', 'weekly' !== $pagespeedCronFrequency.val() );
		$cronMonthDaysInput.toggleClass( 'qtpm-hide', 'monthly' !== $pagespeedCronFrequency.val() );
	};

	$pagespeedCronFrequency.on( 'change', toggleCronInputs );
	toggleCronInputs();

	const handleChange = () => {
		const $spinnerContainer = $( '#qtpm-cron-frequency-fields-container' );
		$spinnerContainer.append( '<span class="spinner is-active"></span>' );

		const $form       = $( '#qtpm-setting form' )[ 0 ];
		const formData    = new FormData( $form );
		const formDataObj = {};

		formData.forEach( ( value, key ) => {
			const $field = $( `[name="${ key }"]`, $form );
			if ( 'hidden' !== $field.attr( 'type' ) || 'option_page' === key ) {
				formDataObj[ key ] = value;
			}
		} );

		makeRestRequest( 'settings', JSON.stringify( formDataObj ), 'POST' )
			.catch( ( error ) => {
				console.error( 'Error updating settings:', error );
			} )
			.finally( () => {
				$spinnerContainer.find( '.spinner' ).remove();
			} );
	};

	$( '#qtpm_cron_frequency, #qtpm_cron_days, #qtpm_cron_month_days, #qtpm_cron_time, #qtpm_delete_on_uninstall' ).on( 'change', debounce( handleChange, 500 ) );
};

export default settingInit;
