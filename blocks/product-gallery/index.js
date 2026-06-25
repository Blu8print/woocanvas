( function ( blocks, element, blockEditor, serverSideRender, components ) {
	var el = element.createElement;
	var Fragment = element.Fragment;
	var useBlockProps = blockEditor.useBlockProps;
	var InspectorControls = blockEditor.InspectorControls;
	var ServerSideRender = serverSideRender;
	var PanelBody = components.PanelBody;
	var SelectControl = components.SelectControl;
	var TextControl = components.TextControl;

	blocks.registerBlockType( 'woocanvas/product-gallery', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Gallery Style', initialOpen: true },
						el( TextControl, {
							label: 'Max Width',
							value: attributes.maxWidth || '',
							placeholder: 'e.g. 500px or 50%',
							onChange: function ( val ) {
								setAttributes( { maxWidth: val } );
							},
						} ),
						el( SelectControl, {
							label: 'Aspect Ratio',
							value: attributes.aspectRatio || 'natural',
							options: [
								{ label: 'Natural', value: 'natural' },
								{ label: '1:1 — Square', value: '1-1' },
								{ label: '4:3 — Landscape', value: '4-3' },
								{ label: '3:4 — Portrait', value: '3-4' },
								{ label: '16:9 — Wide', value: '16-9' },
							],
							onChange: function ( val ) {
								setAttributes( { aspectRatio: val } );
							},
						} ),
						el( SelectControl, {
							label: 'Thumbnails',
							value: attributes.thumbnailPosition || 'below',
							options: [
								{ label: 'Below', value: 'below' },
								{ label: 'Top', value: 'top' },
								{ label: 'Left', value: 'left' },
								{ label: 'Right', value: 'right' },
								{ label: 'Hidden', value: 'hidden' },
							],
							onChange: function ( val ) {
								setAttributes( { thumbnailPosition: val } );
							},
						} )
					)
				),
				el(
					'div',
					blockProps,
					el( ServerSideRender, {
						block: 'woocanvas/product-gallery',
						attributes: props.attributes,
						httpMethod: 'POST',
					} )
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.serverSideRender, window.wp.components );
