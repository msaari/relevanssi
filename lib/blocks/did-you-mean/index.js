import { registerBlockType } from '@wordpress/blocks';
import { __, _x } from '@wordpress/i18n';

import metadata from './block.json';
import Edit from './edit';

registerBlockType( metadata.name, {
	...metadata,
	title: _x( 'Did You Mean?', 'block title', 'relevanssi' ),
	description: _x(
		'Displays spelling suggestions for search queries.',
		'block description',
		'relevanssi'
	),
	keywords: [
		_x( 'search', 'block keyword', 'relevanssi' ),
		_x( 'spelling', 'block keyword', 'relevanssi' ),
		_x( 'suggestion', 'block keyword', 'relevanssi' ),
	],
	attributes: {
		...metadata.attributes,
		prefix: {
			...metadata.attributes.prefix,
			default: __( 'Did you mean:', 'relevanssi' ),
		},
	},
	edit: Edit,
	save: () => null,
} );
