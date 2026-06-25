<?php
defined( 'ABSPATH' ) || exit;

$columns           = max( 2, min( 4, (int) ( $attributes['columns'] ?? 4 ) ) );
$button_color      = $attributes['buttonColor'] ?? '';
$button_text_color = $attributes['buttonTextColor'] ?? '';

/**
 * Returns CSS for the editor preview iframe.
 * Covers layout and structure only — text/colour styling is intentionally omitted
 * so the theme's defaults show through. Button colours are applied only when the
 * user has set them via InspectorControls.
 */
$build_editor_css = function () use ( $columns, $button_color, $button_text_color ): string {
	$font_url = WC()->plugin_url() . '/assets/fonts';

	$css = '
@font-face {
	font-family: star;
	src: url("' . esc_url( $font_url ) . '/WooCommerce.woff2") format("woff2"),
	     url("' . esc_url( $font_url ) . '/WooCommerce.woff") format("woff");
	font-weight: 400;
	font-style: normal;
}
.woocanvas-related-preview { pointer-events: none; }
.woocanvas-related-preview .related.products > h2 { margin: 0 0 1em; }
.woocanvas-related-preview ul.products {
	display: grid;
	grid-template-columns: repeat(' . $columns . ', minmax(0, 1fr));
	gap: 1.5em;
	list-style: none;
	padding: 0;
	margin: 0 0 1em;
}
.woocanvas-related-preview ul.products li.product {
	position: relative;
	margin: 0;
	float: none !important;
	width: auto !important;
}
.woocanvas-related-preview ul.products li.product a {
	text-decoration: none;
	color: inherit;
	display: block;
}
.woocanvas-related-preview ul.products li.product img {
	display: block;
	width: 100%;
	height: auto;
	margin: 0 0 .5em;
}
.woocanvas-related-preview .star-rating {
	overflow: hidden;
	position: relative;
	height: 1em;
	line-height: 1;
	font-size: 1em;
	width: 5.4em;
	font-family: star;
	margin: 0 0 .5em;
	display: inline-block;
}
.woocanvas-related-preview .star-rating::before {
	content: "\53\53\53\53\53";
	color: #ccc;
	float: left;
	top: 0;
	left: 0;
	position: absolute;
}
.woocanvas-related-preview .star-rating span {
	overflow: hidden;
	float: left;
	top: 0;
	left: 0;
	position: absolute;
}
.woocanvas-related-preview .star-rating span::before {
	content: "\53\53\53\53\53";
	top: 0;
	position: absolute;
	left: 0;
	color: #f00;
}
.woocanvas-related-preview .price { display: block; margin: 0 0 .75em; }
.woocanvas-related-preview a.button {
	display: block;
	text-align: center;
	padding: .618em 1em;
	width: 100%;
	box-sizing: border-box;
	text-decoration: none !important;
}
.woocanvas-related-preview .onsale {
	position: absolute;
	top: 0;
	right: 0;
	padding: .202em .618em;
	font-size: .857em;
	font-weight: 700;
}
.woocanvas-related-preview img.secondary-image { display: none !important; }
';

	if ( $button_color ) {
		$css .= '.woocanvas-related-preview a.button { background: ' . esc_attr( $button_color ) . ' !important; }' . "\n";
	}
	if ( $button_text_color ) {
		$css .= '.woocanvas-related-preview a.button { color: ' . esc_attr( $button_text_color ) . ' !important; }' . "\n";
	}

	return $css;
};

$args_filter = static function ( $args ) use ( $columns ) {
	$args['columns']        = $columns;
	$args['posts_per_page'] = $columns;
	return $args;
};

// ── EDITOR PREVIEW ────────────────────────────────────────────────────────────
if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
	$preview_ids = wc_get_products( [
		'limit'   => 1,
		'status'  => 'publish',
		'return'  => 'ids',
		'orderby' => 'date',
		'order'   => 'DESC',
	] );

	if ( empty( $preview_ids ) ) {
		echo '<p style="padding:1em;text-align:center;color:#666;">Add a published product to see a preview.</p>';
		return;
	}

	global $post;
	$post               = get_post( $preview_ids[0] );
	$GLOBALS['product'] = wc_get_product( $preview_ids[0] );
	setup_postdata( $post );

	$related_ids = wc_get_products( [
		'limit'   => $columns,
		'status'  => 'publish',
		'return'  => 'ids',
		'orderby' => 'date',
		'order'   => 'DESC',
		'exclude' => [ $preview_ids[0] ],
	] );

	$inject = static function () use ( $related_ids ) {
		return $related_ids;
	};
	add_filter( 'woocommerce_related_products', $inject );
	add_filter( 'woocommerce_output_related_products_args', $args_filter );

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<style>' . $build_editor_css() . '</style>';
	echo '<div class="woocanvas-related-preview">';
	woocommerce_output_related_products();
	echo '</div>';

	remove_filter( 'woocommerce_output_related_products_args', $args_filter );
	remove_filter( 'woocommerce_related_products', $inject );
	wp_reset_postdata();
	return;
}

// ── FRONTEND ──────────────────────────────────────────────────────────────────
$product = wc_get_product( get_the_ID() );
if ( ! $product ) {
	return;
}

$GLOBALS['product'] = $product;
$block_id           = wp_unique_id( 'woocanvas-related-' );

$inline_css = '';
if ( $button_color ) {
	$inline_css .= '#' . $block_id . ' .button { background-color: ' . esc_attr( $button_color ) . ' !important; }' . "\n";
}
if ( $button_text_color ) {
	$inline_css .= '#' . $block_id . ' .button { color: ' . esc_attr( $button_text_color ) . ' !important; }' . "\n";
}

add_filter( 'woocommerce_output_related_products_args', $args_filter );
?>
<?php if ( $inline_css ) : ?>
<style><?php echo $inline_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></style>
<?php endif; ?>
<div id="<?php echo esc_attr( $block_id ); ?>" <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php woocommerce_output_related_products(); ?>
</div>
<?php
remove_filter( 'woocommerce_output_related_products_args', $args_filter );
