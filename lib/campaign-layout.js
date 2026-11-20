( function () {
	'use strict';

	document.querySelectorAll( '.relevanssi-campaign-row' ).forEach( function ( row ) {
		const features = row.querySelector( '#card-license-upsell' );
		const offer = row.querySelector( '#relevanssi-upgrade-offer.relevanssi-campaign-offer' );
		if ( ! features || ! offer ) {
			return;
		}

		let measuredWidth;
		let retainedHeight = 0;
		function retainFeatureHeight() {
			const width = row.getBoundingClientRect().width;
			if ( width !== measuredWidth ) {
				features.style.minHeight = '';
				offer.style.minHeight = '';
				retainedHeight = 0;
				measuredWidth = width;
			}
			if ( ! features.open ) {
				features.style.minHeight = '';
				offer.style.minHeight = '';
				return;
			}
			// Measure an expanded copy so a dismissed offer still reserves the same feature height.
			const measurement = row.cloneNode( true );
			const measuredFeatures = measurement.querySelector( '#card-license-upsell' );
			measuredFeatures.open = true;
			measuredFeatures.style.minHeight = '';
			const measuredOffer = measurement.querySelector( '#relevanssi-upgrade-offer' );
			measuredOffer.open = true;
			measuredOffer.style.minHeight = '';
			measurement.setAttribute( 'aria-hidden', 'true' );
			measurement.setAttribute( 'inert', '' );
			Object.assign( measurement.style, {
				position: 'absolute', left: '-10000px', top: '0', visibility: 'hidden',
				width: row.getBoundingClientRect().width + 'px', maxWidth: 'none',
				height: 'auto', minHeight: '0', boxSizing: 'border-box',
			} );
			measurement.querySelectorAll( '*' ).forEach( function ( element ) {
				element.style.boxSizing = 'border-box';
			} );
			document.body.appendChild( measurement );
			retainedHeight = Math.ceil( Math.max( retainedHeight, measuredFeatures.getBoundingClientRect().height, measuredOffer.getBoundingClientRect().height ) );
			features.style.minHeight = retainedHeight + 'px';
			offer.style.minHeight = offer.open ? retainedHeight + 'px' : '';
			measurement.remove();
		}

		features.addEventListener( 'toggle', retainFeatureHeight );
		offer.addEventListener( 'toggle', retainFeatureHeight );
		window.addEventListener( 'resize', retainFeatureHeight );
		if ( window.ResizeObserver ) {
			let previousWidth;
			new window.ResizeObserver( function () {
				const width = offer.getBoundingClientRect().width;
				if ( width !== previousWidth ) {
					previousWidth = width;
					retainFeatureHeight();
				}
			} ).observe( row );
		}
		retainFeatureHeight();
	} );
}() );
