<?php
/**
 * Registers WooCommerce product fields as GenerateBlocks dynamic tags.
 *
 * Each tag is available as {{wc_*}} in any GenerateBlocks Text or Headline block.
 * Tags only register when both GenerateBlocks and WooCommerce are active.
 *
 * @package WooCanvas
 */

defined( 'ABSPATH' ) || exit;

/**
 * WooCanvas_WC_Dynamic_Tags
 */
class WooCanvas_WC_Dynamic_Tags {

	/**
	 * Register hooks.
	 */
	public function init(): void {
		// Run late on init so GenerateBlocks has already bootstrapped its system.
		add_action( 'init', [ $this, 'register' ], 20 );
	}

	/**
	 * Register all WooCommerce product dynamic tags.
	 */
	public function register(): void {
		if ( ! class_exists( 'GenerateBlocks_Register_Dynamic_Tag' ) ) {
			return;
		}

		$tags = [
			[
				'title'   => __( 'Product Title', 'woocanvas' ),
				'tag'     => 'wc_product_title',
				'return'  => [ $this, 'get_title' ],
				'supports' => [ 'source' ],
			],
			[
				'title'   => __( 'Product Price', 'woocanvas' ),
				'tag'     => 'wc_product_price',
				'return'  => [ $this, 'get_price_html' ],
				'supports' => [ 'source' ],
			],
			[
				'title'   => __( 'Product Regular Price', 'woocanvas' ),
				'tag'     => 'wc_product_regular_price',
				'return'  => [ $this, 'get_regular_price' ],
				'supports' => [ 'source' ],
			],
			[
				'title'   => __( 'Product Sale Price', 'woocanvas' ),
				'tag'     => 'wc_product_sale_price',
				'return'  => [ $this, 'get_sale_price' ],
				'supports' => [ 'source' ],
			],
			[
				'title'   => __( 'Product Short Description', 'woocanvas' ),
				'tag'     => 'wc_product_short_description',
				'return'  => [ $this, 'get_short_description' ],
				'supports' => [ 'source' ],
			],
			[
				'title'   => __( 'Product Description', 'woocanvas' ),
				'tag'     => 'wc_product_description',
				'return'  => [ $this, 'get_description' ],
				'supports' => [ 'source' ],
			],
			[
				'title'   => __( 'Product SKU', 'woocanvas' ),
				'tag'     => 'wc_product_sku',
				'return'  => [ $this, 'get_sku' ],
				'supports' => [ 'source' ],
			],
			[
				'title'   => __( 'Product Stock Status', 'woocanvas' ),
				'tag'     => 'wc_product_stock_status',
				'return'  => [ $this, 'get_stock_status' ],
				'supports' => [ 'source' ],
			],
			[
				'title'   => __( 'Product Stock Quantity', 'woocanvas' ),
				'tag'     => 'wc_product_stock_quantity',
				'return'  => [ $this, 'get_stock_quantity' ],
				'supports' => [ 'source' ],
			],
			[
				'title'   => __( 'Product Categories', 'woocanvas' ),
				'tag'     => 'wc_product_categories',
				'return'  => [ $this, 'get_categories' ],
				'supports' => [ 'source' ],
			],
			[
				'title'   => __( 'Product Tags', 'woocanvas' ),
				'tag'     => 'wc_product_tags',
				'return'  => [ $this, 'get_tags' ],
				'supports' => [ 'source' ],
			],
			[
				'title'   => __( 'Product Image URL', 'woocanvas' ),
				'tag'     => 'wc_product_image_url',
				'return'  => [ $this, 'get_image_url' ],
				'supports' => [ 'source' ],
			],
			[
				'title'   => __( 'Product Average Rating', 'woocanvas' ),
				'tag'     => 'wc_product_rating',
				'return'  => [ $this, 'get_average_rating' ],
				'supports' => [ 'source' ],
			],
			[
				'title'   => __( 'Product Review Count', 'woocanvas' ),
				'tag'     => 'wc_product_review_count',
				'return'  => [ $this, 'get_review_count' ],
				'supports' => [ 'source' ],
			],
			[
				'title'   => __( 'Product Permalink', 'woocanvas' ),
				'tag'     => 'wc_product_permalink',
				'return'  => [ $this, 'get_permalink' ],
				'supports' => [ 'source' ],
			],
			[
				'title'   => __( 'Add to Cart URL', 'woocanvas' ),
				'tag'     => 'wc_product_add_to_cart_url',
				'return'  => [ $this, 'get_add_to_cart_url' ],
				'supports' => [ 'source' ],
			],
			[
				'title'   => __( 'Product Weight', 'woocanvas' ),
				'tag'     => 'wc_product_weight',
				'return'  => [ $this, 'get_weight' ],
				'supports' => [ 'source' ],
			],
			[
				'title'   => __( 'Product Dimensions', 'woocanvas' ),
				'tag'     => 'wc_product_dimensions',
				'return'  => [ $this, 'get_dimensions' ],
				'supports' => [ 'source' ],
			],
		];

		foreach ( $tags as $tag ) {
			new GenerateBlocks_Register_Dynamic_Tag(
				array_merge( $tag, [ 'type' => 'woocommerce' ] )
			);
		}
	}

	/**
	 * Resolve a WC_Product from the dynamic tag options.
	 *
	 * @param array $options Tag options passed by GenerateBlocks.
	 * @return WC_Product|false
	 */
	private function get_product( array $options ) {
		$id = GenerateBlocks_Dynamic_Tags::get_id( $options );
		return $id ? wc_get_product( $id ) : false;
	}

	// ---------------------------------------------------------------------------
	// Callbacks
	// ---------------------------------------------------------------------------

	public function get_title( array $options ): string {
		$product = $this->get_product( $options );
		return $product ? $product->get_name() : '';
	}

	public function get_price_html( array $options ): string {
		$product = $this->get_product( $options );
		return $product ? $product->get_price_html() : '';
	}

	public function get_regular_price( array $options ): string {
		$product = $this->get_product( $options );
		if ( ! $product ) return '';
		$price = $product->get_regular_price();
		return $price !== '' ? wc_price( $price ) : '';
	}

	public function get_sale_price( array $options ): string {
		$product = $this->get_product( $options );
		if ( ! $product ) return '';
		$price = $product->get_sale_price();
		return $price !== '' ? wc_price( $price ) : '';
	}

	public function get_short_description( array $options ): string {
		$product = $this->get_product( $options );
		return $product ? wpautop( $product->get_short_description() ) : '';
	}

	public function get_description( array $options ): string {
		$product = $this->get_product( $options );
		return $product ? wpautop( $product->get_description() ) : '';
	}

	public function get_sku( array $options ): string {
		$product = $this->get_product( $options );
		return $product ? $product->get_sku() : '';
	}

	public function get_stock_status( array $options ): string {
		$product = $this->get_product( $options );
		if ( ! $product ) return '';
		$status_map = [
			'instock'     => __( 'In stock', 'woocanvas' ),
			'outofstock'  => __( 'Out of stock', 'woocanvas' ),
			'onbackorder' => __( 'On backorder', 'woocanvas' ),
		];
		return $status_map[ $product->get_stock_status() ] ?? $product->get_stock_status();
	}

	public function get_stock_quantity( array $options ): string {
		$product = $this->get_product( $options );
		if ( ! $product ) return '';
		$qty = $product->get_stock_quantity();
		return $qty !== null ? (string) $qty : '';
	}

	public function get_categories( array $options ): string {
		$product = $this->get_product( $options );
		if ( ! $product ) return '';
		return wc_get_product_category_list( $product->get_id() );
	}

	public function get_tags( array $options ): string {
		$product = $this->get_product( $options );
		if ( ! $product ) return '';
		return wc_get_product_tag_list( $product->get_id() );
	}

	public function get_image_url( array $options ): string {
		$product = $this->get_product( $options );
		if ( ! $product ) return '';
		return get_the_post_thumbnail_url( $product->get_id(), 'full' ) ?: '';
	}

	public function get_average_rating( array $options ): string {
		$product = $this->get_product( $options );
		return $product ? $product->get_average_rating() : '';
	}

	public function get_review_count( array $options ): string {
		$product = $this->get_product( $options );
		return $product ? (string) $product->get_review_count() : '';
	}

	public function get_permalink( array $options ): string {
		$product = $this->get_product( $options );
		return $product ? get_permalink( $product->get_id() ) : '';
	}

	public function get_add_to_cart_url( array $options ): string {
		$product = $this->get_product( $options );
		return $product ? $product->add_to_cart_url() : '';
	}

	public function get_weight( array $options ): string {
		$product = $this->get_product( $options );
		if ( ! $product ) return '';
		$weight = $product->get_weight();
		return $weight !== '' ? wc_format_weight( (float) $weight ) : '';
	}

	public function get_dimensions( array $options ): string {
		$product = $this->get_product( $options );
		if ( ! $product ) return '';
		return wc_format_dimensions( $product->get_dimensions( false ) );
	}
}
