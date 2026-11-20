const settle = () => new Promise( resolve => setTimeout( resolve, 0 ) );

beforeEach( () => {
	jest.resetModules();
	document.body.innerHTML = `<details open><summary>Black Friday Week offer</summary>
		<section class="rv-cw-card"><span class="rv-cw-code">BLACKFRIDAY30</span>
		<button type="button" data-rv-copy data-coupon="BLACKFRIDAY30" data-copied="Copied!" data-copy-error="Select code">
		<span data-rv-copy-label role="status" aria-live="polite">Copy</span></button></section></details>`;
	Object.defineProperty( window, 'isSecureContext', { configurable: true, value: true } );
	Object.defineProperty( navigator, 'clipboard', { configurable: true, value: { writeText: jest.fn().mockResolvedValue() } } );
	// The project's older jsdom lacks the browser Selection API.
	document.createRange = jest.fn().mockReturnValue( { selectNodeContents: jest.fn() } );
	window.getSelection = jest.fn().mockReturnValue( { removeAllRanges: jest.fn(), addRange: jest.fn() } );
} );

test( 'copies the configured coupon without submitting or collapsing the offer', async () => {
	require( '../lib/cyber-week-offer' );
	document.querySelector( 'button' ).click();
	await settle();
	expect( navigator.clipboard.writeText ).toHaveBeenCalledWith( 'BLACKFRIDAY30' );
	expect( document.querySelector( '[role="status"]' ).textContent ).toBe( 'Copied!' );
	expect( document.querySelector( 'details' ).open ).toBe( true );
} );

test( 'copies on HTTP sites and removes the temporary field', async () => {
	Object.defineProperty( window, 'isSecureContext', { configurable: true, value: false } );
	document.execCommand = jest.fn().mockReturnValue( true );
	require( '../lib/cyber-week-offer' );
	const button = document.querySelector( 'button' );
	button.focus();
	button.click();
	await settle();
	expect( navigator.clipboard.writeText ).not.toHaveBeenCalled();
	expect( document.execCommand ).toHaveBeenCalledWith( 'copy' );
	expect( document.querySelector( 'textarea' ) ).toBeNull();
	expect( document.activeElement ).toBe( button );
	expect( document.querySelector( '[role="status"]' ).textContent ).toBe( 'Copied!' );
} );

test( 'failed clipboard access selects the coupon for manual copying', async () => {
	navigator.clipboard.writeText.mockRejectedValue( new Error( 'Denied' ) );
	require( '../lib/cyber-week-offer' );
	document.querySelector( 'button' ).click();
	await settle();
	expect( document.querySelector( '[role="status"]' ).textContent ).toBe( 'Select code' );
	expect( document.createRange().selectNodeContents ).toHaveBeenCalledWith( document.querySelector( '.rv-cw-code' ) );
	expect( window.getSelection().addRange ).toHaveBeenCalledWith( document.createRange() );
} );

test( 'failed legacy copying also cleans up the temporary field', async () => {
	Object.defineProperty( window, 'isSecureContext', { configurable: true, value: false } );
	document.execCommand = jest.fn().mockImplementation( () => { throw new Error( 'Denied' ); } );
	require( '../lib/cyber-week-offer' );
	document.querySelector( 'button' ).click();
	await settle();
	expect( document.querySelector( 'textarea' ) ).toBeNull();
	expect( document.querySelector( '[role="status"]' ).textContent ).toBe( 'Select code' );
} );

test( 'does nothing on screens without the settings offer', () => {
	document.body.innerHTML = '';
	expect( () => require( '../lib/cyber-week-offer' ) ).not.toThrow();
	expect( navigator.clipboard.writeText ).not.toHaveBeenCalled();
} );
