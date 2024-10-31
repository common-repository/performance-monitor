import '../lib/chart.js';
import { debounce, makeRestRequest } from './util.js';

/**
 * Setup a line chart using Chart.js library.
 *
 * @param {string}  ajaxAction             - The name of the AJAX action to retrieve chart data.
 * @param {string}  chartTitle             - The title of the chart.
 * @param {string}  canvasId               - The ID of the canvas element to render the chart.
 * @param {string}  canvassMessage         - The selector for the message element for chart don't have data.
 * @param {string}  yAxisLabel             - The label for the Y-axis of the chart.
 * @param {string}  unit                   - The unit of measurement for the Y-axis values.
 * @param {Array}   labelsData             - An array of objects containing information about each dataset label.
 *                                         Each object should have a 'name', 'keys', 'background', and 'border' property.
 * @param {boolean} [isDeviceFilter=false] - Boolean indicating whether a device filter is enabled.
 *
 * @since 1.0.0
 */
const setupChart = (
	ajaxAction,
	chartTitle,
	canvasId,
	canvassMessage,
	yAxisLabel,
	unit,
	labelsData,
	isDeviceFilter = false,
) => {
	const $               = jQuery;
	const charts          = {};
	const $durationSelect = $( '#qtpm-chart-duration' );
	const $customDuration = $( '#qtpm-custom-duration' );
	const $startDateInput = $( '#qtpm-chart-start-date' );
	const $endDateInput   = $( '#qtpm-chart-end-date' );
	const $errorContainer = $( '#qtpm-date-error' );
	const $device         = $( '#qtpm-chart-device' );
	const loadedChart     = {};

	/**
	 * Function to extract chart data based on provided label information.
	 *
	 * @param {Object[]} data   - Array of data objects retrieved via AJAX.
	 * @param {string}   device - The selected device for data extraction.
	 * @param {Object}   label  - Information about the label to extract data.
	 * @return {Array} Extracted data for the specified label and device.
	 *
	 * @since 1.0.0
	 */
	const setupChartData = ( data, device, label ) => {
		return data.map(
			( row ) => {
				if ( 'Performance Score' === yAxisLabel || 'Load Time (Sec)' === yAxisLabel ) {
					return row[ device ][ label?.keys ];
				}
				return row[ label?.keys ];
			},
		);
	};

	/**
	 * Function to update chart data with new labels and dataset values.
	 *
	 * @param {Array}    labels - Array of labels for the X-axis.
	 * @param {Object[]} data   - Array of data objects retrieved via AJAX.
	 * @param {string}   device - The selected device for data extraction.
	 *
	 * @since 1.0.0
	 */
	const updateChartData = ( labels, data, device ) => {
		charts[ canvasId ].data.labels = labels;
		labelsData.forEach( ( label, index ) => {
			charts[ canvasId ].data.datasets[ index ].data = setupChartData( data, device, label );
			charts[ canvasId ].data.datasets[ index ].date = data.map( ( key ) => {
				return key.date;
			} );
		} );
		charts[ canvasId ].update();
	};

	/**
	 * Function to clear chart data.
	 *
	 * @since 1.0.0
	 */
	const clearChartData = () => {
		if ( charts[ canvasId ] ) {
			charts[ canvasId ].data.labels = [];
			labelsData.forEach( ( label, index ) => {
				charts[ canvasId ].data.datasets[ index ].data = [];
			} );
			charts[ canvasId ].update();
		}
	};

	/**
	 * Function to load chart data via AJAX and render the chart.
	 *
	 * @since 1.0.0
	 */
	const loadChartData = () => {
		const selectedDuration = $durationSelect.val();

		let startDate, endDate, xAxisTitle;

		if ( 'custom' === selectedDuration ) {
			$customDuration.show();
			startDate = $startDateInput.val();
			endDate   = $endDateInput.val();

			if ( ! startDate || ! endDate ) {
				$( $errorContainer ).text( qtpmMessageObject.date_range_error );
				return;
			}

			if ( startDate > endDate ) {
				$( $errorContainer ).text( qtpmMessageObject.start_end_error );
				return;
			}
		} else {
			$errorContainer.empty();
			$customDuration.hide();
		}

		$( $errorContainer ).text( '' );
		if ( loadedChart[ selectedDuration ] ) {
			const { labels, data, device, xAxisTitle: title } = loadedChart[ selectedDuration ];
			if ( charts[ canvasId ] && data.length ) {
				$( canvassMessage ).text( '' );
				updateChartData( labels, data, device );
				charts[ canvasId ].options.scales.x.title.text = title;
				charts[ canvasId ].update();
				return;
			}
		}

		makeRestRequest( 'chart_data', {
			type: ajaxAction,
			duration: selectedDuration,
			startDate,
			endDate,
		} ).then( ( response ) => {
			$( canvassMessage ).text( '' );
			if ( ! response || ! response.success || ! response.data ) {
				clearChartData();
				$( canvassMessage ).text(
					response.message,
				);
			}

			const data   = response?.data ?? [];
			const device = $device.val();
			const labels = data.map( ( row ) => row.label );
			xAxisTitle   = response?.xAxisTitle ?? 'Date';

			if ( 'custom' !== $durationSelect.val() && response.success ) {
				loadedChart[ selectedDuration ] = {
					labels,
					data,
					device,
					xAxisTitle,
				};
			}

			if ( charts[ canvasId ] ) {
				updateChartData( labels, data, device );
				charts[ canvasId ].options.scales.x.title.text = xAxisTitle;
				charts[ canvasId ].update();
			} else {
				const ctx          = document.getElementById( canvasId ).getContext( '2d' );
				charts[ canvasId ] = new Chart( ctx, {
					type: 'line',
					data: {
						labels,
						datasets: labelsData.map( ( label ) => ( {
							label: label.name,
							labelHover: label.labelText
								? label.labelText
								: label.name,
							unit,
							data: setupChartData( data, device, label ),
							date: data.map( ( key ) => {
								return key.date;
							} ),
							backgroundColor: label?.background,
							borderColor: label?.border,
							borderWidth: 1,
							pointRadius: 5,
							pointBackgroundColor: label?.border,
							pointBorderColor: '#fff',
							pointHoverRadius: 6,
						} ) ),
					},
					options: {
						responsive: true,
						maintainAspectRatio: false,
						scales: {
							x: {
								title: {
									text: xAxisTitle,
									display: true,
									ticks: {
										font: {
											size: 12,
										},
									},
								},
							},
							y: {
								title: {
									display: true,
									text: yAxisLabel,
									font: {
										size: 12,
									},
								},
							},
						},
						plugins: {
							tooltip: {
								callbacks: {
									title( context ) {
										const index = context[ 0 ].dataIndex;
										if ( 'year' === $durationSelect.val() ) {
											return new Date( context[ 0 ].dataset.date[ index ] ).toLocaleDateString( 'en-US', { month: 'long', year: 'numeric' } );
										}
										return new Date( context[ 0 ].dataset.date[ index ] ).toLocaleDateString();
									},
									label( context ) {
										return `${ context.dataset.labelHover || '' }: ${ context.parsed.y } ${ context.dataset.unit }`;
									},
								},
							},
							legend: {
								display: true,
								title: {
									display: true,
									text: chartTitle,
									font: {
										size: 14,
										weight: 'bold',
									},
									padding: 10,
									color: '#333',
								},
							},
						},
					},
				} );
			}

			if ( isDeviceFilter ) {
				$device.on( 'change', () => {
					updateChartData( labels, data, $device.val() );
				} );
			}
		} ).catch( ( error ) => {
			const message = error.message || error.toString() || 'An unknown error occurred';
			console.error( 'error message:', message );
		} );
	};

	const chartSelectData = localStorage.getItem( 'qtpm-chart-select-data' );

	if ( chartSelectData ) {
		const { duration, startdate, endDate, device } = JSON.parse( chartSelectData );
		if ( duration ) {
			$durationSelect.val( duration );
		}
		if ( startdate ) {
			$startDateInput.val( startdate );
		}
		if ( endDate ) {
			$endDateInput.val( endDate );
		}
		if ( device ) {
			$device.val( device );
		}
	}

	const handleChange = ( e ) => {
		localStorage.setItem( 'qtpm-chart-select-data', JSON.stringify( {
			duration: $durationSelect.val(),
			startdate: $startDateInput.val(),
			endDate: $endDateInput.val(),
			device: $device.val(),
		} ) );

		if ( $device[ 0 ] !== e.target ) {
			loadChartData();
		}
	};

	$durationSelect.add( $startDateInput ).add( $endDateInput ).add( $device ).on( 'change', debounce( handleChange, 500 ) );

	loadChartData();
};
/**
 * Initializes the dashboard by setting up various charts.
 *
 * @since 1.0.0
 */
const dashboardInit = () => {
	const chartData = [
		{
			action: 'lighthouse_score_chart',
			chartTitle: 'Lighthouse Score',
			canvasId: 'qtpm-lh-score-chart-canvas',
			messageSelector: '.qtpm-lh-score-chart-message',
			yAxisLabel: 'Performance Score',
			unit: 'Score',
			labelsData: [
				{ name: 'Performance', keys: 'performance', labelText: 'Performance', background: 'rgba(255, 159, 64, 0.2)', border: 'rgba(255, 159, 64, 1)' },
				{ name: 'Accessibility', keys: 'accessibility', labelText: 'Accessibility', background: 'rgba(54, 162, 235, 0.2)', border: 'rgba(54, 162, 235, 1)' },
				{ name: 'Best Practices', keys: 'best_practices', labelText: 'Best Practices', background: 'rgba(255, 206, 86, 0.2)', border: 'rgba(255, 206, 86, 1)' },
				{ name: 'SEO', keys: 'seo', labelText: 'SEO', background: 'rgba(75, 192, 192, 0.2)', border: 'rgba(75, 192, 192, 1)' },
			],
			isDeviceFilter: true,
		},
		{
			action: 'performance_score_chart',
			chartTitle: 'Performance Score',
			canvasId: 'qtpm-performance-score-chart-canvas',
			messageSelector: '.qtpm-performance-score-chart-message',
			yAxisLabel: 'Performance Score',
			unit: 'score',
			labelsData: [
				{ name: 'First Contentful Paint', keys: 'fcp', labelText: 'FCP', background: 'rgba(75, 192, 192, 0.2)', border: 'rgba(75, 192, 192, 1)' },
				{ name: 'Largest Contentful Paint', keys: 'lcp', labelText: 'LCP', background: 'rgba(153, 102, 255, 0.2)', border: 'rgba(153, 102, 255, 1)' },
				{ name: 'Total Blocking Time', keys: 'tbt', labelText: 'TBT', background: 'rgba(255, 99, 132, 0.2)', border: 'rgba(255, 99, 132, 1)' },
				{ name: 'Speed Index', keys: 'si', labelText: 'SI', background: '#FFB1C1', border: '#FF6384' },
				{ name: 'Cumulative Layout Shift', keys: 'cls', labelText: 'CLS', background: 'rgba(255, 206, 86, 0.2)', border: 'rgba(255, 206, 86, 1)' },
			],
			isDeviceFilter: true,
		},
		{
			action: 'loadtime_insights_chart',
			chartTitle: 'Load Time Insights',
			canvasId: 'qtpm-load-time-chart-canvas',
			messageSelector: '.qtpm-load-time-chart-message',
			yAxisLabel: 'Load Time (Sec)',
			unit: 'Sec',
			labelsData: [
				{ name: 'Load Time', keys: 'load_time', labelText: 'Load Time', background: 'rgba(255, 159, 64, 0.2)', border: 'rgba(255, 159, 64, 1)' },
				{ name: 'First Contentful Paint', keys: 'fcp', labelText: 'FCP', background: 'rgba(75, 192, 192, 0.2)', border: 'rgba(75, 192, 192, 1)' },
				{ name: 'Largest Contentful Paint', keys: 'lcp', labelText: 'LCP', background: 'rgba(153, 102, 255, 0.2)', border: 'rgba(153, 102, 255, 1)' },
				{ name: 'Total Blocking Time', keys: 'tbt', labelText: 'TBT', background: 'rgba(255, 99, 132, 0.2)', border: 'rgba(255, 99, 132, 1)' },
				{ name: 'Speed Index', keys: 'si', labelText: 'SI', background: '#FFB1C1', border: '#FF6384' },
				{ name: 'Cumulative Layout Shift', keys: 'cls', labelText: 'CLS', background: 'rgba(255, 206, 86, 0.2)', border: 'rgba(255, 206, 86, 1)' },
			],
			isDeviceFilter: true,
		},
		{
			action: 'asset_size_chart',
			chartTitle: 'Asset Size',
			canvasId: 'qtpm-asset-size-chart-canvas',
			messageSelector: '.qtpm-asset-size-chart-message',
			yAxisLabel: 'Size (KB)',
			unit: 'KB',
			labelsData: [
				{ name: 'Total Size', keys: 'total_size', background: 'rgba(153, 102, 255, 0.2)', border: 'rgba(153, 102, 255, 1)' },
				{ name: 'CSS Size', keys: 'total_css_size', background: 'rgba(255, 159, 64, 0.2)', border: 'rgba(255, 159, 64, 1)' },
				{ name: 'JS Size', keys: 'total_js_size', background: 'rgba(75, 192, 192, 0.2)', border: 'rgba(75, 192, 192, 1)' },
				{ name: 'Media Size', keys: 'media_total_size', background: '#FFB1C1', border: '#FF6384' },
			],
		},
		{
			action: 'asset_count_chart',
			chartTitle: 'Asset Count',
			canvasId: 'qtpm-asset-count-chart-canvas',
			messageSelector: '.qtpm-asset-count-chart-message',
			yAxisLabel: 'Files',
			unit: 'Files',
			labelsData: [
				{ name: 'Total Assets Count', keys: 'total_asset', background: 'rgba(255, 159, 64, 0.2)', border: 'rgba(255, 159, 64, 1)' },
				{ name: 'CSS', keys: 'css', background: 'rgba(75, 192, 192, 0.2)', border: 'rgba(75, 192, 192, 1)' },
				{ name: 'JS', keys: 'js', background: 'rgba(153, 102, 255, 0.2)', border: 'rgba(153, 102, 255, 1)' },
				{ name: 'Media', keys: 'media', background: '#FFB1C1', border: '#FF6384' },
				{ name: 'Active Plugin', keys: 'active_plugin', background: '#9BD0F5', border: '#36A2EB' },
			],
		},
	];

	chartData.forEach( ( { action, chartTitle, canvasId, messageSelector, yAxisLabel, unit, labelsData, isDeviceFilter = false } ) => {
		setupChart( action, chartTitle, canvasId, messageSelector, yAxisLabel, unit, labelsData, isDeviceFilter );
	} );
};

export default dashboardInit;
