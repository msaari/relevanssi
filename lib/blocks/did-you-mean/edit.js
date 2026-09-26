import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	Disabled,
	PanelBody,
	RangeControl,
	TextControl,
} from '@wordpress/components';
import { Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Normalizes the maximum result count for the block attribute.
 *
 * @param {number|string|undefined} value Control value.
 * @return {number} An integer between 0 and 50.
 */
function normalizeMaxResults( value ) {
	const parsedValue = Number.parseInt( value, 10 );

	if ( Number.isNaN( parsedValue ) ) {
		return 0;
	}

	return Math.min( 50, Math.max( 0, parsedValue ) );
}

/**
 * Displays the Did You Mean block controls and illustrative preview.
 *
 * @param {Object}   props               Block editor properties.
 * @param {Object}   props.attributes    Current block attributes.
 * @param {Function} props.setAttributes Updates block attributes.
 * @return {Element} The block editor interface.
 */
export default function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps();
	const prefix = attributes.prefix || '';
	const suffix = attributes.suffix || '';
	const prefixWithSpacing = prefix ? `${ prefix } ` : '';

	return (
		<Fragment>
			<InspectorControls>
				<PanelBody
					title={ __( 'Suggestion Settings', 'relevanssi' ) }
					initialOpen={ true }
				>
					<TextControl
						label={ __( 'Prefix text', 'relevanssi' ) }
						value={ prefix }
						onChange={ ( value ) =>
							setAttributes( { prefix: value } )
						}
					/>
					<TextControl
						label={ __( 'Suffix text', 'relevanssi' ) }
						value={ suffix }
						onChange={ ( value ) =>
							setAttributes( { suffix: value } )
						}
					/>
					<RangeControl
						label={ __( 'Maximum result count', 'relevanssi' ) }
						help={ __(
							'Show suggestions only if the search returns this many results or fewer. Set to 0 to show only when no results are found.',
							'relevanssi'
						) }
						min={ 0 }
						max={ 50 }
						step={ 1 }
						value={ normalizeMaxResults( attributes.maxResults ) }
						onChange={ ( value ) =>
							setAttributes( {
								maxResults: normalizeMaxResults( value ),
							} )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<Disabled>
					<p>
						{ prefixWithSpacing }
						<a href="#relevanssi-did-you-mean-preview">
							{ __( 'example suggestion', 'relevanssi' ) }
						</a>
						{ suffix }
					</p>
				</Disabled>
				<p>
					<small>
						{ __(
							'Note: Real suggestions appear on the frontend search results page using the suggestion data available to Relevanssi.',
							'relevanssi'
						) }
					</small>
				</p>
			</div>
		</Fragment>
	);
}
