import { registerBlockType } from '@wordpress/blocks';
import { Disabled, RangeControl } from '@wordpress/components';
import renderer from 'react-test-renderer';

import metadata from '../block.json';
import Edit from '../edit';

jest.mock( '@wordpress/blocks', () => ( {
	registerBlockType: jest.fn(),
} ) );

jest.mock( '@wordpress/block-editor', () => {
	const React = require( 'react' );

	return {
		InspectorControls: ( { children } ) =>
			React.createElement( 'aside', null, children ),
		useBlockProps: () => ( { className: 'wp-block-relevanssi-preview' } ),
	};
} );

jest.mock( '@wordpress/components', () => {
	const React = require( 'react' );
	const component = ( name, element = 'div' ) => ( { children, ...props } ) =>
		React.createElement(
			element,
			{ ...props, 'data-component': name },
			children
		);

	return {
		Disabled: component( 'disabled' ),
		PanelBody: component( 'panel-body' ),
		RangeControl: component( 'range-control', 'input' ),
		TextControl: component( 'text-control', 'input' ),
	};
} );

describe( 'Did You Mean editor', () => {
	beforeAll( () => {
		require( '../index' );
	} );

	test( 'registers the dynamic block with localized defaults', () => {
		expect( registerBlockType ).toHaveBeenCalledTimes( 1 );
		const [ name, settings ] = registerBlockType.mock.calls[ 0 ];

		expect( name ).toBe( metadata.name );
		expect( settings.edit ).toBe( Edit );
		expect( settings.save() ).toBeNull();
		expect( settings.attributes.prefix.default ).toBe( 'Did you mean:' );
		expect( settings.keywords ).toEqual( [
			'search',
			'spelling',
			'suggestion',
		] );
	} );

	test( 'clamps the threshold and disables the illustrative link', () => {
		const setAttributes = jest.fn();
		const editor = renderer.create(
			<Edit
				attributes={ {
					prefix: 'Did you mean:',
					suffix: '?',
					maxResults: 5,
				} }
				setAttributes={ setAttributes }
			/>
		);
		const rangeControl = editor.root.findByType( RangeControl );
		const disabledPreview = editor.root.findByType( Disabled );

		rangeControl.props.onChange( 75 );

		expect( setAttributes ).toHaveBeenCalledWith( { maxResults: 50 } );
		expect( disabledPreview.findByType( 'a' ).props.href ).toBe(
			'#relevanssi-did-you-mean-preview'
		);
	} );
} );
