( function ( blocks, element, blockEditor, serverSideRender, components ) {
	var el = element.createElement;
	var Fragment = element.Fragment;
	var useBlockProps = blockEditor.useBlockProps;
	var InspectorControls = blockEditor.InspectorControls;
	var ServerSideRender = serverSideRender;
	var PanelBody = components.PanelBody;
	var SelectControl = components.SelectControl;
	var BaseControl = components.BaseControl;
	var ColorPalette = components.ColorPalette;
	var ToggleControl = components.ToggleControl;

	blocks.registerBlockType( 'woocanvas/product-tabs', {
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
						{ title: 'Tab Style', initialOpen: true },
						el( SelectControl, {
							label: 'Style',
							value: attributes.tabStyle || 'default',
							options: [
								{ label: 'Default', value: 'default' },
								{ label: 'Underline', value: 'underline' },
							],
							onChange: function ( val ) {
								setAttributes( { tabStyle: val } );
							},
						} ),
						el( SelectControl, {
							label: 'Alignment',
							value: attributes.tabAlignment || 'left',
							options: [
								{ label: 'Left', value: 'left' },
								{ label: 'Center', value: 'center' },
								{ label: 'Right', value: 'right' },
							],
							onChange: function ( val ) {
								setAttributes( { tabAlignment: val } );
							},
						} ),
						el(
							BaseControl,
							{ label: 'Active Tab Color', id: 'woocanvas-tabs-active-color' },
							el( ColorPalette, {
								value: attributes.activeColor || null,
								onChange: function ( val ) {
									setAttributes( { activeColor: val || '' } );
								},
							} )
						),
						el( ToggleControl, {
							label: 'Show Reviews tab',
							checked: attributes.showReviews !== false,
							onChange: function ( val ) {
								setAttributes( { showReviews: val } );
							},
						} )
					)
				),
				el(
					'div',
					blockProps,
					el( ServerSideRender, {
						block: 'woocanvas/product-tabs',
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
