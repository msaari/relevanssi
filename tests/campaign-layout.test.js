let offerHeight;
beforeEach( () => {
 jest.resetModules(); offerHeight = 600;
 document.body.innerHTML = '<div class="relevanssi-campaign-row"><div><details id="card-license-upsell" open><summary>Features</summary></details></div><aside><details id="relevanssi-upgrade-offer" class="relevanssi-campaign-offer" open><summary>Offer</summary><p>Offer body</p></details></aside></div>';
 jest.spyOn( HTMLElement.prototype, 'getBoundingClientRect' ).mockImplementation( function () { return { width: 400, height: this.open ? offerHeight : 50 }; } );
} );
afterEach( () => { jest.restoreAllMocks(); } );
test( 'keeps the open feature panel tall when the promotion closes', () => {
 require( '../lib/campaign-layout' );
 const left = document.querySelector( '#card-license-upsell' );
 const right = document.querySelector( '#relevanssi-upgrade-offer' );
 expect( left.style.minHeight ).toBe( '600px' );
 right.open = false; right.dispatchEvent( new Event( 'toggle' ) );
 expect( left.style.minHeight ).toBe( '600px' );
 expect( right.open ).toBe( false );
 offerHeight = 800; window.dispatchEvent( new Event( 'resize' ) );
 expect( left.style.minHeight ).toBe( '800px' );
 left.open = false; left.dispatchEvent( new Event( 'toggle' ) );
 expect( left.style.minHeight ).toBe( '' );
 expect( document.querySelectorAll( '#relevanssi-upgrade-offer' ) ).toHaveLength( 1 );
} );
test( 'does not match heights outside the campaign', () => {
 document.querySelector( '.relevanssi-campaign-row' ).className = '';
 require( '../lib/campaign-layout' );
 expect( document.querySelector( '#card-license-upsell' ).style.minHeight ).toBe( '' );
} );
