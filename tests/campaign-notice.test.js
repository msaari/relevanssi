const settleNotice = () => new Promise( resolve => setTimeout( resolve, 0 ) );
const noticeResponse = () => ( { ok: true, json: async () => ( { success: true } ) } );

beforeEach( () => {
	jest.resetModules();
	document.body.innerHTML = `<div class="wrap"><h1>Dashboard</h1>
		<div id="relevanssi-campaign-notice" data-dismiss-url="/wp-admin/admin-ajax.php" data-dismiss-nonce="notice-nonce" data-dismiss-error="Could not dismiss. Please try again.">
			<p>Black Friday Week offer</p><button type="button" class="notice-dismiss">Dismiss</button>
			<p role="alert" hidden></p>
		</div></div>`;
	window.fetch = jest.fn().mockResolvedValue( noticeResponse() );
} );

test( 'rendering makes no request and uses a native keyboard-accessible button', () => {
	require( '../lib/campaign-notice' );
	expect( window.fetch ).not.toHaveBeenCalled();
	const button = document.querySelector( 'button' );
	expect( button.type ).toBe( 'button' );
	button.focus();
	expect( document.activeElement ).toBe( button );
} );

test( 'dismissal waits for persistence, prevents duplicate requests, and restores focus', async () => {
	let finish;
	window.fetch.mockReturnValueOnce( new Promise( resolve => { finish = resolve; } ) );
	require( '../lib/campaign-notice' );
	const button = document.querySelector( 'button' );
	button.focus();
	button.click();
	button.click();
	expect( document.querySelector( '#relevanssi-campaign-notice' ) ).not.toBeNull();
	expect( window.fetch ).toHaveBeenCalledTimes( 1 );
	const [ url, request ] = window.fetch.mock.calls[ 0 ];
	expect( url ).toBe( '/wp-admin/admin-ajax.php' );
	expect( request.method ).toBe( 'POST' );
	expect( request.credentials ).toBe( 'same-origin' );
	expect( request.body.toString() ).toBe( 'action=relevanssi_dismiss_campaign_notice&security=notice-nonce' );
	finish( noticeResponse() );
	await settleNotice();
	expect( document.querySelector( '#relevanssi-campaign-notice' ) ).toBeNull();
	expect( document.activeElement ).toBe( document.querySelector( 'h1' ) );
} );

test.each( [
	[ 'HTTP failure', () => Promise.resolve( { ok: false } ) ],
	[ 'server failure', () => Promise.resolve( { ok: true, json: async () => ( { success: false } ) } ) ],
	[ 'connection failure', () => Promise.reject( new Error( 'Offline' ) ) ],
	[ 'invalid response', () => Promise.resolve( { ok: true, json: async () => { throw new Error( 'Invalid JSON' ); } } ) ],
] )( '%s keeps the notice, announces the error and allows retry', async ( name, failure ) => {
	window.fetch.mockImplementationOnce( failure );
	require( '../lib/campaign-notice' );
	const button = document.querySelector( 'button' );
	button.click();
	await settleNotice();
	expect( document.querySelector( '#relevanssi-campaign-notice' ) ).not.toBeNull();
	expect( document.querySelector( '[role="alert"]' ).hidden ).toBe( false );
	expect( document.querySelector( '[role="alert"]' ).textContent ).toBe( 'Could not dismiss. Please try again.' );
	expect( button.disabled ).toBe( false );
	expect( document.activeElement ).toBe( button );
	button.click();
	await settleNotice();
	expect( document.querySelector( '#relevanssi-campaign-notice' ) ).toBeNull();
} );

test( 'does nothing on screens without the notice', () => {
	document.body.innerHTML = '';
	require( '../lib/campaign-notice' );
	expect( window.fetch ).not.toHaveBeenCalled();
} );
