<?php
/**
 * Registers the WooCanvas custom block category and all four product blocks.
 *
 * @package WooCanvas
 */

defined( 'ABSPATH' ) || exit;

/**
 * WooCanvas_Block_Registrar
 */
class WooCanvas_Block_Registrar {

	/**
	 * Register hooks.
	 */
	public function init(): void {
		// Priority 11 so GenerateBlocks (priority 10) has already prepended its category.
		add_filter( 'block_categories_all', [ $this, 'register_category' ], 11 );
		add_action( 'init', [ $this, 'register_editor_scripts' ] );
		add_action( 'init', [ $this, 'register_blocks' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_styles' ] );
	}

	/**
	 * Add a "WooCanvas" category to the block inserter.
	 *
	 * @param array $categories Existing block categories.
	 * @return array
	 */
	public function register_category( array $categories ): array {
		$woocanvas = [
			'slug'  => 'woocanvas',
			'title' => __( 'WooCanvas', 'woocanvas' ),
			'icon'  => null,
		];

		$gb_index = array_search( 'generateblocks', array_column( $categories, 'slug' ), true );

		if ( false !== $gb_index ) {
			array_splice( $categories, $gb_index + 1, 0, [ $woocanvas ] );
			return $categories;
		}

		array_unshift( $categories, $woocanvas );
		return $categories;
	}

	/**
	 * Register editor scripts via PHP so we can declare wp-server-side-render as a dependency.
	 */
	public function register_editor_scripts(): void {
		$blocks = [
			'product-gallery',
			'add-to-cart',
			'product-tabs',
			'related-products',
		];

		foreach ( $blocks as $block ) {
			$handle = 'woocanvas-' . $block . '-editor';
			wp_register_script(
				$handle,
				WOOCANVAS_PLUGIN_URL . 'blocks/' . $block . '/index.js',
				[ 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-server-side-render' ],
				WOOCANVAS_VERSION,
				true
			);
		}
	}

	/**
	 * Enqueue WooCommerce frontend stylesheets in the block editor so SSR previews render correctly.
	 */
	public function enqueue_editor_styles(): void {
		$wc_url = WC()->plugin_url() . '/assets/css/';
		$wc_ver = WC_VERSION;

		// WooCommerce only registers these handles on the frontend, not in admin.
		// Enqueue by URL directly so the SSR preview gets the correct WC styles.
		wp_enqueue_style( 'woocanvas-wc-general', $wc_url . 'woocommerce.css', [], $wc_ver );
		wp_enqueue_style( 'woocanvas-wc-layout', $wc_url . 'woocommerce-layout.css', [], $wc_ver );
		wp_enqueue_style( 'woocanvas-wc-smallscreen', $wc_url . 'woocommerce-smallscreen.css', [ 'woocanvas-wc-layout' ], $wc_ver );

		// WooCommerce gallery starts at opacity:0 and uses its own JS to fade in.
		// That JS doesn't run in the block editor, so force visibility for the SSR preview.
		wp_register_style( 'woocanvas-editor-overrides', false, [ 'woocanvas-wc-general' ], WOOCANVAS_VERSION ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_enqueue_style( 'woocanvas-editor-overrides' );
		wp_add_inline_style( 'woocanvas-editor-overrides', '.woocommerce-product-gallery { opacity: 1 !important; }' );
	}

	/**
	 * Register all four product blocks from their block.json definitions.
	 */
	public function register_blocks(): void {
		$blocks = [
			'product-gallery',
			'add-to-cart',
			'product-tabs',
			'related-products',
		];

		foreach ( $blocks as $block ) {
			register_block_type( WOOCANVAS_PLUGIN_DIR . 'blocks/' . $block );
		}
	}
}
