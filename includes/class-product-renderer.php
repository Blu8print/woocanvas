<?php
/**
 * Replaces WooCommerce's default single product output with the block content
 * stored in the WooCanvas template post.
 *
 * Falls back to WooCommerce's default rendering when the template post has no
 * block content, so product pages remain functional out of the box.
 *
 * @package WooCanvas
 */

defined( 'ABSPATH' ) || exit;

/**
 * WooCanvas_Product_Renderer
 */
class WooCanvas_Product_Renderer {

	/**
	 * Register hooks.
	 */
	public function init(): void {
		add_action( 'template_redirect', [ $this, 'maybe_override' ] );
	}

	/**
	 * On single product pages: if the template post has content, remove
	 * WooCommerce's default hooks and replace them with our block output.
	 */
	public function maybe_override(): void {
		if ( ! is_singular( 'product' ) ) {
			return;
		}

		$content = $this->get_template_content();

		if ( null === $content ) {
			return; // fall back to WooCommerce default
		}

		// Remove WooCommerce's default product output hooks.
		remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );
		remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );
		remove_all_actions( 'woocommerce_single_product_summary' );
		remove_all_actions( 'woocommerce_after_single_product_summary' );

		// Output rendered block content in place of the default summary.
		add_action(
			'woocommerce_before_single_product_summary',
			static function () use ( $content ) {
				echo do_blocks( $content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			},
			5
		);
	}

	/**
	 * Returns the template post's block content, or null if it's empty.
	 */
	private function get_template_content(): ?string {
		$post_id = (int) get_option( WooCanvas_CPT_Manager::OPTION_KEY, 0 );

		if ( ! $post_id ) {
			return null;
		}

		$post = get_post( $post_id );

		if ( ! $post instanceof WP_Post ) {
			return null;
		}

		$content = trim( $post->post_content );

		return '' !== $content ? $content : null;
	}
}
