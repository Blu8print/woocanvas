( function ( blocks, element, blockEditor, serverSideRender, components ) {
	var el = element.createElement;
	var Fragment = element.Fragment;
	var useBlockProps = blockEditor.useBlockProps;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelColorSettings = blockEditor.PanelColorSettings;
	var ServerSideRender = serverSideRender;
	var PanelBody = components.PanelBody;
	var SelectControl = components.SelectControl;

	blocks.registerBlockType( 'woocanvas/related-products', {
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
						{ title: 'Grid', initialOpen: true },
						el( SelectControl, {
							label: 'Columns',
							value: attributes.columns || 4,
							options: [
								{ label: '2', value: 2 },
								{ label: '3', value: 3 },
								{ label: '4', value: 4 },
							],
							onChange: function ( val ) {
								setAttributes( { columns: parseInt( val, 10 ) } );
							},
						} )
					),
					el(
						PanelColorSettings,
						{
							title: 'Button Colors',
							initialOpen: false,
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
						block: 'woocanvas/related-products',
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
