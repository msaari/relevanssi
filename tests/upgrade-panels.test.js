const lifecycleListeners = [];
const settle = () => new Promise( resolve => setTimeout( resolve, 0 ) );
const response = () => ( { ok: true, json: async () => ( { success: true } ) } );

function toggle( panel, open ) {
	panel.open = open;
	panel.dispatchEvent( new Event( 'toggle' ) );
}

beforeEach( () => {
	jest.resetModules();
	const addListener = window.addEventListener.bind( window );
	jest.spyOn( window, 'addEventListener' ).mockImplementation( ( type, listener, options ) => {
		lifecycleListeners.push( [ type, listener, options ] );
		addListener( type, listener, options );
	} );
	window.dispatchEvent( new Event( 'pageshow' ) );
	// This jsdom version needs tabindex to model a browser's focusable summary.
	document.body.innerHTML = [ 'features', 'black-friday-2026' ].map( panel => `
		<details open data-relevanssi-upgrade-panel="${ panel }" data-state-url="/wp-admin/admin-ajax.php" data-state-nonce="panel-nonce" data-save-error="Could not save. Please try again.">
			<summary tabindex="0">${ panel }</summary><p>Panel content</p>
		</details>` ).join( '' );
	window.fetch = jest.fn().mockResolvedValue( response() );
} );

afterEach( () => {
	lifecycleListeners.splice( 0 ).forEach( ( [ type, listener, options ] ) => window.removeEventListener( type, listener, options ) );
	jest.restoreAllMocks();
} );

test( 'makes no request when panels are initially rendered', async () => {
	require( '../lib/upgrade-panels' );
	await settle();
	expect( window.fetch ).not.toHaveBeenCalled();
} );

test( 'collapse and reopen persist independently without removing either panel', async () => {
	require( '../lib/upgrade-panels' );
	const [ features, offer ] = document.querySelectorAll( 'details' );
	toggle( offer, false );
	await settle();
	expect( features.open ).toBe( true );
	expect( offer.open ).toBe( false );
	expect( document.querySelectorAll( 'details' ) ).toHaveLength( 2 );
	let [ url, request ] = window.fetch.mock.calls[ 0 ];
	expect( url ).toBe( '/wp-admin/admin-ajax.php' );
	expect( request.credentials ).toBe( 'same-origin' );
	expect( request.method ).toBe( 'POST' );
	expect( request.body.toString() ).toBe( 'action=relevanssi_save_upgrade_panel_state&security=panel-nonce&panel=black-friday-2026&state=collapsed' );
	toggle( offer, true );
	await settle();
	expect( window.fetch.mock.calls[ 1 ][ 1 ].body.get( 'state' ) ).toBe( 'expanded' );
	toggle( features, false );
	await settle();
	expect( window.fetch.mock.calls[ 2 ][ 1 ].body.get( 'panel' ) ).toBe( 'features' );
	expect( offer.open ).toBe( true );
} );

test.each( [
	[ 'HTTP error', () => Promise.resolve( { ok: false } ) ],
	[ 'save failure', () => Promise.resolve( { ok: true, json: async () => ( { success: false } ) } ) ],
	[ 'network error', () => Promise.reject( new Error( 'Offline' ) ) ],
	[ 'invalid response', () => Promise.resolve( { ok: true, json: async () => { throw new Error( 'Invalid JSON' ); } } ) ],
] )( '%s restores the saved state and supports retry', async ( name, failure ) => {
	window.fetch.mockImplementationOnce( failure );
	require( '../lib/upgrade-panels' );
	const panel = document.querySelector( 'details' );
	toggle( panel, false );
	await settle();
	expect( panel.open ).toBe( true );
	expect( document.querySelector( '[role="alert"]' ).hidden ).toBe( false );
	expect( document.activeElement ).toBe( panel.querySelector( 'summary' ) );
	toggle( panel, false );
	await settle();
	expect( panel.open ).toBe( false );
	expect( document.querySelector( '[role="alert"]' ).hidden ).toBe( true );
} );

test( 'rapid toggles save the latest choice in request order', async () => {
	let finish;
	window.fetch.mockReturnValueOnce( new Promise( resolve => { finish = resolve; } ) );
	require( '../lib/upgrade-panels' );
	const panel = document.querySelector( 'details' );
	toggle( panel, false );
	toggle( panel, true );
	expect( window.fetch ).toHaveBeenCalledTimes( 1 );
	finish( response() );
	await settle();
	expect( window.fetch ).toHaveBeenCalledTimes( 2 );
	expect( window.fetch.mock.calls[ 1 ][ 1 ].body.get( 'state' ) ).toBe( 'expanded' );
	expect( panel.open ).toBe( true );
} );

test( 'a failed reopen keeps the summary and retry message accessible', async () => {
	const panel = document.querySelector( 'details' );
	panel.open = false;
	window.fetch.mockRejectedValueOnce( new Error( 'Offline' ) );
	require( '../lib/upgrade-panels' );
	toggle( panel, true );
	await settle();
	expect( panel.open ).toBe( false );
	expect( panel.nextSibling.getAttribute( 'role' ) ).toBe( 'alert' );
	expect( panel.nextSibling.hidden ).toBe( false );
	expect( document.activeElement ).toBe( panel.querySelector( 'summary' ) );
	toggle( panel, true );
	await settle();
	expect( panel.open ).toBe( true );
} );

test( 'does nothing when no panels are present', () => {
	document.body.innerHTML = '';
	require( '../lib/upgrade-panels' );
	expect( window.fetch ).not.toHaveBeenCalled();
} );


test( 'keeps preference saves alive while changing settings tabs', async () => {
	require( '../lib/upgrade-panels' );
	toggle( document.querySelector( 'details' ), false );
	await settle();
	expect( window.fetch.mock.calls[ 0 ][ 1 ].keepalive ).toBe( true );
} );

test( 'an interrupted response during navigation does not show an error or reopen the panel', async () => {
	let rejectSave;
	window.fetch.mockReturnValueOnce( new Promise( ( resolve, reject ) => { rejectSave = reject; } ) );
	require( '../lib/upgrade-panels' );
	const panel = document.querySelector( 'details' );
	toggle( panel, false );
	window.dispatchEvent( new Event( 'pagehide' ) );
	rejectSave( new TypeError( 'Network request interrupted by navigation' ) );
	await settle();
	expect( panel.open ).toBe( false );
	expect( panel.nextSibling.hidden ).toBe( true );
	expect( document.activeElement ).not.toBe( panel.querySelector( 'summary' ) );
	expect( window.fetch ).toHaveBeenCalledTimes( 1 );
} );

test( 'returning from the back-forward cache restores normal failure reporting and retry', async () => {
	require( '../lib/upgrade-panels' );
	window.dispatchEvent( new Event( 'pagehide' ) );
	window.dispatchEvent( new Event( 'pageshow' ) );
	window.fetch.mockRejectedValueOnce( new Error( 'Offline' ) );
	const panel = document.querySelector( 'details' );
	toggle( panel, false );
	await settle();
	expect( panel.open ).toBe( true );
	expect( panel.nextSibling.hidden ).toBe( false );
	toggle( panel, false );
	await settle();
	expect( panel.open ).toBe( false );
} );


test( 'restoring an interrupted page reconciles the chosen state with WordPress', async () => {
	let rejectSave;
	window.fetch.mockReturnValueOnce( new Promise( ( resolve, reject ) => { rejectSave = reject; } ) );
	require( '../lib/upgrade-panels' );
	const panel = document.querySelector( 'details' );
	toggle( panel, false );
	window.dispatchEvent( new Event( 'pagehide' ) );
	rejectSave( new TypeError( 'Navigation interrupted the response' ) );
	await settle();
	const restored = new Event( 'pageshow' );
	Object.defineProperty( restored, 'persisted', { value: true } );
	window.dispatchEvent( restored );
	await settle();
	expect( window.fetch ).toHaveBeenCalledTimes( 2 );
	expect( window.fetch.mock.calls[ 1 ][ 1 ].body.get( 'state' ) ).toBe( 'collapsed' );
	expect( panel.open ).toBe( false );
	expect( panel.nextSibling.hidden ).toBe( true );
} );
