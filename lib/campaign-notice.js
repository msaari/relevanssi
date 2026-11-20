( function () {
	'use strict';

	const notice = document.getElementById( 'relevanssi-campaign-notice' );
	if ( ! notice ) {
		return;
	}
	const button = notice.querySelector( '.notice-dismiss' );
	const error = notice.querySelector( '[role="alert"]' );
	let pending = false;

	button.addEventListener( 'click', async function () {
		if ( pending ) {
			return;
		}
		pending = true;
		button.disabled = true;
		error.hidden = true;
		try {
			const response = await window.fetch( notice.dataset.dismissUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: new URLSearchParams( {
					action: 'relevanssi_dismiss_campaign_notice',
					security: notice.dataset.dismissNonce,
				} ),
			} );
			if ( ! response.ok || ! ( await response.json() ).success ) {
				throw new Error( 'Dismissal was not saved.' );
			}
			const heading = document.querySelector( '#wpbody-content h1, .wrap h1' );
			if ( heading ) {
				heading.setAttribute( 'tabindex', '-1' );
				heading.focus();
			}
			notice.remove();
		} catch ( failure ) {
			error.textContent = notice.dataset.dismissError;
			error.hidden = false;
			button.disabled = false;
			button.focus();
		} finally {
			pending = false;
		}
	} );
}() );
