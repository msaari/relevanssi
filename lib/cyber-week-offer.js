( function () {
	'use strict';

	function copyWithSelection( coupon, button ) {
		const field = document.createElement( 'textarea' );
		field.value = coupon;
		field.style.position = 'fixed';
		field.style.opacity = '0';
		document.body.appendChild( field );
		try {
			field.select();
			if ( ! document.execCommand( 'copy' ) ) {
				throw new Error( 'Copy failed.' );
			}
		} finally {
			field.remove();
			button.focus();
		}
	}

	document.querySelectorAll( '.rv-cw-card [data-rv-copy]' ).forEach( function ( button ) {
		const label = button.querySelector( '[data-rv-copy-label]' );
		const originalLabel = label.textContent;
		let reset;
		button.addEventListener( 'click', async function () {
			window.clearTimeout( reset );
			try {
				if ( navigator.clipboard && window.isSecureContext ) {
					await navigator.clipboard.writeText( button.dataset.coupon );
				} else {
					copyWithSelection( button.dataset.coupon, button );
				}
				label.textContent = button.dataset.copied;
			} catch ( failure ) {
				label.textContent = button.dataset.copyError;
				const range = document.createRange();
				range.selectNodeContents( button.closest( '.rv-cw-card' ).querySelector( '.rv-cw-code' ) );
				const selection = window.getSelection();
				selection.removeAllRanges();
				selection.addRange( range );
			}
			reset = window.setTimeout( function () {
				label.textContent = originalLabel;
			}, 2200 );
		} );
	} );
}() );
