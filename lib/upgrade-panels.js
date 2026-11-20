( function () {
	'use strict';

	let leavingPage = false;
	window.addEventListener( 'pagehide', function () {
		leavingPage = true;
	} );
	window.addEventListener( 'pageshow', function () {
		leavingPage = false;
	} );

	document.querySelectorAll( 'details[data-relevanssi-upgrade-panel]' ).forEach( function ( panel ) {
		let savedOpen = panel.open;
		let pending = false;
		const error = document.createElement( 'p' );
		error.className = 'relevanssi-upgrade-panel-error';
		error.setAttribute( 'role', 'alert' );
		error.hidden = true;
		panel.parentNode.insertBefore( error, panel.nextSibling );

		async function saveState() {
			if ( leavingPage || pending || panel.open === savedOpen ) {
				return;
			}
			const requestedOpen = panel.open;
			pending = true;
			error.hidden = true;
			try {
				const response = await window.fetch( panel.dataset.stateUrl, {
					method: 'POST',
					credentials: 'same-origin',
					keepalive: true,
					body: new URLSearchParams( {
						action: 'relevanssi_save_upgrade_panel_state',
						security: panel.dataset.stateNonce,
						panel: panel.dataset.relevanssiUpgradePanel,
						state: requestedOpen ? 'expanded' : 'collapsed',
					} ),
				} );
				if ( ! response.ok || ! ( await response.json() ).success ) {
					throw new Error( 'Preference was not saved.' );
				}
				savedOpen = requestedOpen;
			} catch ( failure ) {
				// Navigation can interrupt the response after WordPress has already saved the choice.
				if ( leavingPage ) {
					return;
				}
				if ( panel.open === requestedOpen ) {
					panel.open = savedOpen;
				}
				error.textContent = panel.dataset.saveError;
				error.hidden = false;
				panel.querySelector( 'summary' ).focus();
			} finally {
				pending = false;
			}
			if ( panel.open !== savedOpen ) {
				saveState();
			}
		}

		panel.addEventListener( 'toggle', saveState );
		window.addEventListener( 'pageshow', function ( event ) {
			if ( event.persisted ) {
				saveState();
			}
		} );
	} );
}() );
