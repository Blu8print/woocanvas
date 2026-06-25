( function ( blocks, element, blockEditor, serverSideRender, components ) {
	var el = element.createElement;
	var Fragment = element.Fragment;
	var useBlockProps = blockEditor.useBlockProps;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelColorSettings = blockEditor.PanelColorSettings;
	var ServerSideRender = serverSideRender;
	var PanelBody = components.PanelBody;
	var SelectControl = components.SelectControl;

	blocks.registerBlockType( 'woocanvas/add-to-cart', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps( { className: 'woocommerce' } );

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Layout', initialOpen: true },
						el( SelectControl, {
							label: 'Orientation',
							value: attributes.layout || 'row',
							options: [
								{ label: 'Side by side', value: 'row' },
								{ label: 'Stacked', value: 'column' },
							],
							onChange: function ( val ) {
								setAttributes( { layout: val } );
							},
						} )
					),
					el(
						PanelColorSettings,
						{
							title: 'Button Colors',
							initialOpen: true,
							colorSettings: [
								{
									value: attributes.buttonColor || '',
									onChange: function ( val ) {
										setAttributes( { buttonColor: val || '' } );
									},
									label: 'Background',
								},
								{
									value: attributes.buttonTextColor || '',
									onChange: function ( val ) {
										setAttributes( { buttonTextColor: val || '' } );
									},
									label: 'Text',
								},
							],
						}
					)
				),
				el(
					'div',
					blockProps,
					el( ServerSideRender, {
						block: 'woocanvas/add-to-cart',
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
