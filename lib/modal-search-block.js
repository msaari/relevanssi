(function (blocks, blockEditor, components, element, i18n) {
	'use strict';

	var el = element.createElement;
	var InspectorControls = blockEditor.InspectorControls;
	var useBlockProps = blockEditor.useBlockProps || function (props) { return props || {}; };
	var PanelBody = components.PanelBody;
	var TextControl = components.TextControl;
	var ToggleControl = components.ToggleControl;
	var __ = i18n.__;

	blocks.registerBlockType('relevanssi/modal-search', {
		title: __('Relevanssi Modal Search', 'relevanssi'),
		description: __('Open an accessible search form in a lightweight modal.', 'relevanssi'),
		icon: 'search',
		category: 'widgets',
		attributes: {
			triggerLabel: { type: 'string', default: __('Search', 'relevanssi') },
			modalLabel: { type: 'string', default: __('Search this site', 'relevanssi') },
			iconOnly: { type: 'boolean', default: false },
			postTypes: { type: 'string', default: '' }
		},
		edit: function (props) {
			var attributes = props.attributes;
			var blockProps = useBlockProps({ className: 'relevanssi-modal-search-editor' });

			return el(
				'div',
				blockProps,
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __('Modal search settings', 'relevanssi') },
						el(TextControl, {
							label: __('Button label', 'relevanssi'),
							value: attributes.triggerLabel,
							onChange: function (value) { props.setAttributes({ triggerLabel: value }); }
						}),
						el(TextControl, {
							label: __('Dialog heading', 'relevanssi'),
							value: attributes.modalLabel,
							onChange: function (value) { props.setAttributes({ modalLabel: value }); }
						}),
						el(ToggleControl, {
							label: __('Show icon only', 'relevanssi'),
							checked: attributes.iconOnly,
							onChange: function (value) { props.setAttributes({ iconOnly: value }); }
						}),
						el(TextControl, {
							label: __('Limit to post types', 'relevanssi'),
							help: __('Optional comma-separated post type names.', 'relevanssi'),
							value: attributes.postTypes,
							onChange: function (value) { props.setAttributes({ postTypes: value }); }
						})
					)
				),
				el(
					'button',
					{ type: 'button', className: 'wp-element-button', disabled: true },
					attributes.iconOnly ? '\uD83D\uDD0D' : attributes.triggerLabel
				)
			);
		},
		save: function () {
			return null;
		}
	});
})(window.wp.blocks, window.wp.blockEditor || window.wp.editor, window.wp.components, window.wp.element, window.wp.i18n);
