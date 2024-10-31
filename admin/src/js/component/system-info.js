import { makeRestRequest } from './util.js';

const $              = jQuery;
const sysInfoTMPL    = wp.template( 'qtpm-system-info-table' );
const pluginInfoTMPL = wp.template( 'qtpm-plugin-info-table' );
const cronInfoTMPL   = wp.template( 'qtpm-cron-info-table' );

/**
 * Fetches and updates system information.
 *
 * @param {string}   endpoint
 * @param {string}   target   - The target element selector to update.
 * @param {Function} template - The template function to use for rendering.
 * @return {void}
 *
 * @since 1.0.0
 */
export const fetchAndUpdateInfo = ( endpoint, target, template ) => {
	makeRestRequest( endpoint ).then( ( response ) => {
		if ( response.success && response.data ) {
			$( target )
				.empty()
				.append( template( response.data ).replace( /\s+/g, ' ' ) );
			updateCountdown();
		}
	} )
		.catch( ( error ) => console.error( 'Error fetching', error ) );
};

/**
 * Calculates the time difference between the target time and the current time.
 *
 * @param {number} targetTime The target time in milliseconds.
 *
 * @return {Object} An object containing the days, hours, minutes, seconds, and distance in milliseconds.
 */
function getTimeDifference( targetTime ) {
	const now      = new Date().getTime();
	const distance = targetTime - now;

	// Calculate days, hours, minutes, and seconds
	const days    = Math.floor( distance / ( 1000 * 60 * 60 * 24 ) );
	const hours   = Math.floor( ( distance % ( 1000 * 60 * 60 * 24 ) ) / ( 1000 * 60 * 60 ) );
	const minutes = Math.floor( ( distance % ( 1000 * 60 * 60 ) ) / ( 1000 * 60 ) );
	const seconds = Math.floor( ( distance % ( 1000 * 60 ) ) / 1000 );

	return { days, hours, minutes, seconds, distance };
}

/**
 * Updates the countdown display for all cron job elements.
 *
 * @return {void}
 */
function updateCountdown() {
	const countdowns = $( document ).find( '.qtpm-cron-countdown' );

	countdowns.each( function() {
		const $this          = $( this );
		const targetTime     = new Date( $this.data( 'time' ) ).getTime();
		const timeDifference = getTimeDifference( targetTime );

		// Check if the countdown is over
		if ( 0 > timeDifference.distance ) {
			$this.html( 'Now' );
		} else {
			let html = '';
			if ( 0 < timeDifference.days ) {
				html += `${ timeDifference.days.toString().padStart( 2, '0' ) } day${ 1 === timeDifference.days ? '' : 's' } `;
				if ( 0 < timeDifference.hours ) {
					html += `${ timeDifference.hours.toString().padStart( 2, '0' ) } hour${ 1 === timeDifference.hours ? '' : 's' } `;
				}
			} else if ( 0 < timeDifference.hours ) {
				html += `${ timeDifference.hours.toString().padStart( 2, '0' ) } hour${ 1 === timeDifference.hours ? '' : 's' } `;
				if ( 0 <= timeDifference.minutes ) {
					html += `${ timeDifference.minutes.toString().padStart( 2, '0' ) } minute${ 1 === timeDifference.minutes ? '' : 's' } `;
				}
			} else {
				if ( 0 < timeDifference.minutes ) {
					html += `${ timeDifference.minutes.toString().padStart( 2, '0' ) } minute${ 1 === timeDifference.minutes ? '' : 's' } `;
				}

				if ( 0 <= timeDifference.seconds ) {
					html += `${ timeDifference.seconds.toString().padStart( 2, '0' ) } second${ 1 >= timeDifference.seconds ? '' : 's' } `;
				}
			}

			$this.html( html );
		}
	} );
}

/**
 * Initializes the system information retrieval process.
 *
 * @since 1.0.0
 */
const systemInfoInit = () => {
	makeRestRequest( 'system_info' ).then( ( response ) => {
		if ( ! response.success || ! response.data || null === response.data ) {
			return;
		}

		const { phpInfo, databaseInfo, serverInfo, wordPressInfo, cacheInfo, wpConstantsInfo, pluginsInfo, cronInfo } = response.data;

		const targets = [
			{ selector: '#qtpm-system-info-php', info: phpInfo, template: sysInfoTMPL },
			{ selector: '#qtpm-system-info-database', info: databaseInfo, template: sysInfoTMPL },
			{ selector: '#qtpm-system-info-wordPress', info: wordPressInfo, template: sysInfoTMPL },
			{ selector: '#qtpm-system-info-server', info: serverInfo, template: sysInfoTMPL },
			{ selector: '#qtpm-system-info-cache', info: cacheInfo, template: sysInfoTMPL },
			{ selector: '#qtpm-system-info-wp-constants', info: wpConstantsInfo, template: sysInfoTMPL },
			{ selector: '#qtpm-system-info-plugin', info: pluginsInfo, template: pluginInfoTMPL },
			{ selector: '#qtpm-system-info-cron', info: cronInfo, template: cronInfoTMPL },
		];

		targets.forEach( ( { selector, info, template } ) => {
			$( selector ).find( '.qtpm-system-info-table-wrapper' ).empty().append( template( info ).replace( /\s+/g, ' ' ) );
		} );
	} ).catch( ( error ) => {
		console.error( 'Error fetching cache data:', error );
	} );

	$( document ).on( 'click', '#qtpm-get-latest-info', function( e ) {
		e.preventDefault();
		$( '#qtpm-system-info-php, #qtpm-system-info-database, #qtpm-system-info-wordPress, #qtpm-system-info-server, #qtpm-system-info-cache, #qtpm-system-info-plugin, #qtpm-system-info-cron' )
			.find( '.qtpm-system-info-table-wrapper' )
			.empty()
			.append( `<p>${ qtpmMessageObject.loading }</p>` );

		fetchAndUpdateInfo( 'latest_info?type=php_info', '#qtpm-system-info-php .qtpm-system-info-table-wrapper', sysInfoTMPL );
		fetchAndUpdateInfo( 'latest_info?type=database_info', '#qtpm-system-info-database .qtpm-system-info-table-wrapper', sysInfoTMPL );
		fetchAndUpdateInfo( 'latest_info?type=wordPress_info', '#qtpm-system-info-wordPress .qtpm-system-info-table-wrapper', sysInfoTMPL );
		fetchAndUpdateInfo( 'latest_info?type=server_info', '#qtpm-system-info-server .qtpm-system-info-table-wrapper', sysInfoTMPL );
		fetchAndUpdateInfo( 'latest_info?type=cache_info', '#qtpm-system-info-cache .qtpm-system-info-table-wrapper', sysInfoTMPL );
		fetchAndUpdateInfo( 'latest_info?type=plugin_info', '#qtpm-system-info-plugin .qtpm-system-info-table-wrapper', pluginInfoTMPL );
		fetchAndUpdateInfo( 'latest_info?type=schedule_cron_detail', '#qtpm-system-info-cron .qtpm-system-info-table-wrapper', cronInfoTMPL );
	} );

	setInterval( () => {
		updateCountdown();
	}, 1000 );
};

export default systemInfoInit;
