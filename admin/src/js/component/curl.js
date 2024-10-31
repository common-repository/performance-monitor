import { makeRestRequest, progressBar } from './util.js';

const $ = jQuery;
/**
 * Initializes the CURL data retrieval process.
 *
 * @since 1.0.0
 */
const curlInit = () => {
	makeRestRequest( 'curl_data' ).then( ( response ) => {
		$( '#qtpm-curl-display' ).html(
			wp
				.template( 'qtpm-curl-data-display' )( response.data )
				.replace( /\s+/g, ' ' ),
		);
	} ).catch( ( error ) => {
		setTimeout( () => $( '.progress_bar' ).fadeOut(), 100 );
		console.error( error );
	} );

	/**
	 * Initiates the process to retrieve data for latest curl info.
	 */
	$( '#qtpm-get-latest-curl-data' ).on( 'click', () => {
		const $this = $( this );
		$this.prop( 'disabled', true );
		makeRestRequest( 'latest_curl_data' ).then( ( response ) => {
			$this.prop( 'disabled', false );
			$( '#qtpm-curl-display' ).html(
				wp.template( 'qtpm-curl-data-display' )( response.data ).replace( /\s+/g, ' ' ),
			);
		} );
	} );

	/**
	 * Toggles the collapse/expand state of curl data sections.
	 */
	$( document ).on( 'click', '#qtpm-collapse-button, .qtpm-curl-data-header, .qtpm-accordion-header', function() {
		const $this = $( this );
		if ( $this.is( '#qtpm-collapse-button' ) ) {
			const isExpand = 'expand' === $this.data( 'action' );
			$( '.qtpm-curl-data-header' ).each( function() {
				const accordion = $( this );
				const isHidden  = accordion.next( '.qtpm-curl-data-display' ).is( ':hidden' );
				if ( ( isExpand && isHidden ) || ( ! isExpand && ! isHidden ) ) {
					accordion.next( '.qtpm-curl-data-display' ).slideToggle();
					accordion.find( '.qtpm-accordion-button' ).text( isExpand ? '-' : '+' );
				}
			} );
			$this.data( 'action', isExpand ? 'collapse' : 'expand' );
			$this.text( isExpand ? qtpmMessageObject.collapse : qtpmMessageObject.expand );
		} else {
			const buttonText = $this.find( '.qtpm-accordion-button' ).text();
			$this.next( '.qtpm-curl-data-display, .qtpm-resource' ).slideToggle();
			$this.find( '.qtpm-accordion-button' ).text( '+' === buttonText ? '-' : '+' );
		}
	} );

	/**
	 * Initiates the process to retrieve data for all pages and display it.
	 */
	$( document ).on( 'click', '#qtpm-get-all-page-matrix', function() {
		const $getAllPageBtn = $( this );
		const $progressBar   = $( '.qtpm-progress_bar' );
		const $progress      = $( '.qtpm-progress' );
		$getAllPageBtn.prop( 'disabled', true );

		makeRestRequest( 'all_pages_list' ).then( ( response ) => {
			let pageCount = response.data.length;
			$progress.data( 'progress', 0 );
			$progressBar.fadeIn();
			$( '#qtpm-curl-display' ).empty();

			let sequence = Promise.resolve();

			response.data.forEach( ( data ) => {
				sequence = sequence.then( () => {
					const url   = decodeURIComponent( data.url );
					const title = decodeURIComponent( data.title );

					return makeRestRequest( `curl_data?url=${ url }&title=${ title }` ).then( ( curlData ) => {
						if ( null !== curlData ) {
							$( '#qtpm-curl-display' ).append(
								wp.template( 'qtpm-curl-data-display' )( curlData.data ).replace( /\s+/g, ' ' ),
							);

							pageCount--;

							const progress = ( ( response.data.length - pageCount ) / response.data.length ) * 100;
							progressBar( progress, 30, $progress, $progressBar );
						}
					} );
				} );
			} );

			sequence.then( () => {
				$getAllPageBtn.prop( 'disabled', false );
			} ).catch( ( error ) => {
				$getAllPageBtn.prop( 'disabled', false );
				setTimeout( () => $progressBar.fadeOut(), 100 );
				console.error( 'Error fetching data:', error );
			} );
		} ).catch( ( error ) => {
			$getAllPageBtn.prop( 'disabled', false );
			setTimeout( () => $progressBar.fadeOut(), 100 );
			console.error( 'Error fetching data:', error );
		} );
	} );
};

export default curlInit;
