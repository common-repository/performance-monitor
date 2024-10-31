// import '../css/style.scss';
import {
	convertToHypanCase,
} from './component/util.js';

export const tabLoaded = {};
jQuery( ( $ ) => {
	'use strict';

	const $tabWrapper = $( '.nav-tab-wrapper' );
	const $tabMenus   = $( '#toplevel_page_performance-monitor' );

	/**
	 * Switches to the specified tab by showing its content and initializing it if not already loaded.
	 *
	 * @param {string} tabId - The ID of the tab to switch to.
	 */
	const switchTab = ( tabId ) => {
		$tabWrapper.find( 'button' ).removeClass( 'nav-tab-active' );
		$tabMenus.find( '.wp-submenu-wrap li' ).removeClass( 'current' );

		$tabMenus.find( '.wp-submenu-wrap li a' ).each( function() {
			const $this = $( this );
			if ( $this.attr( 'href' ).includes( tabId ) ) {
				$this.parent().addClass( 'current' );
			}
		} );

		$( `.nav-tab-wrapper button[data-id="${ tabId }"]` ).addClass( 'nav-tab-active' );
		$( '.qtpm-tab-content' ).hide();
		$( tabId ).show();
		sessionStorage.setItem( 'qtpm_active_tab', tabId );
		loadTab( tabId );
	};

	/**
	 * Function to load tab content.
	 * @param {string} tabId - The ID of the tab to switch to.
	 */
	const loadTab = ( tabId ) => {
		const tabName = convertToHypanCase( tabId.replace( '#qtpm-', '' ) );
		if ( ! tabLoaded[ tabName ] ) {
			import( `./component/${ tabName }.js` ).then( ( module ) => module.default() );
			tabLoaded[ tabName ] = true;
		}
	};

	/**
	 * Event listener for tab buttons
	 */
	$tabWrapper.on( 'click', 'button', ( event ) => {
		event.preventDefault();
		const tabId = $( event.target ).data( 'id' );

		if ( tabId.concat( '-tab' ) !== window.location.hash ) {
			switchTab( tabId, $tabWrapper );
			window.location.hash = tabId.replace( '#', '' ).concat( '-tab' );
		}
	} ).on( 'mouseenter focus', 'button', ( event ) => {
		event.preventDefault();
		const tabId = $( event.target ).data( 'id' );
		loadTab( tabId, $tabWrapper );
	} );

	// Event listener for accordion triggers
	$( '.qtpm-accordion' ).on( 'click', '.qtpm-accordion-trigger', function() {
		const $this      = $( this );
		const isExpanded = 'true' === $this.attr( 'aria-expanded' );
		const $target    = $( '#' + $this.attr( 'aria-controls' ) );

		$this.attr( 'aria-expanded', ! isExpanded );
		$target.attr( 'hidden', isExpanded );
	} );

	/**
	 * Event listener for hash changes
	 */
	$( window ).on( 'hashchange', () => {
		const hash = window.location.hash.replace( '-tab', '' );
		if ( hash.startsWith( '#qtpm-' ) ) {
			switchTab( hash );
		}
	} );

	/**
	 * Array of tabs that exist in the page
	 */
	const hasTabs    = [
		'#qtpm-dashboard-tab',
		'#qtpm-system_info-tab',
		'#qtpm-curl-tab',
		'#qtpm-page_speed-tab',
		'#qtpm-setting-tab',
	];
	const defaultTab = hasTabs
		.find( ( hasTab ) => window.location.hash === hasTab )
		?.replace( '-tab', '' );

	if ( defaultTab ) {
		switchTab( defaultTab, $tabWrapper );
	} else {
		const cachedTab = sessionStorage.getItem( 'qtpm_active_tab' );
		if ( cachedTab.startsWith( '#qtpm-' ) ) {
			switchTab( cachedTab );
		} else {
			switchTab( '#qtpm-dashboard' );
		}
	}
} );
