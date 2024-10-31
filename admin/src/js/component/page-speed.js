import '../lib/chart.js';
import { makeRestRequest } from './util.js';

const $             = jQuery;
const metricFilters = {};
let device          = null;

/**
 * Handles the API response from the server.
 *
 * @param {Object} response - The response object from the server.
 *
 * @since 1.0.0
 */
const handleAPIResponse = ( response ) => {
	if ( ! response.success || ! response.data || null === response.data ) {
		return;
	}

	showPageSpeedData( response.data.mobile_data );
	showPageSpeedData( response.data.desktop_data );
};

/**
 * Displays PageSpeed data based on the provided data.
 *
 * @param {Object} data - The PageSpeed data to display.
 * @since 1.0.0
 */
const showPageSpeedData = ( data ) => {
	device = data.lighthouseResult.configSettings.formFactor;

	$( `#qtpm-pagespeed-${ device }-container` ).html(
		wp.template( 'qtpm-pagespeed-device' )( device ).replace( /\s+/g, ' ' ),
	);

	// Diagnostics types to be matched with audits
	const diagnostics = {
		'font-display': { type: [ 'FCP', 'LCP' ] },
		'critical-request-chains': { type: [ 'FCP', 'LCP' ] },
		'largest-contentful-paint-element': { type: [ 'LCP' ] },
		'layout-shift-elements': { type: [ 'CLS' ] },
		'long-tasks': { type: [ 'TBT' ] },
		'render-blocking-resources': { type: [ 'FCP', 'LCP' ] },
		'unused-css-rules': { type: [ 'FCP', 'LCP' ] },
		'unminified-css': { type: [ 'FCP', 'LCP' ] },
		'unminified-javascript': { type: [ 'FCP', 'LCP' ] },
		'unused-javascript': { type: [ 'LCP' ] },
		'uses-text-compression': { type: [ 'FCP', 'LCP' ] },
		'uses-rel-preconnect': { type: [ 'FCP', 'LCP' ] },
		'server-response-time': { type: [ 'FCP', 'LCP' ] },
		redirects: { type: [ 'FCP', 'LCP' ] },
		'uses-rel-preload': { type: [ 'FCP', 'LCP' ] },
		'efficient-animated-content': { type: [ 'LCP' ] },
		'duplicated-javascript': { type: [ 'TBT' ] },
		'legacy-javascript': { type: [ 'TBT' ] },
		'total-byte-weight': { type: [ 'LCP' ] },
		'dom-size': { type: [ 'TBT' ] },
		'bootup-time': { type: [ 'TBT' ] },
		'mainthread-work-breakdown': { type: [ 'TBT' ] },
		'third-party-summary': { type: [ 'TBT' ] },
		'third-party-facades': { type: [ 'TBT' ] },
		'non-composited-animations': { type: [ 'CLS' ] },
		'unsized-images': { type: [ 'CLS' ] },
		viewport: { type: [ 'TBT' ] },
	};

	// Process the audits and add to metricFilters
	Object.entries( data.lighthouseResult.audits ).forEach( ( [ auditKey ] ) => {
		const auditType = diagnostics[ auditKey ]?.type;
		if ( auditType ) {
			auditType.forEach( ( type ) => {
				if ( ! metricFilters[ `qtpm-metric-${ type.toLowerCase() }-${ device }` ] ) {
					metricFilters[ `qtpm-metric-${ type.toLowerCase() }-${ device }` ] = [];
				}
				metricFilters[ `qtpm-metric-${ type.toLowerCase() }-${ device }` ].push( auditKey );
			} );
		}
	} );

	const auditFields = [
		'first-contentful-paint',
		'largest-contentful-paint',
		'total-blocking-time',
		'cumulative-layout-shift',
		'speed-index',
	];

	const qtpmMetrics = auditFields.map(
		( field ) => data.lighthouseResult.audits[ field ] || false,
	);

	const filteredAudits = Object.entries( data.lighthouseResult.audits )
		.filter( ( [ objKey ] ) => ! auditFields.includes( objKey ) )
		.reduce( ( obj, [ objKey, value ] ) => ( { ...obj, [ objKey ]: value } ), {} );

	showMetrics( qtpmMetrics, data.lighthouseResult.categories.performance.score );
	showDiagnostics( filteredAudits );
	$( '#run-pagespeed-api' ).removeAttr( 'disabled' );
};

/**
 * Displays metrics based on the provided data.
 *
 * @param {Array}  metrics          - The metrics data to display.
 * @param {number} performanceScore
 * @since 1.0.0
 */
const showMetrics = ( metrics, performanceScore ) => {
	const $metricsContainer = $( `#qtpm-metrics-container-${ device }` ).empty();
	const scores            = [];

	metrics.filter( Boolean ).forEach( ( metric ) => {
		metric.device = device;

		$metricsContainer.append(
			wp.template( 'qtpm-pagespeed-metrics' )( metric ).replace( /\s+/g, ' ' ),
		);
		scores[ metric.id ] = metric.score;
	} );
	showGauge( scores, performanceScore );
};

/**
 * Displays gauge chart based on the provided scores.
 *
 * @param {Object} scores           - The scores data for gauge chart.
 * @param {number} performanceScore
 * @since 1.0.0
 */
const showGauge = ( scores, performanceScore ) => {
	const weights = {
		'first-contentful-paint': 10,
		'speed-index': 25,
		'largest-contentful-paint': 10,
		'total-blocking-time': 25,
		'cumulative-layout-shift': 30,
	};

	const gaugeData          = {};
	const auditPerformances  = {};
	const overallPerformance = performanceScore * 100;

	for ( const audit in weights ) {
		const weight      = weights[ audit ];
		const score       = scores[ audit ];
		const performance = weight * score;

		auditPerformances[ audit ] = performance;
	}

	gaugeData.auditPerformances  = auditPerformances;
	gaugeData.overallPerformance = overallPerformance.toFixed( 2 );

	$( `#qtpm-metrics-gauge-container-${ device }` ).append(
		wp.template( 'qtpm-pagespeed-gauge' )( gaugeData ).replace( /\s+/g, ' ' ),
	);

	const ctxLabels   = [];
	const ctxData     = [];
	const ctxBGColor  = [
		'rgb(255, 99, 132)',
		'rgb(255, 205, 86)',
		'rgb(54, 162, 235)',
		'rgb(255, 205, 86)',
		'rgb(54, 162, 235)',
	];
	const longToShort = {
		'first-contentful-paint': 'FCP',
		'speed-index': 'SI',
		'largest-contentful-paint': 'LCP',
		'total-blocking-time': 'TBT',
		'cumulative-layout-shift': 'CLS',
	};

	Object.keys( gaugeData.auditPerformances ).forEach( ( key ) => {
		ctxLabels.push( longToShort[ key ] );
		ctxData.push( gaugeData.auditPerformances[ key ] );
		ctxBGColor.push();
	} );

	const data = {
		labels: ctxLabels,
		datasets: [
			{
				label: 'Score',
				data: ctxData,
				backgroundColor: ctxBGColor,
			},
		],
	};

	const config = {
		type: 'doughnut',
		data,
		options: {
			plugins: {
				legend: {
					display: false,
				},
			},
			cutout: '75%',
		},
	};

	new Chart( document.getElementById( `performanceGauge-${ device }` ), config );
};

/**
 * Displays diagnostics based on the provided data.
 *
 * @param {Object} diagnostics - The diagnostics data to display.
 * @since 1.0.0
 */
const showDiagnostics = ( diagnostics ) => {
	const $diagnosticsContainer = $(
		`#qtpm-diagnostics-container-${ device }`,
	).empty();
	const $auditPassedContainer = $(
		`#qtpm-audit-passed-container-${ device }`,
	).empty();

	const diagnosticArray = Object.values( diagnostics );

	const nullScoreDiagnostics = diagnosticArray.filter(
		( diagnostic ) => null === diagnostic.score,
	);

	const scoreDiagnostics = diagnosticArray.filter(
		( diagnostic ) => null !== diagnostic.score,
	);

	scoreDiagnostics.sort(
		( a, b ) =>
			( a.score ? a.score * 100 : -1 ) - ( b.score ? b.score * 100 : -1 ),
	);

	scoreDiagnostics.concat( nullScoreDiagnostics ).forEach( ( diagnostic ) => {
		diagnostic.device = device;
		const html        = wp
			.template( 'qtpm-pagespeed-diagnostics' )( diagnostic )
			.replace( /\s+/g, ' ' );

		if (
			( 1 === diagnostic.score &&
				'informative' !== diagnostic.scoreDisplayMode ) ||
			'notApplicable' === diagnostic.scoreDisplayMode
		) {
			$auditPassedContainer.append( html );
		} else {
			$diagnosticsContainer.append( html );
		}
	} );
};

/**
 * Initializes the PageSpeed data retrieval process.
 *
 * @since 1.0.0
 */
const pageSpeedInit = () => {
	makeRestRequest( 'pagespeed_api_data?cached=1' ).then( handleAPIResponse )
		.catch( ( xhr, status, error ) => {
			console.error( 'error', error );
		} );

	const $qtpmAPIRunBtn = $( '#run-pagespeed-api' );

	// Accordion handling in various areas.
	$( document ).on( 'click', '.qtpm-audit-accordion-trigger', function() {
		const $this      = $( this );
		const isExpanded = 'true' === $this.attr( 'aria-expanded' );
		const $target    = $( '#' + $this.attr( 'aria-controls' ) );

		$this.attr( 'aria-expanded', ! isExpanded );
		$target.attr( 'hidden', isExpanded );
	} );

	$( document ).on( 'click', '.qtpm-autid-expand-all', function() {
		const $this             = $( this );
		const isExpanded        = 'true' === $this.attr( 'aria-expanded' );
		const $target           = $( '#' + $this.attr( 'aria-controls' ) );
		const accordionPanels   = $target.find( '.qtpm-audit-accordion-panel' );
		const accordionTriggers = $target.find( '.qtpm-audit-accordion-trigger' );

		$this.text( ! isExpanded ? qtpmMessageObject.collapse_all : qtpmMessageObject.expand_all );
		$this.attr( 'aria-expanded', ! isExpanded );

		accordionPanels.each( function() {
			const $thisPanel = $( this );
			$thisPanel.attr( 'hidden', isExpanded );
		} );

		accordionTriggers.each( function() {
			const $thisTrigger = $( this );
			$thisTrigger.attr( 'aria-expanded', ! isExpanded );
		} );
	} );

	$qtpmAPIRunBtn.on( 'click', function() {
		$qtpmAPIRunBtn.prop( 'disabled', true );
		$( '.qtpm-metrics-gauge-container, .qtpm-metrics-container, .qtpm-audit-accordion' )
			.html( `<p>${ qtpmMessageObject.loading }</p>` );

		makeRestRequest( 'pagespeed_api_data' )
			.then( handleAPIResponse )
			.catch( ( xhr, status, error ) => {
				console.error( error );
			} )
			.finally( () => {
				$qtpmAPIRunBtn.prop( 'disabled', false );
			} );
	} );

	$( document ).on(
		'change',
		'.qtpm-metricfilter [name=matricfilter]',
		function() {
			const $this          = $( this );
			const metricFilterId = $this.attr( 'id' );
			const ariaControls   = $this.attr( 'aria-controls' );
			const metricList     = metricFilters[ metricFilterId ];

			// Remove 'active' class from all labels and add it to the selected one
			$( `#${ ariaControls } .qtpm-metricfilter label` ).removeClass( 'active' );
			$( `label[for=${ metricFilterId }]` ).addClass( 'active' );

			if ( 'qtpm-metric-all-mobile' === metricFilterId || 'qtpm-metric-all-desktop' === metricFilterId ) {
				$( `#${ ariaControls } .qtpm-element-container` ).show();
				return;
			}

			device = $this.data( 'device' );

			// Hide all metric containers and then show only the ones corresponding to the selected filter
			$( `#${ ariaControls } .qtpm-element-container` ).hide();
			metricList.forEach( ( item ) => {
				$( `#qtpm-${ item }-container-${ device }` ).show();
			} );
		},
	);

	$( document ).on(
		'change',
		'.qtpm-pagespeed-device-tab [name=qtpm_pagespeed_device]',
		function() {
			const $this           = $( this );
			const pagespeedDevice = $this.attr( 'id' );

			// Remove 'active' class from all labels and add it to the selected one
			$( '.qtpm-pagespeed-device-tab label' ).removeClass( 'active' );
			$( `label[for=${ pagespeedDevice }]` ).addClass( 'active' );

			// Hide all data and then show only selected device data.
			$( '.qtpm-pagespeed-device-container' ).hide();
			$( `#${ pagespeedDevice }-container` ).show();
		},
	);
};
export default pageSpeedInit;
