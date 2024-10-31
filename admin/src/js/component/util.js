const $ = jQuery;

/**
 * A utility function to display progress bar.
 *
 * @param {number} progress       - The progress percentage.
 * @param {number} [speed=100]    - The speed of progress animation.
 * @param {jQuery} [$progress]    - jQuery object representing the progress element.
 * @param {jQuery} [$progressBar] - jQuery object representing the progress bar element.
 *
 * @since 1.0.0
 */
export const progressBar = (
	progress,
	speed = 100,
	$progress = jQuery( '.qtpm-progress' ),
	$progressBar = jQuery( '.qtpm-progress_bar' ),
) => {
	let i = parseInt( $progress.data( 'progress' ), 0 );
	$progress.data( 'progress', parseInt( progress, 0 ).toString() );
	const count = setInterval( () => {
		if ( i <= progress ) {
			const iStr = i.toString();
			$progress.css( { width: iStr + '%' } );
			$progressBar.find( '.qtpm-item_value' ).html( iStr + '%' );

			if ( 100 === i ) {
				setTimeout( () => {
					$progressBar.fadeOut();
					setTimeout( () => {
						$progress.css( { width: '0%' } );
						$progressBar.find( '.qtpm-item_value' ).html( '0%' );
					}, 200 );
				}, 100 );
			}
		} else {
			clearInterval( count );
		}
		i++;
	}, speed );
};

/**
 * Makes a REST request.
 *
 * @param {string} endpoint The REST API endpoint to send the request to.
 * @param {Object} data     The data to send with the request.
 * @param {string} method   The HTTP method to use for the request. Default is 'GET'.
 * @return {Promise}        A promise that resolves with the response or rejects with an error.
 *
 * @since 1.0.0
 */
export const makeRestRequest = ( endpoint, data = {}, method = 'GET' ) => {
	return new Promise( ( resolve, reject ) => {
		$.ajax( {
			url: `${ qtpmRestSettings.url }/${ endpoint }`,
			method,
			beforeSend( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', qtpmRestSettings.nonce );
			},
			data,
			contentType: 'application/json',
			success: resolve,
			error: reject,
		} );
	} );
};

/**
 * Converts a string to camel case.
 *
 * @param {string} str - The string to convert.
 * @return {string}    - The camel case version of the input string.
 *
 * @since 1.0.0
 */
export const convertToHypanCase = ( str ) => {
	return str.replace( /_/g, '-' ).toLowerCase();
};

export const debounce = ( func, wait ) => {
	let timeout;
	return ( ...args ) => {
		clearTimeout( timeout );
		timeout = setTimeout( () => func.apply( this, args ), wait );
	};
};
